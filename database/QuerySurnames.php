<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  QuerySurnames.php													*
 *																		*
 *  Display list of surnames for a subset of the census database.		*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		Census			census identifier, for example 'CA1881'			*
 *		District		district number within census					*
 *		SubDistrict		subdistrict letter code within district			*
 *		Division		optional division within subdistrict			*
 *		Surname			if present pattern match for surnames			*
 *						Normally this will be "^X" where X is the		*
 *						first letter of the desired surnames			*
 *		Count           maximum number of rows of surnames to display   *
 *		Offset          starting offset within the response set         *
 *																		*
 *  History:															*
 *		2011/08/26		created											*
 *		2012/09/16		Province parameter removed						*
 *		2013/05/22		use pageTop and pageBot to standardize layout	*
 *						use	$connection->quote to quote values		    *
 *		2013/05/26		display more rows by using columns of responses	*
 *						provide link back to first letter index			*
 *		2013/06/17		correct encoding of surnames with special chars	*
 *		2014/02/25		allow mixed case on parameters					*
 *						$debug set by common code						*
 *		2014/10/09		subsequent pages set LIMIT too high				*
 *						display correct count of number of surnames		*
 *						suppress backward and/or forward links if at	*
 *						beginning or end of results						*
 *						position help link properly with title			*
 *						center result statistics						*
 *						add columns parameteer to permit controlling	*
 *						number of columns in display					*
 *		2015/01/16		use CensusResponse.php instead of				*
 *						QueryResponseYYYY.php							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/21		use class Census to get census information		*
 *						add id to debug trace div						*
 *						include http.js before util.js					*
 *		2017/02/07		use class Country								*
 *		2018/01/18		tolerate lang parameter							*
 *		2018/11/16      use class Template                              *
 *		                use prepared statements                         *
 *		2019/02/21      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
$census			    = null;     // instance of class Census
$censusYear			= null;
$censusId			= null;
$district			= null;     // instance of class District
$distId				= null;
$subdistrict		= null;     // instance of class SubDistrict
$subdistId			= null;
$division			= null;
$cc		            = 'CA';
$countryName		= 'Canada';
$province		    = null;
$byear				= null;		// year of birth
$range				= null;		// birth year range
$lang				= 'en';
$orderby			= 'ORDER BY Surname ASC';
$count				= 20;		// number of rows to display
$columns			= 3;
$offset				= 0;
$totalcount			= null;
$npuri				= $_SERVER['QUERY_STRING'];
$npuri              = preg_replace('/&?offset=[^&]+/i', '', $npuri);
$npuri              = preg_replace('/&?surname=[^&]+/i', '', $npuri);
$where				= '';		// accumulate WHERE expression

// get parameter values
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{			// loop through all parameters
    if (is_array($value))
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                print_r($value,true) . "</td></tr>\n"; 
    else
    {
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n"; 
        $value              = trim($value);
    }
	switch(strtolower($key))
	{		// switch on parameter name
	    case 'count':
        {		// limit number of rows returned
            if (strlen($value) > 0)
			    $count	            = $value;
			break;
	    }		// limit number of rows returned

	    case 'columns':
        {		// limit number of columns displayed
            // ignored. This capability is moved to the template.
			break;
	    }		// limit number of columns displayed

	    case 'offset':
	    {		// starting offset
            if (strlen($value) > 0)
			    $offset	            = $value;
			break;
	    }		// starting offset

	    case 'totalcount':
	    {		// total count of surnames already determined
            if (strlen($value) > 0)
			    $totalcount	        = $value;
			break;
	    }		// total count of surnames

	    case 'orderby':
	    {		// Override order of display
			if (strtolower($value) == 'count')
			{
			    $orderby	    = 'ORDER BY Number DESC';
			}
			break;
	    }		// Override order of display

	    case 'byear':
	    {
            if (strlen($value) > 0)
			    $byear	            = $value;
			break;
	    }		// "BYear"

	    case 'range':
	    {		// range of birth years
            if (strlen($value) > 0)
			    $range	            = $value;
			break;
	    }		// "Range"

	    case 'surname':
        {		// pattern match
            if (strlen($value) > 0)
                $surname            = $value;
			break;
	    }		// match in string

	    case 'province':
	    {		// used only by menu
			break;
	    }		// used only by menu

	    case 'census':
	    {		// passed for Javascript
            if (strlen($value) > 0)
			    $censusId	        = $value;
			break;
	    }		// passed for Javascript

	    case 'district':
	    {		// district
            if (strlen($value) > 0)
			    $distId		        = $value;
			break;
	    }		// district

	    case 'subdistrict':
	    {		// subdistrict
            if (strlen($value) > 0)
			    $subdistId	        = $value;
			break;
	    }		// sub district

	    case 'division':
        {		// division within subdistrict
            if (strlen($value) > 0)
                $division           = $value;
			break;
	    }		// Division

	    case 'debug':
	    {		// handled by common code
			break;
	    }		// handled by common code

	    case 'lang':
	    {		// language selection
			if (strlen($value) >= 2)
			    $lang	    = strtolower(substr($value,0,2));
			break;
	    }		// language selection

	    default:
	    {		// other parameters simple text comparison
			if (!is_null($value) && strlen($value) > 0)
			{	// valid
                $where	                .= "$and$key=?";
                $sqlParms[]		        = $value;
                $and                    = ' AND ';
			}	// valid 
			break;
	    }		// ordinary parameter
	}		// switch on parameter name
}			// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

