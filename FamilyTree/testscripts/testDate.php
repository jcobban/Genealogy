<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testDate.php							*
 *									*
 *  Display a web page for testing the functionality of LegacyDate.	*
 *									*
 *  Parameters:								*
 *	in	the text value of the date to interpret			*
 *									*
 *  History:								*
 *	2014/12/02	enclose comment blocks				*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';
    require_once __NAMESPACE__ . '/LegacyDate.inc';

    // defaults
    $in		= '';
    $out	= '';
    $template	= LegacyDate::getTemplate();

    foreach ($_POST as $key => $value)
    {		// loop through all parameters
	switch(strtolower($key))
	{	// act on specific parameter
	    case 'template':
	    {
		$template	= $value;
		LegacyDate::setTemplate($template);
		break;
	    }
	
	    case 'debug':
	    {
		LegacyDate::setDebug(true);
		break;
	    }
	
	    case 'in':
	    {
		$in	= $value;
	
		break;
	    }
	}	// act on specific parameter
    }		// loop through all parameters

    // parse the date
    try {
	$date	= new LegacyDate(' ' . $in);
	$out	= "date='" . $date->getDate() .
	      "', sort=" . $date->getSortDate() .
	      "', Julian=" . $date->getJulianDate() .
	      ', msg=' . $date->getMessage() .
	      ', string=\'' . $date->toString() . "'";
    }
    catch(Exception $e)
    {
	print "<p>Exception: " . $e->getMessage();
    }

    htmlHeader("Test Date",
		array( '/jscripts/util.js',
			'testDate.js'));

?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/genCountry.php?cc=CA'		=> 'Canada',
		'/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
  <div class='body'>
    <h1>Test Date</h1>
    <form name='indForm' action='testDate.php' method='post'>
      <div class='row'>
        <label class='column1' for='selTemplate'>
    Choose presentation layout:
        </label>
        <select name='selTemplate' id='selTemplate'>
	<option value='[dd] [Mon] [yyyy] [BC]'>Default
	<option value='[dd] [Mon] [yyyy] [BC][AD]'>Show AD/BC Indicator
	<option value='[dd] [Mon] [yyyy] = [OSdd] [OSMon] [OSyyyy]'>
		Show NS and OS Dates
	<option value='[dd] [Mon] [yyyy] [BCE][CE]'>
		Show CE/BCE Indicator
	<option value='[Mon] [ddord], [yyyy] [BC]'>American
	<option value='[ddord] [Mon] [yyyy] [BC]'>British
	<option value='[ddord] [Mon] [xxxx] [BC]'>Roman Numeral Year
    </select>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='column1' for='template'>Format Template: </label>
      <input type='text' name='template' size='40'
		class='white leftnc' value='<?php print $template; ?>'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='column1' for='in'>Enter Date:</label>
      <input type='text' name='in' id='in' size='40'
		class='white leftnc' value='<?php print $in; ?>'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
      <label class='column1' for='debug'>Debug:</label>
      <input type='checkbox' name='debug' id='debug' value='Y' class='white left'>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
<?php
	print $out;
?>
      <div style='clear: both;'></div>
     </div>
     <div class='row'>
	<button type='submit' id='Submit'>Submit</button>
      <div style='clear: both;'></div>
     </div>
    </form>
<p><a href='/datesHelp.html'>Detailed Information on Entering Dates</a></p>
<h2>Notes:</h2>
<p>The internal representation of a date is as defined by 
<i>LegacySpec 7k 02-15-2010.txt</i>
except as discussed below.  A date is represented internally in two forms:
<ul>
    <li>An internal encoding of the date, including support for various kinds
	of ranges.
    <li>A sort date, for arranging events in chronological order.
