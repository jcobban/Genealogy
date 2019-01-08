<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DontMergeEntries.php												*
 *																		*
 *  Display a web page containing a list of entries to suppress global	*
 *  merge of individuals on a case by case basis.  These are usually	*
 *  name entries that are not distinguishable by name and birth date,	*
 *  but are known not to represent the same individual.					*
 *																		*
 *  History:															*
 *		2011/11.01		created											*
 *		2012/01/13		change class names								*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/07/31		defer setup of facebook link					*
 *						standardize initialization						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						submitting form invoked Locations.php			*
 *						dates were displayed in internal format			*
 *						display both individuals for each entry			*
 *		2016/02/06		use showTrace									*
 *		2017/09/14		use DontMergeEntry::getDontMergeEntries			*
 *		2017/10/30		use DontMergeEntrySet							*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/DontMergeEntry.inc';
require_once __NAMESPACE__ . '/DontMergeEntrySet.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the parameters
    $pattern	= '';
    $offset	= 0;
    $limit	= 20;
    foreach($_GET as $key => $value)
    {
		switch($key)
		{		// take action based upon key
		    case 'pattern':
		    {		// pattern to match against surname
				$pattern	= $value;
				break;
		    }		// pattern to match against surname

		    case 'offset':
		    {		// position within the complete list of responses
				$offset		= (int)$value;
				break;
		    }		// position within the complete list of responses

		    case 'limit':
		    {		// maximum number of entries displayed
				$limit		= (int)$value;
				break;
		    }		// maximum number of entries displayed
		}		// take action based upon key
    }
    $prevoffset	= $offset - $limit;
    $nextoffset	= $offset + $limit;

    // construct the query
    $getParms		= array('offset'	=> $offset,
							'limit'		=> $limit,
							'order'		=> 'IDIRLeft');
    if (strlen($pattern) > 0)
		$getParms['surname']	= $pattern;
    $entries		= new DontMergeEntrySet($getParms);
    $information	= $entries->getInformation();
    $count		= $information['count'];
    $last		= $offset = count($entries);

    htmlHeader('Do Not Merge Individuals List',
				array(	'/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js',
						'/jscripts/default.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				'/genCountry.php?cc=CA'		=> 'Canada',
				'/Canada/genProvince.php?Domain=CAON'
									=> 'Ontario',
				'/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
  <h1>
      <span class="right">
		<a href="DontMergeEntriesHelpen.html" target="help">? Help</a>
      </span>
		Do Not Merge Individuals List
  </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class="message"><?php print $msg; ?></p>
<?php
    }

    showTrace();
?>
  <form name="locForm" action="DontMergeEntries.php">
    <div class="row">
      <label class="label" for="pattern">
		Surname Pattern:
      </label>
      <input name="pattern" id="pattern" type="text" size="64" class="white leftnc"
		value="<?php print $pattern; ?>">
      <div style="clear: both;"></div>
    </div>
    <p>
      <button type="submit" id="Search">
		Search
      </button>
    </p>
  </form>
<?php
    if (strlen($msg) == 0)
    {		// no errors, we have a response to display
?>
<div class="center">
  <span class="left" id="npprev">
<?php
		if ($prevoffset >= 0)
		{	// previous page of output to display
?>
    <a href="DontMergeEntries.php?pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $prevoffset; ?>">&lt;---</a>
<?php
		}	// previous page of output to display
		else
		{
?>
    &nbsp;
<?php
		}
?>
  </span>
  <span class="right" id="npnext"> 
<?php
		if ($nextoffset < $count)
		{	// next page of output to display
?>
    <a href="Locations.php?pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $nextoffset; ?>">---&gt;</a>
<?php
		}	// next page of output to display
		else
		{
?>
    &nbsp;
<?php
		}
?>
    </span>
		rows <?php print $offset; ?> to <?php print $last; ?> of <?php print $count; ?> 
    <div style="clear: both;"></div>
  </div>
<!--- Put out the response as a table -->
<table class="details">
<!--- Put out the column headers -->
  <thead>
    <tr>
      <th class="colhead">
		IdirLeft
      </th>
      <th class="colhead">
		Surname
      </th>
      <th class="colhead">
		GivenName
      </th>
      <th class="colhead">
		BirthD
      </th>
      <th class="colhead">
		DeathD
      </th>
      <th class="colhead">
      </th>
      <th class="colhead">
		IdirRight
      </th>
      <th class="colhead">
		Surname
      </th>
      <th class="colhead">
		GivenName
      </th>
      <th class="colhead">
		BirthD
      </th>
      <th class="colhead">
		DeathD
      </th>
    </tr>
  </thead>
  <tbody>
<?php
		// display the results
		foreach($entries as $row)
		{
		    $idirleft	= $row->get('idirleft');
		    $lsurname	= $row->get('lsurname');
		    $lgivenName	= $row->get('lgivenname');
		    $lbirthd	= $row->get('lbirthdate');
		    $ldeathd	= $row->get('ldeathdate');
		    $idirright	= $row->get('idirright');
		    $rsurname	= $row->get('rsurname');
		    $rgivenName	= $row->get('rgivenname');
		    $rbirthd	= $row->get('rbirthdate');
		    $rdeathd	= $row->get('rdeathdate');
?>
    <tr>
		<td class="odd right">
		    <a href="Person.php?idir=<?php print $idirleft; ?>">
				<?php print $idirleft; ?> 
		    </a>
		</td>
		<td class="odd left">
		    <?php print $lsurname; ?>
		</td>
		<td class="odd left">
		    <?php print $lgivenName; ?>
		</td>
		<td class="odd left">
		    <?php print $lbirthd; ?>
		</td>
		<td class="odd left">
		    <?php print $ldeathd; ?>
		</td>
		<td class="odd left">
		</td>
		<td class="odd right">
		    <a href="Person.php?idir=<?php print $idirright; ?>">
				<?php print $idirright; ?> 
		    </a>
		</td>
		<td class="odd left">
		    <?php print $rsurname; ?>
		</td>
		<td class="odd left">
		    <?php print $rgivenName; ?>
		</td>
		<td class="odd left">
		    <?php print $rbirthd; ?>
		</td>
		<td class="odd left">
		    <?php print $rdeathd; ?>
		</td>
    </tr>
<?php
		}	// loop through results
?>
  </tbody>
</table>
<?php
    }		// no errors, we have a response to display
?>
</div>
<?php
    pageBot();
?>
<div class="balloon" id="Helppattern">
<p>
This is a regular expression, as supported by MySQL, which is used to limit
the locations to be displayed. See <a href="http://www.tin.org/bin/man.cgi?section=7&topic=regex">Henry Spencer"s regex page</a>.
<ul>
    <li>If the pattern contains no special 
characters then only locations containing that string will be included.
For example the pattern "London" will match locations containing the string
"London".  Note that the search ignores case, so that pattern will also match
"LONDON" and "london".
    <li>If the pattern begins with a caret '^' then only locations that
<b>begin</b> with the remainder of the pattern are included.  
For example the pattern
"^Ba" displays locations starting with "Ba" (or "ba" or "BA").
    <li>If the pattern ends with a dollar sign '$', then only locations that
<b>end</b> with the remainder of the pattern are included.  
For example the pattern
"CA$" matches locations that end with "CA" (or "ca" or "Ca").
    <li>In a pattern a period '.' matches any single character.  For example
the pattern "B.b" matches any location that contains two letter Bs separated
by one character, for example "Bab", "Beb", "Bib", "Bob", or "Bub" 
anywhere in the location name.
    <li>In a pattern an asterisk '*' matches zero or more of the preceding
character; "bo*b" matches "bb", "bob", and "boob"
anywhere in the location name.
</p>
</div>
<div class="balloon" id="HelpSearch">
<p>Clicking on this button refreshes the displayed list of locations
based upon the pattern.  You may also hit the "enter" key to perform the
same function.
</p>
</div>
  <!-- balloons to pop up when mouse moves over forward and back links -->
  <div class="popup" id="mousenpprev">
    <p class="label">
		Go to Row <?php print $offset - $limit; ?>&nbsp;
    </p>
  </div>
  <div class="popup" id="mousenpnext">
    <p class="label">
		Go to Row <?php print $offset + $limit; ?>&nbsp;
    </p>
  </div>
</body>
</html>
