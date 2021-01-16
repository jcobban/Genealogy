<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  mergeIndivid.php                                                    *
 *                                                                      *
 *  Display a web page to support merging two individuals               *
 *  in the family tree table of individuals.                            *
 *                                                                      *
 *  URI Parameters:                                                     *
 *      idir1           unique numeric key of the first instance of     *
 *                      Person.  Synonyms idir and id.                  *
 *      idir2           unique numeric key of the first instance of     *
 *                      Person                                          *
 *      lang            preferred user language of communication        *
 *                                                                      *
 *  History:                                                            *
 *      2010/12/25      created                                         *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2012/01/13      change class names                              *
 *      2012/07/26      change genOntario.html to genOntario.php        *
 *      2013/01/28      clean up parameter validation                   *
 *                      do not display Gender of individuals, since it  *
 *                      must match, instead hide it as a parameter      *
 *                      add button to prevent merging in future         *
 *                      add help balloons for all fields                *
 *      2013/02/24      standardize presentation                        *
 *      2013/05/31      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *                      use id= instead of name= with buttons           *
 *      2013/06/01      change nominalIndex.html to legacyIndex.php     *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/03/10      use CSS for layout instead of tables            *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/12/01      print $warn, which may contain debug trace      *
 *                      pass debug flag to mergeUpdIndivid.php          *
 *      2015/01/01      use getBirthEvent, getChristeningEvent,         *
 *                      getDeathEvent, getBuriedEvent                   *
 *                      and extended getName from LegacyIndiv           *
 *      2015/03/24      explicitly pass givenname, surname, gender,     *
 *                      and birth year range to getIndivNamesXml        *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/23      add support for treename                        *
 *      2016/01/19      add id to debug trace                           *
 *      2017/06/03      use new format of LegacyIndiv constructor       *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/16      legacyIndivid.php renamed to Person.php         *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2018/11/19      change Help.html to Helpen.html                 *
 *      2019/01/07      use namespace Genealogy                         *
 *                      use Template                                    *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/09/09      customize error messages from template          *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// parameters to nominalIndex.php
$nameuri            = '';
$birthmin           = '';
$birthmax           = '';
$idir               = null;
$idirtext           = null;
$idir2              = null;
$idir2text          = null;
$lang               = 'en';

$parmsText  = "<p class='label'>\$_GET</p>\n" .
              "<table class='summary'>\n" .
              "<tr><th class='colhead'>key</th>" .
                  "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                    "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {
        case 'id':
        case 'idir':
        case 'idir1':
        {           // identifier of individual
            if (is_int($value) || (is_string($value) && ctype_digit($value)))
                $idir       = (int)$value;
            else
                $idirtext   = htmlspecialchars($value);
            break;
        }           // identifier of individual

        case 'idir2':
        {           // identifier of individual
            if (is_int($value) || (is_string($value) && ctype_digit($value)))
                $idir2      = (int)$value;
            else
                $idir2text  = htmlspecialchars($value);
            break;
        }           // identifier of individual

        case 'lang':
        {           // identifier of individual
            $lang       = FtTemplate::validateLang($value);
            break;
        }           // identifier of individual

    }               // switch on keyword
}                   // loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

// get template
$template       = new FtTemplate("mergeIndivid$lang.html");
$template['otherStylesheets']->update(array('filename'    => 'mergeIndivid'));
$translate      = $template->getTranslate();
$t              = $translate['tranTab'];

$template->set('LANG', $lang);
if ($debug)
    $template->set('DEBUG', 'Y');
else
    $template->set('DEBUG', 'N');

