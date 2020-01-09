<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbPageStats.php													*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms by page number within a volume number.						*
 *																		*
 *  Parameters:															*
 *		volume			volume number									*
 *		Debug			enable debug output								*
 *																		*
 *  History:															*
 *		2016/09/25		created											*
 *		2016/11/28		fix divide by zero								*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

// default parameters
$title	        = 'Ontario: Wesleyan Methodist Baptisms Status';
$volume	        = 0;

// process input parameters to get specific options
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $name => $value)
{			        // loop through parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($name))
	{		        // act on specific parameter
	    case 'volume':
	    {
			if (strlen($value) > 0 && ctype_digit($value))
			{
			    $volume	= $value;
			    $title	.= ": Volume $volume";
			}
			else
			    $msg	.= "Invalid volume='$value' ";
			break;
	    }		    // volume number
	}		        // act on specific parameter
}			        // loop through parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	    = "SELECT Page, COUNT(*), SUM(IDIR > 0)" .
				    	" FROM MethodistBaptisms" .
				    	" WHERE Volume=$volume" .
				    	" GROUP BY Volume, Page ORDER BY Volume, Page";
    $stmt	 	= $connection->query($query);
    $queryText  = htmlspecialchars($query);
	if ($stmt)
	{		// successful query
	    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
			print "<p>$queryText</p>\n";
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
			     'WmbPageStats.js'));
?>
<body>
<?php
pageTop(array(
			'/genealogy.php'	=> 'Genealogy',
			'/genCountry.php?cc=CA'	=> 'Canada',
			'/Canada/genProvince.php?Domain=CAON'
							    => 'Ontario',
			'/Ontario/WmbQuery.php?lang=en'
						        => 'New Wesleyan Methodist Baptisms Query',
			'WmbVolStats.php'	=> 'Statistics by Volume'));
?>
    <div class='body'>
      <h1><?php print $title; ?>
        <span class='right'>
    	  <a href='WmbPageStatsHelpen.html' target='_blank'>Help?</a>
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
        <input type='hidden' name='Volume' id='Volume'
        	value='<?php print $volume; ?>'>
        <!--- Put out the response as a table -->
        <table class='form'>
          <thead>
        	<!--- Put out the column headers -->
        	<tr>
        	  <th class='colhead1st'>
        	    Page
        	  </th>
        	  <th class='colhead'>
        	    Done
        	  </th>
        	  <th class='colhead'>
        	    Linked
        	  </th>
        	  <th class='colhead'>
        	    Percent
        	  </th>
        	  <th class='colhead'>
        	    Details
        	  </th>
        	</tr>
          </thead>
          <tbody>
<?php
	    $total		= 0;
	    $totalLinked	= 0;
	    foreach($result as $row)
	    {
			$page		= $row[0];
			$count		= $row[1];
			$total		+= $count;
			$linked		= $row[2];
			$totalLinked	+= $linked;
			$pctLinked	= 100 * $linked / $count;
?>
        	<tr>
        	  <td class='odd bold right'>
        	    <?php print $page; ?>
        	  </td>
        	  <td class='odd bold right'>
        	    <?php print number_format($count); ?>
        	  </td>
        	  <td class='odd bold right'>
        	    <?php print number_format($linked); ?>
        	  </td>
        	  <td class='<?php print pctClass($pctLinked); ?>'>
        	    <?php print number_format($pctLinked, 2); ?>%
        	  </td>
        	  <td>
        	    <button id='ShowPageDetails<?php print $page; ?>' type='button'>
        			Details
        	    </button>
        	  </td>
        	</tr>
<?php
	    }		// process all rows
	    if ($total > 0)
			$pctLinked	= 100 * $totalLinked / $total;
	    else
			$pctLinked	= 0;
?>
          </tbody>
          <tfoot>
        	<tr>
        	  <td class='odd bold right first'>
        	    Total
        	  </td>
        	  <td class='odd bold right'>
        	    <?php print number_format($total); ?>
        	  </td>
        	  <td class='odd bold right'>
        	    <?php print number_format($totalLinked); ?>
        	  </td>
        	  <td class='<?php print pctClass($pctLinked); ?>'>
        	    <?php print number_format($pctLinked, 2); ?>%
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
    <div id='HelpShowPageStats' class='balloon'>
        Click on this button to show the transcription of the page.
    </div>
  </body>
</html>
