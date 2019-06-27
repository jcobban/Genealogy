<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\TemplateTag;

/************************************************************************
 *  BlogPost.php														*
 *																		*
 *  Display a web page for creating a new Blog post.					*
 *																		*
 *  Parameters (passed by method='get')									*
 *		blogid	unique numeric identifier of the entry in table Blogs	*
 *				to which this ia a response or follow on.  Default 0.	*
 *		table	Table that this message is referencing.  Default 'Blogs'*
 *																		*
 * History:																*
 *		2018/09/12	    created											*
 *		2018/12/12      change insertion in title to BLOGTITLE          *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function responses													*
 *  																	*
 *  This function is invoked recursively to display the tree of			*
 *  blog posts.															*
 *  																	*
 *  Input:																*
 *      $blogid		the identifier of the post at the top of a	    	*
 * 					tree of posts to display					    	*
 *      $indent		the indentation level of the current post	    	*
 *  																	*
 *  Returns:															*
 *      A string containing HTML representing the contents of the		*
 *      tree of blog posts.												*
 ************************************************************************/
function responses($blogid, $indent)
{
    global	$document_root;
    global	$lang;
    global	$userid;
    global	$months;
    global	$lmonths;
    global	$blogTemplate;
    global	$debug;
    global	$warn;

    $blog		        = new Blog(array('id'	=> $blogid));
    $matches		    = array();
    $username		    = $blog->get('username');
    $user		        = new User(array('userid'	=> $userid));
    $btemplate		    = new Template($blogTemplate);
    if ($username != $userid && !canUser('all'))
    {
		$btemplate->updateTag('buttonRow$blogid', null);
    }
    $btemplate->set('blogid',	$blogid);
    $btemplate->set('margin',	($indent + 6) . 'em');
    $btemplate->set('datetime',	$blog->get('datetime'));
    $btemplate->set('username',	$blog->get('username'));
    if ($debug)
        $btemplate->set('debug',		    'Y');
    else
        $btemplate->set('debug',		    'N');
    $subject		    = $blog->get('subject');
    if (strlen($subject) == 0)
		$subject	    = '*not supplied*';
    $btemplate->set('blogname',	$subject);
    $text		        = $blog->get('text');
    $btemplate->set('message',	$text);
    $datetime		    = $blog->get('datetime');
    if (preg_match('/^(\d+)-(\d+)-(\d+) (.*)$/', $datetime, $matches) == 1)
    {
        $btemplate->set('year',	    $matches[1]);
        $btemplate->set('month',	$months[$matches[2] - 0]);
        $btemplate->set('lmonth',	$lmonths[$matches[2] - 0]);
        $btemplate->set('day',	    $matches[3]);
        $btemplate->set('time', 	$matches[4]);
    }
    else
    {
        $btemplate->set('year', '');
        $btemplate->set('month', '');
        $btemplate->set('lmonth', '');
        $btemplate->set('day', '');
        $btemplate->set('time', $datetime);
    }
    $posts		= $btemplate->compile();
    $indent		+= 6;

    $blogParms		= array('keyvalue'	=> $blogid,
					'table'		=> 'Blogs');
    $bloglist		= new RecordSet('Blogs', $blogParms);
    $blogCount		= $bloglist->count();
    foreach($bloglist as $blog)
    {
		$blogid		= $blog->get('id');
		$posts		.= responses($blogid, $indent);
    }			// loop through responses

    return $posts;	// accumulated HTML string
}		// function responses

/************************************************************************
 *			  OOO  PPPP  EEEEE N   N    CCC   OOO  DDDD  EEEEE		    *
 *			 O   O P   P E     NN  N   C   C O   O D   D E				*
 *			 O   O PPPP  EEEE  N N N   C     O   O D   D EEEE		    *
 *			 O   O P     E     N  NN   C   C O   O D   D E				*
 *			  OOO  P     EEEEE N   N    CCC   OOO  DDDD  EEEEE		    *
 ************************************************************************/

// process input parameters
$blogid			    = 0;
$lang			    = 'en';
$table			    = 'Blogs';
$edit			    = false;

