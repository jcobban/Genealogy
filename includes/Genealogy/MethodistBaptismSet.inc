<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MethodistBaptismSet.inc                                             *
 *                                                                      *
 *  Definition of a class representing a set of Methodist baptism       *
 *  registrations.                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2019/04/28      created                                         *
 *      2019/12/09      missing AND before soundex in where expression  *
 *      2020/01/25      add method getStatistics                        *
 *                      add method getPageStatistics                    *
 *                      add method getVolumeStatistics                  *
 *      2020/04/24      support ANSI standard use of "                  *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/MethodistBaptism.inc';

/************************************************************************
 *  class MethodistBaptismSet                                           *
 *                                                                      *
 *  Definition of a class representing a set of marriage registrations  *
 *  or marriage certificates.                                           *
 *                                                                      *
 ************************************************************************/

class MethodistBaptismSet extends RecordSet
{

    /********************************************************************
     *  function MethodistBaptismSet::__construct                       *
     *                                                                  *
     *  Create an instance of the class MethodistBaptismSet             *
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
     ********************************************************************/
    function __construct($parms = array())
    {
        parent::__construct('MethodistBaptisms',
                           $parms);
    }       // MethodistBaptismSet::__construct

    /********************************************************************
     *  function MethodistBaptismSet::getWhere                          *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
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
            $warn           .= '<p>MethodistBaptismSet::getWhere: ' . __LINE__ . ' ' .
                               print_r($this->parms, true) . "</p>\n";
        $where              = '';
        $and                = 'WHERE ';

        if (!is_array($this->sqlParms))
            $this->sqlParms     = array();
        $tparms                 = array();
        $regYear                = null;

        // build WHERE clause
        foreach($this->parms as $fldname => $value)
        {                       // translate field names and ignore empty
            if (is_null($value) || strlen($value) == 0)
                continue;
            $fldnameLc          = strtolower($fldname);
            switch($fldnameLc)
            {                   // act on specific field names
                case 'surname':
                {               // surname
                    $where      .= $and . $this->getWherePattern('Surname',
                                                                 $value,
                                                                 true);
                    $and        = ' AND ';
                    break;
                }

                case 'surnamesoundex':
                {
                    $where      .= "$and LEFT(SOUNDEX(Surname),4)=LEFT(SOUNDEX(:surname),4)";
                    $this->sqlParms['surname']  = $value;
                    $and        = ' AND ';
                    break;
                }               // surname

                case 'volume':
                case 'page':
                case 'idir':
                {               // numeric fields
                    $where      .= $and . $this->getWhereInt($fldname,
                                                             $value,
                                                             $fldnameLc);
                    $and        = ' AND ';
                    break;
                }               // numeric fields


                case 'district':
                case 'area':
                case 'givenname':
                case 'surname':
                case 'father':
                case 'mother':
                case 'residence':
                case 'birthplace':
                case 'birthdate':
                case 'baptismdate':
                case 'baptismplace':
                case 'minister':
                {               // text fields
                    $where      .= $and . $this->getWherePattern($fldname,
                                                                 $value,
                                                                 true);
                    $and        = ' AND ';
                    break;
                }               // match in string

            }                   // act on specific field names
        }                       // translate field names and ignore empty

        return $where;
    }       // function MethodistBaptismSet::getWhere

    /********************************************************************
     *  function MethodistBaptismSet::getStatistics                     *
     *                                                                  *
     *  Get statistics for specific set of baptism registrations.       *
     *                                                                  *
     *  Example:                                                        *
     *      $baptisms       = new MethodistBaptismSet(                  *
     *                          array('pattern'     => $pattern));      *
     *      $result         = $baptisms->getStatistics();               *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an array containing the following values for each           *
     *      district in the set:                                        *
     *              district        district name                       *
     *              count           number of transcribed records       *
     *              linkcount       number linked to the family tree    *
     ********************************************************************/
    function getStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query  = 'SELECT District, ' .
                        "SUM(Surname != '') AS `Count`, " .
                        'SUM(Idir != 0) AS LinkCount ' .
                        'FROM MethodistBaptisms ' .
                        $this->where .
                        ' GROUP BY District' .
                        ' ORDER BY District';
        $stmt           = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);
        if ($stmt->execute($this->sqlParms))
        {
            if ($debug)
                $warn   .= "<p>MethodistBaptismSet::getStatistics: " .
                            " query='$queryText'</p>\n";
            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        else
        {
            $msg        .= "query '$queryText' failed: " .
                               print_r($stmt->errorInfo(), true);
            return null;
        }       // query failed
    }       // MethodistBaptismSet::getStatistics

    /********************************************************************
     *  function MethodistBaptismSet::getDistrictStatistics             *
     *                                                                  *
     *  Get statistics for each "Area" within a "District".             *
     *                                                                  *
     *  Example:                                                        *
     *      $baptisms   = new MethodistBaptismSet(                      *
     *                              array('district'    => "^$dname$"));*
     *      $result     = $baptisms->getDistrictStatistics();           *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an associative array containing the following values        *
     *      for each page in the volume:                                *
     *              area            area within District                *
     *              count           number of transcribed records       *
     *              linkcount       number linked to the family tree    *
     ********************************************************************/
    function getDistrictStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query      = "SELECT Area, COUNT(*) AS `Count`, SUM(IDIR > 0) AS LinkCount" .
                        " FROM MethodistBaptisms" .
                        " WHERE District=:district1" .
                        " GROUP BY Area, District ORDER BY Area";
        $stmt           = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

        if ($stmt->execute($this->sqlParms))
        {
            if ($debug)
                $warn   .= "<p>MethodistBaptismSet::getDistrictStatistics: " .
                            " query='$queryText'</p>\n";
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
    }       // function MethodistBaptismSet::getDistrictStatistics

    /********************************************************************
     *  function MethodistBaptismSet::getVolumeStatistics               *
     *                                                                  *
     *  Get statistics for each Volume.                                 *
     *                                                                  *
     *  Example:                                                        *
     *      $baptisms   = new MethodistBaptismSet();                    *
     *      $result     = $baptisms->getVolumeStatistics();             *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an associative array containing the following values        *
     *      for each page in the volume:                                *
     *              volume          area within Volume                  *
     *              pages           number of pages                     *
     *              done            number of registrations transcribed *
     *              linkcount       number linked to the family tree    *
     ********************************************************************/
    function getVolumeStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query      = "SELECT Volume, MAX(Page) as Pages, COUNT(*) as Done, " .
                            'SUM(Idir>0) AS LinkCount ' .
                            " FROM MethodistBaptisms" .
                            " GROUP BY Volume ORDER BY Volume";
        $stmt           = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

        if ($stmt->execute($this->sqlParms))
        {
            if ($debug)
                $warn   .= "<p>MethodistBaptismSet::getVolumeStatistics: " .
                            " query='$queryText'</p>\n";
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
    }       // function MethodistBaptismSet::getVolumeStatistics

    /********************************************************************
     *  function MethodistBaptismSet::getPageStatistics                 *
     *                                                                  *
     *  Get statistics for specific volume of registrations.            *
     *                                                                  *
     *  Example:                                                        *
     *      $baptisms   = new MethodistBaptismSet(                      *
     *                                  array('volume'  => $volume));   *
     *      $result     = $baptisms->getPageStatistics();               *
     *                                                                  *
     *  Returns:                                                        *
     *      An array each row of which is                               *
     *      an associative array containing the following values        *
     *      for each page in the volume:                                *
     *              page            page number                         *
     *              count           number of transcribed records       *
     *              linkcount       number linked to the family tree    *
     ********************************************************************/
    function getPageStatistics()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;

        // execute the query
        $query      = "SELECT Page, COUNT(*) AS `Count`, SUM(IDIR > 0) AS LinkCount" .
                        " FROM MethodistBaptisms" .
                        " WHERE Volume=:volume" .
                        " GROUP BY Volume, Page ORDER BY Volume, Page";
        $stmt           = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

        if ($stmt->execute($this->sqlParms))
        {
            if ($debug)
                $warn   .= "<p>MethodistBaptismSet::getPageStatistics: " .
                            " query='$queryText'</p>\n";
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
    }       // function MethodistBaptismSet::getPageStatistics

}       // class MethodistBaptismSet
