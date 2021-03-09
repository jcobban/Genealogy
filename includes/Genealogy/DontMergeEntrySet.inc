<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  DontMergeEntrySet.inc                                               *
 *                                                                      *
 *  Definition of a class representing a set of DontMergeEntry records. *
 *                                                                      *
 *      CREATE TABLE `tblDM` (                                          *
 *          `IDIRLeft` INT(10) UNSIGNED NOT NULL DEFAULT '0',           *
 *          `IDIRRight` INT(10) UNSIGNED NOT NULL DEFAULT '0',          *
 *          PRIMARY KEY (`IDIRLeft`,`IDIRRight`) )                      *
 *          ENGINE=InnoDB DEFAULT CHARSET=utf8                          *
 *                                                                      *
 *  Examples:                                                           *
 *      $allEntries     = new DontMergeEntrySet();                      *
 *      $entries        = new DontMergeEntrySet(array('surname'         *
 *                                              => '^Smith$'));         *
 *      $entries        = new DontMergeEntrySet(array('right.surname'   *
 *                                              => '^Smith$'));         *
 *      $entries        = new DontMergeEntrySet(array('givenname'       *
 *                                              => 'John'));            *
 *                                                                      *
 *  History:                                                            *
 *      2017/10/30      created                                         *
 *      2019/01/06      move to namespace Genealogy                     *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class DontMergeEntrySet                                             *
 *                                                                      *
 *  Definition of a class recording information about a set of          *
 *  DontMergeEntry records for the family tree.                         *
 *                                                                      *
 ************************************************************************/

class DontMergeEntrySet extends RecordSet
{

    /********************************************************************
     *  function DontMergeEntrySet::__construct                         *
     *                                                                  *
     *  Construct an instance of DontMergeEntrySet based upon the       *
     *  search parameters.                                              *
     *                                                                  *
     *  Input:                                                          *
     *      $parms              associative array of lookup parameters  *
     *          'idirleft'              match on left IDIR              *
     *          'idirright'             match on right IDIR             *
     *          'idir'                  match on either IDIR            *
     *          'surname'               match on left surname           *
     *          'left.surname'          match on left surname           *
     *          'givenname'             match on left given name        *
     *          'left.givenname'        match on left given name        *
     *          'right.surname'         match on right surname          *
     *          'right.givenname'       match on right givenname        *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms     = null,
                         $order     = "`Left`.`Surname`")
    {
        $parms['order']     = $order;
        parent::__construct('tblDM ' .
                    'JOIN tblIR AS `Left` ON IdirLeft=`Left`.Idir '.
                    'JOIN tblIR AS `Right` ON IdirRight=`Right`.Idir',
                            $parms);
    }       // DontMergeEntrySet::__construct

    /********************************************************************
     *  function DontMergeEntrySet::getWhere                            *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     *      A zero length string is returned if the input array is      *
     *      empty, otherwise the string starts with the word WHERE      *
     ********************************************************************/
    protected function getWhere()
    {
        global  $debug;
        global  $warn;
        global  $connection;

        if (!is_array($this->parms))
            throw new Exception("DontMergeEntrySet::getWhere:: parms=" .
                            print_r($this->parms, true));   

        $where      = '';
        $and        = 'WHERE ';

        if (!is_array($this->sqlParms))
            $this->sqlParms     = array();

        foreach($this->parms as $fldname => $value)
        {           // loop through all parameters
            $fieldLc        = strtolower($fldname);

            switch($fieldLc)
            {           // act on specific fields
                case 'idirleft':
                case 'idirright':
                {       // references to individuals
                    if ($value instanceof Person)
                        $value      = $value->getIdir();

                    $expression = $this->getWhereInt($fieldLc,
                                          $value,
                                          $this->sqlParms);
                    if (strlen($expression) > 0)
                    {       // list of values
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // list of values
                    break;
                }       // references to individuals

                case 'idir':
                {       // references to individuals
                    if ($value instanceof Person)
                        $value      = $value->getIdir();

                    if (intval($value) != 0)
                    {
                        $where      .= "$and (`IDIRLeft`=? OR `IDIRRight`=?)";
                        $this->sqlParms[]   = $value;
                        $this->sqlParms[]   = $value;
                        $and        = ' AND ';
                    }
                    break;
                }       // references to individuals

                case 'surname':
                case 'left.surname':
                {
                    $expression = $this->getWherePattern('`Left`.Surname',
                                         $value,
                                         $this->sqlParms);
                    if (strlen($expression) > 0)
                    {       // list of values
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // list of values
                    break;
                }

                case 'givenname':
                case 'left.givenname':
                {
                    $expression = $this->getWherePattern('`Left`.GivenName',
                                         $value,
                                         $this->sqlParms);
                    if (strlen($expression) > 0)
                    {       // list of values
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // list of values
                    break;
                }

                case 'right.surname':
                {
                    $expression = $this->getWherePattern('`Right`.Surname',
                                         $value,
                                         $this->sqlParms);
                    if (strlen($expression) > 0)
                    {       // list of values
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // list of values
                    break;
                }

                case 'right.givenname':
                {
                    $expression = $this->getWherePattern('`Right`.GivenName',
                                         $value,
                                         $this->sqlParms);
                    if (strlen($expression) > 0)
                    {       // list of values
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }       // list of values
                    break;
                }

                default:
                {
                    $warn   .= "<p>DontMergeEntrySet::getWhere: " .
                               "Unsupported field name `$fldname`.</p>\n";
                    break;
                }

            }           // act on specific fields
        }           // loop through all parameters

        return $where;
    }       // DontMergeEntry::getWhere

}       // class DontMergeEntrySet
