<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  eftSimul.php							*
 *									*
 *  Simulate the response from NxPay.com				*
 *									*
 *  Parameters (passed by method='post'):				*
 *									*
 *  History:								*
 *	2015/09/03	created						*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/common.inc';

    // emit the xml header
    header("content-type: text/plain");

    $api_service	= 0;
    foreach($_POST as $key => $value)
    {		// loop through all parameters
	if ($key == 'api_service')
	    $api_service	= intval($value);
    }		// loop through all parameters
    switch($api_service)
    {
	case 2:
	{
	    print "message=not%20found";
	    print "&response_code=E0020\n";
	    break;
	}

	case 19:
	{
	    print "total_balance=200.00";
	    print "&response_code=0";
	    break;
	}

	default:
	{
	    print "message=not%20found";
	    print "&response_code=E0020\n";
	    break;
	}

    }			// switch			
?>
