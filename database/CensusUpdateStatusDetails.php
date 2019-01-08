<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusUpdateStatusDetails.php										*
 *																		*
 *  This script displays the current transcription status of every page	*
 *  in a selected enumeration division of any of the supported censuses.*
 *																		*
 *  Parameters:															*
 *		Census			the identifier of the census, including CC  	*
 *		District		district number within the census				*
 *		SubDistrict		sub-district letter or number within			*
 *						the district									*
 *		Division		enumeration division within the sub-district	*
 *		ShowProofreader	if true show proofreader's id in column			*
 *																		*
 *  History:															*
 *		2010/08/19		Suppress warning for missing Province parameter	*
 *						Use new formatting of page						*
 *		2010/11/19		Increase separation of HTML and PHP				*
 *						use MDB2 connection resource					*
 *		2011/01/05		Avoid warning if census doesn't have divisions	*
 *		2011/01/15		Include both names and ages in completion		*
 *						statistics										*
 *		2011/05/19		use CSS in place of tables						*
 *		2011/06/27		add support for 1916 census						*
 *		2011/09/25		correct URL for update/query form in header and	*
 *						trailer											*
 *		2011/10/16		add links to previous and next division of		*
 *						current district								*
 *						improve separation of PHP and HTML				*
 *						validate census year							*
 *						use table to identify number of lines per page	*
 *						clarify SQL query statements					*
 *		2011/12/08		use <button> for edit page						*
 *						the page cleans up any completely blank pages	*
 *						and any incorrect transcriber entries based		*
 *						upon the displayed statistics					*
 *		2011/12/17		improve parameter validation					*
 *						add support for optionally displaying			*
 *						proofreader id									*
 *		2012/03/10		permit administrator to upload pages to			*
 *						production server								*
 *		2012/05/29		hide upload										*
 *		2012/07/10		add display of % linked to family tree			*
 *		2012/09/13		pages in new division incremented by 1 instead	*
 *						of bypage										*
 *						use common routine getNames to obtain division	*
 *						info											*
 *						use common table to validate census				*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *						this only effects a comment						*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/07/14		use class SubDistrict							*
 *		2013/11/22		handle lack of database server connection		*
 *						gracefully										*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/28		use CSS for layout								*
 *		2014/04/26		remove formUtil.inc obsolete					*
 *						use class Page to update Pages table			*
 *						bad URL in header and trailer for edit			*
 *		2014/09/23		use shared function pctClass					*
 *						do not use SubDistrict object if constructor	*
 *						failed											*
 *						case independent parameter validation			*
 *						do not warn on presence of debug parameter		*
 *		2014/12/30		use new format of Page constructor				*
 *						redirect debugging output to $warn				*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/08		include CommonForm.js library					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/11/08		debug output sent to $parm instead of $warn		*
 *		2015/11/23		center district description						*
 *						missing page title								*
 *		2016/01/22		use class Census to get census information		*
 *						add id to debug trace div						*
 *						include http.js before util.js					*
 *		2016/07/19		use title in <h1>								*
 *		2016/07/26		fix to support improved group by validation		*
 *		2016/10/01		group by validation fails for Census1911		*
 *		2017/09/12		use get( and set(								*
 *		2018/01/15		accept lang= attribute							*
 *		2018/01/17		correct error in delete empty pages code		*
 *						parameter list of new CensusLineSet changed		*
 *		2018/11/08      improve parameter error checking                *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values if not specified
$censusId				= '';
$censusYear				= '';
$districtId		    	= '';
$districtName		   	= 'unresolved';
$subdistrictId			= '';
$subdistrictName		= 'unresolved';
$division				= '';
$province				= '';
$cc				        = 'CA';
$countryName			= 'Canada';
$domain 				= '';
$npprev		    		= '';
$prevSd 				= '';
$prevDiv				= '';
$npnext		    		= '';
$nextSd		    		= '';
$nextDiv				= '';
$lang		    		= 'en';
$showProofreader	    = false;

// obtain the parameters passed with the request
foreach($_GET as $key => $value)
{		            // loop through parameters
	switch(strtolower($key))
	{	            // take action on parameter id
	    case 'domain':
	    {	        // domain code
			$domain 	        = strtoupper($value);
			$province           = substr($domain, 2);
			break;
	    }	        // domain code

	    case 'province':
	    {	        // province code
			$province	        = strtoupper($value);
			$domain	            = 'CA' . $value;
			break;
	    }	        // province code

	    case 'state':
	    {	        // state code
			$province	        = strtoupper($value);
			$domain	            = 'US' . $value;
			break;
	    }	        // state code

	    case 'census':
	    {           // census identifier
			$censusId	        = $value;
			break;
	    }	        // census identifier

	    case 'district':
	    {	        // district number
			$districtId         = $value;
			if (substr($districtId, strlen($districtId) - 2, 2) == ".0")
			    $districtId	    = floor($districtId);
			break;
	    }	        // district number

	    case 'subdistrict':
	    {	        // subdistrict code
			$subdistrictId	    = $value;
			break;
	    }	        // subdistrict code

	    case 'division':
	    {	        // division identifier
			$division	        = $value;
			break;
	    }	        // division identifier

	    case 'showproofreader':
	    {	        // proofreader option
			if ($value == 'true')
			    $showProofreader	= true;
			break;
	    }	        // proofreader option

	    case 'lang':
	    {           // user's preferred language of communication
			if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }           // user's preferred language of communication

	    case 'debug':
	    {           // handled by common
			break;
	    }           // handled by common

	    default:
	    {	        // unexpected parameter
			$warn	.= "Unexpected parameter $key=$value. ";
			break;
	    }	        // unexpected parameter

	}	            // take action on parameter id
}	            	// loop through parameters

// the invoker must explicitly provide the Census year
if (strlen($censusId) == 0)
{
    $msg	        .= 'Census parameter missing. ';
    $censusId       = 'CA1881';
    $census	        = new Census(array('censusid'	=> 'CA1881'));
}
else
{
    $census	        = new Census(array('censusid'	=> $censusId));
    if ($census->isExisting())
    {
	    $censusYear 	= substr($censusId, 2);
        if ($census->get('collective'))
        {
            if ($province == '')
            { 
                $msg	.= 'Province parameter missing ' .
                            "for Census identifier '$censusId'. ";
            }
            else
            {
                $censusId   = $province . $censusYear;
                $census	    = new Census(array('censusid'	=> $censusId));
            }
        }
        else
	    if ($census->get('partof'))
            $province	= substr($censusId, 0, 2);
        $pctfactor	    = 100 / $census->get('linesPerPage');
    }
    else
        $msg	.= "Census identifier '$censusId' is not supported. ";
}

// the invoker must explicitly provide the District number
if (strlen($districtId) == 0)
{
    $msg	        .= 'District parameter missing. ';
    $district    = '';
}
else
{                   // district number specified
    $district       = new District(array('censusid'     => $census,
                                         'id'           => $districtId));
    if ($district->isExisting())
        $districtName   = $district->get('name');
    else
        $msg	.= "District number $districtId is not defined" .
                    " for Census identifier '$censusId'. ";
}                   // district number specified

// the invoker must explicitly provide the SubDistrict identifier
if (strlen($subdistrictId) == 0)
{
    $msg	        .= 'SubDistrict parameter missing. ';
}
else
{                   // sub-district number specified
    $subParms	    = array('sd_census'	=> $census,
					    	'sd_distid'	=> $district,	
					    	'sd_id'		=> $subdistrictId,
					    	'sd_div'	=> $division);
    $subDistrict	= new SubDistrict($subParms);

    if ($subDistrict->isExisting())
    {
	    $subdistrictName	= $subDistrict->get('sd_name');
	    $pages		    	= $subDistrict->get('sd_pages');
	    $page1		    	= $subDistrict->get('sd_page1');
	    $bypage		    	= $subDistrict->get('sd_bypage');
	    $population			= $subDistrict->get('sd_population');
	    if ($population == 0)
			$population		= 1;	// prevent divide by zero
	    $imageBase	    	= $subDistrict->get('sd_imageBase');
	    $relFrame	    	= $subDistrict->get('sd_relFrame');
	
	    // setup the links to the preceding and following divisions within
	    // the current district
	    $npprev	        	= $subDistrict->getPrevSearch();
	    $prevSd 	    	= $subDistrict->getPrevSd();
	    $prevDiv	    	= $subDistrict->getPrevDiv();
	    $npnext	        	= $subDistrict->getNextSearch();
	    $nextSd	        	= $subDistrict->getNextSd();
	    $nextDiv	    	= $subDistrict->getNextDiv();
    }
    else
        $msg	.= "Sub-District ID '$subdistrictId' is not defined within " .
            "District number $districtId of Census identifier '$censusId'. ";
}                   // sub-district number specified

// some actions depend upon whether the user can edit the database
if (canUser('edit'))
{		// user can update database
	$searchPage		= 'ReqUpdate.php';
	$action			= 'Update';
}		// user can updated database
else
{		// user can only view database
	$searchPage		= 'QueryDetail' . $censusYear . '.html';
	$action			= 'Query';
}		// user can only view database

// access database only if there were no errors in validating parameters
if (strlen($msg) == 0)
{		            // no errors
	// build parameters for searching database
	$getParms			= array();
	$getParms['censusId']		= $censusId;
	$getParms['distId']		    = $districtId;
	$getParms['subdistId']		= $subdistrictId;
	$getParms['division']		= $division;
	$getParms['order']		    = 'Line';

	// execute the main query
	$lineset	= new CensusLineSet($getParms);
	$result		= $lineset->getStatistics();
	$cleanupPages	= array();	// pages portion of WHERE
}		// no errors

if ($showProofreader)
{		// show proofreader id
		$npprev	.= "&amp;ShowProofreader=true";
		$npnext	.= "&amp;ShowProofreader=true";
}		// show proofreader id

// put out the HTML header
$title	= $censusYear . ' Census of Canada Division Status';
htmlHeader($title,
				array(	'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'/jscripts/CommonForm.js',
						'CensusUpdateStatusDetails.js'));
?>
<body>
<?php
pageTop(array(
				"/genealogy.php?lang=$lang"	=> "Genealogy",
				"/genCountry.php?cc=CA&amp;lang=$lang"	=> "Canada",
				"/database/genCensuses.php?lang=$lang"	=> "Censuses",
				"$searchPage?Census=$censusId&amp;Province=$province&amp;District=$districtId&amp;SubDistrict=$subdistrictId&amp;Division=$division&amp;lang=$lang"	=> "$action $censusYear Census",
				"/database/CensusUpdateStatus.php?Census=$censusId&amp;lang=$lang"
								=> "Status Summary",
		"/database/CensusUpdateStatusDist.php?Census=$censusId&amp;District=$districtId&amp;lang=$lang"					=> "District $districtId $districtName Summary"));
?>
<div class='body'>
<h1>
  <span class='right'>
		<a href='CensusUpdateStatusDetailsHelpen.html' target='help'>? Help</a>
  </span>
		<?php print $title; ?>
  <div style='clear: both;'></div>
  </h1>
<?php
showTrace();

if (strlen($msg) > 0)
{
?>
<p class='message'><?php print $msg; ?></p>
<?php
}		// errors
else
{		// no errors
?>
<div class='center'>
<?php
		if (strlen($npprev) > 0)
		{
?>
		<span class='left'>
		    <a href='CensusUpdateStatusDetails.php<?php print $npprev; ?>'
				id='toPrevDiv'>
				&lt;---
		    </a>
		</span>
<?php
		}	// previous division exists
		if (strlen($npnext) > 0)
		{
?>
		<span class='right'>
		    <a href='CensusUpdateStatusDetails.php<?php print $npnext; ?>'
				id='toNextDiv'>
				---&gt;
		    </a>
		</span>
<?php
		}	// next division exists
?>
      <span class='label'>District 
<?php
		print $districtId . ' ' . $districtName . ', ' . 
		  'SubDistrict ' . $subdistrictId . ' ' . $subdistrictName;
if (strlen($division) > 0)
		print " Div $division";
?>
      </span>
      <span style='clear: both;'></span>
    </div>
    <form name='divForm' action='donothing.php'>
		<input type='hidden' id='Census' value='<?php print $censusId; ?>'>
		<input type='hidden' id='Province' value='<?php print $province; ?>'>
		<input type='hidden' id='District' value='<?php print $districtId; ?>'>
		<input type='hidden' id='SubDistrict'
								value='<?php print $subdistrictId; ?>'>
		<input type='hidden' id='Division' value='<?php print $division; ?>'>
<!--- Put out the response as a table -->
      <table border="1">
        <thead>
<!--- Put out the column headers -->
          <tr>
		<th class='colhead'>
		  Page
		</th>
		<th class='colhead'>
		  Done
		</th>
		<th class='colhead'>
		  %Done
		</th>
		<th class='colhead'>
		  %Linked
		</th>
		<th class='colhead'>
		  Transcriber
		</th>
<?php
		if ($showProofreader)
		{	// show proofreader id
?>
		<th class='colhead'>
		  Proofreader
		</th>
<?php
		}	// show proofreader id
?>
		<th class='colhead' <?php if (false) { print "colspan='2'"; } ?>>
		  Action
		</th>
		<th class='colhead'>
		  &nbsp;
		</th>
		<th class='colhead'>
		  Page
		</th>
		<th class='colhead'>
		  Done
		</th>
		<th class='colhead'>
		  %Done
		</th>
		<th class='colhead'>
		  %Linked
		</th>
		<th class='colhead'>
		  Transcriber
		</th>
<?php
		if ($showProofreader)
		{	// show proofreader id
?>
		<th class='colhead'>
		  Proofreader
		</th>
<?php
		}	// show proofreader id
?>
		<th class='colhead' <?php if (false) { print "colspan='2'"; } ?>>

		  Action
		</th>
  </tr>
</thead>
<tbody>
<?php
		// display the results
		$even		= false;
		$exppage	= $page1;
		$done		= 0;
		$linked		= 0;

		foreach($result as $row)
		{
		    $page		= $row['page'];
		    $namecount		= $row['namecount'];
		    $page_population	= $row['pt_population'];
		    $transcriber	= $row['pt_transcriber'];
		    $agecount		= $row['agecount'];
		    $proofreader	= $row['pt_proofreader'];
		    $idircount		= $row['idircount'];

		    if ($even)
		    {		// insert empty cell
?>
		<td>&nbsp;</td>
<?php
		    }		// insert empty cell
		    else
		    {		// start new row
?>
  <tr>
<?php
		    }		// start new row

		    $page	= $page - 0;	// numeric page number

		    while ($exppage < $page)
		    {		// list empty pages
				$ptparms	= array('pt_sdid'	=> $subDistrict,
								'pt_page'	=> $exppage);
				$emptyPage	= new Page($ptparms);
				$emp_population	= $emptyPage->get('pt_population');
?>
		<td class='dataright'>
		  <?php print $exppage; ?> 
		</td>
		<td class='dataright'>
		  0
		</td>
		<td class='p00right'>
		  0.00
		</td>
		<td class='p00right'>
		  0.00
		</td>
		<td class='dataleft'>
		  &nbsp;
		</td>
<?php
				if ($showProofreader)
				{	// show proofreader id
?>
		<td class='dataleft'>
		  &nbsp;
		</td>
<?php
				}	// show proofreader id

				if ($emp_population > 0)
				{		// page is available to transcribe
?>
		<td class='center'>
		    <button type='button' id='Edit<?php print $exppage; ?>'>
				<?php print $action; ?> 
		    </button>
		</td>
<?php
				}		// page is available to transcribe
				else
				{		// no image for page
?>
		<td class='center'>
		    No Image
		</td>
<?php
				}		// no image for page

				if (false)
				{		// extra column if master
?>
		<td class='center'>
		</td>
<?php
				}		// extra column if master
				$exppage += $bypage;
				if ($even)
				{	// start new ro
?>
  </tr>
  <tr>
<?php
				    $even	= false;
				}	// start new row
				else
				{	// insert empty cell
?>
		<td>&nbsp;</td>
<?php
				    $even	= true;
				}	// insert empty cell
		    }		// list empty pages

		    if ($namecount == 0)
		    {		// no lines in page, should be deleted
				$cleanupPages[]	= $page;
				// clear transcriber for empty page
				$ptparms	= array('pt_sdid'	=> $subDistrict,
								'pt_page'	=> $page);
				$pageEntry	= new Page($ptparms);
				$pageEntry->set('pt_transcriber', '');
				$pageEntry->save(false);
		    }		// no lines in page, should be deleted

		    // display a row with values from database
		    $pctdone	= ($namecount + $agecount)*50/$page_population; 
		    $pctlinked	= $idircount*100/$page_population;
?>
		<td class='dataright'>
		  <?php print $page; ?> 
		<td class='dataright'>
		  <?php print $namecount; ?> 
		<td class='<?php print pctClass($pctdone, false); ?>'>
		  <?php print number_format($pctdone, 2); ?> 
		</td>
		<td class='<?php print pctClass($pctlinked, false); ?>'>
		  <?php print number_format($pctlinked, 2); ?> 
		</td>
		<td class='dataleft'>
		  <?php print $transcriber; ?> 
		</td>
<?php
		if ($showProofreader)
		{	// show proofreader id
?>
		<td class='dataleft'>
		  <?php print $proofreader; ?> 
		</td>
<?php
		}	// show proofreader id
?>
		<td class='center'> <!-- button to edit the page-->
		    <button type='button' id='Edit<?php print $exppage; ?>'>
				<?php print $action; ?> 
		    </button>
		</td>
<?php
		if (false)
		{	// master can perform uploads
?>
		<td class='center'>
		    <button type='button' id='Upload<?php print $exppage; ?>'>
				Copy
		    </button>
		</td>
<?php
		}	// master can perform uploads
		// total for division
		$done		+= intval(($namecount + $agecount)/2);
		$linked		+= $idircount;	// total linked for division

		// complete the current row and set up for the next
		if ($even)
		{
?>
  </tr>
<?php
				$even	= false;
		}
		else
		{
				$even	= true;
		}
		$exppage += $bypage;
}		// process all rows

		// if last page from database is less than last page from SubDistricts
		$lastpage	= $pages * $bypage + $page1 - 1;
		if ($exppage <= $lastpage)
		{
		    if ($even)
		    {		// insert empty cell
?>
		<td>&nbsp;</td>
<?php
		    }		// insert empty cell
		    else
		    {		// start new row
?>
  <tr>
<?php
		    }		// start new row
		}

		while ($exppage <= $lastpage)
		{		// list empty pages at end
?>
		<td class='dataright'>
		  <?php print $exppage; ?>
		<td class='dataright'>
		  0
		<td class='p00right'>
		  0.00
		</td>
		<td class='p00right'>
		  0.00
		</td>
		<td class='dataleft'>
		  &nbsp;
		</td>
<?php
		if ($showProofreader)
		{	// show proofreader id
?>
		<td class='dataleft'>
		  &nbsp;
		</td>
<?php
		}	// show proofreader id
?>
		<td class='center'> <!-- button to edit page -->
		    <button type='button' id='Edit<?php print $exppage; ?>'>
				<?php print $action; ?> 
		    </button>
		</td>
<?php
		if (false)
		{	// master can perform uploads
?>
		<td class='center'>
		</td>
<?php
		}	// master can perform uploads
		    $exppage	+= $bypage;
		    if ($even)
		    {		// start new row
?>
  </tr>
  <tr>
<?php
				$even	= false;
		    }		// start new row
		    else
		    {		// insert an empty cell
?>
		<td>&nbsp;
		</td>
<?php
				$even	= true;
		    }		// insert an empty cell
		}		// list empty pages

		// put out totals row
?>
</tbody>
<tfoot>
  <tr>	<!-- separate total from details with empty row -->
  </tr>
  <tr>	<!-- totals row -->
		<td class='dataright'>
		  Total
		</td>
		<td class='dataright'>
		  <?php print $done; ?> 
		</td>
		<td class='<?php print pctClass($done * 100 / $population, false); ?>'>
		  <?php print number_format($done * 100 / $population, 2); ?> 
		</td>
		<td class='<?php print pctClass($linked * 100 / $population, false); ?>'>
		  <?php print number_format($linked * 100 / $population, 2); ?> 
  </tr>	<!-- totals row -->
</tfoot>
  </table>
</form>
<?php
		if (count($cleanupPages) > 0 && canUser('edit'))
		{		// there are completely blank pages and user is auth'd
		    $getParms['page']	= $cleanupPages;
		    $deleteSet	= new CensusLineSet($getParms);
		    $count	= $deleteSet->delete();
		    if ($count > 0)
		    {		// some pages deleted
?>
<p>Deleted <?php print $count; ?>
		lines in completely blank pages of the transcription.</p>
<?php
		    }		// some pages deleted
		}		// there are completely blank pages and user is auth'd
?>
  <p>
<form action='CensusUpdateStatusDetails.php' method='get'>
		<input type='hidden' name='Census' value='<?php print $censusId; ?>'>
		<input type='hidden' name='Province' value='<?php print $province; ?>'>
		<input type='hidden' name='District' value='<?php print $districtId;?>'>
		<input type='hidden' name='SubDistrict' value='<?php print $subdistrictId;?>'>
		<input type='hidden' name='Division' value='<?php print $division;?>'>
		<input type='hidden' name='ShowProofreader' value='<?php if (!$showProofreader) print "true";?>'>
		<button type='submit' id='changeProof'>
<?php
		if ($showProofreader)
		    print "Do not";
?>
		    Show Proofreader
		</button>
    </form>
<?php
}		// no errors
showTrace();
?>
  </div> <!-- class='body' -->
<?php
pageBot();
?>
	<div id='mousetoPrevDiv' class='popup'>
	  <p class='large'>Go to Subdistrict <?php print $prevSd; ?> 
		Division <?php print $prevDiv; ?>&nbsp;
      </p>
	</div>
	<div id='mousetoNextDiv' class='popup'>
	  <p class='large'>Go to Subdistrict <?php print $nextSd; ?> 
		Division <?php print $nextDiv; ?>&nbsp;
      </p>
	</div>
	<div class='balloon' id='HelprightTop'>
        Click on this button to signon to access extended features of the
        web-site
	    or to manage your account with the web-site.
	</div>
	<div class='balloon' id='HelpEdit'>
	    Click on this button to edit the transcription of this page.
	</div>
	<div class='balloon' id='HelpUpload'>
	    Click on this button to upload the transcription of this page to
	    the production server.
	</div>
	<div class='balloon' id='HelpchangeProof'>
	    Click on this button to enable or disable display of the proofreader id.
	</div>
  </body>
</html>
