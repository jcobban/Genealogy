<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbDetail.php														*
 *																		*
 *  Display the contents of a Wesleyan Methodist Baptism as a detail	*
 *  form with optional ability to update the record.					*
 *																		*
 *  Input (passed by method=get):										*
 *		Volume			volume number									*
 *		Page			page number										*
 *		IDMB			record number									*
 *																		*
 *  History:															*
 *		2016/02/22		created											*
 *		2016/03/06		reformat dates to dd mmm yyyy					*
 *						use class to represent sex of matching entries	*
 *		2016/04/25		replace ereg with preg_match					*
 *	    2016/11/02	    prev and next buttons stay within current page	*	
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/13		$imatches not defined							*
 *		2017/03/19		use preferred parameters for new Person			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/11		use RecordSet									*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2017/11/13		use PersonSet in place of Person::getPersons	*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$monthName	= array('01'	=> 'Jan',
					'02'	=> 'Feb',
					'03'	=> 'Mar',
					'04'	=> 'Apr',
					'05'	=> 'May',
					'06'	=> 'June',
					'07'	=> 'July',
					'08'	=> 'Aug',
					'09'	=> 'Sep',
					'10'	=> 'Oct',
					'11'	=> 'Nov',
					'12'	=> 'Dec');

// action depends upon whether the user is authorized to
// update the database
if(canUser('all'))
{
	$title				= 'Wesleyan Methodist Baptism Detail Update';
	$update				= true;
	$action				= 'Update';
	$readonly			= '';
	$txtleftclass		= 'white left';
	$txtleftclassnc		= 'white leftnc';
	$txtrightclass		= 'white right';
	$formaction			= 'WmbUpdate.php';
}
else
{
	$title				= 'Wesleyan Methodist Baptism Detail Query';
	$update				= false;
	$action				= 'Details';
	$readonly			= " readonly='readonly'";
	$txtleftclass		= 'ina left';
	$txtleftclassnc		= 'ina leftnc';
	$txtrightclass		= 'ina rightnc';
	$formaction			= 'donothing.php';
}

// default parameter values
$idmb	                = '';
$volume	                = null;
$page	                = null;

