<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  ToDos.php														    *
 *																		*
 *  Display a web page containing all of the research ToDo items        *
 *  matching a pattern.													*
 *																		*
 *	Parameters:															*
 *		idir        unique numeric key of subject Person				*
 *		pattern     regular expression pattern to match name of item    *
 *		lang        requested language of communications                *
 *		offset      starting offset within result set                   *
 *		limit       maximum number of items to display at a time        *
 *																		*
 *  History:															*
 *		2019/08/13		created									        *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$idir                       = null;
$personName                 = null;
$pattern	                = '';
$offset	                    = 0;
$limit	                    = 20;
$lang                       = 'en';

// get parameter values
foreach($_GET as $key => $value)
{                           // loop through parameters
	switch(strtolower($key))
    {		                // take action based upon key
        case 'idir':
        {
            if (ctype_digit($value))
                $idir       = (int)$value;
            break;
        }

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
			    $msg	    .= "Invalid Offset='$value'. ";
			break;
	    }

	    case 'limit':
	    {
			if (ctype_digit($value))
			    $limit	= intval($value);
			else
			    $msg	    .= "Invalid Limit='$value'. ";
			break;
        }

        case 'lang':
        {                   // preferred language
            if (strlen($value) == 2)
                $lang       = strtolower($value);
        }                   // preferred language

	}		                // take action based upon key
}                           // loop through parameters

$template		= new FtTemplate("ToDos$lang.html");
$tranTab        = $template->getTranslate();
$tr             = $tranTab['tranTab'];      // instance of TemplateTag

$template->set('IDIR',          $idir);
$template->set('PATTERN',       $pattern);
$template->set('UPATTERN',      urlencode($pattern));
$template->set('OFFSET',        $offset);
$template->set('LIMIT',         $limit);
$template->set('LANG',          $lang);

$prevoffset	= $offset - $limit;
$nextoffset	= $offset + $limit;
$template->set('PREVOFFSET',    $prevoffset);
$template->set('NEXTOFFSET',    $nextoffset);

// get part of the list of todos matching the pattern
if (strlen($pattern) > 0)
	$getParms	= array('ToDoName'	=> $pattern,
						'limit'		=> $limit,
						'offset'	=> $offset);
else
	$getParms	= array('limit'		=> $limit,
                        'offset'	=> $offset);

if (is_int($idir) && $idir > 0)
{
    $person                         = Person::getPerson($idir);
    if ($person->isExisting())
    {
        $getParms['idir']           = $idir;
        $personName                 = $person->getName($tr);
    }
}

if ($personName)
{
    $template->set('PERSONNAME',    $personName);
}
else
{
    $template->set('PERSONNAME',    '');
    $template['titleName']->update(null);
}

if (strlen($pattern) == 0)
    $template['titlePattern']->update(null);

$todos		    = new RecordSet('ToDo', $getParms);
$info		    = $todos->getInformation();
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
        $template->set('COUNT', number_format($count));
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
        $element            = $template['todo$IDTD'];
        $rowHtml            = $element->outerHTML();
        $data               = '';
		foreach($todos as $idtd => $todo)
        {	            // loop through results
            $rtemplate                      = new Template($rowHtml);
		    $rtemplate->set('IDTD',		    $idtd); 
		    $todoName                       = $todo['todoname'];
		    $rtemplate->set('TODONAME',		htmlspecialchars($todo)); 
            $openedd                        = $todo['openedd'];
            $newidir                        = $todo['idir'];
            if ($newidir != $idir)
            {
                $person                     = Person::getPerson($newidid);
                if ($person->isExisting())
                {
                    $personName             = $person->getName($tr);
                    $idir                   = $newidir;
                }
            }
		    $rtemplate->set('PERSONNAME',	$personName);
		    $startd	                        = new LegacyDate($openedd);
		    $rtemplate->set('OPENEDDATE',	$startd->toString(9999,false,$tr));
		    $closedd                        = $todo['closedd'];
		    $endd	                        = new LegacyDate($closedd);
		    $rtemplate->set('CLOSEDDATE',	$endd->toString(9999, false, $tr));
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
