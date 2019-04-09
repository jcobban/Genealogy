<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deletePictureXml.php												*
 *																		*
 *  Handle a request to delete an individual picture in 				*
 *  the Legacy family tree database.  This file generates an			*
 *  XML file, so it can be invoked from Javascript.						*
 *																		*
 *  Parameters:															*
 *		idbr		unique numeric key of instance of Picture			*
 *																		*
 *  History:															*
 *		2011/05/28		Created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/21		use Picture::delete to delete object		*
 *						rename script to remind that it returns XML		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Picture.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<deleted>\n";

print "    <parms>\n";
foreach($_POST as $key => $value)
    print "\t<$key>$value</$key>\n";
print "    </parms>\n";
    			
// get the updated values of the fields in the record

if (!canUser('edit'))
{		// not authorized
    $msg	.= 'User not authorized to delete picture. ';
}		// not authorized

if (array_key_exists('idbr', $_POST))
{		// idbr to be deleted
    $idbr		= $_POST['idbr'];
}		// idbr to be deleted
else
{
    $idbr		= null;
    $msg		.= 'Missing mandatory parameter idbr=. ';
}
 
if (strlen($msg) == 0)
{		// no errors detected
    // delete the identified picture entry
    try {
        $picture	= new Picture($idbr);
        $picture->delete(true);
    } catch (Exception $e) {
        print "    <msg>" . $e->getMessage() . "</msg>\n";
    }
}		// no errors detected
else
{
    print "    <msg>$msg</msg>\n";
}

// close root node of XML output
print "</deleted>\n";
