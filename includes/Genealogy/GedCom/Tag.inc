<?php
namespace Genealogy\GedCom;
/************************************************************************
 *  Genealogy/Gedcom/Tag.inc                                            *
 *                                                                      *
 *  This file contains the definitions of the classes for processing    *
 *  GEDCOM 5.5 genealogy files.                                         *
 *                                                                      *
 *  History:                                                            *
 *      2018/11/24      created                                         *
 *                                                                      *
 *  Copyright &copy; 2018 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  class Tag                                                           *
 *                                                                      *
 *  This class represents the base behavior of all GEDCOM 5.5 tags      *
 *        +1 <refid | null> XXXX <text | null>                          *
 *          +2 [CONT|CONC] <text>                                       *
 *                                                                      *
 ************************************************************************/
class Tag  implements \Iterator
{
    public      $level;     // level in the hierarchy
    public      $id;        // reference identifier if any
    public      $tagname;   // name (type) of the tag
    public      $parms;     // any parameters
    protected   $document;  // containing document
    protected   $parent;    // immediate parent tag
    protected   $children;  // list of immediate child tags
    protected   $tagsByName;// associative table of child tags by tag name

    /********************************************************************
     *  function Tag::__constructor                                     *
     *                                                                  *
     *  Constructor for an instance of Tag:                             *
     *  Application code does not invoke the constructors for instances *
     *  of Tag, rather it uses the factory method add of the parent     *
     *  Tag, or of the Document for top level tags.                     *
     *                                                                  *
     *  Input:                                                          *
     *      $level          the level in the hierarchy                  *
     *      $id             the reference identifier                    *
     *      $name           the tag name (type)                         *
     *      $parms          any parameters                              *
     *      $document       containing instance of Document             *
     *      $parent         instance of Tag or null                     *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent    = null)
    {
        global  $warn;
        global  $debug;

        if (is_null($parent))
            $parentString       = 'null';
        else
            $parentString       = $parent->toString();
        if ($debug)
            $warn   .= "<p>Tag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms'," .
                                    "  $parentString)</p>\n";
        if ($level > 0 && is_null($parent))
           throw new \Exception("Tag::__construct: parent should not be null if level=$level");

        $this->level        = $level;
        $this->id           = $id;
        $this->tagname      = $name;
        $this->parms        = $parms;
        $this->document     = $document;
        $this->parent       = $parent;
        $this->children     = array();
        $this->tagsByName   = array();
    }       // Tag::constructor

    /********************************************************************
     *  function Tag::add                                               *
     *                                                                  *
     *  This factory method processes immediate children of the current *
     *  tag.  The default implementation is to reject all child tags    *
     *  except CONCatenate and CONTinuation, which tags exist only      *
     *  because the GEDCOM 5.5 specification imposed a maximum          *
     *  record length of 255 characters, so this class can be used      *
     *  for the lowest level tags in the definition hierarchy.          *
     *  This also means that CONC and CONT are permitted to extend      *
     *  any tag which does not have a specific implementation of        *
     *  add.                                                            *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $tagname    the tag name (type)                             *
     *      $parms      any parameters                                  *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag or null                                     *
     *                                                                  *
     *  Side Effects:                                                   *
     *      If this method returns an instance of Tag, that instance    *
     *      has been pushed onto the end of the array of children,      *
     ********************************************************************/
    function add($level,
                 $id,
                 $tagname,
                 $parms)
    {
        $childTag               = null;
        $tagname                = strtoupper($tagname);
        switch($tagname)
        {
            case 'CONC':
            {       // concatenate onto field
                $this->parms    .= $parms;
                break;
            }       // concatenate onto field

            case 'CONT':
            {       // concatenate onto field with a space separation
                $this->parms    .= "\n" .  $parms;
                break;
            }       // concatenate onto field with a space separation

            default:
            {                   // all others unexpected
                $childTag       = new Tag($level,
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
    }       // Tag::add

    /********************************************************************
     *  function Tag::register                                          *
     *                                                                  *
     *  This method registers a new child tag under the current tag.    *
     *                                                                  *
     *  Input:                                                          *
     *      $childTag       instance of Tag                             *
     ********************************************************************/
    protected function register($childTag)
    {
        if ($childTag)
        {
            $this->children[]   = $childTag;
            $tagname            = $childTag->tagname;
            if (array_key_exists($tagname, $this->tagsByName))
            {
                if (is_array($this->tagsByName[$tagname]))
                    array_push($this->tagsByName[$tagname], $childTag);
                else
                {
                    $temp   = array($this->tagsByName[$tagname], $childTag);
                    $this->tagsByName[$tagname]     = $temp;
                }
            }
            else
                $this->tagsByName[$tagname]     = $childTag;
        }
    }           // function Tag::register

    /********************************************************************
     *  function Tag::diagnostic                                        *
     *                                                                  *
     *  This method displays a general warning message.                 *
     *                                                                  *
     *  Input:                                                          *
     *      $message    error details                                   *
     ********************************************************************/
    function diagnostic($message)
    {
        global  $warn;
        $specchars  = array('&', '<', '>', '&mdash;');
        $entities   = array("&amp;", "&lt;", "&gt;", "-");

        $warn   .= "<p class=\"message\">$message tag " .
                    str_replace($specchars, $entities, "'{$this->level} {$this->tagname} {$this->parms}', ");
        $parent         = $this->parent;
        while($parent)
        {
            $warn   .=
            str_replace($specchars, $entities, " under '{$parent->level} {$parent->id} {$parent->tagname} {$parent->parms}', ");
            $parent      = $parent->parent;
        }
        $warn   .= "</p>\n";
    }           //  function Tag::diagnostic

    /********************************************************************
     *  function Tag::display                                           *
     *                                                                  *
     *  This method displays the fully accumulated instance of Tag.     *
     *                                                                  *
     *  Returns:                                                        *
     *      String containing HTML                                      *
     ********************************************************************/
    function display()
    {
        $retval .= "<p style=\"margin-left: " . $this->level . "0px\">" .
            "{$this->level} {$this->id} {$this->tagname} {$this->parms}</p>\n";
        foreach($this->children as $childTag)
            $retval     .= $childTag->display();
        return $retval;
    }       // function Tag::display

    /********************************************************************
     *  function Tag::toString                                          *
     *                                                                  *
     *  This method permits an instance of Tag to be used wherever      *
     *  a string is used.                                               *
     *                                                                  *
     *  Returns:                                                        *
     *      String description of instance                              *
     ********************************************************************/
    function toString()
    {
        return
            "Tag('{$this->level} {$this->id} {$this->tagname} {$this->parms}')";
    }       // function toString()

    /********************************************************************
     *  function Tag::__string                                          *
     *                                                                  *
     *  This method permits an instance of Tag to be used wherever      *
     *  a string is used.                                               *
     *                                                                  *
     *  Returns:                                                        *
     *      String description of instance                              *
     ********************************************************************/
    function __string()
    {
        return
            "Tag('{$this->level} {$this->id} {$this->tagname} {$this->parms}')";
    }       // function __string()

    /********************************************************************
     *  function Tag::getTagsByName                                     *
     *                                                                  *
     *  This method gets all of the immediately children of a Tag       *
     *  that have a specific tag name.                                  *
     *                                                                  *
     *  Returns:                                                        *
     *      Array of instances of Tag.                                  *
     ********************************************************************/
    function getTagsByName($tagname)
    {
        $tagname        = strtoupper($tagname);
        if (array_key_exists($tagname, $this->tagsByName))
        {
            if (is_array($this->tagsByName))
                return $this->tagsByName[$tagname];
            else
                return array($this->tagsByName[$tagname]);
        }
        else
            return array();
    }       // function getTagsByName()

    /********************************************************************
     *  function Tag::rewind                                            *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Reposition to the first element of the "array".                 *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag                                             *
     ********************************************************************/
    public function rewind()
    {
        return reset($this->children);
    }       // function Tag::rewind

    /********************************************************************
     *  function Tag::current                                           *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the value of the current element of the "array"          *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     *      instance of Tag                                             *
     ********************************************************************/
    public function current()
    {
        return current($this->children);
    }       // function Tag::current

    /********************************************************************
     *  function Tag::key                                               *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Return the key of the current element of the "array".           *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     *      instance of Tag                                             *
     ********************************************************************/
    public function key()
    {
        return  key($this->children);
    }       // function Tag::key

    /********************************************************************
     *  function Tag::next                                              *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Advance to the next element of the "array".                     *
     *                                                                  *
     *  Returns:                                                        *
     *      String                                                      *
     *      instance of Tag                                             *
     ********************************************************************/
    public function next()
    {
        return next($this->children);
    }       // function Tag::next

    /********************************************************************
     *  function Tag::valid                                             *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Returns true if the iterator is valid.                          *
     *                                                                  *
     *  Returns:                                                        *
     *      boolean                                                     *
     ********************************************************************/
    public function valid()
    {
        $key        = key($this->children);
        return ($key !== NULL && $key !== FALSE);
    }       // function Tag::valid

}       // class Tag

/************************************************************************
 *  class NoteTag                                                       *
 *                                                                      *
 *  This class represents the behavior specific to the NOTE tag         *
 *  at level 0.  See class NoteStructure for the NOTE tag under         *
 *  other tags.                                                         *
 *                                                                      *
 *      n @<XREF:NOTE>@ NOTE <SUBMITTER_TEXT>                           *
 *        +1 [ CONC | CONT] <SUBMITTER_TEXT>                            *
 *        +1 <<SOURCE_CITATION>>                                        *
 *        +1 REFN <USER_REFERENCE_NUMBER>                               *
 *          +2 TYPE <USER_REFERENCE_TYPE>                               *
 *        +1 RIN <AUTOMATED_RECORD_ID>                                  *
 *        +1 <<CHANGE_DATE>>                                            *
 *                                                                      *
 ************************************************************************/
class NoteTag extends Tag
{

    /********************************************************************
     *  function NoteTag::__constructor                                 *
     *                                                                  *
     *  Constructor for an instance of NoteTag:                         *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>NoteTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // NoteTag::__construct

    /********************************************************************
     *  function NoteTag::add                                           *
     *                                                                  *
     *  This factory method processes immediate children of the NOTE    *
     *  tag at the top level.  See class NoteStructure for the NOTE     *
     *  tag at lower levels.                                            *
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
            case 'CONC':
            {       // concatenate onto field
                $this->parms    .= $parms;
                return null;
            }       // concatenate onto field

            case 'CONT':
            {       // concatenate onto field with a space separation
                $this->parms    .= "\n" .  $parms;
                return null;
            }       // concatenate onto field with a space separation

            case 'SOUR':
            {                   //    +1 <<SOURCE_CITATION>>
                $childTag           = new SourceCit($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'noterecord');
                break;
            }                   //    +1 <<SOURCE_CITATION>>

            case 'REFN':
            {                   //    +1 REFN <USER_REFERENCE_NUMBER>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

            case 'RIN':
            {                   //    +1 RIN <AUTOMATED_RECORD_ID>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

            case 'CHAN':
            {                   //    +1 <<CHANGE_DATE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

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
    }       // NoteTag::add

}       // class NoteTag

/************************************************************************
 *  class ObjeTag                                                       *
 *                                                                      *
 *  This class represents the behavior specific to the OBJE tag         *
 *  at level 0 which represents a multi-media record.                   *
 *                                                                      *
 *  0 @XREF:OBJE@ OBJE                                      {1:1}       *
 *   1 FORM <MULTIMEDIA_FORMAT>                             {1:1}       *
 *   1 TITL <DESCRIPTIVE_TITLE>                             {0:1}       *
 *   1 <<NOTE_STRUCTURE>>                                   {0:M}       *
 *   1 BLOB                                                 {1:1}       *
 *    2 CONT <ENCODED_MULTIMEDIA_LINE>                      {1:M}       *
 *   1 OBJE @<XREF:OBJE>@ - chain to continued object       {0:1}       *
 *   1 REFN <USER_REFERENCE_NUMBER>                         {0:M}       *
 *    2 TYPE <USER_REFERENCE_TYPE>                          {0:1}       *
 *   1 RIN <AUTOMATED_RECORD_ID>                            {0:1}       *
 *   1 <<CHANGE_DATE>>                                      {0:1}       *
 *                                                                      *
 ************************************************************************/
class ObjeTag extends Tag
{
    /********************************************************************
     *  function ObjeTag::__constructor                                 *
     *                                                                  *
     *  Constructor for an instance of ObjeTag:                         *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>ObjeTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // ObjeTag::__construct

    /********************************************************************
     *  function ObjeTag::add                                           *
     *                                                                  *
     *  This factory method processes immediate children of the OBJE    *
     *  top level tag.                                                  *
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

            case 'FORM':
            {           // MULTIMEDIA_FORMAT>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $document,
                                              $this->document,
                                              $this);
                break;
            }           // MULTIMEDIA_FORMAT>

            case 'TITL':
            {           // DESCRIPTIVE_TITLE>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // DESCRIPTIVE_TITLE>

            case 'NOTE':
            {           // <NOTE_STRUCTURE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <NOTE_STRUCTURE>>

            case 'BLOB':
            {           // binary file
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // binary file

            case 'OBJE':
            {           // @<XREF:OBJE>@ - chain to continued object
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // @<XREF:OBJE>@ - chain to continued object

            case 'REFN':
            {           // USER_REFERENCE_NUMBER>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // USER_REFERENCE_NUMBER>

            case 'RIN':
            {           // AUTOMATED_RECORD_ID>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // AUTOMATED_RECORD_ID>

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
    }       // ObjeTag::add

}       // class ObjeTag

/************************************************************************
 *  class RepositoryTag                                                 *
 *                                                                      *
 *  This class represents the behavior specific to the REPO tag         *
 *  at level 0.                                                         *
 *                                                                      *
 *  0 @<XREF:REPO>@ REPO                                    {1:1}       *
 *   1 NAME <NAME_OF_REPOSITORY>                            {0:1}       *
 *   1 <<ADDRESS_STRUCTURE>>                                {0:1}       *
 *   1 <<NOTE_STRUCTURE>>                                   {0:M}       *
 *   1 REFN <USER_REFERENCE_NUMBER>                         {0:M}       *
 *    2 TYPE <USER_REFERENCE_TYPE>                          {0:1}       *
 *   1 RIN <AUTOMATED_RECORD_ID>                            {0:1}       *
 *   1 <<CHANGE_DATE>>                                      {0:1}       *
 *                                                                      *
 ************************************************************************/
class RepositoryTag extends Tag
{
    /********************************************************************
     *  function RepositoryTag::__constructor                           *
     *                                                                  *
     *  Constructor for an instance of RepositoryTag:                   *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>RepositoryTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // RepositoryTag::__construct

    /********************************************************************
     *  function RepositoryTag::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of the REPO    *
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

            case 'NAME':
            {               // <NAME_OF_REPOSITORY>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <NAME_OF_REPOSITORY>

            case 'ADDR':
            {               // <<ADDRESS_STRUCTURE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <<ADDRESS_STRUCTURE>>

            case 'NOTE':
            {               // <<NOTE_STRUCTURE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <<NOTE_STRUCTURE>>

            case 'REFN':
            {               // <USER_REFERENCE_NUMBER>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <USER_REFERENCE_NUMBER>

            case 'RIN':
            {               // <AUTOMATED_RECORD_ID>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <AUTOMATED_RECORD_ID>

            case 'CHAN':
            {               // <<CHANGE_DATE>>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // <<CHANGE_DATE>>

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
    }       // RepositoryTag::add

}       // class RepositoryTag

/************************************************************************
 *  class SourceTag                                                     *
 *                                                                      *
 *  This class represents the behavior specific to the SOUR tag         *
 *  at level 0.                                                         *
 *                                                                      *
 *      0 @<XREF:SOUR>@ SOUR                                            *
 *        1 DATA                                                        *
 *          2 EVEN <EVENTS_RECORDED>                                    *
 *            3 DATE <DATE_PERIOD>                                      *
 *            3 PLAC <SOURCE_JURISDICTION_PLACE>                        *
 *          2 AGNC <RESPONSIBLE_AGENCY>                                 *
 *          2 <<NOTE_STRUCTURE>>                                        *
 *        1 AUTH <SOURCE_ORIGINATOR>                                    *
 *          2 [CONT|CONC] <SOURCE_ORIGINATOR>                           *
 *        1 TITL <SOURCE_DESCRIPTIVE_TITLE>                             *
 *          2 [CONT|CONC] <SOURCE_DESCRIPTIVE_TITLE>                    *
 *        1 ABBR <SOURCE_FILED_BY_ENTRY>                                *
 *        1 PUBL <SOURCE_PUBLICATION_FACTS>                             *
 *          2 [CONT|CONC] <SOURCE_PUBLICATION_FACTS>                    *
 *        1 TEXT <TEXT_FROM_SOURCE>                                     *
 *          2 [CONT|CONC] <TEXT_FROM_SOURCE>                            *
 *        1 <<SOURCE_REPOSITORY_CITATION>>                              *
 *        1 <<MULTIMEDIA_LINK>>                                         *
 *        1 <<NOTE_STRUCTURE>>                                          *
 *        1 REFN <USER_REFERENCE_NUMBER>                                *
 *          2 TYPE <USER_REFERENCE_TYPE>                                *
 *        1 RIN <AUTOMATED_RECORD_ID>                                   *
 *        1 <<CHANGE_DATE>>                                             *
 *                                                                      *
 ************************************************************************/
class SourceTag extends Tag
{
    /********************************************************************
     *  function SourceTag::__constructor                               *
     *                                                                  *
     *  Constructor for an instance of SourceTag:                       *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>SourceTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // SourceTag::__construct

    /********************************************************************
     *  function SourceTag::add                                         *
     *                                                                  *
     *  This factory method processes immediate children of the SOUR    *
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
            //    +1 DATA
            //      +2 EVEN <EVENTS_RECORDED>
            //        +3 DATE <DATE_PERIOD>
            //        +3 PLAC <SOURCE_JURISDICTION_PLACE>
            //      +2 AGNC <RESPONSIBLE_AGENCY>
            //      +2 <<NOTE_STRUCTURE>>
            case 'DATA':
            {
                $childTag   = new IgnoreSubTags($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this,
                                                array('EVEN', 'AGNC', 'NOTE'));
                $childTag->diagnostic('Unimplemented');
                break;
            }

            //    +1 AUTH <SOURCE_ORIGINATOR>
            case 'AUTH':
            {
                $childTag       = new TextTag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this,
                                              'srcauthor');
                break;
            }

            //    +1 TITL <SOURCE_DESCRIPTIVE_TITLE>
            case 'TITL':
            {
                $childTag       = new TextTag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this,
                                              'srctitle');
                break;
            }

            //    +1 ABBR <SOURCE_FILED_BY_ENTRY>
            case 'ABBR':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unimplemented');
                break;
            }

            //    +1 PUBL <SOURCE_PUBLICATION_FACTS>
            case 'PUBL':
            {
                $childTag   = new TextTag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                              $this->document,
                                          $this,
                                          'srcpubl');
                break;
            }

            //    +1 TEXT <TEXT_FROM_SOURCE>
            case 'TEXT':
            {
                $childTag   = new TextTag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                              $this->document,
                                          $this,
                                          'srctext');
                break;
            }

            //  <<SOURCE_REPOSITORY_CITATION>>
            //  n REPO @XREF:REPO@
            //    +1 <<NOTE_STRUCTURE>>
            //    +1 CALN <SOURCE_CALL_NUMBER>
            //      +2 MEDI <SOURCE_MEDIA_TYPE>
            case 'REPO':
            {
                // FTM does not support Repository Records, and therefore
                // this REPO tag has no parameters
                $childTag   = new SourceRepoTag($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                              $this->document,
                                                $this);
                break;
            }

            //    +1 <<MULTIMEDIA_LINK>>
            case 'OBJE':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

            //    +1 <<NOTE_STRUCTURE>>
            case 'NOTE':
            {
                $childTag   =   new NoteStruct($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                              $this->document,
                                                $this,
                                                'srcnote');
                break;
            }

            //    +1 REFN <USER_REFERENCE_NUMBER>
            case 'REFN':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

                    //    +1 RIN <AUTOMATED_RECORD_ID>
            case 'RIN':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

            //    +1 <<CHANGE_DATE>>
            case 'CHAN':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }

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
    }       // SourceTag::add

}       // class SourceTag

/************************************************************************
 *  class SubmitterTag                                                  *
 *                                                                      *
 *  This class represents the behavior specific to the SUBM tag         *
 *  at level 0.                                                         *
 *                                                                      *
 *      0 @<XREF:SUBM>@ SUBM {1:1}                                      *
 *        1 NAME <SUBMITTER_NAME> {1:1}                                 *
 *        1 <<ADDRESS_STRUCTURE>> {0:1}                                 *
 *        1 <<MULTIMEDIA_LINK>> {0:M}                                   *
 *        1 LANG <LANGUAGE_PREFERENCE> {0:3}                            *
 *        1 RFN <SUBMITTER_REGISTERED_RFN> {0:1}                        *
 *        1 RIN <AUTOMATED_RECORD_ID> {0:1}                             *
 *        1 <<CHANGE_DATE>>                                             *
 *                                                                      *
 ************************************************************************/
class SubmitterTag extends Tag
{
    /********************************************************************
     *  function SubmitterTag::__constructor                            *
     *                                                                  *
     *  Constructor for an instance of SubmitterTag:                    *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>SubmitterTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // SubmitterTag::__construct

    /********************************************************************
     *  function SubmitterTag::add                                      *
     *                                                                  *
     *  This factory method processes immediate children of the SUBM    *
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
            case 'ADDR':
            {                   // address structure
                $childTag           = new AddressStructure($level,
                                                           $this->id,
                                                           $tagname,
                                                           $parms,
                                                           $this->document,
                                                           $this);
                break;
            }                   // address structure

            case 'NAME':
            {                   // SUBMITTER_NAME
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // SUBMITTER_NAME

            case 'PHON':
            {                   // address structure
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // address structure

            case 'OBJE':
            {                   // multimedia link
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // multimedia link

            case 'LANG':
            {                   // LANGUAGE_PREFERENCE
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // LANGUAGE_PREFERENCE

            case 'RFN':
            {                   // SUBMITTER_REGISTERED_RFN
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // SUBMITTER_REGISTERED_RFN

            case 'RIN':
            {                   // AUTOMATED_RECORD_ID
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }                   // AUTOMATED_RECORD_ID

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
    }       // SubmitterTag::add

}       // class SubmitterTag

/************************************************************************
 *  class SubmissionTag                                                 *
 *                                                                      *
 *  This class represents the behavior specific to the SUBN tag         *
 *  at level 0.                                                         *
 *                                                                      *
 *  0 @XREF:SUBN@ SUBN                                                  *
 *   1 SUBM @XREF:SUBM@                                                 *
 *   1 FAMF <NAME_OF_FAMILY_FILE>                                       *
 *   1 TEMP <TEMPLE_CODE>                                               *
 *   1 ANCE <GENERATIONS_OF_ANCESTORS>                                  *
 *   1 DESC <GENERATIONS_OF_DESCENDANTS>                                *
 *   1 ORDI <ORDINANCE_PROCESS_FLAG>                                    *
 *   1 RIN <AUTOMATED_RECORD_ID>                                        *
 *                                                                      *
 ************************************************************************/
class SubmissionTag extends Tag
{
    /********************************************************************
     *  function SubmissionTag::__constructor                           *
     *                                                                  *
     *  Constructor for an instance of SubmissionTag:                   *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>SubmissionTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // SubmissionTag::__construct

    /********************************************************************
     *  function SubmissionTag::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of the SUBN    *
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
            case 'SUBM':
            {           // @XREF:SUBM@
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // @XREF:SUBM@

            case 'FAMF':
            {           // <NAME_OF_FAMILY_FILE>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <NAME_OF_FAMILY_FILE>

            case 'TEMP':
            {           // <TEMPLE_CODE>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <TEMPLE_CODE>

            case 'ANCE':
            {           // <GENERATIONS_OF_ANCESTORS>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <GENERATIONS_OF_ANCESTORS>

            case 'DESC':
            {           // <GENERATIONS_OF_DESCENDANTS>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <GENERATIONS_OF_DESCENDANTS>

            case 'ORDI':
            {           // <ORDINANCE_PROCESS_FLAG>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <ORDINANCE_PROCESS_FLAG>

            case 'RIN':
            {           // <AUTOMATED_RECORD_ID>
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // <AUTOMATED_RECORD_ID>

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
    }       // SubmissionTag::add
}       // class SubmissionTag

/************************************************************************
 *  class TrailerTag                                                    *
 *                                                                      *
 *  This class represents the behavior specific to the TRLR tag         *
 *  at level 0.                                                         *
 *                                                                      *
 ************************************************************************/
class TrailerTag extends Tag
{
    /********************************************************************
     *  function TrailerTag::__constructor                              *
     *                                                                  *
     *  Constructor for an instance of TrailerTag:                      *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>TrailerTag::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // TrailerTag::__construct

    /********************************************************************
     *  function TrailerTag::add                                        *
     *                                                                  *
     *  This factory method processes immediate children of the TRLR    *
     *  tag.  There are no supported sub-tags of this tag.              *
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
        $childTag       = new Tag($level,
                                  $this->id,
                                  $tagname,
                                  $parms,
                                              $this->document,
                                  $this);
        $childTag->diagnostic('Unexpected');
        $this->register($childTag);
        return $childTag;
    }       // TrailerTag::add

}       // class TrailerTag

/************************************************************************
 *  class EventTag                                                      *
 *                                                                      *
 *  This class represents the behavior specific to events               *
 *  under the INDI or FAM tag that are represented by an instance of    *
 *  Event.                                                              *
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
require_once "Genealogy/Event.inc";
use Genealogy\Event;

class EventTag extends Tag
{
    // order number for next event
    private static  $order      = 0;

    // table to translate tag names into IDET values
    private static  $eventTypes = array(
                            'ADOP'  => Event::ET_ADOPTION,
                            'BIRT'  => Event::ET_BIRTH,
                            'BURI'  => Event::ET_BURIAL,
                            'CHRA'  => Event::ET_CHRISTENING,
                            'DEAT'  => Event::ET_DEATH,
                            'ANUL'  => Event::ET_ANNULMENT,
                            'BAPM'  => Event::ET_LDS_BAPTISM,
                            'BARM'  => Event::ET_BARMITZVAH,
                            'BASM'  => Event::ET_BASMITZVAH,
                            'BLES'  => Event::ET_BLESSING,
                            'CENS'  => Event::ET_CENSUS,
//      'XXXX'  => Event::ET_CIRCUMCISION,
//      'XXXX'  => Event::ET_CITIZENSHIP,
                            'CONF'  => Event::ET_CONFIRMATION,
                            'CONL'  => Event::ET_LDS_CONFIRMATION,
//      'XXXX'  => Event::ET_COURT,
                            'CREM'  => Event::ET_CREMATION,
//      'XXXX'  => Event::ET_DEGREE,
                            'DIV'   => Event::ET_DIVORCE,
                            'DIVF'  => Event::ET_DIVORCE_FILING,
                            'EDUC'  => Event::ET_EDUCATION,
                            'EMIG'  => Event::ET_EMIGRATION,
//      'XXXX'  => Event::ET_EMPLOYMENT,
                            'ENGA'  => Event::ET_ENGAGEMENT,
                            'FCOM'  => Event::ET_FIRST_COMMUNION,
                            'GRAD'  => Event::ET_GRADUATION,
//      'XXXX'  => Event::ET_HOBBIES,
//      'XXXX'  => Event::ET_HONOURS,
//      'XXXX'  => Event::ET_HOSPITAL,
//      'XXXX'  => Event::ET_ILLNESS,
                            'IMMI'  => Event::ET_IMMIGRATION,
//      'XXXX'  => Event::ET_INTERVIEW,
//      'XXXX'  => Event::ET_LAND,
                            'MARB'  => Event::ET_MARRIAGE_BANNS,
                            'MARC'  => Event::ET_MARRIAGE_CONTRACT,
                            'MARL'  => Event::ET_MARRIAGE_LICENSE,
//      'XXXX'  => Event::ET_MARRIAGE_NOTICE,
                            'MARS'  => Event::ET_MARRIAGE_SETTLEMENT,
                            '_MDCL' => Event::ET_MEDICAL,
//      'XXXX'  => Event::ET_MEMBERSHIP,
                            '_MILT' => Event::ET_MILITARY_SERVICE,
//      'XXXX'  => Event::ET_MISSION,
//      'XXXX'  => Event::ET_NAMESAKE,
                            'NATU'  => Event::ET_NATURALIZATION,
//      'XXXX'  => Event::ET_OBITUARY,
                            'OCCU'  => Event::ET_OCCUPATION,
//      'XXXX'  => Event::ET_ORDINANCE,
                            'ORDN'  => Event::ET_ORDINATION,
                            'DSCR'  => Event::ET_PHYSICAL_DESCRIPTION,
                            'PROB'  => Event::ET_PROBATE,
                            'PROP'  => Event::ET_PROPERTY,
                            'RELI'  => Event::ET_RELIGION,
                            'RESI'  => Event::ET_RESIDENCE,
                            'RETI'  => Event::ET_RETIREMENT,
//      'XXXX'  => Event::ET_SCHOOL,
                            'IDNO'  => Event::ET_SOCIAL_SECURITY_NUMBER,
                            'WILL'  => Event::ET_WILL,
//      'XXXX'  => Event::ET_MEDICAL_CONDITION,
//      'XXXX'  => Event::ET_MILITARY,
//      'XXXX'  => Event::ET_PHOTO,
                            'SSN'   => Event::ET_SOC_SEC_NUM,
//      'XXXX'  => Event::ET_OCCUPATION_1,
                            'NATI'  => Event::ET_NATIONALITY,
//      'XXXX'  => Event::ET_FAMILY_GROUP,
//      'XXXX'  => Event::ET_ETHNICITY,
//      'XXXX'  => Event::ET_FUNERAL,
                            '_ELEC' => Event::ET_ELECTION,
                            'MARR'  => Event::ET_MARRIAGE
//      'XXXX'  => Event::ET_MARRIAGE_FACT
//      'XXXX'  => Event::ET_BIRTH_REGISTRATION
//      'XXXX'  => Event::ET_DEATH_REGISTRATION
//      'XXXX'  => Event::ET_MARRIAGE_REGISTRATION
//      'XXXX'  => Event::ET_LDS_ENDOWED
//      'XXXX'  => Event::ET_LDS_INITIATORY
//      'XXXX'  => Event::ET_LDS_SEALED
//      'XXXX'  => Event::ET_MARRIAGE_END
//      'XXXX'  => Event::ET_LDS_PAR_SEALED
                            );

    /********************************************************************
     *  function EventTag::__construct                                  *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>EventTag::__construct($level," .
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

    }       // EventTag::__construct

    /********************************************************************
     *  function EventTag::add                                          *
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
    function add($level, $id, $tagname, $parms)
    {
        $childTag               = null;
        $tagname                = strtoupper($tagname);
        switch($tagname)
        {
            case 'TYPE':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // type present

            case 'DATE':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // date present

            case 'PLAC':
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // place present

            case 'CAUS':
            {
                $childTag           = new DeathCause($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                              $this->document,
                                                    $this);
                break;
            }   // cause present

            case 'SOUR':
            {               // <<SOURCE_CITATION>> {0:M}
                $childTag           = new SourceCit($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    'indievent');
                break;
            }               // <<SOURCE_CITATION>> {0:M}

            case 'NOTE':    // <<NOTE_STRUCTURE>> {0:M}
            {
                $childTag           = new NoteStruct($level,
                                                     $this->id,
                                                     $tagname,
                                                     $parms,
                                              $this->document,
                                                     $this,
                                                     'desc');
                break;
            }               // end of NOTE child

            case 'ADDR':// start of ADDRESS_STRUCT
            {               // ADDR Tag
                $childTag           = new AddressStructure($level,
                                                           $this->id,
                                                           $tagname,
                                                           $parms,
                                              $this->document,
                                                           $this);
                break;
            }               // end of ADDR Tag

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

            case 'OBJE':    // Multimedia link
            {       // ignore, and accept and ignore subtags
                $childTag           = new IgnoreSubtags($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }       // end of ignored multi-level tags

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
    }       // EventTag::add

    /********************************************************************
     *  EventTag::clearOrder                                            *
     *                                                                  *
     *  This static method clears the order field for the start of a    *
     *  new top level object.                                           *
     ********************************************************************/
    static function clearOrder()
    {
        self::$order    = 0;
    }       // EventTag::clearOrder

}       // class EventTag

/************************************************************************
 *  class SourceRepoTag                                                 *
 *                                                                      *
 *  This class represents the behavior specific to the REPOsitory       *
 *  tag in a Source Record.                                             *
 *                                                                      *
 *      0 @<XREF:SOUR>@ SOUR                                            *
 *        <<SOURCE_REPOSITORY_CITATION>>                                *
 *        1 REPO @XREF:REPO@                                            *
 *          2 <<NOTE_STRUCTURE>>                                        *
 *          2 CALN <SOURCE_CALL_NUMBER>                                 *
 *            3 MEDI <SOURCE_MEDIA_TYPE>                                *
 ************************************************************************/
class SourceRepoTag extends Tag
{

    /********************************************************************
     *  function SourceRepoTag::__construct                             *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
     *      $parent     parent tag                                      *
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
            $warn   .= "<p>SourceRepoTag::__construct($level," .
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
    }       // SourceRepoTag::__construct

    /********************************************************************
     *  function SourceRepoTag::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of the REPO    *
     *  tag under the the SOURce record.                                *
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
            case 'NOTE':
            {                   // <<NOTE_STUCTURE>>
                $childTag       = new NoteStruct($level,
                                                $id,
                                                $tagname,
                                                $parms,
                                              $this->document,
                                                $this,
                                                'idar');
                break;
            }                   // <<NOTE_STUCTURE>>

            case 'CALN':
            {                   // call number within repository
                $childTag       = new RepoCalnTag($level,
                                                $id,
                                                $tagname,
                                                $parms,
                                              $this->document,
                                                $this);
                break;
            }                   // call number within repository

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
    }       // SourceRepoTag::add
}       // class SourceRepoTag

/************************************************************************
 *  class RepoCalnTag                                                   *
 *                                                                      *
 *  This class represents the behavior specific to the Call Number tag  *
 *  under the REPOsitory tag in a Source Record.                        *
 *                                                                      *
 *        2 CALN <SOURCE_CALL_NUMBER>                                   *
 *          3 MEDI <SOURCE_MEDIA_TYPE>                                  *
 ************************************************************************/
class RepoCalnTag extends Tag
{

    // media types supported by Legacy
    private static $mediaTypes  = array(
                            'bible'     => 2,
                            'book'      => 3,
                            'census'    => 4,
                            'tax'       => 4,
                            'church'    => 5,
                            'court'     => 6,
                            'electronic'    => 1,
                            'interview' => 7,
                            'journal'   => 8,
                            'land'      => 9,
                            'letter'    => 10,
                            'microfilm' => 1,
                            'military'  => 11,
                            'newspaper' => 12,
                            'periodical'    => 13,
                            'personal'  => 14,
                            'civil registry'=> 15,
                            'vital'     => 15);

    /********************************************************************
     *  function RepoCalnTag::__construct                               *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
     *      $parent     parent tag                                      *
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
            $warn   .= "<p>RepoCalnTag::__construct($level," .
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
    }       // RepoCalnTag::__construct

    /********************************************************************
     *  function RepoCalnTag::add                                       *
     *                                                                  *
     *  This factory method processes immediate children of the         *
     *  Call Number tag                                                 *
     *  under the REPOsitory tag in a Source Record.                    *
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
            case 'MEDI': // media type
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }       // call number within repository

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
    }       // RepoCalnTag::add
}           // class RepoCalnTag

/************************************************************************
 *  class NoteStruct                                                    *
 *                                                                      *
 *  This class represents the behavior specific to the NOTE structure   *
 *  under any tag.                                                      *
 *                                                                      *
 *      n NOTE @<XREF:NOTE>@                                            *
 *        +1 <<SOURCE_CITATION>>                                        *
 *      or                                                              *
 *      n NOTE [SUBMITTER_TEXT> | <NULL>]                               *
 *        +1 [ CONC | CONT ] <SUBMITTER_TEXT>                           *
 *        +1 <<SOURCE_CITATION>>                                        *
 *                                                                      *
 ************************************************************************/
class NoteStruct extends Tag
{
    // field name within associated instance of Record


    /********************************************************************
     *  function NoteStruct::__construct                                *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
     *      $parent     parent instance of Tag                          *
     *      $newfield   field name within the parent's instance of Record*
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent,
                         $newfield)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>NoteStruct::__construct($level," .
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
        $this->fieldname= $newfield;
    }       // NoteStruct::__construct

    /********************************************************************
     *  function NoteStruct::add                                        *
     *                                                                  *
     *  This factory method processes immediate children of the NOTE    *
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
        {                       // act on specific next level tags
            case 'CONC':
            {                   // concatenate onto field
                $this->parms    .= $parms;
                return null;
            }                   // concatenate onto field

            case 'CONT':
            {       // concatenate onto field with a space separation
                $this->parms    .= "\n" .  $parms;
                return null;
            }       // concatenate onto field with a space separation

            case 'SOUR':
            {                   // <<SOURCE_CITATION>> {0:M}
                $childTag           = new SourceCit($level,
                                                    $this->id,
                                                    $tagname,
                                                    $parms,
                                                    $this->document,
                                                    $this,
                                                    $this->fieldname);
                break;
            }                   // <<SOURCE_CITATION>> {0:M}

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
    }       // NoteStruct::add

}       // class NoteStruct

/************************************************************************
 *  class SourceCit                                                     *
 *                                                                      *
 *  This class represents the behavior specific to the source citation  *
 *  under any tag.                                                      *
 *                                                                      *
 *    <SOURCE_CITATION>                                                 *
 *      n SOUR @<XREF:SOUR>@  pointer to source record                  *
 *       +1 PAGE <WHERE_WITHIN_SOURCE>                                  *
 *       +1 EVEN <EVENT_TYPE_CITED_FROM>                                *
 *        +2 ROLE <ROLE_IN_EVENT>                                       *
 *       +1 DATA                                                        *
 *        +2 DATE <ENTRY_RECORDING_DATE>                                *
 *        +2 TEXT <TEXT_FROM_SOURCE>                                    *
 *         +3 [ CONC | CONT ] <TEXT_FROM_SOURCE>                        *
 *       +1 QUAY <CERTAINTY_ASSESSMENT>                                 *
 *       +1 <<MULTIMEDIA_LINK>>                                         *
 *       +1 <<NOTE_STRUCTURE>>                                          *
 *                                                                      *
 *       Systems not using source records                               *
 *      n SOUR <SOURCE_DESCRIPTION>                                     *
 *       +1 [ CONC | CONT ] <SOURCE_DESCRIPTION>                        *
 *       +1 TEXT <TEXT_FROM_SOURCE>                                     *
 *        +2 [CONC | CONT ] <TEXT_FROM_SOURCE>                          *
 *       +1 <<NOTE_STRUCTURE>>                                          *
 *                                                                      *
 ************************************************************************/
require_once "Genealogy/Citation.inc";
use Genealogy\Citation;

class SourceCit extends Tag
{
    // translate string event types to internal citation type code
    static $citTypes    = array(
                        'name'          => Citation::STYPE_NAME,
                        'birth'         => Citation::STYPE_BIRTH,
                        'chris'         => Citation::STYPE_CHRISTEN,
                        'death'         => Citation::STYPE_DEATH,
                        'buried'        => Citation::STYPE_BURIED,
                        'notes'         => Citation::STYPE_NOTESGENERAL,
                        'researchnotes' => Citation::STYPE_NOTESRESEARCH,
                        'medicalnotes'  => Citation::STYPE_NOTESMEDICAL,
                        'deathcause'    => Citation::STYPE_DEATHCAUSE,
                        'altname'       => Citation::STYPE_ALTNAME,
                        'cstatus'       => Citation::STYPE_CHILDSTATUS,
                        'cpreldad'      => Citation::STYPE_CPRELDAD,
                        'cprelmom'      => Citation::STYPE_CPRELMOM,
                        'baptism'       => Citation::STYPE_LDSB,
                        'endow'         => Citation::STYPE_LDSE,
                        'sealchild'     => Citation::STYPE_LDSP,
                        'sealspouse'    => Citation::STYPE_LDSS,
                        'nevmarried'    => Citation::STYPE_NEVERMARRIED,
                        'mar'           => Citation::STYPE_MAR,
                        'marrnote'      => Citation::STYPE_MARNOTE,
                        'marnever'      => Citation::STYPE_MARNEVER,
                        'marnokids'     => Citation::STYPE_MARNOKIDS,
                        'marend'        => Citation::STYPE_MAREND,
                        'confirmation'  => Citation::STYPE_LDSC,
                        'initiatory'    => Citation::STYPE_LDSI,
                        'indievent'     => Citation::STYPE_EVENT,
                        'famevent'      => Citation::STYPE_MAREVENT,
                        'todo'          => Citation::STYPE_TODO
                        );

    /********************************************************************
     *  function SourceCit::__construct                                 *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      the reference id or source description          *
     *      $document       containing instance of Document             *
     *      $parent     parent instance of Tag                          *
     *      $newfield   field name within the parent's instance of Record*
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent,
                         $newfield)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>SourceCit::__construct($level," .
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

        $type   = strtolower($newfield);
        if (!array_key_exists($type, self::$citTypes))
            $warn   .= '<p class="message">SourceCit::__construct: ' .
                            "Unexpected event type '$newfield'</p>\n";

    }       // SourceCit::__construct

    /********************************************************************
     *  function SourceCit::add                                         *
     *                                                                  *
     *  This factory method processes immediate children of the         *
     *  Source tag defining a source citation                           *
     *                                                                  *
     *  Input:                                                          *
     *      $level          the level in the hierarchy                  *
     *      $id             the reference identifier                    *
     *      $name           the tag name (type)                         *
     *      $parms          any parameters                              *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of Tag                                             *
     ********************************************************************/
    function add($level, $id, $tagname, $parms)
    {
        $childTag       = null;
        $tagname        = strtoupper($tagname);
        switch($tagname)
        {                       // act on specific next level tags
            case 'PAGE':
            {                   // identify location in source
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }                   // identify location in source

            case 'DATA':
            {
                $childTag           = new SourceCitData($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                              $this->document,
                                                        $this);
                break;
            }           // end of Source citation DATA tag

            case 'QUAY':    // Certainty assessment tag
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // Certainty assessment tag

            case 'NOTE':    // NOTE structure
            {
                $childTag           = new NoteStruct($level,
                                                     $this->id,
                                                     $tagname,
                                                     $parms,
                                              $this->document,
                                                     $this,
                                                     'srcdetnote');
                break;
            }               // Note structure

            case 'EVEN':    // EVENT_TYPE_CITED_FROM
            case 'OBJE':    // MULTIMEDIA_LINK
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;      // ignore for now
            }

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
    }       // SourceCit::add

}       // class SourceCit

/************************************************************************
 *  class SourceCitData                                                 *
 *                                                                      *
 *  This class represents the behavior specific to the DATA tag         *
 *  under a source citation tag.                                        *
 *       +1 DATA                                                        *
 *        +2 DATE <ENTRY_RECORDING_DATE>                                *
 *        +2 TEXT <TEXT_FROM_SOURCE>                                    *
 *         +3 [ CONC | CONT ] <TEXT_FROM_SOURCE>                        *
 *                                                                      *
 ************************************************************************/
class SourceCitData extends Tag
{

    /********************************************************************
     *  function SourceCitData::__construct                             *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      the reference id or source description          *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>SourceCitData::__construct($level, " .
                         "'$id', " .
                         "'$name', " .
                         "'$parms')</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
    }       // SourceCitData::__construct

    /********************************************************************
     *  function SourceCitData::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of the         *
     *  DATA tag under a source citation                                *
     *                                                                  *
     *  Input:                                                          *
     *      $level          the level in the hierarchy                  *
     *      $id             the reference identifier                    *
     *      $name           the tag name (type)                         *
     *      $parms          any parameters                              *
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
            case 'DATE':    // DATE
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }           // entered date

            case 'TEXT':
            {
                $childTag           = new TextTag($level,
                                                  $this->id,
                                                  $tagname,
                                                  $parms,
                                              $this->document,
                                                  $this,
                                                  'srcdettext');
                break;
            }           // end of Source citation DATA tag

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
    }       // SourceCit::add

}       // class SourceCit

/************************************************************************
 *  class TextTag                                                       *
 *                                                                      *
 *  This class represents the behavior specific to the TEXT structure   *
 *  under any tag.                                                      *
 *                                                                      *
 *      n TEXT [<TEXT> | <NULL>]                                        *
 *      +1 [ CONC | CONT ] <TEXT>                                       *
 *                                                                      *
 *  Note that the base class Tag implements support for continuation.   *
 ************************************************************************/
class TextTag extends Tag
{

    /********************************************************************
     *  function TextTag::__construct                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
     *      $parent     instance of Tag                                 *
     *      $newrecord  associated instance of Record                   *
     *      $newfield   field name within the instance of Record        *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent,
                         $newrecord = null,
                         $newfield  = null)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>TextTag::__construct($level," .
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
        $this->fieldname= $newfield;
    }       // TextTag::__construct

}       // class TextTag

/************************************************************************
 *  class AddressStructure                                              *
 *                                                                      *
 *  This class represents the behavior specific to an Address Structure *
 *  tag.                                                                *
 *                                                                      *
 *      n ADDR <ADDRESS_LINE> {0:1}                                     *
 *      +1 CONT <ADDRESS_LINE> {0:M}                                    *
 *      +1 ADR1 <ADDRESS_LINE1> {0:1}                                   *
 *      +1 ADR2 <ADDRESS_LINE2> {0:1}                                   *
 *      +1 CITY <ADDRESS_CITY> {0:1}                                    *
 *      +1 STAE <ADDRESS_STATE> {0:1}                                   *
 *      +1 POST <ADDRESS_POSTAL_CODE> {0:1}                             *
 *      +1 CTRY <ADDRESS_COUNTRY> {0:1}                                 *
 *      +1 _NAME <TEXT> {0:1}                                           *
 *                                                                      *
 ************************************************************************/
class AddressStructure extends Tag
{
    // instances of AddressStructure as an array
    private static  $allAddresses   = array();

    /********************************************************************
     *  function AddressStructure::__construct                          *
     *                                                                  *
     *  Input:                                                          *
     *      $level      the level in the hierarchy                      *
     *      $id         the reference identifier                        *
     *      $name       the tag name (type)                             *
     *      $parms      any parameters                                  *
     *      $document       containing instance of Document             *
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
            $warn   .= "<p>AddressStructure::__construct($level," .
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
    }       // AddressStructure::__construct

    /********************************************************************
     *  function AddressStructure::add                                  *
     *                                                                  *
     *  This factory method processes immediate children of the ADDR    *
     *  tag in and Address Structure.                                   *
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
            case 'CONC':
            {       // concatenate onto field
                $this->parms    .= $parms;
                return null;
            }       // concatenate onto field

            case 'CONT':
            {       // concatenate onto field with a space separation
                $this->parms    .= "\n" .  $parms;
                return null;
            }       // concatenate onto field with a space separation

            case 'ADR1':    // Address_LINE1
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address line 1

            case 'ADR2':    // Address_LINE2
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address line 2

            case 'CITY':    // Address_CITY
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address City

            case 'STAE':    // Address_STATE
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address State

            case 'POST':    // Address_POSTAL_CODE
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address postal code

            case 'CTRY':    // Address_COUNTRY
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address country

            case '_NAME':   // name of the address
            {
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                break;
            }               // address country

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
    }       // AddressStructure::add

}       // class AddressStructure

/************************************************************************
 *  class IgnoreSubtags                                                 *
 *                                                                      *
 *  This class represents the behavior specific to an unimplemented     *
 *  tag that is known to have specific subtags.                         *
 *  An instance of Tag is created for each sub-tag and a warning        *
 *  is issued for each tag name that is not in the supported list.      *
 *  All 2nd level subtags will generate warnings.                       *
 ************************************************************************/
class IgnoreSubtags extends Tag
{
    // array of subtag tag names
    private $subtags    = null;

    /********************************************************************
     *  function IgnoreSubTags::__construct                             *
     *                                                                  *
     *  Input:                                                          *
     *      $level          the level in the hierarchy                  *
     *      $id             the reference identifier                    *
     *      $name           the tag name (type)                         *
     *      $parms          any parameters                              *
     *      $document       containing instance of Document             *
     *      $parent         parent tag                                  *
     *      $subtags        array of permitted sub-tag names            *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
                         $document,
                         $parent,
                         $subtags   = null)
    {
        global  $warn;
        global  $debug;

        if ($debug)
            $warn   .= "<p>IgnoreSubTags::__construct($level," .
                                    "  '$id'," .
                                    "  '$name'," .
                                    "  '$parms'," .
                                    '  $document,' .
                                    "  $parent)</p>\n";
        parent::__construct($level,
                            $id,
                            $name,
                            $parms,
                            $document,
                            $parent);
        if (is_null($subtags))
            $this->subtags      = array();
        else
            $this->subtags      = $subtags;
    }       // function IgnoreSubTags::__construct

    /********************************************************************
     *  function IgnoreSubtags::add                                     *
     *                                                                  *
     *  This factory method processes immediate children of the NOTE    *
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
        $childTag               = null;
        $tagname                = strtoupper($tagname);
        switch($tagname)
        {
            case 'CONC':
            {       // concatenate onto field
                $this->parms    .= $parms;
                break;
            }       // concatenate onto field

            case 'CONT':
            {       // concatenate onto field with a space separation
                $this->parms    .= "\n" .  $parms;
                break;
            }       // concatenate onto field with a space separation

            default:
            {                   // all others unexpected
                $childTag       = new Tag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                              $this->document,
                                          $this);
                if (!in_array($tagname, $this->subtags))
                {       // warn if not in list
                    $childTag->diagnostic('Unexpected');
                }       // warn if not in list
                break;
            }                   // all others unexpected
        }       // act on specific next level tags

        $this->register($childTag);
        return $childTag;
    }       // function IgnoreSubtags::add

}       // class IgnoreSubtags
