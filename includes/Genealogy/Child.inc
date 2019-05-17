<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Child.inc															*
 *																		*
 *  Definition of a class representing a child record in a genealogical	*
 *  database.  This class provides access to the information in a row	*
 *  of the table tblCR.													*
 *																		*
 *  This record contains information about the relationship between an	*
 *  individual and the individual's parents.							*
 *  The Sealed to Parents event formerly kept in this record has been	*
 *  moved to an instance of Event.                                      *
 *																		*
 *     Columns															*
 *		    Name					  Type								*
 *		    IDCR					  INTEGER(10)						*
 *		    IDMR					  INTEGER(10)						*
 *				    --> tblMR.IDMR, ID to parents marriage record		*
 *		    IDIR					  INTEGER(10)						*
 *				    --> tblIR.IDIR, ID to child's individual record		*
 *		    Order					  SMALLINT(5)						*
 *				    used as sort order.									*
 *		    PrefChild				  TINYINT(1)						*
 *				    1=preferred, 0=not									*
 *		    IDCS					  INTEGER(10)						*
 *				    --> tblCS.IDCS, ID to Child Status record			*
 *						see Child::$intStatus							*
 *		    IDCPDad					  INTEGER(10)						*
 *				    --> tblCP.IDCP, ID to Child/Parent Rel'ship record	*
 *						see Child::$intType								*
 *		    IDCPMom					  INTEGER(10)						*
 *				    --> tblCP.IDCP, ID to Child/Parent Rel'ship record	*
 *						see Child::$intType								*
 *		    CPDadPrivate			  TINYINT(1)						*
 *				    0=No, 1=Yes											*
 *		    CPMomPrivate			  TINYINT(1)						*
 *				    0=No, 1=Yes											*
 *		    ParSealSD				  INTEGER(10)						*
 *				    (see Sort Date) Deprecated							*
 *		    ParSealD				  VARCHAR(100)						*
 *				    (see Date) Deprecated								*
 *		    IDTRParSeal				  INTEGER(10)						*
 *				    --> tblTR.IDTR, ID to Temple Record Deprecated		*
 *		    LDSP					  TINYINT(1)						*
 *				1=marked for Seal to Parents TempleReady submission,	*
 *				0=not marked											*
 *						Deprecated										*
 *		    TempleTag				  TINYINT(1)						*
 *				    temporary use, 0=no, 1=yes							*
 *		    ParSealNote				  LONGTEXT		  -				    *
 *						Deprecated										*
 *																		*
 *  History:															*
 *		2010/11/29		created											*
 *		2010/12/02		accept numeric string as key					*
 *		2010/12/21		throw exception from methods on bad parameters	*
 *		2010/12/23		above fix revealed querying tblSR instead of	*
 *						tblCR											*
 *		2011/01/03		also created on tblSR							*
 *		2011/03/07		throw exception if try to add child that is		*
 *						alreadymember of family							*
 *		2012/01/14		change class name to LegacyChild				*
 *		2012/10/02		use LegacyRecord::setField to manipulate the	*
 *						record contents so the save method will			*
 *						correctlyconstruct the required INSERT or		*
 *						UPDATE SQL command								*
 *		2012/10/20		make default initial row array a static member	*
 *						use save method to add child to family			*
 *		2012/10/30		add field idcr to default record				*
 *		2013/03/02		correct setting of needInsert					*
 *						validate in setCPRelDad and setCPRelMom and		*
 *						invoke those functions from new					*
 *						Child::setField									*
 *						do not query database for extensions to			*
 *						pre-defined interpretive texts					*
 *						move static function getChildByIdirIdmr to		*
 *						method LegacyFamily::getChildByIdir				*
 *						add Child::getFamily							*
 *						add Child::getPerson							*
 *		2013/04/20		support setting illegitimate relationship to	*
 *						parents											*
 *						correct error in reporting invalid parm to		*
 *						setCPRel***										*
 *		2013/08/09		base class renamed to Record					*
 *		2014/02/25		correct fields incorrectly set to null in		*
 *						constructor										*
 *						validate all fields in setField					*
 *		2014/03/06		do not write records with IDIR=0 to database	*
 *		2014/03/14		add support for locating or adding a specific	*
 *						individual as a child of a specific family		*
 *						to the constructor								*
 *						add static function getChildren					*
 *		2014/10/01		add method addOwner								*
 *		2014/11/30		correct misspelled $cquery to $queryc			*
 *		2014/12/04		add method getParSealEvent						*
 *						redirect diagnostic output to $warn				*
 *		2015/01/06		add method getName								*
 *		2015/01/14		add method clearEvent to permit Event			*
 *						to remove an event from this record				*
 *		2015/02/20		support new Child(array('idcr' => $idcr))		*
 *						add method toXml								*
 *						support order by birthsd in getChildren			*
 *						support constructing child when instance of		*
 *						LegacyFamily does not have an IDMR value		*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2017/02/02		add method toJson								*
 *		2017/03/19		use prefered interface to new LegacyIndiv		*
 *						use prefered interface to new LegacyFamily		*
 *		2017/08/07		class LegacyChild renamed to class Child		*
 *						use prepared statement in getChildren			*
 *						add static function deleteChildren				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/10		change class LegacyFamily to class Family		*
 *						complete migration to PDO prepared statements	*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *						rename method getIndiv to getPerson				*
 *		2017/11/02		add translate table to enable `Order` to be		*
 *						referenced using a non-reserved word			*
 *						add default sort order for RecordSet			*
 *						Set child order for new record to 1 greater		*
 *						than the current highest value rather than count*
 *		2017/11/11		remove static functions replace by RecordSet	*
 *		2017/12/18		add support for temporary fields				*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/04/18      support method getLastSqlCmd                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';

