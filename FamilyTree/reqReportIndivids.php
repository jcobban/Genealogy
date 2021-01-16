<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  reqReportIndivids.php												*
 *																		*
 *  Request a report of individuals matching a search.					*
 *																		*
 *  History:															*
 *		2011/02/05		created											*
 *		2012/01/13		change class names								*
 *		2012/02/08		add dates to criteria							*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/10		use CSS instead of tables for form layout		*
 *		2014/04/21		add support for individual event place and date	*
 *						and christening and buried date					*
 *		2015/06/30		add support for searching by event description	*
 *						and by cause of death							*
 *						add support for joining tblIR and tblER			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2020/12/22      use FtTemplate                                  *
 *																		*
 *  Copyright &copy; 2016 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang	    			= 'en';

if (isset($_GET) && count($_GET) > 0)
{			            // invoked by method=get
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                          "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through input parameters
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>" .
                            htmlspecialchars($value) . "</td></tr>\n";
        if (is_string($value))
            $value      = trim($value); 
		switch(strtolower($key))
		{	            // switch on parameter name
		    case 'lang':
		    {			// user requested language
                $lang       = FtTemplate::validateLang($value);
				break;
		    }			// user requested language
		}		        // act on specific parameter
    }                   // loop through input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			            // invoked by method=get

$template				= new FtTemplate("reqReportIndivids$lang.html");

$template->set('LANG',              $lang);
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');

$template->display();