// get parameter values
if (count($_GET) > 0)
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// process specific named parameters
	    case 'id':
	    case 'idmb':
	    {
			$idmb	= $value;
			if (!preg_match("/^([<=>!]*)([0-9]{1,7})$/", $idmb))
			{
			    $msg	.= "Registration Number $idmb must be a number. ";
			}
			break;
	    }		// IDMB passed

	    case 'volume':
	    {
			$volume	= $value;
			if (!preg_match("/^([0-9]{1,2})$/", $volume))
			{
			    $msg	.= "Volume Number $volume must be a number. ";
			}
			break;
	    }		// Volume passed

	    case 'page':
	    {
			$page	= $value;
			if (!preg_match("/^([0-9]{1,3})$/", $page))
			{
			    $msg	.= "Page Number $page must be a number. ";
			}
			break;
	    }		// Page passed

	    case 'debug':
	    case 'lang':
	    {		// debug handled by common code
			break;
	    }		// debug handled by common code

	    default:
	    {		// any other paramters
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if ($idmb == '')
{
	$msg		.= "IDMB parameter omitted. ";
}

// if no error messages Issue the query
if (strlen($msg) == 0)
{		// no errors
	$getNext	= substr($idmb,0,1) == '>';
	// get the baptism registration object
	if ($volume && $page)
	{
	    $getParms	= array('volume'	=> $volume,
						'page'		=> $page,
						'idmb'		=> $idmb,
						'limit'		=> 1);
	    if (!$getNext)
			$getParms['order']	= "`IDMB` DESC";
	    $baptisms	= new RecordSet('MethodistBaptisms', $getParms);
	    if ($baptisms->count() > 0)
			$baptism	= $baptisms->rewind();
	    else
	    {		// ran off end or beginning of page
			if ($getNext)
			{		// get first line of next page
			    $getParms	= array('volume'	=> $volume,
							'page'		=> $page + 1,
							'limit'		=> 1);
			    $baptisms	= new RecordSet('MethodistBaptisms', $getParms);
			}		// get first line of next page
			else
			if ($page > 1)
			{		// get last line of previous page
			    $getParms	= array('volume'	=> $volume,
							'page'		=> $page - 1,
							'limit'		=> 1,
							'order'		=> "`IDMB` DESC");
			    $baptisms	= new RecordSet('MethodistBaptisms', $getParms);
			}		// get last line of previous page

			// get first record in set
			if ($baptisms->count() > 0)
			    $baptism	= $baptisms->rewind();
			else
			    $baptism	= null;
	    }		// ran off end or beginning of page
	}
	else
	    $baptism	= new MethodistBaptism(array('idmb' => $idmb));

	if ($baptism && $baptism->isExisting())
	{			// have a record from the database
	    // copy contents into working variables
	    $volume		= $baptism->get('volume');
	    $page		= $baptism->get('page');
	    $surname		= $baptism->get('surname');
	    $idir		= $baptism->get('idir');
	    $givenName		= $baptism->get('givenname');
	    $birthDate		= $baptism->get('birthdate');
	    $person		= null;
	    $imatches		= array();

	    // if this registration is not already linked to
	    // look for individuals who match
	    if ($idir == 0 && $update)
	    {			// updating 
			// check for existing citations to this registration
			$citparms	=
					array('idsr'		=> 158,
					      'type'		=> Citation::STYPE_BIRTH,
					   'srcdetail'=> "V[^\d]*$volume.*Page $page.*# $idmb"); 
			$citations	= new CitationSet($citparms);
			if ($citations->count() > 0)
			{		// citation to death in old location
			    $citrow	= $citations->rewind();
			    $idir	= $citrow->get('idime');
			}		// citation to death in old location
			else
			{		// check for event citation
			    $citparms	=
					    array('idsr'	=> 158,
						  'type'	=> Citation::STYPE_EVENT,
					  'srcdetail'=> "V[^\d]*$volume.*Page $page.*# $idmb"); 
			    $citations	= new CitationSet($citparms);
			    foreach($citations as $idsx => $citation)
			    {
					$ider		= $citation->get('idime');
					$event		= new Event($ider);
					$idet		= $event->getIdet();
					if ($idet == Event::ET_BIRTH)
					{
					    $idir		= $event->getIdir();
					    break;
					}
			    }
			}		// check for event citation

			if ($idir == 0 &&
			    strlen($surname) > 0 && strlen($givenName) > 0) 
			{			// no existing citation
			    if ($debug)
					$warn	.= "<p>Search for match on $surname, $givenName</p>\n";
			    // look for individuals in the family tree whose names are
			    // rough matches to the name on the death registration
			    // who have the same sex, and who were born within 2 years
			    // of the deceased.

			    // obtain the birth year
			    $rxResult		= preg_match('/[0-9]{4}/',
								     $birthDate,
								     $matches);
			    if ($rxResult > 0)
					$birthYear	= intval($matches[0]);
			    else
					$birthYear	= 1800;

			    // look 2 years on either side of the year
			    $birthrange	= array(($birthYear - 2) * 10000,
							    ($birthYear + 2) * 10000);
			    // search for a match on any of the parts of the
			    // given name
			    $gnameList	= explode(' ', $givenName);

			    // quote the surname value
			    $getParms	= array('loose'		=> true,
							'surname'	=> $surname,
							'givenname'	=> $gnameList,
							'birthsd'	=> $birthrange,
							'incmarried'	=> true,
			    			'order'		=> 'tblNX.Surname, tblNX.GivenName, tblIR.BirthSD');
			    $imatches	= new PersonSet($getParms);
			}			// record is initialized with name
			else
			if ($idir > 0 &&
			    strlen($surname) == 0 && strlen($givenName)== 0) 
			{			// record is uninitialized

			    if ($idir > 0)
			    {		// found a citation
					try {
					    $person	= new Person(array('idir' => $idir));
					    $linkedName	= $person->getName(Person::NAME_INCLUDE_DATES);
					} catch (Exception $e) {
					    $msg	.= "Exception: " .  $e->getMessage();
					}
			    }		// found a citation
			}			// record is uninitialized
	    }			// updating

	    // get information from the existing link
	    if ($idir > 0)
	    {			// existing link
			if ($debug)
			    $warn		.= "<p>Existing link IDIR=$idir</p>\n";
			try {
			    if (is_null($person))
					$person	= new Person(array('idir' => $idir));
			    $linkedName = $person->getName(Person::NAME_INCLUDE_DATES);
			    $maidenName	= $person->getSurname();
			    $genderClass	= $person->getGenderClass();
			    if ($maidenName != $surname)
			    {		// $surname is not maiden name
					$linkedName	= str_replace($maidenName,
								  "($maidenName) $surname",
								  $linkedName);
			    }		// $surname is not maiden name
			} catch (Exception $e)
			{
			    $linkedName	= $givenName . ' ' . $surname .
						      ' (not found in database)';
			}
	    }			// existing link

	    // copy contents into working variables
	    // some of the fields may have been changed by the cross-ref code
	    $surname	= $baptism->get('surname');
	    $givenName	= $baptism->get('givenname');
	    $birthDate	= $baptism->get('birthdate');

	    $subject	= "number: " . 
					      $idmb . ', ' . 
					      $givenName . ' ' . $surname;
	}			// have a record from the database
	else
	{
	    $subject	= "not found";
	    $msg	.= "No match found for supplied parameters.";
	}
}			// no errors, perform query
else
{			// error detected
	$subject	= "number: " . $idmb;
	$volume		= '';
	$page		= '';
}			// error detected

$title		    = $action;
$subject		= rawurlencode($subject);

htmlHeader("Wesleyan Methodist Baptism: $title",
	       array(	'/jscripts/js20/http.js',
					'/jscripts/CommonForm.js',
					'/jscripts/Ontario.js',
					'/jscripts/util.js',
					'/tinymce/jscripts/tiny_mce/tiny_mce.js',
					'WmbDetail.js'),
			true);
?>
<body>
  <div id='transcription' style='overflow: auto; overflow-x: scroll'>
<?php
pageTop(array(
	'/genealogy.php'	=> 'Genealogy',
	'/genCanada.html'	=> 'Canada',
	'WmbQuery.html'	=> 'New Query', 
	'WmbStats.php'	=> 'Status', 
	"WmbDoQuery.php?volume=$volume&Page=$page"
						=> "Volume $volume Page $page"));
?>
 <div class='body'>
   <h1>
  <span class='right'>
	<a href='WmbDetailHelpen.html' target='help'>? Help</a>
  </span>
        Wesleyan Methodist Baptism: <?php print $title; ?>
   </h1>
<?php
if (strlen($warn) > 0)
{			// warning message
?>
  <div class='warning'>
	<?php print $warn; ?> 
  </div>
<?php
}			// warning message

if (strlen($msg) > 0)
{			// error messages
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
}			// error messages
else
{			// no errors
	// copy contents into working variables
	$idmb		= str_replace("'","&#39;",$baptism->get('idmb'));
	$volume		= str_replace("'","&#39;",$baptism->get('volume'));
	$page		= str_replace("'","&#39;",$baptism->get('page'));
	$district	= str_replace("'","&#39;",$baptism->get('district'));
	$area		= str_replace("'","&#39;",$baptism->get('area'));
	$givenname	= str_replace("'","&#39;",$baptism->get('givenname'));
	$surname	= str_replace("'","&#39;",$baptism->get('surname'));
	$father		= str_replace("'","&#39;",$baptism->get('father'));
	$mother		= str_replace("'","&#39;",$baptism->get('mother'));
	$residence	= str_replace("'","&#39;",$baptism->get('residence'));
	$birthplace	= str_replace("'","&#39;",$baptism->get('birthplace'));
	$birthdate	= str_replace("'","&#39;",$baptism->get('birthdate'));
	$rxResult		= preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
							     $birthDate,
							     $matches);
	if ($rxResult > 0)
	    $birthdate	= $matches[3] . ' ' . $monthName[$matches[2]] .
					  ' ' . $matches[1];
	
	$baptismdate	= str_replace("'","&#39;",$baptism->get('baptismdate'));
	$rxResult		= preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
							     $baptismdate,
							     $matches);
	if ($rxResult > 0)
	    $baptismdate= $matches[3] . ' ' . $monthName[$matches[2]] .
					  ' ' . $matches[1];
	
	$baptismplace	= str_replace("'","&#39;",$baptism->get('baptismplace'));
	$minister	= str_replace("'","&#39;",$baptism->get('minister'));
	$commap		= strpos($minister, ',');
	if ($commap > 0)
	{
	    $minister	= trim(substr($minister, $commap + 1)) . ' ' .
					  substr($minister, 0, $commap);
	}
	$idir		= $baptism->get('idir');
