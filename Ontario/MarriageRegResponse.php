<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

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
 *		2016/07/04		misspelled $domain in delete dialog			*
 *		2016/08/25		correct calculation of number of empty pages	*
 *						for 1870 through 1872							*
 *		2016/11/28		change increment for 1872						*
 *		2017/02/07		use class Country								*
 *		2017/02/18		add fields OriginalVolume, OriginalPage, and	*
 *						Originalitem									*
 *						use prepared statement for query				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/01/09      use named substitutions in prepared statement   *
 *		                only scan $_GET once                            *
 *		                move all parameter validation out of loop       *
 *		                use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/07/08      correct handling of 1870-1872 marriages         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function emptyRows													*
 *																		*
 *  Generate empty rows corresponding to records that do not			*
 *  already exist in the database, allowing them to be created by		*
 *  an authorized contributor.											*
 *																		*
 *  Input:																*
 *		$count			number of extra rows to generate				*
 *		$html           text of template for a row                      *
 ************************************************************************/
function emptyRows($count, $html)
{
    global $regyear;
    global $eregnum;            // expected registration number
    global $regnum;
    global $lastRegNum;
    global $rowclass;
    global $action;
    global $rownum;
    global $debug;
    global $warn;

    $retval             = '';
    for ($i = 0; $i < $count; $i++)
    {
        if ($regyear == 1870 && $eregnum > 70000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;
        if ($regyear == 1871 && $eregnum > 120000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;
        if ($regyear == 1872 && $eregnum > 120000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;
        if ($eregnum >= $lastRegNum)
            break;

        $marrTemplate       = new Template($html);
        $marrTemplate->set('regyear',       $regyear);
        $marrTemplate->set('regnum',        $eregnum);
        $marrTemplate->set('action',        $action);
        $marrTemplate->set('rowclass',      $rowclass);
		$marrTemplate->updateTag('Delete$regyear$regnum', null);
		$marrTemplate->updateTag('glink$regyear$regnum', null);
		$marrTemplate->updateTag('gname$regyear$regnum', '&nbsp;');
		$marrTemplate->updateTag('blink$regyear$regnum', null);
		$marrTemplate->updateTag('bname$regyear$regnum', '&nbsp;');
        $marrTemplate->set('g_byear',       '&nbsp;');
        $marrTemplate->set('b_byear',       '&nbsp;');
        $marrTemplate->set('m_date',        '&nbsp;');
        $marrTemplate->set('m_place',       '&nbsp;');
        $retval         .= $marrTemplate->compile() . "\n";

        if ($rowclass == 'odd')
            $rowclass	= 'even';
        else
            $rowclass	= 'odd';
        $eregnum++;
        $rownum++;
    }		// loop filling in extra rows
    return $retval;
}		// function emptyRows

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// variables for constructing the SQL statement
// join expression for the two tables from which the information is extracted
$join				= 'LEFT JOIN MarriageIndi AS Groom ON ' .
                        'Marriage.M_RegYear= Groom.M_RegYear AND ' .
                        'Marriage.M_RegNum= Groom.M_RegNum AND ' .
                        'Marriage.M_RegDomain= Groom.M_RegDomain AND ' .
                        "Groom.M_Role= 'G' " .
                      'LEFT JOIN MarriageIndi AS Bride ON ' .
                        'Marriage.M_RegYear= Bride.M_RegYear AND ' .
                        'Marriage.M_RegNum= Bride.M_RegNum AND ' .
                        'Marriage.M_RegDomain= Bride.M_RegDomain AND ' .
                        "Bride.M_Role= 'B' " .
                      'LEFT JOIN MarriageIndi AS Minister ON ' .
                        'Marriage.M_RegYear= Minister.M_RegYear AND ' .
                        'Marriage.M_RegNum= Minister.M_RegNum AND ' .
                        'Marriage.M_RegDomain= Minister.M_RegDomain AND ' .
                        "Minister.M_Role= 'M' ";

// construct the various portions of the SQL SELECT statement
// where expression
$where				= '';
$sqlParms	        = array();

$selroles			= 0;		    // bit mask of roles to include GBM
$limit				= '';		    // limit on which rows to return
$prefix				= 'M_';		    // common prefix of table field names
$cprefix			= 'Marriage.M_';// prefix of table field names
$and				= 'WHERE ';		// logical and operator in SQL expressions
$flds				= 'Marriage.M_RegDomain, ' .
                      'Marriage.M_RegYear AS RegYear, Marriage.M_RegNum AS RegNum, ' .
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
$npand				= '?';		    // adding parms to $npuri
$npprev				= '';		    // previous selection
$npnext				= '';		    // next selection
$count				= 20;           // default maximum number of rows
$offset				= 0;            // starting offset in result set
$expand				= canUser('edit');  // display missing records
$oexpand			= $expand;      // original value of $expand
$regyear			= 0;            // registration year
$regnum				= 0;            // registration number
$regCounty          = null;         // registration county code
$lastRegNum			= 0;            // last number displayed
$originalVolume		= null;
$originalPage		= null;
$originalItem		= null;
$surname            = null;
$surnameSoundex     = false;
$byear              = null;
$range              = 1;            // +/- range on birth year 
$cc				    = 'CA';
$code				= 'ON';
$countryName		= 'Canada';
$countyName		    = '';
$regTownship		= '';
$domain			    = 'CAON';
$domainName			= 'Ontario';
$needSpouse			= false;
$matchAnywhere      = false;
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
    if (strlen($value) > 0)
    {
        $fieldLc                = strtolower($key);
        switch($fieldLc)
        {		// switch on parameter name
            case 'count':
            {		// limit number of rows returned
                $count	        = $value;
                break;
            }		// limit number of rows returned
    
            case 'offset':
            {		// starting offset
                $offset	        = $value;
                break;
            }		// starting offset
    
            case 'regdomain':
            {
                $domain      = $value;
                break;
            }		// RegDomain
    
            case 'regyear':
            {		// year of registration
                $regyear        = $value;
                break;
            }		// year of registration
    
            case 'regnum':
            {		// RegNum
                $regnum	        = $value;
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
    

            case 'surname':
            {
                $surname        = $value;
                $needSpouse	    = true;
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
                $matchAnywhere  = true;
                $npuri		    .= "$npand$key=$value";
                $npand		    = '&amp;'; 
                $orderby	    = $nominalOrd;
                $expand		    = false;
                $oexpand	    = false;
                break;
            }		// match in string

            case 'place':
            case 'date':
            {		// match anywhere in string
                $where		    .= "$and LOCATE(:$fieldLc, Marriage.M_$key)>0";
                $sqlParms[$fieldLc]	= $value;
                $and		    = ' AND ';
                $npuri		    .= "$npand$key=$value";
                $npand		    = '&amp;';
                $orderby	    = $nominalOrd;
                $expand		    = false;
                $oexpand	    = false;
                break;
            }		// match in string

            case 'surnamesoundex':
            {		// handled under Surname
                $surnameSoundex     = true;
                $npuri		        .= "$npand$key=$value";
                $npand		        = '&amp;'; 
                $orderby	        = $nominalOrd;
                $expand		        = false;
                $oexpand	        = false;
                break;
            }		// handled under Surname

            case 'byear':
            {		// birth year
                $byear              = $value;
                break;
            }		// birth year

            case 'range':
            {		// birth year Range
                $range	            = $value;
                $needSpouse	        = true;
                $npuri		        .= "{$npand}{$key}={$value}";
                $npand		        = '&amp;'; 
                $expand		        = false;
                $oexpand	        = false;
                break;
            }		// birth year Range

            case 'county':
            case 'regcounty':
            {		// exact match on county code
                $needSpouse	        = true;
                $where		        .= "$and Marriage.M_RegCounty=:regcounty ";
                $sqlParms['regcounty']	= $value;
                $regCounty	        = $value;
                $and		        = ' AND ';
                $npuri		        .= "{$npand}RegCounty=$value";
                $npand		        = '&amp;'; 
                $orderby	        = $nominalOrd;
                $expand		        = false;
                $oexpand	        = false;
                break;
            }		// exact match on county code

            case 'township':
            case 'regtownship':
            {		// exact match on county code
                $needSpouse	        = true;
                $where		        .= "$and Marriage.M_RegTownship=:regtownship ";
                $sqlParms['regtownship']	= $value;
                $regTownship	    = $value;
                $and		        = ' AND ';
                $npuri		        .= "{$npand}RegTownship=$value";
                $npand		        = '&amp;'; 
                $orderby	        = $nominalOrd;
                $expand		        = false;
                $oexpand	        = false;
                break;
            }		// exact match on county code

            case 'originalvolume':
            case 'originalpage':
            case 'originalitem':
            {		// exact match on field in Marriage table
                $needSpouse	        = true;
                $where		        .= "$and Marriage.M_$key=:$fieldLc ";
                $sqlParms[$fieldLc]	= $value;
                $and		        = ' AND ';
                $npuri		        .= "{$npand}{$key}={$value}";
                $npand		        = '&amp;'; 
                $orderby	        = $nominalOrd;
                $expand		        = false;
                $oexpand	        = false;
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
            {       // requested language
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value, 0, 2));
                break;
            }		// requested language
    
            case 'debug':
            {		// debug handled by common.inc
                break;
            }		// debug

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

        }			// switch on parameter name
    }	            // non-empty value
}			        // loop through all parameters
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";

// start the template
$template			= new FtTemplate("MarriageRegResponse$lang.html");
$trtemplate     = $template->getTranslate();

// validate combinations of parameter values
// validate count
if (is_string($count) && strlen($count) > 0)
{		
	if (!preg_match("/^([0-9]{1,2})$/", $count))
	{
	 	$msg        .= "Row count '$count' must be number between 1 and 99. ";
	    $count	    = 20;		// replace with default
	}
}		// maximum number of rows to display

// validate offset
if (is_string($offset) && strlen($offset) > 0)
{		// starting offset
    if (!preg_match("/^([0-9]{1,6})$/", $offset))
    {
        $msg        .= "Row offset '$offset' must be number between 0 and 999999. ";
        $offset	    = 0;
    }

    if ($offset > 0)
    {
        $oexpand	= $expand;
        $expand		= false;
    }
}		// starting offset

// interpret domain code
if (is_string($domain) && strlen($domain) >= 4)
{		        // domain code specified
    $domainObj	        = new Domain(array('domain'	    => $domain,
                                           'language'	=> 'en'));
    if ($domainObj->isExisting())
    {
        $cc		        = substr($domain, 0, 2);
        $countryObj	    = new Country(array('code' => $cc));
        $countryName	= $countryObj->getName();
        $domainName	    = $domainObj->get('name');
	    $where		    .= "$and Marriage.M_RegDomain=:regdomain ";
	    $sqlParms['regdomain']	    = $domain;
	    $and		    = ' AND ';
	    $npuri		    .= "{$npand}{$key}={$value}";
	    $npand		    = '&amp;'; 
    }
    else
    {
        $msg	        .= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
    }
}		        // domain code specified

// registration year
if (is_string($regyear) && strlen($regyear) > 0)
{               // regyear specified
    if (!preg_match("/^([0-9]{1,4})$/", $regyear))
    {
        $msg	.= $key . " must be a number. ";
    }
    else
    if  (($regyear < "1867") || ($regyear > 2000))
    {
        $msg	.= "$key $regyear out of range. ";
    }
    else
    {	// valid
        $where	            .= "$and Marriage.M_RegYear=:regyear ";
        $sqlParms['regyear']= $regyear;
        $and	            = ' AND ';
	    $npuri		        .= "{$npand}RegYear=$regyear";
	    $npand		        = '&amp;'; 
    }	// valid
}               // regyear specified

// validate registration number
if (is_string($regnum) && strlen($regnum) > 0)
{		        // RegNum specified
    if ($regyear <= 0)
    {
        $msg	.=
        'Registration Number may only be specified with Registration Year. ';
    }
    else
    if (!preg_match("/^([0-9]{1,6})$/", $regnum))
    {
        $msg	.= 'RegNum must be a number. ';
    }
    else
    {           // valid
	    $lastRegNum	        = $regnum + $count;
        $where	            .= "$and Marriage.M_RegNum>=:regnum ";
        $sqlParms['regnum']	= $regnum;
        $and	            = ' AND ';
        if ($expand)
        {
            if ($regyear <= 1872 && $regnum > 10000)
                $lastRegNum	= $regnum + 10 * floor($count / 3 + 1);
            $where          .= "AND Marriage.M_RegNum<:lastregnum";
            $sqlParms['lastregnum']	= $lastRegNum;
        }
    }
}		// registration number

// validate county code
if ($regCounty)
{                       // county code
    $countyObj		        = new County($domain, $regCounty);
    $countyName		        = $countyObj->get('name');
}                       // county code

if ($regyear == 0)
{
    $template->updateTag('yearStats',null);
    $template->updateTag('countyStats',null);
    $template->updateTag('townshipStats',null);
}
else
if (is_null($regCounty))
{
    $template->updateTag('countyStats',null);
    $template->updateTag('townshipStats',null);
}
else
if ($regTownship == '')
{
    $template->updateTag('townshipStats',null);
}

// user did not include any of husband, wife, or minister
if ($selroles == 0)
    $selroles		= 3;	// default to husband and wife

// the comparison for surname depends upon multiple parameters
if (is_string($surname) && strlen($surname) > 0)
{               // surname specified
    if (preg_match("/[.+*^$]/", $surname))
    {		    // match regular expression pattern
        $operation          = " REGEXP :surname";
    }		    // match regular expression pattern
    else
    if ($surnameSoundex)
    {		    // match soundex
        $operation          = "Soundex=LEFT(SOUNDEX(:surname),4)";
    }		    // match soundex
    else
    {		    // match exact
        $operation          = "=:surname";
    }		    // match exact
    $sqlParms['surname']= $surname;

    $or		    		    = '(';
    $where	                .= $and;
    if ($selroles & 1)
    {
        $where	            .= "(Groom.M_Surname$operation";
        $or	    		    = ' OR ';
    }
    if ($selroles & 2)
    {
        $where	            .= "$or Bride.M_Surname$operation";
        $or	    		    = ' OR ';
    }
    if ($selroles & 4)
    {
        $where	            .= "$or Minister.M_Surname$operation";
    }
    $where	                .= ')';
    $and	    		    = ' AND ';
        
    $npuri		            .= $npand . "Surname=$surname";
    $npand		    		= '&amp;'; 
    $orderby	    		= $nominalOrd;
    $expand		    		= false;
    $oexpand	            = false;
}               // surname specified

if ($matchAnywhere)
{
    foreach($_GET as $key => $value)
    {
        $fieldLc            = strtolower($key);
        switch($fieldLc)
        {
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
                $or		        = '';
                $where	        .= "$and (";
                $sqlParms[$fieldLc]	= $value;
                if ($selroles & 1)
                {
                    $where	    .= "LOCATE(:$fieldLc, Groom.M_$key)>0";
                    $or	        = ' OR ';
                }
                if ($selroles & 2)
                {
                    $where	    .= "$or LOCATE(:$fieldLc, Bride.M_$key)>0";
                    $or	        = ' OR ';
                }
                if ($selroles & 4)
                {
                    $where	    .= "$or LOCATE(:$fieldLc, Minister.M_$key)>0";
                }
                $where	        .= ') ';
                $and		    = ' AND ';
                break;
            }       // fields which match anywhere
        }           // act on parameter name
    }               // loop through parameters again
}                   // match anywhere in value
// the comparison for birth year depends upon multiple parameters
if (is_string($byear) && strlen($byear) > 0)
{               // birth year specified
    if (!preg_match("/^([0-9]{1,4})$/", $byear))
    {
        $msg	    .= "Birth Year must be a number. ";
    }
    else
    if (!preg_match("/^([0-9]{1,2})$/", $range))
    {
        $msg	    .= "Birth Year range must be a number. ";
    }
    else
    if  (($byear < "1700") || ($byear > 2000))
    {
        $msg	    .= "Birth Year out of range. ";
    }
    else
    {
        $or		                = '';
        $where	                .= "$and(";
        if ($selroles & 1)
        {
            $where	            .= "ABS(Groom.M_BYear-:byear) < :range ";
            $sqlParms['byear']	= $byear;
            $sqlParms['range']	= $range;
            $or	                = ' OR ';
        }
        if ($selroles & 2)
        {
            $where	            .= "$or ABS(Bride.M_BYear-:byear) < :range";
            $sqlParms['byear']	= $byear;
            $sqlParms['range']	= $range;
        }
        $where	                .= ') ';
        $and	                = ' AND ';
        $npuri	                .= "{$npand}{$key}={$byear}";
        $npand	                = '&amp;'; 
    }
    $orderby	                = $nominalOrd;
    $expand		                = false;
    $oexpand	                = false;
}               // birth year specified

if (strlen($where) == 0)
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
        if ($regnum > 0)
            $npprev	= "RegNum=$regnum&Count=$count&Offset=$tmp";
        else
            $npprev	= "Count=$count&Offset=$tmp";
    }	// not first page of response

    // URI for link to next page of response
    $tmp		= $offset + $count;
    if ($regnum > 0)
        $npnext	= "RegNum=$regnum&Count=$count&Offset=$tmp";
    else
        $npnext	= "Count=$count&Offset=$tmp";
}		// starting offset within existing query
else
{
    $limit	= " LIMIT $count";
    if ($expand)
    {		// display unused records
        if (($regyear == 1870 && $regnum > 70000) ||
            ($regyear == 1871 && $regnum > 120000) ||
            ($regyear == 1872 && $regnum > 170000))
        {
            $tcount	= floor($count / 3 + 1) * 10;
            $npnext	= 'RegNum=' . ($regnum + $tcount);
            $npprev	= 'RegNum=' . ($regnum - $tcount);
        }
        else
        if ($regnum > 0)
        {
            $npnext	= 'RegNum=' . ($regnum + $count);
            $npprev	= 'RegNum=' . ($regnum - $count);
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
        if ($regnum > 0)
            $npnext	= "RegNum=$regnum&Count=$count&Offset=$count";
        else
            $npnext	= "Count=$count&Offset=$count";
    }		// only display records from database
}

if ($regnum == 0)
    $expand	= false;

// if no error messages display the query
if (strlen($msg) == 0)
{
    // execute the query for total number of matches
    $query	= "SELECT COUNT(*) FROM Marriage $join $where";
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
                   print_r($stmt->errorInfo(),true);
    }		// error performing query

    // execute the query for results
    $query		    = "SELECT $flds FROM Marriage $join ".
                            "$where ORDER BY $orderby $limit";
    $stmt		    = $connection->prepare($query);
    $queryText	    = debugPrepQuery($query, $sqlParms);
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
                   print_r($stmt->errorInfo(),true);
    }		// error performing query
}		// no error messages

