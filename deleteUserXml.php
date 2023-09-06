<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteUserXml.php                                                   *
 *                                                                      *
 *  Handle a request to delete an registered user from the              *
 *  database.  This file generates an                                   *
 *  XML file, so it can be invoked from Javascript.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      clientid      unique name of a registered user                  *
 *                                                                      *
 *  History:                                                            *
 *      2011/02/14      Created                                         *
 *      2012/01/13      change class names                              *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/12/25      rename to deleteUserXml.php                     *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/12/30      error in use of PDO execute                     *
 *      2017/09/13      use class User                                  *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2021/06/08      method delete no longer prints output           *
 *      2023/01/19      protect against script insertion                *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
    header("Content-Type: text/xml");
    require_once __NAMESPACE__ . "/User.inc";
    require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    print "<?xml version='1.0' encoding='UTF-8'?".">\n";
    print "<deleted>\n";

    $clientid     = null;

    print "    <parms>\n";
    foreach($_POST as $key => $value)
    {               // loop through all parameters
        $safevalue  = htmlspecialchars($value);
        print "\t<$key>$safevalue</$key>\n";
        switch(strtolower($key))
        {           // act on specific parameteres
            case 'clientid':
            {       // clientid to be deleted
                $clientid     = $safevalue;
                break;
            }       // clientid to be deleted
        }           // act on specific parameteres
    }               // loop through all parameters
    print "    </parms>\n";

    if (!canUser('all'))
    {       // not authorized
        $msg        .= 'User not authorized to delete user. ';
    }       // not authorized

    if (is_null($clientid))
    {
        $msg        .= 'Missing mandatory parameter clientid=. ';
    }

    if (strlen($msg) == 0)
    {       // no errors detected
        // delete the indicated event entry
        $user           = new User(array('username' => $clientid));
        if ($user->isExisting())
        {
            $count      = $user->delete();
            $lastCmd    = $user->getLastSqlCmd();
            print "<cmd count='$count'>$lastCmd</cmd>\n";
        }
    }           // no errors detected
    else
    {           // parameter validation failed
        print "    <msg>\n";
        print htmlentities($msg,ENT_XML1);
        print "    </msg>\n";
    }           // parameter validation failed

    // close root node of XML output
    print "</deleted>\n";