?>
  <form action='<?php print $formaction; ?>'
			method='post' 
			name='distForm' id='distForm'
			enctype='multipart/form-data'>
<p>
  <button type='button' id='Previous'><u>P</u>revious</button>
	&nbsp;
  <button type='button' id='Next'><u>N</u>ext</button>
	&nbsp;
  <button type='button' id='NewQuery'>New <u>Q</u>uery</button>
  <input name='IDMB' id='IDMB' type='hidden'
			value='<?php print $idmb; ?>'>
<?php
if ($debug)
{			// debugging enabled
?>
	<input type='hidden' name='Debug' id='Debug'
					value='Y'/>
<?php
}			// debugging enabled
?>
</p>
<div class='row' id='VolPageRow'>
  <div class='column1'>
	<label class='labelSmall' for='Volume'>Volume:</label>
	<input name='Volume' id='Volume'
			type='text' size='2' maxlength='4'
			value='<?php print $volume; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div class='column2'>
	<label class='labelSmall' for='Page'>Page:</label>
	<input name='Page' id='Page'
			type='text' size='3' maxlength='5' 
			value='<?php print $page; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='DistrictRow'>
  <div class='column1'>
	<label class='labelSmall' for='District'>District:</label>
	<input name='District' id='District'
			type='text' size='32' maxlength='128'
			value='<?php print $district; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div class='column2'>
	<label class='labelSmall' for='Area'>Area:</label>
	<input name='Area' id='Area'
			type='text' size='32' maxlength='128' 
			value='<?php print $area; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='NameRow'>
  <div class='column1'>
	<label class='labelSmall' for='GivenName'>Name: Given:</label>
	<input name='GivenName' id='GivenName'
			type='text' size='32' maxlength='40'
			value='<?php print $givenname; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div class='column2'>
	<label class='labelSmall' for='Surname'>Family:</label>
	<input name='Surname' id='Surname'
			type='text' size='20' maxlength='40' 
			value='<?php print $surname; ?>' 
			class='<?php print $txtleftclass; ?>' 
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='BirthDatePlaceRow'>
  <div class='column1'>
	<label class='labelSmall' for='BirthDate'>Birth&nbsp;Date:</label>
	<input name='BirthDate' id='BirthDate'
			type='text' size='12' maxlength='32'
			value='<?php print $birthdate; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div class='column2'>
	<label class='labelSmall' for='BirthPlace'>Birth&nbsp;Place:</label>
	<input name='BirthPlace' id='BirthPlace'
			type='text' size='20' maxlength='128'
			value='<?php print $birthplace; ?>'
			class='<?php print $txtleftclassnc; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<?php
	if ($idir > 0)
	{	// link to family tree database
?>
<div class='row' id='LinkRow'>
  <div class='column2'>
	<label class='labelSmall' for='IDIR'>Link:</label>
	<a href='/FamilyTree/Person.php?idir=<?php print $idir; ?>'
			id='showLink' class='<?php print $genderClass; ?>'
			target='_blank'>
	    <?php print $linkedName; ?>
	</a>
	  <input type='hidden' id='IDIR' name='IDIR' 
			value='<?php print $idir; ?>'>
  </div>
	<button id='clearIdir' type='button'>Clear</button>
  <div style='clear: both;'></div>
</div>
<?php
	}	// link to family tree database
	else
	{	// not matched to some individuals in database
?>
<div class='row'id='LinkRow'>
  <div class='column2'>
	<label class='labelSmall' for='IDIR'>Link:</label>
	<select name='IDIR' id='IDIR' rows='1'
			class='<?php print $txtleftclass; ?>'>
	    <option value='0'>Possible matches to this registration:
<?php
	    foreach($imatches as $iidir => $person)
	    {
			$igivenname	= $person->get('givenname'); 
			$isurname	= $person->get('surname');
			$isex		= $person->get('gender');
			if ($isex == Person::MALE)
			{
			    $sexclass	= 'male';
			    $childrole	= 'son';
			    $spouserole	= 'husband';
			}
			else
			if ($isex == Person::FEMALE)
			{
			    $sexclass	= 'female';
			    $childrole	= 'daughter';
			    $spouserole	= 'wife';
			}
			else
			{
			    $sexclass	= 'unknown';
			    $childrole	= 'child';
			    $spouserole	= 'spouse';
			}

			$iname  	= $person->getName(Person::NAME_INCLUDE_DATES);
			$parents	= $person->getParents();
			$comma		= ' ';
			foreach($parents as $idmr => $set)
			{	// loop through parents
			    $pfather	= $set->getHusbName();
			    $pmother	= $set->getWifeName();
			    $iname	.= "$comma$childrole of $pfather and $pmother";
			    $comma	= ', ';
			}	// loop through parents

			$families	= $person->getFamilies();
			$comma		= ' ';
			foreach ($families as $idmr => $set)
			{	// loop through families
			    if ($isex == Person::FEMALE)
					$spouse	= $set->getHusbName();
			    else
					$spouse	= $set->getWifeName();
			    $iname	.= "$comma$spouserole of $spouse";
			    $comma	= ', ';
			}	// loop through families
?>
	    <option value='<?php print $iidir; ?>'
			    class='<?php print $sexclass; ?>'>
	    <?php print $iname; ?>
	    </option>
<?php
	    }	// loop through results
?>
	</select>
<?php
	}	// not matched to some persons in database
