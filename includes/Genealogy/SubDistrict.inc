<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  SubDistrict.inc                                                     *
 *                                                                      *
 *  Definition of a class representing a single enumeration division    *
 *  from a census.  This class provides                                 *
 *  access to the information in a row of the table SubDistricts        *
 *  and the associated information in the table Districts.              *
 *                                                                      *
 *  History:                                                            *
 *      2013/07/14      created                                         *
 *      2013/08/09      base class renamed to Record                    *
 *      2013/08/17      add support for 1921 census                     *
 *      2013/08/21      add remarks column                              *
 *      2013/08/28      add 1861 census images                          *
 *      2013/08/30      use JPEG images for 1901 census                 *
 *      2013/09/11      use JPEG images for 1911 census                 *
 *      2013/11/22      throw exception in constructor if no database   *
 *                      server connection                               *
 *      2014/04/27      validate census identifier                      *
 *      2014/09/20      use class District                              *
 *      2014/09/22      use preferred URL for LAC images                *
 *                      improve sort order for numeric sub-districts    *
 *                      and divisions                                   *
 *                      support an array of districts in call to        *
 *                      getSubDistricts                                 *
 *      2014/12/28      redirect diagnostic output to $warn             *
 *      2014/12/29      if instance of District is passed to static     *
 *                      function getSubDistricts, the census id is      *
 *                      from the instance is also used in selection     *
 *                      add method getPages                             *
 *      2015/03/17      method getSubDistricts set fcount incorrectly   *
 *      2015/05/23      improve exceptions                              *
 *      2015/06/02      use new global census table                     *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/11/17      update population in District table when saving *
 *                      add method getDistrict                          *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2016/12/14      use prepared statement in constructor to        *
 *                      better handle bad parameters                    *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/08/06      missing globals for $connection, $debug, $warn  *
 *                      in method set                                   *
 *      2017/08/22      correct creation of new division within         *
 *                      existing subdistrict                            *
 *      2017/09/27      add common field name translation table         *
 *                      use prepared statements for update              *
 *                      permit altering all identification fields       *
 *                      as long as it does not create a duplicate       *
 *      2017/11/17      method getPages changed to return a RecordSet   *
 *                      instead of an array, and to return a full set   *
 *                      of Page records even if the database has not    *
 *                      been filled in yet.                             *
 *      2017/11/21      use prepared statements                         *
 *      2017/12/08      getPages didn't work if some pages initialized  *
 *      2017/01/24      support passing Census object to constructor    *
 *      2017/02/13      getting all of the Page instances for the       *
 *                      SubDistrict is moved into class PageSet         *
 *      2018/03/12      throw exception if constructor called with      *
 *                      array of district ids                           *
 *      2018/05/11      return 0 or 1 for numeric fields that are       *
 *                      empty or null in the database                   *
 *      2018/06/06      handle SD_ID array value better                 *
 *      2018/11/07      census parameter optional if instance of        *
 *                      District passed to constructor                  *
 *                      do not include division in constructor if       *
 *                      it is empty                                     *
 *      2019/12/01      if there are multiple matches to parameters     *
 *                      return new record                               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2020/06/07      notify user and administrator that the initial  *
 *                      frame number field has not been initialized.    *
 *                      correct handling of explicit no division id     *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc' ;
require_once __NAMESPACE__ . '/Census.inc' ;
require_once __NAMESPACE__ . '/District.inc' ;
require_once __NAMESPACE__ . '/PageSet.inc' ;

/************************************************************************
 *  class SubDistrict                                                   *
 *                                                                      *
 *  Definition of a class recording information about an enumeration    *
 *  division from a census.                                             *
 *                                                                      *
 ************************************************************************/

class SubDistrict extends Record
{
    /********************************************************************
     *  SubDistrict::$initRow                                           *
     *                                                                  *
     *  Default initial row for a record in table SubDistricts          *
     ********************************************************************/
    protected static $initRow   = array(
                            'sd_census'         => 'CA1881',
                            'sd_distid'         => 0,
                            'sd_id'             => '',
                            'sd_div'            => '',
                            'sd_sched'          => '1',
                            'sd_name'           => '',
                            'sd_pages'          => 50,
                            'sd_page1'          => 1,
                            'sd_population'     => 0,
                            'sd_lacreel'        => '',
                            'sd_ldsreel'        => 0,
                            'sd_imagebase'      => 0,
                            'sd_relframe'       => 0,
                            'sd_framect'        => 0,
                            'sd_bypage'         => 1,
                            'sd_remarks'        => '');

    /********************************************************************
     *  SubDistrict::$translate                                         *
     *                                                                  *
     *  Define alternate field names.                                   *
     ********************************************************************/
    protected static $translate = array(
                        'census'            => 'sd_census',
                        'censusid'          => 'sd_census',
                        'distid'            => 'sd_distid',
                        'dist'              => 'sd_distid',
                        'district'          => 'sd_distid',
                        'id'                => 'sd_id',
                        'sdid'              => 'sd_id',
                        'subdist'           => 'sd_id',
                        'subdistid'         => 'sd_id',
                        'subdistrict'       => 'sd_id',
                        'div'               => 'sd_div',
                        'division'          => 'sd_div',
                        'sched'             => 'sd_sched',
                        'schedule'          => 'sd_sched',
                        'name'              => 'sd_name',
                        'pages'             => 'sd_pages',
                        'page1'             => 'sd_page1',
                        'population'        => 'sd_population',
                        'lacreel'           => 'sd_lacreel',
                        'ldsreel'           => 'sd_ldsreel',
                        'imagebase'         => 'sd_imagebase',
                        'relframe'          => 'sd_relframe',
                        'framect'           => 'sd_framect',
                        'bypage'            => 'sd_bypage',
                        'remarks'           => 'sd_remarks');

    /********************************************************************
     *  SubDistrict::$defaultOrder                                      *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = "SD_census,SD_distid,LPAD(SD_id,4,' '),LPAD(SD_div,4,' '),SD_sched";

    /********************************************************************
     *  SubDistrict::$info                                              *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'     => 'SubDistricts',
                'name'      => 'SubDistricts',
                'prime'     => array('sd_census', 'sd_distid', 'sd_id', 'sd_div', 'sd_sched'),
                'srvmg'     => false,
                'fldcount'  => 16,
                'order'     => 'SD_census,SD_distid,LPAD(SD_id,4,\' \'),LPAD(SD_div,4,\' \'),SD_sched',
                'classname' => 'SubDistrict');

    /********************************************************************
     *  SubDistrict::$formerCensus                                      *
     *  SubDistrict::$formerDistrict                                    *
     *                                                                  *
     *  To avoid unnecessarily invoking the constructors for Census     *
     *  and District the class remembers the last instances that        *
     *  it created.                                                     *
     ********************************************************************/
    private static  $formerCensus       = null;
    private static  $formerDistrict     = null;

