<?php
namespace Templating;
use \PDO;
use \Exception;
use \ArrayAccess;
use \SplDoublyLinkedList;
use \Genealogy\Record;
use \Genealogy\RecordSet;

/************************************************************************
 *  Class Template                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2015/11/27  Migrate to PDO for database access                  *
 *                  use PHP 5 class structure                           *
 *                  standardize nesting                                 *
 *                  add documentation                                   *
 *                  strpos was used improperly                          *
 *                  the methods replace_field and value, which          *
 *                  referenced the table initialized by the method      *
 *                  add would never have worked because they            *
 *                  referenced an undefined state variable!             *
 *                  The methods replace and replace_field were          *
 *                  inefficient because they duplicated the             *
 *                  substitution functionality in method compile.       *
 *                  Method compile was inefficient because it           *
 *                  did a separate search and replace for each          *
 *                  substitution supplied by method add.                *
 *                  It now performs all substitutions (which it         *
 *                  claimed erroneously to do before) in a single       *
 *                  pass over the template.  The old implementation     *
 *                  was O(N*L) on the number of substitutions.          *
 *                  The new implementation is O(L) on the length        *
 *                  of the template.                                    *
 *                                                      James Cobban    *
 *      2016/03/04  add method addSelect                James Cobban    *
 *      2016/05/04  permit spaces around substitution names             *
 *                  prevent infinite loop if clear is false             *
 *                                                      James Cobban    *
 *      2016/05/06  addSelect set wrong string in template              *
 *                                                      James Cobban    *
 *      2016/09/23  add method updateTag                                *
 *                                                      James Cobban    *
 *      2016/10/24  protect updateTag against easy parameter            *
 *                  errors                              James Cobban    *
 *      2016/10/27  remove id attribute only if more than one           *
 *                  array entry                         James Cobban    *
 *      2016/10/31  support nested tags with updateTag                  *
 *                                                      James Cobban    *
 *      2016/11/17  add method getRawTemplate to eliminate public       *
 *                  access to member $page              James Cobban    *
 *      2017/04/02  permit being used from command line                 *
 *                                                      James Cobban    *
 *      2017/04/04  accept full text of insertion on include            *
 *                                                      James Cobban    *
 *      2017/04/18  add diagnostic info for errors in include           *
 *                                                      James Cobban    *
 *      2017/10/17  support class RecordSet             James Cobban    *
 *      2017/11/13  support deleting tags whose id starts with          *
 *                  "delete" and contains an insertion point whose      *
 *                  value is the empty string           James Cobban    *
 *      2017/11/15  support passing an array or RecordSet as the        *
 *                  value of a substitution in updateTag                *
 *                                      James Cobban                    *
 *      2018/01/11  escape HTML special characters      James Cobban    *
 *      2018/01/14  add function TemplateTag::update                    *
 *                  add function TemplateTag::getId                     *
 *                  change class ArrayInsertion to take instance        *
 *                  of TemplateTag instead of the id of an instance     *
 *                  add function Template::addTagUpdate                 *
 *                  in debugging code map { to &#125; and } to          *
 *                  &#123; instead of &rbrace; and &lbrace to           *
 *                  support XML                                         *
 *                  add diagnostics for tags with missing end tags      *
 *                                                      James Cobban    *
 *      2018/01/18  add $template->debug to control debug output        *
 *                  from Template logic                                 *
 *                  do not add tags inside a <template> to lookup       *
 *                  add support for PHP style, $name, insertions        *
 *      2018/01/23  add method getElementsByTagName                     *
 *      2018/01/25  separate basic functionality in class Template      *
 *                  from site-specific functionality which is           *
 *                  performed in a derived class.                       *
 *      2018/01/31  isolate the implementation further from the         *
 *                  environment of the Family Tree site                 *
 *                  support language specific master templates          *
 *                  support IE 7                                        *
 *      2018/02/10  correct handling of attribute values in tag         *
 *      2018/03/14  use multi-byte safe string functions                *
 *      2018/04/22  define constants for brace style insertions         *
 *      2018/04/24  use linked list for better performance in           *
 *                  innermost loop                                      *
 *      2018/05/28  add new substitution OTHERSTYLESHEETS in            *
 *                  basic page templates                                *
 *      2018/09/15  move initialization of fields used in               *
 *                  FamilyTree page template to customization           *
 *      2018/09/28  support passing text of template to constructor     *
 *                  apply tag updates for set(,,true) to permit         *
 *                  manipulating tags within the value                  *
 *                  pass full substitution text to TemplateTag::update  *
 *      2018/11/02  add definition of GOOGLECC and GOOGLEKEY            *
 *      2018/11/06  don't use multibyte string functions                *
 *                  change implementation of alternate stylesheets      *
 *      2018/11/14  method setFields accepts class implementing Iterator*
 *      2018/11/16  implement readonly ArrayAccess to tags by id        *
 *      2018/11/18  permit script to define BREADCRUMBS                 *
 *      2018/11/28  support requesting an alternate language page       *
 *                  template                                            *
 *      2018/12/22  include substitutions defined by set in expansion   *
 *                  of tags.                                            *
 *      2019/01/30  move breadcrumbs into a menu requested by hamburger *
 *                  move help links from <h1> into menu                 *
 *                  include link to signon or account management in     *
 *                  menu                                                *
 *      2019/02/08  if possible replace advertising <iframe> with       *
 *                  the contents of the <body> of the ad file           *
 *                  selection of random advertisement moved her from    *
 *                  common.inc                                          *
 *      2019/02/19  FtTemplate constructor consolidates all of the      *
 *                  functions for creating a template with knowledge    *
 *                  of the current application script and support       *
 *                  for I18n                                            *
 *      2019/03/07  when replacing a tag with a string adjust the       *
 *                  length of the hierarchy of parent tags so that      *
 *                  if those tags are substituted they will still       *
 *                  refer to the complete tag.                          *
 *                  When replaceing a tag with text hide all of the     *
 *                  tags identified by id= which are not there any      *
 *                  more because of the replacement                     *
 *      2019/07/28  improve handling of missing closing tags and        *
 *                  report them as warnings                             *
 *                  ensure $msg an $warn are defined in case this       *
 *                  is not invoked as part of the Genealogy app         *
 *      2019/12/04  add support for array and object references in      *
 *                  substitution names                                  *
 *      2020/04/23  improve handling of invalid HTML and restructure    *
 *                  tag handling into separate startTag and closeTag    *
 *                  functions                                           *
 *      2020/05/03  fix interaction of set with third parameter         *
 *      2020/11/16  add list of includes to warning messages            *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban All Rights Reserved           *
 ************************************************************************/
require_once __NAMESPACE__ . '/TemplateTag.inc' ;

/************************************************************************
 *  $msg                                                                *
 *                                                                      *
 *  Initialize common variable for accumulating error messages.         *
 *  This means that class Template can be used by code that does not    *
 *  include /Genealogy/common.inc                                       *
 ************************************************************************/
if (!isset($msg) || !is_string($msg))
    $msg        = '';

/************************************************************************
 *  $warn                                                               *
 *                                                                      *
 *  Initialize common variable for accumulating warning messages.       *
 ************************************************************************/
if (!isset($warn) || !is_string($warn))
    $warn       = '';

/************************************************************************
 *  class ArrayInsertion                                                *
 *                                                                      *
 *  Encapsulate information maintained about a request to perform       *
 *  an array insertion into a tag element                               *
 ************************************************************************/
class ArrayInsertion
{
    public  $tag;       // the associated instance of TemplateTag
    public  $array;     // array of entries with substitution info
    public  $start;     // start position in main template
    public  $end;       // end position in main template

    public function __construct($newtag,
                                $newarray)
    {
        if ($newtag instanceof TemplateTag)
        {
            $this->tag      = $newtag;
            $this->array    = $newarray;
            $this->start    = $newtag->start;
            $this->end      = null;     // not known yet
        }
        else
            throw new Exception('ArrayInsertion::__construct: ' .
                'first parameter is not an instance of TemplateTag');
    }       // function ArrayInsertion::__construct

}       // class ArrayInsertion

class Template implements ArrayAccess
{
    /********************************************************************
     *   State Variables                                                *
     ********************************************************************/
    private     $clear;         // if true discard unreferenced substitutions
    protected   $fields;        // array of field name values
    protected   $main;          // name of the main page file or record
    private     $mainValid;     // true if the main page file name is valid
    private     $sub;           // name of the sub-page file or record
    private     $submarker;     // name of substitution to replace with subpage
    protected   $includes;      // array of file insertions

    protected   $page;          // the contents of the template
                                // use $template->getRawTemplate()
    private     $cursor;        // position within page during parse
    private     $inTemplate;    // between <template> and </template>
    private     $tagStack;      // stack of tags.  $tagStack[0] == $dom
    public      $dom;           // the template parsed into a DOM

    private     $db;            // if true get template from database
                                // if false get template from file
    private     $table;         // name of the database table if $db is true
    private     $phpMode;       // substitution points use PHP syntax
    protected   $tagUpdates;    // instances of ArrayInsertion
    public      $debug      = false;// enable debugging
    public      $test       = false;// enable debugging
    private     $userCanDebug;  // invoked by administrator?

    // parsing
    private     $tag        = '';   // extracting sub-template
    private     $subTemplate= '';   // sub-template
    private     $quote      = '';   // opening quote of id attribute
    private     $substitution= null;// object to insert into sub-template
    private     $inQuote    = false;// if true inside string constant
    private     $lastDelete = 0;    // id of current tag delete
    private     $insertions = 0;    // count number of insertions

    const   OPEN_BRACE      = '{{';
    const   CLOSE_BRACE     = '}}';

