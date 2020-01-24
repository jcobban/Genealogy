<?php
namespace Genealogy;
use \Exception;
use \NumberFormatter;
use Templating\Template;
use Templating\TemplateTag;

/************************************************************************
 *  class FtTemplate									    			*
 *														                *
 *  History:															*
 *      2019/04/08      split off from Template.inc                     *
 *      2019/05/17      change CONTACT... only if not already set       *
 *                      if called with something that is not the        *
 *                      name of a file, invoke base class               *
 *      2019/06/25      add class="menuitem" to all links in the menu   *
 *                      separate portions of menu by a menu bar         *
 *      2019/07/19      add support for country-qualified language code *
 *      2019/07/23      add static method validateLang                  *
 *      2019/12/23      use class Advertisers to locate next            *
 *                      advertisement                                   *
 *      2020/01/22      add method getFormatter                         *
 *	    									                            *
 *  Copyright &copy; 2020 James A. Cobban All Rights Reserved			*
 ************************************************************************/

/************************************************************************
 *  class FTTemplate									    			*
 *														                *
 *  Class derived from Template to support pages in the Family Tree		*
 *  web-site that use /templates/pagexxx.html as the base layout.		*
 ************************************************************************/

// the classes User and Language need to be defined in order to execute this
// sample extension.  It is requested by include rather than require
// so this sample code can be left in place even when not used
require_once  'Templating/Template.inc' ;
include_once  __NAMESPACE__ . '/User.inc';
include_once  __NAMESPACE__ . '/Language.inc';
include_once  __NAMESPACE__ . '/Advertiser.inc';

class FtTemplate extends Template
{
    /********************************************************************
     *  FtTemplate::lang                                                *
     *													                *
     *	The requested ISO language code.                                *
     ********************************************************************/
    private $lang               = 'en';
    private $formatter          = null;

    /********************************************************************
     *  FtTemplate::trtemplate                                          *
     *													                *
     *	Instance of Template for translating to language	            *
     ********************************************************************/
    private $trtremplate        = null;

    /********************************************************************
     *	function FtTemplate::__construct							    *
     *													                *
     *	Input:												            *
     *		$include	a string containing the filename of a		    *
     *		    	    template file.                      			*
     *		    	    If this is "...pagell.html" or                  *
     *		    	    "...dialogll.html" then this is the master      *
     *		    	    page/dialog template.  Otherwise it is the name *
     *		            of the language specific main include file.     *
     *		            This string ends with "ll.html" where "ll"      *
     *		            is the BCP 47 language code.  This string is a  *
     *		            file name relative to the common templates      *
     *		            directory "$document_root/templates/"           * 
     *													                *
     *	Examples:											            *
     *	    $template = new FtTemplate("${tempBase}page$lang.html");    *
     *		$template = new FtTemplate('genealogypl.html');				*
     ********************************************************************/
    public function __construct($include    = null,
                                $dialog     = false)
    {
        global	$debug;
        global	$warn;
        global	$msg;
        global  $document_root;

        if (!is_string($include))
            throw new Exception("FtTemplate::__construct: " .
                                "first parameter is not a string");
        if (!preg_match('/(^.*)([a-z]{2,3}-[A-Z]{2,3}|[a-z]{2,3}).html$/',
                        $include, 
                        $matches))
        {                   // pass to parent
            parent::__construct($include);
            return;
        }                   // pass to parent

        $includebase        = $matches[1];
        $lang               = $matches[2];
        $this->lang         = $lang;
        $this->formatter    = new \NumberFormatter(str_replace('-','_',$lang),
                                                   \NumberFormatter::DECIMAL);
        $includeSub         = null;

    	$tempBase	        = $document_root . '/templates/';

        if (substr($include,-12,5) == '/page' ||
            substr($include,-14,7) == '/dialog')
            parent::__construct($include);  // master template
        else
        {                   // include file name
		    if (file_exists($tempBase . $include))
		        $includeSub     = $include;
		    else
			{               // try to find closest match
                $language   	= new Language(array('code' => $lang));
                if ($language->isExisting())
                    $lang       = $language['code'];
                else
                    $lang       = 'en';
                $langName	    = $language->get('name');
				$nativeName	    = $language->get('nativename');
				$sorry  	    = $language->getSorry();
			    $warn   	    .= str_replace(array('$langName','$nativeName'),
			                                   array($langName, $nativeName),
			                                   $sorry);
				$includeSub     = "$includebase$lang.html";
		        if (file_exists("$tempBase$includebase$lang.html"))
				    $includeSub = "$includebase$lang.html";
		        else
		        if (file_exists("$tempBase$includebase" . "en.html"))
				    $includeSub = $includebase . "en.html";
		        else
				    throw new Exception ("FtTemplate::__construct: cannot find template file '{$includebase}en.html'");
            }               // try to find closest match

            if (strlen($lang) > 2)
                $langcode       = substr($lang, 0, 2);
            else
                $langcode       = $lang;
            if ($dialog)
                parent::__construct($tempBase . "dialog$langcode.html");
            else
                parent::__construct($tempBase . "page$langcode.html");

            parent::includeSub($tempBase . $includeSub,
		                       'MAIN');
        }                   // include file name

        if (file_exists($tempBase . "Trantab$lang.html"))
            $this->trtemplate = new Template("${tempBase}Trantab$lang.html");
        else
            $this->trtemplate = new Template("${tempBase}Trantaben.html");
    }           // function FtTemplate::__construct

