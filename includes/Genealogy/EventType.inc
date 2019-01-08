<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  EventType.inc														*
 *																		*
 *  Definition of a class representing an event type in a genealogical		*
 *  database.  This class provides access to the information in a row		*
 *  of the table tblET.														*
 *  This table does not support i18n so it is not used by this				*
 *  implementation but it is maintained for compatibility with Legacy		*
 *  Family Tree.														*
 *																		*
 *		Table: tblET - Event Type Name List, we originally called these		*
 *						Event Type.										*
 *		 						RECORD 1 will always exist as blank		*
 *								record. Don't delete it.				*
 *																		*
 *       Columns														*
 *		    Name						Type		   Size						*
 *		    IDET						Long Integer		  4				*
 *						used by: tblER.IDET								*
 *		    EventType						Text				100				*
 *						the name assigned to an event						*
 *		    Used						Byte				  1				*
 *						temporary use, 0=no, 1=yes						*
 *		    Tag1						Byte				  1				*
 *						0=no, 1=yes										*
 *		    ShowDate						Byte				  1				*
 *						0=No, 1=Yes										*
 *		    ShowDescription				Byte				  1				*
 *						0=No, 1=Yes										*
 *		    ShowPlace						Byte				  1				*
 *						0=No, 1=Yes										*
 *		    Sentence						Text				255				*
 *		    Sentence2						Text				255				*
 *		    Sentence3						Text				255				*
 *		    Sentence4						Text				255				*
 *		    Sentence5						Text				255				*
 *		    Sentence6						Text				255				*
 *		    Sentence7						Text				255				*
 *		    Sentence8						Text				255				*
 *		    Private						Byte				  1				*
 *						0=no, 1=yes										*
 *		    PPExclude						Byte				  1				*
 *				    Exclude from Potential Problems Report,				*
 *						0=no, 1=yes										*
 *		    qsTag						Byte				  1				*
 *				    temporary use										*
 *						0=no, 1=yes										*
 *		    RGExclude						Byte				  1				*
 *						0=no, 1=yes										*
 *																		*
 *  History:																*
 *		2010/10/11		use explicit query code								*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global								*
 *		2010/12/26		add getPreposition method						*
 *						add getNotes method								*
 *						eliminate internal member idtr						*
 *		2011/01/02		use regexp in construct by name						*
 *						throw Exception on construction failure				*
 *		2011/06/11		add methods getFirst and getNext				*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/05/13		correct comparison for abbreviations				*
 *						support optional 2-character abbreviations used		*
 *						by GEDCOM 5.5										*
 *		2012/08/15		add toString method								*
 *		2012/08/18		do not throw exception on IDET 0				*
 *		2012/10/06		improve comments								*
 *		2012/10/20		standardize initialization of new record		*
 *		2013/08/09		base class renamed to Record						*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/11/01		use prepared statement								*
 *						accept associative array search parms				*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class EventType														*
 *																		*
 *  Definition of a class implementing behavior for event type records		*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class EventType extends Record
{

    /********************************************************************
     *		$initRow														*
     *		static array containing default values for a new record				*
     *																		*
     *		This is not actually used for this class, but is retained for		*
     *		compatibility with the other classes derived from Record		*
     ********************************************************************/
    protected static	$initRow	= array(
				'idet'		=> 0,
				'eventtype'	=> '',
				'used'		=> 0,
				'tag1'		=> 0,
				'showdate'	=> 0,
				'showdescription'=> 0,
				'showplace'	=> 0,
				'sentence'	=> '[HeShe] [EventName] [Desc] [onDate] [inPlace].[Sources] [Notes]', 
				'sentence2'	=> '[HeShe] [EventName] [onDate].[Sources] [Notes]', 
				'sentence3'	=> '[HeShe] [EventName] [inPlace].[Sources] [Notes]', 
				'sentence4'	=> '[HeShe] [EventName] [Desc].[Sources] [Notes]', 
				'sentence5'	=> '[HeShe] [EventName] [Desc] [onDate].[Sources] [Notes]', 
				'sentence6'	=> '[HeShe] [EventName] [Desc] [inPlace].[Sources] [Notes]', 
				'sentence7'	=> '[HeShe] [EventName] [onDate] [inPlace].[Sources] [Notes]', 
				'sentence8'	=> '[HeShe] [EventName].[Sources] [Notes]', 
				'private'	=> 0,
				'ppexclude'	=> 0,
				'qstag'		=> 0,
				'rgexclude'	=> 0);

    /********************************************************************
     *		EventType::__construct												*
     *																		*
     *  Given the identifier or database result construct an event		*
     *		type record.														*
     *																		*
     *  Input:																*
     *		    $parms		search parameters as associative array, or		*
     *						a database record presented as an array, or		*
     *			numeric identifier of a event type (key of tblET)
     *						0 or 'new': return a new default instance		*
     *																		*
     *		Returns:														*
     *		    instance of EventType										*
     ********************************************************************/
    function __construct($parms = array())
    {
		global	$debug;
		global	$warn;
		global	$connection;

		// action depends upon type of first parameter
		// convert deprecated formats to search parms array
		if (is_string($parms) && ctype_digit($parms))
		    $parms	= array('idet'	=> (int)$parms);
		else
		if ($parms == 'new')
		    $parms	= array('idet'	=> 0);
		else
		if (is_int($parms))
		    $parms	= array('idet'	=> $parms);

		if (is_array($parms))
		{		// array parameter
		    $idet		= 0;
		    $validFields	= true;
		    $needInsert		= false;
		    foreach($parms as $field => $value)
		    {
				$fieldLc		= strtolower($field);
				if ($fieldLc == 'idet')
				{
				    $idet		= $value;
				}
				else
				if (!array_key_exists($fieldLc, self::$initRow))
				{
				    $validFields	= false;
				    $warn		.= "<p>EventType::__construct: " .
								" Invalid field name '$field'.</p>\n";
				}
		    }

		    // check for full database row
		    if ($validFields && count($parms) == count(self::$initRow))
		    {
				$dbrow		= $parms;
				$needInsert	= false;
		    }
		    else
		    if ($idet > 0)
		    {			// IDET value
				// construct the query of the event type table
				$query		= "SELECT * FROM tblET WHERE IDET=:idet";
				$sqlParms	= array('idet' => $idet);
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $sqlParms);
				
				// query the database
				if ($stmt->execute($sqlParms))
				{		// success
				    $row		= $stmt->fetch(PDO::FETCH_ASSOC);

				    if ($debug)
						print "<p>$query</p>";
				    
				    if (is_array($row))
				    {		// found matching event type record
						$dbrow		= $row;
						$needInsert	= false;
				    }		// found matching event type record
				    else
				    {		// record not found
						$dbrow		= self::$initRow;
						$needInsert	= true;
				    }		// record not found
				}		// success
				else
				{		// error performing query
				    throw new Exception("EventType::__construct: " . 
								"query='$queryText' " .
								print_r($connection->errorInfo(),true));
				}		// error performing query
		    }		// valid code
		    else
		    {		// create new default record
				$dbrow		= self::$initRow;
				$needInsert	= true;
		    }		// create new default record
		}		// numeric identifier of record
		else
		{		// unexpected parameter type
		    throw new Exception("EventType::__construct: called with id type " .
				 gettype($id));
		}		// unexpected parameter type

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblET');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('EventType Record constructed:');
    }		// EventType::__construct

}		// class EventType 

