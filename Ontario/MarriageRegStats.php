<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegStats.php												*
 *																		*
 *  Display the status of the transcription of marriage registrations.	*
 *																		*
 *  Parameters:															*
 *		RegDomain		domain consisting of country code and state		*
 *																		*
 *  History:															*
 *		2011/01/09		created											*
 *		2011/03/14		display breakdown by township					*
 *		2011/03/16		put Help URL in standard location				*
 *						include rightTop button							*
 *						display in 3 columns							*
 *		2011/10/27		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/13		use CSS for table style							*
 *		2014/03/06		link to help misplaced on page					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2018/01/01		add language parameter							*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$domain		= 'CAON';	// default domain
$cc			= 'CA';
$countryName	= 'Canada';
$domainName		= 'Canada: Ontario:';
$stateName		= 'Ontario';
$lang		= 'en';

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
	    case 'domain':
	    case 'regdomain':
	    {
			$domain		= $value;
			$cc		= strtoupper(substr($domain, 0, 2));
			$country	= new Country(array('code'	=> $cc));
			$countryName	= $country->getName();
			$domainObj	= new Domain(array('domain'	=> $domain,
								   'language'	=> 'en'));
			if (!$domainObj->isExisting())
			{
			    $msg	.= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
			}
			$domainName	= $domainObj->getName(1);
			$stateName	= $domainObj->getName(0);
			break;
	    }		// RegDomain

	    case 'lang':
	    {
			if (strlen($value) == 2)
			    $lang	= strtolower($value);    	
			break;
	    }		// any other paramters

	    case 'debug':
	    {
			break;
	    }		// handled by common code

	    default:
	    {
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT M_RegYear, SUM(M_Date != '') FROM Marriage
					    WHERE M_RegDomain='$domain'
					    GROUP BY M_RegYear ORDER BY M_RegYear";
	$stmt	 	= $connection->query($query);
	if ($stmt)
	{		// successful query
	    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
	    {		// debug output
			print "<p>$query</p>\n";
	    }		// debug output
	}		// successful query
	else
	{
	    $msg	.= "query '$query' failed: " .
					   print_r($connection->errorInfo(),true);
	}		// query failed
}			// no errors

$title	= $domainName . " Marriage Registration Status";
htmlHeader($title,
			array('/jscripts/util.js',
			      'MarriageRegStats.js'));
?>
<body>
<?php
pageTop(array(
			"/genealogy.php?lang=$lang"
					=> 'Genealogy',
			"/genCountry.php?cc=$cc&lang=$lang"
							=> $countryName,
			"/Canada/genProvince.php?domain=$domain&lang=$lang"
							=> $stateName,
			"MarriageRegQuery.php?lang=$lang"
							=> 'New Marriage Query'));
?>
  <div class='body'>
<h1>
  <span class='right'>
	<a href='MarriageRegStatsHelpen.html' target='_blank'>Help?</a>
  </span>
  <?php print $title; ?></h1>
</h1>
<?php
	if (strlen($warn) > 0)
	{		// print warning messages if any
	    print "<p class='warning'>$warn</p>\n";
	}		// print warning messages if any

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
 <form id='display' action='donothing.php' method='get'>
<!--- Put out the response as a table -->
  <table class='details'>
<!--- Put out the column headers -->
<tr>
	<th class='colhead'>
	RegYear
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
	<th>
	</th>
	<th class='colhead'>
	RegYear
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
	<th>
	</th>
	<th class='colhead'>
	RegYear
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
</tr>
<?php
	    $columns		= 3;
	    $col		= 1;
	    $total		= 0;
	    $rownum		= 0;
	    $yearclass		= "odd";
	    foreach($result as $row)
	    {
			$rownum++;
			$regYear	= $row[0];
			$count		= $row[1];
			$total		+= $count;
			if (strlen($count) > 3)
			    $count	= substr($count, 0, strlen($count) - 3) . ',' .
						  substr($count, strlen($count) - 3);
			if ($col == 1)
			{
?>
<tr>
<?php
			}
?>
	<td class='<?php print $yearclass; ?> right'>
	    <?php print $regYear; ?>
	    <input type='hidden' id='RegYear<?php print $rownum; ?>'
			value='<?php print $regYear; ?>'>
	</td>
	<td class='<?php print $yearclass; ?> right'>
	    <?php print $count; ?>
	</td>
	<td class='left'>
	    <button type='button' id='YearStats<?php print $rownum; ?>'>
			View
	    </button>
	</td>
<?php
			$col++;
			if ($col > $columns)
			{	// at column limit, end row
			    $col	= 1;
			    if ($yearclass == "odd")
					$yearclass	= "even";
			    else
					$yearclass	= "odd";
?>
</tr>
<?php
			}	// at column limit, end row
			else
			{	// start new column
?>
	<td>
	</td>
<?php
			}	// start new column
	    }		// process all rows

	    // end last row if necessary
	    if ($col != 1)
	    {		// partial last column
?>
</tr>
<?php
	    }		// partial last column

	    // insert comma into formatting of total
	    if (strlen($total) > 3)
			$total	= substr($total, 0, strlen($total) - 3) . ',' .
					  substr($total, strlen($total) - 3);
?>
<tr>
	<td class='right'>
	    Total
	</td>
	<td class='dataright'>
	    <?php print $total; ?>
	</td>
</tr>
  </table>
 </form>
<?php
	}		// display results of query
?>
 </div>
<?php
pageBot();
?>
<div class='balloon' id='HelpYearStats'>
Click on this button to display the geographical breakdown of the
transcription for the specific year.
</div>
<div class='balloon' id='HelpRegYear'>
This field shows the year for which the statistics apply.
</div>
</body>
</html>