    /********************************************************************
     *  SubDistrict->census                                             *
     *                                                                  *
     *  Instance of class Census containing associated census info      *
     ********************************************************************/
    private $census;

    /********************************************************************
     *  SubDistrict->district                                           *
     *                                                                  *
     *  Instance of class District containing associated district info  *
     ********************************************************************/
    private $district;

    /********************************************************************
     *  SubDistrict->prevSd                                             *
     *  SubDistrict->prevDiv                                            *
     *  SubDistrict->prevSubDistrict                                    *
     *  SubDistrict->nextSd                                             *
     *  SubDistrict->nextDiv                                            *
     *  SubDistrict->nextSubDistrict                                    *
     *                                                                  *
     *  Cache identifiers of the preceding and following enumeration    *
     *  divisions within the current district.  These are set by        *
     *  method getPrevSearch and getNextSearch and retrieved by         *
     *  methods getPrevSd, getPrevDiv, getPrevSubDistrict,              *
     *  getNextSd, getNextDiv, and getNextSubDistrict.                  *
     ********************************************************************/
    private $prevSearch         = null;
    private $prevSd             = null;
    private $prevDiv            = null;
    private $prevSubDistrict    = null;
    private $nextSearch         = null;
    private $nextSd             = null;
    private $nextDiv            = null;
    private $nextSubDistrict    = null;

