<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genState.php							*
 *									*
 *  Display a web page containing links for a particular US State	*
 *  from the Legacy database.						*
 *									*
 *  History:								*
 *	2014/10/03	created						*
 *	2016/05/20	CountiesEdit.php moved to folder Canada		*
 *			use class Domain to validate domain code	*
 *									*
 *  Copyright 2016 &copy; James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Domain.inc";
    require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *									*
 *  Open code.								*
 *									*
 ************************************************************************/

// the hierarchy of URLs to display in the top and bottom of the page
$breadcrumbs	= array(
		'/genealogy.php'	=> 'Genealogy',
		'/genCountry.php?cc=US'	=> 'United States of America',
		'/Canada/DomainsEdit.php?cc=US'	=> 'List of States');


    // validate all parameters passed to the server and construct the
    // various portions of the SQL SELECT statement
    $code		= null;
    foreach ($_GET as $key => $value)
    {			// loop through all parameters
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'code':
	    {		// state postal abbreviation
		$code		= $value;
		$domain		= 'US' . $code;
		$domainObj	= new Domain(array('domain'	=> $domain,
						   'language'	=> 'en'));
		if ($domainObj->isExisting())
		    $statename		= $domainObj->get('name');
		else
		    $statename		= "Unknown State '$code'";
		break;
	    }		// state postal abbreviation
	}		// act on specific parameters
    }			// loop through all parameters

    if (is_null($code))
    {
	$msg		.= "Missing parameter Code. ";
	$title		= "Missing Parameter Code";
    }
    else
	$title		= $statename . ": Genealogy Resources"; 
    htmlHeader($title,
		array(  '/jscripts/js20/http.js',
			'/jscripts/util.js',
			'common.js'));
?>
<body>
<?php
pageTop($breadcrumbs);
?>	
 <div class='body'>
  <h1>
    <span class='right'>
	<a href='genStateHelp.html' target='_blank'>Help?</a>
    </span>
    <?php print $title . "\n"; ?>
    <div style='clear: both;'></div>
  </h1>
<?php
	// print error messages if any
	if (strlen($msg) > 0)
	{		// message to display
?>
    <p class='message'>
	<?php print $msg; ?> 
    </p>
<?php
	}		// message to display
	else
	{		// display results
	    if ($code == 'MI')
	    {
?>
  <p class='label'>
    <a target='_blank' href='http://envoy.libofmich.lib.mi.us/1870_census/'>
	1870 Census of Michigan - by the Library of Michigan</a> 
  </p>
<?php
            }
?>
  <p class='label'>
    <a target='_blank' 
	href='/Canada/CountiesEdit.php?Domain=US<?php print $code; ?>'>
		Counties
    </a>
  </p>
<?php
	}		// display results
?>
  </div> <!-- end of <div id='body'> -->
<?php
    pageBot();
?>
