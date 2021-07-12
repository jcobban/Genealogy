<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  HospitalEmployeeLedgerEntry.inc                                     *
 *                                                                      *
 *  Definition of a class representing an entry in a employee ledger    *
 *  for an Ontario Hospital.                                            *
 *  This class provides access to the information in a row              *
 *  of the table HospitalEmployeeLedgerEntries.                         *
 *                                                                      *
 *  CREATE TABLE HospitalEmployeeLedgerEntries (                        *
 *      Location    VARCHAR(64),        -- Location of Hospital         *
 *      Ledger      CHAR(1) DEFAULT 'E',-- type of Ledger               *
 *      Employee    INT(11),            -- Employee Number              *
 *      Page        INT(11),            -- Page Number                  *
 *      PRIMARY KEY (Location, Ledger, Employee),                       *
 *      GivenNames  VARCHAR(64) DEFAULT '', -- given name               *
 *      Surname     VARCHAR(64) DEFAULT '', -- surname                  *
 *      SoundsLike  VARCHAR(4) DEFAULT '',  -- soundex code             *
 *      Age         VARCHAR(6) DEFAULT '',                              *
 *      PrevOcc     VARCHAR(32) DEFAULT '',                             *
 *      PrevRes     VARCHAR(128) DEFAULT '',                            *
 *      Service     VARCHAR(32) DEFAULT '',                             *
 *      DateEmploy  VARCHAR(16) DEFAULT '',                             *
 *      DateDisch   VARCHAR(16) DEFAULT '',                             *
 *      Wages       FLOAT(6,2) DEFAULT 0,                               *
 *      Remarks     VARCHAR(255) DEFAULT '',                            *
 *      IDIR        INT(10) UNSIGNED DEFAULT NULL,                      *
 *      FOREIGN KEY (`IDIR`) REFERENCES `tblIR` (`IDIR`) );             *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/12      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class HospitalEmployeeLedgerEntry                                   *
 *                                                                      *
 *  Definition of a class recording information about a ledger page.    *
 *                                                                      *
 ************************************************************************/

class HospitalEmployeeLedgerEntry extends Record
{
    /********************************************************************
     *  HospitalEmployeeLedgerEntry::$initRow                           *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'location'              => '',
                'ledger'                => '',
                'page'                  => 1,
                'employee'              => '',
                'givennames'            => '',
                'surname'               => '',
                'soundslike'            => '',
                'age'                   => '',
                'prevocc'               => '',
                'prevres'               => '',
                'service'               => '',
                'dateemploy'            => '',
                'datedisch'             => '',
                'wages'                 => 0,
                'religion'              => '',
                'remarks'               => '',
                'idir'                  => null);

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::$info                              *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'HospitalEmployeeLedgerEntries',
                'name'              => 'HospitalEmployeeLedgerEntries',
                'prime'             =>
                                    array('location','ledger','employee'),
                'srvmg'             => false,
                'fldcount'          => 17,
                'order'             => 'page,employee',
                'classname'         => 'HospitalEmployeeLedgerEntry');

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::$defaultOrder                      *
     *                                                                  *
     *  Default sort order for query response by class RecordSet        *
     ********************************************************************/
    static $defaultOrder    = "`Page`,`Employee`";

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::$translate                         *
     *                                                                  *
     *  Define synonyms for field names                                 *
     ********************************************************************/
    protected static $translate = array(
                'hospital'           => 'location');

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::__construct                        *
     *                                                                  *
     *  Construct an instance of HospitalEmployeeLedgerEntry based upon *
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

        $errpref    = 'HospitalEmployeeLedgerEntry::__construct: ';
        if (is_null($connection))
        {
            throw new Exception($errpref .
                                'No connection to database server');
        }
        $needInsert             = false;
        $this->table            = 'HospitalEmployeeLedgerEntries';

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

                        case 'employee':
                        {       // employee number
                            $employee               = $value;
                            $where                  .= $and . 
                                                    "employee=:employee";
                            $sqlParms['employee']   = $employee;
                            $and                    = ' AND ';
                            break;
                        }       // employee number
                    }           // act on key fields
                }               // loop through parameters

                // add other search parameters
                $where                  .= $and .  "Location=:location";
                $sqlParms['location']   = $location;
                $and                    = ' AND ';
                $where                  .= $and .  "ledger=:ledger";
                $sqlParms['ledger']     = $ledger;
                $and                    = ' AND ';

                $query                  = "SELECT * FROM HospitalEmployeeLedgerEntries $where";

                // query the database
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
                if ($stmt->execute($sqlParms))
                {       // success
                    if ($debug)
                        $warn       .= "<p>$errpref" . __LINE__ . 
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
                        $dbrow              = self::$initRow;
                        $dbrow['location']  = $location;
                        $dbrow['ledger']    = $ledger;
                        $dbrow['employee']  = $employee;
                        $needInsert         = true;
                    }               // create new record
                    else
                    {               // error performing query
                        $this->errors = "$errpref'$queryText' " .
                            "returns " . count($result) . " records";
                    }               // error performing query
                }                   // success
                else
                {       // error performing query
                    $this->errors   = "$errpref'$queryText' " .
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
            $this->errors               = $errpref .
                                         "parameter must be an array: is " .
                                         gettype($parms);
        }           // unsupported parameter type

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'HospitalEmployeeLedgerEntries');
        $this->needInsert   = $needInsert;

        // apply any other parameters as updates
        foreach($parms as $key => $value)
        {               // loop through parameters
            switch(strtolower($key))
            {           // act on key fields
                case 'location':
                case 'ledger':
                case 'employee':
                    break;

        		case 'page':
        		case 'givennames':
        		case 'surname':
        		case 'soundslike':
        		case 'age':
        		case 'prevocc':
        		case 'prevres':
        		case 'service':
        		case 'dateemploy':
        		case 'datedisch':
        		case 'wages':
        		case 'religion':
                case 'remarks':
                    $this->set($key, $value);
                    break;

            }           // act on key fields
        }               // loop through parameters

        // diagnostic output if debug is set
        $this->dump('HospitalEmployeeLedgerEntry Record constructed:');
    }       // HospitalEmployeeLedgerEntry::__construct

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::getName                            *
     *                                                                  *
     *  Get the title of the HospitalEmployeeLedgerEntry.               *
     *                                                                  *
     *  Returns:                                                        *
     *      Name of the record as a string.                             *
     ********************************************************************/
    function getName($options   = '')
    {
        return "{$this->row['location']} {$this->row['ledger']} employee {$this->row['givennames']} {$this->row['surname']}";
    }       // HospitalEmployeeLedgerEntry::getName

    /********************************************************************
     *  HospitalEmployeeLedgerEntry::__toString                         *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return "{$this->row['location']} {$this->row['ledger']} employee {$this->row['employee']} {$this->row['givennames']} {$this->row['surname']}";
    }       // HospitalEmployeeLedgerEntry::__toString

}       // class HospitalEmployeeLedgerEntry
