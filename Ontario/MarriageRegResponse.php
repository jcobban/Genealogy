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
 *  Parametersi															*
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
 *		2016/07/04		misspelled $domain in delete dialog			    *
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
 *		2019/12/13      remove M_ prefix from field names               *
 *		2020/06/13      set REGCOUNTY                                   *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/MarriageSet.inc';
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
    global $regNum;
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
		$marrTemplate->updateTag('Delete$regyear$regNum', null);
		$marrTemplate->updateTag('glink$regyear$regNum', null);
		$marrTemplate->updateTag('gname$regyear$regNum', '&nbsp;');
		$marrTemplate->updateTag('blink$regyear$regNum', null);
		$marrTemplate->updateTag('bname$regyear$regNum', '&nbsp;');
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
                        'Marriage.RegYear= Groom.RegYear AND ' .
                        'Marriage.RegNum= Groom.RegNum AND ' .
                        'Marriage.RegDomain= Groom.RegDomain AND ' .
                        "Groom.Role= 'G' " .
                      'LEFT JOIN MarriageIndi AS Bride ON ' .
                        'Marriage.RegYear= Bride.RegYear AND ' .
                        'Marriage.RegNum= Bride.RegNum AND ' .
                        'Marriage.RegDomain= Bride.RegDomain AND ' .
                        "Bride.Role= 'B' " .
                      'LEFT JOIN MarriageIndi AS Minister ON ' .
                        'Marriage.RegYear= Minister.RegYear AND ' .
                        'Marriage.RegNum= Minister.RegNum AND ' .
                        'Marriage.RegDomain= Minister.RegDomain AND ' .
                        "Minister.Role= 'M' ";

// construct the various portions of the SQL SELECT statement
// where expression

