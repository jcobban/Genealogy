<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  LocationDialogsXML.php 												*
 *																		*
 *  Retrieve the language specific set of location dialog templates.    *
 *                                                                      *
 *  Input:                                                              *
 *      lang            ISO 2-character language code                   *
 *																		*
 *  History:															*
 *		2018/12/27		created                                         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
$document_root	    = $_SERVER['DOCUMENT_ROOT'];
$lang		        = 'en';
foreach ($_GET as $key => $value)
{
    switch(strtolower($key))
    {
        case 'lang':
        {
            if (strlen($value) > 2)
                $lang           = strtolower(substr($value, 0, 2));
            break;
        }
    }
}

$tempBase		    = $document_root . '/templates/';
$filename           = "{$tempBase}LocationDialogs$lang.html";
if (!file_exists($filename))
    $filename       = "{$tempBase}LocationDialogsen.html";

// display the results
header("Content-Type: text/xml");
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print file_get_contents($filename);
