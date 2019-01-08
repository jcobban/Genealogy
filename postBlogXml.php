<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  postBlogXml.php							*
 *									*
 *  PHP script to support posting a blog message using Ajax.		*
 *									*
 *  Parameters:								*
 *	idir	unique numeric identifier of record to post blog for	*
 *	table	name of table containing the relevant record		*
 *	email	email address of sender					*
 *	subject	subject or title of blog post				*
 *	message	text of blog post					*
 *	update	if 'Y' update the specified post instead of adding	*
 *		a post that references the specified post		*
 *									*
 *  History:								*
 *	2010/08/11	Renamed to postBlog.php				*
 *	2010/08/11	Changed to return XML output to permit use	*
 *			through Javascript Ajax.			*
 *	2010/09/25	Check error on $result, not $connection after	*
 *			query/exec					*
 *	2010/10/23	move connection establishment to common.inc	*
 *	2011/12/08	allow users other than master to post blogs	*
 *	2012/01/13	change class names				*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/03/25	broaden support to any table with numeric key	*
 *			use class Blog					*
 *			rename to PostBlogXml.php			*
 *	2014/03/31	anyone can post to the Users table		*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/07/26	accept 'emailaddress' as a parameter		*
 *			include text entered by a guest as the default	*
 *			body of the e-mail used to respond, and the	*
 *			first line of that text as the subject of the	*
 *			email						*
 *	2016/01/19	add id to debug trace				*
 *	2018/09/07	do not report error for empty tablename		*
 *	2018/09/12	add support for subject				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/Blog.inc";
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<blog>\n";
    print "    <parms>\n";

    $idir		= null;
    $table		= 'tblIR';
    $subject		= null;
    $message		= '';
    $email		= '';
    $information	= null;
    $keyname		= null;
    $update		= false;

    foreach($_POST as $key => $value)
    {	
	print "\t<$key>" . xmlentities($value) . "</$key>\n";

	switch(strtolower($key))
	{		// act on specific keys
	    case 'idir':
	    case 'id':
	    case 'keyvalue':
	    {
			$keyname	= $key;
			$idir		= $value;
			break;
	    }		// id

	    case 'table':
	    case 'tablename':
	    {
			$table		= $value;
			if (strlen($table) > 0)
			{
			    $information	= Record::getInformation($table);
			    if ($information)
			    {		// get primary key name
					$keyname	= $information['prime'];
			    }		// get primary key name
			    else
					$msg		.= "Unsupported table name '$table'. ";
			}
			else
			    $keyname		= '';
			break;
	    }		// table

	    case 'idar':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblAR';
			break;
	    }

	    case 'idbp':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblBP';
			break;
	    }

	    case 'idbr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblBR';
			break;
	    }

	    case 'idcp':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblCP';
			break;
	    }

	    case 'idcr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblCR';
			break;
	    }

	    case 'ider':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblER';
			break;
	    }

	    case 'idhb':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblHB';
			break;
	    }

	    case 'idhl':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblHL';
			break;
	    }

	    case 'idir':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblIR';
			break;
	    }

	    case 'idlr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblLR';
			break;
	    }

	    case 'idmr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblMR';
			break;
	    }

	    case 'idms':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblMS';
			break;
	    }

	    case 'idnr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblNR';
			break;
	    }

	    case 'idnx':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblNX';
			break;
	    }

	    case 'idrm':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblRM';
			break;
	    }

	    case 'idsr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblSR';
			break;
	    }

	    case 'idsx':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblSX';
			break;
	    }

	    case 'idtc':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblTC';
			break;
	    }

	    case 'idtd':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblTD';
			break;
	    }

	    case 'idtl':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblTL';
			break;
	    }

	    case 'idtr':
	    {
			$idir		= $value;
			$keyname	= $key;
			$table		= 'tblTR';
			break;
	    }

	    case 'subject':
	    {
			$subject	= $value;
			break;
	    }		// message

	    case 'message':
	    case 'text':
	    {
			$message	= $value;
			break;
	    }		// message

	    case 'email':
	    case 'emailaddress':
	    {
			$email		= $value;
			break;
	    }		// email address of sender

	    case 'update':
	    {
			if (strtoupper($value) == 'Y')
			    $update	= true;
			break;
	    }		// email address of sender
	}		// act on specific parameters
    }
    print "    </parms>\n";

    if (is_null($information) && is_string($table))
    {
	$information	= Record::getInformation($table);
    }
    if ($keyname)
	$keyname	= strtoupper($keyname);
    else
    if ($information)
	$keyname	= strtoupper($information['prime']);
    else
	$keyname	= 'Unknown';
 
    if (is_null($idir))
	$msg	.= "Missing mandatory record identifier '$keyname'. ";
    if (strlen($userid) == 0)
    {			// not signed in
	if (strlen($email) == 0)
	    $msg	.= 'Posting user did not self identify. ';
	else
	{
	    $user	= new User(array('email'	=> $email));
	    if ($user->isExisting())
			$username	= $user->get('username');
	    else
			$username	= $email;
	}
    }			// not signed in
    else
	$username	= $userid;

    if (strlen($msg) == 0)
    {			// no errors detected
	if (strlen($subject) == 0)
	{
	    $nlpos		= strpos($message, "\n");
	    if ($nlpos === false)
			$nlpos	= strpos($message, "\r");
	    if (is_int($nlpos))
			$subject	= substr($message, 0, $nlpos);
	    else
	    {
			$subject	= $message;
	    }
	}
	
	if (strlen($email) > 0 && $email == $username)
	{
	    if (strlen($message) > 250)
			$body		= substr($message, 0, 150) . '...' .
							  substr($message, -97);
	    else
			$body		= $message;
	    $message	.= "\nSender's e-mail address: " . 
					   "<a href='mailto:$email?subject=" .
					   rawurlencode($subject) . "&body=" .
					   rawurlencode($body) . "'>$username</a>\n";
	}

	// post a copy of the message to each identified record
	if ($update && $table == 'Blogs')
	{
	    $blog	= new Blog(array('table'	=> $table,
								 'index'	=> $idir));
	    $blog->set('subject',	$subject);
	    $blog->set('text',		$message);
 	    $blog->save(true);	// update database
	}
	else
	{
	    $idirs			= explode(',', $idir);
	    foreach($idirs as $i => $idir)
	    {
			try {
	 	    $blog	= new Blog(array('table'	=> $table,
	 	 	 		        'keyvalue'	=> $idir,
	 	 	  		        'keyname'	=> $keyname,
	 	 		 	        'subject'	=> $subject,
	 	 		 	        'text'		=> $message,
	 	 	 	 	        'username'	=> $username));
	 	    $blog->save(true);	// update database
			} catch (Exception $e) {
	 	    print "    <msg>\n";
	 	    print xmlentities($e->getMessage());
	 	    print "    </msg>\n";
			}
	    }
	}
    }		// no errors detected
    else
    {		// errors in parameters
	print "    <msg>\n";
	print xmlentities($msg);
	print "    </msg>\n";
    }		// errors in parameters

    showTrace();

    // close root node of XML output
    print "</blog>\n";
