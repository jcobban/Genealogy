<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  ToDo.inc															*
 *																		*
 *  Definition of a class representing an item of research work to be	*
 *  done in a genealogical database.  This class provides access to		*
 *  the information in a row of the table tblTD.						*
 *																		*
 *  CREATE TABLE `tblTD` (			                                    *
 *		`IDTD`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,		*
 *		`ToDoType`      TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		`IDIR`          INT(10) UNSIGNED DEFAULT NULL,			        *
 *		`IDTC`          INT(10) UNSIGNED DEFAULT NULL,			        *
 *		`IDTL`          INT(10) UNSIGNED DEFAULT NULL,			        *
 *		`ToDoName`      VARCHAR(255) DEFAULT NULL,			            *
 *		`OpenedD`       VARCHAR(100) DEFAULT NULL,			            *
 *		`OpenedSD`      INT(10) DEFAULT NULL,			                *
 *		`ReminderD`     VARCHAR(100) DEFAULT NULL,			            *
 *		`ReminderSD`    INT(10) DEFAULT NULL,			                *
 *		`ClosedD`       VARCHAR(100) DEFAULT NULL,			            *
 *		`IDAR`          INT(10) UNSIGNED DEFAULT NULL,			        *
 *		`Status`        TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		`Priority`      TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		`Desc`          LONGTEXT,			                            *
 *		`Results`       LONGTEXT,			                            *
 *		`FilingRef`     VARCHAR(50) DEFAULT NULL,			            *
 *		`Tag1`          TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		`Used`          TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		`qsTag`         TINYINT(3) UNSIGNED DEFAULT NULL,			    *
 *		PRIMARY KEY (`IDTD`),			                                *
 *		KEY `ToDoName` (`ToDoName`),			                        *
 *		KEY `IDIR` (`IDIR`),			                                *
 *		KEY `IDTC` (`IDTC`),			                                *
 *		KEY `IDTL` (`IDTL`) )			                                *
 *																		*
 *  History:															*
 *		2014/03/10		created											*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2017/08/04		change class LegacyAddress to class Address		*
 *			            constructor supports array parameter for search	* 
 *		2017/08/15		change class LegacyToDo to class ToDo			*
 *		2017/11/21		use prepared statements							*
 *		2017/12/18		add support for temporary fields				*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class ToDo															*
 *																		*
 *  Definition of a class implementing behavior of research work to be	*
 *  done in a genealogical tree.					                    *
 *																		*
 ************************************************************************/

class ToDo extends Record
{
    /********************************************************************
     *	ToDo::$initRow													*
     *																	*
     *	array containing default values for all fields in a new record	*
     ********************************************************************/
    protected static	$initRow	= array(
							'idtd'			=> 0,
							'todotype'		=> 0,
							'idir'			=> 0,
							'idtc'			=> 0,
							'idtl'			=> 0,
							'todoname'		=> '',
							'openedsd'		=> -99999999,
							'openedd'		=> '',
							'remindersd'	=> -99999999,
							'reminderd'		=> '',
			              //'closeedsd'	=> -99999999,
							'closeedd'		=> '',
							'idar'			=> 0,
							'status'		=> 1,
							'priority'		=> 1,
							'desc'			=> '',
							'results'		=> '',
							'filingref'		=> '',
							'tag1'			=> 0,
							'qstag'			=> 0,
							'used'			=> 0);

