<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DomainsEdit.php														*
 *																		*
 *  Display form for editting information about administrative			*
 *  domains for managing vital statistics records.						*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain	2 letter country code + 2/3 letter state/province code	*
 *																		*
 *  History:															*
 *		2016/05/20		created											*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/02/07		use class Country								*
 *		2017/08/13		correct display of country name					*
 *						add header link to services menu				*
 *		2017/12/05		correct order of options in language selection	*
 *		2018/01/04		remove Template from template file names		*
 *		2018/02/02		page through results if more than limit			*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/DomainSet.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc			    	= 'CA';
$countryName		= 'Canada';
$domainType			= 'Province';
$lang		    	= 'en';
$offset		    	= 0;
$limit		    	= 20;
$parmsDebug			= '';
$newCountry			= false;
$parmsText      	= "<p class='label'>\$_REQUEST</p>\n" .
                          "<table class='summary'>\n" .
                            "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
foreach($_REQUEST as $key => $value)
{
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	$matches	= array();
	if (preg_match("/[A-Z]+$/", $key, $matches))
	{
	    $code	= $matches[0];
	    $key	= strtolower(substr($key, 0, strlen($key) - strlen($code)));
	    if ($key == 'lang')
			$key	= 'rowlang';
	}
	else
	{
	    $code	= '';
	    $key	= strtolower($key);
	}

	switch($key)
	{
	    case 'cc':
	    {
			if (preg_match('/^[a-zA-Z]{2}$/', $value) == 1)
			{
			    $cc			= $value;
			}
			break;
	    }		// country code

	    case 'lang':
	    case 'language':
	    {
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
			break;
	    }		// language code

	    case 'code':
	    {
			if (substr($value, 0, 2) != $cc)
			{
			    $newCountry		= true;
			    break;	// have switched countries
			}
			$newCode		= $value;
			$domain		    = new Domain(array('domain'	=> $code,
							            	   'language'	=> $lang));
			if ($newCode != $code)
			{		// user has changed the code
			    $chkdomain	= new Domain(array('domain'	=> $newCode,
					            			   'language'	=> $lang));
			    if ($chkdomain->isExisting())
			    {		// duplicates existing record
					$warn	.= "<p>You cannot change the code from '$code' to '$newCode' because that value is already in use.</p>\n";
			    }		// duplicates existing record
			    else
			    {		// change the code
					$domain->set('domain', $newCode);
			    }		// change the code
			}		// user has changed the code
			break;
	    }			// record identifier, domain code

	    case 'rowlang':
	    {
			$rowlang	        = $value;
			break;
	    }

	    case 'name':
	    {			// name of domain
			if ($newCountry || $rowlang != $lang)
			    break;
			if (strlen($value) == 0)
			{
			    $domain->delete(false);
			}
			else
			{
			    $domain->set('name', $value);
			    $domain->save(null);
			}
			break;
	    }			// name of domain

	    case 'resourcesurl':
	    {			// name of domain
			if ($newCountry || $rowlang != $lang)
			    break;
			$domain->set('resourcesurl', $value);
			$domain->save(null);
			break;
	    }			// name of domain

	    case 'offset':
	    {
			if (strlen($value) > 0 && ctype_digit($value))
			    $offset	        = $value;
			break;
	    }

	    case 'limit':
	    {
			if (strlen($value) > 0 && ctype_digit($value))
			    $limit	        = $value;
			break;
	    }

	}		// check supported parameters
}			// loop through all parameters
if ($debug)
{			// ensure listing of parameters not interrupted
	$warn	.= $parmsText . "  </table>\n";
}			// ensure listing of parameters not interrupted

$countryObj		= new Country(array('code' => $cc));
$countryName	= $countryObj->getName($lang);
if ($cc != 'CA')
	$domainType	= 'State';


