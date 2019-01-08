<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdateDists.php													*
 *																		*
 *  Request to update or view a portion of the Districts table.			*
 *																		*
 *  History:															*
 *		2010/11/22		created											*
 *		2010/11/24		link to help page								*
 *		2011/06/27		add support for 1916							*
 *		2013/04/13		support being invoked without edit				*
 *						authorization better							*
 *						change to PHP									*
 *		2013/08/17		add support for 1921							*
 *		2013/09/04		pass full census identifiers for post 1867		*
 *		2013/09/05		validate Census parameter						*
 *		2013/11/16		gracefully handle lack of database server		*
 *						connection										*
 *		2013/12/28		use CSS for layout								*
 *		2015/06/02		display warning messages						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/10		escape province names							*
 *		2016/01/20		add id to debug trace div						*
 *						use class Census to get census information		*
 *						built selection list dynamically from database	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/15		use class Template								*
 *		2017/11/04		$provinces erroneously set to empty array		*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/11		htmlspecchars moved to Template class			*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		$censusList														*
 *																		*
 *		List of censuses to choose from									*
 ************************************************************************/
$censusList		= array();
$getParms		= array('partof'	=> null);
$censuses		= new CensusSet($getParms);
foreach($censuses as $censusRec)
{
	$censusList[$censusRec->get('censusid')] =
					array(	'id'		=> $censusRec->get('censusid'),
						    'name'		=> $censusRec->get('name'),
						    'selected'	=> '');
}

$censusId				= '';
$censusYear				= '';
$provinces				= '';
$cc					    = 'CA';
$countryName			= 'Canada';
$province				= 'CW';
$lang				    = 'en';

foreach($_GET as $key => $value)
{		// loop through parameters
	switch(strtolower($key))
	{
	    case 'census':
	    {
			// support old parameter value
			if (strlen($value) == 4)
			{
			    $censusId	= 'CA' . $value;
			    $censusYear	= $value;
			}
			else
			    $censusId	= $value;

			// validate
			$censusRec	= new Census(array('censusid'	=> $censusId));
			if ($censusRec->isExisting())
			{
			    $provinces	= $censusRec->get('provinces');
			    $censusList[$censusId]['selected'] = "selected='selected'";
			}
			else
			{
			    $warn	.= "<p>Census '$censusId' is unsupported</p>\n";
			    $provinces	= '';
			}
			$cc		= substr($censusId, 0, 2);
			$censusYear	= substr($censusId, 2);
			$country	= new Country(array('code' => $cc));
			$countryName	= $country->get('name');
			if ($cc == 'QC')
			    $province	= 'QC';
			break;
	    }

	    case 'province':
	    {			// province code
			$province	= $value;
			if (strlen($value) == 2)
			{
			    $pos	= strpos($provinces, $value);
			    if ($pos === false && ($pos & 1) == 1)
					$msg	.= "Invalid value '$value' for Province. ";
			}
			else
			if (strlen($value) != 0)
			    $msg	.= "Invalid value '$value' for Province. ";
			break;
	    }			// province code

	    case 'lang':
        {
            if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }			// language code

	}	// act on specific parameters
}		// loop through parameters

// notify the invoker if they are not authorized
$update	            = canUser('edit');
$tempBase	        = $document_root . '/templates/';
$template	        = new FtTemplate("${tempBase}page$lang.html");
$includeSub	        = "ReqUpdateDists$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	    = 'ReqUpdateDistsen.html';
}
$template->includeSub($tempBase . $includeSub,
	            	  'MAIN');

$template->set('CENSUSYEAR', 	$censusYear);
$template->set('COUNTRYNAME',	$countryName);
$template->set('CENSUSID',		$censusId);
$template->set('PROVINCE',		$province);
$template->set('CONTACTTABLE',	'Districts');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->updateTag('censusOpt', $censusList);
if ($censusYear > 1867)
{			// post confederation census
	if ($province == 'CW' || $province == '')
	    $template->updateTag('allProvincesOpt',
					     array('selected' => "selected='selected'"));
	else
	    $template->updateTag('allProvincesOpt',
					     array('selected' => ''));
}
else
	$template->updateTag('allProvincesOpt', null);

$getParms	= array('cc' => $cc);
$domains	= new DomainSet($getParms);
$provArray	= array();
for ($ip = 0; $ip < strlen($provinces); $ip = $ip + 2)
{			// loop through all provinces
	$pc			= substr($provinces, $ip, 2);
	$domainObj		= $domains[$pc];
	$provinceName	= $domainObj->get('name');
	$pname		= $provinceName;
	if ($pc == $province)
	    $seld		= "selected='selected'";
	else
	    $seld		= '';
	$provArray[$pc]	= array('pc'		=> $pc,
						    'name'		=> $pname,
						    'selected'	=> $seld);
}
$template->updateTag('provinceOpt', $provArray);
$template->display();
showTrace();
