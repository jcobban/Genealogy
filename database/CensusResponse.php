<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusResponse.php													*
 *																		*
 *  Display list of individuals matching a query of a census of Canada.	*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		Census			identifier of census 'XX9999'					*
 *		Province		optional 2 letter province code					*
 *		District		district number within 1871 census				*
 *		SubDistrict		subdistrict letter code within district			*
 *		Division		optional division within subdistrict			*
 *		OrderBy			'Name'	order response by surname, given names,	*
 *								and birth year							*
 *						'Line'	order response by position within form	*
 *		Family			if present limit response to members of a		*
 *						family with this identifier						*
 *		Page			if present limit response to individuals on 	*
 *						the specific page (typically with OrderBy=Line)	*
 *		Limit			limit number of rows to display at a time		*
 *		Offset			starting row within result set					*
 *		BYear			if present limit response by birth year			*
 *		Range			if present the range on either side of birth year*
 *		Surname			if present pattern match for surnames			*
 *		SurnameSoundex	if present match surnames by soundex code		*
 *		GivenNames		if present match given names by pattern			*
 *		Occupation		if present match occupation by pattern			*
 *		BPlace			if present match birth place by pattern			*
 *		Origin			if present match origin by pattern				*
 *		Nationality		if present match nationality by pattern			*
 *		Religion		if present match religion by pattern			*
 *		...																*
 *																		*
 *  History (of QueryCensus.php, which is superceded by this script):	*
 *		2010/10/07		fix warnings on keys Province and Division		*
 *		2010/11/21		support pre-confederation censuses				*
 *		2010/11/28		correct URL for displaying CensusForm from		*
 *						QueryDetail										*
 *		2010/12/22		use $connection->quote to encode the surname	*
 *						so that surnames with a quote can be used		*
 *		2011/01/07		fix error in surname search by regexp			*
 *		2011/02/16		fix syntax error in surname soundex search if	*
 *						quotes											*
 *		2011/03/27		use switch for parameter names					*
 *						always include OrderBy parameter in $npuri		*
 *						do not fail if OrderBy parameter missing		*
 *		2011/04/10		search whole database if no district specified	*
 *		2011/05/01		make the "See All Fields" hyperlink look like a	*
 *						button.											*
 *		2011/05/08		add Province to $npuri							*
 *		2011/07/13		1911 Census does need Division in table link	*
 *		2011/09/03		support a comma-separated list of				*
 *						district:subdistrict pairs in the				*
 *						SubDistrict parameter							*
 *		2011/09/04		add code to handle corrupted Districts or		*
 *						SubDistrictsi tables.  And support global		*
 *						$SubDist that is an array.						*
 *		2011/09/18		ignore buttons from IE7							*
 *		2011/10/09		significant restructuring to facilitate future	*
 *						maintenance.									*
 *						Improved error handling							*
 *						Cookie set here rather than by function call	*
 *						from census specific script						*
 *		2011/10/15		provide query specific identification string	*
 *						for header										*
 *		2012/03/31		support for IDIR link to family tree			*
 *		2012/04/01		if full page requested provide button to see	*
 *						image											*
 *		2012/04/07		fix bug in subdistricts with no division value	*
 *						fix bug that LIMIT set to 1						*
 *		2012/06/22		include province id in pre-confederation		*
 *						description										*
 *		2012/09/14		always include division in URI					*
 *		2012/09/25		pass census identifier to other scripts			*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/02/27		add Address, Location, CauseOfDeath to fields	*
 *						searched by regular expression					*
 *		2013/05/23		add Debug parameter								*
 *		2013/07/07		use classes SubDistrict and Page				*
 *		2014/06/05		urlencode parameters							*
 *		2014/08/10		remove setting of cookie from this module		*
 *																		*
 *  History (of QueryResponse1881.php, which is superceded)				*
 *		2010/09/11		update layout									*
 *		2010/11/21		new functionality in QueryCensus.php			*
 *		2011/08/26		all query response pages renamed to				*
 *						QueryResponseyyyy								*
 *						use actual buttons for actions that are links	*
 *		2011/10/13		support popup for mouseover forward and			*
 *						back links										*
 *		2012/03/31		support hyperlink based upon IDIR field			*
 *						phase out attrCell function						*
 *						combine name fields, remove age and district	*
 *						name											*
 *						color code names by sex							*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2014/02/15		display unknown gender in green					*
 *		2014/06/05		remove <table> used for layout					*
 *																		*
 *  History:															*
 *		2015/01/13		created											*
 *						add support for splitting screen with image		*
 *		2015/01/16		forward and backward links used old URL			*
 *		2015/01/20		add support for searching all censuses			*
 *						use JOIN rather than WHERE expression to join	*
 *						Districts and SubDistricts to Census table		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/05		include 1906 and 1916 census in ALL				*
 *						determine count for All response				*
 *		2015/07/08		use CommonForm.js								*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/28		missing help for 'Details' button				*
 *		2016/01/21		use class Census to get census information		*
 *						add id to debug trace div						*
 *						include http.js before util.js					*
 *		2016/04/25		replace ereg with preg_match					*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2017/09/12		use get( and set(								*
 *		2017/11/04		correct sex display of name						*
 *						include language in hyperlink to Person page	*
 *		2017/12/18		do not pass empty parameter values to			*
 *						CensusLineSet constructor						*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/17		parameter list of new CensusLineSet changed		*
 *		2018/02/22		"See All Fields" button used old URL for form	*
 *		2018/06/06		do not set SubDist to array in npprev and npnext*
 *						if there is only one value						*
 *		2018/11/12      simplify construction of forward and back links *
 *		2019/01/19      avoid failure on district id array              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/04/01      do not fail if district not specified           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// set default values that are overriden by parameters

$censusYear				= 1881;		    // census year
$censusId				= 'CA1881';	
$cc					    = 'CA';	    	// country code
$countryName			= 'Canada'; 	// country name
$province				= 'ON';	    	// province/state code
$provinceName			= 'Ontario';	// province/state name
$district				= '';	    	// default all districts
$subDistId				= '';	    	// default all subdistricts
$distId					= '';	    	// default all divisions
$limit					= 20;	    	// default max lines per page
$offset					= 0;	    	// default start first line of result
$range					= 1;	    	// default 1 year either side age/byear
$page					= null;	    	// default any page
$family					= null;	    	// default any family
$lang					= 'en';		    // default language
$orderBy				= 'Name';   	// default order alphabetically
$SurnameSoundex			= false;    	// check text of surname, not soundex
$result					= array();
$respDesc				= '';		
$search			        = ''; 
$respDescRows			= null;
$respDescSub			= null;
$respDescDiv			= null;
$respDescPage			= null;
$respDescFam			= null;
$parms					= array();

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
$parmCount	            = 0;		// number of search parameters
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{				// loop through all parameters
	if (is_string($value))
	    $textValue	= $value;
	else
	if (is_array($value))
	    $textValue	.= 'array';
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$textValue</td></tr>\n"; 
    if (is_string($value) && strlen($value) == 0)
		continue;

    if ((is_string($value) && strlen($value) > 0) ||
		is_array($value))
    {
		$fieldLc			= strtolower($key);
		switch($fieldLc)
		{			// switch on parameter name
		    case 'census':
		    {			// Census identifier
				$parms[$fieldLc]	= $value;
				$parmCount		    ++;
				$censusId		    = $value;
				$cc			        = strtoupper(substr($censusId, 0, 2));

				if (strtoupper(substr($censusId,2)) == 'ALL')
				{		// special census identifier to search all
				    $censusYear		= 'ALL';
				    $province		= 'CW';	// for pre-confederation
				}		// special census identifier
				else
				{		// full census identifier
				    $censusRec	= new Census(array('censusid'	=> $value));
				    if ($censusRec->isExisting())
				    {
						$censusYear	= substr($censusId, 2);
						$partof		= $censusRec->get('partof');
						if ($partof)
						{
						    $province	= $cc;
						    $cc		= $partof;
						}
				    }
				    else
						$msg	.= "Census value '$censusId' invalid. ";
				}		// full census identifier
				break;
		    }			// Census identifier

		    case 'count':
		    case 'limit':
		    {			// limit number of rows returned
				$parms['limit']		= $value;
				if (ctype_digit($value) && $value >= 5 && $value <= 99)
				    $limit		= intval($value);
				else
				    $msg		.=
				        "$key '$value' must be number between 5 and 99. ";
				break;
		    }			// limit number of rows returned

		    case 'offset':
		    {			// starting offset
				$parms[$fieldLc]		= $value;
				if (preg_match("/^([0-9]{1,6})$/", $value))
				    $offset		= (int)$value;
				else
				    $msg		.= "Row Offset must be an integer " .
								   "between 0 and 999,999. ";
				break;
		    }			// starting offset

		    case 'orderby':
		    {			// Override order of display
				if ($value == 'Name' || $value == 'Line')
				    $orderBy	= $value;
				else
				    $msg	.= "Invalid value of OrderBy='$value'";
				break;
		    }			// Override order of display

		    case 'byear':
		    {			// BYear
				$parms[$fieldLc]		= $value;
				$parmCount		++;
				if (preg_match("/^([0-9]{1,4})$/", $value) == 0 ||
				    $value < 1750 || $value > 2099)
				    $msg	.= "Birth Year '$value' must be an integer " .
							"and in the range 1750 to 2099.  ";
				break;
		    }			// BYear

		    case 'range':
		    {			// Range of ages or birth years
				$parms[$fieldLc]		= $value;
				$parmCount		++;
				if (preg_match("/^([0-9]{1,2})$/", $value) && 
				    $value >= 0 && $value <= 20)
				    $range		= intval($value);
				else
				    $msg		.= "Range '$value' must be an integer ".
								   "between 0 and 20";
				break;
		    }			// "Range"

		    case 'page':
		    {			// "Page"
				$parms[$fieldLc]		= $value;
				$parmCount++;
				if (preg_match("/^([0-9]{1,4})$/", $value) && 
				    $value > 0)
				{
				    $page		= (int)$value;
				    $orderBy		= 'Line';
				}
				else
				    $msg		.= "Page number '$value' " .
								   "must be a positive integer. ";
				break;
		    }			// "Page"

		    case 'family':
		    {			// Family
				$parms[$fieldLc]		= $value;
				$parmCount		++;
				// value must not contain a quote/apostrophe
				// value is normally a number but there are exceptions
				// and the field is stored as a string in the database
				if (preg_match("/^\w+$/", $value))
				{
				    $family		    = $value;
				    $orderBy		= 'Line';
				}
				else
				    $msg		    .= "Family value '$value' " .
								        "contains an invalid character. ";
				break;
		    }			// "Family"

		    case 'surname':
		    {			// Surname
				$parms[$fieldLc]		= $value;
				$parmCount++;
				$surname		        = $value;
				break;
		    }			// match in string

		    case 'surnamesoundex':
		    {			// Do soundex comparison of surname
				$parms[$fieldLc]		    = $value;
				$parmCount++;
				$SurnameSoundex		        = true;
				break;
		    }			// Do soundex comparison of surname

		    case 'province':
		    {			// used only by menu
				$parmCount++;
				if (preg_match("/^([A-Z]{2})$/", $value))
				{
				    $province		        = $value;
				    $parms[$fieldLc]		= $value;
				}
				else
				    $msg	.= "Province code '$value' is invalid.  ";
				break;
		    }			// used only by menu

		    case 'district':
		    {			// district is simple text
				$parmCount++;
				if (is_array($value) || is_numeric($value))
				{
				    $district	            = $value;
				    $parms[$fieldLc]		= $value;
				}
				else
				    $msg	.= "District number '$value' is invalid. ";
				break;
		    }			// district is simple text

		    case 'subdistrict':
		    {			// subdistrict
				$parmCount		++;
				if (is_string($value))
				{
				    $rxcnt	= preg_match("/^([0-9.]+):([A-Za-z0-9]+)$/",
								     $value,
								     $matches);
				}
				else
				    $rxcnt	= 0;

				if ($rxcnt == 1)
				{		// district:subdist format
				    $district	= $matches[1];
				    $subDistId	= $matches[2];
				    $parms['district']		= $district;
				    $parms['subdistrict']	= $subDistId;
				}		// district:subdist format
				else
				{		// subdist format
				    $subDistId	= $value;
				    $parms['subdistrict']	= $subDistId;
				}		// subdist format
				break;
		    }			// sub district

		    case 'division':
            {			// Division, usually integer but not always
                $matches                = array();
                if (preg_match('/\w+/', $value, $matches) == 1)
                {
                    $division           = $matches[0];
				    $parms[$fieldLc]	= $division;
				    $parmCount		    ++;
                    $distId			    = $division;
                }
				break;
		    }			// Division

		    case 'lang':
		    {			// language code
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value,0,2));
				break;
		    }			// language code

		    case 'givennames':
		    case 'occupation':
		    case 'bplace':
		    case 'origin':
		    case 'nationality':
		    case 'religion':
		    case 'coverage':
		    case 'query':
		    case 'submit':
		    case 'debug':
		    {			// no validation
				$parms[$fieldLc]		= $value;
				$parmCount	++;
				break;
		    }			// no validation

		    default:
		    {			// other parameters simple text comparison
				$parms[$fieldLc]		= $value;
				$parmCount	++;
				if (preg_match("/^[a-zA-Z0-9 ']+$/", $value) == 0)
				{
				    $msg .= $key . " contains invalid character.  ";
				}
				break;
		    }			// ordinary parameter
		}			// switch on parameter name
    }				// non-empty string value or array
}				// foreach parameter
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";
if ($parmCount == 0)
    $msg	.= 'No parameters passed. ';

