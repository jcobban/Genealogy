<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BirthRegDetail.php													*
 *																		*
 *  Display the results of a query of an individual record from the		*
 *  birth registrations table and if the user is authorized permits		*
 *  updating the record													*
 *																		*
 *  Parameters:															*
 *		RegYear			year the birth was registered					*
 *		RegNum			registration number within year					*
 *    or ...															*
 *		RegYear			year the birth was registered					*
 *		OriginalVolume	hardcopy volume number							*
 *		OriginalPage	page number in hardcopy volume					*
 *		OriginalItem	ordinal position on page						*
 *																		*
 *  History:															*
 *		2010/08/28		Change to new layout							*
 *						Fix warnings for missing parameters				*
 *		2010/12/30		fix handling of parent's married indicator		*
 *						improve separation of HTML and PHP				*
 *						permit editing the image file name				*
 *		2011/02/21		improve separation of HTML and Javascript		*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *		2011/04/09		use small labels								*
 *						change $txt... variables to only contain class	*
 *						name											*
 *		2011/04/21		fill in some fields from preceding record even	*
 *						if it is not the record with number regnum-1	*
 *		2011/05/21		change associated javascript file name			*
 *						use CSS to format header and footer				*
 *						add fields to hold place of work of parents		*
 *						standardize all places at 64 characters			*
 *						all names at 48 characters						*
 *						expand image and remarks to 256 characters		*
 *						ensure all input fields enforce actual field	*
 *						lengths											*
 *		2011/06/16		put more information into mailto				*
 *		2011/06/24		change default birthplace to township of		*
 *						registration									*
 *						change default informant residence to township	*
 *		2011/07/15		move informant after marriage info				*
 *		2011/08/10		put links to project status in breadcrumbs		*
 *		2012/02/10		make remarks a text area						*
 *						include all Ontario county abbreviations		*
 *						improve layout									*
 *						support short-cut key strokes					*
 *		2012/02/19		avoid error when refreshing						*
 *		2012/03/04		include regyear and regnum fields in read-only	*
 *		2012/03/14		add help for RegId field						*
 *						support B_IDIR field for link to family tree	*
 *						database										*
 *		2012/03/16		major cleanup of interpreting fields in record	*
 *						move county names table to countyNames.inc so it*
 *						can be shared with other scripts				*
 *		2012/03/31		make all variable names start with lower case	*
 *						letter											*
 *						display selection list of matching individuals	*
 *						if no existing citation to registration			*
 *		2012/04/16		avoid REGEXP error with non-alphabetic given	*
 *						names											*
 *		2012/05/28		set class in <option>s based upon sex			*
 *		2012/06/02		display & update Ontario Archives microfilm		*
 *						reel number										*
 *		2012/07/01		add line for registration date and name of		*
 *						registrar										*
 *						expand places to 128 characters					*
 *		2012/10/27		correct missing = sign in MarriageDate field	*
 *		2013/03/08		interpret all numeric registration date			*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/06/27		display remarks as text rather than in textarea	*
 *						if user not authorized to update				*
 *		2013/06/27		use tinyMCE for editing remarks					*
 *						display remarks as text to visitors				*
 *						remove use of Select for informant relationship	*
 *						replacing with abbreviation support				*
 *		2013/11/15		handle missing database connection gracefully	*
 *						clean up parameter handling						*
 *						add RegDomain parameter							*
 *						use class County to obtain county name			*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/16		use CSS for form layout instead of table		*
 *						use class Death to access database				*
 *		2014/01/24		use name from database in link					*
 *		2014/02/06		replace checked with selected in sex select		*
 *		2014/02/17		use common routine getSurnameChk to search		*
 *						for matching individuals by name				*
 *		2014/03/08		set gender specific class for link to match		*
 *						in family tree									*
 *						sex select statement initialized by PHP			*
 *		2014/03/10		include parents names in list of possible		*
 *						matches											*
 *		2014/03/27		allow autocomplete								*
 *		2014/04/03		order possibly matches by name					*
 *		2014/06/13		add clear button for IDIR association			*
 *						update field B_IDIR for association defined		*
 *						through a citation								*
 *						partially initialize blank birth records		*
 *						if there is an existing citation				*
 *		2014/09/13		marriage place not available before 1908		*
 *		2014/11/13		add header/footer link to township summary		*
 *		2014/12/18		generalize for all provinces and move to		*
 *						folder Canada									*
 *		2015/02/23		use LegacyIndiv->getName with date option		*
 *		2015/04/06		handle exception in LegacyIndiv::getIndivs		*
 *		2015/04/19		if possible display the image by splitting		*
 *						the window										*
 *		2015/05/01		support new parameter ShowImage					*
 *						add id attributes to all form rows				*
 *		2015/06/10		contents of textarea should not be escaped		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/18		clean up given names before using in search		*
 *		2015/11/07		remarks edit widget misplaced					*
 *		2016/01/19		add id to debug trace							*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/06		add link to current county summary in header	*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/01/02		$countyName was not initialized on error		*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/02/07		use class Country								*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *		2017/07/12		use locationCommon to expand location names		*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/03/17		correct alignment of registration number		*
 *						adjust length of remarks and image fields		*
 *						to match width of rest of form					*
 *		2018/10/03      use class Template                              *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// set up based upon the user's level of authorization
