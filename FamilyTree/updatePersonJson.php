<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updatePersonJson.php                                                *
 *                                                                      *
 *  Handle a request to update an individual in the Legacy family tree  *
 *  database using AJAX.                                                *
 *                                                                      *
 *  Parameters (passed by method=POST):                                 *
 *      id              unique identifier of record                     *
 *                                                                      *
 *    The following parameters represent fields in the LegacyIndiv      *
 *    record.  These parameter names are case insensitive.              *
 *      IDIR                                                            *
 *      FSID                                                            *
 *      Surname                                                         *
 *      SoundsLike      SOUNDEX (set automatically if Surname present)  *
 *      GivenName                                                       *
 *      Prefix                                                          *
 *      Title                                                           *
 *      NameNote                                                        *
 *      Gender                  0 = male, 1 = female, 2 = unknown       *
 *      BirthSD                 sort date as integer                    *
 *      BirthD                  date in internal form                   *
 *      IDLRBirth               location index (IDLR)                   *
 *      ChrisSD                 sort date as integer                    *
 *      ChrisD                  date in internal form                   *
 *      IDLRChris               location index (IDLR)                   *
 *      ChrTerm                                                         *
 *      DeathSD                 sort date as integer                    *
 *      DeathD                  date in internal form                   *
 *      IDLRDeath               location index (IDLR)                   *
 *      BuriedSD                sort date as integer                    *
 *      BuriedD                 date in internal form                   *
 *      IDLRBuried              location index (IDLR)                   *
 *      Cremated                                                        *
 *      IDARBirth               address index (IDAR)                    *
 *      IDARChris               address index (IDAR)                    *
 *      IDARDeath               address index (IDAR)                    *
 *      IDARBuried              address index (IDAR)                    *
 *      BirthNote                                                       *
 *      ChrisNote                                                       *
 *      DeathNote                                                       *
 *      BuriedNote                                                      *
 *      BaptismNote                                                     *
 *      EndowNote                                                       *
 *      Living                                                          *
 *      BaptismSD               sort date as integer                    *
 *      BaptismD                date in internal form                   *
 *      BaptismKind                                                     *
 *      IDTRBaptism             temple index (IDTR)                     *
 *      LDSB                                                            *
 *      EndowSD                 sort date as integer                    *
 *      EndowD                  date in internal form                   *
 *      IDTREndow               temple index (IDTR)                     *
 *      LDSE                                                            *
 *      ConfirmationD           date in internal form                   *
 *      ConfirmationSD          sort date as integer                    *
 *      ConfirmationKind                                                *
 *      IDTRConfirmation        temple index (IDTR)                     *
 *      ConfirmationNote                                                *
 *      LDSC                                                            *
 *      InitiatoryD             date in internal form                   *
 *      InitiatorySD            sort date as integer                    *
 *      IDTRInitiatory          temple index (IDTR)                     *
 *      InitiatoryNote                                                  *
 *      LDSI                                                            *
 *      TempleTag                                                       *
 *      FSSync                                                          *
 *      FSDups                                                          *
 *      FSOrdinance                                                     *
 *      FSLinks                                                         *
 *      IDMRPref                                                        *
 *      IDMRParents                                                     *
 *      IDAR                                                            *
 *      AncInterest                                                     *
 *      DecInterest                                                     *
 *      Tag1                                                            *
 *      Tag2                                                            *
 *      Tag3                                                            *
 *      Tag4                                                            *
 *      Tag5                                                            *
 *      Tag6                                                            *
 *      Tag7                                                            *
 *      Tag8                                                            *
 *      Tag9                                                            *
 *      TagGroup                                                        *
 *      TagAnc                                                          *
 *      TagDec                                                          *
 *      SaveTag                                                         *
 *      qsTag                                                           *
 *      SrchTagIGI                                                      *
 *      SrchTagRG                                                       *
 *      SrchTagFS                                                       *
 *      RGExclude                                                       *
 *      ReminderTag                                                     *
 *      ReminderTagDeath                                                *
 *      TreeNum                                                         *
 *      LTMP1                                                           *
 *      LTMP2                                                           *
 *      AlreadyUsed                                                     *
 *      UserRef                                                         *
 *      AncestralRef                                                    *
 *      Notes                                                           *
 *      References                                                      *
 *      Medical                                                         *
 *      DeathCause                                                      *
 *      PPCheck                                                         *
 *      Imported                                                        *
 *      Relations                                                       *
 *      IntelliShare                                                    *
 *      NeverMarried                                                    *
 *      DirectLine                                                      *
 *      STMP1                                                           *
 *      ColorTag                                                        *
 *      Private                                                         *
 *      PPExclude                                                       *
 *      DNA                                                             *
 *                                                                      *
 *    The following parameters provide the ability to assign values     *
 *    to fields in the record with interpretation of external values.   *
 *                                                                      *
 *      BirthDate               date in external form (e.g. dd mmm yyyy)*
 *      BirthLocation           location name                           *
 *      BirthAddress            address name                            *
 *      ChrisDate               date in external form (e.g. dd mmm yyyy)*
 *      ChrisLocation           location name                           *
 *      DeathDate               date in external form (e.g. dd mmm yyyy)*
 *      DeathLocation           location name                           *
 *      BuriedDate              date in external form (e.g. dd mmm yyyy)*
 *      BuriedLocation          location name                           *
 *      BaptismDate             date in external form (e.g. dd mmm yyyy)*
 *      BaptismTemple           temple name                             *
 *      EndowDate               date in external form (e.g. dd mmm yyyy)*
 *      EndowTemple             temple name                             *
 *      ConfirmationDate        date in external form (e.g. dd mmm yyyy)*
 *      ConfirmationTemple      temple name                             *
 *      InitiatoryDate          date in external form (e.g. dd mmm yyyy)*
 *      InitiatoryTemple        temple name                             *
 *                                                                      *
 *  History of updateIndividXml.php:                                    *
 *      2010/09/30      created                                         *
 *      2010/10/23      move connection establishment to common.in      *
 *      2010/12/21      handle exception thrown by new LegacyLocation   *
 *                      use switch statement for field names instead of *
 *                      ifs                                             *
 *      2012/01/13      change class names                              *
 *      2012/08/01      update events recorded by instances of          *
 *                      Event                                           *
 *      2012/09/20      new parameter idcr for editting existing        *
 *                      children                                        *
 *      2012/09/28      insert new record into database for new         *
 *                      individual                                      *
 *      2013/03/10      Support for boolean flags implemented as        *
 *                      checkboxes                                      *
 *      2013/03/14      LegacyLocation constructor no longer saves      *
 *      2013/04/20      save changes to Child record                    *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/09/26      diagnostic trace moved inside top level node    *
 *                      to avoid XML syntax error when debug on         *
 *      2014/10/24      preferred events moved to instances of          *
 *                      Event and recorded in tblER                     *
 *      2014/11/21      suppress diagnostic output from initialization  *
 *      2014/12/04      diagnostic information moved to $warn           *
 *                      Event processing made common to better support  *
 *                      events moved to tblER and permit reordering of  *
 *                      events using javascript in editIndivid.js       *
 *      2015/05/20      event description not cleared between events    *
 *                      so passed to subsequent events that do not      *
 *                      have a description field                        *
 *                      delete of an event stored in tblIR did not      *
 *                      work because it used                            *
 *                      LegacyIndiv::set('idlrxxxx', 1) instead of      *
 *                      LegacyIndiv::clearEvent('xxxx')                 *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      add id to debug trace                           *
 *      2016/04/28      change to signature of LegacyIndiv::toXml       *
 *      2017/03/19      use preferred parameters to new LegacyIndiv     *
 *                      use preferred parameters to new LegacyFamily    *
 *      2017/08/08      class LegacyChild renamed to class Child        *
 *      2017/08/23      propertly support traditional events in         *
 *                      main record                                     *
 *      2017/08/25      change implementation so $event always set      *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *  History of updatePersonJson.php:                                    *
 *      2019/06/01      created from updateIndividXml.php               *
 *      2019/10/16      support fields prefixed with Christening        *
 *      2021/03/04      do not create duplicate events                  *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// start document
