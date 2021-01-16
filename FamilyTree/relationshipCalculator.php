<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  relationshipCalculator.php											*
 *																		*
 *  Display a web page reporting the degree of relationship between		*
 *  two individuals.													*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		idir1			unique identifier of the first individual		*
 *		idir2			unique identifier of the second individual		*
 *																		*
 * History:																*
 *		2010/12/29		created											*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/01/05		do not fail if no spouse defined				*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/01		change legacyIndex.html to legacyIndex.php		*
 *		2013/08/01		invoked as popup dialog, so no top and bottom	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		use CSS for form layout instead of tables		*
 *		2014/03/21		remove deprecated LegacyIndiv::getNumParents,	*
 *						and getNumMarriages								*
 *		2014/04/04		LegacyIndiv::getMarriages renamed to getFamilies*
 *		2014/04/26		formUtil.inc obsoleted							*
 *	    2014/08/14	    wrong birth and death dates for second indiv	*	
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2015/01/01		use new getBirthDate and getDeathDate			*
 *		2015/01/11		if invoked without idir2, redirect back to		*
 *						search menu with updated name parameter			*
 *		2015/01/23		add close button								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/03/19		use preferred parms for new LegacyIndiv			*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  setAncestors														*
 *																		*
 *  Record in the table of ancestors for individual 1 that the parents	*
 *  of an ancestor identified by the first parameter are ancestors of	*
 *  the indicated degree.												*
 *																		*
 *  Parameters:															*
 *		$person		individual whose parents are to be set to $degree   *
 *		$degree		degree of ancestorhood.							    *
 *					0		self										*
 *					0x8000		spouse									*
 *					1		parent										*
 *					0x8001		parent-in-law							*
 *					2		grand-parent								*
 *					3		great-grand-parent						    *
 *						...												*
 *																		*
 ************************************************************************/
function setAncestors($person, $degree)
{
    global $ancestors;

    $parents	= $person->getParents();
    foreach($parents as $ip => $family)
    {		// loop through parents
		$idirfather	= $family->get('idirhusb');
		if ($idirfather > 0)
		{		// father exists
		    $ancestors[$idirfather]	= $degree;
		    $father	= new Person(array('idir' => $idirfather));
		    if ($degree < 0x8000)
				setAncestors($father, $degree + 1);
		}		// father exists
		$idirmother	= $family->get('idirwife');
		if ($idirmother > 0)
		{		// mother exists
		    $ancestors[$idirmother]	= $degree;
		    $mother	= new Person(array('idir' => $idirmother));
		    if ($degree < 0x8000)
				setAncestors($mother, $degree + 1);
		}		// father exists
    }		// loop through parents
}		// setAncestors

/************************************************************************
 *  $relTable															*
 *																		*
 *  Two dimensional array where first index is level of ancestry from	*
 *  $person1, as obtained from the array element $ancestors, and the	*
 *  second index is the level of ancestry from $person2.				*
 ************************************************************************/
