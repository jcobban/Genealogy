<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  WmbDistrictStats.php												*
 *																		*
 *  Display statistics about the transcription of Wesleyan Methodist	*
 *  Baptisms for a particular district.									*
 *																		*
 *  Parameters:															*
 *		district		name of district						        *
 *																		*
 *  History:															*
 *		2013/06/28		created											*
 *		2013/08/04		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/11/27		handle database server failure gracefully		*
 *						improve parameter handling						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/14		use standard appearance of status report		*
 *						add help page									*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/30		use composite cell style classes				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$district	= 'unknown';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			    // loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		    // process specific named parameters
	    case 'district':
        {
            $district       = $value;
            break;
        }
    }		    // process specific named parameters
}			    // loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

if ($district == 'unknown')
	$msg	.= 'Missing mandatory parameter "district".  ';

if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT Area, SUM(Surname != '') FROM MethodistBaptisms" .
					    "WHERE District=:district" .
                        " GROUP BY Area ORDER BY Area";
    $sqlParms   = array('district'  => $district);
    $stmt	 	= $connection->prepare($query);
    $queryText  = debugPrepQuery($query, $sqlParms);
	if ($stmt->execute($sqlParms))
	{		// successful query
	    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
            $warn   .= "<p>WmbDistrictStats.php: " . __LINE__ .
                            " query=$queryText</p>\n";
	}		// successful query
	else
	{
	    $msg	.= "query '$queryText' failed: " .
					   print_r($stmt->errorInfo(),true);
	}		// query failed
}			// no errors

htmlHeader($title,
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
			'/Ontario/WmbQuery.html'
							=> 'New Query',
			'WmbStats.php'		=> 'Overall Status'));
?>
    <div class='body'>
      <h1>
        <span class='right'>
    	  <a href='WmbDistrictStatsHelpen.html' target='_blank'>Help?</a>
        </span>
        Ontario: Wesleyan Methodist Baptisms Status for 
        <?php print $district; ?> District
      <span style='clear: both;'></span>
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
	          Area
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
	    foreach($result as $row)
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
