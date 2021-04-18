<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountryNamesEdit.php												*
 *																		*
 *  Display form for editting information about country names			*
 *																		*
 *  History:															*
 *		2017/10/27		created											*
 *		2018/01/03		remove Template from template names				*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2021/01/15      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/CountryName.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms				= array();
$cc			    		= 'CA';
$cctext                 = null;
$code                   = 'en';
$codetext               = null;
$countryName			= 'Canada';
$lang		    		= 'en';
$article				= '';
$possessive				= 'of ';

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{			// method='get'
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                          "<table class='summary'>\n" .
                          "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {			// loop through parameters
        $valuetext      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$valuetext</td></tr>\n";
	    switch(strtolower($key))
	    {                   // act on specific parameters
			case 'cc':
            {		        // country code
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $cc				= $value;
                else
                    $cctext         = htmlspecialchars($value);
			    break;
            }		        // country code

			case 'lang':
			{			    // language code
                $lang               = FtTemplate::validateLang($value);
			    break;
			}			    // language code

	    }			        // act on specific parameters
	}			        // loop through parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";

}				// method='get'
else
if (isset($_POST) && count($_POST) > 0)
{		// when submit button is clicked invoked by method='post'
    $parmsText          = "<p class='label'>\$_POST</p>\n" .
                            "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                "<th class='colhead'>value</th></tr>\n";
	$countryNameObj		= null;
	foreach($_POST as $key => $value)
	{
        $valuetext      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$valuetext</td></tr>\n";
	    if (preg_match('/^([a-zA-Z@#$%_]+)(\d*)$/', $key, $matches))
	    {			// ends with row number
			$column	    = strtolower($matches[1]);
			$code	    = $matches[2];
	    }			// ends with row number
        else
        {
            $column	    = strtolower($key);
            $code       = '';
        }

	    switch($column)
	    {
			case 'cc':
			{
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $cc				= strtoupper($value);
                else
                    $cctext         = htmlspecialchars($value);
			    break;
			}		// country code

			case 'lang':
			{
                $lang       = FtTemplate::validateLang($value);
			    break;
			}

			case 'code':
            {                       // language code for name
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
                {
                    $code			= strtolower($value);
	                if (canUser('edit') &&
	                    $countryNameObj && 
	                    $countryNameObj->get('name') != '')
	                {                   // save last instance
	                    $updated    = $countryNameObj->save(null);
				        if ($updated && $debug)
	                        $warn	.= "<p>CountryNamesEdit.php: " . __LINE__ .
	                            " " . $countryNameObj->getLastSqlCmd() . "</p>\n";
	                }                   // save last instance
                    $countryNameObj =
                        new CountryName(array('Code3166_1' => $cc,
					                    	  'Code639_1'  => $code));
			        if ($debug)
					    $warn	.= "<p>CountryNamesEdit.php: " . __LINE__ .
                    " created new CountryName(array('Code3166_1' => '$cc', 'Code639_1'  => '$code')), </p>\n";
                }
                else
                    $codetext       = htmlspecialchars($value);
			    break;
			}                       // language code for name

			case 'name':
			{
			    if (strlen($value) > 0 && $value != '*')
					$countryNameObj->set('name', $value);
			    else
			    {
					if ($debug)
					    $warn	.= "<p>Deleted name '" .
						   $countryNameObj->get('name') . 
						   "' for country code '$cc' " .
						   "for language code '$code'</p>\n";
					$countryNameObj->set('name', '');
					$countryNameObj->delete(false);
			    }
			    break;
			}

			case 'article':
			{
			    if (substr($value, -1) != ' ' && substr($value, -1) != "'")
					$value	= $value . ' ';
			    $countryNameObj->set('article', $value);
			    if ($debug)
				    $warn	.= "<p>CountryNamesEdit.php: " . __LINE__ .
			    " \$countryNameObj->set('article', $value)</p>\n";
			    break;
			}

			case 'possessive':
			{
			    if (substr($value, -1) != ' ' && substr($value, -1) != "'")
					$value	= $value . ' ';
			    $countryNameObj->set('possessive', $value);
			    if ($debug)
				    $warn	.= "<p>CountryNamesEdit.php: " . __LINE__ .
			    " \$countryNameObj->set('possessive', $value)</p>\n";
			    break;
			}

			case 'debug':
			{
			    break;
			}		// debug handled by common code

	    }			// check supported parameters
	}			// loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";

	// apply any updates to the last instance of CountryName
	if ($countryNameObj)
	{
	    $countryNameObj->save(null);
	}
}		// when submit button is clicked invoked by method='post'

if (canUser('edit'))
{
	$action		= 'Update';
}
else
{
	$action		= 'Display';
}

$template		= new FtTemplate("CountryNamesEdit$action$lang.html");

if (is_string($cctext))
{
    $text               = $template['ccInvalid']->innerHTML;
    $msg                .= str_replace('$cc', $cctext, $text);
}

if (is_string($codetext))
{
    $text               = $template['codeInvalid']->innerHTML;
    $msg                .= str_replace('$code', $codetext, $text);
}

$getParms['code3166_1']	= "^$cc$";
$country		        = new Country(array('code' => $cc));
$countryNameObj		    = new CountryName(array('cc'	=> $country,
						        		        'lang'	=> $lang));
$countryName		    = $countryNameObj->getName();
$article		        = $countryNameObj->get('article');
$possessive		        = $countryNameObj->get('possessive');

$template->set('LANG',		        $lang);
$template->set('CC',		        $cc);
$template->set('COUNTRYNAME',	    $countryName);
$template->set('CONTACTTABLE',	    'CountryNames');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));

if (strlen($msg) == 0)
{			// no errors detected
	$enName		    = new CountryName(array('code3166_1'	=> $country,
					            			'code639_1'	    => $lang));
    $names		    = new RecordSet('CountryNames', $getParms);
	$information	= $names->getInformation();
    $rownum         = 1;
	if (count($names) == 0)
	    $names->push('en', $enName);
    foreach($names as $record)
    {
        $record['rownum']   = $rownum;
        $rownum++;
    }


	$template->set('ARTICLE',		    $article);
	$template->set('POSSESSIVE',	    $possessive);
}			// no errors detected
else
{
    $template['countryForm']->update(null);
}

$template['Row$rownum']->update($names);
$template->display();
