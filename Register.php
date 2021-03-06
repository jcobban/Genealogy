<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Register.php														*
 *																		*
 *  This script provides a common interface for registering as an		*
 *  authorized user of the web site.									*
 *																		*
 *  Parameters (passed by POST):										*
 *		userid			new userid requested by user					*
 *		password		new password supplied by user					*
 *		password2		new password supplied by user					*
 *																		*
 *  History:															*
 *		2010/08/22		Created											*
 *		2010/11/16		Remove no longer necessary import of db.inc		*
 *		2011/02/12		Improve separation of HTML, Javascript, and PHP	*
 *						Remove restrictions on userid that are not		*
 *						documented										*
 *						Improve validation.								*
 *		2011/04/28		use CSS rather than tables for layout of header	*
 *						and trailer										*
 *		2012/04/17		remove top and bottom divisions of page			*
 *		2012/06/22		error in validation								*
 *		2012/06/23		add contact "button"							*
 *		2013/03/06		make validation of userids match with Signon.php*
 *		2013/09/11		add support for SHA-512 password hash			*
 *						add support for suppressing e-mails				*
 *						correct validation bugs							*
 *						do not reprompt user after successful			*
 *						registration									*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/12/05		do not initialize $msg and generate debug output*
 *		2013/12/10		use CSS for layout								*
 *		2013/12/18		add for attribute to <label> tags				*
 *						add id attribute on input tags					*
 *		2014/03/27		use class User to access table of Users			*
 *		2014/07/18		allow e-mail address as userid					*
 *		2014/07/25		add support for suppressing help popups			*
 *		2014/08/01		send e-mail on registration						*
 *						pass debug flag to subsequent invocations		*
 *						use User('ousername' => new name)				*
 *		2014/08/06		call dialogBot to setup for popAlert			*
 *						encode HTML special chars in messages			*
 *		2014/08/11		display $_POST in debug mode					*
 *						set e-mail flag to checked on first entry		*
 *		2015/05/11		use ContactAuthor.php to contact administrator	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/31		display password score as user types			*
 *		2015/12/30		fix conflict with autoload						*
 *		2016/01/19		debug trace was not shown						*
 *		2016/02/02		User construct ousername removed				*
 *		2017/08/31		replace obsolete eregi call						*
 *		2017/09/12		use get( and set(								*
 *		2018/02/05		use template									*
 *		2018/05/28		include specific CSS							*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/07/09      title was not set in all cases                  *
 *      2020/12/03      correct XSS vulnerability                       *
 *      2021/05/31      pass confirmid to send Email                    *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

// parameters
$lang			            = 'en';
$uidPattern		            = '#^[^<>@&]{6,63}$#';
$newuserid					= '';
$user			            = null;     // instance of User
$email						= '';
$password					= '';
$password2					= '';
$auth                       = 'pending';
$noHelp			        	= false;
$chknohelp		        	= "";

if (isset($_POST) && count($_POST) > 0)
{			    // invoked by POST
    $useEmail	    	    = false;
    $chkusemail		        = "";
    $parmsText              = "<p class='label'>\$_POST</p>\n" .
			                   "<table class='summary'>\n" .
			                      "<tr><th class='colhead'>key</th>" .
			                        "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {			// loop through parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                htmlspecialchars($value) . "</td></tr>\n"; 

	    switch(strtolower($key))
	    {		// act on specific parameters
			case'userid':
			{
			    $newuserid	    = $value;
			    break;
			}

			case'password':
			{
			    $password	    = $value;
			    break;
			}

			case'password2':
			{
			    $password2	    = $value;
			    break;
			}

			case 'email':
			{
			    $email		    = $value;
			    break;
			}

			case 'usemail':
			{
			    $useEmail		= true;
			    $chkusemail		= "checked='checked'";
			    break;
			}

			case 'nohelp':
			{
			    $noHelp		    = true;
			    $chknohelp		= "checked='checked'";
			    break;
			}

            case 'auth':
            {
                if (strlen($value) > 0)
                    $auth       = $value;
                break;
            }

			case 'lang':
	        {
	            $lang           = FtTemplate::validateLang($value);
			    break;
			}

	    }		// act on specific parameters
    }			// loop through parameters

    if (strlen($newuserid) == 0 && strlen($email) > 0)
        $newuserid              = str_replace(array('@','.'), 
                                              array('_','_'), 
                                              $email);
    if (strlen($password) == 0)
    {
        if (strlen($password2) == 0)
        {
            $password           = "\000$newuserid"; // hard to enter 
            $password2          = "\000$newuserid"; // hard to enter
        }
    }
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}
else
if (isset($_GET))
{			    // invoked by GET
    $useEmail		        = true;
    $chkusemail		        = "checked='checked'";

    $parmsText              = "<p class='label'>\$_GET</p>\n" .
			                   "<table class='summary'>\n" .
			                      "<tr><th class='colhead'>key</th>" .
			                        "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                htmlspecialchars($value) . "</td></tr>\n"; 
        switch(strtolower($key))
        {		// act on specific parameters
			case 'lang':
	        {
	            $lang       = FtTemplate::validateLang($value);
			    break;
			}

        }		// act on specific parameters
    }           // loop through parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}			    // invoked by GET

$template		            = new FtTemplate("Register$lang.html", true);
$template->set('CHKUSEMAIL',	$chkusemail);
$template->set('CHKNOHELP',	    $chknohelp);
$template->set('NEWUSERID',	    $newuserid);
$template->set('EMAIL',		    $email);
$template['otherStylesheets']->update(array('filename'	=> '/Register'));

// turn off all messages and then individually re-enable
$messages                       = array();
$messages['shortUsername']		= null;
$messages['badUsername']		= null;
$messages['shortPassword']		= null;
$messages['passwordMismatch']	= null;
$messages['badEmail']		    = null;
$messages['initialPrompt']		= null;
$messages['emailInUse']		    = null;
$messages['useridInUse']		= null;

// validate parameters
if (strlen($newuserid) > 0 ||
    (strlen($password) > 0 && strlen($password2) > 0))
{		                // new registration supplied
    if (strlen($newuserid) < 6)
		$messages['shortUsername']	= array('newuserid'  => $newuserid);
    if (!preg_match($uidPattern, $newuserid))
		$messages['badUsername']	= array('newuserid'  => $newuserid);
    if (strlen($password) < 6)
		$messages['shortPassword']	= array('newuserid'	=> $newuserid,
							                'password'	=> $password);

    if ($password != $password2)
		$messages['passwordMismatch']	= array('newuserid'	=> $newuserid,
							                    'password'	=> $password,
							                    'password2'	=> $password2);

    if (strlen($email) == 0 && strpos($newuserid, '@') !== false)
		$email		= $newuserid;

    if (strlen($email) < 6 || strpos($email, '@') === false)
    {
		$messages['badEmail']	    = array('newuserid'	=> $newuserid,
						    	            'email'      => $email);
        $template->set('TITLE', $template['titleNew']->innerHTML);
    }
    else
    {			        // validate e-mail
		$user		    = new User(array('email'    => $email));
		if ($user->isExisting())
		{
		    $olduser	= $user->get('username');
		    if ($newuserid != $olduser)
		    {
				$messages['emailInUse']	= array('newuserid'	=> $newuserid,
								                'email'	    => $email);
                $user	= null;
                $template->set('TITLE', $template['titleAlready']->innerHTML);
            }
            else
            {           // OK
                $template->set('TITLE', $template['titleComplete']->innerHTML);
            }           // OK
		}               // email defined
		else
		{			    // check for userid already in use
		    $user	    = new User(array('username' => $newuserid));
            if ($user->isExisting())
            {
				$messages['useridInUse']	= array('newuserid' => $newuserid);
                $template->set('TITLE', $template['titleAlready']->innerHTML);
            }
            else
            {           // OK
                $template->set('TITLE', $template['titleComplete']->innerHTML);
            }           // OK
		}			    // check for userid already in use
    }			        // validate e-mail
}		                // registration supplied
else
{		                // registration not supplied
    $messages['initialPrompt']	            = array('newuserid' => $newuserid);
    $template->set('TITLE', $template['titleNew']->innerHTML);
}		                // registration not supplied

// if there are no errors in validation, create the new account
if ($user)
{		                // create new account
    if (!$user->isExisting())
    {		            // user does not already exist
		$user->set('password',	$password);
		$user->set('email',	    $email);
		$user->set('auth',	    $auth);
		if ($useEmail)
		    $user->set('usemail', 1);
		else
		    $user->set('usemail', 0);
		$user->save();
		$id		        = $user->get('id');
		$confirmid	    = $user->get('confirmid');

		$subjectTag	    = $template->getElementById('emailSubject');
		$subject	    = str_replace('$newuserid',
						    	      $newuserid,
							          trim($subjectTag->innerHTML()));
		$bodyTag    	= $template->getElementById('emailBody');
		$body		    = str_replace(array('$newuserid','$servername','$id','$confirmid'),
						    		  array($newuserid,$servername,$id,$confirmid),
							    	  trim($bodyTag->innerHTML()));
		// send e-mail to the new user to validate the address
		$sent		    = mail($email,
		 	        	       $subject,
		 		               $body);

		$template['okmsgRespond']->update(array('newuserid'	=> $newuserid,
						    	                'email'	=> $email));
		$template['okmsgAlready']->update( null);
		$template['titleNew']->update( null);
		$template['titleAlready']->update( null);
		$template['submit']->update( null);
		$template['passRow']->update( null);
		$template['pass2Row']->update( null);
    }
    else
    {
		$template['okmsgAlready']->update(array('newuserid'	=> $newuserid,
							                    'email'	    => $email));
		$template['okmsgRespond']->update( null);
		$template['titleNew']->update( null);
		$template['titleComplete']->update( null);
    }

}		            // create new account
else
{		            // first entry with no parameters
    $template['titleComplete']->update( null);
    $template['titleAlready']->update( null);
    $template['okmsgRespond']->update( null);
    $template['okmsgAlready']->update( null);
    $messages['initialPrompt']	        = array('newuserid'	=> '',
							                    'email'	    => '');
}		            // first entry with no parameters

$template['shortUsername']->update($messages['shortUsername']);
$template['badUsername']->update($messages['badUsername']);
$template['shortPassword']->update($messages['shortPassword']);
$template['passwordMismatch']->update($messages['passwordMismatch']);
$template['badEmail']->update($messages['badEmail']);
$template['initialPrompt']->update($messages['initialPrompt']);
$template['emailInUse']->update($messages['emailInUse']);
$template['useridInUse']->update($messages['useridInUse']);

$template->display();
