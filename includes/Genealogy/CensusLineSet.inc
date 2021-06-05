<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
use \mb_strlen;
use \mb_substr;
/************************************************************************
 *  CensusLineSet.inc                                                   *
 *                                                                      *
 *  Definition of a class representing a set of lines from a census of  *
 *  Canada,  This class provides access to the information in a set     *
 *  of row of tables Censusyyyy.                                        *
 *                                                                      *
 *  History:                                                            *
 *      2017/10/17      created                                         *
 *      2018/01/12      respond with a full page of default records     *
 *                      if requested to return all of the lines in      *
 *                      a page                                          *
 *      2018/01/17      Correct handling of surname, simplify parameter *
 *                      handling.  Use defined pattern match for        *
 *                      specific surname instead of exact match.        *
 *                      Match against beginning of given names, not     *
 *                      anywhere in the given name field.               *
 *                      Minimize creation of instances of Census        *
 *      2018/03/10      fill in family number for pages where only      *
 *                      the first member of each family has it          *
 *                      do not rebuild $this->list every time           *
 *                      rewind is called                                *
 *      2018/03/12      handle subdistrict comma-list                   *
 *                      handle division as district:subdistrict         *
 *      2018/03/14      do not fill in family number if surname is      *
 *                      empty or starts with [                          *
 *                      use mb_strlen and mb_substr on given names      *
 *                      handle subdistrict array value                  *
 *      2018/05/20      support regular expression pattern match for    *
 *                      given names                                     *
 *                      match whole supplied string for given names     *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/11/29      add factory methods getDistrict and             *
 *                      getSubDistrict                                  *
 *                      getStatistics now returns all pages             *
 *      2020/04/16      support exact match on surname                  *
 *      2020/04/23      fill in new 1911 page from AutomatedGenealogy   *
 *      2020/12/02      protect against XSS                             *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusLine.inc';
require_once __NAMESPACE__ . '/Surname.inc';

/************************************************************************
 *  CensusLineSet                                                       *
 *                                                                      *
 *  Definition of a class recording information about several           *
 *  individuals from the population schedule of a census of Canada.     *
 ************************************************************************/

class CensusLineSet extends RecordSet
{
    /********************************************************************
     *  CensusLineSet->censusYear                                       *
     *  CensusLineSet->censusId                                         *
     *  CensusLineSet->census                                           *
     *                                                                  *
     *  The census identifier is not present in the actual records      *
     *  but is specified as a parameter to the constructor to           *
     *  identify the table to read from.                                *
     ********************************************************************/
    protected   $censusYear     = '1881';
    protected   $censusId       = 'CA1881';
    protected   $census         = null;         // instance of Census

    /********************************************************************
     *  CensusLineSet->district                                         *
     *  CensusLineSet->subdistrict                                      *
     *  CensusLineSet->division                                         *
     *  CensusLineSet->page                                             *
     *                                                                  *
     *  Parameters to create new default page.                          *
     ********************************************************************/
    protected   $district       = null;         // id of District
    protected   $distObj        = null;         // instance of District
    protected   $subdistrict    = null;         // id of subdistrict
    protected   $subdistObj     = null;         // instance of SubDistrict
    protected   $division       = null;         // id of division
    protected   $page           = null;         // page number
    protected   $autofill       = true;

    /********************************************************************
     *  CensusLineSet->notPage                                          *
     *                                                                  *
     *  $this->notPage is true if any parameter that is not permitted   *
     *  in the identification of a page is passed to the constructor.   *
     ********************************************************************/
    protected   $notPage        = false;

