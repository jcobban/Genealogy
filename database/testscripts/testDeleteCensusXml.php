<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDeleteCensusXml.php						*
 *									*
 *  test the deleteCensusXml.php script					*
 *									*
 *  History:								*
 *	2015/07/02	access PHP includes using include_path		*	
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
 
    $title	= "Test deleteCensusXml.php";
    $subject	= rawurlencode($title);

    htmlHeader($title);
?>
<body>
<?php
    pageTop(array("/genealogy.php"	=> "Genealogy",
		"/genCanada.html"	=> "Canada",
		"/genCensuses.php"	=> "Censuses"));
?>
<div class='body'>
    <h1>
	<?php print $title; ?> 
    </h1>
<form name='evtForm' action='/database/deleteCensusXml.php' method='post'>
    <p>
	<input type='text' name='CensusId' value=''>
<p>
  <button type='submit'>Execute</button>
</p>
</form>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpCensusId'>
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
</p>
</div>
</body>
</html>
