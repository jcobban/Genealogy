<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateMarriageJSON.php                                              *
 *                                                                      *
 *  Handle a request to update an individual marriage in                *
 *  the Legacy family tree database.                                    *
 *                                                                      *
 *  The following parameters must be passed using the POST method.      *
 *                                                                      *
 *      idmr            unique numeric key of marriage record           *
 *                                                                      *
 *  The following parameters may be passed using the POST method.       *
 *                                                                      *
 *      treename        name of the database subdivision                *
 *      IDIRHusb        unique numeric key of husband                   *
 *      HusbIdir        unique numeric key of husband (old form)        *
 *      HusbOrder       sort order from husband's point of view         *
 *      HusbPrefMar     preferred marriage                              *
 *      HusbGivenName   given name of husband                           *
 *      HusbSurname     surname of husband                              *
 *      HusbMarrSurname married surname of husband                      *
 *      HusbBirthSD     sort date                                       *
 *      IDIRWife        unique numeric key of wife                      *
 *      WifeIdir        unique numeric key of wife (old form)           *
 *      WifeOrder       sort order from wife's point of view            *
 *      WifePrefMar     preferred marriage                              *
 *      WifeGivenName   given name of wife                              *
 *      WifeSurname     surname of wife                                 *
 *      WifeMarrSurname married surname of husband                      *
 *      WifeBirthSD     sort date                                       *
 *      MarriedNameRule is wife known by husband's name?                *
 *      MarD            date of marriage in text form                   *
 *      MarLoc          location of marriage                            *
 *      Notes           textual notes                                   *
 *      NotMarried      never married indicator                         *
 *      NoChildren      no children indicator                           *
 *      SealD           date of LDS sealing in text form                *
 *      IDTRSeal        id of temple of sealing                         *
 *      TrSeal          id of temple of sealing (old form)              *
 *      SealLoc         name of temple of sealing (see Temple)          *
 *      IDMS            marriage status                                 *
 *      in general any valid field name                                 *
 *                                                                      *
 *  History as updateMarriageXml.php:                                   *
 *      2010/09/19      ensure new IDLR not unnecessarily generated     *
 *      2010/09/25      Check error on $result, not $connection after   *
 *                      query/exec                                      *
 *      2010/10/22      use method='post'                               *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/10/30      add support for marriage status                 *
 *      2011/06/12      correct error on setting sort date for Sealing  *
 *      2011/11/26      update tblNX with alternate married name[s]     *
 *      2012/01/13      change class names                              *
 *                      renamed to updateMarriageXml.php                *
 *      2012/08/27      only update fields in the database if the       *
 *                      script specifies a different value from the     *
 *                      database copy automatically create a spouse     *
 *                      if the name has changed but there was no        *
 *                      current spouse IDIR value.                      *
 *                      These two changes permit changing the edit      *
 *                      marriage dialog to allow changing the name      *
 *                      fields                                          *
 *      2012/10/16      use setField and save methods to update         *
 *                      database records                                *
 *      2012/11/04      date functionality moved into class LegacyFamily*
 *      2012/11/12      avoid use of && in SQL commands                 *
 *                      update database only through save method        *
 *                      if records                                      *
 *      2012/11/27      handle exception thrown by LegacyFamily::setName*
 *      2013/01/25      only create new individuals with non-empty names*
 *      2013/02/12      do not use LegacyIndiv::getIdir to determine    *
 *                      whether or not the record is already in the     *
 *                      database                                        *
 *      2013/03/14      LegacyLocation constructor no longer saves      *
 *      2013/03/23      perform all manipulation of children here,      *
 *                      rather than through invocation of               *
 *                      individual scripts                              *
 *      2013/04/02      avoid creating empty spouse                     *
 *      2013/05/20      act on NotMarried and NoChildren fields         *
 *      2013/06/01      remove use of deprecated interfaces             *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *                      cannot set WifeBirthSD using setField           *
 *      2014/03/06      implement ability to add children using         *
 *                      new rows in children table without requiring    *
 *                      invocation of editIndivid.php                   *
 *      2014/03/18      ensure IDIR set for children                    *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/10/25      handle CIDIR field value 'undefined'            *
 *      2015/07/01      escape special chars in parameter values        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/12      support treename                                *
 *      2017/03/18      husband is optional in family                   *
 *      2017/03/19      use preferred parameters to new LegacyIndiv     *
 *                      use preferred parameters to new LegacyFamily    *
 *      2017/09/02      class LegacyTemple renamed to class Temple      *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2018/05/16      return more information about children added    *
 *                      to the family                                   *
 *      2019/08/05      rewritten so that updates performed after       *
 *                      all information collected from parameters       *
 *      2019/10/01      create Person record and associated Child       *
 *                      record for a new child only just before next    *
 *                      child or end of children                        *
 *      2019/10/28      add information to response to adding a child   *
 *      2019/11/11      do not create instance of Person for wife       *
 *                      if the name is empty                            *
 *      2019/12/21      ensure birth and death dates of children        *
 *                      are updated                                     *
 *      2020/03/18      add support for generic events                  *
 *      2022/08/17      correct providing updated IDIR and IDCR         *
 *                      when adding children                            * 
 *                                                                      *
 *  History as updateMarriageJSON.php:                                  *
 *      2022/08/20      migrated to returning JSON                      *
 *      2022/10/30      update death date for new child                 *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
