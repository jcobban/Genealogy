<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageReport.inc                                            *
 *                                                                      *
 *  Definition of a class representing a report from a minister of      *
 *  religion in pre-confederation Ontario listing marriages that he     *
 *  had performed in a particular year.  It represents the              *
 *  information in a row of the table CountyMarriageReports.            *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/29      created                                         *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2016/05/10      obsolete global table $domains was declared     *
 *      2017/01/13      toXml did not print output if requested         *
 *      2017/01/31      add method toJson                               *
 *      2017/03/11      use common translate table for field names      *
 *                      get table statistics in getCountyMarriageReports*
 *                      fill in page number and image from preceding    *
 *                      support operator prefix on numeric parameters   *
 *                      to getCountyMarriageReports                     *
 *      2017/09/20      support fractional report numbers               *
 *      2017/10/12      complete migration to PDO prepared statements   *
 *                      report results of delete in XML response        *
 *      2017/12/18      add support for temporary fields                *
 *      2018/02/25      remove static function getCountyMarriageReports *
 *                      use debugPrepQuery to format diagnostics        *
 *                      fix extra quote marks in UPDATE                 *
 *                      use $record->log method instead of static       *
 *      2018/11/21      remove static getWhere                          *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/05/18      add $info                                       *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  CountyMarriageReport                                                *
 *                                                                      *
 *  Definition of a class implementing behavior for pre-confederation   *
 *  County Marriage Reports within a genealogy database.                *
 ************************************************************************/

class CountyMarriageReport extends Record
{

    /********************************************************************
     *  CountyMarriageReport::$initRow                                  *
     *                                                                  *
     *  static array containing default values for a new record         *
     ********************************************************************/
    protected static    $initRow    = array(
                        'm_regdomain'           => 'CAON',
                        'm_volume'              => 1,
                        'm_reportno'            => 1,
                        'm_page'                => 1,
                        'm_givennames'          => '',
                        'm_surname'             => '',
                        'm_surnamesoundex'      => '',
                        'm_faith'               => '',
                        'm_residence'           => '',
                        'm_image'               => '',
                        'm_idir'                => 0,
                        'm_remarks'             => '');

    /********************************************************************
     *  CountyMarriageReport::$info                                     *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'CountyMarriageReports',
                'name'              => 'CountyMarriageReports',
                'prime'             => array('m_regdomain','m_volume','m_reportno'),
                'srvmg'             => false,
                'fldcount'          => 6,
                'order'             => 'M_RegDomain,M_Volume,M_ReportNo',
                'classname'         => 'CountyMarriageReport');

    /********************************************************************
     *  CountyMarriageReport::$translate                                *
     *                                                                  *
     *  Translation of alternate field names                            *
     ********************************************************************/
    protected static $translate = array(
                        'regdomain'             => 'm_regdomain',
                        'domain'                => 'm_regdomain',
                        'volume'                => 'm_volume',
                        'reportno'              => 'm_reportno',
                        'page'                  => 'm_page',
                        'givennames'            => 'm_givennames',
                        'surname'               => 'm_surname',
                        'surnamesoundex'        => 'm_surnamesoundex',
                        'faith'                 => 'm_faith',
                        'residence'             => 'm_residence',
                        'image'                 => 'm_image',
                        'idir'                  => 'm_idir',
                        'remarks'               => 'm_remarks');

