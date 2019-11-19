<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Account.php															*
 *																		*
 *  This script provides a common interface for account administration	*
 *  for an authorized user of the web site.								*
 *																		*
 *  History:															*
 *		2010/10/30		Created											*
 *		2010/11/17		Do not apply changes if errors detected			*
 *		2010/12/22		correct URL of genealogy page in header &		*
 *						trailer											*
 *		2011/02/14		validate new e-mail address						*
 *		2011/03/10		redirect to signon if not already logged on		*
 *		2011/04/28		use CSS rather than tables for layout of header	*
 *						and trailer										*
 *		2012/01/05		use id rather than name for buttons to avoid	*
 *						passing them to the action script in IE			*
 *		2012/05/28		add explicit class to <input type='text'>		*
 *						cleanup parameter validation					*
 *						remove top and bottom headers					*
 *		2012/06/28		add contact "button"							*
 *		2013/01/20		undefined variable $qemail						*
 *		2013/09/11		switch to using SHA512 password hash			*
 *						add option to suppress sending e-mails			*
 *		2013/11/16		handle lack of database server connection		*
 *						gracefully										*
 *		2013/12/05		support parameter debug							*
 *		2013/12/10		use CSS for layout								*
 *						fix bug in displaying e-mail address			*
 *		2013/12/18		add id attributes to input elements				*
 *		2014/03/27		use class User to access Users table			*
 *		2014/06/15		support for popupAlert moved to common code		*
 *		2014/07/25		add support for suppressing help popups			*
 *						moved to top level of public_html				*
 *		2014/08/11		NoHelp parameter set email flag by mistake		*
 *						add display of $_POST to debug output			*
 *						pass debug flag with submit						*
 *						it was possible to turn the email and nohelp	*
 *						options on, but not to turn them off			*
 *		2014/08/22		add support for deleting user cookie			*
 *		2014/08/22		user cookie removed entirely					*
 *		2014/08/29		display blog messages							*
 *		2014/09/15		add http.js to header							*
 *		2015/05/11		use ContactAuthor.php to contact administrator	*
 *		2015/06/30		correct validation of password change			*
 *						restore page top and bottom						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/01		change password was broken						*
 *		2016/01/19		add id to debug trace							*
 *		2017/09/12		use get( and set(								*
 *		2017/10/16		use class RecordSet								*
 *		2017/11/15		use class Template								*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/25		common functionality moved to class FtTemplate	*
 *		2018/05/28		include specific CSS							*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$okmsg	            = '';		// positive notices
$lang		        = 'en';
$newPassword	    = '';
$newPassword2	    = '';
$password		    = '';
$email		        = '';
$useEmail		    = false;
$nohelp		        = false;

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
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
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'userid':
			{
			    if ($value != $userid)
			      $msg .= 'Attempt to bypass security by changing userid. ';
			    break;
			}	    // userid
	
			case 'password':
			{
			    $password	= trim($value);
			    break;
			}	    // password
	
			case 'newpassword':
			{
			    $newPassword	= trim($value);
			    if (strlen($newPassword) > 0)
			    {		// request to change password
					if ($user)
					{
					    if ($debug)
						    $warn	.= "<p>Password set to '$password'</p>";
					    $user->set('password', null);
					    $user->set('shapassword',
							    hash('sha512', $newPassword));
					}
			    }		// request to change password
			    break;
			}	    // new password
	
			case 'newpassword2':
			{
			    $newPassword2	= trim($value);
			    break;
			}	    // new password repeat
	
			case 'email':
			{		// request to change email address
			    $email	        = trim($value);
			    if (strpos($email, "'") !== false)
				    $msg	    .= "Invalid email address. ";
			    else
			    if (strlen($email) > 0 && $email != $oldemail)
			    {		// valid e-mail address
					// check for an existing userid with the desired
					// email address
					try {
					    $user2	= new User(array('email' => $email));
					    $msg	.= "Requested e-mail address '$email' is already in use. ";
					} catch(Exception $e) {
					    // email address not in use by any other account
					    $user->set('email', $email);
					}
			    }		// valid e-mail address
			    break;
			}		// request to change email address
	
			case 'usemail':
			{		// request to enable e-mail
			    $useEmail	    = true;
			    break;
			}		// request to enable e-mail
	
			case 'nohelp':
			{		// request to suppress popup help
			    $nohelp	        = true;
			    break;
            }		// request to suppress popup help

			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
	    }		    // act on specific parameter
    }	            // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account

