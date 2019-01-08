<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testOrderChildren.php
 *
 *  test the orderChildren.php script
 *
 *  Parameters:
 *	idir	unique numeric key of instance of legacyIndiv
 *
 *  Copyright 2010 James A. Cobban
 *
 *  History:
 **/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Order Children by Date',
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
	Test Order Children by Date
    </h1>
  <form name='evtForm' action='/FamilyTree/orderChildrenXml.php' method='post'>
    <div class='row'>
	<label class='column1' for='idmr'>IDMR:</label>
	<input type='text' name='idmr' id='idmr' value='0'>
    </div>
    <div class='row'>
	<button type='submit'>Order Children by Birth Date</button>
    </div>
  </form>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidmr'>
<p>The unique numeric key (IDMR) of the family to update.
</p>
</div>
</body>
</html>
