<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegStats.php												*
 *																		*
 *  Display the status of the transcription of marriage registrations.	*
 *																		*
 *  Parameters:															*
 *		RegDomain		domain consisting of country code and state		*
 *		columns         number of columns to display                    *
 *																		*
 *  History:															*
 *		2011/01/09		created											*
 *		2011/03/14		display breakdown by township					*
 *		2011/03/16		put Help URL in standard location				*
 *						include rightTop button							*
 *						display in 3 columns							*
 *		2011/10/27		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/13		use CSS for table style							*
 *		2014/03/06		link to help misplaced on page					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2018/01/01		add language parameter							*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/07/08      use Template                                    *
 *		2019/12/14      use class MarriageSet                           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/MarriageSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$domain		    = 'CAON';	// default domain
$cc			    = 'CA';
$countryName	= 'Canada';
$domainName		= 'Canada: Ontario:';
$stateName		= 'Ontario';
$columns        = 3;
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			    // loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		    // process specific named parameters
	    case 'domain':
	    case 'regdomain':
	    {
			$domain		    = $value;
			$cc		        = strtoupper(substr($domain, 0, 2));
			break;
	    }		// RegDomain

        case 'columns':
        {
            if (ctype_digit($value) && $value > 0 && $value < 10)
                $columns    = intval($value);
            break;
        }

	    case 'lang':
	    {
			$lang	        = FtTemplate::validateLang($value);    	
			break;
	    }		// any other paramters

	    case 'debug':
	    {
			break;
	    }		// handled by common code

	    default:
	    {
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		    // process specific named parameters
}			    // loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

$template       = new FtTemplate("MarriageRegStats$lang.html");

$country	    = new Country(array('code'	    => $cc));
$countryName	= $country->getName($lang);
$domainObj	    = new Domain(array('domain'	    => $domain,
				            	   'language'	=> $lang));
if (!$domainObj->isExisting())
{
    $msg	        .= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
	$domain		    = 'CAON';	    // restore defaults
    $domainObj	    = new Domain(array('domain'	    => $domain,
	    			            	   'language'	=> $lang));
	$cc			    = 'CA';
	$countryName	= 'Canada';
}

$domainName	    = $domainObj->getName(1);
$stateName	    = $domainObj->getName(0);

$marriages      = new MarriageSet(array('domain' => $domain));
$result         = $marriages->getSummary();

// get sub-templates
$colHeaders             = $template['columnHeaders'];
$headerHtml             = $colHeaders->innerHTML();
$colData                = "\t\t  <tr>\n";
$spacer                 ='';
for ($i = $columns; $i; $i--)
{
    $colData            .= $spacer . $headerHtml;
    $spacer             = "\t\t\t<th>&nbsp;</th>\n";
}
$colHeaders->update($colData . "\t\t  </tr>\n");

// add display of data
$rowElement             = $template['row$REGYEAR'];
$rowHtml                = $rowElement->innerHTML();
$rowData                = "";
$total		            = 0;
$yearClass              = 'odd';
$row                    = reset($result);
while($row)
{               // continue until finished
    $rowData                .= "\n\t\t  <tr>\n";
    $spacer                 = '';
	for ($i = $columns; $i && $row; $i--)
	{
        $rtemplate          = new \Templating\Template($rowHtml);
	    $rtemplate->set('YEARCLASS',    $yearClass);
		$regYear	        = $row[0];
		$count		        = $row[1];
		$linked		        = $row[2];
	    $rtemplate->set('REGYEAR',      $regYear);
	    $rtemplate->set('DONE',         number_format($count));
        $rtemplate->set('LINKED',       number_format($linked));
        $pctLinked          = 100 * $linked / $count;
        $rtemplate->set('PCTLINKED', pctClass($pctLinked));
	    $rowData            .= $spacer . $rtemplate->compile();
        $spacer             = "\n\t\t\t<td>&nbsp;</td>\n";
		$total		        += $count;
        $row                = next($result);
    }
    $rowData                .= "\n\t\t  </tr>\n";
    if ($yearClass == 'odd')
        $yearClass          = 'even';
    else
        $yearClass          = 'odd';
}               // continue until finished
$rowElement->update($rowData);

$template->set('TOTAL',         number_format($total));
$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('STATENAME',     $stateName);

$template->display();
