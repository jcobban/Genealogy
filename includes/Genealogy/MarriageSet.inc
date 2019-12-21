<?php
namespace Genealogy;
use \PDO;
/************************************************************************
 *  MarriageSet.inc														*
 *																		*
 *  A class representing a set of transcriptions of marriage            *
 *  licenses, banns, or civil registrations.	                        *
 *																		*
 *  History:															*
 *		2017/12/22		created											*
 *		2019/12/15      add methods getSummary, getStatistics, and      *
 *		                getCountyStatistics                             *
 *		                use field names in prepared statement           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Marriage.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class MarriageSet													*
 *																		*
 *  Definition of a class representing a set of marriage registrations	*
 *  or marriage certificates.											*
 ************************************************************************/

class MarriageSet extends RecordSet
{


    /********************************************************************
     *	MarriageSet::$tableName         								*
     *																	*
     *	The first parameter to the parent constructor.                  *
     ********************************************************************/
    private static  $tableName  =
                'Marriage ' .
				  'LEFT JOIN MarriageIndi AS Groom ON ' .
						'Marriage.RegYear = Groom.RegYear AND ' .
						'Marriage.RegNum = Groom.RegNum AND ' .
						'Marriage.RegDomain = Groom.RegDomain AND ' .
						"Groom.Role = 'G' " .
				  'LEFT JOIN MarriageIndi AS Bride ON ' .
						'Marriage.RegYear = Bride.RegYear AND ' .
						'Marriage.RegNum = Bride.RegNum AND ' .
						'Marriage.RegDomain = Bride.RegDomain AND ' .
						"Bride.Role = 'B' " .
				  'LEFT JOIN MarriageIndi AS Minister ON ' .
						'Marriage.RegYear = Minister.RegYear AND ' .
						'Marriage.RegNum = Minister.RegNum AND ' .
						'Marriage.RegDomain = Minister.RegDomain AND ' .
						"Minister.Role = 'M' ";

    /********************************************************************
     *	MarriageSet::$fieldNames        								*
     *																	*
     *	The third parameter to the parent constructor.                  *
     ********************************************************************/
    private static  $fieldNames  =
				'Marriage.*, ' .
				  'Groom.Surname AS G_Surname,' .
				  'Groom.GivenNames AS G_Given,' .
                  'Groom.BYear AS G_BYear,' .
                  'Groom.IDIR AS G_IDIR, ' .
				  'Bride.Surname AS B_Surname,' .
				  'Bride.GivenNames AS B_Given,' .
                  'Bride.BYear AS B_BYear,' .
                  'Bride.IDIR AS B_IDIR, ' .
				  'Minister.Surname AS M_Surname,' .
				  'Minister.GivenNames AS M_Given,' .
                  'Minister.IDIR AS M_IDIR';

    /********************************************************************
     *	MarriageSet::$numericOrd            							*
     *	MarriageSet::$nominalOrd            							*
     *																	*
     *	Sort order depends upon the search parameters.                  *
     ********************************************************************/
	private static  $numericOrd     	= 'Marriage.RegYear, Marriage.RegNum ';
	private static	$nominalOrd	        = 'Groom.Surname, Groom.GivenNames, ' .
				        	        	  'Marriage.RegYear, Marriage.RegNum ';

    /********************************************************************
     *	MarriageSet::$expand            							    *
     *	MarriageSet::$regNum            							    *
     *	MarriageSet::$lastRegNum           							    *
     *																	*
     *	If the request is for a range of registration numbers then      *
     *	return records for every registration number in the range.      *
     ********************************************************************/
	private $expand     	            = false;
	private $regDomain    	            = null;
	private $regYear    	            = null;
	private $regNum    	                = null;
	private $lastRegNum     	        = null;

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
		parent::__construct(self::$tableName,
                            $parms,
                            self::$fieldNames);
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
		$where		    	        = '';
		$and		    	        = 'WHERE ';
		if (strtoupper($this->orderby) == 'NAME')
		{
		    $this->orderby  		= self::$nominalOrd;
		    $this->expand	        = false;
		    $oexpand	    		= false;
		}
		else
		{
		    $this->orderby	    	= self::$numericOrd;
		    $this->expand	        = canUser('edit');
		    $oexpand	    		= $this->expand;
		}

