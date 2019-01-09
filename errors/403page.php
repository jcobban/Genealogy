<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  403page.php								*
 *									*
 *  Handle an HTTP 403 error, Page not Found				*
 *									*
 *    History:								*
 *	2014/08/06	created						*
 *	2015/05/25	show request_uri if redirect_url unavailable	*
 *	2015/07/17	use absolute URL in header			*
 ************************************************************************/
    require_once __NAMESPACE__ . "/common.inc";

    htmlHeader("Genealogy: Page Not Found",
		array('/jscripts/util.js',
		      '/jscripts/default.js'),
		false);
    pageTop(array(
	'/genealogy.php'	=> 'Genealogy',
	'FamilyTree/nominalIndex.php' => 'Nominal Index of Individuals'));
?>
<div class='body'>
  <h1>Access Denied </h1>
<p>Access denied to the URL
<?php 
    if (array_key_exists('REDIRECT_URL', $_ENV))
	print '<b>"' . $_ENV['REDIRECT_URL'] . '"</b> ';
    else
	print '<b>"' . $_SERVER['REQUEST_URI'] . '"</b> ';
    foreach($_ENV as $name => $value)
	$warn	.= "<p>\$_ENV['$name']='$value'</p>\n";
    showTrace();
?>
</div> <!-- body -->
<?php
    pageBot("403page.php");
