<?php
namespace Templating;
use \Exception;
use \ArrayAccess;
use \Iterator;
use \Genealogy\Record;
use \Genealogy\RecordSet;

/************************************************************************
 *  TemplateTag.inc						                            	*
 *										                                *
 *  History:									                        *
 *      2019/04/07  split out from Template.inc                         *
 *      2019/08/02  provide access to outerHTML, innerHTML, and         *
 *                  childNodes as attributes                            *
 *                  support assigning values to outerHTML and innerHTML *
 *	    									                            *
 *  Copyright &copy; 2019 James A. Cobban All Rights Reserved			*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc' ;

/************************************************************************
 *  class TemplateTag											        *
 *													                    *
 *  Encapsulate information maintained about HTML/XML tags recognized	*
 *  in the template text as a DOM.									    *
 ************************************************************************/
class TemplateTag implements Iterator, ArrayAccess
{
    public	$template;  	// the containing template
    public	$tagName;	    // the tag name
    public	$id;		    // id attribute value
    public	$children;	    // array of immediate children of this tag
    public	$parent;	    // parent tag
    public	$attributes;	// associative array of attributes
    public	$start;		    // starting offset in Template::$page
    public	$length;	    // length of total contents of tag
    public	$tagLength;	    // length of opening tag
    public	$etagLength;	// length of closing tag
    public	$contents;	    // content constraints model
    public	$substitutions;	// number of substitution points
    public	$elements;	    // table to find instances of TemplateTag
                            // by id= attribute, only in document tag
    private $lookup;        // associative array for lookup by data-key

   /*********************************************************************
    *	TemplateTag::$model										        *
    *													                *
    *	Constraints on content for each tag defined by HTML:			*
    *         void		means that the tag may not be				    *
    *                   explicitly closed.  For example:	            *
    *                           <br> is treated as XML <br/>			*
    *                           <input> is treated as XML <input/>	    *
    *         paragraph	means that if there is an existing open			*
    *                   <p> tag then the closing </p> tag is assumed	*
    *                   when the next "paragraph" tag is encountered    *
    *			        For example:									*
    *                       <p>first paragraph<p>second ...		        *
    *                             is treated as XML:					*
    *                       <p>first paragraph</p><p>second ...         *
    *********************************************************************/
    static	$model	= array('address'		=> 'paragraph',
                            'area'			=> 'void',
                            'article'		=> 'paragraph',
                            'aside'			=> 'paragraph',
                            'base'			=> 'void',
                            'blockquote'	=> 'paragraph',
                            'br'			=> 'void',
                            'col'			=> 'void',
                            'command'		=> 'void',
                            'div'			=> 'paragraph',
                            'dl'			=> 'paragraph',
                            'embed'			=> 'void',
                            'fieldset'		=> 'paragraph',
                            'footer'		=> 'paragraph',
                            'form'			=> 'paragraph',
                            'h1'			=> 'paragraph',
                            'h2'			=> 'paragraph',
                            'h3'			=> 'paragraph',
                            'h4'			=> 'paragraph',
                            'h5'			=> 'paragraph',
                            'h6'			=> 'paragraph',
                            'header'		=> 'paragraph',
                            'hr'			=> 'void',
                            'img'			=> 'void',
                            'input'			=> 'void',
                            'keygen'		=> 'void',
                            'li'			=> 'li',
                            'link'			=> 'void',
                            'menu'			=> 'paragraph',
                            'meta'			=> 'void',
                            'nav'			=> 'paragraph',
                            'ol'			=> 'paragraph',
                            'p'	    		=> 'paragraph',
                            'param'			=> 'void',
                            'pre'			=> 'paragraph',
                            'section'		=> 'paragraph',
                            'source'		=> 'void',
                            'table'			=> 'paragraph',
                            'track'			=> 'void',
                            'ul'			=> 'paragraph',
                            'wbr'			=> 'void');

