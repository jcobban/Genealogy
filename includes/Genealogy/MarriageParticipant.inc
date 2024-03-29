<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  MarriageParticipant.inc                                             *
 *                                                                      *
 *  Definition of a class representing a participant in a marriage      *
 *  as recorded in a marriage licence, declaration of Banns, or         *
 *  a marriage registration.                                            *
 *  Participants include the Bride, the Groom, and the Minister.        *
 *  This class provides access to the information in a row              *
 *  of the table MarriageIndi.                                          *
 *                                                                      *
 *    CREATE TABLE `MarriageIndi` (                                     *
 *      `RegDomain` VARCHAR(4) NOT NULL DEFAULT 'CAON',                 *
 *      `RegYear` INT(11) NOT NULL,                                     *
 *      `RegNum` INT(11) NOT NULL,                                      *
 *      `Role` CHAR(1) NOT NULL DEFAULT '',                             *
 *      `Surname` VARCHAR(32) DEFAULT '',                               *
 *      `SurnameSoundex` CHAR(4) DEFAULT '',                            *
 *      `GivenNames` VARCHAR(48) DEFAULT '',                            *
 *      `Age` VARCHAR(16) DEFAULT NULL,                                 *
 *      `BYear` INT(11) DEFAULT NULL,                                   *
 *      `Residence` VARCHAR(128) DEFAULT NULL,                          *
 *      `BirthPlace` VARCHAR(128) DEFAULT NULL,                         *
 *      `MarStat` CHAR(1) DEFAULT '?',                                  *
 *      `Occupation` VARCHAR(64) DEFAULT '',                            *
 *      `FatherName` VARCHAR(64) DEFAULT '',                            *
 *      `MotherName` VARCHAR(64) DEFAULT '',                            *
 *      `Religion` VARCHAR(64) DEFAULT '',                              *
 *      `WitnessName` VARCHAR(64) DEFAULT '',                           *
 *      `WitnessRes` VARCHAR(128) DEFAULT NULL,                         *
 *      `Remarks` TEXT,                                                 *
 *      `IDIR` INT(10) UNSIGNED DEFAULT NULL,                           *
 *      PRIMARY KEY (`RegDomain`,                                       *
 *                   `RegYear`,                                         *
 *                   `RegNum`,                                          *
 *                   `Role`),                                           *
 *      KEY `Surname` (`Surname`),                                      *
 *      KEY `SurnameSoundex` (`SurnameSoundex`),                        *
 *      KEY `GivenNames` (`GivenNames`),                                *
 *      KEY `Residence` (`Residence`),                                  *
 *      KEY `BirthPlace` (`BirthPlace`),                                *
 *      KEY `Occupation` (`Occupation`),                                *
 *      KEY `Religion` (`Religion`),                                    *
 *      KEY `IDIR` (`IDIR`) )                                           *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *  History:                                                            *
 *      2014/01/21      created                                         *
 *      2014/02/28      age field changed to string so non-integral     *
 *                      values can be recorded                          *
 *      2014/03/05      fix error in support of construct from array    *
 *      2014/12/26      send debugging information to $warn             *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/07/19      do not throw exception on invalid ages          *
 *                      because this caused failure on 'Full'           *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/11/09      change all SQL to prepared statements           *
 *                      permit changing record identification fields    *
 *      2017/12/18      add support for temporary fields                *
 *      2018/12/17      change throws to generate message               *
 *      2018/12/23      support invocation by array of search parms     *
 *                      support passing instance of Domain              *
 *      2019/12/13      remove M_ prefix from field names               *
 *      2020/09/09      update lastSqlCmd                               *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class MarriageParticipant                                           *
 *                                                                      *
 *  Definition of a class recording information about a single marriage *
 *  registration or marriage certificate.                               *
 *                                                                      *
 ************************************************************************/

