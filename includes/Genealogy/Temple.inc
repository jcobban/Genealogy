<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Temple.inc															*
 *																		*
 *  Definition of a class representing a temple of the Church of		*
 *  Latter Day Saints in a genealogical database.				 		*
 *  This class provides access to the information in a row of the		*
 *  table tblTR.														*
 *																		*
 *  Table: tblTR - Temple Name List										*
 *																		*
 *		IDTR				INT(10)				index					*
 *		Code				VARCHAR(6)		6 character id				*
 *		Code2				VARCHAR(6)		2 character id				*
 *		Temple				VARCHAR(50)		name						*
 *		Address				VARCHAR(255)	street address				*
 *		Used				TINYINT(3)								    *
 *		Tag1				TINYINT(3)								    *
 *		qsTag				TINYINT(3)								    *
 *		TempleStart			INT(10)										*
 *				(0=no date, YYYYMMDD=20050621) date temple opened		*
 *		TempleEnd			INT(10)										*
 *				(0=no date, YYYYMMDD=20050621) date temple closed		*
 *																		*
 *  History:															*
 *		2010/10/11		use explicit query code							*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global							*
 *	    2010/12/26	    add getPreposition method			            *
 *						add getNotes method								*
 *						eliminate internal member idtr					*
 *		2011/01/02		use regexp in construct by name					*
 *						throw Exception on construction failure			*
 *		2011/06/11		add methods getFirst and getNext				*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/05/13		correct comparison for abbreviations			*
 *						support optional 2-character abbreviations used	*
 *						by GEDCOM 5.5									*
 *		2012/08/15		add toString method								*
 *		2012/08/18		do not throw exception on IDTR 0				*
 *		2012/10/06		improve comments								*
 *		2012/10/20		standardize initialization of new record		*
 *		2012/12/08		change getName to only return name portion		*
 *						of string										*
 *		2013/02/23		implement changes to structure of tblTR record	*
 *						add methods getCode and getCode2				*
 *		2013/08/09		base class renamed to Record					*
 *		2014/09/30		add static methods getTemples and getWhere		*
 *						obsolete getFirst, getNext						*
 *						add method get to invoke class specific		    *
 *						handling of some fields							*
 *		2014/10/01		add method addOwner								*
 *		2014/10/16		do not access database to construct null temple	*
 *		2014/12/28		send trace information to $warn					*
 *		2015/06/27		return instance of LegacyDate from methods		*
 *						getStartDate and getEndDate						*
 *						accept text date to set for 'templestart'		*
 *						and 'templeend'									*
 *						add support for 'YYYY/MM/DD' numeric date		*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/08		base class changed to RecordPic					*
 *		2017/09/23		do not throw exception on new record			*
 *		2017/12/18		add support for temporary fields				*
 *		2019/05/14      do not throw exceptions in constructor or set   *
 *		                add $info                                       *
 *		2019/07/25      add street address                              *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';

