<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  MethodistBaptismSet.inc												*
 *																		*
 *  Definition of a class representing a set of Methodist baptism       *
 *  registrations.	                                                    *
 *																		*
 *  History:															*
 *		2019/04/28		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/MethodistBaptism.inc';

/************************************************************************
 *  class MethodistBaptismSet											*
 *																		*
 *  Definition of a class representing a set of marriage registrations	*
 *  or marriage certificates.											*
 *																		*
 ************************************************************************/

class MethodistBaptismSet extends RecordSet
{

    /********************************************************************
     *	function MethodistBaptismSet::__construct						*
     *																	*
     *	Create an instance of the class MethodistBaptismSet		 		*
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
     ********************************************************************/
    function __construct($parms	= array())
    {
		parent::__construct('MethodistBaptisms',
						   $parms);
    }		// MethodistBaptismSet::__construct

    /********************************************************************
     *  function MethodistBaptismSet::getWhere							*
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
		    $warn	        .= '<p>MethodistBaptismSet::getWhere: ' . __LINE__ . ' ' . 
					    	   print_r($this->parms, true) . "</p>\n";
		$where		    	= '';
		$and		    	= 'WHERE ';

		if (!is_array($this->sqlParms))
		    $this->sqlParms	    = array();
		$tparms		        	= array();
		$regYear        		= null;

		// build WHERE clause
		foreach($this->parms as $fldname => $value)
        {			            // translate field names and ignore empty
            if (is_null($value) || strlen($value) == 0)
                continue;
		    $fldnameLc          = strtolower($fldname);
		    switch($fldnameLc)
		    {                   // act on specific field names
				case 'surname':
                {               // surname
                    $where      .= $and . $this->getWherePattern('Surname',
                                                                 $value,
                                                                 true);
                    $and		= ' AND ';
                    break;
                }

                case 'surnamesoundex':
                {
					$where	    .= "LEFT(SOUNDEX(Surname),4)=LEFT(SOUNDEX(:surname),4)";
					$this->sqlParms['surname']	= $value;
					$and		= ' AND ';
				    break;
				}               // surname

                case 'volume':
                case 'page':
                case 'idir':
                {               // numeric fields
                    $where      .= $and . $this->getWhereInt($fldname,
                                                             $value,
                                                             $fldnameLc);
                    $and		= ' AND ';
                    break;
                }               // numeric fields


				case 'district':
				case 'area':
				case 'givenname':
				case 'surname':
				case 'father':
				case 'mother':
				case 'residence':
				case 'birthplace':
				case 'birthdate':
				case 'baptismdate':
				case 'baptismplace':
				case 'minister':
				{		        // text fields
                    $where      .= $and . $this->getWherePattern($fldname,
                                                                 $value,
                                                                 true);
                    $and		= ' AND ';
				    break;
				}		        // match in string

		    }                   // act on specific field names
		}			            // translate field names and ignore empty

		return $where;
    }		// function MethodistBaptismSet::getWhere

}		// class MethodistBaptismSet 
