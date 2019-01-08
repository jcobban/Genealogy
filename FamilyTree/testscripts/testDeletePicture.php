<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeletePicture.php						*
 *									*
 *  test the updatePicture.php script					*
 *									*
 *  Parameters:								*
 *	ider	unique numeric key of instance of legacyPicture	*
 *		if set to zero requests creation of new instance	*
 *									*
 *  History:								*
 *	2010/09/10	created						*
 *	2014/09/19	updated to conform to layour standards		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug	= false;

    htmlHeader('Test Delete Picture');
?>
<body>
<?php
    pageTop(array(
		"/genealogy.php"		=> "Genealogy",
		"/genCountry.php?cc=CA"		=> "Canada",
		'/Canada/genProvince.php?domain=CAON'	=> "Ontario",
		'/FamilyTree/Services.php'	=> "Family Tree Services"));
?>
<div class='body'>
  <h1>
	Test Delete Picture
  </h1>
  <form name='evtForm' action='/deletePictureXml.php' method='post'>
    <div class='row'>
	<label class='column1' for='idbr'>IDBR:</label>
	<input type='text' id='idbr' name='idbr'
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
	<label class='column1' for='rownum'>Rownum:</label>
	<input type='text' id='rownum' name='rownum'
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
	<label class='column1' for='formname'>Form Name:</label>
	<input type='text' id='formname' name='formname'
		class='white leftnc' value='evtForm'>
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
	<button type='submit'>Delete Picture</button>
      <div style='clear: both;'></div>
    </div>
  </form>
</div> <!-- body -->
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