    /********************************************************************
     *	ToDo::$translate												*
     *																	*
     *	array containing synonyms for field names                       *
     ********************************************************************/
    protected static	$translate	= array(
                            'name'			=> 'todoname',
                            'type'          => 'todotype',
                            'category'      => 'idtc',
                            'notes'         => 'desc');
    /********************************************************************
     *	constants for values of field 'idtc'                            *
     ********************************************************************/
    const TC_NULL		                = 1;
    const TC_BIRTH_CERTIFICATE		    = 2;
    const TC_CEMETERY		            = 3;
    const TC_CENSUS		                = 4;
    const TC_CHURCH_RECORD		        = 5;
    const TC_COURT_RECORD		        = 6;
    const TC_DEATH_CERTIFICATE		    = 7;
    const TC_IMMIGRATION		        = 8;
    const TC_LAND_DEED		            = 9;
    const TC_LAND_PATENT		        = 10;
    const TC_MARRIAGE_CERTIFICATE		= 11;
    const TC_MILITARY_RECORD		    = 12;
    const TC_NATURALIZATION		        = 13;
    const TC_OBITUARY		            = 14;
	const TC_PROBATE_ESTATE             = 15;
    const TC_SHIP_PASSENGER_LIST		= 16;
    const TC_SS_DEATH_INDEX		        = 17;
    const TC_TAX_RECORDS		        = 18;
    const TC_TOMBSTONE		            = 19;
    const TC_WILL		                = 20;

    /********************************************************************
     *	$intCategory	interpret the category field (IDTC)				*
     ********************************************************************/
    static $intCategory	= array(
						 1	=> '',
						 2 	=> 'Birth Certificate',
						 3 	=> 'Cemetery',
						 4 	=> 'Census',
						 5 	=> 'Church Record',
						 6 	=> 'Court Record',
						 7 	=> 'Death Certificate',
						 8 	=> 'Immigration',
						 9 	=> 'Land Deed',
						10 	=> 'Land Patent',
						11 	=> 'Marriage Certificate',
						12 	=> 'Military Record',
						13 	=> 'Naturalization',
						14 	=> 'Obituary',
						15 	=> 'Probate/Estate',
						16 	=> 'Ship Passenger List',
						17 	=> 'SS Death Index',
						18 	=> 'Tax Records',
						19 	=> 'Tombstone',
						20 	=> 'Will');

