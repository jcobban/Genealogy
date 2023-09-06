<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  DistrictMarriagesEdit.php                                           *
 *                                                                      *
 *  Display form for editting information about marriages               *
 *  within a report submitted by a minister of religion to report       *
 *  marriages he performed during a year prior to confederation.        *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Domain      2 letter country code + 2 letter province code      *
 *      Volume      volume number                                       *
 *      ReportNo    report number                                       *
 *                                                                      *
 *  History:                                                            *
 *      2017/07/18      split off from CountyMarriagesEdit.php          *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/22      use CountyMarriageSet                           *
 *                      do not display decimal fraction on report no    *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2021/07/09      use template                                    *
 *      2023/08/08      support placeholder attribute for given names   *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/CountyMarriage.inc";
require_once __NAMESPACE__ . "/CountyMarriageSet.inc";
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  compareReports                                                      *
 *                                                                      *
 *  Implement sorting order of instances of CountyMarriage              *
 *  This is required because PHP does not yet have a way to access      *
 *  a normal member function of a class to perform comparisons.         *
 ************************************************************************/
function compareReports(CountyMarriage $r1, 
                        CountyMarriage $r2)
{
    return $r1->compare($r2);
}

// validate parameters
$domain                 = 'CAUC';
$prov                   = 'UC';
$province               = 'Upper Canada (Ontario)';
$cc                     = 'CA';
$countryName            = 'Canada';
$image                  = '';
$lang                   = 'en';
$volume                 = null;
$reportNo               = null;
$itemNo                 = null;
$role                   = null;
$offset                 = null;
$limit                  = null;
$domaintext             = null;
$provtext               = null;
$imagetext              = null;
$volumetext             = null;
$reportNoText           = null;
$itemNotext             = null;
$roletext               = null;
$offsettext             = null;
$limittext              = null;
$ministerName           = '';
$faith                  = '';
$residence              = '';
$page                   = '';
$image                  = '';
$fixup                  = true;

