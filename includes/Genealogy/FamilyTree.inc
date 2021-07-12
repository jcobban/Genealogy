<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  FamilyTree.inc                                                      *
 *                                                                      *
 *  Definition of a class representing a Family Tree. It primarily      *
 *  exists to provide factory methods.                                  *
 *                                                                      *
 *      $tree       = new FamilyTree();     // done in initialization   *
 *      $person     = $tree->getPerson($idir);                          *
 *      $location   = $tree->getLocation($idlr);                        *
 *      $family     = $tree->getFamily($idmr);                          *
 *      $event      = $tree->getEvent($ider);                           *
 *                                                                      *
 *  This class also provides access to the information in the table     *
 *  tblHR.                                                              *
 *                                                                      *
 *      CREATE TABLE `tblHR` (                                          *
 *         `Item`       VARCHAR(10) NOT NULL DEFAULT '',                *
 *         `Setting`    VARCHAR(255) DEFAULT NULL,                      *
 *          PRIMARY KEY (`Item`) )                                      *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8                              *
 *                                                                      *
 *      $tree       = new FamilyTree();                                 *
 *      foreach($tree as $item => $setting) { ...}                      *
 *      $setting    = $tree->get($item);                                *
 *                                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2019/11/23      created.                                        *
 *                      See LegacyHeader.inc for previous history.      *
 *      2021/03/09      add methods isValid, dump, getErrors            *
 *                      to match interface for Record                   *
 *                      remove Exception throw in constructor           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  class FamilyTree                                                    *
 *                                                                      *
 *  Definition of a class implementing behavior for a Family Tree.      *
 ************************************************************************/

class FamilyTree implements Iterator
{
    /********************************************************************
     *  tree->table                                                     *
     *                                                                  *
     *  Associative array containing all key/value pairs in header.     *
     ********************************************************************/
    private $table;

    /********************************************************************
     *  FamilyTree::$initRow                                            *
     *                                                                  *
     *  Static array containing default values for a new record         *
     *                                                                  *
     *  This is not actually used for this class, but is retained for   *
     *  compatibility with the other classes derived from Record        *
     ********************************************************************/
    public static   $initRow    = array('item'      => '',
                                        'setting'   => '');

    /********************************************************************
     *  FamilyTree::$info                                               *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    public static   $info   = array(
                                'table'         => 'tblHR',
                                'name'          => 'Header',
                                'prime'         => 'item',
                                'srvmg'         => false,
                                'fldcount'      => 2,
                                'order'         => 'Item',
                                'classname'     => 'FamilyTree');

    /********************************************************************
     *  tree->familyIndex                                               *
     *                                                                  *
     *  Associative array containing existing instances of Family       *
     ********************************************************************/
    private $familyIndex    = array();

    /********************************************************************
     *  tree->eventIndex                                                *
     *                                                                  *
     *  Associative array containing existing instances of Event        *
     ********************************************************************/
    private $eventIndex    = array();

    /********************************************************************
     *  tree->templeIndex                                               *
     *                                                                  *
     *  Associative array containing existing instances of Temple       *
     ********************************************************************/
    private $templeIndex    = array();
    private $templeNames    = array();

    /********************************************************************
     *  tree->addressIndex                                              *
     *                                                                  *
     *  Associative array containing existing instances of Address      *
     ********************************************************************/
    private $addressIndex    = array();

    /********************************************************************
     *  tree->citationIndex                                             *
     *                                                                  *
     *  Associative array containing existing instances of Citation     *
     ********************************************************************/
    private $citationIndex    = array();

    /********************************************************************
     *  $this->msg                                                      *
     *                                                                  *
     *  Any error messages accumulated by constructor.                  *
     *  See FamilyTree::getErrors                                       *
     ********************************************************************/
    protected   $msg                = '';

    /********************************************************************
     *  function FamilyTree::__construct                                *
     *                                                                  *
     *  Create an instance of FamilyTree                                *
     *                                                                  *
     ********************************************************************/
    function __construct()
    {
        global  $debug;
        global  $warn;
        global  $connection;

        // construct the query of the header table
        $query              = "SELECT Item, Setting FROM tblHR";
        
        // query the database
        if ($debug)
            $warn           /= "<p>FamilyTree::__construct: $query</p>";
        $stmt               = $connection->query($query);
        if ($stmt)
        {               // success
            $this->table    = array();  
            $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($result as $dbrow)
            {           // copy all entries into table
                $this->table[$dbrow['item']]    = $dbrow['setting'];
            }           // copy all entries into table
        }               // success
        else
        {               // error retrieving record row
            $this->msg      = "FamilyTree::__construct: '$query' result=" . 
                                print_r($connection->errorInfo(),true);
            $this->table    = array();  
        }               // error retrieving record row
    }       // FamilyTree::__construct

