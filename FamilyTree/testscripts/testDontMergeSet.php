<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDontMergeSet.php						*
 *									*
 *  Test the DontMergeEntrySet class					*
 *									*
 *  History:								*
 *	2014/03/27	use common layout routines			*
 *									*
 *  Copyright 2015 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/DontMergeEntrySet.inc';
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    htmlHeader("Test DontMergeEntrySet",
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
	Test DontMergeEntrySet
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
	$allEntries		= new DontMergeEntrySet();
	if ($allEntries->count() > 0)
	{
?>
    <table>
      <thead>
	<tr>
	  <th class='colhead'>IDIRleft</th>
	  <th class='colhead'>Surname</th>
	  <th class='colhead'>Given</th>
	  <th class='colhead'>IDIRright</th>
	  <th class='colhead'>Surname</th>
	  <th class='colhead'>Given</th>
      </thead>
<?php
	    foreach($allEntries as $entry)
	    {
?>
	<tr>
	    <td class='odd right'><?php print $entry->get('idirleft'); ?></td>
	    <td class='odd left'><?php print $entry->get('lsurname'); ?></td>
	    <td class='odd left'><?php print $entry->get('lgivenname'); ?></td>
	    <td class='odd right'><?php print $entry->get('idirright'); ?></td>
	    <td class='odd left'><?php print $entry->get('rsurname'); ?></td>
	    <td class='odd left'><?php print $entry->get('rgivenname'); ?></td>
	</tr>
<?php
	    }
?>
    </table>
<?php
	}
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