    /********************************************************************
     *  function CensusLineSet::__construct                             *
     *                                                                  *
     *  Construct an instance of CensusLineSet based upon the           *
     *  parameters.                                                     *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array of parameters              *
     *                  identifying the set of rows.                    *
     *      $fields     specific fields to return, default all          *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms,
                         $fields    = '*')
    {
        global  $debug;
        global  $warn;

        $parmstext      = "[";
        $comma          = '';
        $join           = '';
        foreach($parms as $fldname => $value)
        {                   // analyze parameters
            if ($debug)
                if (is_array($value))
                {
                    $parmstext              .= $comma . "'$fldname' => [";
                    $sub                    = '';
                    foreach($value as $index => $val)
                    {
                        $parmstext          .= $sub;
                        if (is_string($index))
                            $parmstext      .= "'$index' => ";
                        if (is_string($val))
                            $parmstext      .= "'" . htmlspecialchars($val) . "'";
                        else
                        if (is_numeric($val))
                            $parmstext      .= $val;
                        else
                            $parmstext      .= gettype($val);
                        $sub                = ',';
                    }
                    $parmstext              .= ']';
                }
                else
                    $parmstext  .= $comma . "'$fldname' => '$value'";
            $comma      = ',';
            $fieldLc    = CensusLine::getRealFieldName($fldname);
            switch($fieldLc)
            {               // act on specific field name
                case 'census':
                case 'censusid':
                {
                    if ($value instanceof Census)
                    {
                        $this->census       = $value;
                        $censusId           = $this->census->get('id');
                    }
                    else
                        $censusId           = $value;
                    $this->censusId         = $censusId;
                    if ($censusId == 'CAALL')
                    {       // search all Canadian censuses
                        $censusYear         = 'ALL';
                        $this->censusYear   = $censusYear;
                        $this->table        = 'CensusALL';
                    }       // search all Canadian censuses
                    else
                    {       // specific census
                        if (is_null($this->census))
                        {
                            $this->census   = new Census(array('id' => $censusId));
                        }
                        $censusYear         = $this->census->get('year');
                        $this->censusYear   = $censusYear;
                        $this->table        = $this->census->get('table');
                        if (!$this->census->isExisting())
                            $this->msg      .= "Invalid Census='" .
                                    htmlspecialchars($censusId) . "'";
                    }       // specific census
                    break;
                }

                case 'order':
                {
                    if ($fldname != 'order')
                    {
                        unset($parms[$fldname]);
                        $parms['order']     = $value;
                    }
                    break;
                }

                case 'link':
                case 'join':
                {
                    $join                   = $value;
                    break;
                }

                case 'autofill':
                {
                    if (is_bool($value))
                        $this->autofill     = $value;
                    else
                    if (is_int($value))
                        $this->autofill     = $value != 0;
                    break;
                }
            }               // act on specific field name
        }                   // analyze parameters
        if ($debug)
            $warn   .= "<p>CensusLineSet::__construct(\$parms=$parmstext)<p>\n";

        if ($censusId == 'CAALL')
        {
            parent::__construct("Census1851," . 
                                "Census1861," . 
                                "Census1871," . 
                                "Census1881," . 
                                "Census1891," . 
                                "Census1901," . 
                                "Census1906," . 
                                "Census1911," . 
                                "Census1916," . 
                                "Census1921",
                                $parms,
                                $fields);
        }
        else
            parent::__construct($this->table . ' ' . $join,
                                $parms,
                                $fields);
    }       // CensusLineSet::__construct

    /********************************************************************
     *  function CensusLineSet::getIdentifier                           *
     *                                                                  *
     *  Common construction of WHERE expression for fields that are     *
     *  part of the identification of a portion of the census.          *
     *                                                                  *
     *  Input:                                                          *
     *      $fieldLc    field name in record in lower case              *
     *      $value      a value or an array of values                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing an SQL expression                         *
     *      A zero length string is returned if the input value is      *
     *      empty.                                                      *
     *                                                                  *
     *  Side Effects:                                                   *
     *      $this->sqlParms     sets substitution values to PDO exec    *
     *      $this->notPage      set to true if not a single value       *
     *      Errors are appended to the global $warn                     *
     ********************************************************************/
    function getIdentifier($fieldLc, $value)
    {
        $where          = '';
        if (is_array($value) && count($value) == 1)
            $value      = reset($value);
        if (is_array($value) && count($value) > 0)
        {
            $this->notPage      = true;
            $or             = '(';
            foreach($value as $val)
            {
                $where          .= $or . "`$fieldLc`=?";
                $this->sqlParms[]   = $val;
                $or         = ' OR ';
            }
            $where          .= ')';
        }
        else
        if (is_numeric($value) ||
            (is_string($value) && strlen($value) > 0))
        {
            $where          .= "`$fieldLc`=?";
            $this->sqlParms[]       = $value;
        }
        else
            $this->notPage      = true;
        return $where;
    }       // CensusLineSet::getIdentifier

