<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateIndividXml.php											    *
 *																	    *
 *  Handle a request to update an individual in the Legacy family tree  *
 *  database using AJAX.											    *
 *																	    *
 *  Parameters (passed by method=POST):								    *
 *		id				unique identifier of record					    *
 *																	    *
 *    The following parameters represent fields in the LegacyIndiv	    *
 *    record.  These parameter names are case insensitive.			    *
 *		IDIR														    *
 *		FSID														    *
 *		Surname														    *
 *		SoundsLike		SOUNDEX (set automatically if Surname present)  *
 *		GivenName													    *
 *		Prefix														    *
 *		Title														    *
 *		NameNote													    *
 *		Gender					0 = male, 1 = female, 2 = unknown	    *
 *		BirthSD					sort date as integer				    *
 *		BirthD					date in internal form				    *
 *		IDLRBirth				location index (IDLR)				    *
 *		ChrisSD					sort date as integer				    *
 *		ChrisD					date in internal form				    *
 *		IDLRChris				location index (IDLR)				    *
 *		ChrTerm														    *
 *		DeathSD					sort date as integer				    *
 *		DeathD					date in internal form				    *
 *		IDLRDeath				location index (IDLR)				    *
 *		BuriedSD				sort date as integer				    *
 *		BuriedD					date in internal form				    *
 *		IDLRBuried				location index (IDLR)				    *
 *		Cremated													    *
 *		IDARBirth				address index (IDAR)				    *
 *		IDARChris				address index (IDAR)				    *
 *		IDARDeath				address index (IDAR)				    *
 *		IDARBuried				address index (IDAR)				    *
 *		BirthNote													    *
 *		ChrisNote													    *
 *		DeathNote													    *
 *		BuriedNote													    *
 *		BaptismNote													    *
 *		EndowNote													    *
 *		Living														    *
 *		BaptismSD				sort date as integer				    *
 *		BaptismD				date in internal form				    *
 *		BaptismKind													    *
 *		IDTRBaptism				temple index (IDTR)					    *
 *		LDSB														    *
 *		EndowSD					sort date as integer				    *
 *		EndowD					date in internal form				    *
 *		IDTREndow				temple index (IDTR)					    *
 *		LDSE														    *
 *		ConfirmationD			date in internal form				    *
 *		ConfirmationSD			sort date as integer				    *
 *		ConfirmationKind											    *
 *		IDTRConfirmation		temple index (IDTR)					    *
 *		ConfirmationNote											    *
 *		LDSC														    *
 *		InitiatoryD				date in internal form				    *
 *		InitiatorySD			sort date as integer				    *
 *		IDTRInitiatory			temple index (IDTR)					    *
 *		InitiatoryNote												    *
 *		LDSI														    *
 *		TempleTag													    *
 *		FSSync														    *
 *		FSDups														    *
 *		FSOrdinance													    *
 *		FSLinks														    *
 *		IDMRPref													    *
 *		IDMRParents													    *
 *		IDAR														    *
 *		AncInterest													    *
 *		DecInterest													    *
 *		Tag1														    *
 *		Tag2														    *
 *		Tag3														    *
 *		Tag4														    *
 *		Tag5														    *
 *		Tag6														    *
 *		Tag7														    *
 *		Tag8														    *
 *		Tag9														    *
 *		TagGroup													    *
 *		TagAnc														    *
 *		TagDec														    *
 *		SaveTag														    *
 *		qsTag														    *
 *		SrchTagIGI													    *
 *		SrchTagRG													    *
 *		SrchTagFS													    *
 *		RGExclude													    *
 *		ReminderTag													    *
 *		ReminderTagDeath											    *
 *		TreeNum														    *
 *		LTMP1														    *
 *		LTMP2														    *
 *		AlreadyUsed													    *
 *		UserRef														    *
 *		AncestralRef												    *
 *		Notes														    *
 *		References													    *
 *		Medical														    *
 *		DeathCause													    *
 *		PPCheck														    *
 *		Imported													    *
 *		Relations													    *
 *		IntelliShare												    *
 *		NeverMarried												    *
 *		DirectLine													    *
 *		STMP1														    *
 *		ColorTag													    *
 *		Private														    *
 *		PPExclude													    *
 *		DNA															    *
 *																	    *
 *    The following parameters provide the ability to assign values	    *
 *    to fields in the record with interpretation of external values.   *
 *																	    *
 *		BirthDate				date in external form (e.g. dd mmm yyyy)*
 *		BirthLocation			location name						    *
 *		BirthAddress			address name						    *
 *		ChrisDate				date in external form (e.g. dd mmm yyyy)*
 *		ChrisLocation			location name						    *
 *		DeathDate				date in external form (e.g. dd mmm yyyy)*
 *		DeathLocation			location name						    *
 *		BuriedDate				date in external form (e.g. dd mmm yyyy)*
 *		BuriedLocation			location name						    *
 *		BaptismDate				date in external form (e.g. dd mmm yyyy)*
 *		BaptismTemple			temple name							    *
 *		EndowDate				date in external form (e.g. dd mmm yyyy)*
 *		EndowTemple				temple name							    *
 *		ConfirmationDate		date in external form (e.g. dd mmm yyyy)*
 *		ConfirmationTemple		temple name							    *
 *		InitiatoryDate			date in external form (e.g. dd mmm yyyy)*
 *		InitiatoryTemple		temple name							    *
 *																	    *
 *  History:														    *
 *		2010/09/30		Create										    *
 *		2010/10/23		move connection establishment to common.in	    *
 *		2010/12/21		handle exception thrown by new LegacyLocation   *
 *						use switch statement for field names instead of *
 *						ifs											    *
 *		2012/01/13		change class names							    *
 *		2012/08/01		update events recorded by instances of		    *
 *						Event										    *
 *		2012/09/20		new parameter idcr for editting existing	    *
 *						children									    *
 *		2012/09/28		insert new record into database for new		    *
 *						individual									    *
 *		2013/03/10		Support for boolean flags implemented as	    *
 *						checkboxes									    *
 *		2013/03/14		LegacyLocation constructor no longer saves	    *
 *		2013/04/20		save changes to Child record				    *
 *		2013/12/07		$msg and $debug initialized by common.inc	    *
 *		2014/04/26		formUtil.inc obsoleted						    *
 *		2014/09/26		diagnostic trace moved inside top level node    *
 *						to avoid XML syntax error when debug on		    *
 *		2014/10/24		preferred events moved to instances of		    *
 *						Event and recorded in tblER					    *
 *		2014/11/21		suppress diagnostic output from initialization  *
 *		2014/12/04		diagnostic information moved to $warn		    *
 *						Event processing made common to better support  *
 *						events moved to tblER and permit reordering of  *
 *						events using javascript in editIndivid.js	    *
 *		2015/05/20		event description not cleared between events    *
 *						so passed to subsequent events that do not	    *
 *						have a description field					    *
 *						delete of an event stored in tblIR did not	    *
 *						work because it used						    *
 *						LegacyIndiv::set('idlrxxxx', 1) instead of	    *
 *						LegacyIndiv::clearEvent('xxxx')				    *
 *		2015/07/02		access PHP includes using include_path		    *
 *		2016/01/19		add id to debug trace						    *
 *		2016/04/28		change to signature of LegacyIndiv::toXml	    *
 *		2017/03/19		use preferred parameters to new LegacyIndiv	    *
 *						use preferred parameters to new LegacyFamily    *
 *		2017/08/08		class LegacyChild renamed to class Child	    *
 *		2017/08/23		propertly support traditional events in		    *
 *						main record									    *
 *		2017/08/25		change implementation so $event always set	    *
 *		2017/09/28		change class LegacyEvent to class Event		    *
 *		2017/10/13		class LegacyIndiv renamed to class Person	    *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																	    *
 *  Copyright &copy; 2019 James A. Cobban							    *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<update>\n";

