<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  grantIndivid.php													*
 *																		*
 *  Display a web page to grant the authority to update an individual,	*
 *  his/her spouses, his/her descendants, and his/her ancestors.		*
 *																		*
 *  Parameters passed by method=POST:									*
 *		idir			unique numeric key of the instance of			*
 *						Person for which the grant is to be given		*
 *		pattern         pattern for limiting User Names                 *
 *																		*
 *  History:															*
 *		2010/11/08		created											*
 *		2010/12/09		add link to help page							*
 *						improve separation of PHP and HTML				*
 *						Exclude self from list of users displayed		*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/08/24		do not permit granting access to current user	*
 *		2012/01/13		change class names								*
 *		2012/02/26		sort user names in selection list				*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						correct <th class="labelsmall"> to				*
 *						class="labelSmall"								*
 *		2013/05/29		initialize parameter to legacyIndex.php			*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/08		replace table with CSS for layout				*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/01		use extended getName from LegacyIndiv			*
 *		2015/01/18		script may now be invoked using method=get		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		use User::getUsers instead of SQL query			*
 *		2016/02/06		use showTrace									*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/21		use RecordSet to get set of users for select	*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/07/12      use FtTemplate                                  *
 *		                add support for username pattern                *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get parameters
$idir                   = null;
$pattern                = '';
$lang                   = 'en';
$treename    	        = '';
$surname    	        = '';

if (count($_GET) > 0)
{
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
        {		    // act on specific parameter
            case 'idir':
            case 'id':
            {
                if (ctype_digit($value))
                    $idir       = intval($value);
                break;
            }

            case 'pattern':
            {
                $pattern        = $value;
                break;
            }

			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{		            // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
            case 'idir':
            case 'id':
            {
                if (ctype_digit($value))
                    $idir       = $value;
                break;
            }

            case 'pattern':
            {
                $pattern        = $value;
                break;
            }

			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$action             = 'Display';
$nameuri	        = '';

// note that record 0 in tblIR contains only the next available value
// of IDIR and is ignored by this implementation
if ($idir > 0)
{		            // get the requested individual
    $person		    = new Person(array('idir' => $idir));
    $treename       = $person->getTreename();

	$isOwner	    = canUser('edit') && $person->isOwner();
	 
	$name		    = $person->getName(Person::NAME_INCLUDE_DATES);
	$given		    = $person->getGivenName();
	if (strlen($given) > 2)
	    $givenPre	= substr($given, 0, 2);
	else
	    $givenPre	= $given;
	$surname	    = $person->getSurname();
	$nameuri	    = rawurlencode($surname . ', ' . $givenPre);
	if (strlen($surname) == 0)
	    $prefix	    = '';
	else
	if (substr($surname,0,2) == 'Mc')
	    $prefix	    = 'Mc';
	else
	    $prefix	    = substr($surname,0,1);
	if ($isOwner)
    {		        // OK
        if (strlen($pattern) > 0)
	        $getParms	= array('username' => array('!' . $userid, $pattern));
        else
	        $getParms	= array('username' => '!' . $userid);
        $users		= new UserSet($getParms);
        if ($users->count() > 0)
	        $action     = 'Update';
	}		        // OK
}		            // get the requested individual
else
{		            // invalid input
	$name	    	= "Invalid Value of idir=$idir";
	$person	    	= null;
    $surname    	= '';
    $users          = array();
}		            // invalid input

$template       = new FtTemplate("grantIndivid$action$lang.html");

if (strlen($surname) > 0)
{
	$links["Surnames.php?initial=$prefix"] =
						"Surnames Starting with '$prefix'";
	$links["Names.php?Surname=$surname"]  =
						"Surname '$surname'";
}		// surname present

$options            = $template['User$id'];
if ($options)
    $options->update($users);

$template->set('IDIR',          $idir);
$template->set('NAME',          $name);
$template->set('SURNAME',       $surname);
$template->set('NAMEURI',       $nameuri);
$template->set('TREENAME',      $treename);
$template->set('PREFIX',        $prefix);
$template->set('PATTERN',       $pattern);

$template->display();
