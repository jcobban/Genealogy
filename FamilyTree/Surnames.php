<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  Surnames.php														*
 *																		*
 *  Display a web page containing all of the surnames starting with a	*
 *  given letter or sharing a Soundex code.								*
 *																		*
 *  Parameters (passed by Method='GET'):								*
 *		initial			return surnames which start with a string		*
 *		soundex			return surnames matching a SOUNDEX code			*
 *		soundslike		return surnames matching a SOUNDEX code			*
 *		pattern			return surnames matching a regular expression	*
 *		maxcols			maximum number of columns of surnames to show	*
 *																		*
 *  History:															*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		add help page link          					*
 *		2011/10/31		permit clicking with mouse anywhere in table	*
 *						cell											*
 *		2011/11/15		clean up parameter processing					*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/17		use functions pageTop and pageBot to standardize*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace table with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/01/07		change require to require_once					*
 *		2015/05/17		use class Surname::getSurnames					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2016/11/12		pad short soundex values to avoid error			*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/09/05		use new parameter 'pattern' to getSurnames		*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/10/31      use class Template                              *
 *		2018/12/26      did not accept surnames starting with O'        *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2020/02/23      $surname and $idnr were not initialized if      *
 *		                there were no surname matches                   *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2021/09/15      automatically delete unused entries             *
 *      2022/06/04      use negative lookahead REGEX syntax             *
 *                      hide "Wifeof" surname entries                   *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values
$maxcols	    			= 8;
$getParms	    			= array();
$initial        			= null;
$soundex        			= null;
$pattern        			= null;
$surname        			= null;
$idnr           			= null;
$lang           			= 'en';
$treename       			= '';

// check all passed parameters
foreach($_GET as $key => $value)
{		            // loop through all parameters
	switch($key)
	{	            // take action depending upon name of parameter
	    case 'initial':
	    {		    // initial= specified
            $initial	                = mb_strtoupper(mb_substr($value,0,1)) . mb_substr($value, 1);
			if (strlen($initial) == 0)
			{
			    $msg	.= "Invalid zero length surname prefix.";
			    $getParms['surname']	= '';
			}
			else
			if ($initial == 'F')
            {               // exclude "Mc" names
			    $getParms['surname']	= "^F(?!irstwifeof)";
			}               // exclude "Mc" names
			else
			if ($initial == 'M')
            {               // exclude "Mc" names
			    $getParms['surname']	= "^M(?!(c|ac))";
			}               // exclude "Mc" names
			else
			if ($initial == 'Mc')
            {               // exclude "Mc" names
			    $getParms['surname']	= "^M(c|ac)";
			}               // exclude "Mc" names
			else
			if ($initial == 'W')
            {               // exclude "Mc" names
			    $getParms['surname']	= "^W(?!ifeof)";
			}               // exclude "Mc" names
			else
			if (preg_match('/^\w/u', $initial))
            {               // surname that starts with a letter
			    $getParms['surname']	= "^$initial";
			}               // surname that starts with a letter
			else
			{               // surname that doesn't start with a letter
			    $getParms['surname']	= "^[^a-zA-Z]";
			}               // surname that doesn't start with a letter
			break;
	    }		            // initial= specified

	    case 'soundex':
	    case 'soundslike':
        {		// soundex specified
			$soundex	                = substr($value . '000', 0, 4);
			$getParms['soundslike']	    = $soundex;
			break;
	    }		// soundex specified

	    case 'pattern':
        {		// soundex specified
            $pattern                    = $value;
			$getParms['surname']	    = $pattern;
			break;
	    }		// soundex specified

	    case 'maxcols':
	    {		// maximum number of surnames per row
			if (ctype_digit($value))
                $maxcols	            = intval($value);
            if ($maxcols> 9)
                $maxcols                = 9;
			break;
        }		// maximum number of surnames per row

        case 'treename':
        {
            $treename                   = $value;
			break;
        }

        case 'lang':
        {
            $lang       = FtTemplate::validateLang($value);
			break;
        }
	}	// take action depending upon name of parameter
}		// loop through all parameters

$template	            = new FtTemplate( "Surnames$lang.html");