/************************************************************************
 *  class Child															*
 *																		*
 *  Definition of a class implementing behavior for Child records		*
 *  within a genealogy database.										*
 ************************************************************************/

class Child extends Record
{
    /********************************************************************
     *		Options for method getName									*
     ********************************************************************/
    const	NAME_INCLUDE_DATES	= 1;
    const	NAME_SURNAME_FIRST	= 2;
    const	NAME_INCLUDE_MDATE	= 4;

    /********************************************************************
     *	Child->$sealEvent	instance of Event describing LDS sealing 	*
     ********************************************************************/
    private	$sealEvent	= null;

    /********************************************************************
     *	Child::$initRow													*
     *																	*
     *	static array containing default values for all fields in a		*
     *	new record														*
     ********************************************************************/
    protected static	$initRow	= array(
				'idcr'				    => 0,		// key of record
				'idmr'				    => 0,		// IDMR of parents
				'idir'				    => 0,		// IDIR of child
				'order'				    => 0,		// order in family
				'prefchild'			    => 0,
				'idcs'				    => 1,		// default status
				'idcpdad'			    => 1,		// relation to father
				'idcpmom'			    => 1,		// relation to mother
				'cpdadprivate'			=> 0,		// reltodad private?
				'cpmomprivate'			=> 0,		// reltomom private?
				'parsealsd'			    => -99999999,	// deprecated
				'parseald'			    => '',		// deprecated
				'idtrparseal'			=> 1,		// deprecated
				'ldsp'				    => 0,		// deprecated
				'templetag'			    => 0,
				'parsealnote'			=> '');		// deprecated

    /********************************************************************
     *	Child::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                		'table'     		=> 'tblCR',
                		'name'      		=> 'Children',
                		'prime'	    		=> 'idcr',
	            		'srvmg'     		=> true,
	            		'fldcount'			=> 4,
                		'order'     		=> '`order`',
                		'classname' 		=> 'Child');

    /********************************************************************
     *	Child::$translate												*
     *																	*
     *	static array containing synonyms for field names in the record	*
     ********************************************************************/
    protected static	$translate	= array(
				        'childorder'			=> 'order');

    /********************************************************************
     *	Child::$defaultOrder											*
     *																	*
     *	The default value of the ORDER BY clause in the iteration of	*
     *	a RecordSet for this class.										*
     ********************************************************************/
    protected static	$defaultOrder	= '`Order`';

    /********************************************************************
     *	Child::$intStatus	                        					*
     *	interpret the status field					                    *
     *	This saves having to use the database server		            *
     *	to retrieve the most common values.				                *
     ********************************************************************/
    static $intStatus	= array(
						 1 	=>	'',
						 2 	=>	'None',
						 3 	=>	'Stillborn',
						 4 	=>	'Twin',
						 5 	=>	'Illegitimate'
								);

    // Symbolic constants for calling Child::setStatus
    const	STATUS_NULL		    = 1;
    const	STATUS_NONE		    = 2;
    const	STATUS_STILLBORN	= 3;
    const	STATUS_TWIN		    = 4;
    const	STATUS_ILLEGITIMATE	= 5;

    /********************************************************************
     *  Child::$intType													*
     *																	*
     *  Interpretation of values of Child to Parent Relationships.		*
     *  These values are defined in the database table tblCP, which		*
     *  permits extending the definitions.								*
     ********************************************************************/
    static $intType	= array(
						 1 => '',
						 2 => 'adopted',
						 3 => 'biological',
						 4 => 'challenged',
						 5 => 'disproved',
						 6 => 'foster',
						 7 => 'guardian',
						 8 => 'sealing',
						 9 => 'step',
						10 => 'unknown',
						11 => 'private',
						12 => 'family member',
						13 => 'illegitimate');

    // Symbolic constants for calling Child::setCpRelxxx
    const	RELATE_NULL		    =  1;
    const	RELATE_ADOPTED		=  2;
    const	RELATE_BIOLOGICAL	=  3;
    const	RELATE_CHALLENGED	=  4;
    const	RELATE_DISPROVED	=  5;
    const	RELATE_FOSTER		=  6;
    const	RELATE_GUARDIAN		=  7;
    const	RELATE_SEALING		=  8;
    const	RELATE_STEP	    	=  9;
    const	RELATE_UNKNOWN		= 10;
    const	RELATE_PRIVATE		= 11;
    const	RELATE_FAMILYMEMBER	= 12;
    const	RELATE_ILLEGITIMATE	= 13;

