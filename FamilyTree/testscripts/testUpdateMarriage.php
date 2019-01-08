<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateMarriage.php						*
 *									*
 *  Display a web page for editting a specific marriage			*
 *  from the Legacy database						*
 *									*
 *  Parameters:								*
 *	idmr	if specified indicates to edit a specific		*
 *		marriage that already exists				*
 *	idir	if specified indicates to create a new			*
 *		marriage in which the person identified			*
 *		by this IDIR value is a spouse				*
 *	child	if specified indicates to create a new			*
 *		marriage in which the person identified			*
 *		by this IDIR value is a child.				*
 *									*
 *  History:								*
 *	2011/06/12	created						*
 *	2017/09/12	use get( and set(				*
 *	2017/09/28	change class LegacyEvent to class Event		*
 *									*
 * Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/Event.inc';
    require_once __NAMESPACE__ . '/LegacyHeader.inc';

    // get the parameters passed to the script
    $idir	= null;	// individual as primary spouse in family
    $child	= null;	// individual as primary spouse in family
    $given	= null;	// given name of individual
    $surname	= null;	// given name of individual
    $idmr	= 0;	// marriage to display
    $idmrpref	= 0;	// preferred marriage for the individual

    foreach($_GET as $key => $value)
    {			// loop through all parameters passed to script
	switch($key)
	{		// take action on specific parameter
	    case 'id':
	    case 'idir':
	    {		// identify primary spouse
		$idir		= $value;
		break;
	    }		// identify primary spouse

	    case 'child':
	    {		// identify child
		$child		= $value;
		break;
	    }		// identify child

	    case 'given':
	    {		// default given name
		$given		= $value;
		break;
	    }		// default given name of individual

	    case 'surname':
	    {
		$surname	= $value;
		break;
	    }		// default surname of individual

	    case 'idmr':
	    {// identify specific marriage to select for display
		$idmr		= $value;
		break;
	    }// identify specific marriage

	    // ignore unrecognized parameters
	}// take action on specific parameter
    }	// loop through all parameters passed to script

    // validate the parameters
    if (!is_null($child) &&
	strlen($child) > 0 &&
	ctype_digit($child))
    {	// the identified individual is a child
	$isOwner	= canUser('edit') && 
			  RecOwner::chkOwner($child, 'tblIR');
	try
	{
	    $person		= new Person($child, 'idir');
	    if ($given === null)
		$given		= $person->getGivenName();
	    if ($surname === null)
		$surname	= $person->getSurname();
	    $fathSurname	= $surname;
	    $sex		= $person->get('gender');
	    $birth		= new LegacyDate($person->get('birthd'));
	    $birth		= $birth->toString();
	    $death		= new LegacyDate($person->get('deathd'));
	    $death		= $death->toString();
	    $idmrpref		= $person->get('idmrpref');

	    if (strtolower(substr($surname, 0, 2)) == 'mc')
		$prefix	= 'Mc';
	    else
		$prefix	= substr($surname, 0, 1);

	    $title	= "Edit Parents for $given $surname";
	}
	catch(Exception $e)
	{	// error in new Person
	    $title	= 'Invalid Identification of Child';
	    $msg	= "child=$child: " . $e->getMessage();
	    $isOwner	= true;
	    $person	= null;
	}	// error in new Person

	if (!$isOwner)
	    $msg	.= 'You are not authorized to edit the parents of '.
			$given . ' ' . $surname;

	$families	= array();
    }	// get the requested child
    else
    if (!is_null($idir) &&
	strlen($idir) > 0 &&
	ctype_digit($idir))
    {	// the identified individual is the primary spouse
	$isOwner	= canUser('edit') && 
			  RecOwner::chkOwner($idir, 'tblIR');
	    $person		= new Person($idir, 'idir');
	    if ($given === null)
		$given		= $person->getGivenName();
	    if ($surname === null)
		$surname	= $person->getSurname();
	    $fathSurname	= '';	// surname of husband
	    $sex		= $person->get('gender');
	    $birth		= new LegacyDate($person->get('birthd'));
	    $birth		= $birth->toString();
	    $death		= new LegacyDate($person->get('deathd'));
	    $death		= $death->toString();
	    $idmrpref		= $person->get('idmrpref');
	    if ($idmrpref == 0)
	    {	// set preferred marriage to first marriage if any
		$families	= $person->getFamilies();
		if (count($families) > 0)
		{// at least one marriage
		    $family	= current($families);
		    if ($family)
		    {	// have first family
			$idmrpref	= $family->getIdmr(); 
		// update field in individual
			$person->set('idmrpref', $idmrpref);
			$person->save(false);
		    }	// have first family
		}// at least one marriage
	    }	// set preferred marriage to first marriage if any

	    if (strtolower(substr($surname, 0, 2)) == 'mc')
		$prefix	= 'Mc';
	    else
		$prefix	= substr($surname, 0, 1);

	    $families	= $person->getFamilies();

	$title	= "Edit Marriages for $given $surname";

	if (!$isOwner)
	    $msg	.= 'You are not authorized to edit the marriages of '.
			$given . ' ' . $surname;
    }			// id of spouse
    else
    {			// required parameter missing or invalid
	$idir		= '';
	$person		= null;
	$prefix		= '';
	$surname	= '';
	$birth		= '';
	$death		= '';
	$title		= "idir Parameter Missing or Invalid";
	$msg		= "idir parameter missing or invalid";
    }	// missing required parameter

    // get the initial marriage to expand 
    if ($idmr > 0)
	$idmr	= $idmrpref;

    $title	= htmlspecialchars($title, ENT_QUOTES);
    htmlHeader($title,
 		array(	'/jscripts/js20/http.js',
 			'/jscripts/CommonForm.js',
 			'/jscripts/util.js',
 			'/jscripts/Cookie.js',
 			'/Common.js',
 			'commonMarriage.js',
 			'testUpdateMarriage.js'));
?>
<body>
  <div class='body'>
  <table class='fullwidth'>
    <tr>
      <td class='left'>
    <h1>
	<?php print $title; ?>
    </h1>
      </td>
      <td class='right'>
	<a href='testUpdateMarriageHelp.html' target='help'>? Help</a>
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
    }	// error message to be displayed
    else
    if ($person)
    {	// primary spouse found
showTrace();
?>
  <form name='indForm' action='updateMarriages.php' method='post'>
    <div>
      <input type='hidden' name='idir' value='<?php print $idir; ?>'>
      <input type='hidden' name='sex' value='<?php print $sex; ?>'>
    </div>
    <table class='details' id='marriageList'>
	<thead>
	    <tr>
		<th class='colhead'>
		    Marriage Date
		</th>
		<th class='colhead'>
		    Spouse Name
		</th>
		<th class='colhead'>
		    Pref
		</th>
		<th class='colhead' colspan='2'>
		    Actions
		</th>
	    </tr>
	</thead>
	<tbody id='marriageListBody'>
<?php
	    foreach($families as $idmr => $family)
	    {
		if ($person->getGender() == Person::FEMALE)
		{	// female
		    $spsSurname	= $family->get('husbsurname');
		    $spsGiven	= $family->get('husbgivenname');
		    $spsid	= $family->get('idirhusb');
		    $spsclass	= 'male';
		}	// female
		else
		{	// male
		    $spsSurname	= $family->get('wifesurname');
		    $spsGiven	= $family->get('wifegivenname');
		    $spsid	= $family->get('idirwife');
		    $spsclass	= 'female';
		}	// male
    
	// information about spouse
		try
		{
		    $spouse	= new Person($spsid,
					    'idir');
showTrace();
		}
		catch(Exception $e)
		{
		    $spouse	= null;
		}
		$mdateo	= new LegacyDate($family->get('mard'));
		$mdate	= $mdateo->toString();

		if (strlen($mdate) == 0)
		    $mdate	= 'Unknown';
?>
	<tr id='marriage<?php print $idmr; ?>'>
	  <td id='mdate<?php print $idmr; ?>'>
		<?php print $mdate; ?> 
	  </td>
	  <td>
	    <a href='Person.php?id=<?php print $spsid; ?>'
		class='<?php print $spsclass; ?>'
		id='spousename<?php print $idmr; ?>'>
		<?php print $spsGiven; ?> <?php print $spsSurname; ?>
	    </a>
	  </td>
	  <td class='center'>
	    <input type='checkbox' name='Pref<?php print $idmr; ?>'
		<?php if ($idmr == $idmrpref) print "checked"; ?>>
	  </td>
	  <td>
	    <button type='button' id='Edit<?php print $idmr; ?>'>
		Edit Marriage
	    </button>
	  </td>
	  <td>
	    <button type='button' id='Delete<?php print $idmr; ?>'>
		Delete Marriage
	    </button>
	  </td>
	</tr>
<?php
	    }	// loop through marriages
?>
      </tbody>
      <tfoot>
	<tr id='AddMarriageRow'>
	  <td>
	  </td>
	  <td>
	  </td>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='Add'>
		<u>A</u>dd Marriage
	    </button>
	  </td>
	</tr>
      </tfoot>
    </table>
    <p>
      <button type='button' id='Finish'>
    	<u>C</u>lose
      </button>
    &nbsp;
      <button type='button' id='Reorder'>
    	<u>O</u>rder Marriages by Date
      </button>
    </p>
  </form> <!-- name='indForm' -->
<!--
 *	The current family is displayed in a separate form		*
 *	 		*
-->
<form name='famForm' action='/FamilyTree/updateMarriageXml.php' method='post'>
    <table id='formTable'>
	<tbody>
	    <tr id='IdmrRow'>
		<th class='labelSmall'>
		    IDMR:
		</th>
		<td class='left'>
		    <input type='text' name='idmr' size='6'
			readonly='readonly' value='<?php print $idmr; ?>'>
	    </tr>
	    <tr id='Husb'>
	  <th class='labelSmall'>
		    Husband:
	    <input type='hidden' name='IDIRHusb'
			value='0'>
	  </th>
	  <td class='left'>
	    <input type='text' name='HusbGivenName' size='30'
			maxlength='120'
			class='white left'
			value=''>
	  </td>
	  <td>
	    <input type='text' name='HusbSurname' size='20'
			maxlength='120'
			class='white left'
			value='<?php print $fathSurname; ?>'>
	  </td>
	  <td>
	    <button type='button' id='editHusb'>
		    Edit Husband
	    </button>
	  </td>
	</tr>
	<tr id='HusbMarrSurnameRow'>
	  <td>
	  </td>
	  <th class='labelSmall'>
	    Husband's Married Surname:
	  </th>
	  <td>
	    <input type='text' name='HusbMarrSurname' size='20'
			maxlength='255'
			class='white left'
			value='<?php print $surname; ?>'>
	  </td>
	</tr>
	<tr id='SelectHusbandRow'>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='changeHusb'>
		    Select Existing Husband
	    </button>
	  </td>
	  <td>
	    <button type='button' id='createHusb'>
		    Create New <u>H</u>usband
	    </button>
	  </td>
	  <td>
	    <button type='button' id='detachHusb'>
		    Detach Husband
	    </button>
	  </td>
	</tr> <!-- end of Husband row -->
	<tr id='Wife'>
	  <th class='labelSmall'>
		    Wife:
	    <input type='hidden' name='IDIRWife'
			value='0'>
	  </th>
	  <td class='left'>
	    <input type='text' name='WifeGivenName' size='30'
			maxlength='120'
			class='white left'
			value=''>
	  </td>
	  <td>
	    <input type='text' name='WifeSurname' size='20'
			maxlength='120'
			class='white left'
			value=''>
	  </td>
	  <td>
	    <button type='button' id='editWife'>
		    Edit Wife
	    </button>
	</tr>
	<tr id='WifeMarrSurnameRow'>
	  <td>
	  </td>
	  <th class='labelSmall'>
	    Wife's Married Surname:
	  </th>
	  <td>
	    <input type='text' name='WifeMarrSurname' size='20'
			maxlength='255'
			class='white left'
			value='<?php print $surname; ?>'>
	  </td>
	</tr>
	<tr id='selectWifeRow'>
	  <td>
	  </td>
	  <td>
	    <button type='button' id='changeWife'>
		    Select Existing Wife
	    </button>
	  </td>
	  <td>
	    <button type='button' id='createWife'>
		    Create New <u>W</u>ife
	    </button>
	  </td>
	  <td>ac
	    <button type='button' id='detachWife'>
		    Detach Wife
	    </button>
	  </td>
	    </tr> <!-- end of Wife row -->
	    <tr id='Marriage'>
		<th class='labelSmall'>
		    Married:
		</th>
	  <td class='left'>
	    <input type='text' name='MarD' size='20'
			maxlength='100'
			class='white left'
			value=''>
		    at
	  </td>
	  <td colspan='2'>
	    <input type='text' name='MarLoc' size='40'
			maxlength='255'
			class='white leftnc'
			value=''>
	  </td>
	  <td>
	    <button type='button' id='marriageDetails'>
		    Details
	    </button>
	  </td>
	    </tr> <!-- end of Marriage row -->
	<!-- rows for other events are inserted here -->
	<tr id='AddEventRow'>
	  <th class='labelSmall'>
		    Actions:
	  </th>
	    <td class='cell left' colspan='2'>
		<button type='button' id='AddEvent'>
		    <u>A</u>dd Event
		</button>
		&nbsp;
		<button type='button' id='OrderEvents'>
		    Order Events by <u>D</u>ate
		</button>
	    </td>
	</tr>
	    <tr id='Status'>
		<th class='labelSmall'>
		    Status:
		</th>
		<td colspan='3'>
		    <select name='IDMS' size='1'>
		<option value='1' selected='selected'>
		    no special status
		</option>
		<option value='2'>
		    Annulled
		</option>
		<option value='3'>
		    Common Law
		</option>
		<option value='4'>
		    Divorced
		</option>
		<option value='5'>
		    Married
		</option>
		<option value='6'>
		    Other
		</option>
		<option value='7'>
		    Separated
		</option>
		<option value='8'>
		    Unmarried
		</option>
		<option value='9'>
		    Divorce
		</option>
		<option value='10'>
		    Separation
		</option>
		<option value='11'>
		    Private
		</option>
		<option value='12'>
		    Partners
		</option>
		<option value='13'>
		    Death of one spouse
		</option>
		<option value='14'>
		    Single
		</option>
		<option value='15'>
		    Friends</option>
		</option>
		    </select>
		</td>
	</tr> <!-- end of Ending Status row -->
	<tr id='NameRule'>
	    <th class='labelSmall'>
		Name Rule:
	    </th>
	    <td colspan='3'>
	      <select name='MarriedNameRule' size='1'>
		<option value='0'>
		    Don't Generate Married Names
		</option>
		<option value='1' selected>
		    Replace Wife's Surname with Husband's Surname
		</option>
	      </select>
	    </td>
	</tr> <!-- end of Name Rule row -->
	<tr id='Notes'>
	    <th class='labelSmall'>
		Notes:
	    </th>
	    <td colspan='3'>
    <!-- note that when initializing a <textarea>, unlike other tags
	 the space around the text value becomes part of the value
	 of the tag, so there can be no space characters between the 
	 opening and closing tags and the value of the field -->
		    <textarea name='Notes' cols='60' rows='4'></textarea>
		</td>
		<td>
		    <button type='button' name='noteDetails'>
		    Details
		    </button>
		</td>
	    </tr> <!-- end of Notes row -->
	    <tr id='ChildHdr'>
		<th class='label'>Children:</th>
		<th class='labelSmall'>
		    Has no children:
		</th>
		<td colspan='2'>
		    <input type='checkbox' name='noChildren'>
		</td>
		<td>
		    <button type='button' name='noChildDetails'>
		    Details
		    </button>
		</td>
	    </tr> <!-- end of children header row -->
	</tbody>
    </table>
<p class='label'>Children:</p>
    <table class='details' id='children'>
	<thead>
	    <tr>
		<th class='colhead'>
		    &nbsp;&nbsp;&nbsp;Given&nbsp;&nbsp;&nbsp;
		</th>
		<th class='colhead'>
		    &nbsp;&nbsp;Surname&nbsp;&nbsp;
		</th>
		<th class='colhead'>
		    &nbsp;Birth&nbsp;
		</th>
		<th class='colhead'>
		    &nbsp;Death&nbsp;
		</th>
		<th class='colhead' colspan='2'>
		    Actions
		</th>
	</thead>
	<tbody>
      </tbody>
      <tfoot>
	<tr>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
		<button type='button' name='addChild'>
		    Add <u>E</u>xisting Child
		</button>
	    </td>
	    <td>
		<button type='button' name='addNewChild'>
		    Add <u>N</u>ew Child
		</button>
	    </td>
	</tr>
	</tfoot>
    </table> <!-- id='children' -->
<p>
  <button type='submit' name='Submit'>
	<u>U</u>pdate Marriage
  </button>
&nbsp;
  <button type='button' name='orderChildren'>
	<u>O</u>rder Children by Birth Date
  </button>
&nbsp;
  <button type='button' name='Pictures'>
	Edit <u>P</u>ictures
  </button>
&nbsp;
  <button type='button' name='Events'>
	Edit Other E<u>v</u>ents
  </button>
</p>
</form>
<?php
    }	// family found
?>
    </div>
<div class='hidden' id='templates'>
  <table id='templateRows'>
    <!--
     *	layout of the table row to display a summary of a marriage
     *  in the table at the top of the dialog.
     *	Putting the layout here permits more user
     *	customization, including support for alternate languages.
     *	This row layout should match the rows in <tbody id='marriageListBody'>
    -->
	<tr id='marriage$idmr'>
	  <td id='mdate$idmr'>
		$mdate
	  </td>
	  <td>
	    <a href='Person.php?id=$spsid'
		class='$spsclass'
		id='spousename$idmr'>
		$spsGiven $spsSurname
	    </a>
	  </td>
	  <td class='center'>
	    <input type='checkbox' name='Pref$idmr'>
	  </td>
	  <td>
	    <button type='button' id='Edit$idmr'>
		Edit Marriage
	    </button>
	  </td>
	  <td>
	    <button type='button' id='Delete$idmr'>
		Delete Marriage
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
    <tr id='child$idcr'>
	<td class='name'>
	    <input class='$gender' name='CGiven$idcr' value='$givenname'
			type='text' size='18'
			maxlength='120'>
	</td>
	<td class='name'>
	    <input class='$gender' name='CSurname$idcr' value='$surname'
			type='text' size='10'
			maxlength='120'>
	</td>
	<td class='name'>
	    <input class='white left' name='Cbirth$idcr' value='$birthd'
			type='text' size='8'
			maxlength='100'>
	</td>
	<td class='name'>
	    <input class='white left' name='Cdeath$idcr' value='$deathd'
			type='text' size='8'
			maxlength='100'>
	</td>
	<td>
	    <button type='button' id='editChild$idir'>
		Edit Child
	    </button>
	</td>
	<td>
	    <button type='button' id='detChild$idcr'>
		Detach Child
	    </button>
	</td>
    </tr>
    <!-- end of template for child row -->

    <!--
     *	layout of the table row to display a child added to this marriage
     *  by Javascript code, but not backed by a LegacyChild record.
     *	request is received.  Putting the layout here permits more user
     *	customization, including support for alternate languages.
    -->
    <tr id='aChild$idir'>
	<td class='name'>
	    <input class='$gender' name='AGiven$idir' value='$givenname'
			type='text' size='18'
			maxlength='120'>
	</td>
	<td class='name'>
	    <input class='$gender' name='ASurname$idir' value='$surname'
			type='text' size='10'
			maxlength='120'>
	</td>
	<td class='name'>
	    <input class='white left' name='Abirth$idir' value='$birthd'
			type='text' size='8'
			maxlength='100'>
	</td>
	<td class='name'>
	    <input class='white left' name='Adeath$idir' value='$deathd'
			type='text' size='8'
			maxlength='100'>
	</td>
	<td>
	    <button type='button' id='editChild$idir'>
		Edit Child
	    </button>
	</td>
    </tr>
    <!-- end of template for added child row -->

    <!-- template for sealed to spouse event row -->
	<tr id='SealedRow$temp'>
	  <th class='labelSmall'>
		Sealed&nbsp;to&nbsp;Spouse (LDS):
	  </th>
	  <td class='left'>
		<input type='text' size='20' maxlength='100'
			name='SealD'
			class='white left'
			value='$eventd'>
		    at
	  </td>
	  <td colspan='2'>
	    <select name='IDTRSeal' size='1'>
		<option value='1'>
		</option>
		<option value='2'>Aba, Nigeria
		</option>
		<option value='3'>Accra, Ghana
		</option>
		<option value='4'>Adelaide, Australia
		</option>
		<option value='5'>Cardston, Alberta
		</option>
		<option value='6'>Albuquerque, New Mexico
		</option>
		<option value='7'>Anchorage, Alaska
		</option>
		<option value='8'>Apia, Samoa
		</option>
		<option value='9'>Mesa, Arizona
		</option>
		<option value='10'>Asuncion, Paraguay
		</option>
		<option value='11'>Atlanta, Georgia
		</option>
		<option value='12'>Buenos Aires, Argentina
		</option>
		<option value='13'>Billings, Montana
		</option>
		<option value='14'>Birmingham, Alabama
		</option>
		<option value='15'>Bismarck, North Dakota
		</option>
		<option value='16'>Bogota, Colombia
		</option>
		<option value='17'>Boise, Idaho
		</option>
		<option value='18'>Boston, Massachusetts
		</option>
		<option value='19'>Bountiful, Utah
		</option>
		<option value='20'>Brisbane, Australia
		</option>
		<option value='21'>Baton Rouge, Louisiana
		</option>
		<option value='22'>Campinas, Brazil
		</option>
		<option value='23'>Caracas, Venezuela
		</option>
		<option value='24'>Chicago, Illinois
		</option>
		<option value='25'>Ciudad Juárez, Mexico
		</option>
		<option value='26'>Cochabamba, Bolivia
		</option>
		<option value='27'>Colonia Juárez, Chihuahua, Mexico
		</option>
		<option value='28'>Columbia, South Carolina
		</option>
		<option value='29'>Columbus, Ohio
		</option>
		<option value='30'>Copenhagen, Denmark
		</option>
		<option value='31'>Columbia River, Washington
		</option>
		<option value='32'>Curitiba, Brazil
		</option>
		<option value='33'>Dallas, Texas
		</option>
		<option value='34'>Denver, Colorado
		</option>
		<option value='35'>Detroit, Michigan
		</option>
		<option value='36'>Edmonton, Alberta
		</option>
		<option value='37'>Endowment House
		</option>
		<option value='38'>Frankfurt, Germany
		</option>
		<option value='39'>Freiberg, Germany
		</option>
		<option value='40'>Fresno, California
		</option>
		<option value='41'>Fukuoka, Japan
		</option>
		<option value='42'>Guadalajara, Mexico
		</option>
		<option value='43'>Guatemala City, Guatemala
		</option>
		<option value='44'>Guayaquil, Ecuador
		</option>
		<option value='45'>The Hague, Netherlands
		</option>
		<option value='46'>Halifax, Nova Scotia
		</option>
		<option value='47'>Laie, Hawaii
		</option>
		<option value='48'>Helsinki, Finland
		</option>
		<option value='49'>Hermosillo, Sonora, Mexico
		</option>
		<option value='50'>Hong Kong, China
		</option>
		<option value='51'>Houston, Texas
		</option>
		<option value='52'>Idaho Falls, Idaho
		</option>
		<option value='53'>Johannesburg, South Africa
		</option>
		<option value='54'>Jordan River, Utah
		</option>
		<option value='55'>Kiev, Ukraine
		</option>
		<option value='56'>Kona, Hawaii
		</option>
		<option value='57'>Los Angeles, California
		</option>
		<option value='58'>Lima, Peru
		</option>
		<option value='59'>Logan, Utah
		</option>
		<option value='60'>London, England
		</option>
		<option value='61'>Louisville, Kentucky
		</option>
		<option value='62'>Lubbock, Texas
		</option>
		<option value='63'>Las Vegas, Nevada
		</option>
		<option value='64'>Madrid, Spain
		</option>
		<option value='65'>Manhattan,  New York
		</option>
		<option value='66'>Manila, Philippines
		</option>
		<option value='67'>Manti, Utah
		</option>
		<option value='68'>Medford, Oregon
		</option>
		<option value='69'>Melbourne, Australia
		</option>
		<option value='70'>Memphis, Tennessee
		</option>
		<option value='71'>Mérida, Mexico
		</option>
		<option value='72'>Mexico City, Mexico
		</option>
		<option value='73'>Montevideo, Uruguay
		</option>
		<option value='74'>Monterrey, Mexico
		</option>
		<option value='75'>Monticello, Utah
		</option>
		<option value='76'>Montreal, Quebec
		</option>
		<option value='77'>Mount Timpanogos, Utah
		</option>
		<option value='78'>Nashville, Tennessee
		</option>
		<option value='79'>Nauvoo, Illinois (new)
		</option>
		<option value='80'>Nauvoo, Illinois (original)
		</option>
		<option value='81'>Newport Beach, California
		</option>
		<option value='82'>Nuku'alofa, Tonga
		</option>
		<option value='83'>Hamilton, New Zealand
		</option>
		<option value='84'>Oakland, California
		</option>
		<option value='85'>Oaxaca, Mexico
		</option>
		<option value='86'>Ogden, Utah
		</option>
		<option value='87'>Oklahoma City, Oklahoma
		</option>
		<option value='88'>Orlando, Florida
		</option>
		<option value='89'>Other
		</option>
		<option value='90'>Porto Alegre, Brazil
		</option>
		<option value='91'>Palmyra, New York
		</option>
		<option value='92'>Panamá City, Panamá
		</option>
		<option value='93'>Papeete, Tahiti
		</option>
		<option value='94'>Perth, Australia
		</option>
		<option value='95'>President's Office
		</option>
		<option value='96'>Portland, Oregon
		</option>
		<option value='97'>Preston, England
		</option>
		<option value='98'>Provo, Utah
		</option>
		<option value='99'>Raleigh, North Carolina
		</option>
		<option value='100'>Recife, Brazil
		</option>
		<option value='101'>Redlands, California
		</option>
		<option value='102'>Regina, Saskatchewan
		</option>
		<option value='103'>Reno, Nevada
		</option>
		<option value='104'>Rexburg, Idaho
		</option>
		<option value='105'>Sacramento, California
		</option>
		<option value='106'>Santiago, Chile
		</option>
		<option value='107'>San Antonio, Texas
		</option>
		<option value='108'>San Diego, California
		</option>
		<option value='109'>Santo Domingo, Dominican Republic
		</option>
		<option value='110'>Seattle, Washington
		</option>
		<option value='111'>Seoul, South Korea
		</option>
		<option value='112'>St. George, Utah
		</option>
		<option value='113'>San Jose, Costa Rica
		</option>
		<option value='114'>Salt Lake
		</option>
		<option value='115'>St. Louis, Missouri
		</option>
		<option value='116'>Snowflake, Arizona
		</option>
		<option value='117'>São Paulo, Brazil
		</option>
		<option value='118'>St. Paul, Minnesota
		</option>
		<option value='119'>Spokane, Washington
		</option>
		<option value='120'>Stockholm, Sweden
		</option>
		<option value='121'>Suva, Fiji
		</option>
		<option value='122'>Bern, Switzerland
		</option>
		<option value='123'>Sydney, Australia
		</option>
		<option value='124'>Taipei, Taiwan
		</option>
		<option value='125'>Tampico, Mexico
		</option>
		<option value='126'>Tuxtla Gutierrez, Mexico
		</option>
		<option value='127'>Tokyo, Japan
		</option>
		<option value='128'>Toronto, Ontario
		</option>
		<option value='129'>Veracruz, Mexico
		</option>
		<option value='130'>Vernal, Utah
		</option>
		<option value='131'>Villahermosa, Tabasco, Mexico
		</option>
		<option value='132'>Washington, D.C.
		</option>
		<option value='133'>Winter Quarters (new)
		</option>
		<option value='134'>Winter Quarters
		</option>
		<option value='136'>Twin Falls, Idaho
		</option>
		<option value='137'>Draper, Utah
		</option>
		<option value='138'>Oquirrh Mountain, Utah
		</option>
	    </select>
	  </td>
	  <td>
	    <button type='button'
			id='EditIEvent18$temp'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelIEvent18$temp'>
		Delete
	    </button>
	  </td>
	</tr>
    <!-- end of template for sealed to spouse event row -->

    <!-- template for marriage ended event row -->
	<tr id='EndedRow$temp'>
	  <th class='labelSmall'>
		Marriage&nbsp;Ended:
	  </th>
	  <td class='left'>
		<input type='text' size='20' maxlength='100'
			name='MarEndD'
			class='white left'
			value='$eventd'>
		    at
	  </td>
	  <td colspan='2'>
	  </td>
	  <td>
	    <button type='button'
			id='EditIEvent24$temp'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelIEvent24$temp'>
		Delete
	    </button>
	  </td>
	</tr>
    <!-- end of template for mariage ended event row -->

    <!-- template for general marriage event row -->
	<tr id='EventRow$ider'>
	  <th class='labelSmall'>
		<input type='hidden'
			name='citType$ider'
			value='$idet'>
		$type $description
	  </th>
	  <td class='left'>
		<input type='text' size='20'
			name='Date$ider'
			class='white left'
			value='$eventd'>
		    at
	  </td>
	  <td colspan='2'>
		<input type='text' size='40'
			name='EventLoc$ider'
			class='white leftnc'
			value='$eventloc' >
	  </td>
	  <td>
	    <button type='button'
			id='EditEvent$ider'>
		Details
	    </button>
	  </td>
	  <td>
	    <button type='button' id='DelEvent$ider'>
		Delete
	    </button>
	  </td>
	</tr>
    <!-- end of template for marriage event row -->
  </table>
</div> <!-- id='templates' -->
<div class='balloon' id='HelpHusbGivenName'>
<p>This displays the given names of the husband.
This is a read-only field.  The husband is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpHusbSurname'>
<p>This displays the family name of the husband. 
This is a read-only field.  The husband is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpWifeGivenName'>
<p>This displays the given names of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpWifeSurname'>
<p>This displays the family name of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpchangeHusb'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the husband
in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateHusb'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the husband in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachHusb'>
<p>Selecting this button detaches the currently assigned husband from this
marriage.  It is not necessary to do this before selecting or creating
a new husband.
</p>
</div>
<div class='balloon' id='HelpchangeWife'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the wife
in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateWife'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the wife in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachWife'>
<p>Selecting this button detaches the currently assigned wife from this
marriage.  It is not necessary to do this before selecting or creating
a new wife.
</p>
</div>
<div class='balloon' id='HelpMarD'>
<p>Supply the date of the marriage.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href='datesHelp.html'>supported date formats</a> for details.
</p>
</div>
<div class='balloon' id='HelpMarLoc'>
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
<div class='balloon' id='HelpIDMS'>
<p>This selection list permits you to specify the ending or current
status of this marriage.
</p>
</div>
<div class='balloon' id='HelpMarriedNameRule'>
<p>This selection list permits you to specify whether or not the wife
took her husband's surname as a result of the marriage.  The default
is the traditional practice.
</p>
</div>
<div class='balloon' id='HelpNotes'>
<p>Supply extended textual notes about the marriage.
<p>Although you might be tempted to include the text of a newspaper notice
about the marriage in this field, it is recommended that you put that
text into the citation text field instead.
</p>
</div>
<div class='balloon' id='HelpAddCitation'>
<p>Selecting this button permits you to add a citation to document a
source of your knowledge of this family.
</p>
</div>
<div class='balloon' id='HelpeditCitation'>
<p>Selecting this button pops up a dialog that permits you to specify
more information about a citation beyond the name of the source and the
page within that source where the evidence is located.  This dialog also
permits you to modify the name of the source and the page identification.
</p>
</div>
<div class='balloon' id='HelpSource'>
<p>When you add a new source citation this field is a selection list
of all of the defined sources currently used in the family tree, from
which you can select the source that you wish to cite.
</p>
<p>Once the citation is add this becomes a read-only text field
documenting the selection.  If you wish to change the source select the
"Edit Citation" button.
</p>
</div>
<div class='balloon' id='HelpPage'>
<p>When you add a new source citation this field is a text field
for specifying the page within the selected source that contains
the evidence for this family.
</p>
<p>Once the citation is add this becomes a read-only text field
documenting the page.  If you wish to change the page select the
"Edit Citation" button.
</p>
</div>
<div class='balloon' id='HelpdelCitation'>
<p>Selecting this button removes the citation.
</p>
</div>
<div class='balloon' id='HelpaddChild'>
<p>Selecting this button, or using the keyboard short-cut alt-E, opens a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
to choose an existing individual
in the family tree database to add as a child of this family.
</p>
</div>
<div class='balloon' id='HelpaddNewChild'>
<p>Selecting this button, or using the keyboard short-cut alt-N, opens a 
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to create a new individual in the
family tree database that is added as a child of this family.
</p>
</div>
<div class='balloon' id='Helpupdate'>
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class='balloon' id='HelporderChildren'>
<p>Selecting this button, or using the keyboard short-cut alt-O, 
reorders the children of this marriage by their
dates of birth.
</p>
</div>
<div class='balloon' id='HelpeditChild'>
<p>Selecting this button opens a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to edit the detailed information
about the child summarized in this line of the form.
</p>
</div>
<div class='balloon' id='HelpdetChild'>
<p>Selecting this button detaches the child summarized in this line of the
form from this family.  You can then go to another family and attach the
child there.
</p>
</div>
</body>
</html>
