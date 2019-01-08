<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  GraveStone.inc														*
 *																		*
 *  Definition of a class representing a grave stone.						*
 *  This class provides access to the information in a row				*
 *  of the table GraveStones.												*
 *																		*
 * CREATE TABLE `GraveStones` (												*
 *		`GS_Index` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,				*
 *		`GraveStone` CHAR(3) NOT NULL,										*
 *		`Township` VARCHAR(48) NOT NULL,								*
 *		`Cemetery` VARCHAR(64) NOT NULL,								*
 *		`Zone` VARCHAR(8) NOT NULL,										*
 *		`Row` VARCHAR(8) NOT NULL,										*
 *		`Plot` VARCHAR(8) NOT NULL,										*
 *		`Side` VARCHAR(16) NOT NULL,										*
 *		`Surname` VARCHAR(64) NOT NULL,										*
 *		`GivenName` VARCHAR(64) NOT NULL,								*
 *		`Text` TEXT,														*
 *		`BirthDate` INT(11) DEFAULT NULL,								*
 *		`DeathDate` INT(11) DEFAULT NULL,								*
 *		PRIMARY KEY (`GS_Index`),										*
 *		UNIQUE KEY `GS_Index` (`GS_Index`),								*
 *		KEY `GraveStone` (`County`),										*
 *		KEY `Township` (`Township`),										*
 *		KEY `Cemetery` (`Cemetery`),										*
 *		KEY `Zone` (`Zone`),												*
 *		KEY `Row` (`Row`),												*
 *		KEY `Plot` (`Plot`),												*
 *		KEY `Surname` (`Surname`),										*
 *		KEY `GivenName` (`GivenName`) )										*
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8 								*
 *																		*
 *  History:																*
 *		2018/02/17		created												*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class GraveStone														*
 *																		*
 *  Definition of a class recording information about a single grave		*
 *  stone																*
 *																		*
 ************************************************************************/

class GraveStone extends Record
{
    /********************************************************************
     *		GraveStone::$initRow												*
     *																		*
     *		Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'gs_index'		=> 0,
				'county'		=> '',
				'township'		=> '',
				'cemetery'		=> '',
				'zone'			=> '',
				'row'			=> '',
				'plot'			=> '',
				'side'			=> '',
				'surname'		=> '',
				'givenname'		=> '',
				'text'			=> '',
				'birthdate'		=> 0,
				'deathdate'		=> 0);

    /********************************************************************
     *		GraveStone::$translate												*
     *																		*
     *		Define alternate field names										*
     ********************************************************************/
    protected static $translate	= array('index'		=> 'gs_index');

