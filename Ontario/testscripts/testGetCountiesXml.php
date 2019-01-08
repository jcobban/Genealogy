<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetCountiesXml.php						*
 *									*
 *  test the CountiesListXml.php script					*
 *									*
 *  History:								*
 *	2014/12/26	created						*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug	= false;
    $subject	= rawurlencode("Test Get Counties List XML");

    htmlHeader('Test Get Counties List Xml');
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/genCountry.php?cc=CA'		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'		=> 'Ontario'));
?>
<div class='body'>
    <h1>
	Test CountiesListXml.php
    </h1>
  <form name='evtForm' action='../CountiesListXml.php' method='get'>
    <p>
      <label class='column1'>Domain:</label>
	<input type='text' name='Domain' value='CAON'>
    </p>
  <p>
    <label class='labelSmall' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </p>
    <p>
      <button type='submit'>Text</button>
    </p>
  </form>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpDomain'>
<p>Specify the domain for which a list of counties is to be obtained.
</p>
</div>
</body>
</html>
