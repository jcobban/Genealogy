<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  DeathSet.inc                                                        *
 *                                                                      *
 *  Definition of a class representing a set of death registrations.    *
 *                                                                      *
 *  History:                                                            *
 *      2019/12/14      created                                         *
 *      2020/04/24      support ANSI standard use of "                  *
 *      2021/02/12      add county name to value of getStatistics       *
 *                      and sort by county name not code                *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Death.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class DeathSet                                                      *
 *                                                                      *
 *  Definition of a class representing a set of death registrations     *
 *  or death certificates.                                              *
 *                                                                      *
 ************************************************************************/

class DeathSet extends RecordSet
{
    /********************************************************************
     *  function DeathSet::__construct                                  *
     *                                                                  *
     *  Create an instance of the class DeathSet                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $parameters         associative array of fieldname, value   *
     *                          pairs to define the subset of the       *
     *                          records in the SQL table that are       *
     *                          represented by this instance.  The      *
     *                          parameters 'offset' and 'limit'         *
     *                          are reserved to set the OFFSET and      *
     *                          LIMIT clauses.  Use '`offset`', and     *
     *                          '`limit`' to reference fields with      *
     *                          those names. This may be null.          *
     *                                                                  *
     *  Throws:                                                         *
     *      An exception for invalid parameters.                        *
     ********************************************************************/
    function __construct($parms = array())
    {
        parent::__construct('Deaths', $parms);
    }       // DeathSet::__construct

