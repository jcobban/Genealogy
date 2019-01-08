<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ancestorReport.php													*
 *																		*
 *  Display a web page containing a report of the ancestors of an		*
 *  individual.															*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		idir			unique numeric identifier of the individual		*
 *						whose ancestors are to be displayed.			*
 *		ancDepth		number of generations to display				*
 *						default is from session cookie					*
 *		incLocs			include locations for events					*
 *																		*
 * History:																*
 *		2010/12/29		created											*
 *		2011/01/06		correct privacy code							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/04/05		show marriage date								*
 *						optionally include locations of events			*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/17		use standard functions to layout header and		*
 *						footer											*
 *		2013/06/01		change nominalIndex.html to legacyIndex.php		*
 *						include all owners in the contact Author email	*
 *		2013/07/30		defer facebook initialization until after load	*
 *						clean up initialization							*
 *						correct invalid exposure of information for		*
 *						private											*
 *						add mouseover help for all fields				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/10		eliminate use of tables for layout				*
 *		2014/03/06		label class name changed to column1				*
 *		2014/03/21		remove deprecated LegacyIndiv::getNumParents,	*
 *						LegacyIndiv::getNextParents						*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/26		fix <?print to <?php print						*
 *		2015/01/01		use new getBirthDate and getDeathDate			*
 *						and extended getName from LegacyIndiv			*
 *		2015/01/23		remove top and bottom headers					*
 *						add close button								*
 *		2015/03/07		use LegacyIndiv::getName to obtain all			*
 *						information to display about an individual		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/07		use cookies rather than sessions				*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2016/02/24		change order of lines to look more like tree	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/08		improve parameter validation					*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/10/25      use class Template                              *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  show																*
 *																		*
 *  Show an individual and that individual's parents.					*
 *																		*
 *	Input:																*
 *		$person         Instance of class Person                        *
 *		$level          ancestor level                                  *
 *		$prefix         Text to emit preceding the person's name        *
 *		$template		Main template for the page						*
 *																		*
 *	Returns:															*
 *	    HTML in a string                                                *
 ************************************************************************/
function show($person, $level, $prefix 		= '', $template)
{
    global  $debug;
    global  $warn;
    global  $bprivlim;
    global  $dprivlim;
    global  $ancDepth;
    global  $incLocs;
    global  $fatherText;
    global  $motherText;
    global  $fathersmaleText;
    global  $fathersfemaleText;
    global  $mothersmaleText;
    global  $mothersfemaleText;
    global  $direction;

    if ($debug)
        $warn   .= "<p>ancestorReport: ". __LINE__ .
                " show($person, level=$level, '$prefix', \$template)</p>\n";
    $retval         		= '';

    $idir	        		= $person->getIdir();
    if ($incLocs)
		$nameOpts			= Person::NAME_INCLUDE_LOCS;
    else
		$nameOpts			= Person::NAME_INCLUDE_DATES;
    $name	        		= $person->getName($nameOpts);

    if ($person->getGender() == Person::FEMALE)
    {
        $gclass	    		= 'female';
        $fathersText        = $fathersfemaleText;
        $mothersText        = $mothersfemaleText;
    }
    else
    {
        $gclass	    		= 'male';
        $fathersText        = $fathersmaleText;
        $mothersText        = $mothersmaleText;
    }

    // limit depth of display
    if ($level > $ancDepth)
		return;

    $parents			= $person->getParents();
    if (count($parents) > 0)
    {			// at least one set of parents
		foreach($parents as $ip 		=> $family)
		{		// loop through parents
		    $fatherid				= $family->get('idirhusb');

		    // information about father
		    if ($fatherid > 0)
		    {		// displaying info on father
                if ($direction == 'CP')
                {       // child before parent in prefix
		            if (strlen($prefix) > 0)
                        $pText      		= $prefix . $fathersText;
                    else
                        $pText      		= $fatherText;
                }       // child before parent in prefix
                else
                {       // parent before child in prefix
		            if (strlen($prefix) > 0)
                        $pText      		= $fathersText . $prefix;
                    else
                        $pText      		= $fatherText;
                }       // parent before child in prefix
				$father			= new Person(array('idir' => $fatherid));
                $retval  .= show($father, 
                                $level+1, 
                                $pText,
                                $template);
		    }		// displaying info on father
        }		// loop through parents

        $temElt         		= $template->getElementById('showPerson');
        $ttemplate      		= new Template($temElt->outerHTML());
        $ttemplate->set('PREFIX',       $prefix);
        $ttemplate->set('IDIR',         $idir);
        $ttemplate->set('GCLASS',       $gclass);
        $ttemplate->set('NAME',         $name);
        $retval             .= $ttemplate->compile();

		foreach($parents as $ip 		=> $family)
		{		// loop through parents
		    $motherid				= $family->get('idirwife');

		    // information about mother
		    if ($motherid > 0)
            {		// displaying info on mother
                if ($direction == 'CP')
                {       // child before parent in prefix
		            if (strlen($prefix) > 0)
                        $pText      		= $prefix . $mothersText;
                    else
                        $pText      		= $motherText;
                }       // child before parent in prefix
                else
                {       // parent before child in prefix
		            if (strlen($prefix) > 0)
                        $pText      		= $mothersText . $prefix;
                    else
                        $pText      		= $motherText;
                }       // parent before child in prefix
				$mother			= new Person(array('idir' => $motherid));
                $retval .= show($mother, 
                                $level+1, 
                                $pText,
                                $template);
		    }		// displaying info on mother

		}		// loop through parents
    }			// at least one set of parents
    else
    {			// no parents
        $temElt         		= $template->getElementById('showPerson');
        $ttemplate      		= new Template($temElt->outerHTML());
        $ttemplate->set('PREFIX',       $prefix);
        $ttemplate->set('IDIR',         $idir);
        $ttemplate->set('GCLASS',       $gclass);
        $ttemplate->set('NAME',         $name);
        $retval         .= $ttemplate->compile();
    }			// no parents
    return  $retval;
}			// show


