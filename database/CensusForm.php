<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  CensusForm.php														*
 *																		*
 *  Display every field from a page of a census in tabular form			*
 *  so it can be editted.												*
 *																		*
 *  History (as CensusForm1881.php):									*
 *		2010/09/05		Fix warning on use of 'Province'				*
 *						Reformat to new page layout.					*
 *		2010/10/05		correct warning on Division parameter			*
 *		2010/11/18		services from CensusForm.inc expanded			*
 *		2011/01/15		only display next arrow if there is a next page	*
 *		2011/02/16		remove extra arrow outside of <a>				*
 *		2011/05/04		use CSS in place of tables for header/trailer	*
 *						layout											*
 *		2011/07/02		validate transcriber							*
 *						permit transcriber to update image URL			*
 *		2011/07/04		report any proofreader comments					*
 *		2011/09/10		improve separation of HTML & PHP				*
 *						improve separation of HTML & Javascript			*
 *						enforce maximum field lengths					*
 *		2011/09/24		use button for "Display Original Census Image"	*
 *						implement Alt-I and Alt-C keyboard shortcuts	*
 *		2011/10/09		do not capitalize some fields					*
 *		2011/10/13		support diagnostic messages						*
 *						add help divisions for additional fields and	*
 *						buttons											*
 *		2011/10/19		add mouseover for forward and backward links	*
 *		2011/11/18		improve presentation of numeric fields by		*
 *						aligning the input field to the right in the	*
 *						cell											*
 *						improve presentation of flag fields by aligning	*
 *						the input field and its value in the			*
 *						center of the cell								*
 *		2011/11/29		do not initialize family number if surname is	*
 *						[....											*
 *		2012/04/01		add button for managing IDIR value				*
 *		2012/04/04		add help for IDIR button						*
 *		2012/04/13		use id= rather than name= on buttons to prevent	*
 *						them being passed to the action scripts			*
 *						add help for treeMatch button					*
 *						use templates to support i18n					*
 *						move common popup divisions to an include file	*
 *		2012/04/27		extend $rowClass array to support lines			*
 *						squeezed in by the enumerator					*
 *		2012/07/30		add button to clear IDIR association for a line	*
 *		2012/09/28		expand remarks field to 255 characters			*
 *		2013/04/08		suppress family tree button for blank lines		*
 *		2013/05/17		shrink vertical button size by using 			*
 *						class='button'									*
 *		2013/06/21		expand maximum size of surname and givenname to	*
 *						match the family tree limits					*
 *		2013/07/01		setting row class fails for line number out of	*
 *						normal range									*
 *		2013/07/03		correct capitalization of variable names		*
 *		2013/10/19		share code for running through rows of table	*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2014/04/24		always show "Clear" button if supported			*
 *		2014/05/22		reduce width of some columns					*
 *  History:															*
 *		2018/01/11		use class Template								*
 *		2018/02/09		support multilingual popups by iso code			*
 *		2018/02/23		ensure censusId of individual colony used		*
 *						for pre-confederation censuses					*
 *						ensure RecordSet compares full values of		*
 *						parameters for FieldComments					*
 *		2018/02/27		make addition of extra information to			*
 *						CensusLineSet for page more efficient			*
 *						ignore bad province parm for post-confederation	*
 *		2018/10/16      lang parameter was ignored                      *
 *		                get language apology text from Languages        *
 *		                address performance problem                     *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/04/06      use new FtTemplate::includeSub                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/FieldComment.inc';
require_once __NAMESPACE__ . '/common.inc';

// open code
ob_start();			// ensure no output before redirection

// check for abnormal behavior of Internet Explorer
if ($browser->browser == 'IE' && $browser->majorver < 8)
{		// IE < 8 does not support CSS replacement of cellspacing
	$cellspacing	= "cellspacing='0px'";
}		// IE < 8 does not support CSS replacement of cellspacing
else
{
	$cellspacing	= "";
}

// variables for constructing the main SQL SELECT statement
$countryName		= 'Canada';
$provinceName		= '';
$censusId			= '';
$censusYear			= 1881;
$province			= '';
$distID	    		= '';
$districtName		= '';
$subDistrictName	= '';
$subDistID			= '';
$division			= '';
$page		    	= 0;
$lang		    	= 'en';
$transcriber		= '';
$proofreader		= '';
$bypage		        = 1;
$npPrev		        = '';
$npNext		        = '';

// get parameter values into local variables
// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
$getParms	= array('order'	=> 'line');
foreach ($_GET as $key => $value)
{				// loop through all parameters
    $value              = trim($value);
	switch(strtolower($key))
	{
	    case 'census':
	    case 'censusid':
	    {			// supported field name
			$census		= new Census(array('censusid' => $value));
			if ($census->isExisting())
			{
			    $censusId			= $value;
			    if ($census->get('collective'))
			    {		// unexpected
			    }		// unexpected
			    else
			    {
					$province		= substr($value, 0, 2);
					$getParms['censusid']	= $censusId;
			    }
			    $temp			= substr($censusId, -4);
			    $censusYear			= intval(substr($censusId, -4));
			}
			else
			    $msg	.= "Invalid value of CensusID='$value'. ";
			break;
	    }			// Census Identifier

	    case 'province':
	    {			// Province code (pre-confederation)
			$domain		= new Domain(array('code' => 'CA' . strtoupper($value)));
			if ($domain->isExisting())
			{		// valid province code
			    $province			= strtoupper($value);
			    $provinceName		= $domain->getName();
			    if ($censusYear < 1867)
			    {		// pre-confederation
					if ($census->get('collective'))
					{
					    $censusId		        = $province . $censusYear;
					    $getParms['censusid']   = $censusId;
					    $census	= new Census(array('censusid' => $censusId));
					}
					$getParms['province']	= $value;
			    }		// pre-confederation
			}		// valid province code
			else
			if ($censusYear < 1867)
			    $msg	.= "Invalid value of Province='$value'. ";
			else
			    $province	= '';
			break;
	    }			// Province code

	    case 'district':
	    {			// District number
			$distpat	= '/^\d+(\.5|\.0)?$/';
			if (is_array($value))
			{
			    $value			= current($value);
			    if (count($value) != 1)
					$msg	.= "Invalid value of District=" . 
						   print_r($value,true). ". ";
			}
			if (preg_match($distpat, $value) == 1)
			{		// valid district number
			    $distID			= $value;
			    $getParms['district']	= $value;
			}		// valid district number
			else
			    $msg	.= "Invalid value of District='$value'. ";
			break;
	    }			// District number

	    case 'subdistrict':
	    {			// subdistrict id
			$subDistID			= $value;
			$getParms['subdistrict']	= $value;
			break;
	    }			// subdistrict id

	    case 'division':
	    {			// division
			if (preg_match('/(\d+):(\d+)/', $value, $matches))
			{
			    $distID			            = $matches[1];
			    $subDistID			        = $matches[2];
			    $getParms['district']	    = $distID;
			    $getParms['subdistrict']	= $subDistID;
			    break;
			}
			else
			if ($censusYear == 1906)
			{		// 1906 uses alphabetic division identifiers
			    if (strlen($value) != 0 && ctype_digit($value)) 
					$warn	.= "Invalid value of Division='$value'. ";
			}		// 1906 uses alphabetic division identifiers
			else
			{		// most years division is numeric
			    if (strlen($value) != 0 && !ctype_digit($value)) 
					$warn	.= "Invalid value of Division='$value'. ";
			}		// most years division is numeric
			$division			        = $value;
			$getParms['division']		= $value;
			break;
	    }			// division

	    case 'page':
	    {			// "Page"
			if (ctype_digit($value))
			{
			    $page			        = $value;
			    $getParms['page']		= $page;
			}
			else
			    $msg	.= "Invalid value of Page='$value'. ";
			break;
	    }			// "Page"

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
        }
	    case 'showclear':
	    {			// to clear or not to clear
			// obsolete
			break;
	    }			// to clear or not to clear

	    case 'debug':
	    {			// already handled
			break;
	    }			// already handled 
	}			// already handled
}				// loop through parameters

