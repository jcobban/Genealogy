<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  Locations.php														*
 *																		*
 *  Display a web page containing all of the locations matching a		*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2010/08/22		use new layout									*
 *		2010/09/25		where duplicate locations, order by IDLR		*
 *		2010/10/05		add keystroke support to gain bubble help		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/04		add link to help page							*
 *						sort names by SortedLocation					*
 *		2011/10/31		cleanup separation of PHP & HTML				*
 *						support mouseover help and links				*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/04/29		display summary of location						*
 *		2013/05/18		add name field to permit direct creation of		*
 *						locations										*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *		2014/03/12		use CSS instead of tables for layout			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/19		use legacyLocation::getLocations				*
 *						this makes the database request more efficient	*
 *		2014/09/22		restore functionality to display all locations	*
 *						if no pattern specified							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/06		add button to close the dialog					*
 *		2016/01/19		add id to debug trace							*
 *		2016/04/05		add button for creating new entry				*
 *		2017/01/03		handle exception from getLocations				*
 *		2017/07/17		handle unmatched square brackets in pattern		*
 *		2017/09/09		renamed to Locations.php						*
 *		2017/09/12		use get( and set(								*
 *		2017/11/04		use RecordSet instead of getLocations			*
 *		2018/04/14		urlencode the pattern in forward and back links	*
 *		2018/11/06      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/06/15      use ordinal numbering of records                *
 *		2020/01/22      internationalize numbers                        *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$pattern	        = '';
$offset	            = 0;
$limit	            = 20;
$lang               = 'en';

// get parameter values
foreach($_GET as $key => $value)
{                           // loop through parameters
	switch(strtolower($key))
	{		                // take action based upon key
	    case 'pattern':
	    {
			$value		    = str_replace('\\','',$value);
			if (preg_match('/[\w ]*\[[\w ]*$/', $value) == 1)
			{
			    $pattern	= str_replace('[','\[',$value);
			}
			else
			    $pattern	= $value;
			break;
	    }

	    case 'offset':
	    {
			if (ctype_digit($value))
			    $offset	= intval($value);
			else
                $msg	    .= "Invalid Offset='" . 
                                htmlspecialchars($value) . "'. ";
			break;
	    }

	    case 'limit':
	    {
			if (ctype_digit($value))
			    $limit	= intval($value);
			else
			    $msg	    .= "Invalid Limit='" . 
                                htmlspecialchars($value) . "'. ";
			break;
        }

        case 'lang':
        {                   // preferred language
            $lang           = FtTemplate::validateLang($value);
        }                   // preferred language

	}		                // take action based upon key
}                           // loop through parameters

$template		                    = new FtTemplate("Locations$lang.html");
$formatter                          = $template->getFormatter();

$template->set('PATTERN',       htmlspecialchars($pattern));
$template->set('UPATTERN',      urlencode($pattern));
$template->set('OFFSET',        $offset);
$template->set('LIMIT',         $limit);
$template->set('LANG',          $lang);

$prevoffset	                        = $offset - $limit;
$nextoffset	                        = $offset + $limit;
$template->set('PREVOFFSET',    $prevoffset);
$template->set('NEXTOFFSET',    $nextoffset);

// get part of the list of locations matching the pattern
if (strlen($pattern) > 0)
	$getParms	= array('Location'	=> $pattern,
						'limit'		=> $limit,
						'offset'	=> $offset);
else
	$getParms	= array('limit'		=> $limit,
						'offset'	=> $offset);

$locations		= new RecordSet('Locations', $getParms);
$info		    = $locations->getInformation();
$count		    = $info['count'];

if (strlen($msg) == 0)
{
	if ($count == 0)
    {           // nothing to display
        $template->updateTag('somematches', null);
	}           // nothing to display
	else
    {			// got some results
        $template->updateTag('nomatches', null);
        $template->set('COUNT', $formatter->format($count));
	    $template->set('OFFSET', $offset);
	    $template->set('FIRST', $offset + 1);
		$last	        = min($nextoffset, $count);
	    $template->set('LAST', $last);
		if ($prevoffset < 0)
	    {	// no previous page of output to display
	        $template->updateTag('topPrev', null);
		}	// no previous page of output to display
		if ($nextoffset >= $count)
		{	// no next page of output to display
	        $template->updateTag('topNext', null);
        }	// no next page of output to display

        // display the results
        $element            = $template['location$IDLR'];
        $rowHtml            = $element->outerHTML();
        $data               = '';
		foreach($locations as $idlr => $loc)
        {	            // loop through results
            $rtemplate                      = new Template($rowHtml);
		    $rtemplate->set('IDLR',		    $idlr); 
		    $location                       = $loc->get('location');
		    $rtemplate->set('LOCATION',		htmlspecialchars($location)); 
		    $latitude                       = $loc->get('latitude');
		    $rtemplate->set('LATITUDE',	    $latitude);
		    $longitude                      = $loc->get('longitude');
		    $rtemplate->set('LONGITUDE',	$longitude);
            if ($latitude != 0 || $longitude != 0)
				$rtemplate->set('LOCPRESENT',   "&#x2713;"); // check
	        else
				$rtemplate->set('LOCPRESENT',   "&nbsp;");
		    $notes                          = $loc->get('notes');
		    $rtemplate->set('NOTES',		$notes);
	        if (strlen($notes) > 0)
				$rtemplate->set('NOTESPRESENT',	"&#x2713;"); // check
			else
				$rtemplate->set('NOTESPRESENT', "&nbsp;");
		    $boundary                       = $loc->get('boundary');
		    $rtemplate->set('BOUNDARY',		$boundary);
	        if (strlen($boundary) > 0)
				$rtemplate->set('BOUNDPRESENT',	"&#x2713;"); // check
			else
                $rtemplate->set('BOUNDPRESENT', "&nbsp;");
            $data       .= $rtemplate->compile();
        }	            // loop through results
        $element->update($data);
	}	                // got some results
}		                // ok
else
{                       // errors
    $template->updateTag('somematches', null);
    $template->updateTag('nomatches', null);
}                       // errors

$template->display();
