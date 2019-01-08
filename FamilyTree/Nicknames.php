<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Nicknames.php							*
 *									*
 *  Display a web page containing all of the alternate given names	*
 *  matching a pattern.							*
 *									*
 *  History:								*
 *	2017/12/10	created						*
 *	2018/01/04	remove Template from template file names	*
 *	2018/01/10	use file_exists to check for defined templates	*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Nickname.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the parameters
    $getParms		= array();
    $pattern		= '';
    $offset		= 0;
    $getParms['offset']	= 0;
    $limit		= 20;
    $getParms['limit']	= 20;
    $lang		= 'en';

    if (count($_GET) > 0)
    {			// invoked by method=get
	foreach($_GET as $key => $value)
	{		// loop through parameters
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
		    $offset			= (int)$value;
		    $getParms['offset']	= $offset;
		    break;
		}
    
		case 'limit':
		{
		    $limit			= (int)$value;
		    $getParms['limit']	= $limit;
		    break;
		}
    
		case 'lang':
		{	// language choice
		    $lang		= strtolower(substr($value,0,2));
		    break;
		}	// language choice
	    }		// take action based upon key
	}		// loop through parameters
    }			// invoked by method=get
    else
    if (count($_POST) > 0)
    {			// invoked by method=post
	foreach($_POST as $key => $value)
	{		// loop through parameters
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
		    $pattern			= $value;
		    if (strlen($pattern) > 0)
			$getParms['nickname']	= $pattern;
		    break;
		}
    
		case 'offset':
		{
		    $offset			= (int)$value;
		    $getParms['offset']		= $offset;
		    break;
		}
    
		case 'limit':
		{
		    $limit			= (int)$value;
		    $getParms['limit']		= $limit;
		    break;
		}
    
		case 'lang':
		{	// language choice
		    $lang		= strtolower(substr($value,0,2));
		    break;
		}	// language choice

		case 'name':
		{
		    $nickname	= new Nickname($value);
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
		    $nickname->save(false);
		    break;
		}

	    }		// take action based upon key
	}		// loop through parameters
    }			// invoked by method=post

    $prevoffset	= $offset - $limit;
    $nextoffset	= $offset + $limit;
    $last	= $offset + $limit - 1;

    if (canUser('edit'))
	$action		= 'Update';
    else
	$action		= 'Display';

    // get the list of matching nicknames
    if ($debug)
	$warn	.= "<p>\$nicknames	= new RecordSet('Nicknames'," .
				print_r($getParms,true) . ")</p>\n";
    $nicknames		= new RecordSet('Nicknames',
					$getParms);
    $info		= $nicknames->getInformation();
    $count		= $info['count'];

    $tempBase		= $document_root . '/templates/';
    $template		= new FtTemplate("${tempBase}page$lang.html");
    $includeSub		= "Nicknames" . $action . $lang . ".html";
    if (!file_exists($tempBase . $includeSub))
    {
	$includeSub	= "Nicknames" . $action . "en.html";
    }
    $gotPage	= $template->includeSub($tempBase . $includeSub,
					'MAIN');
    $template->set('CONTACTSUBJECT',	$_SERVER['REQUEST_URI']);

    $template->set('PATTERN',		$pattern);

    if ($nicknames)
    {		// query issued
	$template->set('LIMIT',		$limit);
	$template->set('OFFSET',	$offset);
	$template->set('PREVOFFSET',	$prevoffset);
	$template->set('NEXTOFFSET',	$nextoffset);
	$template->set('LAST',		$last);
	$template->set('COUNT',		$count);
	$template->set('LANG',		$lang);

	// display the results
	$even			= 'odd';
	$i			= 0;
	foreach($nicknames as $nickname)
	{
	    $nickname['even']	= $even;
	    $nickname['i']	= $i;
	    if ($even == 'even')
		$even		= 'odd';
	    else
		$even		= 'even';
	    $i++;
	}
	$template->updateTag('nickname$i', $nicknames);
    }	// query issued
    else
    {
	$template->updateTag('linksfront', null);
	$template->updateTag('details', null);
    }

    $template->display();
    showTrace();
