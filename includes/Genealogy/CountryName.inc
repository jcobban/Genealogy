<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountryName.inc                                                     *
 *                                                                      *
 *  Definition of a class representing a name of a country.             *
 *  This class provides access to the information in a row              *
 *  of the table CountryNames.                                          *
 *                                                                      *
 *  CREATE TABLE CountryNames (                                         *
 *      `Code3166_1`    CHAR(2) NOT NULL, -- country code               *
 *      `Code639_1`     CHAR(2) NOT NULL, -- language code              *
 *      `Name`          VARCHAR(255) NOT NULL DEFAULT '',               *
 *      `Article`       VARCHAR(32) NOT NULL DEFAULT '',                *
 *      `Possessive`    VARCHAR(32) NOT NULL DEFAULT '',                *
 *      PRIMARY KEY (`Code3166_1`,`Code639_1`) )                        *
 *      ENGINE=InnoDb DEFAULT CHARSET=utf8;                             *
 *                                                                      *
 *      `Code3166_1`    The ISO 3166-1 two character country code       *
 *                      by convention in upper case                     *
 *      `Code639_1`     The ISO 639-1 two character language code       *
 *                      by convention in lower case                     *
 *      `Name`          The name of the specified country in the        *
 *                      specified language                              *
 *      `Article`       The definite article with which to prefix the   *
 *                      name of the country.  For example:              *
 *                          'France' takes 'la ' in language 'fr'       *
 *                          'Canada' takes 'le ' in language 'fr'       *
 *                          'États Unies de l'Amerique' takes 'les '    *
 *                              in language 'fr'                        *
 *                          Countries that start with a vowel take      *
 *                              "l'" in language 'fr'.  This            *
 *                              requires that the value of this field   *
 *                              include the space separator if needed   *
 *      `Possessive`    The possessive with which to prefix the name    *
 *                      of the country.  For example:                   *
 *                          'France' takes 'de la ' in language 'fr'    *
 *                          'Canada' takes 'du ' in language 'fr'       *
 *                          'États Unies de l'Amerique' takes 'des '    *
 *                              in language 'fr'                        *
 *                          Countries that start with a vowel take      *
 *                              "de l'" in language 'fr'.  This         *
 *                              requires that the value of this field   *
 *                              include the space separator if needed   *
 *                                                                      *
 *  History:                                                            *
 *      2017/10/27      created                                         *
 *      2017/12/18      add support for temporary fields                *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/02/19      do not throw exceptions in constructor          *
 *                      do not invoke other constructors from           *
 *                      the constructor                                 *
 *                      permit passing instances of Country and         *
 *                      Language to the constructor                     *
 *      2019/04/18      support method getLastSqlCmd                    *
 *      2019/05/18      add $info                                       *
 *      2019/12/05      used country code to get language               *
 *                      add methods getLanguage and getCountry          *
 *                      fix errors in constructor                       *
 *                      fix get('article') and get('possessive')        *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';

/************************************************************************
 *  class CountryName                                                   *
 *                                                                      *
 *  Definition of a class recording information about a name of a       *
 *  country.                                                            *
 ************************************************************************/

class CountryName extends Record
{
    /********************************************************************
     *  CountryName::$country                                           *
     *                                                                  *
     *  Associated instance of Country.                                 *
     ********************************************************************/
    private $country        = null;

    /********************************************************************
     *  CountryName::$language                                          *
     *                                                                  *
     *  Associated instance of Language.                                *
     ********************************************************************/
    private $language       = null;

    /********************************************************************
     *  CountryName::$initRow                                           *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'code3166_1'        => '',
                'code639_1'         => '',
                'name'              => '',
                'article'           => '',
                'possessive'        => '');

    /********************************************************************
     *  CountryName::$info                                              *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'CountryNames',
                'name'              => 'CountryNames',
                'prime'             => array('code3166_1', 'code639_1'),
                'srvmg'             => false,
                'fldcount'          => 5,
                'order'             => 'Name',
                'classname'         => 'CountryName');

    /********************************************************************
     *  CountryName::$defaultOrder                                      *
     *                                                                  *
     *  Default sort order for query response by class RecordSet        *
     ********************************************************************/
    static $defaultOrder    = "`Name`";