    /********************************************************************
     *  TemplateTag::__construct									    *
     *													                *
     *	Create an instance of TemplateTag								*
     *													                *
     *	Input:												            *
     *	    $template	containing instance of Template					*
     *	    $start	starting offset in template text					*
     *	    $inTemplate	true if parsing inside an HTML5  <template> tag	*
     ********************************************************************/
    public function __construct($template,
                                $start		= -1,
                                $inTemplate	= false)
    {
        global	$warn;

        $this->template		    = $template;
        $this->children		    = array();	// empty array
        $this->attributes	    = array();	// empty array
        $this->start		    = $start;
        $this->length		    = 0;		// not known until find end tag
        $this->id		        = '';
        $this->tagLength	    = 0;
        $this->etagLength	    = 0;
        $this->substitutions	= 0;
        $this->lookup	        = null;

        if ($start < 0)
        {			// creating the DOM instance
            $this->tagName		= '**document**';	
            $this->contents		= 'dom';
            $this->elements		= array();	// empty
            $this->start		= 0;
            $page			    = '';
            return;
        }			// template text not available

        // tag
        $page			        = $template->getRawTemplate();
        $matches		        = array();
        preg_match('/<([^>\s]*)/', substr($page, $start), $matches);
        $newTagName		        = $matches[1];
        $this->tagName		    = $newTagName;	
        $tagLc			        = strtolower($newTagName);
        if (array_key_exists($tagLc, TemplateTag::$model))
            $this->contents	    = TemplateTag::$model[$tagLc];
        else
            $this->contents	    = 'default';
        $tagend			        = strpos($page, '>', $start);
        $this->tagLength	    = $tagend - $start + 1;
        $this->etagLength	    = 0;
        if (substr($page, $tagend - 1, 1) == '/')
            $this->contents	    = 'void';
        $startTag	            = substr($page, $this->start, $this->tagLength);
        $matches	            = array();
        $s		                = strlen($newTagName) + 2;
        if ($s < strlen($startTag))
        {			// something after tag name
            while(preg_match('/\s*(([a-zA-Z_$][^=>\s]*)(\s*=\s*(\'([^\']*)\'|"([^"]*)"|([^\s>]+))?)?)/', substr($startTag, $s), $matches))
            {
                $ml		    	= strlen($matches[0]);
                $name			= $matches[2];
                $this->attributes[$name]= $matches[count($matches)-1];
                $s			    += $ml;
            }
        }		    // something after tag name

        if ($inTemplate)
        {			// ignore id of tags inside template
            $this->id		= '';
        }			// ignore id of tags inside template
        else
        if (array_key_exists('id', $this->attributes))
        {
            $this->id		= $this->attributes['id'];
            $this->template->dom->elements[$this->id]	= $this;
        }
        else
            $this->id		= '';
        $this->elements		= null;		// not used

        if ($this->template->debug)
            $warn	.= "<p>TemplateTag::__construct: " . __LINE__ .
                " tagName=" . $this->tagName . "," .
                " id='" .  escape($this->id) . "'," .
                " start=" . $this->start . "," .
                " length=" . $this->length . "," .
                " tagLength=" . $this->tagLength . "," .
                " etagLength=" . $this->etagLength . "," .
                " tag=" .  escape(substr($page, $this->start, $this->tagLength)) . "," .
                " contents=" . $this->contents . "</p>\n";
    }		// function TemplateTag::__construct

    /********************************************************************
     *  function TemplateTag::addChild									*
     *													                *
     *	This method adds a subordinate tag as a child of the current	*
     *	tag.												            *
     ********************************************************************/
    public function addChild($child)
    {
        $this->children[]	= $child;
        $child->parent		= $this;
        $child->template	= $this->template;
    }		// function TemplateTag::addChild

    /********************************************************************
     *  function TemplateTag::replChild									*
     *													                *
     *	This method replaces an existing tag as a child of the current	*
     *	tag.												            *
     ********************************************************************/
    public function replChild($oldChild, $child)
    {
        $page		= $this->template->getRawTemplate();
        $ord		= false;
        for($i = 0; $i < count($this->children); $i++)
        {
            $tag	= $this->children[$i];
            if ($tag->start == $oldChild->start)
            {
                $ord	= $i;
            }
        }
        if ($ord !== false)
            $this->children[$ord]	= $child;
        else
            $this->children[]		= $child;
        $child->parent		= $this;
        $child->template	= $this->template;
    }		// function TemplateTag::replChild

