<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  District.inc														*
 *																		*
 *  Definition of a class representing a census district        		*
 *  from a census.  This class provides									*
 *  access to the information in a row of the table Districts			*
 *																		*
 *  CREATE TABLE `Districts` (											*
 *		`D_Census`		VARCHAR(8) NOT NULL DEFAULT 'CA1881',			*
 *		`D_Id`			DECIMAL(4,1) NOT NULL,							*
 *		`D_Name`		VARCHAR(48) DEFAULT NULL,						*
 *		`D_Nom`			VARCHAR(48) DEFAULT NULL,						*
 *		`D_Province`	CHAR(2) DEFAULT NULL,							*
 *		`D_Population`	INT(11) NOT NULL DEFAULT 0,						*
 *		`D_Transcribed` INT(11) NOT NULL DEFAULT 0,						*
 *		PRIMARY KEY (`D_Census`,`D_Id`) )								*
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8 								*
 *																		*
 *  History:															*
 *		2014/09/19		created											*
 *		2014/09/23		add methods getPrev and getNext					*
 *						add method getSubDistricts						*
 *						add methods getSdCount and getFCount			*
 *						simulate fields 'd_sdcount' and 'd_fcount'		*
 *		2014/12/28		improve clarity of implementation of get		*
 *						improve clarity of implementation of set		*
 *						improve efficiency of getSubDistricts by		*
 *						saving the previously obtained value			*
 *						remove methods getSdCount and getFCount, the	*
 *						contents are still available through get		*
 *						with field names 'd_sdcount' and 'd_fcount'		*
 *						and these methods were unused					*
 *						redirect debugging output to $warn				*
 *		2015/03/17		method getDistricts returned null when limit	*
 *						not specified									*
 *						method getSubDistricts did not get counts from	*
 *						new interface									*
 *						class SubDistrict used without being included	*
 *		2015/06/02		use new global census table						*
 *		2015/06/05		demand parameter d_census for search parms		*
 *						index response from getDistricts by d_id		*
 *			            misspelled field name in WHERE expression	    *	
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/11/17		add method synchPopulation						*
 *		2016/01/20		add support in getDistricts for list of censuses*
 *						use Census class								*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2017/02/02		index response to getDistricts by district id	*
 *		2017/03/11		add method getJson								*
 *						accept parameter 'district' in getDistricts as	*
 *						synonym for 'd_id'.								*
 *		2017/07/20		require option to include sub-districts in XML	*
 *						and JSON output									*
 *		2017/08/03		use prepared statements to update and insert	*
 *		2017/10/29		complete move to prepared statements			*
 *						use RecordSet									*
 *		2017/11/24		use field name synonym table consistently		*
 *						correct error in method getNext					*
 *						move pseudo-fields d_sdcount and d_fcount		*
 *						to the extras array								*
 *		2017/12/23		support more pseudo-fields						*
 *						accept district id as array if single value		*
 *		2018/11/07      accept 'd_census' as instance of Census         *
 *		                for existing record                             *
 *		                add method getCensus                            *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/01/19      report constructor errors through message       *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc' ;
require_once __NAMESPACE__ . '/Census.inc' ;
require_once __NAMESPACE__ . '/SubDistrict.inc' ;

/************************************************************************
 *  class District														*
 *																		*
 *  Definition of a class recording information about a district		*
 *  from a census.  For Canada this is a federal electoral district		*
 ************************************************************************/

class District extends Record
{
    /********************************************************************
     *	District::$initRow												*
     *																	*
     *	Default initial row for a record in table Districts				*
     ********************************************************************/
    protected static $initRow	= array(
		'd_census'	        => 'CA1881',
		'd_id'		        => 0,
		'd_name'	        => '',
		'd_nom'		        => '',
		'd_province'	    => 'ON',
		'd_population'	    => 0,       // statistics
		'd_transcribed'	    => 0);      // statistics

    /********************************************************************
     *	District::$translate											*
     *																	*
     *	Definition of synonyms for field names							*
     ********************************************************************/
    protected static $translate	= array(
		'census'			=> 'd_census',
		'censusid'			=> 'd_census',
		'id'				=> 'd_id',
		'distid'			=> 'd_id',
		'district'			=> 'd_id',
		'name'				=> 'd_name',
		'nom'				=> 'd_nom',
		'prov'				=> 'd_province',
		'province'			=> 'd_province',
		'provcode'			=> 'd_province',
		'population'		=> 'd_population',
		'transcribed'		=> 'd_transcribed',
		'sdcount'			=> 'd_sdcount',
		'fcount'			=> 'd_fcount');

