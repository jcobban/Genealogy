<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  MarriageSet.inc														*
 *																		*
 *  Definition of a class representing a set of marriage registrations.	*
 *																		*
 *  History:															*
 *		2017/12/22		created											*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class MarriageSet													*
 *																		*
 *  Definition of a class representing a set of marriage registrations	*
 *  or marriage certificates.											*
 *																		*
 ************************************************************************/

class MarriageSet extends RecordSet
{

    /********************************************************************
     *	function MarriageSet::__construct								*
     *																	*
     *	Create an instance of the class MarriageSet		 				*
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
    function __construct($parms	= array())
    {
		parent::__construct('Marriage' .
				  'LEFT JOIN MarriageIndi AS Groom ON ' .
						'Marriage.M_RegYear = Groom.M_RegYear AND ' .
						'Marriage.M_RegNum = Groom.M_RegNum AND ' .
						'Marriage.M_RegDomain = Groom.M_RegDomain AND ' .
						"Groom.M_Role = 'G' " .
				  'LEFT JOIN MarriageIndi AS Bride ON ' .
						'Marriage.M_RegYear = Bride.M_RegYear AND ' .
						'Marriage.M_RegNum = Bride.M_RegNum AND ' .
						'Marriage.M_RegDomain = Bride.M_RegDomain AND ' .
						"Bride.M_Role = 'B' " .
				  'LEFT JOIN MarriageIndi AS Minister ON ' .
						'Marriage.M_RegYear = Minister.M_RegYear AND ' .
						'Marriage.M_RegNum = Minister.M_RegNum AND ' .
						'Marriage.M_RegDomain = Minister.M_RegDomain AND ' .
						"Minister.M_Role = 'M' ",
						   $parms,
						    'Marriage.*, ' .
				  'Groom.M_Surname AS G_Surname, ' .
				  'Groom.M_GivenNames AS G_Given, ' .
				  'Groom.M_BYear AS G_BYear, Groom.M_IDIR AS G_IDIR, ' .
				  'Bride.M_Surname AS B_Surname, ' .
				  'Bride.M_GivenNames AS B_Given, ' .
				  'Bride.M_BYear AS B_BYear, Bride.M_IDIR AS B_IDIR, ' .
				  'Minister.M_Surname AS M_Surname, ' .
				  'Minister.M_GivenNames AS M_Given, ' .
				  'Minister.M_IDIR AS M_IDIR');
    }		// MarriageSet::__construct

    /********************************************************************
     *  function MarriageSet::getWhere									*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
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
		    $warn	        .= '<p>MarriageSet::getWhere: ' . __LINE__ . ' ' . 
					    	   print_r($this->parms, true) . "</p>\n";
		$where		    	= '';
		$and		    	= 'WHERE ';
		$numericOrd     	= 'Marriage.M_RegYear, Marriage.M_RegNum ';
		$nominalOrd	        = 'Groom.M_Surname, Groom.M_GivenNames, ' .
					        	  'Marriage.M_RegYear, Marriage.M_RegNum ';
		if (strtoupper($this->orderby) == 'NAME')
		{
		    $this->orderby  	= $nominalOrd;
		    $expand	        	= false;
		    $oexpand	    	= false;
		}
		else
		{
		    $this->orderby	    = $numericOrd;
		    $expand	        	= canUser('edit');
		    $oexpand	    	= $expand;
		}

		if (!is_array($this->sqlParms))
		    $this->sqlParms	    = array();
		$tparms		        	= array();
		$regYear        		= null;
		$expand		        	= canUser('edit');
		$oexpand	        	= $expand;
		$selroles	        	= 0;		// bit mask of roles to include
		$range		        	= 1;		// default birth year range

		// the parameters are scanned twice so that fields whose value is used
		// by other parameters are available when needed
		foreach($this->parms as $fldname => $value)
		{			            // loop through parameters first time
		    switch(strtolower($fldname))
		    {
				case 'inchusband':
				{
				    $selroles	    |= 1;	// mask bit for grooms
				    break;
				}		        // include grooms in search

				case 'incwife':
				{
				    $selroles	    |= 2;	// mask bit for brides
				    break;
				}		        // include brides in search

				case 'incminister':
				{
				    $selroles	    |= 4;	// mask bit for ministers
				    break;
				}		        // include ministers in search

				case 'range':
				{		        // Range
				    $needSpouse		= true;
				    if (preg_match("/^([0-9]{1,2})$/", $value))
						$range	    = intval($value);
				    else	
						$warn	    .= "<p>MarriageSet::getWhere: Birth Year range '$value' must be a number.</p>\n";
				    break;
                }		        // range
            }                   // switch on parameter names
		}			            // loop through parameters first time

		// built WHERE clause
		foreach($this->parms as $fldname => $value)
		{			            // translate field names and ignore empty
		    switch(strtolower($fldname))
		    {                   // act on specific field names
				case 'regyear':
				{		        // numeric field
				    $where		    .= "$and Marriage.M_RegYear=? ";
				    $this->sqlParms[]	= $value;
				    $and		    = 'AND';
				    $regYear		= $value;
				    break;
				}		        // RegYear

				case 'regnum':
				{		        // RegNum
				    $regNum		= $value;
				    $lastRegNum		= $regNum + $count;
				    if (is_null($regYear))
				    {
						$warn		.= '<p>MarriageSet::getWhere: ' .
									    __LINE__ . 
		'Registration Number may only be specified with Registration Year.</p>';
				    }
				    else
				    {
						$where			.= "$and Marriage.M_RegNum>=? ";
						$this->sqlParms[]	= $value;
						$and			= 'AND';
						if ($expand)
						{
						    if ($regYear <= 1872 && $regNum > 10000)
							$lastRegNum	= $regNum + 10 * floor($count / 3 + 1);
						    $where		.= "AND Marriage.M_RegNum<? ";
						    $this->sqlParms[]	= $lastRegNum;
						}
				    }
				    break;
				}		        // RegNum

				case 'surname':
				{               // surname
				    $needSpouse	= true;
				    if (preg_match("/[.+*^$]/", $value))
				    {		    // match pattern
						$or			    = '';
						$where			.= "$and (";
						if ($selroles & 1)
						{
						    $where		.= "Groom.M_Surname REGEXP ? ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 2)
						{
						    $where	    .= "$or Bride.M_Surname REGEXP ? ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 4)
						{
						    $where	    .= "$or Minister.M_Surname REGEXP ? ";
						    $this->sqlParms[]	= $value;
						}
						    $or		    = 'OR';
						$where		    .= ') ';
						$and		    = 'AND';
				    }		        // match pattern
				    else
				    if (array_key_exists("SurnameSoundex", $this->parms))
				    {		        // match soundex
						$or			= '';
						$where		.= "$and (";
						if ($selroles & 1)
						{
						    $where	.= "Groom.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 2)
						{
						    $where	.= "$or Bride.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 4)
						{
						    $where	.= "$or Minister.M_SurnameSoundex=LEFT(SOUNDEX(?),4) ";
						    $this->sqlParms[]	= $value;
						}
						    $or		    = 'OR';
						$where		    .= ') ';
						$and		    = 'AND';
				    }		        // match soundex
				    else
				    {		        // match exact
						$or		        = '';
						$where		    .= "$and (";
						if ($selroles & 1)
						{
						    $where	    .= "Groom.M_Surname=? ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 2)
						{
						    $where	    .= "$or Bride.M_Surname=? ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 4)
						{
						    $where	    .= "$or Minister.M_Surname=?";
						    $this->sqlParms[]	= $value;
						}
						$or		        = 'OR';
						$where		    .= ') ';
						$and		    = 'AND';
				    }		    // match exact
		    
				    $this->orderby	    = $nominalOrd;
				    break;
				}               // surname

				case 'givennames':
				case 'occupation':
				case 'religion':
				case 'fathername':
				case 'mothername':
				case 'witnessname':
				case 'witnessres':
				case 'birthplace':
				case 'remarks':
				case 'age':
				case 'marstat':
				{		        // match anywhere in string
				    $or		        = '';
				    $where		    .= "$and (";
				    if ($selroles & 1)
				    {
						$where	    .= "LOCATE(?, Groom.M_$key)>0 ";
						$this->sqlParms[]	= $value;
				    }
				    if ($selroles & 2)
				    {
						$where	    .= "$or LOCATE(?, Bride.M_$key)>0 ";
						$this->sqlParms[]	= $value;
				    }
				    if ($selroles & 4)
				    {
						$where	    .= "$or LOCATE(?, Minister.M_$key)>0";
						$this->sqlParms[]	= $value;
				    }
				    $or		    	= 'OR';
				    $where		    .= ') ';
				    $and		    = 'AND';
				    $this->orderby	= $nominalOrd;
				    break;
				}		        // match in string

				case 'place':
				case 'date':
				{		        // match anywhere in string
				    $where		    .= "$and LOCATE(?, Marriage.M_$key) > 0 ";
				    $this->sqlParms[]	= $value;
				    $and		    = 'AND';
				    $this->orderby	= $nominalOrd;
				    break;
				}		        // match in string

				case 'surnamesoundex':
				{		        // handled under Surname
				    $this->orderby	= $nominalOrd;
				    break;
				}		        // handled under Surname

				case 'byear':
				{		        // birth year
				    if (!preg_match("/^([0-9]{1,4})$/", $value))
				    {
						$msg	    .= "Birth Year must be a number. ";
				    }
				    else
				    if  (($value < "1700") || ($value > 3000))
				    {
						$msg	    .= "Birth Year out of range. ";
				    }
				    else
				    {
						$or		    = '';
						$where	    .= "$and (";
						if ($selroles & 1)
						{
						    $where	.= "ABS(Groom.M_BYear-?) < $range ";
						    $this->sqlParms[]	= $value;
						}
						if ($selroles & 2)
						{
						    $where	.= "$or ABS(Bride.M_BYear-?) < $range";
						    $this->sqlParms[]	= $value;
						}
						$or		    = 'OR';
						$where		.= ') ';
						$and		= 'AND';
				    }
				    $this->orderby	= $nominalOrd;
				    break;
				}		        // birth year

				case 'regdomain':
				{		        // registration domain
				    $regDomain		= $value;
				    $where		    .= "$and Marriage.M_RegDomain=? ";
				    $this->sqlParms[]	= $value;
				    $and		    = 'AND';
				    break;
				}		        // registration domain

				case 'regcounty':
				case 'regtownship':
				case 'originalvolume':
				case 'originalpage':
				case 'originalitem':
				{		        // exact match on field in Marriage table
				    $needSpouse		= true;
				    $where		    .= "$and Marriage.M_$key=? ";
				    $this->sqlParms[]	= $value;
				    $and		    = 'AND';
				    $this->orderby	= $nominalOrd;
				    break;
				}		        // exact match on field in Marriage table
		    }                   // act on specific field names
		}			            // translate field names and ignore empty

		return $where;
    }		// function MarriageSet::getWhere

}		// class MarriageSet 
