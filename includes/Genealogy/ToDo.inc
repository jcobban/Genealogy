<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ToDo.inc                                                            *
 *                                                                      *
 *  Definition of a class representing an item of research work to be   *
 *  done in a genealogical database.  This class provides access to     *
 *  the information in a row of the table tblTD.                        *
 *                                                                      *
 *  CREATE TABLE `tblTD` (                                              *
 *      `IDTD`          INT(10) NOT NULL AUTO_INCREMENT,                *
 *      `ToDoType`      TINYINT(3) DEFAULT NULL,                        *
 *      `IDIR`          INT(10) DEFAULT NULL,                           *
 *      `IDTC`          INT(10) DEFAULT NULL,                           *
 *      `IDTL`          INT(10) DEFAULT NULL,                           *
 *      `ToDoName`      VARCHAR(255) DEFAULT NULL,                      *
 *      `UserName`      VARCHAR(64) DEFAULT NULL,                       *
 *      `OpenedD`       VARCHAR(100) DEFAULT NULL,                      *
 *      `OpenedSD`      INT(10) DEFAULT NULL,                           *
 *      `ReminderD`     VARCHAR(100) DEFAULT NULL,                      *
 *      `ReminderSD`    INT(10) DEFAULT NULL,                           *
 *      `ClosedD`       VARCHAR(100) DEFAULT NULL,                      *
 *      `IDAR`          INT(10) DEFAULT NULL,                           *
 *      `Status`        TINYINT(3) DEFAULT NULL,                        *
 *      `Priority`      TINYINT(3) DEFAULT NULL,                        *
 *      `Desc`          LONGTEXT,                                       *
 *      `Results`       LONGTEXT,                                       *
 *      `FilingRef`     VARCHAR(50) DEFAULT NULL,                       *
 *      `Tag1`          TINYINT(3) DEFAULT NULL,                        *
 *      `Used`          TINYINT(3) DEFAULT NULL,                        *
 *      `qsTag`         TINYINT(3) DEFAULT NULL,                        *
 *      PRIMARY KEY (`IDTD`),                                           *
 *      KEY `ToDoName` (`ToDoName`),                                    *
 *      KEY `IDIR` (`IDIR`),                                            *
 *      FOREIGN KEY `Person` (`IDIR`) REFERENCES `tblIR` (`IDIR`),
 *      KEY `IDTC` (`IDTC`),                                            *
 *      KEY `IDTL` (`IDTL`) )                                           *
 *      FOREIGN KEY `Location` (`IDTL`) REFERENCES `tblLR` (`IDLR`),    *
 *      KEY `UserName` (`UserName`),                                    *
 *      FOREIGN KEY `User` (`UserName`) REFERENCES `Users` (`UserName`))*
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/10      created                                         *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2017/08/04      change class LegacyAddress to class Address     *
 *                      constructor supports array parameter for search * 
 *      2017/08/15      change class LegacyToDo to class ToDo           *
 *      2017/11/21      use prepared statements                         *
 *      2017/12/18      add support for temporary fields                *
 *      2019/08/27      add username field                              *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2020/12/25      correct minor errors                            *
 *                      remove throw from constructor                   *
 *      2021/04/23      do not include username in search by IDTD       *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class ToDo                                                          *
 *                                                                      *
 *  Definition of a class implementing behavior of research work to be  *
 *  done in a genealogical tree.                                        *
 *                                                                      *
 ************************************************************************/

class ToDo extends Record
{
    /********************************************************************
     *  ToDo::$initRow                                                  *
     *                                                                  *
     *  array containing default values for all fields in a new record  *
     ********************************************************************/
    protected static    $initRow    = array(
                            'idtd'          => 0,
                            'todotype'      => 0,
                            'idir'          => 0,
                            'idtc'          => 0,
                            'idtl'          => 1,
                            'todoname'      => '',
                            'todoname'      => '',
                            'openedsd'      => -99999999,
                            'openedd'       => '',
                            'remindersd'    => -99999999,
                            'reminderd'     => '',
                          //'closedsd'  => -99999999,
                            'closedd'       => '',
                            'idar'          => 0,
                            'status'        => 1,
                            'priority'      => 1,
                            'desc'          => '',
                            'results'       => '',
                            'filingref'     => '',
                            'tag1'          => 0,
                            'qstag'         => 0,
                            'used'          => 0);

