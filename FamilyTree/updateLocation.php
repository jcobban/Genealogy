<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateLocation.php													*
 *																		*
 *  Handle a request to update an individual location in 				*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters:															*
 *		idlr	unique numeric identifier of the LegacyLocation record	*
 *				to update												*
 *		others	any field name defined in the LegacyLocation record		*
 *																		*
 *  History:															*
 *		2010/08/16		Add information into log						*
 *		2010/10/05		redirect to locations list, not updated record	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/05		improve error handling							*
 *		2011/09/26		support geo-locator format latitude and			*
 *						longitude										*
 *		2012/01/13		change class names								*
 *		2013/04/12		use possibly updated location name for			*
 *						search pattern									*
 *		2013/04/16		adjusting latitude and longitude to internal	*
 *						values is moved to LegacyLocation::postUpdate	*
 *		2013/05/18		permit creation of new location signalled		*
 *						by IDLR=0										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/10/01		use method isOwner to determine authorization	*
 *						to update										*
 *		2015/01/06		diagnostic information redirected to $warn		*
 *						if creating new location and no errors detected	*
 *						redirect to menu of locations					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/21		restore redirect to menu at end if not			*
 *						invoked in child window							*
 *		2016/01/19		add id to debug trace							*
 *		2016/05/31		correct setting of latitude and longitude		*
 *		2016/06/06		addOwner fails for new location					*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use set(										*
 *		2018/12/12		use class Template								*
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$idlr		    = null;		// primary key
$location		= null;		// instance of Location
$lang		    = 'en';
$closeAtEnd		= false;

// get the requested Location record
// override from passed parameters
if (isset($_POST) && count($_POST) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{			// act on specific keys
		    case 'idlr':
		    {			// identifier present
				$idlr	= $value;
				if (strlen($idlr) > 0 &&
				    ctype_digit($idlr))
				{		// positive integer
				    if ($idlr > 0)
				    {		// IDLR of existing location
						$location	= new Location(array('idlr' => $idlr));
				    }		// IDLR of existing location
				}		// positive integer
				else
				    $msg	.= 'Invalid Value of idlr=' . $idlr;
				break;
		    }			// identifier present
	
		    case 'location':
		    {			// location name
				if (is_null($idlr))
				{
				    $msg	.= 'Got to location name handler with IDLR null, which should only happen if the input field for location is before the input field for IDLR in the input form. ';
				}
	
				if (strlen($msg) == 0)
				{		// no errors
				    if (is_null($location))
				    {		// create new location
						$location	= new Location(array('location' => $value));
	
						// make the current user an owner of this location
						if (!$location->isExisting())
						    $location->save(false);
						$location->addOwner();
				    }		// create new location
				    else
						$location->setName($value);
				}		// no errors
				break;
		    }			// location name supplied
	
		    case 'latitude':
		    {
				if ($location)
				    $location->setLatitude($value);
				break;
		    }			// latitude
	
		    case 'longitude':
		    {
				if ($location)
				    $location->setLongitude($value);
				break;
		    }			// longitude
	
		    case 'fsplaceid':
		    case 'used':
		    case 'sortedlocation':
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
		    {
				if ($location)
				    $location->set($key, $value);
				break;
		    }			// longitude
	
		    case 'closeatend':
		    {			// close the frame after update
				if (strtolower($value) == 'y')
				    $closeAtEnd		= true;
				break;
		    }			// close the frame after update
	
		    case 'lang':
		    {
	                $lang       = FtTemplate::validateLang($value);
				break;
		    }
		}			// act on specific keys
	}				// loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get

// use possibly updated location name
// for search pattern
if ($location)
{
	$pattern		= $location->getName();
	if (strlen($pattern) > 5)
	    $pattern	= substr($pattern, 0, 5);

	if ($location->isOwner())
	    $location->save(false);
}
else
	$pattern		= '';

if (is_null($idlr))
{
	$msg			.= 'idlr Parameter Missing';
	$idlr			= 1;
}

if (strlen($msg) > 0 || strlen($warn) > 0)
{
	$template	        = new FtTemplate("updateLocationError$lang.html");
    $template->set('LANG',		    $lang);
    $template->set('NAMESTART',		$pattern);
    $template->set('IDLR',		    $idlr);
	$template->display();
}
else
{			// update was successful
	if ($closeAtEnd)
	{		// close the dialog
	    $template	        = new FtTemplate("updateLocationOK$lang.html");
        $template->set('LANG',		$lang);
        $template->set('NAMESTART',	$pattern);
        $template->set('IDLR',		$idlr);
        $template->display();
	}		// close the dialog
	else
	{		// redirect to main page for locations
	    header('Location: Locations.php?pattern=^' . urlencode($pattern));
	    //header('Location: Locations.php?pattern=^' . urlencode($pattern));
	}		// redirect to main page for locations
}			// update was successful
