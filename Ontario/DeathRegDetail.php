<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegDetail.php                                                  *
 *                                                                      *
 *  Display the contents of an Ontario death registration as a detail   *
 *  form with optional ability to update the record.                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      RegYear         year the death was registered                   *
 *      RegNum          registration number within year                 *
 *    or ...                                                            *
 *      RegYear         year the death was registered                   *
 *      OriginalVolume  hardcopy volume number                          *
 *      OriginalPage    page number in hardcopy volume                  *
 *      OriginalItem    ordinal position on page                        *
 *                                                                      *
 *  History:                                                            *
 *      2010/09/02      New format                                      *
 *      2010/12/22      More separation of PHP and HTML                 *
 *                      Fix uninitialized variables                     *
 *      2011/04/07      use smaller text on labels to compress dialog   *
 *                      support keyboard shortcuts                      *
 *      2011/04/21      fill in some fields from preceding record even  *
 *                      if it is not the record with number regnum-1    *
 *      2011/06/16      put more information into mailto                *
 *      2011/08/10      put links to project status in breadcrumbs      *
 *      2011/09/04      use real buttons for next, previous, and        *
 *                      new query                                       *
 *                      add help balloons for read-only fields          *
 *                      add image URL field                             *
 *      2012/01/08      include separate RegYear and RegNum fields in   *
 *                      the read-only version so the next and prev      *
 *                      buttons work                                    *
 *                      add help for RegId fieldstrlen($d2) > 0 &&      *
 *                      default place names include county              *
 *                      widen some input fields                         *
 *                      use textarea for Remarks                        *
 *      2012/03/14      add help for RegId field                        *
 *      2012/03/16      major cleanup of interpreting fields in record  *
 *                      move county names table to countyNames.inc so it*
 *                      can be shared with other scripts                *
 *                      support D_IDIR field for link to family tree    *
 *                      database                                        *
 *      2012/03/31      make all variable names start with lower case   *
 *                      display selection list of matching individuals  *
 *                      if no existing citation to registration         *
 *      2012/04/03      change search for matching individuals so it    *
 *                      supports married names and checks for birth     *
 *                      year match                                      *
 *      2012/04/23      avoid names that cannot be used in regexp       *
 *      2012/05/15      match max input lengths to field sizes          *
 *      2012/06/06      correct search for possible matches             *
 *                      do not force capitalization of addresses        *
 *      2012/06/15      correct size of Father's birth place field      *
 *      2012/07/01      do not capitalize Age                           *
 *                      default registration date and registrar to      *
 *                      values from preceding record                    *
 *      2012/09/12      initialize parent's birthplaces from record     *
 *      2012/10/14      expand cause field to 255 characters            *
 *      2012/11/13      do not uppercase duration of cause              *
 *      2013/02/05      correct maximum size of physician address       *
 *      2013/05/15      default death place to township                 *
 *      2013/05/21      somehow lost database value of death place in   *
 *                      last fix                                        *
 *      2013/06/27      use tinyMCE for editing remarks                 *
 *                      display remarks as text to visitors             *
 *                      adjust lengths of some fields to narrow the     *
 *                      display                                         *
 *                      remove use of Select for informant relationship *
 *                      replacing with abbreviation support             *
 *      2013/08/04      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/10/27      avoid layout change if match list very wide     *
 *      2013/11/15      handle missing database connection gracefully   *
 *      2013/11/21      expand duration field to 32 characters          *
 *      2013/11/25      support RegDomain parameter                     *
 *                      clean up parameter validation                   *
 *                      use class County to obtain county name          *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/01/13      use CSS for form layout instead of table        *
 *      2014/01/15      use class Death to access database              *
 *                      include microfilm reel number field             *
 *                      show maiden names in matches                    *
 *      2014/01/24      show name from database in link                 *
 *      2014/01/27      put maiden surname before married surname       *
 *                      in selection list of matches                    *
 *      2014/02/17      use common routine getSurnameChk to search      *
 *                      for matching individuals by name                *
 *      2014/02/25      remove extra quote mark in hierarchy            *
 *                      <span> tag not closed                           *
 *      2014/03/08      set gender specific class for link to match     *
 *                      in family tree                                  *
 *                      sex select statement initialized by PHP         *
 *                      marital status select initialized by PHP        *
 *                      bad HTML on <h1>                                *
 *      2014/03/10      show names of parents for potential matches     *
 *      2014/03/15      expand birth and death dates to 32 characters   *
 *                      and occupation to 64 characters                 *
 *      2014/03/27      allow autocomplete                              *
 *      2014/04/01      hide residence line if registration year < 1909 *
 *      2014/04/04      order possible matches by name                  *
 *      2014/06/13      add clear button for IDIR association           *
 *      2014/09/03      initialize empty record if there is an          *
 *                      individual whose death already cites the        *
 *                      record identifier                               *
 *      2015/02/06      error in interpretation of death date and age   *
 *      2015/02/11      most fields were not pre-initialized where      *
 *                      there was an existing link to family tree       *
 *      2015/03/03      add header/footer link to township summary      *
 *      2015/03/21      eliminate duplicate matching individuals from   *
 *                      search matches                                  *
 *                      use LegacyIndiv::getIndivs to get potential     *
 *                      matches instead of SQL                          *
 *                      also look for citation to individual death event*
 *                      use Citation::getCitations                      *
 *                      use existing citation to fill in reg link       *
 *      2015/04/08      undefined $imatches if invoked by casual user   *
 *      2015/05/01      if possible display the image by splitting      *
 *                      the window                                      *
 *                      support new parameter ShowImage                 *
 *                      add id attributes to all form rows              *
 *      2015/06/10      do not escape contents of textarea              *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/11      search for matches did not include married name *
 *                      on debug passed xml='p' to getIndivs            *
 *      2015/11/07      text edit widget mispositioned                  *
 *      2015/12/23      sex was not set from citation                   *
 *                      genderClass was not set from citation           *
 *      2016/04/25      replace ereg with preg_match                    *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/06/10      recover from bad IDER value in citation         *
 *                      matching the pattern                            *
 *      2016/11/28      ensure valid definition of county & township    *
 *                      even if errors in parameters                    *
 *      2017/01/18      expand duration field to 64 characters          *
 *                      replace method setField with set                *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/02/07      use class Country                               *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *      2017/07/12      use locationCommon to expand location names     *
 *      2017/07/27      class LegacyCitation renamed to class Citation  *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      change class LegacyIndiv to class Person        *
 *      2017/11/19      use CitationSet in place of getCitations        *
 *      2017/12/13      use PersonSet in place of Person::getPersons    *
 *      2018/03/17      correct alignment of registration number        *
 *      2018/05/28      use template                                    *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/03/21      fix references to undefined $death              *
 *      2020/03/27      $idir was not defined                           *
 *                      calculations failed if RegNum omitted           *
 *      2020/11/28      fix XSS error                                   *
 *      2021/04/24      add residence field                             *
 *      2021/05/29      fix non-numeric regNum                          *
 *                      improve parameter checking                      *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Death.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$regYear                = null;
