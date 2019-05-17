<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genCensuses.php														*
 *																		*
 *  Display a web page containing information on Censuses for a country *
 *  recorded in the database.											*
 *																		*
 *  History:															*
 *		2010/08/23		use new layout									*
 *						make output conditional on authorization		*
 *		2010/09/03		Add update 1851 census transcription			*
 *		2010/11/17		add help										*
 *		2011/04/28		use CSS rather than tables for layout of header	*
 *						and trailer										*
 *		2011/07/07		add pointer to 1842 images						*
 *		2011/09/01		report overall progress of transcriptions		*
 *		2011/09/25		add 1906 census implementation					*
 *		2011/10/19		remove redundant image tools					*
 *		2012/01/24		use default.js									*
 *		2012/11/14		in census statistics for pre-confederation		*
 *						census only include count for specified colony	*
 *		2013/01/26		table SubDistTable renamed to SubDistricts		*
 *		2013/04/05		use functions pageTop and pageBot to standardize*
 *		2013/04/13		permit viewing management tables				*
 *		2013/04/14		LAC databases moved and changed					*
 *		2013/05/08		use common script ReqUpdate.php for census		*
 *						update											*
 *		2013/06/16		LAC moved 1891 census, left no forwarding		*
 *						address											*
 *		2013/08/17		add support for 1921 census						*
 *		2013/08/28		add support for 1861 census at LAC				*
 *		2013/12/05		support parameter debug							*
 *						handle lack of database server gracefully		*
 *						move to subdirectory database					*
 *						use common list of supported census identifiers	*
 *						accumulate statistics for pre-confed censuses	*
 *		2013/12/18		missing close <a> tag							*
 *		2015/01/20		add ability to query all census tables			*
 *		2015/03/08		change URL and description for LAC 1851 census	*
 *		2015/04/19		change URL for LAC 1901 census					*
 *		2015/06/02		change links to management for districts,		*
 *						sub-districts, and pages to PHP					*
 *						add support for 1831 Census of Quebec			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		ReqUpdatePages moved to .php					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/11/17		improve performance by getting stats from		*
 *						Districts table									*
 *		2016/01/21		add debug trace output							*
 *						include http.js before util.js					*
 *						add Edit Censuses								*
 *						use class Census to get census information		*
 *		2016/12/28		support pre-confederation censuses other than	*
 *						Canada West										*
 *		2017/09/12		use get( and set(								*
 *		2017/09/15		use class Template								*
 *		2017/10/16		use class CensusSet								*
 *		2017/12/01		get statistics more efficiently					*
 *		2018/01/04		remove Template from template file names		*
 *		2019/02/19      use new FtTemplate constructor                  *
 *																		*
 *  Copyright 2018 &copy; James A. Cobban								*
 ***********************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/CountryName.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		open code														*
 ***********************************************************************/
// default values of parameters
$cc			        = 'CA';
$countryName	    = 'Canada';
$lang		        = 'en';		// default english

// determine which districts to display
foreach ($_GET as $key => $value)
{			// loop through all parameters
	switch(strtolower($key))
	{
	    case 'cc':
	    case 'countrycode':
        {
			$cc		        = strtoupper($value);
			if ($cc == 'UK')
			    $cc		    = 'GB';
			break;
	    }

	    case 'lang':
        {		// debug handled by common code
            if (strlen($value) >= 2)
			    $lang		    = strtolower(substr($value,0,2));
			break;
	    }		// debug handled by common code

	}		// switch on parameter name
}			// foreach parameter

// initialize template
if (canUser('edit'))
	$action		    = 'Update';
else
	$action		    = 'Display';
$tempBase		    = $document_root . '/templates/';
$baseName		    = "genCensuses$action{$cc}en.html";
if (file_exists($tempBase . $baseName))
    $includeSub		= "genCensuses$action$cc$lang.html";
else
    $includeSub		= "genCensuses{$action}__$lang.html";
$template		= new FtTemplate($includeSub);

// initialize substitution values
$languageObj		= new Language(array('code' => $lang));
$template->set('LANGUAGE', $languageObj->get('name'));
$countryNameObj		= new CountryName(array('cc' => $cc, 'lang' => $lang));
$countryName		= $countryNameObj->getName();
$article			= $countryNameObj->get('article');
$possessive			= $countryNameObj->get('possessive');
$update		    	= canUser('edit');

// get statistics
$cenpop		    	= array();
$cendone			= array();
$getParms			= array('countrycode'	=> $cc,
							'collective'	=> 0);
$censuses			= new CensusSet($getParms);
$query		        = "SELECT D_Census, SUM(D_Transcribed) AS transcribed, SUM(D_Population) AS Population " .
                    "FROM Districts LEFT JOIN Censuses ON CensusId=D_Census " .
                        "WHERE LEFT(CensusId,2)=:cc OR PartOf=:cc " .
                        "GROUP BY D_Census " .
                        "ORDER BY RIGHT(D_Census,4), LEFT(D_Census,2)";

$stmt	            = $connection->prepare($query);
$sqlParms           = array('cc' => $cc);
$queryText          = debugPrepQuery($query, $sqlParms);
if ($stmt->execute($sqlParms))
{  		// success
    if ($debug)
		$warn	.= "<p>genCensuses.php: " . __LINE__ . " $query</p>\n";
    $statistics		= $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($statistics as $row)
    {
		$censusid	= $row['d_census'];
		$cenyear	= intval(substr($censusid, 2));
		if (array_key_exists($cenyear, $cendone))
		    $cendone[$cenyear]	+= $row['transcribed'];
		else
		    $cendone[$cenyear]	= $row['transcribed'];
		if (array_key_exists($cenyear, $cenpop))
		    $cenpop[$cenyear]	+= $row['population'];
		else
		    $cenpop[$cenyear]	= $row['population'];
    }		// success
}		// loop through each census
else
{
    $msg	.= "query='$queryText': message=" .
					print_r($stmt->errorInfo(), true) . ". ";
}		// error on request

$title		= $countryName . ": Censuses";

foreach($cendone as $year => $value)
	$template->set('CENDONE' . $year, number_format(floatval($value)));
foreach($cenpop as $year => $value)
	$template->set('CENPOP' . $year, number_format(floatval($value)));
$template->set('TITLE',		$title);
$template->set('COUNTRYNAME',	$countryName);
$template->set('ARTICLE',		$article);
$template->set('POSSESSIVE',	$possessive);
$template->set('CC',		$cc);
$template->set('CONTACTSUBJECT',	$_SERVER['REQUEST_URI']);

$template->display();
