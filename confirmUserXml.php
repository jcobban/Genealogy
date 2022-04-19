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
 *      clientid        unique name of a registered user                *
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
 *      2021/06/08      method save no longer has a parameter           *
 *      2022/03/27      use common arrayToXML                           *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
$confirmed          = array('parms'     => $_GET);

$clientid           = null;
foreach($_GET as $key => $value)
{
    switch (strtolower($key))
    {
        case 'clientid':
            $clientid     = trim($value);
            break;
    }
}

// get the updated values of the fields in the record


if (!canUser('all'))
{       // not authorized
    $msg            .= 'Current user is not authorized to confirm user. ';
}       // not authorized

if ($clientid == null)
{
    $msg            .= 'Missing mandatory parameter clientid=. ';
}

if (strlen($warn) > 0)
{
    $confirmed['warn']  = $warn;
}

if (strlen($msg) == 0)
{                   // no errors detected
    // confirm the indicated event entry
    $client             = new User(array('username' => $clientid));
    if ($client->isExisting() && $client['auth'] == 'pending')
    {               // user is awaiting confirmation
        $confirmed['id']        = $client['id'];
        $client->set('auth', 'blog,edit');
        $count                  = $client->save();
        if ($count == 0)
        {
            $confirmed['msg']   = "No user confirmed";
        }
        else
        {           // send e-mail to the new user to validate the address
            $confirmed['cmd']   = $client->getLastSqlCmd();
            $email              = $client['email'];
            $sent               = mail($email,
             "[JamesCobban.net] Thank You for Registering as User $clientid",
             " Thank you.\n\n" .
             "Administrator");
        }           // send e-mail to the new user to validate the address
    }               // user is awaiting confirmation
    else
    if (!$client->isExisting())
    {
        $confirmed['msg']       = htmlentities("User '$clientid' is not defined", ENT_XML1);
    }
}                   // no errors detected
else
{
    $confirmed['msg']           = $msg;
}

print arrayToXML($confirmed, array('nodename' => 'confirmed'));
