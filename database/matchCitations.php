<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  matchCitations.php                                                  *
 *                                                                      *
 *  This script attempts to match all citations of individuals in the   *
 *  family tree to the specified census page to the appropriate         *
 *  line on the page.  When a unique match is found a link is defined   *
 *  from the census line to the individual in the family tree.          *
 *                                                                      *
 *  History:                                                            *
 *      2012/04/09      created                                         *
 *      2013/04/20      fix syntax error in search                      *
 *                      provide proper header and footer                *
 *                      support "contribute" button                     *
 *      2013/08/25      add support for 1921 census                     *
 *      2013/11/26      handle database server failure gracefully       *
 *      2013/11/30      also match individual events for match          *
 *      2013/12/30      use CSS for layout                              *
 *                      genCensuses.php moved to lower directory        *
 *      2014/01/28      missing global $i caused incorrect HTML         *
 *                      and failure to update page form                 *
 *      2014/04/10      fix pattern failure for pre-confed censuses     *
 *      2014/07/15      support for popupAlert moved to common code     *
 *      2014/08/24      permit extra blanks in front of the page        *
 *                      number in citations to census pages to permit   *
 *                      sorting citations in a more natural order       *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/08/16      script legacyIndivid.php renamed to Person.php  *
 *      2017/12/15      use class CensusLine                            *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2022/04/01      use template for all output                     *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/CensusLine.inc';
    require_once __NAMESPACE__ . '/FtTemplate.inc';
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function checkRow                                                   *
 *                                                                      *
 *  Check one row of the database for matching citations.               *
 *                                                                      *
 *  Input:                                                              *
 *      $row            array returned from database                    *
 *      	$row[0]         idir                                        *
 *      	$row[1]         surname                                     *
 *      	$row[2]         givenName                                   *
 *      	$row[3]         birthSD                                     *
 *      	$row[4]         sex                                         *
 *      $etemplate      string of HTML with text insertion points       *
 *                                                                      *
 *  Returns:                                                            *
 *      String of HTML to insert in resulting page                      *
 ************************************************************************/
