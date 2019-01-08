<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteIndivid.php													*
 *																		*
 *  Display a web page to delete a particular record					*
 *  from the Legacy table of individuals.								*
 *																		*
 *  URI Parameters:														*
 *		idir			unique numeric key of the instance of			*
 *						Person to be deleted.  If this is omitted		*
 *						or zero then no action is taken and an error	*
 *						message is displayed.							*
 *																		*
 *  History:															*
 *		2010/12/20		created											*
 *		2010/12/23		Move database update to LegacyIndiv class		*
 *						definition										*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2012/01/13		change class names								*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						reference nominalIndex.php not legacyIndex.html	*
 *						remove unused help popups						*
 *						warn on undefined parameter						*
 *						enable debug by parameter						*
 *						add link to person page to header/footer		*
 *						for failed										*
 *						include all owners in the contact Author email	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/10		remove tables									*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/11/29		do not reinitialize global variables set by		*
 *						common.inc										*
 *		2015/01/01		use extended getName from LegacyIndiv			*
 *		2015/05/12		do not escape title								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/common.inc';

    // global variables
    $idir		= null;		// key of record to delete		
    $surname		= '';		// surname from record

    // check parameters
    foreach ($_GET as $key => $value)
    {		// loop through all parameters
	switch(strtolower($key))
	{	// act on specific parameter	
	    case 'id':
	    case 'idir':
	    {		// key of instance of Person
		if (strlen($value) > 0 && ctype_digit($value))
		{	// integer value
		    $idir	= intval($value);
		    // note that record 0 in tblIR contains only the next
		    // available value of IDIR
		    if ($idir < 1)
			$msg	.= "IDIR must be a positive integer. ";
		}	// integer value
		else
		    $msg	.= "IDIR value '$value' invalid. ";
		break;
	    }		// key of instance of Person

	    case 'debug':
	    {		// handled by common.inc
		break;
	    }		// activate debugging

	    default:
	    {
		$warn	.= "Unexpected parameter $key='$value'. ";
		break;
	    }
	}	// act on specific parameter	
    }		// loop through all parameters

    // parameter to nominalIndex.php

    $nameuri	= '';
    if (!is_null($idir) && $idir > 0)
    {			// get the requested person
	$person		= new Person(array('idir' => $idir));
	if ($person->isExisting())
	{			// person is defined
	    if (canUser('edit'))
	    {		// not signed on as a contributor
		if (!$person->isOwner())
		{		// user is not authorized to edit this record
		    $msg	.= 'Current user is not authorized to delete this person.  Contact the administrator or an existing owner of this family for permission.';
		}		// user is not authorized to edit this record
	    }		// not signed on as a contributor
	    else
		$msg	.= 'Please sign on as a contributor to the site. ';
	     
	    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
	    $given		= $person->getGivenName();
	    if (strlen($given) > 2)
		$givenpre	= substr($given, 0, 2);
	    else
		$givenpre	= $given;
	    $surname		= $person->getSurname();
	    $nameuri		= rawurlencode($surname . ', ' . $givenpre);
	    $title		= "Delete $name";

	    // check that person is not related to anyone in the tree
	    $parents		= $person->getParents();
	    $families		= $person->getFamilies();
	    if (count($parents) > 0 ||
		count($families) > 0)
	    {		// person is not connected to any others
		$msg	.= "$given $surname is a member of a family. 
	Detach from parents, spouse, and children before trying to delete. ";
	    }		// person is not connected to any others

	    if (strlen($msg) == 0)
	    {		// OK to delete
		$counts	= $person->delete(false);
		$person	= null;	// release
	    }		// OK to delete
      }			// Person already exists
      else
      {			// handle record not found exception
	    $title	= "Invalid IDIR value $idir";
	    $msg	.= "Invalid IDIR value $idir";
	    $surname	= '';
      }			// handle record not found exception
    }		// get the requested person
    else
    {		// error
	$title		= "Invalid or missing IDIR value";
	$msg		= "Invalid or missing IDIR value";
	$idir		= 0;
    }		// error

    $links	= array('/genealogy.php'	=> 'Genealogy',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
						=> 'Ontario',
			'/FamilyTree/Services.php'
						=> 'Services',
			"/FamilyTree/nominalIndex.php?name=$nameuri"
						=> 'Nominal Index');
    if (strlen($surname) > 0)
    {
	if (substr($surname,0,2) == 'Mc')
	    $prefix	= 'Mc';
	else
	    $prefix	= substr($surname,0,1);
	$links["Surnames.php?initial=$prefix"] =
				"Surnames Starting with '$prefix'";
	$links["Names.php?Surname=$surname"] =
				"Surname '$surname'";
	if (strlen($msg) > 0)
	    $links["Person.php?idir=$idir"] =
				"$given $surname";
    }		// have a surname

    htmlHeader($title,
	       array('/jscripts/CommonForm.js',
			'/jscripts/js20/http.js',
		     '/jscripts/util.js',
		     '/jscripts/default.js'));
?>
<body>
<?php
    pageTop($links);
?>
  <div class="body">
    <h1>
      <span class="right">
	<a href="deleteIndividHelpen.html" target="help">? Help</a>
      </span>
	<?php print $title; ?> 
    </h1>
<?php
    showTrace();
 
    if (strlen($msg) > 0)
    {		// error message
?>
  <p class="message">
    <?php print $msg; ?> 
  </p>
<?php
    }		// error message
    else
    {		// successful
	$citCount	= $counts['citCount'];
	$eventCount	= $counts['eventCount'];
	$personCount	= $counts['indivCount'];
?>
<p class="label">
    Deleted <?php if ($citCount == 0) print "no"; else print $citCount; ?>
	citation<?php if ($citCount != 1) print 's'; ?>.
    Deleted <?php if ($eventCount == 0) print "no"; else print $eventCount; ?>
	event<?php if ($eventCount != 1) print 's'; ?>.
    Deleted <?php if ($personCount == 0) print "no"; else print $personCount; ?>
	person<?php if ($personCount != 1) print 's'; ?>.
</p>
<?php
    }		// successful
?>
  </div>	<!-- end of <div id="body"> -->
<?php
    pageBot($title, $idir, 'tblIR');
?>
</body>
</html>
