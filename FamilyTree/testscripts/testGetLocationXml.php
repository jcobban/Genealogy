<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetLocationXml.php						*
 *									*
 *  test the getLocationXml.php script					*
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
    $subject	= rawurlencode("Test Get Location XML");

    htmlHeader('Test getLocationXml.php');
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
	Test getLocationXml.php
    </h1>
  <form name='evtForm' action='/getLocationXml.php' method='get'>
    <p>
      <label class='column1'>Name:</label>
	<input type='text' name='name' value=''>
    </p>
  <p>
    <label class='labelSmall' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </p>
    <p>
      <button type='submit'>Text</button>
    </p>
  </form>
</div>
<?php
    pageBot();
?>
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
