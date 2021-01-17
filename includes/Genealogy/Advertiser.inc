<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Advertiser.inc														*
 *																		*
 *  Definition of a class representing the collection of information    *
 *  for a particular advertiser.                                        *
 *  This class provides access to the information in a row of the		*
 *  table Advertisers.													*
 *																		*
 *  Record Structure Reference											*
 *		CREATE TABLE Advertisers (		                                *
 *		AdName		VARCHAR(255),		        -- file name		    *
 *		PRIMARY KEY (AdName),		                                    *
 *		AdEmail		VARCHAR(255),		        -- email address		*
 *		Count01		INT(10) NOT NULL DEFAULT 0,	-- count for January	*
 *		Count02		INT(10) NOT NULL DEFAULT 0,	-- count for February	*
 *		Count03		INT(10) NOT NULL DEFAULT 0,	-- count for March		*
 *		Count04		INT(10) NOT NULL DEFAULT 0,	-- count for April		*
 *		Count05		INT(10) NOT NULL DEFAULT 0,	-- count for May		*
 *		Count06		INT(10) NOT NULL DEFAULT 0,	-- count for June		*
 *		Count07		INT(10) NOT NULL DEFAULT 0,	-- count for July		*
 *		Count08		INT(10) NOT NULL DEFAULT 0,	-- count for August		*
 *		Count09		INT(10) NOT NULL DEFAULT 0,	-- count for September	*
 *		Count10		INT(10) NOT NULL DEFAULT 0,	-- count for October	*
 *		Count11		INT(10) NOT NULL DEFAULT 0,	-- count for November	*
 *		Count12		INT(10) NOT NULL DEFAULT 0,	-- count for December   *
 *		Row         INT(10) DEFAULT NULL)       -- numeric id           *
 *																		*
 *  History:															*
 *		2019/12/20      created                                         *
 *		2019/12/23      add column Row                                  *
 *		2020/01/13      fix change adname column                        *
 *		2020/12/02      protect against XSS                             *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Advertiser													*
 *																		*
 *  Definition of a class implementing behavior for advertising         *
 *  access (click) statistics.                                          *
 *																		*
 ************************************************************************/

class Advertiser extends Record
{

    /********************************************************************
     *	Advertiser::$initRow											*
     *																	*
     *	static array containing default values for all fields in a		*
     *	new record														*
     ********************************************************************/
    protected static	$initRow	= array(
            	'adname'            => '',
            	'ademail'           => '',
				'count01'			=> 0,
				'count02'			=> 0,
				'count03'			=> 0,
				'count04'			=> 0,
				'count05'			=> 0,
				'count06'			=> 0,
				'count07'			=> 0,
				'count08'			=> 0,
				'count09'			=> 0,
				'count10'			=> 0,
				'count11'			=> 0,
                'count12'			=> 0,
                'row'               => null);

    /********************************************************************
     *	Advertiser::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'Advertisers',
                'name'      		=> 'Advertisers',
                'prime'	    		=> 'adname',
	            'srvmg'     		=> false,
	            'fldcount'			=> 13,
                'order'     		=> 'AdName',
                'classname' 		=> 'Advertiser');

    /********************************************************************
     *	Advertiser::$translate											*
     *																	*
     *	array containing synonyms for field names						*
     ********************************************************************/
    protected static	$translate	= array(
				'name'		=> 'adname',
				'email'		=> 'ademail');

    /********************************************************************
     *	function Advertiser::__construct								*
     *																	*
     *  Construct an instance of an advertising statistic record from   *
     *  table Advertisers.			                                    *
     *																	*
     *  Input:															*
     *		$id		    a database record presented as an array		    *
     *					or an associative array of search parameters	*
     *																	*
     *  Returns object containing record.						        *
     ********************************************************************/
    function __construct($id)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$needInsert	            = false;

		$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

		if (is_array($id))
        {		                    // array
            $parms              = $id;
		}		                    // array
		else
		{		                    // unexpected parameter type
		    $this->msg          .= "Advertiser::__construct: called with " .
                                        gettype($id) . ". ";
            $parms              = array();
		}		                    // unexpected parameter type

