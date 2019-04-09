<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Addresses.php														*
 *																		*
 *  Display a web page containing all of the addresses matching a		*
 *  pattern.															*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		pattern			regular expression to match names				*
 *		offset			starting offset within result set				*
 *		limit			number of entries to display from result set	*
 *		repositories	if set show repository entries					*
 *		event			if set show event entries						*
 *		mailing			if set show mailing entries (requires master	*
 *						auth)											*
 *																		*
 *  History:															*
 *		2010/12/04		created											*
 *		2012/01/13		change class names								*
 *		2012/01/28		default mailing and repositories to selected	*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/02/21		add a button for adding a repository address	*
 *						add help text for all input fields and buttons	*
 *		2013/02/23		cleanup											*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/19		replace tables with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		replace SQL query with call to					*
 *						Address::getAddresses							*
 *		2016/01/19		add id to debug trace							*
 *		2016/01/19		include http.js									*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/08		improve parameter validation					*
 *		2017/10/16		use class RecordSet								*
 *		2018/02/12		use Template									*
 *						support delete									*
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

$kindToText		= array('Mail','Event','Repo');

// get the parameters
// set defaults
$pattern		= '';
$offset		= 0;
$limit		= 20;
$repositories	= 0;
$event		= 0;
$mailing		= 0;
$lang		= 'en';

// override from passed parameters
if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $value		= trim($value);
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'pattern':
		    {			// name search parameter
				$pattern	= $value;
				break;
		    }			// name search parameter
	
		    case 'offset':
		    {			// offset of first row
				if (ctype_digit($value))
				    $offset	= (int)$value;
				else
				    $msg	.= "Offset='$value' invalid. ";
				break;
		    }			// offset of first row
	
		    case 'limit':
		    {			// max number of rows
				if (ctype_digit($value))
				    $limit	= (int)$value;
				else
				    $msg	.= "Limit='$value' invalid. ";
				break;
		    }			// max number of rows
	
		    case 'repositories':
		    {			// repositories
				if (ctype_digit($value))
				    $repositories	= (int)$value;
				else
				    $msg	.= "Repositories='$value' invalid. ";
				break;
		    }			// respositories
	
		    case 'event':
		    {			// event addresses
				if (ctype_digit($value))
				    $event	= (int)$value;
				else
				    $msg	.= "Limit='$value' invalid. ";
				break;
		    }			// event addresses
	
		    case 'mailing':
		    {			// only master user can see mailing addresses
				if (canUser('all'))
				    $mailing	= (int)$value;
				break;
		    }			// only master user can see mailing addresses
	
		    case 'lang':
		    {			// user requested language
				if (strlen($value))
				    $lang	= strtolower($value);
				break;
		    }			// user requested language
		}		    // act on specific parameters
	}			    // loop through input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{			        // invoked by method=get
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		$matches	= array();
		if (preg_match('/^([a-zA-Z]+)(\d+)$/',
						$key,
						$matches))
		{
		    $col	= strtolower($matches[1]);
		    $idar	= $matches[2];
		}
		else
		{
		    $col	= strtolower($key);
		    $idar	= '';
		}
		$value		= trim($value);
		switch($col)
		{		// act on specific parameters
		    case 'pattern':
		    {			// name search parameter
				$pattern	= $value;
				break;
		    }			// name search parameter
	
		    case 'offset':
		    {			// offset of first row
				if (ctype_digit($value))
				    $offset	= (int)$value;
				else
				    $msg	.= "Offset='$value' invalid. ";
				break;
		    }			// offset of first row
	
		    case 'limit':
		    {			// max number of rows
				if (ctype_digit($value))
				    $limit	= (int)$value;
				else
				    $msg	.= "Limit='$value' invalid. ";
				break;
		    }			// max number of rows
	
		    case 'repositories':
		    {			// repositories
				if (ctype_digit($value))
				    $repositories	= (int)$value;
				else
				    $msg	.= "Repositories='$value' invalid. ";
				break;
		    }			// respositories
	
		    case 'event':
		    {			// event addresses
				if (ctype_digit($value))
				    $event	= (int)$value;
				else
				    $msg	.= "Limit='$value' invalid. ";
				break;
		    }			// event addresses
	
		    case 'mailing':
		    {			// only master user can see mailing addresses
				if (canUser('all'))
				    $mailing	= (int)$value;
				break;
		    }			// only master user can see mailing addresses
	
		    case 'lang':
		    {			// user requested language
				if (strlen($value) >= 2)
				    $lang	        = strtolower(substr($value, 0, 2));
				break;
		    }			// user requested language
	
		    case 'kind':
		    {
				$kind		= $value;
				break;
		    }
	
		    case 'action':
		    {
				if (strtolower($value) == 'delete')
				{
				    $address	= new Address(array('idar'	=> $idar,
									    'kind'	=> $kind));
				    $address->delete(false);
				}
				break;
		    }
		}		// act on specific parameters
	}			// loop through input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			    // invoked by method=post