header("Content-Type: application/json");
$retval                         = array();

// user must be authorized to edit the database
if (!canUser('edit'))
{       // the user not authorized
    $msg            .= 'User not authorized to update the database. ';
}       // the user not authorized

try {

// process parameters
$idmr                           = null;
$family                         = null;     // instace of Family
$husb                           = null;     // instance of Person
$wife                           = null;     // instance of Person
$idirhusb                       = null;     // IDIR of Person
$idirwife                       = null;     // IDIR of Person
$child                          = null;     // instance of Person
$childr                         = null;     // instance of Child
$childOrder                     = 0;
$treename                       = '';
$wifemarrsurname                = null;
$idet                           = null;
$events                         = array();
$parms                          = array();
$actions                        = array();

if (isset($_GET) && count($_GET) > 0)
{                           // invoked by method=get
    $retval['parms']            = $_GET;
}                           // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{                           // invoked by method=post
    $retval['parms']            = $_POST;
    foreach($_POST as $key => $value)
    {                       // loop through parameters

        $namePattern            = "/([a-zA-Z]+)([0-9]*)/";
        $rgResult               = preg_match($namePattern, $key, $matches);
        if ($rgResult === 1)
        {                   // pattern match
            $field              = strtolower($matches[1]);
            $id                 = $matches[2];
        }                   // pattern match
        else
        {                   // no match (should not happen)
            $field              = strtolower($key);
            $id                 = '';
        }                   // no match

        switch($field)
        {                   // act on each parameter
            case 'idmr':
            {               // key of instance of Family to update
                if (is_string($value) && strlen($value) > 0 &&
                    ctype_digit($value))
                {           // idmr specified and numeric
                    $idmr           = intval($value);
                    $family         = new Family(array('idmr' => $idmr));

                    // checkboxes are special in that if they are unchecked
                    // the field is not passed in the parameters,
                    // so the absence of the field must be treated as not set
                    $family['notmarried']       = 0;
                    $family['nochildren']       = 0;
                }           // idmr specified and numeric
                else
                {
                    $msg            .= "Value of idmr='$idmr' must be numeric key. ";
                }
                break;
            }               // key of instance of Family to update

            case 'idirhusb':
            case 'husbidir':
            {               // key of instance of Person for male partner
                $idirhusb                   = (int)$value;
                $family['idirhusb']         = $idirhusb;
                if ($idirhusb > 0)
                {           // family has a husband
                    $husb               = new Person(array('idir' => $idirhusb));
                    if ($husb->isExisting())
                        $gender             = $husb->getGender();
                    else
                    {
                        $gender             = Person::MALE;
                        $husb->setTreename($treename);
                        $husb['sex']        = $gender;
                        $husb->set('gender', $gender);
                    }
                    if ($gender == Person::MALE)
                        $family['idirhusb'] = $idirhusb;
                }           // family has a husband
                else
                {           // a husband is optional
                    $family['idirhusb']     = $idirhusb;
                    $husb                   = null;
                }           // a husband is optional
                break;
            }               // key of instance of Person for male partner

            case 'treename':
            {
                $treename           = $value;
                break;
            }

            case 'husbgivenname':
            {               // male partner given name
                $family['husbgivenname']    = $value;
                if ($husb)
                    $husb['givenname']      = $value;
                else
                if ($value != '')
                {
                    $husb                   = new Person();
                    $husb->setGender(Person::MALE);
                    $husb['givenname']      = $value;
                }
                break;
            }               // male partner given name

            case 'husbsurname':
            {               // male partner family name
                $family['husbsurname']      = $value;
                if ($husb)
                {
                    if ($value != '?')
                        $husb['surname']    = $value;
                }
                else
                if ($value != '' && $value != '?')
                {
                    $husb           = new Person();
                    $husb->setGender(Person::MALE);
                    $husb['surname']        = $value;
                }
                break;
            }               // male partner family name

            case 'husbmarrsurname':
            {
                $family['husbmarrsurname']  = $value;
                break;
            }

            case 'husborder':
            {
                $family['husborder']        = $value;
                break;
            }

            case 'husbprefmar':
            {
                $family['husbprefmar']      = $value;
                break;
            }

            case 'husbbirthsd':
            {
                break;
            }

            case 'idirwife':
            case 'wifeidir':    // old form
            {
                $idirwife                       = (int)$value;
                if ($idirwife > 0)
                {       // family has a wife
                    $wife       = new Person(array('idir' => $idirwife));
                    $gender                     = $wife->getGender();
                    if ($gender == Person::FEMALE)
                    {
                        $family['idirwife']     = $idirwife;
                    }
                }       // family has a wife
                else
                {       // create new wife
                    // for genealogical purposes a family must have a female
                    // partner because a family is defined in terms of children
                    $wife                       = new Person();
                    $wife->setGender(Person::FEMALE);
                    $wife->setTreeName($treename);
                }       // create new wife
                break;
            }

            case 'wifegivenname':
            {
                $family['wifegivenname']        = $value;
                if ($wife)
                {
                    $wife['givenname']          = $value;
                }
                else
                if ($value != '')
                {
                    $wife                       = new Person();
                    $wife->setGender(Person::FEMALE);
                    $wife['givenname']          = $value;
                }
                break;
            }

            case 'wifesurname':
            {
                if ($value != '?')
                {
                    $family['wifesurname']      = $value;
                    if ($wife)
                        $wife['surname']        = $value;
                }
                break;
            }

            case 'wifemarrsurname':
            {
                if (strlen($value) > 0)
                {
                    $family['wifemarrsurname']      = $value;
                    if ($value != $family->get('husbsurname')) 
                        $wifemarrsurname            = $value;
                }
                break;
            }

            case 'wifeorder':
            {
                $family['wifeorder']        = $value;
                break;
            }

            case 'wifeprefmar':
            {
                $family['wifeprefmar']      = $value;
                break;
            }

            case 'wifebirthsd':
            {
                // cannot change from this dialog
                break;
            }

            case 'marriednamerule':
            {
                $family['marriednamerule']  = $value;
                break;
            }

            case 'mard':
            {       // date of marriage
                if ($family['wifegivenname'] === '' &&
                    $family['wifesurname'] === '')
                    $wife                   = null;
                if ($wife)
                {               // may be new wife or wife's name may be changed 
                    $wife->save(false);
                    $lastSql                = $wife->getLastSqlCmd();
                    if (strlen($lastSql) > 0)
                    {
                        $ttagname           = "mard" . __LINE__;
                        $actions[$ttagname] = $lastSql;
                    }
                    $idirwife               = $wife['idir'];
                    $family['idirwife']     = $idirwife;
                }               // may be new wife or wife's name may be changed 

                // this call also sets field 'marsd'
                $family['mard']             = $value;
                break;
            }       // date of marriage

            case 'idet':
            {                       // event type
                $parms                      = array('idet'  => $value,
                                                    'idmr'  => $idmr);
                break;
            }                   

            case 'ider':
            {                       // key of existing Event
                $parms['ider']              = $value;
                break;
            }

            case 'date':
            {                       // date of event
                $parms['eventd']            = $value;
                break;
            }

            case 'eventloc':
            {                       // location of event
                $event                      = new Event($parms);
                $event->setLocation($value);
                $events[]                   = $event;
                break;
            }

            case 'marendd':
            {       // end of marriage
                // this call also sets field 'marendsd'
                $family['marendd']      = $value;
                break;
            }       // end of marriage

            case 'marloc':
            {
                if ($value && strlen($value) > 0)
                {
                    $marLocation    = new Location(array('location' => $value));
                    if (!$marLocation->isExisting())
                    {
                        $marLocation->save(false);
                        $lastSql    = $marLocation->getLastSqlCmd();
                        if (strlen($lastSql) > 0)
                        {
                            $ttagname           = 'marloc' . __LINE__;
;                           $actions[$ttagname] = $lastSql;
                        }
                    }
                    $IDLRMar        = $marLocation->getId();
                }
                else
                    $IDLRMar        = 1;
                $family['idlrmar']      = $IDLRMar;
                break;
            }

            case 'seald':
            {       // date sealed to parents deprecated
                // this call also sets field 'sealsd'
                $family['seald']        = $value;
                break;
            }       // date sealed to parents

            case 'sealloc':
            {       // deprecated
                if ($value && strlen($value) > 0 && ctype_digit($value))
                {
                    $SealTemple         = new Temple(array('idtr' => $value));
                    $IDTRSeal           = $SealTemple->getIdtr();
                }
                else
                    $IDTRSeal           = 1;
                $family['idtrseal']     = $idtrseal;
                break;
            }

            case 'idtrseal':
            case 'trseal':  // old form
            {
                $family['idtrseal']         = $value;
                break;
            }

            case 'idms':
            {
                $family['idms']             = $value;
                break;
            }

            case 'notes':
            {
                $family['notes']            = $value;
                break;
            }

            case 'notmarried':
            {
                $family['notmarried']       = 1;
                break;
            }

            case 'nochildren':
            {
                $family['nochildren']       = 1;
                break;
            }

            case 'submit':
            case 'submit':
            case 'idime':
            case 'cittype':
            case 'addcitation':
            case 'addchild':
            case 'addnewchild':
            {                   // buttons
                break;
            }                   // buttons

            case 'source':
            case 'idsx':
            case 'page':
            case 'editcitation':
            case 'delcitation':
            {                   // ignore buttons
                break;
            }                   // ignore buttons

            case 'editchild':
            {                   // edit child button
                // $id contains IDIR of instance of Person
                break;
            }                   // edit child

            case 'detchild':
            {                   // detach child button
                // $id contains IDCR of instance of Child
                break;
            }                   // detach child button

            case 'cidir':
                // $id contains rownum from form
                // first field in new child
                $respobj                        = array();

                // complete processing of previous child
                // $child is an instance of Person
                if ($child !== null)
                {                   // complete previous child
                    $isNewChild                 = !$child->isExisting();
                    $child->save(false);    // apply pending updates
                    $lastSql    = $child->getLastSqlCmd();
                    $cidir                      = $child['idir'];
                    $respobj["idir$oldrow"]     = $cidir;
                    if (strlen($lastSql) > 0)
                    {
                        $ttagname   = 'cidir' . __LINE__;
                        $respobj[$ttagname]     = $lastSql;
                    }
                    if ($isNewChild)
                    {
                        $birthEvent             = $child->getBirthEvent(true);
                        $birthsd                = $birthEvent['eventsd'];
                        $respobj["birthsd$oldrow"] = $birthsd;
                    }

                    if (is_null($childr))
                    {               // add new child
                        $childr             = new Child(array('idmr' => $idmr,
                                                              'idir' => $cidir));
                        $errors             = $childr->getErrors();
                        if (strlen($errors) > 0)
                            $respobj['errors']  = $errors;
                        else
                            $respobj['cmd581']  = $child->getLastSqlCmd();
                    }               // add new child

                    if ($childr)
                    {               // reorder children
                        $newChild               = !$childr->isExisting();
                        $childr['idmr']         = $idmr;
                        $childr['idir']         = $cidir;
                        $childr['order']        = $childOrder;
                        $childOrder++;
                        $childr->save(false);
                        $respobj["child" . __LINE__] 
                                                = $child->getLastSqlCmd();
                        $idcr                   = $childr['idcr'];
                        $respobj["idcr$oldrow"] = $idcr;
                    }               // reorder children
                }                   // complete previous child
                if (strlen($value) > 0)
                    $idir                       = intval($value);
                else
                    $idir                       = 0;
                // $idir is 0 for a new child
                // $idir is -1 to delete an old child

                if ($idir >= 0 && !$family->isExisting())
                {                   // possibly true on first child
                    $family->save(false);
                    $lastSql                    = $family->getLastSqlCmd();
                    if (strlen($lastSql) > 0)
                    {
                        $ttagname               = 'cidir' . __LINE__;
                        $respobj[$ttagname]     = $lastSql;
                    }
                    $idmr                       = $family->getIdmr();
                }                   // possibly true on first child

                if ($idir == 0)
                {                   // new person
                    $child                      = new Person();
                    $child->setTreeName($treename);
                }                   // new person
                else
                if ($idir > 0)
                {                   // existing person
                    $child                  = Person::getPerson($idir);
                    $cidir                  = $idir;
                }                   // existing person
                else
                {                   // negative, detach existing child
                    $child                  = null;
                }                   // negative, detach existing child
                if ($child)
                    $priName                = $child->getPriName();
                else
                    $priName                = null;
                if ($child && $child->isExisting())
                {
                    $birthEvent             = $child->getBirthEvent(false);
                    $deathEvent             = $child->getDeathEvent(false);
                }
                else
                {
                    $birthEvent             = null;
                    $deathEvent             = null;
                }

                $childr                     = null; // processed
                $oldrow                     = $id;  // row number of child
                $actions[$key]              = $respobj;
                break;                  // first record for next child

            case 'cidcr':
            {                       // IDCR of child, zero for new child
                // $id contains rownum from form
                if (strlen($value) > 0)
                    $idcr                   = intval($value);
                else
                    $idcr                   = 0;
                if ($idcr > 0)
                {
                    $childr                 = new Child(array('idcr' => $idcr));
                    if ($childr->isExisting())
                    {                   // existing instance of Child
                        if ($idir < 0)
                        {               // remove existing Child from Family
                            $count          = $childr->delete(false);
                            if ($count)
                                $actions['delete' . __LINE__]   = 
                                                $childr->getLastSqlCmd();
                            $childr         = null;
                        }               // remove existing Child from Family
                        else
                        {               // retain existing Child
                            if ($childr['idir'] == 0)
                                $childr['idir']     = $idir;
                        }               // retain existing Child
                    }                   // instance of Child
                }
                break;
            }   // IDCR of child

            case 'cgender':
            {               // numeric gender of child
                // $id contains rownum from form
                if ($child)
                    $child['gender']            = $value;
                break;
            }               // numeric gender of child

            case 'cgiven':
                // $id contains rownum from form
                if ($priName)
                    $priName['givenname']       = $value;
                break;      // given name of child

            case 'csurname':
                // $id contains rownum from form
                if ($priName)
                    $priName['surname']         = $value;
                break;      // surname of  child

            case 'cbirth':
                // $id contains rownum from form
                $respobj                        = array();
                if ($child)
                {
                    $child['birthd']            = $value;
                    if (!$child->isExisting())
                    {
                        $child->save(false);
                        $lastSql                = $child->getLastSqlCmd();
                        if (strlen($lastSql) > 0)
                        {
                            $ttagname           = 'cbirth' . __LINE__;
                            $respobj[$ttagname] = $lastSql;
                        }
                    }
                    $birthEvent                 = $child->getBirthEvent(true);
                    $birthEvent['eventd']       = $value;
                    $birthEvent->save(false);
                    $lastSql    = $birthEvent->getLastSqlCmd();
                    if (strlen($lastSql) > 0)
                    {
                        $ttagname               = 'cbirth' . __LINE__;
                        $respobj[$ttagname]     = $lastSql;
                    }
                }
                $actions[$key]                  = $respobj;
                break;      // birth date of child

            case 'cdeath':
                // $id contains rownum from form
                if (strlen($value) == 0)
                    break;
                $respobj                        = array();
                if ($child)
                {
                    $child['deathd']            = $value;
                    $deathEvent                 = $child->getDeathEvent(true);
                }
                if ($deathEvent)
                {
                    $deathEvent['eventd']       = $value;
                    $deathEvent->save(false);
                    $lastSql                    = $deathEvent->getLastSqlCmd();
                    if (strlen($lastSql) > 0)
                    {
                        $respobj['cdeath']     = $lastSql;
                    }
                }
                $actions[$key]                  = $respobj;
                break;      // death date child

            default:
                break;      // ignore any unrecognized parameters

        }                   // act on each supported parameter
    }                       // loop through parameters
    $retval['actions']                          = $actions;
}                           // invoked by method=post

