<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  TownshipsUpdate.php													*
 *																		*
 *  Update the database to reflect changes from TownshipsEdit.php.		*
 *																		*
 *  Parameters (passed by method=post):									*
 *		Prov		two letter code for province						*
 *		County		three letter code for county						*
 *																		*
 *  History:															*
 *		2012/05/07		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/29		use class Township to access database			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		debug trace was not shown						*
 *						include http.js before util.js					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/07		use class Country								*
 *						handle change of identification code			*
 *		2018/10/22      use class Template                              *
 *		2019/02/21      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

$domain	    	= 'CAON';
$prov		    = 'ON';
$countryName	= 'Canada';
$domainName		= 'Ontario';
$lang           = 'en';
$county		    = null;
$countyName		= null;
$township		= null;
$changeCount	= 0;

// organize the parameters as an associative array
$parms	        = array();
$parmsText      = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_POST as $key => $value)
{				// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{			// act on specific keys
	    case 'domain':
	    {			    // administrative domain
			$domain		    = strtoupper($value);
			break;
	    }			    // administrative domain

	    case 'prov':
	    {			    // administrative domain
			$domain		    = 'CA' . strtoupper($value);
			break;
	    }			    // administrative domain

	    case 'county':
	    {			    // county abbreviation
			$county			= $value;
			break;
	    }			    // county abbreviation

	    case 'lang':
        {			    // language code
            if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }			    // language code

	    default:
	    {			    // other input fields
			if (substr($key,0,10) == 'DeleteCode')
			{		    // delete existing township
			    break;
			}		    // delete existing township

			$matches	= array();
			$rres	= preg_match('/^([a-zA-Z]+)([0-9]+)$/', $key, $matches);
			if ($rres == 1)
			{		    // name includes row number
			    $colname	= $matches[1];
			    $rownum	    = $matches[2];
			    switch(strtolower($colname))
			    {		// act on column name
					case 'code':
					case 'name':
					case 'oldcode':
					{
					    break;
					}

					default:
					{	// other keywords
					    $msg   .= "Unrecognized parameter $key='$value'. ";
					    break;
					}	// other keywords
			    }		// act on column name
			}		    // name includes row number
	    }		    	// other input fields
	}		        	// act on specific keys
}				        // loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

// interpret parameters
$domainObj	            = new Domain(array('domain'	    => $domain,
		            	        		   'language'	=> $lang));
if ($domainObj->isExisting())
{		// valid domain
    $prov		    	= substr($domain, 2);
    $parms['domain']	= $domain;
    $cc			    	= substr($domain, 0, 2);
    $countryObj			= $domainObj->getCountry();
    $countryName		= $countryObj->getName($lang);
    $domainName			= $domainObj->getName();
}		// valid domain
else
    $msg	            .= "Invalid domain name '$value'. ";

$parms['county']		= $county;
$countyObj	        	= new County(array('domain'     => $domain, 
                                           'code'       => $county));
$countyName	        	= $countyObj->getName();

// start template
$template		        = new FtTemplate("TownshipsUpdate$lang.html");

$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	    	$domain);
$template->set('PROVNAME',	    	$domainName);
$template->set('STATENAME',	    	$domainName);
$template->set('COUNTY',	        $county);
$template->set('COUNTYNAME',	    $countyName);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Townships');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
if ($debug)
    $template->set('DEBUG',		    'Y');
else
    $template->set('DEBUG',		    'N');

$changed        = $template->getElementById('changed');
$changedHTML    = $changed->outerHTML();
$deleted        = $template->getElementById('deleted');
$deletedHTML    = $deleted->outerHTML();
$added          = $template->getElementById('added');
$addedHTML      = $added->outerHTML();
$data           = '';
$changeCount	= 0;

// notify the invoker if they are not authorized
if (!canUser('edit'))
{	                    // not authorized to update database
    $msg            .= "You are not authorized.  <a href='/Signon.php' target='_blank'> <span class='button'>Sign on</span></a> to update the database. ";
}		                // not authorized to update database
else
{ 		                // authorized
    $township		= null;
    $code		    = null;
    $oldrownum		= '1';
    $data           = '';
    foreach($_POST as $key => $value)
    {			// loop through all parameters
		if (substr($key,0,10) == 'DeleteCode')
		{		// delete existing township
		    $code		    = str_replace('_',' ',substr($key, 10));
		    $parms['code']	= $code;
            $township		= new Township($parms);
            $ttemplate      = new Template($deletedHTML);
            $ttemplate->set('CODE',     $code);
            $ttemplate->set('NAME',     $township->getName());
            $data           .= $ttemplate->compile();
		    $township->delete();
		    $changeCount++;
		    continue;
		}		// delete existing township

		$matches	= array();
		$rres   	= preg_match('/([a-zA-Z]+)([0-9]+)/', $key, $matches);
		if ($rres == 1)
		{		// name includes row number
		    $colname    	                = $matches[1];
		    $rownum	                        = $matches[2];
		    if ($rownum != $oldrownum)
		    {
				$oldrownum	                = $rownum;
				if (!is_null($township))
				{		// collected change data
				    $changed		        = false;
				    $ocode	                = $township->set('code', $newcode);
				    if ($ocode != $newcode)
				    {
				        $changed	        = true;
				        $codeMsg	        = "new identification code '$newcode'";
				    }
				    else
				        $codeMsg	        = "";
				    $name	                = $township->set('name', $newname);
                    if ($name != $newname)
                    {
                        $changed	        = true;
				        $codeMsg	        = "new name '$newname'";
                    }
				    if ($changed)
				    {		// township changed
					    if ($township->isExisting())
					        $ttemplate      = new Template($changedHTML);
					    else
					        $ttemplate		= new Template($addedHTML);;
					    $township->save(false);
					    $changeCount++;
                        $ttemplate->set('OCODE',    $ocode);
                        $ttemplate->set('NAME',     $name);
                        $ttemplate->set('CODEMSG',  $codeMsg);
                        $data               .= $ttemplate->compile();
				    }		// township changed
				    $township	            = null;
				}		    // collected change data
		    }		        // start new row

		    switch(strtolower($colname))
		    {		        // act on column name
				case 'code':
				{	        // new code name
				    $newcode	            = $value;
				    break;
				}	        // new code name

				case 'name':
				{	        // display name
				    $newname	        = $value;
				    break;
				}	        // display name

				case 'oldcode':
				{	        // original code name
				    $code		        = $value;	// lookup key
                    $parms['code']	    = $code;
				    $township		    = new Township($parms, true);
				    break;
				}	        // original code name

		    }		        // act on column name
		}		            // field name includes row number
    }			            // loop through all parameters
}			                // authorized

switch($changeCount)
{			// act on values of changeCount	
    case 0:
    {
        $statElt        = $template->getElementById('count0');
        $ttemplate      = new Template($statElt->outerHTML());
        $data           .= $ttemplate->compile();
		break;
    }

    case 1:
    {
        $statElt        = $template->getElementById('count1');
        $ttemplate      = new Template($statElt->outerHTML());
        $data           .= $ttemplate->compile();
		break;
    }

    default:
    {
        $statElt        = $template->getElementById('countmany');
        $ttemplate      = new Template($statElt->outerHTML());
        $ttemplate->set('CHANGECOUNT', $changeCount);
        $data           .= $ttemplate->compile();
		break;
    }
}			// act on values of changeCount	
$template->set('DATA', $data);

$template->display();
