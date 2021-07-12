<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  invokeByPost.php                                                    *
 *                                                                      *
 *  Invoke a script that is normally invoked by method="post" using     *
 *  a URL.                                                              *
 *                                                                      *
 *  Parameters:                                                         *
 *      script          string identifying script to invoke             *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/05      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// process parameters
$script                     = null;
$parmsList                  = array();
if (count($_GET) > 0)
{                       // invoked by URL
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {                   // loop through all parameters
        $safevalue          = htmlspecialchars(trim($value));
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                           "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'script':
            {           // requested script
                $script             = $value;
                break;
            }           // requested script

            default:
            {
                $parmsList[$key]    = $value;
                break;
            }
        }           // switch on parameter name
    }               // foreach parameter
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}                   // invoked by URL

$includeSub         = "invokebyPosten.html";
$template           = new FtTemplate($includeSub);

// validate parameters
if (is_null($script))
{           // missing parameter
    $script         = '';
}           // missing parameter

if (strlen($msg) == 0)
{           // no errors detected
    $template->set('SCRIPT',    $script);
    $rowtext        = $template['parmRow$key']->outerHTML;
    $text           = '';
    foreach($parmsList as $key => $value)
    {
        $text   .= str_replace(array('$key','$value'),
                               array($key, $value),
                               $rowtext);
    }
    $template['parmRow$key']->update($text);
}           // no errors detected

$template->display();
