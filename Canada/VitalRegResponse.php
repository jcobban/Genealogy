<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  VitalRegResponse.php												*
 *																		*
 *  Display the results of a query of the vital record registrations	*
 *  tables.																*
 *  This script is intended to be invoked by a script VitalRegQuery.php *
 *  and to search all of the vital registration tables.                 *
 *																		*
 *  Parameters:															*
 *		Limit															*
 *		Offset															*
 *		Surname															*
 *		GivenNames														*
 *		Occupation														*
 *		Religion														*
 *		FatherName														*
 *		MotherName														*
 *		Place															*
 *		Date															*
 *		SurnameSoundex													*
 *		BirthDate														*
 *		Range															*
 *		RegDomain														*
 *		RegCounty														*
 *		RegTownship														*
 *																		*
 *  History:															*
 *		2015/01/24		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/07		use class Country								*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2019/02/21      use new FtTemplate constructor                  *
 *		2019/12/13      remove B_ prefix from file names                *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// variables for constructing the SQL statement
$sel				= '';		// WHERE expression
$m_sel				= '';		// WHERE expression
$d_sel				= '';		// WHERE expression
$limitopt			= '';		// limit on which rows to return
$birthDate			= null;		// birth date
$range				= 1;		// birth date range in years
$surname			= null;
$surnameSoundex		= false;
$and				= '';		// logical and operator in WHERE
$npuri				= 'VitalRegResponse.php';// for next and previous links
$npand				= '?';		// adding parms to $npuri
$npprev				= '';		// previous selection
$npnext				= '';		// next selection
$limit				= 20;
$domain				= 'CAON';
$domaintext         = null;
$code				= 'ON';
$domainName			= 'Ontario';
$cc					= 'CA';
$countryName		= 'Canada';
$lang               = 'en';
$regYear			= 0;
$regNum				= 0;
$offset				= 0;
$sqlParms			= array();

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                            "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $valuetext      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$valuetext</td></tr>\n";
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }

    		case 'regdomain':
    		case 'domain':
    		{		// administrative domain
                if (preg_match('/^[a-zA-Z}{4,5}$/', $value))
                    $domain		        = $value;
                else
                    $domaintext         = htmlspecialchars($value);
    		    break;
    		}		// administrative domain

    		case 'count':
    		case 'limit':
            {		// number of rows to display at a time
                if (ctype_digit($value))
    			    $limit	            = $value;
    		    break;
    		}		// number of rows to display at a time

    		case 'offset':
    		{		// starting offset
                if (ctype_digit($offset))
    			    $offset	            = $value;
    		    break;
    		}		// starting offset

    		case 'sex':
    		case 'infrel':
    		{		// selection lists
    		    if (strlen($value) > 0 && $value != '?')
    		    {
    				$sel	    .= $and	. '' . $key . '=?' ;
    				array_push($sqlParms, $value);
    				$npuri	    .= "{$npand}{$key}={$value}";
    				$npand	    = '&amp;'; 
    				$and	    = ' AND ';
    		    }   
    		    break;
    		}		// selection lists

    		case 'surname':
    		{
                if (strlen($value) > 0)
                {
    		        $surname	= $value;
    		        $npuri	    .= "{$npand}{$key}={$value}";
                    $npand	    = '&amp;';
                }
    		    break;
    		}

    		case 'givennames':
    		case 'birthplace':
    		case 'phys':
    		case 'informant':
    		case 'fathername':
    		case 'fatherocc':
    		case 'mothername':
    		case 'motherocc':
    		case 'husbandname':
    		{		// match anywhere in string
                if (strlen($value) > 0)
                {
	    		    $sel	    .= $and . '' . $key . ' REGEXP ?' ;
	    		    array_push($sqlParms, $value);
	    		    $npuri	    .= "{$npand}{$key}={$value}";
	    		    $npand	    = '&amp;'; 
	    		    $and	    = ' AND ';
                }
    		    break;
    		}		// match in string

    		case 'birthdate':
    		{
                if (strlen($value) > 0)
                {
	    		    $birthDate	= $value;
	    		    $npuri	    .= "{$npand}{$key}={$value}";
	    		    $npand  	= '&amp;'; 
                }
    		    break;
    		}		// birth date

    		case 'range':
    		{
                if (strlen($value) > 0)
                {
	    		    $range	    = $value;
	    		    $npuri	    .= "{$npand}{$key}={$value}";
	    		    $npand	    = '&amp;'; 
                }
    		    break;
    		}		// birth date range in years

    		case 'surnamesoundex':
    		{		// soundex flag
                if (strlen($value) > 0)
                {
    				$surnameSoundex	= true;
    		        $npuri	    .= "{$npand}{$key}={$value}";
    		        $npand	    = '&amp;'; 
                }
    		    break;
    		}		// soundex flag

    		case 'debug':
    		{		// handled by common.inc
    		    break;
    		}		// debug

    		default:
    		{		// ordinary parameter
                if (strlen($value) > 0)
                {
	    		    $sel	    .= $and . '' . $key . '=?';
	    		    array_push($sqlParms, $value);
	    		    $npuri	    .= "{$npand}{$key}={$value}";
	    		    $npand	    = '&amp;'; 
	    		    $and	    = ' AND ';
                }
    		    break;
    		}		// ordinary parameter
        }		    // switch on parameter name
    }			    // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			        // parameters passed

if (canUser('edit'))
    $action         = 'Update';
