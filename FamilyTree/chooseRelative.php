<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  chooseRelative.php													*
 *																		*
 *  Display a web page to select a specific existing individual			*
 *  from the Legacy table of individuals.								*
 *																		*
 *  URI Parameters (passed by method="GET"):							*
 *																		*
 *		idir			mandatory, the selected individual is made the	*
 *						initial default selection						*
 *																		*
 *  History:															*
 *		2011/01/16		Created											*
 *		2012/01/07		match functionality of legacyIndex.html			*
 *		2012/01/13		change class names								*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						correct <th class="labelsmall"> to				*
 *						class="labelSmall"								*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *		2013/08/01		remove pageTop and pageBot because this is a	*
 *						popup dialog									*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/10		remove tables									*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/01		use extended LegacyIndiv::getName to get name	*
 *						with birth and death dates						*
 *		2015/06/20		leave a little extra space above selection list	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/05/24		missing dialogBot								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $title	= 'Choose Relative';

    if (array_key_exists('idir', $_GET))
    {
		$idir	= $_GET['idir'];
		try
		{
		    $person	= new Person(array('idir' => $_GET['idir']));

		    // check if current user is an owner of the record and therefore
		    // permitted to see private information and edit the record
		    $isOwner	= $person->isOwner();

		    // get information for constructing title and
		    // breadcrumbs
		    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
		    $given	= $person->getGivenName();
		    $surname	= $person->getSurname();
		    $nameuri	= rawurlencode($surname . ', ' . $given);
		    if (strlen($surname) == 0)
				$prefix	= '';
		    else
		    if (substr($surname,0,2) == 'Mc')
				$prefix	= 'Mc';
		    else
				$prefix	= substr($surname,0,1);

		    $title		= "Choose Relative of " . $name;

		}	// try
		catch(Exception $e)
		{	// error creating individual
		    $msg	.= 'Invalid value of default IDIR. ' . $e->getMessage();
		    $name	= '';
		}	// error creating individual
    }		// default individual specified
    else
		$msg	.= 'Missing mandatory parameter idir. ';

    htmlHeader($title,
				array(  '/jscripts/js20/http.js',
						'/jscripts/util.js',
						'chooseRelative.js'),
				true);
?>
<body>
  <div class="body">
    <h1>
      <span class="right">
		<a href="chooseRelativeHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
      <div style="clear: both;"></div>
    </h1>
<?php
		if (strlen($msg) > 0)
		{
?>
      <p class="message">
		<?php print $msg; ?> 
      </p>
<?php
		}
		else
		{	// no error messages
?>
  <form name="indForm" action="relationshipCalculator.php" method="get">
      <div class="row">
		<label class="column1" for="Name">Name:
		</label>
		<input type="text" name="Name" id="Name" size="56" class="white left"
				value="<?php print $name; ?>">
		<input type="hidden" name="idir1"
				value="<?php print $idir; ?>">
		<div style="clear: both;"></div>
      </div>
      <p><!-- leave a little extra space --></p>
      <div class="row">
		<label class="column1" for="idir2">
		  Select:
		</label>
		<select name="idir2" id="idir2" size="10" class="white left">
		    <option value="0">Choose an individual</option>
		</select>
		<div style="clear: both;"></div>
      </div>
  </form>
<?php
		}		// no error messages
?>
</div>	<!-- end of <div id="body"> -->
<?php
    dialogBot();
?>
<div class="balloon" id="HelpName">
Type a name, starting with the surname, a comma, and the given names.  As you 
type the text you have entered is used to select the individuals to list,
starting with the first individual whose name begins with the text
you have entered.
</div>
<div class="balloon" id="Helpidir2">
This selection list displays a list of individuals in alphabetical order,
by surname, and then by given name, that starts with the individual
in the database who is the first to come after the text entered in the
name field.  Click on an individual in this list to display the information
known about that individual.
</div>
<div class="balloon" id="HelpincMarried">
If this option is checked then the list of individuals will include
married names as well as birth names.
</div>
<div class="popup" id="loading">
Loading...
</body>
</html>
