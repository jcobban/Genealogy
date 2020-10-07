<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Participant.inc														*
 *																		*
 *  Definition of a class representing a participant, other than the	*
 *  primary individual or individuals, in an event.  This class provides*
 *  access to the information in a row of the table Participants.		*
 *																		*
 *  History:															*
 *		2012/11/21		Created											*
 *		2013/08/09		base class renamed to Record					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/01/19		use set in place of setField					*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/12/18		add support for temporary fields				*
 *		2019/07/18      use Person::getPerson                           *
 *		2019/09/20      correct implementation of Participant::dump     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  Participant															*
 *																		*
 *  Definition of a class implementing behavior for participant records	*
 *  within a genealogy database.										*
 *																		*
 *		Table: Participants - Participant table							*
 *																		*
 *		CREATE TABLE `Participants` (									*
 *				`IDPR`				INT(10) NOT NULL AUTO_INCREMENT,	*
 *						This is a unique identifier of the record, used	*
 *						primarily to permit deletion					*
 *				`IDIME`				INT(10) DEFAULT NULL,				*
 *						The unique identifier of the record within the	*
 *						database containing information about the event	*
 *						participated in.								*
 *				`IDIR`				INT(10) DEFAULT NULL,				*
 *						The unique identifier of the non-primary		*
 *						participant in the event.						*
 *				`Role`				VARCHAR(100) DEFAULT NULL,			*
 *						The role of the participant expressed as the 	*
 *						English language identification of the role.	*
 *						For example 'Witness', or 'Minister', or		*
 *						'Accoucheur'.									*
 *				`Type`				TINYINT(3) UNSIGNED DEFAULT NULL,	*
 *						Specifies what kind of record IDIME points to	*
 *						and the type of event within that record if the	*
 *						record documents multiple events.				*
 *				PRIMARY KEY (`IDPR`)									*
 *				KEY `IDIR` (`IDIR`)										*
 *				KEY `IDIME` (`IDIME`)									*
 *		) DEFAULT CHARSET=utf8;											*
 ************************************************************************/

class Participant extends Record
{
    /********************************************************************
     *	constants identifying the types of events and where the details	*
     *  are located within the record identified by IDIME.				*
     *																	*
     *  Facts where IDIME points to an Person Record in tblIR			*
     ********************************************************************/
    const PTYPE_UNSPECIFIED	= 0;
    const PTYPE_BIRTH		= 2;
    // expected participant roles 'Midwife', 'Accoucheur', or 'Physician'
    const PTYPE_CHRISTEN	= 3;
    // expected participant roles 'Minister', 'Godmother', 'Godfather'
    const PTYPE_DEATH		= 4;
    // expected participant roles 'Physician', 'Assassin'
    const PTYPE_BURIED		= 5;
    // expected participant roles 'Minister'
    const PTYPE_DEATHCAUSE	= 9;
    // expected participant roles 'Physician'
    const PTYPE_LDSB		= 15;	// LDS Baptism
    const PTYPE_LDSE		= 16;	// LDS Endowment
    const PTYPE_LDSC		= 26;	// LDS Confirmation
    const PTYPE_LDSI		= 27;	// LDS Initiatory

    /********************************************************************
     *	IDIME points to Child Record tblCR.IDCR							*
     ********************************************************************/
    const PTYPE_LDSP		= 17;	// LDS Sealed to Parents

    /********************************************************************
     *	IDIME points to Marriage Record tblMR.idmr						*
     ********************************************************************/
    const PTYPE_LDSS		= 18;	// LDS Sealed to Spouse
    const PTYPE_MAR		    = 20;	// Marriage	
    // expected participant roles 'Minister', 'Witness'
				
    /********************************************************************
     *	IDIME points to Event Record tblER.ider							*
     ********************************************************************/
    const PTYPE_EVENT		= 30;	// Individual Event
    const PTYPE_MAREVENT	= 31;	// Marriage Event

    /********************************************************************
     *	Participant::$intType											*
     *																	*
     *  Table of string descriptions for each event type				*
     *																	*
     *	This is intended to be public information.						*
     ********************************************************************/
    static $intType = array(
				0	=> "Unspecified",
				1	=> "Name",
				2	=> "Birth",
				3	=> "Christening",
				4	=> "Death",
				5	=> "Buried",
				9	=> "Death Cause",
				15	=> "LDS Baptism",
				16	=> "LDS Endowment",
				17	=> "LDS Sealed to Parents",
				18	=> "LDS Sealed to Spouse",
				20	=> "Marriage",
				26	=> "LDS Confirmation",
				27	=> "LDS Initiatory",
				30	=> "Event",
				31	=> "Marriage Event",
				100	=> "Temporary");

