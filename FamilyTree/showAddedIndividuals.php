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
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/08/30      use Template                                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/PersonSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// process parameters
$week			= 1;
$weektext       = null;
$lang			= 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display information
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch (strtolower($key))
		{		    // act on individual parameters
		    case 'week':
            {
                if (strlen($value) > 0)
                {
					if (ctype_digit($value) && ($value > 0))
					    $week	    = (int)$value;
					else
	                {
	                    $weektext   = htmlspecialchars($value);
					    $week	    = 1;
	                }
                }
				break;
		    }		// week

		    case 'date':
		    {
				$week				= null;
				$start				= strtotime($value);
				$end				= strtotime('+1 day', $start);
				$startdate			= strftime('%Y %b %d', $start);
				$startday			= intval(strftime('%Y%m%d', $start));
				$enddate			= strftime('%Y %b %d', $end);
				$endday				= intval(strftime('%Y%m%d', $end));
				break;
		    }

		    case 'debug':
		    {		// already processed by common code
				break;
		    }		// already processed by common code

		    case 'lang':
		    {		// standard handling
                $lang       = FtTemplate::validateLang($value);
				break;
		    }		// stanard handling

		    default:
		    {
                $warn	.= "<p>Unexpected parameter $key='" .
                    htmlspecialchars($value) . "' ignored.</p>\n";
				break;
		    }
		}		    // act on individual parameters
    }			    // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display information

$template           = new FtTemplate("showAddedIndividuals$lang.html");
$tranTab            = $template->getTranslate();
$t                  = $tranTab['tranTab'];

$template['otherStylesheets']->update(array('filename'  => 'showAddedIndiviuals'));

if (isset($weektext))
    $warn           .= "<p>Invalid week=\"$weektext\" ignored.</p>\n";

if (isset($week))
{
	$now			= time();
	$aday			= 24 * 60 * 60;
	$aweek			= 7 * $aday;
	$start			= $now - $week * $aweek;
	$end			= $start + $aweek;
	$start		    += $aday;
	$startdate		= strftime('%Y %b %d', $start);
	$startday		= intval(strftime('%Y%m%d', $start));
	$enddate		= strftime('%Y %b %d', $end);
	$endday			= intval(strftime('%Y%m%d', $end));
	$prev			= $week + 1;
	$next			= $week - 1;
}

$template->set('startdate',         $startdate);
$template->set('enddate',           $enddate);
$template->set('LANG',              $lang);

if(strlen($msg) == 0)
{
    $getparms	    = array('added'		=> array($startday, $endday),
	      			        'order'		=> 'Surname, givenName, birthSD',
		    		        'offset'    => 0,
			    	        'limit'     => 20);
	$persons	    = new PersonSet($getparms);
    $info	        = $persons->getInformation();

	$count	        = $info['count'];
	if (isset($week))
    {
        $template->set('prev',          $prev);
        $template->set('next',          $next);
    }		// week set
    else
    {
        $template['toPrevWeek']->update(null);
        $template['toNextWeek']->update(null);
    }

	if ($count == 0)
    {
        $template['someMatches']->update(null);
        $template['person']->update(null);
	}
	else
	{	            // some matches
        $template['noMatches']->update(null);
        $template['someMatches']->update(array('count'  => $count));

        $of                 = $t['of'];
        $husband            = $t['husband'];
        $wife               = $t['wife'];
        $spouse             = $t['spouse'];
        $style	            = 'odd';
        $parms              = array();
	    foreach($persons as $idir => $person)
        {	        // loop through all rows
			$gender		    = $person['gender'];
			if ($gender == 0)
			{
			    $gender	    = 'male';
			    $childRole	= $t['son'];
			    $spouseRole	= $husband;
			}
			else
			if ($gender == 1)
			{
			    $gender	    = 'female';
			    $childRole	= $t['daughter'];
			    $spouseRole	= $wife;
			}
			else
			{
			    $gender	    = 'unknown';
			    $childRole	= $t['child'];
			    $spouseRole	= $spouse;
			}
			
			$surname		    = $person['surname'];
			$givenname		    = $person['givenname'];
            $evBirth		    = $person->getBirthEvent();
            if ($evBirth)
                $bdate          = $evBirth->getDate($person->getBPrivLim(), $t);
            else
                $bdate          = '';
            $evDeath		    = $person->getDeathEvent();
            if ($evDeath)
                $ddate          = $evDeath->getDate($person->getDPrivLim(), $t);
            else
                $ddate          = '';
//			if (strlen($surname) > 0 ||
//			    strlen($givenname) > 0 ||
//			    strlen($birthd) > 0 ||
//			    strlen($deathd) > 0)
			{		// a real entry
                $parentsStr	        = '';
                $comma              = '';
			    $parents	        = $person->getParents();
			    foreach($parents as $pidmr => $family)
			    {		// loop through parents
					$parentsStr	    .= "$comma$childRole $of " .
                                       $family->getName($t);
                    $comma          = ', ';
			    }		// loop through parents

			    $spouseStr	        = '';
                if (strlen($parentsStr) > 0)
                    $comma          = ', ' . $t['and'] . ' ';
                else
                    $comma          = '';
			    $spouse	            = $person->getFamilies();
			    foreach($spouse as $pidmr => $family)
                {		// loop through spouses
                    $spouseStr      .= $comma;
					if ($gender == 'male')
					{
					    $spouseStr	.= " $husband $of " .
						$family->getWifeName();
					}
					else
					if ($gender == 'female')
					{
					    $spouseStr	.= " $wife $of " .
						$family->getHusbName();
					}
					else
					{
					    $spouseStr	.= " $spouse $of " .
						$family->getName($t);
                    }
                    $comma          = ',';
                }		// loop through spouses
 
                $parms[]            = array(
					        'idir'		    => $person->get('idir'),
					        'surname'		=> $person->get('surname'),
					        'givenname'		=> $person->get('givenname'),
					        'birthd'		=> $bdate,
					        'deathd'		=> $ddate,
					        'gender'		=> $gender,
					        'childRole'	    => $childRole,
					        'spouseRole'	=> $spouseRole,
					        'parentsStr'	=> $parentsStr,
					        'spouseStr'	    => $spouseStr);

			    if ($style == 'odd')
			    	$style	= 'even';
			    else
		    		$style	= 'odd';
			}		// a real entry
        }	        // loop through all rows
        $template['person']->update($parms);
	}	            // some matches
}		            // no errors

$template->display();
