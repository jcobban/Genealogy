<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Concession.inc														*
 *																		*
 *  Definition of a class representing a concession within a township	*
 *  specifically for determinining the coordinates of lots within		*
 *  that concession.													*
 *  This class provides access to the information in a row				*
 *  of the table Concessions.											*
 *																		*
 *		Domain			CHAR(4) NOT NULL DEFAULT 'CAON',				*
 *		County			VARCHAR(16) NOT NULL DEFAULT '',				*
 *		Township		VARCHAR(64) NOT NULL DEFAULT '',				*
 *		ConID			VARCHAR(8) NOTE NULL DEFAULT '',				*
 *		PRIMARY KEY (Domain, County, Township, ConID),					*
 *		Order			INTEGER,										*
 *		FirstLot		INTEGER,										*
 *		LastLot			INTEGER,										*
 *		Latitude		FLOAT(10,6) NOT NULL DEFAULT 0.0,				*
 *		Longitude		FLOAT(10,6) NOT NULL DEFAULT 0.0,				*
 *		LatByLot		FLOAT(10,6) NOT NULL DEFAULT 0.0,				*
 *		LongByLot		FLOAT(10,6) NOT NULL DEFAULT 0.0,				*
 *																		*
 *  History:															*
 *		2016/06/13		created											*
 *		2017/12/18		add support for temporary fields				*
 *		2018/10/24      constructor accepts objects for selection       *
 *		                values for domain, county, and township         *
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Township.inc';

/************************************************************************
 *  class Concession													*
 *																		*
 *  Definition of a class recording information about a concession		*
 *  within a township for performing geo-coding.						*
 *  Although this class uses the administrative hierarchy created for	*
 *  tracking vital-statistics (Domain, County, Township) it it mainly	*
 *  used by the family tree portion of the web-site to extend the		*
 *  Google maps geo-coding support.										*
 ************************************************************************/

