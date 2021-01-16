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
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$pattern	                = '';
$count	                    = 0;
$offset	                    = 0;
$limit	                    = 20;
$lang                       = 'en';

if (isset($_GET) && count($_GET) > 0)
{
    foreach($_GET as $key => $value)
    {
		switch($key)
		{		// take action based upon key
		    case 'offset':
		    {
				if (ctype_digit($value))
				    $offset	= intval($value);
				else
                    $msg	.= "Invalid Offset='" . 
                                htmlspecialchars($value) . "'. ";
				break;
		    }

		    case 'limit':
		    {
				if (ctype_digit($value))
				    $limit	= intval($value);
				else
				    $msg	.= "Invalid Limit='" . 
                                htmlspecialchars($value) . "'. ";
				break;
		    }

		    case 'lang':
		    {
                $lang       = FtTemplate::validateLang($value);
				break;
		    }	                // presentation language
		}		// take action based upon key
    }
}

$template				= new FtTemplate("UnlinkedPersons$lang.html");

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
    if ($debug)
		$warn	.= "<p>QueryCount=$queryCount</p>\n";

    $stmt	= $connection->query($queryCount);
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
						   print_r($connection->errorInfo(), true);
    }

    $query	= "SELECT tblIR.idir, tblIR.givenname, tblIR.surname " .
						"FROM tblIR " .
						"LEFT JOIN tblMR as husb on husb.idirhusb=tblIR.idir " .
						"LEFT JOIN tblMR as wife on wife.idirwife=tblIR.idir " .
						"LEFT JOIN tblCR on tblCR.IDIR=tblIR.IDIR " .
						"WHERE husb.idirhusb IS NULL AND wife.idirwife IS NULL AND tblCR.idcr IS NULL " . 
						"GROUP BY tblIR.idir, tblIR.givenname, tblIR.surname " .
						"ORDER BY tblIR.idir " .
						"LIMIT $limit";
    if ($debug)
		$warn	.= "<p>Query=\"$query\"</p>\n";

    $stmt	= $connection->query($query);
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
		$msg	.= "query '$query' failed. " .
						   print_r($connection->errorInfo(), true);
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
        $count	        = number_format($count);
        $template->set('COUNT',     $count);
		$last	        = min($nextoffset - 1, $count);
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
        $template->set('last',		$last);
        $template->set('count',		$count);
 
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
