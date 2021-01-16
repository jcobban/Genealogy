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
 *		2014/03/21		use Picture::delete to delete object		    *
 *						rename script to remind that it returns XML		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Picture.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<deleted>\n";

$idbr               = null;
$idbrtext           = null;
$parmsText          = '';

if (isset($_POST) && count($_POST) > 0)
{		                        // parameters passed by method=post
    foreach($_POST as $key => $value)
    {		                    // loop through all parameters
        $parmsText      .= "    <$key>" .
                            htmlspecialchars($value) . "</$key>\n"; 
        switch(strtolower($key))
        {
            case 'idbr':
            {
                if (ctype_digit($value))
                    $idbr       = intval($value);
                else
                    $idbrtext   = htmlspecialchars($value);
                break;
            }
        }
    }                       // loop through parms
}                           // $_POST defined
print "    <parms>\n$parmsText</parms>\n";
    			
if (!canUser('edit'))
{		// not authorized
    $msg	.= 'User not authorized to delete picture. ';
}		// not authorized

if (is_string($idbrtext))
    $msg        .= "Invalid value for IDBR='$idbrtext'. ";
else
{
    if (is_null($idbr))
        $msg		.= 'Missing mandatory parameter IDBR. ';
}
 
if (strlen($msg) == 0)
{		// no errors detected
    // delete the identified picture entry
    $picture	= new Picture($idbr);
    if ($picture->isExisting())
    {
        $picture->delete();
        print "<cmd>" . $picture->getLastSqlCmd() . "</cmd>\n";
    }
    else
        $msg    .= "No instance of Picture defined for IDBR=$idbr. ";
}		// no errors detected

if (strlen($msg) > 0)
{
    print "    <msg>$msg</msg>\n";
}

// close root node of XML output
print "</deleted>\n";