// individual
if (isset($idir))
{                   // get the first individual
    $template->set('IDIR',          $idir);
    $person                     = new Person(array('idir' => $idir));
    if ($person->isExisting())
    {
        $isOwner                = canUser('edit') && $person->isOwner();
        $name                   = $person->getName(Person::NAME_INCLUDE_DATES);
        $template->set('NAME',      $name);
        if (!$isOwner)
        {
            $text               = $template['firstNotAuth']->innerHTML;
            $msg                .= str_replace('$idir', $idir, $text);
        }
        $given                  = $person->getGivenName();
        $template->set('GIVEN',     $given);
        if (strlen($given) > 2)
            $givenPre           = substr($given, 0, 2);
        else
            $givenPre           = $given;
        $template->set('GIVENPRE',  $givenPre);
        $surname                = $person->getSurname();
        $nameuri                = rawurlencode($surname . ', ' . $given);
        $template->set('NAMEURI',       $nameuri);
        if (strlen($surname) == 0)
            $prefix             = '';
        else
        if (substr($surname,0,2) == 'Mc')
            $prefix             = 'Mc';
        else
            $prefix             = substr($surname,0,1);
        $template->set('SURNAME',       $surname);
        $template->set('PREFIX',        $prefix);
        $treename               = $person->getTreeName();
        $template->set('TREENAME', $treename);

        // interpret sex of individual
        $gender                 = $person->getGender();
        if ($gender == Person::MALE)
        {
            $gender             = 'M';
            $genderClass        = 'male';
        }
        else
        if ($gender == Person::FEMALE)
        {
            $gender             = 'F';
            $genderClass        = 'female';
        }
        else
        {
            $gender             = '';
            $genderClass        = 'other';
        }
        $template->set('GENDER',        $gender);
        $template->set('GENDERCKASS',   $gender);
    
        // numeric identified of Address record if any
        $idar                   = $person->get('idar');
        $template->set('IDAR', $idar);

        // birth event
        $birth                  = $person->getBirthEvent();
        $birthd                 = '';
        $birthLocationName      = '';
        if ($birth)
        {               // birth event present
            $birthd             = $birth->getDate(9999);
            $birthyear          = floor($birth->get('eventsd')/10000);
            $birthmin           = $birthyear - 10;
            if ($birthmin < -9999)
                $birthmin       = -9999;
            $birthmax           = $birthyear + 10;
            if ($birthmax < -9980)
                $birthmax       = 3000;
            $idlrbirth          = $birth->get('idlrevent');
            if($idlrbirth > 1)
            {
                $birthLocation  = new Location(array('idlr' => $idlrbirth));
                $birthLocationName  = $birthLocation->getName();
            }           // birth date specified
        }               // birth event present
        else
        {               // birth event absent
            $birthmin           = -9999;
            $birthmax           = 2100;
        }               // birth event absent
        $template->set('BIRTHD',            $birthd);
        $template->set('BIRTHLOCATIONNAME', $birthLocationName);
        $template->set('BIRTHMIN',          $birthmin);
        $template->set('BIRTHMAX',          $birthmax);

        // christening event
        $chris                  = $person->getChristeningEvent();
        $chrisd                 = '';
        $chrisLocationName      = '';
        if ($chris)
        {
            $chrisd             = $chris->getDate(9999);
            $idlrchris          = $chris->get('idlrevent');
            if ($idlrchris > 1)
            {
                $chrisLocation  = new Location(array('idlr' => $idlrchris));
                $chrisLocationName  = $chrisLocation->getName();
            }           // christening location specified
        }
        $template->set('CHRISD', $chrisd);
        $template->set('CHRISLOCATIONNAME', $chrisLocationName);

        // death event
        $death                  = $person->getDeathEvent();
        $deathd                 = '';
        $deathLocationName      = '';
        if ($death)
        {
            $deathd             = $death->getDate(9999);
            $idlrdeath          = $death->get('idlrevent');
            if($idlrdeath > 1)
            {
                $deathLocation  = new Location(array('idlr' => $idlrdeath));
                $deathLocationName  = $deathLocation->getName();
            }           // death location specified
        }
        $template->set('DEATHD', $deathd);
        $template->set('DEATHLOCATIONNAME', $deathLocationName);

        // buried event
        $buried                 = $person->getBuriedEvent();
        $buriedd                = '';
        $buriedLocationName     = '';
        if ($buried)
        {
            $buriedd            = $buried->getDate(9999);
            $idlrburied         = $buried->get('idlrevent');
            if($idlrburied > 1)
            {
                $buriedLocation = new Location(array('idlr' => $idlrburied));
                $buriedLocationName = $buriedLocation->getName();
            }           // buried location specified
        }
        $template->set('BURIEDD', $buriedd);
        $template->set('BURIEDLOCATIONNAME', $buriedLocationName);
    }                   // first person is existing
    else
    {                   // first person is not existing
        $text                   = $template['firstInvalid']->innerHTML;
        $msg                    .= str_replace('$idir', $idir, $text);
        $template->set('NAME',  $t['Not Existing']);
    }                   // first person is not existing
}                       // get the first person
else
{                       // first person not identified
    if (is_null($idirtext))
    {
        $msg                    .= $template['firstMissing']->innerHTML;
        $template->set('NAME',  $t['Missing']);
    }
    else
    {
        $text                   = $template['firstInvalid']->innerHTML;
        $msg                    .= str_replace('$idir', $idirtext, $text);
        $template->set('NAME',  $t['Invalid']);
    }
}                       // first person not identified

