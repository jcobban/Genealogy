<?php
namespace Genealogy\GedCom;
/************************************************************************
 *  Genealogy/Gedcom/IndiTag.inc                                        *
 *                                                                      *
 *  This file contains the definitions of the classes for processing    *
 *  INDI tags and their children in GEDCOM 5.5 genealogy files.         *
 *                                                                      *
 *  History:                                                            *
 *      2019/01/05      created                                         *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

require_once "Genealogy/GedCom/Tag.inc";
require_once "Genealogy/LegacyDate.inc";

/************************************************************************
 *  class IndiTag                                                       *
 *                                                                      *
 *  This class represents the behavior specific to the INDI tag         *
 *                                                                      *
 *  0 @XREF:INDI@ INDI                              {1:1}               *
 *   1 RESN <RESTRICTION_NOTICE>                    {0:1}               *
 *   1 NAME <NAME_PERSONAL>                         {0:M}               *
 *    2 NPFX <NAME_PIECE_PREFIX>                    {0:1}               *
 *    2 GIVN <NAME_PIECE_GIVEN>                     {0:1}               *
 *    2 NICK <NAME_PIECE_NICKNAME>                  {0:1}               *
 *    2 SPFX <NAME_PIECE_SURNAME_PREFIX             {0:1}               *
 *    2 SURN <NAME_PIECE_SURNAME>                   {0:1}               *
 *    2 NSFX <NAME_PIECE_SUFFIX>                    {0:1}               *
 *    2 <<SOURCE_CITATION>>                         {0:M}               *
 *    2 <<NOTE_STRUCTURE>>                          {0:M}               *
 *   1 SEX <SEX_VALUE>                              {0:1}               *
 *   1 <<INDIVIDUAL_EVENT_STRUCTURE>>               {0:M}               *
 *   1 <<INDIVIDUAL_ATTRIBUTE_STRUCTURE>>           {0:M}               *
 *   1 <<LDS_INDIVIDUAL_ORDINANCE>>                 {0:M}               *
 *   1 <<CHILD_TO_FAMILY_LINK>>                     {0:M}               *
 *   1 <<SPOUSE_TO_FAMILY_LINK>>                    {0:M}               *
 *   1 SUBM @<XREF:SUBM>@                           {0:M}               *
 *   1 <<ASSOCIATION_STRUCTURE>>                    {0:M}               *
 *   1 ALIA @<XREF:INDI>@                           {0:M}               *
 *   1 ANCI @<XREF:SUBM>@                           {0:M}               *
 *   1 DESI @<XREF:SUBM>@                           {0:M}               *
 *   1 <<SOURCE_CITATION>>                          {0:M}               *
 *   1 <<MULTIMEDIA_LINK>>                          {0:M}               *
 *   1 <<NOTE_STRUCTURE>>                           {0:M}               *
 *   1 RFN <PERMANENT_RECORD_FILE_NUMBER>           {0:1}               *
 *   1 AFN <ANCESTRAL_FILE_NUMBER>                  {0:1}               *
 *   1 REFN <USER_REFERENCE_NUMBER>                 {0:M}               *
 *   2 TYPE <USER_REFERENCE_TYPE>                   {0:1}               *
 *   1 RIN <AUTOMATED_RECORD_ID>                    {0:1}               *
 *   1 <<CHANGE_DATE>>                              {0:1}               *
 ************************************************************************/
class IndiTag extends Tag
{
    /********************************************************************
     *  function IndiTag::__constructor                                 *
     *                                                                  *
     *  Constructor for an instance of IndiTag:                         *
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
            $warn   .= "<p>IndiTag::__construct($level," .
                                    "  $id," .
                                    "  $name," .
                                    "  $parms)</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // IndiTag::__construct

    /********************************************************************
     *  function IndiTag::getChildRecord                                *
     *                                                                  *
     *  Get an instance of Child given the reference ID of the          *
     *  family.                                                         *
     *                                                                  *
     *  Input:                                                          *
     *      $refid      GEDCOM reference id of the family.              *
     *      $default    existing instance of Child                      *
     ********************************************************************/
    function getChildRecord($refid, $default = null)
    {
        if (array_key_exists($refid, $this->childRecords))
            return $this->childRecords[$refid];
        else
        {   // create new instance
            if (is_null($default))
            {
                $childr     = new Child(array('idcr' => 0));
                $childr->set('idmr', $refid);
            }
            else
                $childr     = $default;
            $this->childRecords[$refid] = $childr;
            return $childr;
        }   // create new instance
    }       // IndiTag::getChildRecord

