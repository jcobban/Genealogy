<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteMarriage.php						*
 *									*
 *  test the deleteMarriage.php script					*
 *									*
 *  Parameters:								*
 *	idmr	unique numeric key of instance of legacyMarriage	*
 *	idir	unique numeric key of spouse in marriage		*
 *	child	unique numeric key of child in marriage			*
 *									*
 *  History:								*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *	2016/12/20	add support for identifying point of view	*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    //$debug	= false;

    htmlHeader('Test Delete Marriage');
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
	Test Delete Marriage;
    </h1>
<form name='evtForm' action='/FamilyTree/deleteMarriageXml.php' method='post'>
    <div class='row'>
      <label class='column1' for='idmr'>IDMR:</label>
	<input type='text' class='white rightnc' name='idmr' id='idmr' value='0'>
      <div style='clear: both;'></div>
    </div>
<?php
    if (isset($_GET['idir']))
    {
	$idir		= $_GET['idir'];
?>
    <div class='row'>
      <label class='column1' for='idir'>IDIR of Spouse:</label>
	<input type='text' class='white rightnc' name='idir' id='idir'
		value='<?php print $idir; ?>'>
      <div style='clear: both;'></div>
    </div>
<?php
    }
    if (isset($_GET['child']))
    {
	$child		= $_GET['child'];
?>
    <div class='row'>
      <label class='column1' for='child'>IDIR of Child:</label>
	<input type='text' class='white rightnc' name='child' id='child'
		value='<?php print $child; ?>'>
      <div style='clear: both;'></div>
    </div>
<?php
    }
?>
    <div class='row'>
      <label class='labelSmall' for='debug'>
	Debug:
      </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <button type='submit'>Delete Marriage</button>
      <div style='clear: both;'></div>
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
