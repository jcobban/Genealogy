<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Nicknames.php														*
 *																		*
 *  Display a web page containing all of the alternate given names		*
 *  matching a pattern.													*
 *																		*
 *  History:															*
 *		2017/12/10		created											*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/10		use file_exists to check for defined templates	*
 *		2019/01/21      add gender field                                *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/05/05      improve parameter validation                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Nickname.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$getParms	    	= array();
$pattern	    	= '';
$offset		        = 0;
$getParms['offset']	= 0;
$limit	        	= 20;
$getParms['limit']	= 20;
$lang       		= 'en';

if (count($_GET) > 0)
{			    // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	foreach($_GET as $key => $value)
	{		    // loop through parameters
	    switch($key)
	    {		// take action based upon key
			case 'pattern':
			{
			    $pattern		= $value;
			    if (strlen($pattern) > 0)
					$getParms['nickname']	= $pattern;
			    break;
			}

			case 'offset':
			{
                if (ctype_digit($value))
			        $offset			= (int)$value;
			    break;
			}

			case 'limit':
            {
                if (ctype_digit($value))
			        $limit			= (int)$value;
			    break;
			}

			case 'lang':
            {	// language choice
                if (strlen($value) >= 2)
			        $lang		= strtolower(substr($value,0,2));
			    break;
			}	// language choice
	    }		// take action based upon key
	}		    // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			    // invoked by method=get
else
if (count($_POST) > 0)
{			    // invoked by method=post
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{		    // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $matches	= array();
	    if (preg_match('/^([a-zA-Z]+)(\d+)$/', $key, $matches))
	    {
			$key		= $matches[1];
			$row		= $matches[2];
	    }
	    switch($key)
	    {		// take action based upon key
			case 'pattern':
			{
			    $pattern			        = $value;
			    if (strlen($pattern) > 0)
					$getParms['nickname']	= $pattern;
			    break;
			}

			case 'offset':
            {
                if (ctype_digit($value))
			        $offset			        = (int)$value;
			    break;
			}

			case 'limit':
			{
                if (ctype_digit($value))
			        $limit			        = (int)$value;
			    break;
			}

			case 'lang':
            {	// language choice
                if (strlen($value) >= 2)
			        $lang		            = strtolower(substr($value,0,2));
			    break;
			}	// language choice

			case 'name':
			{
			    $nickname	            = new Nickname($value);
			    break;
			}

			case 'prefix':
			{
			    $nickname->set('prefix', $value);
			    break;
			}

			case 'givenname':
			{
			    $nickname->set('givenname', $value);
			    break;
			}

			case 'gender':
            {
                switch(strtolower($value))
                {
                    case 'm':
                        $nickname->set('gender', 0);
                        break;

                    case 'f':
                        $nickname->set('gender', 1);
                        break;

                    case '':
                        $nickname->set('gender', null);
                        break;

                }
			    $nickname->save(false);
			    break;
			}

	    }		// take action based upon key
	}		    // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			    // invoked by method=post

$getParms['offset']	    = $offset;
$getParms['limit']	    = $limit;
$prevoffset	            = $offset - $limit;
$nextoffset	            = $offset + $limit;
$last	                = $offset + $limit;

if (canUser('edit'))
	$action		= 'Update';
else
	$action		= 'Display';

// get the list of matching nicknames
if ($debug)
	$warn	    .= "<p>\$nicknames	= new RecordSet('Nicknames'," .
							print_r($getParms,true) . ")</p>\n";
$nicknames		= new RecordSet('Nicknames',
									$getParms);
$info		    = $nicknames->getInformation();
$count		    = $info['count'];
if ($last > $count)
    $last       = $count;

$template		= new FtTemplate("Nicknames$action$lang.html");

$template->set('CONTACTSUBJECT',	$_SERVER['REQUEST_URI']);
$template->set('PATTERN',		    $pattern);

if ($nicknames)
{		// query issued
	$template->set('LIMIT',		    $limit);
	$template->set('OFFSET',	    $offset);
	$template->set('FIRST',	        $offset + 1);
    $template->set('PREVOFFSET',	$prevoffset);
    if ($prevoffset < 0)
        $template['topPrev']->update(null);
	$template->set('NEXTOFFSET',	$nextoffset);
    if ($nextoffset >= $count)
        $template['topNext']->update(null);
	$template->set('LAST',		    $last);
	$template->set('COUNT',		    $count);
	$template->set('LANG',		    $lang);

	// display the results
	$even			                = 'odd';
	$i			                    = 0;
	foreach($nicknames as $nickname)
	{
	    $nickname['even']	        = $even;
	    $nickname['i']	            = $i;
	    if ($even == 'even')
			$even		            = 'odd';
	    else
            $even		            = 'even';
        $gender                     = $nickname['gender'];
        if (is_null($gender))
            $nickname['gender']     = '';
        else
        if ($gender > 0)
            $nickname['gender']     = 'F';
        else
            $nickname['gender']     = 'M';
	    $i++;
	}
	$template['nickname$i']->update($nicknames);
}	// query issued
else
{
	$template['topBrowse']->update(null);
	$template['dataTable']->update(null);
}

$template->display();
showTrace();
