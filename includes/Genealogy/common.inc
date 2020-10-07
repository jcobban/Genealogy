<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  common.inc                                                          *
 *                                                                      *
 *  Common functions used by all pages.                                 *
 *                                                                      *
 *  The following global variables that can be used by any script:      *
 *                                                                      *
 *      $debug          boolean true if debugging output is requested   *
 *                      If this is already set to true it remains true  *
 *                      otherwise it is set to false                    *
 *      $servername     the domain name of the web server               *
 *      $directory      directory portion of the path                   *
 *      $dsn            associative array containing the parameters     *
 *                      used to initialize the database connection      *
 *      $databaseName   the name of the SQL database                    *
 *      $connection     an MDB connection resource to database server   *
 *      $user           instance of User for the current user           *
 *      $userid         the user name of the current user               *
 *      $authorized     string list of authorized capabilities          *
 *      $browser        an instance of class Browser containing browser *
 *                      information                                     *
 *                      Some of the important subfields of this object  *
 *                      include:                                        *
 *                              $browser->browser       e.g. 'IE'       *
 *                              $browser->version                       *
 *                              $browser->majorver                      *
 *                              $browser->minorver                      *
 *                                                                      *
 *  The following lookup tables that can be used by any script:         *
 *      $censusCodes            list of supported census codes          *
 *      $censusLinesPerPage     lookup table of number of lines per     *
 *                              page indexed by census code.            *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/22      Created                                         *
 *      2010/10/04      Suppress warning when session key 'authorized'  *
 *                      is defined, but not session key 'userid'        *
 *      2010/10/20      Add initialization of $browser object           *
 *      2010/10/30      Add htmlHeader function                         *
 *                      get browser characteristics                     *
 *                      If signed on provide access to account mgmnt    *
 *      2010/11/10      use <a> tag for old IE                          *
 *      2010/11/16      replace get_browser() to speed up pages         *
 *      2010/12/25      separate PHP and HTML in rightTop()             *
 *      2011/02/06      make text of signon button customizable         *
 *      2011/03/10      add an id onto the rightTop button so it can be *
 *                      changed in response to a signon event           *
 *      2011/04/22      fix server name in htmlHeader                   *
 *      2011/09/26      replace deprecated language= attribute with     *
 *                      type= in generated <script> tags.               *
 *      2011/11/05      add isFirstVowel function                       *
 *      2011/11/22      ignore errors thrown in session management      *
 *      2012/01/07      only clear $authorized on logoff so can supply  *
 *                      default userid on logon                         *
 *      2012/01/24      set default $debug                              *
 *      2012/02/08      correct db setup if user accesses jamescobban.net*
 *      2012/03/31      update copyright date in htmlHeader             *
 *      2012/05/12      extend session timeout                          *
 *                      always get session userid if available          *
 *                      correct handling of session setup failure       *
 *                      change getBrowser function to Browser::get      *
 *      2012/06/09      add support for genealogy.jamescobban.net       *
 *      2012/06/21      change initial text in signon button to         *
 *                      'contribute'                                    *
 *      2012/06/22      migrate ereg to preg_match                      *
 *      2012/08/21      add meta keywords to htmlHeader                 *
 *                      improve documentation                           *
 *                      only set $debug to false if it does not exist   *
 *      2012/09/13      move defaultImage function from CensusForm.inc  *
 *                      to common.inc, so it can be used by PageForm    *
 *                      move getNames method from formUtil.inc and      *
 *                      changed to not use or modify global variables   *
 *                      except $debug and $connection, and to include   *
 *                      the previously separate functionality of        *
 *                      getnpPrev and getnpNext                         *
 *                      add common table of supported census identifiers*
 *      2012/10/14      issue new <meta charset> tag for HTML5          *
 *      2012/10/22      move declaration of $debug before first use to  *
 *                      avoid an error message if display_errors is set *
 *                      always initialize all members of return value   *
 *                      of function getNames                            *
 *      2012/10/27      change determination of default frame for 1911  *
 *                      census so it prefers $relFrame over $imageBase  *
 *      2012/12/19      do not terminate script for errors              *
 *      2013/01/26      table SubDistTable renamed to SubDistricts      *
 *      2013/04/05      add hyperlinks on browser caveat notice         *
 *                      add functions to layout header and footer       *
 *                      update copyright date in HTML header to 2013    *
 *      2013/04/16      add facebook like                               *
 *      2013/05/17      revert to using table to layout page footer     *
 *                      because     proper CSS3 doesn't work unless use *
 *                      a feature not supported by IE7                  *
 *                      use rawurlencode for mailto subject             *
 *      2013/06/01      get administrator's e-mail address from Users   *
 *                      database and include owners' email addresses    *
 *                      in the contact author mailto URL                *
 *      2013/07/07      function defaultImage replaced by               *
 *                      SubDistrict::getDefaultImage                    *
 *                      function getNames replaced by class             *
 *                      SubDistrict methods                             *
 *      2013/08/06      add option 'back' to pageTop list of breadcrumbs*
 *      2013/08/17      add support for 1921 census                     *
 *      2013/11/15      do not send connection error text in response   *
 *                      as it prevents session_start from working.      *
 *                      Instead the error text is stored in the global  *
 *                      variable $connMsg where it can be accessed      *
 *                      by scripts                                      *
 *                      set explicit time zone to Toronto               *
 *      2013/11/25      add lookup table $domains                       *
 *                      set default initial value of $debug based       *
 *                      upon value of $_GET['debug']                    *
 *                      reorder some of the initialization steps        *
 *                      highlight comment blocks                        *
 *      2013/12/19      use CSS to layout page footer                   *
 *      2013/12/27      improve CSS layout of page header and footer    *
 *      2013/12/30      suppress IE only attributes of <iframe> if      *
 *                      the browser supports HTML5 and CSS3             *
 *      2014/01/06      fix error if invoked by method='POST'           *
 *      2014/01/13      add entries to $domains table                   *
 *                      create $countries table                         *
 *      2014/01/15      add function pctClass                           *
 *                      add function last($string, $len)                *
 *      2014/02/17      loose surname pattern match moved to common.inc *
 *      2014/02/27      mySQL SOUNDEX function fails on '?'             *
 *      2014/03/30      do not include e-mail addresses in page footer  *
 *      2014/04/15      add more surnames                               *
 *      2014/06/28      extend signon to 30 days                        *
 *      2014/07/09      accept any case on debug parameter              *
 *      2014/07/13      revert session duration to 30 minutes           *
 *      2014/07/15      move support for popupAlert to pageBot          *
 *                      so it will be available on all pages            *
 *      2014/07/26      add advertising frame to page top               *
 *      2014/08/22      perform autologon if requested by user          *
 *      2014/09/03      add all US states to supported domains          *
 *                      add a few more country codes                    *
 *      2014/09/07      add table of province names moved from          *
 *                      database/DistForm.php                           *
 *      2014/09/23      add border parameter to function pctClass       *
 *                      add exclamation mark to button with userid      *
 *                      to signal presence of messages                  *
 *      2014/09/26      make debugging trace XML compliant              *
 *      2014/09/28      $blogCount not initialized                      *
 *      2014/11/10      add patterns for names like Dixon, and Cornell  *
 *      2014/11/15      $newuserid not defined if no cookies            *
 *      2014/11/21      suppress debugging output for XML scripts       *
 *      2014/11/29      put debugging output into $warn so it will      *
 *                      appear inside the <body> section                *
 *      2015/01/01      update copyright notice in htmlHead             *
 *      2015/01/12      addition of index.html to Advertisements        *
 *                      folder resulted in bad header                   *
 *      2015/02/23      add some surnames starting with H               *
 *      2015/05/23      consolidate census information in $censusInfo   *
 *      2015/05/25      do autosignon for method=Post                   *
 *                      extract directory portion of path               *
 *                      ensure links to javascript files are always     *
 *                      absolute in htmlHeader                          *
 *                      include query string in contactAuthor parms     *
 *                      from pageBot function                           *
 *      2015/07/02      access PHP includes using include_path          *
 *                      access files and directories in web site        *
 *                      using $_SERVER['DOCUMENT_ROOT']                 *
 *                      allow breadcrumb to have no URL                 *
 *      2015/07/20      add <header>, <main>, and <footer> tags         *
 *                      for HTML5 navigation                            *
 *      2015/08/11      new global $familyTreeCookie                    *
 *      2015/09/28      migrate to PDO                                  *
 *      2015/12/10      get userid and password of current user from    *
 *                      cookie, validate them, and get authorizations   *
 *                      from the instance of User rather than keeping   *
 *                      them in session variables.                      *
 *                      database configuration file renamed             *
 *      2016/01/01      error message printed before page header        *
 *      2016/01/06      delete cookie 'dboptions' if it exists          *
 *      2016/01/19      add method showTrace                            *
 *      2016/01/21      repair signoff                                  *
 *                      Census support all moved to class Census        *
 *      2016/01/27      turn off popup help support only on page loaded *
 *      2016/02/05      function dialogBot did not have user info       *
 *      2016/05/20      global table $domains replaced by database      *
 *                      table and class Domain                          *
 *      2016/11/12      invalid call to method getMessage               *
 *                      $_SERVER['HTTP_USER_AGENT'] not set if not      *
 *                      invoked from browser                            *
 *                      avoid undefined $stylesheet                     *
 *      2017/02/04      move javascript includes to footer              *
 *      2017/02/08      remove obsolete global function getSurnameChk   *
 *                      remove array $countries replaced by class       *
 *                      Country                                         *
 *      2017/07/07      undefined $e                                    *
 *      2017/07/18      undefined $msg in htmlHeader                    *
 *      2017/09/05      $surpatterns and $mcnames replaced by           *
 *                      the pattern field in the Surname object         *
 *      2018/01/14      support ability of master to manually set       *
 *                      userid and authorized attributes for debugging  *
 *      2018/01/27      functions htmlHeader, pageTop, dialogBot,       *
 *                      and pageBot utilize class Template to use       *
 *                      standard page layout templates                  *
 *      2018/02/23      do not handle $warn or $msg in htmlHeader       *
 *                      because there is no includeSub template         *
 *      2018/10/27      apologize for lack of support of non-English    *
 *                      for pages still using function htmlHeader       *
 *      2018/11/12      only activate debug output for administrators   *
 *      2019/01/06      move to namespace Genealogy                     *
 *      2019/02/08      determination of advertisement to display       *
 *                      moved to class FtTemplate                       *
 *      2019/03/18      for scripts that do not yet support templates   *
 *                      move the help link to the menu button           *
 *                      move the contact link to the menu button        *
 *                      add the "About Site" link to the menu button    *
 *                      add the advertisement to the header             *
 *      2019/05/18      correct parsing of familyTree cookie            *
 *      2019/08/01      htmlHeader did not set $LANG                    *
 *      2020/02/14      initialize $dbversion                           *
 *      2020/06/09      set secure and samesite=lax on all cookies      *
 *      2020/06/10      ensure $browser->majorver always set            *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
