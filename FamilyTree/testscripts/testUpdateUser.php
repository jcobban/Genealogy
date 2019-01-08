<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateUser.php							*
 *									*
 *  test the updateUserXml.php script					*
 *									*
 *  Parameters:								*
 *	userid		username to update				*
 *	password	new password					*
 *									*
 *  History:								*
 *	2014/07/25	Created						*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';


    htmlHeader('Test Reset User Password',
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"	=> "Genealogy",
		  "/genCountry.php?cc=CA"	=> "Canada",
		  '/Canada/genProvince.php?domain=CAON'	=> "Ontario",
		  '/FamilyTree/Services.php'	=> "Family Tree Services"));
?>
<div class='body'>
    <h1>
	Test Update User
    </h1>
<form name='evtForm' action='/updateUserXml.php' method='post'>
    <div class='row'>
	<label class='column1'>User Name:</label>
	<input type='text' class='white left' name='username' id='username'
		value=''>
    </div>
    <div class='row'>
	<label class='column1'>New Password:</label>
	<input type='text' class='white left' name='password' id='password'
		value=''>
    </div>
    <div class='row'>
  <button type='submit' name='submit'>Reset User</button>
    </div>
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
