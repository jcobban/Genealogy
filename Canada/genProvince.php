<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genProvince.php														*
 *																		*
 *  Display a web page containing links for a particular CA Province	*
 *  from the Legacy database.											*
 *																		*
 *  Parameters:															*
 *		Domain			4 character domain identifier					*
 *																		*
 *  History (as genOntario.php):										*
 *		2010/08/23		change to new standard layout					*
 *		2011/04/09		change to PHP									*
 *		2011/04/23		order death query after marriage query			*
 *		2012/05/09		add link to counties management					*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/06/29		add support for Wesleyan Methodist Baptisms		*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/08/16		display nominal index in separate tab			*
 *		2013/11/10		open more links in new window					*
 *		2013/12/24		use CSS for layout instead of tables			*
 *		2014/10/19		display counties link to everyone				*
 *		2014/12/30		Birth registration scripts moved to Canada		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/30		add County Marriage Query						*
 *						display trace data								*
 *		2016/05/20		CountiesEdit moved to folder Canada				*
 *		2017/07/18		separate district marriages from county			*
 *		2017/11/13		use Template									*
 *		2018/01/04		redirect to genProvince.php						*
 *  History:															*
 *		2014/12/18		created											*
 *		2016/01/19		add id to debug trace							*
 *		2016/05/20		CountiesEdit moved to folder Canada				*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/11/12		ensure $cc and $countryName initialized			*
 *		2017/02/07		use class Country								*
 *		2018/01/01		support language parameter						*
 *		2018/01/04		remove Template from template file names		*
 *		2019/02/21      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
$code	    	= null;
$cc		    	= 'CA';		            // country code
$countryName	= 'Canada';         	// country name
$domain	    	= 'CAON';	        	// domain code
$domainName		= 'Canada: Ontario:';	// domain name
$stateName		= 'Ontario';        	// state/province name
$lang		    = 'en';	                // language code

foreach ($_GET as $key => $value)
{			            // loop through all parameters
	switch(strtolower($key))
	{		            // act on specific parameters
	    case 'code':
	    {		        // state postal abbreviation
			if (preg_match('/[a-zA-Z]{2,3}/', $value))
			    $domain		= 'CA' . $value;
			break;
	    }		        // state postal abbreviation

	    case 'domain':
	    case 'regdomain':
	    {		        // domain code
			if (preg_match('/[a-zA-Z]{4,5}/', $value))
			    $domain		= $value;
			break;
	    }		        // domain code

	    case 'lang':
	    {		        // language code
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
			break;
	    }		        // language code
	}		            // act on specific parameters
}			            // loop through all parameters

$domainObj		= new Domain(array('domain'	    => $domain,
								   'language'	=> $lang));
$cc			    = substr($domain, 0, 2);
$code		    = substr($domain, 2, 2);
$countryObj		= new Country(array('code' => $cc));
$countryName	= $countryObj->getName($lang);
$stateName		= $domainObj->getName(0);
$domainName		= $domainObj->getName(1);

$tempBase		= $document_root . '/templates/';
$includeSub		= "genProvince$domain$lang.html";
if (!file_exists($tempBase . "genProvince{$domain}en.html"))
{                   // domain code not supported
    $includeSub = "genProvince$lang.html";
}                   // domain code not supported
if (!file_exists($tempBase . $includeSub))
{	    		            // no template for domain in chosen language
	$includeSub	            = "genProvince{$domain}en.html";
	if (!file_exists($tempBase . $includeSub))
	{	    	            // no template for domain in English
	    $includeSub	        = "genProvince$cc$lang.html";
	    if (!file_exists($tempBase . $includeSub))
	    {		            // no template for country in chosen language
			$includeSub	    = "genProvince{$cc}en.html";
			if (!file_exists($tempBase . $includeSub))
			{	            // no template for country in English
			    $includeSub	= "genProvince$lang.html";
			}	            // no template for country in English
	    }	    	        // no template for country in chosen language
	}	    	            // no template for domain in English
}			                // no template for domain in chosen language
$template		= new FtTemplate($includeSub);

$template->set('COUNTRYNAME',	$countryName);
$template->set('PROVINCENAME',	$stateName);
$template->set('DOMAINNAME',	$domainName);
$template->set('DOMAIN',		$domain);
$template->set('CC',		    $cc);
$template->set('LANG',		    $lang);
$template->set('CONTACTTABLE',	'Domains');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
