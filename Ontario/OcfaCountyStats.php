<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaCountyStats.php													*
 *																		*
 *  Display statistics about the transcription of cemetery inscriptions.*
 *  for a particular county.											*
 *																		*
 *  Parameters:															*
 *		county			name of county									*
 *		debug			control debug output							*
 *																		*
 *  History:															*
 *		2012/05/06		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use standard appearance for stats reports		*
 *						add link to help documentation					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/12/17      use class OcfaSet                               *
 *		                use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Ocfa.inc';
require_once __NAMESPACE__ . '/OcfaSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$countyName	        = null;
$lang	    	    = 'en';

// get parameter values
if (count($_GET) > 0)
{	        	    // invoked by URL 
	$parmsText      = "<p class='label'>\$_GET</p>\n" .
	                        "<table class='summary'>\n" .
	                        "<tr><th class='colhead'>key</th>" .
	                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through all parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                         "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'county':
            {
                if (strlen($value) > 0)
	                $countyName		    = $value;
				break;
		    }		// county 
	
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
		}		// act on specific parameters
	}			// loop through all parameters
	if ($debug)
	    $warn       .= $parmsText . "</table>\n";
}

$template                   = new FtTemplate("OcfaCountyStats$lang.html");

if (is_null($countyName))
{
	$countyName             = 'Unknown';
	$msg	                .= "Missing mandatory parameter 'county'";
}

$template->set('COUNTYNAME',            $countyName);

if (strlen($msg) == 0)
{			        // no errors
	$ocfas                  = new OcfaSet(array('county'    => $countyName));
	$result	                = $ocfas->getCountyStatistics();

    $columns		        = 1;
    $col		            = 1;
    $total		            = 0;
    $rowtag                 = $template['datarow'];
    $rowbody                = $rowtag->outerHTML;
    $data                   = '';
    foreach($result as $row)
    {
		$township	        = $row['township'];
		$count		        = $row['count'];
        $total		        += $count;
        $rtemplate          = new \Templating\Template($rowbody);
        $rtemplate->set('township',         $township);
        $rtemplate->set('count',            number_format($count));
        $data               .= $rtemplate->compile() . "\n";
    }		        // loop through rows
    $rowtag->update($data);

    $template->set('total',                 number_format($total));
}			        // no errors
else
    $template['dataTable']->update(null);

$template->display();