// get original values of some fields in the record
// in case these fields are not updated by this request
try {
    // get the updated values of the fields in the record
    $idir		    		= 0;		// record in tblIR being updated
    $person		    		= null;
    $idcr		    		= 0;		// record in tblCR being updated
    $idmr		    		= 0;		// record in tblMR for parents of child
    $ider		    		= 0;		// record in tblER for event
    $nameChanged			= false;
    $event		    		= null;
    $eventDate				= '';
    $eventDescn				= '';
    $eventLocation			= '';
    $eventIdtr				= 0;
    $eventPreferred			= 0;
    $eventOrder				= null;
    $eventSd				= -99999999;
    $events		    		= array();

    // provide list of parameters in feedback
    print "<parms>\n";

    foreach($_POST as $name => $value)
    {
        // provide list of parameters in feedback
        if (is_string($value))
            print "<$name>" . htmlentities($value,ENT_XML1);
        else
            print "<$name>" . print_r($value, true);
    error_log("<p>updateIndividXml.php: " . __LINE__ .
                "<$name>" . print_r($value, true) . "</$name>\n",
              3,
              $document_root . "/logs/updateIndivid.log");
        $namePattern		= "/([a-zA-Z]+)([0-9]*)/";
        $rgResult		= preg_match($namePattern, $name, $matches);
        if ($rgResult === 1)
        {		// match
            $column		= strtolower($matches[1]);
            $id			= $matches[2];
        }		// match
        else
        {		// no match
            $column		= strtolower($name);
            $id			= '';
        }		// no match

        if (strlen($msg) == 0)	// ignore parameters if error detected
        switch($column)
        {
            case 'id':
            {		// unique identifier of record to update
                $person	        = new Person(array('id' => $value));
                if ($person->isExisting())
                {
                    $idir	    = $person->getIdir();
                    $surname	= $person->getSurname();
                    $givenName	= $person->getGivenName();
                    $gender	    = $person->getGender();
                }
                break;
            }		// unique identifier of record to update

            case 'idir':
            {
                $idir		    = $value;
                $person	        = new Person(array('idir' => $idir));
                if ($person->isExisting())
                {
                    $surname	= $person->getSurname();
                    $givenName	= $person->getGivenName();
                    $gender	    = $person->getGender();
                }
                break;
            }		// idir

            case 'idcr':
            {		// link to parents
                $idcr		    = $value;
                $olddebug	    = $debug;
                $debug		    = false;
                if ($idcr > 0)
                    $childr	    = new Child(array('idcr' => $idcr));
                $debug		    = $olddebug;
                break;
            }		// idcr

            case 'parentsidmr':
            {
                $idmr		    = $value;
                $olddebug	    = $debug;
                $debug		    = false;
                if ($idmr > 0)
                    $family	    = new Family(array('idmr' => $idmr));
                $debug	        = $olddebug;
                break;
            }		// IDMR value of parents

            case 'surname':
            {
                $person->setSurname($value);
                break;
            }		// surname

            case 'givenname':
            {
                $person->setGivenName($value);
                break;
            }		// given name	

            case 'gender':
            {
                $person->setGender($value);
                break;
            }		// gender	

            case 'eventider':
            {			// first field in event description
                $ider		= intval($value);
                if ($ider > 0)
                    $event      = new Event(array('ider'    => $ider));
                else
                    $event      = new Event(array('idir'    => $idir,
                                                  'idtype'  => Event::IDTYPE_INDIV));
                break;
            }			// first field in event description

            case 'birthdate':
            {
                $eventDate	= $value;
                $event		= $person->getBirthEvent(true);
                $event->setDate($value);
                $clearEvent	= 'birth';
                break;
            }

            case 'chrisdate':
            {
                $eventDate	= $value;
                $event		= $person->getChristeningEvent(true);
                $event->setDate($value);
                $clearEvent	= 'chris';
                break;
            }

            case 'deathdate':
            {
                $eventDate	= $value;
                $event		= $person->getDeathEvent(true);
                $event->setDate($value);
                $clearEvent	= 'death';
                break;
            }

            case 'burieddate':
            {
                $eventDate	= $value;
                $event		= $person->getBuriedEvent(true);
                $event->setDate($value);
                $clearEvent	= 'buried';
                break;
            }

            case 'baptismdate':
            {
                $eventDate	= $value;
                $event		= $person->getBaptismEvent(true);
                $event->setDate($value);
                $clearEvent	= 'baptism';
                break;
            }

            case 'endowdate':
            {
                $eventDate	= $value;
                $event		= $person->getEndowEvent(true);
                $event->setDate($value);
                $clearEvent	= 'endow';
                break;
            }

            case 'confirmationdate':
            {
                $eventDate	= $value;
                $event		= $person->getConfirmationEvent(true);
                $event->setDate($value);
                $clearEvent	= 'confirmation';
                break;
            }

            case 'initiatorydate':
            {
                $eventDate	= $value;
                $event		= $person->getInitiatoryEvent(true);
                $event->setDate($value);
                $clearEvent	= 'initiatory';
                break;
            }

            case 'eventdate':
            {			// generic event date
                $eventDate		= $value;
                $clearEvent		= 'event';
                if ($event)
                    $event->setDate($value);
                else
                {
                    error_log('updateIndividXml.php: ' . __LINE__ .
                    " $name='$value', \$event is null, " .
                    ' $_POST=' . print_r($_POST, true) . "\n");
                }
                break;
            }			// generic event date

            case 'birthlocation':
            case 'chrislocation':
            case 'deathlocation':
            case 'buriedlocation':
            case 'eventlocation':
            {			// text of location
                $eventLocation		= $value;
                if ($event)
                    $event->setLocation($value);
                else
                {
                    error_log('updateIndividXml.php: ' . __LINE__ .
                    " $name='$value', \$event is null, " .
                    ' $_POST=' . print_r($_POST, true) . "\n");
                }
    error_log("<p>updateIndividXml.php: " . __LINE__ .
                    " event location=" . $event->get('idlrevent') . "</p>\n", 
              3,
              $document_root . "/logs/updateIndivid.log");
                break;
            }			// text of location

            case 'baptismtemple':
            case 'endowtemple':
            case 'confirmationtemple':
            case 'initiatorytemple':
            {			// selection list of temples
                $eventIdtr		= $value;
                if ($eventIdtr > 0)
                {
                    $event->set('eventidlr', $eventIdtr);
                    $event->set('kind', 1);
                }
                break;
            }			// selection list of temples

            case 'birthaddress':
            case 'chrisaddress':
            case 'deathaddress':
            case 'buriedaddress':
            {			// address not currently supported
                $eventAddress		= $value;
                break;
            }			// address not currently supported

            case 'birthnote':
            case 'chrisnote':
            case 'deathnote':
            case 'buriednote':
            case 'baptismnote':
            case 'endownote':
            case 'confirmationnote':
            case 'initiatorynote':
            case 'eventdescn':
            {
                $eventDescn		= $value;
                if ($event)
                    $event->set('description', $eventDescn);
                else
                {
                    error_log('updateIndividXml.php: ' . __LINE__ .
                    " $name='$value', \$event is null, " .
                    ' $_POST=' . print_r($_POST, true) . "\n");
                }
                break;
            }

            case 'eventpref':
            {
                if (is_string($value) &&
                    strlen($value) > 0 &&
                    strtolower($value) != 'n')
                    $event->set('preferred', 1);
                else
                    $event->set('preferred', 0);
                break;
            }

            case 'eventidet':
            {
                if (is_int($value) || ctype_digit($value))
                {
                    switch(intval($value))
                    {
                        case Event::ET_BIRTH:
                            $clearEvent         = 'birth';
                            break;

                        case Event::ET_CHRISTENING:
                            $clearEvent         = 'chris';
                            break;

                        case Event::ET_DEATH:
                            $clearEvent         = 'death';
                            break;

                        case Event::ET_BURIAL:
                            $clearEvent         = 'buried';
                            break;

                        case Event::ET_LDS_BAPTISM:
                            $clearEvent         = 'baptism';
                            break;

                        case Event::ET_LDS_CONFIRMATION:
                            $clearEvent         = 'confirmation';
                            break;

                        case Event::ET_LDS_INITIATORY:
                            $clearEvent         = 'initiatory';
                            break;

                        case Event::ET_LDS_ENDOWED:
                            $clearEvent         = 'endow';
                            break;

                    }
                    $event->set('idet', $value);
                }
                break;
            }

            case 'eventcittype':
            {
                $cittype		= intval($value);
                break;
            }

            case 'eventorder':
            {
                if (is_int($value) || ctype_digit($value))
                    if ($event)
                        $event->set('order', $value);
                break;
            }

            case 'eventsd':
            {			// this is set by Event::setDate
                $eventSd		= $value;
                break;
            }			// this is set by Event::setDate
            
            case 'eventchanged':
            {
                if (canUser('edit') && $value != '0')
                {		            // user authorized to update database
                    if ($person)
                        $event->setAssociatedRecord($person);
                    if (strlen($eventDate) > 0 ||
                        strlen($eventLocation) > 0 ||
                        strlen($eventDescn) > 0)
                    {	            // have an event
                        if ($idir > 0 && !is_null($event))
                        {
                            $event->save(true);
                            $event->toXml('event');
                            if ($clearEvent != '')
                            {	    // clear event information in tblIR
                                $person->clearEvent($clearEvent);
                                if ($clearEvent == 'birth')
                                {
                                    $person->set('birthsd',
                                        $event->get('eventsd'));
                                }
                            }	    // clear event information in tblIR
                        }
                        else
                        {	        // defer until IDIR known
                            $events[]		= $event;	// track
                        }	        // defer until IDIR known
                    }	            // have an event
                    else
                    {	            // no event information
                        if ($ider > 0)
                        {	        // existing record in tblER
                            $event->delete(true);
                        }	        // existing record in tblER
    
                        if ($clearEvent != '')
                        {	        // clear event information in tblIR
                            $person->clearEvent($clearEvent);
                        }	        // clear event information in tblIR
                    }	            // no event information

                }		            // user authorized to update database

                // reset for next event
                $event			    = null;
                $eventDate		    = '';
                $eventDescn		    = '';
                $eventLocation		= '';
                $clearEvent		    = '';
                $eventPreferred		= 0;
                $eventOrder		    = null;
                $eventSd		    = -99999999;
                $eventIdtr		    = 0;
                $ider			    = 0;
                $idet			    = 0;
                $cittype		    = 0;
                break;
            }		// EventChanged

            case 'cremated':
            case 'living':
            case 'tag':
            case 'taggrp':
            case 'cremated':
            case 'living':
            case 'tag':
            case 'taggrp':
            case 'taganc':
            case 'tagdec':
            case 'savetag':
            case 'qstag':
            case 'srchtagigi':
            case 'srchtagrg':
            case 'srchtagfs':
            case 'rgexclude':
            case 'remindertag':
            case 'remindertagdeath':
            case 'ppcheck':
            case 'imported':
            case 'nevermarried':
            case 'directline':
            case 'private':
            case 'alreadyused':
            case 'templetag':
            {		// boolean fields
                if (is_string($value))
                {		// form passed a string
                    if (strlen($value) > 0)
                    $person->set($column,
                             intval($value));
                    else
                    $person->set($column,
                             0);
                }		// form passed a string
                else
                if (is_array($value))
                {		// form passed an array
                    if (count($value) >= 2)
                    $person->set($column,
                                 intval($value[1]));
                    else
                    if (count($value) >= 1)
                    $person->set($column,
                                 intval($value[0]));
                    else
                    $person->set($column,
                                 0);
                }		// form passed an array
                break;
            }		// boolean fields

            case 'cpidcs':
            {
                if (strlen($value) > 0)
                    $childr->set('idcs',$value);
                break;
            }		// field in tblCR record

            case 'cpreldad':
            {
                if (strlen($value) > 0)
                    $childr->set('idcpdad',$value);
                break;
            }		// field in tblCR record

            case 'cpdadprivate':
            {
                if ($value == 'on')
                    $childr->set('cpdadprivate', 1);
                else
                    $childr->set('cpdadprivate', 0);
                break;
            }		// field in tblCR record

            case 'cprelmom':
            {
                if (strlen($value) > 0)
                    $childr->set('idcpmom',$value);
                break;
            }		// field in tblCR record

            case 'cpmomprivate':
            {
                if ($value == 'on')
                    $childr->set('cpmomprivate', 1);
                else
                    $childr->set('cpmomprivate', 0);
                break;
            }		// field in tblCR record

            case 'title':
            case 'prefix':
            case 'fsid':
            case 'soundslike':	// Soundex
            case 'namenote':
            case 'chrterm':	
            case 'fssync':
            case 'fsdups':
            case 'fsordinance':
            case 'fslinks':
            case 'idmrpref':
            case 'idmrparents':
            case 'idar':
            case 'ancinterest':
            case 'decinterest':
            case 'treenum':
            case 'ltmp':
            case 'userref':
            case 'ancestralref':
            case 'notes':
            case 'references':
            case 'medical':
            case 'deathcause':
            case 'relations':
            case 'intellishare':
            case 'stmp':
            case 'colortag':
            case 'dna':
            case 'ppexclude':
            {		// all other supported fields
                if (is_null($person))
    error_log("<p>updateIndividXml.php: " . __LINE__ .
                    " idir=$idir " . $_SERVER['QUERY_STRING'] . "</p>\n",
              3,
              $document_root . "/logs/updateIndivid.log");
                else
                    $person->set($name, $value);
                break;
            }		// all other supported fields

            case 'treename':
            {		// all other supported fields
                $person->setTreeName($value);
                break;
            }		// all other supported fields

            case 'debug':
            {
                // handled by common code
                break;
            }		// debug
 
            default:
            {
                print "<ignored/>";	// warning in response
                break;
            }		// all other fields
        }		// switch on column name
        print "</$name>\n";	// close off parameter instance
    }		// loop through all parameters

    // close off list of parameters
    print "</parms>\n";

    if (is_null($person))
        $msg	.= "No individual identified. ";

    if ($person && canUser('edit'))
    {		// user authorized to update database
        // write the changes to the individual record
         
    error_log("<p>updateIndividXml.php " . __LINE__ . "</p>\n",
              3,
              $document_root . "/logs/updateIndivid.log");
        $person->save(true);
        $idir	= $person->getIdir();	// in case its a new individual

        // save any pending instances of Event
        foreach($events as $ie => $event)
        {		// create or update events
            if (!is_null($event))
            {
                $event->set('idir', $idir);
                if ($event->get('idtype') == Event::IDTYPE_INDIV &&
                    $event->get('idet') == Event::ET_BIRTH)
                {
                    $person->set('birthsd', $event->get('eventsd'));
                    $person->save(true);
                }
                $event->save(true);
                $event->toXml('event');
            }
        }		// create or update events

        //  include dump of updated record in response
        $person->toXml('indiv',
                       true, 
                       Person::TOXML_INCLUDE_NAMES);

        // check for updates to the child relationship record
        if ($idcr > 0 && !is_null($childr))
        {		// individual updated as child in a family
            $childr->save(true);
            $childr->toXml('child');
        }		// individual updated as child in a family
        else
        if ($idmr > 0 && !is_null($family))
        {		// individual added to family
            try {
                $childr		= $family->addChild($idir);
                $childr->save(true);
                $childr->toXml('child');
            }
            catch(Exception $e)
            {
                $msg		.= "Unable to add Child: " . 
                           $e->getMessage();
            }
        }		// individual added to family
    }			// user authorized to update database
} catch (Exception $e)
{			// global catch for exceptions anywhere in script
    $msg	.= $e->getMessage() . ": trace " .
                   $e->getTraceAsString();
}			// global catch for exceptions anywhere in script

if (strlen($msg) > 0)
    print "<msg>" . $msg . "</msg>\n";

// close off the XML response file
print "</update>\n";