function checkRow($row, $etemplate)
{
    global  $connection;
    global  $debug;         // debug output requested
    global  $warn;          // accumulate warnings
    global  $msg;           // accumulate error messages
    global  $lang;          // user's preferred language
    global  $census;        // census ID 'CCyyyy'
    global  $censusYear;    // yyyy
    global  $table;         // database table to search
    global  $province;      // province code for pre-confederation
    global  $district;      // district number
    global  $subDistrict;   // sub-district identifier
    global  $division;      // division identifier
    global  $page;          // page identifier
    global  $i;             // row number

    $idir       			= $row[0];
    $surname    			= $row[1];
    // remove characters from surname that have special meaning
    // to REGEXP or not handled by SOUNDEX
    $surname    			= str_replace('?','',$surname);
    $surname    			= str_replace('*','',$surname);
    $surname    			= str_replace('[','',$surname);
    $surname    			= str_replace(']','',$surname);
    $surname    			= str_replace("'",'',$surname);

    $givenName  			= $row[2];
    if ($row[4] == 0)
        $sexTest            = " AND Sex='M'";
    else
    if ($row[4] == 1)
        $sexTest            = " AND Sex='F'";
    $birthsd                = $row[3];  // yyyymmdd
    $rxResult               = preg_match('/^[A-Za-z]/', $surname);
    if (strlen($surname) > 0 && $rxResult > 0)
    {   // surname acceptable to SOUNDEX
	    $birthYear          = floor($birthsd / 10000);
	
	    // pattern for matching surnames: 1st two characters and last
	    if (strlen($surname) > 3)
	        $surPattern     = '^' . substr($surname, 0, 2) . '.*' .
    	                      substr($surname, strlen($surname) - 1) . '$';
	    else
	        $surPattern     = '^' . $surname . '$';
	
	    // pattern for matching given names: 1st two chars anywhere
	    if (strlen($givenName) > 2)
	        $partGiven      = substr($givenName, 0, 2);
	    else
	        $partGiven      = $givenName;
	    $rxResult           = preg_match('/^[A-Z]+$/i', $partGiven);
	    if ($rxResult == 0) 
	        $partGiven      = ".";  // match anything
	
	    if ($censusYear < 1867)
	    {
	        $provinceW      = "Province=:province AND ";
	        $sqlParms       = array('province'  => $province);
	    }
	    else
	    {
	        $provinceW      = '';
	        $sqlParms       = array();
	    }
	
	    // the following looks for lines in the specified page
	    // where:
	    //  1. The surname matches by SOUNDEX and failing
	    //     that the first 2 characters and the last character
	    //     of the surname match.
	    //  2. The first 2 characters of the given name occur
	    //     somewhere in the given name in the census page.
	    //  3. The birth year is within 3 years of the birth
	    //     year in the family tree.
	    //  4. Matches on sex
	    $match  = "SELECT Line, Surname, GivenNames, BYear FROM $table " .
	                    "WHERE $provinceW District=:district AND " .
	                            "SubDistrict=:subDistrict AND " .
	                            "Division=:division AND " .
	                            "Page=:page AND " .
	                            "(SurnameSoundex=LEFT(SOUNDEX(:surname),4) OR ".
	                            "Surname REGEXP :surPattern) AND " .
	                            "GivenNames REGEXP :partGiven AND " .
	                            "ABS(:birthYear - BYear) < 4 " .
	                             $sexTest;
	    $sqlParms['district']       = $district;
	    $sqlParms['subDistrict']    = $subDistrict;
	    $sqlParms['division']       = $division;
	    $sqlParms['page']           = $page;
	    $sqlParms['surname']        = $surname;
	    $sqlParms['surPattern']     = $surPattern;
	    $sqlParms['partGiven']      = $partGiven;
	    $sqlParms['birthYear']      = $birthYear; 
	
	    $stmt               = $connection->prepare($match);
	    $matchText          = debugPrepQuery($match, $sqlParms);
	    if ($stmt->execute($sqlParms))
	    {       // successful query
	        $mResult        = $stmt->fetchAll(PDO::FETCH_ASSOC);
	        if ($debug)
                $warn       .= "<p>matchCitations.php: " . __LINE__ . 
                                    " $matchText</p>\n";
	
	        // action depends upon how many matches the above
	        // pattern returned
	        if (count($mResult) > 1 && strlen($givenName) > 2)
	        {       // more than one match
	            if ($debug)
	                $warn   .= '<p>number of rows in result=' .
	                                count($mResult) . "</p>\n";
	            // make given name test more restrictive
	            // look for match on 4 characters instead of just 2
	            // for example on "John" rather than "Jo"
	            if (strlen($givenName) > 4)
	                $givPattern = substr($givenName, 0, 4);
	            else
	                $givPattern = $givenName;
	            foreach($mResult as $mRow)
	            {       // loop through matches
	                $rxResult   = preg_match("/$givPattern/i",
	                                     $mRow['givennames']);
	                if ($rxResult == 1)
	                    break;
	                $lastRow    = $mRow;
	            }       // loop through matches
	            if (!$mRow)
	                $mRow       = $lastRow;
	        }       // more than one match
	        else
	        if (count($mResult) > 0)
	        {       // at most one match
	            $mRow           = $mResult[0];
	        }       // at most one match
	        else
	            $mRow           = null;
	
	        if ($mRow)
	        {
                $line           = $mRow['line'];
                $msurname       = $mRow['surname'];
                $mgivennames    = $mRow['givennames'];
                $mbyear         = $mRow['byear'];
                $text           = str_replace(
                    array('$i', '$idir', '$lang', '$surname', '$givenName', '$birthYear', '$msurname', '$mgivennames', '$mbyear', '$line'), 
                    array( $i ,  $idir ,  $lang ,  $row[1] ,  $row[2], $birthYear ,  $msurname ,  $mgivennames ,  $mbyear ,  $line) ,  
                    $etemplate);
	            $cenParms       = array('census'    => $census,
		                                'district'  => $district,
		                                'subdistrict'   => $subDistrict,
		                                'division'  => $division,
		                                'page'      => $page,
		                                'line'      => $line);
	            $censusLine     = new CensusLine($cenParms);
	            $censusLine->set('idir', $idir);
                $censusLine->save();
                return $text;
            }
	    }       // successful query
	    else
	    {
	        $msg    .= "'" . htmlentities($match) . "': " .
	                       print_r($connection->errorInfo(),true);
	    }       // error on query
    }           // surname acceptable to SOUNDEX
    return '';
}       // function checkRow

// interpret parameters
$census                 = null;
$censusYear             = null;
$table                  = null;
$province               = null;
$district               = null;
$subDistrict            = null;
$division               = null;
$page                   = null;
$lang                   = 'en';
$langtext               = null;
$censustext             = null;
$provincetext           = null;
$districttext           = null;
$subdistricttext        = null;
$divisiontext           = null;
$pagetext               = null;

