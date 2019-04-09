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
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . "/Citation.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<deleted";
$idsx	= 0;
foreach($_POST as $key => $value)
{			// loop through all parameters
	print " $key='$value'";
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'idsx':
	    {
		$idsx	= $value;
		break;
	    }		// IDSX
	}		// act on specific parameters
}			// loop through all parameters
print ">\n";

// include info on parameters
print "    <parms>\n";
foreach($_POST as $key => $value)
{
	print "\t<$key>$value</$key>\n";
}
print "    </parms>\n";

// determine if permitted to add children
if (!canUser('edit'))
{		// take no action
	$msg	.= 'Not authorized to delete citation. ';
}		// take no action

// validate parameters
if ($idsx == 0)
	$msg	.= 'Missing mandatory parameter idsx. ';

showTrace();

if (strlen($msg) > 0)
{
	print "<msg>$msg</msg>\n";
	print "</deleted>\n";
	exit;
}

$citation	= new Citation(array('idsx' => $idsx));
$citation->delete("cmd");
 
print "</deleted>\n";
