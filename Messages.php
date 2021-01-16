<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Messages.php														*
 *																		*
 *  This script displays any messages sent to the current user.         *
 *																		*
 *  History:															*
 *		2020/10/28		Created from Account.php						*
 *		2020/12/03      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$okmsg	            		= '';		// positive notices
$lang		        		= 'en';
$newPassword	    		= '';
$newPassword2	    		= '';
$password		    		= '';
$email		        		= '';
$useEmail		    		= null;
$nohelp		                = null;

// if invoked by method=get process the parameters
if (isset($_GET) && count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
			                   "<table class='summary'>\n" .
			                      "<tr><th class='colhead'>key</th>" .
			                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                htmlspecialchars($value) . "</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
        }
    }
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

// this script can only be invoked for a user who is signed in
if (strlen($userid) == 0)
{			        // redirect to signon
	header('Location: Signon.php?lang=' . $lang);
	exit;
}			        // redirect to signon

$template		    = new FtTemplate("Messages$lang.html");

$user		        = new User(array("username" => $userid));

// get existing account details
$blogParms		    = array('keyvalue'	=> $user->get('id'),
                            'table'		=> 'Users',
                            'order'     => 'BL_Index DESC');
$bloglist		    = new RecordSet('Blogs', $blogParms);
$blogCount		    = $bloglist->count();

// define substitution values
$template->set('USERID',		$userid);
$template->set('LANG',		    $lang);

// display existing blog entries
if ($blogCount > 0)
    $template->updateTag('blog$blid',
                         $bloglist);
else
{
    $template['messages']->update(null);
    $template['blogform']->update(null);
}
$template->display();
