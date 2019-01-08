<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateIndivid.php												*
 *																		*
 *  Display a web page to support editing details of an particular		*
 *  record from the Legacy table of individuals.						*
 *																		*
 *  URI Parameters:														*
 *		idir			unique numeric key of the instance of			*
 *						Person to be displayed.							*
 *						If this is omitted or zero then 				*
 *						a new instance of Person is created.			*
 *						For backwards compatibility the script also		*
 *						accepts 'id'.									*
 *																		*
 *		The following parameters may be specified on an invocation of	*
 *		this page using Javascript window.open to request the page to	*
 *		update the values of specific fields in the invoking page when	*
 *		the user submits an update.										*
 *																		*
 *		setidir			name of field in which to place the unique		*
 *						numeric key of the new instance of Person		*
 *		Surname			name of the field in which to place the surname	*
 *						of the new instance of Person				    *
 *		GivenName		name of the field in which to place the given	*
 *						names of the new instance of Person		        *
 *		Prefix			name of the field in which to place the name	*
 *						prefix of the new instance of Person		    *
 *		NameNote		name of the field in which to place the name	*
 *						note of the new instance of Person				*
 *		Gender			name of the field in which to place the gender	*
 *						of the new instance of Person				    *
 *		BirthDate		name of the field in which to place the birth	*
 *						date of the new instance of Person				*
 *		BirthLocation	name of the field in which to place the birth	*
 *						location of the new instance of Person		    *
 *		ChrisDate		name of the field in which to place the			*
 *						christening date of the new instance			*
 *		ChrisLocation	name of the field in which to place the			*
 *						christening location of the new instance		*
 *		DeathDate		name of the field in which to place the death	*
 *						date of the new instance of Person				*
 *		DeathLocation	name of the field in which to place the death	*
 *						location of the new instance of Person		    *
 *		BuriedDate		name of the field in which to place the burial	*
 *						date of the new instance of Person				*
 *		BuriedLocation	name of the field in which to place the burial	*
 *						location of the new instance of Person		    *
 *		UserRef			name of the field in which to place the user	*
 *						reference value of the new instance of Person	*
 *		AncestralRef	name of the field in which to place the			*
 *						ancestral reference value of the new instance		*
 *						of Person										*
 *		DeathCause		name of the field in which to place the death		*
 *						cause of the new instance of Person				*
 *		... or, in general, any field name in this page.				*
 *																		*
 *		Furthermore a parameter with a name starting with 'init' can be		*
 *		used to initialize the value of a field matching the remainder		*
 *		of the parameter name if a new individual is being created.		*
 *		Note that the field name portion of these parameters is 		*
 *		case-insensitive.  												*
 *		In particular:														*
 *																		*
 *		initSurname		set initial value for the surname				*
 *		initGivenName	set initial value for the given names				*
 *		initPrefix		set initial value for the name prefix				*
 *		initNameNote	set initial value for the name note				*
 *		initGender		set initial value for the gender				*
 *		initBirthDate	set initial value for the birth date				*
 *		initBirthLocation set initial value for the birth location		*
 *		initChrisDate	set initial value for the christening date		*
 *		initChrisLocation set initial value for the christening location*
 *		initDeathDate	set initial value for the death date				*
 *		initDeathLocation set initial value for the death location		*
 *		initBuriedDate	set initial value for the burial date				*
 *		initBuriedLocation set initial value for the burial location		*
 *		initUserRef		set initial value for the user						*
 *		initAncestralRef set initial value for the ancestral				*
 *		initDeathCause	set initial value for the death cause				*
 *																		*
 *  When this is invoked to create a child the following parameter must		*
 *  be passed:																*
 *																		*
 *		parentsIdmr		IDMR of the record for the parent's relationship*
 *																		*
 *  The following parameters may also be passed to supply information		*
 *  that may not yet have been written to the database because the		*
 *  family record is in the process of being created:						*
 *																		*
 *		initSurname		initial surname for the child						*
 *		fathGivenName		father's given name								*
 *		fathSurname		father's surname								*
 *		mothGivenName		mother's given name								*
 *		mothSurname		mother's surname								*
 *																		*
 *  History:																*
 *		2010/08/11		Correct error in mailto: subject line, and add		*
 *						birth date and death date into title.				*
 *		2010/08/11		encode field values with htmlspecialchars		*
 *		2010/08/12		if invoked from another web page, apply				*
 *						changes to that web page as a side effect of		*
 *						submitting the update.  Also support				*
 *						initializing values in the form.				*
 *		2010/08/28		Add Edit Details on name to permit citations		*
 *						and to move name note off main page.				*
 *						Add Edit Details on death cause.				*
 *		2010/09/20		remove onsubmit= parameter from form				*
 *						it is supplied by editIndivid.js::loadEdit		*
 *		2010/09/27		Support standard idir= parameter				*
 *		2010/10/01		Add hyperlinks for IE < 8						*
 *		2010/10/10		Evaluate locations at top of page to handle		*
 *						error message emitted by Location constructor		*
 *		2010/10/21		use RecOwner class to validate access				*
 *		2010/10/23	    move connection establishment to common.inc
 *		2010/10/30	    move $browser object to common.inc
 *		2010/10/31	    do not expand page if user is not owner of record
 *		2010/11/10	    add help link
 *		2010/11/14	    move name prefix and title to name event
 *		2010/11/27	    add support for medical and research notes
 *			            improve separation of HTML and PHP
 *			            use editEvent.php in place of obsolete
 *			            editEventIndiv.php
 *		2010/11/29	    correct initialization of $given
 *						improve title for case of adding a child
 *		2010/12/09	    add name on submit button and initially disable
 *						add balloon help for all buttons and input fields
 *		2010/12/12	    replace LegacyDate::dateToString with
 *						LegacyDate::toString
 *						escape HTML special chars in title
 *		2010/12/18	    The link to the nominal index in the header and
 *						trailer breadcrumbs points at current name
 *		2010/12/20	    handle exception thrown by new Person
 *						handle exception thrown by new Family
 *						handle exception thrown by new LegacyLocation
 *						add button to delete individual if a candidate
 *		2010/12/24	    pass parentsIdmr to updateIndivid.php
 *						reduce padding between cells to compress the form
 *		2010/12/26	    add support for modifying field IDMRPref in
 *						response to request from editMarriages.php.
 *		2010/12/29	    if IDMRPref not set, default to first marriage
 *						ensure value of 'idar' is numeric
 *		2011/01/10	    use LegacyRecord::getField method
 *		2011/02/23	    use editEvent.php to edit general notes
 *		2011/03/02	    change name of submit button to 'Submit'
 *						visual support for Alt-U
 *		2011/03/06	    Combine label text into edit buttons and
 *						add Alt-... support for each button
 *		2011/04/22	    fix IE7 support
 *		2011/05/25	    add button for editting pictures
 *		2011/06/24	    correct handling of children
 *		2011/08/22	    if no marriages set text of Edit Marriages button
 *						to Add Spouse or Childa and change standard text
 *						to Edit Families with Alt-F
 *		2011/08/24	    if no parents set text of Edit Parents button
 *						to Add Parents
 *		2011/10/23	    add help for additional fields and buttons
 *		2012/01/07	    add ability to explicitly supply father's and 
 *						mother's name when adding a child
 *		2012/01/13	    change class names
 *		2012/01/23	    display loading indicator while waiting for response
 *						to changed in a location field
 *		2012/02/25	    id= rather than name= used to identify buttons so
 *						they will not be passed to the action script by IE
 *						help text added for some hidden fields
 *						add support for LDS events in main record
 *						add support for list of tblER events
 *						add event button moved to after list of events
 *						order events by date button added
 *						event type encoded in id value of buttons
 *						IDER encoded in id value of buttons and name value of
 *						input fields for events in tblER
 *						cittype encoded in id value of buttons and name value
 *						of input fields for events in main record		*
 *						support all documented init fields				*
 *		2012/05/06		explicitly set class for input text fields		*
 *		2012/05/12		remove display of Soundex code						*
 *		2012/05/31		defer adding child to family until submit		*
 *		2012/08/01		support user modification of events recorded in		*
 *						Event instances on the main dialog				*
 *		2012/08/12		add ability to edit sealed to parents event		*
 *		2017/07/27		class LegacyCitation renamed to class Citation		*
 *		2017/09/09		change class LegacyLocation to class Location		*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event				*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/Family.inc';
    require_once __NAMESPACE__ . '/Source.inc';
    require_once __NAMESPACE__ . '/Citation.inc';
    require_once __NAMESPACE__ . '/Address.inc';
    require_once __NAMESPACE__ . '/Temple.inc';
    require_once __NAMESPACE__ . '/LegacyHeader.inc';
    require_once __NAMESPACE__ . '/RecOwner.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // configuration options
    // if one of the following options is true then the event appears in
    // the dialog even if the event is not recorded (e.g. date and place blank)
    $alwaysShowBirth	= true;
    $alwaysShowChristen	= true;	// traditional christening
    $alwaysShowBaptism	= false;// LDS Baptism
    $alwaysShowEndow	= false;// LDS Endowment
    $alwaysShowConfirm	= false;// LDS Confirmation
    $alwaysShowInitiat	= false;// LDS Initiatory
    $alwaysShowDeath	= true;
    $alwaysShowBuried	= true;
   
    // establish support for the database header table

    $header	    = new LegacyHeader();

    $release	= $header->get('LegacyVer');

    // process parameters looking for identifier of individual
    $idir		    = 0;
    $parentsIdmr	= 0;
    $idcr		    = 0;	// edit existing child of family
    $person		    = null;	// instance of Person
    $childr		    = null;	// instance of Child
    $family		    = null;	// instance of Family
    $genderFixed	= '';	// pre-selected gender
    $idirset		= false;// idir provided and non-zero
    $fathGivenName	= '';
    $fathSurname	= '';
    $fathname		= 'unknown';
    $mothGivenName	= '';
    $mothSurname	= '';
    $mothname		= 'unknown';

    foreach($_GET as $key => $value)
    {
		switch(strtolower($key))
		{
		    case 'id':
		    case 'idir':
		    {	// identifier of individual
				if (strlen($value) > 0 &&
				    ctype_digit($value))
				{// valid number
				    $idir	= $value;
				    $idirset	= $idir > 0;
				}// valid number
				else
				{// invalid
				    $msg	.= "Unexpected value '$value' for IDIR. ";
				}// invalid
				break;
		    }	// identifier of individual

		    case 'idcr':
		    {	// identifier of child relationship record
				if (strlen($value) > 0 &&
				    ctype_digit($value) &&
				    $value > 0)
				{// valid number
				    $idcr	= $value;
				}// valid number
				else
				{// invalid
				    $msg	.= "Unexpected value '$value' for IDCR. ";
				}// invalid
				break;
		    }	// identifier of child relationship record

		    case 'parentsidmr':
		    {	// identifier of parents family
				if (strlen($value) > 0 && 
				    ctype_digit($value) &&
				    $value > 0)
				{
				    $parentsIdmr	= $value;
				}
				else
				{// invalid
				    $msg	.= "Unexpected value '$value' for parentsidmr. ";
				}// invalid
				break;
		    }	// identifier of parent's family

		    case 'fathGivenName':
		    {	// explicit father's given name
				$fathGivenName	= $value;
				break;
		    }	// explicit father's given name

		    case 'fathSurname':
		    {	// explicit father's surname
				$fathSurname	= $value;
				break;
		    }	// explicit father's given name

		    case 'mothGivenName':
		    {	// explicit mother's given name
				$mothGivenName	= $value;
				break;
		    }	// explicit mother's given name

		    case 'mothSurname':
		    {	// explicit mother's surname
				$mothSurname	= $value;
				break;
		    }	// explicit mother's given name
		}// switch on parameter name
    }	// loop through all parameters

    // override default mother's and father's names if required
    if (strlen($fathSurname) > 0 || strlen($fathGivenName) > 0)
		$fathname	= trim($fathGivenName . ' ' . $fathSurname);
    if (strlen($mothSurname) > 0 || strlen($mothGivenName) > 0)
		$mothname	= trim($mothGivenName . ' ' . $mothSurname);

    // note that record 0 in tblIR contains only the next available value
    // of IDIR
    // parameter to nominalIndex.html
    $nameuri		= '';
    $person		= null;	// instance of Person
    $childr		= null;	// instance of Child

    if ($idirset)
    {	// get the requested individual
      try
      {	// constructor throws exceptions
		$person		= new Person($idir, 'idir');

		$isOwner	= canUser('edit') && 
						  RecOwner::chkOwner($idir, 'tblIR');
		 
		$given		= $person->getGivenName();
		$surname	= $person->getSurname();
		$nameuri	= rawurlencode($surname . ', ' . $given);
		$birth		= new LegacyDate($person->get('birthd'));
		$birth		= $birth->toString();
		$death		= new LegacyDate($person->get('deathd'));
		$death		= $death->toString();
		$title		= "Edit $given $surname ($birth - $death)";
		$idar		= $person->get('idar') - 0;
      }	// constructor throws exceptions
      catch(Exception $e)
      {
		$title		= "Invalid or missing value of IDIR=$idir";
		$msg		= $e->getMessage();
		$person		= null;
		$surname	= '';
		$isOwner	= true;
      }	// caught exception
    }	// get the requested individual
    else
    {	// create new individual with defaults
// create an instance of Person for the new individual
		$person		= new Person('new');
		$isOwner	= canUser('edit');
		$given		= '';
		$surname	= '';
		$birth		= '';
		$death		= '';
		$title		= "Edit New Person";
		$idar		= 0;

// initialize fields from passed parameters
		foreach($_GET as $key => $value)
		{	// loop through parameters
		    if (substr($key,0,4) == 'init')
		    {	// initialize field in database record
				$fld	= strtolower(substr($key, 4));
				try
				{// initialize field in record
				    switch($fld)
				    {
						case 'surname':
						{
						    $surname	= $value;
						    $person->set($fld, $value);
						    break;
						}// surname

						case 'givename':
						{
						    $given	= $value;
						    $person->set($fld, $value);
						    break;
						}// surname

						case 'birthdate':
						case 'chrisdate':
						case 'deathdate':
						case 'burieddate':
						case 'baptismdate':
						case 'confirmationdate':
						case 'endowdate':
						case 'initiatorydate':
						{
						    $fldname	= substr($fld, strlen($fld)-3);
						    // following sets both date and sortdate
						    $person->set($fld, $value);
						    break;
						}// date fields

						case 'birthlocation':
						case 'chrislocation':
						case 'deathlocation':
						case 'buriedlocation':
						{
						    $fldname	= 'idlr' . substr($fld, strlen($fld)-8);
						    $loc	= new Location($value);
						    $person->set($fldname, $loc->getIdlr());
						    break;
						}// location fields

						case 'baptismtemple':
						case 'confirmationtemple':
						case 'endowtemple':
						case 'initiatorytemple':
						{
						    $fldname	= 'idtr' . substr($fld, strlen($fld)-6);
						    $loc	= new Temple($value);
						    $person->set($fld, $loc->getIdtr());
						    break;
						}// LDS temple fields

						default:
						{
						    $person->set($fld, $value);
						    break;
						}// surname
				    }// switch on field name
				}// initialize field in record
				catch(Exception $e)
				{// invalid field name
				    $msg	.= "Invalid parameter $key='$value'. ";
				}// invalid field name
		    }	// initialize field in database record
		}	// loop through parameters
    }	// create new individual

    // identify prefix of name for name summary page
    if (strlen($surname) == 0)
		$prefix	= '';
    else
    if (substr($surname,0,2) == 'Mc')
		$prefix	= 'Mc';
    else
		$prefix	= substr($surname,0,1);

    // check for case of adding  or editting a child

    if ($person &&
		$parentsIdmr > 0)
    {	// identified a family to which the child belongs
		try
		{
		    if ($idirset)
		    {	// child already existed and was already family member
				$family	= new Family($parentsIdmr);
		        $childr	= $family->getChildByIdir($idir);
		    }	// child already existed and was already family member
		    else
		    {	// add new child to family
		        $title		= 'Edit New Child of ' . $fathname;
		        if (strlen($fathname) > 0 && strlen($mothname) > 0)
				    $title	.= ' and ' . $mothname;
		        else
				    $title	.= $mothname;
		    }	// add new child to family
		}	// try
		catch(Exception $e)
		{	// failed to get parent's family
		    $title	= "Failed Locating Child";
		    $msg	.= $e->getMessage();
		}	// failed to get parent's family
    }	// identified a family to which the child belongs

    if ($person)
    {	// individual found
// check for case of adding or editting a child
		if ($idcr > 0)
		{	// child already exists and is already family member
		    $childr		= new Child(array('idcr' => $idcr));
		    $parentsIdmr	= $childr->getIdmr();
		    try {
				$family		= new Family($parentsIdmr);
				$fathname	= $family->get('husbgivenname') . ' ' .
							  $family->get('husbsurname');
				$fathname	= trim($fathname);
				$mothname	= $family->get('wifegivenname') . ' ' .
							  $family->get('wifesurname');
				$mothname	= trim($mothname);
		    }
		    catch (Exception $e) {
				$msg	.= "IDMR=$parentsIdmr not found in database. ";
		    }	// getting parent's family failed
		}	// child already exists and is already family member
		else
		if ($parentsIdmr > 0)
		{	// identified a family to which to add the child 
		    try {
				$family		= new Family($parentsIdmr);
				$fathname	= $family->get('husbgivenname') . ' ' .
							  $family->get('husbsurname');
				$fathname	= trim($fathname);
				$mothname	= $family->get('wifegivenname') . ' ' .
							  $family->get('wifesurname');
				$mothname	= trim($mothname);
		    }
		    catch (Exception $e) {
				$msg	.= "parentsidmr=$value not found in database. ";
		    }	// getting parent's family failed
		    $title		= 'Edit New Child of ';
		    if (strlen($fathname) > 0)
		    {	// child has a father
				$title	.= $fathname;
				if (strlen($mothname) > 0)
				    $title	.= ' and ';
		    }	// child has a father
		    if (strlen($mothname) > 0)
				$title	.= $mothname;
		}	// identified a family to which to add the child

// extract fields from individual for display
		$id		    = $person->get('id'); 
		$idmrpref	= $person->get('idmrpref'); 
		$eSurname	= htmlspecialchars($surname, ENT_QUOTES);
		$eGiven		= htmlspecialchars($given, ENT_QUOTES);
		$gender		= $person->get('gender');
		$private	= $person->get('private');
		if ($private != 0)
		    $privateChecked	= "checked='checked'";
		else
		    $privateChecked	= '';
		$birthd		= new LegacyDate($person->get('birthd'));
		$chrisd		= new LegacyDate($person->get('chrisd'));
		$deathd		= new LegacyDate($person->get('deathd'));
		$buriedd	= new LegacyDate($person->get('buriedd'));
		$deathCause	= htmlspecialchars($person->get('deathcause'),
								   ENT_QUOTES);

// get location names corresponding to IDLR values in record
		try
		{
		    $idlrBirth		= $person->get('idlrbirth');
		    $birthLocation	= new Location($idlrBirth);
		    $birthLocationName	= htmlspecialchars($birthLocation->getName(),
									   ENT_QUOTES); 
		}
		catch(Exception $e)
		{	// failed to get location
		    $msg	.= 'Failed to get birth location. ' .
						   $e->getMessage();
		    $birthLocation	= null;
		    $birthLocationName	= '';
		}	// failed to get location

		try
		{
		    $idlrChris		= $person->get('idlrchris');
		    $chrisLocation	= new Location($idlrChris);
		    $chrisLocationName	= htmlspecialchars($chrisLocation->getName(),
									   ENT_QUOTES); 
		}
		catch(Exception $e)
		{	// failed to get location
		    $msg	.= 'Failed to get christening location. ' .
						   $e->getMessage();
		    $chrisLocation	= null;
		    $chrisLocationName	= '';
		}	// failed to get location

		try
		{
		    $idlrDeath		= $person->get('idlrdeath');
		    $deathLocation	= new Location($idlrDeath);
		    $deathLocationName	= htmlspecialchars($deathLocation->getName(),
									   ENT_QUOTES); 
		}
		catch(Exception $e)
		{	// failed to get location
		    $msg	.= 'Failed to get death location. ' .
						   $e->getMessage();
		    $deathLocationName	= '';
		}	// failed to get location

		try
		{
		    $idlrBuried		= $person->get('idlrburied');
		    $buriedLocation	= new Location($idlrBuried);
		    $buriedLocationName	= htmlspecialchars($buriedLocation->getName(),
									   ENT_QUOTES); 
		}
		catch(Exception $e)
		{	// failed to get location
		    $msg	.= 'Failed to get buried location. ' .
						   $e->getMessage();
		    $buriedLocationName	= '';
		}	// failed to get location

// construct a query of the event table for the current 
// individual.  Each record represents an event or fact
// in the life of the individual
		$eventQuery	= "SELECT * FROM tblER WHERE IDIR=$idir AND IDType=0 ORDER BY `Order`";
		
// query the database
		$eventRes	= $connection->query($eventQuery);
		if ($debug)
		    print "<p>$eventQuery</p>\n";

    }	// individual found

    $title	= htmlspecialchars($title);
    $etitle	= htmlspecialchars($title, ENT_QUOTES);
    htmlHeader($title,
		       array('/jscripts/CommonForm.js',
				     '/jscripts/js20/http.js',
				     '/jscripts/util.js',
				     '/Common.js'));
