<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Birth.inc															*
 *																		*
 *  Definition of a class representing a birth registration.			*
 *  This class provides access to the information in a row				*
 *  of the table Births.												*
 *																		*
 *	CREATE TABLE `Births` (                                             *
 *		`RegDomain`			    VARCHAR(4) NOT NULL DEFAULT 'CAON',		*
 *		`RegYear`			    INT(11) NOT NULL,					    *
 *		`RegNum`			    INT(11) NOT NULL,					    *
 *		`RegCounty`             CHAR(3) DEFAULT NULL,					*
 *		`RegTownship`			VARCHAR(64) DEFAULT NULL,				*
 *		`MsVol`			        INT(4) DEFAULT '0',					    *
 *		`Surname`			    VARCHAR(32) DEFAULT NULL,				*
 *		`SurnameSoundex`        CHAR(4) DEFAULT NULL,					*
 *		`GivenNames`			VARCHAR(48) DEFAULT NULL,				*
 *		`Sex`                   CHAR(1) DEFAULT NULL,					*
 *		`BirthPlace`			VARCHAR(128) DEFAULT NULL,				*
 *		`BirthDate`			    VARCHAR(16) DEFAULT NULL,				*
 *		`CalcBirth`             DATE DEFAULT NULL,					    *
 *		`ParentsMarried`        CHAR(1) DEFAULT NULL,					*
 *		`FatherName`			VARCHAR(48) DEFAULT NULL,				*
 *		`FatherOccupation`	    VARCHAR(32) DEFAULT NULL,				*
 *		`FatherOccPlace`		VARCHAR(128) DEFAULT NULL,				*
 *		`MotherName`			VARCHAR(48) DEFAULT NULL,			    *
 *		`MotherOccupation`	    VARCHAR(32) DEFAULT NULL,				*
 *		`MotherOccPlace`		VARCHAR(128) DEFAULT NULL,				*
 *		`FormerHusband`		    VARCHAR(48) DEFAULT NULL,			    *
 *		`MarriagePlace`		    VARCHAR(128) DEFAULT NULL,				*
 *		`MarriageDate`		    VARCHAR(16) DEFAULT NULL,			    *
 *		`Accoucheur`			VARCHAR(48) DEFAULT NULL,			    *
 *		`Informant`			    VARCHAR(48) DEFAULT NULL,			    *
 *		`InformantRes`		    VARCHAR(128) DEFAULT NULL,			    *
 *		`InformantRel`		    VARCHAR(16) DEFAULT NULL,			    *
 *		`RegDate`			    VARCHAR(16) DEFAULT NULL,			    *
 *		`Remarks`               LONGTEXT,					            *
 *		`Image`			        VARCHAR(256) DEFAULT NULL,			    *
 *		`IDIR`			        INT(10) UNSIGNED DEFAULT NULL,			*
 *		`ChangedBy`			    VARCHAR(64) NOT NULL DEFAULT '',		*
 *		`ChangeDate`            DATE DEFAULT NULL,					    *
 *		`Registrar`			    VARCHAR(128) NOT NULL DEFAULT '',		*
 *		`OriginalVolume`		VARCHAR(16) DEFAULT NULL,				*
 *		`OriginalPage`		    INT(11) DEFAULT NULL,					*
 *		`OriginalItem`		    INT(11) DEFAULT NULL,					*
 *		PRIMARY KEY (`RegDomain`,					                    *
 *		             `RegYear`,					                        *
 *		             `RegNum`),					                        *
 *		KEY `RegCounty`   (`RegCounty`),			    		        *
 *		KEY `RegTownship` (`RegTownship`),			    		        *
 *		KEY `Surname`     (`Surname`),				    	            *
 *		KEY `SurnameSoundex` (`SurnameSoundex`),	    				*
 *		KEY `GivenNames`  (`GivenNames`),			    		        *
 *		KEY `IDIR`        (`IDIR`),					                    *
 *		KEY `ChangeDate`  (`ChangeDate`) )			    		        *
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8 					            *
 *                                                                      *
 *  History:															*
 *		2014/01/14		created											*
 *		2014/01/18		default parentsMarried to 'Y'					*
 *		2014/10/10		add country code to default location			*
 *						and support all domains							*
 *		2014/12/06		send trace info to $warn						*
 *		2015/01/23		ensure that CalcBirth is updated when			*
 *						BirthDate is set even if the value of			*
 *						BirthDate is invalid							*
 *		2015/04/19		default image URL to previous record			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						move default place identification to private	*
 *						method for clarity								*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/01/19		use set in place of setField					*
 *		2017/08/17		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *		2017/10/01		add static methods getBirths, updateBirths, and	*
 *						deleteBirths, getStatistics, getYearStatistics	*
 *		2017/10/16		function of static methods moved to class		*
 *						BirthSet										*
 *		2017/12/18		add support for temporary fields				*
 *		2018/05/28		MySQL started objecting to assigning an			*
 *						empty string to an INT, so ensure INT fields	*
 *						set to NULL for empty string					*
 *		2018/11/11      support standard constructor calling pattern    *
 *		                support passing instance of Domain              *
 *		                do not throw exceptions from constructor        *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/06/12      improve validation of values assigned to Dates  *
 *		2019/12/13      remove B_ prefix from file names                *
 *		2020/12/02      protect against XSS                             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class Birth															*
 *																		*
 *  Definition of a class recording information about a single birth	*
 *  registration or birth certificate.									*
 *																		*
 ************************************************************************/