// create template
$template	        = new FtTemplate("QuerySurnames$lang.html");

// validate parameters and build WHERE clause
// constructing the WHERE clause by starting with $and set to 'WHERE '
// ensures that if no parameters are specified the WHERE clause is empty
$where              = '';
$sqlParms           = array();
$and                = 'WHERE ';     // connector between expressions

// validate row Count
$count              = preg_replace('/[.,]/', '', $count); 
if (!ctype_digit($count) || $count < 1 || $count > 99)
{
    $text               = $template['countInvalid']->innerHTML();
    $msg                .= str_replace('$count', $count, $text);
    $count              = 20;
}

// validate Offset
if (!is_null($offset))
{
    $offset             = preg_replace('/[.,]/', '', $offset); 
    if (!ctype_digit($offset))
    {
        $text           = $template['offsetInvalid']->innerHTML();
        $msg            .= str_replace('$offset', $offset, $text);
        $offset         = 0;
    }
}
else
    $offset             = 0;

// validate TotalCount
if (!is_null($totalcount))
{
    $totalcount         = preg_replace('/[.,]/', '', $totalcount); 
    if (!ctype_digit($totalcount) || $totalcount < 1)
    {
        $text           = $template['totalcountInvalid']->innerHTML();
        $msg            .= str_replace('$totalcount', $totalcount, $text);
        $totalcount     = null;
    }
}

// validate Census
if (!is_null($censusId))
{                       // Census parameter supplied
    if (strlen($censusId) < 6)
    {
        $text           = $template['censusInvalid']->innerHTML();
        $msg            .= str_replace('$censusId', $censusId, $text);
    }
    else
    {                   // string is long enough
	    $census	            = new Census(array('censusid'	=> $censusId));
        if (!$census->isExisting())
        {
            $text           = $template['censusUnsupported']->innerHTML();
            $msg            .= str_replace('$censusId', $censusId, $text);
        }
	    $partof             = $census->get('partof');
	    if ($partof)
	    {
	        $cc			    = $partof;
			$province	    = substr($censusId, 0, 2);
	    }
	    else
	        $cc			    = substr($censusId, 0, 2);
	    $country		    = new Country(array('code' => $cc));
	    $countryName	    = $country->getName();
	    $censusYear		    = intval(substr($censusId, 2));
    }                   // string is long enough
}                       // Census parameter supplied
else
    $msg            .= $template['censusMissing']->innerHTML();

// validate district parameter
if (!is_null($distId))
{                           // district specified
    if (is_array($distId))
    {                       // array of values
        $or                     = "{$and}(";
        foreach($distId as $id)
        {                   // loop through districts
            $dist   = new District(array('census'   => $census,
                                         'id'       => $id));
            if (is_null($district))
            {
                $district   = $dist;
                $distName   = $dist->get('name');
                $template->set('DISTID',    $id);
	            $template->set('DNAME',	    $distName);
            }
            if ($dist->isExisting())
            {
                $where          = "{$or}District=?";
                $sqlParms[]     = $id;
                $or             = ' OR ';
            }
            else
            {
                $text           = $template['districtUndefined']->innerHTML();
                $msg            .= str_replace(array('$id','$censusId'),
                                               array($id, $censusId), 
                                               $text);
            }
        }                   // loop through districts
        if ($or == ' OR ')
        {                   // at least one valid district in array
            $where              .= ')';
            $and                = ' AND ';
        }                   // at least one valid district in array
    }                       // array of values
    else
    {                       // single value
        $district   = new District(array('census'   => $census,
                                         'id'       => $distId));
        $distName   = $district->get('name');
	    $template->set('DISTID',    $distId);
	    $template->set('DNAME',	    $distName);
        if ($district->isExisting())
        {
            $where              = "{$and}District=?";
            $sqlParms[]         = $distId;
            $and                = ' AND ';
        }
        else
        {
            $text           = $template['districtUndefined']->innerHTML();
            $msg            .= str_replace(array('$id','$censusId'),
                                           array($distId, $censusId),
                                           $text);
        }
    }                       // single value
}                           // district specified
else
{
	$template->set('DISTID',    '');
    $template->set('DNAME',	    'ALL');
}

