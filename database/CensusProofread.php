<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusProofread.php                                                 *
 *                                                                      *
 *  Proofread a page of a Canadian census.                              *
 *                                                                      *
 *  History:                                                            *
 *      2011/07/02      created                                         *
 *      2011/10/22      validate all parameters                         *
 *                      support pre-confederation censuses              *
 *      2013/01/26      table SubDistTable renamed to SubDistricts      *
 *      2013/06/11      correct URL for requesting next page to edit    *
 *      2013/11/26      handle database server failure gracefully       *
 *      2014/04/26      remove formUtil.inc obsolete                    *
 *      2015/05/09      simplify and standardize <h1>                   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/21      use class Census to access census information   *
 *                      display debug trace                             *
 *                      include http.js before util.js                  *
 *      2017/11/21      use classes CensusLine and SubDistrict to       *
 *                      replace database queries                        *
 *      2017/11/24      separate ina style from other input styles      *
 *      2020/12/01      eliminate XSS vulnerabilities                   *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusLine.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function validate                                                   *
 *                                                                      *
 *  Input:                                                              *
 *      $oldrow     existing line from census transcription             *
 *      $newrow     data entered by proofreader                         *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing HTML from the template.                       *
 ************************************************************************/
function validate($oldrow, $newrow)
{
    global  $line;          // line number in output
    global  $template;      // instance of class FtTemplate
    global  $warn;

    if ($oldrow && $oldrow->isExisting())
    {                   // got results
        $rtemplate          = $template['dataRow$line'];
        if (is_object($rtemplate))
            $rtemplate          = $rtemplate->outerHTML;
        $result             = '';
        foreach($newrow as $fldname => $value)
        {               // loop through proofreader values
            $fieldLc        = strtolower($fldname);
            $rownum         = $oldrow['line'];
            if ($oldrow->get($fieldLc) != $value)
            {           // proofreader changed value
                ++$line;
                if ($line < 10)
                    $lineText   = '0' . $line;
                else
                    $lineText   = $line;
                if ($line < 100)
                {       // max 99 rows
                    $result     .= str_replace(array('$line',
                                                    '$rownum',
                                                    '$fldname',
                                                    '$oldvalue',
                                                    '$value',
                                                    '$notes'),
                                              array($lineText,
                                                    $rownum,
                                                    $fldname,
                                                    $oldrow->get($fldname),
                                                    $value,
                                                    ''),
                                              $rtemplate);
                }       // max 99 rows
                else
                {       // report overflow
                    $result     .=
			"        <tr>\n" .
			"          <th class='left' colspan='5'>\n" .
			"        Too many changes to page.  Remainder not reported.\n" .
			"          </th>\n" .
            "        </tr>\n";
                    break;
                }       // report overflow
            }           // proofreader changed value
        }               // loop through proofreader values
    }                   // got results from DB
    else
    {
        $msg    .= 'Logic Error: Query of database found no matching row. ';
    }
    return $result;
}       // validate

// open code
$censusID               = null;
$censusIDtext           = null;
$censusYear             = null;
$cc                     = 'CA';
$province               = '';
$distID                 = null;
$distIDtext             = null;
$subdistID              = null;
$subdistIDText          = null;
$division               = null;
$divisiontext           = null;
$page                   = null;
$pagetext               = null;
$lang                   = 'en';
$langtext               = null;
$line                   = 0;
$oldrownum              = '';
$newrow                 = array();
$lineSet                = null;
$getParms               = array('order' => 'line');

