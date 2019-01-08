<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SubDistUpdate.php							*
 *									*
 *  Update the entries for a specific Census and District in the 	*
 *  SubDistricts table.  This is the action for the script		* 
 *  SubDistForm.php							*
 *									*
 *  History:								*
 *	2010/10/04	Reformat to new page layout.			*
 *	2010/11/07	use MDB2 common connection			*
 *	2010/11/20	add page increment column			*
 *	2010/11/27	do not set page1 and bypage to null for		*
 *			censuses where they are not displayed by	*
 *			SubDistForm.php					*
 *	2011/03/09	improve PHP & HTML separation			*
 *	2011/09/23	Correct to support over 99 rows			*
 *	2011/09/24	get previous and next district number from	*
 *			Districts table					*
 *			clean up error handling				*
 *			separate PHP and HTML				*
 *	2012/01/24	use default.js for initialization		*
 *	2012/09/17	pass full censusId to SubDistForm.php		*
 *	2012/10/27	form field name for page 1 changed to SD_PageI	*
 *			to simplify support for more than 100 		*
 *			subdistricts					*
 *	2013/01/26	table SubDistTable renamed to SubDistricts	*
 *	2013/07/13	use SubDistrict class to update database	*
 *			This means that individual records in the table	*
 *			SubDistricts are typically updated rather than	*
 *			deleted and then inserted.			*
 *	2013/08/26	pass province and full census identifier	*
 *			in header link to request subdist update	*
 *	2013/09/07	use standard header and footer formatting	*
 *			add link to hierarchy of districts		*
 *	2013/11/26	handle database server failure gracefully	*
 *	2014/04/26	remove formUtil.inc obsolete			*
 *	2014/09/26	use classes District and SubDistrict		*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/01/21	use class Census to access census information	*
 *			show debug trace				*
 *			include http.js before util.js			*
 *	2017/02/07	use class Country				*
 *	2017/08/06	permit changing id and div in existing record	*
 *			deletion of records moved here			*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Country.inc';
    require_once __NAMESPACE__ . '/District.inc';
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // interpret the parameters
    $censusId		= null;
    $distId		= null;
    foreach($_POST as $key => $value)
    {			// loop through all parameters
	switch(strtolower($key))
	{		// act on specific keys
	    case 'census':
	    {
		try
		{		// valid census code
		    $censusRec	= new Census(array('censusid'	=> $value,
						   'collective'	=> 0));
		    $censusId	= $value;
		    $censusYear	= substr($censusId, 2);
		    if (is_null($censusRec->get('partof')))
			$cc		= substr($censusId, 0, 2);
		    else
			$cc		= $censusRec->get('partof');
		    $countryObj		= new Country(array('code' => $cc));
		    $countryName	= $countryObj->getName();
		}		// valid census code
		catch (Exception $e) {
		    $msg	.= "Invalid Census identifier '$value'. ";
		}
		break;
	    }		// Census identifier

	    case 'district':
	    {
		$distId		= $value;
		if ($distId == floor($distId))
		    $distId	= (int) $distId;
		break;
	    }		// district number
	}		// act on specific keys
    }			// loop through all parameters

    // get District information
    $prevDist		= null;
    $prevDistId		= '';
    $prevDistName	= '';
    $nextDist		= null;
    $nextDistId		= '';
    $nextDistName	= '';
    $province		= '';
    try {
	$distParms	= array('census'	=> $censusId,
				'id'		=> $distId);
	$district	= new District($distParms);
	$distid		= $district->get('d_id');
	$distName	= $district->get('d_name');
	$province	= $district->get('d_province');
	$prevDist	= $district->getPrev();
	$prevDistId	= $prevDist->get('d_id');
	if ($prevDistId == floor($prevDistId))
	    $prevDistId	= (int) $prevDistId;
	$prevDistName	= $prevDist->get('d_name');
	$nextDist	= $district->getNext();
	$nextDistId	= $nextDist->get('d_id');
	if ($nextDistId == floor($nextDistId))
	    $nextDistId	= (int) $nextDistId;
	$nextDistName	= $nextDist->get('d_name');
    } catch (Exception $e) {
	$msg	.= $e->getMessage();
    }

    // update only if authorized
    if ($authorized == 'yes')
    {			// user is authorized to update
	// the replacement data for this page is passed by method=post
	// insert it into the database one subdistrict/division at a time
	$sdParms		= array('sd_census'	=> $censusId,
					'sd_distid'	=> $district);
	$oldrownum		= null;		// starting row number
	$numlen			= 2;		// length of row number
	$subDistrict		= null;
	foreach ($_POST as $name => $value)
	{		// loop through all of the fields in $_POST
	    if ($debug)
		$warn	.= "<p>\$_POST['$name']='$value'</p>\n";
	    if ($name == 'Census' ||
	        $name == 'District' ||
	    	$name == 'Province' ||
		$name == 'Submit')
	    {		// already handled
	    }		// already handled
	    else
	    {		// field in row
		// each name in $_POST consists of a database field name and
		// a row number which may be either 2 or 3 digits long
		$matches	= array();
		$numMatches	= preg_match('/^([a-zA-Z_]+)(\d+)$/',
					     $name,
					     $matches);
		if ($numMatches == 1)
		{
		    $column	= $matches[1];
		    $rownum	= $matches[2];
		}
		else
		{
		    $column	= $name;
		    $rownum	= '';
		}
		$column		= strtolower($column);
		if ($column == 'sd_pagei')
		    $column	= 'sd_page1';	// old fixup
		switch($column)
		{			// act on specific field names
		    case 'orig_id':
		    {
			$sdParms['sd_id']	= $value;
			break;
		    }

		    case 'orig_div':
		    {
			$sdParms['sd_div']	= $value;
			break;
		    }

		    case 'orig_sched':
		    {
			$sdParms['sd_sched']	= $value;
if ($debug)
    $warn	.= "<p>" . __LINE__ . " new SubDistrict(" .
		   print_r($sdParms, true) . "</p>\n";
			$subDistrict		= new SubDistrict($sdParms);
			break;
		    }

		    case 'sd_name':
		    {
			if ($value == '[Delete]')
			{
			    $subDistrict->delete();
			    $subDistrict	= null;
			}
			else
			    $subDistrict->set($column, $value);
			break;
		    }

		    case 'sd_id':
		    case 'sd_div':
		    case 'sd_sched':
		    case 'sd_pages':
		    case 'sd_page1':
		    case 'sd_population':
		    case 'sd_lacreel':
		    case 'sd_ldsreel':
		    case 'sd_imagebase':
		    case 'sd_relframe':
		    case 'sd_framect':
		    case 'sd_bypage':
		    case 'sd_remarks':
		    {
			if ($subDistrict)
			{
if ($debug)
    $warn	.= "<p>" . __LINE__ . " \$subDistrict->set('$column','$value')" . "</p>\n";
			    $subDistrict->set($column, $value);
			}
			break;
		    }

		}			// act on specific field names

		if ($oldrownum && $rownum != $oldrownum)
		{		// first field in new row so update is complete
		    if ($subDistrict)
			$subDistrict->save(false);
		    $subDistrict	= null;
		}	// row number changed, insert new record
		$oldrownum		= $rownum;
	    }		// field in row
	}		// loop through elements of $_POST

	// do final row
	if ($subDistrict)
	    $subDistrict->save(false);
    }		// authorized to update database
    else
    {		// not authorized
	$msg	.= 'You are not authorized to update the database.';
    }		// not authorized

    $title	= $censusYear . ' Census of ' . $countryName .
			' Sub-District Table Update';
    $subject	= urlencode($title);
    htmlHeader($title,
	       array(	'/jscripts/default.js',
			'/jscripts/js20/http.js',
			'/jscripts/util.js'));
