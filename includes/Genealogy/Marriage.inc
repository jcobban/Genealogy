<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Marriage.inc                                                        *
 *                                                                      *
 *  Definition of a class representing a marriage registration          *
 *  in a genealogical database.  This class provides access to the      *
 *  information in a row of the table Marriage.                         *
 *                                                                      *
 *  CREATE TABLE `Marriage` (                                           *
 *          `RegDomain` VARCHAR(4) NOT NULL DEFAULT 'CAON',             *
 *          `RegYear` INT(11) NOT NULL,                                 *
 *          `RegNum` INT(11) NOT NULL,                                  *
 *          `MsVol` VARCHAR(5) DEFAULT '',                              *
 *          `RegCounty` CHAR(3) DEFAULT '',                             *
 *          `RegTownship` VARCHAR(64) DEFAULT NULL,                     *
 *          `Date` VARCHAR(16) DEFAULT '',                              *
 *          `CalcDate` DATE DEFAULT NULL,                               *
 *          `Place` VARCHAR(128) DEFAULT NULL,                          *
 *          `LicenseType` CHAR(1) DEFAULT 'L',                          *
 *          `Remarks` LONGTEXT,                                         *
 *          `ChangedBy` VARCHAR(64) DEFAULT '',                         *
 *          `ChangeDate` DATE DEFAULT NULL,                             *
 *          `Image` VARCHAR(256) NOT NULL DEFAULT '',                   *
 *          `Registrar` VARCHAR(128) NOT NULL DEFAULT '',               *
 *          `RegDate` VARCHAR(16) NOT NULL DEFAULT '',                  *
 *          `OriginalVolume` VARCHAR(16) DEFAULT NULL,                  *
 *          `OriginalPage` INT(11) DEFAULT NULL,                        *
 *          `OriginalItem` INT(11) DEFAULT NULL,                        *
 *          PRIMARY KEY (`RegDomain`,                                   *
 *          `RegYear`,                                                  *
 *          `RegNum`),                                                  *
 *          KEY `RegCounty` (`RegCounty`),                              *
 *          KEY `RegTownship` (`RegTownship`),                          *
 *          KEY `Place` (`Place`) )                                     *
 *          ENGINE=InnoDB DEFAULT CHARSET=utf8                          *
 *                                                                      *
 *  History:                                                            *
 *      2013/06/10      created                                         *
 *      2013/08/09      class renamed to Marriage because it has        *
 *                      nothing to do with Legacy                       *
 *      2014/01/21      complete implementation                         *
 *      2014/01/25      missing fields m_calcdate, m_regdate,           *
 *                      and m_registrar in default record               *
 *      2014/03/05      dependent MarriageParticipant records not       *
 *                      created if invoked with record keys             *
 *                      method getGroom returned bride                  *
 *      2014/10/10      add country code to default location            *
 *                      and support all domains                         *
 *                      missing include of MarriageParticipant          *
 *      2014/12/26      diagnostic information sent to $warn            *
 *      2015/05/01      default image URL to previous record            *
 *      2015/07/01      unmatched tag in diagnostic output from         *
 *                      constructor                                     *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2017/02/17      add fields OriginalVolume, OriginalPage, and    *
 *                      OriginalItem                                    *
 *                      support searching for record in constructor     *
 *      2017/03/13      permit creating new Marriage given only the     *
 *                      pre-1873 volume number, page, and item          *
 *                      fill in default year and reel for new pre-1873  *
 *                      marriage registrations.                         *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/11/07      convert queries to prepared statements          *
 *      2017/12/18      add support for temporary fields                *
 *      2017/12/22      support temporary fields in constructor         *
 *      2018/02/24      numvolume not initialized                       *
 *                      county, township, etc. not initialized from     *
 *                      preceding entry if constructor called with      *
 *                      search parms in first parameter                 *
 *      2018/05/28      MySQL started objecting to assigning an         *
 *                      empty string to an INT, so ensure INT fields    *
 *                      set to NULL for empty string                    *
 *      2018/12/11      ensure m_calcdate always valid                  *
 *      2019/01/24      add missing method toJson                       *
 *      2019/06/12      improve validation of values assigned to Dates  *
 *      2019/10/28      permit spaces in front of save parm             *
 *                      return count from save                          *
 *      2019/12/13      remove M_ prefix from field names               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2020/01/16      OriginalPage changed to VARCHAR                 *
 *      2020/06/13      undefined $insert                               *
 *      2020/09/09      support changing primary key of record          *
 *                      update lastSqlCmd                               *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/County.inc';

/************************************************************************
 *  Marriage                                                            *
 *                                                                      *
 *  Definition of a class implementing behavior for Marriage            *
 *  registration records within a genealogy database.                   *
 ************************************************************************/

class Marriage extends Record
{

    /********************************************************************
     *  Marriage::$initRow                                              *
     *                                                                  *
     *  static array containing default values for a new record         *
     ********************************************************************/
    protected static    $initRow    = array(
                        'regdomain'             => 'CAON',
                        'regyear'               => 2014,
                        'regnum'                => 0,
                        'msvol'                 => '0',
                        'regcounty'             => '',
                        'regtownship'           => '',
                        'date'                  => '',
                        'calcdate'              => null,
                        'place'                 => '',
                        'licensetype'           => 'L',
                        'remarks'               => '',
                        'changedby'             => '',
                        'changedate'            => '',
                        'image'                 => '',
                        'registrar'             => '',
                        'regdate'               => '',
                        'originalvolume'        => '',
                        'originalpage'          => '',
                        'originalitem'          => '');

