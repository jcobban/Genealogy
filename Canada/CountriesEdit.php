<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountriesEdit.php													*
 *																		*
 *  Display form for editting information about 						*
 *  countries for managing record transcriptions.						*
 *																		*
 *  History:															*
 *		2017/02/04		created											*
 *		2017/02/07		use class Country								*
 *		2017/02/25		use class Template								*
 *		2018/01/04		remove "Template" from template file names		*
 *		2018/01/22		display only part of the table at a time		*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms		= array();
$pattern		= '';
$lang		    = 'en';
$offset		    = 0;
$limit		    = 20;

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{			// method='get'
	$parmsText		= "<table>";
	foreach($_GET as $key => $value)
	{			// loop through parameters
	    $parmsText	.= "<tr><th>$key</th><td>$value</td></tr>\n";
	    switch(strtolower($key))
	    {
			case 'lang':
			{
			    if (preg_match("/^[a-zA-Z]{2}$/", $value))
					$lang		= strtolower(substr($value,0,2));
			    break;
			}		// language

			case 'pattern':
			{
			    $pattern		= $value;
			    $getParms['name']	= $value;
			    break;
			}		// pattern match

			case 'offset':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$offset		= $value;
			    break;
			}

			case 'limit':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$limit		= $value;
			    break;
			}
	    }		// act on specific parameters
	}		    // loop through parameters
	if ($debug)
	    $warn	.= $parmsText . "</table>";
}		    	    // method='get'
else
if (isset($_POST) && count($_POST) > 0)
{		            // when submit button is clicked invoked by method='post'
	$parmsText		= "<table>";
	$country		= null;
	foreach($_POST as $key => $value)
	{
	    $parmsText	.= "<tr><th>$key</th><td>$value</td></tr>\n";
	    $matches	= array();
	    $pres	= preg_match("/[A-Z]{2}$/", $key, $matches);
	    if ($pres)
	    {			// last 2 characters are upper case
			$code	= $matches[0];
			$key	= substr($key, 0, strlen($key) - 2);
	    }			// last 2 characters are upper case
	    else
	    {
			$code	= '';
	    }

	    switch(strtolower($key))
	    {
			case 'lang':
			{
			    if (preg_match("/^[a-zA-Z]{2}$/", $value))
					$lang	= strtolower(substr($value,0,2));
			    break;
			}		// language

			case 'code':
			{
			    if ($country)
					$country->save(null);
			    if (strlen($value) != 2)
			    {
					$warn	.= $parmsText . "</table>";
					$warn	.= "<p>CountriesEdit.php: " . __LINE__ .
							" invalid value of code='$value'</p>";
			    }
			    $code	= strtoupper($value);
			    $country	= new Country(array('code'	=> $code));
			    break;
			}

			case 'name':
			{
			    $country->set('name', $value);
			    break;
			}

			case 'dialingcode':
			{
			    if (preg_match("/^[0-9]+$/", $value))
			    {
			        $country->set('dialingcode', $value);
			    }
			    break;
			}

			case 'currency':
			{
			    if (preg_match("/^[a-zA-Z]{3}$/", $value))
			    {
			         $country->set('currency', strtoupper($value));
			    }
			    break;
			}

			case 'deletecountry':
			{
			    $country	= new Country(array('code'	=> $code));
			    $country->delete(false);
			    $warn	.= "<p>deleted country code '$code'</p>\n";
			    break;
			}

			case 'pattern':
			{
			    $pattern		= $value;
			    $getParms['name']	= $value;
			    break;
			}		// pattern match

			case 'offset':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$offset		= $value;
			    break;
			}

			case 'limit':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$limit		= $value;
			    break;
			}

	    }			// check supported parameters
	}			// loop through all parameters

	if ($country)
	    $country->save(null);// apply updates to last country
	if ($debug)
	    $warn	.= $parmsText . "</table>";
}		// when submit button is clicked invoked by method='post'

if (strlen($msg) == 0)
{			// no errors detected
	$getParms['offset']	= $offset;
	$getParms['limit']	= $limit;
	$countries		= new RecordSet('Countries', $getParms);
}			// no errors detected

if (canUser('edit'))
	$action		= 'Update';
else
		$action		= 'Display';

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "Countries$action$lang.html";
if (!file_exists($tempBase . $includeSub))
{
		$includeSub	= 'Countries' . $action . 'en' . '.html';
		$language	= new Language(array('code'	=> $lang));
		$langName	= $language->get('name');
		$nativeName	= $language->get('nativename');
		$sorry  	= $language->getSorry();
$warn   	.= str_replace(array('$langName','$nativeName'),
                           array($langName, $nativeName),
                           $sorry);
}
$gotPage	= $template->includeSub($tempBase . $includeSub, 'MAIN');
$template->set('PATTERN',		 $pattern);
$template->set('CONTACTTABLE',	'Countries');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('lang', $lang);
$template->set('offset', $offset);
$template->set('limit', $limit);
$info	= $countries->getInformation();
$count	= $info['count'];
$template->set('totalrows', $count);
$template->set('first', $offset + 1);
$template->set('last', min($count, $offset + $limit));
if ($offset > 0)
		$template->set('npPrev', "&offset=" . ($offset-$limit) . "&limit=$limit");
else
		$template->updateTag('prenpprev', null);
if ($offset < $count - $limit)
		$template->set('npNext', "&offset=" . ($offset+$limit) . "&limit=$limit");
else
		$template->updateTag('prenpnext', null);
$template->updateTag('Row$code',
						 $countries);
$template->display();
