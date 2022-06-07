<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  GedcomUpload.php													*
 *																		*
 *  Display a web page for uploading a GEDCOM 5.5 file into the			*
 *  Legacy database.													*
 *																		*
 *  Parameters (passed by method="get"):								*
 * 																		*
 *  History: 															*
 *		2012/05/12		created											*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// safely get parameter values
$lang                       = 'en';
$langtext                   = null;

if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {		            // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                "$safevalue</td></tr>\n"; 
		switch(strtolower($key))
		{	            // switch on parameter name
		    case 'debug':
		    case 'userid':
		    {		    // handled by common.inc
				break;
		    }		    // handled by common.inc

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;
            }

		    default:
		    {
                $warn	    .= "Unexpected parameter $key='$safevalue'";
				break;
		    }
		}	            // switch on parameter name
    }		            // loop through all parameters
}                       // invoked by method=get

$template       = new FtTemplate("GedcomUpload$lang.html");

if (!canUser('edit'))
    $msg	.= $template['notAuth']->innerHTML;

$template->set('LANG',      $lang);

if (strlen($msg) > 0)
    $template['fileForm']->update();

$template->display();