    /********************************************************************
     *  function CensusLineSet::getGivenNames                           *
     *                                                                  *
     *  Common construction of WHERE expression for fields that         *
     *  contain one or more given names                                 *
     *                                                                  *
     *  Input:                                                          *
     *      $fieldLc    field name in record in lower case              *
     *      $value      a value or an array of values                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     *      A zero length string is returned if the input array is      *
     *      empty, otherwise the string starts with the word WHERE      *
     *                                                                  *
     *  Side Effects:                                                   *
     *      $this->sqlParms     sets substitution values to PDO exec    *
     *      $this->notPage      set to true if not a single value       *
     ********************************************************************/
    function getGivenNames($fieldLc, $value)
    {
        if (strlen($value) == 0)
            return '';
        $where          = '';

        if (is_string($value))
            $value      = explode(' ', $value);
        if (is_array($value))
        {           // array of given names
            $or         = '(';
            foreach($value as $name)
            {       // loop through given names
                if (mb_strlen($name) > 0)
                {       // find name starting with prefix
                    if (strlen($name) > 2 &&
                        preg_match('/[\^.$*+?[\]]/', $name) == 1)
                    {       // regular expression
                        if (mb_substr($name, 0, 1) == '^')
                            $name   = "[[:<:]]" . mb_substr($name, 1);
                        $where          .= $or . 'GivenNames REGEXP ?'; 
                        $this->sqlParms[]   = $name;
                    }       // regular expression
                    else
                    {       // literal string
                        $where  .= $or . 'LOCATE(?,`GivenNames`)=1' .
                               ' OR LOCATE(?,`GivenNames`)>0';
                        $this->sqlParms[]   = $name;
                        $this->sqlParms[]   = ' ' . $name;
                    }       // literal string
                    $or         = ' OR ';
                }       // find name starting with prefix
            }       // loop through given names
            if ($or == ' OR ')
                $where  .= ')';
        }           // array of given names
        return $where;
    }           // CensusLineSet::getGivenNames

