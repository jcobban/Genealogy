<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaDoQuery.php														*
 *																		*
 *  Display a report of individuals whose marriage matches the			*
 *  requested pattern.  This is invoked by method='get' from			*
 *  OcfaDoQuery.html.													*
 *																		*
 *  Parameters:															*
 *		Count															*
 *		Offset															*
 *		Surname															*
 *		GivenNames														*
 *		SurnameSoundex													*
 *		County															*
 *		Township														*
 *		Cemetery														*
 *																		*
 *  History:															*
 *		2011/03/20		created											*
 *		2013/01/20		use urlencode on URI parameters					*
 *		2013/04/04		replace deprecated calls to doQuery				*
 *		2013/05/23		shorten title									*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/05		explicitly order response						*
 *		2013/06/29		update not implemented yet						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/27		use CSS to layout <h1> and pagination controls	*
 *		2015/05/01		PHP print statements were corrupted				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/25		replace ereg with preg_match					*
 *		2019/05/01      update link to query to include parameters      *
 *		                passed to this script                           *
 *		                use standard element ids for top and bottom     *
 *		                page scrolling so PgUp and PgDn work            *
 *		2021/02/21      protect against XSS                             *
 *		                improve parameter checking                      *
 *		                get message texts from template                 *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Ocfa.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/Surname.inc";
require_once __NAMESPACE__ . "/common.inc";

// action taken depends upon whether the user is authorized to
// update the database

// default values
$offset						= 0;
$offsettext                 = null;
$limit						= 20;
$limittext                  = null;
$county         			= '';
$countytext                 = null;
$township       			= '';
$townshiptext               = null;
$cemetery       			= '';
$cemeterytext               = null;
$givenname     			    = '';
$giventext                  = null;
$surname        			= '';
$surnametext                = null;
$surnamesoundex        		= 'N';
$soundex        			= false;
$soundextext                = null;
$reference        			= false;
$referencetext              = null;
$matches        			= array();
$totalrows      			= 0;
$getParms       			= array();
$lang						= 'en';

// validate all parameters passed to the server
// first extract the values of all supplied parameters
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			        // loop through all parameters
    $safevalue  = htmlspecialchars($value);
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
	switch(strtolower($key))
	{		        // switch on parameter name
	    case 'count':
	    case 'limit':
        {		    // limit number of rows returned
            if (is_int($value) || ctype_digit($value))
            {
                $limit	                    = $value;
                $getParms['limit']          = $value;
            }
            else
                $limittext                  = $safevalue;
			break;
	    }		    // limit number of rows returned

	    case 'offset':
	    {		    // starting offset
            if (is_int($value) ||ctype_digit($value))
            {
			    $offset	                    = $value;
                $getParms['offset']         = $value;
            }
            else
                $offsettext                 = $safevalue;
			break;
	    }		    // starting offset

	    case 'surname':
        {
            if (preg_match("/^[a-zA-Z ']*$/", $value) > 0)
            {
                $surname                    = $value;
                if (strlen($value) > 0)
                    $getParms['surname']    = $value;
            }
            else
                $surnametext                = $safevalue;
			break;
	    }

	    case 'givenname':
	    case 'givennames':
	    {		// match anywhere in string
            if (preg_match("/^[a-zA-Z ']*$/", $value) > 0)
            {
                $givenname                  = $value;
                if (strlen($value) > 0)
                    $getParms['givenname']  = $value;
            }
            else
                $giventext                  = $safevalue;
			break;
	    }		// match in string

	    case 'cemetery':
        {		// match anywhere in string
            $result     = preg_match("/^[-a-zA-Z0-9#()\/,.? ']*$/", $value);
            if ($result > 0)
            {
                $cemetery                   = $value;
                if (strlen($value) > 0)
                    $getParms['cemetery']   = $value;
            }
            else
                $cemeterytext               = $safevalue;
			break;
	    }		// match in string

	    case 'surnamesoundex':
	    case 'loosesurname':
        {		// handled under Surname
            if (preg_match("/^[a-zA-Z0-9]*$/", $value) > 0)
            {
                $surnamesoundex             = $value;
                if (strtoupper($value) != 'N')
                    $getParms['loosesurname']   = true;
            }
            else
                $soundextext                = $safevalue;
			break;
	    }		// handled under Surname

	    case 'county':
        {		// exact match on field in table
            $result     = preg_match("/^[-a-zA-Z0-9#()\/,.? ']*$/", $value);
            if ($result > 0)
            {
                $county                     = $value;
                if (strlen($value) > 0)
                    $getParms['county']     = "^$value$";
            }
            else
                $countytext                 = $safevalue;
			break;
	    }		// exact match on field in table

	    case 'township':
        {		// exact match on field in table
            if (preg_match("/^[-a-zA-Z0-9#()\/,.? ']*$/", $value) > 0)
            {
                $township                   = $value;
                if (strlen($value) > 0)
                    $getParms['township']   = "^$value$";
            }
            else
                $townshiptext               = $safevalue;
			break;
	    }		// exact match on field in table

	    case 'lang':
        {           // not used in search
            $lang                       = FtTemplate::validateLang($value);
			break;
	    }           // not used in search

	    case 'reference':
	    {		    // default match on field in table
            if (preg_match("/^[-a-zA-Z0-9#()\/,.? ']*$/", $value))
            {
                $getParms[$key]         = $value;
            }
            else
                $referencetext          = $safevalue;
			break;
        }		    // default match on field in table

	}		        // switch on parameter name
}			        // loop through all parameters
if ($debug)
    $warn   .= $parmsText . "</table>\n";

