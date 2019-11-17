<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Census.inc															*
 *																		*
 *  Definition of a class representing a census.  This class provides	*
 *  access to the information in a row of the table Censuses			*
 *																		*
 *  CREATE TABLE `Censuses` (											*
 *		`CensusId`      VARCHAR(8) NOT NULL,							*
 *				PRIMARY KEY (`CensusId`) )								*
 *						normally CCYYYY or CCSSYYYY						*
 *		`Name`          VARCHAR(128) NOT NULL,							*
 *						descriptive name of census						*
 *		`LinesPerPage`  INT(11) NOT NULL,								*
 *						number of lines on the standard population form	*
 *		`Collective`    TINYINT(1) DEFAULT '0',							*
 *						1 if represents multiple separately				*
 *						administered censuses							*
 *		`PartOf`        VARCHAR(2) DEFAULT NULL,						*
 *						CC of country if it is a census of part			*
 *						and CensusId format CCSSYYYY is not used        *
 *		`Provinces`     VARCHAR(254) NOT NULL,							*
 *						string containing the 2 character state ids		*
 *						of all states covered by the census				*
 *		`GroupLines`    INT(2), interval for visually grouping lines	*
 *		`LastUnderline` INT(2), highes underlined line					*
 *		`IDSR`          INT(11), identifier of Source record			*
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8								*
 *																		*
 *  History:															*
 *		2016/01/21		created											*
 *		2017/09/05		add create option to constructor to permit		*
 *						creating new entries							*
 *						initialize list of states from Domain objects	*
 *						substring was wrong for country code check		*
 *		2017/09/26		add methods get and set							*
 *						add common field name translate table			*
 *		2017/10/09		in static function getWhere:					*
 *						parameter 'countrycode' now matches censuses	*
 *						that are administered by that country or that	*
 *						are part of that country.						*
 *						parameter 'debug' can be specified to generate	*
 *						trace output									*
 *						parameter errors are now created as warnings	*
 *						not as errors									*
 *		2017/10/16		static functions moved to class CensusSet		*
 *		2017/10/24		use class DomainSet								*
 *		2017/11/20		support additional "fields" countrycode, year,	*
 *						and table in method get							*
 *		2017/11/23		add field IDSR									*
 *						add method getStats								*
 *		2017/12/18		add support for temporary fields				*
 *		2017/12/23		add support for 'prov' field					*
 *		2018/01/12		add grouplines, lastunderline fields			*
 *		2018/02/13		tolerate constructor call with string censusid	*
 *						if partof defined use it as the country id		*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/02/20      derive default name from CensusId               *
 *		                improve get('countrycode')                      *
 *		                improve get('table')                            *
 *		                add get('province')                             *
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/05/18      add $info                                       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc' ;
require_once __NAMESPACE__ . '/Domain.inc' ;
require_once __NAMESPACE__ . '/DomainSet.inc' ;
require_once __NAMESPACE__ . '/Country.inc' ;

/************************************************************************
 *  class Census														*
 *																		*
 *  Definition of a class recording information about a census			*
 *																		*
 ************************************************************************/

class Census extends Record
{
    /********************************************************************
     *	Census::$initRow												*
     *																	*
     *	Default initial row for a record in table Censuses				*
     ********************************************************************/
    protected static $initRow	= array(
				'censusid'			=> 'CA1881',
				'name'				=> '1881 Census of Canada',
				'linesperpage'		=> 50,
				'collective'		=> 0,
				'partof'			=> null,
				'provinces'			=> '',
				'grouplines'		=> 5,
				'lastunderline'		=> 50,
				'idsr'				=> null);

    /********************************************************************
     *	Census::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'Censuses',
                'name'      		=> 'Censuses',
                'prime'	    		=> 'censusid',
	            'srvmg'     		=> false,
	            'fldcount'			=> 6,
                'order'     		=> 'Name',
                'classname' 		=> 'Census');

    /********************************************************************
     *	Census::$translate												*
     *																	*
     *	Alternative field names											*
     ********************************************************************/
    protected static $translate	= array(
				'id'				=> 'censusid',
				'code'				=> 'censusid',
				'states'			=> 'provinces',
				'source'			=> 'idsr',
				'state'			    => 'province',      // pseudo field
				'country'			=> 'countrycode',	// pseudo field
				'cc'				=> 'countrycode',	// pseudo field
				'year'				=> 'year',		    // pseudo field
				'table'				=> 'table');		// pseudo field

