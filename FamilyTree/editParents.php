<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editParents.php														*
 *																		*
 *  Display a web page for displaying the parents of a particular 		*
 *  individual from the Legacy database									*
 *																		*
 *  Parameters (passed by method=get) 									*
 *		idir			unique numeric key of individual				*
 *		given			given name of individual in case that			*
 *						information is not already written to the		*
 *						database										*
 *		surname			surname of individual in case that information	*
 *						is not already written to the database			*
 *		idmr			numeric key of specific marriage to initially	*
 *						display											*
 *		treename		name of tree subdivision of database			*
 *		new				parameter to add a new set of parents			*
 *																		*
 *  History: 															*
 * 		2010/08/21		Change to use new page format					*
 * 		2010/09/05		Make Close button work							*
 *		2010/10/21		use RecOwners class to validate access			*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/27		add parameters given and surname because the	*
 *						user may have modified the name in the			*
 *						invoking editIndivid.php web page but not		*
 *						updated the database record yet.				*
 *		2010/12/04		add link to help panel 							*
 *						improve separation of HTML and JS				*
 *		2010/12/12		escape title									*
 *		2010/12/20		accept parameter idir= as well as id=			*
 *						handle exception from new LegacyIndiv			*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/03/26		support shortcut keys							*
 *		2011/08/21		do not initialize given name of father			*
 *						for new set of parents							*
 *		2011/11/26		support database assisted location name			*
 *						add buttons to edit Husband or Wife as individuals*
 *						support editing married surnames				*
 *		2012/01/13		change class names								*
 *						make changes to match editMarriages.php			*
 *		2012/02/25		change ids of fields in marriage list to contain*
 *						IDMR instead of row number						*
 *						use id= keyword on buttons to avoid passing		*
 *						them to the action script						*
 *		2012/05/30		specify explicit class on all input fields		*
 *						identify child's row by IDCR rather than IDIR	*
 *		2012/11/17		initialize $family for display of specific		*
 *						marriage										*
 *						display family events from event table on		*
 *						requested marriage.								*
 *						change implementation so event type or IDER		*
 *						value is contained in the name of the button,	*
 *						not from a hidden field matching the rownum		*
 *		2012/11/27		for consistency the marriage details form is	*
 *						always filled in dynamically as a result of		*
 *						receiving the response to an AJAX request,		*
 *						rather than sometimes filled in by PHP and some	*
 *						times by javascript.							*
 *						the location of the sealed to spouse event is	*
 *						made a selection list to permit updating.		*
 *		2013/01/14		remove reference to obsolete var $enotes		*
 *		2013/02/26		move IDIR fields for parents to hide from mouse	*
 *						help											*
 *		2013/03/03		make children's names and dates editable		*
 *		2013/03/25		complete editability of children				*
 *		2013/05/20		add templates for never married and no children	*
 *						facts											*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *						rename buttons to choose an existing individual	*
 *						as husband or wife to id="choose..."			*
 *						handle all child rows the same with the fields	*
 *						uniquely identified by the order value of the	*
 *						corresponding LegacyChild records				*
 *						use internal names with Husb/Wife, not			*
 *						Father/Mother to simplify implementation		*
 *		2014/03/19		use CSS rather than tables to layout form		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/06/02		restore value of IDCR for each child			*
 *		2014/07/15		add help balloon for Order Events button		*
 *						add msgDiv										*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/02		add prompt to confirm deletion					*
 *		2014/11/16		initialize display of family without requiring	*
 *						AJAX											*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/02/01		get temple select options from database			*
 *						get event texts from class Event and			*
 *						make them available to Javascript				*
 *		2015/02/19		remove user of deprecated interface to			*
 *						LegacyFamily constructor						*
 *						change remaining debug code to add to $warn		*
 *		2015/02/25		do not access name and birth date of spouses	*
 *						from the family record							*
 *		2015/04/28		add warning dialog that a child is already		*
 *						edited when attempt to edit the child for whom	*
 *						a set of parents is being created or edited		*
 *		2015/06/20		display error messages							*
 *						failed if IDMRParents set in individual to bad	*
 *						family value									*
 *						document action of enter key in child row		*
 *						Make the notes field a rich-text editor.		*
 *		2015/07/02		access PHP includes using include_path			*
 *						For new set of parents surname displayed		*
 *						incorrectly if child's surname contains quote	*
 *		2015/08/12		add support for tree division of database		*
 *		2016/02/06		use showTrace									*
 *		2016/03/14		given name and surname of children were not		*
 *						escaped for quotes.								*
 *						wrong class used for edit and detach buttons	*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/20      rearrange order of fields to simplify           *
 *		                updateMarriageXml.php                           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the parameters passed to the script
    $idir	= null;		// individual as primary spouse in family
    $idmr	= null;		// initial marriage
    $person	= null;		// instance of Person
    $family	= null;		// instance of Family
    $isowner	= false;	// current user is an owner of the family
    $given	= '';		// given name of individual
    $surname	= '';		// given name of individual
    $treename	= '';		// treename of database division
    $prefix	= '';		// initial part of surnames
    $birth	= '';		// birth date as string
    $death	= '';		// death date as string
    $idmrpref	= 0;		// preferred marriage for the individual
    $new	= false;
    $submit	= false;	// do not submit form, use AJAX

    foreach($_GET as $key => $value)
    {		// loop through all parameters passed to script
		switch(strtolower($key))
		{	// take action on specific parameter
		    case 'id':
		    case 'idir':
		    {	// identify child whose parents are to be displayed
				if (strlen($value) > 0)
				    if (ctype_digit($value))
						$idir		= $value;
				    else
						$msg	.= "Invalid IDIR=$value. ";
				break;
		    }	// identify child whose parents are to be displayed

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

		    case 'treename':
		    {
				$treename	= $value;
				break;
		    }	// tree name of database division

		    case 'idmr':
		    {	// identify specific set of parents to select for display
				if (strlen($value) > 0)
				    if (ctype_digit($value))
						$idmr		= $value;
				    else
						$msg	.= "Invalid IDMR=$value. ";
				break;
		    }	// identify specific set of parents

		    case 'new':
		    {		// add a new family
				if (strtolower($value) == 'y')
				    $new	= true;
				break;
		    }		// add a new family

		    case 'submit':
		    case 'debug':
		    {	// emit debugging information
				if ($value == 'Y' || $value == 'y')
				    $submit	= true;
				break;
		    }	// emit debugging information

		    // ignore unrecognized parameters
		}	// take action on specific parameter
    }		// loop through all parameters passed to script

    // get the child by identifier
    if (!is_null($idir))
    {		// get the requested individual
		try
		{
		    $person		= new Person(array('idir' => $idir));
		    $isOwner		= canUser('edit') && $person->isOwner();
		    if ($given === '')
				$given		= $person->getGivenName();
		    if ($surname === '')
				$surname= $person->getSurname();

		    $idmrpref		= $person->get('idmrparents');
		    $families		= $person->getParents();
		    if (count($families) == 0 && $idmrpref > 0)
		    {			// correct database error
				$person->set('idmrparents', 0);
				$person->save(false);
				$idmrpref	= 0;
		    }			// correct database error

		    if ($idmrpref == 0 || $new || !is_null($idmr))
		    {			// preferred parents not already set
				if (count($families) > 0 && !$new)
				{		// at least one set of parents
				    foreach($families as $id => $parents)
				    {		// have first parents
						if (is_null($family))
						    $family	= $parents;
						if ($idmrpref == 0)
						{	// set preferred parents in child
						    $idmrpref	= $id;
						    // update field in individual
						    $person->set('idmrparents', $idmrpref);
						    $person->save(false);
						}	// set preferred parents in child
						if ($id == $idmr)
						{	// match requested set of parents
						    $family	= $parents;
						    break;
						}	// match requested set of parents
				    }		// have first parents
				    // at this point $family is either the first set of
				    // parents or is the set matching the parameter idmr
				    // note that the parameter idmr is ignored if it does
				    // not match any of the sets of parents of the child
				    // identified by the parameter idir
				    if ($debug)
						$warn	.= "<p>Matched existing set of parents with IDMR=" .
							$family->getIdmr() . "</p>\n";
				}		// at least one set of parents
				else
				{		// create new set of parents
				    if ($debug)
						$warn	.= "<p>" . __LINE__ . " new Family(array('husbsurname' => '$surname')))</p>\n";
				    $parms	= array('husbsurname' => $surname,
								'husbmarrsurname' => $surname);
				    $family	= new Family($parms);
				    $idmr	= 0;
				}		// create new set of parents
		    }			// preferred parents not already set
		    else
		    {			// use idmrpref for set of parents
				if ($debug)
				    $warn	.= "<p>" . __LINE__ . " new Family('idmr'=>$idmrpref)</p>\n";
				$family		= new Family(array('idmr' => $idmrpref));
		    }			// use idmrpref for set of parents

		    if (strtolower(substr($surname, 0, 2)) == 'mc')
				$prefix	= 'Mc';
		    else
				$prefix	= substr($surname, 0, 1);

		    $title	= "Edit Parents for $given $surname";
		}
		catch(Exception $e)
		{	// error in creation of individual
		    $title	= 'Invalid Identification of child';
		    $msg	.= "idir=$idir: " . $e->getMessage();
		    $isOwner	= true;
		    $person	= null;
		}	// error in creation of individual

		if (!$isOwner)
		    $msg	.= 'You are not authorized to edit the parents of '.
						$given . ' ' . $surname;
    }		// get the requested individual
    else
    {		// missing required parameter
		$person		= null;
		$surname	= 'invalid id';
		$title		= 'idir Parameter Missing or Invalid';
    }		// missing required parameter

    $title	= str_replace('"','&quot;',$title);
    htmlHeader($title,
                array(  '/jscripts/tinymce/js/tinymce/tinymce.js',
				    	'/jscripts/CommonForm.js',
						'/jscripts/js20/http.js',
						'/jscripts/util.js',
 			            '/jscripts/Cookie.js',
 			            '/jscripts/locationCommon.js',
 			            'commonMarriage.js',
						'editParents.js'),
				true);
