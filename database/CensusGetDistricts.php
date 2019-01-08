<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusGetDistricts.php												*
 *																		*
 *  Get a selected list of census district information as an XML		*
 *  document.															*
 *																		*
 *  History:															*
 *		2010/11/21		use common MDB2 database connection				*
 *		2013/11/16		gracefully handle database server failure		*
 *		2013/11/26		clean up parameter validation					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/19		support french									*
 *		2017/10/25		use class RecordSet								*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Census.inc";
require_once __NAMESPACE__ . "/District.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$census		    = null;
$province		= null;
$censusYear		= 9999;
$lang		    = '';

foreach($_GET as $key => $value)
{			// loop through all parameters
	switch (strtolower($key))
	{		// act on specific parameters
	    case 'census':
	    {
			$census		= $value;
			$censusRec	= new Census(array('censusid'	=> $value));
			if ($censusRec->isExisting())
			    $censusYear	= intval(substr($census, 2, 4));
			else
			    $msg	.= "Census='$census' not supported. ";
			break;
	    }

	    case 'province':
	    case 'state':
	    {
			if (preg_match('/^[A-Z]{2}$/', $value) == 1)
			    $province	= $value;
			break;
	    }

	    case 'lang':
	    {
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
			break;
	    }
	}		// act on specific parameters
}			// loop through all parameters

// the invoker must explicitly provide the Census identifier
if (is_null($census))
	$msg	.= 'Missing Census identifier.  ';
else
if ($censusYear < 1867 && is_null($province))
// for pre-confederation censuses, the province must also be defined
	$province	= substr($census, 0, 2);

// top node of XML result
header("Content-Type: text/xml");
print("<?xml version='1.0' encoding='UTF-8'?>\n");

if (strlen($msg) == 0)
{		// no messages so far
	// variables for construction the SQL SELECT statement
	$getParms	= array('D_Census'	=> $census);
	if (strlen($province) == 2)
	    $getParms['D_Province']	= $province;
	if ($lang == 'fr')
	    $getParms['order']		= 'D_Nom';
	else
	    $getParms['order']		= 'D_Name';
	$districts			= new RecordSet('Districts',
									    $getParms);

	// display the results
	if (strlen($msg) == 0)
	{		// no errors
	    print("<select Census='$census'>\n");
	    foreach($districts as $district)
	    {		// loop through all result rows
			$did		= $district->get('d_id');
			if ($did == floor($did))
			    $did	= floor($did);
			if ($lang == 'fr')
			    $Name	= htmlspecialchars($district->get('d_nom')); 
			else
			    $Name	= htmlspecialchars($district->get('d_name')); 
			$Province	= $district->get('d_province'); 
			print("<option value='$did'>$Name ($Province)</option>\n");
	    }		// loop through all result rows
	    print("</select>\n");	// close off top node of XML result
	}		// no errors
	else
	    print("<msg>$msg</msg>\n");
}		// user supplied needed parameters
else
	print("<msg>$msg</msg>\n");

