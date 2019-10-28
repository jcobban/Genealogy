<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbDetail.php														*
 *																		*
 *  Display the contents of a Wesleyan Methodist Baptism as a detail	*
 *  form with optional ability to update the record.					*
 *																		*
 *  Input (passed by method=get):										*
 *		Volume			volume number									*
 *		Page			page number										*
 *		IDMB			record number									*
 *																		*
 *  History:															*
 *		2016/02/22		created											*
 *		2016/03/06		reformat dates to dd mmm yyyy					*
 *						use class to represent sex of matching entries	*
 *		2016/04/25		replace ereg with preg_match					*
 *	    2016/11/02	    prev and next buttons stay within current page	*	
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/13		$imatches not defined							*
 *		2017/03/19		use preferred parameters for new Person			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/11		use RecordSet									*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2017/11/13		use PersonSet in place of Person::getPersons	*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/07/11      use Template                                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

					'10'	=> 'Oct',
					'11'	=> 'Nov',
					'12'	=> 'Dec');

// action depends upon whether the user is authorized to
// update the database
if(canUser('all'))
	$action             = 'Update';
else
    $action             = 'Display';

// default parameter values
$idmb	                = null;
$volume	                = null;
$page	                = null;
$lang                   = 'en';

// get parameter values
if (count($_GET) > 0)
{                   // invocation from query
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			    // loop through all input parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                         "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		    // process specific named parameters
		    case 'id':
		    case 'idmb':
		    {
				if (ctype_digit($value))
				{
				    $idmb	    = $value;
				}
				break;
		    }		// IDMB passed
	
		    case 'volume':
		    {
				if (ctype_digit($value))
				{
				    $volume	    = $value;
				}
				break;
		    }		// Volume passed
	
		    case 'page':
		    {
				if (ctype_digit($value))
				{
				    $page	    = $value;
				}
				break;
		    }		// Page passed
	
		    case 'debug':
		    {		// handled by common code
				break;
		    }		// handled by common code
	
		    case 'lang':
            {		// handled by common code
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value, 0, 2));
				break;
		    }		// handled by common code
	
		    default:
		    {		// any other paramters
				$warn	.= "Unexpected parameter $key='$value'. ";
				break;
		    }		// any other paramters
		}		    // process specific named parameters
	}			    // loop through all input parameters
	if ($debug)
	    $warn           .= $parmsText . "</table>\n";
}                   // invocation from query

$template               = new FtTemplate("WmbDetail$action$lang.html");
$trtemplate             = $template->getTranslate();
$monthName              = $trtemplate['Months'];

if (is_null($idmb))
{
	$msg		        .= "IDMB parameter omitted. ";
}

