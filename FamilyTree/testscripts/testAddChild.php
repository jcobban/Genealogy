<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testAddChild.php													*
 *																		*
 *  Test the addChildXml.php script										*
 *																		*
 *  History:															*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2014/03/27		use common layout routines						*
 *						use HTML 4 features, such as <label>			*
 *		2014/11/30		add debug option								*
 *		2014/12/02		enclose comment blocks							*
 *																		*
 *  Copyright &copy; 2014 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    htmlHeader("Test Add Child",
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
	Test Add Child Script
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
    <form name='evtForm' action='/FamilyTree/addChildXml.php' method='post'>
     <div class='row'>
      <label class='labelSmall' style='width: 5em;' for='idmr'>IDMR:
      </label>
	<input type='text' name='idmr' id='idmr' 
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='labelSmall' style='width: 5em;' for='idir'>IDIR:
      </label>
	<input type='text' name='idir' id='idir' 
		class='white rightnc' value='0'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='labelSmall' style='width: 5em;' for='debug'>Debug:
      </label>
	<input type='checkbox' name='debug' id='debug' 
		class='white left' value='Y'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <button type='submit'>Add child</button>
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
