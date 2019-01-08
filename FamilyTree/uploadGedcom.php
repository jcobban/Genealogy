<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  uploadGedcom.php													*
 *																		*
 *  This script displays a dialog for uploading a GEDCOM 5.5 family     *
 *  tree and merging it with the existing family tree.                  *
 *																		*
 *    History:															*
 *		2018/11/28      created                                         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *		open code														*
 ***********************************************************************/
$cc			    = 'CA';
$countryName	= 'Canada';
$lang		    = 'en';		// default english

// process parameters passed by caller
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

	    case 'debug':
	    {		// requested debug
			break;
	    }		// requested debug
	}		// switch on parameter name
}			// foreach parameter
$update     = canUser('edit');

$tempBase	= $document_root . '/templates/';
$template	= new FtTemplate("${tempBase}page$lang.html");
$includeSub	= "uploadGedcom$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub     = 'uploadGedcomen.html';
}
$template->includeSub($tempBase . $includeSub,
					  'MAIN');

$template->display();
