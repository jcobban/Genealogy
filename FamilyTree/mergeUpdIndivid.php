<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  mergeUpdIndivid.php													*
 *																		*
 *  Handle a request to merge two individuals in 						*
 *  the Legacy family tree database.									*
 *																		*
 *  History:															*
 *		2010/12/26		created											*
 *		2010/12/28		set 'updated' and 'updatedtime' to now			*
 *						update name of merged individual in marriage	*
 *						records											*
 *						take higher of 'ancinterest' and 'decinterest'	*
 *		2011/01/08		add additional breadcrumbs into header and		*
 *						trailer											*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/01/14		use LegacyIndiv::mergeFrom method				*
 *		2011/04/05		include link to individual in breadcrumbs		*
 *		2012/01/13		change class names								*
 *		2012/03/15		support B_IDIR field in Births table			*
 *		2012/06/08		update nominal index records to reflect merge	*
 *						clear up parameter processing					*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/01/19		use setField and save to update database		*
 *		2013/05/31		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/04		include link to merged individual in			*
 *						header/footer									*
 *		2013/06/11		enclose diagnostic info from record saves		*
 *						in <p>											*
 *						correct record key values in some calls to		*
 *						logSqlUpdate									*
 *		2013/07/19		get IDCR for child record being deleted as a	*
 *						duplicate within a family BEFORE deleting the	*
 *						record.											*
 *		2013/08/01		defer facebook initialization until after load	*
 *	    2013/08/15	    fix a couple of undefined variable errors	    *	
 *		2013/11/22		field IDLRChris erroneously converted to		*
 *						LegacyDate										*
 *		2014/03/10		use CSS for layout instead of tables			*
 *		2014/03/21		use LegacyAltName::deleteAltNames to delete		*
 *						alternate names used by second individual		*
 *		2014/03/26		make sure blog entries for the second individual*
 *						are not lost									*
 *		2014/04/08		class LegacyAltName renamed to LegacyName		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/05/08		handle exceptions thrown in LegacIndiv::delete	*
 *		2014/08/07		use Citation::updateCitations					*
 *		2014/09/22		include link to individual in header if given	*
 *						name and no surname								*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/30		events moved from tblIR to tblER				*
 *						use Event::updateEvents to update tblER			*
 *		2014/12/01		print trace info in body						*
 *		2014/12/19		method LegacyIndiv::getFamilies returns			*
 *						associative array indexed by IDMR				*
 *		2015/01/14		birth date was deleted from merged individual	*
 *		2015/02/01		clear preferred flag in any events copied		*
 *						from second individual							*
 *		2015/02/10		if invoked from an instance of editIndivid.php	*
 *						then update fields in that edit window			*
 *		2015/03/21		copy citations from 2nd individual's events		*
 *		2015/06/06		pass list of moved events to editIndivid		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *		2016/04/28		improve merging of citations to birth and death	*
 *						events, which did not work when merging an old	*
 *						style simulated event (IDER=0) with a real event*
 *		2017/01/17		use method set in place of setField				*
 *		2017/03/18		update given name and surname in caller			*
 *						use preferred parameters for new LegacyIndiv	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/18		class LegacyName renamed to class Name			*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/29		use class RecordSet to update birth, death,		*
 *						and marriage transcriptions to new IDIR			*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    // control debugging output
    $msg		= '';
    $surname		= '';
    $nameuri		= '';	// defaults for trail of breadcrumbs
    $prefix		= '';
    $idir1		= null;
    $idir2		= null;
    $useSurname2	= false;
    $useGivenName2	= false;
    $useBthDate2	= false;
    $useBthLoc2		= false;
    $useCrsDate2	= false;
    $useCrsLoc2		= false;
    $useDthDate2	= false;
    $useDthLoc2		= false;
    $useBurDate2	= false;
    $useBurLoc2		= false;
    $isOwner		= canUser('edit');

    // process parameters
    foreach($_POST as $key => $value)
    {		// loop through all parameters
		switch($key)
		{	// act on specific parameters
		    case 'idir1':
		    {
				$idir1		= $value;
				$person1	= new Person(array('idir' => $idir1));
				$evBirth1	= $person1->getBirthEvent(true);
				$evChris1	= $person1->getChristeningEvent(true);
				$evDeath1	= $person1->getDeathEvent(true);
				$evBuried1	= $person1->getBuriedEvent(true);
				$isOwner	=  $isOwner && $person1->isOwner();
				break;
		    }	// idir1

		    case 'idir2':
		    {
				$idir2		= $value;
				$person2	= new Person(array('idir' => $idir2));
				$evBirth2	= $person2->getBirthEvent(true);
				$evChris2	= $person2->getChristeningEvent(true);
				$evDeath2	= $person2->getDeathEvent(true);
				$evBuried2	= $person2->getBuriedEvent(true);
				$isOwner	= $isOwner && $person2->isOwner();
				break;
		    }	// idir2

		    case 'SurnameCb1':
		    {
				$useSurname2	= false;
				break;
		    }	// SurnameCb1

		    case 'SurnameCb2':
		    {
				$useSurname2	= true;
				break;
		    }	// SurnameCb2

		    case 'GivenNameCb1':
		    {
				$useGivenName2	= false;
				break;
		    }	// GivenNameCb1

		    case 'GivenNameCb2':
		    {
				$useGivenName2	= true;
				break;
		    }	// GivenNameCb2

		    case 'BthDateCb1':
		    {
				$useBthDate2	= false;
				break;
		    }	// BthDateCb1

		    case 'BthDateCb2':
		    {
				$useBthDate2	= true;
				break;
		    }	// BthDateCb2

		    case 'BthLocCb1':
		    {
				$useBthLoc2	= false;
				break;
		    }	// BthLocCb1

		    case 'BthLocCb2':
		    {
				$useBthLoc2	= true;
				break;
		    }	// BthLocCb2

		    case 'CrsDateCb1':
		    {
				$useCrsDate2	= false;
				break;
		    }	// CrsDateCb1

		    case 'CrsDateCb2':
		    {
				$useCrsDate2	= true;
				break;
		    }	// CrsDateCb2

		    case 'CrsLocCb1':
		    {
				$useCrsLoc2	= false;
				break;
		    }	// CrsLocCb1

		    case 'CrsLocCb2':
		    {
				$useCrsLoc2	= true;
				break;
		    }	// CrsLocCb2

		    case 'DthDateCb1':
		    {
				$useDthDate2	= false;
				break;
		    }	// DthDateCb1

		    case 'DthDateCb2':
		    {
				$useDthDate2	= true;
				break;
		    }	// DthDateCb2

		    case 'DthLocCb1':
		    {
				$useDthLoc2	= false;
				break;
		    }	// DthLocCb1

		    case 'DthLocCb2':
		    {
				$useDthLoc2	= true;
				break;
		    }	// DthLocCb2

		    case 'BurDateCb1':
		    {
				$useBurDate2	= false;
				break;
		    }	// BurDateCb1

		    case 'BurDateCb2':
		    {
				$useBurDate2	= true;
				break;
		    }	// BurDateCb2

		    case 'BurLocCb1':
		    {
				$useBurLoc2	= false;
				break;
		    }	// BurLocCb1

		    case 'BurLocCb2':
		    {
				$useBurLoc2	= true;
				break;
		    }	// BurLocCb2

		}	// act on specific parameters
    }		// loop through all parameters

    // get the identifiers of the two individuals
    if ($idir1 !== null && $idir2 !== null && $idir1 != $idir2)
    {		// IDIRs of two individuals specified
		if ($isOwner)
		{
		    $given	= $person1->getGivenName();
		    $surname	= $person1->getSurname();
		    $nameuri = rawurlencode($surname . ', ' .  $given);
		    if (strlen($surname) == 0)
				$prefix	= '';
		    else
		    if (substr($surname,0,2) == 'Mc')
				$prefix	= 'Mc';
		    else
				$prefix	= substr($surname,0,1);

		    $title	= 'Merge ' . $given . ' ' .
							     $surname . ' and ' .
								 $person2->getGivenName() . ' ' .
							     $person2->getSurname();
		}	// current user can edit both individuals
		else
		{	// user not authorized
		    $title	= 'Merge Failed';
		    $msg    .= 'You are not authorized to update these individuals. ';
		}	// user not authorized
    }		// parameters OK
    else
    {		// missing parameter
		$title		= 'Merge Failed';
		$msg		.= 'Missing mandatory parameters. ';
    }		// missing parameter

    htmlHeader($title,
		       array('/jscripts/CommonForm.js',
				     '/jscripts/js20/http.js',
				     '/jscripts/util.js',
				     'mergeUpdIndivid.js'));
