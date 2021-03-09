<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  EventType.inc                                                       *
 *                                                                      *
 *  Definition of a class representing an event type in a genealogical  *
 *  database.  This class provides access to the information in a row   *
 *  of the table tblET.                                                 *
 *  This table does not support i18n so it is not used by this          *
 *  implementation but it is maintained for compatibility with Legacy   *
 *  Family Tree.                                                        *
 *                                                                      *
 *      Table: tblET - Event Type Name List,                            *
 *                              RECORD 1 will always exist as blank     *
 *                              record. Don't delete it.                *
 *                                                                      *
 *       Columns                                                        *
 *          Name                Type                                    *
 *          IDET                INT(10),                                *
 *                      used by: tblER.IDET                             *
 *          EventType           VARCHAR(100),                           *
 *                      the name assigned to an event                   *
 *          Used                TINYINT(3),                             *
 *          Tag1                TINYINT(3),                             *
 *          ShowDate            TINYINT(3),                             *
 *          ShowDescription     TINYINT(3),                             *
 *          ShowPlace           TINYINT(3),                             *
 *          Sentence            VARCHAR(255),                           *
 *          Sentence2           VARCHAR(255),                           *
 *          Sentence3           VARCHAR(255),                           *
 *          Sentence4           VARCHAR(255),                           *
 *          Sentence5           VARCHAR(255),                           *
 *          Sentence6           VARCHAR(255),                           *
 *          Sentence7           VARCHAR(255),                           *
 *          Sentence8           VARCHAR(255),                           *
 *          Private             TINYINT(3),                             *
 *          PPExclude           TINYINT(3),                             *
 *          qsTag               TINYINT(3),                             *
 *          RGExclude           TINYINT(3),                             *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/11      use explicit query code                         *
 *      2010/10/20      changed parent constructor                      *
 *      2010/10/23      connection made global                          *
 *      2010/12/26      add getPreposition method                       *
 *                      add getNotes method                             *
 *                      eliminate internal member idtr                  *
 *      2011/01/02      use regexp in construct by name                 *
 *                      throw Exception on construction failure         *
 *      2011/06/11      add methods getFirst and getNext                *
 *      2012/01/13      rename classes to have upper case character     *
 *      2012/05/13      correct comparison for abbreviations            *
 *                      support optional 2-character abbreviations used *
 *                      by GEDCOM 5.5                                   *
 *      2012/08/15      add toString method                             *
 *      2012/08/18      do not throw exception on IDET 0                *
 *      2012/10/06      improve comments                                *
 *      2012/10/20      standardize initialization of new record        *
 *      2013/08/09      base class renamed to Record                    *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/11/01      use prepared statement                          *
 *                      accept associative array search parms           *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/10/08      remove tracelog entries                         *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class EventType                                                     *
 *                                                                      *
 *  Definition of a class implementing behavior for event type records      *
 *  within a genealogy database.                                        *
 *                                                                      *
 ************************************************************************/

class EventType extends Record
{

    /********************************************************************
     *  EventType::$initRow                                             *
     *                                                                  *
     *  static array containing default values for a new record         *
     *                                                                  *
     *  This is not actually used for this class, but is retained for   *
     *  compatibility with the other classes derived from Record        *
     ********************************************************************/
    protected static    $initRow    = array(
                'idet'              => 0,
                'eventtype'         => '',
                'used'              => 0,
                'tag1'              => 0,
                'showdate'          => 0,
                'showdescription'   => 0,
                'showplace'         => 0,
                'sentence'          => '[HeShe] [EventName] [Desc] [onDate] [inPlace].[Sources] [Notes]', 
                'sentence2'         => '[HeShe] [EventName] [onDate].[Sources] [Notes]', 
                'sentence3'         => '[HeShe] [EventName] [inPlace].[Sources] [Notes]', 
                'sentence4'         => '[HeShe] [EventName] [Desc].[Sources] [Notes]', 
                'sentence5'         => '[HeShe] [EventName] [Desc] [onDate].[Sources] [Notes]', 
                'sentence6'         => '[HeShe] [EventName] [Desc] [inPlace].[Sources] [Notes]', 
                'sentence7'         => '[HeShe] [EventName] [onDate] [inPlace].[Sources] [Notes]', 
                'sentence8'         => '[HeShe] [EventName].[Sources] [Notes]', 
                'private'           => 0,
                'ppexclude'         => 0,
                'qstag'             => 0,
                'rgexclude'         => 0);

