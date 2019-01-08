<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  QueryCensus.php							*
 *									*
 *  Common code for PHP web pages implementing searches of a Canadian	*
 *  Census.								*
 *									*
 *  Parameters (passed by method='get'):				*
 *	Province	optional 2 letter province code			*
 *	District	district number within 1871 census		*
 *	SubDistrict	subdistrict letter code within district		*
 *	Division	optional division within subdistrict		*
 *	OrderBy		'Name'	order response by surname, given names, *
 *				and birth year				*
 *			'Line'	order response by position within form	*
 *	Family		if present limit response to members of a	*
 *			family with this identifier			*
 *	Page		if present limit response to individuals on 	*
 *			the specific page (typically with OrderBy=Line)	*
 *	Count		limit number of rows to display at a time	*
 *	Offset		starting row within result set			*
 *	BYear		limit response by birth year			*
 *	Range		the range on either side of birth year		*
 *	Surname		pattern match for surnames			*
 *	SurnameSoundex	match surnames by soundex code			*
 *	GivenNames	match given names by pattern			*
 *	Occupation	match occupation by pattern			*
 *	BPlace		match birth place by pattern			*
 *	Origin		match origin by pattern				*
 *	Nationality	match nationality by pattern			*
 *	Religion	match religion by pattern			*
 *	Census		census year					*
 *	...								*
 *	debug		enable script debugging				*
 *									*
 *  History:								*
 *	2010/10/07	fix warnings on keys Province and Division	*
 *	2010/11/21	support pre-confederation censuses		*
 *	2010/11/28	correct URL for displaying CensusForm from	*
 *			QueryDetail					*
 *	2010/12/22	use $connection->quote to encode the surname	*
 *			so that surnames with a quote can be used	*
 *	2011/01/07	fix error in surname search by regexp		*
 *	2011/02/16	fix syntax error in surname soundex search if	*
 *			quotes						*
 *	2011/03/27	use switch for parameter names			*
 *			always include OrderBy parameter in $npuri	*
 *			do not fail if OrderBy parameter missing	*
 *	2011/04/10	search whole database if no district specified	*
 *	2011/05/01	make the "See All Fields" hyperlink look like a	*
 *			button.						*
 *	2011/05/08	add Province to $npuri				*
 *	2011/07/13	1911 Census does need Division in table link	*
 *	2011/09/03	support a comma-separated list of		*
 *			district:subdistrict pairs in the		*
 *			SubDistrict parameter				*
 *	2011/09/04	add code to handle corrupted Districts or	*
 *			SubDistrictsi tables.  And support global	*
 *			$SubDist that is an array.			*
 *	2011/09/18	ignore buttons from IE7				*
 *	2011/10/09	significant restructuring to facilitate future	*
 *			maintenance.					*
 *			Improved error handling				*
 *			Cookie set here rather than by function call	*
 *			from census specific script			*
 *	2011/10/15	provide query specific identification string	*
 *			for header					*
 *	2012/03/31	support for IDIR link to family tree		*
 *	2012/04/01	if full page requested provide button to see	*
 *			image						*
 *	2012/04/07	fix bug in subdistricts with no division value	*
 *			fix bug that LIMIT set to 1			*
 *	2012/06/22	include province id in pre-confederation	*
 *			description					*
 *	2012/09/14	always include division in URI			*
 *	2012/09/25	pass census identifier to other scripts		*
 *	2013/01/26	table SubDistTable renamed to SubDistricts	*
 *	2013/02/27	add Address, Location, CauseOfDeath to fields	*
 *			searched by regular expression			*
 *	2013/05/23	add Debug parameter				*
 *	2013/07/07	use classes SubDistrict and Page		*
 *	2014/06/05	urlencode parameters				*
 *	2014/08/10	remove setting of cookie from this module	*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

require_once __NAMESPACE__ . "//home/jcobban/includes/common.inc";
require_once __NAMESPACE__ . "//home/jcobban/includes/formUtil.inc";
require_once __NAMESPACE__ . "//home/jcobban/includes/SubDistrict.inc";
require_once __NAMESPACE__ . "//home/jcobban/includes/Page.inc";

/************************************************************************
 *  getResults								*
 *									*
 *  Issue a query to the database to extract the data from the database	*
 *  matching the parameters passed to the current web page		*
 *									*
 *  Returns:								*
 *	array of associative arrays from the database			*
 ************************************************************************/

