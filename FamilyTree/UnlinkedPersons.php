<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  getUnlinkedPersons.php											    *
 *																		*
 *  Display a web page containing all Persons in the family tree	    *
 *  who are neither married nor children of a family.					*
 *																		*
 *  History:															*
 *		2017/01/15		created											*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2020/12/26      use FtTemplate                                  *
 *		                protect against XSS exposures                   *
 *		2022/07/22      add support for Surname pattern match           *
 *		                support debug output of parameters              *
 *		                fix setting of $last                            *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$count	                    = 0;
$offset	                    = 0;
$offsettext                 = null;
$limit	                    = 20;
$limittext                  = null;
$lang                       = 'en';
$surnamepatt                = null;
$surnametext                = null;

if (isset($_GET) && count($_GET) > 0)
{
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
		{		// take action based upon key
		    case 'offset':
				if (ctype_digit($value))
				    $offset	        = intval($value);
                else
                    $offsettext     = $safevalue;
				break;          // offset

		    case 'limit':
				if (ctype_digit($value))
				    $limit	        = intval($value);
				else
                    $limittext      = $safevalue;
				break;          // limit

            case 'surname':
                if (preg_match('/^[a-zA-Z0-9.*?]+$/', $value))
                    $surnamepatt    = $value;
                else
                    $surnametext    = $safevalue;
                break;          // surname


		    case 'lang':
                $lang       = FtTemplate::validateLang($value);
				break;          // presentation language

		}		// take action based upon key
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}

$template				= new FtTemplate("UnlinkedPersons$lang.html");

if (is_string($offsettext))
    $warn	.= "<p>Invalid Offset='$offsettext' ignored</p>\n";
if (is_string($limittext))
    $warn	.= "<p>Invalid Limit='$limittext' ignored</p>\n";
if (is_string($surnametext))
    $msg	.= "Invalid Surname='$surnametext'. ";
$prevoffset	            = $offset - $limit;
$nextoffset	            = $offset + $limit;

if (canUser('edit'))
{			// authorized to update database
    $queryCount	= "SELECT count(*) " .
						"FROM tblIR " .
						"LEFT JOIN tblMR as husb on husb.idirhusb=tblIR.idir " .
						"LEFT JOIN tblMR as wife on wife.idirwife=tblIR.idir " .
						"LEFT JOIN tblCR on tblCR.IDIR=tblIR.IDIR " .
                        "WHERE husb.idirhusb IS NULL AND wife.idirwife IS NULL AND tblCR.idcr IS NULL";
    if (is_string($surnamepatt))
        $queryCount     .= " AND tblIR.Surname REGEXP '$surnamepatt'";
    if ($debug)
		$warn	.= "<p>QueryCount=$queryCount</p>\n";

    $stmt	        = $connection->query($queryCount);
    if ($stmt)
    {
		$record		= $stmt->fetch(PDO::FETCH_NUM);
		if (is_null($record) || $record === false)
		{
		    $warn	.= "<p>QueryCount=$queryCount</p>\n";
		    $warn	.= "<p>PDO::fetch returned false</p>\n";
		}
		else
		{
		    $count		= $record[0];
		}
    }
    else
    {
		$msg	.= "query '$queryCount' failed. " .
						   print_r($connection->errorInfo(), true) . ". ";
    }

    $query	= "SELECT tblIR.idir, tblIR.givenname, tblIR.surname " .
						"FROM tblIR " .
						"LEFT JOIN tblMR as husb on husb.idirhusb=tblIR.idir " .
						"LEFT JOIN tblMR as wife on wife.idirwife=tblIR.idir " .
						"LEFT JOIN tblCR on tblCR.IDIR=tblIR.IDIR " .
						"WHERE husb.idirhusb IS NULL AND wife.idirwife IS NULL AND tblCR.idcr IS NULL "; 
    if (is_string($surnamepatt))
        $query      .= "AND tblIR.Surname REGEXP '$surnamepatt' ";
	$query          .= "GROUP BY tblIR.idir, tblIR.givenname, tblIR.surname " .
						"ORDER BY tblIR.idir " .
                        "LIMIT $limit OFFSET $offset";
    if ($debug)
		$warn	.= "<p>Query=\"$query\"</p>\n";

    $stmt	        = $connection->query($query);
    if ($stmt)
    {
		$records	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($debug)
		{
		    if ($records === false)
				$warn	.= "<p>fetch returned false</p>\n";
		    else
				$warn	.= "<p>fetch returned " . gettype($records) . "</p>\n";
		}
    }
    else
    {
		$msg	    .= "Query '$query' failed. " .
						   print_r($connection->errorInfo(), true) . ". ";
    }
}			// authorized to update database
else
{
	$msg	.= "You are not an administrator. ";
}

if (strlen($msg) == 0)
{		// ok
	if ($count == 0)
    {
        $template['someLinked']->update(null);
	}
	else
	{			    // got some results
        $template['allLinked']->update(null);
        $last	        = min($nextoffset - 1, $count);
        $count	        = number_format($count);
        $template->set('COUNT',     $count);
		if ($prevoffset >= 0)
        {	        // previous page of output to display
            $template->set('prevoffset',        $prevoffset);
        }	        // previous page of output to display
        else
            $template['npprev']->update(null);
		if ($nextoffset < $count)
		{	        // next page of output to display
            $template->set('nextoffset',        $nextoffset);
		}	        // next page of output to display
        else
            $template['npnext']->update(null);
		$template->set('offset',		$offset);
        $template->set('last',		    $last);
        $template->set('count',		    $count);
		$template->set('limit',		    $limit);
 
        // display the results
        $tr             = $template['dataRow$idir'];
        $data           = '';
		foreach($records as $person)
        {
            $rowtmp     = new Template($tr->outerHTML);
		    $idir	    = $person['idir']; 
		    $givenname	= htmlspecialchars($person['givenname']); 
            $surname	= htmlspecialchars($person['surname']);
            $rowtmp->set('idir',        $idir);
            $rowtmp->set('givenname',   $givenname);
            $rowtmp->set('surname',     $surname);
            $data       .= $rowtmp->compile();
        }	    // loop through results
        $tr->update($data);
	}	        // got some results
}		        // ok
else
{
    $template['frontBrowse']->update(null);
    $template['results']->update(null);
    $template['someLinked']->update(null);
    $template['allLinked']->update(null);
}

$template->display();
