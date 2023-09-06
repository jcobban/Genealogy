<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\TemplateTag;

/************************************************************************
 *  Signon.php                                                          *
 *                                                                      *
 *  This script provides a common interface for signing on as an        *
 *  authorized user of the web site.                                    *
 *                                                                      *
 *  Parameters (passed by POST):                                        *
 *      userid          new userid requested by user                    *
 *      password        new password supplied by user                   *
 *      act             action signalled from previous invocation       *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/22      Created                                         *
 *      2010/11/12      add help panel                                  *
 *      2010/12/20      correct URL of genealogy page in header &       *
 *                      trailer                                         *
 *      2011/03/19      identify keyboard shortcuts in button labels    *
 *                      add context specific help                       *
 *      2011/03/31      Permit spaces in user names                     *
 *      2011/04/22      support IE7                                     *
 *      2011/04/28      use CSS rather than tables for layout of header *
 *                      and trailer                                     *
 *      2012/01/05      improve validation of userid                    *
 *                      use last supplied userid as default             *
 *                      use id rather than name for buttons to avoid    *
 *                      passing them to the action script in IE         *
 *      2012/04/17      remove top and bottom divisions of page         *
 *      2012/05/28      set explicit class in <input> tags              *
 *      2012/06/21      add promotional information to page.            *
 *      2012/06/28      add contact "button"                            *
 *      2013/03/06      make validation of userids match with           *
 *                      Register.php                                    *
 *      2013/03/08      EU Cookie notice                                *
 *      2013/09/11      add support for SHA-512 password hashing        *
 *      2013/11/16      handle lack of database server gracefully       *
 *      2013/12/05      do not explicitly set $msg                      *
 *                      add debug output                                *
 *      2013/12/10      use CSS for layout                              *
 *      2013/12/18      add for= attribute to <label> tags              *
 *                      add id= attribute to <input> tags               *
 *      2014/03/27      on good signon redirect to UserInfo.php         *
 *                      remove syntax verification of userid and        *
 *                      password, since they are verified by matching   *
 *                      the database entry, this can only introduce     *
 *                      the inability of a valid user to sign on        *
 *                      do not enter this script if user is already     *
 *                      signed on                                       *
 *                      use class User to access Users table            *
 *      2014/06/26      expire login in 30 days, not 30 minutes         *
 *      2014/07/13      revert to 30 minutes for session                *
 *      2014/06/15      support for popupAlert moved to common code     *
 *      2014/07/18      add suggestion to use e-mail address as userid  *
 *      2015/02/02      do not log the individual back on using         *
 *                      the rememberme cookie if the person logged off  *
 *      2015/05/11      use ContactAuthor.php to contact administrator  *
 *      2015/06/30      did not issue error message if bad password     *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/04      add button to reset password                    *
 *                      add accesskey attribute to buttons              *
 *      2015/12/07      session tracking moved to cookies from session  *
 *      2015/12/30      fix conflict with autoload                      *
 *      2016/01/01      used saved userid and password even if new      *
 *                      logon specified                                 *
 *      2016/01/19      add id to debug trace                           *
 *      2016/02/02      new User option ousername removed               *
 *      2017/08/31      undefined $e because try/catch replaced by      *
 *                      if/then/else                                    *
 *      2017/09/12      use get( and set(                               *
 *      2017/12/13      $lang was not initialized if no parameters      *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/25      common functionality moved to class FtTemplate  *
 *      2018/02/10      use template for request to browser to reissue  *
 *                      the client's request once signed on             *
 *      2018/05/28      include specific CSS                            *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2019/12/22      use new message for missing password            *
 *      2020/12/03      correct XSS vulnerabilities                     *
 *      2022/06/13      more secure implementation of forgotten password*
 *      2023/06/01      correct implementation of forgotten password    *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
// the following obtains $userid and $authorized from the cookie
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$newuserid              = '';
$email                  = '';
$password               = '';
$action                 = '';
$validToken             = '';
$persist                = false;
$lang                   = 'en';
$redirectto             = "UserInfo.php";

// if invoked by method=get display the initial signon dialog
if (count($_GET) > 0)
{                   // invoked by URL to display signon dialog
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'redirect':
            {
                if (strlen($value) > 0)
                    $redirectto     = $value;
                break;
            }
    
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
    
            case 'debug':
            {
                if (strtolower($value) == 'y')
                    $debug      = true;
                break;
            }
    
        }       // act on specific parameters
    }           // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display signon dialog
else
if (count($_POST) > 0)
{                   // invoked by post for new signon
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
        switch(strtolower($key))
        {       // act on specific parameter
            case 'userid':
            {
                if (strlen($value) > 0)
                    $newuserid      = trim($value);
                break;
            }
    
            case 'email':
            {
                if (strlen($value) > 0)
                    $email          = trim($value);
                break;
            }
    
            case 'password':
            {
                if (strlen($value) > 0)
                    $password       = trim($value);
                break;
            }
    
            case 'act':
            {               // action to take
                if (strlen($value) > 0)
                    $action         = trim($value);
                break;
            }               // action to take
    
            case 'redirect':
            {               // next page
                if (strlen($value) > 0)
                    $redirectto     = trim($value);
                break;
            }               // next page
    
            case 'remember':
            {               // user requests persistence
                if (strlen($value) > 0 && strtolower($value) != 'n')
                    $persist        = true;
                break;
            }               // user requests persistence
    
            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
    
            case 'debug':
            {
                if (strtolower($value) == 'y')
                    $debug      = true;
                break;
            }
    
        }           // act on specific parameter
    }               // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by post for new signon

// if currently signed on, should not enter this script

$template                   = new FtTemplate("Signon$lang.html");

$validToken                 = hash("sha256", $email . date('Y-m-d'));
$template->set('VALIDTOKEN',    $validToken);
$template->set('EMAIL',         $email);
if (strlen($newuserid) == 0)
{
    if (strlen($email) > 0)
    {
        $user               = new User(array('email' => $email));
        if ($user->isExisting())
        {
            $newuserid      = $user['username'];
            $template->set('USERID',        $newuserid);
        }
    }
    else
        $msg                .= $template['specify']->innerHTML();
}
else
{                       // userid specified
    $template->set('USERID',        $newuserid);
    // get existing account details
    $user                   = new User(array('username' => $newuserid));
}                       // userid specified

if ($action == 'logoff')
{                       // remove session information
    $userid                     = $newuserid;
    $authorized                 = '';
    if (isset($_COOKIE['persistence']))
    {               // remove new implementation
        unset($_COOKIE['persistence']);
        setcookie('persistence', '', time() - 3600, '/');
    }               // remove new implementation
    if (isset($_COOKIE['rememberme']))
    {               // remove old implementation
        unset($_COOKIE['rememberme']);
        setcookie('rememberme', '', time() - 3600, '/');
    }               // remove old implementation
    unset($_SESSION['userid']);
    $msg            .= $template['signedoff']->innerHTML();
}                       // remove session information
else
if ($action == 'forgotPassword')
{                       // offer to reset password
    if ($user && $user->isExisting() && strlen($msg) == 0)
    {                   // found matching user
        $username       = $user['username'];
        $email          = $user['email'];
        $validToken     = hash("sha256", $email . date('Y-m-d'));
        $template->set('EMAIL',         $email);
        $serverName     = $_SERVER['SERVER_NAME'];
        $proto          = $_SERVER['REQUEST_SCHEME'];
        $emailFrom      = $template['emailfrom']->replace('$serverName',
                                                          $serverName);
        $emailFrom      = htmlspecialchars_decode($emailFrom);
        $headers        = "MIME-Version: 1.0\r\n" .
                          "Content-type:text/html;charset=UTF-8" . "\r\n" .
                          "From: $emailFrom\r\n";
        $tag            = $template['emailsubject'];
        $emailSubject   = str_replace('$username',
                                      $username,
                                      trim($tag->innerHTML));
        $tag            = $template['emailbody'];

        $emailBody  = str_replace(array('$username', '$email', '$proto', '$serverName','$validToken'),
                                  array($username, $email, $proto, $serverName, $validToken),
                                  trim($tag->innerHTML));
        if ($debug)
        {
            $emailTo    = htmlspecialchars($email);
            $emailClean = htmlspecialchars($emailBody);
            $warn       .= "<p>mail('$emailTo','$emailSubject','$emailClean')</p>\n";
        }           // debug
        $sent       = mail( $email,
                            $emailSubject,
                            $emailBody,
                            $headers);
    
        if ($sent)
        {           // mail sent successfully
            $template->updateTag('failed', null);
        }
        else
        {           // could not send mail
            $template->updateTag('respond', null);
            $emailTo    = htmlspecialchars($email);
            $emailBody  = htmlspecialchars($emailBody);
            $warn       .= "<p>mail('$emailTo','$emailSubject'," . 
                            "$emailBody)</p>\n";
        }           // could not send mail
    }               // found matching user
    else
    if (strlen($username) > 0 || strlen($email) > 0)
    {               // invalid parameters or no matching user
            $text       = $template['noAccount']->innerHTML;
            $username   = htmlspecialchars($username);
            $emailTo    = htmlspecialchars($email);
            $msg        .= str_replace(array('$username', '$email'), 
                                       array($username, $emailTo),
                                       $text);
    }               // invalid parameters or no matching user
}                   // offer to reset password
else
if (strlen($userid) > 0 && strlen($authorized) > 0 &&
    strlen($newuserid) == 0)
{       // already signed in and not specifying a new login
    header("Location: Account.php?lang=$lang");
    exit;
}       // already signed on

if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');
$template->set('LANG',          $lang);
$template->updateTag('otherStylesheets',
                     array('filename'   => '/Signon'));

$rcol                   = $template["rightCol1"]->innerHTML;
$template->set('RIGHTCOL',  $rcol);
$template->set('REDIRECTTO',    $redirectto);

if ($debug)
    $warn   .= "<p>Signon.php: " . __LINE__ .
                " newuserid='" . htmlspecialchars($newuserid) . 
                "', password='" . htmlspecialchars($password) . "'</p>\n";

if (is_object($user) && $user->isExisting())
{
    if (strlen($password) == 0)
    {
        $msg                .= $template['enterpassword']->innerHTML();
    }
    else
    if ($user->chkPassword($password, $persist))
    {               // password matches
        $_SESSION['userid']     = $newuserid;

        if ($persist)
        {           // session persistence
            setcookie('persistence', 
                      $user->get('persistence'), 
                      time() + 60*60*24*7, '/');
            //error_log("Signon.php: " . __LINE__ . 
            //            " setcookie('persistence', " . 
            //            $user->get('persistence') .
            //            ", seven days, '/')\n"); 
        }           // session persistence

        if ($redirectto == 'POST')
        {           // use history
            // knowledge of the previous page is only held in the
            // browser so the request to back-up to the previous
            // page must be performed by Javascript
            $template   = new FtTemplate("repost$lang.html");
            $template->set('REDIRECTTO',    $redirectto);
        }           // use history
        else
        if (strlen($warn) == 0)
        {           // redirect
            header("Location: " . $redirectto);
            exit;
        }           // redirect
        else
            $warn   .= "<p>tried header('Location: $redirectto')</p>\n";
    }               // password matches
    else
    {
        $msg            .= $template['incorrect']->innerHTML();
    }
}                   // userid matches
else
if (strlen($userid) > 0)
{                   // userid not found
    $msg                .= $template['incorrect']->innerHTML();
}                   // userid not found

$template->display();

