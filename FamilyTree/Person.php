<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\TemplateTag;

/************************************************************************
 *  Person.php                                                          *
 *                                                                      *
 *  Display a web page containing details of an particular Person       *
 *  from the database table of Persons.                                 *
 *                                                                      *
 *  Parameters (passed by method="get")                                 *
 *      idir    unique numeric identifier of the Person to display      *
 *              Optional if UserRef is specified                        *
 *      UserRef user assigned identifier of the Person to display.      *
 *              Ignored if idir is specified                            *
 *                                                                      *
 * History:                                                             *
 *      2010/08/11      Fix blog code so the option to blog appears     *
 *                      if there are no existing blog messages on the   *
 *                      Person.  Change to use Ajax to add blog         *
 *                      message.                                        *
 *      2010/08/11      Cleanup parameter handling code to avoid        *
 *                      PHP warnings if parameter omitted.              *
 *      2010/08/25      fix undefined $deaths                           *
 *      2010/10/21      improve separation of HTML and PHP              *
 *                      use RecOwners class to validate access          *
 *                      use BlogList and Blog classes to access blogs   *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/10/30      use verb suggested by marriage status           *
 *      2010/11/14      include name suffix and prefix in page title    *
 *      2010/11/29      correct text when child has a mother, but no    *
 *                      father.                                         *
 *      2010/12/10      Move all HTML text output to this file to       *
 *                      clearly separate HTML and PHP and permit        *
 *                      translating the pages into other languages.     *
 *                      Various minor improvements to text.             *
 *      2010/12/11      Use easily customized template strings for event*
 *                      text.                                           *
 *      2010/12/18      The link to the nominal index in the header and *
 *                      trailer breadcrumbs points at current name      *
 *      2010/12/20      Handle exception thrown from new LegacyIndiv    *
 *                      Handle exception thrown from new LegacyLocation *
 *      2010/12/25      Improve separation of PHP, HTML, and Javascript *
 *                      in the blogging section                         *
 *      2010/12/28      Add support for location 'kind' on LDS          *
 *                      sacraments                                      *
 *      2010/12/29      Add 'button' to invoke descendants report       *
 *                      Add 'button' to invoke ancestor report          *
 *                      Move Edit URL and make a 'button'.              *
 *      2011/01/01      Break long text notes into paragraphs whereever *
 *                      two new-lines occur in the original             *
 *      2011/01/02      add reporting of LDS individual events          *
 *      2011/01/03      add alternate name list                         *
 *      2011/01/07      correct case where there is a mother and no     *
 *                      father known                                    *
 *                      Report on alternate names for the individual.   *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/01/16      add 'button' to invoke relationship calculator  *
 *      2011/01/28      pronoun missing from christening event          *
 *      2011/03/10      change Blog button appearance                   *
 *      2011/05/10      display events that only have description text  *
 *      2011/05/23      display any notes on the name of the individual *
 *      2011/05/28      display pictures                                *
 *      2011/08/08      use full month names in marriage events         *
 *                      preset pronouns and roles for spouse            *
 *      2011/08/12      add buttons for editing and deleting blog       *
 *                      messages                                        *
 *      2011/10/23      use actual buttons for functions previously     *
 *                      implemented by hyperlinks made to look like     *
 *                      buttons                                         *
 *                      also add keyboard shortcuts for most buttons    *
 *      2011/11/05      adjust indefinite article based on first letter *
 *                      of event description                            *
 *      2011/11/14      correct missing object if no spouse or partner  *
 *                      in a family                                     *
 *      2011/12/30      display prefix and suffix on children's names   *
 *                      correctly handle unusual location prepositions  *
 *                      eliminate redundant 'of' in parents             *
 *      2012/01/08      move notes and user reference number after      *
 *                      events                                          *
 *      2012/01/13      change class names                              *
 *      2012/02/26      easier to understand code for parentage         *
 *      2012/05/28      support unknown sex                             *
 *      2012/07/25      display general notes for each spouse           *
 *      2012/08/12      add button to display tree picture of family.   *
 *                      display sealing to parents if present.          *
 *      2012/10/08      display user reference field for spouse         *
 *      2012/11/20      include father's title and suffix               *
 *      2012/11/25      catch exceptions allocating spouse and children *
 *      2012/12/08      only show name of LDS temple in events          *
 *      2013/03/03      LegacyIndiv::getNextName now returns all name   *
 *                      index entries                                   *
 *      2013/03/09      permit use of Address in events                 *
 *                      honour private flag in LegacyIndiv              *
 *      2013/04/04      enclose all location names in <span> tags to    *
 *                      support mouseover popup information             *
 *      2013/04/05      use functions pageTop and pageBot to            *
 *                      standardize page appearance                     *
 *                      only use first 2 chars of given name to access  *
 *                      index                                           *
 *                      WYSIWYG editor on blogs                         *
 *      2013/04/12      add support for displaying boundary in          *
 *                      location map                                    *
 *      2013/04/20      add illegitimate relationship of child          *
 *      2013/04/21      eliminate space around each name in a hyperlink *
 *                      by using the new LegacyIndiv::getName method    *
 *      2013/04/24      add birth, marriage, and death registrations    *
 *      2013/04/27      display never married indicator                 *
 *      2013/05/10      honor invisibility                              *
 *      2013/05/14      honor cremated flag                             *
 *                      properly interpret event IDET 65                *
 *      2013/05/17      add IDIR to email subject line                  *
 *      2013/05/29      help popup for rightTop button moved to         *
 *                      common.inc                                      *
 *                      include all owners in the contact Author email  *
 *      2013/06/01      remove use of deprecated interfaces             *
 *      2013/06/13      use parameter idir= in invoking web pages       *
 *      2013/07/01      use <a class="button"> instead of               *
 *                      <td class="button">                             *
 *      2013/08/02      show family and parent information in the       *
 *                      individual popup                                *
 *      2013/10/26      display citations for marriage ended date       *
 *      2013/10/27      change text for marriage event IDET 70          *
 *      2013/11/15      handle lack of database server connection       *
 *      2013/11/28      defer loading Google(r) maps API to speed up    *
 *                      page display                                    *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2013/12/19      correct display of child to parent relationships*
 *      2014/01/31      do not use obsolete LegacyIndiv::getChildRecord *
 *      2014/01/16      show not married indicator before death.        *
 *      2014/03/17      use CSS rather than tables to lay out list      *
 *                      of children, and list of footnotes              *
 *                      interface to Picture made more intuitive        *
 *                      replace deprecated call to LegacyPictureList    *
 *                      with call to Picture::getPictures               *
 *      2014/03/25      class BlogList replaced by static method of     *
 *                      class Blog                                      *
 *      2014/04/08      class LegacyAltName renamed to LegacyName       *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/05/16      use Event for all events                        *
 *      2014/05/24      support cause of death popover for more than    *
 *                      2 individuals                                   *
 *      2014/06/10      handle change to functionality of               *
 *                      LegacyIndiv::getEvents                          *
 *      2014/06/18      show final marriage status                      *
 *      2014/06/29      always show blog to collect e-mail addresses    *
 *                      add id parameter to elements with only name     *
 *      2014/07/11      remove 'Notes:' prefix on marriage notes        *
 *      2014/07/15      support for popupAlert moved to common code     *
 *      2014/07/19      remove 'Note:' prefix from individual events.   *
 *      2014/08/05      add explicit instructions for requestin access  *
 *                      to a private individual                         *
 *      2014/09/08      strip paragraph tags off event note             *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/10/03      clean up initialization                         *
 *                      use Record::displayPictures                     *
 *                      display pictures associated with birth,         *
 *                      christening, death, and burial.                 *
 *      2014/10/15      events moved from tblIR and tblMR to tblER      *
 *      2014/11/29      print $warn, which may contain debug trace      *
 *      2014/12/03      handle exception on bad mother idir             *
 *      2014/12/11      LegacyIndiv::getFamilies and ::getParents       *
 *                      now return arrays indexed on IDMR               *
 *      2014/12/19      always use LegacyIndiv::getBirthEvent and       *
 *                      getDeathEvent, not obsolete fields to get       *
 *                      birth and death information.                    *
 *      2015/01/11      add support for Ancestry Search                 *
 *      2015/01/12      hide support for Ancestry Search as it makes    *
 *                      more sense to move that to editIndivid.php      *
 *      2015/01/23      add accessKey attribute to buttons              *
 *      2015/02/04      add e-mail address to blog postings by visitors *
 *                      misspelled sub for givenname in indiv popu      *
 *      2015/02/21      correct reflexive pronoun in ethnicity phrase   *
 *      2015/03/30      provide more explicit instructions for          *
 *                      accessing private individuals if user is        *
 *                      not signed on.                                  *
 *                      use LegacyIndiv::getName to format child name   *
 *      2015/04/06      use LegacyIndiv::getName to format title        *
 *                      use LegacyIndiv::getBPrivLim and ::getDPrivLim  *
 *                      to obtain event privacy limits                  *
 *      2015/05/01      missing space after closing period if only      *
 *                      the father defined.                             *
 *                      source popup laid out here instead of built     *
 *                      at runtime from template                        *
 *                      individ popup laid out here instead of built    *
 *                      at runtime from template                        *
 *                      emit comma between footnote ref for name not    *
 *                      and footnote refs for name citations            *
 *                      functionality for laying out events moved here *
 *                      from class LegacyIndiv                          *
 *      2015/05/14      add button to request permission to update      *
 *                      if the user is logged on but not an owner       *
 *      2015/05/25      handle URL redirected from old static site      *
 *                      move formatting of source citations here from   *
 *                      class Citation                                  *
 *                      standardize <h1>                                *
 *      2015/05/29      add individuals from event descriptions         *
 *                      into popups                                     *
 *                      ensure that links to individuals are absolute   *
 *      2015/06/11      links inserted by tinyMCE use double-quote      *
 *      2015/06/22      make notes label for spouse highlighted like    *
 *                      the notes label of the primary individual       *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/07/06      add a button in the location popup to edit      *
 *                      the location description                        *
 *      2015/07/16      notes omitted from location popup               *
 *                      handle bad birth/death IDLR                     *
 *                      bad URL in header with surname containing quote *
 *      2015/07/22      information on parents moved ahead of birth     *
 *                      event so all events are handled alike           *
 *                      reference to NameNote in tblIR is handled       *
 *                      as an ordinary citation                         *
 *      2015/07/27      place alternate name information in line after  *
 *                      names of parents and before first event         *
 *                      move the display of name and parents out of     *
 *                      function showEvents for clarity                 *
 *      2015/08/08      clean up text for reporting individual as       *
 *                      marital status single                           *
 *      2015/08/09      if the individual has a parents record, but     *
 *                      there are not parents identified by that record *
 *                      indicate no parents and check for siblings.     *
 *      2015/08/11      support treename                                *
 *      2015/08/26      suppress article in front of titles of nobility *
 *                      as an occupation                                *
 *      2015/09/02      page number was not inserted into match for     *
 *                      Wesleyan Methodist Baptisms                     *
 *      2015/11/23      exception from unexpected URL                   *
 *      2016/01/19      add id to debug trace                           *
 *                      display notes with style notes                  *
 *                      insert class="notes" into <p> with no class     *
 *      2016/02/06      use showTrace                                   *
 *                      for some county marriage references provide     *
 *                      link to see the transcription                   *
 *      2016/03/24      display events with IDET=1                      *
 *      2016/11/25      display aka note for alternate name.            *
 *      2016/12/09      determine geocoder search parm for each location*
 *      2016/12/30      undefined $unknownChildRole                     *
 *                      catch invalid IDIR in showEvent                 *
 *      2017/01/03      handle null status value                        *
 *      2017/01/23      do not use htmlspecchars to build input values *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *      2017/06/03      privatize spouse events according to birth      *
 *                      date of spouse                                  *
 *                      privatise childhood related individual events   *
 *                      using birth privacy limit, not death limit      *
 *                      prompt the visitor to request access if there   *
 *                      are any private items                           *
 *      2017/07/07      support popups for links to individuals in      *
 *                      notes field.  Support multiple links to         *
 *                      individuals both in notes field and in event    *
 *                      description.                                    *
 *      2017/07/23      class LegacyPicture renamed to class Picture    *
 *      2017/07/27      class LegacyCitation renamed to class Citation  *
 *      2017/07/30      class LegacySource renamed to class Source      *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/16      renamed to Person.php                           *
 *      2017/08/18      class LegacyName renamed to Name                *
 *      2017/09/02      class LegacyTemple renamed to Temple            *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/05      change class LegacyFamily to class Family       *
 *                      $idir not set if userref parameter used         *
 *                      validate parameter values, somebody tried an    *
 *                      insertion which generated an exception that     *
 *                      would be inexplicable to most users             *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/19      use CitationSet in place of getCitations        *
 *      2017/12/18      no field idarevent in Event                     *
 *      2018/01/27      use Template                                    *
 *      2018/02/10      implement most of internationalization          *
 *      2018/02/16      internationalize "sealed to"                    *
 *                      create person popups for marriage notes         *
 *      2018/09/15      urlencode subject in contact author             *
 *      2018/12/03      Citation::toHTML changed to return text         *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/07/18      use Person::getPerson                           *
 *      2019/07/21      use Location::getLocation                       *
 *      3019/08/09      support both old and new style citations to     *
 *                      primary name                                    *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/01/21      translate warnings that appear in title         *
 *      2020/01/23      add space below last child                      *
 *      2020/02/02      Family::getEvents returns all family events     *
 *                      in order                                        *
 *      2020/03/13      LegacyDate::setTemplate is now done by          *
 *                      class FtTemplate                                *
 *                      add better fixup for lost records for parents   *
 *                      and spouses                                     *
 *      2020/03/19      hide empty marriage events                      *
 *      2020/06/02      avoid exception on undefined locations          *
 *      2020/08/22      do not ask for parents or families if IDIR 0    *
 *      2020/12/03      correct XSS issues                              *
 *      2021/03/13      change implementation of showEvent to use       *
 *                      translate table in template and use simple      *
 *                      text substitution.                              *
 *      2021/03/19      migrate to ES2015                               *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/User.inc';
//require_once 'customization.php';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *                                                                      *
 *  key phrases that need to be translated to support other languages   *
 *                                                                      *
 ***********************************************************************/

