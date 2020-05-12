<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteEvent.php													*
 *																		*
 *  test the deleteEvent.php script										*
 *																		*
 *  Parameters:															*
 *		ider	unique numeric key of instance of legacyEvent			*
 *				if set to zero requests creation of new instance		*
 *																		*
 *  History:															*
 *		2014/12/02		enclose comment blocks							*
 *																		*
 *  Copyright &copy; 2014 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Delete Event',
		array(	"/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array( "/genealogy.php"	=> 'Genealogy',
			"/genCountry.php?cc=CA"	=> 'Canada',
			'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
			'/legacyServices.php'	=> 'Family Tree Services'));
?>
    <h1>
	Test Delete Event;
    </h1>
    <form name='evtForm' action='/FamilyTree/deleteEventXml.php' 
            method='post'>
	  <p>
	    <label class='column1'>IDIME:</label>
		<input type='text' name='idime' value='0' class='white left'>
	  </p>
	  <p>
	    <label class='column1'>Citation Type:</label>
		<input type='text' name='cittype' value='30' class='white left'>
	  </p>
	  <p>
	    <button type='submit' id='submit'>Delete Event</button>
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
