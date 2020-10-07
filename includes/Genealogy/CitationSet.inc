<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CitationSet.inc														*
 *																		*
 *  Definition of a class representing a set of Citation records.		*
 *																		*
 *  Examples:															*
 *		$allCitations	= new CitationSet();							*
 *																		*
 *  History:															*
 *		2017/10/21		created											*
 *		2017/12/18		do not throw exception unless both count and	*
 *						limit are greater than 1000						*
 *		2018/11/01      ignore 2nd parameter of constructor if order    *
 *		                passed in first parameter                       *
 *		2019/01/06      move to namespace Genealogy                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class CitationSet													*
 *																		*
 *  Definition of a class recording information about a set of Citation	*
 *  records for vital statistics registrations.							*
 *																		*
 ************************************************************************/

class CitationSet extends RecordSet
{
    /********************************************************************
     *	function CitationSet::__construct								*
     *																	*
     *	Construct an instance of CitationSet based upon the search		*
     *	parameters.														*
     *																	*
     *	Input:															*
     *	    $parms				associative array of lookup parameters	*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms		= null,
						 $order		= "`Order`")
    {
        global  $warn;

        // second parameter is ignored if $parms['order'] present
        foreach ($parms as $field => $value)
        {
            if (strtolower($field) == 'order')
                $order      = $value;
        }
        $parms['order']		= $order;

		parent::__construct('tblSX', $parms);
        if ($this->information['count'] > 1000 && $this->limit > 1000)
        {
            $warn       .= '<p>CitationSet: Too many matching citations, ' . 
                            number_format($this->information['count']) . 
                            ' query=' . 
                            debugPrepQuery($this->query, $this->sqlParms) . 
                            ', parms=' . print_r($parms, true). "</p>\n";
            $this->limit    = 0;        // suppress query
        }
    }		// function CitationSet::__construct

    /********************************************************************
     *  function CitationSet::getWhere									*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	Returns:														*
     *	    string containing WHERE clause								*
     *	    A zero length string is returned if the input array is		*
     *      empty, otherwise the string starts with the word WHERE		*
     ********************************************************************/
    protected function getWhere()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if (!is_array($this->parms))
		    throw new Exception("CitationSet::getWhere:: parms=" .
							print_r($this->parms, true));   

		$where		= '';
		$and		= 'WHERE ';

		if (!is_array($this->sqlParms))
		    $this->sqlParms		= array();

		foreach($this->parms as $fldname => $value)
		{			// loop through all parameters
		    $fieldLc		= Citation::getRealFieldName($fldname);
		    switch($fieldLc)
		    {
				case 'idsx':
				{		// primary key
				    $expression	= $this->getWhereInt('IDSX',
									      $value);
				    if (strlen($expression) > 0)
				    {
						$where	.= $and . $expression;
						$and	= ' AND ';
				    }
				    break;
				}		// primary key

				case 'idime':
				{		// IDIME record key
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }
				    break;
				}		// IDIME record key

				case 'idir':
				{		// IDIR pseudo-field
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . '((Type>=0 AND Type<=9) OR ' .
									    'Type=15 OR ' .
									    'Type=16 OR ' .
									    'Type=26 OR ' .
									    'Type=27)';
						$and		= ' AND ';
						$where		.= $and . $expression;
				    }
				    break;
				}		// IDIR pseudo-field

