<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegYearStats.php												*
 *																		*
 *  Display statistics about the transcription of birth registrations.	*
 *																		*
 *  Parameters:															*
 *		regyear			registration year								*
 *																		*
 *  History:															*
 *		2011/03/16		created											*
 *		2011/11/05		use <button> instead of <a> for view action		*
 *						support mouseover help							*
 *						change name of help page						*
 *		2012/06/23		add support for linking statistics				*
 *		2013/08/04		use pageTop and pageBot to standardize appearance*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *		2014/01/14		move pctClass function to common.inc			*
 *						improve parameter handling						*
 *						add support for regDomain parameter				*
 *						use County class to expand county name			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/10/26		add information on lowest and highest regnum	*
 *						and percentage transcribed to display			*
 *		2016/04/25		replace ereg with preg_match					*
 *						support reporting single county					*
 *						support county level summary					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/07		use class Country								*
 *		2017/09/12		use get( and set(								*
 *		2017/10/30		use composite cell style classes				*
 *		2018/06/01		add support for lang parameter					*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/03/19      use Template                                    *
 *		2019/06/23      add column currhigh which excludes delayed      *
 *		2019/07/13      reduce invalid county code message to warning   *
 *		                so it can be corrected                          *
 *		2019/12/13      remove D_ prefix from field names               *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/DeathSet.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$regYear						= '';
$cc			    				= 'CA`';
$country    					= null;     // instance of Country
$countryName					= 'Canada';
$domain		    				= 'CAON';	// default domain code
$domainName						= 'Ontario';
$domainObj 		    			= null;     // instance of Domain
$county 		    			= null;     // instance of County
$countyCode		    			= '';
$countyName		    			= '';
$lang		    				= 'en';

if (count($_GET) > 0)
{                   // parameters passed
	$parmsText      		    = "<p class='label'>\$_GET</p>\n" .
	                                "<table class='summary'>\n" .
	                                "<tr><th class='colhead'>key</th>" .
	                                "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			    // loop through all input parameters
	    $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
    $value                      = trim($value);
		switch(strtolower($key))
		{		    // process specific named parameters
		    case 'regyear':
		    case 'year':
		    {
				$regYear	        = $value;
				break;
		    }		// RegYear passed
	
		    case 'regdomain':
		    case 'domain':
		    {
				$domain		        = $value;
				break;
		    }		// RegDomain
	
		    case 'county':
		    case 'regcounty':
		    {
				if (strlen($value) > 0)
				    $countyCode     = $value;
				break;
		    }		// county
	
		    case 'lang':
		    {
				$lang		        = FtTemplate::validateLang($value);
				break;
		    }		//lang
	
		    case 'debug':
		    {
				break;
		    }
	
		    default:
		    {
				$warn       .= "<p>Unexpected parameter $key='$value'.</p>\n";
				break;
		    }		// any other paramters
		}		    // process specific named parameters
	}			    // loop through all input parameters
	if ($debug)
	    $warn                   .= $parmsText . "</table>\n";
}                   // parameters passed

// create template
if (strlen($countyCode) > 0)
$template           = new FtTemplate("DeathRegYearStatsTown$lang.html");
else
$template           = new FtTemplate("DeathRegYearStats$lang.html");

// validate parameters
$domainObj	            = new Domain(array('domain'	    => $domain,
   					            	   'language'	=> 'en'));
if ($domainObj->isExisting())
{
$cc			        = substr($domain, 0, 2);
$country    		= new Country(array('code' => $cc));
$countryName	    = $country->getName();
}
else
{
$msg	.= "Domain '$value' must be a supported two character country code followed by a two or three character state or province code. ";
}
$domainName		        = $domainObj->get('name');

if (strlen($countyCode) > 0)
{
	$county 		    = new County(array('domain'     => $domainObj, 
	                                       'code'       => $countyCode));
	if ($county->isExisting())
	{
	}
	else
	{
    $warn	        .= "<p>County code '$countyCode' is not valid for domain '$domain'.</p>\n";
}
	$countyName		    = $county->get('name');
}

if ($regYear == '')
{
	$msg		.= "RegYear omitted. ";
}
else
{
	if (!preg_match("/^([0-9]{4})$/", $regYear) ||
	    ($regYear < 1800) || ($regYear > 2000))
	{
	    $msg	.= "RegYear $regYear must be a number between 1800 and 2000. ";
}
}

// update template
$template->set('CC',            $cc);
$template->set('COUNTRY',       $countryName);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('COUNTY',        $countyCode);
$template->set('COUNTYNAME',    $countyName);
$template->set('REGYEAR',       $regYear);
$template->set('PREVREGYEAR',   $regYear - 1);
$template->set('NEXTREGYEAR',   $regYear + 1);

$total                              = 0;
$lowest                             = PHP_INT_MAX;
$highest                            = 0;
$totcount                           = 0;
$totlinked                          = 0;

if (strlen($msg) == 0)
{			                // no errors
	if (is_null($county))
	{
	    $deaths         = new DeathSet(array('domain'       => $domain,
	                                         'regyear'      => $regYear));
	    $result         = $deaths->getStatistics();
	}
	else
	{
	    $deaths         = new DeathSet(array('domain'       => $domain,
	                                         'regyear'      => $regYear,
	                                         'county'       => $countyCode));
	    $result         = $deaths->getCountyStatistics();
	}

	//
    for($i = 0; $i < count($result); $i++)
    {                       // loop through rows
        $row                            = $result[$i];
        $result[$i]['rownum']           = $i;
        if (is_null($county))
        {
	        $tcounty   = new County(array('domain'     => $domainObj, 
                                          'code'       => $row['county']));
            $result[$i]['countyname']   = $tcounty->get('name');
        }
        else
            $result[$i]['countyname']   = $countyName;
        $low                            = $row['low'];
        $high                           = $row['high'];
        if (array_key_exists('currhigh', $row))
            $currhigh                   = $row['currhigh'];
        else
            $currhigh                   = $high;
        $count                          = $currhigh - $low + 1;
        if ($currhigh > $highest)
            $highest                    = $currhigh;
        if ($low < $lowest)
            $lowest                     = $low;
        $surnamecount                   = $row['count'];
        $totcount                       += $surnamecount;
        $pctdone                        = ($surnamecount * 100.0) / $count;
        if ($pctdone > 100.0)
            $pctdone                    = 100.0;
        $pctdoneclass                   = pctClass($pctdone);
        $linkcount                      = $row['linkcount'];
        $totlinked                      += $linkcount;
        $pctlinked                      = ($linkcount * 100.0)/ $count;
        if ($pctlinked > 100.0)
            $pctlinked                  = 100.0;
        $pctlinkedclass                 = pctClass($pctlinked);
        $result[$i]['pctdone']          = number_format($pctdone,2);
        $result[$i]['pctdoneclass']     = $pctdoneclass;
        $result[$i]['pctlinked']        = number_format($pctlinked,2);
        $result[$i]['pctlinkedclass']   = $pctlinkedclass;
    }                       // loop through rows
}		        // ok
else
{
    $result                             = array();
    $lowest                             = 0;
}

if (count($result) > 0)
    $template['stats$rownum']->update($result);
else
    $template['form']->update(null);

$total                              = $highest - $lowest + 1;
$template->set('TOTAL',             $totcount);
$template->set('LOWEST',            $lowest);
$template->set('HIGHEST',           $highest);
$template->set('PCTDONE',           number_format(($totcount * 100.0) / $total,2));
$template->set('PCTDONECLASS',      pctClass(($totcount * 100.0) / $total));
$template->set('PCTLINKED',         number_format(($totlinked* 100.0) / $total,2));
$template->set('PCTLINKEDCLASS',    pctClass(($totlinked * 100.0) / $total));

$template->display();