// interpret district and subdistrict parameters
if (!is_null($subdistId))
{			        // subdistrict 
    if (!is_array($subdistId))
    {
        $subdistlist    = array($subdistId);
        $or             = $and;
    }
    else
    {
        $subdistlist    = $subdistId;
        $or             = "$and(";
	    if (is_array($distId))
        {
            $msg        .= $template['multidistMultisubConflict']->innerHTML();
        }
    }

	foreach($subdistlist as $id)
	{		                // loop through values
	    $d		                = strpos($id, ":");
	    if ($d == false)
	    {		            // old form: separator not found
			if (is_string($distId) ||(is_array($distId) && count($distId)==1))
			{
                $where	        .= "{$or}SubDistrict=?";
                $sqlParms[]     = $id;
                if (is_null($subdistrict))
                {
                    $did            = $distId[0];
                    $parms          = array('census'    => $census,
                                            'district'  => $district,
                                            'id'        => $id);
                    $subdistrict    = new SubDistrict($parms);
                    $firstSdId      = $id;
	                $template->set('SUBDISTID',	    $subdistrict->get('id'));
                    $template->set('SDNAME',	    $subdistrict->get('name'));
                    if ($division)
                        $template->set('DIVISION',	    $division);
                    else
                    {
                        $template->updateTag('crumbSubdist',    null);
                        $template->updateTag('titleDivision',   null);
                    }
                }
			}	            // string distId
			else
            if (is_array($distId))
            {
                $msg	.= $template['multidistSingleSubConflict']->innerHTML();
                if (count($distId) >= 1)
                    $did            = $distId[0];
            }
			else
                $msg        .= $template['districtMissing']->innerHTML();
	    }		            // old form: not found
	    else
	    {		            // separator found
			$did	            = substr($id, 0, $d);
			$sd	                = substr($id, $d + 1);
			$where	            .= "$or(District=? AND SubDistrict=?)";
			$sqlParms[]         = $did;
			$sqlParms[]         = $sd;
            if (is_null($subdistrict))
            {
                $parms          = array('census'    => $census,
                                        'district'  => $did,
                                        'id'        => $sd);
                $subdistrict    = new SubDistrict($parms);
                $firstSdId      = $sd;
            }
	    }		            // separator found
	    $or		                = " OR ";
    }		                // loop through values
    if ($or == ' OR ')
    {                       // at least one test added
        if (is_array($subdistId) && $or == ' OR ')
	        $where	            .= ")\n";
        $and                    = ' AND ';
    }                       // at least one test added
    if (!is_null($subdistrict) && !$subdistrict->isExisting())
    {
        $text	= $template['subdistrictUndefined']->innerHTML();
        $msg    .= str_replace('$firstSdId', $firstSdId, $text);
    }
}			                // subdistrict 
else
{
	$template->set('SUBDISTID',         '');
    $template->set('SDNAME',	        '');
    $template->set('DIVISION',	        '');
    $template->updateTag('titleSubdist', null);
    $template->updateTag('crumbSubdist',    null);
}

// validate division 
if (!is_null($division) && strlen($division) > 0)
{		                    // division within subdistrict
    if (is_null($distId) || is_null($subdistId))
    {                       // missing mandatory parameters
        $msg	.= $template['divNeedsDistSubdist']->innerHTML();
    }                       // missing mandatory parameters
    else
    if ((is_array($distId) && count($distId)> 1) || 
        (is_array($subdistId) && count($subdistId) > 1))
	{
	    $warn .= "Division cannot be specified together with multiple selection for either District or SubDistrict.  ";
	}
	else
	{	                    // valid
        $where	                .= "{$and}Division=?";
        $sqlParms[]		        = $division;
        $and                    = ' AND ';
	}	                    // valid 
}		                    // Division

