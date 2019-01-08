<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testInSubquery.php
 *
 *  test the InSubquery.php script
 *
 *  History:
 *	2012/12/28	created
 *
 *  Copyright 2011 James A. Cobban
 **/
    require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug	= false;

?>
<!DOCTYPE html>
<html>
<head>
    <title>
    Test IN Subquery
    </title>
    <meta HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
    <meta http-equiv='default-style' content='text/css'>
    <meta name='author' content='James A. Cobban'>
    <meta name='copyright' content='&copy; 2012 James A. Cobban'>
    <link rel='stylesheet' type='text/css' href='/styles.css'/>
</head>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/genCountry.php?cc=CA'		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>
	Test IN SubQuery
    </h1>
<?php
    $query	= "SELECT SL_ID FROM SqlLog WHERE Left(SL_Command,17)='INSERT INTO tblIR' and datediff(current_date(), sl_datetime) < 7";
?>
<p>Issue "<?php print $query; ?>"

<?php
    $tresult= $connection->query($query);
    if (PEAR::isError($tresult))
    {// error establishing result
        die($tresult->getMessage());
    }// error establishing result

?>
    <table>
<?php
    $list	= '';
    while($row = $tresult->fetchRow(MDB2_FETCHMODE_ASSOC))
    {// loop through all rows
?>
	<tr>
<?php
	foreach($row as $key => $value)
	{
	    $list	.= $value . ',';
?>
	<th><?php print $key; ?></th><td><?php print $value; ?></td>
<?php
	}// foreach
?>
	<tr>
<?php
    }// loop through all rows
    / remove the last comma
    $list	= substr($list, strlen($list)-1);
?>
    </table>
<?php
    $query	= "SELECT idir, surname, givenname FROM tblIR WHERE id IN ($list)";
?>
<p>Issue "<?php print htmlspecialchars($query); ?>"
<?php
    $tresult= $connection->query($query);
    if (PEAR::isError($tresult))
    {// error establishing result
        die($tresult->getMessage());
    }// error establishing result

?>
    <table>
<?php
    while($row = $tresult->fetchRow(MDB2_FETCHMODE_ASSOC))
    {// loop through all rows
?>
	<tr>
<?php
	foreach($row as $key => $value)
	{
?>
	<th><?php print $key; ?></th><td><?php print $value; ?></td>
<?php
	}// foreach
?>
	<tr>
<?php
    }// loop through all rows
?>
    </table>
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
