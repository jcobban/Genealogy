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
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms			= array();
$pattern			= '';
$lang		    	= 'en';
$offset		    	= 0;
$limit		    	= 20;

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{			// method='get'
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
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
			{
                $lang       = FtTemplate::validateLang($value);
			    break;
			}		// language

			case 'pattern':
			{
			    $pattern		    = $value;
			    $getParms['name']	= $value;
			    break;
			}		// pattern match

			case 'offset':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$offset		    = $value;
			    break;
			}

			case 'limit':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$limit		    = $value;
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
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
	$country		= null;
	foreach($_POST as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $matches	= array();
	    $pres	    = preg_match("/[A-Z]{2}$/", $key, $matches);
	    if ($pres)
	    {			// last 2 characters are upper case
			$cc	    = $matches[0];
			$key	= substr($key, 0, strlen($key) - 2);
	    }			// last 2 characters are upper case
	    else
	    {
			$cc	    = '';
	    }

	    switch(strtolower($key))
	    {
			case 'lang':
			{
                $lang       = FtTemplate::validateLang($value);
			    break;
			}		// language

			case 'code':
			{
			    if ($country)
                    $country->save(null);
                if (strlen($value) >= 2)
                {
			        $cc	        = strtoupper($value);
                    $country	= new Country(array('code'	=> $cc));
                }
                else
                    $country    = null;
			    break;
			}

			case 'name':
			{
			    $country->set('name', $value);
			    break;
			}

			case 'dialingcode':
			{
			    if (ctype_digit($value))
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
			    $country	= new Country(array('code'	=> $cc));
			    $country->delete(false);
			    $warn	    .= "<p>deleted country code '$cc'</p>\n";
			    break;
			}

			case 'pattern':
			{
			    $pattern		    = $value;
			    $getParms['name']	= $value;
			    break;
			}		// pattern match

			case 'offset':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$offset		    = $value;
			    break;
			}

			case 'limit':
			{
			    if (is_numeric($value) || ctype_digit($value))
					$limit		    = $value;
			    break;
			}

	    }			// check supported parameters
	}			    // loop through all parameters

	if ($country)
	    $country->save(null);// apply updates to last country
	if ($debug)
	    $warn	.= $parmsText . "</table>";
}		// when submit button is clicked invoked by method='post'

if (canUser('all'))
	$action		= 'Admin';
else
if (canUser('edit'))
	$action		= 'Update';
else
	$action		= 'Display';

$template		= new FtTemplate("Countries$action$lang.html");

if (strlen($msg) == 0)
{			// no errors detected
	$getParms['offset']	= $offset;
	$getParms['limit']	= $limit;
	$countries		    = new RecordSet('Countries', $getParms);
}			// no errors detected

$template->set('PATTERN',		$pattern);
$template->set('CONTACTTABLE',	'Countries');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('lang',          $lang);
$template->set('offset',        $offset);
$template->set('limit',         $limit);
$info	= $countries->getInformation();
$count	= $info['count'];
$template->set('totalrows',     $count);
$template->set('first',         $offset + 1);
$template->set('last',          min($count, $offset + $limit));
if ($offset > 0)
	$template->set('npPrev',    "&offset=" . ($offset-$limit) . "&limit=$limit");
else
	$template->updateTag('prenpprev', null);
if ($offset < $count - $limit)
	$template->set('npNext',    "&offset=" . ($offset+$limit) . "&limit=$limit");
else
	$template->updateTag('prenpnext', null);
$template->updateTag('Row$code',
					 $countries);
$template->display();
