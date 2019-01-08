<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbVolStats.php														*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms by volume number.											*
 *																		*
 *  History:															*
 *		2016/09/25		created											*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

$title	= 'Ontario: Wesleyan Methodist Baptisms Status';

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT Volume, COUNT(*), MAX(Page)" .
					" FROM MethodistBaptisms" .
					" GROUP BY Volume ORDER BY Volume";
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
			     'WmbVolStats.js'));
?>
<body>
<?php
pageTop(array(
			'/genealogy.php'	=> 'Genealogy',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							=> 'Ontario',
			'/Ontario/WmbQuery.html'		=> 'New Wesleyan Methodist Baptisms Query'));
?>
<div class='body'>
  <h1><?php print $title; ?>
<span class='right'>
	<a href='WmbVolStatsHelpen.html' target='_blank'>Help?</a>
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
	    Volume
	  </th>
	  <th class='colhead'>
	    Pages
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
	    $total		= 0;
	    foreach($result as $row)
	    {
			$volume		= $row[0];
			$count		= $row[1];
			$total		+= $count;
?>
	<tr>
	  <td class='odd bold left first'>
	    <?php print $volume; ?>
	  </td>
	  <td class='odd bold right'>
	    <?php print number_format($row[2]); ?>
	  </td>
	  <td class='odd bold right'>
	    <?php print number_format($count); ?>
	  </td>
	  <td>
	    <button id='ShowVolStats<?php print $volume; ?>' type='button'>
			View
	    </button>
	  </td>
	</tr>
<?php
	    }		// process all rows

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
  <div id='HelpShowVolStats' class='balloon'>
    Click on this button to show page level statistics for this volume.
  </div>
</body>
</html>
