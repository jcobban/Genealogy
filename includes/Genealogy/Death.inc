<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Death.inc                                                           *
 *                                                                      *
 *  Definition of a class representing a transcription of a death       *
 *  registration or certificate.                                        *
 *  This class provides access to the information in a row              *
 *  of the table Deaths.                                                *
 *                                                                      *
 *  CREATE TABLE `Deaths` (                                             *
 *      `RegDomain`       VARCHAR(4) NOT NULL DEFAULT 'CAON',           *
 *      `RegYear`         INT(11) NOT NULL DEFAULT '2009',              *
 *      `RegNum`          INT(11) NOT NULL DEFAULT '0',                 *
 *      `RegCounty`       CHAR(3) DEFAULT NULL,                         *
 *      `RegTownship`     VARCHAR(64) DEFAULT NULL,                     *
 *      `MsVol`           INT(4) DEFAULT '0',                           *
 *      `Surname`         VARCHAR(32) DEFAULT NULL,                     *
 *      `SurnameSoundex`  CHAR(4) DEFAULT NULL,                         *
 *      `GivenNames`      VARCHAR(48) DEFAULT NULL,                     *
 *      `Sex`             CHAR(1) DEFAULT NULL,                         *
 *      `Place`           VARCHAR(128) DEFAULT NULL,                    *
 *      `Date`            VARCHAR(32) DEFAULT NULL,                     *
 *      `CalcDate`        INT(11) DEFAULT NULL,                         *
 *      `Age`             VARCHAR(16) DEFAULT NULL,                     *
 *      `BirthDate`       VARCHAR(32) DEFAULT NULL,                     *
 *      `CalcBirth`       INT(11) DEFAULT NULL,                         *
 *      `Occupation`      VARCHAR(64) DEFAULT NULL,                     *
 *      `MarStat`         CHAR(1) DEFAULT NULL,                         *
 *      `BirthPlace`      VARCHAR(128) DEFAULT NULL,                    *
 *      `Residence`       VARCHAR(128) DEFAULT NULL,                    *
 *      `ResPlace`        VARCHAR(16) DEFAULT NULL,                     *
 *      `ResOnt`          VARCHAR(16) DEFAULT NULL,                     *
 *      `ResCan`          VARCHAR(16) DEFAULT NULL,                     *
 *      `Cause`           VARCHAR(255) DEFAULT NULL,                    *
 *      `Duration`        VARCHAR(64) DEFAULT NULL,                     *
 *      `Phys`            VARCHAR(48) DEFAULT NULL,                     *
 *      `PhysAddr`        VARCHAR(128) DEFAULT NULL,                    *
 *      `Informant`       VARCHAR(48) DEFAULT NULL,                     *
 *      `InfRel`          VARCHAR(16) DEFAULT NULL,                     *
 *      `InfOcc`          VARCHAR(32) DEFAULT NULL,                     *
 *      `InfRes`          VARCHAR(128) DEFAULT NULL,                    *
 *      `Religion`        VARCHAR(32) DEFAULT NULL,                     *
 *      `FatherName`      VARCHAR(48) DEFAULT NULL,                     *
 *      `FatherBPlce`     VARCHAR(128) DEFAULT NULL,                    *
 *      `MotherName`      VARCHAR(48) DEFAULT NULL,                     *
 *      `MotherBPlce`     VARCHAR(128) DEFAULT NULL,                    *
 *      `HusbandName`     VARCHAR(48) DEFAULT NULL,                     *
 *      `Remarks`         LONGTEXT,                                     *
 *      `BurPlace`        VARCHAR(128) DEFAULT NULL,                    *
 *      `BurDate`         VARCHAR(48) DEFAULT NULL,                     *
 *      `Undertkr`        VARCHAR(48) DEFAULT NULL,                     *
 *      `UndertkrAddr`    VARCHAR(128) DEFAULT NULL,                    *
 *      `RegDate`         VARCHAR(16) DEFAULT NULL,                     *
 *      `Registrar`       VARCHAR(128) DEFAULT NULL,                    *
 *      `RecordedBy`      VARCHAR(64) DEFAULT NULL,                     *
 *      `Image`           VARCHAR(256) NOT NULL DEFAULT '',             *
 *      `IDIR`            INT(10) UNSIGNED DEFAULT NULL,                *
 *      `ChangedBy`       VARCHAR(64) NOT NULL DEFAULT '',              *
 *      `ChangeDate`      DATE DEFAULT NULL,                            *
 *      `OriginalVolume`  VARCHAR(16) DEFAULT NULL,                     *
 *      `OriginalPage`    VARCHAR(11) DEFAULT NULL,                     *
 *      `OriginalItem`    INT(11) DEFAULT NULL,                         *
 *      PRIMARY KEY (`RegDomain`,                                       *
 *                   `RegYear`,                                         *
 *                   `RegNum`),                                         *
 *      KEY `Surname` (`Surname`),                                      *
 *      KEY `SurnameSoundex` (`SurnameSoundex`),                        *
 *      KEY `GivenNames` (`GivenNames`),                                *
 *      KEY `IDIR` (`IDIR`),                                            *
 *      KEY `ChangeDate` (`ChangeDate`) )                               *
 *                                                                      *
 *  History:                                                            *
 *      2014/01/14      created                                         *
 *      2014/10/10      add country code to default location            *
 *                      and support all domains                         *
 *      2014/12/06      send trace info to $warn                        *
 *      2015/01/23      ensure that CalcBirth is updated when           *
 *                      BirthDate is set even if the value of           *
 *                      BirthDate is invalid                            *
 *      2015/03/27      permit synonyms d_deathdate, and d_deathplace   *
 *                      in method setField                              *
 *      2015/05/01      default image URL to previous record            *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *                      move default place identification to private    *
 *                      method for clarity                              *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2017/08/17      add fields OriginalVolume, OriginalPage, and    *
 *                      OriginalItem                                    *
 *      2017/10/29      use prepared statements                         *
 *      2017/12/18      add support for temporary fields                *
 *      2018/05/28      MySQL started objecting to assigning an         *
 *                      empty string to an INT, so ensure INT fields    *
 *                      set to NULL for empty string                    *
 *      2018/11/24      log entry for insert did not include parms      *
 *      2019/01/06      move to namespace Genealogy                     *
 *                      replace DATE with yyyymmdd                      *
 *      2019/03/18      `OriginalPage` VARCHAR(11) DEFAULT NULL,        *
 *      2019/06/12      improve validation of values assigned to Dates  *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2021/03/25      update lastSqlCmd                               *
 *      2021/04/24      add column Residence                            *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class Death                                                         *
 *                                                                      *
 *  Definition of a class recording information about a single death    *
 *  registration or death certificate.                                  *
 *                                                                      *
 ************************************************************************/

