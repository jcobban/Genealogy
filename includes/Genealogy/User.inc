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
 *	CREATE TABLE `Users` (                                              *
 *		UserName		VARCHAR(64),									*
 *		Password		CHAR(32),										*
 *		ShaPassword		CHAR(128),										*
 *		Email			VARCHAR(64),									*
 *		CellPhone		VARCHAR(16),									*
 *		Auth			VARCHAR(32),									*
 *		id				INT(10) AUTOINCREMENT							*
 *		Options			INT(10),				// bit mask				*
 *		ConfirmId		INT(10),				    					*
 *		PRIMARY KEY (`UserName`),                                       *
 *      UNIQUE KEY `id` (`id`),                                         *
 *      UNIQUE KEY `Email` (`Email`) )                                  *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
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
 *		2019/07/16      add fields cellphone and confirmid              *
 *		                fix several bugs in constructor and set         *
 *		2019/08/02      add function can($action)                       *
 *		2019/08/26      add function getName() to override default      *
 *		                add function getId() to override default        *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
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
						'cellphone'			=> null,
						'auth'				=> 'pending',
						'id'				=> 0,
                        'options'			=> 1,
                        'confirmid'         => null);

    /********************************************************************
     *	User::$info	    										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
		                'table'     		=> 'Users',
		                'name'      		=> 'Users',
		                'prime'	    		=> 'username',
			            'srvmg'     		=> false,
			            'fldcount'			=> 4,
		                'order'     		=> 'UserName',
		                'classname' 		=> 'User');
		
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
     *	User::$hashseed 												*
     *																	*
     *	Constant string to concatenate prior to generating hash.        *
     ********************************************************************/
    protected static	$hashseed   = '';

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

        if (self::$hashseed == '')
        {
            global  $hashseed;
            if (isset($hashseed) && is_string($hashseed))
                self::$hashseed     = $hashseed;
        }
		$needInsert	                = true;

		if (is_null($connection))
            throw new Exception("User::__construct: connection is null");

		// support old-style index parameter
        if (is_string($parms))
        {
            if ($parms == 'new')
                $parms	            = array('id'	    => 0);
            else
                $parms	            = array('username'	=> $parms);
        }
		else
		if ((is_int($parms) || ctype_digit($parms)) && $parms >= 0)
            $parms	                = array('id'	=> $parms);
        else
        if (is_null($parms))
            $parms	                = array('id'	=> 0);

		if ($debug)
		    $warn	.= "<p>User::__construct(" . print_r($parms, true) . 
						   ")</p>\n";

		if (is_array($parms))
		{		                    // associative array
		    if (count($parms) >= count(self::$initRow))
		    {			            // complete row from database
				$dbrow		        = $parms;
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
						$result		        = $stmt->fetch(PDO::FETCH_ASSOC);
						if (is_array($result))
						{		    // found matching record
						    $dbrow		    = $result;
						    parent::__construct($dbrow,
							            		'Users');
						    $needInsert		= false;
						}		    // found matching record
						else
						{		    // no matching record
                            $dbrow		    = self::$initRow;
						    $needInsert		= true;
						}		    // no matching record
				    }			    // success
				    else
				    {			    // error performing query
						$this->msg    .= "User::__construct: " .
							    "SQL error: query='$queryText', error '" .
							    print_r($stmt->errorInfo(),true)."'";
				    }			    // error performing query
				}			        // found a unique keyfield

				// check for initialization of fields
				$parmString		                    = '';
				foreach($parms as $name => $value)
				{		            // loop through all parameters
				    $parmString		                .= "$name='$value',";
                    $fieldLc		                = strtolower($name);
                    if (is_string($value))
						$value			            = trim($value);
				    switch($fieldLc)
				    {	            // act on specific parameter
						case 'id':
						{		    // record index: already handled
						    break;
						}		    // record index

						case 'username':
						{
						    $dbrow['username']	    = $value;
						    if ($needInsert && $dbrow['email'] == '')
						    {
						    	$dbrow['email']		= $value; // default
						    }
						    break;
						}		    // user name

						case 'email':
						{		    // email address
					    	$dbrow['email']		    = $value;
						    if ($needInsert && $dbrow['username'] == '')
                            {
                                if ($keyname != 'UserName')
						    	    $dbrow['username']	= $value;
						    }
						    break;
						}		    // email address

						case 'password':
						{		    // interpret as external text password
							$dbrow['password']	    = null;
							$hash	    	        = 
					hash('sha512', trim($value) . self::$hashseed);
						    if ($needInsert)
						    {	    // creating new instance
							    $sha	    	        = $hash;
							    $dbrow['shapassword']	= $sha;
						    }	    // creating new instance
						    else
                            {	    // password must match existing
								$sha		            = $dbrow['shapassword'];
								if (strlen($sha) == 0)
								{
								    $sha	            = $hash;
								    $dbrow['shapassword']	= $sha;
                                }
						    }	    // password must match existing
						    break;
						}		    // interpret as external text password

						case 'cellphone':
						{		    // initialize string fields
							$dbrow[$fieldLc]	    = $value;
						    break;
						}		    // initialize string fields

						case 'options':
						case 'confirmid':
						{		    // initialize integer fields
							$dbrow[$fieldLc]	    = intval($value);
						    break;
						}		    // initialize integer fields

						case 'auth':
                        {		    // initialize other fields
                            $value                  = strtolower($value);
                            $dbrow[$fieldLc]	    = $value;
                            if ($value == 'pending')
                                $dbrow['confirmid'] = time();
                            else
                                $dbrow['confirmid'] = null;
						    break;
						}		    // initialize other fields

						case 'usemail':
						{		    // initialize pseudo field
						    if (is_int($value))
                            {
                                if ($value)
								    $value = $dbrow['options'] |
										     OPT_USEMAIL_ON;
								$dbrow['options']	= $value;
						    }	    // update existing record
						    break;
                        }	        // initialize other fields

						case 'shapassword':
						{		    // set only through change password
						    break;
                        }	        // set only through change password

				    }	            // act on specific parameter
				}	                // loop through all parameters
            }		                // key and optional other parms
		}		                    // associative array
		else
		{		                    // unexpected parameter type
		    throw new Exception('User::__construct: called with ' .
                				 gettype($parms));
		}		                    // unexpected parameter type

        // invoke constructor of base class
        if ($needInsert)
        {
            if ($dbrow['auth'] == 'pending')
                $dbrow['confirmid']         = time();
			// check for duplicate email
			$query	        = "SELECT * FROM Users WHERE `EMail`=:email";
            $sqlParms       = array('email'         => $this->row['email']);
			$stmt	        = $connection->prepare($query);
			$queryText	    = debugPrepQuery($query, $sqlParms);
			if ($stmt->execute($sqlParms))
			{			    // success
				if ($debug)
					$warn	.= "<p>User::set: " . __LINE__ .
					            			" $queryText</p>";
				$result		= $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($result))
				{		    // found matching record
                    $this->msg    .= "New email value '$value' duplicates an existing user " . $result['username'] . '. ';
                }		    // found matching record
            }               // success
            else
                $warn   .= "<p>User::__construct: " . __LINE__ . 
                                " query='$queryText' failed " .
                                print_r($stmt->errorInfo(), true) . "</p>\n";
        }                   // inserting

		parent::__construct($dbrow,
		    				'Users');
		$this->needInsert	        = $needInsert;

		// diagnostic output if debug is set
        $this->dump('User Record constructed:');

        // report any errors detected in constructor to user
        $msg                        .= $this->msg;
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
            case 'id':
            {
                if ($this->needInsert)
                    $this->save(false);
                return $this->row['id'];
            }

		    case 'usemail':
				return $this->row['options'] & self::OPT_USEMAIL_ON;

		    default:
		    {
				return parent::get($field);
		    }
		}			// act on specific field names
    }		// User::get

    /********************************************************************
     *	function User::getId											*
     *																	*
     *	Get the unique numeric identifier of the record.				*
     *																	*
     *	Returns:														*
     *	    unique numeric id or null                                   *
     ********************************************************************/
    function getId()
    {
		global $warn;

		$retval	                = $this->row['id'];
		if ($this->needInsert && $retval == 0)
		{
		    $warn	.= "<p>User::getId: table " . $this->table .
				" record does not exist in database</p>\n";
            return null;
        }
        else
            return $retval;
    }       // function User::getId

    /********************************************************************
     *	function User::getName										    *
     *																	*
     *	Get the name of the User.	    								*
     ********************************************************************/
    function getName($options	= null)
    {
		$retval	= $this->row['username'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// User::getName

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
        global  $debug;
        global  $warn;

		parent::set('password', null);
        $hash	    = hash('sha512', trim($newPassword) . self::$hashseed);
        $warn   .= "<p>User::setPassword: password=$newPassword, hash=$hash</p>\n";
		parent::set('shapassword',
					$hash);
		return null;
    }		// function User::setPassword

    /********************************************************************
     *  function User::chkPassword										*
     *																	*
     *	Check the value of the password for a user.					    *
     *																	*
     *  Parameters:														*
     *	    $newPassword	new password value as entered by user		*
     ********************************************************************/
    function chkPassword($newPassword)
    {
        global  $debug;
        global  $warn;

        if (!is_null($this->row['shapassword']))
        {
            $hash	    = hash('sha512', trim($newPassword) . self::$hashseed);
            $warn       .= "<p>User::chkPassword: password=$newPassword, " .
        "hash=$hash, shapassword=" . $this->row['shapassword'] . "</p>\n";
            return $hash == $this->row['shapassword'];
        }
        else
            return $newPassword == $this->row['password'];
    }		// function User::chkPassword

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
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
            $fieldLc	= self::$translate[$fieldLc];
        if ($debug)
            $warn       .= "<p>User::set('$field','$value')'</p>\n";
		switch($fieldLc)
		{
		    case 'password':
		    {
				return self::setPassword($value);
		    }		            // set password

		    case 'usemail':
		    {		            // simulate obsolete field usemail
				if (array_key_exists('usemail', $this->row))
                    return parent::set($field, $value);
                if ($value)
                    $value	= $this->row['options'] | self::OPT_USEMAIL_ON;
                else
				    $value	= $this->row['options'] & ~self::OPT_USEMAIL_ON;
				return parent::set('options', $value);
		    }		            // simulate obsolete field usemail

            case 'auth':
            {
                $value          = strtolower($value);
                if ($value == 'pending')
                {
                    if ($this->row['auth'] != 'pending')
                        return parent::set('confirmid', time());
                }
                else
                    parent::set('confirmid', null);
                return parent::set('auth', $value);
                break;
            }

		    case 'email':
		    {		            // check for duplicates
				if (is_string($value))
                    $value		= trim($value);
                if ($value == $this->row['email'])
                    return $value;      // no change

				// construct the query of the user table
                $query	        = "SELECT * FROM Users " .
                    "WHERE `EMail`=:email AND `UserName`<>:username";
                $sqlParms       = array('email'     => $value,
                                        'username'  => $this->row['username']);
				$stmt	        = $connection->prepare($query);
				$queryText	    = debugPrepQuery($query, $sqlParms);
				if ($stmt->execute($sqlParms))
				{			    // success
					if ($debug)
						$warn	.= "<p>User::set: " . __LINE__ .
						            			" $queryText</p>";
					$result		= $stmt->fetch(PDO::FETCH_ASSOC);
					if (is_array($result))
					{		    // found matching record
	                    $msg    .= "New email value '$value' duplicates an existing user " . $result['username'] . '. ';
	                    return false;
	                }		    // found matching record
	            }               // success
	            else
	                $warn   .= "<p>User::save: " . __LINE__ . 
	                                " query='$queryText' failed " .
	                                print_r($stmt->errorInfo(), true) . "</p>\n";
				return parent::set($field, $value);
		    }		            // no validation implemented yet

		    default:
		    {		            // no validation implemented yet
				if (is_string($value))
				    $value		= trim($value);
				return parent::set($field, $value);
		    }		            // no validation implemented yet
		}		                // switch on field name
    }		// function User::set

    /********************************************************************
     *  function User::save     										*
     *																	*
     *	Save changes made to the User record into the	database.		*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method updated the database record		*
     *	    0			if the database did not need to be updated		*
     ********************************************************************/
    function save($xml)
    {
        global  $connection;
        global  $debug;
        global  $warn;

        $inserting              = $this->needInsert;
        $retval                 = parent::save($xml);
        if ($inserting)
        {
			// construct the query of the user table
			$query	        = "SELECT * FROM Users WHERE `UserName`=:username";
            $sqlParms       = array('username'      => $this->row['username']);
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
					$this->row['id']    = $result['id'];
                }		    // found matching record
            }               // success
            else
                $warn   .= "<p>User::save: " . __LINE__ . 
                                " query='$queryText' failed " .
                                print_r($stmt->errorInfo(), true) . "</p>\n";
        }
        return $retval;
    }		// function User::save

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
	
	/************************************************************************
	 *  public function User::can											*
	 *																		*
	 *  This method returns true if the user can perform	                *
	 *  the indicated function.												*
	 *																		*
	 *  Parameters:															*
	 *		action	    string describing function to be performed  		*
	 *					    'edit'		alter databases						*
	 *						'blog'		post blogs							*
	 *						'admin'		administrator						*
	 *						'debug'		authorized debugger					*
	 *						'pending'	user waiting for confirmation		*
	 *						'all'		master administrator				*
	 *																		*
	 ************************************************************************/
	function can($action)
	{
	    $authorized         = $this->row['auth'];
	    return (($authorized == 'yes') || ($authorized == 'all') ||
	    		(preg_match("/\b$action\b/i", $authorized) == 1));
	}		// User::can

}		// class User 

