<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  CountiesEdit.php													*
 *																		*
 *  Display form for editting information about counties for			*
 *  provincially administered records									*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain	2 letter country code + 2 letter state/province code	*
 *		Prov	two letter province code								*
 *		lang	two character ISO language code							*
 *		offset	starting offset											*
 *		limit	max number of rows to return							*
 *																		*
 *  History:															*
 *		2012/05/07		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/13		use CSS for form layout							*
 *						add support for additional domains				*
 *		2014/10/19		display country and state/province links in		*
 *						header and footer								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/06/09		close <tbody>									*
 *		2017/01/23		do not escape & in county name					*
 *		2017/02/07		use class Country								*
 *		2018/01/02		get state name with language					*
 *		2018/01/09		use class Template								*
 *		2018/01/11		htmlspecchars moved into class Template			*
 *		2018/01/22		display only part of the table at a time		*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2019/04/06      use new FtTemplate::includeSub                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2020/03/27      simplify parameter handling                     *
 *		                and fix premature template creation             *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$domain			    		= 'CAON';
$prov			    		= 'ON';
$cc				    		= 'CA';
$countryName				= 'Canada';
$stateName					= 'Ontario';
$domainName					= 'Canada: Ontario:';
$lang			    		= 'en';
$offset			    		= 0;
$limit			    		= 20;
$changedText				= null;
$deletedText				= null;
$addedText	    			= null;
$get                        = true;

if (isset($_GET) && count($_GET) > 0)
{                       // initial invocation from URL
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{
		    case 'prov':
		    {
				if (preg_match('/[a-zA-Z]{2,3}/', $value) == 1)
				{
				    $prov			= $value;
				    $domain			= 'CA' . $value;
				}
				break;
		    }		// state/province code

		    case 'domain':
		    {
				if (preg_match('/[a-zA-Z]{4,5}/', $value) == 1)
				    $domain			= $value;
				break;
		    }		// state/province code

		    case 'lang':
		    {
                $lang       = FtTemplate::validateLang($value);
				break;
		    }		// debug handled by common code

		    case 'offset':
		    {
				if (is_numeric($value) || ctype_digit($value))
				    $offset			= $value;
				break;
		    }

		    case 'limit':
		    {
				if (is_numeric($value) || ctype_digit($value))
				    $limit			= $value;
				break;
		    }

		    case 'debug':
		    {
				break;
		    }		// debug handled by common code

		    default:
		    {
				$warn	.= "Unexpected parameter $key='$value'. ";
				break;
		    }
		}		// check supported parameters
	}			// loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}                       // initial invocation from URL
else
if (isset($_POST) && count($_POST) > 0)
{                       // invoked to process update
    $get                        = false;

	// organize the parameters as an associative array of instances
	// of the class County
	$counties	                = array();
	$county	                    = null;
	$parmsText                  = "<p class='label'>\$_POST</p>\n" .
                	                  "<table class='summary'>\n" .
	                                  "<tr><th class='colhead'>key</th>" .
	                                  "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{				// loop through all parameters
	    $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
	                            "<td class='white left'>$value</td></tr>\n"; 
		$matches		        = array();
		$fieldLc		        = strtolower($key);
	    if(preg_match('/^(code|name|delete|endyear|startyear|edittownships|editlocation)(.*)$/', $fieldLc, $matches))
	    {
	        $column             = $matches[1];
		    $row	            = $matches[2];
		}
		else
		if (preg_match('/^([a-zA-Z]+)(\d*)$/', $fieldLc, $matches))
		{
		    $column	            = $matches[1];
		    $row    	        = $matches[2];
	    }

		switch(strtolower($column))
		{		            	// act on specific keys
		    case 'domain':
	        {
	            $domain         = $value;
				break;
		    }			            // domain

		    case 'prov':
		    {
				$prov		    = $value;
				$domain		    = 'CA' . $value;
				break;
		    }		            // state/province code

		    case 'code':
	        {	            	// county by county fields
				$parms		    = array('domain'    => $domain,
		    					    	'code'	    => strtoupper($value));
				$county		    = new County($parms);
	            $counties[$county->get('code')]	= $county;
				break;
		    }

		    case 'name':
		    {
				$county->set('name', ucfirst($value));
				break;
		    }

		    case 'startyear':
		    {
				$county->set('startyear', $value);
				break;
		    }

		    case 'endyear':
		    {
                $county->set('endyear', $value);
                $county         = null;
				break;
		    }

		    case 'offset':
		    {
	            if (ctype_digit($value))
	                $offset     = $value - 0;
				break;
		    }

		    case 'limit':
		    {
	            if (ctype_digit($value))
	                $limit       = $value - 0;
				break;
		    }

		    case 'lang':
		    {
                $lang           = FtTemplate::validateLang($value);
				break;
		    }

            case 'countryname':
            case 'statename':
            case 'debug':
                break;

		    default:
		    {
				$warn	.= "<p>Unrecognized parameter $key='$value'. </p>\n";
				break;
		    }			        // unrecognized parameter
	    }
	}			            	// loop through all parameters

    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			    // invoked by method=post

if (canUser('edit'))
	$action			    		= 'Edit';
else
    $action			    		= 'Display';

