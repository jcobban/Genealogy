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
 *		2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$pattern	    				= '';
$offset		        			= 0;
$limit	        				= 20;
$lang	        				= 'en';

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch($key)
		{		// take action based upon key
		    case 'pattern':
	        {
	            if (strlen($value) > 0)
	            {
				    $pattern		    = $value;
	            }
				break;
		    }
	
		    case 'offset':
		    {
	            if (ctype_digit($value))
	            {
				    $offset			= (int)$value;
	            }
				break;
		    }
	
		    case 'limit':
		    {
	            if (ctype_digit($value))
	            {
				    $limit			= (int)$value;
	            }
				break;
		    }
	
		    case 'lang':
	        {		// language choice
	                $lang       = FtTemplate::validateLang($value);
				break;
		    }		// language choice
		}		// take action based upon key
	}
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get

$template		            = new FtTemplate("Temples$lang.html");

// get the list of matching temples
$getParms	    			= array();
if (strlen($pattern) > 0)
    $getParms['temple']	    = $pattern;
$getParms['offset']	        = $offset;
$getParms['limit']	        = $limit;
$temples					= new RecordSet('Temples',
				        		            $getParms);
$info		    			= $temples->getInformation();
$count		    			= $info['count'];

$prevoffset					= $offset - $limit;
$nextoffset		            = $offset + $limit;
$last		                = $offset + $limit - 1;

$template->set('pattern',		htmlspecialchars($pattern));

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
	$even		    	    = 'odd';
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

	if ($prevoffset < 0)
        $template->updateTag('topPrev',         '&nbsp;');
	if ($last >= $count)
	{
	    $last                   = $count;
        $template['topNext']->update('&nbsp;');
	}
}	// query issued
else
{
	$template->updateTag('topBrowse', null);
	$template->updateTag('dataTable', null);
}

$template->display();
