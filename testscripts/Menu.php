<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Menu.php                                                            *
 *                                                                      *
 *  This script displays a menu of available test drivers.              *
 *                                                                      *
 *    History:                                                          *
 *      2014/12/02      created                                         *
 *      2014/12/29      correct breadcrumbs                             *
 *      2022/04/07      use template                                    *
 *                                                                      *
 *  Copyright 2022 James A. Cobban                                      *
 ************************************************************************/
require_once __NAMESPACE__ . "/common.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";

$lang                   = 'en';
$template               = new FtTemplate("TestScriptMenu$lang.html");   
$title                  = "Menu of Test Scripts";
$template->set('TITLE', $title);
if (!canUser('yes'))
    $msg    .= 'Only administrators are authorized to use this function. ';

$telement       = $template['driver$i'];
if (strlen($msg) == 0)
{
    $scripts    = array();
    $dh         = opendir('.');
    if ($dh)
    {       // found directory
        while (($filename = readdir($dh)) !== false)
        {       // loop through files
            if (strlen($filename) > 4 &&
                substr($filename, strlen($filename) - 4) == '.php' &&
                $filename != 'Menu.php')
                $scripts[]  = $filename;
        }       // loop through files
        sort($scripts);
    }       // found Newsletters directory

    $array          = array();
    for ($i = 0; $i < count($scripts); $i++)
    {       // loop through scripts in order
        $filename   = $scripts[$i];
        $dispname   = preg_replace("/[A-Z]/", " $0", $filename);
        $dispname   = ucfirst(substr($dispname, 0, strlen($dispname) - 4));
        if (substr($dispname,0,4) == 'Test')
            $dispname   = 'Test ' . substr($dispname,4);
        $array[]    = array('i'         => $i,
                            'filename'  => $filename,
                            'dispname'  => $dispname);
    }

    $telement->update($array);
}               // no errors detected
else
{
    $telement->update();
}

$template->display();
