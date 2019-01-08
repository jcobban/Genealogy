<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testMergeAddresses.php
 *
 *  Display a web page for testing mergeAddressesXml.php
 *
 * Copyright 2015 James A. Cobban
 **/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader("Test Merge Addresses",
  	       array("/jscripts/CommonForm.js",
		     "/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array());
?>
  <div class='body'>
    <h1>Test Merge Addresses
    </h1>
<form name='locForm' action='/FamilyTree/mergeAddressesXml.php' method='POST'>
<div>
    <table id='formTable'>
	<tbody>
	    <tr>
		<th class='left'>
Merge To IDAR
		</th>
		<td class='left'>
		    <input type='text' size='6' name='to' class='white rightnc'>
		</td>
	    </tr>
	    <tr>
		<th class='left'>
Merge From IDARs
		</th>
		<td class='left'>
		    <input type='text' size='64' name='from' class='white rightnc'>
		</td>
	    </tr>
	</tbody>
    </table>
<p>
  <button type='submit'>Merge
</button>
</p>
</form>
</div>
<?php
    pageBot();
?>
</body>
</html>
