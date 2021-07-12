<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  addCitJSON.php														*
 *																		*
 *  Add a source citation and return XML so that this document can be	*
 *  invoked by JavaScript code using AJAX.  A number of parameters can	*
 *  be appended to the URL to customize the citation:					*
 *																		*
 *  One of the following parameters must be passed to identify			*
 *  the database record containing the fact that is beingxxx documented	*
 *  by the citation														*
 *		idir		the IDIR value of an Person Record					*
 *		idmr		the IDMR value of a Family Record					*
 *		ider		the IDER value of an Event Record					*
 *		idcr		the IDCR value of a Child Record					*
 *		idnx		the IDNX value of an Alternate Name record			*
 *  or																	*
 *		idime		generic key of a record								*
 *																		*
 *  The following parameters must also be passed:						*
 *		idsr		the IDSR value of the associated Source Record.		*
 *		formname    the name attribute of the <form> element in the		*
 *				    invoking page										*
 *		row		    the row index number identifying the HTML elements	*
 *			    	in the invoking web page.  This is required so		*
 *			    	that the Javascript function receiving the XML		*
 *			    	result can update the invoking web page             *
 *			    	appropriately.						                *
 *																		*
 *  The following parameters are all optional:							*
 *		type		specify the specific fact within the associated		*
 *				    data record that is documented by this citation.  	*
 *				    If omitted is set to Citation::STYPE_MAR (20)		*
 *		page		text to identify the page within the source	    	*
 *				    identified by parameter 'idsr'.						*
 *		text		text quoted from the source document				*
 *		note		comments about the citation							*
 *		surety		confidence level in the citation					*
 *				    Default: 3 "Almost Certain Conclusion"				*
 *																		*
 *  History (as addCitXml.php):											*
 *		2010/08/09		Change to use POST instead of GET				*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/11		return name of source							*
 *		2010/10/17		return idsx value of created record				*
 *						return formname parameter as attribute			*
 *		2010/10/21		use Citation and LegacyCitationList				*
 *						classes											*
 *						to access the database rather than SQL commands	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2011/01/25		escape XML entities in response					*
 *		2011/10/02		change name of class LegacyCitationList			*
 *						clean up parameter handling						*
 *		2012/01/13		change class names								*
 *		2012/03/28		for birth citation to Ontario Birth				*
 *						Registrations,									*
 *						death citation to Ontario Death					*
 *						Registrations,									*
 *						and marriage citation to Ontario Marriage		*
 *						Registrations, where the form of the detail		*
 *						info is "yyyy-nnnnn"							*
 *						update the appropriate record(s) in the Births,	*
 *						Deaths, or MarriageIndi tables to point at the	*
 *						record in the tblIR table.						*
 *		2012/04/01		for birth citations to 1901 Census of Canada	*
 *						try to match the corresponding line in the		*
 *						census table and define a link back to the		*
 *						family tree										*
 *		2012/04/05		support birth citations to 1871 through 1916	*
 *						census											*
 *						support alternate names for citations to census	*
 *		2012/05/11		escape SQL command to avoid XML parsing error	*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/10/24		correct invalid SELECT statement used to match	*
 *						birth registrations against a census page		*
 *						failed when no divisions in the subdist			*
 *		2013/04/02		add support for multiple citation tables		*
 *						within page										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/08		syntax error in SQL command						*
 *						add gender check to census search for match		*
 *						correct syntax if multiple index entries		*
 *		2014/01/30		do not pass database connection to constructor	*
 *						use new Citation::__construct					*
 *						capability to assign `order` on new record		*
 *		2014/03/23		use associative array parameter to construct	*
 *						Citation										*
 *		2014/04/30		validate key if possible to avoid unexpected	*
 *						exceptions that cause malformation of XML		*
 *		2014/11/10		support birth, marriage, and death events		*
 *						in tblER										*
 *		2014/11/12		birth citations did not update 1851, 1861 and	*
 *						1921 census records								*
 *						census citation support for birth, death, and	*
 *						marriage events in tblER						*
 *						use classes Birth, Death, and					*
 *						MarriageParticipant to update vital statistics	*
 *						instead of direct SQL							*
 *		2014/11/20		syntax error in SQL looking for match on		*
 *						census page										*
 *						bad parameter list to new CensusLine			*
 *		2014/11/21		censusCitation used IDER instead of IDIR when	*
 *						looking for match for individual				*
 *		2014/11/30		validate parameters								*
 *						enclose trace in <div class='warning'>			*
 *						do not attempt to extract the value of IDCR		*
 *						for the new child, as it may not yet be set		*
 *						suppress debug dumps from constructors			*
 *						avoid SQL syntax errors if bad citation page	*
 *		2014/12/02		do not process zero length parameter values		*
 *		2014/12/29		LegacyName::getNames parameter by reference		*
 *		2015/03/24		adding a birth citation did not set sex			*
 *						in the created birth registration record		*
 *						initialize major fields if create the death		*
 *						registration record for a death citation		*
 *		2015/03/27		$personid not initialized for death registration*
 *						bad field names d_deathdate and d_deathplace	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/10/30		add more information into marriage registration	*
 *						created when user cites an un-transcribed		*
 *						registration									*
 *		2016/02/08		update CountyMarriage entries for marriage		*
 *						citation										*
 *		2016/11/12		undefined $result in County Marriage			*
 *		2017/01/03		avoid exception on age > 110					*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/07/07		incorrect use of $marriage instead of $family	*
 *		2017/07/18		do not try to build marriage location if		*
 *						no marriage event when matching citations to	*
 *						Ontario marriage registrations					*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/05		change class LegacyFamily to class Family		*
 *						improve parameter validation					*
 *		2017/10/11		in presentation of parameter list only include	*
 *						name in the <source> tag interpretation of IDSR	*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/18		use class RecordSet instead of Name::getNames	*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *	History as addCitJSON.php:                                          *
 *	    2021/03/07      converted to return JSON                        *
 *	    2021/06/03      fix formatting errors                           *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/CensusLine.inc';
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/Death.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function censusCitation												*
 *																		*
 *  This function parses the detail text which for a citation to an		*
 *  census of Canada should be the district, subdistrict, division,		*
 *  and page. If it matches that format a link is defined from the		*
 *  transcription of the of the census line to the family tree record	*
 *  of the individual.													*
 *																		*
 *  Input:																*
 *		$year			census year (1871, 1881, 1891, 1901, 1906,		*
 *						1911, 1916, 1921)								*
 *		$page			the source detail information					*
 *		$idir			key of the individual record in the family tree	*
 *		$type			type of event being cited						*
 ************************************************************************/
