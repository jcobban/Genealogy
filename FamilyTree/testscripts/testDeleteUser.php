<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteUser.php							*
 *									*
 *  test the deleteUser.php script					*
 *									*
 *  Parameters:								*
 *	userid	unique numeric key of instance of legacyUser		*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Delete User',
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"	=> "Genealogy",
		  "/genCountry.php?cc=CA"	=> "Canada",
		  '/Canada/genProvince.php?domain=CAON'=> "Ontario",
		  '/FamilyTree/Services.php'	=> "Family Tree Services"));
?>
<div class='body'>
    <h1>
	Test Delete User;
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
<form name='evtForm' action='/deleteUserXml.php' method='post'>
  <div class='row'>
	<label class='column1' for='userid'>
	  User Name:
	</label>
	<input type='text' class='white left' name='userid' is='userid' value=''>
  </div>
  <div class='row'>
	<label class='column1' for='debug'>
	  Debug:
	</label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </div>
  <div class='row'>
  	<button type='submit' name='submit'>Delete User</button>
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
