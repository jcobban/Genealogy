<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  editEvent.php                                                         *
 *                                                                      *
 *  Display a web page for editting one event from the family tree      *
 *  database which is represented by an instance of Event.              *
 *                                                                      *
 *  Parameters (passed by method="get"):                                *
 *      type numeric type value as used by the Citation                 *
 *              record to identify a specific event and which           *
 *              record type it is defined in.  If omitted the default   *
 *              value is 0.                                             *
 *                                                                      *
 *          idir parameter must point to Person record                  *
 *              STYPE_NAME              = 1                             *
 *              STYPE_BIRTH             = 2                             *
 *              STYPE_CHRISTEN          = 3                             *
 *              STYPE_DEATH             = 4                             *
 *              STYPE_BURIED            = 5                             *
 *              STYPE_NOTESGENERAL      = 6                             *
 *              STYPE_NOTESRESEARCH     = 7                             *
 *              STYPE_NOTESMEDICAL      = 8                             *
 *              STYPE_DEATHCAUSE        = 9                             *
 *              STYPE_LDSB              = 15  LDS Baptism               *
 *              STYPE_LDSE              = 16  LDS Endowment             *
 *              STYPE_LDSC              = 26  LDS Confirmation          *
 *              STYPE_LDSI              = 27  LDS Initiatory            *
 *                                                                      *
 *          idnx parameter points to Alternate Name Record tblNX        *
 *              STYPE_ALTNAME           = 10                            *
 *                                                                      *
 *          idcr parameter points to Child Record tblCR                 *
 *              STYPE_CHILDSTATUS       = 11 Child Status               *
 *              STYPE_CPRELDAD          = 12 Relationship to Father     *
 *              STYPE_CPRELMOM          = 13 Relationship to Mother     *
 *              STYPE_LDSP              = 17 Sealed to Parents          *
 *                                                                      *
 *          idmr parameter points to LegacyMarriage Record              *
 *              STYPE_LDSS              = 18 Sealed to Spouse           *
 *              STYPE_NEVERMARRIED      = 19 This individual nvr married*
 *              STYPE_MAR               = 20 Marriage                   *
 *              STYPE_MARNOTE           = 21 Marriage Note              *
 *              STYPE_MARNEVER          = 22 Never Married              *
 *              STYPE_MARNOKIDS         = 23 This couple had no children*
 *              STYPE_MAREND            = 24 marriage ended **added**   *
 *                                                                      *
 *          ider parameter points to Event Record                       *
 *              STYPE_EVENT             = 30 Individual Event,          *
 *                                          idir mandatory              *
 *              STYPE_MAREVENT          = 31 Marriage Event,            *
 *                                          idmr mandatory              *
 *                                                                      *
 *          idtd parameter points to To-Do records tblTD.IDTD           *
 *              STYPE_TODO              = 40 To-Do Item                 *
 *                                                                      *
 *          a temp source type, also any negative numbers are temporary *
 *              STYPE_TEMP              = 100 used to swap sources.     *
 *                                                                      *
 *      idir unique numeric key of instance of Person                   *
 *              required as defined above or                            *
 *      ider unique numeric key of instance of Event                    *
 *              if set to zero with type=STYPE_EVENT or STYPE_MAREVENT  *
 *              causes new Event record to be created.                  *
 *      idnx unique numeric key of instance of Alternate Name           *
 *              Record tblNX                                            *
 *      idcr unique numeric key of instance of Child Record tblCR       *
 *      idmr unique numeric key of instance of LegacyMarriage Record    *
 *      idtd unique numeric key of instance of To-Do records            *
 *              tblTD.IDTD                                              *
 *                                                                      *
 *      givenname optionally explicitly supply given name of individual *
 *              if DB copy may not be current                           *
 *      surname optionally explicitly supply surname of individual      *
 *              if DB copy may not be current                           *
 *      date optionally explicitly supply date of event if DB copy      *
 *              may not be current                                      *
 *      descn optionally explicitly supply description of event if      *
 *              DB copy may not be current                              *
 *      location optionally explicitly supply location of event if DB   *
 *              copy may not be current                                 *
 *      notes optionally explicitly supply notes for event if DB        *
 *              copy may not be current                                 *
 *      rownum feedback row number for common event                     *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/08      set $ider for newly created Event               *
 *      2010/08/09      add input field for Order value                 *
 *      2010/08/11      use htmlspecialchars to escape text values      *
 *      2010/08/16      change to LegacyCitationList interface          *
 *      2010/08/21      Change to use new page format                   *
 *      2010/08/28      implement delete citation                       *
 *      2010/09/05      Permit explictly supplying name of individual   *
 *      2010/10/11      Simplify interface for adding citations         *
 *      2010/10/15      Use cookies to default to last source citation  *
 *                      Remove header and trailer sections from dialog. *
 *                      Support all event types, not just Event         *
 *      2010/10/16      Use Event->getNotes()                           *
 *      2010/10/17      Import citTable.inc and citTable.js to manage   *
 *                      citations                                       *
 *      2010/10/19      Ensure $notes is not null for NAME event        *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/10/29      move Notes after Location in dialog             *
 *      2010/11/04      generate common HTML header tailored to browser *
 *      2010/11/14      include prefix and title in fields for Name     *
 *                      event                                           *
 *      2010/12/04      add link to help page                           *
 *      2010/12/12      replace LegacyDate::dateToString with           *
 *                      LegacyDate::toString                            *
 *      2010/12/20      handle exception thrown by new LegacyIndiv      *
 *                      handle exception thrown by new LegacyFamily     *
 *                      handle exception thrown by new LegacyLocation   *
 *                      improved handling of invalid parameters         *
 *      2011/01/02      add 4 LDS sacraments                            *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/01/22      clean up code                                   *
 *      2011/01/30      identify fact type in title of facts from       *
 *                      indiv record                                    *
 *      2011/02/24      identify fact type in context specific help     *
 *                      for notes                                       *
 *      2011/03/03      underline 'U' in "Update Event" button text     *
 *      2011/06/15      pass idmr to updateEvent.php                    *
 *                      support events in LegacyFamily record           *
 *      2011/07/29      handle new parameters date and location to      *
 *                      supply explicit values of date and location     *
 *                      of event                                        *
 *                      Use LegacyLocation constructor to resolve       *
 *                      short names                                     *
 *      2011/08/08      trim supplied location name                     *
 *      2011/08/21      do not initially display Temple vs. Live kind   *
 *                      row for generic event.                          *
 *      2011/10/01      provide database lookup assist for setting      *
 *                      location names                                  *
 *                      document month name abbreviations in context    *
 *                      help                                            *
 *                      change name of class LegacyCitationList         *
 *      2011/11/19      display alternate names in the Name Event and   *
 *                      provide a button to selectively delete an       *
 *                      alternate name                                  *
 *      2011/12/23      always display married surnames                 *
 *                      display all events in dialog and permit adding, *
 *                      modifying, and deleting events.                 *
 *                      add help panels for all fields                  *
 *      2012/01/08      reorder to put the event type before the date   *
 *      2012/01/13      change class names                              *
 *                      support supplying notes value through parm      *
 *                      include <input type=checkbox> in flag events    *
 *                      add "No Children" to list of marriage events    *
 *      2012/01/23      display loading indicator while waiting for     *
 *                      response to changed in a location field         *
 *      2012/02/25      use tinyMCE for stylized editing of text notes  *
 *      2012/05/06      set explicit class for Order field              *
 *      2012/07/31      make names of individuals identified in the     *
 *                      title of the event hyperlinks to the individual *
 *                      record                                          *
 *                      add names of spouses to all marriage events     *
 *                      expand date input field to display 24 characters*
 *      2012/08/01      permit invoker to explicitly override           *
 *                      description field                               *
 *      2012/08/12      support LDS sealed to parents event             *
 *                      validate associated record for all events       *
 *                      before using it                                 *
 *                      permit setting temple ready indicator           *
 *      2012/10/17      do not attempt to create database objects if    *
 *                      the numeric key is invalid                      *
 *      2012/10/19      supplied given name and surname was not used    *
 *                      by name event                                   *
 *      2012/10/30      ensure templeReady field default to unused      *
 *      2012/11/05      add support for tinyMCE editing of notes        *
 *      2012/11/22      Event::add removed and replaced by member       *
 *                      method addEvent of LegacyIndiv and LegacyFamily *
 *      2013/03/03      LegacyIndiv::getNextName now returns all        *
 *                      alternate names                                 *
 *      2013/04/02      add support for citations for alternate names   *
 *      2013/04/24      add birth, marriage, and death registrations    *
 *      2013/05/26      use dialog in place of alert for new location   *
 *                      name                                            *
 *      2013/07/04      for individual event recorded in instance of    *
 *                      Event do not display event types recorded       *
 *                      in other records.  This permits changing the    *
 *                      event type without creating a new record        *
 *      2013/08/25      add clear button for note textarea              *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/08      standardize appearance of <select>              *
 *      2014/02/12      replace tables with CSS for layout              *
 *      2014/02/17      define local CSS for this form                  *
 *      2014/02/19      add id to <form>                                *
 *      2014/02/24      use dialog to choose from range of locations    *
 *                      instead of inserting <select> into the form     *
 *                      location support moved to locationCommon.js     *
 *      2014/03/06      label class name changed to column1             *
 *      2014/03/10      ability to edit cause of death added to         *
 *                      edit dialogue for normal death event so it      *
 *                      can be removed from the edit Individual dialog  *
 *      2014/03/20      replace deprecated LegacyIndiv::getNumNames     *
 *                      replace deprecated LegacyIndiv::getNextName     *
 *                      wrap alternate name section of Name event in    *
 *                      a fieldset for clarity                          *
 *                      wrap death cause section of Death event in      *
 *                      a fieldset for clarity                          *
 *                      deprecated class LegacyCitationList replaced by *
 *                      calls to Citation::getCitations                 *
 *      2014/04/08      LegacyAltName renamed to LegacyName             *
 *                      management of citations to alternate names      *
 *                      moved to EditName.php script                    *
 *      2014/04/13      permit being invoked with just the IDER value   *
 *      2014/04/15      Display default citation while waiting for      *
 *                      database server to respond to request for list  *
 *                      of sources                                      *
 *                      enable update of citation page number           *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/04/30      refine headings for marriage events             *
 *      2014/05/30      use explicit style class actleftcit in          *
 *                      template for new source citation to limit       *
 *                      the width of the selection list to match the    *
 *                      width of the display after the citation added   *
 *      2014/07/06      move textual interpretation of IDET here from   *
 *                      Event class to support I18N                     *
 *      2014/07/15      support for popupAlert moved to common code     *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *                      use LegacyTemple::getTemples to get list for    *
 *                      <select>                                        *
 *      2014/10/01      add delete confirmation dialog                  *
 *      2014/10/03      add support for associating instances of        *
 *                      Picture with an event.                          *
 *      2014/10/15      events moved out of tblIR into tblER            *
 *      2014/11/19      provide alternative occupation input row        *
 *      2014/11/20      bad generated name for <input name="IDSR...">   *
 *      2014/11/27      use Event::getCitations                         *
 *      2014/11/29      do not crash on new location                    *
 *      2014/11/29      print $warn, which may contain debug trace      *
 *      2014/12/04      global $debug not declared in function          *
 *                      getDateAndLocation                              *
 *      2014/12/12      missing parameter to LegacyTemple::getTemples   *
 *      2014/12/25      redirect debugging output to $warn              *
 *      2014/12/26      add rownum feedback parameter                   *
 *      2015/03/07      use LegacyFamily::getHusbName and getWifeName   *
 *                      instead of deprecated name fields               *
 *      2015/03/14      include Close button if errors                  *
 *      2015/05/15      do not escape HTML tags in textarea, they are   *
 *                      used by rich text editor                        *
 *      2015/06/14      match field sizes in new citation to existing   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      add id to debug trace                           *
 *                      display notes in a larger area                  *
 *      2016/02/05      one trace message was printed instead of saved  *
 *      2016/02/06      use showTrace                                   *
 *      2017/01/03      undefined $checked                              *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *                      use preferred parameters for new LegacyFamily   *
 *      2017/07/23      class LegacyPicture renamed to class Picture    *
 *      2017/07/27      class LegacyCitation renamed to class Citation  *
 *      2017/08/08      class LegacyChild renamed to class Child        *
 *      2017/08/15      class LegacyToDo renamed to class ToDo          *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/23      add a "Choose a Temple" option to temple select *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/18      use RecordSet instead of Temple::getTemples     *
 *      2017/11/19      use CitationSet in place of getCitations        *
 *      2018/02/11      add Close button                                *
 *      2018/03/24      add button to control whether textareas are     *
 *                      displayed as rich text or raw text              *
 *      2018/11/19      change Help.html to Helpen.html                 *
 *      2019/08/01      support tinyMCE 5.0.3                           *
 *      2019/08/06      use editName.php to handle updates of Names     *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2022/04/16      use all of FtTemplate                           *
 *      2022/05/16      default IDET to Event::ET_UNDEF                 *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function getEventType                                               *
 *                                                                      *
 *  Get the type of the event as descriptive text.                      *
 *                                                                      *
 *  Input:                                                              *
 *      $event          instance of class Event                         *
 *                                                                      *
 *  Returns:                                                            *
 *      String description of event type                                *
 ************************************************************************/
