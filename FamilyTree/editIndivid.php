<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editIndivid.php														*
 *																		*
 *  Display a web page to support editing details of an particular		*
 *  record from the Legacy table of individuals.						*
 *																		*
 *  URI Parameters:														*
 *		idir			unique numeric key of the instance of			*
 *						LegacyIndiv to be displayed.  If this is		*
 *						omitted or zero then a new instance of			*
 *						LegacyIndiv is created.							*
 *						For backwards compatibility the script also		*
 *						accepts 'id'.									*
 *		treeName		the section of the database to create the		*
 *						individual in if idir=0							*
 *																		*
 *		The following parameters may be specified on an invocation of	*
 *		this page using Javascript window.open to request the page to	*
 *		update the values of specific fields in the invoking page when	*
 *		the user submits an update.										*
 *																		*
 *		setidir			field name in which to place the unique numeric	*
 *						key of the new instance of LegacyIndiv			*
 *		Surname			field in which to place the surname of			*
 *						the new instance of LegacyIndiv					*
 *		GivenName		field in which to place the given names			*
 *						of the new instance of LegacyIndiv				*
 *		Prefix			field in which to place the name prefix			*
 *						of the new instance of LegacyIndiv				*
 *		NameNote		field in which to place the name note			*
 *						of the new instance of LegacyIndiv				*
 *		Gender			field in which to place the gender				*
 *						of the new instance of LegacyIndiv				*
 *		BirthDate		field in which to place the birth date			*
 *						of the new instance of LegacyIndiv				*
 *		BirthLocation	field in which to place the birth location		*
 *						of the new instance of LegacyIndiv				*
 *		ChrisDate		field in which to place the christening date	*
 *						of the new instance of LegacyIndiv				*
 *		ChrisLocation	field in which to place the christening location*
 *						of the new instance of LegacyIndiv				*
 *		DeathDate		field in which to place the death date			*
 *						of the new instance of LegacyIndiv				*
 *		DeathLocation	field in which to place the death location		*
 *						of the new instance of LegacyIndiv				*
 *		BuriedDate		field in which to place the burial date			*
 *						of the new instance of LegacyIndiv				*
 *		BuriedLocation	field in which to place the burial location		*
 *						of the new instance of LegacyIndiv				*
 *		UserRef			field in which to place the user reference		*
 *						value of the new instance of LegacyIndiv		*
 *		AncestralRef	field in which to place the ancestral			*
 *						reference value of the new instance of			*
 *						LegacyIndiv										*
 *		DeathCause		field in which to place the death cause			*
 *						of the new instance of LegacyIndiv				*
 *		... or, in general, any field name in this page.				*
 *																		*
 *		Furthermore a parameter with a name starting with 'init' can be	*
 *		used to initialize the value of a field matching the remainder	*
 *		of the parameter name if a new individual is being created.		*
 *		Note that the field name portion of these parameters is			*
 *		case-insensitive.  												*
 *		In particular:													*
 *																		*
 *		initSurname		set initial value for the surname				*
 *		initGivenName	set initial value for the given names			*
 *		initPrefix		set initial value for the name prefix			*
 *		initNameNote	set initial value for the name note				*
 *		initGender		set initial value for the gender				*
 *		initBirthDate	set initial value for the birth date			*
 *		initBirthLocation set initial value for the birth location		*
 *		initChrisDate	set initial value for the christening date		*
 *		initChrisLocation set initial value for the christening location*
 *		initDeathDate	set initial value for the death date			*
 *		initDeathLocation set initial value for the death location		*
 *		initBuriedDate	set initial value for the burial date			*
 *		initBuriedLocation set initial value for the burial location	*
 *		initUserRef		set initial value for the user					*
 *		initAncestralRef set initial value for the ancestral			*
 *		initDeathCause	set initial value for the death cause			*
 *																		*
 *  When this is invoked to create a child the following parameter must	*
 *  be passed:															*
 *																		*
 *		parentsIdmr		IDMR of the record for the parent's relationship*
 *																		*
 *  When this is invoked to edit an existing child the following		*
 *  parameter must be passed:											*
 *																		*
 *		idcr			IDCR of the record in tblCR that connects the	*
 *						child to a family								*
 *																		*
 *  When this is invoked to update a parent in the family the following	*
 *  parameter is passed to identify the role in the family:				*
 *																		*
 *		rowid			'Husband', 'Wife', 'Father', 'Mother'			*
 *																		*
 *  The following parameters may also be passed to supply information	*
 *  that may not yet have been written to the database because			*
 *  the family record is in the process of being created:				*
 *																		*
 *		initSurname		initial surname for the child					*
 *		fathGivenName	father's given name								*
 *		fathSurname		father's surname								*
 *		mothGivenName	mother's given name								*
 *		mothSurname		mother's surname								*
 *																		*
 *  History:															*
 *		2010/08/11		Correct error in mailto: subject line, and add	*
 *						birth date and death date into title.			*
 *		2010/08/11		encode field values with htmlspecialchars		*
 *		2010/08/12		if invoked from another web page, apply			*
 *						changes to that web page as a side effect of	*
 *						submitting the update.  Also support			*
 *						initializing values in the form.				*
 *		2010/08/28		Add Edit Details on name to permit citations	*
 *						and to move name note off main page.			*
 *						Add Edit Details on death cause.				*
 *		2010/09/20		remove onsubmit= parameter from form			*
 *						it is supplied by editIndivid.js::loadEdit		*
 *		2010/09/27		Support standard idir= parameter				*
 *		2010/10/01		Add hyperlinks for IE < 8						*
 *		2010/10/10		Evaluate locations at top of page to handle		*
 *						error message emitted by LegacyLocation			*
 *						constructor										*
 *		2010/10/21		use RecOwners class to validate access			*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/30		move $browser object to common.inc				*
 *		2010/10/31		do not expand page if user is not owner of		*
 *						record											*
 *		2010/11/10		add help link									*
 *		2010/11/14		move name prefix and title to name event		*
 *		2010/11/27		add support for medical and research notes		*
 *						improve separation of HTML and PHP				*
 *						use editEvent.php in place of obsolete			*
 *						editEventIndiv.php								*
 *		2010/11/29		correct initialization of $given				*
 *						improve title for case of adding a child		*
 *		2010/12/09		add name on submit button and initially disable	*
 *						add balloon help for all buttons and input fields*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *						escape HTML special chars in title				*
 *		2010/12/18		The link to the nominal index in the header and	*
 *						trailer breadcrumbs points at current name		*
 *		2010/12/20		handle exception thrown by new LegacyIndiv		*
 *						handle exception thrown by new LegacyFamily		*
 *						handle exception thrown by new LegacyLocation	*
 *						add button to delete individual if a candidate	*
 *		2010/12/24		pass parentsIdmr to updateIndivid.php			*
 *						reduce padding between cells to compress form	*
 *		2010/12/26		add support for modifying field IDMRPref in		*
 *						response to request from editMarriages.php		*
 *		2010/12/29		if IDMRPref not set, default to first marriage	*
 *						ensure value of 'idar' is numeric				*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/02/23		use editEvent.php to edit general notes			*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *						visual support for Alt-U						*
 *		2011/03/06		Combine label text into edit button and			*
 *						add Alt-... support for each button				*
 *		2011/04/22		fix IE7 support									*
 *		2011/05/25		add button for editting pictures				*
 *		2011/06/24		correct handling of children					*
 *		2011/08/22		if no marriages set text of Edit Marriages		*
 *						button to Add Spouse or Childa and change		*
 *						standard text to Edit Families with Alt-F		*
 *		2011/08/24		if no parents set text of Edit Parents button	*
 *						to Add Parents									*
 *		2011/10/23		add help for additional fields and buttons		*
 *		2012/01/07		add ability to explicitly supply father's and	*
 *						mother's name when adding a child				*
 *		2012/01/13		change class names								*
 *		2012/01/23		display loading indicator while waiting for		*
 *						response to changes in a location field			*
 *		2012/02/25		id= rather than name= used to identify buttons	*
 *						so they will not be passed to the action		*
 *						script by IE.									*
 *						help text added for some hidden fields			*
 *						add support for LDS events in main record		*
 *						add support for list of tblER events			*
 *						add event button moved to after list of events	*
 *						order events by date button added				*
 *						event type encoded in id value of buttons		*
 *						IDER encoded in id value of buttons and name	*
 *						value of input fields for events in tblER		*
 *						cittype encoded in id value of buttons and		*
 *						name value										*
 *						of input fields for events in main record		*
 *						support all documented init fields				*
 *		2012/05/06		explicitly set class for input text fields		*
 *		2012/05/12		remove display of Soundex code					*
 *		2012/05/31		defer adding child to family until submit		*
 *		2012/08/01		support user modification of events recorded in	*
 *						Event instances on the main dialog				*
 *		2012/08/12		add ability to edit sealed to parents event		*
 *		2012/08/27		correct handling of location selection list		*
 *						on individual events with description			*
 *		2012/09/19		use idcr parameter for editing existing child	*
 *		2012/09/24		enforce maximum lengths for text fields to		*
 *						match database definition						*
 *		2012/09/28		do not set or display ID and IDIR for new record*
 *						the database record is not created until the	*
 *						update is submitted								*
 *		2012/10/14		expand death cause to 255 characters			*
 *		2012/11/09		add customizable events using javascript rather	*
 *						than redisplaying the entire page				*
 *		2012/11/12		no longer need to disable submit button			*
 *		2013/01/17		make gender readonly if pre-selected			*
 *		2013/02/13		cannot issue grant for a new individual			*
 *		2013/02/15		add help bubble for Delete button				*
 *		2013/03/10		add checkbox for Private flag					*
 *		2013/03/12		move rarely used fields to the bottom of		*
 *						the form										*
 *						add support for ancestor and					*
 *						descendant interest								*
 *						color code gender								*
 *						standardize appearance and implementation		*
 *						of selection lists								*
 *		2013/03/14		LegacyLocation constructor no longer does save	*
 *		2013/04/20		add illegitimate relationship of child			*
 *		2013/05/17		shrink the form vertically by using				*
 *						<button class="button">							*
 *		2013/05/26		use dialog in place of alert for new			*
 *						location name									*
 *		2013/05/28		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/05/30		add help text for SealingDate and				*
 *						SealingTemple									*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *						include all owners in author email				*
 *		2013/06/22		move hidden IDAR field							*
 *		2013/08/14		include title and suffix in title of page		*
 *		2013/09/13		do not invoke any methods of individual if		*
 *						errors											*
 *						avoid doing field references more than once		*
 *		2013/10/15		do not display page header and footer if		*
 *						invoked to add or edit a member of a family.	*
 *						process init overrides even for existing		*
 *						individual										*
 *		2013/10/19		correct name parameter to LegacyIndex.php		*
 *		2013/10/25		incorrect field name used when setting initial	*
 *						values for event dates and temples				*
 *		2013/11/23		handle lack of database server connection		*
 *						gracefully										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/11		use CSS to layout form							*
 *		2014/02/17		identify <label> of individual event row		*
 *						to permit editEvent dialog to update it			*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *						add for= attribute on all <label> tags			*
 *		2014/03/06		label class name changed to column1				*
 *		2014/03/10		default to not displaying cause of death in		*
 *						this dialogue, now that it can be editted in	*
 *						the death event detail dialogue					*
 *		2014/03/14		use Event::getEvents							*
 *						remove references to deprecated getNumParents	*
 *						and getNumMarriades								*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/06		move textual interpretation of IDET here from	*
 *						Event class to support I18N						*
 *		2014/06/15		support for popupAlert moved to common code		*
 *		2014/09/20		several validation messages reduced to warnings	*
 *		2014/09/26		pass debug flag to update script				*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/10/24		handle preferred events that are implemented	*
 *						by entries in tblER rather than fields in tblIR	*
 *		2014/11/02		fixup for multiple preferred birth events		*
 *		2014/11/08		initBirthDate, etc don't work because they		*
 *						init fields in old location						*
 *		2014/11/15		incorrect handling of invalid IDIR value		*
 *		2014/11/18		init parms for events do not work if event in	*
 *						tblER											*
 *		2014/11/29		do not reinitialize global variables set by		*
 *						common.inc										*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/04		add fields to each event row to hold:			*
 *						 o checkbox for preferred status				*
 *						 o the IDER value (which may be zero)			*
 *						 o the IDET value								*
 *						 o the citation type							*
 *						 o the order									*
 *						 o the sorted form of the event date			*
 *						 o the event changed indicator					*
 *						Use the same element names for all fields in	*
 *						each event row that are not required to be		*
 *						special to assist the browser to perform		*
 *			            value autoexpansion and popup help, and to	    * 
 *						permit the Javascript code to assign the		*
 *						abbreviation expansions							*
 *						change labels on tblER events to match labels	*
 *						on old style fixed events						*
 *						all events are moved to tblER on save			*
 *		2014/12/26		added row was not the same layout as existing	*
 *						rows											*
 *		2015/01/03		updating event added row when it shouldn't		*
 *		2015/01/09		wrong event order set for birth event when		*
 *						debug not specified								*
 *		2015/01/10		use CSS to style width of button columns		*
 *						some form elements did not have id= values		*
 *		2015/01/15		move table of event texts to HTML to make		*
 *						available to Javascript.  Get English version	*
 *						of event texts from class Event					*
 *		2015/01/18		display grant dialog in right half of window	*
 *						add drop down menu for searching other tables	*
 *						and Ancestry.ca									*
 *		2015/01/27		move Grant button up to the line with all		*
 *						other global buttons							*
 *		2015/02/06		highlight place name in previously unknown		*
 *						location popup									*
 *		2015/03/06		when invoked to add a new child to a family		*
 *						create the required instance of LegacyChild		*
 *						so the user can set the relationship to parents	*
 *						fields											*
 *		2015/03/16		assign IDIR value for new individual			*
 *						initialize IDTR values in new event records		*
 *		2015/03/25		do not flag parentsIdmr=0 as an error			*
 *						top of hierarchy is genealogy.php				*
 *		2015/04/27		initXxxxxDate changes were not being done		*
 *						because event objects were replaced and			*
 *						changed flag was not set						*
 *		2015/05/27		support deleting address						*
 *		2015/06/23		permit longer event description.  This is		*
 *						mostly to tolerate HTML in the value.			*
 *		2015/06/29		ensure new init values for events visible		*
 *						when invoked as child of a family dialog		*
 *						If the pre-defined events are already in		*
 *						tblER $person->getXxxxEvent() creates a			*
 *						different instance than $person->getEvents		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		support treeName database subdivision			*
 *						permit Surname field to contain lower case		*
 *						components such as 'de' or 'van'.				*
 *		2016/02/06		use showTrace									*
 *		2017/01/03		ensure buried event after death event			*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/23		make IDER the first field in event description	*
 *		2017/08/29		ensure special row names for preferred events	*
 *						so the rows are not deleted when the associated	*
 *						event is deleted, just the contents				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/23		use selection list for temples					*
 *		2017/09/24		highlight dates flagged by class LegacyDate		*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2018/02/03		allow more flexibility in value of initGender	*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *      2018/12/18      ensure birth event displayed first              *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function isSelected													*
 *																		*
 *  This method emits the HTML selected attribute for an <option> tag	*
 *  within a <select> if the value of the variable matches the supplied	*
 *  value.																*
 *																		*
 *  Input:																*
 *		$var	variable containing value of a field from the record	*
 *		$value	if the variable matches this value then emit a			*
 *				a 'selected' attribute.									*
 ************************************************************************/
