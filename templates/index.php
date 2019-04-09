<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  /templates/index.php												*
 *																		*
 *  This script displays information about the templates directory.     *
 *																		*
 *    History:															*
 *		2018/11/17		hide contents of templates directory            *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *		open code														*
 ***********************************************************************/
$lang	    	= 'en';		// default english

// process parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach ($_GET as $key => $value)
	{			// loop through all parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'lang':
			{		// requested language
			    if (strlen($value) >= 2)
					$lang	= strtolower(substr($value, 0, 2));
			    break;
			}		// requested language
	
	    }			// switch on parameter name
	}			// foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$template	    = new FtTemplate("templatesIndex$lang.html");

$template->display();

