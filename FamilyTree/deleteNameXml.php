<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteNameXml.php.php												*
 *																		*
 *  Delete an existing name record from tblNX.							*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.												*
 *																		*
 *  History:															*
 *		2011/11/19		created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/21		use LegacyAltName class to access object		*
 *		2014/04/08		class renamed to LegacyName						*
 *		2014/12/25		do not delete if associated record still exists	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/18		class LegacyName renamed to class Name			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/12/12		do not use class RecordSet and					*
 *						Person::getPersons to determine if associated	*
 *						Family and Person still exist					*
 *						class Name no longer throws exception for		*
 *						undefined IDNX value, use method isExisting		*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Name.inc";
require_once __NAMESPACE__ . "/Person.inc";
require_once __NAMESPACE__ . "/Family.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<deleted >\n";

// include info on parameters
$idnx	= null;
print "    <parms>\n";
foreach($_POST as $key => $value)
{
    if (strtolower($key) == 'idnx')
        $idnx	= $value;
    print "\t<$key>$value</$key>\n";
}
print "    </parms>\n";

// determine if permitted to add children
if (!canUser('edit'))
{		// take no action
    $msg	.= 'Not authorized to delete alternate name. ';
}		// take no action

// validate parameters
if (is_null($idnx))
    $msg	.= 'Missing mandatory parameter idnx. ';
else
{			// IDNX specified
    $name	= new Name(array('idnx'	=> $idnx));
    if ($name->isExisting())
    {
        $idir	= intval($name->get('idir'));
        print "<idir>$idir</idir>\n";
        $givenname	= $name->get('givenname');
        print "<givenname>$givenname</givenname>\n";
        $surname	= $name->get('surname');
        print "<surname>$surname</surname>\n";
        $order	= intval($name->get('order'));
        print "<order>$order</order>\n";
        if ($order == 0)
        {			// primary name
    		$idir		= $name->get('idir');
    		$person		= new Person(array('idir'	=> $idir));
    		if ($person->isExisting())
    		{
    		    print "<result count='1'>\n";
    		    $person->toXml();
    		    print "</result>\n";
    		    $msg	.= "Not deleted because associated individual $idir still exists. ";
    		}
        }			// primary name
        else
        if ($order < 0)
        {			// married name
    		$idmr		= $name->get('MarriedNameMarIDID');
    		$family		= new Family(array('idmr'	=> $idmr));
    		if ($family->isExisting())
    		    $msg	.= "Not deleted because associated family $idmr still exists. ";
        }			// married name
        else
    		print "<unexpectedorder>$order</unexpectedorder>\n";
    }
    else
        $msg	.= "Name record with IDNX=$idnx does not exist. ";
}			// IDNX specified

// if permitted, delete the name record
if (strlen($msg) > 0)
{			// invalid parameters
    print "<msg>$msg</msg>\n";
}			// invalid parameters
else
{			// no errors
    if ($name)
        $name->delete("cmd");
}			// no errors

print "</deleted>\n";
