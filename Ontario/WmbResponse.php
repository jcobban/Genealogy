<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  WmbResponse.php														*
 *																		*
 *  Display a report of individuals whose Wesleyan Methodist Baptism	*
 *  matches the requested pattern.  This is invoked by method="get"		*
 *  from WmbQuery.html.													*
 *																		*
 *  Parameters:															*
 *		Count															*
 *		Offset															*
 *		Surname															*
 *		GivenName														*
 *		SurnameSoundex													*
 *		District														*
 *		Area															*
 *		Father															*
 *		Mother															*
 *		etc.															*
 *																		*
 *  History:															*
 *		2013/06/28		created											*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/03		interpret numeric dates							*
 *						replace tables with CSS							*
 *		2015/05/01		PHP print statements were corrupted				*
 *			            validate date before interpreting month		    *	
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/28		force minimum width of columns					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/03/26		make given name a hyperlink						*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/08/19		change default number of lines to 25			*
 *						always display full page if page requested		*
 *						forward and back links are by page if page		*
 *						requested										*
 *						order by internal order for display of page		*
 *		2016/11/28		handle invalid month 00							*
 *		2018/01/24		use new URLs									*
 *						use prepared SQL statements						*
 *						do not fail if asked to show entire table		*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/04/29      change name to WmbResponse.php                  *
 *		                use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/MethodistBaptism.inc";
require_once __NAMESPACE__ . "/MethodistBaptismSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *  dateToString														*
 *																		*
 *  Expand numeric dates to a human readable string.					*
 *																		*
 *  Input:																*
 *		$date		date from database field							*
 *																		*
 *  Returns:															*
 *		Human readable date as a string.								*
 ************************************************************************/
function dateToString($date)
{
    global	$monthName;
    $matches	= array();
    $presult	= preg_match('/^(\d\d\d\d)-(\d+)-(\d+)$/',
                             $date,
                             $matches);
    if ($presult === 1)
    {			// pattern matched
        $year	= $matches[1];
        $month	= $matches[2] - 0;
        $day	= $matches[3] - 0;
        if ($month == 0)
            return $day . '&nbsp;XXX&nbsp;' .  $year;
        if ($month <= 12)
            return $day . '&nbsp;' . $monthName[$month] .
                    '&nbsp;' . $year;
        if ($month > 12 && $day <= 12)
            return $month . '&nbsp;' . $monthName[$day] . '&nbsp;' .  
                        $year;
        else
            return $date;
    }			// pattern matched
    else
        return $date;
}			// dateToString

$getParms				= array();
$npuri					= 'WmbResponse.php';// for next and previous links
$npand					= '?';		        // adding parms to $npuri
$limit					= 20;
$offset					= 0;
$orderby				= 'IDMB';
$volume					= '';
$page					= '';
$lang           		= 'en';
$surname		        = null;
$surnameSoundex		    = false;

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
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
        case 'count':
        case 'limit':
        {		// limit number of rows returned
            if (preg_match("/^([0-9]{1,2})$/", $value))
                $limit	            = $value;
            break;
        }		// limit number of rows returned

        case 'offset':
        {		// starting offset
            if (preg_match("/^([0-9]{1,6})$/", $value))
                $offset	            = $value;
            break;
        }		// starting offset

        case 'volume':
        {
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            $volume		            = $value;
            $getParms['volume']     = $value;
            break;
        }

        case 'page':
        {
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            $page		            = $value;
            $getParms['page']       = $value;
            break;
        }

        case 'lang':
        {		// language requested
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
            break;
        }		// language requested

        case 'surname':
        {
            $surname	            = $value;
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            $orderby	            = 'Surname, GivenName';
            break;
        }

        case 'givenname':
        case 'father':
        case 'mother':
        case 'minister':
        case 'birthplace':
        case 'birthdate':
        case 'baptismplace':
        case 'baptismdate':
        case 'district':
        case 'area':
        case 'residence':
        {		// match anywhere in string
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            $orderby	            = 'Surname, GivenName';
            $getParms[$key]         = urldecode($value);
            break;
        }		// match in string

        case 'surnamesoundex':
        {		// handled under Surname
            $npuri	                .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            if (strtolower($value[0]) != 'n')
                $surnameSoundex	    = true;
            $orderby	            = 'Surname, GivenName';
            break;
        }		// handled under Surname

        default:
        {		// exact match on field in table
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            $orderby	            = 'Surname, GivenName';
            $getParms[$key]         = $value;
            break;
        }		// exact match on field in table

        case 'debug':
        {		// handled by common.inc
            $npuri		            .= "{$npand}{$key}=" . urlencode($value);
            $npand		            = '&amp;'; 
            break;
        }		// debug
    }		    // switch on parameter name
}			    // loop through all parameters
if ($debug)
    $warn               .= $parmsText . "</table>\n";

