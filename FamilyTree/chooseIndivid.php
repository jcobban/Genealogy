<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  chooseIndivid.php													*
 *																		*
 *  Display a web page to select a specific existing individual			*
 *  from the Legacy table of individuals.								*
 *																		*
 *  URI Parameters (passed by method="GET"):							*
 *																		*
 *		name			if present supplies the default initial name	*
 *						for the selection in the form					*
 *						"surname, given names"							*
 *		idir			if present, the selected individual is made the	*
 *						initial default selection						*
 *		parentsIdmr		if present this is a request to select an		*
 *						existing individual to add as a child onto the	*
 *						indicated family record.						*
 *						Handled by both PHP and Javascript				*
 *		callidir		the name of a form in the invoking page that	*
 *						has a javascript method "callidir(idir)" that	*
 *						can be called by this script to pass the IDIR	*
 *						of the chosen individual to the invoking page.	*
 *						Handled by Javascript							*
 *		id				name of an element in the invoking page with a	*
 *						method setNew defined.							*
 *						Handled by Javascript							*
 *		birthyear		approximate birth year							*
 *		range			range of birth years							*
 *		birthmin		lowest birth year								*
 *		birthmax		highest birth year								*
 *		treename		subdivision of database to search				*
 *																		*
 *  History:															*
 *		2010/08/29		Created											*
 *		2010/09/05		Only insert comma in initial name if given name	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2010/12/20		handle exception from new LegacyIndiv			*
 *		2010/12/23		support name= parameter							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/02/23		support for setidir etc. removed in favor of	*
 *						setNew callback in invoker						*
 *		2011/12/30		support field specific help						*
 *						support page help 								*
 *						pop up loading indicator while waiting for		*
 *						a response from the server.						*
 *		2012/01/13		change class names								*
 *		2013/01/20		correctly handle names containing special		*
 *						characters										*
 *		2013/01/28		if invoked with a specific individual include	*
 *						only individuals matching the gender of that	*
 *						individual but excluding that individual		*
 *		2013/03/05		load initial selection directly,				*
 *						not through script								*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/08/01		remove pageTop and pageBot because this is a	*
 *						popup dialog									*
 *		2013/08/15		use name of individual selected by IDIR to		*
 *						initialize the form								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/08		replace table layout with CSS layout			*
 *						add support for birth year range limit			*
 *		2013/12/31		add for attribute to <label> tags				*
 *						correct class for input fields					*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/08/08		add support for popups							*
 *		2014/09/12		improve search for wife's name					*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/02/16		display appropriate text in button based		*
 *						upon selection									*
 *		2015/03/24		birthmax did not accept years in 1700s			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/23		add support for treename						*
 *						broaden valid birth date range					*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/09/09      do not report birthmin -9999 as an error        *
 *		                get message texts from template                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values
$idir								= null;
$idirtext                           = '';
$idmr								= null;
$idmrtext                           = '';
$family								= null;
$name								= '';		// initial position in list
$gender								= '';
$birthmin							= null;
$birthmintext						= '';
$birthmax							= null;
$birthmaxtext						= '';
$birthyear							= null;
$birthyeartext						= '';
$rangetext                          = '';
$treename							= '';
$range								= 1;
$lang                               = 'en';

// check input parameters
if (count($_GET) > 0)
{
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {			// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific input key
		    case 'name':
		    {		// initial name specified as surname, givenname
                $name		        = $value;
                if (strpos($name, ',') === false)
                    $name           = "$name, A";
				break;
		    }		// initial name specified as surname, givenname

		    case 'parentsidmr':
		    {		// family to add a child to
                if (ctype_digit($value))
                    $idmr		    = (int)$value;
                else
                    $idmrtext       = $value;
				break;
		    }		// family to add a child to

		    case 'idir':
		    {		// initial individual specified by IDIR
                if (ctype_digit($value) && $value > 0)
                    $idir		    = (int)$value;
                else
                    $idirtext       = $value;
				break;
		    }		// default individual specified

		    case 'birthyear':
		    {		// birth year specified
			    if (ctype_digit($value) && $value > 1700)
                    $birthyear		= (int)$value;
                else
                    $birthyeartext  = $value;
				break;
		    }		// birth year specified

		    case 'birthmin':
		    {		// minimum birth year specified
			    if (preg_match('/^-?\d+$/', $value))
                    $birthmin		= (int)$value;
                else
                    $birthmintext   = $value;
				break;
		    }		// minimum birth year specified

		    case 'birthmax':
		    {		// maximum birth year specified
			    if (preg_match('/^-?\d+$/', $value))
                    $birthmax		= (int)$value;
                else
                    $birthmaxtext   = $value;
				break;
		    }		// maximum birth year specified

		    case 'range':
		    {		// range of birth years specified
			    if (ctype_digit($value) && $value <= 99)
                    $range		    = (int)$value;
                else
                    $rangetext      = $value;
				break;
		    }		// range of birth years specified

		    case 'treename':
		    {		// subdivision of database
				$treename	        = $value;
				break;
            }		// subdivision of database

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

		}		    // act on specific input key
    }			    // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$template           = new FtTemplate("chooseIndivid$lang.html");

