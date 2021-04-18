<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusDetails.php											    	*
 *																		*
 *  Display every field from a row of a census in tabular form.			*
 *  The parameter values should be such as to only return a				*
 *  single line from the census database.								*
 *																		*
 *  Parameters (passed by method=get):									*
 *      Census          2 character country code plus 4 digit year      *
 *		Province		2 letter province code (CE, CW, NS, NB, or PI)	*
 *		Domain          4 or 5 character domain identifying a state or  *
 *		                province within a federal state                 *
 *		District		district number within census		            *
 *		SubDistrict		subdistrict identifier within district	        *
 *		Division		optional division number within subdistrict		*
 *		Page			page number									    *
 *		Line			line number containing individual			    *
 *																		*
 *  History:															*
 *		2010/09/04		suppress warning message about Province			*
 *		2010/09/10		new layout										*
 *		2011/03/09		syntax error on missing or non-numeric division	*
 *		2011/04/28		use CSS rather than tables for layout of header	*
 *						and trailer										*
 *		2011/05/03		use MDB2										*
 *						translate field names							*
 *						improved error handling							*
 *		2011/08/26		rename all response scripts to					*
 *						QueryResponseYYYY.php							*
 *		2012/01/24		use default.js for initialization				*
 *		2012/03/21		add forward and back links						*
 *		2012/04/06		add hyperlink for IDIR field					*
 *		2012/08/17		add support for pre-confederation censuses		*
 *		2012/08/21		call dispDetails to display information			*
 *		2012/09/27		change names of global variables				*
 *		        		move complete construction of query into		*
 *						CensusDetails									*
 *		2012/11/14		use JOIN syntax to obtain information from		*
 *						Districts and SubDistricts tables				*
 *						include only name fields from support tables	*
 *						explicit limit response to 1 line				*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/02/27		$cellspacing was not available in function		*
 *						dispDetails										*
 *		2013/08/06		add more breadcrumbs							*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/11/27		next arrow went to next page for 1921 census	*
 *		2013/12/30		use CSS for previous & next arrows				*
 *		2014/01/06		move page heading into this script				*
 *						derive next row from database and do not give	*
 *						next row link for last line in division			*
 *						validate parameters								*
 *						pass fewer parameters by global to dispDetails	*
 *		2014/05/24		$distId not initialized							*
 *		2015/01/13		use new CensusResponse.php script				*
 *		2015/04/27		include identification of specific line in DB	*
 *						in contact author header						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2018/11/05      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2020/12/01      validate all parameters                         *
 *		                eliminate XSS vulnerabilities                   *
 *		2021/04/04      escape CONTACTSUBJECT                           *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/CensusLine.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// initial values
$censusId			= null;
$censusYear			= '1881';
$census             = null;     // instance of class Census
$censusText         = null;
$province			= null;
$provinceText       = null;
$countryName        = 'Canada';
$provinceName       = '';
$table              = null;     // name of database table
$domainId			= null;
$distId			    = null;
$distText		    = null;
$districtName		= '';
$distObj            = null;     // instance of class District
$subDistId			= null;
$subDistrictName	= '';
$subDistObj			= null;     // instance of class SubDistrict
$division			= '';
$page               = null;
$pageText           = null;
$pageObj            = null;     // instance of class Page
$line               = null;
$lineText           = null;
$lang			    = 'en';
$unexparms          = array();

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by URL to display current status of account
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
	{			            // loop through all parameters
	    $value                      = trim($value);
	    if (strlen($value) == 0)
	        continue;
	    switch(strtolower($key))
	    {		            // act on specific keys
	        case 'census':
	        {
	            if (preg_match('/^[a-zA-Z]{2,4}\d{4}$/',$value))
	            {
	                $censusId       = strtoupper($value);
	                $censusYear     = substr($value, -4);
	            }
	            else
	                $censusText     = htmlspecialchars($value);
	            break;
	        }
	
			case 'province':
	        {	            // 2 letter province code
	            if (preg_match('/^[a-zA-Z]{2}$/', $value))
	            {
	                $province       = strtoupper($value);
	                $domainId       = 'CA' . $province;
	            }
	            else
	            {
	                $provinceText   = htmlspecialchars($value);
	                $cc             = 'CA';
	            }
			    break;
			}		    	// 2 letter province code
	
			case 'state':
	        {	            // 2 letter state code
	            if (preg_match('/^[a-zA-Z]{2}$/', $value))
	            {
	                $province       = strtoupper($value);
	                $domainId       = 'US' . $province;
	            }
	            else
	            {
	                $provinceText   = htmlspecialchars($value);
	                $cc             = 'US';
	            }
			    break;
			}		    	// 2 letter state code
	
			case 'domain':
	        {	            // 4 letter domain code
	            if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
	            {
	                $domainId       = strtoupper($value);
	                $province       = substr($value, 2);
	            }
	            else
	            {
	                $cc             = htmlspecialchars(substr($value, 0, 2));
	                $provinceText   = htmlspecialchars(substr($value,2));
	            }
			    break;
			}		    	// 4 letter domain code
	
			case 'district':
			{	            // district number
	            if (preg_match('/^\d+(.5|)$/', $value))
				    $distId         = $value;
	            else
	                $distText       = htmlspecialchars($value);
			    break;
			}		    	// district number
	
			case 'subdistrict':
			{               // subdistrict within district
				$subDistId          = $value;
			    break;
			}			    // subdistrict within district
	
			case 'division':
	        {	            // optional division number
	            $division           = $value;
			    break;
			}			    // enumeration division
	
			case 'page':
			{		        // page number
	            if (preg_match('/^\d+$/', $value))
				    $page           = $value;
	            else
	                $pageText       = htmlspecialchars($value);
			    break;
			}	            // Page
	
			case 'line':
			{		        // line number on page
	            if (preg_match('/^\d+$/', $value))
				    $line           = $value;
	            else
	                $lineText       = htmlspecialchars($value);
			    break;
			}	            // Line
	
	        case 'lang':
	        {
	            $lang               = FtTemplate::validateLang($value);
			    break;
	        }
	
		    case 'debug':
		    {			// already handled
				break;
		    }			// already handled 
	
	        default:
	        {
	            $unexparms[]    = array($key, htmlspecialchars($value));
			    break;
	        }
	    }		        // act on specific keys
	}		        	// foreach parameter
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                       // invoked by URL to display current status of account

