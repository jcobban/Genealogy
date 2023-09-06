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
 *      2021/02/15      fix syntax of JSON                              *
 *      2022/09/21      update citations referenced in parms            *
 *                      also display parms in response                  *
 *      2022/09/24      accept parameter source9999 value as IDSR       *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the JSON header
print "{\n";

$comma                          = '';
$idnx                           = null;
print "    \"parms\" : " . json_encode($_POST);
$comma                          = ',';
$citations                      = array();
foreach($_POST as $key => $value)
{
    $lckey                      = strtolower($key);
    if (preg_match('/^([a-z]+)(\d*)$/', $lckey, $matches))
    {
        $idsx                   = $matches[2];
        $lckey                  = $matches[1];
    }
    switch($lckey)
    {
        case 'idnx':
            if (ctype_digit($value))
                $idnx           = intval($value);
            break;

        case 'source':
            $citation           = array("sourcename" => $value);
            break;

        case 'page':
            $citation['page']   = $value;
            $citations[$idsx]   = $citation;
            break;
    }                       // switch on key
}

print "$comma    \"citations\" : " . json_encode($citations);

// get the updated values of the fields in the record
if (is_int($idnx))
{
    // locate existing name record, or create a new empty record
    $name       = new Name(array('idnx' => $idnx));

    // update object from $_POST parameters
    $name->postUpdate();
    $command                    = $name->getLastSqlCmd();
    print "$comma\n\"update\": " . json_encode($command);

    // save object state to server
    $name->save();
    $command                    = $name->getLastSqlCmd();

    // include XML representation of updated record in response
    print ",\n\"record\" : ";
    $name->toJson(true);
    print ",\n\"saveName\": " . json_encode($command);

    foreach($citations as $idsx => $citdata)
    {
        $srcname                = $citdata['sourcename'];
        $page                   = $citdata['page'];
        $citation               = new Citation(array('idsx' => $idsx));
        $idsr                   = null;
        if ($citation->isExisting())
            $idsr               = $citation['idsr'];
        if (ctype_digit($srcname))
            $idsr               = intval($srcname);

        if (is_null($idsr))
        {   
            $source             = new Source(array('name' => $srcname));
            if ($source->isExisting())
                $idsr           = $source->getIdsr();
        }
        else
        {
            $source             = new Source(array('idsr' => $idsr));
        }
        if ($source->isExisting())
        {
            $citation['idsr']   = $idsr;
            $citation['srcdetail']   = $page;
            $count              = $citation->save();
            if ($count)
                print ",\n\"savecit$idsx\": " .
                        json_encode($citation->getLastSqlCmd());
        }
        else
            print ",\"msg\" : \"Invalid source name '$srcname'\"\n";
    }
}
else
    print "\"msg\" : \"Missing mandatory parameter idnx\"\n";

// close off the JSON response file
print "}\n";