// internationalization support
$monthsTag	    	= $trtemplate->getElementById('Months');
if ($monthsTag)
{
	$months	    	= array();
	foreach($monthsTag->childNodes() as $span)
	    $months[]	= trim($span->innerHTML());
}
$lmonthsTag	    	= $trtemplate->getElementById('LMonths');
if ($lmonthsTag)
{
	$lmonths		= array();
	foreach($lmonthsTag->childNodes() as $span)
	    $lmonths[]	= trim($span->innerHTML());
}
$tranTabTag	    	= $template->getElementById('tranTab');
if ($tranTabTag)
{
	$tranTab		= array();
	foreach($tranTabTag->childNodes() as $span)
	{
	    $key		= $span->attributes['data-key'];
	    $tranTab[$key]	= trim($span->innerHTML());
    }
    if (canUser('update'))
        $action                 = $tranTab['Update'];
    else
        $action                 = $tranTab['Display'];
}                   // tranTabTag
else
if (canUser('update'))
    $action                     = 'Update';
else
    $action                     = 'Details';
$template->set('ACTION',        $action);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->set('DOMAIN',	    $domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('CC',	        $cc);
$template->set('CODE',	        $code);
$template->set('COUNTRYNAME',	$countryName);
$template->set('COUNTYNAME',	$countyName);
$template->set('REGTOWNSHIP',	$regTownship);
$template->set('LANG',	        $lang);
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regnum);

