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
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/05/18      add $info                                       *
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
     *	Concession::$info											    *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Concessions',
                'name'      => 'Concessions',
                'prime'	    => array('domain','county','township','conid'),
	            'srvmg'     => false,
	            'fldcount'	=> 11,
                'order'     => "`Domain`,`County`,`Township`,`Order`",
                'classname' => 'Concession');

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
		$query	            = "SELECT * FROM Concessions  " .
				"WHERE `Domain`=? AND `County`=? AND `Township`=? AND ConID=?";
		$sqlParms	        = array($domain, $county, $township, $conid);

		// query the database
		$stmt		        = $connection->prepare($query);
		$queryText	        = debugPrepQuery($query, $sqlParms);
        $this->lastSqlCmd   = $queryText;
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

}		// class Concession 