/************************************************************************
 *  dateTemplate                                                        *
 *                                                                      *
 *  Template governing the appearance of dates.                         *
 ***********************************************************************/

static $dateTemplate    = '[dd] [Month] [yyyy][BC]';

/************************************************************************
// gender pronouns for the current language                             *
 ***********************************************************************/
$malePronoun            = 'He';
$femalePronoun          = 'She';
$otherPronoun           = 'He/She';
$maleChildRole          = 'son';
$femaleChildRole        = 'daughter';
$unknownChildRole       = 'child';
$deathcause             = array();

/************************************************************************
 *  $statusText                                                         *
 *                                                                      *
 *  This table provides a translation from an marriage status to        *
 *  the text to display to the user for the pre-defined values in       *
 *  the current language.                                               *
 ************************************************************************/
static $statusText      = array();

/************************************************************************
 *  $nextFootnote       next footnote number to use                     *
 ************************************************************************/
$nextFootnote           = 1;

/************************************************************************
 *  $citTable           table to map footnote number to citation        *
 *                                                                      *
 *      Each entry in this table is:                                    *
 *      o  an object implementing the method toHtml, such as an         *
 *         instance of Citation, or                                     *
 *      o  an object implementing the method getNotes, or               *
 *      o  a string                                                     *
 ************************************************************************/
$citTable               = array();

/************************************************************************
 *  $citByVal       table to map displayed unique citation string value *
 *                  to a footnote number to eliminate duplicates.       *
 ************************************************************************/
$citByVal               = array();

/************************************************************************
 *  $sourceTable    table to map IDSR to instance of Source             *
 ************************************************************************/
$sourceTable            = array();

/************************************************************************
 *  $individTable   table to map IDIR to instance of Person             *
 ************************************************************************/
$individTable           = array();

/************************************************************************
 *  $locationTable  table to map IDLR to instance of Location           *
 ************************************************************************/
$locationTable          = array();

/************************************************************************
 *  $templeTable    table to map IDTR to instance of Temple             *
 ************************************************************************/
$templeTable            = array();

/************************************************************************
 *  $addressTable   table to map IDAR to instance of Address            *
 ************************************************************************/
$addressTable           = array();

/************************************************************************
 *  function createPopups                                               *
 *                                                                      *
 *  Create popups for any Persons identified by hyper-links in          *
 *  the supplied text.                                                  *
 *                                                                      *
 *  Parameters:                                                         *
 *      $desc       text to check for hyper-links to Persons            *
 *                                                                      *
 *  Returns:    the supplied text, ensuring that the hyperlinks use     *
 *              absolute URLs.                                          *
 ************************************************************************/