    /********************************************************************
     *  IndiTag::setNameFromParms                                       *
     *                                                                  *
     *  Extract the primary name components from the parameter portion  *
     *  of the INDI tag or the NAME subtag.                             *
     *                                                                  *
     *  Input:                                                          *
     *      $parms  any parameters                                  *
     ********************************************************************/
    function setNameFromParms($parms)
    {
        if (strlen($parms) == 0)
            return;

        $start      = strpos($parms, '/');
        if ($start !== false)
        {       // found beginning slash of surname
            $end    = strpos($parms, '/', $start + 1);
            if ($end !== false)
            {       // found ending slash of surname
                $surname    = substr($parms,
                                     $start + 1,
                                     $end - $start - 1);
                $given      = trim(substr($parms, 0, $start) . ' ' .
                                   substr($parms, $end + 1));
            }       // found ending slash of surname
            else
            {       // missing ending slash of surname
                $surname    = substr($parms,
                                     $start + 1);
                $end        = strlen($parms) - 1;
                $given      = trim(substr($parms, 0, $start));
            }       // missing ending slash of surname
        }       // found beginning slash of surname
        else
        {       // no explicit surname, use last word
            $start  = strrpos($parms, ' ');
            if ($start !== false)
            {       // found delimiter before last word
                $surname    = substr($parms,
                                     $start + 1);
                $given      = trim(substr($parms, 0, $start));
            }       // found delimiter before last word
            else
            {       // only one word in name
                $surname    = $parms;
                $given      = '';
            }       // only one word in name
        }       // no explicit surname
   }        // function IndiTag::setNameFromParms

