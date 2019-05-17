<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  Names.php															*
 *																		*
 *  Display a web page containing all of the individuals with a			*
 *  given surname.														*
 *																		*
 *  History:															*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/16		use LegacyDate									*
 *		2010/12/12		replace DateToString with LegacyDate::toString	*
 *						cleanup											*
 *		2011/10/31		permit clicking anywhere in the cell containing	*
 *						a link											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/17		use functions pageTop and pageBot to standardize*
 *						appearance of page								*
 *		2013/07/27		SQL implementation of SOUNDEX is different from	*
 *						every other implementation of SOUNDEX			*
 *						clean up parameter validation					*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/05/15		use LegacyIndiv::getIndivs to get matches		*
 *						display and permit edit of Notes				*
 *		2015/07/02		access PHP includes using include_path			*
 *						link to list of names starting with same letter	*
 *						was wrong										*
 *						add ability to post blogs against a name		*
 *						add option to request surname by IDNR value		*
 *		2015/07/22		link to nominal index did not expand $surname	*
 *		2016/01/19		add id to debug trace							*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		set limit to number of individuals to return	*
 *						from LegacyIndiv::getIndivs if only surname		*
 *						specified because a max of 100 will be displayed*
 *						include link to all individuals with surname	*
 *						if a given name prefix was specified			*
 *		2017/07/18		do not reference instance of LegacyName if		*
 *						the surname was not passed as a parameter		*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/18		class LegacyName renamed to class Name			*
 *		2017/09/05		add regular expression pattern field			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/16		use class RecordSet								*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/10/27		use class Template                              *
 *		                support parameters offset, limit, and lang      *
 *		                support scrolling through set of names if       *
 *		                they exceed the limit                           *
 *		2018/12/26      ignore field IDNR in Name record                *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/03/12      Surname record not created if not required      *
 *		2019/05/17      initialize SOUNDEX, FIRST, and LAST             * 
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/RecOwner.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// analyze input parameters
$prefix		        = '';
$given		        = null;
$surname		    = null;
$surnameRec		    = null;
$nameUri            = '';
$treename           = '';
$where		        = '';
$lang		        = 'en';
$givenOk		    = false;
$edit		        = false;
$action		        = 'Display';
$getParms		    = array();
$offset		        = 0;
$limit		        = 100;

// interpret parameters
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{		        // loop through parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{
	    case 'surname':
        {		// surname specified
            $surname	    = ucfirst($value);

			// identify prefix of the name, usually the first letter
			if (strlen($surname) == 0)
			    $prefix	    = '';
			else
			if (substr($surname,0,2) == 'Mc')
			    $prefix 	= 'Mc';
			else
			if (substr($surname,0,2) == "O'")
			    $prefix 	= substr($surname, 0, 3);
			else
			    $prefix	    = substr($surname,0,1);

			// construct the query
			$getParms['surname']	= $surname;
			break;
	    }		// surname specified

	    case 'idnr':
        {		// IDNR specified, deprecated
            $idnr           = $value;
			$surnameRec	    = new Surname(array('idnr' => $value));
            $surname    	= $surnameRec->get('surname');

			// identify prefix of the name, usually the first letter
			if (strlen($surname) == 0)
			    $prefix	    = '';
			else
			if (substr($surname,0,2) == 'Mc')
			    $prefix     = 'Mc';
			else
			if (substr($surname,0,2) == "O'")
			    $prefix     = substr($surname, 0, 3);
			else
			    $prefix	    = substr($surname,0,1);

			// construct the query
			$getParms['surname']	= $surname;
			break;
	    }		// surname specified

	    case 'given':
	    {	// specified a Given Name or names?
			$given		    = $value;
			if ((is_array($given) && count($given) > 0) ||
			    (is_string($given) && strlen($given) > 0))
			{		// valid parameter
			    $getParms['givenpfx']	= $given;
			    $givenOk	            = true;
			}		// valid parameter
			else
			    $givenOk	            = false;
			break;
	    }		// given name specified

	    case 'edit':
	    {		// option to edit surname record
			if (strtolower($value) == 'y' &&
                canUser('edit'))
            {
                $action         = 'Update';
                $edit	        = true;
            }
			break;
	    }		// option to edit surname record

	    case 'lang':
        {           // requested language of display
            if (strlen($value) == 2)
                $lang           = strtolower($value);            
			break;
	    }		    // requested language of display

	    case 'offset':
        {           // starting offset in set
            if (ctype_digit($value))
                $offset         = $value - 0;            
			break;
	    }		    // starting offset in set

	    case 'limit':
        {           // maximum number to display
            if (ctype_digit($value))
                $limit          = $value - 0;            
			break;
	    }		    // maximum number to display

	    case 'debug':
	    {		// debug handled by common.inc
			break;
	    }		// debug

	    default:
	    {		// unexpected
			$msg	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// unexpected
	}		// switch on parameter
}			// loop through all parameters
if ($debug && count($_GET) > 0)
    $warn       .= $parmsText . "</table>\n";