class MarriageParticipant extends Record
{
    /********************************************************************
     *  MarriageParticipant::$initRow                                   *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                        'regdomain'         => 'CAON',
                        'regyear'           => 2020,
                        'regnum'            => 0,
                        'role'              => 'b',
                        'surname'           => '',
                        'surnamesoundex'    => '',
                        'givennames'        => '',
                        'age'               => 20,
                        'byear'             => 0,
                        'residence'         => '',
                        'birthplace'        => '',
                        'marstat'           => 'S',
                        'occupation'        => '',
                        'fathername'        => '',
                        'mothername'        => '',
                        'religion'          => '',
                        'witnessname'       => '',
                        'witnessres'        => '',
                        'remarks'           => '',
                        'idir'              => 0);

    /********************************************************************
     *  MarriageParticipant::$info                                      *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                        'table'             => 'MarriageIndi',
                        'name'              => 'MarriageIndi',
                        'prime'             => 
                            array('RegDomain','RegYear','RegNum, Role'),
                        'srvmg'             => false,
                        'fldcount'          => 20,
                        'order'             => 
                                        'RegDomain, RegYear, RegNum, Role',
                        'classname'         => 'MarriageParticipant');

    /********************************************************************
     *  MarriageParticipant::$translate                                 *
     *                                                                  *
     *  array containing synonyms for field names                       *
     ********************************************************************/
    protected static $translate = array(
                        'domain'                => 'regdomain',
                        'm_regdomain'           => 'regdomain',
                        'm_regyear'             => 'regyear',
                        'm_regnum'              => 'regnum',
                        'm_role'                => 'role',
                        'm_surname'             => 'surname',
                        'm_surnamesoundex'      => 'surnamesoundex',
                        'm_givennames'          => 'givennames',
                        'givenname'             => 'givennames',
                        'm_age'                 => 'age',
                        'm_byear'               => 'byear',
                        'm_residence'           => 'residence',
                        'm_birthplace'          => 'birthplace',
                        'm_marstat'             => 'marstat',
                        'm_occupation'          => 'occupation',
                        'm_fathername'          => 'fathername',
                        'm_mothername'          => 'mothername',
                        'm_religion'            => 'religion',
                        'm_witnessname'         => 'witnessname',
                        'm_witnessres'          => 'witnessres',
                        'm_remarks'             => 'remarks',
                        'm_idir'                => 'idir');

    /********************************************************************
     *  MarriageParticipant::$defaultOrder                              *
     *                                                                  *
     *  default sort order used by RecordSet                            *
     ********************************************************************/
    protected static $defaultOrder  =
                                'RegDomain, RegYear, RegNum, Role';

    /********************************************************************
     *  MarriageParticipant->domain                                     *
     *                                                                  *
     *  Instance of Domain                                              *
     ********************************************************************/
    protected $domain   = null;

