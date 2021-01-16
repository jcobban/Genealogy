<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  UploadSubdistXml.php												*
 *																		*
 *  Script to upload the transcription and page table for a page within	*
 *  an enumeration division from the development site to the production	*
 *  site.																*
 *																		*
 *  Parameters (passed by POST):										*
 *		Census			the census identifier including domain			*
 *		District		district number within the census				*
 *		SubDistrict		sub-district letter or number within district	*
 *		Division		enumeration division within the sub-district	*
 *		Page			page to copy									*
 *																		*
 *  History:															*
 *		2012/01/26		support Copy button for uploading to server		*
 *		2012/03/10		improve error handling							*
 *		2012/09/16		use census identifier instead of year in		*
 *						parameter										*
 *		2013/11/26		handle database server failure gracefully		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						use multiple row insert to speed up load		*
 *		2020/10/10      remove field prefix for Pages table             *
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/common.inc';

// display the results
print("<?xml version='1.0' encoding='UTF-8'?>\n");

print "<upload>\n";

$server	= $_SERVER['SERVER_NAME'];
if ($server != 'localhost')
    $msg	.= "This function cannot be performed on $server. ";



// default values for parameters
$censusId		= '';
$censusYear		= 9999;
$province		= '';
$distId		= '';
$subdistId	= '';
$SubDivision	= '';
$page		= null;
$sel		= '';	// census data select expression
$ptsel		= '';	// page table select expression
$and		= '';

print "    <parms>\n";
foreach ($_POST as $key => $value)
{			// loop through all parameters
    print "<$key>" . htmlspecialchars($value) . "</$key>\n";
    switch($key)
    {		// act on parameter name
        case 'District':
        {		// district number
    		$distId	= $value;
    		$result	= array();
    		if (!preg_match("/^([0-9]+)(\.[05])?$/", $distId, $result))
    		    $msg	.= "District value '$distId' is invalid. ";
    		else
    		{
    		    if (count($result) > 2 && $result[2] == '.0')
    			$distId	= $result[1];	// integral portion only
    		}
    		$sel	.= $and . 'District=' . $value;
    		$ptsel	.= $and . 'Pages.DistId=' . $value;
    		$and	= ' AND ';
    		break;
        }		// District number

        case 'Census':
        {		// Census identifier
    		$censusId	= $value;
    		try
    		{
    		    $censusRec	= new Census(array('censusid'	=> $value,
    						   'collective'	=> 0));
    		    $censusYear	= intval(substr($censusId, 2));
    		    if ($censusYear < 1867)
    			$province	= substr($censusId, 0, 2);
    		}
    		catch (Exception $e) {
    		    $msg	.= "Invalid Census identifier '$censusId'. ";
    		}
    		break;
        }		// Census identifier

        case 'Province':
        {		// province code deprecated
    		break;
        }		// province code deprecated

        case 'SubDistrict':
        {	// subdistrict code
    		$subdistId	= $value;
    		$sel	.= $and . 'SubDistrict=' . $connection->quote($value);
    		$ptsel	.= $and . 'Pages.SdId=' . $connection->quote($value);
    		$and	= ' AND ';
    		break;
        }	// subdistrict code

        case 'Division':
        {	// division code
    		$division	= $value;
    		$sel	.= $and . 'Division=' . $connection->quote($value);
    		$ptsel	.= $and . 'Pages.`Div`=' . $connection->quote($value);
    		$and	= ' AND ';
    		break;
        }	// division code

        case 'Page':
        {	// page code
    		$page	= $value;
    		$sel	.= $and . 'Page=' . $connection->quote($value);
    		$ptsel	.= $and . 'Pages.Page=' . $connection->quote($value);
    		$and	= ' AND ';
    		break;
        }	// page code

    }	// act on parameter name
}		// Census present
print "    </parms>\n";

// check for missing parameters
if ($censusId == 9999)
{		// Census missing
    $censusId	= '';
    $msg	.= 'Census parameter missing. ';
}		// Census missing

if ($distId == '')
{		// District missing
    $msg	.= 'District parameter missing. ';
}		// District missing

if ($province == '' && $censusYear < 1867)
{		// Province missing
    $msg	.= 'Province parameter missing. ';
}		// Province missing

// the invoker must explicitly provide the SubDistrict code
if (strlen($subdistId) == 0)
    $msg	.= 'SubDistrict parameter missing. ';

// the invoker must explicitly provide the Page number
if ($page === null)
    $msg	.= 'Page parameter missing. ';

// the user must be authorized to edit the database
if (!canUser('edit'))
{		// user can not update database
    $msg	.= 'User is not authorized to upload to the production server. ';
}		// user can not update database

