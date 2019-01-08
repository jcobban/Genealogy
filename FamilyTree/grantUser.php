<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  grantUser.php														*
 *																		*
 *  Display a web page to reporting the results of a grant of authority *
 *  to update an individual, his/her spouses, his/her descendants,		*
 *  and his/her ancestors.												*
 *																		*
 *  Parameters passed by method=POST:									*
 *		idir			unique numeric key of the instance of			*
 *						Person for which the grant is to be given		*
 *		User			unique identifier of the user to whom access	*
 *						is granted										*
 *																		*
 *  History:															*
 *		2010/11/08		created											*
 *		2010/12/09		add link to help page							*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *		2010/12/20		handle exception from new LegacyIndiv			*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/09/26		order user names								*
 *		2012/01/13		change class names								*
 *		2013/06/01		change nominalIndex.html to legacyIndex.php		*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/19		replace table used for layout with CSS			*
 *		2014/08/15		use LegacyIndiv::getFamilies					*
 *						use LegacyFamily:getChildren					*
 *						use LegacyIndiv::getParents						*
 *		2014/09/26		try to update parents and children even if		*
 *						current entry is already granted in case the	*
 *						children or parents were added by another		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *						use Record method addOwner to add ownership		*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/01		use extended getName from LegacyIndiv			*
 *		2015/03/20		use getName fom LegacyIndiv to get names of		*
 *						all granted individuals							*
 *						use great in hierarchies						*
 *						identify spouse for husband and wife entries	*
 *						make each name a color-coded hyperlink			*
 *		2015/04/01		hyperlink root individual						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/01/28		Record::addOwner is ordinary method				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  grantMarriages														*
 *																		*
 *  Extend the grant to spouses and children of the individual.				*
 *																		*
 *  Input:																*
 *		$person																*
 *		$rank																*
 ************************************************************************/
function grantMarriages($person, $rank)
{
    global $User;

    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
    $families	= $person->getFamilies();
    foreach($families as $i => $family)
    {
		if ($person->getGender() == Person::FEMALE)
		{		// female
		    $spsid	= $family->get('idirhusb');
		    $spouse	= $family->getHusband();
		    $role	= 'husband';
		    $spsclass	= 'male';
		}		// female
		else
		{		// male
		    $spsid	= $family->get('idirwife');
		    $spouse	= $family->getWife();
		    $role	= 'wife';
		    $spsclass	= 'female';
		}		// male

		// ensure that access to spouses is permitted
		if ($spsid > 0)
		{		// a spouse is defined
		    $spsName	= $spouse->getName(Person::NAME_INCLUDE_DATES);
		    $done	= $spouse->addOwner($User);
		    if ($done)
		    {		// authority granted
?>
<p>Granting access to <?php print $role; ?> of <?php print $name; ?>
		idir=<?php print $spsid; ?> 
		<a href="Person.php?idir=<?php print $spsid; ?>"
				class="<?php print $spsclass; ?>">
				<?php print $spsName; ?>
		</a>
		to <?php print $User; ?>.
<?php
		    }		// authority granted
		    else
		    {		// previously granted
?>
<p>Previously granted access to <?php print $role; ?> of <?php print $name; ?>
		idir=<?php print $spsid; ?> 
		<a href="Person.php?idir=<?php print $spsid; ?>"
				class="<?php print $spsclass; ?>">
				<?php print $spsName; ?>
		</a>
		to <?php print $User; ?>.
<?php
		    }		// previously granted
		} 		// a spouse is defined

		// check children regardless, since a child may have been
		// added by another user and children may be defined even
		// where only a single spouse is known
		$children	= $family->getChildren();
		foreach($children as $i => $child)
		{		// loop through all child records
		    // display information about child
		    $cid		= $child->getIdir();
		    try
		    {
				$child	= new Person(array('idir' => $cid));
				$cName	= $child->getName(Person::NAME_INCLUDE_DATES);;
				if ($child->getGender() == Person::FEMALE)
				{
				    $role	= 'daughter';
				    $cclass	= 'female';
				}
				else
				if ($child->getGender() == Person::MALE)
				{
				    $role	= 'son';
				    $cclass	= 'male';
				}
				else
				{
				    $role	= 'child';
				    $cclass	= 'unknown';
				}
				$done		= $child->addOwner($User);

				if ($done)
				{	// authority granted
?>
<p>Granting access to <?php print $rank . $role; ?>
    idir=<?php print $cid; ?> 
		<a href="Person.php?idir=<?php print $cid; ?>"
				class="<?php print $cclass; ?>">
				<?php print $cName; ?>
		</a>
    to <?php print $User; ?>.
<?php
				}	// authority granted
				else
				{	// previously granted
?>
<p>Previously granted access to <?php print $rank . $role; ?>
    idir=<?php print $cid; ?> 
		<a href="Person.php?idir=<?php print $cid; ?>"
				class="<?php print $cclass; ?>">
				<?php print $cName; ?>
		</a>
		to <?php print $User; ?>.
<?php
				}	// previously granted

				// recurse down the list of descendants including
				// their spouses and children
				if (strlen($rank) == 0)
				    $trank	= 'grand-';
				else
				    $trank	= 'great-' . $rank;
				grantMarriages($child, $trank);
		    }		// try
		    catch(Exception $e)
		    {		// error creating child's instance of Person
						// don't care
		    }		// error creating child's instance of Person
		}		// loop through all child records
    }			// loop through all marriages of the individual
}		// grantMarriages

