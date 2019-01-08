<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateTemple.php							*
 *									*
 *  Handle a request to update an individual temple in 			*
 *  the Legacy family tree database.					*
 *									*
 *  Parameters:								*
 *	idtr	unique numeric identifier of the Temple record		*
 *		to update						*
 *	others	any field name defined in the Temple record		*
 *									*
 *  History:								*
 *	2012/12/08	created						*
 *	2013/02/23	support new database table format		*
 *			simplify code					*
 *			correct minor errors in messages		*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2017/09/02	class LegacyTemple renamed to Temple		*
 *	2017/09/12	use set(					*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/common.inc';

    // enable debug output
    $debug		= false;

    // process parameters
    $msg		= '';
    $idtr		= null;
    $temple		= null;

    foreach($_POST as $key => $value)
    {		// loop through all parameters
	switch(strtolower($key))
	{	// act on specific keys
	    case 'idtr':
	    {
		$matched	= preg_match('/^[0-9]{1,3}$/', $value);
		if ($matched == 1)
		{		// valid syntax
		    $idtr		= $value;
		    try {
			$temple		= new Temple(array('idtr' => $idtr));
		    } catch(Exception $e) {
			$msg	.= 'Undefined value of idtr=' . $idtr;
		    }		// catch
		}		// valid syntax
		else
		    $msg	.= "idtr value '$value' is invalid. ";
		break;
	    }

	    case 'code':
	    {
		$value		= trim($value);
		$matched	= preg_match('/^[A-Z2]{4,5}$/', $value);
		if ($matched == 1)
		{		// valid syntax
		    if ($temple)
			$temple->set('code', $value);
		}		// valid syntax
		else
		    $msg	.= "Code value '$value' is invalid. ";
		break;
	    }

	    case 'temple':
	    {
		if ($temple)
		    $temple->set('temple', $value);
		break;
	    }

	    case 'templestart':
	    case 'templeend':
	    {
		if ($value == '0')
		    $value	= '';
		if ($temple)
		    $temple->set($key, $value);
		break;
	    }

	    case 'used':
	    {
		$matched	= preg_match('/^[01]$/', $value);
		if ($matched == 1)
		{		// valid syntax
		    if ($temple)
			$temple->set('used', $value);
		}		// valid syntax
		else
		    $msg	.= "Used flag value '$value' is invalid. ";
		break;
	    }

	    case 'tag1':
	    {
		$matched	= preg_match('/^[01]$/', $value);
		if ($matched == 1)
		{		// valid syntax
		    if ($temple)
			$temple->set('tag1', $value);
		}		// valid syntax
		else
		    $msg	.= "Tag1 flag value '$value' is invalid. ";
		break;
	    }

	    case 'qstag':
	    {
		$matched	= preg_match('/^[01]$/', $value);
		if ($matched == 1)
		{		// valid syntax
		    if ($temple)
			$temple->set('qstag', $value);
		}		// valid syntax
		else
		    $msg	.= "qsTag flag value '$value' is invalid. ";
		break;
	    }

	    case 'picidtype':
		break;

	    default:
	    {		// any other fields
		if ($temple)
		    $temple->set($key, $value);
		break;
	    }		// any other fields

	}	// act on specific keys
    }		// loop through all parameters

    if (is_null($temple))
	$msg	.= "Unable to get Temple record. ";

    if (strlen($msg) == 0)
    {		// no errors
	// update the record in the database server
	$temple->save(false);

	// redirect to main descriptive page for temple
	$pattern		= $temple->getName();
	if (strlen($pattern) > 5)
	    $pattern	= substr($pattern, 0, 5);
	header('Location: Temples.php?pattern=^' .
		    urlencode($pattern));
	exit;
    }		// no errors

    htmlHeader($msg);
?>
<body>
  <h1>updateTemple.php: <?php print $msg; ?></h1>
</body>
</html>
