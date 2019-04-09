<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateCitation.php													*
 *																		*
 *  Handle a request to update an individual source citation in 		*
 *  the Legacy family tree database.  Generate an XML response file		*
 *  so this script can be invoked using AJAX.							*
 *																		*
 *  Parameters (passed by POST):										*
 *		idsx	unique numeric identifier of source citation			*
 *				If this is zero (0) a new source is created.			*
 *		others	parameters matching field names of Citation record		*
 *																		*
 *  History:															*
 *		2010/08/22		created											*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<update>\n";

// get the updated values of the fields in the record
$idsx	= $_POST['idsx'];

// locate existing citation record, or create a new empty record
$citation	= new Citation(array('idsx' => $idsx));

// update object from $_POST parametrers
$citation->postUpdate(true);

// save object state to server
$citation->save(true);

// include XML representation of updated record in response
$citation->toXml('citation');

// close root node
print "</update>\n";
