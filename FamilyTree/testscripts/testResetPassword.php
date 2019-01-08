<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testResetPassword.php						*
 *									*
 *  test the resetPassword.php script					*
 *									*
 *  Parameters:								*
 *	userid	unique numeric key of instance of legacyUser		*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2016/01/06	created						*
 *									*
 *  Copyright 2016 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Reset Password',
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
	Test Reset Password;
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
<form name='evtForm' action='/resetPassword.php' method='post'>
  <div class='row'>
	<label class='column1' for='userid'>
	  User Name:
	</label>
	<input type='text' class='white left' name='uid' id='uid' value=''>
  </div>
  <div class='row'>
	<label class='column1' for='debug'>
	  Debug:
	</label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </div>
  <div class='row'>
  	<button type='submit' id='Submit'>Reset Password</button>
  </div>
</form>
<?php
   }
?>
</div>
<?php
   pageBot();
?>
<div class='balloon' id='Helpuserid'>
The userid of the contributor whose password is to be reset.
</div>
<div class='balloon' id='Helpdebug'>
If this option is checked then diagnostic trace output will be generated.
</div>
<div class='balloon' id='HelpSubmit'>
Click on this button to run the script to reset the password.
</div>
</body>
</html>
