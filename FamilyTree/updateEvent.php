<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateEvent.php														*
 *																		*
 *  Handle a request to update an individual event in 					*
 *  the Legacy family tree database.  This file generates an			*
 *  XML file, so it can be invoked from Javascript.						*
 *																		*
 *  Parameters (passed by method='POST'):								*
 *    One of the following record keys:									*
 *		idir			unique numeric key of the individual record		*
 *		idmr			unique numeric key of the family record			*
 *		ider			unique numeric key of instance of Event			*
 *		idcr			unique numeric key of the child relationship	*
 *						record											*
 *    Plus:																*
 *		type			primary event type, as documented in			*
 *						Citation										*
 *		etype			secondary event type, as documented in			*
 *						Event.											*
 *						This is required if primary type is STYPE_EVENT	*
 *						or STYPE_MAREVENT								*
 *		date			date event occurred in human readable form		*
 *		location		location where event occurred.					*
 *		temple			IDTR value of selected Temple					*
 *		templeReady		flag if submission is temple ready				*
 *		kind			kind of location for some LDS sacraments		*
 *						0		live: performed outside temple			*
 *						1		temple									*
 *		cremated		flag if individual was cremated					*
 *		note			long note field for comments					*
 *		deathCause		long text field for cause of death				*
 *		desc			synonym for note, used if primary type is		*
 *						STYPE_EVENT (30) or STYPE_MAREVENT (31)			*
 *		description 	portion of description of event that is not a	*
 *						location.										*
 *						This is used if primary type is STYPE_EVENT		*
 *						or STYPE_MAREVENT								*
 *		occupation 		description field for an occupation type event	*
 *		order			define a specific order for events, deprecated	*
 *		notmarried		flag if never married							*
 *		nochildren		flag if known there were no children			*
 *		givenName		given name of individual						*
 *		surname			surname of individual							*
 *		prefix			name prefix, e.g. "Dr."							*
 *		title			title suffix, e.g. "Jr."						*
 *		newAltGivenName	alternate given name to add						*
 *		newAltSurname	alternate surname to add						*
 *																		*
 *  History:															*
 *		2010/08/08		escape characters with XML entities				*
 *		2010/08/09		use POST instead of GET							*
 *						add support for Order field						*
 *		2010/08/21		LegacyLocation now uses Record functionality	*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/26		escape command string with xmlentities			*
 *		2010/11/14		add prefix and title fields of Name event		*
 *		2010/12/21		handle exception from new LegacyLocation		*
 *		2011/01/02		handle LDS sacraments recorded in LegacyIndiv	*
 *		2011/02/26		set default value of order field to current		*
 *						number of evens, so new events added are at		*
 *						end of list										*
 *		2011/06/15		add support for events in the LegacyFamily		*
 *						record											*
 *		2011/12/23		clean up parameter processing					*
 *						support temple parameter						*
 *		2012/01/13		change class names								*
 *						add support for no children fact of family		*
 *		2012/08/12		add support for sealed to parents event			*
 *						this includes basic support for Child			*
 *						record											*
 *						add support for temple ready indicator			*
 *		2012/09/24		document complete list of input fields			*
 *						permit changing primary name using Edit Name	*
 *						Fact dialog, and permit adding a new			*
 *						alternate name									*
 *		2013/02/26		do not fail if user is not authorized			*
 *		2013/03/14		LegacyLocation constructors no longer saves		*
 *		2013/03/23		fix XML syntax issue with template fields		*
 *		2013/05/14		update cremated indicator						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		ability to edit cause of death added to			*
 *						edit dialogue for normal death event			*
 *		2014/03/21		use new form of LegacyAltName::__construct		*
 *						to add alternate name							*
 *		2014/04/08		LegacyAltName renamed to LegacyName				*
 *		2014/04/14		update citation page number in database			*
 *		2014/04/24		citation parameters not passed to EventUpdate	*
 *						via AJAX										*
 *						incorrect call to create Citation				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/06/14		ignore zero length parameters					*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/27		all events previously recorded in tblIR are		*
 *						moved to tblER									*
 *		2014/11/19		accept synonym 'occupation' for description		*
 *		2014/12/22		use LegacyIndiv::getBirthEvent					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/12		permit clearing death cause						*
 *		2016/10/15		report exceptions in XML safe way				*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/18		class LegacyName renamed to class Name			*
 *		2017/08/23		use standard interface to main events			*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/29		use RecordSet to get default event Order		*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<event>\n";