$relTable	= array(0	=> array(0	=> 'self',
							 1	=> 'parent',
							 2	=> 'grandparent',
							 3	=> 'great-grandparent',
							 4	=> 'great-great-grandparent',
							 5	=> 'great^3-grandparent',
							 6	=> 'great^4-grandparent',
							 7	=> 'great^5-grandparent',
							 8	=> 'great^6-grandparent',
							 9	=> 'great^7-grandparent',
							10	=> 'great^8-grandparent',
					     32768	=> 'spouse',
					     32769	=> 'parent-in-law',
					     32770	=> 'grandparent',
					     32771	=> 'great-grandparent',
					    0x8004	=> 'great-great-grandparent',
					    0x8005	=> 'great^3-grandparent',
					    0x8006	=> 'great^4-grandparent',
					    0x8007	=> 'great^5-grandparent',
					    0x8008	=> 'great^6-grandparent',
					    0x8009	=> 'great^7-grandparent',
					    0x800A	=> 'great^8-grandparent'),
				    32768	=> array(0	=> 'spouse',
						 	 1	=> 'parent',
							 2	=> 'grandparent-in-law',
							 3	=> 'great-grandparent-in-law',
							 4	=> 'great-great-grandparent-in-law',
							 5	=> 'great^3-grandparent-in-law',
							 6	=> 'great^4-grandparent-in-law',
							 7	=> 'great^5-grandparent-in-law',
							 8	=> 'great^6-grandparent-in-law',
							 9	=> 'great^7-grandparent-in-law',
							10	=> 'great^8-grandparent-in-law'),
	    		    32769	=> array(0	=> 'child-in-law',
						 	     1	=> 'sibling-in-law'),
					1	=> array(0	=> 'child',
							 1	=> 'sibling',
							 2	=> 'uncle',
							 3	=> 'great-uncle',
							 4	=> 'great-great-uncle',
							 5	=> 'great^3-uncle',
							 6	=> 'great^4-uncle',
							 7	=> 'great^5-uncle',
							 8	=> 'great^6-uncle',
							 9	=> 'great^7-uncle',
							10	=> 'great^8-uncle',
					     32768	=> 'child',
					     32769	=> 'sibling-in-law',
					     32770	=> 'uncle',
					     32771	=> 'great-uncle',
					    0x8004	=> 'great-great-uncle',
					    0x8005	=> 'great^3-uncle',
					    0x8006	=> 'great^4-uncle',
					    0x8007	=> 'great^5-uncle',
					    0x8008	=> 'great^6-uncle',
					    0x8009	=> 'great^7-uncle',
					    0x800A	=> 'great^8-uncle'),

					2	=> array(0	=> 'grandchild',
							 1	=> 'nephew',
							 2	=> 'cousin',
							 3	=> '1st-cousin once removed',
							 4	=> '1st-cousin twice removed',
							 5	=> '1st-cousin thrice removed',
							 6	=> '1st-cousin 4 times removed',
							 7	=> '1st-cousin 5 times removed',
							 8	=> '1st-cousin 6 times removed',
							 9	=> '1st-cousin 7 times removed',
							10	=> '1st-cousin 8 times removed',
					     32768	=> 'grandchild',
					     32769	=> 'nephew',
					     32770	=> 'cousin-by-marriage',
					     32771	=> '1st-cousin-once removed by-marriage',
					    0x8004	=> '1st-cousin twice removed by-marriage',
					    0x8005	=> '1st-cousin thrice removed by-marriage',
					    0x8006	=> '1st-cousin 4 times removed by-marriage',
					    0x8007	=> '1st-cousin 5 times removed by-marriage',
					    0x8008	=> '1st-cousin 6 times removed by-marriage',
					    0x8009	=> '1st-cousin 7 times removed by-marriage',
					    0x800A	=> '1st-cousin 8 times removed by marriage'),
					3	=> array(0	=> 'great-grandchild',
							 1	=> 'great-nephew',
							 2	=> '1st-cousin once removed',
							 3	=> '2nd-cousin',
							 4	=> '2nd-cousin once removed',
							 5	=> '2nd-cousin twice removed',
							 6	=> '2nd-cousin 3 times removed',
							 7	=> '2nd-cousin 4 times removed',
							 8	=> '2nd-cousin 5 times removed',
							 9	=> '2nd-cousin 6 times removed',
							10	=> '2nd-cousin 7 times removed',
					     32768	=> 'great-grandchild',
					     32769	=> 'great-nephew',
					     32770	=> '1st-cousin-once-removed by-marriage',
					     32771	=> '2nd-cousin by-marriage',
					    0x8004	=> '2nd-cousin once-removed by-marriage',
					    0x8005	=> '2nd-cousin twice removed by-marriage',
					    0x8006	=> '2nd-cousin 3 times removed by-marriage',
					    0x8007	=> '2nd-cousin 4 times removed by-marriage',
					    0x8008	=> '2nd-cousin 5 times removed by-marriage',
					    0x8009	=> '2nd-cousin 6 times removed by-marriage',
					    0x800A	=> '2nd-cousin 7 times removed by marriage'),
					4	=> array(0	=> 'great-great-grandchild',
							 1	=> 'great-great-nephew',
							 2	=> '1st-cousin twice removed',
							 3	=> '2nd-cousin once removed',
							 4	=> '3rd-cousin',
							 5	=> '3rd-cousin once removed',
							 6	=> '3rd-cousin twice removed',
							 7	=> '3rd-cousin 3 times removed',
							 8	=> '3rd-cousin 4 times removed',
							 9	=> '3rd-cousin 5 times removed',
							10	=> '3rd-cousin 6 times removed',
					     32768	=> 'great-great-grandchild',
					     32769	=> 'great-great-nephew',
					     32770	=> '1st-cousin twice-removed by-marriage',
					     32771	=> '2nd-cousin once-removed by-marriage',
					    0x8004	=> '3rd-cousin by-marriage',
					    0x8005	=> '3rd-cousin once removed by-marriage',
					    0x8006	=> '3rd-cousin twice removed by-marriage',
					    0x8007	=> '3rd-cousin 3 times removed by-marriage',
					    0x8008	=> '3rd-cousin 4 times removed by-marriage',
					    0x8009	=> '3rd-cousin 5 times removed by-marriage',
					    0x800A	=> '3rd-cousin 6 times removed by marriage'),
					5	=> array(0	=> 'great^3-grandchild',
							 1	=> 'great^3-nephew',
							 2	=> '1st-cousin 3 times removed',
							 3	=> '2nd-cousin twice removed',
							 4	=> '3rd-cousin once removed',
							 5	=> '4th-cousin',
							 6	=> '4th-cousin once removed',
							 7	=> '4th-cousin twice removed',
							 8	=> '4th-cousin 3 times removed',
							 9	=> '4th-cousin 4 times removed',
							10	=> '4th-cousin 5 times removed',
						    32768	=> 'great^3-grandchild',
						    32769	=> 'great^3-nephew'),
					6	=> array(0	=> 'great^4-grandchild',
							 1	=> 'great^4-nephew',
							 2	=> '1st-cousin 4 times removed',
							 3	=> '2nd-cousin 3 times removed',
							 4	=> '3rd-cousin twice removed',
							 5	=> '4th-cousin once removed',
							 6	=> '5th-cousin',
							 7	=> '5th-cousin once removed',
							 8	=> '5th-cousin twice removed',
							 9	=> '5th-cousin 3 times removed',
							10	=> '5th-cousin 4 times removed',
					     32768	=> 'great^3-great-grandchild',
					     32769	=> 'great^3-great-nephew'),
					7	=> array(0	=> 'great^5-grandchild',
							 1	=> 'great^5-nephew',
							 2	=> '1st-cousin 5 times removed',
							 3	=> '2nd-cousin 4 times removed',
							 4	=> '3rd-cousin 3 times removed',
							 5	=> '4th-cousin twice removed',
							 6	=> '5th-cousin once removed',
							 7	=> '6th-cousin',
							 8	=> '6th-cousin once removed',
							 9	=> '6th-cousin twice removed',
							10	=> '6th-cousin 3 times removed',
					     32768	=> 'great^5-grandchild',
					     32769	=> 'great^5-nephew'),
				);