if (isset($_POST) && count($_POST) > 0)
{                   // perform update
    $record             = null;
    $domain             = null;
    $volume             = null;
    $reportNo           = null;
    $create             = false;
    $fixup              = false;

    $parmsText  = "<p class=\"label\">\$_POST</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
    foreach($_POST as $key => $value)
    {               // process each parameter
        $safevalue              = htmlspecialchars($value);
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$safevalue</td></tr>\n"; 
        if (preg_match("/^([a-zA-Z_]+)(\d*)$/", $key, $matches))
        {
            $column             = $matches[1];
            $row                = $matches[2];
        }
        else
        {
            $column             = $key;
            $row                = '';
        }

        switch(strtolower($column))
        {       // act on specific parameters
            case 'domain':
            {   // Domain
                if (preg_match('/^[A-Za-z]{4,5}$/', $value))
                {
                    $domain         = strtoupper($value);
                    if ($domain == 'CAON')
                        $domain     = 'CAUC';
                    else
                    if ($domain == 'CAQC')
                        $domain     = 'CALC';
                }
                else
                    $domaintext = $safevalue;
                break;
            }   // Domain

            case 'volume':
            {   // volume
                if (ctype_digit($value))
                    $volume     = $value;
                else
                    $volumetext = $safevalue;
                break;
            }

            case 'reportno':
            {
                if (is_int($value) ||
                    (strlen($value) > 0 && ctype_digit($value)))
                {       // valid
                    $reportNo       = $value;
                    $getParms['reportno']   = $reportNo;
                }       // valid
                else
                if (substr($value, -2) == '½' || floor($value) != $value)
                {
                    $reportNo       = floor(substr($value, 0, strlen($value) - 2)) + 0.5;
                    $reportNoText   = floor($reportNo) . '½';
                    $getParms['reportno']   = $reportNo;
                }
                else
                    $reportNoText   = $value;
                break;
            }

            case 'itemno':
            {
                if (ctype_digit($value))
                    $itemno     = $value;
                else
                    $itemnotext = $safevalue;
                break;
            }

            case 'role':
            {
                if ($record &&
                    $record->get('givennames') != 'New Bride' &&
                    $record->get('givennames') != 'New Groom')
                {
                    $record->dump('Save');
                    $record->save();
                    $reports[]  = $record;
                }
                $role       = $value;
                $getParms   = array('domain'    => $domain,
                                    'volume'    => $volume,
                                    'reportno'  => $reportNo,
                                    'itemno'    => $itemno,
                                    'role'      => $role);
                $record     = new CountyMarriage($getParms);
                break;
            }

            case 'givennames':
            case 'surname':
            case 'age':
            case 'residence':
            case 'birthplace':
            case 'fathername':
            case 'mothername':
            case 'date':
            case 'licensetype':
            case 'witnessname':
            case 'remarks':
            case 'idir':
            {
                $record->set($column, $value);
                break;
            }

            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang
        }           // act on specific parameters
    }               // process each parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    // ensure still sorted by keys
    usort($reports,'Genealogy\compareReports');
}                   // perform update
else
if (isset($_GET) && count($_GET) > 0)
{                   // initial report
    $getParms           = array();
    $fixup              = true;

    $parmsText  = "<p class=\"label\">\$_GET</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue      = htmlspecialchars($value);
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'prov':
            {
                if (preg_match('/^[A-Za-z]{2}$/', $value))
                {
                    $prov       = strtoupper($value);
                    $domain     = 'CA' . $prov;
                    if ($domain == 'CAON')
                        $domain     = 'CAUC';
                    else
                    if ($domain == 'CAQC')
                        $domain     = 'CALC';
                }
                else
                    $provtext   = $safevalue;
                break;
            }       // state/province code

            case 'domain':
            case 'regdomain':
            {
                if (preg_match('/^[A-Za-z]{4,5}$/', $value))
                {
                    $domain     = strtoupper($value);
                    if ($domain == 'CAON')
                        $domain     = 'CAUC';
                    else
                    if ($domain == 'CAQC')
                        $domain     = 'CALC';
                }
                else
                    $domaintext = $safevalue;
                break;
            }       // domain code

            case 'volume':
            {
                if (is_int($value) ||
                    (strlen($value) > 0 && ctype_digit($value)))
                {       // valid
                    $volume         = $value;
                    $getParms['volume'] = $volume;
                }       // valid
                else
                    $volumetext     = $safevalue;
                break;
            }

            case 'reportno':
            {
                if (is_int($value) ||
                    (strlen($value) > 0 && ctype_digit($value)))
                {       // valid
                    $reportNo       = $value;
                    $getParms['reportno']   = $reportNo;
                }       // valid
                else
                if (substr($value, -2) == '½' ||
                    substr($value, -2) == '.5')
                {
                    $reportNo   = floor(substr($value, 0, strlen($value) - 2)) + 0.5;
                    $getParms['reportno']   = $reportNo;
                }
                else
                if ($value != '')
                    $reportNoText   = $safevalue;
                break;
            }

            case 'itemno':
            {
                if (is_int($value) ||
                    (strlen($value) > 0 && ctype_digit($value)))
                {       // valid
                    $itemNo         = $value;
                    $getParms['itemno'] = $itemNo;
                }       // valid
                else
                    $itemNotext     = $safevalue;
                break;
            }

            case 'role':
            {
                if (strlen($value) > 0)
                {
                    $role           = $value;
                    $getParms['role']   = $role;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'givennames':
            {
                if (strlen($value) > 0)
                {
                    $getParms['givennames'] = $value;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'surname':
            {
                if (strlen($value) > 0)
                {
                    $getParms['surname']    = $value;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'soundex':
            {
                if (strtolower($value) == 'y')
                {
                    if (isset($getParms['surname']))
                    {
                        $surname    = $getParms['surname'];
                        unset($getParms['surname']);
                        $getParms['surnamesoundex'] = $surname;
                    }
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'residence':
            {
                if (strlen($value) > 0)
                {
                    $getParms['residence']  = $value;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'offset':
            {
                if (is_int($value) || ctype_digit($value))
                {           // valid
                    $offset         = $value;
                    $getParms['offset'] = $offset;
                    if ($offset > 0)
                    {
                        $fixup          = false;
                        if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                    }
                }           // valid
                else
                    $offsettext     = $safevalue;
                break;
            }               // offset

            case 'count':
            case 'limit':
            {
                if (is_int($value) || ctype_digit($value))
                {           // valid
                    $limit          = $value;
                    $getParms['limit']  = $limit;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }           // valid
                else
                    $limittext      = $safevalue;
                break;
            }               // count

            case 'debug':
            {
                break;
            }               // debug handled by common code

            case 'lang':
            {               // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }               // lang

            default:
            {
                $warn       .= "Unexpected parameter $key=\"$value\". ";
                break;
            }
        }                   // check supported parameters
    }                       // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    if (strlen($msg) == 0)
    {       // execute the query to get the contents of the page
        $reports                = new CountyMarriageSet($getParms);
    }       // no errors detected
    else
        $reports                = array();

    if (count($reports) == 0 &&
        !is_null($domain) && !is_null($volume) && !is_null($reportNo))
    {                       // have required parameters
        $create                 = true;
        if (is_null($itemNo))
        {                   // initialize new report with 10 empty entries
            $item               = 1;
            $lastItem           = 10;
        }                   // initialize new report
        else
        {                   // create one empty entry
            $item               = $itemNo;
            $lastItem           = $itemNo;
        }                   // create one empty entry

        $getParms               = array('domain'    => $domain,
                                        'volume'    => $volume,
                                        'reportNo'  => $reportNo);
        for(;$item <= $lastItem; $item++)
        {                   // loop creating new empty records
            $getParms['itemNo'] = $item;
            $getParms['role']   = 'G';
            $groom              = new CountyMarriage($getParms);
            $groom->set('placeholder', 'New Groom');
            $reports[]          = $groom;
            $getParms['role']   = 'B';
            $bride              = new CountyMarriage($getParms);
            $bride->set('placeholder', 'New Bride');
            $reports[]          = $bride;
        }                   // loop creating new empty records
    }                       // have required parameters
    else
        $create                 = false;
}                           // initial report

if (canUser('edit'))
    $action         = 'Update';
else
    $action         = 'Display';

$template           = new FtTemplate("DistrictMarriagesEdit$action$lang.html");
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

// get domain information
if (is_null($domain))
    $msg                    .= "Missing parameter Domain. ";
else
{                       // have domain identifier
    $domainObj              = new Domain(array('domain'     => $domain,
                                               'language'   => $lang));
    if ($domainObj->isExisting())
    {
        $getParms['domain'] = $domain;
        $cc                 = substr($domain, 0, 2);
        $prov               = substr($domain, -2);
        $province           = $domainObj->get('name');
    }
    else
    {
        $msg                .= "Domain=\"$domain\" unsupported. ";
        $province           = 'Domain : ' . $domain;
    }
    $countryObj             = new Country(array('code' => $cc));
    $countryName            = $countryObj->getName();
}

if (is_string($reportNoText))
{
    $warn   .= "<p>DistrictMarriagesEdit.php: " . __LINE__ .
                    " reportno=\"$reportNoText\"</p>\n";
}

$minister                       = null;
if (is_string($domaintext))
{
    $warn   .= "<p>DistrictMarriagesEdit.php: " . __LINE__ .
                    " domain=\"$domaintext\"</p>\n";
}
else
if ($domain && $volume && $reportNo)
{                           // no errors detected
    $getParms                   = array('domain'    => $domain,
                                        'volume'    => $volume,
                                        'reportno'  => $reportNo);
    $minister                   = new CountyMarriageReport($getParms);
    if ($minister->isExisting())
    {               // record describing report
        $ministerName           = $minister->get('givennames') . ' ' .
                                        $minister->get('surname');
        $faith                  = $minister->get('faith');
        $residence              = $minister->get('residence');
        $page                   = $minister->get('page');
        $image                  = $minister->get('image');
    }               // record describing minister
    else
        $minister               = null;
}                           // no errors detected

$title          = "$countryName: $province: Marriage Report ";
if (is_null($volume))
{
    $template['volumeLink']->update(null);
    $template['volumeTitle']->update(null);
    $template->set('VOLUME',            'null');
}
else
{
    $title      .= "Volume $volume ";
    $template->set('VOLUME',            $volume);
}

if (is_null($reportNo))
{
    $template['reportLink']->update(null);
    $template['reportTitle']->update(null);
    $template->set('REPORTNO',          'null');
}
else
{
    $template->set('REPORTNO',          $reportNo);
    $title      .= "Report No. $reportNo ";
}

if (is_null($itemNo))
{
    $template['itemTitle']->update(null);
    $template['itemNo']->update(null);
    $template->set('ITEMNO',            'null');
}
else
{
    $template->set('ITEMNO',            $itemNo);
    $title      .= "Item $itemNo ";
}

if ($create)
    $title      .= $t["Create"];
else
    $title      .= $t["Update"];


// set common substitutions
$template->set('CC',                    $cc);
$template->set('COUNTRYNAME',           $countryName);
$template->set('PROV',                  $prov);
$template->set('PROVINCE',              $province);
$template->set('DOMAIN',                $domain);
$template->set('DOMAINNAME',            $province);
$template->set('TITLE',                 $title);

// pass report information to template
$template->set('MINISTERNAME',          $ministerName);
$template->set('FAITH',                 $faith);
$template->set('RESIDENCE',             $residence);
$template->set('PAGE',                  $page);
$template->set('IMAGE',                 $image);
$template->set('REPORTNOTEXT',          $reportNoText);

if (strlen($msg) == 0)
{
    $showReport         = $domain && $volume && $reportNo;
    if ($showReport)
    {       // show pointers for previous and next entry
        if (is_null($itemNo) || $itemNo == 1)
        {
            if ($reportNo == floor($reportNo))
                $prevReportNo   = $reportNo - 1;
            else
                $prevReportNo   = floor($reportNo);
            $prevUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                          $prevReportNo;
            $prevText   = $prevReportNo;
        }
        else
        {
            $prevUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                          $reportNo . "&ItemNo=" . ($itemNo - 1);
            $prevText   = $reportNo . '-' . ($itemNo - 1);
        }
        if (is_null($itemNo))
        {
            if ($reportNo == floor($reportNo))
                $nextReportNo   = $reportNo + 1;
            else
                $nextReportNo   = ceil($reportNo);
            $nextUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                          $nextReportNo;
            $nextText   = $nextReportNo;
        }
        else
        {
            $nextUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                          $reportNo . "&ItemNo=" . ($itemNo + 1);
            $nextText   = $reportNo . '-' . ($itemNo + 1);
        }

        $template->set('PREVTEXT',      $prevText);
        $template->set('PREVURI',       $prevUri);
        $template->set('NEXTTEXT',      $nextText);
        $template->set('NEXTURI',       $nextUri);
    }
    else
        $template['topBrowse']->update(null);

    // show the response
    $rowelt             			= $template['Row$row'];
    $data               			= '';
    if (count($reports) > 0)
    {
        $rowtext        			= $rowelt->outerHTML;
        $reportNo       			= 0;
        $page           			= 1;
        $row            			= 0;
        $nextRole       			= 'G';
        $tempRecord     			= null;
        $date           			= '';
        $licenseType    			= 'L';
        $last           			= end($reports);
        $first          			= reset($reports);
        foreach($reports as $record)
        {
            $itemNo                 = $record->get('itemno'); 
            $role                   = $record->get('role');
            $givennames             = $record->get('givennames');
            if ($record->offsetExists('placeholder'))
                $placeholder        = $record->get('placeholder');
            else
                $placeholder        = '';
            $surname                = $record->get('surname');
            $row++;
            if ($row < 10)
                $row                = '0' . $row;
            $domain                 = $record->get('domain'); 
            $volume                 = $record->get('volume'); 
            $reportNo               = $record->get('reportno'); 
            if ($reportNo == floor($reportNo))
                $reportNoText       = intval($reportNo);
            else
                $reportNoText       = floor($reportNo) . '½';
            $itemNo                 = $record->get('itemno'); 
            $role                   = $record->get('role');

            // get values in a form suitable for presenting in HTML
            $givennames             = $record->get('givennames');
            $givennames             = str_replace("'","&#39;",$givennames);
            $surname                = $record->get('surname');
            $surname                = str_replace("'","&#39;",$surname);
            $age                    = $record->get('age'); 
            $residence              = $record->get('residence'); 
            $residence              = str_replace("'","&#39;",$residence);
            $birthplace             = $record->get('birthplace'); 
            $birthplace             = str_replace("'","&#39;",$birthplace);
            $fathername             = $record->get('fathername'); 
            $fathername             = str_replace("'","&#39;",$fathername);
            $mothername             = $record->get('mothername'); 
            $mothername             = str_replace("'","&#39;",$mothername);
            $witness                = $record->get('witnessname');
            $witness                = str_replace("'","&#39;",$witness);
            $remarks                = $record->get('remarks');
            $remarks                = str_replace("'","&#39;",$remarks);
            $date                   = $record->get('date');
            $licenseType            = $record->get('licensetype');
            $idir                   = $record->get('idir');

            if ($role == 'G')
            {       // groom record
                $sexclass           = 'male';
            }       // groom record
            else
            {
                $sexclass           = 'female';
            }
            $rtemplate              = new Template($rowtext);
            $rtemplate->set('rtemplate',        $rtemplate);
            $rtemplate->set('itemNo',           $itemNo);
            $rtemplate->set('role',             $role);
            $rtemplate->set('givennames',       $givennames);
            $rtemplate->set('surname',          $surname);
            $rtemplate->set('row',              $row);
            $rtemplate->set('domain',           $domain);
            $rtemplate->set('volume',           $volume);
            $rtemplate->set('reportNo',         $reportNo);
            $rtemplate->set('reportNoText',     $reportNoText);
            $rtemplate->set('givennames',       $givennames);
            $rtemplate->set('placeholder',      $placeholder);
            $rtemplate->set('surname',          $surname);
            $rtemplate->set('age',              $age);
            $rtemplate->set('residence',        $residence);
            $rtemplate->set('birthplace',       $birthplace);
            $rtemplate->set('fathername',       $fathername);
            $rtemplate->set('mothername',       $mothername);
            $rtemplate->set('witness',          $witness);
            $rtemplate->set('remarks',          $remarks);
            $rtemplate->set('date',             $date);
            $rtemplate->set('licenseType',      $licenseType);
            $rtemplate->set('idir',             $idir);
            $rtemplate->set('sexclass',         $sexclass);
            if ($idir == 0)
            {
                $rtemplate['Link$row']->update(null);
                $clear      = $rtemplate['Clear$row'];
                if ($clear)
                    $clear->update(null);
            }
            else
                $rtemplate['Find$row']->update(null);

            $data           .= $rtemplate->compile();
        }                       // process all rows
        $template['norecords']->update(null);
    }

    $rowelt->update($data);
}                           // no errors
else
{                           // errors
    $template['topBrowse']->update(null);
    $template['countyForm']->update(null);
}                           // errors

$template->display();
