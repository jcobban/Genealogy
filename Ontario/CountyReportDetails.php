<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageReportEdit.php                                        *
 *                                                                      *
 *  Display form for editting information about an individual marriage  *
 *  report submitted by a minister of religion to report marriages      *
 *  performed during a year prior to confederation.                     *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Domain      2 letter country code + 2 letter province code      *
 *      Volume      volume number                                       *
 *      ReportNo    report number                                       *
 *                                                                      *
 *  History:                                                            *
 *      2017/03/11      created                                         *
 *      2017/07/18      use Canada West instead of Ontario              *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/03/25      script was only expecting Domain param          *
 *                      but links passed RegDomain                      *
 *                      do not display .0                               *
 *      2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2021/09/03      use FtTemplate                                  *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/common.inc";

// defaults
$domain             = 'CACW';
$prov               = 'CW';
$province           = 'Canada West';
$cc                 = 'CA';
$countryName        = 'Canada';
$by                 = 'County';
$lang               = 'en';
$volume             = null;
$reportNo           = null;
$report             = null;     // instance of CountyMarriageReport

// process update
if (count($_POST) > 0)
{           // update
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $field => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($field))
        {       // act on specific fields
            case 'domain':
            case 'regdomain':
            {   // Domain
                $domain         = $value;
                break;
            }   // Domain

            case 'volume':
            {   // volume number in archives
                $volume         = $value;
                break;
            }   // volume number in archives

            case 'showvolume':
            {   // include volume number in report
                $newvolume      = $value;
                break;
            }   // include volume number in report

            case 'reportno':
            {   // report number within volume
                $reportNo       = $value;
                if ($reportNo == floor($reportNo))
                    $reportNo   = intval($reportNo);
                $getParms       = array('domain'    => $domain,
                                        'volume'    => $volume,
                                        'reportno'  => $reportNo);
                $report         = new CountyMarriageReport($getParms);
                //$report->set('volume',$newvolume);
                break;
            }   // report number within volume

            case 'page':
            {   // page number within report
                $report->set('page',$value);
                break;
            }   // page number within report

            case 'year':
            {   // year number within report
                $report->set('year',$value);
                break;
            }   // year number within report

            case 'givennames':
            {   // given name of officiant
                $report->set('givennames',$value);
                break;
            }   // given name of officiant

            case 'surname':
            {   // surname of officiant
                $report->set('surname',$value);
                break;
            }   // surname of officiant

            case 'faith':
            {   // affiliation of officiant
                $report->set('faith',$value);
                break;
            }   // affiliation of officiant

            case 'residence':
            {   // residence of officiant
                $report->set('residence',$value);
                break;
            }   // residence of officiant

            case 'image':
            {   // URL of image of original document
                $report->set('image',$value);
                break;
            }   // URL of image of original document

            case 'idir':
            {   // link to family tree of officiant
                $report->set('idir',$value);
                break;
            }   // link to family tree of officiant

            case 'remarks':
            {
                $report->set('remarks',$value);
                break;
            }

            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang

        }       // act on specific fields
    }
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

    $report->save();
}           // update
else
{           // validate parameters
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {       // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {       // act on specific parameters
            case 'domain':
            case 'regdomain':
            {   // Domain
                $domain         = $value;
                break;
            }   // Domain
    
            case 'volume':
            {   // volume
                $volume         = $value;
                break;
            }
    
            case 'reportno':
            {
                $reportNo       = $value;
                if ($reportNo == floor($reportNo))
                    $reportNo   = intval($reportNo);
                break;
            }

            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang
    
        }       // act on specific parameters
    }       // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    // get data for report
    $getParms       = array('domain'    => $domain,
                            'volume'    => $volume,
                            'reportno'  => $reportNo);
    $report         = new CountyMarriageReport($getParms);
}           // validate parameters

$template           = new FtTemplate("CountyReportDetails$lang.html");
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

// interpret domain
$cc                 = substr($domain, 0, 2);
$prov               = substr($domain, 2, 2);
$domainObj          = new Domain(array('domain'     => $domain,
                                       'language'   => 'en'));
if ($domainObj->isExisting())
{
    $province       = $domainObj->get('name');
    if ($prov == 'UC')
        $by         = 'District';
}
else
{
    $msg            .= "Domain='$value' unsupported. ";
    $province       = 'Unknown';
}
$countryObj         = new Country(array('code' => $cc));
$countryName        = $countryObj->getName();

$template->set('DOMAIN',			$domain);
$template->set('PROV',			    $prov);
$template->set('PROVINCE',			$province);
$template->set('DOMAINNAME',		$province);
$template->set('CC',			    $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('BY',			    $by);
$template->set('LANG',			    $lang);
$template->set('VOLUME',			$volume);
$template->set('DEBUG',			    $debug?'Y':'N');

if (!canUser('edit'))
{
    $template->set('READONLY',       "readonly='readonly'");
    $template->set('DISABLED',       "disabled='disabled'");
    $template->set('BG',             'ina');
    $template->set('ACTION',         $t['Display']);
    $template['Submit']->update(null);  // hide submit button
}       // not authorized to update database
else
{       // authorized to update database
    $template->set('READONLY',       '');
    $template->set('DISABLED',       '');
    $template->set('BG',             'white');
    $template->set('ACTION',         $t['Update']);
}       // authorized to update database

if ($reportNo < 2)
    $template['topPrev']->update(null);

// get contents of record
$domain             = $report->get('domain');
$volume             = $report->get('volume');
$reportNo           = $report->get('reportno');
if ($reportNo == floor($reportNo))
    $reportNo       = intval($reportNo);
$page               = $report->get('page'); 
$year               = $report->get('year');
$givennames         = $report->get('givennames');
$surname            = $report->get('surname');
$faith              = $report->get('faith');
$residence          = $report->get('residence');
$image              = $report->get('image');
$idir               = $report->get('idir');
$remarks            = $report->get('remarks');
$template->set('REPORTNO',			$reportNo);
$template->set('REPORTNOminus1',	$reportNo - 1);
$template->set('REPORTNOplus1',		$reportNo + 1);
$template->set('PAGE',			    $page);
$template->set('YEAR',			    $year);
$template->set('GIVENNAMES',		$givennames);
$template->set('SURNAME',			$surname);
$template->set('FAITH',			    $faith);
$template->set('RESIDENCE',			$residence);
$template->set('IMAGE',			    $image);
$template->set('IDIR',			    $idir);
$template->set('REMARKS',			$remarks);

if (strlen($msg) > 0)
    $template['reportForm']->update(null);

$template->display();
