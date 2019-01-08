<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateEvent.php							*
 *									*
 *  Test the RecOwner class.						*
 *	RO_index							*
 *	RO_username							*
 *	RO_table							*
 *	RO_keyname							*
 *	RO_keyvalue							*
 *									*
 *  History:								*
 *	2014/09/27	created						*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/RecOwner.inc';
    require_once __NAMESPACE__ . '/common.inc';

    $roindex	= '';
    $rousername	= '';
    $rotable	= '';
    $rokeyname	= '';
    $rokeyvalue	= '';

    $getParms	= array();
    foreach($_POST as $fldname => $value)
    {	// loop through all parameters
	switch(strtolower($fldname))
	{// act on specific keys
	    case 'ro_index':
	    {
		$roindex	= $value;
		if (strlen($value) > 0)
		    $getParms['ro_index']	= $value;
		break;
	    }

	    case 'ro_username':
	    {
		$rousername	= $value;
		if (strlen($value) > 0)
		    $getParms['ro_username']	= $value;
		break;
	    }

	    case 'ro_table':
	    {
		$rotable	= $value;
		if (strlen($value) > 0)
		    $getParms['ro_table']	= $value;
		break;
	    }

	    case 'ro_keyname':
	    {
		$rokeyname	= $value;
		if (strlen($value) > 0)
		    $getParms['ro_keyname']	= $value;
		break;
	    }

	    case 'ro_keyvalue':
	    {
		$rokeyvalue	= $value;
		if (strlen($value) > 0)
		    $getParms['ro_keyvalue']	= $value;
		break;
	    }

	}// act on specific keys
    }	// loop through all parameters

    $record	= null;
    if (count($getParms) > 0)
    {	// parameters specified
	try {
	    $record	= new RecOwner($getParms);
	} catch (Exception $e) {
	    $msg	.= $e->getMessage();
	}// catch
    }	// parameters specified

    htmlHeader('Test RecOwner Class',
	       array('/jscripts/js20/http.js',
		     '/jscripts/CommonForm.js',
		     '/jscripts/util.js',
		     'testRecOwner.js'));
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/genCountry.php?cc=CA'		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		'/FamilyTree/Services.php'		=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test RecOwner Class
    </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
    <p class='message'><?php print $msg; ?></p>
<?php
	$msg	= '';
    }

    $olddebug		= $debug;
    $debug		= true;
    if (!is_null($record))
    {
	$record->dump('Show Contents');
    }
    $debug		= $olddebug;

    if (strlen($rokeyvalue) > 0 && strlen($rotable) > 0)
    {
	$result	= RecOwner::chkOwner($rokeyvalue,
				     $rotable);
?>
<p>RecOwner::chkOwner(<?php print $rokeyvalue;?>,'<?php print $rotable;?>')
	returns <?php if ($result) print "true"; else print "false";?>
	for current user <?php print $userid; ?>
</p>
<?php
    }

    if (strlen($rokeyvalue) > 0 && 
	strlen($rotable) > 0 &&
	strlen($rousername) > 0)
    {
	$result	= RecOwner::addOwner($rokeyvalue,
				     $rotable,
				     $rousername);
?>
<p>RecOwner::addOwner(<?php print $rokeyvalue;?>,'<?php print $rotable;?>','<?php print $rousername;?>')
	returns <?php if ($result) print "true"; else print "false";?>
</p>
<?php
    }
?>
<form name='evtForm' id='evtForm' action='testRecOwners.php' method='post'>
<form name='evtForm' id='evtForm' action='testRecOwners.php' method='post'>
  <p>
    <label class='column1'>Index:</label>
	<input type='text' name='RO_Index' id='RO_Index' class='white right'
		value='<?php print $roindex; ?>'>
  <p>
    <label class='column1'>User Name:</label>
	<input type='text' name='RO_UserName' id='RO_UserName' class='white leftnc'
		value='<?php print $rousername; ?>'>
  <p>
    <label class='column1'>Table:</label>
	<input type='text' name='RO_Table' id='RO_Table' class='white leftnc'
		value='<?php print $rotable; ?>'>
  <p>
    <label class='column1'>Key Name:</label>
	<input type='text' name='RO_KeyName' id='RO_KeyName' class='white leftnc'
		value='<?php print $rokeyname; ?>'>
  <p>
    <label class='column1'>Key Value:</label>
	<input type='text' name='RO_KeyValue' id='RO_KeyValue' class='white leftnc'
		value='<?php print $rokeyvalue; ?>'>
  <p>
    <button type='submit' id='Submit'>Test</button>
  </p>
</form>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpRO_Index'>
Edit the surname of the individual.  Note that changing the surname causes
</div>
<div class='balloon' id='HelpRO_UserName'>
Edit the surname of the individual.  Note that changing the surname causes
</div>
<div class='balloon' id='HelpRO_Table'>
Edit the surname of the individual.  Note that changing the surname causes
</div>
<div class='balloon' id='HelpRO_KeyName'>
Edit the surname of the individual.  Note that changing the surname causes
</div>
<div class='balloon' id='HelpRO_KeyValue'>
Edit the surname of the individual.  Note that changing the surname causes
</div>
</body>
</html>
