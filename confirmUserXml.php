<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  confirmUserXml.php                                                  *
 *                                                                      *
 *  Handle a request to confirm an registered user from the             *
 *  database.  This file generates an                                   *
 *  XML file, so it can be invoked from Javascript.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      userid      unique name of a registered user                    *
 *                                                                      *
 *  History:                                                            *
 *      2011/11/28      Created                                         *
 *      2012/01/13      change class names                              *
 *                      change file name to confirmUserXml.php          *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/12/02      enclose comment blocks                          *
 *                      output trace and warning information            *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/12/30      error in use of PDO execute                     *
 *      2016/01/19      add id to debug trace                           *
 *      2017/09/13      use class User to update database               *
 *      2021/05/24      correct errors because it was run under the     *
 *                      client's authorization, not the administrator   *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?".">\n";
print "<confirmed>\n";

$clientid         = null;
print "    <parms>\n";
foreach($_POST as $key => $value)
{
    print "\t<$key>$value</$key>\n";
    switch (strtolower($key))
    {
        case 'clientid':
            $clientid     = trim($value);
            break;
    }
}
print "    </parms>\n";

// get the updated values of the fields in the record


if (!canUser('all'))
{       // not authorized
    $msg        .= 'User not authorized to confirm user. ';
}       // not authorized

if ($clientid == null)
{
    $msg        .= 'Missing mandatory parameter clientid=. ';
}

showTrace();

if (strlen($msg) == 0)
{                   // no errors detected
    // confirm the indicated event entry
    $client             = new User(array('username' => $clientid));
    if ($client['auth'] == 'pending')
    {               // user is awaiting confirmation
        print "<id>{$client['id']}</id>\n";
        $client->set('auth', 'blog,edit');
        $count          = $client->save();
        if ($count == 0)
        {
            print "    <msg>No user confirmed.</msg>\n";
            error_log("confirmUserXml.php: User $clientId authorization previously '{$client['auth']}");
        }
        else
        {           // send e-mail to the new user to validate the address
            print "<cmd>" . $client->getLastSqlCmd() . "</cmd>\n";
            $email      = $client['email'];
            $sent       = mail($email,
             "[JamesCobban.net] Thank You for Registering as User $clientid",
             " Thank you.\n\n" .
             "Administrator");
        }           // send e-mail to the new user to validate the address
    }               // user is awaiting confirmation
}                   // no errors detected
else
{
    error_log("confirmUserXml.php: User $clientId authorization failed msg=$msg");
    print "    <msg>\n";
    print $msg;
    print "    </msg>\n";
}

// close root node of XML output
print "</confirmed>\n";

