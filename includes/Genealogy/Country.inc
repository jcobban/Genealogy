<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Country.inc															*
 *																		*
 *  Definition of a class representing a country.						*
 *  This class provides access to the information in a row				*
 *  of the table Countries.												*
 *																		*
 *  CREATE TABLE Countries (											*
 *		Code			CHAR(2) NOT NULL,								*
 *		Name			VARCHAR(255) NOT NULL DEFAULT '',				*
 *		DialingCode		VARCHAR(32) NOT NULL DEFAULT '1',				*
 *		Currency		CHAR(3) NOT NULL DEFAULT 'USD')					*
 *		ENGINE=InnoDb DEFAULT CHARSET=utf8;								*
 *																		*
 *  History:															*
 *		2017/02/04		created											*
 *		2017/09/05		use prepared statement in constructor			*
 *		2017/10/21		support changing code using set					*
 *						remove getCountries, replaced by RecordSet		*
 *		2017/12/18		add support for temporary fields				*
 *		2018/01/01		improve diagnostics in constructor				*
 *						support short-hand constructor call				*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Country														*
 *																		*
 *  Definition of a class recording information about a country.		*
 *																		*
 ************************************************************************/

class Country extends Record
{
    /********************************************************************
     *	Country::$defaultOrder											*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static $defaultOrder	= "`Name`";

    /********************************************************************
     *	Country::$initRow												*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'code'			=> '',
				'name'			=> '',
				'dialingcode'		=> '',
				'currency'		=> '');

    /********************************************************************
     *	Country::$translate												*
     *																	*
     *	Define synonyms for field names									*
     ********************************************************************/
    protected static $translate	= array(
				'cc'			=> 'code');

    /********************************************************************
     *	Country::__construct											*
     *																	*
     *	Construct an instance of Country based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $parms		 		an associative array of parameters		*
     *							to select an existing entry or			*
     *							a complete row from the table as an		*
     *							associative array						*
     *																	*
     *	Returns object containing a description of a country			*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $connection;

		if (is_null($connection))
		{
		    throw new Exception('Country::constructor: " .
							"No connection to database server');
		}
		$needInsert	= false;
		$this->table	= 'Countries';

		// support undocumented shorthand call
		if (is_string($parms) && strlen($parms) == 2)
		    $parms	= array('code'	=> strtoupper($parms));

		// validate parameters
		if (is_array($parms))
		{			// valid parm type
		    if (count($parms) < count(self::$initRow))
		    {			// search parameters
				$code		= 'not supplied';
				$where		= '';
				$and		= 'WHERE ';
				$sqlParms	= array();
				foreach($parms as $key => $value)
				{			// loop through parameters
				    switch(strtolower($key))
				    {		// act on key fields
						case 'cc':
						case 'code':
						{		// country
						    $code		= $value;
						    $where		.= $and . "Code=:code";
						    $sqlParms['code']	= $value;
						    $and		= ' AND ';
						    break;
						}		// country

				    }		// act on key fields
				}			// loop through parameters

				$query		= "SELECT * FROM Countries $where";

				// query the database
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $sqlParms);
				if ($stmt->execute($sqlParms))
				{		// success
				    if ($debug)
						$warn	.= "<p>Country::__construct: " . __LINE__ . 
							   " query='$queryText'</p>\n";

				    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if (count($result) == 1)
				    {		// existing record
						$dbrow		= $result[0];
						$needInsert	= false;
				    }		// existing record
				    else
				    if (count($result) == 0)
				    {		// create a new record
						// set defaults
						$dbrow			= self::$initRow;
						$dbrow['code']		= $code;
						$dbrow['name']		= "Country: '$code'";
						$needInsert		= true;
				    }		// create new record
				    else
				    {		// error performing query
						throw new Exception("Country::__construct: '$queryText' " .
							"returns " . count($result) . " records");
				    }		// error performing query
				}		// success
				else
				{		// error performing query
				    throw new Exception("Country::__construct: '$queryText' " .
								print_r($stmt->errorInfo(),true));
				}		// error performing query
		    }			// search parameters
		    else
		    {			// build from existing record
				$dbrow		= $parms;
				$needInsert	= false;
		    }			// build from existing record
		}			// array parameter
		else
		{			// unsupported parameter type
		    throw new Exception("Country::__construct: " .
						"parameter must be an array: is " .
						gettype($parms));
		}			// unsupported parameter type


		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Countries');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('Country Record constructed:');
    }		// Country::__construct

    /********************************************************************
     *	Country::save													*
     *																	*
     *	Save changes made to the Country record into the database.		*
     *																	*
     *	Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format within <cmd> tags.						*
     *					if a string use it as tag name					*
     ********************************************************************/
    function save($xml)
    {
		global	$debug;
		global	$warn;
		global	$userid;
		global	$connection;

		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $xml	= true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		}		// true

