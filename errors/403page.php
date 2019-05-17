<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  403page.php								                            *
 *									                                    *
 *  Handle an HTTP 403 error, Access denied             				*
 *									                                    *
 *    History:								                            *
 *  	2014/08/06  	created						                    *
 *  	2015/05/25  	show request_uri if redirect_url unavailable	*
 *  	2015/07/17  	use absolute URL in header			            *
 *		2019/02/13      use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

if (array_key_exists('REDIRECT_URL', $_ENV))
    $url            = $_ENV['REDIRECT_URL'];
else
    $url            = $_SERVER['REQUEST_URI'];

$lang               = 'en';
if (preg_match('/lang=(\w+)/', $url, $matches))
    $lang           = strtolower(substr($matches[1],0,2));
else
if (preg_match('/^([^\?]+)(\w\w).html/', $url, $matches))
    $lang           = strtolower(substr($matches[2],0,2));

$template	        = new FtTemplate("403page$lang.html");

$template->set('URL',           $url);
$template->set('LANG',          $lang);

$template->display();
