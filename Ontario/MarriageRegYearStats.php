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
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/common.inc';

$regYear		= null;
$domain		    = 'CAON';	// default domain
$domainName		= 'Canada: Ontario:';
$stateName		= 'Ontario';
$lang		= 'en';
$county		= null;
$showTownship	= false;

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
			if (preg_match("/^1[89][0-9]{2}$/", $value) == 1)
			    $regYear	= $value;
			else
			    $msg	.= "RegYear $regYear must be a number between 1860 and 2000. ";
			break;
	    }		// year 

	    case 'domain':
	    case 'regdomain':
	    {
			if (strlen($value) == 0)
			    break;
			$domain		= strtoupper($value);
	        // interpret domain code
			$domainObj	= new Domain(array('domain'	=> $domain,
								   'language'	=> 'en'));
			if (!$domainObj->isExisting())
			{
			    $msg	.= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
			}
			$stateName	= $domainObj->getName(0);
			$domainName	= $domainObj->getName(1);
			break;
	    }		// RegDomain

	    case 'county':
	    {
			if (strlen($value) == 0)
			    break;
			$countyObj		= new County($domain, $value);
			if ($countyObj->isExisting())
			{
			    $county		= $value;
			    $countyName		= $countyObj->get('name');
			    $showTownship	= true;
			}
			else
			{
			    $msg	.= "County code '$value' is not valid for domain '$domain'. ";
			}
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
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters

	}		// act on specific parameters
}			// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (is_null($regYear))
	$msg	.= 'Year of registration not specified. ';

if (strlen($msg) == 0)
{			// no errors
	$title		= $domainName;
	if (!is_null($county))
	    $title	.= ' ' . $countyName . ':';
	$title		.= " Marriage Registration Status $regYear";

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
    if ($debug)
	    $warn	.= "<p>$query</p>\n";
	$stmt	 	= $connection->query($query);
	if ($stmt)
	{		// successful query
	    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
	}		// successful query
	else
	{
	    $msg	.= "query '$query' failed: " .
					   print_r($connection->errorInfo(),true);
	}		// query failed
}			// no errors
else
{		// errors detected
	$title		= $domainName.": Marriage Registration Status by Year";
}		// errors detected

htmlHeader($title,
			array('/jscripts/util.js',
			     'MarriageRegYearStats.js'));
$crumbs	= array(
			'/genealogy.php'	=> 'Genealogy',
			'/genCanada.html'	=> 'Canada',
			"/Canada/genProvince.php?Domain=$domain"	=> $stateName,
			"MarriageRegQuery.html?RegDomain=$domain"=>'New Marriage Query',
			"MarriageRegStats.php?RegDomain=$domain"=> 'Status');
if (!is_null($county))
	$crumbs["MarriageRegYearStats.php?RegDomain=$domain&RegYear=$regYear"] =
							   "County Status $regYear";
?>
<body>
<?php
pageTop($crumbs);
?>
<div class='body'>
  <h1><?php print $title; ?>
<span class='right'>
<?php
if (!is_null($county))
{
?>
	<a href='MarriageRegYearCountyStatsHelpen.html' target='_blank'>Help?</a>
<?php
}
else
{
?>
	<a href='MarriageRegYearStatsHelpen.html' target='_blank'>Help?</a>
<?php
}
?>
</span>
  </h1>
<?php
showtrace();