function isSelected($var, $value)
{
    if ($var == $value) print 'selected="selected"'; 
}		// isSelected

/************************************************************************
 *  function isChecked													*
 *																		*
 *  This method emits the HTML checkedd attribute for an 				*
 *  <input type="checkbox"> tag if the value of the variable			*
 *  is not zero.														*
 *																		*
 *  Input:																*
 *		$var	variable containing value of a field from the record	*
 ************************************************************************/
function isChecked($var)
{
    if ($var != 0) print 'checked="checked"'; 
}		// isChecked

// interpret the database gender field as a CSS class name
$genderClasses				= array('male','female','unknown');

// configuration options
// if one of the following options is true then the event appears in
// the dialog even if the event is not recorded (e.g. date and place blank)
$alwaysShowBirth			= true;
$alwaysShowChristen			= true;		// traditional christening
$alwaysShowBaptism			= false;	// LDS Baptism
$alwaysShowEndow			= false;	// LDS Endowment
$alwaysShowConfirm			= false;	// LDS Confirmation
$alwaysShowInitiat			= false;	// LDS Initiatory
$alwaysShowDeath			= true;
$alwaysShowDeathCause		= false;
$alwaysShowBuried			= true;

// process parameters looking for identifier of individual
$idir						= 0;		// edit existing individual
$parentsIdmr				= 0;		// add new child to family
$idcr						= 0;		// edit existing child of family
$person						= null;		// instance of Person
$childr						= null;		// instance of Child
$family						= null;		// instance of Family
$genderFixed				= '';		// pre-selected gender
$idirset					= false;	// idir provided and non-zero
$fathGivenName				= '';
$fathSurname				= '';
$fatherName					= 'unknown';
$mothGivenName				= '';
$mothSurname				= '';
$motherName					= 'unknown';
$treeName					= '';
$showHdrFtr					= true;

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	                // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'id':
            case 'idir':
            {		// identifier of individual
                if (strlen($value) > 0 &&
                    ctype_digit($value))
                {	// valid number
                    $idir		= $value;
                    $idirset	= $idir > 0;
                }	// valid number
                else
                {	// invalid
                    $msg	.= "Unexpected value '$value' for IDIR. ";
                }	// invalid
                break;
            }		// identifier of individual
    
            case 'idcr':
            {		// identifier of child relationship record
                if (strlen($value) > 0 &&
                    ctype_digit($value) &&
                    $value > 0)
                {	// valid number
                    $idcr		= $value;
                    $showHdrFtr	= false;
                }	// valid number
                break;
            }		// identifier of child relationship record
    
            case 'parentsidmr':
            {		// identifier of parents family
                if (strlen($value) > 0 && 
                    ctype_digit($value) &&
                    $value > 0)
                {
                    $parentsIdmr		= $value;
                    $showHdrFtr		= false;
                }
                break;
            }		// identifier of parent's family
    
            case 'rowid':
            {		// role of parent in family
                $showHdrFtr		= false;
                break;
            }		// role of parent in family
    
            case 'fathgivenname':
            {		// explicit father's given name
                $fathGivenName	= $value;
                break;
            }		// explicit father's given name
    
            case 'fathsurname':
            {		// explicit father's surname
                $fathSurname	= $value;
                break;
            }		// explicit father's given name
    
            case 'mothgivenname':
            {		// explicit mother's given name
                $mothGivenName	= $value;
                break;
            }		// explicit mother's given name
    
            case 'mothsurname':
            {		// explicit mother's surname
                $mothSurname	= $value;
                break;
            }		// explicit mother's given name
    
            case 'treename':
            {		// tree to create individual in
                $treeName				= $value;
                break;
            }		// tree to create individual in
        }	// switch on parameter name
    }		// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	                // invoked by URL to display current status of account

// override default mother's and father's names if required
if (strlen($fathSurname) > 0 || strlen($fathGivenName) > 0)
    $fatherName	= trim($fathGivenName . ' ' . $fathSurname);
if (strlen($mothSurname) > 0 || strlen($mothGivenName) > 0)
    $motherName	= trim($mothGivenName . ' ' . $mothSurname);

// parameter to nominalIndex.php
$nameuri		            = '';

// idir parameter explicitly provided for existing individual
if ($idirset)
{		// get the requested individual
    $person		            = new Person(array('idir' => $idir));
    $isOwner			    = canUser('edit') && $person->isOwner();
    $treeName			    = $person->getTreeName();
}		// get the requested individual
else
{		// create new individual with defaults
    // create an instance of Person for the new individual
    $person				    = new Person();
    //$person->setTreeName($treeName);
    $isOwner				= canUser('edit');
}		// create new individual

// make sure the internal events structure of the individual is initialized
if ($person && $person->get('idir') > 0)
    $events		            = $person->getEvents();
else
    $events                 = array();

// initialize or update fields from passed parameters
$given						= $person->get('givenname');
$surname					= $person->get('surname');
$eSurname					= '';
$eGiven						= '';
$prefix						= '';
$name						= '';
$idar						= 0;
$nameuri					= '';
$evBirth					= $person->getBirthEvent(true);	
$evChristen					= $person->getChristeningEvent(false);	
$evBaptism					= $person->getBaptismEvent(false);
$evEndow					= $person->getEndowEvent(false);
$evConfirm					= $person->getConfirmationEvent(false);
$evInitiat					= $person->getInitiatoryEvent(false);
$evDeath					= $person->getDeathEvent(false);
$evBuried					= $person->getBuriedEvent(false);
$birthChanged				= 0;		// birth event
$christenChanged			= 0;		// traditional christening event
$baptismChanged				= 0;		// LDS Baptism event
$endowChanged				= 0;		// LDS Endowment event
$confirmChanged				= 0;		// LDS Confirmation event
$initiatChanged				= 0;		// LDS Initiatory event
$deathChanged				= 0;		// death event
$buriedChanged				= 0;		// buried event

if ($debug)
    $warn	.= "<p>editIndivid.php: " . __LINE__ .
                " Initialize from parameters</p>\n";