$nameuri			= '';
$person	    		= null;
$date	    		= getdate();
$curyear			= $date['year'];
$bprivlim			= $curyear - 100;	// privacy limit on birth date
$dprivlim			= $curyear - 72;	// privacy limit on death date
$bdateTxt			= '';
$ddateTxt			= '';

// defaults
$surname			= '';
$ancDepth			= 4;
$incLocs			= false;
$idir	    		= null;
$lang               = 'en';
$name               = '';
$nameUri            = '';
$treeName           = '';
$prefix             = '';

// check session parameters
foreach($_COOKIE as $key => $value)
{		            // examine all parameters
	switch ($key)
	{	            // act on specific parameters
	    case 'ancDepth':
	    {
			$ancDepth	= $value;
			break;
	    }	        // ancestor display depth

	    case 'incLocs':
	    {           // indicator of whether or not to show locations
			$incLocs	= $value;
			break;
	    }	        // to show or not to show locations
	}	            // act on specific parameters
}		            // examine all relevant session parameters

// check passed parameters
foreach($_GET as $key => $value)
{		// examine all parameters
	switch ($key)
	{	// act on specific parameters
	    case 'ancDepth':
	    {
			if (ctype_digit($value) && $value > 0)
			{
			    $ancDepth		= $value;
			    setcookie('ancDepth', $ancDepth);
			}
			else
			    $warn	.= "<p>ancDepth='$value' ignored.</p>\n";
			break;
	    }	// ancestor display depth

	    case 'incLocs':
	    {    // indicator of whether or not to show locations
			$incLocs		= $value;
			if ($incLocs)
			    setcookie('incLocs', $incLocs == 1);
			break;
	    }	// to show or not to show locations

	    case 'idir':
	    {    // identify the individual whose ancestors are to be displayed
			if (ctype_digit($value) && $value > 0)
			{
			    $idir	= intval($value);
			    $person	= new Person(array('idir' => $idir));

			    if ($person->isExisting())
			    {
					$name		= $person->getName(
							Person::NAME_INCLUDE_DATES);
					$surname	= $person->getSurname();
					$given		= $person->getGivenName();
					$bdateTxt	= $person->getBirthDate();
					$ddateTxt	= $person->getDeathDate();
					$treeName	= $person->getTreeName();
					$nameuri	= rawurlencode($surname . ', ' .$given);
					if (strlen($surname) == 0)
					    $prefix	= '';
					else
					if (substr($surname,0,2) == 'Mc')
					    $prefix	= 'Mc';
					else
					    $prefix	= substr($surname,0,1);

					$title		= "Ancestor Tree for $name";
			    }		// existing person in tree
			    else
			    {
					$msg		.=
						"There is no existing person with IDIR=$idir. ";
					$title		= "Ancestor Tree Failure";
					$surname	= 'Unknown';
					$name		= 'Unknown';
					$prefix		= '?';
			    }
			}	// syntax OK
			else
			{	// not a number
			    $msg	.= "Invalid parameter IDIR='$value'. ";
			}	// not a number
			break;
        }		// IDIR

        case 'lang':
        {
            if (strlen($value) == 2)
                $lang           = strtolower($value);
        }
	}		// act on specific parameters
}			// examine all parameters

