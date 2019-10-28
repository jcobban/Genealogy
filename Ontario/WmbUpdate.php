<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbUpdate.php														*
 *																		*
 *  Update an entry in the Wesleyan Methodist baptisms table.			*
 *																		*
 *  History:															*
 *		2016/03/11		created											*
 *		2016/10/13		update birth and christening events when		*
 *						linking to an individual						*
 *		2016/12/13		display next baptism on page after update		*
 *		2017/03/19		use preferred parameters for new Person			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/29		use standard invocation of new Citation			*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

    // expand only if authorized
    if (!canUser('edit'))
    {		// not authorized
		$msg	.= 'You are not authorized to update baptism registrations. ';
    }		// not authorized

    // default values
    $domain		= 'CAON';
    $domainName		= 'Ontario';
    $regYear		= null;
    $idmb		= null;
    $volume		= null;
    $page		= null;
    $idirChanged	= false;

    // get key values from parameters
    if (isset($_POST['IDMB']))
    {
		$value		= $_POST['IDMB'];
		if (ctype_digit($value))
		{
		    $idmb	= intval($value);
		}
		else
		    $msg	.= "IDMB='$value' is not numeric. ";
    }			// IDMB specified

    if (is_null($idmb))
		$msg	.= 'Baptism record number not specified. ';

    if (strlen($msg) == 0)
    {			// no errors
		$baptism	= new MethodistBaptism(array('idmb' => $idmb));
		$idir		= $baptism->get('idir');
		$person		= null;

		// process remaining input parameters
		// the values passed in the parameters override the existing values
		if ($debug)
		    $warn	.= "<p>";
		foreach($_POST as $key => $value)
		{	// loop through all parameters
		    switch(strtolower($key))
		    {	// act on specific keywords
				case 'idmb':
				{	// already handled
				    break;
				}	// already handled

				case 'submit':
				{	// used by javascript 
				    break;
				}	// used by javascript

				case 'idir':
				{
				    $idirChanged	= $idir != $value;
				    if ($idirChanged)
				    {
						$idir		= $value;
						$baptism->set($key, $value);
				    }
				    break;
				}

				case 'date':
				case 'place':
				{
				    $baptism->set('Birth' . $key, $value);
				    break;
				}

				case 'volume':
				{
				    if (ctype_digit($value))
				    {
						$volume		= intval($value);
						$baptism->set('volume', $volume);
				    }
				    else
						$msg	.= "Volume='$value' is not numeric. ";
				    break;
				}

				case 'page':
				{
				    if (ctype_digit($value))
				    {
						$page		= intval($value);
						$baptism->set('page', $page);
				    }
				    else
						$msg	.= "Page='$value' is not numeric. ";
				    break;
				}

				case 'debug':
				{		// handled by common code
				    break;
				}		// handled by common code

				default:
				{
				    $baptism->set($key, $value);
				    break;
				}

		    }	// act on specific keywords
		}	// loop through all parameters
		if ($debug)
		    $warn	.= "<p>";

		// if IDIR was not already set check for the case where the
		// family tree already cites the specific registration
		if ($idir == 0)
		{			// IDIR not set
		    $parms	= array('IDSR'		=> 158,
							'Type'		=> Citation::STYPE_CHRISTEN,
							'SrcDetail'	=> "$idmb",
							'limit'		=> 1);
		    $citations	= new CitationSet($parms,
								  'IDSX');
		    $info	= $citations->getInformation();
		    $count	= $info['count'];
		    $count	= $parms['count'];
		    if ($count > 0)
		    {			// there is already a citation to this
				$citation	= current($citations);
				if ($citation instanceof Record)
				{
				    $idir		= $citation->get('idime');
				    $baptism->set('idir', $idir);
				    $idirChanged	= true;
				} 
				else
				    print_r($citation);
		    }			// there is already a citation to this
		}			// IDIR not set

		// update the baptism record
		$baptism->save(false);

		// support adding citation to family tree
		if ($idirChanged && $idir > 0)
		{			// the associated individual has changed
		    try {
				$person		= new Person(array('idir' => $idir));
				$source		= new Source(array('srcname' =>
							' Wesleyan Methodist Baptisms, Ontario'));
				$idsr		= $source->get('idsr');
				$srcdetail	= 'vol ' . $baptism->get('volume') .
							  ' page ' . $baptism->get('page');

				// create or update the christening event
				$christEvent	= $person->getChristeningEvent(true);
				$christEvent->setDate($baptism->get('BaptismDate'));
				$christEvent->setLocation($baptism->get('BaptismPlace'));
				$citation	= new Citation(array('idsr'	=> $idsr,
									     'srcdetail'=> $srcdetail));
				$christEvent->addCitations($citation);

				// create or update the birth event
				$birthEvent	= $person->getBirthEvent(true);
				if (strlen($birthEvent->getDate()) <= 8)
				    $birthEvent->setDate($baptism->get('BirthDate'));
				if ($birthEvent->getLocation() == '' ||
				    $birthEvent->getLocation() == 'Ontario, Canada')
				    $birthEvent->setLocation($baptism->get('BirthPlace'));
				$citation	= new Citation(array('idsr'	=> $idsr,
									     'srcdetail'=> $srcdetail));
				$birthEvent->addCitations($citation);
		    } catch (Exception $e) {}
		    // add a christening event to the individual
		}			// the associated individual has changed
    }				// no errors

    // Identify next registration to update
    if (strlen($msg) == 0 && strlen($warn) == 0)
    {		// redirect immediately to next registration
		header("Location: WmbDetail.php?Volume=$volume&Page=$page&IDMB=>$idmb"); 
    }		// redirect immediately to next registration
    else
    {		// display page
		// put out standard HTML header
		htmlHeader("Update Wesleyan Methodist Baptism Registration",
				array(	'/jscripts/util.js',
						'WmbUpdate.js'));
?>
<body>
<?php
		pageTop(array(
				'/genealogy.php'	=> 'Genealogy',
				'/genCanada.html'	=> 'Canada',
				"gen$domainName.php"	=> $domainName,
				'WmbQuery.html'		=> 'New Query', 
				"WmbStats.php"		=> 'Status'));
?>
<div class='body'>
    <h1>Update Wesleyan Methodist Baptism Registration</h1>
<?php
    if (strlen($warn) > 0)
    {			// display warning message
?>
    <div class='warning'><?php print $warn; ?></div>
<?php
    }			// display warning message

    if (strlen($msg) == 0)
    {		// action performed
?>
    <p class='label'>
		<a href='WmbQuery.html?IDMB=<?php print $idmb; ?>&Count=20' 
				id='newQuery'>
		Specify next page to update</a>
    </p>

    <p class='label'>
		<a href='WmbDetail.php?IDMB=<?php print $nextIDMB; ?>' 
				id='updNext'>
		    Update Baptism Registration <?php print $nextIDMB; ?>
		</a>
    </p>
<?php
    }		// action performed
    else
    {		// action not performed, display diagnostic message
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }		// action not performed
?>
  </div> <!-- end of <div id='body'> -->
<?php
    pageBot();
?>
</body>
</html>
<?php
    }		// display page
?>
