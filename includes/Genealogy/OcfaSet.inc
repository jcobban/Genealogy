<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  OcfaSet.inc														    *
 *																		*
 *  Definition of a class representing a set of Ontario Cemetery        *
 *  Finding Aid (OCFA) index entries.	                                *
 *																		*
 *  History:															*
 *		2019/12/16		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Ocfa.inc';

/************************************************************************
 *  class OcfaSet														*
 *																		*
 *  Definition of a class representing a set of OCFA) index entries.	*
 ************************************************************************/

class OcfaSet extends RecordSet
{
    /********************************************************************
     *	function OcfaSet::__construct									*
     *																	*
     *	Create an instance of the class OcfaSet		 				    *
     *																	*
     *	Parameters:														*
     *	    $parameters			associative array of fieldname, value	*
     *							pairs to define the subset of the		*
     *							records in the SQL table that are		*
     *							represented by this instance.  The		*
     *							parameters 'offset' and 'limit'			*
     *							are reserved to set the OFFSET and		*
     *							LIMIT clauses.  Use '`offset`', and		*
     *							'`limit`' to reference fields with		*
     *							those names. This may be null.			*
     *																	*
     *	Throws:															*
     *	    An exception for invalid parameters.						*
     ********************************************************************/
    function __construct($parms)
    {
        $this->usenames         = true;

		parent::__construct('Ocfa', $parms);
    }		// OcfaSet::__construct

    /********************************************************************
     *	function OcfaSet::getWhere										*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	Input:															*
     *	$this->parms	associative array of field/value combinations	*
     *					the field names are interpreted according to	*
     *					the standard translate table.  Note that		*
     *					because the parameter is an associative array	*
     *					that only one value can be specified for each	*
     *					field name, however in some cases that value	*
     *					can be an array, as discussed in the following.	*
     *					The value specified for each field is			*
     *					interpreted as follows:							*
	 *	    			'Surname'           							*
	 *	    			'GivenName'         							*
	 *	    			'Cemetery'          							*
	 *	    			'County'            							*
	 *	    			'Township'          							*
	 *	    			'Reference'         							*
	 *	    			'Soundex'          								*
     *					'orderby':    	override default sort order		*
     *					'offset':		start offset in result set		*
     *					 'limit':		max number of rows				*
     *	$this->sqlParms     construct array of parms for PDO execute	*
     *	$this->limit		return value of limit parameter				*
     *	$this->offset		return value of offset parameter			*
     *																	*
     *	Returns:														*
     *	    string containing WHERE clause								*
     *	    A zero length string is returned if the input array is		*
     *      empty, otherwise the string starts with the word WHERE		*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied parms are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     ********************************************************************/
    protected function getWhere()
    {
		global	$debug;
		global	$warn;

		if ($debug)
		    $warn	.= '<p>OcfaSet::getWhere: ' . 
						   print_r($this->parms, true) . "</p>\n";
		$where			            = '';
		$and			            = 'WHERE ';
		if (!is_array($this->sqlParms))
		    $this->sqlParms		    = array();
		$tparms			            = array();
		foreach($this->parms as $fldname => $value)
		{			// translate field names and ignore empty
            $fieldLc		        = Ocfa::getRealFieldName($fldname);
		    if (is_array($value) || strlen($value) > 0)
				$tparms[$fieldLc]	= $value;
		}			// translate field names and ignore empty

		// create WHERE clause
		$surname		            = null;
        $soundex		            = false;

		foreach($tparms as $fieldLc => $value)
		{
		    switch($fieldLc)
		    {			// act on specific fields
				case 'limit':
				{
				    if (is_int($value) || ctype_digit($value))
						$this->limit		= intval($value);
				    break;
				}

				case 'offset':
				{
				    if (is_int($value) || ctype_digit($value))
						$this->offset		= intval($value);
				    break;
				}

				case 'givenname':
				case 'cemetery':
				case 'reference':
				{		// text fields for pattern match
				    $expression	= $this->getWherePattern($fieldLc,
                                                         $value,
                                                         true);
				    if (strlen($expression) > 0)
				    {		// expression generated
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }		// expression generated
				    break;
				}		// text fields

				case 'surname':
				{		// surname, comparison postponed
				    $surname		= $value;
				    break;
				}		// surname

				case 'surnamesoundex':
				{		// soundex comparison flag
				    if (is_bool($value))
						$soundex	= $value;
				    else
				    if (strlen($value) > 0 && strtoupper($value) != 'N')
						$soundex	= true;
				    break;
				}		// soundex comparison flag

				case 'county':
				case 'township':
				{		// other fields exact match
				    $where		.= "$and `$fieldLc`=:$fieldLc";
				    $this->sqlParms[$fieldLc]		= $value;
				    $and		= ' AND ';
				    break;
				}		// other fields
		    }			// act on specific fields
		}			    // loop through all parameters

		// special checks for surname
		if (!is_null($surname))
		{			    // surname comparison requested
		    if (preg_match("/[.+*?^$]/", $surname))
		    {		    // match pattern
				$where	.= $and . 'Surname REGEXP :surname';
		        $this->sqlParms['surname']	= $tparms['surname'];
		    }		    // match pattern
		    else
		    if ($soundex)
		    {		    // match soundex or pre-defined surname pattern
				$where	.= $and .  $this->getSurnameChk('Ocfa',
                                                        $value);
		    }		    // match soundex or pre-defined surname pattern
		    else
		    {		    // match exact
				$where	.= $and . 'Surname=:surname';
		        $this->sqlParms['surname']	= $tparms['surname'];
		    }		    // match exact
        }			    // surname comparison requested

		return $where;
    }		// OcfaSet::getWhere

