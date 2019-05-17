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
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$regYear				= '';
$cc			    		= 'CA`';
$country    			= null;
$countryName			= 'Canada';
$domain		    		= 'CAON';	// default domain
$domainName				= 'Ontario';
$county 		    	= null;
$countyCode		    	= '';
$countyName		    	= '';
$lang		    		= 'en';

if (count($_GET) > 0)
{                   // parameters passed
	$parmsText      		= "<p class='label'>\$_GET</p>\n" .
	                            "<table class='summary'>\n" .
	                            "<tr><th class='colhead'>key</th>" .
	                            "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			    // loop through all input parameters
	    $parmsText                  .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n"; 
        $value                      = trim($value);
		switch(strtolower($key))
		{		    // process specific named parameters
		    case 'regyear':
		    {
				$regYear	        = $value;
				break;
		    }		// RegYear passed
	
		    case 'regdomain':
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
				if (strlen($value) >= 2)
				    $lang		    = strtolower($value);
				break;
		    }		//lang
	
		    case 'debug':
		    {
				break;
		    }
	
		    default:
		    {
				$msg	            .= "Unexpected parameter $key='$value'. ";
				break;
		    }		// any other paramters
		}		    // process specific named parameters
	}			    // loop through all input parameters
	if ($debug)
	    $warn       .= $parmsText . "</table>\n";
}                   // parameters passed

// create template
if (strlen($countyCode) > 0)
    $template           = new FtTemplate("DeathRegYearStatsTown$lang.html");
else
    $template           = new FtTemplate("DeathRegYearStats$lang.html");

// validate parameters
$domainObj	= new Domain(array('domain'	    => $domain,
       						   'language'	=> 'en'));
if ($domainObj->isExisting())
{
    $cc			        = substr($domain, 0, 2);
    $country    		= new Country(array('code' => $cc));
    $countryName	    = $country->getName();
    $domainName		    = $domainObj->get('name');
}
else
{
    $msg	.= "Domain '$value' must be a supported two character country code followed by a two character state or province code. ";
    $domainName	= 'Domain : ' . $value;
}

if (strlen($countyCode) > 0)
{
	$county 		        = new County(array('domain'     => $domainObj, 
	                                           'code'       => $countyCode));
	if ($county->isExisting())
	{
	    $countyName		    = $county->get('name');
	}
	else
	{
	    $msg	.= "County code '$countyCode' is not valid for domain '$domain'. ";
    }
}

if ($regYear == '')
{
	$msg		.= "RegYear omitted. ";
}
else
{
	if (!preg_match("/^([0-9]{4})$/", $regYear) ||
	    ($regYear < 1860) || ($regYear > 2000))
	{
	    $msg	.= "RegYear $regYear must be a number between 1860 and 2000. ";
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
{			// no errors
	// execute the query
    if (is_null($county))
    {
	    $query	= "SELECT D_RegCounty, " .
							"SUM(D_Surname != '') AS SurnameCount,  " .
							"SUM(D_Idir != 0) AS LinkCount, " .
							"MIN(D_RegNum) as low, " .
							"MAX(D_RegNum) as high  " .
						"FROM Deaths " .
						"WHERE D_RegDomain=:domain AND D_RegYear=:regyear " .
						"GROUP BY D_RegCounty " .
                        "ORDER BY D_RegCounty";
        $sqlParms       = array('domain'        => $domain,
                                'regyear'       => $regYear);
    }
    else
    {
	    $query	= "SELECT D_RegCounty, D_RegTownship, " .
							"SUM(D_Surname != '') AS SurnameCount,  " .
							"SUM(D_Idir != 0) AS LinkCount, " .
							"MIN(D_RegNum) as low, " .
							"MAX(D_RegNum) as high  " .
						"FROM Deaths " .
						"WHERE D_RegDomain=:domain AND D_RegYear=:regyear " .
							"AND D_RegCounty=:county " .
						"GROUP BY D_RegCounty, D_RegTownship " .
                        "ORDER BY D_RegCounty, D_RegTownship";
        $sqlParms       = array('domain'        => $domain,
                                'regyear'       => $regYear,
                                'county'        => $countyCode);
    }
    $stmt	 	    = $connection->prepare($query);
    $queryText      = debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		    // successful query
        $result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
        for($i = 0; $i < count($result); $i++)
        {
            $result[$i]['rownum']           = $i;
            if (is_null($county))
            {
	            $tcounty   = new County(array('domain'     => $domainObj, 
                    'code'       => $result[$i]['d_regcounty']));
                $result[$i]['countyname']   = $tcounty->get('name');
            }
            else
                $result[$i]['countyname']   = $countyName;
            $high                           = $result[$i]['high'];
            $low                            = $result[$i]['low'];
            $count                          = $high - $low + 1;
            if ($high > $highest &&
                ($highest == 0 || $high < ($highest + 2000) || $high < ($low + 2000)))
                $highest                    = $high;
            if ($low < $lowest)
                $lowest                     = $low;
            $surnamecount                   = $result[$i]['surnamecount'];
            $totcount                       += $surnamecount;
            $pctdone                        = ($surnamecount * 100.0) / $count;
            $pctdoneclass                   = pctClass($pctdone);
            $linkcount                      = $result[$i]['linkcount'];
            $totlinked                      += $linkcount;
            $pctlinked                      = ($linkcount * 100.0)/ $count;
            $pctlinkedclass                 = pctClass($pctlinked);
            $result[$i]['pctdone']          = number_format($pctdone,2);
            $result[$i]['pctdoneclass']     = $pctdoneclass;
            $result[$i]['pctlinked']        = number_format($pctlinked,2);
            $result[$i]['pctlinkedclass']   = $pctlinkedclass;
        }
	}		    // successful query
	else
	{
	    $msg	    .= "query '$queryText' failed: " .
                        print_r($connection->errorInfo(),true);
        $result     = array();
	}		    // query failed
}		        // ok
else
    $result         = array();

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