// if no errors execute the query
if (strlen($msg) == 0)
{		// no errors
    if ($censusYear < 1867)
    {		// pre-confederation
        $sel	.= $and . 'Province=' . $connection->quote($province);
    }		// pre-confederation
    $ptsel		.= $and . 'Pages.Census=' . $connection->quote($censusId);

    // prepare to update the production server
    $dsn		= $servers['remote'];
    $dburl		= $dsn['phptype'] . 
    			      ':dbname=' . $dsn['database'] .
    			      ';host=' . $dsn['hostspec'] .
    			      ';charset=utf8';
    try {
        $connection2	= new PDO($dburl,
    					  $dsn['username'], 
    					  $dsn['password']);

        if ($debug)
        {		// trace output
    		$warn  .= "<p>connection established to production server '$dburl'.</p>\n";
        }		// trace output

        $connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    } catch(Exception $e)
    {		// error establishing connection
        $msg	.= "Unable to connect to production server using: " .
    			      "dburl='$dburl', " .
    			      $e->getMessage() . ". ";
        $connection2	= null;
        print "</upload>\n";
        exit;
    }		// error establishing connection

    // upload the census data from the development server to
    // the production server

    // query the development server for the census transcription
    // data for this enumeration division
    $qry	= "SELECT * FROM Census$censusYear WHERE $sel";
    print "<cmd>" . htmlspecialchars($qry) . "</cmd>\n";
    $stmt		= $connection->query($qry);
    if ($stmt)
    {			// main query successful
        $result		= $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0)
        {		// something to copy
    		// delete the old records from the production server
    		$delete		= "DELETE FROM Census$censusYear WHERE $sel";
    		print "<delete>" . htmlspecialchars($delete) . "</delete>\n";
    		$stmt		= $connection2->query($delete);
    		if ($stmt)
    		{
    		    $eresult	= $stmt->rowCount();
    		    print "<deleted>$eresult</deleted>\n";
    		}
    		else
    		{
    		    print "<msg>" . print_r($connection->errorInfo(),true) .
    			  "</msg>\n";
    		}		// error on request

    		// prepare a statement to insert the whole page in a single command
    		$stmt		= null;
    		$insert		= "INSERT INTO Census$censusYear (";
    		$onerow		= '(';
    		$comma		= '';
    		foreach($result[0] as $name => $value)
    		{		// loop through all columns
    		    $insert	.= $comma . $name;
    		    $onerow	.= $comma . '?';
    		    $comma	= ', ';
    		}		// loop through all columns
    		$onerow		.= ')';
    		$insert		.= ') VALUES(';
    		$comma		= '';
    		for($i = 0; $i < count($result); $i++)
    		{
    		    $insert	.= $comma . $onerow;
    		    $comma	= ', ';
    		}
    		$insert		.= ')';
    		$stmt		= $connection2->prepare($insert); 
    		$parms		= array();
    		foreach($result as $row)
    		{		// loop through all rows
    		    foreach($row as $key => $value)
    			$parms[]	= $value;
    		}		// loop through all rows
    		print "<stmt>'$insert', parms=" . print_r($parms, true) . "</stmt>\n";

    		// execute the prepared statement to perform multiple row insert
    		if ($stmt->execute($parms))
    		{		// successfully inserted
    		    $eresult		= $stmt->rowCount();
    		    print "<inserted>$eresult</inserted>\n";
    		}		// successfully inserted
    		else
    		{		// error on insert
    		    print "<msg>" .
    			  print_r($connection->errorInfo(),true) .
    			  "</msg>\n";
    		}		// error on request

    		// release the prepared statement
    		$stmt		= null;

    		// upload the page table records from the development server to
    		// the production server

    		// query the development server for the page table entries
    		// for this enumeration division
    		$qry	= "SELECT * FROM Pages WHERE $ptsel";
    		print "<cmd>" . htmlspecialchars($qry) . "</cmd>\n";
    		$stmt	= $connection->query($qry);
    		if ($stmt)
    		{
    		    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);

    		    // delete the old page record from the production server
    		    $delete		= "DELETE FROM Pages WHERE $ptsel";
    		    print "<delete>" . htmlspecialchars($delete) . "</delete>\n";
    		    $stmt		= $connection2->query($delete);
    		    if ($stmt)
    		    {
    			$eresult	= $stmt->rowCount();
    			print "<deleted>$eresult</deleted>\n";
    		    }
    		    else
    		    {
    			print "<msg>" .
    			      print_r($connection->errorInfo(),true) .
    			      "</msg>\n";
    		    }		// error on request

    		    $insert		= '';
    		    $stmt		= null;
    		    foreach($result as $row)
    		    {		// loop through all rows
    			if (strlen($insert) == 0)
    			{		// create template insert
    			    $insert	= "INSERT INTO Pages (";
    			    $values	= '';
    			    $comma	= '';
    			    foreach($row as $name => $value)
    			    {	// loop through all fields
    				$insert	.= $comma . $name;
    				$values	.= $comma . ':' . $name;
    				$comma	= ', ';
    			    }	// loop through all fields
    			    $insert	.= ') VALUES(' . $values . ')';
    			    print "<insert>" . htmlspecialchars($insert) . "</insert>\n";
    			    $stmt	= $connection2->prepare($insert); 
    			}		// create template insert
    			print "<execute>";
    			print 'page=' . $row['page'];
    			print "</execute>\n";
    			if ($stmt->execute($row))
    			{
    			    $eresult	= $stmt->rowCount();
    			    print "<inserted>$eresult</inserted>\n";
    			}
    			else
    			{
    			    print "<msg>" .
    				  print_r($connection->errorInfo(),true) .
    				  "</msg>\n";
    			}	// error on request
    		    }		// loop through all rows

    		    // release the prepared statement
    		    $stmt	= null;
    		}		// successful query
    		else
    		{		// error on query
    		    print "<msg>" . print_r($connection->errorInfo(),true) .
    			  "</msg>\n";
    		}		// error on request
        }			// something to copy
        else
    		print "<msg>Nothing to copy</msg>\n";
    }			// main query successful
    else
    {			// main query failed
        print "<msg>" .
    		  print_r($connection->errorInfo(),true) .
    		  "</msg>\n";
    }			// main query failed
}				// no validation errors
else
    print "<msg>" . htmlspecialchars($msg) . "</msg>\n";

// close off the XML document
print "</upload>\n";
?>