    /********************************************************************
     *  ToDo::$info                                                     *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                        'table'             => 'tblTD',
                        'name'              => 'ToDos',
                        'prime'             => 'idtd',
                        'srvmg'             => true,
                        'fldcount'          => 20,
                        'order'             => array('IDIR','OpenedSD','UserName', 'ToDoName'),
                        'classname'         => 'ToDo');

    /********************************************************************
     *  ToDo::$translate                                                *
     *                                                                  *
     *  array containing synonyms for field names                       *
     ********************************************************************/
    protected static    $translate  = array(
                            'name'          => 'todoname',
                            'type'          => 'todotype',
                            'category'      => 'idtc',
                            'notes'         => 'desc');

    /********************************************************************
     *  constants for values of field 'todotype'                        *
     ********************************************************************/
    const TD_TODOITEM                   = 0;
    const TD_CORRESPONDENCE             = 1;
    const TD_OTHER                      = 2;

    /********************************************************************
     *  constants for values of field 'idtc'                            *
     ********************************************************************/
    const TC_NULL                       = 1;
    const TC_BIRTH_CERTIFICATE          = 2;
    const TC_CEMETERY                   = 3;
    const TC_CENSUS                     = 4;
    const TC_CHURCH_RECORD              = 5;
    const TC_COURT_RECORD               = 6;
    const TC_DEATH_CERTIFICATE          = 7;
    const TC_IMMIGRATION                = 8;
    const TC_LAND_DEED                  = 9;
    const TC_LAND_PATENT                = 10;
    const TC_MARRIAGE_CERTIFICATE       = 11;
    const TC_MILITARY_RECORD            = 12;
    const TC_NATURALIZATION             = 13;
    const TC_OBITUARY                   = 14;
    const TC_PROBATE_ESTATE             = 15;
    const TC_SHIP_PASSENGER_LIST        = 16;
    const TC_SS_DEATH_INDEX             = 17;
    const TC_TAX_RECORDS                = 18;
    const TC_TOMBSTONE                  = 19;
    const TC_WILL                       = 20;

    /********************************************************************
     *  $intCategory    interpret the category field (IDTC)             *
     *                                                                  *
     *  See the localization tables in templates/Trantabll.html         *
     *  <div id="idtcText">                                             *
     ********************************************************************/
    static $intCategory = array(
                         1  => '',
                         2  => 'Birth Certificate',
                         3  => 'Cemetery',
                         4  => 'Census',
                         5  => 'Church Record',
                         6  => 'Court Record',
                         7  => 'Death Certificate',
                         8  => 'Immigration',
                         9  => 'Land Deed',
                        10  => 'Land Patent',
                        11  => 'Marriage Certificate',
                        12  => 'Military Record',
                        13  => 'Naturalization',
                        14  => 'Obituary',
                        15  => 'Probate/Estate',
                        16  => 'Ship Passenger List',
                        17  => 'SS Death Index',
                        18  => 'Tax Records',
                        19  => 'Tombstone',
                        20  => 'Will');