    /********************************************************************
     *  function FamilyTree::getPerson                                  *
     *                                                                  *
     *  Factory method to get an instance of Person                     *
     *                                                                  *
     *  Input:                                                          *
     *      $idir       unique numeric key                              *
     ********************************************************************/
    function getPerson($idir)
    {
        return Person::getPerson($idir);
    }           // FamilyTree::getPerson

    /********************************************************************
     *  function FamilyTree::getLocation                                *
     *                                                                  *
     *  Factory method to get an instance of Location                   *
     *                                                                  *
     *  Input:                                                          *
     *      $idlr       either unique numeric key or unique name        *
     ********************************************************************/
    function getLocation($idlr)
    {
        return Location::getLocation($idlr);
    }           // FamilyTree::getLocation

    /********************************************************************
     *  function FamilyTree::getFamily                                  *
     *                                                                  *
     *  Factory method to get an instance of Family                     *
     *                                                                  *
     *  Input:                                                          *
     *      $idmr       unique numeric key                              *
     ********************************************************************/
    function getFamily($idmr)
    {
        if (!array_key_exists($idmr, $this->familyIndex))
            $this->familyIndex[$idmr]    = new Family(array('idmr' => $idmr));
        return $this->familyIndex[$idmr];
    }           // FamilyTree::getFamily

    /********************************************************************
     *  function FamilyTree::getEvent                                   *
     *                                                                  *
     *  Factory method to get an instance of Event                      *
     *                                                                  *
     *  Input:                                                          *
     *      $ider       unique numeric key                              *
     ********************************************************************/
    function getEvent($ider)
    {
        if (!array_key_exists($ider, $this->eventIndex))
            $this->eventIndex[$ider]    = new Event(array('ider' => $ider));
        return $this->eventIndex[$ider];
    }           // FamilyTree::getEvent

    /********************************************************************
     *  function FamilyTree::getTemple                                  *
     *                                                                  *
     *  Factory method to get an instance of Temple                     *
     *                                                                  *
     *  Input:                                                          *
     *      $idtr       unique numeric key                              *
     ********************************************************************/
    function getTemple($idtr)
    {
        if (!array_key_exists($idtr, $this->templeIndex))
            $this->templeIndex[$idtr]   = new Temple(array('idtr' => $idtr));
        return $this->templeIndex[$idtr];
    }           // FamilyTree::getTemple

    /********************************************************************
     *  function FamilyTree::getAddress                                 *
     *                                                                  *
     *  Factory method to get an instance of Address                    *
     *                                                                  *
     *  Input:                                                          *
     *      $idar       unique numeric key                              *
     ********************************************************************/
    function getAddress($idar)
    {
        if (!array_key_exists($idar, $this->addressIndex))
            $this->addressIndex[$idar]  = new Address(array('idar' => $idar));
        return $this->addressIndex[$idar];
    }           // FamilyTree::getAddress

    /********************************************************************
     *  function FamilyTree::getSource                                  *
     *                                                                  *
     *  Factory method to get an instance of Source                     *
     *                                                                  *
     *  Input:                                                          *
     *      $idsr       either unique numeric key or unique name        *
     ********************************************************************/
    function getSource($idsr)
    {
        return Source::getSource($idsr);
    }           // FamilyTree::getSource

    /********************************************************************
     *  function FamilyTree::getCitation                                *
     *                                                                  *
     *  Factory method to get an instance of Citation                   *
     *                                                                  *
     *  Input:                                                          *
     *      $idsx       unique numeric key                              *
     ********************************************************************/
    function getCitation($idsx)
    {
        if (!array_key_exists($idsx, $this->citationIndex))
            $this->citationIndex[$idsx] = 
                                new Citation(array('idsx' => $idsx));
        return $this->citationIndex[$idsx];
    }           // FamilyTree::getCitation

