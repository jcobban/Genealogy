<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusResponse.php                                                  *
 *                                                                      *
 *  Display list of individuals matching a query of a census of Canada. *
 *                                                                      *
 *  Parameters (passed by method='get'):                                *
 *      Census          identifier of census 'XX9999'                   *
 *      Province        optional 2 letter province code                 *
 *      District        district number within 1871 census              *
 *      SubDistrict     subdistrict letter code within district         *
 *      Division        optional division within subdistrict            *
 *      OrderBy         'Name'  order response by surname, given names, *
 *                              and birth year                          *
 *                      'Line'  order response by position within form  *
 *      Family          if present limit response to members of a       *
 *                      family with this identifier                     *
 *      Page            if present limit response to individuals on     *
 *                      the specific page (typically with OrderBy=Line) *
 *      Limit           limit number of rows to display at a time       *
 *      Offset          starting row within result set                  *
 *      BYear           if present limit response by birth year         *
 *      Range           if present the range on either side of birth year*
 *      Surname         if present pattern match for surnames           *
 *      SurnameSoundex  if present match surnames by soundex code       *
 *      GivenNames      if present match given names by pattern         *
 *      Occupation      if present match occupation by pattern          *
 *      BPlace          if present match birth place by pattern         *
 *      Origin          if present match origin by pattern              *
 *      Nationality     if present match nationality by pattern         *
 *      Religion        if present match religion by pattern            *
 *      ...                                                             *
 *                                                                      *
 *  History (of QueryCensus.php, which is superceded by this script):   *
 *      2010/10/07      fix warnings on keys Province and Division      *
 *      2010/11/21      support pre-confederation censuses              *
 *      2010/11/28      correct URL for displaying CensusForm from      *
 *                      QueryDetail                                     *
 *      2010/12/22      use $connection->quote to encode the surname    *
 *                      so that surnames with a quote can be used       *
 *      2011/01/07      fix error in surname search by regexp           *
 *      2011/02/16      fix syntax error in surname soundex search if   *
 *                      quotes                                          *
 *      2011/03/27      use switch for parameter names                  *
 *                      always include OrderBy parameter in $npuri      *
 *                      do not fail if OrderBy parameter missing        *
 *      2011/04/10      search whole database if no district specified  *
 *      2011/05/01      make the "See All Fields" hyperlink look like a *
 *                      button.                                         *
 *      2011/05/08      add Province to $npuri                          *
 *      2011/07/13      1911 Census does need Division in table link    *
 *      2011/09/03      support a comma-separated list of               *
 *                      district:subdistrict pairs in the               *
 *                      SubDistrict parameter                           *
 *      2011/09/04      add code to handle corrupted Districts or       *
 *                      SubDistrictsi tables.  And support global       *
 *                      $SubDist that is an array.                      *
 *      2011/09/18      ignore buttons from IE7                         *
 *      2011/10/09      significant restructuring to facilitate future  *
 *                      maintenance.                                    *
 *                      Improved error handling                         *
 *                      Cookie set here rather than by function call    *
 *                      from census specific script                     *
 *      2011/10/15      provide query specific identification string    *
 *                      for header                                      *
 *      2012/03/31      support for IDIR link to family tree            *
 *      2012/04/01      if full page requested provide button to see    *
 *                      image                                           *
 *      2012/04/07      fix bug in subdistricts with no division value  *
 *                      fix bug that LIMIT set to 1                     *
 *      2012/06/22      include province id in pre-confederation        *
 *                      description                                     *
 *      2012/09/14      always include division in URI                  *
 *      2012/09/25      pass census identifier to other scripts         *
 *      2013/01/26      table SubDistTable renamed to SubDistricts      *
 *      2013/02/27      add Address, Location, CauseOfDeath to fields   *
 *                      searched by regular expression                  *
 *      2013/05/23      add Debug parameter                             *
 *      2013/07/07      use classes SubDistrict and Page                *
 *      2014/06/05      urlencode parameters                            *
 *      2014/08/10      remove setting of cookie from this module       *
 *                                                                      *
 *  History (of QueryResponse1881.php, which is superceded)             *
 *      2010/09/11      update layout                                   *
 *      2010/11/21      new functionality in QueryCensus.php            *
 *      2011/08/26      all query response pages renamed to             *
 *                      QueryResponseyyyy                               *
 *                      use actual buttons for actions that are links   *
 *      2011/10/13      support popup for mouseover forward and         *
 *                      back links                                      *
 *      2012/03/31      support hyperlink based upon IDIR field         *
 *                      phase out attrCell function                     *
 *                      combine name fields, remove age and district    *
 *                      name                                            *
 *                      color code names by sex                         *
 *      2013/04/14      use pageTop and PageBot to standardize page     *
 *                      layout                                          *
 *      2014/02/15      display unknown gender in green                 *
 *      2014/06/05      remove <table> used for layout                  *
 *                                                                      *
 *  History:                                                            *
 *      2015/01/13      created                                         *
 *                      add support for splitting screen with image     *
 *      2015/01/16      forward and backward links used old URL         *
 *      2015/01/20      add support for searching all censuses          *
 *                      use JOIN rather than WHERE expression to join   *
 *                      Districts and SubDistricts to Census table      *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/07/05      include 1906 and 1916 census in ALL             *
 *                      determine count for All response                *
 *      2015/07/08      use CommonForm.js                               *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/12/28      missing help for 'Details' button               *
 *      2016/01/21      use class Census to get census information      *
 *                      add id to debug trace div                       *
 *                      include http.js before util.js                  *
 *      2016/04/25      replace ereg with preg_match                    *
 *      2017/08/16      script legacyIndivid.php renamed to Person.php  *
 *      2017/09/12      use get( and set(                               *
 *      2017/11/04      correct sex display of name                     *
 *                      include language in hyperlink to Person page    *
 *      2017/12/18      do not pass empty parameter values to           *
 *                      CensusLineSet constructor                       *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/17      parameter list of new CensusLineSet changed     *
 *      2018/02/22      "See All Fields" button used old URL for form   *
 *      2018/06/06      do not set SubDist to array in npprev and npnext*
 *                      if there is only one value                      *
 *      2018/11/12      simplify construction of forward and back links *
 *      2019/01/19      avoid failure on district id array              *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/04/01      do not fail if district not specified           *
 *      2019/12/04      did not set lang parameter on rows              *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/03/24      use CensusLine pseudo-field 'sexclass'          *
 *      2020/04/16      move template ahead of validation               *
 *      2020/10/10      remove field prefix for Pages table             *
 *      2020/12/01      eliminate XSS vulnerabilities                   *
 *      2021/02/04      improve parameter checking and messages         *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *      2021/04/16      handle subdistrict id with colon better         *
 *      2022/04/17      handle invocation with collective Census        *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// set default values that are overriden by parameters

$censusYear             = 1881;         // census year
$censusId               = 'CA1881';
$censustext             = null;
$censusRec              = null;         // instance of Census
$cc                     = 'CA';         // country code
$countryName            = 'Canada';     // country name
$province               = 'ON';         // province/state code
$provincetext           = null;
$provinceName           = 'Ontario';    // province/state name
$distId                 = '';           // default all divisions
$districttext           = null;
$district               = '';           // default all districts
$districtObj            = null;         // instance of District
$DName                  = '';
$subDistId              = '';           // default all subdistricts
$subdisttext            = null;
$subDistrict            = null;         // instance of SubDistrict
$divId                  = '';           // default all subdistricts
$divisiontext           = null;
$limit                  = 20;           // default max lines per page
$limittext              = null;
$offset                 = 0;            // default start first line
$offsettext             = null;
$byeartext              = null;
$range                  = 1;            // default 1 year either side
$rangetext              = null;
$page                   = null;         // default any page
$pagetext               = null;
$family                 = '';           // default any family
$familytext             = null;
$lang                   = 'en';         // default language
$orderBy                = 'NAME';       // default order alphabetically
$orderbytext            = null;
$surnametext            = null;
$SurnameSoundex         = false;        // check text of surname
$soundextext            = null;
$result                 = array();
$respDesc               = '';
$search                 = '';
$respDescRows           = null;
$respDescSub            = null;
$respDescDiv            = null;
$respDescPage           = null;
$respDescFam            = null;
$parms                  = array();
$badfields              = array();

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
$parmCount              = 0;        // number of search parameters
if (isset($_GET) && count($_GET) > 0)
{
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                          "<table class='summary'>\n" .
                            "<tr>" .
                              "<th class='colhead'>key</th>" .
                              "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {                               // loop through all parameters
        if (is_array($value))
            $textValue  = 'array...';
        else
            $textValue  = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$textValue</td></tr>\n";
        if (is_string($value) && strlen($value) == 0)
            continue;

        $fieldLc                        = strtolower($key);
        switch($fieldLc)
        {                           // switch on parameter name
            case 'census':
            {                       // Census identifier
                if (preg_match('/^([a-zA-Z]{0,4})([0-9]{4}|ALL)$/',
                               $value, $matches))
                {                   // value passed
                    $censusId           = strtoupper($value);
                    $cc                 = $matches[1];
                    $censusYear         = $matches[2];
                }                   // value passed
                else
                    $censustext         = htmlspecialchars($value);
                break;
            }                       // Census identifier

            case 'count':
            case 'limit':
            {                       // limit number of rows returned
                if (ctype_digit($value) && $value >= 5 && $value <= 99)
                    $limit              = intval($value);
                else
                    $limittext          = htmlspecialchars($value);
                break;
            }                       // limit number of rows returned

            case 'offset':
            {                       // starting offset
                if (ctype_digit($value) && $value < 999999)
                {
                    $offset             = (int)$value;
                    $parms[$fieldLc]    = $offset;
                }
                else
                    $offsettext         = htmlspecialchars($value);
                break;
            }           // starting offset

            case 'orderby':
            {           // Override order of display
                $temp                   = strtoupper($value);
                if ($temp == 'NAME' || $temp == 'LINE')
                    $orderBy            = $temp;
                else
                    $orderbytext        = htmlspecialchars($value);
                break;
            }           // Override order of display

            case 'byear':
            {           // Birth Year
                if (ctype_digit($value) &&
                    $value >= 1750 && $value < 2100)
                {
                    $parms[$fieldLc]        = $value;
                    $parmCount      ++;
                }
                else
                    $byeartext          = htmlspecialchars($value);
                break;
            }           // Birth Year

            case 'range':
            {           // Range of ages or birth years
                $parmCount      ++;
                if (ctype_digit($value) &&
                    $value <= 20)
                {
                    $range              = intval($value);
                    $parms[$fieldLc]    = $value;
                }
                else
                    $rangetext          = htmlspecialchars($value);
                break;
            }           // "Range"

            case 'page':
            {           // "Page"
                if (ctype_digit($value) &&
                    $value > 0)
                {
                    $page                   = (int)$value;
                    $orderBy                = 'LINE';
                    $limit                  = 99;
                    $parms[$fieldLc]        = $value;
                    $parmCount++;
                }
                else
                    $pagetext       = htmlspecialchars($value);
                break;
            }           // "Page"

            case 'family':
            {           // Family
                if (preg_match("/^\w+$/", $value))
                {
                    $parms[$fieldLc]        = $value;
                    $parmCount      ++;
                    // value must not contain a quote/apostrophe
                    // value is normally a number but there are exceptions
                    // and the field is stored as a string in the database
                    $family         = $value;
                    $orderBy        = 'LINE';
                }
                else
                    $familytext     = htmlspecialchars($value);
                break;
            }           // "Family"

            case 'surname':
            {           // Surname regular expression
                if (is_string($value) &&
                    preg_match("/^[A-Za-z '^$.*[\]]+$/", $value))
                {
                    $parms[$fieldLc]        = $value;
                    $parmCount++;
                    $surname                = $value;
                }
                else
                    $surnametext            = htmlspecialchars($value);
                break;
            }           // match in string

            case 'surnamesoundex':
            {           // Do soundex comparison of surname
                if (is_bool($value) ||
                    (is_string($value) &&
                    preg_match("/^[nyNY01]/", $value)))
                {
                    $parms[$fieldLc]            = $value;
                    $parmCount++;
                    $SurnameSoundex             = true;
                }
                else
                    $soundextext    = htmlspecialchars($value);
                break;
            }           // Do soundex comparison of surname

            case 'province':
            {           // used only by menu
                $parmCount++;
                if (is_string($value) &&
                    preg_match("/^[A-Z]{2}$/", $value))
                {
                    $province               = $value;
                    $parms[$fieldLc]        = $value;
                }
                else
                    $provincetext   = htmlspecialchars(print_r($value,true));
                break;
            }           // used only by menu

            case 'district':
            {                   // district number or array of numbers
                $parmCount++;
                if (is_array($value) || is_numeric($value))
                {
                    $district               = $value;
                    $parms[$fieldLc]        = $value;
                }
                else
                    $districttext   = htmlspecialchars(print_r($value,true));
                break;
            }                   // district is simple text

            case 'subdistrict':
            {                   // subdistrict
                $parmCount      ++;
                if (is_string($value))
                {
                    if (strpos($value, ','))
                        $value      = explode(',', $value);
                    else
                        $value      = array($value);
                }
                if (is_array($value))
                {                   // array
                    $subDistId  = $value;
                    $comma      = '';
                    $parmvalue  = '';
                    foreach($value as $sd)
                    {               // loop through values
                        $rxcnt  = preg_match("/^([0-9.]+):([A-Za-z0-9]+)$/",
                                             $sd,
                                             $matches);
    
                        if ($rxcnt == 1)
                        {           // district:subdist format
                            $parmvalue  .= "$comma$sd";
                            $comma      = ',';
                        }           // district:subdist format
                        else
                        if (preg_match('/^[a-zA-Z0-9(){}[\]]+$/', $sd))
                        {           // subdist format
                            $parmvalue  .= "$comma$sd";
                            $comma      = ',';
                        }           // subdist format
                        else
                            $subdisttext    .= $comma . 
                                               htmlspecialchars($sd);
                    }               // loop through values
                    if (strlen($parmvalue) > 0)
                        $parms['subdistrict']   = $parmvalue;
                }
                else
                    $subdisttext    = htmlspecialchars(print_r($value, true));
                break;
            }                   // sub district

            case 'division':
            {           // Division, usually integer but not always
                if (is_string($value))
                {
                    $value              = urldecode($value);
                    $value  = preg_replace('/[^a-zA-Z0-9]/', '', $value);
                    $divId              = $value;
                    $parms[$fieldLc]    = $divId;
                    $parmCount          ++;
                }
                else
                    $divisiontext   = htmlspecialchars(print_r($value,true));
                break;
            }           // Division

            case 'lang':
            {           // language code
                $lang               = FtTemplate::validateLang($value);
                break;
            }           // language code

            case 'debug':
            case 'query':
            case 'submit':
            {           // ignore
                break;
            }

            case 'givennames':
            case 'occupation':
            case 'bplace':
            case 'origin':
            case 'nationality':
            case 'religion':
            case 'coverage':
            {           // no validation
                if (preg_match("/^[^<]+$/", $value))
                {
                    $parms[$fieldLc]        = $value;
                    $parmCount++;
                }
                else
                    $badfields[$key]        = htmlspecialchars($value);
                break;
            }           // no validation

            default:
            {           // other parameters simple text comparison
                if (preg_match("/^[a-zA-Z0-9 ',-]+$/", $value))
                {
                    $parms[$fieldLc]        = $value;
                    $parmCount  ++;
                }
                else
                    $badfields[$key]        = htmlspecialchars($value);
                break;
            }           // ordinary parameter
        }               // switch on parameter name
    }                       // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}

// choose template
$showLine           = $orderBy == "LINE";
if ($showLine)
    $showLineFile   = 'Line';
else
    $showLineFile   = '';
if ($censusYear == 'ALL')
    $file           = "CensusResponseAll$showLineFile$lang.html";
else
    $file           = "CensusResponse$showLineFile$lang.html";

$template   = new FtTemplate($file);

// validate parameters
if ($parmCount == 0)
    $msg            .= $template['noParameters']->innerHTML;

if (is_string($offsettext))
    $warn           .= $template['offsetInvalid']->replace('$value', $offsettext);
if (is_string($limittext))
    $warn           .= $template['limitInvalid']->replace('$value', $limittext);

// validate CensusId
if (is_string($censustext))
{                   // syntactically invalid
    $msg    .= $template['censusInvalid']->replace('$value', $censustext);
}                   // syntactically invalid
else
if ($censusId == 'CAALL')
{                   // search all Canadian Censuses
    $province               = 'CW'; // for pre-confederation
}                   // search all Canadian Censuses
else
if ($censusId)
{                   // specific census identifier
    $censusRec              = new Census(array('censusid'   => $censusId));
    if ($censusRec->isExisting())
    {
        $cc                 = $censusRec['cc'];
        $censusYear         = $censusRec['year'];
        $partof             = $censusRec['partof'];
        $collective         = $censusRec['collective'];
        if ($partof)
        {
            $province       = $censusRec['province'];
        }
        if ($collective)
        {
            $censusId       = $province . $censusYear;
            $censusRec      = new Census(array('censusid'   => $censusId));
        }

        $parms['censusid']  = $censusId;
    }
    else
        $msg    .= $template['censusUnsupported']->replace('$value', $censusId);
}                   // specific census identifier

if (is_string($orderbytext))
    $warn       .= $template['orderbyInvalid']->replace('$value', $orderbytext);
if (is_string($byeartext))
    $msg       .= $template['byearInvalid']->replace('$value', $byeartext);
if (is_string($rangetext))
    $msg        .= $template['rangeInvalid']->replace('$value', $rangetext);
if (is_string($pagetext))
    $msg        .= $template['pageInvalid']->replace('$value', $pagetext);
if (is_string($familytext))
    $msg        .= $template['familyInvalid']->replace('$value', $familytext);
if (is_string($surnametext))
    $msg        .= $template['surnameInvalid']->replace('$value', $surnametext);
if (is_string($soundextext))
    $msg        .= $template['soundexInvalid']->replace('$value', $soundextext);
if (is_string($provincetext))
    $msg        .= $template['provinceInvalid']->replace('$value', $provincetext);
if (is_string($districttext))
    $msg        .= $template['districtInvalid']->replace('$value', $districttext);
if (is_string($subdisttext))
    $msg        .= $template['subdistInvalid']->replace('$value', $subdisttext);
if (is_string($divisiontext))
    $msg        .= $template['divisionInvalid']->replace('$value', $divisiontext);
foreach ($badfields as $key => $text)
    $msg        .= $template['fieldInvalid']->replace(array('$key','$value'),
                                                      array($key, $text));

// now that the fields have all been validated we can
// construct the WHERE clause of the query
if (strlen($msg) == 0)
{                           // no errors in validation
    // start constructing the forward and back links
    $queryString        = urldecode($_SERVER['QUERY_STRING']);
    $queryString        = preg_replace('/\w+=&/', '', $queryString);
    $queryString        = preg_replace('/&\w+=$/', '', $queryString);
    $queryString        = preg_replace('/OrderBy=\w+&/i', '', $queryString);
    $queryString        = preg_replace('/&Page=\d+/i', '', $queryString);
    $queryString        = preg_replace('/&Family=\d+/i', '', $queryString);

    $npuri              = "CensusResponse.php?$queryString";    // base query
    $npPrev             = '';                               // previous selection
    $npNext             = '';                               // next selection

    // the list of fields to be displayed and the form of the link clause
    // to obtain required information from the Districts and SubDistricts
    // tables depends upon the census year
    if (ctype_digit($censusYear))
    {               // census year
        if ($censusYear < 1867)
        {           // pre-confederation
            $flds   = "Province, District, SubDistrict, Division, Page, Line," .
                          "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
                          "BPlace, Occupation, IDIR, Sex";

            $join  = "JOIN Districts ON " .
                            "(D_Census='$province$censusYear' AND D_Id=District) " .
                     "JOIN SubDistricts ON " .
                            "(SD_Census='$province$censusYear' AND " .
                            "SD_DistId=District AND " .
                    "SD_Id=SubDistrict AND SD_Div=Division) ";
        }           // pre-confederation
        else
        if ($censusYear == 1906)
        {           // first census of prairie provs, no Occupation field
            $flds   = "D_Province as Province, District, SubDistrict, Division, Page, Line," .
                          "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
                          "BPlace, '' AS Occupation, IDIR, Sex";

            $join   = "JOIN Districts ON " .
                            "(D_Census='CA$censusYear' AND D_Id=District) " .
                      "JOIN SubDistricts ON " .
                            "(SD_Census='CA$censusYear' AND " .
                            "SD_DistId=District AND " .
                    "SD_Id=SubDistrict AND SD_Div=Division) ";
        }           // post-confederation
        else
        {           // post-confederation
            $flds   = "D_Province as Province, District, SubDistrict, Division, Page, Line," .
                          "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
                          "BPlace, Occupation, IDIR, Sex";

            $join   = "JOIN Districts ON " .
                            "(D_Census='CA$censusYear' AND D_Id=District) " .
                      "JOIN SubDistricts ON " .
                            "(SD_Census='CA$censusYear' AND " .
                            "SD_DistId=District AND " .
                    "SD_Id=SubDistrict AND SD_Div=Division) ";
        }           // post-confederation
    }           // census year
    else
        $flds       = "Province, District, SubDistrict, Division, Page, Line,Surname, GivenNames, Age, BYear, D_Name, SD_Name,BPlace, Occupation, IDIR, Sex";

    $parms['limit']             = $limit;
    if (isset($join))
        $parms['join']          = $join;
    if ($page)
    {                       // display within a Page
        if ($orderBy == 'LINE')
        {                   // request for whole page
            $temp               = $page - 1;
            if ($temp > 0)
                $npPrev         = "Page=$temp";
            $temp               = 1 + $page;    // ensure numeric add
            $npNext             = "Page=$temp";
        }                   // request for whole page
    }                       // display within a Page

    if (ctype_digit($family))
    {                   // Family
        if ($orderBy == "LINE")
        {               // request for whole family
            $temp           = $family - 1;
            if ($temp > 0)
                $npPrev     = "Family=$temp";
            $temp           = 1 + $family;  // ensure numeric add
            $npNext         = "Family=$temp";
        }               // request for whole family
    }                   // "Family"

    // construct ORDER BY clause
    if ($orderBy == "LINE")
    {       // display lines in original order
        $npuri              .= "&OrderBy=Line";
        $limit              = 99;
    }       // display lines in original order
    else
    {       // display lines in alphabetical order
        $npuri              .= "&OrderBy=Name";
        // URI components for backwards and forwards
        // browser links
        $tmp                = $offset - $limit;
        if ($tmp < 0)
            $npPrev         = "";   // no previous link
        else
            $npPrev         = "Limit={$limit}&amp;Offset={$tmp}";
        $tmp                = $offset + $limit;
        $npNext             = "Limit={$limit}&amp;Offset={$tmp}";
    }       // display lines in alphabetical order

    // include district information in URIs for previous and next page
    if (isset($district))
    {
        $getParms                   = array();
        if ($censusYear == 1851 || $censusYear == 1861)
            $getParms['d_census']   = $province . $censusYear;
        else
            $getParms['d_census']   = $censusId;
        if (is_array($district))
            $dist_id                = reset($district);
        else
            $dist_id                = $district;
        if (!is_null($dist_id) && $dist_id != 0)
        {
            $getParms['d_id']       = $dist_id;
            $districtObj            = new District($getParms);
            $province               = $districtObj->get('d_province');
        }
        else
            $districtObj            = null;
    }
    else
    {
        $districtObj                = null;
    }

    // execute the query
    $parms['order']                 = ucfirst(strtolower($orderBy));
    if ($debug)
        $warn                       .= "<p>CensusResponse.php: " . __LINE__ .
            " new CensusLineSet(" . var_export($parms, true) .
                                "," . var_export($flds, true) . ")</p>\n";
    $result                         = new CensusLineSet($parms, $flds);
    $info                           = $result->getInformation();
    $count                          = $info['count'];
    if (isset($info['query']))
    {
        if ($debug)
            $warn           .= "<p>CensusResponse.php: " . __LINE__ .
                                        " query='" . $info['query'] . "'</p>\n";
    }
    else
        $msg                .= "CensusLineSet creation failed. ";

    // add additional data to result rows
    if ($count > 0)
    {                       // have a response
        $class      = 'odd';
        foreach($result as $i => $row)
        {                   // loop through lines of response
            if (is_null($row['division']))
                $row['division']    = '';
            $row['i']               = $i;
            $row['class']           = $class;
            if ($class == 'odd')
                $class              = 'even';
            else
                $class              = 'odd';
            $tempId                 = $row['censusid'];
            if (is_string($tempId))
            {
                $row['census']      = substr($tempId,2);
            }
            else
            {
                $row['censusid']    = $censusId;
                $row['census']      = substr($censusId,2);
            }
            if (!isset($row['province']))
                $row['province']    = $province;
            $district               = $row['district'];
            if (substr($district,-2) == '.0')
                $row['district']    = substr($district, 0, strlen($district) - 2);
            $idir                   = $row['idir'];
            $givennames             = $row['givennames'];
            $surname                = $row['surname'];
            $sex                    = $row->get('sexclass');
            if ($idir > 0)
                $row['fullname']    = "<a href='/FamilyTree/Person.php?idir=$idir&amp;lang=$lang' target='_blank' class='$sex'>\n" .
                                  "\t    <strong>$surname</strong>,\n" .
                                  "\t    $givennames\n" .
                                  "\t  </a>\n";
            else
                $row['fullname']    = "\t    <strong>$surname</strong>,\n" .
                "\t    $givennames\n";
            $row['lang']            = $lang;
            //$result[$i]       = $row;
        }                   // loop through lines of response
        $result->rewind();
    }                       // have rows in response
    else
    {
        $result                 = null;
        $warn                   .= $template['noRecords']->outerHTML;
    }

    // option to show everything on page or update
    if ($showLine)
    {                   // include line column in display
        $search             = "?Census=$censusId";
        $respDescRows       = null;
        if (is_array($subDistId))
            $SdId           = $subDistId[0];
        else
            $SdId           = $subDistId;
        $search             .= "&amp;Province=$province";
        if (is_array($district))
        {
            foreach($district as $val)
                $search     .= "&amp;District[]=" . $val;
        }
        else
            $search         .= "&amp;District=" . $district;
        $search             .= "&amp;SubDistrict=" . $SdId .
                               "&amp;Division=" . $divId;
        if ($page)
            $search         .= "&amp;Page=" . $page;
        else
        if (strlen($family) > 0)
            $search         .= "&amp;Family=" . $family;
        if ($censusYear < 1867)
            $censusId       = $province . $censusYear;
        else
            $censusId       = 'CA' . $censusYear;

        if (is_array($district))
            $dId            = $district[0];
        else
            $dId            = $district;
        if (is_array($SdId))
            $subdId         = $SdId[0];
        else
            $subdId         = $SdId;

        // determine division identifier to use in query
        if (strlen($distId) > 0)
        {
            $d              = strpos($distId, ':');
            if ($d !== false)
            {           // separator found
                $dId        = substr($distId, 0, $d);
                $subdId     = substr($distId, $d+1);
                $divId      = '';
            }           // separator found
        }
        if ($dId == floor($dId))
            $dId            = floor($dId);
        if (preg_match('/^([0-9.]+):(.+)$/', $subdId, $matches))
        {
            if ($matches[1] == $dId)
                $subdId     = $matches[2];
        }
        $sdParms            = array(
                                'census'    => $censusId,
                                'distId'    => $dId,
                                'SD_Id'     => $subdId,
                                'SD_Div'    => $divId);
        $subDistrict        = new SubDistrict($sdParms);
        if (!$subDistrict->isExisting())
            $msg            .=  "SubDistrict census='$censusId', distId=$dId, SD_Id='$subdId', SD_Div='$divId' does not exist. ";
        if ($lang == 'fr')
            $DName          = $subDistrict->get('d_nom');
        else
            $DName          = $subDistrict->get('d_name');
        $SubDName           = $subDistrict->get('sd_name');
        $page1              = $subDistrict->get('sd_page1');
        $imageBase          = $subDistrict->get('sd_imagebase');
        $relFrame           = $subDistrict->get('sd_relframe');
        $pages              = $subDistrict->get('sd_pages');
        $bypage             = $subDistrict->get('sd_bypage');
        if ($page)
        {
            $lastpage       = $page1 + $bypage * ($pages - 1);
            if ($page < $page1 ||
                $page > $lastpage ||
                (($bypage == 2) && (($page - $page1) % $bypage) != 0))
            {
                $msg        .= "$page is not a valid page number within SubDistrict ($censusId,$dId,$subdId,$divId).\n";
            }
        }

        // identify requested page or family
        $respDescSub        = array('dId'       => $dId,
                                    'DName'     => $DName,
                                    'subdId'    => $subdId,
                                    'SubDName'  => $SubDName,
                                    'province'  => '');
        if (strlen($divId) > 0)
            $respDescDiv    = array( 'divId'   => $divId);
        if ($censusYear < 1867)
            $respDescSub['province']    = $province;
        if ($page)
            $respDescPage   = array('page'      => $page);
        else
            $respDescFam    = array('family'    => $family);
    }                   // show line number column
    else
    if ($result)
    {                   // do not show line number
        $info               = $result->getInformation();
        $totalrows          = $info['count'];
        $first              = $offset + 1;
        $last               = min($offset + $limit, $totalrows);
        $respDescRows       = array('first'     => $first,
                                    'last'      => $last,
                                    'totalrows' => $totalrows);
    }                   // do not show line number
    else
    {
        $totalrows          = 0;
        $first              = $offset + 1;
        $last               = min($offset + $limit, $totalrows);
        $respDescRows       = array('first'     => $first,
                                    'last'      => $last,
                                    'totalrows' => $totalrows);
    }
}                           // no errors in validation

if (strtoupper($censusYear) == 'ALL')
    $censusYear = 'All';
$breadCrumbs    = array(array(  'url'   => "/genealogy.php?lang=$lang",
                            'label' => 'Genealogy'),
                        array(  'url'   => '/genCanada.html',
                            'label' => $countryName),
                        array(  'url'   => "/database/genCensuses.php?lang=$lang",
                            'label' => 'Censuses'),
                        array(  'url'   => "/database/QueryDetail.php?Census=CA$censusYear&lang=$lang",
                            'label' =>"New $censusYear Census Query"));
if ($showLine && $page)
{       // display whole page
    if (is_array($district))
    {
        $distText   = print_r($district, true);
        $distParm   = '';
        foreach($district as $val)
        {
            if (is_numeric($val) && $val == floor($val))
                $val    = floor($val);
            $distParm   .= "&amp;District[]=$val";
        }
    }
    else
    {
        if (is_numeric($district) && $district == floor($district))
            $district   = floor($district);
        $distText   = $district;
        $distParm   = "&amp;District=$district";
    }
    // add links for census hierarchy
    $breadCrumbs[]  = array('url'   => "CensusUpdateStatus.php?Census=$censusId",
                            'label' => "$censusYear Summary");
    if (is_numeric($district))
    {
        $breadCrumbs[]  = array('url'   => "CensusUpdateStatusDist.php?Census=$censusId&amp;Province=$province&amp;District=$district",
                            'label' => "District $distText $DName Summary");
    }
    $breadCrumbs[]  = array('url'   => "CensusUpdateStatusDetails.php?Census=$censusId&amp;Province=$province$distParm&amp;SubDistrict={$subDistId[0]}&amp;Division=$divId",
                            'label' => "Division Details");
}       // display whole page

$title  = "$censusYear Census of $countryName Query Response";

$template->set('CENSUSYEAR',        $censusYear);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CENSUSID',          $censusId);
$template->set('PROVINCE',          $province);
$template->set('PROVINCENAME',      $provinceName);
$template->set('LANG',          $lang);
if (isset($dId))
{
    $template->set('DISTRICT',      $dId);
    $template->set('DISTRICTNAME',      $DName);
    $template->set('SUBDISTRICT',       $subdId);
    $template->set('SUBDISTRICTNAME',   $SubDName);
    $template->set('DIVISION',      $divId);
}
$template->set('CENSUS',            $censusYear);
$template->set('SEARCH',            $search);
$template->set('CONTACTTABLE',      'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));

if (strlen($msg) == 0)
{                           // no errors and something to display
    $template['respdescrows']->update(  $respDescRows);
    $template['respdescsub']->update(   $respDescSub);
    $template['respdescdiv']->update(   $respDescDiv);
    $template['respdescfam']->update(   $respDescFam);
    $template['respdescpage']->update(  $respDescPage);
    if ($showLine && $page)
    {       // display whole page
        $pageRec    = new Page($subDistrict, $page);
        $image      = $pageRec->get('image');
        $template->set('IMAGE', $image);
    }
    else
        $template['buttonRow']->update(null);

    if (strlen($npPrev) > 0)
    {
        $template['topPrev']->update(
                             array('npPrev' => "$npuri&$npPrev"));
        $template['botPrev']->update(
                             array('npPrev' => "$npuri&$npPrev"));
    }
    else
    {
        $template['topPrev']->update(null);
        $template['botPrev']->update(null);
    }
    if (strlen($npNext) > 0)
    {
        $template['topNext']->update(
                             array('npNext' => "$npuri&$npNext"));
        $template['botNext']->update(
                             array('npNext' => "$npuri&$npNext"));
    }
    else
    {
        $template['topNext']->update(null);
        $template['botNext']->update(null);
    }

    // update the popup for explaining the action taken by arrows
    if (strlen($family) > 0)
    {                       // displaying a full family
        $template['familyminusonepre']->update(
                        array('family - 1'=> ($family - 1)));
        $template['familyplusonepre']->update(
                        array('family + 1'=> ($family + 1)));
        $template['familyminusonepost']->update(
                        array('family - 1'=> ($family - 1)));
        $template['familyplusonepost']->update(
                        array('family + 1'=> ($family + 1)));
        $template['rowminuscountpre']->update(null);
        $template['rowpluscountpre']->update(null);
        $template['rowminuscountpost']->update(null);
        $template['rowpluscountpost']->update(null);
        $template['pageminusonepre']->update(null);
        $template['pageplusonepre']->update(null);
        $template['pageminusonepost']->update(null);
        $template['pageplusonepost']->update(null);
    }                       // displaying a full family
    else
    if ($page)
    {                       // displaying a full page
        $template['pageminusonepre']->update(
                        array('page - 1'=> ($page - 1)));
        $template['pageplusonepre']->update(
                        array('page + 1'=> ($page + 1)));
        $template['pageminusonepost']->update(
                        array('page - 1'=> ($page - 1)));
        $template['pageplusonepost']->update(
                        array('page + 1'=> ($page + 1)));
        $template['familyminusonepre']->update(null);
        $template['familyplusonepre']->update(null);
        $template['familyminusonepost']->update(null);
        $template['familyplusonepost']->update(null);
        $template['rowminuscountpre']->update(null);
        $template['rowpluscountpre']->update(null);
        $template['rowminuscountpost']->update(null);
        $template['rowpluscountpost']->update(null);
    }                       // displaying a full page
    else
    {                       // any other query
        $template['rowminuscountpre']->update(
                         array('offset - limit + 1' => ($offset - $limit + 1)));
        $template['rowpluscountpre']->update(
                         array('offset + limit + 1' => ($offset + $limit + 1)));
        $template['rowminuscountpost']->update(
                         array('offset - limit + 1' => ($offset - $limit + 1)));
        $template['rowpluscountpost']->update(
                         array('offset + limit + 1' => ($offset + $limit + 1)));
        $template['familyminusonepre']->update(null);
        $template['familyplusonepre']->update(null);
        $template['familyminusonepost']->update(null);
        $template['familyplusonepost']->update(null);
        $template['pageminusonepre']->update(null);
        $template['pageplusonepre']->update(null);
        $template['pageminusonepost']->update(null);
        $template['pageplusonepost']->update(null);
    }                       // any other query

    $template['Row$i']->update($result);
}                           // no errors and something to display
else
{                           // nothing to display
    $template['topBrowse']->update(null);
    $template['buttonForm']->update(null);
    $template['botBrowse']->update(null);
}                           // nothing to display

$template->display();
