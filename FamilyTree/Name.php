<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Name.php															*
 *																		*
 *  Display a web page containing details of a particular Name record	*
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																		*
 *  Parameters:															*
 *		idnx			Unique numeric identifier of the name record.	*
 *																		*
 *  History:															*
 *		2015/05/04		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/04      use class Template                              *
 *		2018/12/26      ignore field IDNR in Name record                *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// defaults
$idnx	            = null;         // numeric key of record
$name	            = null;         // instance of class Name
$lang	            = 'en';

// process parameters
foreach($_GET as $key => $value)
{			// loop through all parameters
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'idnx':
	    case 'id':
        {
            if (is_string($value))
            {
                $value          = trim($value);
			    if (strlen($value) > 0 &&
                    ctype_digit($value))
                    $value      = intval($value);
            }

            if (is_int($value) && $value > 0)
			    $idnx	        = $value;
			else
			    $msg        .= "IDNX value '$value' is not a positive number. ";
			break;
	    }

	    case 'debug':
	    {		// handled by common.inc
			break;
	    }		// debug

	    case 'lang':
        {		// handled by common.inc
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
			break;
	    }		// debug

	    default:
	    {
			$warn	.= "<p>Unexpected parameter $key='$value'.</p>\n";
			break;
	    }
	}		// act on specific parameters
}			// loop through all parameters

// get the requested name record
if (!is_null($idnx))
{		// parameter present
    $name		= new Name(array('idnx' => $idnx));

    // action depends upon whether the user is authorized to update
    // this specific record
    if ($name->isExisting() && $name->isOwner())
        $action         = 'Update';
    else
        $action         = 'Display';
}
else
{
    $name               = new Name(array('idnx' => 0));
    $action             = 'Display';
}

// get the template
$tempBase	        = $document_root . '/templates/';
$template	        = new FtTemplate("${tempBase}page$lang.html");
$includeSub	        = "Name" . $action . $lang . '.html';
if (!file_exists($tempBase . $includeSub))
{
	$language	    = new Language(array('code' => $lang));
	$langName	    = $language->get('name');
	$nativeName	    = $language->get('nativename');
    $sorry          = $language->getSorry();
    $warn           .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	    = "Name" . $action . "en.html";
}
$template->includeSub($tempBase . $includeSub,
                      'MAIN');

// get the requested name record
if (!is_null($idnx) && $name->isExisting())
{		// parameter present
	$surname		    = $name->get('surname');
	$surnameRec			= new Surname(array('surname' => $surname));
	$idnr				= $surnameRec->get('idnr');
	$idir				= $name->get('idir');
	$person				= new Person(array('idir' => $idir));
	$tblirName	        = $person->getName(Person::NAME_INCLUDE_DATES);
	$template->set('IDNX',		        $idnx);
	$template->set('IDNR',		        $idnr);
	$template->set('IDIR',		        $idir);
	$template->set('TBLIRNAME',		    $tblirName);
	switch($person->getGender())
	{
	    case Person::MALE:
	    {
			$sex	= 'male';
			break;
	    }
	
	    case Person::FEMALE:
	    {
			$sex	= 'female';
			break;
	    }
	
	    default:
	    {
			$sex	= 'other';
			break;
	    }
	
	}
	
	$surname				= $name->get('surname');
	$soundslike				= $name->get('soundslike');
	$givenname				= $name->get('givenname');
	$nameUri                = "$surname, $givenname";
	$prefix					= $name->get('prefix');
	$nametitle				= $name->get('title');
	$gener				    = $name->get('gender');
	$userref				= $name->get('userref');
	$order					= $name->get('order');
	$marriednamecreatedby	= $name->get('marriednamecreatedby');
	$birthsd				= $name->get('birthsd');
	$preferredaka			= $name->get('preferredaka');
	$akanote				= $name->get('akanote');
	$idmr					= $name->get('marriednamemaridid');
	$srchtag				= $name->get('srchtag');
	$qstag					= $name->get('qstag');
	
	$template->set('SEX',		    	    $sex);
	$template->set('NAME',		    	    $name->getName());
	$template->set('NAMEURI',		    	$nameUri);
	$template->set('SURNAME',		    	$surname);
	$template->set('SOUNDSLIKE',			$soundslike);
	$template->set('GIVENNAME',		    	$givenname);
	$template->set('PREFIX',		    	$prefix);
	$template->set('NAMETITLE',			    $nametitle);
	$template->set('TREENAME',		    	$person->getTreeName());
	$template->set('USERREF',			    $userref);
	$template->set('ORDER',			        $order);
	$template->set('MARRIEDNAMECREATEDBY',	$marriednamecreatedby);
	$template->set('BIRTHSD',			    $birthsd);
	$template->set('PREFERREDAKA',			$preferredaka);
	$template->set('AKANOTE',			    $akanote);
	$template->set('IDMR',			        $idmr);
	$template->set('SRCHTAG',			    $srchtag);
	$template->set('QSTAG',			        $qstag);
	
	if ($order < 0)
	{               // married name
	    $template->set('ORDERMARRIEDCLASS',     'inline');
	    $template->set('ORDERPRIMECLASS',       'none');
	    $template->set('ORDERAKACLASS',         'none');
	    for($i = 0; $i <= 2; $i++)
	    {
	        if ($i == $marriednamecreatedby)
	            $template->set("MNSELECTED$i",  'selected="selected"');
	        else
	            $template->set("MNSELECTED$i",  '');
	    }
	    $template->updateTag('preferredakaRow', null);
	    $template->updateTag('akanoteRow', null);
	}               // married name
	else
	if ($order == 0)
	{               // primary name
	    $template->set('ORDERMARRIEDCLASS',     'none');
	    $template->set('ORDERPRIMECLASS',       'inline');
	    $template->set('ORDERAKACLASS',         'none');
	    $template->updateTag('marriednamecreatedbyRow', null);
	    $template->updateTag('marriednamemarididRow', null);
	    $template->updateTag('preferredakaRow', null);
	    $template->updateTag('akanoteRow', null);
	}               // primary name
	else
	{               // also known as
	    $template->set('ORDERMARRIEDCLASS',     'none');
	    $template->set('ORDERPRIMECLASS',       'none');
	    $template->set('ORDERAKACLASS',         'inline');
	    $template->updateTag('marriednamecreatedbyRow', null);
	    $template->updateTag('marriednamemarididRow', null);
	}               // also known as
	
	if ($idmr > 0)
	{
	    $family	= new Family(array('idmr' => $idmr));
	    $template->set('FAMILYNAME',      $family->getName());
	}
	else
	{
	    $template->updateTag('familyRow',       null);
	}
}		// IDNX present and valid
else
{		// IDNX missing or invalid
	$msg	            .= 'Parameter idnx or name missing or invalid. ';
    $action             = 'Display';
	$template->set('IDNX',		        $idnx);
	$template->set('IDNR',		            1);
	$template->set('IDIR',		            0);
	$template->set('SEX',		    	    '');
	$template->set('NAME',		    	    'IDNX Missing or Invalid');
	$template->set('NAMEURI',		    	'');
	$template->set('SURNAME',		    	'');
	$template->set('SOUNDSLIKE',			'');
	$template->set('GIVENNAME',		    	'');
	$template->set('PREFIX',		    	'');
	$template->set('NAMETITLE',			    '');
	$template->set('TREENAME',		    	'');
	$template->set('USERREF',			    '');
}		// idnx missing or invalid

$template->display();