</ul>
<p>Given an input date string it is parsed into the internal forms, which are
displayed, along with the results of converting the internal date back into
a string according to a chosen template.
<p>There are a couple of minor amendments to the internal representation:
<ul>
    <li>The internal representation of a date includes only one indicator for
	BC dates.  However a range of dates may span from a BC date to an AD
	date.  Such is the case, for example, with the reign of Augustus, the
	first Emperor of Rome.  In this rare case 50 is added to the 
	month field of the second date of the range to indicate that the
	second date is AD.  However such a value will be misinterpreted by
	Legacy Family Tree.
    <li>The representation of a sort date for a BC date is explicitly
	decribed to be the same as the representation for an AD date, except
	the entire number is negative.  The example given in the
	specification is "1 Jan 400 BC would be  -40000101".  First
	note that should be <b>-4000101</b>.  More significantly
	this means that within a given year BC dates will sort backwards!
	For example 1 Sep 400 BC should sort after 1 Jan 400 BC, but
	-4000901 is <b>less than</b> -4000101.  So the implementation
	for BC years has been changed to: day + 100*month - 10000*year
	from the implied -(day + 100*month + 10000*year).  In this 
	implementation 1 Jan 400 BC is represented as -3999899 and
	1 Sep 400 BC as -3999899.
    <li>The document does not discuss how to handle the centuries-long
	migration between the Julian and Gregorian calendars.  In particular
	for most of the British Empire, including the American Colonies (but
	not most of Canada which was part of the French Empire at the time), 
	"old style"
	dates use the Julian Calendar but with the year starting on "Lady Day",
	also known as the Feast of the Annunciation of the Virgin Mary
	<a href='#fn'><sup>1</sup></a>, that
	is March 25th.  So, for example, 20 Mar 1736 NS = 9 Mar 1735 OS.  The
	implementation on this web-site is that the internal representation
	of all dates after 4 Oct 1582 is Gregorian New Style, and before
	that is Julian Proleptic with the year starting at 1 Jan in both cases.
	The programmer can choose to display an "Old Style" date by using
	the appropriate template.
</ul>
<hr>
<ol>
    <li id='#fn'>Year long property leases in Britain were synchronized to the
	calendar year.  Even over a century after the switch to new style in
	1752 these leases turned over on "Old Lady Day", 6th April in the 19th
	century, because otherwise in 1752 the lease would have been cut short
	by 11 days.  See Thomas Hardy's novel "Far from the Madding Crowd".
</ol>
  </div>
<?php
    pageBot();
?>
  <div id='HelpselTemplate' class='balloon'>
	Choose from a selection of predefined output templates.
  </div>
  <div id='Helptemplate' class='balloon'>
	Modify this string to customize the output template.
     	Substitutions are identified by codes enclosed in square brackets:
	<table>
	  <tr><th>dd</th>	<td>day of month as a number</td></tr>
	  <tr><th>ddord</th>	<td>day of month as an ordinal number</td></tr>
	  <tr><th>OSdd</th>	<td>day of month as a number, Julian calendar after 1582</td></tr>
	  <tr><th>Mon</th>	<td>3 character abbreviation for month name</td></tr>
	  <tr><th>OSMon</th>	<td>3 char abbr for month, Julian calendar after 1582</td></tr>
	  <tr><th>Month</th>	<td>full month name</td></tr>
	  <tr><th>OSMonth</th>	<td>full month name, Julian calendar after 1582</td></tr>
	  <tr><th>yyyy</th>	<td>year (numeric)</td></tr>
	  <tr><th>xxxx</th>	<td>year (roman numerals)</td></tr>
	  <tr><th>OSyyyy</th>	<td>year (numeric), Old Style: Julian plus 25th March</td></tr>
	  <tr><th>BC</th>	<td>display epoch if BC</td></tr>
	  <tr><th>AD</th>	<td>display epoch if AD</td></tr>
	  <tr><th>BCE</th>	<td>display epoch if Before Common Era</td></tr>
	  <tr><th>CE</th>	<td>display epoch if Common Era</td></tr>
	</table>
  </div>
  <div id='Helpdebug' class='balloon'>
	Click on this option to output a trace of the parse.
  </div>
  <div id='HelpSubmit' class='balloon'>
	Click to interpret the entered date.
  </div>
  <div id='Helpin' class='balloon'>
    <p>Enter a date or a range of dates.  The format is very flexible.  Each
	date may be expressed in a number of formats.
	See <a href='/datesHelp.html'>Detailed Information on Entering Dates</a> for further information.
  </div>
</body>
</html>