if (!is_null($surname) && strlen($surname) > 0)
{		                    // surname pattern match
	// value must be a limited regular expression
    $len		                = strlen($surname);
    $len1                       = $len - 1;
    if (substr($surname,0,1) == '^')
    {                       // match at beginning of name
        if ($len == 4 && 
            substr($surname,1,1) == '[' && substr($surname,3,1) == ']')
        {                   // special characters expressed as charset
		    $where	            .= "{$and}LEFT(Surname,1)=?"; 
		    $sqlParms[]		    = substr($surname, 2, 1);
        }                   // special characters expressed as charset
        else
        if (substr($surname,-1,1) == '$')
        {                   // exact match
            $where	            .= $and . "Surname=?";
		    $sqlParms[]		    = substr($surname, 1, $len-2);
        }                   // exact match
        else
        {                   // match string at beginning
            $where	            .= $and . "LEFT(Surname,$len1)=?"; 
		    $sqlParms[]		    = substr($surname, 1);
        }                   //match string at beginning
    }                       // match at beginning of name
	else
    if (substr($surname,-1,1) == '$')
    {                       // match at end of name
		$where	                .= "{$and}RIGHT(Surname,$len1)=?"; 
		$sqlParms[]		        = substr($surname, 0, $len1);
    }                       // match at end of name
    else
    {                       // match anywhere
	    $where	                .= $and . 'LOCATE(?, Surname) > 0';
		$sqlParms[]		        = $surname;
    }                       // match anywhere
}		                    // surname pattern match

// validate range parameter
if (!is_null($range) && strlen($range) > 0)
{                           // validate range
	if (!ctype_digit($range))
        $msg	.= $template['rangeInvalid']->innerHTML();
	else
    if ($range < 1 || $range > 20)
    {
        $text	= $template['rangeOutofrange']->innerHTML();
        $msg    .= str_replace('$range', $range, $text);
    }
}                           // validate range
else
    $range          = 0;

// birth year expression depends upon presence of range value
if (!is_null($byear))
{                           // validate birth year
	if (ctype_digit($byear) && strlen($byear) == 4)
    {                       // birth year valid
        $byear                  = $byear - 0;
	    if ($range > 0)
	    {			        // range of ages
	        $where	            .= "{$and}ABS(BYear - ?) <= ?";
	        $sqlParms[]         = $byear;
	        $sqlParms[]         = $range;
	    }			        // range of ages
	    else
	    {			        // specific value
			$where	            .= "{$and}BYear=?";
	        $sqlParms[]         = $byear;
	    }			        // specific value
    }                       // birth year valid
    else
    {
        $text	= $template['byearInvalid']->innerHTML();
        $msg    .= str_replace('$byear', $byear, $text);
        $byear                  = null;
    }
}		                    // validate birth year

// determine the value of the LIMIT clause from the parameters
// the number of columns in the display is controlled by the template
$headerRow      = $template['headerRow'];
if (!is_null($headerRow))
{
    $columns        = count($headerRow->childNodes())/2;
}
else
{
    $warn           .= "<p>QuerySurnames.php: " . __LINE__ .
                        " missing tag with id 'headerRow' in template</p>";
}

$limit		= $count * $columns;

// get count of total number of results
if (is_null($totalcount) && strlen($msg) == 0)
{			// do not already have total count	
    $query	= "SELECT surname, COUNT(*) AS number " .
							"FROM Census$censusYear " .
							"$where " .
							"GROUP BY Surname";

    $stmt	= $connection->prepare($query);
    if ($debug)
        $warn   .= "<p>" . __LINE__ . " query=" .
                    debugPrepQuery($query, $sqlParms) . "</p>\n";

    if ($stmt->execute($sqlParms))
    {
		$result		= $stmt->fetchAll(PDO::FETCH_NUM);
		$totalcount	= count($result);
    }
    else
    {			// error issuing query
		$msg        .=  "query=" . htmlspecialchars($query) . ".  ";
		$msg        .=  "sqlParms=" . print_r($sqlParms, true) . ".  ";
		$msg        .=  "errors=" . print_r($stmt->errorInfo(),true) . ".  ";
    }			// error issuing query
}			// do not already have total count

