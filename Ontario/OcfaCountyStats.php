<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  OcfaCountyStats.php													*
 *																		*
 *  Display statistics about the transcription of cemetery inscriptions.*
 *  for a particular county.											*
 *																		*
 *  Parameters:															*
 *		county			name of county									*
 *		debug			control debug output							*
 *																		*
 *  History:															*
 *		2012/05/06		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use standard appearance for stats reports		*
 *						add link to help documentation					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$countyName	    = null;

// get parameter values
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'county':
	    {
            $countyName		    = $value;
			break;
	    }		// county 

	}		// act on specific parameters
}			// loop through all parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (is_null($countyName))
{
	$countyName     = 'unknown';
	$msg	        .= "Missing mandatory parameter 'county'";
}

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT Township, SUM(Surname != '') FROM Ocfa " .
					    "WHERE County=:county" . 
					    " GROUP BY Township ORDER BY Township";
    $stmt	 	= $connection->prepare($query);
    $queryText  = debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		// successful query
	    $result	= $stmt->fetch(PDO::FETCH_NUM);
	}		// successful query
	else
	{
	    $msg	.= "query '$queryText' failed: " .
					   print_r($stmt->errorInfo(),true);
	}		// query failed
}			// no errors

htmlHeader("Ontario: OCFA Status for $countyName County",
	       array('/jscripts/default.js',
			     '/jscripts/util.js',
			     '/jscripts/default.js'));
?>
<body>
<?php
pageTop(array(
			'/genealogy.php'	=> 'Genealogy',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
			'/Ontario/OcfaQuery.html'	=> 'New Query',
			'/Ontario/OcfaStats.php'		=> 'Stats'));
?>
<div class='body'>
  <h1>
<span class='right'>
	<a href='OcfaCountyStatsHelpen.html' target='_blank'>Help?</a>
</span>
<div style='clear: both;'></div>
        Ontario: OCFA Status for <?php print $countyName ?>  County
  </h1>
<?php
	if (strlen($msg) > 0)
	{		// print error messages if any
	    print "<p class='message'>$msg</p>\n";
	}		// print error messages if any
	else
	{		// display results of query
?>
<!--- Put out the response as a table -->
<table class='form'>
  <thead>
	<!--- Put out the column headers -->
	<tr>
	  <th class='colhead1st'>
	    Township
	  </th>
	  <th class='colhead'>
	    Done
	  </th>
	</tr>
  </thead>
  <tbody>
<?php
	    $columns		= 1;
	    $col		= 1;
	    $total		= 0;
	    foreach($result as $row);
	    {
			$township	= $row[0];
			$count		= $row[1];
			$total		+= $count;
			if (strlen($count) > 3)
			    $count	= substr($count, 0, strlen($count) - 3) . ',' .
						  substr($count, strlen($count) - 3);
			if ($col == 1)
			{
?>
	<tr>
<?php
			}
?>
	  <td class='odd bold left first'>
	    <?php print $township; ?>
	  </td>
	  <td class='odd bold right'>
	    <?php print $count; ?>
	  </td>
<?php
			$col++;
			if ($col > $columns)
			{	// at column limit, end row
			    $col	= 1;
?>
	</tr>
<?php
			}	// at column limit, end row
			else
			{	// start new column
?>
	  <td>
	  </td>
<?php
			}	// start new column
	    }		// process all rows

	    // end last row if necessary
	    if ($col != 1)
	    {		// partial last column
?>
	</tr>
<?php
	    }		// partial last column

	    // insert comma into formatting of total
	    if (strlen($total) > 3)
			$total	= substr($total, 0, strlen($total) - 3) . ',' .
					  substr($total, strlen($total) - 3);
?>
  </tbody>
  <tfoot>
	<tr>
	  <td class='odd bold right first'>
	    Total
	  </td>
	  <td class='odd bold right'>
	    <?php print $total; ?>
	  </td>
	</tr>
  </tfoot>
</table>
<?php
	}		// display results of query
?>
</div>
<?php
pageBot();
?>
</body>
</html>
