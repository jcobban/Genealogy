<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegYearStats.php											*
 *																		*
 *  Display statistics about the transcription of marriage				*
 *  registrations for a specific year.									*
 *																		*
 *  Parameters:															*
 *		regYear			registration year								*
 *		regDomain		country code and state/province postal id		*
 *		county			county code to limit response to				*
 *																		*
 *  History:															*
 *		2011/03/14		created											*
 *		2011/10/05		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *		2012/06/23		add support for linking statistics				*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		move function pctClass to common.inc			*
 *						improve parameter handling						*
 *						use CSS rather than tables for layout			*
 *						add support for regDomain parameter				*
 *						use County class to expand county name			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/03/01		add information on lowest and highest regnum	*
 *						and percentage transcribed to display			*
 *		2016/03/30		support reporting single county					*
 *						support county level summary					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/09/08		zero-divide if only one registration in county	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/30		use composite cell style classes				*
 *		2017/12/17		add button to display marriages in number order	*
 *		2018/01/01		tolerate lang= parameter						*
 *						support both regdomain= and domain= parameters	*
 *						display both country and state/province			*
 *						in title										*
 *						only search requested domain					*
 *						do not display PHP_INT_MAX for $lowest			*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/07/08      use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$regYear		    = null;
$domain		        = 'CAON';	// default domain
$domainName		    = 'Canada: Ontario:';
$stateName		    = 'Ontario';
$lang		        = 'en';
$county		        = null;
$showTownship	    = false;

// get key values from parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'regyear':
	    {
			$regYear	    = $value;
			break;
	    }		// year 

	    case 'domain':
	    case 'regdomain':
	    {
			if (strlen($value) >= 4)
			    $domain		= strtoupper($value);
			break;
	    }		// RegDomain

	    case 'county':
	    {
			if (strlen($value) >= 2)
			    $county     = $value;
			break;
	    }		// county

	    case 'lang':
	    {
			if (strlen($value) == 2)
			    $lang	= strtolower($value);
			break;
	    }		// allow debug output

	    case 'count':
	    case 'list':
	    case 'debug':
	    {
			break;
	    }		// allow debug output

	    default:
	    {
			$warn	    .= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters

	}		// act on specific parameters
}			// loop through all parameters
if ($debug)
    $warn               .= $parmsText . "</table>\n";

// get template
if ($county)
    $template           = new FtTemplate("MarriageRegYearCountyStats$lang.html");
else
    $template           = new FtTemplate("MarriageRegYearStats$lang.html");

// interpret registration year parameter
if (is_null($regYear))
    $msg	            .= 'Year of registration not specified. ';
else
if (!ctype_digit($regYear) || $regYear < 1800 || $regYear > 2000)
    $msg	            .= "RegYear $regYear must be a number between 1860 and 2000. ";
$template->set('YEAR',              $regYear);
$template->set('PREVYEAR',          $regYear - 1);
$template->set('NEXTYEAR',          $regYear + 1);

// interpret domain code
$domainObj	            = new Domain(array('domain'	    => $domain,
				                    	   'language'	=> $lang));
