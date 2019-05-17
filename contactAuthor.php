<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  contactAuthor.php													*
 *																		*
 *  Implement contacting the author of a page by using the internal		*
 *  blog support														*
 *																		*
 *  Parameters:															*
 *		idir			unique key of associated record instance	    *
 *		tablename		database table the key refers to				*
 *		subject			information about the referrer			    	*
 *		text			additional text to include in message			*
 *																		*
 *  History:															*
 *		2014/03/27		use common layout routines						*
 *						use HTML 4 features, such as <label>			*
 *		2015/02/05		add accessKey attributes to form elements		*
 *						change text in button to "Send"					*
 *						correct class name from RecOwners to RecOwner	*
 *		2015/03/05		separate initialization logic and HTML			*
 *		2015/03/25		top page of hierarchy is now genealogy.php		*
 *		2015/05/26		add optional text to initialize message			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/12/30		fix conflict with autoload						*
 *		2016/01/19		add id to debug trace							*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *						use preferred form of new LegacyIndiv			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/17		use class UserSet instead of RecOwner			*
 *						correct placement of page top					*
 *		2018/09/07		default template namemisspelled					*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

if (!canUser('blog'))
	$userid 	= '';
$recordid	    = 0;
$tableName	    = 'tblIR';
$about	        = '';
$text	        = '';
$lang	        = 'en';
$person	        = null;

foreach($_GET as $name => $value)
{
	switch(strtolower($name))
	{
	    case 'id':
	    case 'idir':
	    {
			$recordid		= $value;
			break;
	    }		// key value

	    case 'tablename':
	    {
			$info		= Record::getInformation($value);
			if ($info)
			    $tableName	= $info['table'];
			break;
	    }		// table name

	    case 'subject':
	    {
			$about		= "About: $value\n";
			break;
	    }		// table name

	    case 'text':
	    {
			$text		= $value;
			break;
	    }		// table name

	    case 'lang':
	    {
			if (strlen($value) >= 2)
			    $lang	= strtolower(substr($value,0,2));
			break;
	    }		// table name

	}		// act on specific keys
}			// loop through all parameters

// take any table specific action
switch($tableName)
{			// act on specific table names
	case 'tblIR':
	{
	    if (strlen($about) == 0)
	    {
			$person		= new Person(array('idir' => $recordid));
			if ($person->isExisting())
			    $about	= "About: " . $person->getName() .
					  " (IDIR=$recordid)\n";
	    }
	}
}			// act on specific table names

$template		    = new FtTemplate("ContactAuthor$lang.html");

// get a list of all the owners of the current record
// this includes all of the administrators
if (strlen($recordid) > 0 && strlen($tableName) > 0)
	$contacts	= new UserSet(array('recordid'	=> $recordid,
						            'table'	    => $tableName));
else
	$contacts	= new UserSet(array('auth'	    => 'yes'));

$contactIds         = '';
$comma	            = '';
foreach ($contacts as $ic => $contact)
{
	$contactIds	    .= $comma . $contact->get('id');
	$comma		    = ',';
}

$user	            = new User(array("username" => $userid));
$email	            = $user->get('email');
$template->set('USERID',		$userid);
$template->set('EMAIL',	    	$email);
$template->set('LANG',	    	$lang);
$template->set('ABOUT',	    	$about);
$template->set('TEXT',	    	$text);
$template->set('TABLENAME',		$tableName);
$template->set('CONTACTIDS',	$contactIds);

// for registered users the E-Mail address is a private attribute of
// the User record, do not expose it to prying eyes
$email		= null;
if ($userid && strlen($userid) > 0)
{			// have userid of current user
	$user		= new User(array('username'	=> $userid));
	$email		= $user->get('email');
	if ($user->isExisting())
	    $template->updateTag('promptforemail', null);
	else
	    $template->updateTag('hiddenemail', null);
}			// have userid of current user
else
	$template->updateTag('hiddenemail', null);

$template->display();
