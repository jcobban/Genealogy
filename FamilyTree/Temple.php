<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Temple.php															*
 *																		*
 *  Display a web page containing details of an particular LDS Temple	*
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																		*
 *  Parameters:															*
 *      code            identify temple by its string identifier        *
 *		idtr			Unique numeric identifier of the temple.	    *
 *		                Deprecated in favor of `code`.                  *
 *						For backwards compatibility this can be			*
 *						specified using the 'id' parameter.				*
 *																		*
 *  History:															*
 *		2012/12/06		created											*
 *		2013/02/23		implement new record format for tblTR			*
 *						display start and end dates in human form and 	*
 *						accept human dates as input						*
 *		2013/05/23		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						add IDTR value to e-mail subject				*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace table with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with a temple						    *
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/06/27		display start and end dates as text strings		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/12		use get( and set(								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/25      use Template                                    *
 *		                use code to identify Temple in pref to IDTR     *
 *		                add street address                              *
 *		                merge in update logic from updateTemple.php     *
 *		2020/02/16      after successful update with no messages to     *
 *		                display to user, return to display of Temples   *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// process parameters
$idtr                   = null;
$code                   = null;
$lang                   = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of temple
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
			case 'code':
            {
                if (strlen($value) > 2 && strlen($value) < 6)
                    $code               = strtoupper($value);
                break;
            }

			case 'idtr':
            {
                if (ctype_digit($value))
                    $idtr               = (int)$value;
                break;
            }

			case 'lang':
            {
                $lang                   = FtTemplate::validateLang($value);
                break;
            }

        }		    // act on specific parameter
    }	            // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of temple
else
if (count($_POST) > 0)
{		            // invoked by submit to update temple
    $newvalues          = array();
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc        = strtolower($key);
	    switch($fieldLc)
	    {		    // act on specific parameter
			case 'code':
            {
                if (strlen($value) > 2 && strlen($value) < 6)
                    $code               = strtoupper($value);
                break;
            }

			case 'idtr':
            {
                if (ctype_digit($value))
                    $idtr               = (int)$value;
                break;
            }

			case 'lang':
            {
                $lang                   = FtTemplate::validateLang($value);
                break;
            }

		    case 'temple':
		    case 'address':
		    case 'templestart':
		    case 'templeend':
		    case 'used':
		    case 'tag1':
		    case 'qstag':
            {
                $newvalues[$fieldLc]    = $value;
                break;
            }

	    }		    // act on specific parameter
    }	            // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update temple

// action depends upon whether the user is authorized to update
if (canUser('edit'))
	$action             = 'Update';
else
    $action             = 'Display';

$template               = new FtTemplate("Temple$action$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'Temple'));

$template->set('LANG',                      $lang);
if ($debug)
    $template->set('DEBUG',                 'Y');
else
    $template->set('DEBUG',                 'N');

// get the requested temple
if ($code != null)
    $temple	                = new Temple(array('code' => $code));
else
if ($idtr != null)
{		// IDTR present and valid
    $temple	                = new Temple(array('idtr' => $idtr));
}
else
    $temple                 = null;

if (is_object($temple) && $temple->isExisting())
{
    if (count($_POST) > 0 && canUser('edit'))
    {                       // apply updates
        foreach($newvalues as $fieldLc => $value)
            $temple[$fieldLc]               = $value;
        $temple->save(false);
        if ($debug)
            $warn       .= "<p>" . $temple->getLastSqlCmd() . "</p>\n";
        else
        {
            $name           = $temple['name'];
            if (strlen($name) > 1)
                $prefix     = substr($name, 0, 1);
            else
                $prefix     = $name;
            header( "Location: Temples.php?pattern=^$prefix&lang=$lang" );
            exit ;
        }
    }                       // apply updates

    $template->set('NAME',	                $temple['Name']);
    $template->set('CODE',	                $temple['Code']);
    $template->set('CODE2',	                $temple['Code2']);
    $template->set('ADDRESS',               $temple['address']);

    // interpret start and end dates
    $templeStartDate	    = $temple->getStartDate();
    $template->set('TEMPLESTART',	        $templeStartDate->toString());

    $templeEndDate	        = $temple->getEndDate();
    $template->set('TEMPLEEND',		        $templeEndDate->toString());

	if ($temple->get('used'))
	    $template->set('USEDCHECKED',	'checked="checked"'); 
	else
	    $template->set('USEDCHECKED',	'');
	if ($temple->get('qstag'))
	    $template->set('QSCHECKED',	    'checked="checked"'); 
	else
	    $template->set('QSCHECKED',	    '');
	if ($temple->get('tag1'))
	    $template->set('TAG1CHECKED',	'checked="checked"'); 
	else
	    $template->set('TAG1CHECKED',	'');
}		// present and valid
else
{		// code missing or invalid
    if ($code)
        $template->set('NAME',	            "Undefined Temple '$code'");
    else
    if ($idtr)
        $template->set('NAME',	            "Undefined Temple $idtr");
    else
        $template->set('NAME',	            'MISSING');
    $msg	.= 'Parameter `code` missing or invalid. ';
    $template['locForm']->update(null);
}		// code missing or invalid

$template->display();
