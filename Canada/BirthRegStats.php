<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  BirthRegStats.php													*
 *																		*
 *  Display statistics about the transcription of birth registrations.	*
 *																		*
 *  Parameters:															*
 *		RegDomain	domain consisting of country code and state	    	*
 *		lang        language code, default 'en'                         *
 *																		*
 *  History:															*
 *		2011/01/09  	created											*
 *		2011/03/16  	display in 3 columns							*
 *		2011/08/10  	add help										*
 *		2011/10/27  	use <button> instead of <a> for view action		*
 *				    	support mouseover help							*
 *		2013/04/13  	use functions pageTop and pageBot to standardize*
 *		2013/11/16  	handle lack of database server connection		*
 *				    	gracefully										*
 *				    	clean up parameter handling						*
 *				    	support RegDomain parameter						*
 *		2013/12/07  	$msg and $debug initialized by common.inc		*
 *		2013/12/24  	use CSS for layout instead of tables			*
 *		2014/01/13  	use CSS for table style							*
 *		2015/07/02  	access PHP includes using include_path			*
 *		2015/09/28  	migrate from MDB2 to PDO						*
 *		2016/01/19  	add id to debug trace							*
 *				    	include http.js before util.js					*
 *				    	rejected debug parameter						*
 *		2016/05/20  	use class Domain to validate domain code		*
 *		2017/01/02  	add linked column								*
 *		2017/02/07  	use class Country								*
 *		2017/10/01  	use Birth::getStatistics						*
 *		2017/10/01  	use BirthSet->getSummary						*
 *		2018/01/02  	use split class names							*
 *		2018/10/06      use class Template                              *
 *		2019/02/21      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/01/22      use NumberFormatter                             *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2020/11/28      correct XSS error                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/BirthSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$domain	    			= 'CAON';	// default domain
$cc			    		= 'CA';		// default country code
$code		    		= 'ON';		// default province code
$countryName			= 'Canada';	// default country name
$domainName				= 'Canada: Ontario';
$stateName				= 'Ontario';
$lang	    			= 'en';

if (isset($_GET) && count($_GET) > 0)
{			            // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {				    // loop through all input parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
		switch(strtolower($key))
		{			    // process specific named parameters
		    case 'regdomain':
		    case 'domain':
		    {
				$domain		= strtoupper($value);
				break;
		    }			// RegDomain
	
		    case 'code':
		    {           // province code within Canada
	            $code		= strtoupper($value);
	            $domain     = 'CA' . $code;
				break;
	        }			// province code
	
		    case 'lang':
	        {
	            $lang       = FtTemplate::validateLang($value);
				break;
	        }			// lang
	
		    case 'debug':
		    {			// handled by common code
				break;
		    }			// handled by common code 
	
		    default:
		    {			// any other parameters
				$warn	.= "<p>Unexpected parameter $key='" .
                                htmlspecialchars($value) . "'.</p>";
				break;
		    }			// any other parameters
		}			    // process specific named parameters
	}				    // loop through all input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			            // invoked by method=get

$template		    = new FtTemplate("BirthRegStats$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'BirthRegStats'));

$domainObj	        = new Domain(array('domain' 	=> $domain,
				        			   'language'	=> 'en'));
$domainName	        = $domainObj->getName(1);
$stateName	        = $domainObj->getName(0);
$cc	    		    = substr($domain, 0, 2);
$code	    	    = substr($domain, 2, 2);
$countryObj		    = new Country(array('code' => $cc));
$countryName    	= $countryObj->getName();

$template->set('CC',        		$cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	    	$domain);
$template->set('STATENAME',	    	$stateName);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
if ($debug)
    $template->set('DEBUG',		    'Y');
else
    $template->set('DEBUG',		    'N');

$thRow              = $template->getElementById('thRow');
$numCols            = (int)((count($thRow->children) - 2) / 4);
$dataRow            = $template->getElementById('dataRow');

$births	        	= new BirthSet(array('domain' => $domain));
$result		        = $births->getSummary();
$yearHTML           = $dataRow->innerHTML();
$col                = 0;
$data               = '';
$total              = 0;
$totalLinked        = 0;
$yearClass          = 'odd';
$formatter          = $template->getFormatter();

foreach($result as $i => $row)
{                       // loop through each year of statistics
    if ($col == 0)
        $data       .= "    <tr>\n";
    $ttemplate      = new Template($yearHTML);
    $regYear        = $row[0];
    $count          = $row[1];
    $linked         = $row[2];
    if ($count == 0)
        $pctLinked	= 0;
    else
        $pctLinked	= 100 * $linked / $count;
    $total          += $count;
    $totalLinked    += $linked;
    $ttemplate->set('DOMAIN', $domain);
    $ttemplate->set('REGYEAR', $regYear);
    $ttemplate->set('YEARCLASS', $yearClass . ' right');
    $ttemplate->set('COUNT', $formatter->format($count));
    $ttemplate->set('LINKED', $formatter->format($linked));
    $ttemplate->set('PCTLINKED', pctClass($pctLinked));
    $data           .= $ttemplate->compile();
    $col++;
    if ($col >= $numCols)
    {
        $data       .= "    </tr>\n";
        $col        = 0;
        if ($yearClass == 'odd')
            $yearClass          = 'even';
        else
            $yearClass          = 'odd';
    }
    else
        $data       .= "    <td></td>\n";
}                       // loop through each year of statistics
$dataRow->update($data);
$template->set('TOTAL',         $formatter->format($total));
$template->set('TOTALLINKED',   $formatter->format($totalLinked));
$template->display();