    /********************************************************************
     * function SubDistrict::__construct                                *
     *                                                                  *
     *  Construct an instance of SubDistrict based upon the             *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array of parameters              *
     *                  identifying the specific row.                   *
     *                  This may be a row retrieved from the database.  *
     *                  to obtain a specific instance:                  *
     *  new SubDistrict(array('sd_census'   => $censusId,               *
     *                        'sd_distId'   => $distId ,                *
     *                        'sd_Id'       => $sdId,                   *
     *                        'sd_div'      => $division,               *
     *                        'sd_sched'    => $sched);                 *
     *                  If the value of sd_distId is an instance of     *
     *                  class District then the parameter sd_census     *
     *                  is ignored if present and may be omitted        *
     *                                                                  *
     *  Returns object containing a description of a subdistrict        *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        $needInsert             = false;

        $this->table            = 'SubDistricts';
        $this->census           = null;
        $this->district         = null;
        $censusId               = self::$initRow['sd_census'];
        $distId                 = self::$initRow['sd_distid'];
        $sdId                   = self::$initRow['sd_id'];
        $division               = self::$initRow['sd_div'];
        $sched                  = self::$initRow['sd_sched'];

        if ($debug)
            $warn   .= "<p>SubDistrict::__construct(" . var_export($parms, true) . "<p>\n";
        foreach($parms as $field => $value)
        {                           // process parameters
            $fieldLc            = strtolower($field);
            if (array_key_exists($fieldLc, self::$translate))
                $fieldLc        = self::$translate[$fieldLc];
            switch($fieldLc)
            {                       // act on specific parameters
                case 'sd_census':
                {                   // census id
                    if ($value instanceof Census)
                    {               // object
                        $this->census           = $value;
                        $censusId               = $this->census['censusid'];
                        self::$formerCensus     = $this->census;
                    }               // object
                    else
                    {               // string
                        $censusId               = $value;
                        $formerCensus           = self::$formerCensus;
                        if ($formerCensus &&
                            $censusId == $formerCensus['censusid'])
                        {
                            $this->census       = $formerCensus;
                        }
                        else
                        {           // need to construct
                            $parms      = array('censusid' => $censusId,
                                                'collective' => 0);
                            $this->census       = new Census($parms);
                            self::$formerCensus = $this->census;
                        }           // need to construct
                    }
                    break;
                }                   // census id

                case 'sd_distid':
                {                   // district id
                    if ($value instanceof District)
                    {
                        $this->district         = $value;
                        self::$formerDistrict   = $value;
                        $distId                 = $this->district['id'];
                        $this->census           = $this->district->getCensus();
                        self::$formerCensus     = $this->census;
                        $censusId               = $this->census['censusid'];
                    }
                    else
                    if (is_array($value))
                    {
                        $this->msg      .= "SubDistrict::__construct: " .
                                            " sd_distid is " .
                                            print_r($value, true);
                        $distId                 = $value;
                    }
                    else
                    {
                        $distId                 = $value;
                        $formerDistrict         = self::$formerDistrict;
                        if ($formerDistrict &&
                            $distId == $formerDistrict['id'])
                            $this->district     = $formerDistrict;
                    }
                    break;
                }                   // district id

                case 'sd_id':
                {                   // sub-district id
                    if (is_array($value))
                    {
                        if (count($value) == 1)
                            $sdId   = $value[0];
                        else
                            $this->msg  .= "SubDistrict.inc: " .
                                        " SD_ID=" . print_r($value, true);
                    }
                    else
                        $sdId                   = $value;
                    break;
                }                   // sub-district id

                case 'sd_div':
                {                   // enumeration division
                    $division                   = $value;
                    break;
                }                   // enumeration division

                case 'sd_sched':
                {                   // census schedule id
                    $sched                      = $value;
                    break;
                }                   // census schedule id

            }                       // act on specific parameters
        }                           // process parameters

        // query the database for district information
        $this->district         = $this->getDistrict($distId);

        // construct the query for an existing record

        if (!is_null($division))
        {                   // division included in search
            $sdQuery    = "SELECT * FROM SubDistricts " .
                                "WHERE SD_Census=:censusId AND " .
                                  "SD_DistId=:distId AND " .
                                  "SD_Id=:sdId AND " .
                                  "SD_Div=:division AND " .
                                  "SD_Sched=:sched";
            $sqlParms   = array('censusId'  => $censusId,
                                'distId'    => $distId ,
                                'sdId'      => $sdId,
                                'division'  => $division,
                                'sched'     => $sched);
        }                   // division included in search
        else
        {                   // division not included in search
            $sdQuery    = "SELECT * FROM SubDistricts " .
                                "WHERE SD_Census=:censusId AND " .
                                  "SD_DistId=:distId AND " .
                                  "SD_Id=:sdId AND " .
                                  "SD_Sched=:sched";
            $sqlParms   = array('censusId'  => $censusId,
                                'distId'    => $distId ,
                                'sdId'      => $sdId,
                                'sched'     => $sched);
        }                   // division not included in search

        // query the database for sub-district information
        $stmt       = $connection->prepare($sdQuery);
        $queryText  = debugPrepQuery($sdQuery, $sqlParms);
        if ($stmt->execute($sqlParms))
        {       // success
            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($debug)
            {
                $warn   .= "<p>SubDistrict.inc: " . __LINE__.
                    " query='$queryText'</p>\n";
                if (count($result) != 1)
                {
                    $warn   .= "<p>numRows=" . count($result) . "</p>\n";
                }
            }

            if (count($result) == 1)
            {       // existing record
                $dbrow          = $result[0];
                $needInsert     = false;
            }       // existing record
            else
            {       // create a new record
                // set defaults
                $dbrow              = self::$initRow;
                $dbrow['sd_census'] = $censusId;
                $dbrow['sd_distid'] = $distId;
                $dbrow['sd_id']     = $sdId;
                if (is_null($division) || strlen($division) == 0)
                {
                    $prevQuery  = "SELECT * FROM SubDistricts " .
                                    "WHERE SD_Census=:censusId AND " .
                                      "SD_DistId=:distId AND " .
                                      "SD_Id<:sdId AND " .
                                      "SD_Sched=:sched " .
                                      "LIMIT 1";
                    unset($sqlParms['division']);
                }
                else
                {
                    $dbrow['sd_div']    = $division;
                    $prevQuery  = "SELECT * FROM SubDistricts " .
                                    "WHERE SD_Census=:censusId AND " .
                                      "SD_DistId=:distId AND " .
                                      "SD_Id=:sdId AND " .
                                      "SD_Div<:division AND " .
                                      "SD_Sched=:sched " .
                                   "LIMIT 1";
                }
                $dbrow['sd_name']   = "District $distId Sub-District $sdId";

                // fill in info from preceding record
                $stmt               = $connection->prepare($prevQuery);
                $prevQueryText      = debugPrepQuery($prevQuery, $sqlParms);
                if ($stmt->execute($sqlParms))
                {       // succeeded
                    $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($debug)
                    {
                        $warn   .= "<p>SubDistrict.inc: " . __LINE__.
                               " query='$prevQueryText'</p>\n";
                    }
                    if (count($result) > 0)
                    {   // previous record
                        $prevrow                = $result[0];
                        $dbrow['sd_name']       = $prevrow['sd_name'];
                        $dbrow['sd_pages']      = $prevrow['sd_pages'];
                        $dbrow['sd_page1']      = $prevrow['sd_page1'];
                        $dbrow['sd_population'] = $prevrow['sd_population'];
                        $dbrow['sd_lacreel']    = $prevrow['sd_lacreel'];
                        $dbrow['sd_ldsreel']    = $prevrow['sd_ldsreel'];
                        $dbrow['sd_imagebase']  = $prevrow['sd_imagebase'];
                        $dbrow['sd_relframe']   = $prevrow['sd_relframe'];
                        $dbrow['sd_framect']    = $prevrow['sd_framect'];
                        $dbrow['sd_bypage']     = $prevrow['sd_bypage'];
                    }   // previous record
                }       // succeeded
                else
                {       // error performing query
                    if ($stmt->errorInfo()[0] == 'HY093')
                    {
                        print "<p>sql='$prevQuery', sqlParms=" .
                                    var_export($sqlParms,true) . 
                                    ', error=' .
                                    var_export($stmt->errorInfo(),true) .
                                    "</p>\n";
                    }
                    else
                    {
                        print "<p>sql='$prevQueryText'</p>\n";
                    }
                    $this->msg  .=" '$prevQueryText' " .
                                        var_export($stmt->errorInfo(),true) . '.';
                    $dbrow      = self::$initRow;
                }       // error performing query
                $needInsert     = true;
            }       // create new record
        }       // success
        else
        {       // error performing query
            $this->msg          .= " '$queryText' " .
                                    print_r($stmt->errorInfo(),true);
            $dbrow              = self::$initRow;
        }       // error performing query

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'SubDistricts');
        $this->needInsert   = $needInsert;

        // apply any updates passed in the parameters
        foreach($parms as $field => $value)
        {       // check for other initialization
            $fieldLc    = strtolower($field);
            if (array_key_exists($fieldLc, self::$translate))
                $fieldLc    = self::$translate[$fieldLc];
            switch($fieldLc)
            {       // act on specific parameters
                case 'sd_census':
                case 'sd_distid':
                case 'sd_id':
                case 'sd_div':
                case 'sd_sched':
                {
                    // identification fields already handled
                    break;
                }

                default:
                {
                    if (array_key_exists($field, $dbrow))
                        parent::set($field, $value);
                    break;
                }
            }       // act on specific parameters
        }       // check for other initialization

        // diagnostic output if debug is set
        $this->dump('SubDistrict Record constructed:');
    }       // SubDistrict::__construct

    /********************************************************************
     * function SubDistrict:get                                         *
     *                                                                  *
     *  Obtain the current value of a field in the object.              *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to retrieve                       *
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
        $fieldLc        = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        if (substr($fieldLc,0,2) == 'd_')
        {               // accept district field names
            return $this->district[$fieldLc];
        }               // accept district field names
        else
        {               // other fields
            switch($fieldLc)
            {
                case 'sd_census':
                case 'sd_distid':
                case 'sd_id':
                case 'sd_div':
                case 'sd_sched':
                case 'sd_name':
                case 'sd_lacreel':
                case 'sd_remarks':
                {
                    return $this->row[$fieldLc];
                }

                case 'sd_pages':
                case 'sd_page1':
                case 'sd_bypage':
                {
                    if ($this->row[$fieldLc] == '' ||
                        $this->row[$fieldLc] == 0 ||
                        is_null($this->row[$fieldLc]))
                        return 1;
                    else
                        return $this->row[$fieldLc];
                }

                case 'sd_ldsreel':
                case 'sd_imagebase':
                case 'sd_relframe':
                case 'sd_framect':
                {
                    if ($this->row[$fieldLc] == '' ||
                        is_null($this->row[$fieldLc]))
                        return 0;
                    else
                        return $this->row[$fieldLc];
                }

                default:
                    return parent::get($fieldLc);
            }
        }               // other fields

    }       // SubDistrict::get

    /********************************************************************
     * function SubDistrict:set                                         *
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
            case 'sd_census':
            case 'sd_distid':
            case 'sd_id':
            case 'sd_div':
            case 'sd_sched':
            {       // unique key values, check for duplicate
                if ($value != $this->row[$fieldLc])
                {   // value changed
                    $getParms   = array(
                            'sd_census' => $this->row['sd_census'],
                            'sd_distid' => $this->row['sd_distid'],
                            'sd_id'     => $this->row['sd_id'],
                            'sd_div'    => $this->row['sd_div'],
                            'sd_sched'  => $this->row['sd_sched']);
                    $getParms[$fieldLc]     = $value;
                    $temp   = new SubDistrict($getParms);
                    if ($temp->isExisting())
                    {
                        throw new Exception("SubDistrict::set: " . __LINE__ .
           " attempt to change '$field' would result in duplicate record.");
                    }
                }   // value changed
                return parent::set($fieldLc, $value);
            }       // sd_id

            default:
            {       // no validation implemented yet
                return parent::set($fieldLc, $value);
            }       // no validation implemented yet
        }           // switch on field name
    }       // SubDistrict::set

    /********************************************************************
     * function SubDistrict::save                                       *
     *                                                                  *
     *  Save changes made to the SubDistrict record into the            *
     *  database.                                                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        If true diagnostic output is emitted in XML     *
     *                  format. If a string use it as top tag name      *
     ********************************************************************/
    function save($xml)
    {
        global  $debug;
        global  $warn;
        global  $userid;
        global  $connection;

        $updatePopulation   = false;

        // determine top level tag name for XML output
        $indent             = '';
        if (is_string($xml))
        {                   // string tag name
            $matches        = array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent     = $matches[1];
                $xml        = $matches[2];
            }
            $tagname        = $xml;
            $xml            = true;
        }                   // string tag name
        else
        if ($xml)
        {                   // true
            $indent         = "    ";
            $tagname        = 'cmd';    // default
        }                   // true

