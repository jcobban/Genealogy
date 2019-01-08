<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testAddDontMerge.php						*
 *									*
 *  Test the addDontMergeXml.php script					*
 *									*
 *  History:								*
 *	2014/03/27	use common layout routines			*
 *			use HTML 4 features, such as <label>		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    htmlHeader("Test Add Don't Merge Entry",
		array('/jscripts/js20/http.js',
			'/jscripts/CommonForm.js',
			'/jscripts/util.js'));
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
	Test Add Don't Merge
    </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
    <p class='message'>
	<?php print $msg; ?>
    </p>
<?php
    }
    else
    {	// no errors
?>
<form name='evtForm' id='evtForm' action='/FamilyTree/addDontMergeXml.php' method='post'>
    <p>
    <label class='labelSmall' for='idirleft'>
	IDIRleft:
    </label>
	<input type='text' name='idirleft' id='idirleft'
		class='white rightnc' value='0'>
</p>
    <p>
    <label class='labelSmall' for='idirright'>
	IDIRright:
    </label>
	<input type='text' name='idirright' id='idirright'
		class='white rightnc' value='6'>
</p>
  <button type='submit'>Add Don't Merge</button>
</p>
</form>
<?php
    }	// no errors
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidirleft'>
<p>Edit the unique numeric key (IDIR) of the individual to update.
</p>
<div class='balloon' id='Helpidirright'>
<p>Edit the unique numeric key (IDIR) of the individual to update.
</p>
</div>
</body>
</html>
