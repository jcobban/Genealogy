<?php
namespace Genealogy;
use \PDO;
use \Exception;
use Templating\Template;
/************************************************************************
 *  Citation.inc                                                        *
 *                                                                      *
 *  Definition of a class representing a citation in a genealogical     *
 *  database.  This class provides access to the information in a row   *
 *  of the table tblSX.                                                 *
 *                                                                      *
 *   Table: tblSX - Source Detail/Citation table                        *
 *                                                                      *
 *          Name                             Type                       *
 *          IDSX                        INTEGER(10)                     *
 *          IDSR                        INTEGER(10)                     *
 *                  --> tblSR.IDSR, ID to Master Source Record          *
 *          IDIME                       INTEGER(10)                     *
 *                  --> tblIR.IDIR, ID to Individual Record or          *
 *                  --> tblNX.IDNX, ID to Alternate Name Record or      *
 *                  --> tblCR.IDCR, ID to Child Record or               *
 *                  --> tblMR.IDMR, ID to Marriage Record or            *
 *                  --> tblMR.IDER, ID to Event Record                  *
 *          Type                        TINYINT(3)                      *
 *              See the STYPE_xxx constants below                       *
 *          SrcDetail                   VARCHAR(255)                    *
 *              Source Details, page number, etc.                       *
 *          SrcPrintDetail              TINYINT(3)                      *
 *              1=Add this Detail to the Source Citation on Reports     *
 *              0=not                                                   *
 *          SrcDetText                  LONGTEXT                        *
 *              Text of Source Citation                                 *
 *          SrcPrintText                TINYINT(3)                      *
 *              1=Add this Text to the Source Citation on Reports       *
 *              0=not                                                   *
 *          SrcDetNote                  LONGTEXT                        *
 *              Comments for Source Citation                            *
 *          SrcPrintNote                TINYINT(3)                      *
 *              1=Add these Comments to the Source Citation on Reports  *
 *              0=not                                                   *
 *          SrcPrint                    TINYINT(3)                      *
 *              1=Include this Citation on Reports                      *
 *              0=not                                                   *
 *          SrcSurety                   TINYINT(3)                      *
 *              0 to 4                                                  *
 *              4=Convincing Evidence                                   *
 *              3=Almost Certain Conclusion                             *
 *              2=Probable Conclusion                                   *
 *              1=Marginal Evidence                                     *
 *              0=Have not decided yet                                  *
 *          SrcMPub                     VARCHAR(100)                    *
 *              (see Date) Recorded Date                                *
 *          EnteredSD                   INTEGER(10)                     *
 *              (see Sort Date)                                         *
 *          EnteredD                    VARCHAR(100)                    *
 *              (see Date) Recorded Date                                *
 *          FilingRef                   VARCHAR(50)                     *
 *              user filing number                                      *
 *          Order                       SMALLINT(5)                     *
 *              used as sort order.                                     *
 *          Used                        TINYINT(3)                      *
 *              temporary use, 0=no, 1=yes                              *
 *          Verified                    TINYINT(3)                      *
 *              0=No, 1=Yes                                             *
 *          Content                     LONGTEXT                        *
 *          Override                    LONGTEXT                        *
 *              Combine all three user override formats                 *
 *              separated by //                                         *
 *              For example:                                            *
 *                  Footnote o'ride//Subsequent O'ride//Bibliography O'ride*
 *          OverrideFootnote            TINYINT(3)                      *
 *              0=don't use the override paragraph,                     *
 *              1=use the override paragraph                            *
 *          OverrideSubsequent          TINYINT(3)                      *
 *              0=don't use the override paragraph,                     *
 *              1=use the override paragraph                            *
 *          OverrideBibliography        TINYINT(3)                      *
 *              0=don't use the override paragraph,                     *
 *              1=use the override paragraph                            *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/22      use common facilities of LegacyRecord           *
 *      2010/10/11      check for error after query                     *
 *      2010/10/20      changed parent constructor                      *
 *                      add set methods                                 *
 *      2010/10/23      connection made global                          *
 *      2010/11/05      return empty record for not found               *
 *      2010/11/27      adjust $inttype 6,7, & 8 to be in English       *
 *                      word order                                      *
 *      2010/12/02      accept numeric string as key                    *
 *      2011/01/10      throw exception on invalid parameters           *
 *      2012/01/14      change class name to LegacyCitation             *
 *      2012/04/12      getIdsr not working                             *
 *      2012/08/05      add setField method                             *
 *                      add setDate method                              *
 *                      add getDate method                              *
 *                      all setXxxx methods now return previous value   *
 *                      implement class specific dump method            *
 *      2012/10/02      use LegacyRecord::setField to modify record     *
 *                      contents so the save method will correctly      *
 *                      create the INSERT or UPDATE SQL command to      *
 *                      apply the changes                               *
 *      2012/10/20      standardize initialization of new record        *
 *      2013/08/09      base class renamed to Record                    *
 *      2014/01/30      initialize `order` if fact record and type      *
 *                      identified for new record                       *
 *      2014/03/08      do not create duplicate citations               *
 *      2014/03/23      add constructor by parameter list               *
 *      2014/04/13      constructor was creating new records rather     *
 *                      than updating existing record                   *
 *      2014/06/14      validate more parameters on constructor         *
 *                      permit updating existing record in constructor  *
 *                      remove static method getCitationWhere           *
 *                      use regexp match for text fields in getCitations*
 *                      ensure source object is initialized             *
 *      2014/07/04      handle zero length string for text fields in    *
 *                      getCitations                                    *
 *                      if no selection parameters in getCitations      *
 *                      return all citation records                     *
 *                      Support passing limit and offset in parameter   *
 *                      array to getCitations                           *
 *                      return total number of matches in parameter     *
 *                      array to getCitations                           *
 *                      validate parameters to getCitations             *
 *      2014/08/07      add static method updateCitations to apply      *
 *                      update to set of citations                      *
 *                      add pseudo field names IDIR, IDMR, IDER, IDCR,  *
 *                      and IDNX to encapsulate match based on both     *
 *                      IDIME and Type to static methods                *
 *      2014/08/22      if limit=0 passed to getCitations only          *
 *                      return count in parms['count']                  *
 *                      add method toXml to interpret IDSR, Type,       *
 *                      and enteredd                                    *
 *      2014/09/04      use common routine to build where for static    *
 *                      methods                                         *
 *                      $parms['enteredsd'] may be a range of dates     *
 *      2014/09/08      add method toHtml                               *
 *      2014/09/23      do not enclose order parameter in back-quotes   *
 *                      in static method getCitations to permit         *
 *                      more than one field                             *
 *                      return count and query in parms to getCitations *
 *      2014/10/13      change pattern for census citations to permit   *
 *                      omitting the space before 'div'                 *
 *      2014/10/16      move expansion of Source record in              *
 *                      XML output to end of expansion of the           *
 *                      Citation record, instead of right after         *
 *                      the IDSR field.                                 *
 *      2014/11/20      wrong IDIME value used in obtaining next order  *
 *                      value for new citation                          *
 *      2014/11/30      method save returns record with IDSX set to     *
 *                      value for duplicate record when database update *
 *                      prevented because of existing duplicate record  *
 *      2014/12/06      send trace information to $warn                 *
 *      2015/01/14      use CensusResponse.php instead of obsolete      *
 *                      QueryResponseYYYY.php                           *
 *      2015/03/07      remove deprecated shortcuts to master source    *
 *                      information                                     *
 *                      add method getRecord to get the associated      *
 *                      record containing the fact details              *
 *                      change method getName to return descriptive     *
 *                      string for the citation                         *
 *      2015/04/27      escape < and > in SQL commands displayed in     *
 *                      XML or HTML                                     *
 *      2015/07/21      add more information to getName so it can be    *
 *                      used by legacyIndiv as unique key for citation  *
 *                      add primary name note as pseudo-citation for    *
 *                      primary name to eliminate special case code     *
 *                      in legacyIndiv.php                              *
 *      2015/09/11      do not print out $warn                          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/10/07      function save did not return count              *
 *      2015/12/08      base class changed to RecordPic                 *
 *      2016/04/28      change toXML to always return the XML and       *
 *                      only print it by default                        *
 *      2016/06/06      add method getPerson                            *
 *                      add attribute $this->refRecord                  *
 *                      make method getRecord more robust and efficient *
 *                      use method getRecord instead of SQL in construct*
 *                      handle records with invalid type field NULL     *
 *      2017/03/11      add method toJson                               *
 *      2017/05/29      add support for cemetery transcriptions and     *
 *                      'Canada, Post Offices and Postmasters' and      *
 *                      birth and marriage citations by volume, page    *
 *                      number, and item to method toHTML               *
 *      2017/07/07      invalid pattern including '/'                   *
 *                      misspelled $page instead of $pageNum            *
 *      2017/07/20      support Upper Canada District Marriages         *
 *      2017/07/27      change class name to Citation                   *
 *      2017/07/30      change class LegacySource to class Source       *
 *      2017/08/07      class LegacyChild renamed to class Child        *
 *      2017/08/15      class LegacyToDo renamed to class ToDo          *
 *      2017/08/17      death citations by volume, page     number, and *
 *                      item to method toHTML                           *
 *      2017/09/21      handle county marriage report number with 1/2   *
 *                      use URL embedded in srcdetail                   *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/10      use prepared statements                         *
 *                      do not throw exception on new record by IDSX    *
 *      2017/10/13      change class LegacyIndiv to class Person        *
 *      2017/10/17      support field name synonyms                     *
 *                      add diagnostic warnings to constructor          *
 *      2017/11/18      remove static functions replaced by class       *
 *                      CitationSet                                     *
 *      2017/12/18      add support for temporary fields                *
 *      2018/11/01      add lang parameter to method toHTML             *
 *      2018/12/03      method toHTML changed to use template           *
 *                      method toHTML returns the generated text        *
 *                      instead of printing it                          *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/04/15      replace is_a with instanceof                    *
 *      2019/04/18      support method getLastSqlCmd                    *
 *      2019/07/18      use Person::getPerson                           *
 *      2019/08/06      support accessing most fields from the          *
 *                      associated Source                               *
 *                      do not create Source in constructor             *
 *                      support idsr => instance of Source in           *
 *                      constructor and $citation->set                  *
 *      2019/09/20      correct implementation of Citation::dump        *
 *      2019/11/02      hide extra HTML inserted by new version of      *
 *                      tinyMCE                                         *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2020/01/10      toHTML did not generate unique id values and    *
 *                      and left many empty lines in the output         *
 *      2020/05/23      correct toHTML handling of citations where the  *
 *                      Source has a url                                *
 *                      add special support for Ontario Historical      *
 *                      Atlases                                         *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/Source.inc';

/************************************************************************
 *  class Citation                                                      *
 *                                                                      *
 *  Definition of a class implementing behavior for citation records    *
 *  within a genealogy database.                                        *
 *                                                                      *
 ************************************************************************/