if (canUser('edit'))
{		// user is authorized to edit the database
	$update	    	= true;
	$action		    = 'Update';
}		// user is authorized to edit the database
else
{		// user can only view the contents
	$update		    = false;
	$action		    = 'Display';
}		// user can only view the contents

//	data base column name prefix
$dbprefix       	= "b_";

// validate parameters
$regYear		    = '';
$regNum		        = '';
$volume				= '';
$page				= '';
$item				= '';
$domain				= 'CAON';	// default domain
$cc					= 'CA';
$code				= 'ON';
$countryName		= 'Canada';
$domainName			= 'Ontario';
$regCounty			= '';
$countyName			= '';
$regTownship		= '';
$lang       		= 'en';
$indiv		    	= null;
$imatches			= null;

foreach($_GET as $key => $value)
{			// loop through all input parameters
	switch(strtolower($key))
	{		// process specific named parameters
	    case 'regyear':
	    {
			$regYear	= $value;
			if (preg_match("/^([0-9]{4})$/", $regYear) == 0 ||
			    ($regYear < 1860) || ($regYear > 2020))
			{
			    $msg	.= "Registration Year $regYear must be a number between 1860 and 2020. ";
			}
			break;
	    }		// RegYear passed

	    case 'regnum':
	    {
			$regNum 	= $value - 0;
			if (preg_match("/^([0-9]{2,7})$/", $regNum) == 0)
			{
			    $msg	.= "Registration Number $regNum must be a number. ";
			}
			break;
	    }		// RegNum passed

	    case 'originalvolume':
	    {
			$volume		= $value;
			$numVolume	= preg_replace('/[^0-9]/', '', $value);
			break;
	    }		// RegNum passed

	    case 'originalpage':
	    {
			if (ctype_digit($value))
			    $page   	= $value;
			else
			    $msg	    .= "Page Number $value must be a number. ";
			break;
	    }		// RegNum passed

	    case 'originalitem':
	    {
			if (ctype_digit($value))
			    $item	= $value;
			else
			    $msg	.= "Item Number $value must be a number. ";
			break;
	    }		// RegNum passed

	    case 'regdomain':
	    {
			$domain		     = $value;
			$domainObj	        = new Domain(array('domain'	    => $domain,
							                       'language'	=> 'en'));
			if ($domainObj->isExisting())
			{
			    $cc		    	= substr($domain, 0, 2);
			    $code		    = substr($domain, 2, 2);
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->getName();
			    $domainName		= $domainObj->get('name');
			}
			else
			    $domainName		= "Unknown Domain '$domain'";
			break;
	    }		// RegDomain

	    case 'showimage':
	    {
			break;
	    }		// handled by JavaScript

	    case 'debug':
	    {
			break;
	    }		// debug set by common code

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
			break;
        }
	    default:
	    {
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		// process specific named parameters
}			// loop through all input parameters

if ($regYear == '')
{
	$msg		.= "RegYear omitted. ";
}

if ($regNum == '' && $volume == '')
{
	$msg		.= "RegNum omitted. ";
}
else
if ($volume != '' && $page != '' && $item != '')
{
	$regNum		= $numVolume .
				  str_pad($page, 3, '0', STR_PAD_LEFT) .
				  str_pad($item, 2, '0', STR_PAD_LEFT);
    $paddedRegNum	= str_pad($regNum,7,"0",STR_PAD_LEFT);
}
else
    $paddedRegNum	= str_pad($regNum,7,"0",STR_PAD_LEFT);

// start the template
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "BirthRegDetail$action$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	= new Language(array('code' => $lang));
	$langName	= $language->get('name');
	$nativeName	= $language->get('nativename');
    $sorry  	= $language->getSorry();
    $warn   	.= str_replace(array('$langName','$nativeName'),
                               array($langName, $nativeName),
                               $sorry);
	$includeSub	= "BirthRegDetail" . $action . "en.html";
}
$template->includeSub($tempBase . $includeSub,
		    		  'MAIN');