// start constructing the forward and back links
$queryString    = urldecode($_SERVER['QUERY_STRING']);
$queryString    = preg_replace('/\w+=&/', '', $queryString);
$queryString    = preg_replace('/&\w+=$/', '', $queryString);
$queryString    = preg_replace('/OrderBy=\w+&/i', '', $queryString);
$queryString    = preg_replace('/&Page=\d+/i', '', $queryString);
$queryString    = preg_replace('/&Family=\d+/i', '', $queryString);
if ($debug)
    $warn       .= "<p>query='$queryString'</p>\n";
$npuri		    = "CensusResponse.php?$queryString";	// base query
$npPrev		    = '';		                            // previous selection
$npNext		    = '';	                                // next selection
$showLine	    = false;	                            // include line number

// the list of fields to be displayed and the form of the link clause
// to obtain required information from the Districts and SubDistricts
// tables depends upon the census year
if (ctype_digit($censusYear))
{			// census year
    if ($censusYear < 1867)
    {			// pre-confederation
		$flds	= "Province, District, SubDistrict, Division, Page, Line," .
				      "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
				      "BPlace, Occupation, IDIR, Sex";
    
		$join  = "JOIN Districts ON " .
						"(D_Census='$province$censusYear' AND D_Id=District) " .
				 "JOIN SubDistricts ON " .
						"(SD_Census='$province$censusYear' AND " .
						"SD_DistId=District AND " .
		 		"SD_Id=SubDistrict AND SD_Div=Division) ";
    }			// pre-confederation
    else
    if ($censusYear == 1906)
    {			// first census of prairie provs, no Occupation field
		$flds	= "D_Province as Province, District, SubDistrict, Division, Page, Line," .
				      "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
				      "BPlace, '' AS Occupation, IDIR, Sex";
    
		$join	= "JOIN Districts ON " .
						"(D_Census='CA$censusYear' AND D_Id=District) " .
				  "JOIN SubDistricts ON " .
						"(SD_Census='CA$censusYear' AND " .
						"SD_DistId=District AND " .
		 		"SD_Id=SubDistrict AND SD_Div=Division) ";
    }			// post-confederation
    else
    {			// post-confederation
		$flds	= "D_Province as Province, District, SubDistrict, Division, Page, Line," .
				      "Surname, GivenNames, Age, BYear, D_Name, SD_Name," .
				      "BPlace, Occupation, IDIR, Sex";
    
		$join	= "JOIN Districts ON " .
						"(D_Census='CA$censusYear' AND D_Id=District) " .
				  "JOIN SubDistricts ON " .
						"(SD_Census='CA$censusYear' AND " .
						"SD_DistId=District AND " .
		 		"SD_Id=SubDistrict AND SD_Div=Division) ";
    }			// post-confederation
}			// census year
else
    $flds	= "Province, District, SubDistrict, Division, Page, Line,Surname, GivenNames, Age, BYear, D_Name, SD_Name,BPlace, Occupation, IDIR, Sex";