// complete processing of IDIR
if ($idir)
{                   // syntactically valid
	$person		                    = new Person(array('idir' => $idir));
	if ($person->isExisting())
	{               // existing Person
	    $surname	                = $person->getSurname();
	    $givenname	                = $person->getGivenName();

	    if (strlen($surname) <= 1)
	    {		        // no surname, only wife's given
			$lgiven	                = strlen($givenname) - 6;
			if ($lgiven < 8)
			    $lgiven	= 8;
		    $name	                = ", " .  substr($givenname, 0, $lgiven);
	    }		        // no surname, only wife's given
	    else
	    if (substr($givenname, 0, 4) == 'Mary')
	    {		        // names starting with 'Ma' too common
		    $name	                = $surname . ", Mary";
	    }		        // names starting with 'Ma' too common
	    else
	    if (strlen($givenname) > 2)
		    $name	             = $surname . ", " . substr($givenname, 0, 2);
	    else
		    $name	                = $surname . ", " . $givenname;
	    $gender	                    = $person->getGender();
	    if ($gender == Person::MALE)
		    $gender	                = 'M';
	    else
	    if ($gender == Person::FEMALE)
		    $gender	                = 'F';
	}	                // existing Person
	else
	{	                // error creating individual
        $text                       = $template['noPerson']->innerHTML;
        $msg	                    .= str_replace('$idir', $idir, $text);
	}	                // error creating individual
}	                    // syntactically valid
else
if (strlen($idirtext) > 0)
{                       // invalid IDIR value
    $text                           = $template['badIdir']->innerHTML;
    $msg	                        .= str_replace('$idir', $idirtext, $text);
}                       // invalid IDIR value

// complete processing of IDMR
if ($idmr)
{                       // syntactically valid
	$family		                    = new Family(array('idmr' => $idmr));
    if (!$family->isExisting())
    {
        $text                       = $template['noFamily']->innerHTML;
        $msg	                    .= str_replace('$idmr', $idmr, $text);
    }
}                       // syntactically valid
else
if (strlen($idmrtext) > 0)
{                       // invalid IDMR value
    $text                           = $template['badIdmr']->innerHTML;
    $msg	                        .= str_replace('$idmr', $idmrtext, $text);
}                       // invalid IDMR value

if (strlen($birthyeartext) > 0 )
{                       // invalid birth year value
    $text                           = $template['badBirthYear']->innerHTML;
    $msg	                        .= str_replace('$birthyear', $birthyeartext, $text);
}                       // invalid birth year value
if (strlen($birthmintext) > 0 )
{                       // invalid min birth year value
    $text                           = $template['badBirthMin']->innerHTML;
    $msg	                        .= str_replace('$birthmin', $birthmintext, $text);
}                       // invalid min birth year value
if (strlen($birthmaxtext) > 0 )
{                       // invalid max birth year value
    $text                           = $template['badBirthMax']->innerHTML;
    $msg	                        .= str_replace('$birthmax', $birthmaxtext, $text);
}                       // invalid max birth year value
if (strlen($rangetext) > 0)
{                       // invalid birth range value
    $text                           = $template['badRange']->innerHTML;
    $msg	                        .= str_replace('$range', $rangetext, $text);
}                       // invalid birth range value

// if birth year is specified and explicit birth range not
// supplied then calculate the explicit birth range from the
// estimated birth year and the range
if (is_null($birthmin) && !is_null($birthyear))
	$birthmin	            = $birthyear - $range;
if (is_null($birthmax) && !is_null($birthyear))
	$birthmax	            = $birthyear + $range;
if (!is_null($birthmin))
{
	if (is_null($birthmax))
	    $birthmax	        = $birthmin + 1;
	else
	if ($birthmax < $birthmin)
    {                   // birth range out of order
        $text               = $template['badOrder']->innerHTML;
        $msg	            .= str_replace(array('$birthmin','$birthmax'),
                                           array($birthmin, $birthmax),
                                           $text);
    }                   // birth range out of order
}
else
if (!is_null($family))
{			// get range from parents birth years
	$husbbirthsd            = $family->get('husbbirthsd');
	$wifebirthsd            = $family->get('wifebirthsd');

	if ($husbbirthsd != 0 && $husbbirthsd != -99999999)
	{	// have father's birth date
	    $birthmin	        = floor($husbbirthsd/10000) + 15;
	    $birthmax	        = floor($husbbirthsd/10000) + 65;
	}	// have father's birth date
	else
	if ($wifebirthsd != 0 && $wifebirthsd != -99999999)
	{	// have mother's birth date
	    $birthmin	        = floor($wifebirthsd/10000) + 15;
	    $birthmax	        = floor($wifebirthsd/10000) + 55;
	}	// have mother's birth date
	else
	{
	    $birthmin	        = 1750;
	    $birthmax	        = 1900;
	}
}			// get range from parents birth years

$template['otherStylesheets']->update(array('filename', 'chooseIndivid'));

$template->set('NAME',              $name);
$template->set('GENDER',            $gender);
$template->set('IDIR',              $idir);
$template->set('IDMR',              $idmr);
$template->set('TREENAME',          $treename);
$template->set('BIRTHMIN',          $birthmin);
$template->set('BIRTHMAX',          $birthmax);
$template->set('LANG',              $lang);

$template->display();
