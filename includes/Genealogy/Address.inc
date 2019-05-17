<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Address.inc															*
 *																		*
 *  Definition of a class representing an address or repository in 		*
 *  genealogical database.												*
 *  This class provides access to the information in a row of the		*
 *  table tblAR.														*
 *																		*
 *  Record Structure Reference											*
 *		Name			Type											*
 *		IDAR			INTEGER(10)										*
 *				used by: tblIR.IDARBirth, tblIR.IDARChris,				*
 *				tblIR.IDARDeath, tblIR.IDARBuried, tblIR.IDAR,			*
 *				tblMR.IDAR, tblER.IDAR, tblSR.IDAR, tblTD.IDAR			*
 *		Kind			TINYINT(3) 0=mailing, 1=event, 2=repository		*
 *		Style			TINYINT(3) 0=USA, 1=European					*
 *		AddrName		VARCHAR(255)									*
 *		AddrSort		VARCHAR(255)									*
 *		Address1		VARCHAR(50)										*
 *		Address2		VARCHAR(50)										*
 *		City			VARCHAR(50)										*
 *		State			VARCHAR(50)										*
 *		ZipCode			VARCHAR(50)										*
 *		Country			VARCHAR(50)										*
 *		Phone1			VARCHAR(50)										*
 *		Phone2			VARCHAR(50)										*
 *		Email			VARCHAR(255)									*
 *		HomePage		VARCHAR(255)									*
 *		AddrNotes		LONGTEXT,				-						*
 *		Latitude		Double											*
 *		Longitude		Double											*
 *		List1			TINYINT(3) 0=no, 1=yes							*
 *		List2			TINYINT(3) 0=no, 1=yes							*
 *		List3			TINYINT(3) 0=no, 1=yes							*
 *		List4			TINYINT(3) 0=no, 1=yes							*
 *		List5			TINYINT(3) 0=no, 1=yes							*
 *		List6			TINYINT(3) 0=no, 1=yes							*
 *		Private			TINYINT(3) 0=no, 1=yes							*
 *		Tag1			TINYINT(3) 0=no, 1=yes							*
 *		Used			TINYINT(3) 0=no, 1=yes							*
 *		FSResolved		TINYINT(3) 0=no, 1=yes, 2=could not be resolved	*
 *		VEResolved		TINYINT(3) 0=no, 1=yes, 2=could not be resolved	*
 *		Verified		TINYINT(3) 0=no, 1=yes							*
 *		qsTag			TINYINT(3) 0=no, 1=yes							*
 *																		*
 *  History:															*
 *		2010/09/30		explicitly set case for field names				*
 *						require get function access to IDAR and Kind	*
 *						support search for address by name				*
 *		2010/10/11		use explicit query code							*
 *		2010/10/19		Ensure that textual get methods always return	*
 *						a string										*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global							*
 *		2010/12/02		accept numeric string as key					*
 *		2010/12/05		clear needInsert if record found in databas		*
 *						return instance with lowest IDAR if multipl		*
 *						name matches									*
 *						add method setKind								*
 *		2010/12/08		do not accept key of 0							*
 *		2011/01/12		throw an exception on bad parameters to the		*
 *						constructor										*
 *		2012/01/14		change class name to LegacyAddress				*
 *		2012/01/28		initialize more fields in new record			*
 *		2012/04/21		initialize all fields in new record				*
 *		2012/10/20		standardize initialization of new record		*
 *		2013/02/21		correct field names in initial record			*
 *						add second field to constructor for kind		*
 *						add method getZoom for Google Maps support		*
 *						add method getPreposition for symmetry with		*
 *						LegacyLocation									*
 *						add method getLatitude for symmetry with		*
 *						LegacyLocation									*
 *						add method getLongitude for symmetry with		*
 *						LegacyLocation									*
 *						add method setLatitude for symmetry with		*
 *						LegacyLocation									*
 *						add method setLongitude for symmetry with		*
 *						LegacyLocation									*
 *		2013/03/09		add method toString for symmetry with Location	*
 *						add method __toString for symmetry with Location*
 *						add method setField to ensure proper handling	*
 *						of longitude and latitude						*
 *						honour private flag								*
 *		2013/08/09		base class renamed to Record					*
 *		2013/09/23		missing $ in reference to $value on				*
 *						lines 549,589									*
 *		2014/08/28		add static method mergeAddresses				*
 *		2014/12/28		redirect diagnostic output to $warn				*
 *		2015/02/13		support associative array parameter to			*
 *						constructor										*
 *		2015/05/27		add static methods getWhere, getAddresses,		*
 *						deleteAddresses, updateAddresses				*
 *						change mergeAddresses to be a normal method		*
 *						and to use the update methods of the various	*
 *						classes which reference Addresses				*
 *						do not create default new record if IDAR		*
 *						passed to constructor							*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						support all fields in getWhere					*
 *						support array parameter to integer fields in	*
 *						getWhere										*
 *		2015/12/08		base class changed to RecordPic					*
 *		2017/07/30		change class LegacySource to class Source		*
 *		2017/08/04		change class LegacyAddress to class Address		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/23		reorganize include structure					*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/09/30		complete conversion to PDO prepared statements	*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/10/16		static functions replaced by use of RecordSet	*
 *		2017/11/27		add method get									*
 *		2017/12/18		add support for temporary fields				*
 *		2018/11/06      do not throw exception for invalid IDAR         *
 *		2019/01/05      do not thrown any exceptions from constructor   *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/01/18      method mergeAddresses used the obsolete         *
 *		                Address::deleteAddresses instead of             *
 *		                RecordSet->delete                               *
 *		2019/04/18      support method getLastSqlCmd                    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Source.inc';

