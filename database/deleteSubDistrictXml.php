<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteSubDistrictXml.php											*
 *																		*
 *  Handle a request to delete an individual SubDistrict from the		*
 *  SubDistricts table.  This script generates an XML file, so it can	*
 *  be invoked from Javascript											*
 *																		*
 *  Parameters (passed by method='post'):								*
 *		Census															*
 *		District														*
 *		SubDistrict														*
 *		Division														*
 *		Sched															*
 *		Id				id value of invoking HTML element				*
 *																		*
 *  History:															*
 *		2013/07/17		created											*
 *		2013/11/26		handle database server failure gracefully		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2023/01/20      remove support for constructor throw and        *
 *		                delete generating XML string                    *
 *																		*
 *  Copyright &copy; 2023 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the xml header
print("<?xml version='1.0' encoding='utf-8'?>\n");
print "<deleted>\n";

print "    <parms>\n";
$idime		        = null;
$cittype		    = 30;	// default individual event in tbler
$getParms		    = array();
foreach($_POST as $key => $value)
{		// loop through all parameters
    $value          = htmlspecialchars($value);
    print "\t<$key>$value</$key>\n";
    switch(strtolower($key))
    {
        case 'census':
        case 'district':
        case 'subdistrict':
        case 'division':
        case 'sched':
    		$getParms[$key]	= $value;
    		break;
    }
}		// loop through all parameters
print "    </parms>\n";
    			
if (!canUser('edit'))
{		// not authorized
    $msg	        .= 'User not authorized to delete event. ';
}		// not authorized

// identify the specific SubDistrict
$subDistrict	    = new SubDistrict($getParms);
if (!$subDistrict->isExisting())
{
    $msg		    .= "No matching subDistrict. ";
    $subDistrict	= null;
}

// expand only if authorized
if (strlen($msg) == 0)
{			// user is authorized to update
    $subDistrict->delete(false);
    print $subDistrict->toXml('subdistrict');
}			// user is authorized to update
else
{
    print "    <msg>\n";
    print $msg;
    print "    </msg>\n";
}

// close root node of XML output
print "</deleted>\n";
?>
