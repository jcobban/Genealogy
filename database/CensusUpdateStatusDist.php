<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  CensusUpdateStatusDist.php											*
 *																		*
 *  Display the progress of the transcription of a specific census		*
 *  district.															*
 *																		*
 *  History:															*
 *		2010/09/10		Reformat to new page layout.					*
 *		2010/11/19		use common MDB2 connection resource				*
 *						increase separation of HTML and PHP				*
 *						improve parameter validation					*
 *		2011/05/18		use CSS layout instead of tables				*
 *		2011/09/11		minor change to debug output					*
 *		2011/09/25		display previous district only if there is one	*
 *						display next district only if there is one		*
 *						districts do not have to have integer numbers	*
 *						also display name of previous and next district	*
 *						correct URL for update/query form in header and *
 *						trailer											*
 *						report all divisions of the district, not just	*
 *						those with non-zero stats						*
 *						improve separation of PHP and HTML				*
 *		2011/12/09		improve parameter validation					*
 *		2012/01/26		add Copy button for uploading to server			*
 *		2012/04/27		add help balloons for subdistrict and			*
 *						division ids									*
 *		2012/09/17		Census parameter changed to identifier from year*
 *		2012/10/11		correct search page URL							*
 *		2012/12/31		add percentage linked							*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *						variable $linked not initialized				*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2013/04/20		avoid divide by zero							*
 *		2013/05/23		add option to display surnames by division		*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/07/06		correct URL for requesting next page to			*
 *						edit/view										*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/30		use CSS for layout								*
 *		2014/01/14		move function pctClass to common.inc			*
 *						use common appearance for status tables			*
 *		2014/02/19		correct URL for query							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/08		use common functions from CommonForm.js			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/21		get census info from Census class				*
 *		2017/09/12		use get( and set(								*
 *		2017/10/30		use composite cell style classes				*
 *		2017/11/24		use prepared statements							*
 *						use District class								*
 *		2018/01/18		tolerate lang parameter							*
 *		2018/02/23		accept being invoked with CA1851 or CA1861 plus	*
 *						Province parameter								*
 *		2019/11/26      use Template                                    *
 *		2020/01/22      internationalize numbers                        *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values for parameters
$cc						        = 'CA';
$countryName                    = 'Canada';
$censusId						= '';
$censusYear						= null;
$censusName						= '';
$distId		    				= '';
$distName		    			= '';
$province						= 'ON';
$provinceName					= 'Ontario';
$lang		    				= 'en';
$unexpected                     = array();

// process parameters passed by method=get
if (count($_GET) > 0)
{	        	        // invoked by URL
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	// validate parameters
	foreach ($_GET as $key => $value)
	{			        // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		        // act on parameter name
		    case 'census':
		    case 'censusid':
		    {		    // Census identifier
				$censusId	    = $value;
				break;
		    }		    // Census identifier
	
		    case 'district':
		    {		    // district number
				$distId		    = $value;
				break;
		    }		    // District number
	
		    case 'province':
		    {		    // province code deprecated
				$province	    = strtoupper($value);
				break;
		    }		    // province code 
	
		    case 'lang':
		    {		    // language
				$lang	        = FtTemplate::validateLang($value);
				break;
		    }		    // language
	
		    case 'debug':
		    {		    // debug handled by common
				break;
		    }		    // debug handled by common
	
		    default:
		    {	        // unexpected parameter
				$unexpected[$key]   = $value;
				break;
		    }	        // unexpected parameter
		}	            // act on parameter name
	}		            // loop through all parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}	        	        // invoked by URL to display current status of account

// some actions depend upon whether the user can edit the database
if (canUser('edit'))
	$action		            = 'Update';
else
	$action		            = 'Display';

$template               = new FtTemplate("CensusStatusDist$action$lang.html");
$formatter                  = $template->getFormatter();

// check for missing parameters
if (is_null($censusId))
{		            // Census missing
	$censusId	            = '';
	$msg		            .= $template['censusMissing']->innerHTML;
}		            // Census missing
else
{                   // census specified
	$censusRec	            = new Census(array('censusid'	=> $censusId));
	if ($censusRec->isExisting())
    {
        $censusName         = $censusRec['name'];
		$censusYear	        = intval(substr($censusId, 2));
        if ($censusYear < 1867)
        {
			if ($censusRec->get('collective') == 0)
				$province	= substr($censusId, 0, 2);
            else
            {
                $text       = $template['censusNotPart']->innerHTML;
                $warn       .= '<p>' . 
                                str_replace('$CENSUSID', $censusId, $text).
                                "<p>\n";
            }
        }
        $cc                 = $censusRec['cc'];
        $country            = new Country(array('code'  => $cc));
        $countryName        = $country->getName($lang);
	}
	else
    {
        $text               = $template['censusUndefined']->innerHTML;
        $msg                .= str_replace('$CENSUSID', $censusId, $text);
    }

    if ($censusRec->get('collective') == 1)
    {               // collective census must be qualified by province
        if (strlen($province) != 2)
           $province        = 'CW'; 
		$censusId	        = $province . $censusYear;
	    $censusRec	        = new Census(array('censusid'	=> $censusId));
        $text               = $template['censusCorrected']->innerHTML;
        $warn               .= '<p>' .
                                str_replace('$CENSUSID', $censusId, $text) .
	                            "</p>\n";
    }               // collective census must be qualified by province
}                   // census specified

if ($distId == '')
{		            // District missing
    $msg		            .= $template['districtMissing']->innerHTML;
    $distName               = 'Missing';
}		            // District missing
else
{                   // district specified
	$result		            = array();
    if (preg_match("/^([0-9]+)(\.[05])?$/", $distId, $result) != 1)
    {
        $text               = $template['districtInvalid']->innerHTML;
        $msg                .= str_replace('$DISTID', $distId, $text);
    }
	else
	{
	    if (count($result) > 2 && $result[2] == '.0')
			$distId	        = $result[1];	// integral portion only
	}
	
	$district	            = new District(array('census'	=> $censusId,
					            		         'id'	    => $distId));
	if (!$district->isExisting())
    {
        $text               = $template['districtUndefined']->innerHTML;
        $msg                .= str_replace(array('$CENSUSID','$DISTID'), 
                                           array($censusId, $distId),
                                           $text);
        $distName           = $district['name'];
    }
    else
        $distName           = 'Unknown';
}                   // district specified

// if no errors execute the query
if (strlen($msg) == 0)
{		            // no errors so far
	$distName	            = $district->get('name');
    $province	            = $district->get('province');
    $domain                 = new Domain(array('domain' => $cc . $province,
                                               'lang'   => $lang));
    $provinceName           = $domain->getName();

	// get the total population of the district
    $prevDistrict		    = $district->getPrev();
    if ($prevDistrict)
    {
        $prevDist		    = $prevDistrict->get('id');
        $prevDistName	    = $prevDistrict->get('name');
        $prevCensusId	    = $prevDistrict->get('census');
        $template->set('PREVDIST',      $prevDist);
        $template->set('PREVDISTNAME',  $prevDistName);
        $template->set('PREVCENSUSID',  $prevCensusId);
    }
    else
    {
        $template['topPrev']->update(null);
        $template['botPrev']->update(null);
    }

    $nextDistrict		    = $district->getNext();
    if ($nextDistrict)
    {
        $nextDist		    = $nextDistrict->get('id');
        $nextDistName	    = $nextDistrict->get('name');
        $nextCensusId	    = $nextDistrict->get('census');
        $template->set('NEXTDIST',      $nextDist);
        $template->set('NEXTDISTNAME',  $nextDistName);
        $template->set('NEXTCENSUSID',  $nextCensusId);
    }
    else
    {
        $template['topNext']->update(null);
        $template['botNext']->update(null);
    }

    $pop		    	    = $district->get('d_population');

    $resAll                 = $district->getStatistics();
    if ($debug)
    {
        $warn               .= "<table class='summary'>\n";
	    $first              = true;
	    foreach($resAll as $row)
	    {
	        if ($first)
	        {
	            $warn       .= "<tr>\n";
	            foreach($row as $field => $value)
	                $warn   .= "<th class='colhead'>$field</th>\n";
	            $warn       .= "</tr>\n";
	            $first      = false;
	        }
	        $warn           .= "<tr>\n";
	        foreach($row as $field => $value)
	            $warn       .= "<td class='white left'>$value</td>\n"; 
	        $warn           .= "</tr>\n";
	    }
        $warn               .= "</table>\n";
    }                       // debug response

    $prevDist			= $prevDistrict->get('id');
    $prevDistName		= $prevDistrict->get('name');
    $nextDist			= $nextDistrict->get('id');
    $nextDistName		= $nextDistrict->get('name');


	$done		        		= 0;
    $linked		        		= 0;
    $data                       = '';

    $rowElement         		= $template['subdist$ir'];
    $templateText         		= $rowElement->outerHTML;
	
	foreach($resAll as $row)
	{
        $distId	        		= $row['sd_distid'];
        if ($distId == floor($distId))
            $distId     		= intval($distId);
		$nameCount	    		= $row['namecount'];
		if (is_null($nameCount))
		    $row['namecount']	= 0; 
		$ageCount	    		= $row['agecount'];
		if (is_null($ageCount))
		    $row['agecount']	= 0; 
		$idirCount	    		= $row['idircount'];
		if (is_null($idirCount))
		    $row['idircount']	= 0; 
		$population	    		= $row['sd_population'];
		if ($population > 0)
	 	{	// division exists in original images
	 		$pct	    		= ($nameCount + $ageCount)*50/$population;
	 		$pctl	    		= $idirCount*100/$population;
	 	}	// division exists in original images
	 	else
	 	{	// division missing
	 		$pct	    		= 100;
	 		$pctl	    		= 100;
        }	// division missing
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $row['pct']             = $formatter->format($pct);
        $row['pctl']            = $formatter->format($pctl);
        $row['pctclasspct']     = pctClass($pct);
        $row['pctclasspctl']    = pctClass($pctl);

        $rtemplate              = new \Templating\Template($templateText);
        $rtemplate['subdist$ir']->update($row);
        $data                   .= $rtemplate->compile();
	 
	 	$done	                += intval($nameCount);
	 	$linked	                += intval($idirCount);
    }		// process all rows

    $rowElement->update($data);
	
	// summary line
	if ($pop > 0)
	{
		$pct	                = $done*100/$pop;
		$pctl	                = $linked*100/$pop;
	}
	else
	{
		$pct	                = 0;
		$pctl	                = 0;
    }
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    $template->set('DONE',              $formatter->format($done));
    $template->set('POP',               $formatter->format($pop));
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
    $template->set('PCT',               $formatter->format($pct));
    $template->set('PCTL',              $formatter->format($pctl));
    $template->set('PCTCLASSPCT',       pctClass($pct));
    $template->set('PCTCLASSPCTL',      pctClass($pctl));
}		            // no errors
else
{
    $template['dataTable']->update(null);   // hide the tabular display
    $template['topBrowse']->update(null);   // hide the paging
    $template['botBrowse']->update(null);   // hide the paging
}

$template->set('CENSUSNAME',            $censusName);
$template->set('CENSUSID',              $censusId);
$template->set('CENSUSYEAR',            $censusYear);
$template->set('DISTID',                $distId);
$template->set('DISTNAME',              $distName);
$template->set('CC',                    $cc);
$template->set('COUNTRYNAME',           $countryName);
$template->set('PROVINCE',              $province);
$template->set('PROVINCENAME',          $provinceName);

$template->display();
