<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateUserXml.php						*
 *									*
 *  test the updateUserXml.php script					*
 *									*
 *  History:								*
 *	2015/06/30	created						*
 *	2015/07/02	access PHP includes using include_path		*	
 *									*
 *  Copyright 2015 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    $title	= "Test Update User Xml";
    $subject	= rawurlencode($title);

    htmlHeader($title);
?>
<body>
<?php
    pageTop(array("/genealogy.html"	=> 'Genealogy',
		  "/genCanada.html"	=> 'Canada',
		  '/genCensuses.php'	=> 'Censuses'));
?>
<div class='body'>
    <h1>
	<?php print $title; ?> 
    </h1>
  <form name='evtForm' action='/updateUserXml.php' method='post'>
    <div class='row'>
	<label class='label' for='username'>User Name:</label>
	<input type='text' name='username' class='white leftnc' value=''>
	<div style='clear: both;'></div>
    </div>
    <div class='row'>
	<label class='label' for='password'>Password:</label>
	<input type='text' name='password' class='white leftnc' value=''>
	<div style='clear: both;'></div>
    </div>
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