    /********************************************************************
     *  function Child::__construct										*
     *																	*
     *  Construct an instance of Child based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.  In particular the value of the primary key		*
     *	obtained through calling Child::getIdcr, or Record::getId		*
     *	of Child::get('idcr') may not be initialized.					*
     *																	*
     *  Input:															*
     *	    $id			a full database record presented as an			*
     *					associative array of fields, or					*
     *					an associative array of key values to			*
     *					identify the record to use.  This can be either	*
     *					array('idcr' => $idcr) or						*
     *					array('idmr' => $idmr, 'idir' => $idir)			*
     *					The second form identifies the record for a		*
     *					particular individual as a child of a specific	*
     *					family to locate or add a specific individual	*
     *					as a child of a specific family.				*
     *																	*
     *  Returns object containing child record.							*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($id)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,
								  PDO::FETCH_ASSOC);
		$dbrow		= null;
		$needInsert	= false;

		// action depends upon type of parameter
		if (is_int($id))
		    $parms	= array('idcr' => $id);
		else
		if (is_string($id) && ctype_digit($id))
		    $parms	= array('idcr' => intval($id));
		else
		if ($id == 'new')
		    $parms	= array('idcr' => 0);
		else
		if (is_array($id))
		    $parms	= $id;
		else
		    throw new Exception("Child::__construct: " .
								"called with " .  print_r($id, true));

		// $parms is now an associative array
		$idcr		= null;
		$idmr		= null;
		$idir		= null;
		$where		= '';
		$sqlParms	= array();
		$and		= 'WHERE ';
		foreach($parms as $fldname => $value)
		{			// loop through all parameters
		    $fieldLc	= strtolower($fldname);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	= self::$translate[$fieldLc];
		    switch($fieldLc)
		    {			// act on specific field names
				case 'idcr':
				{		// IDCR identify specific instance
				    if (is_string($value) && ctype_digit($value))
				    {
						$idcr			= intval($value);
						if ($idcr > 0)
						{
						    $where		.= $and . 'IDCR=:idcr';
						    $sqlParms['idcr']	= $idcr;
						    $and		= ' AND ';
						}
				    }
				    else
				    if (is_int($value))
				    {
						$idcr			= $value;
						if ($idir < 0)
						    $warn	.= "<p>Field Name 'IDCR' value '$value' out of range.</p>";
						else
						if ($idcr > 0)
						{
						    $where		.= $and . 'IDCR=:idcr';
						    $sqlParms['idcr']	= $idcr;
						    $and		= ' AND ';
						}
				    }
				    else
						$warn	.= "<p>Field Name 'IDCR' value '$value' unsupported.</p>\n";
				    break;
				}		// IDCR identify specific instance

				case 'idmr':
				{		// IDMR identify parents
				    if (is_string($value) && ctype_digit($value))
				    {
						$idmr			= intval($value);
						$where			.= $and . 'IDMR=:idmr';
						$sqlParms['idmr']	= $idmr;
						$and			= ' AND ';
				    }
				    else
				    if (is_int($value))
				    {
						$idmr			= $value;
						if ($idmr < 0)
						    $warn	.= "<p>Field Name 'IDMR' value '$value' out of range.</p>\n";
						$where			.= $and . 'IDMR=:idmr';
						$sqlParms['idmr']	= $idmr;
						$and			= ' AND ';
				    }
				    else
						$warn	.= "<p>Field Name 'IDMR' value '$value' unsupported.</p>\n";
				    break;
				}		// IDMR identify parents

				case 'idir':
				{		// IDIR identify child
				    if (is_string($value) && ctype_digit($value))
				    {
						$idir			= intval($value);
						if ($idir < 1)
						    $warn	.= "<p>Field Name 'IDIR' value '$value' out of range.</p>\n";
						$where			.= $and . 'IDIR=:idir';
						$sqlParms['idir']	= $idir;
						$and			= ' AND ';
				    }
				    else
				    if (is_int($value))
				    {
						$idir			= $value;
						if ($idir < 1)
						    $warn	.= "<p>Field Name 'IDIR' value '$value' out of range.</p>\n";
						$where			.= $and . 'IDIR=:idir';
						$sqlParms['idir']	= $idir;
						$and			= ' AND ';
				    }
				    else
						$warn	.= "<p>Field Name 'IDIR' value '$value' unsupported. </p>\n";
				    break;
				}		// IDIR identify child

				case 'order':
				case 'prefchild':
				case 'idcs':
				case 'idcpdad':
				case 'idcpmom':
				case 'cpdadprivate':
				case 'cpmomprivate':
				case 'parsealsd':
				case 'parseald':
				case 'idtrparseal':
				case 'ldsp':
				case 'templetag':
				case 'parsealnote':
				{		// other valid field names
				    if ($fldname != $fieldLc)
				    {		// ensure all field names are lower case
						unset($parms[$fldname]);
						$parms[$fieldLc]	= $value;
				    }		// ensure all field names are lower case
				    break;
				}		// other valid field names

				default:
				{		// unrecognized field names
				    $warn	.= "<p>Child::__construct: " . __LINE__ . 
								" Field Name '$fldname' not recognized.</p>";
				    break;
				}		// unrecognized field names
		    }			// act on specific field names
		}			// loop through all parameters

		$count		= count($parms);
		if ($count >= count(self::$initRow))
		{		// complete row from database
		    $dbrow		= $parms;
		    $needInsert		= $dbrow['idcr'] == 0;
		}		// complete row from database
		else
		{		// search parameters
		    // act on specific parameter sets
		    if (strlen($where) > 0)
		    {			// existing record
				// construct the query of the child table
				$query		        = 'SELECT * FROM tblCR ' . $where;
				
				// query the database
				$stmt		        = $connection->prepare($query);
				$queryText	        = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
				if ($stmt->execute($sqlParms))
				{		// successful query
				    $result		    = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if ($debug)
						$warn		.= '<p>Child:__construct: ' . __LINE__ .
									   "query='$queryText'</p>\n";
		
				    $numMatches	= count($result);
				    if ($numMatches == 1)
				    {		// found child record
						$dbrow		= $result[0];
						$needInsert	= false;
				    }		// found child record
				    else
				    if ($numMatches == 0)
				    {		// record not found
						if ($idir > 0)
						{	// add new child to family
						    $dbrow		= self::$initRow;
						    $needInsert		= true;
						    $dbrow['idmr']	= $idmr;
						    $dbrow['idir']	= $idir;
		
						    if ($idmr > 0)
						    {		// existing family
								// order new child after all existing children
								$queryc		= "SELECT COALESCE(MAX(`Order`)+1,0) FROM tblCR WHERE IDMR=:idmr";
								$sqlParms	= array('idmr' => $idmr);

								// query the database
								$stmt	        = $connection->prepare($queryc);
								$queryText	    = debugPrepQuery($queryc,
								            	    			 $sqlParms);
                            $this->lastSqlCmd   = $queryText;
								if ($stmt->execute($sqlParms))
								{		// successful query
								    $row	    = $stmt->fetch(PDO::FETCH_NUM);
								    $newOrder	= $row[0];
								    if ($debug)
									$warn	.= "<p>Child::__construct: " .
						__LINE__ . " '$queryText' returns $newOrder</p>";
								}		// successful query
								else
								{		// error performing query
								    throw new Exception("Child::__construct: " .
										__LINE__ . " '$queryText', " .
									print_r($stmt->errorInfo(), true));
								}		// error performing query
						    }		// existing family
						    else
								$newOrder	= 0;
						    $dbrow['order']	= $newOrder;
						}	// add new child to family
						else
						    throw new Exception("Child::__construct: " .
									    "Record not found $queryText");
				    }		// record not found
				    else
				    {		// too many records found
						throw new Exception("Child::__construct: " .
									    "$numMatches found $queryText");
				    }		// too many records found
				}		// successful query
				else
				{		// error performing query
				    throw new Exception("Child::__construct: " .
									"'$queryText' " .
									print_r($stmt->errorInfo(),true));
				}		// error performing query
		    }			// existing record
		    else
		    if ($idcr === 0)
		    {			// create a new record
				// set defaults
				$dbrow		= self::$initRow;
				$needInsert	= true;
		    }			// create new record
		    else
		    {			// invalid record
				throw new Exception("Child::__construct: " .
								    "Invalid array parameter :" .
								    print_r($parms, true));
		    }			// invalid record
		}			// search parameters

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblCR');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('Child Record constructed:');
    }		// function Child::__construct

    /********************************************************************
     *  function Child::getIdcr											*
     *																	*
     *	Retrieve the unique numeric key of this instance of Child.		*
     *	Note that this value is not available for a new Child			*
     *	record, that is one created with parameter 0 or 'new', until	*
     *	after the function save() has been called, because only then	*
     *	does the database server assign a value for the key.  Until		*
     *	then this method throws an exception.							*
     *																	*
     *  Returns:														*
     *	    unique key/index of this instance of Child					*
     ********************************************************************/
    function getIdcr()
    {
		if ($this->needInsert)
		    throw new Exception("Child::getIdcr: " .
								"record does not exist in database"); 
		return $this->row['idcr'];
    }		// function Child::getIdcr

