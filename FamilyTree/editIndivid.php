<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editIndivid.php                                                     *
 *                                                                      *
 *  Display a web page to support editing details of an particular      *
 *  record from the Legacy table of individuals.                        *
 *                                                                      *
 *  URI Parameters:                                                     *
 *      idir            unique numeric key of the instance of           *
 *                      LegacyIndiv to be displayed.  If this is        *
 *                      omitted or zero then a new instance of          *
 *                      LegacyIndiv is created.                         *
 *                      For backwards compatibility the script also     *
 *                      accepts 'id'.                                   *
 *      treeName        the section of the database to create the       *
 *                      individual in if idir=0                         *
 *                                                                      *
 *      The following parameters may be specified on an invocation of   *
 *      this page using Javascript window.open to request the page to   *
 *      update the values of specific fields in the invoking page when  *
 *      the user submits an update.                                     *
 *                                                                      *
 *      setidir         field name in which to place the unique numeric *
 *                      key of the new instance of LegacyIndiv          *
 *      Surname         field in which to place the surname of          *
 *                      the new instance of LegacyIndiv                 *
 *      GivenName       field in which to place the given names         *
 *                      of the new instance of LegacyIndiv              *
 *      Prefix          field in which to place the name prefix         *
 *                      of the new instance of LegacyIndiv              *
 *      NameNote        field in which to place the name note           *
 *                      of the new instance of LegacyIndiv              *
 *      Gender          field in which to place the gender              *
 *                      of the new instance of LegacyIndiv              *
 *      BirthDate       field in which to place the birth date          *
 *                      of the new instance of LegacyIndiv              *
 *      BirthLocation   field in which to place the birth location      *
 *                      of the new instance of LegacyIndiv              *
 *      ChrisDate       field in which to place the christening date    *
 *                      of the new instance of LegacyIndiv              *
 *      ChrisLocation   field in which to place the christening location*
 *                      of the new instance of LegacyIndiv              *
 *      DeathDate       field in which to place the death date          *
 *                      of the new instance of LegacyIndiv              *
 *      DeathLocation   field in which to place the death location      *
 *                      of the new instance of LegacyIndiv              *
 *      BuriedDate      field in which to place the burial date         *
 *                      of the new instance of LegacyIndiv              *
 *      BuriedLocation  field in which to place the burial location     *
 *                      of the new instance of LegacyIndiv              *
 *      UserRef          field in which to place the user reference     *
 *                      value of the new instance of LegacyIndiv        *
 *      AncestralRef  field in which to place the ancestral             *
 *                      reference value of the new instance of          *
 *                      LegacyIndiv                                     *
 *      DeathCause      field in which to place the death cause         *
 *                      of the new instance of LegacyIndiv              *
 *      ... or, in general, any field name in this page.                *
 *                                                                      *
 *      Furthermore a parameter with a name starting with 'init' can be *
 *      used to initialize the value of a field matching the remainder  *
 *      of the parameter name if a new individual is being created.     *
 *      Note that the field name portion of these parameters is         *
 *      case-insensitive.                                               *
 *      In particular:                                                  *
 *                                                                      *
 *      initSurname      set initial value for the surname              *
 *      initGivenName  set initial value for the given names            *
 *      initPrefix      set initial value for the name prefix           *
 *      initNameNote  set initial value for the name note               *
 *      initGender      set initial value for the gender                *
 *      initBirthDate  set initial value for the birth date             *
 *      initBirthLocation set initial value for the birth location      *
 *      initChrisDate  set initial value for the christening date       *
 *      initChrisLocation set initial value for the christening location*
 *      initDeathDate  set initial value for the death date             *
 *      initDeathLocation set initial value for the death location      *
 *      initBuriedDate  set initial value for the burial date           *
 *      initBuriedLocation set initial value for the burial location    *
 *      initUserRef      set initial value for the user                 *
 *      initAncestralRef set initial value for the ancestral            *
 *      initDeathCause  set initial value for the death cause           *
 *                                                                      *
 *  When this is invoked to create a child the following parameter must *
 *  be passed:                                                          *
 *                                                                      *
 *      parentsIdmr      IDMR of the record for the parent's relationship*
 *                                                                      *
 *  When this is invoked to edit an existing child the following        *
 *  parameter must be passed:                                           *
 *                                                                      *
 *      idcr          IDCR of the record in tblCR that connects the     *
 *                      child to a family                               *
 *                                                                      *
 *  When this is invoked to update a parent in the family the following *
 *  parameter is passed to identify the role in the family:             *
 *                                                                      *
 *      rowid          'Husb', 'Wife', 'Father', 'Mother'               *
 *                                                                      *
 *  The following parameters may also be passed to supply information   *
 *  that may not yet have been written to the database because          *
 *  the family record is in the process of being created:               *
 *                                                                      *
 *      initSurname     initial surname for the child                   *
 *      fathGivenName   father's given name                             *
 *      fathSurname     father's surname                                *
 *      mothGivenName   mother's given name                             *
 *      mothSurname     mother's surname                                *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/11      Correct error in mailto: subject line, and add  *
 *                      birth date and death date into title.           *
 *      2010/08/11      encode field values with htmlspecialchars       *
 *      2010/08/12      if invoked from another web page, apply         *
 *                      changes to that web page as a side effect of    *
 *                      submitting the update.  Also support            *
 *                      initializing values in the form.                *
 *      2010/08/28      Add Edit Details on name to permit citations    *
 *                      and to move name note off main page.            *
 *                      Add Edit Details on death cause.                *
 *      2010/09/20      remove onsubmit= parameter from form            *
 *                      it is supplied by editIndivid.js::loadEdit      *
 *      2010/09/27      Support standard idir= parameter                *
 *      2010/10/01      Add hyperlinks for IE < 8                       *
 *      2010/10/10      Evaluate locations at top of page to handle     *
 *                      error message emitted by LegacyLocation         *
 *                      constructor                                     *
 *      2010/10/21      use RecOwners class to validate access          *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/10/30      move $browser object to common.inc              *
 *      2010/10/31      do not expand page if user is not owner of      *
 *                      record                                          *
 *      2010/11/10      add help link                                   *
 *      2010/11/14      move name prefix and title to name event        *
 *      2010/11/27      add support for medical and research notes      *
 *                      improve separation of HTML and PHP              *
 *                      use editEvent.php in place of obsolete          *
 *                      editEventIndiv.php                              *
 *      2010/11/29      correct initialization of $given                *
 *                      improve title for case of adding a child        *
 *      2010/12/09      add name on submit button and initially disable *
 *                      add balloon help for all buttons and input fields*
 *      2010/12/12      replace LegacyDate::dateToString with           *
 *                      LegacyDate::toString                            *
 *                      escape HTML special chars in title              *
 *      2010/12/18      The link to the nominal index in the header and *
 *                      trailer breadcrumbs points at current name      *
 *      2010/12/20      handle exception thrown by new LegacyIndiv      *
 *                      handle exception thrown by new LegacyFamily     *
 *                      handle exception thrown by new LegacyLocation   *
 *                      add button to delete individual if a candidate  *
 *      2010/12/24      pass parentsIdmr to updateIndivid.php           *
 *                      reduce padding between cells to compress form   *
 *      2010/12/26      add support for modifying field IDMRPref in     *
 *                      response to request from editMarriages.php      *
 *      2010/12/29      if IDMRPref not set, default to first marriage  *
 *                      ensure value of 'idar' is numeric               *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/02/23      use editEvent.php to edit general notes         *
 *      2011/03/02      change name of submit button to 'Submit'        *
 *                      visual support for Alt-U                        *
 *      2011/03/06      Combine label text into edit button and         *
 *                      add Alt-... support for each button             *
 *      2011/04/22      fix IE7 support                                 *
 *      2011/05/25      add button for editting pictures                *
 *      2011/06/24      correct handling of children                    *
 *      2011/08/22      if no marriages set text of Edit Marriages      *
 *                      button to Add Spouse or Childa and change       *
 *                      standard text to Edit Families with Alt-F       *
 *      2011/08/24      if no parents set text of Edit Parents button   *
 *                      to Add Parents                                  *
 *      2011/10/23      add help for additional fields and buttons      *
 *      2012/01/07      add ability to explicitly supply father's and   *
 *                      mother's name when adding a child               *
 *      2012/01/13      change class names                              *
 *      2012/01/23      display loading indicator while waiting for     *
 *                      response to changes in a location field         *
 *      2012/02/25      id= rather than name= used to identify buttons  *
 *                      so they will not be passed to the action        *
 *                      script by IE.                                   *
 *                      help text added for some hidden fields          *
 *                      add support for LDS events in main record       *
 *                      add support for list of tblER events            *
 *                      add event button moved to after list of events  *
 *                      order events by date button added               *
 *                      event type encoded in id value of buttons       *
 *                      IDER encoded in id value of buttons and name    *
 *                      value of input fields for events in tblER       *
 *                      cittype encoded in id value of buttons and      *
 *                      name value                                      *
 *                      of input fields for events in main record       *
 *                      support all documented init fields              *
 *      2012/05/06      explicitly set class for input text fields      *
 *      2012/05/12      remove display of Soundex code                  *
 *      2012/05/31      defer adding child to family until submit       *
 *      2012/08/01      support user modification of events recorded in *
 *                      Event instances on the main dialog              *
 *      2012/08/12      add ability to edit sealed to parents event     *
 *      2012/08/27      correct handling of location selection list     *
 *                      on individual events with description           *
 *      2012/09/19      use idcr parameter for editing existing child   *
 *      2012/09/24      enforce maximum lengths for text fields to      *
 *                      match database definition                       *
 *      2012/09/28      do not set or display ID and IDIR for new record*
 *                      the database record is not created until the    *
 *                      update is submitted                             *
 *      2012/10/14      expand death cause to 255 characters            *
 *      2012/11/09      add customizable events using javascript rather *
 *                      than redisplaying the entire page               *
 *      2012/11/12      no longer need to disable submit button         *
 *      2013/01/17      make gender readonly if pre-selected            *
 *      2013/02/13      cannot issue grant for a new individual         *
 *      2013/02/15      add help bubble for Delete button               *
 *      2013/03/10      add checkbox for Private flag                   *
 *      2013/03/12      move rarely used fields to the bottom of        *
 *                      the form                                        *
 *                      add support for ancestor and                    *
 *                      descendant interest                             *
 *                      color code gender                               *
 *                      standardize appearance and implementation       *
 *                      of selection lists                              *
 *      2013/03/14      LegacyLocation constructor no longer does save  *
 *      2013/04/20      add illegitimate relationship of child          *
 *      2013/05/17      shrink the form vertically by using             *
 *                      <button class="button">                         *
 *      2013/05/26      use dialog in place of alert for new            *
 *                      location name                                   *
 *      2013/05/28      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/05/30      add help text for SealingDate and               *
 *                      SealingTemple                                   *
 *      2013/06/01      change legacyIndex.html to legacyIndex.php      *
 *                      include all owners in author email              *
 *      2013/06/22      move hidden IDAR field                          *
 *      2013/08/14      include title and suffix in title of page       *
 *      2013/09/13      do not invoke any methods of individual if      *
 *                      errors                                          *
 *                      avoid doing field references more than once     *
 *      2013/10/15      do not display page header and footer if        *
 *                      invoked to add or edit a member of a family.    *
 *                      process init overrides even for existing        *
 *                      individual                                      *
 *      2013/10/19      correct name parameter to LegacyIndex.php       *
 *      2013/10/25      incorrect field name used when setting initial  *
 *                      values for event dates and temples              *
 *      2013/11/23      handle lack of database server connection       *
 *                      gracefully                                      *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/11      use CSS to layout form                          *
 *      2014/02/17      identify <label> of individual event row        *
 *                      to permit editEvent dialog to update it         *
 *      2014/02/24      use dialog to choose from range of locations    *
 *                      instead of inserting <select> into the form     *
 *                      location support moved to locationCommon.js     *
 *                      add for= attribute on all <label> tags          *
 *      2014/03/06      label class name changed to column1             *
 *      2014/03/10      default to not displaying cause of death in     *
 *                      this dialogue, now that it can be editted in    *
 *                      the death event detail dialogue                 *
 *      2014/03/14      use Event::getEvents                            *
 *                      remove references to deprecated getNumParents   *
 *                      and getNumMarriades                             *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/07/06      move textual interpretation of IDET here from   *
 *                      Event class to support I18N                     *
 *      2014/06/15      support for popupAlert moved to common code     *
 *      2014/09/20      several validation messages reduced to warnings *
 *      2014/09/26      pass debug flag to update script                *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/10/01      add delete confirmation dialog                  *
 *      2014/10/24      handle preferred events that are implemented    *
 *                      by entries in tblER rather than fields in tblIR *
 *      2014/11/02      fixup for multiple preferred birth events       *
 *      2014/11/08      initBirthDate, etc don't work because they      *
 *                      init fields in old location                     *
 *      2014/11/15      incorrect handling of invalid IDIR value        *
 *      2014/11/18      init parms for events do not work if event in   *
 *                      tblER                                           *
 *      2014/11/29      do not reinitialize global variables set by     *
 *                      common.inc                                      *
 *      2014/11/29      print $warn, which may contain debug trace      *
 *      2014/12/04      add fields to each event row to hold:           *
 *                       o checkbox for preferred status                *
 *                       o the IDER value (which may be zero)           *
 *                       o the IDET value                               *
 *                       o the citation type                            *
 *                       o the order                                    *
 *                       o the sorted form of the event date            *
 *                       o the event changed indicator                  *
 *                      Use the same element names for all fields in    *
 *                      each event row that are not required to be      *
 *                      special to assist the browser to perform        *
 *                      value autoexpansion and popup help, and to      * 
 *                      permit the Javascript code to assign the        *
 *                      abbreviation expansions                         *
 *                      change labels on tblER events to match labels   *
 *                      on old style fixed events                       *
 *                      all events are moved to tblER on save           *
 *      2014/12/26      added row was not the same layout as existing   *
 *                      rows                                            *
 *      2015/01/03      updating event added row when it shouldn't      *
 *      2015/01/09      wrong event order set for birth event when      *
 *                      debug not specified                             *
 *      2015/01/10      use CSS to style width of button columns        *
 *                      some form elements did not have id= values      *
 *      2015/01/15      move table of event texts to HTML to make       *
 *                      available to Javascript.  Get English version   *
 *                      of event texts from class Event                 *
 *      2015/01/18      display grant dialog in right half of window    *
 *                      add drop down menu for searching other tables   *
 *                      and Ancestry.ca                                 *
 *      2015/01/27      move Grant button up to the line with all       *
 *                      other global buttons                            *
 *      2015/02/06      highlight place name in previously unknown      *
 *                      location popup                                  *
 *      2015/03/06      when invoked to add a new child to a family     *
 *                      create the required instance of LegacyChild     *
 *                      so the user can set the relationship to parents *
 *                      fields                                          *
 *      2015/03/16      assign IDIR value for new individual            *
 *                      initialize IDTR values in new event records     *
 *      2015/03/25      do not flag parentsIdmr=0 as an error           *
 *                      top of hierarchy is genealogy.php               *
 *      2015/04/27      initXxxxxDate changes were not being done       *
 *                      because event objects were replaced and         *
 *                      changed flag was not set                        *
 *      2015/05/27      support deleting address                        *
 *      2015/06/23      permit longer event description.  This is       *
 *                      mostly to tolerate HTML in the value.           *
 *      2015/06/29      ensure new init values for events visible       *
 *                      when invoked as child of a family dialog        *
 *                      If the pre-defined events are already in        *
 *                      tblER $person->getXxxxEvent() creates a         *
 *                      different instance than $person->getEvents      *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/12      support treeName database subdivision           *
 *                      permit Surname field to contain lower case      *
 *                      components such as 'de' or 'van'.               *
 *      2016/02/06      use showTrace                                   *
 *      2017/01/03      ensure buried event after death event           *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *                      use preferred parameters for new LegacyFamily   *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/08      class LegacyChild renamed to class Child        *
 *      2017/08/23      make IDER the first field in event description  *
 *      2017/08/29      ensure special row names for preferred events   *
 *                      so the rows are not deleted when the associated *
 *                      event is deleted, just the contents             *
 *      2017/09/02      class LegacyTemple renamed to class Temple      *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/23      use selection list for temples                  *
 *      2017/09/24      highlight dates flagged by class LegacyDate     *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/18      use RecordSet instead of Temple::getTemples     *
 *      2018/02/03      allow more flexibility in value of initGender   *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/11/19      change Help.html to Helpen.html                 *
 *      2018/12/18      ensure birth event displayed first              *
 *      2019/07/03      do not perform any action on database if        *
 *                      not signed in                                   *
 *                      ensure IDIR always set                          *
 *      2019/08/11      Child methods getStatus, getCPRelDad, and       *
 *                      getCPRelMom obsoleted                           *
 *      2019/11/15      hide buttons for editing parents and families   *
 *                      when adding a spouse or parent                  *
 *                      add more information when adding a new Person   *
 *                      based upon initialization parameters            *
 *      2020/01/09      Event::getDesc is renamed to getNotes           *
 *      2020/07/19      only show death case if requested               *
 *      2021/01/26      template controls which events are always       *
 *                      displayed.                                      *
 *      2022/03/25      remove unnecessary and premature creation of    *
 *                      Surname instance                                *
 *      2022/04/06      add support for adding and editing alternate    *
 *                      names                                           *
 *                      on creating new Person use Nicknames table      *
 *                      to get default gender                           *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function isSelected                                                 *
 *                                                                      *
 *  This method emits the HTML selected attribute for an <option> tag   *
 *  within a <select> if the value of the variable matches the supplied *
 *  value.                                                              *
 *                                                                      *
 *  Input:                                                              *
 *      $var  variable containing value of a field from the record      *
 *      $value  if the variable matches this value then emit a          *
 *              a 'selected' attribute.                                 *
 ************************************************************************/
