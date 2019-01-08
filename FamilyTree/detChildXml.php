<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  detChildXml.php							*
 *									*
 *  Detach a specific individual as a child on a specific family.	*
 *									*
 *  Parameters:								*
 *	idcr		unique key of child record in tblCR		*
 *	idir		if specified validates that deleted child	*
 *			record matches this IDIR			*
 *	idmr		if specified validates that deleted child	*
 *			record matches this IDMR			*
 *									*
 *  History:								*
 *	2010/08/27	created						*
 *	2010/10/23	move connection establishment to common.inc	*
 *	2010/11/11	do not invoke RecOwners::chkOwner without $idir	*
 *	2010/12/21	handle exception from new LegacyFamily		*
 *	2012/05/29	change parameter to IDCR			*
 *	2013/03/02	use LegacyChild delete method rather than SQL	*
 *			to delete record				*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/09/27	RecOwners class renamed to RecOwner		*
 *			use Record method isOwner to check ownership	*
 *	2014/12/22	additional parameter validation			*
 *			dump child record before deleting		*
 *			include command used to delete record		*
 *			print warning messages if present		*
 *	2015/07/02	access PHP includes using include_path		*
 *	2017/08/08	class LegacyChild renamed to class Child	*
 *	2017/10/13	class LegacyIndiv renamed to class Person	*
 *									*
 *  Copyright 2017 &copy; James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/Person.inc";
require_once __NAMESPACE__ . "/Child.inc";
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<detached>";

    $child	= null;
    $idcr	= null;
    $idir	= null;
    $idmr	= null;

    // examine parameters
	print "    <parms>\n";
	foreach($_POST as $name => $value)
	{
	    switch(strtolower($name))
	    {
		case 'idcr':
		{
		    $idcr	= $value;
		    if (!ctype_digit($idcr) || $idcr < 1)
			$msg	.= "Invalid value of IDCR=$idcr. ";
		    break;
		}
	
		case 'idir':
		{
		    if (strlen($value) > 0)
		    {
			$idir	= $value;
			if (!ctype_digit($idir) || $idir < 1)
			    $msg	.= "Invalid value of IDIR=$idir. ";
		    }
		    break;
		}
	
		case 'idmr':
		{
		    if (strlen($value) > 0)
		    {
			$idmr	= $value;
			if (!ctype_digit($idmr) || $idmr < 1)
			    $msg	.= "Invalid value of IDMR=$idmr. ";
		    }
		    break;
		}
	
	    }
	    print "\t<$name>$value</$name>\n";
	}
	print "    </parms>\n";

    // validate parameters
    if ($idcr == null)
	$msg	.= 'Missing mandatory parameter idcr. ';

    if (!canUser('edit'))
    {		// not authorized
	$msg	.= 'User not authorized to update database. ';
    }		// not authorized

    if (strlen($msg) == 0)
    {		// no errors so far
	try {
	    $child	= new Child(array('idcr' => $idcr));
	    if (!is_null($idir) && $idir != $child->getIdir())
		$msg	.= "IDIR " . $child->getIdir() . " in Child record does not match explicit IDIR=$idir. ";
	    if (!is_null($idmr) && $idmr != $child->getIdmr())
		$msg	.= "IDMR " . $child->getIdmr() . " in Child record does not match explicit IDMR=$idmr. ";
	    $idir	= $child->getIdir();
	    $person	= new Person(array('idir' => $idir));

	    // determine if permitted to detach child
	    if (!$person->isOwner())
		$msg	.= 'User is not an owner of individual ' . $idir . '. ';
	}
	catch(Exception $e)
	{
	    $msg	.= $e->getMessage();
	}
    }		// no errors so far

    if (strlen($msg) > 0)
    {
	print "    <msg>$msg</msg>\n";
    }
    else
    {		// proceed
	$child->toXml("child");
	$child->delete("cmd");
    }		// proceed
    print "</detached>\n";
?>