		if ($this->needInsert)
		{		// create new record
		    $fldnames	= '';
		    $comma	= '';
		    $values	= '';
		    $parms	= array();
		    foreach($this->row as $fld => $value)
		    {
				$fldnames	.= $comma . "`$fld`";
				$values		.= $comma . '?';
				array_push($parms, $value);
				$comma	= ", ";
		    }		// loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	= "INSERT INTO Countries ($fldnames) VALUES($values)";

		    // insert the new record into the database
		    $stmt	= $connection->prepare($insert);
		    if ($stmt->execute($parms) === false)
		    {		// error inserting record
				throw new Exception("Country::save:
							    '$insert', " .
							    print_r($stmt->errorInfo(),true));
		    }		// error inserting record
		    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		    $count	= count($result);
		    if ($xml)
				print "<$tagname result='$count'>" . xmlentities($insert) . 
						"</$tagname>\n";
		    if ($debug)
		    {
				$warn	.= "<p>$insert</p>\n";
				$warn	.= "<p>count=$count</p>\n";
		    }

		    $this->changed	= array();	// reset
		    $this->needInsert	= false;

		    // log the update
		    $this->log($insert,
				       $parms);
		}		// create new record
		else
		{		// update existing record
		    $update		= "UPDATE Countries SET ";
		    $set		= '';
		    $comma		= '';
		    $count		= 0;
		    $parms		= array();
		    foreach($this->changed as $fld => $value)
		    {
				$set	.= $comma . "`$fld`=?";
				array_push($parms, $value);
				$comma	= ', ';
		    }		// loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		// changes made
				// assemble the UPDATE command
				$update		.= $set ." WHERE `Code`=?";
				if ($this->former)
				    array_push($parms, $this->former['code']);
				else
				    array_push($parms, $this->row['code']);


				// perform the update
				$stmt		= $connection->prepare($update);
				if ($stmt->execute($parms) === false)
				{		// error updating record
				    throw new Exception("Country::save:
								'$update', " .
								print_r($stmt->errorInfo(),true));
				}		// error updating record
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$tagname count='$count'>" .
							xmlentities($update) . "</$tagname>\n";
				if ($debug)
				    $warn	.= "<p>'$update' count=$count</p>\n";

				// log the update
				$this->log($update,
						   $parms);
		    }		// changes made
		}		// update existing record
		return $count;
    }		// Country::save

    /********************************************************************
     *	Country::delete													*
     *																	*
     *	Delete the database record associated with this instance.		*
     *																	*
     *	Input:															*
     *	    $xml		if true emit XML								*
     *					if a string emit XML using as tag name			*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record		*
     *	    0			if the record did not need to be deleted		*
     ********************************************************************/
    function delete($xml=false)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		// handle XML output option
		if (is_string($xml))
		    $xmltag	= $xml;
		else
		if ($xml)
		{
		    $xml	= true;
		    $xmltag	= 'cmd';
		}

		// to simplify expressions
		if (!$this->needInsert)
		{		// have existing record
		    $code		= $this->row['code'];
		    $delete		= "DELETE FROM Countries WHERE `Code`=:code";
		    $sqlParms		= array('code'	=> $code);
		    $sqlParmsText	= print_r($sqlParms, true);

		    $stmt		= $connection->prepare($delete);
		    if ($stmt->execute($sqlParms))
		    {		// successful delete
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$delete</$xmltag>\n";
				else
				if ($debug)
				    print "<p>$delete</p>\n<p>count='$count'</p>\n";

				// log the update
				$this->log($delete,
						   $sqlParms);

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// successful delete
		    else
		    {		// error updating record
				print "<p>query='$delete', parms=$sqlParmsText</p>\n";
				print "<p class='message'>" .
						print_r($stmt->errorInfo(),true) . 
						"</p>\n";
				return 0;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }		// Country::delete

    /********************************************************************
     *	Country::getName												*
     *																	*
     *	Convenience function for accessing the 'name' attribute.		*
     *																	*
     *	Returns:														*
     *	    Name of the county as a string.								*
     ********************************************************************/
    function getName($options	= '')
    {
		if (strlen($options) == 2)
		{
		    $parms	= array('cc'	=> $this->row['code'],
							'lang'	=> $options);
		    $countryNameObj	= new CountryName($parms);
		    return $countryNameObj->getName();
		}
		else
		    return $this->row['name'];
    }		// Country::getName

    /********************************************************************
     *	Country::__toString												*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return  $this->row['name'];
    }		// Country::__toString

}		// class Country
