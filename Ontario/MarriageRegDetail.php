<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegDetail.php												*
 *																		*
 *  Display the details of a specific Ontaro marriage registration.		*
 *																		*
 *  Parameters:															*
 *		RegYear			year the marriage was registered				*
 *		RegNum			registration number within year					*
 *    or ...															*
 *		RegYear			year the marriage was registered required		*
 *						from 1873 onward								*
 *		OriginalVolume	hardcopy volume number							*
 *		OriginalPage	page number in hardcopy volume					*
 *		OriginalItem	ordinal position on page						*
 *																		*
 *  History:															*
 *		2010/09/02		New format										*
 *		2011/03/14		improve separation of PHP & HTML				*
 *						use <button>									*
 *						use MDB2										*
 *						translate all numeric dates to text				*
 *		2011/03/27		display error messages in body of page			*
 *						get defaults from previous record even if		*
 *						it is not immediately preceding record			*
 *		2011/03/29		expand some text fields							*
 *		2011/04/04		use style class for form table to reduce padding*
 *		2011/04/06		display witnesses after Bride and before		*
 *						Minister										*
 *		2011/04/07		shrink display by using smaller font for labels	*
 *						and separate line for roles.					*
 *		2011/04/09		phase out deprecated doQuery method				*
 *						move remarks to bottom							*
 *						do not inherit remarks from previous record		*
 *						improve separation of PHP & HTML				*
 *						use selection list for License type				*
 *		2011/06/16		put more information into mailto				*
 *		2011/08/09		change name of supporting javascript file		*
 *		2011/08/10		put links to project status in breadcrumbs		*
 *		2011/09/04		use real buttons for next, previous, and		*
 *						new query										*
 *						add help balloons for read-only fields			*
 *						add image URL field								*
 *		2011/09/09		make text fields for Minister same length as	*
 *						for bride and groom.							*
 *		2011/09/21		make registration year and number read-only		*
 *		2011/10/01		do not capitalize age fields					*
 *						expand given names in witness and parent name	*
 *						fields											*
 *		2012/02/13		expand county abbreviations						*
 *		2012/04/08		change initial defaults for date and place		*
 *						of marriage when creating a new record.			*
 *		2012/04/22		correct reference to $county on line 201		*
 *		2012/11/02		provide button to view image when authorized	*
 *						to edit											*
 *		2013/02/05		add help for birth year display					*
 *		2013/02/26		add support for selection list of matches to	*
 *						bride and groom									*
 *		2013/03/15		fill in full county name in default location	*
 *						fields											*
 *						more efficiently obtain records from database	*
 *		2013/05/12		add 1870 to years with special sequence numbers	*
 *		2013/06/27		use tinyMCE for editing remarks					*
 *						display remarks as text to visitors				*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/10/20		correct syntax error in HTML					*
 *		2013/11/15		handle missing database connection gracefully	*
 *		2013/11/25		clean up parameter handling						*
 *						add RegDomain parameter							*
 *						function getCountyName moved to County.inc		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/10		migrate to CSS from tables for layout			*
 *						witness names are 64 characters long			*
 *						enclose groom, bride, witnesses, and minister	*
 *						input fields in styled divisions				*
 *		2014/01/18		use CSS to specify lengths of input fields		*
 *		2014/01/21		use class Marriage and class MarriageParticipant*
 *						use <fieldset> to organize portions of dialog	*
 *		2014/02/03		for minister put residence and occupation on	*
 *						one line to save vertical space					*
 *						expand width of image URL input field			*
 *		2014/02/10		include registration domain in breadcrumbs		*
 *						correct header 1 text to include domain name	*
 *		2014/03/05		display minister if not editting				*
 *						highlight lack of image warning					*
 *						default date of registration to year			*
 *		2014/06/14		show values of date and place from existing		*
 *						citation										*
 *						correctly display estimated age of bride/groom	*
 *						use Citation::getCitations to get				*
 *						existing citation								*
 *		2014/10/11		pass registration domain to update script		*
 *						validate and interpret domain code against		*
 *						common table									*
 *		2014/12/26		debugging information in $warn					*
 *		2015/01/26		invalid age set for participant with undefined	*
 *						birth date										*
 *		2015/04/08		RegDomain field not defined for read-only		*
 *		2015/05/01		if possible display the image by splitting		*
 *						the window										*
 *						support new parameter ShowImage					*
 *		2016/06/10		do not escape contents of textarea				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/10		include domain name in previous and next urls	*
 *		2015/11/07		text edit widget mispositioned					*
 *		2016/01/14		add help for clear association buttons			*
 *						debug parameter was not passed to update		*
 *		2016/03/30		add header and footer links to status reports	*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/02/17		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *		2017/03/13		support creating record given only				*
 *						OriginalVolume, OriginalPage, and OriginalItem	*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new Family			*
 *		2017/07/12		use locationCommon to expand location names		*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/02/24		correctly initialize originalvolume,			*
 *						originalpage, and originalitem from regnum		*
 *		2018/03/17		display registration year and number in col 1	*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		                do not reject lang parameter                    *
 *		2019/01/24      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  searchpParticipant													*
 *																		*
 *  Handle the IDIR or IDIR lookup for a participant.                   *
 *																		*
 *  Input:																*
 *		$participant				MarriageParticipant record			*
 ************************************************************************/
