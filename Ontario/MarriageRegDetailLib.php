<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegDetailLib.php						*
 *									*
 *  Display the contents of an individual participant in a marriage	*
 *  registration as a detail form with optional ability to update	*
 *  the record.								*
 *									*
 *  History:								*
 *	2011/03/14	use MDB2					*
 *	2011/03/19	eliminate use of putTextInput function		*
 *			add display of calculated birth year of partners*
 *			widen location fields to show "24" characters	*
 *			escape witness names				*
 *			("O'Brien" would have failed)			*
 *	2011/03/29	expand some fields				*
 *	2011/04/04	use gender based label styles			*
 *	2011/04/06	do not capitalize occupation			*
 *			do not display witness info here		*
 *	2011/04/07	put role in separate line to shrink dialog	*
 *			horizontally					*
 *	2011/09/04	disable birth year so tab skips it		*
 *	2011/11/09	allow value [Blank] for age			*
 *			initialize birth year from age only if not set	*
 *			in DB						*
 *	2012/03/28	support M_IDIR field for link to family tree	*
 *			database					*
 *	2012/09/02	include hidden field to feed M_IDIR value to	*
 *			update						*
 *	2013/02/05	change BirthYear class and remove maximum length*
 *	2013/02/27	add selection list for matches on bride		*
 *			and groom					*
 *	2013/03/15	more gracefully handle null row passed to	*
 *			dispParticipant					*
 *	2013/10/20	correct syntax error in HTML			*
 *	2014/01/10	migrate to CSS from tables			*
 *			parents' names are 64 characters long		*
 *			do not use gender classes for labels		*
 *			match bride against married names as well as	*
 *			maiden name					*
 *			show both married and maiden name in matches	*
 *	2014/01/18	reduce width of marital status field		*
 *	2014/01/21	use MarriageParticipant record			*
 *			add support for displaying minister		*
 *			display full name and dates for resolved link	*
 *			instead of reusing name from registration	*
 *	2014/01/27	handle bad IDIR for marriage participant	*
 *	2014/01/28	only include individuals with occupation or	*
 *			minister or priest in matches for officiant	*
 *	2014/02/10	show names of parents for potential matches	*
 *			include maiden name in search for matches	*
 *	2014/02/17	use common routine getSurnameChk to search	*
 *			for matching individuals by surname		*
 *	2014/02/25	lookup array $surpatterns moved to common.inc	*
 *	2014/06/15	order name matches alphabetically		*
 *	2014/12/26	use Person::getBirthEvent and			*
 *			getDeathEvent to get birth and death dates	*
 *	2015/01/06	fill in birthplace and parents names for new	*
 *			marriage registration that is already cited	*
 *			add clear IDIR association button		*
 *	2015/01/26	$warn not declared				*
 *	2015/02/26	get name with birth and death dates from	*
 *			Person::getName					*
 *	2015/03/21	eliminate duplicate matching individuals from	*
 *			search matches					*
 *	2015/04/08	use Person::getIndivs to get list of		*
 *			matching individuals and Person::getName	*
 *			to display name, maiden name, birth date, and	*
 *			death date					*
 *	2015/06/18	missing clear after occupation and marstat	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/07	search for matches fails if father's surname	*
 *			ends in a space					*
 *	2015/10/28	small change to parameter list of		*
 *			Person::getIndivs				*
 *	2016/06/08	only copy birthplace from individual record	*
 *			if there is no existing MarriageIndi record	*
 *	2017/01/23	do not use htmlspecchars to build input values	*
 *	2017/03/17	birth year displayed as text not <input>	*
 *	2017/03/19	use preferred parameters to new Person		*
 *	2017/09/12	use get( and set(				*
 *	2017/09/24	class LegacyLocation renamed to Location	*
 *	2017/10/13	class LegacyIndiv renamed to class Person	*
 *	2017/12/13	use class PersonSet				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';

/************************************************************************
 *  dispParticipant							*
 *									*
 *  Construct the form elements for one participant to the marriage.	*
 *									*
 *  Input:								*
 *	$participant		MarriageParticipant record		*
 ************************************************************************/
function dispParticipant($participant)
{
    global $readonly;
    global $txtleftclass;
    global $txtleftclassnc;
    global $txtrightclass;
    global $authorized;
    global $connection;
    global $surpatterns;
    global $debug;
    global $warn;

    if ($debug)
    {
	if (is_null($participant))
	{
	    $warn	.= "<p class='message'>MarriageRegDetailLib.php: dispParticipant called with no MarriageParticipant record</p>\n";
	}
	else
	{		// have a record
	    $participant->dump('Spouse');	// diagnostic dump
    }		// have a record

    showTrace();
    }		// $debug

    // if this participant is not already linked to
    // look for individuals who match
    $imatches		= null;
    if (!is_null($participant))
    {		// have a record
	$regYear	= $participant->get('m_regyear');
	$role		= $participant->get('m_role');
	$givenNames	= $participant->get('m_givennames');
	$surname	= $participant->get('m_surname');
	$age		= $participant->get('m_age');
	$birthYear	= $participant->get('m_byear');
	$idir		= $participant->get('m_idir');
	$residence	= $participant->get('m_residence');
	$birthPlace	= $participant->get('m_birthplace');
	$occupation	= $participant->get('m_occupation');
	$marStat	= $participant->get('m_marstat');
	$religion	= $participant->get('m_religion');
	$fatherName	= $participant->get('m_fathername');
	$motherName	= $participant->get('m_mothername');

	// assume minister is middle aged (between 25 and 69)
	if ($role == 'M' && ($age == '' || $age == 0))
	{
	    $age	= 47;
	    $birthYear	= $regYear - 47;
	}

	// if the record contains a link to an individual record
	// obtain a copy of the individual record, if possible,
	// otherwise clear the link
	if ($idir > 0)
	{			// try to get individual record
	    try {
		$person	= new Person(array('idir' => $idir));
		$evBirth	= $person->getBirthEvent(false);
		if ($evBirth)
		{		// have birth event
		    // get birth year from individual birth event
		    $birthsd	= $evBirth->get('eventsd');
		    if ($birthsd > -99999999 && !$birthYear)
			$birthYear	= floor($birthsd / 10000);
		    // display birth place from individual birth event
		    $idlrBirth	= $evBirth->get('idlrevent');
		    if ($idlrBirth > 1 && $birthPlace == '')
		    {		// birth place specified
			try {
			    $birthLoc	= new Location(array('idlr' => $idlrBirth));
			    $birthPlace	= $birthLoc->getName();
			} catch (Exception $e) {
			}
		    }		// birth place specified
		}		// have birth event

		$parents	= $person->getPreferredParents();
		if ($parents)
		{		// have preferred parents
		    if (strlen($fatherName) == 0)
			$fatherName	= $parents->getHusbName(0);
		    if (strlen($motherName) == 0)
			$motherName	= $parents->getWifeName(0);
		}		// have preferred parents
	    } catch (Exception $e) {
		$idir	= 0;
	    }
	}			// try to get individual record

	// cover the case where the birth year fields is not initialized
	if ($birthYear === null || $birthYear == 0 ||
	    $age === null || $age === 0)
	{		// birth year not present in database
	    if (is_int($age) || (strlen($age) > 0 && ctype_digit($age)))
		$birthYear	= $regYear - $age;
	    else
		$birthYear	= $regYear - 20;
	}		// birth year not present in database

	// check for matching individuals in the family tree
	if ($authorized &&
	    strlen($surname) > 0 && strlen($givenNames) > 0 &&
	    ($idir == 0 || $idir === null))
	{		// no existing citation
	    // search for a match on any of the parts of the
	    // given name
	    $gnameList	= explode(' ', $givenNames);

	    $getParms	= array('loose'		=> true,
				'incmarried'	=> true,
				'surname'	=> $surname,
				'givenname'	=> $gnameList);

	    // selection based on gender of partner
	    if ($role == 'G')
	    {
		$getParms['gender']	= 0;
		$birthDelta		= 2;
	    }
	    else
	    if ($role == 'B')
	    {
		$getParms['gender']	= 1;
		$birthDelta		= 2;
	    }
	    else
	    {
		$birthDelta		= 27;
	    }

	    // look on either side of the birth year
	    $birthrange	= array(($birthYear - $birthDelta) * 10000,
				($birthYear + $birthDelta) * 10000);
	    $getParms['birthsd']	= $birthrange;
	    $getParms['order']		= "tblNX.Surname, tblNX.GivenName, tblIR.BirthSD";

	    $fatherName	= trim($fatherName);
	    if (strlen($fatherName) > 2)
	    {		// possibly include father's surname in check
		$spacecol	= strrpos($fatherName, ' ');
		if (is_int($spacecol))
		    $fatherSurname	= substr($fatherName, $spacecol + 1);
		else
		    $fatherSurname	= $fatherName;
		if (is_string($fatherSurname) && $fatherSurname != $surname)
		    $getParms['surname']	= array($surname,
							$fatherSurname, null);
	    }		// possibly include father's surname in check

	    // use the alternate name table so the search includes married
	    // names, but include information from the main record
	    if ($role == 'M')
		$getParms['occupation']	= array('minister',
						'priest',
						'clergyman');

	    if ($debug)
		$warn	.= "<p>\$getParms=" . print_r($getParms, true) .
			   "</p<\n";
	    $imatches	= new PersonSet($getParms);
	}		// no existing citation

    showTrace();

	// extract fields in form for insertion into value attribute
	// of <input> tag
	$qGivenNames	= str_replace("'","&#39;",$givenNames);
	$qSurname	= str_replace("'","&#39;",$surname);
	$age		= str_replace("'","&#39;",$age);
	$residence	= str_replace("'","&#39;",$residence);
	$birthPlace	= str_replace("'","&#39;",$birthPlace);
	$occupation	= str_replace("'","&#39;",$occupation);
	$marStat	= str_replace("'","&#39;",$marStat);
	$religion	= str_replace("'","&#39;",$religion);
	$fatherName	= str_replace("'","&#39;",$fatherName);
	$motherName	= str_replace("'","&#39;",$motherName);
    }		// have a marriage participant record
    else
    {		// no marriage participant record
	$qGivenNames	= '';
	$qSurname	= '';
	$age		= 20;
	$birthYear	= $regYear - 20;
	$idir		= 0;
	$residence	= '';
	$birthPlace	= '';
	$occupation	= '';
	$marStat	= '';
	$religion	= '';
	$fatherName	= '';
	$motherName	= '';
    }		// no marriage participant record

    // details of output depend upon whether this is called for
    // the Bride or the Groom
    if ($role == 'G')
    {
	$roleName	= 'Groom';
	$sexclass	= 'male';
    }		// Groom
    else	
    if ($role == 'B')
    {		// Bride
	$roleName	= 'Bride';
	$sexclass	= 'female';
    }		// Bride
    else	
    if ($role == 'M')
    {		// Minister
	$roleName	= 'Minister';
	$sexclass	= 'other';
	if ($occupation == '')
	    $occupation	= 'Minister';
    }		// Minister
    else	
    {		// Other
	$roleName	= 'Other=' . $role;
	$sexclass	= 'other';
    }		// Other

?>
    <fieldset class='<?php print $sexclass; ?>'>
      <legend class='labelSmall'><?php print $roleName; ?>: </legend>
    <div class='row' id='<?php print $role; ?>NameRow'>
      <div class='column1'>
	<label class='labelSmall'>Given&nbsp;Name:</label>
  	    <input name='<?php print $role; ?>GivenNames'
		id='<?php print $role; ?>GivenNames'
		type='text'
		size='22' maxlength='48' class='<?php print $txtleftclass; ?>'
		value='<?php print $qGivenNames; ?>' <?php print $readonly; ?>/>
      </div>
      <div class='column2'>
	<label class='labelSmall'>Surname:</label>
  	    <input name='<?php print $role; ?>Surname'
  		id='<?php print $role; ?>Surname'
		type='text'
		size='16' maxlength='32' class='<?php print $txtleftclass; ?>'
		value='<?php print $qSurname; ?>' <?php print $readonly; ?>/>
      </div>
      <div style='clear: both;'></div>
    </div>
<?php
    if ($role == 'M')
    {			// no age field for Minister
?>
  	    <input name='<?php print $role; ?>Age'
  		id='<?php print $role; ?>Age'
		type='hidden' value='45'>
<?php
    }
    else
    {			// prompt for age otherwise
?>
    <div class="row" id="<?php print $role; ?>BirthRow">
      <div class="column1">
	<label class="labelSmall">Age:</label>
  	    <input name="<?php print $role; ?>Age"
  		id="<?php print $role; ?>Age"
		type="text"
		size="5" maxlength="7" class="<?php print $txtrightclass; ?>"
		value="<?php print $age; ?>" <?php print $readonly; ?>/>
      </div>
      <div class="column2">
	<label class="labelSmall">Birth&nbsp;Year:</label>
	    <span id="<?php print $role; ?>BirthYearText">
		<?php print $birthYear; ?>
	    </span>
  	    <input name="<?php print $role; ?>BirthYear"
		id="<?php print $role; ?>BirthYear" 
		type="hidden"
		value="<?php print $birthYear; ?>">
      </div>
      <div style="clear: both;"></div>
    </div>
<?php
    }			// no age field for Minister

    if ($idir > 0)
    {			// link to individual record
	$gender	= $person->getGender();
	switch($gender)
	{
	    case 0:	// MALE
	    {
		$aclass	= 'male';
		break;
	    }

	    case 1:	// FEMALE
	    {
		$aclass	= 'female';
		break;
	    }

	    default:	// anything else
	    {
		$aclass	= 'other';
		break;
	    }

	}		// act on gender of individual

	$bprivlim	= intval(date('Y')) - 97;
	$dprivlim	= intval(date('Y')) - 72;
	$evBirth	= $person->getBirthEvent(false);
	if ($evBirth)
	    $birth	= $evBirth->getDate($bprivlim);
	else
	    $birth	= '';
	$evDeath	= $person->getDeathEvent(false);
	if ($evDeath)
	    $death	= $evDeath->getDate($dprivlim);
	else
	    $death	= '';
	$name		= $person->getName(Person::NAME_INCLUDE_DATES);

?>
    <div class='row' id='<?php print $role; ?>LinkRow'>
      <div class='column2'>
	<label  class='labelSmall' for='<?php print $role; ?>IDIR'>Link:</label>
	  <a href='/FamilyTree/Person.php?idir=<?php print $idir; ?>'
		target='_blank' class='<?php print $aclass; ?>'
		id='<?php print $role; ?>ShowLink'>
	    <?php print $name; ?>
	  </a>
	  <input name='<?php print $role; ?>IDIR'
		id='<?php print $role; ?>IDIR'
		type='hidden' value='<?php print $idir; ?>'/>
      </div>
	  <button id='Clear<?php print $role; ?>'
		type='button'>
	    Clear
	  </button>
      <div style='clear: both;'></div>
    </div>
<?php
    }		// link to individual record
    else
    if ($imatches && $imatches->count() > 0)
    {	// matched to some individuals in database
?>
    <div class='row' id='<?php print $role; ?>LinkRow'>
      <div class='column2'>
	<label  class='labelSmall'>Link:</label>
	  <select name='<?php print $role; ?>IDIR' id='<?php print $role; ?>IDIR'
		rows='1' class='<?php print $txtleftclass; ?>'>
	    <option value='0' class='unknown'>
		Possible matches to this registration:
<?php
		foreach($imatches as $iidir => $person)
		{	// loop through results
		    $igivenname	= $person->get('givenname'); 
		    $isurname	= $person->get('surname');
		    $isex	= $person->get('gender');
		    if ($isex == Person::MALE)
		    {
			$sexclass	= 'male';
			$childrole	= 'son';
			$spouserole	= 'husband';
		    }
		    else
		    if ($isex == Person::FEMALE)
		    {
			$sexclass	= 'female';
			$childrole	= 'daughter';
			$spouserole	= 'wife';
		    }
		    else
		    {
			$sexclass	= 'unknown';
			$childrole	= 'child';
			$spouserole	= 'spouse';
		    }

		    $iname   = $person->getName(Person::NAME_INCLUDE_DATES);
		    $parents	= $person->getParents();
		    $comma	= ' ';
		    foreach($parents as $idcr => $set)
		    {	// loop through parents
			$father	= $set->getHusbName();
			$mother	= $set->getWifeName();
			$iname	.= "$comma$childrole of $father and $mother";
			$comma	= ', ';
		    }	// loop through parents

		    $families	= $person->getFamilies();
		    $comma	= ' ';
		    foreach ($families as $idmr => $set)
		    {	// loop through families
			if ($isex == Person::FEMALE)
			    $spouse	= $set->getHusbName();
			else
			    $spouse	= $set->getWifeName();
			$iname	.= "$comma$spouserole of $spouse";
			$comma	= ', ';
		    }	// loop through families
?>
	    <option value='<?php print $iidir; ?>'
			class='<?php print $sexclass; ?>'>
		<?php print $iname; ?>
	    </option>
<?php
		}	// loop through results
?>
	</select>
      </div>
      <div style='clear: both;'></div>
    </div>
<?php
    }	// matched to some individuals in database
?>
    <div class='row' id='<?php print $role; ?>ResidenceRow'>
      <div class='column1'>
	<label class='labelSmall'>Residence:</label>
  	    <input name='<?php print $role; ?>Residence'
		id='<?php print $role; ?>Residence'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclassnc; ?>'
		value='<?php print $residence; ?>' <?php print $readonly; ?>/>
      </div>
<?php
    if ($role == 'M')
    {			// display Occupation here for Minister
?>
      <div class='column2'>
	<label class='labelSmall'>Occupation:</label>
  	    <input name='<?php print $role; ?>Occupation'
		id='<?php print $role; ?>Occupation'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclassnc; ?>'
		value='<?php print $occupation; ?>' <?php print $readonly; ?>/>
      </div>
<?php
    }			// display Occupation here for Minister
    else
    {			// no birth place for Minister
?>
      <div class='column2'>
	<label class='labelSmall'>Birth&nbsp;Place:</label>
  	    <input name='<?php print $role; ?>BirthPlace'
		id='<?php print $role; ?>BirthPlace'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclassnc; ?>'
		value='<?php print $birthPlace; ?>' <?php print $readonly; ?>/>
      </div>
<?php
    }			// no birth place for Minister
?>
      <div style='clear: both;'></div>
    </div>
<?php
    if ($role != 'M')
    {			// include occupation and marital status for spouses
?>
    <div class='row' id='<?php print $role; ?>OccupationRow'>
      <div class='column1'>
	<label class='labelSmall'>Occupation:</label>
  	    <input name='<?php print $role; ?>Occupation'
		id='<?php print $role; ?>Occupation'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclassnc; ?>'
		value='<?php print $occupation; ?>' <?php print $readonly; ?>/>
      </div>
      <div class='column2'>
	<label class='labelSmall'>Marital&nbsp;Status:</label>
  	    <input name='<?php print $role; ?>MarStat'
		id='<?php print $role; ?>MarStat'
		type='text' size='1' maxlength='1'
		class='<?php print $txtleftclass; ?>1em'
		value='<?php print $marStat; ?>' <?php print $readonly; ?>/>
      </div>
      <div style='clear: both;'></div>
    </div>
<?php
    }			// include occupation and marital status for spouses
?>
    <div class='row' id='<?php print $role; ?>ReligionRow'>
      <div class='column1'>
	<label class='labelSmall'>Religion:</label>
  	<input name='<?php print $role; ?>Religion'
		id='<?php print $role; ?>Religion'
		type='text' size='16' maxlength='64'
		class='<?php print $txtleftclassnc; ?>'
		value='<?php print $religion; ?>' <?php print $readonly; ?>/>
      </div>
      <div style='clear: both;'></div>
    </div>
<?php
    if ($role != 'M')
    {			// no parents for Minister
?>
    <div class='row' id='<?php print $role; ?>ParentsRow'>
      <div class='column1'>
	<label class='labelSmall'>Father:</label>
  	<input name='<?php print $role; ?>FatherName'
		id='<?php print $role; ?>FatherName'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclass; ?>'
		value='<?php print $fatherName; ?>' <?php print $readonly; ?>/>
      </div>
      <div class='column2'>
	<label class='labelSmall'>Mother:</label>
  	<input name='<?php print $role; ?>MotherName'
		id='<?php print $role; ?>MotherName'
		type='text' size='22' maxlength='64'
		class='<?php print $txtleftclass; ?>'
		value='<?php print $motherName; ?>' <?php print $readonly; ?>/>
      </div>
      <div style='clear: both;'></div>
    </div>
<?php
    }			// no parents for Minister
?>
    </fieldset>
<?php
}		// dispParticipant function
?>