/************************************************************************
 *  chkAncestors														*
 *																		*
 *  Check the table of ancestors for individual 1 to see if an ancestor	*
 *  of individual 2 is present.											*
 *																		*
 *  Parameters:															*
 *		$person			individual whose parents are to be checked		*
 *		$degree			degree of ancestorhood							*
 *																		*
 ************************************************************************/
function chkAncestors($person, $degree, $chkspouses = true)
{
    global $ancestors;
    global $person1;
    global $relTable;
    global $commonAncestor;

    if (is_null($person))
		throw new Exception("chkAncestors: null individual");

    $idir	= $person->getIdir();
    $rel1	= null;
    // print "<p>chkAncestors($idir, $degree)";
    if (array_key_exists($idir, $ancestors))
    {		// $person is a common ancestor of $person1 and $person2
		$commonAncestor	= $person;
		$rel1	= $ancestors[$idir];
		//print '<p>common ancestor $ancestors[' . $idir . '] is ' . $rel1 .
		//	", $degree=" . $degree;
		if (array_key_exists($rel1, $relTable) &&
		    array_key_exists($degree, $relTable[$rel1]))
		    return ' is the ' . $relTable[$rel1][$degree] . ' of ';

		if ($rel1 & 0x8000)
		    $bymarriage1	= 'by marriage ';
		else
		    $bymarriage1	= '';
		if ($degree & 0x8000)
		    $bymarriage2	= 'by marriage ';
		else
		    $bymarriage2	= '';
		return "'s " . $rel1 .
				'th ancestor ' . $bymarriage1 .
				$person->getGivenName() . ' ' . $person->getSurname() .
				' is the ' . ($degree & 0x7FFF) . 
				'th ancestor ' . $bymarriage2 .'of ';
    }		// $person is a common ancestor of $person1 and $person2

    // keep searching, try ancestors
    $parents	= $person->getParents();
    foreach($parents as $ip => $family)
    {
		$idirfather	= $family->get('idirhusb');
		if ($idirfather > 0)
		{		// father exists
		    $father	= new Person(array('idir' => $idirfather));
		    $result	= chkAncestors($father, $degree + 1);
		    if (strlen($result) > 0)
				return $result;
		}		// father exists
		$idirmother	= $family->get('idirwife');
		if ($idirmother > 0)
		{		// mother exists
		    $mother	= new Person(array('idir' => $idirmother));
		    $result	= chkAncestors($mother, $degree + 1);
		    if (strlen($result) > 0)
				return $result;
		}		// mother exists
    }			// loop through sets of parents


    // keep searching, try spouses
    $families	= $person->getFamilies();
    if ($chkspouses)
    {
		foreach($families as $if => $family)
		{
		    if ($person->getGender() == Person::MALE)
				$spouseidir	= $family->get('idirwife');
		    else
				$spouseidir	= $family->get('idirhusb');
		    if ($spouseidir > 0)
		    {		// has a spouse
				$spouse	= new Person(array('idir' => $spouseidir));
				$result	= chkAncestors($spouse, 0x8000 | $degree, false);
				if (strlen($result) > 0)
				    return $result;
		    }		// has a spouse
		}		// loop through marriages
    }			// check spouses

    return '';	// no relationship found in this branch
}	// chkAncestors