    /********************************************************************
     *  function TemplateTag::update									*
     *													                *
     *  Use information from an array to create repeated HTML/XML text	*
     *	based upon a specific tag in a template.						*
     *	As with Template::add and TTemplate::setFields this method		*
     *	saves the supplied information so it can be applied when the	*
     *	method Template::compile is called.								*
     *													                *
     *  Input:												            *
     *	    $array	an array of entries providing named values			*
     *		    	to be used to replace matching substitution			*
     *		    	points in the tag as a template.					*
     *		    	Each entry in this array may either be an			*
     *		    	associative array or an instance of Record.			*
     *		    	if the array is empty then the tag is removed		*
     *		    	from the generated document.						*
     *		    	if the parameter is not set or is NULL the			*
     *		    	tag is removed from the generated document.			*
     *		    	if this is a string then the value of the string    *
     *		    	replaces the entire tag in the generated update     *
     ********************************************************************/
    public function update($array = null)
    {
        global $warn;

        $backtrace	= debug_backtrace();
        $caller		= current($backtrace);
        if ($this->template->debug)
            $warn	.= "<p>TemplateTag::update: " . __LINE__ .
                                ' called from ' . $caller['file'] . ':' . $caller['line'] . "</p>\n";
        if (!isset($array))
            $array		= array();
        if (is_array($array) || $array instanceof RecordSet)
        {
            if ($this->template->debug)
                $warn	.= "<p>TemplateTag::update: " .  __LINE__ .
                           " count(\$array)=" . count($array) . "</p>\n";
            if (count($array) > 0)
            {			// parameter is not empty
                if ($array instanceof RecordSet)
                    $first	= $array->rewind();
                else
                    $first	= current($array);
                if (is_string($first) || is_numeric($first))
                {		// not an array of arrays or Records
                    $array	= array($array);	// make it so
                }		// not an array of arrays or Records
            }			// parameter is not empty
            $instance		= new ArrayInsertion($this, $array);
            $this->template->addTagUpdate($instance); 
        }
        else
        if ($array instanceof Record)
        {
            if ($this->template->debug)
                $warn	    .= "<p>TemplateTag::update: " .  __LINE__ .
                                " \$array is class " . get_class($array) . "</p>\n";
            $array	        = array($array);
            $this->template->addTagUpdate(new ArrayInsertion($this, $array)); 
        }           // Record
        else
        if (is_string($array))
        {           // string
            if ($this->template->debug)
                $warn	    .= "<p>TemplateTag::update: " .  __LINE__ .
                                " \$array='" . escape($array) . "'</p>\n";
            $instance		= new ArrayInsertion($this, $array);
            $this->template->addTagUpdate($instance); 
        }           // string
        else
        {           // other
            if ($this->template->debug)
                $warn	    .= "<p>TemplateTag::update: " .  __LINE__ .
                " \$array='" . print_r($array,1) . "'</p>\n";
        }
    }		// function TemplateTag::update

    /********************************************************************
     *  function TemplateTag::printTag									*
     *													                *
     *	This method prints a summary of this tag and the tree of		*
     *	tags under this tag.										    *
     ********************************************************************/
    public function printTag($level = 0)
    {
        print $this->show($level);
    }		// function printTag

    /********************************************************************
     *  function TemplateTag::show  									*
     *													                *
     *	This method creates a string containing a summary of this tag   *
     *	and the tree of	tags under this tag.						    *
     ********************************************************************/
    public function show($level = 0)
    {
        $retval     = "<p style='text-indent: {$level}0px;margin-top:0px;'>" .
                        "&lt;" . $this->tagName;
        foreach($this->attributes as $attr => $value)
            $retval     .= " $attr=\"$value\"";
        $retval         .= "&gt; start=" . $this->start .
                            ", length=" . $this->length;
        if ($this->etagLength > 0)
        {
            $retval     .= ' ... ';
            $page		= $this->template->getRawTemplate();
            $etag       = substr($page, $this->start + $this->length - $this->etagLength, $this->etagLength);
            $retval     .= escape($etag);
        }
        $retval         .= "</p>\n";
        foreach ($this->children as $child)
            $retval     .= $child->show($level + 1);
        return $retval;
    }		// function printTag