print "{\n";
$comma                      = '';
$debug                      = false;

// get original values of some fields in the record
// in case these fields are not updated by this request
try {
    // get the updated values of the fields in the record
    $idir                   = 0;        // record in tblIR being updated
    $person                 = null;
    $idcr                   = 0;        // record in tblCR being updated
    $idmr                   = 0;        // record in tblMR for parents
    $ider                   = 0;        // record in tblER for event
    $nameChanged            = false;
    $event                  = null;
    $eventDate              = '';
    $eventDescn             = '';
    $eventLocation          = '';
    $eventIdtr              = 0;
    $eventPreferred         = 0;
    $eventOrder             = null;
    $eventSd                = -99999999;
    $events                 = array();

    // provide list of parameters in feedback
    if (isset($_POST) && count($_POST) > 0)
    {
        print "\"parms\":\n\t{\n";

        foreach($_POST as $name => $value)
        {                           // loop through all parameters
            // provide list of parameters in feedback
            print "$comma    \"$name\": ";
            if (is_numeric($value))
                print $value;
            else
            if (is_string($value))
                print json_encode($value);
            else
            if (is_array($value))
            {
                print " {";
                $c              = " ";
                foreach($value as $key => $val)
                {
                    print "$c\"$key\": " . json_encode($val);
                    $c          = ", ";
                }
                print "}"; 
            }
            else
                print json_encode(urlencode(print_r($value, true)));
            $namePattern        = "/([a-zA-Z]+)([0-9]*)/";
            $rgResult           = preg_match($namePattern, $name, $matches);
            if ($rgResult === 1)
            {                       // match
                $column         = strtolower($matches[1]);
                $id             = $matches[2];
            }                       // match
            else
            {                       // no match
                $column         = strtolower($name);
                $id             = '';
            }                       // no match

            // ignore empty values 
            if (strlen($msg) > 0)   // ignore parameters if error detected
                continue;
            
            switch($column)
            {                       // switch on column name
                case 'id':
                case 'idir':
                {                   // unique identifier of record to update
                    if (!ctype_digit($value))
                        break;
                    $person             = Person::getPerson($value);
                    if ($person->isExisting())
                    {
                        $idir           = $person->getIdir();
                        $gender         = $person->getGender();
                    }
                    $priName            = $person->getPriName();
                    $surname            = $priName['surname'];
                    $givenName          = $priName['givenname'];
                    break;
                }                   // unique identifier of record to update

                case 'idcr':
                {                   // link to parents
                    if (!ctype_digit($value))
                        break;
                    $idcr               = $value;
                    if ($idcr > 0)
                        $childr         = new Child(array('idcr' => $idcr));
                    break;
                }                   // idcr

                case 'parentsidmr':
                {
                    if (!ctype_digit($value))
                        break;
                    $idmr               = $value;
                    if ($idmr > 0)
                        $family         = new Family(array('idmr' =>$idmr));
                    break;
                }                   // IDMR value of parents

                case 'surname':
                {
                    $person['surname']      = $value;
                    $priName['surname']     = $value;
                    break;
                }                   // surname

                case 'givenname':
                {
                    $person['givenname']    = $value;
                    $priName['givenname']   = $value;
                    break;
                }                   // given name   

                case 'gender':
                {
                    if ($value == Person::FEMALE &&
                        $person['gender'] == Person::MALE &&
                        $person['idir'] > 0)
                    {               // change gender from male to female
                        error_log('updatePersonJson.php: ' . __LINE__ .
                                    ' change gender to female $_POST=' . 
                                    print_r($_POST,true), 
                                  1, 'webmaster@jamescobban.net');
                    }               // change gender from male to female
                    $person->setGender($value);
                    break;
                }                   // gender   

                case 'eventider':
                {                   // first field in event description
                    if (!ctype_digit($value))
                        break;
                    $ider           = intval($value);
                    if ($ider > 0)
                        $event      = new Event(array('ider'    => $ider));
                    else
                        $event      = new Event(array('idir'    => $idir,
                                                      'idtype'  => Event::IDTYPE_INDIV));
                    break;
                }                   // first field in event description

                case 'birthdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getBirthEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'birth';
                    break;
                }

                case 'chrisdate':
                case 'christeningdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getChristeningEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'chris';
                    break;
                }

                case 'deathdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getDeathEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'death';
                    break;
                }

                case 'burieddate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getBuriedEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'buried';
                    break;
                }

                case 'baptismdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getBaptismEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'baptism';
                    break;
                }

                case 'endowdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getEndowEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'endow';
                    break;
                }

                case 'confirmationdate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getConfirmationEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'confirmation';
                    break;
                }

                case 'initiatorydate':
                {
                    $eventDate          = $value;
                    if ($ider == 0 && $idir != 0)
                    {
                        $tevent         = $person->getInitiatoryEvent(false);
                        if ($tevent)
                            $event      = $tevent;
                    }
                    $event->setDate($value);
                    $clearEvent         = 'initiatory';
                    break;
                }

                case 'eventdate':
                {                   // generic event date
                    $eventDate          = $value;
                    $dateObj            = new LegacyDate($value);
                    $string             = $dateObj->toString();
                    $message            = $dateObj->getMessage();
                    $clearEvent         = 'event';
                    if ($event)
                    {
                        $event->setDate($dateObj);
                    }
                    else
                    {
                        error_log('updatePersonJson.php: ' . __LINE__ .
                                    " $name='$value', \$event is null, " .
                                    ' $_POST=' . print_r($_POST, true) . 
                                    "\n");
                    }
                    break;
                }                   // generic event date

                case 'birthlocation':
                case 'chrislocation':
                case 'christeninglocation':
                case 'deathlocation':
                case 'buriedlocation':
                case 'eventlocation':
                {                   // text of location
                    $eventLocation      = $value;
                    if ($event)
                        $event->setLocation($value);
                    else
                    {
                        error_log('updatePersonJson.php: ' . __LINE__ .
                                    " $name='$value', \$event is null, " .
                                    ' $_POST=' . print_r($_POST, true) . 
                                    "\n");
                    }
                    break;
                }                   // text of location

                case 'baptismtemple':
                case 'endowtemple':
                case 'confirmationtemple':
                case 'initiatorytemple':
                {                   // selection list of temples
                    if (ctype_digit($value))
                    {
                        $eventIdtr          = $value;
                        if ($eventIdtr > 0)
                        {
                            $event->set('eventidlr',    $eventIdtr);
                            $event->set('kind',         1);
                        }
                    }
                    break;
                }                   // selection list of temples

                case 'birthaddress':
                case 'chrisaddress':
                case 'christeningaddress':
                case 'deathaddress':
                case 'buriedaddress':
                {                   // address not currently supported
                    $eventAddress       = $value;
                    break;
                }                   // address not currently supported

                case 'birthnote':
                case 'chrisnote':
                case 'christeningnote':
                case 'deathnote':
                case 'buriednote':
                case 'baptismnote':
                case 'endownote':
                case 'confirmationnote':
                case 'initiatorynote':
                case 'eventdescn':
                {                   // event notes
                    $eventDescn         = $value;
                    if ($event)
                        $event->set('description', $eventDescn);
                    else
                    {
                        error_log('updatePersonJson.php: ' . __LINE__ .
                        " $name='$value', \$event is null, " .
                        ' $_POST=' . print_r($_POST, true) . "\n");
                    }
                    break;
                }                   // event notes

                case 'eventpref':
                {                   // preferred event flag
                    if (is_string($value) &&
                        strlen($value) > 0 &&
                        strtolower($value) != 'n')
                        $event->set('preferred', 1);
                    else
                        $event->set('preferred', 0);
                    break;
                }                   // preferred event flag

                case 'eventidet':
                {                   // event type
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
                }                   // event type

                case 'eventcittype':
                {                   // citation type
                    if (!ctype_digit($value))
                        break;
                    $cittype                = intval($value);
                    break;
                }                   // citation type

                case 'eventorder':
                {                   // event sort order
                    if (is_int($value) || ctype_digit($value))
                        if ($event)
                            $event->set('order', $value);
                    break;
                }                   // event sort order

                case 'eventsd':
                {                   // this is set by Event::setDate
                    $eventSd                = $value;
                    break;
                }                   // this is set by Event::setDate

                case 'eventchanged':
                {
                    if (canUser('edit'))
                    {               // user authorized to update database
                        if ($person)
                            $event->setAssociatedRecord($person);
                        if (strlen($eventDate) > 0 ||
                            strlen($eventLocation) > 0 ||
                            strlen($eventDescn) > 0)
                        {           // have an event
                            if ($idir > 0 && !is_null($event))
                            {
                                $ucount         = $event->save();
                                if (strlen($warn) > 0)
                                {
                                    print "$comma\"warn\": " .
                                                    json_encode($warn);
                                    $warn       = '';
                                }

                                if ($ucount > 0)
                                {
                                    $command    = $event->getLastSqlCmd();
                                    print "$comma\"cmd\": " .
                                                    json_encode($command);
                                }
                                else
                                {
                                    $errors     = $event->getErrors();
                                    print "$comma\"errors\": " .
                                                    json_encode($errors);
                                }
                                $ider           = $event['ider'];
                                print "$comma\"event$ider\": " .
                                                $event->toJson(false);
                                if ($clearEvent != '')
                                {   // clear event information in tblIR
                                    $person->clearEvent($clearEvent);
                                    if ($clearEvent == 'birth')
                                    {
                                        $person->set('birthsd',
                                            $event->get('eventsd'));
                                    }
                                }   // clear event information in tblIR
                            }
                            else
                            {       // defer until IDIR known
                                $events[]       = $event;   // track
                            }       // defer until IDIR known
                        }           // have an event
                        else
                        {           // no event information
                            if ($ider > 0)
                            {       // existing record in tblER
                                $event->delete(Record::JSON);
                            }       // existing record in tblER

                            if ($clearEvent != '')
                            {       // clear event information in tblIR
                                $person->clearEvent($clearEvent);
                            }       // clear event information in tblIR
                        }           // no event information

                    }               // user authorized to update database

                    // reset for next event
                    $event              = null;
                    $eventDate          = '';
                    $eventDescn         = '';
                    $eventLocation      = '';
                    $clearEvent         = '';
                    $eventPreferred     = 0;
                    $eventOrder         = null;
                    $eventSd            = -99999999;
                    $eventIdtr          = 0;
                    $ider               = 0;
                    $idet               = 0;
                    $cittype            = 0;
                    break;
                }       // EventChanged

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
                {       // boolean fields
                    if (is_string($value))
                    {       // form passed a string
                        if (strlen($value) > 0)
                        $person->set($column,
                                 intval($value));
                        else
                        $person->set($column,
                                 0);
                    }       // form passed a string
                    else
                    if (is_array($value))
                    {       // form passed an array
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
                    }       // form passed an array
                    break;
                }       // boolean fields

                case 'cpidcs':
                {
                    if (!ctype_digit($value))
                        break;
                    $childr->set('idcs',$value);
                    break;
                }       // field in tblCR record

                case 'cpreldad':
                {
                    if (!ctype_digit($value))
                        break;
                    $childr->set('idcpdad',$value);
                    break;
                }       // field in tblCR record

                case 'cpdadprivate':
                {
                    if ($value == 'on')
                        $childr->set('cpdadprivate', 1);
                    else
                        $childr->set('cpdadprivate', 0);
                    break;
                }       // field in tblCR record

                case 'cprelmom':
                {
                    if (!ctype_digit($value))
                        break;
                    $childr->set('idcpmom',$value);
                    break;
                }       // field in tblCR record

                case 'cpmomprivate':
                {
                    if ($value == 'on')
                        $childr->set('cpmomprivate', 1);
                    else
                        $childr->set('cpmomprivate', 0);
                    break;
                }       // field in tblCR record

                case 'title':
                case 'prefix':
                case 'fsid':
                case 'soundslike':  // Soundex
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
                {       // all other supported fields
                    if (is_null($person))
        error_log("<p>updatePersonJson.php: " . __LINE__ .
                        " idir=$idir " . $_SERVER['QUERY_STRING'] . "</p>\n",
                  3,
                  $document_root . "/logs/updatePerson.log");
                    else
                        $person->set($name, $value);
                    break;
                }       // all other supported fields

                case 'treename':
                {       // all other supported fields
                    $person->setTreeName($value);
                    break;
                }       // all other supported fields

                case 'debug':
                {
                    // handled by common code
                    break;
                }       // debug

                default:
                {       // all other fields
                    break;
                }       // all other fields
            }           // switch on column name
            $comma                      = ",\n";
        }               // loop through all parameters
        // close off list of parameters
        print "\t}";
        $comma                          = ",\n";
    }                   // have parameters

    if (is_null($person))
        $msg    .= "No individual identified. ";
    else
    if (canUser('edit'))
    {                       // user authorized to update database
        // write the changes to the individual record
        $person->save();
        $command                    = $person->getLastSqlCmd();
        print "$comma\"savePerson\": " . json_encode($command);
        // write the changes to the individual record
        $priName->save();
        $command                    = $priName->getLastSqlCmd();
        print "$comma\"saveName\": " . json_encode($command);
        // in case its a new individual get IDIR assigned by server
        $idir                       = $person->getIdir();

        // check married name records
        if ($person['gender'] == Person::FEMALE)
        {                   // female   
            $nameset    = new RecordSet('Names',
                                        array('idir'        => $idir,
                                              '`order`'     => -1));
            foreach ($nameset as $idnx => $name)
            {               // loop through married names
                $marr   = new Family(array('idmr'       => $name['idmr']));
                $husbPriName    = $marr->getHusbPriName();
                if ($marr['marriednamerule'] == 1 && 
                    $husbPriName && $husbPriName['surname'] != '')
                {           // wife's married surname is husband's surname
                    $name['surname']        = $husbPriName['surname'];
                    $name->save();
                    $command                = $name->getLastSqlCmd();
                    print "$comma\"name$idnx\": " . json_encode($command);
                }           // wife's married surname is husband's surname
            }               // loop through married names
        }                   // female

        $ie                         = 0;
        // save any pending instances of Event
        foreach($events as $ie => $event)
        {                   // create or update events
            if (!is_null($event))
            {
                $event->set('idir', $idir);
                if ($event->get('idtype') == Event::IDTYPE_INDIV &&
                    $event->get('idet') == Event::ET_BIRTH)
                {
                    $person->set('birthsd', $event->get('eventsd'));
                    $person->save();
                    $command            = $person->getLastSqlCmd();
                    print "$comma\"person$ie\": " . json_encode($command);
                    $command            = $person->getPriName()->getLastSqlCmd();
                    if ($command !== '')
                        print "$comma\"name$ie\": " . json_encode($command);
                }
                $event->save();
                $command            = $event->getLastSqlCmd();
                print "$comma\"eventCmd$ie\": " . json_encode($command) .
                        ",\n\"event$ie\": " . $event->toJson(false);
            }
        }       // create or update events

        //  include dump of updated record in response
        print "$comma\"person\": " . $person->toJson(false, 
                                                  Person::TOJSON_INCLUDE_NAMES);

        // check for updates to the child relationship record
        if ($idcr > 0 && !is_null($childr))
        {                   // individual updated as child in a family
            $childr->save();
            $command                = $childr->getLastSqlCmd();
            print "$comma\"childcmd$ie\": " . json_encode($command) .
                    ",\n\"child$ie\": " . $childr->toJson(false);
        }                   // individual updated as child in a family
        else
        if ($idmr > 0 && !is_null($family))
        {                   // individual added to family
            try {
                $childr             = $family->addChild($idir);
                $childr->save();
                $command            = $childr->getLastSqlCmd();
                print "$comma\"child$ie\": " . json_encode($command);
                print "$comma\"child\": " . $childr->toJson(false);
            }
            catch(Exception $e)
            {
                $msg        .= "Unable to add Child: " . $e->getMessage();
            }
        }       // individual added to family
    }           // user authorized to update database
    else
        $msg            .= "Current user not authorized to update database.";
} catch (Exception $e)
{           // global catch for exceptions anywhere in script
    $msg    .= $e->getMessage() . ": trace " . $e->getTraceAsString();
}           // global catch for exceptions anywhere in script

if (strlen($msg) > 0)
    print ",\n    \"msg\" : " . json_encode($msg) . "\n";

// close off the JSON response file
print "}\n";
