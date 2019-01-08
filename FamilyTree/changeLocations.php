<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  changeLocations.php							*
 *									*
 *  Display a web page to perform a pattern matching replacement on	*
 *  the names of a set of locations.					*
 *									*
 *  History:								*
 *	2011/01/16	created						*
 *	2011/02/01	improve error checking				*
 *	2012/01/13	change class names				*
 *	2012/07/26	change genOntario.html to genOntario.php	*
 *	2013/06/01	use pageTop and pageBot to standardize		*
 *			appearance					*
 *	2013/07/31	use default dynamic initialization		*
 *			add help text for all fields			*
 *			defer facebook initialization until after load	*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/02/10	eliminate use of tables for layout		*
 *	2014/04/26	formUtil.inc obsoleted				*
 *	2014/09/15	pass debug flag					*
 *			clean up parameter processing			*
 *			use Location::matchReplaceNames instead		*
 *			of SQL to update database			*
 *	2014/11/29	print $warn, which may contain debug trace	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/12/07	use cookies rather than sessions		*
 *			use method=post rather than method=get		*
 *	2016/01/19	add id to debug trace				*
 *			include http.js					*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Location.inc";
    require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('edit'))
    {			// take no action
	$msg	.= 'Not authorized to update database. ';
    }			// take no action

    // interpret patterns
    $from	= null;
    $to		= null;

    foreach($_POST as $key => $value)
    {			// loop through all parameters
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'pattern':
	    {
		if (strlen($value) > 0)
		{		// search pattern specified
		    set_cookie('pattern', $value);
		    $from		= $value;
		}		// search pattern specified
		break;
	    }

	    case 'replace':
	    {
		if (strlen($value) > 0)
		{		// search pattern specified
		    set_cookie('replace', $value);
		    $to			= $value;
		}		// search pattern specified
		break;
	    }

	}		// act on specific parameters
    }			// loop through all parameters

    // check for missing parameters
    if (is_null($from) || is_null($to))
    {			// skip update, go direct to prompt
	if (is_null($from))
	{
	    if (isset($_COOKIE['pattern']))
		$from	= $_COOKIE['pattern'];
	    else
		$from	= '';
	}

	if (is_null($to))
	{
	    if (isset($_COOKIE['replace']))
		$to		= $_COOKIE['replace'];
	    else
		$to		= '';
	}
	$result		= null;
    }			// skip update go direct to prompt
    else
    if (strlen($msg) == 0)
    {		// no errors detected
	$locations	= new RecordSet('Locations',
					array('Location'	=> $from));
	// strip off opening caret if preset
	if (substr($from, 0, 1) == '^')
	    $from	= substr($from, 1);
	// strip off closing dollar sign if present
	if (substr($from, strlen($from)-1) == '$')
	    $from	= substr($from, 0, strlen($from)-1);

	$set		= array('Location'	=> array($from, $to),
				'SortedLocation'=> array($from, $to));
	$result		= $locations->update($set,
					     false,
					     false);
    }		// something to do

    htmlHeader('Find and Change Locations',
		array(	'/jscripts/CommonForm.js',
			'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'changeLocations.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
		  '/FamilyTree/Services.php'	=> 'Services',
		  'Locations.php'		=> 'Locations'));
?>
  <div class='body'>
  <h1>Find and Change Locations
      <span class='right'>
	<a href='changeLocationsHelpen.html' target='help'>? Help</a>
      </span>
    <div style='clear: both;'></div>
  </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {			// problems
?>
  <p class='message'><?php print $msg; ?></p>
<?php
    }			// problems
    else
    {			// no errors detected
	if (is_int($result))
	{		// update performed
?>
<p class='label'><?php print $result; ?> locations changed.</p>
<?php
	}		// update performed
?>
<form name='locForm' id='locForm' action='changeLocations.php' method='post'>
<?php
	if ($debug)
	{		// debug specified
?>
    <input type='hidden' name='debug' id='debug' value='Y'>
<?php
	}		// debug specified
?>
  <div class='row'>
    <label class='label' for='pattern'>
	Find:
    </label>
    <input name='pattern' id='pattern' type='text' class='white leftnc'
		size='40' value='<?php print $from; ?>'>
    <div style='clear: both;'></div>
  </div>
  <div class='row'>
    <label class='label' for='replace'>
	Replace With:
    </label>
    <input name='replace' id='replace' type='text' class='white leftnc'
	size='40' value='<?php print $to; ?>'>
    <div style='clear: both;'></div>
  </div>
<p>
<button type='submit' id='Submit'>
Search and Replace
</button>
</p>
</form>
<?php
    }		// no errors detected
?>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='Helppattern'>
<p>
Only locations containing this string will be included.
For example the pattern "London" will match locations containing the string
"London".  Note that the search does not ignore case, so that pattern will
only match "London", not "london" or "LONDON".  This is not a regular
expression, but two regular expression capabilities are included.  If the
supplied string starts with a caret '^' then the remainder of the string
must match at the beginning of the location names.  If the supplied string
ends with a dollar sign '$' then the remainder of the string must match the
end of the location names.  Do not specify both caret and dollar sign.
</p>
</div>
<div class='balloon' id='Helpreplace'>
This field is used to supply the text that replaces the text matched by
the pattern in the "Find" field.
</div>
<div class='balloon' id='HelpSubmit'>
Click on this button to perform the search and replace on location names.
</div>
</body>
</html>
