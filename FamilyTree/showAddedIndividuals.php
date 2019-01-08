<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  showAddedIndividuals.php											*
 *																		*
 *  Display a list of individuals added to the database during a		*
 *  given week.															*
 *																		*
 *  History:															*
 *		2012/08/23		created											*
 *		2013/05/17		use pageTop and pageBot functions to standardize*
 *						appearance										*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS instead of tables for layout			*
 *						display the information about the individual	*
 *						in an anecdotal form rather than in columns		*
 *						report the names of parents						*
 *						add popup identifiers over the forward and		*
 *						backward week links								*
 *						add link to overall help page					*
 *		2014/08/28		use "added" field of tblIR to select matches	*
 *						add spouse information							*
 *						display date range with names of months			*
 *		2014/09/04		use LegacyIndiv::getIndivs instead of SQL		*
 *						reject parameter week=0							*
 *						display individuals for 7 day period, not 8		*
 *						do not display link to next week for this week	*
 *		2014/12/03		print $warn, which may contain debug trace		*
 *		2014/12/30		LegacyIndiv::getFamilies and					*
 *						LegacyIndiv::getParents return array indexed	*
 *						on IDMR											*
 *		2015/01/01		use new getBirthDate and getDeathDate			*
 *		2015/03/08		use LegacyFamily::getHusbName and getWifeName	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/12/12		use class PersonSet in place of					*
 *						Person::getPersons								*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    // process parameters
    $week			= 1;
    $lang			= 'en';

    foreach($_GET as $key => $value)
    {			// loop through all parameters
		switch (strtolower($key))
		{		// act on individual parameters
		    case 'week':
		    {
				if (ctype_digit($value) && ($value > 0))
				    $week	= (int)$value;
				else
				{
				    $msg	.= "Unexpected value week='$value'. ";
				    $week	= 1;
				}
				break;
		    }		// week

		    case 'date':
		    {
				$week		= null;
				$start		= strtotime($value);
				$end		= strtotime('+1 day', $start);
				$startdate	= strftime('%Y %b %d', $start);
				$startday	= intval(strftime('%Y%m%d', $start));
				$enddate	= strftime('%Y %b %d', $end);
				$endday		= intval(strftime('%Y%m%d', $end));
				break;
		    }

		    case 'debug':
		    {		// already processed by common code
				break;
		    }		// already processed by common code

		    case 'lang':
		    {		// already processed by common code
				$lang		= strtolower(substr($value, 0, 2));
				break;
		    }		// already processed by common code

		    default:
		    {
				$msg	.= "Unexpected parameter $key='$value'. ";
				break;
		    }
		}		// act on individual parameters
    }			// loop through all parameters

    if (isset($week))
    {
		$now		= time();
		$aday		= 24 * 60 * 60;
		$aweek		= 7 * $aday;
		$start		= $now - $week * $aweek;
		$end		= $start + $aweek;
		$start		+= $aday;
		$startdate	= strftime('%Y %b %d', $start);
		$startday	= intval(strftime('%Y%m%d', $start));
		$enddate	= strftime('%Y %b %d', $end);
		$endday		= intval(strftime('%Y%m%d', $end));
		$prev		= $week + 1;
		$next		= $week - 1;
    }

    $getparms	= array('added'		=> array($startday, $endday),
					'order'		=> 'Surname, givenName, birthSD');
    $persons	= new PersonSet($getparms);
    $info	= $persons->getInformation();

    $title	= "Display Persons Added Between $startdate and $enddate";

    htmlHeader($title, 
				array(	'/jscripts/js20/http.js',
					'/jscripts/util.js',
				      'showAddedIndividuals.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="showAddedIndividualsHelpen.html" target="help">? Help</a>
      </span>
      <?php print $title; ?>
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class="message"><?php print $msg; ?>
<?php
    }
    else
    {			// no error
		$count	= $info['count'];
		if (isset($week))
		{
?>
    <div class="center">
		<div class="left" id="toPrevWeek">
		    <a href="showAddedIndividuals.php?week=<?php print $prev; ?>">&lt;---</a>
		</div>
<?php
		    if ($next > 0)
		    {		// some time in the past
?>
		<div class="right" id="toNextWeek">
		    <a href="showAddedIndividuals.php?week=<?php print $next; ?>">---&gt;</a></td>
		</div>
<?php
		    }		// some time in the past
		}		// week set

		if ($count == 0)
		{
?>
    No matches for that period
		<div style="clear: both;"></div>
    </div>
<?php
		}
		else
		{	// some matches
?>
    <?php print $count; ?> matches for that period
		<div style="clear: both;"></div>
    </div>
<?php
		    $style	= 'odd';
		    foreach($persons as $idir => $person)
		    {	// loop through all rows
				try {
				$idir		= $person->get('idir');
				$surname	= $person->get('surname');
				$givenname	= $person->get('givenname');
				$birthd		= $person->getBirthDate();
				$deathd		= $person->getDeathDate();
				$gender		= $person->get('gender');
				if ($gender == 0)
				{
				    $gender	= 'male';
				    $childRole	= 'son';
				    $spouseRole	= 'husband';
				}
				else
				if ($gender == 1)
				{
				    $gender	= 'female';
				    $childRole	= 'daughter';
				    $spouseRole	= 'wife';
				}
				else
				{
				    $gender	= 'unknown';
				    $childRole	= 'child';
				    $spouseRole	= 'partner';
				}
				
				if (strlen($surname) == 0 &&
				    strlen($givenname) == 0 &&
				    strlen($birthd) == 0 &&
				    strlen($deathd) == 0)
				{		// ignore empty entry
				}		// ignore empty entry
				else
				{		// a real entry
				    $parentsStr	= '';
				    $parents	= $person->getParents();
				    foreach($parents as $pidmr => $family)
				    {		// loop through parents
					$parentsStr	.= ' ' . $childRole . " of " .
								$family->getName();
				    }		// loop through parents

				    $spouseStr	= '';
				    $spouse	= $person->getFamilies();
				    foreach($spouse as $pidmr => $family)
				    {		// loop through spouses
					if ($gender == 'male')
					{
					    $spouseStr	.= " husband of " .
						$family->getWifeName();
					}
					else
					if ($gender == 'female')
					{
					    $spouseStr	.= " wife of " .
						$family->getHusbName();
					}
					else
					{
					    $spouseStr	.= " partner of " .
						$family->getName();
					}
				    }		// loop through spouses
?>
		  <p style="margin-left:6em; text-indent:-3em; margin-top:0; margin-bottom:0.5ex;">
		    <a href="Person.php?idir=<?php print $idir; ?>"
				class="<?php print $gender; ?>" target="_blank">
				<?php print "$surname, $givenname ($birthd-$deathd)" ?>
		    </a>
				<?php print $parentsStr; ?>
				<?php print $spouseStr; ?>
		  </p>
<?php
				    if ($style == 'odd')
					$style	= 'even';
				    else
					$style	= 'odd';
				}		// a real entry
				} catch (Exception $e) {
?>
    <span class="message"><?php $e->getMessage(); ?></span>
<?php
				}		// catch
		    }	// loop through all rows
?>
      <tbody>
    </table>
<?php
		}	// some matches
    }		// no errors
?>
</div>
<?php
    pageBot($title);
?>
  <div id="mousetoPrevWeek" class="popup">
    <p class="label">Go to Previous Week
    </p>
  </div>
  <div id="mousetoNextWeek" class="popup">
    <p class="label">Go to Next Week
    </p>
  </div>
  <div class="balloon" id="HelpSurname">
    <p>Edit the surname of the individual.  Note that changing the surname
		causes a number of other fields and records to be updated.  In
		particular the Soundex value, stored in field 'SoundsLike' in the
		individual records is updated.  Also if the surname does not
		already appear in the database, a record is added into the table
		'tblNR'.
    </p>
</div>
</body>
</html>