?>
<body>
  <div class='topcrumbs'>
    <table class='fullwidth'>
      <tr>
		<td>
		<a href='/genealogy.php'>Genealogy</a>:
		<a href='/genCountry.php?cc=CA'>Canada</a>:
		<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
		<a href='/FamilyTree/Services.php'>Services</a>:
		<a href='/FamilyTree/nominalIndex.php?name=<?php print $nameuri; ?>'>
				Nominal Index
		</a>:
		<a href='/FamilyTree/Surnames.php?initial=<?php print $prefix; ?>'>
				Surnames Starting with '<?php print $prefix; ?>'
		</a>:
		<a href='/FamilyTree/Names.php?Surname=<?php print $surname;?>'>
				Surname '<?php print $surname; ?>'
		</a>:
		<a href='/FamilyTree/Person.php?id=<?php print $idir; ?>'>
				<?php print "$given $surname"; ?> 
		</a>:
		</td>
		<td class='right'>
		</td>
      </tr>
    </table>
  </div>
  <div class='body'>
  <table class='fullwidth'>
    <tr>
      <td class='left'>
    <h1>
		<?php print $title; ?> 
    </h1>
      </td>
      <td class='right'>
		<a href='editIndividHelp.html' target='help'>? Help</a>
      </td>
    </tr>
  </table>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
		<?php print $msg; ?> 
  </p>
