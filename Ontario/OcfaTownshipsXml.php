<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaTownshipsXml.php						*
 *									*
 *  Create an XML file of information about the counties in the OCFA	*
 *  database.								*
 *									*
 *  Parameters:								*
 *	County		name of county for which list of townships to   *
 *			be returned					*
 *									*
 *  History:								*
 *	2011/03/20	created						*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *	2015/10/08	improve parameter processing			*
 *			made much much faster by using pre-built table	*
 *			of townships					*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/common.inc";

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<select>\n";

    $county	= 'Msx';
    foreach($_GET as $key => $value)
    {
	switch(strtolower($key))
	{
	    case 'county':
	    {
		$county	= $value;
		break;
	    }
	}		// act on specific parameters
    }			// loop through parameters
    $encCounty	= htmlentities($county);
    print "    <county>$encCounty</county>\n";
    // execute the query
    $query	= 'SELECT Township FROM OcfaTownships WHERE County=' .
		  $connection->quote($county);
    print '    <cmd>' . htmlentities($query) . "</cmd>\n";
    $stmt	= $connection->query($query);
    if ($stmt)
    {			// successful query
	$result		= $stmt->fetchAll(PDO::FETCH_NUM);
	foreach($result as $row)
	{
	    $township	= htmlentities($row[0]);
	    print "    <option value='$township'>$township</option>\n";
	}
    }			// successful query
    else
    {
	print '    <msg>' . print_r($connection->errorInfo(),true) . "</msg>\n";
    }

    print "</select>\n";
?>
