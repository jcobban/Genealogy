<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  User.inc															*
 *																		*
 *  Definition of a class representing a record in the Users table		*
 *  that represents a single user entry.								*
 *																		*
 *		UserName		VARCHAR(64)										*
 *		Password		CHAR(32)										*
 *		ShaPassword		CHAR(128)										*
 *		Email			VARCHAR(64)										*
 *		Auth			VARCHAR(32)										*
 *		id				INT(10) AUTOINCREMENT							*
 *		Options			INT(10)				bit mask					*
 *																		*
 *  History:															*
 *		2014/03/27		created											*
 *		2014/07/25		field 'usemail' replaced by bit in 'options'	*
 *						create parameter 'ousername' to specify to		*
 *						find an existing user by name without creating	*
 *						a new entry										*
 *						method setPassword added to centralize password	*
 *						manipulation here								*
 *		2014/07/31		record not constructed from database when		*
 *						numeric record id passed						*
 *		2014/08/01		do not construct new record if only passed		*
 *						e-mail address									*
 *		2014/11/29		put trace information into $warn				*
 *		2015/03/05		in static method getUsers if passed 'Auth'		*
 *						value, match on complete token and always		*
 *						include admins									*
 *		2015/07/03		accept numeric string as constructor parameter	*
 *		2015/07/12		add support for Options field to GetUsers		*
 *						make first parm of GetUsers by reference		*
 *						return count in parm							*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						add to getUsers option to match whole user name	*
 *						by '^username$' pattern and to exclude a user	*
 *						name by '!username' pattern						*
 *		2015/12/07		if called with 'ousername' and 'password'		*
 *						perform password validation						*
 *						getUsers treats Auth='pending' specially		*
 *						constructor did not handle full record input	*
 *						constructor did construct from username or		*
 *						email even if it had already done construct		*
 *						using record id									*
 *		2016/01/18		$dbrow not initialized properly for username	*
 *						getUsers failed for username + auth				*
 *		2016/02/02		remove ousername option							*
 *		2017/12/18		add support for temporary fields				*
 *		2018/02/02		do not throw exception on bad index number		*
 *		2018/09/24		clean up constructor implementation				*
 *	            		trim string values of fields                    *
 *		2018/11/11      do not throw exception from constructor for     *
 *		                bad parameters                                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class User															*
 *																		*
 *  Definition of a class implementing the interface to a record		*
 *  in the User table.													*
 *																		*
 ************************************************************************/

class User extends Record
{

    /********************************************************************
     *	User::$initRow													*
     *																	*
     *	array containing default values for all fields in a new record	*
     ********************************************************************/
    protected static	$initRow	= array(
						'username'			=> '',
						'password'			=> null,
						'shapassword'		=> null,
						'email'				=> '',
						'auth'				=> 'pending',
						'id'				=> 0,
						'options'			=> 1);

    /********************************************************************
     *	User::$translate												*
     *																	*
     *	array containing alternate field names							*
     ********************************************************************/
    protected static	$translate	= array(
						'userid'			=> 'username',
						'name'				=> 'username',
						'recordid'			=> 'recordid',
						'table'				=> 'table');

    /********************************************************************
     *	User::$defaultOrder												*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	= 'UserName';

    // masks to turn on an off specific options
    const OPT_USEMAIL_ON		= 1;
    const OPT_NOHELP_ON			= 2;

    /********************************************************************
     *  function User::__construct										*
     *																	*
     *  Given defining parameters construct an instance.				*
     *																	*
     * Input:															*
     *		$parms		associative array of search parameters			*
     *					for example:									*
     *				    array('id'				=> 74)					*
     *				    array('username'		=> 'JohnSmith')			*
     *				    array('email'		=> 'jsmith@google.ca')		*
     *					If the first element in the array identifies	*
     *					one of the unique keys, 'id', 'username', or	*
     *					'email', then that field is used to search for	*
     *					an existing record and any other fields are		*
     *					used to initialize other fields.				*
     *					or an array representing a complete row			*
     *					fetched from the database						*
     *					Deprecated paramter options:                    *
     *					the numeric key of the existing record		    *
     *					or the string "new" to create a new record		*
     *																	*
     *  Returns object containing User record.							*
     *																	*
     *	Throws exception if no connection to SQL server					*
     *	or the parameter is not an associative array, an integer,       *
     *	or the string 'new'.                                            *
     ********************************************************************/
    function __construct($parms = null)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;
		global	$userid;

		$needInsert	        = true;
		$errors		        = '';

		if (is_null($connection))
            throw new Exception("User::__construct: connection is null");

