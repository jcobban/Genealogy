<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  descendantReport.php												*
 *																		*
 *  Display a web page containing a report of the descendants of an		*
 *  individual.															*
 *																		*
 *  Parameters (passed by method='get'):								*
 *		idir			unique numeric identifier of the individual		*
 *						whose descendants are to be displayed.			*
 *		descDepth		number of generations to display				*
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
 *		2013/02/16		standardize appearance of depth input field		*
 *		2013/03/07		LegacyFamily::getChild returns instance of		*
 *						Child											*
 *		2013/05/17		use standard functions to layout header and		*
 *						footer											*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *						include all owners in the contact Author email	*
 *						remove use of deprecated interfaces				*
 *		2013/07/30		defer facebook initialization until after load	*
 *						clean up initialization							*
 *						correct invalid exposure of information for		*
 *						private											*
 *						add mouseover help for all fields				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/10		remove tables									*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2014/12/26		getFamilies result is indexed by IDMR			*
 *		2014/12/26		fix <?print to <?php print						*
 *		2015/01/01		use getBirthDate and getDeathDate				*
 *						and extended getName from LegacyIndiv			*
 *		2015/01/23		remove top and bottom headers					*
 *						add close button								*
 *		2015/03/07		use LegacyIndiv::getName to obtain information	*
 *						to display about an individual					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/07		use cookies rather than sessions				*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/06/03		add Full Screen button							*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/10/24		use class Template                              *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function show														*
 *																		*
 *  Show an individual and that individual's children.					*
 *																		*
 *	Input:																*
 *		$person         Instance of class Person                        *
 *		$level          descendant level                                *
 *		$template		Main template for the page						*
 *																		*
 *	Returns:															*
 *	    HTML in a string                                                *
 ************************************************************************/
function show($person, $level, $template)
{
    global $bprivlim;
    global $mprivlim;
    global $dprivlim;
    global $descDepth;
    global $incLocs;

    $elt            = $template->getElementById('descendant');
    if ($level == 1)
        $ttemplate  = new Template($elt->innerHTML());
    else
        $ttemplate  = new Template($elt->outerHTML());

    $idir	        = $person->getIdir();
    $ttemplate->set('IDIR',     $idir);

    if ($incLocs)
		$nameOpts	= Person::NAME_INCLUDE_LOCS;
    else
		$nameOpts	= Person::NAME_INCLUDE_DATES;
    $name	        = $person->getName($nameOpts);
    $ttemplate->set('NAME',     $name);

    if ($person->getGender() == Person::FEMALE)
		$gclass	= 'female';
    else
        $gclass	= 'male';
    $ttemplate->set('GCLASS',   $gclass);

    $families	= $person->getFamilies();
    if (count($families) > 0)
    {
        $num	        = 1;	// counter for children
        $marriages      = '';   // accumulate output text for marriages

		foreach($families as $fi => $family)
		{		// loop through marriages
            $elt            = $template->getElementById('marriage');
            $mtemplate      = new Template($elt->outerHTML());
		    if ($person->getGender() == Person::FEMALE)
		    {		// female
				$spsid		= $family->get('idirhusb');
				$spsclass	= 'male';
		    }		// female
		    else
		    {		// male
				$spsid		= $family->get('idirwife');
				$spsclass	= 'female';
            }		// male
            $mtemplate->set('SPSID',        $spsid);
            $mtemplate->set('SPSCLASS',     $spsclass);

		    // date of marriage
		    $mdate		    = new LegacyDate($family->get('mard')); 
		    $mdateTxt		= $mdate->toString($mprivlim);
		    $idlr		    = $family->get('idlrmar');
		    $mloc		    = new Location(array('idlr' => $idlr));
            $mlocTxt		= $mloc->toString();
            $mtemplate->set('MDATETXT',     $mdateTxt);
			if ($incLocs && strlen($mlocTxt) > 0)
                $mtemplate->set('MLOCTXT',      $mlocTxt);
            else
                $mtemplate->updateTag('marriageLoc', null);

		    // information about spouse
		    if ($spsid > 0)
		    {		// displaying info on spouse:
				$spouse		= new Person(array('idir' => $spsid));
                $name		= $spouse->getName($nameOpts);
                $mtemplate->set('NAME',     $name);
            }
            else
                $mtemplate->set('NAME',     'unknown');

		    // limit depth of display
            if ($level <= $descDepth)
            {               // display information about children
		        $children	= $family->getChildren();
		        if (count($children) > 0)
                {		    // found at least one child record
                    $showChild      = '';
				    foreach($children as $idcr => $child)
				    {		// loop through all child records
                        $showChild  .= show($child->getPerson(),
                                            $level+1,
                                            $template);
				    }	    // loop through all child records
                    $mtemplate->set('SHOWCHILD', $showChild);
                }		    // found at least one child record
                else
                    $mtemplate->updateTag('children', null);
            }               // display information about children
            else
                $mtemplate->updateTag('children', null);

            $marriages  .= $mtemplate->compile();
		}	            	// loop through families
        $ttemplate->set('MARRIAGES',    $marriages);
    }			            // at least one marriage
    else
        $ttemplate->set('MARRIAGES',    '');

    return $ttemplate->compile();
}		// function show


