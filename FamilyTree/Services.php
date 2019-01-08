<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Services.php														*
 *																		*
 *  Display a web page with a list of genealogical database				*
 *  services.															*
 *																		*
 *  History:															*
 *		2010/10/23		add display of userid in header					*
 *		2010/11/04		use htmlHeader									*
 *						add help										*
 *		2010/12/03		add tables tblCS, tblCP, tblET, tblAR			*
 *		2010/12/12		add date of birth estimation tool				*
 *		2011/02/05		add reporting tool								*
 *						make birth date calculator available to all		*
 *		2011/07/30		include javascript for signon button			*
 *		2011/11/15		add citations list to list of services			*
 *		2011/12/17		add link to recent updates report				*
 *						identify current database and host				*
 *		2012/01/13		change class names								*
 *		2012/01/24		use default.js for initialization				*
 *		2012/04/07		add help bubble for righttop					*
 *		2012/05/02		open nominal index in separate window/tab		*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/08/23		Add showAddedIndividuals.php					*
 *		2012/10/06		remove obsolete tool							*
 *		2012/10/22		rearrange options in a more functional order	*
 *		2013/02/28		fix error in handling SQL error					*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS for layout instead of tables			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/25		User table management moved to top level of		*
 *						public_html										*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/07		use User::getUsers to get pending count			*
 *		2015/12/23		testing for class Locale failed with autoload	*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/02/04		add link to manage countries list				*
 *		2017/08/16		add link to manage do not merge list			*
 *		2017/10/27		use Template									*
 *		2017/11/21		use RecordSet to get User information			*
 *		2018/01/04		remove Template from template file names		*
 *		2018/01/10		distinguish administrator from contributor		*
 *		2018/02/17		suppress warning on pendingUsers tag			*
 *						issue warning for unsupported language			*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		Open Code														*
 ************************************************************************/

$lang	        = 'en';
foreach($_GET as $key => $value)
{
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'lang':
	    {
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value, 0, 2));
			break;
	    }
	}		// act on specific parameters
}

// for administrator see if there are any new subscribers
$pendingUsers	= 0;
if (canUser('all'))
{		        // user can update any database
	// get information on any pending registrations
	$getParms	    = array('auth'	=> 'pending');
	$users		    = new RecordSet('Users', $getParms);
	$info		    = $users->getInformation();
	$pendingUsers	= $info['count'];
}		        // user can update any database

// determine type of user requesting this dialog
if (canUser('admin'))
	$action		= 'Admin';	// administrator
else
if (canUser('edit'))
	$action		= 'Update';	// contributor
else
	$action		= 'Display';	// casual visitor

// create the Template
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= $tempBase . "Services$action$lang.html";
if (!file_exists($includeSub))
{			// try without language
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub 	= $tempBase . "Services{$action}en.html";
}			// try without language
$gotPage	= $template->includeSub($includeSub,
								'MAIN');

// set global replacements
$template->set('DATABASE_NAME',	$databaseName);
$template->set('SERVER_NAME',	$_SERVER['SERVER_NAME']);

$tag		= $template->getElementById('pendingUsers');
if ($tag)
{
	if ($pendingUsers == 0)
	    $tag->update(null);
	else
	    $tag->update(array('pendingUsers' => $pendingUsers));
}

$template->display();
