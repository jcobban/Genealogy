<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testChooseIndivid.php						*
 *									*
 *  Test the chooseIndivid.php script					*
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

    htmlHeader("Test Choose Individual",
		array('/jscripts/js20/http.js',
			'/jscripts/CommonForm.js',
			'/jscripts/util.js',
			'testChooseIndivid.js'));
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
	Test Choose Individual
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
  <form name='indForm' action='donothing.php' method='get'>
    <div class='row'>
      <label class='labelSmall' for='IDIR'>
	IDIR:
      </label>
      <input type='text' name='IDIR'  id='IDIR' 
		class='white rightnc' value='' size='6'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <label class='labelSmall' for='Surname'>
	Surname:
      </label>
      <input type='text' name='Surname' id='Surname'
		class='white left' value='' size='40'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <label class='labelSmall' for='GivenName'>
	Given Name:
      </label>
      <input type='text' name='GivenName' id='GivenName'
		class='white left' value='' size='40'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <label class='labelSmall' for='ParentsIdmr'>
	Parents IDMR:
      </label>
      <input type='text' name='ParentsIdmr' id='ParentsIdmr'
		class='white rightnc' value='' size='8'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <label class='labelSmall' for='BirthMin'>
	Min Birth Year:
      </label>
      <input type='text' name='BirthMin' id='BirthMin'
		class='white left' value='' size='4'>
      <div style='clear: both;'></div>
    </div>
    <div class='row'>
      <label class='labelSmall' for='BirthMax'>
	Min Birth Year:
      </label>
      <input type='text' name='BirthMax' id='BirthMax'
		class='white left' value='' size='4'>
      <div style='clear: both;'></div>
    </div>
     <div class='row'>
      <label class='labelSmall' for='debug'>Debug: </label>
	<select name='debug' id='debug' size='1' class='white left'>
	    <option value='N' selected='selected'>False</option>
	    <option value='Y'>True</option>
	</select>
      <div style='clear: both;'></div>
     </div>
    <div class='row'>
      <button type='button' id='choose'>Choose Individual</button>
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
