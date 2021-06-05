<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  CountyMarriagesEdit.php                                             *
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
 *      2016/01/29      created                                         *
 *      2016/03/18      after update ensure groom before bride          *
 *      2016/03/19      construct selection list of possible matches    *
 *                      in a popup menu                                 *
 *      2016/03/22      update IDIR                                     *
 *                      include page number and link to image in header *
 *                      highlight links to tree                         *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/10/23      add columns                                     *
 *      2017/01/13      add "Clear" button to remove linkage            *
 *                      add templates for "Find", "Tree", and "Clear"   *
 *                      buttons.                                        *
 *      2017/01/18      correct undefined $image                        *
 *                      replace setField with set                       *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/02/07      use class Country                               *
 *      2017/07/18      use Canada West instead of Ontario              *
 *      2017/09/12      use get( and set(                               *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2020/04/16      recovery for missing records is in class        *
 *                      CountyMarriageSet                               *
 *      2021/04/21      correct error if no matches                     *
 *      2021/05/19      inconsistent spelling of $reportNotext          *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
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
$domain                     = 'CACW';       // default domain
$domaintext                 = null;
$prov                       = 'CW';         // default province code
$provtext                   = null;
$province                   = 'Canada West';// name of province
$cc                         = 'CA';         // default country code
$countryName                = 'Canada';     // name of country
$image                      = '';
$lang                       = 'en';
$volume                     = null;
$volumetext                 = null;
$reportNo                   = null;
$reportNotext               = null;
$itemNo                     = null;
$itemNotext                 = null;
$role                       = null;
$roletext                   = null;
$offset                     = null;
$limit                      = null;
$fixup                      = true;

if (count($_POST) > 0)
{                       // perform update
    $parmsText  = '<p class="label">\$_POST</p>\n' .
                  '<table class="summary">\n' .
                  '<tr><th class="colhead">key</th>' .
                      '<th class="colhead">value</th></tr>\n';
    $reports                = array();
    $record                 = null;
    $domain                 = null;
    $create                 = false;
    $fixup                  = false;

    foreach($_POST as $key => $value)
    {                   // loop through all update parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$safevalue</td></tr>\n"; 
        if (preg_match("/^([a-zA-Z_]+)(\d*)$/", $key, $matches))
        {
            $column         = $matches[1];
            $row            = $matches[2];
        }
        else
        {
            $column         = $key;
            $row            = '';
        }

        switch(strtolower($column))
        {               // act on specific parameters
            case 'domain':
            {           // Domain
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                    $domain         = strtoupper($value);
                else
                    $domaintext     = $safevalue;
                break;
            }           // Domain

            case 'volume':
            {           // volume
                if (is_int($value) || ctype_digit($value))
                    $volume     = $value;
                else
                    $volumetext = $safevalue;
                break;
            }           // volume

            case 'reportno':
            {           // report no
                if (is_int($value) || ctype_digit($value))
                {       // valid
                    $reportNo               = $value;
                    $reportNotext           = $value;
                    $getParms['reportno']   = $reportNo;
                }       // valid
                else
                if (preg_match('/^(\d+)(½|.5)$/', $value, $matches))
                {
                    $reportNo               = $matches[1] + 0.5;
                    $reportNotext           = $matches[1] . '½';
                    $getParms['reportno']   = $reportNo;
                }
                else
                {
                    $warn   .= "<p>CountyMarriagesEdit.php: " . __LINE__ .
                                " invalid reportno='$safevalue' ignored</p>\n";
                }
                break;
            }           // report no

            case 'itemno':
            {           // item no
                if (is_int($value) ||
                    ctype_digit($value))
                {       // valid
                    $itemNo                 = $value;
                    $getParms['itemno']     = $itemNo;
                }       // valid
                else
                    $itemNotext             = $safevalue;
                break;
            }           // itemno

            case 'role':
            {           // role
                if ($record &&
                    $record->get('givennames') != 'New Bride' &&
                    $record->get('givennames') != 'New Groom')
                {
                    $record->dump('Save');
                    $record->save();
                    $reports[]  = $record;
                }
                $role           = $safevalue;
                if ($role == 'B' || $role == 'G')
                {       // a role field in a row of the table
                    $getParms   = array('domain'    => $domain,
                                        'volume'    => $volume,
                                        'reportno'  => $reportNo,
                                        'itemno'    => $itemNo,
                                        'role'      => $role);
                    $record     = new CountyMarriage($getParms);
                    $msg        .= $record->getErrors();
                }       // a role field in a row of the table
                break;
            }           // role

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
            {           // field to update
                $record->set($column, $value);
                break;
            }           // field to update

            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang
        }           // act on specific parameters
    }               // loop through all update parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    // ensure reports still sorted by keys before displaying them
    usort($reports,'Genealogy\compareReports');
}                       // perform update
else
{                       // initial query
    $parmsText      = "<p class=\"label\">\$_GET</p>\n" .
                        "<table class=\"summary\">\n" .
                        "<tr><th class=\"colhead\">key</th>" .
                        "<th class=\"colhead\">value</th></tr>\n";
    $getParms       = array();
    $fixup          = true;
    foreach($_GET as $key => $value)
    {                   // loop through all parameters
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {               // act on specific parameters
            case 'prov':
            {
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
                {
                    $prov           = strtoupper($value);
                    $domain         = 'CA' . $prov;
                }
                else
                    $provtext       = $safevalue;
                break;
            }           // state/province code

            case 'domain':
            case 'regdomain':
            {
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                    $domain         = strtoupper($value);
                else
                    $domaintext     = $safevalue;
                break;
            }           // domain code

            case 'volume':
            {
                if (is_int($value) || ctype_digit($value))
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
                if (is_int($value) || ctype_digit($value))
                {       // valid
                    $reportNo       = $value;
                    $reportNotext   = $value;
                    $getParms['reportno']   = $reportNo;
                }       // valid
                else
                if (preg_match('/^(\d+)(½|.5)$/', $value, $matches))
                {
                    $reportNo       = $matches[1] + 0.5;
                    $reportNotext   = $matches[1] . '½';
                    $getParms['reportno']   = $reportNo;
                }
                else
                if ($safevalue != '')
                    $warn   .= "<p>CountyMarriagesEdit.php: " . __LINE__ .
                               " invalid reportno='$safevalue' ignored</p>\n";
                break;
            }

            case 'itemno':
            {
                if (is_int($value) || ctype_digit($value))
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
                    $fixup                  = false;
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
                {       // valid
                    $offset         = $value;
                    $getParms['offset'] = $offset;
                    if ($offset > 0)
                    {
                        $fixup          = false;
                        if ($debug)
                            $warn   .= "<p>fixup set to false for '$key'";
                    }
                }       // valid
                break;
            }

            case 'count':
            {
                if (is_int($value) || ctype_digit($value))
                {       // valid
                    $limit          = $value;
                    $getParms['limit']  = $limit;
                    $fixup          = false;
                    if ($debug)
                        $warn   .= "<p>fixup set to false for '$key'";
                }       // valid
                break;
            }

            case 'debug':
            {
                break;
            }           // debug handled by common code

            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang

            default:
            {
                $warn   .= "Unexpected parameter $key='$value'. ";
                break;
            }
        }               // check supported parameters
    }                   // loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
    if ($debug)
        if ($fixup)
            $warn       .= "<p>fixup=true";
        else
            $warn       .= "<p>fixup=false";

    if (strlen($msg) == 0)
    {       // no errors detected
        // execute the query to get the contents of the page
        if ($debug)
            $warn       .= "<p>CountyMarriagesEdit.php: " .__LINE__ .
                            " new CountyMarriageSet(" . 
                            var_export($getParms, true) . ")</p>\n";
        $reports        = new CountyMarriageSet($getParms);
    }       // no errors detected

    if (count($reports) == 0 &&
        !is_null($domain) && !is_null($volume) && !is_null($reportNo))
    {
        $create         = true;
        if (is_null($itemNo))
        {       // initialize new report with 10 empty entries
            $item       = 1;
            $lastItem   = 10;
        }       // initialize new report
        else
        {       // create one empty entry
            $item       = $itemNo;
            $lastItem   = $itemNo;
        }       // create one empty entry

        $getParms   = array('domain'    => $domain,
                            'volume'    => $volume,
                            'reportNo'  => $reportNo);
        for(;$item <= $lastItem; $item++)
        {       // loop creating new empty records
            $getParms['itemNo'] = $item;
            $getParms['role']   = 'G';
            $groom              = new CountyMarriage($getParms);
            $msg                .= $groom->getErrors();
            $groom->set('givennames', 'New Groom');
            $reports[]          = $groom;
            $getParms['role']   = 'B';
            $bride              = new CountyMarriage($getParms);
            $msg                .= $bride->getErrors();
            $bride->set('givennames', 'New Bride');
            $reports[]          = $bride;
        }       // loop creating new empty records
    }
    else
        $create = false;
}           // initial report

if (!is_null($volume) && !is_null($reportNo))
    $templateName   = "CountyMarriagesEditReport$lang.html";
else
    $templateName   = "CountyMarriagesEdit$lang.html";
$template           = new FtTemplate($templateName);
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

// validate Domain
if (!is_string($domaintext))
{
    if ($domain == 'CAON')
        $domain     = 'CACW';
    else
    if ($domain == 'CAQC')
        $domain     = 'CACE';
    $domainObj      = new Domain(array('domain'     => $domain,
                                       'language'   => 'en'));
    if ($domainObj->isExisting())
    {
        $getParms['domain'] = $domain;
        $cc         = $domainObj['countrycode'];
        $prov       = $domainObj['state'];
        $province   = $domainObj['name'];
    }
    else
    {
        $msg        .= "Domain='$domain' unsupported. ";
        $province   = 'Domain : ' . $domain;
    }
    $countryObj     = $domainObj->getCountry();
    $countryName    = $countryObj->getName();
}
else
{
    $province       = 'Domain : ' . $domaintext;
    $msg            .= "Invalid Domain Identifier '$domaintext'. ";
}

// set common substitutions
$template->set('CC',                    $cc);
$template->set('COUNTRYNAME',           $countryName);
$template->set('PROV',                  $prov);
$template->set('PROVINCE',              $province);
$template->set('DOMAIN',                $domain);
$template->set('DOMAINNAME',            $province);

// check for marriage report information
$report                 = null;
$ministerName           = '';
$faith                  = '';
$residence              = '';
$page                   = '';
$image                  = '';
if ($domain && $volume && $reportNo)
{                   // records belong to specific report
    $getParms           = array('domain'    => $domain,
                                'volume'    => $volume,
                                'reportno'  => $reportNo);
    $report             = new CountyMarriageReport($getParms);
    if ($report->isExisting())
    {               // record describing report
        $ministerName   = $report  ->get('givennames') . ' ' .
                                $report  ->get('surname');
        $faith          = $report  ->get('faith');
        $residence      = $report  ->get('residence');
        $page           = $report  ->get('page');
        $image          = $report  ->get('image');
    }               // record describing report
    else
        $report         = null;
}                   // records belong to specific report

// pass report information to template
$template->set('MINISTERNAME',      $ministerName);
$template->set('FAITH',             $faith);
$template->set('RESIDENCE',         $residence);
$template->set('PAGE',              $page);
$template->set('IMAGE',             $image);
$template->set('VOLUME',            $volume);
$template->set('REPORTNO',          $reportNo);
$template->set('REPORTNOTEXT',      $reportNotext);
if (is_null($itemNo))
{
    $template['itemTitle']->update(null);
    $template->set('ITEMNO',            'null');
}
else
    $template->set('ITEMNO',            $itemNo);

if (strlen($msg) == 0)
{
    if (is_null($volume))
    {
        $template['volumeLink']->update(null);
        $template['volumeTitle']->update(null);
    }

    if (is_null($reportNo))
    {
        $template['reportLink']->update(null);
        $template['reportTitle']->update(null);
    }

    if (is_null($report))
        $template['ministerLink']->update(null);

    if (strlen($image) == 0)
        $template['imageButton']->update(null);
    
    
    // notify the invoker if they are not authorized to update the form
    if (canUser('edit'))
    {       // authorized to update database
        $readonly   = '';
        $disabled   = '';
        $template['notauth']->update(null);
        if ($create)
            $template->set('ACTION',            $t["Create"]);
        else
            $template->set('ACTION',            $t["Update"]);
    }       // authorized to update database
    else
    {
        $readonly   = 'readonly="readonly"';
        $disabled   = 'disabled="disabled"';
        $template->set('ACTION',            $t["Display"]);
    }       // not authorized to update database
    $template->set('READONLY',              $readonly);
    $template->set('DISABLED',              $disabled);

    if ($domain && $volume && $reportNo)
    {       // show pointers for previous and next entry
        if (is_null($itemNo) || $itemNo == 1)
        {
            if ($reportNo == floor($reportNo))
                $prevText   = $reportNo - 1;
            else
                $prevText   = floor($reportNo);
            $prevUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                            $prevText;
        }
        else
        {
            $prevUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                            $reportNotext . "&ItemNo=" . ($itemNo - 1);
            $prevText   = $reportNotext . '-' . ($itemNo - 1);
        }
        if (is_null($itemNo))
        {
            if ($reportNo == floor($reportNo))
                $nextText   = $reportNo + 1;
            else
                $nextText   = ceil($reportNo);
            $nextUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                            $nextText;
        }
        else
        {
            $nextUri    = "Domain=$domain&Volume=$volume&ReportNo=" .
                            $reportNotext . "&ItemNo=" . ($itemNo + 1);
            $nextText   = $reportNotext . '-' . ($itemNo + 1);
        }

        $template->set('PREVTEXT',      $prevText);
        $template->set('PREVURI',       $prevUri);
        $template->set('NEXTTEXT',      $nextText);
        $template->set('NEXTURI',       $nextUri);
    }
    else
        $template['topBrowse']->update(null);

    // show the response
    $rowelt             = $template['Row$row'];
    $data               = '';
    if (count($reports) > 0)
    {
        $rowtext        = $rowelt->outerHTML;
        $reportNo       = 0;
        $page           = 1;
        $row            = 0;
        $nextRole       = 'G';
        $tempRecord     = null;
        $date           = '';
        $licenseType    = 'L';
        $last           = end($reports);
        $first          = reset($reports);
        foreach($reports as $record)
        {
            $itemNo                 = $record->get('itemno'); 
            $role                   = $record->get('role');
            $givennames             = $record->get('givennames');
            $surname                = $record->get('surname');
            $row++;
            if ($row < 10)
                $row        = '0' . $row;
            $domain                 = $record->get('domain'); 
            $volume                 = $record->get('volume'); 
            $reportNo               = $record->get('reportno'); 
            if ($reportNo == floor($reportNo))
                $reportNotext       = intval($reportNo);
            else
                $reportNotext       = floor($reportNo) . '½';
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
                $sexclass               = 'male';
            }       // groom record
            else
            {
                $sexclass               = 'female';
            }
            $rtemplate                  = new Template($rowtext);
            $rtemplate->set('rtemplate',        $rtemplate);
            $rtemplate->set('itemNo',           $itemNo);
            $rtemplate->set('role',             $role);
            $rtemplate->set('givennames',       $givennames);
            $rtemplate->set('surname',          $surname);
            $rtemplate->set('row',              $row);
            $rtemplate->set('domain',           $domain);
            $rtemplate->set('volume',           $volume);
            $rtemplate->set('reportNo',         $reportNo);
            $rtemplate->set('reportNoText',     $reportNotext);
            $rtemplate->set('givennames',       $givennames);
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
            $rtemplate->set('readonly',         $readonly);
            $rtemplate->set('disabled',         $disabled);
            if ($idir == 0)
            {
                $rtemplate['Link$row']->update(null);
                $rtemplate['Clear$row']->update(null);
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
