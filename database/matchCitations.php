<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  matchCitations.php													*
 *																		*
 *  This script attempts to match all citations of individuals in the	*
 *  family tree to the specified census page to the appropriate			*
 *  line on the page.  When a unique match is found a link is defined	*
 *  from the census line to the individual in the family tree.			*
 *																		*
 *  History:															*
 *		2012/04/09		created											*
 *		2013/04/20		fix syntax error in search						*
 *						provide proper header and footer				*
 *						support "contribute" button						*
 *		2013/08/25		add support for 1921 census						*
 *		2013/11/26		handle database server failure gracefully		*
 *		2013/11/30		also match individual events for match			*
 *		2013/12/30		use CSS for layout								*
 *						genCensuses.php moved to lower directory		*
 *		2014/01/28		missing global $i caused incorrect HTML			*
 *						and failure to update page form					*
 *		2014/04/10		fix pattern failure for pre-confed censuses		*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/08/24		permit extra blanks in front of the page		*
 *						number in citations to census pages to permit	*
 *						sorting citations in a more natural order		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2017/12/15		use class CensusLine							*
 *		2020/03/13      use FtTemplate::validateLang                    *
 *		2020/05/14      use FtTemplate                                  *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/CensusLine.inc';
    require_once __NAMESPACE__ . '/FtTemplate.inc';
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function checkRow													*
 *																		*
 *  Check one row of the database for matching citations.				*
 *																		*
 *  Input:																*
 *		$row			array returned from database					*
 *		$row[0]			idir											*
 *		$row[1]			surname											*
 *		$row[2]			givenName										*
 *		$row[3]			birthSD											*
 *		$row[4]			sex												*
 *																		*
 *	Returns:															*
 *		HTML output					                					*
 ************************************************************************/
