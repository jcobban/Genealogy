<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteCountyMarriageXml.php						*
 *									*
 *  Delete an existing county marriage report record from table		*
 *  CountyMarriages.							*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.						*
 *									*
 *  History:								*
 *	2016/01/30	created						*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
header("Content-Type: text/xml");
require_once	__NAMESPACE__ . '/CountyMarriage.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print "<deleted>\n";

    // include info on parameters
    print "    <parms>\n";
    $domain		= 'CAON';
    $volume		= null;
    $reportNo		= null;
    $itemNo		= null;
    $groom		= null;
    $bride		= null;
    $rownum		= null;
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

	    case 'volume':
	    {
		$volume		= $value;
		break;
	    }		// registration year

	    case 'reportno':
	    {
		$reportNo	= $value;
		break;
	    }		// registration num

	    case 'itemno':
	    {
		$itemNo		= $value;
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
    if (is_null($volume))
	$msg	.= 'Missing mandatory parameter Volume. ';
    if (is_null($reportNo))
	$msg	.= 'Missing mandatory parameter ReportNo. ';
    if (is_null($itemNo))
	$msg	.= 'Missing mandatory parameter ItemNo. ';
    
    // current user must be authorized to update the database
    if (!canUser('edit'))
    {		// take no action
	$msg	.= 'User not authorized to update database. ';
    }		// take no action

    if (strlen($msg) == 0)
    {			// no errors so far
      try
      {
	$getParms	= array('domain'	=> $domain, 
				'volume'	=> $volume,
				'reportno'	=> $reportNo,
				'itemno'	=> $itemNo);
	$getParms['role']	= 'G';
	$groom	= new CountyMarriage($getParms);
	if ($groom->isExisting())
	    $groom->toXml('groom');
	else
	    $groom	= null;
	$getParms['role']	= 'B';
	$bride	= new CountyMarriage($getParms);
	if ($bride->isExisting())
	    $bride->toXml('bride');
	else
	    $bride	= null;
      }			// try
      catch(Exception $e)
      {			// catch failure of new CountyMarriage
	$msg	.= $e->getMessage();
      }			// catch failure of new CountyMarriage
    }			// no errors so far

    if (is_null($groom) && is_null($bride))
	$msg	.= "No record matching parameters. ";

    if (strlen($msg) > 0)
    {		// problems detected
	print "    <msg>\n\t" . 
	      xmlentities($msg) . 
	      "\n    </msg>\n";
    }		// problems detected
    else
    {		// OK to delete marriage
	if ($groom)
	    $count	= $groom->delete(true);
	if ($bride)
	    $count	+= $bride->delete(true);
	print "<count>$count</count>\n";
    }		// OK to delete marriage
     
    print "</deleted>\n";

?>
