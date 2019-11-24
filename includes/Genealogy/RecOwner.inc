<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  RecOwner.inc														*
 *																		*
 *  Definition of a class representing a record in the RecOwners table	*
 *  that records that a particular user has authority to update a		*
 *  specific record														*
 *																		*
 *  History:															*
 *		2010/10/21		created											*
 *		2010/10/23		connection made global							*
 *		2010/11/09		add method addOwner								*
 *		2011/02/02		escape special characters in debugging output	*
 *						throw exceptions for SQL failures instead of	*
 *						dieing											*
 *		2011/08/24		return true or false from addOwner method as	*
 *						already documented								*
 *		2011/12/30		allow master user to access any record			*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/01/22		require current user to have edit authorization	*
 *						to access record								*
 *		2012/10/20		standardize initialization of new record		*
 *		2013/06/01		add table name to constructor					*
 *						add static method getEmailList					*
 *		2013/08/09		base class renamed to Record					*
 *		2013/11/15		handle missing database connection				*
 *		2014/03/30		do not duplicate administrators who are also	*
 *						record owners in the email list					*
 *						implement associative array parameter for		*
 *						constructor										*
 *						replace getEmailList with getOwners				*
 *		2014/09/26		Method addUser did not work because it first	*
 *						checked if the current user was already an		*
 *						owner before adding								*
 *						No need to add administrator as owner			*
 *						Constructor functionality enhanced to clearly	*
 *						handle the three cases:							*
 *						1) complete row from database supplied			*
 *						2) set of parameters to find existing record	*
 *						3) set of parameters to either match existing	*
 *						   record or initialize a new record			*
 *						static method chkOwner uses constructor			*
 *						static method addOwner uses constructor			*
 *		2014/10/01		addOwner set default keyname for Legacy tables	*
 *		2014/12/08		diagnostic output to $warn						*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/12/18		add support for temporary fields				*
 *		2019/07/13      use named insertion points and debugPrepQuery   *
 *		                fix insertion logic                             *
 *		2019/11/21      add RecOwner::$info                             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/User.inc';

/************************************************************************
 *  class RecOwner														*
 *																		*
 *  Definition of a class implementing the interface to a record		*
 *  in the RecOwner table.												*
 *																		*
 ************************************************************************/

class RecOwner extends Record
{
    /********************************************************************
     *	RecOwner::$initRow						                        *
     *									                                *
     *  array containing default values for a new record		        *
     ********************************************************************/
    protected static	$initRow	= array(
								'ro_index'		    => 0,
								'ro_username'		=> '',
								'ro_table'		    => '',
								'ro_keyname'		=> '',
								'ro_keyvalue'		=> 0);

    /********************************************************************
     *	RecOwner::$translate			                    			*
     *									                                *
     *  array containing synonyms for field names			            *
     ********************************************************************/
    protected static	$translate	= array(
								'index'				=> 'ro_index',
								'username'			=> 'ro_username',
								'table'				=> 'ro_table',
								'keyname'			=> 'ro_keyname',
								'id'				=> 'ro_keyvalue',
								'keyvalue'			=> 'ro_keyvalue');

    /********************************************************************
     *	RecOwner::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'RecOwners',
                'name'      => 'RecOwners',
                'prime'	    => 'ro_index',
	            'srvmg'     => true,
	            'fldcount'	=> 6,
                'order'     => 'RO_Username,RO_Table,RO_KeyValue',
                'classname' => 'RecOwner');

    /********************************************************************
     *	RecOwner::__construct											*
     *																	*
     *  Construct an instance of a record owner							*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameters			    *
     *				    which may be a complete row retrieved from		*
     *				    the table or								    *
     *					an associative array of parameters to find a	*
     *				    match in the table or						    *
     *				    numeric identifier of a record in the table		*
     *																	*
     *  Returns instance of RecOwner					                *
     ********************************************************************/
    function __construct($parms)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$dbrow		= null;
		$needInsert	= true;
		if (is_int($parms) || (is_string($parms) && ctype_digit($parms)))
		{			        // numeric identifier of record
		    $parms	= array('ro_index'	=> $parms);
		}			        // numeric identifier of record

		if (is_array($parms))
        {			        // associative array or record
            if ($debug)
                $warn   .= "<p>new RecOwner(" . print_r($parms, true) ."</p>\n";
		    if (count($parms) == count(self::$initRow))
		    {			    // array is same size as record
				$index		        = 0;
				$isRecord	        = true;
				foreach($parms as $fldname => $value)
				{		    // loop through all parameters
				    $lcfldname		= strtolower($fldname);
				    if ($lcfldname == 'ro_index')
				    {
						$index		= intval($value);
				    }
				    if (!array_key_exists(strtolower($fldname), self::$initRow))
				    {		// does not match any field in record
						$isRecord	= false;
						$warn		.= "<p>RecOwner::__construct: " .
				"Unsupported field name `$fldname` in supplied record is ignored.</p>\n";
				    }		// does not match any field in record
				}		    // loop through all parameters

				if ($isRecord)
				{		    // parms is complete row
				    $dbrow	        = $parms;
				    parent::__construct($dbrow,
									    'RecOwners');
				    $needInsert	    = $index == 0;
				}		    // parms is complete row
		    }			    // array is same size as record
		}			        // associative array or record
		else
		    $this->msg  .= "RecOwner::__construct: called with " .
                            gettype($parms) . '=' . print_r($parms, true) .
                            ".\n";