function checkRow($row)
{
    global	$connection;
    global	$debug;
    global	$warn;
    global	$msg;
    global	$template;
    global	$census;	        // census ID 'CCyyyy'
    global	$censusYear;
    global	$table;
    global	$province;
    global	$district;
    global	$subDistrict;
    global	$division;
    global	$page;
    global	$i;

    $idir	        			= $row[0];
    $surname					= $row[1];
    // remove characters from surname that have special meaning
    // to REGEXP or not handled by SOUNDEX
    $psurname					= str_replace(array('?','*','[',']',"'"),
                                              '',
                                              $surname);
    $givenName					= $row[2];
    if ($row[4] == 0)
	    $sexTest				= " AND Sex='M'";
    else
    if ($row[4] == 1)
	    $sexTest				= " AND Sex='F'";
    $birthsd					= $row[3];	// yyyymmdd
    $rxResult					= preg_match('/^[A-Za-z]/', $psurname);
	if (strlen($psurname) > 0 && $rxResult > 0)
	{	                    // surname acceptable to SOUNDEX
	
		// pattern for matching surnames: 1st two characters and last
        if (strlen($psurname) > 3)
        {
		    $first2			    = substr($psurname, 0, 2);
            $last1              = substr($psurname, strlen($psurname) - 1); 
		    $surPattern			= "^$first2.*$last1$";
        }
		else
		    $surPattern			= "^$psurname$";
	
		// pattern for matching given names: 1st two chars anywhere
		if (strlen($givenName) > 2)
		    $partGiven			= substr($givenName, 0, 2);
		else
		    $partGiven			= $givenName;
		$rxResult			    = preg_match('/^[A-Z]+$/i', $partGiven);
		if ($rxResult == 0) 
		    $partGiven			= ".";	// match anything
	
		if ($censusYear < 1867)
		{
		    $provinceW			= "Province=:province AND ";
		    $sqlParms			= array('province'	=> $province);
		}
		else
		{
		    $provinceW			= '';
		    $sqlParms			= array();
		}
	
		// the following looks for lines in the specified page
		// where:
		//	1. The surname matches by SOUNDEX and failing
		//	   that the first 2 characters and the last character
		//	   of the surname match.
		//	2. The first 2 characters of the given name occur
		//	   somewhere in the given name in the census page.
		//	3. The birth year is within 3 years of the birth
		//	   year in the family tree.
		//	4. Matches on sex
		$match	= "SELECT Line, Surname, GivenNames, BYear FROM $table " .
						"WHERE $provinceW District=:district AND " .
								"SubDistrict=:subDistrict AND " .
								"Division=:division AND " .
								"Page=:page AND " .
								"(SurnameSoundex=LEFT(SOUNDEX(:surname),4) OR ".
								"Surname REGEXP :surPattern) AND " .
								"GivenNames REGEXP :partGiven AND " .
								"ABS(:birthYear - BYear) < 4 " .
								 $sexTest;
		$sqlParms['district']		= $district;
		$sqlParms['subDistrict']	= $subDistrict;
		$sqlParms['division']		= $division;
		$sqlParms['page']		    = $page;
		$sqlParms['surname']		= $psurname;
		$sqlParms['surPattern']		= $surPattern;
		$sqlParms['partGiven']		= $partGiven;
		$sqlParms['birthYear']		= $birthYear; 
	
		$stmt		                = $connection->prepare($match);
		$matchText	                = debugPrepQuery($match, $sqlParms);
		if ($stmt->execute($sqlParms))
		{		                // successful query
		    $mResult	            = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    if ($debug)
	            $warn	            .= "<p>matchCitations.php: " . __LINE__ . 
	                                    " $matchText</p>\n";
	
		    // action depends upon how many matches the above
		    // pattern returned
		    if (count($mResult) > 1 && strlen($givenName) > 2)
		    {		            // more than one match
				if ($debug)
				    $warn	        .= '<p>number of rows in result=' .
								        count($mResult) . "</p>\n";
				// make given name test more restrictive
				// look for match on 4 characters instead of just 2
				// for example on "John" rather than "Jo"
				if (strlen($givenName) > 4)
				    $givPattern	    = substr($givenName, 0, 4);
				else
				    $givPattern	    = $givenName;
				foreach($mResult as $mRow)
				{		        // loop through matches
				    $rxResult	    = preg_match("/$givPattern/i",
									        	 $mRow['givennames']);
				    if ($rxResult == 1)
						break;
				    $lastRow	    = $mRow;
				}		        // loop through matches
				if (!$mRow)
				    $mRow		    = $lastRow;
		    }		            // more than one match
		    else
		    if (count($mResult) > 0)
		    {		            // at most one match
				$mRow		        = $mResult[0];
		    }		            // at most one match
		    else
				$mRow		        = null;
	
		    if ($mRow)
		    {
                $line	            = $mRow['line'];
				$cenParms	        = array('census'	=> $census,
											'district'	=> $district,
											'subdistrict'	=> $subDistrict,
											'division'	=> $division,
											'page'		=> $page,
											'line'		=> $line);
				$censusLine	        = new CensusLine($cenParms);
		        $birthYear			= floor($birthsd / 10000);
				$censusLine->set('idir', $idir);
				$censusLine->save(false);
                $rtemplate          = new Template($template['birthMatch']->outerHTML);
                $updateParms        = array('i'         => $i,
                                            'idir'      => $idir,
                                            'lang'      => $lang,
                                            'surname'   => $surname,
                                            'givenName' => $givenName,
                                            'birthYear' => $birthYear,
                                            'line'      => $line,
                                            'mRow'      => $mRow);
                $rtemplate['birthMatch']->update($updateParms);
                return $rtemplate->compile();
		    }
		}		                // successful query
		else
		{		                // error on query
		    $msg	.= "'" . htmlentities($match) . "': " .
                        print_r($connection->errorInfo(),true);
            return '';
		}		                // error on query
    }			                // surname acceptable to SOUNDEX
}		// function checkRow

// check authorization

$census		                    = null;
$censusYear		                = null;
$table		                    = null;
$province		                = null;
$district		                = null;
$subDistrict	                = null;
$division		                = null;
$page		                    = null;
$lang		                    = 'en';