function getResults()
{
    global	$debug;		// if true, output trace
    global	$Census;	// census year, e.g. '1891'	
    global	$Province;	// province identifier, e.g. 'ON'	
    global	$District;	// district number
    global	$SubDist;	// sub-district number
    global	$Division;	// division number
    global	$page;		// page number
    global	$family;	// family number
    global	$showLine;	// include the Line number in response
    global	$last;		// ordinal number of last record retrieved
    global	$npnext;	// link for subsequent portion of results
    global	$respDesc;	// description of response
    global	$censusLines;	// instance of CensusLineSet

    $info	= $censusLines->getInformation();
    $totalrows	= $info['count'];	// total rows in result set
    print '<p>' . htmlspecialchars($info['query']) . "</p>\n";
    print '<p> ' . $info['count'] . " rows</p>\n";

    $count	= $censusLines->count();
    $last	= $offset + $count;
    if (($last == $totalrows) && (substr($npnext, 0, 5) == 'Count'))
	$npnext	= '';
    $respDesc	= "returned rows " . ($offset + 1) . " to " .
		  $last . " of " . $totalrows;

?>
    <form action='doNothing.php' name='buttonForm'>
<?php
    if ($showLine)
    {		// option to show everything on page or update
	if (is_array($SubDist))
	    $SdId		= $SubDist[0];
	else
	    $SdId		= $SubDist;
	$search	=   "?Province=" . $Province .
		    "&amp;District=" . $District .
		    "&amp;SubDistrict=" . $SdId .
		    "&amp;Division=" . $Division;
	if (strlen($page) > 0)
	    $search	.= "&amp;Page=" . $page;
	else
	    $search	.= "&amp;Family=" . $family;
	if ($Census < 1867)
	    $censusId	= $Province . $Census;
	else
	    $censusId	= 'CA' . $Census;

	if (is_array($District))
	    $dId	= $District[0];
	else
	    $dId	= $District;
	if (is_array($SdId))
	    $subdId	= $SdId[0];
	else
	    $subdId	= $SdId;

	// determine division identifier to use in query
	$divId	= $Division;
	if (strlen($Division) > 0)
	{
	    $d		= strpos($Division, ':');
	    if ($d !== false)
	    {		// separator found
		$dId	= substr($Division, 0, $d);
		$subdId	= substr($Division, $d+1);
		$divId	= '';
	    }		// separator found
	}

	$sdParms	= array(
			'census'	=> $censusId,
			'distId'	=> $dId, 
			'SD_Id'		=> $subdId,
			'SD_Div'	=> $divId);
	$subDistrict	= new SubDistrict($sdParms);
	$DName		= $subDistrict->get('d_name');
	$SubDName	= $subDistrict->get('sd_name');
	$page1		= $subDistrict->get('sd_page1');
	$imageBase	= $subDistrict->get('sd_imagebase');
	$relFrame	= $subDistrict->get('sd_relframe');
	$pages		= $subDistrict->get('sd_pages');
	$bypage		= $subDistrict->get('sd_bypage');

	// identify requested page or family
	if (strlen($divId) > 0)
	    $respDesc	= "dist $dId $DName, subdist $subdId $SubDName, div $divId";
	else
	    $respDesc	= "dist $dId $DName, subdist $subdId $SubDName,";
	if ($Census < 1867)
	    $respDesc	= $Province . ', ' . $respDesc;
	if (strlen($page) > 0)
	    $respDesc	.= " page $page";
	else
	    $respDesc	.= " family $family";

	if (strlen($page) > 0)
	{	// display whole page	
	    $pageRec	= new Page($subDistrict, $page);
	    $image	= $pageRec->get('pt_image');
?>
	<p class='labelSmall'>
	<a href='CensusUpdateStatus.php?Census=<?php print $censusId; ?>'>
	    <?php print $Census; ?> Summary
	</a>:
	<a href='CensusUpdateStatusDist.php?Census=<?php print $censusId; ?>&amp;Province=<?php print $Province; ?>&amp;District=<?php print $District; ?>'>
	    District <?php print $District; ?> <?php print $DName; ?> Summary</a>:
	<a href='CensusUpdateStatusDetails.php?Census=<?php print $censusId;?>&amp;Province=<?php print $Province;?>&amp;District=<?php print $District;?>&amp;SubDistrict=<?php print $SubDist[0];?>&amp;Division=<?php print $Division;?>'>
	    Division Details
	</a>:
	</p>
	<p class='label'>
	    <input type='hidden' name='search' disabled
	value='CensusForm<?php print $Census; ?>.php<?php print $search;?>'>
	    <input type='hidden' name='image' disabled
		value='<?php print $image; ?>'>
	    <button type='button' name='seeAllFields'>See All Fields</button>
	    <button type='button' name='displayImage'>Display Image</button>
	</p>
<?php
	}	// display whole page	
    }		// show line number column

    return	$censusLines;
}		// getResults

