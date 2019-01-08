<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  buildCitations.php							*
 *									*
 *  Rebuild citations lost due to bug in Person class.			*
 *									*
 *  History:								*
 *	2017/11/05	created						*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/CensusLine.inc';
    require_once __NAMESPACE__ . '/RecordSet.inc';
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    $startoffset	= 0;
    $limit		= 20;
    foreach($_GET as $key => $value)
    {
	switch(strtolower($key))
	{
	    case 'offset':
	    {
		$startoffset		= intval($value);
		break;
	    }

	    case 'limit':
	    {
		$limit		= intval($value);
		break;
	    }

	}
    }
    $parms		= array('type'		=> 30,
				'offset'	=> 0,
				'limit'		=> $limit,
				'order'		=> 'IDSX');
    $citations		= new RecordSet('Citations', $parms);
    $info		= $citations->getInformation();
    $count		= $info['count'];
    $row		= $info['initrow'];
    $fields		= '';
    $comma		= '';
    foreach($row as $field => $value)
    {
	$fields		.= $comma . "`$field`";
	$comma		= ',';
    }

    $sqlfile	= fopen('/home/jcobban/public_html/logs/loadCitations.sql', 'w');
    if ($sqlfile == false)
    {
	print_r(error_get_last());
	exit;
    }

    for($offset = $startoffset; $offset < $count; $offset += $limit)
    {
print "<p>" . __LINE__ . " offset=$offset, count=$count</p>\n";
flush();
	$parms		= array('type'		=> 30,
				'offset'	=> $offset,
				'limit'		=> $limit,
				'order'		=> 'IDSX');
	$citations	= new RecordSet('Citations', $parms);
	$cmd		= "INSERT INTO tblSX ($fields) VALUES ";
	$valcomma	= '';

	foreach($citations as $idsx => $citation)
	{
	    $cmd	.= $valcomma . '(';
	    $comma	= '';
	    foreach($citation as $field => $value)
	    {
		if (is_numeric($value))
		    $cmd	.= $comma . $value;
		else
		{
		    $value	= str_replace("'","\\'",$value);
		    $cmd	.= $comma . "'$value'";
		}
		$comma	= ',';
	    }
	    $cmd	.= ')';
	    $valcomma	= ',';
	}
	$cmd	.= ";\n";
	fwrite($sqlfile, $cmd);
	if ($offset > $startoffset + 10000)
	    break;
    }

    htmlHeader("Rebuild Citations",
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
	Rebuild Citations
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
	showTrace();
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
