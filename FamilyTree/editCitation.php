<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editCitation.php													*
 * 																		*
 *  Display a web page for editting a specific citation to a source.	*
 * 																		*
 *  History: 															*
 * 		2010/08/21		Change to use new page format					*
 *		2010/09/05		Eliminate space characters from <textarea>s		*
 *		2010/10/15		Remove header and trailer from dialogs.			*
 *						Add field level help information.				*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2011/01/10		use LegacyRecord::getField method				*
 *						handle exception from new Citation				*
 *		2011/01/28		do no permit modifying type						*
 *		2011/02/27		improve separation of HTML and Javascript		*
 *		2012/01/13		change class names								*
 *		2013/03/31		clean up parameter handling						*
 *		2013/09/20		add help text for update button					*
 *						correct class of text field for citation detail	*
 *						display event type as simple text, not select	*
 *						pretty up the generated HTML 					*
 *						add testing option								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/06		label class name changed to column1				*
 *						add labels on prompts for source and page		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with a Citation							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/03/08		class Citation no longer has method				*
 *						getTitle										*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 * 																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('edit'))
    {		// not authorized
		$msg	.= 'User not authorized to edit citations. ';
    }		// not authorized

    // locate the required citation based upon the parameters passed
    // to the page
    $idsx		= 0;
    $citation		= null;
    $title		= "Missing Identifier of Citation";

    foreach($_GET as $key => $value)
    {			// loop through all parameters
		switch(strtolower($key))
		{		// act on specific parameter
		    case 'idsx':
		    {		// identifier of instance of Citation
				if (strlen($value) > 0)
				{		// get the requested citation
				    $idsx	= $value;
				    try
				    {
						$citation	= new Citation(array('idsx' => $idsx));
				        $title		= "Edit Citation";
				    }
				    catch(Exception $e)
				    {
						$title		= "Citation $idsx Not Found";
						$msg		.= $e->getMessage();
						$citation	= null;
				    }
				}		// get the requested citation
				break;
		    }		// identifier of instance of Citation

		    case 'tableid':
		    case 'formid':
		    {
				break;
		    }		// table id in invoking page

		    case 'submit':
		    {
				break;
		    }		// table id in invoking page

		    case 'text':
		    {		// used by Javascript
				break;
		    }		// used by Javascript

		    case 'debug':
		    {
				break;
		    }		// table id in invoking page

		    default:
		    {		// anything else
				$msg	.= "Unexpected parameter $key='$value'. ";
				break;
		    }		// anything else
		}		// act on specific parameter
    }			// loop through all parameters

    if ($idsx == 0)
    {
		$msg		.= "Missing idsx parameter. ";
    }		// missing parameter

    htmlHeader($title,
				array(  '/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js',
						'/tinymce/jscripts/tiny_mce/tiny_mce.js',
						'editCitation.js'),
				true);
?>
<body>
  <div class="body">
    <h1>
      <span class="right">
		<a href="editCitationHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {		// error message
?>
    <p class="message"><?php print $msg; ?></p>
<?php
    }		// error message
    else
    if ($citation)
    {		// citation found
		$citType	= Citation::$intType[$citation->getCitType()];
		$citKey		= Citation::$recType[$citation->getCitType()];
		$citDetail	= $citation->getDetail(); 
		$citDetailText	= $citation->getDetailText();
		$citDetailNote	= $citation->getDetailNote();
?>
  <form name="citForm" id="citForm" action="updateCitation.php" method="post">
    <div class="row" id="identRow">
      <input type="hidden" name="idsx" id="idsx"
				value="<?php print $idsx; ?>">
      <label class="column1" for="Type">
		    Event Type:
      </label>
      <input type="hidden" name="Type" id="Type"
				value="<?php print $citation->getCitType(); ?>">
      <span class="label">
		<?php print $citType; ?>
      </span>
      <label class="label" for="idime">
		<?php print $citKey; ?>: 
      </label>
		<input type="text" name="idime" id="idime" size="6"
				readonly="readonly" class="ina rightnc"
				value="<?php print $citation->getIdime(); ?>">
      <div style="clear: both;"></div>
    </div>
    <div class="row" id="sourceRow">
      <label class="column1" for="IDSR">
		    Source:
      </label>
		<select name="IDSR" id="IDSR" class="white left">
		    <option value="<?php print $citation->getIdsr(); ?>">
				<?php print $citation->getSource()->getTitle(); ?> 
		    </option>
		</select>
    </div>
    <div class="row" id="detailRow">
      <label class="column1" for="SrcDetail">
		    Page Id:
      </label>
		<input type="text" name="SrcDetail" id="SrcDetail" size="76"
				class="white leftnc"
				value="<?php print $citDetail; ?>">
      <div style="clear: both;"></div>
    </div>
    <div class="row" id="textRow">
      <label class="column1">
		    Text of Source:
      </label>
		<textarea name="SrcDetText" id="SrcDetText" rows="4"
				cols="64"><?php print $citDetailText; ?></textarea>
      <div style="clear: both;"></div>
    </div>
    <div class="row" id="noteRow">
      <label class="column1"> 
		    Comments:
      </label>
		<textarea name="SrcDetNote" id="SrcDetNote" rows="4"
				cols="64"><?php print $citDetailNote; ?></textarea>
      <div style="clear: both;"></div>
    </div>
    <p id="buttonRow">
<?php
		if ($debug)
		{	// testing
?>
      <button type="submit" id="update">
<?php
		}	// testing
		else
		{	// not testing
?>
      <button type="button" id="update">
<?php
		}	// not testing
?>
		<u>U</u>pdate Citation
      </button>
		&nbsp;
		<button type="button" id="Pictures">
		  <u>P</u>ictures
		</button>
		<input type="hidden" name="PicIdType" id="PicIdType" 
				value="41">
    </p>
</form>
<?php
    }		// citation found
?>
  </div>
<?php
    dialogBot();
?>
<div class="balloon" id="HelpIDSR">
<p>Select the master source entry by name for this citation.
</p>
</div>
<div class="balloon" id="HelpType">
<p>This field displays the type of event for which this is a citation.
</p>
</div>
<div class="balloon" id="Helpidime">
<p>This read-only field displays the record number of the record that
contains the information about the associated fact or event.
</p>
</div>
<div class="balloon" id="HelpSrcDetail">
<p>Enter the primary citation information, usually identifying the specific
page within the master source where the information is located.
</p>
</div>
<div class="balloon" id="HelpSrcDetText">
<p>Enter the actual text from the source that documents this fact.
</p>
</div>
<div class="balloon" id="HelpSrcDetNote">
<p>Enter comments or other information that is related to the citation, but
not found within the text of the source.
</p>
</div>
<div class="balloon" id="Helpupdate">
<p>Click on this button, or use the keyboard short-cut Alt-U, to
apply the update.
</p>
</div>
  <div class="balloon" id="HelpPictures">
    Click this button to open a dialog for managing the pictures associated
    with the citation.
  </div>
</body>
</html>
