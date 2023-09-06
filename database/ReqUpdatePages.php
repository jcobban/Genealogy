<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdatePages.php                                                  *
 *                                                                      *
 *  Update a page of the Pages database.                                *
 *                                                                      *
 *  History (of ReqUpdatePages.html):                                   *
 *      2010/10/01      Reformat to new page layout.                    *
 *      2011/01/20      Make <select size='9'>                          *
 *      2011/06/27      add support for 1916                            *
 *      2011/11/04      add support for mouseover help                  *
 *      2013/07/30      add Facebook like                               *
 *                      correct context specific Help                   *
 *      2013/08/17      add support for 1921                            *
 *      2014/06/02      do not use table for layouts                    *
 *      2015/05/25      help pages were not displayed in new tab/window *
 *                      misspelled name of PageFormHelp                 *
 *                                                                      *
 *  History (of ReqUpdatePages.php):                                    *
 *      2015/06/02      renamed and made conditional on user's auth     *
 *                      display warning messages                        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/03/16      support dynamically change Census passed to     *
 *                      PageForm.php on submit                          *
 *      2017/02/07      use class Country                               *
 *                      validate census identifier                      *
 *      2017/09/12      use get( and set(                               *
 *      2019/12/04      use FtTemplate                                  *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$censusId               = null;
$censusIdText           = null;
$cc                     = 'CA';
$countryName            = 'Canada';
$censusYear             = '';
$provinceCode           = '';
$distId                 = '';
$subdistId              = '';
$division               = '';
$search                 = '';
$conj                   = '?';
$lang                   = 'en';
$selected               = array(0   => ' selected="selected"',
                                'QC'    => array(1831   => ''),
                                'CA'    => array(1851   => '',
                                                 1861   => '',
                                                 1871   => '',
                                                 1881   => '',
                                                 1891   => '',
                                                 1901   => '',
                                                 1906   => '',
                                                 1911   => '',
                                                 1916   => '',
                                                 1921   => '')
                                );

if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
            "<td class='white left'>$safevalue</td></tr>\n";

        switch(strtolower($key))
        {       // act on specific keys
            case 'census':
            case 'censusid':
            {
                $search             = "$conj$key=$value";
                $conj               = '&';
                if (preg_match('/^[0-9]{4}$/', $value))
                {               // old format only includes year
                    $censusId       = 'CA' . $censusYear;
                }       // old format only includes year
                else
                if (preg_match('/^[a-zA-Z]{2,5}[0-9]{4}$/', $value))
                {       // CCYYYY
                    $censusId       = strtoupper($value);
                }       // CCYYYY
                else
                    $censusIdText   = $safevalue;
                break;
            }

            case 'province':
            {
                $search             = "$conj$key=$value";
                $conj               = '&';
                $provinceCode       = $value;
                break;
            }

            case 'district':
            {
                $search             = "$conj$key=$value";
                $conj               = '&';
                $distid             = $value;
                break;
            }

            case 'subdistrict':
            {
                $search             = "$conj$key=$value";
                $conj               = '&';
                $subdistid          = $value;
                break;
            }

            case 'division':
            {
                $search             = "$conj$key=$value";
                $conj               = '&';
                $division           = $value;
                break;
            }

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

        }       // act on specific keys
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

$template                   = new FtTemplate("ReqUpdatePages$lang.html");
$translate                  = $template->getTranslate();
$t                          = $translate['tranTab'];

// determine whether the invoker can update
if (canUser('edit'))
    $action                 = $t["Update"];
else
    $action                 = $t["Display"];

$template->updateTag('otherStylesheets',    
                     array('filename'   => 'ReqUpdatePages'));

if (is_string($censusIdText))
{
    $msg            .= $template['censusInvalid']->replace('$censusid', $censusIdText);
}
else
if (is_string($censusId))
{
    $census             = new Census(array('censusid'   => $censusId));
    if (!$census->isExisting())
        $msg            .= $template['censusUnsupported']->replace('$censusid', $censusId);
    $cc                 = $census->get('cc');
    $censusYear         = $census->get('year');
    $partof             = $census->get('partof');
    $provinceCode       = $census->get('province');
    $selected[0]        = '';
    $selected[$cc][$censusYear] = ' selected="selected"';
}
else
{
    $msg            .= $template['censusUndefined']->innerHTML;
    $censusYear     = '';
}

$template->set('ACTION',                $action);
$countryObj                 = new Country(array('code' => $cc));
$countryName                = $countryObj->getName();
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$search                     .= $conj;
$template->set('SEARCH',            $search);

if (strlen($msg) == 0)
{
    $template->set('CENSUSID',          $censusId);
    $template->set('CENSUSYEAR',        $censusYear);
    $template->set('PROVINCECODE',      $provinceCode);
    $template->set('PROVINCE',          $provinceCode);
    $template->set('DISTID',            $distId);
    $template->set('SUBDISTID',         $subdistId);
    $template->set('DIVISION',          $division);
    $template->set('SELECTED',          $selected);
}
else
{
    $template->set('CENSUSYEAR',        $censusYear);
    $template['distForm']->update(null);
}

$template->display();
