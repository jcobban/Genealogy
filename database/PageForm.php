<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  PageForm.php														*
 *																		*
 *  Update the Pages database for a single enumeration division.		*
 *																		*
 *  Parameters															*
 *		Census			year of census									*
 *		Province		province of enumeration (mandatory < 1867)		*
 *		District		district number									*
 *		SubDistrict		sub-district identifier							*
 *		Division		division number (optional for some sub-districts*
 *																		*
 *  History:															*
 *		2010/10/01		Reformat to new page layout.					*
 *		2010/10/04		Default image URL for 1911 census				*
 *		2010/10/19		Add hyperlink to help page						*
 *						Make page number read-only						*
 *						Clean up separation of HTML and PHP				*
 *						Add default image URL creation for 1851 census	*
 *		2010/11/20		add support for alternate page increment		*
 *						use common MDB2 connection to database			*
 *		2010/11/21		do not generate default image URL if missing	*
 *						information in SubDistricts						*
 *		2010/11/23		no error message on empty value of Province		*
 *		2010/11/28		support either image base or relative frame as	*
 *						first frame number for image url generation		*
 *		2011/01/20		incorrect where clause if division null string	*
 *		2011/02/03		add button for viewing the identified image		*
 *		2011/04/20		improve separation of javascript and HTML		*
 *		2011/06/03		use CSS for layout in place of tables			*
 *		2011/06/27		add support for 1916 census						*
 *		2011/09/04		add support for 1871 census						*
 *						clean up default image generation				*
 *		2011/09/10		change algorithm for default 1871 image files	*
 *		2011/09/25		make parameters Province and Division optional	*
 *						improve validation								*
 *						add support for 1906 census images				*
 *		2011/11/04		use button to view images						*
 *		2012/04/16		add id='Submit' on submit button so help works.	*
 *		2012/09/13		share default image URL calculation with		*
 *						CensusForms										*
 *						use clearer variable names						*
 *						use full census identifier in parameters		*
 *		2013/01/26		remove diagnostic printout						*
 *		2013/04/13		support being invoked without edit				*
 *						authorization better							*
 *		2013/07/14		use SubDistrict object							*
 *		2013/07/23		add support for ripple update to image URLs		*
 *						support district numbers ending in .0			*
 *		2013/07/26		do not capitalize page number					*
 *		2011/08/19		add support for 1921 census						*
 *		2011/08/21		improve structuring of display table to			*
 *						support common dynamic keystroke handling		*
 *		2013/08/31		provide ability to override ImageBase and		*
 *						RelFrame through parameters						*
 *		2013/11/26		handle database server failure gracefully		*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/07		do not override $debug value from common		*
 *						pass Debug to PageUpdate.php					*
 *		2014/12/30		use class Page to access Pages table			*
 *						do not display fractional portion of integer	*
 *						district id										*
 *						redirect debugging output to $warn				*
 *		2015/05/08		do not use tables for layout					*
 *						use tiling interface for image displaye			*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/05/25		display help in a new tab or window				*
 *		2016/06/05		use $censusInfo									*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		query page moved to ReqUpdatePages.php			*
 *		2016/01/20		add id to debug trace div						*
 *						include http.js before util.js					*
 *						use class Census to get census information		*
 *		2017/09/12		use get( and set(								*
 *		2017/11/17		functionality for initializing page table		*
 *						database records is moved to class SubDistrict	*
 *						$subdistrict->getPages now returns RecordSet	*
 *		2019/11/13      use Template                                    *
 *		2020/06/07      correct insertions into undefined SubDistrict   *
 *		                error message                                   *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get parameter values into local variables
$cc			    		= 'CA';		    // ISO country code
$countryName			= 'Canada';
$censusId				= null;		    // census code
$censustext				= null;		// invalid census id 
$censusYear				= '';		    // census year
$province				= null;		    // province for pre-confederation
$provincetext			= null;		// invalid province id
$distId		    		= null;
$disttext		    	= null;		// invalid district number
$subdistId				= null;
$subdisttext			= null;		// invalid subdistrict identifier
$division				= '';
$divisiontext			= null;		// invalid division number
$imageBase              = null;
$imageBasetext          = null;
$relFrame               = null;
$relFrametext           = null;
$offset                 = 0;
$limit                  = 20;
$lang		    	    = 'en';
$npprev                 = '';
$npnext                 = '';

// process initialization parameters 
if (isset($_GET) && count($_GET) > 0)
{		            // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                  "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {			    // loop through all parameters
        $valuetext  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$valuetext</td></tr>\n"; 
        $value                      = trim($value);

	    switch(strtolower($key))
	    {
	        case 'census':
	        {		// census identifier
                if (preg_match('/^([a-zA-Z]{0,5})(\d{4})$/', $value))
                {
                    $censusId	    = strtoupper($value);
		    		if (strlen($censusId) == 4)
		    		{		// old format only includes year
		    		    $censusYear		= $censusId;	// census year
		    		    $censusId		= 'CA' . $censusYear;
		    		}		// old format only includes year
                }
                else
                    $censustext     = htmlspecialchars($value);
	    		break;
	        }		// census year

	        case 'province':
	        {		// province code
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
	    		    $province	        = strtoupper($value);
                else
                    $provincetext       = htmlspecialchars($value);
	    		break;
	        }		// province code
	    
	        case 'district':
	        {		// district number
                if (preg_match('/^\d+(.5|)$/', $value))
                    $distId	        = $value;
                else
                    $disttext       = htmlspecialchars($value);
	    		break;
	        }		// district number

	        case 'subdistrict':
	        {		// subdistrict code
                if (preg_match('/^[a-zA-Z0-9()[\]-]+$/', $value))
					$subdistId	    = $value;
                else
                    $subdisttext    = htmlspecialchars($value);
	    		break;
	        }		// subdistrict code

	        case 'division':
	        {		// enumeration division
                if (preg_match('/^[a-zA-Z0-9()[\]-]+$/', $value))
					$division	    = $value;
                else
                    $divisiontext   = htmlspecialchars($value);
	    		break;
	        }		// enumeration division

	        case 'imagebase':
            {		            // image base at LAC
                if (ctype_digit($value))
                    $imageBase      = $value;
                else
                    $imageBasetext  = htmlspecialchars($value);
	    		break;
	        }		            // image base at LAC

	        case 'relframe':
            {		            // relative frame start at LAC
                if (ctype_digit($value))
                    $relFrame       = $value;
                else
                    $relFrametext   = htmlspecialchars($value);
	    		break;
	        }		            // relative frame start at LAC

	        case 'offset':
            {		// offset in page set
                if (ctype_digit($value))
	    		    $offset         = $value;
	    		break;
	        }		// starting offset in page set

	        case 'limit':
	        {		// maximum number of pages to display
                if (ctype_digit($value))
	    		    $limit          = $value;
	    		break;
	        }		// maximum number of pages to display

	        case 'lang':
	        {		// language
                $lang               = FtTemplate::validateLang($value);
	    		break;
	        }		// language

	    }	        // switch on $key value
    }		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}		            // invoked by method=get

// allocate Template
$update	                    = canUser('admin');
if ($update)
	$action                 = 'Update';
else
	$action                 = 'Display';
$template		            = new FtTemplate("PageForm$action$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'PageForm'));

// validate census identifier
if (is_string($censustext))
    $msg                    .= $template['censusUndefined']->
                                        replace('$CENSUSID', $censustext);
else
if ($censusId)
{                       // census identifier specified
	$censusRec				= new Census(array('censusid'	=> $censusId));
	if ($censusRec->isExisting())
	{                   // census defined
		$partof				= $censusRec->get('partof');
		if (is_string($partof) && strlen($partof) == 2)
		    $cc				= $partof;
		else
		    $cc				= substr($censusId, 0, 2);
	    $censusYear			= intval(substr($censusId, 2));
	
        // validate province
        if (is_string($province))
        {
	        $provs	        = $censusRec->get('provinces');
            if (strlen($province) > 0 && 
                strpos($provs, $province) === false)
                $provincetext= $province;
            if (is_string($provincetext))
            {
                $msg        .=
                    $template['provinceInvalid']->replace('$province', 
                                                            $provincetext);
            }
            if ($censusRec['collective'])
            {           // collection of censuses
                $ppos		= strpos($censusRec->get('provinces'), $province);
			    if (strlen($province) != 2 || $ppos === false ||
				    $ppos < 0 || ($ppos & 1) == 1)
                {
		            $msg	.= $template['provinceUndefined']->
                                    replace(array('$province','$censusid'), 
                                            array($province, $censusId));
                }
                $censusId	    = $province . $censusYear;
	    	    $domain		    = 'CA' . $province;
	    	    $domainParms    = array('domain'	=> $domain,
	                        	    	'language'	=> $lang);
	    	    $domainObj	    = new Domain($domainParms);
	    	    $countryName    = $domainObj->getName();
            }           // collection of censuses
            // else province ignored
        }               // province specified
	    else
	    {               // province not specified
            if ($censusRec['collective'])
            {           // collection of censuses
                $msg	    .= $template['provinceMissing']->
                                        replace('$censusId', $censusId);
            }           // collection of censuses
	    }               // province not specified
	}                   // census defined
    else
    {                   // census not defined
        $msg                .= $template['censusUndefined']->
                                    replace('$CENSUSID', $censusId);
        $cc                 = substr($censusId, 0, 2);
    }                   // census not defined
}                       // census identifier specified
else
{                       // census identifier not specified
    $msg	                .= $template['censusMissing']->innerHTML;
    $countryName            = '';
}                       // census identifier not specified

// validate country
if ($cc)
{
    $countryObj				= new Country(array('code' => $cc));
	if ($countryObj->isExisting())
	{
        $countryName		= $countryObj->getName();
    }
    else
    {
        $text	            = $template['countryUndefined']->
                                                    replace('$cc', $cc);
		$msg	            .= $text;
    }
}

// validate district identifier
if (is_string($disttext))
    $msg	        .= $template['districtUndefined']->
                                            replace('$DISTID', $disttext);
else
if ($distId)
{                       // district specified
	if (preg_match("/^[0-9]+(\.[05]|)$/", $distId) == 1)
	{		            // matches pattern of a district number
	    if (substr($distId,strlen($distId)-2) == '.0')
		    $distId			= substr($distId, 0, strlen($distId) - 2);
        $district	= new District(array('census'	=> $censusId,
            	    					 'distid'	=> $distId));
	    if ($district->isExisting())
            $distName		= $district->get('d_name');
        else
        {
            $msg	        .= $template['districtUndefined']->
                                            replace('$DISTID', $distId);
        }
	}		            // matches pattern of a district number
	else
	{
        $msg                .= $template['districtInvalid']->
                                        replace('$distId', $distId);
    }
}                       // district specified
else
    $msg	                .= $template['districtMissing']->innerHTML;

// get the district and subdistrict names
// and other information about the identified division
if (is_string($subdisttext))
{
    $text	            = $template['subdistrictUndefined']->innerHTML;
    $text               = str_replace('$CENSUSID', $censusId.$censustext, $text);
    $text               = str_replace('$DISTID', $distId.$disttext, $text);
    $text               = str_replace('$SUBDISTID', $subdisttext, $text);
    $msg	            .= $text;
}
if ($censusId && $distId && $subdistId)
{
    $subDistrict	= new SubDistrict(array('sd_census'	=> $censusId,
            	    						'sd_distid'	=> $distId,
	                						'sd_id'		=> $subdistId,
	    	    	        				'sd_div'	=> $division));

    if ($subDistrict->isExisting())
    {
		$subdistName		= $subDistrict->get('sd_name');
		$pageCount		    = $subDistrict->get('sd_pages');
        $page1		    	= $subDistrict->get('sd_page1');
        $bypage		    	= $subDistrict->get('sd_bypage');
        if (is_null($imageBase))
            $imageBase		= $subDistrict->get('sd_imagebase');
        else
            $subDistrict->set('sd_imagebase', $imageBase);
        if (is_null($relFrame))
            $relFrame		= $subDistrict->get('sd_relframe');
        else
		    $subDistrict->set('sd_relframe', $relFrame);
        // the page number past the end of the division
        $dlmpage			= $page1 + ($pageCount * $bypage);

        $pages              = $subDistrict->getPages();

        // setup the links to the preceding and following divisions within
        // the current district
        $npprev		    	= $subDistrict->getPrevSearch();
        $npnext		    	= $subDistrict->getNextSearch();
    }
    else
	{
        $text	            = $template['subdistrictUndefined']->innerHTML;
        $text               = str_replace('$CENSUSID', $censusId, $text);
        $text               = str_replace('$DISTID', $distId, $text);
        if (strlen($division) > 0)
            $subdistId      = "$subdistId-$division";
        $text               = str_replace('$SUBDISTID', $subdistId, $text);
		$msg	            .= $text;
    }
}
if (is_null($subdistId))
    $msg	                .= $template['subdistrictMissing']->innerHTML;
$template->set('CC',		        $cc);
$template->set('COUNTRYNAME',	    $countryName);
$template->set('CENSUSID',		    $censusId);
$template->set('CENSUSYEAR',	    $censusYear);

if (strlen($msg) == 0)
{
    $template->set('PROVINCE',		$province);
    $template->set('DISTID',		$distId);
    $template->set('SUBDISTID',		$subdistId);
    $template->set('DIVISION',		$division);
    $template->set('LANG',		    $lang);
    $template->set('DISTNAME',      $distName);
    $template->set('SUBDISTNAME',	$subdistName);

    // search arguments to URL for current instance
	if (strlen($npprev) == 0)
    {
        $template['topPrev']->update('&nbsp;');
        $template['botPrev']->update('&nbsp;');
    }
    else
        $template->set('NPPREV', $npprev);

	if (strlen($npnext) == 0)
    {
        $template['topNext']->update('&nbsp;');
        $template['botNext']->update('&nbsp;');


    }
    else
        $template->set('NPNEXT', $npnext);

    if (strlen($division) == 0)
    {
        $template['topDiv']->update(null);
        $template['botDiv']->update(null);
    }

    // display the results
    $template['pageRow$page']->update($pages);
}		// no errors in validation
else
{
    $template['topBrowse']->update(null);
    $template['censusForm']->update(null);
    $template['botBrowse']->update(null);
}

$template->display();
