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
 *		2019/05/18      get search name from cookie or parameters       *
 *		                and display in input field                      *
 *      2019/11/17      move CSS to <head>                              *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$birthmin		= '-10000';
$birthmax		= '3000';
$name		    = '';
$treeName		= '';
$lang	        = 'en';

// check familyTree cookie
foreach($familyTreeCookie as $key => $value)
{
    switch(strtolower($key))
    {
        case 'treename':
        {
            $treeName           = $value;
            break;
        }

        case 'idir':
        {
            if (ctype_digit($value))
            {
                $lastPerson     = new Person(array('idir' => $value));
                if ($lastPerson->isExisting())
                {
                    $name       = $lastPerson->getName(Person::NAME_SURNAME_FIRST);
                }
            }
            break;
        }
    }               // act on specific attributes
}                   // loop through attributes

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{				// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch (strtolower($key))
		{			// act on specific parameters
		    case 'birthmin':
		    {
				$birthmin	    = intval($value);
				break;
		    }
	
		    case 'birthmax':
		    {
				$birthmax	    = intval($value);
				break;
		    }
	
		    case 'name':
            {
                if (strlen($value) > 0)
                    $name	        = $value;
				break;
		    }
	
		    case 'treename':
		    {
				$treeName	    = $value;
				break;
		    }
	
		    case 'lang':
            {		// language choice
                $lang       = FtTemplate::validateLang($value);
				break;
		    }		// language choice
	
		}			// act on specific parameters
	}				// loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

$template	        = new FtTemplate("nominalIndex$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'nominalIndex'));

if ($treeName == '')
	$treeNameText	= "South-Western Ontario";
else
	$treeNameText	= $treeName;
$nameSet		    = new RecordSet('Names');
$treeNames		    = $nameSet->getDistinct('treename');
$trees		        = array();
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

$template->set('NAME',		    $name);
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
