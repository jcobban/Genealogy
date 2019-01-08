<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageVolumeSummary.php										*
 *																		*
 *  Display information about those volumes of pre-confederation		*
 *  marriages that have been partially transcribed.						*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain	2 letter country code + 2 letter state/province code	*
 *																		*
 *  History:															*
 *		2017/07/15		created											*
 *		2017/07/18		use Canada West instead of Ontario				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/common.inc";

// defaults
$domain		= 'CAUC';
$prov		= 'UC';
$province		= 'Upper Canada';
$cc			= 'CA';
$countryName	= 'Canada';
$volume		= null;
$reportNo		= null;
$report		= null;		// instance of CountyMarriageReport
$offset		= null;
$limit		= null;

// validate parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{
	    case 'prov':
	    {
			$prov		= $value;
			$domain		= 'CA' . $value;
			$domainObj	= new Domain(array('domain'	=> $domain,
							       'language'	=> 'en'));
			if ($domainObj->isExisting())
			    $province	= $domainObj->get('name');
			else
			{
			    $msg		.= "Prov='$value' unsupported. ";
			    $province	= 'Unknown';
			}
			break;
	    }		// state/province code

	    case 'domain':
	    case 'regdomain':
	    {
			$domain		= $value;
			$cc		= substr($value, 0, 2);
			$prov		= substr($value, 2, 2);
			$domainObj	= new Domain(array('domain'	=> $domain,
								   'language'	=> 'en'));
			if ($domainObj->isExisting())
			{
			    $province	= $domainObj->get('name');
			}
			else
			{
			    $msg	.= "Domain='$value' unsupported. ";
			    $province	= 'Unknown';
			}
			$countryObj	= new Country(array('code' => $cc));
			$countryName	= $countryObj->getName();
			break;
	    }		// state/province code

	    case 'debug':
	    {
			break;
	    }		// debug handled by common code

	    default:
	    {
			if (strlen($value) > 0)
			    $warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }
	}		// check supported parameters
}		// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (strlen($msg) == 0)
{		// no errors detected
	// execute the query to get the contents of the page
	$query	= 'SELECT M_Volume, COUNT(M_ItemNo)/2 AS Number ' .
					'FROM CountyMarriages ' .
					"WHERE M_RegDomain='$domain' " .
					'GROUP BY M_Volume ' .
					'ORDER BY M_Volume';
	$stmt	= $connection->query($query);
	if ($stmt)
	{		// successful
	    $results	= $stmt->fetchAll(PDO::FETCH_ASSOC);
	}		// successful
	else
	{		// error retrieving records
	    throw new Exception(
			    "CountyMarriageVolumeSummary: '$query' result=" . 
					       print_r($connection->errorInfo(), true));
	}		// error retrieving records
}		// no errors detected

$title	= "$countryName: $province: Volume Summary";
htmlHeader($title,
	       array(	'/jscripts/CommonForm.js',
					'/jscripts/js20/http.js',
					'/jscripts/util.js',
					'CountyMarriageVolumeSummary.js'));
?>
<body>
<?php
pageTop(array(
			'/genealogy.php'		=> 'Genealogy',
			"/genCountry.php?cc=$cc"	=> $countryName,
			'/Canada/genProvince.php?Domain=CAON'
								=> $province,
			"/Ontario/CountyMarriageEditQuery.php"
								=> 'County Marriage Query'));
?>
<div class='body'>
  <h1>
<span class='right'>
	<a href='CountyMarriageVolumeSummaryHelpen.html' target='help'>? Help</a>
</span>
	<?php print $title; ?>
<div style='clear: both;'></div>
  </h1>
<?php
showTrace();

