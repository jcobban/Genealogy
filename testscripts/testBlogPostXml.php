<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testBlogPostXml.php                                                 *
 *                                                                      *
 *  test the postBlogXml.php script                                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      idir        unique numeric key of instance of Person            *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/27      use common layout routines                      *
 *                      use HTML 4 features, such as <label>            *
 *      2022/04/20      use FtTemplate                                  *
 *                                                                      *
 *  Copyright 2022 James A. Cobban                                      *
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';

$idname         = 'id';
$idvalue        = '0';
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

            default:
                if (substr($keylc, 0, 2) == 'id')
                {
                    $idname     = $keylc;
                    $idvalue    = $value;
                }
                break;
        }
    }
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by method=get

$template           = new FtTemplate("TestCommon$lang.html");

if (!canUser('yes'))
    $msg            .= $template['adminOnly']->innerHTML;

$template->set('SUBJECT',       'Add Blog by AJAX');
$template->set('TITLE',         'Test Add Blog by AJAX');
$scriptName         = $_SERVER['SCRIPT_NAME'];
$strpos             = strpos($scriptName, '/testscripts');
$template->set('DIRECTORY', substr($scriptName, 0, $strpos));
$testbody           = '';
if (strlen($msg) == 0)
{                   // no errors
    $testbody       .= "<form name='evtForm' action='/postBlogXml.php' method='post'>\n";
    $testbody       .= "  <div class=\"grid2test\">\n";
    $testbody       .= "    <label class='labelSmall' for='$idname'>\n";
    $IDNAME         .= strtoupper($idname);
    $testbody       .= "    $IDNAME:\n";
    $testbody       .= "    </label>\n";
    $testbody       .= "    <input type='text' name='$idname'\n";
    $testbody       .= "               id='$idname'\n";
    $testbody       .= "               style='width: 9em'\n";
    $testbody       .= "        class='white rightnc' value='$idvalue'>\n";

    if ($idname == 'id')
    {
        $testbody   .= "    <label class='labelSmall' for='tablename'>Table:\n";
        $testbody   .= "    </label>\n";
        $testbody   .= "    <input type='text' name='tablename' id='tablename'\n";
    $testbody       .= "               style='width: 9em'\n";
        $testbody   .= "        class='white leftnc' value='tblIR'>\n";
    }
    else
    {
        $tablename  = 'tbl' . strtoupper(substr($idname,2));
        $testbody   .= "    <input type='hidden' name='tablename' id='tablename'\n";
        $testbody   .= "        class='white leftnc' value='$tablename'>\n";
    }
    $testbody       .= "    <label class='labelSmall' for='email'>Email:\n";
    $testbody       .= "    </label>\n";
    $testbody       .= "    <input type='text' name='email' id='email'\n";
    $testbody       .= "        size='64' maxlength='255'\n";
    $testbody       .= "               style='width: 64em'\n";
    $testbody       .= "        class='white leftnc' value=''>\n";
    $testbody       .= "    <label class='labelSmall' for='subject'>Subject:\n";
    $testbody       .= "    </label>\n";
    $testbody       .= "    <input type='text' name='subject' id='subject'\n";
    $testbody       .= "        size='64' maxlength='255'\n";
    $testbody       .= "               style='width: 64em'\n";
    $testbody       .= "        class='white leftnc'>\n";
    $testbody       .= "    <label class='labelSmall' for='update'>Update Existing:\n";
    $testbody       .= "    </label>\n";
    $testbody       .= "    <input type='text' name='update' id='update'\n";
    $testbody       .= "        size='1'\n";
    $testbody       .= "               style='width: 1em'\n";
    $testbody       .= "        class='white leftnc' value='N'>\n";
    $testbody       .= "    <label class='labelSmall' for='update'>Debug:\n";
    $testbody       .= "    </label>\n";
    $testbody       .= "    <input type='text' name='debug' id='debug'\n";
    $testbody       .= "        size='1'\n";
    $testbody       .= "               style='width: 1em'\n";
    $testbody       .= "        class='white leftnc' value='N'>\n";
    $testbody       .= "  </div>\n";
    $testbody       .= "    <textarea name='message' rows='5' cols='100'>[enter message]</textarea>\n";
    $testbody       .= "    <button type='submit'>Blog</button>\n";
    $testbody       .= "</form>\n";
    $testbody       .= "<div class='balloon' id='Helpid'>\n";
    $testbody       .= "Edit the unique numeric key (IDIR) of the individual to update.\n";
    $testbody       .= "</div>\n";
}               // no errors

$template->set('TESTBODY',      $testbody);

$template->display();