		if (!is_array($this->sqlParms))
		    $this->sqlParms	    	= array();
        $tparms		        		= array();
        $this->regDomain            = 'CAON';   // default
		$regYear        			= null;
		$regNum        			    = null;
        $oexpand	        		= $this->expand;
        $surnameSoundex             = false;
		$selroles	        		= 0;		// bit mask of roles to include
		$range		        		= 1;		// default birth year range
        $surname                    = '';
        $byear                      = null;

		foreach($this->parms as $fldname => $value)
		{			            // translate field names and ignore empty
		    $fieldLc                    = strtolower($fldname);
		    switch($fieldLc)
		    {                   // act on specific field names
				case 'inchusband':
				{
				    $selroles	        |= 1;	// mask bit for grooms
				    break;
				}		        // include grooms in search

				case 'incwife':
				{
				    $selroles	        |= 2;	// mask bit for brides
				    break;
				}		        // include brides in search

				case 'incminister':
				{
				    $selroles	        |= 4;	// mask bit for ministers
				    break;
                }		        // include ministers in search
            }
        }
        if ($selroles == 0)
            $selroles                   = 3;

		// build WHERE clause
		foreach($this->parms as $fldname => $value)
		{			            // translate field names and ignore empty
            $fieldLc            = Marriage::getRealFieldName($fldname);
            if (is_null($fieldLc))
                $fieldLc        = MarriageParticipant::getRealFieldName($fldname);
            if (is_null($fieldLc))
                $fieldLc        = strtolower($fldname);
		    switch($fieldLc)
		    {                   // act on specific field names
				case 'regyear':
				{		        // numeric field
				    $regYear	    	= $value;
				    break;
				}		        // RegYear

				case 'regnum':
				{		        // RegNum
				    $regNum		        = $value;
				    break;
				}		        // RegNum

				case 'range':
				{		        // Marriage Year range
                    $needSpouse		    = true;
                    if (ctype_digit($value))
                        $value          = intval($value);
                    if (is_int($value) && $value >= 1 and $value <= 50)
						$range	        = $value;
				    else	
                        $warn	        .= "<p>MarriageSet::getWhere: " .
        "Birth Year range '$value' must be a number between 1 and 50.</p>\n";
				    break;
                }		        // Marriage Year range

				case 'surname':
				{               // surname
                    $needSpouse	        = true;
                    $surname            = $value;
				    $this->orderby	    = self::$nominalOrd;
                    $this->expand       = false;
				    break;
				}               // surname

                case 'givenname':
                    $fieldLc            = 'givennames';
                    $fldname            = 'GivenNames';
				case 'givennames':
				case 'occupation':
				case 'religion':
				case 'fathername':
				case 'mothername':
				case 'witnessname':
				case 'witnessres':
				case 'marriageplace':
				case 'remarks':
				case 'age':
				case 'marstat':
				{		        // match anywhere in string
				    $or		            = '';
				    $where		        .= "$and (";
				    if ($selroles & 1)
				    {
						$where	        .= "LOCATE(:$fieldLc, Groom.$fieldLc)>0 ";
						$this->sqlParms[$fieldLc]	= $value;
				        $or		            = ' OR ';
				    }
				    if ($selroles & 2)
				    {
						$where	        .= "$or LOCATE(:$fieldLc, Bride.$fieldLc)>0 ";
						$this->sqlParms[$fieldLc]	= $value;
				        $or		            = ' OR ';
				    }
				    if ($selroles & 4)
				    {
						$where	        .= "$or LOCATE(:$fieldLc, Minister.$fieldLc)>0";
						$this->sqlParms[$fieldLc]	= $value;
				    }
				    $or		    	    = ' OR ';
				    $where		        .= ') ';
				    $and		        = ' AND ';
				    $this->orderby	    = self::$nominalOrd;
                    $this->expand       = false;
				    break;
				}		        // match in string

				case 'place':
				case 'date':
				{		        // match anywhere in string
				    $where		        .= "$and LOCATE(:$fieldLc, Marriage.$fieldLc) > 0 ";
				    $this->sqlParms[$fieldLc]	= $value;
				    $and		        = ' AND ';
                    $this->orderby	    = self::$nominalOrd;
                    $this->expand       = false;
				    break;
				}		        // match in string

				case 'surnamesoundex':
				{		        // handled under Surname
                    $this->orderby	    = self::$nominalOrd;
                    $surnameSoundex     = true;
				    break;
				}		        // handled under Surname

				case 'byear':
                {		        // birth year
				    if (!is_int($value) && !ctype_digit($value))
				    {
						$msg	        .= "Birth Year must be a number. ";
				    }
				    else
				    if  (($value < 1700) || ($value > 3000))
				    {
						$msg	        .= "Birth Year out of range. ";
				    }
                    else
                    {
                        $byear          = $value;
                        $this->orderby	= self::$nominalOrd;
                    }
                    $this->expand       = false;
				    break;
				}		        // birth year

				case 'regdomain':
				{		        // registration domain
				    $this->regDomain	= $value;
				    $where		        .= "$and Marriage.RegDomain=:$fieldLc ";
				    $this->sqlParms[$fieldLc]	= $value;
				    $and		        = ' AND ';
				    break;
				}		        // registration domain

				case 'regcounty':
				case 'regtownship':
				case 'originalvolume':
				case 'originalpage':
				case 'originalitem':
				{		        // exact match on field in Marriage table
				    $needSpouse		    = true;
				    $where		        .= "$and Marriage.$fieldLc=:$fieldLc ";
				    $this->sqlParms[$fieldLc]	= $value;
				    $and		        = ' AND ';
				    $this->orderby	    = self::$nominalOrd;
                    $this->expand       = false;
				    break;
				}		        // exact match on field in Marriage table
		    }                   // act on specific field names
        }			            // translate field names and ignore empty

