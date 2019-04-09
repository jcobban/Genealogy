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
 *		2018/11/11		get error message texts frin template           *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/Concession.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$cc	    	    		= 'CA';   	    // country code
$countryName			= 'Canada';
$domain	    			= 'CAON';   	// administrative domain
$domainName				= 'Ontario';
$county		    		= null;		    // abbreviation
$countyName				= "Unknown";	// full name
$township				= null;		    // abbreviation
$townshipName			= "Unknown";	// full name
$concession				= null;		    // concession name
$concessionName			= "Unknown";	// full name
$lang           		= 'en';
$getParms				= array();

// get parameter values
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{				// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{			// act on specific keys
	    case 'cc':
	    case 'country':
        {
            if (strlen($value) == 2)
			    $cc	        	= strtoupper($value);
			break;
	    }

	    case 'domain':
	    {
			$domain	        	= strtoupper($value);
			break;
	    }

	    case 'county':
	    {
			$county		    	= $value;
			break;
	    }

	    case 'township':
	    {
			$township			= $value;
			break;
	    }

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
            break;
        }
	}			// act on specific keys
}				// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (canUser('edit'))
	$action			= 'Update';
else
	$action			= 'Display';

$template			= new FtTemplate("ConcessionsEdit$action$lang.html");

$template->set('CONTACTTABLE',	'Concessions');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',          $lang);
//$template->set('OFFSET',        $offset);
//$template->set('LIMIT',         $limit);
$template->set('TOTALROWS',     $count);
//$template->set('FIRST',         $offset + 1);
//$template->set('LAST',          min($count, $offset + $limit));

// validate Domain
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
    $textElt     = $template->getElementById('unknownDomain');
    $text       = $textElt->innerHTML();
    $msg       .= str_replace('$value', $domain, $text);
    $getParms['domain']	= "^$domain$";
}

if (is_string($county))
{
	$countyObj      	= new County(array('domain'	=> $domainObj, 
					                       'county'	=> $county));
    $countyName         = $countyObj->getName();
    if ($countyObj->isExisting())
        $getParms['county']	= $countyObj;
    else
    {
        $textElt    = $template->getElementById('unknownCounty');
        $text       = $textElt->innerHTML();
        $msg       .= str_replace(array('$county', '$domain'), 
                                        array($county, $domainName),
                                        $text);
        $getParms['county']	= "^$county$";
    }
}
else
{
    $textElt    = $template->getElementById('missingCounty');
    $msg        .= $textElt->innerHTML();
}

if (is_string($township))
{
	$townshipObj        = new Township(array('domain'	=> $domainObj, 
						                     'county'	=> $countyObj,
						                     'code'	    => $township));
	$townshipName	    = $townshipObj->getName();
    if ($townshipObj->isExisting())
	    $getParms['township']	= $townshipObj;
    else
    {
        $textElt    = $template->getElementById('unknownTownship');
        $text       = $textElt->innerHTML();
        $msg        .= str_replace(array('$township', '$county'),
                                   array($township, $county),
                                   $text);
	    $getParms['township']	= "^$township$";
    }
}
else
{
    $textElt        = $template->getElementById('missingTownship');
    $msg            .= $textElt->innerHTML();
}

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
}			        // no errors
else
    $concessions            = array();

$template->set('CC',	    	$cc);
$template->set('COUNTRYNAME',	$countryName);
$template->set('DOMAIN',		$domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('COUNTYCODE',	$county);
$template->set('COUNTY',	    $county);
$template->set('COUNTYNAME',	$countyName);
$template->set('TOWNSHIP',	    $townshipName);
$template->set('TOWNSHIPNAME',	$townshipName);

$rowElt             = $template->getElementById('Row$LINE');
$rowHtml            = $rowElt->outerHTML();
$data               = '';
$line               = 1;
foreach($concessions as $concession)
{
    $rtemplate      = new Template($rowHtml);
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

$template->display();
