<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Nickname.inc                                                        *
 *                                                                      *
 *  Definition of a class representing an alternate spelling of a       *
 *  given name record in a genealogical database.                       *
 *  This class provides     access to the information in a row of the   *
 *  table Nickname.                                                     *
 *                                                                      *
 *  CREATE TABLE Nicknames (                                            *
 *      `Nickname`      VARCHAR(64) DEFAULT '',                         *
 *              the way a given name is spelled in some documents       *
 *      PRIMARY KEY `NickName`,                                         *
 *      `Prefix`        VARCHAR(255) DEFAULT '',                        *
 *              the prefix, or comma-separated list of prefixes that    *
 *              are used to search for matches in tblNX                 *
 *      `GivenName`     VARCHAR(255) DEFAULT '')                        *
 *              the "standard" spelling of the given name               *
 *      `Gender`        TINYINT(3) UNSIGNED DEFAULT NULL                *
 *              0 = male, 1 = female, null = non-specific               *
 *      ENGINE=InnoDb DEFAULT CHARSET=utf8;                             *
 *                                                                      *
 *  History:                                                            *
 *      2017/12/10      created                                         *
 *      2017/12/18      do not crap out on zero length given name       *
 *      2017/12/18      add support for temporary fields                *
 *      2019/01/09      move to namespace Genealogy                     *
 *      2019/01/21      add column gender                               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Nickname                                                      *
 *                                                                      *
 *  Definition of a class implementing behavior for Nickname records    *
 *  within a genealogy database.                                        *
 ************************************************************************/

class Nickname extends Record
{

    /********************************************************************
     *  Nickname::$initRow                                              *
     *                                                                  *
     *  array containing default values for a new record                *
     ********************************************************************/
    protected static    $initRow    = array('nickname'  => '',
                                            'prefix'    => '',
                                            'givenname' => '',
                                            'gender'    => null);

    /********************************************************************
     *  Nickname::$defaultOrder                                         *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'Nickname';

    /********************************************************************
     *  function Nickname::__construct                                  *
     *                                                                  *
     *  Given a given name from a document extract information about    *
     *  the name including how to search for it in the family tree.     *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      o array('nickname' => $givenname) to get the    *
     *                    specific record                               *
     *                  o a database record presented as an array       *
     *                                                                  *
     *              There is no way to create an                        *
     *              uninitialized instance of this class                *
     *                                                                  *
     *  Returns object containing nickname record.                      *
     *                                                                  *
     *  Sets $this->msg if unable to complete function                  *
     ********************************************************************/
    function __construct($parms)
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $dbrow  = null;

        // action depends upon type of first parameter
        // convert old format calls to new format internally
        if (is_string($parms))
        {       // nickname
            $parms  = array('nickname'  => $parms);
        }       // nickname