function censusCitation($year, $page, $idir, $type)
{
    global $connection;
    global $debug;

    if ($type == Citation::STYPE_BIRTH &&
    	!is_null($idir))
    {	                // citation to birth
    	// extract keys identifying the specific page of the census
    	// from the citation string
    	$prov		    = '';
    	$dist	    	= 0;
    	$subdist    	= '';
    	$div	    	= '';
    	$pagenum    	= 0;
    	$pattern    	= "/^([A-Z]{2},|) *(dist *([0-9.]+)|).*?, *(subdist ([A-Z0-9]+)|)(.*?)(div *([A-Z0-9]+)|) *page *([0-9]+)/i";
    	//$pattern	= "/^(([A-Z]{2},|))/i";
    	$count	    	= preg_match($pattern,
    				    		     $page,
    				    		     $matches);
    	if ($count == 1)
    	{
    	    if ($debug)
    			print "\"matches\" : " . print_r($matches, true) . ",\n";
    	    if (strlen($matches[1]) == 3)
    			$prov	= substr($matches[1],0,2);
    	    else
    			$prov	= "''";
    	    $dist	    = $matches[3];
    	    $subdist	= $matches[5];
    	    $div	    = $matches[8];
    	    $pagenum	= $matches[9];
    	    if ($debug)
    	    {
    			print "\"prov\" : " . json_encode($prov) . ",\n";
    			print "\"dist\" : " . json_encode($dist) . ",\n";
    			print "\"subdist\" : " . json_encode($subdist) . ",\n";
    			print "\"div\" : " . json_encode($div) . ",\n";
    			print "\"pagenum\" : " . json_encode($pagenum) . ",\n";
    	    }
    	}
    	else
    	if ($debug)
    	    print "\"count\" : " . json_encode($count) . ",\n";

    	// get information about the individual in a format
    	// suitable for use in the SQL command
    	// the individual may be identified in the census by
    	// birth name, married name, or other alternate name

    	print ",\n    \"alt\" : {\n";
    	$nameparms	    = array('idir' => $idir);
    	$namerecs	    = new RecordSet('Names', $nameparms);

    	$whereName	    = '';
    	$genderTest	    = '';
    	$or		        = '';
        $sqlParms	    = array();
    	foreach($namerecs as $idnx => $namerec)
    	{
    	    $surname	    = $namerec->get('surname');
    	    $givenname	    = $namerec->get('givenname');
    	    $birthsd	    = $namerec->get('birthsd');
    	    $gender	        = $namerec->get('gender');
    	    print "\"row$idnx\" : {\n";
    	    print "\"idnx\" : "      . json_encode($idnx) . ",\n";
    	    print "\"surname\" : "   . json_encode($surname) . ",\n";
    	    print "\"givenname\" : " . json_encode($givenname) . ",\n";
    	    print "\"birthsd\" : "   . json_encode($birthsd) . ",\n";
    	    print "\"gender\" : "   . json_encode($gender) . "\n";
    	    print "}\n";
    	    $sqlParms['surname' . $idnx]	= $surname;
    	    $sqlParms['partGiven' . $idnx]	= substr($givenname, 0, 2);
    	    if (strlen($genderTest) == 0)
    	    {		        // add gender test
    			if ($gender == 0)
    				$genderTest	= "SEX='M' AND ";
    			else
    			if ($gender == 1)
    				$genderTest	= "SEX='F' AND ";
    	    }		        // add gender test
    	    $whereName	.= "$or(LEFT(SOUNDEX(:surname$idnx),4)=SurnameSoundex AND " .
    						"LOCATE(:partGiven$idnx, GivenNames) > 0)";
    	    $or		    = ' OR ';
            $birthYear	= floor($birthsd/10000);
    	}	                // loop through matching names
    	print "}";

    	if (strlen($whereName) > 0)
    	{	                // have at least one name to check
    	    // search the census page for a vague match
    	    $sqlParms['dist']		= $dist;
    	    $sqlParms['subdist']	= $subdist;
    	    $sqlParms['div']		= $div;
    	    $sqlParms['pagenum']	= $pagenum;
    	    $sqlParms['birthYear']	= $birthYear;
    	    if ($year < 1867)
    	    {
    			$sqlParms['prov']	= $prov;
    			$select	= "SELECT Line, Surname, GivenNames FROM Census$year " .
    					" WHERE Province=:prov AND" .
    					" District=:dist AND" .
    					" SubDistrict=:subdist AND" .
    					" Division=:div AND" .
    					" Page=:pagenum AND $genderTest" .
    					" ($whereName) AND" .
    					" ABS(BYear - :birthYear) < 3";
    	    }
    	    else
    			$select	= "SELECT Line, Surname, GivenNames FROM Census$year " .
    					"WHERE District=:dist AND " .
    					"SubDistrict=:subdist AND " .
    					"Division=:div AND " .
    					"Page=:pagenum AND $genderTest " .
    					"($whereName) AND " .
    					"ABS(BYear - :birthYear) < 3";
    	    $sqlParmsText	= print_r($sqlParms, true);
    	    $stmt		    = $connection->prepare($select);
    	    if ($stmt->execute($sqlParms))
    	    {	            // select succeeded
    			$sresult	= $stmt->fetchAll(PDO::FETCH_ASSOC);
    			$count		= count($sresult);
                print ",\n    \"select\" : {\n";
                print "        \"count\" : $count,";
                print "        \"cmd\" : " .
                                    json_encode($select) . ",\n";
                print "        \"parms\" : " .
                                    json_encode($sqlParmsText) . "\n";
                print "    }";
                $comma          = ",\n";
    			foreach($sresult as $row)
    			{	        // found match in census
    			    $line	        = $row['line'];
    			    $csurname	    = $row['surname'];
    			    $cgivennames    = $row['givennames'];

    			    print ",\n    \"row$line\" : {\n";
    			    print "	       \"line\" : $line,\n";
                    print "	       \"surname\" : " . 
                                        json_encode($csurname) . ",\n";
                    print "        \"givennames\" : " .
                                        json_encode($cgivennames) . "\n";
                    print "\n    }";

    			    if ($count == 1)
    			    {		// unique match
    					if ($year < 1867)
    					    $censusId		= $prov . $year;
    					else
    					    $censusId		= 'CA' . $year;
    					$line	= new CensusLine(array(
    								            'Census'	=> $censusId,
    								            'District'	=> $dist,
    								            'SubDistrict'=> $subdist,
    								            'Division'	=> $div,
    								            'Page'		=> $pagenum),
    								            $line);
    					$line->set('idir', $idir);
                        $result		= $line->save();
                        print ",\n    \"sqlcmd" . __LINE__ . "\" : " .
                                    json_encode($sqlcmd);
    			    }		// unique match
    			}		    // found matches in census
    	    }		        // select succeeded
    	    else
    	    {	            // failed to select records
                print "    \"select\" : {\n";
                print "        \"failed\" : true,";
                print "        \"cmd\" : " .
                                    json_encode($select) . ",\n";
                print "        \"parms\" : " .
                                    json_encode($sqlParmsText) . ",\n";
                print "        \"msg\" : " .
                    json_encode(print_r($stmt->errorInfo(),true)) . "\n";
                print "    },\n";
    	    }	            // failed
    	}	                // have at least one name to check
    }		                // citation to birth
}		// function censusCitation