    /********************************************************************
     *  function IndiTag::add                                           *
     *                                                                  *
     *  This factory method processes immediate children of the INDI    *
     *  tag.                                                            *
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
    function add($level,
                    $id,
                    $tagname,
                    $parms)
    {
        $childTag       = null;
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {
            //* +1 TYPE <EVENT_DESCRIPTOR> {0:1}
            //* +1 DATE <DATE_VALUE> {0:1}
            //* +1 <<PLACE_STRUCTURE>> {0:1}
            //* +1 <<ADDRESS_STRUCTURE>> {0:1}
            case 'ADOP':
            case 'BARM':
            case 'BASM':
            case 'BLES':
            case 'CENS':
            case 'CONF':
            case 'EMIG':
            case 'FCOM':
            case 'GRAD':
            case 'IMMI':
            case 'ORDN':
            case 'NATU':
            case 'PROB':
            case 'RETI':
            case 'WILL':
            case '_MILT':       // FTM special
            case '_MDCL':       // FTM special
            case '_ELEC':       // FTM special
            case '_FA1':        // Marriage Fact (FTM)
            case '_FA2':        // Fact 2 (FTM)
            case '_FA3':        // Fact 3 (FTM)
            case '_FA4':        // Fact 4 (FTM)
            case '_FA5':        // Fact 5 (FTM)
            case '_FA6':        // Fact 6 (FTM)
            case '_FA7':        // Fact 7 (FTM)
            case '_FA8':        // Fact 8 (FTM)
            case '_FA9':        // Fact 9 (FTM)
            case '_FA10':       // Fact 10 (FTM)
            case '_FA11':       // Fact 11 (FTM)
            case '_FA12':       // Fact 12 (FTM)
            case '_FA13':       // Fact 13 (FTM)
            case 'EVEN':        // generic event
            {
                $childTag       =  new EventTag($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this);
                break;
            }       // generic user defined event

            //*     LDS_INDIVIDUAL_ORDINANCE:=
            //*     [
            //*     n [ BAPL | CONL ) {1:1}
            //*     +1 STAT <LDS_BAPTISM_DATE_STATUS> {0:1}
            //*     +1 DATE <DATE_LDS_ORD> {0:1}
            //*     +1 TEMP <TEMPLE_CODE> {0:1}
            //*     +1 PLAC <PLACE_LIVING_ORDINANCE> {0:1}
            //*     +1 <<SOURCE_CITATION>> {0:M}
            //*     +1 <<NOTE_STRUCTURE>> {0:M}
            case 'BAPL':    // LDS Baptism
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'baptism');
                break;
            }       //  LDS Baptism

            case 'CONL':    // LDS Confirmation
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'confirmation');
                break;
            }       //  LDS confirmation

            //*     LDS_INDIVIDUAL_ORDINANCE:=
            //*     n ENDL {1:1}
            //*     +1 STAT <LDS_ENDOWMENT_DATE_STATUS> {0:1}
            //*     +1 DATE <DATE_LDS_ORD> {0:1}
            //*     +1 TEMP <TEMPLE_CODE> {0:1}
            //*     +1 PLAC <PLACE_LIVING_ORDINANCE> {0:1}
            //*     +1 <<SOURCE_CITATION>> {0:M}
            //*     +1 <<NOTE_STRUCTURE>> {0:M}
            case 'ENDL':    // LDS Endowment
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'endow');
                break;
            }       //  LDS endowment

            //*     LDS_INDIVIDUAL_ORDINANCE:=
            //*     n SLGC {1:1}
            //*     +1 STAT <LDS_CHILD_SEALING_DATE_STATUS> {0:1}
            //*     +1 DATE <DATE_LDS_ORD> {0:1}
            //*     +1 TEMP <TEMPLE_CODE> {0:1}
            //*     +1 PLAC <PLACE_LIVING_ORDINANCE> {0:1}
            //*     +1 FAMC @<XREF:FAM>@ {1:1}
            //*     +1 <<SOURCE_CITATION>> {0:M} p.34
            //*     +1 <<NOTE_STRUCTURE>> {0:M}
            case 'SLGC':    // LDS Sealing Child
            {
                $childTag       =   new LdsSlgcOrd($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this);
                break;
            }       //  LDS Sealing child

            //*     n BIRT [Y|<NULL>) {1:1}
            //*     +1 <<EVENT_DETAIL>> {0:1} p.29
            //*     +1 FAMC @<XREF:FAM>@ {0:1}
            case 'BIRT':
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'birth');
                break;
            }       //  'Birth_c

            //*     n BAPM [Y|<NULL>) {1:1}
            //*     +1 <<EVENT_DETAIL>> {0:1}
            case 'BAPM':
            //*     n CHR [Y|<NULL>) {1:1}
            //*     +1 <<EVENT_DETAIL>> {0:1} p.29
            //*     +1 FAMC @<XREF:FAM>@ {0:1}
            case 'CHR':
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'chris');
                break;
            }       // Baptism or Christening

            //*     n DEAT [Y|<NULL>) {1:1}
            //*     +1 <<EVENT_DETAIL>> {0:1}
            case 'DEAT':
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'death');
                break;
            }       // Death

            //*     n [ BURI | CREM ) [Y|<NULL>] {1:1}
            //*     +1 <<EVENT_DETAIL>> {0:1}
            case 'CREM':
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'buried');
                break;
            }       // Cremation

            case 'BURI':
            {
                $childTag       =   new IndiEvniTag($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'buried');
                break;
            }       // Burial

            //*     +1 <<INDIVIDUAL_ATTRIBUTE_STRUCTURE>> {0:M}
            case 'CAST':
            case 'DSCR':
            case 'EDUC':
            case 'IDNO':
            case 'NATI':
            case 'NCHI':
            case 'NMR':
            case 'OCCU':
            case 'PROP':
            case 'RELI':
            case 'RESI':
            case 'SSN':
            {
                $childTag       =  new EventTag($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this);
                break;
            }

            //  n TITL <NOBILITY_TYPE_TITLE> {1:1}
            //  +1 <<EVENT_DETAIL>> {0:1}
            // There is a bug in the implementation of FTM that it puts
            // name prefixes in the TITL newTag, which as documented is for
            // titles of nobility, instead of the NPFX subtag
            // of the NAME Tag
            case 'TITL':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

            case 'AFN':
            case 'ANCI':
            case 'DESI':
            //* +1 RESN <RESTRICTION_NOTICE> {0:1}
            case 'RESN':
            case 'RFN':
            case 'RIN':
            //* +1 SUBM @<XREF:SUBM>@ {0:M}
            case 'SUBM':
            {       // single level children
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // single level children

            case 'ASSO':
            //* n PHON <PHONE_NUMBER> {0:3}
            case 'PHON':
            case 'SOUR':
            //* n OBJE @<XREF:OBJE>@ {1:1}
            //* |
            //* nOBJE {1:1}
            //* +1 FORM <MULTIMEDIA_FORMAT> {1:1}
            //* +1 TITL <DESCRIPTIVE_TITLE> {0:1}
            //* +1 FILE <MULTIMEDIA_FILE_REFERENCE> {1:1}
            //* +1 <<NOTE_STRUCTURE>> {0:M}
            case 'OBJE':    // Multimedia link
            {       // ignore, and accept and ignore subtags
                $childTag       = new IgnoreSubtags($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this);
                break;
            }       // end of ignored multi-level tags

            case 'CHAN':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // end of Change Date Tag

            // the ALIA tag is misused by FTM to contain an
            // actual alternate name as text, rather than a cross-reference
            // to another INDI tag.
            case 'ALIA':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // Alias

            //* n NOTE @<XREF:NOTE>@ {1:1}
            //* +1 <<SOURCE_CITATION>> {0:M}
            //* |
            //* n NOTE [<SUBMITTER_TEXT> | <NULL>) {1:1}
            //* +1 [ CONC | CONT ) <SUBMITTER_TEXT> {0:M}
            //* +1 <<SOURCE_CITATION>> {0:M}
            // FTM only uses the first form
            case 'NOTE':
            {
                $childTag       = new NoteStruct($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'notes');
                break;
            }       // end of NOTE structure

            case 'ADDR':
            {       // ADDR Tag
                $childTag       = new AddressStructure($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }       // end of ADDR Tag

            //*     +1 <<CHILD_TO_FAMILY_LINK>> {0:M}
            case 'FAMC':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // end of FAMC Tag

            //* +1 <<SPOUSE_TO_FAMILY_LINK>> {0:M}
            case 'FAMS':
            {
                $idmr               = $this->document->getId($parms);
                //if ($gender == 0)
                //    $family->setHusb($this);
                //else
                //    $family->setWife($this);
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // end of FAMS Tag

            //* n NAME <NAME_PERSONAL> {1:1}
            //* +1 NPFX <NAME_PIECE_PREFIX> {0:1}
            //* +1 GIVN <NAME_PIECE_GIVEN> {0:1}
            //* +1 NICK <NAME_PIECE_NICKNAME> {0:1}
            //* +1 SPFX <NAME_PIECE_SURNAME_PREFIX {0:1}
            //* +1 SURN <NAME_PIECE_SURNAME> {0:1}
            //* +1 NSFX <NAME_PIECE_SUFFIX> {0:1}
            //* +1 <<SOURCE_CITATION>> {0:M}
            //* +1 <<NOTE_STRUCTURE>> {0:M}
            case 'NAME':
            {
                $this->setNameFromParms($parms);
                $childTag       = new PersNameStruct($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this);
                break;
            }       // end of NAME Tag

            //* n REFN <USER_REFERENCE_NUMBER> {0:M}
            //* +1 TYPE <USER_REFERENCE_TYPE> {0:1}
            case 'REFN':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // end of REFNum Tag

            //*     +1 SEX <SEX_VALUE> {0:1}
            case 'SEX':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // end of SEX Tag

            //*     FTM special tags
            case '_FREL':
            case '_MREL':
            {           // relationship to mother and father
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // relationship to mother and father

            default:
            {           // all others unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unexpected');
                break;
            }           // all others unexpected
        }               // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // IndiTag::add

}       // class IndiTag

/************************************************************************
 *  class PersNameStruct                                                *
 *                                                                      *
 *  This class represents the behavior specific to the NAME tag         *
 *  under the INDI tag                                                  *
 *                                                                      *
 *  <PERSONAL_NAME_STRUCTURE>                                           *
 *   1 NAME <NAME_PERSONAL>                                             *
 *    2 NPFX <NAME_PIECE_PREFIX>                                        *
 *    2 GIVN <NAME_PIECE_GIVEN>                                         *
 *    2 NICK <NAME_PIECE_NICKNAME>                                      *
 *    2 SPFX <NAME_PIECE_SURNAME_PREFIX                                 *
 *    2 SURN <NAME_PIECE_SURNAME>                                       *
 *    2 NSFX <NAME_PIECE_SUFFIX>                                        *
 *    2 <<SOURCE_CITATION>>                                             *
 *    2 <<NOTE_STRUCTURE>>                                              *
 *                                                                      *
 ************************************************************************/
class PersNameStruct extends Tag
{

