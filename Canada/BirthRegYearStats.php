<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
use \Templating\Template;

/************************************************************************
 *  BirthRegYearStats.php												*
 *																		*
 *  Display statistics about the transcription of birth registrations.	*
 *																		*
 *  Parameters:															*
 *		regdomain		domain											*
 *		regyear			registration year								*
 *		county			county code within domain (optional)			*
 *																		*
 *  History:															*
 *		2011/01/09		created											*
 *		2011/11/05		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *						change name of help page						*
 *		2012/06/23		add support for linking statistics				*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/11/16		handle lack of database server connection		*
 *						gracefully										*
 *						clean up parameter handling						*
 *						support RegDomain parameter						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *		2014/01/14		move function pctClass to common.inc			*
 *						improve parameter handling						*
 *						use County class to expand county name			*
 *		2014/12/29		move to folder Canada							*
 *						support all provinces							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/11/02		add information on lowest and highest regnum	*
 *						and percentage transcribed to display			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *						common trace was discarded						*
 *		2016/04/25		replace ereg with preg_match					*
 *						support reporting single county					*
 *						support county level summary					*
 *		2016/05/06		%done in summary column was wrong				*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/09/01		do not include delayed registrations in stats	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/01		use Birth::getYearStatistics					*
 *		2017/10/16		use BirthSet									*
 *		2017/10/30		use composite cell style classes				*
 *		2018/10/06      use class Template                              *
 *		2019/05/29      do not number_format registration numbers       *
 *		2019/06/17      ignore late registrations in calculating        *
 *		                highest registration number                     *
 *      2020/01/22      use NumberFormatter                             *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Birth.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/BirthSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$regYear		    = '';
$domain	    	    = 'CAON';	// default domain
$domainName		    = 'Canada: Ontario';
$stateName		    = 'Ontario';
$cc                 = 'CA';
$countryName	    = 'Canada';
$county		        = null;
$countyName		    = null;
$lang               = 'en';
$showTownship	    = false;
$getParms		    = array();

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
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
		    case 'regyear':
		    {
				$regYear		= $value;
				$getParms['year']	= $regYear;
				if (!ctype_digit($regYear) ||
				    ($regYear < 1860) || ($regYear > 2000))
				{
				    $msg	.=
				"RegYear $regYear must be a number between 1860 and 2000. ";
				}
				break;
		    }		// RegYear passed
	
		    case 'regdomain':
		    case 'domain':
		    {
	            $domain         	= $value;
	            $cc                 = substr($domain, 0, 2);
	            $getParms['domain']	= $value;
				break;
		    }		// RegDomain
	
		    case 'code':
		    {
	            $domain         	= 'CA' . $value;
	            $cc                 = 'CA';
	            $getParms['domain']	= $domain;
				break;
		    }		// code
	
		    case 'lang':
		    {
	            $lang       = FtTemplate::validateLang($value);
				break;
		    }		// lang
	
		    case 'county':
		    {
				if (strlen($value) > 0)
                {
	                $county		        = $value;
                }
				break;
		    }		// county
	
		    case 'debug':
		    {
				break;
		    }		// allow debug output
	
		    default:
		    {
				$warn	.= "Unexpected parameter $key='$value'. ";
				break;
		    }		// any other paramters
		}		    // process specific named parameters
	}			    // loop through all input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get

$template		= new FtTemplate("BirthRegYearStats$lang.html");

// interpret country code
$countryObj	    = new Country(array('cc' 	=> $cc));
$countryName    = $countryObj->getName();

// interpret domain code
$domainObj	    = new Domain(array('domain' 	=> $domain,
				    			   'language'	=> 'en'));
$domainName	    = $domainObj->getName(1);
$stateName	    = $domainObj->getName(0);
if (!$domainObj->isExisting())
{
    $msg	    .= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
}

if ($regYear == '')
{
	$msg		.= "RegYear omitted. ";
}

// validate county code
if ($county)
{
    $getParms['county']	= $county;
    $countyObj		    = new County($domain, $county);
	if ($countyObj->isExisting())
	{
	    $countyName		= $countyObj->get('name');
	    $showTownship	= true;
	}
	else
	{
	    $msg	.= "County code '$value' is not valid for domain '$domain'. ";
    }

    $template->set('COUNTY',		    $county);
    $template->set('COUNTYNAME',		$countyName);
}
else
{
    $template->set('COUNTY',            '');
    $template->set('COUNTYNAME',		'All');
    $template->updateTag('countyName',  null);
	$template->updateTag('countyStatusLink', null);
}

$template->set('CC',        		$cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	    	$domain);
$template->set('STATENAME',	    	$stateName);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DEBUG',		        $debug?'Y':'N');

if (strlen($msg) == 0)
{			// no errors
    // get the statistics
    $births		    = new BirthSet($getParms);

	if ($county)
	    $result	    = $births->getCountyStatistics();
	else
        $result	    = $births->getStatistics();
}		// no errors
else
{
    $result         = array();
}

if (!$showTownship)
    $template->updateTag('TownshipTH', null);

$dataRow            = $template->getElementById('dataRow');
$yearHTML           = $dataRow->outerHTML();

$total	        	= 0;
$totalLinked	    = 0;
$rownum		        = 0;
$countyObj		    = null;
$countyName		    = '';
$lowest		        = PHP_INT_MAX;
$highest		    = 0;
$data               = '';
$formatter          = $template->getFormatter();

foreach($result as $row)
{
    $ttemplate      = new Template($yearHTML);
	$rownum++;
	$county		    = $row['county'];
	try {
	    if (is_null($countyObj) ||
		$county != $countyObj->get('code'))
	    {		// new county code
		    $countyObj	= new County($domain, $county);
		    $countyName	= $countyObj->get('name');
	    }		// 
	} catch (Exception $e)
	{
	    if ($debug)
		$warn	.= "<p class='message'>" . $e->getMessage() .
			"</p>\n";
	    $countyName		= $county;
	}
	if (array_key_exists('township', $row))
         $township	    = $row['township'];
    else
         $township      = '&nbsp;';
	$count		        = $row['count'];
	$total		        += $count;
	$linked		        = $row['linkcount'];
	if ($count == 0)
	    $pctLinked	    = 0;
	else
	    $pctLinked  	= 100 * $linked / $count;
	$totalLinked	    += $linked;
	$low		        = $row['low'];
    $high		        = $row['high'];
    if (array_key_exists('currhigh', $row))
	    $currhigh		= $row['currhigh'];
    else
        $currhigh		= $high;

	if ($low < $lowest)
	    $lowest	        = $low;
	if ($high < ($low + 400000) && $high > $highest)
	    $highest	    = $high;
    $todo		        = $currhigh - $low + 1;
    if ($todo < $count)
        $todo           = $count;
	if ($todo == 0)
	    $pctDone	    = 0;
	else
        $pctDone	    = 100 * $count / $todo;

    $ttemplate->set('ROWNUM',       $rownum);
    $ttemplate->set('COUNTY',       $county);
    $ttemplate->set('COUNTYNAME',   $countyName);
    $ttemplate->set('TOWNSHIP',     $township);
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    $ttemplate->set('COUNT',        $formatter->format($count));
    $ttemplate->set('LOW',          $low);
    $ttemplate->set('HIGH',         $high);
    $ttemplate->set('LINKED',       $linked);
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
    $ttemplate->set('PCTDONE',      $formatter->format($pctDone));
    $ttemplate->set('PCTDONECLASS', pctClass($pctDone));
    $ttemplate->set('PCTLINKED',    $formatter->format($pctLinked));
    $ttemplate->set('PCTLINKEDCLASS', pctClass($pctLinked));
    if (!$showTownship)
        $ttemplate->updateTag('townshipCol', null);
    $data           .= $ttemplate->compile();
}	            	// process all rows

$dataRow->update($data);
	    
if ($total == 0)
{
	$pctDone	= 0;
	$pctLinked	= 0;
}
else
{
	$pctDone	= 100 * $total / ($highest - $lowest + 1);
	$pctLinked	= 100 * $totalLinked / $total;
}
$template->set('REGYEAR',           $regYear);
$template->set('REGYEARP',          $regYear - 1);
$template->set('REGYEARN',          $regYear + 1);
$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
$template->set('TOTAL',             $formatter->format($total));
$template->set('LOWEST',            $lowest);
$template->set('HIGHEST',           $highest);
$template->set('TOTALLINKED',       $formatter->format($totalLinked));
$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
$template->set('PCTDONE',           $formatter->format($pctDone));
$template->set('PCTDONECLASS',      pctClass($pctDone));
$template->set('PCTLINKED',         $formatter->format($pctLinked));
$template->set('PCTLINKEDCLASS',    pctClass($pctLinked));
if (!$showTownship)
    $template->updateTag('CountyCol', null);

$template->display();