// process the parameters
if (isset($_GET) && count($_GET) > 0)
{                           // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                       // loop through all parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n";
        switch(strtolower($key))
		{	                // act on each specific parameter
		    case 'census':
		    case 'censusid':
		    {	            // census identifier: XXyyyy
				$census		    = $value;
				$censusYear	    = substr($census, 2);
				$table		    = 'Census' . $censusYear;
				switch($censusYear)
				{
				    case '1851':
				    {
						$idsr		= 11;
						$preConfed	= true;
						break;
				    }
	
				    case '1861':
				    {
						$idsr		= 12;
						$preConfed	= true;
						break;
				    }
	
				    case '1871':
				    {
						$idsr		= 13;
						$preConfed	= false;
						break;
				    }
	
				    case '1881':
				    {
						$idsr		= 16;
						$preConfed	= false;
						break;
				    }
	
				    case '1891':
				    {
						$idsr		= 17;
						$preConfed	= false;
						break;
				    }
	
				    case '1901':
				    {
						$idsr		= 19;
						$preConfed	= false;
						break;
				    }
	
				    case '1906':
				    {
						$idsr		= 224;
						$preConfed	= false;
						break;
				    }
	
				    case '1911':
				    {
						$idsr		= 271;
						$preConfed	= false;
						break;
				    }
	
				    case '1916':
				    {
						$idsr		= 389;
						$preConfed	= false;
						break;
				    } 
	
				    case '1921':
				    {
						$idsr		= 466;
						$preConfed	= false;
						break;
				    }
	
				    default:
				    {
						$msg	.= "Invalid census year: $censusYear. ";
						break;
				    }
	
				}
				break;
		    }	            // census identifier
	
		    case 'province':
		    {	            // province code (pre-confederation)
				$province	    = $value;
				break;
		    }	            // province code
	
		    case 'district':
		    {	            // district identifier
				$district	    = $value;
				$rxResult	    = preg_match("/^[0-9.]+$/", $district);
				if ($rxResult != 1)
				    $msg	    .= "District value '$district' is invalid. ";
				break;
		    }	            // district identifier
	
		    case 'subdistrict':
		    {	            // subDistrict identifier
				$subDistrict	= $value;
				break;
		    }	            // subDistrict identifier
	
		    case 'division':
		    {	            // division identifier
				$division	    = $value;
				break;
		    }	            // division identifier
	
		    case 'page':
		    {	            // page number
				$page		    = $value;
				$rxResult	    = preg_match("/^[0-9]+$/", $page);
				if ($rxResult != 1)
				    $msg	    .= "Page number '$page' is invalid. ";
				break;
		    }	            // page number
	
		    case 'lang':
		    {
		        $lang           = FtTemplate::validateLang($value);
				break;
		    }
	
		    case 'debug':
		    {
				break;
		    }
	
		    default:
		    {
				$msg	.= "Unexpected parameter: $key=$value. ";
				break;
		    }
		}	                // act on each specific parameter
	}		                // loop through all parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}                           // invoked by method=get

$template                       = new FtTemplate("matchCitations$lang.html");

if (!canUser('edit'))
	$msg	    .= 'You are not authorized to perform this function. ';
if (is_null($census))
	$msg		.= "Missing mandatory parameter census. ";

$dName	                        = 'Not Found';
$subdName	                    = 'Not Found';

