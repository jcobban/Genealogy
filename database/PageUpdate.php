<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  PageUpdate.php							*
 *									*
 *  Update the Page table entries for a particular division.  This	*
 *  script is invoked by PageForm.php with method='post'.		*
 *									*
 *  Parameters:								*
 *	Census		census identifier CCYYYY			*
 *	District	census district identifier			*
 *	SubDistrict	sub-district identifier				*
 *	Division	enumeration division number			*
 *									*
 *  History:								*
 *	2010/10/01	Reformat to new page layout.			*
 *	2010/10/19	Document entire Page table update process	*
 *	2012/09/13	pages in new division incremented by 1 instead	*
 *			of bypage					*
 *			use common routine getNames to obtain division	*
 *			info						*
 *			remove deprecated calls to doQuery and doExec	*
 *			use full census identifier in parameters	*
 *	2013/07/14	use SubDistrict class				*
 *	2013/08/17	accept district number with .0 appended		*
 *	2013/11/26	handle database server failure gracefully	*
 *	2014/04/26	remove formUtil.inc obsolete			*
 *			use class Page to update Pages table		*
 *	2014/05/19	correct field names in page table update	*
 *	2014/05/22	handle failure of SubDistrict constructor	*
 *			correct indentation				*
 *	2014/08/19	do not warn on 3 digit page numbers		*
 *	2014/09/07	improve handling of input			*
 *	2014/12/30	use new format of Page constructor		*
 *	2015/05/09	simplify and standardize <h1>			*
 *	2015/05/23	misspelled variable name caused bad District	*
 *			constructor call				*
 *			attempted to set protected fields in Page	*
 *			update functionality completely rewritten to	*
 *			better exploit the functionality of the Page	*
 *			and SubDistrict classes				*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/08/12	ReqUpdatePages moved to .php			*
 *	2016/01/20	add id to debug trace div			*
 *			include http.js before util.js			*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/Page.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // variables for constructing the main SQL SELECT statement
    $flds	= "PT_Census, PT_DistId, PT_SdId, PT_Div, PT_Page, PT_Population, PT_Transcriber, PT_ProofReader, PT_Image";
    $tbls	= "Pages";
    if (!canUser('edit'))
	$warn	.= "The database has not been updated because you are not permitted to update this table. ";

    // identify the specific Division
    $censusId		= null;		// census id 'CCYYYY'
    $censusYear		= null;		// year of enumeration
    $distId		= null;		// district number
    $subdistId		= null;		// subdistrict identifier
    $division		= null;		// division number
    $province		= null;		// explicit province id
    $subDistrict	= null;		// instance of SubDistrict
    $pageEntry		= null;		// instance of Page
    $provs		= null;		// string of valid provinces

    foreach($_POST as $key => $value)
    {		// loop through parameters
	if ($debug)
	    $warn	.= "<p>\$_POST['$key']='$value'</p>\n";
	// split the field names into column name
	// and page number
	$patres	= preg_match('/^([a-zA-Z_]+)([0-9]+)$/',
			     $key,
			     $split);
	if ($patres == 1)
	{
	    $fldname	= $split[1];	// part that is all letters and
	    $pagenum	= $split[2];	// part that is numeric
	}
	else
	{
	    $fldname	= $key;
	    $pagenum	= '';
	}
	switch(strtolower($fldname))
	{	// take action on parameter id
	    case 'census':
	    {
		$censusId	= $value;
		try
		{
		    $censusRec	= new Census(array('censusid'	=> $censusId,
						   'collective'	=> 0));
		    $provs	= $censusRec->get('provinces');
		    $censusYear	= intval(substr($censusId, 2));
		    if ($censusYear < 1867)
			$province	= substr($censusId, 0, 2);
		}
		catch (Exception $e) {
		    $msg	.= "Invalid Census identifier '$censusId'. ";
		}
		break;
	    }	// census identifier

	    case 'district':
	    {	// district number
		$distId	= $value;
		if (preg_match("/^[0-9]+([.][05])?$/", $distId))
		{			// valid syntax
		    if ($distId == floor($distId))
			$distId	= floor($distId);
		}			// valid syntax
		else
		    $msg	.= "District number '$distId' not numeric. ";
		break;
	    }	// district number

	    case 'province':
	    {	// province code
		$province	= $value;
		if (strlen($value) != 2 || strpos($provs, $value) === false)
		    $msg	.= "Province Code '$value' not recognized. ";
		break;
	    }	// province code

	    case 'subdistrict':
	    {	// subdistrict code
		$subdistId	= $value;
		break;
	    }	// subdistrict code

	    case 'division':
	    {	// division code
		$division	= $value;

		// the invoker must explicitly provide the census id
		if (is_null($censusId))
		    $msg	.= 'Census parameter missing. ';

		// the invoker must explicitly provide the District number
		if (is_null($distId))
		    $msg	.= 'District parameter missing. ';

		// the invoker must explicitly provide the SubDistrict number
		if (is_null($subdistId))
		    $msg	.= 'SubDistrict parameter missing. ';


		// try to get the instance of SubDistrict
		try {
		    $subDistrict	= new SubDistrict(
				array('sd_census'	=> $censusId,
				      'sd_distid'	=> $distId,
				      'sd_id'		=> $subdistId,
				      'sd_div'		=> $division));
		} catch (Exception $e) {
		    $msg   .= "Invalid enumeration division identification: " .
					"SD_census = $censusId, " .
					"SD_distid = $distId, " .
					"SD_id = $subdistId, " .
					"SD_div = $division :" .
					$e->getMessage() .
					$e->getTraceAsString();
		}
		break;
	    }	// division code

	    case 'pt_page':
	    {
		if (!ctype_digit($value))
		    $msg	.= "Invalid value $key='$value'. ";
		if (strlen($msg) == 0 && canUser('edit'))
		{
		    $ptParms	= array('pt_sdid' => $subDistrict,
					'pt_page' => $value);
		    $pageEntry	= new Page($ptParms);
		}
		break;
	    }			// PT_Page

	    case 'pt_population':
	    case 'pt_transcriber':
	    case 'pt_proofreader':
	    {
		if (strlen($msg) == 0 && canUser('edit'))
		{
		    $pageEntry->set($fldname, $value);
		}
		break;
	    }			// PT_Xxxx

	    case 'pt_image':
	    {
		if (strlen($msg) == 0 && canUser('edit'))
		{
		    $pageEntry->set($fldname, $value);
		    $pageEntry->save(false);
		    $pageEntry		= null;
		}
		break;
	    }			// PT_Image

	    case 'debug':
	    {
		break;
	    }

	    default:
	    {
		$warn	.= "<p>Unexpected parameter '$key' = '$fldname' '$pageNum'</p>\n";
		break;
	    }

	}	// take action on parameter id
    }		// loop through parameters

    if (strlen($msg) == 0)
    {		// no errors
	// arguments to URL
	$search		= "?Census=$censusId&Province=$province&District=$distId&SubDistrict=$subdistId&Division=$division";

	// get the district and subdistrict names
	// and other information about the identified division
	if ($subDistrict)
	{
	$distName	= $subDistrict->get('sd_name');
	$subdistName	= $subDistrict->get('sd_name');
	$pages		= $subDistrict->get('sd_pages');
	$page1		= $subDistrict->get('sd_page1');
	$bypage		= $subDistrict->get('sd_bypage');
	$imageBase	= $subDistrict->get('sd_imageBase');
	$relFrame	= $subDistrict->get('sd_relFrame');

	// setup the links to the preceding and following divisions within
	// the current district
	$npprev		= $subDistrict->getPrevSearch();
	$prevSd 	= $subDistrict->getPrevSd();
	$prevDiv	= $subDistrict->getPrevDiv();
	$npnext		= $subDistrict->getNextSearch();
	$nextSd		= $subDistrict->getNextSd();
	$nextDiv	= $subDistrict->getNextDiv();
	}
    }		// no errors
    else
	$search		= '';

    htmlHeader('Canada: Census Page Table Update',
		array(	'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'PageUpdate.js'));
?>
<body>
<?php
    pageTop(array("../genealogy.php"		=> "Genealogy", 
		  "../genCanada.html"		=> "Canada",
		  "../genCensuses.php"		=> "Censuses",
		  "ReqUpdatePages.php$search"	=> "Select New Division"));
?>
 <div class='body'>
    <h1>
    <span class='right'>
	<a href='PageHelpen.html' target='help'>? Help</a>
    </span>
	Census Page Table Update
    <div style='clear: both;'></div>
    </h1>
<?php
    showTrace();

    if (strlen($msg) == 0)
    {		// no errors
?>
    <form name='actForm'>
      <!-- Specify next district to update -->
      <p>
	<button id='newReq'
	    onclick="location='ReqUpdatePages.php<?php print $search; ?>'; return false;">
	    Specify next district to update
	</button>

<?php

	if (strlen($npnext) > 0)
	{
?>
      <!-- Short cut to update next division of census -->
      <p>
	<button id='nextDiv'
		onclick="location='PageForm.php<?php print $npnext;?>'; return false;">
	    Update next division <?php print "($nextSd - $nextDiv) "; ?>
	    in current district
	</button>
      </p>
<?php
	}

	if (strlen($npprev) > 0)
 	{
?>
      <!-- Short cut to update previous division of census -->
      <p>
	<button name='prevDiv'
		onclick="location='PageForm.php<?php print $npprev; ?>'; return false;"; >
	    Update previous division <?php print "($prevSd - $prevDiv) "; ?>
	    in current district
	</button>
      </p>
<?php
	}		// there is another division in district
?>
</form>
<?php
    }			// no errors
    else
    {			// show errors
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }			// show errors
?>
  </div> <!-- class='body' -->
<?php
    pageBot();
?>
  <div class='balloon' id='helpnewReq'>
  </div>
  <div class='balloon' id='helpnextDiv'>
  </div>
  <div class='balloon' id='helpprevDiv'>
  </div>
</body>
</html>
