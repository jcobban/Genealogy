<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editName.php														*
 *																		*
 *  Display a web page for editting one alternate name for an			*
 *  individual from the Legacy database which is represented			*
 *  by an instance of Name (a record in table tblNX).					*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		idnx	unique numeric key of instance of Name record tblNX		*
 *		form	name of the form in the invoking page					*
 * 																		*
 *  History: 															*
 *		2014/04/08		created											*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2014/03/09		Citation::getTitle is removed					*
 *		2015/05/18		do not escape textarea value.  HTML tags		*
 *						are used by the rich-text editor.				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2016/11/25		notes field needs to be named 'akanote'			*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/common.inc';

/********************************************************************
 *   OO  PPP  EEEE N  N     CC   OO  DDD  EEEE						*
 *  O  O P  P E    NN N    C  C O  O D  D E							*
 *  O  O PPP  EEE  N NN    C    O  O D  D EEE						*
 *  O  O P    E    N NN    C  C O  O D  D E							*
 *   OO  P    EEEE N  N     CC   OO  DDD  EEEE						*
 ********************************************************************/

// default title
$title		    = 'Edit Alternate Name';
$readonly		= '';

// safely get parameter values
// defaults
// parameter values from URI
$idnx		= null;	// index of Name
$idir		= null;	// index of Person

// database records
$person		= null;	// instance of Person
$altname		= null;	// instance of Name

// process input parameters from the search string passed by method=get
foreach($_GET as $key => $value)
{
	switch($key)
	{
	    case 'idnx':
	    {	// get the key of instance of Alternate Name Record tblNX
			$idnx	= intval($value);
			if ($idnx < 1)
			    $msg	.= "Invalid value idnx='$value'.  ";
			if (!canUser('edit'))
			    $msg .= 'You are not authorized to edit alternate name events. ';
			if (strlen($msg) == 0)
			{		// process only if no errors detected
			try
			{
			    $altname		= new Name(array('idnx' => $idnx));
			    $idir		= $altname->get('idir');
			    $surname		= $altname->get('surname');
			    $soundslike		= $altname->get('soundslike');
			    $givenname		= $altname->get('givenname');
			    $prefix		= $altname->get('prefix');
			    $nametitle		= $altname->get('title');
			    $userref		= $altname->get('userref');
			    $order		= $altname->get('order');
			    $marriednamecreatedby= $altname->get('marriednamecreatedby');
			    $preferredaka	= $altname->get('preferredaka');
			    if ($preferredaka == 0)
					$preferredakachecked	= '';
			    else
					$preferredakachecked	= 'checked="checked"';
			    $notes		= $altname->get('akanote');
			    $marriednamemaridid	= $altname->get('marriednamemaridid');
			}
			catch(Exception $e)
			{		// new Name failed
			    $altname	= null;
			    $msg	.= "Invalid altname identification idnx=$idnx. " .
						       $e->getMessage();
			    $idnx	= null;
			}		// new Name failed
			}		// process only if no errors detected
			break;
	    }		//idnx

	    case 'form':
	    {
			$formname	= $value;
			break;
	    }		// form name
	}	// switch
}		// loop through all parameters

// use instance of Person to expand title
if (!is_null($idir))
{			// have an IDIR
	$person		= new Person(array('idir' => $idir));
	if ($person->isExisting())
	    $title	.= ' for ' . $person->getName();
	else
	{		// Person does not exist
	    $msg	.= "Invalid IDIR=$idir in Name record. " .
						       $e->getMessage();
	    $idir	= null;
	}		// Person does not exist
}			// have an IDIR

htmlHeader($title,
			array(  '/jscripts/js20/http.js',
					'/jscripts/CommonForm.js',
					'/jscripts/util.js',
					'/jscripts/Cookie.js',
					'/tinymce/jscripts/tiny_mce/tiny_mce.js',
					'editName.js'),
			true);
?>
<body>
  <div class="body">
<h1>
  <span class="right">
	<a href="editNameHelpen.html" target="help">? Help</a>
  </span>
  <?php print $title; ?>
</h1>
<?php
showTrace();

