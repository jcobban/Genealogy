<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Sources.php															*
 *																		*
 *  Display a web page containing all of the Sources matching a			*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2010/08/29		Use new layout									*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/11		explicitly set field names to lower case		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/30		use htmlHeader and add link to help page		*
 *		2010/12/08		format number of citations						*
 *		2012/01/13		change class names								*
 *		2012/04/01		pad citation number portion less than 1000		*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/03/28		support mouseover help							*
 *						separate javascript and HTML					*
 *						add button for deleting un-referenced source	*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/18		Delete button for a source was not type="button"*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS for layout instead of tables			*
 *						correct back link								*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/08/23		use Source::getSources							*
 *						use Citation::getCitations						*
 *						eliminate all direct uses of SQL				*
 *						validate parameters and issue error message		*
 *						instead of querying database if wrong			*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/02/13		specify style class for input field				*
 *		2015/05/28		support split screen for displaying source		*
 *						<button type="Edit..."> was type="submit"		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/10/14		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		Open Code														*
 ************************************************************************/

// get the parameters passed by method=GET
$pattern				    = '';
$offset 				    = 0;
$limit	    			    = 20;
if (count($_GET) > 0)
{	        	    // invoked by URL
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'pattern':
		    {
				$pattern	= $value;
				break;
		    }

		    case 'offset':
		    {
				$offset		= (int)$value;
				break;
		    }

		    case 'limit':
		    {
				$limit		= (int)$value;
				break;
		    }
		}		// act on specific parameters
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL
    $prevoffset	= $offset - $limit;
    $nextoffset	= $offset + $limit;

    // get an associative array of source records matching the
    // supplied parameters
    $parms	= array('limit'		=> $limit,
					'offset'	=> $offset,
					'order'		=> 'SrcName');
    if (strlen($pattern) > 0)
		$parms['SrcName']	= $pattern;
    $sources		= new RecordSet('Sources', $parms);
    $information	= $sources->getInformation();
    // Note: $information['count'] >= $sources->count() <= $limit
    $count		= $information['count'];

    htmlHeader('Sources Master List',
				array(	'/jscripts/js20/http.js',
					'/jscripts/CommonForm.js',
					'/jscripts/util.js',
					'Sources.js'));
?>
<body>
  <div id="transcription" style="overflow: auto; overflow-x: scroll">
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <h1>
		<span class="right">
		  <a href="SourcesHelpen.html" target="help">? Help</a>
		</span>
		Sources Master List
    </h1>
<?php
    showTrace();

    if (strlen($msg) == 0)
    {
		if ($count == 0)
		    $showCount	= 'No';
		else
		    $showCount	= $count;
?>
    <p class="label">
      <?php print $showCount; ?> Sources match the specified pattern.
    </p>
    <form name="srcForm" action="Sources.php">
      <div class="row" id="patternRow">
		<label class="label" for="pattern" style="width: 8em;">
		    Pattern:
		</label>
		<input name="pattern" type="text" size="64" class="white leftnc"
				    value="<?php print $pattern; ?>">
		    <div style="clear: both;"></div>
      </div>
      <div class="row" id="buttonRow">
		<button type="submit" id="Search">
		    Search
		</button>
<?php
		if (canUser('edit'))
		{		// permit adding a source
?>
		&nbsp;
		<button type="button" id="CreateNew">
		    Create New Source
		</button>
<?php
		}		// permit adding a source
?>
		<div style="clear: both;"></div>
      </div>
<?php
		if ($count > 0)
		{		// query issued
		    $last	= min($nextoffset - 1, $count);
?>
      <div class="center">
<?php
		    if ($prevoffset >= 0)
		    {	// previous page of output to display
?>
		<div class="left">
		  <a href="Sources.php?pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $prevoffset; ?>">
		  &lt;---
		  </a>
		</div>
  <?php
		    }	// previous page of output to display
		    if ($nextoffset   < $count)
		    {	// next page of output to display
?>
		<div class="right"> 
		  <a href="Sources.php?pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $nextoffset; ?>">---&gt;</a>
		</div>
  <?php
		    }	// next page of output to display
?>
		rows <?php print $offset; ?> to <?php print $last; ?>
		of <?php print $count; ?>
		<div style="clear: both;"></div>
      </div>
<!--- Put out the response as a table -->
<table class="details" id="SourceTable">
<!--- Put out the column headers -->
  <thead>
    <tr>
      <th class="colhead">
		IDSR
      </th>
      <th class="colhead">
		Type
      </th>
      <th class="colhead">
		Source Name
      </th>
      <th class="colhead">
		Citation Count
      </th>
    </tr>
  </thead>
  <tbody>
<?php
		    //$fmt = new NumberFormatter( 'en_CA', NumberFormatter::DECIMAL );
		    // display the results
		    foreach($sources as $idsr => $source)
		    {		// loop through matching sources
				$idst		= $source->getType();
				$typeText	= $source->getTypeText(); 
				$name		= $source->getName(); 
				// query the database for citation count
				$parms	= array('IDSR'	=> $idsr,
						'limit'	=> 0);
				$cresult	= new RecordSet('Citations', 
								$parms);
				$cinformation	= $cresult->getInformation();
				$ccount		= number_format($cinformation['count']);

				// set up text for action button
				if (canUser('edit'))
				{		// authorized
				    $action	= "Edit";
				    $label	= "Edit $idsr";
				}		// authorized
				else
				{		// not authorized
				    $action	= "Show";
				    $label	= "Show $idsr";
				}		// not authorized
?>
    <tr id="Row<?php print $idsr; ?>">
		<td class="odd right">
		    <button type="button" class="width110" 
				id="<?php print $action . $idsr; ?>">
				<?php print $label; ?> 
		    </button>
		</td>
		<td class="odd left" id="Type<?php print $idsr; ?>">
		    <?php print $typeText; ?>  
		</td>
		<td class="odd left" id="Name<?php print $idsr; ?>">
		    <?php print $name; ?> 
		</td>
<?php
				if ($ccount > 0 || $action == 'Show')
				{
?>
		<td class="odd right">
		    <?php print $ccount; ?> 
		</td>
<?php
				}
				else
				{		// citation count zero
?>
		<td class="odd center">
		    <button type="button" class="width110"
					id="Delete<?php print $idsr; ?>">
				Delete
		    </button>
		</td>
<?php
				}		// citation count zero
?>
    </tr>
<?php
flush();
		    }		// loop through matching sources
?>
  </tbody>
</table>
</form>
<?php
		}		// query issued
    }			// no errors
    else
    {			// display errors
?>
  <p class="message"><?php print $msg; ?>
  </p>
<?php
    }			// display errors