if (strlen($given) > 0)
    $nameUri        = $surname . ', ' . substr($given, 0, 2);
else
    $nameUri        = $surname;

$template	        = new FtTemplate("Names$action$lang.html");

// I18N
$tranTabTag	            = $template->getElementById('tranTab');
if ($tranTabTag)
{
	$tranTab		    = array();
	foreach($tranTabTag->childNodes() as $span)
	{
	    $key		    = $span->attributes['data-key'];
	    $tranTab[$key]	= trim($span->innerHTML());
    }

	$genderText	= array(0 => $tranTab['male'], 
				        1 => $tranTab['female'], 
				        2 => $tranTab['unknown']); 
}
else
	$genderText	= array(0 => 'male', 
				        1 => 'female', 
				        2 => 'unknown'); 
	
// determine title for page
if (is_null($surname))
{		// missing mandatory parameter
    $msg		    .= 'Missing mandatory parameter Surname';
    $surname        = '';
    $surnameRec	    = new Surname(array('idnr' => 1));
    $title	        = $template->getElementById('missing')->innerHTML();
}		// missing mandatory parameter
else
if (strlen($surname) == 0)
{
    $title	        = $template->getElementById('nosurname')->innerHTML();
    $surnameRec	    = new Surname(array('idnr' => 1));
}
else
{
    $title	        = $template->getElementById('surname')->innerHTML();
    $surnameRec	    = new Surname(array('surname' => $surname));
}
$idnr               = $surnameRec->get('idnr');
$soundslike         = $surnameRec->get('soundslike');
$pattern            = $surnameRec->get('pattern');
$notes              = $surnameRec->get('notes');
$template->set("SURNAME",	        $surname);
$template->set("PREFIX",	        $prefix);
$template->set('TITLE',	            $title, true);
$template->set("IDNR",	            $idnr);
$template->set("SOUNDSLIKE",	    $soundslike);
$template->set("SOUNDEX",	        $soundslike);
$template->set("PATTERN",	        $pattern);
$template->set("NOTES",	            $notes);
$template->set('LANG',			    $lang);
$template->set('OFFSET',			$offset+1);
$template->set('LIMIT',			    $limit);
$template->set('CONTACTKEY',		$idnr);
$template->set('CONTACTTABLE',		'Names');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
if (strlen($userid) > 0)
{
    $user	        = new User(array("username" => $userid));
    if ($user->isExisting())
    {
        $template->set('EMAILADDRESS',      $user->get('email'));
        $template->set('USERID',            $userid);
        $template->set('EMAILCLASS',        'ina');
        $template->set('EMAILREADONLY',     'readonly="readonly"');
    }
    else
    {
        $template->set('EMAILADDRESS',      '');
        $template->set('USERID',            '');
        $template->set('EMAILCLASS',        'white');
        $template->set('EMAILREADONLY',     '');
    }
}
else
{
    $template->set('EMAILADDRESS',          '');
    $template->set('USERID',                '');
    $template->set('EMAILCLASS',            'white');
    $template->set('EMAILREADONLY',         '');
}

if (strlen($msg) == 0)
{		// no errors detected
    $getParms['offset']	    = $offset;
    $getParms['limit']	    = $limit;
    $personList		        = new PersonSet($getParms);
	$info			        = $personList->getInformation();
    $count			        = $info['count'];
    $actualCount		    = $personList->count();
    if ($count > 0)
    {
        $first              = $personList->rewind();
        $nameUri            = $first->get('surname') . ', ' .
                                substr($first->get('givenname'), 0, 2);
        $treename           = $first->getTreename();
    }
}		// no errors detected
else
{
    $title	= $template->getElementById('missing')->innerHTML();
    $personList		        = array();
    $count                  = 0;
    $actualCount            = 0;
}
$template->set('TOTALCOUNT',		    $count);
$template->set('ACTUALCOUNT',		    $actualCount);
$template->set('FIRST',		            $offset + 1);
$template->set('LAST',		            min($offset + $actualCount, $count));
if ($actualCount >= $count)
    $template->updateTag('showActualCount', null);