    /********************************************************************
     *  function PersNameStruct::__construct                            *
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
            $warn   .= "<p>PersNameStruct::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms'," .
                                    "  $parent)</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // PersNameStruct::__construct

    /********************************************************************
     *  function PersNameStruct::add                                    *
     *                                                                  *
     *  This factory method processes immediate children of th          *
     *  NAME tag under the INDI                                         *
     *  tag.                                                            *
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
        $childTag       = null;
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {
            case 'NPFX':
            {                   // <NAME_PIECE_PREFIX> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // <NAME_PIECE_PREFIX> {0:1}

            case 'GIVN':
            {                   // <NAME_PIECE_GIVEN> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // end of GIVN child

            case 'NICK':
            {                   // <NAME_PIECE_NICKNAME> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // end of NICK child

            case 'SPFX':
            {                   // <NAME_PIECE_SURNAME_PREFIX {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // end of SPFX child

            case 'SURN':
            {                   // <NAME_PIECE_SURNAME> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // end of SURN child

            case 'NSFX':
            {                   // <NAME_PIECE_SUFFIX> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // end of NSFX child

            case 'SOUR':
            {                   // <<SOURCE_CITATION>> {0:M}
                $childTag           = new SourceCit($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'name');
                break;
            }                   // end of SOUR child

            case 'NOTE':
            {                   // <<NOTE_STRUCTURE>> {0:M}
                $childTag           = new NoteStruct($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'namenote');
                break;
            }                   // end of NOTE child

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
        }                       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // PersNameStruct::add

}       // class PersNameStruct

/************************************************************************
 *  class LdsIndiOrdTag                                                 *
 *                                                                      *
 *  This class represents the behavior specific to events               *
 *  under the INDI                                                      *
 *                                                                      *
 *  <LDS_INDIVIDUAL_ORDINANCE>                                          *
 *   1 BAPL|CONL|ENDL                                                   *
 *    2 STAT <LDS_DATE_STATUS>                                          *
 *    2 DATE <DATE_VALUE>                                               *
 *    2 TEMP <TEMPLE_CODE>                                              *
 *    2 PLAC <PLACE_LIVING_ORDINANCE>                                   *
 *    2 FAMC @<XREF:FAM>@                                               *
 *    2 <<SOURCE_CITATION>>                                             *
 *    2 <<MULTIMEDIA_LINK>>                                             *
 *                                                                      *
 ************************************************************************/
class LdsIndiOrdTag extends Tag
{

