<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  getPersonSvg.php													*
 *																		*
 *  Get the information on an individual as an SVG response file so		*
 *  it can be displayed as a graphic									*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		idir			numeric key of individual						*
 *																		*
 *  History:															*
 *		2012/08/11		created											*
 *		2013/02/24		correct typo									*
 *		2013/06/01		remove use of deprecated interfaces				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/26		remove use of deprecated methods of				*
 *						LegacyFamily									*
 *						separate XML from PHP code						*
 *						generate unique id values for SVG elements		*
 *						display error messages in red text				*
 *						add a title for graphic							*
 *		2015/01/01		use getBirthEvent and gettDeathEvent			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *						script renamed to getPersonSvg.php				*
 *		2018/02/10		use Template for internationalization			*
 *		2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James Alan Cobban								*
 ************************************************************************/
header("Content-Type: image/svg+xml");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// for generating unique id attributes
$seqid	= 1;

/************************************************************************
 *  functiom display													*
 *																		*
 *  Display an individual along with their spouses, children, and		*
 *  parents																*
 *																		*
 *  Input:																*
 *		$idir			IDIR of individual							    *
 *		$x				X coordinate of top left corner of space		*
 *		$y				Y coordinate of top left corner of space		*
 ************************************************************************/
function display($idir, $x, $y)
{
    global	$seqid;
    global	$bprivlim;
    global	$dprivlim;
    
    $person		        = new Person(array('idir' => $idir));
    $families	        = $person->getFamilies();
    $allParents	        = $person->getParents();
    $familyCount	    = $families->count();
    if ($familyCount > 1)
    {		// at least two marriages
        // display 1st spouse to left of individual
        // display 2nd spouse to right, and so on ... 
    
        if (count($allParents) > 0)
        {	// display first set of parents
            // center prime under parents
            $mainy		    = $y + 200;
            displayParents($person, $x + 150, $y);
        }	// display first set of parents
        else
        {	// no parents
            $mainy		    = $y;
        }	// no parents
        $spousex		    = $x;
        $mainx		        = $x + 300;
    
        // display prime
        displayBox($person, 
        		    $mainx, 
        		    $mainy - (25 * $familyCount) + 40,
        		    200, 
        		    100 + 25 * max(0, $familyCount - 3));
    }		// at least two marriages
    else
    {		// no or only 1 marriage
        // display 1st spouse to right of prime individual
    
        if (count($allParents) > 0)
        {	// display first set of parents
            // center prime under parents
            $mainy		    = $y + 200;
            $mainx		    = $x + 150;
            displayParents($person, $x, $y);
        }	// display first set of parents
        else
        {	// no parents
            $mainx	        = $x;
            $mainy	        = $y;
        }	// no parents
    
        displayBox($person, $mainx, $mainy, 200, 100);
        $spousex	        = $mainx + 300;
    }		// no or only 1 marriage
    
    // show information about families in which this individual 
    // is a spouse or partner
    if ($familyCount > 0)
    {		// show spouses
        $im		            = 1;
        $cx		            = $x;
        $cy		            = $mainy + 200;
        foreach($families as $fidmr => $family)
        {		// loop through marriages
            if ($person->getGender() == Person::FEMALE)
            {		// female
        		$spsSurname	= $family->get('husbsurname');
        		$spsGiven	= $family->get('husbgivenname');
        		$spsid	    = $family->get('idirhusb');
            }		// female
            else
            {		// male
        		$spsSurname	= $family->get('wifesurname');
        		$spsGiven	= $family->get('wifegivenname');
        		$spsid	    = $family->get('idirwife');
            }		// male
    
            // information about spouse
            if ($spsid > 0)
            {
        		$spouse	        = new Person(array('idir' => $spsid));
        		if ($im > 1 && $spousex < $cx + 300)
        		    $spousex	= $cx + 300;
        		displayBox($spouse, $spousex, $mainy, 200, 100);
            }
    
            // draw connecting line to represent marriage link
            $starty	            = $mainy + 50;
            if ($im > 2)
            {
        		$startx	        = $spousex - 50;
        		$length	        = 25*$im;
        		$horizy	        = $starty - $length;
        		$length	        = $startx - $mainx - 200;
?>
    <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
            d='m <?php print $startx; ?>,<?php print $starty; ?> 0,-<?php print $length; ?> 0,0'
            id='path<?php print $seqid++; ?>' />
    <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
            d='m <?php print $startx; ?>,<?php print $horizy; ?> -<?php print $length; ?>,0 0,0'
            id='path<?php print $seqid++; ?>' />
<?php
        		$length	        = 50;
            }
            else
            if ($spousex > $mainx)
            {
        		$startx	        = $mainx + 200;
        		$length	        = $spousex - $startx;
            }
            else
            {
        		$startx	        = $spousex + 200;
        		$length	        = $mainx - $startx;
            }
?>
    <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
            d='m <?php print $startx; ?>,<?php print $starty; ?> <?php print $length; ?>,0 0,0'
            id='path<?php print $seqid++; ?>' />
<?php
            // display date of marriage
            $mdateo	            = new LegacyDate($family->get('mard'));
            $mdate	            = $mdateo->toString($dprivlim, false);
            $textx	            = min($startx + 10, 
        			              max($spousex, $mainx) - 90);
            $texty	            = $starty - 10;
?>
    <text id='text<?php print $seqid++; ?>'
            y='<?php print $texty; ?>' x='<?php print $textx; ?>'
            style='font-size:12px;font-style:normal;font-weight:normal;line-height:125%;letter-spacing:0px;word-spacing:0px;fill:#000000;fill-opacity:1;stroke:none;font-family:Sans'
            xml:space='preserve'>
      <tspan y='<?php print $texty; ?>' x='<?php print $textx; ?>'
            id='tspan<?php print $seqid++; ?>'>
<?php print $mdate; ?>
      </tspan>
    </text>
<?php
    
            // display information about children
            $children	        = $family->getChildren();
            $numChildren	    = $children->count();
            if ($numChildren > 0)
            {	// found at least one child record
        		// draw line down to line connecting children
        		if ($spousex > $mainx)
        		    $vertx	    = $spousex - 50;
        		else
        		    $vertx	    = $mainx - 50;
        		$starty	        = $mainy + 50;
        		$length	        = 100;
?>
    <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
            d='m <?php print $vertx; ?>,<?php print $starty; ?> 0,100 0,0'
            id='path<?php print $seqid++; ?>' />
<?php
        		// draw line connecting children
        		$startx	        = $cx + 100;
        		$starty	        = $mainy + 150;
        		$length	        = max(250 * ($numChildren - 1),
        				          $vertx - $startx) ;
?>
    <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
            d='m <?php print $startx; ?>,<?php print $starty; ?> <?php print $length; ?>,0 0,0'
            id='path<?php print $seqid++; ?>'/>
<?php
    
        		foreach($children as $cidir => $child)
        		{		// loop through all child records
        		    // draw line from line connecting children to child
        		    $startx	    = $cx + 100;
        		    $starty	    = $mainy + 150;
        		    $length	    = 50;
?>
    <path
    style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
    d='m <?php print $startx; ?>,<?php print $starty; ?> 0,50 0,0'
    id='path<?php print $seqid++; ?>' />
<?php
    
        		    $child	    = $child->getPerson();
        		    displayBox($child, $cx, $cy, 200, 100);
        		    $cx	        += 250;
        		}		// loop through all child records
            }		// found at least one child record
            else
        		$cx		        += 250;
            $im++;	// marriage index
    
            if ($spousex == $x)
        		$spousex	    += 600;
            else
        		$spousex	    += 300;
        }		// loop through marriages
    }			// show spouses
}		// function display

