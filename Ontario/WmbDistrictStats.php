<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \NumberFormat;
/************************************************************************
 *  WmbDistrictStats.php												*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms for a particular district.									*
 *																		*
 *  Parameters:															*
 *		district		name of district						        *
 *																		*
 *  History:															*
 *		2013/06/28		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use standard appearance of status report		*
 *						add help page									*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2020/01/22      internationalize numbers                        *
 *		                urlencode parameters to WmbResponse.php         *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptismSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$district	    = null;
$columns		= 1;
$lang           = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			    // loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		    // process specific named parameters
	    case 'district':
        {
            $district       = $value;
            break;
        }

	    case 'columns':
        {
            if (ctype_digit($value) && $value > 0 && $value < 10)
                $columns    = intval($value);
            break;
        }

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value, 0, 2));
            break;
        }
    }		    // process specific named parameters
}			    // loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

$template                   = new FtTemplate("WmbDistrictStats$lang.html");
$formatter                  = $template->getFormatter();

if (is_null($district))
	$msg	                .= $template['distMissing']->innerHTML;

if (strlen($msg) == 0)
{			    // no errors
    // execute the query
    $parms                  		= array('district' => "^$district$");
    $baptisms               		= new MethodistBaptismSet($parms);
	$results                		= $baptisms->getDistrictStatistics();

	// lay out the table header row
	$headRowElt    					= $template['headRow'];
	$headRowHtml   					= $headRowElt->innerHTML();
	$data          					= "         <tr>\n";
	$spacer        					= "";
	for($ic	= $columns; $ic; $ic--)
	{
	    $data                       .= $spacer . $headRowHtml;
	    $spacer             		= "           <th>\n              </th>\n";
	}
	$data                           .= "        </tr>\n";
	$headRowElt->update($data);
	
	// lay out the data rows
	$dataRowElt    					= $template['dataRow$ROWNUM'];
	$dataRowHtml   					= $dataRowElt->innerHTML();
	$rownum        					= 1;
	$distnum       					= 1;
	$rowclass      					= 'odd';
	$total         					= 0;
    $totalLinked         			= 0;
	$data          					= '';
	for ($row = reset($results); $row; )
	{
	    $data                       .= "         <tr id=\"dataRow$rownum\">\n";
	    $spacer             		= "";
	    for($ic = $columns; $ic && $row; $ic--)
	    {
	        $rtemplate      		= new \Templating\Template($dataRowHtml);
	        $township	    		= $row['area'];
	        $distnum++;
	        $count		    		= $row['count'];
	        $linkCount				= $row['linkcount'];
	        if ($count > 0)
	            $pctLinked          = 100 * $linkCount / $count;
	        else
	            $pctLinked          = 0;
			$total		            += $count;
	        $totalLinked         	+= $linkCount;
	        $rtemplate->set('TOWNSHIP',     $township);
	        $rtemplate->set('TOWNSHIPURL',  urlencode("^$township$"));
	        $rtemplate->set('CLASS',        $rowclass);
	        $rtemplate->set('COUNT',        $formatter->format($count));
            $rtemplate->set('LINKCOUNT',    $formatter->format($linkCount));
            $rtemplate->set('PCTCLASSLINKED',pctClass($pctLinked));
	        $data                   .= $spacer . $rtemplate->compile();
	        $spacer                 = "           <td>\n              </td>\n";
	        $row                    = next($results);
	    }
	    $data                       .= "        </tr>\n";
	    $rownum++;
	    if ($rowclass == 'odd')
	        $rowclass               = 'even';
	    else
	        $rowclass               = 'odd';
	}
	$dataRowElt->update($data);
	
	$template->set('TOTAL',         $formatter->format($total));
	$template->set('TOTALLINKED',   $formatter->format($totalLinked));
	if ($total > 0)
	    $pctLinked                  = 100 * $totalLinked / $total;
	else
	    $pctLinked                  = 0;
	$template->set('PCTCLASSLINKED',pctClass($pctLinked));
	$template->set('DISTRICT',      $district);
	$template->set('DISTRICTURL',   urlencode("^$district$"));
}			    // no errors
else
{
    $template['dataTable']->update(null);
}

$template->display();
