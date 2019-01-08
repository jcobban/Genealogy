<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusUpdateStatus.php												*
 *																		*
 *  Display the progress of the transcription of a specific census		*
 *																		*
 *  History:															*
 *		2010/09/12		Reformat to new page layout.					*
 *		2010/11/19		Use common MDB2 database connection				*
 *						increase separation of HTML and PHP				*
 *						add error checking								*
 *		2011/01/22		add link to help page							*
 *		2011/09/27		add support for 1916 census						*
 *						simplify census year validation					*
 *		2011/09/25		correct URL for update/query form in header		*
 *						and trailer										*
 *		2011/10/16		provide links to preceding and following		*
 *						census years									*
 *		2011/12/10		use HTML <button>								*
 *		2012/09/17		Census parameter contains census identifier		*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/04/14		use pageTop and PageBot to standardize page		*
 *						layout											*
 *		2013/05/23		add button to display surnames					*
 *		2013/06/11		correct URL for requesting next page to edit	*
 *		2013/07/15		correct URL for requesting next page to edit	*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *		2013/12/28		use CSS for layout								*
 *						add thead, tbody, tfoot to display table		*
 *		2014/06/22		add help balloons for District and Province		*
 *		2014/06/27		add help for district name, done, and todo		*
 *						columns, and correctly position help balloons	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/08		include CommonForm.js							*
 *						add support for 1831 census of Quebec			*
 *						correct ordering of censuses by province		*
 *						popup census and province name for links		*
 *		2015/08/21		do not issue error message for missing province	*
 *						and display total stats for country				*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						display French name of district					*
 *		2015/11/18		get stats from Districts table					*
 *		2015/12/10		initial support for non-Canadian censuses		*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *						display all provinces for collective censuses	*
 *						use class Census to access census information	*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/12/10		handle province not initialized					*
 *		2017/02/07		use class Country								*
 *		2017/09/05		correct alignment of summary row				*
 *						use Domain object								*
 *		2017/09/12		use get( and set(								*
 *		2017/10/17		use class CensusSet								*
 *		2017/10/30		use composite cell style classes				*
 *		2017/11/24		use Template									*
 *						use prepared statements							*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/11		htmlspecchars moved to Template class			*
 *		2018/01/29		use class FtTemplate							*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// general state variables
$cc					    = 'CA';
$countryName			= 'Canada';
$censusId				= null;
$censusYear				= 0;
$province				= null;
$censusRec				= null;
$lang				    = 'en';

// Validate parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{
	    case 'census':
	    {
			$censusId	        = $value;
			if (strlen($censusId) == 4)
			{
			    $censusYear		= intval($censusId);
			    $censusId		= 'CA' . $censusId;
			}
			else
			if (strlen($censusId) == 6)
			{
			    $cc			    = substr($censusId, 0, 2);
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->get('name');
			    $censusYear		= intval(substr($censusId, 2));
			    if ($censusYear < 1867 && substr($censusId,0,2) != 'CA')
					$province	= substr($censusId,0,2);
			}
			$censusRec	= new Census(array('censusid'	=> $censusId));
			if ($censusRec->isExisting())
			{
			    $partOf	= $censusRec->get('partof');
			    if (strlen($partOf) == 2)
			    {
					$cc		= $partOf;
					$countryObj	= new Country(array('code' => $cc));
					$countryName	= $countryObj->getName();
			    }
			}		// valid census id
			else
			{
			    $warn	.= "<p>Census='$censusId' is not supported.</p>\n";
			}
			break;
	    }			// Census

	    case 'province':
	    {
			$province	= $value;
			break;
	    }			// Province

	    case 'lang':
	    {			// language code
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
			break;
	    }			// language code

	}			// switch on parameter name
}				// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (is_null($censusId))
{		// missing parameter
	$censusId	= 'Unknown';
	$msg		.= "Missing Census parameter. ";
}		// missing parameter
else
if ($censusYear > 1850 && $censusYear < 1867)
{		// post Durham, pre-confederation
	$preConfed	= true;
	if ($province == 'ON')
	    $province	= 'CW';
	else
	if ($province == 'QC')
	    $province	= 'CE';
}		// post Durham, pre-confederation
else
if ($censusYear >= 1867)
{		// post-confederation
	$preConfed	= false;
	if ($province == 'CW')
	    $province	= 'ON';
	else
	if ($province == 'CE')
	    $province	= 'QC';
}		// post-confederation
else
	$preConfed	= false;

// obtain name of province from code
if (is_null($province))
	$provinceName	= 'National';
else			// translate province code to name
{
	$domainObj	= new Domain(array('domain'	=> $cc . $province,
							   'language'	=> 'en'));
	if ($domainObj->isExisting())
	    $provinceName	= $domainObj->get('name');
	else
	    $provinceName	= $province;
}

