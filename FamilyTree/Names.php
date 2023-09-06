<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  Names.php                                                           *
 *                                                                      *
 *  Display a web page containing all of the individuals with a         *
 *  given surname.                                                      *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/11/16      use LegacyDate                                  *
 *      2010/12/12      replace DateToString with LegacyDate::toString  *
 *                      cleanup                                         *
 *      2011/10/31      permit clicking anywhere in the cell containing *
 *                      a link                                          *
 *      2012/01/13      change class names                              *
 *      2012/07/26      change genOntario.html to genOntario.php        *
 *      2013/05/17      use functions pageTop and pageBot to standardize*
 *                      appearance of page                              *
 *      2013/07/27      SQL implementation of SOUNDEX is different from *
 *                      every other implementation of SOUNDEX           *
 *                      clean up parameter validation                   *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/08      standardize appearance of <select>              *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *      2014/12/12      print $warn, which may contain debug trace      *
 *      2015/05/15      use LegacyIndiv::getIndivs to get matches       *
 *                      display and permit edit of Notes                *
 *      2015/07/02      access PHP includes using include_path          *
 *                      link to list of names starting with same letter *
 *                      was wrong                                       *
 *                      add ability to post blogs against a name        *
 *                      add option to request surname by IDNR value     *
 *      2015/07/22      link to nominal index did not expand $surname   *
 *      2016/01/19      add id to debug trace                           *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/03/19      set limit to number of individuals to return    *
 *                      from LegacyIndiv::getIndivs if only surname     *
 *                      specified because a max of 100 will be displayed*
 *                      include link to all individuals with surname    *
 *                      if a given name prefix was specified            *
 *      2017/07/18      do not reference instance of LegacyName if      *
 *                      the surname was not passed as a parameter       *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/18      class LegacyName renamed to class Name          *
 *      2017/09/05      add regular expression pattern field            *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/10/16      use class RecordSet                             *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/10/27      use class Template                              *
 *                      support parameters offset, limit, and lang      *
 *                      support scrolling through set of names if       *
 *                      they exceed the limit                           *
 *      2018/12/26      ignore field IDNR in Name record                *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/03/12      Surname record not created if not required      *
 *      2019/05/17      initialize SOUNDEX, FIRST, and LAST             *
 *      2020/04/25      correct search for matching records in Names    *
 *                      add link to surnames with same pattern          *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2020/12/14      blog section removed from display only template *
 *      2021/07/17      deprecate IDNR parameter                        *
 *      2021/09/30      hide line to display surnames with same pattern *
 *                      if no pattern is defined for this surname       *
 *      2022/06/08      no warning for userid parameter                 * 
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/RecOwner.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// analyze input parameters
$prefix                 = '';
$idnr                   = null;
$idnrtext               = null;
$given                  = null;
$surname                = null;
$surnameRec             = null;
$nameUri                = '';
$treename               = '';
$where                  = '';
$lang                   = 'en';
$givenOk                = false;
$edit                   = false;
$action                 = 'Display';
$getParms               = array();
$offset                 = 0;
$limit                  = 100;
$maxcols                = 4;

