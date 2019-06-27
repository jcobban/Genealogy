<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\escape;

/************************************************************************
 *  ListOfAdvertisers.php												*
 *																		*
 *  This script displays a list of advertisers.							*
 *																		*
 *    History:															*
 *		2015/06/18		created											*
 *		2018/10/18		use class Template								*
 *																		*
 *  Copyright 2018 James A. Cobban										*
 ************************************************************************/
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// common
$lang               = 'en';

foreach($_GET as $key => $value)
{
    switch (strtolower($key))
    {
        case 'lang':
        {
            if (strlen($value) == 2)
                $lang       = strtolower($value);
            break;
        }
    }               // act on specific parameter
}                   // loop through parameters

$template		= new FtTemplate("ListOfAdvertisers$lang.html");

$scripts	        = array();
$dh                 = opendir($document_root . '/Advertisements/');
if ($dh)
{		// found directory
    while (($filename = readdir($dh)) !== false)
    {		// loop through files
		if (strlen($filename) > 5 &&
		    substr($filename, strlen($filename) - 5) == '.html' &&
		    $filename != 'index.html' &&
		    substr($filename, 0, 10) != 'AddForRent')
		    $scripts[]	= $filename;
    }		// loop through files
    sort($scripts);
}		// found advertisements directory
else
    $warn   .= "<p>Unable to open '" . $document_root .
                "/Advertisements/</p>\n";

$rowElt         = $template->getElementById('adrow');
$rowHtml        = $rowElt->outerHTML();
$data           = '';
for ($i = 0; $i < count($scripts); $i++)
{		// loop through scripts in order
    $filename	= $scripts[$i];
    $rtemplate  = new Template($rowHtml);
    $atemplate  = new Template("$document_root/Advertisements/$filename");
    $divs       = $atemplate->getDocument()->getElementsByTagName('div');
    $div        = $divs[0];
    $divText    = $div->outerHTML();
    $res        = preg_match('#^(.*?style=.)([^\'"]*)(.*)$#is',
                             $divText,
                             $matches);
    if ($res)
        $divText    = $matches[1] . $matches[2] . ' float: left; display: block;' . $matches[3];
    $rtemplate->set('AD', $divText);
    $data       .= $rtemplate->compile();
}		// loop through scripts in order
$template->updateTag('adrow',
                     $data);

$template->display();
