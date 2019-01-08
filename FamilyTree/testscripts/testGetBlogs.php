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
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    $username	= $userid;	// default to current user

    foreach($_POST as $key => $value)
    {
	if ($key == 'username' && strlen($value) > 0)
	{
	    $username	= $value;
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
	Test Get Blogs
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
	$parms		= array('username'	=> $username);
	$blogs		= new RecordSet('Blogs', $parms);
?>
    <p><?php print count($blogs); ?> blog entries matched 
	username='<?php print $username; ?>'</p>
    <table class='summary'>
<?php
	$class	= 'odd';
	foreach($blogs as $blid => $blog)
	{
?>
      <tr>
	<th class='label'><?php print $blog->get( 'bl_index'); ?></th>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_datetime'); ?></td>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_username'); ?></td>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_table'); ?></td>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_keyname'); ?></td>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_keyvalue'); ?></td>
	<td class='<?php print $class;?>'><?php print $blog->get( 'bl_text'); ?></td>
      </tr>
<?php
	    if ($class == 'odd')
		$class	= 'even';
	    else
		$class	= 'odd';
	}		// loop through all blogs
?>
    </table>
<form name='evtForm' action='testGetBlogs.php' method='post'>
  <p>
    <label class='labelSmall' for='username'>
	User Name:
    </label>
	<input type='text' name='username'
			   id='username'
		class='white rightnc' value='<?php print $username; ?>'>
  </p>
  <p>
	<button type='submit'>Display</button>
  </p>
</form>
<?php
    }	// no errors
?>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='Helpusername'>
<p>Specify the user name for which blog entries are to be displayed.
</p>
</div>
</body>
</html>
