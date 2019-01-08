<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbDoQuery.php														*
 *																		*
 *  Display a report of individuals whose Wesleyan Methodist Baptism	*
 *  matches the requested pattern.  This is invoked by method='get'		*
 *  from WmbQuery.html.													*
 *																		*
 *  Parameters:															*
 *		Count															*
 *		Offset															*
 *		Surname															*
 *		GivenName														*
 *		SurnameSoundex													*
 *		District														*
 *		Area															*
 *		Father															*
 *		Mother															*
 *		etc.															*
 *																		*
 *  History:															*
 *		2013/06/28		created											*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/03		interpret numeric dates							*
 *						replace tables with CSS							*
 *		2015/05/01		PHP print statements were corrupted				*
 *			            validate date before interpreting month		    *	
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/28		force minimum width of columns					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/03/26		make given name a hyperlink						*
 *		2016/04/25		replace ereg with preg_match					*
 *		2016/08/19		change default number of lines to 25			*
 *						always display full page if page requested		*
 *						forward and back links are by page if page		*
 *						requested										*
 *						order by internal order for display of page		*
 *		2016/11/28		handle invalid month 00							*
 *		2018/01/24		use new URLs									*
 *						use prepared SQL statements						*
 *						do not fail if asked to show entire table		*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/MethodistBaptism.inc";
require_once __NAMESPACE__ . "/common.inc";

$monthName	= array('01'	=> 'Jan',
					'02'	=> 'Feb',
					'03'	=> 'Mar',
					'04'	=> 'Apr',
					'05'	=> 'May',
					'06'	=> 'June',
					'07'	=> 'July',
					'08'	=> 'Aug',
					'09'	=> 'Sep',
					'10'	=> 'Oct',
					'11'	=> 'Nov',
					'12'	=> 'Dec',
					'00'	=> '');

/************************************************************************
 *  dateToString														*
 *																		*
 *  Expand numeric dates to a human readable string.					*
 *																		*
 *  Input:																*
 *		$date		date from database field							*
 *																		*
 *  Returns:															*
 *		Human readable date as a string.								*
 ************************************************************************/
function dateToString($date)
{
    global	$monthName;
    $matches	= array();
    $presult	= preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/',
    					     $date,
    					     $matches);
    if ($presult === 1)
    {			// pattern matched
    	$year	= $matches[1];
    	$month	= $matches[2];
    	$day	= $matches[3];
    	if ($month == 0)
    	    return ($day - 0) . '&nbsp;XXX&nbsp;' .  $year;
    	if ($month <= 12)
    	    return ($day - 0) . '&nbsp;' . $monthName[$month] . '&nbsp;' .
    					$year;
    	if ($month > 12 && $day <= 12)
    	    return ($month - 0) . '&nbsp;' . $monthName[$day] . '&nbsp;' .  
    					$year;
    	else
    	    return $date;
    }			// pattern matched
    else
    	return $date;
}			// dateToString

// action taken depends upon whether the user is authorized to
// update the database
if (canUser('wmb'))
{
	$title	= "Wesleyan Methodist Baptisms Update";
}
else
{
	$title	= "Wesleyan Methodist Baptisms Query";
}

$and			= ' WHERE ';		// combining SQL expressions
$where			= '';
$sqlParms		= array();
$npuri			= 'WmbDoQuery.php';	// for next and previous links
$npand			= '?';		        // adding parms to $npuri
$count			= 20;
$offset			= 0;
$orderby		= 'IDMB';
$volume			= null;
$page			= null;
$lang           = 'en';

// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
// first extract the values of all supplied parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{			// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// switch on parameter name
	    case 'count':
	    {		// limit number of rows returned
			if (!preg_match("/^([0-9]{1,2})$/", $value))
			{
			    $msg  .= "Row count must be number between 1 and 99. ";
			    $count	= 20;		// replace with default
			}
			else
			    $count	= $value;
			break;
	    }		// limit number of rows returned

	    case 'offset':
	    {		// starting offset
			if (!preg_match("/^([0-9]{1,6})$/", $value))
			{
			    $msg .= 'Row offset must be number between 0 and 999,999. ';
			    $offset	= 0;
			}
			else
			    $offset	= $value;
			break;
	    }		// starting offset

	    case 'volume':
	    {
			$volume		= $value;
			break;
	    }

	    case 'page':
	    {
			$page		= $value;
			break;
	    }

	    case 'lang':
        {		// language requested
            if (strlen($value) == 2)
                $lang       = strtolower($value);
			break;
	    }		// language requested

	    case 'debug':
	    {		// handled by common.inc
			break;
	    }		// debug
	}		// switch on parameter name
}			// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (strlen($msg) == 0)
{			// no errors
	// variable portion of URI for next and previous links
	if ($offset > 0)
	{		// starting offset within existing query
	    $limit	= " LIMIT $count OFFSET $offset";
	    $tmp	= $offset - $count;
	    if ($tmp < 0)
			$npprev	= "";	// no previous link
	    else
			$npprev	= "Count={$count}&Offset={$tmp}";
	    $tmp		= $offset + $count;
	    $npnext		= "Count={$count}&Offset={$tmp}";
	}		// starting offset within existing query
	else
	{
	    $limit		= " LIMIT $count";
	    $npprev		= "";
	    $npnext		= "Count={$count}&Offset={$count}";
	}

	// construct the various portions of the SQL SELECT statement
	$surname		= null;
	$surnameSoundex		= false;
	foreach ($_GET as $key => $value)
	{			        // loop through all parameters
	    if (strlen($value) > 0)
	    {
			$fldnameLc	= strtolower($key);
			switch($fldnameLc)
			{		    // switch on parameter name
			    case 'count':
			    case 'offset':
			    case 'lang':
			    case 'debug':
			    {		// already handled
					break;
			    }		// already handled

			    case 'surname':
			    {
					$surname	= $value;
					$npuri		.= "{$npand}{$key}=" . urlencode($value);
					$npand		= '&amp;'; 
					$orderby	= 'Surname, GivenName';
					break;
			    }

			    case 'givenname':
			    case 'father':
			    case 'mother':
			    case 'minister':
			    case 'birthplace':
			    case 'birthdate':
			    case 'baptismplace':
			    case 'baptismdate':
			    case 'district':
			    case 'area':
			    case 'residence':
			    {		// match anywhere in string
					$where		.= $and;
					$where		.= "$key REGEXP :$fldnameLc";
					$sqlParms[$fldnameLc]	= $value;
					$npuri		.= "{$npand}{$key}=" .
							   urlencode($value);
					$npand		= '&amp;'; 
					$orderby	= 'Surname, GivenName';
					$and		= ' AND ';
					break;
			    }		// match in string

			    case 'surnamesoundex':
			    {		// handled under Surname
					$npuri	.= "{$npand}{$key}=" . urlencode($value);
					$npand		= '&amp;'; 
					if (strtolower($value[0]) != 'n')
					    $surnameSoundex	= true;
					$orderby	= 'Surname, GivenName';
					break;
			    }		// handled under Surname

			    case 'volume':
			    case 'page':
			    {		// exact match on field in table
					$where		.= $and;
					$where		.= "$key=:$fldnameLc";
					$sqlParms[$fldnameLc]	= $value;
					$npuri		.= "{$npand}{$key}=" .
							   urlencode($value);
					$npand		= '&amp;'; 
					$and		= ' AND ';
					break;
			    }		// exact match on field in table

			    default:
			    {		// exact match on field in table
					$where		.= $and;
					$where		.= $key . "=:$fldnameLc" .
					$sqlParms[$fldnameLc]		= $value;
					$npuri		.= "{$npand}{$key}=" . 
							   urlencode($value);
					$npand		= '&amp;'; 
					$orderby	= 'Surname, GivenName';
					$and		= ' AND ';
					break;
			    }		// exact match on field in table
			}		    // switch on parameter name
	    }			    // non-empty value
	}			        // foreach parameter

	if ($surname)
	{			// surname search specified
	    $where			.= $and;
	    $sqlParms['surname']	= $surname;
	    if (preg_match("/[.+*^$]/", $value))
	    {		// match pattern
			$where	.= "Surname REGEXP :surname";
	    }		// match pattern
	    else
	    if ($surnameSoundex)
	    {		// match soundex
			$where	.= "LEFT(SOUNDEX(Surname),4)=LEFT(SOUNDEX(:surname,4)";
	    }		// match soundex
	    else
	    {		// match exact
			$where	.= "Surname=:surname";
	    }		// match exact
	}			// surname search specified

	// execute the query
	$query		= "SELECT COUNT(*) FROM MethodistBaptisms $where";
	$stmt		= $connection->prepare($query);
	$queryText	= debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		// successful query
	    $row	= $stmt->fetch(PDO::FETCH_NUM);
	    $totalrows	= $row[0];	// get the value of COUNT(*)
	    if ($totalrows >= $count &&
			$orderby == 'IDMB' && 
			!is_null($page) &&
			strlen($page) > 0)
	    {
			$npuri		= 'WmbDoQuery.php';	// for links
			$npand		= '?';
			$limit		= "LIMIT $totalrows";
			$prevpage	= $page - 1;
			$nextpage	= $page + 1;
			$npprev		= "Volume=$volume&Page=$prevpage&Count=$count";
			$npnext		= "Volume=$volume&Page=$nextpage&Count=$count";
	    }
	    if ($debug)
			$warn	.= "<p>$queryText</p>";

	    // execute the query
	    $query		= "SELECT * FROM MethodistBaptisms $where ORDER BY $orderby $limit";
	    $stmt		= $connection->prepare($query);
	    $queryText		= debugPrepQuery($query, $sqlParms);
	    if ($stmt->execute($sqlParms))
	    {		// successful query
			$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
			$numRows	= count($result);
			if ($debug)
			    $warn	.= "<p>$queryText</p>";
	    }		// successful query
	    else
	    {		// command rejected by database server
			$msg	.= "'$queryText': " .
					   print_r($connection->errorInfo(),true);
	    }		// command rejected by database server
	}		// successful query
	else
	{		// command rejected by database server
	    $msg	.= "'" . htmlspecialchars($query) . "': " .
					   print_r($connection->errorInfo(),true);
	}		// command rejected by database server
}			// no errors
$tvolume		= $volume;
if (is_null($tvolume) || strlen($tvolume) == 0)
	$tvolume	= '1';