    /********************************************************************
     *  function DeathSet::getWhere                                     *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *  $this->parms    associative array of field/value combinations   *
     *                  the field names are interpreted according to    *
     *                  the standard translate table.  Note that        *
     *                  because the parameter is an associative array   *
     *                  that only one value can be specified for each   *
     *                  field name, however in some cases that value    *
     *                  can be an array, as discussed in the following. *
     *                  The value specified for each field is           *
     *                  interpreted as follows:                         *
     *                   'birthplace':                                  *
     *                   'fatheroccupation':                            *
     *                   'fatheroccplace':                              *
     *                   'motheroccupation':                            *
     *                   'motheroccplace':                              *
     *                   'marriageplace':                               *
     *                   'informantres':                                *
     *                   'remarks':                                     *
     *                   'givennames':                                  *
     *                   'informant':                                   *
     *                   'fathername':                                  *
     *                   'fatherocc':                                   *
     *                   'mothername':                                  *
     *                   'motherocc':                                   *
     *                   'husbandname':                                 *
     *                   'formerhusband':                               *
     *                   'accoucheur':                                  *
     *                   'registrar':                                   *
     *                          The value is a regular expression.      *
     *                          For example if a simple string is       *
     *                          provided, then it will match anywhere   *
     *                          in the value. To match the exact value  *
     *                          pass '^fullvalue$'.                     *
     *                   'regyear':                                     *
     *                   'regnum':                                      *
     *                   'msvol':                                       *
     *                   'originalpage':                                *
     *                   'originalitem':                                *
     *                   'idir':                                        *
     *                          The value is either an integer or an    *
     *                          array.  If the array contains only      *
     *                          numbers then records where the field    *
     *                          matches any of the values are selected. *
     *                          Otherwise if each value is prefixed     *
     *                          by a comparison operator (<, <=, >=, >, *
     *                          !=, or <>) then the comparisons must    *
     *                          all be true to match.  For example:     *
     *                          array('>=100','<=200') includes all     *
     *                          records with a value >=100 and <=200.   *
     *                          If there are only two values, the first *
     *                          value is an integer, and the second     *
     *                          value is ':' followed by an integer     *
     *                          for example array(100,':200')           *
     *                          this is interpreted the same as the     *
     *                          previous example.                       *
     *                   'birthdate':                                   *
     *                   'marriagedate':                                *
     *                          If the parameter 'range' is specified   *
     *                          then records in which the value         *
     *                          of the field is within 'range' years    *
     *                          of the specified date are included      *
     *                          If range is not specified then this is  *
     *                          an exact match.                         *
     *                   'surname':                                     *
     *                          If 'surnamesoundex' is true or 'Y'      *
     *                          then the match is for surnames with     *
     *                          the same SOUNDEX code as this surname.  *
     *                          Otherwise this is an exact match.       *
     *                   'regdomain':                                   *
     *                   'regcounty':                                   *
     *                   'regtownship':                                 *
     *                   'sex':                                         *
     *                   'calcbirth':                                   *
     *                   'parentsmarried':                              *
     *                   'informantrel':                                *
     *                   'regdate':                                     *
     *                   'image':                                       *
     *                   'originalvolume':                              *
     *                   'changedby':                                   *
     *                          exact value match                       *
     *                   'surnamesoundex':                              *
     *                   'range':                                       *
     *                          The value is either true or 'Y' to      *
     *                          alter the behavior of other comparisons *
     *                   'offset':                                      *
     *                   'limit':                                       *
     *                          These set the returned values of        *
     *                          the parameters $limit and $offset       *
     *  $this->sqlParms     construct array of parms for PDO execute    *
     *  $this->limit        return value of limit parameter             *
     *  $this->offset       return value of offset parameter            *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     *      A zero length string is returned if the input array is      *
     *      empty, otherwise the string starts with the word WHERE      *
     *                                                                  *
     *  Side-Effects:                                                   *
     *      Issues with the supplied parms are reported by adding       *
     *      warning messages onto the global variable $warn.            *
     ********************************************************************/
    protected function getWhere()
    {
        global  $debug;
        global  $warn;

        if ($debug)
            $warn   .= '<p>DeathSet::getWhere: ' . 
                           print_r($this->parms, true) . "</p>\n";
        $where          = '';
        $and            = 'WHERE ';
        if (!is_array($this->sqlParms))
            $this->sqlParms     = array();
        $tparms         = array();
        foreach($this->parms as $fldname => $value)
        {           // translate field names and ignore empty
            $fieldLc        = Death::getRealFieldName($fldname);
            if (is_array($value) || strlen($value) > 0)
                $tparms[$fieldLc]   = $value;
        }           // translate field names and ignore empty

        // create WHERE clause
        $surname                    = null;
        $soundex                    = false;
        $range                      = 1;
        $birthDate                  = '';
        $marriageDate               = '';

        foreach($tparms as $fieldLc => $value)
        {
            switch($fieldLc)
            {           // act on specific fields
                case 'limit':
                {
                    if (is_int($value) || ctype_digit($value))
                        $this->limit        = intval($value);
                    break;
                }

                case 'offset':
                {
                    if (is_int($value) || ctype_digit($value))
                        $this->offset       = intval($value);
                    break;
                }

                case 'range':
                {
                    if (is_int($value) || ctype_digit($value))
                        $range              = intval($value);
                    break;
                }

                case 'birthplace':
                case 'fatheroccupation':
                case 'fatheroccplace':
                case 'motheroccupation':
                case 'motheroccplace':
                case 'marriageplace':
                case 'informantres':
                case 'remarks':
                case 'givennames':
                case 'informant':
                case 'fathername':
                case 'fatherocc':
                case 'mothername':
                case 'motherocc':
                case 'husbandname':
                case 'formerhusband':
                case 'accoucheur':
                case 'registrar':
                {       // text fields for pattern match
                    $expression = $this->getWherePattern($fieldLc,
                                                         $value,
                                                         true);
                    if (strlen($expression) > 0)
                    {       // expression generated
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // expression generated
                    break;
                }       // text fields

                case 'regyear':
                case 'regnum':
                case 'msvol':
                case 'originalpage':
                case 'originalitem':
                case 'idir':
                {       // positive integer fields
                    $expression = $this->getWhereInt($fieldLc,
                                                     $value,
                                                     true);
                    if (strlen($expression) > 0)
                    {       // expression generated
                        $where              .= $and . $expression;
                        $and                = ' AND ';
                    }       // expression generated
                    break;
                }       // positive integer fields

                case 'birthdate':
                {       // birth date fields
                    $birthDate              = $value;
                    break;
                }       // birth date field

                case 'marriagedate':
                {       // marriage date field
                    $marriageDate           = $value;
                    break;
                }       // date fields

                case 'surname':
                {       // surname, comparison postponed
                    $surname        = $value;
                    break;
                }       // surname

                case 'surnamesoundex':
                {       // soundex comparison flag
                    if (is_bool($value))
                        $soundex    = $value;
                    else
                    if (strlen($value) > 0 && strtoupper($value) != 'N')
                        $soundex    = true;
                    break;
                }       // soundex comparison flag

                case 'regdomain':
                case 'regcounty':
                case 'regtownship':
                case 'sex':
                case 'calcbirth':
                case 'parentsmarried':
                case 'informantrel':
                case 'regdate':
                case 'image':
                case 'originalvolume':
                case 'changedby':
                {       // other fields exact match
                    $where      .= "$and `$fieldLc`=:$fieldLc";
                    $this->sqlParms[$fieldLc]       = $value;
                    $and        = ' AND ';
                    break;
                }       // other fields
            }           // act on specific fields
        }               // loop through all parameters

        // birth date interpretation involves two parameters
        if (strlen($birthDate) > 0)
        {
            $date               = new LegacyDate(' ' . $birthDate);
            $y                  = $date->getYear();
            $m                  = $date->getActualMonth();
            if ($m > 0)
                $where          .= $and .
                        "ABS(DATEDIFF(CalcDeath, '$y-$m-15'))<(30 * :range)";
            else
                $where          .= $and .
                        "ABS(DATEDIFF(CalcDeath, '$y-6-15'))<(365 * :range)";
            $this->sqlParms['range']    = $range;
            $and                = ' AND ';
        }

        // marriage date interpretation involves two parameters
        if (strlen($marriageDate) > 0)
        {
            $date               = new LegacyDate(' ' . $marriageDate);
            $y                  = $date->getYear();
            $m                  = $date->getMonth();
            $d                  = $date->getDay();
            $where              .= $and .
"ABS(DATEDIFF(STR_TO_DATE(MarriageDate,'%d %M %Y'), '$y-$m-$d'))<(365 * :range)";
            $this->sqlParms['range']    = $range;
            $and                = ' AND ';
        }

        // special checks for surname
        if (!is_null($surname))
        {           // surname comparison requested
            if (preg_match("/[.+*?^$]/", $surname))
            {       // match pattern
                $where  .= $and . 'Surname REGEXP :surname';
            }       // match pattern
            else
            if ($soundex)
            {       // match soundex
                $where  .= $and . 'SurnameSoundex = LEFT(SOUNDEX(:surname),4)';
            }       // match soundex
            else
            {       // match exact
                $where  .= $and . 'Surname=:surname';
            }       // match exact

            $this->sqlParms['surname']  = $tparms['surname'];
        }           // surname comparison requested

        return $where;
    }       // DeathSet::getWhere

    /********************************************************************
     *  function DeathSet::getSummary                                   *
     *                                                                  *
     *  Get year by year statistics for death registrations.            *
     *  This returns an array with one record per year.                 *
     *                                                                  *
     *  Example:                                                        *
     *      $deaths     = new DeathSet(array('domain' => 'CAON'));      *
     *      $result     = $deaths->getSummary();                        *
     *                                                                  *
     *  Returns:                                                        *
     *      array each row of which is an array containing the          *
     *      following values:                                           *
     *          0       year of registrations                           *
     *          1       number of death registrations transcribed       *
     *          2       number of death registrations linked to tree    *
     ********************************************************************/
    function getSummary()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query  = "SELECT RegYear, SUM(Surname != ''), SUM(IDIR > 0) " .
                        'FROM Deaths ' . $this->where .
                            ' GROUP BY RegYear ORDER BY RegYear';
        $sqlParmsText       = print_r($this->sqlParms, true);
        $queryTxt           = debugPrepQuery($query, $this->sqlParms);
        $stmt               = $connection->prepare($query);
        if ($stmt->execute($this->sqlParms))
        {           // query successful
            $result         = $stmt->fetchAll(PDO::FETCH_NUM);
            if ($debug)
            {           // debug output
                $warn   .= "<p>DeathSet.inc: " . __LINE__ . "query='$queryTxt'</p>\n".
                           "<p>sqlParms=$sqlParmsText</p>\n";
            }           // debug output
        }           // query successful
        else
        {           // query failed
            $msg        .= "query '$queryTxt' failed, " .
                           print_r($stmt->errorInfo(), true);
            $result = null;
        }           // query failed
        return $result;
    }       // Death::getSummary