// now that the fields have all been validated we can
// construct the WHERE clause of the query
if (strlen($msg) == 0)
{		// no errors in validation
    if (isset($join))
		$parms['join']	= $join;
    foreach ($_GET as $key => $val)
    {			// loop through all parameters
		if (is_string($val) && strlen($val) > 0)
		{		// non-empty string parameter
		    switch(strtolower($key))
		    {		// switch on parameter name
				case 'page':
				{		// "Page"
				    if ($orderBy == 'Line')
				    {		// request for whole page
						$temp	= $val - 1;
						if ($temp > 0)
						    $npPrev	= "Page=$temp";
						$temp	= 1 + $val;	// ensure numeric add
						$npNext	= "Page=$temp";
				    }		// request for whole page
				    break;
				}		// "Page"

				case 'family':
				{		// Family
				    if ($orderBy == "Line")
				    {		// request for whole family
						$temp	= $val - 1;
						if ($temp > 0)
						    $npPrev	= "Family=$temp";
						$temp	= 1 + $val;	// ensure numeric add
						$npNext	= "Family=$temp";
				    }		// request for whole family
				    break;
				}		// "Family"

				default:
				{		// other parameters simple text comparison
				    break;
				}	// ordinary parameter
		    }		// switch on parameter name
		}		// non-empty string value
    }		// foreach parameter


    // construct ORDER BY clause
    if ($orderBy == "Line")
    {		// display lines in original order
		$showLine	= true;
		$npuri		.= "&OrderBy=Line";
		$limit		= 99;
    }		// display lines in original order
    else
    {		// display lines in alphabetical order
		$showLine	= false;
		$npuri		.= "&OrderBy=Name";
		// URI components for backwards and forwards
		// browser links
		$tmp	= $offset - $limit;
		if ($tmp < 0)
		    $npPrev	= "";	// no previous link
		else
		    $npPrev	= "Limit={$limit}&amp;Offset={$tmp}";
		$tmp	= $offset + $limit;
		$npNext	= "Limit={$limit}&amp;Offset={$tmp}";
    }		// display lines in alphabetical order

    // include district information in URIs for previous and next page
    if (isset($district))
    {
	    $getParms		            = array();
        if ($censusYear == 1851 || $censusYear == 1861)
	    	$getParms['d_census']	= $province . $censusYear;
        else
            $getParms['d_census']	= $censusId;
        if (is_array($district))
            $dist_id        	    = reset($district);
        else
            $dist_id                = $district;
        if (!is_null($dist_id) && $dist_id != 0)
        {
            $getParms['d_id']   	= $dist_id;
            $districtObj	        = new District($getParms);
            $province		        = $districtObj->get('d_province');
        }
        else
            $districtObj	        = null;
    }
    else
    {
        $districtObj	            = null;
    }

    // execute the query
    $parms['order']	        = $orderBy;
    $result		            = new CensusLineSet($parms, $flds);
    if ($debug)
    {
		$warn		.= "<p>CensusResponse.php: " . __LINE__ .
							" parms=" . print_r($parms, true) . "</p>\n";
		$info		= $result->getInformation();
		$warn		.= "<p>CensusResponse.php: " . __LINE__ .
							" query='" . $info['query'] . "'</p>\n";
    }

    // add additional data to result rows
    $class		= 'odd';
    foreach($result as $i => $row)
    {				// loop through lines of response
        if (is_null($row['division']))
            $row['division']    = '';
		$row['i']       	    = $i;
		$row['class']	        = $class;
		if ($class == 'odd')
		    $class	            = 'even';
		else
		    $class	            = 'odd';
		$tempId		            = $row['censusid'];
		if (is_string($tempId))
		{
		    $row['census']	    = substr($tempId,2);
		}
		else
		{
		    $row['censusid']	= $censusId;
		    $row['census']	    = substr($censusId,2);
		}
		if (!isset($row['province']))
		    $row['province']	= $province;
		$district	= $row['district']; 
		if (substr($district,-2) == '.0')
		    $row['district']	= substr($district, 0, strlen($district) - 2); 
		$sex	                = $row['sex'];
		if ($sex == 'M' || $sex == 'm')
		    $sex	            = 'male';
		else
		if ($sex == 'F' || $sex == 'f')
		    $sex	            = 'female';
		else
		    $sex	            = 'unknown';
		$row['sex']				= $sex;
		$idir					= $row['idir'];
		$givennames				= $row['givennames'];
		$surname	= $row['surname'];
		if ($idir > 0)
		    $row['fullname']	= "<a href='/FamilyTree/Person.php?idir=$idir&amp;lang=$lang' target='_blank' class='$sex'>\n" .
							  "\t    <strong>$surname</strong>,\n" .
							  "\t    $givennames\n" .
							  "\t  </a>\n";
		else
		    $row['fullname']	= "\t    <strong>$surname</strong>,\n" .
							  "\t    $givennames\n";
		//$result[$i]		= $row;
    }				// loop through lines of response
    $result->rewind();

    // option to show everything on page or update
    if ($showLine)
    {			// include line column in display
		$search			= "?Census=$censusId";
		$respDescRows		= null;
		if (is_array($subDistId))
		    $SdId		= $subDistId[0];
		else
		    $SdId		= $subDistId;
		$search			.= "&amp;Province=$province";
		if (is_array($district))
		{
		    foreach($district as $val)
				$search	.= "&amp;District[]=" . $val;
		}
		else
		    $search	.= "&amp;District=" . $district;
		$search		.= "&amp;SubDistrict=" . $SdId .
						   "&amp;Division=" . $distId;
		if ($page)
		    $search	.= "&amp;Page=" . $page;
		else
		    $search	.= "&amp;Family=" . $family;
		if ($censusYear < 1867)
		    $censusId	= $province . $censusYear;
		else
		    $censusId	= 'CA' . $censusYear;

		if (is_array($district))
		    $dId	= $district[0];
		else
		    $dId	= $district;
		if (is_array($SdId))
		    $subdId	= $SdId[0];
		else
		    $subdId	= $SdId;

		// determine division identifier to use in query
		$divId		= $distId;
		if (strlen($distId) > 0)
		{
		    $d		= strpos($distId, ':');
		    if ($d !== false)
		    {		// separator found
				$dId	= substr($distId, 0, $d);
				$subdId	= substr($distId, $d+1);
				$divId	= '';
		    }		// separator found
		}
		if ($dId == floor($dId))
		    $dId	= floor($dId);

		$sdParms	= array(
						'census'	=> $censusId,
						'distId'	=> $dId, 
						'SD_Id'		=> $subdId,
						'SD_Div'	=> $divId);
		$subDistrict	= new SubDistrict($sdParms);
		if ($lang == 'fr')
		    $DName		= $subDistrict->get('d_nom');
		else
		    $DName		= $subDistrict->get('d_name');
		$SubDName	= $subDistrict->get('sd_name');
		$page1		= $subDistrict->get('sd_page1');
		$imageBase	= $subDistrict->get('sd_imagebase');
		$relFrame	= $subDistrict->get('sd_relframe');
		$pages		= $subDistrict->get('sd_pages');
		$bypage		= $subDistrict->get('sd_bypage');

		// identify requested page or family
		$respDescSub = array('dId'		=> $dId,
						     'DName'		=> $DName,
						     'subdId'		=> $subdId,
						     'SubDName'		=> $SubDName,
						     'province'		=> '');
		if (strlen($divId) > 0)
		    $respDescDiv = array( 'divId'	=> $divId);
		if ($censusYear < 1867)
		    $respDescSub['province']	= $province;
		if ($page)
		    $respDescPage	= array('page'		=> $page);
		else
		    $respDescFam	= array('family'	=> $family);
    }		// show line number column
    else
    {
		$info		    	= $result->getInformation();
		$totalrows	        = $info['count'];
		$first		        = $offset + 1;
		$last		        = min($offset + $limit, $totalrows);
		$respDescRows		= array('first'		=> $first,
								    'last'		=> $last,
								    'totalrows'	=> $totalrows);
    }
}		// no errors in validation