    /********************************************************************
     *	static function FtTemplate::validateLang						*
     *													            	*
     *	Input:											            	*
     *		$value          a string provided by an application from    *
     *		                user input and therefore unreliable	       	*
     *													            	*
     *	Returns:										            	*
     *		An IETF BCP 47 language code possibly country-qualified	    *
     *		for example 'es-MX'.  If the syntax of the input value      *
     *		is not consistent with IETF BCP 47 this returns 'en'.       *
     ********************************************************************/
    public static function validateLang($value)
    {
        $lang               = 'en';
        $value              = str_replace('_','-',$value);
        if (preg_match('/^([a-zA-Z]{2,3})(-([a-zA-Z0-9]{2,3})|)$/',
                       $value,
                       $matches))
        {
            if (strlen($matches[2]) > 0)
                $lang       = strtolower($matches[1]) . '-' .
                              strtoupper($matches[3]);
            else
                $lang       = strtolower($matches[1]);
        }
        else
        {
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value, 0, 2));
        }
        return $lang;
    }           // static function validateLang

    /********************************************************************
     *	function FtTemplate::getFormatter								*
     *													            	*
     *	Get the locale specific number formatter                        *
     *													            	*
     *	Returns:										            	*
     *		instance of class NumberFormatter					        *
     ********************************************************************/
    public function getFormatter()
    {
        return $this->formatter;
    }           // function getFormatter

    /********************************************************************
     *	function FtTemplate::getTranslate								*
     *													            	*
     *	Get the language specific translate table.						*
     *													            	*
     *	Returns:										            	*
     *		instance of class Template					            	*
     ********************************************************************/
    public function getTranslate()
    {
        return $this->trtemplate;
    }           // function getTranslate

    /********************************************************************
     *	function FtTemplate::includeSub									*
     *													                *
     *	Specify a template file which is to be included and the name    *
     *	of the insertion point where it is to be inserted.				*
     *	I would have preferred to call this function "include" but that	*
     *	is a reserved word in PHP 5.6 although not in PHP 7.			*
     *													                *
     *	INPUT:												            *
     *	    $filename	file name to include							*
     *	                this name is relative to the application        *
     *	                template folder rather than being an absolute   *
     *	                file name as is passed to Template::includeSub  *
     *	    $submarker	insertion point name.  This is folded to		*
     *			        upper case before searching the master template.*
     *	    $after	    default false, means to perform the insertion	*
     *			        before applying text substitutions specified	*
     *			        by $template->set or $template->updateTag.		*
     *			        If true this insertion is done after text		*
     *			        substitution.  This is required if the include	*
     *			        file contains references that might be falsely	*
     *			        identified as insertion points.					*
     *													                *
     *	Returns:											            *
     *	    true if the requested file exists, otherwise false			*
     ********************************************************************/
    public function includeSub($filename,
                               $submarker	= 'SUBPAGE',
                               $after       = false)
    {
        global  $document_root;

        $tempBase	        = $document_root . '/templates/';
        if (substr($filename, -5) == ".html" &&
            !file_exists($tempBase . $filename))
        {
            $filename	    = substr($filename, 0, strlen($filename) - 7) .
                              'en.html';
        }

        return parent::includeSub($tempBase . $filename,
                                  $submarker,
                                  $after);
    }       // function includeSub

    /********************************************************************
     *	function FtTemplate::customization								*
     *													            	*
     *	This is the site specific customization of the initialization	*
     *	of an instance of Template for the Family Tree web-site.		*
     *	The convention of the Family Tree web-site is that the master	*
     *	template file is named:											*
     *		page.html	    English HTML5								*
     *		pagefr.html	    French HTML5								*
     *		pagexx.html	    for language code xx HTML5                	*
     *		pageIE7.html	English IE < 8 (HTML 4)						*
     *		pagefrIE7.html	French IE < 8 (HTML 4)						*
     *		pagexxIE7.html	for language code xx IE < 8 (HTML4)			*
     ********************************************************************/
    public function customization()
    {
        global		$browser;		// initialized by common.inc
        global		$debug;			// debugging activated
        global		$warn;			// tracing and warning output
        global		$msg;			// error message output
        global		$dsn;			// information about current server
        global		$iecrap;		// IE 7 crap
        global		$userid;		// logged on userid
        global		$authorized;	// current user authorization string
        global		$options;		// current user functionality options

        if ($debug)
            $warn   .= "<p>FtTemplate::customization: " . __LINE__ .
                        " \$this->main='" . $this->main . "'</p>";
        $mainlen	        = strlen($this->main) - 5;
        $matches		    = array();
        if (preg_match('/(page|dialog)(\w*).html$/', $this->main, $matches))
        {
            $lang		    = $matches[2];
            $langlen	    = strlen($lang);
            $mainlen	    = $mainlen - $langlen;
            if ($langlen == 0)
                $lang       = 'en';
        }
        else
        {
            $lang		    = 'en';
        }
        // $main excludes the language code, if present, and .html
        $main	            = substr($this->main, 0, $mainlen);

        // support language specific templates and non-HTML5 compliant browsers
        if ($browser->browser == 'IE' && $browser->majorver < 9)
        {		        // old browser
            // users who are still trying to run Windows XP with IE6 or IE7
            if (file_exists("{$main}IE7$lang.html"))
                $this->main	    = "{$main}IE7$lang.html";
            else
            if (file_exists("{$main}IE7en.html"))
                $this->main	= "{$main}IE7en.html";
            else
                $this->main	= "{$main}IE7.html";
        }		        // old browser
        else
        {               // W3C compliant browser
            if (file_exists("$main$lang.html"))
                $this->main	    = "$main$lang.html";
            else
            if (file_exists("{$main}en.html"))
                $this->main	    = "{$main}en.html";
            else
                $this->main	    = "{$main}.html";
        }               // W3C compliant browser

        if ($debug)
            $this->set('DEBUG',	        'Y');
        else
            $this->set('DEBUG',	        'N');

        $this->set('IECRAP',		    $iecrap);	// support IE < 8

        // run-time can interrogate these values to take action
        // for example the OPTIONS value is used to determine whether or
        // not to use popup help
        $this->set('USERID',		    $userid);
        $this->set('AUTHORIZED',	    $authorized);
        $this->set('OPTIONS',		    $options);

        if (!array_key_exists('KEYWORDS',       $this->fields))
			$this->set('KEYWORDS',		'');	// additional meta keywords
        if (!array_key_exists('CONTACTKEY',     $this->fields))
			$this->set('CONTACTKEY',	'');	// identify the specific record
        if (!array_key_exists('CONTACTTABLE',   $this->fields))
			$this->set('CONTACTTABLE',	'');	// identify the table
        if (!array_key_exists('CONTACTSUBJECT', $this->fields))
			$this->set('CONTACTSUBJECT',$_SERVER['REQUEST_URI']);
        $this->set('GOOGLEKEY',         $dsn['googlekey']);
        $this->set('GOOGLECC',          'CA');

        // invoke parent functionality
        parent::customization();
    }		// FtTemplate::customization

    /********************************************************************
     *	function FtTemplate::displayBegin								*
     *															        *
     *	This is the site specific customization which is performed		*
     *	immediately prior to generating the output of the Template.		*
     *	Prepare to report any issues that have been encountered			*
     *	during the preparation of the page to the user.					*
     *	This is deferred until just before the page is displayed.		*
     ********************************************************************/
    public function displayBegin()
    {
        global		$debug;
        global		$warn;		// tracing
        global		$msg;		// error messages
        global		$document_root;	// root directory of website

        $lang                   = $this->lang;

        // if the page contains an <h1> tag use that as the default title
        if (!array_key_exists('TITLE', $this->fields) ||
            $this->fields['TITLE'] == '')
        {		// script has not initialized TITLE
            $h1array		    = $this->getElementsByTagName('h1');
            if (count($h1array) > 0)
            {
                $title		    = current($h1array)->innerHTML();
                // remove tags
                $spanpos	    = strpos($title, '<span class');
                if ($spanpos !== false)
                {
                    $clospos	= strpos($title, '</span>', $spanpos) + 7;
                    $title		= substr($title, 0, $spanpos) .
                                  substr($title, $clospos);
                }
                $title		    = preg_replace('#<[^>]+>#s', '', $title);
                $title		    = trim($title);
                // do substitutions into the string and make it the
                // value of TITLE
                $this->set('TITLE', $title, true);
            }
            else
                $warn	.= "<p>FtTemplate::displayBegin: " . __LINE__ .
                " could not find &lt;h1&gt; to initialize TITLE</p>\n";
        }		// script has not initialized TITLE

        // pick an advertisement to display
        $getparms               = array('adname' => '!');
        $advertisers            = new RecordSet('Advertisers',
                                                $getparms);
        $info                   = $advertisers->getInformation();
        $count                  = $info['count'];
        $counterRec             = new Advertiser(array('adname' => ''));
        $counterRec->increment('01');   // stats count
        $index	                = $counterRec['count01'] % $count;
        $getparms               = array('adname'    => '!',
                                        'offset'    => $index,
                                        'limit'     => 1);
        $advertisers            = new RecordSet('Advertisers',
                                                $getparms);
        $advertiser             = $advertisers->rewind();
        $adname                 = $advertiser['adname'];
        $adurl	                = "/Advertisements/$adname.html";
        $date                   = date('m d');
        $month                  = substr($date, 0, 2);
        $advertiser->increment($month);

        // the following code is to ensure that the count for
        // the next month is zero before the month begins
        // this should be moved to a cron job
        $day                    = substr($date, -2);
        if ($day == '28')
        {                   // move to cron job
            $month              += 1;
            if ($month > 12)
                $month          = $month - 13;
            if (strlen($month) == 1)
                $month          = '0' . $month;
            $advertiser->set("Count$month", 0);
            $advertiser->save(false);
        }                   // move to cron job

        // change insertion of ads to not use <iframe>
        $adElement              = $this['advertFrame'];
        if ($adElement)
        {
            $adTemplate         = new Template($document_root . $adurl);
            $adDoc              = $adTemplate->getDocument();
            $adBody             = $adDoc->getElementsByTagName('body');
            if (count($adBody) > 0)
            {
                $adBody         = current($adBody);
                // the following replaces the <iframe> with the contents of
                // the <body> of the referenced URL
                $adElement->update($adBody->innerHTML());
            }
            else
                $warn           .= $adTemplate->getDocument()->show();
        }
        else
            $this->set('ADURL',		    $adurl);
 
        // if the script has not explicitly defined a special stylesheet
        // delete the alternate stylesheet tag
        $noSsSet     = true;
        foreach($this->tagUpdates as $id => $entry)
        {
            if ($entry->tag->id == 'otherStylesheets')
            {
                $noSsSet     = false;
                break;
            }
        }
        if ($noSsSet)
            $this->updateTag('otherStylesheets',    null);

        // set TRACE to a paragraph containing warning messages if any
        if (strlen($warn) > 0)
        {
            ob_start();
            showTrace();	// common presentation
            $this->set('TRACE',	ob_get_clean());
            $warn		= '';
        }
        else
            $this->set('TRACE',		'');

        // set MSG to a paragraph containing error messages if any
        if (strlen($msg) > 0)
        {
            $this->set('MSG',		"<p class='message'>$msg</p>\n");
            $msg		= '';
        }
        else
            $this->set('MSG',		'');

        // set LANG to the requested language code
        $this->set('LANG',		    $this->lang);

        // initialize BREADCRUMBS from <div id='breadcrumbs'>
        if (array_key_exists('BREADCRUMBS', $this->fields))
        {
            $breadcrumbs            = $this->fields['BREADCRUMBS'];
        }
        else
        {                   // not already defined
	        $crumbTag		        = $this->getElementById('breadcrumbs');
	        if ($crumbTag)
	        {			    // breadcrumbs definition found
                $breadcrumbs	    = $crumbTag->innerHTML();
                $anchors            = preg_split('/<a\s/', $breadcrumbs);
                $breadcrumbs        = $anchors[0];
                for($i = 1; $i < count($anchors); $i++)
                {
                    $anchor         = $anchors[$i];
                    $apos           = strpos($anchor, '>');
                    $breadcrumbs    .= '<a ' . substr($anchor, 0, $apos) .
                                        ' class="menuitem"' .
                                        substr($anchor, $apos);
                }
	            $crumbTag->update(null);	// delete the template tag
            }
	        else
	        {			    // tag not found
	            $includeSub		= current($this->includes);
	            $warn	.= "<p>FtTemplate::customization: " .
                    "no element id='breadcrumbs' in '$includeSub'</p>\n";
                $breadcrumbs    = '';
            }			    // tag not found
        }

	    $menuButton		            = $this->getElementById('menuButton');
        if ($menuButton)
        {                   // new heading style
            $breadcrumbs            = str_replace(':', '<br>', $breadcrumbs);
            $breadcrumbs            .= '<hr class="menubar">';
            global  $userid;
            if (isset($userid) && strlen($userid) > 0)
            {               // signed on
                $elt                = $this['UserInfoAccount'];
                $atemplate          = new Template($elt->innerHTML());
                $atemplate->setFields($this->fields);
                // determine if their are messages queued to the current user
                $user	            = new User(array("username" => $userid));
                $blogParms	        = array('keyvalue'	=> $user->get('id'),
                                            'table'		=> '^Users$');
                $bloglist	        = new RecordSet('Blogs', $blogParms);
                $blogCount	        = $bloglist->getInformation()['count'];
                if ($blogCount == 0)
                    $atemplate['gotMail']->update(null);
                $breadcrumbs        .= $atemplate->compile();
            }               // signed on
            else
            {               // not signed on
                $elt                = $this['UserInfoSignon'];
                if ($elt)
                    $signon         = $elt->innerHTML();
                else
                    $signon         = 'Sign On';
                $breadcrumbs        .= "<a href=\"/Signon.php?lang=$lang\" id=\"session\" target=\"_blank\" class=\"menuitem\">$signon</a><br>\n";
            }               // not xigned on

            // search for additional language support
            $includeSub		        = current($this->includes);
            if (strpos($includeSub, '<') === false)
            {               // value does not include HTML    
                $count          = preg_match("/([^\/]*)([a-z]{2}-[A-Z]{2}|[a-z]{2}).html$/", $includeSub, $matches);
                if ($count)
                {
                    $filename   = $matches[0];
                    $prefix     = $matches[1];
                    $langcode   = $matches[2];
                    $flen       = strlen($includeSub) - strlen($filename);
                    $folder     = substr($includeSub, 0, $flen);
                }
                else
                {               // this shouldn't happen
	                $prefix     = substr($includeSub, 0, strlen($includeSub)-7);
	                $enddir     = strrpos($prefix, '/');
	                $folder     = substr($prefix, 0, $enddir + 1);
	                $prefix     = substr($prefix, $enddir + 1);
                }               // this shouldn't
                $script         = $_SERVER['SCRIPT_NAME'];
                $query          = $_SERVER['QUERY_STRING'];
                $oparms         = explode('&', $query);
                $nparms         = '';
                $amp            = '?';
                foreach($oparms as $parm)
                {
                    $pieces     = explode('=', $parm);
                    $key        = strtolower($pieces[0]);
                    if ($key != '' && $key != 'lang')
                    {
                        $nparms .= $amp . $parm;
                        $amp    = '&';
                    }
                }
                $dir            = opendir($folder);
                if ($dir === false)
                {
                    print "<p>FtTemplate.inc: " . __LINE__ . " folder='" .
                        \Templating\escape($folder) . "'</p>\n";
                    exit;
                }

                // add links to other language versions of the current page
                $first              = true;
                while(($name = readdir($dir)) !== false)
                {           // search files in template directory
                    if (preg_match("/^$prefix([a-z]{2}-[A-Z]{2}|[a-z]{2}).html$/", $name, $matches))
                    {       // file matches current template
                        $tlang  	    = $matches[1];
                        if ($tlang != $lang)
                        {   // not the current language
                            if ($first)
                            {
                                $breadcrumbs    .= '<hr class="menubar">';
                                $first          = false;
                            }
                            $language   = new Language(array('code' => $tlang));
                            $langcode   = $language['code'];
                            $native     = $language['nativename'];
                            $cc         = $language['cc'];
                            if ($cc)
                            {
                                $country    = new Country(array('cc' => $cc));
                                if ($country->isExisting())
                                    $native .= '(' . $country->getName($langcode) . ')';
                            }
                            $comma      = strpos($native, ',');
                            if ($comma !== false)
                                $native = substr($native, 0, $comma);
                            $native     = ucfirst($native);
                            $breadcrumbs.= 
                                "<a href=\"$script$nparms{$amp}lang=$tlang\" class=\"menuitem\">$native</a><br>\n";
                        }   // not the current language
                    }       // file matches current template
                }           // search files in template directory
            }               // includeSub is not HTML text

            // add contents of help section of <h1> tag
            $breadcrumbs                .= '<hr class="menubar">';
            $h1array		            = $this->getElementsByTagName('h1');
            if (count($h1array) > 0)
            {               // at least one <h1> tag in page
                $h1		                = current($h1array);
                foreach($h1->childNodes() as $child)
                {           // loop through children of first <h1>
                    if ($child->tagName == 'span' &&
                        isset($child->attributes['class']) &&
                        $child->attributes['class'] == 'right')
                    {       // move help section to menu
                        $help       = $child->innerHTML();
                        $apos       = strpos($help, '<a');
                        $apos       = strpos($help, '>', $apos + 1);
                        $help       = substr($help, 0, $apos) .
                                      ' id="menuhelp" class="menuitem"' .
                                      substr($help, $apos);
                        $help       = str_replace('</a>',
                                                  '</a><br>',
                                                  $help);
                        $breadcrumbs.= $help;
                        $child->update(null);
                    }       // move help section to menu
                }           // loop through children of first <h1>
            }               // at least one <h1> tag in page
            else
            {
                $warn	.= "<p>FtTemplate::customization: " . __LINE__ .
                                " no &lt;h1&gt; on page</p>\n";
                $warn   .= $this->getDocument()->show();
            }

            // add about link
            $elt            = $this['UserInfoAbout'];
            if ($elt)
                $about      = $elt->innerHTML();
            else
                $about      = 'About this Site';
            $breadcrumbs    .= "<a href=\"/AboutHelp$lang.html\" class=\"menuitem\" target=\"help\">$about</a><br>\n";
            // add contact author link
            $footSection    = $this['footSection'];
            foreach($footSection->childNodes() as $child)
            {           // loop through children of footer
                if ($child->tagName == 'div' &&
                    isset($child->attributes['class']) &&
                    $child->attributes['class'] == 'botcrumbs')
                {       // move help section to menu
                    $help       = $child->innerHTML();
                    $apos       = strpos($help, '<a');
                    $apos       = strpos($help, '>', $apos + 1);
                    $help       = substr($help, 0, $apos) .
                                  ' class="menuitem"' .
                                  substr($help, $apos);
                    $breadcrumbs.= $help;
                    $child->update(null);
                }       // move help section to menu
            }           // loop through children of first <h1>

        }                   // new heading style
        else
            $warn   .= "<p>FtTemplate::displayBegin: breadcrumbs=" .
                        \Templating\escape($breadcrumbs) . "</p>\n";

	    $this->set('BREADCRUMBS',	$breadcrumbs, true);

        // invoke parent functionality
        parent::displayBegin();
    }		// FtTemplate::displayBegin

    /********************************************************************
     *	function FtTemplate::displayEnd									*
     *													            	*
     *	This is the site specific customization which is performed		*
     *	immediately after generating the output of the Template			*
     *	but before it is returned to the requestor.						*
     ********************************************************************/
    public function displayEnd()
    {
        global $warn;

        if (strlen($warn) > 0)
        {			// diagnostic messages from Template class
            // prefer to insert this at the end of the <main> section
            // which is defined in the standard /templates/page.html
            // but if I cannot find that, insert it before the end of the
            // <body> section, and failing that before the end of the
            // document
            $position	= strpos($this->page, '</main>');
            if ($position === false)
            {
                $position	= strpos($this->page, '</body>');
                if ($position === false && count($this->dom->children) > 0)
                {		// insert before the last closing tag
                    $topTag	= $this->dom->children[0];
                    $topTagName	= $topTag->tagName;	// handle XML
                    $position	= strpos($this->page, "</$topTagName>");
                    if ($position === false)
                        $position	= strlen($this->page);
                }
            }

            // insert the trace output into the generated page
            ob_start();
            showTrace();	// common presentation
            $this->page		= substr($this->page, 0, $position) .
										ob_get_clean() .
										substr($this->page, $position);
        }			// diagnostic messages from Template class
        // invoke parent functionality
        parent::displayEnd();
    }		// FtTemplate::displayEnd
}		// class FtTemplate
