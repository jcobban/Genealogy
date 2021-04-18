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
 *		2019/02/18      use new FtTemplate constructor                  *
 *		2021/01/03      correct XSS vulnerability                       *
 *      2021/04/04      escape contact subject URL                      *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$lang			= 'en';

if (isset($_GET) && count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {			// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>" .
                            htmlspecialchars($value) . "</td></tr>\n"; 
        switch(strtolower($key))
        {
    		case 'lang':
    		{
	            $lang           = FtTemplate::validateLang($value);
    		    break;
    		}		// language
    
        }
    }			// loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL 

$template		= new FtTemplate("videoTutorials$lang.html");

$template->set('CONTACTTABLE',		'Videos');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('LANG', $lang);
$videos		= new RecordSet('Videos', array('lang' => $lang));
if ($videos->count() == 0)
    $videos	= new RecordSet('Videos', array('lang' => 'en'));
$template->updateTag('$filename',
		     $videos);
$template->display();