// validate mandatory parameters 
if (strlen($censusId) == 0)
{
	$msg	.= 'Missing mandatory parameter CensusId. ';
}
else
if ($censusYear < 1867 && strlen($province) == 0)
{		// missing mandatory parameter
	$msg	.= 'Missing mandatory parameter Province. ';
}		// missing mandatory parameter
if (strlen($distID) == 0)
{		// missing mandatory parameter
	$msg	.= 'Missing mandatory parameter District. ';
}		// missing mandatory parameter
if (strlen($subDistID) == 0)
{		// missing mandatory parameter
	$msg	.= 'Missing mandatory parameter SubDistrict. ';
}		// missing mandatory parameter
if (strlen($page) == 0)
{		// missing mandatory parameter
	$msg	.= 'Missing mandatory parameter Page. ';
}		// missing mandatory parameter

if (strlen($msg) == 0)
{		// no messages, do search
	$district	= new District(array('census'	=> $census,
							         'id'	    => $distID));
	if ($lang == 'fr')
	    $districtName	= $district->get('nom');
	else
	    $districtName	= $district->get('name');

	// get information about the sub-district
	$parms	= array('sd_census'	=> $census, 
					'sd_distid'	=> $distID, 
					'sd_id'		=> $subDistID,
					'sd_div'	=> $division);

	$subDistrict	= new SubDistrict($parms);
	if (!$subDistrict->isExisting())
	    $warn		.= "<p>Invalid identification of sub-district:".
	" sd_census=$censusId, sd_distid=$distID, sd_id=$subDistID, sd_div=$division</p>\n";
	$subDistrictName	= $subDistrict->get('sd_name');
	if (strlen($subDistrictName) > 48)
	    $subDistrictName	= substr($subdName, 0, 45) . '...';
	$page1			    = $subDistrict->get('sd_page1');
	$imageBase		    = $subDistrict->get('sd_imagebase');
	$relFrame		    = $subDistrict->get('sd_relframe');
	$pages			    = $subDistrict->get('sd_pages');
	$bypage			    = $subDistrict->get('sd_bypage');

	// obtain information about the page
	$pageRec		    = new Page($subDistrict, $page);

	$image			    = $pageRec->get('pt_image');
	$numLines		    = $pageRec->get('pt_population');
	$transcriber		= $pageRec->get('pt_transcriber');
	$proofreader		= $pageRec->get('pt_proofreader');

	if ($page > $page1)
	{			// not the first page in the division
	    $npPrev	= "?CensusId=$censusId&District=$distID" .
					  "&SubDistrict=$subDistID&Division=$division" .
					  "&Page=" . ($page - $bypage);
	}			// not the first page in the division
	else
	    $npPrev	= '';		// previous selection
	if ($page < ($page1 + $pages * $bypage))
	{			// not the last page in the division
	    $npNext	= "?CensusId=$censusId&District=$distID" .
					  "&SubDistrict=$subDistID&Division=$division" .
					  "&Page=" . ($page + $bypage);
	}			// not the last page in the division
	else
	    $npNext	= '';		// next selection

	// get field comments
	// note that RecordSet knows only that the census, subdistrict, and
	// division fields are strings and so must be told to match the
	// whole values
	$fcSet		= new RecordSet('FieldComments',
					            array(  'fc_Census'	=> "^$censusId$",
					                	'fc_DistId'	=> $distID,
						                'fc_SdId'	=> "^$subDistID$",
						                'fc_Div'	=> "^$division$", 
						                'fc_Page'	=> $page,
						                'order'		=> "FC_Line, FC_FldName"));

	// manage transcriber and proofreader status
	if (canUser('all'))
	{
	    if (strlen($transcriber) == 0)
			$transcriber	= $userid;
	    $action		= 'Update';
	}
	else
	if (canUser('edit'))
	{
	    if ($userid == $transcriber)
			$action		= 'Update';
	    else
			$action		= 'Proofread';
	}
	else
	    $action		    = 'Display';
}		// no errors, continue with request
else
{
	$action			    = 'Display';
	$transcriber		= '';
	$proofreader		= '';
	$image			    = '';
}

