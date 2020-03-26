<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getTempleJSON.php													*
 *																		*
 *  Get the information on a LDS temple or temples as an JSON response	*
 *  file so it can be retrieved by Javascript using AJAX.				*
 *  In particular invoked by /jscripts/locationCommon.js				*
 *																		*
 *  If the name of the temple is specified and that string matches	    *
 *  exactly either the temple or the short name of a temple or		    *
 *  temples, then that temple or those temples are returned. 		    *
 *  However if the name does not match any temple then the name is	    *
 *  used as a pattern match, and the temple or temples whose		    *
 *  temple names start with the supplied name are returned.			    *
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		name				beginning of temple name to search for	    *
 *		limit               maximum number of records to return         *
 *		                    default 40                                  *
 *																		*
 *  History:															*
 *		2020/03/04		created	from getLocationJSON.php				*
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// display the results
header("Content-Type: application/json");

$limit		    				= 40;		// default limit
$getParms						= array();
$name		    				= null;

print "{\n    \"parms\" : {\n";             // start object
$comma                          = '';

foreach($_GET as $fldname => $value)
{			                // loop through all parameters
    $escvalue                   = json_encode($value);
    print "$comma        \"$fldname\" : $escvalue";
    $comma                      = ',';
    switch(strtolower($fldname))
    {		                // act on specific fieldnames
        case 'limit':
        {		            // override limit
    		$limit			    = $value;
    		break;
        }		            // override liit

        case 'name':
        case 'temple':
        {		            // search 'name' parameter
            $value          	= trim($value);
            $name		    	= $value;
            // if name is enclosed in square brackets do not include them
            if (substr($value, 0, 1) == '[')
                $value      	= substr($value, 1);
            if (substr($value, -1, 1) == ']')
                $value      	= substr($value, 0, strlen($value) - 1);
            // escape regexp special characters
            $search         	= str_replace('?', '\\?', $value);
            $search         	= str_replace('+', '\\.', $search);
            $search         	= str_replace('.', '\\.', $search);
            $search         	= str_replace('[', '\\[', $search);

		    $getParms[]			= array('temple'  => "^$search");
    		break;
        }		            // search 'name' parameter

        case 'address':
        case 'used':
        case 'tag1':
        case 'qstag':
        case 'templestart':
        case 'templeend':
        {		            // other field names
    		$getParms[$fldname]	= $value;
    		break;
        }		            // other field names
    }		                // act on specific fieldnames
}			                // loop through all parameters
print "\n    },\n";         // end "parms" object

$getParms['limit']		        = $limit;
$temples		                = new RecordSet('Temples', $getParms);

// display the results

if (strlen($msg) > 0)
{				// report failure
    print '    "message" : "' . $msg . "'\n};\n";
}				// report failure
else
{				// have a temple or temples to return
    $count		                = $temples->count();
    print '    "count" : "' .  $count . "\",\n";
    $info		                = $temples->getInformation();
    $query		                = $info['query'];
    print '    "cmd" : "' . str_replace('"', '\\"', 
                                str_replace('\\','\\\\',$query)) . "\",\n";
    print "    \"temples\" : {\n    ";
    $comma                      = '';
    foreach($temples as $idlr => $temple)
    {			// run through all matching temples
        $code                   = json_encode($temple['code']);
        print "$comma\n    $code :\n       ";
        $temple->toJson('temple');
        $comma          = ',';
    }			// run through all matching temples
    print "\n    }\n}\n";
}				// have a temple or temples to return
