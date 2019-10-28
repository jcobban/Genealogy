<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Tables.php															*
 *																		*
 *  Display a web page to provide access to the database tables that	*
 *  interpret type and status fields.									*
 *																		*
 *  History:															*
 *		2012/10/22		split off from Services.php						*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace tables with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/08/15		renamed to Tables.php							*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/23      use Template                                    *
 *		2019/07/23      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		        = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$template       = new FtTemplate("Tables$lang.html");

$template->display();
