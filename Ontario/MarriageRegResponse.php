<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegResponse.php												*
 *																		*
 *  Display a report of individuals whose marriage matches the			*
 *  requested pattern.  This is invoked by method='get' from			*
 *  MarriageRegQuery.html.												*
 *																		*
 *  Parameters:															*
 *		Count															*
 *		Offset															*
 *		RegYear															*
 *		RegNum															*
 *		Surname															*
 *		GivenNames														*
 *		Occupation														*
 *		Religion														*
 *		FatherName														*
 *		MotherName														*
 *		Place															*
 *		Date															*
 *		SurnameSoundex													*
 *		BYear															*
 *		Range															*
 *		RegDomain														*
 *		RegCounty														*
 *		RegTownship														*
 *		OriginalVolume													*
 *		OriginalPage													*
 *		Originalitem													*
 *																		*
 *  History: 															*
 *		2010/11/11		ambiguity on surname REGEXP comparison			*
 *		2011/01/09		handle request to see all registrations for a	*
 *						year in order by registration number			*
 *						improve separation of PHP and HTML				*
 *		2011/08/05		add birth year to display						*
 *		2011/10/27		rename to MarriageRegResponse.php				*
 *		2011/11/05		use <button> instead of link for action			*
 *						support mouseover help							*
 *		2012/03/28		make name a link to the family tree if			*
 *						the marriage registration is referenced			*
 *						combine surname and given names in report		*
 *		2012/04/07		only display 20 lines when updating				*
 *		2012/05/01		correct row number in empty rows preceding		*
 *						first active line.								*
 *		2013/01/09		add button to delete a marriage					*
 *		2013/02/27		give clear message if invoked with no parameters*
 *		2013/04/04		replace deprecated call to doQuery				*
 *		2013/04/28		also display spouse								*
 *		2013/05/12		1870 marriages in volume 7 only use numbers		*
 *						ending in 1, 2, or 3.							*
 *		2013/05/14		correct previous and next links in case where	*
 *						explicit minimum registration number was		*
 *						specified and the user is not authorised to		*
 *						update the database								*
 *						and adjust for 1870,1871,1872					*
 *		2013/08/04		use pageTop and pageBot to standardize appearance*
 *		2013/11/15		handle missing database connection gracefully	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/27		use CSS to layout <h1> and pagination links		*
 *		2014/02/10		include overall status and status for year		*
 *						in breadcrumbs if search includes registration	*
 *						year											*
 *						generate valid HTML page on SQL errors			*
 *						display domain name in page title and header 1	*
 *		2014/04/04		change range of volumes for 1871 marriages		*
 *		2014/06/06		add option to explicitly set displayed order	*
 *						issue warning message for unexpected parms		*
 *	    2014/08/24	    birth year range was too restrictive		    *	
 *		2014/10/11		pass domain name to child dialogs				*
 *						support delete confirmation dialog				*
 *		2015/05/01		PHP print statements were corrupted				*
 *						do not display domain id, it is implied by		*
 *						the title										*
 *		2015/07/01		use LEFT JOIN so that the display reflects		*
 *						the case where there is a record in Marriage	*
 *						with no corresponding records in MarriageIndi	*
 *						JOIN the separate groom, bride, and minister	*
 *						records so the entire results can be obtained	*
 *						in a single request to the server				*
 *						Display only one line per marriage				*
 *						Display groom before bride in all lines			*
 *						Remove display of role of first spouse and add	*
 *						calculated birth year of second spouse			*
 *						Present "Delete" button for records where there	*
 *						is a record in 'Marriage' even if no record[s]	*
 *						in 'MarriageIndi'								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/16		missing operator between groom and bride's		*
 *						birth year calculations							*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/07/04		misspelled $regdomain in delete dialog			*
 *		2016/08/25		correct calculation of number of empty pages	*
 *						for 1870 through 1872							*
 *		2016/11/28		change increment for 1872						*
 *		2017/02/07		use class Country								*
 *		2017/02/18		add fields OriginalVolume, OriginalPage, and	*
 *						Originalitem									*
 *						use prepared statement for query				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  emptyRows																*
 *																		*
 *  Generate empty rows corresponding to records that do not				*
 *  already exist in the database, allowing them to be created by		*
 *  an authorized contributor.												*
 *																		*
 *  Input:																*
 *		$count				number of extra rows to generate				*
 ************************************************************************/