function searchParticipant($participant)
{
    global  $template;
    global  $debug;
    global  $warn;

    // if this participant is not already linked to
    // look for individuals who match
    $regYear			= $participant->get('m_regyear');
    $role				= $participant->get('m_role');
    $givenNames			= $participant->get('m_givennames');
    $surname			= $participant->get('m_surname');
    $age				= $participant->get('m_age');
    $birthYear			= $participant->get('m_byear');
    $idir				= $participant->get('m_idir');
    $residence			= $participant->get('m_residence');
    $birthPlace			= $participant->get('m_birthplace');
    $occupation			= $participant->get('m_occupation');
    $marStat			= $participant->get('m_marstat');
    $religion			= $participant->get('m_religion');
    $fatherName			= $participant->get('m_fathername');
    $motherName			= $participant->get('m_mothername');

    // assume minister is middle aged (between 25 and 69)
    if ($role == 'M' && ($age == '' || $age == 0))
    {
        $age	= 47;
        $birthYear	= $regYear - 47;
    }

    // cover the case where the birth year fields is not initialized
    if ($birthYear === null || $birthYear == 0 ||
        $age === null || $age === 0)
    {		// birth year not present in transcription
        if (is_int($age) || (strlen($age) > 0 && ctype_digit($age)))
            $birthYear	= $regYear - $age;
        else
            $birthYear	= $regYear - 20;
    }		// birth year not present in database

    // check for matching individuals in the family tree
    $chooseRow          = $template[$role . 'ChooseRow'];
    if ($chooseRow)
    {                       // updating
	    if (strlen($surname) > 0 && strlen($givenNames) > 0 &&
	        ($idir == 0 || $idir === null))
	    {		            // no existing citation
	        // search for a match on any of the parts of the
	        // given name
	        $gnameList	= explode(' ', $givenNames);
	
	        $getParms	= array('loose'		    => true,
	                            'incmarried'	=> true,
	                            'surname'	    => $surname,
	                            'givenname'	    => $gnameList);
	
	        // selection based on gender of partner
	        if ($role == 'G')
	        {
	            $getParms['gender']	= 0;
	            $birthDelta		    = 2;
	        }
	        else
	        if ($role == 'B')
	        {
	            $getParms['gender']	= 1;
	            $birthDelta		    = 2;
	        }
	        else
	        {
	            $birthDelta		    = 27;
	        }
	
	        // look on either side of the birth year
	        $birthrange	= array(($birthYear - $birthDelta) * 10000,
	                            ($birthYear + $birthDelta) * 10000);
	        $getParms['birthsd']	= $birthrange;
	        $getParms['order']		= "tblNX.Surname, tblNX.GivenName, tblIR.BirthSD";
	
	        $fatherName	    = trim($fatherName);
	        if (strlen($fatherName) > 2)
	        {		        // possibly include father's surname in check
	            $spacecol	= strrpos($fatherName, ' ');
	            if (is_int($spacecol))
	                $fatherSurname	= substr($fatherName, $spacecol + 1);
	            else
	                $fatherSurname	= $fatherName;
	            if (is_string($fatherSurname) && $fatherSurname != $surname)
	                $getParms['surname']	= array($surname,
	                                        		$fatherSurname, null);
	        }		        // possibly include father's surname in check
	
	        // use the alternate name table so the search includes married
	        // names, but include information from the main record
	        if ($role == 'M')
	            $getParms['occupation']	= array('minister',
	                        	                'priest',
	                        	                'clergyman');
	
	        if ($debug)
	            $warn	    .= "<p>\$getParms=" . print_r($getParms, true) .
	                           "</p<\n";
	        $imatches	    = new PersonSet($getParms);
	        $options        = $template[$role . 'Option$iidir'];
	        if (is_null($options))
	        {
	            $warn   .= "<p>263 cannot find '{$role}Option\$iidir'</p>\n";
                $warn   .= "<p>264 chooseRow=" . $chooseRow->show() . "</p>\n";
	        }
	        else
		    if ($imatches && $imatches->count() > 0)
	        {	            // matched to some individuals in family tree
                $searchList     = array();
                $childof        = $template['childof']->innerHTML();
		        foreach($imatches as $iidir => $person)
		        {	        // loop through results
                    $iname          = $person->getName(Person::NAME_INCLUDE_DATES);
                    $parents        = $person->getPreferredParents();
                    if ($parents)
                        $iname      .= $childof . $parents->getName();
		            $searchList[]   = array('iidir'     => $iidir,
		                                    'iname'     => $iname,
		                                    'sexclass'  => $person->getGenderClass());
	            }	        // loop through results
	            $options->update($searchList);
		    }	            // matched to some individuals in family tree
		    else
	        {               // no matches
	            $chooseRow->update(null);
		    }               // no matches
	    }		            // no existing citation
		else
	        $chooseRow->update(null);
    }                       // updating
}		// function searchParticipant

