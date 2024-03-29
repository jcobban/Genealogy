<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  replyBlogXml.php                                                    *
 *                                                                      *
 *  PHP script to support replying to a blog entry using Ajax.          *
 *                                                                      *
 *  Parameters:                                                         *
 *      blid    unique numeric identifier of blog message to reply to   *
 *      message reply text                                              *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/30      created                                         *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/12/30      fix conflict with autoload                      *
 *      2017/09/12      use get( and set(                               *
 *      2019/12/19      replace xmlentities with htmlentities           *
 *      2021/06/10      explicitly generate all output                  *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Blog.inc";
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?".">\n");
print "<blog>\n";
print "    <parms>\n";
$blid               = null;
foreach($_POST as $key => $value)
{
    print "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
    if ($key == 'blid' || $key == 'id' || $key == 'bl_index')
        $blid       = $value;
    else
    if ($key == 'message' || $key == 'text')
        $message    = $value;
}
print "    </parms>\n";

// variables for constructing the main SQL SELECT statement

if (is_null($blid))
{       // missing parameter
    $msg            .= 'Missing mandatory parameter "blid". ';
}       // missing parameter

if (!canUser("blog"))
{       // not authorized to update database
    $msg            .= "Not authorized to send blog messages.";
}       // not authorized to update database

if (strlen($msg) == 0)
{       // no errors detected
    $blog           = new Blog(intval($blid));

    // send the reply as a blog
    $sender         = $blog->get('bl_username');
    $sendUser       = new User(array('username' => $sender));
    $sendId         = $sendUser->get('id');
    $reply          = new Blog(array('table'    => 'Users',
                                     'keyvalue' => $sendId,
                                     'text'     => $message));
    $count          = $reply->save();
    if ($count === false)
        print "    <errors>" . $reply->getErrors() . "</errors>\n";
    $index          = $reply->getIndex();
    $lastCmd        = $reply->getLastSqlCmd();
    print "    <cmd count='$count' index='$index'>$lastCmd</cmd>\n";

    // validate that the current user is either the sender of the
    // original blog or the addressee of the original blog
    $table          = $blog->get('bl_table');
    if ($table == 'Users')
    {
        $user       = new User($blog->get('bl_keyvalue'));
        $receiver   = $user->get('username');
    }
    else
        $receiver   = '';

    if ($userid == $sender || $userid == $receiver || canUser('yes'))
    {
        $count      = $blog->delete();
        $lastcmd    = $blog->getLastSqlCmd();
        print "    <cmd count='$count'>$lastcmd</cmd>\n";

        if (is_int($count))
            print "    <msg>Deleted $count records.</msg>\n";
    }       // current user is either the sender or the receiver
    else
    {       // errors in parameters
        print "    <msg>\n";
        print "Blogs may only be deleted by the sender or the receiver of the message.";
        print "    </msg>\n";
    }       // errors in parameters
}           // no errors detected
else
{           // errors in parameters
    print "    <msg>\n";
    print htmlentities($msg,ENT_XML1);
    print "    </msg>\n";
}           // errors in parameters

// close root node of XML output
print "</blog>\n";