function getEventType($event)
{
    global $translate;

    $idet           = $event['idet'];
    $result         = $translate['eventText'][$idet];
    if (is_null($result))
        return "IDET=$idet";
    else
        return $result;
}       // getEventType

/************************************************************************
 *  function getDateAndLocation                                         *
 *                                                                      *
 *      If the values for date and location have been explicitly        *
 *      provided, use them.  Otherwise obtain the values from the       *
 *      associated database record.                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      $record             data base record as instance of Record      *
 *      $dateFldName        field name containing date of event         *
 *      $locFldName         field name containing IDLR of location      *
 *                          of event                                    *
 ************************************************************************/
function getDateAndLocation($record,
                            $dateFldName,
                            $locFldName)
{
    global $debug;
    global $warn;
    global $date;
    global $location;   // instance of Location
    global $msg;
    global $idlr;

    if (is_null($date))
    {           // date value not explicitly supplied
        $date       = new LegacyDate($record->get($dateFldName));
        $date       = $date->toString();
    }           // date value not explicitly supplied

    if (is_null($location))
    {           // location value not explicitly supplied
        $idlr       = $record->get($locFldName);
        if ($debug)
            $warn   .= "<p>\$idlr set to $idlr from field name '$locFldName'</p>\n";
        $location   = new Location(array('idlr'         => $idlr));
    }           // location value not explicitly supplied
}       // function getDateAndLocation

/************************************************************************
 *  function getDateAndLocationLds                                      *
 *                                                                      *
 *  If the values for date and location have been explicitly            *
 *  provided, use them.  Otherwise obtain the values from the           *
 *  associated database record.                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      $record             data base record as instance of Record      *
 *      $kind               temple indicator                            *
 *      $dateFldName        field name containing date of event         *
 *      $locFldName         field name containing IDLR of location      *
 *                          of event                                    *
 ************************************************************************/
