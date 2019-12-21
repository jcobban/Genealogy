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
 *		Count12		INT(10) NOT NULL DEFAULT 0)	-- count for December   *
 *																		*
 *  History:															*
 *		2019/12/20      created                                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
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
				'count12'			=> 0);

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
            if (!array_key_exists('adname', $dbrow) ||
                strlen($dbrow['adname']) == 0)
            {                       // row missing primary key
                $this->msg      .= "Advertiser::__construct: invalid row ";
                $comma          = 'array(';
                foreach($dbrow as $key => $value)
                {
                    $this->msg  .= "$comma'$key'=>$value";
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
					    if (is_string($value) && strlen($value) > 0)
	                    {
							$adname		= $value;
					        $where		.= $and . "`AdName`=:adname";
					        $sqlParms['adname']		= $adname;
					        $and		= ' AND ';
	                    }
					    else
					    {
							$this->msg	.= "Advertiser::__construct: `AdName` value '$value' invalid. ";
							$adname	    = '';
					    }
					    break;
					}
	
			    }			        // act on specific field names
			}		        	    // loop through search parameters
	
			if (strlen($where) > 0)
			{	// obtain the first instance that matches search parameters
			    $query		            = "SELECT * FROM Advertisers $where";
	
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
		global	$connection;

        if ($month < 0 || $month > 12)
            return false;
        if ($month < 10)
            $month          = '0' . $month;
		if ($this->former)
            $adname		    = $this->former['adname'];
        else
            $adname         = $this->row['adname'];
        $update		        = "UPDATE Advertisers " .
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
            $this->log($update, $parms);
		}		        // successful update
		else
		{		// error updating record
		    $msg	        .= $updateText . ": error=" .
			        		   print_r($stmt->errorInfo(),true) . ". ";
		    $count          = false;
		}		// error updating record
        return $count;
    }           // function Advertiser::increment

}		// class Advertiser 