/************************************************************************
 *  The following functionality is included and executed on each of the	*
 *  QueryDetailyyyy.php scripts.					*
 ************************************************************************/

    // validate all parameters passed to the server and construct the
    // various portions of the SQL SELECT statement
    // set default values that are overriden by parameters
    // Note that the value of $Census is passed from the invoking script

    $District	= '';		// default all districts
    $SubDist	= '';		// default all subdistricts
    $Division	= '';		// default all divisions
    $count	= 20;		// default max lines per page
    $offset	= 0;		// default start with first line of result
    $Range	= 1;		// default 1 year either side of age/byear
    $page	= '';		// default any page
    $family	= '';		// default any family
    $Province	= '';		// default any province
    $orderBy	= 'Name';	// default order alphabetically
    $SurnameSoundex	= false;// check text of surname, not soundex code
    $msg	= '';		// accumulate error message

    // loop through all of the passed parameters to validate them
    // and save their values into local variables, overriding
    // the defaults specified above
    foreach ($_GET as $key => $val)
    {			// loop through all parameters
	if ((is_string($val) && strlen($val) > 0) ||
	    is_array($val))
	{
	    switch($key)
	    {		// switch on parameter name
		case 'Count':
		{		// limit number of rows returned
		    if (ereg("^([0-9]{1,2})$", $val) && ($val >= 5))
			$count	= (int)$val;
		    else
			$msg    .="Row Count '$val' must be number between 5 and 99. ";
		    break;
		}		// limit number of rows returned

		case 'Offset':
		{		// starting offset
		    if (ereg("^([0-9]{1,6})$", $val))
			$offset	= (int)$val;
		    else
			$msg   .= "Row Offset must be an integer " +
					"between 0 and 999,999. ";
		    break;
		}		// starting offset

		case 'OrderBy':
		{		// Override order of display
		    if ($val == 'Name' || $val == 'Line')
			$orderBy	= $val;
		    else
			$msg	.= "Invalid value of OrderBy='$val'";
		    break;
		}		// Override order of display

		case 'BYear':
		{		// BYear
		    if (!ereg("^([0-9]{1,4})$", $val) ||
			$val < 1750 || $val > 2099)
			$msg	.= "Birth Year '$val' must be an integer " .
				    "and in the range 1750 to 2099.  ";
		    break;
		}		// BYear

		case 'Range':
		{		// Range of ages or birth years
		    if (ereg("^([0-9]{1,2})$", $val) && $val >= 0 && $val <= 20)
			$Range	= (int)$val;
		    else
			$msg	.= "Range '$val' must be an integer " .
				   "between 0 and 20";
		    break;
		}		// "Range"

		case 'Page':
		{		// "Page"
		    if (ereg("^([0-9]{1,4})$", $val) && $val > 0)
		    {
			$page		= (int)$val;
			$orderBy	= 'Line';
		    }
		    else
			$msg	.= "Page number '$val' " .
				   "must be a positive integer. ";
		    break;
		}		// "Page"

		case 'Family':
		{		// Family
		    // value must not contain a quote/apostrophe
		    // value is normally a number but there are exceptions
		    // and the field is stored as a string in the database
		    if (ereg("^[0-9A-Za-z]+$", $val))
		    {
			$family		= $val;
			$orderBy	= 'Line';
		    }
		    else
			$msg	.= "Family value '$val' " .
				   "contains an invalid character. ";
		    break;
		}		// "Family"
		
		case 'Surname':
		{		// Surname
		    $Surname	= $val;
		    // value may be a regular expression
		    if (ereg("[.+*^$()?]", $val))
		    {		// match pattern
			$SurnameOp	= ' REGEXP ';
		    }		// match pattern
		    else
		    {		// match text
			$SurnameOp	= ' = ';
		    }		// match text
		    break;
		}		// match in string

		case 'SurnameSoundex':
		{		// Do soundex comparison of surname
		    $SurnameSoundex	= true;
		    break;
		}		// Do soundex comparison of surname

		case 'Province':
		{		// used only by menu
		    if (ereg("^([A-Z]{2})$", $val))
			$Province	= $val;
		    else
			$msg	.= "Province code '$val' is invalid.  ";
		    break;
		}		// used only by menu

		case 'District':
		{		// district is simple text
		    if (is_string($val) && !ereg("^[0-9]{1,3}(\.5)?$", $val))
			$msg	.= "District number '$val' is invalid. ";
		    else
			$District	= $val;
		    break;
		}		// district is simple text

		case 'SubDistrict':
		{		// subdistrict
		    if (is_string($val))
			$rxcnt	= preg_match("/^([0-9.]+):([A-Za-z0-9]+)$/",
					     $val,
					     $matches);
		    else
			$rxcnt	= 0;
		    if ($rxcnt == 1)
		    {		// district:subdist format
			$District	= $matches[1];
			$SubDist	= $matches[2];
		    }		// district:subdist format
		    else
		    {		// subdist format
			$SubDist	= $val;
		    }		// subdist format
		    break;
		}		// sub district

		case 'Division':
		{		// Division, usually number but not always
		    $Division	= $val;
		    break;
		}		// Division

		case 'GivenNames':
		case 'Occupation':
		case 'BPlace':
		case 'Origin':
		case 'Nationality':
		case 'Religion':
		case 'Census':
		case 'Coverage':
		case 'Query':
		case 'Submit':
		{		// no validation
		    break;
		}		// no validation

		case 'Debug':
		case 'debug':
		{		// no validation
		    if (strtolower($val) == 'y')
			$debug	= true;
		    break;
		}		// no validation

		default:
		{		// other parameters simple text comparison
		    if (!ereg("^[a-zA-Z0-9 ']+$", $val))
		    {
			$msg .= $key . " contains invalid character.  ";
		    }
		    break;
		}	// ordinary parameter
	    }		// switch on parameter name
	}	// non-empty string value or array
    }		// foreach parameter

    // variables for constructing the SQL SELECT statement

    $npuri	= "QueryResponse$Census.php";	// for next and previous links
    $npand	= '?';		// adding parms to $npuri
    $npprev	= '';		// previous selection
    $npnext	= 'Count=20&amp;Offset=20';	// next selection
    $showLine	= false;	// include line number in display

    // the list of fields to be displayed and the form of the link clause
    // to obtain required information from the Districts and SubDistricts
    // tables depends upon the census year
    if ($Census < 1867)
    {		// pre-confederation
	$flds	= "Province, District, SubDistrict, Division, Page, Line,
		   Surname, GivenNames, Age, BYear, D_Name, SD_Name, 
		   BPlace, Occupation, IDIR, Sex";

	$link	= "JOIN Districts ON " .
		  "D_Census='$Province$Census' AND D_Id=District " .
		  "JOIN SubDistricts ON " .
		  "SD_Census='$Province$Census' AND SD_DistId=District AND " .
		  "SD_Id=SubDistrict AND SD_Div=Division";
    }		// pre-confederation
    else
    if ($Census == 1906)
    {		// first census of prairie provinces, no Occupation field
	$flds	= "District, SubDistrict, Division, Page, Line,
		   Surname, GivenNames, Age, BYear, D_Name, SD_Name, BPlace, IDIR, Sex";

	$link	= "JOIN Districts ON " .
		  "D_Census='CA$Census' AND D_Id=District " .
		  "JOIN SubDistricts ON " .
		  "SD_Census='CA$Census' AND SD_DistId=District AND " .
		  "SD_Id=SubDistrict AND SD_Div=Division";
    }		// post-confederation
    else
    {		// post-confederation
	$flds	= "District, SubDistrict, Division, Page, Line,
		   Surname, GivenNames, Age, BYear, D_Name, SD_Name,
		   BPlace, Occupation, IDIR, Sex";

	$link	= "JOIN Districts ON " .
		  "D_Census='CA$Census' AND D_Id=District " .
		  "JOIN SubDistricts ON " .
		  "SD_Census='CA$Census' AND SD_DistId=District AND " .
		  "SD_Id=SubDistrict AND SD_Div=Division";
    }		// post-confederation

