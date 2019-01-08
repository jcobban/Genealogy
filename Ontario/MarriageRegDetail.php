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
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . "/common.inc";
require_once 'MarriageRegDetailLib.php';

/************************************************************************
 *  $monthNames															*
 *																		*
 *		Table to translate numeric months to month names				*
 ************************************************************************/
    $monthNames	= array(	'01'	=> 'Jan',
    						'02'	=> 'Feb',
    						'03'	=> 'Mar',
    						'04'	=> 'Apr',
    						'05'	=> 'May',
    						'06'	=> 'Jun',
    						'07'	=> 'Jul',
    						'08'	=> 'Aug',
    						'09'	=> 'Sep',
    						'10'	=> 'Oct',
    						'11'	=> 'Nov',
    						'12'	=> 'Dec');

/************************************************************************
 *  function right														*
 *																		*
 *  Return the right most portion of a string								*
 *																		*
 *  Input:																*
 *		$string				string												*
 *		$len				length of string to return						*
 ************************************************************************/
function right($string, $len)
{
    return substr($string, strlen($string) - $len);
}		// function right

/************************************************************************
 *		Open Code														*
 ************************************************************************/
// action depends upon whether the user is authorized to
// update the database
if(canUser('edit'))
{
	$action		= "Update";
	$readonly	= "";
	$txtleftclass	= "white left";
	$txtleftclassnc	= "white leftnc";
	$txtrightclass	= "white rightnc";
}
else
{
	$action		= "Details";
	$readonly	= " readonly='readonly'";
	$txtleftclass	= "ina left";
	$txtleftclassnc	= "ina leftnc";
	$txtrightclass	= "ina rightnc";
}

