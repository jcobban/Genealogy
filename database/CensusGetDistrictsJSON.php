<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusGetDistrictsJSON.php											*
 *																		*
 *  Get a selected list of census district information as a JSON		*
 *  document.															*
 *																		*
 *  History:															*
 *		2020/12/01      created                                         *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . "/Census.inc";
require_once __NAMESPACE__ . "/District.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$census		    			= null;     // census identifier
$province					= null;     // province identifier
$censusYear					= 9999;
$lang		    			= 'en';

print "{\n";
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by URL to display current status of account
    $parmsText              = "\"get\" : {";
    $comma                  = '';
	foreach($_GET as $key => $value)
	{			// loop through all parameters
        $parmsText          .= "$comma\"$key\": \"" .
                                htmlspecialchars($value) . '"';
        $comma              = ', ';
	    $value              = trim($value);
	    if (strlen($value) == 0)
	        continue;
		switch (strtolower($key))
		{		// act on specific parameters
		    case 'census':
		    {
				$census		= htmlspecialchars($value);
				$censusRec	= new Census(array('censusid'	=> $value));
				if ($censusRec->isExisting())
				    $censusYear	= $censusRec['year'];
				else
				    $msg	.= "Census='$census' not supported. ";
				break;
		    }
	
		    case 'province':
		    case 'state':
		    {
				if (preg_match('/^[A-Z]{2}$/', $value) == 1)
				    $province	= $value;
				else
                    $msg	.= "Province='" . 
                                htmlspecialchars($value) . 
                                "' invalid. ";
				break;
		    }
	
		    case 'lang':
		    {
	            $lang           = FtTemplate::validateLang($value);
				break;
		    }
		}		// act on specific parameters
	}			// loop through all parameters
    print "$parmsText},\n";
}                       // invoked by URL to display current status of account

// the invoker must explicitly provide the Census identifier
if (is_null($census))
	$msg	.= 'Missing Census identifier.  ';
else
if ($censusYear < 1867 && is_null($province))
// for pre-confederation censuses, the province must also be defined
	$province	= substr($census, 0, 2);


if (strlen($msg) == 0)
{		// no messages so far
	// variables for construction the SQL SELECT statement
	$getParms	= array('D_Census'	=> $census);
	if (strlen($province) == 2)
	    $getParms['D_Province']	= $province;
	if (substr($lang, 0, 2) == 'fr')
	    $getParms['order']		= 'D_Nom';
	else
	    $getParms['order']		= 'D_Name';
	$districts			= new RecordSet('Districts',
									    $getParms);

	// display the results
	if (strlen($msg) == 0)
    {		// no errors
        $comma          = '';
	    foreach($districts as $district)
	    {		// loop through all result rows
			$did		= $district->get('d_id');
			if ($did == floor($did))
			    $did	= floor($did);
			if (substr($lang, 0, 2) == 'fr')
			    $Name	= htmlspecialchars($district->get('d_nom')); 
			else
			    $Name	= htmlspecialchars($district->get('d_name')); 
			$Province	= $district->get('d_province'); 
			print("$comma\"$did\" : \"$Name ($Province)\"");
            $comma          = ",\n";
        }		// loop through all result rows
	}		// no errors
	else
        print(",\n\"msg\" : \"$msg\"\n");
}		// user supplied needed parameters
else
    print("\n\"msg\" : \"$msg\"\n");
print "\n}";

