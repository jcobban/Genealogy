<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getFamilyOfXml.php													*
 *																		*
 *  Return an XML file containing a summary of individuals belonging to	*
 *  a particular family, and how they match against a suggested family	*
 *  from a census.														*
 *																		*
 *  The script first identifies all of the members of the immediate		*
 *  family of the individual, that is parents, siblings, and children,	*
 *  and then compares their given names to the given names of the		*
 *  family in the census.												*
 *																		*
 *  The response is an XML file that suggests the IDIR number for each	*
 *  individual in the census family.									*
 *																		*
 *  <names>																*
 *    <parms>															*
 *		<name>$value</name> [repeated...]								*
 *    </parms>															*
 *    <msg>error messages if any, omitted if none</msg>					*
 *    <indiv>															*
 *		<page>page</page>												*
 *		<line>line on page</line>										*
 *		<censurname>surname from census</censurname>					*
 *		<cengiven>givennames from census</cengiven>						*
 *		<cenbyear>birth year estimate from census</cenbyear>			*
 *		<idir>idir of match in family tree</idir>						*
 *		<surname>surname of match</surname>								*
 *		<givenname>given name of match</givenname>						*
 *		<birthd>birth date of match</birthd>							*
 *		<deathd>death date of match</deathd>							*
 *		<role>relationship to key individ</role>						*
 *    </indiv> [repeated...]											*
 *  </names>															*
 *																		*
 *  Parameters:															*
 *																		*
 *		idir		    identification of an individual in the family	*
 *					    tree database									*
 *		census			census identifier, country code plus year		*
 *		province		province, for pre-confederation censuses		*
 *						of Canada										*
 *		district		district number within the census				*
 *		subDistrict		sub-district identifier with the district		*
 *		division		division within the sub-district				*
 *		page			page within the division 						*
 *		line			line on the page of the census containing		*
 *						individual										*
 *		family			family number of selected individual			*
 *																		*
 *  History:															*
 *		2012/09/10		created											*
 *		2013/06/01		remove use of deprecated interfaces				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/22		use LegacyIndiv::getBirthEvent					*
 *		2014/12/26		getFamilies result is indexed on idmr			*
 *		2015/01/01		use getBirthDate and gettDeathDate				*
 *		2015/01/07		change require to require_once					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/29		use RecordSet									*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// some given names have familiar forms that start with different
