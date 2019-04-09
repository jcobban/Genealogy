<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  displayPage.php														*
 *																		*
 *  This script displays a selected template within the basic page.		*
 *  This should be used to display all pages which were simple HTML		*
 *  so that the standard frame for the site is presented.				*
 *																		*
 *  Input:																*
 *		Template		the template name.								*
 *						is this is a simple file name the template is	*
 *						obtained from the template directory.       	*
 *		lang			requested language, default 'en'				*
 *																		*
 *    History:															*
 *		2018/01/23		created											*
 *		2018/01/25		common functionality moved to class FtTemplate	*
 *		2018/02/03		generate popups for links to family tree		*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/05      embed <body> contents of non-template pages     *
 *		                into main template so all of the FtTemplate     *
 *		                customization is performed                      *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  $individTable		table to map IDIR to instance of Person			*
 ************************************************************************/
$individTable	= array();

/************************************************************************
 *  createPopups														*
 *																		*
 *  Create popups for any individuals identified by hyper-links in		*
 *  the supplied text.													*
 *																		*
 *  Parameters:															*
 *		$text		text to check for hyper-links to individuals		*
 *																		*
 *  Returns:	the supplied text, ensuring that the hyperlinks use		*
 *				absolute URLs.											*
 ************************************************************************/
function createPopups($text)
{
    global	$warn;
    global	$individTable;

    $pieces		= explode('<a ', $text);
    $first		= true;
    $retval		= '';
    foreach($pieces as $piece)
    {		// description contains a link
		if ($first)
		{
		    $retval	.= $piece;
		    $first	= false;
		    continue;
		}
		$retval		.= "<a ";
		$urlstart	= strpos($piece, "href=");
		// $quote is either single or double quote
		$quote		= substr($piece, $urlstart + 5, 1);
		$urlstart	+= 6;
		$urlend		= strpos($piece, $quote, $urlstart);
		$url		= substr($piece,
							 $urlstart,
							 $urlend - $urlstart);
		$equalpos	= strrpos($url, "idir=");
		if ($equalpos !== false)
		{		// link to an individual
		    $refidir		= substr($url, $equalpos + 5);
		    $refind		= new Person(array('idir' => $refidir));
		    $individTable[$refidir]	= $refind;
		    if (substr($url, 0, $equalpos) == "Person.php?")
				$retval	.= substr($piece, 0, $urlstart) .
						     "/FamilyTree/" .
						     substr($piece, $urlstart);
		    else
				$retval	.= $piece;
		}
		else
		    $retval	.= $piece;
    }		// description contains a link
    return $retval;
}		// function createPopups

/************************************************************************
 *		open code														*
 ***********************************************************************/
$templateName	= null;
$lang	    	= 'en';		// default english

// process parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach ($_GET as $key => $value)
	{			// loop through all parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'template':
			{		// requested template
			    if (strlen($value) > 0)
					$templateName	= $value;
			    break;
			}		// requested template
	
			case 'lang':
			{		// requested language
			    if (strlen($value) >= 2)
					$lang	        = strtolower(substr($value,0,2));
			    break;
			}		// requested language
	
	    }			// switch on parameter name
	}			// foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$tempBase	            = $document_root . '/templates/';
if (substr($templateName, 0, 1) == '/')
{                   // template location relative to document root
    $template	            = new FtTemplate("${tempBase}page$lang.html");
    if (substr($templateName, -5) != '.html' &&
        substr($templateName, -4) != '.htm')
    {               // add filetype
        $templateName	= $templateName . '.html';
    }               // add filetype

	if (file_exists($document_root . $templateName))
    {               // template file exists
        $sourceTemplate = new Template($document_root . $templateName);
        $document       = $sourceTemplate->getDocument();
        $html           = $document->childNodes()[0];
        $toplevel       = $html->childNodes();
        foreach($toplevel as $tag)
        {           // loop through children of <html>
            if (strtolower($tag->tagName) == 'body')
            {       // <body> tag
                $body       = $tag;
                $bodyText   = $body->innerHTML();
                $bodyText   .= "\n        <script src=\"/jscripts/util.js\" type=\"application/javaScript\">\n</script>\n";
                $template->includeSub($bodyText,    'MAIN');
                break;
            }       // <body> tag
        }           // loop through children of <html>
    }               // template file exists
    else
        $msg        .= "Unable to open file $document_root$templateName. ";
}                   // template location relative to document root
else
{                   // template location relative to template directory
    if (substr($templateName, -5) == '.html')
    {               // exclude file type
        $templateName	= substr($templateName, 0, strlen($templateName) - 5);
    }               // exclude file type
	$includeSub	        = $templateName . $lang . '.html';
	$template           = new FtTemplate($includeSub);
}                   // template location relative to template directory

// create popup balloons for each of the individuals referenced on this page
$tag		            = $template['Individ$idir'];
$bodyTag   	            = $template['familyTree'];
if ($tag && $bodyTag)
{			// place to expand popups
    $body   	        = $bodyTag->innerHTML();
    createPopups($body);
    $templateParms	    = array();
    foreach($individTable as $idir => $individ)
    {		// loop through all referenced individuals
		$name		    = $individ->getName();
		$evBirth	    = $individ->getBirthEvent();
		if ($evBirth)
		{
		    $birthd 	= $evBirth->getDate();
		    $birthloc	= $evBirth->getLocation()->getName();
		    if ($birthloc == '')
		    {
				$birthloc	= array();
		    }
		}
		else
		{
		    $birthd 	= array();
		    $birthloc	= array();
		}
		$evDeath	    = $individ->getDeathEvent();
		if ($evDeath)
		{
		    $deathd	    = $evDeath->getDate();
		    $deathloc	= $evDeath->getLocation()->getName();
		    if ($deathloc == '')
		    {
				$deathloc	= array();
		    }
		}
		else
		{
		    $deathd	    = array();
		    $deathloc	= array();
		}
		$families	    = $individ->getFamilies();
		$parents	    = $individ->getParents();
		$entry		    = array('name'			=> $name,
						    	'idir'			=> $individ->get('idir'),
						    	'birthd'		=> $birthd,
						    	'birthloc'		=> $birthloc,
						    	'deathd'		=> $deathd,
						    	'deathloc'		=> $deathloc,
						    	'description'	=> '',
						    	'families'		=> $families,
						    	'parents'		=> $parents);
		$templateParms[$idir]	= $entry;
    }		// loop through all referenced individuals
    
    // create popup balloons for each of the people referenced on this page
    $template->updateTag('Individ$idir',
						 $templateParms);
}			// place to expand popups
showTrace();
$template->display();
