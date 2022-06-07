<?php
namespace Genealogy;
use \PDO;
use \NumberFormatter;
use \Exception;
/************************************************************************
 *  MailUsers.php                                                       *
 *                                                                      *
 *  Send a message to a subset of users matching a pattern, and if      *
 *  necessary continue to send with the next subset of those users.     *
 *                                                                      *
 *  History:                                                            *
 *      2022/05/29      created                                         *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang               = 'en';
$pattern            = '';
$authPattern        = '';
$mailPattern        = '';
$options            = '';
$subject            = 'General';
$offset             = 0;
$limit              = 50;
$id                 = '';
$body               = '';
$langtext           = null;
$patterntext        = null;
$authtext           = null;
$mailtext           = null;
$optionstext        = null;
$subjecttext        = null;
$offsettext         = null;
$limittext          = null;
$mainParms          = array();

if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                       // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
        $fieldLc    = strtolower($key);
        switch($fieldLc)
        {                   // act on specific parameter
            case 'lang':
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;      // lang

            case 'pattern':
            {
                if (preg_match('/^[a-zA-Z@_.^$-]+$/', $value))
                {
                    $pattern                = $value;
                    $mainParms['username']  = $value;
                }
                else
                    $patterntext            = htmlspecialchars($value);
                break;
            }

            case 'auth':
            case 'authpattern':
            {
                if (preg_match('/^[a-zA-Z@_.,-]+$/', $value))
                {
                    $authPattern            = $value;
                    $mainParms['auth']      = $value;
                }
                else
                    $authtext               = htmlspecialchars($value);
                break;
            }

            case 'mail':
            case 'mailpattern':
            {
                if (preg_match('/^[a-zA-Z@_.^$-]+$/', $value))
                {
                    $mailPattern            = $value;
                    $mainParms['email']     = $value;
                }
                else
                    $mailtext               = htmlspecialchars($value);
                break;
            }

            case 'options':
            {
                if (ctype_digit($value))
                {
                    $options                = $value;
                    $mainParms['options']   = $value;
                }
                else
                if (strlen($value) > 0)
                    $optionstext            = htmlspecialchars($value);
                break;
            }

            case 'subject':
            {
                if (preg_match('/^[^<>&]+$/', $value))
                {
                    $subject                = $value;
                }
                else
                if (strlen($value) > 0)
                    $subjecttext            = htmlspecialchars($value);
                break;
            }

            case 'password':
            {
                if (preg_match('/^length[<=>]\d+$/', $value))
                {
                    $password               = $value;
                    $mainParms['password']  = $value;
                }
                else
                    $passwordtext           = htmlspecialchars($value);
                break;
            }

            case 'body':
                $body                       = $value;
                break;      // body text for message

            case 'offset':
            {
                if (ctype_digit($value))
                    $offset                 = $value;
                else
                    $offsettext             = htmlspecialchars($value);
                break;
            }

            case 'limit':
            {
                if (ctype_digit($value))
                    $limit                  = $value;
                else
                    $limittext              = htmlspecialchars($value);
                break;
            }
        }                   // act on specific parameter
    }                       // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
    $newoffset      = $offset;
}                           // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{                   // invoked by method=get
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {                       // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
        $fieldLc    = strtolower($key);
        switch($fieldLc)
        {                   // act on specific parameter
            case 'lang':
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;      // lang

            case 'pattern':
            {
                if (preg_match('/^[a-zA-Z@_.^$-]+$/', $value))
                {
                    $pattern                = $value;
                    $mainParms['username']  = $value;
                }
                else
                    $patterntext            = htmlspecialchars($value);
                break;
            }

            case 'auth':
            case 'authpattern':
            {
                if (preg_match('/^[a-zA-Z@_.,-]+$/', $value))
                {
                    $authPattern            = $value;
                    $mainParms['auth']      = $value;
                }
                else
                    $authtext               = htmlspecialchars($value);
                break;
            }

            case 'mail':
            case 'mailpattern':
            {
                if (preg_match('/^[a-zA-Z@_.^$-]+$/', $value))
                {
                    $mailPattern            = $value;
                    $mainParms['email']     = $value;
                }
                else
                    $mailtext               = htmlspecialchars($value);
                break;
            }

            case 'options':
            {
                if (ctype_digit($value))
                {
                    $options                = $value;
                    $mainParms['options']   = $value;
                }
                else
                if (strlen($value) > 0)
                    $optionstext            = htmlspecialchars($value);
                break;
            }

            case 'subject':
            {
                if (preg_match('/^[^<>&]+$/', $value))
                {
                    $subject                = $value;
                }
                else
                if (strlen($value) > 0)
                    $subjecttext            = htmlspecialchars($value);
                break;
            }

            case 'password':
            {
                if (preg_match('/^length[<=>]\d+$/', $value))
                {
                    $password               = $value;
                    $mainParms['password']  = $value;
                }
                else
                    $passwordtext           = htmlspecialchars($value);
                break;
            }

            case 'body':
                $body                       = $value;
                break;      // body text for message

            case 'offset':
            {
                if (ctype_digit($value))
                    $offset                 = $value;
                else
                    $offsettext             = htmlspecialchars($value);
                break;
            }

            case 'limit':
            {
                if (ctype_digit($value))
                    $limit                  = $value;
                else
                    $limittext              = htmlspecialchars($value);
                break;
            }
        }                   // act on specific parameter
    }                       // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
    $newoffset      = $offset + $limit;
}

// create the Template instance
$template                   = new FtTemplate("MailUsers$lang.html");
$formatter                  = $template->getFormatter();

// if not the administrator do nothing
if (canUser('all'))
{       // only the administrator can use this dialog
    // get the parameters
    $namePattern        = '/^([A-Za-z_]+)(\d+)$/';

    $mainParms['limit']         = $limit;
    $mainParms['offset']        = $offset;

    $prevoffset                 = $offset - $limit;
    $nextoffset                 = $offset + $limit;


    // construct the blind carbon copy (BCC) list for bulk mailing
    $warn   .= "<p>filter=" . var_export($mainParms, true) . "</p>\n";
    $users                      = new UserSet($mainParms);
    $info                       = $users->getInformation();
    $warn                       .= "<p>SQL='" . $info['query'] . "'</p>\n";
    $count                      = $info['count'];
    $bcclist                    = $users->getMaillist();
    $admins                     = new UserSet(array('auth' => 'yes'));
    $tolist                     = $admins->getMaillist();


    $template->set('BCCLIST',                   $bcclist);
    $template->set('TOLIST',                    $tolist);
    $template->set('PATTERN',                   $pattern);
    $template->set('AUTHPATTERN',               $authPattern);
    $template->set('MAILPATTERN',               $mailPattern);
    $template->set('OPTIONS',                   $options);
    $template->set('SUBJECT',                   $subject);
    $template->set('PASSWORD',                  $password);
    $template->set('BODY',                      $body);
    $template->set('OFFSET',                    $newoffset);
    $template->set('LIMIT',                     $limit);
    $template->set('LAST',                      min($nextoffset, $count));
    $template->set('COUNT',                     $formatter->format($count));

    $template->updateTag('notadmin',            null);

    if (isset($_POST) && count($_POST) > 0)
    {
        $template->updateTag('sendrow',         null);
        $warn   .= "<p>sendmail bcc=$bcclist</p>\n";
        if (mail('unmonitored@jamescobban.net',
                 '[JamesCobban.net] ' . $subject,
                 $body,
                 array( 'From'          => 'unmonitored@jamescobban.net',
                        'Reply-To'      => 'webmaster@jamescobban.net',
                        'Bcc'           => $bcclist,
                        'MIME-Version'  => '1.0',
                        'Content-Type'  => 'text/html; charset=UTF-8')))
        {
            $warn   .= "<p>Mail sent.</p>\n";
        }
        else
        {
            $msg    .= "Mail send failed. ";
        }
    }
}       // only administrator can use this dialog
else
{       // not administrator
    $template->updateTag('locForm', null);
}       // not administrator

$template->display();
