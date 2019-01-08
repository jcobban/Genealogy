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
 *						use htmlHeader to generate HTML					*
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
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Location.inc";
require_once __NAMESPACE__ . "/Person.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/PersonSet.inc";
require_once __NAMESPACE__ . '/common.inc';

    // determine if permitted to update records
    if (!canUser('edit'))
    {		// take no action
		$msg	.= 'User not authorized to delete location. ';
    }		// take no action

    // validate parameters
    if (array_key_exists('idlr', $_POST))
		$idlr	= $_POST['idlr'];
    else
    {
		$msg	.= 'Missing mandatory parameter idlr. ';
		$idlr	= null;
    }

    if (strlen($msg) == 0)
    {		// no problems encountered
		try
		{
		    $location	= new Location(array('IDLR' => $idlr));
		    $name	= $location->getName();
		    $namePref	= substr($name, 0, 5); 
		}
		catch (Exception $e)
		{	// not found
		    $location	= null;
		    $name	= 'Not Found';
		    $namePref	= '';
		    $msg	.= "Create location failed for IDLR=$idlr. " .
						$e->getMessage();
		}	// not found

		// search for matches in tblIR
		$indParms	= array(array('idlrbirth' => $idlr,
							      'idlrchris' => $idlr,
							      'idlrdeath' => $idlr,
							      'idlrburied' => $idlr),
							'order'		=> 'Surname, GivenName, BirthSD, DeathSD');

		$persons	= new PersonSet($indParms,
								'Surname, GivenName, BirthSD, DeathSD');
		$info		= $persons->getInformation();
		$count		= $info['count'];

		// check for references to this location from events
		$getParms	= array('idlrevent' => $idlr);
		$events		= new RecordSet('Events', $getParms);
		$info		= $events->getInformation();
		$count		+= $info['count'];
		if ($count > 0)
		    $msg	.= "Location not deleted because " .
						   number_format($count) .
						   " events reference it. ";
    }		// no problems encountered
    else
    {		// error
		$location	= null;
		$name		= 'Error';
		$namePref	= '';
    }		// error

    $title	= "Delete Location: $name";
    htmlHeader($title,
				array("/jscripts/default.js"));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/genCountry.php?cc=CA'	=> 'Canada',
				  '/Canada/genProvince.php?Domain=CAON'
									=> 'Ontario',
				  '/FamilyTree/Services.php'	=> 'Services',
				  "/FamilyTree/Locations.php?pattern=$namePref"
									=> 'Locations'));
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="deleteLocationHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
      <div style="clear: both;"></div>
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class="message">
		<?php print $msg; ?> 
    </p>
<?php
    }
    else
    {
		if ($debug)
		    $deleteParm	= "<p>";
		else
		    $deleteParm	= false;
		$result		= $location->delete($deleteParm);

		if ($result == 1)
		{		// exactly one location deleted
?>
		<p class="label">Location "<?php print $name; ?>" deleted</p>
<?php
		}		// exactly one location deleted
		else
		if ($result == 0)
		{		// no locations deleted
?>
		<p class="warning">Location "<?php print $name; ?>" not deleted</p>
<?php
		}		// no locations deleted
		else
		{		// more than one location deleted
?>
		<p class="warning"><?php $result; ?> locations deleted</p>
<?php
		}		// more than one location deleted
    }

?>
</div>
<?php
    if (is_null($idlr))
		pageBot($title);
    else
		pageBot($title . " IDLR=$idlr");
?>
</body>
</html>
