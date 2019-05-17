<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DistForm.php														*
 *																		*
 *  Display form for editting sub-district information for a district	*
 *  of a Census of Canada												*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Census			census identifier XX9999					    *
 *		Province		two letter code, optional						*
 *																		*
 *  History:															*
 *		2010/11/22		created											*
 *		2011/06/03		use CSS for layout in place of tables			*
 *		2012/09/17		Census parameter is full census identifier		*
 *		2013/04/13		support being invoked without edit authorization*
 *						better											*
 *		2013/08/28		display progress of SubDistricts table for each	*
 *						district										*
 *		2013/08/30		popup help for subdistrict buttons				*
 *		2013/09/04		add forward and backward links					*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/12/28		use CSS for layout								*
 *						pass debug parameter							*
 *		2014/04/26		remove formUtil.inc obsolete					*
 *		2014/09/07		move province names table to common.inc			*
 *		2014/09/22		use District class to access database			*
 *						Note that this changes the order in which		*
 *						provinces are displayed within a census to		*
 *						numeric order by district, rather than alpha	*
 *						order by province ID							*
 *						use shared function pctClass					*
 *						case independent parameter interpretation		*
 *		2015/03/17		display diagnostic output						*
 *						did not include SubDistrict class				*
 *						fix failure if no districts in selection		*
 *		2015/06/05		add id attribute to all input fields			*
 *						format subdistrict count as input field			*
 *						pass debug flag to DistUpdate.php				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/10		escape province names							*
 *						display counts using even/odd highlighting		*
 *						prepare for support of non-Canadian censuses	*
 *		2016/01/20		add id to debug trace output					*
 *						include http.js before util.js					*
 *						generalize collective census support to display	*
 *						all districts for all subordinate censuses		*
 *						use class Census instead of $censusInfo			*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/12/26		do not generate PHP warning on short censusid	*
 *		2017/02/07		use class Country								*
 *		2017/09/05		use class Domain								*
 *		2017/09/12		use get( and set(								*
 *		2017/09/15		use class Template								*
 *		2017/10/29		use class RecordSet for Districts				*
 *		2017/11/04		$data, $npPrev, $npNext not initialized errors	*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/11		htmlspecchars moved to Template class			*
 *		2018/01/17		support new class composition					*
 *		2019/02/19      use new FtTemplate constructor                  *
 *		                support scrolling through the table             *
 *		                if there are more than 20 districts             *
 *		                perform the database update in this module      *
 *		                instead of passing to DistUpdate.php            *
 *		                support deleting Districts                      *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate all parameters passed to the server 
$censusId				= '';
$censusYear				= 0;
$census   				= null;
$province				= '';		// all provinces
$provinceName			= '';		// name of province
$cc			            = 'CA';		// ISO country code
$countryName			= 'Canada';
$offset                 = 0;
$limit                  = 20;
$getParms				= array();	// parameter for new RecordSet
$lang		            = 'en';		// default english

// determine which districts to display
if (isset($_GET) && count($_GET) > 0)
{ 
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {			// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        if ($value == '?')
        {		// value explicitly not supplied
            $msg		.= "$key must be selected. ";
        }		// value explicitly not supplied
        
        switch(strtolower($key))
        {
            case 'census':
            {		// Census Year
                $censusId	= $value;
                break;
            }		// Census identifier
    
            case 'state':
            {		// state code (international)
                $province	= $value;
                break;
            }		// state code (international)
    
            case 'province':
            {		// province code (Canada only)
                $province	= $value;
                break;
            }		// province code (mandatory for pre-1867)

            case 'offset':
            {
                $offset     = $value;
                break;
            }

            case 'limit':
            case 'count':
            {
                $limit      = $value;
                break;
            }

            case 'lang':
            {		// debug handled by common code
                $lang		= strtolower(substr($value,0,2));
                if ($lang == 'fr')
                    $getParms['order']	= 'D_Nom';
                else
                    $getParms['order']	= 'D_Name';
                break;
            }		// debug handled by common code
    
            case 'debug':
            {		// debug handled by common code
                break;
            }		// debug handled by common code
    
    
            default:
            {		// unexpected
                $warn	.= "Unexpected parameter $key='$value'. ";
                break;
            }		// unexpected
        }		// switch on parameter name
    }			// foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	                // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{		            // invoked by submit to update account
    ob_start();
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                  "<th class='colhead'>value</th></tr>\n";
    $district       = null;
    foreach($_POST as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        if (preg_match('/^([^\d]+)(\d*)$/', $key, $matches));
        {
            $fldname        = $matches[1];
            $rownum         = $matches[2];
        }
        switch(strtolower($fldname))
        {		    // act on specific parameter
            case 'census':
            {		// Census Year
                $censusId	    = $value;
                $census   	    = new Census(array('censusid' => $censusId));
                break;
            }		// Census identifier
    
            case 'state':
            case 'province':
            {		// province code (Canada only)
                $province	    = $value;
                break;
            }		// province code (mandatory for pre-1867)

            case 'offset':
            {
                $offset         = $value;
                break;
            }

            case 'limit':
            case 'count':
            {
                $limit          = $value;
                break;
            }
    
            case 'lang':
            {		// debug handled by common code
                $lang		    = strtolower(substr($value,0,2));
                if ($lang == 'fr')
                    $getParms['order']	= 'D_Nom';
                else
                    $getParms['order']	= 'D_Name';
                break;
            }		// debug handled by common code
    
            case 'debug':
            {		// debug handled by common code
                break;
            }		// debug handled by common code
    
            case 'd_id':
            {		// district identifier
                if ($district)
                {
                    $district->save('p');
                    $district           = null;
                }
                $d_id		            = $value;
                if (strlen($d_id) > 0 && is_numeric($d_id))
                {
                    $divParms           = array('d_census'      => $census,
                                                'd_id'	        => $d_id);
                    $district	        = new District($divParms);
                }
                break;
            }		// district identifier

            case 'd_name':
            {
                if (strtolower($value) == 'delete')
                {
                    $district->delete('p');
                    $district           = null;
                }
                if ($district)
                        $district->set($fldname, $value);
                break;
            }		// update fields

            case 'd_nom':
            case 'd_province':
            {
                if ($district)
                        $district->set($fldname, $value);
                break;
            }		// update fields
        }		    // act on specific parameter
    }	            // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
    $warn       .= ob_get_clean();
}		            // invoked by submit to update account

// create Template
if (canUser('admin'))
    $action     = "Update";
else
    $action     = "Display";
$template		= new FtTemplate("DistForm$action$lang.html");

// notify the invoker if they are not authorized

// interpret censusid
if (strlen($censusId) == 4 && ctype_digit($censusId))
{		            // backwards compatibility year only
    $censusYear		= $censusId;
    if ($censusYear < 1867)
        $censusId	= 'CW' . $censusId;
    else
        $censusId	= 'CA' . $censusId;
}		            // backwards compatibility
else
if (strlen($censusId) >= 6)
    $censusYear	    = substr($censusId, -4);
else
{
    $censusYear     = 0;
    $msg            .= "Invalid Census Identifier '$censusId'. ";
}

$census   	        = new Census(array('censusid' => $censusId));
$cc		            = $census['countrycode'];
if (strlen($province) == 0)
    $province       = $census['province'];
$country   	        = new Country(array('code' => $cc));
$countryName	    = $country->getName();
if ($country->isExisting())
{
    if ($census->get('collective'))
    {
        $subParms	            = array('year'	=> $censusYear,
                                        'partof'=> $cc);
        $list		            = new CensusSet($subParms);
        $carray		            = array();
        foreach($list as $crec)
            $carray[]	        = $crec->get('censusid');
        $getParms['d_census']	= $carray;
    }
    else
    {
        $getParms['d_census']	= $censusId;
    }
}
else
{
    $getParms['d_census']	    = $censusId;
}

// interpret domain
if (strlen($province) > 0)
{		// specified
    $domainObj	= new Domain(array('domain'	    => $cc . $province,
                                   'language'	=> 'en'));
    $provinceName	            = $domainObj->get('name');
    if ($domainObj->isExisting())
    {		// matches a province code
        $getParms['d_province']	= $province;
    }		// matches a province name
    else
    {		// does not match pattern
        $warn 	                .= "State code '$value' unsupported. ";
    }		// does not match pattern
}		// specified
else
    $provinceName	            = 'All';

if (ctype_digit($offset))
    $offset                     = intval($offset);
else
    $offset                     = 0;
$getParms['offset']             = $offset;

if (ctype_digit($limit))
    $limit                      = intval($limit);
else
    $limit                      = 20;
$getParms['limit']              = $limit;

$search	= "?Census=$censusId&amp;Province=$province&amp;lang=$lang";

// if no error messages display the query
$npNext			                = '';
$npPrev			                = '';
$data			                = array();
if (strlen($msg) == 0)
{				// no errors detected
    $getParmsNum                = $getParms;
    $getParmsNum['order']       = 'D_Id';
    unset($getParmsNum['offset']);
    unset($getParmsNum['limit']);
    $nresult			        = new RecordSet('Districts', $getParmsNum);
    if ($nresult->count() > 0)
    {
	    $firstDistrict	        = $nresult->rewind();
	    $prevDistrict	        = $firstDistrict->getPrev();
	    $lastDistrict	        = $nresult->last();
        $nextDistrict	        = $lastDistrict->getNext();
    }

    // execute the query to get the contents of the page
    $result			            = new RecordSet('Districts', $getParms);
    $info			            = $result->getInformation();
    $first                      = $offset + 1;
    $template->set('FIRST',             $first);
    $last                       = $offset + $result->count();
    $template->set('LAST',              $last);
    $total                      = $info['count'];
    $template->set('TOTAL',             $total);
    $template->set('OFFSET',            $offset);
    $template->set('LIMIT',             $limit);
            
    if ($result->count() > 0)
    {			// table of districts present
        if ($offset == 0)
        {
	        if ($prevDistrict)
	        {			// not first entry in table
	            $prevCensus	    = $prevDistrict->get('d_census');
	            $prevProv	    = $prevDistrict->get('d_province');
                $npPrev	= "?Census=$prevCensus&Province=$prevProv&lang=$lang" .
                            "&offset=0&limit=$limit";
	        }			// not first entry in table
        }               // offset 0
        else
            $npPrev	= "?Census=$censusId&Province=$province&lang=$lang" .
                        "&offset=" . ($offset - $limit) . "&limit=$limit";

        if ($offset > $total - $limit)
        {
	        if ($nextDistrict)
	        {			// not last entry
	            $nextCensus	    = $nextDistrict->get('d_census');
                $nextProv	    = $nextDistrict->get('d_province');
	            $npNext	= "?Census=$nextCensus&Province=$nextProv&lang=$lang" .
                            "&offset=0&limit=$limit";
            }			// not last entry
        }
        else
            $npNext	= "?Census=$censusId&Province=$province&lang=$lang" .
                        "&offset=" . ($offset + $limit) . "&limit=$limit";

        $line		            = 1;
        $numclass		        = 'odd';
        foreach($result as $district)
        {
            $line	            = str_pad($line, 2, '0', STR_PAD_LEFT);
            // get sub-district statistics
            $sdcount	        = $district->get('d_sdcount');
            $fcount		        = $district->get('d_fcount');
            if ($sdcount > 0)
                $fpct	        = 100.0 * $fcount / $sdcount;
            else
                $fpct	        = 0;
            $pop	            = $district->get('d_population');
            $done	            = $district->get('d_transcribed');
            if ($pop > 0)
                $donepct	    = 100.0 * $done / $pop;
            else
                $donepct	    = 0;

            if ($census && $census->get('collective'))
                $tcensusId	    = $province . $censusYear;
            else
                $tcensusId	    = $censusId;
            $fpctClass	        = pctClass($fpct, true);
            $donepctClass	    = pctClass($donepct, true);
            $fpct		        = number_format($fpct, 2);
            $pop		        = number_format($pop, 0);
            $done		        = number_format($done, 0);
            $data[]	            = array('line'		=> $line,
			                        'distId'	=> $district->get('id'),
			                        'name'		=> $district->get('name'),
			                        'nom'		=> $district->get('nom'),
			                        'prov'		=> $district->get('province'),
			                        'sdcount'	=> $sdcount,
			                        'fcount'	=> $fcount,
			                        'numclass'	=> $numclass,
			                        'fpct'		=> $fpct,
			                        'fpctclass'	=> $fpctClass,
			                        'pop'		=> $pop,
			                        'done'		=> $done,
			                        'donepctclass'	=> $donepctClass,
			                        'tcensusId'	=> $tcensusId);
            $line++;
            if ($numclass == 'odd')
                $numclass	    = 'even';
            else
                $numclass	    = 'odd';
        }
    }			// table of districts present
    else
    {			// no districts in table
        $npPrev	                = '';
        $npNext 	            = '';
    }			// no districts in table
}				// no errors

$title		= "Census Administration: $countryName: $censusYear Census: District Table";

$template->set('TITLE',	 		    $title);
$template->set('CENSUSYEAR',	 	$censusYear);
$template->set('CC',		        $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('CENSUSID',			$censusId);
$template->set('PROVINCE',			$province);
$template->set('PROVINCENAME',		$provinceName);
$template->set('CONTACTTABLE',		'Districts');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

if (strlen($npPrev) > 0)
{
    $template->updateTag('topPrev', array('npPrev' => $npPrev));
    $template->updateTag('botPrev', array('npPrev' => $npPrev));
}
if (strlen($npNext) > 0)
{
    $template->updateTag('topNext', array('npNext' => $npNext));
    $template->updateTag('botNext', array('npNext' => $npNext));
}

if (count($data) > 0)
{
    $template->updateTag('countzero', null);	    // remove message
    $template->updateTag('Row$line',		        // display result
                         $data);
}
else
{				// no results
    $template->updateTag('distForm', null);		    // remove output
    $template->updateTag('topBrowse', null);		// remove links
    $template->updateTag('botBrowse', null);	    // remove links
}				// no results

// check for missing mandatory parameters
if (strlen($censusId) == 0) 
    $msg		.= $template['noCensusId']->innerHTML();
if (strlen($province) == 0) 
    $warn		.= $template['noStateId']->innerHTML();

$template->display();
showTrace();