// create template
$template			    		= new FtTemplate("Counties$action$lang.html");

$includeSub			    		= "CountiesDialogs$lang.html";
$template->includeSub($includeSub, 'DIALOGS', true);

// analyse parameters
$domainObj	            		= new Domain(array('domain'	    => $domain,
           				                	       'language'	=> $lang));
if ($domainObj->isExisting())
{
    $cc			        		= substr($domain, 0, 2);
    $prov		        		= substr($domain, 2, 2);
	$stateName					= $domainObj->getName(0);
    $domainName	        		= $domainObj->getName(1);
}
else
{
    $msg		                .= "Domain='$domain' unsupported. ";
    $domainName	        		= 'Unknown';
    $stateName	        		= 'Unknown';
}
$countryObj		        		= new Country(array('code' => $cc));
$countryName	        		= $countryObj->getName();
$changed        	    		= $template['changed'];
$deleted        	    		= $template['deleted'];
$added          	    		= $template['added'];
$summary                		= $template['summary'];
if ($get && !is_null($summary))
    $summary->update(null);

// apply updates
if (isset($_POST) && count($_POST) > 0)
{			        // invoked by method=post

	// put last entry into table
	if ($county)
		$counties[$county->get('code')]	= $county;
	$changedHTML    				= $changed->outerHTML();
	$deletedHTML    				= $deleted->outerHTML();
	$addedHTML      				= $added->outerHTML();
	$data           				= '';
	$changeCount	                = 0;
	if (canUser('update'))
	{
		foreach($counties as $code => $county)
	    {		    			// loop through all rows in database
	        $county->dump('updating');
	        if ($county->getName() == '')
	        {                   // delete county
	            $count              = $county->delete(false);
				if ($count > 0)
			    {
			        $changeCount	+= $count;
			        $ttemplate      = new Template($deletedHTML);
		            $ttemplate->set('CODE',		            
	                                $code);
			        $data           .= $ttemplate->compile();
	            }
	        }                   // delete county
	        else
	        if ($county->isExisting())
	        {                   // update existing county
				$count	            = $county->save(false);
				if ($count > 0)
			    {
			        $changeCount	+= $count;
			        $ttemplate      = new Template($changedHTML);
		            $ttemplate->set('CODE',		            
		                            $code);
		            $ttemplate->set('NEWCOUNTYNAME',	
		                            $county->get('name'));
		            $ttemplate->set('NEWCOUNTYSTARTYEAR',	
		                            $county->get('startYear'));
		            $ttemplate->set('NEWCOUNTYENDYEAR',     
		                            $county->get('endYear'));
			        $data           .= $ttemplate->compile();
	            }				// record changed
	        }                   // update existing county
	        else
	        {                   // create new county
				$count              = $county->save(false);
			    $changeCount++;
			    $ttemplate          = new Template($addedHTML);
				$ttemplate->set('COUNTY',           $county->get('code'));
				$ttemplate->set('COUNTYNAME',       $county->get('name'));
				$ttemplate->set('COUNTYSTARTYEAR',  $county->get('startyear'));
			    $ttemplate->set('COUNTYENDYEAR',    $county->get('endyear'));
			    $data               .= $ttemplate->compile();
	        }                   // create new county
		}					    // loop through all rows in database


		$changedText				= null;
		$deletedText				= null;
	    $addedText	    			= $data;
	}                   // user can update
	else
	{                   // user cannot update
		$changedText				= null;
		$deletedText				= null;
	    $addedText	    			= null;
	}                   // user cannot update

    $template->set('CHANGECOUNT',    $changeCount);
    if ($changeCount == 0)
        $template['summary']->update(null);
}                       // invoked to process update

if (strlen($msg) == 0)
{			// no errors detected
	// execute the query to get the contents of the page
	$getParms		= array('domain'	=> $domain,
							'offset'	=> $offset,
							'limit'		=> $limit);
	$counties		= new CountySet($getParms);
}			// no errors detected

$template->set('CONTACTTABLE',	    'Counties');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('DOMAIN',		    $domain);
$template->set('DOMAINNAME',	    $domainName);
$template->set('STATENAME',	        $stateName);
$template->set('CC',		        $cc);
$template->set('COUNTRYNAME',	    $countryName);
$template->set('LANG',              $lang);
$template->set('CONTACTTABLE',	    'Counties');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('OFFSET',            $offset);
$template->set('LIMIT',             $limit);
$info		= $counties->getInformation();
$count		= $info['count'];
$template->set('TOTALROWS',         $count);
$template->set('FIRST',             $offset + 1);
$template->set('LAST',              min($count, $offset + $limit));
if ($offset > 0)
	$template->set('npPrev', "&offset=" . ($offset-$limit) . "&limit=$limit");
else
	$template->updateTag('prenpprev', null);
if ($offset < $count - $limit)
	$template->set('npNext', "&offset=" . ($offset+$limit) . "&limit=$limit");
else
	$template->updateTag('prenpnext', null);
if (strlen($msg) > 0)
	$template->updateTag('countyForm', null);

$template->updateTag('Row$code',
					 $counties);
$changed->update($changedText);
$deleted->update($deletedText);
$added->update($addedText);

$template->display();
