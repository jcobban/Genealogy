<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteSource.php						*
 *									*
 *  test the updateSourceXml.php script					*
 *									*
 *  Parameters:								*
 *	idsr	unique numeric key of instance of legacySource		*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Delete Source',
		array(	"/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> 'Genealogy',
		  "/genCountry.php?cc=CA"		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test Delete Source
    </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
    }	// error message to display
    else
    {
?>
<form name='evtForm' action='/deleteSourceXml.php' method='post'>
  <div class='row'>
    <label class='column1' for='idsr'>IDSR:</label>
    <input type='text' name='idsr' id='idsr' value='0' class='white rightnc'>
  </div>
  <div>
    <label class='column1' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </div>
  <div class='row'>
    <button type='submit'>Delete</button>
  </div>
</form>
<?php
    }
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