// letters than the formal name.
$nicknames	= array(
    			'bell'		=> 'Is',	// Isabell
    			'bella'		=> 'Is',	// Isabella
    			'bert'		=> 'Al',	// Albert
    			'bertie'	=> 'Al',	// Albert
    			'berttie'	=> 'Al',	// Albert
    			'berty'		=> 'Al',	// Albert
    			'bessie'	=> 'El',	// Elizabeth
    			'bessie'	=> 'El',	// Elizabeth
    			'bessy'		=> 'El',	// Elizabeth
    			'betsey'	=> 'El',	// Elizabeth
    			'betsy'		=> 'El',	// Elizabeth
    			'bettsy'	=> 'El',	// Elizabeth
    			'betty'		=> 'El',	// Elizabeth
    			'bill'		=> 'Wi',	// William
    			'catherine'	=> 'Ka',	// Kate
    			'catharine'	=> 'Ka',	// Kate
    			'dick'		=> 'Ri',	// Richard
    			'dougald'	=> 'Du',	// Dugald
    			'dougall'	=> 'Du',	// Dugall
    			'dugald'	=> 'Do',	// Dougald
    			'dugall'	=> 'Do',	// Dougall
    			'earnest'	=> 'Er',	// Ernest
    			'effa'		=> 'Eu',	// Euphemia
    			'effay'		=> 'Eu',	// Euphemia
    			'effie'		=> 'Eu',	// Euphemia
    			'efie'		=> 'Eu',	// Euphemia
    			'effy'		=> 'Eu',	// Euphemia
    			'elizabeth'	=> 'Be',	// Betsy
    			'elisabeth'	=> 'Be',	// Betsy
    			'ellen'		=> 'He',	// Helen
    			'eraminta'	=> 'Ar',	// Aramintha
    			'erbert'	=> 'He',	// Herbert
    			'erebella'	=> 'Ar',	// Arabella
    			'eretha'	=> 'Ar',	// Aretha
    			'ernest'	=> 'Ea',	// Earnest
    			'erron'		=> 'Aa',	// Aaron
    			'ersely'	=> 'Ur',	// Ursula
    			'hanna'		=> 'An',	// Anna
    			'hannah'	=> 'An',	// Anna
    			'hariat'	=> 'He',	// Henrietta
    			'hariet'	=> 'He',	// Henrietta
    			'hariett'	=> 'He',	// Henrietta
    			'hariot'	=> 'He',	// Henrietta
    			'harret'	=> 'He',	// Henrietta
    			'harreth'	=> 'He',	// Henrietta
    			'harrette'	=> 'He',	// Henrietta
    			'harriet'	=> 'He',	// Henrietta
    			'harriete'	=> 'He',	// Henrietta
    			'harriett'	=> 'He',	// Henrietta
    			'harrie'	=> 'He',	// Henry
    			'harry'		=> 'He',	// Henry
    			'hary'		=> 'He',	// Henry
    			'hattie'	=> 'He',	// Henrietta
    			'hatty'		=> 'He',	// Henrietta
    			'helen'		=> 'El',	// Ellen
    			'hellen'	=> 'El',	// Ellen
    			'henry'		=> 'Ha',	// Harry
    			'jack'		=> 'Jo',	// John
    			'jean'		=> 'Ja',	// Jane
    			'jeanet'	=> 'Ja',	// Janet
    			'jeanett'	=> 'Ja',	// Janet
    			'jeanette'	=> 'Ja',	// Janet
    			'jeanettie'	=> 'Ja',	// Janet
    			'jeannett'	=> 'Ja',	// Janet
    			'jeannette'	=> 'Ja',	// Janet
    			'jenat'		=> 'Ja',	// Janet
    			'jenet'		=> 'Ja',	// Janet
    			'jenett'	=> 'Ja',	// Janet
    			'jenetta'	=> 'Ja',	// Janet
    			'jenette'	=> 'Ja',	// Janet
    			'jenitt'	=> 'Ja',	// Janet
    			'jennat'	=> 'Ja',	// Janet
    			'jennet'	=> 'Ja',	// Janet
    			'jennett'	=> 'Ja',	// Janet
    			'jennetta'	=> 'Ja',	// Janet
    			'jennette'	=> 'Ja',	// Janet
    			'jenney'	=> 'Ja',	// Jane
    			'jennie'	=> 'Ja',	// Jane
    			'jennitt'	=> 'Ja',	// Janet
    			'jenny'		=> 'Ja',	// Jane
    			'jeorge'	=> 'Ge',	// George
    			'jessee'	=> 'Ja',	// Janet
    			'jessey'	=> 'Ja',	// Janet
    			'jessie'	=> 'Ja',	// Janet
    			'jessy'		=> 'Ja',	// Janet
    			'jno'		=> 'Jo',	// John
    			'jno.'		=> 'Jo',	// John
    			'katalina'	=> 'Ca',	// Catherine
    			'kate'		=> 'Ca',	// Catherine
    			'katharine'	=> 'Ca',	// Catherine
    			'katherine'	=> 'Ca',	// Catherine
    			'kathleen'	=> 'Ca',	// Catherine
    			'kathrine'	=> 'Ca',	// Catherine
    			'katie'		=> 'Ca',	// Catherine
    			'kattie'	=> 'Ca',	// Catherine
    			'katy'		=> 'Ca',	// Catherine
    			'liza'		=> 'El',	// Eliza
    			'lizebeth'	=> 'El',	// Elizabeth
    			'lizey'		=> 'El',	// Elizabeth
    			'lizie'		=> 'El',	// Elizabeth
    			'lizy'		=> 'El',	// Elizabeth
    			'lizzi'		=> 'El',	// Elizabeth
    			'lizzie'	=> 'El',	// Elizabeth
    			'lizzy'		=> 'El',	// Elizabeth
    			'lotte'		=> 'Ch',	// Charlotte
    			'lottey'	=> 'Ch',	// Charlotte
    			'lottie'	=> 'Ch',	// Charlotte
    			'lotty'		=> 'Ch',	// Charlotte
    			'molly'		=> 'Ma',	// Mary
    			'nancie'	=> 'An',	// Ann
    			'nancy'		=> 'An',	// Ann
    			'nellie'	=> 'El',	// Ellen
    			'nelly'		=> 'El',	// Ellen
    			'neomie'	=> 'Na',	// Naomi
    			'netty'		=> 'He',	// Henrietta
    			'nettie'	=> 'He',	// Henrietta
    			'nevan'		=> 'Ni',	// Niven
    			'neven'		=> 'Ni',	// Niven
    			'nevin'		=> 'Ni',	// Niven
    			'Nial'		=> 'Ne',	// Neil
    			'Niel'		=> 'Ne',	// Neil
    			'peggy'		=> 'Ma',	// Margaret
    			'polly'		=> 'Ma',	// Mary
    			'shalotte'	=> 'Ch',	// Charlotte
    			'sharlot'	=> 'Ch',	// Charlotte
    			'sharlote'	=> 'Ch',	// Charlotte
    			'sharlott'	=> 'Ch',	// Charlotte
    			'sharlotte'	=> 'Ch',	// Charlotte
    			'sharlottie'	=> 'Ch',	// Charlotte
    			'Teddy'		=> 'Th',	// Theodore
    			'Tom'		=> 'Th',	// Thomas
    			'Tomas'		=> 'Th',	// Thomas
    			'Tommy'		=> 'Th'		// Thomas
    			);