// get the updated values of the fields in the record
$msg						= '';
$idir						= null;
$person             		= null;         // instance of Person
$idmr						= null;
$family             		= null;         // instance of Family
$ider						= null;
$event              		= null;         // instance of Event
$idcr						= null;
$child              		= null;         // instance of Child
$type						= null;
$etype						= null;
$order						= null;
$date						= null;
$location					= null;         // instance of Location or Temple
$kind						= 0;	        // 1 if location is a temple
$note						= '';
$deathCause					= '';
$description				= '';
$notmar						= 0;
$nokids						= 0;
$templeReady				= 0;
$cremated					= 0;
$prefix						= null;
$title						= null;
$surname					= null;
$givenName					= null;
$altSurname					= null;
$altGivenName				= null;
$idlr						= 1;	        // default location blank
$citParms					= array();
$citations          		= array();      // list of citations

print "    <parms>\n";
error_log("updateEvent.php: <parms>\n",3,$document_root . "/logs/updateEvent.txt");
try {
foreach($_POST as $key => $value)
{		// loop through all parameters
	$xmlkey	        = str_replace("$","_",$key);
	print "\t<$xmlkey>" . htmlspecialchars($value,ENT_XML1) . "</$xmlkey>\n";
    error_log("updateEvent.php: $xmlkey='$value'\n",
              3,
              $document_root . "/logs/updateEvent.txt");

	switch($key)
	{	// act on specific keys
	    case 'idir':
	    {		// idir of individual to be updated
			$idir		                = $value;
			if (strlen($idir) > 0)
			    $person	                = new Person(array('idir' => $idir));
			if (!$person->isOwner())
			    $msg	.= "User not authorized to update individual $idir. ";
			break;
	    }		// idir of individual to be updated

	    case 'idmr':
	    {		// idmr of family to be updated
			$idmr		                = $value;
			if (strlen($idmr) > 0)
			    $family	                = new Family(array('idmr' => $idmr));
			break;
	    }		// idmr of family to be updated

	    case 'ider':
	    {		// ider to be updated
			$ider		                = $value;
            if (strlen($ider) > 0)
            {
                $event	                = new Event(array('ider' => $ider));
            }
			break;
	    }		// ider to be updated

	    case 'idcr':
	    {		// idcr to be updated
			$idcr	                	= $value;
			if (strlen($idcr) > 0)
			{
			    $child	                = new Child(array('idcr' => $idcr));
			    try {
					$idir	            = $child->getIdir();
					$person	            = new Person(array('idir' => $idir));
					if (!$person->isOwner())
					    $msg	.=
			"User not authorized to update individual $idir. ";
					$idmr           	= $child->getIdmr();
					$family	            = new Family(array('idmr' => $idmr));
			    }
			    catch (Exception $e)
			    {
					$person	            = null;
					$family	            = null;
			    }
			}
			break;
	    }		// idcr to be updated

	    case 'type':
	    {		// type to be updated
			$type		                = $value;
			if (strlen($type) > 0)
			{
			    if ($type == Citation::STYPE_LDSE ||
					$type == Citation::STYPE_LDSI ||
					$type == Citation::STYPE_LDSP ||
					$type == Citation::STYPE_LDSB ||
					$type == Citation::STYPE_LDSC ||
					$type == Citation::STYPE_LDSS)
					$kind	            = 1;	// location is a temple
			    else
					$kind	            = 0;	// location is a location
			}
			break;
	    }		// type to be updated

	    case 'etype':
	    {		// etype to be updated
			$etype		                = $value;
			break;
	    }		// etype to be updated

	    case 'date':
	    {		// date to be updated
			$date	                	= trim($value);
			break;
	    }		// date to be updated

	    case 'kind':
	    {		// location kind to be updated
			$kind	                    = $value;
			break;
	    }		// location kind to be updated

	    case 'location':
	    case 'temple':
	    {		// location to be updated
			if (strlen($value) > 0)
			{		// find/create location by name
			    if ($kind == 1)
					$location	        = new Temple(array('idtr' => $value));
			    else
			    {
					if (is_int($value) || ctype_digit($value))
					    $location	= new Location(array('idlr' => $value));
					else
                        $location	= new Location(array('location' => $value));

                    // if a new location need to save to get IDLR value
					if (!$location->isExisting())
					    $location->save(false);
			    }
			    $idlr	                = $location->getId();
			}		// find/create location by name
			else		
			{		// empty location
			    if ($kind == 1)
					$location	        = new Temple(array('idtr' => 1));
			    else
					$location	        = new Location(array('idlr' => 1));
			    $idlr               	= 1;
			}		// empty location
			break;
	    }		// location to be updated

	    case 'note':
	    {		// note to be updated
			$note		                = $value;
			break;
	    }		// note to be updated

	    case 'deathCause':
	    {		// deathCause to be updated
			$deathCause	                = $value;
			break;
	    }		// deathCause to be updated

	    case 'desc':
	    {		// note to be updated
			$note	                	= $value;
			break;
	    }		// note to be updated

	    case 'description':
	    case 'occupation':
	    {		// description of event
			$description	            = $value;
			break;
	    }		// descriptions of event

	    case 'order':
	    {		// order to be updated
			$order		                = $value;
			break;
	    }		// order to be updated

	    case 'prefix':
	    {		// prefix to be updated
			$prefix		                = $value;
			break;
	    }		// prefix to be updated

	    case 'title':
	    {		// title to be updated
			$title		                = $value;
			break;
	    }		// title to be updated

	    case 'notmarried':
	    {		// value of not married indicator
			$notmar		                = $value;
			break;
	    }		// value of not married indicator

	    case 'nochildren':
	    {		// value of no children indicator
			$nokids		                = $value;
			break;
	    }		// value of no children indicator

	    case 'templeReady':
	    {		// value of temple ready submission indicator
			$templeReady	            = $value;
			break;
	    }		// value of temple ready submission indicator

	    case 'cremated':
	    {		// value of cremated indicator
			$cremated	                = $value;
			break;
	    }		// value of cremated indicator

	    case 'givenName':
	    {		// value of primary given name
			$givenName	                = $value;
			break;
	    }		// value of primary given name

	    case 'surname':
	    {		// value of primary surname
			$surname	                = $value;
			break;
	    }		// value of primary surname

	    case 'newAltGivenName':
	    {		// value of given name portion of alternate name
			$altGivenName	            = $value;
			break;
	    }		// value of temple ready submission indicator

	    case 'newAltSurname':
	    {		// value of temple ready submission indicator
			$altSurname	                = $value;
			break;
	    }		// value of temple ready submission indicator

	    case 'idime':
	    {		// citation IDIME
			$citParms['idime']	        = $value;
			break;
	    }		// citation IDIME

	    case 'citType':
	    {		// citation type
			$citParms['type']	        = $value;
			break;
	    }		// citation type

	    default:
	    {		// other fields
			if (substr($key, 0, 6) == 'Source')
			{	// IDSR of source
			    $idsx	                = intval(substr($key, 6));
			    $citParms['idsr']	    = $value;
			}	// IDSR of source
			else
			if (substr($key, 0, 6) == 'IDSR')
			{	// IDSR of source
			    $idsx	                = intval(substr($key, 4));
			    $citParms['idsr']	    = $value;
			}	// IDSR of source
			else
			if (substr($key, 0, 4) == 'Page')
			{
			    $idsx	                = intval(substr($key, 4));
			    if ($idsx == 0)
			    {		// new citation
					$citParms['srcdetail']	= $value;
					$citation	        = new Citation($citParms);
			    }		// new citation
			    else
			    {		// update existing citation
					$citation	        = new Citation(array('idsx' => $idsx));
					$citation->set('srcdetail', $value);
                }		// update existing citation
                $citations[]            = $citation;
			}
	    }		// other fields
	}	// act on specific keys
}		// loop through all parameters

print "    </parms>\n";
} catch (Exception $e) {
error_log("updateEvent.php: " . __LINE__ . ': ' .$e->getMessage() . ': ' . $e->gettraceAsString() ."\n",3,$document_root . "/logs/updateEvent.txt");
}
// close root node of XML output

