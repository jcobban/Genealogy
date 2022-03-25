<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getPersonNamesJSON.php												*
 *																		*
 *  Return a JSON document containing a summary of individuals matching *
 *  a particular pattern, for example with surname and beginning of     *
 *  given name.															*
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
 *		lang            language for response                           *
 *																		*
 *  History:															*
 *		2020/04/03      created from getIndivNamesXml.php to move       *
 *		                more function to script and return JSON         *
 *		2020/04/13      correct order of persons                        *
 *		2021/01/01      correct poor performance if only 1 surname      *
 *		2022/03/23      feedback next surname                           *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// return the results as an JSON document
print "{\n";

// construct search pattern from parameters
$idir				= 0;		// IDIR to exclude from response
$indiv				= null;		// instance of Person
$idmr				= 0;		// IDMR of family to add to
$family				= null;		// instance of Family
$surname			= '';		// default to start at first surname
$lastsurname		= null;		// default to end at last surname
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
$lang               = 'en';     // preferred language of output
$treename			= '';
$qsurname			= "''";
$limit				= 50;
$matches			= array();

if (isset($_GET) && count($_GET) > 0)
{
    print "    \"parms\" : {";
    $comma                          = '';
	foreach($_GET as $key => $value)
	{
        $escvalue                   = str_replace('"','\\"',$value);
        print "$comma\n        \"$key\" : \"$escvalue\"";
        $comma                      = ',';
	    switch(strtolower($key))
	    {		// act on specific keys
	        case 'idir':
	        {		// match an existing individual for merge
	    		if (strlen($value) > 0)
	    		{	// value provided
	    		    if (ctype_digit($value))
	    		    {	// all numeric
	    				$idir		= $value;
	    				$indiv      = new Person(array('idir' => $idir));
	    				if (!$indiv->isExisting())
	    				    $msg    .= "No database record for idir=$idir. ";
	    		    }	// all numeric
	    		    else
	    				$msg	.= "Invalid value of IDIR='$value'. ";
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
	
	        case 'lang':
	        {
	            $lang           = FtTemplate::validateLang($value);
	            break;
	        }
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
    // include deduced parameters
    if (!$sexPassed	&& strlen($sex) > 0)
    {
        print ",\n    \"sex\" : \"$sex\"";
    }
    if (!$birthyearPassed && !is_null($birthyear))
    {
        print ",\n    \"birthyear\" : \"$birthyear\"";
    }
    if (!$birthminPassed && !is_null($birthmin))
    {
        print ",\n    \"birthmin\" : \"$birthmin\"";
    }
    if (!$birthmaxPassed && !is_null($birthmax))
    {
        print ",\n    \"birthmax\" : \"$birthmax\"";
    }

    print "\n    },\n";         // end "parms" object
}               // have parms passed by method=get

$template           = new FtTemplate("Person$lang.html");
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

if (strlen($msg) == 0)
{
    // if the name pattern to search for was not supplied in
    // the parameters, get the name that was used in the last
    // invocation of this script from a cookie
    if (false && strlen($surname) == 0 &&
        strlen($givenname) == 0 &&
        isset($familyTreeCookie) &&
        count($familyTreeCookie) > 0)
    {		    // parameters did not set name
        // the familyTree cookie is now extracted by common code for
        // all scripts
        if (array_key_exists('idir', $familyTreeCookie))
        {		// last referenced individual
    		$val	        = $familyTreeCookie['idir'];
            $indiv	        = new Person(array('idir' => $val));
            if ($indiv->isExisting())
            {
    		    $surname	= $indiv->get('surname');
    		    $givenname	= $indiv->get('givenname');
    		    $retval	    = true;		// globals set
            } 
            else 
            {
    		    $msg	    .= "IDIR=$val does not identify an existing Person. ";
    		}
        }		// last referenced individual
    }		    // parameters did not set initial name

    // construct search
    $getParms	= array();
    if ($loose)
    {			// loose comparison for names
        $getParms['loose']		    = 'Y';
        $getParms['surname']	    = $surname;
    }			// loose comparison for names
    else
    {			// range of surnames
        // starting with the specific name and running to the end
        if (is_null($lastsurname))
            $getParms['surname']	= $surname;
        else
            $getParms['surname']	= array($surname, $lastsurname);
    }			// range of surnames

    // optional given name for first individual within surname
    if (strlen($givenname) > 0)
    {
        $givennames		            = explode(' ', $givenname);
        $givennames		            = array_diff($givennames, array(''));
        if (count($givennames) == 1)
    		$getParms['givenname']	= $givenname;
        else
    		$getParms['givenname']	= $givennames;
    }		// given name supplied

    // tree name
    $getParms['treename']		    = $treename;

    // check for gender specific request
    if ($sex == 'M' || $sex == 'm')
    {			// looking for male
        $getParms['gender']		    = 0;
    }			// looking for male
    else
    if ($sex == 'F' || $sex == 'f')
    {			// looking for female
        $getParms['gender']		    = 1;
    }			// looking for female

    // which types of names to include
    // if looking for a potential spouse or child to add to a family 
    // do not include married names
    if ($incMarried)
        $getParms['incmarried']	    = 'y';

    // birth year to include in comparison
    if (!is_null($birthmin))
        $getParms['birthmin']	    = $birthmin;
    if (!is_null($birthmax))
        $getParms['birthmax']	    = $birthmax;

    // IDIR to exclude for match
    // do not merge an individual with him/herself, and do not merge with
    // any individual already known not to be the individual
    if ($idir > 0)
        $getParms['excidir']	    = $idir;

    // if request to look for child to add to a family,
    // exclude existing children of family from results
    if ($idmr > 0)
        $getParms['excidmr']	    = $idmr;

    $getParms['limit']		        = $limit;
    $getParms['order']		        = 'tblNX.`Surname`, tblNX.`GivenName`, COALESCE(EBirth.`EventSD`,tblIR.`BirthSD`)';
    // execute the query
    $msgParms                       = "array(";
    $comma                          = '';
    foreach($getParms as $key => $value)
    {
        $msgParms                   .= "$comma'$key'=>";
        if (is_string($value))
        {
            if (preg_match('/-?\d+/',$value))
                $msgParms           .= $value;
            else
                $msgParms           .= "'$value'";
        }
        else
        if (is_int($value))
            $msgParms               .= $value;
        else
        if (is_null($value))
            $msgParms               .= 'null';
        else
        if (is_array($value))
        {
            $msgParms               .= 'array(';
            $tcom                   = '';
            foreach($value as $id => $val)
            {
                $msgParms           .= "$tcom$id=>";
                if (is_string($val))
                    $msgParms       .= "'$val'";
                else
                if (is_int($val))
                    $msgParms       .= $val;
                else
                if (is_null($val))
                    $msgParms       .= 'null';
                else
                    $msgParms       .= print_r($val, true);
                $tcom               = ',';
            }
            $msgParms               .= ')';
        }
        else
            $msgParms               .= print_r($value, true);
        $comma                      = ',';
    }
    $msgParms                       .= ')';
    print "      \"cmd\" : \"new PersonSet($msgParms)\"";
    $result		                    = new PersonSet($getParms);
    $info		                    = $result->getInformation();
    print ",\n      \"query\" : \"" .
        str_replace('"', '\\"', $info['query']) . "\"";
    $surnameObj                     = new Surname(array('surname' => $surname));
    $nextObj                        = $surnameObj->next();
    print ",\n      \"nextSurname\" : \"" .
        str_replace('"', '\\"', $nextObj->getSurname()) . "\"";

    if (isset($info['count']))
        $count		                = $info['count'];
    else
        $count                      = 0;
    print ",\n      \"count\" : $count";

    // iterate through results
    print ",\n    \"persons\" : {\n";
    $comma                          = '';
    $i                              = 0;
    foreach($result as $idir => $indiv)
    {		// loop through all result rows
        // check if current user is an owner of the record and therefore
        // permitted to see private information and edit the record
        $isOwner	        = $indiv->isOwner();
        $i++;

        // extract fields from individual 
        $surname	        = $indiv->get('indexsurname');
        $maidenname	        = $indiv->get('surname');
        $givenname	        = $indiv->get('givenname');
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
    		$bprivlim		= $currYear - 105;
    		$dprivlim		= $currYear - 27;
        }
        if ($birth)
        {
    		$birthsd	    = $birth->get('eventsd');
    		$birthd		    = $birth->getDate($bprivlim, $t);
        }
        else
        {
    		$birthd		    = '';
    		$birthsd	    = 99999999;
        }
        if ($death)
        {
    		$deathsd	    = $death->get('eventsd');
    		$deathd	        = $death->getDate($dprivlim, $t);
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
            print "$comma\n    \"$i\" : {\n        \"idir\" : $idir";
            $comma          = ',';
    		print ",\n        \"gender\" : ";
            switch($gender)
            {
                case 0:
                    print "\"male\"";
                    $child          = 'son';
                    $spouse         = 'husband';
                    break;
                case 1:
                    print "\"female\"";
                    $child          = 'daughter';
                    $spouse         = 'wife';
                    break;
                default:
                    print "\"unknown\"";
                    $child          = 'child';
                    $spouse         = 'spouse';
                    break;
            }
            $name                           = $surname;
            if (strlen($maidenname) > 0 && $maidenname != $surname)
    		    $name                       .= " ($maidenname)";
            $name                           .= ", $givenname";
            if (strlen($birthd) > 0 || strlen($deathd) > 0)
    		    $name                       .= " ($birthd\u{2014}$deathd)";

    		if ($private == 0)
    		{		// information is public
    		    // display names of parents
    		    if ($includeParents)
                {		// include parents' names
                    $nParms                 = array('trantab'       => $t,
                                                    'includedate'   => false,
                                                    'includeloc'    => false);
    				$parents	            = $indiv->getParents();
    				if (count($parents) > 0)
    				{
                        $conjunction	    = ' ' . $t[$child] . ' ' . 
                                                $t['of'] . ' ';
    				    foreach($parents as $idmr => $parent)
    				    {
                            $name           .= $conjunction . 
                                                $parent->getName($nParms);
    						$conjunction	= ', ' . $t['and'] . ' ';
    				    }	// loop through all sets of parents
    				}
    		    }		// include parents' names

    		    // display name of spouse
    		    if ($includeSpouse)
    		    {		// include spouse's name
    				$families	                = $indiv->getFamilies();
    				if (count($families) > 0)
    				{
    				    $conjunction	= ' ' . $t[$spouse] . ' ' . $t['of'] . ' ';
    				    foreach($families as $idmr => $family)
    				    {
    						if ($gender == 0)
    						    $spouseName	    = $family->getWifePriName();
    						else
    						    $spouseName	    = $family->getHusbPriName();
    						if ($spouseName)
    						{
    						    $name           .= $conjunction . $spouseName->getName();
    						    $conjunction	= ', ' . $t['and'] . ' ';
    						}
    				    }	// loop through all sets of families
    				}
                }		    // include spouse's name
                $name           = str_replace('"', '\\"', $name);
    		}		        // information is public
            print ",\n        \"name\" : \"$name\"}";
        }		            // existence of individual visible
    }			            // loop through all result rows
    print "\n}";

}			// no errors
else
{
    print "\"msg\" : \"$msg\"\n";
}
print "\n}\n";
