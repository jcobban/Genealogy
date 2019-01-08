<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  calcBirthDate.php													*
 *																		*
 *  Display a web page for calculating a date of birth from an event	*
 *  and an age at the time of the event.								*
 *																		*
 *  History:															*
 *		2010/12/12		created											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		use class on <select> to standardize appearance	*
 *		2014/02/10		eliminate use of tables for layout				*
 *		2014/03/06		label class name changed to column1				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2018/02/17		use Template									*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Template.inc";
    require_once __NAMESPACE__ . "/Language.inc";
    require_once __NAMESPACE__ . "/common.inc";

$lang		= 'en';

foreach($_GET as $key => $value)
{
    $fieldLc	= strtolower($key);
    if ($key == 'lang' && strlen($value) == 2)
		$lang	= strtolower($value);
}

    $tempBase		= $document_root . '/templates/';
    $template		= new FtTemplate("${tempBase}page$lang.html");
    $includeSub		= "calcBirthDate$lang.html";
    if (!file_exists($tempBase . $includeSub))
    {
	$language	= new Language(array('code' => $lang));
	$langName	= $language->get('name');
	$nativeName	= $language->get('nativename');
    $sorry      = $language->getSorry();
    $warn       .= str_replace(array('$langName','$nativeName'),
                               array($langName, $nativeName),
                               $sorry);
		$includeSub	= 'calcBirthDateen.html';
    }
    $template->includeSub($tempBase . $includeSub,
						  'MAIN');
    $template->set('LANG',		$lang);
    $template->display();