    // the pattern match for a name cannot be
    // just \w+ because that would match $15
    // the first character of the name must not be a digit
    // or an ASCII special character but may be any
    // Unicode character that is not in the ASCII set
    // the template engine does not care about the contents
    // of the "name" but for consistency this pattern
    // is designed to match the syntax supported by PHP
    // which includes an optional subscript or
    // reference to a member of an object
    const   NAME_PATT       = '/^[a-zA-Z_\x{0080}-\x{FFFF}][\w\x{0080}-\x{FFFF}]*(\[-?\w+\](\[-?\w+\]|)|->[a-zA-Z_\x{0080}-\x{FFFF}][\w\x{0080}-\x{FFFF}]*)?/u';
    /********************************************************************
     *  function Template::__construct                                  *
     *                                                                  *
     *  Input:                                                          *
     *      $init   a string containing the filename of the main        *
     *              template file, or if $text is true, or the          *
     *              value contains a "<" or "$", the actual text        *
     *              of the template                                     *
     *      $text   if true then $init is the text of the template      *
     *                                                                  *
     *  Examples:                                                       *
     *      $template = new Template($root . '/page.html');             *
     *      $template = new Template('<tag ...');                       *
     ********************************************************************/
    public function __construct($init = null, $text = false)
    {
        global  $debug;
        global  $warn;
        global  $msg;

        if ($this->debug)
        {
            if (is_string($init))
            {
                if (strlen($init) > 200)
                    $inittext   = substr($init, 0, 100) . '...' .
                                  substr($init, -97);
                else
                    $inittext   = $init;
                $warn   .= "<p>Template::__construct('" . htmlspecialchars($inittext) .
                           "')</p>\n";
            }
            else
                $warn   .= "<p>Template::__construct()</p>\n";
            ob_start();
            debug_print_backtrace();
            $warn   .= '<p>' . ob_get_clean() . "</p>\n";
        }

        // initialize defaults
        $this->clear            = true;
        $this->fields           = array();
        $this->page             = null;
        $this->main             = $init;
        $this->sub              = null;
        $this->submarker        = 'SUBPAGE';
        $this->includes         = array();
        $this->table            = 'templates';
        $this->db               = false;
        $this->userCanDebug     = isset($user) && $user->can('debug');

        // the Document Object Model used for tag based substitution
        $this->dom              = new TemplateTag($this, -1);
        $this->tagStack         = array($this->dom);
        $this->tagUpdates       = array();

        $this->customization();     // site specific customization

        if (is_string($init))
        {
            if ($text ||
                strpos($init, '<') !== false ||
                strpos($init, '$') !== false)
            {
                $this->page         = $init;
                $this->main         = 'string template';
                $backtrace          = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                foreach($backtrace as $caller)
                {
                    $this->main     .= ', ' . $caller['file'] .
                                        ':' . $caller['line'];
                }
                $this->mainValid    = true;
                $this->phpMode      = strpos($init, self::OPEN_BRACE) === false;
            }
            else
            {
                if (preg_match('/^\w+:/', $init))
                    $this->mainValid    = true;
                else
                    $this->mainValid    = file_exists($this->main);
                if (!$this->mainValid)
                    $warn   .= "<p>Template::construct: " . __LINE__ . " Invalid parameter to Template constructor '" . htmlspecialchars($init) . "'</p>\n";
            }
        }
        else
        {
            $warn   .= "<p>Template::construct: " . __LINE__ . " Invalid parameter to Template constructor " .
                           gettype($init) . "</p>\n";
            $this->mainValid    = false;
        }
    }       // method Template::construct

    /********************************************************************
     *  function Template::customization                                *
     *                                                                  *
     *  This is the default implementation of site specific             *
     *  customization of the initialization of an instance of Template. *
     *  This method is extended in a class derived from Template        *
     *  to perform common functionality that is required on every       *
     *  template used on your site.  Specifically this is used to       *
     *  initialize the variables used to fill in the basic page         *
     *  template whose file name is passed to the constructor.          *
     *                                                                  *
     ********************************************************************/
    protected function customization()
    {
    }       // method Template::customization

    /********************************************************************
     *  function Template::open                                         *
     *                                                                  *
     *  Change the main template file.  This is required if the main    *
     *  template file was not specified in the constructor.             *
     *  Calling this method is safer than updating $template->main      *
     *                                                                  *
     *  Returns:                                                        *
     *      true if the main file name exists, otherwise false          *
     ********************************************************************/
    public function open($filename)
    {
        $this->main         = $filename;
        $this->mainValid    = file_exists($this->main);
        return $this->mainValid;
    }       // method Template::open

    /********************************************************************
     *  function Template::fileExists                                   *
     *                                                                  *
     *  Determine whether the specified main template file exists.      *
     *                                                                  *
     *  Returns:                                                        *
     *      true if the main file name exists, otherwise false          *
     ********************************************************************/
    public function fileExists()
    {
        return $this->mainValid;
    }       // method Template::fileExists

    /********************************************************************
     *  function Template::set                                          *
     *                                                                  *
     *  Used to identify the value to substitute for a specific named   *
     *  field in the template.                                          *
     *                                                                  *
     *  INPUT:                                                          *
     *      $field  name to match in the template                       *
     *      $value  value to substitute into the template               *
     *              this may be an array or an object if the            *
     *              substitution points include subscripts or           *
     *              member references                                   *
     *      $doSubs perform substitutions into the value before         *
     *              saving it                                           *
     *              This is ignored if $value is not a string           *
     ********************************************************************/
    public function set($field, $value, $doSubs=false)
    {
        global  $warn;

        if (is_string($value) && strlen($value) > 0 && $doSubs)
        {
            $ttemplate              = new Template($value, true);
            $ttemplate->setFields($this->fields);
            $ttagUpdates            = array();
            foreach($this->tagUpdates as $tagUpdate)
            {
                $id                 = $tagUpdate->tag->id;
                $val                = $tagUpdate->array;
                $tag                = $ttemplate->getElementById($id);
                if ($tag)
                {               // tag is found in template
                    $tag->update($val);
                }               // tag is found in template
                else
                {               // tag should be in enclosing template
                    $ttagUpdates[]  = $tagUpdate;
                }               // tag should be in enclosing template
            }
            $this->tagUpdates       = $ttagUpdates;
            $value                  = $ttemplate->compile();
        }
        $this->fields[$field]       = $value;
    }       // method Template::set

    /********************************************************************
     *  function Template::get                                          *
     *                                                                  *
     *  Used to get the value to substitute for a specific named        *
     *  field in the template.                                          *
     *                                                                  *
     *  INPUT:                                                          *
     *      $field  name to match in the template                       *
     ********************************************************************/
    public function get($field)
    {
        return $this->fields[$field];
    }       // method Template::get

    /********************************************************************
     *  function Template::addTagUpdate                                 *
     *                                                                  *
     *  Add an instance of ArrayInsertion to the table tagUpdates       *
     *                                                                  *
     *  Input:                                                          *
     *      $tagUpdate  instance of ArrayInsertion                      *
     ********************************************************************/
    public function addTagUpdate($tagUpdate)
    {
        global  $warn;
        if ($tagUpdate instanceof ArrayInsertion)
        {
            $this->tagUpdates[]             = $tagUpdate;
        }
        else
            $warn       .= "<p>Template::addTagUpdate: " . __LINE__ .
                            " invalid parameter ignored</p>\n";
    }       // function Template::addTagUpdate

    /********************************************************************
     *  function Template::updateTag                                    *
     *                                                                  *
     *  Use information from an array to create repeated HTML/XML text  *
     *  based upon a tag identified by its id= attribute.               *
     *                                                                  *
     *  This is a short-hand way to do the following:                   *
     *      $tag    = $template->getElementById($id);                   *
     *      $tag->update($array);                                       *
     *                                                                  *
     *  Input:                                                          *
     *      $id     value of the id attribute identifying a tag         *
     *          in the template.                                        *
     *      $array  an array of entries providing named values          *
     *          to be used to replace matching substitution             *
     *          points in the piece of text identified by               *
     *          the first parameter.                                    *
     *          Each entry in this array may either be an               *
     *          associative array or an instance of Record.             *
     ********************************************************************/
    public function updateTag($id,
                              $array)
    {
        global $warn;

        $backtrace  = debug_backtrace();
        $caller     = current($backtrace);
        if ($this->debug)
        {
            $warn   .= "<p>Template::updateTag: " .  __LINE__ . " id=";
            if ($id instanceof TemplateTag)
                $warn   .= htmlspecialchars($tag->outerHTML()) . ", ";
            else
                $warn   .= htmlspecialchars($id) . ", ";
            if (is_array($array))
                $warn   .= "count(\$array)=" . count($array);
            else
            if ($array instanceof Record)
                $warn   .= "\$array=" . get_class($array);
            else
                $warn   .= "\$array=" . print_r($array,true);
            $warn       .= " called from " . $caller['file'] .
            ':' . $caller['line'] . "</p>\n";
        }
        $this->parseHTML(); // ensure the DOM is built

        if ($id instanceof TemplateTag)
            $tag    = $id;
        else
            $tag    = $this->getElementById($id);
        if ($tag)
            $tag->update($array);
        else
            $warn   .= "<p>Template::updateTag: " .  __LINE__ .
                " element with specified tag id='$id' not found in document.</p>";
    }       // Template::updateTag

    /********************************************************************
     *  function Template::setFields                                    *
     *                                                                  *
     *  Supply a set of name value substitutions.                       *
     *  This function is inherited from the original implementation     *
     *  of Template, where in fact it had a bug which meant that it     *
     *  didn't work.  It is deprecated in favor of Template::updateTag  *
     *  or TemplateTag::update.                                         *
     *                                                                  *
     *  INPUT:                                                          *
     *      $fields     associative array of named substitutions        *
     *                  or an instance of a class that implements       *
     *                  interface Iterator                              *
     ********************************************************************/
    public function setFields($fields)
    {
        if (is_array($fields) ||
            $fields instanceof Iterator)
        {
            foreach($fields as $field => $value)
            {
                $this->fields[$field]   = $value;
            }
        }
    }       // method Template::setFields

    /********************************************************************
     *  function Template::includeSub                                   *
     *                                                                  *
     *  Specify a template file which is to be included and the name    *
     *  of the insertion point where it is to be inserted.              *
     *  I would have preferred to call this function "include" but that *
     *  is a reserved word in PHP 5.6 although not in PHP 7.            *
     *                                                                  *
     *  INPUT:                                                          *
     *      $filename   file name to include                            *
     *          If the supplied value contains an insertion             *
     *          point or an HTML tag, it is the actual data             *
     *          to insert.                                              *
     *          You might argue that this duplicates the                *
     *          function set, except that include insertions            *
     *          are performed before the first updateTag or set         *
     *          is acted on.                                            *
     *      $submarker  insertion point name.  This is folded to        *
     *          upper case before searching the master template.        *
     *      $after  default false means to perform the insertion        *
     *          before applying text substitutions specified            *
     *          by $template->set or $template->updateTag.              *
     *          If true this insertion is done after text               *
     *          substitution.  This is required if the include          *
     *          file contains references that might be falsely          *
     *          identified as insertion points.                         *
     *                                                                  *
     *  Returns:                                                        *
     *      true if the requested file exists, otherwise false          *
     ********************************************************************/
    public function includeSub($filename,
                               $submarker   = 'SUBPAGE',
                               $after       = false)
    {
        global $warn;

        if ($this->debug)
        {
            $warn   .= "<p>Template::includeSub: " . __LINE__ .
                           " filename='" . $filename . "', submarker='" .
                           $submarker. "'";
            if ($after)
                $warn   .= ", after=true";
            $warn   .= "</p>\n";
        }
        if ($this->dom->length > 0)
        {
            throw new Exception("Template::includeSub: " . __LINE__ .
                " called after template parsed");
        }

        if ($after)
        {           // do include after substitutions
            if (file_exists($filename))
                $this->fields[$submarker]   = file_get_contents($filename);
            else
                $this->fields[$submarker]   = "<p class='message'>Unable to optn file '$filename'</p>\n";
        }           // do include after substitutions
        else
        if (strpos($filename, self::OPEN_BRACE) !== false ||    // insertion point
            strpos($filename, '<') !== false ||     // or HTML
            file_exists($filename))         // or valid filename
        {           // do include before substitutions
            $this->includes[$submarker] = $filename;
            return true;
        }           // do include before substitutions
        else
        {           // invalid parameter
            error_log(__FILE__ . " " . __LINE__ .
                        " unable to open \$filename = '$filename'" .
                        " for template='" . $this->main . "'\n");
            $this->set($submarker, "<p class='message'>" .
                        __FILE__ . " " . __LINE__ .
                        " unable to open \$filename = '$filename'" .
                        " for template='" . $this->main . "'</p>\n");
            return false;
        }           // invalid parameter
    }       // method Template::includeSub

