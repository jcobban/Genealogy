<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  UserInfo.php														*
 *																		*
 *  When a user logs on this page is invoked to check to see if there	*
 *  are any outstanding messages for the user.  If there are they are	*
 *  displayed, otherwise the nominal index page is displayed.			*
 *																		*
 * History:																*
 *		2014/03/27		created											*
 *		2014/07/04		refresh invoking page							*
 *						provide quick links to latest info				*
 *						reminder about SPAM								*
 *		2014/07/18		newsletters and update reports moved			*
 *						automate selection of latest newsletter and		*
 *						latest software update report					*
 *		2014/07/27		include attributes of user in hidden input		*
 *						fields to make them visible to Javascript		*
 *		2014/08/22		report an error if the script is not invoked	*
 *						from Signon.php									*
 *		2014/08/23		setting of user information cookie moved to		*
 *						Signon.php										*
 *		2015/03/25		top page of hierarchy is now genealogy.php		*
 *		2015/05/26		use absolute URLs to referenced files on site	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/07		display trace information						*
 *		2015/12/30		fix conflict with autoload						*
 *		2016/01/19		add id to debug trace							*
 *		2017/09/12		use get( and set(								*
 *		2017/10/16		use class RecordSet								*
 *		2018/01/04		remove Template from template file names		*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ***********************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		    = 'en';
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {		    	// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {
    		case 'lang':
    		{
    		    if (strlen($value) >= 2)
    			    $lang		= strtolower(substr($value,0,2));
    		    break;
            }		// language
        }           // act on specific parameter
    }			    // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL 
else
if (count($_POST) > 0)
{	        	    // invoked by post
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {		    	// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {
    		case 'lang':
    		{
    		    if (strlen($value) >= 2)
    			    $lang		= strtolower(substr($value,0,2));
    		    break;
    		}		// language
        }           // act on specific parameter
    }			    // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by post 

$monthnames	= array('',
					'January','February','March','April',
					'May','June','July','August',
					'September','October','November','December');

// show any blog postings
if (strlen($userid) > 0)
{
	$user		= new User(array('username'	=> $userid));

	$blogParms	= array('keyvalue'	=> $user->get('id'),
						'table'		=> 'Users');
	$bloglist	= new RecordSet('Blogs', $blogParms);
	$blogCount	= $bloglist->count();
}
else
	$msg	.= "This script must be invoked from the Signon dialog. ";

$template		= new FtTemplate("UserInfo$lang.html");

if (strlen($msg) == 0)
{
	// create list of newsletters
	$newsletters	= array();
	$dh             = opendir('Newsletters');
	if ($dh)
	{		// found Newsletters directory
	    while (($filename = readdir($dh)) !== false)
	    {		// loop through files
			if (strlen($filename) > 4 &&
			    substr($filename, strlen($filename) - 4) == '.pdf')
			    $newsletters[]	= $filename;
	    }		// loop through files
	    rsort($newsletters);
	}		// found Newsletters directory

	$filename	= $newsletters[0];
	$y	    	= substr($filename,10,4);
	$m		    = substr($filename,15,2);
	$month		= $monthnames[$m - 0];
	$template->updateTag('newsletter$i',
					     array( 'i'		    => 0,
						        'filename'	=> $filename,
						        'month'	    => $month,
						        'y'		    => $y));

	$reports	= array();
	$dh	= opendir('MonthlyUpdates');
	if ($dh)
	{		// found Newsletters directory
	    while (($filename = readdir($dh)) !== false)
	    {		// loop through files
			if (strlen($filename) > 4 &&
			    substr($filename, strlen($filename) - 4) == '.pdf')
			    $reports[]	= $filename;
	    }		// loop through files
	    rsort($reports);
	}		// found Newsletters directory

	$filename	= $reports[0];
	$y	    	= substr($filename,6,4);
	$m		    = substr($filename,11,2);
	$month		= $monthnames[$m - 0];
	$template->updateTag('MonthlyUpdates$i',
					     array('i'		    => 0,
					    	   'filename'	=> $filename,
					    	   'month'	    => $month,
					    	   'y'		    => $y));

	// display existing blog entries
	$template->updateTag('blog$blid',
					     $bloglist);
}

$template->display();