if (strlen($msg) > 0)
{
    $template->set('IDIR',              '');
    $template->set('GIVEN',             '');
    $template->set('GIVENPRE',          '');
    $template->set('SURNAME',           '');
    $template->set('PREFIX',            '');
    $template->set('TREENAME',          '');
    $template->set('GENDER',            '');
    $template->set('GENDERCLASS',       'other');
    $template->set('IDAR',              '');
    $template->set('BIRTHD',            '');
    $template->set('BIRTHLOCATIONNAME', '');
    $template->set('CHRISD',            '');
    $template->set('CHRISLOCATIONNAME', '');
    $template->set('DEATHD',            '');
    $template->set('DEATHLOCATIONNAME', '');
    $template->set('BURIEDD',           '');
    $template->set('BURIEDLOCATIONNAME','');
}

// other individual to merge with first individual
if (isset($idir2))
{           // get the requested individual
  if ($idir2 == $idir)
  {
        $text                   = $template['cannotMergeSelf']->innerHTML;
        $msg                    .= str_replace('$idir', $idir, $text);
        $template->set('NAME2', 'Self');
  }
  else
  {       // distinct
    $template->set('IDIR2',         $idir2);
    $person             = new Person(array('idir' => $idir2));
    if ($person->isExisting())
    {
        $isOwner        = canUser('edit') && 
                          $person->isOwner();
        $name           = $person->getName(Person::NAME_INCLUDE_DATES);
        $template->set('NAME2',     $name);
        if (!$isOwner)
        {
            $text               = $template['secondNotAuth']->innerHTML;
            $msg                .= str_replace('$idir', $idir2, $text);
        }
    
        $given          = $person->getGivenName();
        $template->set('GIVEN2',    $given);
        if (strlen($given) > 2)
            $givenPre   = substr($given, 0, 2);
        else
            $givenPre   = $given;
        $template->set('GIVENPRE2', $givenPre);
        $surname        = $person->getSurname();
        $nameuri        = rawurlencode($surname . ', ' . $given);
        if (strlen($surname) == 0)
            $prefix     = '';
        else
        if (substr($surname,0,2) == 'Mc')
            $prefix     = 'Mc';
        else
            $prefix     = substr($surname,0,1);
        $template->set('SURNAME2',  $surname);
        $template->set('PREFIX2',   $prefix);
        $treename           = $person->getTreeName();
        $template->set('TREENAME2', $treename);
        // interpret sex of individual
        $gender             = $person->getGender();
        if ($gender == Person::MALE)
            $gender         = 'M';
        else
        if ($gender == Person::FEMALE)
            $gender         = 'F';
        else
            $gender         = '';
        $template->set('GENDER2', $gender);
    
        // numeric index to associated Address record if any
        $idar               = $person->get('idar');
        $template->set('IDAR2', $idar);

        // birth event
        $birth              = $person->getBirthEvent();
        $birthd             = '';
        $birthLocationName  = '';
        $idlrbirth          = 1;
        if ($birth)
        {
            $birthd         = $birth->getDate(9999);
            $idlrbirth      = $birth->get('idlrevent');
        }
        if ($idlrbirth > 1)
        {
            $birthLocation  = new Location(array('idlr' => $idlrbirth));
            $birthLocationName  = $birthLocation->getName();
        }       // specified
        $template->set('BIRTHD2', $birthd);
        $template->set('BIRTHLOCATIONNAME2', $birthLocationName);
    
        // christening event
        $chris              = $person->getChristeningEvent();
        $chrisd             = '';
        $idlrchris          = 1;
        $chrisLocationName  = '';
        if ($chris)
        {
            $chrisd         = $chris->getDate(9999);
            $idlrchris      = $chris->get('idlrevent');
        }
        if ($idlrchris > 1)
        {
            $chrisLocation  = new Location(array('idlr' => $idlrchris));
            $chrisLocationName  = $chrisLocation->getName();
        }       // specified
        $template->set('CHRISD2', $chrisd);
        $template->set('CHRISLOCATIONNAME2', $chrisLocationName);

        // death event
        $death              = $person->getDeathEvent();
        $deathd             = '';
        $idlrdeath          = 1;
        $deathLocationName  = '';
        if ($death)
        {
            $deathd         = $death->getDate(9999);
            $idlrdeath      = $death->get('idlrevent');
        }
        if ($idlrdeath > 1)
        {
            $deathLocation  = new Location(array('idlr' => $idlrdeath));
            $deathLocationName  = $deathLocation->getName();
        }       // specified
        $template->set('DEATHD2', $deathd);
        $template->set('DEATHLOCATIONNAME2', $deathLocationName);

        // buried event
        $buried             = $person->getBuriedEvent();
        $buriedd            = '';
        $idlrburied         = 1;
        $buriedLocationName = '';
        if ($buried)
        {
            $buriedd        = $buried->getDate(9999);
            $idlrburied     = $buried->get('idlrevent');
        }
        if ($idlrburied > 1)
        {
            $buriedLocation = new Location(array('idlr' => $idlrburied));
            $buriedLocationName = $buriedLocation->getName();
        }       // specified
        $template->set('BURIEDD2', $buriedd);
        $template->set('BURIEDLOCATIONNAME2', $buriedLocationName);
        $template->set('VIEW2DISABLED', '');
    }                   // second person is existing
    else
    {                   // second person is not existing
        $text                   = $template['secondInvalid']->innerHTML;
        $msg                    .= str_replace('$idir', $idir2, $text);
        $template->set('NAME2', $t['Not Existing']);
    }                   // second person is not existing
  }                     // distinct
}                       // second person specified
else
{                       // second person not identified
    if (is_null($idir2text))
    {
        $msg                    .= $template['secondMissing']->innerHTML;
        $template->set('NAME2', $t['Missing']);
    }
    else
    {
        $text                   = $template['secondInvalid']->innerHTML;
        $msg                    .= str_replace('$idir', $idir2text, $text);
        $template->set('NAME2', $t['Invalid']);
    }
}                       // second person not identified

if (strlen($msg) > 0)
{
    $template->set('IDIR2',             '');
    $template->set('GIVEN2',            '');
    $template->set('SURNAME2',          '');
    $template->set('PREFIX2',           '');
    $template->set('TREENAME2',         '');
    $template->set('GENDER2',           '');
    $template->set('GENDER2CLASS',      'other');
    $template->set('IDAR2',             '');
    $template->set('BIRTHD2',           '');
    $template->set('BIRTHLOCATIONNAME2','');
    $template->set('CHRISD2',           '');
    $template->set('CHRISLOCATIONNAME2','');
    $template->set('DEATHD2',           '');
    $template->set('DEATHLOCATIONNAME2','');
    $template->set('BURIEDD2',          '');
    $template->set('BURIEDLOCATIONNAME2','');
    $template->set('VIEW2DISABLED',     'disabled="disabled"');
}

$template->display();
