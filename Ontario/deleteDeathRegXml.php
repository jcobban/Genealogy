<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteDeathRegXml.php												*
 *																		*
 *  Delete an existing death registration record from Deaths.			*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.												*
 *																		*
 *  History:															*
 *		2014/08/28		created											*
 *		2014/12/25		report count of deleted records					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once	__NAMESPACE__ . '/Death.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print "<deleted>\n";

    // include info on parameters
    print "    <parms>\n";
    $domain		= 'CAON';
    $rownum		= null;
    $regYear		= null;
    $regNum		= null;
    foreach($_POST as $key => $value)
    {			// loop through all parameters
	print "\t<$key>$value</$key>\n";
	switch(strtolower($key))
	{		// act on specific keys
	    case 'domain':
	    {
		$domain		= $value;
		break;
	    }		// registration domain

	    case 'regyear':
	    {
		$regYear	= $value;
		break;
	    }		// registration year

	    case 'regnum':
	    {
		$regNum		= $value;
		break;
	    }		// registration num

	    case 'rownum':
	    {
		$rownum		= $value;
		break;
	    }		// rownum in input form

	}		// act on specific keys
    }			// loop through all parameters
    print "    </parms>\n";

    // validate parameters
    if (is_null($rownum))
	$msg	.= 'Missing mandatory parameter rownum. ';
    if (is_null($regYear))
	$msg	.= 'Missing mandatory parameter RegYear. ';
    if (is_null($regNum))
	$msg	.= 'Missing mandatory parameter RegNum. ';
    
    // current user must be authorized to update the database
    if (!canUser('edit'))
    {		// take no action
	$msg	.= 'User not authorized to update database. ';
    }		// take no action

    try
    {
	$deathreg	= new Death($domain, $regYear, $regNum);
    }		// try
    catch(Exception $e)
    {		// catch failure of new Death
	$msg	.= $e->getMessage();
    }		// catch failure of new Death

    if (strlen($msg) > 0)
    {		// problems detected
	print "    <msg>\n\t" . 
	      htmlentities($msg,ENT_XML1) . 
	      "\n    </msg>\n";
    }		// problems detected
    else
    {		// OK to delete marriage
	$count	= $deathreg->delete(true);
	print "<count>$count</count>\n";
    }		// OK to delete marriage
     
    print "</deleted>\n";

?>