    /********************************************************************
     *	function ToDo::__construct										*
     *																	*
     *  Given the identifier extract information about a research work	*
     *	to do item.														*
     *																	*
     *  Input:															*
     *	    $id			numeric identifier of a work item		 		*
     *					or a database record presented as an array		*
     *					If is 0 or 'new', create a new empty record		*
     *					for later insertion into the database.			*
     *																	*
     *  Returns object containing work to do record.					*
     ********************************************************************/
    function __construct($id)
    {
		global $debug;
		global $connection;

		$dbrow				= null;

		// current date in internal forms
		$currdate			= getdate();
		$ldate				= 1000000*$currdate['mday'] +
			    			  10000*$currdate['mon'] +
				    		  $currdate['year'];
		if ($ldate < 10000000)
		    $ldate			= '000' . $ldate . '00000000';
		else
		    $ldate			= '00' . $ldate . '00000000';
		$sdate				= 10000*$currdate['year'] +
				    		  100*$currdate['mon'] +
					    	  $currdate['mday'];

		// reformat old-style parameters into array format
		if (is_array($id))
		    $parms			= $id;
		else
		if (is_string($id) && strtolower($id) == 'new')
		    $parms			= array('idtd'	=> 0);
		else
		if (is_string($id))
		{
		    if (ctype_digit($id))
				$parms		= array('idtd'	=> (int)$id);
		    else
				$parms		= array('todoname' => $id);
		}
		else
		if (is_int($id))
		    $parms			= array('idtd'	=> $id);
		else
		    throw new Exception("ToDo::__construct: " .
				"called with " . gettype($id) . " value=" . $id);

		$where				= '';
		$sqlParms		    = array();
		$and				= 'WHERE ';
		$createRecord		= true;
		$errorMsg		= '';

		foreach($parms as $fieldname => $value)
		{				// loop through parameters
		    switch(strtolower($fieldname))
		    {				// act on specific parameters
				case 'idtd':
				{
				    if ($value > 0)
				    {
						$createRecord	= false;
						$where		.= $and . "`IDTD`=:idtd";
						$sqlParms['idtd']= $value;
				    }
				    else
				    if ($value < 0)
						$errorMsg	.= "'IDTD' value $value out of range. ";
				    break;
				}		// idtd

				case 'todoname':
				{
				    $where		.= $and . "`ToDoName`=:todoname";
				    $sqlParms['todoname']	= $value;
				    break;
				}		// todoname

				case 'todotype':
				case 'idir':
				case 'idtc':
				case 'idtl':
				case 'openedsd':
				case 'openedd':
				case 'remindersd':
				case 'reminderd':
				case 'closeedd':
				case 'idar':
				case 'status':
				case 'priority':
				case 'desc':
				case 'results':
				case 'filingred':
				case 'tag1':
				case 'qstag':
				case 'used':
				{		// other valid field names not used to search
				    break;
				}		// other valid field names

				default:
				{
				    $errorMsg	.= "'$fieldname' unsupported. ";
				    break;
				}
		    }				// act on specific search keys
		}				// loop through parameters

		if (count($parms) >= count(ToDo::$initRow))
		{		// complete row from database
		    $dbrow		= $parms;
		    $needInsert		= $dbrow['idtd'] == 0;
		    $where		= '';
		}		// complete row from database

		// fail if invalid parameters
		if (strlen($errorMsg) > 0)
		    throw new Exception("ToDo::__construct: ".
							    $errorMsg);

		if (strlen($where) > 0)
		{				// search for existing record
		    // construct the query of the work to do table
		    $query		= "SELECT * FROM tblTD WHERE " . $where;
		    
		    // query the database
		    $stmt		= $connection->prepare($query);

		    if ($stmt->execute($sqlParms))
		    {		// successful execute
				if ($debug)
				    print "<p>$query</p>";
				
				$result		= $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_null($result) && !$createRecord)
				{		// error performing query
				    throw new Exception("ToDo::__construct: ".
								"No record found for '$where', " .
								print_r($sqlParms, true));
				}		// error performing query
		    }		// successful execute
		    else
		    {		// error performing query
				throw new Exception("ToDo::__construct: ".
							    "'$query', " . 
							    print_r($sqlParms, true) . ', ' .
							    print_r($stmt->errorInfo(), true));
		    }		// error performing query
		}		// existing record
		else
		{		// create a new record
		    // set defaults
		    $dbrow		= self::$initRow;
		    $dbrow['openedsd']	= $sdate;
		    $dbrow['openedd']	= $ldate;
		}		// create new record

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblTD');
		$this->needInsert	= $dbrow['idtd'] == 0;

		// initialize field values from parameters
		if (count($parms) < count(ToDo::$initRow))
		foreach($parms as $fieldname => $value)
		{
		    $fieldNameLc	= strtolower($fieldname);
		    if ($fieldnameLc != 'idtd')
				$dbrow[$fieldNameLc]	= $value;
		}		// loop through parameters