// identify the SQL server to connect to based upon the URL
// initializes the global variable $dsn
require_once __NAMESPACE__ . '/configDb.inc';

// ensure that all required classes are loaded when referenced
//function __autoload($class_name) {
//    include __NAMESPACE__ . "/" . $class_name . '.inc';
//}

// required by PHP 5.6
date_default_timezone_set('America/Toronto');

/************************************************************************
 *  $msg                                                                *
 *                                                                      *
 *  Initialize common variable for accumulating error messages.         *
 ************************************************************************/
if (!isset($msg) || !is_string($msg))
    $msg        = '';

/************************************************************************
 *  $warn                                                               *
 *                                                                      *
 *  Initialize common variable for accumulating warning messages.       *
 ************************************************************************/
if (!isset($warn) || !is_string($warn))
    $warn       = '';

/************************************************************************
 *                                                                      *
 *      Connect to the database server                                  *
 *                                                                      *
 ************************************************************************/
$dburl              = $dsn['phptype'] .
                          ':dbname=' . $dsn['database'] .
                          ';host=' . $dsn['hostspec'] .
                          ';charset=utf8';

$dbversion          = '';
try {
    $connection     = new PDO($dburl,
                              $dsn['username'],
                              $dsn['password']);

    $dbversion      = $connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    $connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    // by default MySQL and MariaDB use quote (") for string literals
    // but the ANSI standard reserves quote to escape column names
    $connection->query("SET GLOBAL sql_mode='ANSI_QUOTES'");
} catch(Exception $e)
{       // error establishing connection
    $msg    .= "Unable to connect to database using: " .
              "dburl='$dburl', " .
              $e->getMessage() . ". ";
    $connection = null;
}       // error establishing connection


