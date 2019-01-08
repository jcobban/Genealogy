<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editPicture.php														*
 *																		*
 *  Display a web page for editting one picture for an					*
 *  record from the Legacy database which is represented				*
 *  by an instance of Picture (a record in table tblBR).				*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		idbr	unique numeric key of instance of Picture				*
 *				if set to zero											*
 *				causes new Picture record to be created.				*
 *				if not available from record identified by idir 		*
 *		idtype	numeric type value as used by the Picture				*
 *				record to identify the record type and event a new		*
 *				picture is associated with								*
 *																		*
 *				IDTYPEPerson    = 0		tblIR.IDIR						*
 *				IDTYPEBirth     = 1		tblIR.IDIR						*
 *				IDTYPEChris     = 2		tblIR.IDIR						*
 *				IDTYPEDeath     = 3		tblIR.IDIR						*
 *				IDTYPEBuried    = 4		tblIR.IDIR						*
 *				IDTYPEMar       = 20	tblMR.IDMR						*
 *				IDTYPEEvent     = 30	tblER.IDER						*
 *				IDTYPESrcMaster = 40	tblSR.IDSR						*
 *				IDTYPESrcDetail = 41	tblSX.IDSX						*
 *				IDTYPEToDo      = 50	tblTD.IDTD						*
 *				IDTYPEAddress   = 70	tblAR.IDAR						*
 *				IDTYPELocation  = 71	tblLR.IDLR						*
 *				IDTYPETemple    = 72	tblTR.IDTR						*
 *																		*
 *		pictype		type of document									*
 *				PIC_TYPE_PICTURE= 0		image file						*
 *				PIC_TYPE_SOUND	= 1		sound file						*
 *				PIC_TYPE_VIDEO	= 2		video file						*
 *				PIC_TYPE_OTHER	= 3		other file						*
 *																		*
 *		idir	unique numeric key of instance of record as defined		*
 *				above to which a new image is associated				*
 * 																		*
 *  History: 															*
 *		2011/06/26		created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/12		replace tables with CSS for layout				*
 *		2014/03/06		label class name changed to column1				*
 *						for= attributes added to all labels				*
 *		2014/03/21		interface to Picture made more intuitive		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/29		support all associated record types				*
 *		2014/10/02		add prompt to confirm deletion					*
 *						improve titles for previously unused types		*
 *		2014/10/06		add support for audio and video files and for	*
 *						an audio caption on an image					*
 *			            improve parameter validation			        * 
 *		2014/11/29		do not reinitialize global variables set by		*
 *						common.inc										*
 *						print $warn, which may contain debug trace		*
 *		2015/05/18		do not escape contents of textarea, HTML is		*
 *						used by rich-text editor						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/15		class LegacyToDo renamed to ToDo				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Help.html to Helpen.html                 *
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
require_once __NAMESPACE__ . '/common.inc';

    // default title
    $title		= 'Edit Picture Error';

    // safely get parameter values
    // defaults
    $idtype	= Picture::IDTYPEPerson;		
    $pictype	= Picture::PIC_TYPE_PICTURE;
    $idbr	= null;
    $idir	= null;
    $picture	= null;

    foreach($_GET as $key => $value)
    {
		if ($debug)
		    $warn	.= "<p>\$_GET['$key']='$value'</p>\n";
		switch(strtolower($key))
		{
		    case 'idbr':
		    {		    // the unique record identifier of existing record
				if (ctype_digit($value))
				    $idbr	= intval($value, 10);
				else
				    $msg	.= "Invalid value for idbr='$value'. ";
				break;
		    }		    // the unique record identifier of existing record

		    case 'idtype':
		    {		// identifier of associated record type and event
				if (ctype_digit($value))
				{
				    $idtype	= intval($value, 10);
				    // textual description of picture type
				    if (array_key_exists($idtype, Picture::$IdTypeNames))
						$recordType	= Picture::$IdTypeNames[$idtype];
				    else
				    {
						$recordType	= "Invalid idtype=" . $idtype;
						$msg	.= "Invalid value for idtype='$value'. ";
				    }
				}
				else
				    $msg	.= "Invalid value for idtype='$value'. ";
				break;
		    }		// identifier of associated record type and event

		    case 'pictype':
		    {		// type of document
				if (ctype_digit($value) && $value <= 3)
				    $pictype	= intval($value, 10);
				else
				    $msg	.= "Invalid value for pictype='$value'. ";
				break;
		    }		// type of document

		    case 'idir':
		    {		// key of associated database record
				if (ctype_digit($value) && $value > 0)
				{
				    $idir	= intval($value, 10);
				}
				else
				    $msg	.= "Invalid value for idir='$value'. ";
				break;
		    }		// key of associated database record

		    case 'debug':
		    {		// handled by common code
				break;
		    }		// handled by common code

		    default:
		    {
                $warn	.= "<p>editPicture.php: " . __LINE__ . 
                            " Unexpected parameter $key='$value.</p>\n";
				break;
		    }
		}		// switch
    }			// loop through all parameters

    // collective validation and get instance of Picture
    try
    {
		if ($idbr > 0)
		{			// existing picture
		    $picture	= new Picture($idbr);
		    $idir	= $picture->get('idir');
		    $record	= $picture->getRecord();
		    $idtype	= $picture->getIdType();
		    $title	= "Edit Picture for ";
		}			// existing picture
		else
		{			// request to create new picture
		    $picture	= new Picture('',
							      $idir,
							      $idtype,
							      $pictype);
		    $title	= "Edit New Picture for ";
		    $idbr	= null;
		}			// request to create new picture
    }
    catch(Exception $e)
    {		// new Picture failed
		$picture	= null;
		$title		= "Invalid Picture";
		$msg		.= "Invalid Picture idbr=$idbr " . $e->getMessage();
		$idbr		= null;
    }		// new Picture failed

    // Obtain the associated database record according to type
    // and set up the title
    switch($idtype)
    {			// take action according to type
		case Picture::IDTYPEPerson:
		{		// individual
		    // image is associated with a record in tblIR
		    $record	= new Person(array('idir' => $idir));
		    $title	.= $record->getGivenName() .
						   ' ' . $record->getSurname();
		    break;
		}		// individual
		
		case Picture::IDTYPEBirth:	//  1  Birth
		case Picture::IDTYPEChris:	//  2  Chr
		case Picture::IDTYPEDeath:	//  3  Death
		case Picture::IDTYPEBuried:	//  4  Buried
		{		// individual event
		    // image is associated with a record in tblIR
		    $record	= new Person(array('idir' => $idir));
		    $title	.= $recordType . ' of ' . $record->getGivenName() .
						   ' ' . $record->getSurname();
		    break;
		}		// individual event
		
		case Picture::IDTYPEMar:		
		{		// 20 Marriage	tblMR.IDMR
		    $record	= new Family(array('idmr' => $idir));
		    // image is associated with a record in tblMR
		    $title	.= " Marriage between " .
						  $record->get('husbgivenname') . ' ' .
						  $record->get('husbsurname') . ' and ' .
						  $record->get('wifegivenname') . ' ' .
						  $record->get('wifesurname');
		    break;
		}		// 20 Marriage	tblMR.IDMR
		
		case Picture::IDTYPEEvent:
		{ 		// 30 Event	tblER.IDER
		    $record	= new Event(array('ider' => $idir));
		    // image is associated with a record in tblER
		    $title	.= " Event ";
		    break;
		} 		// 30 Event	tblER.IDER
		
		case Picture::IDTYPESrcMaster:
		{ 		// 40 Master Source tblSR.IDSR
		    $record	= new Source(array('idsr' => $idir));
		    // image is associated with a record in tblSR
		    $title	.= " Source " . $record->getName();
		    break;
		}		// 40 Master Source tblSR.IDSR
		
		case Picture::IDTYPESrcDetail:
		{ 		// 41 Source Detail tblSX.IDSX
		    $record	= new Citation(array('idsx' => $idir));
		    // image is associated with a record in tblSX
		    $title	.= " Citation " . $record->getCitTypeText();
		    break;
		}	 	// 41 Source Detail tblSX.IDSX
		
		case Picture::IDTYPEToDo:
		{ 		// 50 ToDo	tblTD.IDTD
		    $record	= new ToDo(array('idtd' => $idir));
		    $title	.= " To Do Item";
		    break;
		}		// 50 ToDo	tblTD.IDTD
		
		case Picture::IDTYPEAddress:
		{ 		// 70 Address	tblAR.IDAR
		    $record	= new Address(array('idar' => $idir));
		    // image is associated with a record in tblAR
		    $title	.= "Address " . $record->getName();
		    break;
		}	 	// 70 Address	tblAR.IDAR
		
		case Picture::IDTYPELocation:
		{ 		// 71 Location	tblLR.IDLR
		    $record	= new Location(array('idlr' => $idir));
		    // image is associated with a record in tblLR
		    $title	.= "Location " . $record->getName();
		    break;
		}	 	// 71 Location	tblLR.IDLR
		
		case Picture::IDTYPETemple:
		{		// 72 Temple	tblTR.IDTR
		    $record	= new Temple(array('idtr' => $idir));
		    // image is associated with a record in tblTR
		    $title	.= " Temple " . $record->getName();
		    break;
		}	 	// 72 Temple	tblTR.IDTR

		default:
		{
		    $msg	.= 'Invalid picture type ' . $idtype;
		    $record	= NULL;
		    $title	= "Invalid Picture Type"; 
		}
    }		// get appropriate database record

    htmlHeader($title,
				array(  '/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js',
						'/jscripts/Cookie.js',
						'citTable.js',
						'editPicture.js'),
				true);
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/genCanada.html'		=> 'Canada',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>	
  <div class="body">
    <h1>
      <span class="right">
		<a href="editPictureHelpen.html" target="help">? Help</a>
      </span>
      <?php print $title; ?> 
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {			// errors
?>
    <p class="message">
		<?php print $msg; ?> 
    </p>
<?php
    }			// errors
    else
    {			// no errors
		showTrace();

		// get information from record for display
		$picType	= $picture->getType();
		$picSelected	= array('','','','');
		$picSelected[$picType]	= ' selected="selected"';
		$location	= $picture->getURL();
		$caption	= $picture->getCaption();
		$date		= $picture->getDate();
		$desc		= $picture->getDesc();
		$print		= $picture->getPrint();
		$sound		= $picture->getSoundURL();
		$pref		= $picture->getPref();
		$userref	= $picture->getFilingRef();
?>
    <form name="picForm" action="updatePicture.php" method="post">
      <div>
		<input type="hidden" name="idir" value="<?php print $idir; ?>">
		<input type="hidden" name="idbr" value="<?php print $idbr; ?>">
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
      <div class="row" id="fileTypeRow">
		  <label class="column1" for="pictype">
				File Type:
		  </label>
		  <select size="1" name="pictype" id="pictype"
				class="white left"
				value="<?php print $pictype; ?>">
				<option value="0"<?php print $picSelected[0]; ?>>Image</option>
				<option value="1"<?php print $picSelected[1]; ?>>Sound</option>
				<option value="2"<?php print $picSelected[2]; ?>>Video</option>
				<option value="3"<?php print $picSelected[3]; ?>>Other</option>	
		    </select>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="imageURLRow">
		  <label class="column1" for="picnameurl">
				Image URL:
		  </label>
		    <input type="text" size="64" name="picnameurl" id="picnameurl"
				maxlength="511" class="white leftnc"
				value="<?php print str_replace('"','&quot;',$location); ?>">
		    <button type="button" id="browseFile">
				Browse
		    </button>
		  <div style="clear: both;"></div>
		</div>
<?php
		if (strlen($location) > 0)
		{		// file reference present
?>
		<div class="row" id="contentsRow">
		  <div class="left">
		  <label class="column1">
				Contents:
		  </label>
		  </div>
<?php
		    $picture->toHtml();
?>
		  <div style="clear: both;"></div>
		</div>
<?php
		}		// file reference present
?>
		<div class="row" id="captionRow">
		  <label class="column1" for="piccaption">
				Caption:
		  </label>
		    <input type="text" size="64" name="piccaption" id="piccaption"
				maxlength="255" class="white leftnc"
				value="<?php print str_replace('"','&quot;',$caption); ?>">
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="dateRow">
		  <label class="column1" for="picdate">
				Date:
		  </label>
		    <input type="text" size="16" name="picdate" id="picdate"
						maxlength="30" class="white left"
						value="<?php print $date; ?>">
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="descriptionRow">
		  <label class="column1" for="picdesc">
				Description:
		  </label>
		    <textarea  cols="50" rows="4" name="picdesc"
				id="picdesc"><?php print $desc; ?></textarea>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="printRow">
		  <label class="column1" for="picprint">
				Print:
		  </label>
		    <input type="checkbox" name="picprint" id="picprint"
				<?php if($print) print 'checked="checked"'; ?>>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="soundRow">
		  <label class="column1" for="picsoundnameurl">
				Sound File URL:
		  </label>
		    <input type="text" name="picsoundnameurl" id="picsoundnameurl"
						size="64" class="white leftnc"
						value="<?php print $sound; ?>">
		    <button type="button" name="browseSound" id="browseSound">
				Browse
		    </button>
		  <div style="clear: both;"></div>
		</div>
<?php
		if (strlen($sound) > 0)
		{		// sound file reference present
		    $filetype	= substr($sound, strlen($sound) - 3);
		    if ($filetype == 'mp3')
				$filetype	= 'mpeg';
?>
		<div class="row" id="playSoundRow">
		  <label class="column1">
				Play Sound:
		  </label>
		  <audio controls="controls">
		    <source src="<?php print $sound; ?>"
						type="audio/<?php print $filetype; ?>">
		    <span class="message">
				Your browser does not support the audio element.
		    </span>
		  </audio>
		  <div style="clear: both;"></div>
		</div>
<?php
		}		// sound file reference present
?>
		<div class="row" id="preferredRow">
		  <label class="column1" for="picpref">
				Preferred:
		  </label>
		    <input type="checkbox" name="picpref" id="picpref"
						<?php if($pref) print 'checked="checked"'; ?>>
		  <div style="clear: both;"></div>
		</div>
		<div class="row" id="filingRefRow">
		  <label class="column1" for="filingref">
				Filing Ref:
		  </label>
		    <input type="text" size="40" name="filingref" id="filingref"
				maxlength="50" class="white leftnc"
				value="<?php print $userref; ?>">
		  <div style="clear: both;"></div>
		</div>
      <p id="buttonRow">
		<button type="submit" id="updPicture">
		  <u>U</u>pdate Picture
		</button>
      </p>
    </form>
<?php
    }		// no errors
