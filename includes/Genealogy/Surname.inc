<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Surname.inc                                                         *
 *                                                                      *
 *  Definition of a class representing a surname record in a            *
 *  genealogical database.                                              *
 *  This class provides access to the information in a row of the       *
 *  table tblNR.                                                        *
 *                                                                      *
 *  The following is adapted from the Legacy Family Tree internal       *
 *  documentation                                                       *
 *      Copyright 1995-2005, Millennia Corporation                      *
 *      All Rights Reserved                                             *
 *                                                                      *
 *          Name                    Type                                *
 *          IDNR                    Integer(10),                        *
 *              used by: tblNX.IDNR, deprecated                         *
 *          Surname                 VARCHAR(120),   unique key          *
 *          SoundsLike              VARCHAR(4),                         *
 *          Pattern                 VARCHAR(64),    added               *
 *          Notes                   LONGTEXT,       added               *
 *          Used                    TINYINT(3),                         *
 *              temporary use, 0=no, 1=yes                              *
 *          Tag1                    TINYINT(3),                         *
 *              0=no, 1=yes                                             *
 *          qsTag                   TINYINT(3),                         *
 *              temporary use, 0=no, 1=yes                              *
 *                                                                      *
 *  The design of this table in the original implementation violated    *
 *  recommended design for SQL databases by having a primary key        *
 *  that was meaningless when there was a unique field.  This design    *
 *  complicated the design of the Names table (tblNX) by requiring      *
 *  that it contain two fields whose values need to be synchronized.    *
 *  So the field IDNR is not used in this implementation.               *
 *                                                                      *
 *  History:                                                            *
 *      2011/01/03      created                                         *
 *      2012/01/13      rename classes to have upper case character     *
 *      2012/10/20      standardize initialization of new record        *
 *      2013/08/09      base class renamed to Record                    *
 *      2014/12/08      comment blocks enclosed                         *
 *                      diagnostic output redirected to $warn           *
 *      2015/01/08      add method getName for symmetry                 *
 *      2015/05/16      add field Notes                                 *
 *                      add field SoundsLike                            *
 *                      add support for explicit field names in         *
 *                      constructor                                     *
 *                      add static method getSurnames                   *
 *                      add method save                                 *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/11/16      error in getting count                          *
 *      2016/12/31      use prepared statements                         *
 *                      add support for all fields in record for        *
 *                      getNames etc search.                            *
 *      2017/01/09      exclude "Wifeof" and "Motherof" surnames from   *
 *                      construct and prefix search                     *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/09/05      add regular expression pattern match            *
 *      2017/11/21      use prepared statements                         *
 *      2017/12/18      add support for temporary fields                *
 *      2018/12/25      Surname is primary key                          *
 *      2019/01/03      replace static method getNextSurname with       *
 *                      new Surname(array('surname' => ">=$surname")    *
 *      2019/05/17      add Surname::$info                              *
 *      2021/09/15      add method getCount                             *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Surname                                                       *
 *                                                                      *
 *  Definition of a class implementing behavior for Surname records     *
 *  within a genealogy database.                                        *
 ************************************************************************/

class Surname extends Record
{

    /********************************************************************
     *  Surname::$initRow                                               *
     *                                                                  *
     *  array containing default values for a new record                *
     ********************************************************************/
    protected static    $initRow    = array(
                'idnr'              => 0,       // deprecated
                'surname'           => '',      // primary key
                'soundslike'        => '',
                'pattern'           => '',      // added
                'notes'             => '',      // added
                'used'              => 1,
                'tag1'              => 0,
                'qstag'             => 0);

    /********************************************************************
     *  Surname::$info                                                  *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'tblNR',
                'name'              => 'Surnames',
                'prime'             => 'surname',
                'srvmg'             => false,
                'fldcount'          => 8,
                'order'             => 'Surname',
                'classname'         => 'Surname');

    /********************************************************************
     *  Surname::$defaultOrder                                          *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'Surname';

    /********************************************************************
     *  function Surname::__construct                                   *
     *                                                                  *
     *  Given an identifier extract information about a surname.        *
     *                                                                  *
     *  Input:                                                          *
     *      $parms  o array('idnr' => $idnr) to get a record by         *
     *                record identification number, deprecated          *
     *                except for 'idnr'=> 1                             *
     *              o array('surname' => $surname) to get a record      *
     *                by surname, creating the record if necessary      *
     *              o array('surname' => ">=$surname") to get a record  *
     *                with the lowest surname that is greater than      *
     *                the supplied value.                               *
     *              o a database record presented as an array           *
     *                                                                  *
     *              There is no way to create an                        *
     *              uninitialized instance of this class                *
     *              new Surname(array('surname' => 'new')) returns the  *
     *              record for the surname 'new'.                       *
     *              new Surname(array('idnr' => $idnr)) fails if        *
     *              there is no record for the value of $idnr           *
     *                                                                  *
     *  Returns object containing surname record.                       *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms)
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $dbrow                  = null;
        $needInsert             = false;

        // action depends upon type of first parameter
        // convert old format calls to new format internally
        if (is_int($parms))
        {       // numeric identifier of record
            $parms              = array('idnr'      => $parms);
        }       // numeric identifier of record
        else
        if (is_string($parms))
        {       // surname
            $parms              = trim($parms);
            if (ctype_digit($parms))
                $parms          = array('idnr'      => (int)$parms);
            else
                $parms          = array('surname'   => $parms);
        }       // surname

        $this->constDebug('Surname', $parms);

        if (is_array($parms))
        {           // array
            if (count($parms) < count(self::$initRow))
            {           // set of search parameters
                $where              = '';
                $limit              = '';
                $and                = 'WHERE ';
                $surname            = null;
                $idnr               = null;
                $sqlParms           = array();
                foreach($parms as $fieldname => $value)
                {       // loop through parameters
                    switch(strtolower($fieldname))
                    {       // act on specific fields
                        case 'id':
                        case 'idnr':
                        {
                            if (is_string($value))
                                $value      = trim($value);
                            if (ctype_digit($value))
                                $value      = intval($value);
                            if (is_int($value) && $value > 0)
                            {
                                $where      .= $and . "IDNR=?";
                                $sqlParms[] = $value;
                                $and        = ' AND ';
                                $idnr       = $value;
                            }
                            else
                                $this->msg  .= "Invalid value of IDNR=$value. ";
                            if ($value !== 1)
                            {
                                $warn   .= "<p>Surname::__construct: deprecated invocation with obsolete IDNR=$value key.</p>\n";
                                ob_start();
                                debug_print_backtrace();
                                $warn   .= ob_get_clean();
                            }
                            break;
                        }   // numeric id

                        case 'surname':
                        {
                            if ($value == '')
                            {           // empty surname
                                $where      .= $and . "IDNR=1";
                                $surname    = '';
                            }           //empty surname
                            else
                            if (substr($value, 0, 2) == '>=')
                            {           // next surname >= value
                                $where      .= $and . "Surname>=?";
                                $value      = substr($value, 2);
                                $sqlParms[] = $value;
                                $surname    = $value;
                                $limit      = ' LIMIT 1';
                            }           // next surname ?= value
                            else
                            {           // match on surname
                                $where      .= $and . "Surname=?";
                                $sqlParms[] = $value;
                                $surname    = $value;
                            }           // match on surname
                            $and            = ' AND ';
                            break;
                        }   // surname

                        default:
                        {
                            $warn   .= "<p>Surname::__construct: Unexpected field name '$fieldname'</p>\n";
                            break;
                        }
                    }       // act on specific fields
                }           // loop through parameters

                if (strlen($where) == 0)
                    $this->msg      .= "No valid parameters passed. parms=" .
                                        print_r($parms, true) . '. ';
                if (strlen($this->msg) == 0)
                {       // valid parameters
                    // construct the query of the surname table
                    $query              = "SELECT * FROM tblNR $where$limit";

                    // query the database
                    $stmt               = $connection->prepare($query);
                    $queryText          = debugPrepQuery($query, $sqlParms);

                    if ($stmt->execute($sqlParms))
                    {       // execute successful
                        $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($debug)
                            $warn   .= '<p>Surname::_construct: ' .
                                __LINE__ . " query='$queryText'</p>\n";
                        if (count($result) > 0)
                        {   // found surname record
                            $dbrow          = $result[0];
                            $needInsert     = false;
                        }   // found surname record
                        else
                        {   // no match
                            if ($surname)
                            {   // create a new surname record
                                $dbrow              = self::$initRow;
                                $dbrow['surname']   = $surname;
                                $needInsert         = true;
                            }   // create a new surname record
                            else
                            {   // create a new surname record
                                $dbrow                  = self::$initRow;
                                if (!is_null($idnr))
                                {
                                    $dbrow['idnr']      = $idnr;
                                    $dbrow['surname']   = "Missing IDNR $idnr";
                                }
                                $needInsert             = true;
                                $this->msg              .=
                        "Cannot create new Surname record without surname. ";
                            }   // create a new surname record
                        }   // no match
                    }       // execute successful
                    else
                    {       // execute failed
                        $this->msg  .= __LINE__ .
                                    " execute '$queryText' failed, result=" .
                                    print_r($stmt->errorInfo(),true) . ". ";
                        print "<msg>" . $this->msg . "</msg>\n";
                        $needInsert = false;
                    }       // execute failed
                }       // valid parameters
            }           // set of search parameters
            else
            {           // complete row returned from database query
                $dbrow          = $parms;
                if (array_key_exists('idnr', $dbrow))
                    $idnr       = $dbrow['idnr'];
                else
                    $this->msg  .= __LINE__ .
                                " no field 'idnr' in supplied row. ";
                $needInsert     = false;
            }           // complete row returned from database query
        }           // array
        else
        {           // invalid parameter
            $this->msg  .= __LINE__ . " called with " .
                 gettype($parms) . ", value='" . print_r($parms, true) . "'. ";
        }           // invalid parameter

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'tblNR');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Surname Record constructed:');
        if (strlen($this->msg) > 0)
            $this->msg        .= "Surname::__construct: " . $this->msg;
    }       // function Surname::__construct

    /********************************************************************
     *  function Surname::getIdnr                                       *
     *                                                                  *
     *  Retrieve the unique numeric key of this instance of             *
     *  Surname.                                                        *
     ********************************************************************/
    function getIdnr()
    {
        // if necessary insert the record into the
        // database to associate the object with a database record
        if ($this->needInsert)
            $this->save();

        return $this->row['idnr'];
    }       // function Surname::getIdnr

    /********************************************************************
     *  function Surname::getSurname                                    *
     *                                                                  *
     *  Get the surname value for this record.                          *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing surname                                   *
     ********************************************************************/
    function getSurname()
    {
        return $this->row['surname'];
    }       // function Surname::getSurname

    /********************************************************************
     *  function SurName::getName                                       *
     *                                                                  *
     *  Get the descriptive name of this record.                        *
     *  This method is implemented for consistency with other classes   *
     *  which have a getName method to return a summary of the          *
     *  instance.                                                       *
     *                                                                  *
     *  Input:                                                          *
     *      $options    required to match signature in Record           *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing the surname                               *
     ********************************************************************/
    function getName($options   = null)
    {
        return $this->row['surname'];
    }       // function Surname::getName

    /********************************************************************
     *  function Surname::getCount                                      *
     *                                                                  *
     *  Get the the number of instances of class Person that have this  *
     *  surname.                                                        *
     *                                                                  *
     *  Returns:                                                        *
     *      integer count                                               *
     ********************************************************************/
    function getCount()
    {
        $getParms['surname']        = $this->row['surname'];
        $personList                 = new PersonSet($getParms);
        $info                       = $personList->getInformation();
        return $info['count'];
    }       // function Surname::getCount

    /********************************************************************
     *  function Surname::save                                          *
     *                                                                  *
     *  Save changes made to the Surname record into the                *
     *  database.                                                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format, if a string use it as tag name          *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $userid;
        global  $connection;

        if (is_string($xml))
        {       // string tag name
            $tagname    = $xml;
            $xml        = true;
        }       // string tag name
        else
        if ($xml)
        {       // true
            $tagname    = 'cmd';
        }       // true
        if ($xml)
            error_log("Surname::save called with deprecated parameter\n" .
                        $this->debugBacktrace());

        if (strlen($this->msg) > 0)
        {
            $msg    .= "Save of invalid Surname record suppressed because of " .
                            $this->msg;
            if ($xml)
                print "<$tagname>$msg</$tagname>\n";
            return 0;
        }

        // insert the record into the database if it does not already
        // exists, otherwise update those fields that need to be updated
        if ($this->needInsert)
        {       // create new record
            $fldnames               = '';
            $values                 = '';
            $comma                  = '';
            $sqlParms               = array();
            foreach($this->row as $fld => $value)
            {           // loop through all fields in record
                switch($fld)
                {       // act on field name
                    case 'idnr':
                    {   // auto-increment key of record
                        // set by SQL server
                        break;
                    }   // auto-increment key of record

                    case 'surname':
                    {   // changing surname also updates soundex
                        if (substr($value,0,6) == 'Wifeof' ||
                            substr($value,0,8) == 'Motherof' ||
                            $value == '')
                        {       // do not create new record
                            $this->needInsert       = false;    // do not create
                        }       // do not create new record
                        else
                        {       // create new record
                            $fldnames               .=
                                "$comma`surname`, `soundslike`";
                            $values                 .= 
                                "$comma:surname,LEFT(SOUNDEX(:surname),4)";
                            $sqlParms['surname']    = $value;
                            $comma                  = ',';
                        }       // create new record
                        break;
                    }   // changing surname also updates soundex

                    case 'soundslike':
                    {   // set under 'surname'
                        break;
                    }   // set under 'surname'

                    default:
                    {   // other fields
                        $fldnames                   .= "$comma`$fld`";
                        $values                     .= "$comma:$fld";
                        if (is_null($value) || (strlen($value) == 0))
                            $sqlParms[$fld]         = '';
                        else
                            $sqlParms[$fld]         = $value;
                        $comma                      = ',';
                        break;
                    }   // other fields
                }       // switch on field name
            }           // loop through all fields in record

            if (!$this->needInsert || strlen($fldnames) == 0)
                return 0;

            // create the command to insert the new record into the database
            $insert         = "INSERT INTO tblNR ($fldnames) VALUES($values)";

            // insert the record into the database
            $stmt           = $connection->prepare($insert);
            $insertText     = debugPrepQuery($insert, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // execute the prepared statement
                $count      = $stmt->rowCount();
                if ($xml)
                    print "<$tagname count='$count'>$insertText</$tagname>\n";

                if ($debug)
                {
                    $warn   .= "<p>$insertText, count=$count</p>\n";
                }

                $this->changed      = array();  // reset
                $this->needInsert   = false;    // no longer need insert

                // get the record identifier set by the database server for
                // the new record, update primary key value in record,
                // as it was previously 0
                $idnr               = $connection->lastInsertId();
                $this->row['idnr']  = $idnr;

                // log the update
                $this->log($insert,
                           $sqlParms);
            }           // execute the prepared statement
            else
            {           // error executing query
                throw new Exception("Surname::save: '$insertText', " .
                                    print_r($stmt->errorInfo(),true));
            }           // error executing query
        }               // create new record
        else
        {       // update existing record
            $update             = "UPDATE tblNR SET ";
            $set                = '';
            $comma              = '';
            $sqlParms           = array();
            foreach($this->changed as $fld => $value)
            {           // loop through all fields in record
                switch($fld)
                {       // act on field name
                    case 'idnr':
                    {   // auto-increment key of record
                        // set by SQL server
                        break;
                    }   // auto-increment key of record

                    case 'surname':
                    {   // changing surname also updates soundex
                        $set                    .=
                            "$comma`surname`=:surname, " .
                            "`soundslike`=LEFT(SOUNDEX(:surname),4)";
                        $sqlParms['surname']    = $value;
                        $comma                  = ', ';
                        break;
                    }   // changing surname also updates soundex

                    case 'soundslike':
                    {   // set under 'surname'
                        break;
                    }   // set under 'surname'

                    default:
                    {   // other fields
                        $set                    .= "$comma`$fld`=:$fld";
                        $sqlParms[$fld]         = $value;
                        $comma                  = ', ';
                        break;
                    }   // other fields
                }       // switch on field name
            }           // loop through all fields in record
            $this->changed                      = array();  // reset

            if (strlen($set) > 0)
            {           // changes made to contents of record
                $idnr                   = $this->row['idnr'];
                $update                 = "UPDATE tblNR SET $set " .
                                           "WHERE Surname=:osurname";
                $sqlParms['osurname']   = $this->former['surname'];

                // update the record
                $stmt                   = $connection->prepare($update);
                $updateText             = debugPrepQuery($update, $sqlParms);
                if ($stmt->execute($sqlParms))
                {       // update successful
                    $count  = $stmt->rowCount();
                    if ($xml)
                    {
                        print "<$tagname count='$count'>$updateText</$tagname>\n";
                    }

                    if ($debug)
                    {
                        $warn   .= "<p>$updateText, count=$count</p>\n";
                    }

                    // log the update
                    $this->log($update,
                               $sqlParms);
                }       // update successful
                else
                {       // error updating record
                    throw new Exception("Surname::save: '$updateText', " .
                                        print_r($stmt->errorInfo(),true));
                }       // error updating record
            }           // changes made
        }               // update existing record
    }       // function Surname::save

}       // class Surname
