<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteLocation.php													*
 *																		*
 *  Delete a location record from the table of locations.				*
 *																		*
 *  History:															*
 *		2010/10/29		created											*
 *		2010/11/05		validate authorization by canUser()				*
 *						improve error diagnostics						*
 *		2010/12/21		handle exception from new Location				*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/07/31		use default dynamic initialization				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/19		use Location::delete to delete record			*
 *		2014/11/30		do not permit deletion of a location that		*
 *						has events referencing it						*
 *						use LegacyIndiv::getIndivs and					*
 *						Event::getEvents to identify references			*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/31		use RecordSet instead of Event::getEvents		*
 *		2017/12/12		use PersonSet instead of Person::getPersons		*
 *						format count of events							*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/02/18      use new FtTemplate constructor                  *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2023/01/23      Get message texts from template                 *
 *																		*
 *  Copyright &copy; 2023 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Location.inc";
require_once __NAMESPACE__ . "/Person.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/PersonSet.inc";
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$idlr               = null;
$idlrtext           = null;
$lang               = 'en';
$langtext           = null;
$name               = '';
$namePref           = '';

if (isset($_POST) && count($_POST) > 0)
{		            // parameters passed by method=post
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {		        // loop through all parameters
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n"; 
		switch(strtolower($key))
		{	        // act on specific key

		    case 'lang':
		    {
                $lang       = FtTemplate::validateLang($value,
                                                       $langtext);
				break;
		    }	    // presentation language

		    case 'idlr':
            {
                if (is_int($value) && $value > 0)
                {
                    $idlr	        = $value;
                }
				else
                if (is_string($value) &&
                    ctype_digit($value))
                {
                    $idlr	        = intval($value);
                }
				else
                {	// invalid format
                    if (strlen($safevalue) > 0)
                        $idlrtext       = $safevalue;
                    else
                        $idlr           = null;
				}	// invalid format
				break;
		    }	    // idlr
		}	        // act on specific key
    }		        // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // parameters passed by method=post

$template           = new FtTemplate("deleteLocation$lang.html");

// determine if permitted to update records
if (!canUser('edit'))
{		        // take no action
	$msg	        .= $template['notAuthorized']->innerHTML;
}		        // take no action

if (is_string($idlrtext))
{
    $msg	    .= $template['invalidIdlr']->replace('$idlrtext',$idlrtext);
}
else
if (is_null($idlr))
{
    $name       = $template['missingIdlr']->innerHTML;
    $msg	    .= $name;
    $namePref   = 'IDLR';
}
else
    $warn       .= "<p>IDLR is " . var_export($idlr, true) . "</p>\n";

if (strlen($msg) == 0)
{		        // no problems encountered
    $location	    = new Location(array('IDLR' => $idlr));
    $name	        = $location->getName();
    $namePref	    = substr($name, 0, 5); 

	// search for matches in tblIR
	$indParms	    = array(array('idlrbirth'   => $idlr,
	    					      'idlrchris'   => $idlr,
	       					      'idlrdeath'   => $idlr,
		    				      'idlrburied'  => $idlr),
                                  'order'		=>
                            'Surname, GivenName, BirthSD, DeathSD');
	
	$persons	    = new PersonSet($indParms,
	       							'Surname, GivenName, BirthSD, DeathSD');
	$info	    	= $persons->getInformation();
	$count	    	= $info['count'];
	
	// search for matches in tblMR
	$famParms	    = array('idlrmar'   => $idlr,
	    					'order'		=> 'Surname, GivenName, BirthSD, DeathSD');
	
	$families	    = new RecordSet('Families', $famParms);
	$info		    = $families->getInformation();
	$count		    += $info['count'];
	
	// check for references to this location from events
	$getParms	    = array('idlrevent' => $idlr);
	$events		    = new RecordSet('Events', $getParms);
	$info		    = $events->getInformation();
	$count		    += $info['count'];
	
    if ($count == 0)
    {
        $result		= $location->delete(false);
        $template['referenced']->update(null);
        if ($result == 0)
            $template['deleted']->update(null);
    }
    else
    {
        $template['deleted']->update(null);
        $template->set('COUNT',     $count);
    }
}		        // no problems encountered
else
{
    $template['referenced']->update(null);
    $template['deleted']->update(null);
}

$template->set('NAME',          $name);
$template->set('NAMEPREF',      $namePref);
$template->set('IDLR',          $idlr);
$template->display();
