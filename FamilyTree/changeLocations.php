<?php
namespace Genealogy;
/************************************************************************
 *  changeLocations.php													*
 *																		*
 *  Display a web page to perform a pattern matching replacement on		*
 *  the names of a set of locations.									*
 *																		*
 *  History:															*
 *		2011/01/16		created											*
 *		2011/02/01		improve error checking							*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/07/31		use default dynamic initialization				*
 *						add help text for all fields					*
 *						defer facebook initialization until after load	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/10		eliminate use of tables for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/15		pass debug flag									*
 *						clean up parameter processing					*
 *						use Location::matchReplaceNames instead			*
 *						of SQL to update database						*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/07		use cookies rather than sessions				*
 *						use method=post rather than method=get			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2019/02/16      use class Template                              *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Location.inc";
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

if (!canUser('edit'))
{			// take no action
	$msg	.= 'Not authorized to update database. ';
}			// take no action

// interpret patterns
$from	        = null;
$to		        = null;
$lang           = 'en';

if (count($_GET) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters

            case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang        = strtolower(substr($value, 0, 2));
            }
		}		    // act on specific parameters
	}			    // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account
else
if (count($_POST) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{			// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'pattern':
		    {
				if (strlen($value) > 0)
				{		// search pattern specified
				    setcookie('pattern', $value);
				    $from		= $value;
				}		// search pattern specified
				break;
		    }
	
		    case 'replace':
		    {
				if (strlen($value) > 0)
				{		// search pattern specified
				    setcookie('replace', $value);
				    $to			= $value;
				}		// search pattern specified
				break;
		    }

            case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value, 0, 2));
            }
		}		// act on specific parameters
	}			// loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account


// check for missing parameters
if (is_null($from) || is_null($to))
{			// skip update, go direct to prompt
	if (is_null($from))
	{
	    if (isset($_COOKIE['pattern']))
			$from	= $_COOKIE['pattern'];
	    else
			$from	= '';
	}

	if (is_null($to))
	{
	    if (isset($_COOKIE['replace']))
			$to		= $_COOKIE['replace'];
	    else
			$to		= '';
	}
	$result		= null;
}			// skip update go direct to prompt
else
if (strlen($msg) == 0)
{		// no errors detected
	$locations	= new RecordSet('Locations',
	    						array('Location'	=> $from));
	// strip off opening caret if preset
	if (substr($from, 0, 1) == '^')
	    $from	= substr($from, 1);
	// strip off closing dollar sign if present
	if (substr($from, -1) == '$')
	    $from	= substr($from, 0, strlen($from)-1);

	$set		= array('Location'	    => array($from, $to),
						'SortedLocation'=> array($from, $to));
	$result		= $locations->update($set,
					    		     false,
						    	     false);
}		// something to do
else
    $result     = 0;

$template		= new FtTemplate("changeLocations$lang.html");

if (is_null($result))
    $template['resultLine']->update(null);
else
    $template->set('RESULT',    $result);
$template->set('FROM',          $from);
$template->set('TO',            $to);

$template->display();
