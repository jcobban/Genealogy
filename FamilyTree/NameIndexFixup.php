<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  NameIndexFixup.php							*
 *									*
 *  This script corrects individuals that have more than one primary	*
 *  nominal index entry in tblNX.					*
 * 									*
 *  History:								*
 *	2015/03/04	created						*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *	2016/01/19	add id to debug trace				*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/common.inc';

    if (!canUser('all'))
	$msg	.= 'You are not authorized to update the database. ';


    htmlHeader('Name Index Fixup', array());
?>
<body>
<h1>Name Index Fixup</h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
<p class='message'><?php print $msg; ?></p>
<?php
    }
    else
    {			// no errors in parameters
	$query	= "SELECT IDNX, tblNX.IDIR, tblIR.givenName, tblIR.surname FROM tblNX INNER JOIN tblIR ON tblIR.IDIR=tblNX.IDIR WHERE `Order`=0 AND (tblNX.Surname<>tblIR.Surname OR tblNX.GivenName<>tblIR.GivenName) LIMIT 50";
	$stmt	= $connection->query($query);
	if ($stmt)
	{		// succeeded
?>
    <p><?php print $query; ?></p>
<?php
	    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
	    foreach($result as $row)
	    {		// loop through matches
		$idnx		= $row['idnx'];
		$idir		= $row['idir'];
		$givenName	= $row['givenname'];
		$surname	= $row['surname'];

		$update		= "UPDATE tblNX SET GivenName=" .
				  $connection->quote($givenName) .
				  ", Surname=" .
				  $connection->quote($surname) .
				  " WHERE IDNX=$idnx";
		$stmt		= $connection->query($update);
		if ($stmt)
		{	// updated
		    $updCount	= $stmt->rowCount();
?>
    <p><?php print $update; ?> updated <?php print $updCount; ?> records</p>
<?php
		}	// updated
		else
		{	// error performing update
		    $msg	.= "'$update' " . $updCount->getMessage();
		}	// error performing update
	    }		// loop through matches
	}		// succeeded
	else
	{		// error performing query
	    $msg	.= "'$query' " . print_r($connection->errorInfo(), true);
	}		// error performing query
    }			// no errors in parameters

    if (strlen($msg) > 0)
    {
?>
<p class='message'><?php print $msg; ?></p>
<?php
    }
?>
  </body>
</html>
