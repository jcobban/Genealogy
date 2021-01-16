<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  chooseRelative.php													*
 *																		*
 *  Display a web page to select a specific existing individual			*
 *  from the Legacy table of individuals.								*
 *																		*
 *  URI Parameters (passed by method="GET"):							*
 *																		*
 *		idir			mandatory, the selected individual is made the	*
 *						initial default selection						*
 *																		*
 *  History:															*
 *		2011/01/16		Created											*
 *		2012/01/07		match functionality of legacyIndex.html			*
 *		2012/01/13		change class names								*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						correct <th class="labelsmall"> to				*
 *						class="labelSmall"								*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *		2013/08/01		remove pageTop and pageBot because this is a	*
 *						popup dialog									*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/10		remove tables									*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/01		use extended LegacyIndiv::getName to get name	*
 *						with birth and death dates						*
 *		2015/06/20		leave a little extra space above selection list	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/05/24		missing dialogBot								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/02/19      use Template                                    *
 *      2019/11/17      move CSS to <head>                              *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *                      correct page title                              *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// parameters
$lang               = 'en';
$idir               = null;
$idirtext           = null;
$name               = '?';

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    $value		= trim($value);
		switch(strtolower($key))
		{			// act on specific parameters
		    case 'idir':
		    case 'id':
		    {			// get the individual by identifier
				if (is_int($value) || ctype_digit($value))
				{
				    $idir		= $value;
				    $getParms['idir']	= $idir;
				}
                else
                    $idirtext           = htmlspecialchars($value);
				break;
		    }			// get the individual by identifier
	
		    case 'lang':
		    {
	            $lang       = FtTemplate::validateLang($value);
				break;
		    }
		}			// act on specific parameters
	}				// loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			        // invoked by method=get

$template		    = new FtTemplate("chooseRelative$lang.html", true);
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'chooseRelative'));

if (is_string($idirtext))
{
    $msg	        .= "Invalid IDIR=$idirtext. ";
    $name           = $idirtext;
    $template['indForm']->update(null);
}
else
if ($idir)
{
    $person	        = new Person(array('idir' => $idir));

    // check if current user is an owner of the record and therefore
    // permitted to see private information and edit the record
    $isOwner	    = $person->isOwner();

    // get information for constructing title and
    // breadcrumbs
    $name	        = $person->getName(Person::NAME_INCLUDE_DATES);
    $given	        = $person->getGivenName();
    $surname	    = $person->getSurname();
    $treename       = $person->getTreename();
    $nameuri	    = rawurlencode($surname . ', ' . $given);
    if (strlen($surname) == 0)
		$prefix	    = '';
    else
    if (substr($surname,0,2) == 'Mc')
		$prefix	    = 'Mc';
    else
		$prefix	    = substr($surname,0,1);

    $template->set('IDIR',		    $idir);
    $template->set('NAME',		    $name);
    $template->set('GIVEN',		    $given);
    $template->set('SURNAME',		$surname);
    if (strlen($surname) > 2)
        $template->set('PREFIX',    substr($surname,0,2));
    else
        $template->set('PREFIX',    $surname);
    $template->set('TREENAME',		$treename);
    $template->set('NAMEURI',		$nameuri);
}		// default individual specified
else
{
    $msg	        .= 'Missing mandatory parameter idir. ';
    $name	        = 'Missing IDIR';
    $template['indForm']->update(null);
}
$template->set('NAME',		    $name);
$text               = $template['title']->innerHTML();
$template->set('TITLE', str_replace('$NAME', $name, $text));

$template->display();
