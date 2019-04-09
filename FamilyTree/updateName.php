<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateName.php														*
 *																		*
 *  Handle a request to update an individual name in 					*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters (passed by POST):										*
 *		idnx	unique numeric identifier of name.						*
 *				If this is zero (0) a new name is created.				*
 *		others	valid field names within the Name record.				*
 *																		*
 *  History:															*
 *		2014/04/09		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<update>\n";

$idnx	= null;
foreach($_POST as $key => $value)
{
	if ($key == 'idnx')
	    $idnx	= intval($value);
}

// get the updated values of the fields in the record
if (is_int($idnx))
{
    // locate existing name record, or create a new empty record
    $name		= new Name(array('idnx' => $idnx));

    // update object from $_POST parameters
    $name->postUpdate(true);

    // save object state to server
    $name->save(true);

    // include XML representation of updated record in response
    $name->toXml('name');
}
else
	print "<msg>Missing mandatory parameter idnx</msg>\n";

// close root node
print "</update>\n";
