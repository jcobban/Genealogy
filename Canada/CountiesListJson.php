<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountiesListJson.php												*
 *																		*
 *  Get a selected list of counties as an XML document from the			*
 *  database.  This replaces the hard-coded Counties.xml file			*
 *																		*
 *  History:															*
 *		2018/11/19      created                                         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/common.inc';

// the invoker should explicitly provide the province code
$domain	        = null;
$prov	        = '';
foreach ($_GET as $key => $value)
{			// loop through all keys
	switch(strtolower($key))
	{		// act on specific keys
		case 'prov':
		{
    		$domain		= 'CA' . $value;
    		$domainObj	= new Domain(array('domain'	=> $domain,
                						   'language'	=> 'en'));
    		if ($domainObj->isExisting())
    		{	// valid
    		    $prov	= $value;
    		}	// valid
    		else
    		    $msg	.= "Invalid value of Prov code '$value'. ";
    		break;
		}		// province code

		case 'state':
		{
	    	$domain		= 'US' . $value;
	    	$domainObj	= new Domain(array('domain'	=> $domain,
	    			            		   'language'	=> 'en'));
	    	if ($domainObj->isExisting())
	    	{	// valid
	    	    $prov	= $value;
	    	}	// valid
	    	else
	    	    $msg	.= "Invalid value of State code '$value'. ";
	    	break;
		}		// province code

		case 'domain':
		{
		    $domain		= $value;
		    $domainObj	= new Domain(array('domain'	=> $domain,
			                			   'language'	=> 'en'));
		    if ($domainObj->isExisting())
		    {
		        $prov	= substr($value, 2);
		    }
		    else
		        $msg	.= "Invalid value of domain id '$value'. ";
		    break;
		}		// full domain id including country code
	}		// act on specific keys
}			// loop through all keys

if (is_null($domain))
	$msg	.= "Missing domain name parameter. ";

// display the results
// top node of XML result
header("Content-Type: application/json");

// query the database
if (strlen($msg) == 0)
{			            // no errors
	$getParms	    = array('domain'	=> $domain);
	$list		    = new CountySet($getParms);

	print "{\n";
    $comma          = '';
	foreach($list as $code	=> $county)
	{		// loop through all matching counties
        $name	    = $county->get('name');
        if (substr($name, -9) == ' District')
            $name   = substr($name, 0, strlen($name) - 9);
        print "$comma\n\t\"$name\"\t\t: \"$code\"";
        $comma      = ',';
	}		// loop through all result rows

	print("\n}\n");	// close off array
}			            // no errors
else
{			            // return error message
	print "\"$msg\"\n";
}			            // return error message