// now that the fields have all been validated we can
// construct the WHERE clause of the query
if (strlen($msg) == 0)
{		// no errors in validation
    foreach ($_GET as $key => $val)
    {			// loop through all parameters
	if (is_string($val) && strlen($val) > 0)
	{		// non-empty string parameter
	    switch($key)
	    {		// switch on parameter name
		case 'Census':
		case 'Count':
		case 'Offset':
		case 'OrderBy':
		case 'Coverage':
		case 'Query':
		case 'Submit':
		case 'Debug':
		{		// handled outside of this loop
		    break;
		}		// handled outside of this loop

		case 'SurnameSoundex':
		case 'Province':
		case 'District':
		case 'SubDistrict':
		case 'Division':
		case 'Range':
		{		// handled outside loop but kept in URI
		    $npuri	.= "{$npand}{$key}={$val}";
		    $npand	= "&amp;"; 
		    break;
		}		// handled outside loop but kept in URI

		case 'BYear':
		{
		    $npuri	.= "{$npand}BYear={$val}";
		    $npand	= "&amp;"; 
		    break;
		}		// "BYear"

		case 'Page':
		{		// "Page"
		    if ($orderBy == 'Line')
		    {		// request for whole page
			$temp	= $val - 1;
			if ($temp > 0)
			    $npprev	= "Page=$temp";
			$temp	= 1 + $val;	// ensure numeric add
			$npnext	= "Page=$temp";
		    }		// request for whole page
		    else
		    {		// Page is just part of general request
			$npuri	.= "{$npand}{$key}={$val}";
			$npand	= "&amp;"; 
		    }		// Page is just part of general request
		    break;
		}		// "Page"

		case 'Family':
		{		// Family
		    if ($orderBy == "Line")
		    {		// request for whole family
			$temp	= $val - 1;
			if ($temp > 0)
			    $npprev	= "Family=$temp";
			$temp	= 1 + $val;	// ensure numeric add
			$npnext	= "Family=$temp";
		    }		// request for whole family
		    else
		    {		// Family is just part of general request
			$npuri	.= "{$npand}{$key}={$val}";
			$npand	= "&amp;"; 
		    }		// Family is just part of general request
		    break;
		}		// "Family"
		
		case 'Surname':
		{		// match anywhere in string
		    // value may be a regular expression
		    $npuri	.= "{$npand}{$key}=" . urlencode($val);
		    $npand	= "&amp;"; 
		    break;
		}		// match in string

		case 'GivenNames':
		case 'Occupation':
		case 'BPlace':
		case 'Origin':
		case 'Nationality':
		case 'Religion':
		case 'Address':
		case 'Location':
		case 'CauseOfDeath':
		{		// match anywhere in string
		    // value is a regular expression
		    $npuri	.= "{$npand}{$key}=" . urlencode($val);
		    $npand	= "&amp;"; 
		    break;
		}		// match in string

		case 'debug':
		case 'Debug':
		{
		    break;
		}

		default:
		{		// other parameters simple text comparison
		    $npuri	.= "{$npand}{$key}={$val}";
		    $npand	= "&amp;"; 
		    break;
		}	// ordinary parameter
	    }		// switch on parameter name
	}		// non-empty string value
    }		// foreach parameter


    // construct ORDER BY clause
    if ($orderBy == "Line")
    {		// display lines in original order
	$orderby	= 'ORDER BY Page, Line';
	$showLine	= true;
	$npuri		.= "{$npand}OrderBy=Line";
    }		// display lines in original order
    else
    {		// display lines in alphabetical order
	$orderby	= 'ORDER BY Surname, GivenNames, BYear';
	$showLine	= false;
	$npuri		.= "{$npand}OrderBy=Name";
	// construct LIMIT clause and URI components for backwards and forwards
	// browser links
	$limit	= " LIMIT {$count} OFFSET {$offset}";
	$tmp	= $offset - $count;
	if ($tmp < 0)
	    $npprev	= "";	// no previous link
	else
	    $npprev	= "Count={$count}&amp;Offset={$tmp}";
	$tmp	= $offset + $count;
	$npnext	= "Count={$count}&amp;Offset={$tmp}";
    }		// display lines in alphabetical order

    // include district information in URIs for previous and next page
    if (is_array($District))
    {		// add Districts to where clause	
        foreach($District as $id)
        {		// loop through values
	    if (strlen($id) > 0)
	    {		// district value supplied
	        $npuri	.= '&amp;District[]=' . $id;
	    }		// district value supplied
        }		// loop through values
    }	// array of Districts
    else
    if (strlen($District) > 0)
    {	// simple value
        $npuri	.= '&amp;District=' . $District;
    }	// simple value

    // include sub-district information in URIs for previous and next page
    if (is_string($SubDist))
	$SubDist	= explode(',', $SubDist);
    if (is_array($SubDist))
    {		// add SubDists to where clause	
        foreach($SubDist as $id)
        {		// loop through values
	    if (strlen($id) > 0)
	    {		// district value supplied
	        $npuri	.= '&amp;SubDistrict[]=' . $id;
	    }		// district value supplied
        }		// loop through values
    }	// array of SubDists
    else
    if (strlen($SubDist) > 0)
    {	// simple value
        $npuri	.= '&amp;SubDistrict=' . $SubDist;
    }	// simple value

    $npuri	.= '&amp;Division=' . $Division;

    $parms		= $_GET;
    $parms['join']	= $link;
    $censusLines	= new CensusLineSet($_GET,
					    $orderby,
					    $flds);
}		// no errors in validation