    /********************************************************************
     *  function Death::getStatistics                                   *
     *                                                                  *
     *  Get statistics for specific set of death registrations.         *
     *                                                                  *
     *  Example:                                                        *
     *      $deaths     = new DeathSet(array('domain'   => 'CAON',      *
     *                                       'year'     => 1887));      *
     *      $result     = $deaths->getStatistics();                     *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an array containing the following values for each           *
     *      county in the domain:                                       *
     *              regcounty       county code                         *
     *              count           number of transcribed records       *
     *              linkcount       number linked to the tree           *
     *              low             lowest registration number          *
     *              high            highest registration number         *
     *              countyname      name of county from Counties table  *
     ********************************************************************/
    function getStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query  = 'SELECT RegCounty AS County, ' .
                                "SUM(Surname != '') AS `Count`, " .
                                'SUM(Idir != 0) AS LinkCount, ' .
                                'MIN(RegNum) as low, ' .
                                'MAX(RegNum) as high,  ' .
                                'COALESCE(Counties.Name, RegCounty) as countyname ' .
                        'FROM Deaths JOIN Counties ON Counties.Code=RegCounty ' .
                        $this->where .
                        ' GROUP BY CountyName,RegCounty ' .
                        'ORDER BY CountyName';
        $stmt           = $connection->prepare($query);
        if ($stmt->execute($this->sqlParms))
        {
            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        else
        {
            $msg        .= "query '" . $query . "' failed: " .
                               print_r($this->sqlParms, true) . ': ' .
                               print_r($stmt->errorInfo(), true);
            return null;
        }       // query failed
    }       // Death::getStatistics