    /********************************************************************
     *  function MarriageParticipant::__construct                       *
     *                                                                  *
     *  Construct an instance of MarriageParticipant based upon the     *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $domain             4 or 5 char administrative domain       *
     *                          (2 char country code + state/prov code) *
     *                          or an associative array containing the  *
     *                          4 key fields and the search values      *
     *                          or an associative array containing a    *
     *                          complete record from the database       *
     *          If the first parameter is a string:                     *
     *      $year               registration year                       *
     *      $number             registration number within year         *
     *      $role               role of the participant in the          *
     *                          marriage.  May be 'B', 'G', or 'M'      *
     *                                                                  *
     *  Returns object containing information about a participant       *
     *  recorded in a marriage registration.                            *
     *                                                                  *
     *  Throws Exception if unable to complete function because of      *
     *  bad parameters.                                                 *
     ********************************************************************/
    function __construct($domain,
                         $year      = null,
                         $number    = null,
                         $role      = null)
    {
        global $debug;
        global $warn;
        global $connection;

        $needInsert                 = false;
        $parms                      = null;
        $this->table                = 'MarriageIndi';

        // validate parameters
        if ($domain instanceof Domain)
        {                       // convert parameters
            $this->domain           = $domain;
            $code                   = $domain->get('domain');
            $parms                  = array('regdomain'   => $code,
                                            'regyear'     => $year,
                                            'regnum'      => $number,
                                            'role'        => $role);
        }                       // convert parameters
        else
        if (is_string($domain))
        {                       // convert parameters
            $parms                  = array('regdomain'   => $domain,
                                            'regyear'     => $year,
                                            'regnum'      => $number,
                                            'role'        => $role);
        }                       // convert parameters
        else
        if (is_array($domain))
        {                       // convert search parms
            $parms                  = $domain;
        }                       // convert search parms
        else
        {                       // unsupported data type
            $this->msg  .= "Dirst parameter must be a string or array. ";
        }                       // unsupported data type

        if (is_array($parms))
        {                       // initialize new entry
            if (count($parms) >= count(self::$initRow))
            {                   // complete row
                $dbrow              = $parms;
                $needInsert         = false;
            }                   // complete row
            else
            {                   // search parameters
                foreach($parms as $field => $value)
                {
                    $fieldLc        = strtolower($field);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc    = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {           // act on specific field
                        case 'regdomain':
                        {
                            $domain = $value;
                            break;
                        }
    
                        case 'regyear':
                        {
                            $year   = $value;
                            break;
                        }
    
                        case 'regnum':
                        {
                            $number = $value;
                            break;
                        }
    
                        case 'role':
                        {
                            $role   = $value;
                            break;
                        }
    
                    }           // act on specific field
                }               // loop through parameter fields

                // validate domain name
                $domain             = strtoupper($domain);
                if ($domain instanceof Domain)
                {
                    $this->domain   = $domain;
                    $domain         = $domain->get('domain');
                }
                else
                if (is_null($this->domain))
                {
                    $this->domain   = new Domain(array('domain' => $domain));
                    if (!$this->domain->isExisting())
                        $this->msg      .=  "Domain '$domain' must be a supported two character country code followed by a state or province abbreviation. ";
                }
    
                // validate registration year
                if (is_string($year) && ctype_digit($year))
                    $year           = (int)$year;
                if (!is_int($year) || $year < 1800 || $year > 2100)
                    $this->msg      .= "Registration year '$year' must be a numeric year. ";
    
                // validate registration number
                if (is_string($number) && ctype_digit($number))
                    $number = (int)$number;
                if (!is_int($number) || $number < 1)
                    $this->msg      .= "Registration number '$number' must be a positive integer. ";
    
                // validate participant role
                if (preg_match("/^[BGM]$/", $role) != 1)
                    $this->msg      .= "Role '$role' must be one of 'B', 'G', or 'M'";
    
                // search for an existing record matching the key
                $query      = 'SELECT * FROM MarriageIndi ' .
                                          'WHERE RegDomain=:domain AND  ' .
                                            'RegYear=:year AND ' .
                                            'RegNum=:number AND ' .
                                            'Role=:role';
                $sqlParms   = array('domain'    => $domain,
                                    'year'      => $year,
                                    'number'    => $number,
                                    'role'      => $role);
    
                // query the database
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
    
                if ($stmt->execute($sqlParms))
                {               // successful query
                    $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($debug)
                        $warn           .= "<p>MarriageParticipant.inc: " .
                                            __LINE__ . " $queryText</p>\n";
    
                    if (count($result) > 0)
                    {           // existing record
                        $dbrow              = $result[0];
                        $needInsert         = false;
                    }           // existing record
                    else
                    {           // create a new record
                        // set defaults
                        $dbrow              = self::$initRow;
                        $dbrow['regdomain'] = $domain;
                        $dbrow['regyear']   = $year;
                        $dbrow['regnum']    = $number;
                        $dbrow['role']      = $role;
                        $dbrow['byear']     = $year - 20;
                        switch($role)
                        {
                            case 'B':
                            {   // bride
                                $dbrow['marstat']   = 'S';  // spinster
                                break;
                            }   // bride
    
                            case 'G':
                            {   // groom
                                $dbrow['marstat']   = 'B';  // bachelor
                                break;
                            }   // groom
    
                        }       // fields depending on role
                        $needInsert     = true;
                    }           // create new record
                }               // successful query
                else
                {               // error performing query
                    $this->msg          .= __LINE__ . " '$queryText' " .
                                            print_r($stmt->errorInfo(),true);
                }               // error performing query
            }                   // search parameters
        }                       // initialize record
        else
            throw new Exception("MarriageParticipant::__construct: invalid parameters");

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'MarriageIndi');
        $this->needInsert           = $needInsert;

