<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  DeathRegResponse.php												*
 *																		*
 *  Display the results of a query of the death registrations table.	*
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
 *		2010/08/24		Change to new layout							*
 *						Fix warning on SurnameSoundex					*
 *		2011/01/09		handle request to see all registrations for		*
 *						a year in order by registration number			*
 *						improve separation of PHP and HTML				*
 *		2011/08/10		add help										*
 *		2011/11/05		rename to DeathRegResponse.php					*
 *						use <button> instead of link for action			*
 *						support mouseover help							*
 *		2012/03/20		1869 is a valid year							*
 *		2012/03/26		make given names a link to the family tree if	*
 *						the death registration is referenced			*
 *		2012/03/27		combine surname and given names in report		*
 *		2012/03/30		explicitly specify <thead> and <tbody>			*
 *						shorten column headers							*
 *		2012/05/01		correct row number in empty rows preceding first*
 *						active line.									*
 *		2012/12/31		support unknown sex								*
 *		2013/02/27		fix bug in SQL error handling					*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/15		handle missing database connection gracefully	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *						simplify button implementation					*
 *						clean up next and previous links				*
 *						support RegDomain parameter						*
 *		2013/12/27		improved CSS for paginations controls			*
 *		2014/02/10		include overall status and status for year		*
 *						in breadcrumbs if search includes registration	*
 *						year											*
 *						generate valid HTML page on SQL errors			*
 *		2014/04/05		allow query on place of death					*
 *		2014/04/15		wrong class used for year and regnum in empty	*
 *						rows											*
 *		2014/04/26		remove use of getCount function					*
 *		2014/05/14		correct setting of previous and next links		*
 *						if regnum parameter present						*
 *		2014/05/15		handle omission of RegNum parameter when		*
 *						updating										*
 *		2014/08/28		add Delete registration button					*
 *						encode RegYear and RegNum in button ids			*
 *		2014/10/11		pass domain name to child dialogs				*
 *						support delete confirmation dialog				*
 *		2015/05/01		php print statements were corrupted				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/11		prevent death date from wrapping				*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/06/01		add support for lang parameter					*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2018/12/27		use class Template                      		*
 *		2019/12/13      remove D_ prefix from field names               *
 *		2020/01/22      internationalize numbers                        *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2022/07/08      support display: flex                           *
 *		2022/12/31      PHP 8 does not permit subtract from string      *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/Death.inc";
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
    global $regyear;
    global $eregnum;            // expected registration number
    global $regnum;
    global $rowclass;
    global $action;
    global $rownum;

    $retval             = '';
    for ($i = 0; $i < $limit; $i++)
    {
        $deathTemplate      = new Template($html);
        $deathTemplate->set('regyear',      $regyear);
        $deathTemplate->set('regnum',       $eregnum);
        $deathTemplate->set('action',       $action);
        $deathTemplate->set('rowclass',     $rowclass);
        $deathTemplate->set('sexclass',     'other');
		$deathTemplate->updateTag('Delete$regyear$regnum', null);
		$deathTemplate->updateTag('link$regyear$regnum', null);
		$deathTemplate->updateTag('name$regyear$regnum', '&nbsp;');
        $deathTemplate->set('date',         '&nbsp;');
        $deathTemplate->set('place',        '&nbsp;');
        $deathTemplate->set('age',          '&nbsp;');
        $deathTemplate->set('birthdate',    '&nbsp;');
        $retval         .= $deathTemplate->compile() . "\n";

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

// action taken depends upon whether the user is authorized to
// update the database
$update	            = canUser('edit');

// variables for constructing the SQL statement
$limitopt			= '';		// limit on which rows to return
$prefix				= '';		// common prefix of table field names
$orderby			= 'Surname, GivenNames, RegYear, RegNum';
$npuri				= 'DeathRegResponse.php';	// for next and previous links
$npand				= '?';		// adding parms to $npuri
$npprev				= '';		// previous selection
$npnext				= '';		// next selection
$limit				= 20;
$offset				= 0;
$cc					= 'CA';
$countryName		= 'Canada';
$domain				= 'CAON';
$code				= 'ON';
$domainName			= 'Ontario';
$regCounty			= null;
$countyName		    = '';
$regTownship		= null;
$expand				= true;
$getParms			= array();	// parameters to new DeathSet
$regyear			= 0;
$regyeartext		= null;
$regnum				= 0;
$regnumtext         = null;
$soundex            = false;
$offset				= 0;
$lang				= 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
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
	    $prefixKey	    = $prefix . $key;

	    $fieldLc        = strtolower($key);
	    switch($fieldLc)
	    {
			case 'regdomain':
			case 'domain':
			{
                $getParms[$key]         = $value;
				$domain		            = $value;
				$npuri	                .= "$npand$key=$value";
				$npand	                = '&amp;';
			    break;
			}		// administrative domain

			case 'regcounty':
			case 'county':
			{
                $getParms[$key]         = $value;
				$regCounty		        = $value;
				$npuri	                .= "$npand$key=$value";
				$npand	                = '&amp;';
			    break;
			}		// county

			case 'regtownship':
			case 'township':
			{
                $getParms[$key]         = $value;
				$regTownship		    = $value;
				$npuri	                .= "$npand$key=$value";
				$npand	                = '&amp;';
			    break;
            }		// township

			case 'count':
			case 'limit':
			{		// number of rows to display at a time
				$limit	                = trim($value);
			    break;
			}		// number of rows to display at a time

			case 'offset':
			{		// starting offset
				$offset	                = trim($value) - 0;
			    break;
			}		// starting offset

			case 'sex':
			case 'marstat':
			case 'infrel':
			{		// selection lists
			    if ($value != '?')
			    {
                    $getParms[$key]     = $value;
					$npuri	            .= "$npand$key=$value";
					$npand	            = '&amp;'; 
			    }   
			    break;
			}		// selection lists

			case 'regyear':
            {		// numeric field
                if (ctype_digit($value))
                {
			        $regyear	        = $value;
				    $npuri	            .= "{$npand}{$key}={$value}";
				    $npand	            = '&amp;'; 
                    $orderby	        = "RegYear,RegNum";
                }
                else
                    $regyeartext        = htmlspecialchars($value);
			    break;
			}		// numeric fields

			case 'regnum':
            {		// numeric field
                if (ctype_digit($value))
                    $regnum	        = intval($value);
                else
                    $regnumtext     = htmlspecialchars($value);
			    break;
			}		// numeric fields

			case 'surname':
			{
                $getParms[$key]     = $value;
			    $npuri	            .= "{$npand}{$key}={$value}";
			    $npand	            = '&amp;'; 
			    $expand	            = false;
			    break;
			}

			case 'givennames':
			case 'occupation':
			case 'deathplace':
			case 'date':
			case 'place':
			case 'cause':
			case 'physician':
			case 'informant':
			case 'religion':
			case 'fathername':
			case 'fatherbplce':
			case 'mothername':
			case 'motherbplce':
			case 'husbandName':
            {		// match anywhere in string
                $getParms[$key]     = $value;
			    $npuri	            .= "{$npand}{$key}={$value}";
			    $npand	            = '&amp;'; 
			    $expand	            = false;
			    break;
			}		// match in string

			case 'surnamesoundex':
            {		// handled under Surname
                $soundex            = ($value == 'yes');
			    $npuri	            .= "{$npand}{$key}={$value}";
			    $npand	            = '&amp;'; 
			    $expand	            = false;
			    break;
			}		// handled under Surname

			case 'lang':
			{
                $lang       = FtTemplate::validateLang($value);
			    break;
			}		//lang

			case 'debug':
			{
			    break;
			}

            case 'order':
            {
                if (strlen($value) > 0)
                    $orderby        = $value;
                break;
            }

			default:
			{		// ordinary parameter
                $getParms[$key]     = $value;
			    $npuri	            .= "{$npand}{$key}={$value}";
			    $npand	            = '&amp;'; 
			    $expand	            = false;
			    break;
			}		// ordinary parameter
	    }	        // switch
	}	            // non-empty value
}		            // foreach parameter>
if ($debug)
    $warn                   .= $parmsText . "</table>\n";

