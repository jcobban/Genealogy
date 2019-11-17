<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getUnlinkedIndividuals.php											*
 *																		*
 *  Display a web page containing all individuals in the family tree	*
 *  who are neither married nor children of a family.					*
 *																		*
 *  History:															*
 *		2017/01/15		created											*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

    // get the parameters
    $pattern	= '';
    $count	= 0;
    $offset	= 0;
    $limit	= 20;
    foreach($_GET as $key => $value)
    {
		switch($key)
		{		// take action based upon key
		    case 'offset':
		    {
				if (ctype_digit($value))
				    $offset	= intval($value);
				else
				    $msg	.= "Invalid Offset='$value'. ";
				break;
		    }

		    case 'limit':
		    {
				if (ctype_digit($value))
				    $limit	= intval($value);
				else
				    $msg	.= "Invalid Limit='$value'. ";
				break;
		    }
		}		// take action based upon key
    }
    $prevoffset	= $offset - $limit;
    $nextoffset	= $offset + $limit;

    if (canUser('edit'))
    {			// authorized to update database
    $queryCount	= "SELECT count(*) " .
						"FROM tblIR " .
						"LEFT JOIN tblMR as husb on husb.idirhusb=tblIR.idir " .
						"LEFT JOIN tblMR as wife on wife.idirwife=tblIR.idir " .
						"LEFT JOIN tblCR on tblCR.IDIR=tblIR.IDIR " .
						"WHERE husb.idirhusb IS NULL AND wife.idirwife IS NULL AND tblCR.idcr IS NULL";
    if ($debug)
		$warn	.= "<p>QueryCount=$queryCount</p>\n";

    $stmt	= $connection->query($queryCount);
    if ($stmt)
    {
		$record		= $stmt->fetch(PDO::FETCH_NUM);
		if (is_null($record) || $record === false)
		{
		    $warn	.= "<p>QueryCount=$queryCount</p>\n";
		    $warn	.= "<p>PDO::fetch returned false</p>\n";
		}
		else
		{
		    $count		= $record[0];
		}
    }
    else
    {
		$msg	.= "query '$queryCount' failed. " .
						   print_r($connection->errorInfo(), true);
    }

    $query	= "SELECT tblIR.idir, tblIR.givenname, tblIR.surname " .
						"FROM tblIR " .
						"LEFT JOIN tblMR as husb on husb.idirhusb=tblIR.idir " .
						"LEFT JOIN tblMR as wife on wife.idirwife=tblIR.idir " .
						"LEFT JOIN tblCR on tblCR.IDIR=tblIR.IDIR " .
						"WHERE husb.idirhusb IS NULL AND wife.idirwife IS NULL AND tblCR.idcr IS NULL " . 
						"GROUP BY tblIR.idir, tblIR.givenname, tblIR.surname " .
						"ORDER BY tblIR.idir " .
						"LIMIT $limit";
    if ($debug)
		$warn	.= "<p>Query=\"$query\"</p>\n";

    $stmt	= $connection->query($query);
    if ($stmt)
    {
		$records	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($debug)
		{
		    if ($records === false)
				$warn	.= "<p>fetch returned false</p>\n";
		    else
				$warn	.= "<p>fetch returned " . gettype($records) . "</p>\n";
		}
    }
    else
    {
		$msg	.= "query '$query' failed. " .
						   print_r($connection->errorInfo(), true);
    }
    }			// authorized to update database
    else
    {
		$msg	.= "You are not authorized to update the database. ";
    }

    htmlHeader('Unlinked Persons',
				array(	'/jscripts/js20/http.js',
						'/jscripts/CommonForm.js',
						'/jscripts/util.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/genCanada.html'		=> 'Canada',
				  '/FamilyTree/Services.php'	=> 'Services'));
?> 
  <div class="body">
    <h1>
		<span class="right">
		  <a href="UnlinkedIndividualsHelpen.html" target="help">? Help</a>
		</span>
		Persons who are not linked to any Families List
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {	// errors detected
?>
  <p class="message">
<?php print $msg; ?> 
  </p>
<?php
    }		// errors detected
    else
    {		// ok
		if ($count == 0)
		{
?>
    <p class="label">
		All individuals in the family tree are members of families.
    </p>
<?php
		}
		else
		{			// got some results
		    $count	= number_format($count);
?>
    <p class="label">
		<?php print $count; ?> individuals are not linked to any families.
    </p>
  <div class="center">
<?php
		$last	= min($nextoffset - 1, $count);
		if ($prevoffset >= 0)
		{	// previous page of output to display
?>
    <div class="left" id="npprev">
      <a href="UnlinkedIndividuals.php?limit=<?php print $limit; ?>&amp;offset=<?php print $prevoffset; ?>">&lt;---</a>
    </div>
<?php
		}	// previous page of output to display
?>
<?php
		if ($nextoffset < $count)
		{	// next page of output to display
?>
    <div class="right" id="npnext"> 
      <a href="UnlinkedIndividuals.php?limit=<?php print $limit; ?>&amp;offset=<?php print $nextoffset; ?>">---&gt;</a>
    </div>
<?php
		}	// next page of output to display
?>
		rows <?php print $offset; ?> to <?php print $last; ?> of <?php print $count; ?> 
    <div style="clear: both;"></div>
  </div>
<!--- Put out the response as a table -->
  <table class="details">
<!--- Put out the column headers -->
    <thead>
      <tr>
		<th class="colhead">
		  IDLR
		</th>
		<th class="colhead">
		  Given Name
		</th>
		<th class="colhead">
		  Surname
		</th>
      </tr>
    </thead>
    <tbody>
<?php
		// display the results
		foreach($records as $person)
		{
		    $idir	= $person['idir']; 
		    $givenname	= $person['givenname']; 
		    $surname	= $person['surname'];
?>
      <tr>
		<td class="odd right">
		    <a href="Person.php?id=<?php print $idir; ?>" target="_blank">
				<?php print $idir; ?> 
		    </a>
		</td>
		<td class="odd left">
		    <?php print htmlspecialchars($givenname); ?>
		</td>
		<td class="odd left">
		    <?php print htmlspecialchars($surname); ?>
		</td>
      </tr>
<?php
		}	// loop through results
?>
    </tbody>
  </table>
<?php
		}	// got some results
    }		// ok
?>
</div>	<!-- end of id="body" -->
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
<div class="balloon" id="Helpnamefld">
<p>Enter an actual location name in this field.  When you click on the
<span class="button">Search</span> button the dialog for displaying or
editing the details of the location pops up.  If you are authorised to 
update the database then this dialog permits you to create a new location,
otherwise only existing locations can be viewed in this way.
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
