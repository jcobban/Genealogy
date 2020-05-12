<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getCitationForImageJSON.php                                          *
 *                                                                      *
 *  Generate a JSON document with information about the complete        *
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
 *                      support JSON                                    *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: application/json");
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


// execute the query
if (strlen($msg) == 0)
{
    // display the results
    // top node of XML result
    print("{\n");
    print "\t\"image\" : \"$image\"";

    $pages      = new RecordSet('Pages',
                                array('pt_image'    => $image));

    // report on all matching records
    foreach($pages as $page)
    {       // loop through all result rows
        $pagenum            = $page['page'];
        print ",\n\t\"page$pagenum\" : {";
        $comma              = '';
        foreach($page as $key => $value)
        {       // loop through fields in row
            if (substr($key, 0, 3) == 'pt_')
                $key        = substr($key, 3);
            print "$comma\n\t\t\"$key\" : \"$value\"";
            $comma          = ',';
        }       // loop through fields in row
        print "\n\t}";
    }       // loop through all result rows

    print("\n}\n");    // close off top node of XML result
}       // user supplied needed parameters
else
{       // error
	print "{\"msg\": \"$msg\"\n}\n";
}       // error