if (file_exists($tempBase . "Trantab$lang.html"))
    $trtemplate = new Template("${tempBase}Trantab$lang.html");
else
    $trtemplate = new Template("${tempBase}Trantaben.html");

// pass parameters to template
$template->set('regYear',		$regYear);
$template->set('regNum',		$regNum);
$template->set('paddedRegNum',	$paddedRegNum);
$template->set('volume',		$volume);
$template->set('page',		    $page);
$template->set('item',		    $item);
$template->set('domain',		$domain);
$template->set('cc',		    $cc);
$template->set('code',	        $code);
$template->set('countryName',	$countryName);
$template->set('domainName',	$domainName);
$template->set('regCounty',		$regCounty);
$template->set('countyName',	$countyName);
$template->set('regTownship',	$regTownship);
$template->set('LANG',		    $lang);
$template->set('CONTACTTABLE',	'Births');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);

// internationalization support
$monthsTag		    = $trtemplate->getElementById('Months');
if ($monthsTag)
{
	$months	    	= array();
	foreach($monthsTag->childNodes() as $span)
	    $months[]	= trim($span->innerHTML());
}
$lmonthsTag		    = $trtemplate->getElementById('LMonths');
if ($lmonthsTag)
{
	$lmonths		= array();
	foreach($lmonthsTag->childNodes() as $span)
	    $lmonths[]	= trim($span->innerHTML());
}