		// diagnostic output if debug is set
		$this->dump('To Do Record constructed:');
    }		// function ToDo::__construct

    /********************************************************************
     *	function ToDo::getIdtd											*
     *																	*
     *	Retrieve the unique numeric key of this instance of				*
     *  ToDo.															*
     *																	*
     *	Note that this value is not available for a new ToDo		    *
     *  record, one created with index value 0 or a string name that	*
     *	does not match any existing work to do, until after the			*
     *	function save() has been called, because only then does the		*
     *	database server assign a value for the key.  Until then this	*
     *  method returns 0.												*
     ********************************************************************/
    function getIdtd() { return $this->row['idtd']; }

    /********************************************************************
     *	function ToDo::getCategory										*
     *																	*
     *	Get the numeric category code of the unit of work to do.		*
     ********************************************************************/
    function getCategory()
    {
		return $this->row['idtc'];
    }		// function ToDo::getCategory

    /********************************************************************
     *	function ToDo::setCategory										*
     *																	*
     *	Set the numeric type code of the unit of work to do.			*
     *																	*
     *	Parameters:														*
     *	    $newCategory		new value for IDTC						*
     *																	*
     *	Returns:														*
     *	    previous type												*
     *																	*
     *	Throws Exception on bad parameter.								*
     ********************************************************************/
    function setCategory($newCategory)
    {
		if (!array_key_exists($newCategory, self::$intCategory))
		{		// out of range of predefined values
		    // query the database
		    $query	        = "SELECT Category FROM tblTC WHERE IDTC=?";
		    $sqlParms	    = array($newCategory);
		    $stmt	        = $connection->prepare($query);
		    $queryText	    = debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				$result		= $stmt->fetch(PDO::FETCH_ASSOC);
				if (!is_array($result))
				{
				    error_log("ToDo::setCategory: ".
							    "invalid new type=$newCategory\n");
				    return $this->row['idtc'];
				}
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("ToDo::setCategory: ".
							     "'$queryText' result=" . 
							    print_r($stmt->errorInfo(),true));
		    }		// out of range of predefined values
		}		// out of range of predefined values
		return parent::set('idtc', $newCategory);
    }		// function ToDo::setCategory

    /********************************************************************
     *	function ToDo::getCategoryText									*
     *																	*
     *	Get the textual interpretation of the category code of the		*
     *	unit of research work to do.									*
     ********************************************************************/
    function getCategoryText()
    {
		global $debug;
		global $connection;

		$type		= $this->row['idtc'];
		if (array_key_exists($type, self::$intCategory))
		    return self::$intCategory[$type];

		// query the database
		$query		= "SELECT Category FROM tblTC WHERE IDTC=?";
		$sqlParms	= array($type);
		$stmt		= $connection->prepare($query);
		$queryText	= debugPrepQuery($query, $sqlParms);
		if ($stmt->execute($sqlParms))
		{		// success
		    if ($debug)
		        print "<p>ToDo::getCategoryText: " . __LINE__ . 
							" $queryText</p>";
		    $result		= $stmt->fetch(PDO::FETCH_ASSOC);
		    if (is_array($result))
		    {		// found work to do type record
		        return $result['category'];
		    }		// found work to do type record
		    else
		    {		// key not found
		        return 'Unknown ToDo Category: ' . $type;
		    }		// key not found
		}		// success
		else
		{		// error performing query
		    error_log("ToDo.inc: " . __LINE__ . " $queryText, " .
						     print_r($stmt->errorInfo(),true) . "\n");
		    return 'Unknown ToDo Category: ' . $type;
		}		// error performing query

    }		// function ToDo::getCategoryText

    /********************************************************************
     *	function ToDo::getName											*
     *																	*
     *	Get the name of the unit of research work to do.				*
     ********************************************************************/
    function getName($options	= null)
    {
		return $this->row['todoname'];
    }		// function ToDo::getName

    /********************************************************************
     *	function ToDo::setName											*
     *																	*
     *	Set the name of the unit of research work to do.				*
     *																	*
     *	Parameters:														*
     *	    $newName		new value for work to do name				*
     *																	*
     *	Returns:														*
     *	    previous name												*
     ********************************************************************/
    function setName($newName)
    {
		return parent::set('todoname', $newName);
    }		// function ToDo::setName

    /********************************************************************
     *	function ToDo::set												*
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
     *	    Former value of field										*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'openedd':
		    {		// legacy internal date format
				if ($value instanceof LegacyDate)
				{	// parameter is an instance of LegacyDate
				    $date	= $value;
				}	// parameter is an instance of LegacyDate
				else
				{	// convert the value to instance of LegacyDate
				    $date	= new LegacyDate(' ' . $value);
				}	// convert the value to instance of LegacyDate
				parent::set('openedsd', $date->getSortDate());
				return parent::set('openedd', $date->getDate());
		    }		// legacy internal date format

		    case 'reminderd':
		    {		// legacy internal date format
				if ($value instanceof LegacyDate)
				{	// parameter is an instance of LegacyDate
				    $date	= $value;
				}	// parameter is an instance of LegacyDate
				else
				{	// convert the value to instance of LegacyDate
				    $date	= new LegacyDate(' ' . $value);
				}	// convert the value to instance of LegacyDate
				parent::set('remindersd', $date->getSortDate());
				return parent::set('reminderd', $date->getDate());
		    }		// legacy internal date format

		    case 'closedd':
		    {		// legacy internal date format
				if ($value instanceof LegacyDate)
				{	// parameter is an instance of LegacyDate
				    $date	= $value;
				}	// parameter is an instance of LegacyDate
				else
				{	// convert the value to instance of LegacyDate
				    $date	= new LegacyDate(' ' . $value);
				}	// convert the value to instance of LegacyDate
				//parent::set('closedsd', $date->getSortDate());
				return parent::set('closedd', $date->getDate());
		    }		// legacy internal date format

		    case 'idar':
		    {		// value is IDAR
				if ($value instanceof Address)
				{	// parameter is an instance of LegacyDate
				    return parent::set('idar', $value['idar']);
				}	// parameter is an instance of LegacyDate
				else
				if (is_int($value) || ctype_digit($value))
				    return parent::set('idar', $value);
				else
				    throw new Exception("ToDo::set: invalid value '$value' for field '$field'");
		    }		// value is IDAR

		    case 'openedsd':
		    case 'remindersd':
		    case 'closedsd':
		    {		// legacy internal date format
				// do not change the value, it is set by set('eventd')
				return $this->row[$field];
		    }		// legacy internal date format

		    default:
		    {		// no validation implemented yet
				return parent::set($field, $value);
		    }		// no validation implemented yet
		}		    // switch on field name
    }		// function ToDo::set

    /********************************************************************
     *  function ToDo::toXml											*
     *																	*
     *  Emit an XML representation of the record.						*
     *																	*
     *  Most fields in the record are represented by a child element	*
     *	with the name of the field containing the internal value as text*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two elements.  The internal encoded string value appears in an	*
     *	element with 'c' appended to the field name, while the element	*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'todo'			*
     *	    $print		if true print the output, else just return it	*
     *	    $options		match signature								*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/
    function toXml($top		= 'todo',
				   $print	= true,
				   $options	= 0)
    {
		$idtd		= $this->row['idtd'];
		$retval		= "<$top idtd='$idtd'>\n";

		foreach($this->row as $fld => $value)
		{	        	// loop through all fields
		    switch($fld)
		    {
				case 'idar':
				{	    // IDAR of repository
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    if ($value > 0)
				    {
						try {
						    $repo	= new Address(array('idar' => $value));
						    $retval	.= $repo->toXml('repository', false);
						} catch (Exception $e) {
						    $retval	.= "<msg>" . $e->getMessage() .
								   "</msg>\n";
						}
				    }
				    break;
				}

				case 'openedd':
				case 'reminderd':
				case 'closedd':
				{		// legacy internal date format
				    $date	= new LegacyDate($value);
				    $retval	.= "    <{$fld}c>" . xmlentities($value) . "</{$fld}c>\n";
				    $value	= $date->toString();
				    $retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    break;
				}		// legacy internal date format

				case 'idtc':
				{	    // document type
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    $value	= $this->getCategoryText();
				    $retval	.= "    <category>" . xmlentities($value) .
						"</category>\n";
				    break;
				}

				default:
				{		// fields passed back with no interpretation
				    $retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    break;
				}		// fields passed back with no interpretation
		    }		    // switch on field name
		}		        // loop through all fields
		
		$retval	.= "</$top>\n";
    }		// ToDo::toXml

}		// class ToDo 