/************************************************************************
 *  function displayBox													*
 *																		*
 *  Display information about an individual in a box					*
 *																		*
 *  Input:																*
 *		$person		instance of Family									*
 *		$x		    X coordinate of top-left corner of box				*
 *		$y		    Y cooredinate of top-left corner of box				*
 *		$width		width of box										*
 *		$height		height of box										*
 ************************************************************************/
function displayBox($person, $x, $y, $width, $height)
{
    global	$seqid;
    global $bprivlim;
    global $dprivlim;
    
    $idir		= $person->getId();
    
    $textx		= $x + 15;
    $texty		= $y + 25;
    $sex		= $person->getGender();
    switch($sex)
    {
        case 0:
        {		// Male
            $fill	= '#c0c0ff';	// pale blue
            break;
        }		// Male
    
        case 1:
        {		// Female
            $fill	= '#ffc0c0';	// pale red
            break;
        }		// Female
    
        default:
        {		// Other
            $fill	= '#c0ffc0';	// pale green
            break;
        }		// Other
    }		// act on sex
    
    $surname	= $person->getSurname();
    $givenname	= $person->getGivenName();
    
    // check if current user is an owner of the record and therefore
    // permitted to see private information and edit the record
    $isOwner	= $person->isOwner();
    
    // privatize birth and date information if required
    if ($isOwner)
    {		// do not privatize dates
        $bprivlim	= 9999;
        $dprivlim	= 9999;
    }		// do not privatize dates
    else
    {		// privatize dates
        $bprivlim	= intval(date('Y')) - 100;
        $dprivlim	= intval(date('Y')) - 72;
    }		// privatize dates
    
    $evBirth		= $person->getBirthEvent();
    if ($evBirth)
        $bdateTxt	= $evBirth->getDate($bprivlim);
    else
        $bdateTxt	= '';
    $evDeath		= $person->getDeathEvent();
    if ($evDeath)
        $ddateTxt	= $evDeath->getDate($dprivlim);
    else
        $ddateTxt	= '';
    
     
?>
        <rect style='fill:<?php print $fill; ?>;fill-opacity:1;stroke:#000000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:4;stroke-opacity:1;stroke-dasharray:none'
        		id='rect<?php print $seqid++; ?>'
        		width='<?php print $width; ?>' height='<?php print $height; ?>'
        		x='<?php print $x; ?>' y='<?php print $y; ?>' />
        <a xlink:href='getPersonSvg.php?id=<?php print $idir; ?>'>
          <text id='text<?php print $seqid++; ?>'
        		y='<?php print $texty; ?>' x='<?php print $textx; ?>'
        		style='font-size:12px;font-style:normal;font-weight:normal;line-height:125%;letter-spacing:0px;word-spacing:0px;fill:#000000;fill-opacity:1;stroke:none;font-family:Sans'
        		xml:space='preserve'>
<?php
    
    // first line of text in the box
?>
            <tspan y='<?php print $texty; ?>' x='<?php print $textx; ?>'
        		id='tspan<?php print $seqid++; ?>'>
<?php print "$givenname $surname"; ?>
            </tspan>
<?php
    
    // second line of text in the box
    $texty	+= 15;
?>
            <tspan id='tspan<?php print $seqid++; ?>'
        		y='<?php print $texty; ?>' x='<?php print $textx; ?>'>
        		b: <?php print $bdateTxt; ?>
            </tspan>
<?php
    
    // third line of text in the box
    if (strlen($ddateTxt) > 0)
    {		// death known
        $texty	+= 15;
?>
            <tspan id='tspan<?php print $seqid++; ?>'
        		y='<?php print $texty; ?>' x='<?php print $textx; ?>'>
        		d: <?php print $ddateTxt; ?>
            </tspan>
<?php
    }		// death known
    
    // close text block
?>
          </text>
        </a>
<?php
}		// displayBox

