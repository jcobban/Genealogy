<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testOrderPicturesByDate.php						*
 *									*
 *  test the orderPicturesByDateXml.php script				*
 *									*
 *  Parameters:								*
 *	idir	unique numeric key of instance of legacyIndiv		*
 *									*
 *  History:								*
 *	2014/03/21	created						*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader("Test Order Pictures by Date",
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> 'Genealogy',
		"/genCountry.php?cc=CA"		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		'/FamilyTree/Services.php'		=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test Order Pictures by Date
    </h1>
<form name='evtForm' action='/orderPicturesByDateXml.php' method='post'>
    <div class='row'>
	<label class='column1' for='idir'>IDIR:</label>
	<input type='text' class='white rightnc' name='idir' value='0'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
	<button type='submit'>Order Pictures by Date</button>
      <div style='clear: both;'></div>
    </div>
</form>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidir'>
<p>Edit the unique numeric key (IDIR) of the individual to update.
</p>
</div>
</body>
</html>
