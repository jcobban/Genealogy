<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountySet.inc														*
 *																		*
 *  Definition of a class representing a set of County records.			*
 *																		*
 *  TABLE Counties (													*
 *		Domain			CHAR(4) NOT NULL DEFAULT 'CAON',				*
 *		Code			CHAR(3) NOT NULL DEFAULT '',					*
 *		Name			VARCHAR(255) NOT NULL DEFAULT '',				*
 *		StartYear		INTEGER(4) NOT NULL DEFAULT '1852',				*
 *		EndYear			INTEGER(4) NOT NULL DEFAULT '9999')				*
 *		UNIQUE KEY `Domain` (`Domain`, `Code`),							*
 *		KEY `Name` (`Name`) )											*
 *																		*
 *  Examples:															*
 *		$allCounties	= new CountySet();								*
 *		$countryCodes	= $allCounties->getDistinct('cc');				*
 *		$domainCodes	= $allCounties->getDistinct('domain');			*
 *		$ONCounties		= new CountySet(array('prov' => 'ON'));			*
 *		$MICounties		= new CountySet(array('state' => 'MI'));		*
 *		$MBCounties		= new CountySet(array('domain' => 'CAMB'));		*
 *																		*
 *  History:															*
 *		2017/10/21		created											*
 *		2018/02/18		if called with instance of Domain, pass that	*
 *						instance to new County to avoid creating		*
 *						new Domain and Country records					*
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class CountySet														*
 *																		*
 *  Definition of a class recording information about a set of County	*
 *  records for vital statistics registrations.							*
 *																		*
 ************************************************************************/

class CountySet extends RecordSet
{
    /********************************************************************
     *		CountySet::$domain											*
     *																	*
     ********************************************************************/
    private	$domain		= null;
    private	$domainObj	= null;

    /********************************************************************
     *	CountySet::__construct											*
     *																	*
     *	Construct an instance of CountySet based upon the search		*
     *	parameters.														*
     *																	*
     *	Input:															*
     *	    $parms				associative array of lookup parameters	*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms		= null,
						 $order		= "`Name`")
    {
		$parms['order']		= $order;
		parent::__construct('Counties', $parms);
    }		// CountySet::__construct

    /********************************************************************
     *	CountySet::getWhere												*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	Returns:														*
     *	    string containing WHERE clause								*
     *	    A zero length string is returned if the input array is		*
     *     empty, otherwise the string starts with the word WHERE		*
     ********************************************************************/
    protected function getWhere()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if (!is_array($this->parms))
		    throw new Exception("CountySet::getWhere:: parms=" .
							print_r($this->parms, true));   

		$where      		= '';
		$and        		= 'WHERE ';
		$cc	        	    = null;
		$state      		= null;

		if (!is_array($this->sqlParms))
		    $this->sqlParms		= array();

