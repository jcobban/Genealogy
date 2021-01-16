<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteCitationXml.php												*
 *										                                *
 *  Delete an existing citation record from tblSX.						*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.												*
 *																		*
 *  History:															*
 *		2010/08/28		created											*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/18		return parameter values both as elements under	*
 *						<parms>											*
 *						and as attribute values of <deleted> element.	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/05		use canUser() to validate access				*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/06/25		use class Citation								*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/07		change require to require_once					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *      2020/12/05      correct XSS vulnerabilities                     *
 *                      add messages                                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . "/Citation.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";

$idsx	            = 0;
$idsxtext           = null;
$rownum             = '';
$formname           = '';
$parmsText          = '';
if (isset($_POST) && count($_POST) > 0)
{		                        // parameters passed by method=post
    foreach($_POST as $key => $value)
    {		                    // loop through all parameters
        $parmsText      .= "    <$key>" .
                            htmlspecialchars($value) . "</$key>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'idsx':
            {
                if (ctype_digit($value))
                    $idsx	        = $value;
                else
                    $idsxtext       = htmlspecialchars($value);
			    break;
            }		// IDSX

            case 'rownum':
            {
                $rownum             = htmlspecialchars($value);
                break;
            }

            case 'formname':
            {
                $formname           = htmlspecialchars($value);
                break;
            }
		}		// act on specific parameters
	}			// loop through all parameters
}		                        // parameters passed by method=post

print "<deleted idsx='$idsx' rownum='$rownum' formname='$formname'>\n";
// include info on parameters
print "    <parms>\n$parmsText</parms>\n";

// determine if permitted to add children
if (!canUser('edit'))
{		// take no action
	$msg	.= 'Not authorized to delete citation. ';
}		// take no action

// validate parameters
if (is_string($idsxtext))
    $msg	.= "IDSX value '$idsxtext' is invalid. ";
else
if (is_null($idsx))
	$msg	.= 'Missing mandatory parameter idsx. ' . $idsxtext;

showTrace();

if (strlen($msg) > 0)
{
	print "<msg>$msg</msg>\n";
}
else
{                       // delete requested record
    $citation	    = new Citation(array('idsx' => $idsx));
    $citation->delete("cmd");
}                       // delete requested record 
print "</deleted>\n";
