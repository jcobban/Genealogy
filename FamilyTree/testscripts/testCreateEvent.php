<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testCreateEvent.php							*
 *									*
 *  test the CreateEvent.php script					*
 *									*
 *  History:								*
 *	2011/01/28	created						*
 *	2014/09/18	use standard layout				*
 *	2014/12/02	enclose comment blocks				*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';


    htmlHeader('Test Create Event',
		array('/jscripts/js20/http.js',
			'/jscripts/CommonForm.js',
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
		  '/genCountry.php?cc=CA'		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test Create Event;
    </h1>
<form name='evtForm' action='/createEvent.php' method='post'>
  <p>
    <label class='column1' for=;idir'>IDIR:</label>
	<input type='text' class='white rightnc'
		id='idir' name='idir' value='5861'>
  </p>
  <p>
    <label class='column1' for='type'>Type:</label>
	<input type='text' class='white rightnc'
		id='type' name='type' value='30'>
  </p>
  <p>
    <label class='column1' for='etype'>Event Type:</label>
	<select name='etype' id='etype' class='white left'>
	    <option value='2'>adopted</option>

	    <option value='63'>also worked as a</option>
	    <option value='17'>appeared in court</option>
	    <option value='56'>attended school</option>
	    <option value='8'>baptized</option>
	    <option value='9'>Bar Mitzvah</option>
	    <option value='10'>Bat Mitzvah</option>

	    <option value='14'>became a citizen</option>
	    <option value='41'>belonged to</option>
	    <option value='11'>blessed (LDS)</option>
	    <option value='3'>born</option>
	    <option value='4'>buried</option>
	    <option value='5'>christened</option>

	    <option value='13'>circumcized</option>
	    <option value='15'>confirmed</option>
	    <option value='16'>confirmed LDS</option>
	    <option value='18'>cremated</option>
	    <option value='50'>described as</option>
	    <option value='6'>died</option>

	    <option value='20'>divorced from</option>
	    <option value='22'>education</option>
	    <option value='68'>elected as</option>
	    <option value='23'>emigrated from</option>
	    <option value='24'>employed as</option>
	    <option value='25'>engaged</option>

	    <option value='36'>entered marriage contract</option>
	    <option value='12'>enumerated in a census</option>
	    <option value='66'>ethnicity</option>
	    <option value='65'>family group</option>
	    <option value='21'>filed for divorce from</option>
	    <option value='26'>first communion</option>

	    <option value='67'>funeral</option>
	    <option value='27'>graduated from</option>
	    <option value='28'>hobby of</option>
	    <option value='29'>honored as</option>
	    <option value='31'>ill with</option>
	    <option value='32'>immigrated</option>

	    <option value='30'>in hospital</option>
	    <option value='60'>in the military</option>
	    <option value='33'>interviewed</option>
	    <option value='54'>lived</option>
	    <option value='35'>marriage banns issued</option>
	    <option value='37'>marriage license</option>

	    <option value='39'>marriage settlement</option>
	    <option value='7'>marriage was annulled</option>
	    <option value='38'>married</option>
	    <option value='69'>married (69)</option>
	    <option value='70'>married (70)</option>
	    <option value='59'>medical condition</option>

	    <option value='40'>medical event</option>
	    <option value='42'>military</option>
	    <option value='43'>mission</option>
	    <option value='44'>named for</option>
	    <option value='64'>nationality</option>
	    <option value='45'>naturalized</option>

	    <option value='1'>null event 1</option>
	    <option value='46'>obituary published</option>
	    <option value='49'>ordained as</option>
	    <option value='48'>ordinance (LDS)</option>
	    <option value='34'>owned land</option>
	    <option value='52'>owned property</option>

	    <option value='61'>photo</option>
	    <option value='19'>received a degree</option>
	    <option value='53'>religious affiliation</option>
	    <option value='55'>retired</option>
	    <option value='57'>Social Security Number</option>
	    <option value='62'>Social Security Number (62)</option>

	    <option value='51'>will probated</option>
	    <option value='58'>will</option>
	    <option value='47' selected='selected'>worked as a</option>
	    <option value='0'>unused 0</option>
	    <option value='71'>unused 72</option>
	</select>
  </p>
<p>
  <button type='submit'>Create Event</button>
</p>
</form>
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