    /********************************************************************
     *  function LdsIndiOrdTag::__construct                             *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $parent     instance of IndiTag                             *
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
            $warn   .= "<p>LdsIndiOrdTag::__construct($level," .
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
    }       // LdsIndiOrdTag::__construct

    /********************************************************************
     *  function LdsIndiOrdTag::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of an internal *
     *  event under the INDI tag.                                       *
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
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {
            case 'STAT':
            {                   // status
                // CHILD | CLEARED | COMPLETED | INFANT | PRE-1970 |
                // QUALIFIED | STILLBORN | SUBMITTED | UNCLEARED | BIC  | DNS ]
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // status

            case 'DATE':
            {                   // <DATE_LDS_ORD>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // <DATE_LDS_ORD>

            case 'TEMP':
            {                   // <TEMPLE_CODE>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // <TEMPLE_CODE>

            case 'PLAC':
            {                   //  <PLACE_LIVING_ORDINANCE>
                // The locality of the place where a living LDS ordinance
                // took place. Usually only a living LDS
                // baptism place is recorded in this field.
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // <PLACE_LIVING_ORDINANCE>

            case 'SOUR':
            {                   // <<SOURCE_CITATION>> {0:M}
                $childTag   =   new SourceCit($level,
                                            $this->id,
                                            $tagname,
                                            $parms,
                                            $this->document,
                                            $this,
                                            $this->typename);
                break;
            }                   // end of SOUR child

            case 'NOTE':
            {                   // <<NOTE_STRUCTURE>> {0:M}
                $childTag   =   new NoteStruct($level,
                                            $this->id,
                                            $tagname,
                                            $parms,
                                            $this->document,
                                            $this,
                                            $this->typename . 'note');
                break;
            }                   // end of NOTE child

            case 'ADDR':// start of ADDRESS_STRUCT
            {                   // ADDR Tag
                $childTag   =   new AddressStructure($level,
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
    }       // LdsIndiOrdTag::add

}       // class LdsIndiOrdTag

/************************************************************************
 *  class IndiEvniTag                                                   *
 *                                                                      *
 *  This class represents the behavior specific to events               *
 *  under the INDI                                                      *
 *                                                                      *
 *  <LDS_INDIVIDUAL_ORDINANCE>                                          *
 *   1 BAPL|CONL|ENDL                                                   *
 *    2 DATE <DATE_VALUE>                                               *
 *    2 <<PLACE_STRUCTURE>>                                             *
 *    2 <<ADDRESS_STRUCTURE>>                                           *
 *    2 AGE <AGE_AT_EVENT>                                              *
 *    2 AGNC <RESPONSIBLE_AGENCY>                                       *
 *    2 CAUS <CAUSE_OF_EVENT>                                           *
 *    2 <<SOURCE_CITATION>>                                             *
 *    2 <<MULTIMEDIA_LINK>>                                             *
 *                                                                      *
 ************************************************************************/
class IndiEvniTag extends Tag
{
    // associated instance of Person
    private $person;

