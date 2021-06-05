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
 *		confirmid       verification hash code							*
 *																		*
 *  History:															*
 *		2014/08/01		Created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/30		fix conflict with autoload						*
 *		2017/09/12		use get( and set(								*
 *		2018/01/28		use Template									*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *		2021/01/03      correct XSS vulnerability                       *
 *		2021/05/31      validate that confirmid parameter is numeric    *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . "/common.inc";

// get parameters
$userid				= null;
$id					= null;
$idtext             = null;
$confirmid			= null;
$confirmidtext		= null;
$lang				= 'en';

if (isset($_GET) && count($_GET) > 0)
{
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			    // loop through parametersa
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                "$safevalue</td></tr>\n"; 
	    $value                  = trim($value);
		switch(strtolower($key))
		{		    // act on specific parameters
		    case 'userid':
		    case 'clientid':
		    {
				$userid		        = $value;
				break;
		    }
	
		    case 'id':
	        {
	            if (ctype_digit($id))
	                $id		        = $value;
	            else
	                $idtext         = $safevalue;
				break;
		    }
	
		    case 'confirmid':
		    {
	            if (ctype_digit($id))
				    $confirmid		= $value;
	            else
	                $confirmidtext  = $safevalue;
				break;
		    }
	
		    case 'lang':
		    {
	            $lang               = FtTemplate::validateLang($value);
				break;
		    }
	
		}		        // act on specific parameters
	}		    	    // loop through parameters
}                       // invoked on web server

// get the template
$template		        = new FtTemplate("ConfirmEmail$lang.html");

// validate parameters
if (is_string($idtext))
{
    $text               = $template['invalidid']->innerHTML();
    $msg	            .= str_replace('$id', $idtext, $text);
}
else
if (is_null($id))
{                   // account identifier missing
    $text               = $template['missing']->innerHTML();
    $msg	            .= str_replace('$name', 'id', $text);
}                   // account identifier missing
else
{		            // account identifier supplied
    $user		            = new User(array('id'	=> $id));
    if (!$user->isExisting())
    {
        $text           = $template['invalidid']->innerHTML();
        $msg	        .= str_replace('$id', $id, $text);
    }
}                   // account identifier supplied`

if (is_null($userid))
{		            // user name missing
    $text               = $template['missing']->innerHTML();
    $msg	            .= str_replace('$name', 'userid', $text);
}                   // user name missing
else
if ($user)
{		            // user name supplied
    if ($userid != $user['username'])
    {               // username does not match
        $text       = $template['invalidusername']->innerHTML();
        $msg	    .= str_replace('$userid', 
                                   htmlspecialchars($userid), 
                                   $text);
    }               // username does not match
}		            // user name supplied

if (is_string($confirmidtext))
{
    $text               = $template['invalidconfirm']->innerHTML();
    $msg	            .= str_replace('$confirmid', $confirmidtext, $text);
    $user               = null;
}

if ($user)
{                   // id and confirmid supplied
    if (is_null($confirmid) || $confirmid == $user['confirmid'])
    {		        // confirmid matches
	    $user->set('auth', 'edit,blog');
        $user->save();
        $user->dump('auth set');
    }		        // confirmid matches
    else
    {
        $text   = $template['invalidconfirm']->innerHTML();
        $msg	.= str_replace('$confirmid', 
                               htmlspecialchars($confirmid), 
                               $text);
    }
}		            // id and confirmid supplied

$template->set('USERID',	htmlspecialchars($userid));
$template->set('LANG',		$lang);

if (strlen($msg) > 0)
	$template['confirmation']->update(null);
$template->display();