    /********************************************************************
     *	Census::$defaultOrder											*
     *																	*
     *	Defines the default sort order for class RecordSet				*
     ********************************************************************/
    protected static $defaultOrder		= '`Name`';

    /********************************************************************
     *	function Census::__construct									*
     *																	*
     *  Construct an instance of Census based upon the parameters.      *
     *  This instance may or may not be synchronized with		        *
     *	the database.													*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameters				*
     *					identifying the specific row.					*
     *					This may be a row retrieved from the database.	*
     *																	*
     *  Returns object containing a description of a census				*
     *																	*
     *	Throws Exception if unable to complete creating record			*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $msg;
		global $connection;

		$cc			= 'CA';
		$countryName		= 'Canada';

		if (is_string($parms))
		    $parms		= array('censusid'	=> $parms);
		else
        if (!is_array($parms))
        {
            $this->msg      .= "parameter is " . gettype($parms) . " not an associative array";
            $parms          = array();
        }
		$needInsert	= false;

		$this->table	= 'Censuses';

		// check for $parms as a complete existing row from the database
		$dbrow		= null;
		if (count($parms) >= count(self::$initRow))
		{			// complete row from database
		    $dbrow		= array();
		    foreach($parms as $field => $value)
		    {			// loop through all parameters
				$fieldLc	= strtolower($field);
				if (array_key_exists($fieldLc, self::$initRow))
				{
				    $dbrow[$fieldLc]		= $value;
				}
				else
				{		// act on specific field names
				    $warn		.= "<p>Census::__construct: " .
								   __LINE__ .
						" Invalid Census field name $field='$value'</p>\n";
				    $this->extras[$fieldLc]	= $value;
				}		// act on specific field names
		    }			// loop through all parameters
		}			// complete row from database
		else
		{			// parameters for creating row
		    $censusId				= null;
		    $query		    		= null;
		    $sqlParms				= array();
		    $where		    		= '';
		    $and		    		= ' WHERE ';

		    // process parameters that identify a census
		    foreach($parms as $field => $value)
		    {			// process search parameters
				$fieldLc	        = strtolower($field);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	    = self::$translate[$fieldLc];
				switch($fieldLc)
				{		// act on specific parameters
				    case 'censusid':
				    {
						$censusId	= $value;
						$cc		    = substr($censusId, 0, 2);
						$where		.= "$and CensusId=?";
						$sqlParms[]	= $censusId;
						$and		= ' AND ';
						break;
				    }

				    case 'name':
				    {		// valid field names for search
						$where		.= "$and `$fieldLc`=?";
						$sqlParms[]	= $value;
						$and		= ' AND ';
						break;
				    }		// valid field names for search

				    case 'create':
				    {		// deprecated
						break;
				    }		// deprecated

				}		// act on specific parameters
		    }			// process search parameters

		    // query the database for census information
		    $query		        = "SELECT * FROM Censuses $where";
		    $stmt		        = $connection->prepare($query);
		    $queryText		    = debugPrepQuery($query, $sqlParms);
            $this->lastSqlCmd   = $queryText;
		    if ($stmt->execute($sqlParms))
            {				// successful query
				if ($debug)
				    $warn	    .= "<p>Census.inc: " . __LINE__ .
    							   " query='$queryText'</p>";

				$dbrow	= $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($dbrow))
				{			// existing record
				    $needInsert			    = false;
				}			// existing record
				else
				{			// create a new record
				    // set defaults
				    $dbrow			        = self::$initRow;
                    $dbrow['censusid']		= $censusId;
                    $cc                     = substr($censusId, 0, 2);
                    $year                   = substr($censusId, -4);

				    // process parameters that modify record
				    foreach($parms as $field => $value)
				    {		// process modify parameters
						$fieldLc	= strtolower($field);
						if (array_key_exists($fieldLc, self::$translate))
						    $fieldLc	= self::$translate[$fieldLc];
						switch($fieldLc)
						{		// act on specific parameters
						    case 'name':
						    case 'linesperpage':
						    case 'collective':
						    case 'provinces':
						    case 'idsr':
						    {
							    $dbrow[$fieldLc]	= $value;
							    break;
						    }		// set selected fields

						    case 'partof':
						    {
                                $dbrow[$fieldLc]	= $value;
                                if (strlen($value) == 2)
                                    $cc             = strtoupper($value);
							    break;
						    }		// set selected fields

						}		// act on specific parameters
				    }			// process modify parameters

                    $country                = new Country(array('cc' => $cc));
                    $dbrow['name']          = "$year Census of " .
                                              $country->getName();
                    $needInsert			    = true;

				    // fill in default states/provinces list
				    if ($dbrow['provinces'] == '')
				    {
						$provinces	    = '';
						$parms		    = array('cc' => $cc);
						$domains	    = new DomainSet($parms);
						foreach($domains as $domain)
						{
						    $provinces .= substr($domain->get('domain'),2);
						}
						$dbrow['provinces']	= $provinces;
				    }
				}			// create new record
		    }				// successful query
		    else
		    {				// error performing query
                $this->msg  .= "query='$queryText' " .
                                print_r($connection->errorInfo(),true) .
                                ". ";
		    }				// error performing query
		}				    // parameters for creating row

		// report serious parameter errors
        if (strlen($this->msg) > 0)
        {
            $msg	.= "Census::__construct: " . $this->$msg . ".  ";
        }

		// initialize derived information
		$countrycode			    = substr($dbrow['censusid'], 0, 2);
		$year				        = substr($dbrow['censusid'], 2);
		$this->extras['year']		= $year;
		if ($dbrow['partof'] == 'CA' && $year < 1867)
		{
		    $this->extras['prov']	= $countrycode; 
		    $this->extras['countrycode']= $dbrow['partof'];
		}
		else
		{
		    $this->extras['prov']	= '';
		    $this->extras['countrycode']= $countrycode;
		}
		
		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Censuses');
		$this->needInsert	        = $needInsert;

		// diagnostic output if debug is set
		$this->dump('Census Record constructed:');
    }		//	function Census::__construct

    /********************************************************************
     *	function::Census:get											*
     *																	*
     *	Obtain the current value of a field in the object.				*
     *																	*
     *  Parameters:														*
     *	    $name		name of field to retrieve, plus					*
     *					 'cc' or 'countrycode' ISO country code			*
     *					 'year' enumeration year						*
     *					 'table' name of database table					*
     ********************************************************************/
    function get($name)
    {
		$fieldLc		    = strtolower($name);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc		= self::$translate[$fieldLc];
        $censusid           = $this->row['censusid'];
		switch($fieldLc)
		{			// act on specific field
		    case 'censusid':
		    case 'name':
		    case 'linesperpage':
		    case 'collective':
		    case 'provinces':
		    case 'grouplines':
		    case 'lastunderline':
				return $this->row[$fieldLc];

		    case 'partof':
		    {           // if census for part of a country
				$partof		= $this->row[$fieldLc];
				if (is_null($partof))
				    return '';
				else
				    return $partof;
		    }           // if census for part of a country

		    case 'idsr':
		    {           // the identifier of the Source record
				$idsr		= $this->row['idsr'];
				if (is_null($idsr))
				    return 1;       // "blank" Source
				else
				    return $idsr;
            }           // the identifier of the Source record

            case 'countrycode':
            {           // ISO country code
                $partof		= $this->row['partof'];
                if (is_string($partof) && strlen($partof) == 2)
                    return $partof;
                else
                    return substr($censusid, 0, 2);
            }           // ISO country code

            case 'province':
            {           // province or state abbreviation
                // if this is a census of an individual state or
                // province this returns the string id 
                $idl        = strlen($censusid);
                $partof		= $this->row['partof'];
                if (is_string($partof) && strlen($partof) == 2)
                    return substr($censusid, 0, 2);
                else
                if ($idl >= 8)
                    return substr($censusid, 2, $idl - 6);
                else
                    return null;    // national census
            }           // province or state abbreviation

            case 'year':
            {           // enumeration year
				return intval(substr($censusid,-4));
            }           // enumeration year

            case 'table':
            {           // database table name
                $idl                = strlen($censusid);
				if (array_key_exists('table', $this->row))
				    return $this->row['table'];     // to do
                else
                if ($this->get('countrycode') == 'CA')
                    return 'Census' . substr($censusid, -4);
                else
                    return substr($censusid, 0, $idl - 4) . 
                            'Census' . substr($censusid, -4);
            }           // database table name

		    default:
		    {		    // other fields
				return parent::get($fieldLc);
		    }		    // other fields
		}		        // act on specific field
    }		// function Census::get

