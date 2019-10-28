<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getPersonNamesXml.php												*
 *																		*
 *  Return an XML file containing a summary of individuals matching a	*
 *  particular pattern, for example with surname and beginning of given	* 
 *  name.																*
 *																		*
 *  Parameters:															*
 *		Surname			mandatory surname or surname prefix				*
 *		LastSurname		optional end surname of range					*
 *		GivenName		optional given name								*
 *		Sex				gender: "M" or "F"								*
 *		IDIR			IDIR value to exclude from match				*
 *		BirthMin		minimum birth year to include in search			*
 *		BirthMax		maximum birth year to include in search			*
 *		BirthYear		approximate birth year to include in search		*
 *		Range			range on either side of approximate birth year	*
 *		incMarried		include married names in search					*
 *		parentsIdmr		IDMR of family, looking for potential children	*
 *		loose			use loose comparison (e.g. soundex on surname)	*
 *		buttonId		id of the invoking button						*
 *		includeParents	include names of parents						*
 *		includeSpouse	include name of spouse							*
 *																		*
 *  History:															*
 *		2010/08/29		created											*
 *		2010/09/19		privatize dates									*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/14		do not display idir=0							*
 *		2011/06/12		add gender to response							*
 *		2011/11/26		use name index table to support married names	*
 *		2012/01/13		change class names								*
 *		2012/04/16		add support for search by approximate birth year*
 *						and for loose name comparison					*
 *						and for gender specific							*
 *		2012/04/21		make loose surname and given checks even looser	*
 *		2012/05/02		check against BirthSD in tblIR because more		*
 *						reliable than value in tblNX					*
 *		2012/05/05		suppress duplicate response for husbands who	*
 *						have both a primary and a marriage entry with	*
 *						the same values									*
 *		2012/07/27		handle familiar names in loose search more		*
 *						generically										*
 *						in loose search only match to start of given	*
 *						name part										*
 *						GivenNames parameter is now always full			*
 *						field value										*
 *		2012/09/10		expand list of givens with alternate prefixes	*
 *		2012/10/08		expand list of givens with alternate prefixes	*
 *		2012/10/21		expand list of given names with					*
 *						alternate prefixes								*
 *		2012/11/25		expand list of givens with alternate prefixes	*
 *						try surnames with and without final 's'			*
 *						only prefix 'Mc' on probable Scottish names		*
 *		2013/01/28		add exclusion of a specified IDIR and all IDIRs	*
 *						that are defined in tblDM to not be merged with	*
 *						the specified IDIR.  This is to support the		*
 *						Merge Individuals page							*
 *		2013/01/30		do not fail on bad IDIR value					*
 *		2013/02/26		use clearer JOIN syntax for tables				*
 *		2013/02/28		add alternatives for 'Edith', 'Matilda', etc.	*
 *						support array of alternative given names		*
 *						improve performance								*
 *		2013/03/05		do not use info from IDIR to override surname	*
 *						and givenname parameters, so user can			*
 *						override the presented list by name				*
 *						search for children to add to a family			*
 *						excludes existing children of that family so	*
 *						you won't attach the same child twice			*
 *						by mistake.										*
 *		2013/03/12		honor privacy setting of individual				*
 *		2013/03/23		given name was not split into components before	*
 *						looking for match								*
 *		2013/04/13		if invoked with no parameters default to		*
 *						search for last referenced individual instead   *
 *						of first										*
 *		2013/05/10		fix error if cookie improperly formatted		*
 *		2013/06/10		too many distinct surnames match soundex 'M245'	*
 *						use a pattern match instead						*
 *		2013/06/17		fix problems with Irish patronymics				*
 *		2013/07/04		improve matches for McIntyre and Montgomery		*
 *		2013/07/21		correct privacy support so that owner sees dates*
 *		2013/07/31		include BirthSD and DeathSD values if invoker	*
 *						is owner of individual							*
 *		2013/08/05		add synonyms for "Lydia"						*
 *		2013/09/02		add synomyms for "Selena", "Celinda", etc.		*
 *		2013/11/04		add synonyms for Gertrude etc.					*
 *		2013/11/15		handle database connection failure gracefully	*
 *		2013/12/05		fix bug when surname is zero length				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/10		add birth year range when looking for children	*
 *						add versions of "Libby"							*
 *	    2013/12/16	    do not fail on bad value of sex, just ignore	*	
 *						add pattern for "Sm[iey]th?", "Kellestine"		*
 *		2014/02/06		when searching for a match to a specifie		*
 *						individual, identified by IDIR, use birth date	*
 *		2014/02/17		loose surname pattern match moved to common.inc	*
 *						allow negative birth year and range				*
 *		2014/09/12		did not handle request from chooseIndivids.js	*
 *						for merge with exclusion correctly				*
 *						pass implicit parameter values in document		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *		2014/11/28		limit response by gender						*
 *						option to add information on parents and spouse	*
 *		2014/12/03		handle exception on bad spouse IDIR				*
 *		2014/12/22		use LegacyIndiv::getBirthEvent					*
 *		2015/03/24		remove code to default names, birth, and sex	*
 *						based on IDIR parameter, which messed up		*
 *						search for relative								*
 *		2015/04/14		add 'Moria[h]' to given names					*
 *		2015/06/16		do not include parents and spouses if private	*
 *		2015/07/02		access PHP includes using include_path			*
 *						use LegacyIndiv::getPersons						*
 *		2015/07/06		do not remove quote from surname				*
 *		2015/08/11		support treename								*
 *		2015/10/26		add support for LastSurname parameter			*
 *		2016/07/26		pass array of given names if split by space		*
 *		2016/10/13		get birth date and standard date using common	*
 *			            event routines					                *	
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/09/12		use get( and set(								*
 *		2017/12/12		use class PersonSet in place of					*
 *						Person::getPersons								*
 *		2019/07/18      fix infinite recursion getting spouse name      *       
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/common.inc';

// return the results as an XML document
// the following is a kluge to get around a bug in VIM styling for
// PHP scripts where it interprets the closing bracket as the end of
// PHP code and the beginning of HTML code
print "<?xml version='1.0' encoding='UTF-8'?" . ">\n";

// construct search pattern from parameters
$where				= '';		//Un start constructing where clause
$idir				= 0;		// IDIR to exclude from response
$indiv				= null;		// instance of Person
$idmr				= 0;		// IDMR of family to add to
$family				= null;		// instance of Family
$surname			= '';		// default to start at first surname
$lastsurname		= null;		// default to start at first surname
$givenname			= '';		// default to start at first given name 
$sex				= '';		// default to either sex
$birthyear			= null;		// default to not checking birth year
$birthmin			= null;		// default to not checking bth year min
$birthmax			= null;		// default to not checking bth year max
$sexPassed			= false;	// true if explicit sex specified
$birthyearPassed	= false;	// true if explicit birth year
$birthminPassed		= false;	// true if explicit min birth year
$birthmaxPassed		= false;	// true if explicit max birth year
$incMarried			= false;	// default to not include married names
$loose				= false;	// default to exact name match
$includeParents		= false;	// do not include parents' names
$includeSpouse		= false;	// do not include spouse's name
$range				= 3;		// +/- 3 years on birth year
$buttonId			= '';		// id of invoking button
$treename			= '';
$qsurname			= "''";
$limit				= 50;
$matches			= array();

foreach($_GET as $key => $value)
{
    switch(strtolower($key))
    {		// act on specific keys
        case 'idir':
        {		// match an existing individual for merge
    		if (strlen($value) > 0)
    		{	// value provided
    		    if (ctype_digit($value))
    		    {	// all numeric
    				$idir		= $value;
    				try {
    				    $indiv   = new Person(array('idir' => $idir));
    				}		// try
    				catch (Exception $e) {
    				    $msg    .= "No database record for idir=$idir. ";
    				}	// catch
    		    }	// all numeric
    		    else
    				$msg	.= "Invalid value of IDIR='" .
    						   xmlentities($value) . "'. ";
    		}	// value provided
    		break;
        }		// match an existing individual for merge

        case 'surname':
        {		// match by surname
            if (strlen(trim($value)) > 0)
    		    $surname	= $value;
    		break;
        }		// match by surname

        case 'lastsurname':
        {		// match by surname
    		if (strlen(trim($value)) > 0)
    		    $lastsurname	= $value;
    		else
    		    $lastsurname	= null;
    		break;
        }		// match by surname

        case 'givenname':
        {		// match by given name pattern
            // see note above for surname
            if (strlen(trim($value)) > 0)
    		    $givenname	= str_replace("'", "", $value);
    		break;
        }		// match by given name pattern

        case 'treename':
        {		// match by treename
            if (strlen(trim($value)) > 0)
    		    $treename	= $value;
    		break;
        }		// match by treename


        case 'sex':
        {		// match by sex
    		$sexPassed		= true;
    		if ($value == 'm' || $value == 'M' ||
    		    $value == 'f' || $value == 'F')
    		    $sex		= $value;
    		break;
        }		// match by sex

        case 'birthyear':
        {		// match by approximate year of birth
    		$birthyearPassed	= true;
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (preg_match('/-?\d+/', $value, $matches) == 1)
    				$birthyear	= $matches[0];
    		    else
    				$msg	.= "Invalid value of BirthYear='$value'. ";
    		}	// value supplied
    		break;
        }		// match by year of birth

        case 'birthmin':
        {		// match by year of birth
    		$birthminPassed	= true;
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (preg_match('/-?\d+/', $value, $matches) == 1)
    				$birthmin	= $matches[0];
    		    else
    				$msg	.= "Invalid value of BirthMin='$value'. ";
    		}	// value supplied
    		else
    		    $birthmin	= -9999;
    		break;
        }		// match by year of birth

        case 'birthmax':
        {		// match by year of birth
    		$birthmaxPassed	= true;
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (preg_match('/-?\d+/', $value, $matches) == 1)
    				$birthmax	= $matches[0];
    		    else
    				$msg	.= "Invalid value of BirthMax='$value'. ";
    		}	// value supplied
    		else
    		    $birthmax	= 9999;
    		break;
        }		// match by year of birth

        case 'range':
        {		// range of birth dates
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (ctype_digit($value))
    				$range		= $value;
    		    else
    				$msg	.= "Invalid value of range='$value'. ";
    		}	// value supplied
    		break;
        }		// range of birth dates

        case 'incmarried':
        {		// include married names in response
    		$incMarried	= strtolower($value) == 'y' ||
    						  strtolower($value) == 'yes';
    		break;
        }		// include married names in response

        case 'includeparents':
        {		// include parents' names in response
    		$includeParents	= strtolower($value) == 'y' ||
    						  strtolower($value) == 'yes';
    		break;
        }		// include parents' names in response

        case 'includespouse':
        {		// include spouse's name in response
    		$includeSpouse	= strtolower($value) == 'y' ||
    						  strtolower($value) == 'yes';
    		break;
        }		// include spouse's name in response

        case 'parentsidmr':
        {		// IDMR of family to add children to
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (ctype_digit($value))
    		    {		// is numeric
    				$idmr		= $value;
    				try {
    				    $family	= new Family(array('idmr' => $idmr));
    				    $husbbirthsd= $family->get('husbbirthsd');
    				    $wifebirthsd= $family->get('wifebirthsd');
    				    if ($husbbirthsd != 0)
    				    {	// have father's birth date
    						$birthmin	= floor($husbbirthsd/10000)+15;
    						$birthmax	= floor($husbbirthsd/10000)+65;
    				    }	// have father's birth date
    				    else
    				    if ($wifebirthsd != 0)
    				    {	// have mother's birth date
    						$birthmin	= floor($wifebirthsd/10000)+15;
    						$birthmax	= floor($wifebirthsd/10000)+55;
    				    }	// have mother's birth date
    				    $incMarried	= false;
    				}	// try
    				catch (Exception $e) {
    				    $msg	.= "No database record for parentsIdmr=$idmr. ";
    				}	// catch
    		    }		// is numeric
    		    else
    				$msg	.= "Invalid value of parentsIdmr='$value'. ";
    		}	// value supplied
    		break;
        }		// IDMR of family to add children to

        case 'loose':
        {
    		$loose		= strtolower($value) == 'y' ||
    						  strtolower($value) == 'yes';
    		break;
        }		// loose specified

        case 'buttonid':
        {		// feeds back a linkage to the invoker
    		$buttonId	= $value;
    		break;
        }		// feeds back a linkage to the invoker

        case 'limit':
        {		// limit number of responses
    		if (strlen($value) > 0)
    		{	// value supplied
    		    if (ctype_digit($value))
    				$limit	= $value;
    		    else
    				$msg	.= "Invalid value of Limit='$value'. ";
    		}	// value supplied
    		break;
        }		// limit number of responses

    }		// act on specific keys
}			// loop through parameters

