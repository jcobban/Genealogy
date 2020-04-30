<?php
namespace Genealogy\GedCom;
/************************************************************************
 *  Genealogy/Gedcom/FamilyTag.inc                                      *
 *                                                                      *
 *  This file contains the definitions of the classes for processing    *
 *  FAM tags in GEDCOM 5.5 genealogy files.                             *
 *                                                                      *
 *  History:                                                            *
 *      2019/01/04      created                                         *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

require_once "Genealogy/GedCom/Tag.inc";
require_once "Genealogy/Child.inc";
use Genealogy\Child;


/************************************************************************
 *  class FamilyTag                                                     *
 *                                                                      *
 *  This class represents the behavior specific to the FAM tag          *
 *  at level 0.                                                         *
 *                                                                      *
 *  0 @<XREF:FAM>@ FAM                              {1:1}               *
 *   1 <<FAMILY_EVENT_STRUCTURE>>                   {0:M}               *
 *    2 HUSB                                        {0:1}               *
 *     3 AGE <AGE_AT_EVENT>                         {1:1}               *
 *    2 WIFE                                        {0:1}               *
 *     3 AGE <AGE_AT_EVENT>                         {1:1}               *
 *   1 HUSB @<XREF:INDI>@                           {0:1}               *
 *   1 WIFE @<XREF:INDI>@                           {0:1}               *
 *   1 CHIL @<XREF:INDI>@                           {0:1}               *
 *   1 NCHI <COUNT_OF_CHILDREN>                     {0:1}               *
 *   1 SUBM @<XREF:SUBM>@                           {0:M}               *
 *   1 <<LDS_SPOUSE_SEALING>>                       {0:M}               *
 *   1 <<SOURCE_CITATION>>                          {0:M}               *
 *   1 <<MULTIMEDIA_LINK>>                          {0:M}               *
 *   1 <<NOTE_STRUCTURE>>                           {0:M}               *
 *   1 REFN <USER_REFERENCE_NUMBER>                 {0:M}               *
 *    2 TYPE <USER_REFERENCE_TYPE>                  {0:1}               *
 *   1 RIN <AUTOMATED_RECORD_ID>                    {0:1}               *
 *   1 <<CHANGE_DATE>>                              {0:1}               *
 ************************************************************************/
class FamilyTag extends Tag
{
    // instances of FamilyTag accessed by GEDCOM xref id
    private static  $allFamilies    = array();

    // instances of Child accessed by the GEDCOM xref id of the child
    private     $childRecords   = array();

    // number of children processed so far
    private     $numChildren    = 0;