foreach($_GET as $key => $value)
{			// loop through parameters
    $fieldLc            = strtolower($key);
    if (substr($fieldLc,0,4) == 'init')
    {			// initialize field in database record
        if ($debug)
             $warn	.= "<p>editIndivid.php: " . __LINE__ . " $key='$value'</p>\n";
        $fieldLc	= substr($fieldLc, 4);
        switch($fieldLc)
        {
            case 'surname':
            {
                if (strlen($value) > 0 && $person)
                {
                    $surname	    = $value;
        if ($debug)
             $warn	.= "<p>editIndivid.php: " . __LINE__ . " \$person->setSurname('$value')</p>\n";
                    $person->setSurname($value);
                }		// value supplied
                break;
            }	// surname

            case 'givenname':
            {
                if (strlen($value) > 0 && $person)
                {
                    $given		= $value;
                    $person->set($fieldLc, $value);
                }		// value supplied
                break;
            }	// surname

            case 'birthdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    $evBirth->setDate(' ' . $value);
                    $birthChanged	= 1;
                }		// value supplied
                break;
            }

            case 'deathdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evDeath))
                        $evDeath	= $person->getDeathEvent(true);
                    $evDeath->setDate(' ' . $value);
                    $deathChanged		= 1;
                }		// value supplied
                break;
            }

            case 'chrisdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evChristen))
                        $evChristen	= $person->getChristeningEvent(true);
                    $evChristen->setDate(' ' . $value);
                    $christenChanged	= 1;
                }		// value supplied
                break;
            }

            case 'burieddate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evBuried))
                        $evBuried	= $person->getBuriedEvent(true);
                    $evBuried->setDate(' ' . $value);
                    $buriedChanged		= 1;
                }		// value supplied
                break;
            }

            case 'baptismdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evBaptism))
                        $evBaptism	= $person->getBaptismEvent(true);
                    $evBaptism->setDate(' ' . $value);
                    $baptismChanged		= 1;
                }		// value supplied
                break;
            }

            case 'confirmationdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evConfirm))
                        $evConfirm	= $person->getConfirmationEvent(true);
                    $evConfirm->setDate(' ' . $value);
                    $confirmChanged		= 1;
                }		// value supplied
                break;
            }

            case 'endowdate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evEndow))
                        $evEndow	= $person->getEndowEvent(true);
                    $evEndow->setDate(' ' . $value);
                    $endowChanged		= 1;
                }		// value supplied
                break;
            }

            case 'initiatorydate':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evInitiat))
                        $evInitiat	= $person->getInitiatoryEvent(true);
                    $evInitiat->setDate(' ' . $value);
                    $initiatChanged		= 1;
                }		// value supplied
                break;
            }

            case 'birthlocation':
            {
                if (strlen($value) > 0 && $person)
                {
                    $loc		= new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save(false);	// get IDLR
                    $evBirth->set('idlrevent', $loc->getIdlr());
                    $birthChanged		= 1;
                }		// value supplied
                break;
            }	// location fields

            case 'chrislocation':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evChristen))
                        $evChristen	= $person->getChristeningEvent(true);
                    $loc		= new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save(false);	// get IDLR
                    $evChristen->set('idlrevent', $loc->getIdlr());
                    $chrisChanged		= 1;
                }		// value supplied
                break;
            }	// location fields

            case 'deathlocation':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evDeath))
                        $evDeath	= $person->getDeathEvent(true);
                    $loc		= new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save(false);	// get IDLR
                    $evDeath->set('idlrevent', $loc->getIdlr());
                    $deathChanged		= 1;
                }		// value supplied
                break;
            }	// location fields

            case 'buriedlocation':
            {
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evBuried))
                        $evBuried	= $person->getBuriedEvent(true);
                    $loc		= new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save(false);	// get IDLR
                    $evBuried->set('idlrevent', $loc->getIdlr());
                    $buriedChanged		= 1;
                }		// value supplied
                break;
            }	// location fields

            case 'baptismtemple':
            {	// LDS temple fields
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evBaptism))
                        $evBaptism	= $person->getBaptismEvent(true);
                    try {
                        $loc	= new Temple(array('idtr' => $value));
                        $evBaptism->set('idlrevent', $loc->getIdtr());
                        $baptismChanged	= 1;
                    } catch(Exception $e) {
                        $msg	.= "Invalid Baptism Temple " .
                                   $e->getMessage() . ". ";
                    }
                }		// value supplied
                break;
            }	// LDS temple fields

            case 'confirmationtemple':
            {	// LDS temple fields
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evConfirm))
                        $evConfirm	= $person->getConfirmationEvent(true);
                    try {
                        $loc	= new Temple(array('idtr' => $value));
                        $evConfirm->set('idlrevent',
                                $loc->getIdtr());
                        $confirmChanged	= 1;
                    } catch(Exception $e) {
                        $msg	.= "Invalid Confirmation Temple " .
                                   $e->getMessage() . ". ";
                    }
                }		// value supplied
                break;
            }	// LDS temple fields

            case 'endowtemple':
            {	// LDS temple fields
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evEndow))
                        $evEndow	= $person->getEndowEvent(true);
                    try {
                        $loc	= new Temple(array('idtr' => $value));
                        $evEndow->set('idlrevent', $loc->getIdtr());
                        $endowChanged	= 1;
                    } catch(Exception $e) {
                        $msg	.= "Invalid Endow Temple " .
                                   $e->getMessage() . ". ";
                    }
                }		// value supplied
                break;
            }	// LDS temple fields

            case 'initiatorytemple':
            {	// LDS temple fields
                if (strlen($value) > 0 && $person)
                {
                    if (is_null($evInitiat))
                        $evInitiat	= $person->getInitiatoryEvent(true);
                    try {
                        $loc	= new Temple(array('idtr' => $value));
                        $evInitiat->set('idlrevent',
                                $loc->getIdtr());
                        $initiatChanged	= 1;
                    } catch(Exception $e) {
                        $msg	.= "Invalid Initiatory Temple " .
                                   $e->getMessage() . ". ";
                    }
                }		// value supplied
                break;
            }		// LDS temple fields

            case 'gender':
            {		// set gender
                if (strlen($value) > 0 && $person)
                {
                    $value	= strtolower($value);
                    if ($value == 1 || $value == 'f' ||
                        strpos($value, 'female') !== false)
                        $person->set('gender', 'female');
                    else
                    if ($value == 0 || $value == 'm' ||
                        strpos($value, 'male') !== false)
                        $person->set('gender', 'male');
                    else
                        $person->set('gender', 'unknown');
                    $genderFixed	= 'readonly="readonly"';
                }		// value supplied
                break;
            }		// set gender

            default:
            {
                if (strlen($value) > 0 && $person)
                {
                    if ($debug)
                        $warn	.= 
                            "<p>editIndivid.php: " . __LINE__ . " \$person->set('$fieldLc', '$value')</p>\n";
                    // note that if $fieldLc is not a field name in the
                    // record this will create a temporary field
                    $person->set($fieldLc, $value);
                }		// value supplied
                break;
            }		// no special handling

        }		// switch on field name
    }			// initialize field in database record
}			// loop through parameters

// extract information from the instance of Person
if ($debug)
    $warn	.= "<p>editIndivid.php: " . __LINE__ . " \$person found or created</p>\n";
if ($person)
{			// individual found or created
    if ($person->get('id') > 0)
    {       // person is initialized
        // check for case of adding or editting a child
        if ($idcr > 0)
        {		// child already exists and is already family member
            $childr				    = new Child(array('idcr' => $idcr));
            $parentsIdmr			= $childr->getIdmr();
            $family			    = new Family(array('idmr' => $parentsIdmr));
            if ($family->isExisting())
            {
                $fatherName			= $family->getHusbName();
                $fatherName			= trim($fatherName);
                $motherName			= $family->getWifeName();
                $motherName			= trim($motherName);
            }
            else 
            {
                $warn	            .= "editIndivid.php: " . __LINE__ . " IDMR=$parentsIdmr not found in database. ";
            }		// getting parent's family failed
        }		// child already exists and is already family member
        else
        if ($parentsIdmr > 0)
        {		// identified a family to which to add the child 
            $family			    = new Family(array('idmr' => $parentsIdmr));
            if ($family->isExisting())
            {
                if ($idir == 0)
                {		// new child
                    $person->save(false);
                    $idir			= $person->getIdir();
                }		// new child
                $childr			    = new Child(array('idmr' => $parentsIdmr,
                                                      'idir' 		=> $idir));
                $childr->save(false);
                $idcr			    = $childr->getIdcr();
                $fatherName			= $family->getHusbName();
                $fatherName			= trim($fatherName);
                $motherName			= $family->getWifeName();
                $motherName			= trim($motherName);
            }
            else
            {
                $warn	            .= "editIndivid.php: " . __LINE__ . " parentsidmr=$value not found in database. ";
            }		// getting parent's family failed
            $title		            = 'Edit New Child of ';
            if (strlen($fatherName) > 0)
            {		// child has a father
                $title	            .= $fatherName;
                if (strlen($motherName) > 0)
                    $title	        .= ' and ';
            }		// child has a father
            if (strlen($motherName) > 0)
                $title	            .= $motherName;
        }		// identified a family to which to add the child
    
        // extract fields from individual for display
        $id					        = $person->get('id');
        $gender				        = $person->get('gender');
        $genderClass		        = $genderClasses[$gender];
        $private			        = $person->get('private');
        $families			        = $person->getFamilies();
        $parents			        = $person->getParents();
    
        if (count($families) > 0)
        {		// ensure never married indicator is off
            $neverMarried			= 0;
            $neverMarriedRO			= 'readonly="readonly"';
        }		// ensure never married indicator is off
        else
        {		// allow never married indicator to be set
            $neverMarried			= $person->get('neverMarried');
            $neverMarriedRO			= "";
        }		// allow never married indicator to be set
    
        // the value of IDMRPref may be invalid due to a flaw in
        // the earlier implementation.  If so fix it.
        $idmrpref				    = $person->get('idmrpref') - 0;
        if ($idmrpref == 0 &&
            count($families) > 0)
            $idmrpref			= $families->rewind()->getIdmr();
    
        // information for Ancestry.ca search
        $fatherGivenName			= '';
        $fatherSurname				= '';
        $motherGivenName			= '';
        $motherSurname				= '';
        $prefParents				= $person->getPreferredParents();
        if ($prefParents)
        {			// have preferred parents
            $father					= $prefParents->getHusband();
            if ($father)
            {			// have father
                $fatherGivenName	= $father->getGivenName();
                $fatherSurname		= $father->getSurname();
            }			// have father
            $mother					= $prefParents->getWife();
            if ($mother)
            {			// have father
                $motherGivenName	= $mother->getGivenName();
                $motherSurname	= $mother->getSurname();
            }			// have father
        }			// have preferred parents
    
        $ancInterest	= $person->get('ancinterest');
        $decInterest	= $person->get('decinterest');
    
        $userRef		= str_replace('"','&quot;',$person->get('userref'));
        $ancestralRef	= str_replace('"','&quot;',$person->get('ancestralref'));
    
    }               // person is initialized
    else
    {
        $idir                   = 0;
        $id					    = $person->get('id');
        $gender				    = $person->get('gender');
        $genderClass		    = $genderClasses[$gender];
        $private			    = $person->get('private');
        $neverMarried			= 0;
        $neverMarriedRO			= '';
        $fatherGivenName        = '';
        $fatherSurname          = '';
        $motherGivenName        = '';
        $motherSurname          = '';
        $userRef                = '';
        $ancestralRef            = '';
        $ancInterest            = 0;
        $decInterest            = 0;
        $parents                = array();
        $families               = array();
        $idmrpref               = 0;
    }

    // construct title
    $eGiven		    = str_replace('"','&quot;',$given);
    $eSurname		= str_replace('"','&quot;',$surname);
    $name		    = $person->getName(Person::NAME_INCLUDE_DATES);
    if (strlen($name) == 0)
        $name		        = 'New Person';
    $idar		    = $person->get('idar') - 0;
    $title		    = "Edit $name";
    $nameuri		= rawurlencode($surname . ', ' . $given);

    // identify prefix of name for name summary page
    if (strlen($surname) == 0)
        $prefix	= '';
    else
    if (substr($surname,0,2) == 'Mc')
        $prefix	= 'Mc';
    else
        $prefix	= substr($surname,0,1);
}	                // individual found
else
{		            // unable to allocate instance of Person
    $title		= "Invalid or Missing value of IDIR=$idir";
}		            // unable to allocate instance of Person