/************************************************************************
 *  The following bit of open code is executed on every page            *
 *                                                                      *
 *  Global Variables:                                                   *
 *      $document_root          the root directory of the site          *
 ************************************************************************/
// choose the advertising page to display
$ads            = array();
$document_root  = $_SERVER['DOCUMENT_ROOT'];

/************************************************************************
 *  The following bit of open code is executed on every page that       *
 *  has functionality depending upon whether or not the current user    *
 *  has signed on to the web site.                                      *
 *                                                                      *
 *  Global Variables:                                                   *
 *      $user                   instance of User for the current user   *
 *      $familyTreeCookie       info on last family tree activities     *
 *                                                                      *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';

$user               		= null;     // instance of User
$userid             		= null;
$password           		= null;
$newuserid          		= '';
$authorized         		= '';
$familyTreeCookie   		= array();
$signonText         		= 'contribute';
try {
    // check for existing session
    session_start();
    if (strlen($msg) == 0 && isset($_SESSION) && count($_SESSION) > 0)
    {                       // handle session variables
        error_log("\$_SESSION=" . var_export($_SESSION,true) . "\n");
        foreach($_SESSION as $key => $value)
        {                   // loop through all session variables
            if ($key == 'userid')
            {
                $userid             = trim($value);
                if (strlen($userid) > 0)
                {           // user name passed
                    $user           = new User(array("username" => $userid));
                    if ($user->isExisting())
                    {
                        $authorized = $user->get('auth');
                        $options    = $user->get('options');
                    }
                    else
                    {
                        $warn       .= "<p>new User failed for userid='$userid': </p>\n";
                        $msg        .= "Logic error: Invalid username passed by session. ";
                        $authorized = '';
                        $options    = null;
                    }
                }           // user name passed
            }
        }                   // loop through all session variables
    }                       // handle session variables

    // check cookies
    if (isset($_COOKIE) && count($_COOKIE) > 0)
    {                   // handle cookies
	    error_log("\$_COOKIE=" . var_export($_COOKIE, true) . "\n");
	    foreach($_COOKIE as $key => $value)
	    {               // loop through all cookie variables
	        switch(strtolower($key))
	        {           // act on specific cookies
	            case 'persistence':
	            {
	                if (!isset($userid))
	                {
	                    $user       = new User(array('persistence' => $value));
	                    if ($user->isExisting())
	                        $userid = $user['username'];
	                    else
	                        $user   = null;
	                }
	                setcookie($key, $value,
	                          [ 'expires'   => time() + 60*60*24*7, 
	                            'path'      => '/',
	                            'secure'    => true,
	                            'samesite'  => 'lax']);
	                break;
	            }
	
	            case 'rememberme':
	            {
	                unset($_COOKIE[$key]);
	                setcookie($key, '', 
	                          [ 'expires'   => time() - 3600, 
	                            'path'      => '/',
	                            'secure'    => true,
	                            'samesite'  => 'lax']);
	                break;
	            }
	
	            case 'familytree':
	            {       // family tree information
	                $parts      = explode('&', $value);
	                foreach($parts as $part)
	                {       // process each part
	                    $o          = strpos($part, ':');
	                    $pname      = rawurldecode(substr($part, 0, $o));
	                    $pvalue     = rawurldecode(substr($part, $o+1));
	                    $familyTreeCookie[$pname]   = $pvalue;
	                }       // process each part
	                break;
	            }       // family tree information
	
	            case 'dboptions':
	            {       // obsolete dboptions
	                setcookie('dboptions','', 
	                          [ 'expires'   => time() - 3600, 
	                            'path'      => '/',
	                            'secure'    => true,
	                            'samesite'  => 'lax']);
	                $value      = '';
	                break;
	            }       // obsolete dboptions
	        }           // act on specific cookies
	
	    }               // loop through all cookie variables
    }                   // handle cookies

    // record that the user is logged on
    if (!is_null($userid))
        $_SESSION['userid'] = $userid;

    if (strlen($msg) == 0)
    {
        $url                = $_SERVER["REQUEST_URI"];
        $pieces             = explode('?', $url);
        $path               = $pieces[0];
    }                   // no errors
} catch(Exception $e)
{                       // report errors thrown in the session code
    $msg    .= $e->getMessage();
}                       // report errors thrown in the session code

// to handle a problem with Apache mod_rewrite get the directory
// portion of the URL
$scriptName                 = $_SERVER['PHP_SELF'];
$filePatn                   = "#[^/]+\.\w+$#";
$res                        = array();
$count                      = preg_match($filePatn, $scriptName, $res);
if ($count == 1)
{                       // script name matched pattern
    $scriptNameLen          = strlen($scriptName);
    $fileNameLen            = strlen($res[0]);
    $directory              = substr($scriptName,
                                     0,
                                     $scriptNameLen - $fileNameLen);
}                       // script name matched pattern
else
{
    $directory              = '';
}

/************************************************************************
 *  $debug                                                              *
 *                                                                      *
 *  if $debug has been defined and set prior to invoking this           *
 *  include then the value will remain unchanged.  otherwise the        *
 *  initial value is set based upon the debug parameter,                *
 *  as passed in $_GET or $_POST or set in a cookie.                    *
 *  This avoids an error being reported if the display_errors           *
 *  option is set in the PHP server.                                    *
 *                                                                      *
 ************************************************************************/
