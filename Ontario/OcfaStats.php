<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaStats.php														*
 *																		*
 *  Display statistics about the transcription of cemetery inscriptions.*
 *																		*
 *  Parameters:															*
 *		debug			control debug output							*
 *																		*
 *  History:															*
 *		2011/03/20		created											*
 *		2012/05/06		switch to button to displaying county level		*
 *						statistics										*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use standard appearance for stats reports		*
 *						add link to help documentation					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/12/17      use class OcfaSet                               *
 *		                use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/OcfaSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// common
$lang	    	    = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL 
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}

$template           = new FtTemplate("OcfaStats$lang.html");

$template->set('LANG',              $lang);

if (strlen($msg) == 0)
{			            // no errors
	$ocfas          		= new OcfaSet(array('offset'    => 0));
	$result	        		= $ocfas->getStatistics();

    $columns				= 3;
    $col		    		= 1;
    $total		    		= 0;
    $rowtag         		= $template['datarow'];
    $rbody          		= $rowtag->innerHTML;
    $data           		= '';
    foreach($result as $row)
    {                   // loop through rows
		$county		        = $row['county'];
		$count		        = $row['count'];
        $total		        += $count;
		if ($col == 1)
        {
            $data           .= "\t\t<tr>\n";
        }
        $rtemplate          = new \Templating\Template($rbody);
        $rtemplate->set('county',       $row['county']);
        $rtemplate->set('count',        number_format($count));
        $data               .= $rtemplate->compile();
        
		$col++;
        if ($col > $columns)
		{	            // at column limit, end row
		    $col	        = 1;
            $data           .= "\t\t</tr>\n";
		}	            // at column limit, end row
		else
		{	            // start new column
            $data           .= "\t\t  <td>&nbsp;</td>\n";
        }	            // start new column
    }                   // loop through rows
    $template->set('total',            number_format($total));
    $rowtag->update($data);
}			            // no errors
else
{
    $template['dataTable']->update(null);
}

$template->display();
