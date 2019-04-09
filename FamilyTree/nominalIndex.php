<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  nominalIndex.php													*
 *																		*
 *  Primary dialog for searching for individuals in the family tree.	*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		birthmin		individuals born in or after this year			*
 *		birthmax		individuals born in or before this year			*
 *		treename		sub-tree to limit search to						*
 *		name			name matches or after "surname, givennames" 	*
 *		incmarried		if not empty include married names in search	*
 *		includeparents	if not empty include parents names in response	*
 *		includespouse	if not empty include spouses names in response	*
 *		sex				limit search by sex								*
 *																		*
 *  History (as nominalIndex.html):										*
 *		2010/08/23		change to new standard layout					*
 *		2010/09/11		selection list for individuals					*
 *		2010/11/04		revert to HTML 4.1 to satisfy IE				*
 *						add help link									*
 *		2011/02/22		improve separation of HTML & Javascript			*
 *		2011/11/26		add checkbox to include married names			*
 *		2011/12/27		display loading indicator while waiting for		*
 *						response from server for list of names			*
 *		2013/04/05		add Facebook Like button						*
 *		2013/04/08		renamed to nominalIndex.php						*
 *		2013/04/26		change text on heading link						*
 *		2013/05/28		improve layout of alphabetical links			*
 *						help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/12/08		replace table layout with CSS layout			*
 *		2014/01/23		add birth year range							*
 *		2014/02/18		begin birth year set to -10000 to include		*
 *						individuals with no birth date					*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/11/25		add option for selecting by sex					*
 *						add options for including parents and spouses	*
 *						widen main select to allow room for above		*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *						missing <body> tag								*
 *		2015/02/02		add "button" to add an unrelated individual		*
 *						put include options on the same line			*
 *						change style class of main selection			*
 *						spread out first letter options to reduce height*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/11		add tree name field on form						*
 *						use global $familyTreeCookie					*
 *		2015/08/23		cookie didn't work, add parameter treename=		*
 *		2016/02/06		use showTrace									*
 *		2016/12/31		document new support for multiple given names	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/16		renamed to nominalIndex.php						*
 *		2017/11/13		use class Template								*
 *		2018/01/04		remove Template from template file names		*
 *		2019/02/19      use new FtTemplate constructor                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

$birthmin		= '-10000';
$birthmax		= '3000';
$treeName		= '';
$lang	        = 'en';

foreach($_GET as $key => $value)
{				// loop through parameters
	switch (strtolower($key))
	{			// act on specific parameters
	    case 'birthmin':
	    {
			$birthmin	= intval($value);
			break;
	    }

	    case 'birthmax':
	    {
			$birthmax	= intval($value);
			break;
	    }

	    case 'treename':
	    {
			$treeName	= $value;
			break;
	    }

	    case 'lang':
	    {		// language choice
			$lang		= strtolower(substr($value,0,2));
			break;
	    }		// language choice

	}			// act on specific parameters
}				// loop through parameters

if ($treeName == '')
	$treeNameText	= "South-Western Ontario";
else
	$treeNameText	= $treeName;
$nameSet		= new RecordSet('Names');
$treeNames		= $nameSet->getDistinct('treename');
$trees		= array();
foreach($treeNames as $atree)
{
	if ($atree == '')
	    $trees[]	= array('code'		=> '',
    						'atree'		=> 'South-Western Ontario',
	    					'selected'	=> "selected='selected'");
	else
	    $trees[]	= array('code'		=> $atree,
		    				'atree'		=> $atree,
			    			'selected'	=> '');
}

$template	        = new FtTemplate("nominalIndex$lang.html");

$template->set('TREENAME',		$treeNameText);
$template->set('birthmin',		$birthmin);
$template->set('birthmax',		$birthmax);

if (!canUser('edit'))
{
	$template->updateTag('addUnrelated', null);
	$template->updateTag('createNewTree', null);
}

$template->updateTag('treeOpt', $trees);

$template->display();