$template               = new FtTemplate("WmbResponse$lang.html");
$tranTab                = $template->getTranslate();
$monthName              = $tranTab['Months'];
$template->set('CC',            'CA');
$template->set('DOMAIN',        'CAON');
if (strlen($volume) > 0)
    $template->set('VOLUME',    $volume);
else
    $template['volumeLink']->update(null);
$template->set('LANG',          $lang);

if ($surname)
{			    // surname search specified
    if ($surnameSoundex)
        $getParms['surnamesoundex'] = $surname;
    else
        $getParms['surname']        = $surname;
}			    // surname search specified

$getParms['offset']                 = $offset;
if ($orderby == 'IDMB' && strlen($page) > 0)
    $limit                          = 99;
else
    $getParms['limit']              = $limit;

$results        = new MethodistBaptismSet($getParms);
$info           = $results->getInformation();
$total          = $info['count'];

// variable portion of URI for next and previous links
if ($orderby == 'IDMB' && 
    strlen($page) > 0)
{
    $prevpage	    = $page - 1;
    $nextpage	    = $page + 1;
    if ($prevpage > 0)
        $npprev		= "Volume=$volume&Page=$prevpage&Count=$limit";
    else
        $npprev     = '';
    $npnext		    = "Volume=$volume&Page=$nextpage&Count=$limit";
}
else
if ($offset > 0)
{		    // starting offset
    $tmp	        = $offset - $limit;
    if ($tmp < 0)
        $npprev	    = "";	// no previous link
    else
        $npprev	    = "Count=$limit&Offset=$tmp";
    $tmp		    = $offset + $limit;
    if ($tmp > $total)
        $npnext		= "";
    else
        $npnext		= "Count=$limit&Offset=$tmp";
}		    // starting offset
else
{           // at beginning
    $npprev		    = "";
    $npnext		    = "Count=$limit&Offset=$limit";
}           // at beginning

$template->set('NPURI',         $npuri);
$template->set('NPAND',         $npand);
if (strlen($npprev) > 0)
{
    $template->set('NPPREV',    $npprev);
}
else
{
    $template['topPrev']->update(null);
    $template['botPrev']->update(null);
}
if (strlen($npnext) > 0)
{
    $template->set('NPNEXT',    $npnext);
}
else
{
    $template['topNext']->update(null);
    $template['botNext']->update(null);
}

$template->set('STARTOFFSET',   $offset + 1);
$template->set('ENDOFFSET',     min($total, $offset + $limit));
$template->set('TOTALROWS',     $total);

$dataRow                    = $template['datarow'];
$dataRowText                = $dataRow->outerHTML();
$data                       = '';
$class                      = 'odd';
foreach($results as $idmb => $baptism)
{               // loop through all baptisms
    $baptism['class']       = $class;
    if ($class == 'odd')
        $class              = 'even';
    else
        $class              = 'odd';
    $baptism['birthdate']   = dateToString($baptism['birthdate']);
    $baptism['baptismdate'] = dateToString($baptism['baptismdate']);
    $rowTemplate            = new Template($dataRowText);
    if ($baptism['idir'] > 0)
        $rowTemplate['person']->update(null);
    else
        $rowTemplate['personLink']->update(null);
    $rowTemplate['datarow']->update($baptism);
    $data                   .= $rowTemplate->compile();
}               // loop through all baptisms
$dataRow->update($data);

$template->display();
