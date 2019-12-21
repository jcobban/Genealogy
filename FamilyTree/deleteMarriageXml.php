<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteMarriageXml.php												*
 *																		*
 *  Delete an existing marriage record from tblMR.						*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.												*
 *																		*
 *  History:															*
 *		2010/08/14		created											*
 *		2010/08/28		log update to SQL log							*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/05		Validate that user is authorized to delete		*
 *						the marriage.									*
 *		2010/12/21		handle exception from new LegacyFamily			*
 *						delete citations associated with marriage record*
 *						escape XML characters, if any, in message text	*
 *		2010/12/23		move delete database record code to LegacyFamily*
 *						class definition								*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/12/30		only include RecOwners.inc once					*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/08/28		rename to deleteMarriageXml.php					*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *		2015/01/07		change require to require_once					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/11/29		delete unreferenced spouses						*
 *		2016/12/20		fix exception if IDIR of other spouse is zero	*
 *		2017/03/19		use preferred parameters for new LegacyFamily	*
 *		2017/09/12		use get( 										*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header('Content-Type: text/xml');
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<deleted>\n";

// include info on parameters
print "    <parms>\n";
$idmr		= null;
$idir		= null;
$child		= null;
foreach($_POST as $key => $value)
{
    print "\t<$key>$value</$key>\n";
    switch(strtolower($key))
    {
        case 'idmr':
        {
    		$idmr		= intval($value);
    		break;
        }

        case 'idir':
        {
    		$idir		= intval($value);
    		break;
        }

        case 'child':
        {
    		$child		= intval($value);
    		break;
        }

    }
}
print "    </parms>\n";

// validate parameters
if (is_null($idmr))
    $msg	.= 'Missing mandatory parameter idmr. ';

// current user must be authorized to update the database
// and must be an owner of the individual records for both
// the husband and the wife to delete the family record
$husband		= null;
$wife		= null;
try
{
    $family	= new Family(array('idmr' => $idmr));

    $idirhusb	= $family->get('idirhusb');
    if ($idirhusb)
        $husband	= $family->getHusband();
    $idirwife	= $family->get('idirwife');
    if ($idirwife)
        $wife	= $family->getWife();
    $isOwner	= (($idirhusb == 0) ||
    			   RecOwner::chkOwner($idirhusb,
    					      'tblIR')) &&
    			  (($idirwife == 0) ||
    			   RecOwner::chkOwner($idirwife,
    					       'tblIR'));

    if (!canUser('edit') || !$isOwner)
    {		// take no action
        $msg	.= 'User not authorized to delete Family. ';
    }		// take no action
}		// try
catch(Exception $e)
{		// catch failure of new Family
    $msg	.= $e->getMessage();
}		// catch failure of new Family

if (strlen($msg) > 0)
{		// problems detected
    print "    <msg>\n\t" . 
          htmlentities($msg,ENT_XML1) . 
          "\n    </msg>\n";
}		// problems detected
else
{		// OK to delete marriage
    $family->delete(true);
    $family	= null;

    // delete husband if unreferenced
    if ($idirhusb != $idir)
    {		// not invoked from husband's page
        print "<husband>\n<idir>$idirhusb</idir>\n";
        if ($husband)
        {		// have a husband
    		$numParents	= count($husband->getParents());
    		$numFamilies	= count($husband->getFamilies());
    		$numEvents	= count($husband->getEvents());
    		print "<parents>" . $numParents . "</parents>\n";
    		print "<families>" . $numFamilies . "</families>\n";
    		print "<events>" . $numEvents . "</events>\n";
    		if ($numParents == 0 &&
    		    $numFamilies == 0 &&
    		    $numEvents == 0)
    		    $husband->delete(true);
        }		// have a husband
        print "</husband>\n";
    }		// not invoked from husband's page

    // delete wife if unreferenced
    if ($idirwife != $idir)
    {		// not invoked from wife's page
        print "<wife>\n<idir>$idirwife</idir>\n";
        if ($wife)
        {
    		$numParents	= count($wife->getParents());
    		$numFamilies	= count($wife->getFamilies());
    		$numEvents	= count($wife->getEvents());
    		print "<parents>" . $numParents . "</parents>\n";
    		print "<families>" . $numFamilies . "</families>\n";
    		print "<events>" . $numEvents . "</events>\n";
    		if ($numParents == 0 &&
    		    $numFamilies == 0 &&
    		    $numEvents == 0)
    		    $wife->delete(true);
        }
        print "</wife>\n";
    }		// not invoked from wife's page

}		// OK to delete marriage
 
print "</deleted>\n";