    /********************************************************************
     *  function CensusLineSet::getWhere                                *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *      $this->parms  associative array of field/value combinations *
     *                    censusId may compared to an array of values   *
     *                    offset is the starting offset in the result   *
     *                    limit is the max number of records to return  *
     *              Special Field Names:                                *
     *                    year          match the census year           *
     *                    countrycode   match a country                 *
     *                    state         match an included state         *
     *                    province      match an included province      *
     *      $this->sqlParms     sets substitution values to PDO exec    *
     *      $this->offset       sets starting offset if supplied        *
     *      $this->limit        sets maximum number of matches          *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     *      A zero length string is returned if the input array is      *
     *      empty, otherwise the string starts with the word WHERE      *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Errors are appended to the global $warn                     *
     ********************************************************************/
    function getWhere()
    {
        global  $debug;
        global  $warn;
        global  $msg;

        $range          = 5;
        $this->orderby  = 'Surname, GivenNames, BYear';
        $province       = '';
        $cenYear        = 1881;
        $byear          = null;
        $surname        = null;
        $soundex        = false;
        $where          = '';
        $and            = 'WHERE ';
        $this->notPage  = false;

        // obtain information required for comparisons 
        foreach($this->parms as $fldname => $value)
        {
            $fieldLc        = strtolower($fldname);
            $fieldLc        = CensusLine::getRealFieldName($fieldLc);
            switch($fieldLc)
            {       // act on specific field name
                case 'census':
                case 'censusid':
                {       // Census Identifier
                    $censusId       = $value;
                    $this->censusId = $censusId;
                    $province       = substr($censusId, 0, 2);
                    $cenYear        = intval(substr($censusId, -4));
                    break;
                }       // Census Identifier

                case 'province':
                {           // used only by menu
                    if (is_string($value))
                    {
                        switch(strtoupper($value))
                        {   // support pre-confederation names
                            case 'ON':
                            {
                                $value  = array('ON', 'CW');
                                break;
                            }
        
                            case 'QC':
                            {
                                $value  = array('QC', 'CE');
                                break;
                            }
        
                        }   // support pre-confederation names
                    }

                    if (is_array($value) && count($value) > 0)
                    {
                        $province       = current($value);
                        $where          .= $and;
                        $or             = '(';
                        foreach($value as $code)
                        {
                            $where      .= $or . "`$fieldLc`=?";
                            $this->sqlParms[]   = $code;
                            $or         = ' OR ';
                        }
                        $where          .= ')';
                        $and            = ' AND ';
                    }
                    else
                    if (is_string($value) && strlen($value) == 2)
                    {
                        $province       = $value;
                        $where          .= $and . "`$fieldLc`=?";
                        $this->sqlParms[]   = $value;
                        $and            = ' AND ';
                    }
                    break;
                }           // used only by menu

                case 'district':
                {
                    if (is_array($value) && count($value) == 1)
                        $value      = reset($value);
                    $this->district = $value;
                    $expression     = $this->getIdentifier($fieldLc,
                                                           $value);
                    if (strlen($expression) > 0)
                    {
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }
                    break;
                }       // "district"

                case 'subdistrict':
                {
                    $ovalue             = $value;
                    if (is_string($value))
                        $subdistricts   = explode(',',$value);
                    else
                    if (is_array($value))
                        $subdistricts   = $value;
                    else
                    {
                        $warn   .= "<p>CensusLineSet:getWhere: " . __LINE__ .
                               " unexpected value " . 
                               htmlspecialchars(print_r($value, true)) . 
                               " for subdistrict</p>\n";
                        break;
                    }

                    // subdistricts is an array
                    if (count($subdistricts) > 1)
                    {
                        $value              = array();
                        foreach($subdistricts as $id)
                        {
                            if (preg_match('/(\d+):(\d+)/', $id, $matches))
                            {
                                $value[]    = $matches[2];
                                if (is_null($this->district))
                                {
                                    $this->district = $matches[1];
                                    $expr       =
                                        $this->getIdentifier($fieldLc,
                                                             $matches[1]);
                                    if (strlen($expr) > 0)
                                    {
                                        $where      .= $and . $expression;
                                        $and        = ' AND ';
                                    }
                                }
                            }
                            else
                                $value[]    = $id;
                        }
                    }
                    else
                    {
                        $value              = $subdistricts[0];
                        if (preg_match('/^(\d+):(\w+)$/', $value, $matches))
                        {
                            $value          = $matches[2];
                            if (is_null($this->district))
                            {
                                $this->district = $matches[1];
                                $expression     =
                                    $this->getIdentifier($fieldLc,
                                                         $matches[1]);
                                if (strlen($expr) > 0)
                                {
                                    $where      .= $and . $expr;
                                    $and        = ' AND ';
                                }
                            }
                        }
                    }
                    $this->subdistrict      = $value;
                    $expression             =
                                        $this->getIdentifier($fieldLc,
                                                             $value);
                    if (strlen($expression) > 0)
                    {
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                    }
                    else
                        $warn   .= "<p>CensusLineSet:getWhere: $fieldLc expression is empty for value=" . print_r($ovalue, true) . "</p>\n";
                    break;
                }       // "subdistrict"

                case 'division':
                {
                    if (strlen($value) == 0)
                        break;
                    if (preg_match('/(\d+):(\d+)/', $value, $matches))
                    {
                        $this->district     = $matches[1];
                        $expression         = $this->getIdentifier('district',
                                                                   $matches[1]);
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                        $this->subdistrict  = $matches[2];
                        $expression         = $this->getIdentifier('subdistrict',
                                                                   $matches[2]);
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                        $this->division     = '';
                    }
                    else
                    {
                        $this->division     = $value;
                        $expression         = $this->getIdentifier($fieldLc,
                                                                   $value);
                        if (strlen($expression) > 0)
                        {
                            $where          .= $and . $expression;
                            $and            = ' AND ';
                        }
                    }
                    break;
                }       // "division"

                case 'page':
                {
                    $this->page             = intval($value);
                    $expression             = $this->getIdentifier($fieldLc,
                                                                   $value);
                    if (strlen($expression) > 0)
                    {
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                    }
                    break;
                }       // "page"

                case 'range':
                {       // Range of ages or birth years
                    $matches            = array();
                    if (ctype_digit($value))
                    {
                        $range      = intval($value);
                    }
                    else
                        $msg    .= "Range '" . htmlspecialchars($value) . 
                                    "' must be an integer. ";
                    break;
                }       // "Range"

                case 'family':
                {
                    $matches            = array();
                    if (preg_match('/\d+/', $value, $matches))
                    {
                        $this->orderby      = 'Page, Line';
                        $this->notPage      = true;
                        $where          .= $and . "`$fieldLc`=?";
                        $this->sqlParms[]   = $value;
                        $and            = ' AND ';
                    }
                    break;
                }

                case 'surname':
                {
                    $this->notPage          = true;
                    $this->orderby          = 'Surname, GivenNames, BYear';
                    if (is_string($value))
                    {
                        if (strlen($value) > 0)
                            $surname        = array($value);
                    }
                    else
                    if (is_array($value))
                        $surname            = $value;
                    break;
                }

                case 'surnamesoundex':
                {           // surname loose search
                    if (strlen($value) == 0)
                        break;
                    $this->notPage          = true;
                    $this->orderby          = 'Surname, GivenNames, BYear';
                    if (is_bool($value))
                        $soundex            = $value;
                    else
                    if (is_numeric($value))
                        $soundex            = $value != 0;
                    else
                    if (is_string($value) && strlen($value) > 0)
                    {
                        $value              = strtolower($value);
                        $soundex            = substr($value, 0, 1) != 'n';
                    }
                    break;
                }           // surname loose search

                case 'givennames':
                {           // given name loose search
                    $expression             = $this->getGivenNames('givennames',
                                                                   $value);
                    if (strlen($expression) > 0)
                    {
                        $this->notPage      = true;
                        $this->orderby      = 'Surname, GivenNames, BYear';
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                    }           // expression provided
                    break;
                }           // given name loose search

                case 'byear':
                {
                    $matches                = array();
                    if (preg_match('/\d+/', $value, $matches))
                    {
                        $byear              = $matches[0];
                        $this->notPage      = true;
                    $this->orderby          = 'Surname, GivenNames, BYear';
                    }
                    break;
                }       // "BYear"

                case 'count':
                case 'offset':
                case 'orderby':
                case 'coverage':
                case 'query':
                case 'submit':
                case 'debug':
                case 'autofill':
                {       // handled outside of this loop
                    break;
                }       // handled outside of this loop

                default:
                {
                    $this->notPage      = true;
                    if (is_array($value) && count($value) > 0)
                    {
                        $where          .= $and;
                        $or             = '(';
                        foreach($value as $val)
                        {
                            $where      .= $or . "`$fieldLc`=?";
                            $this->sqlParms[]   = $val;
                            $or         = ' OR ';
                        }
                        $where          .= ')';
                        $and            = ' AND ';
                    }
                    else
                    if (is_numeric($value) ||
                        (is_string($value) && strlen($value) > 0))
                    {
                        $where          .= $and . "`$fieldLc`=?";
                        $this->sqlParms[]   = $value;
                        $and            = ' AND ';
                    }
                    break;
                }
            }           // act on specific field name
        }               // loop through all parameters

        // checking surname depends upon whether SOUNDEX is specified
        if (isset($surname))
        {                   // surname search requested
            $where              .= $and;
            $or                 = '(';
            foreach($surname as $name)
            {               // loop through surnames
                if ($soundex)
                {                   // loose search
                    if (preg_match('/^[A-Z]\d{3}$/', $name))
                        $where          .= "$or`SurnameSoundex`=?";
                    else
                        $where      .= "$or LEFT(SOUNDEX(?),4)=`SurnameSoundex`";
                    $this->sqlParms[]   = $name;
                    $or                 = ' OR ';
                }                   // loose search
                else
                if (substr($name, 0, 1) == '^')
                {                   // match pattern
                    if (substr($name, -1) == '$')
                    {               // exact match
                        $l                  = strlen($name) - 2;
                        $where              .= "$or Surname=?";
                        $this->sqlParms[]   = substr($name,1, $l);
                    }               // exact match
                    else
                    {               // match front
                        $l                  = strlen($name) - 1;
                        $where              .= "$or LEFT(Surname,$l)=?";
                        $this->sqlParms[]   = substr($name,1);
                    }               // match front
                    $or                 = ' OR ';
                }                   // match pattern
                else
                {                   // more specific search
                    $surnameObj     = new Surname(array('surname'   => $name));
                    $pattern            = $surnameObj->get('pattern');
                    if (strlen($pattern) == 0)
                        $pattern        = $name;
                    $expression         = $this->getWherePattern('Surname',
                                                                 $pattern,
                                                                 $this->sqlParms);
                    if (strlen($expression) > 0)
                    {               // expression specified
                        $where          .= $or . $expression;
                        $or             = ' OR ';
                    }               // expression specified
                }                   // more specific search
            }                       // loop through surnames

            if ($or == ' OR ')
            {               // finish off
                $where                  .= ')';
                $and                    = ' AND ';
            }               // finish off
        }                   // surname search requested

        // checking birth year depends upon value of $range
        if (isset($byear))
        {                   // limit search by year of birth
            $where                  .= $and;
            if ($range > 0)
            {               // range of ages
                $where              .= "ABS(BYear - ?)<=?";
                $this->sqlParms[]   = $byear;
                $this->sqlParms[]   = $range;
            }               // range of ages
            else
            {               // specific value
                $where              .= "BYear=?\n";
                $this->sqlParms[]   = $byear;
            }               // specific value
            $and                    = ' AND ';
        }                   // limit search by year of birth

        if (!$this->notPage && 
                    isset($this->district) &&
                    isset($this->subdistrict) &&
                    isset($this->page))
        {
            $this->orderby      = 'Page, Line';
        }
        if ($debug)
        {
            $warn       .= "<p>CensusLineSet::getWhere: " . __LINE__ .
                " where='" . htmlspecialchars($where) . "'</p>\n" .
                "<p>sqlParms=" . print_r($this->sqlParms, true) . "</p>\n";  
        }

        return $where;
    }       // CensusLineSet::getWhere

