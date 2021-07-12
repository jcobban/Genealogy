<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getRecordXml.php                                                    *
 *                                                                      *
 *  Get the information on an instance of Record as an XML response     *
 *  file so it can be retrieved by Javascript using AJAX.  The          *
 *  contents of the XML response is a root tag with the name of the     *
 *  table containing a tag for each matching record, with that          *
 *  containing a tag for each field in the record.                      *
 *                                                                      *
 *  Parameters (passed by method='GET'):                                *
 *      table           name of the table:                              *
 *      surname         searching Persons, Names, Surnames              *
 *      domain          registration domain: country code,              *
 *                      province/state code                             *
 *                      default 'CAON'                                  *
 *      year            registration year                               *
 *      number          registration number within the year             *
 *      county          county abbreviation                             *
 *      townshipcode    abbreviation                                    *
 *      townshipname    full name                                       *
 *      volume          volume number for county marriage reports       *
 *      reportno        report number within volume of county marriage  *
 *                      reports                                         *
 *      itemno          item within a county marriage report            *
 *      iduser          key of Users table                              *
 *      idblog          key of Blogs table                              *
 *      offset          starting offset in results                      *
 *      limit           max number of records to return                 *
 *      other           field name within database, e.g. surname        *
 *                                                                      *
 *  History:                                                            *
 *      2017/01/15      initialization moved to getRecordCommon.php     *
 *      2017/10/15      support class RecordSet                         *
 *      2019/01/10      move to namespace Genealogy                     *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once 'getRecordCommon.php';

// display the results
header("Content-Type: text/xml");
print("<?xml version='1.0' encoding='UTF-8'?".">\n");

if (strlen($msg) > 0)
{
    print "<msg>\n";
    print "    $msg\n";
    showTrace();
    print "</msg>\n";
}
else
{                       // no errors
    try {
        if (isset($record))
        {
            if (is_array($record))
            {
                $records    = $record;
                print "<list count=\"" . count($records) . "\">\n";
                showTrace();
                foreach($records as $key => $record)
                {
                    $record->toXml($top, true, $options);
                }
                print "</list>\n";
            }
            else
            if ($record instanceof RecordSet)
            {
                $records    = $record;
                print "<list count=\"" . $records->count() . "\">\n";
                $info       = $records->getInformation();
                print "\n\t<info>\n";
                foreach ($info as $field => $value)
                {
                    if (is_string($value))
                        $value      = htmlspecialchara(trim($value),ENT_XML1);
                    print "\t\t<$field>";
                    if (is_array($value))
                    {
                        foreach($value as $key => $val)
                            if (is_numeric($key))
                                print "\t\t\t<val>$val</val>\n";
                            else
                                print "\t\t\t<$key>$val</$key>\n";
                    }
                    else
                        print $value;
                    print "</$field>\n";
                }
                print "\t</info>\n";
                showTrace();
                foreach($records as $key => $record)
                {
                    $record->toXml($top, true, $options);
                }
                print "</list>\n";
            }
            else
            {
                $record->toXml($top, true, $options);
            }
        }
        else
            print "<fuck></fuck>\n";
    } catch(Exception $e) {
        print "<exception>" . $e->getMessage() . "</exception>\n";
    }
}                       // no errors
