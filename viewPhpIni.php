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
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once('Template.inc');
require_once('common.inc');

$tempBase		    = $document_root . '/templates/';
$template		    = new FtTemplate("${tempBase}page$lang.html");

$filename           = php_ini_loaded_file();
$file               = file_get_contents("./.htaccess");
$file               = "<p" . str_replace("\n","</p>\n<p>", $file) . "</p>\n";
$template->set('MAIN',$file);


$template->display();