if (strlen($msg) > 0)
{		// print error messages if any
?>
  <p class='message'>
<?php print $msg; ?> 
  </p>
<?php
}		// print error messages if any
else
{		// display results of query
?>
  <div class='center' id="topBrowse">
    <div class='left' id="topPrev">
      <a href='MarriageRegYearStats.php?regdomain=<?php print $domain; ?>&regyear=<?php print $regYear - 1; ?>&county=<?php print $county; ?>' id='toPrevYear'>
	    &lt;--- <?php print $regYear - 1; ?> 
	  </a>
    </div>
    <div class='right' id="topNext">
	  <a href='MarriageRegYearStats.php?regdomain=<?php print $domain; ?>&regyear=<?php print $regYear + 1; ?>&county=<?php print $county; ?>' id='toNextYear'>
	    <?php print $regYear + 1; ?> ---&gt;
	  </a>
    </div>
    <div style='clear: both;'></div>
  </div>
  <form id='display' action='donothing.php' method='get'>
<input type='hidden' id='RegYear' 
	value='<?php print $regYear; ?>'>
<input type='hidden' id='Domain' 
	value='<?php print $domain; ?>'>
<!--- Put out the response as a table -->
<table class='form' id="dataTable">
  <thead>
  <!--- Put out the column headers -->
	<tr>
	  <th class='colhead1st'>
	    County
	  </th>
<?php
	if ($showTownship)
	{
?>
	  <th class='colhead'>
	    Township
	  </th>
<?php
}
?>
	  <th class='colhead'>
	    Done
	  </th>
	  <th class='colhead'>
	  Low
	  </th>
	  <th class='colhead'>
	  High
	  </th>
	  <th class='colhead'>
	  %Done
	  </th>
	  <th class='colhead'>
	    %Linked
	  </th>
	  <th class='colhead' colspan='2'>
	    View
	  </th>
	</tr>
  </thead>
  <tbody>
<?php
	    $total		    = 0;
	    $totalLinked	= 0;
	    $rownum		    = 0;
	    $countyObj		= null;
	    $countyName		= '';
	    $lowest		    = PHP_INT_MAX;
	    $highest		= 0;
	    foreach($result as $row)
	    {
			$rownum++;
			$county		= $row['m_regcounty'];
			try {
			    if (is_null($countyObj) ||
					$county != $countyObj->get('code'))
			    {		// new county code
					$countyObj	= new County($domain, $county);
					$countyName	= $countyObj->get('name');
			    }		// 
			} catch (Exception $e)
			{
			    if ($debug)
					print "<p class='message'>" . $e->getMessage() .
						"</p>\n";
			    $countyName		= $county;
			}
			if (array_key_exists('m_regtownship', $row))
			    $township	= $row['m_regtownship'];
			$count		= $row['mcount'];
			$total		+= $count;
			$linked		= $row['linkcount'];
			if ($count == 0)
			    $pctLinked	= 0;
			else
			    $pctLinked	= 100 * $linked / $count;
			$totalLinked	+= $linked;
			$low		    = $row['low'];
			$high		    = $row['high'];
			if ($low < $lowest)
			    $lowest	    = $low;
            if ($high > $highest &&
                ($highest == 0 || 
                 ($high - $low) < 2000))
                $highest	= $high;
			$todo		    = $high - $low + 1;
			if ($todo == 0)
			    $pctDone	= 0;
			else
			    $pctDone	= 50 * $count / $todo;
			if (strlen($count) > 3)
			    $count	= number_format($count, 0, '.', ',');
?>
	<tr>
	  <td class='odd bold left first'>
	    <?php print $countyName; ?>
	    <input type='hidden' id='County<?php print $rownum; ?>' 
			value='<?php print $county; ?>'>
	  </td>
<?php
	if (array_key_exists('m_regtownship', $row))
	{
?>
	  <td class='odd bold left'>
	    <?php print $township; ?>
	    <input type='hidden' id='Town<?php print $rownum; ?>' 
			value='<?php print $township; ?>'>
	  </td>
<?php
	}
?>
	  <td class='odd bold right'>
	    <?php print $count; ?>
	  </td>
	  <td class='odd bold right'>
	      <?php print $low; ?>
	      <input type='hidden' id='low<?php print $rownum; ?>' 
			  value='<?php print $low; ?>'>
	  </td>
	  <td class='odd bold right'>
	      <?php print $high; ?>
	      <input type='hidden' id='high<?php print $rownum; ?>' 
			  value='<?php print $high; ?>'>
	  </td>
	  <td class='<?php print pctClass($pctDone); ?>'>
	      <?php print number_format($pctDone, 2); ?>% 
	  </td>
	  <td class='<?php print pctClass($pctLinked); ?>'>
	    <?php print number_format($pctLinked, 2); ?>% 
	  </td>
<?php
			if ($showTownship)
			{
?>
	  <td>
	    <button type='button' id='TownStats<?php print $rownum; ?>'>
			Alpha
	    </button>
	  </td>
	  <td>
	    <button type='button' id='ByNumber<?php print $rownum; ?>'>
			Number
	    </button>
	    <input type='hidden' name='First<?php print $rownum; ?>'
					id='First<?php print $rownum; ?>'
					value='<?php print $low; ?>'>
	  </td>
<?php
			}	// show button to display registrations in order
			else
			{	// show county details button
?>
	  <td>
	    <button type='button' id='TownStats<?php print $rownum; ?>'>
			Details
	    </button>
	  </td>
<?php
			}	// show county details button
?>
</tr>
<?php
	    }		// process all rows
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
?>
  </tbody>
  <tfoot>
	<tr>
<?php
	if ($showTownship)
	{
?>
	  <td class='odd bold left first'>&nbsp;  </td>
	  <td class='odd bold left'>
	      Total
	  </td>
<?php
	}
	else
	{
?>
	  <td class='odd bold left first'>
	      Total
	  </td>
<?php
	}
?>
	  <td class='odd bold right'>
	      <?php print $total; ?>
	  </td>
	  <td class='odd bold right'>
	      <?php print $lowest; ?>
	  </td>
	  <td class='odd bold right'>
	      <?php print $highest; ?>
	  </td>
	  <td class='<?php print pctClass($pctDone); ?>'>
	      <?php print number_format($pctDone, 2); ?>% 
	  </td>
	    <td class='<?php print pctClass($pctLinked); ?>'>
	      <?php print number_format($pctLinked, 2); ?>% 
	    </td>
	    <td class='evenleft'>&nbsp;  </td>
	</tr>
  </tfoot>
</table>
  </form>
<?php
        showTrace();
}		// display results of query
?>
  </div> <!-- end of <div id='body'> -->
<div class='balloon' id='HelpTownStats'>
Click on this button to display a summary of the marriages transcribed
for the specific town or township.
</div>
<div class='balloon' id='HelpCounty'>
This field displays the name of the county where the
marriage was registered.
</div>
<div class='balloon' id='HelpTown'>
This field displays the name of the city, town, village, or township where the
marriage was registered.
</div>
<?php
pageBot();
?>
