<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testOrderMarriagesByDate.php
 *
 *  test the orderMarriagesByDate.php script
 *
 *  Parameters:
 *	idir	unique numeric key of instance of legacyIndiv
 *
 *  Copyright 2010 James A. Cobban
 *
 *  History:
 **/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader("Test Order Marriages by Date",
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
	pageTop(array("/genealogy.php"	=> "Genealogy",
			"/genCountry.php?cc=CA"	=> "Canada",
			'/Canada/genProvince.php?domain=CAON'	=> "Ontario",
			'/legacyServices.php'	=> "Family Tree Services"));
?>
  <div class='body'>
    <h1>
	Test Order Marriages by Date
    </h1>
    <form name='evtForm' action='/FamilyTree/orderMarriagesByDateXml.php' method='post'>
     <div class='row'>
      <label class='labelSmall' for='idir'>IDIR: </label>
	<input type='text' name='idir' id='idir' value='0' class='white rightnc'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='labelSmall' for='sex'>Sex: </label>
	<select name='sex' id='sex' size='1' class='white left'>
	    <option value='0' selected='selected'>Husband</option>
	    <option value='1'>Wife</option>
	</select>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='labelSmall' for='debug'>Debug: </label>
	<select name='debug' id='debug' size='1' class='white left'>
	    <option value='N' selected='selected'>False</option>
	    <option value='Y'>True</option>
	</select>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
  <button type='submit'>Order Marriages by Date</button>
      <div style='clear: both;'></div>
     </div>
</p>
</form>
</div>
<?php
	pageBot();
?>
</body>
</html>
