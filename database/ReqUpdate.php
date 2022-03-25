<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdate.php														*
 *																		*
 *  Request a page of the census to be editted.							*
 *																		*
 *  Parameters:															*
 *		Census			identifier of census, country code plus year	*
 *						for example: CA1881								*
 *		Province		identifier of province to select				*
 *																		*
 *  History:															*
 *		2010/10/01		Reformat to new page layout.					*
 *		2011/01/22		Add help URL									*
 *						move onload specification to ReqUpdate1901.js	*
 *		2011/05/18		use CSS instead of tables for layout			*
 *		2013/05/07		use common PHP instead of specific html files	*
 *		2013/06/01		only include prairie provinces in 1906 and 1916	*
 *						censuses										*
 *		2013/08/17		add support for 1921 census						*
 *		2013/11/16		gracefully handle lack of database server		*
 *						connection										*
 *		2013/11/29		let common.inc set initial value of $debug		*
 *						use valid list of censuses from common.inc		*
 *		2014/09/07		use shared table of province names				*
 *		2015/05/09		simplify and standardize <h1>					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/10		escape values from global table $provinceNames	*
 *		2016/01/22		use class Census to get census information		*
 *						show debug trace								*
 *						include http.js before util.js					*
 *		2016/12/28		support requesting collective census id			*
 *		2017/09/05		use Country and Domain objects to get			*
 *						information about country and province			*
 *		2017/09/12		use get( and set(								*
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Census.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/common.inc";

// defaults
$censusId               = 'CA1881';
$censusYear				= null;
$cc			            = 'CA';
$countryName			= 'Canada';
$lang                   = 'en';

// get parameter values into local variables
// validate all parameters passed to the server
if (count($_GET) > 0)
{	                // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {			    // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {		    // switch on parameter name
            case 'census':
            {		// census identifier
                $censusId		= $value;
                if (strlen($censusId) == 4)
                    $censusId	= 'CA' . $censusId;
                break;
            }		// census identifier

            case 'lang':
            {
	            $lang               = FtTemplate::validateLang($value);
                break;
            }

        }		    // switch on parameter name
    }			    // loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}	                // invoked by URL

$census	                = new Census(array('censusid'	=> $censusId));
$censusYear		        = $census['year'];
$cc			            = $census['cc'];
$provinces		        = $census['provinces'];
$country		        = new Country(array('code' => $cc));
$countryName	        = $country->get('name');

$tempBase		        = $document_root . '/templates/';
if (file_exists($tempBase . "CensusReqUpdate$cc" . "en.html"))
    $template           = new FtTemplate("CensusReqUpdate$cc$lang.html");
else
    $template           = new FtTemplate("CensusReqUpdate__$lang.html");

if (!$census->isExisting())
    $warn	.= "<p>Census '$censusId' not pre-defined.</p>\n";

$domainset	            = new DomainSet(array('cc'	        => $cc,
                                              'language'	=> $lang));
if (count($domainset) > 0)
{
    $domain             = $domainset->rewind();
    $code               = $domain['domain'];
    $cl                 = strlen($code) - 2;
}
else
    $cl                 = 2;

$provList               = array();
for($io = 0; $io < strlen($provinces); $io += $cl)
{		        // loop through provinces
    $province		    = substr($provinces, $io, $cl);
    $domainObj	        = $domainset["$cc$province"];
    if (is_null($domainObj))
    {
        //error_log("ReqUpdate.php: " . __LINE__ .
        //            " no entry in \$domainset for '$cc$province', " .
        //        "DomainSet(array('cc' => '$cc', 'language' => '$lang'))");
        $provinceName   = "$cc$province";
    }
    else
        $provinceName	= $domainObj->get('name');
    if ($census->get('collective'))
        $provList[]     = array('censusid'      => $province . $censusYear,
                                'province'      => $province,
                                'provincename'  => $provinceName);
    else
        $provList[]     = array('censusid'      => $censusId,
                                'province'      => $province,
                                'provincename'  => $provinceName);
}               // loop through provinces

$template['Province$province']->update($provList);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('CENSUSID',      $censusId);
$template->set('CENSUSYEAR',    $censusYear);

$template->display();