$selroles			= 0;		    // bit mask of roles to include GBM
$limit				= 20;		    // limit on which rows to return
$prefix				= '';		    // common prefix of table field names
$cprefix			= 'Marriage.';// prefix of table field names
$npuri				= '';		    // common portion of query
$npand              = '?';          // combine query parms
$count				= 20;           // default maximum number of rows
$offset				= 0;            // starting offset in result set
$expand				= canUser('edit');  // display missing records
$oexpand			= $expand;      // original value of $expand
$regyear			= 0;            // registration year
$regNum				= 0;            // registration number
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
$getparms           = array();

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// first extract the values of all supplied parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{			// loop through all parameters
    $value                          = trim($value);
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    if (strlen($value) > 0)
    {
        $fieldLc                    = strtolower($key);
        switch($fieldLc)
        {		// switch on parameter name
            case 'count':
            case 'limit':
            {		// limit number of rows returned
                $limit	            = $value;
                break;
            }		// limit number of rows returned
    
            case 'offset':
            {		// starting offset
                $getparms[$fieldLc]	= $value;
                $offset	            = $value;
                break;
            }		// starting offset
    
            case 'regdomain':
            {
                $getparms[$fieldLc]	= $value;
                $domain             = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// RegDomain
    
            case 'regyear':
            {		// year of registration
                $getparms[$fieldLc]	= $value;
                $regyear            = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// year of registration
    
            case 'regnum':
            {		// RegNum
                $getparms[$fieldLc]	= $value;
                $regNum	            = $value;
                break;
            }		// registration number
    
            case 'originalvolume':
            {		// original volume
                $getparms[$fieldLc]	= $value;
                $originalVolume	    = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// original volume
    
            case 'originalpage':
            {		// original page number
                $getparms[$fieldLc]	= $value;
                $originalPage	    = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// original page number
    
            case 'originalitem':
            {		// original Item position
                $getparms[$fieldLc]	= $value;
                $originalItem	    = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// original Item position
    

            case 'surname':
            {
                $surname            = $value;
                $needSpouse	        = true;
                $getparms[$fieldLc]	= $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }

            case 'givenname':
                $fieldLc            = 'givennames';
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
                $getparms[$fieldLc]	= $value;
                $matchAnywhere      = true;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// match in string

            case 'place':
            case 'date':
            {		// match anywhere in string
                $getparms[$fieldLc]	= $value;
                $expand		    = false;
                $oexpand	    = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// match in string

            case 'surnamesoundex':
            {		// handled under Surname
                $getparms[$fieldLc]	= true;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// handled under Surname

            case 'byear':
            {		// birth year
                $byear              = $value;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// birth year

            case 'range':
            {		// birth year Range
                $getparms[$fieldLc]	= $value;
                $range	            = $value;
                $needSpouse	        = true;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// birth year Range

            case 'county':
            case 'regcounty':
            {		// exact match on county code
                $getparms['regcounty']	= $value;
                $needSpouse	        = true;
                $regCounty	        = $value;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// exact match on county code

            case 'township':
            case 'regtownship':
            {		// exact match on county code
                $getparms['regtownship']	= $value;
                $needSpouse	        = true;
                $regTownship	    = $value;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// exact match on county code

            case 'originalvolume':
            case 'originalpage':
            case 'originalitem':
            {		// exact match on field in Marriage table
                $getparms[$fieldLc]	= $value;
                $needSpouse	        = true;
                $expand		        = false;
                $oexpand	        = false;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// exact match on field in Marriage table

            case 'order':
            {
                $getparms[$fieldLc]	= $value;
                if (strtoupper($value) == 'NAME')
                {
                    $expand	    = false;
                    $oexpand	= false;
                }
                else
                if (strtoupper($value) == 'NUMBER')
                {
                    $expand	    = canUser('edit');
                    $oexpand	= $expand;
                }
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// include ministers in report

            case 'inchusband':
            {
                $getparms['inchusband'] = true;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// include grooms in search
    
            case 'incwife':
            {
                $getparms['incwife'] = true;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// include brides in search
    
            case 'incminister':
            {
                $getparms['incminister'] = true;
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// include ministers in search
    
            case 'lang':
            {       // requested language
                $lang           = FtTemplate::validateLang($value);
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
                break;
            }		// requested language
    
            case 'debug':
            {		// debug handled by common.inc
                $npuri              .= "$npand$key=$value";
                $npand              = '&';
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

if (is_string($offset) && !ctype_digit($offset))
{
    error_log("MarriageRegResponse.php: " . __LINE__ .
                            " offset='$offset'\n");
    $warn               .= "<p>MarriageRegResponse.php: " . __LINE__ .
                            " offset='$offset'</p>\n";
    $result             = preg_match('/\d*/',$offset, $matches);
    $offset             = $matches[0];
    if (strlen($offset) == 0)
        $offset         = 0;
}

if (is_string($limit) && !ctype_digit($limit))
{
    error_log("MarriageRegResponse.php: " . __LINE__ .
                            " limit='$limit'\n");
    $warn               .= "<p>MarriageRegResponse.php: " . __LINE__ .
                            " limit='$limit'</p>\n";
    $result             = preg_match('/\d*/',$limit, $matches);
    $limit              = $matches[0];
    if (strlen($limit) == 0)
        $limit          = 0;
}

$getparms['limit']	        = $limit;
$npuri                      .= "$npuri{$npand}Limit=$limit";

// start the template
$template			= new FtTemplate("MarriageRegResponse$lang.html");
$trtemplate         = $template->getTranslate();

// validate county code
if ($regCounty)
{                       // county code
    $countyObj		        = new County($domain, $regCounty);
    $countyName		        = $countyObj->get('name');
}                       // county code
else
{
    $template->updateTag('countyStats',null);
    $template->updateTag('townshipStats',null);
}

if ($regyear == 0)
{
    $template->updateTag('yearStats',null);
    $template->updateTag('countyStats',null);
    $template->updateTag('townshipStats',null);
}
else
if ($regTownship == '')
{
    $template->updateTag('townshipStats',null);
}

if ($regNum == 0)
    $expand	= false;

// if no error messages display the query
if (strlen($msg) == 0)
{
    $marriages      = new MarriageSet($getparms);
    $info           = $marriages->getInformation();
    if ($debug)
    {
	    $warn           = "<p class='label'>\$info</p>\n" .
	                        "<table class='summary'>\n" .
	                        "<tr><th class='colhead'>key</th>" .
	                        "<th class='colhead'>value</th></tr>\n";
	    foreach ($info as $key => $value)
	    {			// loop through all parameters
	        $warn           .= "<tr><th class='detlabel'>$key</th>" .
	                            "<td class='white left'>";
	        if (is_array($value))
	            $warn       .= print_r($value, true);
	        else
	            $warn       .= $value;
	        $warn           .= "</td></tr>\n"; 
        }
        $warn           .= "</table>\n";
    }

    $numRows	    = min(count($marriages), $limit);
    $totalrows      = $info['count'];
}		// no error messages
else
{
    $marriages      = array();
    $numRows        = 0;
    $totalrows      = 0;
}

// internationalization support
$months	    	            = $trtemplate['Months'];
$lmonths	    	        = $trtemplate['LMonths'];
$tranTab	    	        = $trtemplate['tranTab'];
if (canUser('update'))
    $action                 = $tranTab['Update'];
else
    $action                 = $tranTab['Display'];
$template->set('ACTION',        $action);
$template->set('CONTACTTABLE',		'Marriage');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->set('DOMAIN',	    $domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('CC',	        $cc);
$template->set('CODE',	        $code);
$template->set('COUNTRYNAME',	$countryName);
$template->set('COUNTYNAME',	$countyName);
$template->set('REGCOUNTY',	    $regCounty);
$template->set('REGTOWNSHIP',	$regTownship);
$template->set('LANG',	        $lang);
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regNum);
$template->set('NPURI',         $npuri);

// if no error messages display the results of the query
if (strlen($msg) == 0)
{		// no error messages
	$template->set('FIRSTOFFSET',	$offset+1);
	$template->set('LASTOFFSET',    min($totalrows, $offset+$limit));
    $template->set('TOTALROWS',     $totalrows);
    if ($regNum)
    {               // registration number specified
        if ($regyear <= 1872 && $regNum > 10000)
        {
            $volume         = intdiv($regNum, 10000);
            $page           = intdiv($regNum - ($volume * 10000), 10);
            $item           = $regNum % 10;
            $previtem       = $item - $limit;
            $prevpage       = $page;
            while ($previtem < 0)
            {
                $previtem   += 3;
                $prevpage--;
            }
            $prevregnum     = $volume * 10000 + $prevpage * 10 + $previtem; 
            $nextitem       = $item + $limit;
            $nextpage       = $page;
            while ($nextitem > 3)
            {
                $nextitem   -= 3;
                $nextpage++;
            }
            $nextregnum     = $volume * 10000 + $nextpage * 10 + $nextitem; 
        }
        else
        {
            $prevregnum     = $regNum - $limit;
            $nextregnum     = $regNum + $limit;
        }
        $prevoffset         = $npand . "RegNum=$prevregnum";
        $nextoffset         = $npand . "RegNum=$nextregnum";
    }               // registration number specified
    else
    {
        if ($offset == 0)
            $prevoffset         = '';
        else
            $prevoffset         = $npand . "Offset=" . ($offset - $limit);
        if (($offset + $limit) >= $totalrows)
            $nextoffset         = '';
        else
            $nextoffset         = $npand . "Offset=" . ($offset + $limit);
    }
	$template->set('PREVOFFSET',	$prevoffset);
	$template->set('NEXTOFFSET',	$nextoffset);
    if ($prevoffset == '')
    {
        $template->updateTag('topPrev', null);
        $template->updateTag('botPrev', null);
    }
    if ($nextoffset = '')
    {
        $template->updateTag('topNext', null);
        $template->updateTag('botNext', null);
    }
    $marrRowElt         = $template['row$regyear$regnum'];
    $marrHTML           = $marrRowElt->outerHTML();
    $data               = '';
    $rowclass   		= 'odd';
	$rownum		        = 0;
    $eregnum		    = $regNum;	// expected entry

    foreach($marriages as $marr)
    {                   // loop through matching records
        $regyear                = $marr['regyear'];
        $regNum                 = $marr['regnum'];
        $marr['action']		    = $action;
        $marr['rowclass']		= $rowclass;
        $marr['lang']		    = $lang;
        $marrTemplate           = new Template($marrHTML);

        $gidir                   = $marr['g_idir'];
        if ($gidir > 0)
            $marrTemplate->updateTag('gname$regyear$regnum', null);
        else
        {
            $marrTemplate->updateTag('glink$regyear$regnum', null);
            if (!$marr->isExisting())
                $marrTemplate->updateTag('gname$regyear$regnum', null);
        }
        $bidir                   = $marr['b_idir'];
        if ($bidir > 0)
            $marrTemplate->updateTag('bname$regyear$regnum', null);
        else
        {
            $marrTemplate->updateTag('blink$regyear$regnum', null);
            if (!$marr->isExisting())
                $marrTemplate->updateTag('bname$regyear$regnum', null);
        }
        if (!canUser('update') || !$marr->isExisting())
            $marrTemplate->updateTag('Delete$regyear$regnum', null);
        $marrTemplate->updateTag('row$regyear$regnum', $marr);
        $data               .= $marrTemplate->compile() . "\n";

        if ($rowclass == 'odd')
            $rowclass       = 'even';
        else
            $rowclass       = 'odd';
    }                   // loop through matching records

    $marrRowElt->update($data);
}		// no error messages
else
{
    $template['topBrowse']->update(null);
    $template['respform']->update(null);
    $template['botBrowse']->update(null);
}

$template->display();