/************************************************************************
 *  function displayParents												*
 *																		*
 *  Display the parents of an individual								*
 *																		*
 *  Input:																*
 *		$person		instance of Family									*
 *		$x		    X coordinate of top-left corner of box				*
 *		$y		    Y cooredinate of top-left corner of box				*
 ************************************************************************/
function displayParents($person, $x, $y)
{
    global	$seqid;
    global  $bprivlim;
    global  $dprivlim;
    
    $allParents	= $person->getParents();	// all sets of parents
    $parents	= $allParents->rewind();	// first set of parents
    $idirfather	= $parents->get('idirhusb');
    $idirmother	= $parents->get('idirwife');
    if ($idirfather > 0)
    {	// display father
        $father	= new Person(array('idir' => $idirfather)); 
        displayBox($father, $x, $y, 200, 100);
    }	// display father
    if ($idirmother > 0)
    {	// display mother
        $mother	= new Person(array('idir' => $idirmother)); 
        displayBox($mother, $x + 300, $y, 200, 100);
    }	// display mother
    
    // draw a line between the parents to represent their marriage
    $startx	= $x + 200;
    $starty	= $y + 50;
    $length	= 100;
?>
        <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
        		d='m <?php print $startx; ?>,<?php print $starty; ?> <?php print $length; ?>,0 0,0'
        		id='path<?php print $seqid++; ?>' />
<?php
    
    // display date of marriage
    $mdateo	= new LegacyDate($parents->get('mard'));
    $mdate	= $mdateo->toString($dprivlim, false);
    $textx	= $startx + 10; 
    $texty	= $starty - 10;
?>
        <text id='text<?php print $seqid++; ?>'
        		y='<?php print $texty; ?>' x='<?php print $textx; ?>'
        		style='font-size:12px;font-style:normal;font-weight:normal;line-height:125%;letter-spacing:0px;word-spacing:0px;fill:#000000;fill-opacity:1;stroke:none;font-family:Sans'
        		xml:space='preserve'>
          <tspan y='<?php print $texty; ?>' x='$textx'
        		id='tspan<?php print $seqid++; ?>'>
<?php print $mdate; ?>
          </tspan>
        </text>
<?php
    
    // draw a line down to the prime individual
    $startx	= $x + 250;
    $starty	= $y + 50;
    $length	= 150;
?>
        <path style='fill:#000000;stroke:#000000;stroke-width:2;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;stroke-miterlimit:4;stroke-dasharray:none'
        		d='m <?php print $startx; ?>,<?php print $starty; ?> 0,<?php print $length; ?> 0,0'
        		id='path<?php print $seqid++; ?>' />
<?php
}		// displayParents

