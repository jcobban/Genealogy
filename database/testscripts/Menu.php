<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Menu.php								*
 *									*
 *  This script displays a menu of available test drivers.		*
 *									*
 *    History:								*
 *	2014/12/02	created						*
 *	2014/12/29	correct breadcrumbs				*
 *									*
 *  Copyright 2014 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . "//home/jcobban/includes/common.inc";

    if (!canUser('yes'))
	$msg	.= 'Only administrators are authorized to use this function. ';

    $title	= "Menu of Test Scripts";
    htmlHeader($title,
		array('../../jscripts/util.js',
		      '../../jscripts/js20/http.js',
		      'Menu.js'),
		false);
?>
<body>
<?php
    pageTop(array(
	'/genealogy.php'		=> "Genealogy",
	'/FamilyTree/Services.php'	=> "Family Services"));
?>
<div class='body'>
  <h1>Menu of Census Test Drivers
    <span class='right'>
	<a href='MenuHelpen.html' target='help'>? Help</a>
    </span>
  </h1>
<?php
    if (strlen($warn) > 0)
    {			// report errors in setup
?>
   <p class='warning'><?php print $warn; ?></p>
<?php
    }			// report errors in setup
    if (strlen($msg) > 0)
    {			// report errors in setup
?>
   <p class='message'><?php print $msg; ?></p>
<?php
    }			// report errors in setup
    else
    {			// authorized
?>
    <h3>Test Drivers:</h3>
<?php
	$scripts	= array();
	$dh	= opendir('.');
	if ($dh)
	{		// found directory
	    while (($filename = readdir($dh)) !== false)
	    {		// loop through files
		if (strlen($filename) > 4 &&
		    substr($filename, strlen($filename) - 4) == '.php' &&
		    $filename != 'Menu.php')
		    $scripts[]	= $filename;
	    }		// loop through files
	    sort($scripts);
	}		// found Newsletters directory

	for ($i = 0; $i < count($scripts); $i++)
	{		// loop through scripts in order
	    $filename	= $scripts[$i];
	    $dispname	= preg_replace("/[A-Z]/", " $0", $filename);
	    $dispname	= ucfirst(substr($dispname, 0, strlen($dispname) - 4));
	    if (substr($dispname,0,4) == 'Test')
		$dispname	= 'Test ' . substr($dispname,4);
?>
      <p class='label'>
	<a href='<?php print $filename; ?>' target='_blank'>
		<?php print $dispname; ?>
	</a>
      </p>
<?php
	}		// loop through scripts in order
    }			// authorized
?>
</div> <!-- body -->
<?php
    pageBot();
?>
</body>
</html>