$breadcrumbs	= array('/genealogy.php'	=> 'Genealogy',
						'/genCountry.php?cc=CA'	=> 'Canada',
						'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
						'/displayPage.php?template=WmbQuery'
							=> 'Wesleyan Methodist Baptism Query',
						"/Ontario/WmbVolStats.php"
							=> "Statistics by Volume",
						"/Ontario/WmbPageStats.php?volume=$tvolume"
							=> "Volume $tvolume");

htmlHeader($title,
			array('/jscripts/js20/http.js',
			      '/jscripts/util.js',
			      'WmbDoQuery.js'));
?>
<body>
<?php
pageTop($breadcrumbs);
?>
    <div class='body'>
      <h1>
        <?php print $title; ?> 
        <span class='right'>
	  <a href='WmbDoQueryHelpen.html' target='_blank'>Help?</a>
        </span>
      </h1>
      <p>This tool is an alternate and <b>unauthorized</b> interface to the
	transcription © Ida Reed, 2001 and made 
	<a href='http://freepages.genealogy.rootsweb.ancestry.com/~wjmartin/wesleyan.htm'>available on Rootsweb</a>
	by Bill Martin.
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
          <div class='left'>
<?php
	if (strlen($npprev) > 0)
	{
?>
        	<a href='<?php print $npuri.$npand.$npprev; ?>' id='prevPage'>&lt;---</a>
<?php
	}
?>
          </div>
          <div class='right'>
<?php
	if (strlen($npnext) > 0)
	{
?>
    	    <a href='<?php print $npuri.$npand.$npnext; ?>' id='nextPage'>---&gt;</a>
<?php
	}
?>
          </div>
          displaying rows <?php print $offset+1; ?> to <?php print $offset + $numRows; ?>
          	of <?php print $totalrows; ?> 
          <div style='clear: both;'></div>
            </div>
            <!--- Put out the response as a table -->
            <form name='wmbform' action='nothing'>
            <table class='details'>
          <thead>
          <!--- Put out the column headers -->
            <tr>
          	<th class='colhead'>
          	  Volume
          	</th>
          	<th class='colhead'>
          	  Page
          	</th>
          	<th class='colhead'>
          	  Surname
          	</th>
          	<th class='colhead'>
          	  Given Names
          	</th>
          	<th class='colhead'>
          	  Birth Place
          	</th>
          	<th class='colhead'>
          	  Birth Date
          	</th>
          	<th class='colhead'>
          	  Details
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
	    $idir	= $row['idir'];

	    // start table row for display
?>
          <tr>
        	<td class='<?php print $class; ?> right'>
        	  <?php print $row['volume'];?>
        	</td>
        	<td class='<?php print $class; ?> right'>
        	  <?php print $row['page'];?>
        	</td>
        	<td class='<?php print $class; ?>'
        			style='min-width: 180px'>
        	  <?php print $row['surname'];?>
        	</td>
        	<td class='<?php print $class; ?>'
        			style='min-width: 180px'>
<?php
	    if ($idir > 0)
	    {
			print "<a href='../FamilyTree/Person.php?idir=$idir'\n" .
					"\ttarget='_blank'>";
	    }
?>
	  <?php print $row['givenname'];?>
<?php
	    if ($idir > 0)
	    {
			print "</a>\n";
	    }
?>
        	</td>
        	<td class='<?php print $class; ?>'
        			style='min-width: 120px'>
        	  <?php print $row['birthplace'];?>
        	</td>
        	<td class='<?php print $class; ?>'
        			style='min-width: 110px'>
        	  <?php print dateToString($row['birthdate']);?>
        	</td>
        	<td class='<?php print $class; ?>'>
        	  <button type='button' id='Details<?php print $row['idmb'];?>' class='button'>Details</button>
        	</td>
          </tr>
<?php
	    $even	= !$even;
	}		// process all rows
?>
          </tbody>
        </table>
      </form>
  <!--- Put out a line with links to previous and next section of table -->
      <div class='center'>
        <div class='left'>
<?php
	if (strlen($npprev) > 0)
	{
?>
    	  <a href='<?php print $npuri.$npand.$npprev; ?>'>&lt;---</a>
<?php
	}
?>
        </div>
        <div class='right'>
    	  <a href='<?php print $npuri.$npand.$npnext; ?>'>---&gt;</a>
        </div>
        <div style='clear: both;'></div>
      </div>
<?php
}		// display results of query
?>
    </div> <!-- end of <div id='body'> -->
<?php
pageBot($title);
?>
    <div class='balloon' id='HelpDetails'>
        Click on this button to see the whole record.
    </div>
  </body>
</html>
