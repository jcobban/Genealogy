<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegQuery.php													*
 *																		*
 *  Display the query dialog for transcriptions of Death registrations.	*
 *																		*
 *  History:															*
 *		2017/10/19		created										    *
 *		2018/01/04		remove Template from template file names	    *
 *		2019/01/06      use namespace Genealogy                         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/common.inc';

$cc			        = 'CA';		// country code
$countryName		= 'Canada';	// country name
$lang			    = 'en';		// default language
$domain			    = 'CAON';	// default domain
$province		    = 'ON';		// selected province code
$provinceName		= 'Ontario';	// selected province name
$regyear            = '';
$regnum             = '';

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{					// loop through all parameters
	if (is_string($value))
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	else
	if (is_array($value))
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>array</td></tr>\n"; 

    switch(strtolower($key))
    {				// switch on parameter name
		case 'domain':
		{			// domain code
		    $domain		= $value;
		    break;
		}			// domain code
		
		case 'regyear':
		{			// registration year
		    $regyear		= $value;
		    break;
        }			// registration year

		case 'regnum':
		{			// registration number
		    $regnum 		= $value;
		    break;
        }			// registration number

		case 'lang':
		case 'language':
		{			// language code
		    if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
		    break;
		}			// language code

    }				// switch on parameter name
}					// foreach parameter
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";

$domainObj			= new Domain(array('domain'	=> $domain,
						   'language'	=> $lang));
if ($domainObj->isExisting())
{
    $provinceName		= $domainObj->get('name');
    $province			= substr($domain, 2);
    $cc				= substr($domain, 0, 2);
    $country			= new Country(array('code' => $cc));
    $countryName		= $country->get('name');
}

$template		= new FtTemplate("DeathRegQuery$lang.html");

$template->set('COUNTRYNAME',		$countryName);
$template->set('PROVINCENAME',		$provinceName);
$template->set('DOMAIN',	    	$domain);
$template->set('REGYEAR',	    	$regyear);
$template->set('REGNUM',	    	$regnum);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Deaths');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
