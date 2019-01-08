<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testRecordSet.php							*
 *									*
 *  Test class RecordSet						*
 *									*
 *  History:								*
 *	2017/10/14	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';    

    $tableName		= 'Sources';
    $pattern		= '';
    $getParms		= array();
    foreach($_GET as $fldname => $value)
    {
	switch(strtolower($fldname))
	{
	    case 'table':
	    {
		$tableName	= $value;
		$information	= Record::getInformation($tableName);
		if (is_null($information))
		    $msg	.= "Unsupported Table Name '$tableName'. ";
		break;
	    }

	    default:
	    {
		$getParms[$fldname]	= $value;
	    }
	}	// switch
    }		// foreach

    if (strlen($msg) == 0 && $tableName)
	$list		= new RecordSet($tableName, $getParms);
    else
	$list		= null;

    $subject	= rawurlencode("Test class RecordSet");

    htmlHeader('Test class RecordSet');
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
<div class='body'>
    <h1>
	Test class RecordSet
   </h1>
   <p>Query: <?php print htmlspecialchars(urldecode($_SERVER['QUERY_STRING'])); ?></p>
<?php
    if (strlen($msg))
    {
?>
    <p class='message'><?php print $msg; ?></p>
<?php
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
	$count	= $list->delete('p',
				true);
?>
    <p>Deleted <?php print $count; ?> records</p>
<?php
    }			// created RecordSet instance
    showTrace();
?>
    <form action='testRecordSet.php' method='get'>
      <div class='row' id='tableRow'>
	<label class='label' for='table' style='width: 8em;'>
	    Table:
	</label>
	<input name='table' type='text' size='64' class='white leftnc'
		    value='<?php print $tableName; ?>'>
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
</div>
<?php
    pageBot();
?>
</body>
</html>