		if ($regYear)
		{		        // regyear
		    $where		                .= "$and Marriage.RegYear=:regyear ";
		    $this->sqlParms['regyear']	= $regYear;
            $and		                = ' AND ';
            $this->regYear              = $regYear;
		}		        // RegYear

		if ($regNum)
		{		        // RegNum
		    if (is_null($regYear))
		    {
				$warn	    	        .= '<p>MarriageSet::getWhere: ' .
						    	            __LINE__ . 
    ' Registration Number may only be specified with Registration Year.</p>';
		    }
		    else
		    {
				$where			        .= "$and Marriage.RegNum>=:regnum ";
				$this->sqlParms['regnum']	= $regNum;
				$and		    	    = ' AND ';
				if ($this->expand)
				{
                    $lastRegNum	    	= $regNum + $this->limit;
				    if ($regYear <= 1872 && $regNum > 10000)
                        $lastRegNum	    = $regNum +
                                          10 * floor($this->limit / 3 + 1);
				    $where		        .= "AND Marriage.RegNum<:lastregnum ";
				    $this->sqlParms['lastregnum']	= $lastRegNum;
                    $this->regNum       = $regNum;
                    $this->lastRegNum   = $lastRegNum;
				}
		    }
		}		        // RegNum

        // add check for surname
        if (strlen($surname) > 0)
        {                       // surname specified
            $surnameObj         = new Surname(array('surname' => $surname)); 
            $surnamePattern         = $surnameObj->get('pattern');
			$this->sqlParms['surname']	= $surname;
		    if (preg_match("/[.+*^$]/", $value))
		    {		            // match pattern
				$or			            = '';
				$where			        .= "$and (";
				if ($selroles & 1)
				{
				    $where		        .= "Groom.Surname REGEXP :surname";
				    $or		            = ' OR ';
				}
				if ($selroles & 2)
				{
				    $where	            .= "$or Bride.Surname REGEXP :surname";
				    $or		            = ' OR ';
				}
				if ($selroles & 4)
				{
				    $where	        .= "$or Minister.Surname REGEXP :surname";
				}
				$where		            .= ')';
				$and		            = ' AND ';
		    }		            // match pattern
		    else
		    if ($surnameSoundex && strlen($surnamePattern) > 0)
		    {		            // match pattern
				$or			            = '';
				$where		            .= "$and(";
			    $this->sqlParms['surname']	= $surnamePattern;
				if ($selroles & 1)
				{
				    $where		        .= "Groom.Surname REGEXP :surname";
				    $or		            = ' OR ';
				}
				if ($selroles & 2)
				{
				    $where	            .= "$or Bride.Surname REGEXP :surname";
				    $or		            = ' OR ';
				}
				if ($selroles & 4)
				{
				    $where	        .= "$or Minister.Surname REGEXP :surname";
				}
				$where		            .= ')';
				$and		            = ' AND ';
            }		            // match pattern
		    else
		    if ($surnameSoundex)
		    {		            // match soundex
				$or			            = '';
				$where		            .= "$and(";
				if ($selroles & 1)
				{
                    $where	    .=
                       "Groom.SurnameSoundex=LEFT(SOUNDEX(:surname),4)";
				    $or		    = ' OR ';
				}
				if ($selroles & 2)
				{
                    $where	    .= 
                       "$or Bride.SurnameSoundex=LEFT(SOUNDEX(:surname),4)";
				    $or		    = ' OR ';
				}
				if ($selroles & 4)
				{
                    $where	    .= 
                       "$or Minister.SurnameSoundex=LEFT(SOUNDEX(:surname),4)";
				    $or		    = ' OR ';
				}
				$where		    .= ') ';
				$and		    = ' AND ';
		    }		            // match soundex
		    else
		    {		            // match exact
				$or		        = '';
				$where		    .= "$and(";
				if ($selroles & 1)
				{
				    $where	    .= "Groom.Surname=:surname";
				    $or		    = ' OR ';
				}
				if ($selroles & 2)
				{
				    $where	    .= "$or Bride.Surname=:surname";
				    $or		    = ' OR ';
				}
				if ($selroles & 4)
				{
				    $where	    .= "$or Minister.Surname=:surname";
				    $or		    = ' OR ';
				}
				$where		    .= ')';
				$and		    = ' AND ';
		    }		            // match exact
        }                       // surname specified