    /********************************************************************
     *  private function Template::breakTemplate                        *
     *                                                                  *
     *  Split all of the insertion points out of a template.            *
     *                                                                  *
     *  Input:                                                          *
     *      $template   string of HTML/XML                              *
     *                                                                  *
     *  Returns:                                                        *
     *      array, each entry of which consists of a substitution       *
     *      name and a block of raw text.                               *
     ********************************************************************/
    private function breakTemplate($template)
    {
        global $warn;

        $pieces                     = array();
        if ($this->phpMode)
        {           // extract $name style substitutions
            $start                  = 0;
            $piece                  = '';
            $name                   = '';
            $prevChar               = '';
            $LBRACE                 = '{';
            $RBRACE                 = '}';
            while(($this->cursor = strpos($template, '$', $start)) !== false)
            {           // loop through template
                if ($this->cursor > 0)
                    $prevChar       = $template[$this->cursor - 1];
                if ($prevChar == '\\')
                {       // escaped dollar sign
                    $piece          .= substr($template,
                                              $start,
                                              $this->cursor - $start - 1) .
                                              '$';
                    $start          = $this->cursor + 1;
                }       // escaped dollar sign
                else
                if ($prevChar == $LBRACE)
                {       // complex syntax insertion
                    $piece          .= substr($template,
                                              $start,
                                              $this->cursor - $start - 1);
                    if (strlen($name) > 0 || strlen($piece) > 0)
                        $pieces[]   = array($name, $piece);
                    $piece          = '';
                    $this->cursor++;    // advance over $ sign
                    $end            = strpos($template, $RBRACE, $this->cursor);
                    $name           = substr($template, $this->cursor, $end-$this->cursor);
                    // note that PHP permits the value of $name in this
                    // case to be an arbitrary expression, but this
                    // implementation requires it to be the name of
                    // a substitution value so the $ is stripped off
                    // the front
                    $start          = $end + 1; // advance over rbrace
                }       // complex syntax insertion
                else
                {       // basic syntax
                    $piece          .= substr($template,
                                              $start,
                                              $this->cursor - $start);
                    $matches        = array();
                    // the pattern match for a name cannot be
                    // just \w+ because that would match $15
                    if (preg_match(self::NAME_PATT,
                                   substr($template, $this->cursor + 1),
                                   $matches))
                    {       // have a valid variable name
                        $tname      = $matches[0];
                        if (strlen($name) > 0 || strlen($piece) > 0)
                        {
                            $pieces[]   = array($name, $piece);
                        }
                        $piece      = '';
                        $name       = $tname;
                        $start      = $this->cursor + strlen($name) + 1;
                    }       // have a valid variable name
                    else
                    {       // a dollar sign as ordinary text
                            htmlspecialchars(substr($template, $this->cursor + 1, 20)) . "</p>\n";
                        // for example $15.25 will come here
                        $piece      .= '$';
                        $start      = $this->cursor + 1;
                    }       // a dollar sign as ordinary text
                }       // basic syntax
            }           // loop through template
            $piece                  .= substr($template, $start);
            if (strlen($name) > 0 || strlen($piece) > 0)
                $pieces[]           = array($name,
                                            $piece);
        }           // extract $name style substitutions
        else
        {           // extract {{name}} style substitutions
            $temp                   = explode(self::OPEN_BRACE, $template);
            foreach($temp as $piece)
            {
                $split              = explode(self::CLOSE_BRACE, $piece);
                if (count($split) > 1)
                {
                    $name       = trim($split[0]);
                    $pieces[]   = array($name, $split[1]);
                }
                else
                {
                    $pieces[]   = array('', $split[0]);
                }
            }
        }           // extract {{}} style substitutions
        if ($this->debug)
        {
            $warn   .= "<p>Template::breakTemplate: " . __LINE__ .
                        " \$pieces=[</p>\n<table>\n<tbody>\n";
            foreach($pieces as $i => $piece)
            {
                $warn   .= "<tr>\n";
                $name   = $piece[0];
                $text   = $piece[1];
                if ($name != 'COUNT' && strlen($text) > 100)
                {
                    $skipped    = strlen($text) - 100;
                    $text       = substr($text, 0, 80) . "...$skipped..." .
                                    substr($text, -13);
                }
                $warn   .= "<th>$i&nbsp;'$name'</th><td>" . htmlspecialchars($text) .
                            "</td>\n";
                $warn   .= "</tr>\n";
            }
            $warn       .= "</tbody>\n";
            $warn       .= "</table>\n";
        }           // debug
        return $pieces;
    }       // function breakTemplate

    /********************************************************************
     *  function Template::displayBegin                                 *
     *                                                                  *
     *  This is the default implementation of site specific             *
     *  customization which is performed immediately prior to           *
     *  generating the output of the Template.                          *
     *  This method is extended in each derived class.                  *
     ********************************************************************/
    protected function displayBegin()
    {

    }       // function Template::displayBegin

    /********************************************************************
     *  function Template::displayEnd                                   *
     *                                                                  *
     *  This is the default implementation of site specific             *
     *  customization which is performed immediately prior to           *
     *  returning the output of the Template to the requestor.          *
     *  This method is extended in each derived class.                  *
     ********************************************************************/
    protected function displayEnd()
    {
    }       // function Template::displayEnd

    /********************************************************************
     *  function Template::compile                                      *
     *                                                                  *
     *  Construct the web page by applying all of the substitutions     *
     *  to the template.                                                *
     *                                                                  *
     *  Return:                                                         *
     *      The completed HTML/XML with all substitutions made.         *
     ********************************************************************/
    public function compile()
    {
        global  $warn;
        global  $msg;

        // load the template text into the object and set $this->page
        $this->page     = $this->loadPage();
        if ($this->debug)
            $warn       .= "<p>Template::compile: " . __LINE__ .
                        " \$this->page='" . htmlspecialchars($this->page) . "'</p>\n";

        /****************************************************************
         * Perform site specific customization                          *
         ****************************************************************/
        $this->displayBegin();

        /****************************************************************
         * Perform updates of individual tags                           *
         ****************************************************************/
        $this->doTagUpdates();

        /****************************************************************
         *  Replace all global Markers with Values                      *
         *                                                              *
         *  This is now the only code which applies changes to the      *
         *  template, permitting this to be done in one sequential      *
         *  pass over the template rather than multiple search and      *
         *  replace.  It also supports case-insensitive name matching   *
         ****************************************************************/
        //$tpage            = htmlspecialchars($this->page);
        //$tpage            = str_replace("\n", "</p><p>", $tpage);
        //$tpage            = str_replace("\t", "\\t", $tpage);

        $pieces         = $this->breakTemplate($this->page);
        // start rebuilding the page while applying substitutions
        if (count($pieces) > 0)
        {                       // there are substitution points
            $page   = '';       // start rebuilding with subs
            foreach($pieces as $piece)
            {                   // loop through all substitutions
                $name               = $piece[0];
                $rawtext            = $piece[1];

                if (strlen($name) > 0)
                {               // insert value of fields[$name]
                    $lbpos          = strpos($name, '[');
                    $arpos          = strpos($name, '->');
                    $subscript      = '';
                    $member         = '';
                    if ($lbpos !== false)
                    {           // subscript present
                        $subscript  = substr($name, $lbpos);
                        $name       = substr($name, 0, $lbpos);
                    }           // subscript present
                    else
                    if ($arpos !== false)
                    {           // member reference present
                        $member     = substr($name, $arpos + 2);
                        $name       = substr($name, 0, $lbpos);
                        $warn       .= "<p>Template.inc: " . __LINE__ .
                            " found substitution for '$name' with member reference '$member'</p>\n";
                    }           // member reference present

                    if (array_key_exists($name, $this->fields))
                    {               // name found in substitutions
                        $replace    = $this->fields[$name];
                        if (is_string($replace) || is_numeric($replace))
                        {               // replacement value is simple value
                            $page       .= $replace;
                        }               // replacement value is simple value
                        else
                        if (is_array($replace))
                        {               // replacement value is array
                            if (count($replace) > 0 && strlen($subscript) > 0)
                            {
                                if (preg_match('/\[(\w+)\](\[(\w+)\]|)/',
                                               $subscript,
                                               $subs))
                                {
                                    $sub1       = $subs[1];
                                    if (ctype_digit($sub1))
                                        $sub1   = intval($sub1);
                                    if (array_key_exists($sub1, $replace))
                                    {
                                        $val            = $replace[$sub1];
                                        if (is_array($val) && count($subs) > 3)
                                        {       // 2 subscripts
                                            $sub2       = $subs[3];
                                            if (ctype_digit($sub2))
                                                $sub2   = intval($sub2);
                                            if (array_key_exists($sub2, $val))
                                            {
                                                $page   .= $val[$sub2];
                                            }
                                        }       // 2 subscripts
                                        else
                                            $page       .= $val;
                                    }
                                }
                                else
                                    $warn   .= "<p>Template::compile: " . __LINE__ .
                                                " '$subscript' does not match pattern</p>\n";

                            }
                        }               // replacement value is array
                        else
                        if (is_object($replace))
                        {               // replacement value is object
                            if (strlen($member) > 0)
                            {           // get value from member
                                $page   .= $replace->$member;
                            }           // get value from member
                        }               // replacement value is object
                        else
                        if (is_bool($replace))
                        {               // replacement value is boolean
                            if ($replace)
                                $page   .= 'true';
                            else
                                $page   .= 'false';
                        }               // replacement value is boolean
                        else
                        if (!is_null($replace))
                        {               // unsupported
                            $warn   .= "<p>Template::compile: " . __LINE__ .
                            " name='$name', replace=" . gettype($replace) .
                            ' ' .
                                htmlspecialchars(print_r($replace,true)). ", before '" .
                                htmlspecialchars($rawtext) . "'</p>\n";
                        }               // unsupported
                    }               // name found in substitutions
                    else
                    if ($this->phpMode)
                    {       // $name style insertions
                        if ($name == 'domainName')
                            print "<p>Template::compile: " . __LINE__ .
                                " No value for insertion point \$$name</p>";
                        if ($this->debug)
                        {
                            $warn   .= "<p>Template.inc: " . __LINE__ .
                                " No value for insertion point \$$name</p>\n";
                            foreach($this->fields as $name => $value)
                                $warn   .= "<p>Template.inc: " . __LINE__ .
                                " fields['$name']='$value'</p>\n";
                        }
                        if (preg_match('/^\w/', $rawtext) ||
                            !preg_match('/^\w+$/', $name))
                            $page   .= '{$' . $name . '}';
                        else
                            $page   .= '$' . $name;

                    }       // $name style insertions
                    else
                    {       // {{name}} style insertions
                        if ($this->debug)
                        {
                            $warn   .= "<p>Template.inc: " . __LINE__ .
                                            ' No value for insertion point ' .
                                            self::OPEN_BRACE .
                                        $name . self::CLOSE_BRACE . "</p>\n";
                            $warn   .= "<p>Keys=";
                            foreach($this->fields as $key => $object)
                                $warn   .= "'$key', ";
                            $warn   .= "</p>\n";
                        }
                        if (!$this->clear)
                        {       // leave substitution point in place
                            $page   .= self::OPEN_BRACE . $split[0] . self::CLOSE_BRACE;
                        }       // leave substitution point in place
                    }       // {{name}} style insertions
                    $page       .= $rawtext;
                }       // insert value of fields[$name]
                else
                    $page       .= $rawtext;
            }       // loop through all substitutions
            $this->page     = $page;
        }       // there are substitutions to process

        /****************************************************************
         *  Invoke the customization in a derived class.                *
         ****************************************************************/
        $this->displayEnd();

        /****************************************************************
         *   Return Page                                                *
         ****************************************************************/
        return($this->page);
    }       // method Template::compile

