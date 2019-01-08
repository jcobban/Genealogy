<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testMergeLocations.php
 *
 *  Display a web page for testing mergeLocationsXml.php
 *
 * Copyright 2010 James A. Cobban
 **/
    require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug	= false;

    htmlHeader("Test Merge Locations",
  	       array("/jscripts/CommonForm.js",
		     "/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array());
?>
  <div class='body'>
    <h1>Test Merge Locations
    </h1>
<form name='locForm' action='/FamilyTree/mergeLocationsXml.php' method='POST'>
<div>
    <table id='formTable'>
	<tbody>
	    <tr>
		<th class='left'>
Merge To IDLR
		</th>
		<td class='left'>
		    <input type='text' size='6' name='to' class='white rightnc'>
		</td>
	    </tr>
	    <tr>
		<th class='left'>
Merge From IDLRs
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
