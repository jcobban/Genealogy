<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BirthRegQuery.php													*
 *																		*
 *  Generalized dialog for querying the table of birth registrations	*
 *  for any province.													*
 *																		*
 *  History:															*
 *		2014/12/18		created											*
 *		2015/01/23		misspelled variable $domainname					*
 *						uninitialized variable $domain					*
 *		2015/01/26		add birth date range field						*
 *		2015/05/11		location of help page changed					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/17		display debug trace								*
 *						pass debug option to search scripts				*
 *		2016/01/19		add id to debug trace							*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/07		use class Country								*
 *		2018/10/05      use class Template                              *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Domain.inc";
    require_once __NAMESPACE__ . "/Country.inc";
    require_once __NAMESPACE__ . "/Language.inc";
    require_once __NAMESPACE__ . "/Template.inc";
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// validate all parameters passed to the server script
$code		    = 'ON';
$cc			    = 'CA';
$domain		    = 'CAON';
$countryName	= 'Canada';
$domainName		= 'Canada: Ontario';
$stateName		= 'Ontario';
$lang	    	= 'en';

foreach ($_GET as $key => $value)
{			    // loop through all parameters
	switch(strtolower($key))
	{	    	// act on specific parameters
	    case 'code':
	    {		// state postal abbreviation
			$code		= $value;
			$cc		    = 'CA';
			$domain		= 'CA' . $code;
			break;
	    }		// state postal abbreviation

	    case 'domain':
	    case 'regdomain':
	    {		// state postal abbreviation
			$domain		    = $value;
			$cc		        = substr($domain, 0, 2);
			break;
	    }		// state postal abbreviation

	    case 'lang':
	    {
			if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }
	}   		// act on specific parameters
}   			// loop through all parameters

$domainObj	    = new Domain(array('domain'	    => $domain,
				                   'language'	=> $lang));
$countryObj	    = new Country(array('code' => $cc));
$countryName	= $countryObj->getName();
$domainName	    = $domainObj->getName(1);
$stateName	    = $domainObj->getName(0);
$code		    = substr($domain, 2, 2);

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= $tempBase . "BirthRegQuery$lang.html";
if (!file_exists($includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
    $includeSub		= $tempBase . "BirthRegQueryen.html";
}
$template->includeSub($includeSub, 'MAIN');
$template->set('COUNTRYNAME',		$countryName);
$template->set('CC',		        $cc);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	    	$domain);
$template->set('STATENAME',	    	$stateName);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
if ($debug)
    $template->set('DEBUG',		    'Y');
else
    $template->set('DEBUG',		    'N');

$template->display();
