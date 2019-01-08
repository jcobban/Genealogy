<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  PageForm.php							*
 *									*
 *  Update the Pages database for a single enumeration division.	*
 *									*
 *  Parameters								*
 *	Census		year of census					*
 *	Province	province of enumeration (mandatory < 1867)	*
 *	District	district number					*
 *	SubDistrict	sub-district identifier				*
 *	Division	division number (optional for some sub-districts*
 *									*
 *  History:								*
 *	2010/10/01	Reformat to new page layout.			*
 *	2010/10/04	Default image URL for 1911 census		*
 *	2010/10/19	Add hyperlink to help page			*
 *			Make page number read-only			*
 *			Clean up separation of HTML and PHP		*
 *			Add default image URL creation for 1851 census	*
 *	2010/11/20	add support for alternate page increment	*
 *			use common MDB2 connection to database		*
 *	2010/11/21	do not generate default image URL if missing	*
 *			information in SubDistricts			*
 *	2010/11/23	no error message on empty value of Province	*
 *	2010/11/28	support either image base or relative frame as	*
 *			first frame number for image url generation	*
 *	2011/01/20	incorrect where clause if division null string	*
 *	2011/02/03	add button for viewing the identified image	*
 *	2011/04/20	improve separation of javascript and HTML	*
 *	2011/06/03	use CSS for layout in place of tables		*
 *	2011/06/27	add support for 1916 census			*
 *	2011/09/04	add support for 1871 census			*
 *			clean up default image generation		*
 *	2011/09/10	change algorithm for default 1871 image files	*
 *	2011/09/25	make parameters Province and Division optional	*
 *			improve validation				*
 *			add support for 1906 census images		*
 *	2011/11/04	use button to view images			*
 *	2012/04/16	add id='Submit' on submit button so help works.	*
 *	2012/09/13	share default image URL calculation with	*
 *			CensusForms					*
 *			use clearer variable names			*
 *			use full census identifier in parameters	*
 *	2013/01/26	remove diagnostic printout			*
 *	2013/04/13	support being invoked without edit		*
 *			authorization better				*
 *	2013/07/14	use SubDistrict object				*
 *	2013/07/23	add support for ripple update to image URLs	*
 *			support district numbers ending in .0		*
 *	2013/07/26	do not capitalize page number			*
 *	2011/08/19	add support for 1921 census			*
 *	2011/08/21	improve structuring of display table to		*
 *			support common dynamic keystroke handling	*
 *	2013/08/31	provide ability to override ImageBase and	*
 *			RelFrame through parameters			*
 *	2013/11/26	handle database server failure gracefully	*
 *	2014/07/15	support for popupAlert moved to common code	*
 *	2014/09/07	do not override $debug value from common	*
 *			pass Debug to PageUpdate.php			*
 *	2014/12/30	use class Page to access Pages table		*
 *			do not display fractional portion of integer	*
 *			district id					*
 *			redirect debugging output to $warn		*
 *	2015/05/08	do not use tables for layout			*
 *			use tiling interface for image displaye		*
 *	2015/05/09	simplify and standardize <h1>			*
 *	2015/05/25	display help in a new tab or window		*
 *	2016/06/05	use $censusInfo					*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/08/12	query page moved to ReqUpdatePages.php		*
 *	2016/01/20	add id to debug trace div			*
 *			include http.js before util.js			*
 *			use class Census to get census information	*
 *	2017/09/12	use get( and set(				*
 *	2017/11/17	functionality for initializing page table	*
 *			database records is moved to class SubDistrict	*
 *			$subdistrict->getPages now returns RecordSet	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Country.inc';
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/Page.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // get parameter values into local variables
    $censusYear		= '';		// census year
    $cc			= 'CA';		// ISO country code
    $countryName	= 'Canada';
    $province		= '';		// province for pre-confederation
    $distId		= '';
    $subdistId		= '';
    $division		= '';

    // identify census

    $update	= canUser('admin');
    if ($update)
	$readonly	= '';
    else
    {
	$readonly	= "readonly='readonly'";
	$warn		.= "<p>The database will not be updated because you are not signed on as a user authorized to update this table</p>\n";
    }

    // variables for constructing the main SQL SELECT statement

    $npuri		= 'PageForm.php';	// for next and previous links
    $npand		= '?';			// adding parms to $npuri
    $npprev		= '';			// previous selection
    $npnext		= '';			// next selection

    // validate all parameters passed to the server and construct the
    // various portions of the SQL SELECT statement
    foreach ($_GET as $key => $value)
    {			// loop through all parameters
	if ($value == '?')
	{		// value explicitly not supplied
	    $msg .= "$key must be selected. ";
	}		// value explicitly not supplied
	else
	switch($key)
	{
	    case 'Census':
	    {		// census identifier
		if (strlen($value) == 4)
		{		// old format only includes year
		    $censusYear		= $value;	// census year
		    $censusId		= 'CA' . $censusYear;
		}		// old format only includes year
		else
		    $censusId		= $value;

		$censusRec	= new Census(array('censusid'	=> $censusId));
		$partof		= $censusRec->get('partof');
		if (is_string($partof) && strlen($partof) == 2)
		    $cc		= $partof;
		else
		    $cc		= substr($censusId, 0, 2);
		$censusYear	= intval(substr($censusId, 2));
		$countryObj	= new Country(array('code' => $cc));
		$countryName	= $countryObj->getName();
		break;
	    }		// census year

	    case 'Province':
	    {		// province code
		$province	= $value;
		if ($censusRec)
		{
		    $ppos	= strpos($province,
					 $censusRec->get('provinces'));
		    if (strlen($province) != 2 ||
			$ppos < 0 || ($ppos & 1) == 1)
		    {
			$msg	.= "Province '$province' not supported for '$censusId' census. ";
		    }
		    else
		    {
			$domain		= 'CA' . $province;
			$domainParms	= array('domain'	=> $domain,
						'language'	=> 'en');
			$domainObj	= new Domain($domainParms);
			$countryName	= $domainObj->getName();
		    }
		}
		else
		    $msg	.= "Province specified without valid Census. ";
		break;
	    }		// province code
	
	    case 'District':
	    {		// district number
		if (preg_match("/^[0-9]+(\.[05]|)$/", $value) == 1)
		{		// matches pattern of a district number
		    if (substr($value,strlen($value)-2) == '.0')
			$distId	= substr($value, 0, strlen($value) - 2);
		    else
			$distId	= $value;
		}		// matches pattern of a district number
		else
		{
		    $msg		.= "District value $value invalid. ";
		}
		break;
	    }		// district number

	    case 'SubDistrict':
	    {		// subdistrict code
		$subdistId		= $value;
		break;
	    }		// subdistrict code

	    case 'Division':
	    {		// enumeration division
		$division		= $value;
		break;
	    }		// enumeration division

	}	// switch on $key value
    }		// foreach parameter

    // search arguments to URL for current instance
    $search	= "?Census=$censusId&amp;Province=$province&amp;District=$distId&amp;SubDistrict=$subdistId&amp;Division=$division";
    if ($censusYear == 1851 || $censusYear == 1861)
	$censusId	= $province . $censusYear;

    // get the district and subdistrict names
    // and other information about the identified division
    $subDistrict	= new SubDistrict(array('sd_census'	=> $censusId,
						'sd_distid'	=> $distId,
						'sd_id'		=> $subdistId,
						'sd_div'	=> $division));

    $distName		= $subDistrict->get('d_name');
    $subdistName	= $subDistrict->get('sd_name');
    $pages		= $subDistrict->get('sd_pages');
    $page1		= $subDistrict->get('sd_page1');
    $bypage		= $subDistrict->get('sd_bypage');
    $imageBase		= $subDistrict->get('sd_imagebase');
    $relFrame		= $subDistrict->get('sd_relframe');

    // the page number past the end of the division
    $dlmpage		= $page1 + ($pages * $bypage);
 
    // setup the links to the preceding and following divisions within
    // the current district
    $npprev		= $subDistrict->getPrevSearch();
    $npnext		= $subDistrict->getNextSearch();

    if ($division == "?")
    {		// division not selected
	$msg	.= 'You must select a division with the chosen subdistrict. ';
    }		// division not selected
    $title	= "Census Administration: $countryName: $censusYear Census: Page Table";
    if ($update)
	$title	.= ' Update';
    else
	$title	.= ' Display';

    htmlHeader($title,
		array(	'/jscripts/CommonForm.js',
			'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'PageForm.js'));
?>
<body>
  <div id='transcription' style='overflow: auto; overflow-x: scroll'>
<?php
    pageTop(array(
	'/genealogy.php'	=> 'Genealogy', 
	'/genCanada.html'	=> 'Canada',
	'genCensuses.php'	=> 'Censuses',
	'EditCensuses.php'	=> 'Censuses Admin',
	"DistForm.php?Census=$censusId&Province=$province"
				=> "$censusId Districts Admin",
	"SubDistForm.php?Census=$censusId&Province=$province&District=$distId" 
			=> "District $distId $distName SubDistricts Admin",
	"ReqUpdatePages.php$search"
				=> 'Select New Division'));
?>
 <div class='body'>
    <h1>
      <span class='right'>
	<a href='PageFormHelpen.html' target='_blank'>
	Help?
	</a>
      </span>
	<?php print $title; ?>
      <div style='clear: both;'></div>
      </div>
    </h1>
<?php
    // display diagnostic trace
    showTrace();

    // if error messages display them
    if (strlen($msg) > 0)
    {		// there are messages to display
?>
    <p class='message'>
	<?php print $msg; ?> 
    </p>
<?php
    }		// there are messages to display
    else
    {		// authorized to update database
	// get the set of pages
	$pages		= $subDistrict->getPages();
?>
    <div class='center'>
<?php
	if (strlen($npprev) > 0)
	{
?>
      <span class='left' id='gotoPrefDivTop'>
	<a href='<?php print $npuri . $npprev; ?>'>&lt;---</a>
      </span>
<?php
	}	// previous division exists
	if (strlen($npnext) > 0)
	{
?>
      <span class='right' id='gotoNextDivTop'>
	<a href='<?php print $npuri . $npnext; ?>'>---&gt;</a>
      </span>
<?php
	}	// next division exists

	// identify the displayed page
	$label		= "Census=$censusId, ";
	if (strlen($province) > 0)
	    $label	.= "Province=$province, ";
	$label	.= "Dist $distId $distName, SubDist $subdistId $subdistName";
	if (strlen($division) > 0)
	    $label	.= ", Div $division\n";
	print $label;
?>
    <span style='clear: both;'></span>
  </div>
<!--- Put out the response as a table -->
<form name='censusForm'
	action='PageUpdate.php' 
	method='post' 
	autocomplete='off' 
	enctype='multipart/form-data'>
  <div id='hidden'>
    <!-- parameters identifying the division being editted -->
    <input type='hidden' name='Census' id='Census' value='<?php print $censusId; ?>'>
    <input type='hidden' name='Province' id='Province' value='<?php print $province; ?>'>
    <input type='hidden' name='District' id='District' value='<?php print $distId; ?>'>
    <input type='hidden' name='SubDistrict' id='SubDistrict' value='<?php print $subdistId;?>'>
    <input type='hidden' name='Division' id='Division' value='<?php print $division; ?>'>
<?php
	if ($debug)
	{
?>
    <input type='hidden' name='Debug' id='Debug' value='Y'>
<?php
	}
?>
  </div>
  <table id='dataTbl' class='form'>
    <thead><!--- Put out the column headers -->
      <tr>
	<th class='colhead'>
	Page
	</th>
	<th class='colhead'>
	Count
	</th>
	<th class='colhead'>
	Transcriber
	</th>
	<th class='colhead'>
	Proofreader
	</th>
	<th class='colhead'>
	Image URL
	</th>
	<th class='colhead'>
	View
	</th>
      </tr>
    </thead>
    <tbody>
<?php
	// display the results
	foreach($pages as $page)
	{	// loop through pages in division
	    // ensure that line number is always 2 digits
	    $pagenum		= (string) $page->get('pt_page');
	    $pagenum		= str_pad($pagenum, 2, "0", STR_PAD_LEFT);

	    $population		= $page->get('pt_population');
	    $transcriber	= $page->get('pt_transcriber');
	    $proofreader	= $page->get('pt_proofreader');
	    $image		= $page->get('pt_image');
?>
  <tr>
    <td>
	<input type='text' name='PT_Page<?php print $pagenum; ?>'
		id='PT_Page<?php print $pagenum; ?>'
		value='<?php print $pagenum; ?>' readonly='readonly'
		class='ina rightnc' size='3'>
    </td>
    <td>
	<input type='text' name='PT_Population<?php print $pagenum; ?>' 
		id='PT_Population<?php print $pagenum; ?>' 
		value='<?php print $population; ?>' <?php print $readonly; ?> 
		class='white rightnc' size='3'>
    </td>
    <td>
	<input type='text' name='PT_Transcriber<?php print $pagenum; ?>' 
		id='PT_Transcriber<?php print $pagenum; ?>' 
		value='<?php print $transcriber; ?>' <?php print $readonly; ?> 
		class='white leftnc' size='10'>
    </td>
    <td>
	<input type='text' name='PT_ProofReader<?php print $pagenum; ?>' 
		id='PT_ProofReader<?php print $pagenum; ?>' 
		value='<?php print $proofreader; ?>' <?php print $readonly; ?> 
		class='white leftnc' size='10'>
    </td>
    <td>
	<input type='text' name='PT_Image<?php print $pagenum; ?>' 
		id='PT_Image<?php print $pagenum; ?>' 
		value='<?php print $image; ?>' <?php print $readonly; ?> 
		class='white leftnc' size='64'>
    </td>
    <td>
	<button type='button' id='View<?php print $pagenum; ?>'>View</button>
    </td>
  </tr>
<?php
	}	// loop through pages in division
?>
    </tbody>
  </table>
<?php
    if (canUser('edit'))
    {
?>
  <p>
    <button type='submit' id='Submit'>
	<u>U</u>pdate Database
    </button>
  </p>
<?php
    }
?>
</form>
<?php
    }		// no errors in validation
?>
    <div class='center'>
<?php
	if (strlen($npprev) > 0)
	{
?>
      <span class='left' id='gotPrevDivBot'>
	<a href='<?php print $npuri . $npprev; ?>'>&lt;---</a>
      </span>
<?php
	}	// previous division exists
	if (strlen($npnext) > 0)
	{
?>
      <span class='right' id='gotoNextDivBot'>
	<a href='<?php print $npuri . $npnext; ?>'>---&gt;</a>
      </span>
<?php
	}	// next division exists

	// identify the displayed page
	$label		= "Census=$censusId, ";
	if (strlen($province) > 0)
	    $label	.= "Province=$province, ";
	$label	.= "Dist $distId $distName, SubDist $subdistId $subdistName";
	if (strlen($division) > 0)
	    $label	.= ", Div $division\n";
	print $label;
?>
    <span style='clear: both;'></span>
  </div>
  </div> <!-- end of <div id='body'> -->
<?php
    showTrace();
    pageBot();
?>
  </div> <!-- id='transcription' -->
  <!-- templates for dynamic HTML -->
  <div class='hidden' id='templates'>
    <!-- no matching names dialog -->
    <form id='ChangeImageForm$sub'>
      <p class='label'>Image URL Changed</p>
      <p>Increment URLs for Pages Following 
  	<input type='text' name='Page' value='$page'
  		size='3' class='white rightnc'> by
  	<input type='text' name='Increment' value='$increment'
  		size='3' class='white rightnc'>
      <p>
	<button type='button' id='incrementImage$sub'>Increment</button>
	<button type='button' id='closeDlg$sub'>Close</button>
      </p>
    </form>
  </div> <!-- id='templates' -->
  <!-- The remainder of the page consists of context specific help text.
  -->
  <div class='balloon' id='HelpCensus'>
    The census for which you wish to update the Census Page description table
    is identified by selecting the year of the census from this list.
  </div>
  <div class='balloon' id='HelpProvince'>
    The province selection list is populated once you have selected a
    particular census.  This is used to restrict the list of districts
    displayed to those within the selected province.  For pre-confederation
    censuses it is mandatory to select a province, because the census was
    administered separately within each colony.
  </div>
  <div class='balloon' id='HelpDistrict'>
    The District selection list is populated once you have selected a
    particular Census, and is modified if you select a province within the Census.
    Select one district.
  </div>
  <div class='balloon' id='HelpSubDistrict'>
    The Sub-District selection list is populated once you have selected a
    particular district.
  </div>
  <div class='balloon' id='HelpDivision'>
    If the Sub-District is divided into multiple enumeration divisions, then
    this selection list is presented to permit you to select the specific
    division.
  </div>
  <div class='balloon' id='HelpPT_Page'>
    This field identifies the page number within the division.  It is not editable.
  </div>
  <div class='balloon' id='HelpPT_Population'>
    This field contains the count of the number of individuals enumerated in
    the image of the original.  For most pages it is the number of rows on
    the original form.
    This is a decimal number.
  </div>
  <div class='balloon' id='HelpPT_Image'>
    The Image field contains the URL of the original census image as
    it is available from the Library and Archives of Canada web-site.
    If you change just the last numeric part of the image, then a dialog
    pops up to ask if you wish the URLs of the following pages to be
    adjusted by the same increment you applied to the current image.
  </div>
  <div class='balloon' id='HelpPT_Transcriber'>
    The transcriber field contains the user identifier of the individual 
    responsible for transcribing this page.  It is set to the first user
    to edit the page.  Once set the page is edittable only by that user,
    all other users, including the proofreader, can only comment on the 
    transcription.
  </div>
  <div class='balloon' id='HelpPT_ProofReader'>
    The proofreader field contains the user identifier of the individual 
    responsible for proofreading this page.
  </div>
  <div class='balloon' id='HelprightTop'>
    Click on this button to signon to access extended features of the web-site
    or to manage your account with the web-site.
  </div>
  <div class='balloon' id='HelpView'>
    Click on this button to view the image file identified in the preceding
    field in a new window.
  </div>
  <div class='balloon' id='HelpSubmit'>
    Click on this button to apply the changes to the page table database.
    alternatively you may use the keyboard shortcut Alt-U.
  </div>
</body>
</html>
