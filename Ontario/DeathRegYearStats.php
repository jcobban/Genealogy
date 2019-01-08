<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegYearStats.php												*
 *																		*
 *  Display statistics about the transcription of birth registrations.	*
 *																		*
 *  Parameters:															*
 *		regyear			registration year								*
 *																		*
 *  History:															*
 *		2011/03/16		created											*
 *		2011/11/05		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *						change name of help page						*
 *		2012/06/23		add support for linking statistics				*
 *		2013/08/04		use pageTop and pageBot to standardize appearance*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *		2014/01/14		move pctClass function to common.inc			*
 *						improve parameter handling						*
 *						add support for regDomain parameter				*
 *						use County class to expand county name			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/10/26		add information on lowest and highest regnum	*
 *						and percentage transcribed to display			*
 *		2016/04/25		replace ereg with preg_match					*
 *						support reporting single county					*
 *						support county level summary					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/07		use class Country								*
 *		2017/09/12		use get( and set(								*
 *		2017/10/30		use composite cell style classes				*
 *		2018/06/01		add support for lang parameter					*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$regYear		= '';
$cc			    = 'CA`';
$countryName	= 'Canada';
$domain		    = 'CAON';	// default domain
$domainName		= 'Ontario';
$county		    = null;
$showTownship	= false;
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// process specific named parameters
	    case 'regyear':
	    {
			$regYear	= $value;
			if (!preg_match("/^([0-9]{4})$/", $regYear) ||
			    ($regYear < 1860) || ($regYear > 2000))
			{
			    $msg	.= "RegYear $regYear must be a number between 1869 and 2000. ";
			}
			break;
	    }		// RegYear passed

	    case 'regdomain':
	    {
			$domainObj	= new Domain(array('domain'	=> $value,
		        						   'language'	=> 'en'));
			if ($domainObj->isExisting())
			{
			    $domain		= $value;
			    $cc			= substr($domain, 0, 2);
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->getName();
			    $domainName		= $domainObj->get('name');
			}
			else
			{
			    $msg	.= "Domain '$value' must be a supported two character country code followed by a two character state or province code. ";
			    $domainName	= 'Domain : ' . $value;
			}
			break;
	    }		// RegDomain

	    case 'county':
	    case 'regcounty':
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
			    $lang		= strtolower($value);
			break;
	    }		//lang

	    case 'debug':
	    {
			break;
	    }

	    default:
	    {
			$msg	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if ($regYear == '')
{
	$msg		.= "RegYear omitted. ";
}

if (strlen($msg) == 0)
{			// no errors
	$title		= $domainName;
	if (!is_null($county))
	    $title	.= ' ' . $countyName;
	$title		.= ": Death Registration Status $regYear";

	// execute the query
	if (is_null($county))
	    $query	= "SELECT D_RegCounty, " .
							"SUM(D_Surname != '') AS SurnameCount,  " .
							"SUM(D_Idir != 0) AS LinkCount, " .
							"MIN(D_RegNum) as low, " .
							"MAX(D_RegNum) as high  " .
						"FROM Deaths " .
						"WHERE D_RegDomain='$domain' AND D_RegYear=$regYear " .
						"GROUP BY D_RegCounty " .
						"ORDER BY D_RegCounty";
	else
	    $query	= "SELECT D_RegCounty, D_RegTownship, " .
							"SUM(D_Surname != '') AS SurnameCount,  " .
							"SUM(D_Idir != 0) AS LinkCount, " .
							"MIN(D_RegNum) as low, " .
							"MAX(D_RegNum) as high  " .
						"FROM Deaths " .
						"WHERE D_RegDomain='$domain' AND D_RegYear=$regYear " .
							"AND D_RegCounty='$county'" .
						"GROUP BY D_RegCounty, D_RegTownship " .
						"ORDER BY D_RegCounty, D_RegTownship";
	$stmt	 	= $connection->query($query);
	if ($stmt)
	{		    // successful query
	    $result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
	}		    // successful query
	else
	{
	    $msg	.= "query '$query' failed: " .
						   print_r($connection->errorInfo(),true);
	}		    // query failed
}		        // ok
else
{		        // missing parameter
	$title		= $domainName.": Death Registration Status by Year";
}		        // missing parameter

htmlHeader($title,
			array('/jscripts/util.js',
			      'DeathRegYearStats.js'));
$crumbs	= array(
				'/genealogy.php?lang=$lang'		=> 'Genealogy', 
				"/genCountry.php?cc=CA&lang=$lang"	=> $countryName,
				"/Canada/genProvince.php?domain=$domain&lang=$lang"
												=> $domainName,
				"DeathRegQuery.php?RegDomain=$domain&lang=$lang"
												=> 'New Death Query',
				"DeathRegStats.php?RegDomain=$domain&lang=$lang"
												=> 'Status');
if (!is_null($county))
			$crumbs["DeathRegYearStats.php?RegDomain=$domain&RegYear=$regYear&lang=$lang"] =
										   "County Status $regYear";
?>
    <body>
<?php
pageTop($crumbs);
?>
    <div class='body'>
      <h1><?php print $title; ?>
        <span class='right'>
			<a href='DeathRegYearStatsHelpen.html' target='_blank'>Help?</a>
        </span>
      </h1>
<?php
showTrace();
if (strlen($msg) > 0)
{		// print error messages if any
			print "<p class='message'>$msg</p>\n";
}		// print error messages if any
else
{		// display results of query
?>
      <div class='center'>
        <div class='left'>
          <a href='DeathRegYearStats.php?regyear=<?php print $regYear - 1; ?>&county=<?php print $county; ?>&lang=<?php print $lang; ?>' id='toPrevYear'>
			    &lt;--- <?php print $regYear - 1; ?> 
			</a>
        </div>
        <div class='right'>
			<a href='DeathRegYearStats.php?regyear=<?php print $regYear + 1; ?>&county=<?php print $county; ?>&lang=<?php print $lang; ?>' id='toNextYear'>
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
        <table class='form'>
          <!--- Put out the column headers -->
          <thead>
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
			  <th class='colhead'>
			  View
			  </th>
			</tr>
  </thead>
  <tbody>
  <?php
			    $total		= 0;
			    $totalLinked	= 0;
			    $rownum		= 0;
			    $countyObj		= null;
			    $countyName		= '';
			    $lowest		= PHP_INT_MAX;
			    $highest		= 0;
			    foreach($result as $row)
			    {
						$rownum++;
						$county		= $row['d_regcounty'];
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
								$warn	.= "<p class='message'>" . $e->getMessage() .
									"</p>\n";
						    $countyName		= $county;
						}
						if (array_key_exists('d_regtownship', $row))
						    $township	= $row['d_regtownship'];
						$count		= $row['surnamecount'];
						$total		+= $count;
						$linked		= $row['linkcount'];
						if ($count == 0)
						    $pctLinked	= 0;
						else
						    $pctLinked	= 100 * $linked / $count;
						$totalLinked	+= $linked;
						$low		= $row['low'];
						$high		= $row['high'];
						if ($low < $lowest)
						    $lowest	= $low;
						if ($high > $highest)
						    $highest	= $high;
						$todo		= $high - $low + 1;
						if ($todo == 0)
						    $pctDone	= 0;
						else
						    $pctDone	= 100 * $count / $todo;
						$count		= number_format($count, 0, '.', ',');
  ?>
			<tr>
			  <td class='odd bold left first'>
			      <?php print $countyName; ?>
			      <input type='hidden' id='County<?php print $rownum; ?>' 
						  value='<?php print $county; ?>'>
			  </td>
<?php
			if (array_key_exists('d_regtownship', $row))
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
			  <td>
			      <button type='button' id='TownStats<?php print $rownum; ?>'>
						  View
			      </button>
			  </td>
			</tr>
  <?php
			    }		// process all rows
  
			    if ($total == 0)
						  $pctLinked	= 0;
			    else
						  $pctLinked	= 100 * $totalLinked / $total;
			    if (strlen($total) > 3)
						  $total	= substr($total, 0, strlen($total) - 3) . ',' .
						  	  substr($total, strlen($total) - 3);
			    $totalToDo		= $highest - $lowest + 1;
			    if ($totalToDo == 0)
						$pctDone	= 0;
			    else
						$pctDone	= 100 * $total / $totalToDo;
  ?>
          </tbody>
          <tfoot>
			<tr>
<?php
			if ($showTownship)
			{
?>
			  <td class='total left first'>&nbsp;</td>
			  <td class='total bold left'>
			      Total
			  </td>
<?php
			}
			else
			{
?>
			  <td class='total left first'>
			      Total
			  </td>
<?php
			}
?>
			  <td class='total right'>
			      <?php print $total; ?>
			  </td>
			  <td class='total right'>
			      <?php print $lowest; ?>
			  </td>
			  <td class='total right'>
			      <?php print $highest; ?>
			  </td>
			  <td class='total <?php print pctClass($pctDone); ?>'>
			      <?php print number_format($pctDone, 2); ?>% 
			  </td>
			  <td class='total <?php print pctClass($pctLinked); ?>'>
			      <?php print number_format($pctLinked, 2); ?>% 
			  </td>
			  <td class='total left'>&nbsp;</td>
			</tr>
          </tfoot>
        </table>
      </form>
<?php
}		// display results of query
?>
    </div> <!-- end of <div id='body'> -->
<?php
pageBot();
?>
    <div class='balloon' id='HelpTownStats'>
        Click on this button to display a summary of the deaths transcribed
        for the specific town or township.
    </div>
    <div class='balloon' id='HelpCounty'>
      This field displays the name of the county where the
     death was registered.
    </div>
    <div class='balloon' id='HelpTown'>
      This field displays the name of the city, town, village, or township where the
      death was registered.
    </div>
  </body>
</html>
