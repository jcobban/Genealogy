<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountyMarriageSet.inc												*
 *																		*
 *  Definition of a class representing a set of records of marriages	*
 *  registered in pre-confederation Ontario.							*
 *																		*
 *  History:															*
 *		2017/10/22		created											*
 *		2018.04/26		if an instance is created for a complete		*
 *						report, ensure that all of the items within		*
 *						the report are present in order					*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2020/01/26      add method getVolumeSummary                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/CountyMarriage.inc';

/************************************************************************
 *  class CountyMarriageSet												*
 *																		*
 *  Definition of a class implementing a set of pre-confederation		*
 *  County Marriages within a genealogy database.						*
 ************************************************************************/

class CountyMarriageSet extends RecordSet
{
    /********************************************************************
     *	CountyMarriageSet::$fullReport									*
     *																	*
     *	This state variable is true if the request is for the entire	*
     *	contents of a specific report.  That is the request specified	*
     *	a search for a match on domain, volume, and report number and	*
     *	no other parameters.											*
     ********************************************************************/
    private $fullReport		= false;

    /********************************************************************
     *	function CountyMarriageSet::__construct							*
     *																	*
     *  Create an instance of a class that describes a set of			*
     *	county or district marriage registrations.						*
     *																	*
     *  Input:															*
     *	    $parms		an associative array for selecting records		*
     *					to be part of the set.							*
     *	    $order		desired order of the records in a report		*
     *																	*
     *  Returns object containing  set of county marriage records.		*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms	= null,
						 $order =
				"M_RegDomain, M_Volume, M_ReportNo, M_ItemNo, M_Role DESC")
    {
        $parms['order']		= $order;
		parent::__construct('CountyMarriages',
						    $parms);
    }		// CountyMarriageSet::__construct

    /********************************************************************
     *	function CountyMarriageSet::getWhere							*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	Returns:														*
     *		string containing WHERE clause								*
     ********************************************************************/
    protected function getWhere()
    {
		global $warn;

		// construct the WHERE clause
		$where						    = '';
		$and						    = 'WHERE ';
		$reportMask					    = 0;		// bit mask
		foreach($this->parms as $fldname => $value)
		{		// loop through all parameters
            $fieldLc	= CountyMarriage::getRealFieldName($fldname);
            $pname      = substr($fieldLc, 2);
		    switch($fieldLc)
		    {
				case 'm_regdomain':
				{		// text fields full match
				    $where			    .= "$and `$fieldLc`=:domain";
				    $this->sqlParms['domain']		= $value;
				    $and			    = ' AND ';
				    $reportMask			|= 1;
				    break;
				}		// text fields

				case 'm_date':
				case 'm_licensetype':
				{		// text fields full match
				    $where			    .= "$and `$fieldLc`=:$pname";
				    $this->sqlParms[$pname]		= $value;
				    $and			    = ' AND ';
				    $reportMask			|= 8;
				    break;
				}		// text fields

				case 'm_givennames':
				case 'm_surname':
				case 'm_age':
				case 'm_residence':
				case 'm_birthplace':
				case 'm_fathername':
				case 'm_mothername':
				case 'm_witnessname':
				case 'm_remarks':
				{		// text fields pattern match
				    $expr		    = $this->getWherePattern($fieldLc,
									    		             $value,
                                                             $pname);
				    if (strlen($expr) > 0)
				    {
						$where		.= $and . $expr;
						$and		= ' AND ';
				    }
				    $reportMask		|= 8;
				    break;
				}		// text fields

				case 'm_surnamesoundex':
				{		// text fields soundex
				    $where		    .= "$and `$fldname`=LEFT(SOUNDEX(:$pname),4)";
				    $this->sqlParms[$pname]	= $value;
				    $and		    = ' AND ';
				    $reportMask		|= 8;
				    break;
				}		// text fields

				case 'm_volume':
				{		// numeric fields
				    if ((is_int($value) || ctype_digit($value))
						&& $value >= 0)
				    {
						$where			.= "$and `$fieldLc`=:$pname";
						$this->sqlParms[$pname]	= $value;
						$and			= ' AND ';
						$reportMask		|= 2;
				    }
				    else
				        $warn	        .= "<p>CountyMarriageSet::getWhere: " .
							   "Invalid `$fldname` value '$value'.</p>\n";
				    break;
				}		// numeric fields

				case 'm_itemno':
				case 'm_idir':
				{		// numeric fields
				    if ((is_int($value) || ctype_digit($value))
						&& $value >= 0)
				    {
						$where			.= "$and `$fieldLc`=:$pname";
						$this->sqlParms[$pname]	= $value;
						$and			= ' AND ';
				    }
				    else
				        $warn	        .= "<p>CountyMarriageSet::getWhere: " .
							   "Invalid `$fldname` value '$value'.</p>\n";
				    $reportMask			|= 8;
				    break;
				}		// numeric fields

				case 'm_reportno':
				{		// numeric fields
				    $where			.= "$and `M_ReportNo`=:$pname";
				    if (is_numeric($value))
						$this->sqlParms[$pname]	= $value;
				    else
				    {
						$matches		= array();
						$rxr	= preg_match('#(\d+)(\x{00BD}|.5|1/2)#u',
								             $value,
								             $matches);
						if ($rxr)
						    $value		= intval($matches[1]) + 0.5;
						$this->sqlParms[$pname]	= $value;
				    }
				    $and			    = ' AND ';
				    $reportMask			|= 4;
				    break;
				}		// numeric fields

				default:
				{
				    $warn	.= "<p>CountyMarriageSet::getWhere: " .
							   "Unrecognized parameter `$fldname`.</p>\n";
				    break;
				}
		    }
		}		// loop through all parameters

		$this->fullReport	= $reportMask == 7;
		return $where;
    }		// CountyMarriageSet::getWhere

