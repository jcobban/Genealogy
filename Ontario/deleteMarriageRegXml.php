<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteMarriageRegXml.php						*
 *									*
 *  Delete a marriage registration record.				*
 *									*
 *  Parameters:								*
 *									*
 *  History:								*
 *	2013/01/09	created						*
 *	2013/11/27	handle database server failure gracefully	*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/10/11	renamed from MarriageRegDelete.php to		*
 *			deleteMarriageRegXml.php for consistency	*
 *			use class Marriage				*
 *	2015/07/02	access PHP includes using include_path		*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
header("Content-Type: text/xml");
require_once	__NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print "<deleted>\n";

    // initial field value defaults
    $domain		= 'CAON';
    $rownum		= null;
    $regNum		= null;
    $regYear		= null;

    // include info on parameters
    print "    <parms>\n";
    foreach($_POST as $key => $value)
    {
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
    }
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

    // try to get marriage registration
    try
    {
	$marriagereg	= new Marriage($domain, $regYear, $regNum);
    }		// try
    catch(Exception $e)
    {		// catch failure of new Marriage
	$msg	.= $e->getMessage();
    }		// catch failure of new Marriage

    if (strlen($warn) > 0)
    {
	print "<div class='warning'>$warn</div>\n";
    }
    if (strlen($msg) > 0)
    {		// problems detected
	print "    <msg>\n\t" . 
	      xmlentities($msg) . 
	      "\n    </msg>\n";
    }		// problems detected
    else
    {		// OK to delete marriage
	$count	= $marriagereg->delete('cmd');
    }		// OK to delete marriage

    print "</deleted>\n";
?>
