<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetFamilyOfXml.php						*
 *									*
 *  test the getFamilyOfXml.php script					*
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
    $subject	= rawurlencode("Test Get Family Of Individual XML");

    htmlHeader('Test Get Family Of Individual XML');
?>
<body>
<?php
	pageTop(array("/genealogy.php"	=> 'Genealogy',
			"/genCountry.php?cc=CA"	=> 'Canada',
			'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
			'/legacyServices.php'	=> 'Family Tree Services'));
?>
<div class='body'>
    <h1>
	Test Get Family Of Individual XML
   </h1>
<form name='evtForm' action='/getFamilyOfXml.php' method='get'>
    <p>
      <label class='column1'>IDIR:</label>
	<input type='text' name='idir' value='' size='6' class='white left'>
    </p>
    <p>
      <label class='column1'>Census:</label>
	<input type='text' name='census' value='CA1881' size='6' class='white right'>
    </p>
    <p>
      <label class='column1'>Province:</label>
	<input type='text' name='province' value='CW' size='2' class='white right'>
    </p>
    <p>
      <label class='column1'>District:</label>
	<input type='text' name='district' value='' size='3' class='white left'>
    </p>
    <p>
      <label class='column1'>Sub-District:</label>
	<input type='text' name='subDistrict' value='' size='3' class='white rightnc'>
    </p>
    <p>
      <label class='column1'>Division:</label>
	<input type='text' name='division' value='' size='3' class='white left'>
    </p>
    <p>
      <label class='column1'>Page:</label>
	<input type='text' name='page' value='' size='3' class='white left'>
    </p>
    <p>
      <label class='column1'>Line:</label>
	<input type='text' name='line' value='' size='3' class='white left'>
    </p>
    <p>
      <label class='column1'>Family:</label>
	<input type='text' name='family' value='' size='3' class='white left'>
    </p>
  <p>
    <label class='labelSmall' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </p>
    <p>
	<button type='submit'>Test</button>
    </p>
  </form>
</div>
<?php
    pageBot();
?>
</div>
<div class='balloon' id='Helpname'>
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
value, stored in field 'SoundsLike' in the individual records is updated.
Also if the surname does not already appear in the database, a record is
added into the table 'tblNR'.
</p>
</div>
</body>
</html>