$template           = new FtTemplate("OcfaDoQuery$lang.html");

if (is_string($offsettext))
	$warn		.= $template['offsettext']->replace('$text', $offsettext);
if (is_string($limittext))
	$warn		.= $template['limittext']->replace('$text', $limittext);
if (is_string($countytext))
	$msg		.= $template['countytext']->replace('$text', $countytext);
if (is_string($townshiptext))
	$msg		.= $template['townshiptext']->replace('$text', $townshiptext);
if (is_string($cemeterytext))
	$msg		.= $template['cemeterytext']->replace('$text', $cemeterytext);
if (is_string($giventext))
	$msg		.= $template['giventext']->replace('$text', $giventext);
if (is_string($surnametext))
	$msg		.= $template['surnametext']->replace('$text', $surnametext);
if (is_string($soundextext))
	$msg		.= $template['soundextext']->replace('$text', $soundextext);
if (is_string($referencetext))
	$msg		.= $template['referencetext']->replace('$text', $referencetext);

$template->set('COUNTY',		    	$county);
$template->set('TOWNSHIP',		    	$township);
$template->set('CEMETERY',		    	$cemetery);
$template->set('GIVENNAMES',			$givenname);
$template->set('SURNAME',		    	$surname);
$template->set('SURNAMESOUNDEX',		$surnamesoundex);
$template->set('LANG',			        $lang);
$template->set('LIMIT',			        $limit);
$template->set('OFFSET1',			    $offset + 1);

if (strlen($msg) == 0)
{
	// execute the query
    $matches        = new RecordSet('Ocfa', $getParms);
    $info           = $matches->getInformation();
    $totalrows      = $info['count'];
    $query          = $info['query'];
    if ($debug)
        $warn       .= "<p>query='$query'</p>\n";
    $count          = $matches->count();
}
else
{
    $totalrows      = 0;
    $count          = 0;
}
$formatter          = $template->getFormatter();
$template->set('TOTALROWS',		    	$formatter->format($totalrows));

$offsetlast         = $offset + $count;
if ($offsetlast > $totalrows)
    $offsetlast     = $totalrows;
$template->set('OFFSETLAST',			$offsetlast);
$prevoffset         = $offset - $limit;
$nextoffset         = $offset + $limit;
$template->set('PREVOFFSET',			$prevoffset);
$template->set('NEXTOFFSET',			$nextoffset);

if ($prevoffset < 0)
{
    $template['topPrev']->update(null);
    $template['botPrev']->update(null);
}
if ($nextoffset >= $totalrows)
{
    $template['topNext']->update(null);
    $template['botNext']->update(null);
}

if ($count > 0)
{               // have at least one response
    $template['nomatches']->update(null);
    $rowelt         = $template['Row$recordindex'];
    if (is_null($rowelt))
        $template->getDocument()->printTag();
	$rowtemplate    = $rowelt->outerHTML;
	$data           = '';
	$class          = 'odd';
    foreach($matches as $entry)
    {
        $entry['class']     = $class;
        $ttemplate          = new \Templating\Template($rowtemplate);
        $ttemplate['Row$recordindex']->update($entry);
        $data               .= $ttemplate->compile();
        if ($class == 'odd')
            $class          = 'even';
        else
            $class          = 'odd';
    }           // loop through rows of response
    $rowelt->update($data);
}               // have at least one response
else
{
    $template['topBrowse']->update(null);
    $template['dataTable']->update(null);
    $template['botBrowse']->update(null);
}
$template->display();
