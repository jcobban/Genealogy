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
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2019/04/06      use new FtTemplate::includeSub                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *											                            *
 *  Copyright &copy; 2020 James A. Cobban						        *
 ************************************************************************/
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
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
        $valuetext  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$valuetext</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'lang':
			case 'code':
			{
                $lang       = FtTemplate::validateLang($value);
			    break;
			}		// language of presentation

			case 'pattern':
			{
			    if (strlen($value) > 0)
                {
					$pattern		    = $value;
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
        $valuetext  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$valuetext</td></tr>\n"; 
	    $fieldLc		= strtolower($key);
	    if ($fieldLc == 'debug' ||
			$fieldLc == 'lang' ||
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
                $lang       = FtTemplate::validateLang($value);
			}		// language

			case 'code':
			{
			    if ($language instanceof Language)
			    {
					$language->save(null);
                }
                if (preg_match('/^\w\w$/', $value))
                {
			        $code   	= strtolower($value);
                    $language	= new Language(array('code'	=> $code));
                }
                else
                {
                    $msg        .= "Invalid language code '" .
                                    htmlspecialchars($value) .
                                    "' in parameter $key$code. ";
                    $language   = null;
                }
			    break;
			}

			case 'name':
            {
                if ($language)
			        $language->set('name', $value);
			    break;
			}

			case 'nativename':
			{
                if ($language)
			        $language->set('nativename', $value);
			    break;
			}

			case 'article':
			{
                if ($language)
			        $language->set('article', $value);
			    break;
			}

			case 'possessive':
			{
                if ($language)
			        $language->set('possessive', $value);
			    break;
			}

			case 'sorry':
			{
                if ($language)
			        $language->set('sorry', $value);
			    break;
			}

			case 'deletecountry':
			{
			    $language	= new Language(array('code'	=> $code));
                $count      = $language->delete(false);
                if ($count > 0)
                {
                    $warn   	.= "<p>Deleted language code '$code'</p>\n";
                    $warn       .= '<p>' . $language->getLastSqlCmd() .
                        "</p>\n";
                }
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

if (canUser('edit'))
	$action		= 'Update';
else
	$action		= 'Display';

$template		= new FtTemplate("Languages$action$lang.html");

$includeSub		= "LanguagesDialogs$lang.html";
$gotPage	    = $template->includeSub($includeSub,
				            			'DIALOGS',
					    	        	true);	// add after substitutions

$template->set('PATTERN',		    $pattern);
$template->set('CONTACTTABLE',	    'Languages');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',              $lang);
$template->set('OFFSET',            $offset);
$template->set('LIMIT',             $limit);

if (strlen($msg) == 0)
{			// no errors detected
	$getParms['offset']	= $offset;
	$getParms['limit']	= $limit;
	$languages	    	= new RecordSet('Languages', $getParms);
	
	$info       	    = $languages->getInformation();
	$count	            = $info['count'];
	$template->set('TOTALROWS',     $count);
	$template->set('FIRST',         $offset + 1);
	$template->set('LAST',          min($count, $offset + $limit));
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
	
    if ($count > 0)
        $template->updateTag('Row$code', $languages);
    else
        $template['dataTable']->update(null);
}			// no errors detected
else
{
    $template['topBrowse']->update(null);
    $template['languageForm']->update(null);
}

$template->display();