?>
  <div style='clear: both;'></div>
</div>
<div class='row' id='BaptismDatePlaceRow'>
  <div class='column1'>
	<label class='labelSmall' for='BaptismDate'>Baptism&nbsp;Date:</label>
	<input name='BaptismDate' id='BaptismDate'
			type='text' size='10' maxlength='32'
			value='<?php print $baptismdate; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div class='column2'>
	<label class='labelSmall' for='BaptismPlace'>Baptism&nbsp;Place:</label>
	<input name='BaptismPlace' id='BaptismPlace'
			type='text' size='20' maxlength='128'
			value='<?php print $baptismplace; ?>'
			class='<?php print $txtleftclassnc; ?>'
			<?php print $readonly; ?>/>
	</div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='FatherNameRow'>
  <div class='column1'>
	<label class='labelSmall' for='Father'>Father's&nbsp;Name:</label>
	<input name='Father' id='Father'
			type='text' size='32' maxlength='48'
			value='<?php print $father; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='MotherNameRow'>
  <div class='column1'>
	<label class='labelSmall' for='Mother'>Mother's&nbsp;Name:</label>
	<input name='Mother' id='Mother'
			type='text' size='32' maxlength='48'
			value='<?php print $mother; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='ResidenceRow'>
  <div class='column1'>
	<label class='labelSmall' for='Residence'>Residence:</label>
	<input name='Residence' id='Residence'
			type='text' size='32' maxlength='48'
			value='<?php print $residence; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<div class='row' id='MinisterRow'>
  <div class='column1'>
	<label class='labelSmall' for='Minister'>Minister:</label>
	<input name='Minister' id='Minister'
			type='text' size='20' maxlength='48'
			value='<?php print $minister; ?>'
			class='<?php print $txtleftclass; ?>'
			<?php print $readonly; ?>/>
  </div>
  <div style='clear: both;'></div>