if (isset($_POST) && count($_POST) > 0)
{
    $parmsText              = "<p class='label'>\$_POST</p>\n" .
                                  "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    // validate the parameters that identify the specific page to
    // be updated
    foreach($_POST as $key => $value)
    {                       // loop through all parameters
        $safevalue              = htmlspecialchars(trim($value));
        $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
                                    "<td class='white left'>" .
                                    "$safevalue</td></tr>\n";
        $match                  = array();
        if (preg_match('/^([a-zA-Z_]+)(\d*)$/', $key, $match)) 
        {
            $key                = strtolower($match[1]);
            $rownum             = $match[2];
        }
        else
            $key                = strtolower($key);

        switch($key)
        {                   // act on specific parameter
            case 'census':
            case 'censusid':
                $matches        = array();
                if (preg_match('/^([a-zA-Z]{2})(\d{4})$/', $value, $matches))
                {
                    $censusID           = $value;
                    $getParms['censusid']   = $censusID;
                    $census             = new Census(
                                            array('censusid'   => $censusID,
                                                  'collective' => 0));
                    $cc                 = $matches[1];
                    $censusYear         = $matches[2];
                }
                else
                    $censusIDtext       = $safevalue;
                break;      // census identifier supplied

            case 'province':
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
                    $province           = $value;
                else
                    $provincetext       = $safevalue;
                break;      // province identifier supplied

            case 'district':
                if (preg_match("/^[0-9]+(\.5|\.0)?$/", $value))
                {
                    $distID             = $value;
                    $getParms['district']   = $value;
                    $district           = new District(
                                    array('Census'   => $censusID,
                                          'DistId'   => $distID));
                }
                else
                    $distIDtext         = $safevalue;
                break;      // district identifier supplied
    
            case 'subdistrict':
                if (preg_match("/^\w+$/", $value))
                {
                    $subdistID          = $value;
                    $getParms['subdistrict']        = $value;
                }
                else
                    $subdistIDtext      = $safevalue;
                break;      // sub-district identifier supplied
    
            case 'division':
                if (preg_match("/^\w+$/", $value))
                { 
                    $division           = $value;
                    $getParms['division']   = $division;
                }
                else
                    $divisiontext       = $safevalue;
                break;      // division supplied
    
            case 'page':
                if (preg_match("/^[0-9]+$/", $value))
                {
                    $page               = $value;
                    $getParms['page']   = $page;
                    $lineSet            = new CensusLineSet($getParms);
                    $oldrow             = $lineSet->rewind();
                    $oldrownum          = $oldrow['line'];
                }
                else
                    $pagetext           = $safevalue;
                break;      // page number supplied
    
            case 'image':
                if (preg_match("/^[0-9a-zA-Z:_.\-\/]+$/", $value))
                    $image              = $value;
                else
                    $imagetext          = $safevalue;
                break;      // page number supplied

            case 'lang':
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;      // language selection

        }                   // act on specific fields
    }                       // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

    if (count($newrow) > 0)
        $result     .= validate($oldrow,  
                                $newrow);
}                           // invoked by method=post

$template       = new FtTemplate("CensusProofread$lang.html");

if (isset($_GET) && count($_GET) > 0)
    $msg        .= $template['notPost']->innerHTML;

if (is_string($censusIDtext))
    $msg        .= $template['censusInvalid']->replace('$censusIDtext',
                                                       $censusIDtext);
else
if (is_string($censusID))
{
    if ($census->isExisting())
    {
        $cc         = $census['cc'];
        $province   = $census['province'];
        $censusYear = $census['year'];
    }
    else
        $msg    .= $template['censusUndefined']->replace('$censusID',
                                                         $censusID);
}
else
    $msg        .= $template['censusMissing']->innerHTML;

if (is_string($distIDtext))
    $msg        .= $template['districtInvalid']->replace('$distIDtext',
                                                         $distIDtext);
else
if (is_string($distID))
{
    if (!$district->isExisting())
        $msg    .= $template['districtUndefined']->
                                    replace(array('$censusID','$distID'),
                                            array($censusID, $distID));
}
else
    $msg        .= $template['districtMissing']->innerHTML;

if (is_string($subdistIDtext))
    $msg        .= $template['subdistrictInvalid']->
                                            replace('$subdistIDtext',
                                                    $subdistIDtext);
else
if (is_string($subdistID))
{
    $subDist    = new SubDistrict(array(
                        'SD_Census' => $censusID,
                        'SD_DistId' => $distID,
                        'SD_Id'     => $subdistID,
                        'SD_Div'    => $division));
    if (!$subDist->isExisting())
        $msg        .= $template['subdistrictUndefined']->
                        replace(array('$subdistID','$distID','$censusID'),
                                array($subdistID, $distID, $censusID));
}
else
    $msg        .= $template['subdistrictMissing']->innerHTML;

