<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteName.php							*
 *									*
 *  test the updateName.php script					*
 *									*
 *  Parameters:								*
 *	idnx	unique numeric key of instance of Name			*
 *	surname	display matching entries for surname			*
 *									*
 *  History:								*
 *	2014/12/25	created						*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/common.inc';

    $idnx	= 1;
    $limit	= 20;
    $surname	= null;
    $order	= null;

    foreach($_GET as $name => $value)
    {		// loop through all parameters
	switch(strtolower($name))
	{	// act on specific parameters
	    case 'idnx':
	    {	// IDNX
		$idnx		= $value;
		break;
	    }	// IDNX

	    case 'limit':
	    {	// limit
		$limit		= $value;
		break;
	    }	// limit

	    case 'surname':
	    {	// surname
		$surname	= $value;
		break;
	    }	// surname

	    case 'order':
	    {	// surname
		$order		= $value;
		break;
	    }	// surname
	}	// act on specific parameters
    }		// loop through all parameters

    if ($idnx > 1)
	$nameParms	= array('idnx'	=> $idnx,
				'limit'	=> $limit);
    else 
    if (strlen($surname) > 0)
	$nameParms	= array('surname'=> $surname,
				'limit'	=> $limit);
    else
	$nameParms	= null;

    if (!is_null($order))
	$nameParms['`order`']	= $order;

    $nameParms['order']		= "IDNX";
    if ($nameParms)
	$result		= new RecordSet('Names', $nameParms);
    else
	$result		= null;

    htmlHeader('Test Delete Name');
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> "Genealogy",
		  "/genCountry.php?cc=CA"		=> "Canada",
		  '/Canada/genProvince.php?domain=CAON'	=> "Ontario",
		  '/FamilyTree/Services.php'	=> "Family Tree Services"));
?>
<div class='body'>
    <h1>
	Test Delete Name;
    </h1>
<?php
    if ($result)
    {		// display entries matching parameters
	foreach($result as $idnx => $name)
	{
?>
    <p>IDNX: <?php print $idnx; ?>,
	IDIR: <?php print $name->get('idir'); ?>,
	Given Name: <?php print $name->get('givenname'); ?>,
	Surname: <?php print $name->get('surname'); ?>,
	Order: <?php print $name->get('order'); ?>
<?php
	}
    }		// display entries matching parameters
?>
    <form name='evtForm' action='/FamilyTree/deleteNameXml.php' method='post'>
      <p>
	<label class='column1' for='idnx'>IDNX:</label>
	<input type='text' id='idnx' name='idnx'
		class='white rightnc' value='<?php print $idnx; ?>'>
      </p>
      <p>
	<label class='column1' for='debug'>Debug:</label>
	<input type='checkbox' id='debug' name='debug'
		class='white left' value='Y'>
      </p>
      <p>
	<button type='submit'>Delete Name</button>
      </p>
    </form>
  </div> <!-- body -->
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
