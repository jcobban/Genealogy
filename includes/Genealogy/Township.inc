<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Township.inc                                                        *
 *                                                                      *
 *  Definition of a class representing a township, specifically as a    *
 *  registration authority for vital statistics.                        *
 *  This class provides access to the information in a row              *
 *  of the table Townships.                                             *
 *                                                                      *
 *      Domain      CHAR(4) NOT NULL DEFAULT 'CAON',                    *
 *      Prov        CHAR(2) NOT NULL DEFAULT 'ON',                      *
 *      County      CHAR(3) NOT NULL DEFAULT '',                        *
 *      Code        VARCHAR(64) NOT NULL DEFAULT '',                    *
 *      Name        VARCHAR(64) NOT NULL DEFAULT '',                    *
 *      Location    INT(10 UNSIGNED DEFAULT NULL,                       *
 *      PRIMARY KEY (`Prov`, `County`, `Code`),                         *
 *      UNIQUE KEY `Domain` (`Domain`, `County`, `Code`),               *
 *      KEY `Name` (`Name`),                                            *
 *      KEY `Location` (`Location`),                                    *
 *      CONSTRAINT `Townships_ibfk_1` FOREIGN KEY (`Location`)          *
 *          REFERENCES `tblLR` (`IDLR`)                                 *
 *          ON DELETE SET NULL ON UPDATE CASCADE )                      *
 *                                                                      *
 *  History:                                                            *
 *      2014/09/29      created                                         *
 *      2014/10/19      province code field deprecated in favor of      *
 *                      domain field                                    *
 *      2014/12/26      send diagnostic information to $warn            *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/01/08      missing ? in prepared statement to delete       *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/06/13      debug output was printed not saved              *
 *      2016/06/19      accept 'township' as a synonym of 'code'        *
 *      2017/02/07      permit passing Domain and County objects        *
 *                      as parms to getTownships                        *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/10/09      in getTownships support debug parameter,        *
 *                      change name to partial match                    *
 *                      warn for unrecognized parameter name            *
 *      2017/12/18      add support for temporary fields                *
 *      2017/12/20      static functions moved to class TownshipSet     *
 *      2018/10/24      improved debugging output from constructor      *
 *                      avoid creating new copies of County, Domain,    *
 *                      and County in constructor                       *
 *      2018/11/11      do not throw exception from constructor for     *
 *                      bad parameters                                  *
 *      2019/04/12      correct handling of county instance of County   *
 *      2020/06/30      add reference to table of Locations             *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';

/************************************************************************
 *  class Township                                                      *
 *                                                                      *
 *  Definition of a class recording information about a single township *
 *  for vital statistics registrations.                                 *
 *                                                                      *
 ************************************************************************/

class Township extends Record
{

    /********************************************************************
     *  Township::$initRow                                              *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'domain'        => 'CAON',
                'prov'          => 'ON',
                'county'        => null,
                'code'          => null,
                'name'          => '',
                'location'      => null);

    /********************************************************************
     *  Township::$info                                                 *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'Townships',
                'name'              => 'Townships',
                'prime'             => array('domain', 'county', 'code'),
                'srvmg'             => false,
                'fldcount'          => 6,
                'order'             => 'Name',
                'classname'         => 'Township');

    /********************************************************************
     *  Township::$translate                                            *
     *                                                                  *
     *  Define alternate field names                                    *
     ********************************************************************/
    protected static $translate = array(
                'idlr'              => 'location');