// if there were any errors detected, report them and terminate
if (is_null($idmr))
{
    $msg            .= 'Missing mandatory parameter idmr. ';
}

if (strlen($msg) > 0)
{       // missing or invalid value of idmr parameter
    $retval["msg"]          = $msg;
    print json_encode($retval);
    exit;
}       // missing or invalid value of idmr parameter

if ($husb)
{
    $husb->save(false);
    $lastSql                    = $husb->getLastSqlCmd();
    if (strlen($lastSql) > 0)
    {
        $ttagname               = 'husb' . __LINE__;
        $actions[$ttagname]     = $lastSql;
    }
    $idirhusb                   = $husb->getIdir();
    $family['idirhusb']         = $idirhusb;
}

if ($wife)
{
    $wife->save(false);
    $lastSql                    = $wife->getLastSqlCmd();
    if (strlen($lastSql) > 0)
    {
        $ttagname               = 'wife' . __LINE__;
        $actions[$ttagname]     = $lastSql;
    }
    $idirwife                   = $wife->getIdir();
    $family['idirwife']         = $idirwife;
}

$family->save(false);
$lastSql                        = $family->getLastSqlCmd();
if (strlen($lastSql) > 0)
{
    $ttagname                   = 'create' . __LINE__;
    $actions[$ttagname]         = $lastSql;
}
$idmr                           = $family->getIdmr();

