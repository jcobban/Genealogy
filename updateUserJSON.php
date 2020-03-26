<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateUserXml.php													*
 *																		*
 *  Handle a request to update fields of a registered user				*
 *  in the database.  This file generates an							*
 *  JSON file, so it can be invoked from Javascript.						*
 *																		*
 *  Parameters:															*
 *		userid			unique name of a registered user			    *
 *		password		new password									*
 *																		*
 *  History:															*
 *		2014/07/25		Created											*
 *		2015/06/30		include record id and username in response		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/30		fix conflict with autoload						*
 *		2016/01/02		restrict characters permitted in password		*
 *						escape password in JSON response					*
 *		2016/02/02		new User option ousername removed				*
 *		2017/09/12		use get( and set(								*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

// values of parameters
$user	                        = null;
$email                          = null;
$cellphone                      = null;
$password	                    = null;

// emit the JSON header
print "{\n";

// get the updated values of the fields in the record
print "    \"parms\" : {\n";
$comma                          = '';
foreach ($_POST as $key => $value)
{			// loop through all parameters
    $escvalue                   = str_replace('"','\\"',$value);
    print "$comma        \"$key\" : \"$escvalue\"";
    $comma                      = ',';
    switch(strtolower($key))
    {		// act on specific parameters
        case 'id':
        {		// unique numeric id of existing entry
            $user	            = new User(array('id' => $value));
            if (!$user->isExisting())
                $msg	        .= "Unable to get User with id='$value'. ";
            break;
        }		// unique numeric id of existing entry

        case 'username':
        {		// external user name
            $user	            = new User(array('username' => $value));
            break;
        }		// external user name

        case 'email':
        {
            $email	            = trim($value);
            break;
        }		// new password

        case 'cellphone':
        {
            $cellphone	        = trim($value);
            break;
        }		// new password

        case 'password':
        {
            $password	        = trim($value);
            break;
        }		// new password

    }		// act on specific parameters
}			// loop through all parameters
print "\n    },\n";         // end "parms" object

if (is_null($user))
{
    $msg		.= 'Missing or invalid mandatory parameter username=. ';
}
 
if (strlen($msg) == 0)
{		// no errors detected
    if ($email)
        $user->set('email', $email);
    if ($cellphone)
        $user->set('cellphone', $cellphone);
    if ($password)
    {	// password specified
        // update the object
        $user->set('password', $password);

        // notify the user
        $email		    = $user->get('email');
        $username		= $user->get('username');

        // notify the administrators
        $getparms		= array('auth'	=> 'all');
        $admins		    = new RecordSet('Users', $getparms);
        $bcc		    = 'BCC: ';
        $comma		    = '';
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
        print "    \"mail\" : {\n";
        print "        \"to\" : " . json_encode($email) . ",\n";   
        print "        \"bcc\" : " . json_encode($bcc) . ",\n";
        print "        \"subject\" : " .
            json_encode("[JamesCobban.net] Password Reset for User $username")
              . ",\n";  
        print "        \"body\" : " .
             json_encode("The password on your account '$username' has been reset by the administrator to '$password'.") . 
                    ",\n";
        print "        \"result\" : $sent\n";   
        print "}\n";   
    }	// password specified
    $user->save(false);
    print "    \"updated\" : ";
    $user->toJson();
}		// no errors detected
else
{
    print "    \"msg\" : " . json_encode($msg) . "\n";
}

// close root node of JSON output
print "}\n";
