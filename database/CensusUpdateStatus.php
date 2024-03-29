<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  CensusUpdateStatus.php                                              *
 *                                                                      *
 *  Display the progress of the transcription of a specific census      *
 *                                                                      *
 *  Parameters:                                                         *
 *      'census'        Census identifier ccyyyy                        *
 *      'province'      province or state code                          *
 *      'lang'          language of communication                       *
 *                                                                      *
 *  History:                                                            *
 *      2010/09/12      Reformat to new page layout.                    *
 *      2010/11/19      Use common MDB2 database connection             *
 *                      increase separation of HTML and PHP             *
 *                      add error checking                              *
 *      2011/01/22      add link to help page                           *
 *      2011/09/27      add support for 1916 census                     *
 *                      simplify census year validation                 *
 *      2011/09/25      correct URL for update/query form in header     *
 *                      and trailer                                     *
 *      2011/10/16      provide links to preceding and following        *
 *                      census years                                    *
 *      2011/12/10      use HTML <button>                               *
 *      2012/09/17      Census parameter contains census identifier     *
 *      2013/01/26      table SubDistTable renamed to SubDistricts      *
 *      2013/04/14      use pageTop and PageBot to standardize page     *
 *                      layout                                          *
 *      2013/05/23      add button to display surnames                  *
 *      2013/06/11      correct URL for requesting next page to edit    *
 *      2013/07/15      correct URL for requesting next page to edit    *
 *      2013/11/26      handle database server failure gracefully       *
 *      2013/11/29      let common.inc set initial value of $debug      *
 *      2013/12/28      use CSS for layout                              *
 *                      add thead, tbody, tfoot to display table        *
 *      2014/06/22      add help balloons for District and Province     *
 *      2014/06/27      add help for district name, done, and todo      *
 *                      columns, and correctly position help balloons   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/07/08      include CommonForm.js                           *
 *                      add support for 1831 census of Quebec           *
 *                      correct ordering of censuses by province        *
 *                      popup census and province name for links        *
 *      2015/08/21      do not issue error message for missing province *
 *                      and display total stats for country             *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *                      display French name of district                 *
 *      2015/11/18      get stats from Districts table                  *
 *      2015/12/10      initial support for non-Canadian censuses       *
 *      2016/01/19      add id to debug trace                           *
 *                      include http.js before util.js                  *
 *                      display all provinces for collective censuses   *
 *                      use class Census to access census information   *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/12/10      handle province not initialized                 *
 *      2017/02/07      use class Country                               *
 *      2017/09/05      correct alignment of summary row                *
 *                      use Domain object                               *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/17      use class CensusSet                             *
 *      2017/10/30      use composite cell style classes                *
 *      2017/11/24      use Template                                    *
 *                      use prepared statements                         *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/11      htmlspecchars moved to Template class           *
 *      2018/01/29      use class FtTemplate                            *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2019/12/01      improved parameter validation                   *
 *      2020/01/22      internationalize numbers                        *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *      2023/01/18      eliminate XSS vulnerabilities                   *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// general state variables
$cc                     = 'CA';
$country                = null;             // instance of Country
$countryName            = 'Canada';
$censusId               = null;
$censusIdText           = null;
$census                 = null;             // instance of Census
$censusYear             = 0;
$province               = '';               // province code
$provinceText           = null;             // invalid province code
$domain                 = null;             // instance of Domain
$provinceName           = 'National';
$lang                   = 'en';
$langtext               = null;

// Validate parameters
if (isset($_GET) && count($_GET) > 0)
{	        	        // invoked by URL
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue                  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'census':
                if (preg_match('/^[0-9]{4}$/', $value))
                    $censusId       = 'CA' . $value;
                if (preg_match('/^[a-zA-Z]{2,4}[0-9]{4}$/', $value))
                    $censusId       = strtoupper($value);
                else
                    $censusIdText   = $safevalue;
                break;
    
            case 'province':
                $value              = strtoupper($value);
                if (preg_match('/^[A-Z]{2}$/', $value))
                    $province       = $value;
                else
                    $provinceText   = $safevalue;
                break;
    
            case 'lang':
                $lang               = FtTemplate::validateLang($value,
                                                               $langtext);
                break;
    
        }           // switch on parameter name
    }               // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	        // invoked by URL to display current status of account

if (strlen($province) == 0)
    $scope                      = "National";
else
    $scope                      = "Provincial";

