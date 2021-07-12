<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteBlogXml.php                                                   *
 *                                                                      *
 *  PHP script to support deleting a blog entry using Ajax.             *
 *                                                                      *
 *  Parameters:                                                         *
 *      blid        unique numeric identifier of blog message to delete *
 *                                                                      *
 *  History:                                                            *
 *      2011/08/12      created                                         *
 *      2012/01/13      change class names                              *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/03/25      use class Blog                                  *
 *      2014/03/30      validate that only sender or receiver of a      *
 *                      blog can delete it                              *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/12/30      fix conflict with autoload                      *
 *      2017/09/12      use get( and set(                               *
 *      2019/05/17      permit administrator to delete Blog records     *
 *                                                                      *
 *  Copyright &copy; 2017 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Blog.inc";
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?".">\n");
print "<blog>\n";
print "    <parms>\n";
$blid   = null;
foreach($_POST as $key => $value)
{           // loop through all parameters
    print "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
    if ($key == 'blid' || $key == 'id' || $key == 'bl_index')
        $blid   = $value;
}           // loop through all parameters
print "    </parms>\n";

// variables for constructing the main SQL SELECT statement

if (is_null($blid))
{           // missing parameter
    $msg    .= 'Missing mandatory parameter "blid". ';
}           // missing parameter

if (!canUser("blog"))
{           // not authorized to update database
    $msg    .= "Not authorized to delete blogs.</msg>\n";
}           // not authorized to update database

if (strlen($msg) == 0)
{           // no errors detected
    try {
        $blog   = new Blog(intval($blid));
        $sender = $blog->get('bl_username');
        $table  = $blog->get('bl_table');
        if ($table == 'Users')
        {
            $user   = new User(intval($blog->get('bl_keyvalue')));
            $receiver   = $user->get('username');
        }
        else
            $receiver   = '';
        print "<userid>$userid</userid>\n";
        print "<sender>$sender</sender>\n";
        print "<receiver>$receiver</receiver>\n";

        if ($userid == $sender || $userid == $receiver || canUser('yes'))
        {
            $count      = $blog->delete();
            $lastCmd    = $blog->getLastSqlCmd();
            print "<cmd count='$count'>$lastCmd</cmd>\n";
        }       // current user is either the sender or the receiver
        else
        {       // errors in parameters
            print "    <msg>\n";
            print "Blogs may only be deleted by the sender or the receiver of the message. authorized=$authorized\n";
            print "    </msg>\n";
        }       // errors in parameters
    } catch (Exception $e) {
        print "    <msg>\n";
        print htmlentities($e->getMessage(),ENT_XML1);
        print "    </msg>\n";
    }
}           // no errors detected
else
{           // errors in parameters
    print "    <msg>\n";
    print htmlentities($msg,ENT_XML1);
    print "    </msg>\n";
}           // errors in parameters

// close root node of XML output
print "</blog>\n";

?>