// interpret parameters
if (isset($_GET) && count($_GET) > 0)
{                               // invoked by method=get
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                              "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                           // loop through parameters
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>" .
                            htmlspecialchars($value) . "</td></tr>\n";
        $value          = trim($value); 
        switch(strtolower($key))
        {
            case 'surname':
            {                   // surname specified
                $surname            = ucfirst($value);
                break;
            }                   // surname specified
    
            case 'idnr':
            {                   // IDNR specified, deprecated
                if (ctype_digit($value))
                    $idnr           = (int)$value;
                else
                    $idnrtext       = htmlspecialchars($value);
                break;
            }                   // surname specified
    
            case 'given':
            {                   // specified a Given Name or names?
                $given              = $value;
                if ((is_array($given) && count($given) > 0) ||
                    (is_string($given) && strlen($given) > 0))
                {               // valid parameter
                    $getParms['givenpfx']   = $given;
                    $givenOk        = true;
                }               // valid parameter
                else
                    $givenOk        = false;
                break;
            }                   // given name specified
    
            case 'edit':
            {                   // option to edit surname record
                if (strtolower($value) == 'y' &&
                    canUser('edit'))
                {
                    $action         = 'Update';
                    $edit           = true;
                }
                break;
            }                   // option to edit surname record
    
            case 'lang':
            {                   // requested language of display
                $lang               = FtTemplate::validateLang($value);
                break;
            }                   // requested language of display
    
            case 'offset':
            {                   // starting offset in response set
                if (ctype_digit($value))
                    $offset         = (int)$value;            
                break;
            }                   // starting offset in set
    
            case 'limit':
            {                   // maximum number to display
                if (ctype_digit($value))
                    $limit          = (int)$value;            
                break;
            }                   // maximum number to display
    
            case 'maxcols':
            {                   // maximum columns to display
                if (ctype_digit($value))
                    $maxcols        = (int)$value;            
                if ($maxcols> 9)
                    $maxcols        = 9;
                break;
            }                   // maximum columns to display
    
            case 'debug':
            case 'text':
            case 'userid':
            {                   // handled by common
                break;
            }                   // handled by common
    
            default:
            {                   // unexpected
                $value              = htmlspecialchars($value);
                $warn  .= "<p>Unexpected parameter $key='$value'.</p>";
                break;
            }                   // unexpected
        }                       // switch on parameter
    }                           // loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}                               // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{                               // invoked by method=post
    $parmsText              = "<p class='label'>\$_POST</p>\n" .
                                  "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {                           // loop through parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>" .
                            htmlspecialchars($value) . "</td></tr>\n";
        switch(strtolower($key))
        {
            case 'surname':
            {                   // surname specified
                $surname            = ucfirst($value);
                break;
            }                   // surname specified
    
            case 'idnr':
            {                   // IDNR specified, deprecated
                if (ctype_digit($value))
                    $idnr           = (int)$value;
                else
                    $idnrtext       = htmlspecialchars($value);
                break;
            }                   // idnr specified
    
            case 'given':
            {                   // specified a Given Name or names?
                $given              = $value;
                if ((is_array($given) && count($given) > 0) ||
                    (is_string($given) && strlen($given) > 0))
                {               // valid parameter
                    $getParms['givenpfx']   = $given;
                    $givenOk        = true;
                }               // valid parameter
                else
                    $givenOk        = false;
                break;
            }                   // given name specified
    
            case 'lang':
            {                   // requested language of display
                $lang               = FtTemplate::validateLang($value);
                break;
            }                   // requested language of display
    
            case 'offset':
            {                   // starting offset in set
                if (ctype_digit($value))
                    $offset         = (int)$value;            
                break;
            }                   // starting offset in set
    
            case 'limit':
            {                   // maximum number to display
                if (ctype_digit($value))
                    $limit          = (int)$value;            
                break;
            }                   // maximum number to display
    
            case 'maxcols':
            {                   // maximum columns to display
                if (ctype_digit($value))
                    $maxcols        = (int)$value;            
                if ($maxcols > 9)
                    $maxcols        = 9;
                break;
            }                   // maximum columns to display
        }                       // switch on parameter
    }                           // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                               // invoked by method=post

$template                           = new FtTemplate("Names$action$lang.html");

if (strlen($given) > 0)
    $nameUri                        = $surname . ', ' . substr($given, 0, 2);
else
    $nameUri                        = $surname;

// I18N
$translate                          = $template->getTranslate();
$t                                  = $translate['tranTab'];
$genderText                         = array(0 => $t['male'], 
                                            1 => $t['female'], 
                                            2 => $t['unknown']); 