    /********************************************************************
     *  function Death::getCountyStatistics                             *
     *                                                                  *
     *  Get statistics for specific year of death registrations.        *
     *                                                                  *
     *  Example:                                                        *
     *      $deaths     = new DeathSet(array('domain'   => 'CAON',      *
     *                                       'year'     => 1887,        *
     *                                       'county'   => 'Msx'));     *
     *      $result     = $deaths->getCountyStatistics();               *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an array containing the following values for each           *
     *      town, city, or township in the county:                      *
     *              county          county code                         *
     *              township        township name                       *
     *              count           number of transcribed records       *
     *              linkcount       number linked to the tree           *
     *              low             lowest registration number          *
     *              high            highest registration number         *
     *              currhigh        highest registration number         *
     *                              excluding delayed registrations     *
     ********************************************************************/
    function getCountyStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query  = 'SELECT RegCounty AS County, RegTownship AS Township, ' .
                                "SUM(Surname != '') AS `Count`, " .
                                'SUM(Idir != 0) AS LinkCount, ' .
                                'MIN(RegNum) as low, ' .
                                'MAX(RegNum) as high,  ' .
                                '(SELECT MAX(RegNum) FROM Deaths  WHERE RegYear=:regyear and RegCounty=:regcounty AND RegTownship=Township AND RegNum<500000) AS `currhigh` ' .
                        'FROM Deaths ' .
                        $this->where .
                        ' GROUP BY RegCounty, RegTownship ' .
                        'ORDER BY RegCounty, RegTownship';
        $stmt           = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

        if ($stmt->execute($this->sqlParms))
        {
            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        else
        {
            $msg        .= "query '$queryText' failed: " .
                               print_r($stmt->errorInfo(), true);
            print "<p class='error'>$msg</p>\n";
            return null;
        }           // query failed
    }               // function Death::getCountyStatistics

}       // class DeathSet 
