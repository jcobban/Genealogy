<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  404page.php															*
 *																		*
 *  Handle an HTTP 404 error, Page not Found							*
 *																		*
 *    History:															*
 *		2014/08/06		created											*
 *		2015/05/25		show request_uri if redirect_url unavailable	*
 *		2015/07/17		use absolute URL in header						*
 *		2018/11/28      redirect non-English help pages                 *
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
if (preg_match('/(^.*Help)(\w*).html$/', $url, $matches))
{
    $page           = $matches[1];
    $lang           = strtolower($matches[2]);
    if (strlen($lang) > 2)
        $lang       = substr($lang, 0, 2);
    else
    if ($lang == '')
        $lang       = 'en';
    if ($lang != 'en')
    {
	    header("HTTP/1.0 301 Moved Permanently");
	    header("Location: {$page}en.html");
	    header("Connection: close");
	    exit();
    }
    $pagename       = $url;
}
else
if (preg_match('/^([^\?]+)\?.*lang=(\w+)/', $url, $matches))
{
    $pagename       = $matches[1];
    $lang           = strtolower(substr($matches[2],0,2));
}
else
if (preg_match('/^([^\?]+)(\w\w).html/', $url, $matches))
{
    $pagename       = $url;
    $lang           = strtolower(substr($matches[2],0,2));
}
else
    $pagename       = $url;

$template	        = new FtTemplate("404page$lang.html");

$template->set('PAGENAME',      $pagename);
$template->set('URL',           $url);
$template->set('LANG',          $lang);

$template->display();
