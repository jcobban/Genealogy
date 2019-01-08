<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testLocationsGetList.php						*
 *									*
 *  test the legacyLocation::getListByPrefix function			*
 *									*
 *  History:								*
 *	2011/07/29	created						*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *	2017/11/04	use RecordSet					*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Location.inc';
    require_once __NAMESPACE__ . '/RecordSet.inc';

    / disable debug output
    $debug	= false;

    if (array_key_exists('prefix', $_GET))
	$prefix	= $_GET['prefix'];
    else
	$prefix	= '';

    $subject	= rawurlencode("Test legacyLocation::getListByPrefix");
    htmlHeader('Test legacyLocation::getListByPrefix',
	       array('/jscripts/js20/http.js',
		'/jscripts/CommonForm.js',
		'/jscripts/util.js'));
?>
<body>
  <div class='topcrumbs'>
    <span class='left'>
	<a href='/genealogy.php'>Genealogy</a>:
	<a href='/genCountry.php?cc=CA'>Canada</a>:
	<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
	<a href='/legacyServices.php'>Family Tree Services</a>:
    </span>
    <div style='clear: both;'></div>
  </div>
  <div class='body'>
    <h1>
	Test legacyLocation::getListByPrefix Script
    </h1>
<form name='evtForm' action='testLocationsGetList.php' method='get'>
    <p>Prefix:
	<label class='column1' for='idir'>IDIR:</label>
	<input type='text' name='prefix' value='<?php print $prefix; ?>'>
    </p>
  <p>
    <label class='labelSmall' for='debug'>
	Debug:
    </label>
	<input type='checkbox' name='debug' id='debug'
		class='white left' value='Y'>
  </p>
    <p>
	<button type='submit'>Test</button>
    </p>
<?php
    $locations	= new RecordSet('Locations',
				array(array('Location'	=> $prefix,
					    'ShortName'	=> $prefix),
				      'limit'	=> $limit,
				      'order'	=> 'Location'));
    foreach($locations as $location)
    {
?>
<p><?php print $location->getName(); ?>
<?php
    }
?>
</form>
  </div>
  <div class='botcrumbs'>
<p>The data in this web site is generated on demand from a database created by
<a href='http:/www.legacyfamilytree.com/'>Legacy Family Tree
</a> 
by Millenia Corp.</p>
<table class='fullwidth'>
  <tr>
    <td class='label'>
	<a href='mailto:webmaster@jamescobban.net?subject=<?php print $subject; ?>'>Contact Author</a>
	<br/>
	<a href='/genealogy.php'>Genealogy</a>:
	<a href='/genCountry.php?cc=CA'>Canada</a>:
	<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
	<a href='/FamilyTree/Services.php'>Family Tree Services</a>:
	<a href='/FamilyTree/legacyIndex.html'>Top Index</a>:
    </td>
    <td class='right'>
	<img SRC='/logo70.gif' height='70' width='70' alt='James Cobban Logo'>
    </td>
  </tr>
</table>
  </div>
<div class='balloon' id='Helpidir'>
<p>Edit the unique numeric key (IDIR) of the individual 
for whom a marriage is to be added.
</p>
</div>
<div class='balloon' id='Helpchild'>
<p>Edit the unique numeric key (IDIR) of the individual 
for whom a set of parents is to be added.
</p>
</div>
</body>
</html>