function isSelected($var, $value)
{
    if ($var == $value) print 'selected="selected"'; 
}       // isSelected

/************************************************************************
 *  function isChecked                                                  *
 *                                                                      *
 *  This method emits the HTML checkedd attribute for an                *
 *  <input type="checkbox"> tag if the value of the variable            *
 *  is not zero.                                                        *
 *                                                                      *
 *  Input:                                                              *
 *      $var  variable containing value of a field from the record      *
 ************************************************************************/
function isChecked($var)
{
    if ($var != 0) print 'checked="checked"'; 
}       // isChecked

// interpret the database gender field as a CSS class name
$genderClasses              = array('male','female','unknown');

// process parameters looking for identifier of individual
$idir                       = null;     // IDIR of instance of Person
$idirset                    = false;    // idir provided and non-zero
$idirtext                   = null;
$person                     = null;     // instance of class Person

$parentsIdmr                = 0;        // IDMR of Family to add new child
$idmrtext                   = null;
$family                     = null;     // instance of Family
$idcr                       = 0;        // IDCR of existing child of family
$idcrtext                   = null;
$childr                     = null;     // instance of Child
$rowid                      = '';       // id of row of family form
$genderFixed                = '';       // pre-selected gender
$fathGivenName              = '';
$fathSurname                = '';
$fatherName                 = '';
$mothGivenName              = '';
$mothSurname                = '';
$motherName                 = '';
$given                      = '';
$surname                    = '';
$treeName                   = '';
$showHdrFtr                 = true;
$lang                       = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                          "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n";
        $value                      = trim($value); 
        switch(strtolower($key))
        {
            case 'id':
            case 'idir':
            {       // identifier of individual
                if (ctype_digit($value))
                {   // valid number
                    $idir           = intval($value);
                    $idirset        = $idir > 0;
                }   // valid number
                else
                {   // invalid
                    $idirtext       = htmlspecialchars($value);
                }   // invalid
                break;
            }       // identifier of individual
    
            case 'idcr':
            {       // identifier of child relationship record
                if (ctype_digit($value))
                {   // valid number
                    if ($value > 0)
                    {
                        $idcr       = intval($value);
                        $showHdrFtr = false;
                    }
                }   // valid number
                else
                    $idcrtext       = htmlspecialchars($value);
                break;
            }       // identifier of child relationship record
    
            case 'parentsidmr':
            case 'idmr':
            {       // identifier of parents family
                if (ctype_digit($value))
                {
                    if ($value > 0)
                    {
                        $parentsIdmr= intval($value);
                        $showHdrFtr = false;
                    }
                }
                else
                    $idmrtext       = htmlspecialchars($value);
                break;
            }       // identifier of parent's family
    
            case 'rowid':
            {       // role of parent in family
                $rowid              = htmlspecialchars(strtolower($value));
                $showHdrFtr         = false;
                break;
            }       // role of parent in family
    
            case 'fathgivenname':
            {       // explicit father's given name
                $fathGivenName      = htmlspecialchars(strtolower($value));
                break;
            }       // explicit father's given name
    
            case 'fathsurname':
            {       // explicit father's surname
                $fathSurname        = htmlspecialchars(strtolower($value));
                break;
            }       // explicit father's given name
    
            case 'mothgivenname':
            {       // explicit mother's given name
                $mothGivenName      = htmlspecialchars(strtolower($value));
                break;
            }       // explicit mother's given name
    
            case 'mothsurname':
            {       // explicit mother's surname
                $mothSurname        = htmlspecialchars(strtolower($value));
                break;
            }       // explicit mother's given name
    
            case 'treename':
            {       // tree to create individual in
                $treeName           = htmlspecialchars(strtolower($value));
                break;
            }       // tree to create individual in

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
            }
        }   // switch on parameter name
    }       // loop through all parameters

    if ($debug)
        $warn                       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