$warn	        .= ob_get_clean();	// ensure previous output in page
$template	    = new FtTemplate("CensusForm$censusYear$action$lang.html");

$popups	= "CensusFormPopups$lang.html";
$template->includeSub($popups,
					  'POPUPS');
$template->set('CENSUSYEAR', 		$censusYear);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CENSUSID',			$censusId);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('LANG',			    $lang);
$template->set('DISTRICT',			$distID);
$template->set('DISTRICTNAME',		$districtName);
$template->set('SUBDISTRICT',		$subDistID);
$template->set('SUBDISTRICTNAME',	$subDistrictName);
$template->set('DIVISION',			$division);
$template->set('PAGE',			    $page);
$template->set('PREVPAGE',			$page - $bypage);
$template->set('NEXTPAGE',			$page + $bypage);
$template->set('ROWS',			    $count);
$template->set('TRANSCRIBER',		$transcriber);
$template->set('PROOFREADER',		$proofreader);
$template->set('CENSUS',			$censusYear);
$template->set('SEARCH',			'');
$template->set('CONTACTTABLE',		'Census' . $censusYear);
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('IMAGE',			    $image);

if (strlen($province) == 0)
{
	$tag		= $template->getElementById('frontProv');
	if ($tag)
	    $tag->update(null);
	$tag		= $template->getElementById('backProv');
	if ($tag)
	    $tag->update(null);
}
if (strlen($division) == 0)
{
	$template->updateTag('frontDiv', null);
	$template->updateTag('backDiv', null);
}
$promptTag	= $template->getElementById('ImagePrompt');
if (strlen($image) == 0)
	$template->updateTag('ImageButton', null); // hide
else
if ($promptTag)
	$promptTag->update(null); // hide