    /********************************************************************
     *  function FamilyTree::get                                        *
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
            $msg    .= "FamilyTree::get: called with item='$item', " .
                           gettype($item) . ". \n";
            return '';
        }
    }       // FamilyTree::get

    /********************************************************************
     *  function FamilyTree::toXml                                      *
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
            $top    = 'header';

        if (is_null($top))
            $retval = '';
        else
            $retval = "    <$top>\n";
        foreach($this->table as $key => $value)
        {
            if (substr($key, 1, 1) == '_')
                $key    = substr($key, 2);
            $retval .= "\t<$key>" . xmlentities($value) . "</$key>\n";
        }
        if ($top !== null)
            $retval .= "    </$top>\n";

        if ($print)
            print $retval;
        return $retval;
    }       // FamilyTree::toXml

    /********************************************************************
     *  function FamilyTree::toJson                                     *
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

        $retval .= "\n    }\n";
        if ($print)
            print $retval;
        return $retval;
    }       // FamilyTree::toJson

    /********************************************************************
     *  function FamilyTree::isValid                                    *
     *                                                                  *
     *  Get an indication of whether or not the constructor completed   *
     *  normally.                                                       *
     *                                                                  *
     *  Returns:                                                        *
     *      true        the parameters passed in the constructor        *
     *                  were valid.                                     *
     *      false       there was/were an error/errors in the           * 
     *                  parameters to the constructor.                  *
     ********************************************************************/
    function isValid()
    {
        return strlen($this->msg) == 0;
    }       // function FamilyTree::isValid

    /********************************************************************
     *  function FamilyTree::dump                                       *
     *                                                                  *
     *  If debug output is enabled, display a diagnostic dump of the    *
     *  contents of a database record in the web page.                  *
     *                                                                  *
     *  Input:                                                          *
     *      $label      descriptive identifier for record               *
     *      $force      if true output even if $debug false             *
     *                  This is not needed because you can write:       *
     *                      $warn   = $record->dump();                  *
     *                                                                  *
     *  Return:                                                         *
     *      string containing trace output as HTML                      *
     ********************************************************************/
    function dump($label    = null,
                  $force    = false)
    {
        global $debug;
        global $warn;

        // ensure that there is a label to use for the display
        if (!is_string($label) || strlen($label) == 0)
            $label              = 'Family Tree';

        $output                 = '';
        if (is_array($this->table))
        {                       // record is initialized
            $output             .= "<p class='label'>$label</p>\n";
            $output             .= "<table border='1'>\n";
            foreach($this->table as $key => $value)
            {
                $output         .= "  <tr>\n";
                $output         .= "    <th class='label'>\n";
                $output         .= "`$key`";
                $output         .= "    </th>\n";
                $output         .= "    <td class='dataleft'>\n";
                if (is_null($value))
                    $output     .= 'null';
                else
                if (strlen($value) > 100)
                    $output     .= htmlspecialchars(substr($value, 0, 75) .
                                    '...' . substr($value, -22));
                else
                    $output     .= htmlspecialchars($value);
                $output         .= "    </td>\n";
                $output         .= "  </tr>\n";
            }   // loop through keys

            $output             .= "</table>\n";
        }                       // record is initialized
        else
            $output .= "<p class='message'>record not initialized</p>\n";

        if (($debug || $force))
            $warn               .= $output;
        return $output;
    }       // function FamilyTree::dump

    /********************************************************************
     *  function FamilyTree::getErrors                                  *
     *                                                                  *
     *  Get any errors detected by the constructor.  These errors       *
     *  prevent updates being applied to the database but are           *
     *  accumulated as an alternative to throwing an exception.         *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing error messages                            *
     ********************************************************************/
    function getErrors()
    {
        return $this->msg;
    }       // function FamilyTree::getErrors

    /********************************************************************
     *  function FamilyTree::rewind                                     *
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
     *  function FamilyTree::current                                    *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the value of the current element of the "array"          *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function current()
    {
        $row            = current($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function FamilyTree::key                                        *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the key of the current element of the "array".           *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function key() 
    {
        $row            = key($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function FamilyTree::next                                       *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Advance to the next element of the "array".                     *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    public function next() 
    {
        $row            = next($this->table);
        return $row;
    }
  
    /********************************************************************
     *  function FamilyTree::valid                                      *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Returns true if the iterator is valid.                          *
     *                                                                  *
     *  Returns:                                                        *
     *      boolean                                                     *
     ********************************************************************/
    public function valid()
    {
        $key            = key($this->table);
        $row            = ($key !== NULL && $key !== FALSE);
        return $row;
    }

}       // class FamilyTree 

