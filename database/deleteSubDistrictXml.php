<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteSubDistrictXml.php						*
 *									*
 *  Handle a request to delete an individual SubDistrict from the	*
 *  SubDistricts table.  This script generates an XML file, so it can	*
 *  be invoked from Javascript						*
 *									*
 *  Parameters (passed by method='post'):				*
 *	Census								*
 *	District							*
 *	SubDistrict							*
 *	Division							*
 *	Sched								*
 *	Id		id value of invoking HTML element		*
 *									*
 *  History:								*
 *	2013/07/17	created						*
 *	2013/11/26	handle database server failure gracefully	*
 *	2015/07/02	access PHP includes using include_path		*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // emit the xml header
    header("content-type: text/xml");
    print("<?xml version='1.0' encoding='utf-8'?>\n");
    print "<deleted>\n";

    print "    <parms>\n";
    $idime		= null;
    $cittype		= 30;	// default individual event in tbler
    $getParms		= array();
    foreach($_POST as $key => $value)
    {		// loop through all parameters
	print "\t<$key>$value</$key>\n";
	switch(strtolower($key))
	{
	    case 'census':
	    case 'district':
	    case 'subdistrict':
	    case 'division':
	    case 'sched':
	    {		// keys of SubDistricts table
		$getParms[$key]	= $value;
		break;
	    }		// keys of SubDistricts table
	}
    }		// loop through all parameters
    print "    </parms>\n";
			
    if (!canUser('edit'))
    {		// not authorized
	$msg	.= 'User not authorized to delete event. ';
    }		// not authorized

    try {
	// identify the specific SubDistrict
	$subDistrict	= new SubDistrict($getParms);
    } catch(Exception $e) {
	$msg		.= "No matching subDistrict. ";
	$subDistrict	= null;
    }

    if ($subDistrict && !($subDistrict->isExisting()))
	$msg		.= "No matching subDistrict. ";

    // expand only if authorized
    if (strlen($msg) == 0)
    {			// user is authorized to update
	$subDistrict->delete(true);
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
