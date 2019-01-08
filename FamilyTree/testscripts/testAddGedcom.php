<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testAddGedcom.php													*
 *																		*
 *  Test the addGedcomXml.php script									*
 *																		*
 *  History:															*
 *		2018/11/29      created                                         *
 *																		*
 *  Copyright &copy; 2014 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    htmlHeader("Test Add Gedcom",
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
	Test Add Gedcom Script
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
    <form name='evtForm' action='/FamilyTree/gedcomAddXml.php' method='post'>
     <div class='row'>
       <label class='labelSmall' for='userid'>UserID:
       </label>
	   <input type='text' name='userid' id='userid' 
		        class='white left' value='jcobban'>
       <div style='clear: both;'></div>
     </div>
     <div class='row'>
       <label class='labelSmall' for='gedname'>GedCom Name:
       </label>
	   <input type='text' name='gedname' id='gedname' 
		        class='white left' value='test'>
       <div style='clear: both;'></div>
     </div>
<?php
    for ($i = 0; $i < 10; $i++)
    {
?>
     <div class='row'>
       <label class='labelSmall' for='line'>Line:
       </label>
	   <input type='text' name='line[]'
		        class='white left' value='0'>
       <div style='clear: both;'></div>
     </div>
<?php
    }	// no errors
?>
     <div class='row'>
       <label class='labelSmall' for='debug'>Debug:
       </label>
	   <input type='checkbox' name='debug' id='debug' 
		        class='white left' value='Y'>
       <div style='clear: both;'></div>
     </div>
     <div class='row'>
       <button type='submit'>Add GedCom</button>
      <div style='clear: both;'></div>
     </div>
    </form>
<?php
    }	// no errors
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidir'>
<p>Edit the unique numeric key (IDIR) of the individual to add to the family.
</p>
</div>
<div class='balloon' id='Helpidmr'>
<p>Edit the unique numeric key (IDMR) of the family to which to add the
individual.
</p>
</div>
</body>
</html>
