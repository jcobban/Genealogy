<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Status.php															*
 *																		*
 *  Display a web page reporting statistics about the family tree.		*
 *																		*
 *  History:															*
 *		2011/06/05		created											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/11		standardize page layout							*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/07/04		use Citation::getCitations to determine			*
 *						number of citation records in use				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/10/14		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/26      use FtTemplate                                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$lang		        = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status

$template               = new FtTemplate("Status$lang.html");

$template->set('LANG',	        $lang);

// query the database for details
// get total number of individuals in database
$recset		    		= new RecordSet('Persons', null);
$information			= $recset->getInformation();
$numPersons				= $information['count']; 

// get total number of families
$recset		    		= new RecordSet('Families', null);
$information			= $recset->getInformation();
$numFamilies			= $information['count']; 

// get total number of events
$recset		    		= new RecordSet('Events', null);
$information			= $recset->getInformation();
$numEvents				= $information['count']; 

// get number of citations
$citlist				= new RecordSet('Citations', null);
$information			= $citlist->getInformation();
$numCitations			= $information['count'];

$template->set('NUMPERSONS',	number_format($numPersons));
$template->set('NUMFAMILIES',	number_format($numFamilies));
$template->set('NUMEVENTS',	    number_format($numEvents));
$template->set('NUMCITATIONS',	number_format($numCitations));

$template->display();
