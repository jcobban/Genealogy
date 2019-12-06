<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusUpdate.php													*
 *																		*
 *  This script updates one page of the year specific census of Canada	*
 *  database.															*
 *  It is invoked by CensusFormYYYY.php using method='post'.			*
 *																		*
 *  History prior to merging all CensusUpdateYYYY.php scripts:			*
 *		2010/08/21		Extract Image URL from parameters				*
 *		2010/10/01		Reformat to new page layout.					*
 *		2010/10/02		Support more or less than 25 lines in a page	*
 *		2011/01/06		Improve separation of PHP and HTML				*
 *						use shared MDB2 connection						*
 *						only offer option of next page if exists		*
 *		2011/01/15		do not count blank lines in page table stats	*
 *						credit transcription to current user			*
 *						allow the form to update any selected fields	*
 *		2011/05/15		use CSS for layout								*
 *						add missing Javascript file						*
 *		2011/09/24		set focus on update next page in division		*
 *						use buttons rather than hyperlinks for actions	*
 *		2011/10/20		Issue message to guide user if not signed in	*
 *						set NumHands to NULL if not numeric value		*
 *						generalized so this script will handle any		*
 *						census											*
 *  History after merging:												*
 *		2011/10/22		renamed consolidated script						*
 *		2011/11/06		do not mention division number if it is unused	*
 *		2012/04/14		allow more characters in an image URL			*
 *		2012/06/24		allow more characters in an image URL			*
 *		2012/09/16		use full census identifier for parameters		*
 *		2012/10/23		unused variable $warn corrected					*
 *		2012/11/02		use page1 and bypage values from SubDistTable	*
 *						to calculate last page in division and next page*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/02/25		accept age enclosed in square brackets			*
 *		2013/06/02		use class CensusLine to exploit LegacyRecord	*
 *						capability										*
 *						to update selected fields in a record			*
 *						use pageTop and pageBot to standardize			*
 *						appearance of page								*
 *		2013/06/05		only warn on bad image URL						*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/06/13		do not insert record with surname '[Delete]'	*
 *						into the database, and delete existing record	*
 *						if surname changed to '[Delete']				*
 *		2013/06/16		correct updating of birth year field from age	*
 *		2013/09/02		add dynamic debug setting						*
 *		2013/09/05		handle exceptions from setField					*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/28		use CSS for layout								*
 *		2014/04/26		use classes SubDistrict and Page to make		*
 *						update of those tables consistent and log		*
 *		2014/10/23		add search link to header and footer			*
 *		2014/12/30		use new form of Page constructor				*
 *						redirect debugging output to $warn				*
 *		2015/02/03		move onclick methods for buttons to .js			*
 *						add a close window button so dialog can be		*
 *						closed when in a frame							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/11/10		do not include division in invocation of search	*
 *		2015/11/17		update statistics in associated District		*
 *		2016/01/04		add help popups for the buttons					*
 *		2016/01/31		use class Census to validate censusId			*
 *		2016/12/26		add citation for birth for new IDIR link		*
 *						on debug do not print command to page			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/12/01      after updating database return to division      *
 *		                status display                                  *
 *																		*
 *  Copyright 2019 James A. Cobban										*
 ************************************************************************/
require_once __NAMESPACE__ . '/CensusLine.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate the parameters that identify the specific page to
// be updated

$censusId				= '';		// XX9999, XX=country code, 9999=year
$census					= null;		// Census record
$censusYear				= 0;		// census year as an integer
$province				= '';		// used in pre-confederation censuses
$distID					= '';		// district number, usually integer
$subDistID				= '';		// sub-district identifier
$division				= '';		// division
$page					= '';		// page number
$image					= '';		// image URL
$lang					= 'en';		// default language
$cc					    = 'CA';		// default country code
$countryName			= 'Canada';	// default country
$varcount				= 0;		// number of parameters passed
$parms					= array();

