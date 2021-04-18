<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdateDists.php                                                  *
 *                                                                      *
 *  Request to update or view a portion of the Districts table.         *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/22      created                                         *
 *      2010/11/24      link to help page                               *
 *      2011/06/27      add support for 1916                            *
 *      2013/04/13      support being invoked without edit              *
 *                      authorization better                            *
 *                      change to PHP                                   *
 *      2013/08/17      add support for 1921                            *
 *      2013/09/04      pass full census identifiers for post 1867      *
 *      2013/09/05      validate Census parameter                       *
 *      2013/11/16      gracefully handle lack of database server       *
 *                      connection                                      *
 *      2013/12/28      use CSS for layout                              *
 *      2015/06/02      display warning messages                        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/12/10      escape province names                           *
 *      2016/01/20      add id to debug trace div                       *
 *                      use class Census to get census information      *
 *                      built selection list dynamically from database  *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/15      use class Template                              *
 *      2017/11/04      $provinces erroneously set to empty array       *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/11      htmlspecchars moved to Template class           *
 *      2019/02/21      use new FtTemplate constructor                  *
 *                      improve support of non-Canadian Censuses        *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/05/02      wrong index into set of Domains                 *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$censusId               = '';
$censusYear             = '';
$provinces              = '';
$cc                     = 'CA';
$countryName            = 'Canada';
$province               = 'CW';
$lang                   = 'en';

foreach($_GET as $key => $value)
{       // loop through parameters
    switch(strtolower($key))
    {
        case 'cc':
        {
            if (strlen($value) == 2)
                $cc     = strtoupper($value);
            break;
        }

        case 'census':
        {
            // support old parameter value
            if (strlen($value) == 4)
            {
                $censusId   = 'CA' . $value;
                $censusYear = $value;
            }
            else
            {
                $censusId   = $value;
                $cc         = strtoupper(substr($censusId,0,2));
            }
            break;
        }

        case 'province':
        {           // province code
            $province       = $value;
            if (strlen($value) == 2)
            {
                $pos        = strpos($provinces, $value);
                if ($pos === false && ($pos & 1) == 1)
                    $msg    .= "Invalid value '$value' for Province. ";
            }
            else
            if (strlen($value) != 0)
                $msg    .= "Invalid value '$value' for Province. ";
            break;
        }           // province code

        case 'lang':
        {
                $lang               = FtTemplate::validateLang($value);
            break;
        }           // language code

    }               // act on specific parameters
}                   // loop through parameters

// notify the invoker if they are not authorized
$update             = canUser('edit');
$template           = new FtTemplate("ReqUpdateDists$lang.html");

/************************************************************************
 *      $censusList                                                     *
 *                                                                      *
 *      List of censuses to choose from                                 *
 ************************************************************************/
if (strlen($censusId) >= 6)
{
    $census         = new Census(array('censusid'   => $censusId));
    $cc             = $census['cc'];
    if (strlen($census['province']) > 0)
        $province   = $census['province'];
    $censusYear     = $census['year'];
}
$censusList         = array();
$getParms           = array('cc'    => $cc);
$censuses           = new CensusSet($getParms);
foreach($censuses as $census)
{
    $censusList[$census->get('censusid')] =
                    array(  'id'        => $census->get('censusid'),
                            'name'      => $census->get('name'),
                            'selected'  => '');
}

if ($census->isExisting())
{
    $provinces      = $census->get('provinces');
    $censusList[$censusId]['selected'] = "selected='selected'";
}
else
{
    $warn           .= "<p>Census '$censusId' is unsupported</p>\n";
    $provinces      = '';
}
$country            = new Country(array('code' => $cc));
$countryName        = $country->get('name');

$template->set('CENSUSYEAR',    $censusYear);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('CENSUSID',      $censusId);
$template->set('PROVINCE',      $province);
$template->set('CONTACTTABLE',  'Districts');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));

$template->updateTag('censusOpt', $censusList);
if ($censusYear > 1867)
{           // post confederation census
    if ($province == 'CW' || $province == '')
        $template->updateTag('allProvincesOpt',
                         array('selected' => "selected='selected'"));
    else
        $template->updateTag('allProvincesOpt',
                         array('selected' => ''));
}
else
    $template->updateTag('allProvincesOpt', null);

$getParms           = array('cc' => $cc);
$domains            = new DomainSet($getParms);
$provArray          = array();
for ($ip = 0; $ip < strlen($provinces); $ip = $ip + 2)
{               // loop through all provinces
    $pc             = substr($provinces, $ip, 2);
    $domainObj      = $domains["$cc$pc"];
    if (is_null($domainObj))
    {
        error_log("ReqUpdateDists.php: " . __LINE__ . " Province code '$cc$pc' not found in Domains of country code '$cc'\n");
        $domainObj  = new Domain(array('code' => "$cc$pc"));
    }
    $provinceName   = $domainObj->get('name');
    if ($pc == $province)
        $seld       = "selected='selected'";
    else
        $seld       = '';
    $provArray[$pc] = array('pc'        => $pc,
                            'name'      => $provinceName,
                            'selected'  => $seld);
}
$template->updateTag('provinceOpt', $provArray);
$template->display();
showTrace();