class Birth extends Record
{
    /********************************************************************
     *	Birth::$initRow													*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'regdomain'			    => 'CAON',
				'regyear'				=> 2014,
				'regnum'				=> 0,
				'regcounty'			    => '',
				'regtownship'			=> '',
				'msvol'				    => 0,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'sex'					=> '?',
				'birthplace'			=> '',
				'birthdate'			    => '',
				'calcbirth'			    => null,
				'parentsmarried'		=> 'Y',
				'fathername'			=> '',
				'fatheroccupation'	    => '',
				'fatheroccplace'		=> '',
				'mothername'			=> '',
				'motheroccupation'	    => '',
				'motheroccplace'		=> '',
				'formerhusband'		    => '',
				'marriageplace'		    => '',
				'marriagedate'		    => '',
				'accoucheur'			=> '',
				'informant'		    	=> '',
				'informantres'	    	=> '',
				'informantrel'	    	=> '',
				'regdate'				=> '',
				'registrar'		    	=> '',
				'remarks'				=> '',
				'image'			    	=> '',
				'originalvolume'		=> 0,
				'originalpage'	    	=> 0,
				'originalitem'	    	=> 0,
				'idir'			    	=> 0,
				'changedby'			    => '',
				'changedate'			=> '');

    /********************************************************************
     *	Birth::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Births',
                'name'      => 'Births',
                'prime'	    => array('regdomain','regyear','regnum'),
	            'srvmg'     => false,
	            'fldcount'	=> 34,
                'order'     => 'RegDomain, RegYear, RegNum',
                'classname' => 'Birth');

    /********************************************************************
     *	Birth::$translate												*
     *																	*
     *	standard alternative field name identifiers						*
     ********************************************************************/
    protected static $translate	= array(
				'b_regdomain'				=> 'regdomain',
				'domain'				    => 'regdomain',
				'b_regyear'			    	=> 'regyear',
				'year'					    => 'regyear',
				'b_regnum'			    	=> 'regnum',
				'num'				    	=> 'regnum',
				'b_regcounty'				=> 'regcounty',
				'county'			    	=> 'regcounty',
				'b_regtownship'		    	=> 'regtownship',
				'township'				    => 'regtownship',
				'b_msvol'					=> 'msvol',
				'b_surname'			    	=> 'surname',
				'b_surnamesoundex'	    	=> 'surnamesoundex',
				'b_givennames'		    	=> 'givennames',
				'b_sex'				    	=> 'sex',
				'b_birthplace'		    	=> 'birthplace',
				'b_place'					=> 'birthplace',
				'b_birthdate'				=> 'birthdate',
				'b_date'					=> 'birthdate',
				'b_calcbirth'				=> 'calcbirth',
				'b_parentsmarried'	    	=> 'parentsmarried',
				'b_fathername'		    	=> 'fathername',
				'b_father'			    	=> 'fathername',
				'b_fatheroccupation'		=> 'fatheroccupation',
				'b_fatheroccplace'	    	=> 'fatheroccplace',
				'b_mothername'		    	=> 'mothername',
				'b_mother'			    	=> 'mothername',
				'b_motheroccupation'		=> 'motheroccupation',
				'b_motheroccplace'	    	=> 'motheroccplace',
				'b_formerhusband'			=> 'formerhusband',
				'b_marriageplace'			=> 'marriageplace',
				'b_marriagedate'			=> 'marriagedate',
				'b_accoucheur'		    	=> 'accoucheur',
				'b_informant'				=> 'informant',
				'b_informantname'			=> 'informant',
				'b_informantres'			=> 'informantres',
				'b_informantrel'			=> 'informantrel',
				'b_regdate'			    	=> 'regdate',
				'b_registrar'				=> 'registrar',
				'b_remarks'			    	=> 'remarks',
				'b_image'					=> 'image',
				'b_originalvolume'	    	=> 'originalvolume',
				'volume'			    	=> 'originalvolume',
				'b_originalpage'			=> 'originalpage',
				'page'					    => 'originalpage',
				'b_originalitem'			=> 'originalitem',
				'item'					    => 'originalitem',
				'b_idir'					=> 'idir',
				'b_changedby'				=> 'changedby',
				'b_changedate'		    	=> 'changedate');