/************************************************************************
 *		Open Code														*
 ************************************************************************/
// action depends upon whether the user is authorized to
// update the database
if(canUser('edit'))
{
    $action		    = "Update";
}
else
{
    $action		    = "Display";
}

// validate parameters
$regYear			= '';
$regNum		        = '';
$paddedRegNum       = '0000000';
$volume	            = '';
$page	            = '';
$item	            = '';
$cc	                = 'CA';	        // default country code
$countryName		= 'Canada';	    // default country name
$domain	            = 'CAON';	    // default domain
$domainName			= 'Ontario';	// default domain name
$countyName			= '';
$regCounty			= '';
$regTownship		= '';
$lang		        = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {		// process specific named parameters
        case 'regyear':
        {
            $regYear	    = $value;
            break;
        }		// RegYear passed

        case 'regnum':
        {
            $regNum	        = $value;
            break;
        }		// RegNum passed

        case 'originalvolume':
        {
            $volume		    = $value;
            $numVolume	    = preg_replace('/[^0-9]/', '', $value);
            break;
        }		// RegNum passed

        case 'originalpage':
        {
            if (ctype_digit($value))
                $page	    = $value;
            else
                $msg	    .= "Page Number $value must be a number. ";
            break;
        }		// RegNum passed

        case 'originalitem':
        {
            if (ctype_digit($value))
                $item	    = $value;
            else
                $msg	    .= "Item Number $value must be a number. ";
            break;
        }		// RegNum passed

        case 'regdomain':
        case 'domain':
        {
            $domain	        = $value;
            break;
        }		// RegDomain

        case 'showimage':
        {
            break;
        }		// handled by JavaScript

        case 'lang':
        {		// preferred language
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value, 0, 2));
            break;
        }		// preferred language


        case 'debug':
        {		// handled by common code
            break;
        }		// debug

        default:
        {
            $warn	.= "<p>Unexpected parameter $key='$value'.</p>\n";
            break;
        }		// any other paramters
    }		    // process specific named parameters
}			    // loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

