<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbStats.php														*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms.															*
 *																		*
 *  History:															*
 *		2013/06/29		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use common appearance for status tables			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

$title	= 'Ontario: Wesleyan Methodist Baptisms Status';

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT District, COUNT(*) FROM MethodistBaptisms
					    GROUP BY District ORDER BY District";
	$stmt	 	= $connection->query($query);
	if ($stmt)
	{		// successful query
	    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
			print "<p>$query</p>\n";
	}		// successful query
	else
	{
	    $msg	.= "query '$query' failed: " .
					   print_r($connection->errorInfo(),true);
	}		// query failed
}			// no errors

htmlHeader($title,
	       array('/jscripts/default.js',
			     '/jscripts/util.js',
			     'WmbStats.js'));
?>
<body>
<?php
pageTop(array(	'/genealogy.php'	=> 'Genealogy',
			'/genCanada.html'	=> 'Canada',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
			'/Ontario/WmbQuery.html'
						=> 'New Wesleyan Methodist Baptisms Query'));
?>
<div class='body'>
  <h1><?php print $title; ?>
<span class='right'>
	<a href='WmbStatsHelpen.html' target='_blank'>Help?</a>
</span>
<div style='clear: both;'></div>
  </h1>
<?php
if (strlen($msg) > 0)
{		// print error messages if any
	print "<p class='message'>$msg</p>\n";
}		// print error messages if any
else
{		// display results of query
?>
  <form name='statsForm'>
<!--- Put out the response as a table -->
<table class='form'>
  <thead>
	<!--- Put out the column headers -->
	<tr>
	<th class='colhead1st'>
	District
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
	<th>
	</th>
	<th class='colhead1st'>
	District
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
	<th>
	</th>
	<th class='colhead1st'>
	District
	</th>
	<th class='colhead'>
	Done
	</th>
	<th class='colhead'>
	View
	</th>
	</tr>
  </thead>
  <tbody>
<?php
	    $columns		= 3;
	    $col		= 1;
	    $total		= 0;
	    foreach($result as $row)
	    {
			$district	= $row[0];
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
	    <?php print $district; ?>
	  </td>
	  <td class='odd bold right'>
	    <?php print $count; ?>
	  </td>
	  <td>
	    <button id='ShowDistrictStats<?php print $district; ?>' 
					type='button'>
			View
	    </button>
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
	    $totallen	= strlen($total);
	    if ($totallen > 6)
			$total	= substr($total, 0, $totallen - 6) . ',' .
					  substr($total, $totallen - 6, 3) . ',' .
					  substr($total, $totallen - 3);
	    else
	    if ($totallen > 3)
			$total	= substr($total, 0, $totallen - 3) . ',' .
					  substr($total, $totallen - 3);
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
  </form>
<?php
}		// display results of query
?>
</div>
<?php
pageBot();
?>
  <div id='HelpShowDistrictStats' class='balloon'>
    Click on this button to show township level statistics for this district.
  </div>
</body>
</html>