</div>
<?php

	if($update)
	{		// authorized to update database
	    // display submit and reset buttons
?>
<p>
	<button type='submit' id='Submit'>
	  <u>U</u>pdate
	</button>
	&nbsp;
	<button type='reset' id='Reset'>
	  <u>C</u>lear Form
	</button>
</p>
<?php
	}		// authorized to update database
?>
</form>
<?php
}			// no error messages
?>
 </div> <!-- end of <div id='body'> -->
<?php
pageBot();
?>
  </div> <!-- id='transcription' -->
<!--  The remainder of the web page consists of divisions containing
context specific help.  These divisions are only displayed if the user
requests help by pressing F1.  Including this information here ensures
that the language of the help balloons matches the language of the
input form.
-->
<div class='balloon' id='HelpGivenName'>
<p>
The Given Names of the child.
</p>
</div>
<div class='balloon' id='HelpSurname'>
<p>
The Surname of the child.
</p>
</div>
<div class='balloon' id='HelpIDIR'>
<p>
This selection list offers a list of persons in the family tree database
who may correspond to this death registration.  Selecting an entry in this
list will cause a citation to this registration to be added to that person.
</p>
</div>
<div class='balloon' id='HelpBirthPlace'>
<p>
Birth place of child.
</p>
</div>
<div class='balloon' id='HelpBirthDate'>
<p>
Birth date of child.
It is suggested that dates be entered consistently as day, 
abbreviation of name of month, and year.
</p>
</div>
<div class='balloon' id='HelpFatherName'>
<p>
Name of father of child.
</p>
</div>
<div class='balloon' id='HelpMotherName'>
<p>
Name of mother of child.
</p>
</div>
<div class='balloon' id='HelpMinister'>
<p>
Name of minister officiating at the baptism.
</p>
</div>
<div class='balloon' id='HelpRemarks'>
<p>
Any comments by the transcriber may be entered here.
</p>
</div>
<div class='balloon' id='HelpIDMB'>
The sequential internal identification of the record.
</div>
<div class='balloon' id='HelpVolume'>
The volume number within which the registration is found.
</div>
<div class='balloon' id='HelpPage'>
The page number within the volume in which the registration is found.
</div>
<div class='balloon' id='HelpSubmit'>
Clicking on this button commits the changes you have made to the database.
</div>
<div class='balloon' id='HelpReset'>
Clicking on this button resets the values of some fields to their defaults.
</div>
<div class='balloon' id='HelpImage'>
This field contains the location of the image of the associated original
document.  This can be an absolute URL, starting with "http:" or a URL 
relative to the
http://<?php print $_SERVER['SERVER_NAME']; ?>/Ontario/Images folder.
</div>
<div class='balloon' id='HelpShowImage'>
Click on this button to see the original image of the registration.
</div>
<div class='balloon' id='HelpNext'>
Click on this button to see the registration with the next higher
registration number.
</div>
<div class='balloon' id='HelpPrevious'>
Click on this button to see the registration with the next lower
registration number.
</div>
<div class='balloon' id='HelpNewQuery'>
Click on this button to issue a new query of the database.
</div>
</body>
</html>
