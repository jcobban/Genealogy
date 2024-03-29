<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  ToDo.php                                                            *
 *                                                                      *
 *  Display a web page containing details of an particular ToDo         *
 *  record from the database.  If the current user is authorized to     *
 *  edit the database, this web page supports that.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      idtd            Unique numeric identifier of the todo.          *
 *                      For backwards compatibility this can be         *
 *                      specified using the 'id' parameter.             *
 *      idir            Unique numeric identifier of the Person.        *
 *      name            specify name of todo to display                 *
 *                      Primarily for creation of a new record          *
 *      closeAtEnd      If set to 'y' or 'Y' then when the todo         *
 *                      has been updated, leave the frame blank         *
 *                                                                      *
 *  History:                                                            *
 *      2019/08/13      created                                         *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      cover XSS vulnerabilities                       *
 *                      improve parameter testing                       *
 *      2021/04/23      move message texts to template                  *
 *                      permit user to fill in IDIR if not already      *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// action depends upon whether the user is authorized to update
// this specific record
if (canUser('edit'))
    $action                     = 'Update';
else
    $action                     = 'Display';

// default values of parametets
$namestart                      = '';
$idtd                           = null;     // default to create new
$idtdtext                       = null;
$todo                           = null;     // instance of ToDo
$idir                           = null;     // default to create new
$idirtext                       = null;
$person                         = null;
$name                           = '';
$todotype                       = null;
$todotext                       = null;
$idtc                           = null;
$idtctext                       = null;
$idtl                           = null;
$idtltext                       = null;
$location                       = null;
$todoname                       = null;
$openeddate                     = null;
$reminderdate                   = null;
$closeddate                     = null;
$idar                           = null;
$idartext                       = null;
$status                         = null;
$statustext                     = null;
$priority                       = null;
$prioritytext                   = null;
$desc                           = null;
$results                        = null;
$filingref                      = null;
$tag1                           = null;
$tag1text                       = null;
$qstag                          = null;
$qstagtext                      = null;
$used                           = null;
$usedtext                       = null;
            