/************************************************************************
 *  function birthCitation												*
 *																		*
 *  This function parses the detail text which for a citation to an		*
 *  Ontario Birth Registration should be the year of registration and	*
 *  the sequential number assigned by the provincial registrar.			*
 *  If it matches that format a link is defined from the transcription	*
 *  of the birth registration to the record in tblIR.					*
 *																		*
 *  Input:																*
 *		$page			the source detail info, registration number		*
 *		$idir			key of the individual record in the family tree	*
 *		$type			type of event being cited						*
 ************************************************************************/
function birthCitation($page, $idir, $type)
{	
    global $debug;

    if ($type == Citation::STYPE_BIRTH &&
    	!is_null($idir))
    {		        // citation to birth
    	// IDIME must be IDIR of individual
    	$personid	= new Person(array('idir' => $idir));
    	$count	    = preg_match('/^([0-9]{4})-([0-9]+)$/',
    				    	     $page,
    					         $matches);
    	if ($count == 1)
    	{		    // detail matches pattern
    	    $regyear	= $matches[1];
    	    $regnum	    = $matches[2];
    	    $birth	    = new Birth('CAON',
    					    	    $regyear,
    						        $regnum);
    	    $birth->set('idir', $idir);
    	    if (!($birth->isExisting()))
    	    {		// new record
    			$birth->set('surname', $personid->get('surname'));
    			$birth->set('givennames',
    						 $personid->get('givenname'));
    			$gender		= $personid->get('gender');
    			if ($gender == Person::MALE)
    			    $birth->set('sex', 'M');
    			else
    			if ($gender == Person::FEMALE)
    			    $birth->set('sex', 'F');
    			$evBirth	= $personid->getBirthEvent(true);
    			$birth->set('birthdate', $evBirth->getDate());
    			$birth->set('birthplace',
    						 $evBirth->getLocation()->toString());
    	    }		// new record
            $result	= $birth->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($birth->getLastSqlCmd());
    	}		    // detail matches pattern
    }			    // citation to birth
    return;
}		// function birthCitation

