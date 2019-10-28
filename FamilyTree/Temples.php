<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Temples.php															*
 *																		*
 *  Display a web page containing all of the temples matching a			*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2012/12/06		created											*
 *		2013/05/23		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace table with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2018/01/04		remove Template from template file names		*
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/04/12      only use pattern if it is not empty             *
 *		                use standard element ids for page scroll        *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$getParms	    				= array();
$pattern	    				= '';
$offset		        			= 0;
$getParms['offset']				= 0;
$limit	        				= 20;
$getParms['limit']				= 20;
$lang	        				= 'en';

foreach($_GET as $key => $value)
{
	switch($key)
	{		// take action based upon key
	    case 'pattern':
        {
            if (strlen($value) > 0)
            {
			    $pattern		    = $value;
                $getParms['temple']	= $pattern;
            }
			break;
	    }

	    case 'offset':
	    {
            if (strlen($value) > 0 && ctype_digit($value))
            {
			    $offset			= (int)$value;
			    $getParms['offset']	= $offset;
            }
			break;
	    }

	    case 'limit':
	    {
            if (strlen($value) > 0 && ctype_digit($value))
            {
			    $limit			= (int)$value;
			    $getParms['limit']	= $limit;
            }
			break;
	    }

	    case 'lang':
        {		// language choice
            if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value, 0, 2));
			break;
	    }		// language choice
	}		// take action based upon key
}

$template		= new FtTemplate("Temples$lang.html");

// get the list of matching temples
$temples		= new RecordSet('Temples',
				        		$getParms);
$info		    = $temples->getInformation();
$count		    = $info['count'];

$prevoffset		= $offset - $limit;
if ($prevoffset < 0)
	$template->updateTag('topPrev', null);
$nextoffset		= $offset + $limit;
$last		    = $offset + $limit - 1;
if ($last >= $count)
{
    $last       = $count;
	$template->updateTag('topNext', null);
}

$template->set('pattern',		$pattern);

if ($count > 0)
{		// query issued
	$template->set('limit',		    $limit);
	$template->set('offset',	    $offset);
	$template->set('doffset',	    $offset+1);
	$template->set('prevoffset',	$prevoffset);
	$template->set('nextoffset',	$nextoffset);
	$template->set('last',		    $last);
	$template->set('count',		    $count);
	$template->set('lang',		    $lang);
	$even		    	= 'odd';
	// display the results
	foreach($temples as $idtr => $temple)
	{
	    $temple['even']	    = $even;
	    if ($even == 'even')
			$even	    	= 'odd';
	    else
			$even	    	= 'even';
	}
	$template->updateTag('temple$idtr', $temples);
}	// query issued
else
{
	$template->updateTag('topBrowse', null);
	$template->updateTag('detail', null);
}

$template->display();
showTrace();
