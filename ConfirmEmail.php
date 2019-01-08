<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ConfirmEmail.php													*
 *																		*
 *  This script handles the confirmation of a new user as an			*
 *  authorized user of the web site.									*
 *																		*
 *  Parameters (passed by GET):											*
 *		userid			new userid requested by user					*
 *		id				record number of new userid						*
 *		hash			verification hash code							*
 *																		*
 *  History:															*
 *		2014/08/01		Created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/30		fix conflict with autoload						*
 *		2017/09/12		use get( and set(								*
 *		2018/01/28		use Template									*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/common.inc";

// get parameters
$userid				= null;
$id					= null;
$hash				= null;
$lang				= 'en';

foreach($_GET as $key => $value)
{			// loop through parameters
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'userid':
	    {
			$userid		= $value;
			break;
	    }

	    case 'id':
	    {
			$id		= $value;
			break;
	    }

	    case 'hash':
	    {
			$hash		= $value;
			break;
	    }

	    case 'lang':
	    {
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
			break;
	    }

	}		// act on specific parameters
}			// loop through parameters

// validate parameters
if (!is_null($id) && !is_null($hash) && !is_null($userid))
{		// confirmation supplied
	try {
	    $user		    = new User(array('id'	=> $id));
	    $shapassword	= $user->get('shapassword');
	    if ($hash != $shapassword)
		    $msg	.= "Invalid hash code for user. ";
	    else
	    {		// confirm
		    $user->set('auth', 'edit,blog');
		    $user->save(false);
	    }		// confirm
	} catch (Exception $e) {
	    $msg	.= "Invalid id number $id. ";
	}
}		// registration supplied
else
{		// registration not supplied
	$msg		.= "Missing mandatory parameters. ";
}		// registration not supplied

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "ConfirmEmail$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	= 'ConfirmEmailen.html';
}
$template->includeSub($tempBase . $includeSub,
				      'MAIN');
$template->set('USERID',	$userid);
$template->set('LANG',		$lang);

if (strlen($msg) > 0)
	$template->updateTag('confirmation', null);
$template->display();
