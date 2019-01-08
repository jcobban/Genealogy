<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Signon.php								*
 *									*
 *  This script provides a common interface for signing on as an	*
 *  authorized user of the web site.					*
 *									*
 *  Parameters (passed by POST):					*
 *	userid		new userid requested by user			*
 *	password	new password supplied by user			*
 *	act		action signalled from previous invocation	*
 *									*
 *  History:								*
 *	2010/08/22	Created						*
 *	2010/11/12	add help panel					*
 *	2010/12/20	correct URL of genealogy page in header &	*
 *			trailer						*
 *	2011/03/19	identify keyboard shortcuts in button labels	*
 *			add context specific help			*
 *	2011/03/31	Permit spaces in user names			*
 *	2011/04/22	support IE7					*
 *	2011/04/28	use CSS rather than tables for layout of header	*
 *			and trailer					*
 *	2012/01/05	improve validation of userid			*
 *			use last supplied userid as default		*
 *			use id rather than name for buttons to avoid	*
 *			passing them to the action script in IE		*
 *	2012/04/17	remove top and bottom divisions of page		*
 *	2012/05/28	set explicit class in <input> tags		*
 *	2012/06/21	add promotional information to page.		*
 *	2012/06/28	add contact "button"				*
 *	2013/03/06	make validation of userids match with		*
 *			Register.php					*
 *	2013/03/08	EU Cookie notice				*
 *	2013/09/11	add support for SHA-512 password hashing	*
 *	2013/11/16	handle lack of database server gracefully	*
 *	2013/12/05	do not explicitly set $msg			*
 *			add debug output				*
 *	2013/12/10	use CSS for layout				*
 *	2013/12/18	add for= attribute to <label> tags		*
 *			add id= attribute to <input> tags		*
 *	2014/03/27	on good signon redirect to UserInfo.php		*
 *			remove syntax verification of userid and	*
 *			password, since they are verified by matching	*
 *			the database entry, this can only introduce	*
 *			the inability of a valid user to sign on	*
 *			do not enter this script if user is already	*
 *			signed on					*
 *			use class User to access Users table		*
 *	2014/06/26	expire login in 30 days, not 30 minutes		*
 *	2014/07/13	revert to 30 minutes for session		*
 *	2014/06/15	support for popupAlert moved to common code	*
 *	2014/07/18	add suggestion to use e-mail address as userid	*
 *	2015/02/02	do not log the individual back on using		*
 *			the rememberme cookie if the person logged off	*
 *	2015/05/11	use ContactAuthor.php to contact administrator	*
 *	2015/06/30	did not issue error message if bad password	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/08/04	add button to reset password			*
 *			add accesskey attribute to buttons		*
 *	2015/12/07	session tracking moved to cookies from session	*
 *	2015/12/30	fix conflict with autoload			*
 *	2016/01/01	used saved userid and password even if new	*
 *			logon specified					*
 *	2016/01/19	add id to debug trace				*
 *	2016/02/02	new User option ousername removed		*
 *	2017/08/31	undefined $e because try/catch replaced by	*
 *			if/then/else					*
 *	2017/09/12	use get( and set(				*
 *	2017/12/13	$lang was not initialized if no parameters	*
 *	2018/01/04	remove Template from template file names	*
 *	2018/01/25	common functionality moved to class FtTemplate	*
 *	2018/02/10	use template for request to browser to reissue	*
 *			the client's request once signed on		*
 *	2018/05/28	include specific CSS				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . "/User.inc";
    require_once __NAMESPACE__ . '/Template.inc';
    require_once __NAMESPACE__ . '/Language.inc';
    // the following obtains $userid and $authorized from the cookie
    require_once __NAMESPACE__ . "/common.inc";

    // validate parameters
    $newuserid		= '';
    $password		= '';
    $action		= '';
    $rememberme		= '';
    $lang		= 'en';
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
		    $template	= new Template($tempBase . 'repost.html');
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

    $title		= 'Sign In for Contributing';
    $tempBase		= $document_root . '/templates/';
    $coffBase		= $document_root . '/CoffeeShop/';
    $template		= new FtTemplate("${tempBase}dialog$lang.html");
    $includeSub		= "Signon$lang.html";
    if (!file_exists($coffBase . $includeSub))
    {
	$languageObj	= new Language(array('code' => $lang));
	$warn		.= "Sorry the site does not support " .
			   $languageObj->get('name') . '/' .
			   $languageObj->get('nativename') .
			   ".</p>\n";
	$includeSub	= 'Signonen.html';
    }
    $template->includeSub($coffBase . $includeSub,
			            'MAIN');
    $template->set('TITLE',		    $title);
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
