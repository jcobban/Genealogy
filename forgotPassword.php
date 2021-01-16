<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  forgotPassword.php                                                  *
 *                                                                      *
 *  This script sends an e-mail to the user to request confirmation     *
 *  of a request to reset the password.                                 *
 *                                                                      *
 *  History:                                                            *
 *      2015/08/04      Created                                         *
 *      2015/12/30      fix conflict with autoload                      *
 *      2017/09/12      use get( and set(                               *
 *      2018/02/04      use class Template                              *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *		2021/01/03      correct XSS vulnerability                       *
 *		                improve support for multiple languages          *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// the user is not signed on so we must act on the userid or e-mail
// supplied by the user
$user           = null;     // instance of User
$username       = null;     // name
$email          = null;     // e-mail address
$lang           = 'en';

if (isset($_POST) && count($_POST) > 0)
{                   // invoked by post
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {   // loop through all parameters
        if ($debug)
            $warn   .= "<p>\$_POST['$key']='$value'</p>\n";
        $value                      = trim($value);
        switch(strtolower($key))
        {       // act on specific parameter
            case 'userid':
            case 'username':
            {
                $username           = trim($value);
                break;
            }               // userid

            case 'email':
            {
                if (is_null($email))
                    $email              = $value;
                break;
            }               // email

            case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }
        }       // act on specific parameter
    }           // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}               // invoked by post 
else
{
    $shapassword    = null;
}

$template       = new FtTemplate("forgotPassword$lang.html");
$translate      = $template->getTranslate();
$t              = $translate['tranTab'];
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'forgotPassword'));
if (!isset($_POST) || count($_POST) == 0)
    $msg            .= $template['notPost']->innerHTML;

// get existing account details
if (is_string($username))
{
    $user           = new User(array("username" => $username));
    if ($user->isExisting())
    {
        $email          = $user->get('email');
        $shapassword    = $user->get('shapassword');
    }
    else
    {
        $text   = $template['noAccountUser']->innerHTML;
        $msg    .= str_replace('$username', htmlspecialchars($username));
    }
}
else
if (is_string($email))
{
    // get existing account details
    $user               = new User(array("email" => $email));
    if ($user->isExisting())
    {
        $username       = $user->get('username');
        $shapassword    = $user->get('shapassword');
    }
    else
    {
        $text   = $template['noAccountEmail']->innerHTML;
        $msg    .= str_replace('$email', htmlspecialchars($email));
    }
}
else
{
    $username   = $t['Unknown'];
    $email      = $t['Unknown'];
}

$template->set('USERID',    htmlspecialchars($username));
$template->set('EMAIL',     htmlspecialchars($email));
$template->set('LANG',      $lang);

if ($user)
{           // missing parameters
    $template->updateTag('needuser', null);
}           // missing parameters

$serverName = $_SERVER['SERVER_NAME'];
$proto      = $_SERVER['REQUEST_SCHEME'];
$headers    = "MIME-Version: 1.0" . "\r\n" .
                      "Content-type:text/html;charset=UTF-8" . "\r\n" .
                      'From: <webmaster@jamescobban.net>' . "\r\n";
$tag        = $template['emailsubject'];
if ($tag)
{
    $emailSubject   = str_replace('$username',
                              $username,
                              trim($tag->innerHTML()));
    $tag        = $template->getElementById('emailbody');
    $emailBody  = str_replace(array('$username','$shapassword','$email','$proto','$serverName'),
                          array($username, $shapassword,$email,$proto,$serverName),
                          trim($tag->innerHTML()));
    if ($debug)
    {
        $warn       .= "<p>mail('$email','$emailSubject'," . 
                            \Templating\escape($emailText) . "</p>\n";
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
    }           // could not send mail
}           // could not get mail message body
else
    $msg    .= "Cannot find element with id='lostpassword' in the template.";
$template->display();
