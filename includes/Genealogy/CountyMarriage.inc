<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountyMarriage.inc                                                  *
 *                                                                      *
 *  CREATE TABLE `CountyMarriages` (                                    *
 *      `RegDomain`   VARCHAR(6) NOT NULL DEFAULT 'CAON',               *
 *      `Volume`      INT(11) NOT NULL,                                 *
 *      `ReportNo`    DECIMAL(9,1) NOT NULL,                            *
 *      `ItemNo`      INT(11) NOT NULL,                                 *
 *      `Role`        CHAR(1) NOT NULL DEFAULT '',                      *
 *      `GivenNames`  VARCHAR(64) DEFAULT NULL,                         *
 *      `Surname`     VARCHAR(64) DEFAULT NULL,                         *
 *      `SurnameSoundex` CHAR(4) DEFAULT NULL,                          *
 *      `Residence`   VARCHAR(64) DEFAULT '',                           *
 *      `Date`        VARCHAR(16) DEFAULT '',                           *
 *      `LicenseType` CHAR(1) DEFAULT 'L',                              *
 *      `WitnessName` VARCHAR(48) DEFAULT '',                           *
 *      `IDIR`        INT(11) DEFAULT NULL,                             *
 *      `Remarks`     TEXT,                                             *
 *      `Age`         VARCHAR(16) DEFAULT NULL,                         *
 *      `BirthPlace`  VARCHAR(64) DEFAULT '',                           *
 *      `FatherName`  VARCHAR(64) DEFAULT '',                           *
 *      `MotherName`  VARCHAR(64) DEFAULT '',                           *
 *      PRIMARY KEY (`RegDomain`,                                       *
 *          `Volume`,                                                   *
 *          `ReportNo`,                                                 *
 *          `ItemNo`,                                                   *
 *          `Role`),                                                    *
 *      KEY `GivenNames` (`GivenNames`),                                *
 *      KEY `Surname` (`Surname`),                                      *
 *      KEY `SurnameSoundex` (`SurnameSoundex`),                        *
 *      KEY `Residence` (`Residence`),                                  *
 *      KEY `IDIR` (`IDIR`) )                                           *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *  Definition of a class representing a record about a spouse in       *
 *  a marriage registered in pre-confederation Ontario  It represents   *
 *  the information in a row of the table CountyMarriages.              *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/29      created                                         *
 *      2016/03/19      add method compare                              *
 *      2016/05/10      obsolete global table $domains was declared     *
 *      2017/09/20      use common translate table for field names      *
 *                      support fractional report numbers               *
 *      2017/10/10      complete migration to prepared statements       *
 *                      permit updating identification fields by set    *
 *      2017/12/18      add support for temporary fields                *
 *      2019/04/18      support method getLastSqlCmd                    *
 *      2019/05/18      add $info                                       *
 *      2021/05/19      validate parameters to constructor              *
 *                      update $this->msg instead of throwing Exception *
 *                      correct handling of reports '..5'               *
 *      2022/12/13      remove field name prefixes                      *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class CountyMarriage                                                *
 *                                                                      *
 *  Definition of a class implementing behavior for pre-confederation   *
 *  County Marriages within a genealogy database.                       *
 ************************************************************************/

class CountyMarriage extends Record
{

    /********************************************************************
     *  CountyMarriage::$initRow                                        *
     *                                                                  *
     *  static array containing default values for a new record         *
     ********************************************************************/
    protected static    $initRow    = array(
                        'regdomain'         => 'CAON',
                        'volume'            => 1,
                        'reportno'          => 1,
                        'itemno'            => '',
                        'role'              => '',
                        'givennames'        => '',
                        'surname'           => '',
                        'surnamesoundex'    => '',
                        'age'               => '',
                        'residence'         => '',
                        'birthplace'        => '',
                        'fathername'        => '',
                        'mothername'        => '',
                        'date'              => '',
                        'licensetype'       => 'L',
                        'witnessname'       => '',
                        'idir'              => 0,
                        'remarks'           => '');

    /********************************************************************
     *  CountyMarriage::$info                                           *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'CountyMarriages',
                'name'              => 'CountyMarriages',
                'prime'             => array('regdomain','volume','reportno','itemno','role'),
                'srvmg'             => false,
                'fldcount'          => 6,
                'order'             => '`Regdomain`,`Volume`,`Reportno`,`Itemno`,`Role` DESC',
                'classname'         => 'CountyMarriage');

    /********************************************************************
     *  $translate      static array containing common translations of  *
     *                  field names                                     *
     ********************************************************************/
    protected static    $translate = array(
                        'm_regdomain'       => 'regdomain',
                        'm_domain'          => 'regdomain',
                        'domain'            => 'regdomain',
                        'm_volume'          => 'volume',
                        'm_reportno'        => 'reportno',
                        'm_itemno'          => 'itemno',
                        'm_role'            => 'role',
                        'm_givennames'      => 'givennames',
                        'm_surname'         => 'surname',
                        'm_surnamesoundex'  => 'surnamesoundex',
                        'm_age'             => 'age',
                        'm_residence'       => 'residence',
                        'm_birthplace'      => 'birthplace',
                        'm_fathername'      => 'fathername',
                        'm_mothername'      => 'mothername',
                        'm_date'            => 'date',
                        'm_licensetype'     => 'licensetype',
                        'm_witnessname'     => 'witnessname',
                        'm_idir'            => 'idir',
                        'm_remarks'         => 'remarks');