// if no error messages Issue the query
if (strlen($msg) == 0)
{		            // no errors
	$getNext	                = substr($idmb,0,1) == '>';
	// get the baptism registration object
	if ($volume && $page)
	{               // get next or preceding entry on page
	    $getParms	            = array('volume'	=> $volume,
    			        	    		'page'		=> $page,
    			        	    		'idmb'		=> $idmb,
    			        	    		'limit'		=> 1);
	    if (!$getNext)
			$getParms['order']	= "`IDMB` DESC";
	    $baptisms	            = new RecordSet('MethodistBaptisms', $getParms);
	    if ($baptisms->count() > 0)
			$baptism	        = $baptisms->rewind();
	    else
	    {		    // ran off end or beginning of page
			if ($getNext)
			{		// get first line of next page
			    $getParms	    = array('volume'	=> $volume,
	        				    		'page'		=> $page + 1,
			        		    		'limit'		=> 1);
			    $baptisms	    = new RecordSet('MethodistBaptisms', $getParms);
			}		// get first line of next page
			else
			if ($page > 1)
			{		// get last line of previous page
			    $getParms	    = array('volume'	=> $volume,
	        				    		'page'		=> $page - 1,
	        				    		'limit'		=> 1,
	        				    		'order'		=> "`IDMB` DESC");
			    $baptisms	    = new RecordSet('MethodistBaptisms', $getParms);
			}		// get last line of previous page

			// get first record in set
			if ($baptisms->count() > 0)
			    $baptism	= $baptisms->rewind();
			else
			    $baptism	= null;
	    }		    // ran off end or beginning of page
	}
	else
	    $baptism	        = new MethodistBaptism(array('idmb' => $idmb));

	if ($baptism && $baptism->isExisting())
	{			// have a record from the database
	    // copy contents into working variables
	    $volume		        = $baptism->get('volume');
	    $page		        = $baptism->get('page');
	    $surname		    = $baptism->get('surname');
	    $idir		        = $baptism->get('idir');
	    $givenName		    = $baptism->get('givenname');
	    $birthDate		    = $baptism->get('birthdate');
	    $person		        = null;
	    $imatches		    = array();

	    // if this registration is not already linked to
	    // look for individuals who match
	    if ($idir == 0 && $update)
	    {			// updating 
			// check for existing citations to this registration
			$citparms	    =
				array('idsr'		=> 158,
				      'type'		=> Citation::STYPE_BIRTH,
					  'srcdetail'   => "V[^\d]*$volume.*Page $page.*# $idmb"); 
			$citations	    = new CitationSet($citparms);
			if ($citations->count() > 0)
			{		// citation to death in old location
			    $citrow	    = $citations->rewind();
			    $idir	    = $citrow->get('idime');
			}		// citation to death in old location
			else
			{		// check for event citation
			    $citparms	=
				    array('idsr'	=> 158,
						  'type'	=> Citation::STYPE_EVENT,
				    	  'srcdetail'=> "V[^\d]*$volume.*Page $page.*# $idmb"); 
			    $citations	= new CitationSet($citparms);
			    foreach($citations as $idsx => $citation)
			    {
					$ider	= $citation->get('idime');
					$event	= new Event($ider);
					$idet	= $event->getIdet();
					if ($idet == Event::ET_BIRTH)
					{
					    $idir		= $event->getIdir();
					    break;
					}
			    }
			}		// check for event citation

			if ($idir == 0 &&
			    strlen($surname) > 0 && strlen($givenName) > 0) 
			{			// no existing citation
			    if ($debug)
					$warn	.= "<p>Search for match on $surname, $givenName</p>\n";
			    // look for individuals in the family tree whose names are
			    // rough matches to the name on the death registration
			    // who have the same sex, and who were born within 2 years
			    // of the deceased.

			    // obtain the birth year
			    $rxResult		= preg_match('/[0-9]{4}/',
								     $birthDate,
								     $matches);
			    if ($rxResult > 0)
					$birthYear	= intval($matches[0]);
			    else
					$birthYear	= 1800;

			    // look 2 years on either side of the year
			    $birthrange	= array(($birthYear - 2) * 10000,
							    ($birthYear + 2) * 10000);
			    // search for a match on any of the parts of the
			    // given name
			    $gnameList	= explode(' ', $givenName);

			    // quote the surname value
			    $getParms	= array('loose'		=> true,
							'surname'	=> $surname,
							'givenname'	=> $gnameList,
							'birthsd'	=> $birthrange,
							'incmarried'	=> true,
			    			'order'		=> 'tblNX.Surname, tblNX.GivenName, tblIR.BirthSD');
			    $imatches	= new PersonSet($getParms);
			}			// record is initialized with name
			else
			if ($idir > 0 &&
			    strlen($surname) == 0 && strlen($givenName)== 0) 
			{			// record is uninitialized

			    if ($idir > 0)
			    {		// found a citation
					try {
					    $person	= new Person(array('idir' => $idir));
					    $linkedName	= $person->getName(Person::NAME_INCLUDE_DATES);
					} catch (Exception $e) {
					    $msg	.= "Exception: " .  $e->getMessage();
					}
			    }		// found a citation
			}			// record is uninitialized
	    }			// updating

	    // get information from the existing link
	    if ($idir > 0)
	    {			// existing link
			if ($debug)
			    $warn		.= "<p>Existing link IDIR=$idir</p>\n";
			try {
			    if (is_null($person))
					$person	= new Person(array('idir' => $idir));
			    $linkedName = $person->getName(Person::NAME_INCLUDE_DATES);
			    $maidenName	= $person->getSurname();
			    $genderClass	= $person->getGenderClass();
			    if ($maidenName != $surname)
			    {		// $surname is not maiden name
					$linkedName	= str_replace($maidenName,
								  "($maidenName) $surname",
								  $linkedName);
			    }		// $surname is not maiden name
			} catch (Exception $e)
			{
			    $linkedName	= $givenName . ' ' . $surname .
						      ' (not found in database)';
			}
	    }			// existing link

	    // copy contents into working variables
	    // some of the fields may have been changed by the cross-ref code
	    $surname	= $baptism->get('surname');
	    $givenName	= $baptism->get('givenname');
	    $birthDate	= $baptism->get('birthdate');

	    $subject	= "number: " . 
					      $idmb . ', ' . 
					      $givenName . ' ' . $surname;
	}			// have a record from the database
	else
	{
	    $subject	= "not found";
	    $msg	    .= "No match found for supplied parameters.";
	}
}			// no errors, perform query
else
{			// error detected
    $baptism        = null;
	$subject	    = "number: " . $idmb;
	$volume		    = '';
	$page		    = '';
}			// error detected