// loop through the input parameters first to extract the identification
// of the specific page
foreach($_POST as $key => $value)
{		// loop through all parameters
    $varcount++;
    switch(strtolower($key))
    {			// act on specific parameter
		case 'census':
		{			// census identifier supplied
		    $censusId		= $value;
		    break;
		}		// census identifier supplied

		// Province is ignored
		case 'province':
		{
		    break;
		}	// Province

		// District is mandatory and must be numeric
		case 'district':
		{		// District supplied
		    $distID			    = $value;
		    $parms['District']	= $value;
		    break;
		}		// District supplied

		// SubDistrict is mandatory
		case 'subdistrict':
		{		// SubDistrict supplied
		    $subDistID		    = $value;
		    $parms['SubDistrict']	= $value;
		    break;
		}		// SubDistrict supplied

		// Division is mandatory even though it is
		// not officially used in some censuses
		case 'division':
		{		// Division supplied
		    $division		    = $value;
		    $parms['Division']	= $value;
		    break;
		}		// Division supplied

		// page number is mandatory and must be numeric
		case 'page':
		{		// Page supplied
		    $page			    = $value;
		    $parms['Page']		= $value;
		    break;
		}		// Page supplied

		case 'image':
		{		// Image supplied
		    $image	            = $value;
		    if (!preg_match("/^[0-9a-zA-Z:_. \-+=\/&?]*$/", $image))
				$warn	        .= "Image URL '$image' contains invalid characters. ";
		    break;
		}		// Image supplied

		case 'lang':
		{		// Image supplied
			$lang	            = FtTemplate::validateLang($value);
		    break;
		}		// Image supplied

    }	        // act on specific parameter
}		        // loop through all parameters

$template		        = new FtTemplate("CensusUpdate$lang.html");

// update database only if authorized
if (!canUser('edit'))
    $msg	            .= $template['notAuthorized']->innerHTML;

// check for mandatory parameters
if ($censusId == '')
{		        // Census not identified
    $msg	            .= $template['censusMissing']->innerHTML;
}		        // Census not identifier
else
{               // censusId specified
    $census		            = new Census(array('censusid' => $censusId));
    $parms['Census']	    = $census;
    if ($census->isExisting())
    {		    // already defined census
		$censusYear	        = $census->get('year');
		if ($censusYear < 1867)
		{	    // pre-confederation census age is at next birthday
		    $province		= substr($censusId, 0, 2);
		    $parms['Province']	= $province;
		    $parms['BYear']	= $censusYear;
		    $partOf		    = $census->get('partof');
		    if (strlen($partOf) == 2)
			    $cc		    = $partOf;
		}	    // pre-confederation census age is at next birthday
		else
		{	    // post-confederation census age is at enumeration
		    $parms['BYear']	= $censusYear - 1;
		    $cc			    = substr($censusId, 0, 2);
        }	    // post-confederation census age is at enumeration

		$country	        = new Country(array('cc'	=> $cc));
		$countryName	    = $country->getName($lang);
    }		    // already defined census
    else
    {           // Census undefined
        $text               = $template['censusUndefined']->innerHTML;
        $msg	            .= str_replace('$censusId', $censusId, $text);
    }           // Census undefined
}               // censusId specified

if ($distID == '')
{		        // District not supplied
    $msg	                .= $template['districtMissing']->innerHTML;
}		        // District not supplied
else
{		        // District supplied
    if (preg_match("/^[0-9]+(\.5|\.0)?$/", $distID))
    {           // syntactically correct numeric district ID
		$district	        = new District(array('d_census'	=> $censusId,
                                                 'd_id'	    => $distID));
        if ($district->isExisting())
        {
		    $districtName   = $district->get('d_name');
		    $province	    = $district->get('d_province');
		    $domain		    = new Domain(array('domain'     => $cc . $province,
				                    		   'language'   => $lang));
            $provinceName	= $domain->get('name');
        }
        else
        {
            $text           = $template['districtUndefined']->innertHTML;
            $msg            .= str_replace(array('$distID','$censusId'),
                                           array($distID, $censusId),
                                           $text);
        }
    }           // syntactically correct numeric district ID
    else
        $msg	            .= $template['districtInvalid']->innerHTML;
}		        // District supplied