/************************************************************************
 *  function deathCitation												*
 *																		*
 *  This function parses the detail text which for a citation to an		*
 *  Ontario Death Registration should be the year of registration and	*
 *  the sequential number assigned by the provincial registrar.			*
 *  If it matches that format a link is defined from the transcription	*
 *  of the death registration to the record in tblIR.					*
 *																		*
 *  Input:																*
 *		$page			the source detail info, registration number		*
 *		$idir			key of the individual record in the family tree	*
 *		$type			type of event being cited						*
 ************************************************************************/
function deathCitation($page, $idir, $type)
{	
    global $debug;

    if ($type == Citation::STYPE_DEATH &&
    	!is_null($idir))
    {		        // citation to death
    	// IDIME must be IDIR of individual
    	$personid	= new Person(array('idir' => $idir));
    	$count	= preg_match('/^([0-9]{4})-([0-9]+)$/',
    					     $page,
    					     $matches);
    	if ($count == 1)
    	{	        // detail matches pattern
    	    $regyear	= $matches[1];
    	    $regnum	= $matches[2];
    	    $death	= new Death('CAON',
    						    $regyear,
    						    $regnum);
    	    $death->set('idir', $idir);
    	    if (!($death->isExisting()))
    	    {		// new record
    			$death->set('surname', $personid->get('surname'));
    			$death->set('givennames',
    						 $personid->get('givenname'));
    			$gender		= $personid->get('gender');
    			if ($gender == Person::MALE)
    			    $death->set('sex', 'M');
    			else
    			if ($gender == Person::FEMALE)
    			    $death->set('sex', 'F');
    			$evBirth	= $personid->getDeathEvent(true);
    			$death->set('birthdate', $evBirth->getDate());
    			$death->set('birthplace',
    						 $evBirth->getLocation()->toString());
    			$birthYear	= floor($evBirth->get('eventsd') / 10000);
    			$evDeath	= $personid->getDeathEvent(true);
    			$death->set('date', $evDeath->getDate());
    			$death->set('place',
    						 $evBirth->getLocation()->toString());
    			$deathYear	= floor($evDeath->get('eventsd') / 10000);
    			$death->set('age', $deathYear - $birthYear);
    	    }		// new record
    	    $result	= $death->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($death->getLastSqlCmd());
    	}	        // detail matches pattern
    }		        // citation to death
    return;
}		// function deathCitation

/************************************************************************
 *  function marriageCitation											*
 *																		*
 *  This function parses the detail text which for a citation to an		*
 *  Ontario Marriage Registration should be the year of registration	*
 *  and the sequential number assigned by the provincial registrar.		*
 *  If it matches that format a link is defined from the transcription	*
 *  of the marriage registration to the record in tblIR.				*
 *																		*
 *  Input:																*
 *		$page			the source detail info, registration number		*
 *		$idmr			key of the marriage record in the family tree	*
 *		$type			type of event being cited						*
 ************************************************************************/
