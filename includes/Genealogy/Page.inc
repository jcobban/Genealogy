<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Page.inc                                                            *
 *                                                                      *
 *  Implementation of a class representing a single page from a census. *
 *  This class provides access to the information in a row              *
 *  of the table Pages.                                                 *
 *                                                                      *
 *  CREATE TABLE `Pages` (                                              *
 *      `Census`        VARCHAR(6) NOT NULL DEFAULT 'CA1881',           *
 *      `DistId`        DECIMAL(4,1) NOT NULL,                          *
 *      `SdId`          VARCHAR(5) NOT NULL,                            *
 *      `Div`           VARCHAR(4) NOT NULL DEFAULT '',                 *
 *      `Sched`         CHAR(1) NOT NULL DEFAULT '1',                   *
 *      `PageNum`       INT(4) NOT NULL DEFAULT '1',                    *
 *      `Population`    INT(2) DEFAULT '25',                            *
 *      `Image`         VARCHAR(255) DEFAULT '',                        *
 *      `Transcriber`   VARCHAR(64) DEFAULT '',                         *
 *      `ProofReader`   VARCHAR(64) DEFAULT '',                         *
 *      PRIMARY KEY (`Census`, `DistId`, `SdId`,                        *
 *                      `Div`, `Sched`, `PageNum`),                     *
 *      KEY `PT_Image` (`Image`) )                                      *
 *                                                                      *
 *  History:                                                            *
 *      2013/07/13      created                                         *
 *      2013/09/08      base class renamed to Record                    *
 *      2014/04/27      correct save update                             *
 *                      set correct default for field population        *
 *      2014/12/28      send trace information to $warn                 *
 *                      support associative array parameter for         *
 *                      constructor for increased readability of code   *
 *                      add static function getPages                    *
 *      2015/05/23      did not throw exception in constructor          *
 *      2015/06/02      use new global census table                     *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/01/21      use class Census                                *
 *      2017/02/02      use common translation of alternate field names *
 *      2017/07/20      parameter list to logSqlUpdate changed          *
 *      2017/11/16      change to use only prepared statements          *
 *                      remove static functions                         *
 *                      permit updating identification fields           *
 *      2017/12/18      add support for temporary fields                *
 *      2020/06/07      do not trust URL if the URL implies a zero      *
 *                      initial frame number for the SubDistrict        *
 *      2020/10/10      remove prefix from field names                  *
 *      2020/12/02      add method getLine                              *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';

/************************************************************************
 *  Page                                                                *
 *                                                                      *
 *  Definition of a class recording information about a single page     *
 *  from a census.                                                      *
 ************************************************************************/

class Page extends Record
{
    /********************************************************************
     *  Page::$initRow                                                  *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                                        'census'            => 'CA1881',
                                        'distid'            => 0,
                                        'sdid'              => '',
                                        'div'               => '',
                                        'sched'             => '1',
                                        'pagenum'           => 1,
                                        'population'        => 25,
                                        'image'             => '',
                                        'transcriber'       => '',
                                        'proofreader'       => '');

    /********************************************************************
     *  Page::$translate                                                *
     *                                                                  *
     *  Translation of alternate field names                            *
     ********************************************************************/
    protected static $translate = array(
                                        'pt_census'         => 'census',
                                        'censusid'          => 'census',
                                        'pt_distid'         => 'distid',
                                        'did'               => 'distid',
                                        'district'          => 'distid',
                                        'pt_sdid'           => 'sdid',
                                        'id'                => 'sdid',
                                        'sd'                => 'sdid',
                                        'subdistrict'       => 'sdid',
                                        'pt_div'            => 'div',
                                        'division'          => 'div',
                                        'pt_sched'          => 'sched',
                                        'schedule'          => 'sched',
                                        'page'              => 'pagenum',
                                        'pt_page'           => 'pagenum',
                                        'pt_population'     => 'population',
                                        'pt_image'          => 'image',
                                        'pt_transcriber'    => 'transcriber',
                                        'pt_proofreader'    => 'proofreader');

    /********************************************************************
     *  Page::$defaultOrder                                             *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'Census,DistId,SdId,`Div`,Sched,PageNum';

    /********************************************************************
     *  Page::$census                                                   *
     *                                                                  *
     *  Save reference to instance of Census                            *
     ********************************************************************/
    protected $census                   = null;

    /********************************************************************
     *  Page::$district                                                 *
     *                                                                  *
     *  Save reference to instance of District                          *
     ********************************************************************/
    protected $district                 = null;

    /********************************************************************
     *  Page::$subDistrict                                              *
     *                                                                  *
     *  Save reference to instance of SubDistrict                       *
     ********************************************************************/
    protected $subDistrict              = null;