$regYeartext            = null;
$regNum                 = null;
$regNumtext             = null;
$volume                 = null;
$volumetext             = null;
$page                   = null;
$pagetext               = null;
$item                   = null;
$itemtext               = null;
$linkedName             = null;
$cc                     = 'CA';
$countryObj             = null;
$countryName            = 'Canada';
$domain                 = 'CAON';       // default domain
$domainObj              = null;
$domaintext             = null;
$domainName             = 'Ontario';    // default domain name
$showImage              = false;
$idir                   = null;
$lang                   = 'en';
$imatches               = null;

// override from passed parameters
if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                                "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {           // loop through all input parameters
        $safeValue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$safeValue</td></tr>\n";
        $value                      = trim($value);
        switch(strtolower($key))
        {       // process specific named parameters
            case 'regyear':
            {
                if (preg_match("/^([0-9]{4})$/", $value) &&
                    ($value >= 1860) && ($value <= 2021))
                    $regYear        = $value;
                else
                    $regYeartext    = $safeValue;
                break;
            }       // RegYear passed
    
            case 'regnum':
            {
                if (preg_match("/([0-9]{2,7})/", $value))
                    $regNum         = intval($value);
                else
                    $regNumtext     = $safeValue;
                break;
            }       // RegNum passed
    
            case 'originalvolume':
            {
                $volume             = $value;
                $numVolume          = preg_replace('/[^0-9]/', '', $value);
                break;
            }       // RegNum passed
    
            case 'originalpage':
            {
                if (ctype_digit($value))
                    $page           = $value;
                else
                    $pagetext       = $safeValue;
                break;
            }       // RegNum passed
    
            case 'originalitem':
            {
                if (ctype_digit($value))
                    $item           = $value;
                else
                    $itemtext       = $safeValue;
                break;
            }       // RegNum passed
    
            case 'domain':
            case 'regdomain':
            {
                if (preg_match('/[a-zA-Z]{4,5}/', $value))
                    $domain         = $value;
                else
                    $domaintext     = $safeValue;
                break;
            }       // RegDomain
    
            case 'showimage':
            {
                $showImage          = strtolower(substr($value,0,1)) == 'y';
                break;
            }       // display original image
    
            case 'lang':
            {       // language specification
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // language specification
    
            case 'debug':
            {       // debug handled by common code
                break;
            }       // debug handled by common code
    
            default:
            {       // any other paramters
                $warn   .= "<p>Unexpected parameter $key='$safeValue'.</p>\n";
                break;
            }       // any other paramters
        }       // process specific named parameters
    }           // loop through all input parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by method=get


