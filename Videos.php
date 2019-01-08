<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  VideosEdit.php														*
 *																		*
 *  Display form for editting information about tutorial videos.		*
 *																		*
 *  History:															*
 *		2018/02/01		created											*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/Language.inc";
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
	$ttext		    = "<table>\n";
	foreach($_GET as $key => $value)
	{			// loop through parameters
		$ttext	.= "<tr><th>$key</th><td>$value</td></tr>\n";
	    switch(strtolower($key))
	    {
			case 'lang':
			{
			    if (strlen($value) >= 2)
					$lang		= strtolower(substr($value, 0, 2));
			    break;
			}		// language

			case 'pattern':
			{
			    $pattern			= $value;
			    if (strlen($value) > 0)
					$getParms['filename']	= $value;
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
	}			// loop through parameters
	if ($debug)
	    $warn   	.= $ttext . "</table>\n";
}				// method='get'
else
if (isset($_POST) && count($_POST) > 0)
{		// when submit button is clicked invoked by method='post'
	$ttext		    = "<table>\n";
	$video		    = null;
	foreach($_POST as $key => $value)
	{
	    $ttext		.= "<tr><th>$key</th><td>$value</td></tr>\n";
	    $fieldLc		= strtolower($key);
	    $matches		= array();
	    if (preg_match('/^([a-zA-Z]+)(\d+)$/', $fieldLc, $matches))
	    {
			$column		= $matches[1];
			$row		= $matches[2];
	    }
	    else
	    {
			$column		= $fieldLc;
			$row		= '';
	    }

	    switch($column)
	    {
			case 'pattern':
			{
			    $pattern			= $value;
			    if (strlen($value) > 0)
					$getParms['filename']	= $value;
			    break;
			}		// pattern match

			case 'filename':
			{
			    if ($video instanceof Record)
			    {
					$video->save(null);
			    }
			    $filename		= $value;
			    break;
			}

			case 'lang':
			{
			    if (strlen($row) > 0)
			    {
					$lang		= strtolower(substr($value, 0, 2));
					$video	= new Record(array('filename'	=> $filename,
								   'lang'	=> $lang),
							     'Videos');
			    }
			    break;
			}		// language

			case 'description':
			{
			    $video->set('description', $value);
			    break;
			}		// language

			case 'display':
			{
			    if ($value === 0 || $value === '0' || 
					strtoupper($value[0]) == 'N')
					$video->set('display', 0);
			    else
					$video->set('display', 1);
			    break;
			}		// language

			case 'delete':
			{
			    if ($value == 'Y')
					$video->delete(false);
			    break;
			}

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

	$ttext  	.= "</table>";
	if ($debug)
	    $warn	.= $ttext;
}		// when submit button is clicked invoked by method='post'

if (strlen($msg) == 0)
{			// no errors detected
	$getParms['offset']	= $offset;
	$getParms['limit']	= $limit;
	$videos		    	= new RecordSet('Videos', $getParms);
}			// no errors detected

if (canUser('edit'))
{
	$action		= 'Edit';
}
else
{
	$action		= 'Display';
}

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "Videos$action$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                      array($langName, $nativeName),
                                       $sorry);
	$includeSub	    = "Videos{$action}en.html";
}

$gotPage	= $template->includeSub($tempBase . $includeSub,
								    'MAIN');

$template->set('PATTERN',		 $pattern);
$template->set('CONTACTTABLE',	'Videos');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',          $lang);
$template->set('OFFSET',        $offset);
$template->set('LIMIT', $limit);
$info	            = $videos->getInformation();
$count	            = $info['count'];
$template->set('COUNT',         $count);
$template->set('FIRST',         $offset + 1);
$template->set('LAST',          min($count, $offset + $limit));
if ($offset > 0)
	$template->updateTag('npprev',
					     array("offset"	=> ($offset - $limit),
							   "limit"	=> $limit,
							   "lang"	=> $lang,
							   "pattern"	=> $pattern));
else
	$template->updateTag('npprev', null);
if ($offset < $count - $limit)
	$template->updateTag('npnext',
					     array("offset"	=> ($offset + $limit),
							   "limit"	=> $limit,
							   "lang"	=> $lang,
							   "pattern"	=> $pattern));
else
	$template->updateTag('npnext', null);
$template->updateTag('mousenpprev',
					 array('prevoffset'	=> ($offset - $limit)));
$template->updateTag('mousenpnext',
					 array('nextoffset'	=> ($offset + $limit)));

$row		= 1;
$even		= 'odd';
foreach($videos as $video)
{
	$video->set('row', $row);
	$video->set('even', $even);
	if ($even == 'odd')
	    $even	= 'even';
	else
	    $even	= 'odd';
	if ($video->get('display'))
	    $video->set('display', 'Y');
	else
	    $video->set('display', 'N');
	$row++;
}
$template->updateTag('video$row',
						 $videos);
$template->display();