function createPopups($desc)
{
    global  $debug;
    global  $warn;
    global  $individTable;
    global  $lang;
    global  $t;

    $pieces         = explode('<a ', $desc);
    $first          = true;
    $piece          = $pieces[0];
    $tran           = $t[$piece];
    if (is_string($tran) && strlen($tran) > 0)
        $piece      = $tran;
    $retval         = $piece;
    for($ip = 1; $ip < count($pieces); $ip++)
    {       // description contains a link
        $piece      = $pieces[$ip];
        $retval     .= "<a ";
        $urlstart   = strpos($piece, "href=");
        // $quote is either single or double quote
        $quote      = substr($piece, $urlstart + 5, 1);
        $urlstart   += 6;
        $urlend     = strpos($piece, $quote, $urlstart);
        $url        = substr($piece,
                                 $urlstart,
                                 $urlend - $urlstart);
        $equalpos = strrpos($url, "idir=");
        if ($equalpos !== false)
        {       // link to a Person
            $refidir     = substr($url, $equalpos + 5);
            if (preg_match('/^\d+/', $refidir, $matches))
            {
                $refind     = Person::getPerson($matches[0]);
                $individTable[$refidir] = $refind;
                if (substr($url, 0, $equalpos) == "Person.php?")
                    $retval .= substr($piece, 0, $urlstart) .
                                 "/FamilyTree/" .
                                 substr($piece, $urlstart);
                else
                    $retval .= $piece;
            }
            else
                $retval     .= $piece;
        }
        else
            $retval         .= $piece;
    }       // description contains a link
    return $retval;
}       // function createPopups

/************************************************************************
 *  function addFootnote                                                *
 *                                                                      *
 *  Add a footnote.                                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      $key            string representation of the citation           *
 *                      for uniqueness check                            *
 *      $cit            instance of Citation, or a string               *
 *                                                                      *
 *  Returns:                                                            *
 *      assigned footnote number                                        *
 ************************************************************************/
function addFootnote($key,
                     $cit)
{
    global      $nextFootnote;
    global      $citByVal;
    global      $citTable;

    if (array_key_exists($key, $citByVal))
    {       // citation can use existing footnote number
        $footnote     = $citByVal[$key];
    }       // citation can use existing footnote number
    else
    {       // citation needs new footnote number
        $footnote     = $nextFootnote;
        $nextFootnote++;
        $citByVal[$key]     = $footnote;
        $citTable[$footnote] = $cit;
    }       // citation needs new footnote number
    return $footnote;
}       // function addFootnote

/************************************************************************
 *  function showCitations                                              *
 *                                                                      *
 *  Given the description of a Person event identify the                *
 *  source citations for that event, generate the HTML for              *
 *  superscript footnote references, and add the footnote to the        *
 *  current page.                                                       *
 *                                                                      *
 *  Input:                                                              *
 *      $event          instance of Event or citation type in tblSX     *
 *      $idime          record identifier of the event or object which  *
 *                      the citation documents                          *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing HTML for citations.                           *
 ************************************************************************/
function showCitations($event,
                       $idime = null,
                       $comma = '')
{
    global  $debug;
    global  $warn;
    global  $connection;
    global  $template;

    $retval             = '';

    // query the database
    if ($event instanceof Event)
    {       // citation to event
        $citations      = $event->getCitations();
    }       // citation to event
    else
    {       // citation to non-event information
        $citType        = $event;
        $citparms       = array('idime'     => $idime,
                                'type'     => $citType,
                                'template'  => $template);
        $citations      = new CitationSet($citparms);
    }       // citation to non-event information

    foreach($citations as $idsx => $cit)
    {       // loop through all citation records
        // manage the tables of citations
        $footnote       = addFootnote($cit->getName(false), $cit);
        $retval         .= "<sup>$comma<a href=\"#fn$footnote\">$footnote</a></sup>";
        $comma          = ',';
    }       // loop through all event records

    return $retval;
}       // function showCitations

/************************************************************************
 *  function showCitationTable                                          *
 *                                                                      *
 *  Dump the accumulated list of citation footnotes.                    *
 ************************************************************************/
function showCitationTable()
{
    global $debug;
    global $warn;
    global $lang;
    global $template;
    global $citTable;
    global $citByVal;
    global $sourceTable;

    $parmTable = array();

    foreach($citTable as $key => $cit)
    {           // loop through all citations
        $entry             = array('key' => $key);
        // generate HTML for this footnote
        if ($cit instanceof Citation)
        {       // invoke toHtml method
            $entry['text']          = $cit->toHTML($lang);
            $idsr                   = $cit->getIdsr();
            if ($idsr > 1)
            {
                $source             = $cit->getSource();
                $source->setTemplate($template);
                $sourceTable[$idsr] = $source;
            }
        }       // invoke toHtml method
        else
        {       // not instance of Citation
            if (is_object($cit) && method_exists($cit, 'getNotes'))
            {       // invoke getNotes method
                        $entry['text']     = $cit->getNotes();
            }       // invoke getNotes method
            else
            {       // treat as text
                        $entry['text']     = $cit;
            }       // treat as text
        }         // not instance of Citation
        $parmTable[$key]     = $entry;
    }           // for each
    $template->updateTag('footnote$key', $parmTable);
}       // function showCitationTable

/************************************************************************
 *  function showEvent                                                  *
 *                                                                      *
 *  Generate the HTML to display information about an event             *
 *  of a Person.                                                        *
 *                                                                      *
 *  Parameters:                                                         *
 *      $pronoun        pronoun appropriate for described Person        *
 *      $gender         gender of Person being described                *
 *      $event          instance of Event containing the event          *
 *                      information                                     *
 *      $template       string template to fill in                      *
 *                      This template has substitution points for:      *
 *                          $Pronoun                                    *
 *                          $reflexivePronoun                           *
 *                          $possesivePronoun                           *
 *                          $onDate                                     *
 *                          $Location                                   *
 *                          $atLocation                                 *
 *                          $Description                                *
 *                          $Temple                                     *
 *                          $Notes                                      *
 *                          $Citations                                  *
 ***********************************************************************/