    /********************************************************************
     *  function TemplateTag::updateInsertions							*
     *													                *
     *	This method tracks information about insertion points that are	*
     *	within the current tag.  The number of insertions points within	*
     *	the tag is tracked in the tag.  Each insertion point tracks		*
     *	which tag it is within.  The function recursively calls itself	*
     *	for each child tag so that each child tag will have the count	*
     *	of insertions within the child tag, and each insertion has		*
     *	a reference to the lowest level tag that it is within.			*
     *													                *
     *	Input:												            *
     *	    $insertions		an array of all insertions in the			*
     *	                            template							*
     *	    $offset		the starting offset of the containing			*
     *	                            tag									*
     *													                *
     *	Side Effects:											        *
     *	    Each element in the array of insertions has a reference		*
     *	    to the lowest level tag that insertion is within			*
     ********************************************************************/
    public function updateInsertions(array &$insertions,
                                         $offset	= 0)
    {
        if ($offset == 0)
            $offset		= $this->start;
        $start		= $this->start - $offset;
        $end		= $start + $this->length;
        for($i = 0; $i < count($insertions); $i++)
        {			// loop through insertions
            if ($insertions[$i][0] >= $start && $insertions[$i][0] <= $end)
            {			// insertion is within tag limits
                $insertions[$i][3]	= $this;
                $this->substitutions++;
            }			// insertion is within tag limits
        }			// loop through insertions
        foreach ($this->children as $child)
            $child->updateInsertions($insertions, $offset);
        return $insertions;
    }		// function updateInsertions

    /********************************************************************
     *  function TemplateTag::outerHTML									*
     *													                *
     *	This method returns the text from the beginning of the tag to	*
     *	the end of the closing tag.									    *
     *													                *
     *	Returns:										                *
     *		string containing HTML or XML				                *
     ********************************************************************/
    public function outerHTML($replace = null)
    {
        global	$warn;

        // the following ensures that the template is loaded
        $page		= $this->template->getRawTemplate();
        if (is_string($replace))
        {                   // string
            if ($this->template->debug)
                $warn	    .= "<p>TemplateTag::outerHTML: " .  __LINE__ .
                                " \$replace='" . escape($replace) . "'</p>\n";
            $instance		= new ArrayInsertion($this, $replace);
            $this->template->addTagUpdate($instance);
            return $replace; 
        }                   // string

        $length		= $this->length;
        if ($length == 0)	// called before ending tag found
            $length	= 32;
        return substr($page,
                      $this->start,
                      $length);
    }		// function TemplateTag::outerHTML

    /********************************************************************
     *  function TemplateTag::innerHTML									*
     *													                *
     *	This method returns the text contained within the tag element	*
     *	including all child tags as text.								*
     *													                *
     *	Returns:										                *
     *		string containing HTML or XML				                *
     ********************************************************************/
    public function innerHTML($replace = null)
    {
        // the following ensures that the template is loaded
        $page		= $this->template->getRawTemplate();
        if (is_string($replace))
        {                   // string
            if ($this->template->debug)
                $warn	    .= "<p>TemplateTag::innrHTML: " .  __LINE__ .
                " \$replace='" . escape($replace) . "'</p>\n";
            $replace        = substr($page, $this->start, $this->tagLength) .
                              $replace .
                              substr($page, 
                                     $this->start + $this->length - $this->etaglength,
                                     $this->etagLength);
            $instance		= new ArrayInsertion($this, $replace);
            $this->template->addTagUpdate($instance);
            return $replace; 
        }                   // string
        $length		= $this->length;
        if ($length == 0)	// called before ending tag found
            $length	= $this->etagLength;
        $start		= $this->start + $this->tagLength;
        $end		= $this->start + $this->length - $this->etagLength;
        return substr($page,
                      $start,
                      $end - $start);
    }		// function TemplateTag::innerHTML

