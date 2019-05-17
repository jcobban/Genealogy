<?php
namespace CoffeeShop;
use \PDO;
use \Exception;
use \Genealogy/Blog;
use \Genealogy/User;
use \Genealogy/Language;

/************************************************************************
 *  Signon.php															*
 *																		*
 *  This script provides a common interface for signing on as an		*
 *  authorized user of the web site.									*
 *																		*
 *  Parameters (passed by POST):										*
 *		userid			new userid requested by user					*
 *		password		new password supplied by user					*
 *		act				action signalled from previous invocation		*
 *																		*
 *  History:															*
 *		2019/01/01      created                                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once 'Genealogy/User.inc';
require_once 'Genealogy/Language.inc';
require_once __NAMESPACE__ . '/CsTemplate.inc';
// the following obtains $userid and $authorized from the cookie
require_once __NAMESPACE__ . "/common.inc";

    // validate parameters
    $newuserid		= '';
    $password		= '';
    $action		    = '';
    $rememberme		= '';
    $lang		    = 'en';
    $signedoff		= 1;
    $specify		= 2;
    $incorrect		= 4;
    $messages		= 0;	// bit mask

    foreach($_COOKIE as $key => $value)
    {			// loop through all parameters
	if ($debug)
	    $warn	.= "<p>\$_COOKIE['$key']='$value'</p>\n";
	switch($key)
	{		// act on specific parameter
	    case 'rememberme':
	    {
		$rememberme	= $value;
		break;
	    }

	    case 'lang':
	    {
		$lang		= strtolower(substr($value,0,2));
		break;
	    }
	}		// act on specific cookie
    }			// loop through all cookies

    // check for new signon
    foreach($_POST as $key => $value)
    {			// loop through all parameters
	if ($debug)
	    $warn	.= "<p>\$_POST['$key']='$value'</p>\n";
	switch($key)
	{		// act on specific parameter
	    case 'userid':
	    {
		$newuserid	= trim($value);
		break;
	    }

	    case 'password':
	    {
		$password		= trim($value);
		break;
	    }

	    case 'act':
	    {
		$action		= trim($value);
		if ($action == 'logoff')
		{
		    $userid		= $newuserid;
		    $authorized		= '';
		    unset($_COOKIE['rememberme']);
		    setcookie('rememberme', '', time() - 3600, '/');
		    $rememberme		= '';
		    setcookie('user', '', time() - 3600, '/');
		    unset($_SESSION['userid']);
		    $messages		|= $signedoff;
		}
		break;
	    }		// action to take

	    case 'lang':
	    {
		$lang		= strtolower(substr($value,0,2));
		break;
	    }

	}		// act on specific parameter
    }			// loop through all parameters

    // check for explicit redirection to another page on signon
    $redirectto		= "UserInfo.php";
    foreach($_GET as $key => $value)
    {
	if ($debug)
	    $warn	.= "<p>\$_GET['$key']='$value'</p>\n";
	switch(strtolower($key))
	{
	    case 'redirect':
	    {
		$redirectto	= $value;
		break;
	    }

	    case 'lang':
	    {
		$lang		= strtolower(substr($value,0,2));
		break;
	    }

	}		// act on specific parameters
    }			// loop through parameters

    // check for a user specified memory of the userid and password
    if ($action != 'logoff' &&
	strlen($newuserid) == 0 && strlen($password) == 0)
    {			// use memorized userid and password
	$parts		= explode('&', $rememberme);
	foreach($parts as $i => $part)
	{		// process each part
	    $o	= strpos($part, ':');
	    $name	= rawurldecode(substr($part, 0, $o));
	    $value	= rawurldecode(substr($part, $o+1));
	    if ($debug)
		$warn	.= "<p>rememberme.$name='$value'</p>\n";
	    switch(strtolower($name))
	    {		// act on specific parameter name
		case 'username':
		{
		    $newuserid	= $value;
		    break;
		}

		case 'password':
		{
		    $password	= $value;
		    break;
		}

	    }		// act on specific parameter name
	}		// process each part
    }			// use memorized userid and password

    // if currently signed on, should not enter this script
    if (strlen($userid) > 0 && strlen($authorized) > 0 &&
	strlen($newuserid) == 0)
    {		// already signed in and not specifying a new login
	header("Location: Account.php");
	exit;
    }		// already signed on

    if (strlen($newuserid) == 0)
	$messages	|= $specify;

    // if no error messages continue
    if (strlen($msg) == 0)
    {					// parameters syntactically OK
	// get existing account details
	$stpw		= md5(trim($password));
	$hashpw		= hash('sha512',trim($password));
	$user		= new User(array('username'	=> $newuserid));
	if ($user->isExisting())
	{
	    if ($user->get('shapassword') == $hashpw ||
		$user->get('password') == $stpw)
	    {				// password matches
		$warn	.= "<p>Password matches</p>\n";
		setcookie('rememberme',
			  "username:$newuserid&password:$password",
			  time() + 30*24*60*60, '/');	// keep for 30 days
		if ($redirectto == 'POST')
		{			// use history
		    // knowledge of the previous page is only held in the
		    // browser so the request to back-up to the previous
		    // page must be performed by Javascript
		    $tempBase	= $document_root . '/templates/';
		    $coffBase	= $document_root . '/CoffeeShop/';
		    $template	= new Template($tempBase . 'reposten.html');
		    $template->set('TITLE', 'Repost User Command after Signon');
		    $template->display();
		    exit;
		}			// use history
		else
		{			// redirect
		    header("Location: " . $redirectto);
		    exit;
		}			// redirect
	    }				// password matches
	    else
	    {
	        $messages	|= $incorrect;
	    }
	}
	else
	{
	    if (strlen($newuserid) > 0)
		$warn	.= "<p>user '$newuserid' not found</p>\n";
	    if (strlen($newuserid) > 0 || strlen($password) > 0)
		$messages	|= $incorrect;
	}				// userid not found
    }					// parameters syntactically OK

    $coffBase		= $document_root . '/CoffeeShop/';
    $template		= new CsTemplate("Signon$lang.html", true);

    $template->set('REDIRECTTO',	$redirectto);
    $template->updateTag('otherStylesheets',
    	       		    array('filename'	=> '/CoffeeShop/Signon'));
    if ($messages)
    {
	if (($messages & $specify) == 0)
	    $template->updateTag('specify', null);
	if (($messages & $signedoff) == 0)
	    $template->updateTag('signedoff', null);
	if (($messages & $incorrect) == 0)
	    $template->updateTag('incorrect', null);
    }
    else
	$template->updateTag('messages', null);

    $template->display();