/************************************************************************
 *  Address																*
 *																		*
 *  Definition of a class implementing behavior for address records		*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class Address extends RecordPic
{
    /********************************************************************
     *	symbolic constants for the kind of address record				*
     ********************************************************************/
    const MAILING	    = 0;
    const EVENT		    = 1;
    const REPOSITORY	= 2;
    const MAXKIND	    = 2;

    /********************************************************************
     *	Address::$defaultOrder											*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static	$defaultOrder	= 'addrname';

    /********************************************************************
     *	Address::$initRow												*
     *																	*
     *	static array containing default values for all fields in a		*
     *	new record														*
     ********************************************************************/
    protected static	$initRow	= array(
				'idar'				=> 0,
				'kind'				=> Address::REPOSITORY,
				'style'				=> 0,
				'addrname'			=> '',
				'addrsort'			=> '',
				'address1'			=> '',
				'address2'			=> '',
				'city'				=> '',
				'state'				=> '',
				'zipcode'			=> '',
				'country'			=> '',
				'phone1'			=> '',
				'phone2'			=> '',
				'email'				=> '',
				'homepage'			=> '',
				'addrnotes'			=> '',
				'latitude'			=> 0,
				'longitude'			=> 0,
				'list1'				=> 0,
				'list2'				=> 0,
				'list3'				=> 0,
				'list4'				=> 0,
				'list5'				=> 0,
				'list6'				=> 0,
				'private'			=> 0,
				'tag1'				=> 0,
				'used'				=> 0,
				'fsresolved'		=> 0,
				'veresolved'		=> 0,
				'verified'			=> 0,
				'qstag'				=> 0);

    /********************************************************************
     *	Address::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'tblAR',
                'name'      		=> 'Addresses',
                'prime'	    		=> 'idar',
	            'srvmg'     		=> true,
	            'fldcount'			=> 4,
                'order'     		=> 'B_RegDomain, B_RegYear, B_RegNum',
                'classname' 		=> 'Address');

    /********************************************************************
     *	Address::$translate												*
     *																	*
     *	array containing synonyms for field names						*
     ********************************************************************/
    protected static	$translate	= array(
				'id'		=> 'idar',
				'name'		=> 'addrname',
				'sort'		=> 'addrsort');

    /********************************************************************
     *	function Address::__construct									*
     *																	*
     *  Construct an instance of an address record from tblAR.			*
     *																	*
     *  Input:															*
     *		$id			identifier of an address (key of tblAR)			*
     *					deprecated, use:								*
     *					  array('idar'	    	=> $idar)				*
     *					or the name of an existing record				*
     *					deprecated, use:								*
     *					  array('addrname'		=> $name)				*
     *					or the name of a new record to create			*
     *					deprecated, use:								*
     *					  array('addrname'		=> $newname)			*
     *					or a database record presented as an array		*
     *					or an associative array of search parameters	*
     *		$newkind	if $id is a string then this parameter			*
     *					indicates the kind of address to limit matches	*
     *					on the name										*
     *					deprecated, use:								*
     *					  array('addrname'		=> $newname,			*
     *							'kind'			=> $newkind)			*
     *																	*
     *  Returns object containing address record.						*
     ********************************************************************/
    function __construct($id, $newkind = null)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$needInsert	= false;

		$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);

		// check for deprecated parameter formats and convert to array
		if (is_string($id) && !is_null($newkind))
		{		// deprecated parameter format
		    $parms		= array('addrname'	=> $id,
							    'kind'		=> $newkind);
		}		// deprecated parameter format
		else
		if ((is_int($id) || ctype_digit($id)) && ($id > 0))
		{		// numeric identifier of existing record
		    $parms		= array('idar'		=> intval($id));
		}		// numeric identifier of existing record
		else
		if (is_string($id))
		{		// non-numeric, name of address
		    $parms		= array('addrname'	=> $id);
		}		// non-numeric, name of address
		else
		if (is_array($id))
        {		// array
            $parms      = $id;
		}		// array
		else
		{		                    // unexpected parameter type
		    $this->msg  .= "Address::__construct: called with " .
                                        gettype($id) . ". ";
            $parms          = array();
		}		                    // unexpected parameter type

		// report parameter validation errors
		if (count($parms) >= count(Address::$initRow))
		{			                // complete row from database
		    $dbrow		    = $parms;
		    $where		    = '';
		    $needInsert		= $dbrow['idar'] == 0;
		}			                // complete row from database
        else
        {                           // process associative array parameter
			$where			= '';
			$sqlParms		= array();
			$and			= 'WHERE ';
			foreach($parms as $fieldname => $value)
			{		        	    // loop through search parameters
			    $fieldNameLc	= strtolower($fieldname);
			    switch($fieldNameLc)
			    {
					case 'idar':
					{
					    if (is_string($value) && ctype_digit($value))
							$value		= intval($value);
	                    if (is_int($value) && $value > 0)
	                    {
							$idar		= $value;
					        $where		.= $and . "`IDAR`=:idar";
					        $sqlParms['idar']		= $idar;
					        $and		= ' AND ';
	                    }
					    else
					    {
							$msg	.= "`IDAR` value '$value' invalid. ";
							$idar	= 0;
					    }
					    break;
					}
	
	                case 'kind':
	                {               // kind
	                    $kind           = $value;
			            if ($kind == Address::MAILING || 
					        $kind == Address::EVENT ||
	                        $kind == Address::REPOSITORY)
	                    {
					        $where		.= $and . "`$fieldname`=:$fieldNameLc";
					        $sqlParms[$fieldNameLc]	= $value;
					        $and		= ' AND ';
	                    }
	                    else
					        $msg        .= "`kind` invalid value='$kind'";
					    break;
	                }               // kind
	
					case 'addrname':
					case 'addrsort':
					case 'email':
					case 'homepage':
					{               // identification fields
					    $where		.= $and . "`$fieldname`=:$fieldNameLc";
					    $sqlParms[$fieldNameLc]	= $value;
					    $and		= ' AND ';
					    break;
					}               // identification fields
	
					case 'style':
					case 'address1':
					case 'address2':
					case 'city':
					case 'state':
					case 'zipcode':
					case 'country':
					case 'phone1':
					case 'phone2':
					case 'addrnotes':
					case 'latitude':
					case 'longitude':
					case 'list1':
					case 'list2':
					case 'list3':
					case 'list4':
					case 'list5':
					case 'list6':
					case 'private':
					case 'tag1':
					case 'used':
					case 'fsresolved':
					case 'veresolved':
					case 'verified':
					case 'qstag':
					{               // not identification fields
					    break;
	                }               // not identification fields
	
					default:
					{
					    $msg	.= "Field Name `$fieldname` not supported. ";
					    break;
					}
			    }			        // act on specific field names
			}		        	    // loop through search parameters
	
			if (strlen($where) > 0)
			{	// obtain the first instance that matches search parameters
			    $query		        = "SELECT * FROM tblAR $where";
	
			    // query the database
			    $stmt		        = $connection->prepare($query);
				$queryText	        = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
			    if ($stmt->execute($sqlParms))
			    {			// successful query
					$row		= $stmt->fetch(PDO::FETCH_ASSOC);
					if ($debug)
					    $warn	.= "<p>Address: " . __LINE__ .
										  "'$queryText'</p>\n";
	
					if (is_array($row))
					{		        // found address record
					    $dbrow		    = $row;
					    $needInsert		= false;
					}		        // found address record
					else
					{		        // create new record
	                    if (!array_key_exists('kind', $parms))
	                        $parms['kind']  = Address::REPOSITORY;
					    $dbrow		        = Address::$initRow;
					    foreach($parms as $fieldname => $value)
					    {		    // apply updates
							$dbrow[strtolower($fieldname)]	= $value;
					    }		    // apply updates
					    $idar		        = $dbrow['idar'];
					    if ($idar > 1 && $dbrow['addrname'] == '')
							$dbrow['addrname']	= 'Lost Record ' . $idar;
					    $needInsert	        = true;
					}		        // create new record
			    }			        // successful query
			    else
			    {			        // query failed
					$this->msg  .= "Address::__construct: '$queryText', " .
										print_r($stmt->errorInfo(), true) .". ";
				    $dbrow		        = Address::$initRow;
			    }		            // error performing query
            }		                // query using search parms
            else
            {
				$this->msg .= "Address::__construct: no selection parameters. ";
				$dbrow		        = Address::$initRow;
            }
        }                           // process associative array parameter

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblAR');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('Address Record constructed:');

        $msg    .= $this->msg;
    }		// Address::__construct

    /********************************************************************
     *	function Address::getIdar										*
     *																	*
     *	Get the unique numeric key of the address.						*
     ********************************************************************/
    function getIdar()
    {
		if ($this->needInsert)
		    $this->save(false);
		return $this->row['idar'];
    }		// Address::getIdar

    /********************************************************************
     *	function Address::getKind										*
     *																	*
     *	Get the kind of the address.									*
     ********************************************************************/
    function getKind()
    {
		return $this->row['kind'];
    }		// Address::getKind

    /********************************************************************
     *	function Address::setKind										*
     *																	*
     *	Set the kind of the address.									*
     ********************************************************************/
    function setKind($newkind)
    {
		if ($newkind >= 0 && $newkind <= Address::MAXKIND)
		    parent::set('kind', $newkind);
    }		// Address::getKind

    /********************************************************************
     *	function Address::toString										*
     *																	*
     *	For symmetry with Location return a string representation		*
     *	of the address.													*
     *																	*
     *	Returns:														*
     *	    String form of address.										*
     ********************************************************************/
    function toString()
    {
		if (($this->row['private'] || $this->row['kind'] == 0) && 
		    !$this->isOwner())
		    return "private";
		$location	= '';
		$address1	= $this->row['address1'];
		if (strlen($address1) > 0)
		    $location	.= $address1 . ', ';
		$address2	= $this->row['address2'];
		if (strlen($address2) > 0)
		    $location	.= $address2 . ', ';
		$city		= $this->row['city'];
		if (strlen($city) > 0)
		    $location	.= $city . ', ';
		$state		= $this->row['state'];
		if (strlen($state) > 0)
		    $location	.= $state . ', ';
		$country	= $this->row['country'];
		if (strlen($country) > 0)
		    $location	.= $country;
		else
		    $location	.= 'USA';
		return $location;
    }		// Address::toString

    /********************************************************************
     *	function Address::__toString									*
     *																	*
     *	For symmetry with Location return a string representation		*
     *	of the address.													*
     *																	*
     *	Returns:														*
     *	    String form of address.										*
     ********************************************************************/
    function __toString()
    {
		return $this->toString();
    }		// Address::__toString

    /********************************************************************
     *	function Address::getName										*
     *																	*
     *	Get the name of the address.									*
     ********************************************************************/
    function getName($options	= null)
    {
		$retval	= $this->row['addrname'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getName

    /********************************************************************
     *	function Address::getAddress1									*
     *																	*
     *	Get the first line of the address.								*
     ********************************************************************/
    function getAddress1()
    {
		$retval	= $this->row['address1'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getAddress1

    /********************************************************************
     *	function Address::getAddress2									*
     *																	*
     *	Get the second line of the address.								*
     ********************************************************************/
    function getAddress2()
    {
		$retval	= $this->row['address2'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getAddress2

    /********************************************************************
     *	function Address::getCity										*
     *																	*
     *	Get the city portion of the address.							*
     ********************************************************************/
    function getCity()
    {
		$retval	= $this->row['city'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getCity

    /********************************************************************
     *	function Address::getState										*
     *																	*
     *	Get the state/province portion of the address.					*
     ********************************************************************/
    function getState()
    {
		$retval	= $this->row['state'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getState

    /********************************************************************
     *	function Address::getPostalCode									*
     *																	*
     *	Get the postal code portion of the address.						*
     ********************************************************************/
    function getPostalCode()
    {
		$retval	= $this->row['zipcode'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getPostalCode

    /********************************************************************
     *	function Address::getCountry									*
     *																	*
     *	Get the country portion of the address.							*
     ********************************************************************/
    function getCountry()
    {
		$retval	= $this->row['country'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getCountry

    /********************************************************************
     *	function Address::getHomePage									*
     *																	*
     *	Get the home web page portion of the address.					*
     ********************************************************************/
    function getHomePage()
    {
		$retval	= $this->row['homepage'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getHomePage

    /********************************************************************
     *	function Address::getNotes										*
     *																	*
     *	Get the descriptive notes about the address.					*
     ********************************************************************/
    function getNotes()
    {
		$retval	= $this->row['addrnotes'];
		if ($retval === null)
		    $retval	= '';
		return $retval;
    }		// Address::getNotes

    /********************************************************************
     *	function Address::getLatitude									*
     *																	*
     *	Get the latitude portion of the address.						*
     *																	*
     *  Returns:														*
     *	    latitude in format xDDMMSS.sss where:						*
     *			x		is '-' for southern latitudes, omitted for		*
     *					northern latitudes								*
     *			DD		is the degree portion of the latitude			*
     *			MM		is the minutes portion of the latitude			*
     *			SS.sss	is the seconds portion of the latitude			*
     ********************************************************************/
    function getLatitude()
    {
		return $this->row['latitude'];
    }		// Address::getLatitude

    /********************************************************************
     *	function Address::getLongitude									*
     *																	*
     *	Get the longitude portion of the address.						*
     *																	*
     *  Returns:														*
     *	    longitude in format xDDMMSS.sss where:						*
     *			x		is '-' for western longitudes (New World), 		*
     *					omitted for eastern longitudes (Old World)		*
     *			DD		is the degree portion of the longitude			*
     *			MM		is the minutes portion of the longitude			*
     *			SS.sss	is the seconds portion of the longitude			*
     ********************************************************************/
    function getLongitude()
    {
		return $this->row['longitude'];
    }		// Address::getLongitude

    /********************************************************************
     *	function Address::getZoom										*
     *																	*
     *	Get the map zoom level.											*
     *																	*
     *  Returns:														*
     *	    Google Maps zoom level										*
     ********************************************************************/
    function getZoom()
    {
		if (array_key_exists('zoom', $this->row))
		    return $this->row['zoom'];
		else
		    return 12;
    }		// Address::getZoom

    /********************************************************************
     *	function Address::getPreposition								*
     *																	*
     *	Get the preposition portion of the address.						*
     ********************************************************************/
    function getPreposition()
    {
		if (array_key_exists('preposition', $this->row))
		    return $this->row['preposition'];
		else
		    return 'at';
    }		// Address::getPreposition

    /********************************************************************
     *	function Address::setLatitude									*
     *																	*
     *	Set the latitude portion of the address.						*
     *																	*
     *  Input:															*
     *	    value		latitude in format xDDMMSS.sss or xDD.dddd		*
     *					where:											*
     *			x		is '-' for southern latitudes, omitted for		*
     *					northern latitudes								*
     *			DD		is the integral degree portion of the latitude	*
     *			DD.dddd	is the latitude in degrees and decimal fraction	*
     *			MM		is the minutes portion of the latitude			*
     *			SS.sss	is the seconds portion of the latitude			*
     *					The long format applies if the value is greater	*
     *					than 360.  Do not use the long format for		*
     *					latitudes within 4 minutes of the equator.		*
     *					The short format is supported because it is		*
     *					used by web geo-location applications.			*
     *																	*
     *	Returns:														*
     *	    Former value in format xDDMMSS.sss 							*
     ********************************************************************/
    function setLatitude($value)
    {
		$value	= (double)$value;
		if (abs($value) < 360.0)
		{		// geo-locator format
		    $negative	= $value < 0;
		    $value	= abs($value);
		    $ddd	= floor($value);
		    $value	= ($value - $ddd) * 60.0;
		    $mm		= floor($value);
		    $ss		= ($value - $mm) * 60.0;
		    $value	= ($ddd * 10000) + ($mm * 100) + $ss;
		    if ($negative)
				$value	= -$value;
		}		// geo-locator format
		return parent::set('latitude', $value);
    }		// Address::setLatitude

    /********************************************************************
     *	function Address::setLongitude									*
     *																	*
     *	Set the longitude portion of the address.						*
     *																	*
     *  Input:															*
     *	    value		longitude in format xDDDMMSS.sss or xDDD.dddd	*
     *					where:											*
     *			x		is '-' for western longitudes (New World), 		*
     *					omitted for eastern longitudes (Old World)		*
     *			DDD		is the integral degree portion of the longitude	*
     *			DDD.dddd is the longitude in degrees and decimal frac	*
     *			MM		is the minutes portion of the longitude			*
     *			SS.sss	is the seconds portion of the longitude			*
     *					The long format applies if the value is greater	*
     *					than 360.  Do not use the long format for		*
     *					values within 4 minutes of the Greenwich		*
     *					meridian.										*
     *					The short format is supported because it is		*
     *					used by web geo-location applications.			*
     *																	*
     *	Returns:														*
     *	    Former value in format xDDMMSS.sss 							*
     ********************************************************************/
    function setLongitude($value)
    {
		$value	= (double)$value;
		if (abs($value) < 360.0)
		{		// geo-locator format
		    $negative	= $value < 0;
		    $value	= abs($value);
		    $ddd	= floor($value);
		    $value	= ($value - $ddd) * 60.0;
		    $mm		= floor($value);
		    $ss		= ($value - $mm) * 60.0;
		    $value	= ($ddd * 10000) + ($mm * 100) + $ss;
		    if ($negative)
				$value	= -$value;
		}		// geo-locator format
		return parent::set('longitude', $value);
    }		// Address::setLongitude

    /********************************************************************
     *	function Address::get											*
     *																	*
     *	Get the value of a field in the object.							*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to get value of					*
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
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'latitude':
		    {	
				return $this->getLatitude();
				break;
		    }		// latitude

		    case 'longitude':
		    {
				return $this->getLongitude();
				break;
		    }		// longitude

		    case 'zoom':
		    {
				return $this->getZoom();
				break;
		    }		// zoom

		    case 'preposition':
		    {
				return $this->getPreposition();
				break;
		    }		// preposition

		    default:
		    {		// other fields
				return parent::get($fieldLc);
		    }		// other fields
		}		// switch on field name
    }		// Address::get

    /********************************************************************
     *	function Address::set											*
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
     *			previous value of the field								*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'latitude':
		    {	
				return $this->setLatitude($value);
		    }		// latitude

		    case 'longitude':
		    {
				return $this->setLongitude($value);
		    }		// longitude

		    case 'kind':
		    {
				return $this->setKind($value);
		    }		// zoom

		    default:
		    {		// other field names
				return parent::set($fieldLc, $value);
		    }		// other fields
		}		// switch on field name
    }		// Address::set

    /********************************************************************
     *	function Address::mergeAddresses								*
     *																	*
     *  Change all references to a particular set of addresses			*
     *	to use the current address instead.								*
     *																	*
     *  Parameters:														*
     *	    $from		array of old address IDAR values				*
     ********************************************************************/
    function mergeAddresses($from)
    {
		global		$connection;
		global		$msg;
		$kind		= $this->row['kind'];
		$to		    = $this->row['idar'];

		// update references by table and field
		if ($kind == Address::MAILING)
		{		// mailing
		    $getParms	= array('IDAR' => $from);
		    Person::updatePersons($getParms,
								      array('IDAR' => $to),
								      'p');
		}		// mailing
		else
		if ($kind == Address::EVENT)
		{		// event
		    $getParms	= array('IDARBirth' => $from);
		    Person::updatePersons($getParms,
								  array('IDARBirth' => $to),
								  'p');
		    $getParms	= array('IDARChris' => $from);
		    Person::updatePersons($getParms, 
								  array('IDARChris' => $to),
								  'p');
		    $getParms	= array('IDARDeath' => $from);
		    Person::updatePersons($getParms, 
								  array('IDARDeath' => $to),
								  'p');
		    $getParms	= array('IDARBuried' => $from);
		    Person::updatePersons($getParms, 
								  array('IDARBuried' => $to),
								  'p');
		    $getParms	= array('IDAR' => $from);
		    Event::updateEvents($getParms,
								array('IDAR' => $to),
								'p');
		    Family::updateFamilies($getParms,
								   array('IDAR' => $to),
								   'p');
		}		// event
		else
		if ($kind == Address::REPOSITORY)
		{		// repository
		    $getParms	= array('IDAR' => $from);
		    Source::updateSources($getParms,
								  array('IDAR' => $to),
								  'p');
		    $getParms	= array('IDAR2' => $from);
		    Source::updateSources($getParms,
								  array('IDAR2' => $to),
								  'p');
		    $getParms	= array('IDAR' => $from);
		    // not implemented yet
		    // LegacyTodo::updateTodos($getParms,
		    //			    array('IDAR' => $to),
		    //			    'p');
		}		// repository

		// delete the now redundant address record[s]
        $addresses  = new RecordSet('Addresses',
                                    array('IDAR' => $from));
		$addresses->delete('p');
    }		// Address::mergeAddresses

}		// class Address 