    // event type
    private $typename;

    /********************************************************************
     *  function Tag::__constructor                                     *
     *                                                                  *
     *  function IndiEvniTag::__construct                               *
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
            $warn   .= "<p>IndiEvniTag::__construct($level," .
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
        $this->typename     = $parms;
    }       // IndiEvniTag::__construct

    /********************************************************************
     *  function IndiEvniTag::add                                       *
     *                                                                  *
     *  This factory method processes immediate children of an internal *
     *  event under the INDI tag.                                       *
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
        $tagname        = strtoupper($tagname);
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
                $date   = new \Genealogy\LegacyDate(' ' . $parms);
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
                $childTag   = new DeathCause($level,
                                             $this->id,
                                             $tagname,
                                             $parms,
                                            $this->document,
                                            $this);
                if ($this->typename != 'death')
                    $childTag->diagnostic('Unexpected');
                break;
            }   // cause present

            case 'SOUR': // <<SOURCE_CITATION>> {0:M}
            {
                $childTag   =   new SourceCit($level,
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
    }       // IndiEvniTag::add

}       // class IndiEvniTag

/************************************************************************
 *  class LdsSlgcOrd                                                    *
 *                                                                      *
 *  This class represents the behavior specific to Child Sealed to      *
 *  Parents Ordinance under the INDI tag.                               *
 *                                                                      *
 *  <LDS_INDIVIDUAL_ORDINANCE>                                          *
 *  1 SLGC {1:1}                                                        *
 *   2 STAT <LDS_CHILD_SEALING_DATE_STATUS> {0:1}                       *
 *   2 DATE <DATE_LDS_ORD> {0:1}                                        *
 *   2 TEMP <TEMPLE_CODE> {0:1}                                         *
 *   2 PLAC <PLACE_LIVING_ORDINANCE> {0:1}                              *
 *   2 FAMC @<XREF:FAM>@ {1:1}                                          *
 *   2 <<SOURCE_CITATION>> {0:M} p.34                                   *
 *   2 <<NOTE_STRUCTURE>> {0:M}                                         *
 *                                                                      *
 ************************************************************************/
class LdsSlgcOrd extends Tag
{
    /********************************************************************
     *  function LdsSlgcOrd::__construct                                *
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
            $warn   .= "<p>LdsSlgcOrd::__construct($level," .
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
    }       // LdsSlgcOrd::__construct

    /********************************************************************
     *  function LdsSlgcOrd::add                                        *
     *                                                                  *
     *  This method processes children of the SLGC tage under the INDI  *
     *  tag.                                                            *
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
        $childTag       = null;
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {
            //* +1 STAT <LDS_CHILD_SEALING_DATE_STATUS>
            //* BIC         = Born in the covenant receiving blessing
            //*               of child to parent sealing.
            //* CLEARED     = Sealing has been cleared for temple ordinance.
            //* COMPLETED   = Completed but the date is not known.
            //* DNS         = This record is not being submitted for this
            //*               temple ordinances.
            //* QUALIFIED   = Ordinance request qualified by authorized crit...
            //* PRE-1970    = Ordinance is likely completed, another ordinance
            //*               for this person was converted from temple records
            //*               of work completed before 1970, therefore this
            //*               ordinance is assumed to be complete until all
            //*               records are converted.
            //* STILLBORN   = Stillborn, not required.
            //* SUBMITTED   = Ordinance was previously submitted.
            //* UNCLEARED   = Data for clearing ordinance request was insuff.
            case 'STAT':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }   // status present

            case 'DATE':
            {                   //  +1 DATE <DATE_LDS_ORD> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   //  +1 DATE <DATE_LDS_ORD> {0:1}

            case 'TEMP':
            {                   //  +1 TEMP <TEMPLE_CODE> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   //  +1 TEMP <TEMPLE_CODE> {0:1}

            case 'FAMC':
            {                   // +1 FAMC @<XREF:FAM>@
                // family to which the child is being sealed
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // +1 FAMC @<XREF:FAM>@ {1:1}

            case 'PLAC':
            {                   // PLAC <PLACE_LIVING_ORDINANCE> {0:1}
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                // although GEDCOM 5.5 documents this tag for the SLGC
                // event, the Legacy database structure does not support
                // living sealings
                $childTag->diagnostic('Unexpected');
                break;
            }                   // PLAC <PLACE_LIVING_ORDINANCE> {0:1}

            case 'SOUR':
            {                   //  +1 <<SOURCE_CITATION>> {0:M}
                $childTag       = new SourceCit($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this,
                                                'parseal');
                break;
            }                   // source citation


            case 'NOTE':
            {                   //* +1 <<NOTE_STRUCTURE>> {0:M}
                $childTag       = new NoteStruct($level,
                                                 $this->id,
                                                 $tagname,
                                                 $parms,
                                                 $this->document,
                                                 $this,
                                                 'parsealnote');
                break;
            }                   //* +1 <<NOTE_STRUCTURE>> {0:M}

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
        }                       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // LdsSlgcOrd::add

}       // class LdsSlgcOrd

/************************************************************************
 *  class DeathCause                                                    *
 *                                                                      *
 *  This class represents the behavior specific to the CAUSe of death   *
 *  tag under the DEATh tag under the INDI tag.  This is an extension of*
 *  GEDCOM 5.5 that is supported in order to permit assigning a source  *
 *  for the cause of death independent of the sources for the death     *
 *  itself.                                                             *
 *                                                                      *
 *      1 DEAT ....                                                     *
 *        2 CAUS <CAUSE_OF_EVENT>                                       *
 *          3 <<SOURCE_CITATION>>                                       *
 *                                                                      *
 ************************************************************************/
class DeathCause extends Tag
{
    // associated instance of Person
    private $person;

    /********************************************************************
     *  function DeathCause::__construct                                *
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
            $warn   .= "<p>DeathCause::__construct($level," .
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
    }       // DeathCause::__construct

    /********************************************************************
     *  function DeathCause::add                                        *
     *                                                                  *
     *  This factory method processes immediate children of the CAUS    *
     *  tag under the DEAT tag under the INDI tag.                      *
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
            case 'SOUR':
            {                   // <<SOURCE_CITATION>> {0:M}
                $childTag       = new SourceCit($level,
                                                $id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this,
                                                'deathcause');
                break;
            }                   // <<SOURCE_CITATION>> {0:M}

            default:
            {                   // all others unexpected
                $childTag           = new Tag($level,
                                              $id,
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
    }       // DeathCause::add
}       // class DeathCause