// if no error messages display the results of the query
if (strlen($msg) == 0)
{		// no error messages
	$template->set('FIRSTOFFSET',	$offset+1);
	$template->set('LASTOFFSET',    $offset+$numRows);
	$template->set('TOTALROWS',     $totalrows);
	$template->set('NPURI',     	$npuri);
    $template->set('NPAND',	        $npand);
	$template->set('NPPREV',	    $npprev);
	$template->set('NPNEXT',	    $npnext);
    if ($npprev == '')
    {
        $template->updateTag('topPrev', null);
        $template->updateTag('botPrev', null);
    }
    if ($npnext == '')
    {
        $template->updateTag('topNext', null);
        $template->updateTag('botNext', null);
    }
    $marrRowElt         = $template->getElementById('row$regyear$regnum');
    $marrHTML           = $marrRowElt->outerHTML();
    $data               = '';
    $rowclass   		= 'odd';
	$rownum		        = 0;
    $eregnum		    = $regnum;	// expected entry

    foreach($result as $marr)
    {                   // loop through matching records
        $regyear            = $marr['regyear'];
        $regnum             = $marr['regnum'];
        if (($regyear == 1870 && $regnum > 70000) ||
            ($regyear == 1871 && $regnum > 120000) ||
            ($regyear == 1872 && $regnum > 170000))
        {
            $pagediff       = floor($regnum/10) - floor($eregnum/10);
            $rowdiff	    = ($regnum - $eregnum) - 7 * $pagediff;
        }
        else
            $rowdiff	    = $regnum - $eregnum;
	    if ($rowdiff > ($count * 2) - $rownum)
			$rowdiff	    = ($count * 2) - $rownum;
	    if ($expand && $rowdiff > 0)
	    {		        // first entry is not first expected
			$numRows	    += $rowdiff;
			$data           .= emptyRows($rowdiff, $marrHTML);
            $eregnum        += $rowdiff;
	    }		        // first entry is not first expected
        $rownum++;
	    if ($rownum > $count)
            break;

        $marr['action']		    = $action;
        $marr['rowclass']		= $rowclass;
        $marr['lang']		    = $lang;
        $marrTemplate           = new Template($marrHTML);

        $gidir                   = $marr['g_idir'];
        if ($gidir > 0)
            $marrTemplate->updateTag('gname$regyear$regnum', null);
        else
            $marrTemplate->updateTag('glink$regyear$regnum', null);
        $bidir                   = $marr['b_idir'];
        if ($bidir > 0)
            $marrTemplate->updateTag('bname$regyear$regnum', null);
        else
            $marrTemplate->updateTag('blink$regyear$regnum', null);
        if (!canUser('update'))
            $marrTemplate->updateTag('Delete$regyear$regnum', null);
        $marrTemplate->updateTag('row$regyear$regnum', $marr);
        $data               .= $marrTemplate->compile() . "\n";
        $eregnum		    = $regnum + 1;	  // expected entry
        if ($regyear == 1870 && $eregnum > 70000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;
        if ($regyear == 1871 && $eregnum > 120000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;
        if ($regyear == 1872 && $eregnum > 120000 && ($eregnum % 10) == 4)
            $eregnum	+= 7;

        if ($rowclass == 'odd')
            $rowclass       = 'even';
        else
            $rowclass       = 'odd';
    }                   // loop through matching records

	// if fewer rows were returned than requested
    // create empty entries
    if ($expand)
    {
        $data       .= emptyRows(($count * 2) - $numRows, $marrHTML);
    }
    $marrRowElt->update($data);
}		// no error messages
else
{
    $template['topBrowse']->update(null);
    $template['respform']->update(null);
    $template['botBrowse']->update(null);
}

$template->display();
