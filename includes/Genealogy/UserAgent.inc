<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  UserAgent.inc                                                      *
 *                                                                      *
 *  Definition of a class representing the collection of information    *
 *  for a particular advertiser.                                        *
 *  This class provides access to the information in a row of the       *
 *  table UserAgents.                                                   *
 *                                                                      *
 *  Record Structure Reference                                          *
 *      CREATE TABLE UserAgents (                                       *
 *      Agent       VARCHAR(255),               -- file name            *
 *      Counter     INT(10) NOT NULL DEFAULT 0, -- counter              *
 *      PRIMARY KEY (agent),                                           *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/18      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class UserAgent                                                     *
 *                                                                      *
 *  Definition of a class implementing behavior for tracking            *
 *  user agents.                                                        *
 *                                                                      *
 ************************************************************************/

class UserAgent extends Record
{

    /********************************************************************
     *  UserAgent::$initRow                                            *
     *                                                                  *
     *  static array containing default values for all fields in a      *
     *  new record                                                      *
     ********************************************************************/
    protected static    $initRow    = array(
                'agent'             => '',
                'counter'           => 0);

    /********************************************************************
     *  UserAgent::$info                                               *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'UserAgents',
                'name'              => 'UserAgents',
                'prime'             => 'agent',
                'srvmg'             => false,
                'fldcount'          => 2,
                'order'             => 'Agent',
                'classname'         => 'UserAgent');

    /********************************************************************
     *  UserAgent::$translate                                           *
     *                                                                  *
     *  array containing synonyms for field names                       *
     ********************************************************************/
    protected static    $translate  = array(
                'name'              => 'agent');

    /********************************************************************
     *  function UserAgent::__construct                                 *
     *                                                                  *
     *  Construct an instance of an UserAgent statistic record from     *
     *  table UserAgents.                                               *
     *                                                                  *
     *  Input:                                                          *
     *      $id         a database record presented as an array         *
     *                  or an associative array of search parameters    *
     *                                                                  *
     *  Returns object containing record.                               *
     ********************************************************************/
    function __construct($id)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        $needInsert             = false;

        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

        if (is_array($id))
        {                           // array
            $parms              = $id;
        }                           // array
        else
        if (is_string($id))
        {
            $parms              = array('agent' => $id);
        }
        else
        {                           // unexpected parameter type
            $this->msg          .= "UserAgent::__construct: called with " .
                                        gettype($id) . ", value=" . var_export($id, true) . ". ";
            $parms              = array();
        }                           // unexpected parameter type

        // report parameter validation errors
        if (count($parms) >= count(UserAgent::$initRow))
        {                           // complete row from database
            $dbrow              = $parms;
            $where              = '';
            if (!array_key_exists('agent', $dbrow))
            {                       // row missing primary key
                $this->msg      .= "UserAgent::__construct: invalid row ";
                $comma          = 'array(';
                foreach($dbrow as $key => $value)
                {
                    $this->msg  .= "$comma'$key'=>" .
                                    htmlspecialchars($value);
                    $comma      = ',';
                }
                $this->msg      .= "). ";
                $dbrow          = UserAgent::$initRow;
            }                       // row missing primary key
        }                           // complete row from database
        else
        {                           // process associative array parameter
            $where              = '';
            $sqlParms           = array();
            $and                = 'WHERE ';
            foreach($parms as $fieldname => $value)
            {                       // loop through search parameters
                $fieldNameLc    = strtolower($fieldname);
                switch($fieldNameLc)
                {
                    case 'agent':
                    {
                        if (is_string($value))
                        {
                            $agent             = $value;
                            $where              .= $and . "`agent`=:agent";
                            $sqlParms['agent'] = $agent;
                            break 2;
                        }
                        else
                        {
                            $value              = htmlspecialchars($value);
                            $this->msg          .= "UserAgent::__construct: " .
                                "`agent` value '$value' invalid. ";
                            $agent             = '';
                        }
                        break;
                    }

                    case 'row':
                    {
                        if (is_int($value) || ctype_digit($value))
                        {
                            $where              .= $and . "`Row`>=:row";
                            $sqlParms['row']    = $value;
                            break 2;
                        }
                        else
                        {
                            if (is_null($value))
                                $value          = 'NULL';
                            else
                                $value          = htmlspecialchars($value);
                            $this->msg          .= "UserAgent::__construct: " .
                                "`Row` value '$value' invalid. ";
                        }
                        break;
                    }

                }                   // act on specific field names
            }                       // loop through search parameters
    
            if (strlen($where) > 0)
            {   // obtain the first instance that matches search parameters
                $query      = "SELECT * FROM UserAgents $where LIMIT 1";
    
                // query the database
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
                if ($stmt->execute($sqlParms))
                {           // successful query
                    $row                = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($debug)
                        $warn           .= "<p>UserAgent: " . __LINE__ .
                                              "'$queryText'</p>\n";
    
                    if (is_array($row))
                    {               // found advertising statistic record
                        $dbrow          = $row;
                        $needInsert     = false;
                    }               // found advertising statistic record
                    else
                    if (isset($sqlParms['row']))
                    {               // wrap around to first
                        $query  = "SELECT * FROM UserAgents WHERE Row>0 LIMIT 1";
                        $stmt           = $connection->prepare($query);
                        $queryText      = $query;
                        $this->lastSqlCmd= $queryText;
                        if ($stmt->execute($sqlParms))
                        {           // successful query
                            $row        = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($debug)
                                $warn   .= "<p>UserAgent: " . __LINE__ .
                                                      "'$queryText'</p>\n";
            
                            if (is_array($row))
                            {       // found advertising statistic record
                                $dbrow      = $row;
                                $needInsert = false;
                            }       // found advertising statistic record
                            else
                            {       // create new record
                                $dbrow              = UserAgent::$initRow;
                                foreach($parms as $fieldname => $value)
                                {   // apply updates
                                    $dbrow[strtolower($fieldname)]  = $value;
                                }   // apply updates
                                $needInsert = true;
                            }       // create new record
                        }           // successful query
                        else
                        {           // query failed
                            $this->msg  .= "UserAgent::__construct: '$queryText', " .
                                print_r($stmt->errorInfo(), true) .". ";
                            $dbrow              = UserAgent::$initRow;
                        }           // error performing query
                    }               // wrap around to first
                    else
                    {               // create new record
                        $dbrow              = UserAgent::$initRow;
                        foreach($parms as $fieldname => $value)
                        {           // apply updates
                            $dbrow[strtolower($fieldname)]  = $value;
                        }           // apply updates
                        $needInsert         = true;
                    }               // create new record
                }                   // successful query
                else
                {                   // query failed
                    $this->msg  .= "UserAgent::__construct: '$queryText', " .
                                        print_r($stmt->errorInfo(), true) .". ";
                    $dbrow              = UserAgent::$initRow;
                }                   // error performing query
            }                       // query using search parms
            else
            {
                $this->msg .= "UserAgent::__construct: no selection parameters. ";
                $dbrow              = UserAgent::$initRow;
            }
        }                           // process associative array parameter

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'UserAgents');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('UserAgent Record constructed:');

        $msg    .= $this->msg;
    }       // UserAgent::__construct

    /********************************************************************
     *  function UserAgent::get                                        *
     *                                                                  *
     *  Obtain the current value of a field in the object.              *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to retrieve                       *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function get($field)
    {
        global  $warn;

        $fieldLc        = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];

        return parent::get($fieldLc);
    }           // function UserAgent::get

    /********************************************************************
     *  function UserAgent::getName                                    *
     *                                                                  *
     *  Get the name of the advertising statistic.                      *
     *                                                                  *
     *  Input:                                                          *
     *      optional translate table for localization                   *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getName($options   = null)
    {
        return $this->row['agent'];
    }       // UserAgent::getName

    /********************************************************************
     *  function UserAgent::set                                        *
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
     *      previous value of the field                                 *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds trace if $field is not already defined as a field      *
     *      or pseudo-field and debugging is enabled                    *
     ********************************************************************/
    function set($field, $value)
    {
        global      $debug;
        global      $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'counter':
            {       // counters
                // you can only reset
                if ($value == 0)    
                    return parent::set($fieldLc, $value);
                else
                {
                    return $this->row[$fieldLc];
                }
            }       // counters

            case 'agent':
            {       // associated advertiser name
                return parent::set($fieldLc, $value);
            }       // associated advertiser name

        }           // switch on field name
    }       // UserAgent::set

    /********************************************************************
     *  function UserAgent::increment                                  *
     *                                                                  *
     *  Increment an access (click) counter.                            *
     *                                                                  *
     *  Parameters:                                                     *
     *      $month      month number (Jan = 1)                          *
     *                                                                  *
     ********************************************************************/
    function increment()
    {
        global  $debug;
        global  $warn;
        global  $connection;

        error_log('UserAgent::increment: ' . __LINE__); 
        $increment     = 'UPDATE UserAgents SET Counter=Counter+1';
        $stmt                   = $connection->prepare($increment);
        if ($stmt->execute())
            error_log('UserAgent::increment: cmd=' . $increment); 
        else
        {
            $errorInfo  =  print_r($stmt->errorInfo(),true);
            error_log("UserAgent.inc: " . __LINE__ .
                                " increment='$increment', " .
                                "errorInfo=$errorInfo\n");
        }
    }           // function UserAgent::increment

    /********************************************************************
     *  function UserAgent::save                                       *
     *                                                                  *
     *  Save changes made to the UserAgent record into the             *
     *  database.                                                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  if a string use it as tag name                  *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1           if the method updated the database record       *
     *      0           if the database did not need to be updated      *
     *                                                                  *
     *  Throws:                                                         *
     *      Exception if user is not authorized to update the database  *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $count                  = 0;

        if ($this->needInsert)
        {               // create new record
            $fldnames           = '';
            $values             = '';
            $sqlParms           = array();
            $comma              = '';
            $agent             = '';
            foreach($this->row as $field => $value)
            {
                switch($field)
                {           // act on field name
                    case 'agent':
                    {       // primary key
                        $agent                  = $value;
                        $fldnames               .= "$comma`$field`";
                        $values                 .= "$comma:$field";
                        $sqlParms[$field]       = $value;
                        break;
                    }       // primary key

                    default:
                    {       // all other fields
                        $fldnames               .= "$comma`$field`";
                        $values                 .= "$comma:$field";
                        $sqlParms[$field]       = $value;
                        break;
                    }       // all other fields
                }           // switch on field name
                $comma                          = ',';
            }               // loop through all fields in record

            $this->changed      = array();  // reset

            // construct the SQL INSERT command
            $insert     = 'INSERT INTO UserAgents ' .
                                "($fldnames) " .
                                "VALUES($values)";

            // insert the new record into the database
            $stmt                   = $connection->prepare($insert);
            $insertText             = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd       = $insertText;
            if ($stmt->execute($sqlParms))
            {                   // success
                $count              = $stmt->rowCount();

                if ($debug)
                {
                    $warn   .= "<p>UserAgent.inc: " . __LINE__ .
                                        " '$insertText'" .
                                        ", count=$count</p>\n";
                }

                $this->needInsert   = false;
                // log the update
                $this->log($insert, $sqlParms);
            }                   // success
            else
            {                   // error inserting record
                $errorInfo  =  print_r($stmt->errorInfo(),true);
                $warn   .= "<p>UserAgent::save: " . __LINE__ .
                                " insert='$insertText'," .
                                " errorInfo=$errorInfo<p>\n";
                error_log("UserAgent.inc: " . __LINE__ .
                                " insert='$insertText', " .
                                "errorInfo=$errorInfo\n");
                if (strlen($insert) > 43)
                    $insert = substr($insert, 0, 40) . '...';
                $this->msg      =
                            "UserAgent::save: '$insertText', $errorInfo"; 
            }                   // error inserting record

        }                       // create new record
        else
        if (count($this->changed) > 0)
        {                       // update existing record
            $set        = '';
            $comma      = '';
            $parms      = array();
            foreach($this->changed as $fld => $value)
            {       // loop through all modified fields in record
                $set                .= $comma . "`$fld`=:$fld";
                $parms[$fld]        = $value;
                $comma              = ', ';
            }       // loop through all modified fields in record

            // if there are any changed fields, update the database
            if (strlen($set) > 0)
            {       // at least one field modified
                // if any fields modified then former must be set
                // using former to get the key of the record permits
                // changing the value of the primary key as long as it
                // is not changed to an already defined value
                if (isset($this->former))
                    $prime          = $this->former['agent'];
                else
                    $prime          = $this->row['agent'];
                $update             = "UPDATE UserAgents SET $set" .
                                          " WHERE agent=:prime";
                $parms['prime']     = $prime;

                $stmt               = $connection->prepare($update);
                $updateText         = debugPrepQuery($update, $parms);
                $this->lastSqlCmd   = $updateText;
                if ($stmt->execute($parms))
                {               // successful update
                    $count          = $stmt->rowCount();    // changed rows
                    if ($debug)
                        $warn       .= "<p>" . $updateText . "</p>\n";

                    // log the update
                    $this->log($update, $parms);
                }               // successful update
                else
                {               // error updating record
                    $this->msg      .= $updateText . ": error=" .
                               print_r($stmt->errorInfo(),true) . ". ";
                    return false;
                }               // error updating record
            }                   // at least one field modified
            else
            {
                $count              = 0;    // nothing changed
                $this->lastSqlCmd   = '';
            }
        }                       // update existing record

        // all changes have been applied
        $this->changed  = array();  // reset
        $this->former   = null;     // reset

        // successful, return count of number of records updated
        return $count;
    }       // function UserAgent::save

}       // class UserAgent 
