<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateUserXml.php                                                   *
 *                                                                      *
 *  Handle a request to update fields of a registered user              *
 *  in the database.  This file generates an                            *
 *  XML file, so it can be invoked from Javascript.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      userid          unique name of a registered user                *
 *      password        new password                                    *
 *                                                                      *
 *  History:                                                            *
 *      2014/07/25      Created                                         *
 *      2015/06/30      include record id and username in response      *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/12/30      fix conflict with autoload                      *
 *      2016/01/02      restrict characters permitted in password       *
 *                      escape password in XML response                 *
 *      2016/02/02      new User option ousername removed               *
 *      2017/09/12      use get( and set(                               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

$user               = null;
$password           = null;

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?".">\n");
print "<update>\n";

// only an administrator can use this script
if (!canUser('all'))
{       // not authorized
    $msg    .= "User not authorized to update user record.  auth='$authorized'. ";
}       // not authorized

// get the updated values of the fields in the record
print "    <parms>\n";
foreach ($_POST as $key => $value)
{           // loop through all parameters
    $safevalue          = htmlentities($value,ENT_XML1);
    print "\t<$key>$safevalue</$key>\n";
    switch(strtolower($key))
    {       // act on specific parameters
        case 'id':
        {       // unique numeric id of existing entry
            if (ctype_digit($value))
            {
                $user       = new User(array('id' => $value));
                if (!$user->isExisting())
                    $msg    .= "Unable to get User with id='$value'. ";
            }
            else
                $msg        .= "Invalid parameter id='$safevalue'. ";
            break;
        }       // unique numeric id of existing entry

        case 'username':
        {       // external user name
            $user       = new User(array('username' => $value));
            if (!$user->isExisting())
                $msg    .= "Unable to find User with Username '$value'. ";
            break;
        }       // external user name

        case 'password':
        {
            $password   = trim($value);
            break;
        }       // new password

    }       // act on specific parameters
}           // loop through all parameters
print "    </parms>\n";

if (is_null($user))
{
    $msg        .= 'Missing or invalid mandatory parameter username=. ';
}
 
if (strlen($msg) == 0)
{       // no errors detected
    $id                 = $user->getId();
    $username           = $user->get('username');
    print "<id>$id</id>\n";
    print "<username>$username</username>\n";
    if ($password)
    {   // password specified
        // update the object
        $user->set('password', $password);

        // notify the user
        $email          = $user->get('email');
        $username       = $user->get('username');

        // notify the administrators
        $getparms       = array('auth'  => 'all');
        $admins         = new RecordSet('Users', $getparms);
        $bcc            = 'BCC: ';
        $comma          = '';
        foreach($admins as $id => $admin)
        {           // loop through administrators
            $bcc        .= $comma . $admin->get('email'); 
            $comma      = ',';
        }           // loop through administrators

        $sent       = mail($email,
             "[JamesCobban.net] Password Reset for User $username",
             "The password on your account '$username' has been reset " .
                "by the administrator to '$password'. " .
                "You are advised to change your password as soon as convenient. ");
        print "<mail>\n";
        print "<to>" . htmlentities($email,ENT_XML1) . "</to>\n";   
        print "<bcc>" . htmlentities($bcc,ENT_XML1) . "</bcc>\n";
        print "<subject>" .
             "[JamesCobban.net] Password Reset for User $username"
              . "</subject>\n";  
        print "<body>" .
             "The password on your account '$username' has been reset " .
                "by the administrator to '" . 
                htmlentities($password,ENT_XML1) . "'." 
              . "</body>\n";
        print "<result>$sent</result>\n";   
        print "</mail>\n";   
    }   // password specified
    $count          = $user->save();
    $lastcmd        = $user->getLastSqlCmd();
    print "    <cmd count='$count' id='$id'>$lastcmd</cmd>\n";
}       // no errors detected
else
{
    print "    <msg>\n";
    print $msg;
    print "    </msg>\n";
}

// close root node of XML output
print "</update>\n";