if (!isset($debug) || !is_bool($debug))
{           // $debug not defined yet
    $debug      = false;
}           // $debug not defined yet

/************************************************************************
 *  Administrator only functions                                        *
 *                                                                      *
 *  An administrator can turn on debugging output                       *
 *  An administrator can test the behavior of a page as viewed by       *
 *  another account by passing parameters with the page request         *
 ************************************************************************/
if (canUser('debug'))
{                   // administrator only
    foreach($_REQUEST as $key => $value)
    {               // loop through all request variables
        $keyLc      = strtolower($key);
        if (is_string($value))
        {           // value is a string
            $valueLc    = strtolower($value);

            switch($keyLc)
            {       // switch on key
                case 'debug':
                {
                    if ($valueLc == 'y' || $valueLc == 'yes')
                    {
                        $debug  = true;
                    }
                    break;
                }

                case 'authorized':
                {
                    $authorized = $valueLc;         // simulate
                    break;
                }

                case 'userid':
                {
                    $userid     = $valueLc;         // simulate
                    break;
                }

                case 'useroptions':
                {
                    $options    = intval($valueLc); // simulate
                    break;
                }
            }       // switch on key
        }           // string value
    }               // loop through all request variables
}                   // administrator only

/************************************************************************
 *  class Browser                                                       *
 *                                                                      *
 *  The following class determines the characteristics of               *
 *  the client's browser.                                               *
 *                                                                      *
 *  Some of the important subfields of this object include:             *
 *      [browser]                                                       *
 *      [version]                                                       *
 *      [majorver]                                                      *
 *      [minorver]                                                      *
 *                                                                      *
 ************************************************************************/
class Browser
{
    public  $browser;
    public  $version;
    public  $majorver;
    public  $minorver;

    function __construct()
    {           // constructor
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $useragent      		= $_SERVER['HTTP_USER_AGENT'];
            $chromePatn     		= "#Chrome/([0-9.]+)#";
            $firefoxPatn    		= "#Firefox/([0-9.]+)#";
            $konquerorPatn  		= "#Konqueror/([0-9.]+)#";
            $msiePatn       		= "#compatible; MSIE ([0-9.]+)#";
            $res        		    = array();

            if (preg_match($chromePatn, $useragent, $res))
            {       // Chrome browser, including M$ Edge
                $this->browser  	= 'chrome';
                $this->version  	= $res[1];
            }       // Chrome browser
            else
            if (preg_match($firefoxPatn, $useragent, $res))
            {       // Firefox browser

                $this->version  	= $res[1];
            }       // Firefox browser
            else
            if (preg_match($konquerorPatn, $useragent, $res))
            {       // Konqueror browser
                $this->browser  	= 'konqueror';
                $this->version  	= $res[1];
            }       // Konqueror browser
            else
            if (preg_match($msiePatn, $useragent, $res))
            {       // MSIE browser
                $this->browser  	= 'IE';
                $this->version  	= $res[1];
            }       // MSIE browser
            else
            {       // other
                $this->browser  	= 'other';
                $this->version  	= '1.0';
            }       // other

            if (preg_match("#^([0-9]*)[^0-9]?([0-9]*)#", $this->version, $res))
            {
                $this->majorver 	= $res[1];
                $this->minorver 	= $res[2];
            }
            else
            {
                $this->majorver 	= $this->version;
                $this->minorver 	= '';
            }

        }
        else
        {       // not invoked from browser
            $this->browser  		= 'netscape';
            $this->version  		= 0;
            $this->majorver 		= 0;
            $this->minorver 		= '';
        }       // not invoked from browser
    }           // constructor
}   // class Browser

/************************************************************************
 *  Global Variables:                                                   *
 *      $browser        an object containing browser information        *
 *      $nohtml5        true if the browser does not support HTML5      *
 *      $iecrap         text that is added to some HTML tags for        *
 *                      browsers that do not support HTML5.             *
 ************************************************************************/
$browser    = new Browser();
$nohtml5    = $browser->browser == 'IE' && $browser->majorver < 9;
if ($nohtml5)
    $iecrap = "scrolling='no' frameborder='0' allowTransparency='true'";
else
    $iecrap = "";

// display a warning to users who are still trying to run Windows XP
// with IE6 or IE7
if ($browser->browser == 'IE' && $browser->majorver < 8)
{       // old browser
    ob_start()
?>
<p>This application does not work well with older versions of
    Internet Explorer.  Please upgrade to at least IE8, and
    preferably <a href='http://windows.microsoft.com/en-CA/internet-explorer/downloads/ie-9/worldwide-languages'>IE9</a> or higher,
    or else install a free 3rd party browser such as
    <a href='http://www.mozilla.org/en-US/firefox/new/'>Firefox</a>
    <a href='http://www.google.com/chrome'>Chrome</a>, or
    <a href='http://www.opera.com/download/'>Opera</a>.
</p>
<?php
    $warn   .= ob_get_clean();
    $msg    .= "Warning: Your computer is insecure and your operating system is no longer supported for security fixes.  Upgrade to a later version of Windows or switch to a more secure operating system?.";
}       // old browser

