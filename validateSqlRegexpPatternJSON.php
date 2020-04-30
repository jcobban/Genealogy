<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  validateSqlRegexpPattern.php										*
 *																		*
 *  Given an Extended Regular Expression validate that it is supported  *
 *  by the SQL server.                                                  *
 *																		*
 *  Parameters:															*
 *		expression  	new password    								*
 *																		*
 *  History:															*
 *		2020/04/25		Created											*
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// values of parameters
$pattern                        = null;
$lang                           = 'en';

// emit the JSON header
print "{\n";

// get the updated values of the fields in the record
print "    \"parms\" : {\n";
$comma                          = '';
foreach ($_GET as $key => $value)
{			                // loop through all parameters
    $escvalue                   = str_replace('"','\\"',$value);
    print "$comma        \"$key\" : \"$escvalue\"";
    $comma                      = ',';
    switch(strtolower($key))
    {		                // act on specific parameters
        case 'pattern':
        {		            // extended regular expression pattern to validate
            $pattern            = $value;
            break;
        }		            // unique numeric id of existing entry

        case 'lang':
        {		            // preferred language of communication
            $lang               = FtTemplate::validateLang($value);
            break;
        }		            // preferred language of communication

    }		                // act on specific parameters
}			                // loop through all parameters
print "\n    },\n";         // end "parms" object
$template       = new FtTemplate("validateSqlRegexpPattern$lang.json");

if (is_null($pattern))
{
    $msg		.= 'Missing or invalid mandatory parameter pattern=. ';
}
 
if (strlen($msg) == 0)
{		// no parameter errors detected
    $query      = "SELECT Surname FROM tblNR WHERE Surname REGEXP :pattern";
    $sqlparms   = array('pattern'       => $pattern);
    $stmt       = $connection->prepare($query);
	$queryText	            	= debugPrepQuery($query, $sqlparms);
    print '    "query" : ' . json_encode($queryText);
    if ($stmt->execute($sqlparms))
    {
        print "\n";
    }
    else
    {
		$errorInfo      = $stmt->errorInfo();
		print ",\n" . '    "msg":' . json_encode(" result=$errorInfo[2], $errorInfo[0], $errorInfo[1]") . "\n";
    }
}		// no parameter errors detected
else
{
    print "    \"msg\" : " . json_encode($msg) . "\n";
}

// close root node of JSON output
print "}\n";
