<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testReplyBlog.php							*
 *									*
 *  test the replyBlogXml.php script					*
 *									*
 *  History:								*
 *	2014/03/27	use common layout routines			*
 *			use HTML 4 features, such as <label>		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('yes'))
	$msg	.= 'Current user is not authorized to use this function. ';

    htmlHeader("Test Reply Blog",
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> "Genealogy",
		  "/genCountry.php?cc=CA"		=> "Canada",
		  "/Canada/genProvince.php?domain=CAON"	=> "Ontario",
		  "legacyServices.php"		=> "Family Tree Services"));
?>
  <div class='body'>
    <h1>
	Test Reply Blog
    </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
    <p class='message'>
	<?php print $msg; ?>
    </p>
<?php
    }
    else
    {	// no errors
?>
<form name='evtForm' action='/replyBlogXml.php' method='post'>
  <p>
    <label class='column1' for='id'>blid:
    </label>
	<input type='text' name='blid' id='blid'
		class='white rightnc' value='0'>
  </p>
  <p>
    <textarea name='message' rows='5' cols='100'>[enter message]</textarea>
  </p>
  <p>
	<button type='submit'>Blog</button>
  </p>
</form>
<?php
    }	// no errors
?>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpid'>
<p>Edit the unique numeric key (IDIR) of the individual to update.
</p>
</div>
</body>
</html>
