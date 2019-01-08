<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteSubDistrictXml.php					*
 *									*
 *  test the deleteSubDistrictXml.php script				*
 *									*
 *  History:								*
 *	2015/07/02	access PHP includes using include_path		*	
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
    
    $title	= "Test deleteSubDistrictXml.php";
    $subject	= rawurlencode($title);

    htmlHeader($title);
?>
<body>
<?php
    pageTop(array("/genealogy.php"	=> "Genealogy",
		"/genCanada.html"	=> "Canada",
		"/genCensuses.php"	=> "Censuses"));
?>
<div class='body'>
    <h1>
	<?php print $title; ?> 
    </h1>
<form name='evtForm' action='/database/deleteSubDistrictXml.php' method='post'>
    <p>
	<select name='Census'>
	    <option value='CW1851'>1851</option>
	    <option value='CW1861'>1861</option>
	    <option value='CA1871'>1871</option>
	    <option value='CA1881'>1881</option>
	    <option value='CA1891'>1891</option>
	    <option value='CA1901'>1901</option>
	    <option value='CA1906'>1906</option>
	    <option value='CA1911'>1911</option>
	    <option value='CA1916'>1916</option>
	</select>
	<input type='text' name='District' value='1'>
	<input type='text' name='SubDistrict' value='1'>
	<input type='text' name='Division' value='1'>
<p>
  <button type='submit'>Execute</button>
</p>
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