// valid census years
$censusYears	= array(1851	=> true,
    				1861	=> true,
    				1871	=> true,
    				1881	=> true,
    				1891	=> true,
    				1901	=> true,
    				1906	=> true,
    				1911	=> true,
    				1916	=> true);

// valid province codes for pre-confederation censuses
$provCodes		= array('CE'	=> true,
    				'CW'	=> true,
    				'NB'	=> true,
    				'NS'	=> true);

/************************************************************************
 *  emitPerson																*
 *																		*
 *  Emit the XML tags to describe an individual from the family tree.		*
 *																		*
 ************************************************************************/
function emitPerson($indiv)
{
$idir	= $indiv->getIdir();

print "\t<idir>$idir</idir>\n";

// privatize birth and date information if required
if ($indiv->isOwner())
{		// do not privatize dates
    $bprivlim	= 9999;
    $dprivlim	= 9999;
}		// do not privatize dates
else
{		// privatize dates
    $bprivlim	= intval(date('Y')) - 97;
    $dprivlim	= intval(date('Y')) - 72;
}		// privatize dates

$surname	= htmlentities($indiv->getSurname(),ENT_XML1);
$givenname	= htmlentities($indiv->getGivenName(),ENT_XML1);

$birthd		= $indiv->getBirthDate();
$deathd		= $indiv->getDeathDate();

print "\t<surname>$surname</surname>\n";
print "\t<givenname>$givenname</givenname>\n";
print "\t<birthd>$birthd</birthd>\n";
print "\t<deathd>$deathd</deathd>\n";
}		// emitPerson

/************************************************************************
 *  function matchMember												*
 *																		*
 *  Search the accumulated family for a match on the surname, given		*
 *  names, and year of birth.											*
 *																		*
 *  Input:																*
 *		$cenSurname		surname from census								*
 *		$cenGiven		given names from census							*
 *		$cenByear		birth year from census							*
 ************************************************************************/