if(canUser('edit'))
{                   // update the record
    $update             = true;
    $action             = 'Update';
}                   // update the record
else
{                   // display the record
    $update             = false;
    $action             = 'Display';
}                   // display the record

// create Template
$template               = new FtTemplate("DeathRegDetail$action$lang.html");
$template['otherStylesheets']->update(array('filename' => 'DeathRegDetail'));
$trtemplate             = $template->getTranslate();

if (is_string($regYeartext))
    $msg                .= "Registration Year $regYeartext must be a number between 1869 and 2000. ";
else
if (is_null($regYear))
{
    $msg                .= "RegYear omitted. ";
    $regYear            = 0;
}

if (is_string($regNumtext))
{
    $msg                .= "Registration Number $regNumtext must be a number. ";
    $regNum             = 0;
}
else
if (is_null($regNum) && is_null($volume))
{
    $msg                .= "RegNum omitted. ";
    $regNum             = 0;
}
else
if (is_string($volume) && is_string($page) && is_string($item))
{
    $regNum             = $numVolume .
                            str_pad($page, 4, '0', STR_PAD_LEFT) .
                            str_pad($item, 2, '0', STR_PAD_LEFT);
}
$paddedRegNum           = str_pad($regNum,7,"0",STR_PAD_LEFT);

if (is_string($pagetext))
    $msg                .= "Page Number $pageText must be a number. ";
if (is_string($itemtext))
    $msg                .= "Item Number $itemtext must be a number. ";

if (is_string($domain))
{
    $domainObj              = new Domain(array('domain'     => $domain,
                                               'language'   => 'en'));
    if ($domainObj->isExisting())
    {
        $cc                 = substr($domain, 0, 2);
        $countryObj         = new Country(array('code' => $cc));
        $countryName        = $countryObj->getName();
        $domainName         = $domainObj->get('name');
    }
    else
    {
        $domainObj          = null;
        $domaintext         = $domain;
        $domain             = null;
    }
}
if (is_string($domaintext))
{
    $warn                    .= "<p>Domain '$domaintext' is not a supported " .
                                "two character country code followed by ".
                                "a state or province code. 'CAON' assumed.</p>\n";
    $domainName             = 'Domain : ' . $domaintext;
}