		// support old-style index parameter
		if (is_string($parms) && $parms == 'new')
		    $parms	        = array('id'	=> 0);
		else
		if ((is_int($parms) || ctype_digit($parms)) && $parms >= 0)
            $parms	        = array('id'	=> $parms);
        else
        if (is_null($parms))
            $parms	        = array('id'	=> 0);

		if ($debug)
		    $warn	.= "<p>User::__construct(" . print_r($parms, true) . 
						   ")</p>\n";

		if (is_array($parms))
		{		                    // associative array
		    if (count($parms) >= count(self::$initRow))
		    {			            // complete row from database
				$dbrow		        = $parms;
				// invoke constructor of base class
				parent::__construct($dbrow,
							        'Users');
				$needInsert	        = false;
		    }			            // complete row from datase
		    else
		    {			            // key and optional other parms
				$dbrow		        = self::$initRow;
				$keyname	        = null;
				$sqlparms	        = array();
				foreach($parms as $name => $value)
				{			        // search for unique key field
				    $fieldLc		= strtolower($name);
				    if (array_key_exists($fieldLc, self::$translate))
						$fieldLc	= self::$translate[$fieldLc];
				    switch($fieldLc)
				    {			    // act on specific parameter
						case 'id':
						{		    // record index
						    if (is_string($value))
						    {       // look for an integer in the value
                                $result	        = preg_match('/\d+/',
                                                             $value, 
                                                             $matches);
								if ($result == 1)
								    $value	    = $matches[0];
                                else
                                {   // invalid value
								    $this->msg    .= "User::__construct: " .
                                                "parameter error: ID='$value'";
                                    $value      = 0;
                                }   // invalid value
                            }       // look for an integer in the value
						    $keyname		    = 'ID';
						    $sqlParms['id']	    = $value;
                            break 2;	// exit loop
						}		    // record index

						case 'username':
						{
						    $keyname		= 'UserName';
						    $sqlParms['id']	= trim($value);
						    break 2;	// exit loop
						}		    // user name

						case 'email':
						{
						    $keyname		= 'Email';
						    $sqlParms['id']	= trim($value);
						    break 2;	// exit loop
						}		    // email address
				    }			    // switch on field name
				}			        // search for unique key field

				if (!is_null($keyname))
				{			        // found a unique keyfield
				    // construct the query of the user table
				    $query	        = "SELECT * FROM Users WHERE $keyname=:id";

				    // query the database
				    $stmt	        = $connection->prepare($query);
				    $queryText	    = debugPrepQuery($query, $sqlParms);
				    if ($stmt->execute($sqlParms))
				    {			    // success
						if ($debug)
						    $warn	.= "<p>User::__construct: " . __LINE__ .
								        " $queryText</p>";
						$result		= $stmt->fetch(PDO::FETCH_ASSOC);
						if (is_array($result))
						{		    // found matching record
						    $dbrow		    = $result;
						    parent::__construct($dbrow,
									'Users');
						    $needInsert		= false;
						}		    // found matching record
						else
						{		    // invalid key
						    $dbrow		    = self::$initRow;
						    $needInsert		= true;
						}		    // invalid key
				    }			    // success
				    else
				    {			    // error performing query
						$this->msg    .= "User::__construct: " .
							    "SQL error: query='$queryText', error '" .
							    print_r($connection->errorInfo(),true)."'";
				    }			    // error performing query
				}			        // found a unique keyfield

				// check for initialization of fields
				$parmString		    = '';
				foreach($parms as $name => $value)
				{		            // loop through all parameters
				    $parmString		.= "$name='$value',";
				    $fieldLc		= strtolower($name);
				    switch($fieldLc)
				    {	            // act on specific parameter
						case 'id':
						{		    // record index: already handled
						    break;
						}		    // record index

						case 'username':
						{
						    $value			        = trim($value);
						    if ($needInsert)
						    {
						    	$dbrow['username']	= $value;
						    	$dbrow['email']		= $value; // default
						    }
						    else
					    		parent::set('username', $value);
						    break;
						}		    // user name

						case 'email':
						{		    // email address
						    $value			        = trim($value);
						    if ($needInsert)
                            {
                                if ($keyname != 'UserName')
						    	    $dbrow['username']	= $value;
					    		$dbrow['email']		= $value;
						    }
						    else
							    parent::set('email', $value);
						    break;
						}		    // email address

						case 'password':
						{		    // interpret as external text password
						    $value			= trim($value);
						    if ($needInsert)
						    {	    // creating new instance
							    $dbrow['password']	    = null;
							    $sha	    	= hash('sha512', trim($value));
							    $dbrow['shapassword']	= $sha;
						    }	    // creating new instance
						    else
                            {	    // password must match existing
								$sha		= $dbrow['shapassword'];
								if (strlen($sha) == 0)
								{
								    $sha	= hash('sha512', trim($value));
								    $dbrow['shapassword']	= $sha;
								}
								if ($sha != hash('sha512', trim($value)))
								{
								    $this->msg    .= "User::__construct: " .
                                            "Password does not match. ";
                                    $needInsert     = false;
								}
						    }	    // password must match existing
						    break;
						}		    // interpret as external text password

						case 'shapassword':
						case 'auth':
						case 'options':
						{		    // initialize other fields
						    if (is_string($value))
							    $value			    = trim($value);
						    if ($needInsert)
							    $dbrow[$fieldLc]	= trim($value);
						    else
							    parent::set($fieldLc, $value);
						    break;
						}		    // initialize other fields

						case 'usemail':
						{		    // initialize pseudo field
						    if (is_int($value))
						    {
								if ($needInsert)
								{	// new record, just set bit in options
								    $value = $dbrow['options'] |
										($value & OPT_USEMAIL_ON);
								    $dbrow['options']	= $value;
								}	// new record, just set bit in options
								else
								{	// update existing record
								    if (array_key_exists('usemail', $this->row))
									    parent::set('usemail', $value);
								    else
									    parent::set('options', $value);
								}	// update existing record
						    }	    // update existing record
						    break;
						}	        // initialize other fields

				    }	            // act on specific parameter

				    $firstparm		= false;
				}	                // loop through all parameters
            }		                // key and optional other parms
		}		                    // associative array
		else
		{		// unexpected parameter type
		    throw new Exception('User::__construct: called with ' .
				 gettype($parms));
		}		// unexpected parameter type