if (strlen($msg) == 0)
{			// no errors detected
	// create an array of country information for select <options>
	$countrySet	= new RecordSet('Countries');
	foreach ($countrySet as $code => $countryObj)
	{
	    if ($code == $cc)
			$countryObj['selected']	= ' selected="selected"';
	    else
			$countryObj['selected']	= '';
	}

	// create an array of language information for select <options>
	$languageSet	= new RecordSet('Languages');
	foreach ($languageSet as $code => $languageObj)
	{
	    if ($code == $lang)
			$languageObj['selected']	= ' selected="selected"';
	    else
			$languageObj['selected']	= '';
	}

	// get the set of administrative domains for the country
	$getParms		= array('cc'		=> $cc,
							'language'	=> $lang,
							'order'		=> 'Name',
							'offset'	=> $offset,
							'limit'		=> $limit);
	$domains		= new DomainSet($getParms);
	$information	= $domains->getInformation();
	$totcount		= $information['count'];
	$count			= $domains->count();

	if ($totcount == 0 && strtolower($lang) != 'en')
	{		// get domains in default language
	    $getParms		= array('cc'		=> $cc,
							    'order'		=> 'Name',
							    'offset'	=> $offset,
							    'limit'		=> $limit);
        $domains		= new DomainSet($getParms);
        foreach($domains as $domain)
            $domain->set('lang', $lang);
	    $information	= $domains->getInformation();
	    $totcount		= $information['count'];
	    $count		    = $domains->count();
	}		// get domains in default language

	if ($totcount == 0)
	{		// no existing defined domains
	    $docodes		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    $totcount		= min(26, $limit);
	    $count	    	= $totcount;
	    for($do = 0; $do < $totcount; $do++)
	    {
			$code		= 'A' . substr($docodes,$do,1);
			$domains[$code]	= new Domain(array('domain'	    => $cc . $code,
								               'language'	=> $lang,
                                               'name'	    => $code));
            $domains[$code]->set('lang', $lang);
	    }
	}		// no existing defined domains
}			// no errors detected
else
{
	$domains	= array();
}

if (canUser('edit'))
	$action		= 'Update';
else
	$action		= 'Display';
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= 'DomainsEdit' . $action .  $cc . $lang . '.html';
if (!file_exists($tempBase . $includeSub))
{			// try English
	$includeSub	= 'DomainsEdit' . $action . $cc .'en.html';
	if ($lang != 'en')
	{
	    $language	= new Language(array('code'	=> $lang));
    	$langName	= $language->get('name');
    	$nativeName	= $language->get('nativename');
    	$sorry  	= $language->getSorry();
        $warn   	.= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	}

	if (!file_exists($tempBase . $includeSub))
	{		// no country specific template
	    $includeSub	= 'DomainsEdit' . $action . 'CAen.html';
	}		// no country specific template
}			// Try English
$gotPage	= $template->includeSub($tempBase . $includeSub,
						    		'MAIN');

$template->set('CONTACTTABLE',	    'Domains');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('CC',	            $cc);
$template->set('COUNTRYNAME',	    $countryName);
$template->set('DOMAINTYPE',	    $domainType);
$template->updateTag('countryOpt',
					 $countrySet);
$template->updateTag('languageOpt',
					 $languageSet);

if (($offset - $limit) >= 0)
	$template->updateTag('npPrevFront',
					     array('cc'		=> $cc,
		    				   'lang'	=> $lang,
		    				   'offset'	=> $offset - $limit,
		    				   'limit'	=> $limit));
else
	$template->updateTag('npPrevFront', null);
		
if (($offset + $limit) < $totcount)
	$template->updateTag('npNextFront',
					     array('cc'		=> $cc,
		    				   'lang'	=> $lang,
		    				   'offset'	=> $offset + $limit,
			    			   'limit'	=> $limit));
else
	$template->updateTag('npNextFront', null);

$template->updateTag('respdescrows',
					 array('first'		=> $offset,
					       'last'		=> min($totcount, $offset+$limit),
					       'totalrows'	=> $totcount));

$template->updateTag('Row$code',
					 $domains);
$template->display();