// check for various birth year combinations
if (is_null($birthmin) && is_null($birthmax) && !is_null($birthyear))
{		// birthyear specified
    $birthmin	= $birthyear - $range;
    $birthmax	= $birthyear + $range;
}		// birthyear specified
else
if ($birthmin > $birthmax)
{
    $msg	.= "BirthMin=$birthmin greater than BirthMax=$birthmax. ";
}

// if any errors detected just return error message
if (strlen($msg) > 0)
{
    print "<msg>$msg</msg>\n";
}
else
{			// no errors
    // top node of XML result
    // the "buttonId" parameter feeds back a linkage to the invoker
    if (strlen($buttonId) > 0)
        print("<names buttonId='$buttonId'>\n");
    else
        print("<names>\n");

    // document parameters to script
    print "  <parms>\n";
    foreach($_GET as $key => $value)
    {
        print "    <$key>$value</$key>\n";
    }

    // include deduced parameters
    if (!$sexPassed	&& strlen($sex) > 0)
    {
        print "    <sex>$sex</sex>\n";
    }
    if (!$birthyearPassed && !is_null($birthyear))
    {
        print "    <birthyear>$birthyear</birthyear>\n";
    }
    if (!$birthminPassed && !is_null($birthmin))
    {
        print "    <birthmin>$birthmin</birthmin>\n";
    }
    if (!$birthmaxPassed && !is_null($birthmax))
    {
        print "    <birthmax>$birthmax</birthmax>\n";
    }
    print "  </parms>\n";

    if (strlen($warn) > 0)
    {		    // support diagnostics prior to start of XML
        print "<div class='warning'>$warn</div>\n";
    }		    // support diagnostics prior to start of XML

    // if the name pattern to search for was not supplied in
    // the parameters, get the name that was used in the last
    // invocation of this script from a cookie
    if (strlen($surname) == 0 &&
        strlen($givenname) == 0 &&
        count($_COOKIE) > 0)
    {		    // parameters did not set name
        // the familyTree cookie is now extracted by common code for
        // all scripts
        if (array_key_exists('idir', $familyTreeCookie))
        {		// last referenced individual
    		$val	        = $familyTreeCookie['idir'];
    		try {
    		    $indiv	    = new Person(array('idir' => $val));
    		    $surname	= $indiv->get('surname');
    		    $givenname	= $indiv->get('givenname');
    		    $retval	    = true;		// globals set
    		} catch(Exception $e) {
    		    $msg	    .= $e->getMessage();
    		}
        }		// last referenced individual
    }		    // parameters did not set initial name

flush();
    // construct WHERE clause
    $getParms	= array();
    if ($loose)
    {			// loose comparison for names
        $getParms['loose']		= 'Y';
        $getParms['surname']	= "$surname";
    }			// loose comparison for names
    else
    {			// range of surnames
        // starting with the specific name and running to the end
        $getParms['surname']	= array($surname, $lastsurname);
    }			// range of surnames

    // optional given name for first individual within surname
    if (strlen($givenname) > 0)
    {
        $givennames		= explode(' ', $givenname);
        $givennames		= array_diff($givennames, array(''));
        if (count($givennames) == 1)
    		$getParms['givenname']	= $givenname;
        else
    		$getParms['givenname']	= $givennames;
    }		// given name supplied

    // tree name
    $getParms['treename']		= $treename;

    // check for gender specific request
    if ($sex == 'M' || $sex == 'm')
    {			// looking for male
        $getParms['gender']		= 0;
    }			// looking for male
    else
    if ($sex == 'F' || $sex == 'f')
    {			// looking for female
        $getParms['gender']		= 1;
    }			// looking for female

    // which types of names to include
    // if looking for a potential spouse or child to add to a family 
    // do not include married names
    if ($incMarried)
        $getParms['incmarried']	= 'y';

    // birth year to include in comparison
    if (!is_null($birthmin))
        $getParms['birthmin']	= $birthmin;
    if (!is_null($birthmax))
        $getParms['birthmax']	= $birthmax;

    // IDIR to exclude for match
    // do not merge an individual with him/herself, and do not merge with
    // any individual already known not to be the individual
    if ($idir > 0)
        $getParms['excidir']	= $idir;

    // if request to look for child to add to a family,
    // exclude existing children of family from results
    if ($idmr > 0)
        $getParms['excidmr']	= $idmr;

    $getParms['limit']		= $limit;
    $getParms['order']		= 'tblNX.`Surname`, tblNX.`GivenName`, COALESCE(EBirth.`EventSD`,tblIR.`BirthSD`)';
    // execute the query
    $msgParms       = "array(";
    $comma          = '';
    foreach($getParms as $key => $value)
    {
        $msgParms   .= "$comma'$key'=>";
        if (is_string($value))
        {
            if (preg_match('/-?\d+/',$value))
                $msgParms   .= $value;
            else
                $msgParms   .= "'$value'";
        }
        else
        if (is_int($value))
            $msgParms   .= $value;
        else
        if (is_null($value))
            $msgParms   .= 'null';
        else
        if (is_array($value))
        {
            $msgParms   .= 'array(';
            $tcom       = '';
            foreach($value as $id => $val)
            {
                $msgParms   .= "$tcom$id=>";
                if (is_string($val))
                    $msgParms   .= "'$val'";
                else
                if (is_int($val))
                    $msgParms   .= $val;
                else
                if (is_null($val))
                    $msgParms   .= 'null';
                else
                    $msgParms   .= print_r($val, true);
                $tcom           = ',';
            }
            $msgParms           .= ')';
        }
        else
            $msgParms   .= print_r($value, true);
        $comma                  = ',';
    }
    $msgParms                   .= ')';
    print "<cmd>new PersonSet($msgParms)</cmd>\n";
    $result		            = new PersonSet($getParms);
    $info		            = $result->getInformation();
    print "<cmd>" . $info['query'] . "</cmd>\n";
    $count		            = $result->count();
flush();

    // iterate through results
    foreach($result as $idir => $indiv)
    {		// loop through all result rows
        // check if current user is an owner of the record and therefore
        // permitted to see private information and edit the record
        $isOwner	        = $indiv->isOwner();

        // extract fields from individual 
        $surname	        = xmlentities($indiv->get('indexsurname'));
        $maidenname	        = xmlentities($indiv->get('surname'));
        $givenname	        = xmlentities($indiv->get('givenname'));
        $birth	            = $indiv->getBirthEvent();
        $death	            = $indiv->getDeathEvent();
        if ($isOwner)
        {
    		$bprivlim		= 9999;
    		$dprivlim		= 9999;
        }
        else
        {
    		$currYear		= intval(date('Y'));
    		$bprivlim		= $currYear - 100;
    		$dprivlim		= $currYear - 27;
        }
        if ($birth)
        {
    		$birthsd	    = $birth->get('eventsd');
    		$birthd		    = $birth->getDate($bprivlim);
        }
        else
        {
    		$birthd		    = '';
    		$birthsd	    = 99999999;
        }
        if ($death)
        {
    		$deathsd	    = $death->get('eventsd');
    		$deathd	        = $death->getDate($dprivlim);
        }
        else
        {
    		$deathd		    = '';
    		$deathsd	    = 99999999;
        }
        $gender	            = $indiv->get('gender');
        $private	        = $indiv->get('private');
        if ($isOwner || $private < 2)
        {		// existence of individual visible
    		print "    <indiv id='$idir'>\n";
    		print "\t<surname>" . $surname . "</surname>\n";
    		print "\t<maidenname>" . $maidenname . "</maidenname>\n";
    		print "\t<givenname>" . $givenname . "</givenname>\n";
    		print "\t<gender>" . $gender . "</gender>\n";
    		print "\t<birthd>$birthd</birthd>\n";
    		print "\t<deathd>$deathd</deathd>\n";
    		if ($isOwner)
    		{		// shared owner
    		    print "\t<birthsd>$birthsd</birthsd>\n";
    		    print "\t<deathsd>$deathsd</deathsd>\n";
    		}		// shared owner

    		if ($private == 0)
    		{		// information is public
    		    // display names of parents
    		    if ($includeParents)
    		    {		// include parents' names
    				$parents	= $indiv->getParents();
    				if (count($parents) > 0)
    				{
    				    print "\t<parents>\n";
    				    $conjunction	= '';
    				    foreach($parents as $idmr => $parent)
    				    {
    						print $conjunction . $parent->getName(false);
    						$conjunction	= ', and ';
    				    }	// loop through all sets of parents
    				    print "\t</parents>\n";
    				}
    		    }		// include parents' names

    		    // display name of spouse
    		    if ($includeSpouse)
    		    {		// include spouse's name
    				$families	                = $indiv->getFamilies();
    				if (count($families) > 0)
    				{
    				    print "\t<families>\n";
    				    $conjunction	        = '';
    				    foreach($families as $idmr => $family)
    				    {
    						if ($gender == 0)
    						    $spouseName	    = $family->getWifePriName();
    						else
    						    $spouseName	    = $family->getHusbPriName();
    						if ($spouseName)
    						{
    						    print $conjunction . $spouseName->getName();
    						    $conjunction	= ', and ';
    						}
    				    }	// loop through all sets of families
    				    print "\t</families>\n";
    				}
    		    }		// include spouse's name
    		}		// information is public

    		// close the top level
    		print "    </indiv>\n";
        }		// existence of individual visible
    }			// loop through all result rows

    print("</names>\n");	// close off top node of XML result
}			// no errors
