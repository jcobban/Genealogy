<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MonthlyUpdates/index.php											*
 *																		*
 *  This script translates the Monthly Updates directory listing        *
 *  to human form.                                                      *
 *																		*
 *    History:															*
 *		2018/11/18      created                                         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *		open code														*
 ***********************************************************************/
$cc			    = 'CA';
$countryName	= 'Canada';
$lang		    = 'en';		// default english

// determine which districts to display
foreach ($_GET as $key => $value)
{			// loop through all parameters
	switch(strtolower($key))
	{
	    case 'lang':
	    {		// requested language
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value, 0, 2));
			break;
	    }		// requested language

	    case 'debug':
	    {		// requested debug
			break;
	    }		// requested debug
	}		// switch on parameter name
}			// foreach parameter
$update     = canUser('edit');

$template	= new FtTemplate("MonthlyUpdates$lang.html");
$trtemplate = $template->getTranslate();

// get month names
$monthTag		= $trtemplate['Months'];
if ($monthTag)
{
	$monthnames	= array('');
	foreach($monthTag->childNodes() as $tag)
	    $monthnames[]	= trim($tag->innerHTML());
}
else
	$monthnames	= array('',
					'January',  'February',	'March',	'April',
					'May',	    'June',	'July',		'Augustt',
					'September','October',	'November',	'December');

// create list of reports
$names	        = array();
$dh		        = opendir('.');
if ($dh)
{		// found MonthlyUpdates directory
	while (($filename = readdir($dh)) !== false)
	{		// loop through files
	    if (strlen($filename) > 4 &&
			substr($filename, strlen($filename) - 4) == '.pdf')
			$names[]	= $filename;
	}		// loop through files
	rsort($names);
}		// found Newsletters directory

$reports	= array();
for ($i = 0; $i < count($names); $i++)
{		// loop through reports in order
	$filename	= $names[$i];
	$y		= substr($filename,6,4);
	$m		= substr($filename,11,2);
	$month		= $monthnames[$m - 0];
	$reports[]	= array(
			    'filename'		=> $filename,
			    'mm'		=> $m,
			    'month'		=> $month,
			    'year'		=> $y);
}		// loop through reports in order
$template->updateTag('report$mm$year',
					 $reports);
$template->display();
