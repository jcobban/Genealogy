<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  CountyMarriageVolumeSummary.php										*
 *																		*
 *  Display information about those volumes of pre-confederation		*
 *  marriages that have been partially transcribed.						*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain	2 letter country code + 2 letter state/province code	*
 *		        CAUC        Upper Canada District Marriages             *
 *		        CACW        Canada West County Marriages                *
 *		lang    language of communication                               *
 *																		*
 *  History:															*
 *		2017/07/15		created											*
 *		2017/07/18		use Canada West instead of Ontario				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2020/01/26      use Template and NumberFormatter                *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/CountyMarriageSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

// defaults
$domain		    			= 'CACW';
$prov		    			= 'CW';
$province					= 'Canada West';
$cc			    			= 'CA';
$countryName				= 'Canada';
$lang                       = 'en';
$volume		    			= null;
$offset		    			= 0;
$offsetText                 = null;
$limit		    			= 20;
$limitText                  = null;

// validate parameters
if (isset($_GET) && count($_GET) > 0)
{                   // invoke by method=get
	$parmsText      = "<p class='label'>\$_GET</p>\n" .
	                        "<table class='summary'>\n" .
	                        "<tr><th class='colhead'>key</th>" .
	                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        $value              = trim($value);
		switch(strtolower($key))
		{		    // check supported parameters
		    case 'prov':
		    {
				$prov		        = $value;
                $domain		        = 'CA' . $value;
                $cc                 = 'CA';
				break;
		    }		// state/province code

		    case 'domain':
		    case 'regdomain':
		    {
				$domain		        = $value;
				$cc		            = substr($value, 0, 2);
				$prov		        = substr($value, 2, 2);
				break;
		    }		// state/province code

            case 'offset':
            {
                if (strlen($value) > 0)
                {
                    if (ctype_digit($value))
                        $offset     = intval($value);
                    else
                        $offsetText = $value;
                }
                break;
            }       // starting offset

            case 'limit':
            {
                if (strlen($value) > 0)
                {
                    if (ctype_digit($value))
                        $limit      = intval($value);
                    else
                        $limitText  = $value;
                }
                break;
            }       // starting limit

		    case 'lang':
            {
                $lant       = FtTemplate::validateLang($value);
				break;
		    }		// preferred lang

		    case 'debug':
		    {
				break;
		    }		// debug handled by common code

		    default:
		    {
				if (strlen($value) > 0)
				    $warn	.= "Unexpected parameter $key='$value'. ";
				break;
		    }
		}		    // check supported parameters
	}		        // loop through all parameters
	if ($debug)
	    $warn               .= $parmsText . "</table>\n";
}                   // invoke by method=get

if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';
if ($prov == 'UC')
    $by                     = 'District';
else
    $by                     = 'County';

$template                   = new FtTemplate("{$by}MarriageVolumeSummary$action$lang.html");
$formatter                  = $template->getFormatter();

// validate Domain code
$domainObj	                = new Domain(array('domain'	    => $domain,
				                        	   'language'	=> $lang));
if (!$domainObj->isExisting())
{
    $text                   = $template['invalidDomain']->innerHTML;
    $msg	                .= str_replace('$domain', $domain, $text);
}
$province	                = $domainObj->get('name');
$countryObj	                = $domainObj->getCountry();
$countryName	            = $countryObj->getName();

// check for errors in offset and limit
if (is_string($offsetText))
{
    $text                   = $template['invalidOffset']->innerHTML;
    $msg	                .= str_replace('$offset', $offsetText, $text);
}
if (is_string($limitText))
{
    $text                   = $template['invalidLimit']->innerHTML;
    $msg	                .= str_replace('$limit', $limitText, $text);
}

// expand the template
if (strlen($msg) == 0)
{		            // no errors detected
    // execute the query to get the contents of the page
    $marriages              = new CountyMarriageSet(array('domain' => $domain));
	$results	            = $marriages->getVolumeSummary();
    $totalTranscribed	    = 0;
    $totalLinked	        = 0;
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    if ($results && count($results) > 0)
    {               // have a response
		foreach($results as $i => $report)
		{		    // process all rows
		    $volume		        = $report['volume'];
            $transcribed	    = floor($report['number']);
	        $totalTranscribed	+= $transcribed;
            $linked	            = floor($report['linked']);
	        $totalLinked	    += $linked;
            $formatted          = $formatter->format($transcribed);
            $results[$i]['number']   = $formatted;
            $formatted          = $formatter->format($linked);
            $results[$i]['linked']   = $formatted;
            if ($linked > 0)
                $pctlinked      = 100 * $linked/ $transcribed;
            else
                $pctlinked      = 0;
            $results[$i]['pctclasslinked'] = pctClass($pctlinked);
        }		    // process all rows
        $template['Row$volume']->update($results);
        $template->set('TOTALTRANSCRIBED',  $formatter->format($totalTranscribed));
        $template->set('TOTALLINKED',  $formatter->format($totalLinked));
        if ($totalTranscribed > 0)
            $pctlinked          = 100 * $totalLinked/ $totalTranscribed;
        else
            $pctlinked          = 0;
        $template->set('PCTCLASSLINKED',   pctClass($pctlinked));
    }               // have a response
    else
    {               // no rows
        $template['dataTable']->update(null);
    }               // no rows
}		            // do search
else
{		            // error
    $template['dataTable']->update(null);
}		            // error
$template->set('DOMAIN',			    $domain);
$template->set('PROVINCE',			    $province);
$template->set('DOMAINNAME',			$province);
$template->set('COUNTRYNAME',			$countryName);

$template->display();
