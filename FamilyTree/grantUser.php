<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  grantUser.php                                                       *
 *                                                                      *
 *  Display a web page to reporting the results of a grant of authority *
 *  to update an individual, his/her spouses, his/her descendants,      *
 *  and his/her ancestors.                                              *
 *                                                                      *
 *  Parameters passed by method=POST:                                   *
 *      idir            unique numeric key of the instance of           *
 *                      Person for which the grant is to be given       *
 *      User            unique identifier of the user to whom access    *
 *                      is granted                                      *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/08      created                                         *
 *      2010/12/09      add link to help page                           *
 *      2010/12/12      replace LegacyDate::dateToString with           *
 *                      LegacyDate::toString                            *
 *      2010/12/20      handle exception from new LegacyIndiv           *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/09/26      order user names                                *
 *      2012/01/13      change class names                              *
 *      2013/06/01      change nominalIndex.html to legacyIndex.php     *
 *                      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/19      replace table used for layout with CSS          *
 *      2014/08/15      use LegacyIndiv::getFamilies                    *
 *                      use LegacyFamily:getChildren                    *
 *                      use LegacyIndiv::getParents                     *
 *      2014/09/26      try to update parents and children even if      *
 *                      current entry is already granted in case the    *
 *                      children or parents were added by another       *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *                      use Record method addOwner to add ownership     *
 *      2014/12/12      print $warn, which may contain debug trace      *
 *      2015/01/01      use extended getName from LegacyIndiv           *
 *      2015/03/20      use getName fom LegacyIndiv to get names of     *
 *                      all granted individuals                         *
 *                      use great in hierarchies                        *
 *                      identify spouse for husband and wife entries    *
 *                      make each name a color-coded hyperlink          *
 *      2015/04/01      hyperlink root individual                       *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      add id to debug trace                           *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *      2017/08/16      legacyIndivid.php renamed to Person.php         *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2018/01/28      Record::addOwner is ordinary method             *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/11/19      change Helpen.html to Helpen.html               *
 *      2019/07/13      send e-mail to grantor and grantee              *
 *                      grantIndivid now passes id of Grantee instead   *
 *                      of username                                     *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2021/05/24      correct use of obsolete $child->getIdir()       *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  grantMarriages                                                      *
 *                                                                      *
 *  Extend the grant to spouses and children of the individual.         *
 *                                                                      *
 *  Input:                                                              *
 *      $person         instance of Person                              *
 *      $rank           string identifier of level of descent           *
 *      $granteeName    string name of grantee                          *
 *      $template       instance of Template                            *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing HTML describing actions for descendants       *
 ************************************************************************/
