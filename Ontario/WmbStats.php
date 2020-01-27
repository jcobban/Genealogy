<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
use \Templating\Template;
/************************************************************************
 *  WmbStats.php														*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms.															*
 *																		*
 *  History:															*
 *		2013/06/29		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use common appearance for status tables			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/07/09      use Template                                    *
 *		2020/01/22      internationalize numbers                        *
 *		                use MethodistBaptismSet::getStatistics          *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptismSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get parameters
$lang                       = 'en';
$columns                    = 3;
$pattern                    = '';

if (isset($_GET) && count($_GET) > 0)
{	        	        // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	                // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		        // act on specific parameter
			case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }           // lang

            case 'columns':
            {
                if (ctype_digit($value) && $value > 0 && $value < 10)
                    $columns        = intval($value);
                break;
            }           // columns

            case 'pattern':
            {
                if (strlen($value) > 0)
                    $pattern        = $value;
                break;
            }           // pattern
        }		        // act on specific parameter
    }	                // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	        // invoked by URL to display current status of account

// get the template
$template               		= new FtTemplate("WmbStats$lang.html");
$formatter              		= $template->getFormatter();

// execute the query
if (strlen($pattern) > 0)
    $getParms           		= array('district'  => $pattern);
else
    $getParms           		= array();
$baptisms               		= new MethodistBaptismSet($getParms);
$results                		= $baptisms->getStatistics();

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
        $rtemplate      		= new Template($dataRowHtml);
        $district	    		= $row['district'];
        $distnum++;
        $count		    		= $row['count'];
        $linkCount				= $row['linkcount'];
        if ($count > 0)
            $pctLinked          = 100 * $linkCount / $count;
        else
            $pctLinked          = 0;
		$total		            += $count;
        $totalLinked         	+= $linkCount;
        $rtemplate->set('DISTRICT',     $district);
        $rtemplate->set('DISTNUM',      $distnum);
        $rtemplate->set('CLASS',        $rowclass);
        $rtemplate->set('COUNT',        $formatter->format($count));
        $rtemplate->set('LINKCOUNT',    $formatter->format($linkCount));
        $rtemplate->set('PCTCLASSLINKED',pctClass($pctLinked));
        $data                   .= $spacer . $rtemplate->compile();
        $spacer         		= "           <td>\n              </td>\n";
        $row            		= next($results);
    }
    $data                       .= "        </tr>\n";
    $rownum++;
    if ($rowclass == 'odd')
        $rowclass       		= 'even';
    else
        $rowclass       		= 'odd';
}
$dataRowElt->update($data);

$template->set('TOTAL',         $formatter->format($total));
$template->set('TOTALLINKED',   $formatter->format($totalLinked));
if ($total > 0)
    $pctLinked                      = 100 * $totalLinked / $total;
else
    $pctLinked          = 0;
$template->set('PCTCLASSLINKED',pctClass($pctLinked));

$template->display();