    /********************************************************************
     *  function Child::getIdmr											*
     *																	*
     *	Retrieve the unique numeric key of the instance of Family		*
     *	representing the parents documented by this instance of			*
     *  Child.	        												*
     *																	*
     *  Returns:														*
     *	    unique numeric key of an instance of Family					*
     ********************************************************************/
    function getIdmr()
    {
		return $this->row['idmr']; 
    }		// function Child::getIdmr

    /********************************************************************
     *  function Child::getFamily										*
     *																	*
     *	Retrieve the instance of Family									*
     *	representing the parents documented by this instance of			*
     *  Child.															*
     *																	*
     *	Input:															*
     *	    an instance of Family										*
     *																	*
     *	Throws:															*
     *	    exception if value of IDMR does not match existing family	*
     ********************************************************************/
    function getFamily() 
    { 
		return new Family(array('idmr' => $this->row['idmr']));
    }		// function Child::getFamily

    /********************************************************************
     *  function Child::getIdir											*
     *																	*
     *	Retrieve the unique numeric key of the instance of Person		*
     *	representing the child documented by this instance of			*
     *  Child.															*
     *																	*
     *	Input:															*
     *	    unique numeric key of an instance of Person					*
     ********************************************************************/
    function getIdir()
    {
		return $this->row['idir'];
    }		// function Child::getIdir

