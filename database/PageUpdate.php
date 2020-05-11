<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  PageUpdate.php														*
 *																		*
 *  Update the Page table entries for a particular division.  This		*
 *  script is invoked by PageForm.php with method='post'.				*
 *																		*
 *  Parameters:															*
 *		Census			census identifier CCYYYY					    *
 *		District		census district identifier						*
 *		SubDistrict		sub-district identifier							*
 *		Division		enumeration division number						*
 *																		*
 *  History:															*
 *		2010/10/01		Reformat to new page layout.					*
 *		2010/10/19		Document entire Page table update process		*
 *		2012/09/13		pages in new division incremented by 1 instead	*
 *						of bypage										*
 *						use common routine getNames to obtain division	*
 *						info											*
 *						remove deprecated calls to doQuery and doExec	*
 *						use full census identifier in parameters		*
 *		2013/07/14		use SubDistrict class							*
 *		2013/08/17		accept district number with .0 appended			*
 *		2013/11/26		handle database server failure gracefully		*
 *		2014/04/26		remove formUtil.inc obsolete					*
 *						use class Page to update Pages table			*
 *		2014/05/19		correct field names in page table update		*
 *		2014/05/22		handle failure of SubDistrict constructor		*
 *						correct indentation								*
 *		2014/08/19		do not warn on 3 digit page numbers				*
 *		2014/09/07		improve handling of input						*
 *		2014/12/30		use new format of Page constructor				*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/05/23		misspelled variable name caused bad District	*
 *						constructor call								*
 *						attempted to set protected fields in Page		*
 *						update functionality completely rewritten to	*
 *						better exploit the functionality of the Page	*
 *						and SubDistrict classes							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		ReqUpdatePages moved to .php					*
 *		2016/01/20		add id to debug trace div						*
 *						include http.js before util.js					*
 *		2017/09/12		use get( and set(								*
 *		2019/12/04      if the update is successful redirect to the     *
 *		                page table update request page which invoke     *
 *		                the page table update                           *
 *		                use FtTemplate                                  *
 *		2020/05/03      add default for censusId                        *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/common.inc';

// variables for constructing the main SQL SELECT statement
$flds	    = "PT_Census, PT_DistId, PT_SdId, PT_Div, PT_Page, PT_Population, PT_Transcriber, PT_ProofReader, PT_Image";
$tbls	    = "Pages";

// identify the specific Division
$censusId				= null;		// census id 'CCYYYY'
$censusYear				= null;		// year of enumeration
$distId		    		= null;		// district number
$subdistId				= null;		// subdistrict identifier
$division				= null;		// division number
$province				= null;		// explicit province id
$subDistrict			= null;		// instance of SubDistrict
$pageEntry				= null;		// instance of Page
$provs		    		= null;		// string of valid provinces
$lang                   = 'en';

// validate the parameters
if (count($_POST) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key 		=> $value)
	{		            // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	
		// split the field names into column name
		// and page number
		$patres	                = preg_match('/^([a-zA-Z_]+)([0-9]+)$/',
		        				             $key,
				        		             $split);
		if ($patres == 1)
		{
		    $fldname	        = $split[1];	// part that is all letters and
		    $pagenum	        = $split[2];	// part that is numeric
		}
		else
		{
		    $fldname	        = $key;
		    $pagenum	        = '';
        }
        if (strlen($value) > 0)
        {               // value passed
			switch(strtolower($fldname))
			{	        // take action on field name
			    case 'census':
			    {
					$censusId	    = $value;
					break;
				}		// census identifier
		
			    case 'district':
			    {	    // district number
					$distId	        = $value;
					break;
				}		// district number
		
			    case 'province':
			    {	    // province code
					$province	    = strtoupper($value);
					break;
				}		// province code
		
			    case 'subdistrict':
			    {	    // subdistrict code
					$subdistId	= $value;
					break;
				}		// subdistrict code
		
			    case 'division':
			    {	    // division code
					$division	= $value;
					break;
				}		// division code
	
				case 'lang':
	            {
	                $lang       = FtTemplate::validateLang($value);
	                break;
	            }
		
			}		    // take action on field name
        }               // value passed
	}				    // loop through parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}		                // invoked by method=post update Pages

$template               = new FtTemplate("PageUpdate$lang.html");

if (!canUser('edit'))
    $msg	            .= $template['notAuthorized']->innerHTML;

// the invoker must explicitly provide the census id
if (is_null($censusId))
    $msg	            .= $template['censusMissing']->innerHTML;
else
{                       // census ID provided
	$census	            = new Census(array('censusid'	=> $censusId,
						        		   'collective'	=> 0));
	$provs	            = $census->get('provinces');
	$censusYear	        = $census->get('year');
	$partof	            = $census->get('partof');
    if (strlen($partof) == 2)
    {
        $province	    = substr($censusId, 0, 2);
        $cc             = $partof;
    }
}               // census ID provided

if (strlen($province) != 2 || strpos($provs, $province) === false)
{
    $text               = $template['provinceInvalid']->innerHTML;
    $msg	            .= str_replace('$province', $province, $text);
}

// the invoker must explicitly provide the District number
if (is_null($distId))
    $msg	            .= $template['districtMissing']->innerHTML;
else
{               // district ID provided
	if (preg_match("/^[0-9]+([.][05])?$/", $distId))
	{			// valid syntax
	    if ($distId == floor($distId))
			$distId	        = intval($distId);
	}						// valid syntax
	else
    {
        $text           = $template['districtInvalid']->innerHTML;
        $msg	        .= str_replace('$distId', $distId, $text);
    }
}               // district ID provided

// the invoker must explicitly provide the SubDistrict number
if (is_null($subdistId))
    $msg	            .= $template['subdistrictMissing']->innerHTML;
else
{               // try to get the instance of SubDistrict
	$subDistrict	= new SubDistrict(array('sd_census'	=> $censusId,
									        'sd_distid'	=> $distId,
									        'sd_id'		=> $subdistId,
									        'sd_div'	=> $division));
	if (!$subDistrict->isExisting())
    {
        $text           = $template['subdistrictUndefined']->innerHTML;
        $msg	        .= str_replace(
                    array('$censusId','$distId','$subdistId','$division'),
                    array($censusId, $distId, $subdistId, $division), 
                    $text);
    }
}               // try to get the instance of SubDistrict

if (strlen($msg) == 0)
{		        // no errors
    // update table
	foreach($_POST as $key 		=> $value)
	{		// loop through parameters
		// split the field names into column name
		// and page number
		$patres	= preg_match('/^([a-zA-Z_]+)([0-9]+)$/',
						     $key,
						     $split);
		if ($patres == 1)
		{
		    $fldname	= $split[1];	// part that is all letters and
		    $pagenum	= $split[2];	// part that is numeric
		}
		else
		{
		    $fldname	= $key;
		    $pagenum	= '';
		}
		switch(strtolower($fldname))
		{	                        // take action on parameter id
		    case 'pt_page':
		    {
				$ptParms	        = array('pt_sdid' => $subDistrict,
									    	'pt_page' => $value);
				$pageEntry	        = new Page($ptParms);
				break;
			}						// PT_Page
	
		    case 'pt_population':
		    case 'pt_transcriber':
		    case 'pt_proofreader':
		    {
				$pageEntry->set($fldname, $value);
				break;
			}						// PT_Xxxx
	
		    case 'pt_image':
		    {
				$pageEntry->set($fldname, $value);
				$pageEntry->save(false);
				$pageEntry		= null;
				break;
			}						// PT_Image
	
		}		                    // take action on parameter id
	}				                // loop through parameters

    header("Location: ReqUpdatePages.php?censusid=$censusId&Province=$province&District=$distId&subdistrict=$subdistId&division=$division&lang=en");
}				        // no errors
else
{                       // report errors
	// arguments to URL
	$search		= "?Census=$censusId&Province=$province&District=$distId&SubDistrict=$subdistId&Division=$division";

	$template->set('SEARCH',		    $search);
	$template->set('CENSUSID',		    $censusId);
    $template->set('PROVINCE',		    $province);
    $template->set('DISTID',		    $distId);
    $template->set('SUBDISTID',		    $subdistId);
    $template->set('DIVISION',		    $division);

    if ($subDistrict)
    {
		// get the district and subdistrict names
		// and other information about the identified division
		$template->set('DISTNAME',	    $subDistrict->get('d_name'));
		$template->set('SUBDISTNAME',	$subDistrict->get('sd_name'));
		$template->set('PAGES',		    $subDistrict->get('sd_pages'));
		$template->set('PAGE1',		    $subDistrict->get('sd_page1'));
		$template->set('BYPAGE',		$subDistrict->get('sd_bypage'));
		$template->set('IMAGEBASE',	    $subDistrict->get('sd_imageBase'));
		$template->set('RELFRAME',	    $subDistrict->get('sd_relFrame'));
		// setup the links to the preceding and following divisions within
		// the current district
		$npprev		                = $subDistrict->getPrevSearch();
		$template->set('NPPREV',		$subDistrict->getPrevSearch());
		$template->set('PREVSD', 	    $subDistrict->getPrevSd());
		$template->set('PREVDIV',	    $subDistrict->getPrevDiv());
		$npnext		                = $subDistrict->getNextSearch();
		$template->set('NPNEXT',		$subDistrict->getNextSearch());
		$template->set('NEXTSD',		$subDistrict->getNextSd());
	    $template->set('NEXTDIV',	    $subDistrict->getNextDiv());
    }

	if (strlen($npnext) == 0)
    {                   // no next division
        $template['gotoNext']->update(null);
	}                   // no next division
	
	if (strlen($npprev) == 0)
	{				    // no previous division
        $template['gotoPrev']->update(null);
    }				    // no previous division
	
    $template->display();
}				        // errors to report