    /********************************************************************
     *  function Page::__construct                                      *
     *                                                                  *
     *  Construct an instance of Page based upon the                    *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms              associative array of parameters or      *
     *                          instance of SubDistrict                 *
     *      $page               page number                             *
     *      $sched              schedule identifier                     *
     *                                                                  *
     *  Returns object containing a description of a page               *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms,
                         $page  = null,
                         $sched = '1')
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $needInsert                         = false;

        $this->table                        = 'Pages';
        if (is_array($parms))
        {       // associative array of parameters
            $censusId                       = 'CA1881';
            $distId                         = null;
            $sdId                           = null;
            $div                            = null;
            $page                           = null;
            $subDistrict                    = null; // instance of SubDistrict

            foreach($parms as $fldname => $value)
            {       // loop through parameters
                $fieldLc                    = strtolower($fldname);
                if (array_key_exists($fieldLc, self::$translate))
                    $fieldLc                = self::$translate[$fieldLc];
                switch($fieldLc)
                {   // act on specific parameters
                    case 'census':
                    {
                        $censusId           = $value;
                        break;
                    }       // Census identifier

                    case 'distid':
                    {
                        $distId             = $value;
                        break;
                    }       // District identifier

                    case 'sdid':
                    {
                        if ($value instanceof SubDistrict)
                        {   // object
                            $subDistrict    = $value;
                        }   // object
                        else
                        if (is_string($value))
                        {   // identifier
                            $sdId           = $value;
                        }   // identifier
                        else
                            $warn           .= "<p>$fldname=" .
                                   print_r($value, true) . "</p>\n";
                        break;
                    }       // SubDistrict identifier

                    case 'div':
                    {
                        $div                = $value;
                        break;
                    }       // Division identifier

                    case 'sched':
                    {
                        $sched              = $value;
                        break;
                    }       // Schedule identifier

                    case 'pagenum':
                    {
                        $page               = $value;
                        break;
                    }       // Census identifier

                }           // act on specific parameters
            }               // loop through parameters

            if (is_null($subDistrict))
            {               // need to obtain instance of SubDistrict
                $sdParms        = array('sd_census' => $censusId,
                                        'sd_distid' => $distId,
                                        'sd_id'     => $sdId,
                                        'sd_div'    => $div,
                                        'sd_sched'  => $sched);
                $subDistrict    = new SubDistrict($sdParms);
            }               // need to obtain instance of SubDistrict
            else
            {               // use instance passed in parameters
                $censusId       = $subDistrict->get('sd_census');
                $distId         = $subDistrict->get('sd_distid');
                $sdId           = $subDistrict->get('sd_id');
                $div            = $subDistrict->get('sd_div');
                $sched          = $subDistrict->get('sd_sched');
            }               // use instance passed in parameters
        }                   // associative array of parameters
        else
        if ($parms instanceof SubDistrict)
        {
            $subDistrict            = $parms;
            $censusId               = $subDistrict->get('sd_census');
            $distId                 = $subDistrict->get('sd_distid');
            $sdId                   = $subDistrict->get('sd_id');
            $div                    = $subDistrict->get('sd_div');
            $sched                  = $subDistrict->get('sd_sched');
        }
        else
        {       // unsupported parameter type
            $this->msg      .= "Unsupported parameter type: " .
                                print_r($parms, true) . ". ";
        }       // unsupported parameter type

        $this->subDistrict          = $subDistrict;
        $this->census               = $subDistrict->getCensus();

        $page1                      = $this->subDistrict['sd_page1'];
        $pages                      = $this->subDistrict['sd_pages'];
        $bypage                     = $this->subDistrict['sd_bypage'];

        if ($page < $page1 ||
            $page > ($page1 + $bypage * ($pages - 1)) ||
            (($page - $page1) % $bypage) != 0)
            $this->msg      .= "Page::__construct: $page is not a valid page number within SubDistrict ($censusId,$distId,$sdId,$div). ";

        // construct the query for an existing record
        $query  = 'SELECT * FROM Pages  ' .
                                'WHERE Census=:censusId AND  ' .
                                      'DistId=:distId AND  ' .
                                      'SdId=:sdId AND ' .
                                      '`Div`=:div AND ' .
                                      'Sched=:sched AND ' .
                                      'PageNum=:page';
        $sqlParms       = array('censusId'  => $censusId,
                                'distId'    => $distId,
                                'sdId'      => $sdId,
                                'div'       => $div,
                                'sched'     => $sched,
                                'page'      => $page);

        // query the database
        $stmt                           = $connection->prepare($query);
        $queryText                      = debugPrepQuery($query, $sqlParms);


        if ($stmt->execute($sqlParms))
        {       // successful execute of prepared statement
            if ($debug)
                $warn   .= "<p>Page::__construct: " . __LINE__ .
                                " $queryText</p>\n";
            $result                     = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($result))
            {       // existing record
                $dbrow                  = $result;
                $needInsert             = false;
            }       // existing record
            else
            {       // create a new record
                // set defaults
                $dbrow                  = self::$initRow;
                $dbrow['census']        = $censusId;
                $dbrow['distid']        = $distId;
                $dbrow['sdid']          = $sdId;
                $dbrow['div']           = $div;
                $dbrow['sched']         = $sched;
                $dbrow['pagenum']       = $page;
                $dbrow['population']    = $this->census->get('linesPerPage');
                $needInsert             = true;
            }       // create new record

            // invoke constructor of base class
            parent::__construct($dbrow,
                                'Pages');
            $this->needInsert   = $needInsert;

            // if the record does not explicitly specify the associated
            // image, calculate it from information in the
            // SubDistrict record
            $image                      = $dbrow['image'];
            if ($sched == '1' && 
                ($image == '' ||
                strpos($image, '00000') !== false))
            {
                $image  = $subDistrict->getDefaultImage($page);
                parent::set('image',
                            $image);
            }

            // diagnostic output if debug is set
            $this->dump('Page Record constructed:');
            $this->valid        = strlen($this->msg) == 0;
        }       // successful execute of prepared statement
        else
        {       // error on execute
            throw new Exception("Page::__construct: '$queryText' " .
                                print_r($sqlParms, true) .
                                print_r($stmt->errorInfo(),true));
        }       // error on execute
    }       // function Page::__construct

    /********************************************************************
     *  function Page:set                                               *
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
     *          previous value of the field                             *
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
            case 'census':
            case 'distid':
            case 'sdid':
            case 'div':
            case 'sched':
            case 'pagenum':
            case 'population':
            case 'image':
            case 'transcriber':
            case 'proofreader':
            {       // no validation implemented yet
                return parent::set($fieldLc, $value);
            }       // no validation implemented yet

            default:
            {       // no validation implemented yet
                return parent::set($fieldLc, $value);
            }       // no validation implemented yet
        }           // switch on field name
    }       // function Page::set

    /********************************************************************
     *  function Page::save                                             *
     *                                                                  *
     *  Save changes made to the Page record into the                   *
     *  database.                                                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format                                          *
     *                  if a string use it as tag name                  *
     ********************************************************************/
    function save($xml)
    {
        global  $debug;
        global  $warn;
        global  $userid;
        global  $connection;

        if (is_string($xml))
        {               // string tag name
            $tagname    = $xml;
            $xml    = true;
        }               // string tag name
        else
        if ($xml)
        {               // boolean true
            $tagname    = 'cmd';
        }               // true

        if ($this->needInsert)
        {               // create new record
            $fldnames       = '';
            $comma          = '';
            $values         = '';
            $sqlParms       = array();
            foreach($this->row as $fld => $value)
            {           // loop through all fields in record
                $fldnames   .= $comma . "`$fld`";
                $values     .= $comma . ":$fld";
                $sqlParms[$fld] = $value;
                $comma      = ", ";
            }           // loop through all fields in record

            // construct the SQL INSERT command
            $insert         = "INSERT INTO Pages ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt           = $connection->prepare($insert);
            $insertText     = debugPrepQuery($insert, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // success
                $count      = $stmt->rowCount();
                if ($xml)
                    print "<$tagname result='$count'>" . $insertText .
                                "</$tagname>\n";
                else
                if ($debug)
                {
                    $warn   .= "<p>Page::save: " . __LINE__ .
                                   " cmd='$insertText', count=$count</p>\n";
                }

                $this->changed  = array();  // reset
                $this->needInsert   = false;

                // log the update
                $this->log($insert,
                           $sqlParms);
            }           // success
            else
            {           // error inserting record
                throw new Exception("Page::save:
                                    '$insertText', " .
                                    print_r($stmt->errorInfo(),true));
            }           // error inserting record
        }               // create new record
        else
        {               // update existing record
            $update         = "UPDATE Pages SET ";
            $set            = '';
            $comma          = '';
            $sqlParms       = array();
            foreach($this->changed as $fld => $value)
            {
                $set        .= $comma . "`$fld`=:$fld";
                $sqlParms[$fld] = $value;
                $comma      = ', ';
            }           // loop through all fields in record
            $this->changed  = array();  // reset

            if (strlen($set) > 0)
            {           // changes made
                // construct the where clause
                $sqlParms['o_census']       = $this->former['census'];
                $sqlParms['o_distId']       = $this->former['distid'];
                $sqlParms['o_sdId']         = $this->former['sdid'];
                $sqlParms['o_div']          = $this->former['div'];
                $sqlParms['o_sched']        = $this->former['sched'];
                $sqlParms['o_page']         = $this->former['pagenum'];
                $where      = 'Census=:o_census AND ' .
                                  'DistId=:o_distId AND ' .
                                  'SdId=:o_sdId AND ' .
                                  '`Div`=:o_div AND ' .
                                  'Sched=:o_sched AND ' .
                                  'PageNum=:o_page';

                // assemble the UPDATE command
                $update     .= $set . ' WHERE ' . $where;

                // perform the update
                $stmt       = $connection->prepare($update);
                $updateText = debugPrepQuery($update, $sqlParms);
                if ($stmt->execute($sqlParms))
                {       // success
                    $count      = $stmt->rowCount();
                    if ($xml)
                            print "<$tagname count='$count'>" .
                                    $updateText . "</$tagname>\n";
                    else
                    if ($debug)
                            $warn   .= "<p>Page::save: " . __LINE__ .
                                    " cmd='$update', count=$count</p>\n";

                    // log the update
                    $this->log($update,
                               $sqlParms);
                }       // success
                else
                {       // error updating record
                    throw new Exception("Page::save:
                                    '$updateTextt', " .
                                    print_r($stmt->errorInfo(),true));
                }       // error updating record
            }           // changes made
        }               // update existing record
    }       // function Page::save

