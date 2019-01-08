<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathRegStats.php								                    *
 *										                                *
 *  Display statistics about the transcription of death registrations.	*
 *										                                *
 *  Parameters:									                        *
 *		RegDomain	domain consisting of country code and state	        *
 *										                                *
 *  History:								                        	*
 *		2011/01/09	    created						                    *
 *		2011/03/16	    display in 3 columns				            *
 *				        include 2nd level breakdown			            *
 *		2011/08/10	    add help					                    *
 *		2011/10/27	    use <button> instead of <a> for view action	    *
 *				        support mouseover help				            *
 *	    2013/08/04	    use pageTop and pageBot to standardize appearance
 *		2013/11/27	    handle database server failure gracefully	    *
 *		2013/12/07	    $msg and $debug initialized by common.inc	    *
 *		2013/12/24	    use CSS for layout instead of tables		    *
 *				        support RegDomain parameter			            *
 *		2014/01/13	    use CSS for table style				            *
 *		2015/07/02	    access PHP includes using include_path		    *
 *		2015/09/28	    migrate from MDB2 to PDO			            *
 *		2016/04/25	    replace ereg with preg_match			        *
 *		2016/05/20	    use class Domain to validate domain code	    *
 *		2017/02/07	    use class Country				                *
 *		2018/06/01	    add support for lang parameter			        *
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *										                                *
 *  Copyright &copy; 2018 James A. Cobban					            *
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc			    = 'CA';		// default country code
$countryName	= 'Canada';	// default country name
$domain	    	= 'CAON';	// default domain
$domainName		= 'Ontario';
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
	switch(strtolower($key))
	{		// process specific named parameters
	    case 'regdomain':
	    {
			$domainObj	= new Domain(array('domain'	=> $value,
		    							   'language'	=> 'en'));
			if ($domainObj->isExisting())
			{
			    $domain	= $value;
			    $cc		= substr($domain, 0, 2);
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->getName();
			    $domainName	= $domainObj->get('name');
			}
			else
			{
			    $msg	.= "Domain '$domain' must be a supported two character country code followed by a two character state or province code. ";
			    $domainName	= "Domain" . $domain;
			}
			break;
	    }		// RegDomain

	    case 'lang':
	    {
			if (strlen($value) == 2)
				$lang		= strtolower($value);
			break;
	    }		//lang

	    case 'debug':
	    {
			break;
	    }		// handled by common code

	    default:
	    {
			$warn	.= "Unexpected parameter $key='$value'. ";
			break;
	    }		// any other paramters
	}		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

// if no error messages Issue the query
if (strlen($msg) == 0)
{			// no errors
	// execute the query
	$query	= "SELECT D_RegYear, SUM(D_Surname != ''), SUM(D_IDIR > 0) " .
						"FROM Deaths
						    WHERE D_RegDomain='$domain'
						    GROUP BY D_RegYear ORDER BY D_RegYear";
	$stmt	 	= $connection->query($query);
	if ($stmt)
	{		// successful query
	    $result		= $stmt->fetchAll(PDO::FETCH_NUM);
	    if ($debug)
				$warn		.= "<p>$query</p>\n";
	}		// successful query
	else
	{
	    $msg	.= "query '$query' failed: " .
						    print_r($connection->errorInfo(),true);
	}		// query failed
}			// no error messages

$title	= $domainName . ": Death Registration Status";

htmlHeader($title,
			array('/jscripts/util.js',
			      'DeathRegStats.js'));
?>
    <body>
<?php
pageTop(array(
			"/genealogy.php?lang=$lang"		=> 'Genealogy', 
			"/genCountry.php?cc=CA&lang=$lang"	=> $countryName,
			"/Canada/genProvince.php?domain=$domain&lang=$lang"
											=> $domainName,
			"DeathRegQuery.php?RegDomain=$domain&lang=$lang"
											=> 'New Death Query'));
?>
    <div class='body'>
      <h1><?php print $title; ?>
        <span class='right'>
			<a href='DeathRegStatsHelpen.html' target='_blank'>Help?</a>
        </span>
      </h1>
<?php
if (strlen($warn) > 0)
{		// print warning messages if any
    print "<p class='warning'>$warn</p>\n";
}		// print warning messages if any

if (strlen($msg) > 0)
{		// print error messages if any
    print "<p class='message'>$msg</p>\n";
}		// print error messages if any
else
{		// display results of query
?>
      <form id='display' action='donothing.php' method='get'>
        <!--- Put out the response as a table -->
        <table class='details'>
          <!--- Put out the column headers -->
          <thead>
			<tr>
			  <th class='colhead'>
			RegYear
			  </th>
			  <th class='colhead'>
			Done
			  </th>
			  <th class='colhead'>
			Linked
			  </th>
			  <th class='colhead'>
			View
			  </th>
			  <th>
			  </th>
			  <th class='colhead'>
			RegYear
			  </th>
			  <th class='colhead'>
			Done
			  </th>
			  <th class='colhead'>
			Linked
			  </th>
			  <th class='colhead'>
			View
			  </th>
			  <th>
			  </th>
			  <th class='colhead'>
			RegYear
			  </th>
			  <th class='colhead'>
			Done
			  </th>
			  <th class='colhead'>
			Linked
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
			    $totalLinked	= 0;
			    $rownum		= 0;
			    $yearclass		= "odd right";
			    foreach($result as $row)
			    {
						$rownum++;
						$regYear	= $row[0];
						$count		= $row[1];
						$linked		= $row[2];
						if ($count == 0)
						    $pctLinked	= 0;
						else
						    $pctLinked	= 100 * $linked / $count;
						$total		+= $count;
						$totalLinked	+= $linked;
						$count		= number_format($count); 
						$linked		= number_format($linked); 
						if ($col == 1)
						{
?>
			<tr>
<?php
						}
?>
			  <td class='<?php print $yearclass; ?>'>
			      <?php print $regYear; ?>
			  </td>
			  <td class='<?php print $yearclass; ?>'>
			      <?php print $count; ?>
			  </td>
			  <td class='<?php print pctClass($pctLinked); ?>'>
			      <?php print $linked; ?>
			  </td>
			  <td class='left'>
			      <button type='button'
								id='YearStats<?php print $domain.$regYear; ?>'>
						View
			      </button>
			  </td>
<?php
						$col++;
						if ($col > $columns)
						{	// at column limit, end row
						    $col	= 1;
						    if ($yearclass == "odd right")
								$yearclass	= "even right";
						    else
								$yearclass	= "odd right";
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
			    $total		= number_format($total);
			    $totalLinked	= number_format($totalLinked);
?>
		  </tbody>
		  <tfoot>
			<tr>
			  <td class='total right'>
			    Totals
			  </td>
			  <td class='total right'>
			      <?php print $total; ?>
			  </td>
			  <td class='total right'>
			      <?php print $totalLinked; ?>
			  </td>
			</tr>
		  </tfoot>
		</table>
	  </form>
<?php
}		// display results of query
?>
    </div> <!-- end of <div id='body'> -->
<?php
pageBot();
?>
    <div class='balloon' id='HelpYearStats'>
      Click on this button to display the geographical breakdown of the
      transcription for the specific year.
    </div>
    <div class='balloon' id='HelpRegYear'>
      This field shows the year for which the statistics apply.
    </div>
  </body>
</html>
