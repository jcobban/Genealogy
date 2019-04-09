<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getSourcesXml.php													*
 *																		*
 *  This script returns an XML file response containing the unique		*
 *  key (IDSR) and all fields of all of the currently defined			*
 *  master sources.														*
 *  This can be invoked using AJAX to populate a select element of a	*
 *  form.																*
 *																		*
 *  Parameters:															*
 *		name	name of the specific <select> element to update to 	    *
 *				support multiple <select> elements in a dialog			*
 *																		*
 *  History:															*
 *		2010/08/20		add name= parameter								*
 *		2010/10/17		allow any input parameters						*
 *						return all fields of each source record			*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/08/28		use Source::getSources and toXml				*
 *						add support for alternate source order			*
 *						add support for limiting sources returned		*
 *						by field values									*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/10/14		use class RecordSet								*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$full	= false;
$order	= 'SrcName';
$parms	= array();

// display the results as an XML document
print("<?xml version='1.0' encoding='UTF-8'?>\n");
// top node of XML result
print("<sources>\n");

// return all of the passed parameters so the requesting page
// can apply the response information to the specific element
foreach($_GET as $fldname => $value)
{			// loop through all parameters
    print "    <$fldname>$value</$fldname>\n";
    switch(strtolower($fldname))
    {		// act on specific parameters
        case 'name':
        {		// name of <select>
    		// value is just echoed
    		break;
        }		// name of <select>

        case 'idsr':
        {		// current selected source
    		// value is just echoed
    		break;
        }		// current selected source

        case 'include':
        {		// option to include all fields in output
    		$full	= strtolower($value) == 'all';
    		break;
        }		// option to include all fields in output

        case 'order':
        {		// specify alternate source order
    		$order	= $value;
    		break;
        }		// specify alternate source order

        case 'debug':
        {		// handled by common code
    		break;
        }		// handled by common code

        default:
        {		// restrictions on which sources to return
    		$parms[$fldname]	= $value;
    		break;
        }		// restrictions on which sources to return
    }
}			// loop through all parameters

// get the list of matching sources
$parms['order']		= $order;
$result	= new RecordSet('Sources',
    					$parms);

foreach($result as $idsr => $source)
{		// loop through all sources
    $name		= htmlspecialchars($source->getName());
    if ($full)
    {			// show the whole record
        $source->toXml("source");
    }			// show the whole record
    else
    {			// just show the name
        print "    <source id='$idsr'>\n";
        print "\t$name\n";
        print "    </source>\n";
    }			// just show the name
}		// loop through all sources

print("</sources>\n");	// close off top node of XML result