// if there are no errors, perform the function
if (strlen($msg) == 0)
{		                    // no errors
	$subDist	= new SubDistrict(array('SD_Census' => $census,
									'SD_DistId' => $district,
									'SD_Id'	    => $subDistrict,
									'SD_Div'    => $division));;
	$dName		= $subDist->get('d_name');
	$subdName	= $subDist->get('sd_name');

	// establish pattern for matching citations to the specified page
	if ($preConfed)
	{	                    // pre-confederation census
	    if (strlen($division) > 0)
			$pattern	= "'^$province, dist $district .* subdist $subDistrict .* div $division page +$page$'";
	    else
			$pattern	= "'^$province, dist $district .* subdist $subDistrict .* page +$page$'";
	}	                    // pre-confederation census
	else
	{	                    // post-confederation census
	    if (strlen($division) > 0)
			$pattern	= "'dist $district .* subdist $subDistrict .* div $division page +$page$'";
	    else
			$pattern	= "'dist $district .* subdist $subDistrict .* page +$page$'";
	}	                    // post-confederation census

	// query to locate all citations from individuals in the family
	// tree to this particular census page
	// Getting the names from tblNX ensures we can compare to both
	// the maiden name and married name
	// But the sex is only recorded in tblIR and since we have to
	// add that to the join anyway, it is safer to get the birth date
	// from there, rather than depend upon the fact that contrary
	// to good database design the birthSD is replicated in tblNX
	$iquery	= "SELECT DISTINCT IDIME, tblNX.Surname, tblNX.GivenName,
							   tblIR.BirthSD, tblIR.Gender 
					  FROM tblSX
							JOIN tblNX ON tblNX.IDIR=tblSX.IDIME
							JOIN tblIR ON tblIR.IDIR=tblSX.IDIME
					  WHERE tblSX.IDSR=$idsr AND
							tblSX.Type=2 AND
							tblSX.SrcDetail REGEXP $pattern";

	$stmt		                = $connection->query($iquery);
	if ($stmt)
	{		                // successful query
	    $iresult	            = $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
			$warn	.= "<p>matchCitations.php: " . __LINE__ . ' '. htmlspecialchars($iquery) . "</p>\n";

	    $equery	= "SELECT DISTINCT tblER.IDIR, tblNX.Surname, tblNX.GivenName,
							       tblIR.BirthSD, tblIR.Gender 
					      FROM tblSX 
							    JOIN tblER ON tblER.IDER=tblSX.IDIME
							    JOIN tblIR ON tblIR.IDIR=tblER.IDIR
							    JOIN tblNX ON tblNX.IDIR=tblER.IDIR
					      WHERE tblSX.IDSR=$idsr AND
							    tblSX.Type=30 AND
							    tblSX.SrcDetail REGEXP $pattern";

	    $stmt	= $connection->query($equery);
	    if ($stmt)
	    {		            // query successful
			$eresult	= $stmt->fetchAll(PDO::FETCH_NUM);
			if ($debug)
			    $warn	.= "<p>matchCitations.php: " . __LINE__ . ' '. htmlspecialchars($equery) . "</p>\n";
	    }		            // query successful
	    else
	    {		            // error on query
			$msg	.= $equery . ': ' .
					   print_r($connection->errorInfo(),true);
	    }		            // error on query
	}		                // successful query
	else
	{		                // error on query
	    $msg	.= "'$iquery': " .
					   print_r($connection->errorInfo(),true);
	}		                // error on query
}		                    // no errors
else
    $template['results']->update(null);

$template->display();

?>	
<div class='body'>
  <h1>Census of Canada: Match Citations
<span class='right'>
	<a href='matchCitationsHelpen.html' target='help'>? Help</a>
</span>
<div style='clear: both;'></div>
  </h1>
<?php
$action	= 'None';

// print trace
showTrace();

// print error messages if any
if (strlen($msg) > 0)
{		// errors in parameters
?>
<p class='message'><?php print $msg; ?></p>
<?php
	$msg	= '';
}		// errors in parameters
else
{		// no errors, continue with request
	print "<p>Checking births:</p>\n";
	$i		= 0;
	foreach($iresult as $row)
	{	// loop through all results
	    checkRow($row);
	    // print trace
    showTrace();

	    if (strlen($msg) > 0)
	    {		// trace information present
?>
<p class='message'><?php print $msg; ?></p>
<?php
			$msg	= '';
	    }		// trace information present
	    $i++;
	}	// loop through all results
	print "<p>Checking events:</p>\n";
	$i		= 0;
	foreach($eresult as $row)
	{	// loop through all results
	    checkRow($row);
	    $i++;
	}	// loop through all results
}		// no errors
?>
  </div>
<?php
pageBot();
?>
  <!-- balloons to pop up when mouse moves over forward and back links -->
  <div class='popup' id='mouseprenpprev'>
<p class='label'>
	Go to Page <?php print $page - 1; ?>&nbsp;
</p>
  </div>
  <div class='popup' id='mouseprenpnext'>
<p class='label'>
	Go to Page <?php print $page + 1; ?>&nbsp;
</p>
  </div>
  <div class='popup' id='mousepostnpprev'>
<p class='label'>
	Go to Page <?php print $page - 1; ?>&nbsp;
</p>
  </div>
  <div class='popup' id='mousepostnpnext'>
<p class='label'>
	Go to Page <?php print $page + 1; ?>&nbsp;
</p>
  </div>
</body>
</html>