function marriageCitation($page, $idmr, $type)
{	
    global $debug;
    global $warn;

    if ($type == Citation::STYPE_MAR && !is_null($idmr))
    {		            // citation to marriage
    	$family		= new Family(array('idmr' => $idmr));
    	$mar		= $family->getMarEvent();
    	if ($mar)
    	{			    // have a marriage event
    	    // check for most common all-numeric reference
    	    $count	    = preg_match('/^([0-9]{4})-([0-9]+)$/',
    				    		     $page,
    				    		     $matches);
    	    // check for citation by explicit volume, page, and column
    	    $countOld	= preg_match('#(vol|volume)\s+([0-9/]+)\s+' . 
    				    			'page\s+([0-9]+)\s+' .
    				    			'(item|col|column)\s+([0-9]+)#',
    				    		     $page,
    				    		     $matchesOld);

    	    $marriage	= null;
    	    if ($count == 1)
    	    {	        // detail matches pattern
    			$regyear    	= $matches[1];
    			$regnum	    	= $matches[2];
    			$marriage   	= new Marriage('CAON',
    					        		       $regyear,
    						        	       $regnum);
    	    }	        // detail matches pattern
    	    else
    	    if ($countOld == 1)
    	    {
    			$originalVolume	= $matchesOld[2];
    			$originalPage	= $matchesOld[3];
    			$originalItem	= $matchesOld[5];
    			if ($debug)
    			    $warn	.= "<p>addCitJson.php: " . __LINE__ .
    						   " originalVolume=$originalVolume, " .
    						   "originalPage=$originalPage, " .
    						   "originalItem=$originalItem</p>\n";
    			$getParms		= array(
    						'domain'		    => 'CAON',
    						'originalVolume'	=> $originalVolume,
    						'originalPage'		=> $originalPage,
    						'originalItem'		=> $originalItem);
    			$marriage   	= new Marriage($getParms);
    			$regyear    	= $marriage->get('regyear');
    			$regnum	    	= $marriage->get('regnum');
    			if ($debug)
    			    $warn   	.= "<p>addCitJson.php: " . __LINE__ .
    					    	   " regyear=$regyear, regnum=$regnum</p>\n";
    	    }

    	    if (isset($marriage))
    	    {			// found Marriage transcription
    			if ($marriage->get('date') == $marriage->get('regyear') &&
    			    $marriage->get('place') == '')
    			{		// uninitialized
    			    $marriage->set('date', $mar->getDate());
    			    $marloc	= $mar->getLocation()->getName();
    			    $marriage->set('place', $marloc);
    			    $locparts	= explode(',',$marloc);
    			    $locCount	= count($locparts);
    			    if ($locCount > 3)
    			    {		// have at least 4 parts to location
    					$countyname	= trim($locparts[$locCount - 3]);
    					$township	= trim($locparts[$locCount - 4]);
    					$getParms	= array('name' => $countyname);
    					$counties	= new CountySet($getParms);
    					if (count($counties) > 0)
    					{
    					    $county	= $counties[0];
    					    $marriage->set('regcounty',
    								$county->get('code'));
    					}
    					else
    					    $marriage->set('regcounty', $countyname);
    					$marriage->set('regtownship', $township);	
    			    }		// have at least 4 parts to location
    			}		// uninitialized
    			else
    			    $marloc	= $marriage->get('place');
    			// update marriage transcription record
    			$result     = $marriage->save();
                if ($result)
                    print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($marriage->getLastSqlCmd());

    			// update record for groom
    			$idirhusb	= $family->get('idirhusb');
    			if ($idirhusb)
    			{			// add information on husband
    			    $husb	= $family->getHusband();
    			    $birth	= $husb->getBirthEvent();
    			    if ($birth)
    			    {		// have birth event
    					$age	= floor($regyear -
    							    ($birth->get('eventsd') / 10000));
    					if ($age < 0)
    					    $age	= 20;
    					$birthloc	= $birth->getLocation()->getName();
    			    }		// have birth event
    			    else
    			    {		// no birth event
    					$age	= 20;
    					$birthloc	= 'Ontario, Canada';
    			    }		// no birth event
    			    $groom	= new MarriageParticipant('CAON',
    									  $regyear,
    									  $regnum,
    									  'G');
    			    $groom->set('idir', $idirhusb);
    			    if ($groom->get('givennames') == '' &&
    					    $groom->get('surname') == '')
    			    {		// not initialized
    					$groom->set('givennames',
    							 $husb->get('givenname'));
    					$groom->set('surname', $husb->get('surname'));
    					if ($age > 110)
    					    $age		= 30;
    					$groom->set('age', $age);
    					$groom->set('residence', $marloc);
    					$groom->set('birthplace', $birthloc);
    			    }		// not initialized
    			    $result	= $groom->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($groom->getLastSqlCmd());
    			}			// add information on husband

    			// update record for bride
    			$idirwife	= $family->get('idirwife');
    			if ($idirwife)
    			{			// add information on wife
    			    $wife	= $family->getWife();
    			    $birth	= $wife->getBirthEvent();
    			    if ($birth)
    			    {		// have birth event
    					$age	= floor($regyear -
    							($birth->get('eventsd') / 10000));
    					if ($age < 0)
    					    $age	= 20;
    					$birthloc	= $birth->getLocation()->getName();
    			    }		// have birth event
    			    else
    			    {		// no birth event
    					$age	= 20;
    					$birthloc	= 'Ontario, Canada';
    			    }		// no birth event
    			    $bride	= new MarriageParticipant('CAON',
    									  $regyear,
    									  $regnum,
    									  'B');
    			    $bride->set('idir', $idirwife);
    			    if ($bride->get('givennames') == '' &&
    					    $bride->get('surname') == '')
    			    {		// not initialized
    					$bride->set('givennames',
    							 $wife->get('givenname'));
    					$bride->set('surname', $wife->get('surname'));
    					$bride->set('age', $age);
    					$bride->set('residence', $marloc);
    					$bride->set('birthplace', $birthloc);
    			    }		// not initialized
    			    $result	= $bride->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($bride->getLastSqlCmd());
    			}			// add information on wife
    	    }			    // found Marriage transcription
    	}			        // have a marriage event
    }		                // citation to marriage
    return;
}		// function marriageCitation