if (!is_null($initial))
{
	if (strlen($initial) == 0)
	    $title	        = $template['nosurname']->innerHTML();
	else
	if (preg_match('/^\w/u', $initial))
    {
        $title	        = $template['starting']->innerHTML();
        $title          = str_replace('$INITIAL', $initial, $title);
    }
	else
	    $title	        = $template['invalid']->innerHTML();
}
else
if (!is_null($soundex))
{
	$title	            = $template['soundex']->innerHTML();
    $title              = str_replace('$VALUE', 
                                      htmlspecialchars($soundex), 
                                      $title);
}
else
if (!is_null($pattern))
{
	$title	            = $template['pattern']->innerHTML();
    $title              = str_replace('$VALUE', 
                                      htmlspecialchars($pattern), 
                                      $title);
}
else
{
    $title	            = $template['missing']->innerHTML();
    $msg                .= "$title. ";
}
$template->set('TITLE',     $title);

// get the matches
if ($debug)
    $warn           .= "<p>\$getParms=" . var_export($getParms, true);
$surnames	                = new RecordSet('Surnames', $getParms);
$info   	                = $surnames->getInformation();
$count	                    = $info['count'];
$template->set('MAXCOLS',           $maxcols);
if ($debug)
    $warn           .= "<p>count=$count, command={$info['query']}</p>\n";

if ($count > 0)
{                   // display the results
    if ($count > 500)
    {
        preg_match('/(WHERE .*) ORDER/', $info['query'], $matches);
        $where          = $matches[1];
        $query          = "SELECT (LEFT(`surname`,2)) as `leading`,count(*) FROM tblNR $where GROUP BY `leading`";
        if ($stmt = $connection->query($query))
        {
            if ($debug)
                $warn       .= "<p>'$query'</p>\n";
            $results    = $stmt->fetchAll();
            foreach($results as $row)
            {
                $leading        = $row[0];
                $lcount         = $row[1];
                $warn   .= "<p><a href='/FamilyTree/Surnames.php?initial=$leading'>Surnames starting with '$leading' $lcount</a></p>\n";
                if ($lcount > 1000)
                {
                    $query2          = "SELECT (LEFT(`surname`,3)) as `leading`,count(*) FROM tblNR WHERE LEFT(`surname`,2)='$leading' GROUP BY `leading`";
                    if ($stmt2 = $connection->query($query2))
                    {
                        $warn       .= "<p style=\"padding-left: 5px\">'$query2'</p>\n";
                        $results2   = $stmt2->fetchAll();
            foreach($results2 as $row)
            {
                $leading        = $row[0];
                $l2count        = $row[1];
                $warn   .= "<p style=\"padding-left: 5px\"><a href='/FamilyTree/Surnames.php?initial=$leading'>Surnames starting with '$leading' $l2count</a></p>\n";
            }
                    }
                }
            }
        }
        else
        {
            $msg   .= "'$query' failed " . print_r($connection->errorInfo(), true) . ".\n";
        }
    }
    $cell                   = $template['asurname'];
    $cellHtml               = str_replace('id="asurname"', '', $cell->outerHTML());
    $data                   = '';
    foreach($surnames as $idnr => $surnamerec)
    {
        $surname	        = $surnamerec->getSurname(); 
        if (substr($surname,0,6) == 'Wifeof' ||
            substr($surname,0,8) == 'Motherof')
            continue;

        if ($surnamerec['pattern'] == '' && $surnamerec->getCount() == 0)
            $surnamerec->delete();
        else
        {           // link to detailed query action
	        $ctemplate      = new Template($cellHtml);
	        $usurname	    = rawurlencode($surname);
	        $ctemplate->set('SURNAME',      $surname);
	        $ctemplate->set('USURNAME',     $usurname);
	        $ctemplate->set('LANG',         $lang);
	        $data           .= $ctemplate->compile();
        }           // link to detailed query action
    }	            // loop through results

    $template['asurname']->update($data);
    $template['nomatches']->update(null);
}		            // some surnames matched
else
{		            // no matches
    $surname            = 'No Match';
    $idnr               = 0;
    $template['namesTable']->update(null);
}		            // no matches

$template->set("SURNAME",	        $surname);
$template->set('CONTACTKEY',		$idnr);
$template->set('TITLE',	            $title);
$template->set('LANG',			    $lang);
$template->set('CONTACTTABLE',		'Names');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('TREENAME',          htmlspecialchars($treename));

$template->display();