// identify prefix of the name, usually the first letter
if (is_string($idnrtext))
{
    $msg                    .= "Invalid value for IDNR='$idnrtext'. ";
}
else
if ($idnr)
{
    $warn                   .= "<p>Deprecated parameter IDNR.</p>";
    $surnameRec             = new Surname(array('idnr' => $idnr));
    if ($surnameRec->isExisting())
        $surname            = $surnameRec['surname'];
    else
        $msg                .= "IDNR value $idnr does not identify an existing Surname record. ";
}
if (is_null($surname))
{                   // missing mandatory parameter
    $msg                    .= 'Missing mandatory parameter Surname. ';
    $surname                = '';
    $idnr                   = 1;
    $surnameRec             = new Surname(array('surname' => ''));
    $title                  = $template['missing']->innerHTML();
}                   // missing mandatory parameter
else
if (strlen($surname) == 0)
{                   // empty surname
    $prefix                 = '';
    $title                  = $template['nosurname']->innerHTML();
    $idnr                   = 1;
    $surnameRec             = new Surname(array('surname' => ''));
}                   // empty surname
else
{                   // surname provided
    $surnameElt             = $template['surname'];
    if ($surnameElt)
        $title              = $surnameElt->innerHTML();
    else
    {
        $title              = "Persons with the Surname '$surname'";
        // notify administrator
        error_log("Names.php: " . __LINE__ . "Could not find id='surname' template 'Names$action$lang.html'");
    }
    $surnameRec             = new Surname(array('surname' => $surname));
    $idnr                   = $surnameRec['idnr'];

    if (substr($surname, 0, 2) == 'Mc')
        $prefix             = 'Mc';
    else
    if (substr($surname, 0, 2) == "O'")
        $prefix             = mb_substr($surname, 0, 3);
    else
        $prefix             = mb_substr($surname, 0, 1);
}                   // surname provided

// construct the query
$getParms['surname']        = $surname;

$soundslike                 = $surnameRec['soundslike'];
$pattern                    = $surnameRec['pattern'];
$notes                      = $surnameRec['notes'];
$template->set("SURNAME",           htmlspecialchars($surname));
$template->set("KEYWORDS",          ", family tree " . htmlspecialchars($surname));
$template->set("PREFIX",            $prefix);
$template->set('TITLE',             $title, true);
$template->set("IDNR",              $idnr);
$template->set("SOUNDSLIKE",        $soundslike);
$template->set("SOUNDEX",           $soundslike);
$template->set("PATTERN",           $pattern);
$template->set("PATTERNU",          urlencode($pattern));
$template->set("NOTES",             $notes);
$template->set('LANG',              $lang);
$template->set('OFFSET',            $offset+1); // display ordinal value
$template->set('LIMIT',             $limit);
$template->set('CONTACTKEY',        $idnr);
$template->set('CONTACTTABLE',      'Names');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

if (strlen($userid) > 0)
{                   // set up blog form
    $template->set('EMAILADDRESS',          $user->get('email'));
    $template->set('USERID',                $userid);
    $template->set('EMAILCLASS',            'ina');
    $template->set('EMAILREADONLY',         'readonly="readonly"');
}
else
{
    $template->set('EMAILADDRESS',          '');
    $template->set('USERID',                '');
    $template->set('EMAILCLASS',            'white');
    $template->set('EMAILREADONLY',         '');
}

if (strlen($msg) == 0)
{                       // no errors detected
    if (isset($_POST) && count($_POST) > 0)
    {                   // update object from $_POST parameters
        $surnameRec->postUpdate(false);

        // save object state to server
        $surnameRec->save();
        $template->set("IDNR",              $surnameRec['idnr']);
        $template->set("SOUNDSLIKE",        $surnameRec['soundslike']);
        $template->set("SOUNDEX",           $surnameRec['soundslike']);
        $template->set("PATTERN",           $surnameRec['pattern']);
        $template->set("NOTES",             $surnameRec['notes']);
    }                   // update object from $_POST parameters

    $getParms['offset']         = $offset;
    $getParms['limit']          = $limit;
    $personList                 = new PersonSet($getParms);
    $info                       = $personList->getInformation();
    $countQuery                 = $info['countquery'];
    $showQuery                  = $info['query'];
    $count                      = $info['count'];
    $actualCount                = $personList->count();
    if ($count > 0)
    {
        $first                  = $personList->rewind();
        if ($first instanceof Person)
        {
            $nameUri            = $first->get('surname') . ', ' .
                                    substr($first->get('givenname'), 0, 2);
            $treename           = $first->getTreename();
        }
    }
}                       // no errors detected
else
{                       // errors
    $title                      = $template['missing']->innerHTML();
    $personList                 = array();
    $count                      = 0;
    $actualCount                = 0;
}                       // errors

