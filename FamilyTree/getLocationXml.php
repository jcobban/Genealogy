<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getLocationXml.php													*
 *																		*
 *  Get the information on a location or locations as an XML response	*
 *  file so it can be retrieved by Javascript using AJAX.				*
 *  In particular invoked by /jscripts/locationCommon.js				*
 *																		*
 *  If the numeric key of a location is specified, only that location	*
 *  is returned.														*
 *																		*
 *  If the name of the location is specified and that string matches	*
 *  exactly either the location or the short name of a location or		*
 *  locations, then that location or those locations are returned. 		*
 *  However if the name does not match any location then the name is	*
 *  used as a pattern match, and the location or locations whose		*
 *  location names start with the supplied name are returned.			*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		name				location name to search for					*
 *  or																	*
 *		idlr				numeric key of location to retrieve			*
 *																		*
 *  History:															*
 *		2011/10/01		created											*
 *		2011/10/28		fix handling of search argument containing quote*
 *		2012/10/07		return default entry for empty string			*
 *						limit max number of replies to 40 to avoid		*
 *						a long wait time								*
 *						include issued command in XML reply				*
 *		2012/10/24		fix XML syntax error if requested location		*
 *						contains XML specific characters such as &, <, >*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/05		use LegacyLocations::getLocations to obtain the	*
 *						list of matches									*
 *		2014/09/18		include SQL command in response					*
 *		2015/01/07		class LegacyLocation declared twice				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/11/04		use class RecordSet in place of getLocations	*
 *		2017/12/08		don't include duplicate entries in count		*
 *						so Javascript won't prompt						*
 *		2019/11/09      move escaping of characters in name here from   *
 *		                Javascript function locationChanged             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$limit		= 40;		// default limit
$getParms		= array();
$name		= null;

foreach($_GET as $fldname => $value)
{			// loop through all parameters
    switch(strtolower($fldname))
    {		// act on specific fieldnames
        case 'limit':
        {		// override limit
    		$limit			= $value;
    		break;
        }		// other field names

        case 'debug':
        {
    		break;
        }

        case 'name':
        {		// special 'name' parameter
    		if ($value == '')
    		    $getParms['location']	= $value;
    		else	// match either location or short name
            {
                $value          = trim($value);
                $name		    = $value;
                // if name is enclosed in square brackets do not include them
                if (substr($value, 0, 1) == '[')
                    $value      = substr($value, 1);
                if (substr($value, -1, 1) == ']')
                    $value      = substr($value, 0, strlen($value) - 1);
                // escape regexp special characters
                $search         = str_replace('?', '\\?', $value);
                $search         = str_replace('+', '\\.', $search);
                $search         = str_replace('.', '\\.', $search);
                $search         = str_replace('[', '\\[', $search);

    		    $getParms[]		= array('location'  => "^$search$",
    	            					'shortname' => "^$search$");
    		}
    		break;
        }		// special 'name' parameter

        case 'idlr':
        case 'fsplaceid':
        case 'location':
        case 'used':
        case 'sortedlocation':
        case 'latitude':
        case 'longitude':
        case 'tag1':
        case 'shortname':
        case 'preposition':
        case 'notes':
        case 'verified':
        case 'fsresolved':
        case 'veresolved':
        case 'qstag':
        case 'zoom':
        case 'boundary':
        {		// other field names
    		$getParms[$fldname]	= $value;
    		break;
        }		// other field names
    }		// act on specific fieldnames
}			// loop through all parameters

$getParms['limit']		= $limit;
$locations			    = new RecordSet('Locations', $getParms);

if (is_string($name) && $locations->count() == 0)
{			// repeat with more general search
    unset($getParms[0]);
    $getParms['location']	= "^$search";   // starting with search
    $locations		    = new RecordSet('Locations', $getParms);
}

// display the results
print("<?xml version='1.0' encoding='UTF-8'?>\n");

if (strlen($msg) > 0)
{				// report failure
    print "<msg>\n";
    print "    " . $msg . "\n";
    print "</msg>\n";
}				// report failure
else
{				// have a location or locations to return
    $count		        = $locations->count();
    if ($count > 1)
    {			// check for duplicates
        $oldname	    = null;
        foreach($locations as $location)
        {
    		if ($location->getName() == $oldname)
    		    $count--;	// we only have to fudge the count
    		else
    		    $oldname	= $location->getName();
        }
    }			// check for duplicates
    print '<locations count="' .  $count . '" ';
    foreach($_GET as $key => $value)
    {			// report parameters
        print $key . '="' . str_replace('"','&quote;',str_replace('&','&amp;',$value)) . '" ';
    }			// report parameters
    print ">\n";		// close tag
    $info		        = $locations->getInformation();
    $query		        = htmlspecialchars($info['query']);
    print "<cmd>$query</cmd>\n";
    foreach($locations as $idlr => $location)
    {			// run through all matching locations
        $location->toXml('location');
    }			// run through all matching locations
    print "</locations>\n";
}				// have a location or locations to return