// validate parameters
$regYear			= '';
$regNum		    	= '';
$volume	    		= '';
$page	    		= '';
$item	    		= '';
$domain	    		= 'CAON';	    // default domain
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
			if (ctype_digit($value) &&
			    ($value >= 1800) && ($value < 2018))
			    $regYear	= $value;
			else
			    $msg	.= "Registration Year $value must be a number between 1800 and 2018. ";
			break;
	    }		// RegYear passed

	    case 'regnum':
	    {
			if (ctype_digit($value))
			    $regNum	= $value;
			else
			    $msg	.= "Registration Number $value must be a number. ";
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
			    $page	= $value;
			else
			    $msg	.= "Page Number $value must be a number. ";
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
	    case 'domain':
	    {
			$domainObj	= new Domain(array('domain'	=> $value,
								   'language'	=> 'en'));
			if ($domainObj->isExisting())
			{
			    $domain	= $value;
			    $domainName	= $domainObj->get('name');
			}
			else
			{
			    $msg	.= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
			    $domainName	= 'Domain : ' . $domain;
			}
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

if ($regYear == '' && $volume == '')
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
					  $item;
}
else
	$paddedRegNum	= str_pad($regNum,7,"0",STR_PAD_LEFT);

// the number of the immediately preceding and following registrations
if (!isset($volume) || ($volume == '' && $regNum > 9999))
    $volume	= floor($regNum/10000);
if ($regYear <= 1872 && isset($volume)) 
{
	if (!isset($page) || $page == '')
	{
	    $page	= (int)(($regNum % 10000)/10);
	    $item	= $regNum % 10;
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
	$prevNum	= $regNum - 1;
	$nextNum	= $regNum + 1;
}		// sequentially numbered

// if no error messages Issue the query
if (strlen($msg) == 0)
{		// no error messages
	// execute the query
	if ($volume != '' && $page != '' && $item != '')
	{
	    $marrParms	= array('domain'		=> $domain,
						'originalvolume'	=> $volume,
						'originalpage'		=> $page,
						'originalitem'		=> $item);
	    if ($regYear >= 1873)
			$marrParms['regyear']	= $regYear;
	    $marriage	= new Marriage($marrParms);
	    $regYear	= $marriage->get('regyear');
	    $regNum	= $marriage->get('regnum');
	}
	else
	{
	    $marriage	= new Marriage($domain,
						       $regYear,
						       $regNum);
	}

	// extract field values for display
	$msVol		= $marriage->get('m_msvol');
	$regCounty	= $marriage->get('m_regcounty');
	$countyObj	= new County($domain, $regCounty);
	$countyName	= $countyObj->get('name');
	$regTownship	= $marriage->get('m_regtownship');
	$mDate		= $marriage->get('m_date');
	$mPlace		= $marriage->get('m_place');
	$licenseType	= $marriage->get('m_licensetype');
	$regDate	= $marriage->get('m_regdate');
	if (is_null($regDate) || $regDate == '')
	    $regDate	= $regYear;
	$registrar	= $marriage->get('m_registrar');
	$remarks	= $marriage->get('m_remarks');
	$image		= $marriage->get('m_image');
	$originalVolume	= $marriage->get('m_originalvolume');
	$originalPage	= $marriage->get('m_originalpage');
	$originalItem	= $marriage->get('m_originalitem');

	// reformat all numeric date if necessary
	$mdyPattern	= '/^([0-9]{2,2})\/([0-9]{2,2})\/([0-9]{2,2})$/';
	if(preg_match($mdyPattern,
			      $mDate,
			      $matches) == 1)
	{		// mm/dd/yy
	    if ($matches[1] <= 12)
	    {	// mm/dd/yy
			$month	= $monthNames[$matches[1]];
			$day	= $matches[2];
	    }	// mm/dd/yy
	    else
	    if ($matches[2] <= 12)
	    {	// dd/mm/yy
			$month	= $monthNames[$matches[2]];
			$day	= $matches[1];
	    }	// dd/mm/yy
	    else
	    {	// illegal format
			$month	= $matches[2] . '?';
			$day	= $matches[1];
	    }	// illegal format
	    $year	= floor($regYear / 100) * 100 + $matches[3];
	    if ($year > $regYear)
			$year	= $year - 100;
	    $mDate	= $day . ' ' . $month . ' ' . $year;
	}		// mm/dd/yy or dd/mm/yy

	// get associated individual records
	$groom		= $marriage->getGroom(true);
	$bride		= $marriage->getBride(true);
	$minister	= $marriage->getMinister(canUser('edit'));

	// check for existing citations to this registration
	$citparms	= array('idsr'		=> 99,
						'type'		=> Citation::STYPE_MAR,
						'srcdetail'	=> "^$regYear-0*$regNum"); 
	$citations	= new CitationSet($citparms);
	if ($citations->count() > 0)
	{		// existing citation
	    $citrow	= $citations->rewind();
	    $idmr	= $citrow->get('idime');
	    try {
			$family		= new Family(array('idmr' => $idmr));

			if ($mDate == '' || $mDate == $regYear)
			{
			    $marDate	= new LegacyDate($family->get('mard'));
			    $mDate	= $marDate->toString();
			}

			if ($mPlace == '')
			{		// location not supplied
			    try {
					$idlr		= $family->get('idlrmar');
					$mLocation	= new Location(array('idlr' => $idlr));
					$mPlace		= $mLocation->getName();
			    } catch (Exception $e) {}
			}		// location not supplied

			$idirhusb	= $family->get('idirhusb');
			$idirwife	= $family->get('idirwife');

			// update information on groom based upon marriage
			// registration
			if ($groom->get('m_surname') == '')
			{	// create new groom
			    try {
			    $person	= new Person(array('idir' => $idirhusb));

			    $groom->set('m_surname',
						     $person->get('surname'));
			    $groom->set('m_givennames',
						     $person->get('givenname'));
			    $byear	= floor($person->get('birthsd')/10000);
			    if ($byear <= -9999)
					$groom->set('m_age',
							 20);
			    else
					$groom->set('m_age',
							 $regYear - $byear);
			    $groom->set('m_idir',
						     $idirhusb);
			    } catch (Exception $e) {
					$msg	.= "IDIR=$idirhusb, Exception:" .
						   $e->getMessage();
			    }
			}	// create new groom
			else
			if ($groom->get('m_idir') == 0)
			    $groom->set('m_idir', $idirhusb);

			// update information on bride based upon marriage
			// registration
			if ($bride->get('m_surname') == '')
			{	// create new bride
			    try {
			    $person	= new Person(array('idir' => $idirwife));

			    $bride->set('m_surname',
						     $person->get('surname'));
			    $bride->set('m_givennames',
						     $person->get('givenname'));
			    $byear	= floor($person->get('birthsd')/10000);
			    if ($byear <= -9999)
					$bride->set('m_age',
							 20);
			    else
					$bride->set('m_age',
							 $regYear - $byear);
			    $bride->set('m_idir',
						     $idirwife);
			    } catch (Exception $e) {
					$msg	.= "IDIR=$idirwife, Exception:" .
						   $e->getMessage();
			    }
			}	// create new bride
			else
			if ($bride->get('m_idir') == 0)
			    $bride->set('m_idir', $idirwife);

	    }		// try existing marriage registration
	    catch (Exception $e)
	    {		// do not report bad key errors
			$warn	.= "IDMR=$idmr, Exception:" . $e->getMessage();
	    }		// do not report bad key errors
	}		// existing citation

	$mPlace		= str_replace("'","&#39;",$mPlace);
}			// no error messages

$subject	= $domainName . ' Marriage Registration: number: ' . 
			  $regYear . '-' . $regNum;
$subject	= rawurlencode($subject);

// emit standard HTML header customized to browser
htmlHeader($domainName . ': Marriage Registration ' . $action,
	       array(	'/jscripts/js20/http.js',
					'/jscripts/CommonForm.js',
					'/jscripts/Ontario.js',
					'/jscripts/util.js',
					'/jscripts/locationCommon.js',
					'/tinymce/jscripts/tiny_mce/tiny_mce.js',
			     	'MarriageRegDetail.js'),
			true);
?>
<body>
  <div id='transcription' style='overflow: auto; overflow-x: scroll'>
<?php
pageTop(array(
			'/genealogy.php'	=> 'Genealogy',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
			'/Ontario/MarriageRegQuery.html'
							=> 'New Marriage Query',
			"/Ontario/MarriageRegStats.php?regdomain=$domain"
							=> 'Status',
			"/Ontario/MarriageRegYearStats.php?regdomain=$domain&regyear=$regYear"
							=> "Status $regYear",
			"MarriageRegYearStats.php?regdomain=$domain&regyear=$regYear&county=$regCounty"
							=> "Status $regYear $countyName",
			"MarriageRegResponse.php?RegDomain=$domain&Offset=0&Count=20&RegYear=$regYear&RegCounty=$regCounty&RegTownship=$regTownship"
							=> "Status $regYear $countyName $regTownship"));
?>
 <div class='body'>
<h1><?php print $domainName; ?>: Marriage Registration
	<?php print $action; ?>
  <span class='right'>
	<a href='MarriageRegDetailHelpen.html' target='help'>? Help</a>
  </span>
  <div style='clear: both;'></div>
</h1>
<?php

showTrace();
 
if (strlen($msg) > 0)
{		// display error messages
?>
  <p class='message'>
<?php print $msg; ?> 
  </p>
<?php
}		// display error messages
else
{		// no errors
?>
  <form action='MarriageRegUpdate.php'
	name='distForm' id='distForm'
	method='POST'>
<div id='hidden'>
  <!-- the following 3 elements store the URLs of the actions
	for the buttons: id='previous', id='next', and id='newQuery' -->
  <input type='hidden' name='previousHref' id='previousHref' disabled
	value='MarriageRegDetail.php?RegDomain=<?php print $domain; ?>&RegYear=<?php print $regYear; ?>&amp;RegNum=<?php print $prevNum; ?>'>
  <input type='hidden' name='nextHref' id='nextHref' disabled
	value='MarriageRegDetail.php?RegDomain=<?php print $domain; ?>&RegYear=<?php print $regYear; ?>&amp;RegNum=<?php print $nextNum; ?>' >
  <input type='hidden' name='newQueryHref' id='newQueryHref' disabled
	value='MarriageRegQuery.html'>
  <!-- the following stores the textual value of the county name in
// the web page so it can be used by the Javascript code 
// to initialize the RegCounty select tag -->
  <input type='hidden' name='RegCountyTxt' id='RegCountyTxt' disabled
			value='<?php print str_replace("'","&#39;",$regCounty); ?>'/>
  <!-- the following stores the textual value of the township name in
// the web page so it can be used by the Javascript code 
// to initialize the RegTownship select tag -->
  <input type='hidden' name='RegTownshipTxt' id='RegTownshipTxt' disabled 
	value='<?php print str_replace("'","&#39;",$regTownship); ?>'/>
  <!-- the following stores the textual value of the marriage type in
// the web page so it can be used by the Javascript code 
// to initialize the LicenseType select tag -->
  <input name='LicenseTypeTxt' id='LicenseTypeTxt' type='hidden' disabled
	value='<?php print $licenseType; ?>'/>
<?php
	if ($debug)
	{
?>
  <input name='Debug' id='Debug' type='hidden' value='Y'/>
<?php
	}
?>
</div> <!-- class='hidden' -->
  <p>
<button type='button' id='previous'><u>P</u>revious</button>
  &nbsp;
<button type='button' id='next'><u>N</u>ext</button>
  &nbsp;
<button type='button' id='newQuery'>New <u>Q</u>uery</button>
  </p>
  <fieldset class='other'>
<legend class='labelSmall'>Identification:</legend>
  <div class='row' >
<?php
// action depends upon whether the user is authorized to
// update the database
if(canUser('edit'))
{
	// display the record identification as two separate fields
	// the user is not permitted to modify these fields
	// as they are the record identifier
?>
	<div class='column1'>
	  <div style="float: left;">
	  <label class='labelSmall'>Year:</label>
  	  <input name='RegYear' id='RegYear' type='text' class='ina rightnc'
			maxlength='4' readonly='readonly'
			value='<?php print $regYear; ?>'/>
	  <input name='RegDomain' id='RegDomain' type='hidden'
			value='<?php print $domain; ?>'>
	  </div>
	  <div class='right'>
	    <label class='labelSmall'>Number:</label>
  	    <input name='RegNum' id='RegNum' type='text' class='ina rightnc'
			style='width: 5em' maxlength='6' readonly='readonly'
			value='<?php print $regNum; ?>'/>
	  </div>
	</div>
<?php
}	// authorized to update database
else
{	// not authorized to update database, combine the two fields
	$paddedRegNum	= str_pad($regNum,5,'0',STR_PAD_LEFT);
?>
	<div class='column1'>
	  <label class='labelSmall'>Identification:</label>
  	  <input name='RegId' id='RegId' type='text' class='ina right'
			readonly='readonly'
			style='width: ' maxlength='12'
			value='<?php print "$regYear-$paddedRegNum"; ?>'/>
	  <input name='RegDomain' id='RegDomain' type='hidden'
			value='<?php print $domain; ?>'>
	</div>
<?php
}	// not authorized to update database
?>
	<div class='column2'>
	  <label class='labelSmall'>MS 932 Reel:</label>
  	  <input name='MsVol' id='MsVol' type='text'
			class='<?php print $txtrightclass; ?>'
			size='4' maxlength='4' <?php print $readonly; ?>
			value='<?php print $msVol; ?>'/>
	</div>
	<div style='clear: both;'></div>
  </div>
<?php
if ($regYear <= 1872 || $regNum > 100000)
{
?>
  <div class='row' >
	<div class='column1'>
	  <label class='labelSmall' for='OriginalVolume'>Volume:</label>
  	  <input name='OriginalVolume' id='OriginalVolume'
			type='text' class='<?php print $txtrightclass; ?>'
			size='3' maxlength='3' <?php print $readonly; ?>
			value='<?php print $originalVolume; ?>'/>
	</div>
	<div class='column2'>
	  <label class='labelSmall' for='OriginalPage'>Page:</label>
  	  <input name='OriginalPage' id='OriginalPage'
			type='text' class='<?php print $txtrightclass; ?>'
			size='3' maxlength='3' <?php print $readonly; ?>
			value='<?php print $originalPage; ?>'/>
	</div>
	<div class='column2'>
	  <label class='labelSmall' for='OriginalItem'>Item:</label>
  	  <input name='OriginalItem' id='OriginalItem'
			type='text' class='<?php print $txtrightclass; ?>'
			size='3' maxlength='3' <?php print $readonly; ?>
			value='<?php print $originalItem; ?>'/>
	</div>
	<div style='clear: both;'></div>
  </div>
<?php
}
else
{
?>
  	  <input name='OriginalVolume' id='OriginalVolume'
			type='hidden'
			value='<?php print $originalVolume; ?>'/>
  	  <input name='OriginalPage' id='OriginalPage'
			type='hidden'
			value='<?php print $originalPage; ?>'/>
  	  <input name='OriginalItem' id='OriginalItem'
			type='hidden'
			value='<?php print $originalItem; ?>'/>
<?php
}
?>
  <div class='row'  id='RegRow'>
	<div class='column1'>
	  <label class='labelSmall'>County:</label>
	  <select name='RegCounty' id='RegCounty' size='1'
			class='<?php print $txtleftclass; ?>'>
	  </select>
	</div>
	<div class='column2'>
	  <label class='labelSmall'>Township:</label>
  	  <input type='text' name='RegTownship' id='RegTownship'
	    size='20' maxlength='64'
	    value='<?php print str_replace("'","&#39;",$regTownship); ?>'
	    class='<?php print $txtleftclassnc; ?>' <?php print $readonly; ?> />
	</div>
	<div style='clear: both;'></div>
  </div>
  </fieldset>
  <fieldset class='other'>
<legend class='labelSmall'>Marriage:</legend>
  <div class='row' >
	<div class='column1'>
	  <label class='labelSmall'>Date:</label>
  	  <input name='Date' id='Date'
			type='text' size='10' maxlength='16'
			value='<?php print str_replace("'","&#39;",$mDate); ?>'
	    class='<?php print $txtleftclassnc; ?>' <?php print $readonly; ?> />
	</div>
	<div class='column2'>
	  <label class='labelSmall'>Place:</label>
  	  <input type='text' name='Place' id='Place' size='20' maxlength='64'
	    value='<?php print $mPlace; ?>'
	    class='<?php print $txtleftclassnc; ?>' <?php print $readonly; ?> />
	</div>
	<div style='clear: both;'></div>
  </div>
  <div class='row' >
	<div class='column1'>
	  <label class='labelSmall'>Type:</label>
	    <select name='LicenseType' id='LicenseType'
			size='1' class='<?php print $txtleftclass; ?>'>
	      <option value='L'>License</option>
	      <option value='B'>Banns</option>
	    </select>
	</div>
	<div style='clear: both;'></div>
  </div>
  </fieldset>
<?php
	// display details for groom
	dispParticipant($groom);

	// display details for bride
	dispParticipant($bride);

?>
<?php
	// display information on witnesses
	$witnessName1	= str_replace("'","&#39;",$groom->get('m_witnessname'));
	$witnessRes1	= str_replace("'","&#39;",$groom->get('m_witnessres'));
	$witnessName2	= str_replace("'","&#39;",$bride->get('m_witnessname'));
	$witnessRes2	= str_replace("'","&#39;",$bride->get('m_witnessres'));
	if ((strlen($witnessName1) > 0) ||
	    (strlen($witnessName2) > 0) ||
	    (canUser('edit')))
	{		// include witness information in display
?>
  <fieldset class='other'>
	<legend class='labelSmall'>Witnesses:</legend>
  <div class='row' >
	<div class='column1'>
	  <label class='labelSmall'>Name:</label>
  	  <input name='Witness1' id='Witness1' type='text' size='22' maxlength='64'
			class='<?php print $txtleftclass; ?>'
			value='<?php print $witnessName1; ?>' 
			  <?php print $readonly; ?>/>
	</div>
	<div class='column2'>
	  <label class='labelSmall'>Residence:</label>
  	  <input name='Witness1Res' id='Witness1Res' type='text'
			size='22' maxlength='64'
			class='<?php print $txtleftclassnc; ?>'
			value='<?php print $witnessRes1; ?>' 
			  <?php print $readonly; ?>/>
	</div>
	<div style='clear: both;'></div>
  </div>
  <div class='row' >
	<div class='column1'>
	  <label class='labelSmall'>Name:</label>
  	  <input name='Witness2' id='Witness2' type='text' size='22' maxlength='64'
			class='<?php print $txtleftclass; ?>'
			value='<?php print $witnessName2; ?>' 
			  <?php print $readonly; ?>/>
	</div>
	<div class='column2'>
	  <label class='labelSmall'>Residence:</label>
  	  <input name='Witness2Res' id='Witness2Res' type='text'
			size='22' maxlength='64'
			class='<?php print $txtleftclass; ?>'
			value='<?php print $witnessRes2; ?>' 
			  <?php print $readonly; ?>/>
	</div>
	<div style='clear: both;'></div>
  </div>
  </fieldset>	<!-- class='other' -->
<?php
	}		// include witness row in display

	if (!is_null($minister))
	{		// minister record present
	    // only display the Minister information if it is present
	    $mSurname		= $minister->get('m_surname');
	    if ($mSurname != '' || canUser('edit'))
			dispParticipant($minister);
	}		// minister record present
	else
?>
  <fieldset class='other'>
<legend class='labelSmall'>Registration:</legend>
	<div class='row'>
	  <div class='column1'>
	    <label class='labelSmall' for='RegDate'>
	      Date:
	    </label>
	    <input name='RegDate' id='RegDate'
			type='text' size='16' maxlength='16'
			value='<?php print $regDate; ?>'
			class='<?php print $txtleftclass; ?>'
			  <?php print $readonly; ?>/>
	  </div>
	  <div class='column2'>
	    <label class='labelSmall' for='Registrar'>
	    Registrar:
	    </label>
	    <input name='Registrar' id='Registrar'
			type='text' size='24' maxlength='128'
			value='<?php print $registrar; ?>'
			class='<?php print $txtleftclass; ?>'
			  <?php print $readonly; ?>/>
	  </div>
	  <div style='clear: both;'></div>
	</div>
<?php
	if (canUser('edit'))
	{	// display remarks if present or editting
?>
  <div class='row' >
	  <label class='labelSmall'>Remarks:</label>
  	  <textarea name='Remarks' id='Remarks'
			type='text' cols='65' rows=4
			class='<?php print $txtleftclassnc; ?>' 
			<?php print $readonly; ?>
			>  <?php print $remarks; ?></textarea>
	<div style='clear: both;'></div>
  </div>
<?php
	}	// updating record
	else
	if (strlen($remarks) > 0)
	{	// Remarks present in DB
?>
<div class='row' >
	<div class='column1'>
	  <label class='labelSmall'>Remarks:</label>
	    <?php print $remarks; ?>
	</div>
	<div style='clear: both;'></div>
  </div>
<?php
	}	// Remarks present in DB


	// display or permit the modification of the image URL
?>
  <div class='row' >
	<div class='column2'>
	  <label class='labelSmall'>Image:</label>
<?php

	if(canUser('edit'))
	{		// authorized to update database
	    if (strlen($image) > 0)
	    {		// provide button
?>
	  <button type='button' id='ShowImage'>
	    Show <u>I</u>mage
	  </button>
<?php
	    }		// provide button
?>

	  <input name='Image' id='Image' type='text' size='80' maxlength='256'
			value='<?php print $image; ?>'
			class='<?php print $txtleftclass; ?>var'
			<?php print $readonly; ?> />
<?php
	}		// authorized to update database
	else
	if (strlen($image) > 0)
	{		// view only
?>
	  <button type='button' id='showImage'>
	    Show <u>I</u>mage
	  </button>
	  <input name='Image' id='Image' type='hidden' disabled
			value='<?php print $image; ?>' />
<?php
	}		// view only
	else
	{		// no image
?>
	<span class='warning'>Not available for this registration</span>
<?php
	}		// no image
?>
	</div>
	<div style='clear: both;'></div>
  </div>
</fieldset>
<?php

	if(canUser('edit'))
	{		// authorized to update database
	    // display submit and reset buttons
?>
  <p>
	  <button type='submit' id='Update'>
	      <u>U</u>pdate
	  </button>
	&nbsp;
	  <button type='button' id='Reset'>
	      <u>C</u>lear Form
	  </button>
  </p>
<?php
	}		// authorized to update database
?>
  </form>
<?php
}		// no error messages
?>
  </div> <!-- class='body' -->
<?php
pageBot();
?>
  </div> <!-- id='transcription' -->
<div class='hidden' id='templates'>

<?php
include $document_root . '/templates/LocationDialogs.html';
?>

</div> <!-- id='templates' -->
<!--  The remainder of the web page consists of divisions containing
context specific help.  These divisions are only displayed if the user
requests help by pressing F1.  Including this information here ensures
that the language of the help balloons matches the language of the
input form.
-->
  <div class='balloon' id='HelpRegTownship'>
The township where the marriage was registered.
  </div>
  <div class='balloon' id='HelpRegTownshipTxt'>
The township where the marriage was registered.
  </div>
  <div class='balloon' id='HelpDate'>
The date the marriage took place.
It is recommended that, for consistency, this be entered as day, month, and
year, with the month abbreviated to the first 3 characters.
  </div>
  <div class='balloon' id='HelpPlace'>
The place where the marriage took place.
  </div>
  <div class='balloon' id='HelpLicenseType'>
'L' if the marriage was by License, or 'B' if it was by Banns.
  </div>
  <div class='balloon' id='HelpGGivenNames'>
The given names of the groom.
  </div>
  <div class='balloon' id='HelpBGivenNames'>
The given names of the bride.
  </div>
  <div class='balloon' id='HelpMGivenNames'>
The given names of the minister.
  </div>
  <div class='balloon' id='HelpGSurname'>
The surname of the groom.
  </div>
  <div class='balloon' id='HelpBSurname'>
The surname of the bride.
  </div>
  <div class='balloon' id='HelpMSurname'>
The surname of the minister.
  </div>
  <div class='balloon' id='HelpGIDIR'>
This is a link to the groom's record in the family tree or 
a selection list of individuals from the family tree
who may match the groom.
  </div>
  <div class='balloon' id='HelpBIDIR'>
This is a link to the bride's record in the family tree or 
a selection list of individuals from the family tree
who may match the bride.
  </div>
  <div class='balloon' id='HelpMIDIR'>
This is a link to the minister's record in the family tree or 
a selection list of individuals from the family tree
who may match the minister.
  </div>
  <div class='balloon' id='HelpClearG'>
Click on this button to clear the current association between the
Groom in this marriage registration and a record in the family tree.
  </div>
  <div class='balloon' id='HelpClearB'>
Click on this button to clear the current association between the
Groom in this marriage registration and a record in the family tree.
  </div>
  <div class='balloon' id='HelpGResidence'>
The current residence of the groom.
  </div>
  <div class='balloon' id='HelpBResidence'>
The current residence of the bride.
  </div>
  <div class='balloon' id='HelpMResidence'>
The current residence of the minister.
  </div>
  <div class='balloon' id='HelpGAge'>
The age of the groom in years.
When you set this the approximate birth year of the groom is calculated
by subtracting the age from the registration year.
  </div>
  <div class='balloon' id='HelpBAge'>
The age of the bride in years.
When you set this the approximate birth year of the bride is calculated
by subtracting the age from the registration year.
  </div>
  <div class='balloon' id='HelpMAge'>
The age of the minister in years.
When you set this the approximate birth year of the minister is calculated
by subtracting the age from the registration year.
  </div>
  <div class='balloon' id='HelpGBirthYear'>
This read-only field displays the calculated birth year of the groom.
  </div>
  <div class='balloon' id='HelpBBirthYear'>
This read-only field displays the calculated birth year of the bride.
  </div>
  <div class='balloon' id='HelpMBirthYear'>
This read-only field displays the calculated birth year of the minister.
  </div>
  <div class='balloon' id='HelpGBirthPlace'>
The birth place of the groom.
  </div>
  <div class='balloon' id='HelpBBirthPlace'>
The birth place of the bride.
  </div>
  <div class='balloon' id='HelpMBirthPlace'>
The birth place of the minister.
  </div>
  <div class='balloon' id='HelpGOccupation'>
The occupation of the groom.
  </div>
  <div class='balloon' id='HelpBOccupation'>
The occupation of the bride.
  </div>
  <div class='balloon' id='HelpMOccupation'>
The occupation of the minister.
  </div>
  <div class='balloon' id='HelpGMarStat'>
The marital status of the groom.
This should be specified as 'M', 'W', 'D', or 'S'.
  </div>
  <div class='balloon' id='HelpBMarStat'>
The marital status of the bride.
This should be specified as 'M', 'W', 'D', or 'S'.
  </div>
  <div class='balloon' id='HelpGReligion'>
The religion of the groom.
  </div>
  <div class='balloon' id='HelpBReligion'>
The religion of the bride.
  </div>
  <div class='balloon' id='HelpMReligion'>
The religion of the minister.
  </div>
  <div class='balloon' id='HelpGFatherName'>
The name of the Father of the groom.
  </div>
  <div class='balloon' id='HelpBFatherName'>
The name of the Father of the bride.
  </div>
  <div class='balloon' id='HelpGMotherName'>
The name of the Mother of the groom.
  </div>
  <div class='balloon' id='HelpBMotherName'>
The name of the Mother of the bride.
  </div>
  <div class='balloon' id='HelpWitness1'>
The name of a formal witness to the marriage.
  </div>
  <div class='balloon' id='HelpWitness2'>
The name of a formal witness to the marriage.
  </div>
  <div class='balloon' id='HelpWitness1Res'>
The residence of a formal witness to the marriage.
  </div>
  <div class='balloon' id='HelpWitness2Res'>
The residence of a formal witness to the marriage.
  </div>
  <div class='balloon' id='HelpRemarks'>
  <p>This field is used to record any comments by the registrar or clerk.
You may use this field to record your own comments by enclosing them in
editorial square brackets.
  </p>
  </div>
  <div class='balloon' id='HelpUpdate'>
Clicking on this button commits the changes you have made to the database.
  </div>
  <div class='balloon' id='HelpReset'>
Clicking on this button resets the values of some fields to their defaults.
  </div>
  <div class='balloon' id='Helpprevious'>
Clicking on this button displays the marriage registration with the next
lower registration number:
	RegDomain=<?php print $domain; ?>, RegYear=<?php print $regYear; ?>,
	RegNum=<?php print $prevNum; ?>
  </div>
  <div class='balloon' id='Helpnext'>
Clicking on this button displays the marriage registration with the next
higher registration number:
	RegDomain=<?php print $domain; ?>, RegYear=<?php print $regYear; ?>,
	RegNum=<?php print $nextNum; ?>
  </div>
  <div class='balloon' id='HelpnewQuery'>
Clicking on this button displays the dialog for searching for marriage
registrations.
  </div>
  <div class='balloon' id='HelpRegYear'>
This read-only field displays the year in which this marriage was
registered.
  </div>
  <div class='balloon' id='HelpRegNum'>
This read-only field displays the registration number assigned by
the Registrar of Ontario within the registration year.
  </div>
  <div class='balloon' id='HelpRegDate'>
This field displays the date on which the marriage was registered/
  </div>
  <div class='balloon' id='HelpRegistrar'>
This field displays the name of the registrar who recorded the
marriage registration.
  </div>
  <div class='balloon' id='HelpMsVol'>
This read-only field displays the number of the reel of microfilm containing
the original image of this registration.
  </div>
  <div class='balloon' id='HelpRegCounty'>
This selection list is used to choose the county for a new registration
or to display the county for an existing registration.
  </div>
  <div class='balloon' id='HelpOriginalVolume'>
This field contains the identification of the original bound volume
containing the marriage registrations or certificates.
  </div>
  <div class='balloon' id='HelpOriginalPage'>
This field contains the page number containing the original
marriage registrations or certificate.
  </div>
  <div class='balloon' id='HelpOriginalItem'>
Where there are more than one marriage registration on the page this field 
contains the ordinal position of the specific registration on the page.
  </div>
  <div class='balloon' id='HelpImage'>
This field is used to supply the Uniform Record Location (URL) of an image
file for this registration.
  </div>
  <div class='balloon' id='HelpviewImage'>
Click on this button to see the original image of the registration.
  </div>
</body>
</html>
