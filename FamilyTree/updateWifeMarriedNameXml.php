<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateWifeMarriedNameXml.php										*
 *																		*
 *  Handle a request to update the alternate name index entry			*
 *  for a wife who takes her husband's surname.							*
 *																		*
 *  The following parameters must be passed using the POST method.		*
 *																		*
 *		idmr			unique numeric key of marriage record			*
 *																		*
 *  History:															*
 *		2013/01/17		created											*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/08		LegacyAltName renamed to LegacyName				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/08/19		class LegacyName renamed to Name				*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<marriage>\n";

// get the updated values of the fields in the record

// user must be authorized to edit the database
if (!canUser('edit'))
{  			// the user not authorized
	$msg	.= 'User not authorized to update the database. ';
}  			// the user not authorized

// validate IDMR
$idmr	= null;
if (array_key_exists('idmr', $_POST))
	$idmr	= $_POST['idmr'];
else
if (array_key_exists('IDMR', $_POST))
	$idmr	= $_POST['IDMR'];
else
{
	$msg	.= 'Missing mandatory parameter idmr. ';
}

if (!is_string($idmr) || !ctype_digit($idmr))
	$msg	.= "Value of idmr='$idmr' must be numeric key. ";
else
{			// idmr specified and numeric
	try {
	    $family	= new Family(array('idmr' => $idmr));
	}
	catch(Exception $e)
	{
	    $msg	.= "unable to get record for IDMR=$idmr : " . $e->getMessage();
	}

	$idirwife	= $family->get('idirwife');
	$wife		= new Person(array('idir' => $idirwife));
}			// idmr specified and numeric

// if there were any errors detected, report them and terminate
if (strlen($msg) > 0)
{			// missing or invalid value of idmr parameter
	print "<msg>$msg</msg>\n";
	print "</marriage>\n";
	exit;
}			// missing or invalid value of idmr parameter

// check to see if the wife's name has changed
if (is_object($wife))
{			// wife changed
	try {
	    // update the associated nominal index record
	    $altNameRec		= new Name($family);
	    $altNameRec->save();
        print "    <cmd>" . $altNameRec->getLastSqlCmd() . "</cmd>\n";
	} catch(Exception $e)
	{		// setName failed
	    print "<msg>Wife changed: " . $e->getMessage() . "</msg>\n";
	}		// setName failed
}			// wife changed

// close off root node
print "</marriage>\n";
