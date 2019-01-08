<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaDoQuery.php														*
 *																		*
 *  Display a report of individuals whose marriage matches the			*
 *  requested pattern.  This is invoked by method='get' from			*
 *  OcfaDoQuery.html.													*
 *																		*
 *  Parameters:															*
 *		Count															*
 *		Offset															*
 *		Surname															*
 *		GivenNames														*
 *		SurnameSoundex													*
 *		County															*
 *		Township														*
 *		Cemetery														*
 *																		*
 *  History:															*
 *		2011/03/20		created											*
 *		2013/01/20		use urlencode on URI parameters					*
 *		2013/04/04		replace deprecated calls to doQuery				*
 *		2013/05/23		shorten title									*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/06/05		explicitly order response						*
 *		2013/06/29		update not implemented yet						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/27		use CSS to layout <h1> and pagination controls	*
 *		2015/05/01		PHP print statements were corrupted				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/25		replace ereg with preg_match					*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Record.inc";
require_once __NAMESPACE__ . "/common.inc";

// action taken depends upon whether the user is authorized to
// update the database

// default values
$and			= 'WHERE ';	// logical and operator in SQL expressions
$where			= '';
$npuri			= 'OcfaDoQuery.php';	// for next and previous links
$npand			= '?';		// adding parms to $npuri
$count			= 20;
$county         = null;
$township       = null;
$offset			= 0;
$lang			= 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// first extract the values of all supplied parameters
$parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			        // loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		        // switch on parameter name
	    case 'count':
        {		    // limit number of rows returned
            if (strlen($value) > 0)
			    $count	        = $value;
			break;
	    }		    // limit number of rows returned

	    case 'offset':
	    {		    // starting offset
            if (strlen($value) > 0)
			    $offset	        = $value;
			break;
	    }		    // starting offset

	    case 'surname':
	    {
            if (strlen($value) > 0)
            {
    			if (preg_match("/[.+*^$]/", $value))
    			{		// match pattern
    			    $where	        .= "$and$key REGEXP ?";
                    $sqlParms[]     = $value;
    			}		// match pattern
    			else
    			if (array_key_exists("SurnameSoundex", $_GET))
    			{		// match soundex
    			    $where	        .= "{$and}Soundex=LEFT(SOUNDEX(?,4))";
                    $sqlParms[]     = $value;
    			}		// match soundex
    			else
    			{		// match exact
    			    $where	        .= "$and$key=?";
                    $sqlParms[]     = $value;
    			}		// match exact
                $and            = ' AND ';
    			$npuri	        .= "{$npand}{$key}=" . urlencode($value);
                $npand	        = '&amp;'; 
            }
			break;
	    }

	    case 'givennames':
	    case 'givenname':
	    {		// match anywhere in string
            if (strlen($value) > 0)
            {
			    $where	        .= "{$and}GivenName REGEXP ?";
                $sqlParms[]     = $value;
                $and            = ' AND ';
			    $npuri	        .= "{$npand}GivenName=" . urlencode($value);
                $npand	        = '&amp;'; 
            }
			break;
	    }		// match in string

	    case 'cemetery':
        {		// match anywhere in string
            if (strlen($value) > 0)
            {
			    $where	        .= "$and$key REGEXP ?";
                $sqlParms[]     = $value;
                $and            = ' AND ';
			    $npuri	        .= "{$npand}{$key}=" . urlencode($value);
                $npand	        = '&amp;'; 
            }
			break;
	    }		// match in string

	    case 'surnamesoundex':
	    {		// handled under Surname
			$npuri	        .= "{$npand}{$key}=" . urlencode($value);
			$npand	        = '&amp;'; 
			break;
	    }		// handled under Surname

	    case 'county':
	    case 'township':
        {		// exact match on field in table
            if (strlen($value) > 0)
            {
		    	$where	        .= "$and$key=?";
                $sqlParms[]     = $value;
                $and            = ' AND ';
			    $npuri	        .= "{$npand}{$key}=" . urlencode($value);
                $npand	        = '&amp;';
            }
			break;
	    }		// exact match on field in table

	    case 'lang':
        {
            if (strlen($value) == 2)
                $lang       = strtolower($value);
			break;
	    }

	    case 'debug':
	    {
            if (strlen($value) > 0)
            {
			    $npuri	        .= "{$npand}{$key}=" . urlencode($value);
                $npand	        = '&amp;'; 
            }
			break;
	    }
 
	    default:
	    {		// exact match on field in table
            if (strlen($value) > 0)
            {
			    $where	        .= "$and$key=?";
                $sqlParms[]     = $value;
                $and            = ' AND ';
			    $npuri	        .= "{$npand}{$key}=" . urlencode($value);
                $npand	        = '&amp;'; 
            }
			break;
	    }		// exact match on field in table
	}		// switch on parameter name
}			// loop through all parameters
if ($debug)
    $warn   .= $parmsText . "</table>\n";

// validation
if (!preg_match("/^([0-9]{1,2})$/", $count))
{
    $msg        .= "Row count must be number between 1 and 99. ";
    $count	    = 20;		// replace with default
}

if (!preg_match("/^([0-9]{1,6})$/", $offset))
{
    $msg        .= 'Row offset must be number between 0 and 999,999. ';
    $offset	    = 0;
}

