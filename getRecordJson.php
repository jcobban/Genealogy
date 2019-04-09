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
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once 'getRecordCommon.php';

// display the results
header("Content-Type: application/json");

if (strlen($msg) > 0)
{
	print '{"msg": "' . $msg . '"}';
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
			foreach($records as $key => $record)
			{
			    print $comma . "\"$key\":\n    ";
			    $record->toJson(true, $options);
			    $comma	= ",\n";
			}
			print "}\n";
	    }
	    else
			$record->toJson(true, $options);
	}
}

