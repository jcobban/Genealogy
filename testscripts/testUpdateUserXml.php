<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUpdateUserXml.php												*
 *																		*
 *  test the updateUserXml.php script									*
 *																		*
 *  Parameters:															*
 *		userid			unique name of a registered user		    	*
 *		password		new password									*
 *																		*
 *  History:															*
 *		2019/12/25      created                                         *
 *																		*
 *  Copyright 2019 James A. Cobban										*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		        = 'en';
if (isset($_GET))
{
    foreach($_GET as $key => $value)
    {
		$key	        = strtolower($key);
		if ($key == 'lang')
		{
		    $lang       = FtTemplate::validateLang($value);
		}
    }
}

$template       = new FtTemplate("testUpdateUserXml$lang.html");

$template->display();