    /********************************************************************
     *  function TemplateTag::__set 									*
     *													                *
     *	This method is defined to permit accessing the outerHTML and    *
     *	innerHTML functions using the same syntax as Javascript         *
     *													                *
     *	Parameters:                                                     *
     *	    $name           name of the function to invoke              *
     *	    $value          string parameter to pass to the function    *
     ********************************************************************/
    public function __set (string $name , $value )
    {
        global $warn;

        if ($name == 'outerHTML')
            $this->outerHTML($value);
        else
        if ($name == 'innerHTML')
            $this->innerHTML($value);
    }       // public function __set (string $name , $value )

    /********************************************************************
     *  function TemplateTag::__get 									*
     *													                *
     *	This method is defined to permit accessing the outerHTML and    *
     *	innerHTML functions using the same syntax as Javascript         *
     *													                *
     *	Parameters:                                                     *
     *	    $name           name of the function to invoke              *
     *													                *
     *	Returns:										                *
     *		string containing HTML or XML				                *
     ********************************************************************/
    public function __get ( string $name )
    {
        global $warn;

        if ($name == 'outerHTML')
            return $this->outerHTML();
        else
        if ($name == 'innerHTML')
            return $this->innerHTML();
        else
        if ($name == 'childNodes')
            return $this->childNodes();

        return null;
    }       // public function __get ( string $name )

    /********************************************************************
     *  function TemplateTag::getElementsByTagName						*
     *													                *
     *	This method searches the children of the current tag and		*
     *  returns an array of tags for which the tag name					*
     *	matches the parameter ignoring case.							*
     *													                *
     *	Input:												            *
     *	    $tagName	string										    *
     *													                *
     *  Returns:											            *
     *	    array of instances of TemplateTag							*
     ********************************************************************/
    public function getElementsByTagName($tagName)
    {
        $tagName	= strtolower($tagName);
        $retval		= array();
        foreach($this->children as $tag)
        {
            if (strtolower($tag->tagName) == $tagName)
                $retval[]	= $tag;
            else
            {
                $temp		= $tag->getElementsByTagName($tagName);
                if (count($temp) > 0)
                    $retval	= array_merge($retval, $temp);
            }
        }
        return $retval;
    }		// function getElementsByTagName

    /********************************************************************
     *  function TemplateTag::childNodes								*
     *													                *
     *	This method returns the immediate children of the node.			*
     *													                *
     *  Returns:											            *
     *	    array of instances of TemplateTag							*
     ********************************************************************/
    public function childNodes()
    {
        return $this->children;
    }		// function TemplateTag::childNodes

    /********************************************************************
     *  function TemplateTag::parentNode								*
     *													                *
     *	This method returns the parent of the node.						*
     *													                *
     *  Returns:											            *
     *	    instance of TemplateTag									    *
     ********************************************************************/
    public function parentNode()
    {
        return $this->parent;
    }		// function TemplateTag::parentNode

    /********************************************************************
     *  function TemplateTag::getTagsById								*
     *													                *
     *	This method returns an array of tags for which the leading		*
     *	portion of the id attribute value matches the supplied key.		*
     *	This method is deprecated.									    *
     *													                *
     *	Input:												            *
     *	    $key	string prefix of the id								*
     *													                *
     *  Returns:											            *
     *	    Associative array of instances of TemplateTag indexed		*
     *	    by id attribute										        *
     *	    This is empty except if called for the Document tag at		*
     *	    the top of the DOM.										    *
     ********************************************************************/
    public function getTagsById($key)
    {
        $retval		= array();
        foreach($this->elements as $id => $tag)
        {
            if (substr($id, 0, strlen($key)) == $key)
                $retval[$id]		= $tag;
        }
        return $retval;
    }		// function getTagsById