    /********************************************************************
     *  function Child::getPerson										*
     *																	*
     *	Retrieve the instance of Person									*
     *	representing the individual who is a child.						*
     *																	*
     *	Input:															*
     *	    an instance of Person										*
     *																	*
     *	Throws:															*
     *	    exception if value of IDIR does not match existing Person	*
     ********************************************************************/
    function getPerson() 
    {
        if (!class_exists(__NAMESPACE__ . '\\Person'))
			include __NAMESPACE__ . '/Person' . ".inc";

		return new Person(array('idir' => $this->row['idir']));
    }		// function Child::getPerson

    /********************************************************************
     *  function Child::getStatus										*
     *																	*
     *	Get the numeric status code of the child in the family.			*
     *																	*
     *  Returns:														*
     *	    1 =>		'',												*
     *	    2 =>		'None',											*
     *	    3 =>		'Stillborn',									*
     *	    4 =>		'Twin',											*
     *	    5 =>		'Illegitimate'									*
     ********************************************************************/
    function getStatus()
    {
		return $this->row['idcs'];
    }		// function Child::getStatus

    /********************************************************************
     *  function Child::setStatus										*
     *																	*
     *	Set the numeric status code of the child in the family.			*
     *																	*
     *	Input:															*
     *		    $newval		new status value.							*
     *          Child::STATUS_NULL										*
     *  		Child::STATUS_NONE										*
     *  		Child::STATUS_STILLBORN									*
     *  		Child::STATUS_TWIN										*
     *  		Child::STATUS_ILLEGITIMATE								*
     *																	*
     *  Returns:														*
     *		    old status code value									*
     *																	*
     *	Throws:															*
     *	    exception if new value is not supported						*
     ********************************************************************/
    function setStatus($newval)
    {
		if (array_key_exists($newval, self::$intStatus))
		    return parent::set('idcs',	$newval);
		else
		    throw new Exception("Child::setStatus: " .
						"called with value='$newval'");
    }		// function Child::getStatus


    /********************************************************************
     *  function Child::getStatusText									*
     *																	*
     *	Get the textual interpretation of the status code of the child.	*
     *																	*
     *  Returns:														*
     *		string														*
     ********************************************************************/
    function getStatusText()
    {
		$status	= $this->row['idcs'];
		if (array_key_exists($status, self::$intStatus))
		    return self::$intStatus[$status];

		return 'Unknown Status: ' . $status;
    }		// function Child::getStatusText

    /********************************************************************
     *  function Child::getCPRelDad										*
     *																	*
     *	Get the numeric code for the relationship between the individual*
     *	identified by field 'idir' and the husband in the family		*
     *	identified by field 'idmr'.										*
     *																	*
     *  Returns:														*
     *	    integer														*
     ********************************************************************/
    function getCPRelDad()
    {
		return $this->row['idcpdad'];
    }		// function Child::getCPRelDad

    /********************************************************************
     *  function Child::getCPRelMom										*
     *																	*
     *	Get the numeric code for the relationship between the individual*
     *	identified by field 'idir' and the wife in the family			*
     *	identified by field 'idmr'`.									*
     *																	*
     *  Returns:														*
     *	    integer														*
     ********************************************************************/
    function getCPRelMom()
    {
		return $this->row['idcpmom'];
    }		// function Child::getCPRelMom

    /********************************************************************
     *  function Child::setCPRelDad										*
     *																	*
     *	Set the numeric code for the relationship between the individual*
     *	identified by field 'idir' and the husband in the family		*
     *	identified by field 'idmr'.										*
     *																	*
     *	Parameter:														*
     *	    $newval		the new relationship code.						*
     *  		Child::RELATE_NULL										*
     *  		Child::RELATE_ADOPTED									*
     *  		Child::RELATE_BIOLOGICAL								*
     *  		Child::RELATE_CHALLENGED								*
     *  		Child::RELATE_DISPROVED									*
     *  		Child::RELATE_FOSTER									*
     *  		Child::RELATE_GUARDIAN									*
     *  		Child::RELATE_SEALING									*
     *  		Child::RELATE_STEP										*
     *  		Child::RELATE_UNKNOWN									*
     *  		Child::RELATE_PRIVATE									*
     *  		Child::RELATE_FAMILYMEMBER								*
     *  		Child::RELATE_ILLEGITIMATE								*
     *																	*
     *  Returns:														*
     *	    old relationship code value									*
     *																	*
     *	Throws:															*
     *	    exception if new value is not supported						*
     ********************************************************************/
    function setCPRelDad($newval)
    {
		if (array_key_exists($newval, self::$intType))
		    return parent::set('idcpdad',	$newval);
		else
		    throw new Exception("Child::setCPRelDad: " .
						"called with value='$newval'");
    }		// function Child::setCPRelDad

    /********************************************************************
     *  function Child::setCPRelMom										*
     *																	*
     *	Set the numeric code for the relationship between the individual*
     *	identified by field 'idir' and the wife in the family			*
     *	identified by field 'idmr'.										*
     *																	*
     *	Parameter:														*
     *	    $newval		the new relationship code.						*
     *  		Child::RELATE_NULL										*
     *  		Child::RELATE_ADOPTED									*
     *  		Child::RELATE_BIOLOGICAL								*
     *  		Child::RELATE_CHALLENGED								*
     *  		Child::RELATE_DISPROVED									*
     *  		Child::RELATE_FOSTER									*
     *  		Child::RELATE_GUARDIAN									*
     *  		Child::RELATE_SEALING									*
     *  		Child::RELATE_STEP										*
     *  		Child::RELATE_UNKNOWN									*
     *  		Child::RELATE_PRIVATE									*
     *  		Child::RELATE_FAMILYMEMBER								*
     *  		Child::RELATE_ILLEGITIMATE								*
     *																	*
     *  Returns:														*
     *	   old relationship code value								    *
     *																	*
     *	Throws:															*
     *	    exception if new value is not supported						*
     ********************************************************************/
    function setCPRelMom($newval)
    {
		if (array_key_exists($newval, self::$intType))
		    return parent::set('idcpmom',	$newval);
		else
		    throw new Exception("Child::setCPRelDad: " .
						"called with value='$newval'");
    }		// function Child::setCPRelMom

