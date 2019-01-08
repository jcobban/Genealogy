<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genCountry.php														*
 *																		*
 *  Display the home page of a country.									*
 *																		*
 *  History:															*
 *		2017/10/23		created											*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/25		common functionality moved to class FtTemplate	*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James Alan Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		open code														*
 ***********************************************************************/
$cc		    	= 'CA';
$countryName	= 'Canada';
$lang		    = 'en';		// default english

// determine which districts to display
foreach ($_GET as $key => $value)
{		        	// loop through all parameters
	switch(strtolower($key))
	{
	    case 'cc':
	    case 'code':
	    case 'countrycode':
	    {
			$cc		        = strtoupper($value);
			$countryObj	    = new Country(array('code' => $cc));
			$countryName	= $countryObj->getName();
			break;
	    }

	    case 'lang':
       {	    	// language code
           if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }	    	// language code

	    default:
	    {	    	// unexpected
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }	    	// unexpected
	}	        	// switch on parameter name
}		        	// foreach parameter

$tempBase	        = $document_root . '/templates/';
$template	        = new FtTemplate("${tempBase}page$lang.html");
$includeSub	        = "genCountry$cc$lang.html";
if (!file_exists($tempBase . $includeSub))
{				    // no specific panel
    $language   	= new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub     = "genCountry{$cc}en.html";
	if (!file_exists($tempBase . $includeSub))
	{			    // no support for country
	    $includeSub	= "genCountry$lang.html";
	    if (!file_exists($tempBase . $includeSub))
	    {			// no language specific error page
			$langName	= $language->get('name');
			$nativeName	= $language->get('nativename');
			$sorry  	= $language->getSorry();
	        $warn   	.= str_replace(array('$langName','$nativeName'),
	                                   array($langName, $nativeName),
	                                   $sorry);
			$includeSub 	= 'genCountryen.html';
	    }			// no language specific error page
	}		    	// no support for country
}			    	// no specific page
$template->includeSub($tempBase . $includeSub,
			    	  'MAIN');
$template->set('COUNTRYNAME',	$countryName);
$template->set('CC',		    $cc);

$template->display();