		// report parameter validation errors
		if (count($parms) >= count(Advertiser::$initRow))
		{			                // complete row from database
		    $dbrow		        = $parms;
            $where		        = '';
            if (!array_key_exists('adname', $dbrow))
            {                       // row missing primary key
                $this->msg      .= "Advertiser::__construct: invalid row ";
                $comma          = 'array(';
                foreach($dbrow as $key => $value)
                {
                    $this->msg  .= "$comma'$key'=>" .
                                    htmlspecialchars($value);
                    $comma      = ',';
                }
                $this->msg      .= "). ";
                $dbrow          = Advertiser::$initRow;
            }                       // row missing primary key
		}			                // complete row from database
        else
        {                           // process associative array parameter
			$where			    = '';
			$sqlParms		    = array();
            $and			    = 'WHERE ';
			foreach($parms as $fieldname => $value)
			{		        	    // loop through search parameters
			    $fieldNameLc	= strtolower($fieldname);
			    switch($fieldNameLc)
			    {
					case 'adname':
					{
					    if (is_string($value))
	                    {
							$adname		        = $value;
					        $where		        .= $and . "`AdName`=:adname";
					        $sqlParms['adname']	= $adname;
					        break 2;
	                    }
					    else
                        {
                            $value              = htmlspecialchars($value);
                            $this->msg	        .= "Advertiser::__construct: " .
                                "`AdName` value '$value' invalid. ";
							$adname	            = '';
					    }
					    break;
					}

					case 'row':
					{
					    if (is_int($value) || ctype_digit($value))
	                    {
                            $where		        .= $and . "`Row`>=:row";
					        $sqlParms['row']	= $value;
					        break 2;
	                    }
					    else
                        {
                            if (is_null($value))
                                $value          = 'NULL';
                            else
                                $value          = htmlspecialchars($value);
                            $this->msg	        .= "Advertiser::__construct: " .
                                "`Row` value '$value' invalid. ";
					    }
					    break;
					}

			    }			        // act on specific field names
			}		        	    // loop through search parameters
	
			if (strlen($where) > 0)
			{	// obtain the first instance that matches search parameters
			    $query		= "SELECT * FROM Advertisers $where LIMIT 1";
	
			    // query the database
			    $stmt		            = $connection->prepare($query);
				$queryText	            = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
			    if ($stmt->execute($sqlParms))
			    {			// successful query
					$row		        = $stmt->fetch(PDO::FETCH_ASSOC);
					if ($debug)
					    $warn	        .= "<p>Advertiser: " . __LINE__ .
										      "'$queryText'</p>\n";
	
					if (is_array($row))
					{		        // found advertising statistic record
					    $dbrow		    = $row;
					    $needInsert		= false;
					}		        // found advertising statistic record
                    else
                    if (isset($sqlParms['row']))
                    {               // wrap around to first
			            $query	= "SELECT * FROM Advertisers WHERE Row>0 LIMIT 1";
					    $stmt		    = $connection->prepare($query);
						$queryText	    = $query;
		                $this->lastSqlCmd= $queryText;
					    if ($stmt->execute($sqlParms))
					    {			// successful query
							$row		= $stmt->fetch(PDO::FETCH_ASSOC);
							if ($debug)
							    $warn	.= "<p>Advertiser: " . __LINE__ .
												      "'$queryText'</p>\n";
			
							if (is_array($row))
							{		// found advertising statistic record
        					    $dbrow		= $row;
		        			    $needInsert	= false;
				        	}		// found advertising statistic record
		                    else
							{		// create new record
							    $dbrow		        = Advertiser::$initRow;
							    foreach($parms as $fieldname => $value)
							    {	// apply updates
									$dbrow[strtolower($fieldname)]	= $value;
							    }	// apply updates
							    $needInsert	= true;
							}		// create new record
					    }			// successful query
					    else
					    {			// query failed
							$this->msg  .= "Advertiser::__construct: '$queryText', " .
								print_r($stmt->errorInfo(), true) .". ";
						    $dbrow		        = Advertiser::$initRow;
					    }		    // error performing query
                    }               // wrap around to first
                    else
					{		        // create new record
					    $dbrow		        = Advertiser::$initRow;
					    foreach($parms as $fieldname => $value)
					    {		    // apply updates
							$dbrow[strtolower($fieldname)]	= $value;
					    }		    // apply updates
					    $needInsert	        = true;
					}		        // create new record
			    }			        // successful query
			    else
			    {			        // query failed
					$this->msg  .= "Advertiser::__construct: '$queryText', " .
										print_r($stmt->errorInfo(), true) .". ";
				    $dbrow		        = Advertiser::$initRow;
			    }		            // error performing query
            }		                // query using search parms
            else
            {
				$this->msg .= "Advertiser::__construct: no selection parameters. ";
				$dbrow		        = Advertiser::$initRow;
            }
        }                           // process associative array parameter

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Advertisers');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('Advertiser Record constructed:');