		// invoke constructor of base class
		if ($needInsert)
		{
		    parent::__construct($dbrow,
							'Users');
		    $this->needInsert	= true;
		}
		else
		{
		    $this->needInsert	= false;
		}

		// diagnostic output if debug is set
        $this->dump('User Record constructed:');

        // report any errors detected in constructor to user
        $msg        .= $this->msg;
    }		// function User::__construct

    /********************************************************************
     *	function User::get												*
     *																	*
     *  Get the value of a field by name								*
     *																	*
     *	Parameters:														*
     *	    $field		field name										*
     *																    *
     *	Returns:														*
     *	    the value of the field										*
     *																    *
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'usemail':
				return $this->row['options'] & self::OPT_USEMAIL_ON;

		    default:
		    {
				return parent::get($field);
		    }
		}			// act on specific field names
    }		// Birth::get

    /********************************************************************
     *  function User::setPassword										*
     *																	*
     *	Change the value of the password for a user.					*
     *  The actual password is not recorded in the record, only the		*
     *	one-way SHA512 hash.											*
     *																	*
     *  Parameters:														*
     *	    $newPassword	new password value as entered by user		*
     ********************************************************************/
    function setPassword($newPassword)
    {
		parent::set('password', null);
		parent::set('shapassword',
						hash('sha512', trim($newPassword)));
		return null;
    }		// function User::setPassword

    /********************************************************************
     *  function User::set												*
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
     *	    the value of the field										*
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
		    case 'password':
		    {
				return self::setPassword($value);
		    }		// set password

		    case 'usemail':
		    {		// simulate obsolete field usemail
				if (array_key_exists('usemail', $this->row))
				    return parent::set($field, $value);
				$value	= ($this->row['options'] & ~self::OPT_USEMAIL_ON) |
						  ($value & self::OPT_USEMAIL_ON);
				return parent::set('options', $value) & 
						self::OPT_USEMAIL_ON;
		    }		// simulate obsolete field usemail

		    default:
		    {		// no validation implemented yet
				if (is_string($value))
				    $value		= trim($value);
				return parent::set($field, $value);
		    }		// no validation implemented yet
		}		// switch on field name
    }		// function User::set

    /********************************************************************
     *  function User::getRealFieldName									*
     *																	*
     *	Perform standard manipulation for this table to obtain the		*
     *	the name of a field from a suggested parameter name.			*
     *																	*
     *	Returns:														*
     *	    A field name in lower case or null if input is invalid.		*
     ********************************************************************/
    public static function getRealFieldName($fldname)
    {
		$fieldLc	= strtolower($fldname);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		if ($fieldLc == 'offset' ||
		    $fieldLc == 'limit' ||
		    $fieldLc == 'order' || 
		    $fieldLc == 'recordid' || 	// support for list of owners
		    $fieldLc == 'table')    	// support for list of owners
		    return $fieldLc;
		else
		if (array_key_exists($fieldLc, self::$initRow))
		    return $fieldLc;
		else
		    return null;
    }		// static function getRealFieldName

}		// class User 

