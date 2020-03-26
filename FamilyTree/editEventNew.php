<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editEvent.php														*
 *																		*
 *  Display a web page for editting one event from the family tree      *
 *  database which is represented by an instance of Event.				*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		type	numeric type value as used by the Citation			    *
 *				record to identify a specific event and which			*
 *				record type it is defined in.  If omitted the default	*
 *				value is 0.												*
 *																		*
 *		    idir parameter points to Person record					    *
 *				STYPE_NAME				= 1								*
 *				STYPE_BIRTH				= 2								*
 *				STYPE_CHRISTEN			= 3								*
 *				STYPE_DEATH				= 4								*
 *				STYPE_BURIED			= 5								*
 *				STYPE_NOTESGENERAL		= 6								*
 *				STYPE_NOTESRESEARCH		= 7								*
 *				STYPE_NOTESMEDICAL		= 8								*
 *				STYPE_DEATHCAUSE		= 9								*
 *				STYPE_LDSB				= 15  LDS Baptism				*
 *				STYPE_LDSE				= 16  LDS Endowment				*
 *				STYPE_LDSC				= 26  LDS Confirmation			*
 *				STYPE_LDSI				= 27  LDS Initiatory			*
 *																		*
 *		    idnx parameter points to Alternate Name Record tblNX		*
 *				STYPE_ALTNAME			= 10							*
 *																		*
 *		    idcr parameter points to Child Record tblCR					*
 *				STYPE_CHILDSTATUS		= 11 Child Status		   		*
 *				STYPE_CPRELDAD			= 12 Relationship to Father  	*
 *				STYPE_CPRELMOM			= 13 Relationship to Mother  	*
 *				STYPE_LDSP				= 17 Sealed to Parents			*
 *																		*
 *		    idmr parameter points to LegacyMarriage Record				*
 *				STYPE_LDSS				= 18 Sealed to Spouse			*
 *				STYPE_NEVERMARRIED		= 19 This individual nvr married*
 *				STYPE_MAR				= 20 Marriage					*
 *				STYPE_MARNOTE			= 21 Marriage Note				*
 *				STYPE_MARNEVER			= 22 Never Married				*
 *				STYPE_MARNOKIDS			= 23 This couple had no children*
 *				STYPE_MAREND			= 24 marriage ended **added**	*
 *																		*
 *		    ider parameter points to Event Record						*
 *				STYPE_EVENT				= 30 Individual Event,			*
 *											idir mandatory				*
 *				STYPE_MAREVENT			= 31 Marriage Event,			*
 *											idmr mandatory				*
 *																		*
 *		    idtd parameter points to To-Do records tblTD.IDTD			*
 *				STYPE_TODO				= 40 To-Do Item		   			*
 *																		*
 *		    a temp source type, also any negative numbers are temporary	*
 *				STYPE_TEMP				= 100 used to swap sources. 	*
 *																		*
 *		idir	unique numeric key of instance of Person				*
 *				required as defined above or 							*
 *		ider	unique numeric key of instance of Event					*
 *				if set to zero with type=STYPE_EVENT or STYPE_MAREVENT	*
 *				causes new Event record to be created.					*
 *		idnx	unique numeric key of instance of Alternate Name		*
 *				Record tblNX											*
 *		idcr	unique numeric key of instance of Child Record tblCR	*
 *		idmr	unique numeric key of instance of LegacyMarriage Record	*
 *		idtd	unique numeric key of instance of To-Do records			*
 *				tblTD.IDTD												*
 * 																		*
 *		givenname optionally explicitly supply given name of individual *
 *				if DB copy may not be current							*
 *		surname	optionally explicitly supply surname of individual		*
 *				if DB copy may not be current							*
 *		date	optionally explicitly supply date of event if DB copy	*
 *				may not be current										*
 *		descn	optionally explicitly supply description of event if	*
 *				DB copy may not be current								*
 *		location optionally explicitly supply location of event if DB	*
 *				copy may not be current 								*
 *		notes	optionally explicitly supply notes for event if DB		*
 *				copy may not be current 								*
 *		rownum	feedback row number for common event					*
 * 																		*
 *  History: 															*
 *		2010/08/08		set $ider for newly created Event				*
 *		2010/08/09		add input field for Order value					*
 *		2010/08/11		use htmlspecialchars to escape text values		*
 *		2010/08/16		change to LegacyCitationList interface			*
 * 		2010/08/21		Change to use new page format					*
 *		2010/08/28		implement delete citation						*
 *		2010/09/05		Permit explictly supplying name of individual	*
 *		2010/10/11		Simplify interface for adding citations			*
 *		2010/10/15		Use cookies to default to last source citation	*
 *						Remove header and trailer sections from dialog.	*
 *						Support all event types, not just Event			*
 *		2010/10/16		Use Event->getNotes()							*
 *		2010/10/17		Import citTable.inc and citTable.js to manage	*
 *						citations										*
 *		2010/10/19		Ensure $notes is not null for NAME event		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/29		move Notes after Location in dialog				*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2010/11/14		include prefix and title in fields for Name		*
 *						event											*
 *		2010/12/04		add link to help page							*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *		2010/12/20		handle exception thrown by new LegacyIndiv		*
 *						handle exception thrown by new LegacyFamily		*
 *						handle exception thrown by new LegacyLocation	*
 *						improved handling of invalid parameters			*
 *		2011/01/02		add 4 LDS sacraments							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/01/22		clean up code									*
 *		2011/01/30		identify fact type in title of facts from		*
 *						indiv record									*
 *		2011/02/24		identify fact type in context specific help		*
 *						for notes										*
 *		2011/03/03		underline 'U' in "Update Event" button text		*
 *		2011/06/15		pass idmr to updateEvent.php					*
 *						support events in LegacyFamily record			*
 *		2011/07/29		handle new parameters date and location to		*
 *						supply explicit values of date and location		*
 *						of event										*
 *						Use LegacyLocation constructor to resolve		*
 *						short names										*
 *		2011/08/08		trim supplied location name						*
 *		2011/08/21		do not initially display Temple vs. Live kind	*
 *						row for generic event.							*
 *		2011/10/01		provide database lookup assist for setting		*
 *						location names									*
 *						document month name abbreviations in context	*
 *						help											*
 *						change name of class LegacyCitationList			*
 *		2011/11/19		display alternate names in the Name Event and	*
 *						provide a button to selectively delete an		*
 *						alternate name									*
 *		2011/12/23		always display married surnames					*
 *						display all events in dialog and permit adding,	*
 *						modifying, and deleting events.					*
 *						add help panels for all fields					*
 *		2012/01/08		reorder to put the event type before the date	*
 *		2012/01/13		change class names								*
 *						support supplying notes value through parm		*
 *						include <input type=checkbox> in flag events	*
 *						add "No Children" to list of marriage events	*
 *		2012/01/23		display loading indicator while waiting for		*
 *						response to changed in a location field			*
 *		2012/02/25		use tinyMCE for stylized editing of text notes	*
 *		2012/05/06		set explicit class for Order field				*
 *		2012/07/31		make names of individuals identified in the		*
 *						title of the event hyperlinks to the individual	*
 *						record											*
 *						add names of spouses to all marriage events		*
 *						expand date input field to display 24 characters*
 *		2012/08/01		permit invoker to explicitly override			*
 *						description field								*
 *		2012/08/12		support LDS sealed to parents event				*
 *						validate associated record for all events		*
 *						before using it									*
 *						permit setting temple ready indicator			*
 *		2012/10/17		do not attempt to create database objects if	*
 *						the numeric key is invalid						*
 *		2012/10/19		supplied given name and surname was not used	*
 *						by name event									*
 *		2012/10/30		ensure templeReady field default to unused		*
 *		2012/11/05		add support for tinyMCE editing of notes		*
 *		2012/11/22		Event::add removed and replaced by member		*
 *						method addEvent of LegacyIndiv and LegacyFamily	*
 *		2013/03/03		LegacyIndiv::getNextName now returns all		*
 *						alternate names									*
 *		2013/04/02		add support for citations for alternate names	*
 *		2013/04/24		add birth, marriage, and death registrations	*
 *		2013/05/26		use dialog in place of alert for new location	*
 *						name											*
 *		2013/07/04		for individual event recorded in instance of	*
 *						Event do not display event types recorded		*
 *						in other records.  This permits changing the	*
 *						event type without creating a new record		*
 *		2013/08/25		add clear button for note textarea				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/12		replace tables with CSS for layout				*
 *		2014/02/17		define local CSS for this form					*
 *		2014/02/19		add id to <form> 								*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *		2014/03/06		label class name changed to column1				*
 *		2014/03/10		ability to edit cause of death added to			*
 *						edit dialogue for normal death event so it		*
 *						can be removed from the edit Individual dialog	*
 *		2014/03/20		replace deprecated LegacyIndiv::getNumNames		*
 *						replace deprecated LegacyIndiv::getNextName		*
 *						wrap alternate name section of Name event in	*
 *						a fieldset for clarity							*
 *						wrap death cause section of Death event in		*
 *						a fieldset for clarity							*
 *						deprecated class LegacyCitationList replaced by	*
 *						calls to Citation::getCitations					*
 *		2014/04/08		LegacyAltName renamed to LegacyName				*
 *						management of citations to alternate names		*
 *						moved to EditName.php script					*
 *		2014/04/13		permit being invoked with just the IDER value	*
 *		2014/04/15		Display default citation while waiting for		*
 *						database server to respond to request for list	*
 *						of sources										*
 *						enable update of citation page number			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/04/30		refine headings for marriage events				*
 *		2014/05/30		use explicit style class actleftcit in			*
 *						template for new source citation to limit		*
 *						the width of the selection list to match the	*
 *						width of the display after the citation added	*
 *		2014/07/06		move textual interpretation of IDET here from	*
 *						Event class to support I18N						*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *						use LegacyTemple::getTemples to get list for	*
 *						<select>										*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/10/03		add support for associating instances of 		*
 *						Picture with an event.							*
 *		2014/10/15		events moved out of tblIR into tblER			*
 *		2014/11/19		provide alternative occupation input row		*
 *		2014/11/20		bad generated name for <input name="IDSR...">	*
 *		2014/11/27		use Event::getCitations							*
 *		2014/11/29		do not crash on new location					*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/04		global $debug not declared in function			*
 *						getDateAndLocation								*
 *		2014/12/12		missing parameter to LegacyTemple::getTemples	*
 *		2014/12/25		redirect debugging output to $warn				*
 *		2014/12/26		add rownum feedback parameter					*
 *		2015/03/07		use LegacyFamily::getHusbName and getWifeName	*
 *						instead of deprecated name fields				*
 *		2015/03/14		include Close button if errors					*
 *		2015/05/15		do not escape HTML tags in textarea, they are	*
 *						used by rich text editor						*
 *		2015/06/14		match field sizes in new citation to existing	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						display notes in a larger area					*
 *		2016/02/05		one trace message was printed instead of saved	*
 *		2016/02/06		use showTrace									*
 *		2017/01/03		undefined $checked								*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/15		class LegacyToDo renamed to class ToDo			*
 *		2017/09/12		use get( and set(								*
 *		2017/09/23		add a "Choose a Temple" option to temple select	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/02/11		add Close button								*
 *		2018/03/24		add button to control whether textareas are		*
 *						displayed as rich text or raw text				*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/08/01      support tinyMCE 5.0.3                           *
 *		2019/08/06      use editName.php to handle updates of Names     *
 *		2020/02/09      use Template                                    *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/********************************************************************
 *	function getDateAndLocation										*
 *																	*
 *		If the values for date and location have been explicitly	*
 *		provided, use them.  Otherwise obtain the values from the	*
 *		associated database record.									*
 *																	*
 *  Parameters:														*
 *	    $record				data base record as instance of Record	*
 *	    $dateFldName		field name containing date of event		*
 *	    $locFldName			field name containing IDLR of location	*
 *							of event								*
 ********************************************************************/
function getDateAndLocation($record,
						    $dateFldName,
						    $locFldName)
{
	global	$debug;
	global	$warn;
	global	$date;
	global	$location;	// instance of Location
	global	$msg;
	global	$idlr;

	if (is_null($date))
	{		// date value not explicitly supplied
	    $date	= new LegacyDate($record->get($dateFldName));
	    $date	= $date->toString();
	}		// date value not explicitly supplied

	if (is_null($location))
	{		// location value not explicitly supplied
	    $idlr	= $record->get($locFldName);
	    if ($debug)
			$warn	.= "<p>\$idlr set to $idlr from field name '$locFldName'</p>\n";
	    $location	= new Location(array('idlr' 		=> $idlr));
	}		// location value not explicitly supplied
}		// getDateAndLocation

/********************************************************************
 *	function getDateAndLocationLds									*
 *																	*
 *	If the values for date and location have been explicitly		*
 *	provided, use them.  Otherwise obtain the values from the		*
 *	associated database record.										*
 *																	*
 *  Parameters:														*
 *	    $record				data base record as instance of Record	*
 *	    $kind				temple indicator						*
 *	    $dateFldName		field name containing date of event		*
 *	    $locFldName			field name containing IDLR of location	*
 *							of event								*
 ********************************************************************/
function getDateAndLocationLds($record,
						       $kind,
                               $dateFldName,
                               $locFldName)
{
	global $date;
	global $location;
	global $msg;
	global $idtr;

	if (is_null($date))
	{		        // date value not explicitly supplied
	    $date	        = new LegacyDate($record->get($dateFldName));
	    $date	        = $date->toString();
	}		        // date value not explicitly supplied
	$idtr		        = $record->get($locFldName);
	if ($kind == 1)
	    $location	    = new Temple(array('idtr' 		=> $idtr));
	else
	{		        // not in temple
	    if (is_null($location))
	    {	        // do not have explicit location
			$location	= Location::getLocation($idtr);
	    }	// do not have explicit location
	}		// not in temple
}		// function getDateAndLocationLds

/********************************************************************
 *	function getEventInfo											*
 *																	*
 *	Get information from an instance of Event						*
 *																	*
 *  Parameters:														*
 *	    $event				instance of Event						*
 ********************************************************************/
function getEventInfo($event)
{
	global	$etype;
	global	$idet;
	global	$order;
	global	$notes;
	global	$descn;
	global	$kind;
	global	$templeReady;
	global	$preferred;
    global	$template;

    $eventText              = $template['eventText'];
	if ($idet <= 1)
	    $idet	            = $event['idet'];	// numeric key of tblET
	$etype		            = $eventText[$idet];
	$order		            = $event['order'];

	if (is_null($notes))
	{
	    $notes	            = $event['desc'];
	    if (is_null($notes))
			$notes	        = '';
	}

	if (is_null($descn))
	    $descn	            = $event['description']; 

	$templeReady	        = $event['ldstempleready'];
	$preferred	            = $event['preferred'];

	$kind		            = $event['kind'];
	if ($kind == 0)
	    getDateAndLocation($event,
					       'eventd',
					       'idlrevent');
	else
	    getDateAndLocationLds($event,
				    		  $kind,
				    		  'eventd',
				    		  'idlrevent');
}	// function getEventInfo

/********************************************************************
 *   OO  PPP  EEEE N  N     CC   OO  DDD  EEEE						*
 *  O  O P  P E    NN N    C  C O  O D  D E							*
 *  O  O PPP  EEE  N NN    C    O  O D  D EEE						*
 *  O  O P    E    N NN    C  C O  O D  D E							*
 *   OO  P    EEEE N  N     CC   OO  DDD  EEEE						*
 ********************************************************************/

// default title
$title						= 'Edit Event Error';
$heading					= 'Edit Event Error';

// safely get parameter values
// defaults
// parameter values from URI
$type						= 0;
$typetext				    = '';
$ider						= null;	// index of Event
$idertext				    = '';
$idet						= null;	// index of EventType
$idettext				    = '';
$idir						= null;	// index of Person
$idirtext				    = '';
$idnx						= null;	// index of Name
$idnxtext				    = '';
$idcr						= null;	// index of Child
$idcrtext				    = '';
$idmr						= null;	// index of Family
$idmrtext				    = '';
$idtd						= null;	// index of ToDo
$idtdtext				    = '';
$etype						= null;
$order						= null;
$idlr						= null;
$idlrtext				    = '';
$idtr						= null;
$idtrtext				    = '';
$kind						= null;
$kindtext				    = '';
$prefix						= null;
$nametitle					= null;
$templeReady				= null;
$preferred					= null;
$date						= null;
$descn						= null;
$occupation					= null;
$location					= null;
$notes						= null;
$notmar						= null;
$nokids						= null;
$cremated					= null;
$deathCause					= null;
$picIdType					= null; // for invoking EditPictures dialog
$given						= '';
$surname					= '';
$rownum						= null;
$lang               		= 'en';

// database records
$event						= null;	// instance of Event
$person						= null;	// instance of Person
$family						= null;	// instance of Family
$child						= null;	// instance of Child
$altname					= null;	// instance of Name
$todo						= null;	// instance of ToDo

// other
$submit						= false;

// process input parameters from the search string passed by method=get
if (isset($_GET) && count($_GET) > 0)
{
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                              "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
	        case 'type':
	        {		// supplied event type
                if (ctype_digit($value) && $value > 0)
		            $type	        = intval($value);
                else
                    $typetext       = $value;
	            break;
	        }		 // supplied event type

	        // get the event record identifier if present
	        case 'ider':
	        {
                if (ctype_digit($value))
		            $ider	        = intval($value);
                else
                    $idertext       = $value;
		        break;
	        }		            // IDER

	        // get the event type identifier if present
	        case 'idet':
	        {
	            $idet	            = $value;
	            break;
	        }		            // IDET

	        // get the key of instance of Person
	        case 'idir':
	        {
                if (ctype_digit($value) && $value > 0)
	                $idir		= intval($value);
                else
                    $idirtext   = $value;
	            break;
	        }		            // IDIR

	        case 'idnx':
	        {	                // get the key of instance of Name record
                if (ctype_digit($value) && $value > 0)
	                $idnx	        = intval($value);
                else
                    $idnxtext       = $value;
	            break;
	        }		            // IDNX

	        case 'idcr':
	        {		            // key of instance of Child record 
                if (ctype_digit($value) && $value > 0)
	                $idcr	        = intval($value);
                else
                    $idcrtext       = $value;
	            break;
	        }		            // IDCR

	        case 'idmr':
	        {		// key of instance of LegacyMarriage Record
                if (ctype_digit($value) && $value > 0)
	                $idmr	        = intval($value);
                else
                    $idmrtext       = $value;
	            break;
	        }		//idmr

	        case 'idtd':
	        {		// key of instance of To-Do records tblTD.IDTD
                if (ctype_digit($value) && $value > 0)
	                $idtd	        = intval($value);
                else
                    $idtdtext       = $value;
	            break;
	        }		// idtd

	        // individual's name can be explicitly supplied for events
	        // associated with
	        // a new individual if that information is not available from the
	        // database record because it has not been written yet
	        case 'givenname':
	        {
	            $given		        = $value;
	            break;
	        }		// given name

	        case 'surname':
	        {		// surname	
	            $surname	        = $value;
	            break;
	        }		// surname

	        // the date, location, and notes field values in the DB record may
	        // not be current as a result of user activity
	        case 'date':
	        {		// date of event as an external string
	            $date		        = $value;
	            break;
	        }		// date

	        case 'descn':
	        {		// description of the event
	            $descn		        = $value;
	            break;
	        }		// descn

	        case 'location':
	        {		// location of the event
	            $location	        = trim($value);
	            break;
	        }		// location

	        case 'idtr':
	        {		// key of temple
                if (ctype_digit($value) && $value > 0)
	                $idtr	        = intval($value);
                else
                    $idtrtext       = $value;
	            break;
	        }		// key of temple

	        case 'rownum':
	        {		// rownum for feedback about the event
	            $rownum		= trim($value);
	            break;
	        }		// rownum for feedback about the event

	        case 'notes':
	        {		// notes about the event
	            $notes		= trim($value);
	            break;
	        }		// notes about the event

	        case 'submit':
	        {		// control whether uses AJAX or submit
	            if (strtoupper($value) == 'Y')
	                $submit	= true;
	            break;
	        }		// control whether uses AJAX or submit

	        case 'debug':
	        {		// debug
	            if (strtoupper($value) == 'Y')
	                $submit	= true;
	            break;
	        }		// debug


	        case 'lang':
	        {
                $lang       = FtTemplate::validateLang($value);
	            break;
	        }

	        case 'text':
	        case 'editnotes':
	        {
	            break;
	        }           // used by Javascript

	        default:
	        {		    // other parameters
	            $warn	.= "<p>Unexpected parameter $key='$value'</p>\n";
	            break;
	        }		    // other parameters
	    }	            // switch
	}		            // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';

// get template
$template               = new FtTemplate("editEvent$action$lang.html",
                                         true);
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

// validate IDER
if (strlen($idertext) > 0)
{
    $text               = $template['invalidIDER']->innerHTML;
    $msg	            = str_replace('$ider', $idertext, $text) . '. ';
}
else
if (is_numeric($ider) && $ider > 0)
{               // existing IDER supplied
    $event		        = new Event(array('ider' 		=> $ider));
    if ($event->isExisting())
	{           // existing event
		$idet		    = $event['idet'];
        if ($event['idtype'] == 0)
        {	    // individual event
            $idir	    = $event['idir'];
            $type	    = 30;
        }	    // individual event
        else
        {	    // marriage event
            $idmr	    = $event['idir'];
            $type	    = 31;
        }	    // married event
    }		    // existing event
    else
    {		    // new event
        $event	        = null;		// is done later
        $text           = $template['invalidIDER']->innerHTML;
        $msg	        .= str_replace('$ider', $ider, $text);
    }		    // new event
}               // valid numeric
else
{               // create new event
}               // create new event

// textual description of event type
if (strlen($typetext) > 0)
{               // invalid syntax
    $text               = $template['invalidEventType']->innerHTML;
    $eventType	        = str_replace('$type', $typetext, $text);
    $msg                .= $eventType . '. ';
}               // invalid syntax
else
if (is_numeric($type) && array_key_exists($type, Citation::$intType))
{
    $intType            = $template['typeText'];
    $eventType	        = $intType[$type];
}
else
{
    $text               = $template['invalidEventType']->innerHTML;
    $eventType	        = str_replace('$type', $type, $text);
}

// key of instance of Name
if (strlen($idnxtext) > 0)
{               // invalid syntax
    $text               = $template['invalidIDNX']->innerHTML;
    $msg	            .= str_replace('$idnx', $idnxtext, $text);
}               // invalid syntax
else
if (is_numeric($idnx) && $idnx > 0)
{               // existing IDNX supplied
	$altname		    = new Name(array('idnx'			=> $idnx));
    if ($altname->isExisting())
    {
	    $idir		    = $altname['idir'];
        $person		    = new Person(array('idir' 		=> $idir));
        $isOwner		= $person->isOwner();
        if (!$isOwner)
            $msg	    .= 'You are not authorized to edit this Name.  ';
        $idmr		    = $altname['idmr'];
        $family		    = new Family(array('idmr' 		=> $idmr));
    }
    else
	{		    // new Name failed
		$altname	    = null;
        $text           = $template['invalidIDNX']->innerHTML;
        $msg	        .= str_replace('$idnx', $idnx, $text);
		$idnx	        = null;
	}		    // new Name failed
}               // existing IDNX supplied

// key of instance of Child
if (strlen($idcrtext) > 0)
{               // invalid syntax
    $text               = $template['invalidIDCR']->innerHTML;
    $msg	            .= str_replace('$idcr', $idcrtext, $text);
}               // invalid syntax
else
if (is_numeric($idcr) && $idcr > 0)
{               // existing IDCR supplied
	$child	            = new Child(array('idcr'			=> $idcr));
    if ($child->isExisting())
    {
	    $idir		    = $child['idir'];
        $person		    = new Person(array('idir' 		=> $idir));
        $isOwner		= $person->isOwner();
        if (!$isOwner)
            $msg	    .= 'You are not authorized to edit " .
                            "the events of this child.  ';
        $idmr		    = $child['idmr'];
        $family		    = new Family(array('idmr' 		=> $idmr));
    }
    else
	{		    // Child does not exist
		$child	        = null;
        $text           = $template['invalidIDCR']->innerHTML;
        $msg	        .= str_replace('$idcr', $idcr, $text);
		$idcr	        = null;
	}		    // Child does not exist
}               // existing IDCR supplied

// key of instance of Person
if (strlen($idirtext) > 0)
{               // invalid syntax
    $text               = $template['invalidIDIR']->innerHTML;
    $msg	            .= str_replace('$idir', $idirtext, $text);
}               // invalid syntax
else
if (is_numeric($idir) && $idir > 0)
{               // existing IDIR supplied
	$person	            = new Person(array('idir'			=> $idir));
    if ($person->isExisting())
    {
        $isOwner		= $person->isOwner();
        if (!$isOwner)
            $msg	    .= 'You are not authorized to edit the events of this person.  ';
        // if name of individual not supplied,
        // get it from Person record
        if (strlen($given) == 0)
            $given		= $person->getGivenname();
        if (strlen($surname) == 0)
            $surname	= $person->getSurname();
        $heading	    = "Edit Event for " .
                    "<a href=\"Person.php?idir=$idir\">$given $surname</a>";
    }
    else
	{		    // Person does not exist
        $person	        = null;
        $idime	        = -1;
        $given	        = '';
        $surname	    = '';
        $text           = $template['invalidIDIR']->innerHTML;
        $msg	        .= str_replace('$idir', $idir, $text);
		$idir	        = null;
	}		    // Person does not exist
}               // existing IDIR supplied

// key of instance of Family
if (strlen($idmrtext) > 0)
{                   // invalid syntax
    $text               = $template['invalidIDMR']->innerHTML;
    $msg	            .= str_replace('$idmr', $idmrtext, $text);
}                   // invalid syntax
else
if (is_numeric($idmr) && $idmr > 0)
{                   // existing IDMR supplied
	$family	            = new Family(array('idmr'			=> $idmr));
    if ($family->isExisting())
    {
        $husbname		= $family->getHusbName();
        $idirhusb		= $family['idirhusb'];
        $wifename		= $family->getWifeName();
        $idirwife		= $family['idirwife'];
        $heading	    = "Edit Event for Family of ";
        if ($idirhusb > 0)
        {		    // husband identified
            $heading	.= "<a href=\"Person.php?idir=$idirhusb\">$husbname</a>";
            if ($idirwife > 0)
            {	    // both spouses identified
                $heading	.= " and ";
            }	    // both spouses identified
        }		    // husband identified

        if ($idirwife > 0)
        {		    // wife identified
            $heading	.= "<a href=\"Person.php?idir=$idirwife\">$wifename</a>";
        }		    // wife identified
    }
    else
	{		        // Family does not exist
        $family	        = null;
        $idime	        = -1;
        $given	        = '';
        $surname	    = '';
        $text           = $template['invalidIDMR']->innerHTML;
        $msg	        .= str_replace('$idmr', $idmr, $text);
	    $idmr	        = null;
    }		        // Family does not exist
}                   // existing IDMR supplied

$idetTitleText          = $template['idetTitleText'];

// validate the presence of parameters depending upon
// the value of the type parameter

// identify the fields in the associated record that are
// updated for each type of event

// default that all fields are unsupported
if ($ider === 0 & $idet > 1)
{
    $event				= new Event(array('ider'			=> 0,
                                          'idet'			=> $idet,
                                          'idir'			=> $idir));
    $event->save(false);
    $ider				= $event['ider'];
    $idime				= $ider;	// key for citations
}

$eventList              = $template['personEvents'];
switch($type)
{		            // take action according to type
    case Citation::STYPE_UNSPECIFIED:		// 0;
    {	            // type not determined yet
        // will be either IDCR, IDIR, IDMR, or IDER based event
        if (is_numeric($idcr) && $idcr > 0)
        {		    // IDCR based event
            $idime	    = $idcr;
            $text       = $template['headingGenericChild']->innerHTML;
            $heading    = str_replace(array('$idir','$lang','$given','$surname'),
                                      array($idir,$lang,$given,$surname),
                                      $text);
        }		    // IDCR based event
        else
        if (is_numeric($idir) && $idir > 0)
        {		    // IDIR based event
            $idime	    = $idir;
            $text       = $template['headingGenericPerson']->innerHTML;
            $heading    = str_replace(array('$idir','$lang','$given','$surname'),
                                      array($idir,$lang,$given,$surname),
                                      $text);
        }		    // IDIR based event
        else
        if (is_numeric($idmr) && $idmr > 0)
        {		    // IDMR based event
            $idime	    = $idmr;
            $text       = $template['headingGenericFamily']->innerHTML;
            $heading    = str_replace(array('$idirhusb','$idirwife','$lang','$husbname','$wifename'),
                                      array($idirhusb,$idirwife,$lang,$husbname,$wifename),
                                      $text);
        }		    // IDMR based event
        else
        {
            $msg	    .= $template['missingIDIME'];
        }

        $etype	        = '';
        $idet	        = 0;
        break;
    }	            // type not determined yet

    // idir parameter points to Person record
 	case Citation::STYPE_NAME:		// 1
    {
        if (is_null($idir) || $idir == 0)
        {		        // individual event requires IDIR
            $msg		.= 'mandatory idir parameter missing. ';
            $given		= $t['Unknown'];
        }		        // individual event requires IDIR
        else
        {		        // edit is performed by editName.php
            $name       = new Name(array('idir'     => $idir,
                                         'order'    => Name::PRIMARY));
            $idnx       = $name['idnx'];
            header("Location: /FamilyTree/editName.php?idnx=$idnx");
            exit;
        }		        // edit is performed by editName.php
        break;
    }                   // primary name of individual

 	case Citation::STYPE_BIRTH:		    // 2
 	case Citation::STYPE_CHRISTEN:		// 3
 	case Citation::STYPE_DEATH:		    // 4
 	case Citation::STYPE_BURIED:		// 5
 	case Citation::STYPE_NOTESGENERAL:	// 6
 	case Citation::STYPE_NOTESRESEARCH:	// 7
 	case Citation::STYPE_NOTESMEDICAL:	// 8
 	case Citation::STYPE_DEATHCAUSE:	// 9
 	case Citation::STYPE_LDSB:	    	// 15  LDS Baptism
 	case Citation::STYPE_LDSE:	    	// 16  LDS Endowment
 	case Citation::STYPE_LDSC:	    	// 26  LDS Confirmation
 	case Citation::STYPE_LDSI:	    	// 27  LDS Initiatory
    {
        if (is_null($idir) || $idir == 0)
        {		// individual event requires IDIR
            $msg		    .= 'mandatory idir parameter missing. ';
            $given		    = 'Unknown';
        }		// individual event requires IDIR
        else
        {		// proceed with edit
            $idime		    = $idir;	// key for citations
            $heading	    = "Edit " . $eventType .
    " for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
            if ($type <= Citation::STYPE_BURIED &&
 		        $type >= Citation::STYPE_BIRTH)
                $picIdType	= $type - 1;
        }		// proceed with edit
        break;
    }               // individual event

    //    idnx parameter points to Alternate Name Record tblNX
 	case Citation::STYPE_ALTNAME:	// 10
    {
        if (is_null($idnx) || $idnx == 0)
            $msg		.= 'Mandatory idnx parameter missing. ';
        else
        {
            header("Location: /FamilyTree/editName.php?idnx=$idnx");
            exit;
        }
        break;
    }

    // idcr parameter points to Child Record tblCR
 	case Citation::STYPE_CHILDSTATUS:	// 11 Child Status	   
 	case Citation::STYPE_CPRELDAD:		// 12 Relationship to Father  
 	case Citation::STYPE_CPRELMOM:		// 13 Relationship to Mother  
 	case Citation::STYPE_LDSP:		    // 17 Sealed to Parents
    {
        if (is_null($idcr) || $idcr == 0)
            $msg		.= 'Mandatory idcr parameter missing. ';
        else
            $idime	= $idcr;	// key for citations
        $heading	= "Edit " . $eventType .
            " for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
        $eventList              = $template['childEvents'];
        break;
    }

    //    idmr parameter points to LegacyMarriage Record
 	case Citation::STYPE_LDSS:		    // 18 Sealed to Spouse
 	case Citation::STYPE_NEVERMARRIED:	// 19 individual never married 
 	case Citation::STYPE_MAR:		    // 20 Marriage	
 	case Citation::STYPE_MARNOTE:		// 21 Marriage Note
 	case Citation::STYPE_MARNEVER:		// 22 Never Married
 	case Citation::STYPE_MARNOKIDS:		// 23 No children  
 	case Citation::STYPE_MAREND:		// 24 marriage end date
    {		// event defined in marriage record
        $heading		        = "Edit " . $eventType;
        if (!$idmr || $idmr == 0)
        {
            $msg		        .= 'Mandatory idmr parameter missing. ';
        }
        else
        {
            $idime		        = $idmr;	// key for citations
            if ($family)
            {		// family specified
                $heading	    .= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
 		    if ($type == Citation::STYPE_MAR)
                    $picIdType	= Picture::IDTYPEMar;
            }		// family specified
        }
        $eventList              = $template['marriageEvents'];
        break;
    }		// event defined in marriage record

    //    ider parameter points to Event Record
 	case Citation::STYPE_EVENT:	// 30 Individual Event
    {
        if (is_null($event))
        {
            $event	= new Event(array('ider' 		=> 0,
                                      'idir' 		=> $idir));
        }

        // get the supplied value of the event subtype
        if ($idet > 1)
            $event->setIdet($idet);

        $idime	                = $ider;	// key for citations
        $idir	                = $event['idir'];
        if ($debug)
            $warn	.= "<p>\$idir set to $idir from event IDER=$ider</p>\n";
        if (is_null($person))
            $person	            = Person::getPerson($idir);
        if ($person->isExisting())
        {
            if ($ider == 0 && $idet > 1)
            {		// create new individual event
                $event	        = $person->addEvent();
                $ider	        = $event['ider'];
            }		// create new individual event

            // if name of individual not supplied, get it from Person record
            if (strlen($given) == 0)
                $given		    = $person->getGivenName();
            if (strlen($surname) == 0)
                $surname	    = $person->getSurname();
            $typetext	        =  $idetTitleText[$idet];	
            $heading            = "Edit $typetext Event for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
            $picIdType	        = Picture::IDTYPEEvent;
        }		// try creating individual
        else
        {		// error creating individual
            $person				= null;
            $idime				= -1;
            $given				= '';
            $surname			= '';
            $heading			= 'Invalid Value of IDIR';
            $msg		        .= 'Unable to create individual event because idir parameter missing or invalid. ';
        }		// error creating individual
        break;
    }

 	case Citation::STYPE_MAREVENT:	// 31 Marriage Event
    {
        if ($family)
        {		// family specified
            $heading            .= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
        }		// family specified

        if ($ider == 0)
        {		// create new marriage event
            if (!is_null($family))
            {
                $event	        = $family->addEvent();
                $ider	        = $event['ider'];

                // set the supplied value of the event subtype
                if (!is_null($idet))
                    $event->setIdet($idet);
            }
            else
            {
                $msg	        .= 'Unable to create family event because idmr parameter missing or invalid. ';
            }
        }		// create new event
        else
        {		// existing event
            $idmr		        = $event['idir'];
            $family		        = new Family(array('idmr' 		=> $idmr));
            $tidet		        = $event['idet'];
            if ($tidet == 70)
                $heading	    = 'Edit ' . ucfirst($event['description']) . ' Event';
            else
                $heading	    = 'Edit ' . ucfirst(Event::$eventText[$tidet]) . ' Event';

            $heading	        .= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
        }		// existing event

        $idime	                = $ider;	// key for citations
        $picIdType	            = Picture::IDTYPEEvent;
        $eventList              = $template['marriageEvents'];
        break;
    }

    //    idtd parameter points to To-Do records tblTD.IDTD
 	case Citation::STYPE_TODO:		// 40 To-Do Item
    {
        if (is_null($idtd) || $idtd == 0)
        {
            $msg		.= 'Mandatory idtd parameter missing. ';
            $todo		= null;
            break;
        }
        $idime	        = $idtd;	// key for citations
        $heading	    = "Edit To Do Fact: IDTD=$idtd";
        break;
    }

    default:
    {
        $msg	        .= 'Invalid event type ' . $type;
        $idime	        = -1;
        $heading	    = 'Invalid Event Type'; 
    }
}		// take action according to type

switch($type)
{		// act on major event type
    case Citation::STYPE_UNSPECIFIED:	// 0
    {	// to be determined
        break;
    }	// to be determined

    case Citation::STYPE_NAME:		// 1
    {
        if ($person)
        {
            if (is_null($notes))
            {
                $notes	= $person['namenote'];
                if (is_null($notes))
                    $notes	= '';
            }

            $prefix	= $person['prefix'];
            if (is_null($prefix))
                $prefix	= '';

            $nametitle	= $person['title'];
            if (is_null($nametitle))
                $nametitle	= '';
        }		// individual defined
        break;
    }

    case Citation::STYPE_BIRTH:		// 2
    {
        if ($person)
        {
            $event		= $person->getBirthEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
            $kind		= null;
        }		// individual defined
        break;
    }

    case Citation::STYPE_CHRISTEN:		// 3
    {
        if ($person)
        {
            $event		= $person->getChristeningEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
            $kind		= null;
        }		// individual defined
        break;
    }

    case Citation::STYPE_DEATH:		// 4
    {
        if ($person)
        {
            $event		= $person->getDeathEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
            $kind		= null;

            $deathCause	= $person['deathcause'];
            if (is_null($deathCause))
                $deathCause	= '';
        }		// individual defined
        break;
    }

    case Citation::STYPE_BURIED:		// 5
    {
        if ($person)
        {
            $event		    = $person->getBuriedEvent(true);
            $ider	        = $event['ider'];
            if ($ider > 0)
            {
                $idime	    = $ider;
                $type	    = Citation::STYPE_EVENT;
            }
            getEventInfo($event);
            $kind		    = null;
            if ($descn == '')
            {
                $descn	    = null;
                $cremated	= false;
            }
            else
            if ($descn == 'cremated')
            {
                $descn	    = null;
                $cremated	= true;
            }
            else
                $cremated	= false;
        }		// individual defined
        break;
    }

    case Citation::STYPE_NOTESGENERAL:	// 6
    {
        if ($person)
        {
            if (is_null($notes))
            {
                $notes	= $person['notes'];
                if (is_null($notes))
                    $notes	= '';
            }
        }		// individual defined
        break;
    }

    case Citation::STYPE_NOTESRESEARCH:	// 7
    {
        if ($person)
        {
            $date	= null;
            $location	= null;
            if (is_null($notes))
            {
                $notes	= $person['references'];
                if (is_null($notes))
                    $notes	= '';
            }
        }		// individual defined
        break;
    }

    case Citation::STYPE_NOTESMEDICAL:	// 8
    {
        if ($person)
        {
            if (is_null($notes))
            {
                $notes	= $person['medical'];
                if (is_null($notes))
                    $notes	= '';
            }
        }		// individual defined
        break;
    }

    case Citation::STYPE_DEATHCAUSE:	// 9
    {
        if ($person)
        {
            if (is_null($notes))
            {
                $notes	= $person['deathcause'];
                if (is_null($notes))
                    $notes	= '';
            }
        }		// individual defined
        break;
    }

    case Citation::STYPE_LDSB:		// 15
    {
        if ($person)
        {
            $event		= $person->getBaptismEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
        }		// individual defined
        break;
    }

    case Citation::STYPE_LDSE:		// 16
    {
        if ($person)
        {
            $event		= $person->getEndowEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
        }		// individual defined
        break;
    }

    case Citation::STYPE_LDSC:		// 26
    {
        if ($person)
        {
            $event		= $person->getConfirmationEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
        }		// individual defined
        break;
    }

    case Citation::STYPE_LDSI:		// 27
    {
        if ($person)
        {
            $event		= $person->getInitiatoryEvent(true);
            $ider	= $event['ider'];
            if ($ider > 0)
            {
                $idime	= $ider;
                $type	= Citation::STYPE_EVENT;
            }
            getEventInfo($event);
        }		// individual defined
        break;
    }

 	case Citation::STYPE_ALTNAME:		// 10
    {
        $notes	= '';
        break;
    }

    //    idcr parameter points to Child Record tblCR
 	case Citation::STYPE_CHILDSTATUS:	// 11 Child Status	   
    {
        if ($child)
        {
            $notes	= '';
        }		// child record present
        break;
    }

 	case Citation::STYPE_CPRELDAD:	// 12 Relationship to Father  
    {
        if ($child)
        {
            $notes	= '';
        }		// child record present
        break;
    }

 	case Citation::STYPE_CPRELMOM:	// 13 Relationship to Mother  
    {
        if ($child)
        {
            $notes	= '';
        }		// child record present
        break;
    }

 	case Citation::STYPE_LDSP:	// 17 Sealed to Parents
    {
        if ($child)
        {
            getDateAndLocationLds($child,
                              1,
                              'parseald',
                              'idtrparseal');
            $notes		= $child['parsealnote'];
            if (is_null($notes))
                $notes	= '';
            $templeReady	= $child['ldsp'];
        }		// child record present
        break;
    }

    //    idmr parameter points to LegacyMarriage Record
 	case Citation::STYPE_LDSS:	// 18 Sealed to Spouse
    {
        if ($family)
        {
            getDateAndLocationLds($family,
                              1,
                              'seald',
                              'idtrseal');
            $templeReady	= $family['ldss'];
        }		// family defined
        break;
    }

 	case Citation::STYPE_NEVERMARRIED:// 19 individual never married 
 	case Citation::STYPE_MARNEVER:	// 22 Never Married
    {
        if ($family)
        {
        $notmar	= $family['notmarried'];
        if ($notmar == '')
            $notmar	= 0;
        }		// family defined
        break;
    }

 	case Citation::STYPE_MAR:		// 20 Marriage	
    {
        if ($family)
        {
        getDateAndLocation($family,
                        'mard',
                        'idlrmar');
        }		// family defined
        break;
    }

 	case Citation::STYPE_MARNOTE:	// 21 Marriage Note
    {
        if (is_null($family && $notes))
        {
            $notes	= $family['notes'];
            if (is_null($notes))
                $notes	= '';
        }		// family defined
        break;
    }

 	case Citation::STYPE_MARNOKIDS:	// 23 couple had no children  
    {
        if ($family)
        {
        $nokids	= $family['nochildren'];
        if ($nokids == '')
            $nokids	= 0;
        }		// family defined
        break;
    }

 	case Citation::STYPE_MAREND:	// 24 marriage ended date
    {
        if ($family)
        {
        $date	= new LegacyDate($family['marendd']);
        $date	= $date->toString();
        }		// family defined
        break;
    }
 	case Citation::STYPE_EVENT:	// 30 Individual Event
    {
        if ($event)
        {
            getEventInfo($event);
            $kind		= null;

            if ($idet == Event::ET_DEATH)
            {
                $deathCause	= $person['deathcause'];
                if (is_null($deathCause))
                    $deathCause	= '';
            }
        }		// event defined
        break;
    }	// Citation::STYPE_EVENT

 	case Citation::STYPE_MAREVENT:	// 31 Marriage Event
    {
        if ($event)
        {
            getEventInfo($event);
            $kind		= null;
        }		// event defined
        break;
    }	// Citation::STYPE_MAREVENT

    //    idtd parameter points to To-Do records tblTD.IDTD
 	case Citation::STYPE_TODO:	// 40 To-Do Item
    {
        $notes	= '';
        break;
    }

    default:				// unsupported values
    {
        break;
    }

}		// act on major event type

/********************************************************************
 *  If the location is in the form of a string, obtain the			*
 *  associated instance of Location.  This will ensure that			*
 *  short form names are resolved, and the name is displayed with	*
 *  the proper case. Also format the location name so that it can	*
 *  be inserted into the value attribute of the text input field.	*
 ********************************************************************/
if (!is_null($location))
{		            // location supplied
    if (is_string($location))
    {
        $locName	    = $location;
        $location	    = new Location(array('location' 		=> $locName));
        if (!$location->isExisting())
            $location->save(false);
        $idlr	        = $location->getIdlr();
    }
    $locName	        = str_replace('"','&quot;',$location->getName());
}		            // location supplied
else	            // location not supplied
    $locName	        = '';

// set insertion values
if (is_null($type))
	$template->set('TYPE',			    '');
else
	$template->set('TYPE',			    $type);
if (is_null($ider))
	$template->set('IDER',			    '');
else
	$template->set('IDER',			    $ider);
if (is_null($idet))
	$template->set('IDET',			    '');
else
	$template->set('IDET',			    $idet);
if (is_null($idir))
	$template->set('IDIR',			    '');
else
	$template->set('IDIR',			    $idir);
if (is_null($idnx))
	$template->set('IDNX',			    '');
else
	$template->set('IDNX',			    $idnx);
if (is_null($idcr))
	$template->set('IDCR',			    '');
else
	$template->set('IDCR',			    $idcr);
if (is_null($idmr))
	$template->set('IDMR',			    '');
else
	$template->set('IDMR',			    $idmr);
if (is_null($idlr))
{
    $template->set('IDLR',			    '');
    $template['locationRow']->update(null);
}
else
	$template->set('IDLR',			    $idlr);
if (is_null($idtd))
	$template->set('IDTD',			    '');
else
	$template->set('IDTD',			    $idtd);
if (is_null($date))
{
    $template->set('DATE',			    '');
    $template['dateRow']->update(null);
}
else
	$template->set('DATE',			    $date);
if (is_null($descn))
{
    $template->set('DESCN',			    '');
    $template['descRow']->update(null);
    $template['occRow']->update(null);
}
else
{
    $template->set('DESCN',			    $descn);
    if ($idet == Event::ET_OCCUPATION ||
        $idet == Event::ET_OCCUPATION_1)
        $template['descRow']->update(null);
    else
        $template['occRow']->update(null);
}
if (is_null($location))
	$template->set('LOCATION',			'');
else
	$template->set('LOCATION',			$location);
if (is_null($notes))
	$template->set('NOTES',			    '');
else
	$template->set('NOTES',			    $notes);
if (is_null($notmar))
	$template->set('NOTMAR',			'');
else
	$template->set('NOTMAR',			$notmar);
if (is_null($nokids))
	$template->set('NOKIDS',			'');
else
	$template->set('NOKIDS',			$nokids);
if (is_null($cremated))
	$template->set('CREMATED',			'');
else
	$template->set('CREMATED',			$cremated);
if (is_null($deathCause))
	$template->set('DEATHCAUSE',		'');
else
	$template->set('DEATHCAUSE',		$deathCause);
if (is_null($picIdType))
	$template->set('PICIDTYPE',			'');
else
	$template->set('PICIDTYPE',			$picIdType);
if (is_null($given))
	$template->set('GIVEN',			    '');
else
	$template->set('GIVEN',			    $given);
if (is_null($surname))
	$template->set('SURNAME',			'');
else
	$template->set('SURNAME',			$surname);
if (is_null($rownum))
	$template->set('ROWNUM',			'');
else
	$template->set('ROWNUM',			$rownum);
if (is_null($lang))
	$template->set('LANG',			    '');
else
	$template->set('LANG',			    $lang);
if (is_null($locName))
	$template->set('LOCNAME',			'');
else
	$template->set('LOCNAME',			$locName);
if (is_null($kind))
{
    $template->set('KIND',			    '');
    $template['kindRow']->update(null);
    $template['templeLabel']->update(null);
    $template['temple']->update(null);
}
else
{
    $template->set('KIND',			    $kind);
    if (is_numeric($idlr))
    {
        if ($kind == 0)
        {
            $template['templeLabel']->update(null);
            $template['temple']->update(null);
        }
        else
        {
            $template['locationLabel']->update(null);
            $template['location']->update(null);
            $temples            = new RecordSet('Temples',
                                                array('offset'  => 0));
            $data               = '';
            if ($idlr == 0)
                $template->set('SELECTEDIDTR0',     'selected="selected"');
            $relement           = $template['temple'];
            $text               = $relement->outerHTML;
            foreach($temples as $idtr => $temple)
            {
                $rtemplate      = new Template($text);
                $rtemplate->set('idtr',         $idtr);
                $rtemplate->set('temple',       $temple['temple']);
                if ($idlr == $idtr)
                    $rtemplate->set('selected', 'selected="selected"');
                else
                    $rtemplate->set('selected', '');
                $data           .= $rtemplate->compile();
            }
            $template['temple']->update($data);
        }
    }
}
if (is_null($date))
	$template->set('DATE',			    '');
else
	$template->set('DATE',			    $date);
if (is_null($etype))
	$template->set('ETYPE',		        '');
else
	$template->set('ETYPE',		        $etype);
if (is_null($order))
	$template->set('ORDER',		        '');
else
	$template->set('ORDER',		        $order);
if (is_null($kind))
	$template->set('KIND',		        '');
else
	$template->set('KIND',		        $kind);
if (is_null($nametitle))
	$template->set('NAMETITLE',		    '');
else
	$template->set('NAMETITLE',		    $nametitle);
if (is_null($templeReady))
	$template->set('TEMPLEREADY',		'');
else
	$template->set('TEMPLEREADY',		$templeReady);
if (is_null($preferred))
	$template->set('PREFERRED',		    '');
else
	$template->set('PREFERRED',		    $preferred);

$template->set('HEADING',			    $heading);
$template->set('TITLE',			    $heading);
if ($person)
    $template->set('NAME',              $person->getName(Person::NAME_INCLUDE_DATES));
else
    $template->set('NAME',              "$given $surname");
if (strlen($surname) > 1)
{
    if (substr($surname, 0, 2) == 'Mc')
        $template->set('PREFIX',        'Mc');
    else
        $template->set('PREFIX',        substr($surname,0,1));
}
else
    $template->set('PREFIX',            $surname);

$etypeoption            = $template['etype$idet'];
$optionText             = $etypeoption->outerHTML;
$data                   = '';

foreach($eventList as $key => $text)
{
    $rtemplate  = new \Templating\Template($optionText);
    $rtemplate->set('idet',             $key);
    $rtemplate->set('text',             $text);
    if ($key == $idet)
        $rtemplate->set('selected',     'selected="selected"');
    else
        $rtemplate->set('selected',     '');
    $data       .= $rtemplate->compile();
}                   // loop through event types
$etypeoption->update($data);

$template->display();