        if ($byear)
	    {                       // birth year specified
			$or		            = '';
			$where	            .= "$and(";
			if ($selroles & 1)
			{
			    $where	        .= "ABS(Groom.BYear-:byear) < :range ";
			    $this->sqlParms['byear']	= $byear;
			    $this->sqlParms['range']	= $range;
			    $or		        = ' OR ';
			}
			if ($selroles & 2)
			{
			    $where	        .= "$or ABS(Bride.BYear-:byear) < :range";
			    $this->sqlParms['byear']	= $byear;
			    $this->sqlParms['range']	= $range;
			}
			$where		        .= ')';
			$and		        = ' AND ';
        }                       // birth year specified

		return $where;
    }		// function MarriageSet::getWhere

    /********************************************************************
     *	function MarriageSet::count										*
     *																	*
     *	Get the number of records in the set that can be iterated over	*
     *	or that would be deleted or updated.							*
     *  This can either by accessed by $set->count() or by count($set).	*
     *																	*
     *  Returns:														*
     *	    count of records											*
     ********************************************************************/
    public function count()
    {
        if ($this->expand)
		    return $this->limit;
		else 
		    return parent::count();
    }		// function MarriageSet::count

    /********************************************************************
     *	function MarriageSet::rewind									*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Reposition to the first element of the "array".					*
     *	The actual loading of the table is deferred until now so that	*
     *	an instance of RecordSet can be used to determine how many		*
     *	records are in the set even if the records themselves are not	*
     *	to be examined.													*
     *																	*
     *  Returns:														*
     *	    first instance of Record in the set							*
     ********************************************************************/
    public function rewind()
    {
		global	$debug;
		global	$warn;
		global	$msg;
        global	$connection;

        $first              = parent::rewind();
        if ($this->expand && count($this->list) < $this->limit)
        {
            $result         = array();
            $iter           = reset($this->list);
            $regNum         = $this->regNum;
            for ($count = 0; $count < $this->limit; $count++)
            {
                if ($iter)
                {           // $iter is an instance of Marriage
                    if ($iter->get('regnum') == $regNum)
                    {       // existing record
                        $result[]       = $iter;
                        $iter           = next($this->list);
                    }       // existing record
                    else
                    {       // create empty record
                        $createParms    = array('regdomain' => $this->regDomain,
                                                'regyear'   => $this->regYear,
                                                'regnum'    => $regNum);
                        $temp           = new Marriage($createParms);
                        $temp['g_idir'] = 0;
                        $temp['b_idir'] = 0;
                        $temp['m_idir'] = 0;
                        $result[]       = $temp;
                    }       // create empty record
                }           // $iter is an instance of Marriage
                else
                {           // past end of $this->list
                    $createParms        = array('regdomain' => $this->regDomain,
                                                'regyear'   => $this->regYear,
                                                'regnum'    => $regNum);
                    $temp               = new Marriage($createParms);
                    $temp['g_idir']     = 0;
                    $temp['b_idir']     = 0;
                    $temp['m_idir']     = 0;
                    $result[]           = $temp;
                }           // past end of $this->list
                $regNum++;
                if ($this->regYear <= 1872 && $regNum > 10000)
                {
                    $volume         = intdiv($regNum, 10000);
                    $page           = intdiv($regNum - ($volume * 10000), 10);
                    $item           = $regNum % 10;
                    if ($item > 3)
                    {
                        $item       = 1;
                        $page++;
                    }
                    $regNum         = $volume * 10000 + $page * 10 + $item;
                }
            }               // loop creating expanded list
            $this->list             = $result;
            $first                  = reset($this->list);
            $this->info['count']    = $this->limit;
        }
        
        return $first;
    }		// public function rewind()

    /********************************************************************
     *	function MarriageSet::getSummary								*
     *																	*
     *  Get year by year statistics for marriage registrations.			*
     *	This returns an array with one record per year.					*
     *																	*
     *	Example:														*
     *	    $marriages	= new MarriageSet(array('domain' => 'CAON'));	*
     *	    $result		= $marriages->getSummary();						*
     *																	*
     *	Returns:														*
     *		array each row of which is an array containing the		    *
     *		following values:										    *
     *		    0		year of registrations							*
     *		    1		number of marriage registrations transcribed	*
     *		    2		number of marriage registrations linked to tree	*
     ********************************************************************/
    function getSummary()
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// execute the query
		$query	= 'SELECT Marriage.RegYear, SUM(Groom.Surname != ""), SUM(Groom.IDIR > 0) ' .
                    'FROM Marriage ' . 
				      'LEFT JOIN MarriageIndi AS Groom ON ' .
						'Marriage.RegYear = Groom.RegYear AND ' .
						'Marriage.RegNum = Groom.RegNum AND ' .
						'Marriage.RegDomain = Groom.RegDomain AND ' .
						"Groom.Role = 'G' " .
                        $this->where .
						    ' GROUP BY RegYear ORDER BY RegYear';
		$sqlParmsText	    = print_r($this->sqlParms, true);
		$stmt		        = $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{			// query successful
		    $result	        = $stmt->fetchAll(PDO::FETCH_NUM);
		    if ($debug)
		    {			// debug output
				$warn	.= "<p>Marriage.inc: " . __LINE__ . "query='$query'</p>\n".
						   "<p>sqlParms=$sqlParmsText</p>\n";
		    }			// debug output
		}			// query successful
		else
		{			// query failed
		    $msg	    .= "query '$query' failed, sqlParms=$sqlParmsText: " .
						   print_r($stmt->errorInfo(), true);
		    $result	= null;
		}			// query failed
		return $result;
    }		// Marriage::getSummary

    /********************************************************************
     *	function Marriage::getStatistics								*
     *																	*
     *  Get statistics for specific year of marriage registrations.		*
     *																	*
     *	Example:														*
     *	    $marriages	= new MarriageSet(array('domain'	=> 'CAON',	*
     *									        'year'		=> 1887));	*
     *	    $result		= $marriages->getStatistics();					*
     *																	*
     *	Returns:														*
     *		An array each row of which is								*
     *		an array containing the following values for each		    *
     *		county in the domain:										*
     *				county		    county code						    *
     *				count	        number of transcribed records		*
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
		$query	= 'SELECT RegCounty AS County, ' .
						 'SUM(Date != "") AS `Count`,  ' .
						 'SUM(Idir != 0) AS LinkCount, ' .
						 'MIN(Marriage.RegNum) as low, ' .
						 'MAX(Marriage.RegNum) as high  ' .
                        'FROM Marriage ' .
                        'LEFT JOIN MarriageIndi ON ' .
                            "MarriageIndi.Role='G' AND " .
				            "Marriage.RegDomain=MarriageIndi.RegDomain AND " .
				            "Marriage.RegYear=MarriageIndi.RegYear AND " .
				            "Marriage.RegNum=MarriageIndi.RegNum " .
						$this->where .
						' GROUP BY RegCounty' .
						' ORDER BY RegCounty';
        $stmt	        = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);
		if ($stmt->execute($this->sqlParms))
		{		    // query succeeded
		    $result	    = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}		    // query succeeded
		else
		{		    // query failed
		    $msg	    .= "query '" . $queryText . "' failed: " .
					    	   print_r($stmt->errorInfo(), true);
		    return null;
		}		    // query failed
    }		// Marriage::getStatistics

    /********************************************************************
     *	function Marriage::getCountyStatistics							*
     *																	*
     *  Get statistics for specific year and county of marriage         *
     *  registrations.		                                            *
     *																	*
     *	Example:														*
     *	    $marriages	= new MarriageSet(array('domain'	=> 'CAON',	*
     *									        'year'		=> 1887,	*
     *									        'county'	=> 'Msx'));	*
     *	    $result		= $marriages->getCountyStatistics();			*
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
     ********************************************************************/
    function getCountyStatistics()
    {
		global	$debug;
		global	$warn;
		global	$msg;
        global	$connection;

		// execute the query
        $query	= 'SELECT RegCounty AS County,' .
                         'RegTownship AS Township, ' .
						 'SUM(Surname != "") AS `Count`,  ' .
						 'SUM(Idir != 0) AS LinkCount, ' .
						 'MIN(Marriage.RegNum) as low, ' .
                         'MAX(Marriage.RegNum) as high ' .
						'FROM Marriage ' .
                        'LEFT JOIN MarriageIndi ON ' .
                            "MarriageIndi.Role='G' AND " .
				            "Marriage.RegDomain=MarriageIndi.RegDomain AND " .
				            "Marriage.RegYear=MarriageIndi.RegYear AND " .
				            "Marriage.RegNum=MarriageIndi.RegNum " .
						$this->where .
						' GROUP BY RegCounty, RegTownship' .
                        ' ORDER BY RegCounty, RegTownship';
        $stmt	        = $connection->prepare($query);
        $queryText      = debugPrepQuery($query, $this->sqlParms);

		if ($stmt->execute($this->sqlParms))
		{		    // query succeeded
		    $result	    = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    return $result;
		}		    // query succeeded
		else
		{		    // query failed
		    $msg	    .= "query '$queryText' failed: " .
                               print_r($stmt->errorInfo(), true);
		    return null;
		}		    // query failed
    }		        // function Marriage::getCountyStatistics

}		// class MarriageSet 