    /********************************************************************
     *	District::$defaultOrder											*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	= 'D_census,D_id';

    /********************************************************************
     *	District::$census												*
     *																	*
     *	Instance of Census for this District.							*
     ********************************************************************/
    protected	$census		= null;

    /********************************************************************
     *	District::$domain												*
     *																	*
     *	Instance of Domain for this District.							*
     ********************************************************************/
    protected	$domain		= null;

    /********************************************************************
     *	District::$subDistricts											*
     *																	*
     *	An array of instances of SubDistrict for this District.			*
     ********************************************************************/
    protected	$subDistricts	= null;

    /********************************************************************
     *	function District::__construct									*
     *																	*
     *  Construct an instance of District based upon the				*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameters				*
     *					identifying the specific row,					*
     *					or a complete row from the table				*
     *					In either case fields 'd_census" or 'sd_census' *
     *					may be either a string or an instance of        *
     *					class Census                                    *
     *																	*
     *	$district	= new District(array('census'	=> $censusId,	    *
     *								     'id'		=> $id));		    *
     *	$district	= new District($dbrow);	                            *
     *																	*
     *  Returns object containing a description of a district			*
     *																	*
     *	Throws Exception if unable to complete creating record			*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $msg;
		global $connection;

		if (!is_array($parms))
		    throw new Exception("District::__construct: " .
							"parameter is not an associative array");
		$needInsert	= false;

		$this->table	= 'Districts';

		// check for $parms as a complete existing row from the database
		$dbrow		= null;
		if (count($parms) == count(self::$initRow))
        {			            // expected size for complete row from database
		    $valid		= true;
		    foreach($parms as $field => $value)
		    {			        // loop through all fields
				switch(strtolower($field))
				{		        // act on specific field names
				    case 'd_census':
				    {
						if ($value instanceof Census)
						{		// object
						    $this->census	= $value;
						    $censusId		= $this->census->get('censusid');
						}		// object
						else
						{		// string
						    $censusId		= $value;
						    $cparms		    = array('censusid' => $censusId,
	        							    		'collective'	=> 0);
						    $this->census	= new Census($cparms);
						}		// string
						break;
				    }

				    case 'd_id'	:
				    {
						$distId		= $value;
						break;
				    }

				    case 'd_name':
				    case 'd_nom':
				    case 'd_province':
				    case 'd_population':
				    case 'd_transcribed':
				    {		    // valid field names
						break;
				    }		    // valid field names

				    default:
				    {		    // unexpected field name
						$valid		= false;
						break;
				    }		    // unexpected field name
				}		        // act on specific field names
		    }			        // loop through all fields

		    // if all valid field names, use row
		    if ($valid)
				$dbrow		= $parms;

		    // add sub-district statistics
		    $this->extras	= array('d_sdcount'	=> 0,
								'd_fcount'	=> 0);
		    $distQuery	    = 'SELECT ' .
						        'COUNT(sd_name) as `d_sdcount`, ' .
					    	    'SUM(sd_relframe > 0) AS d_fcount ' .
					    	'FROM SubDistricts ' .
					    	'WHERE SD_Census=:censusId AND SD_DistId=:distId';
		    $sqlParms   	= array('censusId'	=> $censusId, 
					    		    'distId'	=> $distId);
		    $queryText  	= debugPrepQuery($distQuery, $sqlParms);
		    $stmt	    	= $connection->prepare($distQuery);
		    if ($stmt->execute($sqlParms))
		    {			// successful query
				if ($debug)
				    $warn	.= "<p>District::__construct: " . __LINE__ .
							   " '$queryText'</p>\n";

				$row		= $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($row))
				{			// got statistics
				    $this->extras['d_sdcount']	= $row['d_sdcount'];
				    $this->extras['d_fcount']	= $row['d_fcount'];
				}			// got statistics
		    }			// successful query
		    else
		    {			// query failed
				$this->msg  .= "District::__construct: '$queryText' " .
							    print_r($stmt->errorInfo(),true);
		    }			// query failed
		}			// complete row from database

		// if need to create new row
		if (is_null($dbrow))
		{			                // parameters for creating row
		    // default values
		    $censusId		= self::$initRow['d_census'];
		    $distId	    	= self::$initRow['d_id'];

		    // process parameters that identify a district
		    foreach($parms as $field => $value)
		    {			            // process search parameters
				$fieldLc	= strtolower($field);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	= self::$translate[$fieldLc];
				switch($fieldLc)
				{		            // act on specific parameters
				    case 'd_census':
				    case 'sd_census':
				    {
						if ($value instanceof Census)
						{		    // object
						    $this->census	= $value;
						    $censusId		= $this->census->get('censusid');
						}		    // object
						else
						{		    // string
						    $censusId		= $value;
						    $parms		= array('censusid' => $censusId,
	        									'collective'	=> 0);
						    $this->census	= new Census($parms);
						}		    // string
						break;
				    }

				    case 'd_id':
				    case 'sd_distid':
				    {
						if (is_array($value))
						{
						    if (count($value) == 1)
                                $distId     = reset($value);
                            else
                                $this->msg  .= "District::__construct: " . __LINE__ . " Invalid district id as array. ";
                        }

                        if (is_numeric($value))
                            $distId		= $value;
                        else
                        if (is_string($value))
                        {
							if (preg_match("/^\d+(\.0|\.5|)$/", $value) == 1 &&
							    $value >= 1)
							{	    // syntax valid
							    $distId		= $value;
	                        }	    // syntax valid
							else
							    $this->msg  .="District::__construct: " . __LINE__ .
	                                " Invalid District ID value '$value'. ";
                        }
						break;
				    }               // district identifier

				}		            // act on specific parameters
		    }			        // process search parameters

		    // check if district id was provided
		    if ($distId == 0)
				$this->msg  .= "District::__construct: " . __LINE__ .
							    " Missing District Id value";

		    // construct the query to look for an existing record
		    // query the database for district information
		    $distQuery	= 'SELECT Districts.*, ' .
						    'COUNT(SubDistricts.sd_name) as `d_sdcount`, ' .
						    'SUM(SubDistricts.sd_relframe > 0) AS d_fcount ' .
						'FROM Districts ' .
						'LEFT JOIN SubDistricts ON SD_Census=D_Census AND SD_DistId=D_Id ' .
						'WHERE D_Census=:censusId AND D_Id=:distId ' .
						'GROUP BY D_Census, D_Id';
		    $sqlParms	= array('censusId'	=> $censusId, 
							'distId'	=> $distId);
		    $queryText		= debugPrepQuery($distQuery, $sqlParms);
		    $stmt		= $connection->prepare($distQuery);
		    if ($stmt->execute($sqlParms))
		    {			// successful query
				if ($debug)
				    $warn	.= "<p>District::__construct: " . __LINE__ .
							   " '$queryText'</p>\n";

				$result		= $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($result))
				{			// existing record
				    $this->extras['d_sdcount']	= $result['d_sdcount'];
				    $this->extras['d_fcount']	= $result['d_fcount'];
				    unset($result['d_sdcount']);
				    unset($result['d_fcount']);
				    $dbrow		= $result;
				    $needInsert		= false;
				}			// existing record
				else
				{			// create a new record
				    // set defaults
				    $dbrow		= self::$initRow;
				    $dbrow['d_census']	= $censusId;
				    $dbrow['d_id']	= $distId;
				    $dbrow['d_name']	= "District $distId";
				    $needInsert		= true;
				    $this->extras	= array('d_sdcount'	=> 0,
									'd_fcount'	=> 0);

				    // process parameters that modify record
				    foreach($parms as $field => $value)
				    {			// process modify parameters
						$fieldLc	= strtolower($field);
						if (array_key_exists($fieldLc, self::$translate))
						    $fieldLc	= self::$translate[$fieldLc];
						switch($fieldLc)
						{		// act on specific parameters
						    case 'd_name':
						    case 'd_nom':
						    case 'd_province':
						    {
							    $dbrow[$fieldLc]	= $value;
							    break;
						    }	// set new value

						}		// act on specific parameters
				    }		    // process modify parameters
				}			    // create new record
		    }			        // successful query
		    else
		    {			        // error performing query
				$this->msg      .= "District::__construct: '$queryText' " .
							    print_r($stmt->errorInfo(),true);
		    }			        // error performing query
		}			            // parameters for creating row

        if ($distId == 0)
            throw new Exception("district id not set");
		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Districts');
		$this->needInsert	= $needInsert;

		// apply any updates passed in the parameters
		foreach($parms as $field => $value)
		{		// check for other initialization
		    $fieldLc		= strtolower($field);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	= self::$translate[$fieldLc];
		    switch($fieldLc)
		    {		// act on specific parameters
				case 'd_id':
				case 'd_census':
				{
				    // identification fields already handled
				    break;
				}

				default:
				{
				    if (array_key_exists($field, $dbrow))
 			parent::set($field, $value);
				    break;
				}
		    }		// act on specific parameters
		}		// check for other initialization

		// synchronize with other tables
		if ($needInsert)
		    $this->synchPopulation();

		// diagnostic output if debug is set
        $this->dump('District Record constructed:');
        
        $msg        .= $this->msg;
    }		// function District::__construct

    /********************************************************************
     *	function District:get											*
     *																	*
     *	Obtain the current value of a field in the object.				*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to retrieve						*
     *																	*
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
		{			// act on specific field names
		    case 'd_id':
		    {			// actual fields in table
				$distId		= $this->row['d_id'];
				if ($distId == floor($distId))
				    return floor($distId);	// ensure integer
		    }			// actual fields in table

		    case 'd_census':
		    case 'd_name':
		    case 'd_nom':
		    case 'd_province':
		    case 'd_population':
		    case 'd_transcribed':
		    {			// actual fields in table
				return $this->row[$fieldLc];
		    }			// actual fields in table

		    case 'd_sdcount':	// statistics
		    case 'd_fcount':	// statistics
		    {			// actual fields in table
				return $this->extras[$fieldLc];
		    }			// actual fields in table

		    default:
		    {		// other fields
				return parent::get($fieldLc);
		    }		// other fields
		}			// act on specific field names
    }			// function District::get

    /********************************************************************
     *	function District:set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
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
		    case 'd_sdcount':
		    case 'd_fcount':
		    {		// stats fields
				$this->extras[$fieldLc]	= $value;
				break;
		    }		// stats fields

		    case 'd_census':
		    case 'd_id':
		    case 'd_name':
		    case 'd_nom':
		    case 'd_province':
		    case 'd_population':
		    case 'd_transcribed':
		    {		// no validation implemented yet
				return parent::set($fieldLc, $value);
		    }		// no validation implemented yet

		    default:
		    {		// invalid field name
				return parent::set($fieldLc, $value);
		    }		// invalid field name
		}		// switch on field name
    }			// function District::set

    /********************************************************************
     *	function District::save											*
     *																	*
     *  Save changes made to the District record into the				*
     *  database.														*
     *																	*
     *  Parameters:														*
     *	    $xml		If true diagnostic output is emitted in XML		*
     *					format. If a string use it as top tag name		*
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
		    $fldnames			= '';
		    $comma			= '';
		    $values			= '';
		    $sqlParms			= array();
		    foreach($this->row as $fld => $value)
		    {
				$shortFld		= substr($fld, 2);
				$fldnames		.= $comma . "`$fld`"; 
				$values			.= $comma . ":$shortFld";
				$sqlParms[$shortFld]	= $value;
				$comma			= ", ";
		    }		// loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	= "INSERT INTO Districts ($fldnames) VALUES($values)";
		    $insertText	= debugPrepQuery($insert, $sqlParms);

		    // insert the new record into the database
		    $stmt	= $connection->prepare($insert);
		    if ($stmt->execute($sqlParms))
		    {		// successful insert
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$tagname result='$count'>" .
							htmlentities($insertText,ENT_XML1) . 
						  "</$tagname>\n";
				if ($debug)
				    $warn	.= "<p>District::save: " . __LINE__ .
							   " '$insertText', count=$count</p>\n";

				$this->changed	= array();	// reset
				$this->needInsert	= false;

				// log the update
				logSqlUpdate($insert,
						     $sqlParms,
						     $count,		// compound key
						     '',
						     $this->toJson(false));
				return $count;
		    }		// successful insert
		    else
		    {		// error inserting record
				throw new Exception("District::save:
							    '$insertText', " .
							    print_r($stmt->errorInfo(),true));
		    }		// error inserting record
		}		// create new record
		else
		{		// update existing record
		    $update		= "UPDATE Districts SET ";
		    $set		= '';
		    $comma		= '';
		    $sqlParms		= array();
		    foreach($this->changed as $fld => $value)
		    {
				$shortFld		= substr($fld, 2);
				$set			.= $comma . "`$fld`=:$shortFld" ;
				$sqlParms[$shortFld]	= $value;
				$comma			= ', ';
		    }		// loop through all fields in record

		    if (strlen($set) > 0)
		    {		// changes made
		        $this->changed	= array();	// reset

				// construct the where clause
				$update	.= $set . ' WHERE ' . 
								'D_Census=:ocensusId AND ' .
								'D_Id=:odistId';
				
				if ($this->former)
				{
				    $sqlParms['ocensusId']	= $this->former['d_census'];
				    $sqlParms['odistId']	= $this->former['d_id'];
				}
				else
				{
				    $sqlParms['ocensusId']	= $this->row['d_census'];
				    $sqlParms['odistId']	= $this->row['d_id'];
				}
				$updateText		= debugPrepQuery($update, $sqlParms);

				// perform the update
				$stmt			= $connection->prepare($update);
				if ($stmt->execute($sqlParms))
				{		// successful update
				    $count		= $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$count'>" .
							htmlentities($updateText,ENT_XML1) . 
						      "</$tagname>\n";

				    if ($debug)
						$warn	.= "<p>District::save: " . __LINE__ .
								" sql='$updateText'</p>\n";

				    // log the update
				    logSqlUpdate($update,
							 $sqlParms,
							 $count,	// compound key
							 json_encode($this->former),
							 json_encode($this->row));
				    return $count;
				}		// successful update
				else
				{		// error updating record
				    throw new Exception("District::save: " .
								"'$updateText', " .
								print_r($stmt->errorInfo(),true));
				}		// error updating record
		    }		// changes made
		}		// update existing record
    }			// function District::save

    /********************************************************************
     *	function District::delete										*
     *																	*
     *  Delete the database record associated with this instance.		*
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
    function delete($xml = false)
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
		    $sqlParms	= array();
		    if ($this->former)
		    {
				$sqlParms['ocensusId']	= $this->former['d_census'];
				$sqlParms['odistId']	= $this->former['d_id'];
		    }
		    else
		    {
				$sqlParms['ocensusId']	= $this->row['d_census'];
				$sqlParms['odistId']	= $this->row['d_id'];
		    }
		    $delete	= "DELETE FROM Districts WHERE" .
							' D_Census=:ocensusId AND D_Id=:odistId';
		    $deleteText	= debugPrepQuery($delete, $sqlParms);

		    $stmt	= $connection->prepare($delete);
		    if ($stmt->execute($sqlParms))
		    {		// successful delete
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

				if ($debug)
				    print '<p>District::delete: ' . __LINE__ .
							" '$deleteText', count='$count'</p>\n";

				// log the update
				logSqlUpdate($delete,
						     $sqlParms,
						     $count,		// compound key
						     $this->toJson(false),
						     '');

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// successful delete
		    else
		    {		// error updating record
				error_log("District.inc: " . __LINE__ . " '$deleteText', " .
						print_r($stmt->errorInfo(),true) . "\n");
				return false;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }			// function District::delete

    /********************************************************************
     *	function District::toXml										*
     *																	*
     *  Emit the contents of the object as XML.							*
     *																	*
     *	Parameters:														*
     *	    $top		tag name as a string for the tag enclosing the	*
     *					values.											*
     *					If null, do not enclose the individual values	*
     *					inside a root node.								*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 3rd parameter						*
     ********************************************************************/
    const	TOXML_INCLUDE_SUBDISTRICTS	= 1;

