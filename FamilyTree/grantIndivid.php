<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  grantIndivid.php													*
 *																		*
 *  Display a web page to grant the authority to update an individual,	*
 *  his/her spouses, his/her descendants, and his/her ancestors.		*
 *																		*
 *  Parameters passed by method=POST:									*
 *		idir			unique numeric key of the instance of			*
 *						Person for which the grant is to be given		*
 *																		*
 *  History:															*
 *		2010/11/08		created											*
 *		2010/12/09		add link to help page							*
 *						improve separation of PHP and HTML				*
 *						Exclude self from list of users displayed		*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/08/24		do not permit granting access to current user	*
 *		2012/01/13		change class names								*
 *		2012/02/26		sort user names in selection list				*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						correct <th class="labelsmall"> to				*
 *						class="labelSmall"								*
 *		2013/05/29		initialize parameter to legacyIndex.php			*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/08		replace table with CSS for layout				*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/01		use extended getName from LegacyIndiv			*
 *		2015/01/18		script may now be invoked using method=get		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		use User::getUsers instead of SQL query			*
 *		2016/02/06		use showTrace									*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/21		use RecordSet to get set of users for select	*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the the unique numeric key of the individual
    if (array_key_exists('idir', $_POST))
    {		// standardized keyword
		$idir		= $_POST['idir'];
    }		// standardized keyword
    else
    if (array_key_exists('idir', $_GET))
    {		// standardized keyword
		$idir		= $_GET['idir'];
    }		// standardized keyword
    else
    {		// missing parameter
		$idir		= '';
    }		// missing parameter

    $nameuri	= '';

    // note that record 0 in tblIR contains only the next available value
    // of IDIR
    if ((strlen($idir) > 0) &&
		($idir != 0))
    {		// get the requested individual
		$person		= new Person(array('idir' => $idir));

		$isOwner	= canUser('edit') && $person->isOwner();
		 
		$name		= $person->getName(Person::NAME_INCLUDE_DATES);
		$given		= $person->getGivenName();
		if (strlen($given) > 2)
		    $givenPre	= substr($given, 0, 2);
		else
		    $givenPre	= $given;
		$surname	= $person->getSurname();
		$nameuri	= rawurlencode($surname . ', ' . $givenPre);
		if (strlen($surname) == 0)
		    $prefix	= '';
		else
		if (substr($surname,0,2) == 'Mc')
		    $prefix	= 'Mc';
		else
		    $prefix	= substr($surname,0,1);
		if ($isOwner)
		{		// OK
		    $title		= "Grant Access to $name";
		    $getParms		= array('username' => '!' . $userid);
		    $users		= new RecordSet('Users', $getParms);
		}		// OK
		else
		    $title		= "Access Denied to $name";
    }		// get the requested individual
    else
    {		// invalid input
		$title		= "Invalid Value of idir=$idir";
		$person		= null;
		$surname	= '';
		$isOwner	= false;
    }		// invalid input

    $links	= array(
				'/genealogy.php'		=> 'Genealogy',
				'/genCanada.html'		=> 'Canada',
				'/Canada/genProvince.php?Domain=CAON'
									=> 'Ontario',
				'/FamilyTree/Services.php'	=> 'Services',
				"/FamilyTree/nominalIndex.php?name=$nameuri"
									=> 'Nominal Index');
    if (strlen($surname) > 0)
    {
		$links["Surnames.php?initial=$prefix"] =
							"Surnames Starting with '$prefix'";
		$links["Names.php?Surname=$surname"]  =
							"Surname '$surname'";
    }		// surname present

    htmlHeader($title,
		       array('/jscripts/js20/http.js',
				     '/jscripts/CommonForm.js',
				     '/jscripts/util.js',
				     'grantIndivid.js'),
		       true);
?>
<body>
<?php
    pageTop($links);
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="grantIndividHelpen.html" target="help">? Help</a>
      </span>
      <?php print $title; ?>
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
    }		// error message
 
    if ($isOwner)
    {			// user is authorized to edit this record
		if ($person)
		{		// individual found
?>
    <form method="post" action="grantUser.php" name="grantForm">
    <input type="hidden" name="idir" value="<?php print $idir; ?>">
      <div class="row">
		<label class="column1" for="User">
		    Select user to grant access to:
		</label>
		<select name="User" id="User" size="5" class="white left">
<?php
		foreach($users as $user)
		{
		    $id		= $user->get('id');
		    $name	= $user->get('username');
		    if ($name != $userid)
		    {		// not current user
?>
				<option value="<?php print $name; ?>"><?php print $name;?>
				</option>
<?php
		    }		// not current user
		}		// loop retrieving users
?>
		    </select>
      </div>
    <p>
		<button type="submit" name="Submit">Grant Access</button>
    </p>
    </form>
<?php
		}		// individual found
    }		// current user is an owner of record
    else
    {		// current user does not own record
?>
<p class="message">
    You are not authorized to update this individual.
</p>
<?php
    }		// current user does not own record
?>
</div>	<!-- end of <div id="body"> -->
<?php
    pageBot($title . ": IDIR=$idir");
?>
<div class="balloon" id="HelpUser">
<p>This list permits you to identify the other user to whom you wish
to grant permission to view the private data and update the current 
individual and that individual's
ancestors and descendants.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>
Clicking on this button grants access to the individual and his/her
ancestors and descendants to the indicated user.
</p>
</div>
</body>
</html>