// start the template
$template			        = new FtTemplate("DeathRegResponse$lang.html");
$trtemplate                 = $template->getTranslate();

// validate domain code
$domainObj	            	= new Domain(array('domain'	    => $domain,
        				            	   'language'		=> 'en'));
$domainName		        	= $domainObj->get('name');
if ($domainObj->isExisting())
{
	$cc			        	= substr($domain, 0, 2);
	$code			    	= substr($domain, 2);
    $countryObj	        	= new Country(array('code' => $cc));
    $countryName			= $countryObj->getName();
	$getParms['regdomain']	= $domain;
	$npuri			        .= "{$npand}RegDomain=" . urlencode($domain);
	$npand			    	= '&amp;';
}
else
{
    $text	            	= $template['badDomain']->innerHTML();
    $msg                    .= str_replace('$domain',$domain, $text);
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
if (is_string($regyeartext))
{
    $text	                = $template['yearNot4Digits']->innerHTML();
    $msg                    .= str_replace('$regyear', $regyear, $text);
}
else
if ($regyear)
{                       // regyear specified
    if (($regyear < 1800) || ($regyear > 3000))
    {
        $text	            = $template['yearOutOfRange']->innerHTML();
        $msg                .= str_replace('$regyear',$domain, $text);
    }
    else
    {
		$getParms['regyear']= $regyear;
		$npuri		        .= "{$npand}RegYear=" . urlencode($regyear);
		$npand		        = '&amp;'; 
    }
}                       // regyear specified

// validate regnum
if ($regnumtext)
{
    $text	                = $template['regnumNotNumber']->innerHTML();
    $msg                    .= str_replace('$regnum', $regnumtext, $text);
    $regnum                 = 0;
}
else
if ($regnum)
{
    if (ctype_digit($regnum))
    {
		$getParms['regnum']	= $regnum;
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

// now act according to numeric offset and count values
if ($regnum == 0)
{				// registration number not specified
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
	    $npprev	        = '';
	    $npnext	        = "Count=$limit&Offset=$limit";
	}		// starting offset omitted
}				// registration number not specified
else
{				// registration number specified
	if ($regyear == 0)
	{
        $text	        = $template['needRegYear']->innerHTML();
        $msg            .= str_replace('$regnum', $regnum, $text);
	}
	else
	{
	    $npprev	        = 'RegNum=' . ($regnum - $limit). $npand .
					            'Count=' . $limit;
	    $npnext	        = 'RegNum=' . ($regnum + $limit). $npand .
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
$template->set('REGTOWNSHIP',	$regTownship);
$template->set('LANG',	        $lang);
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regnum);
$formatter                          = $template->getFormatter();

// if no error messages display the results of the query
if (strlen($msg) == 0)
{		// no error messages
	if ($expand)
	    $getParms['regnum']	= array($regnum, ':' . ($regnum + $limit - 1));

    // get the set of Deaths matching the parameters
    $getParms['order']      = $orderby;
	$deaths					= new RecordSet('Deaths', $getParms);
    $totalrows				= $deaths->getInformation()['count'];
    $numRows				= $deaths->count();

	if ($offset + $numRows >= $totalrows && $regnum == 0)
        $npnext		= '';

	$template->set('STARTOFFSET',	$offset+1);
	$template->set('ENDOFFSET',     $offset+$numRows);
	$template->set('TOTALROWS',     $formatter->format($totalrows));
	$template->set('NPURI',     	$npuri);
    $template->set('NPAND',	        $npand);
	$template->set('NPPREV',	    $npprev);
    if ($npprev == '')
    {
        $template->updateTag('topPrev', '&nbsp;');
        $template->updateTag('botPrev', '&nbsp;');
    }
    if ($npnext == '')
    {
        $template->updateTag('topNext', '&nbsp;');
        $template->updateTag('botNext', '&nbsp;');
    }
	$template->set('NPNEXT',	    $npnext);
    $deathRowElt        = $template->getElementById('deathRow$regyear$regnum');
    $deathHTML          = $deathRowElt->outerHTML();
    $rowclass   		= 'odd';
    $data               = '';
	$rownum		        = 0;
    if (is_numeric($regnum))
        $eregnum		= $regnum;	// expected entry
    else
        $eregnum		= 0;	

    foreach($deaths as $death)
    {                   // loop through matching records
        $regyear            = $death->get('regyear');
        $regnum             = $death->get('regnum');
        $rowdiff	        = $regnum - $eregnum;
	    if ($rowdiff > $limit - $rownum)
			$rowdiff	    = $limit - $rownum;
	    if ($expand && $rowdiff > 0)
	    {		        // first entry is not first expected
			$numRows	    += $rowdiff;
			$data           .= emptyRows($rowdiff, $deathHTML);
            $eregnum        += $rowdiff;
	    }		        // first entry is not first expected
        $rownum++;
	    if ($rownum > $limit)
            break;

        $death->set('action',		$action);
        $death->set('rowclass',		$rowclass);
        $death->set('lang',		    $lang);
        switch($death->get('sex'))
        {
            case 'M':
                $death->set('sexclass',		'male');
                break;

            case 'F':
                $death->set('sexclass',		'female');
                break;

            default:
                $death->set('sexclass',     'other');
                break;

        }
        $idir               = $death->get('idir');
        $regyear            = $death->get('regyear');
        $regnum             = $death->get('regnum');

        $deathTemplate      = new Template($deathHTML);
        if ($idir > 0)
            $deathTemplate['name$regyear$regnum']->update(null);
        else
            $deathTemplate['link$regyear$regnum']->update(null);
        if (!canUser('update'))
            $deathTemplate['Delete$regyear$regnum']->update(null);
        $deathTemplate['deathRow$regyear$regnum']->update($death);
        $data               .= $deathTemplate->compile() . "\n";
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
	    $data       .= emptyRows($limit - $numRows, $deathHTML);
    $deathRowElt->update($data);
}		                // no error messages
else
{                       // errors
    $template['topBrowse']->update(null);
    $template['respform']->update(null);
    $template['botBrowse']->update(null);
}                       // errors

$template->display();
