<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateUserXml.php							*
 *									*
 *  Handle a request to update fields of a registered user		*
 *  in the database.  This file generates an				*
 *  XML file, so it can be invoked from Javascript.			*
 *									*
 *  Parameters:								*
 *	userid		unique name of a registered user		*
 *	password	new password					*
 *									*
 *  History:								*
 *	2014/07/25	Created						*
 *	2015/06/30	include record id and username in response	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/12/30	fix conflict with autoload			*
 *	2016/01/02	restrict characters permitted in password	*
 *			escape password in XML response			*
 *	2016/02/02	new User option ousername removed		*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

    $passwordAlphabet	=
		"/^[ABCDEFGHJKLMNOPQRSTUVWXYZ" .
		"abcdefghjkmnopqrstuvwxyz" .
		"0123456789" .
		"!_\-.^$@#!~%]+$/";

    $user	= null;
    $password	= null;

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<update>\n";

    // only an administrator can use this script
    if (!canUser('all'))
    {		// not authorized
	$msg	.= 'User not authorized to update user record. ';
    }		// not authorized

    // get the updated values of the fields in the record
    print "    <parms>\n";
    foreach ($_POST as $key => $value)
    {			// loop through all parameters
	print "\t<$key>" . xmlentities($value) . "</$key>\n";
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'id':
	    {		// unique numeric id of existing entry
		try {
		    $user	= new User(array('id' => $value));
		} catch (Exception $e) {
		    $msg	.= "Unable to get User with id='$value'. ";
		}	// unable to create user entry
		break;
	    }		// unique numeric id of existing entry

	    case 'username':
	    {		// external user name
		$user	= new User(array('username' => $value));
		if (!$user->isExisting())
		    $msg .= "Unable to find User with Username '$value'. ";
		break;
	    }		// external user name

	    case 'password':
	    {
		$password	= trim($value);
		$result		= preg_match($passwordAlphabet,
					     $password);
		if ($result !== 1)
		    $msg .= "Invalid character in password '$password'. ";
		break;
	    }		// new password

	}		// act on specific parameters
    }			// loop through all parameters
    print "    </parms>\n";

    if (is_null($user))
    {
	$msg		.= 'Missing or invalid mandatory parameter username=. ';
    }
 
    if (strlen($msg) == 0)
    {		// no errors detected
	print "<id>" . $user->getId() . "</id>\n";
	print "<username>" . $user->get('username') . "</username>\n";
	if ($password)
	{	// password specified
	    // update the object
	    $password		= trim($password);
	    $user->set('password', null);
	    $user->set('shapassword',
			hash('sha512', $password));

	    // notify the user
	    $email		= $user->get('email');
	    $username		= $user->get('username');

	    $getparms		= array('auth'	=> 'all');
	    $admins		= new RecordSet('Users', $getparms);
	    $bcc		= 'BCC: ';
	    $comma		= '';
	    foreach($admins as $id => $admin)
	    {			// loop through administrators
		$bcc		.= $comma . $admin->get('email'); 
		$comma		= ',';
	    }			// loop through administrators

	    $sent		= mail($email,
		 "[JamesCobban.net] Password Reset for User $username",
		 "The password on your account '$username' has been reset " .
			"by the administrator to '$password'. " .
			"You are advised to change your password as soon as convenient. ");
	    print "<mail>\n";
	    print "<to>" . xmlentities($email) . "</to>\n";   
	    print "<bcc>" . xmlentities($bcc) . "</bcc>\n";
	    print "<subject>" .
		 "[JamesCobban.net] Password Reset for User $username"
		  . "</subject>\n";  
	    print "<body>" .
		 "The password on your account '$username' has been reset " .
			"by the administrator to '" . 
			xmlentities($password) . "'." 
		  . "</body>\n";
	    print "<result>$sent</result>\n";   
	    print "</mail>\n";   
	}	// password specified
	$user->save("cmd");
    }		// no errors detected
    else
    {
	    print "    <msg>\n";
	    print $msg;
	    print "    </msg>\n";
    }

    // close root node of XML output
    print "</update>\n";
?>
