<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  DontMergeEntry.inc                                                  *
 *                                                                      *
 *  Definition of a class representing a don't merge entry in a         *
 *  genealogical database.                                              *
 *  This class provides access to the information in a row of the       *
 *  table tblDM.                                                        *
 *                                                                      *
 *      CREATE TABLE `tblDM` (                                          *
 *          `IDIRLeft` INT(10) UNSIGNED NOT NULL DEFAULT '0',           *
 *          `IDIRRight` INT(10) UNSIGNED NOT NULL DEFAULT '0',          *
 *          PRIMARY KEY (`IDIRLeft`,`IDIRRight`) )                      *
 *          ENGINE=InnoDB DEFAULT CHARSET=utf8                          *
 *                                                                      *
 *  History:                                                            *
 *      2011/09/31      created                                         *
 *      2012/01/13      rename classes to have upper case character     *
 *      2012/10/20      standardize initialization of new record        *
 *      2013/01/29      invalid table name used                         *
 *                      add static method getList                       *
 *      2013/08/09      base class renamed to Record                    *
 *      2014/12/28      send trace info to $warn                        *
 *                      enclose comment blocks                          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/08/17      rename class to DontMergeEntry                  *
 *      2017/09/14      add methods getDontMergeEntries,                *
 *                      updateDontMergeEntries, and                     *
 *                      deleteDontMergeEntries                          *
 *      2017/10/13      change class LegacyIndiv to class Person        *
 *      2017/10/30      complete migration to prepared statements       *
 *      2017/12/18      add support for temporary fields                *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/05/06      add method offsetExists                         *
 *      2019/07/18      use Person::getPerson                           *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Person.inc';

/************************************************************************
 *  class DontMergeEntry                                                *
 *                                                                      *
 *  Definition of a class implementing behavior for don't merge entry   *
 *  records within a genealogy database.                                *
 *  Each record contains a pair of IDIRs which identify two individuals *
 *  who are very similar in external characteristics, such as name and  *
 *  date of birth, but which are not to be merged accidentally because  *
 *  the similarity is a coincidence.                                    *
 ************************************************************************/

class DontMergeEntry extends Record
{
    /********************************************************************
     *  DontMergeEntry::$personLeft                                     *
     *                                                                  *
     ********************************************************************/
    private     $personLeft     = null;

    /********************************************************************
     *  DontMergeEntry::$personRight                                    *
     *                                                                  *
     ********************************************************************/
    private     $personRight    = null;

    /********************************************************************
     *  DontMergeEntry::$initRow                                        *
     *                                                                  *
     *  Static array containing default values for a new record         *
     *                                                                  *
     *  This is not actually used for this class, but is retained for   *
     *  compatibility with the other classes derived from Record        *
     ********************************************************************/
    protected static    $initRow    = array(
                'idirleft'  => 0,
                'idirright' => 0);

    /********************************************************************
     *  DontMergeEntry::$defaultOrder                                   *
     *                                                                  *
     *  Definition of default order for records retrieved by RecordSet  *
     ********************************************************************/
    protected static    $defaultOrder   = 'IdirLeft';