    /********************************************************************
     *  function Template::display                                      *
     *                                                                  *
     *  Output the template with all substitutions made to the PHP      *
     *  output stream.                                                  *
     ********************************************************************/
    public function display()
    {
        $this->compile();
        echo(trim($this->page));

        /****************************************************************
         *   Return Page                                                *
         ****************************************************************/
        return($this->page);
    }       // method Template::display

    /********************************************************************
     *  function Template::validate                                     *
     *                                                                  *
     *  Compare the template and the set of passed field values to      *
     *  identify any inconsistencies.                                   *
     *  Note that this can only be called after loadPage, or a method   *
     *  such as compile or parseHTML which calls loadPage.              *
     *  This is not done particularly efficiently, so it is good        *
     *  that this method is not actually called.                        *
     ********************************************************************/
    public function validate()
    {
        /****************************************************************
         *  Loop Through Markers and See what's missing                 *
         ****************************************************************/
        $missing    = "";
        foreach($this->fields as $field => $value)
        {
            if (strpos($this->page,
                          self::OPEN_BRACE . $field . self::CLOSE_BRACE) === false)
                $missing    .= '<br />' . $field;
        }
        /****************************************************************
         *   Display What's Missing                                     *
         ****************************************************************/
        if (strlen($missing) > 0)
            test('Missing Template Markers' . $missing);
    }       // method Template::validate

    /********************************************************************
     *  function Template::loadPage                                     *
     *                                                                  *
     *  This function loads the contents of the template with the       *
     *  contents of the sub-template inserted.                          *
     *  Rather than directly update $this->page, this method returns    *
     *  the template text so the caller can assign it to $this->page    *
     *  which makes it clearer that $this->page has been changed by     *
     *  the call.                                                       *
     *                                                                  *
     *  Returns:                                                        *
     *      The text of the template as a string.                       *
     ********************************************************************/
    private function loadPage()
    {
        global $warn;

        if ($this->page)
             return $this->page;        // already done

        if ($this->main != "" &&
            (substr($this->main, 0, 4) == 'http' ||
            file_exists($this->main)))
            $page   = file_get_contents($this->main);
        else
            $page   = '<p><strong>File not found name=\'' .
                          $this->main . "'</strong></p>";
        $this->phpMode  = strpos($page, self::OPEN_BRACE) === false;

        /****************************************************************
         *  Sub Page                                                    *
         *                                                              *
         *  This code substitutes the contents of the sub-pages         *
         *  directly into the template.  This is required because       *
         *  the sub-pages may themselves have substitutions.            *
         ****************************************************************/
        // handle traditional single sub-include by adding it to the array
        // of includes
        if (isset($this->sub) && isset($this->submarker))
            $this->includes[$this->submarker]   = $this->sub;

        // loop through includes
        foreach($this->includes as $submarker => $subfile)
        {           // sub-template provided
            if ($this->phpMode)
                $subpos     = strpos($page, "$" . $submarker);
            else
                $subpos     = strpos($page, self::OPEN_BRACE . $submarker . self::CLOSE_BRACE);
            if ($subpos !== false)
            {           // have a place to insert the sub-page
                $sub        = '';
                if (strlen($subfile) > 255 ||
                    strpos($subfile, self::OPEN_BRACE) !== false ||
                    strpos($subfile, '<') !== false)
                {       // too long to be key or filename
                    $sub        = $subfile;
                }       // too long to be key or filename
                else
                {       // short enough to be record key
                    if ($this->db &&
                        substr($subfile,0,1) != '/' &&
                        substr($subfile,0,1) != '.' &&
                        substr($subfile, strlen($subfile) - 5) != '.html')
                    {       // get sub-template from database record
                        $table      = $this->table;
                        $sql    = "SELECT `Content` FROM $table WHERE Stub=?";
                        $stmt       = $pdo->prepare($sql);
                        $sqlParms   = array($subfile);
                        $stmt->execute($sqlParms);
                        $row        = $stmt->fetch(PDO::FETCH_NUM);
                        if (is_array($row))
                            $sub    = $row[0];
                        else
                            $sub    =
                                "*** no record found for key '$subfile' ***";
                    }       // get template from database record
                    else
                    if ($subfile != "" && file_exists($subfile))
                        $sub        = file_get_contents($subfile);
                    else
                    {
                        error_log(__FILE__ . " " . __LINE__ .
                                    " unable to open \$subfile='$subfile' for template='" . $this->main . "'\n");
                        $sub        = "*** file '$subfile' not found ***";
                    }
                }       // short enough to be record key

                if ($this->phpMode)
                    $aftpos = $subpos + strlen($submarker) + 1;
                else
                    $aftpos = $subpos + strlen($submarker) + 4;
                // replace the insertion point with the text from the
                // sub-template
                $page       = substr($page, 0, $subpos) .
                                  $sub .  substr($page, $aftpos);
            }       // have a place to insert the sub-page
            else
            {
                $backtrace  = debug_backtrace();
                $caller     = current($backtrace);
                $warn   .= "<p>Template.inc: " . __LINE__ .
                                " unable to find submarker='$submarker' in template='" . $this->main . "' for inserting subfile='$subfile'\n" .
                                'called from ' . $caller['file'] . ':' . $caller['line'] . "\n";
            }
        }       // sub-template provided
        return $page;
    }       // function Template::loadPage

    /********************************************************************
     *  function Template::getRawTemplate                               *
     *                                                                  *
     *  Get the raw text of the template as assembled from the          *
     *  component files or database records before substitution.        *
     *  This supercedes the only legitimate reason for a user of the    *
     *  class to look at $this->page.                                   *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing the accumulated template                  *
     *                                                                  *
     *  Update:                                                         *
     *      $this->page                                                 *
     ********************************************************************/
    public function getRawTemplate()
    {
        $len                = strlen($this->page);
        if ($len == 0)
        {           // load template
            $this->page     = $this->loadPage();
            $len            = strlen($this->page);
        }           // load template

        return $this->page;
    }       // function getRawTemplate

    /********************************************************************
     *  function Template::log                                          *
     *                                                                  *
     *  Log a diagnostic message for the administrator.                 *
     *                                                                  *
     *  Input:                                                          *
     *      $message                                                    *
     ********************************************************************/
    protected function log($message)
    {
        global  $warn;
        if (substr($this->main, 0, 4) == 'http')
            return;         // do not report errors in external pages
        if (true || $this->userCanDebug)
            $warn   .= "<p>$message</p>\n";
        else
            error_log("$message\n");
    }       // protected function log