/************************************************************************
 *  grantParents														*
 *																		*
 *  Extend the grant to parents of the individual.						*
 ************************************************************************/
function grantParents($person, $rank)
{
    global $User;

    $parents	= $person->getParents();
    foreach($parents as $idcr => $family)
    {
		// check for father
		$fidir		= $family->get('idirhusb');
		if ($fidir > 0)
		{		// has a father
		    $father	= $family->getHusband();
		    $fName	= $father->getName(Person::NAME_INCLUDE_DATES);;

		    $done	= $father->addOwner($User);
		    if ($done)
		    {		// access granted
?>
<p>Granting access to <?php print $rank; ?>father
		idir=<?php print $fidir; ?>
		<a href="Person.php?idir=<?php print $fidir; ?>" class="male">
				<?php print $fName; ?>
		</a>
		to <?php print $User; ?>.
<?php

				// recursively grant access to parents of father
				try
				{
				    if (strlen($rank) == 0)
						$trank	= 'grand-';
				    else
						$trank	= 'great-' . $rank;
				    grantParents(new Person(array('idir' => $fidir)),
							 $trank);
				}		// try
				catch(Exception $e)
				{		// throw from Person
				    // stop recursion
				}		// throw from Person
		    }		// access granted
		    else
		    {		// previously granted
?>
<p>Previously granted access to <?php print $rank; ?>father
		idir=<?php print $fidir; ?> 
		<a href="Person.php?idir=<?php print $fidir; ?>" class="male">
				<?php print $fName; ?>
		</a>
		to <?php print $User; ?>.
<?php
		    }		// previously granted
		}		// has a fatherhttp://www.jamescobban.net

		// check for mother
		$midir		= $family->get('idirwife');
		if ($midir > 0)
		{		// has a mother
		    $mother	= $family->getWife();
		    $mName	= $mother->getName(Person::NAME_INCLUDE_DATES);;
		    $done	= $mother->addOwner($User);
		    if ($done)
		    {		// access granted
?>
<p>Granting access to <?php print $rank; ?>mother
		idir=<?php print $midir; ?>
		<a href="Person.php?idir=<?php print $midir; ?>" class="male">
				<?php print $mName; ?>
		</a>
		to <?php print $User; ?>.
<?php

				// recursively grant access to parents of mother
				try
				{
				    if (strlen($rank) == 0)
						$trank	= 'grand-';
				    else
						$trank	= 'great-' . $rank;
				    grantParents(new Person(array('idir' => $midir)),
							 $trank);
				}		// try
				catch(Exception $e)
				{		// throw from Person
				    // stop recursion
				}		// throw from Person
		    }		// access granted
		    else
		    {		// previously granted
?>
<p>Previously granted access to <?php print $rank; ?>mother
		idir=<?php print $midir; ?>
		<a href="Person.php?idir=<?php print $midir; ?>" class="male">
				<?php print $mName; ?>
		</a>
		to <?php print $User; ?>.
<?php
		    }		// previously granted
		}		// has a mother
    }		// loop through all sets of parents of the individual
}		// grantParents

    // get the the unique numeric key of the individual
    if (array_key_exists('idir', $_POST))
    {		// standardized keyword
		$idir		= $_POST['idir'];
    }		// standardized keyword
    else
    {		// missing parameter
		$idir		= '';
    }		// missing parameter
   
    // get the the unique identifier of the user
    if (array_key_exists('User', $_POST))
    {		// standardized keyword
		$User		= $_POST['User'];
    }		// standardized keyword
    else
    {		// missing parameter
		$User		= '';
    }		// missing parameter

    // note that record 0 in tblIR contains only the next available value
    // of IDIR
    if ((strlen($idir) > 0) &&
		($idir != 0))
    {		// get the requested individual
		try
		{
		    $person	= new Person(array('idir' => $idir));

		    $isOwner	= canUser('edit') && 
						  $person->isOwner();
		     
		    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
		    $given	= $person->getGivenName();
		    $surname	= $person->getSurname();
		    if (strlen($surname) == 0)
				$prefix	= '';
		    else
		    if (substr($surname,0,2) == 'Mc')
				$prefix	= 'Mc';
		    else
				$prefix	= substr($surname,0,1);
		    if ($isOwner)
		    {		// OK
				$title	= "Grant Access to $name";
		    }		// OK
		    else
				$title	= "Access Denied to $name";
		}		// try
		catch(Exception $e)
		{		// error in creation of Person
		    // don't care
		}		// error in creation of Person
    }		// get the requested individual
    else
    {		// invalid input
		$title		= "Invalid Value of idir=$idir";
		$person		= null;
		$isOwner	= false;
    }		// invalid input

    $links	= array('/genealogy.php'	=> 'Genealogy',
						'/genCountry.php?cc=CA'	=> 'Canada',
						'/Canada/genProvince.php?Domain=CAON'
									=> 'Ontario',
						'/FamilyTree/Services.php'
									=> 'Family Tree Services',
						'/FamilyTree/nominalIndex.php'	
									=> 'Surname Index');
    if (strlen($surname) > 0)
    {
		$links["Surnames.php?initial=$prefix"]	=
							"Surnames Starting with '$prefix'";
		$links["Names.php?Surname=$surname'"]	=
							"Surname '$surname'";
		$links["Person.php?id=$idir"]		=
							"$given $surname";
    }		// existing individual

    htmlHeader($title,
		       array('/jscripts/js20/http.js',
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
		<a href="grantUserHelpen.html" target="help">? Help</a>
      </span>
      <?php print $title; ?>
    </h1>
<?php
    showTrace();
 
    if ($isOwner)
    {			// user is authorized to edit this record
		if ($person)
		{		// individual found
		    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
		    $done	= $person->addOwner($User);
		    if ($person->getGender() == Person::FEMALE)
				$class	= 'female';
		    else
		    if ($person->getGender() == Person::MALE)
				$class	= 'male';
		    else
				$class	= 'unknown';
		    if ($done)
		    {		// access granted
?>
    <p>Granting access of idir=<?php print $idir; ?> 
		<a href="Person.php?idir=<?php print $idir; ?>"
				class="<?php print $class; ?>">
				<?php print $name; ?>
		</a>
		    to <?php print $User; ?>.
<?php
		    }		// access granted
		    else
		    {		// previously granted
?>
    <p>Previously granted access of idir=<?php print $idir; ?> 
		<a href="Person.php?idir=<?php print $idir; ?>"
				class="<?php print $class; ?>">
				<?php print $name; ?>
		</a>
		    to <?php print $User; ?>.
<?php
		    }		// previously granted
    
		    // check for children and parents not previously granted
		    grantMarriages($person, '');
		    grantParents($person, '');
		}		// individual found
    }			// current user is an owner of record
    else
    {		// current user does not own record
?>
<p class="message">
    You are not a current owner of this individual and cannot therefore
    grant ownership to another user.
</p>
<?php
    }		// current user does not own record
?>
  </div>
<?php
    pageBot();
?>
</body>
</html>
