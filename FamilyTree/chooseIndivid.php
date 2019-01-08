<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  chooseIndivid.php													*
 *																		*
 *  Display a web page to select a specific existing individual			*
 *  from the Legacy table of individuals.								*
 *																		*
 *  URI Parameters (passed by method="GET"):							*
 *																		*
 *		name			if present supplies the default initial name	*
 *						for the selection in the form					*
 *						"surname, given names"							*
 *		idir			if present, the selected individual is made the	*
 *						initial default selection						*
 *		parentsIdmr		if present this is a request to select an		*
 *						existing individual to add as a child onto the	*
 *						indicated family record.						*
 *						Handled by both PHP and Javascript				*
 *		callidir		the name of a form in the invoking page that	*
 *						has a javascript method "callidir(idir)" that	*
 *						can be called by this script to pass the IDIR	*
 *						of the chosen individual to the invoking page.	*
 *						Handled by Javascript							*
 *		id				name of an element in the invoking page with a	*
 *						method setNew defined.							*
 *						Handled by Javascript							*
 *		birthyear		approximate birth year							*
 *		range			range of birth years							*
 *		birthmin		lowest birth year								*
 *		birthmax		highest birth year								*
 *		treename		subdivision of database to search				*
 *																		*
 *  History:															*
 *		2010/08/29		Created											*
 *		2010/09/05		Only insert comma in initial name if given name	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2010/12/20		handle exception from new LegacyIndiv			*
 *		2010/12/23		support name= parameter							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/02/23		support for setidir etc. removed in favor of	*
 *						setNew callback in invoker						*
 *		2011/12/30		support field specific help						*
 *						support page help 								*
 *						pop up loading indicator while waiting for		*
 *						a response from the server.						*
 *		2012/01/13		change class names								*
 *		2013/01/20		correctly handle names containing special		*
 *						characters										*
 *		2013/01/28		if invoked with a specific individual include	*
 *						only individuals matching the gender of that	*
 *						individual but excluding that individual		*
 *		2013/03/05		load initial selection directly,				*
 *						not through script								*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/08/01		remove pageTop and pageBot because this is a	*
 *						popup dialog									*
 *		2013/08/15		use name of individual selected by IDIR to		*
 *						initialize the form								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/08		replace table layout with CSS layout			*
 *						add support for birth year range limit			*
 *		2013/12/31		add for attribute to <label> tags				*
 *						correct class for input fields					*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/08/08		add support for popups							*
 *		2014/09/12		improve search for wife's name					*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/02/16		display appropriate text in button based		*
 *						upon selection									*
 *		2015/03/24		birthmax did not accept years in 1700s			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/23		add support for treename						*
 *						broaden valid birth date range					*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/common.inc';

    // default values
    $idir	= 0;
    $idmr	= '';
    $family	= null;
    $name	= '';		// explicit initial position in list
    $gender	= '';
    $birthmin	= '';
    $birthmax	= '';
    $birthyear	= null;
    $treename	= '';
    $range	= 1;

    // check input parameters
    foreach($_GET as $key => $value)
    {			// loop through all parameters
		switch($key)
		{		// act on specific input key
		    case 'name':
		    {		// initial name specified as surname, givenname
				$name		= $value;
				break;
		    }		// initial name specified as surname, givenname

		    case 'parentsIdmr':
		    {		// family to add a child to
				if (strlen($value) > 0)
				    if (ctype_digit($value))
				    {
						$idmr		= $value;
						$family		= new Family(array('idmr' => $idmr));
						if (!$family->isExisting())
						    $msg	.= "No database record for parentsIdmr=$idmr. ";
				    }
				    else
						$msg	.= "Invalid value of parentsIdmr=$value. ";
				break;
		    }		// family to add a child to

		    case 'idir':
		    {		// initial individual specified by IDIR
				if (strlen($value) > 0) 
				{	// value present
				    if (ctype_digit($value))
				    {	// syntactically valid
						$idir		= $value;
						$person		= new Person(array('idir' => $idir));
						if ($person->isExisting())
						{
						    $surname	= $person->getSurname();
						    $givenname	= $person->getGivenName();

						    if (strlen($surname) <= 1)
						    {		// no surname, only wife's given
							$lgiven	= strlen($givenname) - 6;
							if ($lgiven < 8)
							    $lgiven	= 8;
						    	$name	= ", " . 
								  substr($givenname, 0, $lgiven);
						    }		// no surname, only wife's given
						    else
						    if (substr($givenname, 0, 4) == 'Mary')
						    {		// names starting with 'Ma' too common
							$name	= $surname . ", Mary";
						    }		// names starting with 'Ma' too common
						    else
						    if (strlen($givenname) > 2)
							$name	= $surname . ", " .
									substr($givenname, 0, 2);
						    else
							$name	= $surname . ", " . $givenname;
						    $gender	= $person->getGender();
						    if ($gender == Person::MALE)
							$gender	= 'M';
						    else
						    if ($gender == Person::FEMALE)
							$gender	= 'F';
						}	// existing
						else
						{	// error creating individual
						    $msg	.= "No record for idir=" . $idir . ": " .
								   $e->getMessage();
						}	// error creating individual
				    }	// syntactically valid
				    else
						$msg	.= "Invalid value of idir=$value. ";
				}	// value present
				break;
		    }		// default individual specified

		    case 'birthyear':
		    {		// birth year specified
				if (strlen($value) > 0 && preg_match('#\d{4}#', $value) != 1)
				    $msg	.= "Birth year=$value invalid. ";
				else
				    $birthyear	= intval($value);
				break;
		    }		// birth year specified

		    case 'birthmin':
		    {		// minimum birth year specified
				if (strlen($value) > 0 && preg_match('#\d{4}#', $value) != 1)
				    $msg	.= "Minimum Birth Year=$value invalid. ";
				else
				    $birthmin	= $value;
				break;
		    }		// minimum birth year specified

		    case 'birthmax':
		    {		// maximum birth year specified
				if (preg_match('#\d{4}#', $value) != 1)
				    $msg	.= "Maximum Birth year=$value invalid. ";
				else
				    $birthmax	= $value;
				break;
		    }		// maximum birth year specified

		    case 'range':
		    {		// range of birth years specified
				if (preg_match('#\d{1,2}#', $value) != 1)
				    $msg	.= "Birth year range=$value invalid. ";
				else
				    $range	= intval($value);
				break;
		    }		// range of birth years specified

		    case 'treename':
		    {		// subdivision of database
				$treename	= $value;
				break;
		    }		// subdivision of database
		}		// act on specific input key
    }			// loop through all parameters

    // if birth year is specified and explicit birth range not
    // supplied then calculate the explicit birth range from the
    // estimated birth year and the range
    if (strlen($birthmin) == 0 && !is_null($birthyear))
		$birthmin	= $birthyear - $range;
    if (strlen($birthmax) == 0 && !is_null($birthyear))
		$birthmax	= $birthyear + $range;
    if (strlen($birthmin) != 0)
    {
		if (strlen($birthmax) == 0)
		    $birthmax	= $birthmin + 1;
		else
		if ($birthmax < $birthmin)
		    $msg	.= "Maximum birth year $birthmax less than minimum birth year $birthmin. ";
    }
    else
    if (!is_null($family))
    {			// get range from parents birth years
		$husbbirthsd= $family->get('husbbirthsd');
		$wifebirthsd= $family->get('wifebirthsd');

		if ($husbbirthsd != 0 && $husbbirthsd != -99999999)
		{	// have father's birth date
		    $birthmin	= floor($husbbirthsd/10000)+15;
		    $birthmax	= floor($husbbirthsd/10000)+65;
		}	// have father's birth date
		else
		if ($wifebirthsd != 0 && $wifebirthsd != -99999999)
		{	// have mother's birth date
		    $birthmin	= floor($wifebirthsd/10000)+15;
		    $birthmax	= floor($wifebirthsd/10000)+55;
		}	// have mother's birth date
		else
		{
		    $birthmin	= 1750;
		    $birthmax	= 1900;
		}
    }			// get range from parents birth years

    htmlHeader('Choose Existing Person',
				array(  '/jscripts/js20/http.js',
						'/jscripts/util.js',
						'chooseIndivid.js'),
				true);
