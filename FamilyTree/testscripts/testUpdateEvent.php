<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testUpdateEvent.php
 *
 *  test the updateEvent.php script
 *
 *  Parameters:
 *	ider	unique numeric key of instance of legacyEvent
 *		if set to zero requests creation of new instance
 *
 *  Copyright 2010 James A. Cobban
 *
 *  History:
 **/
    require_once __NAMESPACE__ . '/common.inc';

    $subject	= rawurlencode("Edit Event");

    htmlHeader('Test Update Event',
               array("/jscripts/tinymce/js/tinymce/tinymce.js",
	                 '/jscripts/js20/http.js',
		             '/jscripts/CommonForm.js',
		             '/jscripts/util.js',
		             '/citTable.js',
		             '/Common.js',
		             '/editEvent.js'));
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/genCountry.php?cc=CA'		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		'/FamilyTree/Services.php'		=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test Update Event;
    </h1>
<form name='evtForm' action='/FamilyTree/updateEvent.php' method='post'>
  <p>
    <label class='column1'>IDIR:</label>
	<input type='text' name='idir' class='white right' value='5861'>
  <p>
    <label class='column1'>IDER:</label>
	<input type='text' name='ider' class='white right' value='2895'>
  <p>
    <label class='column1'>Type:</label>
	<select name='type' class='white left'>
		<option value='0'>UNSPECIFIED</option>
		<option value='1'>NAME</option>
		<option value='2'>BIRTH</option>
		<option value='3'>CHRISTEN</option>
		<option value='4'>DEATH</option>
		<option value='5'>BURIED</option>
		<option value='6'>NOTESGENERAL</option>
		<option value='7'>NOTESRESEARCH</option>
		<option value='8'>NOTESMEDICAL</option>
		<option value='9'>DEATHCAUSE</option>
		<option value='15'>LDS Baptism</option>
		<option value='16'>LDS Endowment</option>
		<option value='26'>LDS Confirmation</option>
		<option value='27'>LDS Initiatory</option>
		<option value='10'>ALTNAME</option>
		<option value='11'>CHILD STATUS</option>
		<option value='12'>CPREL DAD</option>
		<option value='13'>CPREL MOM</option>
		<option value='17'>LDS Sealed to Parents</option>
		<option value='18'>LDS Sealed to Spouse</option>
		<option value='19'>NEVER MARRIED</option>
		<option value='20'>Marriage </option>
		<option value='21'>Marriage NOTE</option>
		<option value='22'>Marriage NEVER</option>
		<option value='23'>Marriage NOKIDS</option>
		<option value='24'>Marriage END</option>
		<option value='30' selected='selected'>Individual Event</option>
		<option value='31'>Marriage Event</option>
		<option value='40'>TODO</option>
	</select>

  <p>
    <label class='column1'>Event Type:</label>
	<select name='etype' class='white left'>
	    <option value='2' >adopted</option>

	    <option value='63' >also worked as a</option>
	    <option value='17' >appeared in court</option>
	    <option value='56' >attended school</option>
	    <option value='8' >baptized</option>
	    <option value='9' >Bar Mitzvah</option>
	    <option value='10' >Bat Mitzvah</option>

	    <option value='14' >became a citizen</option>
	    <option value='41' >belonged to</option>
	    <option value='11' >blessed (LDS)</option>
	    <option value='3' >born</option>
	    <option value='4' >buried</option>
	    <option value='5' >christened</option>

	    <option value='13' >circumcized</option>
	    <option value='15' >confirmed</option>
	    <option value='16' >confirmed LDS</option>
	    <option value='18' >cremated</option>
	    <option value='50' >described as</option>
	    <option value='6' >died</option>

	    <option value='20' >divorced from</option>
	    <option value='22' >education</option>
	    <option value='68' >elected as</option>
	    <option value='23' >emigrated from</option>
	    <option value='24' >employed as</option>
	    <option value='25' >engaged</option>

	    <option value='36' >entered marriage contract</option>
	    <option value='12' >enumerated in a census</option>
	    <option value='66' >ethnicity</option>
	    <option value='65' >family group</option>
	    <option value='21' >filed for divorce from</option>
	    <option value='26' >first communion</option>

	    <option value='67' >funeral</option>
	    <option value='27' >graduated from</option>
	    <option value='28' >hobby of</option>
	    <option value='29' >honored as</option>
	    <option value='31' >ill with</option>
	    <option value='32' >immigrated</option>

	    <option value='30' >in hospital</option>
	    <option value='60' >in the military</option>
	    <option value='33' >interviewed</option>
	    <option value='54' >lived</option>
	    <option value='35' >marriage banns issued</option>
	    <option value='37' >marriage license</option>

	    <option value='39' >marriage settlement</option>
	    <option value='7' >marriage was annulled</option>
	    <option value='38' >married</option>
	    <option value='69' >married (69)</option>
	    <option value='70' >married (70)</option>
	    <option value='59' >medical condition</option>

	    <option value='40' >medical event</option>
	    <option value='42' >military</option>
	    <option value='43' >mission</option>
	    <option value='44' >named for</option>
	    <option value='64' >nationality</option>
	    <option value='45' >naturalized</option>

	    <option value='1' >null event 1</option>
	    <option value='46' >obituary published</option>
	    <option value='49' >ordained as</option>
	    <option value='48' >ordinance (LDS)</option>
	    <option value='34' >owned land</option>
	    <option value='52' >owned property</option>

	    <option value='61' >photo</option>
	    <option value='19' >received a degree</option>
	    <option value='53' >religious affiliation</option>
	    <option value='55' >retired</option>
	    <option value='57' >Social Security Number</option>
	    <option value='62' >Social Security Number (62)</option>

	    <option value='51' >will probated</option>
	    <option value='58' >will</option>
	    <option value='47' selected='selected'>worked as a</option>
	    <option value='0' >unused 0</option>
	    <option value='71' >unused 71</option>
	</select>
  <p>
    <label class='column1'>Date:</label>
	<input type='text' class='white left' name='date' value=''>
  <p>
    <label class='column1'>Desc:</label>
	<input type='text' class='white left' name='desc' value=''>
  <p>
    <label class='column1'>Notes:</label>
	    <textarea name='note' cols='64' rows='4'></textarea>
  <p>
    <label class='column1'>Description:</label>
	<input type='text' class='white left' name='description' value='Public School Teacher &amp; Farmer'>
  <p>
    <label class='column1'>Location:</label>
		<input type='text' class='white left' size='64' name='location'
			value='Caradoc, Middlesex, ON, CA'>
  <p>
    <label class='column1'>Order:</label>
	<input type='text' class='white left' name='order' value=''>
  <p>
    <label class='column1'>Name:</label>
	<input type='text' class='white left' name='givenName' value=''>
	<input type='text' class='white left' name='surname' value=''>
  <p>
    <label class='column1'>Title:</label>
	<input type='text' class='white left' name='title' value=''>
  <p>
    <label class='column1'>Prefix:</label>
	<input type='text' class='white left' name='prefix' value=''>
  <p>
    <label class='column1'>Alt Name:</label>
	<input type='text' class='white left' name='newAltGivenName' value=''>
	<input type='text' class='white left' name='newAltSurname' value=''>
<p>
  <button type='submit'>Update Event</button>
</p>
</form>
<?php
// include support for managing citations of this event
	require_once $document_root . '/FamilyTree/citTable.inc';
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpSurname'>
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
value, stored in field 'SoundsLike' in the individual records is updated.
Also if the surname does not already appear in the database, a record is
added into the table 'tblNR'.
</p>
</div>
</body>
</html>
