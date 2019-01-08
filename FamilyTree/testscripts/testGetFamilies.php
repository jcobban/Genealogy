<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetFamilies.php							*
 *									*
 *  Test Family::getFamilies						*
 *									*
 *  History:								*
 *	2017/10/3	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Family.inc';

    $idmrlo		= null;
    $idmrhi		= null;
    $idirhusblo		= null;
    $idirhusbhi		= null;
    $getParms		= array();
    $families		= array();
    foreach($_GET as $fldname => $value)
    {
	$fieldLc	= strtolower($fldname);
	switch($fieldLc)
	{
	    case 'idmr':
	    {
		$idmrlo		= $value;
		break;
	    }

	    case 'idmrhi':
	    {
		$idmrhi		= $value;
		break;
	    }

	    case 'idirhusb':
	    {
		$idirhusblo	= $value;
		break;
	    }

	    case 'idirhusbhi':
	    {
		$idirhusbhi	= $value;
		break;
	    }

	    default:
	    {
		$getParms[$fieldLc]	= $value;
		break;
	    }
	}	// switch
    }		// foreach

    if ($idmrlo)
    {
	if ($idmrhi)
	    $getParms['idmr']	= array($idmrlo, ':' . $idmrhi);
	else
	    $getParms['idmr']	= $idmrlo;
    }

    if ($idirhusblo)
    {
	if ($idirhusbhi)
	    $getParms['idirhusb']	= array($idirhusblo, ':' . $idirhusbhi);
	else
	    $getParms['idirhusb']	= $idirhusblo;
    }

    if (count($getParms) > 0)
	$families	= RecordSet('Families', $getParms);

    $subject	= rawurlencode("Test Family::getFamilies");

    htmlHeader('Test Family::getFamilies');
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
<div class='body'>
    <h1>
	Test Family::getFamilies
   </h1>
<?php
    foreach($families as $idmr => $family)
    {
?>
    <p>IDMR=<?php print $idmr; ?>
	<a href='/FamilyTree/editMarriages.php?idmr=<?php print $idmr; ?>'><?php print $family->getName(); ?></a>
    </p>
<?php
    }
?>
    <p>
    <form name='queryForm' action='testGetFamilies.php' method='get'>
      <div class='row'>
	<label class='column1' for='IDMR'>IDMR:</label>
	<input type='text' name='IDMR' id='IDMR' class='white rightnc'
		value='<?php print $idmrlo; ?>' size='6'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='IDMRHi'>IDMR High:</label>
	<input type='text' name='IDMRHi' id='IDMRHi' class='white rightnc'
		value='<?php print $idmrhi; ?>' size='6'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='IDIRHusb'>IDIRHusb:</label>
	<input type='text' name='IDIRHusb' id='IDIRHusb' class='white rightnc'
		value='<?php print $idirhusblo; ?>' size='6'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='IDIRHusbHi'>IDIRHusb High:</label>
	<input type='text' name='IDIRHusbHi' id='IDIRHusbHi' class='white rightnc'
		value='<?php print $idirhusbhi; ?>' size='6'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<button type='submit' id='Submit'>Submit</button>
	<div style='clear: both;'></div>
      </div>

    </form>
    </p>
  </div>
<?php
    pageBot();
?>
</body>
</html>
