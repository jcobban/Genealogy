<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbDistrictsXml.php							*
 *									*
 *  Create an XML file of information about the counties in the		*
 *  Wesleyan Methodist Baptisms database.				*
 *									*
 *  History:								*
 *	2013/06/28	created						*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *	2017/01/23	do not use htmlspecchars to build input values	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/common.inc";

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<select name='District' size='1'>\n";


    // execute the query
    $query	= 'SELECT DISTINCT District FROM MethodistBaptisms ORDER BY District';
    print '    <cmd>' . $query . "</cmd>\n";
    $stmt		= $connection->query($query);
    if ($stmt)
    {		// successful query
	$result		= $stmt->fetchAll(PDO::FETCH_NUM);
	print "    <option value=''>Choose a District:</option>\n";
    
	foreach($result as $row)
	{	// loop through districts
	    $county	= htmlentities($row[0]);
	    print "    <option value='" . str_replace("'","&#39;",$county) .
			    "'>" . htmlspecialchars($county) . "</option>\n";
	}	// loop through districts
    }		// successful query
    else
    {
	print '    <msg>' . print_r($connection->errorInfo(),true) . "</msg>\n";
    }
    print "</select>\n";
?>