    function toXml($top,
				   $print	= true,
				   $options	= 0)
    {
		// ensure that there is a label to use for the top element
		if (!is_string($top) || strlen($top) == 0)
		    $top	= 'District';
		if (is_null($top))
		    $retval	= '';
		else
		    $retval	= "    <$top>\n";

		foreach($this->row as $key => $value)
		{
		    if (substr($key, 0, 3) == 'd_')
				$key	= substr($key, 2);
		    $retval	.= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
		}

		// get list of subdistricts
		if ($options & self::TOJSON_INCLUDE_SUBDISTRICTS)
		{
		    $getparms	= array('sd_census'	=> $this->census,
							    'sd_distid'	=> $this);
		    $subdistricts	= new SubDistrictSet($getparms);
		    $retval	.= "<subdistricts count='" . $getparms['count'] . "'>\n";
		    foreach($subdistricts as $subdistrict)
		    {			// loop through subdistricts
				$retval	.= $subdistrict->toXml('subdistrict', false);
		    }			// loop through subdistricts
		    $retval	.= "</subdistricts>\n";
		}			// include sub-districts
		if ($top !== null)
		    $retval	.= "    </$top>\n";
    }			// function District::toXml

    /********************************************************************
     *	function District::toJson										*
     *																	*
     *  Emit the contents of the object as JSON.						*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	0 only emit object								*
     *					1 also emit subdistricts						*
     *																	*
     *	Returns:														*
     *	    JSON representation of the object							*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 2nd parameter						*
     ********************************************************************/
    const	TOJSON_INCLUDE_SUBDISTRICTS	= 1;