/************************************************************************
 *  canUser                                                             *
 *                                                                      *
 *  This global function returns true if the current user can perform   *
 *  the indicated function.                                             *
 *                                                                      *
 *  Parameters:                                                         *
 *      function    string describing function to be performed          *
 *                      'edit'      alter databases                     *
 *                      'blog'      post blogs                          *
 *                      'admin'     administrator                       *
 *                      'debug'     authorized debugger                 *
 *                      'pending'   user waiting for confirmation       *
 *                      'all'       master administrator                *
 *                                                                      *
 ************************************************************************/
function canUser($function)
{
    global  $authorized;        // $user->get('auth') comma-separated tokens
    return (($authorized == 'yes') || ($authorized == 'all') ||
            (preg_match("/\b$function\b/i", $authorized) == 1));
}       // canUser

/************************************************************************
 *  function htmlHeader                                                 *
 *                                                                      *
 *  Emit common HTML header for web pages.                              *
 *                                                                      *
 *  Parameters:                                                         *
 *      $title          page title as a string                          *
 *      $jsIncludes     list of Javascript files to include as array    *
 *                      defaults to none                                *
 *      $ownStyleSheet  true if the current script has its own style    *
 *                      sheet                                           *
 *      $base           'page' or 'dialog'                              *
 *                                                                      *
 *  Deprecated: use Template                                            *
 ************************************************************************/
$scriptIncludes     = array();

function htmlHeader($title,
                    $jsIncludes     = array(),
                    $ownStyleSheet  = false,
                    $base           = 'page')
{
    global  $browser;
    global  $directory;
    global  $scriptIncludes;
    global  $template;
    global  $lang;
    global  $debug;
    global  $warn;
    global  $msg;
    global  $userid;
    global  $authorized;
    global  $options;

    if (!class_exists('Template\\Template'))
        include_once 'Templating/Template.inc';
    if (!class_exists(__NAMESPACE__ . '\\Language'))
        include_once __NAMESPACE__ . '/Language.inc';

    $tempBase       = $_SERVER['DOCUMENT_ROOT'] . '/templates/';
    $nohtml5        = $browser->browser == 'IE' && $browser->majorver < 9;

    if ($nohtml5)
    {       // browser does not support HTML 5, use HTML 4
        $template   = new Template("$tempBase{$base}IE7.html");
        $template->set('IECRAP',
            "scrolling='no' frameborder='0' allowTransparency='true'");
    }       // HTML 4
    else
    {       // HTML 5
        $template   = new Template("$tempBase{$base}en.html");
        $template->set('IECRAP', '');
    }       // HTML 5

    if (strlen($title) > 0)
        $template->set('TITLE', $title);

    // check for language support
    $lang               = 'en';
    if (array_key_exists("lang", $_REQUEST))
    {
        $lang           = strtolower($_REQUEST['lang']);
        if ($lang != 'en')
        {
            $language       = new Language(array('code' => $lang));
            $langName       = $language->get('name');
            $nativeName     = $language->get('nativename');
            $sorry          = $language->getSorry();
            $warn           .= str_replace(array('$langName','$nativeName'),
                                           array($langName, $nativeName),
                                           $sorry);
        }
    }
    $template->set('LANG',      $lang);

    // support passing debug flag to subsequent pages
    if ($debug)
        $template->set('DEBUG', 'Y');
    else
        $template->set('DEBUG', 'N');

    $template->set('BREADCRUMBS',   '');
    $template->set('ADURL',         '');
    $template->set('USERID',        $userid);
    $template->set('CONTRIBUTE',    '');
    $template->set('AUTHORIZED',    $authorized);
    $template->set('OPTIONS',       $options);
    $template->set('BLOGFLAG',      '');

    // postpone javascript includes to end of document
    $scriptIncludes = $jsIncludes;

    if ($ownStyleSheet)
    {           // script has its own style sheet
        $scriptName     = $_SERVER['PHP_SELF'];
        if (substr($scriptName,-4) == '.php')
        {       // script name matched pattern
            $stylesheet = substr($scriptName,
                                 0,
                                 strlen($scriptName) - 4);
            $template->updateTag('otherStylesheets',
                             array('filename'   => $stylesheet));
        }       // script name matched pattern
        else
            $template->updateTag('otherStylesheets',     null);
    }           // script has its own style sheet
    else
        $template->updateTag('otherStylesheets',         null);

    ob_start();     // start accumulating main section of body
}       // function htmlHeader

/************************************************************************
 *  function pageTop                                                    *
 *                                                                      *
 *  This function generates the HTML text to display the page header    *
 *  of all pages so this is customizable in one place.                  *
 *                                                                      *
 *  Input:                                                              *
 *      $breadCrumbs        associative array of URLs => labels         *
 *                                                                      *
 *  Deprecated: use Template                                            *
 ************************************************************************/
