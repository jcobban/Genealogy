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
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/05/18      add $info                                       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/CountryName.inc';

/************************************************************************
 *  class Country														*
 *																		*
 *  Definition of a class recording information about a country.		*
 *																		*
 ************************************************************************/

class Country extends Record
{
    /********************************************************************
     *	Country::$initRow												*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'code'			    => '',
				'name'			    => '',  // English name
				'dialingcode'		=> '',
				'currency'		    => '');

    /********************************************************************
     *	Country::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'Countries',
                'name'      		=> 'Countries',
                'prime'	    		=> 'code',
	            'srvmg'     		=> false,
	            'fldcount'			=> 4,
                'order'     		=> 'Name',
                'classname' 		=> 'Country');

    /********************************************************************
     *	Country::$defaultOrder											*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static $defaultOrder	= "`Name`";

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
		$needInsert	            = false;
		$this->table	        = 'Countries';

		// support undocumented shorthand call
		if (is_string($parms) && strlen($parms) == 2)
		    $parms	            = array('code'	=> strtoupper($parms));

		// validate parameters
		if (is_array($parms))
		{			// valid parm type
		    if (count($parms) < count(self::$initRow))
		    {			// search parameters
				$code	    	= 'not supplied';
				$where	    	= '';
				$and	    	= 'WHERE ';
				$sqlParms   	= array();
				foreach($parms as $key => $value)
				{			    // loop through parameters
				    switch(strtolower($key))
				    {		    // act on key fields
						case 'cc':
						case 'code':
						{		// country
                            $code		        = strtoupper($value);
                            if ($code == 'UK')
                                $code           = 'GB';
						    $where		        .= $and . "Code=:code";
						    $sqlParms['code']	= $value;
						    $and		        = ' AND ';
						    break;
						}		// country

				    }		    // act on key fields
				}			    // loop through parameters

				$query		            = "SELECT * FROM Countries $where";

				// query the database
				$stmt		            = $connection->prepare($query);
				$queryText	            = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
				if ($stmt->execute($sqlParms))
				{		// success
				    if ($debug)
						$warn	    .= "<p>Country::__construct: " . __LINE__ . 
							   " query='$queryText'</p>\n";

				    $result	            = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if (count($result) == 1)
				    {		// existing record
						$dbrow		    = $result[0];
						$needInsert	    = false;
				    }		// existing record
				    else
				    if (count($result) == 0)
				    {		// create a new record
						// set defaults
						$dbrow			= self::$initRow;
						$dbrow['code']	= $code;
						$dbrow['name']	= "Country: '$code'";
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
				$dbrow		            = $parms;
				$needInsert	            = false;
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
     *	Country::getName												*
     *																	*
     *	Get the name of the country in a specified language.            *
     *																	*
     *	Input:															*
     *		$language		2-char ISO language code					*
     *																	*
     *	Returns:														*
     *	    Name of the country as a string.							*
     ********************************************************************/
    function getName($options	= '')
    {
        if (is_string($options) && strlen($options) == 2 && 
            strtolower($options) != 'en')
		{
		    $parms	        = array('cc'	=> $this->row['code'],
							        'lang'	=> $options);
		    $countryName	= new CountryName($parms);
		    return $countryName->getName();
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
