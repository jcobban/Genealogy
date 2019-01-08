<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteLocation.php						*
 *									*
 *  test the updateLocation.php script					*
 *									*
 *  Parameters:								*
 *	idlr	unique numeric key of instance of legacyLocation	*
 *									*
 *  History:								*
 *	2010/09/10	created						*
 *	2014/09/19	updated to conform to layout standards		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Delete Location');
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> "Genealogy",
		  "/genCountry.php?cc=CA"		=> "Canada",
		  '/Canada/genProvince.php?domain=CAON'	=> "Ontario",
		  '/FamilyTree/Services.php'	=> "Family Tree Services"));
?>
<div class='body'>
    <h1>
	Test Delete Location;
    </h1>
<form name='evtForm' action='/FamilyTree/deleteLocation.php' method='post'>
  <p>
    <label class='column1' for='idlr'>IDLR:</label>
	<input type='text' id='idlr' name='idlr'
		class='white rightnc' value='0'>
  </p>
  <p>
    <label class='column1' for='debug'>Debug:</label>
	<input type='checkbox' id='debug' name='debug'
		class='white left' value='Y'>
  </p>
  <p>
    <button type='submit'>Delete Location</button>
  </p>
</form>
</div> <!-- body -->
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
