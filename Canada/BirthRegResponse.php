<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  BirthRegResponse.php												*
 *																		*
 *  Display the results of a query of the birth registrations table.	*
 *																		*
 *  Parameters:															*
 *		Limit															*
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
 *																		*
 *  History:															*
 *		2010/08/27		Change to new layout							*
 *						Fix warning on SurnameSoundex					*
 *		2010/10/27		check result for SQL errors						*
 *		2011/01/09		handle request to see all registrations for a	*
 *						year in order by registration number			*
 *						improve separation of PHP and HTML				*
 *		2011/05/13		put out header and error messages				*
 *		2011/05/29		set default for $offset							*
 *						miscellaneous cleanup							*
 *		2011/06/13		syntax error in soundex search					*
 *		2011/11/05		rename to BirthRegResponse.php					*
 *						use <button> instead of link for action			*
 *						support mouseover help							*
 *		2012/03/21		make given names a link to the family tree if	*
 *						the birth registration is referenced			*
 *		2012/03/27		combine surname and given names in report		*
 *		2012/03/30		explicitly specify <thead> and <tbody>			*
 *						shorten column headers							*
 *		2012/05/01		correct row number in empty rows preceding first*
 *						active line.									*
 *						display blank birthplace cell in empty rows		*
 *		2012/05/28		display entries with unknown sex in black		*
 *		2013/02/27		clear message if invoked with no parameters		*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/11/15		handle missing database connection gracefully	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *						simplify button implementation					*
 *						clean up next and previous links				*
 *						support RegDomain parameter						*
 *		2014/02/10		include overall status and status for year		*
 *						in breadcrumbs if search includes registration	*
 *						year											*
 *						generate valid HTML page on SQL errors			*
 *		2014/04/26		remove use of getCount function					*
 *		2014/05/08		fix bugs introduced by previous change			*
 *		2014/05/15		handle omission of RegNum parameter when		*
 *						updating										*
 *		2014/08/28		add Delete registration button					*
 *		2014/10/11		pass domain name to child dialogs				*
 *						support delete confirmation dialog				*
 *		2014/12/18		generalize for all provinces and move to		*
 *						folder Canada									*
 *		2015/01/23		uninitialized variable $code					*
 *		2015/01/26		add support for birth date range				*
 *		2015/03/21		$birthDate was not created if not set			*
 *		2015/05/01		ensure date column does not wrap				*
 *						php print statements were corrupted				*
 *						displayed limit-1 rows each time				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/11/14		urlencode next and prev links					*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *						support search by county code					*
 *		2017/02/07		use class Country								*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/01		use Birth::getBirths instead of SQL				*
 *		2017/10/16		use class BirthSet instead of Birth::getBirths 	*
 *		2018/03/17		correct handling of missing offset and limit	*
 *		2018/06/04		if birth date is just a year apply range		*
 *		2018/10/09		use class Template                      		*
 *		2018/10/15      get language apology text from Languages        *
 *		2018/12/21      correct insertion of empty rows                 *
 *		                get error message text from template            *
 *		2019/02/21      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/Birth.inc";
require_once __NAMESPACE__ . "/BirthSet.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  emptyRows															*
 *																		*
 *  Generate empty rows corresponding to records that do not			*
 *  already exist in the database.										*
 *																		*
 *  Input:																*
 *		$limit				number of extra rows to generate			*
 *		$html               text of template for a row                  *
 ************************************************************************/