    /********************************************************************
     *  function CensusLineSet::getDistrict                             *
     *                                                                  *
     *  Factory method to get an instance of District                   *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of District or null                                *
     ********************************************************************/
    public function getDistrict()
    {
        if (is_null($this->distObj))
        {
            if ($this->district)
            {           // response is all or part of a page
                $censusYear             = $this->census['year'];
                $count                  = $this->census->get('linesperpage');
                $distParms              = array('d_census'  => $this->census);
                if (is_array($this->district))
                    $distParms['d_id']  = reset($this->district);
                else
                    $distParms['d_id']  = $this->district;
                $this->distObj          = new District($distParms);
            }
        }
        return $this->distObj;      // may still be null
    }       // function CensusLineSet::getDistrict

    /********************************************************************
     *  function CensusLineSet::getSubDistrict                          *
     *                                                                  *
     *  Factory method to get an instance of SubDistrict                *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of SubDistrict or null                             *
     ********************************************************************/
    public function getSubDistrict()
    {
        if (is_null($this->subdistObj))
        {
            if ($this->district && $this->subdistrict)
            {           
                $sdistParms         = array('sd_census' => $this->census,
                                            'sd_distid' => $this->district,
                                            'sd_id'     => $this->subdistrict,
                                            'sd_div'    => $this->division);
                $this->subdistObj   = new SubDistrict($sdistParms);
            }
        }
        return $this->subdistObj;      // may still be null
    }       // function CensusLineSet::getSubDistrict

