<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testAddCit.php							*
 *									*
 *  Test the addCitXml.php script					*
 *									*
 *  History:								*
 *	2014/03/27	use common layout routines			*
 *			use HTML 4 features, such as <label>		*
 *	2014/04/30	add ability to specify name of main key		*
 *			add <select> for IDSR				*
 *			add parm override for selected idsr, and row	*
 *	2014/11/30	add debug option				*
 *	2015/10/07	migrate from MDB2 to PDO			*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    $keyname	= 'idime';
    $keyvalue	= 0;
    $idsr	= 1;
    $rowid	= 0;

    foreach ($_GET as $parm => $value)
    {			// loop through parameters
	switch(strtolower($parm))
	{		// act on specific keys
	    case 'idir':
	    case 'idmr':
	    case 'ider':
	    case 'idcr':
	    case 'idnx':
	    {		// specific record identifier
		$keyname	= $parm;
		$keyvalue	= $value;
		break;
	    }		// specific record identifier

	    case 'idsr':
	    {		// master source
		$idsr		= $value;
		break;
	    }		// master source

	    case 'row':
	    {		// row on invoking form
		$rowid		= $value;
		break;
	    }		// row on invoking form

	}		// act on specific keys
    }			// loop through parameters
    htmlHeader("Test Add Citation",
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
	Test Add Citation
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
    {		// no errors
?>
<form name='evtForm' id='evtForm' action='/FamilyTree/addCitXml.php' method='post'>
  <p>
    <label class='labelSmall' for='<?php print $keyname; ?>'>
	<?php print strtoupper($keyname); ?>:
    </label>
    <input type='text'
		name='<?php print $keyname; ?>' id='<?php print $keyname; ?>'
		class='white rightnc' value='<?php print $keyvalue; ?>'>
  </p>
  <p>
    <label class='labelSmall' for='type'>
	Citation Type:
    </label>
	<select name='type' id='type' class='white left'>
	    <option value=''>Choose an event type:</option>
	    <option value='0'>0 - UNSPECIFIED</option>
	    <option value='1'>1 - NAME</option>
	    <option value='2'>2 - BIRTH</option>
	    <option value='3'>3 - CHRISTEN</option>
	    <option value='4'>4 - DEATH</option>
	    <option value='5'>5 - BURIED</option>
	    <option value='6'>6 - NOTESGENERAL</option>
	    <option value='7'>7 - NOTESRESEARCH</option>
	    <option value='8'>8 - NOTESMEDICAL</option>
	    <option value='9'>9 - DEATHCAUSE</option>
	    <option value='15'>15 - LDSB</option>
	    <option value='16'>16 - LDSE</option>
	    <option value='26'>26 - LDSC</option>
	    <option value='27'>27 - LDSI</option>
	    <option value='10'>10 - ALTNAME</option>
	    <option value='11'>11 - CHILDSTATUS</option>
	    <option value='12'>12 - CPRELDAD</option>
	    <option value='13'>13 - CPRELMOM</option>
	    <option value='17'>17 - LDSP</option>
	    <option value='18'>18 - LDSS</option>
	    <option value='19'>19 - NEVERMARRIED</option>
	    <option value='20'>20 - MAR</option>
	    <option value='21'>21 - MARNOTE</option>
	    <option value='22'>22 - MARNEVER</option>
	    <option value='23'>23 - MARNOKIDS</option>
	    <option value='24'>24 - MAREND</option>
	    <option value='30' selected='selected'>30 - EVENT</option>
	    <option value='31'>31 - 31 - MAREVENT</option>
	</select>
  </p>
  <p>
    <label class='labelSmall' for='idet'>
	Event Type:
    </label>
	<select name='idet' id='idet' class='white left'>
	    <option value='0'>0 - Choose an event type:</option>
	    <option value='1'>1 - </option>
	    <option value='2'>2 - Adoption</option>
	    <option value='3' selected='selected'>3 - Birth</option>
	    <option value='4'>4 - Burial</option>
	    <option value='5'>5 - Christening</option>
	    <option value='16'>16 - Confirmation (LDS)</option>
	    <option value='6'>6 - Death</option>
	    <option value='8'>8 - Baptism</option>
	    <option value='15000'>15000 - Baptism (LDS)</option>
	    <option value='9'>9 - BarMitzvah</option>
	    <option value='10'>10 - BasMitzvah</option>
	    <option value='71'>71 - Birth Registration</option>
	    <option value='11'>11 - Blessing</option>
	    <option value='12'>12 - Census</option>
	    <option value='13'>13 - Circumcision</option>
	    <option value='14'>14 - Citizenship</option>
	    <option value='15'>15 - Confirmation</option>
	    <option value='26000'>26000 - Confirmation (LDS)</option>
	    <option value='17'>17 - Court</option>
	    <option value='18'>18 - Cremation</option>
	    <option value='72'>72 - Death Registration</option>
	    <option value='19'>19 - Degree</option>
	    <option value='22'>22 - Education</option>
	    <option value='68'>68 - Election</option>
	    <option value='23'>23 - Emigration</option>
	    <option value='24'>24 - Employment</option>
	    <option value='16000'>16000 - Endowment (LDS)</option>
	    <option value='66'>66 - Ethnicity</option>
	    <option value='65'>65 - Family Group</option>
	    <option value='26'>26 - First Communion</option>
	    <option value='67'>67 - Funeral</option>
	    <option value='27'>27 - Graduation</option>
	    <option value='28'>28 - Hobbies</option>
	    <option value='29'>29 - Honours</option>
	    <option value='30'>30 - Hospital</option>
	    <option value='31'>31 - Illness</option>
	    <option value='27000'>27000 - Initiatory (LDS)</option>
	    <option value='32'>32 - Immigration</option>
	    <option value='33'>33 - Interview</option>
	    <option value='34'>34 - Land</option>
	    <option value='40'>40 - Medical</option>
	    <option value='59'>59 - Medical Condition</option>
	    <option value='41'>41 - Membership</option>
	    <option value='60'>60 - Military</option>
	    <option value='42'>42 - Military Service</option>
	    <option value='43'>43 - Mission</option>
	    <option value='44'>44 - Namesake</option>
	    <option value='64'>64 - Nationality</option>
	    <option value='45'>45 - Naturalization</option>
	    <option value='46'>46 - Obituary</option>
	    <option value='47'>47 - Occupation</option>
	    <option value='63'>63 - Occupation 1</option>
	    <option value='48'>48 - Ordinance</option>
	    <option value='49'>49 - Ordination</option>
	    <option value='61'>61 - Photo</option>
	    <option value='50'>50 - Physical Description</option>
	    <option value='51'>51 - Probate</option>
	    <option value='52'>52 - Property</option>
	    <option value='53'>53 - Religion</option>
	    <option value='54'>54 - Residence</option>
	    <option value='55'>55 - Retirement</option>
	    <option value='56'>56 - School</option>
	    <option value='57'>57 - Social Security Number</option>
	    <option value='62'>62 - Soc Sec Num</option>
	    <option value='58'>58 - Will</option>
	</select>
  </p>
  <p>
    <label class='labelSmall' for='idsr'>
	IDSR:
    </label>
    <select name='idsr' size='5'>
<?php
    // execute the query
    $query	= "SELECT * FROM tblSR ORDER BY SrcName";
    $stmt	= $connection->query($query);

    if ($stmt)
    {			// successful query
	$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	foreach($result as $row)
	{		// loop through all result rows
	    $value		= $row["idsr"];
	    if ($value == $idsr)
		$selected	= "selected='selected'";
	    else
		$selected	= ""; 
	    $name		= htmlspecialchars($row["srcname"]);
	    print "    <option value='$value' $selected>$value - $name</option>\n";
	}		// loop through all result rows
    }			// successful query
    else
    {		// error issuing query
	print "<p class='message'>'$query': " . print_r($connection->errorInfo(),true) . "</p>\n";
	exit;
    }		// error issuing query
?>
    </select>
</p>
  <p>
    <label class='labelSmall' for='page'>
	Page:
    </label>
	<input type='text' name='page' id='page'
		class='white leftnc' value='xxx'>
</p>
  <p>
    <label class='labelSmall' for='row'>
	Row:
    </label>
	<input type='text' name='row' id='row'
		class='white rightnc' value='<?php print $rowid; ?>'>
  </p>
  <p>
    <label class='labelSmall' for='formname'>
	Form Name:
    </label>
	<input type='text' name='formname' id='formname'
		class='white leftnc' value='evtForm'>
  </p>
  <p>
    <label class='labelSmall' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </p>
  <button type='submit'>Add Citation</button>
</p>
</form>
<?php
    }		// no errors
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpidir'>
<p>Edit the unique numeric key (IDIR) of the individual to update.
</p>
</div>
</body>
</html>