    /********************************************************************
     *  Marriage::$info                                                 *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                        'table'             => 'Marriage',
                        'name'              => 'Marriage',
                        'prime'             =>
                            array('RegDomain','RegYear','RegNum'),
                        'srvmg'             => true,
                        'fldcount'          => 19,
                        'order'             => 'RegDomain, RegYear, RegNum',
                        'classname'         => 'Marriage');

    /********************************************************************
     *  Marriage::$translate                                            *
     *                                                                  *
     *  static array containing field name abbreviation translations    *
     ********************************************************************/
    protected static    $translate  = array(
                        'domain'                    => 'regdomain',
                        'm_regdomain'               => 'regdomain',
                        'year'                      => 'regyear',
                        'm_regyear'                 => 'regyear',
                        'num'                       => 'regnum',
                        'number'                    => 'regnum',
                        'm_regnum'                  => 'regnum',
                        'reel'                      => 'msvol',
                        'm_msvol'                   => 'msvol',
                        'county'                    => 'regcounty',
                        'm_regcounty'               => 'regcounty',
                        'township'                  => 'regtownship',
                        'm_regtownship'             => 'regtownship',
                        'm_date'                    => 'date',
                        'm_calcdate'                => 'calcdate',
                        'm_place'                   => 'place',
                        'm_licensetype'             => 'licensetype',
                        'm_remarks'                 => 'remarks',
                        'm_changedby'               => 'changedby',
                        'm_changedate'              => 'changedate',
                        'm_image'                   => 'image',
                        'm_registrar'               => 'registrar',
                        'm_regdate'                 => 'regdate',
                        'm_originalvolume'          => 'originalvolume',
                        'm_originalpage'            => 'originalpage',
                        'm_originalitem'            => 'originalitem');

    /********************************************************************
     *  Marriage::$defaultOrder                                         *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'RegDomain, RegYear, RegNum';

    /********************************************************************
     *  Marriage::$pre1873Years                                         *
     *                                                                  *
     *  static array containing default values for registration year    *
     *  by volume number for pre-1873 marriage registrations            *
     *                                                                  *
     *  Note the problem caused by the one non-integer volume number    *
     ********************************************************************/
    static  $pre1873Years   = array(
                                 0  => array('year' => 1869, 'reel' => 1),
                                 1  => array('year' => 1869, 'reel' => 1),
                                 2  => array('year' => 1869, 'reel' => 1),
                            // 2/3  => array('year' => 1869, 'reel' => 1),
                                 3  => array('year' => 1869, 'reel' => 1),
                                 4  => array('year' => 1870, 'reel' => 2),
                                 5  => array('year' => 1870, 'reel' => 2),
                                 6  => array('year' => 1870, 'reel' => 2),
                                 7  => array('year' => 1870, 'reel' => 3),
                                 8  => array('year' => 1870, 'reel' => 3),
                                 9  => array('year' => 1870, 'reel' => 3),
                                10  => array('year' => 1870, 'reel' => 3),
                                11  => array('year' => 1870, 'reel' => 4),
                                12  => array('year' => 1871, 'reel' => 4),
                                13  => array('year' => 1871, 'reel' => 4),
                                14  => array('year' => 1871, 'reel' => 4),
                                15  => array('year' => 1871, 'reel' => 5),
                                16  => array('year' => 1871, 'reel' => 5),
                                17  => array('year' => 1871, 'reel' => 5),
                                18  => array('year' => 1871, 'reel' => 5),
                                19  => array('year' => 1871, 'reel' => 6),
                                20  => array('year' => 1872, 'reel' => 6),
                                21  => array('year' => 1872, 'reel' => 6),
                                22  => array('year' => 1872, 'reel' => 7),
                                23  => array('year' => 1872, 'reel' => 7),
                                24  => array('year' => 1872, 'reel' => 7),
                                25  => array('year' => 1872, 'reel' => 8),
                                26  => array('year' => 1872, 'reel' => 8),
                                27  => array('year' => 1872, 'reel' => 8),
                                28  => array('year' => 1872, 'reel' => 8),
                                29  => array('year' => 1872, 'reel' => 9),
                                30  => array('year' => 1872, 'reel' => 9),
                                31  => array('year' => 1872, 'reel' => 9));

    /********************************************************************
     *  $participants                                                   *
     *                                                                  *
     *  Array of instances of MarriageParticipant for each              *
     *  participant in the marriage (Bride, Groom, Minister).           *
     ********************************************************************/
    protected   $participants   = array();