function getDateAndLocationLds($record,
                               $kind,
                               $dateFldName,
                               $locFldName)
{
    global $date;
    global $location;
    global $msg;
    global $idtr;

    if (is_null($date))
    {               // date value not explicitly supplied
        $date         = new LegacyDate($record->get($dateFldName));
        $date         = $date->toString();
    }               // date value not explicitly supplied
    $idtr             = $record->get($locFldName);
    if ($kind == 1)
        $location     = new Temple(array('idtr'         => $idtr));
    else
    {               // not in temple
        if (is_null($location))
        {           // do not have explicit location
            $location = Location::getLocation($idtr);
        }           // do not have explicit location
    }               // not in temple
}       // function getDateAndLocationLds

/************************************************************************
 *  function getEventInfo                                               *
 *                                                                      *
 *  Get information from an instance of Event                           *
 *                                                                      *
 *  Parameters:                                                         *
 *      $event             instance of Event                            *
 ************************************************************************/
function getEventInfo($event)
{
    global $etype;
    global $idet;
    global $order;
    global $notes;
    global $descn;
    global $kind;
    global $templeReady;
    global $preferred;
    $etype                  = getEventType($event);
    if ($idet <= 1)
        $idet               = $event['idet'];   // numeric key of tblET
    $order                  = $event['order'];

    if (is_null($notes))
    {
        $notes              = $event['desc'];
        if (is_null($notes))
            $notes          = '';
    }

    if (is_null($descn))
        $descn              = $event['description']; 

    $templeReady            = $event['ldstempleready'];
    $preferred              = $event['preferred'];

    $kind                   = $event['kind'];
    if ($kind == 0)
        getDateAndLocation($event,
                           'eventd',
                           'idlrevent');
    else
        getDateAndLocationLds($event,
                              $kind,
                              'eventd',
                              'idlrevent');
}   // function getEventInfo

/************************************************************************
 *   OO  PPP  EEEE N  N     CC   OO  DDD  EEEE                          *
 *  O  O P  P E    NN N    C  C O  O D  D E                             *
 *  O  O PPP  EEE  N NN    C    O  O D  D EEE                           *
 *  O  O P    E    N NN    C  C O  O D  D E                             *
 *   OO  P    EEEE N  N     CC   OO  DDD  EEEE                          *
 ************************************************************************/

// default title
$title              = 'Edit Event Error';

// safely get parameter values
// defaults
// parameter values from URI
$stype                  = 0;    // see Citation::STYPE_...
$ider                   = null; // index of Event
$idet                   = null; // index of EventType
$idir                   = null; // index of Person
$idnx                   = null; // index of Name
$idcr                   = null; // index of Child
$idmr                   = null; // index of Family
$idtd                   = null; // index of ToDo
$typetext               = null; // error text for event type
$idertext               = null; // error text for key of Event
$idettext               = null; // error text for key of EventType
$idirtext               = null; // error text for key of Person
$idnxtext               = null; // error text for key of Name
$idcrtext               = null; // error text for key of Child
$idmrtext               = null; // error text for key of Family
$idtdtext               = null; // error text for key of ToDo
$idtrtext               = null; // error text for key of Temple
$date                   = null;
$descn                  = null;
$location               = null;
$notes                  = null;
$notmar                 = null;
$nokids                 = null;
$cremated               = null;
$deathCause             = null;
$picIdType              = null; // for invoking EditPictures dialog
$given                  = '';
$surname                = '';
$rownum                 = null;
$lang                   = 'en';
$etype                  = null;
$order                  = null;
$idlr                   = null;
$idlrtext               = null;
$kind                   = null;
$kindtext               = null;
$prefix                 = null;
$nametitle              = null;
$templeReady            = null;
$preferred              = null;
$typeText               = null;


// database records
$event                  = null; // instance of Event
$person                 = null; // instance of Person
$family                 = null; // instance of Family
$child                  = null; // instance of Child
$altname                = null; // instance of Name
$todo                   = null; // instance of ToDo

// other
$readonly               = ''; // attribute value to insert in <input>
$submit                 = false;

// process input parameters from the search string passed by method=get
if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                              "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>\n" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr>\n<th class='detlabel'>$key</th>" .
                                "<td class='white left'>\n" .
                                "$safevalue</td>\n</tr>\n";
        $value              = trim($value); 
        switch(strtolower($key))
        {
            case 'type':
            {       // supplied event type
                if (ctype_digit($value))
                {
                    $stype          = intval($value);
                    if ($stype == 0)
                        $readonly   = "readonly='readonly'";

                    // textual description of event type
                    if (isset($citText[$stype]))
                        $eventType  = $citText[$stype];
                    else
                        $eventType  = 'Invalid event type ' . $stype;
                }
                else
                    $typetext       = $safevalue;
                break;
            }        // supplied event type

            // get the event record identifier if present
            case 'ider':
            {
                if (ctype_digit($value))
                    $ider           = intval($value);
                else
                    $idertext       = $safevalue;
                break;
            }       // ider

            // get the event type identifier if present
            case 'idet':
            {
                if (ctype_digit($value))
                    $idet           = intval($value);
                else
                    $idettext       = $safevalue;
                break;
            }       // idet

            // get the key of instance of Person
            case 'idir':
            {
                if (ctype_digit($value))
                    $idir           = intval($value);
                else
                    $idirtext       = $safevalue;
                break;
            }       // idir

            case 'idnx':
            {   // get the key of instance of Alternate Name Record tblNX
                if (ctype_digit($value))
                    $idnx           = intval($value);
                else
                    $idnxtext       = $safevalue;
                break;
            }       //idnx

            case 'idcr':
            {       // key of instance of Child Record tblCR
                if (ctype_digit($value))
                {
                    $idcr           = intval($value);
                    $kind           = 0;
                }
                else
                    $idcrtext       = $safevalue;
                break;
            }       //idcr

            case 'idmr':
            {       // key of instance of Marriage Record
                if (ctype_digit($value))
                    $idmr           = intval($value);
                else
                    $idmrtext       = $safevalue;
                break;
            }       //idmr

            case 'idtd':
            {       // key of instance of To-Do records tblTD.IDTD
                if (ctype_digit($value))
                    $idtd           = intval($value);
                else
                    $idtdtext       = $safevalue;
                break;
            }       // idtd

            // individual's name can be explicitly supplied for events
            // associated with
            // a new individual if that information is not available from the
            // database record because it has not been written yet
            case 'givenname':
            {
                $given              = $safevalue;
                break;
            }       // given name

            case 'surname':
            {       // surname 
                $surname            = $safevalue;
                break;
            }       // surname

            // the date, location, and notes field values in the DB record may
            // not be current as a result of user activity
            case 'date':
            {       // date of event as an external string
                $date               = $safevalue;
                break;
            }       // date

            case 'descn':
            {       // description of the event
                $descn              = $safevalue;
                break;
            }       // descn

            case 'location':
            {       // location of the event
                $location           = $safevalue;
                break;
            }       // location

            case 'idtr':
            {       // key of temple
                if (ctype_digit($value))
                    $idtr           = $value;
                else
                    $idtrtext       = $safevalue;
                break;
            }       // key of temple

            case 'kind':
            {       // key of temple
                if (ctype_digit($value) && $value < 2)
                    $kind           = $value;
                else
                    $kindtext       = $safevalue;
                break;
            }       // key of temple

            case 'rownum':
            {       // rownum for feedback about the event
                $rownum             = $safevalue;
                break;
            }       // rownum for feedback about the event

            case 'notes':
            {       // notes about the event
                $notes              = $safevalue;
                break;
            }       // notes about the event

            case 'submit':
            {       // control whether uses AJAX or submit
                if (strtoupper($value) == 'Y')
                    $submit         = true;
                break;
            }       // control whether uses AJAX or submit

            case 'debug':
            {       // debug handled by common code
                break;
            }       // debug


            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

            case 'text':
            case 'editnotes':
            {
                break;
            }           // used by Javascript

            default:
            {           // other parameters
                $warn .= "<p>Unexpected parameter $key='$safevalue'</p>\n";
                break;
            }           // other parameters
        }               // switch
    }                   // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                       // invoked by method=get