$etitle	= str_replace('"','&quot;',$title);
htmlHeader("Edit $name",
           array('/jscripts/CommonForm.js',
                 '/jscripts/js20/http.js',
                 '/jscripts/util.js',
                 '/jscripts/locationCommon.js',
                 'editIndivid.js'),
           true);
$breadcrumbs	= array('/genealogy.php'	=> 'Genealogy',
                        '/genCountry.php?cc=CA'	=> 'Canada',
                        '/Canada/genProvince.php?Domain=CAON'
                                    => 'Ontario',
                        '/FamilyTree/Services.php'
                                    => 'Services',
                        "nominalIndex.php?name=$nameuri"
                                    => 'Nominal Index ',
                        "Surnames.php?initial=$prefix"	=>
                                "Surnames Starting with '$prefix'",
                        "Names.php?Surname=" . urlencode($surname)
                                    => "Surname '$surname'");
if ($idir > 0)
    $breadcrumbs["Person.php?id=$idir"]			= $name;
?>
    <body>
      <div id="transcription" style="overflow: auto; overflow-x: scroll">
<?php
if ($showHdrFtr)
{
    pageTop($breadcrumbs);
}
?>	
      <div class="body">
        <h1>
          <span class="right">
            <a href="editIndividHelpen.html" target="help">? Help</a>
          </span>
          <span style="flow: left" id="title">
<?php
    print "Edit $name";
    if (strlen($treeName) > 0) 
        print ": in tree '$treeName'"; 
?> 
          </span>
          <div style="clear: both;"></div>
        </h1>
<?php
showTrace();

