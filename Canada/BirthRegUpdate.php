<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BirthRegUpdate.php													*
 *																		*
 *  This script updates a birth registration based upon parameters		*
 *  passed from an HTML form by POST.									*
 *																		*
 *  History:															*
 *		2010/08/19		separate PHP code from HTML						*
 *						handle optional parameters to avoid warning		*
 *						correct spelling of Accoucheur					*
 *		2010/08/29		Use new layout									*
 *		2010/12/30		correct handling of parents married indicator	*
 *						improve separation of PHP and HTML				*
 *						use MDB2										*
 *		2011/04/09		standardize										*
 *		2011/05/21		add fields for place of work					*
 *		2012/03/14		add IDIR link									*
 *						standardize handling of all parameters			*
 *						preserve values of fields that are not supplied	*
 *						by the input script.  This provides both		*
 *						flexibility in customizing the input script,	*
 *						permitting fields to be omitted, and supports	*
 *						the IDIR field, which is set by the family		*
 *						tree edit birth event, not through				*
 *						the birth registration edit script.				*
 *		2012/03/31		handle the case where the citation from the		*
 *						family tree was specified before the birth		*
 *						was transcribed.								*
 *						support adding a citation						*
 *		2012/05/13		record userid and date of last change to the	*
 *						record											*
 *		2012/07/01		update registrar name field						*
 *		2012/07/14		handle parentsMarried value of 'on'				*
 *		2013/04/13		use functions pageTop and pageBot to			*
 *						standardize appearance							*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/15		use class Birth to update database				*
 *		2014/01/30		use class Citation to create citation			*
 *						to birth event in family tree					*
 *		2014/02/07		save citation with parameter false				*
 *		2014/03/23		pad registration number in citation				*
 *						use new format Citation constructor				*
 *		2014/08/23		use Citation::getCitations instead of			*
 *						issuing SQL SELECT								*
 *		2014/08/31		if desirable set date of birth, location of		*
 *						birth in individual record						*
 *						also set citation for name						*
 *		2014/12/06		if there is no error or debugging information	*
 *						to report, go immediately to next registration	*
 *		2015/01/26		wrong URL for next query						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2016/02/18		override birth date if day portion is zero		*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/01/19		use set in place of setField					*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/10//20     use class Template                              *
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2019/12/13      remove B_ prefix from file names                *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values
$cc             		= 'CA';
$countryName    		= 'Canada';
$domain		    		= 'CAON';
$code		    		= 'ON';
$domainName     		= 'Ontario';
$county         		= null;
$countyName     		= null;
$township       		= null;
$regYear				= null;
$regNum		    		= null;
$lang           		= 'en';
$errors         		= 0;

// get key values from parameters
foreach($_POST as $key => $value)
{			// loop through all parameters
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'domain':
	    {
			$domain	    = $value;
            if (strlen($value) > 2)
            {
                $cc     = substr($value, 0, 2);
                $code   = substr($value, 2);
            }
			break;
	    }		// domain 

	    case 'regyear':
	    {
			$regYear	= $value;
			break;
	    }		// registration year

	    case 'regnum':
	    {
			$regNum	    = $value;
			break;
	    }		// registration number

        case 'regcounty':
        {
            $county             = $value;
            break;
        }

        case 'regtownship':
        {
            $township           = $value;
            break;
        }

	    case 'lang':
	    {
			if (strlen($value) >= 2)
                $lang               = strtolower(substr($value,0,2));
			break;
	    }		// language

	}		// act on specific parameters
}			// loop through all parameters

// create page template
$template		= new FtTemplate("BirthRegUpdate$lang.html");

if (!canUser('edit'))
{
    $elt        = $template->getElementById('notAuthorized');
    $msg        .= $elt->innerHTML();
}


if (is_null($regYear))
{
    $elt        = $template->getElementById('missYear');
    $msg        .= $elt->innerHTML();
}

if (is_null($regNum))
{
    $elt        = $template->getElementById('missNumber');
    $msg        .= $elt->innerHTML();
}

if (preg_match("/[A-Z]{4,5}/", $domain) == 0)
{
    $elt        = $template->getElementById('badDomain');
    $msg        .= str_replace('$value', $domain, $elt->innerHTML());
}

if (preg_match("/1[89][0-9]{2}/", $regYear) == 0)
{
    $elt        = $template->getElementById('badYear');
    $msg        .= str_replace('$value', $regYear, $elt->innerHTML());
}

if (preg_match("/[0-9]{3,7}/", $regNum) == 0)
{
    $elt        = $template->getElementById('badNumber');
    $msg        .= str_replace('$value', $regNum, $elt->innerHTML());
}

// interpret domain code
$domainObj	            = new Domain(array('domain'     => $domain,
						                   'language'	=> 'en'));
$domainName	            = $domainObj->get('name');
$country                = $domainObj->getCountry();
$countryName            = $country->getName($lang);

if (is_string($county))
{
    $countyObj          = new County(array('domain'     => $domainObj,
                                           'code'       => $county));
    $countyName         = $countyObj->getName();
}
$nextNum	            = intval($regNum) + 1;
$paddedRegNum	        = str_pad($regNum, 5, '0', STR_PAD_LEFT);