if ($subDistID == '')
{		        // SubDistrict not supplied
    $msg	                .= $template['subDistrictMissing']->innerHTML;
}		        // SubDistrict not supplied
else
{               // SubDistrict identifier supplied
	$subDist	            = new SubDistrict(array('sd_census'	=> $census,
							    			        'sd_distid'	=> $district,
												    'sd_id'		=> $subDistID,
								    				'sd_div'	=> $division,
                                                    'sd_sched'	=> '1'));
	$pages		        	= intval($subDist->get('sd_pages'));
	$page1		        	= intval($subDist->get('sd_page1'));
    $bypage		        	= intval($subDist->get('sd_bypage'));

    if (!$subDist->isExisting())
    {
        $text               = $template['subdistrictUndefined']->innertHTML;
        $msg                .= str_replace(array('$subDistID','$distID'),
                                           array($subDistI, $distID),
                                           $text);
    }
}               // SubDistrict identifier supplied

if ($page == '')
{		        // Page not supplied
    $msg	                .= $template['pageMissing']->innerHTML;
}		        // Page not supplied
else
{
    if (ctype_digit($page))
    {
        $page               = intval($page);
        if ($page < $page1 || 
            $page > ($page1 + $bypage * ($pages - 1)) ||
            ($bypage == 2 && ($page % 2) != 1))
        {
            $text	        = $template['pageRange']->innerHTML;
            $msg            .= str_replace('$page', $page, $text);
        }
    }
    else
    {
        $text	            = $template['pageInvalid']->innerHTML;
        $msg                .= str_replace('$page', $page, $text);
    }
}