function matchMember($cenSurname, 
    		     $cenGiven,
    		     $cenByear)
{
//print "<trace>matchMember('$cenSurname', '$cenGiven', '$cenByear')</trace>\n";

global	$immFamily;
global	$nickNames;
$givenNames	= explode(" ", strtolower($cenGiven));
//print "<trace>\$givenNames: ";
//print_r($givenNames); 
//print "</trace>\n";
foreach($immFamily as $idir => $member)
{
//print "<trace>idir=$idir</trace>\n";
    $surname	= $member->getSurname();
    $given		= $member->getGivenName();
    $evBirth	= $member->getBirthEvent(false);
    if ($evBirth)
        $birthyear	= floor(($evBirth->get('eventsd'))/10000);
    else
        $birthyear	= -9999;
// print "<trace>\$surname='$surname', \$given='$given', \$birthyear='$birthyear'</trace>\n";

    // if birth years inconsistent, keep searching
    if (abs($birthyear - $cenByear) > 3)
        continue;	// keep searching
//print "<trace>matched on birth year</trace>\n";

    // if the surnames don't match, keep searching
    if (soundex($cenSurname) != soundex($surname))
        continue;	// keep searching
//print "<trace>matched on surname</trace>\n";

    // perform loose comparison of given names
    // this is the most complex comparison, so it is left for last
    $givenMatch	= false;
    foreach($givenNames as $i => $name)
    {		// try to match each part of given name
        $length	= strlen($name);
        if ($length >= 3)
        {		// compare first part of name
    		$pattern	= substr($name, 0, 3);
        }		// compare first part of name
        else if ($length == 2)
        {		// two character name
    		if (substr($name, 1, 1) == '.')
    		    $pattern	= substr($name, 0, 1);
    		else
    		    $pattern	= $name;
        }		// two character name
        else if ($length == 1)
        {		// one character name
    		$pattern	= $name;
        }		// one character name
        else
        {		// zero length name!
    		continue;
        }		// zero length name!

        // compare portion of name to each of the portions
        // in the given name from the family tree
        $pattern	="/\\b$pattern/i";
//print "<trace>search given name for '$pattern'</trace>\n";
        if (preg_match($pattern, $given))
        {
    		return $member;	// found match
        }
        else
//print "<trace>preg_match('$pattern', '$given') failed</trace>\n";

        // frequently the name as it appears in the census
        // is a familiar form that is not part of the formal
        // registered name of the individual
        if (array_key_exists($name, $nicknames))
        {	// also check nicknames
    		$pattern	= $nicknames[$name];
    		if (preg_match("/\\b$pattern/i", $given))
    		{	// match on nickname
    		    return $member;	// found match
    		}	// match on nickname
        }	// also check nicknames
    }		// try to match each part of given name
}			// loop through all family members
return null;
}		// matchMember

// display the results
print "<?xml version='1.0' encoding='UTF-8'?" . ">\n";

// construct search pattern from parameters
$msg		= '';
$idir		= null;
$indiv		= null;	// instance of Person
$line		= null;
$census		= null;
$censusYear		= 0;
$province		= '';
$district		= null;
$subDistrict	= null;
$division		= '';
$page		= null;
$family		= null;

