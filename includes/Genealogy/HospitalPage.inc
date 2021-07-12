<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  HospitalPage.inc                                                    *
 *                                                                      *
 *  Definition of a class representing a page of a ledger for an        *
 *  Ontario Hospital.                                                   *
 *  This class provides access to the information in a row              *
 *  of the table HospitalPages.                                         *
 *                                                                      *
 *  CREATE TABLE HospitalPages (                                        *
 *	        Location    VARCHAR(64),		-- Location of Hospital     *
 *          Ledger      CHAR(1),            -- type of Ledger           *
 *      	Page        INT(11),		    -- Page Number              *
 *      	PRIMARY KEY (Location, Ledger, Page),                       *
 *          Image       VARCHAR(255));      -- filename of image file   *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/12      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class HospitalPage                                                  *
 *                                                                      *
 *  Definition of a class recording information about a ledger page.    *
 *                                                                      *
 ************************************************************************/

class HospitalPage extends Record
{
    /********************************************************************
     *  HospitalPage::$initRow                                          *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'location'              => '',
                'ledger'                => '',
                'page'                  => 1,
                'image'                 => '');

    /********************************************************************
     *  HospitalPage::$info                                             *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'HospitalPages',
                'name'              => 'HospitalPages',
                'prime'             => array('location','ledger','page'),
                'srvmg'             => false,
                'fldcount'          => 4,
                'order'             => 'page',
                'classname'         => 'HospitalPage');

    /********************************************************************
     *  HospitalPage::$defaultOrder                                     *
     *                                                                  *
     *  Default sort order for query response by class RecordSet        *
     ********************************************************************/
    static $defaultOrder    = "`Page`";

    /********************************************************************
     *  HospitalPage::$translate                                        *
     *                                                                  *
     *  Define synonyms for field names                                 *
     ********************************************************************/
    protected static $translate = array(
                'hospital'           => 'location');

    /********************************************************************
     *  HospitalPage::__construct                                       *
     *                                                                  *
     *  Construct an instance of HospitalPage based upon the            *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms              an associative array of parameters      *
     *                          to select an existing entry or          *
     *                          a complete row from the table as an     *
     *                          associative array                       *
     *                                                                  *
     *  Returns object containing a description of a book               *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        if (is_null($connection))
        {
            throw new Exception('HospitalPage::constructor: " .
                            "No connection to database server');
        }
        $needInsert             = false;
        $this->table            = 'HospitalPages';

        // validate parameters
        if (is_array($parms))
        {           // valid parm type
            if (count($parms) < count(self::$initRow))
            {           // search parameters
                $location       = 'London';
                $ledger         = 'E';
                $page           = null;
                $where          = '';
                $and            = 'WHERE ';
                $sqlParms       = array();
                foreach($parms as $key => $value)
                {               // loop through parameters
                    switch(strtolower($key))
                    {           // act on key fields
                        case 'location':
                        {       // hospital location
                            $location               = $value;
                            break;
                        }       // hospital location

                        case 'ledger':
                        {       // ledger type
                            $ledger                 = $value;
                            break;
                        }       // hospital ledger

                        case 'page':
                        {       // page number
                            $page                   = $value;
                            $where                  .= $and . 
                                                        "page=:page";
                            $sqlParms['page']       = $page;
                            $and                    = ' AND ';
                            break;
                        }       // page number
                    }           // act on key fields
                }               // loop through parameters

                // add other parameters
                $where                  .= $and .  "Location=:location";
                $sqlParms['location']   = $location;
                $and                    = ' AND ';
                $where                  .= $and .  "ledger=:ledger";
                $sqlParms['ledger']     = $ledger;
                $and                    = ' AND ';

                $query                  = "SELECT * FROM HospitalPages $where";

                // query the database
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
                if ($stmt->execute($sqlParms))
                {       // success
                    if ($debug)
                        $warn       .= "<p>HospitalPage::__construct: " . __LINE__ . 
                               " query='$queryText'</p>\n";

                    $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) == 1)
                    {               // existing record
                        $dbrow          = $result[0];
                        $needInsert     = false;
                    }               // existing record
                    else
                    if (count($result) == 0)
                    {               // create a new record
                        // set defaults
                        $dbrow          = self::$initRow;
                        $dbrow['location']  = $location;
                        $needInsert     = true;
                    }               // create new record
                    else
                    {               // error performing query
                        $this->errors = "HospitalPage::__construct: '$queryText' " .
                            "returns " . count($result) . " records";
                    }               // error performing query
                }                   // success
                else
                {       // error performing query
                    $this->errors   = "HospitalPage::__construct: '$queryText' " .
                                print_r($stmt->errorInfo(),true);
                }       // error performing query
            }           // search parameters
            else
            {           // build from existing record
                $dbrow                  = $parms;
                $needInsert             = false;
            }           // build from existing record
        }           // array parameter
        else
        {           // unsupported parameter type
            $this->errors       = "HospitalPage::__construct: " .
                                    "parameter must be an array: is " .
                                    gettype($parms);
        }           // unsupported parameter type

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'HospitalPages');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('HospitalPage Record constructed:');
    }       // HospitalPage::__construct

    /********************************************************************
     *  HospitalPage::getName                                           *
     *                                                                  *
     *  Get the title of the HospitalPage.                              *
     *                                                                  *
     *  Returns:                                                        *
     *      Name of the record as a string.                             *
     ********************************************************************/
    function getName($options   = '')
    {
        return "{$this->row['location']} {$this->row['ledger']} page {$this->row['page']}";
    }       // HospitalPage::getName

    /********************************************************************
     *  HospitalPage::__toString                                        *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return "{$this->row['location']} {$this->row['ledger']} page {$this->row['page']}";
    }       // HospitalPage::__toString

}       // class HospitalPage
