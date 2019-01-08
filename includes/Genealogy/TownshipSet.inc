<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  TownshipSet.inc							*
 *									*
 *  Definition of a class representing a set of townships.		*
 *									*
 *  History:								*
 *	2017/12/20	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Township.inc';

/************************************************************************
 *  class TownshipSet							*
 *									*
 *  Definition of a class representing a set of townships		*
 ************************************************************************/

class TownshipSet extends RecordSet
{

    /********************************************************************
     *	__construct							*
     *									*
     *	Create an instance of the class TownshipSet	 		*
     *									*
     *	Parameters:							*
     *	    $parameters		associative array of fieldname, value	*
     *				pairs to define the subset of the	*
     *				records in the SQL table that are	*
     *				represented by this instance.  The	*
     *				parameters 'offset' and 'limit'		*
     *				are reserved to set the OFFSET and	*
     *				LIMIT clauses.  Use '`offset`', and	*
     *				'`limit`' to reference fields with	*
     *				those names. This may be null.		*
     *									*
     *	Throws:								*
     *	    An exception for invalid parameters.			*
     ********************************************************************/
    function __construct($parms	= array())
    {
	parent::__construct('Townships', $parms);
    }		// TownshipSet::__construct

    /********************************************************************
     *	TownshipSet::getWhere						*
     *									*
     *  Given an associative array of parameters for searching the	*
     *  table, return the appropriate SQL WHERE clause.			*
     *									*
     *	$this->parms	associative array of field/value combinations	*
     *			the field names are interpreted according to	*
     *			the standard translate table.  Note that	*
     *			because the parameter is an associative array	*
     *			that only one value can be specified for each	*
     *			field name.
     *	$this->sqlParms	construct array of parms for PDO execute	*
     *	$this->limit	return value of limit parameter			*
     *	$this->offset	return value of offset parameter		*
     *									*
     *	Returns:							*
     *	    string containing WHERE clause				*
     *	    A zero length string is returned if the input array is	*
     *      empty, otherwise the string starts with the word WHERE	*
     *									*
     *	Side-Effects:							*
     *	    Issues with the supplied parms are reported by adding	*
     *	    warning messages onto the global variable $warn.		*
     ********************************************************************/
    protected function getWhere()
    {
	global	$debug;
	global	$warn;

	if ($debug)
	    $warn	.= '<p>TownshipSet::getWhere: ' . 
			   print_r($this->parms, true) . "</p>\n";
	$where			= '';
	$and			= 'WHERE ';
	if (!is_array($this->sqlParms))
	    $this->sqlParms		= array();

	// create WHERE clause
	foreach($this->parms as $fldname => $value)
	{
	    $fieldLc		= Township::getRealFieldName($fldname);
	    switch($fieldLc)
	    {			// act on specific fields
		case 'domain':
		{		// domain field
		    if (is_string($value))
			$domainObj = new Domain(array('domain'	=> $value,
						      'language'=> 'en'));
		    else
		    if ($value instanceof Domain)
			$domainObj	= $value;
		    else
		    {
			$warn		.= "<p>TownshipSet::getWhere: " . 
					   "invalid domain=" . gettype($value) .
					   "</p>\n";
			$value		= print_r($value,true);
			$domainObj	= null;
		    }
		    if ($domainObj)
		    {
			$where	.= "$and`Domain`=?";
			array_push($this->sqlParms, $domainObj->get('domain'));
			$and	= ' AND ';
			if (!$domainObj->isExisting())
			    $warn	.= "<p>TownshipSet::getWhere: " . __LINE__ .
					" Unsupported Domain='$value'</p>\n";
		    }
		    break;
		}		// domain field

		case 'prov':
		{		// domain field
		    if (is_string($value))
			$domainObj = new Domain(array('domain'	=> 'CA' .$value,
						      'language'=> 'en'));
		    else
		    if ($value instanceof Domain)
			$domainObj	= $value;
		    else
		    {
			$warn		.= "<p>TownshipSet::getWhere: " . 
					   "invalid prov=" . gettype($value) .
					   "</p>\n";
			$value		= print_r($value,true);
			$domainObj	= null;
		    }
		    if ($domainObj)
		    {
			$where	.= "$and`Domain`=?";
			array_push($this->sqlParms, $domainObj->get('domain'));
			$and	= ' AND ';
			if (!$domainObj->isExisting())
			    $warn	.= "<p>TownshipSet::getWhere: " . __LINE__ .
					   " Unsupported Prov='$value'</p>\n";
		    }
		    break;
		}		// domain field

		case 'county':
		{		// county
		    if (is_string($value))
		    {
			if (strlen($value) > 0)
			{
			    $where	.= "$and`County`=?";
			    array_push($this->sqlParms, $value);
			    $and	= ' AND ';
			}
		    }
		    else
		    if ($value instanceof County)
		    {
			$countyObj	= $value;
			$domainObj	= $countyObj->getDomainObj();
			$where		.= "$and`Domain`=?";
			array_push($this->sqlParms, $value->get('domain'));
			$and		= ' AND ';
			$where		.= "$and`County`=?";
			array_push($this->sqlParms, $value->get('code'));
		    }
		    else
			$warn	.= "<p>TownshipSet::getWhere: " . __LINE__ .
					"invalid county=" . gettype($value) .
				   "</p>\n";
		    break;
		}		// county field

		case 'code':
		{		// township code
		    if (strlen($value) > 0)
		    {
			$where	.= "$and`$fldname`=?";
			array_push($this->sqlParms, $value);
			$and	= ' AND ';
		    }
		    break;
		}		// most fields

		case 'name':
		{		// Name of township
		    $expression	= $this->getWherePattern($fieldLc,
							  $value,
							  $this->sqlParms);
		    if (strlen($expression) > 0)
		    {		// expression generated
			$where		.= $and . $expression;
			$and		= ' AND ';
		    }		// expression generated
		    break;
		}		// Name of township

		default:
		{
		    $warn	.= "<p>TownshipSet::getWhere: " . __LINE__ .
				   " Unrecognized search parameter `$fldname`.</p>";
		    break;
		}

	    }			// act on specific fields
	}			// loop through all parameters

	return $where;
    }		// TownshipSet::getWhere

}		// class TownshipSet 
