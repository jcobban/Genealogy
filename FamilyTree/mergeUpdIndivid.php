<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  mergeUpdIndivid.php                                                 *
 *                                                                      *
 *  Handle a request to merge two individuals in                        *
 *  the Legacy family tree database.                                    *
 *                                                                      *
 *  History:                                                            *
 *      2010/12/26      created                                         *
 *      2010/12/28      set 'updated' and 'updatedtime' to now          *
 *                      update name of merged individual in marriage    *
 *                      records                                         *
 *                      take higher of 'ancinterest' and 'decinterest'  *
 *      2011/01/08      add additional breadcrumbs into header and      *
 *                      trailer                                         *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/01/14      use LegacyIndiv::mergeFrom method               *
 *      2011/04/05      include link to individual in breadcrumbs       *
 *      2012/01/13      change class names                              *
 *      2012/03/15      support B_IDIR field in Births table            *
 *      2012/06/08      update nominal index records to reflect merge   *
 *                      clear up parameter processing                   *
 *      2012/07/26      change genOntario.html to genOntario.php        *
 *      2013/01/19      use setField and save to update database        *
 *      2013/05/31      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/06/04      include link to merged individual in            *
 *                      header/footer                                   *
 *      2013/06/11      enclose diagnostic info from record saves       *
 *                      in <p>                                          *
 *                      correct record key values in some calls to      *
 *                      logSqlUpdate                                    *
 *      2013/07/19      get IDCR for child record being deleted as a    *
 *                      duplicate within a family BEFORE deleting the   *
 *                      record.                                         *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2013/08/15      fix a couple of undefined variable errors       *   
 *      2013/11/22      field IDLRChris erroneously converted to        *
 *                      LegacyDate                                      *
 *      2014/03/10      use CSS for layout instead of tables            *
 *      2014/03/21      use LegacyAltName::deleteAltNames to delete     *
 *                      alternate names used by second individual       *
 *      2014/03/26      make sure blog entries for the second individual*
 *                      are not lost                                    *
 *      2014/04/08      class LegacyAltName renamed to LegacyName       *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/05/08      handle exceptions thrown in LegacIndiv::delete  *
 *      2014/08/07      use Citation::updateCitations                   *
 *      2014/09/22      include link to individual in header if given   *
 *                      name and no surname                             *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/10/30      events moved from tblIR to tblER                *
 *                      use Event::updateEvents to update tblER         *
 *      2014/12/01      print trace info in body                        *
 *      2014/12/19      method LegacyIndiv::getFamilies returns         *
 *                      associative array indexed by IDMR               *
 *      2015/01/14      birth date was deleted from merged individual   *
 *      2015/02/01      clear preferred flag in any events copied       *
 *                      from second individual                          *
 *      2015/02/10      if invoked from an instance of editIndivid.php  *
 *                      then update fields in that edit window          *
 *      2015/03/21      copy citations from 2nd individual's events     *
 *      2015/06/06      pass list of moved events to editIndivid        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/01/19      add id to debug trace                           *
 *      2016/04/28      improve merging of citations to birth and death *
 *                      events, which did not work when merging an old  *
 *                      style simulated event (IDER=0) with a real event*
 *      2017/01/17      use method set in place of setField             *
 *      2017/03/18      update given name and surname in caller         *
 *                      use preferred parameters for new LegacyIndiv    *
 *      2017/07/27      class LegacyCitation renamed to class Citation  *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/18      class LegacyName renamed to class Name          *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/29      use class RecordSet to update birth, death,     *
 *                      and marriage transcriptions to new IDIR         *
 *      2018/11/19      change Helpen.html to Helpen.html               *
 *      2019/09/16      use class FtTemplate for output                 *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/05/09      set all fields in parameters to feedbackFunc    *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *		2021/03/17      handle missing birth event for second Person    *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// control debugging output