else
    $action         = 'Display';

// start the template
$template		    = new FtTemplate("VitalRegResponse$action$lang.html");

// validation of parameters is left until after the template
// is allocated to get context specific message text from template

if (is_string($domaintext))
{
    $text           = $template['invalidDomain']->outerHTML;
    $warn           .= str_replace('$domain', $domaintext, $text);
}

$domainObj          = new Domain(array('domain'       => $domain,
                                       'language'     => 'en'));
$cc                 = $domainObj['cc'];
$code               = $domainObj['code'];
$domainName         = $domainObj['name'];
$countryObj         = $domainObj->getCountry();
$countryName        = $countryObj->getName();
if ($domainObj->isExisting())
{
    $sel            .= $and . 'RegDomain=?';
    array_push($sqlParms, $domain);
    $npuri          .= "$npand$key=$domain";
    $npand          = '&amp;';
    $and            = ' AND ';
}
else
{
    $text           = $template['unsupportedDomain']->innerHTML;
    $msg            .= str_replace('$domain', $domain, $text);
}

// surname match depends upon both Surname and SurnameSoundex
if (!is_null($surname))
{			// surname specified
    $sel	    .= $and;
    if (preg_match("/[.+*^$]/", $surname))
    {		// match pattern
        $sel	.= 'Surname REGEXP ?'; 
        array_push($sqlParms, $value);
    }		// match pattern
    else
    if ($surnameSoundex)
    {		// match soundex
        $sel	.= 'SurnameSoundex = LEFT(SOUNDEX(?),4)';
        array_push($sqlParms, $value);
    }		// match soundex
    else
    {		// match exact
        $sel	.= 'Surname=?';
        array_push($sqlParms, $value);
    }		// match exact
        
    $and	    = ' AND ';
}			// surname specified

// birth date match depends upon both BirthDate and Range
if (!is_null($birthDate))
{			// birth date specified
    $date	    = new LegacyDate(' ' . $birthDate);
    $y	        = $date->getYear();
    $m	        = $date->getMonth();
    $d	        = $date->getDay();
    $sel	    .= $and . "ABS(DATEDIFF(CalcBirth, '$y-$m-$d')) < " .
    				  "(365 * $range)";
    $and	    = ' AND ';
}			// birth date specified

// LIMIT expression depends upon both Limit and Offset parameters
if ($offset > 0)
{			// starting offset specified
    $limitopt	= " LIMIT $limit OFFSET $offset";
    $tmp		= $offset - $limit;
    if ($tmp < 0)
        $npprev	= "";	// no previous link
    else
        $npprev	= "Limit=$limit&Offset=$tmp";
    $tmp		= $offset + $limit;
    $npnext		= "Limit=$limit&Offset=$tmp";
}			// starting offset specified
else
{			// starting offset omitted
    $limitopt	= " LIMIT $limit";
    $npprev		= '';
    $npnext		= "Limit=$limit&Offset=$limit";
}			// starting offset omitted

if (strlen($sel) == 0)
    $msg	    .= $template['missingParameters']->innerHTML;
    
// action taken depends upon whether the user is authorized to
// update the database
$action	= 'Details';
$title	= $domainName . ": Vital Statistics Registration Query";
$template->set('cc',		        $cc);
$template->set('code',		        $code);
$template->set('domain',		    $domain);
$template->set('domainName',		$domainName);
$template->set('countryObj',		$countryObj);
$template->set('countryName',		$countryName);

// if no error messages display the results of the query
if (strlen($msg) == 0)
{		// no error messages
    // execute the query
    $cntQuery	= "SELECT COUNT(*) FROM Births WHERE $sel";
    $stmt		= $connection->prepare($cntQuery);
    if ($stmt->execute($sqlParms))
    {		// success
        // get the value of COUNT(*)
        $row 	= $stmt->fetch(PDO::FETCH_NUM);
        $totalrows	= $row[0];

        // execute the query
        $query	= "SELECT RegDomain AS RegDomain, " .
    					"RegYear AS RegYear, " .
    					"RegNum AS RegNum, " .
    					"Surname AS Surname, " .
    					"GivenNames AS GivenNames, " .
    					"Sex AS Sex, " .
    					"BirthDate AS BirthDate, " .
    					"BirthPlace AS BirthPlace, " .
    					"RegTownship AS RegTownship, " .
    					"IDIR AS IDIR, " .
    					"CalcBirth AS CalcBirth " .
    				  "FROM Births " .
    				  "WHERE $sel " .
    				  "ORDER BY Surname, GivenNames, CalcBirth " .
    				  $limitopt;
        $stmt	= $connection->prepare($query);
        if ($stmt->execute($sqlParms))
        {		// success
    		$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
    		$numRows	= count($result);
    		if ($debug)
    		    print "<p>'" . htmlentities($query) .  "' returns " .
    				$numRows . " rows</p>\n";

    		if ($offset + $numRows >= $totalrows && $regNum == 0)
    		    $npnext		= '';
        }		// success
        else
        {		// error performing query
    		 $msg	.= htmlentities($query) . ": " .
    				   print_r($stmt->errorInfo(), true);
        }		// error performing query
    }		// success
    else
    {		// error performing query
        $msg	.= htmlentities($cntQuery) . ": " .
    				   print_r($stmt->errorInfo, true);
    }		// error performing query
}		// no error messages

// add initialization of substitutions
$template->display();
