<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \NumberFormatter;
/************************************************************************
 *  WmbPageStats.php													*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms by page number within a volume number.						*
 *																		*
 *  Parameters:															*
 *		volume			volume number									*
 *		Debug			enable debug output								*
 *																		*
 *  History:															*
 *		2016/09/25		created											*
 *		2016/11/28		fix divide by zero								*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2020/01/22      use FtTemplate                                  *
 *                      use NumberFormatter                             *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/MethodistBaptismSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// default parameters
$volume	                    = null;
$volumeText                 = null;
$lang                       = 'en';

// process input parameters to get specific options
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
	$parmsText      = "<p class='label'>\$_GET</p>\n" .
	                        "<table class='summary'>\n" .
	                        "<tr><th class='colhead'>key</th>" .
	                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $name => $value)
	{			        // loop through parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                         "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($name))
		{		        // act on specific parameter
		    case 'volume':
		    {
                if (strlen($value) > 0)
                {
                    if (ctype_digit(trim($value)))
				        $volume	    = intval($value);
                    else
                        $volumeText = $value;
				}
				break;
            }		    // volume number

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
		}		        // act on specific parameter
	}			        // loop through parameters
	if ($debug)
	    $warn       .= $parmsText . "</table>\n";
}                       // invoked by method=get

$template           = new FtTemplate("WmbPageStats$lang.html");
$formatter          = $template->getFormatter();

if (is_string($volumeText))
{
    $text           = $template['invalidVolume']->innerHTML;
    $msg            .= str_replace('$volume', $volumeText, $text);
}
else
if (is_null($volume))
{
    $msg            .= $template['missingVolume']->innerHTML;
}

if (strlen($msg) == 0)
{			                // no errors
    $template->set('VOLUME',            $volume);
    $baptisms       = new MethodistBaptismSet(array('volume'    => $volume));
    $results        = $baptisms->getPageStatistics();

    $total		        			= 0;
    $totalLinked	    			= 0;
    $templateRow        			= $template['datarow$page'];
    $templateText       			= $templateRow->outerHTML;
    $data               			= '';
    foreach($results as $row)
    {		                // process all rows
		$page		                = $row['page'];
		$count		                = $row['count'];
		$total		                += $count;
		$linked		                = $row['linkcount'];
		$totalLinked	            += $linked;
        $pctLinked	                = 100 * $linked / $count;
        $rtemplate                  = new Template($templateText);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
		$rtemplate->set('page',         $page);
		$rtemplate->set('count',        $formatter->format($count));
		$rtemplate->set('linked',       $formatter->format($linked));
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
		$rtemplate->set('pctLinked',    $formatter->format($pctLinked));
        $rtemplate->set('pctClassLinked',pctClass($pctLinked));
        $data                       .= $rtemplate->compile();
    }		                // process all rows
    $templateRow->update($data);

    if ($total > 0)
		$pctLinked	                = 100 * $totalLinked / $total;
    else
		$pctLinked	                = 0;
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
	$template->set('TOTAL',	            $formatter->format($total));
	$template->set('TOTALLINKED',	    $formatter->format($totalLinked));
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
	$template->set('PCTLINKED',	        $formatter->format($pctLinked));
    $template->set('PCTCLASSLINKED',    pctClass($pctLinked));
}			                // no errors
else
{			                // errors           
    $template->set('VOLUME',            'Not Given');
    $template['statsForm']->update(null);
}			                // errors

$template->set('CC',                    'CA');
$template->set('COUNTRYNAME',           'Canada');
$template->set('DOMAIN',                'CAON');
$template->set('DOMAINNAME',            'Ontario');
$template->display();
