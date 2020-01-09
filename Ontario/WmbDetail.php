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
 *		2020/01/03      use Template                                    *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// action depends upon whether the user is authorized to
// update the database
if(canUser('all'))
{
	$update				= true;
	$action				= 'Update';
}
else
{
	$update				= false;
	$action				= 'Display';
}

// default parameter values
$cc                     = 'CA';
$domain                 = 'CAON';
$operator               = '';
$idmb	                = null;
$volume	                = null;
$page	                = null;
$lang                   = 'en';
$genderClass	        = 'male';

// get parameter values
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through all input parameters
	    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                         "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// process specific named parameters
		    case 'id':
		    case 'idmb':
		    {
				$idmb	    = trim($value);
				break;
		    }		    // IDMB passed
	
		    case 'volume':
		    {
				$volume	    = trim($value);
				break;
		    }		    // Volume passed
	
		    case 'page':
		    {
				$page	    = trim($value);
				break;
		    }		    // Page passed
	
		    case 'lang':
	        {		    // debug handled by common code
	            $lang       = FtTemplate::validateLang($value);
				break;
		    }		    // debug handled by common code
	
		}		        // process specific named parameters
	}			        // loop through all input parameters
	if ($debug)
	    $warn       .= $parmsText . "</table>\n";
}                       // invoked by method=get

$template               = new FtTemplate("WmbDetail$action$lang.html");
$trantab                = $template->getTranslate();
$t                      = $trantab['tranTab'];
$months                 = $trantab['Months'];
$template['otherStylesheets']->update(array('filename' => 'WmbDetail'));

if (is_string($idmb))
{                       // IDMB specified
    if (preg_match("/^([<=>!]*)([0-9]+)$/", $idmb, $matches))
    {
        $operator       = $matches[1];
        $idmb           = $matches[2];
    }
    else
    {
        $text           = $template['idmbInvalid']->innerHTML;
	    $msg	        .= str_replace('$idmb', $idmb, $text);
    }
}                       // IDMB specified
else
{                       // IDMB not specified
    $msg                .= $template['idmbMissing']->innerHTML;
    $template->set('IDMB',              '');
}                       // IDMB not specified
if (is_string($volume) && !ctype_digit($volume))
{                       // volume specified
    $text               = $template['volumeInvalid']->innerHTML;
	$msg	            .= str_replace('$volume', $volume, $text);
    $template->set('VOLUME',            $volume);
}                       // volume specified
else
    $template->set('VOLUME',            '');
if (is_string($page) && !ctype_digit($page))
{                       // page specified
    $text               = $template['pageInvalid']->innerHTML;
	$msg	            .= str_replace('$page', $page, $text);
    $template->set('PAGE',              $page);
}                       // page specified
else
    $template->set('PAGE',              '');

$template->set('LANG',                  $lang);