    /********************************************************************
     *  function TemplateTag::subscript						            *
     *													                *
     *	This method searches the children of the current tag looking    *
     *	for a match on either a data-key attribute, or if that is not   *
     *	present, on position, and returns the innerHTML value of the    *
     *	identified child tag                                            *
     *													                *
     *	Input:												            *
     *	    $key	string value to search children for					*
     *													                *
     *  Returns:											            *
     *	    string value if match found, otherwise null                 *
     ********************************************************************/
    public function subscript($key)
    {
        if (is_null($this->lookup))
        {                   // build lookup table
            $index                  = 0;
            foreach($this->childNodes() as $span)
            {
                if (array_key_exists('data-key', $span->attributes))
                {
                    $newkey		    = $span->attributes['data-key'];
                    if (ctype_digit($newkey))
                    {
                        $newkey     = intval($newkey);
                        if ($newkey >= $index)
                            $index  = $newkey + 1;
                    }
                }
                else
                {
                    $newkey         = $index;
                    $index++;
                }
                $this->lookup[$newkey]	= trim($span->innerHTML());
            }
            
        }                   // build lookup table
        if (array_key_exists($key, $this->lookup))
            return $this->lookup[$key];
        else
            return null;
    }		// function subscript

    /********************************************************************
     *	function TemplateTag::offsetSet									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Updating the set of identified tags is not supported.           *
     *	This implements:                                                *
     *	    $tag[$key]      = $value;                                   *
     *																	*
     *	Parameters:														*
     *	    $key		    lookup key.                                 *
     *	    $value	    	new value               					*
     *																	*
     ********************************************************************/
    public function offsetSet($key, $value)
    {
        if (is_string($key) || is_int($key))
        {
	        if (is_string($value))
	            $this->lookup[$key]     = $value;
	        else
                throw new Exception("TemplateTag::offsetSet: " .
                            "cannot add something other than a string value");
        }
        else
            throw new Exception("TemplateTag::offsetSet: " .
                                "key must be a string or int");
    }		// public function Template::offsetSet

    /********************************************************************
     *	function TemplateTag::offsetExists								*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	    isset($template[$key]);										*
     *																	*
     *	Parameters:														*
     *	    $key		lookup key.                                     *
     ********************************************************************/
    public function offsetExists($key)
    {
		return (array_key_exists($key, $this->lookup));
    }		// public function TemplateTag::offsetExists($key)

    /********************************************************************
     *	function TemplateTag::offsetUnset								*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	It is not supported for external users to delete a tag          *
     *																	*
     *	Parameters:														*
     *	    $key		lookup key.                                     *
     ********************************************************************/
    public function offsetUnset($key)
    {
		if (array_key_exists($key, $this->lookup))
		    unset($this->lookup[$key]);
    }		// private function TemplateTag::offsetUnset($key)

    /********************************************************************
     *	function TemplateTag::offsetGet									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Returns the string associated with a key.		                *
     *	    $value		= $tag[$key];   								*
     *	This is the same as calling $this->subscript($key).  			*
     *																	*
     *	Parameters:														*
     *	    $key	    lookup key	                                    *
     ********************************************************************/
    public function offsetGet($key)
    {
        return $this->subscript($key);
    }		// public function TemplateTag::offsetGet($key)

    /********************************************************************
     *	function TemplateTag::rewind									*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Reposition to the first element of the "array".					*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function rewind()
    {
        $this->subscript(0);        // initialize $this->lookup
        return reset($this->lookup);
    }		// function TemplateTag::rewind

    /********************************************************************
     *	function TemplateTag::current									*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the value of the current element of the "array"			*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function current()
    {
		return current($this->lookup);
    }		// function TemplateTag::current

    /********************************************************************
     *	function TemplateTag::key										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the key of the current element of the "array".			*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function key()
    {
		return key($this->lookup);
    }		// function TemplateTag::key

    /********************************************************************
     *	function TemplateTag::next										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Advance to the next element of the "array".						*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function next()
    {
		return next($this->lookup);
    }		// function TemplateTag::next

    /********************************************************************
     *	function TemplateTag::valid										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Returns true if the iterator is valid.							*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function valid()
    {
        $key 		= key($this->lookup);
	    return ($key !== NULL && $key !== FALSE);
    }		// function TemplateTag::valid

}		// class TemplateTag
