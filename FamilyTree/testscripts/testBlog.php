<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testBlog.php							*
 *									*
 *  test the postBlogXml.php script					*
 *									*
 *  Parameters:								*
 *	idir	unique numeric key of instance of legacyIndiv		*
 *									*
 *  History:								*
 *	2014/03/27	use common layout routines			*
 *			use HTML 4 features, such as <label>		*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    $idname		= 'id';
    $idvalue		= '0';
    foreach($_GET as $key => $value)
    {
	$key	= strtolower($key);
	if (substr($key, 0, 2) == 'id')
	{
	    $idname	= $key;
	    $idvalue	= $value;
	}
    }
    htmlHeader("Test Add Blog",
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> "Genealogy",
		  "/genCountry.php?cc=CA"		=> "Canada",
		  "/Canada/genProvince.php?domain=CAON"	=> "Ontario",
		  "/FamilyTree/Services.php"	=> "Family Tree Services"));
?>
  <div class='body'>
    <h1>
	Test Add Blog
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
<form name='evtForm' action='/postBlogXml.php' method='post'>
  <p>
    <label class='labelSmall' for='<?php print $idname; ?>'>
	<?php print strtoupper($idname); ?>:
    </label>
	<input type='text' name='<?php print $idname; ?>'
			   id='<?php print $idname; ?>'
		class='white rightnc' value='<?php print $idvalue; ?>'>
  </p>
<?php
	if ($idname == 'id')
	{
?>
  <p>
    <label class='labelSmall' for='tablename'>Table:
    </label>
	<input type='text' name='tablename' id='tablename'
		class='white leftnc' value='tblIR'>
  </p>
<?php
	}
?>
  <p>
    <label class='labelSmall' for='email'>Email:
    </label>
	<input type='text' name='email' id='email'
		class='white leftnc' value=''>
  </p>
  <p>
    <label class='labelSmall' for='subject'>Subject:
    </label>
	<input type='text' name='subject' id='subject'
		class='white leftnc'>
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
