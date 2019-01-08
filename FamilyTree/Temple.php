<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Temple.php															*
 *																		*
 *  Display a web page containing details of an particular LDS Temple	*
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																		*
 *  Parameters:															*
 *		idtr			Unique numeric identifier of the temple.	    *
 *						For backwards compatibility this can be			*
 *						specified using the 'id' parameter.				*
 *																		*
 *  History:															*
 *		2012/12/06		created											*
 *		2013/02/23		implement new record format for tblTR			*
 *						display start and end dates in human form and 	*
 *						accept human dates as input						*
 *		2013/05/23		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						add IDTR value to e-mail subject				*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace table with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with a temple						    *
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/06/27		display start and end dates as text strings		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/12		use get( and set(								*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/common.inc';

    $months	= array('01'	=> 'Jan',
					'02'	=> 'Feb',
					'03'	=> 'Mar',
					'04'	=> 'Apr',
					'05'	=> 'May',
					'06'	=> 'Jun',
					'07'	=> 'Jul',
					'08'	=> 'Aug',
					'09'	=> 'Sep',
					'10'	=> 'Oct',
					'11'	=> 'Nov',
					'12'	=> 'Dec');

    // action depends upon whether the user is authorized to update
    if (canUser('all'))
		$readonly	= '';
    else
		$readonly	= "readonly='readonly'";

    // get the requested unique identifier
    if (array_key_exists('idtr', $_GET))
		$idtr	= $_GET['idtr'];
    else
    if (array_key_exists('id', $_GET))
		$idtr	= $_GET['id'];
    else
		$idtr	= null;

    // get the requested temple
    if ($idtr != null &&
		strlen($idtr) > 0 &&
		ctype_digit($idtr))
    {		// IDTR present and valid
		try
		{
		    $temple	= new Temple(array('idtr' => $idtr));
		    $name	= $temple->getName();
		    $title	= "Temple: $name";
		    $code	= $temple->getCode();
		    $code2	= $temple->getCode2();
		    $eName	= htmlspecialchars($name,
								   ENT_QUOTES);

		    // interpret start and end dates
		    $templeStartDate	= $temple->getStartDate();
		    $templeStart	= $templeStartDate->toString();

		    $templeEndDate	= $temple->getEndDate();
		    $templeEnd		= $templeEndDate->toString();
		}	// try
		catch(Exception $e)
		{	// could not get temple
		    $title	= 'Could Not Get Temple';
		    $msg	.= $e->getMessage() . '. ';
		}	// could not get temple
    }		// present and valid
    else
    {		// idtr missing or invalid
		$title	= 'Could Not Get Temple';
		$msg	.= 'Parameter idtr missing or invalid. ';
    }		// idtr missing or invalid

    htmlHeader($title,
				array(	'/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js',
						'Temple.js'),
				true);
?>
<body>
<?php
    pageTop(array('/genealogy.php'			=> 'Genealogy',
				  '/genCanada.html'			=> 'Canada',
				  '/Canada/genProvince.php?Domain=CAON'		=> 'Ontario',
				  '/FamilyTree/Services.php'		=> 'Services',
				  '/FamilyTree/Temples.php'		=> 'Temples'));
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="TempleHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>  
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {	// errors detected
?>
  <p class="message">
    <?php print $msg; ?> 
  </p>
<?php
    }		// errors detected
    else
    {		// no errors
		if ($temple->get('used'))
		    $usedChecked	= 'checked="checked" '; 
		else
		    $usedChecked	= '';
		if ($temple->get('qstag'))
		    $qsChecked	    = 'checked="checked" '; 
		else
		    $qsChecked	    = '';
		if ($temple->get('tag1'))
		    $tag1Checked	= 'checked="checked" '; 
		else
		    $tag1Checked	= '';
?>
  <form name="locForm" id="locForm" action="updateTemple.php" method="post">
    <div class="row">
		<label class="column1" for="idtr">IDTR:</label>
		<input type="text" name="idtr" id="idtr" readonly="readonly"
				size="5" maxlength="5"
				class="ina right"
				value="<?php print $temple->getId(); ?>">
        <div style="clear: both;"></div>
    </div>
    <div class="row">
      <div class="column1">
		<label class="column1" for="Code">Code:</label>
      	<input type="text" name="Code" id="Code"
				size="5" maxlength="5"
				class="white left"
				value="<?php print $code; ?>"
				<?php print $readonly; ?>>
      </div>
      <div class="column2">
		<label class="column2" for="Code2">2 Character Code:</label>
      	<input type="text" name="Code2" id="Code2"
				size="2" maxlength="2"
				class="white left"
				value="<?php print $code2; ?>"
				<?php print $readonly; ?>>
      </div>
        <div style="clear: both;"></div>
    </div>
    <div class="row">
		<label class="column1" for="Temple">Location:</label>
		<input type="text" name="Temple" id="Temple"
				size="50" maxlength="50"
				class="white leftnc"
				value="<?php print $eName; ?>"
				<?php print $readonly; ?>>
        <div style="clear: both;"></div>
    </div>
    <div class="row">
      <div class="column1">
		<label class="column1" for="TempleStart">Date&nbsp;Opened:</label>
		<input type="text" name="TempleStart" id="TempleStart"
				size="8" style="width: 15em;"
				class="white leftnc"
				value="<?php print $templeStart; ?>"
				<?php print $readonly; ?>>
      </div>
      <div class="column2">
		<label class="column1" for="TempleEnd">Date&nbsp;Closed:</label>
		<input type="text" name="TempleEnd" id="TempleEnd"
				size="8" style="width: 15em;"
				class="white leftnc"
				value="<?php print $templeEnd; ?>"
				<?php print $readonly; ?>>
      </div>
        <div style="clear: both;"></div>
    </div>
    <div class="row">
      <div class="column1">
		<label class="column1" for="Used">Used:</label>
      	<input type="checkbox" name="Used" id="Used" value="1"
				<?php print $usedChecked; ?> <?php print $readonly; ?>>
      </div>
      <div class="column2">
		<label class="column2" for="Tag1">Tag1:</label>
      	<input type="checkbox" name="Tag1" id="Tag1" value="1"
				<?php print $tag1Checked; ?> <?php print $readonly; ?>>
      </div>
        <div style="clear: both;"></div>
    </div>
    <div class="row">
		<label class="column1" for="qsTag">qsTag:</label>
      	<input type="checkbox" name="qsTag" id="qsTag" value="1"
				<?php print $qsChecked; ?> <?php print $readonly; ?>>
        <div style="clear: both;"></div>
    </div>
<?php
		if (canUser('all'))
		{	// administrator can update
?>
  <p>
    <button type="submit" id="Submit" class="button">
		<u>U</u>pdate Temple
    </button>
		&nbsp;
		<button type="button" id="Pictures">
		  <u>P</u>ictures
		</button>
		<input type="hidden" name="PicIdType" id="PicIdType" 
				value="72">
  </p> 
<?php
		}	// administrator can update
?>
<!--
<p>
   <button type="button" id="References" class="button">
		Display Individuals using this Temple
  </button>
</p>
-->
  </form>
<?php
    }	// no errors
?>
</div> <!-- end of <div class="body"> -->
<?php
    pageBot($title . ": IDTR=$idtr");
?>
<div class="map" id="mapDiv">
</div>
<div class="hidden" id="templates">
    <!-- the following button should exactly match <button id="showMap"> -->
    <button id="showMapTemplate" class="button">Show Map</button>
    <!-- the following button replaces Show Map -->
    <button id="hideMapTemplate" class="button">Hide Map</button>
</div>
<div class="balloon" id="Helpidtr">
<p>The unique numeric identifier of the temple record in the database.
This is assigned by the system when a new temple is created, and cannot
be altered by the user.
</p>
</div>
<div class="balloon" id="HelpCode">
<p>This field contains an up to 5 character 
abbreviated identification code for the temple
</p>
</div>
<div class="balloon" id="HelpCode2">
<p>If not empty this field contains a 2-character abbreviated identification
code for the temple.
</p>
</div>
<div class="balloon" id="HelpTemple">
<p>The temple name as it appears in web pages.
This should be expressed as a formal address uniquely identifying the
temple.
To avoid creating multiple temple records that all refer to the same
place, it is desirable to use a consistent address structure and abbreviation
style.  
<p>Always include the country name, or 
<a href="http://www.iso.org/iso/country_codes/iso_3166_code_lists/english_country_names_and_code_elements.htm">two character ISO 3166 country code</a>
as the last element of an address.  This avoids ambiguities.
In particular many of the 
<a href="/usstates.html">US Postal Service state abbreviations</a>
duplicate ISO 3166 country codes.  You will save yourself trouble as you
expand your research beyond your home country.  Note that England, Scotland,
Wales, Northern Ireland, and other component states of the United Kingdom,
do not have ISO 3166 country codes.  Also, for obvious reasons, there are
no ISO 3166 country codes for countries that no longer exist, such as
Prussia.
<p>Use consistent abbreviation style.  For example either always include the
period after an abbreviation or always exclude it.  For example consistently
use addresses like "Central Ave, St Petersburg, FL, USA" or like "Central Ave., St. Petersburg, Florida, USA".
</p>
</div>
<div class="balloon" id="HelpNotes">
<p>User notes about the temple.
</p>
</div>
<div class="balloon" id="HelpTempleStart">
<p>This is the date that the temple opened.
</p>
</div>
<div class="balloon" id="HelpTempleEnd">
<p>This is the date that the temple closed.  This is blank if the temple is 
still open.
</p>
</div>
<div class="balloon" id="HelpUsed">
<p>This checkbox indicates whether any ordinances in the family tree
currently reference this temple.
</p>
</div>
<div class="balloon" id="HelpqsTag">
<p>This field is currently unused.
The checkbox is selected if the value of this field is 'yes'.
</p>
</div>
<div class="balloon" id="HelpTag1">
<p>This field is currently unused.
The checkbox is selected if the value of this field is 'yes'.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>Clicking on this button updates the database entry for this temple.
</p>
</div>
<div class="balloon" id="HelpReferences">
<p>Clicking on this button displays a list of the database entries, either
individuals or families, that reference the temple.
</p>
</div>
<div class="balloon" id="HelpmergeDuplicates">
<p>Clicking on this button merges all of the temples with the same name
with the current temple.
</p>
</div>
  <div class="balloon" id="HelpPictures">
    Click this button to open a dialog for managing the pictures associated
    with the temple.
  </div>
</body>
</html>