// create Template
$template		= new FtTemplate("MarriageRegDetail$action$lang.html");
$trtemplate     = $template->getTranslate();

// internationalization support
$monthsTag		    = $trtemplate->getElementById('Months');
if ($monthsTag)
{
    $months	        = array();
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

// issue error messages
if ($regYear == '' && $volume == '')
{
    $msg		    .= "RegYear omitted. ";
}
else
if (!ctype_digit($regYear) ||
    ($regYear < 1800) || ($regYear > 2018))
    $msg	        .= "Registration Year '$regYear' must be a number between 1800 and 2018. ";

if ($regNum == '' && $volume == '')
{
    $msg		    .= "RegNum omitted. ";
}
else
if ($volume != '' && $page != '' && $item != '')
{
    $regNum	        = $numVolume .
                        str_pad($page, 3, '0', STR_PAD_LEFT) .
                      $item;
    $paddedRegNum   = $regNum;
}
else
if (ctype_digit($regNum))
    $paddedRegNum	= str_pad($regNum,7,"0",STR_PAD_LEFT);
else
    $msg	        .= "Registration Number $regNum must be a number. ";

// the number of the immediately preceding and following registrations
if (!isset($volume) || ($volume == '' && $regNum > 9999))
    $volume	        = floor($regNum/10000);
if ($regYear <= 1872 && isset($volume)) 
{
    if (!isset($page) || $page == '')
    {
        $page	    = (int)(($regNum % 10000)/10);
        $item	    = $regNum % 10;
    }
    if ($regNum % 10 == 1)
    {
        $prevNum	= $regNum - 8;
        $nextNum	= $regNum + 1;
    }
    else
    if ($regNum % 10 == 3)
    {
        $prevNum	= $regNum - 1;
        $nextNum	= $regNum + 8;
    }
    else
    {
        $prevNum	= $regNum - 1;
        $nextNum	= $regNum + 1;
    }
}
else
{		// sequentially numbered
    $prevNum	    = $regNum - 1;
    $nextNum	    = $regNum + 1;
}		// sequentially numbered

// get information about the administrative domain
$domainObj	        = new Domain(array('domain'	    => $domain,
                                       'language'	=> 'en'));
if ($domainObj->isExisting())
{
    $cc			    = substr($domain, 0, 2);
    $countryObj		= new Country(array('code' => $cc));
    $countryName	= $countryObj->getName();
    $domainName		= $domainObj->get('name');
}
else
{
    $msg	        .= "Domain '$domain' is not a supported " .
                        "two character country code followed by ".
                        "a state or province code. ";
    $domainName	= 'Domain : ' . $domain;
}

// set the global field values in the template
$template->set('CONTACTTABLE',	'Marriages');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DOMAIN',		$domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('CC',		    $cc);
$template->set('COUNTRYNAME',	$countryName);
$template->set('LANG',		    $lang);
$template['otherStylesheets']->update(
                        array('filename'	=> '/Ontario/MarriageRegDetail'));
$template->set('REGYEAR',		$regYear);
$template->set('REGNUM',		$regNum);
$template->set('PREVNUM',		$prevNum);
$template->set('NEXTNUM',		$nextNum);
$template->set('PADDEDREGNUM',	$paddedRegNum);

// if no error messages Issue the query
if (strlen($msg) == 0)
{		// no error messages
    // execute the query
    if ($volume != '' && $page != '' && $item != '')
    {
        $marrParms	        = array('domain'		    => $domain,
                                    'originalvolume'	=> $volume,
                                    'originalpage'		=> $page,
                                    'originalitem'		=> $item);
        if ($regYear >= 1873)
            $marrParms['regyear']	= $regYear;
        $marriage           = new Marriage($marrParms);
        $regYear            = $marriage->get('regyear');
        $regNum	            = $marriage->get('regnum');
    }
    else
    {
        $marriage	= new Marriage($domain,
                                   $regYear,
                                   $regNum);
    }

    // extract field values for display
    $msVol					= $marriage->get('m_msvol');
    $regCounty				= $marriage->get('m_regcounty');
    $countyObj				= new County($domain, $regCounty);
    $countyName				= $countyObj->get('name');
    $regTownship			= $marriage->get('m_regtownship');
    $mDate					= $marriage->get('m_date');
    $mPlace					= $marriage->get('m_place');
    $licenseType			= $marriage->get('m_licensetype');
    $regDate				= $marriage->get('m_regdate');
    if (is_null($regDate) || $regDate == '')
        $regDate			= $regYear;
    $registrar				= $marriage->get('m_registrar');
    $remarks				= $marriage->get('m_remarks');
    $image					= $marriage->get('m_image');
    $originalVolume			= $marriage->get('m_originalvolume');
    $originalPage			= $marriage->get('m_originalpage');
    $originalItem			= $marriage->get('m_originalitem');

    // reformat all numeric date if necessary
    $mdyPattern		= '/^([0-9]{2,2})\/([0-9]{2,2})\/([0-9]{2,2})$/';
    if(preg_match($mdyPattern,
                  $mDate,
                  $matches) == 1)
    {		// mm/dd/yy
        if ($matches[1] <= 12)
        {	// mm/dd/yy
            $month			= $monthNames[$matches[1]];
            $day			= $matches[2];
        }	// mm/dd/yy
        else
        if ($matches[2] <= 12)
        {	// dd/mm/yy
            $month			= $monthNames[$matches[2]];
            $day			= $matches[1];
        }	// dd/mm/yy
        else
        {	// illegal format
            $month			= $matches[2] . '?';
            $day			= $matches[1];
        }	// illegal format
        $year				= floor($regYear / 100) * 100 + $matches[3];
        if ($year > $regYear)
            $year			= $year - 100;
        $mDate				= $day . ' ' . $month . ' ' . $year;
    }		// mm/dd/yy or dd/mm/yy

    // get associated individual records
    $groom					= $marriage->getGroom(true);
    $bride					= $marriage->getBride(true);
    $minister				= $marriage->getMinister(true);

    // check for existing citations to this registration
    $citparms				= array('idsr'		=> 99,
                                    'type'		=> Citation::STYPE_MAR,
                                    'srcdetail'	=> "^$regYear-0*$regNum"); 
    $citations				= new CitationSet($citparms);
    if ($citations->count() > 0)
    {		// existing citation
        $citrow				= $citations->rewind();
        $idmr				= $citrow->get('idime');
        $family			    = new Family(array('idmr' => $idmr));

        if ($mDate == '' || $mDate == $regYear)
        {
            $marDate        = new LegacyDate($family->get('mard'));
            $mDate		    = $marDate->toString();
        }

        if ($mPlace == '')
        {		// location not supplied
            try {
                $idlr	    = $family->get('idlrmar');
                $mLocation	= new Location(array('idlr' => $idlr));
                $mPlace		= $mLocation->getName();
            } catch (Exception $e) {}
        }		// location not supplied

        $idirhusb	        = $family->get('idirhusb');
        $idirwife	        = $family->get('idirwife');

        // update information on groom based upon marriage
        // registration
        if ($groom->get('m_surname') == '')
        {	// create new groom
            $person	        = new Person(array('idir' => $idirhusb));

            $groom->set('m_surname',
                         $person->get('surname'));
            $groom->set('m_givennames',
                         $person->get('givenname'));
            $byear	        = floor($person->get('birthsd')/10000);
            if ($byear <= -9999)
                $groom->set('m_age',
                            20);
            else
                $groom->set('m_age',
                            $regYear - $byear);
            $groom->set('m_idir',
                         $idirhusb);
        }	// create new groom
        else
        if ($groom->get('m_idir') == 0)
            $groom->set('m_idir', $idirhusb);

        // update information on bride based upon marriage
        // registration
        if ($bride->get('m_surname') == '')
        {	// create new temporary bride
            $person	        = new Person(array('idir' => $idirwife));

            $bride->set('m_surname',
                        $person->get('surname'));
            $bride->set('m_givennames',
                        $person->get('givenname'));
            $byear	        = floor($person->get('birthsd')/10000);
            if ($byear <= -9999)
                $bride->set('m_age',
                             20);
            else
                $bride->set('m_age',
                             $regYear - $byear);
            $bride->set('m_idir',
                         $idirwife);
        }	// create new temporary bride
        else
        if ($bride->get('m_idir') == 0)
            $bride->set('m_idir', $idirwife);

    }		// existing citation

    $mPlace		            = str_replace("'","&#39;",$mPlace);

    $template->set('MSVOL',	        $msVol);
    $template->set('REGCOUNTY',	    $regCounty);
    $template->set('COUNTYOBJ',	    $countyObj);
    $template->set('COUNTYNAME',	$countyName);
    $template->set('REGTOWNSHIP',	$regTownship);
    $template->set('MDATE',	        $mDate);
    $template->set('MPLACE',	    $mPlace);
    $template->set('LICENSETYPE',	$licenseType);
    $template->set('REGDATE',	    $regDate);
    $template->set('REGISTRAR',	    $registrar);
    $template->set('REMARKS',	    $remarks);
    $template->set('IMAGE',	        $image);
    if (strlen($image) < 4 || substr($image, 0, 4) != 'http')
    {
        $template->set('IMAGEDISABLED',	        'disabled="disabled"');
        if ($action != 'Update')
            $template['ShowImage']->update(null);
    }
    else
    {
        $template->set('IMAGEDISABLED',	        '');
        $notAvail   = $template['ImageNotAvailable'];
        if ($notAvail)
            $notAvail->update(null);
    }

    $template->set('ORIGINALVOLUME',$originalVolume);
    $template->set('ORIGINALPAGE',	$originalPage);
    $template->set('ORIGINALITEM',	$originalItem);

    $template->set('GROOMROLE',		        $groom['role']);
    $template->set('GROOMSURNAME',		    $groom['surname']);
    $template->set('GROOMSURNAMESOUNDEX',	$groom['surnamesoundex']);
    $template->set('GROOMGIVENNAMES',		$groom['givennames']);
    $template->set('GROOMAGE',		        $groom['age']);
    $template->set('GROOMBIRTHYEAR',	    $groom['byear']);
    $template->set('GROOMRESIDENCE',		$groom['residence']);
    $template->set('GROOMBIRTHPLACE',		$groom['birthplace']);
    $template->set('GROOMMARSTAT',		    $groom['marstat']);
    $template->set('GROOMOCCUPATION',		$groom['occupation']);
    $template->set('GROOMFATHERNAME',		$groom['fathername']);
    $template->set('GROOMMOTHERNAME',		$groom['mothername']);
    $template->set('GROOMRELIGION',		    $groom['religion']);
    $template->set('WITNESSNAME1',		    $groom['witnessname']);
    $template->set('WITNESSRES1',		    $groom['witnessres']);
    $template->set('GROOMREMARKS',		    $groom['remarks']);
    $template->set('GROOMIDIR',		        $groom['idir']);
    if ($groom['idir'] == 0)
    {
        $template['GroomLinkRow']->update(null);
        $template->set('GROOMNAME',		    $groom['givennames'] . ' ' . $groom['surname']);
        searchParticipant($groom);
    }
    else
    {
        $chooseRow          = $template['GChooseRow'];
        if ($chooseRow)
            $chooseRow->update(null);
        $nameObj            = new Name(array('idir'     => $groom['idir'],
                                             'order'    => 0));
        $name               = $nameObj->getName(Person::NAME_INCLUDE_DATES);
        $template->set('GROOMNAME',         $name);
    }

    $template->set('BRIDEROLE',		        $bride['role']);
    $template->set('BRIDESURNAME',		    $bride['surname']);
    $template->set('BRIDESURNAMESOUNDEX',	$bride['surnamesoundex']);
    $template->set('BRIDEGIVENNAMES',		$bride['givennames']);
    if ($bride['idir'] > 0)
    {
        $nameObj            = new Name(array('idir'     => $bride['idir'],
                                             'order'    => 0));
        $name               = $nameObj->getName(Person::NAME_INCLUDE_DATES);
        $template->set('BRIDENAME',         $name);
    }
    else
        $template->set('BRIDENAME',	        $bride['givennames'] . ' ' . $bride['surname']);
    $template->set('BRIDEAGE',		        $bride['age']);
    $template->set('BRIDEBIRTHYEAR',	    $bride['byear']);
    $template->set('BRIDERESIDENCE',		$bride['residence']);
    $template->set('BRIDEBIRTHPLACE',		$bride['birthplace']);
    $template->set('BRIDEMARSTAT',		    $bride['marstat']);
    $template->set('BRIDEOCCUPATION',		$bride['occupation']);
    $template->set('BRIDEFATHERNAME',		$bride['fathername']);
    $template->set('BRIDEMOTHERNAME',		$bride['mothername']);
    $template->set('BRIDERELIGION',		    $bride['religion']);
    $template->set('WITNESSNAME2',	        $bride['witnessname']);
    $template->set('WITNESSRES2',	        $bride['witnessres']);
    $template->set('BRIDEREMARKS',		    $bride['remarks']);
    $template->set('BRIDEIDIR',		        $bride['idir']);
    if ($bride['idir'] == 0)
    {
        $template['BrideLinkRow']->update(null);
        $template->set('BRIDENAME',		    $bride['givennames'] . ' ' . $bride['surname']);
        searchParticipant($bride);
    }
    else
    {
        $chooseRow          = $template['BChooseRow'];
        if ($chooseRow)
            $chooseRow->update(null);
        $nameObj            = new Name(array('idir'     => $bride['idir'],
                                             'order'    => 0));
        $name               = $nameObj->getName(Person::NAME_INCLUDE_DATES);
        $template->set('BRIDENAME',         $name);
    }

    $template->set('MINISTERROLE',		    $minister['role']);
    $template->set('MINISTERSURNAME',		$minister['surname']);
    $template->set('MINISTERSURNAMESOUNDEX',$minister['surnamesoundex']);
    $template->set('MINISTERGIVENNAMES',	$minister['givennames']);
    if ($minister['idir'] > 0)
    {
        $nameObj            = new Name(array('idir'     => $minister['idir'],
                                             'order'    => 0));
        $name               = $nameObj->getName(Person::NAME_INCLUDE_DATES);
        $template->set('MINISTERNAME',      $name);
    }
    else
        $template->set('MINISTERNAME',	    $minister['givennames'] . ' ' . $minister['surname']);
    $template->set('MINISTERAGE',		    $minister['age']);
    $template->set('MINISTERBIRTHYEAR',	    $minister['byear']);
    $template->set('MINISTERRESIDENCE',		$minister['residence']);
    $template->set('MINISTERBIRTHPLACE',	$minister['birthplace']);
    $template->set('MINISTERMARSTAT',		$minister['marstat']);
    $template->set('MINISTEROCCUPATION',	$minister['occupation']);
    $template->set('MINISTERFATHERNAME',	$minister['fathername']);
    $template->set('MINISTERMOTHERNAME',	$minister['mothername']);
    $template->set('MINISTERRELIGION',		$minister['religion']);
    $template->set('MINISTERWITNESSNAME',	$minister['witnessname']);
    $template->set('MINISTERWITNESSRES',	$minister['witnessres']);
    $template->set('MINISTERREMARKS',		$minister['remarks']);
    $template->set('MINISTERIDIR',		    $minister['idir']);
    if ($minister['idir'] == 0)
    {
        $template['MinisterLinkRow']->update(null);
        searchParticipant($minister);
    }
    else
    {
        $chooseRow          = $template['MChooseRow'];
        if ($chooseRow)
            $chooseRow->update(null);
        $nameObj            = new Name(array('idir'     => $minister['idir'],
                                             'order'    => 0));
        $name               = $nameObj->getName(Person::NAME_INCLUDE_DATES);
        $template->set('MINISTERNAME',         $name);
    }
}			// no error messages
else
    $template['distForm']->update(null);

$template->display();