// get previous and subsequent census ids
$prevCensus		= '';
$prevName		= '';
$prevProv		= '';
$nextCensus		= '';
$nextName		= '';
$nextProv		= '';
if ($censusRec)
{				// have a valid census id
	$getParms	= array('collective'	=> 0);	// all real censuses
	$list		= new CensusSet($getParms);
	$prevRec	= null;
	$nextRec	= null;
	$stopNext	= false;
	foreach($list as $crec)
	{			// search for current entry in full list
	    if ($stopNext)
	    {
			$nextCensus	= $crec->get('censusid');
			break;
	    }
	    if ($crec->get('censusid') == $censusId)
	    {			// found current entry
			$prevCensus	= $prevRec->get('censusid');
			$prevProv	= $prevRec->get('prov');
			$prevName	= $prevRec->get('prov');
			$stopNext	= true;
	    }			// found current entry
	    $prevRec		= $crec;
	}			// search for current entry in full list

	$provinces	= $censusRec->get('provinces');
	if (is_string($province) && strlen($province) == 2)
	{			// province specified
	    $poff		= strpos($provinces, $province);
	    if (is_int($poff))
	    {			// valid province
			if ($poff > 0)
			{		// not first province 
			    $prevProv	= substr($provinces, $poff - 2, 2);
			    $prevName	= $prevProv;
			    $prevCensus	= $censusId;	// same census
			}		// not first province 
			else
			{		// at first province in census
			    if ($prevRec)
			    {		// last province in previous census
					$prevProv	= $prevRec->get('provinces');
					$prevProv	= substr($prevProv,
								 strlen($prevProv) - 2);
					$prevName	= $prevProv;
			    }		// last province in previous census
			}		// at first province

			if ($poff < strlen($provinces) - 2)
			{		// go to next province in census
			    $nextProv	= substr($provinces, $poff + 2, 2);
			    $nextName	= $nextProv;
			    $nextCensus	= $censusId;	// same census
			}		// go to next province in census
			else
			{		// go to first province of next census
			    if ($nextRec)
			    {		// there is a next census
					$nextProv	= $nextRec->get('provinces');
					$nextProv	= substr($nextProv, 0, 2);
					$nextName	= $nextProv;
			    }		// there is a next census
			}		// go to first province of next census
	    }			// valid province
	}			// province specified
	else
	{			// province not specified
	    if ($prevRec)
	    {		// last province in previous census
			$prevProv	= $prevRec->get('provinces');
			$prevProv	= substr($prevProv,
							 strlen($prevProv) - 2);
			$prevName	= $prevProv;
	    }		// last province in previous census
	    if ($nextRec)
	    {		// there is a next census
			$nextProv	= substr($nextRec->get('provinces'), 0, 2);
			$nextName	= $prevProv;
	    }		// there is a next census
	}			// province not specified
}				// have a valid census id

$total2do	= 0;

// some actions depend upon whether the user can edit the database
if (canUser('edit'))
{		// user can update database
	$searchPage	= 'ReqUpdate.php?Census=' . $censusId;
	if (!is_null($province))
	    $searchPage	.= "&amp;Province=$province";
	$action		= 'Update';
}		// user can updated database
else
{		// user can only view database
	$searchPage	= 'QueryDetail' . $censusYear . '.html';
	if (!is_null($province))
	    $searchPage	.= "?Province=$province";
	$action		= 'Query';
}		// user can only view database

if (strlen($msg) == 0)
{		// OK
	// variables for constructing the SQL SELECT statement

	$and	= ' AND ';	// logical and operator in SQL expressions
	$tbls	= "";
	if (!is_null($province))
	{		// provincial summary
	    $result	= new RecordSet('Districts',
							array('census'		=> $censusId,
							      'province'	=> $province));
	}		// provincial summary
	else
	{		// national summary
	    $result	= $censusRec->getStats();	// not used here
	}		// national summary
}		// OK

if (is_null($province))
	$title	= "$censusYear Census of $countryName: National Transcription Status";
else
	$title	= "$censusYear Census of $countryName: $provinceName Transcription Status";

