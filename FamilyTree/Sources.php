<?php
namespace Genealogy;
use \PDO;
use \Exception;
use Templating\Template;
use Templating\TemplateTag;

/************************************************************************
 *  Sources.php															*
 *																		*
 *  Display a web page containing all of the Sources matching a			*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2010/08/29		Use new layout									*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/11		explicitly set field names to lower case		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/30		add link to help page		                    *
 *		2010/12/08		format number of citations						*
 *		2012/01/13		change class names								*
 *		2012/04/01		pad citation number portion less than 1000		*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/03/28		support mouseover help							*
 *						separate javascript and HTML					*
 *						add button for deleting un-referenced source	*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/18		Delete button for a source was not type="button"*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS for layout instead of tables			*
 *						correct back link								*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/08/23		use Source::getSources							*
 *						use Citation::getCitations						*
 *						eliminate all direct uses of SQL				*
 *						validate parameters and issue error message		*
 *						instead of querying database if wrong			*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/02/13		specify style class for input field				*
 *		2015/05/28		support split screen for displaying source		*
 *						<button type="Edit..."> was type="submit"		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/10/14		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/22      use Template                                    *
 *		2019/11/06      add translate table to output for Javascript    *
 *		2020/01/22      internationalize numbers                        *
 *      2020/02/17      'IDST$idst' is not on readonly template         * 
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2020/04/29      add fields and buttons for creating new source  *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters passed by method=GET
$pattern				    = '';
$offset 				    = 0;
$limit	    			    = 20;
$lang                       = 'en';

if (count($_GET) > 0)
{	        	    // invoked by URL
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
            "<td class='white left'>$value</td></tr>\n"; 
        $value                      = trim($value);
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'pattern':
		    {
				$pattern	        = $value;
				break;
		    }

		    case 'offset':
            {
                if (ctype_digit($value))
				    $offset		    = (int)$value;
				break;
		    }

		    case 'limit':
		    {
                if (ctype_digit($value) && $value > 10)
				    $limit		    = (int)$value;
				break;
		    }

		    case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
				break;
		    }
		}		// act on specific parameters
    }
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}	        	    // invoked by URL

if (canUser('edit'))
    $action             = 'Edit';
else
    $action             = 'Display';

// get the appropriate template
$template               = new FtTemplate("Sources$action$lang.html");
$translate              = $template->getTranslate();
$srcTypes               = $translate['srcTypes'];
$formatter              = $template->getFormatter();

// get an associative array of source records matching the
// supplied parameters
$parms	                = array('limit'		=> $limit,
		        	    		'offset'	=> $offset,
				            	'order'		=> 'SrcName');
if (strlen($pattern) > 0)
	$parms['SrcName']	= $pattern;
$sources		        = new RecordSet('Sources', $parms);
$information	        = $sources->getInformation();
// Note: $information['count'] >= $sources->count() <= $limit
$count		            = $information['count'];

$template->set('PATTERN',           htmlspecialchars($pattern));
$template->set('OFFSET',            $offset);
$template->set('LIMIT',             $limit);
$template->set('LANG',              $lang);
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');

// pass interpretation of IDST to Javascript
$rowtag                 = $template['IDST$idst'];
if ($rowtag)
{
	$table              = '';
	foreach($srcTypes as $idst => $name)
	{
	    $rtemplate      = new Template($rowtag->outerHTML);
	    $rtemplate->set('idst',         $idst);
	    $rtemplate->set('name',         $name);
	    $table          .= $rtemplate->compile();
	}
	$rowtag->update($table);
}

// display table of matching sources
if ($sources->count() == 0)
{                           // no records in response
    $template['topBrowse']->update(null);
    $template['dataTable']->update(null);
}                           // no records in response
else
{                           // records to display
    $template['nomatch']->update(null);
    $prevoffset	        = $offset - $limit;
    $nextoffset	        = $offset + $limit;
    $last	            = min($nextoffset - 1, $count);
    $template->set('SHOWOFFSET',    $formatter->format($offset + 1));
    $template->set('COUNT',         $formatter->format($count));
    $template->set('LAST',          $formatter->format($last));

    if ($prevoffset >= 0)
    {	// previous page of output to display
        $template->set('PREVOFFSET',    $prevoffset);
    }	// previous page of output to display
    else
        $template['topPrev']->update(null);
    if ($nextoffset  < $count)
    {	// next page of output to display
        $template->set('NEXTOFFSET',    $nextoffset);
    }	// next page of output to display
    else
        $template['topNext']->update(null);

    // display the results
    $rowElement             = $template['sourceRow$IDSR'];
    $rowHtml                = $rowElement->outerHTML();
    $rowclass               = 'odd';
    $data                   = '';
    foreach($sources as $idsr => $source)
    {		// loop through matching sources
        $rtemplate          = new \Templating\Template($rowHtml);

		$idst		        = $source->getType();
		$typeText	        = $srcTypes[$idst]; 
        $name		        = $source->getName();

		// query the database for citation count
		$parms	            = array('IDSR'	=> $idsr,
                    				'limit'	=> 0);
		$cresult	        = new RecordSet('Citations', 
                    						$parms);
		$cinformation	    = $cresult->getInformation();
        $ccount		        = $cinformation['count'];

        $rtemplate->set('IDSR',             $idsr);
        $rtemplate->set('IDST',             $idst);
        $rtemplate->set('TYPETEXT',         $typeText);
        $rtemplate->set('NAME',             $name);
        $rtemplate->set('ROWCLASS',         $rowclass);
        $rtemplate->set('CCOUNT',       $formatter->format($cinformation['count']));
        $delCell            = $rtemplate['DeleteCell$IDSR'];
        if ($delCell)
        {
            if ($ccount > 0)
                $delCell->update(null);
            else
                $rtemplate['count$IDSR']->update(null);
        }
        $data               .= $rtemplate->compile();
        if ($rowclass == 'odd')
            $rowclass       = 'even';
        else
            $rowclass       = 'odd';
    }
    $rowElement->update($data);
}                           // records in response

$template->display();