if (strlen($msg) > 0)
{		            // error message to display
?>
        <p class="message">
            <?php print $msg; ?> 
        </p>
<?php
}		            // error message to display
else
{		            // OK to edit
    showTrace();

    if ($person)
    {
        if ($isOwner)
        {		    // user is authorized to edit this record
?>
        <form name="indForm" id="indForm" action="updatePersonJson.php" method="post">
          <p>
            <button type="submit" id="Submit" style="width: 175px;">
                <u>U</u>pdate Person
            </button>
<?php
            if ($idir == 0)
            {
?>
            &nbsp;
            <button type="button" id="Delete"
                    style="width: 175px; display: inline">
                Cancel
            </button>
<?php
            }
            else
            if (count($parents) == 0 &&
                count($families) == 0)
            {		// individual is not connected to any others
?>
            &nbsp;
            <button type="button" id="Delete"
                    style="width: 175px; display: inline">
                <u>D</u>elete Person
            </button>
<?php
            }		// individual is not connected to any others
            else
            {		// individual is connected
?>
            &nbsp;
            <button type="button" id="Merge"
                    style="width: 175px; display: inline">
                <u>M</u>erge
            </button>
<?php
            }		// individual is connected

            if ($idirset)
            {		// an existing individual
?>
            &nbsp;
            <button type="button" id="Grant" style="width: 175px;">
                <u>G</u>rant Access
            </button>
<?php
            }		// an existing individual
            $eFatherGivenName  =
                    str_replace('"','&quot;',strtolower($fatherGivenName));
            $eFatherSurname  =
                    str_replace('"','&quot;',strtolower($fatherSurname));
            $eMotherGivenName  =
                    str_replace('"','&quot;',strtolower($motherGivenName));
            $eMotherSurname  =
                    str_replace('"','&quot;',strtolower($motherSurname));
            $eTreeName  =
                    str_replace('"','&quot;',$treeName);
?>
            &nbsp;
            <button type="button" id="Search" style="width: 175px;">
                <u>S</u>earch
            </button>
            <div id="SearchDropdownMenu" class="hidden">
              <button type="button" id="censusSearch" style="width: 175px;">
                <u>C</u>ensus Search
              </button>
              <br>
              <button type="button" id="bmdSearch" style="width: 175px;">
                <u>V</u>ital Stats Search
              </button>
              <br>
              <button type="button" id="ancestrySearch" style="width: 175px;">
                Ancestr<u>y</u> Search
              </button>
            </div> <!-- id="SearchDropdownMenu" -->
            <!-- hidden parameter values -->
            <input type="hidden" name="id" id="id"
                   value="<?php print $id; ?>">
            <input type="hidden" name="parentsIdmr" id="parentsIdmr"
                   value="<?php print $parentsIdmr; ?>">
            <input type="hidden" name="idcr" id="idcr"
                   value="<?php print $idcr; ?>">
            <input type="hidden" name="idar" id="idar" 
                   value="<?php print $idar; ?>">
            <input type="hidden" name="fatherGivenName" id="fatherGivenName"
                   value="<?php print $eFatherGivenName; ?>">
		    <input type="hidden" name="fatherSurname" id="fatherSurname"
		           value="<?php print $eFatherSurname; ?>">
		    <input type="hidden" name="motherGivenName" id="motherGivenName"
		           value="<?php print $eMotherGivenName; ?>">
		    <input type="hidden" name="motherSurname" id="motherSurname"
		           value="<?php print $eMotherSurname; ?>">
		    <input type="hidden" name="treeName" id="treeName"
		           value="<?php print $eTreeName; ?>">
		    <input type="hidden" name="parentCount" id="parentCount"
		           value="<?php print count($person->getParents()); ?>">
		    <input type="hidden" name="familyCount" id="familyCount"
		           value="<?php print count($person->getFamilies()); ?>">
<?php
            if ($debug)
            {		// debugging activated
?>
            <input type="hidden" name="Debug" id="Debug" 
                   value="Y">
<?php
            }		// debugging activated
?>
		    <fieldset id="IdentityFields" class="other">
		      <legend class="labelSmall">Identity:</legend>
		      <div class="row">
		        <label class="column1" for="idir">
		            IDIR:
		        </label>
		        <input type="text" name="idir" id="idir" value="<?php print $idir; ?>"
		                class="ina rightnc" style="width: 4em;" readonly="readonly">
		        <div style="clear: both;"></div>
		      </div>
		      <div class="row">
		        <label class="column1" for="Surname">
		            Surname:
		        </label>
		        <input type="text" name="Surname" id="Surname"
		                placeholder="Supply Surname of new Individual"
		                class="white leftnc" maxlength="120" style="width: 594px;"
		                value="<?php print $eSurname; ?>">
		        <div style="clear: both;"></div>
		      </div>
		      <div class="row">
		        <label class="column1" for="GivenName">
		            Given&nbsp;Names:
		        </label>
		        <input type="text" name="GivenName" id="GivenName" size="64"
		                placeholder="Supply Given Name of new Individual"
                        maxlength="120" class="white leftnc" 
                        style="width: 594px;"
		                value="<?php print $eGiven; ?>">
		        <button type="button" class="button" id="Detail1">
		            Details
		        </button>
		        <div style="clear: both;"></div>
		      </div>
		      <div class="row">
		        <div class="column1">
		          <label class="column1" for="Gender">
		            Gender:
		          </label>
		          <select name="Gender" id="Gender" size="1"
		                <?php print $genderFixed; ?>
		                class="<?php print $genderClass; ?> left">
		            <option value="0" <?php isSelected($gender, 0); ?> class="male">
		                Male
		            </option>
		            <option value="1" <?php isSelected($gender, 1); ?> class="female">
		                Female
		            </option>
		            <option value="2" <?php isSelected($gender, 2); ?> class="unknown">
		                Unknown
		            </option>
		          </select>
		          <div style="clear: both;"></div>
		        </div>
		      </div>
		      </fieldset>
<?php
            // permit editing contents of Child record
            if ($childr)
            {		// permit editing contents of Child record
                $childStatus	= $childr->getStatus();
                $relDad		= $childr->getCPRelDad();
                $relMom		= $childr->getCPRelMom();
                $dadPrivate	= $childr->get('cpdadprivate');
                $momPrivate	= $childr->get('cpmomprivate');
?>
      <fieldset id="RelationshipFields" class="other">
        <legend class="labelSmall">Relationship&nbsp;to:</legend>
      <div class="row">
          <label class="column1" for="CPIdcs">
            Final Status:
          </label>
          <select name="CPIdcs" id="CPIdcs" size="1" class="white left">
            <option value="1" <?php isSelected($childStatus, 1); ?>>
 		Ordinary
            </option>
            <option value="2" <?php isSelected($childStatus, 2); ?>>
 		None
            </option>
            <option value="3" <?php isSelected($childStatus, 3); ?>>
 		Stillborn
            </option>
            <option value="4" <?php isSelected($childStatus, 4); ?>>
 		Twin
            </option>
            <option value="5" <?php isSelected($childStatus, 5); ?>>
 		Illegitimate
            </option>
          </select>
        <div style="clear: both;"></div>
      </div>
<?php
            if (strlen($fatherName) > 0)
            {		// relationship to father
?>
      <div class="row">
        <div class="column1">
          <label class="column1" for="CPRelDad">
                <?php print $fatherName; ?>:
          </label>
          <select name="CPRelDad" id="CPRelDad" size="1"
                class="white left">
            <option value="1" <?php isSelected($relDad, 1); ?>>
                ordinary
            </option>
            <option value="2" <?php isSelected($relDad, 2); ?>>
                adopted
            </option>
            <option value="3" <?php isSelected($relDad, 3); ?>>
                biological
            </option>
            <option value="4" <?php isSelected($relDad, 4); ?>>
                challenged
            </option>
            <option value="5" <?php isSelected($relDad, 5); ?>>
                disproved
            </option>
            <option value="6" <?php isSelected($relDad, 6); ?>>
                foster
            </option>
            <option value="7" <?php isSelected($relDad, 7); ?>>
                guardian
            </option>
            <option value="8" <?php isSelected($relDad, 8); ?>>
                sealing
            </option>
            <option value="9" <?php isSelected($relDad, 9); ?>>
                step
            </option>
            <option value="10" <?php isSelected($relDad, 10); ?>>
                unknown
            </option>
            <option value="11" <?php isSelected($relDad, 11); ?>>
                private
            </option>
            <option value="12" <?php isSelected($relDad, 12); ?>>
                family member
            </option>
            <option value="13" <?php isSelected($relDad, 13); ?>>
                illegitimate
            </option>
          </select>
        </div>
        <div class="column2">
          <label class="labelSmall" for="CPDadPrivate">
                Private?
          </label>
          <input type="checkbox" name="CPDadPrivate" id="CPDadPrivate"
                value="1"
                <?php if ($dadPrivate == 1) print 'checked';?>>
        </div>
        <div style="clear: both;"></div>
      </div>
<?php
            }		// relationship to father

            if (strlen($motherName) > 0)
            {		// relationship to mother
?>
      <div class="row">
        <div class="column1">
          <label class="column1" for="CPRelMom">
                <?php print $motherName; ?>:
          </label>
          <select name="CPRelMom" id="CPRelMom" size="1" class="white left">
            <option value="1" <?php isSelected($relMom, 1); ?>>
                ordinary
            </option>
            <option value="2" <?php isSelected($relMom, 2); ?>>
                adopted
            </option>
            <option value="3" <?php isSelected($relMom, 3); ?>>
                biological
            </option>
            <option value="4" <?php isSelected($relMom, 4); ?>>
                challenged
            </option>
            <option value="5" <?php isSelected($relMom, 5); ?>>
                disproved
            </option>
            <option value="6" <?php isSelected($relMom, 6); ?>>
                foster
            </option>
            <option value="7" <?php isSelected($relMom, 7); ?>>
                guardian
            </option>
            <option value="8" <?php isSelected($relMom, 8); ?>>
                sealing
            </option>
            <option value="9" <?php isSelected($relMom, 9); ?>>
                step
            </option>
            <option value="10" <?php isSelected($relMom, 10); ?>>
                unknown
            </option>
            <option value="11" <?php isSelected($relMom, 11); ?>>
                private
            </option>
            <option value="12" <?php isSelected($relMom, 12); ?>>
                family member
            </option>
            <option value="13" <?php isSelected($relMom, 13); ?>>
                illegitimate
            </option>
          </select>
        </div>
        <div class="column2">
          <label class="labelSmall" for="CPMomPrivate">
                Private?
          </label>
          <input type="checkbox" name="CPMomPrivate" id="CPMomPrivate" value="1"
                <?php if ($momPrivate) print 'checked';?>>
        </div>
        <div style="clear: both;"></div>
      </div>
<?php
            }		// relationship to mother

        // LDS Sealed to Parents Event
        $parSealed	= $childr->getParSealEvent(true);
        $date		= $parSealed->getDate();
        $idlrevent	= $parSealed->get('idlrevent');
        $temples	= new RecordSet('Temples');
?>
      <div class="row" id="SealingRow">
        <label class="column1" for="SealingDate">
                Sealed to Parents:
        </label>
          <input type="text" name="SealingDate" id="SealingDate"
                class="white leftdate"
                value="<?php print $date; ?>">
          <select name="SealingIdtr" id="SealingIdtr" class="white left">
<?php
            if ($idlrevent == 0)
                $selected	= 'selected="selected"';
            else
                $selected	= '';
?>
                <option value="0" <?php print $selected;?>>
                    Choose a Temple:
                </option>
<?php
        foreach($temples as $oidtr => $temple)
        {	// loop through all temples
            $templeName	= $temple->getName();
            if ($oidtr == $idlrevent)
                $selected	= 'selected="selected"';
            else
                $selected	= '';
?>
                <option value="<?php print $oidtr; ?>" <?php print $selected;?>>
                    <?php print $templeName;?> 
                </option>
<?php
        }	// loop through all temples
?>
          </select>
          <input type="hidden" name="SealingIder" id="SealingIder"
                value="<?php print $parSealed->get("ider"); ?>">
          <button type="button" class="button" id="Detail17">
                Details
          </button>
          <button type="button" class="button" id="Clear17">
                Delete
          </button>
        <div style="clear: both;"></div>
      </div>
      </fieldset>
<?php
            }		// permit editing contents of Child record

            // the following code ensures that events that are always to be
            // displayed are displayed
            if (is_null($evBirth) && $alwaysShowBirth)
                $evBirth	= $person->getBirthEvent(true);
            if (is_null($evDeath) && $alwaysShowDeath)
                $evDeath	= $person->getDeathEvent(true);
            if (is_null($evChristen) && $alwaysShowChristen)
                $evChristen	= $person->getChristeningEvent(true);
            if (is_null($evBuried) && $alwaysShowBuried)
                $evBuried	= $person->getBuriedEvent(true);
            if (is_null($evBaptism) && $alwaysShowBaptism)
                $evBaptism	= $person->getBaptismEvent(true);
            if (is_null($evConfirm) && $alwaysShowConfirm)
                $evConfirm	= $person->getConfirmationEvent(true);
            if (is_null($evEndow) && $alwaysShowEndow)
                $evEndow	= $person->getEndowEvent(true);
            if (is_null($evInitiat) && $alwaysShowInitiat)
                $evInitiat	= $person->getInitiatoryEvent(true);

            if ($evBuried &&
                $evDeath)
            {
                $buriedsd	= $evBuried->get('eventsd');
                $deathsd	= $evDeath->get('eventsd');
                if ($buriedsd <= $deathsd)
                {
                    $evBuried->set('eventsd',
                               $deathsd + 2);
                }
            }

            // process all events for this individual in order
            // refresh the list of events
            $events	        = $person->getEvents();

            // the following function wrapper is required because the
            // PHP function usort does not support OOP
            function order($ev1, $ev2)
            {			// customize sort order
                return $ev1->compare($ev2);
            }			// customize sort order
            usort($events,
                  __NAMESPACE__ . '\\order');

            showTrace();
?>
      <fieldset id="EventFields" class="other">
        <legend class="labelSmall">Events</legend>
        <div class="row"
                id="EventColHeadersRow">
<?php
        if ($debug)
        {
?>
          <span class="labelSmall"
                style="width: 3em; padding-left: 3em;">
                IDER:
          </span>
<?php
        }
?>
          <span class="labelSmall"
                style="width: 9em; padding-left: 16em; padding-right: 6em;">
                Date:
          </span>
          <span class="labelSmall"
                style="width: 11em; padding-left: 5em; padding-right: 22em;">
                Location:
          </span>
          <span class="labelSmall">
                Preferred:
          </span>
<?php
        if ($debug)
        {
?>
          <span class="labelSmall"
                style="width: 3em; padding-left: 5em;">
                IDET:
          </span>
          <span class="labelSmall"
                style="width: 3em; padding-left: 5em;">
                CitType:
          </span>
          <span class="labelSmall"
                style="width: 3em; padding-left: 5em;">
                Order:
          </span>
          <span class="labelSmall"
                style="width: 3em; padding-left: 5em;">
                Sort Date:
          </span>
          <span class="labelSmall"
                style="width: 3em; padding-left: 5em;">
                Changed:
          </span>
<?php
        }
?>
          <div style="clear: both;"></div>
        </div>
        <div id="EventBody">
          <!-- always put the Birth event first-->
          <div class="row" id="BirthRow">
<?php
        $rownum	        = 0;
        $event          = $evBirth;
        $ider           = $event->getIder();
        $citType	    = $event->getCitType();
        $idet		    = $event->get('idet');
        $idlr		    = $event->get('idlrevent');
        $kind		    = $event->get('kind');
        $preferred	    = $event->get('preferred');
        $order		    = $event->get('order');
        $type	        = "Birth";

        $date		    = new LegacyDate($event->get('eventd'));
        $date		    = $date->toString();
        $eventd		    = $event->get('eventd');
        if (substr($eventd, 0, 1) == ':')
            $dateError	= 'error';
        else
            $dateError	= '';
        $datesd		    = $event->get('eventsd');

        $location		= $event->getLocation();
        $locationName	= str_replace('"','&quot;',$location->getName());

        $desc		    = str_replace('"','&quot;',$event->getDesc());
        $descn		    = str_replace('"','&quot;',$event->getDescription());

        // ensure that the IDER is the first field in the description
        // of an event so its value is passed to the update script first
        if ($debug)
        {		// show hidden columns
?>
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventIder<?php print $rownum; ?>"
                        id="EventIder<?php print $rownum; ?>"
                        value="<?php print $ider; ?>">
<?php
        }		// show hidden columns
        else
        {		// hide internal fields
?>
        <input type="hidden" name="EventIder<?php print $rownum; ?>"
                        id="EventIder<?php print $rownum; ?>"
                        value="<?php print $ider; ?>">
<?php
        }		// hide hidden columns
?>
        <label class="column1" for="BirthDate">
                Birth:
        </label>
        <input type="text" name="BirthDate" id="BirthDate"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="BirthLocation" id="BirthLocation"
                maxlength="255" class="white leftloc"
                value="<?php print $locationName; ?>">
        <input type="checkbox"
                name="EventPref<?php print $rownum; ?>" 
                id="EventPref<?php print $rownum; ?>" value="Y"
                <?php if ($event->get("preferred"))
                          print 'checked="checked"'; ?>>
<?php
        $changed	= $birthChanged;
        if ($debug)
        {		// show hidden columns
?>
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventIdet<?php print $rownum; ?>"
                        id="EventIdet<?php print $rownum; ?>"
                        value="<?php print $idet; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventCitType<?php print $rownum; ?>"
                        id="EventCitType<?php print $rownum; ?>"
                        value="<?php print $citType; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventOrder<?php print $rownum; ?>"
                        id="EventOrder<?php print $rownum; ?>"
                        value="<?php print $order; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventSD<?php print $rownum; ?>"
                        id="EventSD<?php print $rownum; ?>"
                        value="<?php print $datesd; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventChanged<?php print $rownum; ?>"
                        id="EventChanged<?php print $rownum; ?>"
                        value="<?php print $changed; ?>">
<?php		
        }		// show hidden columns
        else
        {		// hide internal fields
?>
        <input type="hidden" name="EventIdet<?php print $rownum; ?>"
                        id="EventIdet<?php print $rownum; ?>"
                        value="<?php print $idet; ?>">
        <input type="hidden" name="EventCitType<?php print $rownum; ?>"
                        id="EventCitType<?php print $rownum; ?>"
                        value="<?php print $citType; ?>">
        <input type="hidden" name="EventOrder<?php print $rownum; ?>"
                        id="EventOrder<?php print $rownum; ?>"
                        value="<?php print $order; ?>">
        <input type="hidden" name="EventSD<?php print $rownum; ?>"
                        id="EventSD<?php print $rownum; ?>"
                        value="<?php print $datesd; ?>">
        <input type="hidden" name="EventChanged<?php print $rownum; ?>"
                        id="EventChanged<?php print $rownum; ?>"
                        value="<?php print $changed; ?>">
<?php
        }		// hide internal fields
?>
        <button type="button" class="button"
                        id="EventDetail<?php print $rownum; ?>">
                Details
        </button>
        <button type="button" class="button"
                        id="EventDelete<?php print $rownum; ?>">
                Delete
        </button>
        <div style="clear: both;"></div>
      </div>
<?php
        // all other events other than birth
        $rownum             = 1;
        foreach($events as $ie => $event)
        {			// loop through Events
            $ider				= $event->get('ider');
            $citType			= $event->getCitType();
            $idet				= $event->get('idet');
            $idlr				= $event->get('idlrevent');
            $kind				= $event->get('kind');
            $preferred	        = $event->get('preferred');
            $order		        = $event->get('order');
            $showDeathCause	    = false;
            if (array_key_exists($idet, Event::$eventText))
            {		// assign appropriate label
                $type	        = ucfirst(Event::$eventText[$idet]);
            }		// assign appropriate label
            else
            {		// IDET missing from translation table
                $type	        =  "IDET=$idet";
            }		    // IDET missing from translation table

            $date		        = new LegacyDate($event->get('eventd'));
            $date		        = $date->toString();
            $eventd		        = $event->get('eventd');
            if (substr($eventd, 0, 1) == ':')
                $dateError	    = 'error';
            else
                $dateError	    = '';
            $datesd		        = $event->get('eventsd');

            try {
                $location		= $event->getLocation();
                $locationName	= str_replace('"','&quot;',$location->getName());
            } catch (Exception $e) {
                $location		= new Location(array('idlr' => 1));
                $locationName	= '';
            }

            $desc		    = str_replace('"','&quot;',$event->getDesc());
            $descn		    = str_replace('"','&quot;',$event->getDescription());

            $notshown	    = !$preferred;
            $changed	    = 0;

            if ($preferred)
            {		        // preferred events have special layouts
                switch($idet)
                {		    // act on specific event types
                    case Event::ET_BIRTH:
                    {
                        continue 2;        // already displayed
                    }	    // birth

                    case Event::ET_CHRISTENING:
                    {
?>
        <div class="row" id="ChristeningRow">
<?php
                        break;
                    }	    // christening

                    case Event::ET_LDS_BAPTISM:
                    {
?>
        <div class="row" id="BaptismRow">
<?php
                        break;
                    }	    // LDS Baptism

                    case Event::ET_LDS_ENDOWED:
                    {
?>
        <div class="row" id="EndowmentRow">
<?php
                        break;
                    }	    // LDS endowment

                    case Event::ET_LDS_CONFIRMATION:
                    {
?>
        <div class="row" id="ConfirmationRow">
<?php
                        break;
                    }	    // LDS confirmation

                    case Event::ET_LDS_INITIATORY:
                    {
?>
        <div class="row" id="InitiatoryRow">
<?php
                        break;
                    }	    // LDS initiatory

                    case Event::ET_DEATH:
                    {
?>
        <div class="row" id="DeathRow">
<?php
                        break;
                    }	    // death

                    case Event::ET_BURIAL:
                    {
?>
        <div class="row" id="BuriedRow">
<?php
                        break;
                    }	    // burial

                    default:
                    {	    // any other preferred event
?>
        <div class="row" id="EventRow<?php print $rownum; ?>">
<?php
                        break;
                    }	    // any other preferred event
                }		    // act on specific event types
            }		        // preferred events have special layouts
            else
            {		        // standard event contained
?>
    <div class="row" id="EventRow<?php print $rownum; ?>">
<?php
            }		        // standard event contained

            // ensure that the IDER is the first field in the description
            // of an event so its value is passed to the update script first
            if ($debug)
            {		        // show hidden columns
?>
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventIder<?php print $rownum; ?>"
                        id="EventIder<?php print $rownum; ?>"
                        value="<?php print $ider; ?>">
<?php
            }		        // show hidden columns
            else
            {		        // hide internal fields
?>
        <input type="hidden" name="EventIder<?php print $rownum; ?>"
                        id="EventIder<?php print $rownum; ?>"
                        value="<?php print $ider; ?>">
<?php
            }		        // hide hidden columns

            if ($preferred)
            {		        // preferred events have special layouts
                switch($idet)
                {		    // act on specific event types
                    case Event::ET_BIRTH:
                    {       // birth already displayed
                        break;
                    }		// birth

                    case Event::ET_CHRISTENING:
                    {
?>
        <label class="column1" for="ChrisDate">
                Christening:
        </label>
        <input type="text" name="ChrisDate" id="ChrisDate"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="ChrisLocation" id="ChrisLocation" size="52"
                maxlength="255" class="white leftloc"
                value="<?php print $locationName; ?>">
<?php
                        $changed	= $christenChanged;
                        break;
                    }		// christening

                    case Event::ET_LDS_BAPTISM:
                    {
?>
        <label class="column1" for="BaptismDate">
                LDS Baptism:
        </label>
        <input type="text" name="BaptismDate" id="BaptismDate"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
<?php
                        if ($kind == 1)
                        {		// baptised at temple
?>
        <input type="text" name="BaptismTemple" id="BaptismTemple" size="52"
                maxlength="255"
                class="ina leftnc"
                readonly="readonly"
                value="<?php print $locationName; ?>">
        <input type="hidden"
                name="EventIdlr<?php print $rownum; ?>" 
                id="EventIdlr<?php print $rownum; ?>"
                value="<?php print $idlrEvent; ?>">
<?php
                        }		// baptised at temple
                        else
                        {		// baptised outside temple
?>
        <input type="text" name="BaptismLocation" id="BaptismLocation" size="52"
                maxlength="255" class="white leftloc"
                value="<?php print $locationName; ?>">
<?php
                        }		// baptised outside temple
                        $changed	= $baptismChanged;
                        break;
                    }		// LDS Baptism

                    case Event::ET_LDS_ENDOWED:
                    {
?>
        <label class="column1" for="EndowmentDate">
                LDS Endowment:
        </label>
        <input type="text" name="EndowmentDate" id="EndowmentDate" 
                class="white leftdate" value="<?php print $date; ?>">
        <input type="text" name="EndowmentTemple" id="EndowmentTemple"
                size="52" maxlength="255" class="ina leftnc" readonly="readonly"
                value="<?php print str_replace('"','&quot;',$locationName); ?>">
        <input type="hidden"
                name="EventIdlr<?php print $rownum; ?>"
                id="EventIdlr<?php print $rownum; ?>"
                value="<?php print $idlrEvent; ?>">
<?php
                        $changed	= $endowChanged;
                        break;
                    }		// LDS endowment

                    case Event::ET_LDS_CONFIRMATION:
                    {
?>
        <label class="column1" id="ConfirmationDate">
                LDS Confirmation:
        </label>
        <input type="text" name="ConfirmationDate" id="ConfirmationDate"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="ConfirmationTemple" id="ConfirmationTemple"
                size="52" maxlength="255" class="ina leftnc" readonly="readonly"
                value="<?php print $locationName; ?>">
        <input type="hidden"
                name="EventIdlr<?php print $rownum; ?>"
                id="EventIdlr<?php print $rownum; ?>"
                value="<?php print $idlrEvent; ?>">
<?php
                        $changed	= $confirmChanged;
                        break;
                    }		// LDS confirmation

                    case Event::ET_LDS_INITIATORY:
                    {
?>
        <label class="column1" for="InitiatoryDate">
                LDS Initiatory:
        </label>
        <input type="text" name="InitiatoryDate" id="InitiatoryDate"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="InitiatoryTemple" id="InitiatoryTemple"
                size="52" maxlength="255"
                class="ina leftnc" readonly="readonly"
                value="<?php print $locationName; ?>">
        <input type="hidden"
                name="EventIdlr<?php print $rownum; ?>"
                id="EventIdlr<?php print $rownum; ?>"
                value="<?php print $idlrEvent; ?>">
<?php
                        $changed	= $initiatChanged;
                        break;
                    }		// LDS initiatory

                    case Event::ET_DEATH:
                    {
?>
        <label class="column1" for="DeathDate">
                Death:
        </label>
        <input type="text" name="DeathDate" id="DeathDate" size="11"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="DeathLocation" id="DeathLocation" size="52"
                maxlength="255" class="white leftloc"
                value="<?php print $locationName; ?>">
<?php
                        if ($alwaysShowDeathCause)
                            $showDeathCause		= true;
                        $changed	= $deathChanged;
                        break;
                    }		// death

                    case Event::ET_BURIAL:
                    {
?>
        <label class="column1" for="BuriedDate">
                Buried:
        </label>
        <input type="text" name="BuriedDate" id="BuriedDate" size="11"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
        <input type="text" name="BuriedLocation" id="BuriedLocation" size="52"
                maxlength="255" class="white leftloc"
                value="<?php print $locationName; ?>">
<?php
                        $changed	= $buriedChanged;
                        break;
                    }	    // burial

                    default:
                    {	    // any other preferred event
                        $notshown	= true;
                        break;
                    }	    // any other preferred event
                }		    // act on specific event types
            }		        // preferred events have special layouts

            if ($notshown)
            {		        // no special handling for event
?>
        <label class="column1"
                id="EventLabel<?php print $rownum; ?>"
                for="EventDate<?php print $rownum; ?>">
                <?php print $type; ?>:
        </label>
        <input type="text" name="EventDate<?php print $rownum; ?>"
                id="EventDate<?php print $rownum; ?>"
                class="white leftdate<?php print $dateError; ?>"
                value="<?php print $date; ?>">
<?php
                if (strlen($descn) > 0)
                {		// description present
?>
        <input type="text"
                name="EventDescn<?php print $rownum; ?>"
                id="EventDescn<?php print $rownum; ?>"
                maxlength="1023"
                class="white leftnc" style="width: 11em;"
                value="<?php print $descn; ?>">

        <input type="text"
                name="EventLocation<?php print $rownum; ?>"
                id="EventLocation<?php print $rownum; ?>"
                maxlength="255"
                class="white leftloc" style="width: 300px;"
                value="<?php print $locationName; ?>">
<?php
                }		// description present
                else
                {		// only location present
?>
        <input type="text"
                name="EventLocation<?php print $rownum; ?>"
                id="EventLocation<?php print $rownum; ?>"
                maxlength="255"
                class="white leftloc" 
                value="<?php print $locationName; ?>">
<?php
                }		// only location present
            }		// no special handling for event
?>
        <input type="checkbox"
                name="EventPref<?php print $rownum; ?>" 
                id="EventPref<?php print $rownum; ?>" value="Y"
                <?php if ($event->get("preferred"))
                          print 'checked="checked"'; ?>>
<?php
            if ($debug)
            {		// show hidden columns
?>
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventIdet<?php print $rownum; ?>"
                        id="EventIdet<?php print $rownum; ?>"
                        value="<?php print $idet; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventCitType<?php print $rownum; ?>"
                        id="EventCitType<?php print $rownum; ?>"
                        value="<?php print $citType; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventOrder<?php print $rownum; ?>"
                        id="EventOrder<?php print $rownum; ?>"
                        value="<?php print $order; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventSD<?php print $rownum; ?>"
                        id="EventSD<?php print $rownum; ?>"
                        value="<?php print $datesd; ?>">
        <input type="text" readonly="readonly" class="ina rightnc"
                        style="width: 8em;"
                        name="EventChanged<?php print $rownum; ?>"
                        id="EventChanged<?php print $rownum; ?>"
                        value="<?php print $changed; ?>">
<?php		
            }		// show hidden columns
            else
            {		// hide internal fields
?>
        <input type="hidden" name="EventIdet<?php print $rownum; ?>"
                        id="EventIdet<?php print $rownum; ?>"
                        value="<?php print $idet; ?>">
        <input type="hidden" name="EventCitType<?php print $rownum; ?>"
                        id="EventCitType<?php print $rownum; ?>"
                        value="<?php print $citType; ?>">
        <input type="hidden" name="EventOrder<?php print $rownum; ?>"
                        id="EventOrder<?php print $rownum; ?>"
                        value="<?php print $order; ?>">
        <input type="hidden" name="EventSD<?php print $rownum; ?>"
                        id="EventSD<?php print $rownum; ?>"
                        value="<?php print $datesd; ?>">
        <input type="hidden" name="EventChanged<?php print $rownum; ?>"
                        id="EventChanged<?php print $rownum; ?>"
                        value="<?php print $changed; ?>">
<?php
            }		// hide internal fields
?>
        <button type="button" class="button"
                        id="EventDetail<?php print $rownum; ?>">
                Details
        </button>
        <button type="button" class="button"
                        id="EventDelete<?php print $rownum; ?>">
                Delete
        </button>
        <div style="clear: both;"></div>
      </div>
<?php

            if ($showDeathCause)
            {		    // show cause of death row
                $deathCause = $person->get('deathcause');
                $deathCause = str_replace('"','&quot;',$deathcause);
?>
      <div id="DeathCauseRow" class="row">
        <label class="column1" for="DeathCause">
                Death Cause:
        </label>
        <input type="text" name="DeathCause" id="DeathCause"
                maxlength="255" style="width: 594px;"
                class="white leftnc"
                value="<?php print $deathCause; ?>">
        <button type="button" class="button" id="Detail9">
                Details
        </button>
        <button type="button" class="button" id="Clear9">
                Delete
        </button>
        <div style="clear: both;"></div>
      </div>
<?php
            }		    // show cause of death row
            $rownum++;
        }			    // loop through events
?>
      </div> <!-- end of <div id="EventBody"> -->
      <div class="row" id="AddEventRow">
        <label class="column1" for="AddEvent">
        </label>
        <button type="button" class="button" id="AddEvent"
                style="width: 11em;">
                Add <u>E</u>vent
        </button>
        <button type="button" class="button" id="Order">
                <u>O</u>rder Events by Date
        </button>
        <div style="clear: both;"></div>
      </div>
      </fieldset>
      <fieldset id="OtherFields" class="other">
        <legend class="labelSmall">
            Other:
        </legend>
        <!-- row allowing edit of Private flag -->
        <div class="row" id="PrivateRow">
          <div class="column1">
            <label class="column1" for="Private">
              Private:
            </label>
            <select name="Private" id="Private" size="1" class="white left">
              <option value="0" <?php isSelected($private, "0"); ?>>
                No
              </option>
              <option value="1" <?php isSelected($private, "1"); ?>>
                Yes
              </option>
              <option value="2" <?php isSelected($private, "2"); ?>>
                Invisible
              </option>
            </select>
          </div>
          <div class="column2">
            <label class="labelSmall" for="NeverMarried">
              Never Married:
            </label>
            <input type="hidden" name="NeverMarried[]" value="0">
            <input type="checkbox" name="NeverMarried[]" id="NeverMarried"
                value="1"
                <?php print $neverMarriedRO . ' '; isChecked($neverMarried);?>>  
          </div>
          <div style="clear: both;"></div>
        </div>
        <div class="row" id="RefRow">
          <div class="column1">
            <label class="column1" for="UserRef">
                User Ref:
            </label>
            <input type="text" name="UserRef" id="UserRef"
                maxlength="50" style="width: 9em;"
                class="white leftnc"
                value="<?php print $userRef; ?>">
          </div>
          <div class="column2">
            <label class="labelSmall" for="AncestralRef">
                Ancestral Ref:
            </label>
            <input type="text" name="AncestralRef" id="AncestralRef"
                maxlength="20" style="width: 9em;"
                class="white left"
                value="<?php print $ancestralRef; ?>">
          </div>
          <div style="clear: both;"></div>
        </div>
        <div class="row" id="InterestRow">
          <div class="column1">
            <label class="column1" for="AncInterest">
                Ancestor Interest:
            </label>
            <select name="AncInterest" id="AncInterest" 
                        size="1" class="white left">
              <option value="0" <?php isSelected($ancInterest, "0"); ?>>
                Low
              </option>
              <option value="1" <?php isSelected($ancInterest, "1"); ?>>
                Moderate
              </option>
              <option value="2" <?php isSelected($ancInterest, "2"); ?>>
                High
              </option>
              <option value="3" <?php isSelected($ancInterest, "3"); ?>>
                Highest
              </option>
            </select>
          </div>
          <div class="column2">
            <label class="labelSmall" for="DecInterest">
                Descendant Interest:
            </label>
            <select name="DecInterest" id="DecInterest"
                        size="1" class="white left">
              <option value="0" <?php isSelected($decInterest, "0"); ?>>
                Low
              </option>
              <option value="1" <?php isSelected($decInterest, "1"); ?>>
                Moderate
              </option>
              <option value="2" <?php isSelected($decInterest, "2"); ?>>
                High
              </option>
              <option value="3" <?php isSelected($decInterest, "3"); ?>>
                Highest
              </option>
            </select>
          </div>
          <div style="clear: both;"></div>
        </div>
      </fieldset>
      <fieldset id="ButtonFields" class="other">
        <legend class="labelSmall">
            Buttons:
        </legend>
        <div class="row">
          <div class="column1">
          <button type="button" class="buttonCol1" id="Detail6">
                Edit General   <u>N</u>otes
          </button>
          </div>
          <div style="clear: both;"></div>
        </div>
        <div class="row">
          <button type="button" class="buttonCol1" id="Parents">
<?php
            if (count($parents) == 0)
            {
?>
                Add   <u>P</u>arents
<?php
            }		// no existing marriages
            else
            {		// at least one marriage
?>
                Edit   <u>P</u>arents
<?php
            }		// at least one marriage
?>
          </button>
          <button type="button" class="buttonCol2" id="Marriages">
<?php
            if (count($families) == 0)
            {
?>
                Add Spouse or Child
<?php
            }		// no existing marriages
            else
            {		// at least one marriage
?>
                Edit   <u>F</u>amilies
<?php
            }		// at least one marriage
?>
          </button>
          <input type="hidden" name="IDMRPref" id="IDMRPref" 
                value="<?php print $idmrpref; ?>">
          <div style="clear: both;"></div>
        </div>
        <div class="row" id="PicturesRow">
            <button type="button" class="buttonCol1" id="Pictures">
                Edit P<u>i</u>ctures
            </button>
            <button type="button" class="buttonCol2" id="Address">
                  <?php if ($idar > 0) print "Edit"; else print "Add"; ?> 
                  <u>A</u>ddress
            </button>
          <div style="clear: both;"></div>
        </div>
          <!-- row allowing edit of some notes fields -->
        <div class="row" id="NotesRow">
            <button type="button" class="buttonCol1" id="Detail7">
                Edit <u>R</u>esearch Notes
            </button>
            <button type="button" class="buttonCol2" id="Detail8">
                Edit Medical Notes
            </button>
          <div style="clear: both;"></div>
        </div>
      </fieldset>
  </form>
<?php
        }		// current user is an owner of record
        else
        {		// current user does not own record
?>
<p class="message">
    You are not authorized to update this individual.
    Contact one of the existing owners.
</p>
<?php
        }		// current user does not own record
    }		// constructed instance of Person
}			// OK to edit
?>
    </div> <!-- class="body" -->
