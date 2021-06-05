<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  mergeLocationsXml.php												*
 *																		*
 *  Merge a set of locations into a single location.  This requires		*
 *  updating all references to the locations being merged				*
 *																		*
 *  History:															*
 *		2010/08/27		created											*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		use canUser method to validate authorization	*
 *		2013/04/26		merge any additional information from deleted	*
 *						locations into the target location				*
 *						use class LegacyLocation						*
 *		2015/01/07		change require to require_once					*
 *		2015/02/18		use Event::updateEvents,						*
 *						LegacyIndiv::updateIndivs, and					*
 *						LegacyFamily::updateFamilies					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<update>\n";

// working variables
$msg		= '';
$to			= null;		// IDLR
$from		= null;		// array of IDLRs
$toLocation		= null;		// target instance of Location
$fromLocation	= null;		// source instance of Location

// determine if permitted to add children
if (!canUser('edit'))
{		// take no action
    $msg	.= 'Not authorized to merge locations. ';
}		// take no action

// validate parameters
print "    <parms>\n";
foreach($_POST as $key => $value)
{		// loop through all parameters
    print "\t<$key>$value</$key>\n";
    switch(strtolower($key))
    {	// act on specific parameters
        case 'to':
        {	// target record
    		$to		= $_POST['to'];
    		$toLocation	= new Location(array('idlr' => $to));
    		if (!$toLocation->isExisting())
    		    print "\t<msg>Undefined target IDLR=$to</msg>\n";
    		break;
        }	// target record

        case 'from':
        {	// source record(s)
    		$from	= explode(',', $_POST['from']);
    		break;
        }	// source record(s)
    }	// act on specific parameters
}
print "    </parms>";

if (is_null($to))
    $msg	.= 'Missing mandatory parameter to. ';
if (is_null($from))
    $msg	.= 'Missing mandatory parameter from. ';

if (strlen($msg) > 0)
{			// errors
    print "    <msg>$msg</msg>\n";
}			// errors
else
{			// no errors
    // update references by table and field
    $parms		= array("IDLREvent" => $from);
    $setParms	= array("IDLREvent" => $to);
    $events		= new RecordSet('Events', $parms);
    $events->update($setParms, true);
    $parms		= array("IDLRBirth" => $from);
    $setParms	= array("IDLRBirth" => $to);
    $persons	= new RecordSet('Persons', $parms);
    $persons->update($setParms, true);
    $parms		= array("IDLRChris" => $from);
    $setParms	= array("IDLRChris" => $to);
    $persons	= new RecordSet('Persons', $parms);
    $persons->update($setParms, true);
    $parms		= array("IDLRDeath" => $from);
    $setParms	= array("IDLRDeath" => $to);
    $persons	= new RecordSet('Persons', $parms);
    $persons->update($setParms, true);
    $parms		= array("IDLRBuried" => $from);
    $setParms	= array("IDLRBuried" => $to);
    $persons	= new RecordSet('Persons', $parms);
    $persons->update($setParms, true);
    $parms		= array("IDLRMar" => $from);
    $setParms	= array("IDLRMar" => $to);
    $families	= new RecordSet('Families', $parms);
    $families->update($setParms, true);

    // delete the now redundant location record[s]
    $where		= '';
    $or		= '';
    foreach($from as $fromidlr)
    {		// loop through all from values
        $fromLocation	= new Location(array('idlr' => $fromidlr));
        if ($toLocation->getLatitude() == 0.0 &&
    		$toLocation->getLongitude() == 0.0)
        {
    		$toLocation->setLatitude($fromLocation->getLatitude());
    		$toLocation->setLongitude($fromLocation->getLongitude());
    		$toLocation->set('zoom',
    				 $fromLocation->get('zoom'));
        }
        if (strlen($toLocation->get('boundary')) == 0)
    		$toLocation->set('boundary',
    				 $fromLocation->get('boundary'));
        if (strlen($toLocation->get('notes')) == 0)
    		$toLocation->set('notes',
    				 $fromLocation->get('notes'));
        $fromLocation->delete(true);
    }		// loop through all from values

    // save any changed made to the target location
    $toLocation->save();
    print "<cmd>" . $toLocation->getLastSqlCmd() . "</cmd>\n";
}			// no errors

// close the XML document
print "</update>\n";
