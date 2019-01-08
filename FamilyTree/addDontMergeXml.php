<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  addDontMergeXml.php							*
 *									*
 *  Add a Dont Merge table entry and return XML so that this document	*
 *  can be invoked by JavaScript code using AJAX.  A number of		*
 *  parameters can be appended to the URL to customize the citation:	*
 *									*
 *  The following parameters must be passed to identify			*
 *  the pair of individuals to be added to the Dont Merge table (tblDN)	*
 *	idirleft	the IDIR value of an Person Record		*
 *	idirright	the IDMR value of a Family record		*
 *									*
 *  History:								*
 *	2013/01/29	created						*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/09/27	RecOwners class renamed to RecOwner		*
 *			use Record method isOwner to check ownership	*
 *	2014/12/22	include command issued to update database	*
 *			include record contents				*
 *	2015/07/02	access PHP includes using include_path		*
 *	2017/03/19	use preferred parameters for new LegacyIndiv	*
 *	2017/08/17	class LegacyDontMergeEntry renamed to		*
 *			class DontMergeEntry				*
 *	2017/10/08	improve parameter validation			*
 *	2017/10/13	class LegacyIndiv renamed to class Person	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/DontMergeEntry.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");

    // set default values for parameters
    $idirleft	= null;		// first IDIR
    $indiv1	= null;		// first individual
    $idirright	= null;		// second IDIR
    $indiv2	= null;		// second individual

    // validate parameters
    $parms	= "  <parms>\n";
    foreach ($_POST as $key => $value)
    {		// look at all parameters
	$value		= trim($value);
	$parms		.= "    <$key>" . xmlentities($value) . "</$key>\n";
	switch(strtolower($key))
	{	// act on keys
	    case 'idirleft':
	    {	// 
		if (is_int($value) || ctype_digit($value))
		{
		    $idirleft	= (int)$value;
		    $indiv1	= new Person(array('idir' => $idirleft));
		    if ($indiv1->isExisting())
		    {
			if (!$indiv1->isOwner())
			    $msg	.= "User $userid not authorized to modify record IDIR=$idirleft. ";
		    }
		    else
			$msg	.= "idirleft=$idirleft does not exist. ";
		}
		else
		    $msg	.= "idirleft='$idirleft' invalid. ";
		break;
	    }	//

	    case 'idirright':
	    {	// 
		if (is_int($value) || ctype_digit($value))
		{
		    $idirright	= (int)$value;
		    $indiv2	= new Person(array('idir' => $idirright));
		    if ($indiv1->isExisting())
		    {
			if (!$indiv2->isOwner())
			    $msg	.= "User $userid not authorized to modify record IDIR=$idirright. ";
		    }
		    else
			$msg	.= "idirright=$idirright does not exist. ";
		}
		else
		    $msg	.= "idirright='$idirright' invalid. ";
		break;
	    }	//

	    // any other keywords
	    default:
	    {	// quality
		$msg	.= "Unsupported parameter $key. ";
		break;
	    }	// quality
	}	// act on keys
    }		// look at all parameters
    $parms	.= "  </parms>\n";


    // check for missing mandatory parameters;
    if ($idirleft === null)
	$msg	.= 'Missing mandatory parameter idirleft. ';
    if ($idirright === null)
	$msg	.= 'Missing mandatory parameter idirright. ';

    // determine if permitted to update database
    if (!canUser('edit'))
	$msg	.= "User $userid not authorized to add do not merge entry. ".
			"authorized='$authorized' ";

    // if any errors encountered in validating parameters
    // terminate the request and return the error message
    if (strlen($msg) > 0)
    {		// return the message text in XML
	print "<msg>$msg\n$parms</msg>\n";
    }		// return the message text in XML
    else
    {		// no errors found
	// add the dont merge entry to the table
	try {
	    print "<added>\n";
	    $dontmerge	= new DontMergeEntry($idirleft,
					     $idirright);
	    $result	= $dontmerge->save("cmd");	// update database
    
	    if (is_int($result))
	    {
		print "<count>$result</count>\n";
		print $parms;
		$dontmerge->toXml("dontmerge");
		print "</added>\n";
	    }  
	    else
		print "<msg>Unable to add don't merge entry.\n$parms</msg>\n";
	} catch(Exception $e) {
	    print "<msg>\n";
	    print $e->getMessage();
	    print "$parms</msg>\n";
	}	// creation failed
    }		// no errors found
?>