function showEvent($pronoun,
                   $gender,
                   $event,
                   $template)
{
    global  $t;
    global  $individTable;
    global  $somePrivate;
    global  $debug;
    global  $warn;

    // determine privacy limits from the associated Person
    // previously the privacy limits of the primary Person were used
    // which meant a younger spouse could have private information revealed
    $person         = $event->getPerson();
    $bprivlim       = $person->getBPrivLim();
    $dprivlim       = $person->getDPrivLim();
    if ($debug)
        $warn       .= "<p>Person::showEvent: event=" .  $event['ider'] .
                           ", person=" .  $person['idir'] . ', ' .
                           "bprivlim=$bprivlim, dprivlim=$dprivlim, " .
                           ".</p>\n";

    // extract information on event
    // $dateo is an instance of LegacyDate
    // $date is the text expression of the date
    $dateo          = new LegacyDate($event->get('eventd'));
    $dmsg           = $dateo->getMessage();
    if (strlen($dmsg) > 0)
        print "<p>$dmsg: \$event=" .
                        print_r($event, true) . "</p>\n";

    $citType        = $event->getCitType();
    $idet           = $event->get('idet');
    if ($citType == Citation::STYPE_BIRTH ||
        $citType == Citation::STYPE_CHRISTEN ||
        $citType == Citation::STYPE_LDSB ||
        ($citType == Citation::STYPE_EVENT &&
         ($idet == Event::ET_BIRTH ||
          $idet == Event::ET_CHRISTENING ||
          $idet == Event::ET_LDS_BAPTISM ||
          $idet == Event::ET_BARMITZVAH ||
          $idet == Event::ET_BASMITZVAH ||
          $idet == Event::ET_BLESSING ||
          $idet == Event::ET_CIRCUMCISION ||
          $idet == Event::ET_CONFIRMATION ||
          $idet == Event::ET_LDS_CONFIRMATION ||
          $idet == Event::ET_FIRST_COMMUNION
         )
        )
       )        // childhood events
        $date           = $dateo->toString($bprivlim, true, $t);
    else        // adult events
        $date           = $dateo->toString($dprivlim, true, $t);
    if (strtolower($date) == 'private')
        $somePrivate    = true;

    if ($debug)
    {
        $warn   .= "<p>showEvent('" . htmlspecialchars($pronoun) . "'," . 
                    htmlspecialchars($gender) . ",event,'" .
                    htmlspecialchars($template) . "')</p>\n" .
                    "<p>citType=" . htmlspecialchars($citType) . 
                    ", IDET=" . htmlspecialchars($idet) . 
                    ", date='" . htmlspecialchars($date) . "'</p>\n";
    }

    // identify pronouns
    if ($person['gender'] == Person::MALE)
    {
        $pronoun            = $t['He'];
        $reflexivePronoun   = $t['Himself'];
        $possesivePronoun   = $t['His'];
    }
    else
    if ($person['gender'] == Person::FEMALE)
    {
        $pronoun            = $t['She'];
        $reflexivePronoun   = $t['Herself'];
        $possesivePronoun   = $t['Hers'];
    }
    else
    {
        $pronoun            = $t['He/She'];
        $reflexivePronoun   = $t['His/Herself'];
        $possesivePronoun   = $t['His/Hers'];
    }

    $bprivlim       = $person->getBPrivLim();   // birth privacy limit year
    // the first letter of the date text string is folded to lower case
    // so it can be in middle of sentence
    if (strlen($date) >= 1 && substr($date, 0, 1) != 'Q')
        $date     = strtolower(substr($date, 0, 1)) . substr($date, 1);

    // resolve the location
    $idlr           = $event->get('idlrevent');
    $kind           = $event->get('kind');  // may be IDTR or IDLR
    if ($idlr > 0)
    {       // Location or Temple used
        if ($kind)
            $loc = new Temple(array('idtr' => $idlr));
        else
            $loc = Location::getLocation($idlr);
    }       // Location or Temple used
    else
        $loc     = null;

    $idar     = $event->get('idar');
    if (is_null($idar))
        $idar     = 0;

    if ($idar > 1)
    {       // Address used
        $loc = new Address(array('idar' => $idar));
    }       // Address used

    // check for description text
    $description            = $event->get('description');
    if (strlen($date) > 0 ||
        $idlr > 1 ||
        $idar > 1 ||
        strlen($description) > 0)
    {       // there is a non-empty event of this kind
        $notes              = $event->get('desc');
        if (strlen($notes) > 7 &&
            substr($notes, 0, 3) == '<p>' &&
            substr($notes, strlen($notes) - 4) == '</p>')
            $notes          = substr($notes, 3, strlen($notes) - 7);
        if (strlen($notes) > 0)
            $notes          = str_replace("\r\r", "\n<p>", $notes);

        print str_replace(array('$Pronoun',
                                '$reflexivePronoun',
                                '$possesivePronoun',
                                '$onDate',
                                '$Location',
                                '$atLocation',
                                '$Description',
                                '$Temple',
                                '$Notes',
                                '$Citations'),
                          array($pronoun,
                                $reflexivePronoun,
                                $possesivePronoun,
                                $date,
                                showLocation($loc,'',''),
                                showLocation($loc),
                                createPopups($description),
                                showLocation($loc),
                                $notes,
                                showCitations($event)),
                          $template);

    }       // there is an event of this kind
    print "\n";
}       // function showEvent

/************************************************************************
 *  function showLocation                                               *
 *                                                                      *
 *  Display a location as part of an event in a standard way.           *
 *                                                                      *
 *      Input:                                                          *
 *          $location   instance of Location, Temple, or                *
 *                      Address                                         *
 *          $comma      separator between footnote references           *
 *          $defPrep    default preposition before place names          *
 ************************************************************************/
function showLocation($location,
                      $comma    = '',
                      $defPrep  = 'at')
{
    global  $t;
    global  $locindex;
    global  $locationTable;
    global  $templeTable;
    global  $addressTable;
    global  $lang;

    $retval                 = '';
    if ($location instanceof Location)
    {               // Location
        if ($location->isExisting())
        {
            $idlr           = $location->getIdlr();
            $locationTable[$idlr] = $location;
        }
        else
            $idlr           = "new_location ";
        $idprefix           = 'showLoc';
    }               // Location
    else
    if ($location instanceof Temple)
    {               // Temple
        if ($location->isExisting())
        {
            $idlr         = $location->getIdtr();
            $templeTable[$idlr] = $location;
        }
        else
            $idlr           = "new_temple ";
        $idprefix           = 'showTpl';
    }               // Temple
    else
    if ($location instanceof Address)
    {               // Address
        if ($location->isExisting())
        {
            $idlr         = $location->getIdar();
            $addressTable[$idlr] = $location;
        }
        else
            $idlr           = "new_address ";
        $idprefix           = 'showAdr';
    }               // Address
    else
    {               // unsupported
        return "";
    }               // unsupported

    $locname            = $location->toString();
    if (strlen($locname) > 0)
    {               // location defined
        if ($defPrep == 'at')
        {
            $prep           = $location->getPreposition();
            if ($prep == '')
                $prep       = 'at';
            if (array_key_exists($prep, $t))
                $retval     .= $t[$prep];
            else
                $retval     .= $prep;
        }
        else
        if (strlen($defPrep) > 0)
        {
            if (array_key_exists($defPrep, $t))
                $retval     .= $t[$defPrep];
            else
                $retval     .= $defPrep;
        }

        $retval         .= " <span id=\"{$idprefix}{$locindex}_{$idlr}\">$locname</span>\n";
        $locindex++;
    }               // location defined
    return $retval;
}       // function showLocation

/************************************************************************
 *  function showParents                                                *
 *                                                                      *
 *  Generate the HTML to display information about the parents          *
 *  of a Person.                                                   *
 *                                                                      *
 *  Input:                                                              *
 *      $person     reference to an instance of Person                  *
 ************************************************************************/
function showParents($person)
{
    global  $debug;
    global  $warn;
    global  $directory;
    global  $childRole;
    global  $pronoun;
    global  $cpRelType;
    global  $t;
    global  $intStatus;
    global  $individTable;
    global  $lang;

    $idir           = $person->getIdir();

    // show information about parents
    $allParents     = $person->getParents();// RecordSet of family records

    if ($allParents->count() > 0)
    {       // at least one set of parents
        // the role the child plays in the family (son, daughter, or child)
        $role                   = $childRole[$person['gender']];

        foreach($allParents as $idmr => $parents)
        {       // loop through all sets of parents
            $childRec         = $parents->getChildByIdir($idir);

            // extract values to display from marriage
            $dadid              = $parents->get('idirhusb');
            $momid              = $parents->get('idirwife');
            $dadrel             = '';
            $momrel             = '';

            if ($dadid || $momid)
            {   // at least one parent defined
                // check for non-zero child status
                $status         = $childRec['idcs'];
                if ($status > 0)
                {
                    if (array_key_exists($status, $intStatus))
                        $status = $intStatus[$status];
                    else
                        $status = "unknown status index '$status'";
                }
                else
                    $status = '';

                // determine relationship code for each parent
                if ($dadid)
                {       // father is defined
                    $dad        = new Person(array('idir' => $dadid));
                    if (!$dad->isExisting())
                    {
                        $dad['givenname']   = "Father of " . $person['givenname'];
                        $dad['surname']     = $person['surname'];
                        $dad->save();
                    }
                    $individTable[$dadid] = $dad;
                    $dadrel             = $cpRelType[$childRec['idcpdad']];
                    if ($momid == 0)
                    {       // mother not recorded
                        $momrel         = $dadrel;
                        $endofparents   = ".\n";
                    }       // mother not recorded
                    else
                        $endofparents   = '';
                    $gender             = $person->getGenderClass();
                    print $t["was the[$gender]"] . ' ' . 
                      $t[$status] . ' ' .
                      $t[$dadrel] . ' ' .
                      $role . ' ' . $t['of'] . "\n";
?>
    <a href="<?php print $directory; ?>Person.php?idir=<?php print $dadid ?>&amp;lang=<?php print $lang; ?>" class="male"><?php print $dad->getName(); ?></a><?php print $endofparents; ?>
<?php
                }       // father is defined

                if ($dadid && $momid)   // both parents defined
                    print " " . $t['and'] . " ";
                if ($momid > 0)
                {       // mother is defined
                    $mom             = new Person(array('idir' => $momid));
                    if (!$mom->isExisting())
                    {           // fixup
                        $mom['givenname']   = "Mother";
                        $mom['surname']     = "Motherof" . 
                            str_replace(' ','', $person['givenname'] . $person['surname']);
                        $mom->save();
                    }           // fixup
                    $individTable[$momid] = $mom;
                    $momrel             = $cpRelType[$childRec['idcpmom']];
                    if ($dadid == 0 || $momrel != $dadrel)
                    {       // mother's relationship is different
                        print "$momrel $role " . $t['of'] . "\n";
                    }       // mother's relationship is different
?>
<a href="<?php print $directory; ?>Person.php?idir=<?php print $momid; ?>&amp;lang=<?php print $lang; ?>" class="female"><?php print $mom->getName(); ?></a>.
<?php
                }           // mother is defined
            }               // at least one parent defined
            else
            {               // no parents defined in family
                print $t['has no recorded parents'] . ". ";
                $siblings = $parents->getChildren();
                if (count($siblings) > 1)
                {           // has siblings
                    print $pronoun . ' ' . $t['had'] . ' ';
                    foreach($siblings as $idcr => $sibChildRec)
                    {       // loop through siblings
                        $sibIdir = $sibChildRec['idir'];
                        if ($sibIdir != $idir)
                        {   // not self
                            $sibling = $sibChildRec->getPerson();
                            $sibGender = $sibling['gender'];
                            // set the class to color hyperlinks
                            if ($sibGender == Person::MALE)
                            {
                                $cgender = $t['male'];
                                $sibRole = $t['brother'];
                                $article = $t['a[masc]'];
                            }
                            else
                            if ($sibGender == Person::FEMALE)
                            {
                                $cgender = $t['female'];
                                $sibRole = $t['sister'];
                                $article = $t['a[fem]'];
                            }
                            else
                            {
                                $cgender = $t['unknown'];
                                $sibRole = $t['sibling'];
                                $article = $t['a[masc]'];
                            }
                            $sibName = $sibling->getName();
                            print "$article $sibRole";
?>
                <a href="<?php print $directory; ?>Person.php?idir=<?php print $sibIdir; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $cgender; ?>">
                    <?php print $sibName; ?>
                </a>
<?php
                        }       // not self
                    }           // loop through siblings
?>
.
<?php
                }               // has siblings
            }                   // no parents defined in family

            // check for additional information in child record
            $seald = $childRec->get('parseald');
            $idtrseal = $childRec->get('idtrparseal');
            $sealnote = $childRec->get('parsealnote');

            if (strlen($seald) > 0 ||
                $idtrseal > 1 ||
                strlen($sealnote) > 0)
            {       // sealed to parents
                $date = new LegacyDate($seald);
                $datestr= $date->toString($bprivlim, true, $t);
                if (strtolower($datestr) == 'private')
                    $somePrivate = true;
                $temple = new Temple(array('idtr' => $idtrseal));
                print $pronoun . ' ' . $t['was sealed to parents'] . ' ' .
                    $datestr . ' ' . $t['at'] .
                    $temple->getName() . '. ' . $sealnote;
            }       // sealed to parents
        }       // loop through parents
    }       // at least one set of parents
    else
    {       // no parents defined
        print $t['has no recorded parents'] . ". ";
    }       // no parents defined

}       // function showParents

