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
 *		2018/08/10		Created											*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

    // common
    $okmsg	= '';		// positive notices
    if (array_key_exists('lang', $_REQUEST))
		$lang		= $_REQUEST['lang'];
    else
		$lang		= 'en';

    if (strlen($userid) > 0 && strlen($msg) == 0)
    {			// signed on
		try {
		    // get existing account details
		    $user		= new User(array("username" => $userid));
		    $oldemail		= $user->get('email');	// old email
		    $oldpasswd		= $user->get('password');// MD5 of password
		    $oldshapasswd	= $user->get('shapassword');// SHA of password
		    $oldoptions		= $user->get('options');// user options

		    $blogParms		= array('keyvalue'	=> $user->get('id'),
								'table'		=> 'Users');
		    $bloglist		= new RecordSet('Blogs', $blogParms);
		    $blogCount		= $bloglist->count();
		} catch(Exception $e) {
		    $msg		= "Account: Internal system error. " .
						"Unable to find account record for current user. ";
		    $user		= null;
		    $oldoptions		= User::OPT_USEMAIL_ON;
		}
    }			// signed on
    else
    {			// redirect to signon
		header('Location: Signon.php?lang=' . $lang);
		exit;
    }			// redirect to signon

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
    $newPassword	= '';
    $newPassword2	= '';
    $password		= '';
    $email		= $oldemail;
    $useEmail		= false;
    $nohelp		= false;

    if (count($_POST) > 0)
    {		// invoked by submit to update account
		foreach($_POST as $key => $value)
		{	// loop through all parameters
		    if ($debug)
				$warn	.=  "<p>\$_POST['$key']='$value'</p>\n";
		    switch(strtolower($key))
		    {		// act on specific parameter
				case 'userid':
				{
				    if ($value != $userid)
				      $msg .= 'Attempt to bypass security by changing userid. ';
				    break;
				}	// userid

				case 'password':
				{
				    $password	= trim($value);
				    if ($oldpasswd && md5($password) != $oldpasswd)
				    {		// MD5 password doesn't match
						$msg	.= "Password must match the current password
							on the account. ";
				    }		// MD5 password doesn't match
				    else
				    if (hash('sha512',$password) != $oldshapasswd)
				    {
						$msg	.= "Password must match the current password
							on the account. ";
				    }
				    break;
				}	// password

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
				}	// new password

				case 'newpassword2':
				{
				    $newPassword2	= trim($value);
				    break;
				}	// new password repeat

				case 'email':
				{		// request to change email address
				    $email	= trim($value);
				    if (strpos($email, "'") !== false)
						$msg	.= "Invalid email address. ";
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
				    $useEmail	= true;
				    break;
				}		// request to enable e-mail

				case 'nohelp':
				{		// request to suppress popup help
				    $nohelp	= true;
				    break;
				}		// request to suppress popup help
		    }		// act on specific parameter
		}	// loop through all parameters

		// apply changed options
		$options	= $user->get('options');
		$options	&=  ~User::OPT_NOHELP_ON & ~User::OPT_USEMAIL_ON;
		if ($useEmail)
		    $options	= $options | User::OPT_USEMAIL_ON;
		if ($nohelp)
		    $options	= $options | User::OPT_NOHELP_ON;
		$user->set('options', $options);

		// validate combinations of parameters
		if ($password == '')
		{		// password omitted
		    $msg	.= "Password must be specified to change
							account settings.";
		}		// password omitted

		if (strlen($newPassword) > 0 && $newPassword != $newPassword2)
		{		// new password validation failed
		    $msg	.= "To change the password on the account you must
						    supply the new password twice.  ";
		}		// new password validation failed

		if (strlen($msg) == 0)
		{		// apply changes
		    if ($debug)
		    {
				$user->save("p");
		    }
		    else
				$user->save(false);
		    $okmsg	= 'Account updated.';
		}		// apply changes
    }		// invoked by submit from previous invocation

    $coffBase		= $document_root . '/CoffeeShop/';
    $template		= new FtTemplate("Account$lang.html", true);

    $template->set('USERID',		$userid);
    $template->set('EMAIL',		    $email);
    $template->set('LANG',		    $lang);
    $template->updateTag('otherStylesheets',
    		  		array('filename'	=> '/CoffeeShop/Account'));
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