class Citation extends RecordPic
{
    /********************************************************************
     *  $source         the associated instance of Source               *
     ********************************************************************/
    private $source     = null;

    /********************************************************************
     *  $refRecord      the record that is documented by this citation  *
     ********************************************************************/
    private $refRecord  = null;

    /********************************************************************
     *  constants identifying the types of events and where the details *
     *  are located within the record identified by IDME.               *
     *                                                                  *
     *  Facts where IDIME points to an Person Record tblIR              *
     ********************************************************************/
    const STYPE_UNSPECIFIED         = 0;
    const STYPE_NAME                = 1;
    const STYPE_BIRTH               = 2;
    const STYPE_CHRISTEN            = 3;
    const STYPE_DEATH               = 4;
    const STYPE_BURIED              = 5;
    const STYPE_NOTESGENERAL        = 6;
    const STYPE_NOTESRESEARCH       = 7;
    const STYPE_NOTESMEDICAL        = 8;
    const STYPE_DEATHCAUSE          = 9;
    const STYPE_LDSB                = 15;   // Baptism
    const STYPE_LDSE                = 16;   // Endowment
    const STYPE_LDSC                = 26;   // Confirmation
    const STYPE_LDSI                = 27;   // Initiatory

    /********************************************************************
     *      IDIME points to Name Record tblNX                           *
     ********************************************************************/
    const STYPE_ALTNAME             = 10;

    /********************************************************************
     *      IDIME points to Child Record tblCR.IDCR                     *
     ********************************************************************/
    const STYPE_CHILDSTATUS         = 11;   // Child Status
    const STYPE_CPRELDAD            = 12;   // Relationship to Father
    const STYPE_CPRELMOM            = 13;   // Relationship to Mother
    const STYPE_LDSP                = 17;   // Sealed to Parents

    /********************************************************************
     *      IDIME points to Marriage Record tblMR.idmr                  *
     ********************************************************************/
    const STYPE_LDSS                = 18;   // Sealed to Spouse
    const STYPE_NEVERMARRIED        = 19;   // This individual never married
    const STYPE_MAR                 = 20;   // Marriage
    const STYPE_MARNOTE             = 21;   // Marriage Note
    const STYPE_MARNEVER            = 22;   // Never Married
    const STYPE_MARNOKIDS           = 23;   // This couple had no children
    const STYPE_MAREND              = 24;   // Marriage ended **added**

    /********************************************************************
     *  IDIME points to Event Record tblER.ider                             *
     ********************************************************************/
    const STYPE_EVENT               = 30;   // Individual Event
    const STYPE_MAREVENT            = 31;   // Marriage Event

    /********************************************************************
     *  IDIME points to To-Do records tblTD.IDTD                        *
     ********************************************************************/
    const STYPE_TODO                = 40;   // To-Do

    /********************************************************************
     *  $intType                                                        *
     *                                                                  *
     *  Table of string descriptions for each citation type             *
     *                                                                  *
     *  This is intended to be public information.                      *
     ********************************************************************/
    static $intType = array(
                0           => "Unspecified",
                1           => "Name",
                2           => "Birth",
                3           => "Christening",
                4           => "Death",
                5           => "Buried",
                6           => "General Notes",
                7           => "Research Notes",
                8           => "Medical Notes",
                9           => "Death Cause",
                10          => "Alternate Name",
                11          => "Child Status",
                12          => "Child Relationship to Dad",
                13          => "Child Relationship to Mom",
                15          => "LDS Baptism",
                16          => "LDS Endowment",
                17          => "LDS Sealed to Parents",
                18          => "LDS Sealed to Spouse",
                19          => "Never Married",
                20          => "Marriage",
                21          => "Marriage Note",
                22          => "Marriage Never",
                23          => "Marriage No Children",
                24          => "Marriage Ended",        // **added**
                26          => "LDS Confirmation",
                27          => "LDS Initiatory",
                30          => "Event",
                31          => "Marriage Event",
                40          => "To Do",
                100         => "Temporary");

    /********************************************************************
     *  Identify type of record containing event details                *
     *                                                                  *
     *  This is intended to be public information.                      *
     ********************************************************************/
    static $recType = array( 0          => "IDIR",
                             1          => "IDIR",
                             2          => "IDIR",
                             3          => "IDIR",
                             4          => "IDIR",
                             5          => "IDIR",
                             6          => "IDIR",
                             7          => "IDIR",
                             8          => "IDIR",
                             9          => "IDIR",
                            10          => "IDNX",
                            11          => "IDCR",
                            12          => "IDCR",
                            13          => "IDCR",
                            15          => "IDIR",
                            16          => "IDIR",
                            17          => "IDCR",
                            18          => "IDMR",
                            19          => "IDMR",
                            20          => "IDMR",
                            21          => "IDMR",
                            22          => "IDMR",
                            23          => "IDMR",
                            24          => "IDMR",      // **added**
                            26          => "IDIR",
                            27          => "IDIR",
                            30          => "IDER",
                            31          => "IDER",
                            40          => "IDTD");

    /********************************************************************
     *  Citation::$initRow                                              *
     *                                                                  *
     *  array containing default values for all fields in a new record  *
     ********************************************************************/
    protected static    $initRow    = array(
                        'idsx'                  => 0,
                        'idsr'                  => 1,
                        'idime'                 => 0,
                        'type'                  => 0,
                        'srcdetail'             => '',
                        'srcprintdetail'        => 1,
                        'srcdettext'            => '',
                        'srcprinttext'          => 1,
                        'srcdetnote'            => '',
                        'srcprintnote'          => 1,
                        'srcprint'              => 1,
                        'srcsurety'             => 0,
                        'enteredsd'             => -99999999,
                        'enteredd'              => '',
                        'filingref'             => '',
                        'order'                 => 0,
                        'used'                  => 0,
                        'verified'              => 0,
                        'content'               => '',
                        'override'              => '',
                        'overridefootnote'      => 1,
                        'overridesubsequent'    => 1,
                        'overridebibliography'  => 1);

    /********************************************************************
     *  Citation::$info                                                 *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                        'table'                 => 'tblSX',
                        'name'                  => 'Citations',
                        'prime'                 => 'idsx',
                        'srvmg'                 => true,
                        'fldcount'              => 4,
                        'order'                 => '`Order`',
                        'classname'             => 'Citation');

    /********************************************************************
     *  Citation::$translate                                            *
     *                                                                  *
     *  array containing synonyms for field names                       *
     ********************************************************************/
    protected static    $translate  = array(
                        'detail'                => 'srcdetail',
                        'printdetail'           => 'srcprintdetail',
                        'dettext'               => 'srcdettext',
                        'printtext'             => 'srcprinttext',
                        'detnote'               => 'srcdetnote',
                        'printnote'             => 'srcprintnote',
                        'print'                 => 'srcprint',
                        'surety'                => 'srcsurety',
                        'idir'                  => 'idime',
                        'idmr'                  => 'idime',
                        'ider'                  => 'idime',
                        'idcr'                  => 'idime',
                        'idnx'                  => 'idime',
                        // the following are fields in the Source
                        'srcname'               => 'srcname',
                        'srctitle'              => 'srctitle',
                        'idst'                  => 'idst',
                        'srcauthor'             => 'srcauthor',
                        'srcpubl'               => 'srcpubl',
                        'srctext'               => 'srctext',
                        'psrctext'              => 'psrctext',
                        'fsrctext'              => 'fsrctext',
                        'tsrctext'              => 'tsrctext',
                        'srcnote'               => 'srcnote',
                        'psrcnote'              => 'psrcnote',
                        'fsrcnote'              => 'fsrcnote',
                        'tsrcnote'              => 'tsrcnote',
                        'srccallnum'            => 'srccallnum',
                        'srctag'                => 'srctag',
                        'qstag'                 => 'qstag',
                        'srcexclude'            => 'srcexclude',
                        'idar'                  => 'idar',
                        'idar2'                 => 'idar2',
                        'enteredsd'             => 'enteredsd',
                        'enteredd'              => 'enteredd',
                        'filingref'             => 'filingref',
                        'used'                  => 'used',
                        'published'             => 'published',
                        'verified'              => 'verified',
                        'srcmpub'               => 'srcmpub',
                        'srcrollnum'            => 'srcrollnum',
                        'templateid'            => 'templateid',
                        'contents'              => 'contents',
                        'usestandard'           => 'usestandard',
                        'bibliography'          => 'bibliography');

    /********************************************************************
     *  Citation::$defaultOrder                                         *
     *                                                                  *
     *  The default contents of the ORDER BY clause in SELECTs          *
     *  If this is an empty string then the value of the 'prime'        *
     *  attribute of the entry in $primeKey is used.                    *
     ********************************************************************/
    protected static $defaultOrder      = '`Order`';