// if no error messages Issue the query
if (strlen($msg) == 0)
{			// no errors
	// get the birth registration object
	$birth			= new Birth($domain, $regYear, $regNum);

	// copy contents into working variables
	$surname		                = $birth->get('b_surname');
	$givennames                     = $birth->get('b_givennames');
	$sex	                		= $birth->get('b_sex');
	$birthdate	                	= $birth->get('b_birthdate');
	$idir               			= $birth->get('b_idir');
    $regCounty              		= $birth->get('b_regcounty');

	$countyObj              		= new County($domain, $regCounty);

	// set $gender to the code used in tblIR
	// set $genderClass to the CSS class
	if ($sex == 'M')
	{			// male
        $gender                             = 0;
	    $template->set('gender',			0);
	    $template->set('genderClass',		'male');
	}			// male
	else
	if ($sex == 'F')
	{			// female
        $gender                             = 1;
	    $template->set('gender',			1);
	    $template->set('genderClass',		'female');
	}			// female
	else
	{			// unknown
        $gender                             = 2;
	    $template->set('gender',			2);
	    $template->set('genderClass',		'unknown');
	}			// unknown

	// if this registration is not already linked to
	// look for individuals who match

	if ($idir == 0 && $update)
	{				// no existing link to this reg
	    // check for existing citations to this registration
	    $citparms   	= array('idsr'		=> 97,
    		    	    	    'type'		=> Citation::STYPE_BIRTH,
	    		    	        'srcdetail'	=> "^$regYear-0*$regNum"); 
	    $citations		= new CitationSet($citparms);

	    if ($citations->count() > 0)
	    {				// citation to birth in old location
			$citrow	= $citations->rewind();
			$idir	= $citrow->get('idime');
	    }				// citation to birth in old location
	    else
	    {				// citation to birth in event
			$citparms	= array('idsr'		=> 98,
				                'type'		=> Citation::STYPE_EVENT,
				                'srcdetail'	=> "^$regYear-0*$regNum"); 
			$citations	= new CitationSet($citparms);
			foreach($citations as $idsx => $citation)
			{			// loop through citations
			    $ider       	= $citation->get('idime');
			    $event	        = new Event($ider);
			    if ($event->getIdet() == Event::ET_BIRTH)
			    {			// citation for birth event
    				$idir		= $event->getIdir();
	    			break;
			    }			// citation for birth event
			}			// loop through citations
	    }				// citation to birth in event

	    // if still no IDIR value and we have a filled registration
	    // search for matches
	    if ($idir == 0 && strlen($surname) > 0 && strlen($givennames) > 0)
	    {				// existing record
			// get the year portion of the date string
			$count		= preg_match('/[0-9]{4}/',
	        					     $birthdate,
			        			     $matches);
			if ($count > 0)
			    $birthYear	= intval($matches[0]);
			else
			    $birthYear	= $regYear;
			// look 2 years on either side of the year
			$birthrange	= array(($birthYear - 2) * 10000,
    							($birthYear + 2) * 10000);

			// search for a match on any of the parts of the
			// given name
			if (strtolower($givennames) == '[blank]')
			    $gnameList	= '';
			else
			{			// not explicitly blank
			    // remove letters with special meaning
			    $givennames	= str_replace(array('[',']','(',')',"'",'"'),
						                  '',
					            	      $givennames);
			    // separate given names
			    $gnameList	= explode(' ', $givennames);
			    if (count($gnameList) == 1)
	    			$gnameList	= $givennames;
			}			// not explicitly blank

            // identify potential matches in the family tree
            if ($gender <= 1)
			    $getparms	= array('loose'		=> true,
			        			    'surname'	=> $surname,
				        		    'givenname'	=> $gnameList,
			        			    'gender'	=> $gender,
				        		    'birthsd'	=> $birthrange);
            else
			    $getparms	= array('loose'		=> true,
			        			    'surname'	=> $surname,
				        		    'givenname'	=> $gnameList,
				        		    'birthsd'	=> $birthrange);

			$imatches	= new PersonSet($getparms);
	    }				// existing record
	    else
	    if ($idir > 0 && strlen($surname) == 0 && strlen($givennames) == 0)
	    {				// fill in the blanks
			try {
			    $indiv	    	= new Person(array('idir' => $idir));
			    $surname		= $indiv->get('surname');
			    $givennames		= $indiv->get('givenname');
			    $gender		    = $indiv->get('gender');
			    if ($gender == 0)
				    $sex		= 'M';
			    else
			    if ($gender == 1)
				    $sex		= 'F';
			    else
				    $sex		= '?';
			    $birthe		= $indiv->getBirthEvent(false);
			    if ($birthe)
			    {			// have a birth event
				    $birtho		= new LegacyDate($birthe->get('eventd'));
		    		$birthdate	= $birtho->toString();
		    		$idlr		= $birthe->get('idlrevent');
		    		$location	= new Location($idlr);
		    		$birth->set('b_birthplace',
		        			    $location->getName());
		    		$birth->set('b_fatheroccplace',
		        			    $location->getName());
		    		$birth->set('b_motheroccplace',
		        			    $location->getName());
			    	$birth->set('b_marriageplace',
			        		    $location->getName());
				    $birth->set('b_informantres',
				        	    $location->getName());
			    }			// have a birth event

			    // add information about parents
			    $parents	= $indiv->getPreferredParents();
			    if ($parents)
			    {			// have preferred parents
				    $birth->set('b_fathername',
				        	    $parents->getHusbName());
				    $birth->set('b_mothername',
					            $parents->getWifeName());
			    }			// have preferred parents
			    else
			    {			// parents unknown
				    $birth->set('b_fathername',
					            $surname);
			    }			// parents unknown
			    $linkedName	= $indiv->getName(Person::NAME_INCLUDE_DATES);
			    $template->set('linkedName',   $linkedName);
			} catch (Exception $e) {
			    $msg	.= "Trying to get information from IDIR=$idir: Exception: " . $e->getMessage();
			}			// catch
	    }				// fill in the blanks
	}				// no existing link to this reg

	// get name for link
	if ($idir > 0)
	{		// existing link
	    if (is_null($indiv))
			$indiv	= new Person(array('idir' => $idir));
	    $linkedName	= $indiv->getName(Person::NAME_INCLUDE_DATES);
	    $template->set('linkedName',   $linkedName);
	}				// existing link to family tree

	$subject	= "$domainName Birth Registration: number: " . 
				  $regYear . '-' . $regNum . ', ' . 
				  $givennames . ' ' . $surname;
}			// no error messages
else
{			// error detected
	$subject	= "$domainName Birth Registration: number: " . 
				  $regYear . '-' . $regNum ;
}			// error detected

$title  	= "$domainName Birth Registration: " . $action;
$subject	= rawurlencode($subject);

// pass substitutions to template
$template->set('surname',		$surname);
$template->set('givenNames',	$givennames);
$template->set('sex',			$sex);
$template->set('birthDate',		$birthdate);
$template->set('idir',			$idir);
$template->set('regCounty',		$regCounty);
$template->set('countyName',	$countyObj->get('name'));
$template->set('regTownship',	$birth->get('b_regtownship'));
$template->set('subject',	    rawurlencode($subject));