/************************************************************************
 *  class Temple														*
 *																		*
 *  Definition of a class implementing behavior for temple records		*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class Temple extends RecordPic
{

    /********************************************************************
     *	Temple::$initRow												*
     *																	*
     *	array containing default values for a new record				*
     ********************************************************************/
    protected static	$initRow	= array(
									'idtr'			    => 0,
									'code'			    => '',
									'code2'			    => '',
									'temple'		    => '',
									'address'		    => '',
									'templestart'		=> 0,
									'templeend'		    => 99991231,	// never
									'used'			    => 0,
									'tag1'			    => 0,
									'qstag'			    => 0);

    /********************************************************************
     *	Templt::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
					                'table'     		=> 'tblTR',
					                'name'      		=> 'Temples',
					                'prime'	    		=> 'idtr',
						            'srvmg'     		=> true,
						            'fldcount'			=> 10,
					                'order'     		=> 'Temple',
			                        'classname' 		=> 'Temple');
			
	/********************************************************************
     *	Temple::$translate												*
     *																	*
     *	array containing alternate field names							*
     ********************************************************************/
    protected static	$translate	= array(
									'name'			    => 'temple',
									'latitude'		    => 'latitude',
									'longitude'		    => 'longitude',
									'preposition'		=> 'preposition');

    /********************************************************************
     *	Temple::$defaultOrder											*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	= 'Temple';

    /********************************************************************
     *	function Temple::__construct									*
     *																	*
     *  Given the identifier or database result construct an temple		*
     *	record.															*
     *																	*
     *  Input:															*
     *		$id			numeric identifier of a temple (key of tblTR),	*
     *					deprecated in favor of							*
     *					array('idtr' => numeric identifier), or			*
     *					array('code' => up to 5 character string code	*
     *					array('code2' => 2 character string code		*
     *					array('temple' => text within the name of 		*
     *							a temple,								*
     *							for example 'Toronto' or 'Alberta', but	*
     *							NOT 'Nauvoo'.  Case insensitive.		*
     *					or a database record presented as an array		*
     *					Note that some temple names include accented	*
     *							characters so, for example, 			*
     *							array('temple' => 'Panama') or			*
     *							array('temple' => 'Merida') won't work	*
     *							while									*
     *							array('code' => 'PANAM') and			*
     *							array('code' => 'MERID') will work		*
     *																	*
     *	Returns:														*
     *	    instance of Temple											*
     ********************************************************************/
    function __construct($parms = null)
    {
		global	$debug;
		global	$warn;
		global	$connection;

        $needInsert         		= true;

        // ensure that the parameter is translated to standard format
        if (is_null($parms))
            $parms          		= array();
        else
		if (is_string($parms))
		{
		    if (ctype_digit($parms))
				$parms				= array('idtr'		=> (int)$parms);
		    else
		    if (strlen($parms) == 0)
				$parms				= array('idtr'  	=> 1);
		    else
		    if (strlen($parms) == 2 && strcmp($parms, strtoupper($parms)) == 0)
		    {		// 2 character temple abbreviation
				$parms				= array('code2'		=> $parms);
		    }
		    else
		    if (strlen($parms) <= 5 && strcmp($parms, strtoupper($parms)) == 0)
		    {		// temple abbreviation
				$parms				= array('code'		=> $parms);
		    }		// temple abbreviation
		    else
		    {		// do pattern match on name
				$parms				= array('temple'	=> $parms);
		    }		// do pattern match
		}
		else
		if (is_int($parms))
		{		// numeric identifier of record
		    // construct the query of the temple table
		    if ($parms == 0)
				$parms				= array('idtr'		=> 1);
		    else
				$parms				= array('idtr'		=> $parms);
		}
        else
        if (!is_array($parms))
		    $this->msg  .= "Invalid parameter type " . gettype($id) .
							" parameter=" . print_r($id, true) . ". ";

		if (count($parms) >= count(self::$initRow))
		{			// existing record from database
		    $dbrow		    		= $parms;
		    $needInsert				= false;
		}			// existing record from databas
		else
		{			// search parameters
		    $where				    = '';
		    $and				    = 'WHERE ';
		    $sqlParms				= array();
		    foreach($parms as $field => $value)
		    {			// loop through search parms
				$fieldLc	        = strtolower($field);
        		if (array_key_exists($fieldLc, self::$translate))
        		    $fieldLc	    = self::$translate[$fieldLc];
				switch($fieldLc)
				{		// act on specific field names
				    case 'idtr':
				    case 'code':
				    case 'code2':
				    {		// act on specific fields
						$where	    .= $and . $field . '=:' . $fieldLc;
						$sqlParms[$fieldLc]	= $value;
						$and	    = ' AND ';
						break;
				    }		// act on specific fields

				    case 'temple':
				    {		// Temple Name match anywhere
						$where	    .= $and . "LOCATE(:$fieldLc, $field)>0";
						$sqlParms[$fieldLc]	= $value;
						$and	    = ' AND ';
						break;
				    }		// Temple Name match anywhere

				    default:
				    {		// unsupported field
						if ($debug)
						    $warn	.= "<p>Temple::__construct: unexpected parameter $field=>'$value'</p>\n";
						break;
				    }		// unsupported field
				}		// act on specific field names
		    }			// loop through search parms

		    $query	                = 'SELECT * FROM tblTR ' . $where .
						        		' ORDER BY IDTR LIMIT 1';
		    $stmt	                = $connection->prepare($query);
		    $queryText	            = debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {
				if ($debug)
				    $warn	        .= "<p>Temple::__construct: " . __LINE__ .
					        			" query='$queryText'</p>\n";
				$result		        = $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($result))
				{		// found matching temple record
				    $dbrow		    = $result;
				    $needInsert		= false;
				}		// found matching temple record
				else
				{		// no match
				    $dbrow		    = self::$initRow;
				    foreach($parms as $field => $value)
				    {			// loop through init parms
						$fieldLc	= strtolower($field);
						if (array_key_exists($fieldLc, $dbrow))
						    $dbrow[$fieldLc]	= $value;
				    }			// loop through init parms
				}		// no match
		    }
		    else
		    {		// error performing query
				$this->msg      .= "Temple::__construct: " .
								    "query='$queryText' " .
								    print_r($stmt->errorInfo(),true);
				$dbrow		    = self::$initRow;
		    }		// error performing query

		}		// search parameters

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblTR');
		$this->needInsert	        = $needInsert;

		// diagnostic output if debug is set
        $this->dump('Temple Record constructed:');

        if (strlen($this->msg) > 0)
            $msg                    .= $this->msg;
    }		// function Temple::__construct

    /********************************************************************
     *	function Temple::get											*
     *																	*
     *  Get the value of a field by name								*
     *																	*
     *	Parameters:														*
     *	    $field		field name										*
     *																	*
     *	Returns:														*
     *	    the value of the field										*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
		$fieldLc	        = strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	    = self::$translate[$fieldLc];
		switch($fieldLc)
		{			// act on specific field names
		    case 'idtr':
		    {
				return $this->getIdtr();
		    }

		    case 'temple':
		    {
				return $this->getName();
		    }

		    case 'code':
		    {
				return $this->getCode();
		    }

		    case 'code2':
		    {
				return $this->getCode2();
		    }

		    case 'preposition':
		    {
				return $this->getPreposition();
		    }

		    case 'notes':
		    {
				return $this->getNotes();
		    }

		    case 'used':
		    case 'tag1':
		    case 'qstag':
		    {
				return parent::get($field);
		    }

		    case 'templestart':
		    {
				return $this->getStartDate();
		    }

		    case 'templeend':
		    {
				return $this->getEndDate();
		    }

            case 'zoom':
                return 12;

            case 'latitude':
                return 0;

            case 'longitude':
                return 0;

		    default:
		    {
				return parent::get($field);
		    }
		}			// act on specific field names
    }		// function Temple::get

    /********************************************************************
     *	function Temple::getIdtr										*
     *																	*
     *	Get the unique numeric identifier of the temple.				*
     *																	*
     *	Returns:														*
     *	    integer identifier											*
     ********************************************************************/
    function getIdtr()
    {
		return $this->row['idtr'];
    }		// function Temple::getIdtr

    /********************************************************************
     *	function Temple::getName										*
     *																	*
     *	Get the name of the temple.										*
     *	The contents of the field 'temple' has been re-defined from		*
     *	the way it is used in Legacy.  This routine accepts the table	*
     *	in either format.												*
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function getName($options	= null)
    {
		$temple		= $this->row['temple'];
		if (strlen($temple) > 8)
		{
		    // this explode will not find the separator string with the
		    // new record structure
		    $parts	= explode(' - ', $temple, 2);
		    if (count($parts) >= 2)
				return $parts[1];
		    else
				return $temple;
		}
		else
		    return $temple;
    }		// function Temple::getName

    /********************************************************************
     *	function Temple::getCode										*
     *																	*
     *	Get the abbreviation code for the temple.						*
     *	Even though this function should not be deployed against the	*
     *	old record structure it is written so it will not fail          *
     *	if it is.	                                                    *
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function getCode()
    {
		if (array_key_exists('code', $this->row))
		    return $this->row['code'];
		$temple		= $this->row['temple'];
		if (strlen($temple) > 8)
		{
		    // this explode will not find the separator string with the
		    // new record structure
		    $parts	= explode(' - ', $temple, 2);
		    if (count($parts) >= 2)
				return $parts[0];
		}
		return $temple;
    }		// function Temple::getCode

    /********************************************************************
     *	function Temple::getCode2										*
     *																	*
     *	Get the short abbreviation code for the temple.					*
     *	Even though this function should not be deployed against the	*
     *	old record structure it is written so it will not fail          *
     *	if it is.	                                                    *
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function getCode2()
    {
		if (array_key_exists('code2', $this->row))
		    return $this->row['code2'];
		return '';
    }		// function Temple::getCode2

    /********************************************************************
     *	function Temple::getPreposition									*
     *																	*
     *	Get the default preposition for the temple.						*
     *	This method exists so that the external interface to an instance*
     *  of Temple looks as much as possible like the external			*
     *	interface to an instance of class Location.						*
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function getPreposition()
    {
		return 'at';
    }		// function Temple::getPreposition

    /********************************************************************
     *	function Temple::getNotes										*
     *																	*
     *	Simulate getting the notes portion of the temple.				*
     *	This method exists so that the external interface to an instance*
     *  of Temple looks as much as possible like the external			*
     *	interface to an instance of Location.  However there are		*
     *	no notes associated with a temple.								*
     *																	*
     *	Returns:														*
     *	    empty string												*
     ********************************************************************/
    function getNotes()
    {
		return '';
    }		// function Temple::getNotes

    /********************************************************************
     *	function Temple::toString										*
     *																	*
     *	Get the description of the temple as a string.					*
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function toString()
    {
		return $this->row['temple'];
    }		// function Temple::toString

    /********************************************************************
     *	function Temple::__toString										*
     *																	*
     *	Permit PHP to convert an instance to a string.					*
     *																	*
     *	Returns:														*
     *	    string														*
     ********************************************************************/
    function __toString()
    {
		return $this->row['temple'];
    }		// function Temple::__toString

    /********************************************************************
     *	function Temple::getStartDate									*
     *																	*
     *	Get the start date of the temple.								*
     *																	*
     *	Returns:														*
     *	    instance of LegacyDate										*
     ********************************************************************/
    function getStartDate()
    {
		$yyyymmdd	            = parent::get('templestart');
		if (strlen($yyyymmdd) == 8)
		    return new LegacyDate(' ' . substr($yyyymmdd, 0, 4) .
							  '/' . substr($yyyymmdd, 4, 2) .
							  '/' . substr($yyyymmdd, 6, 2));
		else
		    return new LegacyDate('');
    }		// function Temple::getStartDate

    /********************************************************************
     *	function Temple::getEndDate										*
     *																	*
     *	Get the end date of the temple.									*
     *																	*
     *	Returns:														*
     *	    instance of LegacyDate										*
     ********************************************************************/
    function getEndDate()
    {
		$yyyymmdd	            = parent::get('templeend');
		if (strlen($yyyymmdd) == 8)
		    return new LegacyDate(' ' . substr($yyyymmdd, 0, 4) .
							  '/' . substr($yyyymmdd, 4, 2) .
							  '/' . substr($yyyymmdd, 6, 2));
		else
		    return new LegacyDate('');
    }		// function Temple::getEndDate

    /********************************************************************
     *	function Temple::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		value to set it to								*
     *																	*
     *	Returns:														*
     *	    Former value of field										*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *		or pseudo-field and returns null.							*
     ********************************************************************/
    function set($field, $value)
    {
        global  $msg;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'idtr':
		    {			// primary key
                $msg    .= "Temple::set: Cannot alter key of record'. ";
                return $this->row['idtr'];
		    }			// primary key

		    case 'code':
		    {			// abbreviation
				$value		= strtoupper($value);
				if (strlen($value) <= 5)
                    return parent::set($field, $value);
                else
				    $msg    .= "Temple: set: " .
                                "invalid value '$value' for field '$field'. ";
                return $this->row['code'];
		    }			// abbreviation

		    case 'code2':
		    {			// short abbreviation
				$value		= strtoupper($value);
				if (strlen($value) == 2)
                    return parent::set($field, $value);
                else
				    $msg    .= "Temple: set: " .
							"invalid value '$value' for field '$field'";
                return $this->row['code2'];
		    }			// short abbreviation

		    case 'name':
		    case 'temple':
		    {			// temple name
				return parent::set('temple', $value);
		    }			// temple name

		    case 'templestart':
		    case 'templeend':
		    {			// internal date format
				if (strlen($value) == 0 || $value == 0)
				    return parent::set($field, 0);
				if (is_int($value) || ctype_digit($value))
				{		// completely numeric value
				    if (strlen($value) == 8)
				    {		// YYYYMMDD
						return parent::set($field, $value);
				    }		// YYYYMMDD
				    else
				    {		// internal format
						$date	= new LegacyDate($value);
				    }		// internal format
				}		// completely numeric value
				else
				    $date	= new LegacyDate(' ' . $value);
				return parent::set($field, $date->getSortDate());
		    }			// internal date format

		    case 'used':
		    case 'tag1':
		    case 'qstag':
		    {			// yes or no flags
				if ($value == 0 || $value == 1)
                    return parent::set($field, intval($value));
                else
				    $msg    .= "Temple: set: " .
                    "Invalid value '$value' for field '$field'. ";
                return $this->row[$field];
		    }			// yes or no flags

		    default:
		    {			// unexpected field name
				return parent::set($field, $value);
		    }			// unexpected field name
		}			// switch on field name
    }		// function Temple::set

}		// class Temple