        if ($this->needInsert)
        {                   // create new record
            $fldnames               = '';
            $comma                  = '';
            $sqlParms               = array();
            $values                 = '';
            foreach($this->row as $fld => $value)
            {               // loop through all fields in record
                $fldnames           .= $comma . "`$fld`";
                $values             .= $comma . ":$fld";
                $sqlParms[$fld]     = $value;
                $comma              = ", ";
            }               // loop through all fields in record

            // construct the SQL INSERT command
            $insert = "INSERT INTO SubDistricts ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt                   = $connection->prepare($insert);
            $insertTxt              = debugPrepQuery($insert, $sqlParms);
            if ($stmt->execute($sqlParms))
            {               // success
                $count              = $stmt->rowCount();
                if ($xml)
                    print "$indent<$tagname result='$count'>$insertTxt</$tagname>\n";
                if ($debug)
                    $warn           .= "<p>sql='$insertTxt'" .
                                        ", count=$count</p>\n";

                $this->changed      = array();  // reset
                $this->needInsert   = false;

                // log the update
                $this->log($insert, $sqlParms);

                // update population column of associated District
                $updatePopulation   = true;
            }               // success
            else
            {               // error inserting record
                $this->msg          .= "SubDistrict::save: " . __LINE__ .
                                        " '$insertTxt', " . ': ' .
                                        print_r($stmt->errorInfo(),true) .
                                        ". ";
            }               // error inserting record
        }                   // create new record
        else
        {                   // update existing record
            $updatePopulation       = false;
            $update                 = "UPDATE SubDistricts SET ";
            $set                    = '';
            $sqlParms               = array();
            $comma                  = '';
            foreach($this->changed as $fld => $value)
            {               // loop through all changed fields in record
                if ($fld == 'sd_population')
                    $updatePopulation   = true;
                $set                .= $comma . "`$fld`=:$fld";
                $sqlParms[$fld]     = $value;
                $comma              = ', ';
            }               // loop through all changed fields in record
            $this->changed          = array();  // reset

            if (strlen($set) > 0)
            {               // changes made
                // construct the where clause
                $sqlParms['fcensusId']  = $this->former['sd_census'];
                $sqlParms['fdistId']    = $this->former['sd_distid'];
                $sqlParms['fsdId']      = $this->former['sd_id'];
                $sqlParms['fdivision']  = $this->former['sd_div'];

                $update .= $set . ' WHERE SD_Census=:fcensusId AND ' .
                                 'SD_DistId=:fdistId AND '.
                                 'SD_Id=:fsdId AND ' .
                                 'SD_Div=:fdivision';

                // perform the update
                $stmt               = $connection->prepare($update);
                $updateText         = debugPrepQuery($update, $sqlParms);
                if ($stmt->execute($sqlParms))
                {           // success
                    $count          = $stmt->rowCount();
                    if ($xml)
                        print "$indent<$tagname count='$count'>$updateText</$tagname>\n";
                    if ($debug)
                        $warn       .= "<p>sql='$updateText'" .
                                        ", count=$count</p>\n";

                    // log the update
                    $this->log($update, $sqlParms);
                }           // success
                else
                {           // error updating record
                    $updatePopulation   = false;
                    $this->msg          .= "SubDistrict::save: " . __LINE__ .
                                            " '$updateText': " .
                                            print_r($stmt->errorInfo(),true) .
                                            ". ";
                }           // error updating record
            }               // changes made
        }                   // update existing record