    /********************************************************************
     *  function CensusLineSet::rewind                                  *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Reposition to the first element of the "array".                 *
     *  Since this is called just before any iteration this is the      *
     *  latest point at which the contents of the set can be loaded.    *
     *                                                                  *
     *  Returns:                                                        *
     *      first instance of Record in the set                         *
     ********************************************************************/
    public function rewind()
    {
        if (is_null($this->list))
        {
            if ($this->district &&
                $this->subdistrict && 
                is_int($this->page))
            {           // response is all or part of a page
                $censusId           = $this->census['censusid'];
                $censusYear         = $this->census['year'];
                $count              = $this->census->get('linesperpage');
                $distObj            = $this->getDistrict();
                $prov               = $distObj->get('province');
                $distName           = $distObj->get('name');

                $subdistObj         = $this->getSubDistrict();
                $subDistName        = $subdistObj->get('name');
                $oldfamily          =
                                ($this->page - $subdistObj->get('page1')) *
                                ($this->census->get('linesperpage') / 5) + 1;
                $oldsurname         = '[unknown]';
                $oldreligion        = '[religion]';
                $oldorigin          = '[origin]';
                $oldnationality     = 'Canadian';

                $parms          = array('censusid'      => $this->census,
                                        'district'      => $this->district,
                                        'subdistrict'   => $this->subdistrict,
                                        'division'      => $this->division,
                                        'page'          => $this->page,
                                        'line'          => -1);
                $pline          = new CensusLine($parms);
                if ($pline->isExisting())
                {
                    $oldfamily      = $pline->get('family');
                    $oldsurname     = $pline->get('surname');
                    if ($pline->offsetExists('religion'))
                        $oldreligion    = $pline->get('religion');
                    if ($pline->offsetExists('origin'))
                        $oldorigin      = $pline->get('origin');
                    if ($pline->offsetExists('nationality'))
                        $oldnationality = $pline->get('nationality');
                }
                $page       = true;
            }           // response is all of part of a page
            else
                $page       = false;

            if (!array_key_exists('count', $this->information))
            {
                print_r($this->information);
                throw new Exception("information incomplete");
            }

            if ($this->information['count'] == 0 &&
                !$this->notPage &&
                $page)
            {                   // fill in default page
                // create new empty page
                for($line = 1; $line <= $count; $line++)
                {
                    $parms      = array('censusid'      => $this->census,
                                        'district'      => $this->district,
                                        'subdistrict'   => $this->subdistrict,
                                        'division'      => $this->division,
                                        'page'          => $this->page,
                                        'line'          => $line);
                    $cline      = new CensusLine($parms);
                    foreach($cline as $field => $value)
                    {           // loop through fields
                        switch(strtolower($field))
                        {       // act on specific fields
                            case 'family':
                            {
                                $cline->set('family',   $oldfamily);
                                break;
                            }

                            case 'surname':
                            {
                                $cline->set('surname',  $oldsurname);
                                break;
                            }

                            case 'religion':
                            {
                                $cline->set('religion', $oldreligion);
                                break;
                            }

                            case 'origin':
                            {
                                $cline->set('origin',   $oldorigin);
                                break;
                            }

                            case 'nationality':
                            {
                                $cline->set('nationality',  $oldnationality);
                                break;
                            }

                            case 'membet':
                            {
                                $cline->set('origin',   'Y');
                                break;
                            }

                        }       // act on specific fields
                    }           // loop through fields

                    // add extra explanatory fields
                    $cline->set('censusyear',   $censusYear);
                    $cline->set('province',     $prov);
                    $cline->set('d_name',       $distName);
                    $cline->set('sd_name',      $subDistName);
                    $this->list[$line]  = $cline;
                }

                // if possible fill in some columns from AutomatedGenealogy
                if ($this->autofill &&
                    $this->census['id'] == "CA1911") 
                {
                    $this->getAg11();
                }

                $this->information['count'] = $count;
            }                   // fill in default page
            else
            {
                parent::rewind();
                if (!$this->notPage && $page)
                {               // check for issues with whole page
                    foreach($this->list as $line)
                    {
                        if ($line->offsetExists('family'))
                            $family = trim($line->get('family'));
                        else
                            break;
                        $surname    = $line->get('surname');
                        if ($family == '')
                        {
                            if (strlen($surname) > 0 && $surname[0] != '[')
                            $line->set('family', $oldfamily);
                        }
                        else
                            $oldfamily  = $family;
                    }
                }           // check for issues with whole page
                return reset($this->list);
            }
        }           // $this->list not constructed yet
        else
            return reset($this->list);
    }       // function CensusLineSet::rewind