?>
<body>
<?php
    if (strlen($surname) > 0)
    {			// surname present
		pageTop(array(
				'/genealogy.php'			=> 'Genealogy',
				'/genCanada.html'			=> 'Canada',
				'/Canada/genProvince.php?Domain=CAON'		=> 'Ontario',
				'/FamilyTree/Services.php'			=> 'Services',
				"nominalIndex.php?name=$nameuri"		=> 'Nominal Index',
				"Surnames.php?initial=$prefix"	=>
						"Surnames Starting with '$prefix'",
				"Names.php?Surname=$surname"	=>
						"Surname '$surname'",
				"Person.php?idir=$idir1"	=>
						"$given $surname"));
    }			// surname present
    else
    if (strlen($given) > 0)
    {			// given name present but no surname
		pageTop(array(
				'/genealogy.php'			=> 'Genealogy',
				'/genCanada.html'			=> 'Canada',
				'/Canada/genProvince.php?Domain=CAON'		=> 'Ontario',
				'/FamilyTree/Services.php'			=> 'Services',
				"nominalIndex.php?name=$nameuri"		=> 'Nominal Index',
				"Person.php?idir=$idir1"	=>
						"$given"));
    }			// given name present but no surname
    else
    {		// name not present
		pageTop(array(
				'/genealogy.php'		=> 'Genealogy',
				'/genCanada.html'		=> 'Canada',
				'/Canada/genProvince.php?Domain=CAON'	=> 'Ontario',
				'/FamilyTree/Services.php'	=> 'Services',
				'nominalIndex.php'		=> 'Nominal Index'));
    }		// ame not present