// if no error messages Issue the query
if (strlen($msg) == 0)
{       // no errors
    // get the death registration object
    $death              = new Death($domain, $regYear, $regNum);
    $msg                .= $death->getErrors();

    // copy contents into working variables
    $surname            = $death['d_surname'];
    $idir               = $death['d_idir'];
    $givenNames         = $death['d_givennames'];
    $birthDate          = $death['d_birthdate'];
    $sex                = $death['d_sex'];
    if ($sex == 'M')
    {
        $gender         = 0;
    }
    else
    if ($sex == 'F')
    {
        $gender         = 1;
    }
    else
    {
        $gender         = 2;
    }

    $person             = null;

    // if this registration is not already linked to
    // look for individuals who match
    if ($idir == 0 && $update)
    {           // updating
        // check for existing citations to this registration
        $citparms           = array('idsr'      => 98,
                                    'type'      => Citation::STYPE_DEATH,
                                    'srcdetail' => "^$regYear-0*$regNum($|[^0-9])");
        $citations          = new CitationSet($citparms);

        if ($citations->count() > 0)
        {       // citation to death in old location
            $citrow         = $citations->rewind();
            $idir           = $citrow->get('idime');
        }       // citation to death in old location
        else
        {       // check for event citation
            $citparms       = array('idsr'      => 98,
                                    'type'      => Citation::STYPE_EVENT,
                                    'srcdetail' => "^$regYear-0*$regNum($|[^0-9])");
            $citations      = new CitationSet($citparms);
            foreach($citations as $idsx => $citation)
            {
                $ider       = $citation->get('idime');
                $event      = new Event($ider);
                if ($event->isExisting())
                {
                    $idet       = $event->getIdet();
                    if ($idet == Event::ET_DEATH)
                    {
                        $idir       = $event->getIdir();
                        break;
                    }
                }
                else
                {       // bad citation
                    $citation->delete(false);
                }       // bad citation
            }
        }           // check for event citation

        if ($idir == 0 && strlen($surname) > 0 && strlen($givenNames) > 0)
        {           // no existing citation
            if ($debug)
                $warn   .= "<p>Search for match on $surname, $givenNames</p>\n";
            // look for individuals in the family tree whose names are
            // rough matches to the name on the death registration
            // who have the same sex, and who were born within 2 years
            // of the deceased.

            // obtain the birth year for the death registration
            $rxResult           = preg_match('/[0-9]{4}/',
                                             $birthDate,
                                             $matches);
            if ($rxResult > 0)
            {       // explicit birth date includes year of birth
                $birthYear      = intval($matches[0]);
            }       // explicit birth date includes year of birth
            else
            {       // need to calculate birth year from age
                // get the year of death
                $date           = $death['d_date'];
                $rxResult       = preg_match('/[0-9]{4}/',
                                             $date,
                                             $matches);
                if ($rxResult > 0)
                {   // date of death includes a year
                    $deathYear  = intval($matches[0]);
                }   // date of death includes a year
                else
                {   // assume died in year death was registered
                    $deathYear  = $regYear;
                }   // assume died in year death was registered

                // check for all numeric age
                $age            = trim($death['d_age']);
                $rxResult       = preg_match('/([0-9]+)(y|\s|$)/',
                                             $age,
                                             $matches);
                if ($rxResult == 1)
                {   // age contains a number of years
                    $birthYear  = $deathYear - intval($matches[1]);
                }   // age contains a number of years
                else
                {   // no number of years in age
                    $birthYear  = $deathYear;
                }   // no number of years in age
            }       // need to calculate birth year
            // look 2 years on either side of the year
            $birthrange         = array(($birthYear - 2) * 10000,
                                        ($birthYear + 2) * 10000);
            // search for a match on any of the parts of the
            // given name
            $gnameList          = explode(' ', $givenNames);

            // quote the surname value
            $getParms       = array('loose'         => true,
                                    'surname'       => $surname,
                                    'givenname'     => $gnameList,
                                    'gender'        => $gender,
                                    'birthsd'       => $birthrange,
                                    'incmarried'    => true,
                                    'order'         => "tblNX.Surname, tblNX.GivenName, tblIR.BirthSD");
            $imatches   = new PersonSet($getParms);
            if ($debug)
                $warn   .= "<p>DeathRegDetail.php: " . __LINE__ . 
                            " got imatches</p>\n";
        }           // record is initialized with name
        else
        if ($idir > 0)
        {           // record is linked to the family tree
            if (is_null($linkedName))
            {       // found a citation
                $person     = new Person(array('idir' => $idir));
                $linkedName = $person->getName(Person::NAME_INCLUDE_DATES);
            }       // found a citation
        }           // record is linked to the family tree
    }               // updating

    // if we have identified a matching individual in the family tree
    // and the record is not yet initialized
    if ($person &&
        strlen($surname) == 0 &&
        strlen($givenNames) == 0)
    {           // initialize from family tree
        if ($debug)
            $warn   .= "<p>Initialize death record from individual</p>\n";
        $surname        = $person->get('surname');
        $givenNames     = $person->get('givenname');
        $gender     = $person->get('gender');
        if ($gender == 0)
        {
            $sex        = 'M';
            $genderClass    = 'male';
        }
        else
        if ($gender == 1)
        {
            $sex        = 'F';
            $genderClass    = 'female';
        }
        else
        {
            $sex        = '?';
            $genderClass    = 'unknown';
        }
        $birthEvent     = $person->getBirthEvent(true);
        $birthDate      = $birthEvent->getDate();
        $birthSd        = $birthEvent->get('eventsd');
        $birthYear      = floor($birthSd / 10000);
        $age            = $regYear - $birthYear;
        $birthLocation  = $birthEvent->getLocation();
        $deathEvent     = $person->getDeathEvent(true);
        $deathDate      = $deathEvent->getDate();
        $deathLocation  = $deathEvent->getLocation();
        $prefParents    = $person->getPreferredParents();
        $death->set('surname',
                    $surname);
        $death->set('givennames',
                    $givenNames);
        $death->set('sex',
                    $sex);
        $death->set('place',
                    $deathLocation->getName());
        $death->set('date',
                    $deathDate);
        $death->set('birthplace',
                    $birthLocation->getName());
        $death->set('birthdate',
                    $birthDate);
        $death->set('age',
                    $age);
        $death->set('cause',
                    $person->get('deathcause'));
        $death->set('idir',
                    $idir);
        if ($prefParents)
        {           // have preferred parents
            $father     = $prefParents->getHusbName();
            $death->set('d_fathername', $father);
            $mother     = $prefParents->getWifeName();
            $death->set('d_mothername', $mother);
        }           // have preferred parents
    }           // initialize from family tree

    // set $gender to the code in tblIR
    // set $genderClass to the CSS class
    $sex            = $death['d_sex'];
    if ($sex == 'M')
    {           // male
        $gender     = 0;
        $genderClass    = 'male';
    }           // male
    else
    if ($sex == 'F')
    {           // female
        $gender     = 1;
        $genderClass    = 'female';
    }           // female
    else
    {           // unknown
        $gender     = 2;
        $genderClass    = 'unknown';
    }           // unknown

    // get information from the existing link
    if ($idir > 0)
    {           // existing link
        if (is_null($person))
            $person     = new Person(array('idir' => $idir));
        $linkedName     = $person->getName(Person::NAME_INCLUDE_DATES);
        $maidenName     = $person->getSurname();
        if ($maidenName != $surname)
        {       // $surname is not maiden name
            $linkedName = str_replace($maidenName,
                              "($maidenName) $surname",
                              $linkedName);
        }       // $surname is not maiden name
    }           // existing link

    // copy contents into working variables
    // some of the fields may have been changed by the cross-ref code
    $surname        = $death['d_surname'];
    $givenNames     = $death['d_givennames'];
    $date           = $death['d_date'];
    $age            = $death['d_age'];
    $birthDate      = $death['d_birthdate'];

    $subject        = "$domainName Death Registration: number: " .
                        $regYear . '-' . $regNum . ', ' .
                        $givenNames . ' ' . $surname;

    $regCounty      = $death['d_regcounty'];
    $countyObj      = new County($domain, $regCounty);
    $countyName     = $countyObj->get('name');
    $regTownship    = $death['d_regtownship'];
}           // no errors, perform query
else
{           // error detected
    $subject        = "$domainName Death Registration: number: " .
                        $regYear . '-' . $regNum ;
    $regCounty      = '';
    $countyName     = '';
    $regTownship    = 'undefined';
    $sex            = '';
}           // error detected

