<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  EditCensuses.php													*
 *																		*
 *  Display form for editting information about a Census				*
 *																		*
 *  History:															*
 *		2016/01/21		created											*
 *		2017/09/12		use get( and set(								*
 *		2018/01/12		use class Template								*
 *		2019/02/19      use new FtTemplate constructor                  *
 *		                add support for multiple countries              *
 *		                Delete requested by name='Delete'               *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		    = 'en';
$cc		        = 'CA';
$offset         = 0;
$limit          = 20;
$getParms		= array();			// all Censuses

if (isset($_GET) && count($_GET) > 0)
{			// method=get invoked from URL
    $census		= null;
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $name => $value)
    {		// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$name</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($name))
        {	    // act on parameter name
            case 'cc':
            {
                if (strlen($value) >= 2)
				    $cc		    = strtoupper(substr($value,0,2));
				break;
            }   // country code

		    case 'lang':
            {
                if (strlen($value) >= 2)
				    $lang		= strtolower(substr($value,0,2));
				break;
            }   // language code

            case 'offset':
            {
                $offset         = $value;
                break;
            }

            case 'limit':
            {
                $limit         = $value;
                break;
            }
		}	    // act on parameter name
    }	    	// loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

}			    // method=get
else
if (isset($_POST) && count($_POST) > 0)
{			    // method=post, update
    $census		= null;
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_POST as $name => $value)
    {		// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$name</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        if (preg_match("/^([a-zA-Z_]+)(\d*)$/",
    				   $name,
    				   $matches) == 1)
            $column		    = strtolower($matches[1]);
        else
            $column         = strtolower($name);
		switch($column)
		{	    // act on column name
		    case 'censusid':
		    {
                if (!is_null($census))
                {
                    if (strtolower($census['name']) == 'delete')
                        $census->delete(false);
                    else
                        $census->save(false);
                }
                if (strlen($value) >= 6)
				    $census		= new Census(array('censusid'   => $value,
                                                   'create'     => true));
                else
                    $census     = null;
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

            case 'cc':
            {
                if (strlen($value) >= 2)
				    $cc		    = strtoupper(substr($value,0,2));
				break;
            }   // country code

		    case 'lang':
		    {
                if (strlen($value) >= 2)
				    $lang		= strtolower(substr($value,0,2));
				break;
		    }

            case 'offset':
            {
                $offset         = $value;
                break;
            }

            case 'limit':
            {
                $limit         = $value;
                break;
            }
		}	    // act on column name
    }	    	// loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			    // update

// create the template
$update		    = canUser('admin');
if ($update)
    $action		= 'Update';
else
    $action		= 'Display';
$template	    = new FtTemplate("EditCensuses$action$lang.html");

// get the censuses in the correct order
$getParms       = array('cc'        => $cc,
                        'offset'    => $offset,
                        'limit'     => $limit);
$censuses		= new CensusSet($getParms);
$info           = $censuses->getInformation();
$total          = $info['count'];
$count          = $censuses->count();
if ($count == 0)
{
    $censuses[$cc . '1790'] = new Census(array('CensusId' => $cc.'1790'));
}
$template->set('OFFSET',    $offset);
$template->set('LIMIT',     $limit);
$template->set('TOTAL',     $total);
$template->set('FIRST',     $offset+1);
$template->set('LAST',      $offset + $count);
if ($offset == 0)
    $template['topPrev']->update(null);
else
{
    $npPrev         = "EditCensuses.php?cc=$cc&limit=$limit&offset=" .
                        ($offset - $limit);
    if ($debug)
        $npPrev     .= "&debug=Y";
    $template->set('NPPREV',        $npPrev);
}
$last               = $offset + $limit;
if ($last >= $total)
    $template['topNext']->update(null);
else
{
    $npNext         = "EditCensuses.php?cc=$cc&limit=$limit&offset=" .
                        ($offset + $limit);
    if ($debug)
        $npNext     .= "&debug=Y";
    $template->set('NPNEXT',        $npNext);
}

$line		    = '01';
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

$title	        = "Table of Censuses";
$template->set('CC',		        $cc);
$country        = new Country(array('code'  => $cc));
$template->set('COUNTRYNAME',		$country->getName($lang));
$template->set('LANG',			    $lang);
$template->set('CONTACTTABLE',		'Censuses');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

if (strlen($msg) > 0)
    $template->updateTag('censusForm', null);
else
    $template->updateTag('Row$line',
    		             $censuses);

$template->display();
showTrace(); 