if ($wife)
{
    $nameRec                    = new Name(array('idir'     => $wife,
                                                 'order'    => -1,
                                                 'idmr'     => $family));
    if ($wifemarrsurname && $wifemarrsurname != $wife['surname'])
    {                   // explicit surname, for example keep maiden name
        $nameRec['surname']     = $wifemarrsurname;
        $nameRec['marriednamecreatedby']    = 0;
    }                   // explicit surname 
    else
    {                   // take husband's surname
        // note that a record with the husband's surname is created when the
        // wife chooses to retain her maiden name because many sources will
        // ignore that decision
        $nameRec['surname']     = $family['husbsurname'];
        $nameRec['marriednamecreatedby']    = 1;
    }                   // take husband's surname
    $nameRec->save(false);
    $lastSql                    = $nameRec->getLastSqlCmd();
    if (strlen($lastSql) > 0)
    {
        $ttagname               = 'name' . __LINE__;
        $actions[$ttagname]     = $lastSql;
    }
}

// apply last minute changes to Family if any
$family->save(false);
$lastSql                        = $family->getLastSqlCmd();
if (strlen($lastSql) > 0)
{
    $ttagname                   = 'create' . __LINE__;
    $actions[$ttagname]         = $lastSql;
}
$idmr                           = $family->getIdmr();

