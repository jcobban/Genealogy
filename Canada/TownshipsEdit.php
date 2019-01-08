  <?php
/************************************************************************
 *  TownshipsEdit.php													*
 *																		*
 *  Display form for editting information about townships for			*
 *  vital statistics records											*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain		two letter country code	                            *
 *		            + 2/3 letter province/state code		            *
 *		Prov		two letter code										*
 *		County		three letter code									*
 *																		*
 *  History:															*
 *		2012/05/07		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/29		use classes County and Township					*
 *						pass debug flag to update script				*
 *						interpret country, province/state, and county	*
 *						for title										*
 *		2014/10/19		prov keyword didn't work after last change		*
 *						code field was not readonly for casual visitor	*
 *						delete button was not disabled for visitor		*
 *		2014/11/03		minor change to title							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		debug trace was not shown						*
 *						include http.js before util.js					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/11/13		escape code value								*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/02/07		use class Country								*
 *						correct row numbers								*
 *						remove duplicate <tbody>						*
 *		2017/09/12		use get( and set(								*
 *		2017/12/20		use class TownshipSet							*
 *		2018/10/21      use class Template                              *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once "Domain.inc";
require_once 'County.inc';
require_once 'Country.inc';
require_once 'Township.inc';
require_once 'TownshipSet.inc';
require_once 'Language.inc';
require_once 'Template.inc';
require_once 'common.inc';

$prov		    = 'ON';		    // postal abbreviation for province
$domain		    = 'CAON';	    // administrative domain
$countryName	= 'Unknown';
$domainName		= 'Unknown';
$county		    = null;	    	// county abbreviation
$countyName		= "Unknown";	// full name
$lang           = 'en';
$offset         = 0;
$limit          = 1000;
$getParms		= array();

foreach($_GET as $key => $value)
{				// loop through all parameters
	switch(strtolower($key))
	{			// act on specific keys
	    case 'domain':
	    {
			$domain		    = strtoupper($value);
			$domainObj  	= new Domain(array('domain'	    => $domain,
				        			    	   'language'	=> 'en'));
			$getParms['domain']	= $domainObj;
			if ($domainObj->isExisting())
			{
			    $cc			    = substr($domain, 0, 2);
			    $prov		    = substr($domain, 2);
			    $countryObj		= $domainObj->getCountry();
			    $countryName	= $countryObj->getName();
			    $domainName		= $domainObj->get('name');
			}
			else
			    $msg	.= "Invalid domain value '$value'. ";
			break;
	    }
	
	    case 'prov':
	    case 'province':
	    {
			$prov			    = $value;
			$domain			    = 'CA' . $prov;
			$domainObj	        = new Domain(array('domain'	    => $domain,
								                   'language'	=> 'en'));
			$getParms['domain']	= $domainObj;
			if ($domainObj->isExisting())
			{
			    $cc			    = 'CA';
			    $countryObj		= $domainObj->getCountry();
			    $countryName	= $countryObj->getName();
			    $domainName		= $domainObj->get('name');
			}
			else
			    $msg	.= "Invalid province value '$value'. ";
			break;
	    }
	
	    case 'state':
	    {
			$prov			    = $value;
			$domain			    = 'US' . $prov;
			$domainObj	        = new Domain(array('domain'	    => $domain,
								                   'language'	=> 'en'));
			$getParms['domain']	= $domainObj;
			if ($domainObj->isExisting())
			{
			    $cc			    = 'US';
			    $countryObj		= $domainObj->getCountry();
			    $countryName	= $countryObj->getName();
			    $domainName		= $domainObj->get('name');
			}
			else
			    $msg	.= "Invalid province value '$value'. ";
			break;
	    }
	
	    case 'county':
	    {
			$county			        = $value;
			try {
			    $countyObj		    = new County($domain, $county);
			    $getParms['county']	= $countyObj;
			    $countyName		    = $countyObj->getName();
			} catch (Exception $e) {
			    $msg		        .= $e->getMessage();
			    $countyName		    = $county;
			    $getParms['county']	= $county;
			}
			break;
        }

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang               = strtolower(substr($value,0,2));
        }

	}			// act on specific keys
}				// loop through all parameters
if (is_null($county))
	$msg	.= 'Missing mandatory parameter County. ';

if (strlen($msg) == 0)
{			// no errors
	try {
	    $county	        = new County($domain, $county);
	    $countyName	    = $county->get('name');
	    $countyCode	    = $county->get('code');
	} catch (Exception $e) {
	    $msg	        .= $e->getMessage();
	    $countyName	    = "Unknown County Code='$county'";
	    $countyCode	    = $county;
	}

	// execute the query to get the contents of the page
	$townships	        = new TownshipSet($getParms);
    $info	        	= $townships->getInformation();
    $count		        = $info['count'];
}			// no errors
else
{
    $townships          = array();
    $count              = 0;
}

if (canUser('edit'))
	$action			= 'Update';
else
	$action			= 'Display';

$tempBase			= $document_root . '/templates/';
$template			= new FtTemplate("${tempBase}page$lang.html");
$includeSub			= "TownshipsEdit$action$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$includeSub		= 'TownshipsEdit' . $action . 'en' . '.html';
	$language		= new Language(array('code'	=> $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                           array($langName, $nativeName),
                           $sorry);
}
$template->includeSub($tempBase . $includeSub, 'MAIN');

$template->set('CONTACTTABLE',	'Counties');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('CC',	    	$cc);
$template->set('COUNTRYNAME',	$countryName);
$template->set('DOMAIN',		$domain);
$template->set('DOMAINNAME',	$domainName);
$template->set('COUNTYCODE',	$countyCode);
$template->set('COUNTYNAME',	$countyName);
$template->set('LANG',          $lang);
$template->set('OFFSET',        $offset);
//$template->set('LIMIT',         $limit);
$template->set('TOTALROWS',     $count);
$template->set('FIRST',         $offset + 1);
$template->set('LAST',          min($count, $offset + $limit));
$template->set('$line',         '$line');
//if ($offset > 0)
//	$template->set('npPrev', "&offset=" . ($offset-$limit) . "&limit=$limit");
//else
//	$template->updateTag('prenpprev', null);
//if ($offset < $count - $limit)
//	$template->set('npNext', "&offset=" . ($offset+$limit) . "&limit=$limit");
//else
//	$template->updateTag('prenpnext', null);

$rowElt             = $template->getElementById('Row$line');
$rowHtml            = $rowElt->outerHTML();
$data               = '';
$line               = 1;
foreach($townships as $township)
{
    $code           = $township->get('code');
    $name           = $township->get('name');
    $rtemplate      = new Template($rowHtml);
    $rtemplate->set('line',     $line);
    $rtemplate->set('code',     $code);
    $rtemplate->set('name',     $name);
    $data           .= $rtemplate->compile();
    $line++;
}
$rowElt->update($data);
$template->display();