    /********************************************************************
     *  function Citation::__construct                                  *
     *                                                                  *
     *  Given the unique numeric identifier make information on a       *
     *  citation record available.                                      *
     *                                                                  *
     *  Input:                                                          *
     *      $id         an associative array of parameter values to     *
     *                  use to locate and initialize the instance.      *
     *                  For example:                                    *
     *                      array('idsx' => $idsx) constructs from an   *
     *                          existing table row.                     *
     *                      array('idime'       => $idir,               *
     *                            'type'        => $type) adds          *
     *                          a new citation                          *
     *                  or a database record presented as an array      *
     *                  or numeric identifier of an existing citation   *
     *                  (key of tblSX), deprecated                      *
     *                  or 0, or 'new', to create a new citation        *
     *                  record, deprecated                              *
     *                                                                  *
     *  Returns object containing citation record.                      *
     ********************************************************************/
    function __construct($parms = array('idsx' => 0))
    {
        global $connection;
        global $debug;
        global $warn;
        global $msg;
        global $document_root;

        if ($debug)
            $warn   .= "<p>Citation::__construct(" .
                           print_r($parms, true) . ")</p>\n";

        $dbrow              = null;
        $idsx               = null;
        $idime              = null;
        $type               = null;
        $where              = '';
        $and                = 'WHERE ';
        $sqlParms           = array();

        // action depends upon type of first parameter
        if (is_string($parms) && strtolower($parms) == 'new')
            $parms          = array('idsx'  => 0);

        if (is_int($parms) || (is_string($parms) && ctype_digit($parms)))
        {                       // numeric identifier of record
            error_log("Citation.inc: " . __LINE__ .
" deprecated new Citation($id), use new Citation(array('idsx' => $id))\n" .
                        "\n");
            $parms          = array('idsx'  => $parms);
        }                       // numeric identifier of record

        if (is_array($parms))
        {                       // normal array parameter
            // validate parameter
            foreach($parms as $field => $value)
            {                   // validate field names in parameter
                $fieldLc    = strtolower($field);
                if (array_key_exists($fieldLc, self::$translate))
                    $fieldLc    = self::$translate[$fieldLc];
                switch($fieldLc)
                {               // act on specific field name
                    case 'idsx':
                    {           // IDSX
                        if ((is_int($value) || ctype_digit($value)) &&
                            $value >= 0)
                        {
                            $idsx                   = $value;
                            if ($idsx > 0)
                            {
                                $where              .= $and . "IDSX=:idsx";
                                $sqlParms['idsx']   = $idsx;
                                $and                = ' AND ';
                            }
                        }
                        else
                        {
                            $this->msg  .= "Citation::_construct: " .
                                                "idsx='$value' value invalid. ";
                            $idsx       = 0;
                        }
                        break;
                    }           // IDSX

                    case 'idime':
                    {           // IDIME
                        if ((is_int($value) || ctype_digit($value)) &&
                            $value >= 0)
                        {
                            $idime      = $value;
                        }
                        else
                        {
                            $this->msg  .= "Citation::_construct: " .
                                                "idime='$value' value invalid";
                        }
                        break;
                    }           // IDSX

                    case 'type':
                    {           // citation type
                        if (array_key_exists(intval($value), self::$recType))
                        {       // valid record type
                            $type               = $value;
                        }       // valid record type
                        else
                        {
                            $warn   .= "<p>Citation::_construct: " .
                                                "type='$value' value invalid";
                        }
                        break;
                    }           // citation type

                    case 'template':
                    {           // internationalization support
                        if ($value instanceof Template)
                            $this->template     = $value;
                        break;
                    }           // internationalization support
                }               // validate specific field name
            }                   // validate field names in parameter

            // if every field supplied, construct from the array
            $numParms                   = count($parms);
            if ($numParms >= count(self::$initRow))
            {                   // complete row from database
                $dbrow                  = $parms;
                if (is_null($idsx) || $idsx == 0)
                    $this->msg          .= "Citation::_construct: " .
                                                "missing key 'idsx' in row";
            }                   // complete row from database
            else
            {                   // partial row, use as parameters
                if (strlen($where) > 0)
                {               // search for existing record
                    // construct the query of the citation table
                    $query              = "SELECT * FROM tblSX $where";

                    // query the database
                    $stmt               = $connection->prepare($query);
                    $queryText          = debugPrepQuery($query, $sqlParms);
                    $this->lastSqlCmd   = $queryText;
                    if ($stmt->execute($sqlParms))
                    {           // successful query
                        if ($debug)
                            $warn       .= "<p>Citation::_construct: " .
                                   __LINE__ . " query='$queryText'</p>";

                        $row            = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($row))
                        {       // existing record in table
                            $dbrow      = $row;
                            $this->needInsert   = false;
                        }       // existing record in table
                        else
                        {       // new row
                            $dbrow      = self::$initRow;
                            if ($idsx > 0)
                            $warn       .= "<p>Citation::_construct: " .
                                            __LINE__ .
                                        " record not found for $where</p>";
                            $this->needInsert   = true;
                        }       // new row
                        // invoke constructor of base class
                        parent::__construct($dbrow,
                                            'tblSX');

                        // apply any updates
                        foreach($parms as $fldname => $value)
                        {       // loop through parameters
                          $fieldLc  = strtolower($field);
                          if (array_key_exists($fieldLc, self::$translate))
                              $fieldLc  = self::$translate[$fieldLc];
                          // apply any requested changes
                          switch ($fieldLc)
                          {
                            case 'idsx':
                                break;      // already done

                            case 'template':
                                $this->template     = $value;
                                break;

                            case 'idsr':
                                if ($value instanceof Source)
                                {
                                    $this->source   = $value;
                                    $this->set('idsr', $this->source['idsr']);
                                }
                                else
                                if (is_int($value) && $value > 0)
                                    $this->set('idsr', $value);
                                else
                                if (ctype_digit($value) && $value > 0)
                                    $this->set('idsr', (int)$value);
                                else
                                    $this->msg      .=
                            "Invalid value for field 'IDSR'='$value'. ";
                                break;

                            default:
                                $this->set($fieldLc, $value);
                                break;
                          }
                        }           // loop through parameters

                        // diagnostic output if debug is set
                        $this->dump('Citation Record constructed:');
                    }               // successful query
                    else
                    {               // error performing query
                        $errorInfo  = $stmt->errorInfo();
                        $this->msg .= "Citation.inc: " . __LINE__ . " '$queryText', " .
                                    print_r($errorInfo,true) . ". ";
                    }               // error performing query
                }                   // existing record
                else
                {                   // new record
                    $dbrow                  = self::$initRow;

                    // calculate value of `order`
                    if (!is_null($idime) && !is_null($type))
                    {       // record and fact type specified
                        $dbrow['idime']     = $idime;
                        $dbrow['type']      = $type;
                        $orderQuery         = "SELECT COUNT(*) FROM tblSX ".
                                    "WHERE `IDIME`=:idime AND `Type`=:type";
                        $sqlParms           = array('idime' => $idime,
                                                    'type'  => $type);

                        // query the database
                        $stmt               = $connection->prepare($orderQuery);
                        $orderQueryText     = debugPrepQuery($orderQuery,
                                                            $sqlParms);
                        $this->lastSqlCmd   = $orderQueryText;
                        if ($stmt->execute($sqlParms))
                        {       // successful query
                            if ($debug)
                                $warn   .= "<p>Citation::__construct: " .
                                            __LINE__ .
                                            " '$orderQueryText'</p>\n";

                            $row            = $stmt->fetch(PDO::FETCH_NUM);
                            if ($row)
                                $dbrow['order'] = $row[0];
                        }       // successful query
                        else
                        {       // error performing query
                            $errorInfo      = $stmt->errorInfo();
                            $this->msg .= "Citation.inc: " . __LINE__ . " '$queryText', " .
                            "unable to get value for `order` field: " .
                                    print_r($errorInfo,true) . ". ";
                        }       // error performing query
                    }       // record and fact type specified

                    // apply any other changes requested by user
                    foreach($parms as $field => $value)
                    {       // loop through all parameters
                        $fieldLc        = strtolower($field);
                        if (array_key_exists($fieldLc, self::$translate))
                            $fieldLc    = self::$translate[$fieldLc];

                        switch ($fieldLc)
                        {
                            case 'idsx':
                                break;      // already done

                            case 'template':
                                $this->template     = $value;
                                break;

                            case 'idsr':
                                if ($value instanceof Source)
                                {
                                    $this->source   = $value;
                                    $dbrow['idsr']  = $this->source['idsr'];
                                }
                                else
                                if (is_int($value) && $value > 0)
                                    $dbrow['idsr']  = $value;
                                else
                                if (ctype_digit($value) && $value > 0)
                                    $dbrow['idsr']  = (int)$value;
                                else
                                    $this->msg      .=
                            "Invalid value for field 'IDSR'='$value'. ";
                                break;

                            default:
                                $dbrow[$fieldLc]    = $value;
                                break;
                        }
                    }       // loop through all parameters

                    // date of creation of citation
                    $currdate           = getdate();
                    $ldate              = 1000000*$currdate['mday'] +
                                              10000*$currdate['mon'] +
                                              $currdate['year'];
                    if ($ldate < 10000000)
                    {   // 7 digits long i.e. single digit day of month
                        $ldate  = '000' . $ldate . '00000000';
                    }   // 7 digits long i.e. single digit day of month
                    else
                    {   // 8 digits long i.e. double digit day of month
                        $ldate  = '00' . $ldate . '00000000';
                    }   // 8 digits long i.e. double digit day of month
                    $sdate              = 10000*$currdate['year'] +
                                            100*$currdate['mon'] +
                                            $currdate['mday'];
                    $dbrow['enteredsd'] = $sdate;
                    $dbrow['enteredd']  = $ldate;

                    $this->needInsert   = false;
                }       // new record
            }           // partial row use as parameters
        }           // associative array
        else
        {
            $this->msg  .= "Citation::__construct: called with " .
                 gettype($id) . ", value=" . print_r($id, true) . ". ";
        }

