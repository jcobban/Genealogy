<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  GedcomUpload.php													*
 *																		*
 *  Display a web page for uploading a GEDCOM 5.5 file into the			*
 *  Legacy database.													*
 *																		*
 *  Parameters (passed by method="get"):								*
 * 																		*
 *  History: 															*
 *		2012/05/12		created											*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    // default title
    $title		= 'Upload GEDCOM File';

    // safely get parameter values
    // defaults

    if (!canUser('edit'))
		$msg	.= 'You must be signed on with authority to update the database to use this feature. ';

    foreach($_GET as $key => $value)
    {
		switch(strtolower($key))
		{
		    case 'debug':
		    {		// handled by common.inc
				break;
		    }		// handled by common.inc

		    default:
		    {
				$msg	.= "Unexpected parameter $key='$value'";
				break;
		    }
		}	// switch
    }		// loop through all parameters

    htmlHeader($title,
				array(  '/jscripts/CommonForm.js',
						'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'GedcomUpload.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
  <div class="fullwidth">
      <span class="h1">
		<?php print $title; ?> 
      </span>
      <span class="right">
		<a href="GedcomUploadHelpen.html" target="help">? Help</a>
      </span>
      <div style="clear: both;"></div>
  </div>
<?php
    if (strlen($msg) > 0)
    {		// errors
?>
    <p class="message">
		<?php print $msg; ?> 
    </p>
<?php
    }		// errors
    else
    {		// no errors
?>
<form name="fileForm" action="GedcomUpdate.php" method="post"
		enctype="multipart/form-data">
<p>
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="1500000" />
    <!-- Name of input element determines name in $_FILES array -->
    <span class="label">Send this file:</span> 
    <input name="userfile" type="file"
				size="64"
				accept="application/x-gedcom"/>
</p>
<p>
  <button type="submit" id="submit">
		<u>U</u>pload
  </button>
</p>
</form>
<?php
    }		// no errors
?>
</div>
<?php
    pageBot();
?>
<div class="balloon" id="Helpsubmit">
<p>Clicking on this button applies all of the changes made to this fact
or picture and closes the dialog.  Note that pressing the Enter key while
editting any text field in this dialog also performs this function.
</p>
</div>
</body>
</html>
