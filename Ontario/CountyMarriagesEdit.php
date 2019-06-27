<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriagesEdit.php												*
 *																		*
 *  Display form for editting information about marriages				*
 *  within a report submitted by a minister of religion to report		*
 *  marriages he performed during a year prior to confederation.		*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain		2 letter country code + 2 letter province code		*
 *		Volume		volume number										*
 *		ReportNo	report number										*
 *																		*
 *  History:															*
 *		2016/01/29		created											*
 *		2016/03/18		after update ensure groom before bride			*
 *		2016/03/19		construct selection list of possible matches	*
 *						in a popup menu									*
 *		2016/03/22		update IDIR										*
 *						include page number and link to image in header	*
 *						highlight links to tree							*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/10/23		add columns										*
 *		2017/01/13		add "Clear" button to remove linkage			*
 *						add templates for "Find", "Tree", and "Clear"	*
 *						buttons.										*
 *		2017/01/18		correct undefined $image						*
 *						replace setField with set						*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/02/07		use class Country								*
 *		2017/07/18		use Canada West instead of Ontario				*
 *		2017/09/12		use get( and set(								*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/CountyMarriage.inc";
require_once __NAMESPACE__ . "/CountyMarriageSet.inc";
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  compareReports														*
 *																		*
 *  Implement sorting order of instances of CountyMarriage				*
 *  This is required because PHP does not yet have a way to access		*
 *  a normal member function of a class to perform comparisons.			*
 ************************************************************************/
function compareReports(CountyMarriage $r1, 
        				CountyMarriage $r2)
{
    return $r1->compare($r2);
}

// validate parameters
$domain					= 'CACW';
$prov					= 'CW';
$province				= 'Canada West';
$cc						= 'CA';
$countryName			= 'Canada';
$image					= '';
$volume					= null;
$reportNo				= null;
$reportNoText			= '';
$itemNo					= null;
$role					= null;
$offset					= null;
$limit					= null;
$fixup					= true;

if (count($_POST) > 0)
{			// perform update
    $parmsText  = '<p class="label">\$_POST</p>\n' .
                  '<table class="summary">\n' .
                  '<tr><th class="colhead">key</th>' .
                      '<th class="colhead">value</th></tr>\n';
	$reports	    = array();
	$record		    = null;
	$domain		    = null;
	$create		    = false;
	$fixup		    = false;

	foreach($_POST as $key => $value)
	{               // loop through all update parameters
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$value</td></tr>\n"; 
	    if (preg_match("/^([a-zA-Z_]+)(\d*)$/", $key, $matches))
	    {
			$column	= $matches[1];
			$row	= $matches[2];
	    }
	    else
	    {
			$column	= $key;
			$row	= '';
	    }

	    switch(strtolower($column))
	    {		// act on specific parameters
			case 'domain':
			{	// Domain
			    $domain	= $value;
			    break;
			}	// Domain

			case 'volume':
			{	// volume
			    $volume	= $value;
			    break;
			}

			case 'reportno':
			{
			    $reportNoText	= $value;
			    if (is_int($value) ||
				    (strlen($value) > 0 && ctype_digit($value)))
			    {		// valid
		    		$reportNo	        	= $value;
		    		$getParms['reportno']	= $reportNo;
			    }		// valid
			    else
			    if (substr($value, -2) == '½' || floor($value) != $value)
			    {
		    		$reportNo	            = floor(substr($value, 0, strlen($value) - 2)) + 0.5;
		    		$reportNoText	        = floor($reportNo) . '½';
		    		$getParms['reportno']	= $reportNo;
			    }
			    else
				    $warn	.= "<p>CountyMarriagesEdit.php: " . __LINE__ .
				        	   " reportno='$value'</p>\n";
			    break;
			}

			case 'itemno':
			{
			    $itemno	    = $value;
			    break;
			}

			case 'role':
			{
			    if ($record &&
				    $record->get('givennames') != 'New Bride' &&
				    $record->get('givennames') != 'New Groom')
			    {
			    	$record->dump('Save');
			    	$record->save();
			    	$reports[]	= $record;
			    }
			    $role	    = $value;
			    $getParms	= array('domain'	=> $domain,
			        				'volume'	=> $volume,
			        				'reportno'	=> $reportNo,
			        				'itemno'	=> $itemno,
			        				'role'		=> $role);
			    $record	    = new CountyMarriage($getParms);
			    break;
			}

			case 'givennames':
			case 'surname':
			case 'age':
			case 'residence':
			case 'birthplace':
			case 'fathername':
			case 'mothername':
			case 'date':
			case 'licensetype':
			case 'witnessname':
			case 'remarks':
			case 'idir':
			{
			    $record->set($column, $value);
			    break;
			}
	    }		    // act on specific parameters
	}               // loop through all update parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

	// update the last entry
	if ($record && $record->get('givennames') != 'New Bride' &&
	    $record && $record->get('givennames') != 'New Groom')
	{
	    $record->save();
	    $reports[]	= $record;
	}

	// ensure still sorted by keys
	usort($reports,'Genealogy\compareReports');
}			            // perform update
else
{			            // initial report
    $parmsText      = "<p class=\"label\">\$_GET</p>\n" .
                        "<table class=\"summary\">\n" .
                        "<tr><th class=\"colhead\">key</th>" .
                        "<th class=\"colhead\">value</th></tr>\n";
	$getParms	    = array();
	$fixup		    = true;
	foreach($_GET as $key => $value)
	{                   // loop through all parameters
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {               // act on specific parameters
			case 'prov':
			{
			    $prov		= $value;
			    $domain		= 'CA' . $value;
			    $domainObj	= new Domain(array('domain'	=> $domain,
								   'language'	=> 'en'));
			    if ($domainObj->isExisting())
			    {		// defined
		    		$province	= $domainObj->get('name');
		    		$getParms['domain']	= $domain;
			    }		// defined
			    else
			    {		// undefined
		    		$msg		.= "Prov='$value' unsupported. ";
		    		$province	= 'Unknown ' . $value;
			    }		// undefined
			    break;
			}		    // state/province code

			case 'domain':
			case 'regdomain':
			{
			    $domain	    	= $value;
			    $domainObj	    = new Domain(array('domain'	    => $domain,
								                   'language'	=> 'en'));
			    if ($domainObj->isExisting())
			    {
	    			$getParms['domain']	= $domain;
	    			$cc			= substr($value, 0, 2);
	    			$prov		= substr($value, -2);
		    		$province	= $domainObj->get('name');
			    }
			    else
			    {
		    		$msg		.= "Domain='$value' unsupported. ";
		    		$province	= 'Domain : ' . $domain;
			    }
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->getName();
			    break;
			}		    // domain code

			case 'volume':
			{
			    if (is_int($value) ||
				(strlen($value) > 0 && ctype_digit($value)))
			    {		// valid
		    		$volume			= $value;
		    		$getParms['volume']	= $volume;
			    }		// valid
			    break;
			}

			case 'reportno':
			{
			    $reportNoText	= $value;
			    if (is_int($value) ||
	    			(strlen($value) > 0 && ctype_digit($value)))
			    {		// valid
	    			$reportNo		= $value;
	    			$getParms['reportno']	= $reportNo;
			    }		// valid
			    else
			    if (substr($value, -2) == '½' ||
		    		substr($value, -2) == '.5')
			    {
		    		$reportNo	= floor(substr($value, 0, strlen($value) - 2)) + 0.5;
		    		$getParms['reportno']	= $reportNo;
			    }
			    else
			    if ($value != '')
			    	$warn	.= "<p>CountyMarriagesEdit.php: " . __LINE__ .
			        		   " reportno='$value'</p>\n";
			    break;
			}

			case 'itemno':
			{
			    if (is_int($value) ||
	    			(strlen($value) > 0 && ctype_digit($value)))
			    {		// valid
		    		$itemNo			= $value;
		    		$getParms['itemno']	= $itemNo;
			    }		// valid
			    break;
			}

			case 'role':
			{
			    if (strlen($value) > 0)
			    {
		    		$role			= $value;
		    		$getParms['role']	= $role;
		    		$fixup			= false;
		    		if ($debug)
		    		    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'givennames':
			{
			    if (strlen($value) > 0)
			    {
	    			$getParms['givennames']	= $value;
	    			$fixup			= false;
	    			if ($debug)
	    			    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'surname':
			{
			    if (strlen($value) > 0)
			    {
	    			$getParms['surname']	= $value;
	    			$fixup			        = false;
	    			if ($debug)
	    			    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'soundex':
			{
			    if (strtolower($value) == 'y')
			    {
    				if (isset($getParms['surname']))
    				{
    				    $surname	= $getParms['surname'];
    				    unset($getParms['surname']);
    				    $getParms['surnamesoundex']	= $surname;
    				}
    				$fixup			= false;
    				if ($debug)
    				    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'residence':
			{
			    if (strlen($value) > 0)
			    {
	    			$getParms['residence']	= $value;
	    			$fixup			= false;
	    			if ($debug)
	    			    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'offset':
			{
			    if (is_int($value) || ctype_digit($value))
			    {		// valid
		    		$offset			= $value;
		    		$getParms['offset']	= $offset;
		    		if ($offset > 0)
		    		{
		    		    $fixup			= false;
		    		    if ($debug)
		        			$warn	.= "<p>fixup set to false for '$key'";
		    		}
			    }		// valid
			    break;
			}

			case 'count':
			{
			    if (is_int($value) || ctype_digit($value))
			    {		// valid
		    		$limit			= $value;
		    		$getParms['limit']	= $limit;
		    		$fixup			= false;
		    		if ($debug)
		    		    $warn	.= "<p>fixup set to false for '$key'";
			    }		// valid
			    break;
			}

			case 'debug':
			case 'lang':
			{
			    break;
			}		    // debug handled by common code

			default:
			{
			    $warn	.= "Unexpected parameter $key='$value'. ";
			    break;
			}
	    }		        // check supported parameters
	}		            // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
	if ($debug)
	    if ($fixup)
			$warn	.= "<p>fixup=true";
	    else
			$warn	.= "<p>fixup=false";
	if (is_null($domain))
	    $msg	.= "Missing parameter Domain. ";

	if (strlen($msg) == 0)
	{		// no errors detected
	    // execute the query to get the contents of the page
	    $reports	= new CountyMarriageSet($getParms);
	}		// no errors detected

	if (count($reports) == 0 &&
	    !is_null($domain) && !is_null($volume) && !is_null($reportNo))
	{
	    $create		= true;
	    if (is_null($itemNo))
	    {		// initialize new report with 10 empty entries
			$item		= 1;
			$lastItem	= 10;
	    }		// initialize new report
	    else
	    {		// create one empty entry
			$item		= $itemNo;
			$lastItem	= $itemNo;
	    }		// create one empty entry

	    $getParms	= array('domain'	=> $domain,
					'volume'	=> $volume,
					'reportNo'	=> $reportNo);
	    for(;$item <= $lastItem; $item++)
	    {		// loop creating new empty records
			$getParms['itemNo']	= $item;
			$getParms['role']	= 'G';
			$groom			= new CountyMarriage($getParms);
			$groom->set('givennames', 'New Groom');
			$reports[]		= $groom;
			$getParms['role']	= 'B';
			$bride			= new CountyMarriage($getParms);
			$bride->set('givennames', 'New Bride');
			$reports[]		= $bride;
	    }		// loop creating new empty records
	}
	else
	    $create	= false;
}			// initial report

$minister	= null;
if ($domain && $volume && $reportNo)
{		// no errors detected
	$getParms	= array('domain'	=> $domain,
					'volume'	=> $volume,
					'reportno'	=> $reportNo);
	$minister	= new CountyMarriageReport($getParms);
	if (!$minister->isExisting())
	    $minister	= null;
}		// no errors detected

$title	= "$countryName: $province: Marriage Report ";
$crumbs	= array(
			'/genealogy.php'		=> 'Genealogy',
			"/genCountry.php?cc=$cc"	=> $countryName,
			"/Canada/genProvince.php?Domain=$domain"
								=> $province,
			"CountyMarriageEditQuery.php"	=> 'County Marriage Query');
if ($volume)
{
	$title	.= "Volume $volume ";
	$crumbs["CountyMarriageReportEdit.php?RegDomain=$domain&Volume=$volume"]
				= 'Volume Summary';
}
if ($reportNo)
{
	$title	.= "Report No $reportNoText ";
	$crumbs["CountyMarriagesEdit.php?Domain=$domain&Volume=$volume&ReportNo=$reportNoText"]		= 'Report';
}
if ($itemNo)
	$title	.= "Item $itemNo ";
if ($create)
	$title	.= "Create";
else
	$title	.= "Update";

htmlHeader($title,
	       array(	'/jscripts/CommonForm.js',
				'/jscripts/js20/http.js',
				'/jscripts/util.js',
				'CountyMarriagesEdit.js'),
	       true);
?>
<body>
<?php
pageTop($crumbs);
?>
<div class="body">
  <h1>
<span class="right">
	<a href="CountyMarriagesEditHelpen.html" target="help">? Help</a>
</span>
	<?php print $title; ?>
<div style="clear: both;"></div>
  </h1>
<?php
showTrace();

if (strlen($msg) == 0)
{
	// notify the invoker if they are not authorized
	if (canUser('edit'))
	{		// authorized to update database
	    $readonly	= '';
	    $disabled	= '';
	    $codeclass	= 'white code';
	    $textclass	= 'white left';
	    $numclass	= 'white rightnc';
	}		// authorized to update database
	else
	{
?>
  <p class="warning">
	You are not authorized.
	<a href="/Signon.php" target="_blank">
	<span class="button">Sign on</span></a>
	to update the database.
  </p>
<?php
	    $readonly	= 'readonly="readonly"';
	    $disabled	= 'disabled="disabled"';
	    $codeclass	= 'ina code';
	    $textclass	= 'ina left';
	    $numclass	= 'ina right';
	}		// not authorized to update database

	$showReport	= $domain && $volume && $reportNo;
	if ($showReport)
	{		// show pointers for previous and next entry
	    if (is_null($itemNo) || $itemNo == 1)
	    {
			if ($reportNo == floor($reportNo))
			    $prevText	= $reportNo - 1;
			else
			    $prevText	= floor($reportNo);
			$prevUri	= "Domain=$domain&Volume=$volume&ReportNo=" .
					  $prevText;
	    }
	    else
	    {
			$prevUri	= "Domain=$domain&Volume=$volume&ReportNo=" .
					  $reportNoText . "&ItemNo=" . ($itemNo - 1);
			$prevText	= $reportNoText . '-' . ($itemNo - 1);
	    }
	    if (is_null($itemNo))
	    {
			if ($reportNo == floor($reportNo))
			    $nextText	= $reportNo + 1;
			else
			    $nextText	= ceil($reportNo);
			$nextUri	= "Domain=$domain&Volume=$volume&ReportNo=" .
					  $nextText;
	    }
	    else
	    {
			$nextUri	= "Domain=$domain&Volume=$volume&ReportNo=" .
					  $reportNoText . "&ItemNo=" . ($itemNo + 1);
			$nextText	= $reportNoText . '-' . ($itemNo + 1);
	    }
?>
  <div class="center" id="topBrowse">
    <div class="left" id="topPrev">
	  <a href="CountyMarriagesEdit.php?<?php print $prevUri; ?>" id="toPrevReport">
	    &lt;--- <?php print $prevText; ?> 
	  </a>
    </div>
    <div class="right" id="topNext">
	  <a href="CountyMarriagesEdit.php?<?php print $nextUri; ?>" id="toNextYear">
	    <?php print $nextText; ?> ---&gt;
	  </a>
  </div>
<?php
	    if ($showReport)
			print "Volume $volume Report $reportNoText";
	    if ($itemNo)
			print " Item $itemNo";
	    if ($minister)
	    {
			$name		= $minister->get('givennames') . ' ' .
					  $minister->get('surname');
			$faith		= $minister->get('faith');
			$residence	= $minister->get('residence');
			$page		= $minister->get('page');
			$image		= $minister->get('image');
?>
:    Marriages performed by
<?php
			print $name;
			if (strlen($faith) > 0)
			    print ', ' . $faith . ' minister';
			if (strlen($residence) > 0)
			    print ', of ' . $residence;
			if (strlen($page) > 0)
			    print ', on page ' . $page;
	    }
	    if (strlen($image) > 0)
	    {
?>
<p class="label">
	<a href="<?php print $image; ?>" class="button" target="_blank">
	    See&nbsp;Original&nbsp;Image
	</a>
</p>
<?php
	    }
?>
<div style="clear: both;"></div>
  </div>
<?php
	}		// show pointers for previous and next entry
?>
<!--- Put out the response as a table -->
<form name="countyForm" id="countyForm"
	action="CountyMarriagesEdit.php" 
	method="post" 
	autocomplete="off" 
	enctype="multipart/form-data">
<?php
	if ($showReport)
	{		// constant values for report identification
?>
  <input type="hidden" name="Domain" id="Domain"
	value="<?php print $domain; ?>">
  <input type="hidden" name="Volume" id="Volume"
	value="<?php print $volume; ?>">
  <input type="hidden" name="ReportNo" id="ReportNo"
	value="<?php print $reportNo; ?>">
<?php
	}		// constant values for report identification

	if ($debug)
	{
?>
  <input type="hidden" name="Debug" id="Debug" value="Y">
<?php
	}		// debug enabled

	if (count($reports) > 0)
	{		// some rows to display
?>
  <table class="form" id="dataTable">
<!--- Put out the column headers -->
<thead>
  <tr id="hdrRow">
<?php
	    if (!$showReport)
	    {		// showing random records
?>
	<th class="colhead">
	Domain
	</th>
	<th class="colhead">
	Volume
	</th>
	<th class="colhead">
	Report No
	</th>
<?php
	    }		// showing random records
?>
	<th class="colhead">
	Item
	</th>
	<th class="colhead">
	Role
	</th>
	<th class="colhead">
	Given Names
	</th>
	<th class="colhead">
	Surname
	</th>
	<th class="colhead">
	Age
	</th>
	<th class="colhead">
	Residence
	</th>
	<th class="colhead">
	Birth Place
	</th>
	<th class="colhead">
	Father
	</th>
	<th class="colhead">
	Mother
	</th>
	<th class="colhead">
	Date
	</th>
	<th class="colhead">
	B/L
	</th>
	<th class="colhead">
	Witness
	</th>
	<th class="colhead">
	Remarks
	</th>
	<th class="colhead">
	Details
	</th>
	<th class="colhead">
	Delete
	</th>
	<th class="colhead">
	Link
	</th>
  </tr>
</thead>
<tbody>
<?php
	    // display the results
	    $reportNo		= 0;
	    $page		= 1;
	    $row		= 0;
	    $nextRole		= 'G';
	    $tempRecord		= null;
	    $date		= '';
	    $licenseType	= 'L';
	    $last		= end($reports);
	    $first		= reset($reports);
	    foreach($reports as $record)
	    {
			while(true)
			{
			    $row++;
			    if (strlen($row) == 1)
				$row	= '0' . $row;
			    $domain		= $record->get('domain'); 
			    $volume		= $record->get('volume'); 
			    $reportNo		= $record->get('reportno'); 
			    if ($reportNo == floor($reportNo))
				$reportNoText	= intval($reportNo);
			    else
				$reportNoText	= floor($reportNo) . '½';
			    $itemNo		= $record->get('itemno'); 
			    $role		= $record->get('role');

			    if ($fixup && $role !== $nextRole)
			    {		// insert missing record
				$tempRecord		= $record;
				if ($role == 'G')
				{	// missing bride from previous marriage
				    $itemNo		= $itemNo - 1;
				    // initialize with defaults from Groom's record
				    $record	= new CountyMarriage(array(
					'm_regdomain'		=> $domain,
					'm_volume'		=> $volume,
					'm_reportno'		=> $reportNo,
			 		'm_itemno'		=> $itemNo,
			 		'm_role'		=> $nextRole,
			 		'm_givennames'		=> '',
			 		'm_surname'		=> '',
			 		'm_surnamesoundex'	=> '',
					'm_residence'		=> '',
			 		'm_date'		=> $date,
			 		'm_licensetype'		=> $licenseType,
			 		'm_witnessname'		=> '',
					'm_idir'		=> 0,
					'm_remarks'		=> ''));
				}	// missing bride from previous marriage
				else
				{	// missing groom from current marriage
				    // get defaults from Bride's record
				    $residence	= $record->get('residence');
			 	    $date	= $record->get('date');
			 	    $licenseType= $record->get('licensetype');
				    $record	= new CountyMarriage(array(
					'm_regdomain'		=> $domain,
					'm_volume'		=> $volume,
					'm_reportno'		=> $reportNo,
			 		'm_itemno'		=> $itemNo,
			 		'm_role'		=> $nextRole,
			 		'm_givennames'		=> '',
			 		'm_surname'		=> '',
			 		'm_surnamesoundex'	=> '',
					'm_residence'		=> $residence,
			 		'm_date'		=> $date,
			 		'm_licensetype'		=> $licenseType,
			 		'm_witnessname'		=> '',
					'm_idir'		=> 0,
					'm_remarks'		=> ''));
				}	// missing groom from current record
				$role		= $nextRole;
			    }		// insert missing record

			    // get values in a form suitable for presenting in HTML
			    $givennames	= $record->get('givennames');
			    $givennames	= str_replace("'","&#39;",$givennames);
			    $surname	= $record->get('surname');
			    $surname	= str_replace("'","&#39;",$surname);
			    $age	= $record->get('age'); 
			    $residence	= $record->get('residence'); 
			    $residence	= str_replace("'","&#39;",$residence);
			    $birthplace	= $record->get('birthplace'); 
			    $birthplace	= str_replace("'","&#39;",$birthplace);
			    $fathername	= $record->get('fathername'); 
			    $fathername	= str_replace("'","&#39;",$fathername);
			    $mothername	= $record->get('mothername'); 
			    $mothername	= str_replace("'","&#39;",$mothername);
			    $witness	= $record->get('witnessname');
			    $witness	= str_replace("'","&#39;",$witness);
			    $remarks	= $record->get('remarks');
			    $remarks	= str_replace("'","&#39;",$remarks);
			    if ($record->get('date') != '')
				$date	= $record->get('date');
			    if ($role == 'G')
				$licenseType= $record->get('licensetype');
			    $idir	= $record->get('idir');

			    if ($role == 'G')
			    {		// groom record
				$sexclass	= 'male';
				$nextRole	= 'B';
				if ($date == '' || $licenseType == '')
				{	// get marriage date from Bride
				    $getParms	= array('domain'	=> $domain,	
								'volume'	=> $volume,
								'reportNo'	=> $reportNo,
								'itemNo'	=> $itemNo,
								'role'		=> 'B');
				    try {
					$bride	= new CountyMarriage($getParms);
					if ($date == '')
					    $date	= $bride->get('date');
					if ($licenseType == '')
					    $licenseType= $bride->get('licensetype');
				    }
				    catch(Exception $e) {
					$warn	.= "<p>get bride failed: " . $e->getMessage() . "</p>\n<p>getParms=" . print_r($getParms, true) . "</p>\n";
					
					// stuck with missing date of marriage
				    }
				}	// get marriage date from Bride
			    }		// groom record
			    else
			    {
				$sexclass	= 'female';
				$nextRole	= 'G';
			    }
?>
  <tr id="Row<?php print $row; ?>">
<?php
			    if (!$showReport)
			    {		// display report identification
?>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Domain<?php print $row; ?>"
				id="Domain<?php print $row; ?>"
				value="<?php print $domain; ?>"
				class="<?php print $textclass; ?>"
				size="4" maxlength="6" readonly="readonly">
	</td>
	<td class="right <?php print $sexclass; ?>">
	    <input type="text" name="Volume<?php print $row; ?>"
				id="Volume<?php print $row; ?>" 
				value="<?php print $volume; ?>" 
				class="<?php print $numclass; ?>"
				<?php print $readonly; ?>
				size="3" maxlength="5">
	</td>
	<td class="right <?php print $sexclass; ?>">
	    <input type="text" name="ReportNo<?php print $row; ?>"
				id="ReportNo<?php print $row; ?>" 
				value="<?php print $reportNoText; ?>" 
				class="<?php print $numclass; ?>"
				<?php print $readonly; ?>
				size="3" maxlength="5">
	</td>
<?php
			    }		// display report identification
?>
	<td class="right <?php print $sexclass; ?>">
	    <input type="text" name="ItemNo<?php print $row; ?>"
				id="ItemNo<?php print $row; ?>"
				value="<?php print $itemNo; ?>"
				class="<?php print $numclass; ?>"
				size="3" maxlength="3" readonly="readonly">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Role<?php print $row; ?>" 
				id="Role<?php print $row; ?>" 
				value="<?php print $role; ?>" 
				class="<?php print $sexclass; ?>"
				<?php print $readonly; ?>
				size="1" maxlength="1">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="GivenNames<?php print $row; ?>"
				id="GivenNames<?php print $row; ?>" 
				value="<?php print $givennames; ?>" 
				class="<?php print $sexclass; ?>"
				<?php print $readonly; ?>
				size="16" maxlength="64">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Surname<?php print $row; ?>"
				id="Surname<?php print $row; ?>" 
				value="<?php print $surname; ?>" 
				class="<?php print $sexclass; ?>"
				<?php print $readonly; ?>
				size="16" maxlength="64">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Age<?php print $row; ?>"
				id="Age<?php print $row; ?>" 
				value="<?php print $age; ?>" 
				class="<?php print $numclass; ?>"
				<?php print $readonly; ?>
				size="6" maxlength="16">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Residence<?php print $row; ?>"
				id="Residence<?php print $row; ?>" 
				value="<?php print $residence; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="12" maxlength="32">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="BirthPlace<?php print $row; ?>"
				id="BirthPlace<?php print $row; ?>" 
				value="<?php print $birthplace; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="12" maxlength="32">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="FatherName<?php print $row; ?>"
				id="FatherName<?php print $row; ?>" 
				value="<?php print $fathername; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="12" maxlength="32">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="MotherName<?php print $row; ?>"
				id="MotherName<?php print $row; ?>" 
				value="<?php print $mothername; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="12" maxlength="32">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Date<?php print $row; ?>"
				id="Date<?php print $row; ?>" 
				value="<?php print $date; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="12" maxlength="32">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="LicenseType<?php print $row; ?>"
				id="LicenseType<?php print $row; ?>" 
				value="<?php print $licenseType; ?>"
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="1" maxlength="1">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="WitnessName<?php print $row; ?>"
				id="WitnessName<?php print $row; ?>" 
				value="<?php print $witness; ?>"
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="16" maxlength="64">
	</td>
	<td class="left <?php print $sexclass; ?>">
	    <input type="text" name="Remarks<?php print $row; ?>"
				id="Remarks<?php print $row; ?>" 
				value="<?php print $remarks; ?>" 
				class="<?php print $textclass; ?>"
				<?php print $readonly; ?>
				size="16" maxlength="255">
	</td>
	<td class="center">
	    <button type="button" class="button" 
			id="Details<?php print $row; ?>">
			Details
	    </button> 
	</td>
	<td class="center">
	    <button type="button" class="button"
			id="Delete<?php print $row; ?>" <?php print $disabled; ?>>
			Delete
	    </button> 
	</td>
	<td class="center" style="white-space: nowrap">
<?php
			    if ($idir > 0)
			    {		// link to family tree
?>
	    <button type="button" class="button" id="Link<?php print $row; ?>"
				style="color: green;">
			Tree
	    </button>
	    <button type="button" class="button" id="Clear<?php print $row; ?>"
				<?php print $disabled; ?>>
			Clear
	    </button>
<?php
			    }		// link to family tree
			    else
			    {		// no link to family tree
?>
	    <button type="button" class="button" id="Link<?php print $row; ?>"
				<?php print $disabled; ?>>
			Find
	    </button>
<?php
			    }		// no link to family tree
?>
	    <input type="hidden" id="IDIR<?php print $row; ?>"
			name="IDIR<?php print $row; ?>" value="<?php print $idir; ?>">
	</td>
  </tr>
<?php
			    // if we still have to process the temporary record
			    // inserted to correct missing marriage partners
			    // repeat the above once more with the temporary record

			    if ($record === $last && $role == 'G')
			    {		// missing bride from last marriage
				// initialize with defaults from Groom's record
				$record	= new CountyMarriage(array(
					'm_regdomain'		=> $domain,
					'm_volume'		=> $volume,
					'm_reportno'		=> $reportNo,
			 		'm_itemno'		=> $itemNo,
			 		'm_role'		=> 'B',
			 		'm_givennames'		=> '',
			 		'm_surname'		=> '',
			 		'm_surnamesoundex'	=> '',
					'm_residence'		=> '',
			 		'm_date'		=> $date,
			 		'm_licensetype'		=> $licenseType,
			 		'm_witnessname'		=> '',
					'm_idir'		=> 0,
					'm_remarks'		=> ''));
				$tempRecord	= null;
				continue;
			    }		// missing bride from last marriage
			    else
			    if (is_null($tempRecord))
				break;
			    else
			    {	// need to make another pass
				$record		= $tempRecord;
				$tempRecord	= null;
				continue;
			    }	// need to make another pass
			}	// while looping
	    }		// process all rows
?>
</tbody>
  </table>
<?php
showTrace();
	    if (canUser('edit'))
	    {
?>
<button type="submit" id="Submit">Update Database</button>
&nbsp;
<?php
	    }

	}			// some rows to display
	else
	{
?>
  <p class="message">No records match your request.
<a href="CountyMarriageEditQuery.php">Try again</a></p>
<?php
	}
?>
</form>
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
    <div class="balloon" id="HelpDomain">
    <p>This field contains a 4-character identifier of the administrative
    domain for which these registrations were recorded.
    The only meaningful value at present is 'CACW' which identifies Canada West.
    </p>
    </div>
    <div class="balloon" id="HelpVolume">
    <p>This field identifies the specific volume in which the original
    marriage registrations are stored.
    </p>
    </div>
    <div class="balloon" id="HelpReportNo">
    <p>This field identifies the specific report within the volume of
    original marriage registrations.  Each report contains information 
    about marriages performed by a particular minister of religion or
    marriage commissioner (Justice of the Peace) for a year.
    </p>
    </div>
    <div class="balloon" id="HelpItemNo">
    <p>This field identifies the specific marriage within an annual report
    by its position within the report.  The first marriage in a report is 1.
    </p>
    </div>
    <div class="balloon" id="HelpRole">
    <p>This field identifies the role of each spouse within the marriage.
    The groom is identified by 'B' and the bride by 'G'.
    </p>
    </div>
    <div class="balloon" id="HelpGivenNames">
    <p>This field contains the given names of a spouse.
    </p>
    </div>
    <div class="balloon" id="HelpSurname">
    <p>This field contains the surname of a spouse.
    </p>
    </div>
    <div class="balloon" id="HelpLink">
    <p>This button is used to display an existing individual associated with this
    record in the County Marriages table, or to do a search for a potential existing
    match in the family tree.
    </p>
    </div>
    <div class="balloon" id="HelpResidence">
    <p>This field specifies the residence of a spouse.
    </p>
    </div>
    <div class="balloon" id="HelpDate">
    <p>This field specifies the date of the marriage.
    </p>
    </div>
    <div class="balloon" id="HelpLicenseType">
    <p>This field specifies the authorization or qualification of the
    marriage.  It is either 'L' for by license, or 'B' by Banns.
    </p>
    </div>
    <div class="balloon" id="HelpWitnessName">
    <p>This field specifies the name of a witness.
    </p>
    </div>
    <div class="balloon" id="HelpRemarks">
    <p>This field permits adding remarks.  Any remarks on the original form
    can be included here, and editorial remarks can be included enclosed in
    square brackets to indicate they are not on the original document.
    </p>
    </div>
    <div class="balloon" id="HelpDetails">
    <p>This button is used to display a dialog for detailed information about
    a single marriage.  At the moment this is implemented just by displaying
    the two spouses using this same page.
    </p>
    </div>
    <div class="balloon" id="HelpDelete">
    <p>This button is used to delete a marriage registration from the list.
    This deletes two rows from the display, one for each spouse.
    </p>
    </div>
    <div class="balloon" id="HelpSubmit">
    Click on this button to update the database to include the changes you
    have made to the list of marriage registrations.
    </div>
    <div class="hidden" id="templates">
    <!-- template for adding a new marriage -->
    <table>
     <tbody>
      <tr id="Row$rowa">
    	<td class="right male">
    	    <input type="text" name="ItemNo$rowa"
    				value="$itemNo"
    				class="white rightnc"
    				size="3" maxlength="3" readonly="readonly">
    	</td>
    	<td class="left male">
    	    <input type="text" name="Role$rowa" 
    				value="G" 
    				class="male"
    				size="1" maxlength="1">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="GivenNames$rowa" 
    				value="New Groom" 
    				class="male"
    				size="16" maxlength="64">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="Surname$rowa" 
    				value="" 
    				class="male"
    				size="16" maxlength="64">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="Age$rowa" 
    				value="" 
    				class="white rightnc"
    				size="6" maxlength="16">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="Residence$rowa" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="BirthPlace$rowa" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="FatherName$rowa" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="MotherName$rowa" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="Date$rowa" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="LicenseType$rowa" 
    				value="L" 
    				class="white left"
    				size="1" maxlength="1">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="WitnessName$rowa" 
    				value="" 
    				class="white left"
    				size="16" maxlength="64">
    	</td>
    	<td class="leftmale">
    	    <input type="text" name="Remarks$rowa" 
    				value="" 
    				class="white left"
    				size="16" maxlength="255">
    	</td>
      </tr>
      <tr id="Row$rowb">
    	<td class="right female">
    	    <input type="text" name="ItemNo$rowb"
    				value="$itemNo"
    				class="white rightnc"
    				size="3" maxlength="3" readonly="readonly">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Role$rowb" 
    				value="B" 
    				class="female"
    				size="1" maxlength="1">
    	</td>
    	<td class="left female">
    	    <input type="text" name="GivenNames$rowb" 
    				value="New Bride" 
    				class="female"
    				size="16" maxlength="64">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Surname$rowb" 
    				value="" 
    				class="female"
    				size="16" maxlength="64">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Age$rowb" 
    				value="" 
    				class="white rightnc"
    				size="6" maxlength="16">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Residence$rowb" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="left female">
    	    <input type="text" name="BirthPlace$rowb" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="left female">
    	    <input type="text" name="FatherName$rowb" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="left female">
    	    <input type="text" name="MotherName$rowb" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Date$rowb" 
    				value="" 
    				class="white left"
    				size="12" maxlength="32">
    	</td>
    	<td class="left female">
    	    <input type="text" name="LicenseType$rowb" 
    				value="L" 
    				class="white left"
    				size="1" maxlength="1">
    	</td>
    	<td class="left female">
    	    <input type="text" name="WitnessName$rowb" 
    				value="" 
    				class="white left"
    				size="16" maxlength="64">
    	</td>
    	<td class="left female">
    	    <input type="text" name="Remarks$rowb" 
    				value="" 
    				class="white left"
    				size="16" maxlength="255">
    	</td>
      </tr>
     </tbody>
    </table>
      <!-- link to family tree inactive button -->
    <button type="button" class="button" id="Link$rowf">
    	Find
    </button>
      <!-- link to family tree active button -->
    <button type="button" class="button" id="Link$row" style="color: green;">
    	Tree
    </button>
      <!-- clear linkage button -->
    <button type="button" class="button" id="Clear$row">
    	Clear
    </button>
      <!-- select matching names dialog -->
      <form name="idirChooserForm$sub" id="idirChooserForm$sub">
    <p class="label">$surname, $givenname born between $birthmin and $birthmax
    <p>
    <select name="chooseIdir" id="chooseIdir$sub" size="5">
      <option value="">Choose from the following partial matches:</option>
    </select>
    <p>
      <button type="button" id="choose$line">Cancel</button>
    </p>
      </form>
      <!-- no matching names dialog -->
      <form name="idirNullForm$sub" id="idirNullForm$sub">
    <p class="label">$surname, $givenname born between $birthmin and $birthmax
    <p class="message">No individuals match
    <p>
      <button type="button" id="closeDlg$sub">Close</button>
    </p>
      </form>
    </div> <!-- end of templates -->
  </body>
</html>