/************************************************************************
 *  function showEvents                                                 *
 *                                                                      *
 *  Given the identifier of a Person, extract information               *
 *  about that Person's Events.                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      $person     Person whose events are to be displayed.            *
 ************************************************************************/
function showEvents($person)
{
    global  $debug;             // is debugging output enabled
    global  $warn;              // accumulated warning messages
    global  $template;          // the main page template
    global  $user;              // instance of User
    global  $eventText;         // table of phrases
    global  $dateTemplate;      // template for displaying dates
    global  $months;            // table of month abbreviations
    global  $lmonths;           // table of full month names
    global  $t;                 // translate words and phrases
    global  $malePronoun;       // male pronoun nominative case
    global  $femalePronoun;     // female pronoun nominative case
    global  $otherPronoun;      // unknown pronoun nominative case
    global  $private;           // $person is private
    global  $somePrivate;       // some information e.g. death is private
    global  $lang;              // requested language of communication
    global  $family;
    global  $spsid;
    global  $spsName;
    global  $spsclass;

    // initialize fields used in the event descriptions
    $idir           = $person->getIdir();

    $givenName      = $person->getGivenName();
    $surname        = $person->getSurname();
    $gender         = $person['gender'];
    if ($person['gender'] == Person::MALE)
        $pronoun = $malePronoun;
    else
    if ($person['gender'] == Person::FEMALE)
        $pronoun = $femalePronoun;
    else
        $pronoun = $otherPronoun;
    $bprivlim       = $person->getBPrivLim();   // birth privacy limit year
    $dprivlim       = $person->getDPrivLim();   // death privacy limit year

    // display the event table entries for this Person
    $events         = $person->getEvents();
    foreach($events as $ider => $event)
    {               // loop through all event records
        // interpret event type
        $idet       = $event->get('idet');
        if ($idet > 0)
        {       // non-empty event
            showEvent($pronoun,
                      $gender,
                      $event,
                      $eventText[$idet]);       // template
        }       // non-empty event

        // certain index values are used for events that have
        // additional information to display
        if (is_string($ider))
        {           // special events
            switch($ider)
            {           // act on specific special entries
                case 'birth':
                {       // birth event
                    $person->displayPictures(Picture::IDTYPEBirth);
                    break;
                }       // birth event

                case 'christening':
                {       // christening event
                    $person->displayPictures(Picture::IDTYPEChris);
                    break;
                }       // christening event

                case 'death':
                {       // on death event also display cause of death
                    global $deathcause; // array of death causes
                    $cause      = $person->get('deathcause');

                    if (strlen($cause) > 0)
                    {       // death cause present
                        $deathcause[] = $cause;
                        $deathid = 'DeathCause' . count($deathcause);
                        print $t['The cause of death was'];
?>
    <span id="<?php print $deathid; ?>">
                <?php print $cause; ?>
<?php
                        print showCitations(Citation::STYPE_DEATHCAUSE,
                                            $idir);
?>
    </span>.
<?php
                    }   // death cause present

                    // also display any pictures associated with the death event
                    $person->displayPictures(Picture::IDTYPEDeath);
                    break;
                }       // death event

                case 'buried':
                {       // buried event
                    $person->displayPictures(Picture::IDTYPEBuried);
                    break;
                }       // buried event

            }           // act on specific special entries
        }               // special events
        else
        if (is_int($ider))
        {               // standard events
            switch($idet)
            {           // act on specific event types
                case Event::ET_BIRTH:
                {       // buried event
                    $person->displayPictures(Picture::IDTYPEBirth);
                    break;
                }       // buried event

                case Event::ET_CHRISTENING:
                {       // buried event
                    $person->displayPictures(Picture::IDTYPEChris);
                    break;
                }       // buried event

                case Event::ET_DEATH:
                {       // on death event also display cause of death
                    global $deathcause; // array of death causes
                    $cause  = $person->get('deathcause');

                    if (strlen($cause) > 0)
                    {       // death cause present
                    $deathcause[] = $cause;
                    $deathid = 'DeathCause' . count($deathcause);
                    print $t['The cause of death was'];
?>
    <span id="<?php print $deathid; ?>">
                <?php print $cause; ?>
<?php
                    print showCitations(Citation::STYPE_DEATHCAUSE,
                                        $idir);
?>
    </span>.
<?php
                    }   // death cause present

                    // also display any pictures associated with the death event
                    $person->displayPictures(Picture::IDTYPEDeath);
                    break;
                }       // death event

                case Event::ET_BURIAL:
                {       // buried event
                    $person->displayPictures(Picture::IDTYPEBuried);
                    break;
                }       // buried event

            }           // act on specific event types
        }               // standard events
    }                   // loop through all event records

    // check if never married
    $nevermarried = $person->get('nevermarried');
    if ($nevermarried > 0)
        print $pronoun . ' ' . $t['was never married'] . ". ";

}       // function showEvents

/************************************************************************
 *  function displayEvent                                               *
 *                                                                      *
 *  Display a marriage event.                                           *
 *                                                                      *
 *  Parameters:                                                         *
 *      $ider           IDER                                            *
 *      $event          instance of Event                               *
 *      $family         instance of Family                              *
 *      $pronoun        language specific pronoun for Person            *
 *      $spsid          IDIR of spouse's instance of Person             *
 *      $spsName        instance of Name                                *
 *      $spsclass       gender class name                               *
 ************************************************************************/
