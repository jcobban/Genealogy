<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \NumberFormatter;
/************************************************************************
 *  WmbVolStats.php														*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms by volume number.											*
 *																		*
 *  History:															*
 *		2016/09/25		created											*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2020/01/25      use Template, MethodistBaptismSet, and          *
 *		                NumberFormatter                                 *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptismSet.inc';
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

// get template
$template               = new FtTemplate("WmbVolStats$lang.html");
$formatter              = $template->getFormatter();

// execute the query
$baptisms               = new MethodistBaptismSet();
$result                 = $baptisms->getVolumeStatistics();

if (is_array($result) && count($result) > 0)
{
    $dataRow            = $template['dataRow'];
    $dataRowText        = $dataRow->outerHTML();
    $data               = '';
    $total		        = 0;
    $totalLinked        = 0;
    foreach($result as $row)
    {
		$volume		    = $row['volume'];
        $count		    = $row['done'];
        $pages          = $row['pages'];
        $linkCount      = $row['linkcount'];
        if ($count > 0)
            $pctLinked          = 100 * $linkCount / $count;
        else
            $pctLinked          = 0;
        $total		    += $count;
        $totalLinked    += $linkCount;
        $rowTemplate    = new Template($dataRowText);
        $parms          = array('volume'    => $volume,
                                'count'     => $formatter->format($count),
                                'pages'     => $pages,
                                'linkCount' => $formatter->format($linkCount),
                                'pctclasslinked'=> pctClass($pctLinked));
        $rowTemplate['dataRow']->update($parms);
        $data           .= $rowTemplate->compile();
    }
    $dataRow->update($data);

    if ($total > 0)
        $pctLinked              = 100 * $totalLinked / $total;
    else
        $pctLinked              = 0;
    $parms      = array('total'      => $formatter->format($total),
                        'totallinked'=> $formatter->format($totalLinked),
                        'pctclasslinked'    => pctClass($pctLinked));
    $template['footRow']->update($parms);
}
else
{
    $warn               .= $template['nomatches']->innerHTML;
    $template['dataTable']->update(null);
}

$template->display();
