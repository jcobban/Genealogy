<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testAddFamily.php							*
 *									*
 *  Test the addFamilyXml script					*
 *									*
 *  Parameters (passed by Get):						*
 *	type		'idir' create new family where IDIR is spouse	*
 *			'child' create parents for child		*
 *									*
 *  History:								*
 *	2011/06/15	created						*
 *	2014/03/27	use common layout routines			*
 *			use HTML 4 features, such as <label>		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    if (array_key_exists('type', $_GET))
	$type	= $_GET['type'];
    else
	$type	= 'idir';

    $subject	= rawurlencode("Test Add Family");
    htmlHeader('Test Add Family',
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
	Test Add Family Script
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
<form name='evtForm' action='/FamilyTree/addFamilyXml.php' method='post'>
    <p>
      <label class='labelSmall' for='<?php print $type; ?>'>
	IDIR of <?php if ($type=='idir') print "Spouse"; else print "Child"; ?>:
      </label>
      <input type='text' name='<?php print $type; ?>' id='<?php print $type; ?>'
		class='white rightnc' value='0'>
    </p>
<p>
  <button type='submit' id='Submit'>Add Family</button>
</p>
</form>
<?php
    }	// no errors
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidir'>
<p>Edit the unique numeric key (IDIR) of the individual 
for whom a marriage is to be added.
</p>
</div>
<div class='balloon' id='Helpchild'>
<p>Edit the unique numeric key (IDIR) of the individual 
for whom a set of parents is to be added.
</p>
</div>
</body>
</html>