function pageTop($breadCrumbs)
{
    global  $template;
    global  $user;
    global  $userid;
    global  $debug;
    global  $warn;
    global  $msg;
    global  $authorized;
    global  $options;
    global  $signonText;
    global  $browser;
    global  $adurl;
    global  $bctext;

    $template->set('ADURL',     $adurl);

    // run-time can interrogate these values to take action
    // for example the OPTIONS value is used to determine whether or
    // not to use popup help
    $template->set('USERID',        $userid);
    $template->set('AUTHORIZED',    $authorized);
    $template->set('OPTIONS',       $options);

    // manage the appearance and content of the button that
    // identifies the current user
    $template->set('BLOGFLAG',      '');
    if (strlen($userid) == 0)
        $template->set('CONTRIBUTE',    'contribute');
    else
    {
        $template->set('CONTRIBUTE',    '');
        $user       = new User(array("username" => $userid));
        $blogParms  = array('keyvalue'  => $user->get('id'),
                            'table'     => '^Users$');
        $bloglist   = new RecordSet('Blogs', $blogParms);
        $blogCount  = $bloglist->getInformation()['count'];
        if ($blogCount > 0)
            $template->set('BLOGFLAG',
       "<span class='message' style='font-size: larger;'>!</span>&nbsp;");

    }
    $template->set('KEYWORDS',      '');    // additional meta keywords
    $template->set('CONTACTKEY',        '');    // identify the specific record
    $template->set('CONTACTTABLE',      '');    // identify the table
    $template->set('CONTACTSUBJECT',    $_SERVER['REQUEST_URI']);

    // initialize BREADCRUMBS from parameter

    $bctext         = '';
    foreach($breadCrumbs as $url => $label)
    {
        if (strlen($url) == 0)
        {
            $bctext .= $label . ": ";
        }
        else
        if ($url == 'back')
        {       // backup through history
            $bctext .= "<a onclick='history.back(); return false;' href='dummy' class='menuitem'>$label</a>:";
        }       // backup through history
        else
        {       // actual URL
            $bctext .= "<a href='$url' class='menuitem'>$label</a>: ";
        }       // actual URL
    }       // loop through breadcrumbs

    $bctext                 .= '<hr class="menubar">';
    $menuButton             = $template->getElementById('menuButton');
    if ($menuButton)
    {           // new heading style
        $bctext            = str_replace(':', '<br>', $bctext);
        global  $userid;
        if (isset($userid) && strlen($userid) > 0)
        {
            $bctext    .= "<a href=\"/Account.php?lang=en\" title=\"Your account status\" id=\"session\" class='menuitem'>$userid</a><br>\n";
        }
        else
        {
            $elt            = $template['UserInfoSignon'];
            if ($elt)
                $signon     = $elt->innerHTML();
            else
                $signon     = 'Sign On';
            $bctext    .= "<a href=\"/Signon.php?lang=en\" id=\"session\" class=\"menuitem\">$signon</a><br>\n";
        }
        $bctext             .= '<hr class="menubar">';
    }                   // new heading style
}       // function pageTop

/************************************************************************
 *  function pageBot                                                    *
 *                                                                      *
 *  This function generates the HTML text to display the page footer    *
 *  of all pages so this is customizable in one place.                  *
 *                                                                      *
 *  Input:                                                              *
 *      $pageName       text to be sent as the title of e-mails to      *
 *                      administrator and authors of this page          *
 *      $recordKey      if not zero also send e-mail to all registered  *
 *                      owners of the record                            *
 *      $tableName      name of the table containing the identified     *
 *                      record: default 'tblIR'                         *
 *                                                                      *
 *  Deprecated: use Template                                            *
 ************************************************************************/