    /********************************************************************
     *  function ToDo::__construct                                      *
     *                                                                  *
     *  Given the identifier extract information about a research work  *
     *  to do item.                                                     *
     *                                                                  *
     *  Input:                                                          *
     *      $parms    * a database record presented as an array, or     *
     *                * array('idtd' => $idtd) to select an existing    *
     *                  record                                          *
     *                * array('idir' => 0, 'todoname' => $name)         *
     *                  to create a general work item                   *
     *                * array('idir' => $idir, 'todoname' => $name)     *
     *                  to create a new work item for a specific Person *
     *                  or access an existing record with the same      *
     *                  key values                                      *
     *                                                                  *
     *  Returns object containing work to do record.                    *
     *  Errors are reported in $this->msg.                              *
     ********************************************************************/
    function __construct($id = array())
    {
        global  $debug;
        global  $warn;
        global  $connection;
        global  $userid;

        // reformat old-style parameters into array format
        $parms              = $id;
        if (!is_array($parms))
        {
            if (is_string($parms) && strtolower($parms) == 'new')
                $parms      = array('idtd'      => 0);
            else
            if (is_string($parms))
            {
                if (ctype_digit($parms))
                    $parms  = array('idtd'      => (int)$parms);
                else
                    $parms  = array('idir'      => 0,
                                    'todoname'  => $parms,
                                    'username'  => $userid);
            }
            else
            if (is_int($parms))
                $parms      = array('idtd'  => $parms);
        }

        if (!is_array($parms))
        {
            $this->msg      .= "ToDo::__construct: " .
                                "called with " . gettype($parms) .
                                " value=" . var_dump($parms) . ". ";
            $parms          = array();
        }

        $dbrow              = null;

        // current date in internal forms
        $currdate           = getdate();
        $ldate              = 1000000*$currdate['mday'] +
                              10000*$currdate['mon'] +
                              $currdate['year'];
        if ($ldate < 10000000)
            $ldate          = '000' . $ldate . '00000000';
        else
            $ldate          = '00' . $ldate . '00000000';
        $sdate              = 10000*$currdate['year'] +
                              100*$currdate['mon'] +
                              $currdate['mday'];

        $idtd               = null;
        $idir               = null;
        $todoname           = null;
        $username           = $userid;
        $where              = '';
        $sqlParms           = array();
        $and                = 'WHERE ';
        $createRecord       = true;
        $this->msg          = '';

        foreach($parms as $fieldname => $value)
        {                   // loop through parameters
            $fieldLc        = strtolower($fieldname);
            if (array_key_exists($fieldLc, self::$translate))
                $fieldLc    = self::$translate[$fieldLc];
            switch($fieldLc)
            {               // act on specific parameters
                case 'idtd':
                {
                    if ((is_int($value) || ctype_digit($value)) && 
                        $value > 0)
                    {
                        $idtd               = (int)$value;
                        $createRecord       = false;
                        $where              .= "$and`IDTD`=:idtd";
                        $sqlParms['idtd']   = $idtd;
                        $and                = ' AND ';
                        break 2;    // ignore any other parameters
                    }
                    else
                        $this->msg      .= "'IDTD' value $value invalid. ";
                    break;
                }           // idtd

                case 'idir':
                {
                    if ((is_int($value) || ctype_digit($value)) && 
                        $value > 0)
                    {
                        $idir               = (int)$value;
                        $where              .= "$and`IDIR`=:idir";
                        $sqlParms['idir']   = $idir;
                        $and                = ' AND ';
                    }
                    else
                        $this->msg      .= "'IDIR' value $value invalid. ";
                    break;
                }           // IDIR

                case 'todoname':
                {
                    $todoname               = $value;
                    $where                  .= "$and`ToDoName`=:todoname";
                    $sqlParms['todoname']   = $todoname;
                    $and                    = ' AND ';
                    break;
                }           // todoname

                case 'username':
                {
                    $username               = $value;
                    break;
                }           // username

                case 'todotype':
                case 'idtc':
                case 'idtl':
                case 'openedsd':
                case 'openedd':
                case 'remindersd':
                case 'reminderd':
                case 'closeedd':
                case 'idar':
                case 'status':
                case 'priority':
                case 'desc':
                case 'results':
                case 'filingred':
                case 'tag1':
                case 'qstag':
                case 'used':
                {           // other valid field names not used to search
                    break;
                }           // other valid field names

                default:
                {
                    $this->msg  .= "'$fieldname' unsupported. ";
                    break;
                }
            }               // act on specific search keys
        }                   // loop through parameters

        // return record for current user if not specific record
        if (is_null($idtd))
        {
            $where                  .= "$and(`UserName`=:username OR `UserName`='')";
            $sqlParms['username']   = $username;
        }

        if (count($parms) >= count(ToDo::$initRow) &&
            strlen($this->msg) == 0)
        {                   // complete row from database
            $dbrow                  = $parms;
            $needInsert             = $idtd == 0;
            if ($needInsert)
            {
                $dbrow['openedsd']  = $sdate;
                $dbrow['openedd']   = $ldate;
            }
        }                   // complete row from database
        else
        if (strlen($where) > 0)
        {                   // search for existing record
            // construct the query of the work to do table
            if (is_null($idtd) && is_null($idir))
                $where              .= "$amd IDIR=0";
            $query                  = "SELECT * FROM tblTD " . $where;
            
            // query the database
            $stmt                   = $connection->prepare($query);
            $queryText              = debugPrepQuery($query, $sqlParms);

            if ($stmt->execute($sqlParms))
            {               // successful execute
                if ($debug)
                    print "<p>ToDo.inc::__construct: " . __LINE__ .
                            " $queryText</p>";
                
                $result             = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_null($result))
                {       // no matching record
                    $dbrow                  = self::$initRow;
                    $dbrow['openedsd']      = $sdate;
                    $dbrow['openedd']       = $ldate;
                    $dbrow['username']      = $username;
                    $this->needInsert       = true;
                }       // no matching record
                else
                {
                    $dbrow                  = $result;
                    $this->needInsert       = false;
                }
            }       // successful execute
            else
            {       // error performing query
                $errors             = print_r($stmt->errorInfo(), true);
                $this->msg          .= "ToDo::__construct: " . __LINE__ .
                                        " '$queryText', $errors. ";
            }       // error performing query
        }           // existing record
        else
        {           // create a new record
            $dbrow                  = self::$initRow;
            $dbrow['openedsd']      = $sdate;
            $dbrow['openedd']       = $ldate;
            $dbrow['username']      = $username;
            $this->needInsert       = true;
        }           // create new record