// internationalization support
$monthsTag          = $trtemplate->getElementById('Months');
if ($monthsTag)
{
    $months         = array();
    foreach($monthsTag->childNodes() as $span)
        $months[]   = trim($span->innerHTML());
}
$lmonthsTag         = $trtemplate->getElementById('LMonths');
if ($lmonthsTag)
{
    $lmonths        = array();
    foreach($lmonthsTag->childNodes() as $span)
        $lmonths[]  = trim($span->innerHTML());
}

$template->set('CONTACTTABLE',  'Deaths');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('LANG',          $lang);

$template->set('REGYEAR',       $regYear);
$template->set('REGNUM',        $regNum);
$template->set('PREVREGNUM',    $regNum-1);
$template->set('PREV5REGNUM',   $regNum-5);
$template->set('NEXTREGNUM',    $regNum+1);
$template->set('NEXT5REGNUM',   $regNum+5);
$template->set('PADDEDREGNUM',  $paddedRegNum);
$template->set('COUNTYNAME',    $countyName);
if ($showImage)
    $template->set('SHOWIMAGE', 'yes');
else
    $template->set('SHOWIMAGE', 'no');
if (isset($death))
{
    $template->set('REGCOUNTY', $death['d_regcounty']);
    $template->set('REGTOWNSHIP',
                   $death['d_regtownship']);
    $template->set('MSVOL',
                   $death['d_msvol']);
    $template->set('SURNAME',
                   $death['d_surname']);
    $template->set('SURNAMESOUNDEX',
                   $death['d_surnamesoundex']);
    $template->set('GIVENNAMES',
                   $death['d_givennames']);
    $template->set('PLACE',
                   $death['d_place']);
    $template->set('RESIDENCE',
                   $death['residence']);
    $template->set('DATE',
                   $death['d_date']);
    $template->set('CALCDATE',
                   $death['d_calcdate']);
    $template->set('AGE',
                   $death['d_age']);
    $template->set('BIRTHDATE',
                   $death['d_birthdate']);
    $template->set('CALCBIRTH',
                   $death['d_calcbirth']);
    $template->set('OCCUPATION',
                   $death['d_occupation']);
    $marStat    = $death['d_marstat'];
    $template->set('BIRTHPLACE',
                   $death['d_birthplace']);
    $template->set('RESPLACE',
                   $death['d_resplace']);
    $template->set('RESONT',
                   $death['d_resont']);
    $template->set('RESCAN',
                   $death['d_rescan']);
    $template->set('CAUSE',
                   $death['d_cause']);
    $template->set('DURATION',
                   $death['d_duration']);
    $template->set('PHYS',
                   $death['d_phys']);
    $template->set('PHYSADDR',
                   $death['d_physaddr']);
    $template->set('INFORMANT',
                   $death['d_informant']);
    $template->set('INFREL',
                   $death['d_infrel']);
    $template->set('INFOCC',
                   $death['d_infocc']);
    $template->set('INFRES',
                   $death['d_infres']);
    $template->set('RELIGION',
                   $death['d_religion']);
    $template->set('FATHERNAME',
                   $death['d_fathername']);
    $template->set('FATHERBPLCE',
                   $death['d_fatherbplce']);
    $template->set('MOTHERNAME',
                   $death['d_mothername']);
    $template->set('MOTHERBPLCE',
                   $death['d_motherbplce']);
    $template->set('HUSBANDNAME',
                   $death['d_husbandname']);
    $template->set('REMARKS',
                   $death['d_remarks']);
    $template->set('BURPLACE',
                   $death['d_burplace']);
    $template->set('BURDATE',
                   $death['d_burdate']);
    $template->set('UNDERTKR',
                   $death['d_undertkr']);
    $template->set('UNDERTKRADDR',
                   $death['d_undertkraddr']);
    $template->set('REGDATE',
                   $death['d_regdate']);
    $template->set('REGISTRAR',
                   $death['d_registrar']);
    $template->set('RECORDEDBY',
                   $death['d_recordedby']);
    $template->set('IMAGE',
                   $death['d_image']);
    $template->set('ORIGINALVOLUME',
                   $death['d_originalvolume']);
    $template->set('ORIGINALPAGE',
                   $death['d_originalpage']);
    $template->set('ORIGINALITEM',
                   $death['d_originalitem']);
}
else
{
    $template->set('REGCOUNTY',     '');
    $template->set('REGTOWNSHIP',   '');
    $template->set('MSVOL',         '');
    $template->set('SURNAME',       '');
    $template->set('SURNAMESOUNDEX','');
    $template->set('GIVENNAMES',    '');
    $template->set('PLACE',         'Ontario, Canada');
    $template->set('DATE',          $regYear);
    $template->set('CALCDATE',      $regYear);
    $template->set('AGE',           '');
    $template->set('BIRTHDATE',     $regYear);
    $template->set('CALCBIRTH',     '');
    $template->set('OCCUPATION',    '');
    $marStat    = '?';
    $template->set('BIRTHPLACE',    '');
    $template->set('RESPLACE',      '');
    $template->set('RESONT',        '');
    $template->set('RESCAN',        '');
    $template->set('CAUSE',         '');
    $template->set('DURATION',      '');
    $template->set('PHYS',          '');
    $template->set('PHYSADDR',      '');
    $template->set('INFORMANT',     '');
    $template->set('INFREL',        '');
    $template->set('INFOCC',        '');
    $template->set('INFRES',        '');
    $template->set('RELIGION',      '');
    $template->set('FATHERNAME',    '');
    $template->set('FATHERBPLCE',   '');
    $template->set('MOTHERNAME',    '');
    $template->set('MOTHERBPLCE',   '');
    $template->set('HUSBANDNAME',   '');
    $template->set('REMARKS',       '');
    $template->set('BURPLACE',      '');
    $template->set('BURDATE',       '');
    $template->set('UNDERTKR',      '');
    $template->set('UNDERTKRADDR',  '');
    $template->set('REGDATE',       '');
    $template->set('REGISTRAR',     '');
    $template->set('RECORDEDBY',    '');
    $template->set('IMAGE',         '');
    $template->set('ORIGINALVOLUME','');
    $template->set('ORIGINALPAGE',  '');
    $template->set('ORIGINALITEM',  '');
}