// at least one address type must be selected
if ($event == 0 && $mailing == 0)
	$repositories	= 1;

$prevOffset		= $offset - $limit;
$nextOffset		= $offset + $limit;

// construct the query

$getParms		= array('offset'	=> $offset,
						'limit'		=> $limit);
$kindValues		= array();
if ($repositories)
	$kindValues[]	= 2;
if ($event)
	$kindValues[]	= 1;
if ($mailing)
	$kindValues[]	= 0;
if (count($kindValues) == 1)
	$getParms['kind']	= $kindValues[0];
else
if (count($kindValues) > 1)
	$getParms['kind']	= $kindValues;

if (strlen($pattern) > 0)
	$getParms['addrname']	= $pattern;


// to avoid a long wait, first check to see how many responses there are
$addresses	= new RecordSet('Addresses',
						$getParms,
						'AddrName, IDAR');
$count	= $addresses->getInformation()['count'];

if (canUser('edit'))
	$action		= 'Edit';
else
	$action		= 'Display';

$template		= new FtTemplate("Addresses$action$lang.html");

if ($count == 0)
{
	$template->set('COUNT', 'No');
}
else
{		// got some results
	$template->set('COUNT', number_format($count));
}
$template->set('PATTERN', $pattern);
if ($repositories)
	$template->set('REPOCHECKED', 'checked="checked"');
else
	$template->set('REPOCHECKED', '');
if ($event)
	$template->set('EVENTCHECKED', 'checked="checked"');
else
	$template->set('EVENTCHECKED', '');
if (canUser('all'))
{			// only administrator can view mailing addresses
	if ($mailing)
	   $template->set('MAILCHECKED', 'checked="checked"');
	else
	    $template->set('MAILCHECKED', '');
}			// only administrator can view mailing addresses
else
	$template->updateTag('mailCheck', null);

if ($count > 0)
{		// query issued
	if ($prevOffset >= 0)
	    $template->updateTag('prevPage',
						 array('limit'		=> $limit,
                               'prevOffset'	=> $prevOffset,
                               'LANG'       => $lang));
	else
	    $template->updateTag('prevPage', null);

	if ($nextOffset < $count)
	    $template->updateTag('nextPage',
						 array('limit'		=> $limit,
						       'nextOffset'	=> $nextOffset,
                               'LANG'       => $lang));
	else
	    $template->updateTag('nextPage', null);
	$first	= $offset + 1;
	$last	= min($nextOffset, $count);
	$template->updateTag('summary',
					     array('first'	=> $first,
						   'last'	=> $last,
						   'count'	=> $count));

	$addrClass		= 'odd';
	foreach($addresses as $idar => $address)
	{
	    $kind		= $address->get('kind'); 
	    $address->set('kindtext', $kindToText[$kind]); 
	    $address->set('addrClass', $addrClass);
	    $address->set('lang', $lang);
	    if ($addrClass == 'odd')
			$addrClass	= 'even';
	    else
			$addrClass	= 'odd';
	}
	$template->updateTag('addr$idar',
					     $addresses);
}		// display the results
else
	$template->updateTag('addressesTable', null);

$template->display();