// update the male partner if necessary
if (is_object($husb))
{                   // have husband
    if (!$husb->isExisting())
    {
        $husb->save(false);
        $lastSql                = $husb->getLastSqlCmd();
        if (strlen($lastSql) > 0)
        {
            $ttagname           = 'husb' . __LINE__;
            $actions[$ttagname] = $lastSql;
        }
    }
    $idirhusb                   = $husb->getIdir();
    $family['idirhusb']         = $idirhusb;
    if ($husb['gender'] == 0)
        $family->setName($husb);
    else
        $msg    .= "Gender of husband changed to female! ";
}                   // have husband
else
{                   // no husband
    $family->setName(Person::MALE);
}                   // no husband

// update female partner if necessary
if (is_object($wife))
{                   // have wife
    $wife['sex']            = Person::FEMALE;
    $wife->save(false);
    $lastSql                = $wife->getLastSqlCmd();
    if (strlen($lastSql) > 0)
    {
        $ttagname           = 'wife' . __LINE__;
        $actions[$ttagname] = $lastSql;
    }
    $idirwife               = $wife->getIdir();
    $family['idirwife']     = $idirwife;
    $family->setName($wife);
}                   // have wife
else
{                   // no wife
    $family->setName(Person::FEMALE);
}                   // no wife

// update the specified family record
if (is_object($family))
{                   // there is an update to make
    // this updates the record in tblMR and all associated records
    // in tblCR
    $count                  = $family->save(false);
    if ($count)
    {
        $lastSql                = $family->getLastSqlCmd();
        $ttagname           = 'family' . __LINE__;
        $actions[$ttagname] = $lastSql;
    }

    // include the contents of the updated record       
    $retval['family']       = array('idmr'      => $idmr,
                                    'idirhusb'  => $idirhusb,
                                    'idirwife'  => $idirwife);

    if (count($events) > 0)
    {               // update generic events
        foreach($events as $event)
        {
            $event['idmr']          = $idmr;
            $event->save(false);
            $lastSql                = $event->getLastSqlCmd();
            if (strlen($lastSql) > 0)
            {
                $ttagname           = 'event' . __LINE__;
                $actions[$ttagname] = $lastSql;
            }
        }
    }               // update generic events
}                   // there is an update to make

} catch(Exception $e)
{
    $msg    .= "Global exception 706: '" . $e->getMessage() . "'" .
                $e->getTraceAsString();
    $retval["msg"]  = $msg;
}
$retval['actions']                  = $actions;

// close off root node
print json_encode($retval);