?>
</div>
<?php
    pageBot();
?>
  </div> <!-- id="transcription" -->
<?php
    foreach(Source::$intType as $idst => $text)
    {			// loop through all supported values of IDST
?>
    <div id="IDST<?php print $idst; ?>" class="hidden">
		<?php print $text; ?>
    </div>
<?php
    }			// loop through all supported values of IDST
?>
<div class="hidden" id="templates">

  <!-- template for confirming the deletion of an event-->
  <form name="ClrInd$template" id="ClrInd$template">
    <p class="message">$msg</p>
    <p>
      <button type="button" id="confirmClear$type">
		OK
      </button>
      <input type="hidden" id="formname$type" name="formname$type"
				value="$formname">
		&nbsp;
      <button type="button" id="cancelDelete$type">
		Cancel
      </button>
    </p>
  </form>

</div> <!-- id="templates" -->
<div class="balloon" id="Helppattern">
<p>
This is a regular expression, as supported by MySQL, which is used to limit
the Sources to be displayed. See <a href="http://www.tin.org/bin/man.cgi?section=7&topic=regex">Henry Spencer"s regex page</a>.
</p>
<ul>
    <li>If the pattern contains no special 
characters then only Sources containing that string will be included.
For example the pattern "London" will match Sources containing the string
"London".  Note that the search ignores case, so that pattern will also match
"LONDON" and "london".
    <li>If the pattern begins with a caret '^' then only Sources that
begin with the remainder of the pattern are included.  For example the pattern
"^Ba" displays Sources starting with "Ba" (or "ba" or "BA").
    <li>If the pattern ends with a dollar sign '$', then only Sources that
end with the remainder of the pattern are included.  For example the pattern
"CA$" matches Sources that end with "CA" (or "ca" or "Ca").
    <li>In a pattern a period '.' matches any single character.  For example
the pattern 'B.b' matches any Source that contains two letter Bs separated
by one character, for example "Bab", "Beb", "Bib", "Bob", or "Bub".
    <li>In a pattern an asterisk '*' matches zero or more of the preceding
character; "bo*b" matches "bb", "bob", and "boob".
</ul>
</div>
<div class="balloon" id="HelpSearch">
Click on this button to update the list of displayed sources to include only
those sources that match the supplied pattern.
</div>
<div class="balloon" id="HelpShow">
Click on this button to display the detailed information about a source.
</div>
<div class="balloon" id="HelpEdit">
Click on this button to display a form 
to update the information recorded abbout a source.
</div>
<div class="balloon" id="HelpDelete">
This button is displayed for sources that are not associated with any
citations.  Click on this button to delete the source from the database.
</div>
<div class="balloon" id="HelpCreateNew">
Click on this button to open a dialog to create a new source.
</div>
</body>
</html>
