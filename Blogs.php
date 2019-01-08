<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Blogs.php															*
 *																		*
 *  This script provides a common interface for account administration	*
 *  for an authorized user of the web site.								*
 *																		*
 *  History:															*
 *		2018/09/15		Created											*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// common
$lang	    	= 'en';
$okmsg	        = '';		// positive notices
foreach($_REQUEST as $key => $value)
{
    switch(strtolower($key))
    {
        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
            break;
        }
    }
}

// get top level Blog posts
$blogParms		    = array('keyvalue'	=> 0,
							'table'		=> 'Blogs');
$bloglist		    = new RecordSet('Blogs', $blogParms);
$blogCount		    = $bloglist->count();

$title	    	    = 'Blog Management';
$tempBase		    = $document_root . '/templates/';
$template		    = new FtTemplate("${tempBase}page$lang.html");
$includeSub		    = "Blogs$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub     = 'Blogsen.html';
}
$template->includeSub($tempBase . $includeSub,
					  'MAIN');
if (file_exists($tempBase . "Trantab$lang.html"))
    $trtemplate = new Template("${tempBase}Trantab$lang.html");
else
    $trtemplate = new Template("${tempBase}Trantaben.html");

// internationalization support
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

foreach($bloglist as $blog)
{
	$datetime	= $blog->get('datetime');
	$matches	= array();
	if (preg_match('/^(\d+)-(\d+)-(\d+) *(.*)$/', $datetime, $matches) == 1)
	{
	    $blog->set('year',		$matches[1]);
	    $blog->set('month',		$months[$matches[2] - 0]);
	    $blog->set('lmonth',	$lmonths[$matches[2] - 0]);
	    $blog->set('day',		$matches[3]);
	    $blog->set('time',		$matches[4]);
	}
	else
	{
	    $blog->set('year',		'');
	    $blog->set('month',		'');
	    $blog->set('time',		'');
	    $blog->set('lmonth',	'');
	    $blog->set('time',		$datetime);
	}
}

$template->set('TITLE',				$title);
$template->set('USERID',			$userid);
$template->set('LANG',				$lang);
$template->set('CONTACTTABLE',		'Blogs');
$template->set('CONTACTKEYVALUE',	0);

// display existing blog entries
$template->updateTag('blog$blid',
						 $bloglist);
$template->display();
