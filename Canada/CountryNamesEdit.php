<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountryNamesEdit.php												*
 *																		*
 *  Display form for editting information about country names			*
 *																		*
 *  History:															*
 *		2017/10/27		created											*
 *		2018/01/03		remove Template from template names				*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/21      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/CountryName.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms				= array();
$cc			    		= 'CA';
$countryName			= 'Canada';
$lang		    		= 'en';
$article				= '';
$possessive				= 'of ';

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{			// method='get'
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'lang':
			{			// language code
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value,0,2));
			    break;
			}			// language code

			case 'cc':
			{
			    $cc				= $value;
			    $getParms['code3166_1']	= $cc;
			    break;
			}		// pattern match
	    }			// act on specific parameters
	}			// loop through parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";

	$country		    = new CountryName(array('cc'	=> $cc,
						        		        'lang'	=> $lang));
	$countryName		= $country->getName();
	$article		    = $country->get('article');
	$possessive		    = $country->get('possessive');
}				// method='get'
else
if (isset($_POST) && count($_POST) > 0)
{		// when submit button is clicked invoked by method='post'
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	$countryNameObj		= null;
	foreach($_POST as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc	= strtolower($key);
	    if ($fieldLc == 'debug' || $fieldLc == 'cc' || $fieldLc == 'lang')
			$key	= $fieldLc;
	    else
	    {			// last 2 characters are upper case
			$code	= substr($fieldLc, -2);
			$key	= substr($fieldLc, 0, strlen($fieldLc) - 2);
	    }			// last 2 characters are upper case

	    switch(strtolower($key))
	    {
			case 'cc':
			{
			    $cc				= $value;
			    $getParms['code3166_1']	= $cc;
			    $country		= new Country(array('code' => $cc));
			    $countryName	= $country->getName();
			    break;
			}		// language

			case 'lang':
			{
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value,0,2));
			    break;
			}

			case 'code':
			{
			    $code	= $value;
			    if ($countryNameObj && $countryNameObj->get('name') != '')
					$countryNameObj->save(null);
			    $countryNameObj= new CountryName(array('Code3166_1' => $cc,
									'Code639_1'  => $code));
			    if ($debug)
					$warn	.= "<p>CountryNamesEdit.php: " . __LINE__ .
						" created new CountryName(array('Code3166_1' => '$cc', 'Code639_1'  => '$code')), </p>\n";
			    break;
			}

			case 'name':
			{
			    if (strlen($value) > 0 && $value != '*')
					$countryNameObj->set('name', $value);
			    else
			    {
					if ($debug)
					    $warn	.= "<p>Deleted name '" .
						   $countryNameObj->get('name') . 
						   "' for country code '$cc' " .
						   "for language code '$code'</p>\n";
					$countryNameObj->set('name', '');
					$countryNameObj->delete(false);
			    }
			    break;
			}

			case 'article':
			{
			    if (substr($value, -1) != ' ' && substr($value, -1) != "'")
					$value	= $value . ' ';
			    $countryNameObj->set('article', $value);
			    break;
			}

			case 'possessive':
			{
			    if (substr($value, -1) != ' ' && substr($value, -1) != "'")
					$value	= $value . ' ';
			    $countryNameObj->set('possessive', $value);
			    break;
			}

			case 'debug':
			{
			    break;
			}		// debug handled by common code

	    }			// check supported parameters
	}			// loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";

	// apply any updates to the last instance of CountryName
	if ($countryNameObj)
	{
	    $countryNameObj->save(null);
	}
}		// when submit button is clicked invoked by method='post'

if (strlen($msg) == 0)
{			// no errors detected
	$enName		    = new CountryName(array('code3166_1'	=> $cc,
					            			'code639_1'	    => $lang));
	$names		    = new RecordSet('CountryNames', $getParms);
	$information	= $names->getInformation();
	if (!$enName->isExisting())
	    $names->push('en', $enName);
}			// no errors detected

if (canUser('edit'))
{
	$action		= 'Update';
}
else
{
	$action		= 'Display';
}

$template		= new FtTemplate("CountryNamesEdit$action$lang.html");

$template->set('LANG',		$lang);
$template->set('COUNTRYNAME',	$countryName);
$template->set('ARTICLE',		$article);
$template->set('POSSESSIVE',	$possessive);
$template->set('CC',		$cc);
$template->set('LANG',		$lang);
$template->set('CONTACTTABLE',	'CountryNames');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->updateTag('Row$code639_1',
					 $names);
$template->display();
