<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteCountyMarriageReportXml.php					*
 *									*
 *  Delete an existing county marriage report record from table		*
 *  CountyMarriageReports.						*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.						*
 *									*
 *  History:								*
 *	2016/01/30	created						*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
require_once	__NAMESPACE__ . '/CountyMarriageReport.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    header('Content-Type: text/xml');
    print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print "<deleted>\n";

    // include info on parameters
    print "    <parms>\n";
    $domain		= 'CAON';
    $volume		= null;
    $reportNo		= null;
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
    
    // current user must be authorized to update the database
    if (!canUser('edit'))
    {		// take no action
	$msg	.= 'User not authorized to update database. ';
    }		// take no action

    try
    {
	$getParms	= array('domain'	=> $domain, 
				'volume'	=> $volume,
				'reportno'	=> $reportNo);
	$report	= new CountyMarriageReport($getParms);
	$report->toXml('report');
    }		// try
    catch(Exception $e)
    {		// catch failure of new Death
	$msg	.= $e->getMessage();
    }		// catch failure of new Death

    if (strlen($msg) > 0)
    {		// problems detected
	print "    <msg>\n\t" . 
	      xmlentities($msg) . 
	      "\n    </msg>\n";
    }		// problems detected
    else
    {		// OK to delete marriage
	$count	= $report->delete(true);
	print "<count>$count</count>\n";
    }		// OK to delete marriage
     
    print "</deleted>\n";

?>
