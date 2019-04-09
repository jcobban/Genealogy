<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusGetSubDists.php												*
 *																		*
 *  Generate an XML document with information about a set of census		*
 *  enumeration sub-districts.											*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Census			census identifier including domain				*
 *		District		district number									*
 *						if <select name='District[]' multiple='multiple'>*
 *						this is an array. 								*
 *		Sched			schedule number									*
 *																		*
 *  History:															*
 *		2010/10/27		move connection establishment to common.inc		*
 *		2011/01/22		support divisions in 1911 census				*
 *		2012/02/06		improve parameter validation					*
 *						support SD_Sched field							*
 *		2012/06/24		include ByPage in result						*
 *		2012/09/16		use common census identifier validation			*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/07/14		change variable names to start with lower 		*
 *						case letter										*
 *		2013/11/26		handle database server failure gracefully		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/21		use class Census to get census information		*
 *		2017/09/14		correct handling of array parameter values		*
 *						use class SubDistrict							*
 *		2017/10/25		use class RecordSet								*
 *		2017/12/08		output divisions								*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$distpattern	= '/^[0-9]+(\.5)?$/';

// process input parameters
$census	= 'CA1881';
$distsel	= '';
$distList	= '';
$or		= '';		
$comma	= '';
$parmList	= '';
$amp	= '';
$sched	= '1';
$getParms	= array();

foreach ($_GET as $key => $value)
{		// loop through district numbers
    // format the parameter list for reply
    if (is_array($value))
    {
        foreach($value as $avalue)
        {
    		$parmList	.= $amp . $key . '[]=' . $avalue;
    		$amp		= '&amp;';
        }
    }
    else
        $parmList	.= $amp . $key . '=' . $value;
    $amp		= '&amp;';

    switch($key)
    {		// act on specific key
        case 'Census':
        {
    		$census		= $value;
    		$censusRec	= new Census(array('censusid'	=> $value,
    						   'collective'	=> 0));
    		if(!$censusRec->isExisting())
    		    $msg	.= "Census='$value' is invalid. ";
    		$getParms['census']	= $census;
    		break;
        }

        case 'District':
        {
    		if (is_array($value))
    		    $dists	= $value;
    		else
    		    $dists	= explode(",", $value);
    
    		if (count($dists) > 0)
    		{		// District value is not empty
    		    if (count($dists) == 1)
    			$getParms['distid']	= $dists[0];
    		    else
    			$getParms['distid']	= $dists;
    		}		// District value is not empty
    		$distList	= implode(',', $dists);
    		break;
        }

        case 'Sched':
        {		// schedule identifier
    		$sched		= $value;
    		break;
        }		// schedule identifier

        default:
        {		// anything else
    		$msg	.= "Unexpected parameter $key='$value'. ";
    		break;
        }		// anything else
    }		// act on specific key
}		// loop through parameters
if (count($getParms) == 0)
    $msg		.= "No parameters passed by method='get'. ";
$getParms['sched']		= $sched;

if (strlen($msg) == 0)
{		// user supplied needed parameters
    $result		= new RecordSet('SubDistricts',
    					$getParms);
}		// perform query

// display the results
print("<?xml version='1.0' encoding='UTF-8'?>\n");

// top node of XML result
print("<select Census='$census' District='$distList'>\n");
print "<parms>" . $parmList . "</parms>\n";

if (strlen($msg) == 0)
{		// no errors
    $information	= $result->getInformation();
    $query		= $information['query'];
    print "<query>" . xmlEntities($query) . "</query>\n";

    $oldId	= "";
    if (count($result) > 0)
    {	// at least one division returned
        foreach($result as $row)
        {	// loop through all result rows
    		$distId		= $row->get('sd_distid'); 	// district id
    		if (substr($distId,strlen($distId) - 2) == ".0")
    		    $distId	= substr($distId, 0, strlen($distId) - 2); 
    		$sdId		= $row->get('sd_id'); 	// subdistrict id
    		$sdName		= xmlentities($row->get('sd_name'));
    		$division	= $row->get('sd_div');
    		$lacReel	= $row->get('sd_lacreel');
    		$imageBase	= $row->get('sd_imagebase');
    		$relFrame	= $row->get('sd_relframe');
    		$frameCt	= $row->get('sd_framect');
    		$pages		= $row->get('sd_pages');
    		$page1		= $row->get('sd_page1');
    		$byPage		= $row->get('sd_bypage');

    		if ($sdId != $oldId)
    		{
    		    if ($oldId != "")
    			print("</option>\n");
    		    print("<option value='$sdId'>$sdName\n");
    		    $oldId	= $sdId;
    		}
    		if (strlen($division) > 0)
    		    print "<div id='$division'></div>\n";
    		//$warn	.= "<p> dist='$distId' sdid='$sdId' id='$division' sched='$sched' reel='$lacReel' base='$imageBase' frame='$relFrame' count='$frameCt' pages='$pages' page1='$page1' bypage='$byPage'</p>\n";
        }	// loop through all result rows
        
        print "</option>\n";
    }		// at least one division returned
}		// no errors
else
{		// report errors
    print "<message>$msg</message>\n";
}		// report errors
print("</select>\n");	// close off top node of XML result
?>
