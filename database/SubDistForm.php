<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SubDistForm.php							*
 *									*
 *  Display form for editting sub-district information for a district	*
 *  of a Census of Canada						*
 *									*
 *  Parameters (passed by method=get):					*
 *	Census		census identifier CCYYYY			*
 *	Province	two letter code, required on pre-confederation	*
 *			censuses					*
 *	District	district number within census			*
 *	NameWidth	explicit width of name column			*
 *	RemarksWidth	explicit width of remarks column		*
 *	FcAuto		automatic update of frame count, page count	*
 *			and population					*
 *									*
 *  History:								*
 *	2010/10/01	use new format					*
 *	2010/10/04	correct call to genCell in new page		*
 *	2010/10/27	move connection establishment to common.inc	*
 *	2010/11/20	use htmlHeader					*
 *			add page increment column			*
 *	2010/11/20	improve validation				*
 *	2010/11/23	improve separation of HTML and PHP		*
 *			add delete row button				*
 *			add button to display page table		*
 *	2011/03/31	post 1901 censuses use numeric sub-districts	*
 *	2011/04/20	escape name of district and subdistrict		*
 *	2011/06/05	add capability to hide columns			*
 *			improve and clarify parameter validation	*
 *			determine previous and next district from DB	*
 *			correct sort order				*
 *	2011/06/27	add 1916 census support				*
 *	2012/09/15	include province in URI for forward and back	*
 *			links						*
 *			use census identifier, not just year in links	*
 *	2013/01/26	table SubDistTable renamed to SubDistricts	*
 *			set explicit maximum lengths on input fields	*
 *	2013/04/13	support being invoked without edit		*
 *			authorization better				*
 *	2013/07/02	permit explicit setting of width of name column	*
 *	2013/08/18	do not split label of "Add Division" button	*
 *	2013/08/21	add support for 1921 census			*
 *			improve title of dialog				*
 *	2013/08/26	use selective capitalization on name		*
 *	2013/08/27	pass full census identifier and province to	*
 *			ReqUpdateSubDists.html				*
 *	2013/09/16	add FcAuto parameter				*
 *			add RemarksWidth parameter			*
 *	2013/11/22	handle lack of database server connection	*
 *	2014/04/26	remove formUtil.inc obsolete			*
 *	2014/09/22	permit a district number ending in ".0"		*
 *			add id= attribute to all form elements		*
 *	2015/03/28	autocorrect population in 1861 census		*
 *	2015/05/09	remove use of <table> for layout		*
 *	2015/06/05	use class District instead of SQL		*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/01/20	add id to debug trace div			*
 *			include http.js before util.js			*
 *			field names changed in $censusInfo		*
 *			extra ampersand in prev and next links		*
 *			use class Census				*
 *	2016/12/26	do not generate fatal error on bad ident	*
 *	2017/02/07	use class Country				*
 *	2017/08/06	permit updating id and division in existing	*
 *			record.						*
 *	2017/09/12	use get( and set(				*
 *	2017/09/15	use class Template				*
 *	2018/01/04	remove Template from template file names	*
 *	2018/01/10	split administration and display templates	*
 *	2018/01/24	use SubDistrictSet				*
 *			correct null relative frame number		*
 *	2018/02/03	correct handling of $censusObj->get('partof')	*
 *	2018/05/22	choose display class for id, name, lacreel,	*
 *			ldsreel, and image base to distinguish new	*
 *			from unchanged values				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Template.inc';
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/Country.inc';
    require_once __NAMESPACE__ . '/Domain.inc';
    require_once __NAMESPACE__ . '/District.inc';
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/SubDistrictSet.inc';
    require_once __NAMESPACE__ . '/common.inc';
 
    $nameWidth		= 20;		// default width of name column
    $remarksWidth	= 16;		// default width of remarks column
    $fcAuto		= false;	// control automatic update of
					// page count and frame count fields

    // default values for parameters
    $censusId		= '';
    $censusYear		= 9999;
    $cc			= 'CA';
    $countryName	= 'Canada';
    $province		= '';
    $provinceName	= '';
    $distId		= '';
    $name		= '';
    $provList		= '';
    $colspan		= 3;
    $lang		= 'en';		// default english
    $update		= canUser('admin');

    // variables for constructing the main SQL SELECT statement

    $npuri	= '';		// for next and previous links
    $npand	= '?';		// adding parms to $npuri
    $npPrev	= '';		// previous selection
    $npNext	= '';		// next selection

    // validate all parameters passed to the server 
    foreach ($_GET as $key => $value)
    {			// loop through all parameters
	if ($value == '?')
	{		// value explicitly not supplied
	    $msg 	.= $key. ' must be selected. ';
	}		// value explicitly not supplied
	else
	switch($key)
	{		// act on parameter name
	    case 'Census':
	    {		// Census Identifier
		$censusId	= $value;
		if (strlen($censusId) == 4)
		    $censusId	= 'CA' . $censusId;

		try
		{		// valid census identifier
		    $censusRec	= new Census(array('censusid' => $censusId,
						   'collective' => 0));
		    $partof	= $censusRec->get('partof');
		    if (is_string($partof) && strlen($partof) >= 2)
			$cc		= $censusRec->get('partof');
		    else
			$cc		= substr($censusId, 0, 2);
		    $countryObj		= new Country(array('code' => $cc));
		    $countryName	= $countryObj->getName();
		    $censusYear		= intval(substr($censusId, 2));
		    $name		= $censusRec->get('name');
		    $provList		= $censusRec->get('provinces');
		    $npuri		.= "{$npand}{$key}={$value}";
		    $npand		= '&amp;'; 
		    switch($censusYear)
		    {		// switch on year of census
			case 1851:
			case 1861:
			{	// pre-confederation
			    $province	= substr($censusId, 0, 2);
			    break;
			}	// pre-confederation

			case 1901:
			{	// extra column added
			    $colspan	= 4;
			    break;
			}	// extra column added

		    }		// switch on year of census
		}		// valid identifier
		catch (Exception $e)
		{
		    $msg .= "Census identifier '$value' invalid. ";
		    $countryName	= 'Unknown';
		}
		break;
	    }		// Census year

	    case 'Province':
	    {		// province code
		$province	= $value;
		break;
	    }		// province code

	    case 'District':
	    {		// district number
		if (preg_match('/^[0-9]+(\.[05]|)$/', $value) == 1)
		{		// matches pattern of a district number
		    $distId	= $value;
		    if ($distId == floor($distId))
			$distId	= intval($distId);
		}		// matches pattern of a district number
		else
		    $msg .= "District value '$value' invalid. ";
		break;
	    }		// District number

	    case 'NameWidth':
	    {		// explicit width of name column
		$nameWidth	= $value;
		break;
	    }		// explicit width of name column

	    case 'RemarksWidth':
	    {		// explicit width of remarks column
		$remarksWidth	= $value;
		break;
	    }		// explicit width of remarks column

	    case 'FcAuto':
	    {		// automatic update of frame count and page count
		if (strtolower($value) == 'yes')
		    $fcAuto	= true;
		break;
	    }		// automatic update of frame count and page count

	    case 'lang':
	    {		// debug handled by common code
		$lang		= strtolower(substr($value,0,2));
		break;
	    }		// debug handled by common code

	    default:
	    {		// unexpected
		if (strlen($value) > 0)
		{
		    $npuri	.= "{$npand}{$key}={$value}";
		    $npand	= '&amp;'; 
		}
		break;
	    }		// unexpected
	}		// act on parameter name

    }		// foreach parameter

    // if no error messages display the query
    if (strlen($msg) == 0)
    {
	$getParms	= array();
	if ($censusYear == 1851 || $censusYear == 1861)
	    $getParms['d_census']	= $province . $censusYear;
	else
	    $getParms['d_census']	= $censusId;
	$getParms['d_id']	= $distId;
	try {
	    $district		= new District($getParms);

	    $DName		= $district->get('d_name'); 
	    $province		= $district->get('d_province');
	    $prev		= $district->getPrev();
	    if ($prev)
	    {		// there is a previous district
		$prevDist	= $prev->get('d_id');
		if ($prevDist == floor($prevDist))
		    $prevDist	= intval($prevDist);
		$npPrev		.= $npand . 'District=' . $prevDist;
	    }		// there is a previous district
	    $next	= $district->getNext();
	    if ($next)
	    {		// there is a next row
		$nextDist	= $next->get('d_id');
		if ($nextDist == floor($nextDist))
		    $nextDist	= intval($nextDist);
		$npNext	.= $npand . 'District=' . $nextDist;
	    }		// there is a next row

	    // execute the query to get the contents of the page
	    $subdistList	= $district->getSubDistricts();
	}		// valid district number
	catch(Exception $e)
	{		// invalid district number
	    $msg	.= "District number $distId is invalid for the '$censusId' census. ";
	}		// invalid district number

	$domain		= new Domain(array('domain' => 'CA' . $province));
	$provinceName	= $domain->get('name');
    }		// no errors in validation

    // load the results into a parameter array
    if (count($subdistList) > 0)
    {			// page already exists in database
	$line			= 1;
	$prevSubDistrict	= null;
	$data			= array();
	$oldid			= '';
	$oldname		= '';
	$oldlac			= '';
	$oldlds			= '';
	$oldbase		= '';
	foreach($subdistList as $ip => $subDistrict)
	{		// loop through all subdistricts
	    $line		= str_pad($line, 2, "0", STR_PAD_LEFT);
	    $id  		= $subDistrict->get('sd_id');
	    if ($id == $oldid)
		$idclass	= 'same';
	    else
		$idclass	= 'black';
	    $div  		= $subDistrict->get('sd_div');
	    $name 		= $subDistrict->get('sd_name');
	    if ($name == $oldname)
		$nameclass	= 'same';
	    else
		$nameclass	= 'black';
	    $pages  		= $subDistrict->get('sd_pages');
	    $page1 		= $subDistrict->get('sd_page1');
	    $bypage 		= $subDistrict->get('sd_bypage');
	    $population 	= $subDistrict->get('sd_population');
	    $lacreel  		= $subDistrict->get('sd_lacreel');
	    if ($lacreel == $oldlac)
		$lacclass	= 'same';
	    else
		$lacclass	= 'black';
	    $ldsreel  		= $subDistrict->get('sd_ldsreel');
	    if ($ldsreel == $oldlds)
		$ldsclass	= 'same';
	    else
		$ldsclass	= 'black';
	    if ($ldsreel === null || $ldsreel === 'NULL')
		$ldsreel	= 0;
	    $imagebase  	= $subDistrict->get('sd_imagebase');
	    if ($imagebase == $oldbase)
		$baseclass	= 'same';
	    else
		$baseclass	= 'black';
	    $relframe  		= $subDistrict->get('sd_relframe');
	    $framect		= $subDistrict->get('sd_framect');
	    $remarks  		= $subDistrict->get('sd_remarks');

	    $oldid		= $id;
	    $oldname		= $name;
	    $oldlac		= $lacreel;
	    $oldlds		= $ldsreel;
	    $oldbase		= $imagebase;

	    // if requested, calculate the frame count and page count
	    if ($fcAuto && $prevSubDistrict)
	    {		// automatically calculate frame count and page count
		$framect		= $relframe -	
				  $prevSubDistrict->get('sd_relframe');	
		if ($censusYear == 1901 ||
		    $censusYear == 1911)
		    $pages		= $framect;
		else
		if ($censusYear == 1921)
		    $pages		= $framect - 1;
		else
		if (($censusYear == 1851 || $censusYear == 1861))
		    $pages 		= ceil($framect / 2);
		else
		    $pages		= $framect * 2;
		$population		= floor(($pages - 0.5) *
					  $censusRec->get('linesperpage'));
	    }		// automatically calculate frame count and page count

	    // autocorrect population if the current value is the default
	    if ($censusYear == 1861 && $population == 500) 
		$population		= floor(($pages - 0.5) *
					  $censusRec->get('linesperpage'));
	    if ($framect == 0)
	    {			// frame count not initialized yet
		if ($censusYear == 1901 ||
		    $censusYear == 1911 ||
		    $censusYear == 1921 )
		    $framect	= $pages;
		else
		if (($censusYear == 1851 || $censusYear == 1861))
		    $framect	= $pages * 2;
		else
		    $framect	= ceil($pages / 2);
	    }			// frame count not initialized yet
	    if (is_null($relframe))
		$relframe	= 0;

	    $data[]	= array('line'		=> $line,	
				'id'		=> $id,  	
				'idclass'	=> $idclass, 	
				'div'		=> $div,  	
				'name'		=> $name, 	
				'nameclass'	=> $nameclass, 	
				'pages'		=> $pages,  	
				'page1'		=> $page1, 	
				'bypage'	=> $bypage, 	
				'population'	=> $population, 
				'lacreel'	=> $lacreel,  	
				'lacclass'	=> $lacclass, 	
				'ldsreel'	=> $ldsreel,  	
				'ldsclass'	=> $ldsclass, 	
				'imagebase'	=> $imagebase,  
				'baseclass'	=> $baseclass, 	
				'relframe'	=> $relframe,  	
				'framect'	=> $framect,	
				'nameWidth'	=> $nameWidth,
				'remarksWidth'	=> $remarksWidth,
				'remarks'	=> $remarks);  	
	    $line++;
	    $prevSubDistrict	= $subDistrict;
	}		// loop through all subdistricts
    }			// subdistricts already exists in database
    else
    {			// fill in empty district
	$censusYear	= intval(substr($censusId, 2, 4));
	$framect	= floor(500 / $censusRec->get('linesperpage'));

	$lineCt		= 26;	// number of initial entries
	for ($i = 1; $i <= $lineCt; $i++)
	{	// loop through simulated sub-districts
	    // ensure that line number is always 2 digits
	    $line	= (string) $i;
	    if (strlen($line) == 1)
		$line	= "0".$line;
	    if ($censusYear < 1906)
		$sd_id	= substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				 $i - 1, 1);
	    else
		$sd_id	= $i;

	    $data[]	= array('line'		=> $line,	
				'id'		=> $sd_id,  	
			        'idclass'	=> 'black',	
				'div'		=> '',  	
				'name'		=> 'SubDistrict ' . $sd_id,
			        'nameclass'	=> 'black',	
				'pages'		=> '10',  	
				'page1'		=> '1', 	
				'bypage'	=> '1', 	
				'population'	=> 475, 
				'lacreel'	=> 'C-9999',  	
			        'lacclass'	=> 'same',	
				'ldsreel'	=> 0,  	
			        'ldsclass'	=> 'same',	
				'imagebase'	=> '0',
			        'baseclass'	=> 'same',	
				'relframe'	=> '0',  	
				'framect'	=> $framect,	
				'nameWidth'	=> $nameWidth,
				'remarksWidth'	=> $remarksWidth,
				'remarks'	=> '');
	}		// loop through simulated sub-districts
    }			// fill in empty district

    // parameters to ReqUpdateSubDists.html
    $search	= "?Census=$censusId&amp;Province=$province&amp;District=$distId&amp;lang=$lang";

    // notify the invoker if they are not authorized
    $title	= "Census Administration: $countryName: $censusYear Census: Sub-District Table";

    $tempBase		= $document_root . '/templates/';
    $template		= new FtTemplate("${tempBase}page$lang.html");
    if ($update)
	$action		= 'Update';
    else
	$action		= 'Display';
    if ($censusYear < 1867)
    {			// pre-confederation
	$includeSub	= "SubDistFormPre$action$lang.html";
	if (!file_exists($tempBase . $includeSub))
	{		// language not supported
	    $includeSub	= "SubDistFormPre{$action}en.html";
	}		// language not supported
    }			// pre-confederation
    else
    {			// pre-confederation
	$includeSub	= "SubDistForm$action$lang.html";
	if (!file_exists($tempBase . $includeSub))
	{		// language not supported
	    $includeSub	= "SubDistForm{$action}en.html";
	}		// language not supported
    }			// pre-confederation
    $template->includeSub($tempBase . $includeSub, 'MAIN');

    $template->set('CENSUSYEAR', 	$censusYear);
    $template->set('CC',		$cc);
    $template->set('COUNTRYNAME',	$countryName);
    $template->set('CENSUSID',		$censusId);
    $template->set('PROVINCE',		$province);
    $template->set('PROVINCENAME',	$provinceName);
    $template->set('DISTID',		$distId);
    $template->set('DNAME',		$DName);
    $template->set('SEARCH',		$search);
    $template->set('CONTACTTABLE',	'SubDistricts');
    $template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
    $linkHdr	= $template->getElementById('linkHdr');

    if (strlen($npPrev) > 0)
    {
	$template->updateTag('npPrevFront', array('npPrev' => $npuri . $npPrev));
	$template->updateTag('npPrevBack', array('npPrev' => $npuri . $npPrev));
    }
    if (strlen($npNext) > 0)
    {
	$template->updateTag('npNextFront', array('npNext' => $npuri . $npNext));
	$template->updateTag('npNextBack', array('npNext' => $npuri . $npNext));
    }
    $template->updateTag('Row$line',
			 $data);
    $template->display();
    showTrace();