$template->set('TOTALCOUNT',            $count);
$template->set('ACTUALCOUNT',           $actualCount);
$template->set('FIRST',                 $offset + 1);
$template->set('LAST',                  min($offset + $actualCount, $count));
if ($actualCount >= $count)
    $template['showActualCount']->update(null);
$template->set('PREV',                  max($offset-$limit,0));
$template->set('NEXT',                  min($offset+$limit, $count-1));
$template->set('NAMEURI',               $nameUri);
$template->set('TREENAME',              $treename);
if ($debug)
    $template->set('DEBUG',             'Y');
else
    $template->set('DEBUG',             'N');

// check for notes about family
$notes                          = $surnameRec->get('notes');
$template->set('NOTES',                 $notes);
$template->set('SOUNDEX',               $soundslike);
if ($count == 0)
    $count                      = $t['No'];
$template->set('COUNT',                 $count);

$nxparms                        = array('surname' => "^$surname$");
$nxlist                         = new RecordSet('Names', $nxparms);
$information                    = $nxlist->getInformation();
$query                          = $information['query'];
$template->set('QUERY',                 $query);
$nxcount                        = $information['count'];

if ($nxcount == 0)
{                       // no matching names
    if (canUser('edit') &
        $surnameRec->get('pattern') == '' &
        $surnameRec->isExisting())
    {
        $delcount   = $surnameRec->delete(false);
    }
    else
    {
        $template['deletedUnused']->update(null);
    }
    $template->set('NXCOUNT',   'No');
}                       // no matching names
else
{                       // some matching names
    $template['deletedUnused']->update(null);
    $template->set('NXCOUNT',           $nxcount);
}                       // some matching names

if (!canUser('edit'))
    $template['surnameForm']->update(null);

// display the results
$template->set('MAXCOLS',           $maxcols);
$curcol                         = 0;
$data                           = '';
$entryElt                       = $template['entry'];
$entryEltHtml                   = $entryElt->outerHTML();

foreach($personList as $idir => $person)
{
    // link to detailed query action
    $entryTemplate              = new Template($entryEltHtml);
    $name                       = $person->getName(Person::NAME_INCLUDE_DATES);
    $gender                     = $person->getGender();
    $gender                     = $genderText[$gender];
    $entryTemplate->set('NAME',     $name);
    $entryTemplate->set('IDIR',     $idir);
    $entryTemplate->set('GENDER',   $gender);
    $entryTemplate->set('LANG',     $lang);
    $data                       .= $entryTemplate->compile();
}               // loop through results

$template['entry']->update($data);

// show any blog postings
$blogElement                = $template['blogEntry'];
if ($blogElement)
{
    if ($surnameRec->isExisting())
    {
        $idnr                   = $surnameRec->get('idnr');
        $blogParms              = array('keyvalue'          => $idnr,
                                        'table'             => 'tblNR');
        $bloglist               = new RecordSet('Blogs', $blogParms);
    
        // display existing blog entries
        $blogElt                = $template['blogEntry'];
        $data                   = '';
        foreach($bloglist as $blid => $blog)
        {       // loop through all blog entries
            $blogTemplate       = new Template($blogElt->innerHTML());
            $blogTemplate->set('BLID',      $blid);
            $datetime           = $blog->getTime();
            $blogTemplate->set('DATETIME',  $datetime);
            $username           = $blog->getUser();
            $blogTemplate->set('USERNAME',  $username);
            $text               = $blog->getText();
            $text               = str_replace("\n", "</p>\n<p>", $text);
            $blogTemplate->set('TEXT',  $text);
            if ($username != $userid)
                $blogTemplate['blogActions']->update(null);
            $data               .= $blogTemplate->compile();
        }       // loop through all blog entries
        $blogElement->update($data);
    }
    else
        $blogElement->update(null);
}
if ($pattern == '')
    $template['patternPara']->update(null);

$template->display();
