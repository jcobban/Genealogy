<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Location.inc                                                        *
 *                                                                      *
 *  Definition of a class representing an location in a genealogical    *
 *  database.  This class provides access to the information in a row   *
 *  of the table tblLR.                                                 *
 *                                                                      *
 *  TABLE `tblLR`                                                       *
 *    `IDLR`            INT(10) NOT NULL AUTO_INCREMENT,                *
 *    `FSPlaceID`       VARCHAR(255) DEFAULT NULL,                      *
 *    `Preposition`     VARCHAR(120) DEFAULT NULL,                      *
 *    `Location`        VARCHAR(255) DEFAULT NULL,                      *
 *    `SortedLocation`  VARCHAR(255) DEFAULT NULL,                      *
 *    `ShortName`       VARCHAR(255) DEFAULT NULL,                      *
 *    `Tag1`            TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `Used`            TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `Notes`           VARCHAR(255) DEFAULT NULL,                      *
 *    `Verified`        TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `Latitude`        DOUBLE(53,0) DEFAULT NULL,                      *
 *    `Longitude`       DOUBLE(53,0) DEFAULT NULL,                      *
 *    `FSResolved`      TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `VEResolved`      TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `qsTag`           TINYINT(3) UNSIGNED DEFAULT NULL,               *
 *    `Zoom`            TINYINT(3) UNSIGNED DEFAULT '12'                *
 *    `Boundary`        TEXT NOT NULL,                                  *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/21      exploit common functionality of LegacyRecord    *
 *                      to simplify implementation                      *
 *      2010/09/25      zero length location always maps to IDLR 1      *
 *                      default FS Place ID to empty string             *
 *      2010/09/28      correct insert when record already exists       *
 *      2010/10/01      If no match on full name, try short name        *
 *      2010/10/10      Force IDLR to 1 if name is empty                *
 *      2010/10/11      Check error on result not connection            *
 *      2010/10/20      changed parent constructor                      *
 *      2010/10/23      $connection made global                         *
 *      2010/11/05      Do not insert on record not found by IDLR       *
 *      2010/12/05      Get lowest IDLR if multiple matches on location *
 *                      name                                            *
 *      2011/09/26      add methods setLatitude and setLongitude to     *
 *                      support geo-locator format values               *
 *      2011/11/12      add method getZoom for Google Maps support      *
 *      2012/01/13      rename classes to have upper case character     *
 *      2012/02/24      change handling of IDLR that no longer refers to*
 *                      an existing record to create a new dummy record *
 *                      that will facilitate fixing the entries.        *
 *      2012/07/28      hide lost location records at end of report     *
 *      2012/10/06      use LegacyRecord::setField to manipulate record *
 *                      fields so save method will correctly construct  *
 *                      appropriate INSERT or UPDATE to synchronize the *
 *                      database record.                                *
 *                      All set methods return former value             *
 *      2012/10/20      standardize initialization of new record        *
 *      2012/12/31      set sort so street addresses sort last by lot   *
 *                      number                                          *
 *                      add method setName for setting location name    *
 *                      add method setField to ensure appropriate set   *
 *                      methods are called.                             *
 *      2013/02/14      throw an exception for bad IDLR                 *
 *      2013/03/14      standardize behavior by not saving record on    *
 *                      create                                          *
 *      2013/04/12      add Boundary field for region border            *
 *      2013/04/16      change getLatitude, getLongitude, setLatitude,  *
 *                      setLongitude, and toXml to work only with       *
 *                      external values                                 *
 *      2013/05/14      calculate default zoom level based upon number  *
 *                      of commas                                       *
 *                      only change short name if it was not            *
 *                      previously set                                  *
 *      2013/07/22      expand street address pattern to include street *
 *                      names containing an apostrophe (O'Hara St)      *
 *      2013/08/09      base class renamed to Record                    *
 *      2014/03/06      interpret IDLR 0 as IDLR 1                      *
 *      2014/04/02      improve efficiency of prefix search             *
 *      2014/07/06      set higher default zoom level to street locs    *
 *      2014/09/05      add static methods getLocations,                *
 *                      deleteLocations, updateLocations, and the       *
 *                      support function getWhere                       *
 *      2014/09/09      location matches only to beginning of name      *
 *      2014/09/15      add functionality to updateLocations to         *
 *                      perform search and replace in text fields       *
 *      2014/09/18      set zoom level on initial creation from name    *
 *                      Constructor with negative integer formerly      *
 *                      created new object named with the number,       *
 *                      now rejects as bad IDLR value                   *
 *                      on construct with array input, ensure full row  *
 *                      Constructor supports an array of initializers   *
 *                      including IDLR value to modify existing loc     *
 *                      change the implementation of parameter 'name'   *
 *                      to getLocations so it agrees with the former    *
 *                      implementation of script getLocationXml.php     *
 *                      return the query command from getLocations      *
 *      2014/09/26      show true/false in debug trace, not 1/''        *
 *      2014/09/29      add method getField to invoke class specific    *
 *                      handling of some fields                         *
 *      2014/10/01      fix bug in array parameter to constructor       *
 *                      add method addOwner                             *
 *      2014/10/16      do not access database to construct null loc    *
 *      2014/12/08      debugging output in $warn                       *
 *      2014/12/22      label of dump after construct was incomplete    *
 *                      constructor called itself on parm array input   *
 *      2015/02/21      add xml parameter to getLocations               *
 *      2015/04/27      escape < and > in SQL commands displayed in     *
 *                      XML or HTML                                     *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/11/27      fine tune patterns for name matching            *
 *      2015/12/08      base class changed to RecordPic                 *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2016/05/09      do not throw exception for bad IDLR             *
 *      2016/06/06      do not throw exception for bad IDLR in getIdlr  *
 *      2016/07/20      parameter list to logSqlUpdate changed          *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/11/04      static functions replaced by RecordSet          *
 *      2017/11/12      add 'searchname' to method get to get value     *
 *                      for improved search of Google maps              *
 *      2017/12/18      add support for temporary fields                *
 *      2018/01/27      change searchname and county to temporary fields*
 *                      add name, searchname, and county to translate   *
 *      2018/02/24      DomainSet not defined                           *
 *      2018/11/19      only give boundary coordinates to 6 decimal     *
 *                      places in get('boundary')                       *
 *      2019/07/21      add IDLR to sort order for duplicate names      *
 *                      add Location::getLocation                       *
 *      2019/09/26      in getNotes only replace new lines with <br>    *
 *                      if the content is not already HTML              *
 *      2019/10/08      remove tracelog entries                         *
 *      2019/11/23      correct implementation of getLocation           *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2020/04/24      change initial value of "used" to 0             *
 *      2020/05/22      correct creation of new Location with OR        *
 *                      expression                                      *
 *      2020/05/28      correct default setting of sortedlocation       *
 *                      for single digit con number                     *
 *      2020/07/30      do not throw exception in getId/getIdlr for     *
 *                      new record                                      *
 *      2020/10/24      correct handling of synthesized fields          *
 *                      county and searchname                           *
 *      2020/12/16      correct setting of SortedLocation whenever      *
 *                      Location is set, including during creation      *
 *      2020/12/30      replace HTML escapes in location name           *
 *      2021/01/03      static method getLocation extended to support   *
 *                      using location as unique key                    *
 *      2021/02/18      add information to error log                    *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';

/************************************************************************
 *  Location                                                            *
 *                                                                      *
 *  Definition of a class implementing behavior for location records    *
 *  within a genealogy database.                                        *
 *                                                                      *
 ************************************************************************/

class Location extends RecordPic
{

    /********************************************************************
     *  Location::$initRow                                              *
     *                                                                  *
     *  A static array containing default values for a new record       *
     ********************************************************************/
    protected static    $initRow    = array(
                            'idlr'              => 0,
                            'fsplaceid'         => '',
                            'location'          => 'Lost Location 0',
                            'used'              => 0,
                            'sortedlocation'    => 'zzzzzzzzzzzzz 0',
                            'latitude'          => 0,
                            'longitude'         => 0,
                            'tag1'              => 0,
                            'shortname'         => '',
                            'preposition'       => '',
                            'notes'             => '',
                            'verified'          => 0,
                            'fsresolved'        => 0,
                            'veresolved'        => 0,
                            'qstag'             => 0,
                            'zoom'              => 8,
                            'boundary'          => '');

    /********************************************************************
     *  Location::$info                                                 *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'tblLR',
                'name'              => 'Locations',
                'prime'             => 'idlr',
                'srvmg'             => true,
                'fldcount'          => 4,
                'order'             => 'SortedLocation, IDLR',
                'classname'         => 'Location');

    /********************************************************************
     *  Location::$translate                                            *
     *                                                                  *
     *  A static array containing additional supported field names.     *
     ********************************************************************/
    protected static    $translate  = array(
                            'name'              => 'location',
                            'searchname'        => 'searchname',
                            'county'            => 'county');

    /********************************************************************
     *  Location::$defaultOrder                                         *
     *                                                                  *
     *  The default contents of the ORDER BY clause in SELECTs          *
     *  If this is an empty string then the value of the 'prime'        *
     *  attribute of the entry in $primeKey is used.                    *
     ********************************************************************/
    protected static    $defaultOrder   = 'SortedLocation, IDLR';

    /********************************************************************
     *  Location::$namePattern                                          *
     *                                                                  *
     *  This pattern is used for parsing locations to get sort values   *
     *  For consistency of behavior this pattern should be the same     *
     *  as the one in Location.js                                       *
     ********************************************************************/
    private static  $namePattern    =
            "/^(E ½ |S ½ |W ½ |N ½ |E½ |S½ |W½ |N½ |½ |E ¼ |S ¼ |W ¼ |N ¼ |E ½ E ½ |E ½ S ½ |E ½ W ½ |E ½ N ½ |S ½ E ½ |S ½ S ½ |S ½ W ½ |S ½ N ½ |W ½ E ½ |W ½ S ½ |W ½ W ½ |W ½ N ½ |N ½ E ½ |N ½ S ½ |N ½ W ½ |N ½ N ½ |NE ¼ |NW ¼ |SE ¼ |SW ¼ |NE ½ |NW ½ |SE ½ |SW ½ |N pt |NE pt |E pt |NW pt |S pt |SE pt |SW pt |W pt |pt |pt E ½ |pt S ½ |pt W ½ |pt N ½ |)((lot )?[0-9½, &\-]+) ([a-zA-Z0-9'., ]*)(,.*)$/";

    /********************************************************************
     *  Location::$locationIndex                                        *
     *                                                                  *
     *  To save time if a specific record is requested more than once   *
     *  during a process, maintain a table of Location records          *
     *  indexed by IDLR value.                                          *
     ********************************************************************/
    protected static    $locationIndex  = array();

    /********************************************************************
     *  Location::$locationNames                                        *
     *                                                                  *
     *  To save time if a specific record is requested more than once   *
     *  during a process, maintain a table of Location records          *
     *  indexed by Location value.                                      *
     ********************************************************************/
    protected static    $locationNames  = array();

    /********************************************************************
     *  static function Location::getLocation                           *
     *                                                                  *
     *  Factory method to get an existing entry or if there is none     *
     *  create a new instance of the class Location using a key.        *
     *                                                                  *
     *  Input:                                                          *
     *      $idlr       integer identifier of a location or             *
     *                  location name                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      an instance of Location or null                             *
     ********************************************************************/
    static function getLocation($idlr)
    {
        if (is_int($idlr) || ctype_digit($idlr))
        {
            if (array_key_exists($idlr, self::$locationIndex))
                $location       = self::$locationIndex[$idlr];
            else
            {
                $location       = new Location(array('idlr' => $idlr));
                self::$locationIndex[$idlr] = $location;
                $name                       = $location['location'];
                self::$locationNames[$name] = $location;
            }
            return $location;
        }
        else
        if (is_string($idlr))
        {
            $name               = $idlr;
            if (array_key_exists($name, self::$locationNames))
                $location       = self::$locationNames[$name];
            else
            {
                $location       = new Location(array('location' => $name));
                self::$locationNames[$name] = $location;
                $idlr                       = $location['idlr'];
                self::$locationIndex[$idlr] = $location;
            }
            return $location;
        }
        else
            return null;
    }           // static function Location::getLocation

    /********************************************************************
     *  function Location::__construct                                  *
     *                                                                  *
     *  Given the identifier or database row construct a Location       *
     *  record.                                                         *
     *                                                                  *
     *  Input:                                                          *
     *      $id         a database record presented as an array         *
     *                  or an array of initializers/search parms        *
     *                  the following options are deprecated:           *
     *                  numeric identifier of a Location (key of tblLR) *
     *                  or a string location name                       *
     *                                                                  *
     *  Returns instance of Location record.                            *
     *                                                                  *
     *  Sets $this->msg on bad parameters                               *
     ********************************************************************/
    function __construct($id)
    {
        global  $debug;
        global  $warn;
        global  $connection;

        if ($debug)
            $warn           .= "<p>new Location(" . var_export($id, true) .
                                "</p>\n";
        $needInsert         = true;

        // action depends upon type of first parameter
        if (is_array($id))
        {           // array parameter
            $parms          = $id;
        }           // array parameter
        else
        if (is_null($id) || $id === '')
            $parms          = array('idlr' => 1);
        else
        if (is_string($id))
        {           // string parameter
            if (ctype_digit($id))
            {
                $id         = intval($id);
                if ($id == 0)
                    $parms  = array('idlr' => 1);
                else
                    $parms  = array('idlr' => $id);
            }
            else
            {
                $id         = htmlspecialchars_decode($id);
                $parms      = array(array('location'        => $id,
                                          'shortname'       => $id));
            }
        }           // string parameter
        else
        if (is_int($id))
        {           // integer parameter
            if ($id < 1)
                $parms      = array('idlr' => 1);
            else
                $parms      = array('idlr' => $id);
        }           // integer parameter
        else
        {           // unexpected parameter type
            $this->msg  .= "Location::__construct: " .
                            "called with " .
                            gettype($id) . ", value=" .
                            print_r($id, true) . '. ';
            $parms      = array();
        }           // unexpected parameter type

        // validate field names in parameter array
        foreach($parms as $fldname => $value)
        {       // validate field names in parms
            if (!is_array($value))
            {       // simple value
                $fldnameLc  = strtolower($fldname);
                if (!array_key_exists($fldnameLc, self::$initRow))
                    $warn   .= "<p>Location::__construct: Unknown field name '$fldname'</p>\n";
            }       // simple value
        }       // validate field names in parms

        if (count($parms) >= count(self::$initRow))
        {           // full row from database supplied
            $dbrow                      = $parms;
            $needInsert                 = $dbrow['idlr'] == 0;
        }           // full row from database supplied
        else
        {           // search parameters
            $dbrow                      = null;
            $where                      = '';
            $and                        = 'WHERE ';
            $sqlParms                   = array();
            $idlr                       = null;
            $location                   = null;
            foreach($parms as $fldname => $value)
            {               // loop through parameters
                $fldnameLc              = strtolower($fldname);
                if (is_array($value))
                {           // combine by OR
                    if (count($value) > 0)
                    {       // not empty
                        $where          .= $and;
                        $or             = '(';
                        foreach($value as $orfield => $orvalue)
                        {
                            $where      .= "$or`$orfield`=?";
                            $sqlParms[] = $orvalue;
                            $or         = ' OR ';
                            switch(strtolower($orfield))
                            {       // act on specific field names
                                case 'location':
                                {
                                    $location       = $orvalue;
                                    break;
                                }
                            }       // act on specific field names
                        }
                        $where          .= ')';
                        $and            = ' AND ';
                    }       // not empty
                }           // combine by OR
                else
                switch($fldnameLc)
                {       // act on specific field names
                    case 'idlr':
                    {
                        $where          .= "$and`$fldname`=?";
                        $sqlParms[]     = $value;
                        $idlr           = $value;
                        $and            = ' AND ';
                        break;
                    }

                    case 'location':
                    {
                        $value          = htmlspecialchars_decode($value);
                        $where          .= "$and`$fldname`=?";
                        $sqlParms[]     = $value;
                        $location       = $value;
                        $and            = ' AND ';
                        break;
                    }
                }       // act on specific field names
            }           // loop through parameters

            // query the database
            if (strlen($where) > 0)
            {           // have where expression
                $query                  = "SELECT * FROM tblLR $where";
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                if ($stmt->execute($sqlParms))
                {
                    $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($debug)
                        $warn           .= "<p>Location::__construct: " .
                                            __LINE__ . " $queryText</p>\n";

                    if (count($result) == 0)
                    {   // no location matches parameters, create new
                        $dbrow          = self::$initRow;   // defaults
                        $needInsert     = true;

                        if (is_string($location))
                        {       // update fields based upon new location name
                            $dbrow['location']  = $location;

                            // calculate initial value of zoom level
                            // for displaying map
                            $locationLc = strtolower($location);
                            if (strpos($locationLc,' st,') > 0 ||
                                strpos($locationLc,' ave,') > 0 ||
                                strpos($locationLc,' rd,') > 0)
                                $depth  = 15;
                            else
                                $depth  = 6 + 2 * substr_count($location, ',');
                            $dbrow['zoom']  = $depth;
                        }       // update fields based upon new location name
                        else
                        if ($idlr)
                        {       // update fields based upon IDLR
                            $tempname               = 'Lost Location ' . $idlr;
                            $dbrow['location']      = $tempname;
                            $dbrow['sortedlocation']= $tempname;
                            $dbrow['shortname']     = $tempname;
                        }       // update fields based upon IDLR
                    }       // no location matches parameters
                    else
                    {       // existing row
                        $dbrow      = $result[0];
                        $needInsert     = false;
                    }       // existing row
                }           // successful query
                else
                {           // error issuing query
                    $this->msg      .= "Location::__construct: " .
                                        "'$queryText' " .
                                        print_r($stmt->errorInfo(),true) . '. ';
                }           // error issuing query
            }           // have where expression
            else
                $this->msg      .= "Location::__construct: " .
                                    "No IDLR Parameter Specified. ";
        }           // search parameters

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'tblLR');
        $this->needInsert   = $needInsert;

        // save a reference to existing record
        $location           = $this->row['location'];
        if ($needInsert)
        {                   // new record
            if (is_string($location))
                $this->setName($location);  // side effects of name
        }                   // new record
        else
        {                   // existing record
            $idlr           = $this->row['idlr'];
            if ($idlr > 0)
            {               // have key
                if (!array_key_exists($idlr, self::$locationIndex))
                {           // not already saved
                    self::$locationIndex[$idlr]     = $this;
                    self::$locationNames[$location] = $this;
                }           // not already saved
            }               // have key
        }                   // existing record

        // use any other parameters to modify fields
        if (count($parms) < count(self::$initRow))
        {
            foreach($parms as $fldname => $value)
            {       // loop through parameters
                $fldnameLc              = strtolower($fldname);
                if ($fldnameLc != 'idlr' &&
                    $fldnameLc != 'location' &&
                    !is_array($value))
                    $this->set($fldname, $value);
            }       // loop through parameters
        }

        $this->set('searchname', $this->getSearchName());

        // diagnostic output if debug is set
        if ($this->row['idlr'] > 1)
        {
            $tf     = $this->needInsert?'true':'false';
            $this->dump("Location Record constructed: needInsert=$tf");
        }
    }       // Location::__construct

    /********************************************************************
     *  function Location::getSearchName                                *
     *                                                                  *
     *  Get the value of the simulated field 'searchname'               *
     *                                                                  *
     *  Returns:                                                        *
     *      a string designed for searching Google maps                 *
     *                                                                  *
     *  Side-Effects:                                                   *
     *      Initializes $this->extras['searchname'] and                 *
     *      $this->extras['county']                                     *
     ********************************************************************/
    private function getSearchName()
    {           // name to pass to Google Maps
        global  $debug;
        global  $warn;

        if (isset($this->extras['searchname']))
            return $this->extras['searchname'];

        $name           = trim($this->row['location']);
        $searchName     = $name;
        $part1          = '';
        $part2          = '';
        $county         = '';
        //        local    county       province        country
        $geoPattern     = "/^(.*),\s*([^,]+),\s*([^,]+),\s*(\w\w)$/";
        $results        = array();
        $res1           = preg_match($geoPattern, $name, $results);
        if ($res1)
        {       // ends with county, province, country
            $part1      = trim($results[1]);    // street or lot
            $county     = trim($results[2]);    // county or city name
            $province   = $results[3];          // province code
            $cc         = $results[4];          // country
            if (strlen($province) > 2)
            {
                $domains        = new DomainSet(array('cc'      => $cc,
                                                      'name'    => $province));
                if ($domains->count() > 0)
                {
                    $domain     = $domains->rewind();
                    $province   = $domain->get('province');
                }
                else
                {
                    $this->extras['county']     = $county;
                    $this->extras['searchname'] = $searchName;
                    return $searchName;
                }
            }

            // the following is to strip off rural addresses that
            // are not supported by Google maps
            $res2   = preg_match("/\b(lot|lots|con|cons)\b[^,]*,(.*)$/",
                                 $part1,
                                 $results);
            $getParms   = array('domain'    => $cc . $province,
                                'name'      => $county);
            $counties   = new CountySet($getParms);
            if (count($counties) > 0)
            {           // $county is a valid county name
                $countyObj  = $counties->rewind();  // get first County
                $county     = $countyObj->getName();
                $searchName = "$part1, $county county, $province, CA";
            }           // $county is a valid county name
            else
            {
                if ($res2)
                {       // remove rural lot info
                    $part2  = trim($results[2]);
                    $searchName = "$part2, $county, $province, CA";
                }       // remove rural lot info
                else
                    $searchName = "$part1, $county, $province, CA";
            }
        }
        else
        if (strlen($name) > 0)
        {
            if ($debug)
                $warn   .= "<p>Location::getSearchName: " . __LINE__ .
                        " '$name' doesn't match '$geoPattern'</p>\n";
        }
        $this->extras['county']     = $county;
        $this->extras['searchname'] = $searchName;
        return $searchName;
    }       // Location::getSearchName

    /********************************************************************
     *  function Location::get                                          *
     *                                                                  *
     *  Get the value of a field by name                                *
     *                                                                  *
     *  Parameters:                                                     *
     *      $name       field name                                      *
     *                  A special value is 'searchname' which gets      *
     *                  a string designed for searching Google maps     *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function get($field)
    {
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {           // act on specific field names
            case 'idlr':
            {
                return $this->getIdlr();
            }

            case 'location':
            {
                return $this->getName();
            }

            case 'latitude':
            {
                return $this->getLatitude();
            }

            case 'longitude':
            {
                return $this->getLongitude();
            }

            case 'fsplaceid':
            case 'used':
            case 'sortedlocation':
            case 'tag1':
            case 'shortname':
            case 'preposition':
            case 'notes':
            case 'verified':
            case 'fsresolved':
            case 'veresolved':
            case 'qstag':
            case 'zoom':
            {
                return parent::get($field);
            }

            case 'boundary':
            {
                $boundary       = $this->row[$field];
                if (strlen($boundary) > 2)
                {
                    if (substr($boundary,0,1) == '(' &&
                        substr($boundary,-1) == ')')
                        $boundary       = substr($boundary, 1, strlen($boundary) - 2);
                    $boundary       = explode('),(', $boundary);
                    $newBoundary    = '';
                    $comma          = '';
                    foreach($boundary as $pointstr)
                    {
                        $point          = explode(',',$pointstr);
                        $newBoundary    .= $comma . '(' .
                            number_format(floatval($point[0]),6) . ',' .
                            number_format(floatval($point[1]),6) . ')';
                        $comma          = ',';
                    }
                    $boundary           = $newBoundary;
                }
                return $boundary;
            }

            default:
            {       // other fields
                return parent::get($fieldLc);
            }       // other fields
        }           // act on specific field names
    }       // Location::get

    /********************************************************************
     *  function Location::getId                                        *
     *                                                                  *
     *  Get the unique numeric identifier of the location.              *
     *  Synonym for Location::getIdlr.                                  *
     ********************************************************************/
    function getId()
    {
        $idlr       = $this->row['idlr'];
        if ($this->needInsert && $idlr == 0)
        {
            error_log("Location::getId: " . __LINE__ . 
                            " called for unsaved record returns 0");
            $trace  = debug_backtrace();
            foreach($trace as $entry)
                error_log("Location:getId: called from {$entry['file']}:{$entry['line']}"); 
        }
        return $idlr;
    }       // Location::getId

    /********************************************************************
     *  function Location::getIdlr                                      *
     *                                                                  *
     *  Get the unique numeric identifier of the location.              *
     ********************************************************************/
    function getIdlr()
    {
        $idlr       = $this->row['idlr'];
        if ($this->needInsert && $idlr == 0)
            error_log("Location.inc: " . __LINE__ . 
                "Location::getIdlr called for new record");
        return $idlr;
    }       // Location::getIdlr

    /********************************************************************
     *  function Location::getName                                      *
     *                                                                  *
     *  Get the name of the location.                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function getName($options   = null)
    {
        return $this->row['location'];
    }       // Location::getName

    /********************************************************************
     *  function Location::toString                                     *
     *                                                                  *
     *  Get the name of the location.                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function toString()
    {
        return $this->row['location'];
    }       // Location::toString

    /********************************************************************
     *  function Location::__toString                                   *
     *                                                                  *
     *  Get the name of the location.                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function __toString()
    {
        if (array_key_exists('location', $this->row) &&
            is_string($this->row['location']))
            return $this->row['location'];
        else
            return "";
    }       // Location::__toString

    /********************************************************************
     *  function Location::getLatitude                                  *
     *                                                                  *
     *  Get the latitude portion of the location.                       *
     *  The internal xDDMMSS.ssss format is translated to the external  *
     *  xDD.dddddd format.                                              *
     *                                                                  *
     *  Returns:                                                        *
     *      latitude in format xDD.dddd where:                          *
     *          x       is '-' for southern latitudes, omitted for      *
     *                  northern latitudes                              *
     *          DD.dddd     is the latitude in degrees as a real number *
     ********************************************************************/
    function getLatitude()
    {
        $value      = $this->row['latitude'];
        $negative   = $value < 0;
        $uvalue     = abs($value);
        if ($uvalue > 900000.0)
            throw new Exception("Location:getLatitude: " .
                        "internal logic error value $value out of range");
        $ddd        = floor($uvalue/10000); // degrees
        $uvalue     = $uvalue - ($ddd * 10000);
        $mm     = floor($uvalue/100);   // minutes
        $ss     = $uvalue - ($mm * 100);
        $uvalue     = $ddd + ($mm / 60) + ($ss / 3600);
        if ($negative)
            $value  = -$uvalue;
        else
            $value  = $uvalue;
        return $value;
    }       // Location::getLatitude

    /********************************************************************
     *  function Location::getLongitude                                 *
     *                                                                  *
     *  Get the longitude portion of the location.                      *
     *  The internal xDDDMMSS.ssss format is translated to the external *
     *  xDDD.dddddd format.                                             *
     *                                                                  *
     *  Returns:                                                        *
     *      longitude in format xDDD.dddd where:                        *
     *      x       is '-' for western longitudes, omitted for          *
     *              eastern     longitudes                              *
     *      DDD.ddd     is the longitude in degrees as a real number    *
     ********************************************************************/
    function getLongitude()
    {
        $value      = $this->row['longitude'];
        $negative   = $value < 0;
        $uvalue     = abs($value);
        if ($uvalue > 1800000.0)
            throw new Exception("Location:getLongitude: internal logic error value $value out of range");
        $ddd        = floor($uvalue/10000); // degrees
        $uvalue     = $uvalue - ($ddd * 10000);
        $mm     = floor($uvalue/100);   // minutes
        $ss     = $uvalue - ($mm * 100);
        $uvalue     = $ddd + ($mm / 60) + ($ss / 3600);
        if ($negative)
            $value  = -$uvalue;
        else
            $value  = $uvalue;
        return $value;
    }       // Location::getLongitude

    /********************************************************************
     *  function Location::getZoom                                      *
     *                                                                  *
     *  Get the map zoom level.                                         *
     *                                                                  *
     *  Returns:                                                        *
     *      Google Maps zoom level                                      *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function getZoom()
    {
        return $this->row['zoom'];
    }       // Location::getZoom

    /********************************************************************
     *  function Location::getPreposition                               *
     *                                                                  *
     *  Get the preposition portion of the location.                    *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function getPreposition()
    {
        return $this->row['preposition'];
    }       // Location::getPreposition

    /********************************************************************
     *  function Location::getShortName                                 *
     *                                                                  *
     *  Get the short name of the location.                             *
     *                                                                  *
     *  Returns:                                                        *
     *      string                                                      *
     ********************************************************************/
    function getShortName()
    {
        return $this->row['shortname'];
    }       // Location::getShortName

    /********************************************************************
     *  function Location::getNotes                                     *
     *                                                                  *
     *  Get the notes information of the Location.                      *
     *                                                                  *
     *  Returns:                                                        *
     *      string possibly containing HTML tags                        *
     ********************************************************************/
    function getNotes()
    {
        $notes              = $this->row['notes'];
        if (preg_match('/<\w/', $notes))
            return $notes;
        else
            return str_replace("\n", "<br>\n", $notes);
    }       // Location::getNotes

    /********************************************************************
     *  function Location::set                                          *
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
        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'name':
            case 'location':
            {
                return self::setName(htmlspecialchars_decode($value));
            }       // location name

            case 'latitude':
            {
                return self::setLatitude($value);
            }       // latitude

            case 'longitude':
            {
                return self::setLongitude($value);
            }       // longitude

            default:
            {       // no validation implemented yet
                return parent::set($field, $value);
            }       // no validation implemented yet
        }       // switch on field name
    }       // Location::set

    /********************************************************************
     *  function Location::setName                                      *
     *                                                                  *
     *  Set the name of the location.  As a side effect the values of   *
     *  the fields 'shortname' and 'sortedlocation' are set to          *
     *  appropriate values.                                             *
     *                                                                  *
     *  Input:                                                          *
     *      newName     string name                                     *
     ********************************************************************/
    function setName($newName)
    {
        $oldName            = $this->row['location'];
        if (array_key_exists($oldName, self::$locationNames))
        {
            unset(self::$locationNames[$oldName]);
            self::$locationNames[$newName]      = $this;
        }
        // if the short name has not been set to a different value
        // make it the same as the new location value
        if ($this->row['shortname'] == $this->row['location'])
            parent::set('shortname', $newName);

        // determine appropriate sort value
        // for lot locations, whether on urban streets or in rural concessions
        // set the sort value so that all locations on the same street or
        // concession sort together in order by lot number
        $rgxres = preg_match(self::$namePattern,
                             $newName,
                             $matches);
        if ($rgxres > 0)
        {       // street address
            $lotnum     = $matches[2];
            if (strlen($lotnum) > 5)
                $lot6   = substr($lotnum, 5, 1);
            else
                $lot6   = '';
            $con        = $matches[4];
            if (preg_match('/^con\s+[0-9]\s/', $con))
                $con    = 'con 0' . substr($con, 4);
            if (preg_match('/^lot\s+([0-9]+)(\s.*|)$/', $lotnum, $lmatch))
            {       // rural lot < 10
                $num    = $lmatch[1];
                $rest   = $lmatch[2];
                if (strlen($num) < 2)
                    $lotnum = 'lot 0' . $num . $rest;
            }       // rural lot < 10
            else
            if (ctype_digit($lotnum) && strlen($lotnum) < 3)
                $lotnum = substr('000' . $lotnum, strlen($lotnum));

            $sortedlocation = $con . $matches[5] . ', ' .
                $lotnum . ' ' . $matches[1];
            parent::set('sortedlocation',$sortedlocation);
        }       // street address
        else
            parent::set('sortedlocation', $newName);

        // determine appropriate default zoom level
        if ($this->row['zoom'] == 8 &&
            $this->row['longitude'] == 0)
        {       // coordinates not set
            if (strpos($newName,' st,') > 0 ||
                strpos($newName,' ave,') > 0 ||
                strpos($newName,' rd,') > 0)
                $depth      = 15;
            else
                $depth      = 3 + 4 * substr_count($newName, ',');
            parent::set('zoom', $depth);
        }       // coordinates not set

        // set location
        return parent::set('location', $newName);
    }       // Location::setName

    /********************************************************************
     *  function Location::setLatitude                                  *
     *                                                                  *
     *  Set the latitude portion of the location.                       *
     *  The external geo-locator format is translated to the internal   *
     *  format used by Legacy.                                          *
     *                                                                  *
     *  Input:                                                          *
     *      value       latitude in format xDD.dddd where:              *
     *          x       is '-' for southern latitudes, omitted for      *
     *                  northern latitudes                              *
     *          DD      is the integral degree portion of the latitude  *
     *          DD.dddd is the latitude in degrees and decimal fraction *
     *                                                                  *
     *  Returns:                                                        *
     *      Former value in format xDD.dddd                             *
     ********************************************************************/
    function setLatitude($value)
    {
        if (!is_numeric($value))
            return $this->getLatitude();
        $value      = (double)$value;
        $negative   = $value < 0;
        $value      = abs($value);
        if ($value > 90.0)
            throw new Exception("Location:setLatitude: " .
                            "value $value out of range");
        $ddd        = floor($value);    // degrees
        $value      = ($value - $ddd) * 60.0;
        $mm     = floor($value);    // minutes
        $ss     = ($value - $mm) * 60.0;// seconds and fraction
        $value      = ($ddd * 10000) + ($mm * 100) + $ss;
        if ($negative)
            $value  = -$value;
        $oldvalue   = $this->getLatitude();
        parent::set('latitude', $value);
        return $oldvalue;
    }       // Location::setLatitude

    /********************************************************************
     *  function Location::setLongitude                                 *
     *                                                                  *
     *  Set the longitude portion of the location.                      *
     *  The external geo-locator format is translated to the internal   *
     *  format used by Legacy.                                          *
     *                                                                  *
     *  Input:                                                          *
     *      value       longitude in format xDDD.dddd where:            *
     *          x       is '-' for western longitudes (New World),      *
     *                  omitted for eastern longitudes (Old World)      *
     *          DDD     is the integral degree portion of the longitude *
     *          DDD.dddd is the longitude in degrees and decimal        *
     *                  fraction                                        *
     *                                                                  *
     *  Returns:                                                        *
     *      Former value in format xDDD.dddd                            *
     ********************************************************************/
    function setLongitude($value)
    {
        if (!is_numeric($value))
            return $this->getLongitude();
        $value      = (double)$value;
        $negative   = $value < 0;
        $value      = abs($value);
        if ($value > 180.0)
            throw new Exception("Location:setLongitude: " .
                            "value $value out of range");
        $ddd        = floor($value);    // degrees
        $value      = ($value - $ddd) * 60.0;
        $mm     = floor($value);    // minutes
        $ss     = ($value - $mm) * 60.0;// seconds and fraction
        $value      = ($ddd * 10000) + ($mm * 100) + $ss;
        if ($negative)
            $value  = -$value;
        $oldvalue   = $this->getLongitude();
        parent::set('longitude', $value);
        return $oldvalue;
    }       // Location::setLongitude

    /********************************************************************
     *  function Location::toXml                                        *
     *                                                                  *
     *  Emit the contents of the object as XML.                         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $top        tag name as a string for the tag enclosing the  *
     *                  values.                                         *
     *                  If null, do not enclose the individual values   *
     *                  inside a root node.                             *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/
    function toXml($top     = 'location',
                   $print   = true,
                   $options = 0)
    {
        // ensure that there is a label to use for the top element
        if (!is_string($top) || strlen($top) == 0)
            $top    = $this->table;

        $idlr   = $this->row['idlr'];
        if (is_null($top))
            $retval = '';
        else
            $retval = "<$top idlr='$idlr'>\n";
        if (is_array($this->row))
        {
        foreach($this->row as $key => $value)
        {
            $retval .="\t<$key>";
            switch($key)
            {       // act on specific fields
                case 'latitude':
                {
                    $retval .= $this->getLatitude();
                    break;
                }   // latitude

                case 'longitude':
                {
                    $retval .= $this->getLongitude();
                    break;
                }   // longitude

                default:
                {
                    $retval .= htmlentities($value,ENT_XML1);
                    break;
                }   // most fields
            }       // act on specific fields
            $retval .= "</$key>\n";
        }
        }       // have array
        else
        {
            $retval .= "<msg>this->row is not an array</msg>\n";
            $retval .= print_r($this, true);
        }
        if ($top !== null)
            $retval .="</$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }       // Location::toXml

}       // class Location