// set default citation type if only IDET and record key specified
if (is_int($idet) && $stype == 0)
{
    if (!is_null($idcr))
        $stype              = Citation::STYPE_CHILDEVENT;
    else
    if (!is_null($idmr))
        $stype              = Citation::STYPE_MAREVENT;
    else
        $stype              = Citation::STYPE_EVENT;
}

if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';

// get template
$template               = new FtTemplate("editEvent$action$lang.html",
                                         true);
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];
$citText                = $translate['typeText'];   // citation text

$template->includeSub('LocationDialogsen.html',
                      'LOCATIONDIALOGS');
$template->includeSub('TempleDialogsen.html',
                      'TEMPLEDIALOGS');

// issue parameter syntax error messages
$msgtext                = $template['invalidParmValue']->innerHTML;
if (is_string($typetext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('Type',$typetext),
                            $msgtext);
if (is_string($idertext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDER', $idertext),
                            $msgtext);
if (is_string($idettext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDET', $idettext), 
                            $msgtext);
if (is_string($idirtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDIR', $idirtext), 
                            $msgtext);
if (is_string($idnxtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDNX', $idnxtext), 
                            $msgtext);
if (is_string($idcrtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDCR', $idcrtext), 
                            $msgtext);
if (is_string($idmrtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDMR', $idmrtext), 
                            $msgtext);
if (is_string($idtrtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDTR', $idtrtext), 
                            $msgtext);
if (is_string($idtdtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('IDTD', $idtdtext), 
                            $msgtext);
if (is_string($kindtext))
    $msg     .= str_replace(array('$parm','$value'),
                            array('kind', $kindtext), 
                            $msgtext);

// create Event based upon IDER parameter
if (!is_null($ider) && $ider > 0)
{           // existing event
    $event          = new Event(array('ider'         => $ider));
    if ($event->isExisting())
    {
        $idet                   = $event['idet'];
        $idtype                 = $event['idtype'];
        switch($idtype)
        {                   // get associated record
            case Event::IDTYPE_INDIV:       // Person
                $idir           = $event['idir'];
                $stype          = Citation::STYPE_EVENT;
                break;

            case Event::IDTYPE_MAR:         // Family
                $idmr           = $event['idir'];
                $stype          = Citation::STYPE_MAREVENT;
                break;

            case Event::IDTYPE_CHILD:       // Child
                $idcr           = $event['idir'];
                $stype          = 32;
                break;
        }               // get associated record
    }
    else
    {                   // new Event failed
        $event                  = null;
        $msg                    .= "No existing event has IDER=$ider. ";
    }                   // new Event failed
}                       // existing event
else
{                       // request to create new event
    $event                      = null;     // is done later
    if ($stype == 0)
    {
        if (!is_null($idir))
        {               // individual event
            $stype              = Citation::STYPE_EVENT;
        }               // individual event
        else
        if (!is_null($idmr))
        {               // marriage event
            $stype              = Citation::STYPE_MAREVENT;
            $idet               = Event::ET_MARRIAGE;
        }               // married event
        else
        if (!is_null($idtd))
        {               // to do event
            $stype              = Citation::STYPE_TODO;
        }               // to do event
        else
        if (!is_null($idnx))
        {               // name event
            $stype              = Citation::STYPE_ALTNAME;
        }               // name event
    }
}                       // request to create new event

// create Name record based upon IDNX keyword
if ($idnx > 0)
{                       // IDNX was specified in parameters
    $altname                = new Name(array('idnx'         => $idnx));
    if ($altname->isExisting())
        header("Location: /FamilyTree/editName.php?idnx=$idnx");
    else
    {                   // no matching Name
        $altname            = null;
        $msg                .= "Invalid name identification IDNX=$idnx. ";
        $typeText           = $t['Name'];
        if ($typeText == null)
            $typeText       = 'Name';
    }                   // no matching Name
}                       // IDNX was specified in parameters

// create Child record based upon IDCR keyword
if ($idcr > 0)
{                       // IDCR was specified in parameters
    $child                  = new Child(array('idcr'         => $idcr));
    if ($child->isExisting())
    {                   // have matching Child
        $idir               = $child['idir'];
        $person             = new Person(array('idir'         => $idir));
        $isOwner            = canUser('edit') && $person->isOwner();
        if (!$isOwner)
            $msg            .=
            'You are not authorized to edit the events of this child.  ';
        $idmr               = $child['idmr'];
        $family             = new Family(array('idmr'         => $idmr));
    }                   // have matching Child
    else
    {                   // no matching Child
        $child              = null;
        $msg                .= "Invalid child identification IDCR=$idcr. ";
        $idcr               = null;
    }                   // no matching Child
}                       // IDCR was specified in parameters

// get the associated individual record
if ($idir > 0)
{           // IDIR was specified in parameters
    $person                 = new Person(array('idir'         => $idir));
    if ($person->isExisting())
    {
        $isOwner            = canUser('edit') && $person->isOwner();
        if (!$isOwner)
            $msg .= 'You are not authorized to edit the events of this individual.  ';

        // if name of individual not supplied,
        // get it from Person record
        if (strlen($given) == 0)
            $given          = $person->getGivenname();
        if (strlen($surname) == 0)
            $surname        = $person->getSurname();
        $title              = "Edit Event for $given $surname";
    }       // try creating instance of Person
    else
    {       // error creating individual
        $person             = null;
        $idime              = -1;
        $given              = '';
        $surname            = $t['Unknown'];
        $msg                .= "Invalid Value of IDIR=$idir. ";
        $idir               = null;
    }       // error creating individual
}           // IDIR was specified in parameters

// get the To-Do record, under construction
if ($idtd > 0)
{           // IDTD was specified in parameters
    $todo                   = new ToDo(array('idtd'         => $idtd));
    if ($todo->isExisting())
    {
    }
    else
    {       // no match
        $todo               = null;
        $msg                .= "Invalid To Do identification idtd=$idtd. ";
    }       // no match
}           // IDTD was specified in parameters

// create Family record based upon IDMR keyword
if ($idmr > 0)
{           // IDMR was specified in parameters
    $family                 = new Family(array('idmr'         => $idmr));
    if ($family->isExisting())
    {
        $husbname           = $family->getHusbName();
        $idirhusb           = $family['idirhusb'];
        $wifename           = $family->getWifeName();
        $idirwife           = $family['idirwife'];
    }           // existing Family record
    else
    {           // invalid IDMR value
        $husbname           = $t['Unknown'];
        $idirhusb           = 0;
        $wifename           = $t['Unknown'];
        $idirwife           = 0;
        $msg            .= "Invalid family identification idmr=$idmr. ";
    }           // invalid IDMR value
}               // IDMR was specified in parameters

$forText                    = '';
if (strlen($msg) == 0)
{
    // validate the presence of parameters depending upon
    // the value of the type parameter
    // identify the fields in the associated record that are
    // updated for each type of event

    // default that all fields are unsupported
    if ($ider === 0)
    {                   // create new Event
        if (is_null($idet))
            $idet           = Event::ET_UNDEF;
        $event              = new Event(array('ider'            => 0,
                                              'idet'            => $idet,
                                              'idir'            => $idir));

    }                   // create new Event

    // determine portion of page heading that describes the record
    // referenced by the event
    if ((ctype_digit($idir) || is_int($idir)) && $idir > 0)
    {       // IDIR based event
        $forText    = 
                "<a href=\"Person.php?idir=$idir\">$given $surname</a>";
    }       // IDIR based event
    else
    if ((ctype_digit($idmr) || is_int($idmr)) && $idmr > 0)
    {       // IDMR based event
        if ($idirhusb)
        {
            $forText        = 
                "<a href=\"Person.php?idir=$idirhusb&amp;lang=$lang\" class=\"male\">\n" .               
                "                   $husbname</a> ";
            if ($idirwife)
                $forText     .= $t['and'];
        }
        if ($idirwife)
            $forText        .=
                "<a href=\"Person.php?idir=$idirwife&amp;lang=$lang\" class=\"female\">\n" .
                "                   $wifename</a>";
    }
    else
    if (ctype_digit($idtd) || is_int($idtd))
        $forText    = "<a href=\"ToDo.php?idtd=$idtd&amp;lang=$lang\">IDTD=$idtd</a>";
    else
        $forText    = '';

    // take action which depends upon the Citation type
    switch($stype)
    {       // take action according to type
        case Citation::STYPE_UNSPECIFIED:       // 0;
        {   // type not determined yet
            // will be either IDCR, IDIR, IDMR, or IDER based event
            if (is_int($idcr) && $idcr > 0)
            {       // IDCR based event
                $idime      = $idcr;
                $typeText   = $template['typeTextGenericChild']->innerHTML;
            }       // IDCR based event
            else
            if (is_int($idir) && $idir > 0)
            {       // IDIR based event
                $idime      = $idir;
                $typeText   = $template['typeTextGenericPerson']->innerHTML;
            }       // IDIR based event
            else
            if (is_int($idmr) && $idmr > 0)
            {       // IDMR based event
                $idime      = $idmr;
                $typeText   = $template['headingGenericPerson']->innerHTML;
            }       // IDMR based event
            else
            {
                $msg        .= $template['missingIDIME']->innerHTML;
            }
            $etype          = '';
            $idet           = Event::ET_UNDEF;
            break;
        }   // type not determined yet

        //    idir parameter points to Person record
        case Citation::STYPE_NAME:      // 1
        {
            if (is_null($idir) || $idir == 0)
            {               // individual event requires IDIR
                $msg        .= $template['missingKey']->replace('$key',"idir");
                $surname    = $t['Unknown'];
            }               // individual event requires IDIR
            else
            {               // proceed with edit
                $name       = new Name(array('idir'     => $idir,
                                             'order'    => Name::PRIMARY));
                $idnx       = $name['idnx'];
                header("Location: /FamilyTree/editName.php?idnx=$idnx");
                exit;
            }               // proceed with edit
            break;
        }                   // primary name of individual

        case Citation::STYPE_BIRTH:         // 2
        case Citation::STYPE_CHRISTEN:      // 3
        case Citation::STYPE_DEATH:         // 4
        case Citation::STYPE_BURIED:        // 5
        case Citation::STYPE_NOTESGENERAL:  // 6
        case Citation::STYPE_NOTESRESEARCH: // 7
        case Citation::STYPE_NOTESMEDICAL:  // 8
        case Citation::STYPE_DEATHCAUSE:    // 9
        {
            if (is_null($idir))
            {       // individual event requires IDIR
                $msg        .= $template['missingKey']->replace('$key',"idir");
                $surname    = $t['Unknown'];
            }       // individual event requires IDIR
            else
            {       // proceed with edit
                $idime      = $idir; // key for citations
                $typeText   = $citText[$stype];
                if ($stype <= Citation::STYPE_BURIED &&
                     $stype >= Citation::STYPE_BIRTH)
                    $picIdType = $stype - 1;
            }       // proceed with edit
            break;
        }

        case Citation::STYPE_LDSB:          // 15  LDS Baptism
        case Citation::STYPE_LDSE:          // 16  LDS Endowment
        case Citation::STYPE_LDSC:          // 26  LDS Confirmation
        case Citation::STYPE_LDSI:          // 27  LDS Initiatory
        {
            if (is_null($idir))
            {       // individual event requires IDIR
                $msg        .= $template['missingKey']->replace('$key',"idir");
                $surname    = $t['Unknown'];
            }       // individual event requires IDIR
            else
            {       // proceed with edit
                $idime      = $idir; // key for citations
                $typeText   = $citText[$stype];
                if ($stype <= Citation::STYPE_BURIED &&
                     $stype >= Citation::STYPE_BIRTH)
                     $picIdType = $stype - 1;
                $kind       = 1;            // at Temple
            }       // proceed with edit
            break;
        }

        //    idnx parameter points to Alternate Name Record tblNX
        case Citation::STYPE_ALTNAME:       // 10
        {
            if (is_null($idnx))
                $msg     .= $template['missingKey']->replace('$key',"idnx");
            else
            {
                header("Location: /FamilyTree/editName.php?idnx=$idnx");
                exit;
            }
            break;
        }

        //    idcr parameter points to Child Record tblCR
        case Citation::STYPE_CHILDSTATUS:   // 11 Child Status    
        case Citation::STYPE_CPRELDAD:      // 12 Relationship to Father  
        case Citation::STYPE_CPRELMOM:      // 13 Relationship to Mother  
        {
            if (is_null($idcr))
                $msg        .= $template['missingKey']->replace('$key',"idcr");
            else
                $idime      = $idcr; // key for citations
            $typeText       = $citText[$stype];
            break;
        }
        case Citation::STYPE_LDSP:          // 17 Sealed to Parents
        {
            if (is_null($idcr))
                $msg        .= $template['missingKey']->replace('$key',"idcr");
            else
                $idime      = $idcr; // key for citations
            $typeText       = $citText[$stype];
            $kind           = 1;            // at Temple
            break;
        }


        //    idmr parameter points to LegacyMarriage Record
        case Citation::STYPE_LDSS:          // 18 Sealed to Spouse
        case Citation::STYPE_NEVERMARRIED:  // 19 individual never married 
        case Citation::STYPE_MAR:           // 20 Marriage 
        case Citation::STYPE_MARNEVER:      // 22 Never Married
        case Citation::STYPE_MARNOKIDS:     // 23 No children  
        case Citation::STYPE_MAREND:        // 24 marriage end date
        {       // event defined in marriage record
            $typeText       = $citText[$stype];
            if (is_null($idmr))
            {
                $msg        .= $template['missingKey']->replace('$key',"idmr");
            }
            else
            {
                $idime      = $idmr; // key for citations
                if ($family)
                {       // family specified
                    switch ($stype)
                    {
                        case Citation::STYPE_LDSS:          
                            if (is_null($date))
                                $date       = '';
                            $temple         = 1;
                            break;

                        case Citation::STYPE_NEVERMARRIED: 
                        case Citation::STYPE_MARNEVER:    
                        case Citation::STYPE_MARNOKIDS:  
                            $date           = null;
                            $location       = null;
                            $temple         = null;
                            break;

                        case Citation::STYPE_MAR:
                            $marevent       = $family->getMarEvent(true);
                            if (is_null($date))
                            {
                                $eventd     = $marevent['eventd'];
                                $date       = new LegacyDate($eventd);
                                $date       = $date->toString(9999,false,$t);
                            }
                            if (is_null($location))
                            {
                                $idlrevent  = $marevent['idlrevent'];
                                $locparms   = array('idlr' => $idlrevent);
                                $location   = new Location($locparms);
                                $location   = $location->getName();
                            }
                            $notes          = $marevent['notes'];
                            $picIdType      = Picture::IDTYPEMar;
                            break;  // case Citation::STYPE_MAR

                        case Citation::STYPE_MAREND:    
                            if (is_null($date))
                                $date       = '';
                            if (is_null($location))
                                $location   = '';
                            break;  // case Citation::STYPE_MAREND

                    }   // switch on citation type
                }       // family specified
            }
            break;
        }       // event defined in marriage record

        case Citation::STYPE_MARNOTE:       // 21 Marriage Note
        {       // event defined in marriage record
            $typeText       = $citText[$stype];
            if (is_null($idmr))
            {
                $msg        .= $template['missingKey']->replace('$key',"idmr");
            }
            else
            {
                $idime      = $idmr; // key for citations
                if ($family)
                {
                    $notes      = $family['notes'];
                    $date       = null;
                    $location   = null;
                    $temple     = null;
                }
            }
            break;
        }       // event defined in marriage record

        //    ider parameter points to Event Record
        case Citation::STYPE_EVENT: // 30 Individual Event
        {
            if (is_null($event))
            {
                $event = new Event(array('ider'         => 0,
                                         'idir'         => $idir,
                                         'idet'         => $idet));
            }

            // get the supplied value of the event subtype
            if ($idet > 1)
                $event->setIdet($idet);

            $idime                 = $ider; // key for citations
            if ($debug)
                $warn .= "<p>\$idir set to $idir from event IDER=$ider</p>\n";
            if (is_null($person))
                $person             = Person::getPerson($idir);
            if (!is_null($person))
            {
                if ($ider == 0 && $idet > 1)
                {       // create new individual event
                    $event          = $person->addEvent();
                    $ider           = $event['ider'];
                }       // create new individual event

                // if name of individual not supplied,
                // get it from Person record
                if (strlen($given) == 0)
                    $given          = $person->getGivenName();
                if (strlen($surname) == 0)
                    $surname        = $person->getSurname();
                $eventTypeText      = $translate['personEvents'][$idet];
                if ($eventTypeText)
                    $typeText       =  $eventTypeText;
                else
                    $typeText       = "New $idet";
                $picIdType          = Picture::IDTYPEEvent;
            }       // try creating individual
            else
            {       // error creating individual
                $person             = null;
                $idime              = -1;
                $given              = '';
                $surname            = '';
                $msg                .= ', Unable to create individual event because idir parameter missing or invalid. ';
            }       // error creating individual

            break;
        }

        case Citation::STYPE_MAREVENT:  // 31 Marriage Event
        {
            if ($ider == 0)
            {       // create new marriage event
                if (!is_null($family))
                {
                    $event          = $family->addEvent();
                    $ider           = $event['ider'];

                    // set the supplied value of the event subtype
                    if (!is_null($idet))
                        $event->setIdet($idet);
                }
                else
                {
                    $msg .= 'Unable to create family event because idmr parameter missing or invalid. ';
                }
            }       // create new event
            else
            {       // existing event
                $idmr               = $event['idir'];
                $family             = new Family(array('idmr'         => $idmr));
            }       // existing event
            $tidet                  = $event['idet'];
            $marriageEvents         = $template['marriageEvents'];
            $eventTypeText          = $marriageEvents[$tidet];
            
            if ($eventTypeText)
                $typeText           =  ucfirst($eventTypeText);
            else
                $typeText           = "$tidet?";
            if ($tidet == 70)
                $typeText           = ucfirst($event['description']);

            $idime                  = $ider;    // key for citations
            $picIdType              = Picture::IDTYPEEvent;
            break;
        }           // case Citation::STYPE_MAREVENT:

        case Citation::STYPE_CHILDEVENT:  // 32 Child Event
        {
            if ($ider == 0)
            {       // create new child event
                if (!is_null($child))
                {
                    $event          = $child->addEvent();
                    $ider           = $event['ider'];

                    // set the supplied value of the event subtype
                    if (!is_null($idet))
                        $event->setIdet($idet);
                    $eventTypeText  = $translate['childEvents'][$idet];
                    if ($eventTypeText)
                        $typeText   =  ucfirst($eventTypeText);
                    else
                        $typeText       = "$idet?";
                }
                else
                {
                    $msg .= 'Unable to create Child event because idcr parameter missing or invalid. ';
                }
            }       // create new event
            else
            {       // existing event
                $idcr               = $event['idir'];
                $child              = new Child(array('idcr'    => $idcr));
                $tidet              = $event['idet'];
                $eventTypeText      = $translate['childEvents'][$tidet];
                if ($eventTypeText)
                    $typeText       =  ucfirst($eventTypeText);
                else
                    $typeText       = "$tidet?";
            }       // existing event

            $idime                  = $idcr;    // key for citations
            break;
        }           // case Citation::STYPE_CHILDEVENT

        //    idtd parameter points to To-Do records tblTD.IDTD
        case Citation::STYPE_TODO:      // 40 To-Do Item
        {
            if (is_null($idtd) || $idtd == 0)
            {
                $msg        .= $template['missingKey']->replace('$key',"idtd");
                $todo       = null;
                break;
            }
            $idime          = $idtd;    // key for citations
            $typeText       = "To Do Fact:";
            break;
        }

        default:
        {
            $msg            .= 'Invalid event type ' . $stype;
            $idime          = -1;
            $typeText       = $t['Invalid']; 
            break;
        }
    }       // take action according to citation type

    switch($stype)
    {       // act on major event type
        case Citation::STYPE_UNSPECIFIED:   // 0
        {   // to be determined
            break;
        }   // to be determined

        case Citation::STYPE_NAME:      // 1
        {
            if ($person)
            {
                if (is_null($notes))
                {
                    $notes      = $person['namenote'];
                    if (is_null($notes))
                        $notes  = '';
                }

                $prefix         = $person['prefix'];
                if (is_null($prefix))
                    $prefix     = '';

                $nametitle      = $person['title'];
                if (is_null($nametitle))
                    $nametitle  = '';
            }       // individual defined
            break;
        }

        case Citation::STYPE_BIRTH:     // 2
        {
            if ($person)
            {
                $event          = $person->getBirthEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
                $kind           = null;
            }       // individual defined
            break;
        }

        case Citation::STYPE_CHRISTEN:      // 3
        {
            if ($person)
            {
                $event          = $person->getChristeningEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
                $kind           = null;
            }       // individual defined
            break;
        }

        case Citation::STYPE_DEATH:     // 4
        {
            if ($person)
            {
                $event          = $person->getDeathEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
                $kind           = null;

                $deathCause     = $person['deathcause'];
                if (is_null($deathCause))
                    $deathCause = '';
            }       // individual defined
            break;
        }

        case Citation::STYPE_BURIED:        // 5
        {
            if ($person)
            {
                $event          = $person->getBuriedEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
                $kind           = null;
                if ($descn == '')
                {
                    $descn      = null;
                    $cremated   = false;
                }
                else
                if ($descn == 'cremated')
                {
                    $descn      = null;
                    $cremated   = true;
                }
                else
                    $cremated   = false;
            }       // individual defined
            break;
        }

        case Citation::STYPE_NOTESGENERAL:  // 6
        {
            if ($person)
            {
                if (is_null($notes))
                {
                    $notes      = $person['notes'];
                    if (is_null($notes))
                        $notes  = '';
                }
            }       // individual defined
            break;
        }

        case Citation::STYPE_NOTESRESEARCH: // 7
        {
            if ($person)
            {
                $date           = null;
                $location       = null;
                if (is_null($notes))
                {
                    $notes      = $person['references'];
                    if (is_null($notes))
                        $notes  = '';
                }
            }       // individual defined
            break;
        }

        case Citation::STYPE_NOTESMEDICAL:  // 8
        {
            if ($person)
            {
                if (is_null($notes))
                {
                    $notes      = $person['medical'];
                    if (is_null($notes))
                        $notes  = '';
                }
            }       // individual defined
            break;
        }

        case Citation::STYPE_DEATHCAUSE:    // 9
        {
            if ($person)
            {
                if (is_null($notes))
                {
                    $notes      = $person['deathcause'];
                    if (is_null($notes))
                        $notes  = '';
                }
            }       // individual defined
            break;
        }

        case Citation::STYPE_LDSB:      // 15
        {
            if ($person)
            {
                $event          = $person->getBaptismEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
            }       // individual defined
            break;
        }

        case Citation::STYPE_LDSE:      // 16
        {
            if ($person)
            {
                $event          = $person->getEndowEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
            }       // individual defined
            break;
        }

        case Citation::STYPE_LDSC:      // 26
        {
            if ($person)
            {
                $event          = $person->getConfirmationEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
            }       // individual defined
            break;
        }

        case Citation::STYPE_LDSI:      // 27
        {
            if ($person)
            {
                $event          = $person->getInitiatoryEvent(true);
                $ider           = $event['ider'];
                if ($ider > 0)
                {
                    $idime      = $ider;
                    $stype      = Citation::STYPE_EVENT;
                }
                getEventInfo($event);
            }       // individual defined
            break;
        }

        case Citation::STYPE_ALTNAME:      // 10
        {
            $notes              = '';
            break;
        }

        //    idcr parameter points to Child Record tblCR
        case Citation::STYPE_CHILDSTATUS:  // 11 Child Status    
        {
            if ($child)
            {
                $notes          = '';
            }       // child record present
            break;
        }

        case Citation::STYPE_CPRELDAD: // 12 Relationship to Father  
        {
            if ($child)
            {
                $notes          = '';
            }       // child record present
            break;
        }

        case Citation::STYPE_CPRELMOM: // 13 Relationship to Mother  
        {
            if ($child)
            {
                $notes          = '';
            }       // child record present
            break;
        }

        case Citation::STYPE_LDSP: // 17 Sealed to Parents
        {
            if ($child)
            {
                getDateAndLocationLds($child,
                                      1,
                                      'parseald',
                                      'idtrparseal');
                $notes          = $child['parsealnote'];
                if (is_null($notes))
                    $notes      = '';
                $templeReady    = $child['ldsp'];
                $kind           = 1;    // at Temple
            }       // child record present
            break;
        }

        //    idmr parameter points to LegacyMarriage Record
        case Citation::STYPE_LDSS: // 18 Sealed to Spouse
        {
            if ($family)
            {
                getDateAndLocationLds($family,
                                      1,
                                      'seald',
                                      'idtrseal');
                $templeReady    = $family['ldss'];
            }       // family defined
            break;
        }

        case Citation::STYPE_NEVERMARRIED:// 19 individual never married 
        case Citation::STYPE_MARNEVER: // 22 Never Married
        {
            if ($family)
            {
                $notmar         = $family['notmarried'];
                if ($notmar == '')
                    $notmar     = 0;
            }       // family defined
            $date               = null;
            $location           = null;
            break;
        }

        case Citation::STYPE_MAR:      // 20 Marriage 
        {
            if ($family)
            {
                getDateAndLocation($family,
                                   'mard',
                                   'idlrmar');
            }       // family defined
            else
                $warn   .= "<p>" . __LINE__ . " \$idmr=$idmr, \$family is null!</p>\n";
            break;
        }

        case Citation::STYPE_MARNOTE:  // 21 Marriage Note
        {
            if (is_null($family && $notes))
            {
                $notes          = $family['notes'];
                if (is_null($notes))
                    $notes      = '';
            }       // family defined
            break;
        }

        case Citation::STYPE_MARNOKIDS:    // 23 couple had no children  
        {
            if ($family)
            {
                $nokids         = $family['nochildren'];
                if ($nokids == '')
                    $nokids     = 0;
            }       // family defined
            $date               = null;
            $location           = null;
            break;
        }

        case Citation::STYPE_MAREND:   // 24 marriage ended date
        {
            if (is_null($date))
            {
                if ($family)
                {
                    $date       = new LegacyDate($family['marendd']);
                    $date       = $date->toString();
                }       // family defined
                else
                    $date       = '';
            }
            break;
        }

        case Citation::STYPE_EVENT:    // 30 Individual Event
        {
            if ($event)
            {
                getEventInfo($event);
                $kind               = null;

                if ($idet == Event::ET_DEATH)
                {
                    $deathCause     = $person['deathcause'];
                    if (is_null($deathCause))
                        $deathCause = '';
                }
            }       // event defined
            break;
        }   // Citation::STYPE_EVENT

        case Citation::STYPE_MAREVENT: // 31 Marriage Event
        {
            if ($event)
            {
                getEventInfo($event);
                $kind               = null;
            }       // event defined
            break;
        }   // Citation::STYPE_MAREVENT

        //    idtd parameter points to To-Do records tblTD.IDTD
        case Citation::STYPE_TODO: // 40 To-Do Item
        {
            $notes                  = '';
            break;
        }

        default:                // unsupported values
        {
            break;
        }

    }       // act on citation event type

    /********************************************************************
     *  If the location is in the form of a string, obtain the          *
     *  associated instance of Location.  This will ensure that         *
     *  short form names are resolved, and the name is displayed with   *
     *  the proper case. Also format the location name so that it can   *
     *  be inserted into the value attribute of the text input field.   *
     ********************************************************************/
    if (!is_null($location))
    {                   // location supplied
        if (is_string($location))
        {
            $locName        = $location;
            $location       = new Location(array('location' => $locName));
            if (!$location->isExisting())
                $location->save();
            $idlr           = $location->getIdlr();
            if ($debug)
                $warn   .= "<p>\$idlr set to $idlr from location '$locName'</p>\n";
        }
        $locName        = str_replace('"','&quot;',$location->getName());
    }                   // location supplied
    else                // location not supplied
        $locName        = '';
}

$template->set('IDER', $ider);               // index of Event
$template->set('IDET', $idet);               // index of EventType
$template->set('IDIR', $idir);               // index of Person
$template->set('IDNX', $idnx);               // index of Name
$template->set('IDCR', $idcr);               // index of Child
$template->set('IDMR', $idmr);               // index of Family
$template->set('IDTD', $idtd);               // index of ToDo
$template->set('ROWNUM', $rownum);           // invoking row request
$template->set('TYPE', $stype);              // event type parameter

if (strlen($msg) == 0)
{       // no errors
    if ($idet == Event::ET_NULL)
        $idet           = Event::ET_UNDEF;

    // display a selection list of event types if supported by the
    // event
    if (!is_null($idet))
    {       // event type supported
        if ($idcr)
        {   // event applies to a Child
            $template['etypeMarriage']->update(null);
            $template['etypePerson']->update(null);
        }   // event applies to a Child
        else
        if ($idir)
        {   // event applies to an Person
            $template['etypeMarriage']->update(null);
            $template['etypeChild']->update(null);
        }   // event applies to an Person
        else
        if ($idmr)
        {   // event applies to a Family
            $template['etypePerson']->update(null);
            $template['etypeChild']->update(null);
        }   // event applies to a Family
    }       // event type supported
    else
        $template['typeRow']->update();

    // display a date field if the event includes a date
    if (!is_null($date))
        $template->set('DATE', $date);
    else
        $template->set('DATE', '');

    // provide a description input field if supported
    if (!is_null($descn))
    {       // description supported
        $qdescn         = str_replace('"','&quot;',$descn);
        $edescn         = htmlspecialchars($descn);
        $template->set('DESCN', $descn);
        switch($idet)
        {       // act on specific IDET values
            case Event::ET_OCCUPATION:
            case Event::ET_OCCUPATION_1:
            {
                $template['descRow']->update(null);
                break;
            }   // occupations

            default:
            {   // other event types
                $template['occRow']->update(null);
                break;
            }   // other event types
        }       // act on specific IDET values
    }       // description supported
    else
    {
        $template->set('DESCN', '');
        $template['descRow']->update(null);
        $template['occRow']->update(null);
    }

    // provide a location input field if supported
    if (is_null($location))
        $template->set('LOCNAME', '');
    else
    {           // location supported
        if ($location instanceof Temple)
        {       // Temple
            $template['locationRow']->update(null);
            $idtr           = $location->getIdtr();
        }       // Temple
        else
        {       // Location
            $template['templeRow']->update(null);
            $template->set('LOCNAME', $locName);
        }       // Location
    }           // location supported

    // provide a location kind pair of radio buttons for some LDS
    // events that can occur either at a temple or in the field
    if (is_null($kind))
        $template['kindRow']->update(null);
    else
    if ($kind == 0)
        $template['kindRow']->update(array('templeKind' => '',
                                           'liveKind'   => "checked='checked'"));
    else
        $template['kindRow']->update(array('templeKind' => "checked='checked'",
                                           'liveKind'   => ''));

    // temple ready indicator
    if (is_null($templeReady))
        $template['templeReadyRow']->update(null);
    else
    {           // temple ready submission indicator
        if ($templeReady != 0)
            $checked = 'checked="checked"';
        else
            $checked = "";
        $template->set('templechecked', $checked);
    }           // temple ready submission indicator

    if ($stype == Citation::STYPE_NAME)
    {           // permit modifying name of individual
        // provide an input text field for name prefix
        if (!is_null($prefix))
        {       // name prefix supported
            $prefix     = str_replace('"','&quot;', $prefix);
        }       // name prefix supported

        // provide an input text field for title
        if (!is_null($nametitle))
        {       // title supported
            $nametitle = str_replace('"','&quot;', $nametitle);
        }       // title supported
    }           // permit modifying name of individual
    else
    {           // do not display name portions of template
        $template['SurnameRow']->update(null);
        $template['GivenRow']->update(null);
        $template['namePrefixRow']->update(null);
        $template['nameSuffixRow']->update(null);
    }           // do not display name portions of template

    // cremated
    if (is_null($cremated))
        $template['crematedRow']->update(null);

    // provide an input textarea for extended notes if supported
    if (is_null($notes))
        $template['notesRow']->update(null);
    else
        $template->set('notes', $notes);

    // the Order field is present in the Event record to
    // define a specific order in which these events are to be presented
    // to the user.  For the moment it is made explicitly available to
    // the user, but should be hidden once more intuitive methods of
    // ordering events are supported.
    if (is_null($order))
        $template['orderRow']->update(null);
    else
        $template->set('order', $order);

    // the Preferred field is present in the Event record to
    // identify the one instance of a particular event type that is
    // to be reported in situations where only one can be reported
    // for example there is only one Birth date and one Death date that
    // is reported in the heading of an individual page, and only one
    // that can be used for searching for individuals by date
    if (is_null($preferred))
        $template['preferredRow']->update(null);
    else
    if ($preferred)
        $template['preferredRow']->update(array('checked'   => 'checked="checked"'));
    else
        $template['preferredRow']->update(array('checked'   => ''));

    // The not married indicator is present in the Family record
    // The implementation in Legacy according to the documentation is
    // fuzzy, as there are 2 different citation types even though there
    // is only one fact to cite.  The two citation types are described
    // slightly differently.
    // - One specifies that the individual was never married.  But this
    //   should logically be an indicator in Person with no need
    //   for a Family record
    // - The other citation type describes a relationship where it is
    //   known that the couple never married.  This is the only logical
    //   meaning of an indicator in Family

    if (is_null($notmar))
        $template['notMarRow']->update(null);
    else
    if ($notmar)
        $template['notMarRow']->update(array('checked'  => 'checked="checked"'));
    else
        $template['notMarRow']->update(array('checked'  => ''));

    // no children indicator
    if (is_null($nokids))
        $template['noKidsRow']->update(null);
    else
    if ($notmar)
        $template['noKidsRow']->update(array('checked'  => 'checked="checked"'));
    else
        $template['noKidsRow']->update(array('checked'  => ''));

    // citations for the event
    if (is_null($event))
    {
        $citParms   = array('idime'             => $idime,
                            'type'              => $stype);
        $citations  = new CitationSet($citParms);
    }
    else
    {
        $citations  = $event->getCitations();
    }

    $citTemplate    = new Template($template['citTable']->outerHTML);
    $citRowTemplate = $template['sourceRow$idsx']->outerHTML;
    $text           = '';
    if ($citations)
    foreach($citations as $idsx => $cit)
    {       // loop through all citations to this fact
        $idsr   = $cit->getIdsr();
        $title  = str_replace('"','&quot;',$cit->getSource()->getTitle());
        $detail = str_replace('"','&quot;',$cit->getDetail());
        $text   .= str_replace(array('$idsx','$title','$idsr','$detail'),
                               array( $idsx , $title , $idsr , $detail),
                               $citRowTemplate);
    }       // loop through citations
    $citTemplate['sourceRow$idsx']->update($text);
    $citTemplate->setFields(array('idime'   => $idime,
                                  'type'    => $stype));
    $template['citTable']->update($citTemplate->compile());

    // display a list of existing alternate names and the ability
    // to add and delete them for the name event
    if ($stype == Citation::STYPE_NAME)
    {           // Name event, provide access to alternates
        $altNames   = $person->getNames();
        $in         = 0;
        $ntemplate  = $template['altNamesRow$idnx']->outerHTML;
        $text       = '';
        foreach($altNames as $idnx => $altName)
        {       // loop through defined alternate names
            if ($altName['order'] > 0)
            {
                $in++;
                $idnx   = $altName['idnx'];
            }   // actual alternate name
            $text       .= str_replace(array('$idnx','$altName'),
                                       array( $idnx , $altName->getName()),
                                       $ntemplate);
        }       // loop through defined alternate names
    }           // Name event, provide access to alternates
    else
        $template['altNameSet']->update();

    // provide an input textarea for cause of death
    if (is_null($deathCause))
        $template['deathCauseSet']->update();
    else
    {           // deathCause supported
        $eDeathCause    = str_replace('"','&quot;',$deathCause);
        $parms          = array('eDeathCause' => $eDeathCause);
        $template['deathCauseSet']->update($parms);

        // citations for the cause of death
        $citParms       = array('idime'     => $idir,
                                'type'      => Citation::STYPE_DEATHCAUSE);
        $citations      = new CitationSet($citParms);
        $citTemplate    = new Template($template['DcCitTable']->outerHTML);
        $citRowTemplate = $template['sourceRowDc$idsx']->outerHTML;
        $text           = '';
        if ($citations)
        foreach($citations as $idsx => $cit)
        {       // loop through all citations to this fact
            $idsr   = $cit->getIdsr();
            $title  = str_replace('"','&quot;',$cit->getSource()->getTitle());
            $detail = str_replace('"','&quot;',$cit->getDetail());
            $text   .= str_replace(array('$idsx','$title','$idsr','$detail'),
                                   array( $idsx , $title , $idsr , $detail),
                                   $citRowTemplate);
        }       // loop through citations
        $citTemplate['sourceRowDc$idsx']->update($text);
        $citTemplate->setFields(array('idime'   => $idime,
                                      'type'    => $stype));
        $template['DcCitTable']->update($citTemplate->compile());
    }           // deathCause supported
}               // no errors
else
    $template['evtForm']->update();

if (is_null($notes))
    $template['Clear']->update();

if (is_null($picIdType))
{               // exclude button for managing pictures
  $template['Pictures']->update();
  $template['PicIdType']->update();
}               // exclude button for managing pictures

$template->set('TITLE',         "Edit $typeText Event"); 
$template->set('TYPETEXT',      $typeText);
$template->set('FORTEXT',       $forText);


$template->display();
