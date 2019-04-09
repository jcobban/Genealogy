<?php
namespace Genealogy;
use \PDO;
use \Exception;
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
 *		2010/11/04		use htmlHeader to generate HTML					*
 *						add help page									*
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
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values
$maxcols	    = 8;
$getParms	    = array();
$initial        = null;
$soundex        = null;
$pattern        = null;
$lang           = 'en';
$treename       = '';

// check all passed parameters
foreach($_GET as $key => $value)
{		// loop through all parameters
	switch($key)
	{	// take action depending upon name of parameter
	    case 'initial':
	    {		// initial= specified
			$initial	= ucfirst($value);
			if (strlen($initial) == 0)
			{
			    $msg	.= "Invalid zero length surname prefix.";
			    $getParms['surname']	= '';
			}
			else
			if ($initial == 'M')
            {               // exclude "Mc" names
			    $getParms['surname']	= "^M[^c]";
			}               // exclude "Mc" names
			else
			if (ctype_alpha(substr($initial,0,1)))
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
			$title		= "Surnames with Soundex '$value'";
			$soundex	= substr($value . '000', 0, 4);
			$getParms['soundslike']	= $soundex;
			break;
	    }		// soundex specified

	    case 'pattern':
        {		// soundex specified
            $pattern    = $value;
			$title		= "Surnames with Pattern '$value'";
			$getParms['surname']	= $pattern;
			break;
	    }		// soundex specified

	    case 'maxcols':
	    {		// maximum number of surnames per row
			if (ctype_digit($value))
			    $maxcols	= intval($value);
			break;
        }		// maximum number of surnames per row

        case 'treename':
        {
            $treename       = $value;
        }

        case 'lang':
        {
            if (strlen($value) == 2)
                $lang       = strtolower($value);
        }
	}	// take action depending upon name of parameter
}		// loop through all parameters

$template	        = new FtTemplate( "Surnames$lang.html");

if (!is_null($initial))
{
	if (strlen($initial) == 0)
	    $title	= $template->getElementById('nosurname')->innerHTML();
	else
    if (ctype_alpha($initial))
    {
        $title	= $template->getElementById('starting')->innerHTML();
        $title  = str_replace('$INITIAL', $initial, $title);
    }
	else
	    $title	= $template->getElementById('invalid')->innerHTML();
}
else
if (!is_null($soundex))
{
	$title	= $template->getElementById('soundex')->innerHTML();
    $title  = str_replace('$VALUE', $soundex, $title);
}
else
if (!is_null($pattern))
{
	$title	= $template->getElementById('pattern')->innerHTML();
    $title  = str_replace('$VALUE', $pattern, $title);
}
else
	$title	= $template->getElementById('missing')->innerHTML();
$template->set('TITLE',     $title);

// get the matches
$surnames	= new RecordSet('Surnames', $getParms);
$info   	= $surnames->getInformation();
$count	    = $info['count'];

if ($count > 0)
{                   // display the results
    $curcol		    = 0;
    $row            = $template->getElementById('row');
    $rowHtml        = $row->outerHTML();
    $cell           = $template->getElementById('asurname');
    $cellHtml       = str_replace('id="asurname"', '', $cell->outerHTML());
    $data           = '';
    foreach($surnames as $idnr => $surnamerec)
    {
        $surname	    = $surnamerec->getSurname(); 
        if (substr($surname,0,6) == 'Wifeof' ||
            substr($surname,0,8) == 'Motherof')
            continue;

        if ($curcol == 0)
        {
            $rtemplate  = new Template($rowHtml);
            $rdata      = '';
        }
		$curcol++;

        // link to detailed query action
        $ctemplate      = new Template($cellHtml);
        $usurname	    = rawurlencode($surname);
        $ctemplate->set('SURNAME',      $surname);
        $ctemplate->set('USURNAME',     $usurname);
        $ctemplate->set('LANG',         $lang);
        $rdata          .= $ctemplate->compile();
		if ($curcol == $maxcols)
        {		    // last column, complete row
            $rtemplate->updateTag('asurname', $rdata);
            $data       .= $rtemplate->compile();
		    $curcol	    = 0;
		}		    // last column
    }	            // loop through results

    if ($curcol != 0)
    {		        // incomplete last row
        $rtemplate->updateTag('asurname', $rdata);
        $data           .= $rtemplate->compile();
    }		        // incomplete last row
    $template->updateTag('row',         $data);
    $template->updateTag('nomatches',   null);
}		            // some surnames matched
else
{		            // no matches
    $template->updateTag('namesTable',  null);
}		            // no matches

$template->set("SURNAME",	        $surname);
$template->set('TITLE',	            $title);
$template->set('LANG',			    $lang);
$template->set('CONTACTKEY',		$idnr);
$template->set('CONTACTTABLE',		'Names');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('TREENAME',          $treename);

$template->display();