?>	
  <div class="body">
    <h1>
      <span class="right">
		<a href="mergeUpdIndividHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?> 
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
  <p class="message">
		<?php print $msg; ?> 
  </p>
<?php
    }		// error messages
    else
    {		// OK to update
?>
  <p>Values from individual record IDIR=<?php print $idir2; ?> are
     selectively merged into individual record IDIR=<?php print $idir1; ?>.
<?php
		if ($person2->getGender() == Person::FEMALE)
		{		// female
		    $midirfld	= 'IDIRWife';
		    $sidirfld	= 'IDIRHusb';
		    $msurnfld	= 'WifeSurname';
		    $mgivnfld	= 'WifeGivenName';
		}		// female
		else
		{		// male
		    $midirfld	= 'IDIRHusb';
		    $sidirfld	= 'IDIRWife';
		    $msurnfld	= 'HusbSurname';
		    $mgivnfld	= 'HusbGivenName';
		}		// male

		if ($useSurname2)
		{	// take surname from second
		    $person1->setSurname($person2->getSurname());
?>
  <p>Surname replaced by '<?php print $person2->getSurname(); ?>'.
<?php
		}	// take surname from second

		if ($useGivenName2)
		{	// take given name from second
		    $person1->set('givenname', $person2->getGivenName());
?>
  <p>Given name replaced by '<?php print $person2->getGivenName(); ?>'.
<?php
		}	// take given name from second

		// merge birth information

		// the following is to retain the true event when we try to
		// merge a true event with a simulated event (IDER=0)
		if ($evBirth1->getIder() == 0 && $evBirth2->getIder() > 0)
		{		// second is instance of Event
		   $newEvent	= $evBirth2;
		   $newEvent->set('idir', $evBirth1->get('idir'));
		   $oldEvent	= $evBirth1;
		}		// second is instance of Event
		else
		{		// first is instance of Event or neither
		   $newEvent	= $evBirth1;
		   $oldEvent	= $evBirth2;
		}		// first is instance of Event or neither

		$birthDate2	= new LegacyDate($evBirth2->get('eventd'));
		if ($useBthDate2)
		{		// take birth date from second
		    $newEvent->set('eventd',
							$birthDate2);
?>
  <p>Birth date replaced by '<?php print $_POST['BirthDate2']; ?>'.
<?php
		}		// take birth date from second
		else
		{		// take birth date from first
		    $newEvent->set('eventd',
						   new LegacyDate($evBirth1->get('eventd')));
		}		// take birth date from first

		if ($useBthLoc2)
		{		// take birth location from second
		    $newEvent->set('idlrevent',
						   $evBirth2->get('idlrevent'));
?>
  <p>Birth location replaced by '<?php print $_POST['BirthLocation2']; ?>'.
<?php
		}		// take birth location from second
		else
		{		// take birth location from first
		    $newEvent->set('idlrevent',
						   $evBirth1->get('idlrevent'));
		}		// take birth location from first

		// copy citations from second birth date
		$citations	= $oldEvent->getCitations();
		if (count($citations) > 0)
		{		// have citations to copy
?>
  <p>Copy <?php print count($citations); ?> citations to birth.
<?php
		    $newEvent->addCitations($citations);
		}		// have citations to copy

		// cleanup
		if ($oldEvent)
		{
		    $oldEvent->delete('p');
		    $oldEvent	= null;
		    $evBirth2	= null;
		    $evBirth1	= $newEvent;
		}

		// merge christening information
		if ($useCrsDate2)
		{	// take christening date from second
		    $evChris1->set('eventd',
						   new LegacyDate($evChris2->get('eventd')));
?>
  <p>Christening date replaced by '<?php print $_POST['ChrisDate2']; ?>'.
<?php
		}	// take christening date from second
		if ($useCrsLoc2)
		{	// take christening location from second
		    $evChris1->set('idlrevent',
						   $evChris2->get('idlrevent'));
?>
  <p>Christening location replaced by '<?php print $_POST['ChrisLocation2']; ?>'.
<?php
		}	// take christening location from second

		// copy citations from second christening date
		$citations	= $evChris2->getCitations();
		if (count($citations) > 0)
		{		// have citations to copy
?>
  <p>Copy <?php print count($citations); ?> citations to christening.
<?php
		    $evChris1->addCitations($citations);
		}		// have citations to copy

		if ($evChris2)
		{
		    $evChris2->delete('p');
		    $evChris2	= null;
		}

		// merge death information

		// the following is to retain the true event when we try to
		// merge a true event with a simulated event (IDER=0)
		if ($evDeath1->getIder() == 0 && $evDeath2->getIder() > 0)
		{
		   $newEvent	= $evDeath2;
		   $newEvent->set('idir', $evDeath1->get('idir'));
		   $oldEvent	= $evDeath1;
		}
		else
		{
		   $newEvent	= $evDeath1;
		   $oldEvent	= $evDeath2;
		}

		if ($useDthDate2)
		{	// take death date from second
		    $newEvent->set('eventd',
							new LegacyDate($evDeath2->get('eventd')));
?>
  <p>Death date replaced by '<?php print $_POST['DeathDate2']; ?>'.
<?php
		}	// take death date from second
		else
		{		// take birth date from first
		    $newEvent->set('eventd',
							new LegacyDate($evDeath1->get('eventd')));
		}		// take birth date from first
		if ($useDthLoc2)
		{		// take death location from second
		    $newEvent->set('idlrevent',
							$evDeath2->get('idlrevent'));
?>
  <p>Death location replaced by '<?php print $_POST['DeathLocation2']; ?>'.
<?php
		}		// take death location from second
		else
		{		// take death location from first
		    $newEvent->set('idlrevent',
							$evDeath1->get('idlrevent'));
		}		// take death location from first

		// copy citations from second death date
		$citations	= $oldEvent->getCitations();
		if (count($citations) > 0)
		{		// have citations to copy
?>
  <p>Copy <?php print count($citations); ?> citations to death.
<?php
		    $newEvent->addCitations($citations);
		}		// have citations to copy

		if ($oldEvent)
		{
		    $oldEvent->delete('p');
		    $oldEvent	= null;
		    $evDeath2	= null;
		    $evDeath1	= $newEvent;
		}

		if ($useBurDate2)
		{	// take burial date from second
		    $evBuried1->set('eventd',
						    new LegacyDate($evBuried2->get('eventd')));
?>
  <p>Burial date replaced by '<?php print $_POST['BuriedDate2']; ?>'.
<?php
		}	// take burial date from second
		if ($useBurLoc2)
		{	// take burial location from second
		    $evBuried1->set('idlrevent',
						    $evBuried2->get('idlrevent'));
?>
  <p>Burial location replaced by '<?php print $_POST['BuriedLocation2']; ?>'.
<?php
		}	// take burial location from second

		// copy citations from second burial date
		$citations	= $evBuried2->getCitations();
		if (count($citations) > 0)
		{		// have citations to copy
?>
  <p>Copy <?php print count($citations); ?> citations to burial.
<?php
		    $evBuried1->addCitations($citations);
		}		// have citations to copy

		if ($evBuried2)
		{
		    $evBuried2->delete('p');
		    $evBuried2	= null;
		}

		// check all other fields in the main record
		$person1->mergeFrom($person2);

		$person1->save(false);
		if (strlen($evBirth1->getDate()) > 0 ||
		    $evBirth1->get('idlrevent') > 1)
		    $evBirth1->save('p');
		if (strlen($evChris1->getDate()) > 0 ||
		    $evChris1->get('idlrevent') > 1)
		    $evChris1->save('p');
		if (strlen($evDeath1->getDate()) > 0 ||
		    $evDeath1->get('idlrevent') > 1)
		    $evDeath1->save('p');
		if (strlen($evBuried1->getDate()) > 0 ||
		    $evBuried1->get('idlrevent') > 1)
		    $evBuried1->save('p');

		// move event records from individual 2 to individual 1 
		$parms		= array('idir'		=> $idir2,
							'idtype'	=> 0);
		$movedEvents	= new RecordSet('Events', $parms);

		$setparms	= array('idir'		=> $idir1,
							'preferred'	=> 0);
		$olddebug	= $debug;
		$debug		= true;
		$eventSet	= new RecordSet('Events', $parms);
		$result		= $eventSet->update($setparms,
								    false,
								    false);
		showTrace();
		$debug		= $olddebug;

		if ($result > 0)
		{
?>
    <p>Moved <?php print $result; ?>
		individual event records from <?php print $idir2; ?> 
		to <?php print $idir1; ?>.
<?php
		}

		// delete name index entries for deleted individual
		$names		= new RecordSet('Names', array('idir' => $idir2));
		$result		= $names->delete('p');
		if ($result > 0)
		{
?>
  <p>Deleted <?php print $result; ?> nominal index records for second individual. 
<?php
		}

		// Update nominal index records for merged individual. 
?>
  <p>Update nominal index records for merged individual.</p> 
<?php
		$altName	= new Name($person1);
		$altName->save('p');

		// check for marriages of the second individual to associate
		// with the first individual
		$families1	= $person1->getFamilies();
		$families2	= $person2->getFamilies();
		foreach($families2 as $idmr2 => $family2)
		{		// loop through families
		    $keepBoth	= true;
		    foreach($families1 as $idmr1 => $family1)
		    {		// search for duplicate marriage
				if ($family1->get($midirfld) == $idir1 &&
				    $family1->get($sidirfld) == $family2->get($sidirfld))
				{	// merger will create duplicate family
				    $family1->merge($family2);
				    $keepBoth	= false;
				    break;	// leave loop
				}	// merger will create duplicate family
		    }		// search for duplicate marriage

		    if ($keepBoth)
		    {		// retain both families
				$family2->set($midirfld, $idir1);
				$family2->save('p');
?>
  <p>In marriage record IDMR=<?php print $family2->getIdmr(); ?>
		field '<?php print $midirfld; ?>' set to '<?php print $idir1; ?>'.
<?php
		    }		// retain both families
		    else
		    {		// delete duplicate family
?>
  <p>All information merged into family IDMR=<?php print $family1->getIdmr(); ?>
		and family IDMR=<?php print $family2->getIdmr(); ?> deleted.
<?php
				$family2->delete();
		    }		// delete duplicate family
		}		// loop through families

		// ensure the the name of the merged individual is adjusted
		// in all marriages.  The array $families now includes the
		// marriages added from the second individual
		// refresh the local copy of the individual record
		// so that Person::getFamilies does not use local copy
		$person1	= new Person(array('idir' => $idir1));
		$families	= $person1->getFamilies();
		$givenname	= $person1->getGivenName();
		$surname	= $person1->getSurname();
		foreach($families as $idmr => $family)
		{		// loop through families
		    $family->setName($person1);
		    $family->save('p');
?>
  <p>In marriage record IDMR=<?php print $idmr; ?>
		fields '<?php print $msurnfld; ?>' and 
		'<?php print $mgivnfld; ?>' set to 
		<?php print $surname . ", " . $givenname; ?>.
<?php
		}		// loop through families

		// check for child records to update
		$child		= $person2->getChild();	// RecordSet of Child
		foreach($child as $idcr => $childr)
		{		// loop through child records for second individual
		    $cfamily	= $childr->getFamily();
		    $duplicate	= $cfamily->getChildByIdir($idir1);
		    if ($duplicate)
		    {		// there is already a child with the new IDIR
				try {
				    $childr->delete(false);
?>
  <p>Child record IDCR=<?php print $idcr; ?>
		is deleted as a duplicate.
<?php
				} catch(Exception $e) {
				}	// ignore exception
		    }		// there is already a child with the new IDIR
		    else
		    {		// change child record to point at new IDIR
				$childr->set('idir', $idir1);
				$childr->save('p');
?>
  <p>In child record IDCR=<?php print $childr->getId(); ?>
		field 'IDIR' set to '<?php print $idir1; ?>'.
<?php
		    }		// change child record to point at new IDIR
		}		// loop through child records for second individual

		// update citation records to new IDIR
		$citations	= new CitationSet(array('idir'	=> $idir2));
		$result		= $citations->update(array('idime'	=> $idir1),
								     false);
		if ($result > 0)
		{		// at least 1 record updated
?>
  <p>Update <?php print $result; ?> source citations
		to point to individual record IDIR=<?php print $idir1;?>
<?php
		}		// at least 1 record updated

		// check for Birth registration records to update
		$birthSet	= new RecordSet('Births',
								array('b_idir'	=> $idir2));
		$result		= $birthSet->update(array('b_idir'	=> $idir1));
		if ($result > 0)
		{
?>
  <p>Update the transcription of the birth registration.
<?php
		}

		// check for Death registration records to update
		$deathSet	= new RecordSet('Deaths',
								array('d_idir'	=> $idir2));
		$result		= $deathSet->update(array('d_idir'	=> $idir1));
		if ($result > 0)
		{
?>
  <p>Update the transcription of the death registration.
<?php
		}

		// check for Marriage registration records to update
		$marrSet	= new RecordSet('MarriageIndi',
								array('m_idir'	=> $idir2));
		$result		= $marrSet->update(array('m_idir'	=> $idir1));
		if ($result > 0)
		{
?>
  <p>Update the transcription of the marriage registration.
<?php
		}

		// merge blog records for the second individual
		$blogparms	= array('table'		=> 'tblIR',
							'keyvalue'	=> $idir2);
		$blogSet	= new RecordSet('Blogs', $blogparms);
		if ($blogSet->count() > 0)
		{
?>
  <p>Move <?php print $blogSet->count(); ?> blog messages.
<?php

		    foreach($blogSet as $blid => $blog)
		    {		// loop through all blogs
				$blog->set('keyvalue', $idir1);
				$blog->save(false);
		    }		// loop through all blogs
		}
		// delete the duplicate record from tblIR
?>
  <p>Delete the individual record <?php print $idir2; ?></p>
<?php
		$person2->resetFamilies();
		$person2->resetParents();
		try {
		    $person2->delete("p");
		} catch (Exception $e) {
?>
  <p class="warning"><?php print $e->getMessage(); ?></p>
<?php
		}		// catch

    }		// OK to update

    showTrace();

    // get info on common events of the merged individual
    $evBirth1	= $person1->getBirthEvent(true);
    $birthd	= $evBirth1->getDate();
    $birthloc	= htmlspecialchars($evBirth1->getLocation()->getName(),
							   ENT_QUOTES);
    $evChris1	= $person1->getChristeningEvent(true);
    $chrisd	= $evChris1->getDate();
    $chrisloc	= htmlspecialchars($evChris1->getLocation()->getName(),
							   ENT_QUOTES);
    $evDeath1	= $person1->getDeathEvent(true);
    $deathd	= $evDeath1->getDate();
    $deathloc	= htmlspecialchars($evDeath1->getLocation()->getName(),
							   ENT_QUOTES);
    $evBuried1	= $person1->getBuriedEvent(true);
    $buriald	= $evBuried1->getDate();
    $burialloc	= htmlspecialchars($evBuried1->getLocation()->getName(),
							   ENT_QUOTES);
    $givenname	= $person1->getGivenName();
    $surname	= $person1->getSurname();