function pageBot($pageName  = null,
                 $recordKey = 0,
                 $tableName = 'tblIR')
{
    global  $template;
    global  $browser;
    global  $directory;
    global  $scriptIncludes;
    global  $bctext;
    global  $lang;
    global  $document_root;
    global  $debug;
    global  $warn;

    // include javascript files
    for ($i = 0; $i < count($scriptIncludes); $i++)
    {           // loop through javascript includes
        $url        = $scriptIncludes[$i];
        if (substr($url,0,1) == '/' ||
            substr($url,0,5) == 'http:' ||
            substr($url,0,6) == 'https:')
        {       // absolute URL
    ?>
    <script src='<?php print $url; ?>' type='text/javaScript'>
    </script>
    <?php
        }       // absolute URL
        else
        {       // relative URL
    ?>
    <script src='<?php print $directory . $url; ?>' type='text/javaScript'>
    </script>
    <?php
        }       // relative URL
    }           // loop through javascript includes

    // use the accumulated output from the script for the page body
    $main           = ob_get_clean();
    $main           = str_replace('<body>','',$main);
    $main           = str_replace('</body>','',$main);
    $main           = str_replace('</html>','',$main);

    $mtemplate      = new Template($main);

    // add contents of help section of <h1> tag
    $h1array                = $mtemplate->getElementsByTagName('h1');
    if (count($h1array) > 0)
    {               // at least one <h1> tag in page
        $h1                 = current($h1array);
        foreach($h1->childNodes() as $child)
        {           // loop through children of first <h1>
            if ($child->tagName == 'span' &&
                isset($child->attributes['class']) &&
                $child->attributes['class'] == 'right')
            {       // move help section to menu
                $help       = $child->innerHTML();
                $apos       = strpos($help, '<a');
                $apos       = strpos($help, '>', $apos + 1);
                $help       = substr($help, 0, $apos) .
                                      ' id="menuhelp" class="menuitem"' .
                                      substr($help, $apos);
                $help       = str_replace('</a>',
                                              '</a><br>',
                                              $help);
                $bctext     .= $help;
                $child->update(null);
            }       // move help section to menu
        }           // loop through children of first <h1>
    }               // at least one <h1> tag in page

    // add about link
    $elt            = $mtemplate['UserInfoAbout'];
    if ($elt)
        $about      = $elt->innerHTML();
    else
        $about      = 'About This Site';
    $bctext         .= "<a href=\"/AboutHelp$lang.html\" id=\"menuabout\" class=\"menuitem\" target=\"help\">$about</a><br>\n";

    // add contact author link
    if (is_null($pageName) || strlen($pageName) == '')
    {       // use current page name from URL
        $scriptName = $_SERVER["SCRIPT_NAME"];
        $pageName   = substr($scriptName,strrpos($scriptName,'/') + 1);
    }       // use current page name from URL
    $template->set('CONTACTSUBJECT',
            rawurlencode("[Family Tree] $pageName?" . $_SERVER['QUERY_STRING']));
    $template->set('CONTACTKEY', $recordKey);
    $template->set('CONTACTTABLE', $tableName);

    $footSection    = $template['footSection'];
    foreach($footSection->childNodes() as $child)
    {           // loop through children of footer
        if ($child->tagName == 'div' &&
            isset($child->attributes['class']) &&
            $child->attributes['class'] == 'botcrumbs')
        {       // move help section to menu
            $help       = $child->innerHTML();
            $help       = str_replace('$CONTACTSUBJECT',
                                  rawurlencode("[Family Tree] $pageName?" . $_SERVER['QUERY_STRING']),
                                  $help);
            $help       = str_replace('$CONTACTKEY', $recordKey, $help);
            $help       = str_replace('$CONTACTTABLE', $tableName, $help);
            $apos       = strpos($help, '<a');
            $apos       = strpos($help, '>', $apos + 1);
            $help       = substr($help, 0, $apos) .
                                      ' id="menucontact" class="menuitem"' .
                                      substr($help, $apos);
            $bctext     .= $help;
            $child->update(null);
        }       // move help section to menu
    }           // loop through children of first <h1>

    $template->set('MAIN', $mtemplate->compile());
    $template->set('BREADCRUMBS', $bctext);

    // pick an advertisement to display
    $dh             = opendir("$document_root/Advertisements");
    if ($dh)
    {           // found advertisements directory
        while (($filename = readdir($dh)) !== false)
        {       // loop through files
            if (strlen($filename) > 5 &&
                $filename != "index.html" &&
                substr($filename, strlen($filename) - 5) == '.html')
            $ads[]      = $filename;
        }       // loop through files
        include_once  __NAMESPACE__ . '/Advertiser.inc';
        $counterRec     = new Advertiser(array('adname' == ''));
        $counterRec->increment('01');
        $index          = $counterRec['count01'] % count($ads);
        $adname         = $ads[$index];
        $prime          = substr($adname, 0, strlen($adname) - 5);
        $advertiser     = new Advertiser(array('adname' => $prime));
        $date           = date('m d');
        $month          = substr($date, 0, 2);
        $day            = substr($date, -2);
        $advertiser->increment($month);
        if ($day == '28')
        {               // this should be moved to a cron job
            $month      += 1;
            if ($month > 12)
                $month  = $month - 13;
            if (strlen($month) == 1)
                $month  = '0' . $month;
            $advertiser->set("Count$month", 0);
            $advertiser->save(false);
        }               // this should be moved to a cron job
    }           // found advertisements directory
    else
        $adname         = 'AddForRent.html';
    $adurl              = "/Advertisements/$adname";

    // change insertion of ads to not use <iframe>
    $adElement              = $template['advertFrame'];
    if ($adElement)
    {
        $adTemplate         = new Template($document_root . $adurl);
        $adDoc              = $adTemplate->getDocument();
        $adBody             = $adDoc->getElementsByTagName('body');
        if (count($adBody) > 0)
        {
            $adBody         = current($adBody);
            // the following replaces the <iframe> with the contents of
            // the <body> of the referenced URL
            $adElement->update($adBody->innerHTML());
        }
        else
            $warn           .= $adTemplate->getDocument()->show();
    }
    else
        $template->set('ADURL',         $adurl);

    $template->display();
}       // pageBot

/************************************************************************
 *  function dialogBot                                                  *
 *                                                                      *
 *  This function includes common elements that are required in all     *
 *  pages that do not use pageTop and pageBot.                          *
 *                                                                      *
 *  Deprecated: use Template                                            *
 ************************************************************************/
function dialogBot()
{
    global  $template;
    $hdrarray       = $template->getElementsByTagName('header');
    if (count($hdrarray) > 0)
    {
        $header     = reset($hdrarray);
        $header->update(null);  // delete the <header> section
    }
    pageBot();
}       // dialogBot

/************************************************************************
 *  function isFirstVowel                                               *
 *                                                                      *
 *  If the first character in a string is a vowel return true.          *
 *                                                                      *
 *  Parameter:                                                          *
 *      $text   a string conceptually containing language text          *
 *                                                                      *
 *  Returns:                                                            *
 *      true if the first character in the string is a vowel (AEIOU)    *
 *      otherwise false.                                                *
 ************************************************************************/
$letters    = array(
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,     // 0x0
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,     // 0x1
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,     // 0x2
    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,     // 0x3
    0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1,     // 0x4
    0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,     // 0x5
    0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1,     // 0x6
    0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);    // 0x7

function isFirstVowel($text)
{
    global $letters;

    $code   = ord($text);
    if ($code < 0 || $code > 127)
        return false;
    else
        return $letters[$code];
}       // isFirstVowel

/************************************************************************
 *  function translateWords                                             *
 *                                                                      *
 *  Translate each word of the supplied string using a global translate *
 *  table for internationalization.                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      $input          string to be translated                         *
 *                                                                      *
 *  Returns:                                                            *
 *      String with each word translated if there is a translation in   *
 *      the translate table for the current context.                    *
 ************************************************************************/
function translateWords($input)
{
    global $tranTab;

    $words  = explode(' ', $input);
    for($i = 0; $i < count($words); $i++)
    {
        if (array_key_exists($words[$i], $tranTab))
                $words[$i]  = $tranTab[$words[$i]];
    }       // loop through all words
    return implode(' ', $words);
}       // translateWords

/************************************************************************
 *  function pctClass                                                   *
 *                                                                      *
 *  Return the appropriate class for displaying a particular percentage *
 *                                                                      *
 *  Input:                                                              *
 *      $pct            a number between 0 and 100                      *
 *      $border         true        return style with border            *
 *                      false       return style without border         *
 ************************************************************************/
