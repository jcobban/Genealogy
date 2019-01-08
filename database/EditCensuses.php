<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  EditCensuses.php							*
 *									*
 *  Display form for editting information about a Census		*
 *									*
 *  History:								*
 *	2016/01/21	created						*
 *	2017/09/12	use get( and set(				*
 *	2018/01/12	use class Template				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Template.inc';
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/CensusSet.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // notify the invoker if they are not authorized
    $update		= canUser('admin');
    if ($update)
	$action		= 'Update';
    else
	$action		= 'Display';
    $lang		= 'en';
    $getParms		= array();			// all Censuses

    if (isset($_POST) && count($_POST) > 0)
    {			// update
	$census		= null;
	foreach ($_POST as $name => $value)
	{		// loop through parameters
	    if (preg_match("/^([a-zA-Z_]+)(\d*)$/",
			   $name,
			   $matches) == 1)
	    {		// data field
		$column		= strtolower($matches[1]);
		switch($column)
		{	// act on column name
		    case 'censusid':
		    {
			if (!is_null($census))
			    $census->save(false);
			$census		= new Census(array('censusid' => $value,
							   'create' => true));
			break;
		    }

		    case 'name':
		    case 'linesperpage':
		    case 'grouplines':
		    case 'lastunderline':
		    {
			$census->set($column, $value);
			break;
		    }

		    case 'collective':
		    {
			if (strtoupper($value) == 'Y')
			    $value	= true;
			else
			    $value	= false;
			$census->set('collective', $value);
			break;
		    }

		    case 'partof':
		    {
			if (strlen($value) >= 2)
			    $census->set('partof', strtoupper($value));
			else
			    $census->set('partof', null);
			break;
		    }

		    case 'provinces':
		    {
			$census->set('provinces',
				     strtoupper(str_replace(',','',$value)));
			break;
		    }

		    case 'lang':
		    {
			$lang		= strtolower(substr($value,0,2));
			break;
		    }
		}	// act on column name
	    }		// data field
	}		// loop through parameters

	// update last census
	if (!is_null($census))
	    $census->save(false);

    }			// update

    // get the censuses in the correct order
    $censuses		= new CensusSet($getParms);
    $line		= '01';
    foreach($censuses as $census)
    {
	$census->set('line', $line);
	if (is_null($census->get('partof')))
	    $census->set('partof', '');
	if (is_null($census->get('idsr')))
	    $census->set('idsr', '');
	if ($census->get('collective') == 0)
	    $census->set('collective', '');
	else
	    $census->set('collective', 'Y');
	$line++;
	if (strlen($line) == 1)
	    $line	= '0' . $line;
	$census->dump('listing');
    }

    $title	= "Table of Censuses";
    $tempBase	= $document_root . '/templates/';
    $template	= new FtTemplate("${tempBase}page$lang.html");
    $includeSub	= "EditCensuses$action$lang.html";
    if (!file_exists($tempBase . $includeSub))
	$includeSub	= "EditCensuses{$action}en.html";
    $template->includeSub($tempBase . $includeSub,
			  'MAIN');
    $template->set('COUNTRYNAME',		'Canada');
    $template->set('LANG',			$lang);
    $template->set('CONTACTTABLE',		'Censuses');
    $template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

    if (strlen($msg) > 0)
	$template->updateTag('censusForm', null);
    else
	$template->updateTag('Row$line',
		$censuses);

    $template->display();
    showTrace(); 