?>
<body>
  <div class="body">
    <h1>Choose Existing Person
		<span class="right">
		    <a href="chooseIndividHelpen.html">Help?</a>
		</span>
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
?>
  <form name="indForm" action="getIndivNamesXml.php" method="get">
      <div class="row" id="searchRow">
		<label class="column1" for="Name">Name:</label>
		  <input type="text" name="Name" id="Name" size="64" class="white left"
				value="<?php print str_replace('"', '&quot;', $name); ?>">
		  <input type="hidden" name="Sex" id="Sex"
				value="<?php print $gender; ?>">
		  <input type="hidden" name="IDIR" id="IDIR"
				value="<?php print $idir; ?>">
		  <input type="hidden" name="parentsIdmr" id="parentsIdmr" 
				value="<?php print $idmr; ?>">
		  <input type="hidden" name="treename" id="treename"
				value="<?php print str_replace('"', '&quot;', $treename); ?>">
		<div style="clear: both;"></div>
      </div>
      <div class="row" id="birthYearRow">
		<label class="column1" for="birthmin">Birth Year Range:</label>
		<input class="white rightnc" size="4" name="birthmin" id="birthmin"
				value="<?php print $birthmin; ?>">
		<span class="label" for="birthmax">to</span>
		<input class="white rightnc" size="4" name="birthmax" id="birthmax"
				value="<?php print $birthmax; ?>">
		<div style="clear: both;"></div>
      </div>
      <div class="Row" id="selectListRow">
		<label class="column1" for="individ">Select:</label>
		  <select name="individ" id="individ" size="10" class="white left">
		    <option value="0">Choose a Person</option>
		  </select>
		<div style="clear: both;"></div>
      </div>
      <div class="Row" id="buttonRow">
		  <button type="button" id="select">
		    Cancel
		  </button>
		<div style="clear: both;"></div>
      </div>
  </form>