        // invoke constructor of base class
        if ($dbrow != null)
        {
            $idsr                       = $dbrow['idsr'];
            if (is_null($idsr) || $idsr < 1)
                $dbrow['idsr']          = 1;    // empty Source

            // create object
            if (is_null($this->row))
                parent::__construct($dbrow,
                                    'tblSX');
            $this->needInsert   = $dbrow['idsx'] == 0;

            if ($idime != 0 && !is_null($type))
            {                   // validate IDIME
                $record                 = $this->getRecord(); // ?
                if ($idime < 1000000000 && !$record->isExisting())
                    $warn               .= "<p>Citation::__construct: " .
                    __LINE__ . " record with " . self::$recType[$type] .
                    "=$idime does not exist</p>" .
                 $record->dump('why not existing');
            }                   // validate IDIME

            // diagnostic output if debug is set
            $this->dump('Citation Record constructed:');
            $msg                .= $this->msg;
        }
        else
        {
            throw new Exception("Citation::__construct failed: \$this->row is null");
        }
    }       // function Citation::__construct

    /********************************************************************
     *  function Citation::get                                          *
     *                                                                  *
     *  Get the value of a field in the object.                         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to get value of                   *
     ********************************************************************/
    function get($field)
    {
        global  $warn;

        $fieldLc    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc    = self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'idime':
            {                   // synonyms for idime
                return $this->row['idime'];
            }                   // synonyms for idime

            case 'idsx':
            case 'idsr':
            case 'type':
            case 'srcdetail':
            case 'srcprintdetail':
            case 'srcdettext':
            case 'srcprinttext':
            case 'srcdetnote':
            case 'srcprintnote':
            case 'srcprint':
            case 'srcsurety':
            case 'enteredsd':
            case 'enteredd':
            case 'filingref':
            case 'order':
            case 'used':
            case 'verified':
            case 'content':
            case 'override':
            case 'overridefootnote':
            case 'overridesubsequent':
            case 'overridebibliography':
            {                   // other fields in the record
                return parent::get($fieldLc);
            }                   // other fields in the record

            case 'srcname':
            case 'srctitle':
            case 'idst':
            case 'srcauthor':
            case 'srcpubl':
            case 'srctext':
            case 'psrctext':
            case 'fsrctext':
            case 'tsrctext':
            case 'srcnote':
            case 'psrcnote':
            case 'fsrcnote':
            case 'tsrcnote':
            case 'srccallnum':
            case 'srctag':
            case 'qstag':
            case 'srcexclude':
            case 'idar':
            case 'idar2':
            case 'enteredsd':
            case 'enteredd':
            case 'filingref':
            case 'used':
            case 'published':
            case 'verified':
            case 'srcmpub':
            case 'srcrollnum':
            case 'templateid':
            case 'contents':
            case 'usestandard':
            case 'bibliography':
            {                   // fields in associated Source
                return $this->getSource()->get($fieldLc);
            }                   // fields in associated Source

            default:
            {                   // other fields
                return parent::get($fieldLc);
            }                   // other fields
        }
    }       // function Citation::get

    /********************************************************************
     *  function Citation::getIdsx                                      *
     *                                                                  *
     *  Get unique numeric identifier of the Citation record.           *
     *  Note that this is only initialized once the record is in the    *
     *  database.                                                       *
     ********************************************************************/
    function getIdsx() { return intval($this->row['idsx']); }

    /********************************************************************
     *  function Citation::getIdsr                                      *
     *                                                                  *
     *  get the unique numeric identifier of the referenced             *
     *  Source record.                                                  *
     ********************************************************************/
    function getIdsr() { return $this->row['idsr']; }

    /********************************************************************
     *  function Citation::getIdime                                     *
     *                                                                  *
     *  Get the unique numeric identifier of the record containing the  *
     *  details of the fact being documented.                           *
     *                                                                  *
     *  Returns:                                                        *
     *      numeric key of associated record                            *
     ********************************************************************/
    function getIdime() { return $this->row['idime']; }

    /********************************************************************
     *  function Citation::getRecord                                    *
     *                                                                  *
     *  Get the record containing the details of the fact being         *
     *  documented.                                                     *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Person record or                                *
     *      instance of Name record or                                  *
     *      instance of Child record or                                 *
     *      instance of Family record or                                *
     *      instance of Event record                                    *
     *      null if the type field or IDIME value is invalid            *
     ********************************************************************/
    function getRecord()
    {
        global      $warn;

        if ($this->refRecord)   // already identified
        {
            return  $this->refRecord;
        }

        $idime      = $this->row['idime'];
        $stype      = $this->row['type'];

        $refRec     = null;     // initial return value
        switch($stype)
        {
            //  Facts where IDIME points to an Person Record in tblIR
            case self::STYPE_UNSPECIFIED:   // 0
            case self::STYPE_NAME:          // 1
            case self::STYPE_BIRTH:         // 2
            case self::STYPE_CHRISTEN:      // 3
            case self::STYPE_DEATH:         // 4
            case self::STYPE_BURIED:        // 5
            case self::STYPE_NOTESGENERAL:  // 6
            case self::STYPE_NOTESRESEARCH: // 7
            case self::STYPE_NOTESMEDICAL:  // 8
            case self::STYPE_DEATHCAUSE:    // 9
            case self::STYPE_LDSB:          // 15 Baptism
            case self::STYPE_LDSE:          // 16 Endowment
            case self::STYPE_LDSC:          // 26 Confirmation
            case self::STYPE_LDSI:          // 27 Initiatory
            {
                $refRec = Person::getPerson($idime);
                break;
            }

            //  IDIME points to Name record in tblNX
            case self::STYPE_ALTNAME:       // 10
            {
                $refRec = new Name(array('idnx' => $idime));
                break;
            }

            //  IDIME points to Child Record tblCR.IDCR
            case self::STYPE_CHILDSTATUS:   // 11 Child Status
            case self::STYPE_CPRELDAD:      // 12 Relationship to Father
            case self::STYPE_CPRELMOM:      // 13 Relationship to Mother
            case self::STYPE_LDSP:          // 17 Sealed to Parents
            {
                $refRec = new Child(array('idcr' => $idime));
                break;
            }

            //  IDIME points to Marriage Record tblMR.idmr
            case self::STYPE_LDSS:          // 18 Sealed to Spouse
            case self::STYPE_NEVERMARRIED:  // 19 never married
            case self::STYPE_MAR:           // 20 Marriage
            case self::STYPE_MARNOTE:       // 21 Marriage Note
            case self::STYPE_MARNEVER:      // 22 Never Married
            case self::STYPE_MARNOKIDS:     // 23 no children
            case self::STYPE_MAREND:        // 24 Marriage ended *added**
            {
                $refRec = new Family(array('idmr' => $idime));
                break;
            }

            //  IDIME points to Event Record tblER.ider
            case self::STYPE_EVENT:     // 30 Individual Event
            case self::STYPE_MAREVENT:  // 31 Marriage Event
            {
                $refRec = new Event(array('ider' => $idime));
                break;
            }

            //  IDIME points to To-Do records tblTD.IDTD
            case self::STYPE_TODO:      // 40 To-Do
            {
                $refRec = new ToDo(array('idtd' => $idime));
                break;
            }
        }       // act on record type

        $this->refRecord    = $refRec;
        return  $refRec;
    }       // function Citation::getRecord

    /********************************************************************
     *  function Citation::getPerson                                    *
     *                                                                  *
     *  Get the associated instance of Person, if any.                  *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Person record or                                *
     *      null there is none                                          *
     ********************************************************************/
    function getPerson()
    {
        $refRec     = $this->getRecord();
        if ($refRec)
        {           // have an associated record
            try {
                $stype      = $this->row['type'];
                switch($stype)
                {
                    //  Facts where IDIME points to a Person Record in tblIR
                    case self::STYPE_UNSPECIFIED:   // 0
                    case self::STYPE_NAME:      // 1
                    case self::STYPE_BIRTH:     // 2
                    case self::STYPE_CHRISTEN:  // 3
                    case self::STYPE_DEATH:     // 4
                    case self::STYPE_BURIED:    // 5
                    case self::STYPE_NOTESGENERAL:  // 6
                    case self::STYPE_NOTESRESEARCH: // 7
                    case self::STYPE_NOTESMEDICAL:  // 8
                    case self::STYPE_DEATHCAUSE:    // 9
                    case self::STYPE_LDSB:      // 15 Baptism
                    case self::STYPE_LDSE:      // 16 Endowment
                    case self::STYPE_LDSC:      // 26 Confirmation
                    case self::STYPE_LDSI:      // 27 Initiatory
                    {
                        return $refRec;
                    }

                    //  IDIME points to Name record in tblNX
                    case self::STYPE_ALTNAME:   // 10
                    {
                        $idir   = $refRec->getIdir();
                        return Person::getPerson($idir);
                    }

                    //  IDIME points to Child Record tblCR.IDCR
                    case self::STYPE_CHILDSTATUS:   // 11 Child Status
                    case self::STYPE_CPRELDAD:      // 12 Relationship to Father
                    case self::STYPE_CPRELMOM:      // 13 Relationship to Mother
                    case self::STYPE_LDSP:          // 17 Sealed to Parents
                    {
                        return $refRec->getPerson();
                    }

                    //  IDIME points to Marriage Record tblMR.idmr
                    case self::STYPE_LDSS:      // 18 Sealed to Spouse
                    case self::STYPE_NEVERMARRIED:  // 19 never married
                    case self::STYPE_MAR:       // 20 Marriage
                    case self::STYPE_MARNOTE:   // 21 Marriage Note
                    case self::STYPE_MARNEVER:  // 22 Never Married
                    case self::STYPE_MARNOKIDS: // 23 no children
                    case self::STYPE_MAREND:    // 24 Marriage ended *added**
                    {
                        $person = $refRec->getHusband();
                        if ($person)
                            return $person;
                        return $refRec->getWife();
                    }

                    //  IDIME points to Event Record tblER.ider
                    case self::STYPE_EVENT: // 30 Individual Event
                    case self::STYPE_MAREVENT:  // 31 Marriage Event
                    {
                        $idsx       = $this->getIdsx();
                        $eventRec   = $this->getRecord();
                        $refRec     = $eventRec->getAssociatedRecord();
                        if ($refRec instanceof Person)
                            return $refRec;
                        $person = $refRec->getHusband();
                        if ($person)
                            return $person;
                        return $refRec->getWife();
                    }

                    //  IDIME points to To-Do records tblTD.IDTD
                    case self::STYPE_TODO:      // 40 To-Do
                    {
                        $idir       = $refRec->get('idir');
                        return Person::getPerson($idir);
                    }
                }       // act on record type
            } catch (Exception $e)
            {       // exception thrown by a constructor
                $refRec = null;
            }       // exception thrown by a constructor

        }       // citation has a referenced record
        return  $refRec;
    }       // function Citation::getPerson

    /********************************************************************
     *  function Citation::getCitType                                   *
     *                                                                  *
     *  get the type of citation, which indicates where the details of  *
     *  the associated fact are located.                                *
     ********************************************************************/
    function getCitType() { return $this->row['type']; }

    /********************************************************************
     *  function Citation::getCitTypeText                               *
     *                                                                  *
     *  Get textual description of the type of citation.                *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getCitTypeText()
    {
        $type       = $this->row['type'];

        if (is_null($type))
            $retval = 'null';
        else
        if ($type < 0)
            $retval = "Temporary";
        else
        {           // defined type
            if (array_key_exists($this->row['type'], self::$intType))
                $retval = self::$intType[$this->row['type']];
            else
                $retval = "undefined $type";
        }           // defined type
        return $retval;
    }       // function Citation::getCitTypeText

    /********************************************************************
     *  function Citation::getDetail                                    *
     *                                                                  *
     *  Get the details of the citation.                                *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getDetail()
    {
        $retval = $this->row['srcdetail'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }       // function Citation::getDetail

    /********************************************************************
     *  function Citation::getPage                                      *
     *                                                                  *
     *  Synonym for getDetail() which makes its usual function          *
     *  more self-explanatory.                                          *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getPage()
    {
        $retval = $this->row['srcdetail'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }       // function Citation::getPage

    /********************************************************************
     *  function Citation::getDetailText                                *
     *                                                                  *
     *  Get the text of the citation.                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getDetailText()
    {
        $retval = $this->row['srcdettext'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }       // function Citation::getDetailText

    /********************************************************************
     *  function Citation::getDetailNote                                *
     *                                                                  *
     *  Get the comments of the citation.                               *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     ********************************************************************/
    function getDetailNote()
    {
        $retval = $this->row['srcdetnote'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }       // function Citation::getDetailNote

    /********************************************************************
     *  function Citation::getOrder                                     *
     *                                                                  *
     *  Get the numeric position of the citation within the set of      *
     *  citations for the same fact.                                    *
     ********************************************************************/
    function getOrder()
    {
        $retval = $this->row['order'];
        if ($retval === null)
            return 0;
        else
            return $retval;
    }       // function Citation::getOrder

    /********************************************************************
     *  function Citation::getSurety                                    *
     *                                                                  *
     *  Get the confidence level of the researcher in this citation.    *
     ********************************************************************/
    function getSurety()
    {
        $retval = $this->row['srcsurety'];
        if ($retval === null)
            return 3;
        else
            return $retval;
    }       // function Citation::getSurety

    /********************************************************************
     *  function Citation::setIdsr                                      *
     *                                                                  *
     *  Set the unique numeric identifier of the referenced             *
     *  Source record.                                                  *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new value of IDSR to set                        *
     *                                                                  *
     *  Returns:                                                        *
     *          former numeric identifier of Source record value        *
     ********************************************************************/
    function setIdsr($value)
    {
        $oldidsr                    = $this->row['idsr'];
        if ($value != $oldidsr)
        {           // value changed
            $source                 = new Source(array('idsr' => $value));
            if ($source->isExisting())
            {
                $this->source       = $source;
                if ($this->template)
                    $this->setTemplate($this->template);
                parent::set('idsr', $value);
            }
        }           // value changed

        return $oldidsr;
    }       // function Citation::setIdsr

    /********************************************************************
     *  function Citation::setIdime                                     *
     *                                                                  *
     *  Set the unique numeric identifier of the record containing the  *
     *  details of the fact being documented.                           *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new value to set                                *
     *                                                                  *
     *  Returns:                                                        *
     *          former numeric record identifier value                  *
     ********************************************************************/
    function setIdime($value)
    {
        return parent::set('idime', $value);
    }       // function Citation::setIdime

    /********************************************************************
     *  function Citation::setCitType                                   *
     *                                                                  *
     *  Set the type of citation, which indicates where the details of  *
     *  the associated fact are located.                                *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new value to set                                *
     *                                                                  *
     *  Returns:                                                        *
     *          former citation type value                              *
     ********************************************************************/
    function setCitType($value)
    {
        if (!array_key_exists($value, self::$intType))
            throw new Exception("Citation:setCitType invalid value $value");
        return parent::set('type', $value);
    }       // function Citation::setCitType

    /********************************************************************
     *  function Citation::setPage                                      *
     *                                                                  *
     *  Set the page identification text for this citation.             *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new value to set                                *
     *                                                                  *
     *  Returns:                                                        *
     *          former page identification text                         *
     ********************************************************************/
    function setPage($value)
    {
        return parent::set('srcdetail', $value);
    }       // function Citation::setPage

    /********************************************************************
     *  function Citation::setDetailText                                *
     *                                                                  *
     *  Set the text of the citation.                                   *
     *                                                                  *
     *  Parameters:                                                     *
     *         $value       new text                                    *
     *                                                                  *
     *  Returns:                                                        *
     *          former text                                             *
     ********************************************************************/
    function setDetailText($value)
    {
        return parent::set('srcdettext', $value);
    }       // function Citation::setDetailText

    /********************************************************************
     *  function Citation::setDetailNote                                *
     *                                                                  *
     *  Set the comments of the citation.                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new text                                        *
     *                                                                  *
     *  Returns:                                                        *
     *          former text                                             *
     ********************************************************************/
    function setDetailNote($value)
    {
        return parent::set('srcdetnote', $value);
    }       // function Citation::setDetailNote

    /********************************************************************
     *  function Citation::setOrder                                     *
     *                                                                  *
     *  Set the order of the citation within the current set.           *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new order number                                *
     *                                                                  *
     *  Returns:                                                        *
     *      former order                                                *
     ********************************************************************/
    function setOrder($value)
    {
        return parent::set('order', $value);
    }       // function Citation::setOrder

    /********************************************************************
     *  function Citation::setSurety                                    *
     *                                                                  *
     *  Set the confidence level of the citation.                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $value      new confidence level                            *
     *                                                                  *
     *  Returns:                                                        *
     *      former confidence level                                     *
     ********************************************************************/
    function setSurety($value)
    {
        if (($value < 0) || ($value > 4))
            die("Citation::setSurety parameter out of range");
        return parent::set('srcsurety', $value);
    }       // function Citation::setSurety

    /********************************************************************
     *  function Citation::setDate                                      *
     *                                                                  *
     *  Set the entered date.                                           *
     *                                                                  *
     *  Input:                                                          *
     *      $value      instance of LegacyDate or text                  *
     *                                                                  *
     *  Returns:                                                        *
     *      previous value of date as an instance of LegacyDate         *
     ********************************************************************/
    function setDate($value)
    {
        $olddate    = $this->getDate();
        $sdname     = 'enteredsd';
        if ($value instanceof LegacyDate)
        {   // parameter is an instance of LegacyDate
            $date   = $value;
        }   // parameter is an instance of LegacyDate
        else
        {   // convert the value to instance of LegacyDate
            $date   = new LegacyDate(' ' . $value);
        }   // convert the value to instance of LegacyDate
        parent::set('enteredd', $date->getDate());
        parent::set('enteredsd', $date->getSortDate());
        return $olddate;
    }       // function Citation::setDate

    /********************************************************************
     *  function Citation::getSource                                    *
     *                                                                  *
     *  Get the associated master source.                               *
     *                                                                  *
     *  Returns:                                                        *
     *      the master source as an instance of Source                  *
     ********************************************************************/
    function getSource()
    {
        if (is_null($this->source))
        {
            $this->source   = new Source(array('idsr' => $this->row['idsr']));
            if ($this->template)
                $this->source->setTemplate($this->template);
        }
        return $this->source;
    }       // function Citation::getSource

    /********************************************************************
     *  function Citation::getName                                      *
     *                                                                  *
     *  Get a descriptive string for the citation including the         *
     *  identification of the associated event, the name of the         *
     *  master source, and the page number.                             *
     *                                                                  *
     *  Input:                                                          *
     *      $includeRecName     if true include name of record          *
     *                                                                  *
     *  Returns:                                                        *
     *          descriptive string                                      *
     ********************************************************************/
    function getName($includeRecName = true)
    {
        if ($includeRecName)
            $name   = $this->getRecord()->getName() .  ", " .
                      $this->getCitTypeText() . ", ";
        else
            $name   = '';
        return $name .
                "Source='" . $this->getSource()->getName() .
                "', Page='" . $this->getPage() .
                "', Text='" . $this->getDetailText() .
                "', Note='" . $this->getDetailNote() . "'";
    }       // function Citation::getName

    /********************************************************************
     *  function Citation::getDate                                      *
     *                                                                  *
     *  Get the entered date for the citation.                          *
     *                                                                  *
     *  Returns:                                                        *
     *      date the citation was entered as an instance of             *
     *      LegacyDate                                                  *
     ********************************************************************/
    function getDate()
    {
        return new LegacyDate($this->row['enteredd']);
    }       // function Citation::getDate

    /********************************************************************
     *  function Citation::set                                          *
     *                                                                  *
     *  Change the value of a field in the object.                      *
     *  The method validates that the supplied value is compatible with *
     *  the target field.                                               *
     *                                                                  *
     *  Parameters:                                                     *
     *      $field      name of field to change                         *
     *      $value      value to set it to                              *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Adds trace if $field is not already defined as a field      *
     *      or pseudo-field and debugging is enabled                    *
     ********************************************************************/
    function set($field, $value)
    {
        $fieldLc            = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc        = self::$translate[$fieldLc];
        switch($fieldLc)
        {                       // switch on field name
            case 'idime':
                return parent::set('idime', $value);

            case 'idsr':
            {
                if ($value instanceof Source)
                {
                    if ($value->isExisting())
                    {
                        $this->source           = $value;
                        return $this->setIdsr($this->source['idsr']);
                    }
                }
                else
                if (is_int($value) && $value > 0)
                    return $this->setIdsr($value);
                else
                if (ctype_digit($value) && $value > 0)
                    return $this->setIdsr((int)$value);
                break;
            }                   // IDSR

            case 'type':
                return $this->setCitType($value);

            case 'enteredd':
            {                   // internal date format
                return $this->setDate($value);
            }                   // internal date format

            case 'enteredsd':
            {                   // cannot alter
                $this->msg      .= "Citation::set: " .
                                    "invalid field name='$field'";
            }                   // cannot alter

            case 'srcname':
            case 'srctitle':
            case 'idst':
            case 'srcauthor':
            case 'srcpubl':
            case 'srctext':
            case 'psrctext':
            case 'fsrctext':
            case 'tsrctext':
            case 'srcnote':
            case 'psrcnote':
            case 'fsrcnote':
            case 'tsrcnote':
            case 'srccallnum':
            case 'srctag':
            case 'qstag':
            case 'srcexclude':
            case 'idar':
            case 'idar2':
            case 'enteredsd':
            case 'enteredd':
            case 'filingref':
            case 'used':
            case 'published':
            case 'verified':
            case 'srcmpub':
            case 'srcrollnum':
            case 'templateid':
            case 'contents':
            case 'usestandard':
            case 'bibliography':
            {                   // fields in associated Source
                // do not modify
                return $this->getSource()->get($fieldLc);
            }                   // fields in associated Source

            default:
            {                   // permit creation of temporary fields
                return parent::set($field, $value);
            }                   // permit creation of temporary fields
        }                       // switch on field name
    }       // function Citation::set

    /********************************************************************
     *  function Citation::dump                                         *
     *                                                                  *
     *  If debug output is enabled, display a diagnostic dump of the    *
     *  contents of a database record in the web page.                  *
     *                                                                  *
     *  Input:                                                          *
     *      $label      descriptive identifier for record               *
     *      $force      if true output even if $debug false             *
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
            $label  = $this->getName();

        $output         = "<p class='label'>$label</p>\n" .
                            "<table border='1'>";

        foreach($this->row as $key => $value)
        {
            $output     .= "  <tr>\n" .
                            "    <th class='label'>\n      " .
                            $key .
                            "\n    </th>\n" .
                            "    <td class='dataleft'>\n      " .
                            $value;

            if (strlen($value) > 0)
            switch($key)
            {
                case 'enteredd':
                {
                    $debug      = false;
                    $date       = new LegacyDate($value);
                    $output     .= "='" . $date->toString() . "'";
                    break;
                }       // dates

                case 'type':
                {
                    $output     .= '=' . self::$intType[$value];
                    break;
                }       // dates

                default:
                {
                    break;
                }
            }   // field specific formatting
            $output         .= "\n    </td>\n" .
                                "  </tr>\n";
        }   // loop through keys
        $output             .= "</table>\n";

        if ($debug || $force)
        {
            $warn           .= $output;
        }
        return $output;
    }       // function Citation::dump

    /********************************************************************
     *  function Citation::save                                         *
     *                                                                  *
     *  Save changes made to the Citation record into the               *
     *  database.                                                       *
     *                                                                  *
     *  Parameters:                                                     *
     *      $xml        if true diagnostic output is emitted in XML     *
     *                  if a string use it as tag name                  *
     *                                                                  *
     *  Returns:                                                        *
     *      false       if the method was unable to update the database *
     *      1           if the method updated the database record       *
     *      0           if the database did not need to be updated      *
     *                                                                  *
     *  Throws:                                                         *
     *      exception if user is not authorized to update the database  *
     ********************************************************************/
    function save($xml)
    {
        global  $connection;
        global  $debug;
        global  $warn;
        global  $msg;

        if (!array_key_exists('idsx', $this->row))
            $warn   .= "<p>Citation::save: " . __LINE__ .
                    $this->dump("not initialized");
        if ($this->row['idsx'] == 0)
        {       // creating new record, ensure not a duplicate
            // construct a query for duplicate citations
            $query  = 'SELECT IDSX FROM tblSX WHERE ' .
                            '`idsr`=:idsr AND ' .
                            '`idime`=:idime AND ' .
                            '`type`=:type AND ' .
                            '`srcdetail`=:srcdetail AND ' .
                            '`srcdettext`=:srcdettext AND ' .
                            '`srcdetnote`=:srcdetnote AND ' .
                            '`filingref`=:filingref AND ' .
                            '`content`=:content AND ' .
                            '`override`=:override';
            $sqlParms   = array('idsr'      => $this->row['idsr'],
                            'idime'         => $this->row['idime'],
                            'type'          => $this->row['type'],
                            'srcdetail'     => $this->row['srcdetail'],
                            'srcdettext'    => $this->row['srcdettext'],
                            'srcdetnote'    => $this->row['srcdetnote'],
                            'filingref'     => $this->row['filingref'],
                            'content'       => $this->row['content'],
                            'override'      => $this->row['override']);
            // query the database
            $stmt               = $connection->prepare($query);
            $queryText          = debugPrepQuery($query, $sqlParms);
            $this->lastSqlCmd   = $queryText;
            if ($stmt->execute($sqlParms))
            {           // successful query
                if ($debug)
                    $warn       .= '<p>Citation.inc: ' . __LINE__ .
                                   " query='$queryText'</p>";

                // if found duplicate citation record
                $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0)
                {       // ignore duplicate
                    $row        = $result[0];
                    $this->row['idsx']  = $row['idsx'];
                    //print "<p>New instance of Citation duplicates IDSX=" .
                    //  $row['idsx'] . "</p>\n";
                    if ($xml)
                        $this->toXml('citation');
                    return 0;
                }       // ignore duplicate
            }           // successful query
            else
            {           // error performing query
                throw new Exception("Citation::save: '$queryText', " .
                                print_r($stmt->errorInfo(),true));
            }           // error performing query
        }           // creating new record

        // invoke parent's implementation
        return parent::save($xml);
    }       // function Citation::save

    /********************************************************************
     *  function Citation::toXml                                        *
     *                                                                  *
     *  Emit an XML representation of the record.                       *
     *                                                                  *
     *  Most fields in the record are represented by a child element    *
     *  with the name of the field containing the internal value as     *
     *  text.                                                           *
     *                                                                  *
     *  Parameters:                                                     *
     *      $top        tag name for top node, default 'event'          *
     *      $print      if true print the output, else just return it   *
     *      $options    match signature                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      XML representation of the object                            *
     ********************************************************************/

    /********************************************************************
     *      Constant masks for options in 3rd parameter                 *
     ********************************************************************/
    const   TOXML_INCLUDE_SOURCE    = 1;

    function toXml($top = 'citation',
                   $print = true,
                   $options = self::TOXML_INCLUDE_SOURCE)
    {
        global $userid;
        global $authorized;

        $idsx       = $this->row['idsx'];
        $includeSource  = $options && self::TOXML_INCLUDE_SOURCE;
        $retval     = "<$top idsx='$idsx'>\n";
        $idsr       = null;

        foreach($this->row as $fld => $value)
        {           // loop through all fields
            switch($fld)
            {           // interpret some fields
                case 'idsr':
                {       // IDSR of master source
                    $retval .= "    <$fld>" . $value . "</$fld>\n";
                    $idsr       = intval($value);
                    // display the associated instance of Source
                    if ($idsr > 0)
                    {           // IDSR specified in record
                        try {
                            $source = $this->getSource();
                            if ($includeSource)
                            $retval .= $source->toXml('source',
                                          false,
                                  Source::TOXML_INCLUDE_REPOSITOR);
                            else
                            $retval .= "<source>" .
                                   htmlentities($source->getName(),ENT_XML1) .
                                   "</source>\n";
                        } catch (Exception $e) {
                            $retval.= "<msg>" . $e->getMessage() . "</msg>\n";
                        }
                    }           // IDSR specified in record
                    break;
                }       // IDSR of master source

                case 'enteredd':
                {       // legacy internal date format
                    $date   = new LegacyDate($value);
                    $retval .= "    <{$fld}c>" . htmlentities($value,ENT_XML1) .
                            "</{$fld}c>\n";
                    $value  = $date->toString();
                    $retval .= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
                    break;
                }       // legacy internal date format

                case 'type':
                {       // document type
                    $retval .= "    <type>" . $value . "</type>\n";
                    $typemeans  = $this->getCitTypeText();
                    $retval .= "    <typemeans>" . htmlentities($typemeans,ENT_XML1) .
                        "</typemeans>\n";
                    $tableKey   = self::$recType[$value];
                    $retval .= "    <reckey>$tableKey</reckey>\n";
                    break;
                }       // document type

                default:
                {       // fields passed back with no interpretation
                    $retval .= "    <$fld>" . htmlentities($value,ENT_XML1) .
                                "</$fld>\n";
                    break;
                }       // fields passed back with no interpretation
            }           // interpret some fields
        }           // loop through all fields

        $retval .= "</$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }       // function Citation::toXml

    /********************************************************************
     *  function Citation::toJson                                       *
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
        $includeSource  = $options && self::TOXML_INCLUDE_SOURCE;
        $retval     = '';
        $comma      = "{\n";

        foreach($this->row as $fld => $value)
        {
            switch($fld)
            {           // interpret some fields
                case 'idsr':
                {       // IDSR of master source
                    $retval .= $comma . "\t\"$fld\":\t" .
                               json_encode($value);
                    $comma  = ",\n";
                    $idsr       = intval($value);
                    // display the associated instance of Source
                    if ($idsr > 0)
                    {           // IDSR specified in record
                        try {
                            $source = $this->getSource();
                            if ($includeSource)
                            {
                            $retval .= $comma . "\t\"source\":\t";
                            $retval .= $source->toJson(false);
                            }
                            else
                            $retval .= $comma . "\t\"source\":\t" .
                               json_encode(htmlentities($source->getName(),ENT_XML1));
                        } catch (Exception $e) {
                            $retval .= $comma . "\t\"msg\":\t" .
                               json_encode($e->getMessage());
                        }
                    }           // IDSR specified in record
                    break;
                }       // IDSR of master source

                case 'enteredd':
                {       // legacy internal date format
                    $date       = new LegacyDate($value);
                    $retval     .= $comma . "\t\"{$fld}c\":\t" .
                                    json_encode($value);
                    $comma      = ",\n";
                    $value      = $date->toString();
                    $retval     .= $comma . "\t\"$fld\":\t" .
                                    json_encode($value);
                    break;
                }       // legacy internal date format

                case 'enteresd':
                {       // legacy internal date format
                    if ($isOwner)
                    {       // can see private data
                        $retval .= $comma . "\t\"$fld\":\t" .
                               json_encode($value);
                    }       // can see private data
                    break;
                }       // legacy internal sort date

                case 'type':
                {       // document type
                    $retval .= $comma . "\t\"$fld\":\t" .
                               json_encode($value);
                    $comma  = ",\n";
                    $typemeans  = $this->getCitTypeText();
                    $retval .= $comma . "\t\"typemeans\":\t" .
                               json_encode($typemeans);
                    $tableKey   = self::$recType[$value];
                    $retval .= $comma . "\t\"reckey\":\t" .
                               json_encode($tableKey);
                    break;
                }       // document type

                default:
                {       // fields passed back with no interpretation
                    $retval .= $comma . "\t\"$fld\":\t" .
                               json_encode($value);
                    $comma  = ",\n";
                    break;
                }       // fields passed back with no interpretation
            }           // interpret some fields
        }

        // close off the JSON document
        if (strlen($retval) > 0)
            $retval     .= "\n}\n";

        if ($print)
        {
            print $retval;
        }
        return $retval;
    }           // function Citation::toJson

    /********************************************************************
     *  function Citation::toHtml                                       *
     *                                                                  *
     *  Generate the HTML to display the contents of the citation       *
     *  to the viewer.                                                  *`
     *                                                                  *
     *  Input:                                                          *
     *      $lang       the human language code of the output           *
     *                                                                  *
     *  Returns:                                                        *
     *      The generated HTML as a string                              *
     ********************************************************************/
    function toHTML($lang = 'en')
    {
        global $debug;
        global $warn;
        global $msg;
        global $document_root;

        // table of template element ids and what to replace them with
        // initialize to removing all of them
        $updates    = array(
                'sourceBirth'           => null,
                'sourceBirthVolume'     => null,
                'sourceMarriage'        => null,
                'sourceMarriageVolume'  => null,
                'sourceMarriageCounty'  => null,
                'sourceMarriageDistrict'=> null,
                'sourceDeath'           => null,
                'sourceDeathVolume'     => null,
                'sourceCensus'          => null,
                'sourceCensusPreConfed' => null,
                'sourceWesleyanBaptism' => null,
                'sourcePostOffices'     => null,
                'sourceTownshipMap'     => null,
                'sourceCemetery'        => null,
                'detail'                => null,
                'hyperlink'             => null,
                'unrecTitle'            => null,
                'showNote'              => null,
                'showText'              => null);

        // extract fields from citation
        $idsx               = $this->row['idsx'];
        $idsr               = $this->row['idsr'];
        $source             = $this->getSource();   // instance of Source
        $title              = trim($source->getTitle());
        $callnum            = trim($source->get('srccallnum'));
        $detail             = trim($this->getDetail());
        $text               = trim($this->getDetailText());
        $note               = trim($this->getDetailNote());
        if ($debug)
            $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                    " idsx=$idsx, idsr=$idsr, detail='$detail'</p>\n";

        // start the template
        $tempBase           = $document_root . '/templates/';
        $includeSub         = "CitationRef$lang.html";
        if (!file_exists($tempBase . $includeSub))
        {
            $includeSub     = "CitationRefen.html";
        }
        $template           = new Template($tempBase . $includeSub);
        $template->set('IDSX',      $idsx);
        $template->set('IDSR',      $idsr);
        $template->set('LANG',      $lang);
        $template->set('TITLE',     $title);

        switch($title)
        {               // act on selected sources
            case 'Birth Register, CA, Ontario':
            {           // use detail to build link
                $yyyynnnnn  = preg_match('/^\s*([0-9]{4})-([0-9]+)\s*$/',
                                        $detail,
                                        $matches);
                $volpageitem= preg_match('#^\s*([0-9]{4})\s*(vol|volume)\s*([^ ]+)\s*page\s*([0-9]+)\s*(item|col|column)\s*([0-9]+)\s*$#i',
                                        $detail,
                                        $matchesv);
                if ($yyyynnnnn == 1)
                {       // detail matches yyyy-nnnnn
                    $regYear        = $matches[1];
                    $regNum         = $matches[2];
                    $updates['sourceBirth'] =
                                  array('REGYEAR'   => $regYear,
                                        'REGNUM'    => $regNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches yyyy-nnnnn
                else
                if ($volpageitem == 1)
                {       // detail matches year vol page item
                    $regYear        = $matchesv[1];
                    $volume         = $matchesv[3];
                    $page           = $matchesv[4];
                    $item           = $matchesv[6];
                    $updates['sourceBirthVolume'] =
                                  array('REGYEAR'   => $regYear,
                                        'VOLUME'    => $volume,
                                        'PAGE'      => $page,
                                        'ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches pattern year vol page item
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }           // Ontario Birth Registration

            case 'Marriage Register, CA, Ontario':
            {   // use detail to build link
                $yyyynnnnn  = preg_match('/^\s*([0-9]{4})-([0-9]+)\s*$/',
                                        $detail,
                                        $matches);
                $volpageitem= preg_match('#^\s*([0-9]{4})\s*(vol|volume)\s*([^ ]+)\s*page\s*([0-9]+)\s*(item|col|column)\s*([0-9]+)\s*$#i',
                                        $detail,
                                        $matchesv);
                if ($yyyynnnnn == 1)
                {   // detail matches pattern yyyy-nnnnnn
                    $regYear        = $matches[1];
                    $regNum         = $matches[2];
                    $updates['sourceMarriage'] =
                                  array('REGYEAR'   => $regYear,
                                        'REGNUM'    => $regNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern yyyy-nnnnn
                else
                if ($volpageitem == 1)
                {       // detail matches pattern year vol page item
                    $regYear        = $matchesv[1];
                    $volume         = $matchesv[3];
                    $page           = $matchesv[4];
                    $item           = $matchesv[6];
                    $updates['sourceMarriageVolume'] =
                                  array('REGYEAR'   => $regYear,
                                        'VOLUME'    => $volume,
                                        'PAGE'      => $page,
                                        'ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches pattern year vol page item
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'Marriage Register, CA, Ontario':

            case 'Marriage Register, CA, CW, County Registers':
            {       // use detail to build link
                $count  = preg_match('/(vol[^0-9]*([0-9]+)|).*(#|No|sect|sched|report)[^0-9]*([0-9]+\x{00BD}?)[^0-9]+([0-9]+|)/ui',
                                 $detail,
                                 $matches);
                if ($count == 1)
                {       // detail matches pattern
                    $volume     = $matches[2];
                    if ($volume == '')
                        $volume     = 16;
                    $report     = $matches[4];
                    $item       = $matches[5];
                    $updates['sourceMarriageCounty'] =
                                  array('VOLUME'    => $volume,
                                        'REPORT'    => $report,
                                        'ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches pattern
                else
                if (preg_match('#https?://\S*#', $detail, $matches) == 1)
                {       // detail matches hyperlink
                    $updates['hyperlink'] =
                                  array('URL'       => $detail,
                                        'DETAIL'    => $detail);
                }
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'Marriage Register, CA, CW, County Registers':

            case 'Marriage Register, CA, UC, District Registers':
            {       // use detail to build link
                $count  = preg_match('/(vol[^0-9]*([0-9]+)|).*(#|No|sect|sched|report)[^0-9]*([0-9]+)[^0-9]+([0-9]+|)/i',
                                 $detail,
                                 $matches);
                if ($count == 1)
                {       // detail matches pattern vol report item
                    $volume     = $matches[2];
                    if ($volume == '')
                        $volume     = 16;
                    $report     = $matches[4];
                    $item       = $matches[5];
                    $updates['sourceMarriageDistrict'] =
                                  array('VOLUME'    => $volume,
                                        'REPORT'    => $report,
                                        'ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches pattern vol report item
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'Marriage Register, CA, UC, District Registers':

            case 'Death Register, CA, Ontario':
            {           // use detail to build link
                $yyyynnnnn  = preg_match('/^\s*([0-9]{4})-([0-9]+)\s*$/',
                                         $detail,
                                         $matches);
                $volpageitem= preg_match('#^\s*([0-9]{4})\s*(vol|volume)\s*([0-9/]+)\s*page\s+([0-9]+)\s*(item|col|column)\s+([0-9]+)\s*$#',
                                        $detail,
                                        $matchesv);
                if ($yyyynnnnn == 1)
                {       // yyyy-nnnnn
                    $regYear        = $matches[1];
                    $regNum         = $matches[2];
                    $updates['sourceDeath'] =
                                  array('REGYEAR'   => $regYear,
                                        'REGNUM'    => $regNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // yyyy-nnnnn
                else
                if ($volpageitem == 1)
                {       // detail matches pattern
                    $regYear        = $matchesv[1];
                    $volume         = $matchesv[3];
                    $page           = $matchesv[4];
                    $item           = $matchesv[6];
                    $updates['sourceDeathVolume'] =
                                  array('REGYEAR'   => $regYear,
                                        'VOLUME'    => $volume,
                                        'PAGE'      => $page,
                                        'ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // detail matches pattern year vol page item
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'Death Register, CA, Ontario':

            case '1871 Census of Canada':
            case '1881 Census of Canada':
            case '1891 Census of Canada':
            case '1901 Census of Canada':
            case '1906 Census of Canada, Prairies':
            case '1911 Census of Canada':
            case '1916 Census of Canada, Prairies':
            case '1921 Census of Canada':
            {       // use detail to build link
                $cenyear    = substr($title, 0, 4);
                $count      = preg_match('/(dist|district)\s*([0-9\.]+)/i',
                                         $detail,
                                         $matches);
                if ($count == 1)
                    $distId     = $matches[2];
                else
                    $distId     = null;
                if ((strlen($detail) > 0) && is_null($distId))
                    $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                                " dist pattern failed for '$detail  '.</p>\n";

                $count  = preg_match('/\s(subdist|subdistrict)\s*([A-Z0-9]+)/i',
                                    $detail,
                                    $matches);
                if ($count == 1)
                    $subdistId  = $matches[2];
                else
                    $subdistId  = null;
                if ((strlen($detail) > 0) && is_null($distId))
                    $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                                " subdist pattern failed for '$detail'.</p>\n";

                $count  = preg_match('/\s(div|division)\s*([0-9]+)/i',
                                 $detail,
                                 $matches);
                if ($count == 1)
                    $division   = $matches[2];
                else
                    $division   = '';

                $count          = preg_match('/\spage\s*([0-9]+)/i',
                                             $detail,
                                             $matches);
                if ($count == 1)
                    $pageNum    = $matches[1];
                else
                    $pageNum    = null;
                if ((strlen($detail) > 0) && is_null($distId))
                    $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                                " page pattern failed for '$detail'.</p>\n";

                if ($distId && $subdistId && $pageNum)
                {
                    $updates['sourceCensus'] =
                                  array('CENYEAR'   => $cenyear,
                                        'DISTID'    => $distId,
                                        'SUBDISTID' => $subdistId,
                                        'DIVISION'  => $division,
                                        'PAGE'      => $pageNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'post-confederation Census of Canada':

            case '1851 Census of Canada':
            case '1861 Census of Canada':
            {       // use detail to build link
                $cenyear    = substr($title, 0, 4);
                $count  = preg_match('/^([A-Z]{2}),? +(dist|district) ([0-9\.]+) .* (subdist|subdistrict) ([A-Z0-9]+).* (div|ward) ([0-9]+) page +([0-9]+)/i',
                                 $detail,
                                 $matches);
                if ($count == 1)
                {   // detail matches pattern
                    $province       = $matches[1];
                    $distId     = $matches[3];
                    $subdistId      = $matches[5];
                    $division       = $matches[7];
                    $pageNum        = $matches[8];
                    $updates['sourceCensusPreConfed'] =
                                  array('CENYEAR'   => $cenyear,
                                        'PROVINCE'  => $province,
                                        'DISTID'    => $distId,
                                        'SUBDISTID' => $subdistId,
                                        'DIVISION'  => $division,
                                        'PAGE'      => $pageNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern
                else
                {   // check for format with no division
                    $count  = preg_match('/^([A-Z]{2}), (dist|district) ([0-9\.]+) .* (subdist|subdistrict) ([A-Z0-9]+).* page +([0-9]+)/i',
                                 $detail,
                                 $matches);
                    if ($count == 1)
                    {   // detail matches pattern
                        $province   = $matches[1];
                        $distId     = $matches[3];
                        $subdistId  = $matches[5];
                        $pageNum    = $matches[6];
                        $updates['sourceCensusPreConfed'] =
                                  array('CENYEAR'   => $cenyear,
                                        'PROVINCE'  => $province,
                                        'DISTID'    => $distId,
                                        'SUBDISTID' => $subdistId,
                                        'DIVISION'  => '',
                                        'PAGE'      => $pageNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                    }   // detail matches pattern
                    else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                }   // check for format with no division
                break;
            }       // 'pre-confederation Census of Canada':

            case 'Wesleyan Methodist Baptisms, Ontario':
            {       // Wesleyan Methodist Baptisms
                $count  = preg_match('/vol ([0-9\.]+) page +([0-9]+)/i',
                                 $detail,
                                 $matches);
                if ($count == 1)
                {   // detail matches pattern
                        $volume     = $matches[1];
                        $pageNum    = $matches[2];
                        $updates['sourceWesleyanBaptism'] =
                                  array('VOLUME'    => $volume,
                                        'PAGENUM'   => $pageNum,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }   // 'Wesleyan Methodist Baptisms, Ontario':

            case 'Canada, Post Offices and Postmasters':
            {       // Canada, Post Offices and Postmasters
                $count          = preg_match('/[0-9]+/',
                                             $detail,
                                             $matches);
                if ($count == 1)
                {   // detail matches pattern
                    $item       = $matches[0];
                    $updates['sourcePostOffices']   =
                                  array('ITEM'      => $item,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // 'Canada, Post Offices and Postmasters':

			case '1876 Historical Atlas of Oxford County':
			case '1877 Historical Atlas of Elgin County':
			case '1877 Historical Atlas of Norfolk County':
			case '1878 Historical Atlas of Middlesex County':
			case '1879 Historical Atlas of Huron County':
			case '1879 Historical Atlas of Perth County':
			case '1880 Historical Atlas of Lambton County':
            {       // Historical County Atlases
                $count          = preg_match('/[0-9]+/',
                                             $detail,
                                             $matches);
                if (substr($detail, 0, 7) == 'map of ' ||
                    substr($detail, 0, 7) == 'Map of ')
                {   // detail matches pattern
                    $township      = substr($detail, 7);
                    $updates['sourceTownshipMap']   =
                                  array('TOWNSHIP'  => $township,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }   // detail matches pattern
                else
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                break;
            }       // Historical County Atlases

            default:
            {   // does not match any interpreted pattern
                $matches            = array();
                $result             = preg_match('#https?://\S*#',
                                      $detail, 
                                      $matches);
                if ($debug)
                    $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                            " idsx=$idsx, idsr=$idsr, detail='$detail', result=$result</p>\n";
                if (substr($title,0,23) == 'Cemetery Transcription,')
                {
                    $updates['sourceCemetery'] =
                                  array('IDSR'      => $idsr,
                                        'LANG'      => $lang,
                                        'DETAIL'    => $detail);
                }       // cemetery transcription
                else
                if ($result)
                {
                    $updates['hyperlink'] =
                                  array('URL'       => $detail,
                                        'DETAIL'    => $detail);
                }
                else
                if (substr($detail, 0, 4) == 'http')
                {
                    $updates['hyperlink'] =
                                  array('URL'       => $detail,
                                        'DETAIL'    => $detail);
                }
                else
                {
                    if ($debug)
                    {
                        $text   = $template['unrecTitle']->innerHTML();
                        $warn   .= str_replace(array('$LINE','$TITLE'),
                                               array(__LINE__, $title),
                                               $text);
                    }
                    $updates['detail'] =
                                  array('DETAIL'    => $detail);
                }
                break;
            }   // does not match any interpreted pattern
            if ($debug && isset($updates['hyperlink']))
                $warn   .= "<p>Citation::toHTML: " . __LINE__ .
                    " \$updates['hyperlink']=" . var_export($updates['hyperlink'], true) . "</p>\n";
        }       // act on selected sources

        // interpret text quotation from source
        if (strlen($text) > 0)
        {
            if (preg_match('#^\s*<p>([^<]*)</p>\s*$#', $text, $matches))
                $text               = $matches[1];
            $updates['showText']    = array('TEXT'      => $text);
        }

        // interpret comments about the citation
        if (strlen($note) > 0)
        {
            if (preg_match('#^\s*<p>([^<]*)</p>\s*$#', $note, $matches))
                $note               = $matches[1];
            $updates['showNote']    = array('NOTE'      => $note);
        }

        foreach($updates as $id => $value)
        {
            $tag                = $template[$id . '$IDSX'];
            if ($tag)
                $tag->update($value);
            else
                $warn            .= "<p>Citation::toHtml: " . __LINE__ .
                            " could not resolve tag with id='$id\$IDSX'</p>\n";
        }
        $html           = $template->compile();
        $html           = preg_replace('/^[ \t]*[\r\n]+/m', '', $html);
        showTrace();
        return $html;
    }       // function Citation::toHtml

}       // class Citation