        if ($updatePopulation)
        {                   // update population column of associated District
            $this->district->synchPopulation();
            $this->district->save($xml);
        }                   // update population column of associated District
    }       // SubDistrict::save

    /********************************************************************
     * function SubDistrict::delete                                     *
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
        global  $warn;
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

        // to simplify expressions
        if (!$this->needInsert)
        {       // have existing record
            $delete = 'DELETE FROM SubDistricts WHERE ' .
                                'SD_Census=:censusId AND ' .
                                'SD_DistId=:distId AND ' .
                                'SD_Id=:sdId AND ' .
                                'SD_Div=:division';
            $sqlParms   = array('censusId'  => $this->row['sd_census'],
                            'distId'    => $this->row['sd_distid'],
                            'sdId'      => $this->row['sd_id'],
                            'division'  => $this->row['sd_div']);

            $stmt   = $connection->prepare($delete);
            $deleteText = debugPrepQuery($delete, $sqlParms);
            if ($stmt->execute($sqlParms))
            {       // success
            }       // success
            else
            {       // error updating record
                error_log("SubDistrict::delete: " . __LINE__ . " $deleteText, ".
                            print_r($stmt->errorInfo(),true) . "\n");
                return false;
            }       // error updating record

            $count      = $stmt->rowCount();
            if ($xml)
                print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
            else
            if ($debug)
                $warn   .= "<p>SubDistrict::delete: " . __LINE__ .
                            " '$deleteText', count=$count</p>\n";

            // log the update
            logSqlUpdate($delete,
                         $sqlParms,
                         0,
                         json_encode($this->row),
                         '');

            // update the memory copy of the record to reflect that it
            // no longer represents a database record
            $this->needInsert   = true;

            return $count;  // number of records deleted
        }       // have key value

        // record never written to database
        return 0;   // nothing to delete
    }       // SubDistrict::delete

    /********************************************************************
     * function SubDistrict::getCensus                                  *
     *                                                                  *
     *  This factory method returns the instance of class Census        *
     *  associated with this SubDistrict.                               *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of class Census                                    *
     ********************************************************************/
    function getCensus()
    {
        return $this->census;
    }       // function SubDistrict::getCensus

    /********************************************************************
     * function SubDistrict::getDistrict                                *
     *                                                                  *
     *  This factory method returns the instance of class District      *
     *  associated with this SubDistrict.                               *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of class District                                  *
     ********************************************************************/
    function getDistrict($distId = null)
    {
        if (is_null($distId))
            $distId                     = $this->row['sd_distid'];
        if (is_null($this->district))
        {                           // not set from parameters
            $formerDistrict             = self::$formerDistrict;
            if ($formerDistrict &&
                $distId == $formerDistrict['id'])
            {                       // avoid creating new instance
                $this->district         = $formerDistrict;
            }                       // avoid creating new instance
            else
            {                       // create new District
                $distParms              = array('d_census'  => $this->census,
                                                'd_id'      => $distId);
                $this->district         = new District($distParms);
                self::$formerDistrict   = $this->district;
            }                       // create new District
        }                           // not set from parameters
        return $this->district;
    }       // function SubDistrict::getDistrict

    /********************************************************************
     * function SubDistrict::getDefaultImage                            *
     *                                                                  *
     *  If the Page table does not identify the URL of the image of the *
     *  original census image, then this method uses information from   *
     *  the SubDistrict entry to determine the most probable URL for    *
     *  that image.                                                     *
     *                                                                  *
     *  Parameters:                                                     *
     *      $page       Page number in division                         *
     *                                                                  *
     *  Returns:                                                        *
     *      String containing a URL or an empty string.                 *
     ********************************************************************/
    function getDefaultImage($page)
    {
        global $warn;

        $censusId       = $this->row['sd_census'];
        $distId         = $this->row['sd_distid'];
        $subdistId      = $this->row['sd_id'];
        $div            = $this->row['sd_div'];
        $censusYear     = substr($censusId, 2);
        $imageBase      = $this->row['sd_imagebase'];
        $relFrame       = $this->row['sd_relframe'];
        if ($relFrame == 0)
        {
            $warn       .= "<p>SubDistrict::getDefaultImage: the initial frame number field in the SubDistrict table for Census=$censusId District=$distId Sub-District=$subdistId Division=$div has not been initialized.</p>\n";
            error_log("SubDistrict::getDefaultImage: the initial frame number field in the SubDistrict table for Census=$censusId District=$distId Sub-District=$subdistId Division=$div has not been initialized.\n");
        }
        $page1          = $this->row['sd_page1'];
        $pages          = $this->row['sd_pages'];
        $bypage         = $this->row['sd_bypage'];
        $website        = 'https://data2.collectionscanada.ca/';

        $lastpage       = $page1 + $bypage * ($pages - 1);
        if ($page < $page1 ||
            $page > $lastpage ||
            (($bypage == 2) && (($page - $page1) % $bypage) != 0))
        {
            $warn       .= "<p>SubDistrict::getDefaultImage: $page is not a valid page number within SubDistrict ($censusId,$distId,$subdistId,$div)</p>\n";
            $warn       .= "<p>\$page1=$page1, \$pages=$pages, \$bypage=$bypage, \$lastpage=$lastpage remainder=" . (($page - $page1) % $bypage) . "</p>\n";
            return '';
        }

        // note that this method is called from the Page constructor and
        // therefore cannot call the Page constructor

        switch($censusYear)
        {       // switch on census year

            case 1851:
            {
                $frm    = floor(($page - $page1) * 2 / $bypage) + $relFrame;
                $website    = "https://central.bac-lac.gc.ca/";
                $image  = $website .
                            '.item/%3Fapp=Census1851%26op=%26id=%26img=e' .
                            substr('000000000', 0, 9 - strlen($frm)) .
                            $frm;
                break;
            }   // 1851 census

            case 1861:
            {
                $frm    = floor(($page - $page1) * 2 / $bypage) + $relFrame;
                $image  = $website . '1861/jpg/' .
                            substr('0000000', 0, 7 - strlen($imageBase)) .
                            $imageBase .  '_' .
                            substr('00000', 0, 5 - strlen($frm)) .
                            $frm .
                            '.jpg';
                break;
            }   // 1861 census

            case 1871:
            {
                $frm    = $relFrame + floor(($page - $page1 + 1)/2);
                $image  = $website . '1871/jpg/' .
                            substr('0000000', 0, 7 - strlen($imageBase)) .
                            $imageBase .  '_' .
                            substr('00000', 0, 5-strlen($frm)) .
                            $frm . '.jpg';
                break;
            }   // 1871 census

            case 1881:
            {
                $frm      = floor(($page - $page1 + 1)/2) + $relFrame;
                $image  = $website . 'e/e' .
                            substr('000', 0, 3 - strlen($imageBase)) .
                            $imageBase .  '/e' .
                            substr('00000000', 0, 9 - strlen($frm)) .
                            $frm .
                            '.jpg';
                break;
            }   // 1881 census

            case 1891:
            {
                $frm    = floor(($page - $page1 + 1)/2) + $relFrame;
                $image    = $website . '1891/jpg/30953_' .
                                $imageBase . '-' .
                                substr('00000', 0, 5 - strlen($frm)) .
                                $frm .
                                '.jpg';
                break;
            }   // 1891 census

            case 1901:
            {
                $frm    = $page - $page1 + $relFrame;
                $image  = $website . '1901/z/z' .
                                substr('000', 0, 3 - strlen($imageBase)) .
                                $imageBase .
                                '/jpg/z' .
                                substr('000000000', 0, 9 - strlen($frm)) .
                                $frm .
                              '.jpg';
                break;
            }   // 1901 census

            case 1906:
            {
                $frm    = floor(($page - $page1)/2) + $relFrame;
                $image  = $website . 'e/e049/e' .
                                substr('000000000', 0, 9 - strlen($frm)) .
                                $frm . '.pdf';
                break;
            }   // 1906 census

            case 1911:
            {
                if ($relFrame == 0)
                    $frm    = $page - $page1 + $imageBase;
                else
                    $frm    = $page - $page1 + $relFrame;
                $image  = $website . '1911/jpg/e' .
                                substr('000000000', 0, 9 - strlen($frm)) .
                                $frm . '.jpg';
                break;
            }   // 1911 census

            case 1916:
            {
                $reel   = 't-' . ($imageBase - 4342029);
                $frm    = $page - $page1 + $relFrame;
                $image  = $website . '006003/' .
                                $reel .
                                '/jpg/31228_' .
                                substr('0000000', 0, 7 - strlen($imageBase)) .
                                $imageBase .
                                '-' .
                                substr('00000', 0, 5 - strlen($frm)) .
                                $frm .
                                '.jpg';
                break;
            }   // 1916 census

            case 1921:
            {
                $frm    = $page - $page1 + $relFrame;
                $image  = "https://www.ancestry.ca/interactive/8991/1921_" .
                                substr('000', 0, 3 - strlen($imageBase)) .
                                $imageBase .
                                "-e" .
                                substr('000000000', 0, 9 - strlen($frm)) .
                                $frm;
                break;
            }   // 1921 census

        }       // switch on census year

        return $image;
    }       // SubDistrict::getDefaultImage

    /********************************************************************
     * function SubDistrict::getPrevSearch                              *
     *                                                                  *
     *  This method identifies the identifying parameters of the        *
     *  instance of SubDistrict which precedes the current entry within *
     *  the district and returns the search string to append to the URL *
     *  of the script to obtain the information about that preceding    *
     *  instance.                                                       *
     *                                                                  *
     *  Returns:                                                        *
     *      string query expression to append to a URL for method=get   *
     ********************************************************************/
    function getPrevSearch()
    {
        global $connection;
        global $debug;
        global $warn;

        if (is_null($this->prevSearch))
        {       // not initialized
            // identify the immediately preceding enumeration division within
            // the current district
            $censusId       = $this->row['sd_census'];
            $distId         = $this->row['sd_distid'];
            if ($distId == floor($distId))
                $distId     = intval($distId);
            $sdId           = $this->row['sd_id'];
            $province       = $this->district['province'];

            if (ctype_digit($sdId))
                $selPrev    = "SELECT SD_Id, SD_Div from SubDistricts where SD_Census=:censusId && SD_DistId=:distId && CONCAT(LPAD(SD_ID,3,'0'), LPAD(SD_Div,2,' '))<=CONCAT(LPAD(:sdId,3,'0'),LPAD(:division,2,' ')) ORDER BY (SD_Id-0) DESC, LPAD(SD_Div,2,' ') DESC LIMIT 2;";
            else
                $selPrev    = "SELECT SD_Id, SD_Div from SubDistricts where SD_Census=:censusId && SD_DistId=:distId && CONCAT(SD_ID, LPAD(SD_Div,2,' '))<=CONCAT(:sdId,LPAD(:division,2,' ')) ORDER BY SD_Id DESC, LPAD(SD_Div,2,' ') DESC LIMIT 2;";
            $sqlParms       = array('censusId'  => $censusId,
                                    'distId'    => $distId,
                                    'sdId'      => $sdId,
                                    'division'  => $this->row['sd_div']);


            $stmt           = $connection->prepare($selPrev);
            $selText        = debugPrepQuery($selPrev, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // success
                if ($debug)
                    $warn   .= "<p>SubDistrict::getPrevSearch: " .
                                __LINE__ . " $selText</p>\n";

                $result     = $stmt->fetchAll(PDO::FETCH_NUM);
                if (count($result) == 2)
                {       // current and previous division
                    // second row contains ids of previous division
                    $row                = $result[1];
                    $this->prevSd       = $row[0];
                    $this->prevDiv      = $row[1];
                    $this->prevSearch   = "?Census=$censusId&Province=$province&District=$distId&SubDistrict={$row[0]}&Division={$row[1]}";
                }       // current and previous division
                else
                {       // no previous division in district
                    $this->prevSd       = '';
                    $this->prevDiv      = '';
                    $this->prevSearch   = '';
                }       // no previous division in district
            }           // success
            else
            {
                throw new Exception("SubDistrict:getPrevSearch: " .
                                "getNames: query='$selText', " .
                                print_r($stmt->errorInfo(),true));
            }           // error on query
        }               // not initialized
        return $this->prevSearch;
    }       // SubDistrict::getPrevSearch

    /********************************************************************
     * function SubDistrict::getPrevSd                                  *
     *                                                                  *
     *  This method returns the identifier of the subdistrict preceding *
     *  the current subdistrict within the district.                    *
     *                                                                  *
     *  Returns:                                                        *
     *      string identifier of a group of enumeration divisions       *
     *      representing an administrative unit within a District       *
     *      such as a town, township, or city ward.                     *
     *      This is the empty string if this instance of SubDistrict    *
     *      is the first in the District.                                *
     ********************************************************************/
    function getPrevSd()
    {
        if (is_null($this->prevSd))
            $this->getPrevSearch();
        return $this->prevSd;
    }       // SubDistrict::getPrevSd

    /********************************************************************
     * function SubDistrict::getPrevDiv                                 *
     *                                                                  *
     *  This method returns the division identifier of the subdistrict  *
     *  preceding the current subdistrict within the district.          *
     *                                                                  *
     *  Returns:                                                        *
     *      string identifier of a single enumeration divisions         *
     *      within an administrative unit within a District             *
     *      This is the empty string if this instance of SubDistrict    *
     *      is the first in the District.                                *
     ********************************************************************/
    function getPrevDiv()
    {
        if (is_null($this->prevDiv))
            $this->getPrevSearch();
        return $this->prevDiv;
    }       // SubDistrict::getPrevDiv

    /********************************************************************
     * function SubDistrict::getPrevSubDistrict                         *
     *                                                                  *
     *  This method returns the instance of Subdistrict preceding       *
     *  the current SubDistrict within the district.                    *
     *                                                                  *
     *  Returns:                                                        *
     *      This returns an instance of SubDistrict or null.            *
     *      This is null if this instance of SubDistrict                *
     *      is the first in the District.                               *
     ********************************************************************/
    function getPrevSubDistrict()
    {
        if (is_null($this->prevSubDistrict))
        {
            $this->getPrevSearch();
            if ($this->prevSd == '')
                return null;
            $parms          = array('sd_distId' => $this->district,
                                    'sd_Id'     => $this->prevSd,
                                    'sd_div'    => $this->prevDiv,
                                    'sd_sched'  => '1');
            $this->prevSubDistrict      = new SubDistrict($parms);
        }
        return $this->prevSubDistrict;
    }       // SubDistrict::getPrevSubDistrict

    /********************************************************************
     * function SubDistrict::getNextSearch                              *
     *                                                                  *
     *  This method identifies the identifying parameters of the        *
     *  instance of SubDistrict which follows the current entry within  *
     *  the district and returns the search string to append to the URL *
     *  of the script to obtain the information about that following    *
     *  instance.                                                       *
     *                                                                  *
     *  Returns:                                                        *
     *      string query expression to append to a URL for method=get   *
     ********************************************************************/
    function getNextSearch()
    {
        global $connection;
        global $debug;
        global $warn;

        if (is_null($this->nextSearch))
        {       // not initialized
            $censusId       = $this->row['sd_census'];
            $distId         = $this->row['sd_distid'];
            if ($distId == floor($distId))
                $distId     = intval($distId);
            $sdId           = $this->row['sd_id'];
            $province       = $this->district['province'];

            // identify the immediately following enumeration division within
            // the current district
            if (ctype_digit($sdId))
                $selNext    = "SELECT SD_Id, SD_Div from SubDistricts where SD_Census=:censusId && SD_DistId=:distId && CONCAT(LPAD(SD_ID,3,'0'), LPAD(SD_Div,2,' '))>=CONCAT(LPAD(:sdId,3,'0'),LPAD(:division,2,' ')) ORDER BY (SD_Id-0) ASC, LPAD(SD_Div,2,' ') ASC LIMIT 2;";
            else
                $selNext    = "SELECT SD_Id, SD_Div from SubDistricts where SD_Census=:censusId && SD_DistId=:distId && CONCAT(SD_ID, LPAD(SD_Div,2,' '))>=CONCAT(:sdId,LPAD(:division,2,' ')) ORDER BY SD_Id ASC, LPAD(SD_Div,2,' ') ASC LIMIT 2;";
            $sqlParms       = array('censusId'  => $censusId,
                                    'distId'    => $distId,
                                    'sdId'      => $sdId,
                                    'division'  => $this->row['sd_div']);

            $stmt           = $connection->prepare($selNext);
            $selText        = debugPrepQuery($selNext, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // success
                if ($debug)
                    $warn   .= "<p>SubDistrict::getPrevSearch: " .
                                __LINE__ . " $selText</p>\n";
                $result     = $stmt->fetchAll(PDO::FETCH_NUM);
                if (count($result) == 2)
                {       // current and next division
                    // second row contains ids of next division
                    $row                = $result[1];
                    $this->nextSd       = $row[0];
                    $this->nextDiv      = $row[1];
                    $this->nextSearch   = "?Census=$censusId&Province=$province&District=$distId&SubDistrict={$row[0]}&Division={$row[1]}";
                }       // current and next division
                else
                {       // no next in district
                    $this->nextSd       = '';
                    $this->nextDiv      = '';
                    $this->nextSearch   = '';
                }       // no next in district
            }           // success
            else
            {
                throw new Exception("SubDistrict::getNames: " .
                                "query='$selText', " .
                                print_r($stmt->errorInfo(),true));
            }           // error on query
        }               // not initialized
        return $this->nextSearch;
    }       // SubDistrict::getNextSearch

    /********************************************************************
     * function SubDistrict::getNextSd                                  *
     *                                                                  *
     *  This method returns the identifier of the subdistrict following *
     *  the current subdistrict within the district.                    *
     *                                                                  *
     *  Returns:                                                        *
     *      string identifier of a group of enumeration divisions       *
     *      representing an administrative unit within a District       *
     *      such as a town, township, or city ward.                     *
     *      This is the empty string if this instance of SubDistrict    *
     *      is the last in the District.                                *
     ********************************************************************/
    function getNextSd()
    {
        if (is_null($this->nextSd))
            $this->getNextSearch();
        return $this->nextSd;
    }       // SubDistrict::getNextSd

    /********************************************************************
     * function SubDistrict::getNextDiv                                 *
     *                                                                  *
     *  This method returns the division identifier of the subdistrict  *
     *  following the current subdistrict within the district.          *
     *                                                                  *
     *  Returns:                                                        *
     *      string identifier of a single enumeration divisions         *
     *      within an administrative unit within a District             *
     *      such as a town, township, or city ward.                     *
     *      This is the empty string if this instance of SubDistrict    *
     *      is the last in the District.                                *
     ********************************************************************/
    function getNextDiv()
    {
        if (is_null($this->nextDiv))
            $this->getNextSearch();
        return $this->nextDiv;
    }       // SubDistrict::getNextDiv

    /********************************************************************
     * function SubDistrict::getNextSubDistrict                         *
     *                                                                  *
     *  This method returns the instance of Subdistrict following       *
     *  the current SubDistrict within the district.                    *
     *                                                                  *
     *  Returns:                                                        *
     *      This returns an instance of SubDistrict or null.            *
     *      This is null if this instance of SubDistrict                *
     *      is the last in the District.                                *
     ********************************************************************/
    function getNextSubDistrict()
    {
        if (is_null($this->nextSubDistrict))
        {
            $this->getNextSearch();
            if ($this->nextSd == '')
                return null;
            $parms          = array('sd_distId' => $this->district,
                                    'sd_Id'     => $this->nextSd,
                                    'sd_div'    => $this->nextDiv,
                                    'sd_sched'  => '1');
            $this->nextSubDistrict      = new SubDistrict($parms);
        }
        return $this->nextSubDistrict;
    }       // SubDistrict::getNextSubDistrict

    /********************************************************************
     * function SubDistrict::toXml                                      *
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
    function toXml($top,
                   $print   = true,
                   $options = 0)
    {
        // ensure that there is a label to use for the top element
        if (!is_string($top) || strlen($top) == 0)
            $top            = 'SubDistrict';
        if (is_null($top))
            $retval         = '';
        else
            $retval         = "    <$top>\n";

        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 3) == 'sd_')
                    $key    = substr($key, 3);
            $retval         .= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
            if ($key == 'distid')
            {           // also report information from District
                $retval     .= "\t<distname>" .
                                htmlentities($this->district['d_name'],ENT_XML1) .
                                "</distname>\n";
                $retval     .= "\t<province>" .
                                htmlentities($this->district['d_province'],ENT_XML1) .
                                "</province>\n";
            }           // also report information from District
        }
        if ($top !== null)
            $retval         .= "    </$top>\n";
    }       // SubDistrict::toXml

    /********************************************************************
     * function SubDistrict::__toString                                 *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return "SubDistrict{sd_id=" . $this->row['sd_id'] .
                            ", sd_name='" . $this->row['sd_name'] .
                            "'}";
    }       // SubDistrict::__toString

    /********************************************************************
     * function SubDistrict::getPage                                    *
     *                                                                  *
     *  Get an instance of Page for this SubDistrict.                   *
     *                                                                  *
     *  Parameters:                                                     *
     *      $page           page number                                 *
     *      $sched          schedule identifier, default the population *
     *                      schedule                                    *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Page or null                                    *
     ********************************************************************/
    function getPage($page, $sched = '1')
    {
        $page1          = $this->row['sd_page1'];
        $pages          = $this->row['sd_pages'];
        $bypage         = $this->row['sd_bypage'];

        if ($page < $page1 ||
            $page > ($page1 + $bypage * ($pages - 1)) ||
            (($bypage == 2) && (($page - $page1) % $bypage) != 1))
            return null;

        return new Page($this, $page, $sched);
    }           // function SubDistrict::getPage

    /********************************************************************
     * function SubDistrict::getPages                                   *
     *                                                                  *
     *  Get a RecordSet of instances of Page for this SubDistrict.      *
     *  If the corresponding portion of the Pages table has not been    *
     *  created yet, this creates new records for each required Page.   *
     *                                                                  *
     *  Returns:                                                        *
     *      RecordSet of instances of Page indexed by page              *
     *      use foreach($subd->getPages() as $page)                     *
     ********************************************************************/
    function getPages()
    {
        return new PageSet(array('sdid' => $this));
    }       // SubDistrict::getPages

    /********************************************************************
     *  function SubDistrict::getStatistics                             *
     *                                                                  *
     *  Display page by page statistics for this enumeration division   *
     *  transcription records.                                          *
     *                                                                  *
     *  Returns:                                                        *
     *      array of associative arrays containing statistics           *
     *          'page'              page number                         *
     *          'namecount'         number of lines with given name     *
     *          'agecount'          number of lines with age            *
     *          'idircount'         number of lines with IDIR           *
     *          'pt_population'     total lines on page                 *
     *          'pt_transcriber'    name of transcriber                 *
     *          'pt_proofreader'    name of proofreader                 *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Errors are appended to the global $warn                     *
     ********************************************************************/
    function getStatistics()
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $census             = $this->getCensus();
        $censusYear         = $census->get('year');

        $sqlParms           = array('censusid'      => $census['censusid'],
                                    'district'      => $this->row['sd_distid'],
                                    'subdistrict'   => $this->row['sd_id'],
                                    'division'      => $this->row['sd_div']);
        $where              = 'WHERE District=:district AND SubDistrict=:subdistrict AND Division=:division';
        $province           = $census['province'];
        if (strlen($province) > 0)
        {
            $where          .= ' AND Province=:province';
            $sqlParms['province']   = $province;
        }

        $query  = "SELECT Page, SUM(GivenNames != '') AS namecount," .
                            "SUM(Age != '') AS agecount," .
                            "SUM(IDIR != 0) AS idircount," .
                            "PT_Population, PT_Transcriber," .
                            "PT_Proofreader" .
                        " FROM Census$censusYear" .
                        " JOIN Pages ON PT_Census=:censusid AND " .
                            "PT_DistId=District AND PT_SdId=SubDistrict " .
                            "AND PT_Div=Division AND PT_Sched='1' " .
                            "AND PT_Page=Page " .
                        $where .
                        " GROUP BY Page," .
                            "PT_Population, PT_Transcriber," .
                            "PT_Proofreader " .
                        "ORDER BY Page";

        $stmt               = $connection->prepare( $query );
        $queryText          = debugPrepQuery($query, $sqlParms);
        if ($stmt->execute($sqlParms))
        {       // successful query
            $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($debug)
                $warn       .= "<p>SubDistrict::getStatistics: " . __LINE__ .
                                " $queryText</p>";
            $pagesize       = $this->census['linesperpage'];
            if ($debug)
                $warn       .= "<p>SubDistrict::getStatistics: " . __LINE__ . " \$this->getSubDistrict()</p>\n";
            $nextpage       = $this->row['sd_page1'];
            $bypage         = $this->row['sd_bypage'];
            $pages          = $this->row['sd_pages'];

            $retval         = array();
            $row            = current($result);
            for ($i = 0; $i < $pages; $i++)
            {
                if ($row === false || $row['page'] > $nextpage)
                {
                    $retval[]       = array('page'              => $nextpage,
                                            'namecount'         => 0,
                                            'agecount'          => 0,
                                            'idircount'         => 0,
                                            'pt_population'     => $pagesize,
                                            'pt_transcriber'    => '',
                                            'pt_proofreader'    => '');
                }
                else
                {
                    $retval[]       = $row;
                    $row            = next($result);
                }

                $nextpage           += $bypage;
            }
            return $retval;
        }       // successful query
        else
        {       // error issuing query
            // diagnostic printout of main select statement
            $warn   .= "<p>SubDistrict::getStatistics: " . __LINE__ .
                            " '$queryText', " .
                            print_r($stmt->errorInfo(),true) . "</p>\n";
            return array();
        }       // error issuing query

    }           // function SubDistict::getStatistics

}       // class SubDistrict