    /********************************************************************
     *	function Ocfa::getStatistics									*
     *																	*
     *  Get statistics for specific set of ocfa registrations.			*
     *																	*
     *	Example:														*
     *	    $ocfas		= new OcfaSet(array('offset'	=> 0));		    *
     *	    $result		= $ocfas->getStatistics();						*
     *																	*
     *	Returns:														*
     *		An array each row of which is								*
     *		an array containing the following values for each		    *
     *		county in the domain:										*
     *				county		    county code						    *
     *				count	        number of transcribed records		*
     ********************************************************************/
    function getStatistics()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
		$query	= "SELECT County, `Count` FROM OcfaStats";
		$stmt	        = $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{		            // success
            if ($debug)
                $warn   .= "<p>OcfaSet::getStatistics: " . __LINE__ .
                            " '$query'</p>\n";
		    $result	    = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}		            // success
		else
		{		            // query failed
		    $msg	    .= "query '$query' failed: " .
					    	   print_r($stmt->errorInfo(), true);
		    return null;
		}		            // query failed
    }		// Ocfa::getStatistics

    /********************************************************************
     *	function Ocfa::getCountyStatistics								*
     *																	*
     *  Get statistics for specific year of ocfa registrations.		    *
     *																	*
     *	Example:														*
     *	    $ocfas		= new OcfaSet(array('county'	=> 'Msx'));	    *
     *	    $result		= $ocfas->getCountyStatistics();				*
     *																	*
     *	Returns:														*
     *		An array each row of which is							    *
     *		an array containing the following values for each		    *
     *		town, city, or township in the county:					    *
     *				county		    county code						    *
     *				township	    township name					    *
     *				count	        number of transcribed records	    *
     *				linkcount		number linked to the tree		    *
     *				low				lowest registration number		    *
     *				high			highest registration number		    *
     *				currhigh		highest registration number         *
     *				                excluding delayed registrations     *
     ********************************************************************/
    function getCountyStatistics()
    {
		global	$debug;
		global	$warn;
		global	$msg;
        global	$connection;

		// execute the query
        $query	        = "SELECT Township, SUM(Surname != '') AS Count " .
                                "FROM Ocfa " .
                                $this->where . ' ' .
                               	"GROUP BY Township ORDER BY Township";
		$stmt	        = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

		if ($stmt->execute($this->sqlParms))
        {
            if ($debug)
                $warn   .= "<p>OcfaSet::getCountyStatistics: " . __LINE__ .
                            " '$queryText'</p>\n";
            $result	    = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}
		else
		{
		    $msg	    .= "query '$queryText' failed: " .
                               print_r($stmt->errorInfo(), true);
		    return null;
		}		    // query failed
    }		    // function Ocfa::getCountyStatistics

}		// class OcfaSet 