if (is_string($divisiontext))
    $msg        .= $template['divisionInvalid']->replace('$divisiontext',
                                                         $divisiontext);
else
if (is_null($division))
    $msg        .= $template['divisionMissing']->innerHTML;

if (is_string($pagetext))
    $msg        .= $template['pageInvalid']->replace('$pagetext',
                                                     $pagetext);
else
if(is_null($page))
{       // Page not supplied
    $msg        .= $template['pageMissing']->innerHTML;
}       // Page not supplied

if (is_string($imagetext))
    $msg        .= $template['imageInvalid']->replace('$imagetext',
                                                      $imagetext);

if (is_string($langtext))
{
    $warn       .= $template['langInvalid']->replace('$langtext',
                                                      $langtext);
}
if (!canUser('edit'))
{       // not authorized
    $msg        .= $template['notAuth']->innerHTML;
}       // not authorized

// if no errors were encountered in validating the parameters
// proceed to update the database
if (strlen($msg) == 0)
{       // no errors in validating page identifier
    foreach($_POST as $key => $value)
    {                       // loop through all parameters
        $match                  = array();
        if (preg_match('/^([a-zA-Z_]+)(\d*)$/', $key, $match)) 
        {
            $key                = strtolower($match[1]);
            $rownum             = $match[2];
        }
        else
            $key                = strtolower($key);

        if (strlen($rownum) > 0)
        {                   // field name contains row number
            if ($rownum == $oldrownum)
            {
                $newrow[$key]   = $value;
            }
            else
            {               // have all fields for new row
    	        $result             .= validate($oldrow,    
    	                                        $newrow);
    	        $newrow             = array();  // clear old values
                $newrow[$key]       = $value;
                $oldrow             = $lineSet->next();
                $oldrownum          = $oldrow['line'];
            }               // have all fields for new row
        }                   // field name contains row number
    }                       // loop through all parameters

    if (count($newrow) > 0)
        $result     .= validate($oldrow,  $newrow);

    $country        = $census->getCountry();
    $template->set('COUNTRYNAME',       $country->getName());
    $template->set('CC',                $cc);
    $template->set('CENSUSID',          $censusID);
    $template->set('CENSUSYEAR',        $census['year']);
    $template->set('PROVINCE',          $province);
    $template->set('DISTRICT',          $distID);
    $template->set('DISTRICTNAME',      $district['name']);
    $template->set('SUBDISTRICT',       $subdistID);
    $template->set('SUBDISTRICTNAME',   $subDist['name']);
    $template->set('DIVISION',          $division);
    $template->set('PAGE',              $page);
    $subject        = urlencode("CensusProofread.php Census=$censusID Province=$province District=$distID, SubDistrict=$subdistID, Division=$division, Page=$page");

    // identify the next page to update in this division
    $nextPage       = intval($page) + 1;
    $template->set('NEXTPAGE',              $nextPage);
    $prevPage       = intval($page) - 1;
    $template->set('PREVPAGE',              $prevPage);

    $lastPage       = $subDist['page1'] +
                      ($subDist['pages'] - 1) * $subDist['bypage'];
    $sdName         = $subDist['name'];
    if ($nextPage > $lastPage)
        $nextPage   = 0;    // no next page

    $tag            = $template['dataRow$line'];
    if ($tag)
        $tag->update($result);
    else
        $warn       .= "<p>Cannot find tag 'dataRow\$line'</p>\n";
}       // no errors in validating page identifier
else
{
    $template->set('COUNTRYNAME',       'Canada');
    $template->set('CENSUSYEAR',        1881);
    $template->set('CC',                'CA');
    $template->set('CENSUSID',          $censusID);
    $template->set('PROVINCE',          $province);
    $template->set('DISTRICT',          1);
    $template->set('DISTRICTNAME',      'Unknown');
    $template->set('SUBDISTRICT',       'A');
    $template->set('SUBDISTRICTNAME',   'Unknown');
    $template->set('DIVISION',          'Unknown');
    $template->set('PAGE',              '1');
    $template['results']->update();
    $template['nextPagePara']->update();
    $template['prevPagePara']->update();
    $template['proofTable']->update();
}

$template->display();
