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
 *                                                                      *
 *  Copyright &copy; 2017 James A. Cobban                               *
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
                        'm_regdomain'       => 'CAON',
                        'm_volume'          => 1,
                        'm_reportno'        => 1,
                        'm_itemno'          => '',
                        'm_role'            => '',
                        'm_givennames'      => '',
                        'm_surname'         => '',
                        'm_surnamesoundex'  => '',
                        'm_age'             => '',
                        'm_residence'       => '',
                        'm_birthplace'      => '',
                        'm_fathername'      => '',
                        'm_mothername'      => '',
                        'm_date'            => '',
                        'm_licensetype'     => 'L',
                        'm_witnessname'     => '',
                        'm_idir'            => 0,
                        'm_remarks'         => '');

    /********************************************************************
     *  CountyMarriage::$info                                           *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'CountyMarriages',
                'name'              => 'CountyMarriages',
                'prime'             => array('m_regdomain','m_volume','m_reportno','m_itemno','m_role'),
                'srvmg'             => false,
                'fldcount'          => 6,
                'order'             => 'M_Regdomain,M_Volume,M_Reportno,M_Itemno,M_Role DESC',
                'classname'         => 'CountyMarriage');

    /********************************************************************
     *  $translate      static array containing common translations of  *
     *                  field names                                     *
     ********************************************************************/
    protected static    $translate = array(
                        'regdomain'     => 'm_regdomain',
                        'domain'        => 'm_regdomain',
                        'm_domain'      => 'm_regdomain',
                        'volume'        => 'm_volume',
                        'reportno'      => 'm_reportno',
                        'itemno'        => 'm_itemno',
                        'role'          => 'm_role',
                        'givennames'    => 'm_givennames',
                        'surname'       => 'm_surname',
                        'surnamesoundex'=> 'm_surnamesoundex',
                        'age'           => 'm_age',
                        'residence'     => 'm_residence',
                        'birthplace'    => 'm_birthplace',
                        'fathername'    => 'm_fathername',
                        'mothername'    => 'm_mothername',
                        'date'          => 'm_date',
                        'licensetype'   => 'm_licensetype',
                        'witnessname'   => 'm_witnessname',
                        'idir'          => 'm_idir',
                        'remarks'       => 'm_remarks');

    /********************************************************************
     *  $defaultOrder                                                   *
     *                                                                  *
     *  Default sort order for reports                                  *
     ********************************************************************/
    protected static $defaultOrder  =
                "M_RegDomain, M_Volume, M_ReportNo, M_ItemNo, M_Role DESC";

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

        $needInsert = false;
        $this->table    = 'CountyMarriages';
        $msg        = '';

        // validate parameters
        if (is_array($parms))
        {       // associative parms or row returned from database query
            if (count($parms) >= count(self::$initRow))
            {       // row returned from database query
                foreach($parms as $field => $value)
                {   // validate all field names
                    if (!array_key_exists($field, self::$initRow))
                        $msg    .= "Invalid field name `$field` in supplied record. ";
                }   // field names

                if (strlen($msg) == 0)
                {
                    $dbrow      = $parms;
                    $needInsert     = false;
                }
            }       // row returned from database query
            else
            {       // associative array of search parms
                $where      = '';
                $and        = 'WHERE ';
                $sqlParms   = array();
                foreach($parms as $field => $value)
                {   // loop through all supplied search parms
                    $fieldLc    = strtolower($field);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc    = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {   // act on specific parameter names
                        case 'm_domain':
                        case 'm_regdomain':
                        {
                            $where      .= $and . 'M_RegDomain=?';
                            $sqlParms[]     = $value;
                            $and        = ' AND ';
                            break;
                        }   // domain name

                        case 'm_volume':
                        {
                            $where      .= $and . 'M_Volume=?';
                            $sqlParms[]     = $value;
                            $and        = ' AND ';
                            break;
                        }   // volume number

                        case 'm_reportno':
                        {
                            if (is_string($value))
                            {
                                // some idiot put in fractional values for
                                // report numbers!
                                $value      = trim($value);
                                $matches    = array();
                                $rxr        = preg_match('#(\d+)(\x{00BD}|.5|1/2)#u',
                                                         $value, $matches);
                                if ($rxr)
                                    $value  = intval($matches[0]) + 0.5;
                            }
                            $where          .= $and . 'M_ReportNo=?';
                            $sqlParms[]     = $value;
                            $and            = ' AND ';
                            break;
                        }   // report number

                        case 'm_itemno':
                        {
                            $where      .= $and . 'M_ItemNo=?';
                            $sqlParms[]     = $value;
                            $and        = ' AND ';
                            break;
                        }   // report number

                        case 'm_role':
                        {
                            $where      .= $and . 'M_Role=?';
                            $sqlParms[]     = $value;
                            $and        = ' AND ';
                            break;
                        }   // report number

                        default:
                        {
                            if (!array_key_exists($fieldLc, self::$initRow))
                            $msg    .= "Invalid field name `$field`. ";
                            break;
                        }   // unrecognized
                    }   // act on specific parameter names
                }   // loop through all supplied search parms

                if (strlen($msg) == 0)
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
                        $msg    .= "execute '$queryText' failed, errorInfo=" .
                               print_r($stmt->errorInfo(), true);
                    }       // execute failed
                }       // no errors so far
            }           // associative array of search parms
        }           // parameter is an array
        else
        {           // invalid parameter
             $msg   .= "called with parms=" . print_r($parms, true);
        }           // invalid parameter

        if (strlen($msg) > 0)
            throw new Exception("CountyMarriage::__construct: " . $msg);

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
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $userid;
        global  $connection;

        if (is_string($xml))
        {           // string tag name
            $tagname    = $xml;
            $xml    = true;
        }           // string tag name
        else
        if ($xml)
        {           // true
            $tagname    = 'cmd';
        }           // true

        if ($this->needInsert)
        {           // create new record
            $fldnames       = '';
            $comma      = '';
            $values     = '';
            $sqlParms       = array();
            foreach($this->row as $fld => $value)
            {
                $fldnames   .= $comma . "`$fld`";
                if ($fld == 'm_surnamesoundex')
                {
                    $values .= $comma . 'LEFT(SOUNDEX(:surname),4)';
                }
                else
                {
                    $simpleName         = substr($fld, 2);
                    $values         .= $comma . ':' . $simpleName;
                    $sqlParms[$simpleName]  = $value;
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
                $errorInfo  = $stmt->errorInfo();
                if (strlen($insertText) > 43)
                    $insertText = substr($insertText, 0, 40) . '...';
                throw new Exception("CountyMarriage::save: '$insertText', " .
                                print_r($errorInfo,true));
            }           // error inserting record
        }           // create new record
        else
        {           // update existing record
            $set        = '';
            $sqlParms       = array();
            $comma      = 'SET ';
            foreach($this->changed as $fld => $value)
            {           // loop through all changed fields in record
                if ($fld == 'm_surname')
                {
                    $set            .= $comma .
                                       "`M_Surname`=:surname";
                    $sqlParms['surname']    = $value;
                    $set     .= ",`M_SurnameSoundex`=LEFT(SOUNDEX(:surname),4)";
                }
                else
                if ($fld != 'm_surnamesoundex')
                {
                    $simpleName     = substr($fld, 2);
                    $set        .= $comma . "`$fld`=:$simpleName";
                    $sqlParms[$simpleName] = $value;
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
                $sqlParms['odomain']    = $this->former['m_regdomain'];
                $sqlParms['ovolume']    = $this->former['m_volume'];
                $sqlParms['oreport']    = $this->former['m_reportno'];
                $sqlParms['oitem']  = $this->former['m_itemno'];
                $sqlParms['orole']  = $this->former['m_role'];
                $where          = "M_RegDomain=:odomain AND " .
                                  "M_Volume=:ovolume AND " .
                                  "M_ReportNo=:oreport AND " .
                                  "M_ItemNo=:oitem AND " .
                                  "M_Role=:orole";

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
                    throw new Exception("CountyMarriage::save: '$updateText', ".
                                print_r($stmt->errorInfo(),true));
                }       // error updating record
            }           // changes made
        }           // update existing record
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
            $n1     = $r1->get('m_volume') * 10000000 +
                          $r1->get('m_reportno') * 1000 +
                          $r1->get('m_itemno');
            $k1     = $r1->get('m_regdomain') .
                          str_pad($n1,9,'0',STR_PAD_LEFT);
            if ($r1->get('m_role') == 'G')
                $k1 .= '0';
            else
                $k1 .= '1';
            $n2     = $r2->get('m_volume') * 10000000 +
                          $r2->get('m_reportno') * 1000 +
                          $r2->get('m_itemno');
            $k2     = $r2->get('m_regdomain') .
                          str_pad($n2,9,'0',STR_PAD_LEFT);
            if ($r2->get('m_role') == 'G')
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