    /********************************************************************
     *  function Marriage::__construct                                  *
     *                                                                  *
     *  Create an instance of a class that describes a                  *
     *  marriage registration                                           *
     *                                                                  *
     *  Input:                                                          *
     *      $domain             4 char administrative domain            *
     *                          (2 char country code + state/prov code) *
     *                          or an associative array obtained from   *
     *                          an SQL query by a static method         *
     *      $year               registration year                       *
     *      $number             registration number within year         *
     *                                                                  *
     *  Returns object containing marriage record.                      *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($domain,
                         $year  = null,
                         $number= null)
    {
        global  $debug;
        global  $warn;
        global  $connection;
        global  $msg;

        $needInsert         = false;
        $this->table        = 'Marriage';
        $cc                 = 'CA';     // ISO country code
        $pc                 = 'ON';     // postal service state/province code
        $addrEnd            = ', ON, CA';   // last part of addresses
        $sqlParms           = array();
        $parmsText          = '';

        // validate parameters
        if (is_string($domain) || $domain instanceof Domain)
        {       // initialize new entry
            $parms              = array('regdomain' => $domain,
                                        'regyear'       => $year,
                                        'regnum'        => $number);
            $parmsText          = "'$domain',$year,$number";
        }
        else
        if (is_array($domain))
        {
            $parms              = $domain;
            $parmsText          = "array(";
            $comma              = '';
            foreach($parms as $field => $value)
            {
                $parmsText      .= "$comma'$field' => '$value'";
                $comma          = ',';
            }
            $parmsText          .= ')';
        }
        else
        {
            $parmsText          = print_r($domain, true);
            $this->msg          .= "Invalid parameter " .
                                            print_r($domain, true) . '. ';
            $parms              = array();
        }
        if ($debug)
            $warn   .= "<p>Marriage.inc: " . __LINE__ .
                        " new Marriage($parmsText)</p>\n";

        if (count($parms) >= count(self::$initRow))
        {           // row returned from database query
            $dbrow              = $parms;
            foreach($dbrow as $field => $value)
            {       // loop through fields
                $fieldLc                = strtolower($field);
                if (!array_key_exists($fieldLc, self::$initRow))
                {       // move any extra fields to extras
                    $this->extras[$fieldLc] = $value;
                    unset($dbrow[$field]);
                }       // move any extra fields to extras
            }       // loop through fields

            $mregdomain                 = $dbrow['regdomain'];
            $mregyear                   = $dbrow['regyear'];
            $mregnum                    = $dbrow['regnum'];

            $needInsert                 = false;
        }       // row returned from database query
        else
        {       // search parameters
            $sel                        = '';
            $and                        = 'WHERE ';
            $sqlParms                   = $parms;
            $year                       = 0;
            $number                     = 0;
            $domain                     = 'CAON';
            $domainObj                  = null;
            $numvolume                  = null;
            foreach($parms as $field => $value)
            {
                $value          = trim($value);
                if (strlen($value) == 0)
                    continue;
                $fieldLc                = strtolower($field);
                if (array_key_exists($fieldLc, self::$translate))
                    $fieldLc            = self::$translate[$fieldLc];
                switch($fieldLc)
                {
                    case 'originalvolume':
                    {
                        if (ctype_digit($value))
                            $numvolume  = $value;
                        else
                        if ($value == '2/3')
                            $numvolume  = 23;
                        else
                        if ($value >= 'A' and $value <= 'Z')
                        {
                            $numvolume  = ord($value) - ord('A') + 1;
                        }
                        else
                        {
                            error_log("Marriage.inc: " . __LINE__ .
                                        " m_originalvolume='$value'\n");
                        }
                        $number         += $numvolume * 10000;
                        break;
                    }

                    case 'originalpage':
                    {
                        $number         += $value * 10;
                        break;
                    }

                    case 'originalitem':
                    {
                        $number         += $value;
                        break;
                    }

                    case 'regdomain':
                    {
                        if ($value instanceof Domain)
                        {
                            $domainObj  = $value;
                            $domain     = $domainObj->get('code');
                        }
                        else
                        {
                            $domain     = $value;
                            $domParms   = array('domain'    => $domain,
                                        'language'  => 'en');
                            $domainObj  = new Domain($domParms);
                        }
                        if ($domainObj->isExisting())
                        {       // valid domain
                            $cc         = substr($domain, 0, 2);
                            $pc         = substr($domain, 2);
                            $addrEnd    = ", $pc, $cc";
                        }
                        else
                            $warn       .= "<p>Marriage.inc: " . __LINE__ .
                                           " invalid domain id '$domain'</p>\n";
                        $mregdomain     = $domain;
                        break;
                    }

                    case 'regyear':
                    {
                        $mregyear       = $value;
                        $year           = $value;
                        if (is_string($year) && ctype_digit($year))
                            $year       = (int)$year;
                        else
                        if (!is_int($year) || $year < 1800 || $year > 2100)
                            $warn       .= "<p>Marriage::__construct: " .
                                            __LINE__ .
                    "registration year '$year' must be a numeric year</p>\n";
                        break;
                    }

                    case 'regnum':
                    {
                        $mregnum        = $value;
                        $number         = $value;
                        if (is_string($number) && ctype_digit($number))
                            $number     = (int)$number;
                        else
                        if (!is_int($number) || $number < 1)
                            $warn       .= "<p>Marriage::__construct: " .
                                           __LINE__ .
        "registration number '$number' must be a positive integer</p>\n";

                        break;
                    }

                }

                $sel                .= $and . $fieldLc . '=:' . $field;
                $and                = ' AND ';
            }

            // query the database
            $query                  = "SELECT * FROM Marriage $sel";
            $stmt                   = $connection->prepare($query);
            $queryText              = debugPrepQuery($query, $sqlParms);
            $this->lastSqlCmd       = $queryText;
            if ($stmt->execute($sqlParms))
            {       // successful query
                $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($debug)
                {
                    $warn   .= "<p>Marriage.inc: " . __LINE__ .
                               " query='$queryText'</p>\n";
                    $warn   .= "<p>Marriage.inc: returned " . count($result) .
                                   " records</p>\n";
                }

                if (count($result) > 0)
                {       // existing record
                    $dbrow              = $result[0];
                    $mregdomain         = $dbrow['regdomain'];
                    $mregyear           = $dbrow['regyear'];
                    $mregnum            = $dbrow['regnum'];
                    $needInsert         = false;
                }       // existing record
                else
                {       // create a new record
                    if ($year == 0 && $numvolume > 0)
                    {
                        $year   = self::$pre1873Years[$numvolume]['year'];
                        $msvol  = self::$pre1873Years[$numvolume]['reel'];
                    }
                    else
                        $msvol              = '';

                    // set defaults
                    $dbrow                  = self::$initRow;
                    $dbrow['regdomain'] = $domain;
                    $dbrow['regyear']       = $year;
                    $dbrow['regnum']        = $number;
                    $dbrow['date']      = $year;
                    $dbrow['regdate']       = $year;
                    $dbrow['msvol']     = $msvol;
                    $mregdomain             = $domain;
                    $mregyear               = $year;
                    $mregnum                = $number;

                    // fill in some fields from the preceding record,
                    // if it exists
                    $query  = "SELECT RegCounty,RegTownship,MsVol," .
                                  "OriginalVolume,OriginalPage,OriginalItem," .
                                  "Registrar,RegDate,Image " .
                                    "FROM Marriage  " .
                                    "WHERE RegDomain=:domain AND   " .
                                          "RegYear=:year AND  " .
                                          "RegNum<:number " .
                                    "ORDER BY RegNum DESC " .
                                    "LIMIT 1";

                    $sqlParms           = array('domain'    => $domain,
                                                'year'      => $year,
                                                'number'    => $number);
                    $stmt               = $connection->prepare($query);
                    $queryText          = debugPrepQuery($query, $sqlParms);
                    $this->lastSqlCmd   = $queryText;
                    if ($stmt->execute($sqlParms))
                    {       // successful query
                        if ($debug)
                        {
                            $warn       .= '<p>Marriage.inc: ' . __LINE__ .
                                           " query='$queryText'</p>\n";
                        }

                        $result     = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($result))
                        {       // a preceding record exists
                            $row                        = $result;
                            $row['regdomain']           = $domain;
                            $regCounty                  = $row['regcounty'];
                            $dbrow['regcounty']         = $regCounty;
                            $regTownship                = $row['regtownship'];
                            $dbrow['regtownship']       = $regTownship;
                            $dbrow['msvol']             = $row['msvol'];
                            $dbrow['registrar']         = $row['registrar'];
                            $dbrow['image']             = $row['image'];
                            $dbrow['originalvolume']    = $row['originalvolume'];
                            $dbrow['originalpage']      = $row['originalpage'];
                            $dbrow['originalitem']  = $row['originalitem'] + 1;
                            if (strlen($row['regdate']) > 0)
                                $dbrow['regdate']       = $row['regdate'];

                            // initialize places with default value
                            $dftPlace           = $this->getDefaultPlace($row);
                            $dbrow['place'] = $dftPlace;
                        }   // a preceding record exists
                        $needInsert             = true;
                    }       // successful query
                    else
                    {       // error issuing query
                        $this->msg  .= "Marriage::__construct: " . __LINE__ .
                                        " '$queryText' " .
                                        print_r($connection->errorInfo(),true) .
                            '. ';
                        $dbrow                  = self::$initRow;
                    }       // error issuing query

                    // apply any other fields
                    foreach($parms as $field => $value)
                    {
                        $fieldLc            = strtolower($field);
                        if (array_key_exists($fieldLc, self::$translate))
                            $fieldLc        = self::$translate[$fieldLc];
                        $dbrow[$fieldLc]    = $value;
                    }

                    $needInsert     = true;
                }       // create a new record
            }           // successful query
            else
            {
                $this->msg  .=  "Marriage::__construct: " . __LINE__ .
                            " query='$queryText' query failed: " .
                            print_r($stmt->errorInfo(),true) . ".\n";
                $dbrow                  = self::$initRow;
            }
        }           // search parameters

        // get participant records
        $sel            = "WHERE RegDomain=:domain AND " .
                                "RegYear=:year AND " .
                                "RegNum=:number";
        $sqlParms       = array("domain"    => $mregdomain,
                                "year"      => $mregyear,
                                "number"    => $mregnum);
        $pquery         = "SELECT * FROM MarriageIndi $sel ORDER BY Role";
        $pstmt              = $connection->prepare($pquery);
        $pqueryText         = debugPrepQuery($pquery, $sqlParms);
        $this->lastSqlCmd   .= '<br>' . $pqueryText;
        if ($pstmt->execute($sqlParms))
        {       // query successful
            $presults           = $pstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($presults as $prow)
            {       // loop through rows
                $participant    = new MarriageParticipant($prow);
                $role           = $prow['role'];
                $this->participants[$role]  = $participant;
            }       // loop through rows
        }       // query successful
        else
        {       // error issuing query
            $this->msg     .= "Marriage::__construct: " . __LINE__ .
                                " query='$pqueryText', " .
                                print_r($pstmt->errorInfo(),true) . ".\n";
            $dbrow                  = self::$initRow;
        }       // error issuing query

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Marriage');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Marriage Record constructed:');
        $msg                .= $this->msg;
    }       // function Marriage::__construct

    /********************************************************************
     *  function Marriage::getDefaultPlace                              *
     *                                                                  *
     *  Get the default place name from the preceding record in the     *
     *  database                                                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $name       field name                                      *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing default place name constructed from       *
     *      the registration town/township information in the record    *
     ********************************************************************/
    private function getDefaultPlace($row)
    {
        $domain         = $row['regdomain'];
        $cc         = substr($domain, 0, 2);
        $pc         = substr($domain, 2);
        $addrEnd        = ", $pc, $cc";

        $regCounty      = $row['regcounty'];
        $regTownship        = $row['regtownship'];
        $county         = new County($domain,
                                         $regCounty);
        $countyName     = $county->getName();
        $townshipLen        = strlen($regTownship);
        if (substr($regTownship, -5) == ' City')
            $dftPlace   = substr($regTownship, 0, $townshipLen - 5) .
                                 $addrEnd;
        else
        if (substr($regTownship, -4) == ' Twp')
            $dftPlace   = substr($regTownship, 0, $townshipLen - 4) .  ', ' .
                                 $countyName .  $addrEnd;
        else
        if (substr($regTownship, -5) == ' Twp.' ||
            substr($regTownship, -5) == ' Town')
            $dftPlace   = substr($regTownship, 0, $townshipLen - 5) . ', ' .
                                 $countyName . $addrEnd;
        else
        if (substr($regTownship, -8) == ' Village')
            $dftPlace   = substr($regTownship, 0, $townshipLen - 8) . ', ' .
                                 $countyName . $addrEnd;
        else
            $dftPlace   = $regTownship . ', ' .
                                 $countyName . $addrEnd;
        return $dftPlace;
    }       // Death::getDefaultPlace

