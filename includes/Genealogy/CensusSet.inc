<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CensusSet.inc														*
 *																		*
 *  Definition of a class representing a a set of Censuses.				*
 *																		*
 *  History:															*
 *		2017/10/16		created											*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc' ;
require_once __NAMESPACE__ . '/Census.inc' ;

/************************************************************************
 *  class CensusSet														*
 *																		*
 *  Definition of a class representing a set of Censuses.				*
 *																		*
 ************************************************************************/

class CensusSet extends RecordSet
{
    /********************************************************************
     * function CensusSet::__construct									*
     *																	*
     *  Construct an instance of CensusSet based upon the parameters.	*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameters				*
     *					identifying the specific row.					*
     *					This may be a row retrieved from the database.	*
     *																	*
     *  Returns object containing a set of Censuses						*
     *																	*
     *	Throws Exception if unable to complete creating record			*
     ********************************************************************/
    function __construct($parms,
						 $order		= '`Name`')
    {
        foreach($parms as $name=> $value)
            if (strtolower($name) == 'order')
                $order      = $value;
		$parms['order']		= $order;
		parent::__construct('Censuses',
						    $parms);
    }		// Census::__construct

    /********************************************************************
     * function CensusSet::getWhere										*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	    $this->parms  associative array of field/value combinations	*
     *					  censusId may compared to an array of values	*
     *					  offset is the starting offset in the result	*
     *					  limit is the max number of records to return	*
     *				Special Field Names:								*
     *				  year			match the census year			    *
     *				  countrycode	match a country					    *
     *				  cc        	match a country					    *
     *				  state			match an included state			    *
     *				  province		match an included province		    *
     *	    $this->sqlParms		sets substitution values to PDO exec	*
     *	    $this->offset		sets starting offset if supplied		*
     *		$this->limit		sets maximum number of matches			*
     *																	*
     *  Returns:														*
     *		string containing WHERE clause								*
     *		A zero length string is returned if the input array is		*
     *      empty, otherwise the string starts with the word WHERE		*
     *																	*
     *  Side Effects:													*
     *		Errors are appended to the global $warn						*
     ********************************************************************/
    function getWhere()
    {
		global	$debug;
		global	$warn;

		// construct the WHERE clause 
		$where		= '';
		$and		= 'WHERE ';
		foreach($this->parms as $fldname => $value)
		{		// loop through all parameters
		    $fieldLc		= Census::getRealFieldName($fldname);
		    $matches		= array();
		    if (preg_match('/^(.*) AS (.*)$/i', $fieldLc, $matches))
		    {
				$fieldExpr	= $matches[1];
				$fieldLc	= strtolower($matches[2]);
		    }
		    switch($fieldLc)
		    {
				case 'censusid':
				{		// census identifier
				    if (is_string($value))
				    {
						$where			.= "$and`CensusId`=?";
						$this->sqlParms[]	= $value;
				    }
				    else
				    if (is_array($value) && count($value) > 0)
				    {		// list of censuses
						$where		.= $and;
						$or		= '';
						foreach($value as $cid)
						{
						    $where	.= "$or`CensusId`=?";
						    $this->sqlParms[]	= $cid;
						    $or		= ' OR ';
						}
						if (count($value) > 1)
						    $where	.= "($where)";
						$and		= ' AND ';
				    }		// list of censuses
				    else
						$warn	.= "<p>Invalid `$fldname` value '" .
								print_r($value,true) . "'.</p>\n";
				    break;
				}		// census identifier

				case 'countrycode':
				case 'cc':
				{		// country code
				    $where		.= "$and (SUBSTRING(`CensusId`,1,2)=? OR `PartOf`=?)";
				    $this->sqlParms[]	= $value;
				    $this->sqlParms[]	= $value;
				    $and		= ' AND ';
				    break;
				}		// country code

				case 'year':
				{		// enumeration year
				    $where		.= "$and SUBSTRING(`CensusId`,-4)=?";
				    $this->sqlParms[]	= $value;
				    $and		= ' AND ';
				    break;
				}		// enumeration year

				case 'name':
				{		// name search by pattern
				    $expression		= $this->getWherePattern('Name',
											 $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }
				    break;
				}		// name search by pattern

				case 'linesperpage':
				{
				    $expression		= $this->getWhereInt('LinesPerPage',
										     $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }
				    break;
				}

				case 'province':
				case 'state':
				{		// state search by pattern
				    if (strlen($value) == 2)
				    {		// 2 character abbreviation as in US&CA
						// $value must be matched on an even boundary
						// SQL perversely numbers characters starting at 1!
						$where	.= "$and (LOCATE(?,`Provinces`) & 1) = 1";
						$this->sqlParms[]	= $value;
						$and		= ' AND ';
				    }		// pattern match
				    else
						$warn	.= "<p>CensusSet::getWhere: " .
							   "Invalid `$fldname` value '$value'.</p>\n";
				    break;
				}		// state search by pattern

				case 'collective':
				case 'partof':
				case 'provinces':
				{		// match whole value
				    if (is_null($value))
				    {
						$where			.= "$and `$fieldLc` IS NULL";
						$and			= ' AND ';
				    }
				    else
				    if (is_string($value) || is_int($value))
				    {
						$where			.= "$and `$fieldLc`=?";
						$this->sqlParms[]	= $value;
						$and			= ' AND ';
				    }
				    else
						$warn	.= "<p>CensusSet::getWhere: " .
				 "Unsupported value type for parameter `$fldname`.</p>\n";
				    break;
				}		// match whole value

				case 'count':
				{		// ignore if set by previous invocation
				    break;
				}		// ignore if set by previous invocation
		    }
		}		// loop through all parameters

		return $where;
    }			// function CensusSet::getWhere
}		// class CensusSet