if (strlen($msg) > 0)
{		// errors
?>
<p class="message"><?php print $msg;?></p>
<?php
}		// errors
else
{		// no errors
?>
  <form name="nameForm" id="nameForm" action="updateName.php" method="post">
<div id="hidden">
	<input type="hidden" name="idir" id="idir"
			value="<?php print $idir; ?>">
	<input type="hidden" name="idnx" id="idnx"
			value="<?php print $idnx; ?>">
</div> <!-- id="hidden" -->
<div class="row" id="SurnameRow">
  <label class="column1" for="surname">
	    Surname:
  </label>
	<input type="text" name="surname" id="surname" size="32"
	    maxlength="120" class="white left"
	    value="<?php print str_replace('"','&quot;',$surname); ?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="GivenRow">
  <label class="column1" for="givenName">
	    Given Name:
  </label>
	<input type="text" name="givenName" id="givenName" size="50"
	    maxlength="120" class="white left"
	    value="<?php print str_replace('"','&quot;',$givenname);?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="namePrefixRow">
  <label class="column1" for="prefix">
	Name Prefix:
  </label>
	<input type="text" name="prefix" id="prefix" size="16" class="white left"
	    maxlength="120"
	    <?php print $readonly; ?> value="<?php print $prefix; ?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="nameSuffixRow">
  <label class="column1" for="title">
	Name Suffix:
  </label>
	<input type="text" name="title" id="title"
			size="16" class="white left" maxlength="120"
	    <?php print $readonly; ?> value="<?php print $nametitle; ?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="UserrefRow">
  <label class="column1" for="userref">
	User Reference:
  </label>
	<input type="text" name="userref" id="userref"
			size="16" class="white left" maxlength="50"
	    <?php print $readonly; ?> value="<?php print $userref; ?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="OrderRow">
  <label class="column1" for="order">
	Order:
  </label>
	<input type="text" name="order" id="order"
			size="6" class="ina rightnc" 
			readonly="readonly" value="<?php print $order; ?>">
  <div style="clear: both;"></div>
</div>
<div class="row" id="PreferredRow">
  <label class="column1" for="preferredaka">
	Preferred AKA:
  </label>
	<input type="checkbox" name="preferredaka" id="preferredaka"
	    <?php print $readonly; ?> <?php print $preferredakachecked; ?>>
  <div style="clear: both;"></div>
</div>
<div class="row" id="notesRow">
  <label class="column1" for="akanote">
	    Notes:
  </label>
  <textarea name="akanote" id="akanote" cols="64" rows="4"><?php
			    print $notes; ?></textarea>
  <div style="clear: both;"></div>
</div>
<?php
	// citations for the event
	$citParms	= array('idime'	=> $idnx,
						'type'	=> 10);
	$citations	= new CitationSet($citparms);
?>
<table id="citTable">
  <thead>
	<tr>
	    <th class="left">
			<input type="hidden" name="idime" id="idime"
					value="<?php print $idnx; ?>">
			<input type="hidden" name="citType" id="citType"
					value="10">
			Citations:
	    </th>
	    <th class="center">
			Source Name
	    </th>
	    <th class="center">
			Details (Page)
	    </th>
	</tr>
  </thead>
  <tbody>
<?php
	    foreach($citations as $idsx => $cit)
	    {		// loop through all citations to this fact
			$title	= str_replace('"','&quot;',$cit->getSource()->getTitle());
			$detail	= str_replace('"','&quot;',$cit->getDetail());
?>
	<tr id="sourceRow<?php print $idsx; ?>" >
	  <td id="firstButton<?php print $idsx; ?>">
	    <button type="button"
					id="editCitation<?php print $idsx; ?>">
			Edit Citation
	    </button>
	  </td>
	  <td id="sourceCell<?php print $idsx; ?>">
	    <input type="text" name="Source<?php print $idsx; ?>"
					id="Source<?php print $idsx; ?>"
					class="ina leftnc"
					value="<?php print $title; ?>"
					readonly="readonly"
					size="50">
	  </td>
	  <td>
	    <input type="text" name="Page<?php print $idsx; ?>"
					id="Page<?php print $idsx; ?>"
					class="white leftnc"
					value="<?php print $detail; ?>"
					size="32">
	  </td>
	  <td>
	    <button type="button"
					id="delCitation<?php print $idsx; ?>">
			Delete Citation
	    </button>
	  </td>
	</tr>
<?php
	    }		// loop through citations
?>
  </tbody>
  <tfoot>
	<tr>
	  <td>
	    <button type="button" id="AddCitation">
			<u>A</u>dd Citation
	    </button>
	</tr>
  </tfoot>
</table>
<p>
<?php
if ($debug)
{		// if debugging, submit request
?>
  <button type="submit" id="Submit">
	<u>U</u>pdate Name
  </button>
<?php
}		// if debugging, submit request
else
{		// otherwise use AJAX
?>
  <button type="update" id="updName">
	<u>U</u>pdate Name
  </button>
<?php
}		// otherwise use AJAX
?>
	&nbsp;
  <button type="button" id="Clear">
	<u>C</u>lear&nbsp;Notes
  </button>
</p>
  </form>
<?php
}		// no errors
?>
 </div> <!-- id="body" -->