$closeAtEnd                     = false;
$lang                           = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{                       // invoked by URL to display specific record
    $updating       = false;
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through all parameters
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                            $safevalue . "</td></tr>\n"; 
        switch(strtolower($key))
        {               // act on specific parameters
            case 'idtd':
            case 'id':
            {           // numeric key of todo
                if (ctype_digit($value) && $value > 0)
                    $idtd           = $value;
                else
                    $idtdtext       = $safevalue;
                break;
            }           // numeric key of todo
    
            case 'idir':
            {           // numeric key of Person
                if (ctype_digit($value) && $idir > 0)
                {
                    $idir           = $value;
                    $person         = new Person(array('idir' => $idir));
                    if (!$person->isExisting())
                    {
                        $idirtext   = $value;
                        $person     = null;
                    }
                }
                else
                    $idirtext       = $safevalue;
                break;
            }           // numeric key of Person
    
            case 'name':
            {           // name of todo
                $idtd               = 0;
                $name               = $safevalue;
                break;
            }           // name of todo
    
            case 'lang':
            {           // user's preferred language
                $lang               = FtTemplate::validateLang($value);
                break;
            }           // user's preferred language
    
            case 'action':
            {           // request to only display the record
                if (strtolower($value) == 'display')
                    $action         = 'Display';
                break;
            }           // request to only display the record
    
            case 'closeatend':
            {           // close the frame when finished
                if (strtolower($value) == 'y')
                    $closeAtEnd     = true;
                break;
            }           // close the frame when finished
    
            case 'debug':
            case 'text':
            {           // handled by common code
                break;
            }           // handled by common code
    
            default:
            {
                $warn   .= "<p>ToDo.php: Unexpected parameter $key='$safevalue'</p>\n"; break;
            }
        }               // act on specific parameters
    }                   // loop through all parameters
}                       // invoked by URL to display specific record
else
if (count($_POST) > 0)
{                       // invoked by submit to update todo record
    $updating       = true;
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {                   // loop through all parameters
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {               // act on specific parameter
            case 'idtd':
            {
                if (ctype_digit($value))
                    $idtd           = $value;
                else
                    $idtdtext       = $safevalue;
                break;
            }

            case 'todotype':
            {
                if (ctype_digit($value))
                    $todotype       = $value;
                else
                    $todotext       = $safevalue;
                break;
            }

            case 'idir':
            {
                if (ctype_digit($value) && $value > 0)
                {
                    $idir           = $value;
                    $person         = new Person(array('idir' => $idir));
                    if (!$person->isExisting())
                    {
                        $idirtext   = $value;
                        $person     = null;
                    }
                }
                else
                    $idirtext       = $safevalue;
                break;
            }

            case 'idtc':
            {
                if (ctype_digit($value))
                    $idtc           = $value;
                else
                    $idtctext       = $safevalue;
                break;
            }

            case 'idtl':
            {
                if (ctype_digit($value))
                    $idtl           = $value;
                else
                    $idtltext       = $safevalue;
                break;
            }

            case 'location':
            {
                $location           = $safevalue;
                break;
            }

            case 'todoname':
            {
                $todoname           = $safevalue;
                break;
            }

            case 'openeddate':
            {
                $openeddate         = $safevalue;
                break;
            }

            case 'reminderdate':
            {
                $reminderdate       = $safevalue;
                break;
            }

            case 'closeeddate':
            {
                $closeddate         = $safevalue;
                break;
            }

            case 'idar':
            {
                if (ctype_digit($value))
                    $idar           = $value;
                else
                    $idartext       = $safevalue;
                break;
            }

            case 'status':
            {
                if (ctype_digit($value))
                    $status         = $value;
                else
                    $statustext     = $safevalue;
                break;
            }

            case 'priority':
            {
                if (ctype_digit($value))
                    $priority       = $value;
                else
                    $prioritytext= $safevalue;
                break;
            }

            case 'desc':
            {
                $desc               = $value;
                break;
            }

            case 'results':
            {
                $results            = $value;
                break;
            }

            case 'filingref':
            {
                $filingref          = $value;
                break;
            }

            case 'tag1':
            {
                if (ctype_digit($value))
                    $tag1           = $value;
                else
                    $tag1text       = $safevalue;
                break;
            }

            case 'qstag':
            {
                if (ctype_digit($value))
                    $qstag          = $value;
                else
                    $qstagtext      = $safevalue;
                break;
            }

            case 'used':
            {
                if (ctype_digit($value))
                    $used           = $value;
                else
                    $usedtext       = $safevalue;
                break;
            }

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
        }               // act on specific parameter
    }                   // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}                       // invoked by submit to update account

// get the requested todo
if ($idtd > 0)
{                       // IDTD of existing record specified
    $todo           = new ToDo(array('idtd' => $idtd));
    $idir           = $todo['idir'];
    $name           = $todo->getName();
}                       // IDTD of existing record specified
else
if ($idir > 0)
{                       // IDTD not specified, create new
    $todo           = new ToDo(array('idir' => $idir));
}                       // IDTD not specified

if ($todo instanceof ToDo && $todo->isExisting() && !$todo->isOwner())
    $action         = 'Display';

// get template
$template           = new FtTemplate("ToDo$action$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'ToDo'));
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

// report issues detected while processing input
if (is_string($idtdtext) && strlen($idtdtext) > 0)
    $msg    .= $template['invalidIdtd']->replace('$idtdtext',$idtdtext);
if (is_string($idirtext) && strlen($idirtext) > 0)
    $msg    .= $template['invalidIdir']->replace('$idirtext',$idirtext);
if (is_null($idtd) && is_null($idir))
    $msg    .= $template['missingIdtd']->innerHTML;
if (is_string($todotext) && strlen($todotext) > 0)
    $msg    .= $template['invalidType']->replace('$todotext',$todotext);
if (is_string($idtctext) && strlen($idtctext) > 0)
    $msg    .= $template['invalidIdtc']->replace('$idtctext',$idtctext);
if (is_string($idtltext) && strlen($idtltext) > 0)
    $msg    .= $template['invalidIdtl']->replace('$idtltext',$idtltext);
if (is_string($idartext) && strlen($idartext) > 0)
    $msg    .= $template['invalidIdar']->replace('$idartext',$idartext);
if (is_string($statustext) && strlen($statustext) > 0)
    $msg    .= $template['invalidStatus']->replace('$statustext',$statustext);
if (is_string($prioritytext) && strlen($prioritytext) > 0)
    $msg    .= $template['invalidPriority']->replace('$prioritytext',$prioritytext);
if (is_string($tag1text) && strlen($tag1text) > 0)
    $msg    .= $template['invalidTag1']->replace('$tag1text',$tag1text);
if (is_string($qstagtext) && strlen($qstagtext) > 0)
    $msg    .= $template['invalidQstag']->replace('$qstagtext',$qstagtext);
if (is_string($usedtext) && strlen($usedtext) > 0)
    $msg    .= $template['invalidUsed']->replace('$usedtext',$usedtext);
    
//
if ($updating && $todo instanceof ToDo)
{
    if ($todotype !== null)
        $todo['idir']               = $idir;
    if ($todotype !== null)
        $todo['todotype']           = $todotype;
    if ($idtc !== null)
        $todo['idtc']               = $idtc;
    if ($location !== null)
    {
        $locobj             = new Location(array('location' => $location));
        if (!$locobj->isExisting())
            $locobj->save();
        $idtl                       = $locobj->getIdlr();
        $todo['idtl']               = $idtl;
    }
    if ($todoname !== null)
        $todo['todoname']           = $todoname;
    if ($openeddate !== null)
        $todo['openedd']            = $openeddate;
    if ($reminderdate !== null)
        $todo['reminderdd']         = $reminderdate;
    if ($closeddate !== null)
        $todo['closedd']            = $closeddate;
    if ($idar !== null)
        $todo['idar']               = $idar;
    if ($status !== null)
        $todo['status']             = $status;
    if ($priority !== null)
        $todo['priority']           = $priority;
    if ($desc !== null)
        $todo['desc']               = $desc;
    if ($results !== null)
        $todo['results']            = $results;
    if ($filingref !== null)
        $todo['filingref']          = $filingref;
    if ($tag1 !== null)
        $todo['tag1']               = $tag1;
    if ($qstag !== null)
        $todo['qstag']              = $qstag;
    if ($used !== null)
        $todo['used']               = $used;

    $todo->save();
    $idtd                           = $todo['idtd'];
}               // update record

// set up values for displaying in form
$template->set('IDTD',              $idtd);
for($td = 0; $td <= 2; $td++)
{
    if ($td == $idtd)
        $template->set("TDSELECTED$td",     'selected="selected"');
    else
        $template->set("TDSELECTED$td",     '');
}
$template->set('IDIR',              $idir);
if ($person && $person instanceof Person)
{
    $personname                 = $person->getName($t);   
    $template->set('READONLY',      'readonly="readonly"');
}
else
{
    $personname                 = $template['General']->innerHTML;
    $template->set('READONLY',      '');
}
$personname                     = str_replace('<', '&lt;', $personname);
$template->set('PERSONNAME',        $personname);

if (is_null($todotype) && $todo instanceof ToDo)
    $todotype                   = $todo['todotype'];
$template->set('TODOTYPE',          $todotype);
if (is_null($idtc) && $todo instanceof ToDo)
    $idtc                       = $todo['idtc'];
$template->set('IDTC',              $idtc);
for($tc = 0; $tc <= 20; $tc++)
{
    if ($tc == $idtc)
        $template->set("TCSELECTED$tc",     'selected="selected"');
    else
        $template->set("TCSELECTED$tc",     '');
}

if (is_null($idtl) && $todo instanceof ToDo)
    $idtl                       = $todo['idtl'];
$template->set('IDTL',              $idtl);
$location                       = Location::getLocation($idtl);
if ($location && $location instanceof Location)
    $template->set('LOCATION',      $location->getName());
else
    $template->set('LOCATION',      '');
if (is_null($todoname) && $todo instanceof ToDo)
    $todoname                   = $todo['todoname'];
$template->set('TODONAME',          htmlspecialchars($todoname));
if (is_null($openeddate) && $todo instanceof ToDo)
    $openeddate                 = $todo['openedd'];
$date                           = new LegacyDate($openeddate);
$template->set('OPENEDDATE',        $date->toString(9999, false, $t));
if (is_null($reminderdate) && $todo instanceof ToDo)
    $reminderdate                   = $todo['reminderd'];
$date                           = new LegacyDate($reminderdate);
$template->set('REMINDERDATE',      $date->toString(9999, false, $t));
if (is_null($closeddate) && $todo instanceof ToDo)
    $closeddate                 = $todo['closedd'];
$date                           = new LegacyDate($closeddate);
$template->set('CLOSEDDATE',        $date->toString(9999, false, $t));
if (is_null($idar) && $todo instanceof ToDo)
    $idar                       = $todo['idar'];
$template->set('IDAR',              $idar);
if (is_null($status) && $todo instanceof ToDo)
    $status                     = $todo['status'];
$template->set('STATUS',            $status);
if ($status)
    $template->set('STATUSCHECKED', 'checked="checked"');
for($st = 0; $st <= 1; $st++)
{
    if ($st == $status)
        $template->set("STSELECTED$st",     'selected="selected"');
    else
        $template->set("STSELECTED$st",     '');
}
if (is_null($priority) && $todo instanceof ToDo)
    $priority                   = $todo['priority'];
$template->set('PRIORITY',          $priority);
for($pr = 0; $pr <= 2; $pr++)
{
    if ($pr == $priority)
        $template->set("STSELECTED$pr",     'selected="selected"');
    else
        $template->set("STSELECTED$pr",     '');
}
if (is_null($desc) && $todo instanceof ToDo)
    $desc                       = $todo['desc'];
$template->set('DESC',              htmlspecialchars($desc));
if (is_null($results) && $todo instanceof ToDo)
    $results                    = $todo['results'];
$template->set('RESULTS',           htmlspecialchars($results));
if (is_null($filingref) && $todo instanceof ToDo)
    $filingref                  = $todo['filingref'];
$template->set('FILINGREF',         htmlspecialchars($filingref));
if (is_null($tag1) && $todo instanceof ToDo)
    $tag1                       = $todo['tag1'];
$template->set('TAG1',              $tag1);
if ($tag1)
    $template->set('TAG1CHECKED',   'checked="checked"');
else
    $template->set('TAG1CHECKED',   '');
if (is_null($qstag) && $todo instanceof ToDo)
    $qstag                      = $todo['qstag'];
$template->set('QSTAG',             $qstag);
if ($qstag)
    $template->set('QSTAGCHECKED',  'checked="checked"');
else
    $template->set('QSTAGCHECKED',  '');
if (is_null($used) && $todo instanceof ToDo)
    $used                       = $todo['used'];
$template->set('USED',              $used);
if ($used)
    $template->set('USEDCHECKED',   'checked="checked"');
else
    $template->set('USEDCHECKED',   '');

if ($closeAtEnd)
    $template->set('CLOSE',         'y');
else
    $template->set('CLOSE',         'n');

// display any media files associated with the todo
$picParms           = array('idir'      => $idtd,
                            'idtype'    => Picture::IDTYPEToDo);
$picList            = new RecordSet('Pictures', $picParms);
$element            = $template['pictureTemplate'];
if (count($picList) > 0)
{
    if (is_null($element))
        $template->getDocument()->printTag(0);
    $tempText           = $element->innerHTML();
    $pictures           = '';
    foreach($picList as $idbr => $picture)
    {       // loop through all pictures
        $pictures       .= $picture->toHtml($tempText); // display 
    }       // loop through all pictures
    $template->set('PICTURES',  $pictures);
}
else
{
    $template->set('PICTURES',  '');
    $element->update(null);
}

// if user requested to close the page automatically
if ($closeAtEnd)
{
    $template->updateTag('Close', null);
}

$template->display();
