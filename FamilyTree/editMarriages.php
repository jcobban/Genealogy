<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editMarriages.php													*
 *																		*
 *  Display a web page for editing the families for which a particular 	*
 *  individual has the role of spouse from the Legacy database			*
 *																		*
 *  Parameters (passed by method=get) 									*
 *		idir			unique numeric key of individual as spouse		*
 *		child			unique numeric key of individual as child		*
 *		given			given name of individual in case that			*
 *						information is not already written to the		*
 *						database										*
 *		surname			surname of individual in case that information	*
 *						is not already written to the database			*
 *		idmr			numeric key of specific marriage to initially	*
 *						display											*
 *		treename		name of tree subdivision of database			*
 *		new				parameter to add a new family					*
 *																		*
 *  History: 															*
 * 		2010/08/21		Change to use new page format					*
 *		2010/09/04		Add button to reorder marriages					*
 *						Get birth and death dates into variables		*
 *		2010/10/21		use RecOwners class to validate access			*
 *						add balloon help for buttons					*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/15		eliminate use of obsolete showDate				*
 *		2010/11/27		add parameters given and surname because the	*
 *						user may have modified the name in the			*
 *						invoking editIndivid.php web page but not		*
 *						updated the database record yet.				*
 *		2010/12/04		add link to help panel 							*
 *						improve separation of HTML and JS				*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *						escape special chars in title					*
 *		2010/12/20		handle exception thrown by new LegacyIndiv		*
 *						handle both idir= and id=						*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/03/25		support keyboard shortcuts						*
 *		2011/06/18		merge with editMarriage.php						*
 *		2011/10/01		support database assisted location name			*
 *		2011/11/15		add parameter idmr to initiate editing specific	*
 *						family											*
 *						add buttons to edit Husband or Wife as			*
 *						individuals										*
 *		2011/11/26		support editing married surnames				*
 *		2011/12/21		support additional events						*
 *						display all events in the marriage panel		*
 *						suppress function if user is not authorized		*
 *		2012/01/13		change class names								*
 *						all buttons use id= rather than name= to avoid	*
 *						problems with IE passing them as parameters		*
 *						support updating all fields of LegacyFamily		*
 *						record											*
 *						use $idir as identifier of primary spouse		*
 *		2012/01/23		display loading indicator while waiting for		*
 *						response to changes in a location field			*
 *		2012/02/01		permit idir parameter optional if idmr specified*
 *		2012/02/25		change ids of fields in marriage list to contain*
 *						IDMR instead of row number						*
 *		2012/05/27		specify explicit class on all					*
 *						<input type="text">								*
 *		2012/05/29		identify row of table of children by IDCR in	*
 *						case the same child appears more than once		*
 *		2012/11/17		initialize $family for display of specific		*
 *						marriage										*
 *						display family events from event table on		*
 *						requested marriage								*
 *						change implementation so event type or IDER		*
 *						value is contained in the name of the button,	*
 *						not from a hidden field matching the rownum		*
 *		2012/11/27		always display the marriage details form		*
 *						always filled in dynamically as a result of		*
 *						receiving the response to an AJAX request,		*
 *						rather than sometimes filled in by PHP and some	*
 *						times by javascript.							*
 *						the location of the sealed to spouse event is	*
 *						made a selection list to permit updating.		*
 *		2013/01/26		make children's names and dates editable		*
 *		2013/01/23		add undocumented option to submit request in	*
 *						order to be able to see XML response			*
 *		2013/03/25		add ability to detach just added child			*
 *		2013/05/17		shrink dialog vertically by using				*
 *						<button class="button">							*
 *		2013/05/20		change terminology from Marriage to Family		*
 *						add templates for never married and no children	*
 *						facts											*
 *		2013/05/29		add template for new location warning			*
 *		2013/06/01		LegacyIndiv::getMarriages renamed to getFamilies*
 *		2013/07/03		use explicit classes for husband and wife links	*
 *		2013/08/14		include title and suffix in title of page		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *						rename buttons to choose an existing individual	*
 *						as husband or wife to id="choose..."			*
 *						handle all child rows the same with the fields	*
 *						uniquely identified by the order value of the	*
 *						corresponding LegacyChild records				*
 *		2014/03/19		use CSS rather than tables to layout form		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/06/02		add IDCR parameter back into child table row	*
 *		2014/07/15		add help balloon for Order Events button		*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/02		add prompt to confirm deletion					*
 *		2014/10/12		correct married surnames with quotes (O'Brien)	*
 *		2014/11/14		initialize display of family without requiring	*
 *						AJAX											*
 *		2014/11/16		correct parameter list for new LegacyFamily		*
 *						when adding a new family to an individual		*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/26		response from getFamilies is indexed by idmr	*
 *		2015/02/01		get temple select options from database			*
 *						get event texts from class Event and			*
 *						make them available to Javascript				*
 *		2015/02/19		remove user of deprecated interface to			*
 *						LegacyFamily constructor						*
 *						change remaining debug code to add to $warn		*
 *		2015/02/25		do not access name and birth date of spouses	*
 *						from the family record							*
 *		2015/04/28		add warning dialog that a child is already		*
 *						edited when attempt to edit the child for whom	*
 *						a set of parents is being created or edited		*
 *		2015/05/14		handle exception for bad IDLRMarr value			*
 *		2015/06/20		failed if IDMRPref set in individual to bad		*
 *						family value									*
 *						document action of enter key in child row		*
 *						Make the notes field a rich-text editor.		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		add support for tree division of database		*
 *		2015/08/22		popup dialogs were not defined as <form>s		*
 *		2015/08/23		adding family to new individual gave blank		*
 *						primary spouse									*
 *		2016/02/06		use showTrace									*
 *		2016/02/24		handle child record with invalid IDIR			*
 *		2016/06/30		length of dates in added children slightly		*
 *						shorter than ones loaded at start				*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2018/02/16		remove unnecessary <p> and <br> inserted by		*
 *						tinyMCE from the marriage notes					*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/20      rearrange order of fields to simplify           *
 *		                updateMarriageXml.php                           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters passed to the script
$idir				= null;		// individual as primary spouse in family
$idmr				= null;		// marriage to display
$indiv				= null;		// instance of Person
$family				= null;		// instance of Family
$child				= null;		// IDIR of individual as child in new family
$isowner			= false;	// current user is an owner of the family
$given				= '';		// given name of individual
$surname			= '';		// surname of individual
$treename			= '';		// treename of database division
$prefix				= '';		// initial part of surnames
$birth				= '';		// birth date as string
$death				= '';		// death date as string
$idmrpref			= 0;		// preferred marriage for the individual
$new				= false;
$submit				= false;

foreach($_GET as $key => $value)
{			// loop through all parameters passed to script
	switch(strtolower($key))
	{		// take action on specific parameter
	    case 'id':
	    case 'idir':
	    {		// identify primary spouse
			if (strlen($value) > 0)
			    if (ctype_digit($value))
					$idir		= $value;
			    else
					$msg	.= "Invalid IDIR=$value. ";
			break;
	    }		// identify primary spouse

	    case 'child':
	    {		// identify child of family
			if (strlen($value) > 0)
			    if (ctype_digit($value))
					$child		= $value;
			    else
					$msg	.= "Invalid Child=$value. ";
			break;
	    }		// identify child of family

	    case 'given':
	    {
			$given		= $value;
			break;
	    }		// default given name of individual

	    case 'surname':
	    {
			$surname	= $value;
			break;
	    }		// default surname of individual

	    case 'treename':
	    {
			$treename	= $value;
			break;
	    }		// default surname of individual

	    case 'idmr':
	    {		// identify specific marriage to select for display
			if (strlen($value) > 0 &&
			    ctype_digit($value))
			    $idmr		= $value;
			if (strlen($value) > 0)
			    if (ctype_digit($value))
					$idmr		= $value;
			    else
					$msg	.= "Invalid IDMR=$value. ";
			break;
	    }		// identify specific marriage

	    case 'new':
	    {		// add a new family
			if (strtolower($value) == 'y')
			    $new	= true;
			break;
	    }		// add a new family

	    case 'submit':
	    case 'debug':
	    {		// emit debugging information
			if ($value == 'Y' || $value == 'y')
			    $submit	= true;
			break;
	    }		// emit debugging information

	    // ignore unrecognized parameters
	}		// take action on specific parameter
}			// loop through all parameters passed to script

if ($debug)
    $debugText      = 'Y';
else
    $debugText      = 'N';
// validate the parameters
if (!is_null($idmr))
{		// display a specific marriage
    if ($debug)
		$warn	.= "<p>" . __LINE__ .
					" new Family('idmr'=>$idmr)</p>\n";
    $family		            = new Family(array('idmr' => $idmr));
    if (is_null($idir) || $idir == 0)
		$idir		        = $family->get('idirhusb');
    if (is_null($idir) || $idir == 0)
		$idir		        = $family->get('idirwife');

    $indiv		            = new Person(array('idir' => $idir));
    $isOwner		        = canUser('edit') && $indiv->isOwner();
    if ($indiv->isExisting())
    {
		if ($given == '')
		    $given		    = $indiv->getGivenName();
		if ($surname == '')
		    $surname		= $indiv->getSurname();
		$sex		    	= $indiv->get('gender');
		$evBirth	    	= $indiv->getBirthEvent();
		if ($evBirth)
		    $birth	    	= $evBirth->getDate();
		$evDeath	    	= $indiv->getDeathEvent();
		if ($evDeath)
		    $death	    	= $evDeath->getDate();

		if (strtolower(substr($surname, 0, 2)) == 'mc')
		    $prefix	= 'Mc';
		else
		    $prefix	= substr($surname, 0, 1);

		$title		= "Edit Families for $given $surname";
		$families	= $indiv->getFamilies();
    }
    else
    {
		$title		= "Edit Family IDMR=$idmr";
		$families	= array($family);
    }

}			        // explicit family to view
else
if (!is_null($idir))
{			        // the identified individual is the primary spouse
    $indiv		    = new Person(array('idir' => $idir));
    $isOwner		= canUser('edit') && 
					  $indiv->isOwner();

    // ensure we have a name to use for the primary spouse
    if ($given === null)
		$given		= $indiv->getGivenName();
    else
    if ($indiv->getGivenName() == '')
		$indiv->setGivenName($given);
    if ($surname === null)
		$surname	= $indiv->getSurname();
    else
    if ($indiv->getSurname() == '')
		$indiv->setSurname($surname);
    $name		= $indiv->getName();

    // get more information about the spouse
    $sex		= $indiv->get('gender');
    $evBirth		= $indiv->getBirthEvent();
    if ($evBirth)
    {
		$birth		= $evBirth->getDate();
		$birthsd	= $evBirth->get('eventsd');
    }
    else
		$birthsd	= -99999999;
    $evDeath		= $indiv->getDeathEvent();
    if ($evDeath)
		$death		= $evDeath->getDate();
    $families		= $indiv->getFamilies();
    $idmrpref		= $indiv->get('idmrpref');
    if (count($families) == 0 && $idmrpref > 0)
    {			// correct database error
		$indiv->set('idmrpref', 0);
		$indiv->save(false);
		$idmrpref	= 0;
    }			// correct database error

    // choose family to display based upon parameters
    if ($idmrpref == 0 || $new)
    {			        // preferred marriage not already set
		if (count($families) > 0 && !$new)
		{		        // at least one marriage
		    $family	        = $families->rewind();
		    if ($family)
		    {		    // have first family
				$idmrpref	= $family->getIdmr(); 
				// update field in individual
				$indiv->set('idmrpref', $idmrpref);
				$indiv->save(false);
		    }		    // have first family
		}		        // at least one marriage
		else		    // no families
		if ($sex == 0)
		{		        // male and no family
		    if ($debug)
				$warn	.= "<p>" . __LINE__ . " new Family(array('idirhusb' => $idir))</p>\n";
		    $family	    = new Family(array('idirhusb' => $indiv));
		}		        // male and no family
		else
		{		        // female and no family
		    if ($debug)
				$warn	.= "<p>" . __LINE__ . " new Family(array('idirwife' => $idir)))</p>\n";
		    $family	    = new Family(array('idirwife' => $indiv));
		}		        // female and no family
    }			        // preferred marriage not already set
    else
    {
		if ($debug)
		    $warn	    .= "<p>" . __LINE__ . " new Family('idmr'=>$idmrpref)</p>\n";
		$family		    = new Family(array('idmr' => $idmrpref));
    }

    if (strtolower(substr($surname, 0, 2)) == 'mc')
		$prefix	        = 'Mc';
    else
		$prefix	        = substr($surname, 0, 1);

    $title	            = "Edit Families for $name";

	if (!$isOwner)
	    $msg	.= 'You are not authorized to edit the marriages of '.
					$given . ' ' . $surname;
}				// get the requested spouse
else
if (!is_null($child))
{				// the identified individual is a child
	try
	{
	    $indiv		= new Person($child);
	    $isOwner		= canUser('edit') && 
						  $indiv->isOwner();
	    if ($given === null)
			$given		= $indiv->getGivenName();
	    if ($surname === null)
			$surname	= $indiv->getSurname();
	    $sex		= $indiv->get('gender');
	    $evBirth		= $indiv->getBirthEvent();
	    if ($evBirth)
			$birth		= $evBirth->getDate();
	    $evDeath		= $indiv->getDeathEvent();
	    if ($evDeath)
			$death		= $evDeath->getDate();
	    $idmrpref		= $indiv->get('idmrparents');
	    $families		= $indiv->getParents();
	    if ($idmrpref == 0)
	    {			// set preferred parents to first parents
			if (count($families) > 0)
			{		// at least one set of parents
			    $family	= $families->rewind();
			    if ($family)
			    {		// have first family
					$idmrpref	= $family->getIdmr(); 
					// update field in individual
					$indiv->set('idmrparents', $idmrpref);
					$indiv->save(false);
			    }		// have first family
			}		// at least one set of parents
			else
			{		// no set of parents
			    if ($debug)
					$warn	.= "<p>" . __LINE__ . " new Family(array('husbsurname' => '$surname', 'husbmarrsurname' => $surname'))</p>\n";
			    $parms	= array('husbsurname'	=> $surname,
							'husbmarrsurname' => $surname);
			    $family	= new Family($parms);
			}		// no set of parents
	    }		// set preferred parents to first parents 

	    if (strtolower(substr($surname, 0, 2)) == 'mc')
			$prefix	= 'Mc';
	    else
			$prefix	= substr($surname, 0, 1);

	    $title	= "Edit Parents for $given $surname";
	}
	catch(Exception $e)
	{		// error in new Person
	    $title	= 'Invalid Identification of Primary Person';
	    $msg	.= "child=$child: " . $e->getMessage();
	    $isOwner	= true;
	    $indiv	= null;
	}		// error in new Person

	if (!$isOwner)
	    $msg	.= 'You are not authorized to edit '.
					$given . ' ' . $surname;
}		// get the requested child
else
{		// required parameter missing or invalid
	$title		= "idir or child or idmr Parameter Missing or Invalid";
	$msg		.= "idir or child or idmr parameter missing or invalid. ";
}		// missing required parameter

$title	= str_replace('"','&quot;',$title);
htmlHeader($title,
    array(  '/jscripts/tinymce/js/tinymce/tinymce.js',
			'/jscripts/CommonForm.js',
			'/jscripts/js20/http.js',
			'/jscripts/util.js',
 		    '/jscripts/Cookie.js',
 		    '/jscripts/locationCommon.js',
 		    'commonMarriage.js',
			'editMarriages.js'),
			true);
?>
<body>
  <div id="bodydiv" class="body">
<h1>
  <span class="right">
	<a href="editMarriagesHelpen.html" target="help">? Help</a>
  </span>
	<?php print $title; ?>
	  <div style="clear: both;"></div>
</h1>
<?php
showTrace();

if (strlen($msg) > 0)
{			// error message to display
?>
  <p class="message">
	<?php print $msg; ?> 
  </p>
<?php
}			// error message to display
else
if ($indiv)
{			// primary individual found
?>
  <form name="indForm" id="indForm" action="updateMarriages.php" method="post">
  <fieldset id="FamiliesSet" class="other">
	<legend class="labelSmall">Families</legend>
	  <input type="hidden" name="idir" id="idir"
			value="<?php print $idir; ?>">
	  <input type="hidden" name="child" id="child" 
			value="<?php print $child; ?>">
	  <input type="hidden" name="sex" id="sex" 
			value="<?php print $sex; ?>">
	  <input type="hidden" name="debug" id="debug" 
			value="<?php print $debugText; ?>">
	<table class="details" id="marriageList">
	  <thead>
	    <tr>
  	      <th class="colhead">
  		Date
  	      </th>
  	      <th class="colhead">
  		Husband
  	      </th>
  	      <th class="colhead">
  		Wife
  	      </th>
  	      <th class="colhead">
  		Pref
  	      </th>
  	      <th class="colhead" colspan="2">
  		Actions
  	      </th>
	    </tr>
	  </thead>
	  <tbody id="marriageListBody">
<?php
	foreach($families as $index => $tfamily)
	{		// loop through families
	    $idmr		        = $tfamily->getIdmr();

	    $husbName		    = $tfamily->getHusbName();
	    $husbid		        = $tfamily->get('idirhusb');
	    $wifeName		    = $tfamily->getWifeName();
	    $wifeid		        = $tfamily->get('idirwife');

	    // information about husband
	    try
	    {
			$husband	    = new Person(array('idir' => $husbid));
	    }
	    catch(Exception $e)
	    {
			$husband	    = null;
	    }

	    // information about wife
	    try
	    {
			$wife		    = new Person(array('idir' =>$wifeid));
	    }
	    catch(Exception $e)
	    {
			$wife		    = null;
	    }

	    $mdateo		        = new LegacyDate($tfamily->get('mard'));
	    $mdate		        = $mdateo->toString();

	    if (strlen($mdate) == 0)
			$mdate	        = 'Unknown';
?>
	  <tr id="marriage<?php print $idmr; ?>">
	    <td id="mdate<?php print $idmr; ?>">
			<?php print $mdate; ?> 
	    </td>
	    <td>
	      <a href="Person.php?id=<?php print $husbid; ?>"
					class="male" id="husbname<?php print $idmr; ?>">
			<?php print $husbName; ?>
	      </a>
	    </td>
	    <td>
	      <a href="Person.php?id=<?php print $wifeid; ?>"
					class="female" id="wifename<?php print $idmr; ?>">
			<?php print $wifeName; ?>
	      </a>
	    </td>
	    <td class="center">
	      <input type="checkbox" name="Pref<?php print $idmr; ?>"
			<?php if ($idmr == $idmrpref) print "checked"; ?>>
	    </td>
	    <td>
	      <button type="button" class="button" class="button" 
					id="Edit<?php print $idmr; ?>">
			Edit Family
	      </button>
	    </td>
	    <td>
	      <button type="button" class="button" class="button"
					id="Delete<?php print $idmr; ?>">
			Delete Family
	      </button>
	    </td>
	  </tr>
<?php
	}		// loop through marriages
?>
	</tbody>
  </table>
	<div class="row">
	  <label class="column1" for="Add">
			Actions:
	  </label>
	  <button type="button" class="button" id="Add">
			<u>A</u>dd Family
	  </button>
	  &nbsp;
	  <button type="button" class="button" id="Reorder"
			style="width: 18em;">
	    <u>O</u>rder Families by Date
	  </button>
	</div>
  </fieldset>
  </form> <!-- name="indForm" id="indForm" -->
<?php
	if ($family)
	{		// family chosen to display
	    $idmr		        = $family->getIdmr();
	    $idms		        = $family->get('idms');
	    $namerule		    = $family->get('marriednamerule');
	    $selected		    = ' selected="selected"';
	    $idirhusb		    = $family->get('idirhusb');
	    if ($idirhusb)
	    {
			$husb		    = $family->getHusband();
            $husbgivenname	= $husb->get('givenname');
            if (strlen($husbgivenname) == 0)
            $warn           .= "<p>editMarriages.php: " . __LINE__ .
                " husbgivenname='$husbgivenname'</p>\n";
			$husbgivenname	= str_replace('"','&quot;',$husbgivenname);
			$husbsurname	= $husb->get('surname');
			$husbsurname	= str_replace('"','&quot;',$husbsurname);
			$husbbirthsd	= $husb->get('birthsd');
			$husborder	    = $family->get('husborder');
	    }
	    else
	    {
			$husbgivenname	= '';
			$husbsurname	= '';
			$husbbirthsd	= -99999999;
			$husborder	    = 0;
	    }
	    $idirwife		    = $family->get('idirwife');
	    if ($idirwife)
	    {
			$wife		    = $family->getWife();
			$wifegivenname	= $wife->get('givenname');
			$wifegivenname	= str_replace('"','&quot;',$wifegivenname);
			$wifesurname	= $wife->get('surname');
			$wifesurname	= str_replace('"','&quot;',$wifesurname);
			$wifebirthsd	= $wife->get('birthsd');
			$wifeorder	    = $family->get('wifeorder');
	    }
	    else
	    {
			$wifegivenname	= '';
			$wifesurname	= '';
			$wifebirthsd	= -99999999;
			$wifeorder	    = 0;
	    }
        $evMar		        = $family->getMarEvent(true);
        $evMar->dump('editMarriages.php: ' . __LINE__);
        showTrace();
	    $marDate		    = $evMar->getDate();
	    try {
			$marLoc		    = $evMar->getLocation()->toString();
	    } catch(Exception $e) {
			$marLoc		    = $e->getMessage();
	    }
	    $marLoc		        = str_replace('"','&quot;',$marLoc);
	    $notes		        = $family->get('notes');
	    $notes	        	= trim($notes);
	    if (substr($notes, 0, 3) == '<p>');
	    {
			if (strpos(substr($notes, 3), '<p>') === false &&
			    substr($notes, -4) == '</p>')
			    $notes	    = substr($notes, 3, strlen($notes) - 7);	
	    }
	    if (substr($notes, -4) == '<br>')
			$notes		    = substr($notes, 0, strlen($notes) - 4);
?>
<!--*********************************************************************
 *		The current family is displayed in a separate form				*
 *																		*
 **********************************************************************-->
  <form name="famForm" id="famForm"
	action="updateMarriageXml.php" method="post">
	  <input type="hidden" name="idmr" id="idmr" value="<?php print $idmr; ?>">
	  <input type="hidden" name="treename" id="treename" 
			value="<?php print str_replace('"','&quot;',$treename); ?>">
  <fieldset id="HusbandSet" class="male">
	<legend class="labelSmall">Husband</legend>
	<div class="row" id="Husb">
	  <div class="column1">
	    <label class="column1" for="HusbGivenName`">
			Name:
	    </label>
	    <input type="hidden" name="IDIRHusb" id="IDIRHusb"
					value="<?php print $idirhusb; ?>">
	    <input type="text" name="HusbGivenName" id="HusbGivenName"
                    maxlength="120" class="white left column1"
                    value="<?php print $husbgivenname; ?>">
	    <input type="text" name="HusbSurname" id="HusbSurname"
                    maxlength="120" class="white left column2"
                    value="<?php print $husbsurname; ?>">
	    <input type="hidden" name="HusbBirthSD" id="HusbBirthSD"
					value="<?php print $husbbirthsd; ?>">
	    <input type="hidden" name="HusbOrder" id="HusbOrder"
					value="<?php print $husborder; ?>">
	  </div>
	  <div>
	    <button type="button" class="button" id="editHusb">
			Edit Husband
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
	<div class="row" id="HusbMarrSurnameRow">
	  <div class="column1">
	    <label class="column1" for="HusbMarrSurname">
			Married Surname:
	    </label>
	    <span class="left column1">&nbsp;</span>
	    <input type="text" name="HusbMarrSurname" id="HusbMarrSurname"
                    maxlength="255" class="white left column2"
                    value="<?php print $husbsurname; ?>">
	  </div>
	  <div style="clear: both;"></div>
	</div>
	<div class="row" id="SelectHusbandRow">
	    <label class="column1" for="chooseHusb">
			Actions:
	    </label>
	    <button type="button" id="chooseHusb"
			class="button">
			Select Existing Husband
	    </button>
	    &nbsp;
	    <button type="button" id="createHusb"
			class="button">
			Create New <u>H</u>usband
	    </button>
	    &nbsp;
	    <button type="button" id="detachHusb"
			class="button">
			Detach Husband
	    </button>
	  <div style="clear: both;"></div>
	</div> <!-- end of Husband row -->
  </fieldset>
  <fieldset id="WifeSet" class="female">
	<legend class="labelSmall">Wife</legend>
	<div class="row" id="Wife">
	  <div class="column1">
	    <label class="column1" for="WifeGivenName">
			Name:
	    </label>
	    <input type="hidden" name="IDIRWife" id="IDIRWife"
					value="<?php print $idirwife; ?>">
	    <input type="text" name="WifeGivenName" id="WifeGivenName"
                    maxlength="120" class="white left column1"
                    value="<?php print $wifegivenname; ?>">
	    <input type="text" name="WifeSurname" id="WifeSurname"
                    maxlength="120" class="white left column2"
                    value="<?php print $wifesurname; ?>">
	    <input type="hidden" name="WifeBirthSD" id="WifeBirthSD"
					value="<?php print $wifebirthsd; ?>">
	    <input type="hidden" name="WifeOrder" id="WifeOrder"
					value="<?php print $wifeorder; ?>">
	  </div>
	  <div>
	    <button type="button" class="button" id="editWife">
			Edit Wife
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
	<div class="row" id="WifeMarrSurnameRow">
	  <div class="column1">
	    <label class="column1" for="WifeMarrSurname">
			Married Surname:
	    </label>
	    <span class="left column1">&nbsp;</span>
	    <input type="text" name="WifeMarrSurname" id="WifeMarrSurname"
                    maxlength="255" class="white left column2"
                    value="<?php print $husbsurname; ?>">
	  </div>
	  <div style="clear: both;"></div>
	</div>
	<div class="row" id="selectWifeRow">
	    <label class="column1" for="chooseWife">
			Actions:
	    </label>
	    <button type="button" id="chooseWife"
			class="button">
			Select Existing Wife
	    </button>
	    &nbsp;
	    <button type="button" id="createWife"
			class="button">
			Create New <u>W</u>ife
	    </button>
	    &nbsp;
	    <button type="button" id="detachWife"
			class="button">
			Detach Wife
	    </button>
	  <div style="clear: both;"></div>
	</div> <!-- end of Wife row -->
  </fieldset>
  <fieldset id="EventSet" class="other">
	<legend class="labelSmall">Events</legend>
	<div class="row" id="MarriageRow">
	  <div class="column1">
	    <label class="column1" for="MarD">
			Married:
	    </label>
	    <input type="text" name="MarD" id="MarD"
					size="12" maxlength="100"
					class="white left" value="<?php print $marDate; ?>">
	    <span style="font-weight: bold;">at</span>
	    <input type="text" name="MarLoc" id="MarLoc"
					size="36" maxlength="255"
					class="white leftnc" value="<?php print $marLoc; ?>">
	  </div>
	  <div>
	    <button type="button" class="button" id="marriageDetails">
			Details
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div> <!-- end of Marriage row -->
  <!-- rows for other events are inserted here -->
<?php
	    $events	= $family->getEvents();
	    foreach($events as $ider => $event)
	    {			// loop through all events
			$idet		= $event->getIdet();
			$eventd		= $event->getDate();
			$eventloc	= $event->getLocation()->toString();
			$description	= $event->get('description');
			$type		= Event::$eventText[$idet];
?>
	<div class="row" id="EventRow<?php print $ider; ?>">
	  <div class="column1">
	    <label class="column1" for="Date<?php print $ider; ?>">
			<?php print $type; ?> <?php print $description; ?>
	    </label>
	    <input type="hidden"
					name="citType<?php print $ider; ?>"
					value="<?php print $idet; ?>">
	    <input type="text" size="12"
					name="Date<?php print $ider; ?>"
					class="white left"
					value="<?php print $eventd; ?>">
	    <span style="font-weight: bold;">at</span>
	    <input type="text" size="36"
					name="EventLoc<?php print $ider; ?>"
					class="white leftnc"
					value="<?php print $eventloc; ?>" >
	  </div>
	  <div>
	    <button type="button" class="button"
					id="EditEvent<?php print $ider; ?>">
			Details
	    </button>
	    &nbsp;
	    <button type="button" class="button"
					id="DelEvent<?php print $ider; ?>">
			Delete
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
<?php
	    }			// loop through all events
?>
	<div class="row" id="AddEventRow">
	  <label class="column1" for="AddEvent">
			Actions:
	  </label>
	  <button type="button" id="AddEvent"
			class="button">
			<u>A</u>dd Event
	  </button>
			&nbsp;
	  <button type="button" id="OrderEvents"
			class="button">
			    Order Events by <u>D</u>ate
	  </button>
	  <div style="clear: both;"></div>
	</div>
  </fieldset>
  <fieldset id="InformationSet" class="other">
	<legend class="labelSmall">Information</legend>
	<div class="row" id="IdmrRow">
	  <div class="column1">
	    <label class="column1" for="idmrshow">
			IDMR:
	    </label>
        <input type="text" name="idmrshow" id="idmrshow"
                    size="6" class="ina rightnc"
					readonly="readonly" value="<?php print $idmr; ?>">
	  </div>
	  <div style="clear: both;"></div>
	</div>
	<div class="row" id="StatusRow">
	  <div class="column1">
	    <label class="column1" for="IDMS">
			    Status:
	    </label>
	    <select name="IDMS" id="IDMS" size="1" class="white left">
			<option value="1"<?php if ($idms == 1) print $selected;?>>
			    no special status
			</option>
			<option value="2"<?php if ($idms == 2) print $selected;?>>
			    Annulled
			</option>
			<option value="3"<?php if ($idms == 3) print $selected;?>>
			    Common Law
			</option>
			<option value="4"<?php if ($idms == 4) print $selected;?>>
			    Divorced
			</option>
			<option value="5"<?php if ($idms == 5) print $selected;?>>
			    Married
			</option>
			<option value="6"<?php if ($idms == 6) print $selected;?>>
			    Other
			</option>
			<option value="7"<?php if ($idms == 7) print $selected;?>>
			    Separated
			</option>
			<option value="8"<?php if ($idms == 8) print $selected;?>>
			    Unmarried
			</option>
			<option value="9"<?php if ($idms == 9) print $selected;?>>
			    Divorce
			</option>
			<option value="10"<?php if ($idms == 10) print $selected;?>>
			    Separation
			</option>
			<option value="11"<?php if ($idms == 11) print $selected;?>>
			    Private
			</option>
			<option value="12"<?php if ($idms == 12) print $selected;?>>
			    Partners
			</option>
			<option value="13"<?php if ($idms == 13) print $selected;?>>
			    Death of one spouse
			</option>
			<option value="14"<?php if ($idms == 14) print $selected;?>>
			    Single
			</option>
			<option value="15"<?php if ($idms == 15) print $selected;?>>
			    Friends
			</option>
	    </select>
	  </div>
	  <div style="clear: both;"></div>
	</div> <!-- end of Ending Status row -->

	<div class="row" id="NameRuleRow">
	  <label class="column1" for="MarriedNameRule">
			Name Rule:
	  </label>
	    <select name="MarriedNameRule" id="MarriedNameRule"
			size="1" class="white left">
			<option value="0"<?php if ($namerule == 0) print $selected;?>>
			    Don't Generate Married Names
			</option>
			<option value="1"<?php if ($idms == 1) print $selected;?>>
			    Replace Wife's Surname with Husband's Surname
			</option>
	    </select>
	  <div style="clear: both;"></div>
	</div> <!-- end of Name Rule row -->

	<div class="row" id="NotesRow">
	  <label class="column1" for="Notes">
			Notes:
	  </label>
<!-- note that when initializing a <textarea>, unlike other tags
	 the space around the text value becomes part of the value
	 of the tag, so there can be no space characters between the 
	 opening and closing tags and the value of the field -->
	    <textarea name="Notes" id="Notes" cols="60" rows="4"
			><?php print $notes; ?></textarea>
	  <div style="clear: both;"></div>
	</div> <!-- end of Notes row -->
	<div class="row" id="NoteDetailsButtonRow">
	  <div class="column1">
	    <label class="column1" for="noteDetails">
	    </label>
	  </div>
	  <div>
	    <button type="button" class="button" id="noteDetails">
			Details
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div> <!-- end of NoteDetailsButton row -->
	<div class="row" id="InformationActionsRow">
	    <label class="column1" for="chooseHusb">
			Actions:
	    </label>
	    <button type="button" class="button" id="Pictures">
			Edit <u>P</u>ictures
	    </button>
	  <div style="clear: both;"></div>
	</div> <!-- end of Notes row -->
  </fieldset>
  <fieldset class="other" id="ChildrenSet">
	<legend class="labelSmall">Children</legend>
	<table class="details" id="children">
	  <thead>
	   <tr>
	    <th class="colhead" style="width: 176px;">
			&nbsp;&nbsp;&nbsp;Given&nbsp;&nbsp;&nbsp;
	    </th>
	    <th class="colhead" style="width: 116px;">
			&nbsp;&nbsp;Surname&nbsp;&nbsp;
	    </th>
	    <th class="colhead" style="width: 104px;">
			&nbsp;Birth&nbsp;
	    </th>
	    <th class="colhead" style="width: 104px;">
			&nbsp;Death&nbsp;
	    </th>
	    <th class="colhead" colspan="2">
			Actions
	    </th>
	   </tr>
	  </thead>
	  <tbody id="childrenBody">
<?php
	    $children	= $family->getChildren();
	    $rownum	= 0;
	    foreach($children as $idcr => $child)
	    {			// loop through all children
			$cIdcr		    = $child->getIdcr();
			$cIdir		    = $child['idir'];
			try {
			    $cPerson	= $child->getPerson();
			    $gender	    = $cPerson->get('gender');
			    $csurname	= $cPerson->get('surname');
			    $csurname	= str_replace('"','&quot;',$csurname);
			    $cgivenname	= $cPerson->get('givenname');
			    $cgivenname	= str_replace('"','&quot;',$cgivenname);
			    $evBirth	= $cPerson->getBirthEvent(true);
			    $birthd	    = $evBirth->getDate();
			    $birthsd	= $evBirth->get('eventsd');
			    $evDeath	= $cPerson->getDeathEvent(true);
			    $deathd	= $evDeath->getDate();
			    $deathsd	= $evDeath->get('eventsd');
			} catch (Exception $e) {
			    $cPerson	= null;
			    $gender	= 3;
			    $csurname	= $surname;
			    $csurname	= str_replace('"','&quot;',$csurname);
			    $cgivenname	= "Unknown " . $cIdir;
			    $cgivenname	= str_replace('"','&quot;',$cgivenname);
			    $birthd	= '';
			    $birthsd	= 0;
			    $deathd	= '';
			    $deathsd	= 0;
			}
			if ($gender == 0)
			    $genderclass	= 'male';
			else
			if ($gender == 1)
			    $genderclass	= 'female';
			else
			    $genderclass	= 'unknown';
?>
	<tr id="child<?php print $rownum; ?>">
	  <td class="name">
	    <input type="hidden"
					name="CIdir<?php print $rownum; ?>" 
					id="CIdir<?php print $rownum; ?>"
					value="<?php print $cIdir; ?>">
	    <input type="hidden"
					name="CIdcr<?php print $rownum; ?>" 
					id="CIdcr<?php print $rownum; ?>"
					value="<?php print $cIdcr; ?>">
	    <input type="hidden"
					name="CGender<?php print $rownum; ?>" 
					id="CGender<?php print $rownum; ?>"
					value="<?php print $gender; ?>">
	    <input class="<?php print $genderclass; ?>"
					name="CGiven<?php print $rownum; ?>" 
					id="CGiven<?php print $rownum; ?>"
					value="<?php print $cgivenname; ?>" 
					type="text" size="15" maxlength="120">
	  </td>
	  <td class="name">
	    <input class="<?php print $genderclass; ?>"
					name="CSurname<?php print $rownum; ?>"
					id="CSurname<?php print $rownum; ?>" 
					value="<?php print $csurname; ?>"
					type="text" size="10" maxlength="120">
	  </td>
	  <td class="name">
	    <input class="white left"
					name="Cbirth<?php print $rownum; ?>"
					id="Cbirth<?php print $rownum; ?>" 
					value="<?php print $birthd; ?>" 
					type="text" size="12" maxlength="100">
	    <input name="Cbirthsd<?php print $rownum; ?>"
					id="Cbirthsd<?php print $rownum; ?>" 
					type="hidden" 
					value="<?php print $birthsd; ?>">
	  </td>
	  <td class="name">
	    <input class="white left"
					name="Cdeath<?php print $rownum; ?>"
					id="Cdeath<?php print $rownum; ?>" 
					value="<?php print $deathd; ?>"
					type="text" size="12" maxlength="100">
	  </td>
	  <td>
	    <button type="button" class="button"
					id="editChild<?php print $rownum; ?>">
			Edit Child
	    </button>
	  </td>
	  <td>
	    <button type="button" class="button"
					id="detChild<?php print $rownum; ?>">
			Detach&nbsp;Child
	    </button>
	  </td>
	</tr>
<?php
			$rownum++;
	    }			// loop through children
?>
	  </tbody>
	</table> <!-- id="children" -->
	<input type="hidden"
			name="CIdir99" id="CIdir99"
			value="-1">
	<div class="row">
	    <label class="column1" for="chooseHusb">
			Actions:
	    </label>
	    <button type="button" class="button" id="addChild">
			    Add <u>E</u>xisting Child
	    </button>
	    &nbsp;
	    <button type="button" class="button" id="addNewChild">
			    Add <u>N</u>ew&nbsp;Child
	    </button>
	    &nbsp;
	    <button type="button" class="button" id="orderChildren"
			style="width: 18em;">
	      <u>O</u>rder Children by Birth Date
	    </button>
	</div>
  </fieldset>
<p id="MarrButtonLine">
<?php
	    if ($submit)
	    {			// debugging, use submit
?>
  <button type="submit" id="Submit">
	    Submit Family
  </button>
  <button type="button" class="button" id="update">
	    <u>U</u>pdate Family
  </button>
<?php
	    }			// debugging, use submit
	    else
	    {			// normal, use AJAX
?>
  <button type="button" class="button" id="update">
	    <u>U</u>pdate Family
  </button>
<?php
	    }			// normal, use AJAX
	}			// family chosen to display
?>
&nbsp;
  <button type="button" class="button" id="Finish">
	    <u>C</u>lose
  </button>
</p>
  </form> <!-- name="famForm" id="famForm" -->
</div> <!-- id="bodydiv" -->
<?php
}				// individual found

dialogBot();
?>

<div class="balloon" id="HelpPref">
<p>Click on the checkbox to make the specified marriage the preferred
marriage.
</p>
</div>
<div class="balloon" id="HelpEdit">
<p>Edit the marriage on this row.  A dialog is displayed with details 
of the marriage.
</p>
</div>
<div class="balloon" id="HelpDelete">
<p>Delete the marriage on this row.
</p>
</div>
<div class="balloon" id="HelpAdd">
<p>Add a new marriage to the current individual.
</p>
</div>
<div class="balloon" id="HelpFinish">
<p>Close the dialog.  The updates to the database have already been made.
</p>
</div>
<div class="balloon" id="HelpReorder">
<p>Change the order of the marriages to be in chronological order by
marriage date.  If you know the actual order of the marriages, but do not
know the exact date of a marriage, it is recommended that you specify 
a range of dates for the marriage as this will not only permit using this
feature to order the marriages correctly, but also give a hint as to which
documentary sources to search to complete the information.
</p>
</div>
<div class="balloon" id="Helpidmr">
<p>This read-only field displays the internal numeric identifier of
this relationship.
</p>
</div>
<div class="balloon" id="HelpHusbGivenName">
<p>This displays the given names of the husband.
If you alter this field it changes the given name of the individual.
</p>
</div>
<div class="balloon" id="HelpHusbSurname">
<p>This displays the family name of the husband. 
If you alter this field it changes the family name of the individual.
</p>
</div>
<div class="balloon" id="HelpHusbMarrSurname">
<p>This displays the family name by which the husband was known during this
marriage.
If the traditional rule in which the Husband did
not change his surname on marriage is in effect this is a read-only field.
</p>
</div>
<div class="balloon" id="HelpWifeGivenName">
<p>This displays the given names of the wife. 
If you alter this field it changes the given name of the individual.
</p>
</div>
<div class="balloon" id="HelpWifeSurname">
<p>This displays the family name of the wife. 
If you alter this field it changes the family name of the individual.
</p>
</div>
<div class="balloon" id="HelpWifeMarrSurname">
<p>This displays the family name by which the wife was known during this
marriage.  If the traditional rule in which the Wife took her husband's surname
on marriage is in effect this is a read-only field.
</p>
</div>
<div class="balloon" id="HelpchooseHusb">
<p>Selecting this button pops up a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the husband
in this marriage.
</p>
</div>
<div class="balloon" id="HelpeditHusb">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to modify information about the individual
who is the husband in this marriage.
</p>
</div>
<div class="balloon" id="HelpcreateHusb">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to create a
new individual in the family tree to be the husband in this marriage.
</p>
</div>
<div class="balloon" id="HelpdetachHusb">
<p>Selecting this button detaches the currently assigned husband from this
marriage.  It is not necessary to do this before selecting or creating
a new husband.
</p>
</div>
<div class="balloon" id="HelpeditWife">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to modify information about the individual
who is the wife in this marriage.
</p>
</div>
<div class="balloon" id="HelpchooseWife">
<p>Selecting this button pops up a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the wife
in this marriage.
</p>
</div>
<div class="balloon" id="HelpcreateWife">
<p>Selecting this button pops up a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
that permits you to create a
new individual in the family tree to be the wife in this marriage.
</p>
</div>
<div class="balloon" id="HelpdetachWife">
<p>Selecting this button detaches the currently assigned wife from this
marriage.  It is not necessary to do this before selecting or creating
a new wife.
</p>
</div>
<div class="balloon" id="HelpMarD">
<p>Supply the date of the marriage.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpMarLoc">
<p>Supply the location of the marriage.  The text you enter is used to
select an appropriate Location record.  This is done by first doing a
case-insensitive search for a match on the complete text you entered, and if
this fails then a search is done for a match on the short name of the
location.  If no match is found on either search then a new location is
created using exactly the text you entered.  Subsequently the only way that
you can change the appearance of the location is to either select a different
location by typing in its name or short name, or by editing the location
record itself.
</p>
</div>
<div class="balloon" id="HelpmarriageDetails">
<p>Clicking on this button opens a dialog that permits you to add further
details about the marriage and to specify source citations for the fact.
</div>
<div class="balloon" id="HelpMarEndD">
<p>Supply the date that the marriage or relationship came to an end.
If the marriage came to an end as a result of some specific event,
for example a divorce, annulment, or legal separation, then you
should add an event describing that rather than using this field.
<p>The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpSealD">
<p>Supply the date that the partners were sealed to each other at a
Church of Latter Day Saints temple.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href="datesHelpen.html">supported date formats</a> for details.
</p>
</div>
<div class="balloon" id="HelpSealLoc">
<p>This read-only field contains the name of the Church of Latter Day
Saints temple where the partners were sealed to each other. 
To select a different temple click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpDate">
<p>This read-only field displays the date of the family event.
To modify the date, or any other information about this event, click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpLoc">
<p>This read-only field displays the location of the event.
To modify the location, or any other information about this event, click on the
<span class="button">Details</span> button at the end of this row.
</p>
</div>
<div class="balloon" id="HelpAddEvent">
<p>Selecting this button opens a
<a href="editEventHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about a new event being added to the marriage.
</p>
</div>
<div class="balloon" id="HelpOrderEvents">
<p>Selecting this button reorders the events of this marriage by their dates.
</p>
</div>
<div class="balloon" id="HelpEditEvent">
<p>Selecting this button opens a
<a href="editEventHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about the event summarized in this line of the form.
In particular you may add source citations for the event.
</p>
</div>
<div class="balloon" id="HelpDelEvent">
<p>Selecting this button deletes
the event summarized in this line of the form.
</p>
</div>
<div class="balloon" id="HelpIDMS">
<p>This selection list permits you to specify the ending or current
status of this marriage.
</p>
</div>
<div class="balloon" id="HelpMarriedNameRule">
<p>This selection list permits you to specify whether or not the wife
took her husband's surname as a result of the marriage.  The default
is the traditional practice.
</p>
</div>
<div class="balloon" id="HelpNotMarried">
<p>This checkbox is used to indicate that the couple is known to have never
been married.  You may remove this fact 
by clicking on the checkbox to change its state.
</p>
</div>
<div class="balloon" id="HelpneverMarriedDetails">
<p>Click on this button to add additional information about the never married
fact.  In particular you may add source citations for the fact.
</p>
</div>
<div class="balloon" id="HelpNoChildren">
<p>This checkbox is used to indicate that the couple is known to have never
had children.  You may remove this fact by clicking on the 
checkbox to change its state.
</p>
</div>
<div class="balloon" id="HelpnoChildrenDetails">
<p>Click on this button to add additional information about the no children
fact.  In particular you may add source citations for the fact.
</p>
</div>
<div class="balloon" id="HelpNotes">
<p>Supply extended textual notes about the marriage.
Note that the text may include HTML markup which will appear in the
resulting web page.  For example placing the tags "&lt;b&gt;" and "&lt;/b&gt;"
around text makes it bold.  Placing the tags "&lt;a href="Person.php?idir=9999 class="male"&gt;" and "&lt;/a&gt;" around a name, where "9999" is replaced
by the appropriate numeric key value and the appropriate gender is specified
for "class" highlights the name as a hyperlink to the web page.  You can use
this technique to connect the names of witnesses or other participants in the
marriage to their records in the family tree.
</p>
<p>Although you might be tempted to include the text of a newspaper notice
about the marriage in this field, it is recommended that you put that
text into the citation text field instead.
</p>
</div>
<div class="balloon" id="HelpnoteDetails">
<p>Click on this button to add additional information about the marriage notes.
In particular you may add source citations for the notes.
</p>
</div>
<div class="balloon" id="HelpaddChild">
<p>Selecting this button, or using the keyboard short-cut alt-E, opens a
<a href="chooseIndividHelpen.html" target="_blank">dialog</a> 
to choose an existing individual
in the family tree database to add as a child of this family.
</p>
</div>
<div class="balloon" id="HelpaddNewChild">
<p>Selecting this button, or using the keyboard short-cut alt-N, opens a 
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
to create a new individual in the
family tree database that is added as a child of this family.
</p>
</div>
<div class="balloon" id="HelpCGiven">
<p>This displays the given names of a child.
If you alter this field it changes the given name of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCSurname">
<p>This displays the family name of a child.
This defaults to the family name of the father.
If you alter this field it changes the family name of the child.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCbirth">
<p>This input field displays the birth date of a child.
If you alter this field it changes the birth date of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="HelpCdeath">
<p>This input field displays the death date of a child.
If you alter this field it changes the death date of the individual.
Pressing the Enter key in this field moves the input focus down to the
given name field of the next child, adding a new empty child row if needed.
</p>
</div>
<div class="balloon" id="Helpupdate">
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class="balloon" id="HelporderChildren">
<p>Selecting this button, or using the keyboard short-cut alt-O, 
reorders the children of this marriage by their
dates of birth.
</p>
</div>
<div class="balloon" id="HelpeditChild">
<p>Selecting this button opens a
<a href="editIndividHelpen.html" target="_blank">dialog</a> 
to edit the detailed information
about the child summarized in this line of the form.
</p>
</div>
<div class="balloon" id="HelpdetChild">
<p>Selecting this button detaches the child summarized in this line of the
form from this family.  You can then go to another family and attach the
child there.
</p>
</div>
<div class="balloon" id="HelpPictures">
<p>Selecting this button opens a dialog
to edit the set of pictures associated with this family.
</p>
</div>
<div class="balloon" id="HelpFamiliesSet">
This part of the page lists the marriages.
</div>
<div class="balloon" id="HelpHusbandSet">
This part of the page groups information about the Husband.
</div>
<div class="balloon" id="HelpWifeSet">
This part of the page groups information about the Wife.
</div>
<div class="balloon" id="HelpEventSet">
This part of the page groups information about the events of the marriage.
</div>
<div class="balloon" id="HelpInformationSet">
This part of the page groups general information about the marriage.
</div>
<div class="balloon" id="HelpChildrenSet">
This part of the page groups information about the children.
</div>
<div id="loading" class="popup">
Loading...
</div>
<div class="hidden" id="templates">
  <table id="templateRows">
<!--
 *	layout of the table row to display a summary of a marriage
 *  in the table at the top of the dialog.
 *	Putting the layout here permits more user
 *	customization, including support for alternate languages.
 *	This row layout should match the rows in <tbody id="marriageListBody">
-->
	<tr id="marriage$idmr">
	  <td id="mdate$idmr">
			$mdate
	  </td>
	  <td>
	    <a href="Person.php?id=$spsid"
			class="$spsclass" id="spousename$idmr">
			$spsGiven $spsSurname
	    </a>
	  </td>
	  <td class="center">
	    <input type="checkbox" name="Pref$idmr">
	  </td>
	  <td>
	    <button type="button" class="button" id="Edit$idmr">
			Edit Family
	    </button>
	  </td>
	  <td>
	    <button type="button" class="button" id="Delete$idmr">
			Delete Family
	    </button>
	  </td>
	</tr>
<!-- end of template for marriage row -->

<!--
 *	layout of the table row to display a single child of this marriage
 *  each row is added by javascript when the XML response to the AJAX
 *	request is received.  Putting the layout here permits more user
 *	customization, including support for alternate languages.
-->
	<tr id="child$rownum">
	  <td class="name">
	    <input type="hidden" name="CIdir$rownum" id="CIdir$rownum"
					value="$idir">
	    <input type="hidden" name="CIdcr$rownum" id="CIdcr$rownum"
					value="$idcr">
	    <input type="hidden" name="CGender$rownum" id="CGender$rownum"
					value="$sex">
	    <input class="$gender" name="CGiven$rownum" id="CGiven$rownum"
					value="$givenname" type="text" size="14"
					maxlength="120">
	  </td>
	  <td class="name">
	    <input class="$gender" name="CSurname$rownum" id="CSurname$rownum" 
					value="$surname" type="text" size="10" maxlength="120">
	  </td>
	  <td class="name">
	    <input class="white left" name="Cbirth$rownum" id="Cbirth$rownum" 
					value="$birthd" type="text" size="12" maxlength="100">
	    <input name="Cbirthsd$rownum" id="Cbirthsd$rownum" type="hidden" 
					value="$birthsd">
	  </td>
	  <td class="name">
	    <input class="white left" name="Cdeath$rownum" id="Cdeath$rownum" 
					value="$deathd" type="text" size="12" maxlength="100">
	  </td>
	  <td>
	    <button type="button" class="button" id="editChild$rownum">
			Edit Child
	    </button>
	  </td>
	  <td>
	    <button type="button" class="button" id="detChild$rownum">
			Detach&nbsp;Child
	    </button>
	  </td>
	</tr>
<!-- end of template for child row -->
</table>

<!-- template for sealed to spouse event row -->
	<div class="row" id="SealedRow$temp">
	  <div class="column1">
	    <label class="column1" for="SealD">
			Sealed&nbsp;to&nbsp;Spouse (LDS):
	    </label>
	    <input type="text" name="SealD" id="SealD"
					size="12" maxlength="100"
					class="white left" value="$eventd">
	    <span style="font-weight: bold;">at</span>
	    <select name="IDTRSeal" id="IDTRSeal"
					size="1" class="white left">
<?php
$parms	= array();
$temples	= new RecordSet('Temples');
foreach($temples as $idtr => $temple)
{			// loop through all temples
?>
			<option value="<?php print $idtr; ?>">
			    <?php print $temple->getName(); ?>
			</option>
<?php
}			// loop through all temples
?>
	      </select>
	    </div>
	    <div>
	      <button type="button" class="button"
					id="EditIEvent18$temp">
			Details
	      </button>
	      &nbsp;
	      <button type="button" class="button" id="DelIEvent18$temp">
			Delete
	      </button>
	    </div>
	    <div style="clear: both;"></div>
	</div>
<!-- end of template for sealed to spouse event row -->

<!-- template for marriage ended event row -->
	<div class="row" id="EndedRow$temp">
	  <div class="column1">
	    <label class="column1" for="MrEndD">
			Marriage&nbsp;Ended:
	    </label>
	    <input type="text" name="MarEndD" id="MarEndD"
					size="12" maxlength="100"
					class="white left" value="$eventd">
	  </div>
	  <div>
	    <button type="button" class="button"
					id="EditIEvent24$temp">
			Details
	    </button>
	    &nbsp;
	    <button type="button" class="button" id="DelIEvent24$temp">
			Delete
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
<!-- end of template for mariage ended event row -->

<!-- template for general marriage event row -->
	<div class="row" id="EventRow$ider">
	  <div class="column1">
	    <label class="column1" for="Date$ider">
			$type $description
	    </label>
	    <input type="hidden"
					name="citType$ider"
					value="$idet">
	    <input type="text" size="12"
					name="Date$ider"
					class="white left"
					value="$eventd">
	    <span style="font-weight: bold;">at</span>
	    <input type="text" size="36"
					name="EventLoc$ider"
					class="white leftnc"
					value="$eventloc" >
	  </div>
	  <div>
	    <button type="button" class="button"
					id="EditEvent$ider">
			Details
	    </button>
	    &nbsp;
	    <button type="button" class="button" id="DelEvent$ider">
			Delete
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
<!-- end of template for marriage event row -->

<!-- template for never married fact row -->
	<div class="row" id="NotMarriedRow$temp">
	  <div class="column1">
	    <label class="column1" for="NotMarried">
			Not Married:
	    </label>
	    <input type="checkbox" name="NotMarried$temp" id="NotMarried$temp"
			checked="checked">
	  </div>
	  <div>
	    <button type="button" class="button" id="neverMarriedDetails$temp">
			Details
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
<!-- end of template for never married fact row -->

<!-- template for no children fact row -->
	<div class="row" id="NoChildrenRow$temp">
	  <div class="column1">
	    <label class="column1" for="NoChildren">
			No Children:
	    </label>
	    <input type="checkbox" name="NoChildren$temp" id="NoChildren$temp"
					checked="checked">
	  </div>
	  <div>
	    <button type="button" class="button" id="noChildrenDetails$temp">
			Details
	    </button>
	  </div>
	  <div style="clear: both;"></div>
	</div>
<!-- end of template for no children fact row -->

<?php
include $document_root . '/templates/LocationDialogs.html';
?>

  <!-- template for confirming the deletion of an event-->
  <form name="ClrInd$template" id="ClrInd$template">
    <p class="message">$msg</p>
    <p>
      <button type="button" id="confirmClear$type">
    	OK
      </button>
      <input type="hidden" id="formname$type" name="formname$type"
    			value="$formname">
    	&nbsp;
      <button type="button" id="cancelDelete$type">
    	Cancel
      </button>
    </p>
  </form>

  <!-- template for warning child already being edited -->
  <form name="AlreadyEditing$template" id="AlreadyEditing$template">
    <p class="message">$givenname $surname is already being edited</p>
    <p>
      <button type="button" id="justClose$template">
    	OK
      </button>
    </p>
  </form>

<?php
    foreach(Event::$eventText as $idet => $text)
    {			// make the event texts available to Javascript
?>
  <span id="EventText<?php print $idet; ?>"><?php print $text; ?></span>
<?php
    }			// make the event texts available to Javascript
?>
</div> <!-- id="templates" -->
</body>
</html>
