<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  mergeAddressesXml.php						*
 *									*
 *  Merge a set of Addresses into a single Address.  This requires	*
 *  updating all references to the Addresses being merged.		*
 *  This is invoked by Address.js through AJAX				*
 *									*
 *  Parameters (passed by Post):					*
 *	to		IDAR of target instance of Address		*
 *	from		commalist of IDARs of instances of		*
 *			Address to be merged and deleted		*
 *									*
 *  History:								*
 *	2010/12/05	created						*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/08/28	use Address::mergeAddresses			*
 *			improve parameter validation			*
 *	2015/05/27	Address::mergeAddresses is changed to		*
 *			a normal method invoked for the instance of	*
 *			Address identified by the value of to=		*
 *			This automatically validates the value of to=	*
 *			and makes the separate kind= unnecessary since	*
 *			the merged entries must have the matching kind	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2017/08/04	class LegacyAddress renamed to Address		*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<update>";

    if (!canUser('edit'))
    {
	$msg	.= 'Not authorized to merge addresses. ';
    }		// take no action

    // validate parameters
    $to		= null;
    $kind	= null;
    $from	= null;
    $address	= null;		// instance of Address
    foreach($_POST as $fldname => $value)
    {			// check all parameters
	switch(strtolower($fldname))
	{		// act on specific parameter
	    case 'to':
	    {
		$to	= intval($value);
		try {
		    $address	= new Address(array('idar' => $to));
		} catch (Exception $e) {
		    $msg	.=
		"Unable to obtain instance of Address for IDAR=$to. " . 
				   $e->getMessage();
		}
		break;
	    }

	    case 'kind':
	    {
		$kind	= intval($value);
		if ($kind < 0 || $kind > Address::MAXKIND)
		    $msg	.= "Invalid parameter value kind=$value. ";
		break;
	    }

	    case 'from':
	    {
		$from	= explode(',', $value);
		break;
	    }
	}		// act on specific parameter
    }			// check all parameters
    if (is_null($to))
	$msg	.= 'Missing mandatory parameter to. ';
    if (is_null($from))
	$msg	.= 'Missing mandatory parameter from. ';

    if (strlen($msg) == 0)
    {			// proceed with update
	$address->mergeAddresses($from);
    }			// proceed with update
    else
    {			// error in parameters
	print "    <msg>$msg</msg>\n";
    }			// error in parameters

    // close the XML document
    print "</update>\n";
?>
