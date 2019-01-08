<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  BirthSet.inc														*
 *																		*
 *  Definition of a class representing a set of birth registrations.	*
 *																		*
 *  History:															*
 *		2017/10/16		created											*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class BirthSet														*
 *																		*
 *  Definition of a class representing a set of birth registrations		*
 *  or birth certificates.												*
 *																		*
 ************************************************************************/

class BirthSet extends RecordSet
{

    /********************************************************************
     *	function BirthSet::__construct									*
     *																	*
     *	Create an instance of the class BirthSet		 				*
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
     *	    $order				text to place in ORDER clause			*
     *							default is defined for table			*
     *																	*
     *	Throws:															*
     *	    An exception for invalid parameters.						*
     ********************************************************************/
    function __construct($parms	= array(),
						 $order	= 'B_RegDomain, B_RegYear, B_RegNum')
    {
		$parms['order']		= $order;
		parent::__construct('Births', $parms);
    }		// BirthSet::__construct

    /********************************************************************
     *	function BirthSet::getWhere										*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	$this->parms	associative array of field/value combinations	*
     *					the field names are interpreted according to	*
     *					the standard translate table.  Note that		*
     *					because the parameter is an associative array	*
     *					that only one value can be specified for each	*
     *					field name, however in some cases that value	*
     *					can be an array, as discussed in the following.	*
     *					The value specified for each field is			*
     *					interpreted as follows:							*
     *					 'b_birthplace':								*
     *					 'b_fatheroccupation':							*
     *					 'b_fatheroccplace':							*
     *					 'b_motheroccupation':							*
     *					 'b_motheroccplace':							*
     *					 'b_marriageplace':								*
     *					 'b_informantres':								*
     *					 'b_remarks':									*
     *					 'b_givennames':								*
     *					 'b_informant':									*
     *					 'b_fathername':								*
     *					 'b_fatherocc':									*
     *					 'b_mothername':								*
     *					 'b_motherocc':									*
     *					 'b_husbandname':								*
     *					 'b_formerhusband':								*
     *					 'b_accoucheur':								*
     *					 'b_registrar':									*
     *							The value is a regular expression.		*
     *							For example if a simple string is		*
     *							provided, then it will match anywhere	*
     *							in the value. To match the exact value	*
     *							pass '^fullvalue$'.						*
     *					 'b_regyear':									*
     *					 'b_regnum':									*
     *					 'b_msvol':										*
     *					 'b_originalpage':								*
     *					 'b_originalitem':								*
     *					 'b_idir':										*
     *							The value is either an integer or an	*
     *							array.  If the array contains only		*
     *							numbers then records where the field	*
     *							matches any of the values are selected.	*
     *							Otherwise if each value is prefixed		*
     *							by a comparison operator (<, <=, >=, >,	*
     *							!=, or <>) then the comparisons must	*
     *							all be true to match.  For example:		*
     *							array('>=100','<=200') includes all		*
     *							records with a value >=100 and <=200.	*
     *							If there are only two values, the first	*
     *							value is an integer, and the second		*
     *							value is ':' followed by an integer		*
     *							for example array(100,':200')			*
     *							this is interpreted the same as the		*
     *							previous example. 						*
     *					 'b_birthdate':									*
     *					 'b_marriagedate':								*
     *							If the parameter 'range' is specified	*
     *							then records in which the value			*
     *							of the field is within 'range' years	*
     *							of the specified date are included		*
     *							If range is not specified then this is	*
     *							an exact match.							*
     *					 'b_surname':									*
     *							If 'b_surnamesoundex' is true or 'Y'	*
     *							then the match is for surnames with		*
     *							the same SOUNDEX code as this surname.	*
     *							Otherwise this is an exact match.		*
     *					 'b_regdomain':									*
     *					 'b_regcounty':									*
     *					 'b_regtownship':								*
     *					 'b_sex':										*
     *					 'b_calcbirth':									*
     *					 'b_parentsmarried':							*
     *					 'b_informantrel':								*
     *					 'b_regdate':									*
     *					 'b_image':										*
     *					 'b_originalvolume':							*
     *					 'b_changedby':									*
     *							exact value match						*
     *					 'b_surnamesoundex':							*
     *					 'range':										*
     *							The value is either true or 'Y' to		*
     *							alter the behavior of other comparisons	*
     *					 'offset':										*
     *					 'limit':										*
     *							These set the returned values of		*
     *							the parameters $limit and $offset		*
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
		    $warn	.= '<p>BirthSet::getWhere: ' . 
						   print_r($this->parms, true) . "</p>\n";
		$where			= '';
		$and			= 'WHERE ';
		if (!is_array($this->sqlParms))
		    $this->sqlParms		= array();
		$tparms			= array();
		foreach($this->parms as $fldname => $value)
		{			// translate field names and ignore empty
		    $fieldLc		= Birth::getRealFieldName($fldname);
		    if (is_array($value) || strlen($value) > 0)
				$tparms[$fieldLc]	= $value;
		}			// translate field names and ignore empty

		// create WHERE clause
		$surname		= null;
		$soundex		= false;

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

				case 'b_birthplace':
				case 'b_fatheroccupation':
				case 'b_fatheroccplace':
				case 'b_motheroccupation':
				case 'b_motheroccplace':
				case 'b_marriageplace':
				case 'b_informantres':
				case 'b_remarks':
				case 'b_givennames':
				case 'b_informant':
				case 'b_fathername':
				case 'b_fatherocc':
				case 'b_mothername':
				case 'b_motherocc':
				case 'b_husbandname':
				case 'b_formerhusband':
				case 'b_accoucheur':
				case 'b_registrar':
				{		// text fields for pattern match
				    $expression	= $this->getWherePattern($fieldLc,
											             $value,
											             $this->sqlParms);
				    if (strlen($expression) > 0)
				    {		// expression generated
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }		// expression generated
				    break;
				}		// text fields

				case 'b_regyear':
				case 'b_regnum':
				case 'b_msvol':
				case 'b_originalpage':
				case 'b_originalitem':
				case 'b_idir':
				{		// positive integer fields
				    $expression	= $this->getWhereInt($fieldLc,
										             $value,
										             $this->sqlParms);
				    if (strlen($expression) > 0)
				    {		// expression generated
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }		// expression generated
				    break;
				}		// positive integer fields

				case 'b_birthdate':
				{		// birth date fields
				    $rangeSet	= array_key_exists('range', $tparms);
				    if ($rangeSet)
						$range	= intval($tparms['range']);
				    else
						$range	= 1;

				    if (strlen($value) > 0 &&
						($rangeSet ||
						 preg_match('/^\s*\d+\s*$/', $value)))
				    {
						$date		= new LegacyDate(' ' . $value);
						$y		= $date->getYear();
						$m		= $date->getMonth();
						$d		= $date->getDay();
						$where		.= $and .
						"ABS(DATEDIFF(B_CalcBirth, '$y-$m-$d'))<(365 * ?)";
						$this->sqlParms[]	= $range;
				    }
				    else
				    {		// simple comparison
						$where			.= "$and `$fieldLc`=?";
						$this->sqlParms[]	= $value;
				    }		// simple comparison
				    $and		= ' AND ';
				    break;
				}		// birth date field

				case 'b_marriagedate':
				{		// marriage date field
				    if (strlen($value) > 0 &&
						array_key_exists('range', $tparms) )
				    {
						$date		= new LegacyDate(' ' . $value);
						$y		= $date->getYear();
						$m		= $date->getMonth();
						$d		= $date->getDay();
						$where		.= $and .
"ABS(DATEDIFF(STR_TO_DATE(B_MarriageDate,'%d %M %Y'), '$y-$m-$d'))<(365 * ?)";
						$this->sqlParms[]	= $tparms['range'];
				    }
				    else
				    {		// simple comparison
						$where		.= "$and `$fieldLc`=?";
						$this->sqlParms[]	= $value;
				    }		// simple comparison
				    $and		= ' AND ';
				    break;
				}		// date fields

				case 'b_surname':
				{		// surname, comparison postponed
				    $surname		= $value;
				    break;
				}		// surname

				case 'b_surnamesoundex':
				{		// soundex comparison flag
				    if (is_bool($value))
						$soundex	= $value;
				    else
				    if (strlen($value) > 0 && strtoupper($value) != 'N')
						$soundex	= true;
				    break;
				}		// soundex comparison flag

				case 'b_regdomain':
				case 'b_regcounty':
				case 'b_regtownship':
				case 'b_sex':
				case 'b_calcbirth':
				case 'b_parentsmarried':
				case 'b_informantrel':
				case 'b_regdate':
				case 'b_image':
				case 'b_originalvolume':
				case 'b_changedby':
				{		// other fields exact match
				    $where		.= "$and `$fieldLc`=?";
				    $this->sqlParms[]		= $value;
				    $and		= ' AND ';
				    break;
				}		// other fields
		    }			// act on specific fields
		}			// loop through all parameters

		// special checks for surname
		if (!is_null($surname))
		{			// surname comparison requested
		    if (preg_match("/[.+*?^$]/", $surname))
		    {		// match pattern
				$where	.= $and . 'B_Surname REGEXP ?';
		    }		// match pattern
		    else
		    if ($soundex)
		    {		// match soundex
				$where	.= $and . 'B_SurnameSoundex = LEFT(SOUNDEX(?),4)';
		    }		// match soundex
		    else
		    {		// match exact
				$where	.= $and . 'B_Surname=?';
		    }		// match exact

		    $this->sqlParms[]	= $tparms['b_surname'];
		}			// surname comparison requested
		return $where;
    }		// BirthSet::getWhere

    /********************************************************************
     *	function BirthSet::getSummary									*
     *																	*
     *  Get year by year statistics for birth registrations.			*
     *	This returns an array with one record per year.					*
     *																	*
     *	Example:														*
     *	    $births		= new BirthSet(array('domain' => 'CAON'));		*
     *	    $result		= $births->getSummary();						*
     *																	*
     *	Returns:														*
     *		array each row of which is an array containing the		    *
     *		following values:										    *
     *		    0		year of registrations							*
     *		    1		number of birth registrations transcribed		*
     *		    2		number of birth registrations linked to tree	*
     ********************************************************************/
    function getSummary()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
		$query	= 'SELECT B_RegYear, SUM(B_Surname != ""), SUM(B_IDIR > 0) ' .
						'FROM Births ' . $this->where .
						    'GROUP BY B_RegYear ORDER BY B_RegYear';
		$sqlParmsText	= print_r($this->sqlParms, true);
		$stmt		= $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{			// query successful
		    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
		    if ($debug)
		    {			// debug output
				$warn	.= "<p>Birth.inc: " . __LINE__ . "query='$query'</p>\n".
						   "<p>sqlParms=$sqlParmsText</p>\n";
		    }			// debug output
		}			// query successful
		else
		{			// query failed
		    $msg	.= "query '$query' failed, sqlParms=$sqlParmsText: " .
						   print_r($stmt->errorInfo(), true);
		    $result	= null;
		}			// query failed
		return $result;
    }		// Birth::getSummary

    /********************************************************************
     *	function Birth::getStatistics									*
     *																	*
     *  Get statistics for specific set of birth registrations.			*
     *																	*
     *	Example:														*
     *	    $births		= new BirthSet(array('domain'	=> 'CAON',		*
     *									     'year'		=> 1887));		*
     *	    $result		= $births->getStatistics();						*
     *																	*
     *	Returns:														*
     *		An array each row of which is								*
     *		an array containing the following values for each		    *
     *		county in the domain:										*
     *				b_regcounty		county code						    *
     *				surnamecount	number of transcribed records		*
     *				linkcount		number linked to the tree		    *
     *				low				lowest registration number		    *
     *				high			highest registration number		    *
     ********************************************************************/
    function getStatistics()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
		$query	= 'SELECT B_RegCounty, ' .
								'SUM(B_Surname != "") AS SurnameCount,  ' .
								'SUM(B_Idir != 0) AS LinkCount, ' .
								'MIN(B_RegNum) as low, ' .
								'MAX(B_RegNum) as high  ' .
						'FROM Births ' .
						$this->where .
						'GROUP BY B_RegCounty ' .
						'ORDER BY B_RegCounty';
		$stmt	= $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{
		    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}
		else
		{
		    $msg	.= "query '" . $query . "' failed: " .
						   print_r($this->sqlParms, true) . ': ' .
						   print_r($stmt->errorInfo(), true);
		    return null;
		}		// query failed
    }		// Birth::getStatistics

    /********************************************************************
     *	function Birth::getCountyStatistics								*
     *																	*
     *  Get statistics for specific year of birth registrations.		*
     *																	*
     *	Example:														*
     *	    $births		= new BirthSet(array('domain'		=> 'CAON',	*
     *									     'year'		=> 1887,		*
     *									     'county'		=> 'Msx'));	*
     *	    $result		= $births->getCountyStatistics();				*
     *																	*
     *	Returns:														*
     *		An array each row of which is							    *
     *		an array containing the following values for each		    *
     *		town, city, or township in the county:					    *
     *				b_regcounty		county code						    *
     *				b_regtownship	township name					    *
     *				surnamecount	number of transcribed records	    *
     *				linkcount		number linked to the tree		    *
     *				low				lowest registration number		    *
     *				high			highest registration number		    *
     ********************************************************************/
    function getCountyStatistics()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
		$query	= 'SELECT B_RegCounty, B_RegTownship, ' .
								'SUM(B_Surname != "") AS SurnameCount,  ' .
								'SUM(B_Idir != 0) AS LinkCount, ' .
								'MIN(B_RegNum) as low, ' .
								'MAX(B_RegNum) as high  ' .
						'FROM Births ' .
						$this->where .
						'GROUP BY B_RegCounty, B_RegTownship ' .
						'ORDER BY B_RegCounty, B_RegTownship';
		$stmt	= $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{
		    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}
		else
		{
		    $msg	.= "query '" . $query . "' failed: " .
						   print_r($this->sqlParms, true) . ': ' .
						   print_r($stmt->errorInfo(), true);
		    return null;
		}		    // query failed
    }		        // function Birth::getCountyStatistics

}		// class BirthSet 
