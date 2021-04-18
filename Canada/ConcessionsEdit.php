<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ConcessionsEdit.php													*
 *																		*
 *  Display form for editting information about concessions for			*
 *  vital statistics records											*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain			two letter country code	+ state code	    	*
 *		County			three letter code								*
 *		Township		name											*
 *																		*
 *  History:															*
 *		2012/06/13		created											*
 *		2017/02/07		use class Country								*
 *		2018/10/24		use class Template  							*
 *		2018/11/11		get error message texts from template           *
 *		2021/01/13      correct XSS vulnerabilities                     *
 *		                improve parameter checking                      *
 *		2021/04/04      escape CONTACTSUBJECT                           *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/Concession.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$cc	    	    		= 'CA';   	    // country code
$cctext                 = null;
$countryName			= 'Canada';
$domain	    			= 'CAON';   	// administrative domain
$domainObj              = null;
$domaintext             = null;
$domainName				= 'Ontario';
$county		    		= null;		    // abbreviation
$countytext  		    = null;
$countyName				= "Unknown";	// full name
$township				= null;		    // abbreviation
$townshiptext  		    = null;
$townshipName			= "Unknown";	// full name
$concession				= null;		    // concession name
$concessionName			= "Unknown";	// full name
$lang           		= 'en';
$getParms				= array();

// get parameter values
if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
	$parmsText      = "<p class='label'>\$_GET</p>\n" .
	                  "<table class='summary'>\n" .
	                  "<tr><th class='colhead'>key</th>" .
	                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{				// loop through all parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
            "<td class='white left'>$value</td></tr>\n";
        $value                  = trim($value); 
		switch(strtolower($key))
		{			        // act on specific keys
		    case 'cc':
		    case 'country':
	        {
	            if (preg_match('/^\w\w$/', $value))
                    $cc	        	= strtoupper($value);
                else
                    $cctext         = htmlspecialchars($value);
				break;
		    }
	
		    case 'domain':
		    {
	            if (preg_match('/^\w{4,5}$/', $value))
				    $domain	        = strtoupper($value);
                else
                    $domaintext     = htmlspecialchars($value);
				break;
		    }
	
		    case 'county':
		    {
	            if (preg_match('/^\w+$/', $value))
				    $county		    = $value;
                else
                    $countytext     = htmlspecialchars($value);
				break;
		    }
	
		    case 'township':
            {
                if (preg_match('/^[^<>]+$/', $value))
				    $township		= $value;
                else
                    $townshiptext   = htmlspecialchars($value);
				break;
		    }
	
	        case 'lang':
	        {
	            $lang       = FtTemplate::validateLang($value);
	            break;
	        }
		}			// act on specific keys
	}				// loop through all parameters
	if ($debug)
	    $warn       .= $parmsText . "</table>\n";
}			        // invoked by method=get

if (canUser('edit'))
	$action			= 'Update';
else
	$action			= 'Display';

$template			= new FtTemplate("ConcessionsEdit$action$lang.html");

$template->set('CONTACTTABLE',	'Concessions');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('LANG',          $lang);
//$template->set('OFFSET',        $offset);
//$template->set('LIMIT',         $limit);
//$template->set('TOTALROWS',     $count);
//$template->set('FIRST',         $offset + 1);
//$template->set('LAST',          min($count, $offset + $limit));

// validate CC
if (is_string($cctext))
{
	$text                   = $template['CCInvalid']->innerHTML;
    $msg                    .= str_replace('$value', $cctext, $text);
    $countryName            = 'Invalid ' . $cctext;
}

// validate Domain
if (is_string($domaintext))
{
	$text                   = $template['domainInvalid']->innerHTML;
    $msg                    .= str_replace('$value', $domaintext, $text);
    $domainName             = 'Invalid ' . $domaintext;
}
else
{
	$domainObj	    = new Domain(array('domain'	    => $domain,
			               		       'language'	=> $lang));
	if ($domainObj->isExisting())
	{
	    $cc			        = substr($domain, 0, 2);
	    $countryObj		    = $domainObj->getCountry();
	    $countryName	    = $countryObj->getName();
	    $domainName		    = $domainObj->getName();
	    $getParms['domain']	= $domainObj;
	}
	else
	{
	    $text               = $template['domainInvalid']->innerHTML;
	    $msg                .= str_replace('$value', $domain, $text);
	    $domainName		    = $domainObj->getName();
	}
}