foreach($_GET as $key => $value)
{
    switch($key)
    {		// act on specific keys
        // identification of an individual in the family
        // 	tree database
        case 'idir':
        {
    		$idir		= $value;
    		if (ctype_digit($idir))
    		{		// idir parameter numeric
    		    $idir	= (int)$idir;
    		    try {	// to validate value if idir parameter
    			$indiv	= new Person(array('idir' => $idir));
    		    }	// to validate value if idir parameter
    		    catch (Exception $e)
    		    {	// unable to construct instance of Person
    			print "\t<msg>" . $e->getMessage() . "</msg>\n";
    		    }	// unable to construct instance of Person
    		}		// idir parameter present
    		else
    		    $msg	.= "idir='$idir' not numeric. ";
    		break;
        }		// idir

        // census identifier, country code plus year
        case 'census':
        {
    		$census		= $value;
    		if (strlen($census) != 6)
    		    $msg	.= "census='$census' not 6 characters. ";
    		$censusYear	= substr($value, 2);
    		if (ctype_digit($censusYear) &&
    		    array_key_exists($censusYear, $censusYears))
    		    $censusYear	= (int)$censusYear;
    		else
    		    $msg	.=
    			"Census year '$censusYear' not a valid year. ";
    		break;
        }		// census

        // province, for pre-confederation censuses of Canada
        case 'province':
        {
    		$province	= $value;
    		if (!array_key_exists($value, $provCodes))
    		    $msg	.= "province='$province' not valid. ";
    		break;
        }		// province

        // district number within the census
        case 'district':
        {
    		$district	= $value;
    		if (ctype_digit($district))
    		    $district	= (int)$district;
    		else
    		    $msg	.= "district='$district' not numeric. ";
    		break;
        }		// district

        // sub-district identifier with the district
        case 'subDistrict':
        {
    		$subDistrict	= $value;
    		break;
        }		// subDistrict

        // division within the sub-district
        case 'division':
        {
    		$division	= $value;
    		break;
        }		// division

        // page within the division
        case 'page':
        {
    		$page		= $value;
    		if (ctype_digit($page))
    		    $page	= (int)$page;
    		else
    		    $msg	.= "page='$page' not numeric. ";
    		break;
        }		// page

        // line on the page of the census containing individual
        case 'line':
        {
    		$line		= $value;
    		if (ctype_digit($line))
    		    $line	= (int)$line;
    		else
    		    $msg	.= "line='$line' not numeric. ";
    		break;
        }		// line

        // family number of selected individual
        case 'family':
        {
    		$family		= $value;
    		if (ctype_digit($family))
    		    $family	= (int)$family;
    		else
    		    $msg	.= "family='$family' not numeric. ";
    		break;
        }		// family

    }		// act on specific keys
}			// loop through parameters

if ($idir === null)
    $msg	.= 'Missing mandatory parameter idir. ';
if ($census === null)
    $msg	.= 'Missing mandatory parameter census. ';
if ($province === '' && $censusYear < 1867)
    $msg	.= 'Missing mandatory parameter province. ';
if ($district === null)
    $msg	.= 'Missing mandatory parameter district. ';
if ($subDistrict === null)
    $msg	.= 'Missing mandatory parameter subDistrict. ';
if ($division === null)
    $msg	.= 'Missing mandatory parameter division. ';
if ($line === null)
    $msg	.= 'Missing mandatory parameter line. ';
if ($family === null)
    $msg	.= 'Missing mandatory parameter family. ';

// perform a query for the identified family
if (strlen($msg) == 0)
{		// no errors so far
    if ($censusYear < 1867)
        $family	= new RecordSet("Census$censusYear",
    				array('Province'	=> $province,
    				      'District'	=> $district,
    				      'SubDistrict'	=> $subDistrict,
    				      'Division'	=> $division,
    				      'Family'		=> $family,
    				      'order'		=> 'Page, Line'));
    else
        $family	= new RecordSet("Census$censusYear",
    				array('District'	=> $district,
    				      'SubDistrict'	=> $subDistrict,
    				      'Division'	=> $division,
    				      'Family'		=> $family,
    				      'order'		=> 'Page, Line'));

    // execute the query
    if ($family->count() == 0)
        $msg	.= "family='$family' not found in census division. ";
}		// no errors so far

// top node of XML result
print "<names buttonId='doIdir$line'>\n";

// document parameters to script
print "  <parms>\n";
foreach($_GET as $key => $value)
{
    print "    <$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
}
print "  </parms>\n";

// if the parameters failed validation, report the problem
if (strlen($msg) > 0)
{		// error in validation
    print "  <msg>\n";
    print htmlentities($msg,ENT_XML1);
    print "  </msg>\n";
    print "</names>\n";
    exit;
}		// error in validation

// enumerate immediate family of identified individual
$immFamily		= array();
$roles		= array();
$immFamily[$idir]	= $indiv;
$roles[$idir]	= 'self';