    /********************************************************************
     *  $defaultOrder                                                   *
     *                                                                  *
     *  Default sort order for reports                                  *
     ********************************************************************/
    protected static $defaultOrder  =
                "`RegDomain`, `Volume`, `ReportNo`, `ItemNo`, `Role` DESC";

    /********************************************************************
     *  CountyMarriage::__construct                                     *
     *                                                                  *
     *  Create an instance of a class that describes a spouse           *
     *  in a county marriage registration.                              *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array containing either          *
     *                  a complete row from the database or             *
     *                      array('Domain'      => $domain,             *
     *                            'Volume'      => $volume,             *
     *                            'ReportNo'    => $reportno,           *
     *                            'ItemNo'      => $itemno,             *
     *                            'Role'        => $role)               *
     *                  with the case of the parameter names ignored    *
     *                                                                  *
     *  Returns object containing county marriage individual record.    *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        $needInsert         = false;
        $this->table        = 'CountyMarriages';
        $this->msg          = '';

        // validate parameters
        if (is_array($parms))
        {       // associative parms or row returned from database query
            if (count($parms) >= count(self::$initRow))
            {       // row returned from database query
                foreach($parms as $field => $value)
                {   // validate all field names
                    if (!array_key_exists($field, self::$initRow))
                        $this->msg    .= "Invalid field name `$field` in supplied record. ";
                }   // field names

                if (strlen($this->msg) == 0)
                {
                    $dbrow          = $parms;
                    $needInsert     = false;
                }
            }       // row returned from database query
            else
            {       // associative array of search parms
                $where              = '';
                $and                = 'WHERE ';
                $sqlParms           = array();
                foreach($parms as $field => $value)
                {   // loop through all supplied search parms
                    $fieldLc        = strtolower($field);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc    = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {   // act on specific parameter names
                        case 'domain':
                        case 'regdomain':
                        {
                            if ($value instanceof Domain)
                            {
                                $where          .= $and . 'RegDomain=?';
                                $sqlParms[]     = $value['domain'];
                                $and            = ' AND ';
                                break;
                            }

                            if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                            {
                                $where          .= $and . 'RegDomain=?';
                                $sqlParms[]     = $value;
                                $and            = ' AND ';
                            }
                            else
                                $this->msg      .=
                        "CountyMarriage: invalid Domain='$value'. ";
                            break;
                        }   // domain name

                        case 'volume':
                        {
                            if (ctype_digit($value))
                            {
                                $where          .= $and . 'Volume=?';
                                $sqlParms[]     = $value;
                                $and            = ' AND ';
                            }
                            else
                                $this->msg      .=
                        "CountyMarriage: invalid Volume='$value'. ";
                            break;
                        }   // volume number

                        case 'reportno':
                        {
                            if ($value instanceof CountyMarriageReport)
                            {
                                $report         = $value;
                                $where          .= $and .
                        'RegDomain=? AND Volume=? AND ReportNo=?';
                                $sqlParms[]     = $report['regdomain'];
                                $sqlParms[]     = $report['volume'];
                                $sqlParms[]     = $report['reportno'];
                                $and            = ' AND ';
                                break;
                            }

                            if (is_string($value))
                            {
                                // some idiot put in fractional values for
                                // report numbers!
                                $value      = trim($value);
                                $matches    = array();
                                $rxr        = preg_match('#(\d+)(\x{00BD}|\.5|1/2)#u',
                                    $value, $matches);
                                if ($rxr)
                                    $value  = intval($matches[0]) + 0.5;
                            }
                            if (is_numeric($value))
                            {
                                $where          .= $and . 'ReportNo=?';
                                $sqlParms[]     = $value;
                                $and            = ' AND ';
                            }
                            else
                                $this->msg      .=
                        "CountyMarriage: invalid ReportNo='$value'. ";
                            break;
                        }   // report number

                        case 'itemno':
                        {
                            if (is_int($value) || ctype_digit($value))
                            {
                                $where          .= $and . 'ItemNo=?';
                                $sqlParms[]     = $value;
                                $and            = ' AND ';
                            }
                            else
                                $this->msg      .= 
                        "CountyMarriage: invalid ItemNo='$value'. ";
                            break;
                        }   // report number

                        case 'role':
                        {
                            if ($value == 'b' ||
                                $value == 'B' ||
                                $value == 'g' ||
                                $value == 'G')
                            {
                                $where          .= $and . '`Role`=?';
                                $sqlParms[]     = $value;
                                $and            = ' AND ';
                            }
                            else
                                $this->msg      .=
                        "CountyMarriage: invalid Role='$value'. ";
                            break;
                        }   // report number

                        default:
                        {
                            if (!array_key_exists($fieldLc, self::$initRow))
                                $this->msg    .=
                                            "Invalid field name `$field`. ";
                            break;
                        }   // unrecognized
                    }   // act on specific parameter names
                }   // loop through all supplied search parms

                if (strlen($this->msg) == 0)
                {           // no errors so far
                    $query              = "SELECT * FROM CountyMarriages " .
                                          $where;
                    $stmt               = $connection->prepare($query);
                    $queryText          = debugPrepQuery($query, $sqlParms);
                    $this->lastSqlCmd   = $queryText;
                    if ($stmt->execute($sqlParms))
                    {       // successful select
                        if ($debug)
                            $warn   .= '<p>CountyMarriage.inc: ' . __LINE__ .
                                  " query='$queryText'</p>\n";
                        $row            = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($row))
                        {       // match existing row in table
                            $dbrow      = $row;
                            $needInsert = false;
                        }       // match existing row in table
                        else
                        {       // no match, create new row
                            $dbrow      = self::$initRow;
                            foreach($parms as $field => $value)
                            {   // loop through all search parms
                            $fieldLc    = strtolower($field);
                            if (array_key_exists($fieldLc, self::$translate))
                                $fieldLc        = self::$translate[$fieldLc];
                            if (!is_null($fieldLc))
                                $dbrow[$fieldLc]    = $value;
                            }   // loop through all search parms
                            $needInsert = true;
                        }       // no match, create new row
                    }       // successful select
                    else
                    {       // execute failed
                        $this->msg    .= "execute '$queryText' failed, errorInfo=" .
                               print_r($stmt->errorInfo(), true);
                    }       // execute failed
                }       // no errors so far
            }           // associative array of search parms
        }           // parameter is an array
        else
        {           // invalid parameter
             $this->msg   .= "called with parms=" . print_r($parms, true);
        }           // invalid parameter

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'CountyMarriages');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('CountyMarriage Record constructed:');
    }       // CountyMarriage::__construct

    /********************************************************************
     *  CountyMarriage::save                                            *
     *                                                                  *
     *  Save changes made to the CountyMarriage record into             *
     *  the database.                                                   *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format within <cmd> tags.                       *
     *                  if a string use it as tag name                  *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1           if the method updated the database record       *
     *      0           if the database did not need to be updated      *
     *                                                                  *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $userid;
        global  $connection;

        $count              = false;
        if (is_string($xml))
        {           // string tag name
            $tagname        = $xml;
            $xml            = true;
        }           // string tag name
        else
        if ($xml)
        {           // true
            $tagname        = 'cmd';
        }           // true

        if ($this->needInsert)
        {           // create new record
            $fldnames       = '';
            $comma          = '';
            $values         = '';
            $sqlParms       = array();
            foreach($this->row as $fld => $value)
            {
                $fldnames   .= $comma . "`$fld`"; 
                if ($fld == 'surnamesoundex')
                {
                    $values .= $comma . 'LEFT(SOUNDEX(:surname),4)';
                }
                else
                {
                    $values             .= $comma . ':' . $fld;
                    $sqlParms[$fld]     = $value;
                }
                $comma  = ", ";
            }           // loop through all fields in record

            // insert the new record into the database
            $insert             = "INSERT INTO CountyMarriages ($fldnames) " .
                                    "VALUES($values)";
            $stmt               = $connection->prepare($insert);
            $insertText         = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd   = $insertText;
            if ($stmt->execute($sqlParms))
            {           // successful insert
                $count          = $stmt->rowCount();
                if ($xml)
                    print "<$tagname count='$count'>$insertText</$tagname>\n";

                if ($debug)
                    $warn       .= '<p>CountyMarriage.inc: ' . __LINE__ .
                                    " cmd='$insertText'</p>\n" .
                                       "<p>count=$count</p>\n";

                // log the update
                logSqlUpdate($insert,
                             $sqlParms,
                             $count,
                             '',
                             $this->toJson(false));

                // clean up internal state
                $this->changed      = array();  // reset
                $this->needInsert   = false;
            }           // successful insert
            else
            {           // error inserting record
                $errorInfo          = $stmt->errorInfo();
                $errtext    = "CountyMarriage::save: '$insert'" .
                            ", sqlParms=" . var_export($sqlParms, true) .
                            ", errorInfo=" . var_export($errorInfo,true);
                $msg        .= "<p>$errtext</p>\n";
                error_log($errtext);
                return false;
            }           // error inserting record
        }           // create new record
        else
        {           // update existing record
            $set            = '';
            $sqlParms       = array();
            $comma          = 'SET ';
            foreach($this->changed as $fld => $value)
            {           // loop through all changed fields in record
                if ($fld == 'surname')
                {
                    $set            .= $comma .
                                       "`Surname`=:surname";
                    $sqlParms['surname']    = $value;
                    $set     .= ",`SurnameSoundex`=LEFT(SOUNDEX(:surname),4)";
                }
                else
                if ($fld != 'surnamesoundex')
                {
                    $set            .= $comma . "`$fld`=:$fld";
                    $sqlParms[$fld] = $value;
                }
                $comma  = ', ';
            }           // loop through all changed fields in record

            // clean up internal state
            $this->changed  = array();  // reset

            if (strlen($set) > 0)
            {           // changes made
                // construct the where clause
                // this new implementation permits changing the
                // values of the identification fields
                $sqlParms['odomain']    = $this->former['regdomain'];
                $sqlParms['ovolume']    = $this->former['volume'];
                $sqlParms['oreport']    = $this->former['reportno'];
                $sqlParms['oitem']      = $this->former['itemno'];
                $sqlParms['orole']      = $this->former['role'];
                $where          = "RegDomain=:odomain AND " .
                                  "Volume=:ovolume AND " .
                                  "ReportNo=:oreport AND " .
                                  "ItemNo=:oitem AND " .
                                  "`Role`=:orole";

                // assemble the UPDATE command
                $update             = "UPDATE CountyMarriages " .
                                         $set . ' WHERE ' . $where;

                // perform the update
                $stmt               = $connection->prepare($update);
                $updateText         = debugPrepQuery($update, $sqlParms);
                $this->lastSqlCmd   = $updateText;
                if ($stmt->execute($sqlParms))
                {       // successful update
                    $count          = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>$updateText</$tagname>\n";

                    if ($debug)
                        $warn   .= '<p>CountyMarriage.inc: ' . __LINE__ .
                               " query='$updateText'</p>\n";

                    // log the update
                    logSqlUpdate($update,
                             $sqlParms,
                             0, // compound key
                             json_encode($this->former),
                             $this->toJson(false));
                }       // successful update
                else
                {       // error updating record
                    $errorInfo      = $stmt->errorInfo();
                    $errtext        = "CountyMarriage::save: '$update'" .
                            ", sqlParms=" . var_export($sqlParms, true) .
                            ", errorInfo=" . var_export($errorInfo,true);
                    $msg            .= "<p>$errtext</p>\n";
                    error_log($errtext);
                    return false;
                }       // error updating record
            }           // changes made
        }           // update existing record
        return $count;
    }       // CountyMarriage::save

    /********************************************************************
     *  CountyMarriage::compare                                         *
     *                                                                  *
     *  Compare two marriage records                                    *
     *                                                                  *
     *  Input:                                                          *
     *      $other          another instance of CountyMarriage          *
     *                                                                  *
     *  Returns:                                                        *
     *      > 0 if $other is greater than $this by date (in order)      *
     *      == 0 if $other is equal to $this by date                    *
     *      < 0 if $other is less than $this by date (out of order)     *
     ********************************************************************/
    function compare($other)
    {
        if ($other instanceof CountyMarriage)
        {           // comparing two marriage records
            $r1     = $this;
            $r2     = $other;
            $n1     = $r1->get('volume') * 10000000 +
                          $r1->get('reportno') * 1000 +
                          $r1->get('itemno');
            $k1     = $r1->get('regdomain') .
                          str_pad($n1,9,'0',STR_PAD_LEFT);
            if ($r1->get('role') == 'G')
                $k1 .= '0';
            else
                $k1 .= '1';
            $n2     = $r2->get('volume') * 10000000 +
                          $r2->get('reportno') * 1000 +
                          $r2->get('itemno');
            $k2     = $r2->get('regdomain') .
                          str_pad($n2,9,'0',STR_PAD_LEFT);
            if ($r2->get('role') == 'G')
                $k2 .= '0';     // groom before bride
            else
                $k2 .= '1';     // groom before bride   
            if ($k1 < $k2)
            {
                return -1;
            }
            else
            if ($k1 > $k2)
            {
                return 1;
            }
            return 0;
        }           // comparing two marriage records
        else
            throw new Exception("CountyMarriage: compare: " .
                            "parameter is not instance of CountyMarriage");
    }       // CountyMarriage::compare

}       // class CountyMarriage 