    /********************************************************************
     *  function Page::delete                                           *
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
            if ($this->former)
            {       // record has been updated
                $sqlParms['o_census']   = $this->former['census'];
                $sqlParms['o_distId']   = $this->former['distid'];
                $sqlParms['o_sdId']     = $this->former['sdid'];
                $sqlParms['o_div']      = $this->former['div'];
                $sqlParms['o_sched']    = $this->former['sched'];
                $sqlParms['o_page']     = $this->former['pagenum'];
            }       // record has been updated
            else
            {       // record has not been updated
                $sqlParms['o_census']   = $this->row['census'];
                $sqlParms['o_distId']   = $this->row['distid'];
                $sqlParms['o_sdId']     = $this->row['sdid'];
                $sqlParms['o_div']      = $this->row['div'];
                $sqlParms['o_sched']    = $this->row['sched'];
                $sqlParms['o_page']     = $this->row['pagenum'];
            }           // record has not been updated
            $delete     = 'DELETE FROM Pages WHERE ' .
                                    'Census=:o_census AND ' .
                                    'DistId=:o_distId AND ' .
                                    'SdId=:o_sdId AND ' .
                                    '`Div`=:o_div AND ' .
                                    'Sched=:o_sched AND ' .
                                    'PageNum=:o_page';

            $stmt           = $connection->prepare($delete);
            $deleteText     = debugPrepQuery($delete, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // success
                $count      = $stmt->rowCount();
                if ($xml)
                    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
                else
                if ($debug)
                    $warn   .= "<p>Page::delete: " . __LINE__ .
                                    " cmd='$deleteText', count='$count'</p>\n";

                // log the update
                $this->log($delete,
                           $sqlParms);

                // update the memory copy of the record to reflect that it
                // no longer represents a database record
                $this->needInsert   = true;

                return $count;  // number of records deleted
            }           // success
            else
            {           // error updating record
                error_log("Page::delete: " . __LINE__ . " $deleteText\n");
                error_log(print_r($stmt->errorInfo(),true) . "\n");
                return false;
            }           // error updating record
        }               // have key value

        // record never written to database
        return 0;       // nothing to delete
    }       // function Page::delete

    /********************************************************************
     * function Page::getLine                                           *
     *                                                                  *
     *  Get an instance of CensusLine for this Page.                    *
     *                                                                  *
     *  Parameters:                                                     *
     *      $linenum        line number                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of CensusLine or null                              *
     ********************************************************************/
    function getLine($linenum)
    {
        global $debug, $warn;

        if (is_string($linenum)) 
            $line           = intval($linenum, 10);
        else
        if (is_int($linenum))
            $line           = $linenum;
        else
            $line           = 0;
        if ($line > 0 && 
            $line <= $this->row['population'])
        { 
            $parms  = array('Census'        => $this->row['census'],
                            'District'      => $this->row['distid'],
                            'SubDistrict'   => $this->row['sdid'],
                            'Division'      => $this->row['div'],
                            'Page'          => $this->row['pagenum'],
                            'Line'          => $line);
            return new CensusLine($parms);
        }
        else
        {
            if ($debug)
                $warn       .= "<p>Page::getLine(" . gettype($linenum) .
                        ' ' . htmlspecialchars(print_r($linenum, true)) .
                        ")</p>\n";
            return null;
        }
    }           // function Page::getLine

}       // class Page
