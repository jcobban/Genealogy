<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  DontMergeEntries.php												*
 *																		*
 *  Display a web page containing a list of entries to suppress global	*
 *  merge of individuals on a case by case basis.  These are usually	*
 *  name entries that are not distinguishable by name and birth date,	*
 *  but are known not to represent the same individual.					*
 *																		*
 *  History:															*
 *		2011/11.01		created											*
 *		2012/01/13		change class names								*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/07/31		defer setup of facebook link					*
 *						standardize initialization						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						submitting form invoked Locations.php			*
 *						dates were displayed in internal format			*
 *						display both individuals for each entry			*
 *		2016/02/06		use showTrace									*
 *		2017/09/14		use DontMergeEntry::getDontMergeEntries			*
 *		2017/10/30		use DontMergeEntrySet							*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/05/06      use FtTemplate                                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/DontMergeEntry.inc';
require_once __NAMESPACE__ . '/DontMergeEntrySet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$pattern	        			= '';
$lang               			= 'en';
$offset	            			= 0;
$limit	            			= 20;
$offsettext            			= null;
$limittext            			= null;

if (isset($_GET) && count($_GET) > 0)
{
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                          "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
		switch(strtolower($key))
		{		// take action based upon key
		    case 'pattern':
		    {		// pattern to match against surname
				$pattern	        = htmlspecialchars($value);
				break;
		    }		// pattern to match against surname

		    case 'offset':
            {		// position within the complete list of responses
                if (ctype_digit($value))
                    $offset		    = (int)$value;
                else
                    $offsettext     = htmlspecialchars($value); 
				break;
		    }		// position within the complete list of responses

		    case 'limit':
		    {		// maximum number of entries displayed
                if (ctype_digit($value))
				    $limit		    = (int)$value;
                else
                    $limittext      = htmlspecialchars($value); 
				break;
            }		// maximum number of entries displayed

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
				break;
            }
		}		// take action based upon key
    }
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}

$template                   = new FtTemplate("DontMergeEntries$lang.html");
$first                      = $offset + 1;
$prevoffset	                = $offset - $limit;
$nextoffset	                = $offset + $limit;

// construct the query
$getParms		= array('offset'	=> $offset,
						'limit'		=> $limit,
						'order'		=> 'IDIRLeft');
if (strlen($pattern) > 0)
	$getParms['surname']	= $pattern;
$entries		            = new DontMergeEntrySet($getParms);
$information	            = $entries->getInformation();
$count		                = $information['count'];
$last		                = min($nextoffset, $count);

$template->set('OFFSET',        $offset);
$template->set('LIMIT',         $limit);
$template->set('PATTERN',       $pattern);
$template->set('FIRST',         $first);
$template->set('PREVOFFSET',    $prevoffset);
$template->set('NEXTOFFSET',    $nextoffset);
$template->set('LAST',          $last);
$template->set('COUNT',         $count);
$template->set('LANG',          $lang);


// display the results
if ($count > 0)
{
    if ($prevoffset < 0)
        $template['topPrev']->update(null);
    if ($nextoffset >= $count)
        $template['topNext']->update(null);
	$dataRow            = $template['dataRow'];
	$dataRowText        = $dataRow->outerHTML();
	$data               = '';
	$class              = 'odd';
	foreach($entries as $row)
	{
	    $row['class']   = $class;
	    $row['lang']    = $lang;
	    $rtemplate      = new Template($dataRowText);
	    $rtemplate['dataRow']->update($row);
	    $data           .= $rtemplate->compile();
	    if ($class == 'odd')
	        $class      = 'even';
	    else
	        $class      = 'odd';
	}	// loop through results
    $dataRow->update($data);
}
else
{
    $template['topBrowse']->update(null);
    $template['dataTable']->update(null);
}

$template->display();