// gender class for hyper-links
$genderClass	= array('male', 'female', 'male');

// validate input
$idir		    			= null;
$idirtext	    			= null;
$name		    			= null;
$idir1		    			= null;
$idir1text	    			= null;
$person1					= null;
$name1		    			= '';
$surname1					= '';
$nameuri					= '';
$prefix		    			= '';
$gender1					= 0;
$idir2		    			= null;
$idir2text	    			= null;
$person2					= null;
$name2		    			= '';
$surname2					= '';
$nameuri					= '';
$prefix		    			= '';
$gender2					= 0;
$isOwner					= true;

foreach($_GET as $key => $value)
{
  switch($key)
  {
		case 'idir1':
        {
            if (ctype_digit($value) && $value > 0)
                $idir1	        = intval($value);
            else
                $idir1text      = htmlspecialchars($value);
		    break;
		}		// idir1

		case 'idir2':
		{
            if (ctype_digit($value) && $value > 0)
                $idir2	        = intval($value);
            else
                $idir2text      = htmlspecialchars($value);
		    break;
		}		    // idir2

		case 'Name':
		{
		    $name	        = $value;
		    break;
		}		    // Name
    }			    // switch on key
}			        // loop through all parameters

if (is_string($idir1text))
    $msg            .= "Invalid value for IDIR1=$idir1text. ";
else
if (is_int($idir1))
{
	$person1		= new Person(array('idir' => $idir1));
	$isOwner	    = $isOwner && $person1->isOwner();
	$name1		    = $person1->getName(Person::NAME_INCLUDE_DATES);
	$surname1	    = $person1->getSurname();
	$nameuri	    = rawurlencode($name1);
	if (strlen($surname1) == 0)
	    $prefix	    = '';
	else
	if (substr($surname1, 0, 2) == 'Mc')
	    $prefix	    = 'Mc';
	else
	    $prefix	    = substr($surname1, 0, 1);
	$gender1	    = $person1->getGender();
}
else
{
	$name1	        = "Missing parameter IDIR1";
	$msg	        = "Missing parameter IDIR1. ";
}		            // missing idir1

if (is_string($idir2text))
    $msg            .= "Invalid value for IDIR2=$idir2text. ";
else
if (is_int($idir2))
{
	$person2		= new Person(array('idir' => $idir2));
	$isOwner	    = $isOwner && $person2->isOwner();
	$name2		    = $person2->getName(Person::NAME_INCLUDE_DATES);
	$surname2	    = $person2->getSurname();
	$nameuri	    = rawurlencode($name2);
	if (strlen($surname2) == 0)
	    $prefix	    = '';
	else
	if (substr($surname2, 0, 2) == 'Mc')
	    $prefix	    = 'Mc';
	else
	    $prefix	    = substr($surname2, 0, 1);
	$gender2	    = $person2->getGender();
}
else
{
	$name2	    = "Missing parameter IDIR2";
	$msg	    .= "Missing parameter IDIR2. ";
}		            // missing idir2