/************************************************************************
 *  Open Code															*
 ************************************************************************/

$idir			    = null;
$idirtext		    = null;
$lang		        = 'en';

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {			    // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                            htmlspecialchars($value) . 
                        "</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific keys
	        case 'id':
	        case 'idir':
	        {
	    		if (ctype_digit($value) && $value > 0)
	    		    $idir		        = intval($value);
                else
                    $idirtext       = htmlspecialchars($value);
	    		break;
	        }
	
	        case 'lang':
	        {
	            $lang       = FtTemplate::validateLang($value);
	    		break;
	        }
	    }		    // act on specific keys
	}			    // loop through parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}			        // invoked by method=get

$tempBase		= $document_root . '/templates/';
$filename		= "getPersonSVG$lang.xml";
if (!file_exists($tempBase . $filename))
    $filename	= "getPersonSVGen.xml";
$template		= new Template($tempBase . $filename);
$template->set('TITLE', 'Graphical Family Tree');

if (is_string($idirtext))
    $msg	            .= "Invalid value $key='$value'. ";
else
if (is_null($idir))
    $msg	            .= 'Missing parameter idir=. ';

$width			        = 800;

if (strlen($msg) == 0)
{			// no errors so far
    $person		        = new Person(array('idir' => $idir));
    if ($person->isExisting())
    {
        $name		    = $person->getName();
        $totChildren	= 0;

        // show information about families in which this individual 
        // is a spouse or partner
        $families		= $person->getFamilies();
        foreach($families as $fidmr => $family)
        {		// loop through marriages
    		$children	= $family->getChildren();
    		$totChildren	+= max($children->count(), 1);
        }		// loop through marriages
        $width	= max(max(400 + 250 * $totChildren, 700), 
    				500 + 300*$families->count());
    }
    else
    {
        $msg	        .= "IDIR='$value' does not identify an existing Person in the family tree. ";
    }
}			// no errors so far

// display the results

if (strlen($msg) > 0)
{		// display error message
    $template->updateTag('text_seqid_1', array('msg'	=> $msg));
    $template->updateTag('text_seqid_3', null);
    $template->updateTag('text_seqid_5', null);
    $template->set('TREE', '');
    $template->updateTag('text_seqid_7', null);
}		// display error message
else
{		// display a box for the individual
    $template->updateTag('text_seqid_1', null);
    $template->updateTag('text_seqid_3', array('name'	=> $name));
    ob_start();
    display($idir, 140, 100);
    $template->set('TREE', ob_get_clean());
    $template->updateTag('text_seqid_7', null);
}		// display a box for the individual

$template->display();
