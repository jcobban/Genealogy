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
 *	index	unique numeric key of instance of Blog			*
 *									*
 *  History:								*
 *	2014/12/02	enclose comment blocks				*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Blog.inc';

    htmlHeader("Test Delete Blog",
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
    <h1>
	Test Delete Blog
    </h1>
<form name='evtForm' action='/delBlogXml.php' method='post'>
     <div class='row'>
    <label class='column1' for='blid'>BL_Index: </label>
	<input type='text' class='white rightnc' name='blid' id='blid' value='0'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='column1' for='debug'>Debug:
      </label>
	<input type='checkbox' name='debug' id='debug' 
		class='white left' value='Y'>
      <div style='clear: both;'></div>
     </div>
  <button type='submit'>Delete</button>
</p>
</form>
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