?>
<body>
  <div id="bodydiv" class="body">
    <h1>
      <span class="right">
		<a href="editParentsHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
		<div style="clear: both;"></div>
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {		// error message to display
?>
  <p class="message">
		<?php print $msg; ?> 
  </p>
<?php
    }		// error message to display

    if ($person)
    if ($person)
    {		// child found
?>
  <form name="indForm" id="indForm" action="updateParents.php" method="post">
      <fieldset id="FamiliesSet" class="other">
		<legend class="labelSmall">Families</legend>
		  <input type="hidden" name="idir" value="<?php print $idir; ?>">
		<table class="details" id="marriageList">
		  <thead>
		    <tr>
				<th class="colhead">
				    Father
				</th>
				<th class="colhead">
				    Mother
				</th>
				<th class="colhead">
				    Pref
				</th>
				<th class="colhead" colspan="2">
				    Actions
				</th>
		    </tr>
		</thead>
		<tbody id="marriageListBody">
<?php
		    $families		= $person->getParents();
		    foreach($families as $index => $tfamily)
		    {		// loop through marriages
				$idmr		= $tfamily->getIdmr(); 
				if($idmrpref == 0)
				{	// no preferred set of parents selected
				    $idmrpref	= $idmr;	// select first
				}	// no preferred set of parents selected
				$fatherid	= $tfamily->get('idirhusb');
				$fatherName	= $tfamily->getHusbName();
				$motherid	= $tfamily->get('idirwife');
				$motherName	= $tfamily->getWifeName();
?>
		<tr id="marriage<?php print $idmr; ?>">
		  <td>
		    <a href="Person.php?id=<?php print $fatherid ?>"
						class="male" id="fathername<?php print $idmr; ?>">
				<?php print $fatherName; ?>
		    </a>
		  </td>
		  <td>
		    <a href="Person.php?id=<?php print $motherid ?>"
						class="female" id="mothername<?php print $idmr; ?>">
				<?php print $motherName; ?>
		    </a>
		  </td>
		  <td class="center">
		    <input type="checkbox" name="Pref<?php print $idmr; ?>"
				<?php if ($idmr == $idmrpref) print "checked"; ?>>
		  </td>
		  <td>
		    <button type="button" id="Edit<?php print $idmr; ?>" >
				Edit Parents
		    </button>
		  </td>
		  <td>
		    <button type="button" id="Delete<?php print $idmr; ?>">
				Detach Parents
		    </button>
		</tr>
<?php
		    }		// loop through parents
?>
        </tbody>
      </table>
		<div class="row">
		    <label class="column1" for="Add">
				Actions:
		    </label>
		  <button type="button" class="button" id="Add">
				<u>A</u>dd Parents
		  </button>
		</div>
      </fieldset>
  </form> <!-- name="indForm" -->
<?php
		if ($family)
		{		// family chosen to display
		    $idmr		        = $family->getIdmr();
		    $idms		        = $family->get('idms');
		    $namerule		    = $family->get('marriednamerule');
		    $selected		    = ' selected="selected"';
		    $idirhusb		    = $family->get('idirhusb');
		    if ($idirhusb)
		    {
				$husb		    = $family->getHusband();
				$husbgivenname	= $husb->get('givenname');
				$husbgivenname	= str_replace('"','&quot;',$husbgivenname);
				$husbsurname	= $husb->get('surname');
				$husbbirthsd	= $husb->get('birthsd');
		    }
		    else
		    {
				$husbgivenname	= '';
				$husbsurname	= $surname;
				$husbbirthsd	= -99999999;
		    }
		    $husbsurname	= str_replace('"','&quot;',$husbsurname);
		    $idirwife		= $family->get('idirwife');
		    if ($idirwife)
		    {
				$wife		= $family->getWife();
				$wifegivenname	= $wife->get('givenname');
				$wifegivenname	= str_replace('"','&quot;',$wifegivenname);
				$wifesurname	= $wife->get('surname');
				$wifesurname	= str_replace('"','&quot;',$wifesurname);
				$wifebirthsd	= $wife->get('birthsd');
		    }
		    else
		    {
				$wifegivenname	= '';
				$wifesurname	= '';
				$wifebirthsd	= -99999999;
		    }
		    $evMar		= $family->getMarEvent(true);
		    $marDate		= $evMar->getDate();
		    $marLoc		= $evMar->getLocation()->toString();
		    $marLoc		= str_replace('"','&quot;',$marLoc);
		    $notes		= $family->get('notes');
?>
<!--*********************************************************************
 *		The current family is displayed in a separate form				*
 *																		*
 **********************************************************************-->
  <form name="famForm" action="updateMarriageXml.php" method="post">
	    <input type="hidden" name="idmr" id="idmr" value="<?php print $idmr; ?>">
		<input type="hidden" name="treename" id="treename" 
				value="<?php print str_replace('"','&quot;',$treename); ?>">
      <fieldset id="HusbandSet" class="male">
		<legend class="labelSmall">Father</legend>
		<div class="row" id="Husb">
		  <div class="column1">
		    <label class="column1" for="HusbGivenName`">
				Name:
		    </label>
		    <input type="hidden" name="IDIRHusb" id="IDIRHusb"
						value="<?php print $idirhusb; ?>">
		    <input type="text" name="HusbGivenName" id="HusbGivenName"
                    maxlength="120" class="white left column1"
                    value="<?php print $husbgivenname; ?>">
		    <input type="text" name="HusbSurname" id="HusbSurname"
                    maxlength="120" class="white left column2"
                    value="<?php print $husbsurname; ?>">
		    <input type="hidden" name="HusbBirthSD" id="HusbBirthSD"
						value="<?php print $husbbirthsd; ?>">
		  </div>
		  <div>
		    <button type="button" class="button" id="editHusb">
				Edit Father
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="HusbMarrSurnameRow">
		  <div class="column1">
		    <label class="column1" for="HusbMarrSurname">
				Married Surname:
		    </label>
	        <span class="left column1">&nbsp;</span>
		    <input type="text" name="HusbMarrSurname" id="HusbMarrSurname"
                    maxlength="255" class="white left column2"
                    value="<?php print $husbsurname; ?>">
		  </div>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="SelectHusbandRow">
		    <label class="column1" for="chooseHusb">
				Actions:
		    </label>
		    <button type="button" id="chooseHusb"
				class="button">
				Select Existing Father
		    </button>
		    &nbsp;
		    <button type="button" id="createHusb"
				class="button">
				Create New <u>F</u>ather
		    </button>
		    &nbsp;
		    <button type="button" id="detachHusb"
				class="button">
				Detach Father
		    </button>
		  <div style="clear: both;"></div>
		</div> <!-- end of Husband row -->
      </fieldset>
      <fieldset id="WifeSet" class="female">
		<legend class="labelSmall">Mother</legend>
		<div class="row" id="Wife">
		  <div class="column1">
		    <label class="column1" for="WifeGivenName">
				Name:
		    </label>
		    <input type="hidden" name="IDIRWife" id="IDIRWife"
						value="<?php print $idirwife; ?>">
		    <input type="text" name="WifeGivenName" id="WifeGivenName"
                    maxlength="120" class="white left column1"
                    value="<?php print $wifegivenname; ?>">
		    <input type="text" name="WifeSurname" id="WifeSurname"
                    maxlength="120" class="white left column2"
                    value="<?php print $wifesurname; ?>">
		    <input type="hidden" name="WifeBirthSD" id="WifeBirthSD"
						value="<?php print $wifebirthsd; ?>">
		  </div>
		  <div>
		    <button type="button" class="button" id="editWife">
				Edit Mother
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="WifeMarrSurnameRow">
		  <div class="column1">
		    <label class="column1" for="WifeMarrSurname">
				Married Surname:
		    </label>
	        <span class="left column1">&nbsp;</span>
		    <input type="text" name="WifeMarrSurname" id="WifeMarrSurname"
                    maxlength="255" class="white left column2"
                    value="<?php print $husbsurname; ?>">
		  </div>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="selectWifeRow">
		    <label class="column1" for="chooseWife">
				Actions:
		    </label>
		    <button type="button" id="chooseWife"
				class="button">
				Select Existing Mother
		    </button>
		    &nbsp;
		    <button type="button" id="createWife"
				class="button">
				Create New <u>M</u>other
		    </button>
		    &nbsp;
		    <button type="button" id="detachWife"
				class="button">
				Detach Mother
		    </button>
		  <div style="clear: both;"></div>
		</div> <!-- end of Wife row -->
      </fieldset>
      <fieldset id="EventSet" class="other">
		<legend class="labelSmall">Events</legend>
		<div class="row" id="MarriageRow">
		  <div class="column1">
		    <label class="column1" for="MarD">
				Married:
		    </label>
		    <input type="text" name="MarD" id="MarD"
						size="12" maxlength="100"
						class="white left" value="<?php print $marDate; ?>">
		    <span style="font-weight: bold;">at</span>
		    <input type="text" name="MarLoc" id="MarLoc"
						size="36" maxlength="255"
						class="white leftnc" value="<?php print $marLoc; ?>">
		  </div>
		  <div>
		    <button type="button" class="button" id="marriageDetails">
				Details
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div> <!-- end of Marriage row -->
      <!-- rows for other events are inserted here -->
<?php
		    $events	= $family->getEvents();
		    foreach($events as $ider => $event)
		    {			// loop through all events
				$idet		= $event->getIdet();
				$eventd		= $event->getDate();
				$eventloc	= $event->getLocation()->toString();
				$description	= $event->get('description');
				$type		= Event::$eventText[$idet];
?>
		<div class="row" id="EventRow<?php print $ider; ?>">
		  <div class="column1">
		    <label class="column1" for="Date<?php print $ider; ?>">
				<?php print $type; ?> <?php print $description; ?>
		    </label>
		    <input type="hidden"
						name="citType<?php print $ider; ?>"
						value="<?php print $idet; ?>">
		    <input type="text" size="12"
						name="Date<?php print $ider; ?>"
						class="white left"
						value="<?php print $eventd; ?>">
		    <span style="font-weight: bold;">at</span>
		    <input type="text" size="36"
						name="EventLoc<?php print $ider; ?>"
						class="white leftnc"
						value="<?php print $eventloc; ?>" >
		  </div>
		  <div>
		    <button type="button" class="button"
						id="EditEvent<?php print $ider; ?>">
				Details
		    </button>
		    &nbsp;
		    <button type="button" class="button"
						id="DelEvent<?php print $ider; ?>">
				Delete
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
<?php
		    }			// loop through all events
?>
		<div class="row" id="AddEventRow">
		  <label class="column1" for="AddEvent">
				Actions:
		  </label>
		  <button type="button" id="AddEvent"
				class="button">
				<u>A</u>dd Event
		  </button>
				&nbsp;
		  <button type="button" id="OrderEvents"
				class="button">
				    Order Events by <u>D</u>ate
		  </button>
		  <div style="clear: both;"></div>
		</div>
      </fieldset>
      <fieldset id="InformationSet" class="other">
		<legend class="labelSmall">Information</legend>
		<div class="row" id="IdmrRow">
		  <div class="column1">
		    <label class="column1" for="idmrshow">
				IDMR:
		    </label>
            <input type="text" name="idmrshow" id="idmrshow"
                    size="6" class="ina rightnc"
					readonly="readonly" value="<?php print $idmr; ?>">
		  </div>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="StatusRow">
		  <div class="column1">
		    <label class="column1" for="IDMS">
				    Status:
		    </label>
		    <select name="IDMS" id="IDMS" size="1" class="white left">
				<option value="1"<?php if ($idms == 1) print $selected;?>>
				    no special status
				</option>
				<option value="2"<?php if ($idms == 2) print $selected;?>>
				    Annulled
				</option>
				<option value="3"<?php if ($idms == 3) print $selected;?>>
				    Common Law
				</option>
				<option value="4"<?php if ($idms == 4) print $selected;?>>
				    Divorced
				</option>
				<option value="5"<?php if ($idms == 5) print $selected;?>>
				    Married
				</option>
				<option value="6"<?php if ($idms == 6) print $selected;?>>
				    Other
				</option>
				<option value="7"<?php if ($idms == 7) print $selected;?>>
				    Separated
				</option>
				<option value="8"<?php if ($idms == 8) print $selected;?>>
				    Unmarried
				</option>
				<option value="9"<?php if ($idms == 9) print $selected;?>>
				    Divorce
				</option>
				<option value="10"<?php if ($idms == 10) print $selected;?>>
				    Separation
				</option>
				<option value="11"<?php if ($idms == 11) print $selected;?>>
				    Private
				</option>
				<option value="12"<?php if ($idms == 12) print $selected;?>>
				    Partners
				</option>
				<option value="13"<?php if ($idms == 13) print $selected;?>>
				    Death of one spouse
				</option>
				<option value="14"<?php if ($idms == 14) print $selected;?>>
				    Single
				</option>
				<option value="15"<?php if ($idms == 15) print $selected;?>>
				    Friends
				</option>
		    </select>
		  </div>
		  <div style="clear: both;"></div>
		</div> <!-- end of Ending Status row -->

		<div class="row" id="NameRuleRow">
		  <label class="column1" for="MarriedNameRule">
				Name Rule:
		  </label>
		    <select name="MarriedNameRule" id="MarriedNameRule"
				size="1" class="white left">
				<option value="0"<?php if ($namerule == 0) print $selected;?>>
				    Don't Generate Married Names
				</option>
				<option value="1"<?php if ($idms == 1) print $selected;?>>
				    Replace Wife's Surname with Husband's Surname
				</option>
		    </select>
		  <div style="clear: both;"></div>
		</div> <!-- end of Name Rule row -->

		<div class="row" id="NotesRow">
		  <label class="column1" for="Notes">
				Notes:
		  </label>
    <!-- note that when initializing a <textarea>, unlike other tags
		 the space around the text value becomes part of the value
		 of the tag, so there can be no space characters between the 
		 opening and closing tags and the value of the field -->
		    <textarea name="Notes" id="Notes" cols="60" rows="4"
				><?php print $notes; ?></textarea>
		  <div style="clear: both;"></div>
		</div> <!-- end of Notes row -->
		<div class="row" id="NoteDetailsButtonRow">
		  <div class="column1">
		    <label class="column1" for="noteDetails">
		    </label>
		  </div>
		  <div>
		    <button type="button" class="button" id="noteDetails">
				Details
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div> <!-- end of NoteDetailsButton row -->
		<div class="row" id="InformationActionsRow">
		    <label class="column1" for="chooseHusb">
				Actions:
		    </label>
		    <button type="button" class="button" id="Pictures">
				Edit <u>P</u>ictures
		    </button>
		  <div style="clear: both;"></div>
		</div> <!-- end of Notes row -->
      </fieldset>
      <fieldset class="other" id="ChildrenSet">
		<legend class="labelSmall">Children</legend>
		<table class="details" id="children">
		  <thead>
		   <tr>
		    <th class="colhead" style="width: 188px;">
				&nbsp;&nbsp;&nbsp;Given&nbsp;&nbsp;&nbsp;
		    </th>
		    <th class="colhead" style="width: 116px;">
				&nbsp;&nbsp;Surname&nbsp;&nbsp;
		    </th>
		    <th class="colhead" style="width: 98px;">
				&nbsp;Birth&nbsp;
		    </th>
		    <th class="colhead" style="width: 98px;">
				&nbsp;Death&nbsp;
		    </th>
		    <th class="colhead" colspan="2">
				Actions
		    </th>
		   </tr>
		  </thead>
		  <tbody id="childrenBody">
<?php
		    $children	= $family->getChildren();
		    $rownum		= 0;
		    if (count($children) == 0)
		    {			// creating new family
				$rownum		= 0;
				$cIdcr		= '';
				$cIdir		= $person->getIdir();
				$gender		= $person->get('gender');
				$surname	= $person->get('surname');
				$surname	= str_replace('"','&quot;',$surname);
				$givenname	= $person->get('givenname');
				$givenname	= str_replace('"','&quot;',$givenname);
				$gender		= $person->get('gender');
				if ($gender == 0)
				    $genderclass	= 'male';
				else
				if ($gender == 1)
				    $genderclass	= 'female';
				else
				    $genderclass	= 'unknown';
				$evBirth	= $person->getBirthEvent(true);
				$birthd		= $evBirth->getDate();
				$birthsd	= $evBirth->get('eventsd');
				$evDeath	= $person->getDeathEvent(true);
				$deathd		= $evDeath->getDate();
				$deathsd	= $evDeath->get('eventsd');
?>
		<tr id="child<?php print $rownum; ?>">
		  <td class="name">
		    <input type="hidden"
						name="CIdir<?php print $rownum; ?>" 
						id="CIdir<?php print $rownum; ?>"
						value="<?php print $cIdir; ?>">
		    <input type="hidden"
						name="CIdcr<?php print $rownum; ?>" 
						id="CIdcr<?php print $rownum; ?>"
						value="<?php print $cIdcr; ?>">
		    <input type="hidden"
						name="CGender<?php print $rownum; ?>" 
						id="CGender<?php print $rownum; ?>"
						value="<?php print $gender; ?>">
		    <input class="<?php print $genderclass; ?>"
						name="CGiven<?php print $rownum; ?>" 
						id="CGiven<?php print $rownum; ?>"
						value="<?php print $givenname; ?>" 
						type="text" size="18" maxlength="120">
		  </td>
		  <td class="name">
		    <input class="<?php print $genderclass; ?>"
						name="CSurname<?php print $rownum; ?>"
						id="CSurname<?php print $rownum; ?>" 
						value="<?php print $surname; ?>"
						type="text" size="10" maxlength="120">
		  </td>
		  <td class="name">
		    <input class="white left"
						name="Cbirth<?php print $rownum; ?>"
						id="Cbirth<?php print $rownum; ?>" 
						value="<?php print $birthd; ?>" 
						type="text" size="8" maxlength="100">
		    <input name="Cbirthsd<?php print $rownum; ?>"
						id="Cbirthsd<?php print $rownum; ?>" 
						type="hidden" 
						value="<?php print $birthsd; ?>">
		  </td>
		  <td class="name">
		    <input class="white left"
						name="Cdeath<?php print $rownum; ?>"
						id="Cdeath<?php print $rownum; ?>" 
						value="<?php print $deathd; ?>"
						type="text" size="8" maxlength="100">
		  </td>
		  <td>
		    <button type="button" class="button"
						id="editChild<?php print $rownum; ?>">
				Edit Child
		    </button>
		  </td>
		  <td>
		    <button type="button" class="button"
						id="detChild<?php print $rownum; ?>">
				Detach&nbsp;Child
		    </button>
		  </td>
		</tr>
<?php
		    }			// creating new family
		    else
		    foreach($children as $idcr => $child)
		    {			// loop through existing children
				$cIdcr		= $child->getIdcr();
				$cPerson		= $child->getPerson();
				$cIdir		= $cPerson->getIdir();
				$gender		= $cPerson->get('gender');
				$surname	= $cPerson->get('surname');
				$surname	= str_replace('"','&quot;',$surname);
				$givenname	= $cPerson->get('givenname');
				$givenname	= str_replace('"','&quot;',$givenname);
				$gender		= $cPerson->get('gender');
				if ($gender == 0)
				    $genderclass	= 'male';
				else
				if ($gender == 1)
				    $genderclass	= 'female';
				else
				    $genderclass	= 'unknown';
				$evBirth	= $cPerson->getBirthEvent(true);
				$birthd		= $evBirth->getDate();
				$birthsd	= $evBirth->get('eventsd');
				$evDeath	= $cPerson->getDeathEvent(true);
				$deathd		= $evDeath->getDate();
				$deathsd	= $evDeath->get('eventsd');
?>
		<tr id="child<?php print $rownum; ?>">
		  <td class="name">
		    <input type="hidden"
						name="CIdir<?php print $rownum; ?>" 
						id="CIdir<?php print $rownum; ?>"
						value="<?php print $cIdir; ?>">
		    <input type="hidden"
						name="CIdcr<?php print $rownum; ?>" 
						id="CIdcr<?php print $rownum; ?>"
						value="<?php print $cIdcr; ?>">
		    <input type="hidden"
						name="CGender<?php print $rownum; ?>" 
						id="CGender<?php print $rownum; ?>"
						value="<?php print $gender; ?>">
		    <input class="<?php print $genderclass; ?>"
						name="CGiven<?php print $rownum; ?>" 
						id="CGiven<?php print $rownum; ?>"
						value="<?php print $givenname; ?>" 
						type="text" size="18" maxlength="120">
		  </td>
		  <td class="name">
		    <input class="<?php print $genderclass; ?>"
						name="CSurname<?php print $rownum; ?>"
						id="CSurname<?php print $rownum; ?>" 
						value="<?php print $surname; ?>"
						type="text" size="10" maxlength="120">
		  </td>
		  <td class="name">
		    <input class="white left"
						name="Cbirth<?php print $rownum; ?>"
						id="Cbirth<?php print $rownum; ?>" 
						value="<?php print $birthd; ?>" 
						type="text" size="8" maxlength="100">
		    <input name="Cbirthsd<?php print $rownum; ?>"
						id="Cbirthsd<?php print $rownum; ?>" 
						type="hidden" 
						value="<?php print $birthsd; ?>">
		  </td>
		  <td class="name">
		    <input class="white left"
						name="Cdeath<?php print $rownum; ?>"
						id="Cdeath<?php print $rownum; ?>" 
						value="<?php print $deathd; ?>"
						type="text" size="8" maxlength="100">
		  </td>
		  <td>
		    <button type="button" class="button"
						id="editChild<?php print $rownum; ?>">
				Edit Child
		    </button>
		  </td>
		  <td>
		    <button type="button" class="button"
						id="detChild<?php print $rownum; ?>">
				Detach&nbsp;Child
		    </button>
		  </td>
		</tr>
<?php
				$rownum++;
		    }			// loop through children
?>
		  </tbody>
		</table> <!-- id="children" -->
	    <input type="hidden"
		    	name="CIdir99" id="CIdir99"
			    value="-1">
		<div class="row">
		    <label class="column1" for="chooseHusb">
				Actions:
		    </label>
		    <button type="button" class="button" id="addChild">
				    Add <u>E</u>xisting Child
		    </button>
		    &nbsp;
		    <button type="button" class="button" id="addNewChild">
				    Add <u>N</u>ew&nbsp;Child
		    </button>
		    &nbsp;
		    <button type="button" class="button" id="orderChildren"
				style="width: 18em;">
		      <u>O</u>rder Children by Birth Date
		    </button>
		</div>
      </fieldset>
    <p id="MarrButtonLine">
<?php
		    if ($submit)
		    {			// debugging, use submit
?>
      <button type="submit" id="Submit">
		    Submit Family
      </button>
      &nbsp;
      <button type="button" class="button" id="update">
		    <u>U</u>pdate Family
      </button>
<?php
		    }			// debugging, use submit
		    else
		    {			// normal, use AJAX
?>
      <button type="button" class="button" id="update">
		    <u>U</u>pdate Family
      </button>
<?php
		    }			// normal, use AJAX
		}			// family chosen to display
?>
    &nbsp;
      <button type="button" class="button" id="Finish">
		    <u>C</u>lose
      </button>
    </p>
  </form> <!-- name="famForm" -->
</div> <!-- id="bodydiv" -->
<?php
    }		// individual found

    dialogBot();