    /********************************************************************
     *  CountryName::$translate                                         *
     *                                                                  *
     *  Define synonyms for field names                                 *
     ********************************************************************/
    protected static $translate = array(
                'cc'            => 'code3166_1',
                'lang'          => 'code639_1',
                'the'           => 'article',
                'of'            => 'possessive');

    /********************************************************************
     *  function CountryName::__construct                               *
     *                                                                  *
     *  Construct an instance of CountryName based upon the             *
     *  parameters.                                                     *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array of parameters              *
     *                  to select an existing entry where:              *
     *                  'code3166_1' may be a string or an              *
     *                  instance of Country                             *
     *                  case 'code639_1' may be a string or an          *
     *                  instance of Language                            *
     *                  or a complete row from the table as an          *
     *                  associative array                               *
     *                                                                  *
     *  Returns object containing a description of a name of a country  *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        if ($debug)
        {
            $parmsText      = '[';
            $comma          = '';
            foreach($parms as $key => $value)
            {
                $parmsText  .= "$comma'$key'=";
                if (is_numeric($value))
                    $parmsText  .= $value;
                else
                if (is_string($value))
                    $parmsText  .= "'$value'";
                else
                if (is_object($value))
                    $parmsText  .= get_class($value);
                else
                    $parmsText  .= print_r($value, true);
                $comma          = ',';
            }
            $warn   .= "<p>CountryName::__construct__($parmsText])</p>\n";
        }
        if (is_null($connection))
        {
            throw new Exception('CountryName::constructor: " .
                            "No connection to database server');
        }
        $needInsert         = false;
        $this->table        = 'CountryNames';

        // validate parameters
        if (is_array($parms))
        {                       // valid parm type
            if (count($parms) < count(self::$initRow))
            {                   // search parameters
                $code                   = 'not supplied';
                $where                  = '';
                $and                    = 'WHERE ';
                $cc                     = 'CA';
                $lang                   = 'en';
                $sqlParms               = array();
                foreach($parms as $key => $value)
                {               // loop through parameters
                    $fieldLc            = strtolower($key);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc        = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {           // act on key fields
                        case 'code3166_1':
                        {       // country code
                            if ($value instanceof Country)
                            {
                                $this->country      = $value;
                                $cc                 = $value['cc'];
                            }
                            else
                                $cc                 = $value;
                            $where                  .= "$and$fieldLc=:$fieldLc";
                            $sqlParms[$fieldLc]     = $cc;
                            $and                    = ' AND ';
                            break;
                        }       // country code

                        case 'code639_1':
                        {       // language identifier
                            if ($value instanceof Language)
                            {
                                $this->language     = $value;
                                $lang               = $value['code'];
                            }
                            else
                                $lang               = $value;
                            $where                  .= "$and$fieldLc=:$fieldLc";
                            $sqlParms[$fieldLc]     = $lang;
                            $and                    = ' AND ';
                            break;
                        }       // language identifier

                    }           // act on key fields
                }               // loop through parameters

                // query the database
                $query              = "SELECT * FROM CountryNames $where";
                $stmt               = $connection->prepare($query);
                $queryText          = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
                if ($stmt->execute($sqlParms))
                {       // success
                    if ($debug)
                        $warn   .= "<p>CountryName::__construct: " . __LINE__ .
                               " query='$queryText'</p>\n";

                    $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) == 1)
                    {           // existing record
                        $dbrow                  = $result[0];
                        $needInsert             = false;
                    }           // existing record
                    else
                    if (count($result) == 0)
                    {           // create a new record
                        // set defaults
                        $dbrow                  = self::$initRow;
                        $dbrow['code3166_1']    = $cc;
                        $dbrow['code639_1']     = $lang;
                        if ($this->country)
                            $dbrow['name']      = $this->country->get('name');
                        if ($this->language)
                        {
                            $dbrow['article']   = $this->language->get('article');
                            $dbrow['possessive']= $this->language->get('possessive');
                        }
                        $needInsert             = true;
                    }           // create new record
                    else
                    {           // error performing query
                        $this->msg  .= "CountryName::__construct: '$queryText' " .
                            "returns " . count($result) . " records";
                        $dbrow                  = self::$initRow;
                    }           // error performing query
                }               // success
                else
                {               // error performing query
                    $this->msg      .= "CountryName::__construct: " .
                                        "'$queryText' " .
                                        print_r($stmt->errorInfo(),true);
                    $dbrow                      = self::$initRow;
                    $dbrow['code3166_1']        = $cc;
                    $dbrow['code639_1']         = $lang;
                }               // error performing query
            }                   // search parameters
            else
            {                   // build from existing record
                $dbrow              = $parms;
                $needInsert         = false;
            }                   // build from existing record
        }                       // array parameter
        else
        {                       // unsupported parameter type
            $this->msg              .= "CountryName::__construct: " .
                                        "parameter must be an array: is " .
                                        gettype($parms);
            $dbrow                  = self::$initRow;
        }                       // unsupported parameter type


        // invoke constructor of base class
        parent::__construct($dbrow,
                            'CountryNames');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('CountryName Record constructed:');
    }       // CountryName::__construct

    /********************************************************************
     *  function CountryName::getCountry                                *
     *                                                                  *
     *  Get the instance of Country associated with this CountryName    *
     *                                                                  *
     *  Returns:                                                        *
     *      an instance of class Country                                *
     ********************************************************************/
    function getCountry()
    {
        if (is_null($this->country))
        {
            $cc             = $this->row['code3166_1'];
            $this->country  = new Country(array('code' => $cc));
        }
        return $this->country;
    }       // CountryName::getCountry

