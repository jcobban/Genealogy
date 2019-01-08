<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editPictures.php													*
 *																		*
 *  Display a web page for editing the pictures of a particular 		*
 *  record in the genealogy database									*
 * 																		*
 *  Parameters (passed by method=get) 									*
 *		idir			unique numeric key of Person, or			    *
 *		idmr			unique numeric key of Family, or			    *
 *		ider			unique numeric key of Event, or					*
 *		idsr			unique numeric key of Source, or				*
 *		idsx			unique numeric key of Citation, or				*
 *		idtd			unique numeric key of ToDo, or					*
 *		idar			unique numeric key of Address, or				*
 *		idlr			unique numeric key of Location, or				*
 *		idtr			unique numeric key of Temple, or				*
 *		idtype			type of record, only required for idir			*
 *																		*
 *  History:															*
 *		2011/05/26		created											*
 *		2012/01/13		change class names								*
 *		2013/02/24		use standard presentation classes				*
 *						field names use IDBR, not rownum				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *						fully support all associated record types		*
 *		2014/10/03		support both numeric and textual identifiers	*
 *		2014/10/05		prompt for confirmation of delete				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/03/07		use getName to get identification of all records*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/04		class LegacyToDo renamed to ToDo				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/17		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the record identifier
    $idir		    = null;
    $idtypetxt		= 'Indiv';
    foreach($_GET as $key => $value)
    {				// loop through all parameters
		if ($debug)
		    print "<p>\$_GET['$key']='$value'</p>\n";
		switch(strtolower($key))
		{			// act on specific keys
		    case 'idir':
		    {			// idir reference to Person
				$idir	= $value;
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idir='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Person

		    case 'idmr':
		    {			// idir reference to Family
				$idir		= $value;
				$idtypetxt	= 'Marriage';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idmr='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Family

		    case 'ider':
		    {			// idir reference to Event
				$idir		= $value;
				$idtypetxt	= 'Event';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value ider='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Event

		    case 'idsr':
		    {			// idir reference to Source
				$idir		= $value;
				$idtypetxt	= 'Source';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idsr='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Source

		    case 'idsx':
		    {			// idir reference to Citation
				$idir		= $value;
				$idtypetxt	= 'Citation';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idsx='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Citation

		    case 'idtd':
		    {			// idir reference to ToDo
				$idir		= $value;
				$idtypetxt	= 'To Do';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idtd='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to ToDo

		    case 'idar':
		    {			// idir reference to Address
				$idir		= $value;
				$idtypetxt	= 'Address';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idar='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Address

		    case 'idlr':
		    {			// idir reference to Location
				$idir		= $value;
				$idtypetxt	= 'Location';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idlr='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Location

		    case 'idtr':
		    {			// idir reference to Temple
				$idir		= $value;
				$idtypetxt	= 'Temple';
				if (strlen($idir) == 0 || !ctype_digit($idir))
				{		// not numeric
				    $msg	.= "Invalid value idtr='$idir'. ";
				}		// not numeric
				break;
		    }			// idir reference to Temple

		    case 'idtype':
		    {			// type identifier
				$idtypetxt	= $value;
				break;
		    }			// type identifier
		}			// act on specific keys
    }				// loop through all parameters

    // set the numeric record type based upon the parameter
    // and initialize the appropriate record pointer
    $record	= null;

    try {
		switch(strtolower($idtypetxt))
		{
		    case '0':			// 0  Person	tblIR.IDIR
		    case 'indiv':		// 0  Person	tblIR.IDIR
		    {
				$idtype		= Picture::IDTYPEPerson;
				$record		= new Person(array('idir' => $idir));
				$title		= "Edit Pictures for " .
							      $record->getName();
				break;
		    }

		    case '1':
		    case 'birth':		// 1  Birth	 	tblIR.IDIR
		    {
				$idtype		= Picture::IDTYPEBirth;
				$record		= new Person(array('idir' => $idir));
				$title		= "Edit Pictures for Birth of " .
							      $record->getName();
				break;
		    }

		    case '2':
		    case 'chris':		// 2  Chr		tblIR.IDIR
		    {
				$idtype		= Picture::IDTYPEChris;
				$record		= new Person(array('idir' => $idir));
				$title		= "Edit Pictures for Christening of " .
							      $record->getName();
				break;
		    }

		    case '3':
		    case 'death':		// 3  Death		tblIR.IDIR
		    {
				$idtype		= Picture::IDTYPEDeath;
				$record		= new Person(array('idir' => $idir));
				$title		= "Edit Pictures for Death of " .
							  $record->getName();
				break;
		    }

		    case '4':
		    case 'buried':		// 4  Buried		tblIR.IDIR
		    {
				$idtype		= Picture::IDTYPEBuried;
				$record		= new Person(array('idir' => $idir));
				$title		= "Edit Pictures for Burial of " .
							  $record->getName();
				break;
		    }

		    case '20':
		    case 'mar':			// 20 Marriage		tblMR.IDMR
		    {
				$idtype		= Picture::IDTYPEMar;
				$record		= new Family(array('idmr' => $idir));
				$title		= "Edit Pictures for Marriage of " .
							  $record->getName();
				break;
		    }

		    case '30':
		    case 'event':		// 30 Event		tblER.IDER
		    {
				$idtype		= Picture::IDTYPEEvent;
				$record		= new Event(array('ider' => $idir));
				$title		= "Edit Pictures for Event " .
							  $record->getName();
				break;
		    }

		    case '40':
		    case 'srcmaster':		// 40 Master Source	tblSR.IDSR
		    {
				$idtype		= Picture::IDTYPESrcMaster;
				$record		= new Source(array('idsr' => $idir));
				$title		= "Edit Pictures for Master Source " .
							  $record->getName();
				break;
		    }

		    case '41':
		    case 'srcdetail':		// 41 Source Detail	tblSX.IDSX
		    {
				$idtype		= Picture::IDTYPESrcDetail;
				$record		= new Citation(array('idsx' => $idir));
				$title		= "Edit Pictures for Source Citation " .
							  $record->getName();
				break;
		    }

		    case '50':
		    case 'to do':		// 50 To Do		tblTD.IDTD
		    {
				$idtype		= Picture::IDTYPEToDo;
				$record		= new ToDo(array('idtd' => $idir));
				$title		= "Edit Pictures for To Do Item " .
							  $record->getName();
				break;
		    }

		    case '70':
		    case 'address':		// 70 Address		tblAR.IDAR
		    {
				$idtype		= Picture::IDTYPEAddress;
				$record		= new Address(array('idar' => $idir));
				$title		= "Edit Pictures for Address " .
							  $record->getName();
				break;
		    }

		    case '71':
		    case 'location':		// 71 Location		tblLR.IDLR
		    {
				$idtype		= Picture::IDTYPELocation;
				$record		= new Location(array('idlr' => $idir));
				$title		= "Edit Pictures for Location " .
							  $record->getName();
				break;
		    }

		    case '72':
		    case 'temple':		// 72 Temple		tblTR.IDTR
		    {
				$idtype		= Picture::IDTYPETemple;
				$record		= new Temple(array('idtr' => $idir));
				$title		= "Edit Pictures for Temple " .
							  $record->getName();
				break;
		    }

		    default:
		    {
				$msg	.= 'Invalid value of idtype=\'' . $idtypetxt . "'. ";
				$title	= "Edit Pictures for Unknown Type $idtypetxt"; 
		    }		// default
		}		// switch on idtype text

		$isOwner	= $record->isOwner();
    } catch (Exception $e) {
		$msg	.= "Unable to get associated record. " . $e->getMessage();
				$msg	.= "IdType='$idtypetxt'. ";
		$title	= 'Edit Pictures: Failed';
    }

    htmlHeader($title,
				array(  '/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js',
						'editPictures.js'));
?>
<body>
  <div class="body">
    <h1>
      <span class="right">
		<a href="editPicturesHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
      <div style="clear: both;"></div>
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
    else
    if ($record)
    {		// record found
?>
  <form name="picsForm" action="updatePictures.php" method="post">
    <div>
		<input type="hidden" name="idir" value="<?php print $idir; ?>">
		<input type="hidden" name="idtype" value="<?php print $idtype; ?>">
<?php
		if ($debug)
		{
?>
		<input type="hidden" name="Debug" value="Y">
<?php
		}
?>
    </div>
    <table class="details">
      <thead>
		<tr>
		  <th class="colhead">
		      Caption
		  </th>
		  <th class="colhead">
		      File Type
		  </th>
		  <th class="colhead">
		      Date
		  </th>
		  <th class="colhead" colspan="2">
		      Action
		  </th>
		</tr>
      </thead>
      <tbody>
<?php
		$picParms	= array('IDIR'	=> $idir,
							'IDType'=> $idtype);
		$pictureSet	= new RecordSet('Pictures', $picParms);
		foreach($pictureSet as $idbr => $picture)
		{		// loop through pictures
		    $picType	= $picture->getTypeText();
		    $date	= $picture->getDate();
		    $caption	= str_replace('"','&quot;',$picture->getCaption()); 
		    $locn	= str_replace('"','&quot;',$picture->getURL()); 
?>
		<tr id="PictureRow<?php print $idbr; ?>">
		  <td>
				<input type="text" readonly="readonly" size="40"
						class="ina leftnc"
						name="Caption<?php print $idbr; ?>"
						id="Caption<?php print $idbr; ?>"
						value="<?php print $caption; ?>">
		  </td>
		  <td>
				<input type="text" readonly="readonly" size="12"
						class="ina leftnc"
						name="Type<?php print $idbr; ?>"
						id="Type<?php print $idbr; ?>"
						value="<?php print $picType; ?>" >
		  </td>
		  <td>
				<input type="text" readonly="readonly" size="12"
						class="ina leftnc"
						name="Date<?php print $idbr; ?>"
						id="Date<?php print $idbr; ?>"
						value="<?php print $date; ?>">
		  </td>
		  <td>
		    <button type="button" id="Edit<?php print $idbr; ?>">
				Edit Picture
		    </button>
		  </td>
		  <td>
		    <button type="button" id="Del<?php print $idbr; ?>">
				Delete Picture
		    </button>
		  </td>
		</tr>
<?php
		}		// loop through pictures
?>
		<tr id="AddPictureRow">
		  <td style="width: 40em;">
		  </td>
		  <td style="width: 12em;">
		  </td>
		  <td style="width: 12em;">
		  </td>
		  <td>
		  </td>
		  <td>
		    <button type="button" id="Add">
		      <u>A</u>dd Picture
		    </button>
		  </td>
		</tr>
      </tbody>
    </table>
<p>
    <button type="button" id="Close">
		<u>C</u>lose
    </button>
&nbsp;
    <button type="button" id="Order">
		<u>O</u>rder Pictures by Date
    </button>
</p>
</form>
<?php
    }		// individual found
?>
</div> <!-- end of <div id="body"> -->
<?php
    dialogBot();
?>
<div id="templates" class="hidden">

  <!-- template for confirming the deletion of a citation-->
  <form name="PicDel$template" id="PicDel$template">
    <p class="message">$msg</p>
    <p>
      <button type="button" id="confirmDelete$idbr">
		OK
      </button>
      <input type="hidden" id="rownum$idbr" name="rownum$idbr"
				value="$rownum">
      <input type="hidden" id="formname$idbr" name="formname$idbr"
				value="$formname">
		&nbsp;
      <button type="button" id="cancelDelete$idbr">
		Cancel
      </button>
    </p>
  </form>
</div> <!-- end of <div id="templates"> -->
<div class="balloon" id="HelpDate">
<p>This is a read-only field that displays the date on which the picture was
taken.
</p>
</div>
<div class="balloon" id="HelpCaption">
<p>This is a read-only field that displays the caption which is displayed
with the picture.
</p>
</div>
<div class="balloon" id="HelpType">
<p>This is a read-only field that displays the type of associated document.
</p>
</div>
<div class="balloon" id="HelpEdit">
<p>Clicking on this button opens a dialog that permits you to modify
information about the picture or fact, including the type of
picture, date, location, additional information, and source citations.
</p>
</div>
<div class="balloon" id="HelpDel">
<p>Clicking on this button deletes the picture.
</p>
</div>
<div class="balloon" id="HelpAdd">
<p>Clicking on this button opens a dialog that permits you to define
the details of a new picture or fact for the current individual.
</p>
</div>
<div class="balloon" id="HelpClose">
<p>Clicking on this button closes the dialog.  Note that all of the changes
you have made to the picture list have already been applied to the database.
</p>
</div>
<div class="balloon" id="HelpOrder">
<p>Clicking on this button reorders the pictures and facts according to the date
on which they occurred or were observed.  This makes the displayed description
of the individual more coherent.
</p>
</div>
</body>
</html>