// create template
$template	    = new FtTemplate("CensusDetails$censusYear$lang.html");
$template->includeSub("CensusDetailsMessages$lang.html",
                      'MESSAGES');

// report unrecoverable errors in parameters
if (is_string($censusText))
{
    $msg        .= $template['invalidCensusId']->replace('$value',
                                                         $censusText);
}
if (is_string($distText))
{
    $msg        .= $template['invalidDistId']->replace('$value', 
                                                       $distText);
}
if (is_string($pageText))
{
    $msg        .= $template['nonNumericPage']->replace('$value', 
                                                        $pageText);
}
if (is_string($lineText))
{
    $msg        .= $template['nonNumericLine']->replace('$value', 
                                                        $lineText);
}
if (is_string($provinceText))
{
    $id                 = 'invalidDomainId';
    $matches            = array('$state', '$cc'); 
    $replace            = array($provinceText, $cc);
    $msg                .= $template[$id]->replace($matches,
                                                   $replace);
}

// report unexpected parameters
if (count($unexparms) > 0)
{
    $text       = $template['unexpectedParm']->innerHTML;
    foreach($unexparms as $entry)
        $warn       .= str_replace(array('$key','$value'), 
                                   $entry,
                                   $text);
}

// validate CensusID
if (is_null($censusId))
    $msg        .= $template['CensusMissing']->innerHTML;
else
{
    if ($censusYear < 1867 && substr($censusId, 0, 2) == 'CA')
        $censusId       = $province . $censusYear;
	$census		        = new Census(array('censusid' => $censusId));
	if ($census->isExisting())
	{
	    $partof                     = $census->get('partof');
	    if (is_string($partof) && strlen($partof) == 2)
	    {       // override province value from parameters
	        $domainId		        = $partof . substr($censusId, 0, 2);
	        $cc                     = $partof;
	    }       // override province value from parameters
	    else
	        $cc                     = substr($censusId, 0, 2);
	    $censusYear			        = substr($censusId, -4);
	}
	else
	{
	    $msg    .= $template['invalidCensusId']->replace('$value', 
                                                         $censusId);
        $census                     = null;
    }
}               // census is specified

// validate district
if (is_null($distId))
    $msg                .= $template['DistrictMissing']->innerHTML;
else
if (!is_null($census))
{               // district is specified
	$distObj            = new District(array('census'   => $census,
	                                         'id'       => $distId));
	if ($distObj->isExisting())
	{			// valid value
	    $districtName               = $distObj->get('name');
	    $province                   = $distObj->get('province');
	    $domainId                   = $cc . $province;
	}			// valid value
	else
    {
        $censusName                 = $census['name'];
	    $msg    .= $template->fmtmessage('unsupportedDistId',
                                         array('$value','$census'), 
                                         array($distId, $censusName));
        $distObj                    = null;
	}
}               // district is specified

// validate sub-district
if (is_null($subDistId))
    $msg            .= $template['SubDistrictMissing']->innerHTML;
else
if (!is_null($distObj))
{               // district is specified
	$subDistObj     = new SubDistrict(array('district'  => $distObj,
	                                        'sd_id'     => $subDistId,
		 						            'sd_div'  	=> $division,		
	     						            'sd_sched'	=> '1'));
	if ($subDistObj->isExisting())
	{			// valid value
	    $subDistrictName            = $subDistObj->get('name');
	}			// valid value
	else
	{
	    $msg    .= $template['invalidSubdistId']->replace(
	                                array('$value', '$div'), 
	                                array(htmlspecialchars($subDistId)));
        $subDistObj                 = null;
	}
}               // district is specified

