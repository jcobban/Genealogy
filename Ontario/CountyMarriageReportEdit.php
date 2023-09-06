<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageReportEdit.php                                        *
 *                                                                      *
 *  Display form for editting information about marriage reports        *
 *  submitted by ministers of religion to report marriages they         *
 *  performed during a year prior to confederation.                     *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Domain  2 letter country code + 2 letter state/province code    *
 *      Volume  volume number                                           *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/29      created                                         *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2017/01/12      add links to Ontario Archives                   *
 *                      add button to display image                     *
 *      2017/02/07      use class Country                               *
 *      2017/07/18      use Canada West for county registrations        *
 *                      update did not refresh stats                    *
 *      2017/09/12      use get( and set(                               *
 *      2018/02/25      use RecordSet                                   *
 *      2018/03/10      use CountyMarriageReportSet                     *
 *      2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2021/09/05      use class FtTemplate                            *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/CountyMarriageReportSet.inc";
require_once __NAMESPACE__ . "/common.inc";

function compareReports($r1, $r2)
{
    if ($r1->get('regdomain') == $r2->get('regdomain') &&
        $r1->get('volume') == $r2->get('volume') &&
        $r1->get('reportno') == $r2->get('reportno'))
    {
        return 0;
    }
    if ($r1->get('regdomain') < $r2->get('regdomain'))
    {
        return -1;
    } else
    if ($r1->get('regdomain') > $r2->get('regdomain'))
    {
        return 1;
    }
    if ($r1->get('volume') < $r2->get('volume'))
    {
        return -1;
    } else
    if ($r1->get('volume') > $r2->get('volume'))
    {
        return 1;
    }
    if ($r1->get('reportno') < $r2->get('reportno'))
    {
        return -1;
    }

    return 1;
}

// defaults
$domainCode         = 'CACW';
$prov               = 'CW';
$province           = 'Canada West (Ontario)';
$cc                 = 'CA';
$countryName        = 'Canada';
$by                 = 'County';
$lang               = 'en';
$volume             = null;
$reportNo           = null;
$report             = null;     // instance of CountyMarriageReport
$offset             = null;
$limit              = null;

// validate parameters
if (count($_POST) > 0)
{           // perform update
    $parmsText  = "<p class=\"label\">\$_POST</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
    $reports    = array();

    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                        "<td class=\"white left\">$value</td></tr>\n"; 
        if (preg_match("/^([a-zA-Z_]+)(\d*)$/", $key, $matches))
        {
            $column                 = $matches[1];
            $row                    = $matches[2];
        }
        else
        {
            $column                 = $key;
            $row                    = '';
        }

        switch(strtolower($column))
        {       // act on specific parameters
            case 'domain':
            {   // Domain
                if (strlen($value) >= 4)
                    $domainCode     = strtoupper($value);
                break;
            }   // Domain

            case 'volume':
            {   // volume
                if ($report &&
                    $report->get('givennames') != 'New Minister')
                {
                    $report->save();
                    $reports[]      = $report;
                }
                $report             = null;
                $volume             = $value;
                break;
            }

            case 'reportno':
            {
                $reportNo           = $value;
                $getParms           = array('domain'    => $domainCode,
                                            'volume'    => $volume,
                                            'reportno'  => $reportNo);
                $report             = new CountyMarriageReport($getParms);
                break;
            }

            case 'page':
            case 'givennames':
            case 'surname':
            case 'faith':
            case 'residence':
            case 'image':
            case 'remarks':
            {
                $report->set($column, $value);
                break;
            }

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
        }       // act on specific parameters
    }
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

    // update the last entry
    if ($report && $report->get('givennames') != 'New Minister')
    {
        $report->save();
        $reports[]                  = $report;
    }

}           // perform update
else
{           // initial report
    $parmsText  = "<p class=\"label\">\$_GET</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                        "<td class=\"white left\">$value</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'prov':
            {
                $domainCode         = 'CA' . strtoupper($value);
                break;
            }       // state/province code

            case 'domain':
            case 'regdomain':
            {
                if (strlen($value) >= 4)
                    $domainCode     = strtoupper($value);
                break;
            }       // state/province code

            case 'volume':
            {
                if (strlen($value) > 0)
                    $volume         = $value;
                break;
            }

            case 'offset':
            {
                $offset             = $value;
                break;
            }

            case 'count':
            {
                $limit              = $value;
                break;
            }

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

            case 'debug':
            {
                break;
            }       // debug handled by common code

            default:
            {
                if (strlen($value) > 0)
                    $warn   .= "<p>Unexpected parameter $key=\"$value\".</p>";
                break;
            }
        }       // check supported parameters
    }       // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}           // initial report