?>
<div class="balloon" id="HelpPref">
<p>Click on the checkbox to make the specified marriage the preferred
marriage.
</p>
</div>
<div class="balloon" id="HelpEdit">
<p>Clicking on this button pops up a dialog to edit the set of parents
summarized on this row of the dialogue.
</p>
</div>
<div class="balloon" id="HelpDelete">
<p>Delete the set of parents described by this row.
</p>
</div>
<div class="balloon" id="HelpAdd">
<p>Clicking on this button pops up a dialog to add a new set of parents to
this child.
</p>
</div>
<div class="balloon" id="HelpFinish">
<p>Clicking on this button closes the dialog.  The changes to the database
have already been made.
</p>
</div>
<div class="balloon" id="Helpidmr">
<p>This read-only field displays the internal numeric identifier of
this relationship.
</p>
</div>
<div class="balloon" id="HelpHusbGivenName">
<p>This displays the given names of the father.
If you alter this field it changes the given name of the individual.
</p>
</div>
<div class="balloon" id="HelpHusbSurname">
<p>This displays the family name of the father. 
If you alter this field it changes the family name of the individual.
</p>
</div>
<div class="balloon" id="HelpHusbMarrSurname">
<p>This displays the family name by which the father was known during this
marriage.  If the traditional rule in which the Husband did
not change his surname on marriage is in effect this is a read-only field.
</p>
</div>
<div class="balloon" id="HelpeditHusb">
<p>Click on this button to open a dialog to edit the detailed information
about the father in this set of parents.
</p>
</div>
<div class="balloon" id="HelpWifeGivenName">
<p>This displays the given names of the mother. 
If you alter this field it changes the given name of the individual.
</p>
</div>
<div class="balloon" id="HelpWifeSurname">
<p>This displays the family name of the mother. 
If you alter this field it changes the family name of the individual.
</p>
</div>
<div class="balloon" id="HelpWifeMarrSurname">
<p>This displays the family name by which the mother was known during this
marriage.  If the traditional rule in which the Wife took
her husband's surname on marriage is in effect this is a read-only field.
</p>
</div>
<div class="balloon" id="HelpeditWife">
<p>Click on this button to open a dialog to edit the detailed information
about the mother in this set of parents.
</p>
</div>
<div class="balloon" id="HelpchooseHusb">
<p>Selecting this button pops up a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the father
in this set of parents.
</p>
</div>
<div class="balloon" id="HelpcreateHusb">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to create a
new individual in the family tree to be the father in this set of parents.
</p>
</div>
<div class="balloon" id="HelpdetachHusb">
<p>Selecting this button detaches the currently assigned father from this
marriage.  It is not necessary to do this before selecting or creating
a new father.
</p>
</div>
<div class="balloon" id="HelpchooseWife">
<p>Selecting this button pops up a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the mother
in this set of parents.
</p>
</div>
<div class="balloon" id="HelpcreateWife">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to create a
new individual in the family tree to be the mother in this set of parents.
</p>
</div>
<div class="balloon" id="HelpdetachWife">
<p>Selecting this button detaches the currently assigned mother from this
marriage.  It is not necessary to do this before selecting or creating
a new mother.
</p>
</div>
<div class="balloon" id="HelpMarD">
<p>Supply the date of the marriage.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpMarLoc">
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
<div class="balloon" id="HelpmarriageDetails">
<p>Clicking on this button opens a dialog that permits you to add further
details about the marriage and to specify source citations for the fact.
</div>
<div class="balloon" id="HelpMarEndD">
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
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpSealD">
<p>Supply the date that the partners were sealed to each other at a
Church of Latter Day Saints temple.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpSealLoc">
<p>This read-only field contains the name of the Church of Latter Day
Saints temple where the partners were sealed to each other. 
To select a different temple click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpDate">
<p>This read-only field displays the date of the family event.
To modify the date, or any other information about this event, click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpLoc">
<p>This read-only field displays the location of the event.
To modify the location, or any other information about this event, click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpAddEvent">
<p>Selecting this button opens a
<a href="editEventHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about a new event being added to the marriage.
</p>
</div>
<div class="balloon" id="HelpOrderEvents">
<p>Selecting this button reorders the events of this marriage by their dates.
</p>
</div>
<div class="balloon" id="HelpEditEvent">
<p>Selecting this button opens a
<a href="editEventHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about the event summarized in this line of the form.
In particular you may add source citations for the event.
</p>
</div>
<div class="balloon" id="HelpDelEvent">
<p>Selecting this button deletes
the event summarized in this line of the form.
</p>
</div>
<div class="balloon" id="HelpIDMS">
<p>This selection list permits you to specify the ending or current
status of this marriage.
</p>
</div>
<div class="balloon" id="HelpMarriedNameRule">
<p>This selection list permits you to specify whether or not the mother
took her husband's surname as a result of the marriage.  The default
is the traditional practice.
</p>
</div>
<div class="balloon" id="HelpNotes">
<p>Supply extended textual notes about the marriage.
Note that the text may include HTML markup which will appear in the
resulting web page.  For example placing the tags "&lt;b&gt;" and "&lt;/b&gt;"
around text makes it bold.  Placing the tags "&lt;a href="Person.php?idir=9999 class="male"&gt;" and "&lt;/a&gt;" around a name, where "9999" is replaced
by the appropriate numeric key value and the appropriate gender is specified
for "class" highlights the name as a hyperlink to the web page.  You can use
this technique to connect the names of witnesses or other participants in the
marriage to their records in the family tree.
</p>
<p>Although you might be tempted to include the text of a newspaper notice
about the marriage in this field, it is recommended that you put that
text into the citation text field instead.
</p>
</div>
<div class="balloon" id="HelpNotMarried">
<p>This checkbox is used to indicate that the couple is known to have never
been married.  You may remove this fact either by clicking on the 
<span class="button">Delete Event</span> button at the end of the line
containing the checkbox, or by clicking on the checkbox to change its state.
In the latter case the checkbox remains visible in the dialog until some
other action causes the dialog to be refreshed.
</p>
</div>
<div class="balloon" id="HelpNoChildren">
<p>This checkbox is used to indicate that the couple is known to have never
had children.  You may remove this fact either by clicking on the 
<span class="button">Delete Event</span> button at the end of the line
containing the checkbox, or by clicking on the checkbox to change its state.
In the latter case the checkbox remains visible in the dialog until some
other action causes the dialog to be refreshed.
</p>
</div>
<div class="balloon" id="HelpnoteDetails">
<p>Click on this button to add additional information about the marriage notes.
In particular you may add source citations for the notes.
</p>
</div>
<div class="balloon" id="HelpnoChildren">
<p>Select this checkbox if you know that this family had no children.
</p>
</div>
</body>
<div class="balloon" id="HelpnoChildDetails">
<p>Click on this button to add additional information about the fact that this
family has no children.
In particular you may add source citations for the fact.
</p>
</div>
<div class="balloon" id="HelpaddChild">
<p>Selecting this button, or using the keyboard short-cut alt-E, opens a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
to choose an existing individual
in the family tree database to add as a child of this family.
</p>
</div>
<div class="balloon" id="HelpaddNewChild">
<p>Selecting this button, or using the keyboard short-cut alt-N, opens a 
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
to create a new individual in the
family tree database that is added as a child of this family.
</p>
</div>
<div class="balloon" id="HelpCGiven">
<p>This displays the given names of a child.
If you alter this field it changes the given name of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCSurname">
<p>This displays the family name of a child.
This defaults to the family name of the father.
If you alter this field it changes the family name of the child.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCbirth">
<p>This input field displays the birth date of a child.
If you alter this field it changes the birth date of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCdeath">
<p>This input field displays the death date of a child.
If you alter this field it changes the death date of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="Helpupdate">
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class="balloon" id="HelporderChildren">
<p>Selecting this button, or using the keyboard short-cut alt-O, 
reorders the children of this marriage by their
dates of birth.
</p>
</div>
<div class="balloon" id="HelpeditChild">
<p>Selecting this button opens a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about the child summarized in this line of the form.
</p>
</div>
<div class="balloon" id="HelpdetChild">
<p>Selecting this button detaches the child summarized in this line of the
form from this family.  You can then go to another family and attach the
child there.
</p>
</div>
<div class="balloon" id="HelpPictures">
<p>Selecting this button opens a dialog
to edit the set of pictures associated with this family.
</p>
</div>
<div id="loading" class="popup">
Loading...
</div>
<div class="hidden" id="templates">
  <table id="templateRows">
    <!--
     *	layout of the table row to display a summary of a marriage
     *  in the table at the top of the dialog.
     *	Putting the layout here permits more user
     *	customization, including support for alternate languages.
     *	This row layout should match the rows in <tbody id="marriageListBody">
    -->
		<tr id="marriage$idmr">
		  <td id="mdate$idmr">
				$mdate
		  </td>
		  <td>
		    <a href="Person.php?id=$fatherid"
						class="male" id="fathername$idmr">
				$fatherGiven $fatherSurname
		    </a>
		  </td>
		  <td>
		    <a href="Person.php?id=$motherid"
						class="female" id="mothername$idmr">
				$motherGiven $motherSurname
		    </a>
		  </td>
		  <td class="center">
		    <input type="checkbox" name="Pref$idmr">
		  </td>
		  <td>
		    <button type="button" id="Edit$idmr">
				Edit Parents
		    </button>
		  </td>
		  <td>
		    <button type="button" id="Delete$idmr">
				Delete Parents
		    </button>
		  </td>
		</tr>
    <!-- end of template for marriage row -->

    <!--
     *	layout of the table row to display a single child of this marriage
     *  each row is added by javascript when the XML response to the AJAX
     *	request is received.  Putting the layout here permits more user
     *	customization, including support for alternate languages.
    -->
    <tr id="child$rownum">
		<td class="name">
		    <input class="$gender" name="CGiven$rownum" value="$givenname"
						type="text" size="18"
						maxlength="120">
		    <input type="hidden" name="CIdir$rownum" id="CIdir$rownum"
						value="$idir">
		    <input type="hidden" name="CIdcr$rownum" id="CIdcr$rownum"
						value="$idcr">
		    <input type="hidden" name="CGender$rownum" id="CGender$rownum"
						value="$sex">
		</td>
		<td class="name">
		    <input class="$gender" name="CSurname$rownum" value="$surname"
						type="text" size="10"
						maxlength="120">
		</td>
		<td class="name">
		    <input class="white left" name="Cbirth$rownum" value="$birthd"
						type="text" size="8"
						maxlength="100">
		    <input name="Cbirthsd$rownum" type="hidden" value="$birthsd">
		</td>
		<td class="name">
		    <input class="white left" name="Cdeath$rownum" value="$deathd"
						type="text" size="8"
						maxlength="100">
		</td>
		<td>
		    <button type="button" class="button" id="editChild$rownum">
				Edit Child
		    </button>
		</td>
		<td>
		    <button type="button" class="button" id="detChild$rownum">
				Detach Child
		    </button>
		</td>
    </tr>
    <!-- end of template for child row -->
    </table>

    <!-- template for sealed to spouse event row -->
		<div class="row" id="SealedRow$temp">
		  <div class="column1">
		    <label class="column1" for="SealD">
				Sealed&nbsp;to&nbsp;Spouse (LDS):
		    </label>
		    <input type="text" name="SealD" id="SealD"
						size="12" maxlength="100"
						class="white left" value="$eventd">
		    <span style="font-weight: bold;">at</span>
		    <select name="IDTRSeal" id="IDTRSeal"
						size="1" class="white left">