<?php
    }	// error message to display
 
    if ($isOwner)
    {	// user is authorized to edit this record
?>
<form name='indForm' action='/FamilyTree/updateIndividXml.php' method='post'>
<!-- hidden parameter values -->
    <input type='hidden' name='id' 
		value='<?php print $id; ?>'>
    <input type='hidden' name='idir'
		value='<?php print $idir; ?>'>
    <input type='hidden' name='parentsIdmr'
		value='<?php print $parentsIdmr; ?>'>
    <input type='hidden' name='idcr'
		value='<?php print $idcr; ?>'>
  <table>
    <tr>
		<td>
		    <button type='submit' id='Submit'>
				<u>U</u>pdate Person
		    </button>
		</td>
<?php
		$parents	= $person->getParents();
		$families	= $person->getFamilies();
		if ($parents->count() == 0 &&
		    $families->count() == 0)
		{	// individual is not connected to any others
?>
		<td>
		    <button type='button' id='Delete'>
				<u>D</u>elete Person
		    </button>
		</td>
<?php
		}	// individual is not connected to any others
?>
		<td>
		    <button type='button' id='Merge'>
				<u>M</u>erge
		    </button>
		</td>
    </table>
 
    <table class='form'>
<?php
		if ($id > 0)
		{	// existing database record
?>
      <tr>
		<th class='labelSmall'>
				ID:
		</th>
		<td class='right'>
		    <?php print $id; ?> 
		</td>
		</tr>
<?php
		}	// existing database record
		if ($idir > 0)
		{	// existing database record
?>
      <tr>
		<th class='labelSmall'>
				IDIR:
		</th>
		<td class='right'>
		     <?php print $idir; ?> 
		</td>
      </tr>
<?php
		}	// existing database record
?>
      <tr>
		<th class='labelSmall'>Surname:</th>
		<td class='cell left' colspan='3'>
		  <input type='text' name='Surname' size='50' class='white left'
				value='<?php print $eSurname; ?>'>

		</td>
      </tr>
      <tr>
		<th class='labelSmall'>
		    Given&nbsp;Names:
		</th>
		<td class='cell left' colspan='4' >
		  <input type='text' name='GivenName' size='64'
				class='white left'
				value='<?php print $eGiven; ?>'>

		</td>
		<td class='cell left'>
		  <button type='button' id='Detail1'>
		    Details
		  </button>
		</td>
      </tr>
      <tr>
		<th class='labelSmall'>
		    Gender:
		</th>
		<td class='cell left'>
		  <select name='Gender' size='1'>
		    <option value='0' <?php if ($gender == 0) print ' selected'; ?> >
				Male
		    </option>
		    <option value='1' <?php if ($gender == 1) print ' selected'; ?> >
				Female
		    </option>
		    <option value='2' <?php if ($gender == 2) print ' selected'; ?> >
				Unknown
		    </option>
		  </select>

		</td>
		<th class='labelSmall'>
		    Private:
		</th>
		<td class='cell left'>
		    <input type='hidden' name='Private[]' value='0'>
		    <input type='checkbox' name='Private[]' value='1'
				<?php print $privateChecked; ?>>
		</td>
      </tr>
<?php
		if ($childr)
		{	// permit editing contents of Child record
?>
      <tr>
		<th class='labelSmall'>
		    Relationship&nbsp;to:
		</th>
		<th class='labelSmall'>
		    Final Status:
		</th>
		<td class='cell left'>
		  <select name='CPIdcs' size='1'>
		    <option value='1' <?php if ($childr->getStatus() == 1) print ' selected'; ?>>
 		ordinary
		    </option>
		    <option value='2' <?php if ($childr->getStatus() == 2) print ' selected'; ?>>
 		None
		    </option>
		    <option value='3' <?php if ($childr->getStatus() == 3) print ' selected'; ?>>
 		Stillborn
		    </option>
		    <option value='4' <?php if ($childr->getStatus() == 4) print ' selected'; ?>>
 		Twin
		    </option>
		  </select>
		</td>
      </tr>
<?php
		    if (strlen($fathname) > 0)
		    {	// relationship to father
?>
      <tr>
		<th class='labelSmall' colspan='2'>
				<?php print $fathname; ?>:
		</th>
		<td class='cell left'>
		  <select name='CPRelDad' size='1'>
		    <option value='1' <?php if ($childr->getCPRelDad() == 1) print ' selected'; ?>>
				ordinary
		    </option>
		    <option value='2' <?php if ($childr->getCPRelDad() == 2) print ' selected'; ?>>
				adopted
		    </option>
		    <option value='3' <?php if ($childr->getCPRelDad() == 3) print ' selected'; ?>>
				biological
		    </option>
		    <option value='4' <?php if ($childr->getCPRelDad() == 4) print ' selected'; ?>>
				challenged
		    </option>
		    <option value='5' <?php if ($childr->getCPRelDad() == 5) print ' selected'; ?>>
				disproved
		    </option>
		    <option value='6' <?php if ($childr->getCPRelDad() == 6) print ' selected'; ?>>
				foster
		    </option>
		    <option value='7' <?php if ($childr->getCPRelDad() == 7) print ' selected'; ?>>
				guardian
		    </option>
		    <option value='8' <?php if ($childr->getCPRelDad() == 8) print ' selected'; ?>>
				sealing
		    </option>
		    <option value='9' <?php if ($childr->getCPRelDad() == 9) print ' selected'; ?>>
				step
		    </option>
		    <option value='10' <?php if ($childr->getCPRelDad() == 10) print ' selected'; ?>>
				unknown
		    </option>
		    <option value='11' <?php if ($childr->getCPRelDad() == 11) print ' selected'; ?>>
				private
		    </option>
		    <option value='12' <?php if ($childr->getCPRelDad() == 12) print ' selected'; ?>>
				family member
		    </option>
		  </select>
		</td>
		<th class='labelSmall'>
				Private?
		</th>
		<td class='cell left'>
		  <input type='checkbox' name='CPDadPrivate'
				<?php if ($childr->get('cpdadprivate') == 1) print ' checked';?>>
		</td>
      </tr>
<?php
		    }	// relationship to father
		    if (strlen($mothname) > 0)
		    {	// relationship to mother
?>
      <tr>
		<th class='labelSmall' colspan='2'>
				<?php print $mothname; ?>:
		</th>
		<td class='cell left'>
		  <select name='CPRelMom' size='1'>
		    <option value='1' <?php if ($childr->getCPRelMom() == 1) print ' selected'; ?>>
				ordinary
		    </option>
		    <option value='2' <?php if ($childr->getCPRelMom() == 2) print ' selected'; ?>>
				adopted
		    </option>
		    <option value='3' <?php if ($childr->getCPRelMom() == 3) print ' selected'; ?>>
				biological
		    </option>
		    <option value='4' <?php if ($childr->getCPRelMom() == 4) print ' selected'; ?>>
				challenged
		    </option>
		    <option value='5' <?php if ($childr->getCPRelMom() == 5) print ' selected'; ?>>
				disproved
		    </option>
		    <option value='6' <?php if ($childr->getCPRelMom() == 6) print ' selected'; ?>>
				foster
		    </option>
		    <option value='7' <?php if ($childr->getCPRelMom() == 7) print ' selected'; ?>>
				guardian
		    </option>
		    <option value='8' <?php if ($childr->getCPRelMom() == 8) print ' selected'; ?>>
				sealing
		    </option>
		    <option value='9' <?php if ($childr->getCPRelMom() == 9) print ' selected'; ?>>
				step
		    </option>
		    <option value='10' <?php if ($childr->getCPRelMom() == 10) print ' selected'; ?>>
				unknown
		    </option>
		    <option value='11' <?php if ($childr->getCPRelMom() == 11) print ' selected'; ?>>
				private
		    </option>
		    <option value='12' <?php if ($childr->getCPRelMom() == 12) print ' selected'; ?>>
				family member
		    </option>
		  </select>
		</td>
		<th class='labelSmall'>
				Private?
		</th>
		<td class='cell left'>
		  <input type='checkbox' name='CPMomPrivate'
				<?php if ($childr->get('cpmomprivate') == 1) print ' checked';?>>
		</td>
      </tr>
<?php
		    }	// relationship to mother

// LDS Sealed to Parents Event
		$date		= new LegacyDate($childr->get('parseald'));
		$location	= new Temple($childr->get('idtrparseal'));
?>
      <tr>
		<th class='labelSmall'>
				Sealed to Parents:
		</th>
		<td class='cell left'>
		  <input type='text' name='SealingDate' size='11'
				class='white left'
				value='<?php print $date->toString(); ?>'>
		  <input type='hidden' name='idcr'
				value='<?php print $childr->getIdcr(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='SealingTemple' size='52'
				class='ina leftnc'
				readonly='readonly'
				value='<?php print $location->getName(); ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail17'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear17'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}	// permit editing contents of Child record
?>
      <tr>
		<th class='labelSmall'>
		</th>
		<th class='left'>
				Date:
		</th>
		<th class='left'>
				Location:
		</th>
      </tr>
<?php
		if ($alwaysShowBirth ||
		    $birthd->isPresent() ||
		    $person->get('idlrbirth') > 1)
		{// show birth row
?>
      <tr>
		<th class='labelSmall'>
				Birth:
		</th>
		<td class='cell left'>
		  <input type='text' name='BirthDate' size='11'
				class='white left'
				value='<?php print $birthd->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='BirthLocation' size='52'
				class='white leftnc'
				value='<?php print $birthLocationName; ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail2'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear2'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// show birth row

		if ($alwaysShowChristen ||
		    $chrisd->isPresent() ||
		    $person->get('idlrchris') > 1)
		{// show christening row
?>
      <tr>
		<th class='labelSmall'>
				Christening:
		</th>
		<td class='cell left'>
		  <input type='text' name='ChrisDate' size='11'
				class='white left'
				value='<?php print $chrisd->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='ChrisLocation' size='52'
				class='white leftnc'
				value='<?php print $chrisLocationName; ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail3'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear3'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// show christening row

		if ($alwaysShowBaptism ||
		    strlen($person->get('baptismd')) > 0 ||
		    $person->get('idtrbaptism') > 1)
		{// LDS Baptism
		    $date	= new LegacyDate($person->get('baptismd'));
		    if ($person->get('baptismkind') == 1)
				$location	= new Temple($person->get('idtrbaptism'));
		    else
				$location	= new Location($person->get('idtrbaptism'));
?>
      <tr>
		<th class='labelSmall'>
				LDS Baptism:
		</th>
		<td class='cell left'>
		  <input type='text' name='BaptismDate' size='11'
				class='white left'
				value='<?php print $date->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='BaptismTemple' size='52'
				class='ina leftnc'
				readonly='readonly'
				value='<?php print $location->getName(); ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail15'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear15'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// LDS Baptism

		if ($alwaysShowEndow ||
		    strlen($person->get('endowd')) > 0 ||
		    $person->get('idtrendow') > 1)
		{// LDS Endowment
		    $date	= new LegacyDate($person->get('endowd'));
		    $location	= new Temple($person->get('idtrendow'));
?>
      <tr>
		<th class='labelSmall'>
				LDS Endowment:
		</th>
		<td class='cell left'>
		  <input type='text' name='EndowmentDate' size='11'
				class='white left'
				value='<?php print $date->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='EndowmentTemple' size='52'
				class='ina leftnc'
				readonly='readonly'
				value='<?php print $location->getName(); ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail16'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear16'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// LDS Endowment

		if ($alwaysShowConfirm ||
		    strlen($person->get('confirmationd')) > 0 ||
		    $person->get('idtrconfirmation') > 1)
		{// LDS Confirmation
		    $date	= new LegacyDate($person->get('confirmationd'));
		    if ($person->get('confirmationkind') == 1)
				$location	= new Temple($person->get('idtrconfirmation'));
		    else
				$location	= new Location($person->get('idtrconfirmation'));
?>
      <tr>
		<th class='labelSmall'>
				LDS Confirmation:
		</th>
		<td class='cell left'>
		  <input type='text' name='ConfirmationDate' size='11'
				class='white left'
				value='<?php print $date->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='ConfirmationTemple' size='52'
				class='ina leftnc'
				readonly='readonly'
				value='<?php print $location->getName(); ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail26'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear26'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// LDS Confirmation

		if ($alwaysShowInitiat ||
		    strlen($person->get('initiatoryd')) > 0 ||
		    $person->get('idtrinitiatory') > 1)
		{// LDS Initiatory
		    $date	= new LegacyDate($person->get('initiatoryd'));
		    $location	= new Temple($person->get('idtrinitiatory'));
?>
      <tr>
		<th class='labelSmall'>
				LDS Initiatory:
		</th>
		<td class='cell left'>
		  <input type='text' name='InitiatoryDate' size='11'
				class='white left'
				value='<?php print $date->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='InitiatoryTemple' size='52'
				class='ina leftnc'
				readonly='readonly'
				value='<?php print $location->getName(); ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail27'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear27'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// LDS Initiatory

		while($row = $eventRes->fetch())
		{	// loop through Events
		    $event	= new Event($row);
		    $ider	= $event->getIder();
		    $citType	= $event->getCitType();
		    $type	= ucfirst(trim(Event::$eventText[$event->get('idet')]));
		    $date	= $event->getDate();
		    $desc	= htmlspecialchars($event->getDesc(), 
								   ENT_QUOTES);
		    $descn	= htmlspecialchars($event->getDescription(), 
								   ENT_QUOTES);
		    $locn	= htmlspecialchars($event->getLocation()->getName(), 
								   ENT_QUOTES);

?>
      <tr>
		<th class='labelSmall'>
				<?php print $type; ?>:
		</th>
		<td class='cell left'>
		  <input type='text' name='EventDate<?php print $ider; ?>' size='11'
				class='white leftnc'
				value='<?php print $date; ?>'>
		</td>
		<td class='cell left' colspan='3'>
<?php
		    if (strlen($descn) > 0)
		    {// description present
?>
		  <input type='text' name='EventDescn<?php print $ider; ?>' size='16'
				class='white leftnc'
				value='<?php print $descn; ?>'>

		  <input type='text' name='EventLocation<?php print $ider; ?>' size='32'
				class='white leftnc'
				value='<?php print $locn; ?>'>
<?php
		    }// description present
		    else
		    {// only location present
?>
		  <input type='text' name='EventLocation<?php print $ider; ?>' size='52'
				class='white leftnc'
				value='<?php print $locn; ?>'>
<?php
		    }// only location present
?>
		  <input type='hidden' name='EventChanged<?php print $ider; ?>'
				value='0'>
		</td>
		<td class='cell left'>
		  <button type='button' id='EventDetail<?php print $ider; ?>'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='EventDelete<?php print $ider; ?>'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}	// loop through events

		if ($alwaysShowDeath ||
		    $deathd->isPresent() ||
		    $person->get('idlrdeath') > 1)
		{// show death row
?>
      <tr>
		<th class='labelSmall'>
				Death:
		</th>
		<td class='cell left'>
		  <input type='text' name='DeathDate' size='11'
				class='white left'
				value='<?php print $deathd->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='DeathLocation' size='52'
				class='white leftnc'
				value='<?php print $deathLocationName; ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail4'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear4'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// show death row

		if ($alwaysShowBuried ||
		    $buriedd->isPresent() ||
		    $person->get('idlrburied') > 1)
		{// show buried row
?>
      <tr>
		<th class='labelSmall'>
				Burial:
		</th>
		<td class='cell left'>
		  <input type='text' name='BuriedDate' size='11'
				class='white left'
				value='<?php print $buriedd->toString(); ?>'>
		</td>
		<td class='cell left' colspan='3'>
		  <input type='text' name='BuriedLocation' size='52'
				class='white leftnc'
				value='<?php print $buriedLocationName; ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail5'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear5'>
				Delete
		  </button>
		</td>
      </tr>
<?php
		}// show buried row
?>
      <tr>
		<th class='labelSmall'>
		</th>
		<td class='cell left'>
		  <button type='button' id='AddEvent'>
				Add <u>E</u>vent
		  </button>
		</td>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Order'>
				<u>O</u>rder Events by Date
		  </button>
		</td>
      </tr>
      <tr>
		<th class='labelSmall'>
				User Ref:
		</th>
		<td class='cell left'>
		  <input type='text' name='UserRef' size='11'
				class='white leftnc'
				value='<?php print htmlspecialchars($person->get('userref'),
									    ENT_QUOTES); ?>'>
		</td>
		<th class='labelSmall'>
				Ancestral Ref:
		</th>
		<td class='cell left'>
		  <input type='text' name='AncestralRef' size='11'
				class='white left'
				value='<?php print htmlspecialchars($person->get('ancestralref'),
									    ENT_QUOTES); ?>'>
		</td>
      </tr>
      <tr>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Detail6'>
				Edit General <u>N</u>otes
		  </button>
		</td>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Detail7'>
				Edit <u>R</u>esearch Notes
		  </button>
		</td>
      </tr>
      <tr>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Detail8'>
				Edit Medical Notes
		  </button>
		</td>
		<th class='labelSmall'>
				Death Cause:
		</th>
		<td class='cell left' colspan='2'>
		  <input type='text' name='DeathCause' size='36'
				class='white leftnc'
				value='<?php print $deathCause; ?>'>
		</td>
		<td class='cell left'>
		  <button type='button' id='Detail9'>
				Details
		  </button>
		</td>
		<td class='cell left'>
		  <button type='button' id='Clear9'>
				Delete
		  </button>
		</td>
      </tr>
      <tr>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Parents'>
<?php
		    if ($person->getParents()->count() == 0)
		    {
?>
				Add <u>P</u>arents
<?php
		    }	// no existing marriages
		    else
		    {	// at least one marriage
?>
				Edit <u>P</u>arents
<?php
		    }	// at least one marriage
?>
		  </button>
		</td>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Marriages'>
<?php
		    if ($person->getFamilies()->count() == 0)
		    {
?>
				Add Spouse or Child
<?php
		    }	// no existing marriages
		    else
		    {	// at least one marriage
?>
				Edit <u>F</u>amilies
<?php
		    }	// at least one marriage
?>
		  </button>
		  <input type='hidden' name='IDMRPref' 
				value='<?php print $idmrpref; ?>'>
		</td>
      </tr>
      <tr>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Pictures'>
				Edit P<u>i</u>ctures
		  </button>
		</td>
		<td class='cell left' colspan='2'>
		  <button type='button' id='Address'>
				<?php if ($idar > 0) print "Edit"; else print "Add"; ?> 
				<u>A</u>ddress
		  </button>
		  <input type='hidden' name='IDAR' 
				value='<?php print $idar; ?>'>
		</td>
      </tr>
    </table>
</form>
<?php
		if ($idirset)
		{	// an existing individual
?>
<form name='grantForm' action='/grantIndivid.php' method='post'>
    <input type='hidden' name='idir' value='<?php print $person->getIdir(); ?>'>
<p>
  <button type='submit' id='Grant'>Grant Access</button>
</p>
</form>

<?php
		}	// an existing individual
    }	// current user is an owner of record
    else
    {	// current user does not own record
?>
<p class='message'>
    You are not authorized to update this individual.
    Contact one of the existing owners.
</p>
<?php
    }	// current user does not own record
?>
</div>
<div class='botcrumbs'>
<p>The data in this web site is generated on demand from a database created by
<a href='http:/www.legacyfamilytree.com/'>Legacy Family Tree
<?php
    print $release . ' ';
?>
</a> 
by Millenia Corp.</p>
<table class='fullwidth'>
  <tr>
    <td class='label'>
		<a href='mailto:webmaster@jamescobban.net?subject=<?php print $etitle ?>'>
				Contact Author
		</a>
		<br/>
		<a href='/genealogy.php'>Genealogy</a>:
		<a href='/genCountry.php?cc=CA'>Canada</a>:
		<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
		<a href='/FamilyTree/Services.php'>Services</a>:
		<a href='/FamilyTree/nominalIndex.php?name=<?php print $nameuri; ?>'>
				Nominal Index
		</a>:
		<a href='/FamilyTree/Surnames.php?initial=<?php print $prefix; ?>'>
				Surnames Starting with '<?php print $prefix; ?>'
		</a>:
		<a href='FamilyTree/Names.php?Surname=<?php print $surname;?>'>
				Surname '<?php print $surname; ?>'
		</a>:
    </td>
    <td class='right'>
		<img SRC='/logo70.gif' height='70' width='70' alt='James Cobban Logo'>
    </td>
  </tr>
</table>
</div>
<?php
    pageBot();
?>
<div class='hidden' id='templates'>
		    <button type='button' id='AddressRepl'>
				Edit <u>A</u>ddress
		    </button>
</div> <!-- id='templates' -->
<div class='balloon' id='Helpid'>
<p>This read-only field displays the internal record number of the database
record for this individual.
</p>
</div>
<div class='balloon' id='Helpidir'>
<p>This read-only field displays the numeric key that is used by other database
records to locate the database record for this individual.  If the database
was originally loaded from a GEDCOM file this is the numeric key used for
referencing the INDI record in that file.
</p>
</div>
<div class='balloon' id='HelpparentsIdmr'>
<p>This hidden field records the internal record number of the database
record for the family record of the parents of this individual.
</p>
</div>
<div class='balloon' id='HelpSurname'>
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
value, stored in field 'SoundsLike' in the individual record is updated.
Also if the surname does not already appear in the database, a record is
added into the table 'tblNR'.
</p>
</div>
<div class='balloon' id='HelpGivenName'>
<p>Edit the given names of the individual. 
</p>
</div>
<div class='balloon' id='HelpPrefix'>
<p>The name prefix.  I don't know what this is usually used for.
</p>
</div>
<div class='balloon' id='HelpTitle'>
<p>The title is the portion of a name that represents an honorific or rank.
Examples include __NAMESPACE__ . "/Dr.", "Rev'd", "Capt.", and "Sir".
</p>
</div>
<div class='balloon' id='HelpBirthDate'>
<p>The birth date.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpBirthLocation'>
<p>The location where the individual was born.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpChrisDate'>
<p>The date of christening.  Note that because of the key contributions
to genealogy by the Church of Jesus Christ of Latter Day Saints (LDS), the
term "christening" is used to describe a baptism in a Christian church.
The term "baptism" is used exclusively for the LDS sacrament.
<p>  
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpChrisLocation'>
<p>
The location where the individual was christened.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpDeathDate'>
<p>The date of death.
<p>
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpDeathLocation'>
<p>
The location where the individual died.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpBuriedDate'>
<p>The date the individual was buried.
<p>  
If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpBuriedLocation'>
<p>
The location where the individual was buried.  This is typically the name and
location of the cemetery.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpGender'>
<p>
This is a selection list from which to choose the gender of the individual.
</p>
</div>
<div class='balloon' id='HelpPictures'>
<p>
Clicking on this button opens up a
<a href='editPicturesHelp.html' target='help'>dialog</a>
that permits you to add or delete images associated with this individual.
</p>
</div>
<div class='balloon' id='HelpDeathCause'>
<p>
The cause of death, usually from the death certificate.
</p>
</div>
<div class='balloon' id='HelpCPIdcs'>
<p>
Select the most appropriate status of the child.  This is the status
as a result of birth.
</p>
</div>
<div class='balloon' id='HelpDetail'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to enter more details about the associated event.
This includes textual notes and source citations.
</p>
</div>
<div class='balloon' id='HelpClear'>
<p>
Clicking on this button clears all of the information about the 
associated event.  The date and location are cleared to empty strings and
any citations to the event are deleted from the database.
</p>
</div>
<div class='balloon' id='HelpBaptismDate'>
<p>The date of the LDS baptism.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpBaptismLocation'>
<p>The location where the individual was baptized into the Church of Latter Day
Saints.  This is usually at a temple, but may be at another location.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpEndowmentDate'>
<p>The date of the LDS endowment.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpEndowmentLocation'>
<p>The temple where the Church of Latter Day Saints endowment was performed.
</p>
</div>
<div class='balloon' id='HelpConfirmationDate'>
<p>The date of the LDS confirmation.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpConfirmationLocation'>
<p>The location where the individual was confirmed in the Church of Latter Day
Saints.  This is usually at a temple, but may be at another location.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpInitiatoryDate'>
<p>The date of the LDS Initiatory.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpInitiatoryLocation'>
<p>The temple where the Church of Latter Day Saints initiatory was performed.
</p>
</div>
<div class='balloon' id='HelpEventDate'>
<p>The date of the event.
<p>If known this is normally expressed as numeric day,
three character abbreviation of month name, and the year.  For example
"13 Aug 1894".  However you can also specify "about 1876" or "between 1854 and 1882".  
Note that a date of the format "nn/nn/nnnn" is interpreted as "dd/mm/yyyy", not
as the American style "mm/dd/yyyy" except if the second number is in the range
13 to 31 and the first number is in the range 1 to 12.
</p>
<p>For more details see <a href='datesHelp.html' target='help'>"Entering Dates"</a>.
</p>
</div>
<div class='balloon' id='HelpEventDescn'>
<p>The description text associated with the event.  This is any information
that does not specify where the event took place.  For example for an
occupation event it is the title of the occupation.
</p>
</div>
<div class='balloon' id='HelpEventLocation'>
<p>The location where the event took place.
</p>
<?php
    require __NAMESPACE__ . '/locationHelp.html';
?>
</div>
<div class='balloon' id='HelpEventDetail'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to enter more details about the event.
This includes textual notes, and source citations;
</p>
</div>
<div class='balloon' id='HelpEventDelete'>
<p>
Clicking on this button removes all of the information about the 
associated event and
any citations to the event are deleted from the database.
</p>
</div>
<div class='balloon' id='HelpAddEvent'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to add a new event onto this individual.
This includes textual notes, and source citations.
</p>
</div>
<div class='balloon' id='HelpOrder'>
<p>Clicking on this button reorders the events and facts according to the date
on which they occurred or were observed.  This makes the displayed description
of the individual more coherent.
</p>
</div>
<div class='balloon' id='HelpUserRef'>
<p>
The value of this field is a unique identifier that has meaning to you
as the author of the family tree.  For example it might represent how
the individual is related to you using one of the conventions for
representing descent from a common ancestor.
</p>
</div>
<div class='balloon' id='HelpAncestralRef'>
<p>
The value of this field is a reference to the identifier of this individual
in the Church of Latter Day Saints Ancestral File database.
</p>
</div>
<div class='balloon' id='HelpNotes'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to enter extensive textual notes on this individual.
</p>
</div>
<div class='balloon' id='HelpReferences'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to enter textual research notes about your
investigation of this individual.
</p>
</div>
<div class='balloon' id='HelpMedical'>
<p>
Clicking on this button opens up a
<a href='editEventHelp.html' target='help'>dialog</a>
that permits you to enter textual notes about the medical history
of this individual.
</p>
</div>
<div class='balloon' id='HelpCPRelDad'>
<p>
Use this selection box to specify the nature of the relationship between
this individual and his/her father.
</p>
</div>
<div class='balloon' id='HelpCPDadPrivate'>
<p>
If this checkbox shows a checkmark then the nature of the relationship
between this individual and his/her father will not be published.
</p>
</div>
<div class='balloon' id='HelpCPRelMom'>
<p>
Use this selection box to specify the nature of the relationship between
this individual and his/her mother.
</p>
</div>
<div class='balloon' id='HelpCPMomPrivate'>
<p>
If this checkbox shows a checkmark then the nature of the relationship
between this individual and his/her mother will not be published.
</p>
</div>
<div class='balloon' id='HelpParents'>
<p>
Clicking on this button opens up a
<a href='editParentsHelp.html' target='help'>dialog</a>
that permits you to edit or add a set of parents for this individual.
</p>
</div>
<div class='balloon' id='HelpMarriages'>
<p>
Clicking on this button opens up a
<a href='editMarriagesHelp.html' target='help'>dialog</a>
that permits you to edit or add a family
for which this individual functions as a spouse or parent.
</p>
</div>
<div class='balloon' id='HelpEvents'>
<p>
Clicking on this button opens up a
<a href='editEventsHelp.html' target='help'>dialog</a>
that permits you to edit or add events in the life of this individual.
</p>
</div>
<div class='balloon' id='HelpAddress'>
<p>
Clicking on this button opens up a
<a href='AddressHelp.html' target='help'>dialog</a>
that permits you to add or update the mailing address 
and other contact information for this individual.
</p>
</div>
<div class='balloon' id='HelpSubmit'>
<p>
Clicking on this button applies all of the changes you have made in the
form to the database.
</p>
</div>
<div class='balloon' id='HelpMerge'>
<p>
Clicking on this button opens up a dialog that permits you to merge this
individual with another individual in the database.  You do this when as
a result of your research you realize that two records in the database
actually describe the same individual.
</p>
</div>
<div class='balloon' id='HelpGrant'>
<p>
Clicking on this button displays a dialog permitting you to grant authority
to see the private information and update the current individual and the
current individuals ancestors and descendants to another researcher.
</p>
</div>
<div id='loading' class='popup'>
Loading...
</div>
</body>
</html>
