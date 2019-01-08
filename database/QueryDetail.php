<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  QueryDetail.php														*
 *																		*
 *  Display query dialog for a census of Canada.						*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		Census			identifier of census 'XX9999'					*
 *		Province		optional 2 letter province code					*
 *																		*
 *  History:															*
 *		2017/09/19		created											*
 *		2017/10/16		use class DomainSet								*
 *		2018/01/04		remove Template from template file names		*
 *		2018/05/20		add popups										*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// set default values that are overriden by parameters

$censusYear			= 1881;		// census year
$cc		    		= 'CA';		// country code
$countryName		= 'Canada';	// country name
$lang				= 'en';		// default language
$province			= 'CW';		// selected province
$states				= 'ABBCMBNBNSNTONPIQCSKYT';
$censusRec	        = null;

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach ($_GET as $key => $value)
	{				// loop through all parameters
		if (is_array($value))
	        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                        "<td class='white left'>array</td></tr>\n"; 
	    else
	        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                        "<td class='white left'>$value</td></tr>\n"; 
	
	    switch(strtolower($key))
	    {			// switch on parameter name
			case 'census':
			{			// Census identifier
			    $censusId		= $value;
	
			    if (strtoupper($censusId) == 'CAALL')
			    {		// special census identifier to search all
					$cc			    = substr($censusId, 0, 2);
					$censusYear		= substr($censusId, 2);
					$province		= 'CW';	// for pre-confederation
			    }		// special census identifier
			    else
			    {		// full census identifier
					$censusRec	= new Census(array('censusid'	=> $value));
					if ($censusRec->isExisting())
					{
					    $cc			= substr($censusId, 0, 2);
					    $censusYear		= substr($censusId, 2);
					    if ($censusRec->get('partof'))
					    {
					    	$province	= substr($censusId, 0, 2);
					    	$cc		    = $censusRec->get('partof');
					    	$parentRec	= new Census(array('censusid' =>
						                				$cc . $censusYear));
					    	$states		= $parentRec->get('provinces');
					    }
					    else
						$states		= $censusRec->get('provinces');
					}
					else
					    $msg .= "Census value '$censusId' invalid. ";
			    }		// full census identifier
			    break;
			}			// Census identifier
	
			case 'lang':
			{			// language code
			    if (strlen($value) >= 2)
			    {
					$lang		= strtolower(substr($value,0,2));
			    }
			    break;
			}			// language code
	
	    }			// switch on parameter name
	}				// foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

// support for countries other than Canada
if ($cc != 'CA')
{
    $countryObj		= new Country(array('code'=> $cc));
    $countryName	= $countryObj->getName($lang);
}

// determine contents of province/state selection list
$stateArray		= array();
for ($i = 0; $i < strlen($states); $i += 2)
    $stateArray[]	=  $cc . substr($states, $i, 2);
$getParms		= array('domain'	=> $stateArray,
				'lang'		=> $lang);
$stateList		= new DomainSet($getParms);
$selection		= array();
foreach($stateList as $state)
{
    $sel	= array('statecode'	=> $state->get('state'),
			'statename'	=> $state->get('name'),
			'selected'	=> '');
    if ($state->get('state') == $province)
	$sel['selected']		= 'selected="selected"';
    $selection[]	= $sel;
}

// create template
if (strtoupper($censusYear) == 'ALL')
    $censusYear	= 'All';

$title	    	= "$censusYear Census of $countryName Query Request";
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub 	= "Query$censusYear$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$langObj	= new Language(array('code' => $lang));
	$warn		.= "<p>Language=" . $langObj->get('name') . '/' . 
			    $langObj->get('nativename') . 
			    " is not supported</p>\n";
	$includeSub	= "Query{$censusYear}en.html";
}
$template->includeSub($tempBase . $includeSub,
		        'MAIN');
$includePop 	= "CensusQueryPopups$lang.html";
if (!file_exists($tempBase . $includePop))
{
	$includePop 	= 'CensusQueryPopupsen.html';
}
$template->includeSub($tempBase . $includePop,
	    		      'POPUPS');
$template->set('CENSUSYEAR', 	$censusYear);
$template->set('COUNTRYNAME',	$countryName);
$template->set('CENSUSID',		$censusId);
$template->set('LANG',		$lang);
$template->set('CENSUS',		$censusYear);
$template->set('CONTACTTABLE',	'Census' . $censusYear);
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->updateTag('stateoption',
			 $selection);
$template->display();