class Death extends Record
{

    /********************************************************************
     *  Death::$initRow                                                 *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'regdomain'             => 'CAON',
                'regyear'               => 2014,
                'regnum'                => 0,
                'regcounty'             => '',
                'regtownship'           => '',
                'msvol'                 => 0,
                'surname'               => '',
                'surnamesoundex'        => '',
                'givennames'            => '',
                'sex'                   => '?',
                'place'                 => '',
                'date'                  => '',
                'calcdate'              => null,
                'age'                   => '',
                'birthdate'             => '',
                'calcbirth'             => null,
                'occupation'            => '',
                'marstat'               => '?',
                'birthplace'            => '',
                'residence'             => '',
                'resplace'              => '',
                'resont'                => '',
                'rescan'                => '',
                'cause'                 => '',
                'duration'              => '',
                'phys'                  => '',
                'physaddr'              => '',
                'informant'             => '',
                'infrel'                => '?',
                'infocc'                => '',
                'infres'                => '',
                'religion'              => '',
                'fathername'            => '',
                'fatherbplce'           => '',
                'mothername'            => '',
                'motherbplce'           => '',
                'husbandname'           => '',
                'remarks'               => '',
                'burplace'              => '',
                'burdate'               => '',
                'undertkr'              => '',
                'undertkraddr'          => '',
                'regdate'               => '',
                'registrar'             => '',
                'recordedby'            => '',
                'image'                 => '',
                'idir'                  => 0,
                'originalvolume'        => 0,
                'originalpage'          => null,
                'originalitem'          => 0,
                'changedby'             => '',
                'changedate'            => '');

    /********************************************************************
     *  Death::$translate                                               *
     *                                                                  *
     *  Define synonyms for field names                                 *
     ********************************************************************/
    protected static $translate = array(
                'domain'                => 'regdomain',
                'd_regdomain'           => 'regdomain',
                'd_regyear'             => 'regyear',
                'd_regnum'              => 'regnum',
                'd_regcounty'           => 'regcounty',
                'county'                => 'regcounty',
                'd_regtownship'         => 'regtownship',
                'township'              => 'regtownship',
                'd_msvol'               => 'msvol',
                'd_surname'             => 'surname',
                'd_surnamesoundex'      => 'surnamesoundex',
                'd_givennames'          => 'givennames',
                'd_sex'                 => 'sex',
                'd_place'               => 'place',
                'deathplace'            => 'place',
                'd_date'                => 'date',
                'd_calcdate'            => 'calcdate',
                'd_age'                 => 'age',
                'd_birthdate'           => 'birthdate',
                'd_calcbirth'           => 'calcbirth',
                'd_occupation'          => 'occupation',
                'd_marstat'             => 'marstat',
                'd_birthplace'          => 'birthplace',
                'd_resplace'            => 'resplace',
                'd_resont'              => 'resont',
                'd_rescan'              => 'rescan',
                'd_cause'               => 'cause',
                'd_duration'            => 'duration',
                'd_phys'                => 'phys',
                'physician'             => 'phys',
                'attendant'             => 'phys',
                'd_physaddr'            => 'physaddr',
                'd_informant'           => 'informant',
                'd_infrel'              => 'infrel',
                'd_infocc'              => 'infocc',
                'd_infres'              => 'infres',
                'd_religion'            => 'religion',
                'd_fathername'          => 'fathername',
                'd_fatherbplce'         => 'fatherbplce',
                'd_mothername'          => 'mothername',
                'd_motherbplce'         => 'motherbplce',
                'd_husbandname'         => 'husbandname',
                'd_remarks'             => 'remarks',
                'd_burplace'            => 'burplace',
                'd_burdate'             => 'burdate',
                'd_undertkr'            => 'undertkr',
                'd_undertkraddr'        => 'undertkraddr',
                'd_regdate'             => 'regdate',
                'd_registrar'           => 'registrar',
                'd_recordedby'          => 'recordedby',
                'd_image'               => 'image',
                'd_idir'                => 'idir',
                'd_originalvolume'      => 'originalvolume',
                'd_originalpage'        => 'originalpage',
                'd_originalitem'        => 'originalitem',
                'd_changedby'           => 'changedby',
                'd_changedate'          => 'changedate');