?>
</div> <!-- id="body"-->
<?php
    pageBot();
?>
<div class="hidden" id="templates">
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

</div> <!-- id="templates" -->
<div class="balloon" id="Helppicdate">
<p>Enter the date on which the picture was taken. 
The recommended format for entering dates is day of
the month, month name or supported abbreviation thereof, and year.
For example "12 July 1879" or "17 Nov 1906".
</p>
</div>
<div class="balloon" id="Helppictype">
<p>This selection list identifies the type of document that you are imbedding
in the record.  At the moment only "image" has full functionality.
</p>
</div>
<div class="balloon" id="Helppicname">
<p>This is a name given to the associated document to make it easier to find 
if you wish to refer to the same document from multiple places.
</p>
</div>
<div class="balloon" id="Helppiccaption">
<p>Text which describes the contents of the picture.  This is displayed
below the image.
</p>
</div>
<div class="balloon" id="Helppicnameurl">
<p>This is the uniform record locator describing where the document is
located on the web.  Only documents viewable on the web can be imbedded
in the tree.
</p>
</div>
<div class="balloon" id="HelpbrowseFile">
<p>Clicking on this button pops up a dialog to locate the document to
imbed into the tree.
</p>
</div>
<div class="balloon" id="Helppicdesc">
<p>This is an extended text area in which you can enter comments about the
document.
</p>
</div>
<div class="balloon" id="Helppicprint">
<p>This is a checkbox which is used to indicate whether or not the document
may be printed by the user.  This option is not currently implemented.
</p>
</div>
<div class="balloon" id="Helppicsoundnameurl">
<p>This is the uniform record locator describing where a sound clip file
associated with the document is located on the web. 
Only sound files that are available on the web can be imbedded
in the tree.  This option is not currently implemented.
</p>
</div>
<div class="balloon" id="HelpbrowseSound">
<p>Clicking on this button pops up a dialog to locate the sound file to
associate with the document.
</p>
</div>
<div class="balloon" id="Helppicpref">
<p>This is a checkbox which is used to indicate that this is the preferred
document to be displayed for the associated record.
This option is not currently implemented.
</p>
</div>
<div class="balloon" id="Helpfilingref">
<p>This is a text input field that can be used by the user to provide a
user record identifier for this document.
</p>
</div>
<div class="balloon" id="HelpupdPicture">
<p>Clicking on this button applies all of the changes made to this fact
or picture and closes the dialog.  Note that pressing the Enter key while
editting any text field in this dialog also performs this function.
</p>
</div>
</body>
</html>