if (strtoupper($censusYear) == 'ALL')
    $censusYear	= 'All';
$breadCrumbs	= array(array(	'url'	=> "/genealogy.php?lang=$lang",
							'label' => 'Genealogy'), 
						array(	'url'	=> '/genCanada.html',
							'label' => $countryName),
						array(	'url'	=> "/database/genCensuses.php?lang=$lang",
							'label' => 'Censuses'),
						array(	'url'	=> "/database/QueryDetail.php?Census=CA$censusYear&lang=$lang",
							'label' =>"New $censusYear Census Query"));
if ($showLine && $page)
{		// display whole page
    if (is_array($district))
    {
		$distText	= print_r($district, true);
		$distParm	= '';
		foreach($district as $val)
		{
		    if (is_numeric($val) && $val == floor($val))
				$val	= floor($val);
		    $distParm	.= "&amp;District[]=$val";
		}
    }
    else
    {
		if (is_numeric($district) && $district == floor($district))
		    $district	= floor($district);
		$distText	= $district;
		$distParm	= "&amp;District=$district";
    }
    // add links for census hierarchy	
    $breadCrumbs[]	= array('url'	=> "CensusUpdateStatus.php?Census=$censusId",
							'label'	=> "$censusYear Summary");
    if (is_numeric($district))
    {
		$breadCrumbs[]	= array('url'	=> "CensusUpdateStatusDist.php?Census=$censusId&amp;Province=$province&amp;District=$district",
							'label'	=> "District $distText $DName Summary");
    }
    $breadCrumbs[]	= array('url'	=> "CensusUpdateStatusDetails.php?Census=$censusId&amp;Province=$province$distParm&amp;SubDistrict={$subDistId[0]}&amp;Division=$divId",
							'label'	=> "Division Details");
}		// display whole page

    $title	= "$censusYear Census of $countryName Query Response";
    if ($showLine)
		$showLineFile	= 'Line';
    else
		$showLineFile	= '';
    if ($censusYear == 'All')
		$file	= "CensusResponseAll$showLineFile$lang.html";
    else
		$file	= "CensusResponse$showLineFile$lang.html";
    $template	= new FtTemplate($file);

    $template->set('CENSUSYEAR', 		$censusYear);
    $template->set('COUNTRYNAME',		$countryName);
    $template->set('CENSUSID',			$censusId);
    $template->set('PROVINCE',			$province);
    $template->set('PROVINCENAME',		$provinceName);
    $template->set('LANG',			$lang);
    if (isset($dId))
    {
		$template->set('DISTRICT',		$dId);
		$template->set('DISTRICTNAME',		$DName);
		$template->set('SUBDISTRICT',		$subdId);
		$template->set('SUBDISTRICTNAME',	$SubDName);
		$template->set('DIVISION',		$divId);
    }
    $template->updateTag('respdescrows',	$respDescRows);
    $template->updateTag('respdescsub',		$respDescSub);
    $template->updateTag('respdescdiv',		$respDescDiv);
    $template->updateTag('respdescfam',		$respDescFam);
    $template->updateTag('respdescpage',	$respDescPage);
    $template->set('CENSUS',			$censusYear);
    $template->set('SEARCH',			$search);
    $template->set('CONTACTTABLE',		'Census' . $censusYear);
    $template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
    if ($showLine && $page)
    {		// display whole page	
		$pageRec	= new Page($subDistrict, $page);
		$image		= $pageRec->get('pt_image');
		$template->set('IMAGE',	$image);
    }
    else
		$template->updateTag('buttonRow', null);

    if (strlen($npPrev) > 0)
    {
		$template->updateTag('topPrev', 
						     array('npPrev' => "$npuri&$npPrev"));
		$template->updateTag('botPrev', 
						     array('npPrev' => "$npuri&$npPrev"));
    }
    else
    {
		$template->updateTag('topPrev', null);
		$template->updateTag('botPrev', null);
    }
    if (strlen($npNext) > 0)
    {
		$template->updateTag('topNext',
						     array('npNext' => "$npuri&$npNext"));
		$template->updateTag('botNext', 
						     array('npNext' => "$npuri&$npNext"));
    }
    else
    {
		$template->updateTag('topNext', null);
		$template->updateTag('botNext', null);
    }

    // update the popup for explaining the action taken by arrows
    if ($family)
    {
		$template->updateTag('familyminusonepre',
						array('family - 1'=> ($family - 1)));
		$template->updateTag('familyplusonepre',
						array('family + 1'=> ($family + 1)));
		$template->updateTag('familyminusonepost',
						array('family - 1'=> ($family - 1)));
		$template->updateTag('familyplusonepost',
						array('family + 1'=> ($family + 1)));
		$template->updateTag('rowminuscountpre', null);
		$template->updateTag('rowpluscountpre', null);
		$template->updateTag('rowminuscountpost', null);
		$template->updateTag('rowpluscountpost', null);
		$template->updateTag('pageminusonepre', null);
		$template->updateTag('pageplusonepre', null);
		$template->updateTag('pageminusonepost', null);
		$template->updateTag('pageplusonepost', null);
    }
    else
    if ($page)
    {
		$template->updateTag('pageminusonepre',
						array('page - 1'=> ($page - 1)));
		$template->updateTag('pageplusonepre',
						array('page + 1'=> ($page + 1)));
		$template->updateTag('pageminusonepost',
						array('page - 1'=> ($page - 1)));
		$template->updateTag('pageplusonepost',
						array('page + 1'=> ($page + 1)));
		$template->updateTag('familyminusonepre', null);
		$template->updateTag('familyplusonepre', null);
		$template->updateTag('familyminusonepost', null);
		$template->updateTag('familyplusonepost', null);
		$template->updateTag('rowminuscountpre', null);
		$template->updateTag('rowpluscountpre', null);
		$template->updateTag('rowminuscountpost', null);
		$template->updateTag('rowpluscountpost', null);
    }
    else
    {
		$template->updateTag('rowminuscountpre',
						 array('offset - limit + 1' => ($offset - $limit + 1)));
		$template->updateTag('rowpluscountpre',
						 array('offset + limit + 1' => ($offset + $limit + 1)));
		$template->updateTag('rowminuscountpost',
						 array('offset - limit + 1' => ($offset - $limit + 1)));
		$template->updateTag('rowpluscountpost',
						 array('offset + limit + 1' => ($offset + $limit + 1)));
		$template->updateTag('familyminusonepre', null);
		$template->updateTag('familyplusonepre', null);
		$template->updateTag('familyminusonepost', null);
		$template->updateTag('familyplusonepost', null);
		$template->updateTag('pageminusonepre', null);
		$template->updateTag('pageplusonepre', null);
		$template->updateTag('pageminusonepost', null);
		$template->updateTag('pageplusonepost', null);
    }

    if (strlen($msg) > 0)
		$template->updateTag('buttonForm', null);
    else
		$template->updateTag('Row$i',
						     $result);
    $template->display();
