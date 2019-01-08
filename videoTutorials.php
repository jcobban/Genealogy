<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  videoTutorials.php													*
 *																		*
 *  This script provides access to a selection of video tutorials.		*
 *																		*
 *    History:															*
 *		2015/07/29		created											*
 *		2016/01/19		add id to debug trace							*
 *		2017/05/24		add .webm support								*
 *		2018/01/31		use class Template								*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$lang			= 'en';

foreach($_GET as $key => $value)
{			// loop through parameters
    switch(strtolower($key))
    {
		case 'lang':
		{
		    if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
		    break;
		}		// language

    }
}			// loop through parameters

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "videoTutorials$lang.html";
if (!file_exists($tempBase . $includeSub))
{
    $includeSub	    = 'videoTutorialsen.html';
    $language	    = new Language(array('code'	=> $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
}
$gotPage	= $template->includeSub($tempBase . $includeSub, 'MAIN');
$template->set('CONTACTTABLE',		'Videos');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG', $lang);
$videos		= new RecordSet('Videos', array('lang' => $lang));
if ($videos->count() == 0)
    $videos	= new RecordSet('Videos', array('lang' => 'en'));
$template->updateTag('$filename',
		     $videos);
$template->display();
