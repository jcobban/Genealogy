<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  WmbVolStats.php														*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms by volume number.											*
 *																		*
 *  History:															*
 *		2016/09/25		created											*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang           = 'en';

// first extract the values of all supplied parameters
$parmsText      = "<p class=\"label\">\$_GET</p>\n" .
                        "<table class=\"summary\">\n" .
                        "<tr><th class=\"colhead\">key</th>" .
                        "<th class=\"colhead\">value</th></tr>\n";
foreach ($_GET as $key => $value)
{			    // loop through all parameters
    $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                         "<td class=\"white left\">$value</td></tr>\n"; 
    switch(strtolower($key))
    {		    // switch on parameter name

        case 'lang':
        {		// language requested
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
            break;
        }		// language requested
    }		    // switch on parameter name
}			    // loop through all parameters
if ($debug)
    $warn               .= $parmsText . "</table>\n";

// execute the query
$query	= "SELECT Volume, MAX(Page) as Pages, COUNT(*) as Done " .
					" FROM MethodistBaptisms" .
					" GROUP BY Volume ORDER BY Volume";
$stmt	 	        = $connection->query($query);
if ($stmt)
{		// successful query
    $result	        = $stmt->fetchAll();
    if ($debug)
		print "<p>$query</p>\n";
}		// successful query
else
{
    $msg	        .= "query '$query' failed: " .
                        print_r($connection->errorInfo(),true);
    $result         = array();
}		// query failed

// get template

$template               = new FtTemplate("WmbVolStats$lang.html");

if (count($result) > 0)
{
    $dataRow            = $template['dataRow'];
    $dataRowText        = $dataRow->outerHTML();
    $data               = '';
    $total		        = 0;
    foreach($result as $row)
    {
		$volume		    = $row['volume'];
        $count		    = $row['done'];
        $pages          = $row['pages'];
        $total		    += $count;
        $rowTemplate    = new Template($dataRowText);
        $rowTemplate['dataRow']->update(array('volume'  => $volume,
                                              'count'   => number_format($count),
                                              'pages'   => $pages));
        $data           .= $rowTemplate->compile();
    }
    $dataRow->update($data);

    $template['footRow']->update(array('total'      => number_format($total)));
}
else
    $template['dataTable']->update(null);

$template->display();
