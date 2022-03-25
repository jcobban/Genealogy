<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
use \Templating\Template;

/************************************************************************
 *  BirthRegYearStats.php                                               *
 *                                                                      *
 *  Display statistics about the transcription of birth registrations.  *
 *                                                                      *
 *  Parameters:                                                         *
 *      regdomain       domain                                          *
 *      regyear         registration year                               *
 *      county          county code within domain (optional)            *
 *                                                                      *
 *  History:                                                            *
 *      2011/01/09      created                                         *
 *      2011/11/05      use <button> instead of <a> for view action     *
 *                      support mouseover help                          *
 *                      change name of help page                        *
 *      2012/06/23      add support for linking statistics              *
 *      2013/04/13      use functions pageTop and pageBot to standardize*
 *      2013/11/16      handle lack of database server connection       *
 *                      gracefully                                      *
 *                      clean up parameter handling                     *
 *                      support RegDomain parameter                     *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2013/12/24      use CSS for layout instead of tables            *
 *      2014/01/14      move function pctClass to common.inc            *
 *                      improve parameter handling                      *
 *                      use County class to expand county name          *
 *      2014/12/29      move to folder Canada                           *
 *                      support all provinces                           *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/11/02      add information on lowest and highest regnum    *
 *                      and percentage transcribed to display           *
 *      2016/01/19      add id to debug trace                           *
 *                      include http.js before util.js                  *
 *                      common trace was discarded                      *
 *      2016/04/25      replace ereg with preg_match                    *
 *                      support reporting single county                 *
 *                      support county level summary                    *
 *      2016/05/06      %done in summary column was wrong               *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/09/01      do not include delayed registrations in stats   *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/01      use Birth::getYearStatistics                    *
 *      2017/10/16      use BirthSet                                    *
 *      2017/10/30      use composite cell style classes                *
 *      2018/10/06      use class Template                              *
 *      2019/05/29      do not number_format registration numbers       *
 *      2019/06/17      ignore late registrations in calculating        *
 *                      highest registration number                     *
 *      2020/01/22      use NumberFormatter                             *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/11/28      correct XSS error                               *
 *      2021/01/15      improve parameter checking                      *
 *                      move message texts to template                  *
 *      2021/11/22      ignore anomalous regnums in statistics          *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/Birth.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/BirthSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$regYear                    = null;
$regyeartext                = null;
$domain                     = 'CAON';   // default domain
$domaintext                 = null;
$codetext                   = null;
$domainName                 = 'Canada: Ontario';
$stateName                  = 'Ontario';
$cc                         = 'CA';
$countryName                = 'Canada';
$county                     = null;
$countytext                 = null;
$countyName                 = null;
$lang                       = 'en';
$langtext                   = null;
$showTownship               = false;
$getParms                   = array();

if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {               // loop through all input parameters
        $safevalue          =  htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                "$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {           // process specific named parameters
            case 'regyear':
            {
                if (ctype_digit($value) &&
                    ($value >= 1860) && ($value <= 2030))
                {
                    $regYear            = $value;
                    $getParms['year']   = $regYear;
                }
                else
                if (strlen($value) > 0)
                    $regyeartext        = $safevalue;
                break;
            }       // RegYear passed
    
            case 'regdomain':
            case 'domain':
            {
                if (preg_match('/^[a-zA-Z]{4}$/', $value))
                {
                    $domain             = strtoupper($value);
                    $cc                 = substr($domain, 0, 2);
                }
                else
                if (strlen($value) > 0)
                    $domaintext         = $safevalue;
                break;
            }       // RegDomain
    
            case 'code':
            {
                if (preg_match('/^\w\w$/', $value))
                {
                    $domain             = 'CA' . strtoupper($value);
                    $cc                 = 'CA';
                }
                else
                if (strlen($value) > 0)
                    $domaintext         = 'CA' . $safevalue;
                break;
            }       // code
    
            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value,
                                                       $langtext);
                break;
            }       // lang
    
            case 'county':
            {
                if (preg_match('/^\w+$/', $value))
                    $county             = $value;
                else
                if (strlen($value) > 0)
                    $countytext         = $safevalue;
                break;
            }       // county
    
            case 'debug':
            case 'userid':
            {
                break;
            }       // allow debug output
    
            default:
            {
                $warn       .= "Unexpected parameter $key='" .
                                    $safevalue . "'. ";
                break;
            }       // any other paramters
        }           // process specific named parameters
    }               // loop through all input parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by method=get

$template       = new FtTemplate("BirthRegYearStats$lang.html");

if (is_string($regyeartext))
{                   // syntax error
    $text               = $template['invalidRegYear']->innerHTML;
    $msg                .= str_replace('$regyear', $regyeartext, $text);
    $template->set('REGYEAR',               $regyeartext);
}                   // syntax error
else
if (is_null($regYear))
{                   // missing mandatory parameter
    $msg                .= $template['missingRegYear']->innerHTML;
    $template->set('REGYEAR',               '');
}                   // missing mandatory parameter
else
{                   // valid
    $template->set('REGYEAR',               $regYear);
    $template->set('REGYEARP',              $regYear - 1);
    $template->set('REGYEARN',              $regYear + 1);
}                   // valid

// interpret domain code
if (is_string($domaintext))
{                   // syntax error
    $text               = $template['invalidDomain']->innerHTML;
    $msg                .= str_replace('$domain', $domaintext, $text);
}                   // syntax error
else
{                   // validate domain code
    $domainObj          = new Domain(array('domain'     => $domain,
                                           'language'   => 'en'));
    $domainName         = $domainObj->getName(1);
    $stateName          = $domainObj->getName(0);
    $countryObj         = $domainObj->getCountry();
    $countryName        = $countryObj->getName();
    if ($domainObj->isExisting())
        $getParms['domain'] = $domain;
    else
    {
        $text           = $template['unsupportedDomain']->innerHTML;
        $msg            .= str_replace('$domain', $domain, $text);
    }
}                   // validate domain code

// validate county code
if (is_string($countytext))
{                   // syntax error
    $text               = $template['invalidCounty']->innerHTML;
    $msg                .= str_replace('$county', $countytext, $text);
    $template->set('COUNTYNAME',        $countytext);
}                   // syntax error
else
if (is_string($county))
{                   // validate county code
    $getParms['county'] = $county;
    $countyObj          = new County($domain, $county);
    if ($countyObj->isExisting())
    {
        $countyName     = $countyObj->get('name');
        $showTownship   = true;
    }
    else
    {
        $text           = $template['unsupportedCounty']->innerHTML;
        $msg            .= str_replace(array('$county','$domain'), 
                                       array($county, $domain), 
                                       $text);
    }

    $template->set('COUNTY',            $county);
    $template->set('COUNTYNAME',        $countyName);
}                   // validate county code
else
{                   // county code omitted
    $template->set('COUNTY',            '');
    $template->set('COUNTYNAME',        'All');
    $template->updateTag('countyName',  null);
    $template->updateTag('countyStatusLink', null);
}                   // county code omitted

$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('DOMAINNAME',        $domainName);
$template->set('DOMAIN',            $domain);
$template->set('STATENAME',         $stateName);
$template->set('LANG',              $lang);
$template->set('CONTACTTABLE',      'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DEBUG',             $debug?'Y':'N');

if (strlen($msg) == 0)
{                       // no errors
    // get the statistics
    $births                 = new BirthSet($getParms);

    if ($county)
        $result             = $births->getCountyStatistics();
    else
        $result             = $births->getStatistics();

    if (!$showTownship)
        $template->updateTag('TownshipTH', null);
    
    $dataRow                = $template->getElementById('dataRow');
    $yearHTML               = $dataRow->outerHTML();
    
    $total                  = 0;
    $totalLinked            = 0;
    $rownum                 = 0;
    $countyObj              = null;
    $countyName             = '';
    $lowest                 = PHP_INT_MAX;
    $highest                = 0;
    $data                   = '';
    $formatter              = $template->getFormatter();
    
    foreach($result as $row)
    {                   // loop through results
        $ttemplate          = new Template($yearHTML);
        $rownum++;
        $county             = $row['county'];
        if (is_null($countyObj) ||
            $county != $countyObj->get('code'))
        {               // new county code
            $countyObj      = new County($domain, $county);
            $countyName     = $countyObj->get('name');
        }               // new county code
        if (array_key_exists('township', $row))
             $township      = $row['township'];
        else
             $township      = '&nbsp;';
        $count              = $row['count'];
        $total              += $count;
        $linked             = $row['linkcount'];
        if ($count == 0)
            $pctLinked      = 0;
        else
            $pctLinked      = 100 * $linked / $count;
        $totalLinked        += $linked;
        $low                = $row['low'];
        $high               = $row['high'];
        if (array_key_exists('currhigh', $row))
            $currhigh       = $row['currhigh'];
        else
            $currhigh       = $high;
    
        if ($low < $lowest)
            $lowest         = $low;
        if ($high < ($low + 5000) && $high > $highest)
            $highest        = $high;
        $todo               = $currhigh - $low + 1;
        if ($todo < $count)
            $todo           = $count;
        if ($todo == 0)
            $pctDone        = 0;
        else
            $pctDone        = 100 * $count / $todo;
    
        $ttemplate->set('ROWNUM',       $rownum);
        $ttemplate->set('COUNTY',       $county);
        $ttemplate->set('COUNTYNAME',   $countyName);
        $ttemplate->set('TOWNSHIP',     $township);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        $ttemplate->set('COUNT',        $formatter->format($count));
        $ttemplate->set('LOW',          $low);
        $ttemplate->set('HIGH',         $high);
        $ttemplate->set('LINKED',       $linked);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $ttemplate->set('PCTDONE',      $formatter->format($pctDone));
        $ttemplate->set('PCTDONECLASS', pctClass($pctDone));
        $ttemplate->set('PCTLINKED',    $formatter->format($pctLinked));
        $ttemplate->set('PCTLINKEDCLASS', pctClass($pctLinked));
        if (!$showTownship)
            $ttemplate->updateTag('townshipCol', null);
        $data               .= $ttemplate->compile();
    }                   // process all rows
    $dataRow->update($data);
        
    if ($total == 0)
    {                   // avoid divide by zero
        $pctDone            = 0;
        $pctLinked          = 0;
    }                   // avoid divide by zero
    else
    {                   // percent output
        $pctDone            = 100 * $total / ($highest - $lowest + 1);
        $pctLinked          = 100 * $totalLinked / $total;
    }                   // percent output
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    $template->set('TOTAL',             $formatter->format($total));
    if ($lowest == PHP_INT_MAX)
        $lowest             = 0;
    $template->set('LOWEST',            $lowest);
    $template->set('HIGHEST',           $highest);
    $template->set('TOTALLINKED',       $formatter->format($totalLinked));
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
    $template->set('PCTDONE',           $formatter->format($pctDone));
    $template->set('PCTDONECLASS',      pctClass($pctDone));
    $template->set('PCTLINKED',         $formatter->format($pctLinked));
    $template->set('PCTLINKEDCLASS',    pctClass($pctLinked));
    if (!$showTownship)
        $template->updateTag('CountyCol', null);
}                       // no errors
else
{                       // suppress display
    $template['topBrowse']->update(null);
    $template['display']->update(null);
}                       // suppress display

$template->display();