    function toJson($print	= true,
				    $options	= 0)
    {
		$retval		= '';
		$comma		= "{\n";

		foreach($this->row as $key => $value)
		{
		    if (substr($key, 0, 2) == 'd_')
				$key	= substr($key, 2);
		    $retval	.= $comma . "\t\"$key\":\t" . 
						   json_encode($value);
		    $comma	= ",\n";
		}

		// get list of subdistricts
		if ($options & self::TOJSON_INCLUDE_SUBDISTRICTS)
		{
		    $getparms		= array('sd_census'	=> $this->census,
								    'sd_distid'	=> $this);
		    $subdistricts	= new SubDistrictSet($getparms);
		    if (count($subdistricts) > 0)
		    {			// display subdistricts
				foreach($subdistricts as $subdistrict)
				{		// loop through subdistricts
				    $sd_id	= $subdistrict->get('sd_id');
				    if ($subdistrict->get('sd_div') != '')
						$sd_id	.= '-' . $subdistrict->get('sd_div');
				    $retval	.= ",\n" . json_encode($sd_id) . ":\n";
				    $retval	.= $subdistrict->toJson(false);
				}		// loop through subdistricts
		    }			// display subdistricts
		}			// include sub-districts

		// close off the JSON document
		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";

		if ($print)
		    print $retval;
		return $retval;
    }			// function District::toJson