$template           = new FtTemplate("CountyMarriageReportEdit$lang.html");

$domain             = new Domain(array('domain'     => $domainCode,
                                       'language'   => 'en'));
$province           = $domain->get('name');
if ($domain->isExisting())
{
    $cc             = substr($domainCode, 0, 2);
    $prov           = substr($domainCode, 2, 2);
    if ($prov == 'UC')
        $by         = 'District';
    $country        = new Country(array('code' => $cc));
    $countryName    = $country->getName();
}
else
{                   // domain not supported
    $msg            .= "Domain=\"$domainCode\" unsupported. ";
}                   // domain not supported

$template->set('COUNTRYNAME',           $countryName);
$template->set('CC',                    $cc);
$template->set('PROVINCE',              $province);
$template->set('DOMAIN',                $domainCode);
$template->set('DOMAINNAME',            $province);

if (strlen($msg) == 0)
{       // no errors detected
    // execute the query to get the contents of the page
    $getParms               = array('regdomain' => $domainCode);
    if ($volume)
        $getParms['volume'] = $volume;
    if ($offset)
        $getParms['offset'] = $offset;
    if ($limit)
        $getParms['limit']  = $limit;
    $reports                = new CountyMarriageReportSet($getParms);

    if (canUser('edit'))
    {       // authorized to update database
        $readonly           		= '';
        $disabled           		= '';
        $bg                 		= 'white';
        $template->set('ACTION',        'Update');
    }       // authorized to update database
    else
    {
        $readonly           		= 'readonly="readonly"';
        $disabled           		= 'disabled="disabled"';
        $bg                 		= 'ina';
        $template->set('ACTION',        'Display');
    }       // not authorized to update database
    $template->set('READONLY',          $readonly);
    $template->set('DISABLED',          $disabled);
    $template->set('BG',                $bg);

    if (isset($volume) && $reportNo === null)
    {       // display of one volume
        $template->set('VOLUME',        $volume);
        $template->set('VOLUMEplus1',   $volume + 1);
        if ($volume > 1)
            $template->set('VOLUMEminus1',  $volume - 1);
        else
            $template['topPrev']->update('&nbsp;');
    }       // display of whole volume
    else
        $template['topBrowse']->update(null);

    $template->set('DEBUG',         $debug ? 'Y' : 'N');

    // display the results
    $reportNo                       = 0;
    $page                           = 1;
    $row                            = 0;
    $image                          = '';
    $nextReportNo                   = 1;
    $rows                           = array();
    foreach($reports as $report)
    {                           // loop through rows of response
        $row++;
        if (strlen($row) == 1)
            $row                    = '0' . $row;
        $volume                     = $report->get('volume');
        $reportNo                   = $report->get('reportno');
        if ($reportNo == floor($reportNo))
        {
            $reportNo               = intval($reportNo);
            $nextReportNo           = $reportNo + 1;
        }
        else
        {
            $nextReportNo           = intval($reportNo) + 1;
            $reportNo               = intval($reportNo) . '&half;';
        }
        $page               		= $report->get('page'); 
        $image              		= trim($report->get('image'));
        $transcribed        		= $report->get('transcribed'); 
        $linked             		= $report->get('linked'); 
        if ($transcribed > 0)
        {
            $pct            		= number_format(100 * $linked / $transcribed, 2) . '%';
            $pctClass       		= pctClass($pct);
        }
        else
        {
            $pct                    = '';
            $pctClass               = pctClass(0);
            $transcribed            = 0;
        }
        $transcribed                = floor($transcribed / 2);
        if ($image == '')
        {
            $imageStatus            = 'disabled="disabled"';
        }
        else
        {
            $imageStatus            = '';
        }
        $rows[] = array('ROW'               => $row,
                        'VOLUME'            => $volume,
                        'REPORTNO'          => $reportNo,
                        'NEXTREPORTNO'      => $nextReportNo,
                        'PAGE'              => $page,
                        'GIVENNAMES'        => $report['givennames'],
                        'SURNAME'           => $report['surname'],
                        'IMAGE'             => $image,
                        'TRANSCRIBED'       => $transcribed,
                        'LINKED'            => $linked,
                        'PCT'               => $pct,
                        'PCTCLASS'          => $pctClass,
                        'IMAGESTATUS'       => $imageStatus);
    }                           // loop through rows of response
    $template['Row$ROW']->update($rows);

    $row++;
    if ($row < 10)
        $row                        = '0' . $row;
    $template->set('ROW',              $row);
    $template->set('VOLUME',           $volume);
    $template->set('NEXTREPORTNO',     $nextReportNo);
}       // no errors detected
else
{
    $template['reportsForm']->update(null);
}

$template->display();
