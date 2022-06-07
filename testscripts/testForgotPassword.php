<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Test Forgot Password                                                *
 *                                                                      *
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';

$lang           = 'en';

if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue              = htmlspecialchars($value);
        $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
                                    "<td class='white left'>" .
                                    "$safevalue</td></tr>\n"; 
        $keylc                  = strtolower($key);
        switch($keylc)
        {           // act on specific parameter
            case 'lang':
                $lang           = FtTemplate::validateLang($value);
                break;

        }
    }
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by method=get

$template           = new FtTemplate("TestCommon$lang.html");

if (!canUser('yes'))
    $msg            .= $template['adminOnly']->innerHTML;

$template->set('SUBJECT',       'Forgot Password');
$template->set('TITLE',         'Test Forgot Password');
$scriptName         = $_SERVER['SCRIPT_NAME'];
$strpos             = strpos($scriptName, '/testscripts');
$template->set('DIRECTORY', substr($scriptName, 0, $strpos));

$testbody		= "  <form action=\"/forgotPassword.php\" method=\"post\"> \n";
$testbody		.= "    <div class=\"grid2test\">\n";
$testbody		.= "      <label class='label' for=\"userid\">User ID:</label>\n";
$testbody		.= "      <input type='text' name=\"userid\" id=\"userid\">\n";
$testbody		.= "      <label class='label' for=\"email\">or E-Mail Address:</label>\n";
$testbody		.= "      <input type='text' name=\"email\" id=\"email\">\n";
$testbody		.= "      <button type='submit' id=\"Submit\">Test</button>\n";
$testbody		.= "    </div>\n";
$testbody		.= "  </form>\n";
$template->set('TESTBODY',      $testbody);

$template->display();
