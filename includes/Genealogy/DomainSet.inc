<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  DomainSet.inc														*
 *																		*
 *  Definition of a class representing a set of Domain records.			*
 *																		*
 *  History:															*
 *		2017/10/16		created											*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/11/28      add support for field partof                    *
 *		2019/12/02      if partof is not specified only return top level*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';

/************************************************************************
 *  class DomainSet														*
 *																		*
 *  Definition of a class representing a set of Domain records.			*
 ************************************************************************/

class DomainSet extends RecordSet
{

    /********************************************************************
     *	function DomainSet::__construct									*
     *																	*
     *	Construct an instance of DomainSet based upon the parameters	*
     *																	*
     *	Input:															*
     *	    $parms			associative array of lookup parameters		*
     ********************************************************************/
    function __construct($parms	= array(),
						 $order	= "`Name`")
    {
        foreach($parms as $name=> $value)
            if (strtolower($name) == 'order')
                $order      = $value;
		$parms['order']		= $order;
		parent::__construct('Domains', $parms);
    }		// DomainSet::__construct

    /********************************************************************
     *	function DomainSet::getWhere									*
     *																	*
     *	Implement class specific interpretation of search parameters.	*
     ********************************************************************/
    function getWhere()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		// validate parameter
		$limit  				= null;
		$offset					= null;
		$where					= '';
		$and					= 'WHERE ';
		$this->sqlParms			= array();
		$language				= null;
		$cc		    			= null;
		$domain					= null;
		$state					= null;
		$partof					= null;

		foreach($this->parms as $key 	=> $value)
		{			// loop through all parameters
		    $fieldLc	        = Domain::getRealFieldName($key);
		    $matches	        = array();
		    if (preg_match('/^(.*) AS (.*)$/i', $fieldLc, $matches))
		    {
				$fieldExpr	    = $matches[1];
				$fieldLc	    = strtolower($matches[2]);
		    }

		    switch($fieldLc)
		    {		// act on specific keys
				case 'countrycode':
                {
                    $cc                 = $value;
				    if (is_string($cc) && strlen($cc) >= 2)
                    {
                        if (strtoupper($cc) == 'UK')
                            $cc         = 'GB';
						$where			.= $and . $fieldExpr . "=?";
						array_push($this->sqlParms, $cc);
						$and			= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
                                   print_r($value, true) . "</p>\n";
                        $warn   .= "<p>parms=" . print_r($this->parms, true) .
                                    "</p>\n";
                        showTrace();
                        throw new Exception("Unsupported value for `$key`=" .
                                   print_r($value, true));
				    }		// or expression
				    break;
				}			// cc

				case 'state':
				{
					$state		    = strtoupper($value);
				    if (is_string($state) && strlen($state) > 0)
				    {
						$where		.= $and . $fieldExpr . "=?";
						array_push($this->sqlParms, $state);
						$and		= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// state or province

				case 'partof':
                {
                    $partof             = strtoupper($value);
				    if (is_string($partof) && strlen($partof) >= 2)
				    {
                        if (substr($partof, 0, 2) == 'UK')
                            $partof     = 'GB' . substr($partof, 2);
                        if (strlen($partof) == 2)
						    $where		.= $and . "(PartOf=? OR PartOf IS NULL OR PartOf='')";
                        else
						    $where		.= $and . "PartOf=?";
						array_push($this->sqlParms, $partof);
						$and		= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// state or province


				case 'domain':
				{
				    if (is_array($value) && count($value) > 0)
				    {
						$where			.= $and;
						$expr			= '';
						$or			= '';
						foreach($value as $code)
						{
						    $expr		.= $or . "`Domain`=?";
						    array_push($this->sqlParms, $code);
						    $or			= ' OR ';
						}
						if (count($value) > 1)
						    $where		.= "($expr)";
						$domain			= $value;
						$and			= ' AND ';
				    }
				    else
				    if (is_string($value) && strlen($value) > 0)
				    {
						$where			.= $and . "`Domain`=?";
						array_push($this->sqlParms, $value);
						$domain			= $value;
						$and			= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `Domain`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// domain

				case 'language':
				{
				    if (is_string($value))
				    {
						if ($value == '')
						    $value	= 'en';
						$language	= strtolower($value);
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// language

				case 'name':
				{
				    if (is_string($value) && strlen($value) > 0)
				    {
						$where	.= $and . "LOCATE(?, `Name`) > 0";
						array_push($this->sqlParms, $value);
						$and	= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// Name

				default:
				{
				    if (is_array($value))
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unrecognized `$key`=" .
							       print_r($value, true) . "</p>\n";
				    }		// or expression
				    else
						$warn	.= "<p>DomainSet::getWhere: " .
							      "Unrecognized `$key`='$value'.</p>\n";
				}			// other
		    }			// act on specific keys
		}				// loop through all parameters

        // if partof was not specified, limit to top level domains in
        // the country
        if (is_null($partof) || strlen($partof) == 0)
        {
			$where		.= $and . "(PartOf=? OR PartOf IS NULL OR PartOf='')";
            array_push($this->sqlParms, $cc);
        }

		// limit the response to one language for a country list
		if (is_null($language))
		{			// language not set
		    if ($cc)
		    {
				$where				.= $and . "`Language`=?";
				array_push($this->sqlParms, 'en');
				$this->information['index']	= 'state';
		    }
		    else
				$this->information['index']	= 'number';
		}			// language not set
		else
		{			// language explicitly set
		    $where				.= $and . "`Language`=?";
		    array_push($this->sqlParms, $language);
		    if ($cc)
				$this->information['index']	= 'state';
		    else
				$this->information['index']	= 'domain';
		}			// language explicitly set

		return	$where;
    }		// DomainSet::getWhere

    /********************************************************************
     *	function DomainSet::rewind										*
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
				    $warn	.= "<p>DomainSet.inc: " . __LINE__ .
							   " $queryText</p>\n";
				}

				// have a result set, fetch rows
				$retval		= array();
				if (!class_exists(__NAMESPACE__ . '\\Domain'))
				    include __NAMESPACE__ . '/Domain' . ".inc";
				if (array_key_exists('index', $this->information))
				    $index		= $this->information['index'];
				else
				    $index		= 'number';
				foreach($result as $row)
				{		// loop through records from table
				    if (is_array($row))
				    {
						$object			= new Domain($row);
						$key		    = $row['domain'];
						$retval[$key]	= $object;
				    }
				}		// loop through records from table

				$this->list	= $retval;
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("DomainSet::rewind: '$query', " .
							       "parms=$sqlParmsText, " .
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		return reset($this->list);	// first entry
    }		// public function rewind()

}		// class DomainSet

