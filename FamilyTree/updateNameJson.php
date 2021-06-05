<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateNameJson.php                                                  *
 *                                                                      *
 *  Handle a request to update an individual name in                    *
 *  the Legacy family tree database and return a JSON response.         *
 *                                                                      *
 *  Parameters (passed by POST):                                        *
 *      idnx    unique numeric identifier of name.                      *
 *              If this is zero (0) a new name is created.              *
 *      others  valid field names within the Name record.               *
 *                                                                      *
 *  History:                                                            *
 *      2020/12/28      created                                         *
 *      2021/02/15      fix syntax of JSON
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the JSON header
print "{\n";

$comma                      = '';
$idnx                       = null;
foreach($_POST as $key => $value)
{
    if (strtolower($key) == 'idnx' && ctype_digit($value))
        $idnx   = intval($value);
}

// get the updated values of the fields in the record
if (is_int($idnx))
{
    // locate existing name record, or create a new empty record
    $name       = new Name(array('idnx' => $idnx));

    // update object from $_POST parameters
    $name->postUpdate();
    $command                    = $name->getLastSqlCmd();
    print ",\n\"update\": " . json_encode($command);

    // save object state to server
    $name->save();
    $command                    = $name->getLastSqlCmd();

    // include XML representation of updated record in response
    print ",\n\"record\" : ";
    $name->toJson(true);
    print ",\n\"saveName\": " . json_encode($command);
}
else
    print "\"msg\" : \"Missing mandatory parameter idnx\"\n";

// close off the JSON response file
print "}\n";