?>
    <form action="Person.php?idir=<?php print $idir1; ?>"
				name="mainForm" method="get">
      <div class="hidden" id="hiddenElements">
		<input type="hidden" name="birthd" id="birthd"
				value="<?php print $birthd; ?>">
		<input type="hidden" name="birthloc" id="birthloc" 
				value="<?php print $birthloc; ?>">
		<input type="hidden" name="chrisd" id="chrisd" 
				value="<?php print $chrisd; ?>">
		<input type="hidden" name="chrisloc" id="chrisloc" 
				value="<?php print $chrisloc; ?>">
		<input type="hidden" name="deathd" id="deathd" 
				value="<?php print $deathd; ?>">
		<input type="hidden" name="deathloc" id="deathloc" 
				value="<?php print $deathloc; ?>">
		<input type="hidden" name="buriald" id="buriald" 
				value="<?php print $buriald; ?>">
		<input type="hidden" name="burialloc" id="burialloc" 
				value="<?php print $burialloc; ?>">
		<input type="hidden" name="givenname" id="givenname" 
				value="<?php print $givenname; ?>">
		<input type="hidden" name="surname" id="surname" 
				value="<?php print $surname; ?>">
<?php
    foreach($movedEvents as $ider => $event)
    {
		$date		= $event->getDate();
		$location	= $event->getLocation();
		$idet		= $event->get('idet');
		$description	= $event->get('description');
		$cittype	= $event->getCitType();
?>
		<input type="hidden" name="eventd<?php print $ider; ?>"
				id="eventd<?php print $ider; ?>"
				value="<?php print $date; ?>">
		<input type="hidden" name="eventtype<?php print $ider; ?>"
				id="eventtype<?php print $ider; ?>"
				value="<?php print $idet; ?>">
		<input type="hidden" name="eventcittype<?php print $ider; ?>"
				id="eventcittype<?php print $ider; ?>"
				value="<?php print $cittype; ?>">
		<input type="hidden" name="eventdescription<?php print $ider; ?>"
				id="eventdescription<?php print $ider; ?>"
				value="<?php print $description; ?>">
		<input type="hidden" name="eventloc<?php print $ider; ?>"
				id="eventloc<?php print $ider; ?>"
				value="<?php print $location->toString(); ?>">
<?php
    }
?>
      </div>
      <button type="submit" id="Submit">
		Close
      </button>
    </form>
    </div>  <!-- end of <div id="body"> -->
<?php
    pageBot($title . ": IDIR1=$idir1, IDIR2=$idir2");
?>
</body>
</html>