if ($sex == 'M')
{
    $template->updateTag('SexM',
                         array('selected' => "selected='selected'"));
    $template->updateTag('SexF',
                         array('selected' => ' '));
    $template->updateTag('SexU',
                         array('selected' => ' '));
}
else
if ($sex == 'F')
{
    $template->updateTag('SexF',
                         array('selected' => "selected='selected'"));
    $template->updateTag('SexM',
                         array('selected' => ' '));
    $template->updateTag('SexU',
                         array('selected' => ' '));
}
else
{
    $template->updateTag('SexU',
                         array('selected' => "selected='selected'"));
    $template->updateTag('SexM',
                         array('selected' => ' '));
    $template->updateTag('SexF',
                         array('selected' => ' '));
}

switch(strtoupper($marStat))
{
    case 'S':
        $template->updateTag('MarStatS',
                         array('selected' => "selected='selected'"));
        $template->updateTag('MarStatM',
                         array('selected'   => ' '));
        $template->updateTag('MarStatW',
                         array('selected'   => ' '));
        $template->updateTag('MarStatD',
                         array('selected'   => ' '));
        $template->updateTag('MarStatU',
                         array('selected'   => ' '));
        break;

    case 'M':
        $template->updateTag('MarStatS',
                         array('selected'   => ' '));
        $template->updateTag('MarStatM',
                         array('selected' => "selected='selected'"));
        $template->updateTag('MarStatW',
                         array('selected'   => ' '));
        $template->updateTag('MarStatD',
                         array('selected'   => ' '));
        $template->updateTag('MarStatU',
                         array('selected'   => ' '));
        break;

    case 'W':
        $template->updateTag('MarStatS',
                         array('selected'   => ' '));
        $template->updateTag('MarStatM',
                         array('selected'   => ' '));
        $template->updateTag('MarStatW',
                         array('selected' => "selected='selected'"));
        $template->updateTag('MarStatD',
                         array('selected'   => ' '));
        $template->updateTag('MarStatU',
                         array('selected'   => ' '));
        break;

    case 'D':
        $template->updateTag('MarStatS',
                         array('selected'   => ' '));
        $template->updateTag('MarStatM',
                         array('selected'   => ' '));
        $template->updateTag('MarStatW',
                         array('selected'   => ' '));
        $template->updateTag('MarStatD',
                         array('selected' => "selected='selected'"));
        $template->updateTag('MarStatU',
                         array('selected'   => ' '));
        break;

    case '?':
        $template->updateTag('MarStatS',
                         array('selected'   => ' '));
        $template->updateTag('MarStatM',
                         array('selected'   => ' '));
        $template->updateTag('MarStatW',
                         array('selected'   => ' '));
        $template->updateTag('MarStatD',
                         array('selected'   => ' '));
        $template->updateTag('MarStatU',
                         array('selected' => "selected='selected'"));
        break;


    default:
        $template->updateTag('MarStatS',
                         array('selected'   => ' '));
        $template->updateTag('MarStatM',
                         array('selected'   => ' '));
        $template->updateTag('MarStatW',
                         array('selected'   => ' '));
        $template->updateTag('MarStatD',
                         array('selected'   => ' '));
        $template->updateTag('MarStatU',
                         array('selected'   => ' '));
        break;

}

