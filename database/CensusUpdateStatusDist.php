<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusUpdateStatusDist.php											*
 *																		*
 *  Display the progress of the transcription of a specific census		*
 *  district.															*
 *																		*
 *  History:															*
 *		2010/09/10		Reformat to new page layout.					*
 *		2010/11/19		use common MDB2 connection resource				*
 *						increase separation of HTML and PHP				*
 *						improve parameter validation					*
 *		2011/05/18		use CSS layout instead of tables				*
 *		2011/09/11		minor change to debug output					*
 *		2011/09/25		display previous district only if there is one	*
 *						display next district only if there is one		*
 *						districts do not have to have integer numbers	*
 *						also display name of previous and next district	*
 *						correct URL for update/query form in header and *
 *						trailer											*
 *						report all divisions of the district, not just	*
 *						those with non-zero stats						*
 *						improve separation of PHP and HTML				*
 *		2011/12/09		improve parameter validation					*
 *		2012/01/26		add Copy button for uploading to server			*
 *		2012/04/27		add help balloons for subdistrict and			*
 *						division ids									*
 *		2012/09/17		Census parameter changed to identifier from year*
 *		2012/10/11		correct search page URL							*
 *		2012/12/31		add percentage linked							*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *						variable $linked not initialized				*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2013/04/20		avoid divide by zero							*
 *		2013/05/23		add option to display surnames by division		*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/07/06		correct URL for requesting next page to			*
 *						edit/view										*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/30		use CSS for layout								*
 *		2014/01/14		move function pctClass to common.inc			*
 *						use common appearance for status tables			*
 *		2014/02/19		correct URL for query							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/08		use common functions from CommonForm.js			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/21		get census info from Census class				*
 *		2017/09/12		use get( and set(								*
 *		2017/10/30		use composite cell style classes				*
 *		2017/11/24		use prepared statements							*
 *						use District class								*
 *		2018/01/18		tolerate lang parameter							*
 *		2018/02/23		accept being invoked with CA1851 or CA1861 plus	*
 *						Province parameter								*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values for parameters
$censusId		= '';
$censusYear		= null;
$distId		= '';
$province		= 'CW';
$lang		= 'en';

// validate parameters
foreach ($_GET as $key => $value)
{			// loop through all parameters
	switch(strtolower($key))
	{		// act on parameter name
	    case 'census':
	    case 'censusid':
	    {		// Census identifier
			$censusId	= $value;
			$censusRec	= new Census(array('censusid'	=> $censusId));
			if ($censusRec->isExisting())
			{
			    $censusYear	= intval(substr($censusId, 2));
			    if ($censusYear < 1867)
					if ($censusRec->get('collective') == 0)
					    $province	= substr($censusId, 0, 2);
					else
					    $warn	.= "<p>censusId='$censusId' is not the census of a specific colony</p>\n";
			}
			else
			{
			    $msg	.= "Invalid Census identifier '$censusId'. ";
			}
			break;
	    }		// Census identifier

	    case 'district':
	    {		// district number
			$distId		= $value;
			$result		= array();
			if (preg_match("/^([0-9]+)(\.[05])?$/", $distId, $result) != 1)
			    $msg	.= "District value '$distId' is invalid. ";
			else
			{
			    if (count($result) > 2 && $result[2] == '.0')
					$distId	= $result[1];	// integral portion only
			}
			break;
	    }		// District number

	    case 'province':
	    {		// province code deprecated
			if (strlen($value) == 2 && $censusRec->get('collective') == 1)
			{
			    $province		= strtoupper($value);
			    $censusId		= $province . $censusYear;
			    $censusRec	= new Census(array('censusid'	=> $censusId));
			    $warn		.= "<p>CensusId corrected to '$censusId'</p>\n";
			}
			break;
	    }		// province code 

	    case 'lang':
	    {		// language
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
			break;
	    }		// language

	    case 'debug':
	    {		// debug handled by common
			break;
	    }		// debug handled by common

	    default:
	    {	// unexpected parameter
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }	// unexpected parameter
	}	// act on parameter name
}		// Census present

// check for missing parameters
if (is_null($censusId))
{		// Census missing
	$censusId	= '';
	$msg		.= 'Census parameter missing. ';
}		// Census missing

if ($distId == '')
{		// District missing
	$msg		.= 'District parameter missing. ';
}		// District missing

// some actions depend upon whether the user can edit the database
if (canUser('edit'))
{		// user can update database
	$searchPage	= "ReqUpdate.php?Census=$censusId&District=$distId";
	$action		= 'Update';
}		// user can updated database
else
{		// user can only view database
	$searchPage	= "QueryDetail$censusYear.html?District=$distId";
	$action		= 'Query';
}		// user can only view database

// initial defaults reset from database

// if no errors execute the query
if (strlen($msg) == 0)
{		// no errors so far
	// get the current district
	$district	= new District(array('census'	=> $censusId,
							     'id'	=> $distId));
	$distName	= $district->get('name');
	$province	= $district->get('province');

	// get the total population of the district
	if ($district->isExisting())
	{		// district id is valid
	    $prevDistrict	= $district->getPrev();
	    $nextDistrict	= $district->getNext();
	    $prevDist		= $prevDistrict->get('id');
	    $prevDistName	= $prevDistrict->get('name');
	    $prevCensusId	= $prevDistrict->get('census');
	    $nextDist		= $nextDistrict->get('id');
	    $nextDistName	= $nextDistrict->get('name');
	    $nextCensusId	= $nextDistrict->get('census');
	    $pop		= $district->get('d_population');

	    // execute a query that includes divisions with no transcription
	    if ($censusYear > 1901 || $censusYear < 1867)
			$qryAllOrder	= "LPAD(SD_Id,3,'00'), LPAD(SD_Div,3,'00')";
	    else
			$qryAllOrder	= "SD_Id, LPAD(SD_Div,3,'00')";

	    if ($censusYear < 1867)
			$qryAll	= "SELECT SD_DistID, SD_ID, SD_Div," .
					    "SD_Name, SD_Population," .
					    "(SELECT SUM(GivenNames != '') " . 
					    "FROM Census$censusYear WHERE " .
						    "Province='$province' AND " .	
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    " AS NameCount," . 
					    "(SELECT SUM(Age != '') " .
					    "FROM Census$censusYear WHERE " .
						    "Province='$province' AND " .	
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    "AS AgeCount," .
					    "(SELECT SUM(IDIR != 0) " .
					    "FROM Census$censusYear WHERE " .
						    "Province='$province' AND " .	
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    "AS IDIRCount " .
					    "FROM SubDistricts " .
					    "WHERE SD_Census=:censusId AND SD_DistId=:distId " .
					    "ORDER BY $qryAllOrder";
	    else
			$qryAll	= "SELECT SD_DistID, SD_ID, SD_Div," .
					    "SD_Name, SD_Population," .
					    "(SELECT SUM(GivenNames != '') " . 
					    "FROM Census$censusYear WHERE " .
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    " AS NameCount," . 
					    "(SELECT SUM(Age != '') " .
					    "FROM Census$censusYear WHERE " .
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    "AS AgeCount," .
					    "(SELECT SUM(IDIR != 0) " .
					    "FROM Census$censusYear WHERE " .
						    "District=SD_DistId AND " .
						    "SubDistrict=SD_Id AND Division=SD_Div) " .
						    "AS IDIRCount " .
					    "FROM SubDistricts " .
					    "WHERE SD_Census=:censusId AND SD_DistId=:distId " .
					    "ORDER BY $qryAllOrder";
	    $sqlParms	= array('censusId'	=> $censusId,
						'distId'	=> $distId);
	    $stmt	= $connection->prepare($qryAll);
	    $qryAllText	= debugPrepQuery($qryAll, $sqlParms);
	    if ($stmt->execute($sqlParms))
	    {		// successful query
			if ($debug)
			    $warn	.= "<p>CensusUpdateStatusDist.php" . __LINE__ .
					" $qryAllText</p>\n";
			$resAll		= $stmt->fetchAll(PDO::FETCH_ASSOC);
	    }		// successful query
	    else
	    {		// error on request
			$msg	.= "'$qryAllText': " .
					   print_r($stmt->errorInfo(), true);
	    }		// error on request
	}		// district is valid
	else
	{		// no matching district in Districts table
	    $msg		.= "District $distId not defined for the $censusId census. ";
	    $prevDistrict	= null;
	    $nextDistrict	= null;
	    $prevDist		= 0;
	    $prevDistName	= 'Unknown';
	    $prevCensusId	= $censusId;
	    $nextDist		= 0;
	    $nextDistName	= 'Unknown';
	    $nextCensusId	= $censusId;
	    $pop		= 0;
	}		// no matching district in Districts table
}		// no errors
else
	$distName	= 'Unknown';


if (isset($censusRec))
	$title	= $censusRec->get('name') .
			  " District $distId $distName Status";
else
	$title	= "$censusId District $distId $distName Status";

htmlHeader($title,
			array('/jscripts/util.js',
					'/jscripts/CommonForm.js',
					'/jscripts/js20/http.js',
					'CensusUpdateStatusDist.js'));
?>
<body>
<?php
pageTop(array(
			"/genealogy.php"	=> "Genealogy",
			"/genCanada.html"	=> "Canada",
			"/genCensuses.php"	=> "Censuses",
			"/database/$searchPage"	=> "$action $censusYear Census", 
			"/database/CensusUpdateStatus.php?Census=$censusId&Province=$province"
							=> "$censusYear Census Status"));
?>	
<div class='body'>
  <h1><?php print $title; ?> 
  <span class='right'>
	<a href='CensusUpdateStatusDistHelpen.html' target='help'>? Help</a>
  </span>
<div style='clear: both;'></div>
  </h1>
<?php
	showTrace();
	if (strlen($msg) > 0)
	{		// error, suppress function
?>	
<p class='message'>
	<?php print $msg; ?>	
</p>
<?php
	}		// error, suppress function
	else
	{		// no errors
?>	
  <!--- Put out a line with links to previous and next section of table -->
  <div class='center'>
<?php
	if ($prevDist > 0)
	{		// is a previous district
?>
  <div class='left'>
	<a href='CensusUpdateStatusDist.php?Census=<?php print $prevCensusId; ?>&District=<?php print $prevDist; ?>' id='toPrevDist'>
	    &lt;--- district <?php print $prevDist; ?> 
			<?php print $prevDistName; ?>
	</a>
  </div>
<?php
	}		// is a previous district
?>
<?php
	if ($nextDist > 0)
	{		// is a next district
?>
  <div class='right'>
	<a href='CensusUpdateStatusDist.php?Census=<?php print $nextCensusId; ?>&District=<?php print $nextDist; ?>' id='toNextDist'>
	    district <?php print $nextDist; ?>
			<?php print $nextDistName; ?> ---&gt;
	</a>
  </div>
<?php
	}		// is a next district
?>
&nbsp;
<div style='clear: both;'></div>
  </div>

  <!--- Put out the response as a table -->
  <form id='statForm' action='donothing.php'>
<div id='hidden'>
  <input type='hidden' id='Census' value='<?php print $censusId ; ?>'>
  <input type='hidden' id='District' value='<?php print $distId ; ?>'>
  <input type='hidden' id='DistName' value='<?php print $distName ; ?>'>
</div>
<table class='form'>
  <!--- Put out the column headers -->
  <thead>
   <tr>
	 <th class='colhead1st'>
	     SubDist
	 </th>
	 <th class='colhead'>
	     Div
	 </th>
	 <th class='colhead'>
	     Name
	 </th>
	 <th class='colhead'>
	     Done
	 </th>
	 <th class='colhead'>
	     Pop.
	 </th>
	 <th class='colhead'>
	     %Done
	 </th>
	 <th class='colhead'>
	     %Linked
	 </th>
	 <th class='colhead' colspan='2'>
	     Action
	 </th>
   </tr>
  </thead>
  <tbody>
<?php
// display the results
$even		= false;
$done		= 0;
$linked		= 0;
$ir			= 0;

foreach($resAll as $row)
{
	$ir++;
	$District	= $row['sd_distid'];
	$SubDistrict	= $row['sd_id']; 
	$Division	= $row['sd_div'];
	$SD_Name	= $row['sd_name'];
	$SD_Population	= $row['sd_population'];
	$NameCount	= $row['namecount'];
	if (is_null($NameCount))
	    $NameCount	= 0; 
	$AgeCount	= $row['agecount'];
	if (is_null($AgeCount))
	    $AgeCount	= 0; 
	$IDIRCount	= $row['idircount'];
	if (is_null($IDIRCount))
	    $IDIRCount	= 0; 
	    if ($SD_Population > 0)
 	    {	// division exists in original images
 		$pct	= ($NameCount + $AgeCount)*50/$SD_Population;
 		$pctl	= $IDIRCount*100/$SD_Population;
 	    }	// division exists in original images
 	    else
 	    {	// division missing
 		$pct	= 100;
 		$pctl	= 100;
 	    }	// division missing
 
?>
   <tr>
	 <td class='odd bold right first'>
 	    <?php print $SubDistrict; ?> 
 	    <input type='hidden' id='SdId<?php print $ir; ?>'
 		    value='<?php print $SubDistrict; ?>'>
	 </td>
	 <td class='odd bold right'>
 	    <?php print $Division; ?> 
 	    <input type='hidden' id='Div<?php print $ir; ?>'
 		    value='<?php print $Division; ?>'> 
	 </td>
	 <td class='odd bold left'>
 	    <?php print htmlspecialchars($SD_Name); ?> 
	 </td>
	 <td class='odd bold right'>
 	    <?php print number_format($NameCount); ?> 
	 </td>
	 <td class='odd bold right'>
 	    <?php print number_format($SD_Population); ?> 
	 </td>
	 <td class='<?php print pctClass($pct); ?>'>
 	    <?php print number_format($pct, 2); ?> 
	 </td>
	 <td class='<?php print pctClass($pctl); ?>'>
 	    <?php print number_format($pctl, 2); ?> 
	 </td>
	 <td class='center'>
 	    <button type='button' id='Edit<?php print $ir; ?>'>
 		Details
 	    </button>
	 </td>
	 <td class='center'>
 	    <button type='button' id='Surnames<?php print $ir; ?>'>
 		Surnames
 	    </button>
	 </td>
   </tr>
<?php
 	$done	+= intval($NameCount);
 	$linked	+= intval($IDIRCount);
}		// process all rows

// summary line
if ($pop > 0)
{
	$pct	= $done*100/$pop;
	$pctl	= $linked*100/$pop;
}
else
{
	$pct	= 0;
	$pctl	= 0;
}
?>
  </tbody>
  <tfoot>
   <tr>
	 <td class='odd bold right first'>
 	    &nbsp;
	 </td>
	 <td class='odd bold left'>
 	    Total
	 </td>
	 <td class='odd bold left'>
 	    &nbsp;
	 </td>
	 <td class='odd bold right'>
 	    <?php print number_format($done); ?> 
	 </td>
	 <td class='odd bold right'>
 	    <?php print number_format($pop); ?>
	 </td>
	 <td class='<?php print pctClass($pct); ?>'>
 	    <?php print number_format($pct, 2); ?> 
	 </td>
	 <td class='<?php print pctClass($pctl); ?>'>
 	    <?php print number_format($pctl, 2); ?> 
	 </td>
   </tr>
  </tfoot>
</table>
  </form>

  <!--- Put out a line with links to previous and next section of table -->
  <div class='center'>
<?php
	if ($prevDist > 0)
	{		// is a previous district
?>
<div class='left'>
	<a href='CensusUpdateStatusDist.php?Census=<?php print $prevCensusId; ?>&District=<?php print $prevDist; ?>' id='btPrevDist'>
	    &lt;--- district <?php print $prevDist; ?> 
			<?php print $prevDistName; ?>
	</a>
</div>
<?php
	}		// is a previous district
	if ($nextDist > 0)
	{		// is a next district
?>
<div class='right'>
	<a href='CensusUpdateStatusDist.php?Census=<?php print $nextCensusId; ?>&District=<?php print $nextDist; ?>' id='btNextDist'>
	    district <?php print $nextDist; ?>
			<?php print $nextDistName; ?> ---&gt;
	</a>
</div>
<?php
	}		// is a next district
?>
&nbsp;
<div style='clear:both;'></div>
  </div>
<?php
	}		// no errors
?>
  </div> <!-- class='body' -->
<?php
pageBot();
?>
  <div id='mousetoPrevDist' class='popup'>
<p class='label'>Go to district <?php print $prevDist; ?>
			<?php print $prevDistName; ?>
</p>
  </div>
  <div id='mousetoNextDist' class='popup'>
<p class='label'>Go to district <?php print $nextDist; ?>
			<?php print $nextDistName; ?>
</p>
  </div>
  <div id='mousebtPrevDist' class='popup'>
<p class='label'>Go to district <?php print $prevDist; ?>
			<?php print $prevDistName; ?>
</p>
  </div>
  <div id='mousebtNextDist' class='popup'>
<p class='label'>Go to district <?php print $nextDist; ?>
			<?php print $nextDistName; ?>
</p>
  </div>
<div class='balloon' id='HelprightTop'>
Click on this button to signon to access extended features of the web-site
or to manage your account with the web-site.
</div>
<div class='balloon' id='HelpEdit'>
Click on this button to view detailed information about the page by page
status of the transcription for this division.
</div>
<div class='balloon' id='HelpSurnames'>
Click on this button to view a summary of the surnames that are present in
the transcription for this division.
</div>
<div class='balloon' id='HelpCopy'>
Click on this button to upload the transcription data and page descriptions
from the development server to the production server.
</div>
<div class='balloon' id='HelpSdId'>
This cell displays the sub-district identifier within the district.
</div>
<div class='balloon' id='HelpDiv'>
This cell displays the division identifier within the sub-district
if applicable.
</div>
<div class='popup' id='loading'>
Uploading Division Data
</div>
</body>
</html>
