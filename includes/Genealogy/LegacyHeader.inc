<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  LegacyHeader.inc                                                    *
 *                                                                      *
 *  Definition of a class representing a header entry in a genealogical *
 *  database as implemented by Legacy 7 Family Tree.  This class        *
 *  provides access to the information in the table tblHR.              *
 *                                                                      *
 *      CREATE TABLE `tblHR` (                                          *
 *         `Item`       VARCHAR(10) NOT NULL DEFAULT '',                *
 *         `Setting`    VARCHAR(255) DEFAULT NULL,                      *
 *          PRIMARY KEY (`Item`) )                                      *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *      $header     = new LegacyHeader();                               *
 *      foreach($header as $item => $setting) { ...}                    *
 *      $setting    = $header->get($item);                              *
 *                                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/23      connection made global                          *
 *      2011/02/01      replace call to doQuery                         *
 *      2012/01/13      rename classes to have upper case character     *
 *      2013/08/09      correct exception identifier                    *
 *      2013/11/15      do not construct if no database connection      *
 *      2014/09/19      add method toXml to support getRecordXml.php    *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2017/09/11      implement interface Iterator                    *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  LegacyHeader                                                        *
 *                                                                      *
 *  Definition of a class implementing behavior for header entry        *
 *  records within a genealogy database as implemented by Legacy 7      *
 *  Family Tree Software.                                               *
 ************************************************************************/

class LegacyHeader implements Iterator
{
    /********************************************************************
     *  header->table                                                   *
     *                                                                  *
     *  Associative array containing all key/value pairs in header.     *
     ********************************************************************/
    private $table;

    /********************************************************************
     *  LegacyHeader::$initRow                                          *
     *                                                                  *
     *  Static array containing default values for a new record         *
     *                                                                  *
     *  This is not actually used for this class, but is retained for   *
     *  compatibility with the other classes derived from Record        *
     ********************************************************************/
    public static   $initRow    = array(
                        'item'      => '',
                        'setting'   => '');

    /********************************************************************
     *  LegacyHeader::$info                                             *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    public static   $info   = array(
                'table'     => 'tblHR',
                'name'      => 'Header',
                'prime'     => 'item',
                'srvmg'     => false,
                'fldcount'  => 2,
                'order'     => 'Item',
                'classname' => 'LegacyHeader');

    /********************************************************************
     *  DontMergeEntry::$defaultOrder                                   *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    public static   $defaultOrder   = 'Item';

    /********************************************************************
     *  function LegacyHeader::__construct                              *
     *                                                                  *
     *  Given the identifier extract information about a header entry.  *
     *                                                                  *
     *  Returns object containing header record.                        *
     ********************************************************************/
    function __construct()
    {
        global  $debug;
        global  $connection;

        // construct the query of the header table
        $query  = "SELECT Item, Setting FROM tblHR";
        
        // query the database
        if ($debug)
            print "<p>$query</p>";
        $stmt       = $connection->query($query);
        if ($stmt)
        {       // success
            $this->table    = array();  
            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($result as $dbrow)
            {       // copy all entries into table
                $this->table[$dbrow['item']]    = $dbrow['setting'];
            }       // copy all entries into table
        }       // success
        else
        {       // error retrieving record row
            throw new Exception(
                        "LegacyHeader::__construct: '$query' result=" . 
                        print_r($connection->errorInfo(),true));
        }       // error retrieving record row
    }       // LegacyHeader::__construct

    /********************************************************************
     *  function LegacyHeader::get                                      *
     *                                                                  *
     *  Get the setting value corresponding to an item name.            *
     ********************************************************************/
    function get($item)
    {
        global  $msg;

        // action depends upon type of first parameter
        if (array_key_exists($item, $this->table))
        {       // numeric identifier of record
            return $this->table[$item];
        }       // item name
        else
        {
            $msg    .= "LegacyHeader::get: called with item='$item', " .
                           gettype($item) . ". \n";
            return '';
        }
    }       // LegacyHeader::getItem

    /********************************************************************
     *  function LegacyHeader::toXml                                    *
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
    function toXml($top     = 'header',
                   $print   = true,
                   $options = 0)
    {
        global  $debug;

        // ensure that there is a label to use for the top element
        if (!is_string($top) || strlen($top) == 0)
            $top    = 'tblHR';

        if (is_null($top))
            $retval = '';
        else
            $retval = "    <$top>\n";
        foreach($this->table as $key => $value)
        {
            if (substr($key, 1, 1) == '_')
                $key    = substr($key, 2);
            $retval .= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
        }
        if ($top !== null)
            $retval .= "    </$top>\n";

        if ($print)
            print $retval;
        return $retval;
    }       // LegacyHeader::toXml

    /********************************************************************
     *  function LegacyHeader::toJson                                   *
     *                                                                  *
     *  Emit the contents of the object as JSON.                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/
    function toJson($print  = true,
                    $options    = 0)
    {
        $retval     = '{';
        $comma      = '';
        foreach($this->table as $key => $value)
        {
            if (substr($key, 1, 1) == '_')
                $key    = substr($key, 2);
            $retval .= $comma . "\t\"$key\":\t" . json_encode($value);
            $comma  = ",\n";
        }
        $retval .= "\n}\n";

        if ($print)
            print $retval;
        return $retval;
    }       // LegacyHeader::toJson

    /********************************************************************
     *  function LegacyHeader::rewind                                   *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Reposition to the first element of the "array".                 *
     *                                                                  *
     *  Returns:                                                        *
     *          String                                                  *
     ********************************************************************/
    public function rewind()
    {
        reset($this->table);
    }
  
    /********************************************************************
     *  function LegacyHeader::current                                  *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the value of the current element of the "array"          *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function current()
    {
        $row = current($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function LegacyHeader::key                                      *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the key of the current element of the "array".           *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function key() 
    {
        $row = key($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function LegacyHeader::next                                     *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Advance to the next element of the "array".                     *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function next() 
    {
        $row = next($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function LegacyHeader::valid                                    *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Returns true if the iterator is valid.                          *
     *                                                                  *
     *  Returns:                                                        *
     *      boolean                                                     *
     ********************************************************************/
    public function valid()
    {
        $key = key($this->table);
        $row = ($key !== NULL && $key !== FALSE);
        return $row;
    }

}       // class LegacyHeader 