    /********************************************************************
     *  function Child::getCPRelText									*
     *																	*
     *	Get the textual interpretation of a child to parent				*
     *	relationship code.												*
     *																	*
     *	Parameter:														*
     *	    $idcp	numeric code for child to parent relationship		*
     *																	*
     *  Returns:														*
     *	    String interpretation of supplied code						*
     ********************************************************************/
    static function getCPRelText($idcp)
    {
		if (array_key_exists($idcp, self::$intType))
		{		// defined value
		    return self::$intType[$idcp];
		}		// defined value
		else
		{		// key not found
		    return 'Unknown CPRel: ' . $idcp;
		}		// key not found
    }		// function Child::getCPRelText

    /********************************************************************
     *  function Child::getCPRelDadText									*
     *																	*
     *	Get the textual description of the relationship between the		*
     *	individual identified by field 'idir' and the husband in the	*
     *	family identified by field 'idmr'.								*
     *																	*
     *  Returns:														*
     *	    String interpretation of relationship code					*
     ********************************************************************/
    function getCPRelDadText()
    {
		if ($this->row['cpdadprivate'])
		    return '';
		return self::getCPRelText($this->row['idcpdad']);
    }		// function Child::getCPRelDadText

    /********************************************************************
     *  function Child::getCPRelMomText									*
     *																	*
     *	Get the textual description of the relationship between the		*
     *	individual identified by field 'idir' and the wife in the		*
     *	family identified by field 'idmr'.								*
     *																	*
     *  Returns:														*
     *	    String interpretation of relationship code					*
     ********************************************************************/
    function getCPRelMomText()
    {
		if ($this->row['cpmomprivate'])
		    return '';
		return self::getCPRelText($this->row['idcpmom']);
    }		// function Child::getCPRelMomText

    /********************************************************************
     *  function Child::set												*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $newvalue		value to set it to							*
     *																	*
     *	Throws:															*
     *	    exception if target field is not defined in the record or	*
     *	    the new value is not supported by the target field			*
     *																	*
     *  Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $newvalue)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'idcs':
		    {		// child status
				return $this->setStatus($newvalue);
		    }		// child status

		    case 'idcpdad':
		    {		// child to parent relationship for father
				return $this->setCPRelDad($newvalue);
		    }		// child to parent relationship for father

		    case 'idcpmom':
		    {		// child to parent relationship for mother
				return $this->setCPRelMom($newvalue);
		    }		// child to parent relationship for mother

		    case 'parseald':
		    {		// legacy internal date format
				// validate and interpret new value
				if ($newvalue instanceof LegacyDate)
				{	// parameter is an instance of LegacyDate
				    $date	= $newvalue;
				}	// parameter is an instance of LegacyDate
				else
				if (is_string($newvalue) || is_int($newvalue))
				{	// convert the value to instance of LegacyDate
				    $date	= new LegacyDate(' ' . $newvalue);
				}	// convert the value to instance of LegacyDate
				else
				{	// unexpected type
				    throw new Exception("Child::set: " .
						"invalid value type " .  gettype($newvalue) .
						" for field '$field'");
				}	// unexpected

				$sdname		= substr($fieldlc, 0, strlen($fieldLc)-1) .'sd';
				parent::set($sdname, $date->getSortDate());
				return parent::set($fieldLc, $date->getDate());
		    }		// sealed to parents date

		    case 'cpdadprivate':
		    case 'cpmomprivate':
		    case 'ldsp':
		    case 'templetag':
		    {		// true/false flags
				if ($newvalue == 0 || $newvalue == 1)
				{
				    return parent::set($fieldLc, $newvalue);
				}		// valid valud
				else
				{		// invalid value
				    throw new Exception("Child::set: " .
						"invalid value '$newvalue'" .
						" for field name='$field'");
				}		// invalid value
		    }		// true/false flags

		    default:
		    {		// no validation implemented yet
				return parent::set($field, $newvalue);
		    }		// no validation implemented yet
		}		// switch on field name
    }		// function Child::set

    /********************************************************************
     *  function Child::save											*
     *																	*
     *  Save changes made to the Child record into the					*
     *  database.														*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					if a string use it as tag name					*
     ********************************************************************/
    function save($xml)
    {
		// only save records represented by records in tblER
		$idir	            = $this->row['idir'];
		if ($idir > 0)
		{		        // ignore incomplete records
		    // invoke parent's implementation
		    parent::save($xml);
		}		        // ignore incomplete records
    }		// function Child::save

