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
 *		District		district (County) number within 1851 census		*
 *		SubDistrict		subdistrict (Township) number within district	*
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
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
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
$censusId			= 'CA9999';
$countryName        = 'Canada';
$table              = null;
$domainId			= '';
$distId			    = '';
$districtName		= '';
$subDistId			= '';
$subDistrictName	= '';
$division			= '';
$lang			    = 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
foreach ($_GET as $key => $value)
{			            // loop through all parameters
	if (strlen($value) > 0)
	{		            // only process non-empty parameters
	    switch(strtolower($key))
        {		        // act on specific keys
            case 'census':
            {
                $censusId           = $value;
                $censusYear         = substr($value, 2);
                $table	            = 'Census' . $censusYear;
                break;
            }

			case 'province':
            {	            // 2 letter province code
                $domainId           = 'CA' . $value;
                $province           = $value;
                if ($censusYear < 1867)
                    $censusId       = $province . $censusYear;
			    break;
			}		    	// 2 letter province code

			case 'state':
            {	            // 2 letter state code
                $domainId           = 'US' . $value;
			    break;
			}		    	// 2 letter state code

			case 'domain':
            {	            // 4 letter domain code
                $domainId           = $value;
                $province           = substr($value, 2);
			    break;
			}		    	// 4 letter domain code

			case 'district':
			{	            // district number
				$distId             = $value;
			    break;
			}		    	// district number
	
			case 'subdistrict':
			{           // subdistrict within district
				$subDistId          = $value;
			    break;
			}			// subdistrict within district

			case 'division':
            {	        // optional division number
                $division           = $value;
			    break;
			}			// enumeration division

			case 'page':
			{		    // page number
				$page		        = $value;
			    break;
			}	    // Page

			case 'line':
			{		// line number on page
				$line		        = $value;
			    break;
			}	    // Line

            case 'lang':
            {
                if (strlen($value) >= 2);
                    $lang           = strtolower(substr($value,0,2));
			    break;
            }
	    }		    // act on specific keys
	}		        // non-empty value
}		        	// foreach parameter

// create template
$template	    = new FtTemplate("CensusDetails$censusYear$lang.html");

// parameters for getting instance of CensusLine
$getParms                       = array();

// validate CensusID
$census		= new Census(array('censusid' => $censusId));
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
	$getParms['censusid']	    = $census;
    $censusYear			        = substr($censusId, -4);
}
else
{
    $text       = $template->getElementById('invalidCensusId')->innerHTML();
    $msg	    .= str_replace('$value', $censusId, $text);
}

// validate district
$distObj            = new District(array('census'   => $census,
                                         'id'       => $distId));
if ($distObj->isExisting())
{			// valid value
    $districtName               = $distObj->get('name');
    $getParms['district']       = $distId;
    $province                   = $distObj->get('province');
    $domainId                   = $cc . $province;
}			// valid value
else
{
    $text       = $template->getElementById('invalidDistId')->innerHTML();
    $msg	    .= str_replace('$value', $distId, $text);
}

// validate sub-district
$subdistObj         = new SubDistrict(array('district'  => $distObj,
                                            'sd_id'     => $subDistId,
	 						                'sd_div'  	=> $division,		
     						                'sd_sched'	=> '1'));
if ($subdistObj->isExisting())
{			// valid value
    $subDistrictName            = $subdistObj->get('name');
    $getParms['subdistrict']    = $subDistId;
    $getParms['division']       = $division;
}			// valid value
else
{
    $text       = $template->getElementById('invalidSubdistId')->innerHTML();
    $msg	    .= str_replace(array('$value', '$div'), 
                               array($subDistId, $division), 
                               $text);
}

// validate page
$pageObj            = new Page($subdistObj,
                               $page);
if ($pageObj->isExisting())
{
    $lastLine                   = $pageObj->get('population');
    $getParms['page']           = $page;
}
else
{
    $text       = $template->getElementById('invalidPage')->innerHTML();
    $msg	    .= str_replace('$value', $page, $text);
}

// validate Domain
$domainObj	            = new Domain(array('domain' => $domainId));
$provinceName	        = $domainObj->get('name');
$province               = substr($domainId, 2);
$cc                     = substr($domainId, 0, 2);

if ($domainObj->isExisting())
{			// valid value
	if (($censusYear - 0) < 1867)
    {		// pre-confederation
	    $censusId	                = $province . $censusYear;
	    $getParms['province']	    = $province;
    }		// pre-confederation
    $countryName                    = $domainObj->getCountry()->get('name');
}			// valid value
else
{
    $warn       .= "<p>" . __LINE__ . " domainId='$domainId', province='$province'</p>\n";
    $text       = $template->getElementById('invalidDomainId')->innerHTML();
    $msg	    .= str_replace(array('$state', '$cc'), 
                               array($province, $cc),
                               $text);
}

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
    $divElt         = $template->getElementById('division');
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

    $getParms['line']                   = $line;
    $censusLine                         = new CensusLine($getParms);
    if ($censusLine->isExisting())
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
            $familyIdElt    = $template->getElementById('IDIR');
            if ($familyIdElt)
                $familyIdElt->update(null);
        }
        $censusLine->setGetModeHTML(true);
        $template->updateTag('dataTable',     array($censusLine));
    }
    else
    {
        $msg        .= "No matching record in transcription. ";
        $template->updateTag('linksFront',      null);
        $template->updateTag('dataTable',         null);
    }
}
else
{                   // error message generated
    $template->updateTag('linksFront',      null);
    $template->updateTag('dataTable',         null);
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
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',			    $lang);

$template->display();