		foreach($this->parms as $fldname => $value)
		{			// loop through all parameters
		    $fieldLc		= County::getRealFieldName($fldname);
		    $matches		= array();
		    if (preg_match('/^(.*) AS (.*)$/i', $fieldLc, $matches))
		    {
				$fieldExpr	= $matches[1];
				$fieldLc	= strtolower($matches[2]);
		    }

		    switch($fieldLc)
		    {			// act on specific fields
				case 'countrycode':
				{
				    if (is_string($value) && strlen($value) > 0)
				    {
						$cc			        = $value;
						if ($state)
						    $this->domain	= $cc . $state;
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// cc

				case 'domain':
				{
				    if ($value instanceof Domain)
				    {
						$this->domainObj	= $value;
						$this->domain		= $value->get('domain');
				    }
				    else
				    if (is_string($value) && strlen($value) >= 4)
				    {
						$this->domain		= $value;
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$fldname`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}		// domain

				case 'prov':
				{		// Province pseudo field
				    if (is_string($value) && strlen($value) > 0)
				    {
						$state			= $value;
						if ($cc)
						    $this->domain	= $cc . $state;
						else
						    $this->domain	= 'CA' . $state;
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}		// Province pseudo field

				case 'state':
				{		// State pseudo field
				    if (is_string($value) && strlen($value) > 0)
				    {
						$state			= $value;
						if ($cc)
						    $this->domain	= $cc . $state;
						else
						    $this->domain	= 'US' . $state;
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}		// State pseudo field

				case 'code':
				{		// Code
				    if (is_string($value) && strlen($value) > 0)
				    {
						$where		.= $and . "`Code`=?";
						array_push($this->sqlParms, $value);
						$and		= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}		// Code


				case 'name':
				{		// Name
				    if (is_string($value) && strlen($value) > 0)
				    {
						// search for match anywhere in Name
						$where		.= $and . "LOCATE(?, `Name`) > 0";
						array_push($this->sqlParms, $value);
						$and		= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>CountySet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}		// Name

				case 'startyear':
				case 'endyear':
				{
				    $expr	= $this->getWhereInt($fieldLc, $value);
				    if (strlen($expr) > 0)
				    {
						$where	.= $and . $expr;
						$and	= ' AND ';
				    }
				    break;
				}		// Name

				default:
				{
				    $warn .= "<p>CountySet::getWhere: " . __LINE__ .
						" \$fldname='$fldname', fieldLc='$fieldLc'</p>\n";
				    $warn	.= "<p>CountySet::getWhere: unrecognized parameter '$fldname'.</p>\n";
				    break;
				}
		    }			// act on specific fields
		}			// loop through all parameters

		if (is_null($this->domainObj) && $this->domain)
		{			// domain id supplied
		    $this->domainObj	=
						new Domain(array('domain'	=> $this->domain,
								         'language'	=> 'en'));
		}
		if (!is_null($this->domainObj))
		{
		    if ($this->domainObj->isExisting())
		    {	// valid domain
				$where			.= $and . "`Domain`=?";
				array_push($this->sqlParms, $this->domain);
				$and			= ' AND ';
				$cc			= 'CA';
				$prov			= $value;
				$domainSet		= true;
		    }	// valid domain
		    else
				$warn	.= "<p>CountySet::getWhere: Invalid domain code '$value'.</p>";
		}			// domain id supplied
		else
		    $warn	.= "<p>CountySet::getWhere: Missing domain code.</p>";

		return $where;
    }		// County::getWhere

    /********************************************************************
     *	function CountySet::rewind										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	This is redefined to make the state identifier the index of		*
     *	the object.														*
     *	Reposition to the first element of the "array".					*
     *																	*
     *  Returns:														*
     *	    first class instance in the set								*
     ********************************************************************/
    public function rewind()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if ($this->list === null)
		{
		    // construct a query for the specific matches
		    $query		= $this->query;
		    $sqlParmsText	= print_r($this->sqlParms, true);

		    // query the database for matches
		    $stmt		= $connection->prepare($query);
		    if ($stmt->execute($this->sqlParms))
		    {		// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);

				if ($debug)
				{
				    $queryParts	= explode('?', $query);
				    $queryText	= $queryParts[0];
				    for($i = 1; $i < count($queryParts); $i++)
				    {
						$val	= $this->sqlParms[$i - 1];
						if (ctype_digit($val))
						    $queryText	.= $val . $queryParts[$i];
						else
						    $queryText	.= "'$val'" . $queryParts[$i];
				    }
				    $warn	.= "<p>CountySet.inc: " . __LINE__ .
							   " $queryText</p>\n";
				}

				// have a result set, fetch rows
				$retval		= array();
				if (!class_exists(__NAMESPACE__ . '\\County'))
				    include __NAMESPACE__ . '/County' . ".inc";
				if (array_key_exists('index', $this->information))
				    $index		= $this->information['index'];
				else
				    $index		= 'number';
				foreach($result as $row)
				{		// loop through records from table
				    if (is_array($row))
				    {
						if ($this->domain)
						{
						    $row['domain']	= $this->domainObj;
						    $object		= new County($row);
						    $key		= $row['code'];
						    $retval[$key]	= $object;
						}
						else
						    $retval[]		= new County($row);
				    }
				}		// loop through records from table

				$this->list	= $retval;
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("CountySet::rewind: '$query', " .
							       "parms=$sqlParmsText, " .
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		return reset($this->list);	// first entry
    }		// public function rewind()

}		// class CountySet
