<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  resetMasterPasswordXml.php                                          *
 *                                                                      *
 *  Handle a request to update fields of a registered user              *
 *  in the database.  This file generates an                            *
 *  XML file, so it can be invoked from Javascript.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      userid              unique name of a registered user            *
 *      password        new password                                    *
 *                                                                      *
 *  History:                                                            *
 *      2015/08/09      Created                                         *
 *      2015/12/30      fix conflict with autoload                      *
 *      2016/02/02      new User option ousername removed               *
 *      2017/09/12      use get( and set(                               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2021/06/10      generate all output explicitly                  *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

$user       = null;
$password   = null;

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?".">\n");
print "<update>\n";

// get the updated values of the fields in the record
print "    <parms>\n";
foreach ($_GET as $key => $value)
{           // loop through all parameters
    $safevalue              = htmlentities($value,ENT_XML1);
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
                $msg        .= "invalid id=$safevalue. ";
            break;
        }       // unique numeric id of existing entry

        case 'username':
        {       // external user name
            $user       = new User(array('username' => $value));
            if (!$user->isExisting())
                $msg    .= "Unable to find User with Username '$safevalue'. ";
            break;
        }       // external user name

        case 'password':
        {
            $password   = $value;
            break;
        }       // new password

    }       // act on specific parameters
}           // loop through all parameters
print "    </parms>\n";

if (is_null($user))
{
    $msg        .= 'Missing or invalid mandatory parameter username=. ';
}

if (canUser('all'))
{ 
    if (strlen($msg) == 0)
    {       // no errors detected
        $id                 = $user->getId();
        print "<id>$id</id>\n";
        print "<username>" . $user->get('username') . "</username>\n";
        if ($password)
        {   // password specified
            // update the object
            $password       = trim($password);
            $user->set('password', null);
            $user->set('shapassword',
                hash('sha512', $password));
        }   // password specified

        $count              = $user->save();
        $lastCmd            = $user->getLastSqlCmd();
        print "    <cmd count='$count' id='$id'>$lastCmd</cmd>\n";
    }       // no errors detected
    else
    {
        print "    <msg>\n";
        print $msg;
        print "    </msg>\n";
    }
}
// close root node of XML output
print "</update>\n";
?>