        // diagnostic output if debug is set
        $this->dump('MarriageParticipant Record constructed:');
    }       // MarriageParticipant::__construct

    /********************************************************************
     *  function MarriageParticipant:set                                *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change                         *
     *      $value      new value to set it to                          *
     *                                                                  *
     *  Returns:                                                        *
     *          previous value of the field                             *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds trace if $field is not already defined as a field      *
     *      or pseudo-field and debugging is enabled                    *
     ********************************************************************/
    function set($field, $value)
    {
        global  $warn;

        $value      = trim($value);
        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {       // act on specific field
            case 'surnamesoundex':
            case 'byear':
            {
                return $this->row[$fieldLc];
            }       // fields that cannot be altered through this method

            case 'age':
            {
                $matches    = array();
                if (strlen($value) == 0 ||
                    strtolower($value) == '[blank]')
                    $age    = 20;
                else
                if (ctype_digit($value))
                    $age    = $value - 0;
                else
                if (preg_match("/^\s*\d+m\s*$/", $value) == 1)
                    $age    = 0;
                else
                if (preg_match("/\[(\d+)\]/", $value, $matches) == 1)
                    $age    = $matches[1] - 0;
                else
                    $age    = 16;

                if ($age > 110)
                    $warn   .= "<p>MarriageParticipant::set: ".
                                "'$field' value '$value' is too great</p>\n";
                $regyear    = $this->row['regyear'];
                parent::set('byear', $regyear - $age);
                $retval     = parent::set('age', $value);
                return $retval;
            }       // Age

            case 'idir':
            {
                if (is_int($value) ||
                    (is_string($value) && ctype_digit($value)))
                {
                    return parent::set('idir', $value);
                }
                else
                {
                    $warn   .= "<p>MarriageParticipant::set: ". __LINE__ .
                        " IDIR value '$value' must be a positive integer";
                    return $this->row['idir'];
                }
                break;
            }       // IDIR

            case 'regdomain':
            case 'regyear':
            case 'regnum':
            case 'role':
            case 'surname':
            case 'givennames':
            case 'residence':
            case 'birthplace':
            case 'marstat':
            case 'occupation':
            case 'fathername':
            case 'mothername':
            case 'religion':
            case 'witnessname':
            case 'witnessres':
            case 'remarks':
            {
                return parent::set($fieldLc, $value);
            }

            default:
            {       // other field names
                return parent::set($fieldLc, $value);
            }       // other fields
        }       // act on specific field
    }       // MarriageParticipant::set

    /********************************************************************
     *  function MarriageParticipant::save                              *
     *                                                                  *
     *  Save changes made to the MarriageParticipant record into        *
     *  the database.                                                   *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format within <cmd> tags.                       *
     *                  if a string use it as tag name                  *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $userid;
        global  $connection;

        // determine top level tag name for XML output
        $indent             = '';
        $xmlparms           = '';
        if (is_string($xml))
        {               // string tag name
            $matches        = array();
            if (preg_match('/^(\s*)(\w*)(.*)$/', $xml, $matches))
            {
                $indent     = $matches[1];
                $xml        = $matches[2];
                $xmlparms   = $matches[3];
            }
            $tagname        = $xml;
            $xml            = true;
        }               // string tag name
        else
        if ($xml)
        {               // boolean true
            $indent         = '    ';
            $tagname        = 'cmd';
        }               // boolean true

        if ($this->needInsert)
        {       // create new record
            $fldnames       = '';
            $comma          = '';
            $values         = '';
            $sqlParms       = array();
            foreach($this->row as $fld => $value)
            {
                switch($fld)
                {
                    case 'surnamesoundex':
                    {
                        $fldnames   .= $comma . "`SurnameSoundex`"; 
                        $surname    = $this->row['surname'];
                        if (is_null($surname) || (strlen($surname) == 0))
                            $values .= $comma . "''";
                        else
                        {
                            $values .= ",LEFT(SOUNDEX(:surname), 4)";
                            $sqlParms['surname']    = $surname;
                        }
                        $comma  = ", ";
                        break;
                    }       // Surname

                    default:
                    {
                        $fldnames   .= $comma . "`$fld`"; 
                        $values     .= $comma . ":$fld";
                        $sqlParms[$fld] = $value;
                        $comma      = ", ";
                        break;
                    }       // most fields
                }       // act on specific fields
            }       // loop through all fields in record

            // construct the SQL INSERT command 
            $insert = "INSERT INTO MarriageIndi ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt           = $connection->prepare($insert);
            $insertText     = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd   = $insertText;
            if ($stmt->execute($sqlParms))
            {       // successful insert
                $count      = $stmt->rowCount();
                if ($xml)
                {       // XML output
                    print "$indent<$tagname$xmlparms count='$count'>\n" .
                            "$indent\t$insertText\n" .
                            "$indent</$tagname>\n";
                }       // XML output
                if ($debug)
                {
                    $warn   .= "<p>MarriageParticipant::save: " . __LINE__ . 
                                " $insertText</p>\n";
                    $warn   .= "<p>count=$count</p>\n";
                }

                $this->changed      = array();  // reset
                $this->needInsert   = false;

                // log the update
                logSqlUpdate($insert,
                             $sqlParms,
                             0,     // compound key
                             '',
                             json_encode($this->row));
            }       // successful insert
            else
            {       // error inserting record
                error_log("MarriageParticipant.inc: " . __LINE__ . " $insertText\n");
                if (strlen($insertText) > 43)
                    $insertText = substr($insert, 0, 40) . "...";
                $warn   .= "<p>MarriageParticipant::save: " . __LINE__ .
                            " '$insertText', '" .
                                    print_r($stmt->errorInfo(),true);
            }       // error inserting record
        }       // create new record
        else
        {       // update existing record
            $update     = "UPDATE MarriageIndi SET ";
            $set        = '';
            $comma      = '';
            $sqlParms       = array();
            foreach($this->changed as $fld => $value)
            {
                switch($fld)
                {       // act on specific fields
                    case 'surname':
                    {       // surname changed
                        $set        .= $comma . "`Surname`=:surname";
                        $sqlParms['surname']    = $value;
                        $set            .= ",`SurnameSoundex`="; 
                        if (is_null($value) || (strlen($value) == 0))
                            $set        .= "''";
                        else
                            $set        .= "LEFT(SOUNDEX(:surname), 4)";
                        $comma          = ", ";
                        break;
                    }       // Surname

                    default:
                    {       // most fields
                        $set            .= $comma . "`$fld`=:$fld";
                        $sqlParms[$fld]     = $value;
                        $comma          = ', ';
                        break;
                    }       // most fields
                }       // act on specific fields
            }       // loop through all changed fields in record

            $this->changed  = array();  // reset

            if (strlen($set) > 0)
            {       // changes made
                // construct the where clause
                if ($this->former)
                {       // record has been updated
                    $sqlParms['odomain']    = $this->former['regdomain'];
                    $sqlParms['oyear']      = $this->former['regyear'];
                    $sqlParms['onumber']    = $this->former['regnum'];
                    $sqlParms['orole']      = $this->former['role'];
                }       // record has been updated
                else
                {       // record unchanged
                    $sqlParms['odomain']    = $this->row['regdomain'];
                    $sqlParms['oyear']      = $this->row['regyear'];
                    $sqlParms['onumber']    = $this->row['regnum'];
                    $sqlParms['orole']      = $this->row['role'];
                }       // record unchanged
                $where      = "RegDomain=:odomain AND RegYear=:oyear AND RegNum=:onumber AND Role=:orole";

                // assemble the UPDATE command
                $update             .= $set . ' WHERE ' . $where;

                // perform the update
                $stmt               = $connection->prepare($update);
                $updateText         = debugPrepQuery($update, $sqlParms);
                $this->lastSqlCmd   = $updateText;
                if ($stmt->execute($sqlParms))
                {       // successful update
                    $count          = $stmt->rowCount();
                    if ($xml)
                    {
                        print "$indent<$tagname$xmlparms result='$count'>\n" .
                                "$indent\t$updateText\n" .
                                "$indent</$tagname>\n";
                    }

                    if ($debug)
                        $warn   .= "<p>MarriageParticipant::save: " . __LINE__ .
                                 " $updateText</p>\n" . "<p>count=$count</p>\n";

                    // log the update
                    logSqlUpdate($update,
                                 $sqlParms,
                                 $count,        // compound key
                                 json_encode($this->former),
                                 json_encode($this->row));
                }       // successful update
                else
                {       // error updating record
                    $warn   .= "<p>MarriageParticipant::save: " . __LINE__ .
                                    " '$updateText', " .
                                    print_r($stmt->errorInfo(),true);
                }       // error updating record
            }           // changes made
        }               // update existing record
    }       // MarriageParticipant::save

    /********************************************************************
     *  function MarriageParticipant::delete                            *
     *                                                                  *
     *  Delete the database record associated with this instance.       *
     *                                                                  *
     *  Input:                                                          *
     *      $xml        if true emit XML                                *
     *                  if a string emit XML using as tag name          *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1           if the method deleted the database record       *
     *      0           if the record did not need to be deleted        *
     ********************************************************************/
    function delete($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $connection;

        // handle XML output option
        $indent             = '';
        $xmlparms           = '';
        if (is_string($xml))
        {               // string tag name
            $matches        = array();
            if (preg_match('/^(\s*)(\w*)(.*)$/', $xml, $matches))
            {
                $indent     = $matches[1];
                $xml        = $matches[2];
                $xmlparms   = $matches[3];
            }
            $tagname        = $xml;
            $xml            = true;
        }               // string tag name
        else
        if ($xml)
        {               // boolean true
            $xml            = true;
            $tagname        = 'cmd';
        }               // boolean true

        // delete the record if it is in the database
        if (!$this->needInsert)
        {       // existing record in database
            $sqlParms   = array('domain'    => $this->row['regdomain'],
                                'year'      => $this->row['regyear'],
                                'number'    => $this->row['regnum'],
                                'role'      => $this->row['role']);
            $delete = 'DELETE FROM MarriageIndi WHERE ' .
                                    'RegDomain=:domain AND ' .
                                    'RegYear=:year AND ' .
                                    'RegNum=:number AND ' .
                                    'Role=:role';
            $stmt               = $connection->prepare($delete);
            $deleteText         = debugPrepQuery($delete, $sqlParms);
            $this->lastSqlCmd   = $deleteText;
            if ($stmt->execute($sqlParms))
            {       // successful delete
                $count          = $stmt->rowCount();
                if ($debug)
                    $warn   .= "<p>MarriageParticipant::delete: " . __LINE__ . 
                                " $deleteText</p>\n" . "<p>count=$count</p>\n";

                if ($xml)
                    print "$indent<$tagname$xmlparms count='$count'\n" .
                            "$indent\t$deleteText\n" .
                            "$indent</$tagname>\n";

                // log the update
                logSqlUpdate($delete,
                             $sqlParms,
                             $count,
                             json_encode($this->row),
                             '');

                // update the memory copy of the record to reflect that it
                // no longer represents a database record
                $this->needInsert   = true;

                return $count;  // number of records deleted
            }       // successful delete
            else
            {       // error updating record
                error_log("MarriageParticipant.inc: " . __LINE__ .
                        " $deleteText, " . print_r($stmt->errorInfo(),true) . 
                        "\n");
                return false;
            }       // error updating record
        }       // existing record in database

        // record never written to database
        return 0;   // nothing to delete
    }       // MarriageParticipant::delete

}       // class MarriageParticipant 
