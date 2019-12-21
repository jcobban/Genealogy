<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  addChildXml.php														*
 *																		*
 *  Add a child to a family and return XML so that this document can be	*
 *  invoked by JavaScript code using AJAX.								*
 *																		*
 *  Parameters: passed by method=POST									*
 *		idmr		the key of the family to which the child is added	*
 *		idir		the key of the child to add							*
 *																		*
 *  History:															*
 *		2014/02/24		created											*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/11/30		validate parameters								*
 *						enclose trace in <div class='warning'>			*
 *						do not attempt to extract the value of IDCR		*
 *						for the new child, as it may not yet be set		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Child.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print("<child>\n");

// set default values for parameters
$idmr	= null;		// IDMR
$idir	= null;		// IDIR

// validate parameters
print "  <parms>\n";
foreach ($_POST as $key => $value)
{			// look at all parameters
	print "    <$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
	switch(strtolower($key))
	{		// act on specific keys
	    case 'idir':
	    {		// identifier of child to add
			if ((is_int($value) || ctype_digit($value)) &&
			    $value > 0)
			    $idir		= intval($value, 10);
			break;
	    }		// identifier of child to add

	    case 'idmr':
	    {		// identifier of family to add to
			if ((is_int($value) || ctype_digit($value)) &&
			    $value > 0)
			    $idmr		= intval($value, 10);
			break;
	    }		// identifier of family to add to

	}		// act on keys
}			// look at all parameters
print "  </parms>\n";

// check for missing mandatory parameters;
if ($idir === null)
	$msg	.= 'Missing or invalid mandatory parameter idir. ';
if ($idmr === null)
	$msg	.= 'Missing or invalid mandatory parameter idmr. ';
if (!canUser('edit'))
	$msg	.= "User $userid not authorized to update database. ";

// start debugging output
if ($debug)
{
	print "  <div class='warning'>\n";
	print $warn;
}

// determine if permitted to update database
if (strlen($msg) == 0)
{				// parameters syntactically valid
	try {
	    $child	= new Person(array('idir' => $idir));
	    if (!$child->isExisting())
			$msg	.= "No matching Person record for IDIR=$idir. ";
	    else
	    if (!$child->isOwner())
			$msg	.= "User $userid not authorized to modify record IDIR=$idir. ";
	} catch(Exception $e) {
	    $msg	.= "For IDIR: " . $e->getMessage() . "\n";
	}			// validate IDIR
	$family		= new Family(array('idmr' => $idmr));
	if (!$family->isExisting())
	{
	    $msg	.= "No matching Family record for IDMR=$idmr\n";
	}			// validate IDMR
}			// parameters syntactically valid

// close off debugging output
if ($debug)
	print "  </div>\n";

// if any errors encountered in validating parameters
// terminate the request and return the error message
if (strlen($msg) > 0)
{		// return the message text in XML
	print "<msg>$msg</msg>\n";
}		// return the message text in XML
else
{		// no errors found
	$childr		= $family->addChild($idir);
	$childr->toXml('child');
}		// no errors found
print("</child>\n");