		// supplied with parameters to select a row from table
		if (is_null($dbrow))
		{		            // get record from database
		    $where		            = '';
		    $sqlParms		        = array();
		    $and		            = 'WHERE ';
		    foreach($parms as $field => $value)
		    {
				$fieldLc	        = self::getRealFieldName($field);
				switch($fieldLc)
				{	    	// act on specific fields
				    case 'ro_index':
				    {		// key of existing record
						$where		        .= $and . 'RO_Index=:index';
						$sqlParms['index']	= $value;
						$and		        = ' AND ';
						break;
				    }		// key of existing record

				    case 'ro_username':
				    {		// user name field
						$where		        .= $and . 'RO_UserName=:name';
						$sqlParms['name']	= $value;
						$and		        = ' AND ';
						break;
				    }		// user name field

				    case 'ro_table':
				    {		// table name field
						$where		        .= $and .  'RO_Table=:table';
						$sqlParms['table']	= $value;
						$and		        = ' AND ';
						break;
				    }		// table name field

				    case 'ro_keyname':
				    {		// key name field
						$where		        .= $and .  'RO_KeyName=:key';
						$sqlParms['key']	= $value;
						$and		        = ' AND ';
						break;
				    }		// key name field

				    case 'ro_keyvalue':
				    {		// key value field
						$where		        .= $and .'RO_KeyValue=:value';
						$sqlParms['value']	= $value;
						$and		        = ' AND ';
						break;
				    }		// key value

				    default:
				    {
						$warn		.= "<p>RecOwner::__construct: " .
				__LINE__ . " unsupported field name '$field' is ignored</p>\n";
						break;
				    }
				}	    	// act on specific fields
		    }		    	// loop through all parameters

		    // query the database
            $query		            = "SELECT * FROM RecOwners $where";
		    $stmt		            = $connection->prepare($query);
            $queryText              = debugPrepQuery($query, $sqlParms);

		    if ($stmt->execute($sqlParms))
		    {			    // success
				if ($debug)
                    $warn	        .= "<p>RecOwner::__construct: " . 
                                        __LINE__ . " $queryText</p>\n";

				$result		        = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$count		        = count($result);
				if ($count == 0)
				{			// need to create new record
				    $dbrow		    = self::$initRow;
				    foreach($parms as $fldname => $value)
				    {
						$fieldLc	= self::getRealFieldName($fldname);
						$dbrow[$fieldLc]	= $value;
				    }
				    parent::__construct($dbrow,
				            		    'RecOwners');
				    $needInsert		= true;
				}			// need to create new record
				else
				{			// record found
				    $dbrow		    = $result[0];
				    parent::__construct($dbrow,
					    				'RecOwners');
				    $needInsert		= false;
				    if ($count > 1)
				    {
						$warn	    .= "<p>RecOwner::__construct: " .
								    "$count records found $queryText</p>\n";
				    }
				}			// record found
		    }			    // success
		    else
		    {			    // error performing query
				throw new Exception("RecOwner::__construct: '" .
								    $queryText . "', result=" .
								    print_r($stmt->errorInfo(),true));
		    }			    // error performing query
		}			        // get record from database


		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('RecOwner Record constructed:');
    }		// RecOwner::__construct

    /********************************************************************
     *	RecOwner::chkOwner												*
     *																	*
     *  Given the identification of a particular record in the			*
     *	family tree database, determine if the current user has			*
     *  permission to update it.										*
     *																	*
     *  Input:															*
     *	    $id			identifier of a record in the table			    *
     *	    $table		name of table, default tblIR					*
     *																	*
     *  Returns true if current user is an owner of the specified	    *
     *	    	record.		                            				*
     ********************************************************************/
    static function chkOwner($id,
						     $table = 'tblIR')
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;
		global	$userid;	// current user

		if ($debug)
		{
		    $warn	.= "<p>RecOwner::chkOwner(id=$id, table='$table')</p>\n";
		}

		// check for database connection
		if (strlen($msg) > 0)
		    return false;

		// administrator has access to all records even if not explicitly
		// granted by the creator	
		if (canUser('all'))
		    return true;
		else
		if (!canUser('edit'))
		    return false;

		// does the current user share ownership of the record
		$getParms	= array('RO_UserName'	=> $userid,
							'RO_Table'	    => $table,
							'RO_KeyValue'	=> $id);
		try {
		    $result	= new RecOwner($getParms);
		    return true;
		} catch (Exception $e) {
		    // don't actually need to look at record
		}

		return false;
    }		// static function RecOwner::chkOwner

}		// class RecOwner 
