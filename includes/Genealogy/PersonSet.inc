<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  PersonSet.inc                                                       *
 *                                                                      *
 *  Definition of a class representing a set of individuals in a        *
 *  genealogical database.  This class provides access to the           *
 *  information in the table tblIR.                                     *
 *                                                                      *
 *  History:                                                            *
 *      2017/12/10      created                                         *
 *      2019/01/03      replace call to static Surname::getNextSurname  *
 *                      with new Surname(">=$surname")                  *
 *      2019/03/29      make given name search slightly looser by       *
 *                      ignoring the last character in longer names     *
 *      2020/07/28      do surname REGEXP against tblNR                 *
 *      2021/01/01      improve performance of surname searches         *
 *      2021/03/11      optionally generate explain for performance     *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Nickname.inc';

/************************************************************************
 *  class PersonSet                                                     *
 *                                                                      *
 *  Definition of a class implementing behavior for a set of records    *
 *  about individuals within a genealogy database.                      *
 *                                                                      *
 ************************************************************************/

class PersonSet extends RecordSet
{
    /********************************************************************
     *  function PersonSet::__construct                                 *
     *                                                                  *
     *  Construct an instance of PersonSet based upon the search        *
     *  parameters.                                                     *
     *                                                                  *
     *  Input:                                                          *
     *      $parms          associative array of lookup parameters      *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms     = null)
    {
        global      $msg;
        global      $debug;
        global      $warn;

        $orderSet   = false;
        $strposB    = false;
        $strposD    = false;
        $strposER   = false;
        $strposNX   = false;

        foreach($parms  as $name => $value)
        {
            $split  = explode('.', str_replace('`','',$name));
            if (count($split) >= 2)
            {           // table-qualified field name
                $table  = $split[0];
                switch($table)
                {
                    case 'EBirth':
                    {
                        $strposB    = true;
                        break;
                    }

                    case 'EDeath':
                    {
                        $strposD    = true;
                        break;
                    }

                    case 'tblER':
                    {
                        $strposER   = true;
                        break;
                    }

                    case 'tblNX':
                    {
                        $strposNX   = true;
                        break;
                    }

                }
            }           // table-qualified field name
            else
            {           // unqualified field name
                $fieldLc    = strtolower($name);
                switch($fieldLc)
                {
                    case 'order':
                    {
                        $orderSet   = true;
                        break;
                    }

                    case 'surname':
                    case 'givenname':
                    {
                        $strposNX   = true;
                        break;
                    }

                    case 'occupation':
                    case 'residence':
                    case 'religion':
                    case 'nationality':
                    case 'ethnicity':
                    {
                        $strposER   = true;
                        break;
                    }

                }       // switch on parameters
            }           // unqualified field name
        }       // loop through parameters
        if (!$orderSet)
            $parms['order'] = 'tblIR.Surname,' .
                                  'tblIR.GivenName,' .
                                  'COALESCE(EBirth.EventSD,' .
                                           'tblIR.BirthSD,' .
                                           '-99999999)';

        $parms['groupby']   = 'tblIR.IDIR, tblIR.ID, EBirth.EventD, EDeath.EventD, tblNX.Surname, tblNX.GivenName, tblNX.`Order`, EBirth.EventSD, tblIR.BirthSD';
        // identify joined tables referenced by the WHERE expression
        $tableName  = 'tblIR' .
                   ' JOIN tblNX ON tblNX.IDIR=tblIR.IDIR' .
                   ' JOIN tblNR ON tblNR.Surname=tblNX.Surname' .
                   ' LEFT JOIN tblER AS EBirth ON ' .
                        '(EBirth.IDIR=tblIR.IDIR AND EBirth.IDType=0 AND ' .
                                'EBirth.IDET=3 AND EBirth.Preferred=1)' .
                   ' LEFT JOIN tblER AS EDeath ON ' .
                        '(EDeath.IDIR=tblIR.IDIR AND EDeath.IDType=0 AND ' .
                                'EDeath.IDET=6 AND EDeath.Preferred=1)';

        // if requester searching for specific event
        if ($strposER)
            $tableName  .=
                   " JOIN tblER ON (tblER.IDIR=tblIR.IDIR AND tblER.IDType=0)";

        // if where expression depends upon event records then
        parent::__construct($tableName,
                            $parms,
                            "tblIR.*, " .
                                "EBirth.EventD AS EBirthD, " .
                                "EDeath.EventD AS EDeathD, " .
                                "tblNX.Surname AS indexsurname, " .
                                "tblNX.GivenName AS indexgivenname, " .
                                "tblNX.`Order` AS indextype");
        if ($this->information['count'] > 1000 && $this->limit > 1000)
        {
            $this->limit    = 0;    // suppress loading records
        }
    }       // function PersonSet::__construct

    /********************************************************************
     *  function PersonSet::getWhere                                    *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *                  if the value is itself an associative array     *
     *                  then it is evaluated as an expression which is  *
     *                  combined by the operator OR with any other      *
     *                  entries which are associative arrays.           *
     *                  The values of most fields may be an array       *
     *                  in which cases the match is to any of the       *
     *                  values.                                         *
     *                  'givenpfx' specifies a match to the left-most   *
     *                  portion of the given name                       *
     *                  'incmarried' if true specifies to also search   *
     *                  for married surnames                            *
     *                  'loose' if true specifies to perform a loose    *
     *                  search for name                                 *
     *                  'treename' specifies the name of the family     *
     *                  tree to search within, default ''               *
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
            throw new Exception("PersonSet::getWhere:: parms=" .
                                print_r($this->parms, true));

        $where                  = '';
        $and                    = 'WHERE ';
        $or                     = '';
        $needRBracket           = false;
        $loose                  = false;
        $incMarried             = false;
        $surname                = null;
        $givennames             = null;
        $treename               = '';
        $message                = '';

        if (!is_array($this->sqlParms))
            $this->sqlParms     = array();

        foreach($this->parms as $fldname => $value)
        {           // loop through all parameters
            if (!is_string($fldname) &&
                is_array($value) &&
                count($value) > 0)
            {           // comparisons to be combined by or
                $where      .= $and;
                $orExpr     = $value;
                if (count($orExpr) > 1)
                {
                    $where  .= '(';
                    $closeBrak  = ')';
                }
                else
                    $closeBrak  = '';
                foreach($orExpr as $fldname => $value)
                {
                    if (strpos('.', $fldname) !== false)
                        $where      .= $or . "$fldname=?";
                    else
                        $where      .= $or . "tblIR.`$fldname`=?";
                    $this->sqlParms[]       = $value;
                    $or         = ' OR ';
                }
                $where          .= $closeBrak;
                $closeBrak      = '';
                $or         = '';
                $and            = ' AND ';
            }           // comparisons to be combined by or
            else
            {           // comparisons combined by and
                $fieldLc        = strtolower($fldname);
                switch($fieldLc)
                {
                    case 'loose':
                    case 'incmarried':
                    case 'treename':
                    case 'excidir':
                    case 'excidmr':
                    case 'soundslike':
                    case 'givenpfx':
                    case 'occupation':
                    case 'residence':
                    case 'religion':
                    case 'nationality':
                    case 'ethnicity':
                    case 'birthmin':
                    case 'birthmax':
                        break;

                    default:
                        $fieldLc    = Person::getRealFieldName($fldname);
                        break;
                }

                switch($fieldLc)
                {       // act on specific field name
                    case 'loose':
                    {           // loose comparison for names
                        // value should normally be boolean
                        if (is_string($value))
                        {       // string value
                            $loose      = strtolower($value) == 'y';
                        }       // string value
                        else
                        if ($value)
                        {
                            $loose      = true;
                        }
                        break;
                    }       // loose comparison for names

                    case 'incmarried':
                    {       // include married names
                        if ($value === true ||
                            (is_string($value) && strtolower($value) == 'y'))
                            $incMarried = true;
                        break;
                    }       // include married names

                    case 'treename':
                    {       // limit to specific family tree
                        if (is_string($value))
                        {
                            $comma  = strpos($value,",");
                            if (is_int($comma))
                                $treename   = explode(",",$value);
                            else
                                $treename   = $value;
                        }
                        else
                        if (is_array($value))
                        {
                            if (count($value) > 1)
                                $treename   = $value;
                            else
                            if (count($value) == 1)
                                $treename   = $value[0];
                        }
                        else
                            $message    .= "Invalid `$fldname` value " .
                                            print_r($value, true) . ' ';
                        break;
                    }       // limit to specific family tree

                    case 'id':
                    case 'idir':
                    {       // primary key
                        $expression = $this->getWhereInt('tblNX.IDIR',
                                                              $value,
                                                              $this->sqlParms);
                        if (strlen($expression) > 0)
                        {
                            $where  .= $and . $expression;
                            $and    = ' AND ';
                        }
                        break;
                    }       // primary key

                    // IDIR to exclude for match
                    // do not merge an individual with him/herself,
                    // and do not merge with
                    // any individual already known not to be the individual
                    case 'excidir':
                    {
                        $where      .= "$and tblNX.IDIR != $value";
                        $and        = ' AND ';
                        $getParms   = array('idir' => $value);
                        $list       = new DontMergeEntrySet($getParms);
                        foreach($list as $entry)
                        {
                            if ($entry->get('idirleft') == $value)
                                $where  .= "$and tblNX.IDIR!=" . $entry->get('idirright');
                            else
                                $where  .= "$and tblNX.IDIR!=" . $entry->get('idirleft');
                        }
                        break;
                    }           // excidir

                    // if request to look for child to add to a family,
                    // exclude existing children of family from results
                    case 'excidmr':
                    {
                        $where      .= "$and NOT EXISTS (SELECT * FROM tblCR WHERE tblCR.IDIR=tblIR.IDIR AND IDMR=$value)";
                        $and        = ' AND ';
                        break;
                    }           // excidmr

                    case 'surname':
                    {       // surname field
                        $surname    = $value;
                        break;
                    }       // surname field

                    case 'soundslike':
                    {       // surname soundex field
                        // because the SQL SOUNDEX algorithm is different
                        // from the SOUNDEX algorithm implemented by PHP and
                        // all other languages, SQL must itself be used to
                        // convert the input value.  So specify:
                        // 'soundslike' => $surname and not
                        // 'soundslike' => soundex($surname)
                        if (is_array($value))
                        {       // list of values
                            $where  .= $and . "(";
                            $or = '';
                            for($i = 0; $i < count($value); $i++)
                            {
                                $where      .= $or .
                                        "tblNR.`SoundsLike`=LEFT(SOUNDEX(?),4)";
                                $this->sqlParms[]   = $value[$i];
                                $or     = ' OR ';
                            }
                            $where      .= ')';
                            $and    = ' AND ';
                        }       // list of values
                        else
                        if (is_string($value) && strlen($value) > 0)
                        {       // non-empty string
                            $where      .= $and .
                                        'tblNR.`SoundsLike`=LEFT(SOUNDEX(?),4)';
                            $this->sqlParms[]       = $value;
                        }       // non-empty string
                        else
                            $message    .= "Invalid `$fldname` value '$value' ";
                        $and    = ' AND ';
                        break;
                    }       // surname soundex field

                    case 'givenname':
                    {       // given name
                        $givennames         = $value;
                        break;
                    }       // given name field

                    case 'givenpfx':
                    {       // leading portion of given name
                        if (is_array($value))
                        {       // list of values
                            if (count($value) == 0 ||
                                substr($value[0],0,1) == '[')
                                break;
                            $where  .= $and;
                            $or     = '(';
                            $actualCount= 0;
                            for($i = 0; $i < count($value); $i++)
                            {
                                $givenName  = $value[$i];
                                if (ctype_alpha(substr($givenName,0,1)))
                                {   // only handle valid names
                                    $actualCount++;
                                    $where  .= $or .
                                                "LEFT(tblNX.`GivenName`,?)=?";
                                    $this->sqlParms[]   = strlen($givenName);
                                    $this->sqlParms[]   = $givenName;
                                    $or = ' OR ';
                                }   // only handle valid names
                            }
                            if ($actualCount > 0)
                            {       // at least one valid given name
                                $where  .= ')';
                                $and    = ' AND ';
                            }       // at least one valid given name
                            else
                            {       // remove operator from end
                                $where  = substr($where, 0,
                                                 strlen($where) - strlen($and));
                            }       // remove operator from end
                        }       // list of values
                        else
                        {       // string
                            $where  .= $and . "LEFT(tblNX.`GivenName`,?)=?";
                            $this->sqlParms[]   = strlen($value);
                            $this->sqlParms[]   = $value;
                        }       // string
                        $and    = ' AND ';
                        break;
                    }       // leading portion of given name field

                    case 'fsid':
                    case 'prefix':
                    case 'title':
                    case 'namenote':
                    case 'chrterm':
                    case 'birthnote':
                    case 'chrisnote':
                    case 'deathnote':
                    case 'buriednote':
                    case 'baptismnote':
                    case 'endownote':
                    case 'confirmationnote':
                    case 'initiatorynote':
                    case 'fslinks':
                    case 'userref':
                    case 'ancestralref':
                    case 'notes':
                    case 'references':
                    case 'medical':
                    case 'deathcause':
                    case 'relations':
                    case 'intellishare':
                    case 'stmp1':
                    case 'ppexclude':
                    case 'dna':
                    {       // text fields
                        $tfldname   = "`tblIR`.`$fldname`";
                        if (is_array($value))
                        {       // list of values
                            $where  .= $and . "(";
                            $or     = '';
                            for($i = 0; $i < count($value); $i++)
                            {
                                $where      .= $or . $tfldname . "=?";
                                $this->sqlParms[]   = $value[$i];
                                $or     = ' OR ';
                            }
                            $where  .= ')';
                        }       // list of values
                        else
                        {       // string
                            $expression = $this->getWherePattern($tfldname,
                                                             $value);
                            if (strlen($expression) > 0)
                            {
                                $where  .= $and . $expression;
                                $and    = ' AND ';
                            }
                        }       // string
                        $and    = ' AND ';
                        break;
                    }       // text fields

                    case 'birthd':
                    case 'chrisd':
                    case 'deathd':
                    case 'buriedd':
                    case 'baptismd':
                    case 'endowd':
                    case 'confirmationd':
                    case 'initiatoryd':
                    {       // encoded date field
                        $where      .= $and . "`$fldname`=?";
                        $this->sqlParms[]   = $value[$i];
                        $and        = ' AND ';
                        break;
                    }       // encoded date field

                    case 'birthsd':
                    {       // yyyymmdd date field
                        if (is_array($value) && count($value) == 2)
                        {       // range of dates
                            $startRange = $value[0];
                            $endRange   = $value[1];
                            $where  .= $and . "((tblIR.`BirthSD`>=? AND " .
                                             "tblIR.`BirthSD`<=?)";
                            $this->sqlParms[]   = $startRange;
                            $this->sqlParms[]   = $endRange;
                            $where  .= " OR (EBirth.`EventSD`>=? AND " .
                                                "EBirth.`EventSd`<=?))";
                            $this->sqlParms[]   = $startRange;
                            $this->sqlParms[]   = $endRange;
                            $and        = ' AND ';
                        }       // range of dates
                        else
                        if (is_int($value) ||
                            (is_string($value) &&
                            (preg_match("/^\s*-?\d+\s*$/", $value) == 1)))
                        {       // numeric
                            $where  .= $and . "tblIR.`$fldname`=?";
                            $this->sqlParms[]   = $value;
                            $and    = ' AND ';
                        }       // numeric
                        else
                        {       // unsupported
                            $message    .= "Invalid `$fldname` value " .
                                            print_r($value, true) . ' ';
                        }       // unsupported
                        break;
                    }       // yyyymmdd date field

                    case 'chrissd':
                    case 'deathsd':
                    case 'buriedsd':
                    case 'baptismsd':
                    case 'endowsd':
                    case 'confirmationsd':
                    case 'initiatorysd':
                    case 'added':
                    case 'updated':
                    {       // yyyymmdd date field
                        if (is_array($value) && count($value) == 2)
                        {       // range of dates
                            $startRange     = $value[0];
                            $endRange       = $value[1];
                            $where      .= $and .
                                  "tblIR.`$fldname`>=? AND tblIR.`$fldname`<=?";
                            $this->sqlParms[]       = $startRange;
                            $this->sqlParms[]       = $endRange;
                            $and        = ' AND ';
                        }       // range of dates
                        else
                        if (is_int($value) ||
                            (is_string($value) &&
                            (preg_match("/^\s*-?\d+\s*$/", $value) == 1)))
                        {       // numeric
                            $where      .= $and . "tblIR.`$fldname`=?";
                            $this->sqlParms[]       = $value;
                            $and        = ' AND ';
                        }       // numeric
                        else
                        {       // unsupported
                            $message    .= "Invalid `$fldname` value '" .
                                    print_r($value, true) . "' ";
                        }       // unsupported
                        break;
                    }       // yyyymmdd date field

                    case 'gender':
                    case 'idlrbirth':
                    case 'idlrchris':
                    case 'idlrdeath':
                    case 'idlrburied':
                    case 'cremated':
                    case 'idarbirth':
                    case 'idarchris':
                    case 'idardeath':
                    case 'idarburied':
                    case 'living':
                    case 'baptismkind':
                    case 'idtrbaptism':
                    case 'ldsb':
                    case 'idtrendow':
                    case 'ldse':
                    case 'confirmationkind':
                    case 'idtrconfirmation':
                    case 'ldsc':
                    case 'idtrinitiatory':
                    case 'ldsi':
                    case 'templetag':
                    case 'fssync':
                    case 'fsdups':
                    case 'fsordinance':
                    case 'idmrpref':
                    case 'idmrparents':
                    case 'idar':
                    case 'ancinterest':
                    case 'decinterest':
                    case 'tag1':
                    case 'tag2':
                    case 'tag3':
                    case 'tag4':
                    case 'tag5':
                    case 'tag6':
                    case 'tag7':
                    case 'tag8':
                    case 'tag9':
                    case 'taggroup':
                    case 'taganc':
                    case 'tagdec':
                    case 'savetag':
                    case 'qstag':
                    case 'srchtag':
                    case 'srchtagigi':
                    case 'srchtagrg':
                    case 'srchtagfs':
                    case 'rgexclude':
                    case 'remindertag':
                    case 'remindertagdeath':
                    case 'treenum':
                    case 'ltmp1':
                    case 'ltmp2':
                    case 'alreadyused':
                    case 'ppcheck':
                    case 'imported':
                    case 'nevermarried':
                    case 'directline':
                    case 'colortag':
                    case 'private':
                    {       // positive integer fields
                        $expression = $this->getWhereInt('tblIR.'.$fldname,
                                                              $value);
                        if (strlen($expression) > 0)
                        {
                            $where  .= $and . $expression;
                            $and    = ' AND ';
                        }
                        break;
                    }       // positive integer fields

                    case 'occupation':
                    {
                        $where      .= $and . "tblER.IDET=47 AND " .
                                           self::eventDescComp($value);
                        $and    = ' AND ';
                        break;
                    }       // occupation from event

                    case 'residence':
                    {
                        $where      .= $and . "tblER.IDET=54 AND " .
                                           self::eventDescComp($value);
                        $and    = ' AND ';
                        break;
                    }       // residence from event

                    case 'religion':
                    {
                        $where      .= $and . "tblER.IDET=53 AND " .
                                           self::eventDescComp($value);
                        $and    = ' AND ';
                        break;
                    }       // religion from event

                    case 'nationality':
                    {
                        $where      .= $and . "tblER.IDET=64 AND " .
                                           self::eventDescComp($value);
                        $and    = ' AND ';
                        break;
                    }       // nationality from event

                    case 'ethnicity':
                    {
                        $where      .= $and . "tblER.IDET=66 AND " .
                                           self::eventDescComp($value);
                        $and    = ' AND ';
                        break;
                    }       // ethnicity from event

                    case 'birthmin':
                    {       // birth year to match within range
                        $where  .= $and . "(FLOOR(tblIR.BirthSD/10000)>=?";
                        $this->sqlParms[]   = $value;
                        $where  .= ' OR ' . "FLOOR(EBirth.EventSD/10000)>=?)";
                        $this->sqlParms[]   = $value;
                        $and    = ' AND ';
                        break;
                    }       // birth year to match within range

                    case 'birthmax':
                    {       // birth year to match within range
                        $where  .= $and . "(FLOOR(tblIR.BirthSD/10000)<=?";
                        $this->sqlParms[]   = $value;
                        $where  .= ' OR ' . "FLOOR(EBirth.EventSD/10000)<=?)";
                        $this->sqlParms[]   = $value;
                        $and    = ' AND ';
                        break;
                    }       // birth year to match within range

                }       // act on specific field name
            }           // comparisons combined by and
        }           // loop through all parameters

        // construct comparison using both surname and given name
        if (is_string($surname) || is_array($surname))
        {           // surname field present
            if (is_array($surname) && count($surname) > 0)
            {           // array
                if (count($surname) == 2 &&
                    (is_null($surname[1]) ||
                     (strtolower($surname[1]) > strtolower($surname[0]))))
                {       // range
                    if (is_null($surname[1]))
                        $lastsurname    = null;
                    else
                        $lastsurname    = strtolower($surname[1]);

                    if (trim($surname[0]) == '')
                        $surnameParms   = array('surname' => '>= ');
                    else
                        $surnameParms   = array('surname' => '>='. $surname[0]);
                    $surnameRec = new Surname($surnameParms);
                    $surname    = strtolower($surnameRec->get('surname'));

                    if (strlen($surname) > 0)
                    {       // starting surname contains at least one char
                        if (is_string($givennames) && strlen($givennames) > 0)
                        {   // given name supplied
                            $where  .= "$and(tblNX.Surname=? AND tblNX.GivenName>=?)";
                            $this->sqlParms[]   = $surname;
                            $this->sqlParms[]   = $givennames;
                        }   // given name supplied
                        else
                        if (is_array($givennames))
                        {
                            $where      .= "$and(tblNX.Surname=? AND (";
                            $this->sqlParms[]       = $surname;
                            $conj       = '';
                            foreach($givennames as $givenname)
                            {
                                $glen               = strlen($givenname);
                                if ($glen > 3)
                                    $givenname      = substr($givenname,
                                                             0,
                                                             $glen - 1);
                                $where      .= "$conj tblNX.GivenName>=?";
                                $this->sqlParms[]   = $givenname;
                                $conj       = ' OR ';
                            }
                            $where  .= '))';
                        }
                        else
                        {   // only surname supplied
                            if (is_string($lastsurname) &&
                                strlen($lastsurname) > 0)
                            {       // limit search
                                $where      .= $and . 'tblNX.Surname>=?';
                                $this->sqlParms[]       = $surname;
                                $where      .= ' AND tblNX.Surname<=?';
                                $this->sqlParms[]   = $lastsurname;
                            }       // limit search
                            else
                            {
                                $where      .= $and . 'tblNX.Surname=?';
                                $this->sqlParms[]       = $surname;
                            }
                        }   // only surname supplied
                    }       // starting surname contains at least one char
                    else
                    {       // surname is empty
                        if (is_string($givennames) && strlen($givennames) > 0)
                        {   // given name supplied
                            $where  .= "$and (tblNX.Surname='' AND " .
                                            'tblNX.GivenName>=?';
                            $this->sqlParms[]   = $givennames;
                            $where  .= ")";
                        }   // given name supplied
                        else
                        if (is_array($givennames) && count($givennames) > 0)
                        {
                            $where  .= "$and (tblNX.Surname='' AND (";
                            $or         = '';
                            foreach($givennames as $givenname)
                            {
                                $where      .= $or . 'tblNX.GivenName>=?';
                                $this->sqlParms[]   = $givenname;
                                $or     = ' OR ';
                            }
                            $where      .= "))";
                        }
                        else
                        {   // only empty surname supplied
                            $where  .= $and . " tblNX.Surname=(SELECT MIN(Surname) from tblNR)";
                        }   // only empty surname supplied
                    }           // surname is empty
                    $givennames = null;
                }       // range starting with surname
                else
                {       // list of surnames
                    $where      .= $and;
                    if (count($surname) > 1)
                        $or     = '(';
                    else
                        $or     = '';
                    for($i = 0; $i < count($surname); $i++)
                    {
                        if (is_null($surname[$i]))  // ignore null entries
                            continue;

                        $where      .= $or;
                        if ($loose)
                        {
                            $expression = $this->getSurnameChk('tblNR',
                                                            $surname[$i]);
                            $where  .= $expression;
                        }
                        else
                        {
                            $where  .= "tblNX.`Surname`=?";
                            $this->sqlParms[]   = $surname[$i];
                        }
                        $or     = ' OR ';
                    }
                    if (count($surname) > 1)
                        $where  .= ')';
                }       // list of surnames
            }           // array of values
            else
            if (is_string($surname))
            {           // surname is string
                if ($loose)
                {       // standard loose comparison
                    $expression = $and . $this->getSurnameChk('tblNR',
                                                              $surname);
                    $where      .= $expression;
                }       // standard loose comparison
                else
                {       // close comparison for names
                    $where  .= $and . "tblNX.`Surname`=";
                    if (strlen($surname) > 0)
                    {       // surname not empty string
                        $where          .= "?";
                        $this->sqlParms[]   = $surname;
                    }       // surname not empty string
                    else
                        $where      .= "(SELECT MIN(Surname) FROM tblNR)";
                }       // close comparison for names
            }           // surname is string

            $and    = ' AND ';

            if (is_array($givennames) && count($givennames) > 0)
            {           // list of values
                $or     = "$and(";
                $closeBrak  = '';
                foreach($givennames as $givenname)
                {       // loop through givennames
                    if (strlen($givenname) > 0 &&
                        ctype_alpha(substr($givenname,0,1)))
                    {       // only handle valid names
                        $closeBrak  = ')';
                        $where  .= $or . $this->givenNameComp($givenname,
                                                              $loose);
                        $or = ' OR ';
                    }       // only handle valid names
                }       // loop through givennames
                $where      .= $closeBrak;
            }           // list of values
            else
            if (is_string($givennames))
            {           // string
                $where      .= $and . '(' .
                                   $this->givenNameComp($givennames, $loose) .
                                   ')';
            }           // string

            $and        = ' AND ';
            if (is_string($treename))
            {           // treename is string
                if ($treename != '*')
                {
                    $where      .= " AND tblNX.TreeName=?";
                    $this->sqlParms[]   = $treename;
                }
            }           // treename is string
            else
            if (is_array($treename))
            {           // array of tree names
                $or     = " AND (";
                foreach($treename as $tname)
                {       // loop through tree names
                    $where      .= $or . "tblNX.TreeName=?";
                    $this->sqlParms[]   = $tname;
                    $or         = " OR ";
                }       // loop through tree names
                $where      .= ')';
            }           // array of tree names
        }           // surname field present

        // if not requested to include married names but using tblNX
        // in search, exclude married names from search
        if (!$incMarried && is_int(strpos($where , 'tblNX')))
        {           // restrict to birth surnames
            $where      .= $and . "tblNX.`Order`>=0";
        }           // restrict to birth surnames

        if (strlen($message) > 0)
            throw new Exception("PersonSet::getWhere: " .
            $message);

        if ($debug)
        {
            $explain    = "EXPLAIN SELECT * FROM {$this->table} $where";
            $stmt               = $connection->prepare($explain);
            $queryText          = debugPrepQuery($explain, $this->sqlParms);
            $warn   .= "<p>PersonSet::getWhere: $queryText</p>\n";
            if ($stmt->execute($this->sqlParms))
            {           // success
                $warn           .= "<table style=\"border-style: solid; border-width: thin;\">\n";
                $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $needHeader     = true;
                foreach($result as $row)
                {
                    if ($needHeader)
                    {
                        $warn       .= "    <tr>\n";
                        foreach($row as $name => $cell)
                            $warn   .= "        <th style=\"border-style: solid; border-width: thin;\">$name</td>\n";
                        $warn       .= "    </tr>\n";
                        $needHeader = false;
                    }
                    $warn           .= "    <tr>\n";
                    foreach($row as $cell)
                        $warn       .= "        <td style=\"font-weight: normal; border-style: solid; border-width: thin;\">$cell</td>\n";
                    $warn           .= "    </tr>\n";
                }
                $warn               .= "</table>\n";
            }
            else
            {           // error performing query
                $warn  .= "PersonSet::getWhere: \"$queryText\", <br>" .
                    print_r($stmt->errorInfo(),true) .
                    $parmsText . "<br>" .
                    ", parms=" . print_r($this->sqlParms,true) . "\n";
            }           // error performing query
        }
        return $where;
    }       // PersonSet::getWhere

    /********************************************************************
     *  function PersonSet::givenNameComp                               *
     *                                                                  *
     *  Determine the appropriate expression for comparing the given    *
     *  name to a particular value.                                     *
     *                                                                  *
     *  Parameters:                                                     *
     *          $givenName      given name to search for                *
     *          $loose          true for match component of name        *
     *                                                                  *
     *  Returns:                                                        *
     *      Expression to append to WHERE clause                        *
     ********************************************************************/
    protected function givenNameComp($givenName, $loose)
    {
        $expression = '';

        if ($loose)
        {       // loose comparison
            $nickname       = new Nickname($givenName);
            $nicknamepref   = $nickname->getPrefix();
            if (is_array($nicknamepref))
            {       // array
                $or         = '';
                foreach($nicknamepref as $nickname)
                {
                    $expression     .= $or . "tblNX.GivenName REGEXP ?";
                    $this->sqlParms[]   = "[[:<:]]" . $nickname;
                    $or         = ' OR ';
                }
            }       // array
            else
            {       // string
                $expression     .= "tblNX.GivenName REGEXP ?";
                $this->sqlParms[]   = "[[:<:]]" . $nicknamepref;
            }       // string`
        }       // loose comparison
        else
        if (substr($givenName, 0, 2) == '>=')
        {
            $expression     = "tblNX.`GivenName`>=?";
            $this->sqlParms[]   = substr($givenName,2);
        }
        else
        {       // exact match
            $expression     = "tblNX.`GivenName`=?";
            $this->sqlParms[]   = $givenName;
        }       // exact match
        return $expression;
    }       // function PersonSet::givenNameComp

    /********************************************************************
     *  function PersonSet::eventDescComp                               *
     *                                                                  *
     *  Determine the appropriate expression for comparing the          *
     *  description field of an event                                   *
     *                                                                  *
     *  Parameters:                                                     *
     *      $eventDesc      event description to search for             *
     *                                                                  *
     *  Returns:                                                        *
     *      Expression to append to WHERE clause                        *
     ********************************************************************/
    protected function eventDescComp($eventDesc)
    {
        if (is_array($eventDesc))
        {       // list of values
            if (count($eventDesc) == 0)
                return '';
            if (count($eventDesc) > 1)
                $where      = '(';
            else
                $where      = '';
            $or         = '';
            for($i = 0; $i < count($eventDesc); $i++)
            {
                $where      .= $or .
                                   $this->getWherePattern('tblER.Description',
                                                          $eventDesc[$i]);
                $or     = ' OR ';
            }
            if (count($eventDesc) > 1)
                $where      .= ')';
        }       // list of values
        else
        if (strlen($eventDesc) > 0)
        {
            $where      = $this->getWherePattern('tblER.Description',
                                                         $eventDesc);
        }
        else
            $where      = "tblER.Description=''";
        return $where;
    }       // static function Person::eventDescComp

}       // class PersonSet