// defaults
$idir		    = null;
$person		    = null;
$date		    = getdate();
$curyear		= $date['year'];
$bprivlim		= $curyear - 100;	// privacy limit on birth date
$mprivlim		= $curyear - 80;	// privacy limit on marriage date
$dprivlim		= $curyear - 50;	// privacy limit on death date
$descDepth		= 4;
$incLocs		= false;
$nameUri		= '';
$surname		= '';
$lang		    = 'en';
$treeName	    = '';

// check for values saved in cookies on the browser
foreach($_COOKIE as $key => $value)
{
	switch($key)
	{
	    case 'descDepth':
	    {		// get the maximum depth of the tree to display
			$descDepth	= $value;
			break;
	    }		// get the maximum depth of the tree to display

	    case 'incLocs':
	    {		// indicator of whether or not to show locations
			$incLocs	= $value;
			break;
	    }		// indicator of whether or not to show locations
	}		// act on specific keys
}			// loop through all passed parameter

// check for changes from passed parameters
foreach($_GET as $key => $value)
{
	switch($key)
	{
	    case 'descDepth':
	    {		// get the maximum depth of the tree to display
			$descDepth	= $value;
			setcookie('descDepth', $descDepth);
			break;
	    }		// get the maximum depth of the tree to display

	    case 'incLocs':
	    {		// indicator of whether or not to show locations
			$incLocs	= $value == 1;
			setcookie('incLocs', $incLocs);
			break;
	    }		// indicator of whether or not to show locations

	    case 'id':
	    case 'idir':
	    {		// identification of root individual
			$idir		= $value;
			// check if current user is an owner of the record and therefore
			// permitted to see private information and edit the record
			try
			{
			    $person	= new Person(array('idir' => $idir));
	
			    $name	    = $person->getName(Person::NAME_INCLUDE_DATES);
			    $surname	    = $person->getSurname();
			    $given  	    = $person->getGivenName();
			    $nameUri	    = rawurlencode($surname . ', ' . $given);
			    if (strlen($surname) == 0)
					$prefix		= '';
			    else
			    if (substr($surname,0,2) == 'Mc')
					$prefix		= 'Mc';
			    else
					$prefix		= substr($surname,0,1);
			    $bdateTxt		= $person->getBirthDate();
				$treeName	    = $person->getTreeName();
			}	// try
			catch(Exception $e)
			{
			    $msg	.= 'Invalid parameter: ' .
						   $e->getMessage();
			    $title	= "Descendant Tree Failure";
			}	// catch
			break;
        }		// identification of root individual

        case 'lang':
        {
            if (strlen($value) == 2)
                $lang           = strtolower($value);
        }
	}		// act on specific keys
}			// loop through all passed parameter

if (is_null($idir))
{		// missing parameter
	$title	= "Descendant Tree Failure";
	$msg	.= 'Missing mandatory parameter idir. ';
}		// missing parameter

// display page
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}dialog$lang.html");
$includeSub		= "descendantReport$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	    = new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
    $sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	    = "descendantReporten.html";
}
$template->includeSub($tempBase . $includeSub,
                      'MAIN');
if (strlen($surname) == 0)
{
	$template->updateTag('surnamesPrefix',  null);
	$template->updateTag('surname',         null);
}		// surname present

// pass parameters to template
$failureText		= $template->getElementById('Failure')->innerHTML();
$template->set('LANG',	        $lang);
$template->set('IDIR',	        $idir);
$template->set('DESCDEPTH',	    $descDepth);
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
            $template->set('TREENAME',	$treeName);
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
			$template->set('DATA',       show($person, 1, $template));
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
        $template->set('TREENAME',	$treeName);
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
    $template->set('TREENAME',	$treeName);
    $template->set('NAMEURI',	'');
    $template->set('SURNAME',	'');
    $template->set('PREFIX',	'');
}

$template->display();

