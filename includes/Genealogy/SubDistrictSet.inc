<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  SubDistrictSet.inc							*
 *									*
 *  Definition of a class representing a set of subdistricts.		*
 *									*
 *  History:								*
 *	2018/01/24	created						*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';

/************************************************************************
 *  class SubDistrictSet						*
 *									*
 *  Definition of a class representing a set of subdistricts		*
 ************************************************************************/

class SubDistrictSet extends RecordSet
{

    /********************************************************************
     *	__construct							*
     *									*
     *	Create an instance of the class SubDistrictSet	 		*
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
	parent::__construct('SubDistricts', $parms);
    }		// SubDistrictSet::__construct

    /********************************************************************
     *	SubDistrictSet::getWhere					*
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
	    $warn	.= '<p>SubDistrictSet::getWhere: ' . 
			   print_r($this->parms, true) . "</p>\n";
	$where			= '';
	$and			= 'WHERE ';
	if (!is_array($this->sqlParms))
	    $this->sqlParms		= array();

	// create WHERE clause
	foreach($this->parms as $fldname => $value)
	{
	    $fieldLc		= SubDistrict::getRealFieldName($fldname);
	    switch($fieldLc)
	    {			// act on specific fields
		case 'sd_census':
		{		// census field
		    if (is_string($value))
		    {
			$censusId	= $value;
			$censusObj = new Census(array('censusid'=> $value));
		    }
		    else
		    if ($value instanceof Census)
		    {
			$censusObj		= $value;
			$censusId		= $censusObj->get('censusid');
		    }
		    $where			.= "$and`SD_Census`=:censusid";
		    $this->sqlParms['censusid']	= $censusId;
		    $and			= ' AND ';
		    if (!$censusObj->isExisting())
			$warn	.= "<p>SubDistrictSet::getWhere: " . __LINE__ .
					" Unsupported Census='$value'</p>\n";
		    break;
		}		// census field

		case 'sd_distid':
		{		// district id field
		    if (is_string($value))
		    {
			$distId			= $value;
			$parms			= array('census'=>$censusObj,
							'id'	=> $value);
			$districtObj		= new District($parms);
		    }
		    else
		    if ($value instanceof District)
		    {
			$districtObj		= $value;
			$distId			= $districtObj->get('id');
		    }
		    $where			.= "$and`SD_DistId`=:distid";
		    $this->sqlParms['distid']	= $distId;
		    $and			= ' AND ';
		    if (!$districtObj->isExisting())
			$warn	.= "<p>SubDistrictSet::getWhere: " . __LINE__ .
					" Unsupported District='$distId'</p>\n";
		    break;
		}		// domain field

		default:
		{
		    $warn	.= "<p>SubDistrictSet::getWhere: " . __LINE__ .
			" Unrecognized search parameter `$fldname`.</p>";
		    break;
		}

	    }			// act on specific fields
	}			// loop through all parameters

	return $where;
    }		// SubDistrictSet::getWhere

}		// class SubDistrictSet 