    /********************************************************************
     *  function Marriage::toXml                                        *
     *                                                                  *
     *  Emit the contents of the object as XML.                         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $top        tag name as a string for the tag enclosing the  *
     *                  output.                                         *
     *                  If null, do not enclose the individual values   *
     *                  inside a root node.                             *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/
    function toXml($top,
                   $print   = true,
                   $options = 0)
    {
        global  $debug;

        // ensure that there is a label to use for the top element
        if (!is_string($top) || strlen($top) == 0)
            $top    = $this->table;

        if (is_null($top))
            $retval = '';
        else
            $retval = "    <$top>\n";
        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 2) == '')
                    $key    = substr($key, 2);
            $retval .= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
        }

        // display information about participants in the marriage
        foreach ($this->participants as $role => $participant)
        {       // loop through participants
            if ($role == 'B')
                $retval .= $participant->toXml('bride', false);
            else
            if ($role == 'G')
                $retval .= $participant->toXml('groom', false);
            else
            if ($role == 'M')
                $retval .= $participant->toXml('minister', false);
            else
                $retval .= $participant->toXml($role, false);
        }       // loop through participants

        if ($top !== null)
            $retval .= "    </$top>\n";
    }       // Marriage::toXml

    /********************************************************************
     *  function Marriage::toJson                                       *
     *                                                                  *
     *  Emit the contents of the object as JSON.                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      JSON representation of the object                           *
     ********************************************************************/
    function toJson($print      = true,
                    $options    = 0)
    {
        global  $debug;


        $retval     = '';
        $comma      = "{\n";
        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 2) == '')
                $key    = substr($key, 2);
            if (is_numeric($value))
            {
                if ((strlen($value) == 1 || substr($value, 0, 1) != '0'))
                    $retval     .= $comma . "\t\"$key\":\t$value";
                else
                {       // handle values with leading 0
                    $retval     .= $comma . "\t\"$key\":\t\"$value\"";
                }       // handle values with leading 0
            }
            else
            {
                $retval     .= $comma . "\t\"$key\":\t" .
                               json_encode($value);
            }
            $comma      = ",\n";
        }

        // display information about participants in the marriage
        foreach ($this->participants as $role => $participant)
        {       // loop through participants
            if ($role == 'B')
                $retval     .= $comma . "\t\"bride\":\n";
            else
            if ($role == 'G')
                $retval     .= $comma . "\t\"groom\":\n";
            else
            if ($role == 'M')
                $retval     .= $comma . "\t\"minister\":\n";
            else
                $retval     .= $comma . "\t\"$role\":\n";

            $retval .= $participant->toJson(false);
        }       // loop through participants

        if (strlen($retval) > 0)
            $retval     .= "\n}\n";
        if ($print)
            print $retval;
        return $retval;

    }       // Marriage::toJson

    /********************************************************************
     *  function Marriage::set                                          *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change.  Case is ignored.      *
     *      $value      value to set the field to                       *
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
        global  $debug;
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {                       // act on specific field
            case 'changedby':
            case 'changedate':
            {                   // cannot be changed
                break;
            }                   // cannot be changed

            case 'regdomain':
            case 'regyear':
            case 'regnum':
            {                   // changing primary key
                return parent::set($fieldLc, $value);
                break;
            }                   // changing primary key

            case 'date':
            {
                if (strlen($value) == 0)
                {
                    $y      = $this->row['regyear'];
                    $m      = 6;
                    $d      = 1;
                }
                else
                {
                    $date   = new LegacyDate(" $value");
                    $y      = $date->getYear();
                    $m      = $date->getMonth();
                    if ($m < 10)
                        $m  = '0' . $m;
                    $d  = $date->getDay();
                    if ($d < 10)
                        $d  = '0' . $d;
                }
                parent::set('calcdate',"$y-$m-$d");
                return parent::set($fieldLc, $value);
            }

            case 'regcounty':
            case 'regtownship':
            case 'msvol':
            case 'place':
            case 'licensetype':
            case 'remarks':
            case 'regdate':
            case 'registrar':
            case 'image':
            case 'originalvolume':
            case 'originalpage':
            case 'originalitem':
            {
                return parent::set($fieldLc, $value);
            }

            default:
            {                   // other field names
                return parent::set($fieldLc, $value);
            }                   // other fields
        }                       // act on specific field
    }       // function Marriage::set

    /********************************************************************
     *  function Marriage::getBride                                     *
     *                                                                  *
     *  Get the instance of MarriageParticipant for the bride.          *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of MarriageParticipant                             *
     ********************************************************************/
    function getBride()
    {
        if (array_key_exists('B', $this->participants))
            return $this->participants['B'];
        else
        {       // create new bride record
            $domain = $this->row['regdomain'];
            $year   = $this->row['regyear'];
            $number = $this->row['regnum'];
            $bride  = new MarriageParticipant($domain,
                                              $year,
                                              $number,
                                              'B');
            $this->participants['B']    = $bride;
            return  $bride;
        }       // create new bride record
    }       // Marriage::getBride

    /********************************************************************
     *  function Marriage::getGroom                                     *
     *                                                                  *
     *  Get the instance of MarriageParticipant for the groom.          *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of MarriageParticipant                             *
     ********************************************************************/
    function getGroom()
    {
        if (array_key_exists('G', $this->participants))
            return $this->participants['G'];
        else
        {       // create new groom record
            $domain = $this->row['regdomain'];
            $year   = $this->row['regyear'];
            $number = $this->row['regnum'];
            $groom  = new MarriageParticipant($domain,
                                          $year,
                                          $number,
                                          'G');
            $this->participants['G']    = $groom;
            return  $groom;
        }       // create new groom record
    }       // Marriage::getGroom

    /********************************************************************
     *  function Marriage::getMinister                                  *
     *                                                                  *
     *  Get the instance of MarriageParticipant for the minister.       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $create             if true create record if necessary      *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of MarriageParticipant or null                     *
     ********************************************************************/
    function getMinister($create = false)
    {
        if (array_key_exists('M', $this->participants))
            return $this->participants['M'];
        else
        if ($create)
        {       // create new minister record
            $domain = $this->row['regdomain'];
            $year   = $this->row['regyear'];
            $number = $this->row['regnum'];
            $minister   = new MarriageParticipant($domain,
                                          $year,
                                          $number,
                                          'M');
            $this->participants['M']    = $minister;
            return  $minister;
        }       // create new minister record
        else
            return null;
    }       // Marriage::getMinister

    /********************************************************************
     *  function Marriage::save                                         *
     *                                                                  *
     *  Save changes made to the Marriage record into the database.     *
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
     *  Throws:                                                         *
     *      Exception if user is not authorized to update the database  *
     ********************************************************************/
    function save($xml)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $userid;
        global  $connection;

        $retval                 = 0;

        // determine top level tag name for XML output
        $indent             	= '';
        if (is_string($xml))
        {                   // string tag name
            $matches        	= array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent     	= $matches[1];
                $xml        	= $matches[2];
            }
            $tagname        	= $xml;
            $xml            	= true;
        }                   // string tag name
        else
        if ($xml)
        {                   // true
            $indent         	= "    ";
            $tagname        	= 'cmd';
        }                   // true

        if ($this->needInsert)
        {                   // create new record
            $fldnames       	= '';
            $comma          	= '';
            $values         	= '';
            $sqlParms       	= array();
            foreach($this->row as $fld => $value)
            {
                switch($fld)
                {
                    case 'changedby':
                    {
                        $fldnames       .= $comma . "`changedby`";
                        $values         .= $comma . ':userid';
                        $sqlParms['userid'] = $userid;
                        $comma          = ", ";
                        break;
                    }       // Userid

                    case 'changedate':
                    {
                        $fldnames       .= $comma . "`changedate`";
                        $values         .= $comma . 'CURDATE()';
                        $comma          = ", ";
                        break;
                    }       // ChangeDate

                    case 'idir':
                    case 'originalitem':
                    {       // integer fields
                        $fldnames       .= $comma . "`$fld`";
                        $values         .= $comma . ':' . $fld;
                        if (is_int($value) || ctype_digit($value))
                            $sqlParms[$fld]= $value;
                        else
                            $sqlParms[$fld]= null;
                        $comma          = ', ';
                        break;
                    }       // integer fields

                    case 'calcdate':
                    {
                        if ($value != '0000-00-00')
                        {
                            $fldnames           .= $comma . "`$fld`";
                            $values             .= $comma . ":$fld";
                            $sqlParms[$fld]     = $value;
                            $comma              = ", ";
                        }
                        break;
                    }

                    default:
                    {
                        $fldnames       .= $comma . "`$fld`";
                        $values         .= $comma . ":$fld";
                        $sqlParms[$fld]     = $value;
                        $comma          = ", ";
                        break;
                    }       // most fields
                }           // act on specific fields
            }               // loop through all fields in record

            // construct the SQL INSERT command
            $insert     = "INSERT INTO Marriage ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt               = $connection->prepare($insert);
            $insertText         = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd   = $insertText;
            if ($stmt->execute($sqlParms))
            {               // successful insert
                $retval         = $stmt->rowCount();
                if ($xml)
                    print "$indent<$tagname result='$retval'>" . $insertText .
                            "</$tagname>\n";

                if ($debug)
                {
                    $warn   .= "<p>$insertText</p>\n";
                    $warn   .= "<p>count=$retval</p>\n";
                }

                // log the update
                logSqlUpdate($insert,
                             $sqlParms,
                             $retval,
                             '',
                             json_encode($this->row));

                // also save associated participant records
                foreach ($this->participants as $role => $participant)
                {           // loop through participants
                    if ($xml)
                    {       // XML output
                        if ($role == 'B')
                            $participant->save('bride');
                        else
                        if ($role == 'G')
                            $participant->save('groom');
                        else
                        if ($role == 'M')
                            $participant->save('minister');
                        else
                            $participant->save($role);
                    }       // XML output
                    else
                        $participant->save(false);
                    $this->lastSqlCmd       .= '<br>' .
                                        $participant->getLastSqlCmd();
                }           // loop through participants

                // clean up internal state
                $this->changed      = array();  // reset
                $this->needInsert   = false;
            }               // successful insert
            else
            {               // error inserting record
                $msg    .= "Marriage::save: " . __LINE__ .
                            " '$insertText', " .
                            print_r($stmt->errorInfo(),true) . ".\n";
                return $retval; // do not update participant records
            }               // error inserting record
        }                   // create new record
        else
        {                   // update existing record
            $update     = "UPDATE Marriage SET ";
            $set        = '';
            $comma      = '';
            $sqlParms       = array();
            foreach($this->changed as $fld => $value)
            {
                switch($fld)
                {           // act on specific fields
                    case 'changedby':
                    case 'changedate':
                    {       // ChangedBy, ChangeDate
                        break;
                    }       // ChangedBy, ChangeDate

                    case 'idir':
                    case 'originalitem':
                    {       // integer fields
                        $set                .= $comma . "`$fld`=:$fld";
                        if (is_int($value) || ctype_digit($value))
                            $sqlParms[$fld] = $value;
                        else
                            $sqlParms[$fld] = null;
                        $comma              = ', ';
                        break;
                    }       // integer fields

                    default:
                    {       // most fields
                        $set                .= $comma . "`$fld`=:$fld";
                        $sqlParms[$fld]     = $value;
                        $comma              = ', ';
                        break;
                    }       // most fields
                }           // act on specific fields
            }               // loop through all changed fields in record

            // permit change key of MarriageParticipant records
            $newregdomain       = null;
            $newregyear         = null;
            $newregnum          = null;
            if (array_key_exists('regdomain', $this->changed))
                $newregdomain   = $this->changed['regdomain'];
            if (array_key_exists('regyear', $this->changed))
                $newregyear     = $this->changed['regyear'];
            if (array_key_exists('regnum', $this->changed))
                $newregnum      = $this->changed['regnum'];

            // clean up internal state
            $this->changed                  = array();  // reset

            if (strlen($set) > 0)
            {               // changes made
                $set                        .= ',ChangedBy=:userid';
                $set                        .= ',ChangeDate=CURDATE()';
                $sqlParms['userid']         = $userid;

                // construct the where clause
                if ($this->former)
                {
                    $sqlParms['odomain']    = $this->former['regdomain'];
                    $sqlParms['oyear']      = $this->former['regyear'];
                    $sqlParms['onumber']    = $this->former['regnum'];
                }
                else
                {
                    $sqlParms['odomain']    = $this->row['regdomain'];
                    $sqlParms['oyear']      = $this->row['regyear'];
                    $sqlParms['onumber']    = $this->row['regnum'];
                }

                // assemble the UPDATE command
                $update     .= $set . ' WHERE RegDomain=:odomain ' .
                        ' AND RegYear=:oyear AND RegNum=:onumber';

                // perform the update
                $stmt               = $connection->prepare($update);
                $updateText         = debugPrepQuery($update, $sqlParms);
                $this->lastSqlCmd   = $updateText;
                if ($stmt->execute($sqlParms))
                {           // successful update
                    $retval         = $stmt->rowCount();
                    if ($xml)
                        print "$indent<$tagname count='$retval'>" .
                                $updateText . "</$tagname>\n";

                    if ($debug)
                        $warn   .= "<p>$updateText</p>\n";

                    // log the update
                    logSqlUpdate($update,
                                 $sqlParms,
                                 $retval,
                                 json_encode($this->former),
                                 json_encode($this->row));
                }           // successful update
                else
                {           // error updating record
                    $msg    .= "Marriage::save: " . __LINE__ .
                                " '$updateText', " .
                                print_r($stmt->errorInfo(),true) . ".\n";
                    return $retval;
                }           // error updating record
            }               // changes made
            else
                $this->lastSqlCmd   = '';

            // also save associated participant records
            foreach ($this->participants as $role => $participant)
            {               // loop through participant
                // permit change key of Marriage records
                if ($newregdomain)
                    $participant->set('regdomain', $newregdomain);
                if ($newregyear)
                    $participant->set('regyear', $newregyear);
                if ($newregnum)
                    $participant->set('regnum', $newregnum);

                if ($xml)
                {           // XML output
                    if ($role == 'B')
                        $participant->save('bride');
                    else
                    if ($role == 'G')
                        $participant->save('groom');
                    else
                    if ($role == 'M')
                        $participant->save('minister');
                    else
                        $participant->save($role);
                }           // XML output
                else
                    $participant->save(false);
                $this->lastSqlCmd   .= '<br>' . 
                                        $participant->getLastSqlCmd();
            }               // loop through participants
        }                   // update existing record

        return $retval;
    }       // Marriage::save

    /********************************************************************
     *  function Marriage::delete                                       *
     *                                                                  *
     *  Delete the database records associated with this instance.      *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in         *
     *                  XML format                                      *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1           if the method deleted the database record       *
     *      0           if the record did not need to be deleted        *
     ********************************************************************/
    function delete($xml = false)
    {
        global  $debug;
        global $warn;
        global  $connection;

        // handle XML output option
        $indent             = '';
        if (is_string($xml))
        {
            $matches        = array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent     = $matches[1];
                $xml        = $matches[2];
            }
            $tagname        = $xml;
            $xml            = true;
        }
        else
        if ($xml)
        {
            $indent         = '    ';
            $tagname        = 'cmd';
        }

        // get the key of the table rows referenced by this record
        $sqlParms           = array();
        if ($this->former)
        {
            $sqlParms['regdomain']  = $this->former['regdomain'];
            $sqlParms['regyear']    = $this->former['regyear'];
            $sqlParms['regnum']     = $this->former['regnum'];
        }
        else
        {
            $sqlParms['regdomain']  = $this->row['regdomain'];
            $sqlParms['regyear']    = $this->row['regyear'];
            $sqlParms['regnum']     = $this->row['regnum'];
        }

        // delete all associated records
        if (!$this->needInsert)
        {       // only delete if already in database
            $delete = 'DELETE FROM Marriage ' .
                                   'WHERE RegDomain=:regdomain AND ' .
                                     'RegYear=:regyear AND ' .
                                     'RegNum=:regnum';
            $stmt               = $connection->prepare($delete);
            $deleteText         = debugPrepQuery($delete, $sqlParms);
            $this->lastSqlCmd   = $deleteText;
            if ($stmt->execute($sqlParms))
            {       // successful delete
                $count          = $stmt->rowCount();
                if ($debug)
                    $warn       .= "<p>$deleteText</p>\n";

                if ($xml)
                    print "<$tagname count='$count'>$deleteText</$tagname>\n";

                // log the update
                logSqlUpdate($delete,
                             $sqlParms,
                             $count,        // compound key
                             json_encode($this->row),
                             '');

                // also delete all participants
                $delete     = 'DELETE FROM MarriageIndi ' .
                                      'WHERE RegDomain=:regdomain AND ' .
                                        'RegYear=:regyear AND ' .
                                        'RegNum=:regnum';
                $stmt               = $connection->prepare($delete);
                $deleteText         = debugPrepQuery($delete, $sqlParms);
                $this->lastSqlCmd   = '<br>' . $deleteText;
                if ($stmt->execute($sqlParms))
                {       // successful delete
                    $count          = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>$deleteText</$tagname>\n";
                    if ($debug)
                        $warn   .= "<p>$deleteText</p>\n";

                    // log the update
                    logSqlUpdate($delete,
                                 $sqlParms,
                                 $count,        // compound key
                                 '',            // multiple records
                                 '');

                    // update the memory copy of the record to reflect that it
                    // no longer represents a database record
                    $this->needInsert   = true;
                    return $count;
                }       // successful delete
                else
                {       // error updating record
                    error_log("Marriage.inc: " . __LINE__ . " $deleteText\n");
                    error_log(print_r($stmt->errorInfo(),true) . "\n");
                    return false;
                }       // error updating record
            }       // successful delete
            else
            {       // error updating record
                error_log("Marriage.inc: " . __LINE__ . " $deleteText\n");
                error_log(print_r($stmt->errorInfo(),true) . "\n");
                return false;
            }       // error updating record
        }       // only delete if already in database
        return 0;   // no records deleted
    }       // Marriage::delete

}       // class Marriage