function grantMarriages($person, $rank, $granteeName, $template)
{
    global  $warn;
    global  $tranTab;

    $retval             = '';
    $name               = $person->getName(Person::NAME_INCLUDE_DATES);
    $families           = $person->getFamilies();
    foreach($families as $i => $family)
    {
        if ($person->getGender() == Person::FEMALE)
        {               // female
            $spsid      = $family->get('idirhusb');
            $spouse     = $family->getHusband();
            $role       = $tranTab['husband'];
            $spsclass   = 'male';
        }               // female
        else
        {               // male
            $spsid      = $family->get('idirwife');
            $spouse     = $family->getWife();
            $role       = $tranTab['wife'];
            $spsclass   = 'female';
        }               // male

        // ensure that access to spouses is permitted
        if ($spsid > 0)
        {               // a spouse is defined
            $spsName    = $spouse->getName(Person::NAME_INCLUDE_DATES);
            $done       = $spouse->addOwner($granteeName);
            if ($done)
            {           // authority granted
                $element    = $template['grantSpouse'];
            }           // authority granted
            else
            {           // previously granted
                $element    = $template['alreadySpouse'];
            }           // previously granted
            $retval         .= str_replace(
        array('$ROLE','$NAME','$SPSID','$SPSCLASS','$SPSNAME','$GRANTEENAME'),
        array( $role,  $name,  $spsid,  $spsclass,  $spsName,  $granteeName),
                                           $element->outerHTML);
        }               // a spouse is defined

        // check children regardless, since a child may have been
        // added by another user and children may be defined even
        // where only a single spouse is known
        $children   = $family->getChildren();
        foreach($children as $i => $child)
        {               // loop through all child records
            // display information about child
            $cid                = $child['idir'];
            $child              = $child->getPerson();
            if ($child->isExisting())
            {
                $cName          = $child->getName(Person::NAME_INCLUDE_DATES);
                if ($child->getGender() == Person::FEMALE)
                {
                    $role       = $tranTab['daughter'];
                    $cclass     = 'female';
                }
                else
                if ($child->getGender() == Person::MALE)
                {
                    $role       = $tranTab['son'];
                    $cclass     = 'male';
                }
                else
                {
                    $role       = $tranTab['child'];
                    $cclass     = 'unknown';
                }
                $done           = $child->addOwner($granteeName);

                if ($done)
                {       // authority granted
                    $element    = $template['grantChild'];
                }       // authority granted
                else
                {       // previously granted
                    $element    = $template['alreadyChild'];
                }       // previously granted
                $retval         .= str_replace(
        array('$RANK','$ROLE','$CID','$CCLASS','$CNAME','$GRANTEENAME'),
        array( $rank,  $role,  $cid,  $cclass,  $cName,  $granteeName),
                                               $element->outerHTML);

                // recurse down the list of descendants including
                // their spouses and children
                if (strlen($rank) == 0)
                    $trank  = $tranTab['grand-'];
                else
                    $trank  = $tranTab['great-'] . $rank;
                $retval     .= grantMarriages($child,
                                              $trank,
                                              $granteeName,
                                              $template);
            }       // try
            else
            {       // error creating child's instance of Person
                $warn   .= "<p>grantUser.php: " . __LINE__ . 
    " Child does not correspond to an existing instance of Petrson</p>\n";
            }       // error creating child's instance of Person
        }           // loop through all child records
    }               // loop through all marriages of the individual
    return $retval;
}       // function grantMarriages

/************************************************************************
 *  grantParents                                                        *
 *                                                                      *
 *  Extend the grant to parents of the individual.                      *
 *                                                                      *
 *  Input:                                                              *
 *      $person         instance of Person                              *
 *      $rank           string identifier of level of descent           *
 *      $granteeName    string name of grantee                          *
 *      $template       instance of Template                            *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing HTML describing actions for ancestors         *
 ************************************************************************/