        if (is_array($parms))
        {           // array
            if (count($parms) < count(self::$initRow))
            {           // set of search parameters
                $where              = '';
                $and                = '';
                $errors             = '';
                $nickname           = null;
                $sqlParms           = array();
                foreach($parms as $fieldname => $value)
                {       // loop through parameters
                    switch(strtolower($fieldname))
                    {       // act on specific fields
                        case 'nickname':
                        {
                            $where      .= $and . "Nickname=?";
                            $sqlParms[]     = $value;
                            $nickname       = $value;
                            $and        .= ' AND ';
                            break;
                        }   // nickname

                        default:
                        {
                            $errors   .= "Unexpected field name '$fieldname'. ";
                            break;
                        }
                    }       // act on specific fields
                }       // loop through parameters
                if (strlen($where) == 0)
                    $errors     .= "No valid parameters passed. parms=".
                                           print_r($parms, true) . '. ';
                if (strlen($errors) == 0 && strlen($where) > 0)
                {       // valid parameters
                    // construct the query of the nickname table
                    $query  = "SELECT * FROM Nicknames WHERE $where";

                    // query the database
                    $stmt   = $connection->prepare($query);
                    $queryText  = debugPrepQuery($query, $sqlParms);
                    if ($stmt->execute($sqlParms))
                    {       // execute successful
                        if ($debug)
                            $warn   .= '<p>Nickname::_construct: ' .
                                           __LINE__ ."query='$queryText'</p>\n";
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($result))
                        {   // found nickname record
                            $dbrow      = $result;
                            $needInsert     = false;
                        }   // found nickname record
                        else
                        {   // create a new nickname record
                            // replace characters that would cause
                            // string syntax errors or
                            // regexp pattern misinterpretation
                            $nickname   = str_replace(array('"','*','+','?'),
                                                  '.',
                                                  $nickname);

                            // handle given names with special conditions
                            if (strlen($nickname) == 1 ||
                                (strlen($nickname) == 2 &&
                                 substr($nickname, 1, 1) == '.'))
                            {   // initial
                                $givenpref  = substr($nickname, 0, 1);
                            }   // initial
                            else
                            if (strlen($nickname) >= 2)
                                $givenpref  = substr($nickname, 0, 2);
                            else
                                $givenpref  = $nickname;

                            $dbrow      = self::$initRow;
                            $dbrow['nickname']  = $nickname;
                            $dbrow['prefix']    = $givenpref;
                            $dbrow['givenname'] = $nickname;
                            $needInsert     = true;
                        }   // no match
                    }       // execute successful
                    else
                    {       // execute failed
                        throw new Exception("Nickname::__construct: " .
                                            "query '$queryText', result=" .
                                            print_r($stmt->errorInfo(),true));
                    }       // execute failed
                }       // valid parameters
                else
                    throw new Exception("Nickname::__construct: " .
                                        $errors);
            }           // set of search parameters
            else
            {           // complete row returned from database query
                $dbrow          = $parms;
                $needInsert     = false;
            }           // complete row returned from database query
        }           // array
        else
        {           // invalid parameter
            throw new Exception("Nickname::__construct: called with " .
                 gettype($parms) . ", value='" . print_r($parms, true) . "'");
        }           // invalid parameter

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Nicknames');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Nickname Record constructed:');
    }       // Nickname::__construct

    /********************************************************************
     *  function Nickname::getPrefix                                    *
     *                                                                  *
     *  Retrieve the prefix value for searching tblNX for matches       *
     *  Note that $nickname->get('prefix') and $nickname['prefix']      *
     *  return a string containing comma-separated values where this    *
     *  method returns an array.                                        *
     *                                                                  *
     *  Returns:                                                        *
     *      A string if only one prefix to search for or an array of    *
     *      strings if there are multiple possible prefixes to search   *
     ********************************************************************/
    function getPrefix()
    {
        $prefix     = $this->row['prefix'];
        $prefixes   = explode(',', $prefix);
        if (count($prefixes) > 1)
            return $prefixes;
        else
            return $prefix;
    }       // Nickname::getPrefix

    /********************************************************************
     *  function LegacySurName::getName                                 *
     *                                                                  *
     *  Return the descriptive name of this record.                     *
     *  This method is implemented for consistency with other classes   *
     *  which have a getName method to return a summary of the          *
     *  instance.                                                       *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing the nickname                              *
     ********************************************************************/
    function getName($options   = null)
    {
        return $this->row['nickname'];
    }       // Nickname::getName

    /********************************************************************
     *  function Nickname::toXml                                        *
     *                                                                  *
     *  Emit an XML representation of the record.                       *
     *                                                                  *
     *  Most fields in the record are represented by a child element    *
     *  with the name of the field containing the internal value as text*
     *                                                                  *
     *  Each field whose value is an instance of LegacyDate appears as  *
     *  two elements.  The internal encoded string value appears in an  *
     *  element with 'c' appended to the field name, while the element  *
     *  with the original field name contains an external textual       *
     *  (human-readable) representation of the date.  Note that the     *
     *  "sorted date" field appears with its internal value.            *
     *                                                                  *
     *  Each field whose value is an instance of Temple appears         *
     *  as two elements.  The internal numeric key value appears in     *
     *  an element with the original field name.  The external textual  *
     *  value appears in an element whose name is the original field    *
     *  name with the "idlr" prefix removed and a "loc" suffix added.   *
     *  For example the element <idtrparseal> contains the original     *
     *  numeric code while the element <parsealloc> contains the        *
     *  name of the temple.                                             *
     *                                                                  *
     *  Parameters:                                                     *
     *      $top        tag name for top node, default 'child'          *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/
    function toXml($top     = 'nickname',
                   $print   = true,
                   $options = 0)
    {
        $retval             = "<$top>\n";

        foreach($this->row as $fld => $value)
        {       // loop through all fields
            switch($fld)
            {
                case 'gender':
                {           // yes/no flags
                    if (is_null($value))
                        $retval .= "    <$fld></$fld>\n";
                    else
                    if ($value > 0)
                        $retval .= "    <$fld>F</$fld>\n";
                    else
                        $retval .= "    <$fld>M</$fld>\n";
                    break;
                }           // yes/no flags

                default:
                {       // fields passed back with no interpretation
                    $retval .= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
                    break;
                }       // fields passed back with no interpretation
            }       // switch on field name
        }       // loop through all fields

        $retval .= "</$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }       // function Nickname::toXml

    /********************************************************************
     *  function Nickname::toJson                                       *
     *                                                                  *
     *  Emit an JSON representation of the record.                      *
     *                                                                  *
     *  Each field whose value is an instance of LegacyDate appears as  *
     *  two fields.  The internal encoded string value appears in an    *
     *  field with 'c' appended to the field name, while the field      *
     *  with the original field name contains an external textual       *
     *  (human-readable) representation of the date.  Note that the     *
     *  "sorted date" field appears with its internal value.            *
     *                                                                  *
     *  Each field whose value is an instance of Temple appears         *
     *  as two fields.  The internal numeric key value appears in       *
     *  an field with the original field name.  The external textual    *
     *  value appears in an field whose name is the original field      *
     *  name with the "idlr" prefix removed and a "loc" suffix added.   *
     *  For example the field "idtrparseal" contains the original       *
     *  numeric code while the field "parsealloc" contains the          *
     *  name of the temple.                                             *
     *                                                                  *
     *  Parameters:                                                     *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      JSON representation of the object                           *
     ********************************************************************/
    function toJson($print      = true,
                    $options    = 0)
    {
        $retval     = '';
        $comma      = "{\n";

        foreach($this->row as $fld => $value)
        {       // loop through all fields
            switch($fld)
            {
                case 'gender':
                {           // yes/no flags
                    $retval .= $comma . "\t\"$fld\":\t"; 
                    $comma  = ",\n";
                    if (is_null($value))
                        $retval .= 'null';
                    else
                    if ($value > 0)
                        $retval .= '"F"';
                    else
                        $retval .= '"M"';
                    break;
                }           // yes/no flags

                default:
                {       // fields passed back with no interpretation
                    $retval .= $comma . "\t\"$fld\":\t" . 
                               json_encode($value);
                    $comma  = ",\n";
                    break;
                }       // fields passed back with no interpretation
            }       // switch on field name
        }       // loop through all fields

        if (strlen($retval) > 0)
            $retval     .= "\n}\n";
        if ($print)
            print $retval;
        return $retval;
    }       // function Nickname::toJson

}       // class Nickname