				case 'idmr':
				{		// IDMR pseudo-field
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . '(Type>=18 AND Type<=24)';
						$and		= ' AND ';
						$where		.= $and . $expression;
				    }
				    break;
				}		// IDMR pseudo-field

				case 'ider':
				{		// IDER pseudo-field
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . 'Type=30';
						$and		= ' AND ';
						$where		.= $and . $expression;
				    }
				    else
						throw new Exception("CitationSet: getWhere: ".
							"Invalid `$fldname` value '$value'");
				    break;
				}		// IDER pseudo-field

				case 'idcr':
				{		// IDCR pseudo-field
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . '(Type=11 OR ' .
									   'Type=12 OR ' .
									   'Type=13 OR ' .
									   'Type=17)';
				 	$and		= ' AND ';
						$where		.= $and . $expression;
				    }
				    break;
				}		// IDCR pseudo-field

				case 'idnx':
				{		// IDNX pseudo-field
				    $expression		= $this->getWhereInt('IDIME',
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . 'Type=10';
						$and		= ' AND ';
						$where		.= $and . $expression;
				    }
				    break;
				}		// IDNX pseudo-field

				case 'srcdetail':
				case 'srcdettext':
				case 'srcdetnote':
				case 'filingref':
				case 'content':
				case 'override':
				{		// text fields
				    $expression		= $this->getWherePattern($fieldLc,
										         $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }
				    break;
				}		// text fields

				case 'enteredd':
				{		// encoded date field
				    $where		.= $and . "`$fldname`=?";
				    $this->sqlParms[]	= $value;
				    $and		= ' AND ';
				    break;
				}		// encoded date field

				case 'enteredsd':
				{		// YYYMMDD fields
				    if (is_array($value) && count($value) == 2)
				    {		// range of dates
						$where		.= $and . "`$fldname`>=?" .
									" AND `$fldname`<=?";
						$this->sqlParms[]	= $value[0];
						$this->sqlParms[]	= $value[1];
						$and		= ' AND ';
				    }		// range of dates
				    else
				    if (is_string($value) &&
						(preg_match("/^-?\d+$/", $value) == 1))
				    {		// signed integer string
						$where		.= $and . "`$fldname`=?";
						$this->sqlParms[]	= $value;
						$and		= ' AND ';
				    }		// signed integer string
				    else
				    if (is_int($value))
				    {		// integer
						$where		.= $and . "`$fldname`=?";
						$this->sqlParms[]	= $value;
						$and		= ' AND ';
				    }		// integer
				    else
						throw new Exception("CitationSet: getWhere: " .
							"Invalid `$fldname` value '$value'");
				    break;
				}		// YYYYMMDD fields

				case 'idsr':
				case 'type':
				case 'srcprintdetail':
				case 'srcprinttext':
				case 'srcprintnote':
				case 'srcprint':
				case 'srcsurety':
				case 'order':
				case 'used':
				case 'verified':
				case 'overridefootnote':
				case 'overridesubsequent':
				case 'overridebibliography':
				{		// positive integer fields
				    $expression		= $this->getWhereInt($fldname,
										      $value);
				    if (strlen($expression) > 0)
				    {
						$where		.= $and . $expression;
						$and		= ' AND ';
				    }
				    break;
				}		// positive integer fields

                case 'template':
				{		// reserved
				    break;
				}		// reserved

				default:
				{
				    $warn .= "<p>CitationSet::getWhere: " . __LINE__ .
						     " \$fldname='$fldname', \$fieldLc='$fieldLc'" .
				    	     " unrecognized parameter.</p>\n";
				    break;
				}
		    }			// act on specific fields
		}			// loop through all parameters

		return $where;
    }		// function CitationSet::getWhere

    /********************************************************************
     *	function CitationSet::rewind									*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	This is redefined to make the state identifier the index of		*
     *	the object.														*
     *	Reposition to the first element of the "array".					*
     *																	*
     *  Returns:														*
     *		    first class instance in the set							*
     ********************************************************************/
    public function rewind()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if ($this->list === null)
		{
		    if ($this->information['count'] > 1000 && $this->limit > 1000)
				throw new Exception('CitationSet: Too many matching citations, ' . number_format($this->information['count']) . ' query=' . debugPrepQuery($this->query, $this->sqlParms));
		    // construct a query for the specific matches
		    $query		= $this->query;

		    // query the database for matches
		    $stmt		= $connection->prepare($query);
		    $queryText		= debugPrepQuery($query, $this->sqlParms);
		    if ($stmt->execute($this->sqlParms))
		    {		// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
				if ($debug)
				    $warn	.= "<p>CitationSet.inc: " . __LINE__ .
							   " $queryText</p>\n";

				// have a result set, fetch rows
                $this->list		= array();
				if (!class_exists(__NAMESPACE__ . '\\Citation'))
				    include __NAMESPACE__ . "/Citation.inc";
				foreach($result as $row)
				{		// loop through records from table
				    if (is_array($row))
				    {
                        $citation	                = new Citation($row);
                        $citation->setTemplate($this->template);
						$this->list[$row['idsx']]	= $citation;
				    }
				}		// loop through records from table
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("CitationSet::rewind: '$queryText', " .
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		return reset($this->list);	// first entry
    }		// public function rewind()

}		// class CitationSet