?>
<body>
<?php
    pageTop(array(
	"../genealogy.php"	=> "Genealogy" ,
	"../genCanada.html"	=> "Canada",
	"../genCensuses.php"	=> "Censuses",
	"DistForm.php?Census=$censusId&Province=$province"
				=> "$censusYear Census Districts",
	"ReqUpdateSubDists.html?Census=$censusId&Province=$province&District=$nextDistId"			=> "Select New Sub-District"));
?>	
 <div class='body'>
    <h1><?php print $title; ?></h1>
<?php
    showTrace();

    // expand only if no errors
    if (strlen($msg) == 0)
    {		// no errors encountered
?>
    <p>District <?php print $distId; ?> <?php print $distName; ?> Updated</p>
<?php
	if (strlen($prevDistId) > 0)
	{	// previous district defined
?>
    <p class='label'>
	<button onclick="document.location='SubDistForm.php?Census=<?php print $censusId; ?>&District=<?php print $prevDistId; ?>'; return false;">
	    Edit District <?php print $prevDistId; ?>
			<?php print $prevDistName; ?>
	</button>
    </p>
<?php
	}	// previous district defined
	if (strlen($nextDistId) > 0)
	{	// next district defined
?>
    <p class='label'>
	<button onclick="document.location='SubDistForm.php?Census=<?php print $censusId; ?>&District=<?php print $nextDistId; ?>'; return false;">
	    Edit District <?php print $nextDistId; ?>
			<?php print $nextDistName; ?>
	</button>
    </p>
<?php
	}	// next district defined
    }		// no errors encountered
    else
    {
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }
?>
  </div> <!-- class='body' -->
<?php
    pageBot();
?>
</body>
</html>