// process parameters
if (isset($_GET) && count($_GET) > 0)
{
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                       // loop through all parameters
        $safevalue              = htmlspecialchars($value);
        $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
                                    "<td class='white left'>" .
                                    "$safevalue</td></tr>\n"; 
        $key                    = strtolower($key);
        switch($key)
        {                   // act on each specific parameter
            case 'census':
            case 'censusid':
            {               // census identifier: XXyyyy
                if (preg_match('/^[a-zA-Z]{2}[0-9]{4}$/', $value))
                {           // valid syntax
                    $census             = $value;
                    $censusYear         = substr($census, 2);
                    $table              = 'Census' . $censusYear;
                }
                else
                    $censustext         = $safevalue;
                break;
            }           // census identifier
    
            case 'province':
            {           // province code (pre-confederation)
                if ($value == '')
                    $province           = '';
                else
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $province           = strtoupper($value);
                else
                    $provincetext       = $safevalue;
                break;
            }           // province code
    
            case 'district':
            {   // district identifier
                if (preg_match("/^[0-9.]+$/", $value))
                    $district           = $value;
                else
                    $districttext       = $safevalue;
                break;
            }   // district identifier
    
            case 'subdistrict':
            {   // subDistrict identifier
                if (preg_match("/^[a-zA-Z0-9.]+$/", $value))
                    $subDistrict        = $value;
                else
                    $subdistricttext    = $safevalue;
                break;
            }   // subDistrict identifier
    
            case 'division':
            {   // division identifier
                if (preg_match("/^[a-zA-Z0-9]*$/", $value))
                    $division           = $value;
                else
                    $divisiontext       = $safevalue;
                break;
            }   // division identifier
    
            case 'page':
            {   // page number
                if (preg_match("/^[0-9]+$/", $value))
                    $page               = $value;
                else
                    $pagetext           = $safevalue;
                break;
            }   // page number
    
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value,
                                                               $langtext);
                break;
            }
    
            case 'debug':
            {
                break;
            }
    
            default:
            {
                $warn    .= "<p>Unexpected parameter: $key=$value ignored</p>\n";
                break;
            }
        }   // act on each specific parameter
    }       // loop through all parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

$template           = new FtTemplate("matchCitations$lang.html");

// check authorization
if (!canUser('edit'))
    $msg        .= 'You are not authorized to perform this function. ';

if (is_string($censustext))
{
    $msg        .= "Invalid syntax for Census identifier '$censustext'. ";
    $template->set('CENSUSID',          $censustext);
    $template->set('COUNTRYNAME',       'Unknown');
    $template->set('CC',                'CA');
}
else
if (is_null($census))
{
    $msg        .= "Missing mandatory parameter census. ";
    $template->set('CENSUSID',          '');
    $template->set('COUNTRYNAME',       'Unknown');
    $template->set('CC',                'CA');
}
else
{           // syntactically valid Census identifer provided
    $template->set('CENSUSID',          $census);
    $censusObj      = new Census(array('censusid' => $census));
    if ($censusObj->isExisting())
    {       // census is defined
        $idsr       = $censusObj['idsr'];
        $censusYear = $censusObj['year'];
        $cc         = $censusObj['countrycode'];
        $preConfed  = ($cc == 'CA') && ($censusYear < 1867);
        $country    = $censusObj->getCountry();
        $template->set('COUNTRYNAME', $country->getName());
        $template->set('CC', $cc);
    }       // census is defined
    else
    {       // census is not defined
        $msg    .= "Census '$census' is not defined. ";
        $country    = $censusObj->getCountry();
        $template->set('COUNTRYNAME', $country->getName());
        $template->set('CC', $cc);
    }       // census is not defined
}           // syntactically valid Census identifer provided

if (is_string($provincetext))
{
    $msg    .= "State or province identifier '$provincetext' is invalid. ";
    $template->set('PROVINCE',          $provincetext);
}
else
if (is_string($province))
    $template->set('PROVINCE',          $province);
else
    $template->set('PROVINCE',          '');

if (is_string($districttext))
{
    $msg    .= "District identifier '$districttext' is invalid. ";
    $template->set('DISTRICT',          $districttext);
}
else
if (is_null($district))
{
    $msg        .= "Missing mandatory parameter District. ";
    $template->set('DISTRICT',          '');
}
else
    $template->set('DISTRICT',          $district);

if (is_string($subdistricttext))
{
    $msg    .= "Sub-District identifier '$subdistricttext' is invalid. ";
    $template->set('SUBDISTRICT',       $subdistricttext);
}
else
if (is_null($subDistrict))
{
    $msg        .= "Missing mandatory parameter SubDistrict. ";
    $template->set('SUBDISTRICT',       '');
}
else
    $template->set('SUBDISTRICT',       $subDistrict);

if (is_string($divisiontext))
{
    $msg    .= "Division identifier '$divisiontext' is invalid. ";
    $template->set('DIVISION',          $divisiontext);
}
else
if (is_null($division))
{
    $msg        .= "Missing mandatory parameter Division. ";
    $template->set('DIVISION',          '');
}
else
    $template->set('DIVISION',          $division);