function emptyRows($limit, $html)
{
    global $regyear;            // registration year
    global $eregnum;            // expected registration number
    global $regnum;
    global $rowclass;
    global $action;
    global $rownum;
    global $debug;
    global $warn;

    if ($debug)
        $warn	.= "<p>emptyRows($limit): \$regyear='$regyear', \$eregnum='$eregnum'</p>\n";

    $retval             = '';
    for ($i = 0; $i < $limit; $i++)
    {
        $birthTemplate      = new Template($html);
        $birthTemplate->set('regyear',      $regyear);
        $birthTemplate->set('regnum',       $eregnum);
        $birthTemplate->set('action',       $action);
        $birthTemplate->set('rowclass',     $rowclass);
        $birthTemplate->set('sexclass',     'unknown');
		$birthTemplate->updateTag('Delete$regyear$regnum', null);
		$birthTemplate->updateTag('link$regyear$regnum', null);
		$birthTemplate->updateTag('name$regyear$regnum', '&nbsp;');
        $birthTemplate->set('birthdate',    '&nbsp;');
        $birthTemplate->set('birthplace',   '&nbsp;');
        $retval         .= $birthTemplate->compile() . "\n";

		if ($rowclass == 'odd')
		    $rowclass	= 'even';
		else
		    $rowclass	= 'odd';
		$rownum++;
        $eregnum++;
    }		// loop filling in extra rows
    return $retval;
}		// emptyRows

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// variables for constructing the SQL statement
$limitopt			= '';		// limit on which rows to return
$prefix				= 'B_';		// common prefix of table field names
$surname			= null;
$surnameSoundex		= false;
$birthDate			= null;
$orderby			= 'B_Surname, B_GivenNames,B_RegYear,B_RegNum';
$npuri				= 'BirthRegResponse.php';// for next and previous links
$npand				= '?';		// adding parms to $npuri
$npprev				= '';		// previous selection
$npnext				= '';		// next selection
$domain				= 'CAON';
$domainName			= 'Ontario';
$cc					= 'CA';
$code				= 'ON';
$countryName		= 'Canada';
$countyName	    	= '';
$lang				= 'en';
$regyear			= 0;
$regnum				= 0;
$limit				= 20;
$offset				= 0;
$regCounty			= null;
$expand				= true;
$getParms			= array();	// parameters to new BirthSet

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{		        	// loop through all parameters
	if (strlen($value) > 0)
	{	        	// only look at non-empty values
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc	= strtolower($key);
	    switch($fieldLc)
	    {
			case 'regdomain':
			case 'domain':
			{		// administrative domain
				$domain		    	= $value;
			    break;
			}		// administrative domain

			case 'count':
			case 'limit':
			{		// number of rows to display at a time
				$limit			    = $value;
			    break;
			}		// number of rows to display at a time

			case 'offset':
			{		// starting offset
				$offset			    = $value;
			    break;
			}		// starting offset

			case 'sex':
			case 'infrel':
			{		// selection lists
			    if (strlen($value) > 0 && $value != '?')
			    {
					$getParms[$fieldLc]	= $value;
					$npuri	        .= "$npand$key=" . urlencode($value);
					$npand	        = '&amp;'; 
			    }   
			    break;
			}		// selection lists

			case 'regyear':
			{		// registration year
			    $orderby	        = 'B_RegYear, B_RegNum';
				$regyear	        = $value;
			    break;
			}		// registration year

			case 'county':
			case 'regcounty':
			{
			    $regCounty		    = $value;
			    break;
			}

			case 'regnum':
			{		// registration number
				$regnum		    	= $value;
			    break;
			}		// registration number

			case 'surname':
			{
			    $getParms[$fieldLc]	= $value;
			    $npuri	        	.= "$npand$key=" .
					        		   urlencode($value);
			    $npand	        	= '&amp;';
			    $expand		        = false;
			    break;
			}

			case 'givennames':
			case 'birthplace':
			case 'phys':
			case 'informant':
			case 'fathername':
			case 'fatherocc':
			case 'mothername':
			case 'motherocc':
			case 'husbandname':
			{		// match anywhere in string
			    $getParms[$fieldLc]	= $value;
			    $npuri  		    .= "$npand$key=" .
					    	    	   urlencode($value);
			    $npand	        	= '&amp;'; 
			    $expand	         	= false;
			    break;
			}		// match in string

			case 'birthdate':
			{
			    $getParms[$fieldLc]	= $value;
			    $npuri	        	.= "$npand$key=" .
						        	   urlencode($value);
			    $npand		        = '&amp;'; 
			    $expand		        = false;
			    break;
			}		// birth date

			case 'range':
			{
			    $getParms[$fieldLc]	= $value;
			    $npuri      		.= "$npand$key=" .
					        		   urlencode($value);
			    $npand      		= '&amp;'; 
			    break;
			}		// birth date range in years

			case 'surnamesoundex':
			{		// soundex flag
			    $getParms[$fieldLc]	= $value;
			    $npuri	        	.= "$npand$key=" .
						        	   urlencode($value);
			    $npand	        	= '&amp;'; 
			    $expand	        	= false;
			    break;
			}		// soundex flag

            case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang          = strtolower(substr($value,0,2));
			    break;
            }

			case 'debug':
			{		// handled by common.inc
			    break;
			}		// debug

            case 'township':
                $fieldLc            = "reg$fieldLc";
			default:
			{		// ordinary parameter
                $getParms[$fieldLc]	= $value;
			    $npuri      		.= "$npand$key=" .
					        		   urlencode($value);
                $npand	        	= '&amp;'; 
			    $expand	        	= false;
			    break;
			}		// ordinary parameter
	    }	        // switch on parameter name
	}           	// only look at non-empty values
}		            // foreach parameter
if ($debug && count($_GET) > 0)
	$warn   	    .= $parmsText . "</table>\n";