$tempBase	= $document_root . '/templates/';
$template	= new FtTemplate("${tempBase}page$lang.html");
if (is_null($province))
{
	$includeFile	= "CensusUpdateStatusNational$lang.html";
	if (!file_exists($tempBase . $includeFile))
	{
	    $includeFile	= 'CensusUpdateStatusNationalen.html';
	    $language		= new Language(array('code' => $lang));
	    $warn		.= "<p>This page does not support " .
						   $language->get('name') . '/' .
						   $language->get('nativename') ."</p>\n";
	}
}
else
{
	$includeFile	= "CensusUpdateStatusProvincial$lang.html";
	if (!file_exists($tempBase . $includeFile))
	{
	    $includeFile	= 'CensusUpdateStatusProvincialen.html';
	    $language		= new Language(array('code' => $lang));
	    $warn		.= "<p>This page does not support " .
						   $language->get('name') . '/' .
						   $language->get('nativename') ."</p>\n";
	}
}
$template->includeSub($tempBase . $includeFile,
					  'MAIN');
$template->set('TITLE',	 		    $title);
$template->set('CENSUSYEAR', 		$censusYear);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CC', 			    $cc);
$template->set('CENSUSID',			$censusId);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('ACTION',			$action);
$template->set('LANG',			    $lang);
$template->set('CENSUS',			$censusYear);
$template->set('SEARCH',			$searchPage);
$template->set('CONTACTTABLE',		'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

// update popup link information
$template->updateTag('mouseprevCensusLink',
					     array('prevCensus'	=> $prevCensus,
						   'prevName'	=> $prevName));
$template->updateTag('mousenextCensusLink',
					     array('nextCensus'	=> $nextCensus,
						   'nextName'	=> $nextName));

if (strlen($msg) > 0)
{		// errors
	$template->updateTag('displayForm', null);
}		// errors
else
if ($censusRec && is_null($province))
{		// national report
	$cc		= substr($censusId, 0, 2);
	$provs		= $censusRec->get('provinces');
	$provArray	= array();
	for ($i = 0; $i < strlen($provs); $i += 2)
	{	// loop through provinces
	    $provcode	= substr($provs, $i, 2);
	    $domain	= new Domain(array('domain'	=> $cc . $provcode,
							   'language'	=> 'en'));
	    $provinceName	= $domain->get('name');
	    if ($preConfed && $cc == 'CA')
			$link	= "/database/CensusUpdateStatus.php?Census=$provcode$censusYear&amp;Province=$provcode&amp;lang=$lang";
	    else
			$link	= "/database/CensusUpdateStatus.php?Census=$censusId&amp;Province=$provcode&amp;lang=$lang";
	    $percent	= 100 * $result['total'] / $result['pop'];
	    $provArray[$provcode]	=
			array('provcode'	=> $provcode,
			      'ProvinceName'	=> $provinceName,
			      'CensusId'	=> $censusId,
			      'link'		=> $link);
	}		// loop through provinces
	$template->updateTag('provInfo', $provArray);
	$template->updateTag('natStats',
			array('total'		=> number_format($result['total']),
			      'pop'		=> number_format($result['pop']),
			      'percent'		=> number_format($percent,2)));
}		// national report
else
{		// provincial report
	// update forward and backward link arrows
	if (strlen($prevCensus) > 0)
	    $template->updateTag('prevCensusLink',
						 array('prevCensus'	=> $prevCensus,
						       'prevProv'	=> $prevProv,
						       'prevName'	=> $prevName));
	else
	    $template->updateTag('prevCensusLink',
						 null);
	if (strlen($nextCensus) > 0)
	    $template->updateTag('nextCensusLink',
						 array('nextCensus'	=> $nextCensus,
						       'nextProv'	=> $nextProv,
						       'nextName'	=> $nextName));
	else
	    $template->updateTag('nextCensusLink',
						 null);
	// display the results
	$even		= false;
	$total		= 0;
	$ir		= 0;

	foreach($result as $row)
	{		// loop through the records
	    // prepare fields for presentation in HTML
	    $total		+= $row->get('transcribed');
	    $total2do		+= $row->get('population');
	    if ($row->get('transcribed') == 0)
			$pctDone	= 0;
	    else
			$pctDone	= 100*$row->get('transcribed')/$row->get('population');
	    $row['transcribed']	= number_format($row->get('transcribed'));
	    $row['population']	= number_format($row->get('population'));
	    $row['pctdone']	= number_format($pctDone, 2);
	    $row['pctclass']	= pctClass($pctDone);
	    if ($even)
	    {
			$row['rowclass']	= 'even';
			$even			= false;
	    }
	    else
	    {
			$row['rowclass']	= 'odd';
			$even			= true;
	    }
	}		// process all rows

	$template->updateTag('row$id', $result);

	// display total population
	if ($total2do <= 0)
	    $total2do	= 999999;	// prevent divide by zero
	$pctDone	= 100*$total/$total2do;
	$template->set('total', number_format($total));
	$template->set('total2do', number_format($total2do));
	$template->set('pctDone', number_format($pctDone, 2));
	$template->set('pctDoneClass', pctClass($pctDone));
}		// provincial report
$template->display();
