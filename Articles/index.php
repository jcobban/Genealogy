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
 *		2019/02/21      use new FtTemplate consgructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/common.inc";

// process parameters
$lang		= 'en';
foreach ($_GET as $key => $value)
{			// loop through all parameters
    switch(strtolower($key))
    {
		case 'lang':
		{		// requested language
		    if (strlen($value) >= 2)
				$lang	= strtolower(substr($value, 0, 2));
		    break;
		}		// requested language

    }			// switch on parameter name
}			    // foreach parameter

$template		= new FtTemplate("Articles/ArticlesIndex$lang.html");

$template->display();
