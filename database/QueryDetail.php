<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  QueryDetail.php                                                     *
 *                                                                      *
 *  Display query dialog for a census of Canada.                        *
 *                                                                      *
 *  Parameters (passed by method='get'):                                *
 *      Census          identifier of census 'XX9999'                   *
 *      Province        optional 2 letter province code                 *
 *                                                                      *
 *  History:                                                            *
 *      2017/09/19      created                                         *
 *      2017/10/16      use class DomainSet                             *
 *      2018/01/04      remove Template from template file names        *
 *      2018/05/20      add popups                                      *
 *      2019/02/21      use new FtTemplate constructor                  *
 *      2019/04/06      use new FtTemplate::includeSub                  *
 *      2019/07/30      use Record->selected                            *
 *      2019/11/17      move CSS to <head>                              *
 *		2021/04/04      escape CONTACTSUBJECT                           *
 *		2023/01/23      protect against XSS                             *
 *		2023/08/23      improve parameter checking                      *
 *		                use JSON                                        *
 *		                include province parameter for initial dists    *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// set default values that are overriden by parameters

$censusYear         = 1881;         // default census year
$censusId           = 'CA1881';     // default census year
$censusIdText       = null;         // invalid censusID
$cc                 = 'CA';         // default country code
$countryName        = 'Canada';     // default country name
$lang               = 'en';         // default language
$langtext           = null;
$province           = '';           // default selected province
$provincetext       = null;
$states             = 'ABBCMBNBNSNTONPIQCSKYT';
$censusRec          = null;         // instance of Census

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {               // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        if (is_array($value))
            $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>array</td></tr>\n";
        else
            $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n";

        switch(strtolower($key))
        {           // switch on parameter name
            case 'census':
            {           // Census identifier
                if (preg_match('/^\d\d\d\d$/', $value))
                    $censusId           = "CA$value";
                else
                if (preg_match('/^[a-zA-Z]{2}\d\d\d\d$/', $value))
                    $censusId           = strtoupper($value);
                else
                if (preg_match('/^[a-zA-Z]{5,6}$/', $value))
                    $censusId           = strtoupper($value);
                else
                    $censusIdText       = $safevalue;
                break;
            }           // Census identifier

            case 'province':
            case 'state':
            {           // province identifier
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
                    $province           = strtoupper($value);
                else
                    $provincetext       = $safevalue;
                break;
            }           // province identifier

            case 'lang':
            {           // language code
                $lang       = FtTemplate::validateLang($value,
                                                       $langtext);
                break;
            }           // language code

        }           // switch on parameter name
    }               // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

// interpret census identifier
if (strtoupper($censusId) == 'CAALL')
{                   // special census identifier to search all
    $cc                 = substr($censusId, 0, 2);
    $censusYear         = "All";
    $province           = 'CW'; // for pre-confederation
}                   // special census identifier
else
{                   // full census identifier
    $censusRec          = new Census(array('censusid'   => $censusId));
    if ($censusRec->isExisting())
    {
        $cc             = substr($censusId, 0, 2);
        $censusYear     = substr($censusId, 2);
        if ($censusRec->get('partof'))
        {
            $province   = substr($censusId, 0, 2);
            $cc         = $censusRec->get('partof');
            $parentRec  = new Census(array('censusid' =>
                                        $cc . $censusYear));
            $states     = $parentRec->get('provinces');
        }
        else
            $states     = $censusRec->get('provinces');
    }
    else
    {
        $cc             = substr($censusId, 0, 2);
        $censusYear     = substr($censusId, 2);
    }
    if ($province == '' && $censusYear < 1867)
        $province       = 'CW';
}                   // full census identifier

// create template
if (strtoupper($censusYear) == 'ALL')
    $censusYear         = 'All';

$tempBase               = $document_root . '/templates/';
if (file_exists($tempBase . "Query$cc$censusYear$lang.html"))
    $templateName       = "Query$cc$censusYear$lang.html";
else
if (file_exists($tempBase . "Query$cc$censusYear" . "en.html"))
    $templateName       = "Query$cc$censusYear" . "en.html";
else
    $templateName       = "QueryUnsupported$lang.html";
$template               = new FtTemplate($templateName);

$includePop             = "CensusQueryPopups$lang.html";

$template->includeSub($includePop,
                      'POPUPS');

// warn for undefined census
if (is_string($censusIdText))
    $msg                .= $template['censusInvalid']->replace('$censusId', $censusIdText);
if (!$censusRec->isExisting())
    $msg                .= $template['censusUndefined']->replace('$censusId', $censusId);

if (is_string($provincetext))
    $msg                .= $template['stateInvalid']->replace('$province', $provincetext);

// support for countries other than Canada
$countryObj             = new Country(array('code'=> $cc));
$countryName            = $countryObj->getName($lang);

// determine contents of province/state selection list
$stateArray             = array();
for ($i = 0; $i < strlen($states); $i += 2)
    $stateArray[]       =  $cc . substr($states, $i, 2);
$getParms               = array('domain'    => $stateArray,
                                'lang'      => $lang);
$stateList              = new DomainSet($getParms);
if ($stateList->count() == 0)
{                   // no names for the requested language
    $getParms           = array('domain'    => $stateArray,
                                'lang'      => 'en');
    $stateList          = new DomainSet($getParms);
}                   // no names for the requested language
if ($stateList->offsetExists($cc . $province))
{
    $state              = $stateList[$cc . $province];
    $state->selected    = true;
}
else
{
    $state              = null;
    if ($province != '')
    $msg    .= $template['stateUndefined']->replace('$province', $province);
}

$template->updateTag('otherStylesheets',
                     array('filename'   => 'QueryDetail'));
$template->set('CENSUSYEAR',        $censusYear);
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CENSUSID',          $censusId);
$template->set('LANG',              $lang);
$template->set('CENSUS',            $censusYear);
$template->set('CONTACTTABLE',      'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));

$template->updateTag('stateoption', $stateList);

$template->display();