// check for mandatory parameters
if (is_null($idir))
{		// missing parameter
	$title	= "Ancestor Tree Failure";
	$msg	.= 'Missing mandatory parameter idir. ';
}		// missing parameter

// display page
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}dialog$lang.html");
$includeSub		= "ancestorReport$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	    = new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
    $sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	    = "ancestorReporten.html";
}
$template->includeSub($tempBase . $includeSub,
			    	  'MAIN');

// I18N
$fatherText		    = $template->getElementById('Father')->innerHTML();
$motherText		    = $template->getElementById('Mother')->innerHTML();
$elt                = $template->getElementById('Fathers');
if ($elt)
{
    $fathersmaleText	= $elt->innerHTML();
    $fathersfemaleText	= $elt->innerHTML();
}
else
{
    $fathersmaleText	= $template->getElementById('Fathersmale')->innerHTML();
    $fathersfemaleText	= $template->getElementById('Fathersfemale')->innerHTML();
}
$elt                = $template->getElementById('Mothers');
if ($elt)
{
    $mothersmaleText	= $elt->innerHTML();
    $mothersfemaleText	= $elt->innerHTML();
}
else
{
    $mothersmaleText	= $template->getElementById('Mothersmale')->innerHTML();
    $mothersfemaleText	= $template->getElementById('Mothersfemale')->innerHTML();
}
$failureText		= $template->getElementById('Failure')->innerHTML();
$direction		    = $template->getElementById('direction')->innerHTML();

// pass parameters to template
$template->set('LANG',	        $lang);
$template->set('IDIR',	        $idir);
$template->set('ANCDEPTH',	    $ancDepth);
if ($incLocs)
{
    $template->set('INCLOCS',	    '1');
    $template->set('INCLOCSCHECKED','checked="checked"');
}
else
{
    $template->set('INCLOCS',	    '0');
    $template->set('INCLOCSCHECKED','');
}

if (strlen($msg) == 0)
{
	if (!is_null($person) && $person->isExisting())
	{		// individual found
	    if ($bdateTxt == 'Private')
        {
            $template->updateTag('surnamesPrefix',  null);
            $template->updateTag('surname',         null);
            $template->updateTag('depthForm',       null);
            $template->set('DATA',      '');
            $template->set('NAME',      $failureText);
            $template->set('TREENAME',	    $treeName);
            $template->set('NAMEURI',	'');
            $template->set('SURNAME',	'');
            $template->set('PREFIX',	'');
	    }
	    else
        {		// display public data
            $template->set('NAME',	        $name);
            $template->set('NAMEURI',	    $nameUri);
            $template->set('SURNAME',	    $surname);
            $template->set('PREFIX',	    $prefix);
            $template->set('TREENAME',	    $treeName);
            $template->updateTag('private',         null);
			// recursively display ancestor tree
			$template->set('DATA',       show($person, 1, '', $template));
	    }		// display public data
    }		// individual found
    else
    {
        $template->updateTag('surnamesPrefix',  null);
        $template->updateTag('surname',         null);
        $template->updateTag('depthForm',       null);
        $template->updateTag('private',         null);
        $template->set('DATA',      '');
        $template->set('NAME',      $failureText);
        $template->set('TREENAME',	    $treeName);
        $template->set('NAMEURI',	'');
        $template->set('SURNAME',	'');
        $template->set('PREFIX',	'');
    }
}			// success
else
{
    $template->updateTag('surnamesPrefix',  null);
    $template->updateTag('surname',         null);
    $template->updateTag('depthForm',       null);
    $template->updateTag('private',         null);
    $template->set('DATA',      '');
    $template->set('NAME',      $failureText);
    $template->set('TREENAME',	    $treeName);
    $template->set('NAMEURI',	'');
    $template->set('SURNAME',	'');
    $template->set('PREFIX',	'');
}

$template->display();
