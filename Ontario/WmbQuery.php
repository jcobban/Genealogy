<?php
namespace Genealogy;
use \PDO;
use \Exception;

/************************************************************************
 *  WmbQuery.php														*
 *																		*
 *  Request a report of individuals whose Wesleyan Methodist Baptism	*
 *  matches the requested pattern.                                      *
 *																		*
 *  Parameters:															*
 *																		*
 *  History:															*
 *		2020/01/03		created											*
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

$getParms				= array();
$cc                     = 'CA';
$domain                 = 'CAON';
$district               = '';
$area                   = '';
$volume                 = '';
$page                   = '';
$lang           		= 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// first extract the values of all supplied parameters
$parmsText      = "<p class=\"label\">\$_GET</p>\n" .
                        "<table class=\"summary\">\n" .
                        "<tr><th class=\"colhead\">key</th>" .
                        "<th class=\"colhead\">value</th></tr>\n";
foreach ($_GET as $key => $value)
{			    // loop through all parameters
    $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$value</td></tr>\n"; 
    switch(strtolower($key))
    {		    // switch on parameter name
        case 'cc':
        {
            $cc		                = $value;
            break;
        }

        case 'domain':
        {
            $domain		            = $value;
            break;
        }

        case 'district':
        {
            $district		        = $value;
            break;
        }

        case 'area':
        {
            $area		            = $value;
            break;
        }

        case 'volume':
        {
            $volume		            = $value;
            break;
        }

        case 'page':
        {
            $page		            = $value;
            break;
        }

        case 'lang':
        {		// language requested
            $lang                   = FtTemplate::validateLang($value);
            break;
        }		// language requested

    }		    // switch on parameter name
}			    // loop through all parameters
if ($debug)
    $warn               .= $parmsText . "</table>\n";

$template               = new FtTemplate("WmbQuery$lang.html");
$template['otherStylesheets']->update(array('filename' => 'WmbQuery'));
$tranTab                = $template->getTranslate();
$template->set('CC',            $cc);
$template->set('DOMAIN',        $domain);
$template->set('DISTRICT',      $district);
$template->set('AREA',          $area);
$template->set('VOLUME',        $volume);
$template->set('PAGE',          $page);
$template->set('LANG',          $lang);

$template->display();