// start the template
$template			= new FtTemplate("BirthRegResponse$lang.html");;
$trtemplate         = $template->getTranslate();

// validate domain code
$domainObj	           		= new Domain(array('domain'	    => $domain,
        				                	   'language'	=> 'en'));
$domainName		       		= $domainObj->get('name');
if ($domainObj->isExisting())
{
	$cc			       		= substr($domain, 0, 2);
	$code			   		= substr($domain, 2);
    $countryObj	       		= new Country(array('code'      => $cc));
    $countryName			= $countryObj->getName();
	$getParms['regdomain']	= $domain;
	$npuri			    	.= "{$npand}RegDomain=" . urlencode($domain);
	$npand			   		= '&amp;';
}
else
{
    $text	           		= $template['badDomain']->innerHTML();
    $msg                	.= str_replace('$domain',$domain, $text);
}

// validate county code
if ($regCounty)
{                       // county code
    $countyObj		        = new County($domain, $regCounty);
    $countyName		        = $countyObj->get('name');
    $npuri	                .= "{$npand}RegCounty=" . urlencode($regCounty);
    $npand		            = '&amp;';
    $expand		            = false;
    $getParms['regcounty']	= $regCounty;
}                       // county code

// validate regyear
if ($regyear)
{                       // regyear specified
    if (preg_match("/^([0-9]{1,4})$/", $regyear) == 0)
    {
        $text	        = $template['yearNot4Digits']->innerHTML();
        $msg            .= str_replace('$regyear', $regyear, $text);
    }
    else
    if (($regyear < 1800) || ($regyear > 3000))
    {
        $text	        = $template['yearOutOfRange']->innerHTML();
        $msg            .= str_replace('$regyear',$domain, $text);
    }
    else
    {
		$getParms['regyear']	= $regyear;
		$npuri		            .= "{$npand}RegYear=" . urlencode($regyear);
		$npand		            = '&amp;'; 
    }
}                       // regyear specified

// validate regnum
if ($regnum)
{
    if (ctype_digit($regnum))
    {
		$getParms['regnum']	= $regnum;
    }
    else
    {
        $text	            = $template['regnumNotNumber']->innerHTML();
        $msg                .= str_replace('$regnum', $regnum, $text);
        $regnum             = 0;
    }
}

// validate limit
if ((is_int($limit) || ctype_digit($limit)) && $limit < 100)
{
    $limit			        = $limit - 0;
}
else
{
    $text	                = $template['badLimit']->innerHTML();
    $msg                    .= str_replace('$limit', $limit, $text);
    $limit                  = 20;
}
$getParms['limit']	        = $limit;


// validate offset
if (is_int($offset) || ctype_digit($offset))
{
	$offset			        = $offset - 0;
}
else
{
    $text	                = $template['badOffset']->innerHTML();
    $msg                    .= str_replace('$offset', $offset, $text);
    $offset                 = 0;
}
$getParms['offset']	        = $offset;

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

// now act according to numeric offset value
if ($regnum == 0)
{				// registration number not specified
	$getParms['offset']	= $offset;
	if ($expand)
	    $regnum	    	= 1;

	if ($offset > 0)
	{		// starting offset specified
	    $tmp	        = $offset - $limit;
	    if ($tmp < 0)
			$npprev	    = "";	// no previous link
	    else
			$npprev	    = "Count=$limit&Offset=$tmp";
	    $tmp	        = $offset + $limit;
	    $npnext	        = "Count=$limit&Offset=$tmp";
	}		// starting offset specified
	else
	{		// starting offset omitted
	    $npprev	            = '';
	    $npnext	            = "Count=$limit&Offset=$limit";
	}		// starting offset omitted
}				// registration number not specified
else
{				// registration number specified
	if ($regyear == 0)
	{
        $text	            = $template['needRegYear']->innerHTML();
        $msg                .= str_replace('$regnum', $regnum, $text);
	}
	else
	{
	    $npprev	            = 'RegNum=' . ($regnum - $limit). $npand .
					            'Count=' . $limit;
	    $npnext	            = 'RegNum=' . ($regnum + $limit). $npand .
					            'Count=' . $limit;
	}
}				// registration number specified

