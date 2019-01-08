<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteCitation.php						*
 *									*
 *  test the detChildXml.php script					*
 *									*
 *  Copyright 2014 James A. Cobban					*
 *									*
 *  History:								*
 *	2014/12/22	use <label>					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test DetachChild',
		array(	"/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
	pageTop(array("/genealogy.php"			=> "Genealogy",
		      "/genCountry.php?cc=CA"			=> "Canada",
		      '/Canada/genProvince.php?domain=CAON'	=> "Ontario",
		      '/FamilyTree/Services.php'	=> "Tree Services"));
?>
    <h1>
	Test Detach Child
    </h1>
    <form name='evtForm' action='/detChildXml.php' method='post'>
      <div class='row'>
	<label class='column1' for='idir'>IDIR:</label>
	<input type='text' name='idir' value='0' class='white rightnc'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='idcr'>IDCR:</label>
	<input type='text' name='idcr' value='0' class='white rightnc'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='idmr'>IDMR:</label>
	<input type='text' name='idmr' value='0' class='white rightnc'>
	<div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='column1' for='debug'>Debug: </label>
	<select name='debug' id='debug' size='1' class='white left'>
	    <option value='N' selected='selected'>False</option>
	    <option value='Y'>True</option>
	</select>
	<div style='clear: both;'></div>
     </div>
     <div class='row'>
	<button type='submit'>Delete Citation</button>
	<div style='clear: both;'></div>
     </div>
</p>
</form>
</body>
</html>