// if no error messages Issue the query
if (strlen($msg) == 0)
{		                // no errors
	$getNext	        = $operator == '>';
	// get the baptism registration object
	if ($volume && $page)
	{
	    $getParms	    = array('volume'	=> $volume,
					        	'page'		=> $page,
					        	'idmb'		=> $operator . $idmb,
					        	'limit'		=> 1);
	    if (!$getNext)
			$getParms['order']	= "`IDMB` DESC";
	    $baptisms	    = new RecordSet('MethodistBaptisms', $getParms);
	    if ($baptisms->count() > 0)
			$baptism	= $baptisms->rewind();
	    else
	    {		        // ran off end or beginning of page
			if ($getNext)
			{		    // get first line of next page
			    $getParms	= array('volume'	=> $volume,
			        				'page'		=> $page + 1,
					        		'limit'		=> 1);
			    $baptisms	= new RecordSet('MethodistBaptisms', $getParms);
			}		    // get first line of next page
			else
			if ($page > 1)
			{		    // get last line of previous page
			    $getParms	= array('volume'	=> $volume,
				        			'page'		=> $page - 1,
						        	'limit'		=> 1,
			        				'order'		=> "`IDMB` DESC");
			    $baptisms	= new RecordSet('MethodistBaptisms', $getParms);
			}		    // get last line of previous page

			// get first record in set
			if ($baptisms->count() > 0)
			    $baptism	= $baptisms->rewind();
			else
			    $baptism	= null;
	    }		        // ran off end or beginning of page
	}
    else
    {                   // specific record
        $template['pageDisplay']->update(null);
        $baptism	        = new MethodistBaptism(array('idmb' => $idmb));
    }                   // specific record

	if ($baptism && $baptism->isExisting())
	{			        // have a record from the database
	    // copy contents into working variables
	    $idmb		    = $baptism['idmb'];
	    $volume		    = $baptism['volume'];
	    $page		    = $baptism['page'];
	    $surname		= $baptism['surname'];
	    $idir		    = $baptism['idir'];
	    $givenName		= $baptism['givenname'];
	    $birthDate		= $baptism['birthdate'];
	    $person		    = null;
	    $imatches		= array();

	    // if this registration is not already linked to
	    // look for individuals who match
	    if ($idir == 0 && $update)
	    {			// updating 
			// check for existing citations to this registration
			$citpattern         = "V[^\d]*$volume.*Page $page.*# $idmb"; 
			$citparms	        = array('idsr'		=> 158,
		    		    	            'type'		=> Citation::STYPE_BIRTH,
			    		                'srcdetail' => $citpattern); 
			$citations	        = new CitationSet($citparms);
			if ($citations->count() > 0)
			{		// citation to birth in old location
			    $citrow	        = $citations->rewind(); // first citation
			    $idir	        = $citrow->get('idime');
			}		// citation to birth in old location
			else
			{		// check for event citation
			    $citparms	    = array('idsr'	    => 158,
		                		    	'type'	    => Citation::STYPE_EVENT,
			    		                'srcdetail' => $citpattern); 
			    $citations	    = new CitationSet($citparms);
			    foreach($citations as $idsx => $citation)
			    {
					$ider		    = $citation->get('idime');
					$event		    = new Event($ider);
					$idet		    = $event->getIdet();
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
			    $birthrange	    = array(($birthYear - 2) * 10000,
				        			    ($birthYear + 2) * 10000);
			    // search for a match on any of the parts of the
			    // given name
			    $gnameList	    = explode(' ', $givenName);

			    // quote the surname value
			    $getParms	    = array('loose'		=> true,
				            			'surname'	=> $surname,
				            			'givenname'	=> $gnameList,
				            			'birthsd'	=> $birthrange,
				            			'incmarried'	=> true,
			                			'order'		=> 'tblNX.Surname, tblNX.GivenName, tblIR.BirthSD');
			    $imatches	    = new PersonSet($getParms);
			}			// record is initialized with name
			else
			if ($idir > 0 &&
			    strlen($surname) == 0 && strlen($givenName)== 0) 
			{			// record is uninitialized

			    if ($idir > 0)
			    {		// found a citation
					$person	    = new Person(array('idir' => $idir));
					$linkedName	= $person->getName(Person::NAME_INCLUDE_DATES);
			    }		// found a citation
			}			// record is uninitialized
	    }			    // updating

	    // get information from the existing link
	    if ($idir > 0)
	    {			    // existing link
			if ($debug)
			    $warn		    .= "<p>Existing link IDIR=$idir</p>\n";
			if (is_null($person))
				$person	        = new Person(array('idir' => $idir));
            if ($person->isExisting())
            {
			    $linkedName     = $person->getName(Person::NAME_INCLUDE_DATES);
			    $maidenName	    = $person->getSurname();
			    $genderClass	= $person->getGenderClass();
			    if ($maidenName != $surname)
			    {		// $surname is not maiden name
					$linkedName	= str_replace($maidenName,
								              "($maidenName) $surname",
						            		  $linkedName);
			    }		// $surname is not maiden name
            } 
            else
			{
			    $linkedName	    = $givenName . ' ' . $surname .
				    		      ' (not found in database)';
			}
	        $template->set('LINKEDNAME',		     $linkedName);
	    }			    // existing link

	    // copy contents into working variables
	    // some of the fields may have been changed by the cross-ref code
	    $surname	            = $baptism['surname'];
	    $givenName	            = $baptism['givenname'];

        $subject	            = "number: $idmb, $givenName $surname";

	    $birthDate	            = $baptism['birthdate'];
		$rxResult   		    = preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
						    	    	     $birthDate,
							    	         $matches);
		if ($rxResult > 0)
            $birthDate	        = $matches[3] . ' ' . 
                                    $months[intval($matches[2])] . ' ' . 
                                    $matches[1];
        else
            $birthDate	        = str_replace("'","&#39;",$birthDate);
		
		$baptismdate	        = $baptism['baptismdate'];
		$rxResult		        = preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
							        	     $baptismdate,
								             $matches);
		if ($rxResult > 0)
            $baptismdate        = $matches[3] . ' ' . 
                                    $months[intval($matches[2])] .
                                    ' ' . $matches[1];
        else
		    $baptismdate	    = str_replace("'","&#39;",$baptismdate);
		
		$baptismplace	    = str_replace("'","&#39;",$baptism['baptismplace']);
		$minister	        = str_replace("'","&#39;",$baptism['minister']);
		$commap		        = strpos($minister, ',');
		if ($commap > 0)
		{                   // surname before given name
		    $minister	    = trim(substr($minister, $commap + 1)) . ' ' .
				    		  substr($minister, 0, $commap);
        }                   // surname before given name

        $template->set('IDMB',              $idmb);
        $template->set('VOLUME',            $volume);
        $template->set('PAGE',              $page);
		$template->set('DISTRICT',		    $baptism['district']);
		$template->set('AREA',		        $baptism['area']);
	    $template->set('GIVENNAME',		    $givenName);
	    $template->set('SURNAME',		    $surname);
		$template->set('FATHER',		    $baptism['father']);
		$template->set('MOTHER',		    $baptism['mother']);
		$template->set('RESIDENCE',		    $baptism['residence']);
		$template->set('BIRTHPLACE',		$baptism['birthplace']);
	    $template->set('BIRTHDATE',		    $birthDate);
		$template->set('BAPTISMDATE',		$baptismdate);
		$template->set('BAPTISMPLACE',		$baptismplace);
		$template->set('MINISTER',		    $minister);
	    $template->set('IDIR',		        $idir);
	    $template->set('GENDERCLASS',		$genderClass);
        
        if ($idir > 0)
		{	                // link to family tree database
		    $template['MatchRow']->update(null);
		}	                // link to family tree database
		else
		if (count($imatches) > 0)
		{                   // possible matches
            $template['LinkRow']->update(null);
            $optionElt          = $template['option$idir'];
            $optionText         = $optionElt->outerHTML;
            $data               = '';
		    foreach($imatches as $iidir => $person)
		    {               // loop through results
				$igivenname	    = $person->get('givenname'); 
				$isurname	    = $person->get('surname');
				$isex		    = $person->get('gender');
				if ($isex == Person::MALE)
				{
				    $sexclass	= 'male';
				    $childrole	= $t['son'];
				    $spouserole	= $t['husband'];
				}
				else
				if ($isex == Person::FEMALE)
				{
				    $sexclass	= 'female';
				    $childrole	= $t['daughter'];
				    $spouserole	= $t['wife'];
				}
				else
				{
				    $sexclass	= 'unknown';
				    $childrole	= $t['child'];
				    $spouserole	= $t['spouse'];
				}
	
				$iname  	    = $person->getName(Person::NAME_INCLUDE_DATES);
				$parents	    = $person->getParents();
				$comma		    = ' ';
				foreach($parents as $idmr => $set)
				{	            // loop through parents
				    $pfather	= $set->getHusbName();
				    $pmother	= $set->getWifeName();
                    $iname	    .=
                            "$comma$childrole of $pfather and $pmother";
				    $comma	    = ', ';
				}	            // loop through parents
	
				$families	    = $person->getFamilies();
				$comma		    = ' ';
				foreach ($families as $idmr => $set)
				{	            // loop through families
				    if ($isex == Person::FEMALE)
						$spouse	= $set->getHusbName();
				    else
						$spouse	= $set->getWifeName();
				    $iname	    .= "$comma$spouserole of $spouse";
				    $comma	    = ', ';
	            }	            // loop through families
                $rtemplate      = new \Templating\Template($optionText);
                $tparms         = array('idir'      => $iidir,
                                            'sexclass'  => $sexclass,
                                            'iname'     => $iname);
                $rtemplate['option$idir']->update($tparms);
                $data           .= $rtemplate->compile();
            }	            // loop through results
            $optionElt->update($data);

        }                   // possible matches
        else
        {
		    $template['MatchRow']->update(null);
            $template['LinkRow']->update(null);
        }
	}			            // have a record from the database
	else
	{
	    $subject	            = "not found";
	    $msg	                .= $template['noMatch']->innerHTML;
	    $template->set('VOLUME',            '');
	    $template->set('PAGE',              '');
		$template->set('DISTRICT',		    '');
		$template->set('AREA',		        '');
	    $template->set('GIVENNAME',		    '');
	    $template->set('SURNAME',		    '');
		$template->set('FATHER',		    '');
		$template->set('MOTHER',		    '');
		$template->set('RESIDENCE',		    '');
		$template->set('BIRTHPLACE',		'');
	    $template->set('BIRTHDATE',		    '');
		$template->set('BAPTISMDATE',		'');
		$template->set('BAPTISMPLACE',		'');
		$template->set('MINISTER',		    '');
	    $template->set('IDIR',		        0);
        $templare['distform']->update(null);
	}
}			            // no errors, perform query
else
{			// error detected
    $template->set('VOLUME',            '');
    $template->set('PAGE',              '');
	$template->set('DISTRICT',		    '');
	$template->set('AREA',		        '');
    $template->set('GIVENNAME',		    '');
    $template->set('SURNAME',		    '');
	$template->set('FATHER',		    '');
	$template->set('MOTHER',		    '');
	$template->set('RESIDENCE',		    '');
	$template->set('BIRTHPLACE',		'');
    $template->set('BIRTHDATE',		    '');
	$template->set('BAPTISMDATE',		'');
	$template->set('BAPTISMPLACE',		'');
	$template->set('MINISTER',		    '');
    $template->set('IDIR',		        0);
    $subject	    = "number: " . $idmb;
    $templare['distform']->update(null);
}			        // error detected

$template->display();
