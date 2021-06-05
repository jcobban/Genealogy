<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  resetUserPasswordJson.php											*
 *																		*
 *  This script resets the user's password and sends an e-mail to the	*
 *  user with the new password.											*
 *																		*
 *  History:															*
 *		2019/04/12		Created											*
 *		2020/12/03      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/User.inc';
    require_once __NAMESPACE__ . '/FtTemplate.inc';
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  randomPassword														*
 *																		*
 *  Generate a random password.											*
 *  The selection of characters excludes the letters I and O, 			*
 *  lower case 'l', and the digits 1 and 0 to avoid misinterpretation.	*
 *																		*
 *  Input:																*
 *		len		number of characters in the resulting password			*
 ************************************************************************/
function randomPassword($len)
{
    $passwordAlphabet	=	// note it omits I, O, l, 0, and 1 
				"ABCDEFGHJKLMNPQRSTUVWXYZ" .
				"abcdefghjkmnpqrstuvwxyz" .
				"23456789" .
				"!_-+*.^$#~%";
    $newPassword	= '';
    for ($i = 0; $i < $len; $i++)
    {
		$index		= rand(0 , strlen($passwordAlphabet) - 1);
		 substr($passwordAlphabet, $index, 1) . "</p>";
		$newPassword	.= substr($passwordAlphabet, $index, 1);
    }
    return $newPassword;
}		// randomPassword

// the user is not signed on so we must act on the userid or e-mail
// supplied by the user
$user			= null;		// instance of User
$username		= null;		// name
$email			= null;		// e-mail address
$lang			= 'en';

foreach($_REQUEST as $key => $value)
{	        // loop through all parameters
    if ($debug)
        $warn	.= "<p>\$_REQUEST['$key']='" . 
                    htmlspecialchars($value) . "'</p>\n";
    switch(strtolower($key))
    {		// act on specific parameter
		case 'uid':
		case 'userid':
		case 'username':
		{
		    if (strlen($value) > 0)
		    {			// userid supplied
				$username		= trim($value);
				// get existing account details
				$user           = new User(array("username" => $username));
				if ($user->isExisting())
				    $email	    = $user->get('email');
				else
				{
				    $msg	    .=
                        "Unable to find account record for user '" .
                        htmlspecialchars($username) . "'. ";
				    $user	= null;
				}
		    }			// userid supplied
		    break;
		}	// username

		case 'email':
		{
		    if (strlen($value) > 0)
		    {			// email supplied
				$email		    = trim($value);
				// get existing account details
				$user		    = new User(array("email" => $email));
				if ($user->isExisting())
				    $username	= $user->get('userid');
				else
				    $msg	    .=
                            "Unable to find account record for address '" .
                            htmlspecialchars($email) . "'. ";
		    }			// email supplied
		    break;
		}	// email

		case 'lang':
		{
			$lang		    = FtTemplate::validateLang($value);
		    break;
		}

		case 'validate':
		{
		    $code	            = $value;
		    if (is_null($user))
				$msg	        .= "Missing valid user identification. ";
		    else
		    if ($code != $user->get('shapassword'))
				$msg	        .= "Invalid authorization code. ";
		    break;
		}
    }		// act on specific parameter
}	        // loop through all parameters

$template		    = new Template("resetUserPassword$lang.json");

$template->set('USERID',	$username);
$template->set('EMAIL',		$email);
$template->set('LANG',		$lang);

if ($user)
{			// valid user
    $newPassword	= randomPassword(10);
    $user->set('password', null);
    $user->set('shapassword', hash('sha512', $newPassword));
    $user->save();

    // bcc the e-mail to the administrators
    $getparms		= array('auth'	=> 'all');
    $admins		    = new RecordSet('Users', $getparms);
    $bcc		    = 'BCC: ';
    $comma		    = '';
    foreach($admins as $id => $admin)
    {			// loop through administrators
		$bcc		.= $comma . $admin->get('email'); 
		$comma		= ',';
    }			// loop through administrators

    $headers		= "MIME-Version: 1.0" . "\r\n" .
					  "Content-type:text/html;charset=UTF-8" . "\r\n" .
					  'From: <webmaster@jamescobban.net>' . "\r\n";
    $subjectTag		= $template->getElementById('emailsubject');
    $emailSubject	= str_replace('username', 
						          $username,
						          trim($subjectTag->innerHTML()));
    $bodyTag		= $template->getElementById('emailbody');
    $emailBody		= str_replace(array('$username','$newpassword'),
						          array($username, $newPassword),
						          trim($bodyTag->innerHTML()));
    $sent		    = mail(	$email,
					    	$emailSubject,
					    	$emailBody,
					    	$headers);
}			// reset the password
else
{			// missing parameters
    $template->updateTag('passwordreset', null);
}			// missing parameters
$template->display();