    /********************************************************************
     *  function Child::getName											*
     *																	*
     *	Return the primary name for this individual including all parts *
     *	in a standard form plus the names of the parents.				*
     *																	*
     *	Input:															*
     *	    $options		combination of option flags					*
     *			NAME_INCLUDE_DATES		include birth and death dates	*
     *			NAME_SURNAME_FIRST		put surname first				*
     *			NAME_INCLUDE_MDATE		include marriage date			*
     *																	*
     *  Returns:														*
     *	    string containing the primary name of the individual		*
     *	    and the names of the parents								*
     ********************************************************************/
    function getName($options	= 0) 
    {
		$indiv	= new Person(array('idir' => $this->row['idir']));
		$ioptions	= $options & 
						  (self::NAME_INCLUDE_DATES |
						   self::NAME_SURNAME_FIRST);
		$retval	= $indiv->getName($options);

        if (!class_exists(__NAMESPACE__ . '\\Family'))
			include __NAMESPACE__ . '/Family' . ".inc";

		try {
		    $family	    = new Family(array('idmr' => $this->row['idmr']));
		    $foptions	= ($options & self::NAME_INCLUDE_MDATE) != 0;
		    $retval	    .= " child of " . $family->getName($foptions);
		} catch(Exception $e) {
		    $retval	    .= " Invalid IDMR=" . $this->row['idmr'];
		}
		return $retval;
    }		// function Child::getName

    /********************************************************************
     *  function Child::clearEvent										*
     *																	*
     *  This function is used by Event to remove information			*
     *	is being moved to Event.										*
     *																	*
     *	Input:															*
     *	   $eventid		string name of event							*
     ********************************************************************/
    function clearEvent($eventid)
    {
		switch(strtolower($eventid))
		{			        // validate event id
		    case 'parseal':
		    {			    // events using IDTR
				parent::set($eventid . 'd', '');
				parent::set($eventid . 'sd', -99999999);
				parent::set('idtr' . $eventid, 1);
				break;
		    }			    // events using IDTR
		}			        // validate event id
    }		// function Child::clearEvent