    /********************************************************************
     *	function District::__toString									*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return "District{d_id=" . $this->row['d_id'] .
						    ", d_name='" . $this->row['d_name'] .
						    "'}";
    }		// function District::__toString

    /********************************************************************
     *	function District::getPrev										*
     *																	*
     *  Get the instance of District that precedes this instance in		*
     *  order by census identifier and district number.					*
     *																	*
     *	Returns:														*
     *	    Instance of District or null								*
     ********************************************************************/
    function getPrev()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$sqlParms			        = array();
		$census				        = $this->row['d_census'];
		$sqlParms['census']		    = $census;
		$sqlParms['domain']		    = substr($census, 0, 2);
		$sqlParms['censusYear']		= substr($census, 2);
		$sqlParms['id']		    	= $this->row['d_id'];

		// the ORDER BY clause sorts first by census year and then by country
		$query		= 'SELECT * FROM Districts ' . 
						    'WHERE (D_Census=:census AND D_Id<:id) OR ' .
		'(SUBSTR(D_Census,3)=:censusYear AND LEFT(D_Census,2)<:domain) OR '.
							'SUBSTR(D_Census,3)<:censusYear ' .
		'ORDER BY SUBSTR(D_Census,3) DESC,LEFT(D_Census,2) DESC, D_Id DESC ' .
						    "LIMIT 1";
		$queryText	= debugPrepQuery($query, $sqlParms);
		$stmt		= $connection->prepare($query);
		if ($stmt->execute($sqlParms))
		{		// successful query
		    $row		= $stmt->fetch(PDO::FETCH_ASSOC);
		    if ($debug)
				$warn	.= "<p>District::getPrev: " . __LINE__ . 
						   " '$queryText'</p>\n";
		    if (is_array($row))
		    {		// have preceding section
				return	new District($row);
		    }		// have preceding section
		    else
				return null;
		}		// successful query
		else
		{		// invalid query
		    error_log("District::getPrev: " . __LINE__ . " '$queryText'\n");
		    if (strlen($queryText) > 40)
				$queryText	= substr($queryText, 0, 37) . '...';
		    throw("District::getPrev: " . $queryText . ',  ' .
				  print_r($stmt->errorInfo(),true));
		}		// invalid query
    }			// function District::getPrev

    /********************************************************************
     *	function District::getNext										*
     *																	*
     *  Get the instance of District that follows this instance in		*
     *  order by census identifier and district number.					*
     *																	*
     *	Returns:														*
     *	    Instance of District										*
     ********************************************************************/
    function getNext()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$sqlParms			= array();
		$census				= $this->row['d_census'];
		$sqlParms['census']		= $census;
		$sqlParms['domain']		= substr($census, 0, 2);
		$sqlParms['censusYear']		= substr($census, 2);
		$sqlParms['id']			= $this->row['d_id'];
		// the ORDER BY clause sorts first by census year and then by country
		$query		= 'SELECT * FROM Districts ' . 
						    'WHERE (D_Census=:census AND D_Id>:id) OR ' .
		'(SUBSTR(D_Census,3)=:censusYear AND LEFT(D_Census,2)>:domain) OR '.
							'SUBSTR(D_Census,3)>:censusYear ' .
		'ORDER BY SUBSTR(D_Census,3) ASC,LEFT(D_Census,2) ASC, D_Id ASC ' .
						    "LIMIT 1";
		$queryText	= debugPrepQuery($query, $sqlParms);
		$stmt		= $connection->prepare($query);
		if ($stmt->execute($sqlParms))
		{		// successful query
		    $row		= $stmt->fetch(PDO::FETCH_ASSOC);
		    if ($debug)
				$warn	.= "<p>District::getNext: " . __LINE__ . 
						   " '$queryText'</p>\n";
		    if (is_array($row))
		    {		// have following section
				return	new District($row);
		    }		// have following section
		    else
				return null;
		}		// successful query
		else
		{		// query failed
		    error_log("District::getPrev: " . __LINE__ . " '$queryText'\n");
		    if (strlen($queryText) > 40)
				$queryText	= substr($queryText, 0, 37) . '...';
		    throw("District::getNext: " . $queryText . ',  ' .
				  print_r($stmt->errorInfo(),true));
		}		// query failed
    }			// function District::getNext

    /********************************************************************
     *	function District::getCensus    								*
     *																	*
     *  Get the associated instance of class Census.                    *
     *																	*
     *	Returns:														*
     *	    instance of class Census									*
     ********************************************************************/
    function getCensus()
    {
        return $this->census;
    }           // function getCensus

    /********************************************************************
     *	function District::getDomain    								*
     *																	*
     *  Get the associated instance of class Domain.                    *
     *																	*
     *	Returns:														*
     *	    instance of class Domain									*
     ********************************************************************/
    function getDomain($lang = 'en')
    {
        if (is_null($this->domain))
        {
            $census             = $this->getCensus();
            $domainId           = $census['cc'] . $this->row['d_province'];
            $this->domain       = new Domain(array('domain' => $domainId,
                                                   'lang'   => $lang));
        }
        return $this->domain;
    }           // function getDomain

    /********************************************************************
     *	function District::getSubDistricts								*
     *																	*
     *  Get the set of sub-districts that are defined for this instance	*
     *  of District														*
     *																	*
     *	Returns:														*
     *	    instance of SubDistrictSet									*
     ********************************************************************/
    function getSubDistricts()
    {
		global $debug;
		global $warn;

		if (is_null($this->subDistricts))
		{			// list not yet obtained
		    $census		    = $this->census;
		    $id			    = $this->row['d_id'];
		    $getParms		= array('sd_census'	=> $census,
							    	'sd_distid'	=> $this);
		    $this->subDistricts	= new SubDistrictSet($getParms);
		    if ($debug)
				$warn	.= "<p>District::getSubDistricts: " . __LINE__ .
							" \$getParms=array(sd_census => '" .
							$census->get('censusid') .
							"', sd_distid => " . $this->get('id') .
							")</p>\n";
		}			// list not yet obtained

		return $this->subDistricts;
    }			// function District::getSubDistricts

    /********************************************************************
     *	function District::getStatistics								*
     *																	*
     *  Get the statistics for the SubDistricts in this District.       *
     *																	*
     *	Returns:														*
     *	    array of associative arrays                                 *
	 *    		ir		        sequential row number			        *
     *          sd_distid		district id			                    * 
     *          sd_id		    sub-district id			                * 
     *          sd_div		    division name			                *
     *			sd_name		    sub-district name			            * 
     *			sd_population	population			                    *
	 *			namecount		number of rows with name filled in	    * 
	 *			agecount		number of rows with age filled in	    *
	 *			idircount       number of rows linked to family tree    *
     ********************************************************************/
    function getStatistics()
    {
		global $debug;
		global $warn;
        global $msg;
        global $connection;

        $censusId				    = $this->row['d_census'];
        $distId			            = $this->row['d_id'];
        $domain		                = substr($censusId, 0, strlen($censusId) - 4);
        $censusYear		            = substr($censusId, -4);

	    // execute a query that includes divisions with no transcription
	    if ($censusYear > 1901 || $censusYear < 1867)
			$qryAllOrder	= "LPAD(SD_Id,3,'00'), LPAD(SD_Div,3,'00')";
	    else
			$qryAllOrder	= "SD_Id, LPAD(SD_Div,3,'00')";
	
        if ($censusYear < 1867)
        {
            $province       = substr($domain, -2);
			$qryAll	        = "SELECT  (@row_number:=@row_number + 1) AS ir,
                                SD_DistID, SD_ID, SD_Div," .
					            "SD_Name, SD_Population," .
					            "(SELECT SUM(GivenNames != '') " . 
							    "FROM Census$censusYear WHERE " .
								    "Province=:province AND " .	
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    " AS NameCount," . 
							    "(SELECT SUM(Age != '') " .
							    "FROM Census$censusYear WHERE " .
								    "Province=:province AND " .	
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    "AS AgeCount," .
							    "(SELECT SUM(IDIR != 0) " .
							    "FROM Census$censusYear WHERE " .
								    "Province=:province AND " .	
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    "AS IDIRCount " .
							    "FROM SubDistricts " .
							    "WHERE SD_Census=:censusId AND SD_DistId=:distId " .
                                "ORDER BY $qryAllOrder";
            $sqlParms	    = array('censusId'	=> $censusId,
                                    'province'  => $province,
                                    'distId'	=> $distId);
        }
        else
        {
            $qryAll	        = "SELECT (@row_number:=@row_number + 1) AS ir,
                                SD_DistID, SD_ID, SD_Div," .
							    "SD_Name, SD_Population," .
							    "(SELECT SUM(GivenNames != '') " . 
							    "FROM Census$censusYear WHERE " .
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    " AS NameCount," . 
							    "(SELECT SUM(Age != '') " .
							    "FROM Census$censusYear WHERE " .
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    "AS AgeCount," .
							    "(SELECT SUM(IDIR != 0) " .
							    "FROM Census$censusYear WHERE " .
								    "District=SD_DistId AND " .
								    "SubDistrict=SD_Id AND Division=SD_Div) " .
								    "AS IDIRCount " .
							    "FROM SubDistricts " .
							    "WHERE SD_Census=:censusId AND SD_DistId=:distId " .
							    "ORDER BY $qryAllOrder";
	        $sqlParms	    = array('censusId'	=> $censusId,
                                    'distId'	=> $distId);
        }
        $connection->query('SET @row_number = 0');
	    $stmt	        = $connection->prepare($qryAll);
	    $qryAllText	    = debugPrepQuery($qryAll, $sqlParms);
	    if ($stmt->execute($sqlParms))
	    {		    // successful query
			if ($debug)
			    $warn	.= "<p>District::getStatistics: " . __LINE__ .
					            " $qryAllText</p>\n";
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
	    }		    // successful query
	    else
	    {		    // error on request
            $msg	    .= "'$qryAllText': " .
                            print_r($stmt->errorInfo(), true);
            return false;
	    }		    // error on request

    }       // function getStatistics

    /********************************************************************
     *	function District::synchPopulation								*
     *																	*
     *  Update the fields d_population and d_transcribed to match the	*
     *  information from the table of SubDistricts and the Census.		*
     *	This should be called by any script that updates the population	*
     *	field in an instance of SubDistrict, or the GivenNames field	*
     *	in any census table.											*
     *  Note that it is necessary to save the instance of District		*
     *	to update the database.											*
     ********************************************************************/
    function synchPopulation()
    {
		global $debug;
		global $warn;
		global $connection;

		$sqlParms		        = array();
		$censusId		        = $this->row['d_census'];
        $censusYear		        = substr($censusId, 2);
        if (strlen($censusYear) == 4 && ctype_digit($censusYear))
        {
			$id			        = $this->row['d_id'];
			$sqlParms['id']		= $id;
	
			if ($censusYear < 1867 && $censusYear > 1833)
			{
			    $sqlParms['prov']	= $this->row['d_province'];
			    $doneCmd	    = "SELECT SUM(GivenNames != '') as done " .
							        "FROM Census$censusYear " .
							        "WHERE District=:id AND Province=:prov";
			}
			else
			    $doneCmd	    = "SELECT SUM(GivenNames != '') as done " .
							        "FROM Census$censusYear " .
							        "WHERE District=:id";
			$doneCmdText	    = debugPrepQuery($doneCmd, $sqlParms);
			$stmt		        = $connection->prepare($doneCmd);
			if ($stmt->execute($sqlParms))
			{			// success
			    $row		    = $stmt->fetch(PDO::FETCH_ASSOC);
			    if ($debug)
					$warn	.= "<p>District::synchPopulation: " . __LINE__ .
								" '$doneCmdText'</p>\n";
			    if (is_array($row) && $row['done'] > 0)
					$this->set('d_transcribed', $row['done']);
	
			    $popCmd		= "SELECT SUM(SD_Population) as pop " .
								"FROM SubDistricts " .
								"WHERE SD_Census='$censusId' AND SD_DistId=$id";
			    $sqlParms		= array('censusId'	=> $censusId,
									'id'		=> $this->row['d_id']);
			    $popCmdText		= debugPrepQuery($popCmd, $sqlParms);
			    $stmt		    = $connection->prepare($popCmd);
			    if ($stmt->execute($sqlParms))
			    {
					if ($debug)
					    $warn	.= "<p>District::synchPopulation: " . __LINE__ .
						    		" '$popCmdText'</p>\n";
					$row		= $stmt->fetch(PDO::FETCH_ASSOC);
					if ($row['pop'] > 0)
					    $this->set('d_population', $row['pop']);
			    }
			    else
			    {
					error_log("District::synchPopulation: $popCmdText, " .
								print_r($stmt->errorInfo(),true) . "\n");
			    }
			}			// success
			else
			{			// failure
			    error_log("District::synchPopulation: $doneCmdText, " .
								print_r($stmt->errorInfo(),true) . "\n");
	        }			// failure
        }
        else
        {
            error_log("District::synchPopulation: censusId='$censusId'\n");
        }
    }		// function District::synchPopulation

}		// class District 