error_log("updateEvent.php: " . __LINE__ . " </parms>\n",3,$document_root . "/logs/updateEvent.txt");
if (!canUser('edit'))
{
	$msg	.= 'User not authorized to update the database. ';
}

$rtype	= null;

print "<trace>updateEvent.php: " . __LINE__ . " type=$type</trace>\n";
try {
switch($type)
{		// act on the event citation type
	case null:
	{
	    $msg	.= 'type= parameter missing. ';
	    $record	= null;
	    break;
	}		// null

	case Citation::STYPE_UNSPECIFIED:	// 0
	{
	    $msg	.= "Unsupported type=$type. ";
	    $record	= null;
	    break;
	}		// STYPE_UNSPECIFIED

	case Citation::STYPE_NAME:		// 1
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
            $record		= $person;
            if ($person->isExisting())
                $priName    = $person->getPriName();
			if (!is_null($note))
			    $record->set('namenote', $note);
			if (!is_null($title))
			    $record->set('title', $title);
			if ($prefix !== null)
			    $record->set('prefix', $prefix);
			if ($givenName !== null)
			    $record->set('GivenName', $givenName);
			if ($surname !== null)
                $record->set('Surname', $surname);
            if (is_null($priName))
                $priName    = $person->getPriName();
            if ($priName)
                $priName->save('cmd');

			// check for request to add a new alternate name
			if (($altSurname !== null && strlen($altSurname) > 0) ||
			    ($altGivenName !== null && strlen($altGivenName) > 0))
			{		// add a new alternate name
			    $idir			= $person->getIdir();
			    $evBirth			= $person->getBirthEvent(false);
			    if ($evBirth)
					$birthsd		= $evBirth->get('eventsd');
			    else
					$birthsd		= -99999999;
			    $nameSet		= new RecordSet('tblNX',
									array('idir' => $idir));
			    $order			= 0;
			    foreach($nameSet as $idnx => $name)
					if ($name->get('order') > $order)
					    $order		= $name->get('order');
			    $order++;		// one more than highest
			    print "<order line='" . __LINE__ . "'>$order</order>\n";
			    $altParms	= array(
						'idir'		=> $idir,
						'surname'	=> $surname,
						'givenname'	=> $givenName,
						'userref'	=> $person->get('userref'),
						'birthsd'	=> $birthsd,
						'order'		=> $order);
			    if ($altSurname !== null && strlen($altSurname) > 0)
					$altParms['surname']	= $altSurname;
			    if ($altGivenName !== null && strlen($altGivenName) > 0)
					$altParms['givenname']	= $altGivenName;
			    $altName			= new Name($altParms);
			    $altName->save(true);
			}		// add a new alternate name
	    }		// OK to update
	    break;
	}		// STYPE_NAME

	case Citation::STYPE_BIRTH:		// 2
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person->getBirthEvent(true);
			$record->setDate($date);
			$record->set('idlrevent', $idlr);
			if (!is_null($note))
			    $record->set('description', $note);
	    }		// OK to update
	    break;
	}		// STYPE_BIRTH

	case Citation::STYPE_CHRISTEN:		// 3
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person->getChristeningEvent(true);
			$record->setDate($date);
			$record->set('idlrevent', $idlr);
			if (!is_null($note))
			    $record->set('description', $note);
	    }		// OK to update
	    break;
	}		// STYPE_CHRISTEN

	case Citation::STYPE_DEATH:		// 4;
	{
error_log("updateEvent.php: " . __LINE__ . "\n",3,$document_root . "/logs/updateEvent.txt");
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person->getDeathEvent(true);
			$record->setDate($date);
			$record->set('idlrevent', $idlr);
			if (!is_null($note))
			    $record->set('description', $note);
			$person->set('deathcause', $deathCause);
	    }		// OK to update
	    break;
	}		// STYPE_DEATH
	case Citation::STYPE_BURIED:		// 5;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person->getBuriedEvent(true);
			$record->setDate($date);
			$record->set('idlrevent', $idlr);
			if (!is_null($note))
			    $record->set('description', $note);
	    }		// OK to update
	    break;
	}		// STYPE_BURIED

	case Citation::STYPE_NOTESGENERAL:	// 6;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('Notes', $note);
	    }		// OK to update
	    break;
	}		// STYPE_NOTESGENERAL

	case Citation::STYPE_NOTESRESEARCH:	// 7;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('References', $note);
	    }		// OK to update
	    break;
	}

	case Citation::STYPE_NOTESMEDICAL:	// 8;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('Medical', $note);
	    }		// OK to update
	    break;
	}

	case Citation::STYPE_DEATHCAUSE:		// 9;
	{
error_log("updateEvent.php: " . __LINE__ . "\n",3,$document_root . "/logs/updateEvent.txt");
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record	= $person;
			$record->set('DeathCause', $note);
	    }		// OK to update
	    break;
	}

	case Citation::STYPE_LDSB:		// 15;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('BaptismD', $date);
			$record->set('IDTRBaptism', $idlr);
			$record->set('BaptismKind', $kind);
			if (is_null($note))
			    $record->set('BaptismNote', '');
			else
			    $record->set('BaptismNote', $note);
			$record->set('LDSB', $templeReady);
	    }		// OK to update
	    break;
	}

	case Citation::STYPE_LDSE:		// 16;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('EndowD', $date);
			$record->set('IDTREndow', $idlr);
			$record->set('EndowNote', $note);
			$record->set('LDSE', $templeReady);
	    }		// OK to update
	    break;
	}

	case Citation::STYPE_LDSP:		// 17
	{
	    if (is_null($idcr))
	    {
			$msg	.= "idcr value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= new Child(array('idcr' => $idcr));
			$record->set('ParSealD', $date);
			$record->set('IDTRParSeal', $idlr);
			$record->set('ParSealNote', $note);
			$record->set('LDSP', $templeReady);
	    }		// OK to update
	    break;
	}		// case Citation::STYPE_LDSP: 17

	case Citation::STYPE_LDSS:	// 18 Sealed to Spouse
	{
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    else
	    {		// OK to update
			$record		= $family;
			$record->set('SealD', $date);
			$record->set('IDTRSeal', $idlr);
			$record->set('LDSS', $templeReady);
	    }		// OK to update
	    break;
	}		// LDS Sealed to Spouse

	case Citation::STYPE_MAR:		// 20 Marriage	
	{		    // Marriage event recorded in Family
	    if (is_null($idmr))
	    {
			$msg	                .= "idmr value not specified. ";
	    }
	    else
	    {		// OK to update
			$record		            = $family;
			$record->set('MarD',    $date);
			$record->set('IDLRMar', $idlr);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_MAR: 20

	case Citation::STYPE_MARNOTE:	// 21 Marriage Note
	{		    // Marriage Note
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    else
	    {		// OK to update
			$record		= $family;
			$record->set('Notes', $note);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_MARNOTE:	21

	case Citation::STYPE_NEVERMARRIED:// 19 never married 
	case Citation::STYPE_MARNEVER:	// 22 Never Married	     
	{		    // not married indicator
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    if (is_null($notmar))
	    {
			$msg	.= "notmar value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record	= $family;
			if ($notmar == '1')
			    $record->set('notmarried', 1);
			else
			    $record->set('notmarried', 0);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_MARNEVER: 22

	case Citation::STYPE_MARNOKIDS:	// 23 no children
	{		    // marriage events that contain no subfields
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    if (is_null($nokids))
	    {
			$msg	.= "nokids value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $family;
			if ($nokids == '1')
			    $record->set('nochildren', 1);
			else
			    $record->set('nochildren', 0);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_MARNOKIDS:	23

	case Citation::STYPE_MAREND:	// 24 marriage ended
	{		    // marriage events that contain no subfields
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    else
	    {		// OK to update
			$record		= $family;
			$record->set('MarEndD', $date);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_MAREND:	24


	case Citation::STYPE_LDSC:		// 26;
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('ConfirmationD', $date);
			$record->set('IDTRConfirmation', $idlr);
			$record->set('ConfirmationKind', $kind);
			$record->set('ConfirmationNote', $note);
			$record->set('LDSC', $templeReady);
	    }		// OK to update
	    break;
	}           // Citation::STYPE_LDSC:	26;

	case Citation::STYPE_LDSI:		// 27
	{
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "note value not specified. ";
	    }

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $person;
			$record->set('InitiatoryD', $date);
			$record->set('IDTRInitiatory', $idlr);
			$record->set('InitiatoryNote', $note);
			$record->set('LDSI', $templeReady);
	    }		// OK to update
	    break;
	}		    // STYPE_LDSI

	case Citation::STYPE_EVENT:		// 30
	{
print "<trace>updateEvent.php: " . __LINE__ . " Citation::STYPE_EVENT: idir=$idir, ider=$ider, date=$date, location=" . htmlspecialchars($location->getName(),ENT_XML1) . "</trace>\n";
	    if (is_null($idir))
	    {
			$msg	.= "idir value not specified. ";
	    }
	    if (is_null($ider))
	    {
			$msg	.= "ider value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "desc value not spec0ified. ";
	    }
	    if (is_null($description))
	    {
			$msg	.= "description value not specified. ";
	    }
	    if (is_null($etype))
	    {
			$msg	.= "etype value not specified. ";
	    }
	    if (is_null($order) || strlen($order) == 0) 
	    {		// event order not set
			$eventSet	= new RecordSet('tblER',
		                				array('idir'	=> $idir,
					                	      'idtype'	=> Event::IDTYPE_INDIV));
			$order		= $eventSet->count();
	    }		// order not set

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $event;
			$record->set('IDER',		$ider);
			$record->set('IDIR',		$idir);
			$record->set('EventD',		$date);
			$record->set('IDET',		$etype);
			$record->set('IDType',		Event::IDTYPE_INDIV);
			$record->set('IDLREvent',	$idlr);
			$record->set('Desc',		$note);
			$record->set('Order',		$order);
			$record->set('Description',	$description);
			if ($etype == 6)
			{
			    $person->set('deathcause', $deathCause);
			}
	    }		// OK to update
	    else
			print "<msg>$msg</msg>\n";

	    break;
	}		    // STYPE_EVENT

	case Citation::STYPE_MAREVENT:		// 31;
	{
	    if (is_null($idmr))
	    {
			$msg	.= "idmr value not specified. ";
	    }
	    if (is_null($ider))
	    {
			$msg	.= "ider value not specified. ";
	    }
	    if (is_null($date))
	    {
			$msg	.= "date value not specified. ";
	    }
	    if (is_null($location))
	    {
			$msg	.= "location value not specified. ";
	    }
	    if (is_null($note))
	    {
			$msg	.= "desc value not specified. ";
	    }
	    if (is_null($description))
	    {
			$msg	.= "description value not specified. ";
	    }
	    if (is_null($etype))
	    {
			$msg	.= "etype value not specified. ";
	    }
	    if (is_null($order) || strlen($order) == 0) 
	    {		// order not set
			$eventSet	= new RecordSet('tblER',
							array('idir'	=> $idmr,
							      'idtype'	=> Event::IDTYPE_MAR));
			$order		= $eventSet->count();
	    }		// order not set

	    if (strlen($msg) == 0)
	    {		// OK to update
			$record		= $event;
			$record->set('IDIR',		$idmr);
			$record->set('EventD',		$date);
			$record->set('IDET',		$etype);
			$record->set('IDType',		Event::IDTYPE_MAR);
			$record->set('IDLREvent',	$idlr);
			$record->set('Desc',		$note);
			$record->set('Order',		$order);
			$record->set('Description',	$description);
			$record->set('idir',		$idmr);
	    }		// OK to update
	    break;
	}		    // STYPE_MAREVENT

	default:
	{		    // unsupported event type
	    $msg	        .= "Unsupported type=$type. ";
	    $record	        = null;
	    break;
	}		    // unsupported event type

}		        // act on the event citation type

if (strlen($msg) == 0)
{		        // no errors detected
	// update the database to record the new information
	if (isset($record))
    {		        // need to update record
        $needIdime                  = !$record->isExisting();
        $record->save(true);
        foreach($citations as $citation)
        {
            if ($needIdime ||
                $citation['idime'] == 0)
                $citation->set('idime', $record->getId());
            $citation->save('cmd');
        }
    }		        // need to update record

    // because of flaws in the design of the database some information
    // about some events is recorded both in an instance of Event and
    // the associated instance of Person
	if (isset($person))
	{
	    $person->save(true);
	}
}		            // no errors detected
else
{		            // errors in parameters
	print "    <msg>\n";
	print htmlspecialchars($msg,ENT_XML1);
	print "    </msg>\n";
}		            // errors in parameters
} catch (Exception $e) {
	print "    <msg>\n";
	print htmlspecialchars($e->getMessage(),ENT_XML1);
	print htmlspecialchars($e->gettraceAsString(),ENT_XML1);
	print "    </msg>\n";
    error_log("updateEvent.php: " . __LINE__ . ": exception " . $e->getMessage() .
        ': ' . $e->gettraceAsString() ."\n",3,$document_root . "/logs/updateEvent.txt");
}
// close root node of XML output
print "</event>\n";