</div>
<?php
    dialogBot();
?>
<div id="templates" class="hidden">
  <!-- the following two templates exist to permit internationalization
		of the button text -->
  <button type="button" id="selectSelectTemplate">
    Select
  </button>
  <button type="button" id="selectCancelTemplate">
    Cancel
  </button>
</div> <!-- id="templates" -->
<div id="HelpName" class="balloon">
<p>Enter the partial name of the individual you wish to choose.  Enter the
surname first, then a comma followed by the given name.  As you pause in
typing the name the selection list is updated from the database.
</div>
<div id="Helpindivid" class="balloon">
<p>This is a selection list of individuals presented in alphabetical order
starting with the first individual whose surname is equal to the supplied
surname or sorts immediately after it, and if the surname is equal
the first individual whose given name is equal to or sorts immediately
after the supplied given name.  Click on an entry with the mouse to choose it.
</div>
<div id="Helpselect" class="balloon">
<p>Once you have chosen the desired individual from the list, click on this
button, or press enter, to select the individual.
</div>
<div id="Helpbirthmin" class="balloon">
<p>This field specifies the lowest birth year to use in matching individuals.
If this field is blank there is no lower limit.
</div>
<div id="Helpbirthmax" class="balloon">
<p>This field specifies the highest birth year to use in matching individuals.
If this field is blank there is no upper limit.
</div>
<div id="loading" class="popup">
Loading...
</div>
</body>
</html>
