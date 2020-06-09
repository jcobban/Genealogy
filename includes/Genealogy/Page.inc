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
 *  Definition of a class representing a single page from a census.     *
 *  This class provides                                                 *
 *  access to the information in a row of the table Pages.              *
 *                                                                      *
 *  History:                                                            *
 *      2013/07/13      created                                         *
 *      2013/09/08      base class renamed to Record                    *
 *      2014/04/27      correct save update                             *
 *                      set correct default for fieldPT_population      *
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
                                        'pt_census'         => 'CA1881',
                                        'pt_distid'         => 0,
                                        'pt_sdid'           => '',
                                        'pt_div'            => '',
                                        'pt_sched'          => '1',
                                        'pt_page'           => 0,
                                        'pt_population'     => 25,
                                        'pt_image'          => '',
                                        'pt_transcriber'    => '',
                                        'pt_proofreader'    => '');

    /********************************************************************
     *  Page::$translate                                                *
     *                                                                  *
     *  Translation of alternate field names                            *
     ********************************************************************/
    protected static $translate = array(
                                        'census'            => 'pt_census',
                                        'censusid'          => 'pt_census',
                                        'distid'            => 'pt_distid',
                                        'did'               => 'pt_distid',
                                        'district'          => 'pt_distid',
                                        'sdid'              => 'pt_sdid',
                                        'id'                => 'pt_sdid',
                                        'sd'                => 'pt_sdid',
                                        'subdistrict'       => 'pt_sdid',
                                        'div'               => 'pt_div',
                                        'division'          => 'pt_div',
                                        'sched'             => 'pt_sched',
                                        'schedule'          => 'pt_sched',
                                        'page'              => 'pt_page',
                                        'population'        => 'pt_population',
                                        'image'             => 'pt_image',
                                        'transcriber'       => 'pt_transcriber',
                                        'proofreader'       => 'pt_proofreader');

    /********************************************************************
     *  Page::$defaultOrder                                             *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'PT_census,PT_distid,PT_sdid,PT_div,PT_sched,PT_page';

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
                    case 'pt_census':
                    {
                        $censusId           = $value;
                        break;
                    }       // Census identifier

                    case 'pt_distid':
                    {
                        $distId             = $value;
                        break;
                    }       // District identifier

                    case 'pt_sdid':
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

                    case 'pt_div':
                    {
                        $div                = $value;
                        break;
                    }       // Division identifier

                    case 'pt_sched':
                    {
                        $sched              = $value;
                        break;
                    }       // Schedule identifier

                    case 'pt_page':
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
            $this->msg      .= "Unsupported parameter type: $parms. ";
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
                                'WHERE PT_Census=:censusId AND  ' .
                                      'PT_DistId=:distId AND  ' .
                                      'PT_SdId=:sdId AND ' .
                                      'PT_Div=:div AND ' .
                                      'PT_Sched=:sched AND ' .
                                      'PT_Page=:page';
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
                $dbrow['pt_census']     = $censusId;
                $dbrow['pt_distid']     = $distId;
                $dbrow['pt_sdid']       = $sdId;
                $dbrow['pt_div']        = $div;
                $dbrow['pt_sched']      = $sched;
                $dbrow['pt_page']       = $page;
                $dbrow['pt_population'] = $this->census->get('linesPerPage');
                $needInsert         = true;
            }       // create new record

            // invoke constructor of base class
            parent::__construct($dbrow,
                                'Pages');
            $this->needInsert   = $needInsert;

            // if the record does not explicitly specify the associated
            // image, calculate it from information in the
            // SubDistrict record
            $image                      = $dbrow['pt_image'];
            if ($sched == '1' && 
                ($image == '' ||
                strpos($image, '00000') !== false))
            {
                $image  = $subDistrict->getDefaultImage($page);
                parent::set('pt_image',
                            $image);
            }

            // diagnostic output if debug is set
            $this->dump('Page Record constructed:');
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
            case 'pt_census':
            case 'pt_distid':
            case 'pt_sdid':
            case 'pt_div':
            case 'pt_sched':
            case 'pt_page':
            case 'pt_population':
            case 'pt_image':
            case 'pt_transcriber':
            case 'pt_proofreader':
            {       // no validation implemented yet
                return parent::set($field, $value);
            }       // no validation implemented yet

            default:
            {       // no validation implemented yet
                return parent::set($field, $value);
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
                $sqlParms['o_census']       = $this->former['pt_census'];
                $sqlParms['o_distId']       = $this->former['pt_distid'];
                $sqlParms['o_sdId']         = $this->former['pt_sdid'];
                $sqlParms['o_div']          = $this->former['pt_div'];
                $sqlParms['o_sched']        = $this->former['pt_sched'];
                $sqlParms['o_page']         = $this->former['pt_page'];
                $where      = 'PT_Census=:o_census AND ' .
                                  'PT_DistId=:o_distId AND ' .
                                  'PT_SdId=:o_sdId AND ' .
                                  'PT_Div=:o_div AND ' .
                                  'PT_Sched=:o_sched AND ' .
                                  'PT_Page=:o_page';

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
                $sqlParms['o_census']   = $this->former['pt_census'];
                $sqlParms['o_distId']   = $this->former['pt_distid'];
                $sqlParms['o_sdId']     = $this->former['pt_sdid'];
                $sqlParms['o_div']      = $this->former['pt_div'];
                $sqlParms['o_sched']    = $this->former['pt_sched'];
                $sqlParms['o_page']     = $this->former['pt_page'];
            }       // record has been updated
            else
            {       // record has not been updated
                $sqlParms['o_census']   = $this->row['pt_census'];
                $sqlParms['o_distId']   = $this->row['pt_distid'];
                $sqlParms['o_sdId']     = $this->row['pt_sdid'];
                $sqlParms['o_div']      = $this->row['pt_div'];
                $sqlParms['o_sched']    = $this->row['pt_sched'];
                $sqlParms['o_page']     = $this->row['pt_page'];
            }           // record has not been updated
            $delete     = 'DELETE FROM Pages WHERE ' .
                                    'PT_Census=:o_census AND ' .
                                    'PT_DistId=:o_distId AND ' .
                                    'PT_SdId=:o_sdId AND ' .
                                    'PT_Div=:o_div AND ' .
                                    'PT_Sched=:o_sched AND ' .
                                    'PT_Page=:o_page';

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

}       // class Page
