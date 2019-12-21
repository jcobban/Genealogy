<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  postProjectBlog.php													*
 *																		*
 *  PHP script to support posting a blog entry using Ajax.				*
 *																		*
 *  Parameters:															*
 *		projectId		unique numeric identifier of project for which	*
 *						to post blog									*
 *		message				text of blog entry							*
 *																		*
 *  History:															*
 *		2010/12/25		created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/25		use class Blog									*
 *		2015/07/02		access PHP includes using include_path			*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Blog.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<blog>\n";
print "    <parms>\n";

$projectId	= null;
$message	= null;
foreach($_POST as $key => $value)
{	
    print "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
    if (substr($key,0,9) == 'projectId')
        $projectId	= $value;
    else
    if (substr($key,0,7) == 'message')
        $message	= $value;
}
print "    </parms>\n";

// variables for constructing the main SQL SELECT statement

if (is_null($projectId))
{		// missing parameter
    $msg	.= 'Missing mandatory parameter "projectId". ';
}		// missing parameter

if (is_null($message))
{		// missing parameter
    $message	= null;
    $msg	.= 'Missing mandatory parameter "message". ';
}		// missing parameter

if ($authorized != "yes")
    $msg	.= "Not authorized to update database. ";

if (strlen($msg) == 0)
{		// no errors detected
    $blog	= new Blog(array('username'	=> $userid,
    				 'table'	=> 'ToDoList',
    				 'keyname'	=> 'IDoList',
    				 'keyvalue'	=> $projectId,
    				 'text'		=> $message));
    $blog->save(true);
}		// no errors detected
else
{		// errors in parameters
    print "    <msg>\n";
    print htmlentities($msg,ENT_XML1);
    print "    </msg>\n";
}		// errors in parameters

// close root node of XML output
print "</blog>\n";