    /********************************************************************
     *  function CountryName::getLanguage                               *
     *                                                                  *
     *  Get the instance of Language associated with this CountryName   *
     *                                                                  *
     *  Returns:                                                        *
     *      an instance of class Language                               *
     ********************************************************************/
    function getLanguage()
    {
        if (is_null($this->language))
        {
            $ll             = $this->row['code639_1'];
            $this->language = new Language(array('code' => $ll));
        }
        return $this->language;
    }       // CountryName::getLanguage

    /********************************************************************
     *  function CountryName::get                                       *
     *                                                                  *
     *  Get the value of a field in the object.                         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to get value of                   *
     *                                                                  *
     *  Returns:                                                        *
     *      the value of the field                                      *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function get($field)
    {
        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'name':
            {
                return $this->getName();
            }

            case 'article':
            case 'possessive':
            {
                if ($this->needInsert && $this->row[$fieldLc] == '')
                {
                    $language               = $this->getLanguage();
                    $this->row[$fieldLc]    = $language->get($fieldLc);
                }
                return $this->row[$fieldLc];
            }

            case 'code3166_1':
            case 'code639_1':
            default:
            {       // other fields
                return parent::get($fieldLc);
            }       // other fields
        }       // switch on field name
    }       // CountryName::get

    /********************************************************************
     *  function CountryName::getName                                   *
     *                                                                  *
     *  Convenience function for accessing the 'name' attribute.        *
     *                                                                  *
     *  Returns:                                                        *
     *      Name of the county as a string.                             *
     ********************************************************************/
    function getName($options   = null)
    {
        if ($this->row['name'] == '')
        {
            $country            = $this->getCountry();
            $this->row['name']  = $country->getName();
        }
        return $this->row['name'];
    }       // CountryName::getName

    /********************************************************************
     *  function CountryName::__toString                                *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return  $this->getName();
    }       // CountryName::__toString

}       // class CountryName