    /********************************************************************
     *  EventType::$eventTypeIndex                                      *
     *                                                                  *
     *  To save time if a specific record is requested more than once   *
     *  during a process, maintain a table of EventType records.        *
     ********************************************************************/
    protected static    $eventTypeIndex = array();

    /********************************************************************
     *  static function EventType::getEventType                         *
     *                                                                  *
     *  Get an existing entry or create an instance of the class        *
     *  EventType from a row returned from a database query of the      *
     *  table 'tblET' for a value of IDET.                              *
     *                                                                  *
     *  Input:                                                          *
     *      $idet       integer identifier of an eventType              *
     *                                                                  *
     *  Returns:                                                        *
     *      an instance of EventType                                    *
     ********************************************************************/
    static function getEventType($idet)
    {
        if (array_key_exists($idet, self::$eventTypeIndex))
            return self::$eventTypeIndex[$idet];
        else
            return new EventType(array('idet' => $idet));
    }           // static function EventType::getEventType

    /********************************************************************
     *  EventType::__construct                                          *
     *                                                                  *
     *  Given the identifier or database result construct an event      *
     *  type record.                                                    *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      search parameters as associative array, or      *
     *                  a database record presented as an array, or     *
     *                  (deprecated) numeric identifier of a event type *
     *                  0 or 'new': return a new default instance       *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of EventType                                       *
     ********************************************************************/
    function __construct($parms = array())
    {
        global  $debug;
        global  $warn;
        global  $connection;

        // action depends upon type of first parameter
        // convert deprecated formats to search parms array
        if (is_string($parms) && ctype_digit($parms))
            $parms          = array('idet'  => (int)$parms);
        else
        if ($parms == 'new')
            $parms          = array('idet'  => 0);
        else
        if (is_int($parms))
            $parms          = array('idet'  => $parms);
        
        // deprecated parameters have been converted to arrays  
        if (is_array($parms))
        {                   // array parameter
            $idet                   = 0;
            $validFields            = true;
            $needInsert             = false;
            foreach($parms as $field => $value)
            {
                $fieldLc            = strtolower($field);
                if ($fieldLc == 'idet')
                {
                    $idet           = $value;
                }
                else
                if ($fieldLc == 'eventtype' && $value == '')
                {
                    $idet           = 1;
                }
                else
                if (!array_key_exists($fieldLc, self::$initRow))
                {
                    $validFields    = false;
                    $warn       .= "<p>EventType::__construct: " . __LINE__ .
                            " Invalid field name '$field' ignored.</p>\n";
                }
            }

            // check for full database row
            if ($validFields && count($parms) == count(self::$initRow))
            {
                $dbrow              = $parms;
                $needInsert         = false;
            }
            else
            if ($idet > 0)
            {                       // IDET value
                // construct the query of the event type table
                $query              = "SELECT * FROM tblET WHERE IDET=:idet";
                $sqlParms           = array('idet' => $idet);
                $stmt               = $connection->prepare($query);
                $queryText          = debugPrepQuery($query, $sqlParms);
                
                // query the database
                if ($stmt->execute($sqlParms))
                {                   // success
                    $row            = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($debug)
                        $warn       .= "<p>EventType.inc: " . __LINE__ . 
                                        " $queryText</p>";
                    
                    if (is_array($row))
                    {               // found matching event type record
                        $dbrow      = $row;
                        $needInsert = false;
                    }               // found matching event type record
                    else
                    {               // record not found
                        $dbrow      = self::$initRow;
                        $dbrow['idet']  = $idet;
                        $needInsert = true;
                    }               // record not found
                }                   // success
                else
                {                   // error performing query
                    throw new Exception("EventType::__construct: " . 
                                "query='$queryText' " .
                                print_r($stmt->errorInfo(),true));
                }                   // error performing query
            }                       // valid code
            else
            {                       // create new default record
                $this->msg      .= "EventType::__construct: constructor needs non-zero IDET value because this class does not implement Auto-Increment. ";
                $dbrow              = self::$initRow;
                $needInsert         = true;
            }                       // create new default record
        }                           // numeric identifier of record
        else
        {                           // unexpected parameter type
            $this->msg      .= "EventType::__construct: called with id type " .
                                gettype($id) . ". ";
            $dbrow                  = self::$initRow;
            $needInsert             = true;
        }                           // unexpected parameter type

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'tblET');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('EventType Record constructed:');
    }       // EventType::__construct

}       // class EventType 

