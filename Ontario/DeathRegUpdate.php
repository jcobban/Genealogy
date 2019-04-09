<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegUpdate.php													*
 *																		*
 *  Update an entry in the death registrations table.					*
 *																		*
 *  History:															*
 *		2010/08/24		Change to new layout							*
 *						Fix warning on MsVol, etc.						*
 *		2011/04/22		put ids on <a> tags								*
 *		2012/03/14		add IDIR link									*
 *						standardize handling of all parameters			*
 *						preserve values of fields that are not supplied	*
 *						by the input script.  This provides both		*
 *						flexibility in customizing the input script,	*
 *						permitting fields to be omitted, and supports	*
 *						the IDIR field, which is set by the family tree	*
 *						edit death event, not through the death			*
 *						registration edit script.						*
 *		2012/03/31		handle the case where the citation from the		*
 *						family tree was specified before the death		*
 *						was transcribed.								*
 *						support adding a citation						*
 *		2012/05/13		record userid and date of last change to the	*
 *						record											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/15		use class Death to update database				*
 *		2014/01/30		use class Citation to create citation			*
 *						to birth event in family tree					*
 *		2014/02/07		save citation with parameter false				*
 *		2014/03/23		pad registration number in saved citation		*
 *						use new parameter format for Citation			*
 *		2014/08/04		exception if IDIR associated with the record	*
 *						changed to zero									*
 *		2014/08/23		use Citation::getCitations instead of			*
 *						issuing SQL SELECT								*
 *		2014/08/31		if desirable set date of death, location of		*
 *						death, and cause of death in individual record	*
 *						also set citation for cause of death			*
 *		2014/09/09		update date of death if only year specified		*
 *						in associated individual						*
 *						update, if necessary, information about death	*
 *						in an individual that already cited this		*
 *						registration									*
 *						Do not create citations if the supplied IDIR	*
 *						does not match an individual in the tree:		*
 *						This should only happen if not invoked			*
 *						by DeathRegDetail, belt and suspenders			*
 *		2014/12/06		if there is no error or debugging information	*
 *						to report, go immediately to next registration	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/03/13		$domainName was undefined						*
 *		2017/03/19		use preferred parameters for new Person			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/06/01		add support for lang parameter					*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . '/Death.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

define ('NOT_AUTH', 	1);
define ('BAD_DOMAIN',	2);
define ('BAD_YEAR',	    4);
define ('BAD_NUMBER',	8);
define ('MISS_YEAR',	16);
define ('MISS_NUMBER',	32);

// default values
$cc				        = 'CA';
$countryName            = 'Canada';
$domain				    = 'CAON';
$code				    = 'ON';
$domainName				= 'Ontario';
$county                 = null;
$countyName             = null;
$township               = null;
$regYear				= null;
$regNum				    = null;
$idirChanged		    = false;
$lang				    = 'en';
$errors                 = 0;

// get key values from parameters
if (count($_POST) > 0)
{	        	    // invoked by URL to update record
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
    	switch(strtolower($key))
    	{
    	    case 'domain':
    	    {
    			$domain		= $value;
    			break;
    	    }
    
    	    case 'regyear':
    	    {
    			if (ctype_digit($value))
    			{
    			    $regYear	= intval($value);
    			    if ($regYear < 1850 or $regYear > 2100)
    					$msg	.= "RegYear='$value' is out of range. ";
    			}
    			else
    			    $msg	.= "RegYear='$value' is not numeric. ";
    			break;
    	    }
    
    	    case 'regnum':
    	    {
    			if (ctype_digit($value))
    			{
    			    $regNum	= intval($value);
    			}
    			else
    			    $msg	.= "RegNum='$value' is not numeric. ";
    			break;
    	    }

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
    			    $lang		= strtolower(substr($value,0,2));
    			break;
    	    }		    // lang
    	}		        // act on specific parameters
    }					// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	        // invoked by URL to update the record

// get name of domain in requested language
$domainObj	        = new Domain(array('domain'	    => $domain,
					                   'language'	=> $lang));