function displayEvent($ider,
                      $event,
                      $family,
                      $pronoun,
                      $spsid,
                      $spsName,
                      $spsclass)
{
    global  $lang;
    global  $directory;
    global  $t;
    global  $eventText;
    global  $warn;

    // display event
    $idet                       = $event->getIdet();
    if ($idet == Event::ET_MARRIAGE)
    {                       // marriage event
        $date                   = $event->getDate();
        $idlrmar                = $event->get('idlrevent');
        print $pronoun . ' ' . $t[$family->getStatusVerb()];
        // only display a sentence about the marriage
        // if there is a spouse defined
        if ($spsid > 0)
        {                   // have a spouse
?>
        <a href="<?php print $directory; ?>Person.php?idir=<?php print $spsid; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $spsclass; ?>"><?php print $spsName->getName(); ?></a>
<?php
        }                   // have a spouse
        else
        {                   // do not have a spouse
            print " " . $t['an unknown person'];
        }                   // do not have a spouse
        
        if (strlen($date) > 0)
        {
            if (ctype_digit($date))
                print ' ' . $t['in'] . ' ';
            else
            if (ctype_digit(substr($date,0,1)))
                print ' ' . $t['on'] . ' ';
            print ' ' . $date;
        }
            
        // location of marriage
        if ($idlrmar > 1)
        {                   // have location of marriage
            print ' ';      // separate from preceding date
            $marloc             = Location::getLocation($idlrmar);
            print showLocation($marloc);
        }                   // have location of marriage
        $mnotes         = $event['notes'];
        if (strlen($mnotes) == 0)
            print ".\n";
            
        // show citations for this marriage
        if ($ider < 1000000000)
        {                   // real Event
            print showCitations($event);
        }                   // real Event
        else
        {                   // internal Event
            print showCitations(Citation::STYPE_MAR,
                                $family->getIdmr());
        }                   // internal Event

        // show marriage notes
        if (strlen($mnotes) > 0)
        {       // notes defined for this family
            $mnotes     = createPopups($mnotes);
            print str_replace("\n\n", "\n<p>", $mnotes) . "\n";
        }       // notes defined for this family
    }                       // marriage event
    else
    if ($idet == Event::ET_LDS_SEALED)
    {                       // to do
    }                       // to do
    else
    {
        showEvent($t['They'],       // pronoun for family
                  0,                // not relevant
                  $event,           // record with details
                  $eventText[$idet]);// template
    }
}       // function displayEvent

/********************************************************************
 *        OOO  PPPP  EEEEE N   N    CCC   OOO  DDDD  EEEEE          *
 *       O   O P   P E     NN  N   C   C O   O D   D E              *
 *       O   O PPPP  EEEE  N N N   C     O   O D   D EEEE           *
 *       O   O P     E     N  NN   C   C O   O D   D E              *
 *        OOO  P     EEEEE N   N    CCC   OOO  DDDD  EEEEE          *
 ********************************************************************/

// generate unique id values for the <span> enclosing each location
// reference
$locindex               = 1;

// process input parameters
$idir                   = null;
$person                 = null;
$private                = true;
$somePrivate            = false;
$prefix                 = '';
$givenName              = '';
$surname                = '';
$treeName               = '';
// parameter to nominalIndex.php
$nameuri                = '';
$birthDate              = '';
$deathDate              = '';
$lang                   = 'en';
$getParms               = array();

foreach($_GET as $key => $value)
{                   // loop through all parameters
    $value              = trim($value);
    switch(strtolower($key))
    {               // act on specific parameters
        case 'idir':
        case 'id':
        {           // get the Person by identifier
            if (is_int($value) || ctype_digit($value))
            {
                $idir   = $value;
                $getParms['idir'] = $idir;
            }
            else
                $msg    .= "Invalid IDIR=" . htmlspecialchars($value) . ". ";
            break;
        }           // get the Person by identifier

        case 'userref':
        {           // get the Person by user reference
            if (preg_match('/^[a-zA-Z0-9_ ]{1,50}$/', $value))
            {
                $getParms['userref'] = $value;
            }
            else
                $msg    .= "Invalid UserRef='" . 
                            htmlspecialchars($value) . "'. ";
            break;
        }           // get the Person by user reference

        case 'lang':
        {
            $lang       = FtTemplate::validateLang($value);
            break;
        }
    }               // act on specific parameters
}                   // loop through all parameters

// start the template
$template               = new FtTemplate("Person$lang.html");
$template->updateTag('otherStylesheets',
                     array('filename'   => 'Person'));

// internationalization support
$translate              = $template->getTranslate();
if ($translate['dateFormatFull'])
    LegacyDate::setTemplate($translate['dateFormatFull']->innerHTML);
$months                 = $translate['Months'];
$lmonths                = $translate['LMonths'];
$t                      = $translate['tranTab'];
$statusText             = $translate['msStmts'];

// interpret the value of the child to parent relationship in Child
$cpRelType              = $translate['cpRelType'];

// interpret event type IDET as a sentence with substitutions
$eventText              = $template['eventStmt'];

$malePronoun            = $t['He'];
$femalePronoun          = $t['She'];
$otherPronoun           = $t['He/She'];
$maleChildRole          = $t['son'];
$femaleChildRole        = $t['daughter'];
$unknownChildRole       = $t['child'];

// translate the gender of a child to the appropriate noun
$childRole              = array($maleChildRole,         // son
                                $femaleChildRole,       // daughter
                                $unknownChildRole);     // child

// interpret the value of the IDCS field in Child
$intStatus              = array(1           => '',
                                2           => $t['None'],
                                3           => $t['Stillborn'],
                                4           => $t['Twin'],
                                5           => $t['Illegitimate']);

// must have a parameter
if (count($getParms) == 0)
{                   // missing identifier
    $msg                .= $template['missingID']->innerHTML;
    $title              = $template['notFoundTitle']->innerHTML;
    $template['wishtosee']->update(null);
    $template['actionsForm']->update(null);
    $template['blogForm']->update(null);
    $template['footnotesSection']->update(null);
}                   // missing identifier
else
{                   // have an identifier
    // obtain the instance of Person based upon the parameters
    $person             = new Person($getParms);
    $idir               = $person->getIdir();
    $evBirth            = $person->getBirthEvent(false);
    $evDeath            = $person->getDeathEvent(false);
    $bprivlim           = $person->getBPrivLim();
    $dprivlim           = $person->getDPrivLim();

    // check if current user is an owner of the record and therefore
    // permitted to see private information and edit the record
    $isOwner            = $person->isOwner();

    // get information for constructing title and
    // breadcrumbs
    $givenName          = $person->getGivenName();
    if (strlen($givenName) > 2)
        $givenPre       = substr($givenName, 0, 2);
    else
        $givenPre       = $givenName;
    $surname            = $person->getSurname();
    $nameuri            = rawurlencode($surname . ', ' . $givenPre);
    if (strlen($surname) == 0)
        $prefix         = '';
    else
    if (substr($surname,0,2) == 'Mc')
        $prefix         = 'Mc';
    else
        $prefix         = substr($surname,0,1);

    // format dates for title
    if ($person->get('private') == 2 && !$isOwner)
    {
        $title          = $template['invisibleTitle'];
        $template['actionsForm']->update(null);
        $template['blogForm']->update(null);
        $template['footnotesSection']->update(null);
        $surname        = "";
        $birthDate      = $t['Private'];
        $somePrivate    = true;
    }
    else
    {
        $title          = $person->getName($t);
        $bdoff          = strpos($title, '(');
        if ($bdoff === false)
            $birthDate = '';
        else
        {
            $bdoff++;
            $mdashoff = strpos($title, '&', $bdoff);
            $birthDate = substr($title, $bdoff, $mdashoff - $bdoff);
            $ddoff = $mdashoff + 7;
            $cboff = strpos($title, ')', $ddoff);
            $deathDate = substr($title, $ddoff, $cboff - $ddoff);
        }
    }

    // determine if the Person is private
    if (($birthDate != 'Private' && $person->get('private') == 0) ||
        $isOwner)
        $private            = false;
}                   // have an identifier


$template->set('TITLE',         $title);
$template->set('SURNAME',       $surname);
$template->set('PREFIX',        $prefix);
$template->set('NAMEURI',       $nameuri);
$template->set('TREENAME',      $treeName);
$template->set('CONTACTSUBJECT',urlencode($_SERVER['REQUEST_URI']));
$template->set('CONTACTTABLE',  'tblIR');
$template->set('CONTACTKEY',    $idir);

// update tags
if (strlen($treeName) > 0)
    $template->updateTag('inTree', array('treeName' => $treeName));
else
    $template->updateTag('inTree', null);
ob_start();

