<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  TownshipsListXml.php												*
 *																		*
 *  Display form for editting information about townships for			*
 *  vital statistics records											*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Prov		two letter code										*
 *		County		three letter code									*
 *																		*
 *  History:															*
 *		2012/05/07		created											*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/29		use Township class method getTownships			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/12		use get( and set(								*
 *		2017/12/20		use TownshipSet									*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/TownshipSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$prov	        = 'ON';
$county	        = null;
$getParms	    = array();
foreach($_GET as $key => $value)
{				// loop through all parameters
	switch(strtolower($key))
	{			// act on specific keys
	    case 'prov':
	    {
			if (strlen($value) > 0)
			{
			    $prov		= $value;
			    $getParms['prov']	= $prov;
			}
			break;
	    }

	    case 'county':
	    {
			if (strlen($value) > 0)
			{
			    $county		= $value;
			    $getParms['county']	= $county;
			}
			break;
	    }
	}			// act on specific keys
}				// loop through all parameters

if (is_null($county))
	$msg	.= 'Missing mandatory parameter County. ';

if (strlen($msg) == 0)
{			// no errors
	$result		= new TownshipSet($getParms); 
}			// no errors

// top node of XML result
print("<?xml version='1.0' encoding='UTF-8'?>\n");

if (strlen($msg) == 0)
{			// no errors
	print("<select prov='$prov' county='$county'>\n");

	foreach($result as $township)
	{
		$code	= htmlspecialchars($township->get('code'));
		$name	= htmlspecialchars($township->get('name'));
		print "  <option value='$code'>$name</option>\n";
	}
	showTrace();
	print("</select>\n");
}			// no errors
else
{			// errors
	print "<msg>$msg</msg>\n";
}			// errors