    /********************************************************************
     *  Death::$defaultOrder                                            *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'RegDomain, RegYear, RegNum';

    /********************************************************************
     *  Death::$domain                                                  *
     *                                                                  *
     *  Associated instance of class Domain                             *
     ********************************************************************/
    protected   $domain                 = null;

    /********************************************************************
     *  function Death::__construct                                     *
     *                                                                  *
     *  Construct an instance of Death based upon the                   *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $domain             4/5 char administrative domain          *
     *                          (2 char country code + state/prov code) *
     *                          or an associative array obtained from   *
     *                          an SQL query by a static method         *
     *      $year               registration year                       *
     *      $number             registration number within year         *
     *                                                                  *
     *  Returns object containing a description of a death registration *
     ********************************************************************/
    function __construct($parms,
                         $year      = null,
                         $number    = null)
    {
        global $debug;
        global $warn;
        global $msg;
        global $connection;

        $needInsert                 = false;
        $this->table                = 'Deaths';
        $cc                         = 'CA';     // ISO country code
        $pc                         = 'ON';     // state/province code
        $domain                     = 'CAON';

        // validate parameters
        if (is_array($parms))
        {
            foreach($parms as $field => $value)
            {
                $fieldLc            = strtolower($field);
                if (array_key_exists($fieldLc, self::$translate))
                    $fieldLc        = self::$translate[$fieldLc];
                switch($fieldLc)
                {       // act on specific parameters
                    case 'regdomain':
                        $domain     = $value;
                        break;

                    case 'regyear':
                        $year       = $value;
                        break;

                    case 'regnum':
                        $number     = $value;
                        break;

                }       // act on specific parameters
            }           // loop through parameters
        }               // parameters supplied in array
        else
        if (is_string($parms) || $parms instanceof Domain)
        {       // initialize new entry
            $domain                 = $parms;
            if ($year == 0 || $number == 0)
                $this->msg          .= 
        "Death::__construct: domain=$domain, year=$year, number=$number, ";
            $parms                  = array('regdomain'   => $domain,
                                            'regyear'     => $year,
                                            'regnum'      => $number);
        }

        // validate parameters
        if ($domain instanceof Domain)
        {
            $domainObj              = $domain;
            $domain                 = $domainObj->get('domain');
        }
        else
        if (is_string($domain))
        {       // initialize new entry
            $domainObj              = new Domain(array('domain'     => $domain,
                                                       'language'   => 'en'));
        }
        else
        {
            $this->msg              .= "Invalid parameter Domain=" . 
                                        gettype($domain) . ". ";
            $domain                 = null;
        }

        if ($domainObj)
        {
            $this->domain           = $domainObj;
            if (!$domainObj->isExisting())
                $this->msg          .=
        "Domain '$domain' must be a supported two character country code " .
        "followed by a two or three character state or province code. ";
        }
            
        if (is_string($year) && ctype_digit($year))
            $year   = (int)$year;
        else
        if (!is_int($year) || $year < 1800 || $year > 2100)
            $this->msg  .= "Registration year '$year' must be a numeric year. ";
            
        if (is_string($number) && ctype_digit($number))
            $number = (int)$number;
        else
        if (!is_int($number) || $number < 1)
            $this->msg  .= "Registration number '$number' must be a positive integer. ";

        // search for an existing record matching the key
        if (is_array($parms) && count($parms) >= count(self::$initRow))
        {           // build from database record
            $dbrow              = $parms;
        }           // build from database record
        else
        if (strlen($this->msg) == 0)
        {                   // valid parameters
            // query the database
            $query              = 'SELECT * FROM Deaths ' .
                                    'WHERE RegDomain=:domain AND ' .
                                        'RegYear=:year AND ' .
                                        'RegNum=:number';
            $sqlParms           = array('domain'    => $domain,
                                        'year'      => $year,
                                        'number'    => $number);
            $queryText          = debugPrepQuery($query,
                                                 $sqlParms);
            $stmt               = $connection->prepare($query);
            if ($stmt->execute($sqlParms))
            {           // successful query
                if ($debug)
                    $warn       .= "<p>Death.inc: " . __LINE__ .
                                        " '$queryText'</p>\n";
    
                $result                     = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0)
                {       // existing record
                    $dbrow                  = $result[0];
                    $needInsert             = false;
                    $this->lastSqlCmd       = $queryText;
                }       // existing record
                else
                {       // create a new record
                    // set defaults
                    $dbrow                  = self::$initRow;
                    $dbrow['regdomain']     = $domain;
                    $dbrow['regyear']       = $year;
                    $dbrow['regnum']        = $number;
                    $dbrow['date']          = $year;
                    $dbrow['burdate']       = $year;
                    $dbrow['regdate']       = $year;
                    $dbrow['calcdate']      = $year*1000+630;

                    // fill in some fields from the preceding record, 
                    // if it exists
                    $queryPrev  = 'SELECT RegCounty, RegTownship, MsVol,'.
                                  'Registrar, RegDate, Image,' .
                                  'OriginalVolume, OriginalPage, OriginalItem' .
                                ' FROM Deaths ' .
                                ' WHERE RegDomain=:domain AND  ' .
                                    'RegYear=:year AND ' .
                                    'RegNum<:number ' .
                                ' ORDER BY RegNum DESC' .
                                ' LIMIT 1';
                    $queryText      = debugPrepQuery($queryPrev,
                                                     $sqlParms);
                    $stmt           = $connection->prepare($queryPrev);
                    if ($stmt->execute($sqlParms))
                    {           // successful query for previous record
                        if ($debug)
                            $warn   .= "<p>Death.inc: " . __LINE__ .
                                        " '$queryText' </p>\n";

                        $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0)
                        {       // a preceding record exists
                            $row                    = $result[0];
                            $row['regdomain']       = $domain;
                            $regCounty              = $row['regcounty'];
                            $dbrow['regcounty']     = $regCounty;
                            $regTownship            = $row['regtownship'];
                            $dbrow['regtownship']   = $regTownship;
                            $dbrow['msvol']         = $row['msvol'];
                            $dbrow['registrar']     = $row['registrar'];
                            $dbrow['image']         = $row['image'];
                            $dbrow['originalvolume']= $row['originalvolume'];
                            $dbrow['originalpage']  = $row['originalpage'];
                            $dbrow['originalitem']  = $row['originalitem'] + 1;
                            if (strlen($row['regdate']) > 0)
                                $dbrow['regdate']   = $row['regdate'];

                            // initialize places with default value
                            $dftPlace           = $this->getDefaultPlace($row);
                            $dbrow['place']         = $dftPlace;
                            $dbrow['birthplace']    = $dftPlace;
                            $dbrow['burplace']      = $dftPlace;
                            $dbrow['residence']     = $dftPlace;
                            $dbrow['physaddr']      = $dftPlace;
                            $dbrow['infres']        = $dftPlace;
                            $dbrow['undertkraddr']  = $dftPlace;
                        }       // a preceding record exists

                        if ($number > 1000000)
                        {
                            $origVol                = floor($number/1000000);
                            $origPage               = floor($number/100) % 1000;
                            $origItem               = $number % 100;
                            $dbrow['originalvolume']= $origVol;
                            $dbrow['originalpage']  = $origPage;
                            $dbrow['originalitem']  = $origItem;
                        }
                        $needInsert                 = true;
                    }           // successful query for previous record
                    else
                    {           // error issuing query
                        $this->msg                  .= "'$queryText' " .
                                        print_r($stmt->errorInfo(),true) . ". ";
                    }           // error issuing query
                }               // create new record
            }                   // successful query
            else
            {                   // error performing query
                $this->msg                          .= " '$queryText' " .
                                        print_r($stmt->errorInfo(),true) . ". ";
            }                   // error performing query
        }                       // build from database query

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Deaths');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Death Record constructed:');

        if (strlen($this->msg) > 0)
            $msg            .= $this->msg;
    }       // function Death::__construct

    /********************************************************************
     *  function Death::getDefaultPlace                                 *
     *                                                                  *
     *  Get the default place name from the preceding record in the     *
     *  database                                                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $row        preceding record from table                     *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing default place name constructed from       *
     *      the registration town/township information in the record    *
     ********************************************************************/
    private function getDefaultPlace($row)
    {
        $domain             = $row['regdomain'];
        $cc                 = substr($domain, 0, 2);
        $pc                 = substr($domain, 2);
        $addrEnd            = ", $pc, $cc";

        $regCounty          = $row['regcounty'];
        $regTownship        = $row['regtownship'];
        $county             = new County($domain,
                                         $regCounty);
        $countyName         = $county->getName();
        $townshipLen        = strlen($regTownship);
        if (substr($regTownship, -5) == ' City')
            $dftPlace       = substr($regTownship, 0, $townshipLen - 5) .
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
    }       // function Death::getDefaultPlace

    /********************************************************************
     *  function Death:set                                              *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change                         *
     *      $value      new value to set it to                          *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds trace if $field is not already defined as a field      *
     *      or pseudo-field and debugging is enabled                    *
     ********************************************************************/
    function set($field, $value)
    {
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];

        switch($fieldLc)
        {       // act on specific field
            case 'surnamesoundex':
            case 'changedby':
            case 'changedate':
            case 'calcdate':
            case 'calcbirth':
            {               // fields whose value is set from another field
                $warn           .= "<p>Death::set: " . __LINE__ .
                                    " cannot modify '$field'</p>\n";
            }               // fields whose value is set from another field

            case 'date':
            case 'deathdate':
            {
                $date           = new LegacyDate(" $value");
                $y              = $date->getYear();
                // fixup missing or abbreviated birth years
                if ($y == 0)
                    $y          = $this->row['regyear'];
                else
                if ($y < 1000)
                {
                    $regYear    = $this->row['regyear'];
                    $century    = floor($regYear / 100);
                    if ($y > $regYear % 100)
                        $y      = $y + (($century - 1) * 100);
                    else
                        $y      = $y + ($century * 100);
                }
                if ($y > 9999)
                    $y      = $y % 10000;

                $m              = $date->getMonth();
                if ($m == 0)
                    $m          = 6;
                if ($m < 10)
                    $m          = '0' . $m;
                $d              = $date->getDay();
                if ($d > 30 && ($m == 4 || $m == 6 || $m == 9 || $m == 11))
                    $d          = 30;
                if ($d > 29 && $m == 2)
                    $d          = 29;
                if ($d == 29 && $m == 2 && (($y % 4) != 0))
                    $d          = 28;
                if ($d == 0)
                    $d          = 15;
                if ($d < 10)
                    $d          = '0' . $d;

                parent::set('calcdate', $y*10000 + $m*100 + $d);
                return parent::set('date', $value);
            }

            case 'birthdate':
            {
                $date   = new LegacyDate(" $value");
                $y  = $date->getYear();
                // fixup missing or abbreviated birth years
                if ($y == 0)
                    $y  = $this->row['regyear'];
                else
                if ($y < 1000)
                {
                    $regYear    = $this->row['regyear'];
                    $century    = floor($regYear / 100);
                    if ($y > $regYear % 100)
                        $y  = $y + (($century - 1) * 100);
                    else
                        $y  = $y + ($century * 100);
                }
                if ($y > 9999)
                    $y      = $y % 10000;

                $m  = $date->getMonth();
                if ($m < 10)
                    $m  = '0' . $m;
                $d  = $date->getDay();
                if ($d > 30 && ($m == 4 || $m == 6 || $m == 9 || $m == 11))
                    $d      = 30;
                if ($d > 29 && $m == 2)
                    $d      = 29;
                if ($d == 29 && $m == 2 && (($y % 4) != 0))
                    $d      = 28;
                if ($d < 10)
                    $d  = '0' . $d;
                parent::set('calcbirth', $y*10000 + $m*100 + $d);
                return parent::set($fieldLc, $value);
            }

            case 'regyear':
            case 'regnum':
            case 'msvol':
            case 'idir':
            case 'originalitem':
            {
                if (is_int($value) || ctype_digit($value))
                    return parent::set($fieldLc, $value);
                else
                    return $this->row[$fieldLc];
            }

            case 'place':
            case 'regdomain':
            case 'regcounty':
            case 'regtownship':
            case 'surname':
            case 'givennames':
            case 'sex':
            case 'age':
            case 'occupation':
            case 'marstat':
            case 'birthplace':
            case 'resplace':
            case 'resont':
            case 'rescan':
            case 'cause':
            case 'duration':
            case 'phys':
            case 'physaddr':
            case 'informant':
            case 'infrel':
            case 'infocc':
            case 'infres':
            case 'religion':
            case 'fathername':
            case 'fatherbplce':
            case 'mothername':
            case 'motherbplce':
            case 'husbandname':
            case 'remarks':
            case 'burplace':
            case 'burdate':
            case 'undertkr':
            case 'undertkraddr':
            case 'regdate':
            case 'registrar':
            case 'recordedby':
            case 'image':
            case 'originalvolume':
            case 'originalpage':
            {
                return parent::set($fieldLc, $value);
            }

            default:
            {       // other fields
                return parent::set($fieldLc, $value);
            }       // other fields
        }       // act on specific field
    }       // Death::set

    /********************************************************************
     *  function Death::save                                            *
     *                                                                  *
     *  Save changes made to the Death record into the database.        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format within <cmd> tags.                       *
     *                  if a string use it as tag name                  *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1               if the method updated the database record   *
     *      0               if the database did not need to be updated  *
     *                                                                  *
     *  Throws:                                                         *
     *      exception if user is not authorized to update the database  *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $userid;
        global  $connection;

        if (is_string($xml))
        {                   // string tag name
            $tagname                    = $xml;
            $xml                        = true;
        }                   // string tag name
        else
        if ($xml)
        {                   // true
            $tagname                    = 'cmd';
        }                   // true

        if ($this->needInsert)
        {                   // create new record
            $fldnames                   = '';
            $comma                      = '';
            $values                     = '';
            $sqlParms                   = array();
            foreach($this->row as $fld => $value)
            {               // loop through all fields in record
                switch($fld)
                {           // act on specific fields
                    case 'surnamesoundex':
                    {       // ask server to calculate
                        $fldnames               .= $comma . "`$fld`"; 
                        $surname                = $this->row['surname'];
                        $values                 .= ',LEFT(SOUNDEX(:surname),4)';
                        $sqlParms['surname']    = $surname;
                        $comma                  = ", ";
                        break;
                    }       // SurnameSoundex

                    case 'changedby':
                    {
                        $fldnames               .= $comma . "`$fld`"; 
                        $values                 .= $comma . ':changedby';
                        $sqlParms['changedby']  = $userid;
                        $comma                  = ", ";
                        break;
                    }       // ChangedBy

                    case 'changedate':
                    {
                        $fldnames               .= $comma . "`$fld`"; 
                        $values                 .= $comma . 'CURDATE()';
                        $comma                  = ", ";
                        break;
                    }       // ChangeDate

                    case 'idir':
                    case 'originalitem':
                    {       // integer fields
                        $fldnames               .= $comma . "`$fld`";
                        $fld            = substr($fld, 2);
                        $values                 .= $comma . ':' . $fld;
                        if (is_int($value) || ctype_digit($value))
                            $sqlParms[$fld]= $value;
                        else
                            $sqlParms[$fld]= null;
                        $comma                  = ', ';
                        break;
                    }       // integer fields

                    default:
                    {
                        $fldnames               .= $comma . "`$fld`";
                        $fld            = $fld;
                        $values                 .= $comma . ':' . $fld;
                        $sqlParms[$fld] = $value;
                        $comma                  = ", ";
                        break;
                    }       // most fields
                }           // act on specific fields
            }               // loop through all fields in record

            // construct the SQL INSERT command 
            $insert     = "INSERT INTO Deaths ($fldnames) VALUES($values)";
            $insertText = debugPrepQuery($insert, $sqlParms);

            // insert the new record into the database
            $stmt   = $connection->prepare($insert);
            if ($stmt->execute($sqlParms))
            {               // insert successful
                $count              = $stmt->rowCount();
                if ($xml)
                    print "<$tagname result='$count'>" . htmlentities($insert,ENT_XML1) . 
                            "</$tagname>\n";
                else
                if ($debug)
                    $warn           .= "<p>$insert</p>\n<p>count=$count</p>\n";

                $this->lastSqlCmd   = $insertText;
                $this->changed      = array();  // reset
                $this->needInsert   = false;

                // log the update
                $this->log($insert, $sqlParms);
                return $count;
            }               // insert successful
            else
            {               // error inserting record
                $msg    .= "Death::save: " . __LINE__ . " '$insertText', " .
                                print_r($stmt->errorInfo(),true) . ". ";
            }               // error inserting record
        }                   // create new record
        else
        {                   // update existing record
            $update                 = "UPDATE Deaths SET ";
            $set                    = '';
            $comma                  = '';
            $sqlParms               = array();
            foreach($this->changed as $fld => $value)
            {
                switch($fld)
                {           // act on specific fields
                    case 'surname':
                    {       // surname changed
                        $set            .= $comma . '`Surname`=:surname';
                        $sqlParms['surname']    = $value;
                        $set            .= ',`SurnameSoundex`='; 
                        $set            .= 'LEFT(SOUNDEX(:surname), 4)';
                        $comma          = ', ';
                        break;
                    }       // Surname

                    case 'changedby':
                    case 'changedate':
                    case 'surnamesoundex':
                    {       // calculated
                        break;
                    }       // ChangedBy, ChangeDate

                    case 'idir':
                    case 'msvol':
                    case 'originalitem':
                    {       // integer value fields
                        $set                    .= $comma . "`$fld`=:$fld";
                        if (is_int($value) || ctype_digit($value))
                            $sqlParms[$fld]= $value;
                        else
                            $sqlParms[$fld]= null;
                        $comma                  = ', ';
                        break;
                    }       // integer value fields

                    default:
                    {       // most fields
                        $set                    .= $comma . "`$fld`=:$fld";
                        $sqlParms[$fld] = $value;
                        $comma                  = ', ';
                        break;
                    }       // most fields
                }           // act on specific fields
            }               // loop through all changed fields in record

            $this->changed  = array();  // reset

            if (strlen($set) > 0)
            {       // changes made
                // record who changed the record and when
                $set            .= ',ChangedBy=:userid';
                $set            .= ',ChangeDate=CURDATE()';
                $sqlParms['userid'] = $userid;

                // construct the where clause
                if ($this->former)
                {
                    $sqlParms['domain'] = $this->former['regdomain'];
                    $sqlParms['year']   = $this->former['regyear'];
                    $sqlParms['number'] = $this->former['regnum'];
                }
                else
                {
                    $sqlParms['domain'] = $this->row['regdomain'];
                    $sqlParms['year']   = $this->row['regyear'];
                    $sqlParms['number'] = $this->row['regnum'];
                }
                $where      = 'RegDomain=:domain AND ' .
                              'RegYear=:year AND ' .
                              'RegNum=:number';

                // assemble the UPDATE command
                $update     .= $set . ' WHERE ' . $where;
                $updateText = debugPrepQuery($update, $sqlParms);

                // perform the update
                $stmt       = $connection->prepare($update);
                if ($stmt->execute($sqlParms))
                {       // update successful
                    $count      = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>" .
                            htmlentities($updateText,ENT_XML1) . "</$tagname>\n";

                    if ($debug)
                        $warn       .= "<p>Death.inc: " . __LINE__ . 
                                   " '$updateText'</p>\n";
                    $this->lastSqlCmd   = $updateText;

                    // log the update
                    $this->log($update, $sqlParms);
                    return $count;
                }       // update successful
                else
                {       // error updating record
                    $msg    .= "Death::save: ". __LINE__ . " '$updateText', " .
                                print_r($stmt->errorInfo(),true) . ". ";
                }       // error updating record
            }           // changes made
        }               // update existing record
    }       // function Death::save

    /********************************************************************
     *  function Death::delete                                          *
     *                                                                  *
     *  Delete the database record associated with this instance.       *
     *                                                                  *
     *  Input:                                                          *
     *      $xml        if true emit XML                                *
     *                  if a string emit XML using as tag name          *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1               if the method deleted the database record   *
     *      0               if the record did not need to be deleted    *
     ********************************************************************/
    function delete($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // handle XML output option
        if (is_string($xml))
            $xmltag = $xml;
        else
        if ($xml)
        {
            $xml    = true;
            $xmltag = 'cmd';
        }

        // delete the record if it is in the database
        if (!$this->needInsert)
        {       // existing record in database
            $sqlParms               = array();
            $sqlParms['domain']     = $this->row['regdomain'];
            $sqlParms['year']       = $this->row['regyear'];
            $sqlParms['number']     = $this->row['regnum'];
            $delete = "DELETE FROM Deaths WHERE" .
                            ' RegDomain=:domain AND ' .
                            ' RegYear=:year AND RegNum=:number';
            $deleteText             = debugPrepQuery($delete, $sqlParms);
            $stmt                   = $connection->prepare($delete);
            if ($stmt->execute($sqlParms))
            {       // delete successful
                $count              = $stmt->rowCount();
                if ($xml)
                    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
                $this->lastSqlCmd   = $deleteText;
                // log the update
                $this->log($delete, $sqlParms);

                // update the memory copy of the record to reflect that it
                // no longer represents a database record
                $this->needInsert   = true;

                return $count;  // number of records deleted
            }       // delete successful
            else
            {       // error updating record
                error_log("Death.inc: " . __LINE__ . " '$deleteText', " .
                          print_r($stmt->errorInfo(),true) . "\n");
                return false;
            }       // error updating record
        }           // existing record in database

        // record never written to database
        return 0;   // nothing to delete
    }       // function Death::delete

}       // class Death 
