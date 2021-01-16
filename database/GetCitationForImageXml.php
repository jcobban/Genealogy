<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  GetCitationForImageXml.php                                          *
 *                                                                      *
 *  Generate an XML document with information about the complete        *
 *  citation for a page identified by the URL of the image on the       *
 *  LAC web-site.                                                       *
 *                                                                      *
 *  Parameters (passed by method GET):                                  *
 *      Image           URL of image                                    *
 *                                                                      *
 *  History:                                                            *
 *      2011/09/14      created                                         *
 *      2012/01/27      renamed to identify XML return                  *
 *      2013/11/26      handle database server failure gracefully       *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/09/14      use class Page                                  *
 *      2017/11/17      use RecordSet instead of Page::getPages         *
 *      2020/05/12      allow mixed case on parameter name              *
 *		2020/10/10      remove field prefix for Pages table             *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/common.inc';

// the invoker must explicitly provide the URL of the image
$image                      = null;

if (isset($_GET) && count($_GET) > 0)
{
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {               // loop through all parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n";
        switch(strtolower($key))
        {
            case 'image':
                $image      = $value;
                break;
        }
    }               // loop through all parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account
if (is_null($image))
    $msg    = "Did not pass parameter Image by method=get. ";

print("<?xml version='1.0' encoding='UTF-8'?>\n");

// execute the query
if (strlen($msg) == 0)
{
    // display the results
    // top node of XML result
    print("<ident>\n");
    print "<image>" . htmlspecialchars($image) . "</image>\n";

    $pages      = new RecordSet('Pages',
                                array('image'    => $image));

    // report on all matching records
    foreach($pages as $page)
    {       // loop through all result rows
        print "<page>\n";
        foreach($page as $key => $value)
        {       // loop through fields in row
            print "<$key>$value</$key>\n";
        }       // loop through fields in row
        print "</page>\n";
    }       // loop through all result rows

    print("</ident>\n");    // close off top node of XML result
}       // user supplied needed parameters
else
{       // error
    print("<msg>$msg</msg>\n");
}       // error
?>