if ($baptism)
{			// no errors
	// copy contents into working variables
	$idmb			= str_replace("'","&#39;",$baptism->get('idmb'));
	$volume			= str_replace("'","&#39;",$baptism->get('volume'));
	$page			= str_replace("'","&#39;",$baptism->get('page'));
	$district		= str_replace("'","&#39;",$baptism->get('district'));
	$area			= str_replace("'","&#39;",$baptism->get('area'));
	$givenname		= str_replace("'","&#39;",$baptism->get('givenname'));
	$surname		= str_replace("'","&#39;",$baptism->get('surname'));
	$father			= str_replace("'","&#39;",$baptism->get('father'));
	$mother			= str_replace("'","&#39;",$baptism->get('mother'));
	$residence		= str_replace("'","&#39;",$baptism->get('residence'));
	$birthplace		= str_replace("'","&#39;",$baptism->get('birthplace'));
	$birthdate		= str_replace("'","&#39;",$baptism->get('birthdate'));
	$rxResult		= preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
							     $birthDate,
							     $matches);
	if ($rxResult > 0)
	    $birthdate	= $matches[3] . ' ' . $monthName[$matches[2]] .
					  ' ' . $matches[1];
	
	$baptismdate	= str_replace("'","&#39;",$baptism->get('baptismdate'));
	$rxResult		= preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
							     $baptismdate,
							     $matches);
	if ($rxResult > 0)
	    $baptismdate= $matches[3] . ' ' . $monthName[$matches[2]] .
					  ' ' . $matches[1];
	
	$baptismplace	= str_replace("'","&#39;",$baptism->get('baptismplace'));
	$minister	= str_replace("'","&#39;",$baptism->get('minister'));
	$commap		= strpos($minister, ',');
	if ($commap > 0)
	{
	    $minister	= trim(substr($minister, $commap + 1)) . ' ' .
					  substr($minister, 0, $commap);
	}
	$idir		= $baptism->get('idir');
	    foreach($imatches as $iidir => $person)
	    {
			$igivenname	= $person->get('givenname'); 
			$isurname	= $person->get('surname');
			$isex		= $person->get('gender');
			if ($isex == Person::MALE)
			{
			    $sexclass	= 'male';
			    $childrole	= 'son';
			    $spouserole	= 'husband';
			}
			else
			if ($isex == Person::FEMALE)
			{
			    $sexclass	= 'female';
			    $childrole	= 'daughter';
			    $spouserole	= 'wife';
			}
			else
			{
			    $sexclass	= 'unknown';
			    $childrole	= 'child';
			    $spouserole	= 'spouse';
			}

			$iname  	= $person->getName(Person::NAME_INCLUDE_DATES);
			$parents	= $person->getParents();
			$comma		= ' ';
			foreach($parents as $idmr => $set)
			{	// loop through parents
			    $pfather	= $set->getHusbName();
			    $pmother	= $set->getWifeName();
			    $iname	.= "$comma$childrole of $pfather and $pmother";
			    $comma	= ', ';
			}	// loop through parents

			$families	= $person->getFamilies();
			$comma		= ' ';
			foreach ($families as $idmr => $set)
			{	// loop through families
			    if ($isex == Person::FEMALE)
					$spouse	= $set->getHusbName();
			    else
					$spouse	= $set->getWifeName();
			    $iname	.= "$comma$spouserole of $spouse";
			    $comma	= ', ';
			}	// loop through families
	    }	    // loop through results
	}	        // not matched to some persons in database