<?php
    $temples	= new RecordSet('Temples');
    foreach($temples as $idtr => $temple)
    {			// loop through all temples
?>
				<option value="<?php print $idtr; ?>">
				    <?php print $temple->getName(); ?>
				</option>
<?php
    }			// loop through all temples
?>
		    </select>
		  </div>
		  <div>
		      <button type="button" class="button"
						id="EditIEvent18$temp">
				Details
		      </button>
		      &nbsp;
		      <button type="button" class="button" id="DelIEvent18$temp">
				Delete
		      </button>
		    </div>
		    <div style="clear: both;"></div>
		</div>
    <!-- end of template for sealed to spouse event row -->

    <!-- template for marriage ended event row -->
		<div class="row" id="EndedRow$temp">
		  <div class="column1">
		    <label class="column1" for="MrEndD">
				Marriage&nbsp;Ended:
		    </label>
		    <input type="text" name="MarEndD" id="MarEndD"
						size="12" maxlength="100"
						class="white left" value="$eventd">
		  </div>
		  <div>
		    <button type="button" class="button"
						id="EditIEvent24$temp">
				Details
		    </button>
		    &nbsp;
		    <button type="button" class="button" id="DelIEvent24$temp">
				Delete
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
    <!-- end of template for mariage ended event row -->

    <!-- template for general marriage event row -->
		<div class="row" id="EventRow$ider">
		  <div class="column1">
		    <label class="column1" for="Date$ider">
				$type $description
		    </label>
		    <input type="hidden"
						name="citType$ider"
						value="$idet">
		    <input type="text" size="12"
						name="Date$ider"
						class="white left"
						value="$eventd">
		    <span style="font-weight: bold;">at</span>
		    <input type="text" size="36"
						name="EventLoc$ider"
						class="white leftnc"
						value="$eventloc" >
		  </div>
		  <div>
		    <button type="button" class="button"
						id="EditEvent$ider">
				Details
		    </button>
		    &nbsp;
		    <button type="button" class="button" id="DelEvent$ider">
				Delete
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
    <!-- end of template for marriage event row -->

    <!-- template for never married fact row -->
		<div class="row" id="NotMarriedRow$temp">
		  <div class="column1">
		    <label class="column1" for="NotMarried">
				Not Married:
		    </label>
		    <input type="checkbox" name="NotMarried$temp" id="NotMarried$temp"
				checked="checked">
		  </div>
		  <div>
		    <button type="button" class="button" id="neverMarriedDetails$temp">
				Details
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
    <!-- end of template for never married fact row -->

    <!-- template for no children fact row -->
		<div class="row" id="NoChildrenRow$temp">
		  <div class="column1">
		    <label class="column1" for="NoChildren">
				No Children:
		    </label>
		    <input type="checkbox" name="NoChildren$temp" id="NoChildren$temp"
						checked="checked">
		  </div>
		  <div>
		    <button type="button" class="button" id="noChildrenDetails$temp">
				Details
		    </button>
		  </div>
		  <div style="clear: both;"></div>
		</div>
    <!-- end of template for no children fact row -->

<?php
    include $document_root . '/templates/LocationDialogs.html';
?>

  <!-- template for confirming the deletion of an event-->
  <form name="ClrInd$template" id="ClrInd$template">
    <p class="message">$msg</p>
    <p>
      <button type="button" id="confirmClear$type">
		OK
      </button>
      <input type="hidden" id="formname$type" name="formname$type"
				value="$formname">
		&nbsp;
      <button type="button" id="cancelDelete$type">
		Cancel
      </button>
    </p>
  </form>

  <!-- template for warning child already being edited -->
  <form name="AlreadyEditing$template" id="AlreadyEditing$template">
    <p class="message">$givenname $surname is already being edited</p>
    <p>
      <button type="button" id="justClose$template">
		OK
      </button>
    </p>
  </form>

<?php
    foreach(Event::$eventText as $idet => $text)
    {			// make the event texts available to Javascript
?>
  <span id="EventText<?php print $idet; ?>"><?php print $text; ?></span>
<?php
    }			// make the event texts available to Javascript
?>
</div> <!-- id="templates" -->
</body>
</html>