    private function getAg11()
    {
        global  $warn;

        $prefix                         = "http://www.automatedgenealogy.com/census11";
        $agdisturl                      = 
                                "$prefix/District.jsp?did={$this->district}";
        //$agdistpage                   = file_get_contents($agdisturl);
        $agdist                         = new \Templating\Template($agdisturl);
        $document                       = $agdist->getDocument();
        $tables                         = $document->getElementsByTagName('table');
        if (count($tables) == 0)
            print "<p>" . str_replace('<','&lt;',$agdist->getRawTemplate()) ."</p>\n";
        $table                          = $tables[1];
        $rows                           = $table->getElementsByTagName('tr');
        foreach($rows as $ir => $row)
        {
            $cells                      = $row->getElementsByTagName('td');
            $subid                      = str_replace('<','&lt;',$cells[0]->innerHTML);

            if ($subid == $this->subdistrict)
            {
                $link                   = $cells[2]->innerHTML;
                if (preg_match('/"([^"]*)"/', $link, $matches))
                {
                    $link               = $matches[1];
                    //$agsdpage         = file_get_contents($link);
                    $agsd               = new \Templating\Template("$prefix/$link");
                    $sddoc              = $agsd->getDocument();
                    $sdtbls             = $sddoc->getElementsByTagName('table');
                    if (count($sdtbls) < 1)
                        continue;
                    $sdtbl              = $sdtbls[1];
                    $sdrows             = $sdtbl->getElementsByTagName('tr');
                    foreach($sdrows as $jr => $sdrow)
                    {
                        $pcells         = $sdrow->getElementsByTagName('td');
                        $pagenum        = $pcells[0]->innerHTML;

                        if ($pagenum == $this->page)
                        {
                            $plink      = $pcells[5]->innerHTML;
                            if (preg_match('/"([^"]*)"/', $plink, $matches))
                            {
                                $link   = $matches[1];
                                $link   = "$prefix/$link";
                                //$agpgpage   = file_get_contents($link);
                                $agpg   = new \Templating\Template($link);
                                $pgdoc  = $agpg->getDocument();
                                $pgtbls = $pgdoc->getElementsByTagName('table');
                                $pgtbl  = $pgtbls[1];
                                $pgrows = $pgtbl->getElementsByTagName('tr');
                                foreach($pgrows as $pgrow)
                                {
                                    $pgcells        = $pgrow->getElementsByTagName('td');
                                    if (count($pgcells) == 0)
                                        continue;
                                    $linecont       = $pgcells[0]->innerHTML;
                                    if (preg_match('/\d+/',$linecont,$matches))
                                    {           // line number
                                        $linenum    = intval($matches[0]);
                                        if (array_key_exists($linenum,
                                                             $this->list))
                                        {       // line number exists
                                        $cline      = $this->list[$linenum] ;
                                        $famnum     = $pgcells[2]->innerHTML;
                                        $cline['family']    = $famnum;
                                        $name       = $pgcells[3]->innerHTML;
                                        $sep        = strpos($name,' ');
                                        if ($sep)
                                        {
                                            $surname    = substr($name, 0, $sep);
                                            $given      = substr($name, $sep+1);                                        }
                                        else 
                                        {
                                            $surname    = $name;
                                            $given      = '';
                                        }
                                        $cline['surname']       = $surname;
                                        $cline['givennames']    = $given;
                                        $addr       = $pgcells[4]->innerHTML;
                                        $cline['surname']       = $surname;
                                        $sex        = $pgcells[5]->innerHTML;
                                        $cline['sex']           = $sex;
                                        $relation   = $pgcells[6]->innerHTML;
                                        $cline['relation']      = $relation;
                                        $mstat      = $pgcells[7]->innerHTML;
                                        $cline['mstat']         = $mstat;
                                        $bmon       = $pgcells[8]->innerHTML;
                                        $cline['bmon']          = $bmon;
                                        $byear      = $pgcells[9]->innerHTML;
                                        $age        = $pgcells[10]->innerHTML;
                                        $cline['age']           = $age;
                                        if (!ctype_digit($age))
                                            $age                = 0;
                                        if (ctype_digit($byear))
                                            $cline['byeartxt']  = $byear;
                                        else
                                            $byear              = 1921 - $age;
                                        $cline['byear']         = $byear;
                                        }       // line number exists
                                    }           // line number
                                }
                            }
                        }
                    }
                }
                break;
            }
        }
    }       // private function CensusLineSet::getAg11

}       // class CensusLineSet
