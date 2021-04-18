<?php
namespace Genealogy;
use \PDO;
use \Templating\Template;

/************************************************************************
 *  Census1901S2.php													*
 *																		*
 *  Display a report of the images for Schedule 2 of the 1901 Census    *
 *  of Canada.                                                          *
 *																		*
 *  History:															*
 *      2021/04/17      created                                         *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Census1901Sched2.inc';
require_once __NAMESPACE__ . '/common.inc';

// variables for constructing the main SQL SELECT statement
$countryName					= 'Canada';
$censusID						= 'CA1901';
$censusIDText           		= null;
$census                			= null;
$censusYear						= 1901;
$province						= null;
$provinceText					= null;
$provinceName					= '';
$district    					= null;
$distID	    					= null;
$distIDText    					= null;
$districtName					= '';
$subdistrict    				= null;
$subDistrictName				= '';
$subDistID						= null;
$subDistIDText					= null;
$division						= '';
$lang		    				= 'en';
$getParms                       = array();

// get parameter values into local variables
// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by URL 
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {				    // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                    "$safevalue</td></tr>\n";
	    $value              = trim($value);
	    if (strlen($value) == 0)
	        continue;
		switch(strtolower($key))
		{
		    case 'census':
		    case 'censusid':
            {			// supported field name
                if (preg_match('/^[Cc][Aa]1901/', $value))
                    $censusID       = strtoupper($value);
                else
                    $censusIDText   = $safevalue;
				break;
		    }			// Census Identifier
	
		    case 'province':
		    {			// Province code (pre-confederation)
                if (preg_match('/^[a-zA-Z]{2}/', $value))
                    $province		= strtoupper($safevalue);
                else
                    $provinceText	= strtoupper($safevalue);
				break;
		    }			// Province code
	
		    case 'district':
		    {			// District number
				$distpat	                    = '/^\d+(\.5|\.0)?$/';
				if (preg_match($distpat, $value) == 1)
				{		// valid district number
				    $distID			            = $value;
				    $getParms['districtnumber']	= $value;
				}		// valid district number
                else
                    $distIDText                 = $safevalue;
				break;
		    }			// District number
	
		    case 'subdistrict':
		    {			// subdistrict id
				$subDistID			            = $safevalue;
				$getParms['subdistid']	        = "^$subDistID$";
				break;
		    }			// subdistrict id
	
		    case 'division':
		    case 'div':
		    {			// division
				$division			            = $safevalue;
	            $getParms['division']		    = "^$division$";
				break;
		    }			// division
	
	        case 'lang':
	        {
	            $lang               = FtTemplate::validateLang($value);
            }

		    case 'debug':
		    {			// already handled
				break;
		    }			// already handled 
		}			    // already handled
	}				    // loop through parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}                       // invoked by URL

if (canUser('edit'))
	$action		                = 'Update';
else
    $action		        		= 'Display';

// get template
$template	            = new FtTemplate("Census1901S2$action$lang.html");
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

if (is_string($censusIDText))
    $msg                .= "Census='$censusIDText' invalid. ";
else
if (strlen($censusID) == 0)
    $msg	            .= 'Missing mandatory parameter Census. ';
else
{
    $census		        = new Census(array('censusid' => $censusID));
}

if (is_string($distIDText))
    $msg	            .= "Invalid value of District='$distIDText'. ";
else
if (strlen($distID) == 0)
{		// missing mandatory parameter
	$msg	            .= 'Missing mandatory parameter District. ';
}		// missing mandatory parameter
else
{
	$district	= new District(array('census'	=> $census,
							         'id'	    => $distID));
	if ($lang == 'fr')
	    $districtName	= $district->get('nom');
	else
        $districtName	= $district->get('name');
    $province           = $district['province'];
}

if (strlen($province) == 2)
{
	$domain		        = new Domain(array('code' => "CA$province"));
	$provinceName		= $domain->getName();
}

if (strlen($subDistID) == 0)
{		// missing mandatory parameter
	$msg	.= 'Missing mandatory parameter SubDistrict. ';
}		// missing mandatory parameter
else
{
    if (preg_match('/^([0-9.]+):(.+)$/', $subDistID, $matches))
    {
        if ($matches[1] == $distID)
        {
            $subDistID      = $matches[2];
            $getParms['subdistid']	        = "^$subDistID$";
        }
    }
	// get information about the sub-district
	$parms	= array('sd_census'	=> $census, 
					'sd_distid'	=> $distID, 
					'sd_id'		=> $subDistID,
					'sd_div'	=> $division);

	$subDistrict	= new SubDistrict($parms);
	if (!$subDistrict->isExisting())
	    $msg		.= "Invalid identification of sub-district:".
				        " sd_census=$censusID" .
				        ", sd_distid=$distID". 
				        ", sd_id=$subDistID". 
				        ", sd_div=$division";
	$subDistrictName	= $subDistrict->get('sd_name');
	if (strlen($subDistrictName) > 48)
        $subDistrictName	= substr($subDistrictName, 0, 45) . '...';
}

if (strlen($msg) == 0)
{		// no messages, do search
    $set        = new RecordSet('Census1901Sched2', $getParms);
    $template['Row$pagenum']->update($set);
}		// no errors, continue with request
else
	$template['censusForm']->update(null);

$template->set('CENSUSYEAR', 		$censusYear);
$template->set('CENSUS',			$censusYear);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CENSUSID',			$censusID);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('LANG',			    $lang);
$template->set('DISTRICT',			$distID);
$template->set('DISTRICTNAME',		$districtName);
$template->set('SUBDISTRICT',		$subDistID);
$template->set('SUBDISTRICTNAME',	$subDistrictName);
$template->set('DIVISION',			$division);
$template->set('CONTACTTABLE',		'Census1901Sched2');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
