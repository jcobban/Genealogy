<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  MethodistBaptism.inc                                                *
 *                                                                      *
 *  Definition of a class representing a record in the                  *
 *  MethodistBaptisms table that represents a single registration.      *
 *                                                                      *
 *  CREATE TABLE `MethodistBaptisms` (                                  *
 *      `IDMB`          INT(11) NOT NULL AUTO_INCREMENT,                *
 *      `Volume`        INT(11),                                        *
 *      `Page`          INT(11),                                        *
 *      `District`      VARCHAR(128),                                   *
 *      `Area`          VARCHAR(128),                                   *
 *      `GivenName`     VARCHAR(128),                                   *
 *      `Surname`       VARCHAR(128),                                   *
 *      `Father`        VARCHAR(128),                                   *
 *      `Mother`        VARCHAR(128),                                   *
 *      `Residence`     VARCHAR(128),                                   *
 *      `BirthPlace`    VARCHAR(128),                                   *
 *      `BirthDate`     VARCHAR(128),                                   *
 *      `BaptismDate`   VARCHAR(128),                                   *
 *      `BaptismPlace`  VARCHAR(128),                                   *
 *      `Minister`      VARCHAR(128),                                   *
 *      `IDIR`          INT(11),                                        *
 *      PRIMARY KEY (`IDMB`),                                           *
 *      KEY `Volume` (`Volume`),                                        *
 *      KEY `Page` (`Page`),                                            *
 *      KEY `District` (`District`),                                    *
 *      KEY `Area` (`Area`),                                            *
 *      KEY `GivenName` (`GivenName`),                                  *
 *      KEY `Surname` (`Surname`),                                      *
 *      KEY `Father` (`Father`),                                        *
 *      KEY `Mother` (`Mother`) )                                       *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *  History:                                                            *
 *      2016/02/21      created                                         *
 *      2016/11/02      permit comparison operators in search           *
 *      2017/05/29      treat all string parameters to search as        *
 *                      regular expressions.                            *
 *      2017/12/18      add support for temporary fields                *
 *                                                                      *
 *  Copyright &copy; 2017 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

if (!class_exists(__NAMESPACE__ . '\\MethodistBaptism'))
{

/************************************************************************
 *  class MethodistBaptism                                              *
 *                                                                      *
 *  Definition of a class implementing the interface to a record        *
 *  in the Wesleyan Methodist Baptisms table.                           *
 ************************************************************************/

class MethodistBaptism extends Record
{

    /********************************************************************
     *      $initRow        array containing default values for         *
     *                      each field in a new record                  *
     ********************************************************************/
    protected static    $initRow    = array(
                        'idmb'                  => 0,
                        'volume'                => 0,
                        'page'                  => 0,
                        'district'              => '',
                        'area'                  => '',
                        'givenname'             => '',
                        'surname'               => '',
                        'father'                => '',
                        'mother'                => '',
                        'residence'             => '',
                        'birthplace'            => '',
                        'birthdate'             => '',
                        'baptismdate'           => '',
                        'baptismplace'          => '',
                        'minister'              => '',
                        'idir'                  => '');

    /********************************************************************
     *  MethodistBaptism::$info                                         *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'     => 'MethodistBaptisms',
                'name'      => 'MethodistBaptisms',
                'prime'     => 'idmb',
                'srvmg'     => true,
                'fldcount'  => 16,
                'order'     => 'Volume, Page, IDMB',
                'classname' => 'MethodistBaptism');

    /********************************************************************
     *  MethodistBaptism::$translate                                    *
     *                                                                  *
     *  standard alternative field name identifiers                     *
     ********************************************************************/
    protected static    $translate  = array();

    /********************************************************************
     *  function MethodistBaptism::__construct                          *
     *                                                                  *
     *  Given defining parameters construct an instance.                *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      associative array of parameters                 *
     *                  for example:                                    *
     *                      array('idmb'        => $idmb)               *
     *                  or an array representing an already fetched     *
     *                  record                                          *
     *                                                                  *
     *  Returns object containing MethodistBaptism record.              *
     ********************************************************************/
    function __construct($parms)
    {
        global  $debug;
        global  $warn;
        global  $connection;
        global  $userid;

        $needInsert                 = true;
        $errors                     = '';

        if ($debug)
            $warn                   .= "<p>MethodistBaptism::__construct(" .
                                        print_r($parms, true) . ")</p>\n";

        if (is_array($parms))
        {                   // associative array
            if (count($parms) >= count(self::$initRow))
            {               // complete row from database
                $dbrow              = $parms;
                $needInsert         = false;
            }               // complete row from datase
            else
            {               // key and optional other parms
                $idmb               = 0;

                if (array_key_exists('idmb', $parms) &&
                    ($idmb = $parms['idmb']) > 0)
                {           // initialize from database
                    // construct the query of the user table
                    $query          = 'SELECT * FROM MethodistBaptisms ' .
                                        'WHERE IDMB=:idmb';
                    $sqlParms       = array('idmb'  => $idmb);

                    // query the database
                    $stmt           = $connection->prepare($query);
                    $queryText      = debugPrepQuery($query, $sqlParms);

                    if ($stmt->execute($sqlParms))
                    {       // success
                        if ($debug)
                            $warn   .= "<p>MethodistBaptism::__construct: ".
                                   "$queryText</p>";
                        $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0)
                        {       // found matching record
                            $dbrow          = $result[0];
                            $needInsert     = false;
                        }       // found matching record
                        else
                        {       // invalid key
                            $dbrow          = self::$initRow;
                            $needInsert     = true;
                        }       // invalid key
                    }       // success
                    else
                    {       // error performing query
                        $this->msg  .= "MethodistBaptism::__construct: " .
                                        "query='$queryText', " .
                                        print_r($stmt->errorInfo(),true);
                    }       // error performing query
                }           // initialize from database
            }               // key and optional other parms
        }                   // associative array
        else
        {                   // unexpected parameter type
            $this->msg      .= 'MethodistBaptism::__construct: called with ' .
                                gettype($parms);
        }                   // unexpected parameter type

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'MethodistBaptisms');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('MethodistBaptism Record constructed:');
    }       // MethodistBaptism::__construct
}       // class MethodistBaptism
}       // not already declared
