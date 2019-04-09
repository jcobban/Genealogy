<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteEventXml.php													*
 *																		*
 *  Handle a request to delete an individual event in 					*
 *  the Legacy family tree database.  This file generates an			*
 *  XML file, so it can be invoked from Javascript.						*
 *																		*
 *  Parameters:															*
 *		idime		unique numeric key of instance of Record			*
 *		cittype		class of event, identifies location of event        *
 *		            in Record		                                    *
 *																		*
 *  History:															*
 *		2010/08/10		Created											*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/05		use canUser() to check authorization			*
 *		2011/12/05		add support to LDS events in LegacyIndiv		*
 *		2011/12/24		add support for events in LegacyFamily			*
 *		2012/01/13		change class names								*
 *						script name changed to indicate it returns XML	*
 *		2012/12/08		do not fail on request to delete 				*
 *						event for invalid key value.					*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/06/15		use LegacyCitations::deleteCitations			*
 *		2014/12/02		display diagnostic information					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/29		include contents of record that is deleted in	*
 *						the response.									*
 *		2017/09/12		use set(										*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2019/01/02      Citation::deleteCitations replaced by           *
 *		                CitationSet::delete                             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the xml header
print("<?xml version='1.0' encoding='utf-8'?>\n");
print "<deleted>\n";

print "    <parms>\n";
$idime		= null;
$cittype		= 30;	// default individual event in tbler
foreach($_POST as $key => $value)
{		// loop through all parameters
print "\t<$key>$value</$key>\n";
switch($key)
{	// act on specific parameter
    case 'ider':	// old name
    case 'idime':	// new name
    {
	$idime		= $value;
	break;
    }	// record identifier

    case 'cittype':
    {	// class of event
	$cittype	= $value;
	break;
    }	// class of event
}	// act on specific parameter
}		// loop through all parameters
print "    </parms>\n";
		
// get the updated values of the fields in the record

if (!canUser('edit'))
{		// not authorized
$msg	.= 'User not authorized to delete event. ';
}		// not authorized

if ($idime == null)
$msg		.= 'Missing mandatory parameter idime=. ';

// output any trace or warning messages
if (strlen($warn))
{
print "<div class='warning'>$warn</div>\n";
}
 
if (strlen($msg) == 0)
{		// no errors detected
	switch($cittype)
	{	// act on specific event class
	    case Citation::STYPE_EVENT:
	    case Citation::STYPE_MAREVENT:
	    {		// IDIME points to tblER record
			// delete the indicated event entry
			try {
			    $event		= new Event(array('ider' => $idime));
			    $event->toXml('event');
			    $event->delete(true);
			} catch (Exception $e) {
			    // already deleted
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// IDIME points to tblER record
	
	    case Citation::STYPE_LDSB:	// 15  Baptism
	    {		// IDIME points to tblIR record
			// clear the event
			try {
			    $person	= new Person(array('idir' => $idime));
			    $person->toXml('indiv');
			    $person->set('baptismd',	'');
			    $person->set('baptismsd',	-99999999);
			    $person->set('baptismkind',	1);
			    $person->set('idtrbaptism',	1);
			    $person->set('baptismnote',	'');
			    $person->set('ldsb',	0);
			    $person->save(true);		// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// LDS Baptism
	
	    case Citation::STYPE_LDSE:	// 16  Endowment
	    {		// IDIME points to tblIR record
			// clear the event
			try {
			    $person	= new Person(array('idir' => $idime));
			    $person->toXml('indiv');
			    $person->set('endowd',	'');
			    $person->set('endowsd',	-99999999);
			    $person->set('idtrendow',	1);
			    $person->set('endownote',	'');
			    $person->set('ldse',	0);
			    $person->save(true);		// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// LDS Endowment
	
	    case Citation::STYPE_LDSS:	// 18  Sealed to Spouse
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('seald',	'');
			    $family->set('sealsd',	-99999999);
			    $family->set('idtrseal',	1);
			    $family->set('ldss',	0);
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// LDS Sealed to Spouse
	
	    case Citation::STYPE_NEVERMARRIED:	// 19	never married
	    case Citation::STYPE_MARNEVER:	// 22	never married
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('notmarried',	0);
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// not married indicator
	
	    case Citation::STYPE_MAR:	// 20 marriage event
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('mard',	'');
			    $family->set('marsd',	-99999999);
			    $family->set('idlrmar',	1);
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// marriage event
	
	    case Citation::STYPE_MARNOTE:	// 21 marriage note
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('notes',	'');
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// marriage notes
	
	    case Citation::STYPE_MARNOKIDS:// 23 no children
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('nochildren',		0);
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// not married indicator
	
	    case Citation::STYPE_MAREND:	// 24 marriage ended event
	    {		// IDIME points to tblMR record
			// clear the event
			try {
			    $family	= new Family(array('idmr' => $idime));
			    $family->toXml('family');
			    $family->set('marendd',		'');
			    $family->set('marendsd',		-99999999);
			    $family->save(true);	// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// marriage ended event
	
	    case Citation::STYPE_LDSC:	// 26  Confirmation
	    {		// IDIME points to tblIR record
			// clear the event
			try {
			    $person	= new Person(array('idir' => $idime));
			    $person->toXml('indiv');
			    $person->set('confirmationd',	'');
			    $person->set('confirmationsd',	-99999999);
			    $person->set('confirmationkind',	1);
			    $person->set('idtrconfirmation',	1);
			    $person->set('confirmationnote',	'');
			    $person->set('ldsc',		0);
			    $person->save(true);		// write to database
			} catch (Exception $e) {
			    // nothing to delete
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// LDS Confirmation
	
	    case Citation::STYPE_LDSI:	// 27  Initiatory
	    {		// IDIME points to tblIR record
			// clear the event
			try {
			    $person	= new Person(array('idir' => $idime));
			    $person->toXml('indiv');
			    $person->set('initiatoryd',		'');
			    $person->set('initiatorysd',	-99999999);
			    $person->set('idtrinitiatory',	1);
			    $person->set('initiatorynote',	'');
			    $person->set('ldsi',		0);
			    $person->save(true);		// write to database
			} catch (Exception $e) {
			    $msg	.= "No record for key $idime type $cittype. ";
			}
			break;
	    }		// LDS Initiatory
	
	    default:
	    {		// unsupported
			$msg	.= "Unsupported event type $cittype. ";
			break;
	    }		// unsupported
	
	}	// act on specific event class
	
	// execute the command to delete the event
	if (strlen($msg) == 0)
	{	// command to execute
	    // delete the associated citations if any
	    $parms	        = array("idime"	=> $idime,
	                            "type"	=> $cittype);
	    $citations      = new CitationSet($parms);
	    $result	        = $citations->delete('cmd');
	}	// command to execute
	else
	{
	    print "    <msg>\n";
	    print $msg;
	    print "    </msg>\n";
	}
}		// no errors detected
else
{
    print "    <msg>\n";
    print $msg;
    print "    </msg>\n";
}

// close root node of XML output
print "</deleted>\n";
