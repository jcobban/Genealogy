<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  orderMarriagesByDate.php						*
 *									*
 *  Handle a request to reorder the marriage records for an		*
 *  individual in the Legacy family tree database.  The			*
 *  `Husb/WifeOrder` field in each record is updated so the marriages	*
 *  will display in chronological order by the `MarSD` field.  This	*
 *  file generates an XML file, so it can be invoked from Javascript.	*
 *									*
 *  Parameters:								*
 *	idir	unique numeric key of the individual			*
 *									*
 *  History:								*
 *	2010/08/10	created						*
 *	2010/09/25	Check error on $result, not $connection after	*
 *			query/exec					*
 *	2010/10/23	move connection establishment to common.inc	*
 *	2012/01/13	change class names				*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/04/26	formUtil.inc obsoleted				*
 *	2014/12/12	print $warn, which may contain debug trace	*
 *			rename to orderMarriagesByDateXml.php		*
 *			ensure IDIR parameter is numeric and greater	*
 *			than 0						*
 *	2015/02/21	use LegacyFamily::getFamilies			*
 *			use LegacyFamily::setField and ::save		*
 *	2015/03/20	wrong order field used for wife order		*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/08/08	handle case of no spouse			*
 *	2016/01/19	add id to debug trace				*
 *	2017/09/12	use get( and set(				*
 *	2017/11/02	use RecordSet for families			*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<ordered>\n";

    // until I find out why this include generates a new line
    // character, I have to include it after the XML header fields
    // are emitted.
require_once __NAMESPACE__ . "/Family.inc";
require_once __NAMESPACE__ . '/common.inc';

    // get the updated values of the fields in the record
    // list parameters passed to this script
    print "    <parms>\n";
    foreach($_POST as $key => $value)
    {	
	print "\t<$key>" . xmlentities($value) . "</$key>\n";
    }
    print "    </parms>\n";

    // determine if permitted to update database
    if (($authorized != 'yes') &&
	(strpos($authorized, 'edit') === false))
    {		// take no action
	$msg	.= 'Not authorized. ';
    }		// take no action

    // validate parameters
    if (array_key_exists('idir', $_POST))
    {		// idir to be updated
	$idir		= $_POST['idir'];
	if (!ctype_digit($idir) || $idir < 1)
	    $msg	.= "Invalid value IDIR=$idir. ";
    }		// idir to be updated
    else
    {
	$idir		= null;
	$msg		.= 'Mandatory parameter "idir" omitted. ';
    }

    if (array_key_exists('sex', $_POST))
    {		// sex to be updated
	$sex		= $_POST['sex'];
    }		// sex to be updated
    else
    {
	$sex		= null;
	$msg		.= 'Mandatory parameter "sex" omitted. ';
    }

    showTrace();

    if (strlen($msg) == 0)
    {		// no errors detected
	// get the current set of event records for the requested
	// individual.
	if ($sex == 0)
	{
	    $parms	= array("IDIRHusb"	=> $idir,
				'order'		=> 'MarSD');
	    $orderFld	= 'husborder';
	}
	else
	{
	    $parms	= array("IDIRWife"	=> $idir,
				'order'		=> 'MarSD');
	    $orderFld	= 'wifeorder';
	}
	$families	= new RecordSet('Families',$parms);
	$order		= 0;
	foreach($families as $idmr => $family)
	{
	    $marsd		= $family->get('MarSD');
	    if ($sex == 0)
		$spouse		= $family->getWife();
	    else
		$spouse		= $family->getHusband();
	    if ($spouse)
	    {
		$spouseSur	= $spouse->getSurname();
		$spouseGiv	= $spouse->getGivenName();
	    }
	    else
	    {
		$spouseSur	= '';
		$spouseGiv	= '';
	    }
	    $family->set($orderFld, $order);
	    $family->save(true);

	    // include results of update in XML response
	    print "    <new>\n";
	    print "\t<idmr>" . $idmr . "</idmr>\n";
	    print "\t<spouseGiv>" . $spouseGiv . "</spouseGiv>\n";
	    print "\t<spouseSur>" . $spouseSur . "</spouseSur>\n";
	    print "\t<order>" . $order . "</order>\n";
	    print "\t<marsd>" . $marsd . "</marsd>\n";
	    print "    </new>\n";
	    $order++;
	}		// loop through all matching families

    }		// no errors detected
    else
    {		// errors in parameters
	print "    <msg>\n";
	print xmlentities($msg);
	print "    </msg>\n";
    }		// errors in parameters

    // close root node of XML output
    print "</ordered>\n";