foreach($_GET as $key => $value)
{				// loop through all parameters
    $value		    = trim($value);
    switch(strtolower($key))
    {				// act on specific parameters
		case 'blogid':
		case 'id':
		{			// message being followed up
		    $matches		    = array();
		    if (is_string($value) && preg_match('/\d+/', $value, $matches) == 1)
		    {
		    	$blogid			= $matches[0];
		    }
		    else
 	        if (is_int($value) || ctype_digit($value))
 	        {
 		        $blogid			= $value;
 	        }
 	        else
 		        $msg	        .= "Invalid BlogID=$value. ";
 	    break;
		}			// message being followed up

		case 'table':
		{			// get the table name
 	        $table	    	= $value;
 	        break;
		}			// get the table name

		case 'lang':
		{
		    if (strlen($value) >= 2 && ctype_alpha($value))
 		        $lang		= strtolower(substr($value,0,2));
 	        break;
		}

		case 'edit':
		{
		    if (strtoupper($value) == 'Y')
 	        	$edit		= true;
 	        break;
		}

    }				// act on specific parameters
}			    	// loop through all parameters

// start the template
$template	    	= new FtTemplate("BlogPost$lang.html");

$tempBase           = $document_root . '/templates/';
if (file_exists($tempBase . "Trantab$lang.html"))
    $trtemplate = new Template("${tempBase}Trantab$lang.html");
else
    $trtemplate = new Template("${tempBase}Trantaben.html");

// internationalization support
$blogTemplate	    = $template->getElementById('blogTemplate')->innerHTML();

$monthsTag		    = $trtemplate->getElementById('Months');
if ($monthsTag)
{
    $months	    	= array();
    foreach($monthsTag->childNodes() as $span)
		$months[]	= trim($span->innerHTML());
}
$lmonthsTag		    = $trtemplate->getElementById('LMonths');
if ($lmonthsTag)
{
    $lmonths		= array();
    foreach($lmonthsTag->childNodes() as $span)
		$lmonths[]	= trim($span->innerHTML());
}

$user	            = new User(array('username'	=> $userid));
if ($user->isExisting())
{
    $template->updateTag('notLoggedOn', null);
    $template->set('EMAILCLASS',    'ina');
    $template->set('READONLY',      'readonly="readonly"');
}
else
{                       // not signed in
    $template->set('EMAILCLASS',    'white');
    $template->set('READONLY',      '');
}                       // not signed in
$template->set('EMAIL',			    $user->get('email'));
$template->set('CONTACTTABLE',		$table);
$template->set('CONTACTKEY',		$blogid);
$template->set('userid',		    $userid);
$template->set('blogid',		    $blogid);
$template->set('margin',		    '');
if ($debug)
    $template->set('debug',		    'Y');
else
    $template->set('debug',		    'N');

if ($blogid > 0)
{
    $blog		        = new Blog(array('id'		=> $blogid));
    $subject		    = $blog->get('subject');
    if (strlen($subject) == 0)
	    $subject	    = $blogid;
    $template->set('BLOGTITLE',	    $subject);

    if ($blog->isExisting())
    {			        // key of existing Blog record
		$parentblogid	        = $blog->get('keyvalue');
		$template->set('parentblogid',$parentblogid);
		if ($parentblogid == 0)
		    $template->updateTag('parentBlog', null);
		else
		    $template->updateTag('parentBlog',
			            		 array('parentblogid' => $parentblogid));

		if ($edit)
		{
		    $template->set('POSTS', 		'');
		    $template->set('posttext',		$blog->get('text'));
		    $userid				= $blog->get('username');
		    $user	            = new User(array('username'	=> $userid));
		    $template->set('EMAIL',		$user->get('email'));
		    $h1tag		        = $template->getElementById('EditBlog');
		    $template->set('TITLE',
			    	  str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
		    $template->updateTag('Response',	null);
		    $template->updateTag('NewPost',	null);
		}
		else
		{
		    $template->set('POSTS',	    	responses($blogid, 0));
		    $template->set('posttext',		'');
		    $h1tag		        = $template->getElementById('AddBlog');
		    $template->set('TITLE',
		    	    	  str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
		    $template->updateTag('Edit',	null);
		    $template->updateTag('NewPost',	null);
		}
    }			// key of existing Blog record
    else
    {			// new Blog record
		$template->set('POSTS',	    	'');
		$template->set('posttext',	    '');
		$template->set('parentblogid',	'');
		$h1tag		= $template->getElementById('AddBlog');
		$template->set('TITLE',
	    		       str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
		$template->updateTag('Edit',		null);
		$template->updateTag('Response',	null);
    }
}
else
{
    $template->set('POSTS', 			'');
    $template->set('posttext',			'');
    $template->set('BLOGTITLE',	        '');
    $template->updateTag('parentBlog',  null);
    $h1tag		= $template->getElementById('NewBlog');
    $template->set('TITLE',
    			   $h1tag->innerHTML());
    $template->updateTag('Edit',		null);
    $template->updateTag('Response',	null);
}

$template->display();