if (strlen($msg) == 0)
{
	$birth		    = new Birth($domain,
					            $regYear,
					            $regNum);
	$idir		    = $birth->get('idir');
	$idirChanged	= false;
	$parentsMarried = 'N';

	// process remaining input parameters
    // the values passed in the parameters override the existing values
    $parms          = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th><th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
    {	            // loop through all parameters
        $parms      .= "<tr><th class='detlabel'>$key</th><td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {	        // act on specific keywords
			case 'regdomain':
			case 'regyear':
			case 'regnum':
			case 'lang':
			{	    // already handled
			    break;
			}	    // already handled

			case 'sextxt':
			case 'regtownshipsel':
			case 'submit':
			{		// used by javascript 
			    break;
			}		// used by javascript

			case 'idir':
			{
			    $idirChanged	= $idir != $value;
			    $idir		    = $value;
			    $birth->set('idir', $value);
			    break;
			}

			case 'calcbirth':
			case 'calcdate':
			{		// deprecated
			    break;
			}		// deprecated

			case 'debug':
			{		// handled by common code
			    break;
			}		// handled by common code

			case 'parentsmarried':
			{		// ParentsMarried checkbox
			    $parentsMarried	= $value;
			    break;
			}		// ParentsMarried checkbox

			default:
			{
			    if ($debug)
				    $warn	.= "<p>\$birth->set('$key','$value')</p>\n";
			    $birth->set($key, $value);
			    break;
			}

	    }	        // act on specific keywords
    }	            // loop through all parameters
    if ($debug)
        $warn       .= $parms . "</table>\n";

	// checkboxes are a pain because if they are not clicked on
	// NOTHING is passed to the script
	$birth->set('parentsmarried', $parentsMarried);

	// if B_IDIR was not already set check for the case where the
	// family tree already cites the specific registration
	if ($idir == 0)
	{	            // B_IDIR not set
	    $parms	        = array('IDSR'		=> 97,
				        	    'Type'		=> 2,
				        	    'SrcDetail'	=> "$regYear-0*$regNum",
				        	    'limit'		=> 1);
	    $citations	    = new CitationSet($parms,
					    	              'IDSX');
	    $info	        = $citations->getInformation();
	    $count	        = $info['count'];
	    if ($count > 0)
	    {
			$citation	= $citations->rewind();
			$idir		= $citation->get('idime');
			$birth->set('idir', $idir);
	    }
	}	            // B_IDIR not set

	// update the database
	$birth->save(false);

	// support adding citation
	if ($idirChanged && $idir > 0)
	{	// the associated individual in the family tree has changed
	    // if the birth event has not been set or is incomplete
	    // add the birth information to the individual's birth event
		$indiv		    = new Person(array('idir' => $idir));
		$birthEvent	    = $indiv->getBirthEvent(true);
		$oldBirthDate	= $birthEvent->get('eventd');
		$oldBirthIdlr	= $birthEvent->get('idlrevent');
		$birthDate	    = $birth->get('birthdate');
		$birthDate	    = new LegacyDate($birthDate);
		$birthLocation	= $birth->get('birthplace');
		
		if (strlen($oldBirthDate) == 0 ||
		    substr($oldBirthDate,0,1) != '0' ||
		    substr($oldBirthDate,2,2) == 0)
		    $birthEvent->setDate($birthDate);
		if ($oldBirthIdlr <= 1)
		    $birthEvent->setLocation($birthLocation);
		$birthEvent->save(false);

	    // add a citation for the birth to the registration
	    $citParms	    = array('idime'		=> $idir, 
				            	'type'		=> Citation::STYPE_BIRTH, 
				            	'idsr'		=> 97,	// Birth Reg, Ontario 
	    		            	'srcdetail'	=> "$regYear-$paddedRegNum");
	    $citation	    = new Citation($citParms);
	    $citation->save(false);	// write into the database

	    // add a citation for the name to the registration
	    $givenNames	    = $birth->get('givennames');
	    $surName	    = $birth->get('surname');
	    $citParms	    = array('idime'		=> $idir, 
			            		'type'		=> Citation::STYPE_NAME, 
			            		'idsr'		=> 97,	// Birth Reg, Ontario 
	    	            		'srcdetail'	=> "$regYear-$paddedRegNum",
			            		'srcdettext'	=> "$givenNames $surName");
	    $citation	    = new Citation($citParms);
	    $citation->save(false);	// write into the database
    }	        // the associated individual in the family tree has changed

    if (strlen($warn) == 0 && !headers_sent())
	{		        // redirect immediately to next registration
	    header("Location: BirthRegDetail.php?RegDomain=$domain&RegYear=$regYear&RegNum=$nextNum&lang=$lang");
	    exit;
	}		        // redirect immediately to next registration
}		            // no errors detected

if (is_null($countyName))
{
    $template->updateTag('countyStats', null);
}
if (is_null($township))
{
    $template->updateTag('townshipStats', null);
}

// pass parameters to template
$template->set('REGYEAR',			$regYear);
$template->set('REGNUM',			$regNum);
$template->set('PADDEDREGNUM',		$paddedRegNum);
$template->set('NEXTNUM',			$nextNum);
$template->set('DOMAIN',			$domain);
$template->set('CC',			    $cc);
$template->set('LANG',		        $lang);
$template->set('CODE',		        $code);
$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('COUNTYNAME',		$countyName);
$template->set('REGTOWNSHIP',		$township);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