    /********************************************************************
     *  function DontMergeEntry::__construct                            *
     *                                                                  *
     *  Given the unique pair of numeric identifier make information on *
     *  a don't merge entry record available.                           *
     *                                                                  *
     *  Input:                                                          *
     *      $idleft     identifier of an individual                     *
     *                  or associative array containing idirleft and    *
     *                  idirright                                       *
     *      $idright    identifier of another individual or null        *
     *                  if first parameter is an associative array      *
     *                                                                  *
     *  Returns object containing don't merge entry record.             *
     ********************************************************************/
    function __construct($idleft, $idright = null)
    {
        global  $connection;
        global  $debug;
        global  $warn;

        // action depends upon type of first parameter
        if ((is_int($idleft) || ctype_digit($idleft)) &&
            (is_int($idright) || ctype_digit($idright)) &&
            $idleft > 0 && $idright > 0 && $idleft != $idright)
        {       // numeric identifiers of record
            $parms      = array('idirleft'  => $idleft,
                                'idirright' => $idright);
            $dbrow      = $parms;
            // to avoid pointless duplicates ensure that in all
            // records IDIRLeft is less than IDIRRight
            if ($idright < $idleft)
            {
                $dbrow['idirleft']  = $idright;
                $dbrow['idirright'] = $idleft;
            }
        }
        else
        if (is_array($idleft))
        {
            $parms      = $idleft;
            if (array_key_exists('idirleft', $parms) &&
                array_key_exists('idirright', $parms))
            {
                $idleft     = $parms['idirleft'];
                $idright    = $parms['idirright'];
                $dbrow      = $parms;
                // to avoid pointless duplicates ensure that in all
                // records IDIRLeft is less than IDIRRight
                if ($idright < $idleft)
                {
                    $dbrow['idirleft']  = $idright;
                    $dbrow['idirright'] = $idleft;
                }
            }
            else
                throw new Exception("DontMergeEntry::__construct  " .
                            "bad parameters: " . print_r($idleft, true));
        }
        else
            throw new Exception("DontMergeEntry::__construct  " .
                            "bad parameters: ($idleft,$idright)");

        // construct the query of the don't merge entry table
        // This SELECT ensures that if accidentally the same pair of
        // IDIRs has been entered in different orders, only the pair
        // with IDIRLeft less than IDIRRight is retrieved
        $query  = 'SELECT * FROM tblDM ' .
                        'WHERE (IDIRLeft=:idleft AND IDIRRight=:idright) OR ' .
                              '(IDIRLeft=:idright AND IDIRRight=:idleft) ' .
                        'ORDER BY IDIRLeft ASC LIMIT 1';
        $sqlParms   = array('idleft'    => $idleft,
                            'idright'   => $idright);

        // query the database
        $stmt       = $connection->prepare($query);
        $queryText  = debugPrepQuery($query, $sqlParms);
        if ($stmt->execute($sqlParms))
        {       // success

            $result     = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($debug)
                $warn   .= "<p>DontMergeEntry::__construct: " .
                           "$queryText</p>\n";

            if (count($result) > 0)
            {       // found don't merge entry record
                $this->needInsert   = false;
            }       // found don't merge entry record
            else
            {       // create new record
                $this->needInsert   = true;
            }       // create new record

            // invoke constructor of base class
            parent::__construct($dbrow,
                                'tblDM');

            // diagnostic output if debug is set
            $this->dump('DontMergeEntry Record constructed:');
        }       // success
        else
        {       // error performing query
            throw new Exception(
                        "DontMergeEntry::__construct: '$queryText', " .
                            print_r($stmt->errorInfo(),true));
        }       // error performing query
    }       // DontMergeEntry::__construct

    /********************************************************************
     *  function DontMergeEntry::getIdirLeft                            *
     *                                                                  *
     *  Get numeric identifier of the first individual in the           *
     *  DontMergeEntry record.                                          *
     ********************************************************************/
    function getIdirLeft() { return $this->row['idirleft']; }

    /********************************************************************
     *  function DontMergeEntry::getPersonLeft                          *
     *                                                                  *
     *  Get instance of Person for the first individual in the          *
     *  DontMergeEntry record.                                          *
     ********************************************************************/
    function getPersonLeft()
    {
        if (is_null($this->personLeft))
            $this->personLeft   =
                Person::getPerson($this->row['idirleft']);
        return $this->personLeft;
    }

    /********************************************************************
     *  function DontMergeEntry::getIdirRight                           *
     *                                                                  *
     *  Get the numeric identifier of the other individual in the       *
     *  DontMergeEntry record.                                          *
     ********************************************************************/
    function getIdirRight() { return $this->row['idirright']; }

    /********************************************************************
     *  function DontMergeEntry::getPersonRight                         *
     *                                                                  *
     *  Get instance of Person for the second individual in the         *
     *  DontMergeEntry record.                                          *
     ********************************************************************/
    function getPersonRight()
    {
        if (is_null($this->personRight))
            $this->personRight  =
                Person::getPerson($this->row['idirright']);
        return $this->personRight;
    }