    /********************************************************************
     *  Township::$defaultOrder                                         *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'Name';

    /********************************************************************
     *  function Township::__construct                                  *
     *                                                                  *
     *  Construct an instance of Township based upon the                *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      associative array of parameters to select or    *
     *                  initialize an instance of Township              *
     *      $create     if true create a new instance if not present    *
     *                                                                  *
     *  Returns object containing a description of a township           *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms,
                         $create    = false)
    {
        global $debug;
        global $warn;
        global $msg;
        global $connection;

        $needInsert             = false;
        $this->table            = 'Townships';
        $domain                 = null;
        $domainObj              = null;
        $prov                   = null;
        $countyObj              = null;
        $code                   = null;
        $name                   = null;

        if ($debug)
            $warn   .= "<p>Township.inc " . __LINE__ ." Township::__construct(";
        // validate parameters
        if (is_array($parms))
        {                           // valid first parameter
            if ($debug)
                $warn                   .= "array(";
            $comma                      = '';
            foreach($parms as $fldname => $value)
            {                       // loop through all parameters
                switch(strtolower($fldname))
                {                   // act on specific field names
                    case 'domain':
                    {               // registration domain
                        if ($value instanceof Domain)
                        {
                            $domainObj  = $value;
                            if ($debug)
                                $warn   .= $comma . "domain=Domain('" .
                                            $domainObj->getName()."')";
                        }
                        else
                        {
                            $domainObj  = new Domain(array('domain'   => $value,
                                                           'language' => 'en'));
                            if ($debug)
                                $warn   .= $comma . "domain='$value'";
                        }

                        if ($domainObj->isExisting())
                        {
                            $domain     = $domainObj->get('domain');
                            $prov       = substr($domain, 2);
                        }
                        else
                            $this->msg  = "Township::__construct: " .
                                            "Invalid value Domain='$value'. ";
                        break;
                    }               // registration domain

                    case 'prov':
                    {               // province code within Canada
                        if (is_null($domainObj))
                        {           // only if domain not already defined
                            $prov       = $value;
                            if ($debug)
                                $warn   .= $comma . "prov='$value'";
                            $domain     = 'CA' . $prov;
                            $domainObj  = new Domain(array('domain'  => $domain,
                                                           'language'=> 'en'));
                            if (!$domainObj->isExisting())
                                $this->msg  = "Township::__construct: " .
                                                "Invalid value Prov='$prov'.";
                        }
                        break;
                    }               // province code within Canada

                    case 'county':
                    {               // County abbreviation
                        if ($value instanceof County)
                        {
                            $countyObj      = $value;
                            $domainObj      = $countyObj->getDomain();
                            $domain         = $domainObj['domain'];
                            $prov           = $domainObj['prov'];
                            if ($debug)
                                $warn       .= $comma . "county=County('" .
                                                $countyObj->getName()."')";
                            $county         = $countyObj->get('code');
                        }
                        else
                        if (is_string($value))
                        {
                            if ($debug)
                                $warn       .= $comma . "county='$value'";
                            $countyObj      = new County(
                                                array('domain'  => $domainObj,
                                                      'code'    => $value));
                            if ($countyObj->isExisting())
                                $county     = $value;
                        }
                        else
                        {
                            if ($debug)
                                $warn   .= $comma . "county=";
                                            gettype($value) . ' ' .
                                            print_r($value, true);
                            $this->msg  = "Township::__construct: " .
                                        " domain='" . $domainObj->getName() .
                                        "', county=" . gettype($value) . ' ' .
                                        print_r($value, true) .
                                        " invalid type for parameter county. ";
                            $countyObj  = new County(
                                            array('domain'  => $domainObj,
                                                  'code'    => ''));
                            $county     = '';
                        }
                        break;
                    }               // County abbreviation

                    case 'code':
                    case 'township':
                    {               // internal form of township name
                        $code           = $value;
                        if ($debug)
                            $warn       .= $comma . "code='$value'";
                        break;
                    }               // internal form of township name

                    case 'name':
                    {               // external form of township name
                        $name           = $value;
                        if ($debug)
                            $warn       .= $comma . "name='$value'";
                        break;
                    }               // external form of township name

                }                   // act on specific field names
                $comma                  = ', ';
            }                       // loop through all parameters
        }                           // valid first parameter
        else
            $this->msg  .= "Township::__construct: " .
                            "First parameter is not an associative array, " .
                            "it is a " . gettype($parms) . '. ';
        if ($debug)
            $warn       .= '),' . ($create?'true':'false') . ")</p>\n";

        if (is_null($domainObj))
            $this->msg  .= "Township::__construct: " .
                            "Missing mandatory parameter Domain. ";
        if (is_null($countyObj))
            $this->msg  .= "Township::__construct: " .
                            "Missing mandatory parameter County. ";
        if (is_null($code))
            $this->msg  .= "Township::__construct: " .
                            "Missing mandatory parameter Code. ";

        if (is_null($name))
        {                           // no initializer for name
            // construct the record from a query
            $query              = "SELECT * FROM Townships  " .
                "WHERE `Domain`=:domain AND `County`=:county AND `Code`=:code";
            $sqlParms           = array('domain'    => $domain,
                                        'county'    => $county,
                                        'code'      => $code);
            if ($debug)
            {
                $warn           .= "<p>Township.inc " . __LINE__ .
                            " Township::__construct: $query, " .
                            "eparms=" . print_r($sqlParms, true) . "</p>\n";
            }

            // query the database
            $stmt                       = $connection->prepare($query);
            $queryText                  = debugPrepQuery($query, $sqlParms);
            if ($stmt->execute($sqlParms))
            {                       // successful query

                $result                 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count                  = count($result);
                if ($count == 1)
                {                   // existing record
                    $dbrow              = $result[0];
                    $needInsert         = false;
                }                   // existing record
                else
                if ($count == 0)
                {                   // no matching record
                    // set defaults
                    $dbrow              = self::$initRow;
                    $dbrow['domain']    = $domain;
                    $dbrow['prov']      = $prov;
                    $dbrow['county']    = $county;
                    $dbrow['code']      = $code;
                    $dbrow['name']      = $code;
                    $needInsert         = true;
                }                   // no matching record
                else
                    $this->msg          .= "Township::__construct: " .
    "$count records match Domain='$domain', County='$county', and Code='$code'. ";
            }                       // successful query
            else
            {                       // error performing query
                $this->msg  .= "Township::__construct: " .
                                "'$queryText' " .
                                print_r($stmt->errorInfo(),true) . '. ';
            }                       // error performing query
        }                           // construct the record from a query
        else
        if (count($parms) == count(self::$initRow))
        {                           // build from explicit array
            $dbrow                      = $parms;
            $dbrow['domain']            = $domainObj->get('domain');
            $dbrow['county']            = $countyObj->get('code');
        }                           // build from explicit array
        else
            $this->msg  .= "Township::__construct: " .
                          "Invalid parameter " . var_export($parms, true) . '. ';

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Townships');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Township Record constructed:');

        $msg                .= $this->msg;
    }       // function Township::__construct

    /********************************************************************
     *  function Township:set                                           *
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
     *      the value of the field                                      *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function set($field, $value)
    {
        global          $msg;
        $fieldLc                    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc                = self::$translate[$fieldLc];
        switch($fieldLc)
        {                       // act on specific fields
            case 'code':
            case 'name':
            {
                return parent::set($field, $value);
            }

            case 'location':
            {
                if (is_string($value) && ctype_digit($value))
                    $value          = intval($value);
                if (is_int($value) && $value > 0)
                    return parent::set($field, $value);
                else
                {               // invalid new value
                    return $this->row[$fieldLc];
                }               // invalid new value
            }                   // location identifier

            default:
            {
                return parent::set($field, $value);
            }
        }                       // act on specific fields
    }       // function Township::set

    /********************************************************************
     *  function Township::save                                         *
     *                                                                  *
     *  Save changes made to the Township record into the database.     *
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

        // report errors detected by constructor and suppress update
        if (strlen($this->msg) > 0)
        {
            $warn   .= "<p>Township::save: " . __LINE__ .
                "Request to update the Township record ignored because of errors detected when the record was created. '" . $this->msg .
                "'</p>\n";
            return 0;
        }

        if (is_string($xml))
        {                   // string tag name
            $tagname            = $xml;
            $xml                = true;
        }                   // string tag name
        else
        if ($xml)
        {                   // true
            $tagname            = 'cmd';
        }                   // true

        if ($this->needInsert)
        {                   // create new record
            $fldnames           = '';
            $comma              = '';
            $values             = '';
            $sqlParms           = array();
            foreach($this->row as $fieldName => $value)
            {
                $fldnames       .= $comma . "`$fieldName`";
                $values         .= $comma . '?';
                array_push($sqlParms, $value);
                $comma          = ", ";
            }               // loop through all fields in record

            // construct the SQL INSERT command
            $insert = "INSERT INTO Townships ($fldnames) VALUES($values)";
            $stmt               = $connection->prepare($insert);
            $insertText         = debugPrepQuery($insert, $sqlParms);
            // insert the new record into the database
            if ($stmt->execute($sqlParms))
            {               // successful insert
                $count          = $stmt->rowCount();
                if ($xml)
                    print "<$tagname result='$count'>" . $insertText .
                            "</$tagname>\n";
                if ($debug)
                {
                    print "<p>$insertText</p>\n";
                    print "<p>count=$count</p>\n";
                }

                $this->changed      = array();  // reset
                $this->needInsert   = false;

                // log the update
                $this->log($insert,
                           $sqlParms,
                           0);      // compound key
            }               // successful insert
            else
            {               // error inserting record
                throw new Exception("Township::save:
                                '$insertText', " .
                                print_r($stmt->errorInfo(),true));
            }               // error inserting record
        }                   // create new record
        else
        {                   // update existing record
            if ($this->changed && count($this->changed) > 0)
            {               // changes made
                $set            = '';
                $comma          = '';
                $sqlParms       = array();
                foreach($this->changed as $fieldName => $value)
                {
                    $set        .= $comma . "`$fieldName`=?";
                    array_push($sqlParms, $value);
                    $comma      = ', ';
                }           // loop through all fields in record
                $this->changed  = array();  // reset

                // construct the where clause
                if ($this->former)
                {
                    array_push($sqlParms,$this->former['domain']);
                    array_push($sqlParms,$this->former['county']);
                    array_push($sqlParms,$this->former['code']);
                }
                else
                {
                    array_push($sqlParms,$this->row['domain']);
                    array_push($sqlParms,$this->row['county']);
                    array_push($sqlParms,$this->row['code']);
                }

                // assemble the UPDATE command
                $update         = "UPDATE Townships SET " . $set .
                                    ' WHERE Domain=? AND County=? AND Code=?';
                $stmt           = $connection->prepare($update);
                $updateText     = debugPrepQuery($update, $sqlParms);
                // perform the update
                if ($stmt->execute($sqlParms))
                {           // successful update
                    $count      = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>" .
                                $updateText . "</$tagname>\n";
                    if ($debug)
                        $warn .= "<p>$updateText</p>\n";

                    // log the update
                    $this->log($update,
                               $sqlParms,
                               0);
                }           // successful update
                else
                {           // error updating record
                    print "<p>query='$updateText'</p>\n";
                    throw new Exception("Township::save: " . __LINE__ . " " .
                                print_r($stmt->errorInfo(),true));
                }           // error updating record
            }               // changes made
        }                   // update existing record
    }       // function Township::save

    /********************************************************************
     *  function Township::delete                                       *
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
    function delete($xml=false)
    {
        global  $debug;
        global  $connection;

        // handle XML output option
        if (is_string($xml))
            $xmltag             = $xml;
        else
        if ($xml)
        {
            $xml                = true;
            $xmltag             = 'cmd';
        }

        // to simplify expressions
        if (!$this->needInsert)
        {                   // have existing record
            $parms              = array($this->row['domain'],
                                        $this->row['county'],
                                        $this->row['code']);
            $delete             = "DELETE FROM Townships WHERE" .
                                    ' Domain=? AND County=? AND Code=?';
            $stmt               = $connection->prepare($delete);
            $deleteText         = debugPrepQuery($delete, $parms);
            if ($stmt->execute($parms) === false)
            {               // error updating record
                print "<p>$deleteText</p>\n";
                print "<p class='message'>" . htmlentities(print_r($stmt->errorInfo(),true)) .
                            "</p>\n";
                return false;
            }               // error updating record

            $count              = $stmt->rowCount();
            if ($xml)
                print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

            // log the update
            $this->log($delete,
                       $parms);

            // update the memory copy of the record to reflect that it
            // no longer represents a database record
            $this->needInsert   = true;

            return $count;  // number of records deleted
        }                   // have key value

        // record never written to database
        return 0;           // nothing to delete
    }       // function Township::delete

    /********************************************************************
     *  function Township::getName                                      *
     *                                                                  *
     *  Convenience function for accessing the 'name' attribute.        *
     *                                                                  *
     *  Returns:                                                        *
     *      Name of the township as a string.                           *
     ********************************************************************/
    function getName($options   = null)
    {
        return $this->get('name');
    }       // function Township::getName

    /********************************************************************
     *  function Township::__toString                                   *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return  $this->row['code'];
    }       // function Township::__toString

}       // class Township