/************************************************************************
 *  function countyMarriageCitation										*
 *																		*
 *  This function parses the detail text which for a citation to an		*
 *  Ontario pre-Confederation Marriage Registration should be the		*
 *  volume number, the report number, and the item number within the	*
 *  report.																*
 *  If it matches that format a link is defined from the transcription	*
 *  of the county marriage registration to the records in tblIR.		*
 *																		*
 *  Input:																*
 *		$page			the source detail info							*
 *		$idmr			key of the marriage record in the family tree	*
 *		$type			type of event being cited						*
 ************************************************************************/
function countyMarriageCitation($page, $idmr, $type)
{	
    global $debug;
    global $warn;

    $result		= '';
    if ($type == Citation::STYPE_MAR &&
    	!is_null($idmr))
    {		// citation to marriage
    	$count	= preg_match('/(vol[^0-9]*([0-9]+)|).*(No|sect|sched)[^0-9]*([0-9]+)[^0-9]+([0-9]+|)/i',
    					     $page,
    					     $matches);
    	if ($count == 1)
    	{	// detail matches pattern
    	    $volume		= $matches[2];
    	    if ($volume == '')
    			$volume		= 16;
    	    $report		= $matches[4];
    	    $item		= $matches[5];
    	    $groom	= new CountyMarriage(array('domain'	=> 'CAON',
    								   'volume'	=> $volume,
    								   'reportno'	=> $report,
    								   'itemno'	=> $item,
    								   'role'	=> 'G'));
    	    $bride	= new CountyMarriage(array('domain'	=> 'CAON',
    								   'volume'	=> $volume,
    								   'reportno'	=> $report,
    								   'itemno'	=> $item,
    								   'role'	=> 'B'));
    	    $family	= new Family(array('idmr' => $idmr)); 

    	    $result	= 0;
    	    // update record for groom
    	    $idirhusb	= $family->get('idirhusb');
    	    if ($idirhusb)
    	    {			// add information on husband
    			$groom->set('idir', $idirhusb);
    			$result	= $groom->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($groom->getLastSqlCmd());
    	    }			// add information on husband

    	    // update record for bride
    	    $idirwife	= $family->get('idirwife');
    	    if ($idirwife)
    	    {			// add information on wifeand
    			$bride->set('idir', $idirwife);
    			$result	+= $bride->save();
            if ($result)
                print ",\n    \"sqlcmd" . __LINE__ . "\" : " . json_encode($bride->getLastSqlCmd());
    	    }			// add information on wife
    	}	// detail matches pattern
    }		// citation to marriage
    return $result;
}		// function countyMarriageCitation

/************************************************************************
 *  Open Code															*
 ************************************************************************/

// print the root node of the JSON object
print "{\n";

// include feedback parameters as attributes
// set default values for parameters
$idsr				= null;		// index of source record
$idime				= null;		// cited record
$type				= Citation::STYPE_MAR;
$idet				= 0;
$formname			= null;
$rownum				= null;
$page				= '';		// page citation
$note				= '';		// comments
$text				= '';		// text from original document
$surety				= 3;		// reliability of source

// database records identified by IDIME
$source				= null;		// Source($idsr);
$personid			= null;		// Person($idime);
$family				= null;		// Family($idime);
$child				= null;		// Child($idime);
$namerec			= null;		// Name($idime);
$event				= null;		// Event($ider);

// determine if permitted to update database
if (strlen($authorized) == 0)
{		        // user not authorized to update database
	$msg	.= 'Not authorized to add citation. ';
}		        // user not authorized to update database