function emptyRows($count)
{
    global $regYear;
    global $regNum;
    global $lastRegNum;
    global $rowClass;
    global $action;
    global $rownum;

    for ($i = 0; $i < $count; $i++)
    {
    	$rownum++;
    	if ($regYear == 1870 && $regNum > 70000 && ($regNum % 10) == 4)
    	    $regNum	+= 7;
    	if ($regYear == 1871 && $regNum > 120000 && ($regNum % 10) == 4)
    	    $regNum	+= 7;
    	if ($regYear == 1872 && $regNum > 120000 && ($regNum % 10) == 4)
    	    $regNum	+= 7;
    	if ($regNum >= $lastRegNum)
    	    break;
?>
      <tr>
    	<td class='left'>
    	    <button type='button'
    					id='Action<?php print $regYear . $regNum; ?>'>
    			 <?php print $action; ?> 
    	    </button>
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $regYear; ?>
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $regNum; ?>
    	</td>
    	<td class='<?php print $rowClass; ?> center'>&nbsp;</td>
    	<td class='<?php print $rowClass; ?>'>&nbsp;</td>
    	<td class='<?php print $rowClass; ?> right'>&nbsp;</td>
    	<td class='<?php print $rowClass; ?>'>&nbsp;</td>
    	<td class='<?php print $rowClass; ?>'>&nbsp;</td>
    	<td class='<?php print $rowClass; ?>'>&nbsp;</td>
      </tr>
<?php
    	if ($rowClass == 'odd')
    	    $rowClass	= 'even';
    	else
    	    $rowClass	= 'odd';
    	$regNum++;
    }		// loop filling in extra rows
}		// emptyRows

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// variables for constructing the SQL statement
// join expression for the two tables from which the information is extracted
$join				= 'LEFT JOIN MarriageIndi AS Groom ON ' .
    					'Marriage.M_RegYear 		= Groom.M_RegYear AND ' .
    					'Marriage.M_RegNum 		= Groom.M_RegNum AND ' .
    					'Marriage.M_RegDomain 		= Groom.M_RegDomain AND ' .
    					"Groom.M_Role 		= 'G' " .
        			  'LEFT JOIN MarriageIndi AS Bride ON ' .
    					'Marriage.M_RegYear 		= Bride.M_RegYear AND ' .
    					'Marriage.M_RegNum 		= Bride.M_RegNum AND ' .
    					'Marriage.M_RegDomain 		= Bride.M_RegDomain AND ' .
    					"Bride.M_Role 		= 'B' " .
        			  'LEFT JOIN MarriageIndi AS Minister ON ' .
    					'Marriage.M_RegYear 		= Minister.M_RegYear AND ' .
    					'Marriage.M_RegNum 		= Minister.M_RegNum AND ' .
    					'Marriage.M_RegDomain 		= Minister.M_RegDomain AND ' .
    					"Minister.M_Role 		= 'M' " .
$sel				= '';

// where expression
$selroles			= 0;		// bit mask of roles to include GBM
$limit				= '';		// limit on which rows to return
$prefix				= 'M_';		// common prefix of table field names
$cprefix			= 'Marriage.M_';// prefix of table field names
$and				= '';		// logical and operator in SQL expressions
$flds				= 'Marriage.M_RegDomain, ' .
        			  'Marriage.M_RegYear, Marriage.M_RegNum, ' .
         			  'Marriage.M_Date, Marriage.M_Place, ' .
        			  'Groom.M_Surname AS G_Surname, ' .
        			  'Groom.M_GivenNames AS G_Given, ' .
        			  'Groom.M_BYear AS G_BYear, Groom.M_IDIR AS G_IDIR, ' .
        			  'Bride.M_Surname AS B_Surname, ' .
        			  'Bride.M_GivenNames AS B_Given, ' .
        			  'Bride.M_BYear AS B_BYear, Bride.M_IDIR AS B_IDIR, ' .
        			  'Minister.M_Surname AS M_Surname, ' .
        			  'Minister.M_GivenNames AS M_Given, ' .
        			  'Minister.M_IDIR AS M_IDIR';
$numericOrd			= 'Marriage.M_RegYear, Marriage.M_RegNum ';
$nominalOrd			= 'Groom.M_Surname, Groom.M_GivenNames, ' .
        			  'Marriage.M_RegYear, Marriage.M_RegNum ';
