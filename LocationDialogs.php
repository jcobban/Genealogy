<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  LocationDialogs.php 												*
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
require_once __NAMESPACE__ . '/common.inc';

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

print file_get_contents($filename);
