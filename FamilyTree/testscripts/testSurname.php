<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testSurname.php														*
 *																		*
 *  Test driver for the Surname class									*
 * 																		*
 *  History: 															*
 *		2019/01/04		create											*
 *																		*
 *  Copyright 2019 James A. Cobban										*
 ************************************************************************/
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

$idnr       	    = 0;
$name	            = '';
$surname	        = '';
$soundslike		    = '';
$pattern	    	= '';
$notes		    	= '';
$used		    	= 1;
$tag1		    	= 0;
$qstag		    	= 0;
$lang	    	    = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
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
			case 'surname':
            {
                if (strlen($value) >= 2)
                    $surname        = $value;
                break;
            }

			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value,0,2));
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

// create instance of Template
$tempBase		    = $document_root . '/templates/';
$template		    = new FtTemplate("${tempBase}page$lang.html");
$includeSub		    = "testscripts/testSurname$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language   	= new Language(array('code' => $lang));
	$langName   	= $language->get('name');
	$nativeName	    = $language->get('nativename');
	$sorry  	    = $language->getSorry();
    $warn   	    .= str_replace(array('$langName','$nativeName'),
                                   array($langName, $nativeName),
                                   $sorry);
	$includeSub	    = 'testscripts/testSurnameen.html';
}
$template->includeSub($tempBase . $includeSub,
                      'MAIN');

$surnameRec         = new Surname(array('surname'   => $surname));

// define substitution values
$template->set('LANG',		    $lang);
if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');
$idnr		    = $surnameRec['idnr'];
$name		    = $surnameRec['surname'];
$surname		= $surnameRec['surname'];
$soundslike		= $surnameRec['soundslike'];
$pattern		= $surnameRec['pattern'];
$notes		    = $surnameRec['notes'];
$used		    = $surnameRec['used'];
$tag1		    = $surnameRec['tag1'];
$qstag		    = $surnameRec['qstag'];
$template->set('IDNR',	        $idnr);
$template->set('NAME',	        $name);
$template->set('SURNAME',	    $surname);
$template->set('SOUNDSLIKE',	$soundslike);
$template->set('PATTERN',	    $pattern);
$template->set('NOTES',	        $notes);
$template->set('USED',	        $used);
$template->set('TAG1',	        $tag1);
$template->set('QSTAG',	        $qstag);

$template->display();