    /********************************************************************
     *	function Census::getStats										*
     *																	*
     *	Obtain the project statistics for this census					*
     *																	*
     *	Returns:														*
     *	    Associative array containing								*
     *			'total'			total lines transcribed					*
     *			'pop'			total population of district			*
     ********************************************************************/
    function getStats()
    {
		global	$msg;
		global	$debug;
		global	$warn;
		global	$connection;

		$table		= $this->get('table');
		$censusId	= $this->get('censusid');
        $query  	= "SELECT Count(*) AS Total, ";
		if ($this->get('collective'))
		{
		    $query		.= "(SELECT SUM(D_Population) FROM Districts ";
		    $sqlParms		= array();
		    $cc			= $this->get('cc'); 
		    $year		= $this->get('year'); 
		    $censusParms	= array('partof'	=> $cc,
								'year'		=> $year);
		    $censuses		= new CensusSet($censusParms);
		    $or			= ' WHERE ';
		    foreach($censuses as $colony)
		    {
				$colonyId	= $colony->get('censusid');
		        $query		.= "$or D_Census=?";
				$sqlParms[]	= $colonyId;
				$or		= ' OR ';
		    }
		    $query	.= ") AS Pop";
		}
		else
		{
		    $query	.="(SELECT SUM(D_Population) FROM Districts WHERE D_Census=:censusId) AS Pop";
		    $sqlParms	= array('censusId'	=> $censusId); 
		}
		$query		.= " FROM $table WHERE GivenNames != ''";
    
        // execute the query
        $stmt		        = $connection->prepare($query);
        $queryText	        = debugPrepQuery($query, $sqlParms);
        $this->lastSqlCmd   = $queryText;
        if ($stmt->execute($sqlParms))
        {		// successful query
            $row	        = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($debug)
		    {
		        $warn	    .= "<p>Census:getStats: " . __LINE__ .
				               " $queryText</p>\n";
				$warn	    .= "<p>" . print_r($row, true) . "</p>\n";
		    }
		    return $row;
        }		// successful query
        else
        {
            $msg	.= "'" . $queryText . "': " .
				               print_r($connection->errorInfo(), true);
		    return array('total'	=> 0,
						 'pop'		=> 1);	// avoid zero divide
        }		// error on request

    }		// function Census::getStats

    /********************************************************************
     *	function Census::getRealFieldName								*
     *																	*
     *	Perform standard manipulation for this table to obtain the		*
     *	the name of a field from a suggested parameter name.			*
     *																	*
     *	Returns:														*
     *	    A field name in lower case or null if input is invalid.		*
     ********************************************************************/
    public static function getRealFieldName($fldname)
    {
		global $warn;

		$fieldLc	= strtolower($fldname);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		if ($fieldLc == 'offset' || $fieldLc == 'limit')
		    return $fieldLc;
		else
		if (array_key_exists($fieldLc, self::$initRow))
		    return $fieldLc;
		else
		if ($fieldLc == 'countrycode')
		    return "LEFT(`CensusID`,2) AS CountryCode"; // pseudo-field
		else
		if ($fieldLc == 'year')
		    return "SUBSTRING(`CensusId`,-4) AS Year"; // pseudo-field
		else
		    return null;
    }		// static function getRealFieldName

    /********************************************************************
     *	function Census::__toString										*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return "Census{censusid=" . $this->row['censusid'] .
						    ", name='" . $this->row['name'] .
						    "'}";
    }		//	function Census::__toString
}		// class Census 