<?php
if ($showHdrFtr)
    pageBot($title . ": IDIR=$idir", $idir, 'tblIR');
else
    dialogBot();
?>
  </div> <!-- id="transcription" -->
<div class="hidden" id="templates">
<!-- template for Add Address button -->
    <span id="AddressAdd">
        Add <u>A</u>ddress
    </span>
<!-- template for Edit Address button -->
    <span id="AddressRepl">
        Edit <u>A</u>ddress
    </span>
<!-- template for Add Parents button -->
    <span id="AddParentsRepl">
        Add <u>P</u>arents
    </span>
<!-- template for Edit Parents button -->
    <span id="EditParentsRepl">
        Edit <u>P</u>arents
    </span>
<!-- template for Add Spouse button -->
    <span id="AddSpouseRepl">
        Add Spouse or Child
    </span>
<!-- template for Edit Families button -->
    <span id="EditFamiliesRepl">
        Edit <u>F</u>amilies
    </span>
<!-- template for an added row allowing edit of some notes fields -->
      <div id="NotesRow$template">
          <button type="button" class="button" id="Detail7$template">
                Edit <u>R</u>esearch Notes
          </button>
          <button type="button" class="button" id="Detail8$template">
                Edit Medical Notes
          </button>
      </div>
<!-- template for an added row allowing edit of Private flag -->
      <div id="PrivateRow$template">
        <label class="column1" for="Private">
            Private:
        </label>
            <input type="hidden" name="Private[]$template" value="0">
            <input type="checkbox" name="Private[]$template" value="1"
                id="PrivateCheckBox$template">
      </div>
      <div id="RefRow$template">
        <label class="column1" for="UserRef">
                User Ref:
        </label>
        <input type="text" name="UserRef$template" size="11"
                maxlength="50"
                class="white leftnc"
                value="$userRef">
        <label class="labelSmall" for="AncestralRef">
                Ancestral Ref:
        </label>
          <input type="text" name="AncestralRef$template" size="11"
                maxlength="20"
                class="white left"
                value="$ancestralRef">
      </div>