if (strlen($userid) > 0 && strlen($msg) == 0)
{			        // signed on	
    // get existing account details
    $user		    = new User(array("username" => $userid));
    $oldemail		= $user->get('email');	// old email
    $oldpasswd		= $user->get('password');// MD5 of password
    $oldshapasswd	= $user->get('shapassword');// SHA of password
    $oldoptions		= $user->get('options');// user options

    $blogParms		= array('keyvalue'	=> $user->get('id'),
                            'table'		=> 'Users',
                            'order'     => 'BL_Index DESC');
    $bloglist		= new RecordSet('Blogs', $blogParms);
    $blogCount		= $bloglist->count();
}			        // signed on
else
{			        // redirect to signon
	header('Location: Signon.php?lang=' . $lang);
	exit;
}			        // redirect to signon

if (count($_POST) > 0)
{		            // invoked by submit to update account
	if ($oldpasswd && md5($password) != $oldpasswd)
	{		        // MD5 password doesn't match
	     $msg	.=
	            "Password must match the current password on the account. ";
	}		        // MD5 password doesn't match
	else
	if (hash('sha512',$password) != $oldshapasswd)
	{
        $msg	.=
                "Password must match the current password on the account. ";
    }

	// apply changed options
	$options	    = $user->get('options');
	$options	    &=  ~User::OPT_NOHELP_ON & ~User::OPT_USEMAIL_ON;
	if ($useEmail)
	    $options	= $options | User::OPT_USEMAIL_ON;
	if ($nohelp)
	    $options	= $options | User::OPT_NOHELP_ON;
	$user->set('options', $options);

	// validate combinations of parameters
	if ($password == '')
	{		        // password omitted
	    $msg	.= "Password must be specified to change
				account settings.";
	}		        // password omitted

	if (strlen($newPassword) > 0 && $newPassword != $newPassword2)
	{		        // new password validation failed
	    $msg	.= "To change the password on the account you must
			    supply the new password twice.  ";
	}		        // new password validation failed
	
	if (strlen($msg) == 0)
	{		        // apply changes
	    if ($debug)
	    {
		    $user->save("p");
	    }
	    else
		    $user->save(false);
	    $okmsg	= 'Account updated.';
	}		        // apply changes
}		            // invoked by submit from previous invocation


// set checkboxes according to options from database
if ($oldoptions & User::OPT_USEMAIL_ON)
	$chkusemail		= "checked='checked'";
else
	$chkusemail		= "";
if ($oldoptions & User::OPT_NOHELP_ON)
	$chknohelp		= "checked='checked'";
else
	$chknohelp		= "";

// construct update of database
$newPassword	    = '';
$newPassword2	    = '';
$password		    = '';
$email		        = $oldemail;
$useEmail		    = false;
$nohelp		        = false;

// create instance of Template
$title		    	= 'Account Management';
$template		    = new FtTemplate("Account$lang.html", true);

// define substitution values
$template->set('TITLE',		    $title);
$template->set('USERID',		$userid);
$template->set('EMAIL',		    $email);
$template->set('LANG',		    $lang);
$template->updateTag('otherStylesheets',	
		             array('filename'   => '/Account'));
$template->set('chkusemail',	$chkusemail);
$template->set('chknohelp',		$chknohelp);

if (strlen($okmsg) > 0)
	$template->updateTag('update', null);
else
	$template->updateTag('okmsg', null);
// display existing blog entries
$template->updateTag('blog$blid',
				 $bloglist);
$template->display();