// validate county abbreviation
if (is_string($countytext))
{
	$text               = $template['countyInvalid']->innerHTML;
    $msg                .= str_replace(array('$county', '$domain'), 
                                       array($countytext, $domainName),
                                       $text);
    $countyName         = "Invalid $countytext";
}
else
if (is_null($county))
{
    $msg                .= $template['countyMissing']->innerHTML;
}
else
if ($domainObj && is_string($county))
{
	$countyObj      	= new County(array('domain'	=> $domainObj, 
					                       'county'	=> $county));
    $countyName         = $countyObj->getName();
    if ($countyObj->isExisting())
        $getParms['county']	= $countyObj;
    else
    {
        $text           = $template['countyInvalid']->innerHTML;
        $msg            .= str_replace(array('$county', '$domain'), 
                                       array($county, $domainName),
                                       $text);
    }
}

// validate township
if (is_string($townshiptext))
{
	$text               = $template['townshipInvalid']->innerHTML;
    $msg                .= str_replace(array('$township', '$county'),
                                       array($townshiptext, $countyName),
                                       $text);
    $townshipName       = "Invalid $townshiptext";
}
else
if (is_null($township))
{
    $msg                .= $template['townshipMissing']->innerHTML;
}
else
if ($domainObj && $countyObj && is_string($township))
{
	$townshipObj        = new Township(array('domain'	=> $domainObj, 
						                     'county'	=> $countyObj,
						                     'code'	    => $township));
	$townshipName	    = $townshipObj->getName();
    if ($townshipObj->isExisting())
	    $getParms['township']	= $townshipObj;
    else
    {
        $text           = $template['townshipInvalid']->innerHTML;
        $msg            .= str_replace(array('$township', '$county'),
                                       array($township, $countyName),
                                       $text);
    }
}

$template->set('CC',	    	$cc);
$template->set('COUNTRYNAME',	$countryName);
$template->set('DOMAIN',		$domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('COUNTYCODE',	$county);
$template->set('COUNTY',	    $county);
$template->set('COUNTYNAME',	$countyName);
$template->set('TOWNSHIP',	    $townshipName);
$template->set('TOWNSHIPNAME',	$townshipName);

if (strlen($msg) == 0)
{		        	// no errors
	// execute the query to get the contents of the page
	if (count($getParms) == 0)
        $getParms	= null;         // get entire table
	$concessions	= new RecordSet('Concessions', $getParms);

	if (count($concessions) == 0)
	{		        // no concessions defined for this county
	    $parms	= array('domain'	=> $domainObj,
					    'county'	=> $countyObj,
					    'township'	=> $townshipObj);
	    for($ic = 1; $ic <= 10; $ic++)
	    {
			$parms['conid']		= 'con ' . $ic;
			$parms['order']		= $ic;
			$concessions[]	= new Concession($parms, true);
	    }
	}

	$rowElt             = $template->getElementById('Row$LINE');
	$rowHtml            = $rowElt->outerHTML();
	$data               = '';
	$line               = 1;
	foreach($concessions as $concession)
	{
	    $rtemplate      = new \Templating\Template($rowHtml);
	    $rtemplate->set('LINE',     $line);
	    $rtemplate->set('CONID',    $concession->get('conid'));
	    $rtemplate->set('ORDER',	$concession->get('order'));
	    $rtemplate->set('FIRSTLOT',	$concession->get('firstlot'));
	    $rtemplate->set('LASTLOT',	$concession->get('lastlot'));
	    $rtemplate->set('LATITUDE',	$concession->get('latitude'));
	    $rtemplate->set('LONGITUDE',$concession->get('longitude'));
	    $rtemplate->set('LATBYLOT',	$concession->get('latbylot'));
	    $rtemplate->set('LONGBYLOT',$concession->get('longbylot'));
	    $data           .= $rtemplate->compile();
	    $line++;
	}
	$rowElt->update($data);     // replace row template with data
}			        // no errors
else
{
    $template['concessionForm']->update(null);
}

$template->display();