// identify parents
$allparents	= $indiv->getParents();
if (count($allparents) > 0)
{		// at least one set of parents
    for($ip = 0; $ip < count($allparents); $ip++)
    {		// loop through all sets of parents
        $parents	= $allparents[$ip];
        $dadid	= $parents->get('idirhusb');
        if ($dadid > 0)
        {		// father identified
    		try {
    		    $immFamily[$dadid]	= new Person(array('idir' => $dadid));
    		    $roles[$dadid]	= 'father';
    		}
    		catch (Exception $e)
    		{	// failed to get father
    		    // ignore
    		}	// failed to get father
        }		// father identified
        $momid	= $parents->get('idirwife');
        if ($momid > 0)
        {		// mother identified
    		try {
    		    $mom	= new Person(array('idir' => $momid));
    		    $mom->setSurname($parents->get('wifemarrsurname'));
    		    $immFamily[$momid]	= $mom;
    		    $roles[$momid]	= 'mother';
    		}
    		catch (Exception $e)
    		{	// failed to get mother
    		    // ignore
    		}	// failed to get mother
        }		// mother identified
    }		// loop through all sets of parents
}		// at least one set of parents

// identify spouses and children
$families	= $indiv->getFamilies();
foreach($families as $fidmr => $family)
{		// loop through families
    if ($indiv->getGender() == Person::FEMALE)
    {		// female
        $spsid	= $family->get('idirhusb');
    }		// female
    else
    {		// male
        $spsid	= $family->get('idirwife');
    }		// male

    // information about spouse
    if ($spsid > 0)
    {		// have a spouse id
        try {
    		$spouse	= new Person(array('idir' => $spsid));
    		$immFamily[$spsid]	= $spouse;
    		if ($spouse->getGender() == Person::FEMALE)
    		{		// spouse is female
    		    $spouse->setSurname($family->get('wifemarrsurname'));
    		    $roles[$spsid]	= 'wife';
    		}		// spouse is female
    		else
    		    $roles[$spsid]	= 'husband';
        }
        catch (Exception $e)
        {	// failed to get mother
    		// ignore
        }	// failed to get mother
    }		// have a spouse id

    // display information about children
    $children	= $family->getChildren();
    if (count($children) > 0)
    {	// found at least one child record
        for ($ic = 0; $ic < count($children); $ic++)
        {	// loop through all child records
    		$child		= $children[$ic];
    		$cid		= $child->getIdir();
    		$immFamily[$cid]	= $child;
    		$roles[$cid]	= 'child';
        }	// loop through all child records
    }	// found at least one child record
}		// loop through marriages

// compare census records to immediate family
foreach($family as $member)
{		// loop through all family members
    print "    <indiv>\n";
    $page		= $member->get('page');
    $cline		= $member->get('line');
    $censurname	= $member->get('surname');
    $cengiven	= $member->get('givennames');
    $cenbyear	= $member->get('byear');
    print "\t<page>$page</page>\n";
    print "\t<line>$cline</line>\n";
    print "\t<censurname>" . htmlentities($censurname,ENT_XML1) . "</censurname>\n";
    print "\t<cengiven>" . htmlentities($cengiven,ENT_XML1) . "</cengiven>\n";
    print "\t<cenbyear>$cenbyear</cenbyear>\n";
    if ($cline == $line)
    {		// key individual
        // print("\t<trace>self</trace>\n";
        emitPerson($indiv);
        print "\t<role>self</role>\n";
    }		// key individual
    else
    {		// other member
        // print("\t<trace>other</trace>\n";
        try {
    		$member	= matchMember($member->get('surname'), 
    				      $member->get('givennames'),
    				      $member->get('byear'));
        }
        catch(Exception $e)
        {
    		print "\t<msg>" . $e->getMessage() . "</msg>\n";
        }
        // print("\t<trace>after matchMember</trace>\n";
        if (is_null($member))
        {
    		print "\t<idir></idir>\n";
    		print "\t<role>unknown</role>\n";
        }
        else
        {		// have a match
    		try {
    		    emitPerson($member);
    		}
    		catch(Exception $e)
    		{
    		    print "\t<msg>" . $e->getMessage() . "</msg>\n";
    		}
    		$role	= $roles[$member->getIdir()];
    		print "\t<role>$role</role>\n";
        }		// have a match
    }		// other member

    print "    </indiv>\n";
}		// loop through all family members

print("</names>\n");	// close off top node of XML result