    /********************************************************************
     *  function DontMergeEntry::get                                    *
     *                                                                  *
     *  Get the value of a field by name                                *
     *                                                                  *
     *  Parameters:                                                     *
     *      $name       field name                                      *
     *                                                                  *
     *  Special Field Names:                                            *
     *  Field names from the Person record are obtained from the left   *
     *  Person, as are Person field names prefixed by the letter "l" or *
     *  by "left.".                                                     *
     *  Person field names prefixed by the letter "r" or by "right.".   *
     *  are obtained from the right Person.                             *
     ********************************************************************/
    function get($field)
    {
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {           // act on specific field names
            case 'idirleft':
            {
                return $this->row['idirleft'];
            }           // idirleft

            case 'idirright':
            {
                return $this->row['idirright'];
            }           // idirright

            case 'surname':
            case 'lsurname':
            case 'left.surname':
            {
                if (array_key_exists('lsurname', $this->row))
                    return $this->row['lsurname'];
                else
                {
                    $indiv  = $this->getPersonLeft();
                    return $indiv->get('surname');
                }
            }           // left.surname

            case 'givenname':
            case 'lgivenname':
            case 'left.givenname':
            {
                if (array_key_exists('lgivenname', $this->row))
                    return $this->row['lgivenname'];
                else
                {
                    $indiv  = $this->getPersonLeft();
                    return $indiv->get('givenname');
                }
            }           // left.givenname

            case 'birthdate':
            case 'lbirthdate':
            case 'left.birthdate':
            {
                $indiv  = $this->getPersonLeft();
                return $indiv->getBirthDate();
            }           // left.birthdate

            case 'deathdate':
            case 'ldeathdate':
            case 'left.deathdate':
            {
                $indiv  = $this->getPersonLeft();
                return $indiv->getDeathDate();
            }           // left.deathdate

            case 'rsurname':
            case 'right.surname':
            {
                if (array_key_exists('rsurname', $this->row))
                    return $this->row['rsurname'];
                else
                {
                    $indiv  = $this->getPersonRight();
                    return $indiv->get('surname');
                }
            }           // right.surname

            case 'rgivenname':
            case 'right.givenname':
            {
                if (array_key_exists('rgivenname', $this->row))
                    return $this->row['rgivenname'];
                else
                {
                    $indiv  = $this->getPersonRight();
                    return $indiv->get('givenname');
                }
            }           // right.givenname

            case 'rbirthdate':
            case 'right.birthdate':
            {
                $indiv  = $this->getPersonRight();
                return $indiv->getBirthDate();
            }           // right.birthdate

            case 'rdeathdate':
            case 'right.deathdate':
            {
                $indiv  = $this->getPersonRight();
                return $indiv->getDeathDate();
            }           // right.deathdate

            default:
            {       // other fields
                if (substr($fieldLc, 0, 5) == 'left.')
                {
                    $indiv  = $this->getPersonLeft();
                    if ($indiv->offsetExists(substr($fieldLc, 5)))
                        return $indiv->get(substr($fieldLc, 5));
                }
                else
                if (substr($fieldLc, 0, 6) == 'right.')
                {
                    $indiv  = $this->getPersonRight();
                    if ($indiv->offsetExists(substr($fieldLc, 6)))
                        return $indiv->get(substr($fieldLc, 6));
                }
                else
                if (substr($fieldLc, 0, 1) == 'l')
                {
                    $indiv  = $this->getPersonLeft();
                    if ($indiv->offsetExists(substr($fieldLc, 1)))
                        return $indiv->get(substr($fieldLc, 1));
                }
                else
                if (substr($fieldLc, 0, 1) == 'r')
                {
                    $indiv  = $this->getPersonRight();
                    if ($indiv->offsetExists(substr($fieldLc, 1)))
                        return $indiv->get(substr($fieldLc, 1));
                }

                return parent::get($fieldLc);
            }       // other fields
        }           // act on specific field names
    }       // DontMergeEntry::get

    /********************************************************************
     *  function DontMergeEntry::offsetExists                           *
     *                                                                  *
     *  Part of the implementation of the interface ArrayAccess.        *
     *  Returns true if the field name is in use in either the record   *
     *  or the array of temporary fields.                               *
     *      isset($record[$field]);                                     *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      field name.  This is case insensitive and can   *
     *                  be either an actual field name or a synonym     *
     *                  defined by class::$translate.  It can also      *
     *                  be a temporary field name for the life of       *
     *                  this object.                                    *
     ********************************************************************/
    public function offsetExists($field)
    {
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($field, static::$translate))
            $fieldLc    = static::$translate[$fieldLc];
        switch($fieldLc)
        {           // act on specific field names
            case 'idirleft':
            case 'idirright':
            case 'surname':
            case 'lsurname':
            case 'left.surname':
            case 'givenname':
            case 'lgivenname':
            case 'left.givenname':
            case 'birthdate':
            case 'lbirthdate':
            case 'left.birthdate':
            case 'deathdate':
            case 'ldeathdate':
            case 'left.deathdate':
            case 'rsurname':
            case 'right.surname':
            case 'rgivenname':
            case 'right.givenname':
            case 'rbirthdate':
            case 'right.birthdate':
            case 'rdeathdate':
            case 'right.deathdate':
            {       // other fields
                return true;
            }       // other fields

            default:
            {       // other fields
                if (substr($fieldLc, 0, 5) == 'left.')
                {
                    $indiv  = $this->getPersonLeft();
                    if ($indiv->offsetExists(substr($fieldLc, 5)))
                        return true;
                }
                else
                if (substr($fieldLc, 0, 6) == 'right.')
                {
                    $indiv  = $this->getPersonRight();
                    if ($indiv->offsetExists(substr($fieldLc, 6)))
                        return true;
                }
                else
                if (substr($fieldLc, 0, 1) == 'l')
                {
                    $indiv  = $this->getPersonLeft();
                    if ($indiv->offsetExists(substr($fieldLc, 1)))
                        return true;
                }
                else
                if (substr($fieldLc, 0, 1) == 'r')
                {
                    $indiv  = $this->getPersonRight();
                    if ($indiv->offsetExists(substr($fieldLc, 1)))
                        return true;
                }

                if (array_key_exists($fieldLc, $this->extras))
                    return true;
                else
                {
                    $warn   .= "<p>DontMergeEntry::offsetExists: field='$field', fieldLc='$fieldLc' not found</p>\n";
                    return false;
                }
            }
        }           // act on specific field names
    }       // public function DontMergeEntry::offsetExists

}       // class DontMergeEntry
