<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  CensusUpdateStatusDetails.php										*
 *																		*
 *  This script displays the current transcription status of every page	*
 *  in a selected enumeration division of any of the supported censuses.*
 *																		*
 *  Parameters:															*
 *		Census			the identifier of the census, including CC  	*
 *		District		district number within the census				*
 *		SubDistrict		sub-district letter or number within			*
 *						the district									*
 *		Division		enumeration division within the sub-district	*
 *		Page            page number to highlight                        *
 *		ShowProofreader	if true show proofreader's id in column			*
 *																		*
 *  History:															*
 *		2010/08/19		Suppress warning for missing Province parameter	*
 *						Use new formatting of page						*
 *		2010/11/19		Increase separation of HTML and PHP				*
 *						use MDB2 connection resource					*
 *		2011/01/05		Avoid warning if census doesn't have divisions	*
 *		2011/01/15		Include both names and ages in completion		*
 *						statistics										*
 *		2011/05/19		use CSS in place of tables						*
 *		2011/06/27		add support for 1916 census						*
 *		2011/09/25		correct URL for update/query form in header and	*
 *						trailer											*
 *		2011/10/16		add links to previous and next division of		*
 *						current district								*
 *						improve separation of PHP and HTML				*
 *						validate census year							*
 *						use table to identify number of lines per page	*
 *						clarify SQL query statements					*
 *		2011/12/08		use <button> for edit page						*
 *						the page cleans up any completely blank pages	*
 *						and any incorrect transcriber entries based		*
 *						upon the displayed statistics					*
 *		2011/12/17		improve parameter validation					*
 *						add support for optionally displaying			*
 *						proofreader id									*
 *		2012/03/10		permit administrator to upload pages to			*
 *						production server								*
 *		2012/05/29		hide upload										*
 *		2012/07/10		add display of % linked to family tree			*
 *		2012/09/13		pages in new division incremented by 1 instead	*
 *						of bypage										*
 *						use common routine getNames to obtain division	*
 *						info											*
 *						use common table to validate census				*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *						this only effects a comment						*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/07/14		use class SubDistrict							*
 *		2013/11/22		handle lack of database server connection		*
 *						gracefully										*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/28		use CSS for layout								*
 *		2014/04/26		remove formUtil.inc obsolete					*
 *						use class Page to update Pages table			*
 *						bad URL in header and trailer for edit			*
 *		2014/09/23		use shared function pctClass					*
 *						do not use SubDistrict object if constructor	*
 *						failed											*
 *						case independent parameter validation			*
 *						do not warn on presence of debug parameter		*
 *		2014/12/30		use new format of Page constructor				*
 *						redirect debugging output to $warn				*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/08		include CommonForm.js library					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/11/08		debug output sent to $parm instead of $warn		*
 *		2015/11/23		center district description						*
 *						missing page title								*
 *		2016/01/22		use class Census to get census information		*
 *						add id to debug trace div						*
 *						include http.js before util.js					*
 *		2016/07/19		use title in <h1>								*
 *		2016/07/26		fix to support improved group by validation		*
 *		2016/10/01		group by validation fails for Census1911		*
 *		2017/09/12		use get( and set(								*
 *		2018/01/15		accept lang= attribute							*
 *		2018/01/17		correct error in delete empty pages code		*
 *						parameter list of new CensusLineSet changed		*
 *		2018/11/08      improve parameter error checking                *
 *		2018/11/29      use Template                                    *
 *		2020/01/22      internationalize numbers                        *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values if not specified
$censusId						= '';
$censusYear						= '';
$districtId		    			= '';
$districtName		   			= 'unresolved';
$subdistrictId					= '';
$subdistrictName				= 'unresolved';
$division						= '';
$highlightpage                  = null;
$province						= '';
$provinceName					= '';
$cc				        		= 'CA';
$countryName					= 'Canada';
$domain 						= '';
$domainName 					= 'Canada';
$npprev		    				= '';
$prevSd 						= '';
$prevDiv						= '';
$npnext		    				= '';
$nextSd		    				= '';
$nextDiv						= '';
$lang		    				= 'en';
$showProofreader	    		= false;
$unexpected                     = array();

// obtain the parameters passed with the request
foreach($_GET as $key => $value)
{		            // loop through parameters
	switch(strtolower($key))
	{	            // take action on parameter id
	    case 'domain':
	    {	        // domain code
			$domain 	        = strtoupper($value);
			$province           = substr($domain, 2);
			break;
	    }	        // domain code

	    case 'province':
	    {	        // province code
			$province	        = strtoupper($value);
			$domain	            = 'CA' . $value;
			break;
	    }	        // province code

	    case 'state':
	    {	        // state code
			$province	        = strtoupper($value);
			$domain	            = 'US' . $value;
			break;
	    }	        // state code

	    case 'census':
	    {           // census identifier
			$censusId	        = $value;
			break;
	    }	        // census identifier

	    case 'district':
	    {	        // district number
			$districtId         = $value;
			if (substr($districtId, strlen($districtId) - 2, 2) == ".0")
			    $districtId	    = floor($districtId);
			break;
	    }	        // district number

	    case 'subdistrict':
	    {	        // subdistrict code
			$subdistrictId	    = $value;
			break;
	    }	        // subdistrict code

	    case 'division':
	    {	        // division identifier
			$division	        = $value;
			break;
	    }	        // division identifier

	    case 'page':
        {	        // page identifier
            if (ctype_digit($value))
			    $highlightpage	        = intval($value);
			break;
	    }	        // division identifier

	    case 'showproofreader':
	    {	        // proofreader option
			if ($value == 'true')
			    $showProofreader= true;
			break;
	    }	        // proofreader option

	    case 'lang':
	    {           // user's preferred language of communication
			$lang		        = FtTemplate::validateLang($value);
			break;
	    }           // user's preferred language of communication

	    case 'debug':
	    {           // handled by common
			break;
	    }           // handled by common

	    default:
	    {	        // unexpected parameter
			$unexpected[$key]   = $value;
			break;
	    }	        // unexpected parameter

	}	            // take action on parameter id
}	            	// loop through parameters

// some actions depend upon whether the user can edit the database
if (canUser('edit'))
{		// user can update database
	$searchPage		= 'ReqUpdate.php';
	$action			= 'Update';
}		// user can updated database
else
{		// user can only view database
	$searchPage		= 'QueryDetail' . $censusYear . '.html';
	$action			= 'Query';
}		// user can only view database

$template           = new FtTemplate("CensusStatusDetails$action$lang.html");
$formatter                          = $template->getFormatter();

// the invoker must explicitly provide the Census year
if (strlen($censusId) == 0)
{
    $msg	            .= $template['censusMissing']->innerHTML;
    $censusId           = 'CA1881';
}

$census	                = new Census(array('censusid'	=> $censusId));
if ($census->isExisting())
{
    $censusYear 	    = $census['year'];
    if ($census['collective'])
    {               // simulated national census
        if ($province == '')
        {
            $text       = $template['provinceMissing']->innerHTML;
            $msg	    .= str_replace('$censusId', $censusId, $text); 
        }
        else
        {
            $text       = $template['censusReplaced']->innerHTML;
            $newCensusId   = $province . $censusYear;
            $warn       .= '<p>' .
                            str_replace(array('$censusId', '$newCensusId'),
                                        array($censusId, $newCensusId),
                                        $text) . "</p>\n";
            $censusId   = $newCensusId;
            $census	    = new Census(array('censusid'	=> $censusId));
        }
    }               // simulated national census
    else
    if ($census['partof'])
        $province	    = $census['province'];
    $pctfactor	        = 100 / $census['linesPerPage'];
}
else
{
    $text               = $template['censusUndefined']->innerHTML;
    $msg	            .= str_replace('$censusId', $censusId, $text); 
}

// the invoker must explicitly provide the District number
if (strlen($districtId) == 0)
{
    $msg	            .= $template['districtMissing']->innerHTML;
}
else
{                   // district number specified
    $district           = new District(array('censusid'     => $census,
                                             'id'           => $districtId));
    if ($district->isExisting())
    {
        $districtName   = $district->get('name');
    }
    else
    {
        $text           = $template['districtUndefined']->innerHTML;
        $msg	        .= str_replace(array('$districtId','$censusId'), 
                                       array($districtId, $censusId), 
                                       $text); 
    }
    $province           = $district->get('province');
    $domain             = $district->getDomain();
    $provinceName       = $domain->getName();
}                   // district number specified

// the invoker must explicitly provide the SubDistrict identifier
if (strlen($subdistrictId) == 0)
{
    $msg	            .= $template['subdistrictMissing']->innerHTML;
}
else
{                   // sub-district number specified
    $subParms	    = array('sd_census'	=> $census,
					    	'sd_distid'	=> $district,	
					    	'sd_id'		=> $subdistrictId,
					    	'sd_div'	=> $division);
    $subDistrict	= new SubDistrict($subParms);

    if ($subDistrict->isExisting())
    {
	    $subdistrictName	= $subDistrict->get('sd_name');
	    $pages		    	= $subDistrict->get('sd_pages');
	    $page1		    	= $subDistrict->get('sd_page1');
	    $bypage		    	= $subDistrict->get('sd_bypage');
	    $population			= $subDistrict->get('sd_population');
	    if ($population == 0)
			$population		= 1;	// prevent divide by zero
	    $imageBase	    	= $subDistrict->get('sd_imageBase');
	    $relFrame	    	= $subDistrict->get('sd_relFrame');
	
	    // setup the links to the preceding and following divisions within
	    // the current district
	    $npprev	        	= $subDistrict->getPrevSearch();
	    $prevSd 	    	= $subDistrict->getPrevSd();
	    $prevDiv	    	= $subDistrict->getPrevDiv();
	    $npnext	        	= $subDistrict->getNextSearch();
	    $nextSd	        	= $subDistrict->getNextSd();
	    $nextDiv	    	= $subDistrict->getNextDiv();
    }
    else
    {
        $text               = $template['subdistrictUndefined']->innerHTML;
        $msg	            .=
            str_replace(array('$subdistrictId','$districtId','$censusId'), 
                        array($subdistrictId, $districtId, $censusId), 
                        $text);
    } 
}                   // sub-district number specified

$template->set('CENSUSID',		    $censusId);
$template->set('CENSUSYEAR',		$censusYear);
$template->set('DISTRICTID',		$districtId);
$template->set('DISTRICTNAME',		$districtName);
$template->set('SUBDISTRICTID',		$subdistrictId);
$template->set('SUBDISTRICTNAME',	$subdistrictName);
$template->set('DIVISION',		    $division);
$template->set('PROVINCE',		    $province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('CC',		        $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAIN',		    $domain);
$template->set('DOMAINNAME',		$domainName);
$template->set('PREVSD',		    $prevSd);
$template->set('PREVDIV',		    $prevDiv);
$template->set('NEXTSD',		    $nextSd);
$template->set('NEXTDIV',		    $nextDiv);
$template->set('LANG',		        $lang);
$template->set('COLSPAN2',		    '');
if ($showProofreader)
    $template->set('SHOWPROOFREADER',	'false');
else
    $template->set('SHOWPROOFREADER',	'true');

// access database only if there were no errors in validating parameters
if (strlen($msg) == 0)
{		            // no errors
	// build parameters for searching database
	$getParms			        = array();
	$getParms['censusId']		= $censusId;
	$getParms['distId']		    = $districtId;
	$getParms['subdistId']		= $subdistrictId;
	$getParms['division']		= $division;
	$getParms['order']		    = 'Line';

    if ($showProofreader)
    {		            // show proofreader id
    	$npprev	                .= "&amp;ShowProofreader=true";
    	$npnext	                .= "&amp;ShowProofreader=true";
    }		            // show proofreader id
    else
    {                   // hide proofreaer id
        $template['proofHead1']->update(null);
        $template['proofHead2']->update(null);
        $template['donot']->update(null);
    }                   // hide proofreaer id

	// execute the main query
    $result		                = $subDistrict->getStatistics();
	$cleanupPages	            = array();	// pages which should be deleted

    $template->set('NPPREV',		    $npprev);
    $template->set('NPNEXT',		    $npnext);

	if (strlen($npprev) == 0)
        $template['topPrev']->update(null);
	if (strlen($npnext) == 0)
        $template['topNext']->update(null);
    if (strlen($division) == 0)
        $template['divisionPart']->update(null);

	// display the results
	$even					= false;
	$exppage				= $page1;
	$done					= 0;
    $linked					= 0;
    $rowElement             = $template['detail$page'];
    $rowText                = $rowElement->innerHTML;
    $data                   = '';

	foreach($result as $row)
	{
	    $page		        = $row['page'];
	    $namecount		    = $row['namecount'];
	    $agecount		    = $row['agecount'];
	    $idircount		    = $row['idircount'];
	    $population	        = $row['pt_population'];

        $done               += $namecount;
        $linked             += $idircount;

	    if ($even)
	    {		// insert empty cell
	        $data           .= "            <td>&nbsp;</td>\n";
	    }		// insert empty cell
	    else
	    {		// start new row
            $data           .= "          <tr>\n";
	    }		// start new row

		if ($namecount == 0)
		{		// no lines in page, should be deleted
			$cleanupPages[]	= $page;
			// clear transcriber for empty page
			$ptparms	    = array('pt_sdid'	=> $subDistrict,
							    	'pt_page'	=> $page);
			$pageEntry	    = new Page($ptparms);
			$pageEntry->set('pt_transcriber', '');
            $pageEntry->save(false);
            $row['pt_transcriber']  = '';
            $row['pt_proofreader']  = '';
		}		// no lines in page, should be deleted

		// display a row with values from database
        $pctdone	            = ($namecount + $agecount)*50/$population; 
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $row['pctdone']         = $formatter->format($pctdone);
        $row['pctclassdone']    = pctClass($pctdone, false);
		$pctlinked	            = $idircount*100/$population;
        $row['pctlinked']       = $formatter->format($pctlinked);
        $row['pctclasslinked']  = pctClass($pctlinked, false);
        if ($page == $highlightpage)
            $row['pageclass']   = 'even right bold';
        else
            $row['pageclass']   = 'data right';

        $rtemplate  = new \Templating\Template($rowText);
		if (!$showProofreader)
        {	        // hide proofreader id
            $rtemplate['ProofreaderCol']->update(null);
        }
        $rtemplate->getDocument()->update($row);
        $data                   .= $rtemplate->compile();

		// complete the current row and set up for the next
		if ($even)
		{
            $data           .= "          </tr>\n";
			$even	        = false;
		}
		else
		{
			$even	        = true;
		}
    }		// process all rows
    $rowElement->update($data);

    $totpop                 = $subDistrict['population'];
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
	$template->set('DONE',              $formatter->format($done)); 
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
    if ($totpop > 0)
    {
        $template->set('PCTCLASSDONE',      
                        pctClass($done * 100 / $totpop, false));
        $template->set('PCTDONE',           
                        $formatter->format($done * 100 / $totpop)); 
        $template->set('PCTCLASSLINKED',    
                        pctClass($linked * 100 / $totpop, false));
        $template->set('PCTLINKED',         
                        $formatter->format($linked * 100 / $totpop)); 
    }
    else
    {
        $template->set('PCTCLASSDONE',      
                        pctClass(100, false));
        $template->set('PCTDONE',           
                        $formatter->format(100)); 
        $template->set('PCTCLASSLINKED',    
                        pctClass(100, false));
        $template->set('PCTLINKED',         
                        $formatter->format(100)); 
    }
	if (count($cleanupPages) > 0 && canUser('edit'))
	{		        // there are completely blank pages and user is auth'd
	    $getParms['page']	= $cleanupPages;
	    $deleteSet	        = new CensusLineSet($getParms);
	    $count	            = $deleteSet->delete();
	    $template->set('COUNT',         $count); 
	    if ($count == 0)
            $template['deletedLines']->update(null);
    }		        // there are completely blank pages and user is auth'd
    else
        $template['deletedLines']->update(null);
}                   // no errors
else
{
    $template['topBrowse']->update(null);
    $template['dataTable']->update(null);
	$template['deletedLines']->update(null);
	$template['buttonForm']->update(null);
}

$template->display();
