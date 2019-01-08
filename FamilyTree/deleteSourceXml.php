<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteSourceXml.php							*
 *									*
 *  Delete an existing source record from tblSR.			*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.						*
 *									*
 *  History:								*
 *	2013/03/28	created						*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/08/28	use class Source::delete to delete record	*
 *	2014/12/25	do not delete if there are citations to this	*
 *			source						*
 *	2015/01/07	change require to require_once			*
 *	2015/07/02	access PHP includes using include_path		*
 *	2017/07/30	class LegacySource renamed to class Source	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/Source.inc";
require_once __NAMESPACE__ . '/common.inc';

    $idsr	= null;

    // emit the XML header
    header("Content-Type: text/xml");
    print "<?xml version='1.0' encoding='UTF-8'?>\n";
    print "<deleted";
    foreach($_POST as $key => $value)
    {			// include parameters as attributes of top node
	print " $key='$value'";
    }			// include parameters as attributes of top node
    print ">\n";

    // include info on parameters
    print "    <parms>\n";
    foreach($_POST as $key => $value)
    {			// include parameters as tags under <parms>
	if (strtolower($key) == 'idsr')
	    $idsr	= $value;
	print "\t<$key>$value</$key>\n";
    }			// include parameters as tags under <parms>
    print "    </parms>\n";

    // output trace if debugging
    showTrace();

    // determine if permitted to update database
    if (!canUser('edit'))
    {		// take no action
	$msg	.= 'Not authorized to delete source. ';
    }		// take no action

    // validate parameters
    if (is_null($idsr))
	$msg	.= 'Missing mandatory parameter idsr. ';
    else
    {
	try {
	    $source	= new Source(array('idsr' => $idsr));
	    $count	= $source->getCitations(0);
	    if ($count > 0)
		$msg	= "Source not deleted because $count citations refer to it. ";
	} catch(Exception $e)
	{
	    $msg	= $e->getMessage();
	}
    }

    if (strlen($msg) == 0)
    {			// no errors detected in parameters

	try {
	    $source	= new Source(array('idsr' => $idsr));
	    $count	= $source->delete("delete");
	} catch(Exception $e)
	{
	    print "<msg>" . $e->getMessage() . "</msg>\n";
	}
    }			// no errors detected in parameters
    else
    {			// errors detected in parameters
	print "<msg>$msg</msg>\n";
    }			// errors detected in parameters
     
    print "</deleted>\n";

?>