$domainName	        = $domainObj->get('name');
$country                = $domainObj->getCountry();
$countryName            = $country->getName($lang);
if (!$domainObj->isExisting())
{		                // undefined
    $msg	        .= "Domain='$domain' is invalid. ";
}		                // undefined

if (is_string($county))
{
    $countyObj          = new County(array('domain'     => $domainObj,
                                           'code'       => $county));
    $countyName         = $countyObj->getName();
}
$nextNum	            = intval($regNum) + 1;
$paddedRegNum	        = str_pad($regNum, 5, '0', STR_PAD_LEFT);

if (is_null($regYear))
	$msg	.= 'Year of registration not specified. ';
if (is_null($regNum))
	$msg	.= 'Registration number not specified. ';

// expand only if authorized
if (!canUser('edit'))
{				// not authorized
	$msg	.= 'You are not authorized to update birth registrations. ';
}				// not authorized

if (strlen($msg) == 0)
{						// no errors
	$death		= new Death(array('domain'  => $domain,
						          'regyear' => $regYear,
						          'regnum'  => $regNum));
	$idir		= $death->get('d_idir');

	// process remaining input parameters
	// the values passed in the parameters override the existing values
	foreach($_POST as $key => $value)
	{	        // loop through all parameters
	    switch(strtolower($key))
	    {	    // act on specific keywords
			case 'regdomain':
			case 'regyear':
			case 'regnum':
			{	// already handled
			    break;
			}	// already handled

			case 'sextxt':
			case 'regtownshipsel':
			case 'submit':
			case 'lang':
			{	// used by javascript 
			    break;
			}	// used by javascript

			case 'idir':
			{
			    $idirChanged	= $idir != $value;
			    $idir		    = $value;
			    $death->set($key, $value);
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

			default:
			{
			    $death->set($key, $value);
			    break;
			}

	    }	    // act on specific keywords
	}	        // loop through all parameters

	// if D_IDIR was not already set check for the case where the
	// family tree already cites the specific registration
	if ($idir == 0)
	{				    // D_IDIR not set
	    $parms	    = array('IDSR'		=> 98,
				    		'Type'		=> Citation::STYPE_DEATH,
				    		'SrcDetail'	=> "$regYear-0*$regNum",
				    		'limit'		=> 1);
	    $citations	            = new CitationSet($parms,
							                      'IDSX');
	    $info	                = $citations->getInformation();
	    $count	                = $info['count'];
	    if ($count > 0)
	    {				// there is already a citation to this
			$citation	        = current($citations);
			if ($citation instanceof Record)
			{
	    		$idir		    = $citation->get('idime');
	    		$death->set('d_idir', $idir);
	    		$idirChanged	= true;
			} 
			else
			    print_r($citation);
	    }				// there is already a citation to this
	}				    // D_IDIR not set

	// update the database record
	$death->save(false);

	// support adding citation to family tree
	if ($idirChanged && $idir > 0)
	{				// the associated individual has changed
	    // if the death event has not been set or is incomplete
	    // add the death information to the individual's death event
	    try {
			$person		    = new Person(array('idir' => $idir));
			$deathEvent	    = $person->getDeathEvent(true);
			$oldDeathDate	= $deathEvent->get('eventd');
			$oldDeathIdlr	= $deathEvent->get('idlrevent');
			$oldDeathCause	= $person->get('deathcause');
			$deathDate	    = $death->get('date');
			$deathDate	    = new LegacyDate(' ' . $deathDate);
			$deathLocation	= $death->get('place');
			$deathCause	    = $death->get('cause');
			$deathDur	    = $death->get('duration');
			if ($debug)
                $warn	.= "<p>DeathRegUpdate.php: ". __LINE__ . 
                            " Update death date of " . $person->getName() .
						   " to " .  $deathDate->toString() .
						   " at " . $deathLocation . 
						   " with cause " . $deathCause . "</p>";

			// if the death date currently recorded for the individual
			// is not set, or is only an approximation, set it to
			// the date from the death registration
			if (strlen($oldDeathDate) == 0 ||
			    substr($oldDeathDate, 0, 1) != '0' ||
			    substr($oldDeathDate, 0, 6) == '000000')
			{
			    $deathEvent->setDate($deathDate);
			}

			// if the death location is not currently set for the
			// individual, set it to the location from the death reg.
			if ($oldDeathIdlr <= 1)
			    $deathEvent->setLocation($deathLocation);

			// update the database
			$deathEvent->save(false);

			// update the cause of death
			if (is_null($oldDeathCause) || strlen($oldDeathCause) == 0)
			{		// death cause not set
			    $person->set('deathcause', 
						$deathCause . ', ' . $deathDur);
			    $person->save(false);
			}		// death cause not set
	    } catch (Exception $e) {
            $warn	.= "<p>DeathRegUpdate.php: ". __LINE__ . ' ' .
			            $e->getMessage() . "</p>\n";
			$idir		= 0;	// IDIR was invalid
	    }				// catch

	    // add the citation to the database
	    $paddedRegNum	= str_pad($regNum, 5, '0', STR_PAD_LEFT);
	    if ($idir > 0)
	    {				// new IDIR is valid
			$citParms	= array('idime'	=> $idir,
							'type'	=> Citation::STYPE_DEATH,
							'idsr'	=> 98,	// Ont Death Register
							'srcdetail'=> "$regYear-$paddedRegNum"); 
			$citation	= new Citation($citParms);
			$citation->save(false);	// write into the database

			$citParms	= array('idime'	=> $idir,
							'type'	=> Citation::STYPE_DEATHCAUSE,
							'idsr'	=> 98,	// Ont Death Register
							'srcdetail'=> "$regYear-$paddedRegNum"); 
			$citation	= new Citation($citParms);
			$citation->save(false);	// write into the database
	    }				// new IDIR is valid
	}				// the associated individual has changed
}							// no errors

// Identify next registration to update
$nextRegNum		= intval($regNum) + 1;

if (strlen($msg) == 0 && strlen($warn) == 0 && !headers_sent())
{				// redirect immediately to next registration
	header("Location: DeathRegDetail.php?RegDomain=$domain&RegYear=$regYear&RegNum=$nextRegNum&lang=$lang"); 
    exit;
}				// redirect immediately to next registration

// display page
$template		= new FtTemplate("DeathRegUpdate$lang.html");

if ($errors & NOT_AUTH)
{
    $elt        = $template->getElementById('notAuthorized');
    $msg        .= $elt->innerHTML();
}

if ($errors & BAD_DOMAIN)
{
    $elt        = $template->getElementById('badDomain');
    $msg        .= str_replace('$value', $domain, $elt->innerHTML());
}

if ($errors & BAD_YEAR)
{
    $elt        = $template->getElementById('badYear');
    $msg        .= str_replace('$value', $regYear, $elt->innerHTML());
}

if ($errors & BAD_NUMBER)
{
    $elt        = $template->getElementById('badNumber');
    $msg        .= str_replace('$value', $regNum, $elt->innerHTML());
}

if ($errors & MISS_YEAR)
{
    $elt        = $template->getElementById('missYear');
    $msg        .= $elt->innerHTML();
}

if ($errors & MISS_NUMBER)
{
    $elt        = $template->getElementById('missNumber');
    $msg        .= $elt->innerHTML();
}

if (is_null($countyName))
{
    $template->updateTag('countyStats', null);
}
if (is_null($township))
{
    $template->updateTag('townshipStats', null);
}

// pass parameters to template
$template->set('REGYEAR',		$regYear);
$template->set('REGNUM',		$regNum);
$template->set('PADDEDREGNUM',	$paddedRegNum);
$template->set('NEXTNUM',		$nextNum);
$template->set('NEXTREGNUM',	$nextNum);
$template->set('DOMAIN',		$domain);
$template->set('CC',		    $cc);
$template->set('LANG',	        $lang);
$template->set('CODE',	        $code);
$template->set('COUNTRYNAME',	$countryName);
$template->set('DOMAINNAME',	$domainName);
$template->set('COUNTYNAME',	$countyName);
$template->set('REGTOWNSHIP',	$township);
$template->set('CONTACTTABLE',		'Deaths');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