if (!is_null($person))
{       // Person found

    if ($private)
    {
?>
<p class="label">Information on this Person is Private</p>
<?php
    }
    else
    {       // display public data
        if ($person['gender'] == Person::MALE)
            $pronoun    = $malePronoun;
        else
        if ($person['gender'] == Person::FEMALE)
            $pronoun    = $femalePronoun;
        else
            $pronoun    = $otherPronoun;

        // if debugging, dump out details of record
        $person->dump("Person.php: " . __LINE__);

        // Print the name of the Person before the first event
?>
<p><?php print $person->getName(); ?>
<?php
        // print citations for the name
        print showCitations(Citation::STYPE_NAME,     // traditional 
                            $person->getIdir());
        $priName        = $person->getPriName();// new citations
        $idnx           = $priName['idnx'];
        print showCitations(Citation::STYPE_ALTNAME,
                            $idnx);
        print ' ';      // separate name from following

        // show information about the parents of this Person
        // This is always displayed
        // so the user can trace up the tree to non-private Persons
        showParents($person);

        // display any alternate names
        $altNames = $person->getNames(1);
        foreach($altNames as $idnx => $altName)
        {
            print $pronoun . ' ' . $t['was also known as'] . ' ' .
                $altName->getName() . '. ';
            print showCitations(Citation::STYPE_ALTNAME,
                                $idnx);
            $note = $altName->get('akanote');
            if (strlen($note) > 0)
                print $note . ' ';
        }   // loop through alternate names

        // show information about events
        showEvents($person);
?>
    </p>
<?php
        // display the user reference field if present
        try
        {       // userref field present in database
            $userref = $person->get('userref');
            if (strlen($userref) > 0)
            {           // user reference
?>
    <p>User Reference: <?php print $userref; ?>
    </p>
<?php
            }           // userref field present in database
            else
                $userref    = '';
        }
        catch(Exception $e)
        {
            $userref        = '';
        }               // getField failed

        // display any general notes
        $notes              = $person->get('notes');
        if (strlen($notes) > 0 && !$somePrivate)
        {               // notes defined
            $notes          = createPopups($notes);
?>
    <p class="notes"><b>Notes:</b>
<?php
            if (strpos($notes, '<p>') === false)
                print str_replace("\n\n", "\n<p>", $notes);
            else
                print str_replace("<p>", "<p class=\"notes\">", $notes);
            print showCitations(Citation::STYPE_NOTESGENERAL,
                                $idir);
?>
    </p>
<?php
        }       // notes defined

        // show any images/video files for the main Person
        if (!$private)
            $person->displayPictures(Picture::IDTYPEPerson);

        // show information about families in which this Person
        // is a spouse or partner
        if ($debug)
            $warn   .= "<p>\$person-&gt;getFamilies()</p>\n";
        $families                   = $person->getFamilies();

        if (count($families) > 0)
        {       // include families section of page
            if ($person['gender'] == Person::MALE)
            {                   // Male
                $pronoun            = $malePronoun;
                $spousePronoun      = $femalePronoun;
                $spouseChildRole    = $femaleChildRole;
            }                   // Male
            else
            if ($person['gender'] == Person::FEMALE)
            {                   // Female
                $pronoun            = $femalePronoun;
                $spousePronoun      = $malePronoun;
                $spouseChildRole    = $maleChildRole;
            }                   // Female
            else
            {                   // Unknown
                $pronoun            = $otherPronoun;
                $spousePronoun      = $otherPronoun;
                $spouseChildRole    = $unknownChildRole;
            }                   // Female
?>
    <p>
<?php
            $num = 1;           // counter for children

            foreach($families as $idmr => $family)
            {                   // loop through families
                if ($person['gender'] == Person::FEMALE)
                {               // female
                    $spsName        = $family->getHusbPriName();
                    $spsid          = $family->get('idirhusb');
                    $spsclass       = 'male';
                }               // female
                else
                {               // male
                    $spsName        = $family->getWifePriName();
                    $spsid          = $family->get('idirwife');
                    $spsclass       = 'female';
                }               // male

                // information about spouse
                if ($spsid > 0)
                {
                    $spouse         = Person::getPerson($spsid);
                    if (!($spouse->isExisting()))
                    {           // fixup
                        $spouse['givenname']    = $spsName['givenname'];
                        $spouse['surname']      = $spsName['surname'];
                        $spouse->save();
                        if ($debug)
                            $warn   .= $spouse->dump('fixup Person.php:' . __LINE__);
                    }           // fixup
                    $individTable[$spsid] = $spouse;
                }
                else
                {
                    $spouse         = null;
                    $spsid          = 0;
                }
                $mdateo             = new LegacyDate($family->get('mard'));
                $mdate              = $mdateo->toString($dprivlim,
                                                        true,
                                                        $t);

                $events             = $family->getEvents();
?>
    <p style="clear: both;">
<?php
                $verb               = $family->getStatusVerb();
                if ($events->count() == 0)
                {
                    print $pronoun . ' ' . $t[$verb];
                    // only display a sentence about the marriage
                    // if there is a spouse defined
                    if ($spsid > 0)
                    {       // have a spouse
?>
        <a href="<?php print $directory; ?>Person.php?idir=<?php print $spsid; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $spsclass; ?>"><?php print $spsName->getName(); ?></a>.
<?php
                    }       // have a spouse
                    else
                    {       // do not have a spouse
                        print " " . $t['an unknown person'] . '.';
                    }       // do not have a spouse
                }

                if ($spsid > 0)
                {       // have a spouse
                    if ($family->get('notmarried') > 0)
                    {       // never married indicator
                        print $template['neverMarried']->innerHTML;
                    }       // never married indicator

                    // display the event table entries for this family
                    foreach($events as $ider => $event)
                    {       // loop through all event records
                        displayEvent($ider,
                                     $event,
                                     $family,
                                     $pronoun,
                                     $spsid,
                                     $spsName,
                                     $spsclass);
                    }       // loop through all event records

                    //***************************************************
                    //  Marriage Ended Event                            *
                    //                                                  *
                    //  The Legacy database contains a marriage end     *
                    //  date which is presumably intended to record     *
                    //  the unofficial termination of the relationship  *
                    //  where there is no formal event, such as Divorce *
                    //  (ET_DIVORCE), or Annulment (ET_ANNULMENT).      *
                    //  Note that tblER does not define a formal        *
                    //  separation event although that could be a user  *
                    //  implemented ET_MARRIAGE_FACT with description   *
                    //  "Separation".                                   *
                    //  The Legacy database also does not define        *
                    //  a citation type in tblSX to be able to document *
                    //  the information source for knowledge of the end *
                    //  of the marriage.                                *
                    //  To permit this event to be handled in a manner  *
                    //  consistent with all other events, a new         *
                    //  citation type is defined.                       *
                    //                                                  *
                    //****************************************************
                    if (strlen($family->get('marendd')) > 0)
                    {       // marriage ended date present
                        $date   = new LegacyDate($family->get('marendd'));
                        print $t['The marriage ended'];
                        print $date->toString(9999, true, $t) . '.';
                        // show citations for this marriage
                        print showCitations(Citation::STYPE_MAREND,
                                            $family->getIdmr());
                    }           // marriage ended date present

                    // display the final marriage status
                    $idms           = $family->get('idms');
                    $marStatus      = $statusText[$idms];
                    print "\n$marStatus\n";
?>
    </p>
<?php
                    // show any images/video files for the family
                    if (!$private)
                        $family->displayPictures(Picture::IDTYPEMar);

                    // Print the name of the spouse before the first event
?>
<p><?php print $spouse->getName(); ?>
<?php
                    // print citations for the name
                    print showCitations(Citation::STYPE_NAME,
                                        $spouse->getIdir());
                    print ' ';      // separate name from following

                    // show information about the parents of the spouse
                    showParents($spouse);

                    // display any alternate names
                    $altNames = $spouse->getNames(1);
                    foreach($altNames as $idnx => $altName)
                    {   // loop through alternate names
                        print $spousePronoun . ' ' .
                                $t['was also known as'] . ' ' .
                                $altName->getName() . '.';
                        print showCitations(Citation::STYPE_ALTNAME,
                                            $idnx);
                        $note = $altName->get('akanote');
                        if (strlen($note) > 0)
                            print $note;
                    }   // loop through alternate names

                    // show events in the life of the spouse
                    showEvents($spouse);
?>
    </p>
<?php
                    // display the user reference field if present
                    try
                    {       // userref field present in database
                        $userref = $spouse->get('userref');
                        if (strlen($userref) > 0)
                        {       // user reference
?>
    <p>User Reference: <?php print $userref; ?>
    </p>
<?php
                        }       // userref field present in database
                        else
                            $userref = '';
                    }
                    catch(Exception $e)
                    {
                        $userref = '';
                    }           // getField failed

                    // display any general notes for the spouse
                    $notes = $spouse->get('notes');
                    if (strlen($notes) > 0 && !$somePrivate)
                    {       // notes defined
?>
    <p class="notes"><b>Notes:</b>
<?php
                        print str_replace("\n\n", "\n<p>", $notes);
                        print showCitations(Citation::STYPE_NOTESGENERAL,
                                            $spouse->getIdir());
?>
    </p>
<?php
                    }       // notes defined
                    // show any images/video files for the spouse
                    if (!$private)
                        $spouse->displayPictures(Picture::IDTYPEPerson);    
                }       // have a spouse
                else
                {       // end sentence
?>
.
<?php
                }       // end sentence

            // display information about children
            $children = $family->getChildren();
            if (count($children) > 0)
            {   // found at least one child record
?>
    <p class="label">
<?php
                print $t['Children of'] . ' ' . $person->getName();
                if ($spsid)
                {
                    print " " . $t['and'] . " " . $spouse->getName();
                }
                print ":";
?>
    </p>
<?php
try {
        $child                      = $children->rewind();
        while($children->valid())
        {       // loop through all child records
            $idcr                   = $children->key();

            // display information about child
            $cid                    = $child->get('idir');
            $child                  = Person::getPerson($cid);
            $individTable[$cid]     = $child;
            $cName                  = $child->getName($t);

            // set the class to color hyperlinks
            if ($child['gender'] == Person::MALE)
                $cgender            = 'male';
            else
            if ($child['gender'] == Person::FEMALE)
                $cgender            = 'female';
            else
                $cgender            = 'unknown';
            $children->next();
            if ($children->valid())
            {
                $child              = $children->current();
                $last               = '';
            }
            else
                $last               = ' last';
?>
          <div class="row<?php print $last; ?>">
            <div class="column1">
                <?php print $num; ?>
            </div>
            <a href="<?php print $directory; ?>Person.php?idir=<?php print $cid; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $cgender; ?>">
                <?php print $cName; ?>
            </a>
            <div style="clear: both;"></div>
          </div> <!-- class="row" -->
<?php
            $num++;
        }   // loop through all child records
} catch(Exception $e)
{
    print "<p class=\"message\">failure: " . $e->getMessage();
}
                }   // found at least one child record
            }       // loop through families
        }       // at least one marriage

        // give user options if some information is hidden
        if ($somePrivate)
        {
            if ($userid == '')
            {       // not logged on
                $template->updateTag('contactOwners', null);
            }       // not logged on
            else
            {       // logged on but not an owner
                $template->updateTag('notloggedon', null);
            }       // logged on but not an owner
        }       // some data is private
        else
            $template->updateTag('wishtosee', null);

        // for already logged on users
        if (strlen($userid) == 0)
        {
            $template->updateTag('edit', null);
            $template->updateTag('reqgrant', null);
        }
        else
        if ($isOwner)
        {
            $template->updateTag('reqgrant', null);
        }
        else
        {
            $template->updateTag('edit', null);
            if ($user['auth'] == 'visitor')
                $template->updateTag('reqgrant', null);
        }

        $birthPlace         = '';
        if ($evBirth)
            $birthPlace     = $evBirth->getLocation()->getName();
        $deathPlace         = '';
        if ($evDeath)
            $deathPlace     = $evDeath->getLocation()->getName();
        $fatherGivenName    = '';
        $fatherSurname      = '';
        $motherGivenName    = '';
        $motherSurname      = '';
        $parents            = $person->getPreferredParents();
        if ($parents)
        {           // have preferred parents
            $father         = $parents->getHusband();
            if ($father)
            {           // have father
                $fatherGivenName    = $father->getGivenName();
                $fatherSurname      = $father->getSurname();
            }           // have father
            $mother         = $parents->getWife();
            if ($mother)
            {           // have father
                $motherGivenName    = $mother->getGivenName();
                $motherSurname      = $mother->getSurname();
            }           // have father
        }           // have preferred parents
        $template->set('IDIR',              $idir);
        $template->set('GIVENNAME',         $givenName);
        $template->set('SURNAME',           $surname);
        $template->set('TREENAME',          $treeName);
        $template->set('BIRTHDATE',         $birthDate);
        $template->set('BIRTHPLACE',        $birthPlace);
        $template->set('DEATHDATE',         $deathDate);
        $template->set('DEATHPLACE',        $deathPlace);
        $template->set('FATHERGIVENNAME',   $fatherGivenName);
        $template->set('FATHERSURNAME',     $fatherSurname);
        $template->set('MOTHERGIVENNAME',   $motherGivenName);
        $template->set('MOTHERSURNAME',     $motherSurname);

        // show any blog postings
        $blogParms = array('keyvalue' => $idir,
                            'table'     => 'tblIR');
        $bloglist = new RecordSet('Blogs', $blogParms);

        // display existing blog entries
        foreach($bloglist as $blid => $blog)
        {       // loop through all blog entries
            $username       = $blog->getUser();
            if (strlen($username) == 0)
                $blog->set('username', "**guest**");
            $text = $blog->getText();
            $blog->set('text', str_replace("\n", "</p>\n<p>", $text));
            if ($username == $userid)
                $blog->set('showbuttons', $username);
            else
                $blog->set('showbuttons', '');
        }       // loop through all blog entries

        $template->updateTag('blog$blid', $bloglist);
        if (strlen($userid) > 0)
            $template->updateTag('blogEmailRow', null);

        if ($userid == '' || $user['auth'] == 'visitor')
        {
            $template['message']->update(null);
            $template['blogEmailRow']->update(null);
            $template['blogPostRow']->update(null);
        }

        // show accumulated citations
        showCitationTable();
    }           // display public data
}               // Person found