if ($debug)
    $template->set('DEBUG', 'Y');
else
    $template->set('DEBUG', 'N');

if ($idir > 0)
{           // link to family tree database
    $template->updateTag('LinkRowSet',
                         array('idir'       => $idir,
                               'genderClass'=> $genderClass,
                               'linkedName' => $linkedName));
    $template->updateTag('LinkRowMatch',    null);
}           // link to family tree database
else
if ($imatches && $imatches->count() > 0)
{           // matched to some individuals in database
    $template->updateTag('LinkRowSet',  null);
    $matches                        = array();
    foreach($imatches as $iidir => $person)
    {       // loop through results
        $isex                       = $person->get('gender');
        $newMatch                   = array();
        $newMatch['idir']           = $iidir;
        if ($isex == Person::MALE)
        {
            $newMatch['sexclass']   = 'male';
            $childrole              = 'son';
            $spouserole             = 'husband';
        }
        else
        if ($isex == Person::FEMALE)
        {
            $newMatch['sexclass']   = 'female';
            $childrole              = 'daughter';
            $spouserole             = 'wife';
        }
        else
        {
            $newMatch['sexclass']   = 'unknown';
            $childrole              = 'child';
            $spouserole             = 'spouse';
        }

        $iname                  = $person->getName(Person::NAME_INCLUDE_DATES);
        $parents                = $person->getParents();
        $comma                  = ' ';
        foreach($parents as $idcr => $set)
        {       // loop through parents
            $father             = $set->getHusbName();
            $mother             = $set->getWifeName();
            $iname              .= "$comma$childrole of $father and $mother";
            $comma              = ', ';
        }       // loop through parents

        $families               = $person->getFamilies();
        $comma                  = ' ';
        foreach ($families as $idmr => $set)
        {       // loop through families
            if ($isex == Person::FEMALE)
                $spouse         = $set->getHusbName();
            else
                $spouse         = $set->getWifeName();
            $iname              .= "$comma$spouserole of $spouse";
            $comma              = ', ';
        }       // loop through families
        $newMatch['name']       = $iname;
        $matches[$iidir]        = $newMatch;
    }       // loop through results
    $template->updateTag('match',   $matches);
}           // matched to some individuals in database
else
{           // no matches
    $template->updateTag('LinkRowSet',  null);
    $template->updateTag('LinkRowMatch',    null);
}           // no matches

if ($regYear < 1909)
{
    $template->updateTag('ResLengthRow', null);
}

$template->display();
showTrace();