    /********************************************************************
     *	function CountyMarriageSet::rewind								*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Reposition to the first element of the "array".					*
     *	Since this is called just before any iteration this is the		*
     *	latest point at which the contents of the set can be loaded.	*
     *																	*
     *  Returns:														*
     *	    first instance of Record in the set							*
     ********************************************************************/
    public function rewind()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		if (is_array($this->list))
		    return reset($this->list);
		$firstRecord	= parent::rewind();
		if ($this->fullReport)
		{			// check for issues with whole report
		    $conParmsG			= $this->parms;
		    $conParmsG['m_role']	= 'G';
		    $conParmsB			= $this->parms;
		    $conParmsB['m_role']	= 'B';
		    $expectedItem		= 1;
		    $added			= 0;
		    $repaired			= array();
		    foreach($this->list as $line)
		    {
				$item		= $line->get('itemno');
				while ($item > $expectedItem)
				{
				    $conParmsG['m_itemno']	= $expectedItem;
				    $groom		= new CountyMarriage($conParmsG);
				    $repaired[]		= $groom;
				    $conParmsB['m_itemno']	= $expectedItem;
				    $bride		= new CountyMarriage($conParmsB);
				    $repaired[]		= $bride;
				    $added		+= 2;
				    $expectedItem++;
				}
				$repaired[]	= $line;
				if ($line->get('role') == 'B')
				    $expectedItem++;
		    }			// loop through matching records of report
		    if ($added > 0)
				$this->list		= $repaired;
		}			// check for issues with whole page
		return reset($this->list);
    }		// function CountyMarriageSet::rewind

    /********************************************************************
     *	function CountyMarriageSet::getVolumeSummary					*
     *																	*
     *  Get statistics for specific year of marriage registrations.		*
     *																	*
     *	Example:														*
     *	    $marriages	= new MarriageSet(array('domain'	=> 'CAON',	*
     *									        'year'		=> 1887));	*
     *	    $result		= $marriages->getStatistics();					*
     *																	*
     *	Returns:														*
     *		An array each row of which is								*
     *		an array containing the following values for each		    *
     *		county in the domain:										*
     *				volume          volume number                       *
     *				number          number of transcribed marriages		*
     *				linked          number linked to family tree		*
     ********************************************************************/
    function getVolumeSummary()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
	    $query	= 'SELECT M_Volume AS Volume, COUNT(M_ItemNo)/2 AS Number, SUM(M_IDIR>0)/2 AS Linked ' .
							'FROM CountyMarriages ' .
							"WHERE M_RegDomain=:domain " .
							'GROUP BY M_Volume ' .
							'ORDER BY M_Volume';
        $stmt	        = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);
		if ($stmt->execute($this->sqlParms))
        {		    // query succeeded
            if ($debug)
                $warn   .= "<p>ContyMarriageSet::getVolumeSummary: query='$queryText'</p>\n";
		    $result	    = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}
		else
		{
		    $msg	    .= "query '$queryText' failed: " .
                               print_r($stmt->errorInfo(), true);
            print "<p class='error'>$msg</p>\n";
		    return null;
		}		    // query failed
    }		        // function CountyMarriageSet::getVolumeSummary

}		// class CountyMarriageSet