if (is_string($pagetext))
    $msg    .= "Page number '$pagetext' is invalid. ";

if (is_string($langtext))
    $warn   .= "<p>Language identifier '$langtext' is invalid.  English assumed. </p>\n";

$template->set('LANG',              $lang);
$template->set('CC',                $cc);
// if there are no errors, perform the function
if (strlen($msg) == 0)
{                   // no errors
    $subDist    = new SubDistrict(array('SD_Census' => $census,
                                        'SD_DistId' => $district,
                                        'SD_Id'     => $subDistrict,
                                        'SD_Div'    => $division));
    $dName      = $subDist->get('d_name');
    $subdName   = $subDist->get('sd_name');
    $template->set('DISTNAME',      $dName);
    $template->set('SUBDISTNAME',   $subdName);

    // establish pattern for matching citations to the specified page
    if ($preConfed)
    {               // pre-confederation census
        if (strlen($division) > 0)
            $pattern    = "'^$province, dist $district .* subdist $subDistrict .* div $division page +$page$'";
        else
            $pattern    = "'^$province, dist $district .* subdist $subDistrict .* page +$page$'";
    }   // pre-confederation census
    else
    {   // post-confederation census
        if (strlen($division) > 0)
            $pattern    = "'dist $district .* subdist $subDistrict .* div $division page +$page$'";
        else
            $pattern    = "'dist $district .* subdist $subDistrict .* page +$page$'";
    }   // post-confederation census

    // query to locate all citations from individuals in the family
    // tree to this particular census page
    // Getting the names from tblNX ensures we can compare to both
    // the maiden name and married name
    // But the sex is only recorded in tblIR and since we have to
    // add that to the join anyway, it is safer to get the birth date
    // from there, rather than depend upon the fact that contrary
    // to good database design the birthSD is replicated in tblNX
    $iquery = "SELECT DISTINCT IDIME, tblNX.Surname, tblNX.GivenName,
                               tblIR.BirthSD, tblIR.Gender 
                      FROM tblSX
                            JOIN tblNX ON tblNX.IDIR=tblSX.IDIME
                            JOIN tblIR ON tblIR.IDIR=tblSX.IDIME
                      WHERE tblSX.IDSR=$idsr AND
                            tblSX.Type=2 AND
                            tblSX.SrcDetail REGEXP $pattern";

    $stmt           = $connection->query($iquery);
    if ($stmt)
    {       // successful query
        $iresult    = $stmt->fetchAll(PDO::FETCH_NUM);
        if ($debug)
            $warn   .= "<p>matchCitations.php: " . __LINE__ . ' '. htmlspecialchars($iquery) . "</p>\n";

        $equery = "SELECT DISTINCT tblER.IDIR, tblNX.Surname, tblNX.GivenName,
                                   tblIR.BirthSD, tblIR.Gender 
                          FROM tblSX 
                                JOIN tblER ON tblER.IDER=tblSX.IDIME
                                JOIN tblIR ON tblIR.IDIR=tblER.IDIR
                                JOIN tblNX ON tblNX.IDIR=tblER.IDIR
                          WHERE tblSX.IDSR=$idsr AND
                                tblSX.Type=30 AND
                                tblSX.SrcDetail REGEXP $pattern";

        $stmt           = $connection->query($equery);
        if ($stmt)
        {           // query successful
            $eresult    = $stmt->fetchAll(PDO::FETCH_NUM);
            if ($debug)
                $warn   .= "<p>matchCitations.php: " . __LINE__ . ' '. htmlspecialchars($equery) . "</p>\n";
        }           // query successful
        else
        {
            $msg        .= $equery . ': ' .
                           print_r($connection->errorInfo(),true);
        }           // error on query
    }               // successful query
    else
    {               // error on query
        $msg            .= "'$iquery': " .
                               print_r($connection->errorInfo(),true);
    }               // error on query

    $i                  = 0;
    $etemplate          = $template['birthMatch']->outerHTML;
    $text               = '';
    foreach($iresult as $row)
    {               // loop through all results
        $text           .= checkRow($row, $etemplate);
        $i++;
    }               // loop through all results
    $template['birthMatch']->update($text);

    $etemplate          = $template['eventMatch']->outerHTML;
    foreach($eresult as $row)
    {               // loop through all results
        $text           .= checkRow($row, $etemplate);
        $i++;
    }               // loop through all results
    $template['eventMatch']->update($text);
}                   // no errors
else
{
    $template->set('DISTNAME',      'Not Found');
    $template->set('SUBDISTNAME',   'Not Found');
    $template['birthMatch']->update(null);
    $template['eventMatch']->update(null);
}

$template->display();