function grantParents($person, $rank, $granteeName, $template)
{
    global  $warn;
    global  $tranTab;

    $retval                 = '';
    $parents                = $person->getParents();
    foreach($parents as $idcr => $family)
    {
        // check for father
        $fidir              = $family->get('idirhusb');
        if ($fidir > 0)
        {       // has a father
            $father         = $family->getHusband();
            $fName          = $father->getName(Person::NAME_INCLUDE_DATES);

            $done           = $father->addOwner($granteeName);
            if ($done)
            {               // access granted
                $element    = $template['grantFather'];
                $retval     .= str_replace(
        array('$RANK','$FIDIR','$FNAME','$GRANTEENAME'),
        array( $rank,  $fidir,  $fName,  $granteeName),
                                           $element->outerHTML);

                // recursively grant access to parents of father
                if (strlen($rank) == 0)
                    $trank  = $tranTab['grand-'];
                else
                    $trank  = $tranTab['great-'] . $rank;
                $retval     .= grantParents(Person::getPerson($fidir),
                                            $trank,
                                            $granteeName,
                                            $template);
            }               // access granted
            else
            {               // previously granted
                $element    = $template['alreadyFather'];
                $retval     .= str_replace(
        array('$RANK','$FIDIR','$FNAME','$GRANTEENAME'),
        array( $rank,  $fidir,  $fName,  $granteeName),
                                           $element->outerHTML);
            }               // previously granted
        }       // has a fatherhttp://www.jamescobban.net

        // check for mother
        $midir              = $family->get('idirwife');
        if ($midir > 0)
        {       // has a mother
            $mother         = $family->getWife();
            $mName          = $mother->getName(Person::NAME_INCLUDE_DATES);;
            $done           = $mother->addOwner($granteeName);
            if ($done)
            {       // access granted
                $element    = $template['grantMother'];
                $retval     .= str_replace(
        array('$RANK','$MIDIR','$MNAME','$GRANTEENAME'),
        array( $rank,  $midir,  $mName,  $granteeName),
                                           $element->outerHTML);

                // recursively grant access to parents of mother
                if (strlen($rank) == 0)
                    $trank  = $tranTab['grand-'];
                else
                    $trank  = $tranTab['great-'] . $rank;
                $retval     .= grantParents(Person::getPerson($midir),
                                            $trank,
                                            $granteeName, 
                                            $template);
            }           // access granted
            else
            {           // previously granted
                $element    = $template['alreadyMother'];
                $retval     .= str_replace(
        array('$RANK','$MIDIR','$MNAME','$GRANTEENAME'),
        array( $rank,  $midir,  $mName,  $granteeName),
                                           $element->outerHTML);
            }           // previously granted
        }               // has a mother
    }                   // loop through all sets of parents of the individual
    return $retval;
}       // function grantParents

$idir               = null;
$idirtext           = null;
$person             = null;
$isOwner            = false;
$lang               = 'en';
$granteeId          = null;
$granteeName        = null;
$grantee            = null;
$useremail          = 'webmaster@jamescobban.net';

if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {                       // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch (strtolower($key))
        {
            case 'email':
            {
                $useremail      = $value;
                break;
            }

            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
        }
    }                       // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{                   // invoked by submit to update
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach ($_POST as $key => $value)
    {                       // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
            "<td class='white left'>$value</td></tr>\n";
        switch (strtolower($key))
        {
            case 'idir':
            {               // identifier of root individual
                if (ctype_digit($value) && $value > 0)
                    $idir           = $value;
                else
                    $idirtext       = htmlspecialchars($value);
                break;
            }               // identifier of root individual
       
            case 'user':
            case 'grantee':
            {               // id of grantee
                if (strlen($value) > 0)
                    $granteeId      = $value;
                break;
            }               // id of grantee
    
            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
        }                   // missing parameter
    }                       // loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}                   // invoked by submit to update account

$template               = new FtTemplate("grantUser$lang.html");
$tranTab                = $template->getTranslate()['tranTab'];

// validate grantee
$grantee                = new User(array('id'     => $granteeId));

