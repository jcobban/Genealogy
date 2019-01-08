<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  LanguagesEdit.php					                				*
 *											                            *
 *  Display form for editting information about 					    *
 *  languages for managing record transcriptions.					    *
 *											                            *
 *  History:										                    *
 *		2017/02/04  	created						                    *
 *		2017/02/07  	use class Language				                *
 *		2017/02/25  	use class Template				                *
 *		2017/11/11  	do not fail on unsupported language		        *
 *		2018/01/04  	remove Template from template file names	    *
 *		2018/01/22  	display only part of the table at a time	    *
 *		2018/10/15      get language apology text from Languages        *
 *											                            *
 *  Copyright &copy; 2018 James A. Cobban						        *
 ************************************************************************/
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
			case 'code':
			{
			    if (strlen($value) >= 2)
					$lang		= strtolower(substr($value, 0, 2));
			    break;
			}		// language of presentation

			case 'pattern':
			{
			    if (strlen($value) > 0)
			    {
					$pattern		= $value;
					$getParms['name']	= $value;
			    }
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
	    }			// act on specific parameters
	}			    // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}				    // method='get'
else
if (isset($_POST) && count($_POST) > 0)
{		// when submit button is clicked invoked by method='post'
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	$language		= null;
	foreach($_POST as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc		= strtolower($key);
	    if ($fieldLc == 'debug' ||
			$fieldLc == 'pattern')
	    {
	    }
	    else
	    {			// last 2 characters are language code
			$code		= substr($fieldLc, -2);
			$key		= substr($fieldLc, 0, strlen($fieldLc) - 2);
	    }			// last 2 characters are language code

	    switch(strtolower($key))
	    {
			case 'lang':
			{
			    if (strlen($value) >= 2)
					$lang		= strtolower(substr($value, 0, 2));
			}		// language

			case 'code':
			{
			    if ($language instanceof Language)
			    {
					$language->save(null);
			    }
			    $code   	= $value;
			    $language	= new Language(array('code'	=> $code));
			    break;
			}

			case 'name':
			{
			    $language->set('name', $value);
			    break;
			}

			case 'nativename':
			{
			    $language->set('nativename', $value);
			    break;
			}

			case 'article':
			{
			    $language->set('article', $value);
			    break;
			}

			case 'possessive':
			{
			    $language->set('possessive', $value);
			    break;
			}

			case 'sorry':
			{
			    $language->set('sorry', $value);
			    break;
			}

			case 'deletecountry':
			{
			    $language	= new Language(array('code'	=> $code));
			    $language->delete(false);
			    $warn   	.= "<p>deleted language code '$code'</p>\n";
			    break;
			}

			case 'pattern':
			{
			    $pattern	    	= $value;
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

			case 'debug':
			{
			    break;
			}		// debug handled by common code

	    }			// check supported parameters
	}			// loop through all parameters
	if ($debug)
	    $warn	.= $parmsText . "</table>\n";

	// if last record was updated, save the changes
	if ($language instanceof Language)
	    $language->save(null);

}		// when submit button is clicked invoked by method='post'

if (strlen($msg) == 0)
{			// no errors detected
	$getParms['offset']	= $offset;
	$getParms['limit']	= $limit;
	$languages	    	= new RecordSet('Languages', $getParms);
}			// no errors detected

if (canUser('edit'))
	$action		= 'Update';
else
	$action		= 'Display';

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "Languages$action$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	= "Languages{$action}en.html";
}
$gotPage	    = $template->includeSub($tempBase . $includeSub,
				            			'MAIN');
$includeSub		= "LanguagesDialogs$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$includeSub	= 'LanguagesDialogsen.html';
}
$gotPage	= $template->includeSub($tempBase . $includeSub,
				        			'DIALOGS',
						        	true);	// add after substitutions

$template->set('PATTERN',		 $pattern);
$template->set('CONTACTTABLE',	'Languages');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG', $lang);
$template->set('OFFSET', $offset);
$template->set('LIMIT', $limit);
$info       	= $languages->getInformation();
$count	        = $info['count'];
$template->set('TOTALROWS', $count);
$template->set('FIRST', $offset + 1);
$template->set('LAST', min($count, $offset + $limit));
if ($offset > 0)
	$template->set('npPrev', "&offset=" . ($offset-$limit) .
						 "&limit=$limit" .
						 "&pattern=$pattern");
else
	$template->updateTag('prenpprev', null);
if ($offset < $count - $limit)
	$template->set('npNext', "&offset=" . ($offset+$limit) .
						 "&limit=$limit" .
						 "&pattern=$pattern");
else
	$template->updateTag('prenpnext', null);

$template->updateTag('Row$code',
					 $languages);
$template->display();
