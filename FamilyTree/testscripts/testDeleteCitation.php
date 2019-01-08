<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteCitation.php						*
 *									*
 *  test the updateCitation.php script					*
 *									*
 *  Parameters:								*
 *	ider	unique numeric key of instance of legacyCitation	*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug	= false;

    htmlHeader("Test Delete Citation",
		array("/jscripts/js20/http.js",
		      "/jscripts/CommonForm.js",
		      "/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
		  '/genCountry.php?cc=CA'		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test Delete Citations
    </h1>
    <form name='evtForm' action='/FamilyTree/deleteCitationsXml.php' method='post'>
     <div class='row'>
	<label class='column1' for='idir'>IDIR:</label>
	<input type='text' name='idir' id='idir'
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
        <label class='column1' for='type'>Type:</label>
	<input type='text' name='type' id='type'
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
    <label class='column1' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
	<button type='submit'>Delete Citation</button>
      <div style='clear: both;'></div>
     </div>
    </form>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidir'>
The record number of the instance of Person
</div>
<div class='balloon' id='Helptype'>
The type of citation to remove
</div>
</body>
</html>