// validate parameters
// include all of the input parameters as debugging information
print "  \"parms\" : {\n";
$comma          = '';
foreach ($_POST as $key => $value)
{			    // look at all parameters
	$value		= trim($value);
    print "$comma    \"$key\" : " . json_encode($value);
    $comma      = ",\n";
	switch($key)
	{		    // act on keys
	    case 'idsr':
	    {		// master source identifier
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idsr	    = intval($value);
			    $source	    = new Source(array('idsr' => $idsr));
			    if ($source->isExisting())
			    {
                    print ",\n    \"title\" : " . 
                        json_encode($source->get('name'));
			    }
			    else
			    {
					$msg	.= "Invalid value of IDSR=$value. ";
			    }
			}	// validate syntax of parameter
			else
			if (strlen($value) > 0)
                $msg	    .= "Invalid value of IDSR=" .
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// master source identifier

	    case 'type':
	    {		// Citation Type
			if ((is_int($value) || ctype_digit($value)) &&
			    array_key_exists(intval($value), Citation::$intType))
			{	// validate syntax of parameter 
			    $type	= intval($value);
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of Citation Type=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// Citation Type

	    case 'formname':
        {		//
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.-]*$/', $value) == 1) 
			    $formname	 = $value;
			else
			    $msg	.= "Invalid value of FormName=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		//

	    case 'idime':
	    {		// generic key of the record containing the fact
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{		// validate syntax of parameter 
			    $idime	= intval($value);
			}		// validate syntax of parameter 
			else
			if (strlen($value) > 0)
			    $msg	.= "Invalid value of IDIME=" .
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// generic key

	    case 'idir':
	    {		// key of tblIR
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idime	= intval($value);
			    $personid	= new Person(array('idir' => $idime));
			    if ($personid->isExisting())
                {
                    print ",\n    \"person\" : ";
					$personid->toJson();
			    }
			    else
			    {
					$msg	.= "Invalid value of IDIR=$value. "; 
			    }
			}	// validate syntax of parameter 
			else
			if (strlen($value) > 0)
			    $msg	.= "Invalid value of IDIR=" .
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// key of tblIR

	    case 'idmr':
	    {		// key of tblMR
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idime		= intval($value);
			    $family	    = new Family(array('idmr' => $idime));
			    if ($family->isExisting())
			    {
					$family->toJson();
			    }
			    else
			    {
					$msg	.= "Invalid value of IDMR=$idmr. ";
			    }
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of IDMR=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		//key of tblMR

	    case 'idcr':
	    {		// key of tblCR
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idime		= intval($value);
			    $child		= new Child(array('idcr' => $idime));
			    if ($child->isExisting())
			    {
					$person		= $child->getPerson();
					$child->toJson();
			    }
			    else
					$msg	.= "Invalid value of IDCR=$value. ";
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of IDCR=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// key of tblCR

	    case 'idnx':
	    {		// key of tblNX
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idime		= intval($value);
			    $namerec	= new Name(array('idnx'	=> $idime));
			    if ($namerec->isExisting())
			    {
					$nameRec->toJson();
			    }
			    else
					$msg	.= "Invalid value of IDNX=$value. ";
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of IDNX=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// key of tblNX

	    case 'ider':
	    {		// key of tblER
			if ((is_int($value) || ctype_digit($value)) && $value > 0)
			{	// validate syntax of parameter 
			    $idime		= intval($value);
			    $event	= new Event($idime);
			    if ($event->isExisting())
					$event->toJson();
			    else
					$msg	.= "Invalid value of IDER=$value. ";
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of IDER=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// key of tblER

	    case 'idet':
	    {		// event type in tblER
			if ((is_int($value) || ctype_digit($value)) && $value >= 0)
			{	// validate syntax of parameter 
			    $idet	= intval($value);
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of IDET=" . 
                                htmlspecialchars($value) . ". "; 
			break;
	    }		// event type in tblER

	    case 'page':
        {		// page number
			$page	        = htmlspecialchars($value);
			break;
	    }		// page number

	    case 'row':
	    {		// row number
			// get the row identifier within the table in the web page
			if ((is_int($value) || ctype_digit($value)) && $value >= 0)
			    $rownum	    = $value;
			else
			if (strlen($value))
			    $msg	    .= "Invalid value of row=" . 
                                    htmlspecialchars($value) . ". "; 
			break;
	    }		// row number

	    case 'text':
	    {		// quoted text
			// text quoted from the source in the citation
			$text	= $value;
			break;
	    }		// quoted text

	    case 'note':
	    {		// comments on the citation
			$note	= $value;
			break;
	    }		// comments

	    case 'surety':
	    {		// quality
			// perceived quality of the citation
			if ((is_int($value) || ctype_digit($value)) && $value >= 0)
			{	// validate syntax of parameter 
			    $surety	= intval($value);
			}	// validate syntax of parameter 
			else
			if (strlen($value))
			    $msg	.= "Invalid value of Surety=" . 
                                    htmlspecialchars($value) . ". "; 
			break;
	    }		// quality
	}		    // act on keys
}			    // look at all parameters
print "  }";

// check for missing mandatory parameters;
if ($idsr === null)
	$msg	.= 'Missing mandatory parameter idsr. ';
if ($idime === null)
	$msg	.= 'Missing mandatory parameter id.... ';
if ($formname === null)
	$msg	.= 'Missing mandatory parameter formname. ';
if ($rownum === null)
	$msg	.= 'Missing mandatory parameter row. ';

if (strlen($warn) > 0)
{
    print "    \"warn\" : " . json_encode($warn) . ",\n";
}

// if any errors encountered in validating parameters
// terminate the request and return the error message
if (strlen($msg) > 0)
{		// return the message text in XML
	print ",\n    \"msg\" : " . json_encode($msg) . "\n";
}		// return the message text in XML
else
{		// add the citation
	// add the citation to the database
	$citParms	= array('idime'		    => $idime,
						'type'		    => $type,
						'idsr'		    => $idsr,
						'srcdetail'	    => $page,
						'srcdettext'	=> $text,
						'srcdetnote'	=> $note,
						'srcsurety'	    => $surety);

	$citation	= new Citation($citParms);
    $count      = $citation->save();	// write into the database
    if ($count > 0)
        print ",\n    \"sqlcmd" . __LINE__ . "\" : " .
                json_encode($citation->getLastsqlcmd());

	// get the unique numeric identifier of the inserted citation record
	// and feed it back to the invoker 
	$idsx	    = $citation->getIdsx();
	print ",\n    \"citation\" : ";
    $citation->toJson(true, 0);
    $comma      = ",\n";

	// some events moved from tblIR and tblMR to tblER 
	// in general the citation type is passed as the parameter Type=
	$citType		= $type;

	// ensure that $idir is set for facts and events in tblIR
	$idir			= null;
	$idmr			= null;
	switch(Citation::$recType[$citType])
	{
	    case 'IDIR':
	    {
			$idir		= $idime;
			break;
	    }		// citation to a fact or event in tblIR

	    case 'IDMR':
	    {
			$idmr		= $idime;
			break;
	    }		// citation to a fact or event in tblMR

	    case 'IDER':
	    {
			$ider		= $idime;
			if (is_null($event))
			{
			    $event	= new Event($ider);
			}
			break;
	    }		// citation to a fact or event in tblIR

	}		// act on specific record types

	if ($type == Citation::STYPE_EVENT)
	{		// possibly event moved to tblER from tblIR
	    if ($event)
            $idir		= $event['idir'];

	    switch($idet)
	    {		// act on specific event types
			case Event::ET_BIRTH:
			{
			    $citType	= Citation::STYPE_BIRTH;
			    break;
			}

			case Event::ET_DEATH:
			{
			    $citType	= Citation::STYPE_DEATH;
			    break;
			}

	    }		// act on specific event types
	}		// possibly event moved to tblER from tblIR
	else
	{		// possibly event moved to tblER from tblMR
	    if ($event)
			$idmr		= $event['idir'];
	    switch($idet)
	    {		// act on specific event types
			case Event::ET_MARRIAGE:
			{
			    $citType	= Citation::STYPE_MAR;
			    break;
			}

	    }		// act on specific event types
	}		// possibly event moved to tblER from tblMR

	// certain citations require the creation of links from the
    // transcription of the source back to the family tree
    //print "$comma    \"line\" : " . __LINE__ ;
	switch($idsr)
	{		// switch on source identifier
	    case 11:
	    {	// 1851 Census of Canada
			censusCitation('1851', $page, $idir, $citType);
			break;
	    }	// 1871 Census of Canada 

	    case 12:
	    {	// 1871 Census of Canada
			censusCitation('1871', $page, $idir, $citType);
			break;
	    }	// 1871 Census of Canada 

	    case 13:
	    {	// 1871 Census of Canada
			censusCitation('1871', $page, $idir, $citType);
			break;
	    }	// 1871 Census of Canada 

	    case 16:
	    {	// 1881 Census of Canada
			censusCitation('1881', $page, $idir, $citType); 
			break;
	    }	// 1881 Census of Canada 

	    case 17:
	    {	// 1891 Census of Canada
			censusCitation('1891', $page, $idir, $citType);
			break;
	    }	// 1891 Census of Canada 

	    case 19:
	    {	// 1901 Census of Canada
			censusCitation('1901', $page, $idir, $citType);
			break;
	    }	// 1901 Census of Canada 

	    case 85:
	    {	// pre-Confederation Ontario Marriage Registration
			countyMarriageCitation($page, $idmr, $citType);
			break;
	    }	// pre-Confederation Ontario Marriage Registration

	    case 97:
	    {	// Birth Register, CA, Ontario
			birthCitation($page, $idir, $citType);
			break;
	    }	// Birth Register, CA, Ontario

	    case 98:
	    {	// Death Register, CA, Ontario
			deathCitation($page, $idir, $citType);
			break;
	    }	// Death Register, CA, Ontario

	    case 99:
	    {	// Marriage Register, CA, Ontario
			marriageCitation($page, $idmr, $citType);
			break;
	    }	// Marriage Register, CA, Ontario

	    case 224:
	    {	// 1906 Census of Canada
			censusCitation('1906', $page, $idir, $citType);
			break;
	    }	// 1906 Census of Canada 

	    case 271:
	    {	// 1911 Census of Canada
			censusCitation('1911', $page, $idir, $citType);
			break;
	    }	// 1911 Census of Canada 

	    case 389:
	    {	// 1916 Census of Canada
			censusCitation('1916', $page, $idir, $citType);
			break;
	    }	// 1916 Census of Canada 

	    case 466:
	    {	// 1921 Census of Canada
			censusCitation('1921', $page, $idir, $citType);
			break;
	    }	// 1921 Census of Canada 

	}		// switch on source identifier
}		    // add the citation

// close of top level node 
print "}\n";