    /********************************************************************
     *	Birth::$domain  												*
     *																	*
     *	Save reference to instance of Domain                            *
     ********************************************************************/
    protected   $domain                 = null;

    /********************************************************************
     *	function Birth::__construct										*
     *																	*
     *	Construct an instance of Birth based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $parms              associative array which is either       *
     *	                        a complete row from the database        *
     *	                        or a set of search parameters:          *
     *	                        array('domain'  => $domain,             *
     *	                              'year'    => $year,               *
     *	                              'num'     => $number)             *
     *	        where:													*
     *	    $domain		 		4 char administrative domain			*
     *							(2 char country code + state/prov code)	*
     *							or an instance of class Domain          *
     *	    $year				registration year						*
     *	    $number				registration number within year			*
     *																	*
     *	For backwards compatibility the constructor may also be         *
     *	called by new Birth($domain, $year, $number)					*
     *																	*
     *	Returns object containing a description of a birth registration	*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($domain,
						 $year		= null,
						 $number	= null)
    {
		global $debug;
		global $connection;
		global $warn;
		global $msg;

		$needInsert	    	= false;
		$this->table		= 'Births';
		$cc		        	= 'CA';		    // ISO country code
		$pc		        	= 'ON';		    // postal service state/prov code
		$addrEnd	    	= ', ON, CA';	// last part of addresses

        // validate parameters
		if (is_string($domain))
		{		            // initialize new entry
		    $domainObj	    = new Domain(array('domain'	    => $domain,
                                               'language'	=> 'en'));
            $parms          = array('domain'              => $domainObj,
                                    'regyear'             => $year,
                                    'regnum'              => $number);
        }                   // is_string($domain)
        else
        if ($domain instanceof Domain)
        {
            $domainObj      = $domain;
            $domain         = $domainObj->get('domain');
            $parms          = array('domain'              => $domainObj,
                                    'regyear'             => $year,
                                    'regnum'              => $number);
        }                   // $domain instanceof Domain
        else
        if (is_array($domain))
        {
            $parms          = $domain;
        }                   // is_array($domain))
        else
        {
            $this->msg      .= "Birth::__construct(" .
                                    gettype($domain) . ", " .
                                    gettype($year) . ", " .
                                    gettype($number) . "). ";
            $parms          = array();
        }

        if (count($parms) < count(Birth::$initRow))
        {                   // search parameters
            foreach($parms as $field => $value)
            {                       // loop through parameters
				$fieldLc		= strtolower($field);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	= self::$translate[$fieldLc];
				switch($fieldLc)
				{	            	// act on specific field
	                case 'regdomain':
	                {
						if (is_string($value))
                        {		            // initialize new entry
                            $domain         = $value;
                            $domainObj	   
                                = new Domain(array('domain'	    => $domain,
				                                   'language'	=> 'en'));
                            $this->domain   = $domainObj;
				        }                   // is_string($domain)
				        else
				        if ($value instanceof Domain)
				        {
			                $domainObj      = $value;
                            $this->domain   = $domainObj;
			                $domain         = $domainObj->get('domain');
                        }                   // $domain instanceof Domain
                        else
                            $this->msg      .= "Invalid regdomain=" .
                                                gettype($value) . ". ";
                        break;
                    }

				    case 'regyear':
                    {
                        $year               = $value;
                        break;
                    }

				    case 'regnum':
                    {
                        $number             = $number;
                        break;
                    }
                }	            	// act on specific field
            }                       // loop through parameters

		    if ($domainObj->isExisting())
		    {		                // valid domain
				$cc		                    = substr($domain, 0, 2);
				$pc		                    = substr($domain, 2);
		    }		                // valid domain
		    else
				$this->msg		.= "Birth::__construct: " .
                "Domain '" . htmlspecialchars($domain) . 
                "' must be a supported two character country code followed by a 2 or 3 character character state or province code. ";

		    // determine common ending of default addresses
		    $addrEnd	                    = ", $pc, $cc";

		    if (is_string($year) && ctype_digit($year))
				$year	                    = (int)$year;
		    else
            if (!is_int($year) || $year < 1800 || $year > 2100)
            {
                $year           = htmlspecialchars($yes);
                $this->msg		.= "Birth::__construct: registration year '$year' must be a numeric year. ";
            }
		    
		    if (is_string($number) && ctype_digit($number))
				$number	                    = (int)$number;
		    else
		    if (!is_int($number) || $number < 1)
            {
                $number         = htmlspecialchars($number);
                $this->msg		.= "Birth::__construct: registration number '$number' must be a positive integer. ";
            }

		    // search for an existing record matching the key
		    $query	            = "SELECT * FROM Births 
            							WHERE RegDomain=:domain AND 
			            					  RegYear=:year AND
						            		  RegNum=:number";
		    $stmt	            = $connection->prepare($query);
		    
		    // query the database
            $sqlParms	        = array('domain'        => $domain, 
                                        'year'          => $year, 
                                        'number'        => $number);
			$queryText	        = debugPrepQuery($query, $sqlParms);
            $this->lastSqlCmd   = $queryText;
		    if ($stmt->execute($sqlParms))
		    {		        // successful query
				if ($debug)
                    $warn .= "<p>$queryText</p>\n";

				$result	        = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0)
				{		    // existing record
				    $dbrow		            = $result[0];
				    $needInsert	            = false;
				}		    // existing record
				else
				{		    // create a new record
				    // set defaults
				    $dbrow		        	= self::$initRow;
				    $dbrow['regdomain']	= $domain;
				    $dbrow['regyear']		= $year;
				    $dbrow['regnum']		= $number;
				    $dbrow['birthdate']	= $year;
				    $dbrow['regdate']		= $year;

				    // fill in some fields from the preceding record, 
				    // if it exists
				    $queryPrev	= 'SELECT RegCounty, RegTownship, MsVol,'.
								  'Registrar, RegDate, Image,' .
						  'OriginalVolume, OriginalPage, OriginalItem' .
							       ' FROM Births ' .
						  	       ' WHERE RegDomain=:domain AND ' .
									'RegYear=:year AND ' .
									'RegNum<:number' .
							       ' ORDER BY RegNum DESC' .
							       ' LIMIT 1';

				    $stmt	            = $connection->prepare($queryPrev);
			        $queryText	        = debugPrepQuery($queryPrev, $sqlParms);
                    $this->lastSqlCmd   = $queryText;
				    if ($stmt->execute($sqlParms))
				    {		// successful query
						if ($debug)
                            $warn       .= "<p>$queryText</p>\n";

						$result	        = $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (count($result) > 0)
						{		// a preceding record exists
						    $row			            = $result[0];
						    $row['regdomain']		    = $domain;
						    $regCounty			        = $row['regcounty'];
						    $dbrow['regcounty']	        = $regCounty;
						    $regTownship		        = $row['regtownship'];
						    $dbrow['regtownship']	    = $regTownship;
						    $dbrow['msvol']		        = $row['msvol'];
						    $dbrow['registrar']	        = $row['registrar'];
						    $dbrow['image']		        = $row['image'];
						    $volume	                    = $row['originalvolume'];
						    $dbrow['originalvolume']	= $volume;
						    $page	                    = $row['originalpage'];
						    $dbrow['originalpage']	    = $page;
						    $item	                    = $row['originalitem'];
						    $dbrow['originalitem']	    = $item + 1;
						    if (strlen($row['regdate']) > 0)
							    $dbrow['regdate']	    = $row['regdate'];

						    // initialize places with default value
						    $dftPlace		= $this->getDefaultPlace($row);
						    $dbrow['birthplace']	    = $dftPlace;
						    $dbrow['fatheroccplace']	= $dftPlace;
						    $dbrow['motheroccplace']	= $dftPlace;
						    $dbrow['informantres']	= $dftPlace;
						    if ($year < 1908)
							    $dbrow['marriageplace']	= 'N/A';
						    else
							    $dbrow['marriageplace']	= $dftPlace;
						}	// a preceding record exists

						if ($number > 1000000)
						{
						    $origVol	= floor($number/1000000);
						    $origPage	= floor($number/100) % 1000;
						    $origItem	= $number % 100;
						    $dbrow['originalvolume']	= $origVol;
						    $dbrow['originalpage']	= $origPage;
						    $dbrow['originalitem']	= $origItem;
						}
						$needInsert		= true;
				    }		// successful query
				    else
				    {		// error issuing query
                        $this->msg		.= "Birth::__construct: '" .
                                debugPrepQuery($queryPrev, $sqlParms) . "' " .
							    print_r($stmt->errorInfo(),true) . '. ';
				    }		// error issuing query
				}		    // create new record
		    }			    // successful query
		    else
		    {			    // error performing query
                $this->msg		.= "Birth::__construct: '$queryText' " .
							        print_r($stmt->errorInfo(),true) . '. ';
		    }			    // error performing query
		}			        // build from database query
		else
		{			        // build from explicit array
		    $dbrow	            = $domain;
		}		            // build from explicit array

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Births');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('Birth Record constructed:');

        $msg                .= $this->msg;
    }		// function Birth::__construct

    /********************************************************************
     *	function Birth::getDefaultPlace									*
     *																	*
     *  Get the default place name from the preceding record in the		*
     *	database														*
     *																	*
     *	Parameters:														*
     *	    $name		field name										*
     *																	*
     *	Returns:														*
     *	    string containing default place name constructed from		*
     *	    the registration town/township information in the record	*
     ********************************************************************/
    private function getDefaultPlace($row)
    {
		$domain			= $row['regdomain'];
		$cc			= substr($domain, 0, 2);
		$pc			= substr($domain, 2);
		$addrEnd		= ", $pc, $cc";

		$regCounty		= $row['regcounty'];
		$regTownship		= $row['regtownship'];
		$county			= new County($domain,
								     $regCounty);
		$countyName		= $county->getName();
		$townshipLen		= strlen($regTownship);
		if (substr($regTownship, -5) == ' City')
		    $dftPlace	= substr($regTownship, 0, $townshipLen - 5) .
							 $addrEnd;
		else
		if (substr($regTownship, -4) == ' Twp')
		    $dftPlace	= substr($regTownship, 0, $townshipLen - 4) .  ', ' .
							 $countyName .  $addrEnd;
		else
		if (substr($regTownship, -5) == ' Twp.' ||
		substr($regTownship, -5) == ' Town')
		    $dftPlace	= substr($regTownship, 0, $townshipLen - 5) . ', ' .
							 $countyName . $addrEnd;
		else
		if (substr($regTownship, -8) == ' Village')
		    $dftPlace	= substr($regTownship, 0, $townshipLen - 8) . ', ' .
							 $countyName . $addrEnd;
		else
		    $dftPlace	= $regTownship . ', ' .
							 $countyName . $addrEnd;
		return $dftPlace;
    }		// function Birth::getDefaultPlace