// variable portion of URI for next and previous links
if ($offset > 0)
{		// starting offset within existing query
	$limit	        = " LIMIT {$count} OFFSET {$offset}";
	$tmp	        = $offset - $count;
	if ($tmp < 0)
	    $npprev	    = "";	// no previous link
	else
	    $npprev	    = "Count={$count}&Offset={$tmp}";
	$tmp		    = $offset + $count;
	$npnext		    = "Count={$count}&Offset={$tmp}";
}		// starting offset within existing query
else
{
	$limit		    = " LIMIT $count";
	$npprev		    = "";
	$npnext		    = "Count={$count}&Offset={$count}";
}

if (strlen($msg) == 0)
{
	// execute the query
	$query		= "SELECT COUNT(*) FROM Ocfa $where";
    $stmt		= $connection->prepare($query);
    $queryText  = debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		// successful query
	    if ($debug)
            $warn	.= "<p>OcfaDoQuery.php: " . __LINE__ . 
                        " $queryText</p>";

	    // get the value of COUNT(*)
	    $row		    = $stmt->fetch(PDO::FETCH_NUM);
	    $totalrows		= $row[0];

	    // execute the query
	    $query		= "SELECT Surname, GivenName, Cemetery, Township, County FROM Ocfa $where ORDER BY Surname, GivenName, County, Township, Cemetery $limit";
	    $stmt		= $connection->prepare($query);
        $queryText  = debugPrepQuery($query, $sqlParms);
	    if ($stmt->execute($sqlParms))
	    {		// successful query
			$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
			$numRows	= count($result);
			if ($debug)
			{
                $warn	.= "<p>OcfaDoQuery.php: " . __LINE__ . 
                            " $queryText retrieved $numRows entries</p>\n";
			}
	    }		// successful query
	    else
	    {		// command rejected by database server
			$msg	.= "'$queryText': " .
					   print_r($stmt->errorInfo(),true);
	    }		// command rejected by database server
	}		// successful query
	else
	{		// command rejected by database server
	    $msg	.= "'$queryText': " .
					   print_r($stmt->errorInfo(),true);
	}		// command rejected by database server
}

htmlHeader("Ontario Cemetery Finding Aid Query",
			array('/jscripts/util.js',
			      'OcfaDoQuery.js'));
?>
<body>
<?php
pageTop(array(	'/genealogy.php'	=> 'Genealogy',
			'/genCanada.html'	=> 'Canada',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
			'/Ontario/OcfaQuery.html'
							=> 'New Query')); 
?>
    <div class='body'>
      <h1>
        <span class='right'>
	      <a href='OcfaDoQueryHelpen.html' target='_blank'>Help?</a>
        </span>
        Ontario Cemetery Finding Aid Query
      </h1>
      <p>This tool is an alternate and <b>unofficial</b> interface to an old copy
        of the database maintained at <a href='http://www.islandnet.com/ocfa/'>
        Ontario Cemetery Finding Aid</a>.
      </p>
<?php
if (strlen($warn) > 0)
{		// print trace
?>
  <div class='warning'><?php print $warn; ?></div>
<?php
}		// print trace

if (strlen($msg) > 0)
{		// print error messages if any
?>
  <p class='message'><?php print $msg; ?></p>
<?php
}		// print error messages if any
else
{		// display results of query
?>
  <!--- Put out a line with links to previous and next section of table -->
  <div class='center'>
<span class='left'>
<?php
	if (strlen($npprev) > 0)
	{
?>
  <a href='<?php print $npuri.$npand.$npprev; ?>' id='prevPage'>&lt;---</a>
<?php
	}
?>
</span>
<span class='right'>
 <a href='<?php print $npuri.$npand.$npnext; ?>' id='nextPage'>---&gt;</a>
</span>
	displaying rows <?php print $offset+1; ?> to <?php print $offset + $numRows; ?> of <?php print $totalrows; ?> 
  </div>
  <!--- Put out the response as a table -->
  <table class='details'>
<thead>
<!--- Put out the column headers -->
  <tr>
	<th class='colhead'>
	  Surname
	</th>
	<th class='colhead'>
	  Given Names
	</th>
	<th class='colhead'>
	  Cemetery
	</th>
	<th class='colhead'>
	  Township
	</th>
	<th class='colhead'>
	  County
	</th>
  </tr>
</thead>
<tbody>
<?php
	// display the results
	$even		= false;
	$numRows	= 0;
	foreach($result as $row)
	{
	    if ($even)
			$class	= 'even';
	    else
			$class	= 'odd';

	    // start table row for display
?>
  <tr>
<?php
	    // print the values of the attributes
	    foreach ($row as $attribute)
	    {
?>
	<td class='<?php print $class; ?>'><?php print $attribute; ?></td>
<?php
	    }
	    $even	= !$even;
?>
  </tr>
<?php
	}		// process all rows
?>
<tbody>
  </table>
  <!--- Put out a line with links to previous and next section of table -->
  <div class='center'>
<span class='left'>
<?php
	if (strlen($npprev) > 0)
	{
?>
	  <a href='<?php print $npuri.$npand.$npprev; ?>'>&lt;---</a>
<?php
	}
?>
</span>
<span class='right'>
	  <a href='<?php print $npuri.$npand.$npnext; ?>'>---&gt;</a>
</span>
&nbsp;
  </div>
<?php
}		// display results of query
?>
</div> <!-- end of <div id='body'> -->
<?php
pageBot();
?>
</body>
</html>
