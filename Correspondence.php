<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Correspondence.php                                                  *
 *                                                                      *
 *  This script displays a list of correspondence regarding the         *
 *  Family Tree.                                                        *
 *                                                                      *
 *    History:                                                          *
 *      2021/06/24      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *      open code                                                       *
 ***********************************************************************/
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
    $lang       = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
else
    $lang       = 'en';

// process parameters passed by caller
if (isset($_GET) && count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {               // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                "$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {           // switch on parameter name
            case 'lang':
            {       // requested language
                $lang       = FtTemplate::validateLang($value);
                break;
            }       // requested language
    
            case 'debug':
            {       // requested debug
                break;
            }       // requested debug
        }           // switch on parameter name
    }               // foreach parameter
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by URL 

$template           = new FtTemplate("Correspondence$lang.html");
$trtemplate         = $template->getTranslate();

// create list of correspondence
$names              = array();
$dh                 = opendir('Correspondence');
if ($dh)
{                   // found Correspondence directory
    while (($filename = readdir($dh)) !== false)
    {               // loop through files
        if (strlen($filename) > 4)
        {
            $type   = strtolower(substr($filename, strlen($filename) - 4));
            if ($type == '.pdf')
            {
                $names[]    = $filename;
            }
            else
                $warn   .= "<p>filename='$filename', type='$type'</p>\n";
        }
    }               // loop through files
    sort($names);
}                   // found Correspondence directory

$correspondence        = array();
for ($i = 0; $i < count($names); $i++)
{                   // loop through correspondence in order
    $filename       = $names[$i];
    $type           = substr($filename, strlen($filename) - 4);
    $filename       = substr($filename, 0, strlen($filename) - 4);
    $correspondence[]  = array('filename'   => $filename,
                               'type'       => $type,
                               'i'          => $i);
}                   // loop through correspondence in order
$template->updateTag('correspondence$i',
                     $correspondence);

$template->display();