    /********************************************************************
     *		GraveStone::$defaultOrder										*
     *																		*
     *		Default sort order												*
     ********************************************************************/
    protected static $defaultOrder	=
							"`Zone`,`Row`,`Plot`,`Side`,`GS_Index`";
    /********************************************************************
     *		GraveStone::$monthName												*
     *																		*
     *		Table for translating month numbers to names.						*
     ********************************************************************/
    protected static $monthName		= array(
				'', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
				    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

    /********************************************************************
     *		GraveStone::__construct												*
     *																		*
     *		Construct an instance of GraveStone based upon the				*
     *		parameters.  This instance may or may not be synchronized with		*
     *		the database.														*
     *																		*
     *		Input:																*
     *		    $parms				an associative array containing a		*
     *								record or search parameters				*
     *																		*
     *		Returns object containing a description of a grave stone		*
     *																		*
     *		Throws Exception if unable to complete function						*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $connection;

		$needInsert	= false;
		$this->table	= 'GraveStones';
		$errors		= '';
		$where		= '';
		if ($debug)
		    $warn	.= "<p>GraveStone::__construct(" .
						   print_r($parms, true) . ")</p>\n";

		// validate parameters
		if (is_array($parms))
		{			// array
		    if (count($parms) < count(self::$initRow))
		    {			// associative array of parameters
				foreach($parms as $key => $value)
				{			// loop through parameters
				    $fieldLc	= strtolower($key);
				    if (array_key_exists($fieldLc, self::$translate))
						$fieldLc	= self::$translate[$fieldLc];
				    switch($fieldLc)
				    {		// act on key fields
						case 'gs_index':
						{		// grave stone code
						    $where	= "WHERE GS_Index=:index";
						    $sqlParms	= array('index'	=> $value);
						    break;
						}		// grave stone code
				    }			// act on key fields
				}			// loop through parameters

				// set defaults
				$dbrow		= self::$initRow;
				$needInsert	= true;

				if (strlen($where) > 0)
				{			// read the record from the database
				    $query	= "SELECT * FROM GraveStones " . $where;
				    $stmt	= $connection->prepare($query);
				    $queryText	= debugPrepQuery($query, $sqlParms);
				    if ($stmt->execute($sqlParms))
				    {			// successful query
						if ($debug)
						    $warn	.= "<p>$queryText</p>\n";

						$result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (count($result) > 0)
						{		// existing record
						    $dbrow		= $result[0];
						    $needInsert		= false;
						}		// existing record
				    }			// successful query
				    else
				    {			// error performing query
						error_log("GraveStone.inc " . __LINE__ .
							" query='$queryText'</p>\n");
						throw new Exception("GraveStone::__construct: " .
								    "'$queryText' " .
								    print_r($stmt->errorInfo(),true));
				    }			// error performing query
				}			// read the record from the database
		    }				// associative array of parameters
		    else
		    {				// build from existing record
				$dbrow		= $parms;
		    }				// build from existing record
		    parent::__construct($dbrow,
							'GraveStones');

		    $this->needInsert	= $needInsert;

		    // apply any updates to the record
		    foreach($parms as $key => $value)
		    {			// loop through parameters
				$fieldLc	= strtolower($key);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	= self::$translate[$fieldLc];
				if ($fieldLc != 'gs_index')
				    parent::set($fieldLc, $value);
		    }			// loop through parameters

		    // diagnostic output if debug is set
		    $this->dump('GraveStone Record constructed:');
		}				// array
		else
		{				// unsupported data type
		    throw new Exception("GraveStone::__construct: " .
						"first parameter must be a string or array: is " .
						print_r($parms, true));
		}				// unsupported data type
    }		// GraveStone::__construct

    /********************************************************************
     *		GraveStone:get														*
     *																		*
     *		Obtain the current value of a field in the object.				*
     *																		*
     *  Parameters:														*
     *		    $name		name of field to retrieve						*
     *																		*
     ********************************************************************/
    function get($field)
    {
		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc		= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'birthdate':
		    case 'deathdate':
		    {
				$value		= $this->row[$fieldLc];
				if ($value == 0)
				    return '';
				$year		= (int)($value/10000);
				$rem		= $value % 10000;
				$mm		= (int)($rem/100);
				if ($mm > 0 && $mm <= 12)
				    $mm		= self::$monthName[(int)($rem/100)];
				else
				    $mm		= ''; 
				$day		= $rem % 100; 
				if ($year > 0)
				{
				    if ($mm != '')
				    {
						if ($day > 0)
						    return "$year $mm $day";
						else
						    return "$year $mm";
				    }
				    return $year;
				}
				else
				    return '';
		    }		// Province or State code

		    default:
		    {
				return parent::get($fieldLc);
		    }		// other fields
		}		// check field name
    }			// function GraveStone::get

    /********************************************************************
     *		GraveStone:set														*
     *																		*
     *		Change the current value of a field in the object.				*
     *																		*
     *  Parameters:														*
     *		    $name		name of field to change								*
     *		    $value		new value										*
     *																		*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc		= self::$translate[$fieldLc];
		switch($fieldLc)
		{			// act on field name
		    case 'birthdate':
		    case 'deathdate':
		    {			// birth or death date
				if ($value == '')
				    $value	= 0;
				$pieces		= explode(' ', $value);
				$year		= 0;
				$mm		= 0;
				$day		= 0;
				foreach($pieces as $token)
				{
				    if (ctype_digit($token))
				    {
						$token		= intval($token);
						if ($token > 31)
						    $year	= $token;
						else
						    $day	= $token;
				    }
				    else
				    {
						$token		= ucfirst($token);
						$mm	= array_search($token, self::$monthName);
						if ($mm === false)
						    $mm		= 0;
				    }
				    $value	= $year * 10000 + $mm * 100 + $day;
				}
				return parent::set($fieldLc, $value);
		    }			// birth or death date

		    default:
		    {
				return parent::set($fieldLc, $value);
		    }			// other fields
		}			// act on field name
    }			// function GraveStone::set

    /********************************************************************
     *		GraveStone::getName												*
     *																		*
     *		Convenience function for accessing the 'name' attribute.		*
     *																		*
     *		Returns:														*
     *		    Name of the grave stone as a string.						*
     ********************************************************************/
    function getName($options	= null)
    {
		return $this->row['givenname'] . ' ' . $this->row['surname'];
    }		// GraveStone::getName

}		// class GraveStone