// determine the parameters to pass to the next and previous links
$prevoffset	        = $offset - $limit;
if ($prevoffset < 0)
    $npprev	    = '';	// no previous link
else
    $npprev	    = "&amp;Count=$count&amp;Offset=$prevoffset&amp;totalcount=$totalcount";
$nextoffset	    = $offset + $limit;
if ($nextoffset < $totalcount)
{
    $last       = $nextoffset;
    $npnext	    = "&amp;Count=$count&amp;Offset=$nextoffset&amp;totalcount=$totalcount";
}
else
{
    $last   = $totalcount;
    $npnext	= '';
}

// do main query
if (strlen($msg) == 0)
{                   // no errors
    $template->set('COUNT',	        $count);
    $template->set('OFFSET',	    $offset);
    $template->set('PREVOFFSET',	$prevoffset);
    $template->set('NEXTOFFSET',	$nextoffset);
    $template->set('OFFSETSTART',	$offset + 1);
    $template->set('LAST',          $last);
    $template->set('TOTALCOUNT',    $totalcount);
    $template->set('USURNAME',      urlencode($surname));

    $query	        = "SELECT surname, COUNT(*) AS number " .
			        			    "FROM Census$censusYear " .
			        			    "$where " .
			        			    "GROUP BY Surname  " .
			        			    "$orderby " .
			        			    "LIMIT $limit OFFSET $offset";

    $stmt		    = $connection->prepare($query);
    if ($debug)
        $warn       .= "<p>" . __LINE__ . " query=" .
        debugPrepQuery($query, $sqlParms) . "";

    if ($stmt->execute($sqlParms))
    {
		$result	            = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$last	            = $offset + count($result);

		// display the results
        $dataRow            = $template['dataRow'];
        $rowHtml            = $dataRow->outerHTML();
        $dataHtml           = $dataRow->innerHTML();
		$class	            = 'odd';
        $ic	                = 0;        // column display
        $rowdata            = '';
        $data               = '';
		foreach($result as $row)
        {
            $dtemplate          = new Template($dataHtml);
            $surname	        = $row['surname'];
            $dtemplate->set('SURNAME',      $surname);
            $usurname           = urlencode($surname);
            $dtemplate->set('USURNAME',     $usurname);
		    $number	            = $row['number'];
            if (strlen($surname) == 0)
                $esurname       = '&nbsp;';
            else
                $esurname       = htmlspecialchars($surname);
            $dtemplate->set('ESURNAME',     $esurname);
            $dtemplate->set('NUMBER',       number_format($number));
            $dtemplate->set('CLASS',        $class);
            $dtemplate->set('NPURI',        $npuri);
            $data               .= $dtemplate->compile();
		    $ic++;
		    if ($ic == $columns)
            {		            // end row
                $rtemplate      = new Template($rowHtml);
                $rtemplate->updateTag('surnameCol', $data);
                $data           = '';
                $rtemplate->updateTag('numberCol',  '');
                $rowdata        .= $rtemplate->compile();
				if ($class == 'odd')
				    $class	    = 'even';
				else
				    $class	    = 'odd';
				$ic	            = 0;
		    }		            // end row
        }		                // process all rows
        if ($ic > 0)
        {                       // output incomplete last row
            while ($ic < $columns)
            {
                $data       .= "<td>&nbsp;</td><td>&nbsp;</td>";
                $ic++;
            }
            $rtemplate      = new Template($rowHtml);
            $rtemplate->updateTag('surnameCol', $data);
            $rtemplate->updateTag('numberCol',  '');
            $rowdata        .= $rtemplate->compile();
        }                       // output incomplete last row
        $dataRow->update($rowdata);
    }
    else
    {			// error issuing query
		$msg        .=  "query=" . htmlspecialchars($query) . ".  ";
		$msg        .=  "sqlParms=" . print_r($sqlParms, true) . ".  ";
        $msg        .=  "errors=" . print_r($stmt->errorInfo(),true) . ".  ";
        $template->updateTag('response',    null);
    }			// error issuing query
}
else
{
    $template->updateTag('frontPager',      null);
    $template->updateTag('response',        null);
    $template->updateTag('backPager',       null);
}
$template->set('COUNTRYNAME',	$countryName);
$template->set('PROVINCE',	    $province);
$template->set('CENSUSID',	    $censusId);
$template->set('CENSUSYEAR',	$censusYear);

$template->set('NPURI',             $npuri);

$template->display();
