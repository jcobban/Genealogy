<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getEventsXml.php													*
 *																		*
 *  This script returns an XML file response containing the index		*
 *  numbers (IDER values) and all fields of the event records			*
 *  associated with the indicated individual or family record.			*
 *  This can be invoked using AJAX to populate a select element of a	*
 *  form.																*
 *																		*
 *  The root node of the file has nodename 'events'.					*
 *  Under this is either a single <msg> tag reporting any errors or:	*
 *  *	a <parms> node containing the parameter[s] passed by			*
 *		method='get'													*
 *  *	zero or more <event> nodes, each with an ider= attribute and	*
 *		sub-nodes for each field in the record.							*
 *																		*
 *  In each record the <eventd> sub-node contains a textual,			*
 *  human-readable, representation of the event date.  If the invoking	*
 *  user is suitably  authorized there is also an <eventdc> sub-node	*
 *  containing the internal representation of the date and an			*
 *  <eventsd> sub-node containing the date sort key.					*
 *																		*
 *  Parameters (one must be supplied):									*
 *		idir			numeric key of the instance of Person for		*
 *						which event records are to be retrieved			*
 *		idmr			numeric key of the instance of Family for		*
 *						which event records are to be retrieved			*
 *																		*
 *  History:															*
 *		2011/02/02		created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/14		use LegacyEVent::getEvents						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/31		use class RecordSet instead of Event::getEvents	*
 *		2023/01/24      protect against XSS                             *
 *																		*
 *  Copyright &copy; 2023 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Event.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . '/common.inc';

$parms	= array();
// validate parameters
foreach($_GET as $key => $value)
{			// loop through all parameters
    switch(strtolower($key))
    {		// act on specific parameters
        case 'idir':
        {
    		$parms['idir']		= $_GET['idir'];
    		$parms['idtype']	= 0;	
    		break;
        }

        case 'idmr':
        {
    		$parms['idir']		= $_GET['idmr'];
    		$parms['idtype']	= 0;	
    		break;
        }
    }		// act on specific parameters
}			// loop through all parameters

if (!array_key_exists('idir', $parms))
    $msg	.= 'Missing mandatory parameter idir or idmr. ';

// execute the query
if (strlen($msg) == 0)
{		// OK to proceed
    $events	= new RecordSet('Events', $parms);
}		// OK to proceed

// display the results as an XML document
print("<?xml version='1.0' encoding='UTF-8'?>\n");
// top node of XML result
print("<events>\n");

// return all of the passed parameters so the requesting page
// can apply the response information to the specific element
print "    <parms>\n";
foreach($_GET as $fldname => $value)
    print "    <$fldname>" . htmlspecialchars($value) . "</$fldname>\n";
print "    </parms>\n";

if (strlen($msg) > 0)
    print "<msg>$msg</msg>\n";
else
{		// OK
    foreach($events as $ie => $event)
    {		// loop through all events
        $event->toXml('event');
    }		// loop through all events
}		// OK
print("</events>\n");	// close off top node of XML result