$orderby			= $numericOrd;	// default
$npuri				= 'MarriageRegResponse.php';
$npand				= '?';		// adding parms to $npuri
$npprev				= '';		// previous selection
$npnext				= '';		// next selection
$count				= 20;
$offset				= 0;
$expand				= canUser('edit');
$oexpand			= $expand;
$regYear			= 0;
$regNum				= 0;
$lastRegNum			= 0;
$originalVolume		= null;
$originalPage		= null;
$originalItem		= null;
$cc				    = 'CA';
$countryName		= 'Canada';
$regDomain			= 'CAON';
$domainName			= 'Ontario';
$needSpouse			= false;
$lang				= 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// first extract the values of all supplied parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{			// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {		// switch on parameter name
    	case 'count':
    	{		// limit number of rows returned
    	    if (!preg_match("/^([0-9]{1,2})$/", $value))
    	    {
    			$msg  .= "Row count must be number between 1 and 99. ";
    			$count	= 20;		// replace with default
    	    }
    	    else
    			$count	= $value;
    	    break;
    	}		// limit number of rows returned

    	case 'offset':
    	{		// starting offset
    	    if (!preg_match("/^([0-9]{1,6})$/", $value))
    	    {
    			$msg .= 'Row offset must be number between 0 and 999,999. ';
    			$offset	= 0;
    	    }
    	    else
    			$offset	= $value;
    	    if ($offset > 0)
    	    {
    			$oexpand	= $expand;
    			$expand		= false;
    	    }
    	    break;
    	}		// starting offset

    	case 'regdomain':
    	{
    	    // interpret domain code
    	    $domainObj	= new Domain(array('domain'	=> $value,
    							   'language'	=> 'en'));
    	    if ($domainObj->isExisting())
    	    {
    			$regDomain	= $value;
    			$cc		= substr($regDomain, 0, 2);
    			$countryObj	= new Country(array('code' => $cc));
    			$countryName	= $countryObj->getName();
    			$domainName	= $domainObj->get('name');
    	    }
    	    else
    	    {
    			$domainName	= 'Domain : ' . $regDomain;
    			$msg	.= "Domain '$regDomain' must be a supported two character country code followed by a state or province code. ";
    	    }
    	    break;
    	}		// RegDomain

    	case 'regyear':
    	{		// year of registration
    	    if (strlen($value) > 0)
    	    {		// value specified
    			if (!preg_match("/^([0-9]{1,4})$/", $value))
    			{
    			    $msg	.= $key . " must be a number. ";
    			}
    			else
    			if  (($value < "1867") || ($value > 2000))
    			{
    			    $msg	.= $key . " out of range. ";
    			}
    			else
    			{	// valid
    			    $regYear	= $value;
    			    $breadcrumbs["MarriageRegStats.php?regdomain=$regDomain"] = "$domainName Status";
    			    $breadcrumbs["MarriageRegYearStats.php?regdomain=$regDomain&regyear=$regYear"] = "Status $regYear";
    			}	// valid
    	    }		// value specified
    	    break;
    	}		// year of registration

    	case 'regnum':
    	{		// RegNum
    	    if (strlen($value) > 0)
    	    {		// value specified
    	    if (!preg_match("/^([0-9]{1,6})$/", $value))
    	    {
    			$msg	.= 'RegNum must be a number. ';
    	    }
    	    else
    			$regNum	= $value;
    	    }		// value specified
    	    break;
    	}		// registration number

    	case 'originalvolume':
    	{		// original volume
    	    $originalVolume	= $value;
    	    break;
    	}		// original volume

    	case 'originalpage':
    	{		// original page number
    	    $originalPage	= $value;
    	    break;
    	}		// original page number

    	case 'originalitem':
    	{		// original Item position
    	    $originalItem	= $value;
    	    break;
    	}		// original Item position

    	case 'inchusband':
    	{
    	    $selroles	|= 1;	// mask bit for grooms
    	    break;
    	}		// include grooms in search

    	case 'incwife':
    	{
    	    $selroles	|= 2;	// mask bit for brides
    	    break;
    	}		// include brides in search

    	case 'incminister':
    	{
    	    $selroles	|= 4;	// mask bit for ministers
    	    break;
    	}		// include ministers in search

    	case 'lang':
        {
            if (strlen($value) >= 2)
    	        $lang       = strtolower(substr($value, 0, 2));
    	    break;
    	}		// include ministers in search

    	case 'debug':
    	{		// debug handled by common.inc
    	    break;
    	}		// debug
    }			// switch on parameter name
}			// loop through all parameters
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";

// the hierarchy of URLs to display in the top and bottom of the page
$breadcrumbs	= array(
    			'/genealogy.php'	=> 'Genealogy',
    			"/gen$cc.html"		=> $countryName,
    			"/Canada/genProvince.php?domain=$regDomain"	=> $domainName,
    			'MarriageRegQuery.html'	=> 'New Marriage Query');

