<?php
namespace Genealogy;
use \PDO;
use \Exception;
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
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
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

$template       = new FtTemplate("WmbDistrictStats$lang.html");

if (is_null($district))
	$msg	.= 'Missing mandatory parameter "district".  ';

if (strlen($msg) == 0)
{			    // no errors
	// execute the query
	$query	    = "SELECT Area, SUM(Surname != '') FROM MethodistBaptisms " .
					    "WHERE District=:district" .
                        " GROUP BY Area ORDER BY Area";
    $sqlParms       = array('district'  => $district);
    $stmt	 	    = $connection->prepare($query);
    $queryText      = debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		    // successful query
	    $result	    = $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
            $warn   .= "<p>WmbDistrictStats.php: " . __LINE__ .
                            " query=$queryText</p>\n";
	}		    // successful query
	else
	{
	    $msg	    .= "query '$queryText' failed: " .
					   print_r($stmt->errorInfo(),true);
	}		    // query failed
}			    // no errors
else
    $result     = array();

// lay out the table header row
$headRowElt    			= $template['headRow'];
$headRowHtml   			= $headRowElt->innerHTML();
$data          			= "         <tr>\n";
$spacer        			= "";
for($ic	= $columns; $ic; $ic--)
{
    $data               .= $spacer . $headRowHtml;
    $spacer             = "           <th>\n              </th>\n";
}
$data                   .= "        </tr>\n";
$headRowElt->update($data);

// lay out the data rows
$dataRowElt    			= $template['dataRow$ROWNUM'];
$dataRowHtml   			= $dataRowElt->innerHTML();
$rownum        			= 1;
$distnum       			= 1;
$rowclass      			= 'odd';
$total         			= 0;
$data          			= '';
for ($row = reset($result); $row; )
{
    $data               .= "         <tr id=\"dataRow$rownum\">\n";
    $spacer             = "";
    for($ic = $columns; $ic && $row; $ic--)
    {
        $rtemplate      = new \Templating\Template($dataRowHtml);
        $township	    = $row[0];
        $distnum++;
        $count		    = $row[1];
		$total		    += $count;
        $rtemplate->set('TOWNSHIP',     $township);
        $rtemplate->set('CLASS',        $rowclass);
        $rtemplate->set('COUNT',        number_format($count));
        $data           .= $spacer . $rtemplate->compile();
        $spacer         = "           <td>\n              </td>\n";
        $row            = next($result);
    }
    $data               .= "        </tr>\n";
    $rownum++;
    if ($rowclass == 'odd')
        $rowclass       = 'even';
    else
        $rowclass       = 'odd';
}
$dataRowElt->update($data);

$template->set('TOTAL',         number_format($total));
$template->set('DISTRICT',      $district);

$template->display();