$lang                   = 'en';
$surname                = '';
$nameuri                = '';   // defaults for trail of breadcrumbs
$prefix                 = '';
$idir1                  = null;
$idir1text              = null;
$person1        		= null;
$idir2                  = null;
$idir2text              = null;
$person2        		= null;
$evBirth2       		= null;
$evChris2       		= null;
$evDeath2       		= null;
$evBuried2      		= null;
$isOwner        		= null;
$useSurname2            = false;
$useGivenName2          = false;
$useBthDate2            = false;
$useBthLoc2             = false;
$useCrsDate2            = false;
$useCrsLoc2             = false;
$useDthDate2            = false;
$useDthLoc2             = false;
$useBurDate2            = false;
$useBurLoc2             = false;
$isOwner                = canUser('edit');

if (isset($_POST) && count($_POST) > 0)
{                   // invoked by method=post
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    // process parameters
    foreach($_POST as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch($key)
        {           // act on specific parameters
            case 'idir1':
            {
                if (ctype_digit($value))
                {
	                $idir1          = $value;
	                $person1        = new Person(array('idir' => $idir1));
	                $evBirth1       = $person1->getBirthEvent(true);
	                $evChris1       = $person1->getChristeningEvent(true);
	                $evDeath1       = $person1->getDeathEvent(true);
	                $evBuried1      = $person1->getBuriedEvent(true);
                    $isOwner        = $isOwner && $person1->isOwner();
                }
                else
                    $idir1text      = htmlspecialchars($value);
                break;
            }       // idir1

            case 'idir2':
            {
                if (ctype_digit($value))
                {
	                $idir2          = $value;
	                $person2        = new Person(array('idir' => $idir2));
	                // only create events if they are recorded
	                $evBirth2       = $person2->getBirthEvent(false);
	                $evChris2       = $person2->getChristeningEvent(false);
	                $evDeath2       = $person2->getDeathEvent(false);
	                $evBuried2      = $person2->getBuriedEvent(false);
                    $isOwner        = $isOwner && $person2->isOwner();
                }
                else
                    $idir2text      = htmlspecialchars($value);
                break;
            }       // idir2

            case 'SurnameCb1':
            {
                $useSurname2    = false;
                break;
            }       // Surname Checkbox 1

            case 'SurnameCb2':
            {
                $useSurname2    = true;
                break;
            }       // Surname Checkbox 2

            case 'GivenNameCb1':
            {
                $useGivenName2  = false;
                break;
            }       // GivenName Checkbox 1

            case 'GivenNameCb2':
            {
                $useGivenName2  = true;
                break;
            }       // GivenName Checkbox 2

            case 'BthDateCb1':
            {
                $useBthDate2    = false;
                break;
            }       // Birth Date Checkbox 1

            case 'BthDateCb2':
            {
                $useBthDate2    = true;
                break;
            }       // Birth Date Checkbox 2

            case 'BthLocCb1':
            {
                $useBthLoc2     = false;
                break;
            }       // Birth Loc Checkbox 1

            case 'BthLocCb2':
            {
                $useBthLoc2     = true;
                break;
            }       // Birth Loc Checkbox 2

            case 'CrsDateCb1':
            {
                $useCrsDate2    = false;
                break;
            }       // Christening Date Checkbox 1

            case 'CrsDateCb2':
            {
                $useCrsDate2    = true;
                break;
            }       // Christening Date Checkbox 2

            case 'CrsLocCb1':
            {
                $useCrsLoc2     = false;
                break;
            }       // Christening Loc Checkbox 1

            case 'CrsLocCb2':
            {
                $useCrsLoc2     = true;
                break;
            }       // Christening Loc Checkbox 2

            case 'DthDateCb1':
            {
                $useDthDate2    = false;
                break;
            }       // Death Date Checkbox 1

            case 'DthDateCb2':
            {
                $useDthDate2    = true;
                break;
            }       // Death Date Checkbox 2

            case 'DthLocCb1':
            {
                $useDthLoc2     = false;
                break;
            }       // Death Loc Checkbox 1

            case 'DthLocCb2':
            {
                $useDthLoc2     = true;
                break;
            }       // Death Loc Checkbox 2

            case 'BurDateCb1':
            {
                $useBurDate2    = false;
                break;
            }       // Burial Date Checkbox 1

            case 'BurDateCb2':
            {
                $useBurDate2    = true;
                break;
            }       // Burial Date Checkbox 2

            case 'BurLocCb1':
            {
                $useBurLoc2     = false;
                break;
            }       // Burial Loc Checkbox 1

            case 'BurLocCb2':
            {
                $useBurLoc2     = true;
                break;
            }       // Burial Loc Checkbox 2

            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
        }           // act on specific parameters
    }               // loop through all parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}                   // invoked by method=post

$template                       = new FtTemplate("mergeUpdIndivid$lang.html");
$translate                      = $template->getTranslate();
$t                              = $translate['tranTab'];
$template->set('LANG',          $lang);

// get the identifiers of the two individuals
if ($idir1 !== null && $idir2 !== null && $idir1 != $idir2)
{                   // IDIRs of two individuals specified
    if ($isOwner)
    {               // current user can edit both individuals
        $given                  = $person1->getGivenName();
        $surname                = $person1->getSurname();
        $nameuri                = rawurlencode($surname . ', ' .  $given);
        if (strlen($surname) == 0)
            $prefix             = '';
        else
        if (substr($surname,0,2) == 'Mc')
            $prefix             = 'Mc';
        else
            $prefix             = substr($surname,0,1);

        $template->set('GIVENNAME', $given);
        $template->set('SURNAME',   $surname);
        $template->set('NAMEURI',   $nameuri);
        $template->set('PREFIX',    $prefix);
        $template->set('IDIR1',     $idir1);
        $template->set('IDIR2',     $idir2);
        $template->set('NAME1',     $person1->getName());
        $template->set('NAME2',     $person2->getName());
        $template['titleFailed']->update(null);
    }               // current user can edit both individuals
    else
    {               // user not authorized
        $template->set('NAMEURI',   '');
        $template['titleNames']->update(null);
        $template['crumbsSurname']->update(null);
        $msg    .= 'You are not authorized to update these individuals. ';
    }               // user not authorized
}                   // parameters OK
else
{                   // missing parameter
    $template->set('NAMEURI',   '');
    $template['titleNames']->update(null);
    $template['crumbsSurname']->update(null);
    if (is_string($idir1text))
        $msg    .= "Invalid value for IDIR1=$idir1text. ";
    if (is_string($idir2text))
        $msg    .= "Invalid value for IDIR2=$idir1text. ";
    if (is_int($idir1) && is_int($idir2))
    {   
        if ($idir1 == $idir2)
            $msg    .= "Cannot merge Person with IDIR1=$idir1 with itself. ";
    }
    else
        $msg        .= 'Missing mandatory parameters. ';
}                   // missing parameter

if (strlen($msg) == 0)
{                   // valid parameters
    ob_start();

    $priName                    = $person1->getPriName();

    if ($person2['gender'] == Person::FEMALE)
    {               // female
        $midirfld               = 'IDIRWife';
        $sidirfld               = 'IDIRHusb';
        $msurnfld               = 'WifeSurname';
        $mgivnfld               = 'WifeGivenName';
    }               // female
    else
    {               // male
        $midirfld               = 'IDIRHusb';
        $sidirfld               = 'IDIRWife';
        $msurnfld               = 'HusbSurname';
        $mgivnfld               = 'HusbGivenName';
    }               // male
    $template->set('midirfld',          $midirfld);
    $template->set('SIDIRFLD',          $sidirfld);
    $template->set('MSURNFLD',          $msurnfld);
    $template->set('MGIVNFLD',          $mgivnfld);

    if ($useSurname2)
    {               // take surname from second
        $priName['surname']             = $person2['surname'];
        $template->set('NEWSURNAME',    $person2['surname']);
    }               // take surname from second
    else
        $template['replaceSurname']->update(null);

    if ($useGivenName2)
    {               // take given name from second
        $priName['givenname']           = $person2['givenName'];
        $template->set('NEWGIVENNAME',  $person2['givenName']);
    }               // take given name from second
    else
        $template['replaceGivenname']->update(null);

    $template->set('BIRTHDATE2',        $_POST['BirthDate2']);
    $template->set('BIRTHLOCATION2',    $_POST['BirthLocation2']);
    $template->set('CHRISDATE2',        $_POST['ChrisDate2']);
    $template->set('CHRISLOCATION2',    $_POST['ChrisLocation2']);
    $template->set('DEATHDATE2',        $_POST['DeathDate2']);
    $template->set('DEATHLOCATION2',    $_POST['DeathLocation2']);
    $burieddate2                        = $_POST['BuriedDate2'];
    $buriedlocation2                    = $_POST['BuriedLocation2'];

    // merge birth information
    // the following is to retain the true event when we try to
    // merge a true event with a simulated event (IDER=0)
    if ($evBirth1['ider'] == 0 && $evBirth2['ider'] > 0)
    {       // second is instance of Event
       $newEvent    = $evBirth2;
       $newEvent['idir']            = $idir1;
       $oldEvent    = $evBirth1;
    }       // second is instance of Event
    else
    {       // first is instance of Event or neither
       $newEvent    = $evBirth1;
       $oldEvent    = $evBirth2;
    }       // first is instance of Event or neither

    $birthDate2     = new LegacyDate($evBirth2['eventd']);
    if ($useBthDate2)
    {       // take birth date from second
        $newEvent->set('eventd',
                       $birthDate2);
    }       // take birth date from second
    else
    {       // take birth date from first
        $newEvent->set('eventd',
                       new LegacyDate($evBirth1['eventd']));
        $template['replaceBirthDate']->update(null);
    }       // take birth date from first

    if ($useBthLoc2)
    {       // take birth location from second
        $newEvent->set('idlrevent',
                       $evBirth2['idlrevent']);
    }       // take birth location from second
    else
    {       // take birth location from first
        $newEvent->set('idlrevent',
                       $evBirth1['idlrevent']);
        $template['replaceBirthLoc']->update(null);
    }       // take birth location from first

    // copy citations from second birth date
    if ($oldEvent)
    {
	    $citations      = $oldEvent->getCitations();
	    if (count($citations) > 0)
	    {       // have citations to copy
	        $template->set('BIRTHCITATIONSCOUNT', count($citations));
	        $newEvent->addCitations($citations);
	    }       // have citations to copy
	    else
	        $template['birthCount']->update(null);
    }
    $template->set('BIRTHD',        $newEvent->getDate(9999, $t));
    $template->set('BIRTHLOC',      $newEvent->getLocation()->getName());

    // cleanup
    if ($oldEvent)
    {
        $oldEvent->delete('p');
        $oldEvent               = null;
        $evBirth2               = null;
        $evBirth1               = $newEvent;
    }

    // merge christening information
    if ($useCrsDate2)
    {   // take christening date from second
        $evChris1->set('eventd',
                       new LegacyDate($evChris2['eventd']));
    }   // take christening date from second
    else
    {
        $template['replaceChrisDate']->update(null);
    }

    if ($useCrsLoc2)
    {   // take christening location from second
        $evChris1->set('idlrevent',
                       $evChris2['idlrevent']);
    }   // take christening location from second
    else
    {
        $template['replaceChrisLoc']->update(null);
    }

    // copy citations from second christening date
    if ($evChris2)
    {
	    $citations          = $evChris2->getCitations();
	    if (count($citations) > 0)
	    {       // have citations to copy
	        $template->set('CHRISCITATIONSCOUNT', count($citations));
	        $evChris1->addCitations($citations);
	    }       // have citations to copy
	    else
	        $template['chrisCount']->update(null);
    }
    $template->set('CHRISD',        $evChris1->getDate(9999, $t));
    $template->set('CHRISLOC',      $evChris1->getLocation()->getName());

    if ($evChris2)
    {
        $evChris2->delete('p');
        $evChris2               = null;
    }

    // merge death information

    // the following is to retain the true event when we try to
    // merge a true event with a simulated event (IDER=0)
    if ($evDeath1['ider'] == 0 && $evDeath2['ider'] > 0)
    {
       $newEvent                = $evDeath2;
       $newEvent->set('idir', $evDeath1['idir']);
       $oldEvent                = $evDeath1;
    }
    else
    {
       $newEvent                = $evDeath1;
       $oldEvent                = $evDeath2;
    }

    if ($useDthDate2)
    {   // take death date from second
        $newEvent->set('eventd',
                        new LegacyDate($evDeath2['eventd']));
    }   // take death date from second
    else
    {       // take birth date from first
        $newEvent->set('eventd',
            new LegacyDate($evDeath1['eventd']));
        $template['replaceDeathDate']->update(null);
    }       // take birth date from first
    if ($useDthLoc2)
    {       // take death location from second
        $newEvent->set('idlrevent',
                        $evDeath2['idlrevent']);
    }       // take death location from second
    else
    {       // take death location from first
        $newEvent->set('idlrevent',
                        $evDeath1['idlrevent']);
    }       // take death location from first

    // copy citations from second death date
    $citations      = $oldEvent->getCitations();
    if (count($citations) > 0)
    {       // have citations to copy
        $template->set('DEATHCITATIONSCOUNT',   count($citations));
        $newEvent->addCitations($citations);
    }       // have citations to copy
    else
        $template['deathCount']->update(null);
    $template->set('DEATHD',        $newEvent->getDate(9999, $t));
    $template->set('DEATHLOC',      $newEvent->getLocation()->getName());

    if ($oldEvent)
    {
        $oldEvent->delete('p');
        $oldEvent               = null;
        $evDeath2               = null;
        $evDeath1               = $newEvent;
    }

    // merge burial/cremation event
    if ($evBuried2)
    {
        if ($useBurDate2)
        {   // take burial date from second
            $evBuried1->set('eventd',
                new LegacyDate($evBuried2['eventd']));
            $template['replaceBurialDate']->update(array('burieddate2' => $burieddate2));
        }   // take burial date from second
        else
            $template['replaceBurialDate']->update(null);
        if ($useBurLoc2)
        {   // take burial location from second
            $evBuried1->set('idlrevent',
                            $evBuried2['idlrevent']);
            $template['replaceBurialLoc']->update(array('buriedlocation2' => $buriedlocation2));
        }   // take burial location from second
        else
            $template['replaceBurialLoc']->update(null);
    
        // copy citations from second burial date
        $citations      = $evBuried2->getCitations();
        if (count($citations) > 0)
        {       // have citations to copy
            $template['burialCount']->update(array('count'  => count($citations)));
            $evBuried1->addCitations($citations);
        }       // have citations to copy
        else
            $template['burialCount']->update(null);

        $evBuried2->delete('p');
        $evBuried2              = null;
    }
    else
    {
        $template['replaceBurialDate']->update(null);
        $template['replaceBurialLoc']->update(null);
    }
    $template->set('BURIALD',        $evBuried1->getDate(9999, $t));
    $template->set('BURIALLOC',      $evBuried1->getLocation()->getName());

    // check all other fields in the main record
    $retval         = $person1->mergeFrom($person2);

    $person1->save(false);
    if (strlen($evBirth1->getDate()) > 0 ||
        $evBirth1['idlrevent'] > 1)
        $evBirth1->save('p');
    if (strlen($evChris1->getDate()) > 0 ||
        $evChris1['idlrevent'] > 1)
        $evChris1->save('p');
    if (strlen($evDeath1->getDate()) > 0 ||
        $evDeath1['idlrevent'] > 1)
        $evDeath1->save('p');
    if (strlen($evBuried1->getDate()) > 0 ||
        $evBuried1['idlrevent'] > 1)
        $evBuried1->save('p');

    // move event records from individual 2 to individual 1 
    $parms                      = array('idir'      => $idir2,
                                        'idtype'    => 0);
    $movedEvents                = new RecordSet('Events', $parms);

    $setparms                   = array('idir'      => $idir1,
                                        'preferred' => 0);
    $eventSet                   = new RecordSet('Events', $parms);
    $result                     = $eventSet->update($setparms,
                                                    false,
                                                    false);

    if ($result > 0)
        $template->set('MOVEDEVENTCOUNT',       $result);
    else
        $template['eventCount']->update(null);

    // delete name index entries for deleted individual
    $names          = new RecordSet('Names', array('idir' => $idir2));
    $result         = $names->delete('p');
    if ($result > 0)
        $template->set('DELETEDNAMES',          $result);
    else
    if ($template['deletedName'])
        $template['deletedName']->update(null);

    // Update nominal index records for merged individual. 
    $priName->save('p');

    // check for marriages of the second individual to associate
    // with the first individual
    $families1                  = $person1->getFamilies();
    $families2                  = $person2->getFamilies();
    $familiesMergeParms         = array();
    $parmsUpd                   = array();
    foreach($families2 as $idmr2 => $family2)
    {       // loop through families
        $keepBoth               = true;
        foreach($families1 as $idmr1 => $family1)
        {       // search for duplicate marriage
            if ($family1->get($midirfld) == $idir1 &&
                $family1->get($sidirfld) == $family2->get($sidirfld))
            {   // merger will create duplicate family
                $familiesMergeParms[]   = array('idmr1'     => $idmr1,
                                                'idmr2'     => $idmr2);  
                $family1->merge($family2);
                $keepBoth       = false;
                break;  // leave loop
            }   // merger will create duplicate family
        }       // search for duplicate marriage

        if ($keepBoth)
        {       // retain both families
            $family2->set($midirfld, $idir1);
            $family2->save('p');
            $parmsUpd[]         = array('idmr2'         => $idmr2,
                                        'midirfld'      => $midirfld,
                                        'idir1'         => $idir1);
        }       // retain both families
        else
        {       // delete duplicate family
            $family2->delete();
        }       // delete duplicate family
    }       // loop through families
    $template['updateFamily2']->update($parmsUpd);
    $template['mergeFamilies']->update($familiesMergeParms);

    // ensure the the name of the merged individual is adjusted
    // in all marriages.  The array $families now includes the
    // marriages added from the second individual
    // refresh the local copy of the individual record
    // so that Person::getFamilies does not use local copy
    $person1                    = new Person(array('idir' => $idir1));
    $families                   = $person1->getFamilies();
    $givenname                  = $person1->getGivenName();
    $surname                    = $person1->getSurname();
    $updParms                   = array();
    foreach($families as $idmr => $family)
    {       // loop through families
        $family->setName($person1);
        $family->save('p');
        $updParms[]             = array('idmr'      => $idmr,
                                        'msurnfld'  => $msurnfld,
                                        'mgivnfld'  => $mgivnfld,
                                        'surname'   => $surname,
                                        'givenname' => $givenname);
    }       // loop through families
    $template['famNameUpdate']->update($updParms);

    // check for child records to update
    $child                      = $person2->getChild(); // RecordSet of Child
    $delParms                   = array();
    $updParms                   = array();
    foreach($child as $idcr => $childr)
    {       // loop through child records for second individual
        $cfamily                = $childr->getFamily();
        $duplicate              = $cfamily->getChildByIdir($idir1);
        if ($duplicate)
        {       // there is already a child with the new IDIR
            try {
                $childr->delete(false);
                $delParms[]     = array('idcr'      => $idcr);
            } catch(Exception $e) {
                $warn   .= "<p>\$childr->delete(false) failed</p>\n";
            }   // ignore exception
        }       // there is already a child with the new IDIR
        else
        {       // change child record to point at new IDIR
            $childr->set('idir', $idir1);
            $childr->save('p');
            $updParms[]         = array('idcr'      => $idcr,
                                        'idir1'     => $idir1);
        }       // change child record to point at new IDIR
    }       // loop through child records for second individual
    $template['childDeleted']->update($delParms);
    $template['childUpdated']->update($updParms);

    // update citation records to new IDIR
    $citations  = new CitationSet(array('idir'  => $idir2));
    $result     = $citations->update(array('idime'  => $idir1),
                                 false);
    if ($result > 0)
    {       // at least 1 record updated
        $template['sourceCitationsUpdated']->update(array('RESULT'  => $result,
                                                          'idir1'   => $idir1));
    }       // at least 1 record updated
    else
        $template['sourceCitationsUpdated']->update(null);

    // check for Birth registration records to update
    $birthSet               = new RecordSet('Births',
                                            array('idir'    => $idir2));
    $result                 = $birthSet->update(array('idir'    => $idir1));
    if ($result == 0)
        $template['birthRegUpdated']->update(null);

    // check for Death registration records to update
    $deathSet               = new RecordSet('Deaths',
                                            array('idir'    => $idir2));
    $result                 = $deathSet->update(array('idir'    => $idir1));
    if ($result == 0)
        $template['deathRegUpdated']->update(null);

    // check for Marriage registration records to update
    $marrSet                = new RecordSet('MarriageIndi',
                                            array('idir'    => $idir2));
    $result                 = $marrSet->update(array('idir' => $idir1));
    if ($result == 0)
        $template['marrRegUpdated']->update(null);

    // merge blog records for the second individual
    $blogparms              = array('table'     => 'tblIR',
                                    'keyvalue'  => $idir2);
    $blogSet                = new RecordSet('Blogs', $blogparms);
    if ($blogSet->count() > 0)
    {
        $template['moveBlogs']->update(array('BLOGSETCOUNT',    $blogSet->count()));

        foreach($blogSet as $blid => $blog)
        {       // loop through all blogs
            $blog->set('keyvalue', $idir1);
            $blog->save(false);
        }       // loop through all blogs
    }
    else
        $template['moveBlogs']->update(null);

    // delete the duplicate record from tblIR
    $person2->resetFamilies();
    $person2->resetParents();
    try {
        $person2->delete("p");
    } catch (Exception $e) {
        $warn .= "<p>Delete Person failed " . $e->getMessage() . "</p>\n";
    }       // catch

    $text                   = ob_get_clean();
    $template->set('TEXT',          $text);
}       // OK to update

showTrace();

// get info on common events of the merged individual
$evBirth1                   = $person1->getBirthEvent(true);
$birthd                     = $evBirth1->getDate();
$birthloc                   = htmlspecialchars(
                                    $evBirth1->getLocation()->getName(),
                                                   ENT_QUOTES);
$evChris1                   = $person1->getChristeningEvent(true);
$chrisd                     = $evChris1->getDate();
$chrisloc                   = htmlspecialchars(
                                    $evChris1->getLocation()->getName(),
                                                   ENT_QUOTES);
$evDeath1                   = $person1->getDeathEvent(true);
$deathd                     = $evDeath1->getDate();
$evBuried1                  = $person1->getBuriedEvent(true);
$buriald                    = $evBuried1->getDate();
$givenname                  = $person1->getGivenName();
$surname                    = $person1->getSurname();
$movedElt                   = $template['movedevent'];
$movedEltHtml               = $movedElt->outerHTML;
$data                       = '';

foreach($movedEvents as $ider => $event)
{
    $date                   = $event->getDate();
    $location               = $event->getLocation();
    $idet                   = $event['idet'];
    $description            = $event['description'];
    $cittype                = $event->getCitType();
    $etemplate              = new \Templating\Template($movedEltHtml);
    $etemplate->set('IDER',              $ider);
    $etemplate->set('DATE',              $date);
    $etemplate->set('IDET',              $idet);
    $etemplate->set('CITTYPE',           $cittype);
    $etemplate->set('DESCRIPTION',       $description);
    $etemplate->set('LOCATION',          $location->toString());
    $data                   .= $etemplate->compile();
}
$movedElt->update($data);

$template->display();
