<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdateSubDists.php												*
 *																		*
 *  Request to update or view a portion of the SubDistricts database.	*
 *																		*
 *  History (of ReqUpdateSubDists.html):								*
 *		2010/10/01		Reformat to new page layout.					*
 *		2010/11/24		link to help page								*
 *		2011/01/20		increase size of selects to 9 so all censuses	*
 *						display											*
 *		2011/03/09		improve separation of HTML and Javascript		*
 *		2011/06/27		add support for 1916 census						*
 *		2012/09/17		support census identifiers						*
 *		2013/07/30		add Facebook like								*
 *						add help for all form elements					*
 *						standardize appearance of submit button			*
 *		2013/08/18		add support for 1921 census						*
 *		2014/06/29		clear up layout of <h1> and botcrumbs			*
 *		2015/03/15		internationalize all text strings including		*
 *						province names									*
 *		2015/12/10		escape province names							*
 *																		*
 *  History (of ReqUpdateSubDists.php):									*
 *		2015/06/02		renamed and made conditional on user's auth		*
 *						display warning messages						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/20		display debug trace								*
 *						display error messages							*
 *		2017/09/12		use get( and set(								*
 *		2020/03/08      use template                                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$cc			            	= 'CA';
$countryName				= 'Canada';
$censusId					= '';
$censusYear					= '';
$provinces					= '';
$province					= 'CW';
$distId				    	= null;
if (isset($_GET) && count($_GET) > 0)
{	        	        // invoked by URL
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
				                   "<table class='summary'>\n" .
				                      "<tr><th class='colhead'>key</th>" .
				                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{				    // loop through parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{			    // act on specific parameters
		    case 'census':
		    {			// census identifier
				// support old parameter value
				if (strlen($value) == 4)
				{
				    if (intval($censusYear) < 1867)
					    $censusId	= 'CW' . $value;
				    else
					    $censusId	= 'CA' . $value;
				}
				else
				    $censusId	    = $value;
	
				break;
		    }			// census identifier
	
		    case 'province':
            {			// province code
                if (is_string($province))
                    $province	    = strtoupper($value);
                else
                    $msg	        .= "Invalid value '$value' for Province. ";
				break;
		    }			// province code
	
		    case 'district':
		    {			// district number
				$distId		        = $value;
				$result		        = array();
				if (preg_match("/^([0-9]+)(\.[05])?$/", $distId, $result) != 1)
				    $msg	        .= "District value '$distId' is invalid. ";
				else
				{
			        if (count($result) > 2 && $result[2] == '.0')
				        $distId	    = $result[1];	// integral portion only
			    }
			    break;
            }			// District number

			case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
		}			    // act on specific parameters
    }				    // loop through parameters
    if ($debug)
        $warn                       .= $parmsText . "</table>\n";
}	        	        // invoked by URL

$template           			= new FtTemplate("ReqUpdateSubDists$lang.html");
$translate          			= $template->getTranslate();
$t                  			= $translate['tranTab'];

if (canUser('edit'))
    $action                     = $t['Update'];
else
    $action                     = $t['Display'];

// validate Census ID
$censusYear	        			= substr($censusId, 2);
$censusRec	        			= new Census(array('censusid'   => $censusId,
	        	    	    	                   'collective'	=> 0,
    		    	    	                       'create'     => true));
if ($censusRec->get('partof'))
{
    $cc	    	    			= $censusRec->get('partof');
    $censusId	    			= $cc . $censusYear;
}
else
    $cc	    	    			= substr($censusId, 0, 2);
	
if ($cc == 'CA')
{
    $provinceText				= $t['Province'];
    $provincesText				= $t['Provinces'];
}
else
{
    $provinceText				= $t['State'];
    $provincesText				= $t['States'];
}
$countryObj     				= new Country(array('code' => $cc));
$countryName    				= $countryObj->get('name');
$provinces	        			= $censusRec->get('provinces');
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('PROVINCESTEXT',     $provincesText);
$template->set('PROVINCETEXT',      $provinceText);

// validate province code
if (strlen($province) >= 2)
{		// have a province
    $pos	                    = strpos($provinces, $province);
}		// have a province
else
    $warn	        .= "<p>Invalid value '$value' for Province ignored.</p>\n";

$getParms	= array('partof'	    => null,
    				'countrycode'	=> $cc);
$censuses	= new CensusSet($getParms);
if ($censusId == '')
    $selected	= "selected='selected'";
else
    $selected	= '';
$template->set('CENSUSID',          $censusId);
$template->set('SELECTED',          $selected);
foreach ($censuses as $crec)
{
    if ($censusId == $crec['censusid'])
		$crec['selected']	= "selected='selected'";
    else
        $crec['selected']	= '';
}
$template['option$censusid']->update($censuses);
$template['Provinces$censusid']->update($censuses);

// province/state selection list
$optionElt          = $template['provopt$pc'];
$optionText         = $optionElt->outerHTML;
$data               = '';
$rtemplate          = new \Templating\Template($optionText);
$rtemplate->set('pc',                   '');
if ($censusYear > 1867 && $province == 'CW')
	$select		    = 'selected="selected"';
else
    $select         = '';
$rtemplate->set('select',               $select);
$rtemplate->set('name',                 $t['All Provinces']);
$data               .= $rtemplate->compile();
$domains            = $censusRec->getDomains($lang);
foreach($domains as $code => $domain)
{
    $rtemplate          = new \Templating\Template($optionText);
    $rtemplate->set('pc',                   $domain['prov']);
    $rtemplate->set('name',                 $domain['name']);
    if ($domain['prov']  == $province)
        $rtemplate->set('selected',         'selected="selected"');
    else
        $rtemplate->set('selected',         '');
    $data               .= $rtemplate->compile();
}
$optionElt->update($data);

$template->display();