// user did not include any of husband, wife, or minister
if ($selroles == 0)
    $selroles		= 3;	// default to husband and wife


// action taken depends upon whether the user is authorized to
// update the database
if (canUser('edit'))
{
    $action	= "Update";
    $title	= "$domainName: Marriage Registration Update";
}
else
{
    $action	= "Details";
    $title	= "$domainName: Marriage Registration Query";
}

// construct the various portions of the SQL SELECT statement
$sqlParms	= array();

foreach ($_GET as $key => $value)
{			// loop through all parameters
    if (strlen($value) > 0)
    {
    	switch(strtolower($key))
    	{		// switch on parameter name
    	    case 'count':
    	    case 'offset':
    	    case 'debug':
    	    {		// already handled
    			break;
    	    }		// already handled

    	    case 'regyear':
    	    {		// numeric field
    			$sel		.= "$and Marriage.M_RegYear=? ";
    			$sqlParms[]	= $value;
    			$and		= 'AND';
    			$npuri		.= "{$npand}RegYear=$value";
    			$npand		= '&amp;'; 
    			break;
    	    }		// RegYear

    	    case 'regnum':
    	    {		// RegNum
    			$regNum		= $value;
    			$lastRegNum	= $regNum + $count;
    			if ($regYear <= 0)
    			{
    			    $msg	.=
    	'Registration Number may only be specified with Registration Year. ';
    			}
    			else
    			{
    			    $sel	.= "$and Marriage.M_RegNum>=? ";
    			    $sqlParms[]	= $value;
    			    $and	= 'AND';
    			    if ($expand)
    			    {
    					if ($regYear <= 1872 && $regNum > 10000)
    					    $lastRegNum	= $regNum + 10 * floor($count / 3 + 1);
    					$sel.= "AND Marriage.M_RegNum<? ";
    					$sqlParms[]	= $lastRegNum;
    			    }
    			}
    			break;
    	    }		// RegNum

    	    case 'surname':
    	    {
    			$needSpouse	= true;
    			if (preg_match("/[.+*^$]/", $value))
    			{		// match pattern
    			    $or		= '';
    			    $sel	.= "$and (";
    			    if ($selroles & 1)
    			    {
    					$sel	.= "Groom.M_Surname REGEXP ? ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 2)
    			    {
    					$sel	.= "$or Bride.M_Surname REGEXP ? ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 4)
    			    {
    					$sel	.= "$or Minister.M_Surname REGEXP ? ";
    					$sqlParms[]	= $value;
    			    }
    			    $sel	.= ') ';
    			    $and	= 'AND';
    			}		// match pattern
    			else
    			if (array_key_exists("SurnameSoundex", $_GET))
    			{		// match soundex
    			    $or		= '';
    			    $sel	.= "$and (";
    			    if ($selroles & 1)
    			    {
    					$sel	.= "Groom.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 2)
    			    {
    					$sel	.= "$or Bride.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 4)
    			    {
    					$sel	.= "$or Minister.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
    					$sqlParms[]	= $value;
    			    }
    			    $sel	.= ') ';
    			    $and	= 'AND';
    			}		// match soundex
    			else
    			{		// match exact
    			    $or		= '';
    			    $sel	.= "$and (";
    			    if ($selroles & 1)
    			    {
    					$sel	.= "Groom.M_Surname=? ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 2)
    			    {
    					$sel	.= "$or Bride.M_Surname=? ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 4)
    			    {
    					$sel	.= "$or Minister.M_Surname=?";
    					$sqlParms[]	= $value;
    			    }
    			    $sel	.= ') ';
    			    $and	= 'AND';
    			}		// match exact
    	
    			$npuri		.= "$npand$key=$value";
    			$npand		= '&amp;'; 
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }

    	    case 'givennames':
    	    case 'occupation':
    	    case 'religion':
    	    case 'fathername':
    	    case 'mothername':
    	    case 'witnessname':
    	    case 'witnessres':
    	    case 'birthplace':
    	    case 'remarks':
    	    case 'age':
    	    case 'marstat':
    	    {		// match anywhere in string
    			$or		= '';
    			$sel	.= "$and (";
    			if ($selroles & 1)
    			{
    			    $sel	.= "LOCATE(?, Groom.M_$key)>0 ";
    			    $sqlParms[]	= $value;
    			    $or	= 'OR';
    			}
    			if ($selroles & 2)
    			{
    			    $sel	.= "$or LOCATE(?, Bride.M_$key)>0 ";
    			    $sqlParms[]	= $value;
    			    $or	= 'OR';
    			}
    			if ($selroles & 4)
    			{
    			    $sel	.= "$or LOCATE(?, Minister.M_$key)>0";
    			    $sqlParms[]	= $value;
    			}
    			$sel	.= ') ';
    			$and		= 'AND';
    			$npuri		.= "$npand$key=$value";
    			$npand		= '&amp;'; 
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }		// match in string

    	    case 'place':
    	    case 'date':
    	    {		// match anywhere in string
    			$sel		.= "$and LOCATE(?, Marriage.M_$key) > 0 ";
    			$sqlParms[]	= $value;
    			$and		= 'AND';
    			$npuri		.= "$npand$key=$value";
    			$npand		= '&amp;';
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }		// match in string

    	    case 'surnamesoundex':
    	    {		// handled under Surname
    			$npuri		.= "$npand$key=$value";
    			$npand		= '&amp;'; 
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }		// handled under Surname

    	    case 'byear':
    	    {		// birth year
    			if (array_key_exists('Range', $_GET))
    			    $range	= $_GET['Range'];
    			else
    			    $range	= 1;
    			if (!preg_match("/^([0-9]{1,4})$/", $value))
    			{
    			    $msg	.= "Birth Year must be a number. ";
    			}
    			else
    			if (!preg_match("/^([0-9]{1,2})$/", $range))
    			{
    			    $msg	.= "Birth Year range must be a number. ";
    			}
    			else
    			if  (($value < "1700") || ($value > 2000))
    			{
    			    $msg	.= "Birth Year out of range. ";
    			}
    			else
    			{
    			    $or		= '';
    			    $sel	.= "$and (";
    			    if ($selroles & 1)
    			    {
    					$sel	.= "ABS(Groom.M_BYear-?) < $range ";
    					$sqlParms[]	= $value;
    					$or	= 'OR';
    			    }
    			    if ($selroles & 2)
    			    {
    					$sel	.= "$or ABS(Bride.M_BYear-?) < $range";
    					$sqlParms[]	= $value;
    			    }
    			    $sel	.= ') ';
    			    $and	= 'AND';
    			    $npuri	.= "{$npand}{$key}={$value}";
    			    $npand	= '&amp;'; 
    			}
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }		// birth year

    	    case 'range':
    	    {		// Range
    			$needSpouse	= true;
    			// handled under BYear
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			$expand		= false;
    			$oexpand	= false;define("FOO",     "something");
    			break;
    	    }		// Range

    	    case 'regdomain':
    	    {		// registration domain
    			$regDomain	= $value;
    			$sel		.= "$and Marriage.M_RegDomain=? ";
    			$sqlParms[]	= $value;
    			$and		= 'AND';
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			break;
    	    }		// registration domain

    	    case 'regcounty':
    	    case 'regtownship':
    	    case 'originalvolume':
    	    case 'originalpage':
    	    case 'originalitem':
    	    {		// exact match on field in Marriage table
    			$needSpouse	= true;
    			$sel		.= "$and Marriage.M_$key=? ";
    			$sqlParms[]	= $value;
    			$and		= 'AND';
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			$orderby	= $nominalOrd;
    			$expand		= false;
    			$oexpand	= false;
    			break;
    	    }		// exact match on field in Marriage table

    	    case 'inchusband':
    	    {
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			break;
    	    }		// include grooms in report

    	    case 'incwife':
    	    {
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			break;
    	    }		// include brides in report

    	    case 'incminister':
    	    {
    			$npuri		.= "{$npand}{$key}={$value}";
    			$npand		= '&amp;'; 
    			break;
    	    }		// include ministers in report

    	    case 'order':
    	    {
    			if (strtoupper($value) == 'NAME')
    			{
    			    $orderby	= $nominalOrd;
    			    $expand	= false;
    			    $oexpand	= false;
    			    $npnext	.= "&Order=Name";
    			}
    			else
    			if (strtoupper($value) == 'NUMBER')
    			{
    			    $orderby	= $numericOrd;
    			    $expand	= canUser('edit');
    			    $oexpand	= $expand;
    			    $npnext	.= "&Order=Number";
    			}
    			else
    			    $warn	.= "Unexpected value for $key='$value'. ";
    			break;
    	    }		// include ministers in report

    	    case 'query':
    	    case 'reset':
    	    {		// buttons
    			break;
    	    }		// buttons

    	    default:
    	    {		// unrecognized parameter
    			$warn	.= "Unrecognized parameter $key='$value'. ";
    			break;
    	    }		// unrecognized parameter
    	}	// switch on parameter name
    }	// non-empty value
}		// foreach parameter

if (strlen($sel) == 0)
    $msg	.= 'Missing parameters. ';

// variable portion of URI for next and previous links
if ($offset > 0)
{		// starting offset within existing query
    if ($orderby == $numericOrd)
    	$limit	= ' LIMIT ' . ($count * 2) . ' OFFSET ' . ($offset * 2);
    else
    	$limit	= " LIMIT $count OFFSET $offset";

    // URI for link to previous page of response
    if ($offset >= 0)
    {	// not first page of response
    	$tmp	= $offset - $count;
    	if ($regNum > 0)
    	    $npprev	= "RegNum=$regNum&Count=$count&Offset=$tmp";
    	else
    	    $npprev	= "Count=$count&Offset=$tmp";
    }	// not first page of response

    // URI for link to next page of response
    $tmp		= $offset + $count;
    if ($regNum > 0)
    	$npnext	= "RegNum=$regNum&Count=$count&Offset=$tmp";
    else
    	$npnext	= "Count=$count&Offset=$tmp";
}		// starting offset within existing query
else
{
    $limit	= " LIMIT $count";
    if ($expand)
    {		// display unused records
    	if (($regYear == 1870 && $regNum > 70000) ||
    	    ($regYear == 1871 && $regNum > 120000) ||
    	    ($regYear == 1872 && $regNum > 170000))
    	{
    	    $tcount	= floor($count / 3 + 1) * 10;
    	    $npnext	= 'RegNum=' . ($regNum + $tcount);
    	    $npprev	= 'RegNum=' . ($regNum - $tcount);
    	}
    	else
    	if ($regNum > 0)
    	{
    	    $npnext	= 'RegNum=' . ($regNum + $count);
    	    $npprev	= 'RegNum=' . ($regNum - $count);
    	}
    	else
    	{
    	    $npnext	= 'Offset=' . ($offset + $count);
    	    if ($offset > 0)
    			$npprev	= 'Offset=' . ($offset - $count);
    	}
    }		// display unused records
    else
    {		// only display records from database
    	$npprev	= '';
    	if ($regNum > 0)
    	    $npnext	= "RegNum=$regNum&Count=$count&Offset=$count";
    	else
    	    $npnext	= "Count=$count&Offset=$count";
    }		// only display records from database
}

if ($regNum == 0)
    $expand	= false;

// if no error messages display the query
if (strlen($msg) == 0)
{
    // execute the query for total number of matches
    $query	= "SELECT COUNT(*) FROM Marriage $join WHERE $sel";
    $stmt	= $connection->prepare($query);
    $queryText	= debugPrepQuery($query, $sqlParms);
    if ($stmt->execute($sqlParms))
    {
    	if ($debug)
    	{
    	    $warn	.= "<p>MarriageRegResponse.php: " . __LINE__ .
    					   " query='$queryText'</p>\n";
    	}
    
    	// get the value of COUNT(*)
    	$row		= $stmt->fetch(PDO::FETCH_NUM);
    	$totalrows	= $row[0];
    }
    else
    {		// error performing query
    	$msg	.= $queryText . ": " .
    			   print_r($connection->errorInfo(),true);
    }		// error performing query

    // execute the query for results
    $query		= "SELECT $flds FROM Marriage $join ".
    					    "WHERE $sel ORDER BY $orderby $limit";
    $stmt		= $connection->prepare($query);
    $queryText	= debugPrepQuery($query, $sqlParms);
    if ($stmt->execute($sqlParms))
    {
    	$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
    	$numRows	= count($result);
    	if ($debug)
    	{
    	    $warn	.= "<p>MarriageRegResponse: " . __LINE__ .
    					  " query='$queryText'</p>\n";
    	}
    }
    else
    {		// error performing query
    	$msg	.= $queryText . ": " .
    			   print_r($connection->errorInfo(),true);
    }		// error performing query
}		// no error messages

htmlHeader($title,
    	   array('/jscripts/util.js',
    			 '/jscripts/js20/http.js',
    			 'MarriageRegResponse.js'));
?>
<body>
<?php
pageTop($breadcrumbs);
?>
<div class='body'>
  <h1>
    <span class='right'>
    	<a href='MarriageRegResponseHelpen.html' target='_blank'>Help?</a>
    </span>
    <?php print $title . "\n"; ?> 
    <div style='clear: both;'></div>
  </h1>
<?php
    	if (strlen($msg) > 0)
    	{		// print error messages if any
?>
  <p class='message'><?php print $msg; ?></p>
<?php
    	}		// print error messages if any
    	else
    	{		// display results of query
    	    showTrace();
?>
  <!--- Put out a line with links to previous and next section of table -->
  <div class='center'>
    <span class='left'>
<?php
    	if (strlen($npprev) > 0)
    	{
?>
    	<a href='<?php print $npuri.$npand.$npprev; ?>'>&lt;---</a>
<?php
    	}
?>
     </span>
    <span class='right'>
<?php
    	if (strlen($npnext) > 0)
    	{
?>
    	<a href='<?php print $npuri.$npand.$npnext; ?>'>---&gt;</a>
<?php
    	}
?>
    </span>
    displaying rows <?php print $offset+1; ?>
    to <?php print $offset + $numRows; ?> of <?php print $totalrows; ?> 
  </div>
  <!--- Put out the response as a table -->
  <form id='respform'>
    <input type='hidden' id='RegDomain' value='<?php print $regDomain; ?>'>
    <table class='form'>
      <thead>
      <!--- Put out the column headers -->
      <tr>
    	<th class='colhead'>
    	    Action
    	</th>
    	<th class='colhead'>
    	    Year
    	</th>
    	<th class='colhead'>
    	    Num
    	</th>
    	<th class='colhead'>
    	    Groom Name
    	</th>
    	<th class='colhead'>
    	    BYear
    	</th>
    	<th class='colhead'>
    	    Bride Name
    	</th>
    	<th class='colhead'>
    	    BYear
    	</th>
    	<th class='colhead'>
    	    Date
    	</th>
    	<th class='colhead'>
    	    Marriage Place
    	</th>
      </tr>
      </thead>
      <tbody>
<?php
    	// display the results
    	$num		= 0;
    	$rowClass	= 'odd';
    	$rownum		= 0;
    	foreach($result as $row)
    	{
    	    $regYear	= $row['m_regyear'];
    	    // calculate difference between previous and current regNum
    	    $rowdiff	= $row['m_regnum'] - $regNum;
    	    if ($expand && $rowdiff > 0)
    	    {		// first entry is not first expected
    			if ($regYear == 1869 || $regYear>=1873)
    			{			// sequential record numbers
    			    if ($rowdiff < $count)
    					$num	+= $rowdiff;
    			    else
    			    {
    					$rowdiff	= $count - $num;
    					$num	= $count;
    			    }
    			}			// sequential record numbers
    			else
    			{			// volume, page, column
    			    if ($rowdiff >= 10)
    			    {			// not on the same page
    					$pages		= floor($rowdiff/10);
    					$rowdiff	= 3 * $pages + $rowdiff % 10;
    			    }			// not on the same page
    			}			// volume, page, column
    			emptyRows($rowdiff);
    			if ($num >= $count)
    			    break;
    	    }		// first entry is not first expected
    	    $expand	= $oexpand;
    	    $rownum++;

    	    $regNum	= $row['m_regnum'];
    	    $date		= htmlspecialchars($row['m_date']);
    	    $place		= htmlspecialchars($row['m_place']);
    	    $surname		= $row['g_surname'];
    	    $givenNames		= $row['g_given'];
    	    $birthyear		= $row['g_byear'];
    	    $idir		= $row['g_idir'];
    	    $ssurname		= $row['b_surname'];
    	    $sgivenNames	= $row['b_given'];
    	    $sbirthyear		= $row['b_byear'];
    	    $sidir		= $row['b_idir'];

    	    // start table row for display
?>
      <tr>
    	<!-- link to update action -->
    	<td class='left' style='white-space: nowrap;'>
    	    <button type='button'
    					id='Action<?php print $regYear . $regNum; ?>'>
    			 <?php print $action; ?> 
    	    </button>
<?php
    	if ($action == 'Update')
    	{	// user can delete 
?>
    	    <button type='button'
    					id='Delete<?php print $regYear . $regNum; ?>'>
    			Delete
    	    </button>
<?php
    	}	// user can delete 
?>
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $regYear; ?> 
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $regNum; ?> 
    	</td>
    	<td class='<?php print $rowClass; ?> male'>
<?php
    	    if (strlen($surname) > 0 || strlen($givenNames) > 0)
    	    {			// groom present
    			if ($idir > 0)
    			{		// registration is referenced
?>
    	  <a href='/FamilyTree/Person.php?idir=<?php print $idir; ?>'
    			    class='male' target='_blank'>
    	    <strong><?php print $surname; ?></strong>, <?php print $givenNames; ?>
    	      </a>
<?php
    			}		// registration is referenced
    			else
    			{		// registration is not referenced
?>
    	    <strong><?php print $surname; ?></strong>, <?php print $givenNames; ?>
<?php
    			}		// registration is not referenced
    	    }			// groom present
    	    else
    			print "&nbsp;";
?>
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $birthyear; ?>
    	</td>
    	<td class='<?php print $rowClass; ?> female'>
<?php
    	    if (strlen($ssurname) > 0 || strlen($sgivenNames) > 0)
    	    {			// have a bride record
    			if ($sidir > 0)
    			{		// matched to family tree
?>
    	  <a href='/FamilyTree/Person.php?idir=<?php print $sidir; ?>'
    			class='female' target='_blank'>
    	    <strong><?php print $ssurname; ?></strong>, <?php print $sgivenNames; ?>
    	  </a>
<?php
    			}	// matched to family tree
    			else
    			{	// not matched to family tree
?>
    	    <strong><?php print $ssurname; ?></strong>, <?php print $sgivenNames; ?>
<?php
    			}	// not matched to family tree
    	    }		// have a spouse record
    	    else
    			print "&nbsp;";
?>
    	</td>
    	<td class='<?php print $rowClass; ?> right'>
    	    <?php print $sbirthyear; ?>
    	</td>
    	<td class='<?php print $rowClass; ?>' style="min-width: 7em;">
    	    <?php print $date; ?>
    	</td>
    	<td class='<?php print $rowClass; ?>'>
    	    <?php print $place; ?>
    	</td>
      </tr>
<?php
    	    if ($rowClass == 'odd')
    			$rowClass	= 'even';
    	    else
    			$rowClass	= 'odd';

    	    $regNum++;		// next expected record
    	    if (($regNum % 10 == 4) &&
    			(($regYear == 1870 && $regNum > 70000) ||
    			 ($regYear == 1871 && $regNum > 120000) ||
    			 ($regYear == 1872 && $regNum > 170000)))
    			$regNum	+= 7;

    	    if ($num >= $count)
    			break;
    	}		// process all rows

    	// if fewer rows were returned than requested
    	// create empty entries

    	if ($expand)
    	{
    	    emptyRows($count - $num);
    	}
?>
      </tbody>
    </table>
  </form>
<?php
    	    showTrace();
?>
<!--- Put out a line with links to previous and next section of response -->
  <div class='center'>
    <span class='left'>
<?php
    	if (strlen($npprev) > 0)
    	{
?>
    	<a href='<?php print $npuri.$npand.$npprev; ?>'>&lt;---</a>
<?php
    	}
?>
     </span>
    <span class='right'>
<?php
    	if (strlen($npnext) > 0)
    	{
?>
    	<a href='<?php print $npuri.$npand.$npnext; ?>'>---&gt;</a>
<?php
    	}
?>
    </span>
    displaying rows <?php print $offset+1; ?>
    to <?php print $offset + $numRows; ?> of <?php print $totalrows; ?> 
  </div>
<?php
    	}		// display results of query
?>
</div>
<?php
    pageBot();
?>
<div id='templates' class='hidden'>

  <!-- template for confirming the deletion of a citation-->
  <form name='RegDel$template' id='RegDel$template'>
    <p class='message'>$msg</p>
    <p>
      <button type='button' id='confirmDelete$regnum'>
    	OK
      </button>
      <input type='hidden' id='regdomain$template' name='regdomain$template'
    			value='$regdomain'>
      <input type='hidden' id='regyear$template' name='regyear$template'
    			value='$regyear'>
      <input type='hidden' id='formname$template' name='formname$template'
    			value='$formname'>
    	&nbsp;
      <button type='button' id='cancelDelete$regnum'>
    	Cancel
      </button>
    </p>
  </form>
</div> <!-- end of <div id='templates'> -->
<div class='balloon' id='HelpAction'>
Click on this button to display the page of detail information from the
registration, with the ability to update the information if you are authorized.
</div>
<div class='balloon' id='HelpDelete'>
Click on this button to delete the transcription of the specific marriage.
</div>
<div class='balloon' id='HelpRegYear'>
This field contains the year in which the event was registered.
</div>
<div class='balloon' id='HelpRegNum'>
This field contains the registration number as assigned by the Registrar of
Ontario.
</div>
</body>
</html>
