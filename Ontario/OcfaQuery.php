<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaQuery.php												        *
 *																		*
 *  Prompt the user to enter parameters for a search of the 			*
 *  Ontario Cemetery Finding Aid table.									*
 *																		*
 *	Input parameters passed by method='GET':								*
 *		'DOMAIN'		domain id, default 'CAON'                       *
 *		'COUNTY'        county name, default any					    *
 *		'TOWNSHIP'      township name, default any					    *
 *		'CEMETERY'      cemetery name, default any					    *
 *		'GIVENNAMES'    givennames, default any					        *
 *		'SURNAME'       surname, default any					        *
 *		'LIMIT'         max rows displayed per page, default 20			*
 *		'LANG'		    ISO language code, default 'en';				*
 *																		*
 *  History:															*
 *		2019/05/01      created                                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/CountryName.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc		                = 'CA';
$countryName			= 'Canada';
$domain		            = 'CAON';	// default domain
$domainName	            = 'Canada: Ontario:';
$stateName	            = 'Ontario';
$county                 = '';
$township               = '';
$lang		            = 'en';
$cemetery               = '';
$givenname              = '';
$surname                = '';
$limit                  = 20;

$parmsText              = "<p class='label'>\$_GET</p>\n" .
                            "<table class='summary'>\n" .
                            "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {		// process specific named parameters
        case 'domain':
        case 'regdomain':
        {
            $domain		    = $value;
            break;
        }		// RegDomain

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang	= strtolower(substr($value, 0, 2));
            break;
        }		// handled by common code

        case 'debug':
        {
            break;
        }		// handled by common code

        case 'county':
        {
            if (strlen($value) > 0)
                $county             = $value;
            break;
        }

        case 'township':
        {
            if (strlen($value) > 0)
                $township           = $value;
            break;
        }

        case 'cemetery':
        {
            if (strlen($value) > 0)
                $cemetery           = $value;
            break;
        }

        case 'givenname':
        case 'givennames':
        {
            if (strlen($value) > 0)
                $givenname          = $value;
            break;
        }

        case 'surname':
        {
            if (strlen($value) > 0)
                $surname            = $value;
            break;
        }

        case 'count':
        case 'limit':
        {
            if (ctype_digit($value))
                $limit              = $value;
            break;
        }

        default:
        {
            $warn	.= "Unexpected parameter $key='$value'. ";
            break;
        }		// any other parameters
    }		// process specific named parameters
}			// loop through all input parameters
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";

// create instance of Template
$template		    = new FtTemplate("OcfaQuery$lang.html");

$domainObj	        = new Domain(array('domain'     => $domain,
                                       'language'	=> $lang));
$domainName	        = $domainObj->getName(1);
$stateName	        = $domainObj->getName(0);
if ($domainObj->isExisting())
{
    $cc		        = substr($domain, 0, 2);
    $countryObj	    = new Country(array('code' => $cc));
    $countryName	= $countryObj->getName($lang);
}
else
{
    $warn		.= "<p>Domain '$domain' must be a supported two character country code followed by a state or province code.</p>\n";
}

// global substitutions
$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('STATENAME',     $stateName);
if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');
$template->set('COUNTY',        $county);
$template->set('TOWNSHIP',      $township);
$template->set('CEMETERY',      $cemetery);
$template->set('GIVENNAME',     $givenname);
$template->set('SURNAME',       $surname);
$template->set('COUNT',         $limit);

$template->display();
