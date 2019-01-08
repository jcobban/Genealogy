<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testConfirmUser.php							*
 *									*
 *  test the confirmUser.php script					*
 *									*
 *  Parameters:								*
 *	userid	unique numeric key of instance of legacyUser		*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2014/12/02	enclose comment blocks				*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/commomn.inc';

    htmlHeader('Test Confirm User',
	       array("/jscripts/js20/http.js",
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
	Test Confirm User;
    </h1>
<form name='evtForm' action='/confirmUserXml.php' method='post'>
<p>
    <label class='column1'>User Name:</label>
	<input type='text' name='userid' value='' class='white rightnc'>
</p>
<p>
  <button type='submit' id='submit'>Confirm User</button>
</p>
</form>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpuserid'>
<p>userid of the contributor
</p>
</div>
</body>
</html>
