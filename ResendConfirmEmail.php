<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ResenConfirmEmail.php												*
 *																		*
 *  This script resets and resends the confirmation email to all		*
 *  registered users who have not previously confirmed their account	*
 *																		*
 *  History:															*
 *		2018/09/25		Created											*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  function randomPassword												*
 *																		*
 *  This function generates a random password.                          *
 *																		*
 *	Input:																*
 *		$length     number of characters in the generated password		*
 *																		*
 *	Returns:															*
 *	    string containing a password        							*
 ************************************************************************/
function randomPassword($length)
{
    // define variables used within the function    
    $symbols        = array();
    $used_symbols   = '';
    $pass           = '';
 
    // an array of different character types    
    $symbols["lower_case"] = 'abcdefghijklmnopqrstuvwxyz';
    $symbols["upper_case"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $symbols["numbers"] = '1234567890';
    $symbols["special_symbols"] = '!?~@#-_+<>[]{}';

    // build a string with all supported characters
    $used_symbols .= $symbols["lower_case"] . 
					 $symbols["upper_case"] .
					 $symbols["numbers"] .
					 $symbols["special_symbols"];
    // to get index of last character deduct 1 from number of characters
    $symbols_length = strlen($used_symbols) - 1;
     
    $pass		= '';
    for ($i = 0; $i < $length; $i++)
    {
        // get a random character from the string with all characters
        $n          = rand(0, $symbols_length);
        // add the character to the password string
        $pass	    .= $used_symbols[$n];
    }
     
    return $pass; // return the generated password
}		// function randomPassword

// variables 
$lang			= 'en';

// get parameters
foreach($_GET as $key => $value)
{			    // loop through parameters
    switch(strtolower($key))
    {			// act on specific parameters
		case 'lang':
		{
		    if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
		    break;
		}

    }			// act on specific parameters
}			    // loop through parameters


$template		= new FtTemplate("ResendConfirmEmail$lang.html");

if (canUser('all'))
{
    $users			= new RecordSet('Users', 
						array('pattern'	=> 'pending'));
    $subjectTag	    = $template->getElementById('emailSubject');
    $bodyTag	    = $template->getElementById('emailBody');
    foreach($users as $user)
    {
		$newuserid      = $user->get('username');
		$id		        = $user->get('id');
		$email		    = $user->get('email');
		$newpassword	= randomPassword(12);
		$user->setPassword($newpassword);
		$shapassword	= $user->get('shapassword');
		$subject	    = str_replace('$newuserid',
					      $newuserid, 
					      trim($subjectTag->innerHTML()));
		$body		    = str_replace(array('$newuserid','$servername','$id','$newpassword','$shapassword'),
						              array($newuserid,$servername,$id,$newpassword,$shapassword),
						              trim($bodyTag->innerHTML()));
		// send e-mail to the new user to validate the address
		$warn	.= "<p>" . __LINE__ . " \$email='$email'</p>\n".
		 		       "<p>\$subject='$subject'</p>\n".
		 		       "$body\n";
		$sent		= mail($email,
		 		           $subject,
		 		           $body);
    }			// loop through pending users
    $template->updateTag('notAuthorized', null);
}		// authorized

$template->display();
