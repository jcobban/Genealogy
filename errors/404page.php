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
 ************************************************************************/
require_once __NAMESPACE__ . "/common.inc";

if (array_key_exists('REDIRECT_URL', $_ENV))
{
    $url        = $_ENV['REDIRECT_URL'];
}
else
    $url        = $_SERVER['REQUEST_URI'];
$matches        = array();
if (preg_match('/Help(..)\.html$/', $url, $matches))
{
    if ($matches[1] != 'en')
    {
        $url        = substr($url, 0, strlen($url) - 7) . 'en.html';
        header('Location: '.$url);
        exit;
    }
}

htmlHeader("Genealogy: Page Not Found",
			array('/jscripts/util.js',
			      '/jscripts/default.js'),
			false);
pageTop(array(
		'/genealogy.php'	=> "Genealogy"));
?>
<div class='body'>
  <h1>Page Not Found
    <span class='right'>
      <a href='genCanadaHelpen.html' target='_blank'>? Help</a>
    </span>
  </h1>
<p>The page name portion of the URL <b>"<?php print $url; ?>"</b> 
is misspelled.
</div> <!-- body -->
<?php
    pageBot("404page.php");
?>
