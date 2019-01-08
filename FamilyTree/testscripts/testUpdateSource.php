<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateSource.php						*
 *									*
 *  Display a web page for testing updateSource.php			*
 *									*
 *  Parameters:								*
 *	idsr	unique numeric key identifying instance of		*
 *		Source							*
 * 									*
 *  History: 								*
 *	2017/09/12	use get( and set(				*
 *									*
 * Copyright 2017 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/LegacySource.inc';

    / enable debug output
    $debug	= false;

    if (array_key_exists('idsr', $_GET))
    {	// get requested source
// get the individ by identifier
	$idsr	= $_GET['idsr'];
	$idsr	= (int)$idsr;
	$title	= 'Edit Source';
    }	// get requested source
    else
    {
	$title		= "Missing idsr parameter";
	$idsr		= 2;
    }	// missing parameter
    $source		= new Source($idsr);

    htmlHeader($title,
	       array("/jscripts/js20/http.js",
		     "/jscripts/CommonForm.js",
		     "/jscripts/util.js"));
?>
<body>
<?php
	pageTop(array());
?>
  <div class='body'>
    <h1>
<?php
    print $title;
?>
    </h1>
<?php
    if ($source)
    {	// source found
?>
<form name='srcForm' action='/updateSource.php' method='POST'>
  <div class='row'>
    <label class='column1' for='IDSR'>IDSR:</label>
    <input type='text' size='6' name='IDSR' id='IDSR' class='white rightnc'
	value='<?php print $source->get('idsr'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srcname'>srcname:</label>
    <input type='text' size='64' name='srcname' id='srcname' class='white left'
	value='<?php print $source->get('srcname'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srctitle'>srctitle:</label>
    <input type='text' size='64' name='srctitle' id='srctitle' class='white left'
	value='<?php print $source->get('srctitle'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='idst'>idst:</label>
    <input type='text' size='6' name='idst' id='idst' class='white rightnc'
	value='<?php print $source->get('idst'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srcauthor'>srcauthor:</label>
    <input type='text' size='64' name='srcauthor' id='srcauthor' class='white left'
	value='<?php print $source->get('srcauthor'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srcpubl'>srcpubl:</label>
    <input type='text' size='64' name='srcpubl' id='srcpubl' class='white left'
	value='<?php print $source->get('srcpubl'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srctext'>srctext:</label>
    <input type='text' size='64' name='srctext' id='srctext' class='white left'
	value='<?php print $source->get('srctext'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='psrctext'>psrctext:</label>
    <input type='text' size='6' name='psrctext' id='psrctext' class='white rightnc'
	value='<?php print $source->get('psrctext'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='fsrctext'>fsrctext:</label>
    <input type='text' size='6' name='fsrctext' id='fsrctext' class='white rightnc'
	value='<?php print $source->get('fsrctext'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='tsrctext'>tsrctext:</label>
    <input type='text' size='6' name='tsrctext' id='tsrctext' class='white rightnc'
	value='<?php print $source->get('tsrctext'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srcnote'>srcnote:</label>
    <input type='text' size='64' name='srcnote' id='srcnote' class='white left'
	value='<?php print $source->get('srcnote'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='psrcnote'>psrcnote:</label>
    <input type='text' size='6' name='psrcnote' id='psrcnote' class='white rightnc'
	value='<?php print $source->get('psrcnote'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='fsrcnote'>fsrcnote:</label>
    <input type='text' size='6' name='fsrcnote' id='fsrcnote' class='white rightnc'
	value='<?php print $source->get('fsrcnote'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='tsrcnote'>tsrcnote:</label>
    <input type='text' size='6' name='tsrcnote' id='tsrcnote' class='white rightnc'
	value='<?php print $source->get('tsrcnote'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srccallnum'>srccallnum:</label>
    <input type='text' size='64' name='srccallnum' id='srccallnum'
	class='white left'
	value='<?php print $source->get('srccallnum'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srctag'>srctag:</label>
    <input type='text' size='6' name='srctag' id='srctag' class='white rightnc'
	value='<?php print $source->get('srctag'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='qstag'>qstag:</label>
    <input type='text' size='6' name='qstag' id='qstag' class='white rightnc'
	value='<?php print $source->get('qstag'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='srcexclude'>srcexclude:</label>
    <input type='text' size='6' name='srcexclude' id='srcexclude' class='white rightnc'
	value='<?php print $source->get('srcexclude'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='idar'>idar:</label>
    <input type='text' size='6' name='idar' id='idar' class='white rightnc'
	value='<?php print $source->get('idar'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='idar2'>idar2:</label>
    <input type='text' size='6' name='idar2' id='idar2' class='white rightnc'
	value='<?php print $source->get('idar2'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='enteredsd'>enteredsd:</label>
    <input type='text' size='9' name='enteredsd' id='enteredsd' class='white rightnc'
	value='<?php print $source->get('enteredsd'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='enteredd'>enteredd:</label>
    <input type='text' size='64' name='enteredd' id='enteredd'
	class='white left'
	value='<?php print $source->get('enteredd'); ?>'>
  </div>
  <div class='row'>
    <label class='column1' for='filingref'>filingref:</label>
    <input type='text' size='64' name='filingref' id='filingref'
	class='white left'
	value='<?php print $source->get('filingref'); ?>'>
  </div>

<p>
  <button type='submit'>Update Source
</button>
</p>
</form>
<?php
    }	// source found
?>
<div/>
<?php
    pageBot();
?>
<div class='balloon' id='HelpHusbGivenName'>
<p>Edit the given name of the husband. 
</p>
</div>
</body>
</html>
