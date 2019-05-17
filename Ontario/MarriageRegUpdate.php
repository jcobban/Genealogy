<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MarriageRegUpdate.php												*
 *																		*
 *  Update a marriage record with information supplied from the form	*
 *  MarriageRegDetail.php												*
 *																		*
 *  Parameters:															*
 *																		*
 *  History:															*
 *		2011/03/14		set focus on link to next record				*
 *		2011/03/19		record userid and date of last change			*
 *						improve separation of HTML and PHP				*
 *		2011/04/09		only insert minister record if not empty		*
 *		2011/09/04		add image field									*
 *		2011/11/10		use birth year fields passed with request		*
 *		2011/11/21		improve handling of unexpected township value	*
 *		2012/03/29		permit the update form to include only selected	*
 *						fields by preserving the values of any fields	*
 *						that are not present in the form.				*
 *		2013/01/03		fields from old marriage record not quotes		*
 *		2013/05/12		properly calculate next and previous record		*
 *						numbers for 1870/1								*
 *		2013/08/04		use pageTop and pageBot to standardize 			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/21		use Marriage and MarriageParticipant classes	*
 *		2014/04/18		improve validation of parameters				*
 *		2014/10/14		incorrect includes								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/14		display debugging information if requested		*
 *		2016/03/30		add header and footer links to status reports	*
 *		2017/02/18		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *		2017/09/12		use get( and set(								*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/23      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// initial field value defaults
$regYear				= null;
$regNum		    		= null;
$cc                     = 'CA';
$countryName            = 'Canada';
$regDomain				= 'CAON';
$domainName				= 'Ontario';
$action		    		= 'View';
$regCounty				= '';
$regTownship			= '';
$lang                   = 'en';

// apply updates
if (count($_POST) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{			// loop through all parameters
		switch(strtolower($key))
		{		// act on specific keys
		    case 'regyear':
		    {
	    		$regYear	= $value;
	    		break;
		    }		// registration year
	
		    case 'regnum':
		    {
	    		$regNum		= $value;
	    		break;
		    }		// registration number
	
		    case 'regdomain':
		    {
	    		$regDomain	= $value;
	    		break;
		    }		// registration domain
	
		    case 'lang':
            {
                if (strlen($value) >= 2)
	    		    $lang	= substr($value,0,2);
	    		break;
		    }		// registration domain
	
		}		// act on specific keys
	}			// loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account

// create instance of Template
$template		    = new FtTemplate("MarriageRegUpdate$lang.html");

// this script can only be invoked by an authorized user
if (!canUser('edit'))
    $msg	.= 'You are not authorized to update marriage registrations. ';

// validation done here so message texts can be obtained from template
$domain             = new Domain(array('domain'     => $regDomain,
                                       'language'   => 'en'));
$domainName         = $domain->getName();
if (!$domain->isExisting())
    $msg	.= "Domain code '$regDomain' is not defined. ";

if (is_null($regYear))
    $msg	.= "Mandatory parameter 'RegYear' missing. ";
else
if ((is_int($regYear) || ctype_digit($regYear)) && 
    $regYear >= 1000 && $regYear < 2100)
{
}
else
    $msg	.= "RegYear '$regYear' not a year. ";
if (is_null($regNum))
	$msg	.= "Mandatory parameter 'RegNum' missing. ";
else
if (!(is_int($regNum) || ctype_digit($regNum))) 
    $msg	.= "RegNum '$regNum' not a number. ";

// define substitutions
$template->set('LANG',           $lang);
$template->set('CC',             $cc);
$template->set('DOMAIN',         $regDomain);
$template->set('DOMAINNAME',     $domainName);
$template->set('COUNTRYNAME',    $countryName);
$template->set('REGYEAR',        $regYear);
$template->set('REGNUM',         $regNum);

if (strlen($msg) == 0)
{
	// save the values from the existing record, if any so that the
	// update menu does not have to include every field in the records 

    $marriage	    = new Marriage(array('domain'       => $domain, 
                                         'regyear'      => $regYear,
                                         'regnum'       => $regNum));

	// get references to the participants
	$groom		    = $marriage->getGroom(true);
	$bride		    = $marriage->getBride(true);
	$minister	    = $marriage->getMinister(true);

	// handle updated values from the input form
	foreach($_POST as $key => $value)
	{		// loop through all passed values
	    switch(strtolower($key))
	    {	// act on individual keys
			case 'regdomain':
			case 'regyear':
			case 'regnum':
			{		// already handled
			    break;
			}		// already handled
	
			case 'regtownshiptxt':
			case 'licensetypetxt':
			case 'regtownshipsel':
			case 'update':
			{		// ignore
			    break;
			}		// ignore
	
			case 'msvol':
			case 'regcounty':
			case 'regtownship':
			case 'date':
			case 'place':
			case 'licensetype':
			case 'remarks':
			case 'image':
			case 'regdate':
			case 'registrar':
			case 'originalvolume':
			case 'originalpage':
			case 'originalitem':
			{
			    $marriage->set($key, $value);
			    break;
			}
	
			case 'gsurname':
			case 'ggivennames':
			case 'gage':
			case 'gresidence':
			case 'gbirthplace':
			case 'gmarstat':
			case 'goccupation':
			case 'gfathername':
			case 'gmothername':
			case 'greligion':
			case 'gremarks':
			case 'gidir':
			{
			    $groom->set(substr($key, 1), $value);
			    break;
			}
	
			case 'gbirthyear':
			{
			    break;
			}
	
			case 'witness1':
			{
			    $groom->set('m_witnessname', $value);
			    break;
			}
	
			case 'witness1res':
			{
			    $groom->set('m_witnessres', $value);
			    break;
			}
	
			case 'bsurname':
			case 'bgivennames':
			case 'bage':
			case 'bresidence':
			case 'bbirthplace':
			case 'bmarstat':
			case 'boccupation':
			case 'bfathername':
			case 'bmothername':
			case 'breligion':
			case 'bremarks':
			case 'bidir':
			{
			    $oldval = $bride->set(substr($key, 1), $value);
			    break;
			}
	
			case 'bbirthyear':
			{
			    break;
			}
	
			case 'witness2':
			{
			    $bride->set('m_witnessname', $value);
			    break;
			}
	
			case 'witness2res':
			{
			    $bride->set('m_witnessres', $value);
			    break;
			}
	
			case 'msurname':
			case 'mgivennames':
			case 'mresidence':
			case 'mage':
			case 'mbirthplace':
			case 'mmarstat':
			case 'moccupation':
			case 'mreligion':
			case 'mremarks':
			case 'midir':
			{
			    $minister->set(substr($key, 1), $value);
			    break;
			}
	
			case 'mbirthyear':
			{
			    break;
			}
	
			case 'debug':
			{
			    break;
			}		// handled by common code
	
			default:
			{
			    $msg	.= "Unexpected parameter $key. ";
			    break;
			}
	    }	// act on individual keys
	}		// loop through all passed values

	// calculate the registration numbers of the immediately
	// preceding and following registrations
	// some 1870 to 1872 registrations use a vol/page/col format
	if ($regYear <= 1872 && $regNum > 10000)
	{		// use a volume/page/column format
	    $colNum	        = $regNum % 10;
	    if ($colNum == 1)
	    {	// column 1 is preceded by column 3 of previous page
    		$prevNum	= $regNum - 8;
	    	$nextNum	= $regNum + 1;
	    }	// column 1 is preceded by column 3 of previous page
	    else
	    if ($colNum == 3)
	    {	// column 3 is followed by column 1 of next page
	    	$prevNum	= $regNum - 1;
	    	$nextNum	= $regNum + 8;
	    }	// column 3 is followed by column 1 of next page
	    else
	    {	// column 2
	    	$prevNum	= $regNum - 1;
	    	$nextNum	= $regNum + 1;
	    }	// column 2
	}		// use a volume/page/column format
	else
	{		// normally sequentially numbered
	    $prevNum    	= intval($regNum) - 1;
	    $nextNum    	= intval($regNum) + 1;
	}		// normally sequentially numbered

	// pad the registration number out to a minimum of 5 digits
	if (strlen($prevNum) < 5)
	    $prevNumPadded	= substr('00000', 0, 5 - strlen($prevNum)) .
				  $prevNum;
	else
	    $prevNumPadded	= $prevNum;
	if (strlen($regNum) < 5)
	    $regNumPadded	= substr('00000', 0, 5 - strlen($regNum)) . 
				  $regNum;
	else
	    $regNumPadded	= $regNum;
	if (strlen($nextNum) < 5)
	    $nextNumPadded	= substr('00000', 0, 5 - strlen($nextNum)) . 
				  $nextNum;
	else
	    $nextNumPadded	= $nextNum;

    // update the database records
    $marriage->save(false);
    $bride->save(false);
    $groom->save(false);
    // only save the minister record if information exists
    if (strlen($minister->get('m_surname')) > 0)
        $minister->save(false);

    // set substitutions from updated marriage record
	$regCounty	    = $marriage->get('m_regcounty');
	$countyObj	    = new County($regDomain, $regCounty);
	$countyName	    = $countyObj->get('name');
	$regTownship	= $marriage->get('m_regtownship');
    $date		    = $marriage->get('m_date');

    $template->set('REGCOUNTY',      $regCounty);
    $template->set('COUNTYNAME',     $countyName);
    $template->set('REGTOWNSHIP',    $regTownship);
    $template->set('REGNUMPADDED',   $regNumPadded);
    $template->set('PREVNUM',        $prevNum);
    $template->set('PREVNUMPADDED',  $prevNumPadded);
    $template->set('NEXTNUM',        $nextNum);
    $template->set('NEXTNUMPADDED',  $nextNumPadded);

}		// no errors
else
{
    $template['countyStats']->update(null);
    $template['townshipStats']->update(null);
    $template['actions']->update(null);
}

$template->display();