$template           = new FtTemplate("CensusUpdateStatus$scope$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'CensusUpdateStatus'));
$formatter                          = $template->getFormatter();

if (is_string($langtext))
    $warn           .= "<p>lang='$langtext' is not supported BCP47 syntax</p>\n";

if (is_string($censusIdText))
{
    $text                   = $template['censusInvalid']->innerHTML;
    $msg                    .= str_replace('$value', $censusIdText, $text);
}
else
if (is_string($censusId))
{
    $census                 = new Census(array('censusid'   => $censusId));
    $cc                     = $census->get('cc');
    $censusYear             = $census->get('year');
    $preConfed              = $censusYear < 1867;
    $partOf                 = $census->get('partof');
    if (strlen($partOf) > 0)
        $province           = $census->get('province');
    if (!$census->isExisting())
    {
        $text               = $template['censusUnsupported']->innerHTML;
        $text               = str_replace('$censusId', $censusId, $text);
        $warn               .= "<p>$text</p>\n";
    }
}
else
{                       // missing Census parameter
    $censusId               = 'Unknown';
    $msg                    .= $template['censusMissing']->innerHTML;
}                       // missing Census parameter

$country                    = new Country(array('code' => $cc));
$countryName                = $country->getName($lang);

// obtain name of province from code
if (is_string($provinceText))
{
    $text                   = $template['provinceInvalid']->innerHTML;
    $msg                    .= str_replace('$value', $provinceText, $text);
}
else
if (is_null($province))
    $provinceName           = 'National';
else
{                       // translate province code to name
    $domainid               = "$cc$province";
    $domain                 = new Domain(array('domain'     => $domainid,
                                               'language'   => $lang));
    $provinceName           = $domain->get('name');
}                       // translate province code to name

// get previous and subsequent census ids
$prevCensus                 = '';
$prevName                   = '';
$prevProv                   = '';
$nextCensus                 = '';
$nextName                   = '';
$nextProv                   = '';

if (strlen($msg) == 0)
{                       // have no errors detected
    $getParms               = array('collective'    => 0);  // all real censuses
    $list                   = new CensusSet($getParms);
    $prevRec                = null;
    $nextRec                = null;
    $stopNext               = false;
    foreach($list as $crec)
    {                   // search for current entry in full list
        if ($stopNext)
        {
            $nextCensus     = $crec->get('censusid');
            break;
        }
        if ($crec->get('censusid') == $censusId)
        {               // found current entry
            if ($prevRec)
            {
                $prevCensus = $prevRec->get('censusid');
                $prevProv   = $prevRec->get('prov');
                $prevName   = $prevRec->get('prov');
            }
            $stopNext       = true;
        }               // found current entry
        $prevRec            = $crec;
    }                   // search for current entry in full list

    $provinces              = $census->get('provinces');
    $codel                  = strlen($province);
    if (is_string($province) && $codel > 0)
    {                   // province specified
        $poff               = strpos($provinces, $province);
        if (is_int($poff))
        {               // valid province
            if ($poff > 0)
            {           // not first province 
                $prevProv   = substr($provinces, $poff - $codel, $codel);
                $prevName   = $prevProv;
                $prevCensus = $censusId;    // same census
            }           // not first province 
            else
            {           // at first province in census
                if ($prevRec)
                {       // last province in previous census
                    $prevProv   = $prevRec->get('provinces');
                    $prevProv   = substr($prevProv,
                                         strlen($prevProv) - $codel);
                    $prevName   = $prevProv;
                }       // last province in previous census
            }           // at first province

            if ($poff < strlen($provinces) - $codel)
            {           // go to next province in census
                $nextProv       = substr($provinces, $poff + $codel, $codel);
                $nextName       = $nextProv;
                $nextCensus     = $censusId;    // same census
            }           // go to next province in census
            else
            {           // go to first province of next census
                if ($nextRec)
                {       // there is a next census
                    $nextProv   = $nextRec->get('provinces');
                    $nextProv   = substr($nextProv, 0, $codel);
                    $nextName   = $nextProv;
                }       // there is a next census
            }           // go to first province of next census
        }               // valid province
    }                   // province specified
    else
    {                   // province not specified
        if ($prevRec)
        {               // last province in previous census
            $prevProv           = $prevRec->get('provinces');
            $prevProv           = substr($prevProv, strlen($prevProv) - $codel);
            $prevName           = $prevProv;
        }               // last province in previous census
        if ($nextRec)
        {               // there is a next census
            $nextProv           = substr($nextRec->get('provinces'), 0, $codel);
            $nextName           = $prevProv;
        }               // there is a next census
    }                   // province not specified

    $total2do                   = 0;

    // some actions depend upon whether the user can edit the database
    if (canUser('edit'))
    {                   // user can update database
        $searchPage             = 'ReqUpdate.php?Census=' . $censusId;
        if (!is_null($province))
            $searchPage         .= "&amp;Province=$province";
        $action                 = 'Update';
    }                   // user can updated database
    else
    {                   // user can only view database
        $searchPage             = 'QueryDetail' . $censusYear . '.html';
        if (!is_null($province))
            $searchPage         .= "?Province=$province";
        $action                 = 'Query';
    }                   // user can only view database
}                       // no errors detected

$template->set('CENSUSYEAR',        $censusYear);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CC',                $cc);
$template->set('CENSUSID',          $censusId);
$template->set('PROVINCE',          $province);
$template->set('PROVINCENAME',      $provinceName);
$template->set('ACTION',            $action);
$template->set('LANG',              $lang);
$template->set('CENSUS',            $censusYear);
$template->set('SEARCH',            $searchPage);
$template->set('CONTACTTABLE',      'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));

if (strlen($msg) == 0)
{                       // OK
    // variables for constructing the SQL SELECT statement

    $and                        = ' AND ';  // logical and operator
    $tbls                       = "";

    // update popup link information
    $template->updateTag('mouseprevCensusLink',
                             array('prevCensus' => $prevCensus,
                                   'prevName'   => $prevName));

    $template->updateTag('mousenextCensusLink',
                             array('nextCensus' => $nextCensus,
                                   'nextName'   => $nextName));
    if (is_null($province) || strlen($province) == 0)
    {                       // national report
        $result             = $census->getStats();  // not used here
        $cc                 = substr($censusId, 0, 2);
        if ($cc == 'UK')
            $cc                 = 'GB';
        $provs              = $census->get('provinces');
        $domainset          = new DomainSet(array('cc'          => $cc,
                                                  'language'    => $lang));
        $provArray          = array();

        // determine the length of the domain codes within the country
        if (count($domainset) > 0)
        {
            $domain         = $domainset->rewind();
            $code           = $domain['domain'];
            $cl             = strlen($code) - 2;
        }
        else
            $cl             = 2;
        $percent            = 0;

        for ($i = 0; $i < strlen($provs); $i += $cl)
        {                   // loop through provinces
            $provcode       = substr($provs, $i, $cl);
            $domain         = $domainset["$cc$provcode"];
            if ($domain)
                $provinceName   = $domain->get('name');
            else
                $warn   .= "<p>No matching Domain for '$cc$provcode'</p>\n";
            if ($preConfed && $cc == 'CA')
                $link       = "/database/CensusUpdateStatus.php?Census=$provcode$censusYear&amp;Province=$provcode&amp;lang=$lang";
            else
                $link       = "/database/CensusUpdateStatus.php?Census=$censusId&amp;Province=$provcode&amp;lang=$lang";
            $percent                = 100 * $result['total'] / $result['pop'];
            $provArray[$provcode]   = array('provcode'      => $provcode,
                                            'ProvinceName'  => $provinceName,
                                            'CensusId'      => $censusId,
                                            'Category'      => $domain['category'],
                                            'link'          => $link);
        }                   // loop through provinces

        $template->updateTag('provInfo', $provArray);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        $total                  = $formatter->format($result['total']);
        $pop                    = $formatter->format($result['pop']);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $percent                = $formatter->format($percent);
        $template->updateTag('natStats',
                             array('total'      => $total,
                                   'pop'        => $pop,
                                   'percent'    => $percent));
    }                       // national report
    else
    {                       // provincial report
        $parms                  = array('census'  => $censusId,
                                        'province'=> $province);
        $result                 = new RecordSet('Districts',
                                                $parms);

        // update forward and backward link arrows
        if (strlen($prevCensus) > 0)
            $template->updateTag('prevCensusLink',
                             array('prevCensus' => $prevCensus,
                                   'prevProv'   => $prevProv,
                                   'prevName'   => $prevName));
        else
            $template->updateTag('prevCensusLink',
                             null);
        if (strlen($nextCensus) > 0)
            $template->updateTag('nextCensusLink',
                             array('nextCensus' => $nextCensus,
                                   'nextProv'   => $nextProv,
                                   'nextName'   => $nextName));
        else
            $template->updateTag('nextCensusLink',
                             null);
        // display the results
        $even               = false;
        $total              = 0;
        $ir                 = 0;

        foreach($result as $row)
        {                   // loop through the records
            // prepare fields for presentation in HTML
            $transcribed            = $row['transcribed'];
            $population             = $row['population'];
            $total                  += $transcribed;
            $total2do               += $population;
            if ($transcribed == 0)
                $pctDone            = 0;
            else
                $pctDone            = 100 * $transcribed / $population;
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
            $row['transcribed']     = $formatter->format($transcribed);
            $row['population']      = $formatter->format($population);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
            $row['pctdone']         = $formatter->format($pctDone);
            $row['pctclass']        = pctClass($pctDone);
            if ($even)
            {
                $row['rowclass']    = 'even';
                $even               = false;
            }
            else
            {
                $row['rowclass']    = 'odd';
                $even               = true;
            }
        }                   // process all rows

        $template->updateTag('row$id', $result);

        // display total population
        if ($total2do <= 0)
            $pctDone                = 100;  // prevent divide by zero
        else
            $pctDone                = 100 * $total / $total2do;
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        $template->set('total',         $formatter->format($total));
        $template->set('total2do',      $formatter->format($total2do));
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $template->set('pctDone',       $formatter->format($pctDone));
        $template->set('pctDoneClass',  pctClass($pctDone));
    }                       // provincial report
}                           // OK
else
{                           // errors
    $template->updateTag('displayForm', null);
}                           // errors
$template->display();