$template->set('PREV',			    max($offset-$limit,0));
$template->set('NEXT',			    min($offset+$limit, $count-1));
$template->set('NAMEURI',           $nameUri);
$template->set('TREENAME',          $treename);

// check for notes about family
$notes	        	= $surnameRec->get('notes');
$template->set('NOTES',         $notes);
$template->set('SOUNDEX',       $soundslike);
if ($count == 0)
    $template->set('COUNT',     $tranTab['No']);
else
    $template->set('COUNT',     $count);

$idnr		        = $surnameRec->get('idnr');
$nxparms	        = array('surname' => $surname);
$nxlist		        = new RecordSet('Names', $nxparms);
$information	    = $nxlist->getInformation();
$query	            = $information['query'];
$template->set('QUERY',   $query);
$nxcount	        = $information['count'];
if ($nxcount == 0)
{                       // no matching names
    if (canUser('edit') && 
        $surnameRec->get('pattern') == '' && 
        $surnameRec->isExisting())
    {
        $surnameRec->delete(false);
    }
    else
        $template->updateTag('deletedUnused',   null);
    $template->set('NXCOUNT',   'No');
}                       // no matching names
else
{                       // some matching names
    $template->updateTag('deletedUnused',   null);
    $template->set('NXCOUNT',   $nxcount);
}                       // some matching names

if (!canUser('edit'))
    $template->updateTag('surnameForm',    null);

// display the results
$maxcols	    		= 4;
$curcol	        		= 0;
$data           		= '';
$rowElt         		= $template->getElementById('row');
$rowEltHtml     		= $rowElt->outerHTML();
$entryElt       		= $template->getElementById('entry');
$entryEltHtml   		= $entryElt->outerHTML();

foreach($personList as $idir => $person)
{
    if ($curcol == 0)
    {
        $rowTemplate    = new Template($rowEltHtml);
        $rowdata        = '';
    }
		$curcol++;

    // link to detailed query action
    $entryTemplate      = new Template($entryEltHtml);
		$name		        = $person->getName(Person::NAME_INCLUDE_DATES);
		$gender		        = $person->getGender();
    $gender             = $genderText[$gender];
    $entryTemplate->set('NAME',     $name);
    $entryTemplate->set('IDIR',     $idir);
    $entryTemplate->set('GENDER',   $gender);
    $entryTemplate->set('LANG',     $lang);
    $rowdata            .= $entryTemplate->compile();
	if ($curcol == $maxcols)
    {		    // end row and setup to start new row
        $rowTemplate->updateTag('entry',    $rowdata);
        $data           .= $rowTemplate->compile();
		    $curcol	= 0;
	}		    // end row and setup to start new row
}	            // loop through results

if ($curcol != 0)
{		        // there is an incomplete row started
    $rowTemplate->updateTag('entry',    $rowdata);
    $data           .= $rowTemplate->compile();
}		        // there is an incomplete row started
$template->updateTag('row',  $data);

// show any blog postings
if ($surnameRec->isExisting())
{
    $idnr				= $surnameRec->get('idnr');
    $blogParms			= array('keyvalue'	        => $idnr,
	    				        'table'				=> 'tblNR');
    $bloglist			= new RecordSet('Blogs', $blogParms);

	// display existing blog entries
	$blogElt    		= $template->getElementById('blogEntry');
	$data       		= '';
	foreach($bloglist as $blid => $blog)
	{		// loop through all blog entries
	    $blogTemplate   = new Template($blogElt->innerHTML());
	    $blogTemplate->set('BLID',      $blid);
	    $datetime	    = $blog->getTime();
	    $blogTemplate->set('DATETIME',  $datetime);
	    $username	    = $blog->getUser();
	    $blogTemplate->set('USERNAME',  $username);
	    $text	        = $blog->getText();
	    $text	        = str_replace("\n", "</p>\n<p>", $text);
	    $blogTemplate->set('TEXT',  $text);
	    if ($username != $userid)
	        $blogTemplate->updateTag('blogActions', null);
	    $data           .= $blogTemplate->compile();
	}		// loop through all blog entries
	$template->updateTag('blogEntry',   $data);
}
else
	$template->updateTag('blogEntry',   null);

$template->display();