    /********************************************************************
     *  function Child::toXml											*
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
     *  Each field whose value is an instance of Temple appears			*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idlr" prefix removed and a "loc" suffix added.	*
     *	For example the element <idtrparseal> contains the original		*
     *	numeric code while the element <parsealloc> contains the		*
     *	name of the temple.												*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'child'			*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *  Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/
    function toXml($top		= 'child',
				   $print	= true,
				   $options	= 0)
    {
		$idcr		= $this->row['idcr'];
		$retval		= "<$top idcr='$idcr'>\n";
		$idir		= $this->row['idir'];
		$isOwner	= RecOwner::chkOwner($idir, 'tblIR');

		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'idmr':
				{
				    $retval	.= "    <idmr>" . xmlentities($value) . "</idmr>\n";
				    try {
						$family	= new Family(array('idmr' => $value));
						$retval	.= "    <family>" . $family->getName() .
								"    </family>\n";
				    } catch (Exception $e) {
						$retval	.= "    <msg>" . $e->getMessage() . "</msg>\n";
				    }			// catch
				    break;
				}			// IDMR

				case 'idir':
				{
				    $retval	.= "    <idir>" . xmlentities($value) . "</idir>\n";
				    $indiv	= new Person(array('idir' => $value));
				    $retval	.= "    <indiv>" .
						    $indiv->getName(Person::NAME_INCLUDE_DATES) .
								"    </indiv>\n";
				    break;
				}			// IDIR

				case 'parseald':
				{			// legacy internal date format
				    $date	= new LegacyDate($value);
				    if ($isOwner)
				    {			// can see private data
						$retval	.= "    <{$fld}c>" . xmlentities($value) .
								"</{$fld}c>\n";
						$value	= $date->toString();
						$retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    }			// can see private data
				    else
				    {			// cannot see private data
						$value	= $date->toString(1933);
						$retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    }			// can see private data
				    break;
				}			// legacy internal date format

				case 'parsealsd':
				{			// legacy internal sort date
				    if ($isOwner)
				    {			// can see private data
						$retval	.= "    <$fld>" . $value . "</$fld>\n";
				    }			// can see private data
				    break;
				}			// legacy internal sort date

				case 'idtrparseal':
				{			// temple code
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    try {
				    $loc	= new Temple(array('idtr' => $value));
				    $value	= $loc->getName();
				    $locfld	= substr($fld,4) . 'loc';
				    $retval	.= "    <$locfld>" . xmlentities($value) .
								"</$locfld>\n";
				    } catch (Exception $e) {
						$retval	.= "    <msg>" . $e->getMessage() . "</msg>\n";
				    }		// catch
				    break;
				}			// temple code

				case 'ldsp':
				case 'templetag':
				case 'cpdadprivate':
				case 'cpmomprivate':
				case 'prefchild':
				{			// yes/no flags
				    if ($value > 0)
						$retval	.= "    <$fld>Y</$fld>\n";
				    else
						$retval	.= "    <$fld>N</$fld>\n";
				    break;
				}			// yes/no flags

				default:
				{		// fields passed back with no interpretation
				    $retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

		$retval	.= "</$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Child::toXml

    /********************************************************************
     *  function Child::toJson											*
     *																	*
     *  Emit an JSON representation of the record.						*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two fields.  The internal encoded string value appears in an	*
     *	field with 'c' appended to the field name, while the field		*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *  Each field whose value is an instance of Temple appears			*
     *	as two fields.  The internal numeric key value appears in		*
     *	an field with the original field name.  The external textual	*
     *	value appears in an field whose name is the original field		*
     *	name with the "idlr" prefix removed and a "loc" suffix added.	*
     *	For example the field "idtrparseal" contains the original		*
     *	numeric code while the field "parsealloc" contains the			*
     *	name of the temple.												*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *  Returns:														*
     *	    JSON representation of the object							*
     ********************************************************************/
    function toJson($print	    = true,
				    $options	= 0)
    {
		$idcr		= $this->row['idcr'];
		$idir		= $this->row['idir'];
		$retval		= '';
		$comma		= "{\n";
		$isOwner	= RecOwner::chkOwner($idir, 'tblIR');
		$currYear	= intval(date('Y'));
		$privlim	= $currYear - 72;

		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'idmr':
				{
				    $retval		.= $comma . "\t\"idmr\":\t" . 
								   json_encode($value);
				    $comma		= ",\n";
				    try {
						$family		= new Family(array('idmr' => $value));
						$retval		.= $comma . "\t\"family\":\t" . 
									   json_encode($family->getName());
				    } catch (Exception $e) {
						$retval		.= $comma . "\t\"familymsg\":\t" . 
									   json_encode($e->getMessage());
				    }			// catch
				    break;
				}			// IDMR

				case 'idir':
				{
				    $retval	.= $comma . "\t\"idir\":\t" . 
								   json_encode($value);
				    $comma	= ",\n";
				    $indiv	= new Person(array('idir' => $value));
				    $retval	.= $comma . "\t\"child\":\t" . 
		    json_encode($indiv->getName(Person::NAME_INCLUDE_DATES));
				    break;
				}			// IDIR

				case 'parseald':
				{			// legacy internal date format
				    $date	= new LegacyDate($value);
				    if ($isOwner)
				    {			// can see private data
						$retval	.= $comma . "\t\"parsealdc\":\t" . 
								   json_encode($value);
						$comma	= ",\n";
						$value	= $date->toString();
						$retval	.= $comma . "\t\"parseald\":\t" . 
								   json_encode($value);
				    }			// can see private data
				    else
				    {			// cannot see private data
						$value	= $date->toString($privlim);
						$retval	.= $comma . "\t\"parseald\":\t" . 
								   json_encode($value);
						$comma	= ",\n";
				    }			// can see private data
				    break;
				}			// legacy internal date format

				case 'parsealsd':
				{			// legacy internal sort date
				    if ($isOwner)
				    {			// can see private data
						$retval	.= $comma . "\t\"parsealsd\":\t" . 
								   json_encode($value);
						$comma	= ",\n";
				    }			// can see private data
				    break;
				}			// legacy internal sort date

				case 'idtrparseal':
				{			// temple code
				    $retval	.= $comma . "\t\"idtrparseal\":\t" . 
								   json_encode($value);
				    $comma	= ",\n";
				    $loc	= new Temple(array('idtr' => $value));
				    $value	= $loc->getName();
				    $retval	.= $comma . "\t\"parsealloc\":\t" . 
								   json_encode($value);
				    break;
				}			// temple code

				case 'ldsp':
				case 'templetag':
				case 'cpdadprivate':
				case 'cpmomprivate':
				case 'prefchild':
				{			// yes/no flags
				    $retval	.= $comma . "\t\"$fld\":\t"; 
				    $comma	= ",\n";
				    if ($value > 0)
						$retval	.= '"Y"';
				    else
						$retval	.= '"N"';
				    break;
				}			// yes/no flags

				default:
				{		// fields passed back with no interpretation
				    $retval	.= $comma . "\t\"$fld\":\t" . 
								   json_encode($value);
				    $comma	= ",\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Child::toJson

    /********************************************************************
     *  function Child::getParSealEvent									*
     *																	*
     *  Get the LDS sealed to parents event for the child.				*
     *																	*
     *  Input:															*
     *	    $create		if true return instance of Event even			*
     *					if there is no existing event					*
     *																	*
     *  Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getParSealEvent($create = false)
    {
		$event		= $this->sealEvent;
		if (is_null($event))
		{		            // not initialized
		    $trow			        = array();
		    $trow['idir']		    = $this;
		    $trow['idtype']	        = Event::IDTYPE_CHILD;
		    $trow['idet']		    = Event::ET_LDS_PAR_SEALED;
			$trow['eventd']	        = $this->get('parseald');
			$trow['eventsd']	    = $this->get('parsealsd');
			$trow['idlrevent']	    = $this->get('idtrparseal');
			$trow['kind']		    = 1;
			$trow['desc']		    = $this->get('parsealnote');
			$trow['description']	= '';
			$trow['ldstempleready'] = $this->get('ldsp');
		    $trow['preferred']		= 1;
		    $event			        = new Event($trow);
		    if ($create ||
				$trow['eventsd'] != -99999999 ||
				$trow['idlrevent'] > 1 ||
				$trow['desc'] != '')
		    {		        // LDS endow event present
				$this->sealEvent	= $event;
		    }		        // LDS endow event present
		    else
				$event			    =  null;
		}		            // not initialized
		return $event;
    }		// function Child::getParSealEvent

}		// class Child 