        // invoke constructor of base class
        if (!is_array($dbrow))
            $dbrow                  = ToDo::$initRow;
        parent::__construct($dbrow,
                            'tblTD');

        // initialize field values from parameters
        if (count($parms) < count(ToDo::$initRow))
        foreach($parms as $fieldname => $value)
        {
            $fieldLc                = strtolower($fieldname);
            if (array_key_exists($fieldLc, self::$translate))
                $fieldLc            = self::$translate[$fieldLc];
            if ($fieldLc != 'idtd')
                $dbrow[$fieldLc]    = $value;
        }       // loop through parameters

        // diagnostic output if debug is set
        $this->dump('To Do Record constructed:');
    }       // function ToDo::__construct

    /********************************************************************
     *  function ToDo::getIdtd                                          *
     *                                                                  *
     *  Retrieve the unique numeric key of this instance of             *
     *  ToDo.                                                           *
     *                                                                  *
     *  Note that this value is not available for a new ToDo            *
     *  record, one created with index value 0 or a string name that    *
     *  does not match any existing work to do, until after the         *
     *  function save() has been called, because only then does the     *
     *  database server assign a value for the key.  Until then this    *
     *  method returns 0.                                               *
     ********************************************************************/
    function getIdtd() { return $this->row['idtd']; }

    /********************************************************************
     *  function ToDo::getName                                          *
     *                                                                  *
     *  Get the name of the unit of research work to do.                *
     ********************************************************************/
    function getName($options   = null)
    {
        if ($this->row['idir'] > 0)
            return $this->row['idir'] . ': ' . $this->row['todoname'];
        else
            return $this->row['todoname'];
    }       // function ToDo::getName

    /********************************************************************
     *  function ToDo::set                                              *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change                         *
     *      $value      value to set it to                              *
     *                                                                  *
     *  Returns:                                                        *
     *      Former value of field                                       *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function set($field, $value)
    {
        $fieldLc        = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'idtd':
            {               // generated by server, cannot be changed
                return $this->row['idtd'];
            }               // generated by server, cannot be changed

            case 'tootype':
            {
                if (is_int($value) || ctype_digit($value))
                    if ($value >= 0 && $value <= 2)
                        return parent::set('idtd', $value);
                return $this->row['idtd'];
            }

            case 'idir':
            {               // reference to Person
                if (is_int($value) || ctype_digit($value))
                    if ($value > 0)
                        return parent::set('idir', $value);
                return $this->row['idir'];
            }               // reference to Person

            case 'idtc':
            {               // document category
                if (is_int($value) || ctype_digit($value))
                    if ($value >= 0 && $value <= 20)
                        return parent::set('idtc', $value);
                return $this->row['idtc'];
            }               // documment category

            case 'priority':
            {
                if (is_int($value) || ctype_digit($value))
                    if ($value >= 0 && $value <= 2)
                        return parent::set('priority', $value);
                return $this->row['priority'];
            }

            case 'status':
            case 'tag1':
            case 'qstag':
            case 'used':
            {               // fields that can only be 0 or 1
                if ($value)
                    return parent::set($fieldLc, 1);    // true
                else
                    return parent::set($fieldLc, 0);    // false
            }               // fields that can only be 0 or 1

            case 'openedd':
            case 'openesd':
            {               // set when record created, cannot be changed
                return $this->row['openedd'];
            }               // set when record created, cannot be changed

            case 'reminderd':
            {               // internal date format
                if ($value instanceof LegacyDate)
                {           // parameter is an instance of LegacyDate
                    $date   = $value;
                }           // parameter is an instance of LegacyDate
                else
                {           // convert the value to instance of LegacyDate
                    $date   = new LegacyDate(' ' . $value);
                }           // convert the value to instance of LegacyDate
                parent::set('remindersd', $date->getSortDate());
                return parent::set('reminderd', $date->getDate());
            }               // internal date format

            case 'closedd':
            {               // text
                return parent::set('closedd', $value);
            }               // text

            case 'idtl':
            {               // value is key of location table
                // the main table of Locations is used instead of the
                // table of ToDo locations
                if ($value instanceof Location)
                {           // parameter is an instance of Location
                    return parent::set('idtl', $value['idlr']);
                }           // parameter is an instance of Location
                else
                if (is_int($value) || ctype_digit($value))
                    return parent::set('idtl', (int)$value);
                else
                if (is_string($value))
                {
                    $location       = new Location(array('name' => $value));
                    if (!$location->isExisting())
                        $location->save();
                    return parent::set('idtl', $location['idlr']);
                }
                else
                    return $this->row['idtl'];
            }               // value is key of location table

            case 'idar':
            {               // value is IDAR
                if ($value instanceof Address)
                {           // parameter is an instance of Address
                    return parent::set('idar', $value['idar']);
                }           // parameter is an instance of Address
                else
                if (is_int($value) || ctype_digit($value))
                    return parent::set('idar', $value);
                else
                {
                    $msg    .= "ToDo::set: invalid value '$value' for field '$field'";
                    return $this->row['idar'];
                }
            }               // value is IDAR

            case 'openedsd':
            case 'remindersd':
            {               // internal date format
                // do not change the value, it is set by set('....d')
                return $this->row[$field];
            }               // internal date format

            default:
            {               // no validation implemented yet
                return parent::set($field, $value);
            }               // no validation implemented yet
        }                   // switch on field name
    }       // function ToDo::set

    /********************************************************************
     *  function ToDo::toXml                                            *
     *                                                                  *
     *  Emit an XML representation of the record.                       *
     *                                                                  *
     *  Most fields in the record are represented by a child element    *
     *  with the name of the field containing the internal value as text*
     *                                                                  *
     *  Each field whose value is an instance of LegacyDate appears as  *
     *  two elements.  The internal encoded string value appears in an  *
     *  element with 'c' appended to the field name, while the element  *
     *  with the original field name contains an external textual       *
     *  (human-readable) representation of the date.  Note that the     *
     *  "sorted date" field appears with its internal value.            *
     *                                                                  *
     *  Parameters:                                                     *
     *      $top        tag name for top node, default 'todo'           *
     *      $print      if true print the output, else just return it   *
     *      $options        match signature                             *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/
    function toXml($top     = 'todo',
                   $print   = true,
                   $options = 0)
    {
        $idtd       = $this->row['idtd'];
        $retval     = "<$top idtd='$idtd'>\n";

        foreach($this->row as $fld => $value)
        {               // loop through all fields
            switch($fld)
            {
                case 'idar':
                {       // IDAR of repository
                    $retval     .= "    <$fld>" . $value . "</$fld>\n";
                    if ($value > 0)
                    {
                        $repo   = new Address(array('idar' => $value));
                        if ($repo->isExisting())
                            $retval .= $repo->toXml('repository', false);
                    }
                    break;
                }

                case 'openedd':
                case 'reminderd':
                {       // internal date format
                    $date       = new LegacyDate($value);
                    $retval     .= "    <{$fld}c>" . htmlentities($value,ENT_XML1) . 
                                    "</{$fld}c>\n";
                    $value      = $date->toString();
                    $retval     .= "    <$fld>" . htmlentities($value,ENT_XML1) . 
                                    "</$fld>\n";
                    break;
                }       // internal date format

                case 'idtc':
                {       // document type
                    $meaning        = htmlentities(self::$intCategory[$value],ENT_XML1);
                    $retval         .= "    <$fld>$value=$meaning</$fld>\n";
                    break;
                }

                default:
                {       // fields passed back with no interpretation
                    $retval .= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
                    break;
                }       // fields passed back with no interpretation
            }           // switch on field name
        }               // loop through all fields
        
        $retval .= "</$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }       // function ToDo::toXml

}       // class ToDo 