    /********************************************************************
     *  CountyMarriageReport::$defaultOrder                             *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'M_regdomain,M_volume,M_reportno';


    /********************************************************************
     *  function CountyMarriageReport::__construct                      *
     *                                                                  *
     *  Create an instance of a class that describes a                  *
     *  county marriage report.                                         *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array containing either          *
     *                  a complete row from the database or             *
     *                  array('Domain'      => $domain,                 *
     *                        'Volume'      => $volume,                 *
     *                        'ReportNo'        => $reportNo)           *
     *                  with the case of the parameters ignored         *
     *      'Domain'    a domain identifier consisting of a             *
     *                  2 character ISO country id followed by          *
     *                  a state identifier                              *
     *      'Volume'    integer original volume identifier              *
     *      'ReportNo'  report number within a volume.  Most of         *
     *                  these are integers but there is at              *
     *                  least one report with report number             *
     *                  '312½'.  Such a report can be specified         *
     *                  as 312.5 or '312½' or '3121/2'                  *
     *                                                                  *
     *  Returns object containing county marriage report record.        *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        $needInsert = false;
        $this->table    = 'CountyMarriageReports';
        $msg        = '';

        // validate parameters
        if (is_array($parms))
        {       // associative parms or row returned from database query
            if (count($parms) >= count(self::$initRow))
            {       // row returned from database query
                $dbrow      = $parms;
                $needInsert = false;
            }       // row returned from database query
            else
            {       // associative array of search parms
                $where      = '';
                $and        = 'WHERE ';
                $sqlParms   = array();
                foreach($parms as $fldname => $value)
                {   // loop through all supplied search parms
                    $fieldLc    = strtolower($fldname);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc    = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {   // act on specific parameter names
                        case 'm_regdomain':
                        {
                            $where  .= $and . 'M_RegDomain=?';
                            $sqlParms[] = $value;
                            $and    = ' AND ';
                            break;
                        }   // domain name

                        case 'm_volume':
                        {
                            $where  .= $and . 'M_Volume=?';
                            $sqlParms[] = $value;
                            $and    = ' AND ';
                            break;
                        }   // volume number

                        case 'm_reportno':
                        {
                            if (is_string($value))
                            {
                                $value      = trim($value);
                                // some idiot put in fractional values for
                                // report numbers!
                                $matches    = array();
                                if (preg_match('#^(\d+)(\x{00BD}|1/2|.5|.0)?$#u',
                                           $value,
                                           $matches))
                                {       // valid
                                    $reportNo   = intval($matches[1]);
                                    if (count($matches) > 2)
                                    {
                                    $half       = $matches[2];
                                    if ($half == '.0')
                                        $half   = '';
                                    }
                                    else
                                    $half       = '';
                                    if (strlen($half) > 0)
                                    {
                                    $reportNo   += 0.5;
                                    }
                                }       // valid
                                else
                                    $msg    .=
                                "Invalid value '$value' for report number. ";
                            }
                            else
                            if (is_numeric($value))
                                $reportNo       = $value;
                            else
                                $msg    .= "Invalid value '" .
                                       print_r($value, true) .
                                       "' for report number. ";
                            $where  .= $and . 'M_ReportNo=?';
                            $sqlParms[] = $reportNo;
                            $and    = ' AND ';
                            break;
                        }   // report number

                        default:
                        {
                            $msg    .= "Unrecognized search parm `$field`. ";
                            break;
                        }   // unrecognized
                    }   // act on specific parameter names
                }   // loop through all supplied search parms

                if (strlen($msg) == 0)
                {           // no errors so far
                    $query  = "SELECT * FROM CountyMarriageReports $where";
                    $stmt   = $connection->prepare($query);
                    $queryText  = debugPrepQuery($query, $sqlParms);
                    if ($stmt->execute($sqlParms))
                    {       // successful select
                        if ($debug)
                            $warn   .= "<p>CountyMarriageReport.inc: " .
                                       __LINE__ .
                                       " query='$queryText'</p>\n";
                        $row    = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($row))
                        {       // match existing row in table
                            $dbrow      = $row;
                            $needInsert     = false;
                        }       // match existing row in table
                        else
                        {       // no match, create new row
                            $dbrow      = self::$initRow;
                            foreach($parms as $field => $value)
                            {   // loop through all search parms
                                switch(strtolower($field))
                                {   // act on specific parameter names
                                    case 'domain':
                                    case 'm_regdomain':
                                    {
                                    $dbrow['m_regdomain']   = $value;
                                    $domain         = $value;
                                    break;
                                    }   // domain name
                
                                    case 'volume':
                                    case 'm_volume':
                                    {
                                    $dbrow['m_volume']  = $value;
                                    $volume         = $value;
                                    break;
                                    }   // volume number
                
                                    case 'reportno':
                                    case 'm_reportno':
                                    {
                                    $dbrow['m_reportno']    = $value;
                                    $reportNo       = $value;
                                    break;
                                    }   // report number
                
                                }   // act on specific parameter names
                            }   // loop through all search parms

                            $getParms   = array(
                                    'M_RegDomain'   => $domain,
                                    'M_Volume'  => $volume,
                                    'M_ReportNo'    => '<' . $reportNo,
                                    'order'     => 'M_ReportNo DESC',
                                    'limit'     => 1);
                            $reports    = new RecordSet('CountyMarriageReports',
                                            $getParms); 
                            if ($reports->count() > 0)
                            {       // copy values from previous report
                                $report         = $reports->rewind();
                                $dbrow['m_page']    = $report->get('page');
                                $dbrow['m_image']   = $report->get('image');
                            }       // copy values from previous report
                            $needInsert = true;
                        }       // no match, create new row
                    }           // successful select
                    else
                    {       // execute failed
                        $msg    .= "execute '$queryText' failed<br/>" .
                                       ":<br/>errorInfo=" .
                                       print_r($connection->errorInfo(), true);
                    }       // execute failed
                }       // no errors so far
            }       // associative array of search parms
        }       // parameter is an array
        else
        {
             $msg   .= "called with " .
                 gettype($parms) . ", value=" . print_r($parms, true);
        }

        if (strlen($msg) > 0)
            throw new Exception("CountyMarriageReport::__construct: " . $msg);
        // invoke constructor of base class
        parent::__construct($dbrow,
                            'CountyMarriageReports');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('CountyMarriageReport Record constructed:');
    }       // CountyMarriageReport::__construct

    /********************************************************************
     *  function CountyMarriageReport::toXml                            *
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
            if (substr($key, 0, 2) == 'm_')
                    $key    = substr($key, 2);
            $retval .= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
        }

        if ($top !== null)
            $retval .= "    </$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }       // CountyMarriageReport::toXml

    /********************************************************************
     *  function CountyMarriageReport::toJson                           *
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
    function toJson($print  = true,
                    $options    = 0)
    {
        global  $debug;

        $retval     = '';
        $comma      = "{\n";
        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 2) == 'm_')
                    $key    = substr($key, 2);
            $retval     .= $comma . "\t\"$key\":\t" . 
                                   json_encode($value);
            $comma      = ",\n";
        }

        if (strlen($retval) > 0)
            $retval     .= "\n    }\n";
        if ($print)
            print $retval;
        return $retval;
    }       // CountyMarriageReport::toJson

    /********************************************************************
     *  function CountyMarriageReport::get                              *
     *                                                                  *
     *  Get the value of a field by name.                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $name       field name                                      *
     *                  The name prefix "M_" may be omitted             *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds warning if $field is not already defined as a field    *
     *      or pseudo-field and returns null.                           *
     ********************************************************************/
    function get($field)
    {
        $fieldLc        = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc        = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'transcribed':
            case 'linked':
            {
                if (array_key_exists($fieldLc, $this->row))
                    return $this->row[$fieldLc];
                else
                    return 0;
            }

            default:
            {
                return parent::get($fieldLc);
            }
        }
    }       // CountyMarriageReport::get

    /********************************************************************
     *  function CountyMarriageReport::set                              *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change.  Case is ignored.      *
     *                  The name prefix "M_" may be omitted             *
     *      $value      value to set the field to                       *
     ********************************************************************/
    function set($field, $value)
    {
        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {       // act on specific field
            case 'm_regdomain':
            case 'm_volume':
            case 'm_reportno':
            case 'transcribed':
            case 'linked':
            {
                throw new Exception('CountyMarriageReport::set: ' .
                                    "cannot modify `$field`");
            }

            default:
            {
                return parent::set($fieldLc, $value);
            }

        }       // act on specific field
    }       // CountyMarriageReport::set

    /********************************************************************
     *  function CountyMarriageReport::save                             *
     *                                                                  *
     *  Save changes made to the CountyMarriageReport record into       *
     *  the database.                                                   *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  format within <cmd> tags.                       *
     *                  if a string use it as tag name                  *
     ********************************************************************/
    function save($xml = false)
    {
        global  $debug;
        global  $warn;
        global  $userid;
        global  $connection;

        if (is_string($xml))
        {           // string tag name
            $tagname    = $xml;
            $xml    = true;
        }           // string tag name
        else
        if ($xml)
        {           // true
            $tagname    = 'cmd';
        }           // true

        if ($this->needInsert)
        {           // create new record
            $fldnames       = '';
            $comma      = '';
            $values     = '';
            $sqlParms       = array();
            foreach($this->row as $fld => $value)
            {
                $fldnames       .= $comma . "`$fld`";
                $fldabbr        = substr($fld, 2); 
                $values         .= $comma .":$fldabbr";
                $sqlParms[$fldabbr] = $value;
                $comma  = ", ";
            }           // loop through all fields in record

            // construct the SQL INSERT command 
            $insert = "INSERT INTO CountyMarriageReports ($fldnames) " . 
                          "VALUES($values)";

            // insert the new record into the database
            $stmt       = $connection->prepare($insert);
            $insertText     = debugPrepQuery($insert, $sqlParms);
            if ($stmt->execute($sqlParms))
            {           // successful insert
                $count      = $stmt->rowCount();
                if ($xml)
                    print "<$tagname result='$count'>$insertText</$tagname>\n";

                if ($debug)
                {
                    $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
                                    " query='$insertText'";
                                    ", count=$count</p>\n";
                }

                // log the update
                $this->log($insert,
                           $sqlParms);

                // clean up internal state
                $this->changed      = array();  // reset
                $this->needInsert   = false;
            }           // successful insert
            else
            {           // error inserting record
                $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
                                " query='$insertText', error=" .
                                print_r($stmt->errorInfo(),true) . "</p>\n";
                error_log($warn);
                showTrace();
                if (strlen($insertText) > 43)
                    $insertText = substr($insertText, 0, 40) . '...';
                throw new Exception("CountyMarriageReport::save: '$insertText, error=" .
                                    print_r($stmt->errorInfo(),true));
            }           // error inserting record
        }           // create new record
        else
        {           // update existing record
            $update     = "UPDATE CountyMarriageReports SET ";
            $set        = '';
            $comma      = '';
            $sqlParms       = array();
            foreach($this->changed as $fld => $value)
            {           // loop through all changed fields in record
                $fldabbr        = substr($fld, 2); 
                $set            .= $comma . "`$fld`=:$fldabbr";
                $sqlParms[$fldabbr] = $value;
                $comma  = ', ';
            }           // loop through all changed fields in record

            // clean up internal state
            $this->changed  = array();  // reset

            if (strlen($set) > 0)
            {           // changes made
                // construct the where clause
                // the following permits altering the primary key fields
                // as long as a duplicate record is not created
                $where      = "M_RegDomain=:odomain AND " .
                                  "M_Volume=:ovolume AND " .
                                  "M_ReportNo=:oreportNo";
                if ($this->former)
                {       // record changed
                    $sqlParms['odomain']    = $this->former['m_regdomain'];
                    $sqlParms['ovolume']    = $this->former['m_volume'];
                    $sqlParms['oreportNo']  = $this->former['m_reportno'];
                }       // record changed
                else
                {       // record not changed
                    $sqlParms['odomain']    = $this->row['m_regdomain'];
                    $sqlParms['ovolume']    = $this->row['m_volume'];
                    $sqlParms['oreportNo']  = $this->row['m_reportno'];
                }       // record not changed

                // assemble the UPDATE command
                $update     .= $set . ' WHERE ' . $where;
                $stmt       = $connection->prepare($update);
                $updateText = debugPrepQuery($update, $sqlParms);
                if ($stmt->execute($sqlParms))
                {       // successful update
                    $count      = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>$updateText</$tagname>\n";

                    if ($debug)
                        $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
                                 " update='$updateText'</p>\n";

                    // log the update
                    $this->log($update,
                               $sqlParms);
                }       // successful update
                else
                {       // error updating record
                    throw new Exception("CountyMarriageReport::save:
                                    '$updateText', " .
                                    print_r($stmt->errorInfo(),true));
                }       // error updating record
            }           // changes made
        }           // update existing record
    }       // CountyMarriageReport::save

    /********************************************************************
     *  function CountyMarriageReport::delete                           *
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

        // get the key of the table row referenced by this record
        $sqlParms           = array();
        if ($this->former)
        {           // record has been changed
            $sqlParms['regdomain']  = $this->former['m_regdomain'];
            $sqlParms['volume']     = $this->former['m_volume'];
            $sqlParms['reportNo']   = $this->former['m_reportno'];
        }           // record has been changed
        else
        {           // record is unchanged
            $sqlParms['regdomain']  = $this->row['m_regdomain'];
            $sqlParms['volume']     = $this->row['m_volume'];
            $sqlParms['reportNo']   = $this->row['m_reportno'];
        }           // record is unchanged
        $sqlParmsText           = print_r($sqlParms, true);

        // delete all associated records
        if (!$this->needInsert)
        {       // only delete if already in database
            // do not delete the record if it is referenced from
            // other records
            $check  = "SELECT COUNT(*) FROM CountyMarriages " .
                                "WHERE M_RegDomain=:regdomain AND " .
                                "M_Volume=:volume AND " .
                                "M_ReportNo=:reportNo";
            $stmt   = $connection->prepare($check);
            $checkText  = debugPrepQuery($check, $sqlParms);
            if ($stmt->execute($sqlParms))
            {
                if ($debug)
                    $warn   .= "<p>CountyMarriageReports.inc: " . __LINE__ .
                                   " $checkText</p>\n";
                $row    = $stmt->fetch(PDO::FETCH_NUM);
                if ($row && $row[0] > 0)
                {
                    if ($xml)
                        print "<msg>" .
        "Cannot delete record because there are records in CountyMarriages " .
        "which are contained in this report. </msg>\n";
                    if ($debug)
                    $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
        "Cannot delete record because there are records in CountyMarriages " .
        "which are contained in this report. </p>\n";
                
                    return 0;
                }
            }
            else
            {       // error checking matches
                print "<p>$checkText</p>\n";
                print "<p class='message'>" .
                        print_r($stmt->errorInfo(),true) . 
                        "</p>\n";
                return 0;
            }       // error checking matches

            // the report is unreferenced, so delete it
            $delete = "DELETE FROM CountyMarriageReports " .
                                "WHERE M_RegDomain=:regdomain AND " .
                                "M_Volume=:volume AND " .
                                "M_ReportNo=:reportNo";
            $stmt   = $connection->prepare($delete);
            $deleteText = debugPrepQuery($delete, $sqlParms);
            if ($stmt->execute($sqlParms))
            {       // successful delete
                $count      = $stmt->rowCount();
                if ($debug)
                    $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
                                 " delete='$deleteText'</p>\n";

                if ($xml)
                    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

                // log the update
                $this->log($delete,
                           $sqlParms);

                return $count;
            }       // successful delete
            else
            {       // error updating record
                $warn   .= "<p>CountyMarriageReport.inc: " . __LINE__ .
                           "delete='$deleteText', error=" .
                           print_r($stmt->errorInfo(),true) . "</p>\n";
                error_log($warn);
                return 0;
            }       // error updating record
        }       // only delete if already in database
        return 0;   // no records deleted
    }       // CountyMarriageReport::delete
}       // class CountyMarriageReport 