// override default mother's and father's names if required
if (strlen($fathSurname) > 0 || strlen($fathGivenName) > 0)
    $fatherName  = trim($fathGivenName . ' ' . $fathSurname);
if (strlen($mothSurname) > 0 || strlen($mothGivenName) > 0)
    $motherName  = trim($mothGivenName . ' ' . $mothSurname);

// parameter to nominalIndex.php
$nameuri                    = '';
$treeName                   = '';

// idir parameter explicitly provided for existing individual
if (canUser('edit'))
{                       // current user can edit the database
    if ($idirset)
    {                   // get the requested individual
        $person             = new Person(array('idir' => $idir));
        $isOwner            = $person->isOwner();
        $treeName           = $person->getTreeName();
    }                   // get the requested individual
    else
    {                   // create new individual with defaults
        // create an instance of Person for the new individual
        $person             = new Person();
        $idir               = null;
        //$person->setTreeName($treeName);
        $isOwner            = true;
    }                   // create new individual
}                       // current user can edit the database
else
{                       // current user can only view
    if ($idirset)
    {                   // get the requested individual
        $person             = new Person(array('idir' => $idir));
        $treeName           = $person->getTreeName();
    }                   // get the requested individual
    else
        $person             = null;
    $isOwner                = false;
}                       // current user can only view