<?php
dialogBot();
?>
	<div id="cittemplates" class="hidden">
	<table>
		<!-- The following is the template for what a new citation looks
		*    like before the user enters the citation description
		-->
		<tr id="sourceRow$rownum" >
		  <th>
		  </th>
		  <td>
				<select name="Source$rownum" id="Source$rownum"
						class="white left">
				</select>
		  </td>
		  <td>
				<input type="text" name="Page$rownum" id="Page$rownum"
						class="white leftnc" value="$detail" size="32">
				<input type="hidden" name="idime$rownum" id="idime$rownum"
						value="$idime">
				<input type="hidden" name="type$rownum" id="type$rownum" 
						value="$type">
		  </td>
		</tr>
		<!-- The following is the template for what a citation looks
		*    like after the user enters the citation description
		*    This should match exactly the layout for existing citations
		*    as formatted by PHP above.
		-->
		<tr id="sourceRow$idsx" >
		  <td>
		    <button type="button"
				    id="editCitation$idsx">
				Edit Citation
		    </button>
		  </td>
		  <td>
		    <input type="text" name="Source$idsx" id="Source$idsx"
				    class="ina leftnc"
				    value="$title"
				    readonly="readonly"
				    size="50">
		  </td>
		  <td>
		    <input type="text" name="Page$idsx" id="Page$idsx"
				    class="white leftnc"
				    value="$page"
				    size="32">
		  </td>
		  <td>
		    <button type="button"
				    id="delCitation$idsx">
				Delete Citation
		    </button>
		  </td>
		</tr>
	</table>
	
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
	
	</div> <!-- end of <div id="cittemplates"> -->
	<div class="balloon" id="Helpprefix">
      <p>This field is used to supply a name prefix, such as an honorific or
		indicator of rank or profession.  Typical examples include:
		Capt., Col., Dr., Elder, Father, Lord, Lt. Col., Major, Miss, Mr., Mrs.,
		Rev'd, or Sister.
      </p>
	</div>
	<div class="balloon" id="Helptitle">
      <p>This field is used to supply a name suffix.  Typical examples include:
		 J'r, Jr., Jun., S'r, Sen., Sr., III, IV, K.C., M.D.
      </p>
	</div>
	<div class="balloon" id="Helpsurname">
      <p>This field is used to update the alternate surname of an individual.
      </p>
	</div>
	<div class="balloon" id="HelpgivenName">
      <p>This field is used to update the alternate given name of an individual.
      </p>
	</div>
	<div class="balloon" id="Helporder">
		The order field specifies the position within the list of alternate names
		for the current individual.  At present there is no mechanism to change this.
	</div>
	<div class="balloon" id="Helpuserref">
		This field permits assigning a distinct user reference identifier to this
		alternate name.
	</div>
	<div class="balloon" id="Helppreferredaka">
		This field permits identifying this entry as the preferred alternate name.
	</div>
	<div class="balloon" id="HelpSource">
      <p>Read-only field displaying one of the master sources
		cited for this fact or event.
      </p>
	</div>
	<div class="balloon" id="HelpSourceSel">
      <p>This is a selection list used to identify a source which documents
		this event.  If you need to reference a source which has not previously been
		referenced in the database you can scroll up to the first entry in the
		selection list which says "Add New Source".  A short cut for this is pressing
		the letter "A" while the focus is in this selection list.
      </p>
	</div>
	<div class="balloon" id="HelpPage">
      <p>Field displaying the page number within the master source
		that documents this fact or event.
      </p>
	</div>
	<div class="balloon" id="HelpeditCitation">
      <p>Clicking on this button pops up a dialog to edit the details of the
		citation.
      </p>
	</div>
	<div class="balloon" id="HelpdelCitation">
      <p>Clicking on this button deletes this citation.
      </p>
	</div>
	<div class="balloon" id="HelpAddCitation">
      <p>Clicking on this button adds a row to the citation table that permits
		selecting the master source and specifying the page within that source
		that documents the current fact or event.
      </p>
	</div>
	<div class="balloon" id="HelpaddCitation">
      <p>Clicking on this button adds a row to the citation table that permits
		selecting the master source and specifying the page within that source
		that documents the current fact or event.
      </p>
	</div>
	<div class="balloon" id="HelpupdName">
      <p>Clicking on this button applies all of the changes made to this
		or name and closes the dialog.  Note that pressing the Enter key while
		editting any text field in this dialog also performs this function.
      </p>
	</div>
	<div class="balloon" id="HelpSubmit">
	  <p>Clicking on this button applies all of the changes made to this name
		displays the XML returned by the script updateName.php. 
		Note that pressing the Enter key while
		editting any text field in this dialog also performs this function.
	  </p>
	</div>
	<div class="balloon" id="HelpClear">
	  <p>Clicking on this button, or alternatively using the keyboard shortcut
		Alt-C, clears all of the text from the notes field associated with this event.
	  </p>
	</div>
	<div id="loading" class="popup">
		Loading...
	</div>
  </body>
</html>
