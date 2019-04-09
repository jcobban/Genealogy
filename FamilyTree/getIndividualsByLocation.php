<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getIndividualsByLocation.php										*
 *																		*
 *  Get a list of individuals who have events referencing a specified	*
 *  location.															*
 *																		*
 *  Parameters:															*
 *		idlr	unique numeric identifier of the location to search for	*
 *		limit	maximum number of records of each type to display		*
 *				default 25												*
 *		delete	if present and value is 'yes' delete location without	*
 *				asking for permission									*
 *																		*
 *  History:															*
 *		2010/09/27		created											*
 *		2010/10/03		put in death date, not repeat of birth date		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/29		add option to delete unused location			*
 *		2010/12/04		add help page									*
 *		2010/12/21		handle exception from new LegacyLocation		*
 *		2011/11/15		editing a single marriage is now done through a	*
 *						sub-menu of editMarriages.php					*
 *		2012/01/13		change class names								*
 *		2012/05/03		add auto-delete capability						*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/12		remove tables used for layout					*
 *		2014/09/19		use LegacyLocation::delete to delete record		*
 *		2014/11/30		use LegacyIndiv::getPersons, 					*
 *						Event::getEvents, and 							*
 *						LegacyFamily::getFamilies to identify			*
 *						references, and describe type of reference		*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/03/12		include birth and death dates of individuals	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/14		parm for getIndivids to combine fields by OR	*
 *						was wrong										*
 *		2016/01/19		include http.js									*
 *		2016/12/30		catch exception from							*
 *						Event::getAssociatedRecord						*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/31		use RecordSet instead of Event::getEvents		*
 *		2017/12/12		use PersonSet instead of Person::getPersons		*
 *						limit the maximum number of events of each		*
 *						type that will be displayed						*
 *		2018/02/18		report even if IDLR is no longer defined		*
 *		2018/02/25		use Template									*
 *		2019/01/21      delete button was not displayed for unused      *
 *		2019/02/19      pass language to next scripts                   *
 *		                use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

    // validate parameters
    $idlr		    = null;
    $limit		    = 25;
    $autodelete		= false;
    $lang		    = 'en';
    $indcount		= 0;
    $marcount		= 0;
    $evtcount		= 0;

    foreach($_GET as $key => $value)
    {			// loop through all parameters
		switch(strtolower($key))
		{		// act on parameters
		    case 'idlr':
		    {
				$idlr		    = intval($value);
				break;
		    }		// IDLR

		    case 'limit':
		    {
				$limit		    = intval($value);
				break;
		    }		// Limit

		    case 'lang':
		    {
				if (strlen($value) >= 2)
				    $lang		= strtolower(substr($value, 0, 2));
				break;
		    }		// Limit

		    case 'delete':
		    {
				if (strtolower($value) == 'yes')
				    $autodelete	= true;
				break;
		    }
		}		// act on parameters
    }			// loop through all parameters

    if (is_null($idlr))
    {
		$msg		.= 'Missing mandatory parameter idlr. ';
		$locName	= 'Unknown';
    }

    // identify action script
    if (canUser('Edit'))
    {		// permitted to update
		$page	= 'editIndivid.php';
    }		// permitted to update
    else
    {		// can only view
		$page	= 'Person.php';
    }		// can only view

    if (strlen($msg) == 0)
    {		// no messages
		// get the location being used for the search
		$location	= new Location(array('idlr' => $idlr));
		if (!$location->isExisting())
		    $warn	.= "<p>getIndividualsByLocation.php: " . __LINE__ . 
						   " Unable to get Location for IDLR=$idlr. ";
		$locName	= $location->getName();

		// get individuals who have the location in one of the events
		// recorded in the individual record
		$indParms	= array(array('idlrbirth'	    => $idlr,
							      'idlrchris'	    => $idlr,
							      'idlrdeath'	    => $idlr,
							      'idlrburied'	    => $idlr),
							      'limit'			=> $limit,
							      'order'			=>
								  'Surname, GivenName, BirthSD, DeathSD');
		$indivs		= new PersonSet($indParms);
		$indInfo	= $indivs->getInformation();
		$indcount	= $indInfo['count'];
		$count		= $indcount;
    
		// get families which have the location in one of the events
		// recorded in the Family Record
		$marParms	= array('idlrmar'	=> $idlr,
							'limit'		=> $limit,
							'order'		=> '`idmr`');
		$families	= new RecordSet('Families', $marParms);
		$famInfo	= $families->getInformation();
		$famcount	= $famInfo['count'];
		$count		+= $famcount;

		// get events which have the location in one of the events
		// recorded in the Event Record table
		$getParms	= array('idlrevent'	=> $idlr,
							'limit'		=> $limit);
		$events		= new RecordSet('Events',$getParms);
		$evtInfo	= $events->getInformation();
		$evtcount	= $evtInfo['count'];
		$count		+= $evtcount;
    }		// no messages

    $template		= new FtTemplate("getIndividualsByLocation$lang.html");

    $template->set('IDLR', $idlr);
    $template->set('LOCNAME', $locName);
    $prefix		= $locName;
    if (strlen($prefix) > 4)
		$prefix		= substr($prefix, 0, 4);
    $template->set('PREFIX', $prefix);

    if ($count > 0)
    {		// some individuals with refs in main record
		foreach($indivs as $idir => $person)
		{		// loop through individuals
		    if ($person->get('gender') == 0)
				$gender			= 'male';
		    else
				$gender			= 'female';
		    if ($person->get('idlrbirth') == $idlr)
				$eventType		= 'Birth';
		    else
		    if ($person->get('idlrchris') == $idlr)
				$eventType		= 'Christening';
		    else
		    if ($person->get('idlrdeath') == $idlr)
				$eventType		= 'Death';
		    else
		    if ($person->get('idlrburied') == $idlr)
				$eventType		= 'Burial';
		    else
				$eventType		= 'Unknown Event';
		    $name		= $person->getName(Person::NAME_INCLUDE_DATES);
		    $person['eventType']	= $eventType;
		    $person['mpage']		= "$page?idir=$idir&lang=$lang";
		    $person['dname']		= $name;
		    $person['gender']		= $gender;
		}		// loop through individuals

		$template['personEvents']->update( $indivs);

		foreach($events as $ider => $event)
		{		// loop through events
		    $idet			= $event->get('idet');
		    $eventType			= Event::$eventText[$idet];
		    $eventType			= ucfirst($eventType);
		    if ($event->get('idtype') == Event::IDTYPE_INDIV)
		    {	// individual event
				$idir			= $event->getIdir();
				try {
				    $person		= $event->getAssociatedRecord();
				    if ($person->get('gender') == 0)
						$gender		= 'male';
				    else
						$gender		= 'female';
				} catch(Exception $e) {
				    $gender		= 'other';
				}
				$name	= $person->getName(Person::NAME_INCLUDE_DATES);
				$event['eventType']	= $eventType;
				$event['mpage']		= "$page?idir=$idir&lang=$lang";
				$event['dname']		= $name;
				$event['gender']	= $gender;
		    }	// individual event
		    else
		    if ($event->get('idtype') == Event::IDTYPE_MAR)
		    {	// family event
				$family		= $event->getAssociatedRecord();
				$idmr		= $family->get('idmr');
				$idir		= $family->get('idirhusb');
				if ($idir == 0)
				    $idir	= $family->get('idirwife');
				if (canUser('edit'))
				    $mpage	= "editMarriages.php?idir=$idir&idmr=$idmr&lang=$lang";
				else
				    $mpage	= "Person.php?idir=$idir&lang=$lang";
				$event['eventType']		= $eventType;
				$event['mpage']			= $mpage;
				$event['dname']			= $family->getName();
				$event['gender']		= 'unknown';
		    }	    // family event
		}		    // loop through events

		$template['generalEvents']->update( $events);

		foreach($families as $idmr => $family)
		{		    // loop through families
		    $marrEvent	= $family->getMarEvent();
		    if ($marrEvent->get('ider') == 0)
		    {		// not already handled
				$idmr		                = $family->get('idmr');
				$idir		                = $family->get('idirhusb');
				if ($idir == 0)
				    $idir	                = $family->get('idirwife');
				if (canUser('edit'))
				    $mpage		= "editMarriages.php?idir=$idir&idmr=$idmr&lang=$lang";
				else
				    $mpage		= "Person.php?idir=$idir&lang=$lang";
				$family['mpage']		    = $mpage;
				$family['familyName']		= $family->getName();
				$family['gender']		    = 'unknown';
		    }	    // not already handled
		}		    // loop through families

	    if ($indcount > $limit)
	    {
			$indcount		= number_format($indcount);
			$template['indOver']->update(array('indcount'	=> $indcount,
								               'limit'	    => $limit));
	    }
	    else
			$template['indOver']->update( null);
	    if ($famcount > $limit)
	    {
			$famcount		= number_format($famcount);
			$template['famOver']->update(array('famcount'	=> $famcount,
								               'limit'	    => $limit));
	    }
	    else
			$template['famOver']->update( null);
	    if ($evtcount > $limit)
	    {    
			$evtcount		= number_format($evtcount);
			$template['evtOver']->update(array('evtcount'	=> $evtcount,
								               'limit'	    => $limit));
	    }
	    else
			$template['evtOver']->update( null);
		$template['marriageEvents']->update($families);
		$template['nofacts']->update(null);
		$template['deletedLocation']->update(null);
		$template['delForm']->update(null);
    }		    // references
    else
    {		    // no facts use this location
		$template['references']->update(null);
		$template['nofacts']->update(array('locName'	=> $locName));
		if ($autodelete)
		{	    // delete automatically
		    $result		= $location->delete(false);
		    $template['deletedLocation']->update(array('locName' => $locName));
		    $template['delForm']->update(null);
		}		// delete automatically
		else
		{		// ask user if we can delete
		    $template['deletedLocation']->update(null);
		    if ($debug)
				$debugyn	= 'Y';
		    else
				$debugyn	= 'N';
		    $template['delForm']->update(array('locName'	=> $locName,
							                   'idlr'		=> $idlr,
							                   'debug'		=> $debugyn));
		}		// ask user if we can delete
    }		    // no facts use this location

    $template->display();