if (strlen($msg) > 0)
{
	$template->updateTag('frontPager', null);
	$template->updateTag('backPager', null);
	$template->updateTag('censusForm', null);
}
else
{			// no errors
	if (strlen($npPrev) > 0)
	{
	    $template->updateTag('npPrevFront', 
						     array('npPrev' => $npPrev));
	    $template->updateTag('npPrevBack', 
						     array('npPrev' => $npPrev));
	}
	else
	{
	    $template->updateTag('npPrevFront', null);
	    $template->updateTag('npPrevBack', null);
	}
	if (strlen($npNext) > 0)
	{
	    $template->updateTag('npNextFront',
						     array('npNext' => $npNext));
	    $template->updateTag('npNextBack', 
						    array('npNext' => $npNext));
	}
	else
	{
	    $template->updateTag('npNextFront', null);
	    $template->updateTag('npNextBack', null);
	}
}			// no errors

// update the popup for explaining the action taken by arrows
if (strlen($msg) == 0)
{
    $rowElt             = $template->getElementById('Row$line');
    $rowHtml            = $rowElt->outerHTML();
    $data               = '';
	$lineSet		    = new CensusLineSet($getParms);
	$info			    = $lineSet->getInformation();
	$count			    = $info['count'];
	$groupLines		    = $census->get('grouplines');
	$lastunderline		= $census->get('lastunderline');
	$oldFamily		    = '';
	$oldSurname		    = '';
	$oldReligion		= '';
	$oldOrigin		    = '';
	$oldNationality		= '';
	foreach($lineSet as $censusLine)
    {
        $rtemplate      = new Template($rowHtml);
	    foreach($censusLine as $field => $value)
	    {
			switch($field)
			{		// act on specific field names
				case 'line':
				{
					$line			= $value;
					if (($line % $groupLines) == 0 &&
					     $line < $lastunderline)
					    $censusLine->set('cellclass', 'underline');
					else
					    $censusLine->set('cellclass', 'cell');
					if ($line < 10)
                        $line	= '0' . $line;
					break;
				}		// line number on page

				case 'family':
				{
					if ($value == $oldFamily)
					    $censusLine->set('famclass', 'same');
					else
					{
					    $censusLine->set('famclass', 'black');
					    $oldFamily	= $value;
					}
					break;
				}		// family number

				case 'surname':
				{
					if ($value == $oldSurname)
					    $censusLine->set('surclass', 'same');
					else
					{
					    $censusLine->set('surclass', 'black');
					    $oldSurname	= $value;
					}
					break;
				}		// surname

				case 'origin':
				{
					if ($value == $oldOrigin)
					    $censusLine->set('orgclass', 'same');
					else
					{
					    $censusLine->set('orgclass', 'black');
					    $oldOrigin	= $value;
					}
					break;
				}		// ethnic origin

				case 'nationality':
				{
					if ($value == $oldNationality)
					    $censusLine->set('natclass', 'same');
					else
					{
					    $censusLine->set('natclass', 'black');
					    $oldNationality	= $value;
					}
					break;
				}		// nationality

				case 'religion':
				{
					if ($value == $oldReligion)
					    $censusLine->set('relclass', 'same');
					else
					{
					    $censusLine->set('relclass', 'black');
					    $oldReligion	= $value;
					}
					break;
				}		// religion

				// default value of Mother's birthplace is
				// Father's birthplace
				// so if they are equal Mother's birthplace is default value
				case 'mothersbplace';
				{
					if ($value == $censusLine['fathersbplace'])
					    $censusLine->set('mbpclass', 'same');
					else
					    $censusLine->set('mbpclass', 'black');
					break;
				}		// mothersbirthplace

			    case 'idir':
                {
                    if (is_null($value))
                    {
                        $value      = 0;
                        $censusLine->set('idir',        0);
                    }
					if ($value > 0)
					{
					    $censusLine->set('idirtext',    'Show');
					    $censusLine->set('idirclear',   $line);
					}
					else
					{
					    $censusLine->set('idirtext',    'Find');
                        $rtemplate->updateTag('clearIdir$idirclear', null);
					}
					break;
			    }		// idir

                default:
                {
                    if (is_null($value))
                        $censusLine->set($field, '');
					break;
                }
			}		// act on specific field names
	    }			// loop through all fields in record
        $rtemplate->updateTag('Row$line', $censusLine);
        $data           .= $rtemplate->compile() . "\n";
	}			// loop through records in page
	$rowElt->update($data);
}

// display field comments
if ($fcSet && $fcSet->count() > 0)
{
	$template->updateTag('comment$index', $fcSet);
}
else
{
	$template->updateTag('comments', null);
}

set_time_limit(90);
$template->display();