class Concession extends Record
{
    /********************************************************************
     *	Concession::$defaultOrder										*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static $defaultOrder = "`Domain`,`County`,`Township`,`Order`";

    /********************************************************************
     *	Concession::$initRow											*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'domain'		=> 'CAON',
				'county'		=> '',	// county ID, see class County
				'township'		=> '',	// township name
				'conid'			=> '',	// concession identifier
				'order'			=> 1,	// order within township
				'firstlot'		=> 1,	// first lot number
				'lastlot'		=> 20,	// last lot number
				'latitude'		=> 0.0,	// center latitude of lot 1
				'longitude'		=> 0.0, // center longitude of lot 1
				'latbylot'		=> 0.0,	// diff latitude for lot n+1
				'longbylot'		=> 0.0);// diff longitude for lot n+1

    /********************************************************************
     *	function Concession::__construct								*
     *																	*
     *	Construct an instance of Concession based upon the				*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $parms		associative array of parameters to select or	*
     *					initialize an instance of Concession			*
     *	    $create		if true create a new instance if not present	*
     *																	*
     *	Returns object containing a description of a concession			*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms,
						 $create	= true)
    {
		global $debug;
		global $warn;
		global $connection;

		$needInsert 	= false;
		$this->table	= 'Concessions';
		$domain	    	= null;
		$prov	    	= null;
		$county		    = null;
		$township   	= null;
		$conid	    	= null;

		// validate parameters
		if (is_array($parms))
		{		        	// valid first parameter
		    foreach($parms as $fldname => $value)
		    {		    	// loop through all parameters
				switch(strtolower($fldname))
				{	    	// act on specific field names
				    case 'domain':
				    {		// registration domain
						if ($value instanceof Domain)
						    $domainObj	= $value;
						else
						    $domainObj	= new Domain(array('domain'	=> $value,
								                           'language'=> 'en'));
						if ($domainObj->isExisting())
						{
						    $domain		= $domainObj->get('domain');
						    $prov		= substr($domain, 2);
						}
						else
						    throw new Exception("Concession::__construct: " .
							"Invalid value Domain='$value'");
						break;
				    }		// registration domain

				    case 'county':
				    {		// County abbreviation
                        if ($value instanceof County)
                        {
                            $countyObj	= $value;
                            $county     = $countyObj->get('code');
                        }
                        else
                        {
						    $county		= $value;
						    $countyObj	= new County(array('domain'	=> $domainObj,
                                                           'code' => $county));
                        }
						break;
				    }		// County abbreviation

				    case 'township':
				    {		// township name
                        if ($value instanceof Township)
                        {
                            $townshipObj	= $value;
                            $township       = $townshipObj->get('code');
                        }
                        else
                        {
						    $township		= $value;
                            $townshipObj	= new Township(
                                            array('domain'	=> $domainObj,
                                                  'county' => $countyObj,
                                                  'township' => $township));
                        }
						break;
				    }		// township name

				    case 'conid':
				    {		// concession identifier
						$conid		= $value;
						break;
				    }		// concession identifier

				}	    	// act on specific field names
		    }		    	// loop through all parameters
		}		        	// valid first parameter
		else
		    throw new Exception("Concession::__construct: " .
						"First parameter is not an associative array");

		if (is_null($domain))
		    throw new Exception("Concession::__construct: " .
						"Missing mandatory parameter Domain");
		if (is_null($county))
		    throw new Exception("Concession::__construct: " .
						"Missing mandatory parameter County");
		if (is_null($township))
		    throw new Exception("Concession::__construct: " .
						"Missing mandatory parameter Township");
		if (is_null($conid))
		    throw new Exception("Concession::__construct: " .
						"Missing mandatory parameter ConID");
		// construct the record from a query
		$query	= "SELECT * FROM Concessions  " .
						  "WHERE `Domain`=? AND `County`=? AND `Township`=? AND ConID=?";
		$sqlParms	= array($domain, $county, $township, $conid);

		// query the database
		$stmt		= $connection->prepare($query);
		$queryText	= debugPrepQuery($query, $sqlParms);
		if ($stmt->execute($sqlParms))
		{	            	// successful query
		    if ($debug)
		    {
				$warn	.= "<p>Concession::__construct: '$queryText'</p>\n";
		    }
		    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		    $count	= count($result);
		    if ($count == 1)
		    {	        	// existing record
				$dbrow		= $result[0];
				$needInsert	= false;
		    }	        	// existing record
		    else
		    if ($count == 0)
		    {	        	// no matching record
				if ($create)
				{       	// create a new record
				    // set defaults
				    $dbrow		= self::$initRow;
				    $dbrow['domain']	= $domain;
				    $dbrow['county']	= $county;
				    $dbrow['township']	= $township;
				    $dbrow['conid']	= $conid;
				    $needInsert		= true;
				}	        // create a new record
				else
				    throw new Exception("Concession::__construct: " .
    "No record for Domain='$domain', County='$county', and Township='$township', and ConID='$conid'");
		    }	        	// no matching record
		    else
				throw new Exception("Concession::__construct: " .
    "$count records match Domain='$domain', County='$county', and Township='$township', and ConID='$conid'");

		    // invoke constructor of base class
		    parent::__construct($dbrow,
							'Concessions');
		    $this->needInsert	= $needInsert;

		    // if requested update field values
		    foreach($parms as $fldname => $value)
		    {		    	// loop through all parameters
				switch(strtolower($fldname))
				{	    	// act on specific field names
				    case 'order':
				    case 'firstlot':
				    case 'lastlot':
				    case 'latitude':
				    case 'longitude':
				    case 'latbylot':
				    case 'longbylot':
				    {		// valid field names to update
						if ($needInsert)
						    $this->row[strtolower($fldname)]	= $value;
						else
						    parent::set($fldname, $value);
						break;
				    }		// valid field names to update
				}		    // act on specific field names
		    }		    	// loop through all parameters
		}	            	// successful query
		else
		{	            	// error performing query
		    throw new Exception("Concession::__construct: '$queryText' " .
							print_r($stmt->errorInfo(),true));
		}		            // error performing query

		// diagnostic output if debug is set
		$this->dump('Concession Record constructed:');
    }		// function Concession::__construct

    /********************************************************************
     *	function Concession::set										*
     *																	*
     *	Change the value of a field in the object.						*
     *	The method validates that the supplied value is compatible with *
     *	the target field.												*
     *																	*
     *	Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		global	$warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{		        	// act on specific fields
		    case 'order':
		    case 'firstlot':
		    case 'lastlot':
		    case 'latitude':
		    case 'longitude':
		    case 'latbylot':
		    case 'longbylot':
		    {
				return parent::set($field, $value);
		    }

		    default:
		    {
				if (array_key_exists($fieldLc, $this->row))
				{	    	// permanent field
				    if ($value != $this->row[$fieldLc])
						$warn	.= "<p>Concession::set: " . __LINE__ .
								"cannot change value of '$field'</p>\n";
				    return $this->row[$fieldLc];
				}	    	// permanent field
				else
				return parent::set($field, $value);
		    }
		}		        	// act on specific fields
    }		// function Concession::set

    /********************************************************************
     *	function Concession::save										*
     *																	*
     *	Save changes made to the Concession record into the database.	*
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
		{		        	// string tag name
		    $tagname	= $xml;
		    $xml	= true;
		}		        	// string tag name
		else
		if ($xml)
		{		        	// true
		    $tagname	= 'cmd';
		}	        		// true

		if ($this->needInsert)
		{		        	// create new record
		    $fldnames	= '';
		    $comma	= '';
		    $values	= '';
		    $trvalues	= '';
		    $parms	= array();
		    foreach($this->row as $fld => $value)
		    {
				$fldnames	.= $comma . "`$fld`"; 
				$values		.= $comma . '?';
				$trvalues	.= $comma . "'$value'";
				array_push($parms, $value);
				$comma	= ", ";
		    }		        	// loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	= "INSERT INTO Concessions ($fldnames) VALUES($values)";
		    $stmt	= $connection->prepare($insert);
		    $insertText	= debugPrepQuery($insert, $parms);
		    // insert the new record into the database
		    if ($stmt->execute($parms))
		    {		    	// successful insert
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$tagname result='$count'>$insertText</$tagname>\n";

				if ($debug)
				{
				    $warn	.= "<p>$insertText</p>\n";
				    $warn	.= "<p>count=$count</p>\n";
				}

				$this->changed	= array();	// reset
				$this->needInsert	= false;

				// log the update
				logSqlUpdate($insert,
						     $parms,
						     0,		// compound key
						     '',
						     $this->toJson(false));
				return $count;
		    }		    	// error performing query
		    else
		    {		    	// error inserting record
				throw new Exception("Concession::save:
							    '$insertText', " .
							    print_r($stmt->errorInfo(),true));
		    }	    		// error inserting record
		}		        	// create new record
		else
		{		        	// update existing record
		    $update		= "UPDATE Concessions SET ";
		    $trace		= "UPDATE Concessions SET ";
		    $set		= '';
		    $comma		= '';
		    $eparms		= array();
		    foreach($this->changed as $fld => $value)
		    {
				$set	.= $comma . "`$fld`=?";
				array_push($eparms, $value);
				$trace	.= $comma . "`$fld`=" . $connection->quote($value);
				$comma	= ', ';
		    }	        	// loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		// changes made
				// construct the where clause
				array_push($eparms,$this->row['domain']);
				array_push($eparms,$this->row['county']);
				array_push($eparms,$this->row['township']);
				array_push($eparms,$this->row['conid']);
				$where	= 'Domain=? AND County=? AND Township=? AND ConID=?';
				$trace	.= ' WHERE Domain=' . $connection->quote($this->row['domain']) . " AND County=" . $connection->quote($this->row['county']) . " AND Township=" . $connection->quote($this->row['township']) . " AND ConID=" . $connection->quote($this->row['conid']);

				// assemble the UPDATE command
				$update		.= $set . ' WHERE ' . $where;
				$stmt		= $connection->prepare($update);
				$updateText	= debugPrepQuery($update, $eparms);
				// perform the update
				if ($stmt->execute($eparms))
				{	    	// successful update
				    $count		= $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$count'>$updateText</$tagname>\n";
				    if ($debug)
						$warn	.= "<p>$updateText</p>\n" .

				    // log the update
				    logSqlUpdate($update,
							 $parms,
							 0,	// compound key
							 json_encode($this->former),
							 $this->toJson(false));
				    return	$count;
				}	    	// successful update
				else
				{	    	// error updating record
				    throw new Exception("Concession::save:
								'$updateText', " .
								print_r($stmt->errorInfo(),true));
				}	    	// error updating record
		    }		    	// changes made
		}		        	// update existing record
		return 0;
    }		// function Concession::save

    /********************************************************************
     *	function Concession::delete										*
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
		{	                	// have existing record
		    $sqlParms	= array($this->row['domain'],
							$this->row['county'], 
							$this->row['township'],
							$this->row['conid']);
		    $delete	= "DELETE FROM Concessions WHERE" .
						' Domain=? AND County=? AND Township=? AND ConID=?';
		    $stmt	= $connection->prepare($delete);
		    $deleteText	= debugPrepQuery($delete, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {
				 if ($debug)
				    $warn	.= "<p>Concession::delete: " . __LINE__ . 
								"$deleteText</p>\n";
		    }
		    else
		    {	            	// error updating record
				$msg	.= "$deleteText, " . print_r($stmt->errorInfo(),true) . 
						    "</p>\n";
				return false;
		    }	            	// error updating record

		    $count	= $stmt->rowCount();
		    if ($xml)
				print "<$xmltag count='$count'>$delete</$xmltag>\n";

		    // log the update
		    logSqlUpdate($delete,
					     $sqlParms,
					     0,		// compound key
					     '',
					     $this->toJson(false));

		    // update the memory copy of the record to reflect that it
		    // no longer represents a database record
		    $this->needInsert	= true;

		    return $count;	    // number of records deleted
		}		                // have key value

		// record never written to database
		return 0;	            // nothing to delete
    }		// function Concession::delete

}		// class Concession 
