<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountiesListXml.php													*
 *																		*
 *  Get a selected list of counties as an XML document from the			*
 *  database.  This replaces the hard-coded Counties.xml file			*
 *																		*
 *  History:															*
 *		2012/05/07		create											*
 *		2013/11/27		handle database server connection failure		*
 *						use class County to access counties table		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/10/01		method County::getCountiesByDomain renamed		*
 *						to getCounties with associative array parm		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/16		support all domains defined in common.inc		*
 *						escape county codes containing "'" or '"'		*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/09/12		use get( and set(								*
 *		2017/10/21		use class CountySet								*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
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
header("Content-Type: text/xml");
print("<?xml version='1.0' encoding='UTF-8'?>\n");

// query the database
if (strlen($msg) == 0)
{			// no errors
	$getParms	    = array('domain'	=> $domain);
	$list		    = new CountySet($getParms);

	print "<select domain='$domain' prov='$prov'>\n";

	foreach($list as $code	=> $county)
	{		// loop through all matching counties
		$ecode	    = str_replace("'","&#39;",str_replace('"',"&quot;",$code)); 
		$name	    = $county->get('name');
		$ename	    = str_replace(">","&gt;",str_replace("<","&lt;",str_replace("&","&amp;",$name))); 
		print "<option value='$ecode'>$ename</option>\n";
	}		// loop through all result rows

	print("</select>\n");	// close off top node of XML result
}			// no errors
else
{			// return error message
	$emsg	= str_replace(">","&gt;",str_replace("<","&lt;",str_replace("&","&amp;",$msg))); 
	print "<msg>" . htmlspecialchars($emsg) . "</msg>\n";
}			// return error message

