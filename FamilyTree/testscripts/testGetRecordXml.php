<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetRecordXml.php						*
 *									*
 *  test the getRecordXml.php script					*
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
    $key	= $_GET['key'];
    $keyuc	= strtoupper($key);
    $subject	= rawurlencode("Test Get Record XML");

    htmlHeader('Test getRecordXml.php');
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
	Test getRecordXml.php
    </h1>
<form name='evtForm' action='/getRecordXml.php' method='get'>
    <p>
	<label class='column1' for='idir'>
	  <?php print $keyuc; ?>:
	</label>
	<input type='text' name='<?php print $key; ?>' value='0'>
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
<div class='botcrumbs'>
<div class='fullwidth'>
    <span class='left'>
	<a href='mailto:webmaster@jamescobban.net?subject=<?php print $subject; ?>'
>Contact Author</a>
	<br/>
	<a href='/genealogy.php'>Genealogy</a>:
	<a href='/genCountry.php?cc=CA'>Canada</a>:
	<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
	<a href='/FamilyTree/Services.php'>Family Tree Services</a>:
	<a href='/FamilyTree/nominalIndex.html'>Top Index</a>:
    </span>
    <span class='right'>
	<img SRC='/logo70.gif' height='70' width='70' alt='James Cobban Logo'>
    </span>
    <div style='clear: both;'></div>
</div>
</div>
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