    /********************************************************************
     *  function FamilyTag::__construct                                 *
     *                                                                  *
     *  Constructor for an instance of FamilyTag:                       *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent = null)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>FamilyTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // FamilyTag::__construct

    /********************************************************************
     *  function FamilyTag::add                                         *
     *                                                                  *
     *  This factory method processes immediate children of the FAM     *
     *  tag.                                                            *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier (should be blank)      *
     *      $tagname    the tag name (type)                             *
     *      $parms      any parameters                                  *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag                                             *
     ********************************************************************/
    function add($level,
                    $id,
                    $tagname,
                    $parms)
    {
        $childTag       = null;
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {
            //  n @<XREF:FAM>@ FAM
            //    +1 <<FAMILY_EVENT_STRUCTURE>>
            //      +2 HUSB
            //        +3 AGE <AGE_AT_EVENT>
            //      +2 WIFE
            //        +3 AGE <AGE_AT_EVENT>
            case 'ANUL':
            case 'CENS':
            case 'DIV':
            case 'DIVF':
            case 'ENGA':
            case 'MARB':
            case 'MARC':
            case 'MARL':
            case 'MARS':
            case 'EVEN':
            {
                $childTag           = new EventTag($level,
                                                   $this->id,
                                                   $tagname,
                                                   $parms,
                                                   $this->document,
                                                   $this);
                break;
            }

            case 'MARR':
            {
                $childTag           = new FamilyEvniTag($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this,
                                                        'mar');
                break;
            }

            case 'HUSB':
            {                   // +1 HUSB @<XREF:INDI>@
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // identification of husband in marriage

            case 'WIFE':
            {                   // +1 WIFE @<XREF:INDI>@
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // identification of wife in marriage

            case 'CHIL':
            {                   // +1 CHIL @<XREF:INDI>@
                if (array_key_exists($parms, $this->childRecords))
                {       // already added by IndiTag
                    $childr = $this->childRecords[$parms];
                }       // already added by IndiTag
                else
                {       // create new record
                    $childr = new Child(array('idcr' => 0));
                    $childr->set('idir', $parms);
                    $this->childRecords[$parms] = $childr;
                }       // create new record

                // set the order field of the child
                $childr->set('order', $this->numChildren);
                $this->numChildren++;
                $childTag   = new FamilyChildTag($level,
                                               $this->id,
                                               $tagname,
                                               $parms,
                                               $this->document,
                                               $this,
                                               $childr);
                break;
            }       // identification of a child of marriage

            case 'SLGS':
            {                   // +1 <<LDS_SPOUSE_SEALING>>
                $childTag           = new FamilyEvniTag($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        'seal');
                break;
            }                   // end of SLGS structure

            case 'NOTE':
            {                   // +1 <<NOTE_STRUCTURE>>
                $childTag           = new NoteStruct($level,
                                                     $this->id,
                                                     $tagname,
                                                     $parms,
                                                     $this->document,
                                                     $this,
                                                     'notes');
                break;
            }                   // end of NOTE structure


            case 'NCHI':
            {                   // +1 NCHI <COUNT_OF_CHILDREN>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 NCHI <COUNT_OF_CHILDREN>

            case 'SUBM':
            {                   // +1 SUBM @<XREF:SUBM>@
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 SUBM @<XREF:SUBM>@

            case 'SOUR':
            {                   // +1 <<SOURCE_CITATION>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 <<SOURCE_CITATION>>

            case 'OBJE':
            {                   // +1 <<MULTIMEDIA_LINK>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 <<MULTIMEDIA_LINK>>

            case 'REFN':
            {                   // +1 REFN <USER_REFERENCE_NUMBER>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 REFN <USER_REFERENCE_NUMBER>

            case 'RIN':
            {                   // +1 RIN <AUTOMATED_RECORD_ID>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 RIN <AUTOMATED_RECORD_ID>

            case 'CHAN':
            {                   // +1 <<CHANGE_DATE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // change date etc.

            default:
            {                   // all others unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unexpected');
                break;
            }                   // all others unexpected
        }       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // FamilyTag::add

    /********************************************************************
     *  function FamilyTag::setHusb                                     *
     *                                                                  *
     *  Fill in information about the husband in the family from        *
     *  an instance of IndiTag.  This is called to handle the           *
     *  'FAMS' tag in an IndiTag and to handle the 'HUSB'               *
     *  tag in a FamilyTag.                                             *
     *                                                                  *
     *  Input:                                                          *
     *      $person     instance of IndiTag                             *
     ********************************************************************/
    function setHusb($person)
    {
        if (is_object($person))
        {       // get info from existing record
            $idir   = $person->get('idir');
            $evBirth    = $person->getBirthEvent(false);
            if ($evBirth)
                $birthsd    = $evBirth->get('eventsd');
            else
                $birthsd    = -99999999;
        }       // get info from existing record
        else
        {
            $warn   .= '<p class="message">FamilyTag::setHusb: $person=';
            var_dump($person);
        }
    }       // FamilyTag::setHusb

    /********************************************************************
     *  function FamilyTag::setWife                                     *
     *                                                                  *
     *  Fill in information about the wife in the family from           *
     *  an instance of IndiTag.  This is called to handle the           *
     *  'FAMS' tag in an IndiTag and to handle the 'WIFE'               *
     *  tag in a FamilyTag.                                             *
     *                                                                  *
     *  Input:                                                          *
     *      $person     instance of IndiTag                             *
     ********************************************************************/
    function setWife($person)
    {
        if (is_object($person))
        {       // get info from existing record
            $evBirth        = $person->getBirthEvent(false);
            if ($evBirth)
                    $birthsd    = $evBirth->get('eventsd');
            else
                    $birthsd    = -99999999;
        }       // get info from existing record
        else
        {
            $warn   .= '<p class="message">FamilyTag::setWife: $person=';
            var_dump($person);
        }
    }       // FamilyTag::setWife

}       // class FamilyTag

/************************************************************************
 *  class FamilyEvniTag                                                 *
 *                                                                      *
 *  This class represents the behavior specific to events               *
 *  under the INDI tag that are represented by sets of fields within    *
 *  the Person record, not in a separate Event.                         *
 *                                                                      *
 *       +1 DATE <DATE_VALUE>                                           *
 *       +1 <<PLACE_STRUCTURE>>                                         *
 *       +1 <<ADDRESS_STRUCTURE>>                                       *
 *       +1 AGE <AGE_AT_EVENT>                                          *
 *       +1 AGNC <RESPONSIBLE_AGENCY>                                   *
 *       +1 CAUS <CAUSE_OF_EVENT>                                       *
 *       +1 <<SOURCE_CITATION>>                                         *
 *       +1 <<MULTIMEDIA_LINK>>                                         *
 *                                                                      *
 ************************************************************************/
class FamilyEvniTag extends Tag
{
    // event type
    private $typename;

    /********************************************************************
     *  function FamilyEvniTag::__construct                             *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $parent     instance of Tag                                 *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>FamilyEvniTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms'," .
                                    " $parent)</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // FamilyEvniTag::__construct

    /********************************************************************
     *  function FamilyEvniTag::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of an          *
     *  event under the FAM tag.                                        *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $tagname    the tag name (type)                             *
     *      $parms      any parameters                                  *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag                                             *
     ********************************************************************/
    function add($level, $id, $tagname, $parms)
    {
        $childTag               = null;
        $tagname                = strtoupper($tagname);
        switch($tagname)
        {
            case 'DATE':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }   // date present

            case 'PLAC':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }   // place present

            case 'CAUS':
            {
                $childTag       = new DeathCause($level,
                                                 $this->id,
                                                 $tagname,
                                                 $parms,
                                                 $this->document,
                                                 $this);
                break;
            }   // cause present

            case 'SOUR': // <<SOURCE_CITATION>> {0:M}
            {
                $childTag       = new SourceCit($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this,
                                                $this->typename);
                break;
            }       // end of NOTE child

            case 'NOTE': // <<NOTE_STRUCTURE>> {0:M}
            {
                $childTag       = new NoteStruct($level,
                                                 $this->id,
                                                 $tagname,
                                                 $parms,
                                                 $this->document,
                                                 $this,
                                                 $this->typename . 'note');
                break;
            }       // end of NOTE child

            case 'ADDR':// start of ADDRESS_STRUCT
            {       // ADDR Tag
                $childTag       = new AddressStructure($level,
                                                       $this->id,
                                                       $tagname,
                                                       $parms,
                                                       $this->document,
                                                       $this);
                break;
            }       // end of ADDR Tag

            case 'PHON':// phone number
            case 'AGE': // AGE_AT_EVENT
            case 'AGNC':// RESPONSIBLE_AGENCY
            {       // ignore unsupported tags
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unsupported');
                break;
            }       // ignore unsupported tags

            default:
            {                   // all others unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unexpected');
                break;
            }                   // all others unexpected
        }       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // FamilyEvniTag::add

}       // class FamilyEvniTag

/************************************************************************
 *  class FamilyChildTag                                                *
 *                                                                      *
 *  This class represents the behavior specific to the 'CHIL' tag       *
 *  under the FAM tag.                                                  *
 ************************************************************************/
class FamilyChildTag extends Tag
{

    // table for translating relationships to internal code
    private static $cpcodes = array(
                            'natural'   => 1,
                            'adopted'   => 2,
                            'biological'    => 3,
                            'challenged'    => 4,
                            'disproved' => 5,
                            'foster'    => 6,
                            'guardian'  => 7,
                            'sealing'   => 8,
                            'step'      => 9,
                            'unknown'   => 10,
                            'private'   => 11,
                            'family member' => 12);

    /********************************************************************
     *  function FamilyChildTag::__construct                            *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $parent     parent tag                                      *
     *      $childr     associated child record                         *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>FamilyChildTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms'," .
                                    " $parent)</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // FamilyChildTag::__construct

    /********************************************************************
     *  function FamilyChildTag::add                                    *
     *                                                                  *
     *  This factory method processes immediate children of the 'CHIL'  *
     *  tag under the FAM tag.                                          *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag                                             *
     ********************************************************************/
    function add($level, $id, $tagname, $parms)
    {
        $childTag               = null;
        $tagname                = strtoupper($tagname);
        switch($tagname)
        {
            case '_FREL':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }   // FTM relationship to father present

            case '_MREL':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }   // FTM relationship to mother present

            default:
            {                   // all others unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unexpected');
                break;
            }                   // all others unexpected
        }       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // FamilyChildTag::add

}       // class FamilyChildTag