if ($grantee->isExisting())
{                   // grantee is a registered user
    $granteeName            = $grantee['username'];
    $template->set('GRANTEENAME',       htmlspecialchars($granteeName));
    $granteeMail            = $grantee['email'];
    $template->set('GRANTEEMAIL',       htmlspecialchars($granteeMail));
    $userName               = $user['username'];
    $template->set('USERNAME',          htmlspecialchars($userName));
    $userMail               = $user['email'];
    $template->set('USERMAIL',          htmlspecialchars($userMail));

    // note that record 0 in tblIR contains only the next available value
    // of IDIR
    if ($idir > 0)
    {                   // get the requested individual
        $person             = Person::getPerson($idir);
        if ($person->isExisting())
        {               // must be already in the tree
            $template->set('IDIR',                  $idir);
            $isOwner        = canUser('edit') && $person->isOwner();
             
            $name           = $person->getName(Person::NAME_INCLUDE_DATES);
            $given          = $person->getGivenName();
            $surname        = $person->getSurname();
            if (strlen($surname) == 0)
                $prefix     = '';
            else
            if (substr($surname,0,2)    == 'Mc')
                $prefix     = 'Mc';
            else
                $prefix     = substr($surname,0,1);
            $template->set('NAME',                  $name);
            $template->set('GIVENNAME',             $given);
            $template->set('SURNAME',               $surname);
            $template->set('PREFIX',                $prefix);

            $template['idirTitle']->update(null);
            $template['granteeTitle']->update(null);
            if ($isOwner)
            {
                $template['deniedTitle']->update(null);
            }
            else
                $template['grantTitle']->update(null);
        }               // must be already in the tree
        else
        {
            $template['grantTitle']->update(null);
            $template['deniedTitle']->update(null);
            $template['granteeTitle']->update(null);
            $text       = $template['invalidIdir']->innerHTML;
            $msg        .= str_replace('$idir', $idir, $text);
        }
    }       // get the requested individual
    else
    {       // invalid input
        $template['grantTitle']->update(null);
        $template['deniedTitle']->update(null);
        $template['granteeTitle']->update(null);
        $text           = $template['invalidIdir']->innerHTML;
        $msg            .= str_replace('$idir', $idirtext, $text);
    }       // invalid input
}                   // grantee is a registered user
else
{       // invalid input
    $template['grantTitle']->update(null);
    $template['deniedTitle']->update(null);
    $template['idirTitle']->update(null);
    $text               = $template['invalidGrantee']->innerHTML;
    $msg                .= str_replace('$granteeName', $granteeName, $text);
}       // invalid input

if ($isOwner)
{           // user is authorized to edit this record
    $template['notOwner']->update(null);
    $contents                   = '';
    if ($person)
    {       // individual found
        $name       = $person->getName(Person::NAME_INCLUDE_DATES);
        $done       = $person->addOwner($granteeName);
        if ($person->getGender() == Person::FEMALE)
            $class  = 'female';
        else
        if ($person->getGender() == Person::MALE)
            $class  = 'male';
        else
            $class  = 'unknown';
        $template->set('NAME',          $name);
        $template->set('CLASS',         $class);
        if ($done)
        {       // previously granted
            $template['alreadyMain']->update(null);
        }       // previously granted
        else
        {       // new grant
            $template['grantMain']->update(null);
        }       // new grant
    
        // check for children and parents not previously granted
        $contents           .= grantMarriages($person, 
                                              '', 
                                              $granteeName, 
                                              $template);
        $contents           .= grantParents($person, 
                                              '', 
                                              $granteeName, 
                                              $template);
    }       // individual found
    $template->set('CONTENTS',          $contents);

    if (strlen($useremail) > 0 && strlen($contents) > 0)
    {                   // send a copy to the grantor and the grantee
        // To send HTML mail, the Content-type header must be set
        $headers    = 'MIME-Version: 1.0' . "\r\n";
        $headers    .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     
        // Create email headers
        $headers    .= "From: webmaster@jamescobban.net\r\n".
                        "Reply-To: webmaster@jamescobban.net\r\n" .
                        'X-Mailer: PHP/' . phpversion();
        $sendto     = $grantee['email'] . ',' . $user['email'];
        $title      = $template['grantTitle']->innerHTML;
        $title      = str_replace(array('$NAME', '$GRANTEENAME'),
                                  array( $name,   $granteeName),
                                  $title);
        $title      = htmlspecialchars_decode($title);
        $heading    = "<p>$title</p>\n" .
                        $template['grantHeader']->outerHTML;
        if (mail($sendto,
                 $title,
                 $heading . $contents,
                 $headers))
        {
            $template['confirmationFailed']->update(null);
        }
        else
        {
            $template['confirmationOK']->update(null);
        }
    }                   // send a copy to the grantor and the grantee
}                       // current user is an owner of record
else
{                       // current user does not own record
    $template['grantMain']->update(null);
    $template['alreadyMain']->update(null);
    $template['confirmationFailed']->update(null);
    $template['confirmationOK']->update(null);
    $template->set('CONTENTS',          '');
}                       // current user does not own record

$template->display();