    /********************************************************************
     *	function Birth:set												*
     *																	*
     *	Change the value of a field in the object.						*
     *	The method validates that the supplied value is compatible with *
     *	the target field.												*
     *																	*
     *	Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{	            	// act on specific field
		    case 'surnamesoundex':
		    case 'changedby':
		    case 'changedate':
		    case 'calcbirth':
		    {               // fields whose value is set from another field
				throw new Exception(
						"Birth::set: cannot modify '$field'");
		    }               // fields whose value is set from another field

		    case 'birthdate':
		    {
				$date	= new LegacyDate(" $value");
				$y	    = $date->getYear();
				// fixup missing or abbreviated birth years
				if ($y == 0)
				    $y	= $this->row['regyear'];
				else
				if ($y < 1000)
				{
				    $regYear	= $this->row['regyear'];
				    $century	= floor($regYear / 100);
				    if ($y > $regYear % 100)
						$y	= $y + (($century - 1) * 100);
				    else
						$y	= $y + ($century * 100);
				}
                if ($y > 9999)
                    $y      = $y % 10000;

                $m	        = $date->getMonth();
                if ($m > 12)
                    $m      = 12;
				if ($m < 10)
				    $m	    = '0' . $m;
                $d	    = $date->getDay();
                if ($d > 31)
                    $d      = 31;
				if ($d > 30 && ($m == 4 || $m == 6 || $m == 9 || $m == 11))
				    $d		= 30;
				if ($d > 29 && $m == 2)
				    $d		= 29;
				if ($d == 29 && $m == 2 && (($y % 4) != 0))
				    $d		= 28;
				if ($d < 10)
                    $d	= '0' . $d;
                
				parent::set('calcbirth', "$y-$m-$d");
				return parent::set($fieldLc, $value);
		    }

		    case 'parentsmarried':
		    {
				if ($value == '' || $value == 'n' || $value == 'N')
				    return parent::set($fieldLc, 'N');
				else
				    return parent::set($fieldLc, 'Y');
		    }

		    case 'regcounty':
		    case 'regtownship':
		    case 'surname':
		    case 'givennames':
		    case 'sex':
		    case 'birthplace':
		    case 'birthdate':
		    case 'fathername':
		    case 'fatheroccupation':
		    case 'fatheroccplace':
		    case 'mothername':
		    case 'motheroccupation':
		    case 'motheroccplace':
		    case 'formerhusband':
		    case 'marriageplace':
		    case 'marriagedate':
		    case 'accoucheur':
		    case 'informant':
		    case 'informantres':
		    case 'informantrel':
		    case 'regdate':
		    case 'registrar':
		    case 'remarks':
		    case 'image':
		    {
				return parent::set($fieldLc, $value);
		    }

		    case 'msvol':
		    case 'idir':
		    case 'originalvolume':
		    case 'originalpage':
		    case 'originalitem':
		    {
				if (is_null($value) ||
				    (is_string($value) && strlen($value) == 0))
				    $value		= 0;
				return parent::set($fieldLc, $value);
		    }

		    default:
		    {
				return parent::set($fieldLc, $value);
		    }
		}		            // act on specific field
    }		// function Birth::set

    /********************************************************************
     *	function Birth::save											*
     *																	*
     *  Save changes made to the Birth record into the	database.		*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method updated the database record		*
     *	    0			if the database did not need to be updated		*
     *																	*
     *  Throws:															*
     *	    exception if user is not authorized to update the database	*
     ********************************************************************/
    function save($xml)
    {
		global	$debug;
		global	$warn;
		global	$userid;

        if ($this->needInsert || count($this->changed) > 0)
        {                   // record needs to be saved
			// get current date 
			$now		    = getdate();
            parent::set('changedby', $userid);
            parent::set('changedate', 
                        $now['year'] . '-' .  $now['mon'] . '-' . $now['mday']);
            parent::save($xml);
        }                   // record needs to be saved
        else
            return 0;
    }       // function Birth::save

}		// class Birth 
