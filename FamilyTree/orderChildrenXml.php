<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  orderChildrenXml.php												*
 *																		*
 *  Reorder the children of a particular family by birth date.			*
 *  This script returns an XML file so it can be invoked using AJAX.	*
 *																		*
 *  Parameters:															*
 *		idmr	the unique numeric key of the Family record to			*
 *				which the children belong.								*
 *																		*
 *  History:															*
 *		2010/08/21		created.										*
 *		2010/08/27		include parms and external birth & death dates	*
 *						in response										*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/12		replace LegacyDate::toString with				*
 *						LegacyDate::toString							*
 *		2011/06/15		include gender in report						*
 *		2012/01/13		change class names								*
 *						change file name to reflect XML return value	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/12/22		invalid operator in SQL expression				*
 *		2015/02/20		use Child::getChildren							*
 *						use Child::setField and save					*
 *						use Child::toXml								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/09/12		use set(										*
 *		2017/11/04		use RecordSet in place of getChildren			*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Child.inc";
require_once __NAMESPACE__ . "/LegacyDate.inc";
require_once __NAMESPACE__ . "/common.inc";

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<ordered>\n";

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
    $msg	.= 'Not authorized. ' . $authorized;
}		// take no action

// validate parameters
if (array_key_exists('idmr', $_POST))
    $idmr	= $_POST['idmr'];
else
    $msg	.= 'Missing mandatory parameter idmr. ';

showTrace();

if (strlen($msg) > 0)
{
    print "    <msg>$msg</msg>\n";
}
else
{			// valid parameters
    // get list of children in order by birth date
    try {
        $children	= new RecordSet('Children' .
    			' INNER JOIN Persons ON Children.IDIR=Persons.IDIR',
    					array('idmr'	=> $idmr,
    					      'order'	=> 'Persons.BirthSD'));
        $info	= $children->getInformation();
        print "<cmd>" . $info['query'] . "</cmd>\n";

        // update the Order field of each of the children
        // so that they match the order of the birth dates
        $order	= 0;
        foreach($children as $idcr => $childr)
        {		// loop in order by birth
    		$idir		= $childr->get('idir');
    		$oldorder	= $childr->set('order', $order);
print "<order oldorder='$oldorder' neworder='$order' idcr='$idcr' idir='$idir'/>\n";
    		$childr->save(true);
    		$childr->toXml("child");
    		$order++;
        }		// loop in order by birth
    } catch(Exception $e) {
        print "<msg>" . $e->getMessage() . "</msg>\n";
    }
}			// valid parameters
print "</ordered>\n";

?>
