<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testName.php														*
 *																		*
 *  Test driver for the Name class										*
 * 																		*
 *  History: 															*
 *		2019/01/03		create											*
 *																		*
 *  Copyright 2019 James A. Cobban										*
 ************************************************************************/
 require_once __NAMESPACE__ . '/Name.inc';

    htmlHeader('Test Surname',
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> 'Genealogy',
		  "/genCountry.php?cc=CA"		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
    <h1>Test Name</h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
    }	// error message to display
    else
    {
	showTrace();
?>
    <form name='indForm' action='testSurname.php' method='post'>
<?php
	if ($debug)
	{
?>
      <input name='Debug' type='hidden' value='Y'>
<?php
	}	// debugging

	if (array_key_exists('Surname', $_REQUEST))
	{
	    $surname	= $_REQUEST['Surname'];
	    $eparms	= array('Surname' => $surname);
	    $slist	= new RecordSet('Surnames', $eparms);
	    if ($slist->count() > 0)
	    {			// found matches
		foreach($slist as $idnr => $surnameObj)
		{
?>
      <p>IDNR=<?php print $idnr; ?> is <?php print $surnameObj->getName(); ?>
      </p>
<?php
		}
	    }			// found matches
	    else
	    {			// create new entry
		$parms		= array('surname' => $surname);
		$surnameObj	= new Surname($parms);
		$idnr		= $surnameObj->getIdnr();
?>
      <p>IDNR=<?php print $idnr; ?> is <?php print $surnameObj->getName(); ?>
      </p>
<?php
	    }			// create new entry
	    showTrace();
	}
	else
	if (array_key_exists('IDNR', $_POST))
	{
	    print "<p>IDNR=" . print_r($_POST['IDNR'], true);
	    $eparms	= array('IDNR' => $_POST['IDNR']);
	    $slist	= new RecordSet('Surnames', $eparms);
	    foreach($slist as $idnr => $surnameObj)
	    {
?>
      <p>IDNR=<?php print $idnr; ?> is <?php print $surnameObj->getName(); ?>
      </p>
<?php
	    }
	    $nextIdnr	= $idnr + 1;
	}
	else
	{
	    $nextIdnr	= 1;
	    if (array_key_exists('NextIDNR', $_POST))
		$nextIdnr	= intval($_POST['NextIDNR']);
	    for($i = $nextIdnr; $i < $nextIdnr + 10; ++$i)
	    {		// display sequence of names
		try {
		    $surnameObj	= new Surname(array('idnr' => $i));
?>
      <p>
	<input name='IDNR[]' type='text' value='<?php print $i; ?>'
		class='ina rightnc'>
	<?php print $surnameObj->getName(); ?>
      </p>
<?php
		} catch(Exception $e) {
?>
      <p class='message'>
	No record for IDNR=<?php print $i; ?>
      </p>
<?php
		}	// catch
	    }		// display sequence of events
	    $nextIdnr	= $i;
	}		// array of IDNRs not passed
    }			// no errors
?>
      <p>
<?php
    if (isset($nextIdnr))
    {
?>
	<input type='text' name='NextIDNR' class='white rightnc'
		value='<?php print $nextIdnr; ?>'>
<?php
    }
    else
    if (isset($surname))
    {
?>
	<input type='text' name='Surname' class='white left'
		value='<?php print $surname; ?>'>
<?php
    }
?>
      </p>
      <p>
	<button type='submit' id='Submit'>Submit</button>
      </p>
    </form>
  </div>
<?php
    pageBot();
?>
</body>
</html>
