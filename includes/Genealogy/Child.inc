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
 *				    --> tblMR.IDMR, ID to parents Family record		    *
 *		    IDIR					  INTEGER(10)						*
 *				    --> tblIR.IDIR, ID to child's Person record		    *
 *		    Order					  SMALLINT(5)						*
 *				    used as sort order.									*
 *		    PrefChild				  TINYINT(1)						*
 *				    1=preferred, 0=not									*
 *		    IDCS					  INTEGER(10)						*
 *				    --> tblCS.IDCS, ID to Child Status record			*
 *						see Child::STATUS_...							*
 *		    IDCPDad					  INTEGER(10)						*
 *				    --> tblCP.IDCP, ID to Child/Parent Rel'ship record	*
 *						see Child::RELATE_...							*
 *		    IDCPMom					  INTEGER(10)						*
 *				    --> tblCP.IDCP, ID to Child/Parent Rel'ship record	*
 *						see Child::RELATE_...							*
 *		    CPDadPrivate			  TINYINT(1)						*
 *				    0=No, 1=Yes											*
 *		    CPMomPrivate			  TINYINT(1)						*
 *				    0=No, 1=Yes											*
 *																		*
 *		The following fields were formerly used to hold information		*
 *		about the LDS Sealed to Parents event but they are now          *
 *		recorded in an instance of Event.                               *
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
 *		2019/07/18      use Person::getPerson                           *
 *		                add line numbers to warning messages            *
 *		2019/08/05      permit creating an incomplete instance with     *
 *		                IDIR == 0                                       *
 *      2019/08/11      Child methods getStatus, getStatusText,         *
 *                      getCPRelDad, and getCPRelMom obsoleted          *
 *      2019/11/23      support passing objects to idmr and idir in     *
 *                      constructor                                     *
 *                      make factory methods getFamily and getPerson    *
 *                      more efficient                                  *
 *                      move sealed to parents event in XML and JSON    *
 *                      output                                          *
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
    const	NAME_INCLUDE_LOCS	= 4;

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
				        'childorder'		=> 'order');

    /********************************************************************
     *	Child::family					    							*
     *																	*
     *	Reference to associated instance of class Family                *
     ********************************************************************/
    protected           $family             = null;

    /********************************************************************
     *	Child::person					    							*
     *																	*
     *	Reference to associated instance of class Person                *
     ********************************************************************/
    protected           $person             = null;

    /********************************************************************
     *	Child::STATUS_...                             					*
     *																	*
     *	Interpretation of values of field 'IDCS'                        *
     *  Symbolic constants for calling Child::setStatus                 *
     ********************************************************************/
    const	STATUS_NULL		    = 1;
    const	STATUS_NONE		    = 2;
    const	STATUS_STILLBORN	= 3;
    const	STATUS_TWIN		    = 4;
    const	STATUS_ILLEGITIMATE	= 5;

    /********************************************************************
     *  Child::RELATE_...												*
     *																	*
     *  Interpretation of values of Child to Parent Relationships.		*
     *  Symbolic constants for calling Child::setCpRelxxx               *
     ********************************************************************/
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
     *	    $id		  * a full database record presented as an			*
     *					associative array of fields, or					*
     *				  * an associative array of key values to			*
     *					identify the record to use.  This can be either	*
     *					array('idcr' => $idcr) to identify an existing  *
     *					record, or						                *
     *					array('idmr' => $idmr, 'idir' => $idir)			*
     *					to identify the record for a particular Person	*
     *					as a child of a specific family to locate or    *
     *					add a specific Person as a child of a           *
     *					specific Family.				                *
     *																	*
     *  Returns object containing child record.							*
     *																	*
     *	Adds error message to $this->msg if new value is not supported	*
     ********************************************************************/
    function __construct($id)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,
								  PDO::FETCH_ASSOC);
		$dbrow		        = null;
        $needInsert	        = false;
        $parmStr            = 'null';

		// action depends upon type of parameter
        if (is_int($id))
        {
            $parms	        = array('idcr' => $id);
            $parmStr        = $id;
        }
		else
		if (is_string($id) && ctype_digit($id))
        {
		    $parms	        = array('idcr' => intval($id));
            $parmStr        = "'$id'";
        }
		else
		if (is_string($id) && strtolower($id) == 'new')
        {
		    $parms	        = array('idcr' => 0);
            $parmStr        = "'$id'";
        }
		else
        if (is_array($id))
        {
		    $parms	        = $id;
            $parmStr        = print_r($id, true);
        }
        else
        {
		    $this->msg      .= "Child::__construct: " .
                                "called with " . gettype($id) . ' ' .
                                print_r($id, true) . '. ';
            $parms          = array();
            $parmStr        = print_r($id, true);
        }

		// $parms is now an associative array
		$idcr								= null;
		$idmr								= null;
		$idir								= null;
		$where								= '';
		$sqlParms							= array();
        $and								= 'WHERE ';
        $validFieldNames    				= true;

		foreach($parms as $fldname => $value)
		{			                    // loop through all parameters
		    $fieldLc	    				= strtolower($fldname);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc					= self::$translate[$fieldLc];
		    switch($fieldLc)
		    {			                // act on specific field names
				case 'idcr':
				{		                // IDCR identify specific instance
				    if (is_string($value) && ctype_digit($value))
                        $value			    = (int)$value;

				    if (is_int($value) && $value > 0)
				    {
						$idcr			    = $value;
						$where		        .= $and . 'IDCR=:idcr';
						$sqlParms['idcr']	= $idcr;
						$and		        = ' AND ';
				    }
				    else
                        $warn	.= "<p>Child::__construct: " . __LINE__ .
                " Field Name 'IDCR' value '$value' unsupported.</p>\n";
				    break;
				}		                // IDCR identify specific instance

				case 'idmr':
                {		                // IDMR identify parents
                    if ($value instanceof Family)
                    {
                        $this->family       = $value;
                        $value              = $this->family['idmr'];
                    }
                    else
				    if (is_string($value) && ctype_digit($value))
						$value			    = (int)$value;

				    if (is_int($value) && $value > 0)
				    {
						$idmr			    = $value;
						$where			    .= $and . 'IDMR=:idmr';
						$sqlParms['idmr']	= $idmr;
						$and			    = ' AND ';
				    }
				    else
                        $warn	.= "<p>Child::__construct: " . __LINE__ .
                " Field Name 'IDMR' value '$value' unsupported.</p>\n";
				    break;
				}		                // IDMR identify parents

				case 'idir':
				{		                // IDIR identify child
                    if ($value instanceof Person)
                    {
                        $this->person       = $value;
                        $value              = $this->person['idir'];
                    }
                    else
				    if (is_string($value) && ctype_digit($value))
                        $value			    = (int)$value;

				    if (is_int($value) && $value > 0)
				    {
						$idir			    = $value;
						$where			    .= $and . 'IDIR=:idir';
						$sqlParms['idir']	= $idir;
						$and			    = ' AND ';
				    }
                    else
                    {
                        $warn	.= "<p>Child::__construct: " . __LINE__ .
                            " Field Name 'IDIR' value '$value' unsupported. </p>\n";
                        $warn	.= "<p>Child::__construct: " . __LINE__ .
                                    " parms=$parmStr</p>\n";
			            $backtrace          = debug_backtrace();
			            foreach($backtrace as $call)
			            {
			                if (array_key_exists('class', $call) &&
			                    strlen($call['class']) > 0)
			                    $classPref      = $call['class'] . '::';
			                else
			                    $classPref      = '';
			                $warn   .= "<p>Child::__construct: " . __LINE__ .
			                    " function=$classPref" . $call['function'] .
			                    " called from line " . $call['line'] . 
			                    " of " . $call['file'] . "</p>\n";
			            }
			        }
				    break;
				}		                // IDIR identify child

				case 'order':
				case 'prefchild':
				case 'idcs':
				case 'idcpdad':
				case 'idcpmom':
				case 'cpdadprivate':
				case 'cpmomprivate':
				{		                // other valid field names
				    break;
				}		                // other valid field names

				case 'parsealsd':
				case 'parseald':
				case 'idtrparseal':
				case 'ldsp':
				case 'templetag':
				case 'parsealnote':
				{		                // obsolete field names
                    $validFieldNames    = false;
				    break;
				}		                // obsolete field names

				default:
				{		                // unrecognized field names
                    $validFieldNames    = false;
				    $warn	        .= "<p>Child::__construct: " . __LINE__ . 
								" Field Name '$fldname' not recognized.</p>";
				    break;
				}		                // unrecognized field names
		    }			                // act on specific field names
		}			                    // loop through all parameters

        $count		= count($parms);
        if ($count == 10 && $validFieldNames)
        {
			$parms['parsealsd']			= self::$initRow['parsealsd'];
			$parms['parseald']			= self::$initRow['parseald'];
			$parms['idtrparseal']		= self::$initRow['idtrparseal'];
			$parms['ldsp']		    	= self::$initRow['ldsp'];
			$parms['templetag']			= self::$initRow['templetag'];
            $parms['parsealnote']		= self::$initRow['parsealnote'];
            $count                  	= count($parms);
        }

		if ($count >= count(self::$initRow))
		{		                        // complete row from database
		    $dbrow		                = $parms;
		    $needInsert		            = $dbrow['idcr'] == 0;
		}		                        // complete row from database
		else
		{		                        // search parameters
		    // act on specific parameter sets
		    if (strlen($where) > 0)
		    {			                // existing record
				// construct the query of the child table
				$query		            = 'SELECT * FROM tblCR ' . $where;
				
				// query the database
				$stmt		            = $connection->prepare($query);
				$queryText	            = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
				if ($stmt->execute($sqlParms))
				{		                // successful query
				    $result		        = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if ($debug)
						$warn		    .= '<p>Child:__construct: ' . __LINE__ .
									   "query='$queryText'</p>\n";
		
				    $numMatches	        = count($result);
				    if ($numMatches == 1)
				    {		            // found child record
						$dbrow		    = $result[0];
						$needInsert	    = false;
				    }		            // found child record
				    else
				    if ($numMatches == 0)
				    {		            // record not found
					    $dbrow		            = self::$initRow;
                        $needInsert		        = true;
                        if (!is_null($idmr))
					        $dbrow['idmr']	    = $idmr;
                        if (!is_null($idir))
					        $dbrow['idir']	    = $idir;
	
					    if ($idmr > 0)
					    {		        // existing family
							// order new child after all existing children
                            $queryc		        =
            "SELECT COALESCE(MAX(`Order`)+1,0) FROM tblCR WHERE IDMR=:idmr";
							$sqlParms	        = array('idmr' => $idmr);

							// query the database
							$stmt	            = $connection->prepare($queryc);
							$queryText	        = debugPrepQuery($queryc,
							            	        			 $sqlParms);
                            $this->lastSqlCmd   = $queryText;
							if ($stmt->execute($sqlParms))
							{		    // successful query
							    $row	        = $stmt->fetch(PDO::FETCH_NUM);
							    $newOrder	    = $row[0];
							    if ($debug)
								    $warn	    .= "<p>Child::__construct: " .
					__LINE__ . " '$queryText' returns $newOrder</p>";
							}		    // successful query
							else
							{		    // error performing query
							    $this->msg      .= "Child::__construct: " .
									__LINE__ . " '$queryText', " .
								    print_r($stmt->errorInfo(), true) . '. ';
							}		    // error performing query
					    }		        // existing family
					    else
							$newOrder	        = 0;
					    $dbrow['order']	        = $newOrder;
				    }		            // record not found
				    else
				    {		            // too many records found
						$this->msg      .= "Child::__construct: " .
									        "$numMatches found $queryText";
				    }		            // too many records found
				}		                // successful query
				else
				{		                // error performing query
				    $this->msg          .= "Child::__construct: " .
							        		"'$queryText' " .
							        		print_r($stmt->errorInfo(),true);
				}		                // error performing query
		    }			                // existing record
		    else
		    if ($idcr === 0)
		    {			                // create a new record
				// set defaults
				$dbrow		            = self::$initRow;
				$needInsert	            = true;
		    }			                // create new record
		    else
		    {			                // invalid record
				$this->msg              = "Child::__construct: " .
        								    "Invalid array parameter :" .
		        						    print_r($parms, true) . '. ';
		    }		                	// invalid record
		}		                    	// search parameters

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
     *	then this method adds an error message and returns zero.        *
     *																	*
     *  Returns:														*
     *	    unique numeric index of this instance of Child				*
     ********************************************************************/
    function getIdcr()
    {
        global  $msg;

		if ($this->needInsert)
		    $msg        .= "Child::getIdcr: " .
								"record does not exist in database, "; 
		return $this->row['idcr'];
    }		// function Child::getIdcr

    /********************************************************************
     *  function Child::getFamily										*
     *																	*
     *	Retrieve the instance of Family representing the parents        *
     *	documented by this instance of Child.							*
     *																	*
     *	Input:															*
     *	    an instance of Family										*
     ********************************************************************/
    function getFamily() 
    {
        if (is_null($this->family)) 
            $this->family   = new Family(array('idmr' => $this->row['idmr']));
        return  $this->family;
    }		// function Child::getFamily

    /********************************************************************
     *  function Child::getPerson										*
     *																	*
     *	Retrieve the instance of Person									*
     *	representing the individual who is a child.						*
     *																	*
     *	Input:															*
     *	    an instance of Person										*
     ********************************************************************/
    function getPerson() 
    {
        if (!class_exists(__NAMESPACE__ . '\\Person'))
			include __NAMESPACE__ . '/Person' . ".inc";

        if (is_null($this->person)) 
		    $this->person   = Person::getPerson($this->row['idir']);
        return  $this->person;
    }		// function Child::getPerson

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
     *	Adds error message to $msg if new value is not supported		*
     ********************************************************************/
    function setStatus($newval)
    {
        global  $msg;

        if ($newval >= Child::STATUS_NULL && 
            $newval <= CHILD::STATUS_ILLEGITIMATE)
		    return parent::set('idcs',	$newval);
		else
            $msg        .= "Child::setStatus: " .
						    "called with value='$newval'. ";
    }		// function Child::setStatus

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
     *	Adds error message to $msg if new value is not supported		*
     ********************************************************************/
    function setCPRelDad($newval)
    {
        global  $msg;

        if ($newval >= Child::RELATE_NULL && 
            $newval <= Child::RELATE_ILLEGITIMATE)
		    return parent::set('idcpdad',	$newval);
		else
		    $msg        .= "Child::setCPRelDad: " .
						    "called with value='$newval'. ";
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
     *	Adds error message to $msg if new value is not supported		*
     ********************************************************************/
    function setCPRelMom($newval)
    {
        global  $msg;

        if ($newval >= Child::RELATE_NULL && 
            $newval <= Child::RELATE_ILLEGITIMATE)
		    return parent::set('idcpmom',	$newval);
		else
		    $msg        .= "Child::setCPRelMom: " .
						    "called with value='$newval'. ";
    }		// function Child::setCPRelMom

    /********************************************************************
     *  function Child::set												*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		    name of field to change						*
     *	    $newvalue		value to set it to							*
     *																	*
     *	Adds error message to $msg if new value is not supported		*
     *																	*
     *  Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $newvalue)
    {
        global  $msg;

		$fieldLc	        = strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	    = self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'idcs':
		    {		                // child status
				return $this->setStatus($newvalue);
		    }		                // child status

		    case 'idcpdad':
		    {		                // child to parent relationship for father
				return $this->setCPRelDad($newvalue);
		    }		                // child to parent relationship for father

		    case 'idcpmom':
		    {		                // child to parent relationship for mother
				return $this->setCPRelMom($newvalue);
		    }		                // child to parent relationship for mother

		    case 'parseald':
		    {		                // legacy internal date format
				// validate and interpret new value
				if ($newvalue instanceof LegacyDate)
				{	                // parameter is an instance of LegacyDate
				    $date	            = $newvalue;
				}	                // parameter is an instance of LegacyDate
				else
				if (is_string($newvalue) || is_int($newvalue))
				{	                // convert the value to LegacyDate
				    $date	            = new LegacyDate(' ' . $newvalue);
				}	                // convert the value to LegacyDate
				else
				{	                // unexpected type
                    $msg        .= "Child::set: " . "invalid value type " . 
                        gettype($newvalue) . ' ' . print_r($newvalue, true) .
                        " for field '$field'. ";
				}	                // unexpected

				$sdname		= substr($fieldlc, 0, strlen($fieldLc)-1) .'sd';
				parent::set($sdname, $date->getSortDate());
				return parent::set($fieldLc, $date->getDate());
		    }		                // sealed to parents date

		    case 'cpdadprivate':
		    case 'cpmomprivate':
		    case 'ldsp':
		    case 'templetag':
		    {		                // true/false flags
				if ($newvalue == 0 || $newvalue == 1)
				{
				    return parent::set($fieldLc, $newvalue);
				}		            // valid valud
				else
				{		            // invalid value
				    $msg        .= "Child::set: " .
						            "invalid value '$newvalue'" .
						            " for field name='$field'. ";
				}		            // invalid value
		    }		                // true/false flags

		    default:
		    {		                // no validation implemented yet
				return parent::set($field, $newvalue);
		    }		                // no validation implemented yet
		}		                    // switch on field name
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
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method updated the database record		*
     *	    0			if the database did not need to be updated		*
     *																	*
     *  Throws:															*
     *	    Exception if user is not authorized to update the database	*
     ********************************************************************/
    function save($xml)
    {
		// only save records representing an existing instance of Person
		$idir	            = $this->row['idir'];
		if ($idir > 0)
		{		        // ignore incomplete records
		    // invoke parent's implementation
		    return parent::save($xml);
        }		        // ignore incomplete records
        return false;
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
     *			NAME_INCLUDE_LOCS		include birth and death locs	*
     *			            or internationalization translate table     *
     *																	*
     *  Returns:														*
     *	    string containing the primary name of the individual		*
     *	    and the names of the parents								*
     ********************************************************************/
    function getName($options	= 0) 
    {
		global	$debug;
		global	$warn;

		if (is_array($options) || $options instanceof TemplateTag)
		    $tranTab			= $options;
		else
		    $tranTab			= null;

		$person	                = $this->getPerson();
		$retval	                = $person->getName($options);

        $family	                = $this->getFamily();
        if ($family->isExisting())
        {
            if ($tranTab)
            {
                $retval         .= ' ' . $tranTab['child of'] . ' ';
     	        $nameParms      = array('includeDate'		=> true,
     	                                'connector'		    => $tranTab['and'],
     	                                'dateprefix'		=> $tranTab['on'],
                                        'locprefix'		    => $tranTab['at']);
            }
            else
            {
                $retval         .= ' child of ';
                $nameParms      = array('includeDate'       =>
                                        $options && Child::NAME_INCLUDE_DATES);
            }
		    $retval	            .= $family->getName($nameParms);
        }
        else 
		    $retval	            .= " Invalid IDMR=" . $this->row['idmr'];
		return $retval;
    }		// function Child::getName

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
            $event      = new Event(array('idtype'  => Event::IDTYPE_CHILD,
                                          'idet'    => Event::ET_LDS_PAR_SEALED,
                                          'idir'    => $this->row['idir']));
            if (!$event->isExisting())
            {               // does not exist in database
				$event['eventd']	        = $this->get('parseald');
				$event['eventsd']	        = $this->get('parsealsd');
				$event['idlrevent']	        = $this->get('idtrparseal');
				$event['kind']		        = 1;
				$event['desc']		        = $this->get('parsealnote');
				$event['description']	    = '';
				$event['ldstempleready']    = $this->get('ldsp');
		    	$event['preferred']		    = 1;
		        if ($create ||
					$event['eventsd'] != -99999999 ||
					$event['idlrevent'] > 1 ||
					$event['desc'] != '')
		        {		    // LDS seal to parents event present
				    $this->sealEvent	    = $event;
                }		    // LDS seal to parents event present
		        else
				    $event			        = null;
            }               // does not exist in database
		}		            // not initialized
		return $event;
    }		// function Child::getParSealEvent

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
				parent::set('parseald',     '');
				parent::set('parsealsd',    -99999999);
				parent::set('idtrparseal',  1);
			    parent::set('parsealnote',  '');
			    parent::set('ldsp',         0);
				break;
		    }			    // events using IDTR
		}			        // validate event id
    }		// function Child::clearEvent

    /********************************************************************
     *	function Child::getEvents										*
     *																	*
     *  Get the events for the instance of Child.  This array			*
     *	includes all events.  This is an associative array of instances *
     *	of Event in which the key is the IDER value for Events          *
     *	represented as records in table tblER (Events) and a            *
     *	descriptive string for events represented as fields	            *
     *	in the record in tblCR.  This method exists for symmetry with   *
     *	Person::getEvents and Family::getEvents and in case at some     *
     *	future time more than one event is associated with a Child.     *
     *																	*
     *	There is at present a maximum of one event.         			*
     *																	*
     *	Returns:														*
     *	    An associative array of instances of Event indexed			*
     *	    by IDER (or 'parseal' for event in tblCR).				    *
     *																	*
     *	    Use foreach($child->getEvents() as $ider => $event)			*
     ********************************************************************/
    function getEvents()
    {
        $event          = $this->getParSealEvent(false);
        if ($event)
        {
            if ($event['ider'] > 0)
                return array($event['ider']     => $event);
            else
                return array('parseal'          => $event);
        }
        return array();
    }       // function Child::getEvents

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
                    $retval	        .= "    <idmr>$value\n    </idmr>\n";
					$family	        = $this->getFamily();
					$retval	        .= "    <family>" . $family->getName() .
						            		"    </family>\n";
				    break;
				}			    // IDMR

				case 'idir':
				{
                    $retval	        .= "    <idir>$value\n    </idir>\n";
				    $person	        = $this->getPerson();
				    $retval	        .= "    <child>" .
						    $person->getName(Person::NAME_INCLUDE_DATES) .
								        "\n    </child>\n";
				    break;
				}			    // IDIR

				case 'parseald':
				case 'parsealsd':
				case 'idtrparseal':
				case 'ldsp':
				case 'templetag':
				case 'parsealnote':
				{			    // obsolete
				    break;
				}			    // obsolete


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

        // the sealed to parents event, if present, is displayed here
        $tevents		    = $this->getEvents();
        foreach($tevents as $ider => $tevent)
        {
            $retval	        .= $tevent->toXml('event', false);
        }		// loop through events

        // if requested include parent information
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
					$family		= $this->getFamily();
					$retval		.= $comma . "\t\"family\":\t" . 
									   json_encode(html_entity_decode($family->getName()));
				    break;
				}			// IDMR

				case 'idir':
				{
				    $retval	    .= $comma . "\t\"idir\":\t" . 
								   json_encode($value);
				    $comma	    = ",\n";
				    $person	    = $this->getPerson();
				    $retval	    .= $comma . "\t\"child\":\t" . 
		            json_encode(html_entity_decode($person->getName(Person::NAME_INCLUDE_DATES)));
				    break;
				}			// IDIR

				case 'parseald':
				case 'parsealsd':
				case 'idtrparseal':
				case 'ldsp':
                case 'templetag':
				case 'parsealnote':
                    break;

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

        // the sealed to parents event, if present, is displayed here
        $tevents		    = $this->getEvents();
        foreach($tevents as $ider => $tevent)
        {
            $retval	        .= $tevent->toJsob(false);
        }		// loop through events

		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Child::toJson

}		// class Child 

