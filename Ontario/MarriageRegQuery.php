<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  MarriageRegQuery.php												*
 *																		*
 *  Prompt the user to enter parameters for a search of the 			*
 *  Marriage Registration database.										*
 *																		*
 *	Parameters (passed by method=get):									*
 *		domain		    ISO 3166-2 domain code							*
 *		regdomain		synonym for domain								*
 *		lang		    ISO 639-1 preferred language					*
 *		regyear		    registration year							    *
 *		regnum		    registration num							    *
 *		limit		    default max number of rows in response			*
 *		count		    synonym for limit				                *
 *																		*
 *  History:															*
 *		2011/01/09		change URL of transcription status page			*
 *		2011/03/15		use <button>, change quotes, separate JS & HTML	*
 *						enable topRight button							*
 *		2011/06/17		use CSS to layout header and footer				*
 *						use class=form for form layout table			*
 *		2011/08/10		add checkboxes for roles to include in search	*
 *						pretty up table tags							*
 *		2011/09/13		change name of response script					*
 *		2011/10/24		support mouseover help for signon button		*
 *		2011/10/28		use button in place of link for statistics		*
 *		2012/05/06		set class for all input fields					*
 *		2013/08/04		defer initialization of facebook link			*
 *		2014/01/03		replace tables with CSS for layout				*
 *		2014/02/10		change to PHP so it can exploit domains table	*
 *						add <select name='RegDomain'> to choose domain	*
 *						group options with <fieldset>					*
 *		2014/04/01		do not warn for some parameters					*
 *		2015/07/01		add Occupation field for search					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/04/25		replace ereg with preg_match					*
 *		2017/02/07		use class Country								*
 *		2017/02/18		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *		2018/01/01		add language parameter							*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/07/28      accept ISO 3166 standard form for domain        *
 *		                list only domains that are in the same country  *
 *      2019/11/17      move CSS to <head>                              *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc		                = 'CA';
$countryName			= 'Canada';
$domain		            = 'CAON';	// default domain
$domainName	            = 'Canada: Ontario:';
$stateName	            = 'Ontario';
$lang		            = 'en';
$regyear                = '';
$regnum                 = '';
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
            if (strlen($value) >= 4)
                $domain		    = str_replace('-','',$value);
            break;
        }		// RegDomain

        case 'lang':
        {
            $lang	            = FtTemplate::validateLang($value);
            break;
        }		// handled by common code

        case 'debug':
        {
            break;
        }		// handled by common code

        case 'regyear':
        {
            if (ctype_digit($value))
                $regyear        = (int)$value;
            break;
        }

        case 'regnum':
        {
            if (ctype_digit($value))
                $regnum         = (int)$value;
            break;
        }

        case 'count':
        {
            if (ctype_digit($value))
                $limit          = (int)$value;
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
$template		    = new FtTemplate("MarriageRegQuery$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'MarriageRegQuery'));

$domainObj	        = new Domain(array('domain'     => $domain,
                                       'language'	=> $lang));
$domainName	        = $domainObj->getName(1);
$stateName	        = $domainObj->getName(0);
if ($domainObj->isExisting())
{
    $cc		        = substr($domain, 0, 2);
    $countryObj	    = $domainObj->getCountry();
    $countryName	= $countryObj->getName();
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
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regnum);
$template->set('COUNT',         $limit);

// get list of domains for selection list
$getParms	    	= array('language'	=> $lang,
                            'cc'        => $cc);
$domains	    	= new DomainSet($getParms);
$optionElt      	= $template['domain$code'];
if ($optionElt)
{
	$optionText     	= $optionElt->outerHTML();
	$result         	= '';
	foreach($domains as $code => $dom)
	{
	    $ttemplate  = new Template($optionText);
	    $ttemplate->set('code',         $code);
	    $ttemplate->set('state',        $dom['state']);
	    if ($code == $domain)
	        $ttemplate->set('selected', 'selected="selected"');
	    else
	        $ttemplate->set('selected', '');
	    $ttemplate->set('name',         $dom['name']);
	    $result     .= $ttemplate->compile();
	}
	$optionElt->update($result);
}
else
    $msg                .= "Cannot find element with id='domain\$code'. ";

$template->display();
