<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editMarriages.php							*
 * 									*
 *  Display a web page for editing the families for which a particular 	*
 *  individual has the role of spouse from the Legacy database		*
 * 									*
 *  Parameters (passed by method=get) 					*
 *	idir		unique numeric key of individual as spouse	*
 *	given		given name of individual in case that		*
 *			information is not already written to the	*
 *			database					*
 *	surname		surname of individual in case that information	*
 *			is not already written to the database		*
 *	idmr		numeric key of specific marriage to initially	*
 *			display						*
 * 									*
 *  History: 								*
 * 	2010/08/21	Change to use new page format			*
 *	2010/09/04	Add button to reorder marriages			*
 *			Get birth and death dates into variables	*
 *	2010/10/21	use RecOwners class to validate access		*
 *			add balloon help for buttons			*
 *	2010/10/23	move connection establishment to common.inc	*
 *	2010/11/15	eliminate use of obsolete showDate		*
 *	2010/11/27	add parameters given and surname because the	*
 *			user may have modified the name in the		*
 *			invoking editIndivid.php web page but not	*
 *			updated the database record yet.		*
 *	2010/12/04	add link to help panel 				*
 *			improve separation of HTML and JS		*
 *	2010/12/12	replace LegacyDate::dateToString with		*
 *			LegacyDate::toString				*
 *			escape special chars in title			*
 *	2010/12/20	handle exception thrown by new Person	*
 *			handle both idir= and id=			*
 *	2011/01/10	use LegacyRecord::getField method		*
 *	2011/03/25	support keyboard shortcuts			*
 *	2011/06/18	merge with editMarriage.php			*
 *	2011/10/01	support database assisted location name		*
 *	2011/11/15	add parameter idmr to initiate editing specific	*
 *			family						*
 *			add buttons to edit Husband or Wife as		*
 *			individuals					*
 *	2011/11/26	support editing married surnames		*
 *	2011/12/21	support additional events			*
 *			display all events in the marriage panel	*
 *			suppress function if user is not authorized	*
 *	2012/01/13	change class names				*
 *			all buttons use id= rather than name= to avoid	*
 *			problems with IE passing them as parameters	*
 *			support updating all fields of Family	*
 *			record						*
 *			use $idir as identifier of primary spouse	*
 *	2012/01/23	display loading indicator while waiting for	*
 *			response					*
 *			to changed in a location field			*
 *	2012/02/01	permit idir parameter optional if idmr specified*
 *	2012/02/25	change ids of fields in marriage list to contain*
 *			IDMR instead of row number			*
 *	2012/05/27	specify explicit class on all			*
 *			<input type='text'>				*
 *	2012/05/29	identify row of table of children by IDCR in	*
 *			case the same child appears more than once	*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *	2017/07/27	class LegacyCitation renamed to class Citation	*
 *	2017/09/12	use get( and set(				*
 *	2017/09/28	change class LegacyEvent to class Event		*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/Family.inc';
    require_once __NAMESPACE__ . '/Event.inc';
    require_once __NAMESPACE__ . '/Temple.inc';
    require_once __NAMESPACE__ . '/LegacyHeader.inc';
    require_once __NAMESPACE__ . '/RecOwners.inc';

    // get the parameters passed to the script
    $idir	= null;		// individual as primary spouse in family
    $given	= null;		// given name of individual
    $surname	= null;		// given name of individual
    $idime	= null;		// initial marriage

    foreach($_GET as $key => $value)
    {		// loop through all parameters passed to script
	switch(strtolower($key))
	{	// take action on specific parameter
	    case 'id':
	    case 'idir':
	    {	// identify primary spouse
		$idir		= $value;
		break;
	    }	// identify primary spouse

	    case 'given':
	    {
		$given		= $value;
		break;
	    }	// default given name of individual

	    case 'surname':
	    {
		$surname	= $value;
		break;
	    }	// default surname of individual

	    case 'idmr':
	    {	// identify specific marriage to select for display
		$idime		= $value;
		break;
	    }	// identify specific marriage

	    // ignore unrecognized parameters
	}	// take action on specific parameter
    }		// loop through all parameters passed to script

    // validate the parameters
    // the identification of a spouse must be provided
    if (strlen($idir) > 0 &&
	ctype_digit($idir))
    {		// the identified individual is the primary spouse
	$isOwner	= canUser('edit') && 
			  RecOwners::chkOwner($idir, 'tblIR');
	try
	{
	    $person		= new Person($idir, 'idir');
	    if ($given === null)
		$given		= $person->getGivenName();
	    if ($surname === null)
		$surname	= $person->getSurname();
	    $sex		= $person->get('gender');
	    $birth		= new LegacyDate($person->get('birthd'));
	    $birth		= $birth->toString();
	    $death		= new LegacyDate($person->get('deathd'));
	    $death		= $death->toString();
	    $idmrpref		= $person->get('idmrpref');
	    if($idmrpref == 0)
	    {		// set preferred marriage to first marriage if any
		if ($person->getNumMarriages() > 0)
		{	// at least one marriage
		    $family	= $person->getNextMarriage();
		    if($family)
			$idmrpref	= $family->getIdmr(); 
		}	// at least one marriage
	    }		// set preferred marriage to first marriage if any

	    if (strtolower(substr($surname, 0, 2)) == 'mc')
		$prefix	= 'Mc';
	    else
		$prefix	= substr($surname, 0, 1);

	    $title	= "Edit Marriages for $given $surname";
	}
	catch(Exception $e)
	{		// error in new Person
	    $title	= 'Invalid Identification of Primary Spouse';
	    $msg	= "idir=$idir: " . $e->getMessage();
	    $isOwner	= true;
	    $person	= null;
	}		// error in new Person

	if (!$isOwner)
	    $msg	.= 'You are not authorized to edit the marriages of '.
			$given . ' ' . $surname;
    }		// get the requested child
    else
    {		// required parameter missing or invalid
	$idir		= '';
	$person		= null;
	$prefix		= '';
	$surname	= '';
	$birth		= '';
	$death		= '';
	$title		= "idir Parameter Missing or Invalid";
    }		// missing required parameter

    // get the initial marriage to expand (even if it is not visible)
    if ($idime > 0)
	$idmr	= $idime;	// is visible
    else
	$idmr	= $idmrpref;	// is not visible

    try
    {
	$family			= new Family($idmr);
	$idirhusb		= $family->get('idirhusb');
	if ($person === null)
	{
	    $idir		= $idirhusb;
	    $person		= new Person($idir, 'idir');
	    $idmrpref		= $idmr;
	    $title		= "Edit Marriage IDMR=$idmr";
	}
	$husbgiven		= $family->get('husbgivenname');
	$eHusbgiven		= htmlspecialchars($husbgiven,
						   ENT_QUOTES);
	$husbsurname		= $family->get('husbsurname');
	$eHusbsurname		= htmlspecialchars($husbsurname,
						   ENT_QUOTES);
	$husbmarrsurname	= $family->get('husbmarrsurname');
	$eHusbmarrsurname	= htmlspecialchars($husbmarrsurname,
						   ENT_QUOTES);
	$idirwife		= $family->get('idirwife');
	$wifegiven		= $family->get('wifegivenname');
	$eWifegiven		= htmlspecialchars($wifegiven,
						   ENT_QUOTES);
	$wifesurname		= $family->get('wifesurname');
	$eWifesurname		= htmlspecialchars($wifesurname,
						   ENT_QUOTES);
	$wifemarrsurname	= $family->get('wifemarrsurname');
	$eWifemarrsurname	= htmlspecialchars($wifemarrsurname,
						   ENT_QUOTES);
	$idms			= $family->getIdms();
	$mdateo			= new LegacyDate($family->get('mard'));
	$sdateo			= new LegacyDate($family->get('seald'));
	$enotes			= htmlentities($family->get('notes'));
	$marriednamerule	= $family->get('marriednamerule');
	$notmarried		= $family->get('notmarried');
	$nochildren		= $family->get('nochildren');
	$seald			= $family->get('seald');
	$idtrseal		= $family->get('idtrseal');
	$numEvents		= $family->getNumEvents();
	$marendd		= $family->get('marendd');
	try
	{
	    $location	= new Location($family->get('idlrmar'));
	    $locname	= $location->getName();
	}
	catch(Exception $e)
	{		// error creating instance
	    $location	= null;
	    $locname	= '';
	} 		// error creating instance
    }
    catch(Exception $e)
    {		// new Family failed
	$family		= null;
	if ($sex == Person::MALE)
	{		// male
	    $idirhusb		= $idir;
	    $husbgiven		= $given;	// use supplied name
	    $eHusbgiven		= htmlspecialchars($husbgiven,
						   ENT_QUOTES);
	    $husbsurname	= $surname;	// use supplied name
	    $eHusbsurname	= htmlspecialchars($husbsurname,
						   ENT_QUOTES);
	    $idirwife	= 0;
	    $wifegiven	= '';
	    $eWifegiven	= '';
	    $wifesurname	= '';
	    $eWifesurname	= '';
	}		// male
	else
	{		// female
	    $idirhusb		= 0;
	    $husbgiven		= '';
	    $eHusbgiven		= '';
	    $husbsurname	= '';
	    $eHusbsurname	= '';
	    $idirwife		= $idir;
	    $wifegiven		= $given;
	    $eWifegiven		= htmlspecialchars($wifegiven,
						   ENT_QUOTES);
	    $wifesurname	= $surname;
	    $eWifesurname	= htmlspecialchars($wifesurname,
						   ENT_QUOTES);
	    $eWifemarrsurname	= $eHusbsurname;
	}		// female
	$mdateo		= new LegacyDate('');
	$sdateo		= new LegacyDate('');
	$location	= null;
	$locname	= '';
	$enotes		= '';
	$marriednamerule= 1;
	$notmarried	= 0;
	$nochildren	= 0;
	$seald		= '';
	$idtrseal	= 1;
	$numEvents	= 0;
	$marendd	= '';
	$title		= "Edit Marriage for $given $surname";
    }		// new Family failed

    $locenc		= htmlspecialchars($locname,
				       ENT_QUOTES);

    // married name rule is a selection list, so we need to
    // know which option to mark as selected
    if ($family && $marriednamerule == 0)
    {	// explicit specification of married surnames
	$namerule0selected	= "selected='selected'";
	$namerule1selected	= "";
	$marSurnameClass	= 'actleftnc';
	$marSurnameRo	= '';
    }	// explicit specification of married surnames
    else
    {	// traditional wife takes husband's surname
	$namerule0selected	= "";
	$namerule1selected	= "selected='selected'";
	$marSurnameClass	= 'inaleftnc';
	$marSurnameRo	= "readonly='readonly'";
    }	// traditional wife takes husband's surname

    $title	= htmlspecialchars($title);
    htmlHeader($title,
		array(	'/jscripts/CommonForm.js',
			'/jscripts/util.js',
			'/jscripts/js20/http.js',
 			'/jscripts/Cookie.js',
 			'/Common.js',
 			'commonMarriage.js',
			'editMarriages.js'));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> 'Genealogy',
		  "/genCountry.php?cc=CA"		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	<?php print $title; ?>
      <span class='right'>
	<a href='editMarriagesHelp.html' target='help'>? Help</a>
      </span>
    </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
    }		// error message to display
    else
    if ($person)
    {		// primary spouse found
?>
<form name='indForm' action='/FamilyTree/updateMarriageXml.php' method='post'>
  <div>
    <input type='hidden' name='idir' value='<?php print $idir; ?>'>
    <input type='hidden' name='sex' value='<?php print $sex; ?>'>
  </div>
    <table class='details' id='marriageList'>
	<thead>
	    <tr>
		<th class='colhead'>
		    Marriage Date
		</th>
		<th class='colhead'>
		    Spouse Name
		</th>
		<th class='colhead'>
		    Pref
		</th>
		<th class='colhead' colspan='2'>
		    Actions
		</th>
	    </tr>
	</thead>
	<tbody id='marriageListBody'>
<?php
	    // it is necessary to call Person::getNumMarriages to ensure
	    // that the first call to Person::getNextMarriage returns
	    // the first marriage
	    $numMarriages	= $person->getNumMarriages();
	    while(($family	= $person->getNextMarriage()) != null)
	    {		// loop through marriages
		$idmr		= $family->getIdmr(); 

		if ($person->getGender() == Person::FEMALE)
		{		// female
		    $spsSurname	= $family->get('husbsurname');
		    $spsGiven	= $family->get('husbgivenname');
		    $spsid	= $family->get('idirhusb');
		    $spsclass	= 'male';
		}		// female
		else
		{		// male
		    $spsSurname	= $family->get('wifesurname');
		    $spsGiven	= $family->get('wifegivenname');
		    $spsid	= $family->get('idirwife');
		    $spsclass	= 'female';
		}		// male
    
		// information about spouse
		try
		{
		    $spouse	= new Person($spsid,
					     'idir');
		}
		catch(Exception $e)
		{
		    $spouse	= null;
		}
		$mdateo	= new LegacyDate($family->get('mard'));
		$mdate	= $mdateo->toString();

		if (strlen($mdate) == 0)
		    $mdate	= 'Unknown';
?>
	<tr id='marriage<?php print $idmr; ?>'>
	  <td id='mdate<?php print $idmr; ?>'>
		<?php print $mdate; ?> 
	  </td>
	  <td>
	    <a href='Person.php?id=<?php print $spsid; ?>'
		class='<?php print $spsclass; ?>'
		id='spousename<?php print $idmr; ?>'>
		<?php print $spsGiven; ?> <?php print $spsSurname; ?>
	    </a>
	  </td>
	  <td class='center'>
	    <input type='checkbox' name='Pref<?php print $idmr; ?>'
		<?php if ($idmr == $idmrpref) print "checked"; ?>>
	  </td>
	  <td>
	    <button type='button' id='Edit<?php print $idmr; ?>'>
		Edit Marriage
	    </button>
	  </td>
	  <td>
	    <button type='button' id='Delete<?php print $idmr; ?>'>
		Delete Marriage
	    </button>
	  </td>
	</tr>
<?php
	    }		// loop through marriages
?>
      </tbody>
      <tfoot>
	<tr>
	  <td>
	  </td>
	  <td>
	  </td>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='Add'>
		<u>A</u>dd Marriage
	    </button>
	  </td>
	</tr>
      </tfoot>
    </table>
<p>
  <button type='button' id='Finish'>
	<u>C</u>lose
  </button>
&nbsp;
  <button type='button' id='Reorder'>
	<u>O</u>rder Marriages by Date
  </button>
</p>
</form>
</div>
<div id='marriage' class='submenu'>
<form name='famForm' action='updateMarriageXml.php' method='post'>
  <div id='marriageFormDiv'>
    <table id='formTable'>
      <tbody>
	<tr id='IdmrRow'>
	  <th class='labelSmall'>
		    IDMR:
	  </th>
	  <td class='left'>
	    <input type='text' name='idmr' size='6' class='ina rightnc'
			readonly='readonly' value='<?php print $idmr; ?>'>
	</tr>
	<tr id='Husb'>
	  <th class='labelSmall'>
		    Husband:
	    <input type='hidden' name='IDIRHusb'
			value='<?php print $idirhusb; ?>'>
	  </th>
	  <td class='left'>
	    <input type='text' name='HusbGivenName' size='30'
			maxlength='120'
			class='ina left'
			value='<?php print $eHusbgiven; ?>'
			readonly='readonly'>
	  </td>
	  <td>
	    <input type='text' name='HusbSurname' size='20'
			maxlength='120'
			class='ina left'
			value='<?php print $eHusbsurname; ?>'
			readonly='readonly'>
	  </td>
	  <td>
	    <button type='button' id='editHusb'>
		    Edit Husband
	    </button>
	  </td>
	</tr>
	<tr id='HusbMarrSurnameRow'>
	  <td>
	  </td>
	  <th class='labelSmall'>
	    Husband's Married Surname:
	  </th>
	  <td>
	    <input type='text' name='HusbMarrSurname' size='20'
			maxlength='255' <?php print $marSurnameRo; ?>
			class='<?php print $marSurnameClass; ?>'
			value='<?php print $eHusbmarrsurname; ?>'>
	  </td>
	</tr>
	<tr>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='changeHusb'>
		    Select Existing Husband
	    </button>
	  </td>
	  <td>
	    <button type='button' id='createHusb'>
		    Create New <u>H</u>usband
	    </button>
	  </td>
	  <td>
	    <button type='button' id='detachHusb'>
		    Detach Husband
	    </button>
	  </td>
	</tr> <!-- end of Husband row -->
	<tr id='Wife'>
	  <th class='labelSmall'>
		    Wife:
	    <input type='hidden' name='IDIRWife'
			value='<?php print $idirwife; ?>'>
	  </th>
	  <td class='left'>
	    <input type='text' name='WifeGivenName' size='30'
			maxlength='120'
			class='ina left'
			value='<?php print $eWifegiven; ?>'
			readonly='readonly'>
	  </td>
	  <td>
	    <input type='text' name='WifeSurname' size='20'
			maxlength='120'
			class='ina left'
			value='<?php print $eWifesurname; ?>'
			readonly='readonly'>
	  </td>
	  <td>
	    <button type='button' id='editWife'>
		    Edit Wife
	    </button>
	</tr>
	<tr id='WifeMarrSurnameRow'>
	  <td>
	  </td>
	  <th class='labelSmall'>
	    Wife's Married Surname:
	  </th>
	  <td>
	    <input type='text' name='WifeMarrSurname' size='20'
			maxlength='255' <?php print $marSurnameRo; ?>
			class='<?php print $marSurnameClass; ?>'
			value='<?php print $eWifemarrsurname; ?>'>
	  </td>
	</tr>
	<tr>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='changeWife'>
		    Select Existing Wife
	    </button>
	  </td>
	  <td>
	    <button type='button' id='createWife'>
		    Create New <u>W</u>ife
	    </button>
	  </td>
	  <td>
	    <button type='button' id='detachWife'>
		    Detach Wife
	    </button>
	  </td>
	</tr> <!-- end of Wife row -->
	<tr id='Marriage'>
	  <th class='labelSmall'>
		    Married:
	  </th>
	  <td class='left'>
	    <input type='text' name='MarD' size='20'
			maxlength='100'
			class='white left'
			value='<?php print $mdateo->toString(); ?>'>
		    at
	  </td>
	  <td colspan='2'>
	    <input type='text' name='MarLoc' size='40'
			maxlength='255'
			class='white leftnc'
			value='<?php print $locenc; ?>'>
	  </td>
	  <td>
	    <button type='button' id='marriageDetails'>
		    Details
	    </button>
	  </td>
	</tr> <!-- end of Marriage row -->
<?php
	// display other events
	$rownum		= 1;

	// never married indicator
	if ($notmarried > 0)
	{		// never married indicator
	    $citType	= Citation::STYPE_MARNEVER;	// 22
?>
	<tr>
	  <th class='labelSmall'>
		<input type='hidden' readonly='readonly'
			name='citType<?php print $rownum; ?>'
			value='<?php print $citType; ?>'>
		Never Married:
	  </th>
	  <td class='left'>
		<input type='checkbox' name='NotMarried'
			checked='checked'>
	  </td>
	  <td colspan='2'>
		<!-- No data other than flag -->
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent<?php print $rownum; ?>'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent<?php print $rownum; ?>'>
		Delete
	    </button>
	  </td>
	</tr>
<?php
	    $rownum++;
	}		// never married indicator present

	// no children indicator
	if ($nochildren > 0)
	{		// no children indicator
	    $citType	= Citation::STYPE_MARNOKIDS;	// 23
?>
	<tr>
	  <th class='labelSmall'>
		<input type='hidden' readonly='readonly'
			name='citType<?php print $rownum; ?>'
			value='<?php print $citType; ?>'>
		No Children:
	  </th>
	  <td class='left'>
		<input type='checkbox' name='NoChildren'
			checked='checked'>
	  </td>
	  <td colspan='2'>
		<!-- No data other than flag -->
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent<?php print $rownum; ?>'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent<?php print $rownum; ?>'>
		Delete
	    </button>
	  </td>
	</tr>
<?php
	    $rownum++;
	}		// no children indicator present

	// LDS sealing
	if (strlen($seald) > 0)
	{		// LDS sealing present
	    $citType	= Citation::STYPE_LDSS;	// 18
	    $date	= new LegacyDate($seald);
	    $temple	= new Temple($idtrseal);
	    $locn	= htmlspecialchars($temple, 
					   ENT_QUOTES);
?>
	<tr>
	  <th class='labelSmall'>
		<input type='hidden' readonly='readonly'
			name='citType<?php print $rownum; ?>'
			value='<?php print $citType; ?>'>
		Sealed to Spouse (LDS):
	  </th>
	  <td class='left'>
		<input type='text' size='20' maxlength='100'
			name='SealD'
			class='white left'
			value='<?php print $date->toString(); ?>'>
		    at
	  </td>
	  <td colspan='2'>
		<input type='text' readonly='readonly' size='40'
			name='SealLoc'
			class='white left'
			value='<?php print $locn; ?>' >
		<input type='hidden' readonly='readonly'
			name='IDTRSeal'
			value='<?php print $idtrseal; ?>' >
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent<?php print $rownum; ?>'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent<?php print $rownum; ?>'>
		Delete
	    </button>
	  </td>
	</tr>
<?php
	    $rownum++;
	}		// LDS sealing present

	// display events from Events table
	if ($numEvents > 0)
	{		// events to display

	    while(($event = $family->getNextEvent()) != null)
	    {		// loop through events
		$ider	= $event->getIder();
		$citType= $event->getCitType();
		$type	= $event->getType(false);
		$date	= $event->getDate();
		$desc	= htmlspecialchars($event->getDesc(), 
					   ENT_QUOTES);
		$descn	= htmlspecialchars($event->getDescription());
		$locn	= htmlspecialchars($event->getLocation()->getName(), 
					   ENT_QUOTES);
?>
	<tr>
	  <th class='labelSmall'>
		<input type='hidden' readonly='readonly'
			name='ider<?php print $rownum; ?>'
			value='<?php print $ider; ?>'>
		<input type='hidden' readonly='readonly'
			name='citType<?php print $rownum; ?>'
			value='<?php print $citType; ?>'>
		<?php print ucfirst($type); ?> <?php print ucfirst($descn); ?> 
	  </th>
	  <td class='left'>
		<input type='text' readonly='readonly' size='20'
			name='Date<?php print $rownum; ?>'
			class='white left'
			value='<?php print $date; ?>'>
		    at
	  </td>
	  <td colspan='2'>
		<input type='text' readonly='readonly' size='40'
			name='Loc<?php print $rownum; ?>'
			class='white leftnc'
			value='<?php print $locn; ?>' >
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent<?php print $rownum; ?>'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent<?php print $rownum; ?>'>
		Delete
	    </button>
	  </td>
	</tr>
<?php
		$rownum++;
	    }		// loop through events
	}		// events to display

	/****************************************************************
	//  marriage ended event
	/
	//  The Legacy database contains a marriage end date which is 
	//  presumably intended to record the unofficial termination of
	//  the relationship where there is no formal event, such as
	//  Divorce (ET_DIVORCE), or Annulment (ET_ANNULMENT).
	//  Note that tblER does not define a formal separation event
	//  although that could be user implemented as ET_MARRIAGE_FACT
	//  with description "Separation".  The Legacy database also
	//  does not define a citation type in tblSX to be able to document
	//  the information source for knowledge of the end of the marriage.
	//  To permit this event to be handled in a manner consistent with
	//  all other events, a new citation type is defined.
	/
	/****************************************************************/
	if (strlen($marendd) > 0)
	{		// marriage ended date present
	    $citType	= Citation::STYPE_MAREND;	// 24
	    $date	= new LegacyDate($family->get('marendd'));
?>
	<tr>
	  <th class='labelSmall'>
		<input type='hidden' readonly='readonly'
			name='citType<?php print $rownum; ?>'
			value='<?php print $citType; ?>'>
		Marriage Ended:
	  </th>
	  <td class='left'>
		<input type='text' size='20' maxlength='100'
			name='MarEndD'
			class='white left'
			value='<?php print $date->toString(); ?>'>
	  </td>
	  <td colspan='2'>
		<!-- No data other than date -->
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent<?php print $rownum; ?>'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent<?php print $rownum; ?>'>
		Delete
	    </button>
	  </td>
	</tr>
<?php
	    $rownum++;
	}		// marriage ended date present
?>
	<tr id='AddEventRow'>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
		<button type='button' id='AddEvent'>
		    <u>A</u>dd Event
		</button>
	    </td>
	</tr>

	<tr id='Status'>
	  <th class='labelSmall'>
		    Status:
	  </th>
	  <td colspan='3'>
	    <select name='IDMS' size='1'>
<?php
	foreach(Family::$statusText as $status => $text)
	{		// loop through status values
	    if ($family && $status == $family->getIdms())
		$selected	= " selected='selected'";
	    else
		$selected	= "";
?>
		<option value='<?php print $status;?>' <?php print $selected;?>>
		    <?php print $text; ?> 
		</option>
<?php
	}		// loop through status values
?>
	    </select>
	  </td>
	</tr> <!-- end of Ending Status row -->

	<tr id='NameRule'>
	  <th class='labelSmall'>
		Name Rule:
	  </th>
	  <td colspan='3'>
	    <select name='MarriedNameRule' size='1'>
		<option value='0' <?php print $namerule0selected; ?>>
		    Don't Generate Married Names
		</option>
		<option value='1' <?php print $namerule1selected; ?>>
		    Replace Wife's Surname with Husband's Surname
		</option>
	    </select>
	  </td>
	</tr> <!-- end of Name Rule row -->

	<tr id='Notes'>
	  <th class='labelSmall'>
		Notes:
	  </th>
	  <td colspan='3'>
    <!-- note that when initializing a <textarea>, unlike other tags
	 the space around the text value becomes part of the value
	 of the tag, so there can be no space characters between the 
	 opening and closing tags and the value of the field -->
	    <textarea name='Notes' cols='60' rows='4'
		><?php print $enotes; ?></textarea>
	  </td>
	  <td>
	    <button type='button' id='noteDetails'>
		Details
	    </button>
	  </td>
	</tr> <!-- end of Notes row -->
      </tbody>
    </table>
    <p class='label'>Children:</p>
    <table class='details' id='children'>
      <thead>
	<tr>
	    <th class='colhead'>
		&nbsp;&nbsp;&nbsp;Given&nbsp;&nbsp;&nbsp;
	    </th>
	    <th class='colhead'>
		&nbsp;&nbsp;Surname&nbsp;&nbsp;
	    </th>
	    <th class='colhead'>
		&nbsp;Birth&nbsp;
	    </th>
	    <th class='colhead'>
		&nbsp;Death&nbsp;
	    </th>
	    <th class='colhead' colspan='2'>
		Actions
	    </th>
	</tr>
      </thead>
      <tbody>
      </tbody>
      <tfoot>
	<tr>
	    <td colspan='4'>
	    </td>
	    <td>
		<button type='button' id='addChild'>
		    Add <u>E</u>xisting Child
		</button>
	    </td>
	    <td>
		<button type='button' id='addNewChild'>
		    Add <u>N</u>ew Child
		</button>
	    </td>
	</tr>
      </tfoot>
    </table> <!-- id='children' -->
<p>
  <button type='submit' id='update'>
	<u>U</u>pdate Marriage
  </button>
&nbsp;
  <button type='button' id='orderChildren'>
	<u>O</u>rder Children by Birth Date
  </button>
&nbsp;
  <button type='button' id='Pictures'>
	Edit <u>P</u>ictures
  </button>
</p>
</div> <!-- id='marriageFormDiv' -->
</form>
</div> <!-- id='marriage' -->
<?php
    }		// individual found
?>
  </div>
<?php
    pageBot();
?>
<div class='hidden' id='templates'>
  <table id='templateRows'>
    <!--
     *	layout of the table row to display a summary of a marriage
     *  in the table at the top of the dialog.
     *	Putting the layout here permits more user
     *	customization, including support for alternate languages.
     *	This row layout should match the rows in <tbody id='marriageListBody'>
    -->
	<tr id='marriage$idmr'>
	  <td id='mdate$idmr'>
		$mdate
	  </td>
	  <td>
	    <a href='Person.php?id=$spsid'
		class='$spsclass'
		id='spousename$idmr'>
		$spsGiven $spsSurname
	    </a>
	  </td>
	  <td class='center'>
	    <input type='checkbox' name='Pref$idmr'>
	  </td>
	  <td>
	    <button type='button' id='Edit$idmr'>
		Edit Marriage
	    </button>
	  </td>
	  <td>
	    <button type='button' id='Delete$idmr'>
		Delete Marriage
	    </button>
	  </td>
	</tr>
    <!--
     *	layout of the table row to display a single child of this marriage
     *  each row is added by javascript when the XML response to the AJAX
     *	request is received.  Putting the layout here permits more user
     *	customization, including support for alternate languages.
    -->
    <tr id='child$idcr'>
	<td class='name'>
	    <span class='$gender'>$givenname</span>
	</td>
	<td class='name'>
	    <span class='$gender'>$surname</span>
	</td>
	<td class='name'>
	    $birthd
	</td>
	<td class='name'>
	    $deathd
	</td>
	<td>
	    <button type='button' id='editChild$idir'>
		Edit Child
	    </button>
	</td>
	<td>
	    <button type='button' id='detChild$idcr'>
		Detach Child
	    </button>
	</td>
    </tr>
  </table>
</div> <!-- id='templates' -->
<div class='balloon' id='HelpPref'>
<p>Click on the checkbox to make the specified marriage the preferred
marriage.
</p>
</div>
<div class='balloon' id='HelpEdit'>
<p>Edit the marriage on this row.  A dialog is displayed with details 
of the marriage.
</p>
</div>
<div class='balloon' id='HelpDelete'>
<p>Delete the marriage on this row.
</p>
</div>
<div class='balloon' id='HelpAdd'>
<p>Add a new marriage to the current individual.
</p>
</div>
<div class='balloon' id='HelpFinish'>
<p>Close the dialog.  The updates to the database have already been made.
</p>
</div>
<div class='balloon' id='HelpReorder'>
<p>Change the order of the marriages to be in chronological order by
marriage date.  If you know the actual order of the marriages, but do not
know the exact date of a marriage, it is recommended that you specify 
a range of dates for the marriage as this will not only permit using this
feature to order the marriages correctly, but also give a hint as to which
documentary sources to search to complete the information.
</p>
</div>
<div class='balloon' id='Helpidmr'>
<p>This read-only field displays the internal numeric identifier of
this relationship.
</p>
</div>
<div class='balloon' id='HelpIDIRHusb'>
<p>This displays the given names of the husband.
This is a read-only field.  The husband is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpHusbGivenName'>
<p>This displays the given names of the husband.
This is a read-only field.  The husband is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpHusbSurname'>
<p>This displays the family name of the husband. 
This is a read-only field.  The husband is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpHusbMarrSurname'>
<p>This displays the family name by which the husband was known during this
marriage.
This is a read-only field if the traditional rule in which the Husband did
not change his surname on marriage is in effect.
</p>
</div>
<div class='balloon' id='HelpWifeGivenName'>
<p>This displays the given names of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpIDIRWife'>
<p>This displays the given names of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpWifeSurname'>
<p>This displays the family name of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons associated with this row. 
</p>
</div>
<div class='balloon' id='HelpWifeMarrSurname'>
<p>This displays the family name by which the wife was known during this
marriage.
This is a read-only field if the traditional rule in which the Wife took
her husband's surname is in effect.
</p>
</div>
<div class='balloon' id='HelpchangeHusb'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the husband
in this marriage.
</p>
</div>
<div class='balloon' id='HelpeditHusb'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to modify information about the individual
who is the husband in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateHusb'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the husband in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachHusb'>
<p>Selecting this button detaches the currently assigned husband from this
marriage.  It is not necessary to do this before selecting or creating
a new husband.
</p>
</div>
<div class='balloon' id='HelpeditWife'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to modify information about the individual
who is the wife in this marriage.
</p>
</div>
<div class='balloon' id='HelpchangeWife'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the wife
in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateWife'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the wife in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachWife'>
<p>Selecting this button detaches the currently assigned wife from this
marriage.  It is not necessary to do this before selecting or creating
a new wife.
</p>
</div>
<div class='balloon' id='HelpMarD'>
<p>Supply the date of the marriage.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href='datesHelp.html'>supported date formats</a> for details.
</p>
</div>
<div class='balloon' id='HelpMarLoc'>
<p>Supply the location of the marriage.  The text you enter is used to
select an appropriate Location record.  This is done by first doing a
case-insensitive search for a match on the complete text you entered, and if
this fails then a search is done for a match on the short name of the
location.  If no match is found on either search then a new location is
created using exactly the text you entered.  Subsequently the only way that
you can change the appearance of the location is to either select a different
location by typing in its name or short name, or by editing the location
record itself.
</p>
</div>
<div class='balloon' id='HelpmarriageDetails'>
<p>Clicking on this button opens a dialog that permits you to add further
details about the marriage and to specify source citations for the fact.
</div>
<div class='balloon' id='HelpMarEndD'>
<p>Supply the date that the marriage or relationship came to an end.
If the marriage came to an end as a result of some specific event,
for example a divorce, annulment, or legal separation, then you
should add an event describing that rather than using this field.
<p>The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href='datesHelp.html'>supported date formats</a> for details.
</p>
</div>
<div class='balloon' id='HelpSealD'>
<p>Supply the date that the partners were sealed to each other at a
Church of Latter Day Saints temple.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href='datesHelp.html'>supported date formats</a> for details.
</p>
</div>
<div class='balloon' id='HelpSealLoc'>
<p>This read-only field contains the name of the Church of Latter Day
Saints temple where the partners were sealed to each other. 
To select a different temple click on the
<span class='button'>Details</span> button at the end of this row.
</p>
</div>
<div class='balloon' id='HelpDate'>
<p>This read-only field displays the date of the family event.
To modify the date, or any other information about this event, click on the
<span class='button'>Details</span> button at the end of this row.
</p>
</div>
<div class='balloon' id='HelpLoc'>
<p>This read-only field displays the location of the event.
To modify the location, or any other information about this event, click on the
<span class='button'>Details</span> button at the end of this row.
</p>
</div>
<div class='balloon' id='HelpAddEvent'>
<p>Selecting this button opens a
<a href='editEventHelp.html' target='_blank'>dialog</a> 
to edit the detailed information
about a new event being added to the marriage.
</p>
</div>
<div class='balloon' id='HelpEditEvent'>
<p>Selecting this button opens a
<a href='editEventHelp.html' target='_blank'>dialog</a> 
to edit the detailed information
about the event summarized in this line of the form.
In particular you may add source citations for the event.
</p>
</div>
<div class='balloon' id='HelpDelEvent'>
<p>Selecting this button deletes
the event summarized in this line of the form.
</p>
</div>
<div class='balloon' id='HelpIDMS'>
<p>This selection list permits you to specify the ending or current
status of this marriage.
</p>
</div>
<div class='balloon' id='HelpMarriedNameRule'>
<p>This selection list permits you to specify whether or not the wife
took her husband's surname as a result of the marriage.  The default
is the traditional practice.
</p>
</div>
<div class='balloon' id='HelpNotMarried'>
<p>This checkbox is used to indicate that the couple is known to have never
been married.  You may remove this fact either by clicking on the 
<span class='button'>Delete Event</span> button at the end of the line
containing the checkbox, or by clicking on the checkbox to change its state.
In the latter case the checkbox remains visible in the dialog until some
other action causes the dialog to be refreshed.
</p>
</div>
<div class='balloon' id='HelpNoChildren'>
<p>This checkbox is used to indicate that the couple is known to have never
had children.  You may remove this fact either by clicking on the 
<span class='button'>Delete Event</span> button at the end of the line
containing the checkbox, or by clicking on the checkbox to change its state.
In the latter case the checkbox remains visible in the dialog until some
other action causes the dialog to be refreshed.
</p>
</div>
<div class='balloon' id='HelpNotes'>
<p>Supply extended textual notes about the marriage.
</p>
<p>Although you might be tempted to include the text of a newspaper notice
about the marriage in this field, it is recommended that you put that
text into the citation text field instead.
</p>
</div>
<div class='balloon' id='HelpnoteDetails'>
<p>Click on this button to add additional information about the marriage notes.
In particular you may add source citations for the notes.
</p>
</div>
<div class='balloon' id='HelpaddChild'>
<p>Selecting this button, or using the keyboard short-cut alt-E, opens a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
to choose an existing individual
in the family tree database to add as a child of this family.
</p>
</div>
<div class='balloon' id='HelpaddNewChild'>
<p>Selecting this button, or using the keyboard short-cut alt-N, opens a 
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to create a new individual in the
family tree database that is added as a child of this family.
</p>
</div>
<div class='balloon' id='Helpupdate'>
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class='balloon' id='HelporderChildren'>
<p>Selecting this button, or using the keyboard short-cut alt-O, 
reorders the children of this marriage by their
dates of birth.
</p>
</div>
<div class='balloon' id='HelpeditChild'>
<p>Selecting this button opens a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to edit the detailed information
about the child summarized in this line of the form.
</p>
</div>
<div class='balloon' id='HelpdetChild'>
<p>Selecting this button detaches the child summarized in this line of the
form from this family.  You can then go to another family and attach the
child there.
</p>
</div>
<div class='balloon' id='HelpPictures'>
<p>Selecting this button opens a dialog
to edit the set of pictures associated with this family.
</p>
</div>
<div id='loading' class='popup'>
Loading...
</div>
</body>
</html>
