<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  QuerySurnamesTop.php												*
 *																		*
 *  Top index of surnames display.  Lists letters of the alphabet		*
 *  and number of surnames for each.									*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		Census			census identifier, for example 'CA1881'			*
 *		District		district number within census					*
 *		SubDistrict		subdistrict letter code within district			*
 *		Division		optional division within subdistrict			*
 *																		*
 *  History:															*
 *		2013/05/22		created											*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/21		use class Census to get census information		*
 *						add id to debug trace div						*
 *						include http.js before util.js					*
 *		2016/02/19		add links to update for district and division	*
 *						add name of division to title if relevant		*
 *		2017/02/07		use class Country								*
 *		2017/09/12		use get( and set(								*
 *		2018/01/18		tolerate lang parameter							*
 *						ignore case of parameter names					*
 *		2018/11/11      use class Template                              *
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

// default values of parameters
$cc                     = 'CA';
$country			    = null;     // instance of Country
$countryName			= null;
$province               = '';
$censusId				= null;
$census				    = null;     // instance of Census
$censusYear				= null;
$distId					= null;
$district				= null;     // instance of District
$subdistId				= null;
$subdistrict			= null;     // instance of SubDistrict
$division				= null;
$byear					= 0;
$range					= 0;
$lang					= 'en';
$where                  = '';       // WHERE clause
$sqlParms               = array();  // for prepared statement
$and                    = 'WHERE '; // combining operator to build WHERE
$orderby				= 'ORDER BY Surname ASC';
$count					= 20;
$offset					= 0;
$npuri                  = $_SERVER['QUERY_STRING'];

// process parameters
foreach ($_GET as $key => $value)
{			            // loop through all parameters
	switch(strtolower($key))
	{		            // switch on parameter name
	    case 'province':
        {		        // used only by menu
            if (strlen($value) == 2)
                $province       = strtoupper($value);
			break;
	    }		        // used only by menu

	    case 'census':
	    {		        // census identifier
			if (strlen($value) > 0)
			    $censusId	        = $value;
			break;
	    }		        // census identifier

	    case 'district':
	    {		        // district
			if (strlen($value) > 0)
			    $distId		        = $value;
			break;
	    }		        // district

	    case 'subdistrict':
	    {		        // subdistrict
			if (strlen($value) > 0)
			    $subdistId	    = $value;
			break;
	    }		        // sub district

	    case 'division':
	    {		        // division within subdistrict
			if (strlen($value) > 0)
			    $division	    = $value;
			break;
	    }		        // Division

	    case 'lang':
	    {		        // language selection
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
			break;
	    }		        // language selection

	    case 'debug':
	    {		        // handled by common code
			break;
	    }		        // handled by common code

	    default:
	    {		        // other parameters
			$warn	.= "<p>Unexpected parameter $key='$value'.</p>\n";
			break;
	    }	            // other parameters
	}		            // switch on parameter name
}			            // loop through all parameters

// create template
$template	        = new FtTemplate("QuerySurnamesTop$lang.html");

// validate parameters

// validate Census
if (is_null($censusId))
    $msg	.= 'Census identifier not provided. ';
else
{                       // validate Census identifier
    $census	            = new Census(array('censusid'	=> $censusId));
    if ($census->isExisting())
    {                   // defined census
        $cc             = $census->get('partof');
        if (strlen($cc) == 0)
            $cc		    = substr($censusId, 0, 2);
        else
			$province	= substr($censusId, 0, 2);
	    $country	    = new Country(array('code' => $cc));
	    $countryName	= $country->getName();
        $censusYear	    = intval(substr($censusId, 2));
    }                   // defined census
    else
        $msg    .= "Census Identifier '$censusId' is not defined. ";
}                       // validate Census identifier

// validate district

if (is_array($distId) && count($distId) == 1)
{                   // treat array with 1 entry as a simple value
    $distId         = $distId[0];
}                   // treat array with 1 entry as a simple value

if (is_string($distId) || is_numeric($distId))
{                       // validate single district identifier
    $district           = new District(array('census'       => $census,
                                             'id'           => $distId));
    if ($district->isExisting())
    {
        $where          .= "{$and}District=?";
        $sqlParms[]     = $distId;
        $and            = ' AND ';
    }
    else
	    $msg	.= "District number '$value' is not numeric.  ";
}                       // validate single district identifier
else
if (is_array($distId))
{                       // validate multiple district identifiers
    $district               = array();
    $or                     = "$and(";
	foreach($distId as $dist)
	{	                // loop through values
        $temp               = new District(array('census'       => $census,
                                                 'id'           => $distId));
        $district[]         = $temp;
        if ($temp->isExisting())
        {
            $where          .= "{$or}District=?";
            $sqlParms[]     = $distId;
            $or             = ' OR ';
        }
        else
		    $msg	.= "District number $dist is not defined within census '$censusId'. ";
    }	                // loop through values

    if ($or == ' OR ')
    {                   // at least 1 district comparison emitted
        $where              .= ')';
        $and                = ' AND ';
    }                   // at least 1 district comparison emitted
}                       // validate multiple district identifiers

// interpret subdistrict parameters

if (is_array($distId) && count($subdistId) > 1)
    $msg	.= "Multiple selection on district not supported with multiple selection on subdistrict. ";

if (!is_null($subdistId))
{                           // subdist specified
    if (!is_array($subdistId))
        $subdistId          = array($subdistId);
	$or                     = "$and(";
	foreach($subdistId as $id)
	{		                // loop through sub-districts
	    $d		            = strpos($id, ':');
	    if ($d == false)
	    {		            // only subdistrict Id specified
			if (is_array($distId))
				$msg	.= "Multiple districts not supported to qualify subdistrict. ";
	        else
	        if (is_null($distId))
	            $msg	.= "District id required to qualify subdistrict. ";
	        else
			{		        // single district
	            $where	        .= "{$or}SubDistrict=?";
	            $sqlParms[]     = $id;
                $or	            = ' OR ';
                if (is_null($subdistrict))
                {
                    $subParms   = array('census'    => $census,
                                        'district'  => $district,
                                        'id'        => $id);
                    $subdistrict    = new SubDistrict($subParms);
                }
			}		        // single district
	    }		            // only subdistrict Id specified
	    else
	    {                   // district:subdistrict
			$di		            = substr($id, 0, $d);
			$sd		            = substr($id, $d + 1);
			$where	            .= "{$or}(District=? AND SubDistrict=?)";
			$sqlParms[]         = $di;
			$sqlParms[]         = $sd;
			$or	                = ' OR ';
            if (is_null($subdistrict))
            {
                $distId         = $di;
                $subParms       = array('census'    => $census,
                                        'district'  => $di,
                                        'id'        => $sd);
                $subdistrict    = new SubDistrict($subParms);
                $district       = $subdistrict->getDistrict();
            }
		}		            // district:subdistrict
	}			            // loop through sub-districts
	
	if ($or == ' OR ')
	{                       // at least 1 sub-district comparison emitted
	    $where                  .= ')';
	    $and                    = ' AND ';
	}                       // at least 1 sub-district comparison emitted
}                       // subdist specified
else
{
	$template->set('SUBDISTID',	    '');
    $template->set('SDNAME',	    '');
}

// handle divisions
if (!is_null($division))
{                       // division specified
	if (!preg_match("/[a-zAZ0-9]+/", $division))
	{
	    $msg .= "Division='$division' contains invalid character.  ";
	}
	else
	{		            // valid
	    $where		        .= "{$and}Division=?";
	    $sqlParms[]         = $division;
	}		            // valid
}                       // division specified
else
{
	$template->set('DIVISION',	    '');
}

if (strlen($msg) == 0)
{
    // do main query
    $query	= "SELECT LEFT(Surname, 1) AS Initial, COUNT(*) AS number " .
							"FROM Census$censusYear " .
							"$where " .
							"GROUP BY Initial " .
							"ORDER BY Initial";

    $stmt	        = $connection->prepare($query);
    $queryText      = debugPrepQuery($query, $sqlParms);

    if ($stmt->execute($sqlParms))
    {
        if ($debug)
            $warn   .= "<p>QuerySurnamesTop.php: " . __LINE__ . 
                        " $queryText</p>\n";
        $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($debug)
            $warn   .= "<p>QuerySurnamesTop.php: " . __LINE__ . 
                        " count(\$result)=" . count($result) . "</p>\n";
    }
    else
    {		// error issuing query
		$warn   .= "<p>$queryText</p>\n";
        $warn   .= "<p class='message'>" . print_r($stmt->errorInfo(),true) . "</p>\n";
        $result             = array();
    }		// error issuing query
}
else
{               // errors
}               // errors

$template->set('COUNTRYNAME',	$countryName);
$template->set('PROVINCE',	    $province);
$template->set('CENSUSID',	    $censusId);
$template->set('CENSUSYEAR',	$censusYear);
if ($distId)
{
	if (is_array($distId))
	{
	    $distId             = $distId[0];
	    $district           = $district[0];
	}
	$template->set('DISTID',    $distId);
	$template->set('DNAME',	    $district->get('name'));
}
else
{
	$template->set('DISTID',    '');
    $template->set('DNAME',	'ALL');
}
if ($subdistId)
{
	if (is_array($subdistId))
	{
	    $subdistId          = $subdistId[0];
	}
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
else
{
    $template->updateTag('titleSubdist', null);
}
$template->set('NPURI',             '?'.$npuri);

if (is_array($result))
{
	// display the results
    $class	            = 'even';
    $headerRowElt       = $template->getElementById('headerRow');
    $childNodes         = $headerRowElt->childNodes();
    $numCols            = count($childNodes)/2;
    $c	                = 0;
    $rowElement         = $template->getElementById('row');
    $rows               = '';
    $data               = '';

	foreach($result as $i => $row)
    {
        $dtemplate      = new Template($rowElement->innerHTML());
        $initial 	    = $row['initial'];
        if (is_null($initial) || strlen($initial) == 0)
        {
            $dtemplate->set('INITIAL',      '&nbsp;');
            $dtemplate->set('PATTERN',      '^$');
        }
        else
        {
            $dtemplate->set('INITIAL',      htmlspecialchars($initial));
	        if (preg_match("/[A-Za-z0-9 ]/", $initial))
			    $pattern	= '^' . $initial;
	        else
			    $pattern	= '^[' . $initial . ']';
            $dtemplate->set('PATTERN',      $pattern);
        }
	    $number	        = $row['number'];
        $dtemplate->set('NUMBER',       number_format($number));
        $dtemplate->set('CLASS',        $class);
        $dtemplate->set('LANG',         $lang);
        $dtemplate->set('NPURI',        "?$npuri");
        $data           .= $dtemplate->compile();
        $c++;

	    if ($c == $numCols)
        {		        // start new row
	        if ($class == 'odd')
			    $class	= 'even';
	        else
                $class	= 'odd';
            $rtemplate      = new Template($rowElement->outerHTML());
            $rtemplate->updateTag('initialCell',    $data);
            $data           = '';
            $rtemplate->updateTag('statsCell',      null);
            $rows           .= $rtemplate->compile();
            $c              = 0;
        }		        // start new row
    }                   // loop through result
    $template->updateTag('row', $rows);
}
else
{
    $template->update('results',        null);
}

$template->display();
