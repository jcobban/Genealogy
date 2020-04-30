<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getRecordJson.php													*
 *																		*
 *  Get the information on an instance of Record as a JSON response		*
 *  file so it can be retrieved by Javascript.							*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		table			name of the table: 								*
 *		domain			registration domain: country code,				*
 *						province/state code								*
 *						default 'CAON'									*
 *		year			registration year								*
 *		number			registration number within the year				*
 *		county			county abbreviation								*
 *		townshipcode	abbreviation									*
 *		townshipname	full name										*
 *		volume			volume number for county marriage reports		*
 *		reportno		report number within volume of county marriage	*
 *						reports											*
 *		itemno			item within a county marriage report			*
 *		iduser			key of Users table								*
 *		idblog			key of Blogs table								*
 *		offset			starting offset in results						*
 *		limit			max number of records to return					*
 *		other			field name within database, e.g. surname		*
 *																		*
 *  History:															*
 *		2017/01/13		created from getRecordXml.php					*
 *		2017/01/23		support text keys								*
 *		2019/01/10      move to namespace Genealogy                     *
 *		2019/06/23      decode HTML entities in string values           *
 *		2019/11/20      include recordset info with error message       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once 'getRecordCommon.php';

// display the results
header("Content-Type: application/json");

if (strlen($msg) > 0)
{
	print '{"msg": "' . $msg . '"';
    if ($record instanceof RecordSet)
    {
        $info           = $record->getInformation();
        print ",\n\"info\": {";
        $comma          = '';
        foreach ($info as $field => $value)
        {
            if (is_numeric($value))
                print "$comma\t\t\"$field\":\t$value";
            else 
                print "$comma\t\t\"$field\":\t". json_encode($value);
            $comma      = ",\n";
        }
        print "\n\t}";
    }
    print "\n}";
}
else
{
	if (isset($record))
    {
	    if (is_array($record) || $record instanceof RecordSet)
	    {
            print "{\n";
			$records	= $record;
			$comma		= '';
            if ($record instanceof RecordSet)
            {
                $info       = $record->getInformation();
                print "\n\"info\": {";
                foreach ($info as $field => $value)
                {
                    if (is_numeric($value))
                        print "$comma\t\t\"$field\":\t$value";
                    else 
                        print "$comma\t\t\"$field\":\t". json_encode($value);
                    $comma      = ",\n";
                }
                print "\n\t}";
            }
			foreach($records as $key => $record)
			{
			    print $comma . "\"$key\":\n    ";
			    $record->toJson(true, $options);
			    $comma	= ",\n";
			}
			print "\n}\n";
	    }
        else
        if ($record instanceof Record || $record instanceof FamilyTree)
            $record->toJson(array('print'   => true,
                                  'lang'    => $lang), 
                            $options);
        else
            print "{\"record\" : \"$record\"}\n";
	}
}