$template->set('regDomain',		$birth->get('b_regdomain'));
$template->set('regYear',		$birth->get('b_regyear'));
$template->set('regNum',		$birth->get('b_regnum'));
$template->set('regCounty',		$birth->get('b_regcounty'));
$template->set('regTownship',	$birth->get('b_regtownship'));
$template->set('msvol',		    $birth->get('b_msvol'));
$template->set('qsurname',		$surname);
$template->set('surnameSoundex',$birth->get('b_surnamesoundex'));
if ($sex == 'M')
{
    $template->set('sexmaleselected',			"selected='selected'");
    $template->set('sexfemaleselected',			"");
    $template->set('sexotherselected',			"");
}
else
if ($sex == 'F')
{
    $template->set('sexmaleselected',			"");
    $template->set('sexfemaleselected',			"selected='selected'");
    $template->set('sexotherselected',			"");
}
else
{
    $template->set('sexmaleselected',			"");
    $template->set('sexfemaleselected',			"");
    $template->set('sexotherselected',			"selected='selected'");
}

$template->set('birthPlace',		$birth->get('b_birthplace'));
$template->set('birthDate',			$birthdate);
$template->set('calcbirth',			$birth->get('b_calcbirth'));
$parentsMarried                     = $birth->get('b_parentsmarried');
if ($parentsMarried == 'Y')
    $template->set('marriedChecked',"checked='checked'");
else
    $template->set('marriedChecked',"");
$template->set('fatherName',		$birth->get('b_fathername'));
$template->set('fatherOccupation',	$birth->get('b_fatheroccupation'));
$template->set('fatherOccPlace',	$birth->get('b_fatheroccplace'));
$template->set('motherName',		$birth->get('b_mothername'));
$template->set('motherOccupation',	$birth->get('b_motheroccupation'));
$template->set('motherOccPlace',	$birth->get('b_motheroccplace'));
$formerHusband	                	= $birth->get('b_formerhusband');
$template->set('formerHusband',		$formerHusband);
$template->set('marriagePlace',		$birth->get('b_marriageplace'));
$template->set('marriageDate',		$birth->get('b_marriagedate'));
$template->set('accoucheur',		$birth->get('b_accoucheur'));
$template->set('informant',			$birth->get('b_informant'));
$template->set('informantRes',		$birth->get('b_informantres'));
$template->set('informantRel',		$birth->get('b_informantrel'));
$template->set('regDate',			$birth->get('b_regdate'));
$template->set('registrar',			$birth->get('b_registrar'));
$remarks			                = $birth->get('b_remarks');
$template->set('remarks',			$remarks);
$image			                    = $birth->get('b_image');
$template->set('image',			    $image);
$template->set('originalVolume',	$birth->get('b_originalvolume'));
$template->set('originalPage',		$birth->get('b_originalpage'));
$template->set('originalItem',		$birth->get('b_originalitem'));
$template->set('changedBy',			$birth->get('b_changedby'));
$template->set('changeDate',		$birth->get('b_changedate'));

if (!canUser('all'))
{		// master user
    $template->updateTag('Skip5', null);
}		// master user

if ($regNum > 1000000)
    $template->updateTag("hiddenVolPageItem", null);
else
    $template->updateTag("explicitVolPageItem", null);

if ($idir == 0)
{           	// no existing link to family tree
    $template->updateTag("IdirRow", null);
	if ($imatches && count($imatches) > 0)
    {       	// matched to some individuals in database
		foreach($imatches as $iidir => $indiv)
		{
		    $isex       	= $indiv->get('gender');
		    if ($isex == Person::MALE)
		    {
				$sexclass	= 'male';
				$childrole	= 'son';
		    }
		    else
		    if ($isex == Person::FEMALE)
		    {
				$sexclass	= 'female';
				$childrole	= 'daughter';
		    }
		    else
		    {
				$sexclass	= 'unknown';
				$childrole	= 'child';
		    }
		    $iname  	= $indiv->getName(Person::NAME_INCLUDE_DATES);
		    $parents	= $indiv->getParents();
		    $comma	    = ' ';
		    foreach($parents as $idcr => $set)
		    {	    // loop through parents
				$father	= $set->getHusbName();
				$mother	= $set->getWifeName();
				$iname	.= "$comma$childrole of $father and $mother";
				$comma	= ', ';
            }	    // loop through parents

            $indiv->set('sexclass',     $sexclass);
            $indiv->set('iname',        $iname);
        }	        // loop through matches

        $template->updateTag('match$idir',  $imatches);
    }       	    // matched to some individuals in database
    else
    {
        $template->updateTag('LinkRow',  null);
    }
}                   // no existing link to family tree
else
{                   // did not search for matches because already had link
    $template->updateTag('LinkRow',  null);
}                   // did not search for matches because already had link

if (!$update && $formerHusband == "")
    $template->updateTag('FormerHusbandRow',  null);
if (!$update && $remarks == "")
    $template->updateTag('RemarksRow',  null);

// handle Image field
if (!$update && $image == "")
    $template->updateTag('ImageRow',  null);

// display template
$template->display();
