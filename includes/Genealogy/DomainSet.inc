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
 *		2020/03/24      correct handling of domain list containing      *
 *		                empty parameters                                *
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
        foreach($parms as $name => $value)
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
		$cc		    			= 'CA';
		$domain					= null;
		$state					= null;
		$partof					= null;
        $parmsText              = "<table>\n";
		foreach($this->parms as $key 	=> $value)
        {			// loop through all parameters
            $parmsText          .= "    <tr><th>$key</th><td>";
            $parmsText          .= var_export($value, true);
            $parmsText          .= "</td></tr>\n";
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
				    if (is_string($cc) && strlen($cc) >= 2)
                    {
                        $cc                 = strtoupper($value);
                        if ($cc == 'UK')
                            $cc             = 'GB';
						$where			    .= "$and$fieldExpr=:cc";
						$this->sqlParms['cc']   = $cc;
						$and			    = ' AND ';
				    }
				    else
				    {		// not a string
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
                                   var_export($value, true) . "</p>\n";
                        $warn   .= "<p>parms=" . print_r($this->parms, true) .
                                    "</p>\n";
				    }		// not a string
				    break;
				}			// countrycode

				case 'state':
				{			// state or province
				    if (is_string($value) && strlen($value) > 0)
				    {
					    $state		        = strtoupper($value);
						$where		        .= "$and$fieldExpr=:state";
						$this->sqlParms['state']    = $state;
						$and		        = ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       var_export($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// state or province

				case 'partof':
                {
                    if (is_string($value))
                    {
                        if (strlen($value) >= 2)
	                    {
	                        $partof         = strtoupper($value);
	                        if (substr($partof, 0, 2) == 'UK')
	                            $partof     = 'GB' . substr($partof, 2);
					    }
                        else
                        if (strlen($value) != 0)
						    $warn	.= "<p>DomainSet::getWhere: " . __LINE__ .
							           " Unsupported value for `$key`=" .
							           "'$value'</p>\n";
                    }                   // string
                    else
                    if (!is_null($value))
				    {		            // not a string
						$warn	.= "<p>DomainSet::getWhere: " . __LINE__ .
							       " Unsupported value for `$key`=" .
							       var_export($value, true) . "</p>\n";
				    }		            // not a string
				    break;
				}			            // partof


				case 'domain':
				{
				    if (is_array($value) && count($value) > 0)
				    {
						$expr			= '';
                        $or			    = '';
                        $i              = 1;
						foreach($value as $code)
                        {
                            if (strlen($code) > 0)
                            {
						        $expr		.= $or . "`Domain`=:domain$i";
						        $this->sqlParms["domain$i"] = $code;
                                $or			= ' OR ';
                                $i++;
                            }
                        }

                        if ($i > 1)
                        {
                            $where		.= "$and($expr)";
						    $domain			= $value;
                            $and			= ' AND ';
                        }
				    }
				    else
				    if (is_string($value) && strlen($value) > 0)
				    {
						$where			.= $and . "`Domain`=:domain";
						$this->sqlParms['domain']   = $value;
						$domain			= $value;
						$and			= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `Domain`=" .
							       gettype($value) . "</p>\n";
				    }		// or expression
				    break;
				}			// domain

				case 'language':
				{
				    if (is_string($value))
				    {
						if ($value == '')
                            $language   = 'en';
                        else
						    $language	= strtolower($value);
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       var_export($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// language

				case 'name':
				{
				    if (is_string($value) && strlen($value) > 0)
				    {
						$where	.= $and . "LOCATE(:name, `Name`) > 0";
						$this->sqlParms['name']     = $value;
						$and	= ' AND ';
				    }
				    else
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unsupported value for `$key`=" .
							       var_export($value, true) . "</p>\n";
				    }		// or expression
				    break;
				}			// Name

				default:
				{
				    if (is_array($value))
				    {		// or expression
						$warn	.= "<p>DomainSet::getWhere: " .
							       "Unrecognized `$key`=" .
							       var_export($value, true) . "</p>\n";
				    }		// or expression
				    else
						$warn	.= "<p>DomainSet::getWhere: " .
							      "Unrecognized `$key`='$value'.</p>\n";
				}			// other
		    }			// act on specific keys
        }				// loop through all parameters
        $parmsText              .= $parmsText . "</table>\n";

        // if partof was not specified, limit to top level domains in
        // the country
        if (is_null($partof) || strlen($partof) == 0)
        {
			$where		.= $and . "(PartOf=:cc OR (LEFT(Domain,2)=:cc AND (PartOf IS NULL OR PartOf='')))";
            $this->sqlParms['cc']   = $cc;
            $and        = ' AND ';
        }
        else
        {
            if (strlen($partof) == 2)
                $where		.= $and .
"(PartOf=:partof OR (LEFT(Domain,2)=:partof AND (PartOf IS NULL OR PartOf='')))";
            else
			    $where		.= $and . "PartOf=:partof";
			$this->sqlParms['partof']   = $partof;
			$and	    	= ' AND ';
        }

		// limit the response to one language for a country list
		if (is_null($language))
		{			// language not set
			$where				.= $and . "`Language`=:language";
			$this->sqlParms['language']     = 'en';
		}			// language not set
		else
		{			// language explicitly set
		    $where				.= $and . "`Language`=:language";
		    $this->sqlParms['language']     = $language;
		}			// language explicitly set
		if ($cc)
			$this->information['index']	    = 'state';
	    else
			$this->information['index']	    = 'domain';

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
		    // query the database for matches
		    $query		= $this->query;
            $stmt		= $connection->prepare($query);
            $queryText  = debugPrepQuery($query, $this->sqlParms);
		    if ($stmt->execute($this->sqlParms))
		    {		// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);

				if ($debug)
				    $warn	.= "<p>DomainSet.inc: " . __LINE__ .
							   " $queryText</p>\n";

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
				throw new Exception("DomainSet::rewind: '$queryText', " .
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		return reset($this->list);	// first entry
    }		// public function rewind()

}		// class DomainSet