function pctClass($pct, $border=true)
{
    if ($border)
    {           // style with border
        if ($pct <= 0)
            $pctclass   = "p00right border";
        else
        if ($pct < 5)
            $pctclass   = "p01right border";
        else
        if ($pct < 15)
            $pctclass   = "p05right border";
        else
        if ($pct < 25)
            $pctclass   = "p15right border";
        else
        if ($pct < 50)
            $pctclass   = "p25right border";
        else
        if ($pct < 75)
            $pctclass   = "p50right border";
        else
        if ($pct < 100)
            $pctclass   = "p75right border";
        else
            $pctclass   = "p100right border";
    }           // style with border
    else
    {           // style without border
        if ($pct <= 0)
            return 'p00right';
        else
        if ($pct < 5)
            return 'p01right';
        else
        if ($pct < 15)
            return 'p05right';
        else
        if ($pct < 25)
            return 'p15right';
        else
        if ($pct < 50)
            return 'p25right';
        else
        if ($pct < 75)
            return 'p50right';
        else
        if ($pct < 100)
            return 'p75right';
        else
            return 'p100right';
    }           // style without border
    return $pctclass;
}       // pctClass

/************************************************************************
 *  function curPageURL                                                 *
 *                                                                      *
 *  Get the current page URL.                                           *
 *                                                                      *
 *  Returns:                                                            *
 *      a string that can be used for redirect                          *
 ************************************************************************/
function curPageURL()
{
    if (array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on")
        $pageURL    = "https";
    else
        $pageURL    = 'http';
    $pageURL        .= "://" . $_SERVER["SERVER_NAME"];
    if ($_SERVER["SERVER_PORT"] != "80")
        $pageURL    .= ":" .  $_SERVER["SERVER_PORT"];
    $pageURL        .= $_SERVER["REQUEST_URI"];
    return $pageURL;
}       // function curPageURL

function display_xml_error($error, $xml)
{
    $return         = $xml[$error->line - 1] . "\n";
    $return         .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
        case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "\n  Line: $error->line" .
               "\n  Column: $error->column";

    if ($error->file) {
        $return .= "\n  File: $error->file";
    }

    return "$return\n\n--------------------------------------------\n\n";
}       // function display_xml_error

/************************************************************************
 *  function showTrace                                                  *
 *                                                                      *
 *  Display the accumulated trace information.                          *
 ************************************************************************/
$warnid     = '';
function showTrace()
{
    global $warn;
    global $warnid;

    if (strlen($warn) > 0)
    {           // output trace and warning information
        //libxml_use_internal_errors(true);

        //$doc = simplexml_load_string('<div>' .
        //              str_replace(array('&copy;','&nbsp'),
        //                      array('&amp;copy;','&amp;nbsp;'),
        //                      $warn) .
        //               '</div>');
        //$xml = explode("\n", $warn);

        //if (!$doc) {
        //    $errors = libxml_get_errors();

        //    foreach ($errors as $error) {
        //  echo display_xml_error($error, $xml);
        //    }

        //    libxml_clear_errors();
        //}
?>
        <div id='debugTrace<?php print $warnid; ?>' class='warning'>
          <?php print $warn; ?>
        </div>
<?php
        if ($warnid == '')
            $warnid = 1;
        else
            $warnid++;
        $warn   = '';
    }           // output trace and warning information
}       // function showTrace

/************************************************************************
 *  function debugParm                                                  *
 *                                                                      *
 *  For generating a debug trace of the parameters passed to a script   *
 *  by either method=get or method=post translate the parameter value   *
 *  to a string that can be included in the debugging output.           *
 *                                                                      *
 *  Parameter:                                                          *
 *      $value      parameter value                                     *
 *                                                                      *
 *  Returns:                                                            *
 *      string                                                          *
 ************************************************************************/
function debugParm($value)
{
    if (is_array($value))
    {
        $valueText  = 'array(';
        $comma      = '';
        foreach($value as $id => $val)
        {
            $valueText      .= $comma;
            if (is_string($id))
                $valueText  .= "'$id' => ";
            if (is_string($val))
                $valueText  .= "'$val'";
            else
            if (is_numeric($val))
                $valueText  .= $val;
            else
                $valueText  .= print_r($val, true);
            $comma          = ',';
        }
        $valueText  .= ')';
    }
    else
    if (is_null($value))
        $valueText  = 'NULL';
    else
    if ($value === false)
        $valueText  = 'FALSE';
    else
    if ($value === true)
        $valueText  = 'TRUE';
    else
    if (is_string($value))
        $valueText  = "'$value'";
    else
        $valueText  = $value;

    return $valueText;
}       // function debugParm

/************************************************************************
 *  xmlentities                                                         *
 *                                                                      *
 *  For some reason PHP does not have a function that translates        *
 *  characters that have a special meaning in XML ('&', '<', '>') to    *
 *  their corresponding character entities.  The closest is the         *
 *  function htmlentities, but that translates all of the characters    *
 *  for which entities are defined in HTML, not just the 3 supported    *
 *  by XML.                                                             *
 *                                                                      *
 *  Parameter:                                                          *
 *      in      string to translate                                     *
 *                                                                      *
 *  Returns:                                                            *
 *      string with '&', '<', and '>' replaced by character entities.   *
 *                                                                      *
 *  Deprecated: use htmlentities(,ENT_XML1)                             *
 ************************************************************************/
function xmlentities($in)
{
    $specchars  = array('&', '<', '>', '&mdash;');
    $entities   = array("&amp;", "&lt;", "&gt;", "-");
    return str_replace($specchars, $entities, $in);
}       // xmlentities
