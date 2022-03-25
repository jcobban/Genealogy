<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\escape;
/************************************************************************
 *  editMarriages.php                                                   *
 *                                                                      *
 *  Display a web page for editing the families for which a particular  *
 *  Person has the role of spouse from the Legacy database              *
 *                                                                      *
 *  Parameters (passed by method=get)                                   *
 *      idir            unique numeric key of Person as spouse          *
 *      child           unique numeric key of Person as child           *
 *      given           given name of Person in case that               *
 *                      information is not already written to the       *
 *                      database                                        *
 *      surname         surname of Person in case that information      *
 *                      is not already written to the database          *
 *      idmr            numeric key of specific marriage to initially   *
 *                      display                                         *
 *      treename        name of tree subdivision of database            *
 *      new             parameter to add a new family                   *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/21      Change to use new page format                   *
 *      2010/09/04      Add button to reorder marriages                 *
 *                      Get birth and death dates into variables        *
 *      2010/10/21      use RecOwners class to validate access          *
 *                      add balloon help for buttons                    *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/11/15      eliminate use of obsolete showDate              *
 *      2010/11/27      add parameters given and surname because the    *
 *                      user may have modified the name in the          *
 *                      invoking editIndivid.php web page but not       *
 *                      updated the database record yet.                *
 *      2010/12/04      add link to help panel                          *
 *                      improve separation of HTML and JS               *
 *      2010/12/12      replace LegacyDate::dateToString with           *
 *                      LegacyDate::toString                            *
 *                      escape special chars in title                   *
 *      2010/12/20      handle exception thrown by new LegacyIndiv      *
 *                      handle both idir= and id=                       *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/03/25      support keyboard shortcuts                      *
 *      2011/06/18      merge with editMarriage.php                     *
 *      2011/10/01      support database assisted location name         *
 *      2011/11/15      add parameter idmr to initiate editing specific *
 *                      family                                          *
 *                      add buttons to edit Husband or Wife as          *
 *                      individuals                                     *
 *      2011/11/26      support editing married surnames                *
 *      2011/12/21      support additional events                       *
 *                      display all events in the marriage panel        *
 *                      suppress function if user is not authorized     *
 *      2012/01/13      change class names                              *
 *                      all buttons use id= rather than name= to avoid  *
 *                      problems with IE passing them as parameters     *
 *                      support updating all fields of LegacyFamily     *
 *                      record                                          *
 *                      use $idir as identifier of primary spouse       *
 *      2012/01/23      display loading indicator while waiting for     *
 *                      response to changes in a location field         *
 *      2012/02/01      permit idir parameter optional if idmr specified*
 *      2012/02/25      change ids of fields in marriage list to contain*
 *                      IDMR instead of row number                      *
 *      2012/05/27      specify explicit class on all                   *
 *                      <input type="text">                             *
 *      2012/05/29      identify row of table of children by IDCR in    *
 *                      case the same child appears more than once      *
 *      2012/11/17      initialize $family for display of specific      *
 *                      marriage                                        *
 *                      display family events from event table on       *
 *                      requested marriage                              *
 *                      change implementation so event type or IDER     *
 *                      value is contained in the name of the button,   *
 *                      not from a hidden field matching the rownum     *
 *      2012/11/27      always display the marriage details form        *
 *                      always filled in dynamically as a result of     *
 *                      receiving the response to an AJAX request,      *
 *                      rather than sometimes filled in by PHP and some *
 *                      times by javascript.                            *
 *                      the location of the sealed to spouse event is   *
 *                      made a selection list to permit updating.       *
 *      2013/01/26      make children's names and dates editable        *
 *      2013/01/23      add undocumented option to submit request in    *
 *                      order to be able to see XML response            *
 *      2013/03/25      add ability to detach just added child          *
 *      2013/05/17      shrink dialog vertically by using               *
 *                      <button class="button">                         *
 *      2013/05/20      change terminology from Marriage to Family      *
 *                      add templates for never married and no children *
 *                      facts                                           *
 *      2013/05/29      add template for new location warning           *
 *      2013/06/01      LegacyIndiv::getMarriages renamed to getFamilies*
 *      2013/07/03      use explicit classes for husband and wife links *
 *      2013/08/14      include title and suffix in title of page       *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/08      standardize appearance of <select>              *
 *      2014/02/24      use dialog to choose from range of locations    *
 *                      instead of inserting <select> into the form     *
 *                      location support moved to locationCommon.js     *
 *                      rename buttons to choose an existing Person     *
 *                      as husband or wife to id="choose..."            *
 *                      handle all child rows the same with the fields  *
 *                      uniquely identified by the order value of the   *
 *                      corresponding LegacyChild records               *
 *      2014/03/19      use CSS rather than tables to layout form       *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/06/02      add IDCR parameter back into child table row    *
 *      2014/07/15      add help balloon for Order Events button        *
 *      2014/07/15      support for popupAlert moved to common code     *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/10/02      add prompt to confirm deletion                  *
 *      2014/10/12      correct married surnames with quotes (O'Brien)  *
 *      2014/11/14      initialize display of family without requiring  *
 *                      AJAX                                            *
 *      2014/11/16      correct parameter list for new LegacyFamily     *
 *                      when adding a new family to an Person           *
 *      2014/11/29      print $warn, which may contain debug trace      *
 *      2014/12/26      response from getFamilies is indexed by idmr    *
 *      2015/02/01      get temple select options from database         *
 *                      get event texts from class Event and            *
 *                      make them available to Javascript               *
 *      2015/02/19      remove user of deprecated interface to          *
 *                      LegacyFamily constructor                        *
 *                      change remaining debug code to add to $warn     *
 *      2015/02/25      do not access name and birth date of spouses    *
 *                      from the family record                          *
 *      2015/04/28      add warning dialog that a child is already      *
 *                      edited when attempt to edit the child for whom  *
 *                      a set of parents is being created or edited     *
 *      2015/05/14      handle exception for bad IDLRMarr value         *
 *      2015/06/20      failed if IDMRPref set in Person to bad         *
 *                      family value                                    *
 *                      document action of enter key in child row       *
 *                      Make the notes field a rich-text editor.        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/12      add support for tree division of database       *
 *      2015/08/22      popup dialogs were not defined as <form>s       *
 *      2015/08/23      adding family to new Person gave blank          *
 *                      primary spouse                                  *
 *      2016/02/06      use showTrace                                   *
 *      2016/02/24      handle child record with invalid IDIR           *
 *      2016/06/30      length of dates in added children slightly      *
 *                      shorter than ones loaded at start               *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *                      use preferred parameters for new LegacyFamily   *
 *      2017/08/16      legacyIndivid.php renamed to Person.php         *
 *      2017/09/02      class LegacyTemple renamed to class Temple      *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/18      use RecordSet instead of Temple::getTemples     *
 *      2018/02/16      remove unnecessary <p> and <br> inserted by     *
 *                      tinyMCE from the marriage notes                 *
 *      2018/11/19      change Helpen.html to Helpen.html               *
 *      2019/07/20      rearrange order of fields to simplify           *
 *                      updateMarriageXml.php                           *
 *      2020/01/30      use Template                                    *
 *                      display all events in order                     *
 *      2020/03/19      misspelled $urname                              *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2020/12/12      improve output when errors present              *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FamilyTree.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters passed to the script
$idir               = null;     // Person as primary spouse in family
$idirtext           = null;
$idmr               = null;     // marriage to display
$idmrtext           = null;
$indiv              = null;     // instance of Person
$family             = null;     // instance of Family
$child              = null;     // IDIR of Person as child in new family
$childObj           = null;     // instance of Person
$childtext          = null;
$sex                = '';
$isowner            = false;    // current user is an owner of the family
$given              = '';       // given name of Person
$surname            = '';       // surname of Person
$name               = '';       // name of Person
$treename           = '';       // treename of database division
$prefix             = '';       // initial part of surnames
$birth              = '';       // birth date as string
$death              = '';       // death date as string
$idmrpref           = 0;        // preferred marriage for the Person
$lang               = 'en';
$style              = 'Marriages';
$newfamily          = false;
$families           = null;
$submit             = false;

// if invoked by method=get process the parameters
if (isset($_GET) && count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
        $value      = trim($value);
        if (strlen($value) > 0)
        switch(strtolower($key))
        {       // take action on specific parameter
            case 'id':
            case 'idir':
            {       // identify primary spouse
                if (ctype_digit($value))
                    $idir       = $value;
                else
                    $idirtext   = htmlspecialchars($value);
                break;
            }       // identify primary spouse

            case 'child':
            {       // identify child of family
                if (ctype_digit($value))
                    $child      = $value;
                else
                    $childText  = htmlspecialchars($value);
                $style          = 'Parents';
                break;
            }       // identify child of family

            case 'given':
            {
                $given              = htmlspecialchars($value);
                break;
            }       // default given name of Person

            case 'surname':
            {
                $surname            = htmlspecialchars($value);
                break;
            }       // default surname of Person

            case 'treename':
            {
                $treename           = htmlspecialchars($value);
                break;
            }       // default surname of Person

            case 'idmr':
            {       // identify specific marriage to select for display
                if (ctype_digit($value))
                {
                    $idmr           = $value;
                    if ($idmr == 0)
                        $newfamily  = true;
                    else
                        $idmrText   = htmlspecialchars($value);
                }
                else
                    $idmrText       = htmlspecialchars($value);
                break;
            }       // identify specific marriage

            case 'new':
            {       // add a new family
                if (strtolower($value) == 'y')
                    $newfamily  = true;
                break;
            }       // add a new family

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
            }

            case 'submit':
            case 'debug':
            {       // emit debugging information
                if ($value == 'Y' || $value == 'y')
                    $submit = true;
                break;
            }       // emit debugging information

            // ignore unrecognized parameters
        }       // take action on specific parameter
    }           // loop through all parameters passed to script
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

// get instance of FamilyTree
$tree               = new FamilyTree();

// get template
// style is either "Marriages" or "Parents"
$template           = new FtTemplate("edit$style$lang.html",
                                     true);
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];
$template->updateTag('otherStylesheets',
                     array('filename'   => '/FamilyTree/editMarriages'));

if (is_string($idirtext))
{
    $text           = $template['invalidIdir']->innerHTML;
    $msg            .= str_replace('$idirtext',
                                   $idirtext,
                                   $text);
}
if (is_string($childtext))
{
    $text           = $template['invalidChild']->innerHTML;
    $msg            .= str_replace('$childtext',
                                   $childtext,
                                   $text);
}
if (is_string($idmrtext))
{
    $text           = $template['invalidIdmr']->innerHTML;
    $msg            .= str_replace('$idmrtext',
                                   $idmrtext,
                                   $text);
}

if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');

// validate the parameters
if (!is_null($idmr))
{       // display a specific marriage
    if ($debug)
        $warn   .= "<p>" . __LINE__ .
                    " new Family('idmr'=>$idmr)</p>\n";
    $family                 = $tree->getFamily($idmr);
    if (is_null($idir) || $idir == 0)
        $idir               = $family['idirhusb'];
    if (is_null($idir) || $idir == 0)   // no husband
        $idir               = $family['idirwife'];

    $indiv                  = $tree->getPerson($idir);
    if ($indiv && $indiv->isExisting())
    {                   // existing Person
        $isOwner            = canUser('edit') && $indiv->isOwner();
        $families           = $indiv->getFamilies();
    }                   // existing Person
    else
    {
        $isOwner            = canUser('edit');
        $text               = $template['titleEditIdmr']->innerHTML;
        $title              = str_replace('$IDMR',
                                          $idmr,
                                          $text);
        $families           = array($family);
    }

}                   // explicit family to view
else
if (!is_null($idir))
{                   // the identified Person is the primary spouse
    $indiv                  = $tree->getPerson($idir);
    $sex                    = $indiv['gender'];
    $isOwner                = canUser('edit') && $indiv->isOwner();

    $families               = $indiv->getFamilies();
    $idmrpref               = $indiv['idmrpref'];
    if (count($families) == 0 && $idmrpref > 0)
    {           // correct database error
        $indiv->set('idmrpref', 0);
        $indiv->save();
        $idmrpref           = 0;
    }           // correct database error

    // choose family to display based upon parameters
    if ($idmrpref == 0 || $newfamily)
    {                   // preferred marriage not already set
        if (count($families) > 0 && !$newfamily)
        {               // at least one marriage
            $family         = $families->rewind();
            if ($family)
            {           // have first family
                $idmrpref   = $family->getIdmr();
                // update field in Person
                $indiv->set('idmrpref', $idmrpref);
                $indiv->save();
            }           // have first family
        }               // at least one marriage
        else            // no families
        if ($sex == 0)
        {               // male and no family
            if ($debug)
                $warn   .= "<p>" . __LINE__ . " new Family(array('idirhusb' => $idir))</p>\n";
            $family         = new Family(array('idirhusb' => $indiv));
        }               // male and no family
        else
        {               // female and no family
            if ($debug)
                $warn   .= "<p>" . __LINE__ . " new Family(array('idirwife' => $idir)))</p>\n";
            $family         = new Family(array('idirwife' => $indiv));
        }               // female and no family
    }                   // preferred marriage not already set
    else
    {
        if ($debug)
            $warn       .= "<p>" . __LINE__ . " new Family('idmr'=>$idmrpref)</p>\n";
        $family             = $tree->getFamily($idmrpref);
    }

    $text               = $template['titleEditFamilies']->innerHTML;
    $title              = str_replace(array('$GIVEN','$SURNAME'),
                                      array($given, $surname),
                                      $text);

}               // get the requested spouse
else
if (!is_null($child))
{                       // the identified Person is a child
    $childObj               = $tree->getPerson($child);
    if ($childObj && $childObj->isExisting())
    {
        $isOwner            = canUser('edit') && $childObj->isOwner();
        $idmrparents        = $childObj['idmrparents'];
        $families           = $childObj->getParents();
        if (count($families) > 0)
        {               // at least one set of parents
            if ($idmrparents == 0)
            {           // set preferred parents to first parents
                $family     = $families->rewind();
                if ($family)
                {       // have first family
                    $idmrparents    = $family->getIdmr();
                    // update field in Person
                    $childObj->set('idmrparents', $idmrparents);
                    $childObj->save();
                }       // have first family
            }           // preferred parents not set
            else
            {
                $family         = $tree->getFamily($idmrparents);
            }
        }               // at least one set of parents
        else
        {           // no existing parents
            if ($debug)
                $warn   .= "<p>" . __LINE__ . " new Family(array('husbsurname' => '$surname', 'husbmarrsurname' => $surname'))</p>\n";
            $parms              = array('husbsurname'   => $surname,
                                        'husbmarrsurname' => $surname);
            $family             = new Family($parms);
        }           // no existing parents

        $text                   = $template['titleEditParents']->innerHTML;
        $title                  = str_replace(array('$GIVEN','$SURNAME'),
                                          array($given, $surname),
                                          $text);
    }
    else
    {               // error in new Child
        $title                  = $template['titleEditInvalid']->innerHTML;
        $msg                    .= "Child=$child: invalid identification. ";
        $isOwner                = true;
        $childObj               = null;
    }               // error in new Child

    if (!$isOwner)
    {
        $text                   = $template['notOwner']->innerHTML;
        $msg                    .= str_replace(array('$GIVEN','$SURNAME'),
                                               array($given, $surname),
                                               $text);
    }
}       // get the requested child
else
{       // required parameter missing or invalid
    $title              = $template['titleMissing']->innerHTML;
    $msg                .= "$title. ";
}       // missing required parameter

// get information about the primary Person for use in titles
if (isset($indiv) && $indiv->isExisting())
{                       // existing Person
    $isOwner                    = canUser('edit') && $indiv->isOwner();
    $priName                    = $indiv->getPriName();
    if ($given == '')
        $given                  = $priName->getGivenName();
    if ($surname == '')
        $surname                = $priName->getSurname();
    $name                       = $priName->getName($t);
    $sex                        = $indiv['gender'];
    $evBirth                    = $indiv->getBirthEvent();
    if ($evBirth)
        $birth                  = $evBirth->getDate();
    $evDeath                    = $indiv->getDeathEvent();
    if ($evDeath)
        $death                  = $evDeath->getDate();

    $text                       = $template['titleEditFamilies']->innerHTML;
    $title                      = str_replace(array('$GIVEN','$SURNAME'),
                                          array($given, $surname),
                                          $text);
    $families                   = $indiv->getFamilies();

    if (!$isOwner)
        $msg    .= "You are not authorized to edit the marriages of $name. ";
}                       // existing Person
else
if (isset($childObj) && $childObj->isExisting())
{                       // existing Child
    $priName                    = $childObj->getPriName();
    if ($given == '')
        $given                  = $priName->getGivenName();
    if ($surname == '')
        $surname                = $priName->getSurname();
}
else
    $name                       = "$given $surname";

if (strtolower(substr($surname, 0, 2)) == 'mc')
    $prefix                     = 'Mc';
else
    $prefix                     = substr($surname, 0, 1);

if (strlen($msg) == 0 || $family instanceof Family)
{
    $template->set('TITLE',     $title);        // page title
    $template->set('IDIR',      $idir);         // Person as spouse
    $template->set('CHILD',     $child);        // IDIR of Person as child
    $template->set('SEX',       $sex);          // gender
    $template->set('GIVEN',     $given);        // given name of Person
    $template->set('SURNAME',   $surname);      // surname of Person
    $template->set('NAME',      $name);         // name of Person
    $template->set('TREENAME',  $treename);     // treename 
    $template->set('PREFIX',    $prefix);       // initial part of surnames
    $template->set('BIRTH',     $birth);        // birth date as string
    $template->set('DEATH',     $death);        // death date as string
    $template->set('IDMRPREF',  $idmrpref);     // preferred marriage
    
    $marriageElt                    = $template['marriage$idmr'];
    $marriageText                   = $marriageElt->outerHTML;
    $template->set('MARRIAGEROWTEMPLATE',   $marriageText);
    
    $marriageEvtElt                 = $template['MarriageRow$rownum'];
    $marriageEvtText                = $marriageEvtElt->outerHTML;
    
    $eventElt                       = $template['EventRow$rownum'];
    $eventText                      = $eventElt->outerHTML;
    
    $sealedElt                      = $template['SealedRow$rownum'];
    $sealedText                     = $sealedElt->outerHTML;
    
    $endedElt                       = $template['EndedRow$rownum'];
    $endedText                      = $endedElt->outerHTML;
    
    $notMarriedElt                  = $template['NotMarriedRow$rownum'];
    $notMarriedText                 = $notMarriedElt->outerHTML;
    
    $noChildrenElt                  = $template['NoChildrenRow$rownum'];
    $noChildrenText                 = $noChildrenElt->outerHTML;
    
    $childElt                       = $template['child$rownum'];
    $childText                      = $childElt->outerHTML;
    $template->set('CHILDROWTEMPLATE',      $childText);
    
    $data                           = '';
    if ($families)
    foreach($families as $index => $tfamily)
    {       // loop through families
        $idmr                       = $tfamily->getIdmr();
    
        $husbName                   = $tfamily->getHusbName();
        $husbid                     = $tfamily['idirhusb'];
        $wifeName                   = $tfamily->getWifeName();
        $wifeid                     = $tfamily['idirwife'];
    
        // information about husband
        $husband                    = $tree->getPerson($husbid);
    
        // information about wife
        $wife                       = $tree->getPerson($wifeid);
    
        $mdateo                     = new LegacyDate($tfamily['mard']);
        $mdate                      = $mdateo->toString();
    
        if (strlen($mdate) == 0)
            $mdate                  = 'Unknown';
    
        $rtemplate                  = new Template($marriageText);
        $rtemplate->set('idmr',             $idmr);
        $rtemplate->set('husbName',         $husbName);
        $rtemplate->set('husbid',           $husbid);
        $rtemplate->set('wifeName',         $wifeName);
        $rtemplate->set('wifeid',           $wifeid);
        $rtemplate->set('mdate',            $mdate);
        $rtemplate->set('lang',             $lang);
        if ($idmr == $idmrpref)
            $rtemplate->set('prefchecked',  'checked="checked"');
        else
            $rtemplate->set('prefchecked',  '');
        $data                   .= $rtemplate->compile();
    }       // loop through families
    $marriageElt->update($data);

    $idmr                   = $family->getIdmr();
    $idms                   = $family['idms'];
    $namerule               = $family['marriednamerule'];
    $selected               = ' selected="selected"';
    $idirhusb               = $family['idirhusb'];
    if ($idirhusb)
    {
        $husb               = $family->getHusband();
        $husbgivenname      = $husb['givenname'];
        if (strlen($husbgivenname) == 0)
        $warn           .= "<p>editMarriages.php: " . __LINE__ .
            " husbgivenname='$husbgivenname'</p>\n";
        $husbgivenname      = str_replace('"','&quot;',$husbgivenname);
        $husbsurname        = $husb['surname'];
        $husbsurname        = str_replace('"','&quot;',$husbsurname);
        $husbbirthsd        = $husb['birthsd'];
        $husborder          = $family['husborder'];
    }
    else
    {
        $husbgivenname      = '';
        if ($childObj)
            $husbsurname    = str_replace('"','&quot;', $surname);
        else
            $husbsurname    = '';
        $husbbirthsd        = -99999999;
        $husborder          = 0;
    }
    $idirwife               = $family['idirwife'];
    if ($idirwife)
    {
        $wife               = $family->getWife();
        $wifegivenname      = $wife['givenname'];
        $wifegivenname      = str_replace('"','&quot;',$wifegivenname);
        $wifesurname        = $wife['surname'];
        $wifesurname        = str_replace('"','&quot;',$wifesurname);
        $wifebirthsd        = $wife['birthsd'];
        $wifeorder          = $family['wifeorder'];
    }
    else
    {
        $wifegivenname      = '';
        $wifesurname        = '';
        $wifebirthsd        = -99999999;
        $wifeorder          = 0;
    }
    $evMar                  = $family->getMarEvent(true);
    $marDate                = $evMar->getDate();
    $marLoc                 = $evMar->getLocation()->toString();
    $marLoc                 = str_replace('"','&quot;',$marLoc);
    $notes                  = $family['notes'];
    $notes                  = trim($notes);
    if (substr($notes, 0, 3) == '<p>');
    {
        if (strpos(substr($notes, 3), '<p>') === false &&
            substr($notes, -4) == '</p>')
            $notes          = substr($notes, 3, strlen($notes) - 7);
    }
    if (substr($notes, -4) == '<br>')
        $notes              = substr($notes, 0, strlen($notes) - 4);


    $template->set('IDMR',              $idmr);
    $template->set('IDIRHUSB',          $idirhusb);
    $template->set('HUSBGIVENNAME',     $husbgivenname);
    $template->set('HUSBSURNAME',       $husbsurname);
    $template->set('HUSBBIRTHSD',       $husbbirthsd);
    $template->set('HUSBORDER',         $husborder);
    $template->set('IDIRWIFE',          $idirwife);
    $template->set('WIFEGIVENNAME',     $wifegivenname);
    $template->set('WIFESURNAME',       $wifesurname);
    $template->set('WIFEBIRTHSD',       $wifebirthsd);
    $template->set('WIFEORDER',         $wifeorder);
    $template->set('MARDATE',           $marDate);
    $template->set('MARLOC',            $marLoc);
    $template->set('NOTES',             $notes);

    // display events
    $data                           = '';
    $rownum                         = 1;
    $events                         = $family->getEvents();
    if ($events->count() == 0)
    {           // always a marriage event even if empty
        $events[]                   = $family->getMarEvent(true);
    }           // always a marriage event even if empty
    $eventTypes                     = $translate['marriageEvents'];
    foreach($events as $ider => $event)
    {           // loop through all events
        $idet                       = $event['idet'];
        $cittype                    = Citation::STYPE_MAREVENT;
        $eventd                     = $event->getDate();
        $eventloc                   = $event->getLocation()->toString();
        $description                = $event['description'];
        $type                       = $eventTypes[$idet];
        switch($idet)
        {       // select template based upon IDET
            case Event::ET_MARRIAGE:
            {
                $templateText       = $marriageEvtText;
                if ($ider == 0)
                    $cittype        = Citation::STYPE_MAR;
                break;
            }

            case Event::ET_LDS_SEALED:
            {
                $templateText       = $sealedText;
                if ($ider == 0)
                    $cittype        = Citation::STYPE_LDSS;
                break;
            }

            case Event::ET_MARRIAGE_END:
            {
                $templateText       = $endedText;
                if ($ider == 0)
                    $cittype        = Citation::STYPE_MAREND;
                break;
            }

            default:
            {
                $templateText       = $eventText;
                break;
            }
        }       // select template based upon IDET

        $data       .= str_replace(array('$rownum',
                                         '$ider',
                                         '$idet',
                                         '$cittype',
                                         '$eventd',
                                         '$eventloc',
                                         '$description',
                                         '$type',
                                         '$temp'), 
                                   array($rownum,
                                         $ider,
                                         $idet,
                                         $cittype,
                                         $eventd,
                                         $eventloc,
                                         $description,
                                         $type,
                                         ''),
                                   $templateText);
        $rownum++;
    }           // loop through all events

    $marEndEvent                    = $family->getMarEndEvent(false);
    if ($marEndEvent)
    {
        $eventd                     = $marEndEvent->getDate();
        $rtemplate                  = new Template($endedText);
        $rtemplate->set('eventd',       $eventd);
        $rtemplate->set('temp',         '');
        $msTypes                    = $translate['msTypes'];
        $idms                       = $family['idms'];
        $rtemplate->set('reason',       $msTypes[$idms]);
        $data                       .= $rtemplate->compile();
    }

    $template->set('EVENTS',            $data);

    // display information
    $msTypes                        = $translate['msTypes'];
    $msArray                        = array();
    $idms                           = $family['idms'];
    foreach($msTypes as $id => $text)
    {
        if ($id == $idms)
            $selected               = "selected='selected'";
        else
            $selected               = '';
        $msArray[]                  = array('IDMS'      => $id,
                                            'TEXT'      => $text,
                                            'SELECTED'  => $selected);
    }
    $template['option$IDMS']->update($msArray);

    // married name rule
    $mnrTypes                       = $translate['MarriedNameRule'];
    $mnrArray                       = array();
    $mnr                            = $family['marriednamerule'];
    foreach($mnrTypes as $id => $text)
    {
        if ($id == $mnr)
            $selected               = "selected='selected'";
        else
            $selected               = '';
        $mnrArray[]                 = array('MNR'      => $id,
                                            'TEXT'      => $text,
                                            'SELECTED'  => $selected);
    }
    $template['option$MNR']->update($mnrArray);

    // display children
    $data                           = '';
    if ($child && !$family->isExisting())
    {
        $family->save();
        $newChild                   = $family->addChild($childObj);
    }
    $children                       = $family->getChildren();
    if ($children->count() > 0)
    {               // at least one child
        $rownum                     = 0;
        foreach($children as $idcr => $child)
        {           // loop through all children
            $cIdir                  = $child['idir'];
            $cPerson                = $child->getPerson();
            if ($cPerson->isExisting())
            {       // valid IDIR
                $gender             = $cPerson['gender'];
                $csurname           = $cPerson['surname'];
                $csurname           = str_replace('"','&quot;',$csurname);
                $cgivenname         = $cPerson['givenname'];
                $cgivenname         = str_replace('"','&quot;',$cgivenname);
                $evBirth            = $cPerson->getBirthEvent(true);
                $birthd             = $evBirth->getDate();
                $birthsd            = $evBirth['eventsd'];
                $evDeath            = $cPerson->getDeathEvent(true);
                $deathd             = $evDeath->getDate();
                $deathsd            = $evDeath['eventsd'];
            }       // valid IDIR
            else
            {       // invalid IDIR
                $cPerson            = null;
                $gender             = 3;
                $csurname           = $surname;
                $csurname           = str_replace('"','&quot;',$csurname);
                $cgivenname         = "Unknown " . $cIdir;
                $cgivenname         = str_replace('"','&quot;',$cgivenname);
                $birthd             = '';
                $birthsd            = 0;
                $deathd             = '';
                $deathsd            = 0;
            }       // invalid IDIR
            if ($gender == 0)
                $genderclass        = 'male';
            else
            if ($gender == 1)
                $genderclass        = 'female';
            else
                $genderclass        = 'unknown';
            $rownum++;

            $rtemplate              = new Template($childText);
            $rtemplate->set('gender',       $genderclass);
            $rtemplate->set('genderclass',  $genderclass);
            $rtemplate->set('surname',      $csurname);
            $rtemplate->set('givenname',    $cgivenname);
            $rtemplate->set('birthd',       $birthd);
            $rtemplate->set('birthsd',      $birthsd);
            $rtemplate->set('deathd',       $deathd);
            $rtemplate->set('deathsd',      $deathsd);
            $rtemplate->set('rownum',       $rownum);
            $rtemplate->set('idir',         $cIdir);
            $rtemplate->set('idcr',         $idcr);
            $rtemplate->set('sex',          $gender);

            $data               .= $rtemplate->compile();
        }           // loop through children
        $childElt->update($data);
    }               // at least one child
    else
    {               // no children
        $childElt->update(null);
    }               // no children

    if ($submit)
    {               // debugging, use submit
        $template['update']->update(null);
    }               // debugging, use submit
    else
    {               // normal, use AJAX
        $template['Submit']->update(null);
    }               // normal, use AJAX
}                   // no error messages
else
{                   // error messages or no instance of Family selected
    $template->set('TITLE',     $title);        // page title
    $warn       .= "<p>No family chosen to display</p>\n";
    $template['indForm']->update(null);
    $template['famForm']->update(null);
}                   // error messages or no instance of Family selected

$template->display();
