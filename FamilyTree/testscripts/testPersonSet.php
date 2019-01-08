<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testPersonSet.php							*
 *									*
 *  Test class PersonSet						*
 *									*
 *  History:								*
 *	2017/10/14	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/common.inc';    

    $pattern		= '';
    $getParms		= array();
    foreach($_GET as $fldname => $value)
    {
	$getParms[$fldname]	= $value;
    }		// foreach

    if (strlen($msg) == 0)
	$list		= new PersonSet($getParms);
    else
	$list		= null;

    $subject	= rawurlencode("Test class PersonSet");

    htmlHeader('Test class PersonSet');
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
<div class='body'>
    <h1>
	Test class PersonSet
   </h1>
   <p>Query String: <?php print htmlspecialchars(urldecode($_SERVER['QUERY_STRING'])); ?></p>
<?php
    if (strlen($msg))
    {
?>
    <p class='message'><?php print $msg; ?></p>
<?php
	$msg	= '';
    }
    showTrace();
    if ($list)
    {
	$information	= $list->getInformation();
	$className	= $information['classname'];
?>
    <h2>Information:</h2>
    <table>
<?php
	foreach($information as $field => $value)
	{
	    if (is_array($value))
	    {
		$temp		= 'array(';
		$comma		= '';
		foreach($value as $key => $val)
		{
		    if (is_numeric($val) || ctype_digit($val))
			$temp	.= $comma . "'$key' => $val";
		    else
			$temp	.= $comma . "'$key' => '$val'";
		    $comma	= ',';
		}
		$value		= $temp . ')';
	    }
	    if (is_bool($value))
		if ($value)
		    $value	= 'true';
		else
		    $value	= 'false';
?>
	<tr>
	  <th class='label'><?php print $field; ?></th>
	  <td class='odd left'><?php print $value; ?></td>
	</tr>
<?php
	}
?>
    </table>
    <p>Size of list <?php print $list->count(); ?></p>
    <table>
<?php
	$first		= true;
	$tdclass	= 'odd';
	foreach($list as $key => $record)
	{
	    if ($first)
	    {
?>
      <thead>
	<tr>
	  <th class='colhead'>key</th>
<?php
		foreach($record as $fldname => $value)
		{	// build column headers
?>
	  <th class='colhead'><?php print $fldname; ?></th>
<?php
		}	// build column headers
?>
	</tr>
      </thead>
      <tbody>
<?php
		$first	= false;
	    }
?>
	<tr>
	  <th class='right'><?php print $key; ?></th>
<?php
	    foreach($record as $fldname => $value)
	    {		// display field contents
?>
	  <td class='<?php print $tdclass;?> left'><?php print $value; ?></td>
<?php
	    }		// display field contents
?>
	</tr>
<?php
	    if ($tdclass == 'odd')
		$tdclass	= 'even';
	    else
		$tdclass	= 'odd';
	}		// loop through records
?>
      </tbody>
    </table>
<?php
	$count	= $list->update(array('IDSR' => '+=1'),
				'p',
				true,
				'IDSR DESC');
?>
    <p>Updated <?php print $count; ?> records</p>
<?php
    }			// created PersonSet instance
    showTrace();
?>
    <form action='testPersonSet.php' method='get'>
      <div class='row' id='tableRow'>
	<label class='label' for='table' style='width: 8em;'>
	    Table:
	</label>
	<input name='table' type='text' size='64' class='white leftnc'
		    value='Persons'>
	<div style='clear: both;'></div>
      </div>
      <div class='row' id='patternRow'>
	<label class='label' for='pattern' style='width: 8em;'>
	    Pattern:
	</label>
	<input name='pattern' type='text' size='64' class='white leftnc'
		    value='<?php print $pattern; ?>'>
	<div style='clear: both;'></div>
      </div>
      <div class='row' id='buttonRow'>
	<button type='submit' id='Search'>
	    Search
	</button>
      </div>
    </form>
<?php
	$idlr		= 17;
	$indParms	= array(array('idlrbirth' => $idlr,
				      'idlrchris' => $idlr,
				      'idlrdeath' => $idlr,
				      'idlrburied' => $idlr),
				'order'		=> 'Surname, GivenName, BirthSD, DeathSD');

	$persons	= new PersonSet($indParms,
					'Surname, GivenName, BirthSD, DeathSD');
	$information	= $persons->getInformation();
	$count		= $information['count'];
	showTrace();
	if (strlen($msg) > 0)
	{
?>
    <p class='message'><?php print $msg; ?></p>
<?php
	    $msg	= '';
	}
?>
    <h2>Information:</h2>
    <table>
<?php
	foreach($information as $field => $value)
	{
	    if (is_array($value))
	    {
		$temp		= 'array(';
		$comma		= '';
		foreach($value as $key => $val)
		{
		    if (is_numeric($val) || ctype_digit($val))
			$temp	.= $comma . "'$key' => $val";
		    else
			$temp	.= $comma . "'$key' => '$val'";
		    $comma	= ',';
		}
		$value		= $temp . ')';
	    }
	    if (is_bool($value))
		if ($value)
		    $value	= 'true';
		else
		    $value	= 'false';
?>
	<tr>
	  <th class='label'><?php print $field; ?></th>
	  <td class='odd left'><?php print $value; ?></td>
	</tr>
<?php
	}
?>
    </table>
    <p>Size of list <?php print $list->count(); ?></p>
</div>
<?php
    pageBot();
?>
</body>
</html>
