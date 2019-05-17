<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BirthDateFixup.php													*
 *																		*
 *  Display the results of a query of the birth registrations table.	*
 *																		*
 *  Parameters:															*
 *		Limit															*
 *		Offset															*
 *		RegYear															*
 *		RegNum															*
 *		Surname															*
 *		GivenNames														*
 *		Occupation														*
 *		Religion														*
 *		FatherName														*
 *		MotherName														*
 *		Place															*
 *		Date															*
 *		SurnameSoundex													*
 *		BYear															*
 *		Range															*
 *		RegDomain														*
 *		RegCounty														*
 *		RegTownship														*
 *																		*
 *  History:															*
 *		2010/08/27		Change to new layout							*
 *						Fix warning on SurnameSoundex					*
 *		2010/10/27		check result for SQL errors						*
 *		2011/01/09		handle request to see all registrations for a	*
 *						year in order by registration number			*
 *						improve separation of PHP and HTML				*
 *		2011/05/13		put out header and error messages				*
 *		2011/05/29		set default for $offset							*
 *						miscellaneous cleanup							*
 *		2011/06/13		syntax error in soundex search					*
 *		2011/11/05		rename to BirthDateFixup.php					*
 *						use <button> instead of link for action			*
 *						support mouseover help							*
 *		2012/03/21		make given names a link to the family tree if	*
 *						the birth registration is referenced			*
 *		2012/03/27		combine surname and given names in report		*
 *		2012/03/30		explicitly specify <thead> and <tbody>			*
 *						shorten column headers							*
 *		2012/05/01		correct row number in empty rows preceding first*
 *						active line.									*
 *						display blank birthplace cell in empty rows		*
 *		2012/05/28		display entries with unknown sex in black		*
 *		2013/02/27		clear message if invoked with no parameters		*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/11/15		handle missing database connection gracefully	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/24		use CSS for layout instead of tables			*
 *						simplify button implementation					*
 *						clean up next and previous links				*
 *						support RegDomain parameter						*
 *		2014/02/10		include overall status and status for year		*
 *						in breadcrumbs if search includes registration	*
 *						year											*
 *						generate valid HTML page on SQL errors			*
 *		2014/04/26		remove use of getCount function					*
 *		2014/05/08		fix bugs introduced by previous change			*
 *		2014/05/15		handle omission of RegNum parameter when		*
 *						updating										*
 *		2014/08/28		add Delete registration button					*
 *		2014/10/11		pass domain name to child dialogs				*
 *						support delete confirmation dialog				*
 *		2014/12/18		generalize for all provinces and move to		*
 *						folder Canada									*
 *		2017/09/12		use set(										*
 *		2018/19/17		use class Template								*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

$cc	        	= 'CA';
$code	        = 'ON';
$country	    = 'Canada';
$domain	        = 'CAON';
$domainName	    = 'Ontario';
$stateName	    = 'Ontario';
$todo	        = 0;
$lang           = 'en';

foreach ($_GET as $key => $value)
{			    // loop through all parameters
	switch(strtolower($key))
	{	    	// act on specific parameters
	    case 'code':
	    {		// state postal abbreviation
			$code		= $value;
			$cc		    = 'CA';
			$domain		= 'CA' . $code;
			break;
	    }		// state postal abbreviation

	    case 'domain':
	    case 'regdomain':
	    {		// state postal abbreviation
			$domain		    = $value;
			$cc		        = substr($domain, 0, 2);
			$code		    = substr($domain, 2);
			break;
	    }		// state postal abbreviation

	    case 'lang':
	    {
			if (strlen($value) >= 2)
			    $lang		= strtolower(substr($value,0,2));
			break;
	    }
	}   		// act on specific parameters
}   			// loop through all parameters

$template		    = new FtTemplate("BirthDateFixup$lang.html");

$domainObj	        = new Domain(array('domain'	    => $domain,
				                       'language'	=> $lang));
$countryObj	        = new Country(array('code' => $cc));
$countryName	    = $countryObj->getName();
$domainName	        = $domainObj->getName(1);
$stateName	        = $domainObj->getName(0);

// count number left to do
$query	            = "SELECT COUNT(*) FROM Births " .
				    	  "WHERE LEFT(B_CalcBirth,4)='0000'" .
				    	  " OR B_CalcBirth='' OR ISNULL(B_CalcBirth) ";
$result	            = $connection->query($query);
if ($result)
{		// success
	$row	        = $result->fetch(PDO::FETCH_NUM);
	$todo	        = $row[0];
	$warn	        .= "<p>" . $todo . " rows left to do</p>\n";
	if ($todo == 0)
	    $msg	    = "No more birth registrations to correct.";
}		// success
else
{		// error performing query
	 $msg	        .= "Record::__construct: '$query', result=" .
							   print_r($stmt->errorInfo(), true);
}		// error performing query

// the hierarchy of URLs to display in the top and bottom of the page
// execute the query
$query	    = "SELECT B_RegDomain, B_RegYear, B_RegNum, " .
					  "B_BirthDate " .
					  "FROM Births " .
					  "WHERE (LEFT(B_CalcBirth,4)='0000' " .
                      " OR B_CalcBirth='' OR ISNULL(B_CalcBirth)) " .
                      " AND B_RegDomain='$domain' " .
					  "LIMIT 1000";
$result	    = $connection->query($query);
if ($result)
{		// success
	$warn	    .= "<p>'" . htmlentities($query) .  "' returns " .
					$result->rowCount() . " rows</p>\n";
}		// success
else
{		// error performing query
	 $msg	        .= "'$query', result=" .
							   print_r($stmt->errorInfo(), true);
}		// error performing query

$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	    	$domain);
$template->set('STATENAME',	    	$stateName);
$template->set('LANG',		    	$lang);
$template->set('CONTACTTABLE',		'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
if ($debug)
    $template->set('DEBUG',		    'Y');
else
    $template->set('DEBUG',		    'N');

$template->display();
