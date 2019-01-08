<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  FieldComment.inc														*
 *																		*
 *  Definition of a class representing a comment on a field in a		*
 *  census transcription.												*
 *																		*
 *  TABLE FieldComments (												*
 *		`FC_Index` BIGINT(21) UNSIGNED NOT NULL AUTO_INCREMENT,				*
 *		`FC_Census` VARCHAR(6) NOT NULL DEFAULT 'CA1881',				*
 *		`FC_DistId` DECIMAL(4, 1) NOT NULL DEFAULT '0.0',				*
 *		`FC_SdId` VARCHAR(5) NOT NULL DEFAULT '',						*
 *		`FC_Div` VARCHAR(4) NOT NULL DEFAULT '',						*
 *		`FC_Page` INT(4) NOT NULL DEFAULT '0',								*
 *		`FC_Line` INT(4) DEFAULT NULL,										*
 *		`FC_FldName` VARCHAR(64) DEFAULT NULL,								*
 *		`FC_OldValue` VARCHAR(255) DEFAULT NULL,						*
 *		`FC_NewValue` VARCHAR(255) DEFAULT NULL,						*
 *		`FC_Userid` VARCHAR(255) DEFAULT NULL,								*
 *		`FC_Comment` VARCHAR(255) DEFAULT NULL,								*
 *		PRIMARY KEY (`FC_Index`),										*
 *		KEY `FC_Census` (`FC_Census`),										*
 *		KEY `FC_DistId` (`FC_DistId`),										*
 *		KEY `FC_SdId` (`FC_SdId`),										*
 *		KEY `FC_Div` (`FC_Div`),										*
 *		KEY `FC_Page` (`FC_Page`),										*
 *		KEY `FC_Userid` (`FC_Userid`) )										*
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8								*
 *																		*
 *  History:																*
 *		2018/01/18		created												*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class FieldComment														*
 *																		*
 *  Definition of a class recording information about a comment				*
 *  on a field in a census transcription.								*
 *																		*
 ************************************************************************/

class FieldComment extends Record
{
    /********************************************************************
     *		FieldComment::$initRow												*
     *																		*
     *		Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'fc_index'		=> 0,
				'fc_census'		=> '',
				'fc_distid'		=> 0,
				'fc_sdid'		=> '',
				'fc_div'		=> '',
				'fc_page'		=> 0,
				'fc_line'		=> 0,
				'fc_fldname'		=> '',
				'fc_oldvalue'		=> '',
				'fc_newvalue'		=> '',
				'fc_userid'		=> '',
				'fc_comment'		=> '');

    /********************************************************************
     *		FieldComment::$translate										*
     *																		*
     *		Define alternate field names										*
     ********************************************************************/
    protected static $translate	= array(
				'index'			=> 'fc_index',
				'census'		=> 'fc_census',
				'censusid'		=> 'fc_census',
				'distid'		=> 'fc_distid',
				'sdid'			=> 'fc_sdid',
				'div'			=> 'fc_div',
				'page'			=> 'fc_page',
				'line'			=> 'fc_line',
				'fldname'		=> 'fc_fldname',
				'oldvalue'		=> 'fc_oldvalue',
				'newvalue'		=> 'fc_newvalue',
				'userid'		=> 'fc_userid',
				'comment'		=> 'fc_comment');

    /********************************************************************
     *		FieldComment::$defaultOrder										*
     *																		*
     *		Default sort order												*
     ********************************************************************/
    protected static $defaultOrder	= "`fc_census`,`fc_distid`,`fc_sdid`,`fc_div`,`fc_page`,`fc_line`,`fc_fldname`";

    /********************************************************************
     *		FieldComment::__construct										*
     *																		*
     *		Construct an instance of FieldComment based upon the				*
     *		parameters.  This instance may or may not be synchronized with		*
     *		the database.														*
     *																		*
     *		Input:																*
     *		    $parms				an associative array obtained from		*
     *								an SQL query or								*
     *								search parameters						*
     *																		*
     *		Returns object containing a description of a comment				*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $connection;

		$needInsert	= false;
		$this->table	= 'FieldComments';
		$sqlParms	= array();
		if ($debug)
		    $warn	.= "<p>FieldComment::__construct(" .
						   print_r($parms, true) . ")</p>\n";

		// validate parameters
		if (is_array($parms))
		{			// array
		    $parms		= $parms;
		    if (count($parms) < count(self::$initRow))
		    {			// associative array of parameters
				$where		= '';
				foreach($parms as $key => $value)
				{		// loop through parameters
				    $fieldLc		= strtolower($key);
				    if (array_key_exists($fieldLc, self::$translate))
						$fieldLc	= self::$translate[$fieldLc];
				    switch(strtolower($key))
				    {		// act on key fields
						case 'fc_index':
						{
						    $where	= ' WHERE FC_Index=:fc_index';
						    $sqlParms[$fieldLc]	= $value;
						    break;
						}
				    }		// act on key fields
				}		// loop through parameters

				$dbrow		= self::$initRow;
				$needInsert	= true;
				if (strlen($where) > 0)
				{	// read the existing record from the database
				    $query	= "SELECT * FROM FieldComments " . $where;

				    // query the database
				    $stmt	= $connection->prepare($query);
				    $queryText	= debugPrepQuery($query, $sqlParms);
				    if ($stmt->execute($sqlParms))
				    {			// successful query
						if ($debug)
						    $warn		.= "<p>$queryText</p>\n";

						$result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (is_array($result) && count($result) > 0)
						{		// existing record
						    $dbrow		= $result[0];
						    $needInsert		= false;
						}		// existing record
				    }			// successful query
				    else
				    {			// error performing query
						error_log("FieldComment.inc " . __LINE__ .
							    " query='$queryText'</p>\n");
						throw new Exception("FieldComment::__construct: " .
									"'$queryText' " .
								    print_r($stmt->errorInfo(),true));
				    }			// error performing query
				}	// read the existing record from the database
		    }			// associative array of parameters
		    else
		    {			// build from existing record
				$dbrow		= $parms;
		    }			// build from existing record
		}			// array
		else
		{		// unsupported data type
		    $errors	.= "first parameter must be an array: is " .
						   print_r($parms, true);
		}		// unsupported data type

		parent::__construct($dbrow,
						    'FieldComments');
		$this->needInsert	= $needInsert;

		// update any fields from the parameters
		foreach($parms as $key => $value)
		{		// loop through parameters
		    $fieldLc		= strtolower($key);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	= self::$translate[$fieldLc];
		    switch(strtolower($key))
		    {		// act on key fields
				case 'fc_index':
				case 'fc_census':
				case 'fc_distid':
				case 'fc_sdid':
				case 'fc_div':
				case 'fc_page':
				case 'fc_line':
				case 'fc_fldname':
				case 'fc_oldvalue':
				case 'fc_newvalue':
				case 'fc_userid':
				case 'fc_comment':
				{
				    parent::set($fieldLc, $value);
				    break;
				}
		    }		// act on key fields
		}		// loop through parameters

		// diagnostic output if debug is set
		$this->dump('FieldComment Record constructed:');
    }		// FieldComment::__construct

}		// class FieldComment