        $msg    .= $this->msg;
    }		// Advertiser::__construct

    /********************************************************************
     *	function Advertiser::getName									*
     *																	*
     *	Get the name of the advertising statistic.						*
     *																	*
     *	Input:															*
     *		optional translate table for localization					*
     *																	*
     *	Returns:														*
     *	    String										                *
     ********************************************************************/
    function getName($options	= null)
    {
		return $this->row['adname'];
    }		// Advertiser::getName

    /********************************************************************
     *	function Advertiser::set										*
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
     *		previous value of the field								    *
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
        global      $debug;
        global      $warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'count01':
		    case 'count02':
		    case 'count03':
		    case 'count04':
		    case 'count05':
		    case 'count06':
		    case 'count07':
		    case 'count08':
		    case 'count09':
		    case 'count10':
		    case 'count11':
		    case 'count12':
            {       // counters
                // you can only reset
                if ($value == 0)    
                    return parent::set($fieldLc, $value);
                else
                {
                    $warn       .= "<p>Advertisers::set: invalid value $value for field \"$field\".</p>\n";
                    return $this->row[$fieldLc];
                }
		    }		// counters

		    case 'adname':
		    {		// associated advertiser name
				return parent::set($fieldLc, $value);
            }		// associated advertiser name

            default:
            {
				return parent::set($fieldLc, $value);
            }

		}		    // switch on field name
    }		// Advertiser::set

    /********************************************************************
     *	function Advertiser::increment									*
     *																	*
     *  Increment an access (click) counter.	                        *
     *																	*
     *  Parameters:														*
     *	    $month      month number (Jan = 1)                          *
     *																	*
     *	Returns:														*
     *	    1       the database was updated                            *
     *	    false   there was an error                                  *
     ********************************************************************/
    function increment($month)
    {
		global	$debug;
        global	$warn;
        global	$msg;
		global	$connection;

        $month              = intval($month);
        if ($month < 0 || $month > 12)
            return false;
        if ($month < 10)
            $month          = '0' . $month;
		if ($this->former)
            $adname		    = $this->former['adname'];
        else
            $adname         = $this->row['adname'];
        if ($adname == '' && $month == '02')
        {
            $getrow		= "SET @maxrow =(SELECT COALESCE(MAX(Row), 0) + 1 FROM Advertisers)";
            if ($connection->query($getrow))
            {
                if ($debug)
                    $warn   .= "<p>$getrow</p>\n";
            }
            else
            {
                $errorInfo	=  print_r($connection->errorInfo(),true);
                $warn	.= "<p>Advertiser::save: " . __LINE__ .
                                " command='$getrow'," .
                                " errorInfo=$errorInfo<p>\n";
            }
            $update		    = "UPDATE Advertisers " .
                              "SET Count$month=MOD(Count$month+1,@maxrow) " .
                              "WHERE AdName=:prime";
        }
        else
            $update		    = "UPDATE Advertisers " .
                                        "SET Count$month=Count$month+1 " .
								        "WHERE AdName=:prime";
		$parms['prime']	    = $adname;

		$stmt		        = $connection->prepare($update);
		$updateText	        = debugPrepQuery($update, $parms);
        $this->lastSqlCmd   = $updateText;
		if ($stmt->execute($parms))
		{		        // successful update
		    $count	        = $stmt->rowCount();	// changed rows
		    if ($debug)
				$warn	    .= "<p>$updateText</p>\n";

            // log the update
            try {
                $this->log($update, $parms);
            } catch (Exception $e) {
                $msg    .= "<p>Advertiser::increment: failed for adname='$adname'</p>\n";
                print $this->dump('increment');
            }
		}		        // successful update
		else
		{		// error updating record
            $updateText          = htmlspecialchars($updateText);
		    $this->msg	    .= "Advertiser::new: '$updateText': error=" .
                print_r($stmt->errorInfo(),true) . ". ";
            $warn           .= $this->msg;
		    $count          = false;
		}		// error updating record
        return $count;
    }           // function Advertiser::increment

    /********************************************************************
     *	function Advertiser::save										*
     *																	*
     *  Save changes made to the Advertiser record into the				*
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
    function save($xml = false)
    {
        global	$debug;
        global	$warn;
        global	$msg;
        global	$userid;
        global	$connection;
        global	$familyTreeCookie;

        $count                  = 0;

        // determine top level tag name for XML output
        $indent        		    = '';
        if (is_string($xml))
        {			    // string tag name
            $matches   		    = array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent		    = $matches[1];
                $xml   		    = $matches[2];
            }
            $tagname		    = $xml;
            $xml	   		    = true;
        }			    // string tag name
        else
        if ($xml)
        {			    // boolean true
            $indent             = "    ";
            $tagname		    = 'personsave';
        }			    // boolean true

        if ($this->needInsert)
        {			    // create new record
            $fldnames		    = '';
            $values             = '';
            $sqlParms		    = array();
            $comma              = '';
            $adname             = '';
            foreach($this->row as $field => $value)
            {
                switch($field)
                {		    // act on field name
                    case 'adname':
                    {		// primary key
                        $adname                 = $value;
                        $fldnames		        .= "$comma`$field`";
                        $values			        .= "$comma:$field";
                        $sqlParms[$field]		= $value;
                        break;
                    }		// primary key

                    case 'row':
                    {       // row
                        $fldnames		        .= "$comma`Row`";
                        if (strlen($adname) > 0)
                            $values		        .= "{$comma}@nextrow";
                        else
                            $values             .= "{$comma}NULL";
                        break;
                    }		// row

                    default:
                    {		// all other fields
                        $fldnames		        .= "$comma`$field`";
                        $values			        .= "$comma:$field";
                        $sqlParms[$field]		= $value;
                        break;
                    }		// all other fields
                }		    // switch on field name
                $comma                          = ',';
            }			    // loop through all fields in record

            $this->changed		= array();	// reset

            // construct the SQL INSERT command
            if (strlen($adname) > 0)
            {
                $getrow		= "SET @nextrow =(SELECT COALESCE(MAX(Row), 0) + 1 FROM Advertisers)";
                if ($connection->query($getrow))
                {
                    if ($debug)
                        $warn   .= "<p>$getrow</p>\n";
                }
                else
                {
                    $errorInfo	=  print_r($connection->errorInfo(),true);
                    $warn	.= "<p>Advertiser::save: " . __LINE__ .
                                " command='$getrow'," .
                                " errorInfo=$errorInfo<p>\n";
                }
            }
            $insert		= 'INSERT INTO Advertisers ' .
                                "($fldnames) " .
                                "VALUES($values)";

            // insert the new record into the database
            $stmt		            = $connection->prepare($insert);
            $insertText		        = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd       = $insertText;
            if ($stmt->execute($sqlParms))
            {			        // success
                $count		        = $stmt->rowCount();
                if ($xml)
                {
                    print "$indent<$tagname result='$count'>$insertText</$tagname>\n";
                }

                if ($debug)
                {
                    $warn	.= "<p>Advertiser.inc: " . __LINE__ .
                                        " '$insertText'" .
                                        ", count=$count</p>\n";
                }

                $this->needInsert	= false;
                // log the update
                $this->log($insert, $sqlParms);


                // obtain the updated record from the database
                // so the memory copy will include values set by the
                // SQL server or calculated in the INSERT statement
                $adname             = $this->row['adname'];
                $query		        = "SELECT Row FROM Advertisers WHERE AdName=:adname";
                $refParms	        = array('adname'	=> $adname);
                $stmt		        = $connection->prepare($query);
                $queryText	        = debugPrepQuery($query, $refParms);
                if ($stmt->execute($refParms))
                {		        // success
                    $row	        = $stmt->fetch(PDO::FETCH_ASSOC);
                    $this->row['row']   = $row['row'];
                }		        // success
                else
                {		        // error performing query
                    throw new Exception("Advertiser::save: '$queryText', " .
                                        print_r($stmt->errorInfo(),true));
                }		        // error performing query
            }			        // success
            else
            {			        // error inserting record
                $errorInfo	=  print_r($stmt->errorInfo(),true);
                $warn	.= "<p>Advertiser::save: " . __LINE__ .
                                " insert='$insertText'," .
                                " errorInfo=$errorInfo<p>\n";
                error_log("Advertiser.inc: " . __LINE__ .
                                " insert='$insertText', " .
                                "errorInfo=$errorInfo\n");
                if (strlen($insert) > 43)
                    $insert	= substr($insert, 0, 40) . '...';
                throw new Exception("Advertiser::save:
                                    '$insertText', " .  $errorInfo);
            }			        // error inserting record

        }			            // create new record
        else
        if (count($this->changed) > 0 ||
            (is_null($this->row['row']) && $this->row['adname'] != ''))
        {			            // update existing record
		    $set		= '';
		    $comma		= '';
            $parms		= array();
            $rownull    = is_null($this->row['row']);
		    foreach($this->changed as $fld => $value)
            {		// loop through all modified fields in record
				if ($fld == 'row' && !is_null($value))
                {	// row number
                    $rownull        = false;
				}	// row number
				$set		        .= $comma . "`$fld`=:$fld";
				$parms[$fld]	    = $value;
				$comma		        = ', ';
		    }		// loop through all modified fields in record

            if ($rownull)
            {
                $set		        .= "{$comma}Row=@nextrow";
                if ($connection->query("SET @nextrow =(SELECT COALESCE(MAX(Row), 0) + 1 FROM Advertisers)"))
                {
                    if ($debug)
                        $warn   .=
                "<p>SET @nextrow =(SELECT COALESCE(MAX(Row), 0) + 1 FROM Advertisers)</p>\n";
                }
                else
                {
                    $msg    .= "SET @nextrow =(SELECT COALESCE(MAX(Row), 0) + 1 FROM Advertisers).\n";
                }
            }

		    // if there are any changed fields, update the database
		    if (strlen($set) > 0)
		    {		// at least one field modified
				// if any fields modified then former must be set
				// using former to get the key of the record permits
				// changing the value of the primary key as long as it
                // is not changed to an already defined value
                if (isset($this->former))
                    $prime          = $this->former['adname'];
                else
                    $prime	        = $this->row['adname'];
				$update		        = "UPDATE Advertisers SET $set" .
								          " WHERE AdName=:prime";
				$parms['prime']	    = $prime;

				$stmt		        = $connection->prepare($update);
				$updateText	        = debugPrepQuery($update, $parms);
                $this->lastSqlCmd   = $updateText;
				if ($stmt->execute($parms))
				{		// successful update
				    $count	        = $stmt->rowCount();	// changed rows
				    if ($xml)
				    {
                        print "$indent<$tagname$xmlparms result='$count'>\n" .
                                "$indent\t$updateText\n" .
                                "$indent</$tagname>\n";
				    }

				    if ($debug)
						$warn	    .= "<p>" . $updateText . "</p>\n";

				    // log the update
				    $this->log($update, $parms);
				}		// successful update
				else
				{		// error updating record
				    $msg	.= $updateText . ": error=" .
							   print_r($stmt->errorInfo(),true) . ". ";
				    return false;
				}		// error updating record
		    }		// at least one field modified
            else
            {
				$count	            = 0;	// nothing changed
                $this->lastSqlCmd   = '';
            }
		}		// update existing record

		// all changes have been applied
		$this->changed	= array();	// reset
		$this->former	= null;		// reset

		// successful, return count of number of records updated
		return $count;
    }		// function Advertiser::save

}		// class Advertiser 