// if no errors were encountered in validating the parameters
// proceed to update the database
if (strlen($msg) == 0)
{		        // no errors in validating page identification
	$subDistrictName    	= $subDist->get('sd_name');
	$lastPage	        	= $page1 + ($pages - 1) * $bypage;
	$nextPage	        	= intval($page) + $bypage;
	$prevPage	        	= intval($page) - $bypage;
	if ($nextPage > $lastPage)
	    $nextPage	    	= 0;	// no next page
	if ($prevPage < $page1)
	    $prevPage	    	= 0;	// no previous page
	$ptparms	        	= array('pt_sdid'	=> $subDist,
					             	'pt_page'	=> $page);
	$pageEntry	        	= new Page($ptparms);

	// loop through all of the field values passed from
	// the input form    
	$count		        	= 0;	// number of individuals inserted
	$oldrow		        	= '';	// detect change in row number
	$numParms	        	= 0;	// debugging count
	$record		        	= null;	// instance of CensusLine
	foreach($_POST as $key => $value)
	{		    // loop through all input fields
	    $numParms	++;
	    if ($debug)
			    $warn	    .= $key . "='" . $value . "',";
	    $row	            = substr($key, strlen($key) - 2);

	    // ignore fields whose names do not end in 2 decimal digits
	    if (!ctype_digit($row))
			continue;

	    // if the row number changes, update the database
	    if ($row != $oldrow)
	    {
			if (!is_null($record))
			{		// have an instance of CensusLine
			    if ($record->isExisting())
			    {	// updating existing record
					if (strtolower($record->get('surname')) ==
									    '[delete]')
					    $record->delete();
					else
					    $record->save(false);	// save changes
			    }	// updating existing record
			    else
			    {	// inserting new record
					if (strtolower($record->get('surname')) !=
					    '[delete]')
					    $record->save(false);
			    }	// inserting new record
			}		// have an instance of CensusLine 
			$oldrow		= $row;
			$record		= new CensusLine($parms, intval($row));
			$byearset	= false;
	    }

	    // get column name portion of field name
	    $colname	= strtolower(substr($key, 0, strlen($key) - 2));
	    if ($colname == 'setidir')
	    {
			$idsr		= $census->get('idsr');
			if (strlen($division) > 0)
			    $srcdetail	= "dist $distID $districtName, subdist $subDistID $subDistrictName, div $division page $page";
			else
			    $srcdetail	= "dist $distID $districtName, subdist $subDistID $subDistrictName, page $page";
			if ($censusYear < 1867)
			    $srcdetail	= $province . ", " . $srcdetail;
			$idir		= $value;
			$person		= new Person(array('idir' => $idir));
			$birth		= $person->getBirthEvent(false);
			if ($birth)
			{
			    $birth->save(false);
			    if ($debug)
					$warn	.= "<p>citparms=array(" .
									"'idime' => " .$birth->getIder(). "," .
									"'idsr' => $idsr," .
							       "'type' => Citation::STYPE_EVENT,".
									"'srcdetail' => $srcdetail)</p>";
			    $citparms	= array('idime'	=> $birth->getIder(),
									'idsr'	=> $idsr,
									'type'	=> Citation::STYPE_EVENT,
									'srcdetail' => $srcdetail);
			    $cit	= new Citation($citparms);
			    $cit->save(false);
			}
	    }
	    else
	    {		// update database record
			if ($colname == 'idir' && $value == 0)
			    $record->set('idir', 0);
			else
			    $record->set($colname, $value);
	    }		// update database record

	    // count number of individuals on the page
	    if ($colname == 'surname')
	    {
			if (strtolower($value) != '[blank]' &&
			    strtolower($value) != '[deleted]' &&
			    strtolower($value) != '[scratched out]')
			    $count++;	// count actual number of individuals
	    }	    // surname

	}		    // loop through all parameters
	if ($debug)
	    $warn	.= "<p>numParms=$numParms</p>";

	// update the last row on the page
	if ($record->isExisting())
	{	        // updating existing record
	    if (strtolower($record->get('surname')) == '[delete]')
			$record->delete();
	    else
			$record->save(false);	// save changes
	}	        // updating existing record
	else
	{	        // inserting new record
	    if (strtolower($record->get('surname')) != '[delete]')
			$record->save(false);
	}	        // inserting new record

	// register the update in the Pages table
	$pageEntry->set('pt_population',$count);
	$pageEntry->set('pt_transcriber',$userid);
	$pageEntry->set('pt_image',	$image);
	$pageEntry->save($debug);

	// ensure the transcription statistics in the District
	// are synchronized
	$district	= $subDist->getDistrict();
	$district->synchPopulation();
    $district->save(false);

    if (strlen($warn) == 0)
    {
        $host           =  $_SERVER['HTTP_HOST'];
        header("Location: https://$host/database/CensusUpdateStatusDetails.php?Census=$censusId&Province=$province&District=$distID&SubDistrict=$subDistID&Division=$division&page=$page&lang=$lang");
        exit;
    }
}		        // no errors in validating page identifier

$template->set('CENSUSYEAR', 		$censusYear);
$template->set('CC',			    $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CENSUSID',			$censusId);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('LANG',			    $lang);
$template->set('DISTRICT',			$distID);
$template->set('DISTRICTNAME',		$districtName);
$template->set('SUBDISTRICT',		$subDistID);
$template->set('SUBDISTRICTNAME',	$subDistrictName);
$template->set('DIVISION',			$division);
$template->set('PAGE',			    $page);
$template->set('PREVPAGE',			$page - $bypage);
$template->set('NEXTPAGE',			$page + $bypage);
$template->set('CENSUS',			$censusYear);
$template->set('CONTACTTABLE',		'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('IMAGE',	            $image);

if (strlen($province) == 0)
{
    $template->updateTag('frontProv', null);
    $template->updateTag('backProv', null);
}
if (strlen($division) == 0)
{
    $template->updateTag('nextPageDivision', null);
    $template->updateTag('prevPageDivision', null);
    $template->updateTag('resultsDivision', null);
}
if ($nextPage == 0)
    $template->updateTag('nextPagePara', null);
if ($prevPage == 0)
    $template->updateTag('prevPagePara', null);
$promptTag	= $template->getElementById('ImagePrompt');
if (strlen($image) == 0)
    $template->updateTag('ImageButton', null); // hide
else
if ($promptTag)
    $promptTag->update(null); // hide

$template->display();
