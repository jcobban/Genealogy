<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteCensusXml.php							*
 *									*
 *  Handle a request to delete an individual Census from the		*
 *  Censuses table.  This script generates an XML file, so it can	*
 *  be invoked from Javascript						*
 *									*
 *  Parameters (passed by method='post'):				*
 *	CensusId	census identifier				*
 *									*
 *  History:								*
 *	2016/01/21	created						*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // emit the xml header
    header("content-type: text/xml");
    print("<?xml version='1.0' encoding='utf-8'?>\n");
    print "<deleted>\n";

    print "    <parms>\n";
    $getParms		= array();
    foreach($_POST as $key => $value)
    {		// loop through all parameters
	print "\t<$key>$value</$key>\n";
	switch(strtolower($key))
	{
	    case 'censusid':
	    case 'name':
	    {		// key of Censuses table
		$getParms[$key]	= $value;
		break;
	    }		// key of Censuses table
	}
    }		// loop through all parameters
    print "    </parms>\n";
			
    if (!canUser('edit'))
    {		// not authorized
	$msg	.= 'User not authorized to delete census. ';
    }		// not authorized

    try {
	// identify the specific Census
	$Census	= new Census($getParms);
    } catch(Exception $e) {
	$msg		.= "No matching Census. ";
	$Census	= null;
    }

    if ($Census && !($Census->isExisting()))
	$msg		.= "No matching Census. ";

    // expand only if authorized
    if (strlen($msg) == 0)
    {			// user is authorized to update
	$Census->delete(true);
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