if ($isOwner)
{
    if ($debug)
        $action             = 'Debug';
    else
        $action             = 'Update';
}
else
    $action                 = 'Display';

// now have enough information to select template file
$template                   = new FtTemplate("editIndivid$action$lang.html");
if ($debug)
    $warn .= "<p>\$template = new FtTemplate(\"editIndivid$action$lang.html\");</p>\n";
$translate                  = $template->getTranslate();
$t                          = $translate['tranTab'];
$template['otherStylesheets']->update(array('filename'  => 'editIndivid'));

// configuration options obtained from template
// if one of the following options is true then the event appears in
// the dialog even if the event is not recorded (e.g. date and place blank)
$alwaysShowBirth            = $template['BirthRowAlwaysShow'] != null;
$alwaysShowChristen         = $template['ChristeningRowAlwaysShow'] != null;
$alwaysShowBaptism          = $template['BaptismRowAlwaysShow'] != null;
$alwaysShowEndow            = $template['EndowmentRowAlwaysShow'] != null;
$alwaysShowConfirm          = $template['ConfirmationRowAlwaysShow'] != null;
$alwaysShowInitiat          = $template['InitiatoryRowAlwaysShow'] != null;
$alwaysShowDeath            = $template['DeathRowAlwaysShow'] != null;
$alwaysShowDeathCause       = false;
$alwaysShowBuried           = $template['BuriedRowAlwaysShow'] != null;
if ($debug)
{
    $warn   .= "<p>\$alwaysShowBirth=" . ($alwaysShowBirth?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowChristen=" . ($alwaysShowChristen?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowBaptism=" . ($alwaysShowBaptism?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowEndow=" . ($alwaysShowEndow?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowConfirm=" . ($alwaysShowConfirm?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowInitiat=" . ($alwaysShowInitiat?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowDeath=" . ($alwaysShowDeath?'true':'false') . "</p>\n";
    $warn   .= "<p>\$alwaysShowBuried=" . ($alwaysShowBuried?'true':'false') . "</p>\n";
}

// create table eventText which is used by Javascript
$eventText                  = $translate['eventText'];
$eventTextJs                = "    <script>\n      var eventText = {\n";
$comma                      = "\t\t";
foreach($eventText as $key => $value)
{
    $eventTextJs            .= "$comma\"$key\" :\t\"$value\"";
    $comma                  = ",\n\t\t";
}
$eventTextJs                .= "};\n    </script>\n";
$template->set('EVENTTEXT',         $eventTextJs);

// report errors detected in parameters
if (is_string($idirtext))
    $msg                    .= "Unexpected value '$idirtext' for IDIR. ";
if (is_string($idcrtext))
    $msg                    .= "Unexpected value '$idcrtext' for IDCR. ";
if (is_string($idmrtext))
    $msg                    .= "Unexpected value '$idmrtext' for ParentsIDMR. ";

// pass parameters to template
$template->set('LANG',              $lang);
if ($idir)
    $template->set('IDIR',          $idir);
else
{
    $template['person']->update(null);
    $template->set('IDIR',          0);
}

// initialize or update fields from passed parameters
$eSurname                   = '';
$eGiven                     = '';
$prefix                     = '';
$name                       = '';
$idar                       = 0;
$nameuri                    = '';

$birthChanged               = 0;        // birth event
$christenChanged            = 0;        // traditional christening event
$baptismChanged             = 0;        // LDS Baptism event
$endowChanged               = 0;        // LDS Endowment event
$confirmChanged             = 0;        // LDS Confirmation event
$initiatChanged             = 0;        // LDS Initiatory event
$deathChanged               = 0;        // death event
$buriedChanged              = 0;        // buried event


// loop through parameters again to update Person
if ($person instanceof Person)
{                   // $person initialized
    if ($debug)
        $warn  .= "<p>editIndivid.php: " . __LINE__ .
                        " Initialize from parameters</p>\n";
    // initialize the internal events structure of the Person
    if ($person && $idir > 0)
        $events             = $person->getEvents();
    else
        $events             = array();

    $given                  = $person['givenname'];
    $surname                = $person['surname'];
    $evBirth                = null;
    $haveBirth              = false;
    $evChristen             = null;
    $haveChristening        = false;
    $evBaptism              = null;
    $haveBaptism            = false;
    $evEndow                = null;
    $haveEndow              = false;
    $evConfirm              = null;
    $haveConfirm            = false;
    $evInitiat              = null;
    $haveInitiat            = false;
    $evDeath                = null;
    $haveDeath              = false;
    $evBuried               = null;
    $haveBuried             = false;

    foreach($events as $ev)
    {                   // loop through defined events
        switch($ev['idet'])
        {
            case Event::ET_BIRTH:
            {
                $evBirth                = $ev;
                $haveBirth              = true;
                break;
            }       // birth

            case Event::ET_CHRISTENING:
            {
                $evChristen             = $ev;
                $haveChristening        = true;
                break;
            }       // christening

            case Event::ET_LDS_BAPTISM:
            {
                $evBaptism              = $ev;
                $haveBaptism            = true;
                break;
            }       // LDS Baptism

            case Event::ET_LDS_ENDOWED:
            {
                $evEndow                = $ev;
                $haveEndow              = true;
                break;
            }       // LDS endowment

            case Event::ET_LDS_CONFIRMATION:
            {
                $evConfirm              = $ev;
                $haveConfirm            = true;
                break;
            }       // LDS confirmation

            case Event::ET_LDS_INITIATORY:
            {
                $evInitiat              = $ev;
                $haveInitiat            = true;
                break;
            }       // LDS initiatory

            case Event::ET_DEATH:
            {
                $evDeath                = $ev;
                $haveDeath              = true;
                break;
            }       // death

            case Event::ET_BURIAL:
            {
                $evBuried               = $ev;
                $haveBuried             = true;
                break;
            }       // burial
        }
    }                   // loop through defined events

    foreach($_GET as $key => $value)
    {               // loop through parameters
        $fieldLc            = strtolower($key);
        if (substr($fieldLc,0,4) == 'init')
        {           // initialize field in database record
            $fieldLc        = substr($fieldLc, 4);
            $value          = htmlspecialchars(trim($value));
            if (strlen($value) == 0)
                continue;
            switch($fieldLc)
            {
                case 'surname':
                {
                    $surname      = $value;
                    $person->setSurname($value);
                    break;
                }                   // surname
    
                case 'givenname':
                {
                    $given          = $value;
                    $person->set($fieldLc, $value);
                    break;
                }                   // givenname
    
                case 'birthdate':
                {
                    if (is_null($evBirth))
                        $evBirth      = $person->getBirthEvent(true);
                    $evBirth->setDate(' ' . $value);
                    $birthChanged      = 1;
                    break;
                }                   // birthdate
    
                case 'deathdate':
                {
                    if (is_null($evDeath))
                        $evDeath      = $person->getDeathEvent(true);
                    $evDeath->setDate(' ' . $value);
                    $deathChanged      = 1;
                    break;
                }                   // deathdate
    
                case 'chrisdate':
                {
                    if (is_null($evChristen))
                        $evChristen      = $person->getChristeningEvent(true);
                    $evChristen->setDate(' ' . $value);
                    $christenChanged  = 1;
                    break;
                }                   // chrisdate
    
                case 'burieddate':
                {
                    if (is_null($evBuried))
                        $evBuried      = $person->getBuriedEvent(true);
                    $evBuried->setDate(' ' . $value);
                    $buriedChanged      = 1;
                    break;
                }                   // burieddate
    
                case 'baptismdate':
                {
                    if (is_null($evBaptism))
                        $evBaptism      = $person->getBaptismEvent(true);
                    $evBaptism->setDate(' ' . $value);
                    $baptismChanged      = 1;
                    break;
                }                   // baptismdate
    
                case 'confirmationdate':
                {
                    if (is_null($evConfirm))
                        $evConfirm      = $person->getConfirmationEvent(true);
                    $evConfirm->setDate(' ' . $value);
                    $confirmChanged      = 1;
                    break;
                }                   // confirmationdate
    
                case 'endowdate':
                {
                    if (is_null($evEndow))
                        $evEndow      = $person->getEndowEvent(true);
                    $evEndow->setDate(' ' . $value);
                    $endowChanged      = 1;
                    break;
                }                   // endowdate
    
                case 'initiatorydate':
                {
                    if (is_null($evInitiat))
                        $evInitiat      = $person->getInitiatoryEvent(true);
                    $evInitiat->setDate(' ' . $value);
                    $initiatChanged      = 1;
                    break;
                }                   // initiatorydate
    
                case 'birthlocation':
                {                   // birth location name
                    $loc      = new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save();  // get IDLR
                    if (is_null($evBirth))
                        $evBirth      = $person->getBirthEvent(true);
                    $evBirth->set('idlrevent', $loc->getIdlr());
                    $birthChanged      = 1;
                    break;
                }                   // birth location name
    
                case 'chrislocation':
                {                   // christening location name
                    $loc      = new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save();  // get IDLR
                    if (is_null($evChristen))
                        $evChristen      = $person->getChristeningEvent(true);
                    $evChristen->set('idlrevent', $loc->getIdlr());
                    $chrisChanged      = 1;
                    break;
                }                   // christening location name
    
                case 'deathlocation':
                {
                    $loc      = new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save();  // get IDLR
                    if (is_null($evDeath))
                        $evDeath      = $person->getDeathEvent(true);
                    $evDeath->set('idlrevent', $loc->getIdlr());
                    $deathChanged      = 1;
                    break;
                }                   // death location name
    
                case 'buriedlocation':
                {
                    $loc      = new Location(array('location' => $value));
                    if (!$loc->isExisting())
                        $loc->save();  // get IDLR
                    if (is_null($evBuried))
                        $evBuried          = $person->getBuriedEvent(true);
                        $evBuried->set('idlrevent', $loc->getIdlr());
                        $buriedChanged      = 1;
                    break;
                }                   // buriedlocation
    
                case 'baptismtemple':
                {   // LDS temple fields
                    $loc          = new Temple(array('idtr' => $value));
                    if (!$loc->isExisting())
                        $msg      .= "Invalid Baptism Temple $value";
                    if (is_null($evBaptism))
                        $evBaptism          = $person->getBaptismEvent(true);
                    $evBaptism->set('idlrevent', $loc->getIdtr());
                    $baptismChanged          = 1;
                    break;
                }                   // LDS baptismtemple
    
                case 'confirmationtemple':
                {   // LDS temple fields
                    $loc          = new Temple(array('idtr' => $value));
                    if (!$loc->isExisting())
                        $msg      .= "Invalid Confirmation Temple $value";
                    if (is_null($evConfirm))
                        $evConfirm      = $person->getConfirmationEvent(true);
                    $evConfirm->set('idlrevent', $loc->getIdtr());
                    $confirmChanged      = 1;
                    break;
                }                   // LDS confirmationtemple
    
                case 'endowtemple':
                {   // LDS temple fields
                    $loc          = new Temple(array('idtr' => $value));
                    if (!$loc->isExisting())
                        $msg      .= "Invalid Endowment Temple $value";
                    if (is_null($evEndow))
                        $evEndow          = $person->getEndowEvent(true);
                    $evEndow->set('idlrevent', $loc->getIdtr());
                    $endowChanged          = 1;
                    break;
                }   // LDS temple fields
    
                case 'initiatorytemple':
                {   // LDS temple fields
                    $loc          = new Temple(array('idtr' => $value));
                    if (!$loc->isExisting())
                        $msg      .= "Invalid Initiatory Temple $value";
                    if (is_null($evInitiat))
                        $evInitiat          = $person->getInitatoryEvent(true);
                    $evInitiat->set('idlrevent', $loc->getIdtr());
                    $initiatChanged          = 1;
                    break;
                }       // LDS temple fields
    
                case 'gender':
                {       // set gender
                    $value  = strtolower($value);
                    if ($value == 1 || $value == 'f' ||
                        strpos($value, 'female') !== false)
                        $person->set('gender', 'female');
                    else
                    if ($value == 0 || $value == 'm' ||
                        strpos($value, 'male') !== false)
                        $person->set('gender', 'male');
                    else
                        $person->set('gender', 'unknown');
                    $genderFixed  = 'readonly="readonly"';
                    break;
                }           // set gender
    
                case 'lang':
                {
                    $lang           = FtTemplate::validateLang($value);
                }
    
                case 'text':
                {           // handled by javascript
                    break;
                }           // handled by javascript
    
                default:
                {
                    // note that if $fieldLc is not a field name in the
                    // record this will create a temporary field
                    $person->set($fieldLc, $value);
                    break;
                }           // no special handling
    
            }               // switch on field name
        }                   // initialize field in database record
    }                       // loop through parameters
}                           // $person initialized

// extract information from the instance of Person
if ($debug)
    $warn  .= "<p>editIndivid.php: " . __LINE__ . " \$person found or created</p>\n";

if ($person && $person->isExisting())
{                       // person is initialized
    // check for case of adding or editting a child
    if ($idcr > 0)
    {                   // child already exists and is already family member
        $childr                 = new Child(array('idcr' => $idcr));
        $parentsIdmr            = $childr['idmr'];
        $family                 = new Family(array('idmr' => $parentsIdmr));
        if ($family->isExisting())
        {
            $fatherName         = $family->getHusbName();
            $fatherName         = trim($fatherName);
            $motherName         = $family->getWifeName();
            $motherName         = trim($motherName);
        }
        else 
        {
            $warn              .= "<p>editIndivid.php: " . __LINE__ . " IDMR=$parentsIdmr not found in database.</p>\n";
        }               // getting parent's family failed
    }                   // child already exists and is already family member
    else
    if ($parentsIdmr > 0)
    {                   // identified a family to which to add the child 
        $family             = new Family(array('idmr' => $parentsIdmr));
        if ($family->isExisting())
        {               // got parents' family
            if ($idir == 0)
            {           // new child
                $person->save();
                $idir           = $person->getIdir();
            }           // new child
            $childr             = new Child(array('idmr'   => $parentsIdmr,
                                                  'idir'   => $idir));
            $childr->save();
            $idcr               = $childr['idcr'];
            $fatherName         = $family->getHusbName();
            $fatherName         = trim($fatherName);
            $motherName         = $family->getWifeName();
            $motherName         = trim($motherName);
        }               // got parents' family
        else
        {               // getting parents' family failed
            $warn               .= "editIndivid.php: " . __LINE__ . 
                            " parentsidmr=$value not found in database. ";
        }               // getting parents' family failed
        $title                  = 'Edit New Child of ';
        if (strlen($fatherName) > 0)
        {       // child has a father
            $title              .= $fatherName;
            if (strlen($motherName) > 0)
                $title          .= ' and ';
        }       // child has a father
        if (strlen($motherName) > 0)
            $title              .= $motherName;
    }       // identified a family to which to add the child

    // extract fields from individual for display
    $id                         = $person->get('id');
    $gender                     = $person->get('gender');
    $genderClass                = $genderClasses[$gender];
    $private                    = $person->get('private');
    $families                   = $person->getFamilies();
    $familiesCount              = count($families);
    $template->set('FAMILIESCOUNT',     $familiesCount);
    if ($template['ButtonFields'])
    {
        if ($familiesCount > 0)
            $template['addFamily']->update(null);
        else
            $template['editFamilies']->update(null);
    }
    $parents                    = $person->getParents();
    $parentsCount               = count($parents);
    $template->set('PARENTSCOUNT',      $parentsCount);
    if ($template['ButtonFields'])
    {
        if ($parentsCount > 0)
            $template['addParents']->update(null);
        else
            $template['editParents']->update(null);
    }

    if (count($families) > 0)
    {       // ensure never married indicator is off
        $template->set('NEVERMARRIEDCHECKED',       '');
        $template->set('NEVERMARRIEDRO',           
                       'readonly="readonly" disabled="disabled"');
    }       // ensure never married indicator is off
    else
    {       // allow never married indicator to be set
        $neverMarried           = $person->get('neverMarried');
        if ($neverMarried)
            $template->set('NEVERMARRIEDCHECKED',   'checked="checked"');
        else
            $template->set('NEVERMARRIEDCHECKED',   '');
        $template->set('NEVERMARRIEDRO',            '');
    }       // allow never married indicator to be set

    // the value of IDMRPref may be invalid due to a flaw in
    // the earlier implementation.  If so fix it.
    $idmrpref                   = $person->get('idmrpref') - 0;
    if ($idmrpref == 0 &&
        count($families) > 0)
        $idmrpref                = $families->rewind()->getIdmr();

    // information for Ancestry.ca search
    $fatherGivenName            = '';
    $fatherSurname              = '';
    $motherGivenName            = '';
    $motherSurname              = '';
    $prefParents                = $person->getPreferredParents();
    if ($prefParents)
    {           // have preferred parents
        $father                 = $prefParents->getHusband();
        if ($father)
        {           // have father
            $fatherGivenName    = $father->getGivenName();
            $fatherSurname      = $father->getSurname();
        }           // have father
        $mother                 = $prefParents->getWife();
        if ($mother)
        {           // have father
            $motherGivenName    = $mother->getGivenName();
            $motherSurname      = $mother->getSurname();
        }           // have father
    }           // have preferred parents

    $ancInterest    = $person->get('ancinterest');
    $decInterest    = $person->get('decinterest');

    $userRef        = str_replace('"','&quot;',$person->get('userref'));
    $ancestralRef   = str_replace('"','&quot;',$person->get('ancestralref'));

    // construct title
    $name           = $person->getName(Person::NAME_INCLUDE_DATES);
    if (strlen($name) == 0)
        $name              = 'New Person';
    $idar           = $person->get('idar') - 0;
}                       // person is initialized
else
{                       // adding new person
    $idir                   = 0;
    if ($person)
    {
        $id                 = $person->get('id');
        $gender             = $person->get('gender');
        $givennames         = preg_split('/\s+/', $person['givenname']);
        $nicknameset        = new RecordSet('Nicknames',
                                            array('nickname' => $givennames));
        foreach($nicknameset as $nickname)
        {
            if ($nickname['gender'] == 0)
            {
                $gender         = 0;
                break;
            }
            else
            if ($nickname['gender'] == 1)
            {
                $gender         = 1;
                break;
            }
        }
        $person['gender']   = $gender;
        $genderClass        = $genderClasses[$gender];
        $private            = $person->get('private');
    }
    else
    {
        $id                 = 0;
        $gender             = 2;
        $genderClass        = 'unknown';
        $givennames         = preg_split('/\s+/', $given);
        $nicknameset        = new RecordSet('Nicknames',
                                            array('nickname' => $givennames));
        foreach($nicknameset as $nickname)
        {
            if ($nickname['gender'] == 0)
            {
                $gender         = 0;
                $genderClass    = 'male';
                break;
            }
            else
            if ($nickname['gender'] == 1)
            {
                $gender         = 1;
                $genderClass    = 'female';
                break;
            }
        }
        $private            = false;
    }
    $neverMarried           = 0;
    $neverMarriedRO         = '';
    $fatherGivenName        = '';
    $fatherSurname          = '';
    $fatherName             = '';
    $motherGivenName        = '';
    $motherSurname          = '';
    $motherName             = '';
    $userRef                = '';
    $ancestralRef           = '';
    $ancInterest            = 0;
    $decInterest            = 0;
    $parents                = array();
    $families               = array();
    $idmrpref               = 0;

    $template->set('FAMILIESCOUNT',     0);
    $template->set('PARENTSCOUNT',      0);
    if ($rowid == 'husb' || $rowid == 'wife') 
    {                   // families dialog is already open
        $template['Marriages']->update(null);
        $template['editParents']->update(null);
    }                   // families dialog is already open
    else
    if ($rowid == 'father' || $rowid == 'mother')
    {                   // parents dialog is already open
        $template['Parents']->update(null);
        $template['Marriages']->update(null);
    }                   // parents dialog is already open
    else
    {                   // permit adding parents or families
        $template['editParents']->update(null);
        $template['editFamilies']->update(null);
    }                   // permit adding parents or families

    // construct title
    switch($rowid)
    {
        case 'husb':
            $name                  = $template['newHusband']->innerHTML;
            break;

        case 'wife':
            $name                  = $template['newWife']->innerHTML;
            break;

        case 'father':
            $name                  = $template['newFather']->innerHTML;
            break;

        case 'mother':
            $name                  = $template['newMother']->innerHTML;
            break;

        default:
            if (strlen($rowid) > 0)
                $name               = $template['newChild']->innerHTML;
            else
                $name               = $template['newPerson']->innerHTML;
            break;
    }
    $name                   .= " $given $surname";
    $idar                   = 0;
}                       // adding new person

$eGiven                     = str_replace('"','&quot;',$given);
$eSurname                   = str_replace('"','&quot;',$surname);

$title                      = "Edit $name";
$nameuri                    = rawurlencode($surname . ', ' . $given);
$priName                    = $person->getPriName();
$idnx                       = $priName['idnx'];
$nameset                    = new RecordSet('tblNX',
                                            array('idir'    => $idir,
                                            'type'    => '>0'));
$nametext                   = '';
$ntemplate                  = $template['altNameRow'];
$nttext                     = $ntemplate->outerHTML;
foreach($nameset as $altidnx => $altname)
{                       // loop through alternate names
    $nametext   .= str_replace(array('$ALTNAME','$IDNX'),
                               array( $altname->getName(), $altidnx),
                               $nttext);
}                       // loop through alternate names
$ntemplate->update($nametext);

$template->set('NAMEURI',           $nameuri);
$template->set('SURNAME',           $surname);
$template->set('GIVEN',             $given);
$template->set('EGIVEN',            $eGiven);
$template->set('IDNX',              $idnx);
$template->set('ESURNAME',          $eSurname);
$template->set('NAME',              $name);
$template->set('IDMRPREF',          $idmrpref);
$template->set('ID',                $id);
$template->set('PARENTSIDMR',       $parentsIdmr);
$template->set('IDCR',              $idcr);
$template->set('IDAR',              $idar);
if ($template['PicturesRow'])
{
    if ($idar)
        $template['addAddress']->update(null);
    else
        $template['editAddress']->update(null);
}
$template->set('FATHERGIVENNAME',   $fatherGivenName);
$template->set('FATHERSURNAME',     $fatherSurname);
$template->set('MOTHERGIVENNAME',   $motherGivenName);
$template->set('MOTHERSURNAME',     $motherSurname);

// identify prefix of name for name summary page
if (strlen($surname) == 0)
    $prefix                  = '';
else
if (substr($surname,0,2) == 'Mc')
    $prefix                  = 'Mc';
else
    $prefix                  = substr($surname,0,1);
$template->set('PREFIX',            $prefix);

$template->set('TREENAME',          $treeName);
if (strlen($treeName) == 0)
    $template['inTree']->update(null);

// if not authorized do nothing more
if ($action == 'Display')
{
    $template->display();
    exit;
}

// set up for display of basic attributes
$template->set('GENDERFIXED',       $genderFixed);
$template->set('GENDERCLASS',       $genderClass);
for ($g = 0; $g < 3; ++$g)
{
    if ($g == $gender)
        $template->set("SELECTEDGENDER$g",       'selected="selected"');
    else
        $template->set("SELECTEDGENDER$g",       '');
}
if (strlen($fatherName) > 0)
    $template->set('FATHERNAME',    $fatherName);
else
if ($template['fatherRow'])
    $template['fatherRow']->update(null);
else
    print htmlspecialchars($template->getRawTemplate());
if (strlen($motherName) > 0)
    $template->set('MOTHERNAME',    $motherName);
else
    $template['motherRow']->update(null);

if ($idir == 0)
{
    $template['deleteButton']->update(null);
    $template['mergeButton']->update(null);
}
else
if (count($parents) == 0 &&
    count($families) == 0)
{       // individual is not connected to any others
    $template['cancelButton']->update(null);
    $template['mergeButton']->update(null);
}       // individual is not connected to any others
else
{       // individual is connected
    $template['cancelButton']->update(null);
    $template['deleteButton']->update(null);
}       // individual is connected

// permit editing contents of Child record
if ($childr)
{       // permit editing contents of Child record
    $childStatus  = $childr['idcs'];
    for($idcs = 1; $idcs <= 5; ++$idcs)
    {
        if ($idcs == $childStatus)
            $template->set("SELECTEDSTATUS$idcs",   'selected="selected"');
        else
            $template->set("SELECTEDSTATUS$idcs",   '');
    }
    $relDad          = $childr['idcpdad'];
    for($cprel = 1; $cprel <= 13; ++$cprel)
    {
        if ($cprel == $relDad)
            $template->set("SELECTEDRELDAD$cprel",   'selected="selected"');
        else
            $template->set("SELECTEDRELDAD$cprel",   '');
    }
    $relMom          = $childr['idcpmom'];
    for($cprel = 1; $cprel <= 13; ++$cprel)
    {
        if ($cprel == $relMom)
            $template->set("SELECTEDRELMOM$cprel",   'selected="selected"');
        else
            $template->set("SELECTEDRELMOM$cprel",   '');
    }
    $dadPrivate      = $childr['cpdadprivate'];
    if ($dadPrivate)
        $template->set("DADPRIVATECHECKED",   'checked="checked"');
    else
        $template->set("DADPRIVATECHECKED",   '');
    $momPrivate      = $childr['cpmomprivate'];
    if ($momPrivate)
        $template->set("MOMPRIVATECHECKED",   'checked="checked"');
    else
        $template->set("MOMPRIVATECHECKED",   '');

    // LDS Sealed to Parents Event
    $parSealed          = $childr->getParSealEvent(true);
    $date              = $parSealed->getDate();
    $template->set('SEALINGDATE',                   $date);
    $template->set('SEALINGIDER',                   $parSealed['ider']);
    $idlrevent          = $parSealed->get('idlrevent');
    $temples          = new RecordSet('Temples');
    if ($idlrevent > 1)
    {
        $temples[$idlrevent]->set('selected',       'selected="selected"');
        $template->set('NOTEMPLE',                  '');
    }
    else
    {
        $template->set('NOTEMPLE',                  'selected="selected"');
    }
    $template['temple$idtr']->update($temples);
}   
else
    $template['RelationshipFields']->update(null);

// add mandatory events
$childevents                = array();
$seniorevents               = array();
if ($alwaysShowBirth && !$haveBirth)
{
    if (is_null($evBirth))
        $evBirth            = $person->getBirthEvent(true);
    array_push($childevents, $evBirth);
}

if ($alwaysShowChristen && !$haveChristening)
{
    if (is_null($evChristen))    
        $evChristen         = $person->getChristeningEvent(true);
    array_push($childevents, $evChristen);
}

if ($alwaysShowBaptism && !$haveBaptism)
{
    if (is_null($evBaptism))
        $evBaptism          = $person->getBaptismEvent(true);
    array_push($childevents, $evBaptism);
}

if ($alwaysShowEndow && !$haveEndow)
{
    if (is_null($evEndow))
        $evEndow            = $person->getEndowEvent(true);
    array_push($childevents, $evEndow);
}

if ($alwaysShowConfirm && !$haveConfirm)
{
    if (is_null($evConfirm))
        $evConfirm          = $person->getConfirmEvent(true);
    array_push($childevents, $evConfirm);
}

if ($alwaysShowInitiat && !$haveInitiat)
{
    if (is_null($evInitiat))
        $evInitiat          = $person->getInitiatoryEvent(true);
    array_push($childevents, $evInitiat);
}

if ($alwaysShowDeath && !$haveDeath)
{
    if (is_null($evDeath))
        $evDeath            = $person->getDeathEvent(true);
    array_push($seniorevents, $evDeath);
}

if ($alwaysShowBuried && !$haveBuried)
{
    if (is_null($evBuried))
        $evBuried           = $person->getBuriedEvent(true);
    array_push($seniorevents, $evBuried);
}

// merge all defined events putting child events before explicit events
// and senior events after explicit events
$events             = array_merge($childevents, $events, $seniorevents);

// the following function wrapper is required because the
// PHP function usort does not support OOP
function order($ev1, $ev2)
{           // customize sort order
    global  $warn;
    return $ev1->compare($ev2);
}           // customize sort order
// sort the events
usort($events,
      __NAMESPACE__ . '\\order');

$eventsText                 = '';
$rownum                     = 1;
$deathCause                 = $person->get('deathcause');
foreach($events as $ie => $event)
{                       // loop through Events
    //$warn   .= $event->dump('editIndivid.php: ' . __LINE__);
    $ider               = $event->get('ider');
    $citType            = $event->getCitType();
    $idet               = $event->get('idet');
    $idlr               = $event->get('idlrevent');
    $kind               = $event->get('kind');
    $preferred          = $event->get('preferred');
    $order              = $event->get('order');
    if (array_key_exists($idet, Event::$eventText))
    {               // assign appropriate label
        $type           = ucfirst($eventText[$idet]);
    }               // assign appropriate label
    else
    {               // IDET missing from translation table
        $type           =  "IDET=$idet";
    }               // IDET missing from translation table

    $date               = new LegacyDate($event->get('eventd'));
    $date               = $date->toString();
    $eventd             = $event->get('eventd');
    if (substr($eventd, 0, 1) == ':')
        $dateError      = 'error';
    else
        $dateError      = '';
    $datesd             = $event->get('eventsd');

    $location           = $event->getLocation();
    $locationName       = $location->getName();

    $notes              = $event->getNotes();
    $descn              = $event->getDescription();

    $notshown           = !$preferred;
    $changed            = 0;

    if ($preferred)
    {               // preferred events have special layouts
        switch($idet)
        {           // act on specific event types
            case Event::ET_BIRTH:
            {
                $etag          = $template["BirthRow"];
                break;
            }       // birth

            case Event::ET_CHRISTENING:
            {
                $etag          = $template["ChristeningRow"];
                break;
            }       // christening

            case Event::ET_LDS_BAPTISM:
            {
                $etag          = $template["BaptismRow"];
                break;
            }       // LDS Baptism

            case Event::ET_LDS_ENDOWED:
            {
                $etag          = $template["EndowmentRow"];
                break;
            }       // LDS endowment

            case Event::ET_LDS_CONFIRMATION:
            {
                $etag          = $template["ConfirmationRow"];
                break;
            }       // LDS confirmation

            case Event::ET_LDS_INITIATORY:
            {
                $etag          = $template["InitiatoryRow"];
                break;
            }       // LDS initiatory

            case Event::ET_DEATH:
            {
                $etag          = $template["DeathRow"];
                break;
            }       // death

            case Event::ET_BURIAL:
            {
                $etag          = $template["BuriedRow"];
                break;
            }       // burial

            default:
            {       // any other preferred event
                $etag          = $template['EventRow$rownum'];
                break;
            }       // any other preferred event
        }           // act on specific event types
    }               // preferred events have special layouts
    else
    {               // standard event contained
        $etag                  = $template['EventRow$rownum'];
    }               // standard event contained

    $etemplate          = new \Templating\Template($etag->outerHTML);
    $etemplate->set('rownum',               $rownum);
    $etemplate->set('type',                 $type);
    $etemplate->set('ider',                 $ider);
    $etemplate->set('cittype',              $citType);
    $etemplate->set('idet',                 $idet);
    $etemplate->set('idlr',                 $idlr);
    $etemplate->set('kind',                 $kind);
    if ($preferred)
        $etemplate->set('preferredchecked', 'checked="checked"');
    else
        $etemplate->set('preferredchecked', '');
    $etemplate->set('order',                $order);
    $etemplate->set('date',                 $date);
    $etemplate->set('datesd',               $datesd);
    $etemplate->set('dateerror',            $dateError);
    $etemplate->set('desc',                 $notes);
    $etemplate->set('descn',                $descn);
    $etemplate->set('locationname',         $locationName);
    $etemplate->set('changed',              $changed);
    $eventsText         .= $etemplate->compile();

    if ($idet == Event::ET_DEATH && $alwaysShowDeathCause)
    {
        $etag           = $template["DeathCauseRow"];
        $etemplate      = new \Templating\Template($etag->outerHTML);
        $etemplate->set('deathcause',       $deathCause);
        $eventsText     .= $etemplate->compile();
    }
    ++$rownum;
}                       // loop through Events
$template->set('EVENTS',                    $eventsText);

// assignn values for sommon fields
$private                      = $person->get('private');
$template->set('PRIVATE',                   $private);
for ($g = 0; $g <= 2; ++$g)
{
    if ($g == $private)
        $template->set("SELECTEDPRIVATE$g",       'selected="selected"');
    else
        $template->set("SELECTEDPRIVATE$g",       '');
}

$ancinterest                      = $person->get('ancinterest');
$template->set('ANCINTEREST',               $ancinterest);
for ($g = 0; $g <= 3; ++$g)
{
    if ($g == $ancinterest)
        $template->set("SELECTEDANCINTEREST$g",       'selected="selected"');
    else
        $template->set("SELECTEDANCINTEREST$g",       '');
}

$decinterest                      = $person->get('decinterest');
$template->set('DECINTEREST',               $decinterest);
for ($g = 0; $g <= 3; ++$g)
{
    if ($g == $decinterest)
        $template->set("SELECTEDDECINTEREST$g",       'selected="selected"');
    else
        $template->set("SELECTEDDECINTEREST$g",       '');
}
$template->set('USERREF',                   $person->get('userref'));
$template->set('ANCESTRALREF',              $person->get('ancestralref'));

$template->display();
