<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaCountiesXml.php							*
 *									*
 *  Create an XML file of information about the counties in the OCFA	*
 *  database.								*
 *									*
 *  History:								*
 *	2011/03/20	created						*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<select name='County' size='1'>\n";


    // execute the query
    $query	= 'SELECT DISTINCT County FROM Ocfa ORDER BY County';
    print '    <cmd>' . $query . "</cmd>\n";
    $stmt		= $connection->query($query);
    if ($stmt)
    {			// successful query
	$result		= $stmt->fetchAll(PDO::FETCH_NUM);
	print "    <option value=''>Choose a County</option>\n";
	foreach($result as $row)
	{
	    $county	= htmlentities($row[0]);
	    print "    <option value='$county'>$county</option>\n";
	}
    }			// successful query
    else
    {
	print '    <msg>' . print_r($connection->errorInfo(),true) . "</msg>\n";
    }
    print "</select>\n";
?>
