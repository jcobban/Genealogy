<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  calcBirthDate.php													*
 *																		*
 *  Display a web page for calculating a date of birth from an event	*
 *  and an age at the time of the event.								*
 *																		*
 *  History:															*
 *		2010/12/12		created											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		use class on <select> to standardize appearance	*
 *		2014/02/10		eliminate use of tables for layout				*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2018/02/17		use Template									*
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/common.inc";

$lang		= 'en';

if (count($_GET) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc	= strtolower($key);
	    if ($key == 'lang' && strlen($value) >= 2)
	    	$lang	= strtolower(substr($value, 0, 2));
	}
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account

$template		= new FtTemplate("calcBirthDate$lang.html");

$template->set('LANG',		$lang);
$template->display();