    /********************************************************************
     *  Identify type of record containing event details				*
     *																	*
     *	This is intended to be public information.						*
     ********************************************************************/
    static $recType = array(
				0	=> "IDIR",
				1	=> "IDIR",
				2	=> "IDIR",
				3	=> "IDIR",
				4	=> "IDIR",
				5	=> "IDIR",
				9	=> "IDIR",
				15	=> "IDIR",
				16	=> "IDIR",
				17	=> "IDCR",
				18	=> "IDMR",
				20	=> "IDMR",
				26	=> "IDIR",
				27	=> "IDIR",
				30	=> "IDER",
				31	=> "IDER");

    /********************************************************************
     *	Participant::$initRow											*
     *																	*
     *	array containing default values for a new record				*
     ********************************************************************/
    protected static	$initRow	= array(
						'idpr'			=> 0,	// possibly zero
						'idime'			=> 0,
						'idir'			=> 0,
						'role'			=> '',
						'type'			=> 0);

    /********************************************************************
     *	Participant::__construct										*
     *																	*
     *  Given the unique numeric identifier make information on a		*
     *	event record available.											*
     *																	*
     *  Input:															*
     *		$id			numeric identifier (key of Participants)		*
     *					which is 0, or 'new', to create a new record	*
     *					or a database record presented as an array		*
     *																	*
     *  Returns object containing participant record.					*
     ********************************************************************/
    function __construct($id)
    {
		global $connection;
		global $debug;

		$dbrow	= null;

		// action depends upon type of first parameter
		if ($id === 'new')
		    $id		= 0;

		if (is_int($id) || ctype_digit($id))
		{		// numeric identifier of record
		    // construct the query of the event table
		    $query		= "SELECT * FROM Participants WHERE IDPR=:idpr";
		    $sqlParms		= array('idpr'	=> $id);
		    
		    // query the database
		    $stmt		= $connection->prepare($query);
		    $queryText		= debugPrepQuery($query, $sqlParms);
		    if ($id == 0 || $stmt->execute($sqlParms))
		    {		// success
				if ($id > 0)
				    $result	= $stmt->fetch(PDO::FETCH_ASSOC);
				else
				    $result	= null;
				if ($debug)
				    print "<p>Participant::__construct: " . __LINE__ .
							" $querytext</p>";
				    
				if (is_array($result))
				{		// found event record
				    $dbrow		= $result;
				    $needInsert		= false;
				}		// found event record
				else
				{		// not found
				    $dbrow		= self::$initRow;
				    $dbrow['idpr']	= $id;
				    $needInsert		= true;
				}		// not found
		    }		// success
		    else
		    {		// error performing query
				throw new Exception(
						    "Participant::__construct: '$queryText', " .
							print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}		// numeric identifier of record
		else
		if (is_array($id))
		{		// row returned from database query
		    $dbrow		= $id;
		    $needInsert		= false;
		}		// row returned from database query
		else
		{
		    throw new Exception("Participant::__construct: called with " .
				 gettype($id) . ", value='$id'");
		}

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Participants');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('Participant Record constructed:');
    }		// Participant::__construct

    /********************************************************************
     *	Participant::getIdpr											*
     *																	*
     *	Get unique numeric identifier of the Participant record.		*
     *	Note that this is only initialized once the record is in the	*
     *	database as a result of calling save.							*
     ********************************************************************/
    function getIdpr()
    {
		return intval($this->row['idpr']); 
    }

    /********************************************************************
     *	Participant::getIdir											*
     *																	*
     *	Get unique numeric identifier of the Person record				*
     *	for the participant in the event.								*
     ********************************************************************/
    function getIdir()
    { 
		return intval($this->row['idir']); 
    }

    /********************************************************************
     *	Participant::getRole											*
     *																	*
     *	Get the role of the participant									*
     *																	*
     *	Returns:														*
     *			role of the participant as an English role name			*
     ********************************************************************/
    function getRole()
    {
		return $this->get('role');
    }		// Participant::getRole

    /********************************************************************
     *	Participant::getIdime											*
     *																	*
     *	Get the unique numeric identifier of the record containing the	*
     *	details of the event being documented.							*
     ********************************************************************/
    function getIdime() { return $this->row['idime']; }

    /********************************************************************
     *	Participant::getEventType										*
     *																	*
     *	Get the type of event, which indicates where the details of		*
     *	the associated event are located.								*
     ********************************************************************/
    function getEventType() { return $this->row['type']; }

    /********************************************************************
     *	Participant::getEventTypeText									*
     *																	*
     *	Get textual description of the type of event.					*
     ********************************************************************/
    function getEventTypeText()
    {
		if ($this->row['type'] < 0)
		    $retval	= "Temporary";
		else
		    $retval	= self::$intType[$this->row['type']]; 
		if ($retval === null)
		    return '';
		else
		    return $retval;
    }		// Participant::getEventTypeText

    /********************************************************************
     *	Participant::setIdime											*
     *																	*
     *	Set the unique numeric identifier of the record containing the	*
     *	details of the fact being documented.							*
     *																	*
     *  Parameters:														*
     *	    $value		new value to set								*
     *																	*
     *	Returns:														*
     *			former numeric record identifier value					*
     ********************************************************************/
    function setIdime($value)
    {
		return parent::set('idime', $value);
    }		// Participant::setIdime

    /********************************************************************
     *	Participant::setIdir											*
     *																	*
     *	Set the unique numeric identifier of the individual who is		*
     *	a participant in the identified event.							*
     *																	*
     *  Parameters:														*
     *	    $value		new value to set								*
     *																	*
     *	Returns:														*
     *		former numeric record identifier value						*
     ********************************************************************/
    function setIdir($value)
    {
		return parent::set('idir', $value);
    }		// Participant::setIdir

    /********************************************************************
     *	Participant::setEventType										*
     *																	*
     *	Set the type of event, which indicates where the details of		*
     *	the associated fact are located.								*
     *																	*
     *  Parameters:														*
     *	    $value		new value to set								*
     *																	*
     *	Returns:														*
     *		former event type value										*
     ********************************************************************/
    function setEventType($value)
    {
		if (!array_key_exists($value, self::$intType))
		    die("Participant::setEventType invalid parameter");
		return parent::set('type', $value);
    }		// Participant::setEventType

    /********************************************************************
     *	Participant::setRole											*
     *																	*
     *	Set the role of the participant									*
     *																	*
     *  Parameters:														*
     *	    $value		new value to set								*
     *																	*
     *	Returns:														*
     *		former role of the participant as an English role name		*
     ********************************************************************/
    function setRole($value)
    {
		if (!is_string($value))
		    die("Participant::setRole invalid parameter type " .
				gettype($value));
		return parent::set('role', $value);
    }		// Participant::setRole

    /********************************************************************
     *	Participant::set												*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		value to set it to								*
     *																	*
     *	Returns:														*
     *			previous value of the field								*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'type':
		    {
				return $this->setEventType($value);
		    }

		    case 'role':
		    {
				return $this->setRole($value);
		    }

		    default:
		    {		// no validation implemented yet
				return parent::set($field, $value);
		    }		// no validation implemented yet
		}		// switch on field name
    }		// Participant::set

    /********************************************************************
     *  Participant::dump												*
     *																	*
     *  If debug output is enabled, display a diagnostic dump of the	*
     *  contents of a database record in the web page.					*
     *																	*
     *  Input:															*
     *		    $label		descriptive identifier for record			*
     *		    $force		if true output even if $debug false			*
     *		                This is deprecated,                         *
     *		                use $warn   .= $participant->dump(...);     *
     *																	*
     *	Return:															*
     *	    string containing trace output as HTML						*
     ********************************************************************/
    function dump($label	= null, 
				  $force	= false)
    {
		global	$debug;
		global	$warn;

		// ensure that there is a label to use for the display
		if (!is_string($label) || strlen($label) == 0)
		{
		    $person	        = Person::getPerson($this->get('idir'));
		    $label	        = $person->getName(Person::NAME_INCLUDE_DATES);
		}

	    $output	        .= "<p class='label'>\n";
	    $output	        .= $label;
	    $output	        .= "</p>\n";
	    $output	        .= "<table border='1'>\n";
	    foreach($this->row as $key => $value)
	    {
			$output 	.= "  <tr>\n";
			$output 	.= "    <th class='label'>\n";
			$output 	.= $key;
			$output 	.= "    </th>\n";
			$output 	.= "    <td class='dataleft'>\n";
			$output 	.= $value;

			if (strlen($value) > 0)
			{
			    switch($key)
			    {
					case 'type':
					{
					    $output	.= ' = ' . self::$intType[$value];
					    break;
					}

					default:
					{
					    break;
					}
			    }	// field specific formatting
			}	// field value not empty
			$output 	.= "    </td>\n";
			$output 	.= "  </tr>\n";
	    }	// loop through keys
	    $output	        .= "</table>\n";

		if ($debug || $force)
		    $warn		        .= $output;
		return $output;
    }		// Participant::dump

}		// class Participant 