<!-- template for an added row displaying an event -->
      <div id="EventRow$rownum" class="row">
        <label class="column1"
                id="EventLabel$rownum" for="EventDate$rownum">
                $etype:
        </label>
          <input type="hidden" name="EventIder$rownum"
                        id="EventIder$rownum"
                        value="$ider">
          <input type="text"
                name="EventDate$rownum" id="EventDate$rownum"
                class="white leftdate" value="$date">
          <input type="text"
                name="EventDescn$rownum" id="EventDescn$rownum"
                maxlength="1023"
                class="white leftnc" style="width: 11em;"
                value="$description">
          <input type="text"
                name="EventLocation$rownum" id="EventLocation$rownum"
                maxlength="255"
                class="white leftloc" style="width: 300px;"
                value="$location">
          <input type="checkbox" name="EventPref$rownum"
                id="EventPref$rownum" value="Y">
          <input type="hidden" name="EventIdet$rownum"
                        id="EventIdet$rownum"
                        value="$idet">
          <input type="hidden" name="EventCitType$rownum"
                        id="EventCitType$rownum"
                        value="$citType">
          <input type="hidden" name="EventOrder$rownum"
                        id="EventOrder$rownum"
                        value="$rownum">
          <input type="hidden" name="EventSD$rownum"
                        id="EventSD$rownum"
                        value="$datesd">
          <input type="hidden" name="EventChanged$rownum"
                        id="EventChanged$rownum"
                value="0">
          <button type="button" class="button"
                id="EventDetail$rownum">
                Details
          </button>
          <button type="button" class="button"
                id="EventDelete$rownum">
                Delete
          </button>
      </div>

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
      <input type="hidden" id="IDER$type" name="IDER$type"
                value="$ider">
      <input type="hidden" id="RowName$type" name="RowName$type"
                value="$rowname">
      <input type="hidden" id="RowNum$type" name="RowNum$type"
                value="$rownum">
        &nbsp;
      <button type="button" id="cancelDelete$type">
        Cancel
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
<div class="balloon" id="Helpid">
<p>This read-only field displays the internal record number of the database
record for this individual.
</p>
</div>
<div class="balloon" id="Helpidir">
<p>This read-only field displays the numeric key that is used by other database
records to locate the database record for this individual.  If the database
was originally loaded from a GEDCOM file this is the numeric key used for
referencing the INDI record in that file.
</p>
</div>
<div class="balloon" id="HelpparentsIdmr">
<p>This hidden field records the internal record number of the database
record for the family record of the parents of this individual.
</p>
</div>
<div class="balloon" id="HelpSurname">
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
value, stored in field 'SoundsLike' in the individual record is updated.
Also if the surname does not already appear in the database, a record is
added into the table 'tblNR'.
</p>
</div>
<div class="balloon" id="HelpGivenName">
<p>Edit the given names of the individual. 
</p>
</div>
<div class="balloon" id="HelpPrefix">
<p>The name prefix.  I don't know what this is usually used for.
</p>
</div>
<div class="balloon" id="HelpTitle">
<p>The title is the portion of a name that represents an honorific or rank.
Examples include __NAMESPACE__ . "/Dr.", "Rev'd", "Capt.", and "Sir".
</p>
</div>
<div class="balloon" id="HelpSealingDate">
<p>The date the individual was sealed to his parents in an LDS temple.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpBaptismTemple">
<p>The LDS temple where the individual was baptized.
</p>
</div>
<div class="balloon" id="HelpConfirmationTemple">
<p>The LDS temple where the individual was confirmed.
</p>
</div>
<div class="balloon" id="HelpInitiatoryTemple">
<p>The LDS temple where the individual was initiated.
</p>
</div>
<div class="balloon" id="HelpEndowmentTemple">
<p>The LDS temple where the individual was endowed.
</p>
</div>
<div class="balloon" id="HelpSealingTemple">
<p>The LDS temple where the individual was sealed to his parents.
</p>
</div>
<div class="balloon" id="HelpBirthDate">
<p>The birth date.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpBirthLocation">
<p>The location where the individual was born.
</p>
</div>
<div class="balloon" id="HelpChrisDate">
<p>The date of christening.  Note that because of the key contributions
to genealogy by the Church of Jesus Christ of Latter Day Saints (LDS), the
term "christening" is used to describe a baptism in a Christian church.
The term "baptism" is used exclusively for the LDS sacrament.
<p>  
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpChrisLocation">
<p>
The location where the individual was christened.
</p>
</div>
<div class="balloon" id="HelpDeathDate">
<p>The date of death.
<p>
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpDeathLocation">
<p>
The location where the individual died.
</p>
</div>
<div class="balloon" id="HelpBuriedDate">
<p>The date the individual was buried.
<p>  
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpBuriedLocation">
<p>
The location where the individual was buried.  This is typically the name and
location of the cemetery.
</p>
</div>
<div class="balloon" id="HelpGender">
<p>
This is a selection list from which to choose the gender of the individual.
</p>
</div>
<div class="balloon" id="HelpPrivate">
<p>
If this selection list to control how much information is displayed to casual
viewers about this individual.  Selecting 'No' indicates that none of the
information about the individual is private, and as long as the individual
lived long enough ago that the basic information is not covered by privacy 
legislation then the information is displayed.  Selecting 'Yes' indicates that
no information is displayed about the individual but the person's name.
Selecting 'Invisible' indicates that even the existence of the individual
is hidden from casual viewers.  
</p>
</div>
<div class="balloon" id="HelpAncInterest">
<p>
Use this selection list to indicate how important the ancestors of this 
individual are to you.
</p>
</div>
<div class="balloon" id="HelpDecInterest">
<p>
Use this selection list to indicate how important the descendants of this 
individual are to you.
</p>
</div>
<div class="balloon" id="HelpPictures">
<p>
Clicking on this button opens up a
<a href="editPicturesHelpen.html" target="help">dialog</a>
that permits you to add or delete images associated with this individual.
</p>
</div>
<div class="balloon" id="HelpDeathCause">
<p>
The cause of death as provided by the coroner or medical attendant, 
usually from the death certificate.
</p>
</div>
<div class="balloon" id="HelpCPIdcs">
<p>
Select the most appropriate status of the child.  This is the status
as a result of birth.
</p>
</div>
<div class="balloon" id="HelpDetail">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to enter more details about the associated event.
This includes textual notes and source citations.
</p>
</div>
<div class="balloon" id="HelpClear">
<p>
Clicking on this button clears all of the information about the 
associated event.  The date and location are cleared to empty strings and
any citations to the event are deleted from the database.
</p>
</div>
<div class="balloon" id="HelpBaptismDate">
<p>The date of the LDS baptism.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpBaptismLocation">
<p>The location where the individual was baptized into the Church of Latter Day
Saints.  This is usually at a temple, but may be at another location.
</p>
</div>
<div class="balloon" id="HelpEndowmentDate">
<p>The date of the LDS endowment.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpEndowmentLocation">
<p>The temple where the Church of Latter Day Saints endowment was performed.
</p>
</div>
<div class="balloon" id="HelpConfirmationDate">
<p>The date of the LDS confirmation.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpConfirmationLocation">
<p>The location where the individual was confirmed in the Church of Latter Day
Saints.  This is usually at a temple, but may be at another location.
</p>
</div>
<div class="balloon" id="HelpInitiatoryDate">
<p>The date of the LDS Initiatory.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpInitiatoryLocation">
<p>The temple where the Church of Latter Day Saints initiatory was performed.
</p>
</div>
<div class="balloon" id="HelpEventDate">
<p>The date of the event.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href="datesHelpen.html" target="help">"Entering Dates"</a>.
</p>
</div>
<div class="balloon" id="HelpEventDescn">
<p>The description text associated with the event.  This is any information
that does not specify where the event took place.  For example for an
occupation event it is the title of the occupation.
</p>
</div>
<div class="balloon" id="HelpEventLocation">
<p>The location where the event took place.
</p>
</div>
<div class="balloon" id="HelpEventDetail">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to enter more details about the event.
This includes textual notes, and source citations;
</p>
</div>
<div class="balloon" id="HelpEventDelete">
<p>
Clicking on this button removes all of the information about the 
associated event and
any citations to the event are deleted from the database.
</p>
</div>
<div class="balloon" id="HelpAddEvent">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to add a new event onto this individual.
This includes textual notes, and source citations.
</p>
</div>
<div class="balloon" id="HelpOrder">
<p>Clicking on this button reorders the events and facts according to the date
on which they occurred or were observed.  This makes the displayed description
of the individual more coherent.
</p>
</div>
<div class="balloon" id="HelpUserRef">
<p>
The value of this field is a unique identifier that has meaning to you
as the author of the family tree.  For example it might represent how
the individual is related to you using one of the conventions for
representing descent from a common ancestor.
</p>
</div>
<div class="balloon" id="HelpAncestralRef">
<p>
The value of this field is a reference to the identifier of this individual
in the Church of Latter Day Saints Ancestral File database.
</p>
</div>
<div class="balloon" id="HelpNotes">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to enter extensive textual notes on this individual.
</p>
</div>
<div class="balloon" id="HelpReferences">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to enter textual research notes about your
investigation of this individual.
</p>
</div>
<div class="balloon" id="HelpMedical">
<p>
Clicking on this button opens up a
<a href="editEventHelpen.html" target="help">dialog</a>
that permits you to enter textual notes about the medical history
of this individual.
</p>
</div>
<div class="balloon" id="HelpNeverMarried">
<p>
Select this checkbox to indicate that it is known that this individual
never married.  Note that the checkbox is disabled, and displayed as a grey
square, if the individual is a defined as a member of a family.
</p>
</div>
<div class="balloon" id="HelpCPRelDad">
<p>
Use this selection box to specify the nature of the relationship between
this individual and his/her father.
</p>
</div>
<div class="balloon" id="HelpCPDadPrivate">
<p>
If this checkbox shows a checkmark then the nature of the relationship
between this individual and his/her father will not be published.
</p>
</div>
<div class="balloon" id="HelpCPRelMom">
<p>
Use this selection box to specify the nature of the relationship between
this individual and his/her mother.
</p>
</div>
<div class="balloon" id="HelpCPMomPrivate">
<p>
If this checkbox shows a checkmark then the nature of the relationship
between this individual and his/her mother will not be published.
</p>
</div>
<div class="balloon" id="HelpParents">
<p>
Clicking on this button opens up a
<a href="editParentsHelpen.html" target="help">dialog</a>
that permits you to edit or add a set of parents for this individual.
</p>
</div>
<div class="balloon" id="HelpMarriages">
<p>
Clicking on this button opens up a
<a href="editMarriagesHelpen.html" target="help">dialog</a>
that permits you to edit or add a family
for which this individual functions as a spouse or parent.
</p>
</div>
<div class="balloon" id="HelpEvents">
<p>
Clicking on this button opens up a
<a href="editEventsHelpen.html" target="help">dialog</a>
that permits you to edit or add events in the life of this individual.
</p>
</div>
<div class="balloon" id="HelpAddress">
<p>
Clicking on this button opens up a
<a href="AddressHelpen.html" target="help">dialog</a>
that permits you to add or update the mailing address 
and other contact information for this individual.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>
Clicking on this button applies all of the changes you have made in the
form to the database.
</p>
</div>
<div class="balloon" id="HelpDelete">
<p>
This button only appears if the individual has no connections to any other
family members.
Clicking on this button deletes the individual from the database.
</p>
</div>
<div class="balloon" id="HelpMerge">
<p>
Clicking on this button opens up a dialog that permits you to merge this
individual with another individual in the database.  You do this when as
a result of your research you realize that two records in the database
actually describe the same individual.
</p>
</div>
<div class="balloon" id="HelpGrant">
<p>
Clicking on this button displays a dialog permitting you to grant authority
to see the private information and update the current individual and the
current individuals ancestors and descendants to another researcher.
</p>
</div>
<div id="loading" class="popup">
Loading...
</div>
</body>
</html>
