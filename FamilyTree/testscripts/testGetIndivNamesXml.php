<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetIndivNamesXml.php						*
 *									*
 *  Test the getIndivNamesXml.php script				*
 *									*
 *  History:								*
 *	2014/11/25	add testing of includeParents and		*
 *			includeSpouse					*
 *	2015/10/26	add ability to test range of surnames		*
 *			add id= attributes to all input elements	*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    $subject	= rawurlencode("Test Get Family Of Individual XML");

    htmlHeader('Test Get Individual Names XML');
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
	Test Get Individual Names XML
   </h1>
<form name='evtForm' id='evtForm' action='/FamilyTree/getIndivNamesXml.php' method='get'>
    <p>
	<div>
      <label class='column1'>Surname:</label>
	<input type='text' name='Surname' id='Surname' 
		value='' size='25' class='white left'>
	</div>
	<div>
      <label class='column1' for='LastSurname'>Last Surname:</label>
	<input type='text' name='LastSurname' id='LastSurname'
		value='' size='25' class='white left'>
	</div>
    </p>
    <p>
      <label class='column1'>Given Name:</label>
	<input type='text' name='GivenName' id='GivenName' 
		value='' size='40' class='white left'>
    </p>
    <p>
      <label class='column1'>Sex:</label>
	<select name='Sex' id='Sex' size='1' class='white left'>
	    <option class='male' value='M'>Male</option>
	    <option class='female' value='F'>Female</option>
	    <option class='unknown' value='' selected='selected'>Any</option>
	</select>
    </p>
    <p>
      <label class='column1'>IDIR to Exclude:</label>
	<input type='text' name='IDIR' id='IDIR' 
		value='' size='5' class='white left'>
    </p>
    <p>
      <label class='column1'>Birth Year:</label>
	<input type='text' name='BirthYear' id='BirthYear' 
		value='' size='4' class='white left'>
    </p>
    <p>
      <label class='column1'>Range:</label>
	<input type='text' name='Range' id='Range'
		value='' size='3' class='white left'>
    </p>
    <p>
      <label class='column1'>Include Married:</label>
	<input type='checkbox' name='incMarried' id='incMarried'
		value='Y' class='white left'>
    </p>
    <p>
      <label class='column1'>Include Parents:</label>
	<input type='checkbox' name='includeParents' id='includeParents' 
		value='Y' class='white left'>
    </p>
    <p>
      <label class='column1'>Include Spouse:</label>
	<input type='checkbox' name='includeSpouse' id='includeSpouse' 
		value='Y' class='white left'>
    </p>
    <p>
      <label class='column1'>Loose Match:</label>
	<input type='checkbox' name='loose' id='loose' 
		value='Y' class='white left'>
    </p>
    <p>
      <label class='column1'>Button Identifier:</label>
	<input type='text' name='buttonId' id='buttonId'
		value='' size='16' class='white left'>
    </p>
    <p>
	<button type='submit'>Test</button>
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