if (strlen($msg) == 0)
{
	// notify the invoker if they are not authorized
	if (!canUser('edit'))
	{
?>
  <p class='warning'>
	You are not authorized.
	<a href='/Signon.php' target='_blank'>
	<span class='button'>Sign on</span></a>
	to update the database.
  </p>
<?php
	    $readonly	= "readonly='readonly'";
	    $disabled	= "disabled='disabled'";
	    $codeclass	= 'ina code';
	    $textclass	= 'ina left';
	    $numclass	= 'ina right';
	}		// not authorized to update database
	else
	{		// authorized to update database
	    $readonly	= '';
	    $disabled	= '';
	    $codeclass	= 'white code';
	    $textclass	= 'white left';
	    $numclass	= 'white rightnc';
	}		// authorized to update database

?>
<!--- Put out the response as a table -->
<form name="volumeForm"
	action="CountyMarriageVolumeSummary.php" 
	method="post" 
	autocomplete="off" 
	enctype="multipart/form-data">
  <input type="hidden" name="Domain" value="<?php print $domain; ?>">
<?php
	if ($debug)
	{
?>
  <input type="hidden" name="Debug" value="Y">
<?php
	}		// debug enabled
?>
  <table class="form" id="dataTbl">
<!--- Put out the column headers -->
<thead>
  <tr id="hdrRow">
	<th class="colhead">
	  Volume
	</th>
	<th class="colhead">
	  Transcribed
	</th>
	<th class="colhead">
	  Details
	</th>
  </tr>
</thead>
<tbody>
<?php
	// display the results
	$totalTranscribed	= 0;
	foreach($results as $report)
	{
	    $volume		= $report['m_volume'];
	    $transcribed	= floor($report['number']);
	    $totalTranscribed	+= $transcribed;
			
?>
  <tr id="Row<?php print $volume; ?>">
	<td class="right">
	    <input type="text" name="Volume<?php print $volume; ?>"
					value="<?php print $volume; ?>"
					class="<?php print $numclass; ?>"
					size="4" maxlength="4" readonly="readonly">
	</td>
	<td class="right">
	    <input type="text" name="Transcribed<?php print $volume; ?>"
					value="<?php print number_format($transcribed); ?>"
					class="<?php print $numclass; ?>"
					readonly="readonly" disabled="disabled"
					size="8">
	</td>
	<td class="center" style="white-space: nowrap;">
	    <button type="button" id="Details<?php print $volume; ?>">
			Details
	    </button> 
	</td>
  </tr>
<?php
	}		// process all rows
?>
  <tr id="RowTotals">
	<td class="total right">
	    <input type="text" name="VolumeTotal"
					value="Total"
					class="<?php print $numclass; ?>"
					size="4" maxlength="4" readonly="readonly">
	</td>
	<td class="total right">
	    <input type="text" name="TranscribedTotal" 
					value="<?php print number_format($totalTranscribed); ?>"
					class="<?php print $numclass; ?>"
					readonly="readonly" disabled="disabled"
					size="8">
	</td>
	<td class="total center" style="white-space: nowrap;">
	    &nbsp;
	</td>
  </tr>
</tbody>
  </table>
</form>
  <h2>Also see:
  </h2>
  <p>
<a href="http://www.archives.gov.on.ca/en/microfilm/v_mdistrict_t.aspx"
	target="_blank">District Marriage Registers - 1780-1858</a>
  </p>
  <p>
<a href="http://www.archives.gov.on.ca/en/microfilm/v_mcounty_t.aspx"
	target="_blank">County Marriage Registers - 1858-1869</a>
  </p>
<?php
}		// there are no messages to display, do search
else
{		// print error messages
?>
<p class="message">
<?php print($msg); ?>
</p>
<?php
}		// print error messages
?>
  </div>
<?php
pageBot();
?>
<!-- The remainder of the page consists of context specific help text.
-->
<div class="balloon" id="HelpCode">
<p>This field contains a 3-character abbreviation for the county which
is used to refer to this record.
Except when adding a new county this is a read-only field.
</p>
</div>
<div class="balloon" id="HelpName">
<p>The name of the county.
</p>
</div>
<div class='balloon' id='HelpStartYear'>
<p>The year the county came into existence as an administrative unit.
For most counties this is 1852.
</p>
</div>
<div class='balloon' id='HelpEndYear'>
<p>The year the county ceased to exist.  For most counties this is 9999
indicating the county is still in existence, but, for example Carleton
County was merged into the City of Ottawa in the 1990s.
</p>
</div>
<div class='balloon' id='HelpDelete'>
<p>This button is used to delete a county record from the list.
</p>
</div>
<div class='balloon' id='HelpEditTownships'>
<p>This button is used to display a dialog for editing the lower level
administrative units of the county, that is the townships and towns. In most
cases some services, such as land and vital statistics registrations, are 
handled by the county for a city, so the city appears in this list as well.
</p>
</div>
<div class='balloon' id='HelpAdd'>
Click on this button to add another county into the list.
</div>
<div class='balloon' id='HelpSubmit'>
Click on this button to update the database to include the changes you
have made to the counties list for the current province.
</div>
<div class='balloon' id='HelprightTop'>
Click on this button to signon to access extended features of the web-site
or to manage your account with the web-site.
</div>
<div class='hidden' id='templates'>
</div>
</body>
</html>