// validate page
if (is_null($page))
    $msg                .= $template['PageMissing']->innerHTML;
else
if (!is_null($subDistObj))
{               // subDistrict is specified
    $pageObj            = $subDistObj->getPage($page);
	if ($pageObj instanceof Page)
	{
	    $lastLine       = $pageObj->get('population');
	}
	else
	{
        $pagetext       = htmlspecialchars($page);
        $text           = $template['invalidPage']->replace('$value',
                                                            $pagetext);
	}
}               // subDistrict is specified

// validate line
if (is_null($line))
    $msg                    .= $template['LineMissing']->innerHTML;

// validate Domain
if (is_null($domainId))
    $msg                    .= $template['DomainMissing']->innerHTML;
else
{               // domain identifier is specified
	$domainObj	            = new Domain(array('domain' => $domainId));
	$provinceName	        = $domainObj['name'];
	$province               = $domainObj['state'];
	$cc                     = $domainObj['cc'];
	
	if ($domainObj->isExisting())
	{			// valid value
		if ($cc == 'CA' && ($censusYear - 0) < 1867)
	    {		// pre-confederation
		    $censusId	            = $province . $censusYear;
	    }		// pre-confederation
	    $countryName                = $domainObj->getCountry()->get('name');
	}			// valid value
	else
	{
        $provincetest       = htmlspecialchars($province);
        $cctext             = htmlspecialchars($cc);
        $text   = $template['invalidDomainId']->replace(
                                            array('$state', '$cc'), 
	                                        array($provincetext, $cctext));
	}
}               // domain identifier is specified

if (strlen($subDistrictName) > 24)
    $subDistrictName        = substr($subDistrictName, 0, 21) . '...';
$template->set('CENSUSYEAR', 		$censusYear);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CENSUSID',			$censusId);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('DISTRICT',			$distId);
$template->set('DISTNAME',	    	$districtName);
$template->set('SUBDISTRICT',		$subDistId);
$template->set('SUBDISTNAME',	    $subDistrictName);
$template->set('DIVISION',			$division);
if (strlen($division) == 0)
{
    $divElt         = $template['division'];
    if ($divElt)
        $divElt->update(null);
}
$template->set('PAGE',			    $page);
$template->set('LINE',			    $line);
if (strlen($msg) == 0)
{
    if ($line > 1)
        $template->set('PREVLINE',			$line - 1);
    else
        $template->updateTag('topPrev',    null);
    if ($line < $lastLine)
	    $template->set('NEXTLINE',			$line + 1);
    else
        $template->updateTag('topNext',    null);

    $censusLine                         = $pageObj->getLine($line);
    if ($censusLine instanceof CensusLine && $censusLine->isExisting())
    {
	    $censusLine->set('CENSUSID',		$censusId);
	    $censusLine->set('PROVINCE',		$province);
	    $censusLine->set('PROVINCENAME',	$provinceName);
	    $censusLine->set('DISTNAME',	    $districtName);
        $censusLine->set('SUBDISTNAME',	    $subDistrictName);
        if ($division == '&nbsp;')
	        $censusLine->set('DIVVALUE',	'');
        else
	        $censusLine->set('DIVVALUE',	$division);
        $censusLine->set('LANG',	        $lang);
        if ($censusLine->get('idir') == 0)
        {
            $familyIdElt    = $template['IDIR'];
            if ($familyIdElt)
                $familyIdElt->update(null);
        }
        $censusLine->setGetModeHTML(true);
        $template->updateTag('dataTable',       array($censusLine));
    }
    else
    {
        $msg        .= "No matching record in transcription. ";
        $template->updateTag('topBrowse',       null);
        $template->updateTag('dataTable',       null);
    }
}
else
{                   // error message generated
    $template->updateTag('topBrowse',           null);
    $template->updateTag('dataTable',           null);
}                   // error message generated

// check for abnormal behavior of Internet Explorer
$userAgent		= $_SERVER['HTTP_USER_AGENT'];
$msiePos		= strpos($userAgent, "MSIE");
if (is_int($msiePos) && substr($userAgent, $msiePos + 5, 1) < '8')
{		// IE < 8 does not support CSS replacement of cellspacing
	$cellspacing	= "cellspacing='0px'";
}		// IE < 8 does not support CSS replacement of cellspacing
else
{		// W3C compliant implementation of cellspacing
	$cellspacing	= "";
}		// W3C compliant implementation of cellspacing
$template->set('CELLSPACING',		$cellspacing);

$template->set('CONTACTTABLE',		'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('LANG',			    $lang);

$template->display();
