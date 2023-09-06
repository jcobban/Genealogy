<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  resetPassword.php                                                   *
 *                                                                      *
 *  This script resets the user's password and sends an e-mail to the   *
 *  user with the new password.                                         *
 *                                                                      *
 *  History:                                                            *
 *      2015/08/04      Created                                         *
 *      2015/12/30      fix conflict with autoload                      *
 *      2016/01/02      do not generate passwords with <>               *
 *      2016/01/19      add id to debug trace                           *
 *      2017/09/12      use get( and set(                               *
 *      2018/02/04      use class Template                              *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2020/12/03      correct XSS vulnerabilities                     *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  randomPassword                                                      *
 *                                                                      *
 *  Generate a random password.                                         *
 *  The selection of characters excludes the letters I and O,           *
 *  lower case 'l', and the digits 1 and 0 to avoid misinterpretation.  *
 *                                                                      *
 *  Input:                                                              *
 *      len     number of characters in the resulting password          *
 ************************************************************************/
function randomPassword($len)
{ 
    // passwordAlphabet omits I, O, l, 0, and 1 to avoid ambiguity 
    $passwordAlphabet   =  
                "ABCDEFGHJKLMNPQRSTUVWXYZ" .
                "abcdefghjkmnpqrstuvwxyz" .
                "23456789" .
                "!_-+*.^$#~%";
    $newPassword    = '';
    for ($i = 0; $i < $len; $i++)
    {
        $index      = random_int(0 , strlen($passwordAlphabet) - 1);
         substr($passwordAlphabet, $index, 1) . "</p>";
        $newPassword    .= substr($passwordAlphabet, $index, 1);
    }
    return $newPassword;
}       // function randomPassword

// the user is not signed on so we must act on the userid or e-mail
// supplied by the user
$user                   = null;     // instance of User
$username               = null;     // name
$email                  = null;     // e-mail address
$lang                   = 'en';

foreach($_REQUEST as $key => $value)
{                           // loop through all parameters
    if ($debug)
        $warn   .= "<p>\$_REQUEST['$key']='" . 
                    htmlspecialchars($value) . "'</p>\n";
    switch(strtolower($key))
    {                       // act on specific parameter
        case 'uid':
        case 'userid':
        case 'username':
            if (strlen($value) > 0)
            {               // userid supplied
                $username       = trim($value);
            }               // userid supplied
            break;          // username

        case 'email':
            if (strlen($value) > 0)
            {               // email supplied
                $email          = trim($value);
            }               // email supplied
            break;          // email

        case 'lang':
            $lang               = FtTemplate::validateLang($value);
            break;

        case 'validate':
            if (strlen($value) > 0)
                $code           = $value;
            break;

    }                   // act on specific parameter
}                       // loop through all parameters

$template       = new FtTemplate("resetPassword$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'resetPassword'));

// get existing account details
if (is_string($username))
{
    $user    = new User(array("username" => $username));
    if ($user->isExisting())
        $email      = $user->get('email');
    else
    {
        $msg        .= $template['noUsernameMatch']->replace('$username',
                                            htmlspecialchars($username));
        $user       = null;
    }
}
else
if (is_string($email))
{
    // get existing account details
    $user           = new User(array("email" => $email));
    if ($user->isExisting())
        $username   = $user->get('userid');
    else
        $msg        .= $template['noEmailMatch']->replace('$email',
                                                htmlspecialchars($email));
}
else
    $msg            .= $template['MissingUserid']->innerHTML;

$template->set('USERID',    $username);
$template->set('EMAIL',     $email);
$template->set('LANG',      $lang);

if ($code != hash("sha256", $email . date('Y-m-d')))
    $msg            .= $template['InvalidCode']->innerHTML .
        " \$code='$code', hash='" . hash("sha256", $email . date('Y-m-d')); 
if (strlen($msg) == 0)
{                       // no errors
    $newPassword    = randomPassword(12);
    $template->set('NEWPASSWORD',      $newPassword);
    $user->setPassword($newPassword);
    $user->save();
}                       // no errors
else
{                       // missing parameters
    $template['passwordreset']->update( null);
}                       // missing parameters
$template->display();