if ($regyear == 0)
{
    $template->updateTag('yearStats',null);
    $template->updateTag('countyStats',null);
}
else
if ($countyName == '')
{
    $template->updateTag('countyStats',null);
}

$template->set('DOMAIN',	    $domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('CC',	        $cc);
$template->set('CODE',	        $code);
$template->set('COUNTRYNAME',	$countryName);
$template->set('REGCOUNTY',	    $regCounty);
$template->set('COUNTYNAME',	$countyName);
$template->set('LANG',	        $lang);
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regnum);

// if no error messages display the results of the query
if (strlen($msg) == 0)
{		// no error messages
    if ($expand && $regnum > 0)
    {
        $getParms['regnum']	= array($regnum, ':' . ($regnum + $limit - 1));
    }

	// get the set of Births matching the parameters
	$births					= new BirthSet($getParms, $orderby);
	$totalrows				= $births->getInformation()['count'];
    $numRows				= $births->count();

	if ($offset + $numRows >= $totalrows && $regnum == 0)
        $npnext		= '';

	$template->set('OFFSET1',	    $offset+1);
	$template->set('OFFSETNUMROWS', $offset+$numRows);
	$template->set('TOTALROWS',     $totalrows);
	$template->set('NPURI',     	$npuri);
    $template->set('NPAND',	        $npand);
	$template->set('NPPREV',	    $npprev);
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
	$template->set('NPNEXT',	    $npnext);
    $birthRowElt        = $template->getElementById('birthRow$regyear$regnum');
    $birthHTML          = $birthRowElt->outerHTML();
    $data               = '';
    $rowclass   		= 'odd';
	$rownum		        = 0;
    $eregnum		    = $regnum;	// expected entry

    foreach($births as $birth)
    {                   // loop through matching records
        $regyear            = $birth->get('regyear');
        $regnum             = $birth->get('regnum');
        $rowdiff	        = $regnum - $eregnum;
	    if ($rowdiff > $limit - $rownum)
			$rowdiff	    = $limit - $rownum;
	    if ($expand && $rowdiff > 0)
	    {		        // first entry is not first expected
			$numRows	    += $rowdiff;
			$data           .= emptyRows($rowdiff, $birthHTML);
            $eregnum        += $rowdiff;
	    }		        // first entry is not first expected
        $rownum++;
	    if ($rownum > $limit)
            break;

        $birth->set('action',		$action);
        $birth->set('rowclass',		$rowclass);
        $birth->set('lang',		    $lang);
        switch($birth->get('sex'))
        {
            case 'M':
                $birth->set('sexclass',		'male');
                break;

            case 'F':
                $birth->set('sexclass',		'female');
                break;

            default:
                $birth->set('sexclass',     'unknown');
                break;

        }
        $idir               = $birth->get('idir');
        $regyear            = $birth->get('regyear');
        $regnum             = $birth->get('regnum');

        $birthTemplate      = new Template($birthHTML);
        if ($idir > 0)
            $birthTemplate->updateTag('name$regyear$regnum', null);
        else
            $birthTemplate->updateTag('link$regyear$regnum', null);
        if (!canUser('update'))
            $birthTemplate->updateTag('Delete$regyear$regnum', null);
        $birthTemplate->updateTag('birthRow$regyear$regnum', array($birth));
        $data               .= $birthTemplate->compile() . "\n";
        $regnum		        = $regnum + 1;	// expected entry
        $eregnum		    = $regnum;	    // expected entry
        if ($rowclass == 'odd')
            $rowclass       = 'even';
        else
            $rowclass       = 'odd';
    }                   // loop through matching records

	// if fewer rows were returned than requested
    // create empty entries
	if ($expand)
	    $data       .= emptyRows($limit - $numRows, $birthHTML);
    $birthRowElt->update($data);
}		// no error messages
else
{
    $template['topBrowse']->update(null);
    $template['respform']->update(null);
    $template['botBrowse']->update(null);
}

$template->display();
