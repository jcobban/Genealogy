<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusGetSubDistL.php												*
 *																		*
 *  Generate an XML document with information about a set of census		*
 *  enumeration sub-districts											*
 *																		*
 *  Parameters (passed by method=post):									*
 *		Census			census identifier including domain				*
 *		District		district number									*
 *			            if <select name='District[]' multiple='multiple'>
 *						this is an array. 								*
 *		Sched			schedule number									*
 *																		*
 *  Generates XML structure like:										*
 *    <select Census="CA1891" District="100">							*
 *		<parms>Census=CA1891&District=100</parms>						*
 *	    <query>SELECT SD_DistId, ... FROM SubDistricts WHERE ... </query>
 *		<option value="100:B">Bracebridge Village						*
 *	    <div dist="100" sdid="B" id="1" sched="1" reel="T-6357" base="148159" frame="501" count="10" pages="19" page1="1"/>	
 *	    <div dist="100" sdid="B" id="2" sched="1" reel="T-6357" base="148159" frame="511" count="12" pages="22" page1="1"/>
 *	    <div dist="100" sdid="B" id="3" sched="1" reel="T-6357" base="148159" frame="523" count="9" pages="17" page1="1"/>
 *		</option>														*
 *		...																*
 *    </select>															*
 *																		*
 *  History:															*
 *		2010/10/27		move connection establishment to common.inc		*
 *		2011/04/11		get input from $_POST, not $_REQUEST			*
 *		2011/06/27		add 1916 to list of valid censuses				*
 *		2011/09/03		also sort 1911 by division						*
 *		2012/02/08		field SD_Sched added							*
 *						cleanup parameter validation					*
 *		2012/06/24		include ByPage in result						*
 *		2012/09/16		use common census identifier validation table	*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/11/26		handle database server failure gracefully		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/21		use class Census to get census information		*
 *		2017/10/25		use class RecordSet								*
 *		2020/12/01      eliminate XSS vulnerabilities                   *
 *																		*
 * Copyright 2020 &copy; James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . '/common.inc';

$distpattern	= '/^[0-9]+(\.5)?$/';

// process input parameters
$census		= null;		// census code 'CCYYYY'
$distsel		= '';
$distList		= '';
$or			= '';		
$comma		= '';
$parmList		= '';
$amp		= '';
$sched		= '1';
$getParms	= array();

foreach ($_POST as $key => $value)
{		            // loop through parameters
    // format the parmlist for reply
    if (is_array($value))
    {
        foreach($value as $avalue)
        {
    		$parmList	.= $amp . $key . '[]=' . htmlspecialchars($avalue);
    		$amp		= '&amp;';
        }
    }
    else
        $parmList	.= $amp . $key . '=' . htmlspecialchars($value);
    $amp	= '&amp;';

    switch(strtolower($key))
    {		        // act on specific key
        case 'census':
        {
    		$census	= $value;
    		$censusRec	= new Census(array('censusid'	=> $value,
    						   'collective'	=> 0));
    		if(!$censusRec->isExisting())
                $msg	.= "Census='" . htmlspecialshars($value) . 
                            "' is invalid. ";
    		$getParms['census']	= $census;
    		break;
        }

        case 'district':
        {		    // occurs multiple times if multi-selection
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
    		}		// District value is an array
    		$distList	= implode(',', $dists);
    		break;
        }		    // occurs multiple times if multi-selection

        case 'sched':
        {		    // schedule identifier
    		$sched		= $value;
    		break;
        }		    // schedule identifier

        case 'debug':
            break;          // already handled

        default:
        {		    // anything else
            $msg	.= "Unexpected parameter $key='" .
                        htmlspecialchars($value) . "'. ";
    		break;
        }		    // anything else
    }		        // act on specific key
}		            // loop through parameters
if (count($getParms) == 0)
    $msg		.= "No parameters passed by method='post'. ";
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
    print "<query>" . htmlentities($query,ENT_XML1) . "</query>\n";

    $OldId	= '';
    if (count($result) > 0)
    {		// at least one row in division
        foreach($result as $row)
        {		// loop through all result rows
    		$distId		= $row->get('sd_distid');
    		if (substr($distId,strlen($distId) - 2) == '.0')
    		    $distId	= substr($distId, 0, strlen($distId) - 2); 
    		$Id		= $row->get('sd_id'); 
    		$Name		= htmlspecialchars($row->get('sd_name'));
    		$Div		= $row->get('sd_div');
    		$LacReel	= $row->get('sd_lacreel');
    		$ImageBase	= $row->get('sd_imagebase');
    		$RelFrame	= $row->get('sd_relframe');
    		$FrameCt	= $row->get('sd_framect');
    		$Pages		= $row->get('sd_pages');
    		$Page1		= $row->get('sd_page1');
    		$byPage		= $row->get('sd_bypage');

    		if ($Id != $OldId)
    		{
    		    if ($OldId != "")
    			print("</option>\n");
    		    print("<option value='$distId:$Id'>$Name\n");
    		    $OldId	= $Id;
    		}
    		print("<div dist='$distId' sdid='$Id' div='$Div' sched='$sched' reel='$LacReel' base='$ImageBase' frame='$RelFrame' count='$FrameCt' pages='$Pages' page1='$Page1' bypage='$byPage'/>\n");
        }		// loop through all result rows
        
        print("</option>\n");
    }		// at least one row in division
}		// no errors
else
{		// report errors
    print "<message>$msg</message>\n";
}		// report errors
print("</select>\n");	// close off top node of XML result
?>