    /********************************************************************
     *  function Template::startTag                                     *
     *                                                                  *
     *  Process the start tag of an element.                            *
     *                                                                  *
     *  Input:                                                          *
     *      $tagName    string containing name of tag being opened      *
     *      $tagText    string containing opening tag                   *
     ********************************************************************/
    private function startTag($tagName,
                              $tagText)
    {
        if ($this->test)
            $this->log("Template::startTag: " . __LINE__ .
                        " '$tagName' '" . htmlspecialchars($tagText) . "' cursor  ={$this->cursor}");

        $tag                    = new TemplateTag($this,
                                                  $this->cursor,
                                                  $this->inTemplate);
        $id                     = $tag->id;
        $contents               = $tag->contents;   // model
        if ($tagName == 'template')
            $this->inTemplate   = true;

        $peek                   = count($this->tagStack) - 1;
        $topTag                 = $this->tagStack[$peek];
        if ($tagName == $topTag->tagName &&
            $tag->contents == 'default')
        {       // duplicate start tag
            $this->log("Template::startTag: " . __LINE__ .
                " &lt;$tagName&gt; encountered when a tag of the same type was open," . $this->diagPosition($tag));
            $topTag                 = array_pop($this->tagStack);
            if ($this->test)
                $this->log("Template::startTag: " . __LINE__ .
                                " close open &lt;{$topTag->tagName}&gt;");
            $topTag->etagLength     = 0;
            $topTag->tagLength      = $this->cursor - $topTag->start;
            if ($this->test)
                $this->log("Template::startTag: " . __LINE__ .
                            " push tag->start={$tag->start} ".
                htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
            array_push($this->tagStack, $tag);
        }       // duplicate start tag
        else
        if (($tag->contents == 'paragraph' &&
             $topTag->tagName == 'p') ||
            ($tag->tagName == 'li' &&
             $topTag->tagName == 'li') ||
            ($tag->tagName == 'option' &&
             $topTag->tagName == 'option') ||
            ($tag->tagName == 'tr' &&
             $topTag->tagName == 'tr') ||
            (($tag->tagName == 'dt' || $tag->tagName == 'dd') &&
             ($topTag->tagName == 'dt' || $topTag->tagName == 'dd')) ||
            (($tag->tagName == 'td' || $tag->tagName == 'th') &&
             ($topTag->tagName == 'td' || $topTag->tagName == 'th')) ||
            (($tag->tagName == 'thead' || $tag->tagName == 'tbody' || $tag->tagName == 'tfoot') &&
             ($topTag->tagName == 'thead' || $topTag->tagName == 'tbody' || $topTag->tagName == 'tfoot'))
           )
        {                   // check for tags that cannot be nested
            $topTag             = array_pop($this->tagStack);
            $topTagStart        = substr($this->page,
                                         $topTag->start,
                                         $topTag->tagLength);
            $remainder          = min(strlen($this->page) - $tag->start, 40);
            $tagStart           = substr($this->page,
                                         $tag->start,
                                         $remainder);
            $this->log("Template::startTag: " . __LINE__ .
                           ' missing closing tag for "' .
                            htmlspecialchars($topTagStart) .
                            "\" before \"" . htmlspecialchars($tagStart) .
                            "\" template={$this->main}");
            $topTag->addChild($tag);
            if ($this->test)
                $this->log("Template::startTag: " . __LINE__ .
                            " push tag->start={$tag->start} ".
                htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
            array_push($this->tagStack, $tag);
        }       // check for tags that cannot be nested
        else
        {       // add to the stack of enclosing tags
            $topTag->addChild($tag);
            // check for tag with no separate end tag
            // either because this is signaled by the XML syntax
            // or because the tag is defined as void by HTML
            $dlm                    = substr($tagText, strlen($tagText) - 2, 1);
            if ($dlm == '/' || $contents == 'void')
            {                   // tag has no contents
                $tag->etagLength    = 0;
                $tag->length        = $tag->tagLength;
            }                   // tag has no contents
            else
            {                   // push tag
                if ($this->test)
                    $this->log("Template::startTag: " . __LINE__ .
                    " push tag->start={$tag->start} " .
                    htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
                array_push($this->tagStack, $tag);
            }                   // push tag
        }       // add to the stack of enclosing tags

    }       // private function Template::startTag

    /********************************************************************
     *  function Template::diagPosition                                 *
     *                                                                  *
     *  Generate string indicating position within template.            *
     ********************************************************************/
    private function diagPosition($tag)
    {
                                    
        $position   = " at {$tag->start}, template=" . $this->main;
        foreach($this->includes as $key => $filen)
        {
            $position   .= ", includes['$key']='$filen'";
        }
        return $position;
    }       // function Template::diagPosition

    /********************************************************************
     *  function Template::closeTag                                     *
     *                                                                  *
     *  Handle a closing tag of an element                              *
     *                                                                  *
     *  Input:                                                          *
     *      $tagName    string containing name of tag being closed      *
     *      $tagText    string containing opening tag                   *
     ********************************************************************/
    private function closeTag($tagName, $tagText)
    {
        global  $warn;

        if ($this->test)
            $this->log("Template::closeTag: " . __LINE__ .
                        " '$tagName' '" . htmlspecialchars($tagText) . "' cursor={$this->cursor}");

        if ($tagName == 'template')
            $this->inTemplate       = false;

        $tag                        = array_pop($this->tagStack);
        if ($this->test)
            $this->log("Template::closeTag: " . __LINE__ .
                " pop tag->start={$tag->start} " .
                htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
        if ($tag)
        {                       // have top of stack
            if ($tagName == $tag->tagName)
            {                   // close open tag
                $tag->etagLength    = strlen($tagText);
                $tag->length        = $this->cursor + $tag->etagLength -
                                      $tag->start;
            }                   // close open tag
            else
            {                   // closing tag doesn't match
                // inform administrator
                $length         = $this->cursor - $tag->start;
                if ($length > 100)
                    $length     = 100;
                $contents       = htmlspecialchars(substr($this->page, $tag->start, $length));
                if (substr($this->main, 0, 7) != 'string ')
                {
                    $this->log("Template::closeTag: " . __LINE__ .
                                    " stray end tag " .
                                    str_replace('<','&lt',$tagText) .
                                    " doesn't match $contents" .
                                    $this->diagPosition($tag));
                }
                $peek                   = count($this->tagStack) - 1;
                if ($peek >= 0)
                {
                    $topTag                 = $this->tagStack[$peek];
                    if ($tagName == $topTag->tagName)
                    {
                        if ($this->test)
                            $this->log("Template::closeTag: " . __LINE__ .
                                    " close open &lt;{$tag->tagName}&gt;");
                        $tag                = array_pop($this->tagStack);
                    }
                    else
                    {               // does not match next tag on stack
                        for ($ii = $peek; $ii >= 0; $ii--)
                        {           // scan down the stack
                            $stack          = $this->tagStack[$ii];
                            if ($this->test)
                                $this->log("Template::closeTag: " . __LINE__ .
                                    " tagStack[$ii]=". $stack->tagName . ' ' .
                                    htmlspecialchars(substr($this->page, $stack->start, 70)) .
                                    " at {$stack->start}");
                            if ($tagName == $stack->tagName)
                            {       // found matching tag
                                for ($ij = $peek; $ij > $ii; $ij--)
                                {
                                    $closeTag       = $this->tagStack[$ij];
                                    if ($this->test)
                                        $this->log("Template::closeTag: " . __LINE__ .
                                        " close open &lt;{$closeTag->tagName}&gt;");
                                    $closeTag->etagLength   = 0;
                                    $closeTag->length       = $this->cursor - $closeTag->start;
                                    array_pop($this->tagStack);
                                }
                                if ($this->test)
                                    $this->log("Template::closeTag: " . __LINE__ .
                                    " close open &lt;{$stack->tagName}&gt;");
                                $stack->etagLength  = strlen($tagText);
                                $stack->length      = $this->cursor +
                                    $stack->etagLength - $stack->start;
                                break;
                            }       // found matching tag
                        }           // scan down the stack
                    }               // does not match next tag on stack
                }

                if ($tag->contents == 'paragraph')
                {               // top of stack is a block style tag
                    if ($this->test)
                        $this->log("Template::closeTag: " . __LINE__ .
                                " close open &lt;{$tag->tagName}&gt;");
                    $tag->etagLength        = 0;
                    $tag->length            = $this->cursor - $tag->start;
                    $tag                    = array_pop($this->tagStack);
                    if ($tagName == $tag->tagName)
                    {           // close open tag
                        $tag->etagLength    = strlen($tagName) + 3;
                        $tag->length        = $this->cursor + $tag->etagLength -
                                                $tag->start;
                    }           // close open tag
                    else
                    {           // push tag back on stack
                        if ($this->test)
                            $this->log("Template::closeTag: " . __LINE__ .
                                        " push tag->start={$tag->start} ".
                    htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
                        array_push($this->tagStack, $tag);
                    }           // push tag back on stack
                }               // top of stack is a block style tag
                else
                {               // push the tag back on the stack
                    if ($this->test)
                        $this->log("Template::closeTag: " . __LINE__ .
                                    " push tag->start={$tag->start} ".
                htmlspecialchars(substr($this->page, $tag->start, $tag->tagLength)));
                    array_push($this->tagStack, $tag);
                }               // push the tag back on the stack

            }                   // closing tag doesn't match
        }                       // have top of tagStack
        else
        {                       // tagStack is empty
        }                       // tagStack is empty
    }       // private function closeTag

    /********************************************************************
     *  function Template::parseHTML                                    *
     *                                                                  *
     *  Parse the page as HTML                                          *
     *  Updates $this->dom                                              *
     ********************************************************************/
    private function parseHTML()
    {
        global  $warn;
        global  $user;

        if ($this->test)
            $this->log("Template::parseHTML: " . __LINE__ .
                        " name={$this->main}");
        $trace                  = '';

        $this->inTemplate       = false;

        if ($this->dom->length > 0)
            return;     // already parsed

        $len                    = strlen($this->page);
        if ($len == 0)
        {                   // load template
            $this->page         = $this->loadPage();
            $len                = strlen($this->page);
        }                   // load template
        $this->cursor           = 0;    // parse cursor
        $this->dom->length      = $len;

        while($this->cursor < $len)
        {                   // scan the text of the page
            $textStart          = $this->cursor;
            // look for start of next tag
            $this->cursor       = strpos($this->page, "<", $this->cursor);
            if ($this->cursor === false || $this->cursor >= $len)
                break;      // no more tags in template
            // get the text between the end of the previous tag and
            // the start of the next tag
            $text               = substr($this->page,
                                         $textStart,
                                         $this->cursor - $textStart);
            // $this->cursor is the index of the opening '<' of the new tag
            $matches            = array();
            $regResult          = preg_match("#^<([^\s>]+)\s*([^>]*)(>?)#",
                                             substr($this->page, $this->cursor),
                                             $matches);
            // $matches[0] is the complete tag
            // $matches[1] is the tag name
            // $matches[2] is the parameters
            // $matches[3] is the closing >
            if ($regResult)
            {
                $tagText        = $matches[0];  // whole tag
                if ($this->test)
                    print "<p>Template::parseHtml: " . __LINE__ .
                                    " tag cursor=$this->cursor " .
                                    str_replace('<','&lt',$tagText) . "</p>\n";
                $end            = $this->cursor + strlen($tagText);
                $tagName        = strtolower($matches[1]);
                $rest           = $matches[2];  // contents of tag
                $firstInTag     = substr($tagName,0,1);

                // identify tag name
                if ($firstInTag == '/')
                     $this->closeTag(substr($tagName, 1), $tagText);
                else
                if ($firstInTag == '!')
                {                   // comment tag
                    if (substr($tagText,0,4) == "<!--")
                    {               // comment
                        // find end of comment
                        $end            = strpos($this->page, "-->", $this->cursor + 4);
                        $comment        = substr($this->page, $this->cursor, $end - $this->cursor + 3);

                        if ($end)
                            $end        += 3;   // point after delimiter
                        else
                            $end        = strlen($this->page);
                    }               // comment
                }                   // comment tag
                else
                     $this->startTag($tagName, $tagText);
                $this->cursor   = $end; // continue
            }
            else
            {                       // parse of tag failed
                // for example there is a space after the opening <
                $this->log("Template.inc:parseHTML: " . __LINE__ . " preg_match(\"#^<([^\s>]+)\s*([^>]*)(>?)#\", \"" .
                        htmlspecialchars(substr($this->page, $this->cursor, 100)) .
                        "...\") failed");
                $this->cursor++;    // fix infinite loop
            }                       // parse of tag failed
        }                           // scan the text of the page

    }       // method Template::parseHTML

    /********************************************************************
     *  private function Template::findInsertions                       *
     *                                                                  *
     *  Find all of the insertion points in the template.               *
     *  Called from doTagUpdates for tag specific update                *
     *                                                                  *
     *  Input:                                                          *
     *      $tag        instance of TemplateTag containing              *
     *              an XML/HTML template.  If omitted                   *
     *              acts on entire template                             *
     *                                                                  *
     *  Returns:                                                        *
     *      array, each entry of which consists of:                     *
     *        [0] the offset within the template of the start of        *
     *            the insertion point.                                  *
     *        [1] the length of the insertion point                     *
     *        [2] the insertion point name                              *
     *        [3] the containing instance of TemplateTag                *
     *                                                                  *
     *  Examples:                                                       *
     *      "something $name something else "                           *
     *      The array entry contains:                                   *
     *          array(the offset of the $, 5, 'name')                   *
     *      "something ${name} something else "                         *
     *      The array entry contains:                                   *
     *          array(the offset of the $, 7, 'name')                   *
     *      "something {$name} something else "                         *
     *      The array entry contains:                                   *
     *          array(the offset of the {, 7, 'name')                   *
     *      "something {{name}} something else "                        *
     *      The array entry contains:                                   *
     *          array(the offset of the {{, 8, 'name')                  *
     ********************************************************************/
    private function findInsertions($tag = null)
    {
        global $warn;
        global $debug;

        if (is_null($tag))
        {
            $tag                    = $this->dom;
            $template               = $this->page;
            if (is_null($template))
                throw new Exception("Template::findInsertions: " .
                                    "called before template loaded");
        }
        else
        {
            $template               = $tag->outerHTML();
        }
        $insertions                 = array();

        if ($this->phpMode)
        {                       // extract $name style substitutions
            $start                  = 0;
            $this->cursor                   = 0;
            $name                   = '';
            $prevChar               = '';
            $LBRACE                 = '{';  // confuses editors if literal
            $RBRACE                 = '}';  // confuses editors if literal
            while(($this->cursor = strpos($template, '$', $this->cursor)) !== false)
            {                   // loop through template
                if ($this->cursor > 0)
                    $prevChar       = substr($template, $this->cursor - 1, 1);
                if ($prevChar == '\\')
                {               // escaped dollar sign
                    $this->cursor++;    // advance over literal dollar sign
                }               // escaped dollar sign
                else
                if ($prevChar == $LBRACE)
                {               // complex syntax insertion
                    // insertion point starts at the left brace
                    $start          = $this->cursor - 1;
                    $this->cursor++;    // advance over $ sign
                    // insertion is terminated only by a close brace
                    $end            = strpos($template,
                                                    $RBRACE,
                                                    $this->cursor);
                    if ($end === false)
                    {           // close brace not found before end of template
                        $end        = strlen($template);
                        $name       = substr($template,
                                             $this->cursor,
                                             $end - $this->cursor);
                        $this->cursor       = $end;
                    }           // close brace not found before end of template
                    else
                    {           // found close brace
                        $name       = substr($template,
                                             $this->cursor,
                                             $end - $this->cursor);
                        $this->cursor       = $end + 1; // advance over rbrace
                    }           // found close brace
                    $insertions[]   = array($start,
                                            $this->cursor - $start,
                                            $name,
                                            $tag);

                }               // complex syntax insertion
                else
                {               // basic syntax
                    $start          = $this->cursor;
                    $this->cursor++;    // advance over the $
                    if (substr($template, $this->cursor, 1) == $LBRACE)
                    {           // name enclosed in braces
                        $this->cursor++;// advance over left brace
                        $end        = strpos($template, $RBRACE,$this->cursor);
                        if ($end === false)
                        {       // end of template
                            $end    = strlen($template);
                        }       // end of template
                        $name       = substr($template,
                                                    $this->cursor,
                                                    $end - $this->cursor);
                        $this->cursor       = $end + 1; // after right brace
                        $insertions[]   = array($start,
                                                $this->cursor - $start,
                                                $name,
                                                $tag);
                    }           // variable name enclosed in braces
                    else
                    {           // simple variable name
                        $matches            = array();
                        if (preg_match(self::NAME_PATT,
                                       substr($template, $this->cursor),
                                       $matches))
                        {       // have a valid variable name
                            $name           = $matches[0];
                            $this->cursor       += strlen($name);
                            $insertions[]   = array($start,
                                                    $this->cursor - $start,
                                                    $name,
                                                    $tag);
                        }       // have a valid variable name
                        else
                        {       // a dollar sign as ordinary text
                            // for example $15.25 will come here
                        }       // a dollar sign as ordinary text
                    }           // not enclosed in braces
                }               // basic syntax
            }                   // loop through template
        }                       // extract $name style substitutions
        else
        {                       // extract {{name}} style substitutions
            $start                  = 0;
            $this->cursor                   = 0;
            $name                   = '';
            while(($this->cursor = strpos($template, self::OPEN_BRACE , $this->cursor)) !== false)
            {                   // loop through template
                $start              = $this->cursor;
                $this->cursor               = strpos($template, self::CLOSE_BRACE, $this->cursor);
                if ($this->cursor === false)
                {               // not found before end of template
                    $this->cursor           = strlen($template);
                    $name           = substr($template,
                                             $start + 2,
                                             $this->cursor - $start - 2);
                    $insertions[]   = array($start,
                                            $this->cursor - $start,
                                            $name,
                                            $tag);
                    break;
                }               // not found before end of template
                else
                {               // found delimiter of insertion point
                    $name           = substr($template,
                                             $start + 2,
                                             $this->cursor - $start - 2);
                    $this->cursor           += 2;
                    $insertions[]   = array($start,
                                            $this->cursor - $start,
                                            $name,
                                            $tag);
                }               // found delimiter of insertion point
            }                   // loop through template
        }                       // extract {{name}} style substitutions

        // identify the sub-tag of the template containing each insertion point
        if ($tag != null && $tag != $this->dom)
        {                       // only for tag specific updates
            $tag->updateInsertions($insertions);
        }                       // only for tag specific updates

        return $insertions;
    }       // function findInsertions

    /********************************************************************
     *  function Template::getDocument                                  *
     *                                                                  *
     *  Get a reference to the top of the Document Object Model         *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of TemplateTag                                     *
     ********************************************************************/
    public function getDocument()
    {
        $this->parseHTML();
        return $this->dom;
    }       // function Template::getDocument
    public function isParsed()
    {
        return $this->dom->length > 0;
    }       // function Template::getDocument


    /********************************************************************
     *  function Template::getElementById                               *
     *                                                                  *
     *  Get an instance of TemplateTag by id value                      *
     *  You can also use the shorthand $template[$identifier]           *
     *                                                                  *
     *  Input:                                                          *
     *      $identifier value of id field of requested element          *
     *                                                                  *
     *  Returns:                                                        *
     *      instance of TemplateTag or null                             *
     ********************************************************************/
    public function getElementById($identifier)
    {
        $this->parseHTML(); // ensure the DOM is built

        if (array_key_exists($identifier, $this->dom->elements))
            return $this->dom->elements[$identifier];
        else
            return null;
    }       // Template::getElementById

    /********************************************************************
     *  function Template::getElementsByTagName                         *
     *                                                                  *
     *  Search the DOM for all tags with a specified tag name.          *
     *                                                                  *
     *  Input:                                                          *
     *      $tagName    case insensitive string containing tag name     *
     *                                                                  *
     *  Returns:                                                        *
     *      array of instances of TemplateTag or empty array            *
     ********************************************************************/
    public function getElementsByTagName($tagName)
    {
        global $warn;
        $this->parseHTML(); // ensure the DOM is built

        return $this->dom->getElementsByTagName($tagName);
    }       // Template::getElementsByTagName

    /********************************************************************
     *  function Template::switchCase                                   *
     *                                                                  *
     *  This method implements behavior like a switch command by        *
     *  hiding all tags within a set except the one selected by the     *
     *  value.                                                          *
     *                                                                  *
     *  Input:                                                          *
     *      $prefix string prefix of the id of all cases                *
     *      $value  string postfix of the case to be displayed          *
     *                                                                  *
     *  Example:                                                        *
     *    <span id='switch'>                                            *
     *      <span data-case='1'>text to display for value 1</span>      *
     *      <span data-case='2'>text to display for value 2</span>      *
     *      <span data-case='3'>text to display for value 3</span>      *
     *      <span data-case='4'>text to display for value 4</span>      *
     *    </span> <!-- id='switch' -->                                  *
     *                                                                  *
     *      $template->switchCase('case',3); displays only case 3       *
     ********************************************************************/
    public function switchCase($id, $value)
    {
        $switch     = $this->getElementById($id);
        $children   = $switch->childNodes();
        foreach($children as $case)
        {       // loop through cases
            if (!isset($case->attributes['data-case']) ||
                $case->attributes['data-case'] != $value)
                $case->update(null);    // hide all non matches
        }       // loop through cases
    }       // function switchCase

    /********************************************************************
     *  function Template::ifThenElse                                   *
     *                                                                  *
     *  This method implements behavior like an if command by           *
     *  hiding one of a pair of tags.                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $value  boolean expression                                  *
     *      $thenid id of the tag to display if the value is true       *
     *      $elseid id of the tag to display if the value is false      *
     *                                                                  *
     *  Example:                                                        *
     *  <span id='istrue'>HTML to display if expression is true</span>  *
     *  <span id='isfalse'>HTML to display if expression is false</span>*
     *                                                                  *
     *      $template->ifThenElse($boolean, 'istrue', 'isfalse');       *
     ********************************************************************/
    public function ifThenElse($value, $thenid, $elseid)
    {
        $this->parseHTML();

        if ($value)
            $this->updateTag($elseid, null);    // hide else
        else
            $this->updateTag($thenid, null);    // hide then
    }       // function ifThenElse

    /********************************************************************
     *  private function Template::subTag                               *
     *                                                                  *
     *  Substitute text in place of a {{marker}}                        *
     *                                                                  *
     *  Parameters:                                                     *
     *      $result     the accumulated text of the template            *
     *              output as a linked list of strings                  *
     *      $name       insertion point name                            *
     *      $text       the text to append to the result                *
     *      $tag        tag containing the insertion point              *
     *                                                                  *
     *  Returns:                                                        *
     *      updated value of accumulated text of the output             *
     ********************************************************************/
    private function subTag(&$result,
                            $name,
                            $text,
                            $tag)
    {
        global  $warn;

        if (is_null($text) ||
            (is_array($text) && count($text) == 0) ||
            ($text instanceof RecordSet && $text->count() == 0))
        {           // emit zero instances
            do {        // delete back to beginning of current tag
                $last       = $result->pop();
                $pos        = strrpos($last, '<', -1);
            } while ($pos === false);
            $result->push(substr($last, 0, $pos));
            return;
        }           // emit zero instances
        else
        if (is_array($text) || $text instanceof RecordSet)
        {           // array substitution
            do {        // delete back to beginning of current tag
                $last       = $result->pop();
                $pos        = strrpos($last, '<', -1);
            } while ($pos === false);
            $result->push(substr($last, 0, $pos));
            $insertions         = $this->findInsertions($tag);
            $template           = $tag->outerHTML();
            foreach($text as $record)
            {           // loop through array/RecordSet
                $end            = 0;    // end of the previous insertion
                foreach ($insertions as $index => $insertion)
                {       // loop through template
                    $start      = $insertion[0];

                    // first include text prior to next insertion
                    $result->push(substr($template, $end, $start - $end));

                    // locate the insertion point to be replaced
                    $length     = $insertion[1];
                    $end        = $start + $length;
                    $field      = $insertion[2];
                    $this->insertions++;
                    if ($field == $name)
                    {
                        if ($record instanceof Record)
                            $result->push($record->getId());
                        else
                            $result->push($index);
                    }
                    else
                    if ($record instanceof Record)
                    {
                        if ($record->offsetExists($field))
                            $result->push($record[$field]);
                    }
                    else
                    if (is_array($record))
                    {
                        if (array_key_exists($field, $record))
                            $result->push($record[$field]);
                    }
                }       // loop through template

                // add text from template after last insertion point
                $result->push(substr($template, $end));
            }           // loop through array/RecordSet
            $text           = '';   // done
        }       // array substitution
        else
        if (!is_string($text))
        {
            $text   = strval($text);
        }

        // the following is an attempt to detect the situation
        // where the value is being inserted into a string in
        // which case quotes in the value should be escaped
        if (strpos($text, '<') === false)
        {       // value is not HTML
            if (preg_match("/'[^']*$/", $result->top()))
            {       // unclosed single quoted string constant
                $text   = str_replace("'", '&#039;', $text);
            }       // unclosed single quoted string constant
            else
                $text   = str_replace('"', '&quot;', $text);
        }       // value is not HTML
        $this->insertions++;
        $result->push($text);
    }       // function subTag

    /********************************************************************
     *  function Template::substitute                                   *
     *                                                                  *
     *  Expand a piece of text by replacing all substitution points     *
     *  with the values from the associative array.                     *
     *                                                                  *
     *  Parameters:                                                     *
     *      $subTemplate    string containing portion of page           *
     *      $array      associative array or Record                     *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing expansion of template                     *
     ********************************************************************/
    protected function substitute($subTemplate,
                                  $array)
    {
        global  $warn;

        if ($this->debug)
        {
            if (strlen($subTemplate) > 100)
                $tSubTemplate   = substr($subTemplate, 0, 80) . '...' .
                                  substr($subTemplate, -17);
            else
                $tSubTemplate   = $subTemplate;
            $warn   .= "<p>Template::substitute: " . __LINE__ .
                            ' parms=(subTemplate=\'' .
                            htmlspecialchars($tSubTemplate) . "',<br>" .
                            "array count=" . count($array).',';
            foreach($array as $index => $value)
            {
                $warn   .= $index . ' => ' . $value . ',';
            }
            $warn   .= "</p>\n";
        }
        if (is_null($array) || count($array) == 0)
            return '';      // remove sub-template from output

        $pieces         = $this->breakTemplate($subTemplate);
        if (count($pieces) == 1 &&
                strlen($pieces[0][0]) == 0)// no substitutions to make
            return $subTemplate;

        $result         = '';
        foreach($pieces as $piece)
        {           // loop through pieces of template
            $field      = $piece[0];
            $remainder      = $piece[1];
            $text       = '';
            $idpos      = strrpos($result, ' id=', -1);
            if (is_int($idpos))
            {       // delete id attribute
                $quote  = substr($result, $idpos + 4, 1);
                $result = substr($result, 0, $idpos + 1);
                $epos   = strpos($remainder, $quote);
                if (is_int($epos))
                    $remainder  = substr($remainder, $epos + 1);
                $result .= $remainder;
                $remainder  = '';
            }       // delete id attribute
            if ($array instanceof Record)
            {
                if ($array->offsetExists($field))
                    $text   = $array->get($field);
                else
                if (strlen($field) > 0)
                {
                    if ($this->debug)
                        $warn   .= "<p>Template:: " . __LINE__ . " undefined field '$field' remainder=" . htmlspecialchars($remainder) . " ";
                }
            }
            else
            if (array_key_exists($field, $array))
            {
                $text   = $array[$field];
            }
            else
            if (strlen($field) > 0)
            {
                if ($this->debug)
                    $warn   .= "<p>Template:: " . __LINE__ .
                                  " undefined field '$field' remainder='" .
                                  htmlspecialchars($remainder) . "'</p>\n";
            }
            $this->insertions++;
            $result     .= $text . $remainder;
        }           // loop through pieces of template
        if ($this->debug)
            $warn   .= "<p>Template::substitute: " . __LINE__ .
                           " return '" . htmlspecialchars($result) . "</p>\n";
        return $result;
    }       // function substitute

    /********************************************************************
     *  function Template::doTagUpdates                                 *
     *                                                                  *
     *  Perform updates of individual tag elements in the template      *
     ********************************************************************/
    private function doTagUpdates()
    {
        global  $debug;
        global  $warn;

        $startTime  = microtime(true);
        $this->insertions   = 0;
        if ($this->debug)
        {
            $warn   .= "<p>Template::doTagUpdates: " . __LINE__ .
                           " count=" .  count($this->tagUpdates) . "</p>\n";
            ob_start();
            debug_print_backtrace();
            $warn   .= '<p>' . htmlspecialchars(ob_get_clean()) . "</p>\n";
        }

        if (count($this->tagUpdates) == 0)
        {
            return;     // no updates to apply
        }

        $this->parseHTML(); // ensure DOM is built

        if ($this->phpMode)
        {
            $insStart       = '$';
            $insEnd         = '';
        }
        else
        {
            $insStart       = self::OPEN_BRACE;
            $insEnd         = self::CLOSE_BRACE;
        }

        // sort insertions in descending order from end of template to front
        $tagUpdates = array();
        foreach($this->tagUpdates as $tagUpdate)
        {
            $tag                = $tagUpdate->tag;
            $tagUpdate->start   = $tag->start;
            $tagUpdate->end     = $tag->start + $tag->length;
            for($offset = count($tagUpdates); $offset >= 1; --$offset)
            {
                if ($tagUpdates[$offset - 1]->start > $tagUpdate->start)
                    break;
            }
            array_splice($tagUpdates, $offset, 0, 0);
            $tagUpdates[$offset]    = $tagUpdate;
        }

        // now process the tag updates from back to front
        foreach($tagUpdates as $offset => $tagUpdate)
        {
            $tag                = $tagUpdate->tag;
            $id                 = $tag->id;
            $start              = $tag->start;
            $end                = $tag->start + $tag->length;
            $length             = $tag->length;
            $template           = $tag->outerHTML();
            $replace            = $tagUpdate->array;
            $before             = substr($this->page, 0, $start);
            $after              = substr($this->page, $end);
            if (is_array($replace) && count($replace) == 0)
                $replace         = '';

            if (is_string($replace))
            {                   // complete replacement string
                $this->page     = $before . $replace .  $after;
                // adjust length of ancestor tags
                $parent         = $tag->parent;
                while ($parent)
                {
                    $parent->length += strlen($replace) - $length;
                    $parent         = $parent->parent;
                }
                // remove elements from DOM
                $rtemplate      = new Template($template, true);
                $rtemplate->parseHTML();
                foreach($rtemplate->dom->elements as $rid => $tag)
                {
                    if (array_key_exists($rid, $this->dom->elements))
                        $this->dom->elements[$rid]      = null;
                }
                continue;
            }                   // complete replacement string

            // locate all insertion points within this tag
            $insertions         = $this->findInsertions($tag);

            // generate debug trace output
            if ($this->debug)
            {
                if (strlen($template) > 100)
                    $tagText    = substr($template, 0, 70) . '...' .
                                  substr($template, -27);
                else
                    $tagText    = $template;
                $warn   .= "<p>Template::doTagUpdates: " . __LINE__ .
                               " id='"      . htmlspecialchars($id) .
                               "' start="   . $start .
                               " end="      . $end .
                               " length="   . $length .
                               " tag='"     . htmlspecialchars($tagText) .
                               "', array=";
                if (is_array($replace))
                {
                    $warn           .= '[';
                    $comma          = '';
                    foreach($replace as $index => $element)
                    {
                        $warn       .= $comma;
                        $comma      = ', ';
                        if ($element instanceof Record)
                            $warn   .= $index . ' => Record ' .
                                           get_class($element);
                        else
                        if (is_array($element))
                            $warn   .= $index . ' => ' . print_r($element,true);
                        else
                        if (is_string($element))
                            $warn   .= $index . " => '" .
                                           htmlspecialchars($element) . "'";
                        else
                            $warn   .= $index . ' => ' .
                                           print_r($element, true);
                    }
                    $warn   .= ']';
                }
                else
                if ($replace instanceof RecordSet)
                    $warn   .= "class " . get_class($replace) .
                                   " count=" . $replace->count();
                else
                    $warn   .= print_r($replace, true);
                $warn   .= "</p>\n";
            }

            // include white space after the tag up to and
            // including the next new line
            for(;$end < strlen($this->page); ++$end)
            {
                $char       = substr($this->page, $end, 1);
                if ($char == "\n" || $char == "\r")
                {       // include new line
                    ++$end;
                    break;
                }       // include new line
                if (!ctype_space($char))
                    break;  // not a space character
            }       // search for end of template text

            // have identified entire template
            $before         = substr($this->page, 0, $start);
            $after          = substr($this->page, $end);
            $origLength     = $end - $start;
            $template       = substr($this->page, $start, $origLength);

            if ($this->debug)
            {
                $tbefore    = $before;
                if (strlen($tbefore) > 100)
                    $tbefore    = substr($tbefore, 0, 17) . '...' .
                                      substr($tbefore, -80);
                $tafter     = $after;
                if (strlen($tafter) > 100)
                    $tafter = substr($tafter, 0, 80) . '...' .
                                      substr($tafter, -17);
                $warn   .= "<p>Template::doTagUpdates: " . __LINE__ .
                    " \$start=$start, \$end=$end, \$origLength=$origLength</p>\n";
                $line   = __LINE__;
                $warn   .= "<p>Template::doTagUpdates: $line \$before='" .
                                   htmlspecialchars($tbefore) .  "'</p>\n";
                $warn   .= "<p>Template::doTagUpdates: $line \$template='" .
                                   htmlspecialchars($template) .  "'</p>\n";
                $warn   .= "<p>Template::doTagUpdates: $line \$after='" .
                                   htmlspecialchars($tafter) .  "'</p>\n";
            }           // debug

            // if the id attribute of the template tag does not contain
            // a substitution point, and the tag is to be repeated more than
            // once, then set up to avoid emitting the attribute as part
            // of the text generated for the template tag
            // because otherwise it would not be unique in the generated
            // document
            if ($this->phpMode)
                $subPos     = strpos($tag->id, '$');
            else
                $subPos     = strpos($tag->id, self::OPEN_BRACE);
            $startId        = false;

            if (count($replace) > 1 && $subPos === false)
            {       // remove id attribute from template
                $startId        = strpos($template, "id=");
                if ($startId !== false)
                {       // id= is present
                    if (preg_match("/id=(\w+|'[^']*'|\"[^\"]*\")/",
                                   substr($template, $startId),
                                   $matches))
                    {
                        $endId      = $startId + strlen($matches[0]);
                    }
                    else
                    {
                        $startId    = false;
                        $warn   .= "<p>doTagUpdates: " . __LINE__ .
                                   " failed for " . htmlspecialchars(substr($template, $startId, 64)) . "</p>\n";
                    }
                }       // id= is present
            }           // remove id attribute from template

            if ($this->debug)
                $warn   .= "<p>Template::doTagUpdates: " . __LINE__ .
                               " \$template='" .
                                   htmlspecialchars($template) . "'</p>\n";

            // expand template for each record in array
            $result             = new SplDoublyLinkedList;
            $this->inQuote      = false;
            $this->lastDelete   = 0;
            $this->tag          = '';
            foreach($replace as $record)
            {           // loop through records
                if ($this->debug)
                {
                    $warn   .= "<p>Template::doTagUpdates: " . __LINE__ .
                                   " \$record=";
                    if ($record instanceof Record)
                    {
                        $warn       .= get_class($record) . ' [';
                        $comma      = '';
                        foreach($record as $fname => $value)
                        {
                            $warn   .= "$comma$fname='$value'";
                            $comma  = ',';
                        }
                        $warn       .= ']';
                    }
                    else
                    if (is_array($record))
                    {
                        $warn       .= "array [";
                        $comma      = '';
                        foreach($record as $fname => $value)
                        {
                            $warn   .= "$comma$fname='$value'";
                            $comma  = ',';
                        }
                        $warn       .= ']';
                    }
                    else
                    if (is_string($record))
                        $warn   .= "'" .
                               htmlspecialchars($record) . "'";
                    else
                        $warn   .= print_r($record, true);
                    $warn       .= " number of insertions=" . count($insertions) . "</p>\n";
                }

                $end        = 0;    // end of the previous insertion
                foreach ($insertions as $insertion)
                {       // loop through template
                    // insertion point to be replaced
                    $start          = $insertion[0];
                    $length         = $insertion[1];
                    $field          = $insertion[2];
                    $tag            = $insertion[3];

                    if ($start < $end)
                        continue;   // already handled

                    // first include text prior to next insertion
                    // removing the id= attribute if required
                    if ($startId !== false &&
                            $startId >= $end && $startId < $start)
                    {           // delete id attribute
                        $preText = substr($template, $end, $startId - $end) .
                                   substr($template, $endId,$start - $endId);
                    }           // delete id attribute
                    else
                    {
                        $preText = substr($template, $end, $start - $end);
                    }
                    $result->push($preText);

                    $tresult        = '';
                    foreach($result as $piece)
                        $tresult    .= $piece;
                    $olength        = strlen($tresult);
                    if ($olength > 120)
                        $tresult    = substr($tresult, 0, 47) .
                                          '...' .
                                          substr($tresult, -70);
                    $end            = $start + $length;
                    $remainder      = substr($template, $end);
                    $set            = false;
                    if (($record instanceof Record &&
                         $record->offsetExists($field)) ||
                        (is_array($record) &&
                         array_key_exists($field, $record)))
                    {   // get substitution value from array or Record
                        $value      = $record[$field];
                        $set        = true;
                    }
                    else
                    if (array_key_exists($field, $this->fields))
                    {
                        $value      = $this->fields[$field];
                        $set        = true;
                    }

                    // if the field name is found then apply the
                    // value. Note that the value may be explicitly null
                    if($set)
                    {               // value obtained
                        // the following call updates $result to include
                        // the $value
                        $this->subTag($result,
                                      $field,
                                      $value,
                                      $tag);
                        $newLength  = 0;
                        foreach($result as $piece)
                            if (is_string($piece))
                                $newLength  += strlen($piece);
                        if ($newLength < $olength ||
                            is_array($value) || $value instanceof RecordSet)
                        {           // delete remainder of current tag
                            // remove up to end of closing tag
                            $epos   = strpos($remainder, '</');
                            $epos2  = strpos($remainder, '>', $epos);
                            $end    = $end + $epos2 + 1;
                        }           // delete remainder of current tag
                        else
                        if ($newLength == $olength &&
                            $tag->substitutions == 1 &&
                            $tag->contents != 'void')
                        {
                            do {    // delete back to beginning tag
                                $last       = $result->pop();
                                $pos        = strrpos($last, '<', -1);
                            } while ($pos === false);
                            $result->push(substr($last, 0, $pos));

                            // remove up to end of closing tag
                            $epos   = strpos($remainder, '</');
                            $epos2  = strpos($remainder, '>', $epos);
                            $end    = $end + $epos2 + 1;
                        }
                    }               // value obtained
                    else
                    {               // field not present in record
                        if (strlen($field) > 0 && $this->debug)
                        {
                            $warn   .= "<p>Template::doTagUpdates: " .
                                       __LINE__ .
                                       " Could not get value for '" .
                                       $field . "' from record.";
                            foreach($record as $nam => $val)
                                $warn   .= "<br>$nam=$val";
                            $warn   .= "</p>\n";
                        }
                    }               // field not present in record

                    $quoteCount = preg_match_all('/[\'"]/', $remainder);
                    if ($quoteCount & 1)
                    {           // odd number of quote characters
                        $this->inQuote  = !$this->inQuote;  // flip
                    }           // odd number of quote characters
                }           // loop through template
                $result->push(substr($template, $end));
            }               // loop through records

            $newLength      = 0;
            $resultStr      = '';
            foreach($result as $piece)
            {
                $newLength  += strlen($piece);
                $resultStr  .= $piece;
            }
            $lengthChange   = $origLength - $newLength;
            $tempTag        = null;
            for ($i = $offset + 1; $i < count($tagUpdates); $i++)
            {           // check for overlapping tags
                $otherTag   = $tagUpdates[$i];
                if ($otherTag->end >= $tagUpdate->end)
                {
                    $otherTag->end  -= $lengthChange;
                    $tempTag        = $otherTag;
                }
            }           // check for overlapping tags
            $this->page     = $before . $resultStr .  $after;
        }           // loop through array insertions
        $runTime    = microtime(true) - $startTime;
        if ($runTime > 1.0)
        {               // ran for an unexpectedly long time
        if ($this->insertions > 0)
            $warn   .= "<p>Template did $this->insertions insertions in " .
            number_format($runTime,6) . " seconds, at an average rate of " .
            number_format($runTime/$this->insertions,6) .
            " seconds per insertion</p>\n";
        else
            $warn   .= "<p>Template did 0 insertions in " .
                        number_format($runTime,6) . " seconds</p>\n";
        }               // ran for an unexpectedly long time
    }       // method Template::doTagUpdates

    /********************************************************************
     *  function Template::print                                        *
     *                                                                  *
     *  Diagnostic print-out of object                                  *
     ********************************************************************/
    function print()
    {
        print "<h2>Instance of Class Template</h2>\n";
        /********************************************************************
        *    State Variables                                                *
        ********************************************************************/
        if ($this->clear)
            print "<p class=\"indent\">template::clear=true</p>\n";
        else
            print "<p class=\"indent\">template::clear=false</p>\n";
        print "<p class=\"indent\">template::main=" . $this->main . "</p>\n";
        if ($this->mainValid)
            print "<p class=\"indent\">template::mainValid=true</p>\n";
        else
            print "<p class=\"indent\">template::mainValid=false</p>\n";
        if (is_null($this->sub))
            print "<p class=\"indent\">template::sub=null</p>\n";
        else
            print "<p class=\"indent\">template::sub="  . $this->sub . "</p>\n";
        print "<p class=\"indent\">template::submarker="    . $this->submarker . "</p>\n";
        print "<p class=\"indent\">template::includes=" . print_r($this->includes,true) . "</p>\n";
        $page       = $this->page;
        if (strlen($page) > 200)
            $page       = substr($page, 0, 100) . '...' . substr($page, -97);
        print "<p class=\"indent\">template::page='"    . htmlspecialchars($page) . "'</p>\n";
        if($this->db)
            print "<p class=\"indent\">template::db=true</p>\n";
        else
            print "<p class=\"indent\">template::db=false</p>\n";
        print "<p class=\"indent\">template::dom=" . "</p>\n";
        $this->dom->printTag(12);
        if($this->phpMode)
            print "<p class=\"indent\">template::phpMode=true</p>\n";
        else
            print "<p class=\"indent\">template::phpMode=false</p>\n";
        print "<p class=\"indent\">template::tagUpdates="   . print_r($this->tagUpdates,true) . "</p>\n";
        if($this->debug)
            print "<p class=\"indent\">template::debug=true</p>\n";
        else
            print "<p class=\"indent\">template::debug=false</p>\n";
        if (is_null($this->tag))
            print "<p class=\"indent\">template::tag=null</p>\n";
        else
            print "<p class=\"indent\">template::tag="  . $this->tag . "</p>\n";
        if (is_null($this->subTemplate))
            print "<p class=\"indent\">template::subTemplate=null</p>\n";
        else
            print "<p class=\"indent\">template::subTemplate="  . $this->subTemplate . "</p>\n";
        if (is_null($this->quote))
            print "<p class=\"indent\">template::quote=null</p>\n";
        else
            print "<p class=\"indent\">template::quote="    . $this->quote . "</p>\n";
        if (is_null($this->substitution))
            print "<p class=\"indent\">template::substitution=null</p>\n";
        else
            print "<p class=\"indent\">template::substitution=" . $this->substitution . "</p>\n";
        if ($this->inQuote)
            print "<p class=\"indent\">template::inQuote=true</p>\n";
        else
            print "<p class=\"indent\">template::inQuote=false</p>\n";
        print "<p class=\"indent\">template::lastDelete="   . $this->lastDelete . "</p>\n";
        print "<p class=\"indent\">template::insertions="   . $this->insertions . "</p>\n";
        foreach($this->fields as $name => $field)
            print "<p class=\"indent\">template::fields['$name']='" . htmlspecialchars($field) . "'</p>\n";
    }       // function print

    function printFields()
    {
        foreach($this->fields as $name => $field)
            print "<p class=\"indent\">template::fields['$name']='" . htmlspecialchars($field) . "'</p>\n";
    }       // function print

    /********************************************************************
     *  function Template::offsetSet                                    *
     *                                                                  *
     *  Part of the implementation of the interface ArrayAccess.        *
     *  Updating the set of identified tags is not supported.           *
     *                                                                  *
     *  Parameters:                                                     *
     *      $id         tag id value.                                   *
     *      $value      new value                                       *
     *                                                                  *
     ********************************************************************/
    public function offsetSet($id, $value)
    {
        throw new Exception("Template::offsetSet: " .
                                "cannot alter tag table");
        if (is_string($id) && strlen($id) > 0)
        {
            if ($value instanceof TemplateTag)
                $this->dom->elements[$id]   = $value;
            else
                throw new Exception("Template::offsetSet: " .
        "cannot add something other than an instance of class TemplateTag");
        }
        else
            throw new Exception("Template::offsetSet: " .
                                "id must be a non-empty string");
    }       // public function Template::offsetSet

    /********************************************************************
     *  function Template::offsetExists                                 *
     *                                                                  *
     *  Part of the implementation of the interface ArrayAccess.        *
     *      isset($template[$id]);                                      *
     *                                                                  *
     *  Parameters:                                                     *
     *      $id     id value of tag.                                    *
     ********************************************************************/
    public function offsetExists($id)
    {
        return (array_key_exists($id, $this->dom->elements));
    }       // public function Template::offsetExists($id)

    /********************************************************************
     *  function Template::offsetUnset                                  *
     *                                                                  *
     *  Part of the implementation of the interface ArrayAccess.        *
     *  It is not supported for external users to delete a tag          *
     *                                                                  *
     *  Parameters:                                                     *
     *      $id     id value of tag.                                    *
     ********************************************************************/
    public function offsetUnset($id)
    {
        throw new Exception("Template::offsetUnset: " .
                                "cannot alter tag table");
        if (array_key_exists($id, $this->dom->elements))
            unset($this->dom->elements[$id]);
    }       // private function Template::offsetUnset($id)

    /********************************************************************
     *  function Template::offsetGet                                    *
     *                                                                  *
     *  Part of the implementation of the interface ArrayAccess.        *
     *  Returns the instance of Tag Element associated with an id.      *
     *      $value      = $template[$id];                               *
     *  This is the same as calling $this->getElementById($id).         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $id         tag id value.                                   *
     ********************************************************************/
    public function offsetGet($id)
    {
        $this->parseHTML(); // ensure the DOM is built

        if (array_key_exists($id, $this->dom->elements))
            return $this->dom->elements[$id];
        else
            return null;
    }       // public function Template::offsetGet($id)

}       // class Template
