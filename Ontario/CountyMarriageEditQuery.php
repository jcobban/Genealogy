<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageEditQuery.php											*
 *																		*
 *  Prompt the user to enter parameters for a search of the 			*
 *  County Marriage Registration database.								*
 *																		*
 *  History:															*
 *		2016/01/28		created											*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/07/18		use domain CACW instead of CAON					*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/03/10		do not complain about lang parameter			*
 *		2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/11/20      use FtTemplate                                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc             = 'CA';
$countryName    = 'Canada';
$domain		    = 'CACW';	// default domain
$domainName	    = 'Canada West (Ontario)';
$domainError    = null;
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    if (is_string($value))
        $valueText      = htmlspecialchars($value);
    else
        $valueText      = htmlspecialchars(var_export($value));
    $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$valueText</td>" .
                        "</tr>\n"; 
    switch(strtolower($key))
    {		    // process specific named parameters
		case 'domain':
		case 'regdomain':
        {
            if (is_array($value) && count($value) > 0)
            {
                $value          = $value[0];
            }
            if (is_string($value) && strlen($value) >= 4)
                $domain	        = $value;
            else
                $domainError    = $valueText;
		    break;
		}		// RegDomain

		case 'debug':
		{
		    break;
		}		// handled by common code

		case 'regyear':
		case 'volume':
		case 'reportno':
		case 'itemno':
		case 'givennames':
		case 'surname':
		case 'residence':
		case 'soundex':
		{		// to do
		    break;
		}		// to do

		case 'lang':
		{		// language selection
			$lang		    = FtTemplate::validateLang($value);
		    break;
		}		// language selection

		default:
		{
		    $warn	        .= "Unexpected parameter $key='$valueText'. ";
		    break;
		}		// any other paramters
    }		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

$template		= new FtTemplate("CountyMarriageEditQuery$lang.html");
$template['otherStylesheets']->update(	
    		         array('filename'   => 'CountyMarriageEditQuery'));

$domainObj	    = new Domain(array('domain'	    => $domain,
								   'language'	=> $lang));
$domainName	    = $domainObj->get('name');
$cc	            = $domainObj->get('cc');
if (!$domainObj->isExisting())
{
	$msg	    .= "Domain '$domain' must be a supported two character country code followed by a two character state or province code. ";
}

$template->set('CONTACTTABLE',	'CountyMarriages');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DOMAIN',		$domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('CC',		    $cc);
$template->set('COUNTRYNAME',	$countryName);
$template->set('LANG',		    $lang);

$template->set('DEBUG',         $debug ? 'Y' : 'N');

// get a list of domains for the selection list
$getParms	    = array('cc'        => $cc,
                        'language'	=> 'en');
$domains	    = new DomainSet($getParms);
foreach($domains as $code => $domainObj)
{
    if ($code == $domain)
        $domainObj->set('selected',    'selected="selected"');
    else
        $domainObj->set('selected',    '');
}
$template['domain$code']->update($domains);

$template->display();