// embed all of the output from the script    
$template->set('BODY', ob_get_clean());

// create popup balloons for each of the people referenced on this page
$tag                            = $template['Individ$idir'];
if ($tag)
{
    $templateText               = $tag->outerHTML();
    $data                       = '';
    foreach($individTable as $idir => $individ)
    {       // loop through all referenced Persons
        $name                   = $individ->getName();
        $evBirth                = $individ->getBirthEvent();
        if ($evBirth)
        {
            $birthd             = $evBirth->getDate();
            $birthloc           = $evBirth->getLocation()->getName();
            if ($birthloc == '')
            {
                $birthloc       = array();
                if ($birthd == '')
                    $birthloc   = array();
            }
        }
        else
        {
            $birthd             = array();
            $birthloc           = array();
        }
        $evDeath                = $individ->getDeathEvent();
        if ($evDeath)
        {
            $deathd             = $evDeath->getDate();
            $deathloc           = $evDeath->getLocation()->getName();
            if ($deathloc == '')
            {
                $deathloc       = array();
                if ($deathd == '')
                    $deathloc   = array();
            }
        }
        else
        {
            $deathd     = array();
            $deathloc   = array();
        }
        $families   = $individ->getFamilies();
        $parents    = $individ->getParents();
        $entry      = array('name'          => $name,
                            'idir'          => $individ->get('idir'),
                            'birthd'        => $birthd,
                            'birthloc'      => $birthloc,
                            'deathd'        => $deathd,
                            'deathloc'      => $deathloc,
                            'description'   => '',
                            'families'      => $families,
                            'parents'       => $parents);
        $itemplate      = new Template($templateText);
        $itemplate['Individ$idir']->update($entry);
        $data           .= $itemplate->compile();
    }       // loop through all referenced Persons
    $tag->update($data);
}
else
    error_log("Person.php: " . __LINE__ . "Could not find id='Individ$idir' template 'Person$lang.html'");

// create popup balloons for each of the sources referenced on this page
$template->updateTag('Source$idsr',
                     $sourceTable);

// create popup balloons for each of the locations referenced on this page
$template->updateTag('showLocDiv$idlr',
                     $locationTable);
if (!canUser('edit'))
        $template->updateTag('editLoc$idlr', null);

// create popup balloons for each of the temples referenced on this page
$template->updateTag('showTplDiv$idtr',
                     $templeTable);

// create popup balloons for each of the addresss referenced on this page
$template->updateTag('showAdrDiv$idar',
                        $addressTable);

ob_start();
include 'DeathCauses.php';
$template->set('DEATHCAUSES', ob_get_clean());
if (strlen($userid) > 0)
{
    $user                   = new User(array('username' => $userid));
    $template->set('EMAIL', $user->get('email'));
}
else
    $template->set('EMAIL', '');

$template->display();
