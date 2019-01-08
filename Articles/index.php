<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Articles/index.php													*
 *																		*
 *  This script displays the main entry point to the articles part		*
 *  of the web-site														*
 *																		*
 *    History:															*
 *		2015/01/29		created											*
 *		2018/01/23		use Template									*
 *		2018/02/25		use FtTemplate									*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/common.inc";

$lang		= 'en';
// process parameters
foreach ($_GET as $key => $value)
{			// loop through all parameters
    switch(strtolower($key))
    {
		case 'template':
		{		// requested template
		    if (strlen($value) > 0)
				$templateName	= $value;
		    break;
		}		// requested template

		case 'lang':
		{		// requested language
		    if (strlen($value) >= 2)
				$lang	= strtolower(substr($value, 0, 2));
		    break;
		}		// requested language

    }			// switch on parameter name
}			// foreach parameter

$title			= "Genealogy Articles";
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "Articles/ArticlesIndex$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	= new Language(array('code' => $lang));
	$langName	= $language->get('name');
	$nativeName	= $language->get('nativename');
    $sorry      = $language->getSorry();
    $warn       .= str_replace(array('$langName','$nativeName'),
                               array($langName, $nativeName),
                               $sorry);
    $includeSub		= 'Articles/ArticlesIndexen.html';
}
$template->includeSub($tempBase . $includeSub,
				      'MAIN');
$template->display();
