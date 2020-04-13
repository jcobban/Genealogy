<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  uploadGedcom.php													*
 *																		*
 *  This script displays a dialog for uploading a GEDCOM 5.5 family     *
 *  tree and merging it with the existing family tree.                  *
 *																		*
 *    History:															*
 *		2018/11/28      created                                         *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *		open code														*
 ***********************************************************************/
$cc			        = 'CA';
$countryName	    = 'Canada';
$lang		        = 'en';		// default english

// process parameters passed by caller
// override from passed parameters
if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
		{
		    case 'lang':
		    {		// requested language
	            $lang       = FtTemplate::validateLang($value);
				break;
		    }		// requested language
	
		    case 'debug':
		    {		// requested debug
				break;
		    }		// requested debug
		}		// switch on parameter name
	}			// foreach parameter
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get

$update     = canUser('edit');

// create template
$template	= new FtTemplate("uploadGedcom$lang.html");

$template->display();