$title	        = "Relationship of $name1 to $name2";

if ($person1 instanceof Person && is_null($person2))
{
		if (!is_null($name))
    {		// redirect to search menu
        $name       = urlencode($name); 
		    header("Location: chooseRelative.php?name=$name&idir=$idir1");
		    exit;
		}		// redirect to search menu 
}

if (strlen($msg) == 0)
{			// no errors
	//	create a representation of the relationship of the first
	//	individual to all ancestors and ancestors of spouses
	//
	//	Examples:
	//	    $ancestors[n] = 3 means individual n is great-grand-parent
	//	    $ancestors[n] = 0x8000 + 1 means individual is father/mother-in-law
	$ancestors	        = array();
	$ancestors[$idir1]	= 0;	// identify self
	setAncestors($person1, 1);	// identify ancestors
	
	$families	        = $person1->getFamilies();
	foreach($families as $if => $family)
	{		// loop through marriages
	    if ($person1->getGender() == Person::MALE)
			$spouseIdir	= $family->get('idirwife');
	    else
			$spouseIdir	= $family->get('idirhusb');
	    if ($spouseIdir > 0)
	    {
			$spouse	    = new Person(array('idir' => $spouseIdir));
			$ancestors[$spouseIdir]	= 0x8000;	// spouse
			setAncestors($spouse, 0x8000 + 1);		// in-laws
	    }
	}		// loop through marriages
	
	$commonAncestor	    = null;
	$relation	        = chkAncestors($person2, 0);
	if (strlen($relation) == 0)
		$relation	= ' no known relation to';
	else
	{		// adjust for sex of first individual
		if ($person1->getGender() == Person::MALE)
		{
		    $relation	= str_replace(
					array('child', 'spouse', 'sibling', 'parent', 'uncle', 'nephew'),
					array('son', 'husband', 'brother', 'father', 'uncle', 'nephew'),
					$relation);
		}		// 1st individual is male
		else
		{
		    $relation	= str_replace(
					array('child', 'spouse', 'sibling', 'parent', 'uncle', 'nephew'),
					array('daughter', 'wife', 'sister', 'mother', 'aunt', 'niece'),
					$relation);
		}		// 1st individual is female
	}		// adjust for sex of first individual
}			// no errors

htmlHeader($title,
			array(	'/jscripts/js20/http.js',
				    '/jscripts/util.js',
  			        'relationshipCalculator.js'));
?>
<body>
  <div class="body">
    <h1>
      <span class="right">
		<a href="relationshipCalculatorHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?> 
    </h1>
<?php
    showTrace();
 
    if (strlen($msg) > 0)
    {
?>
  <p class="message">
		<?php print $msg; ?> 
  </p>
<?php
    }		// error message to display
    else
    {		// display relationship
?>
    <form name="depthForm" id="depthForm" action="donothing.php"
				method="get">
      <div class="row">
		<button type="button" id="Close" accessKey="C">
		  <u>C</u>lose
		</button>
      </div>
    </form>
  <p><a href="Person.php?idir=<?php print $idir1; ?>" class="<?php print $genderClass[$gender1]; ?>"><?php print "$name1"; ?></a><?php print $relation; ?>
		<a href="Person.php?idir=<?php print $idir2; ?>" class="<?php print $genderClass[$gender2]; ?>"><?php print "$name2"; ?></a>.
<?php
		if (!is_null($commonAncestor))
		{
		    $namec	= $commonAncestor->getName(Person::NAME_INCLUDE_DATES);
?>
    Their common ancestor is <a href="Person.php?idir=<?php print $commonAncestor->getIdir(); ?>" class="<?php print $genderClass[$commonAncestor->getGender()];?>"><?php print "$namec"; ?></a>.
<?php
		}	// have a common ancestor
?>
  </p>
<?php
    }		// display relationship
?>
</div>
<?php
    dialogBot();
?>
<div id="HelpClose" class="balloon">
Click on this button to close the dialog.  Keyboard shortcuts are Alt-C and
Alt-Shift-C.
</div>
</body>
</html>