if (!$domainObj->isExisting())
{
    $msg	            .= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
    $domain		        = 'CAON';	// restore default
    $domainObj	        = new Domain(array('domain'	    => $domain,
				                    	   'language'	=> $lang));
}
$cc                     = substr($domain, 0, 2);
$country                = new Country(array('cc'        => $cc));
$countryName            = $country->getName($lang);
$stateName	            = $domainObj->getName(0);
$domainName	            = $domainObj->getName(1);
$template->set('CC',			    $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('STATENAME',			$stateName);
$template->set('DOMAIN',		    $domain);
$template->set('DOMAINNAME',		$domainName);

if ($county)
{
    $template->set('COUNTY',        $county);
	$countyObj		    = new County($domain, $county);
	if ($countyObj->isExisting())
	{
	    $countyName		= $countyObj->get('name');
        $template->set('COUNTYNAME',$countyName);
	    $showTownship	= true;
	}
	else
	{
	    $msg	        .= "County code '$county' is not valid for domain '$domain'. ";
        $template->set('COUNTYNAME',$county);
	}
}

// execute the query
if (is_null($county))
    $query	= "SELECT M_RegCounty, " .
				"SUM(M_Date != '') AS MCount, " .
				"SUM(M_IDIR != 0) AS LinkCount, " .
				"MIN(Marriage.M_RegNum) as low, " .
				"MAX(Marriage.M_RegNum) as high " .
				"FROM Marriage, MarriageIndi " .
				"WHERE Marriage.M_RegDomain='$domain' AND " .
				      "Marriage.M_RegYear=$regYear AND " .
				      "MarriageIndi.M_Role!='M' AND " .
				      "Marriage.M_RegDomain=MarriageIndi.M_RegDomain AND " .
				      "Marriage.M_RegYear=MarriageIndi.M_RegYear AND " .
				      "Marriage.M_RegNum=MarriageIndi.M_RegNum " .
    		"GROUP BY M_RegCounty " .
				"ORDER BY M_RegCounty";
else
    $query	= "SELECT M_RegCounty, M_RegTownship, " .
				"SUM(M_Date != '') AS MCount, " .
				"SUM(M_IDIR != 0) AS LinkCount, " .
				"MIN(Marriage.M_RegNum) as low, " .
				"MAX(Marriage.M_RegNum) as high " .
				"FROM Marriage, MarriageIndi " .
				"WHERE Marriage.M_RegDomain='$domain' AND " .
				      "Marriage.M_RegYear=$regYear AND " .
				      "Marriage.M_RegCounty='$county' AND " .
				      "MarriageIndi.M_Role!='M' AND " .
				      "Marriage.M_RegDomain=MarriageIndi.M_RegDomain AND " .
				      "Marriage.M_RegYear=MarriageIndi.M_RegYear AND " .
				      "Marriage.M_RegNum=MarriageIndi.M_RegNum " .
    		"GROUP BY M_RegCounty, M_RegTownship " .
            "ORDER BY M_RegCounty, M_RegTownship";

$stmt	 	                = $connection->query($query);
if ($stmt)
{		// successful query
    if ($debug)
        $warn	            .= "<p>$query</p>\n";
    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
}		// successful query
else
{
    $msg	                .= "query '$query' failed: " .
                                print_r($connection->errorInfo(),true);
    $result                 = array();
}		// query failed

$total		    			= 0;
$totalLinked				= 0;
$rownum		    			= 0;
$countyObj					= null;
$countyName					= '';
$lowest		    			= PHP_INT_MAX;
$highest					= 0;
$dataRowElt                 = $template['dataRow$ROWNUM'];
if (!$dataRowElt)
    $template->getDocument()->printTag();
$dataRowHtml                = $dataRowElt->outerHTML();
$rowclass                   = "odd";
$data                       = '';
foreach($result as $row)
{                       // loop through results
    $rtemplate              = new \Templating\Template($dataRowHtml);
    $rownum++;
    $rtemplate->set('ROWNUM',       $rownum);
	$county		            = $row['m_regcounty'];
    $rtemplate->set('COUNTY',       $county);
	if (is_null($countyObj) || $county != $countyObj->get('code'))
	{		            // new county code
		$countyObj	        = new County($domain, $county);
		$countyName	        = $countyObj->get('name');
	}		            // new county code 
    $rtemplate->set('COUNTYNAME',   $countyName);
    if (array_key_exists('m_regtownship', $row))
    {
        $township	        = $row['m_regtownship'];
        $rtemplate->set('TOWNSHIP', $township);
    }
	$count		            = $row['mcount'];
    $rtemplate->set('COUNT',        number_format($count));
	$total		            += $count;
	$linked		            = $row['linkcount'];
    $rtemplate->set('LINKCOUNT',    $linked);
	if ($count == 0)
	    $pctLinked	        = 0;
	else
	    $pctLinked	        = 100 * $linked / $count;
    $rtemplate->set('PCTLINKED',    number_format($pctLinked,2));
    $rtemplate->set('PCTLINKEDCLASS',pctClass($pctLinked));
	$totalLinked	        += $linked;
	$low		            = $row['low'];
    $rtemplate->set('LOW',          $low);
	$high		            = $row['high'];
    $rtemplate->set('HIGH',         $high);
	if ($low < $lowest)
	    $lowest	            = $low;
    if ($high > $highest &&
        ($highest == 0 || 
            ($high - $low) < 2000))
        $highest	        = $high;
	$todo		            = $high - $low + 1;
	if ($todo == 0)
	    $pctDone	        = 0;
	else
	    $pctDone	        = 50 * $count / $todo;
    $rtemplate->set('PCTDONE',      number_format($pctDone, 2));
    $rtemplate->set('PCTDONECLASS', pctClass($pctDone));
    $rtemplate->set('CLASS',        $rowclass);

    $data                   .= $rtemplate->compile();

    if ($rowclass == 'odd')
        $rowclass           = 'even';
    else
        $rowclass           = 'odd';
}		                // process all rows
$dataRowElt->update($data);

if ($total == 0)
{
	$pctDone	= 0;
	$pctLinked	= 0;
}
else
{
	$pctDone	= 50 * $total / ($highest - $lowest + 1);
	$pctLinked	= 100 * $totalLinked / $total;
}
if (strlen($total) > 3)
	$total	= substr($total, 0, strlen($total) - 3) . ',' .
			  substr($total, strlen($total) - 3);
if ($lowest > $highest)
    $lowest		= $highest;

$template->set('TOTAL',         $total);
$template->set('LOWEST',        $lowest);
$template->set('HIGHEST',       $highest);
$template->set('PCTDONE',       number_format($pctDone, 2));
$template->set('PCTDONECLASS',  pctClass($pctDone));
$template->set('PCTLINKED',     number_format($pctLinked,2));
$template->set('PCTLINKEDCLASS',pctClass($pctLinked));

$template->display();
