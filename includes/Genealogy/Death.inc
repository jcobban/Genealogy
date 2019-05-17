<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Death.inc															*
 *																		*
 *  Definition of a class representing a transcription of a death		*
 *  registration or certificate.										*
 *  This class provides access to the information in a row				*
 *  of the table Deaths.												*
 *																		*
 *	CREATE TABLE `Deaths` (                                             *
 *		`D_RegDomain`       VARCHAR(4) NOT NULL DEFAULT 'CAON',			*
 *		`D_RegYear`         INT(11) NOT NULL DEFAULT '2009',			*
 *		`D_RegNum`          INT(11) NOT NULL DEFAULT '0',			    *
 *		`D_RegCounty`       CHAR(3) DEFAULT NULL,			            *
 *		`D_RegTownship`     VARCHAR(64) DEFAULT NULL,			        *
 *		`D_MsVol`           INT(4) DEFAULT '0',			                *
 *		`D_Surname`         VARCHAR(32) DEFAULT NULL,			        *
 *		`D_SurnameSoundex`  CHAR(4) DEFAULT NULL,			            *
 *		`D_GivenNames`      VARCHAR(48) DEFAULT NULL,			        *
 *		`D_Sex`             CHAR(1) DEFAULT NULL,			            *
 *		`D_Place`           VARCHAR(128) DEFAULT NULL,			        *
 *		`D_Date`            VARCHAR(32) DEFAULT NULL,			        *
 *		`D_CalcDate`        INT(11) DEFAULT NULL,			            *
 *		`D_Age`             VARCHAR(16) DEFAULT NULL,			        *
 *		`D_BirthDate`       VARCHAR(32) DEFAULT NULL,			        *
 *		`D_CalcBirth`       INT(11) DEFAULT NULL,			            *
 *		`D_Occupation`      VARCHAR(64) DEFAULT NULL,			        *
 *		`D_MarStat`         CHAR(1) DEFAULT NULL,			            *
 *		`D_BirthPlace`      VARCHAR(128) DEFAULT NULL,			        *
 *		`D_ResPlace`        VARCHAR(16) DEFAULT NULL,			        *
 *		`D_ResOnt`          VARCHAR(16) DEFAULT NULL,			        *
 *		`D_ResCan`          VARCHAR(16) DEFAULT NULL,			        *
 *		`D_Cause`           VARCHAR(255) DEFAULT NULL,			        *
 *		`D_Duration`        VARCHAR(64) DEFAULT NULL,			        *
 *		`D_Phys`            VARCHAR(48) DEFAULT NULL,			        *
 *		`D_PhysAddr`        VARCHAR(128) DEFAULT NULL,					*
 *		`D_Informant`       VARCHAR(48) DEFAULT NULL,					*
 *		`D_InfRel`          VARCHAR(16) DEFAULT NULL,					*
 *		`D_InfOcc`          VARCHAR(32) DEFAULT NULL,					*
 *		`D_InfRes`          VARCHAR(128) DEFAULT NULL,					*
 *		`D_Religion`        VARCHAR(32) DEFAULT NULL,					*
 *		`D_FatherName`      VARCHAR(48) DEFAULT NULL,					*
 *		`D_FatherBPlce`     VARCHAR(128) DEFAULT NULL,					*
 *		`D_MotherName`      VARCHAR(48) DEFAULT NULL,					*
 *		`D_MotherBPlce`     VARCHAR(128) DEFAULT NULL,					*
 *		`D_HusbandName`     VARCHAR(48) DEFAULT NULL,					*
 *		`D_Remarks`         LONGTEXT,					                *
 *		`D_BurPlace`        VARCHAR(128) DEFAULT NULL,					*
 *		`D_BurDate`         VARCHAR(48) DEFAULT NULL,					*
 *		`D_Undertkr`        VARCHAR(48) DEFAULT NULL,					*
 *		`D_UndertkrAddr`    VARCHAR(128) DEFAULT NULL,					*
 *		`D_RegDate`         VARCHAR(16) DEFAULT NULL,					*
 *		`D_Registrar`       VARCHAR(128) DEFAULT NULL,					*
 *		`D_RecordedBy`      VARCHAR(64) DEFAULT NULL,					*
 *		`D_Image`           VARCHAR(256) NOT NULL DEFAULT '',			*
 *		`D_IDIR`            INT(10) UNSIGNED DEFAULT NULL,				*
 *		`D_ChangedBy`       VARCHAR(64) NOT NULL DEFAULT '',			*
 *		`D_ChangeDate`      DATE DEFAULT NULL,					        *
 *		`D_OriginalVolume`  VARCHAR(16) DEFAULT NULL,				    *
 *		`D_OriginalPage`    VARCHAR(11) DEFAULT NULL,				    *
 *		`D_OriginalItem`    INT(11) DEFAULT NULL,					    *
 *		PRIMARY KEY (`D_RegDomain`,			                            *
 *		             `D_RegYear`,			                            *
 *		             `D_RegNum`),			                            *
 *		KEY `D_Surname` (`D_Surname`),			                        *
 *		KEY `D_SurnameSoundex` (`D_SurnameSoundex`),			        *
 *		KEY `D_GivenNames` (`D_GivenNames`),			                *
 *		KEY `D_IDIR` (`D_IDIR`),			                            *
 *		KEY `D_ChangeDate` (`D_ChangeDate`) )			                *
 *																		*
 *  History:															*
 *		2014/01/14		created											*
 *		2014/10/10		add country code to default location			*
 *						and support all domains							*
 *		2014/12/06		send trace info to $warn						*
 *		2015/01/23		ensure that D_CalcBirth is updated when			*
 *						D_BirthDate is set even if the value of			*
 *						D_BirthDate is invalid							*
 *		2015/03/27		permit synonyms d_deathdate, and d_deathplace	*
 *						in method setField								*
 *		2015/05/01		default image URL to previous record			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						move default place identification to private	*
 *						method for clarity								*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/08/17		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *		2017/10/29		use prepared statements							*
 *		2017/12/18		add support for temporary fields				*
 *		2018/05/28		MySQL started objecting to assigning an			*
 *						empty string to an INT, so ensure INT fields	*
 *						set to NULL for empty string					*
 *		2018/11/24      log entry for insert did not include parms      *
 *		2019/01/06      move to namespace Genealogy                     *
 *		                replace DATE with yyyymmdd                      *
 *		2019/03/18  	`D_OriginalPage` VARCHAR(11) DEFAULT NULL,	    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class Death															*
 *																		*
 *  Definition of a class recording information about a single death	*
 *  registration or death certificate.									*
 *																		*
 ************************************************************************/

class Death extends Record
{

    /********************************************************************
     *	Death::$initRow													*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'd_regdomain'			=> 'CAON',
				'd_regyear'				=> 2014,
				'd_regnum'				=> 0,
				'd_regcounty'			=> '',
				'd_regtownship'			=> '',
				'd_msvol'				=> 0,
				'd_surname'				=> '',
				'd_surnamesoundex'		=> '',
				'd_givennames'			=> '',
				'd_sex'					=> '?',
				'd_place'				=> '',
				'd_date'				=> '',
				'd_calcdate'			=> 0,
				'd_age'					=> '',
				'd_birthdate'			=> '',
				'd_calcbirth'			=> 0,
				'd_occupation'			=> '',
				'd_marstat'				=> '?',
				'd_birthplace'			=> '',
				'd_resplace'			=> '',
				'd_resont'				=> '',
				'd_rescan'				=> '',
				'd_cause'				=> '',
				'd_duration'			=> '',
				'd_phys'				=> '',
				'd_physaddr'			=> '',
				'd_informant'			=> '',
				'd_infrel'				=> '?',
				'd_infocc'				=> '',
				'd_infres'				=> '',
				'd_religion'			=> '',
				'd_fathername'			=> '',
				'd_fatherbplce'			=> '',
				'd_mothername'			=> '',
				'd_motherbplce'			=> '',
				'd_husbandname'			=> '',
				'd_remarks'				=> '',
				'd_burplace'			=> '',
				'd_burdate'				=> '',
				'd_undertkr'			=> '',
				'd_undertkraddr'		=> '',
				'd_regdate'				=> '',
				'd_registrar'			=> '',
				'd_recordedby'			=> '',
				'd_image'				=> '',
				'd_idir'				=> 0,
				'd_originalvolume'		=> 0,
				'd_originalpage'		=> null,
				'd_originalitem'		=> 0,
				'd_changedby'			=> '',
				'd_changedate'			=> '');

    /********************************************************************
     *	Death::$translate												*
     *																	*
     *	Define synonyms for field names									*
     ********************************************************************/
    protected static $translate	= array(
				'domain'				=> 'd_regdomain',
				'regdomain'				=> 'd_regdomain',
				'regyear'				=> 'd_regyear',
				'regnum'				=> 'd_regnum',
				'regcounty'				=> 'd_regcounty',
				'county'				=> 'd_regcounty',
				'regtownship'			=> 'd_regtownship',
				'township'		    	=> 'd_regtownship',
				'msvol'					=> 'd_msvol',
				'surname'				=> 'd_surname',
				'surnamesoundex'		=> 'd_surnamesoundex',
				'givennames'			=> 'd_givennames',
				'sex'					=> 'd_sex',
				'place'					=> 'd_place',
				'date'					=> 'd_date',
				'calcdate'				=> 'd_calcdate',
				'age'					=> 'd_age',
				'birthdate'				=> 'd_birthdate',
				'calcbirth'				=> 'd_calcbirth',
				'occupation'			=> 'd_occupation',
				'marstat'				=> 'd_marstat',
				'birthplace'			=> 'd_birthplace',
				'resplace'				=> 'd_resplace',
				'resont'				=> 'd_resont',
				'rescan'				=> 'd_rescan',
				'cause'					=> 'd_cause',
				'duration'				=> 'd_duration',
				'phys'					=> 'd_phys',
				'physician'				=> 'd_phys',
				'attendant'				=> 'd_phys',
				'physaddr'				=> 'd_physaddr',
				'informant'				=> 'd_informant',
				'infrel'				=> 'd_infrel',
				'infocc'				=> 'd_infocc',
				'infres'				=> 'd_infres',
				'religion'				=> 'd_religion',
				'fathername'			=> 'd_fathername',
				'fatherbplce'			=> 'd_fatherbplce',
				'mothername'			=> 'd_mothername',
				'motherbplce'			=> 'd_motherbplce',
				'husbandname'			=> 'd_husbandname',
				'remarks'				=> 'd_remarks',
				'burplace'				=> 'd_burplace',
				'burdate'				=> 'd_burdate',
				'undertkr'				=> 'd_undertkr',
				'undertkraddr'			=> 'd_undertkraddr',
				'regdate'				=> 'd_regdate',
				'registrar'				=> 'd_registrar',
				'recordedby'			=> 'd_recordedby',
				'image'					=> 'd_image',
				'idir'					=> 'd_idir',
				'originalvolume'		=> 'd_originalvolume',
				'originalpage'			=> 'd_originalpage',
				'originalitem'			=> 'd_originalitem',
				'changedby'				=> 'd_changedby',
				'changedate'			=> 'd_changedate');

    /********************************************************************
     *	Death::$defaultOrder											*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	= 'D_RegDomain, D_RegYear, D_RegNum';

    /********************************************************************
     *  function Death::__construct										*
     *																	*
     *	Construct an instance of Death based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $domain		 		4 char administrative domain			*
     *							(2 char country code + state/prov code)	*
     *							or an associative array obtained from	*
     *							an SQL query by a static method			*
     *	    $year				registration year						*
     *	    $number				registration number within year			*
     *																	*
     *	Returns object containing a description of a death registration	*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms,
						 $year		= null,
						 $number	= null)
    {
		global $debug;
		global $warn;
		global $msg;
		global $connection;

		$needInsert	    = false;
		$this->table	= 'Deaths';
		$cc		        = 'CA';		// ISO country code
		$pc		        = 'ON';		// postal service state/province code

		// validate parameters
		if (is_array($parms))
		{
		    foreach($parms as $field => $value)
		    {
				$fieldLc	        = strtolower($field);
				if (array_key_exists($fieldLc, self::$translate))
                    $fieldLc	    = self::$translate[$fieldLc];
				switch($fieldLc)
				{		// act on specific parameters
				    case 'd_regdomain':
						$domain		= $value;
						break;

				    case 'd_regyear':
						$year		= $value;
						break;

				    case 'd_regnum':
						$number		= $value;
						break;

				}		// act on specific parameters
		    }			// loop through parameters
        }			    // parameters supplied in array
        else
		if (is_string($parms) || $parms instanceof Domain)
        {		// initialize new entry
            $domain         = $parms;
            $parms          = array('d_regdomain'   => $domain,
                                    'd_regyear'     => $year,
                                    'd_regnum'      => $number);
        }

        // validate parameters
        if ($domain instanceof Domain)
        {
            $domainObj      = $domain;
            $domain         = $domainObj->get('domain');
        }
        else
		if (is_string($domain))
		{		// initialize new entry
		    $domainObj	= new Domain(array('domain' => $domain,
                                           'language'	=> 'en'));
        }
        else
        {
            $this->msg      .= "Invalid parameter " . gettype($domain) . ". ";
            $domain         = null;
        }

        if ($domainObj && !$domainObj->isExisting())
			$this->msg      .=
		"Domain '$domain' must be a supported two character country code " .
		"followed by a two character state or province code";
		    
		if (is_string($year) && ctype_digit($year))
			$year	= (int)$year;
		else
		if (!is_int($year) || $year < 1800 || $year > 2100)
			$this->msg  .= "Registration year '$year' must be a numeric year";
		    
		if (is_string($number) && ctype_digit($number))
			$number	= (int)$number;
		else
		if (!is_int($number) || $number < 1)
			$this->msg  .= "Registration number '$number' must be a positive integer";

        // search for an existing record matching the key
		if (is_array($parms) && count($parms) >= count(self::$initRow))
		{			// build from database record
		    $dbrow	            = $parms;
		}			// build from database record
		else
        if (strlen($this->msg) == 0)
        {                   // valid parameters
		    // query the database
		    $query	            = 'SELECT * FROM Deaths ' .
							        'WHERE D_RegDomain=:domain AND ' .
								        'D_RegYear=:year AND ' .
								        'D_RegNum=:number';
		    $sqlParms	        = array('domain'	=> $domain,
							            'year'		=> $year,
							            'number'	=> $number);
		    $queryText	        = debugPrepQuery($query,
								                 $sqlParms);
		    $stmt	            = $connection->prepare($query);
		    if ($stmt->execute($sqlParms))
		    {			// successful query
				if ($debug)
				    $warn	    .= "<p>Death.inc: " . __LINE__ .
							            " '$queryText'</p>\n";
    
				$result		                = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0)
				{		// existing record
				    $dbrow		            = $result[0];
				    $needInsert		        = false;
				}		// existing record
				else
				{		// create a new record
				    // set defaults
				    $dbrow			        = self::$initRow;
				    $dbrow['d_regdomain']	= $domain;
				    $dbrow['d_regyear']		= $year;
				    $dbrow['d_regnum']		= $number;
				    $dbrow['d_date']		= $year;
				    $dbrow['d_burdate']		= $year;
				    $dbrow['d_regdate']		= $year;
				    $dbrow['d_calcdate']	= $year*10000 + 0615;

				    // fill in some fields from the preceding record, 
				    // if it exists
				    $queryPrev	= 'SELECT D_RegCounty, D_RegTownship, D_MsVol,'.
								  'D_Registrar, D_RegDate, D_Image,' .
						  'D_OriginalVolume, D_OriginalPage, D_OriginalItem' .
								' FROM Deaths ' .
						  		' WHERE D_RegDomain=:domain AND  ' .
									'D_RegYear=:year AND ' .
									'D_RegNum<:number ' .
								' ORDER BY D_RegNum DESC' .
								' LIMIT 1';
				    $queryText	= debugPrepQuery($queryPrev,
									 $sqlParms);
				    $stmt	= $connection->prepare($queryPrev);
				    if ($stmt->execute($sqlParms))
				    {		// successful query for previous record
						if ($debug)
						    $warn	.= "<p>Death.inc: " . __LINE__ .
								   htmlspecialchars($queryText).
								   "</p>\n";

						$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (count($result) > 0)
						{	// a preceding record exists
						    $row			        = $result[0];
						    $row['d_regdomain']		= $domain;
						    $regCounty			    = $row['d_regcounty'];
						    $dbrow['d_regcounty']	= $regCounty;
						    $regTownship		    = $row['d_regtownship'];
						    $dbrow['d_regtownship']	= $regTownship;
						    $dbrow['d_msvol']		= $row['d_msvol'];
						    $dbrow['d_registrar']	= $row['d_registrar'];
						    $dbrow['d_image']		= $row['d_image'];
						    $dbrow['d_originalvolume']= $row['d_originalvolume'];
						    $dbrow['d_originalpage']= $row['d_originalpage'];
						    $dbrow['d_originalitem']= $row['d_originalitem'] + 1;
						    if (strlen($row['d_regdate']) > 0)
							$dbrow['d_regdate']	    = $row['d_regdate'];

						    // initialize places with default value
						    $dftPlace		= $this->getDefaultPlace($row);
						    $dbrow['d_place']		= $dftPlace;
						    $dbrow['d_birthplace']	= $dftPlace;
						    $dbrow['d_burplace']	= $dftPlace;
						    $dbrow['d_physaddr']	= $dftPlace;
						    $dbrow['d_infres']		= $dftPlace;
						    $dbrow['d_undertkraddr']	= $dftPlace;
						}	// a preceding record exists

						if ($number > 1000000)
						{
						    $origVol	            = floor($number/1000000);
						    $origPage	            = floor($number/100) % 1000;
						    $origItem	            = $number % 100;
						    $dbrow['d_originalvolume']	= $origVol;
						    $dbrow['d_originalpage']	= $origPage;
						    $dbrow['d_originalitem']	= $origItem;
						}
						$needInsert		            = true;
				    }		// successful query for previous record
				    else
				    {		// error issuing query
						$this->msg  .= "'$queryText' " .
							    print_r($stmt->errorInfo(),true) . ". ";
				    }		// error issuing query
				}	    	// create new record
		    }		    	// successful query
		    else
		    {			    // error performing query
				$this->msg  .= " '$queryText' " .
							    print_r($stmt->errorInfo(),true) . ". ";
		    }			    // error performing query
		}			        // build from database query

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Deaths');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('Death Record constructed:');

        if (strlen($this->msg) > 0)
            $msg        .= $this->msg;
    }		// function Death::__construct

    /********************************************************************
     *  function Death::getDefaultPlace									*
     *																	*
     *  Get the default place name from the preceding record in the		*
     *	database														*
     *																	*
     *	Parameters:														*
     *	    $row		preceding record from table						*
     *																	*
     *	Returns:														*
     *	    string containing default place name constructed from		*
     *	    the registration town/township information in the record	*
     ********************************************************************/
    private function getDefaultPlace($row)
    {
		$domain				= $row['d_regdomain'];
		$cc			    	= substr($domain, 0, 2);
		$pc			    	= substr($domain, 2);
		$addrEnd			= ", $pc, $cc";

		$regCounty			= $row['d_regcounty'];
		$regTownship		= $row['d_regtownship'];
		$county				= new County($domain,
							    	     $regCounty);
		$countyName			= $county->getName();
		$townshipLen	    = strlen($regTownship);
		if (substr($regTownship, -5) == ' City')
		    $dftPlace	    = substr($regTownship, 0, $townshipLen - 5) .
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
    }		// function Death::getDefaultPlace

    /********************************************************************
     *	function Death:set												*
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
		global	$warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];

		switch($fieldLc)
		{		// act on specific field
		    case 'd_surnamesoundex':
		    case 'd_changedby':
		    case 'd_changedate':
		    case 'd_calcdate':
		    case 'd_calcbirth':
		    {
				$warn           .= "<p>Death::set: " . __LINE__ .
						            " cannot modify '$field'</p>\n";
		    }

		    case 'd_date':
		    case 'd_deathdate':
		    {
				$date	        = new LegacyDate(" $value");
				$y	            = $date->getYear();
				// fixup missing or abbreviated birth years
				if ($y == 0)
				    $y	        = $this->row['d_regyear'];
				else
				if ($y < 100)
				{
				    $regYear	= $this->row['d_regyear'];
				    $century	= floor($regYear / 100);
				    if ($y > $regYear % 100)
						$y	    = $y + (($century - 1) * 100);
				    else
						$y	    = $y + ($century * 100);
				}

                $m	            = $date->getMonth();
                if ($m == 0)
                    $m          = 6;
				if ($m < 10)
				    $m	        = '0' . $m;
				$d	            = $date->getDay();
				if ($d > 30 && ($m == 4 || $m == 6 || $m == 9 || $m == 11))
				    $d		    = 30;
				if ($d > 29 && $m == 2)
				    $d		    = 29;
				if ($d == 29 && $m == 2 && (($y % 4) != 0))
                    $d		    = 28;
                if ($d == 0)
                    $d          = 15;
				if ($d < 10)
                    $d	        = '0' . $d;
				parent::set('d_calcdate',$y*10000 + $m*100 + $d);
				return parent::set('d_date', $value);
		    }

		    case 'd_birthdate':
		    {
				$date	= new LegacyDate(" $value");
				$y	= $date->getYear();
				// fixup missing or abbreviated birth years
				if ($y == 0)
				    $y	= $this->row['d_regyear'];
				else
				if ($y < 100)
				{
				    $regYear	= $this->row['d_regyear'];
				    $century	= floor($regYear / 100);
				    if ($y > $regYear % 100)
						$y	= $y + (($century - 1) * 100);
				    else
						$y	= $y + ($century * 100);
				}

				$m	= $date->getMonth();
				if ($m < 10)
				    $m	= '0' . $m;
				$d	= $date->getDay();
				if ($d > 30 && ($m == 4 || $m == 6 || $m == 9 || $m == 11))
				    $d		= 30;
				if ($d > 29 && $m == 2)
				    $d		= 29;
				if ($d == 29 && $m == 2 && (($y % 4) != 0))
				    $d		= 28;
				if ($d < 10)
				    $d	= '0' . $d;
				parent::set('d_calcbirth',$y*10000 + $m*100 + $d);
				return parent::set($fieldLc, $value);
		    }

		    case 'd_place':
		    case 'd_deathplace':	// synonym
		    {
				return parent::set('d_place', $value);
		    }

		    case 'd_regdomain':
		    case 'd_regyear':
		    case 'd_regnum':
		    case 'd_regcounty':
		    case 'd_regtownship':
		    case 'd_msvol':
		    case 'd_surname':
		    case 'd_givennames':
		    case 'd_sex':
		    case 'd_age':
		    case 'd_occupation':
		    case 'd_marstat':
		    case 'd_birthplace':
		    case 'd_resplace':
		    case 'd_resont':
		    case 'd_rescan':
		    case 'd_cause':
		    case 'd_duration':
		    case 'd_phys':
		    case 'd_physaddr':
		    case 'd_informant':
		    case 'd_infrel':
		    case 'd_infocc':
		    case 'd_infres':
		    case 'd_religion':
		    case 'd_fathername':
		    case 'd_fatherbplce':
		    case 'd_mothername':
		    case 'd_motherbplce':
		    case 'd_husbandname':
		    case 'd_remarks':
		    case 'd_burplace':
		    case 'd_burdate':
		    case 'd_undertkr':
		    case 'd_undertkraddr':
		    case 'd_regdate':
		    case 'd_registrar':
		    case 'd_recordedby':
		    case 'd_image':
		    case 'd_idir':
		    case 'd_originalvolume':
		    case 'd_originalpage':
		    case 'd_originalitem':
		    {
				return parent::set($fieldLc, $value);
		    }

		    default:
		    {		// other fields
				return parent::set($fieldLc, $value);
		    }		// other fields
		}		// act on specific field
    }		// Death::set

    /********************************************************************
     *  function Death::save											*
     *																	*
     *	Save changes made to the Death record into the database.		*
     *																	*
     *	Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format within <cmd> tags.						*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1				if the method updated the database record	*
     *	    0				if the database did not need to be updated	*
     *																	*
     *  Throws:															*
     *	    exception if user is not authorized to update the database	*
     ********************************************************************/
    function save($xml)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$userid;
		global	$connection;

		if (is_string($xml))
		{	            	// string tag name
		    $tagname	= $xml;
		    $xml    	= true;
		}           		// string tag name
		else
		if ($xml)
		{           		// true
		    $tagname	= 'cmd';
		}           		// true

		if ($this->needInsert)
		{	            	// create new record
		    $fldnames					= '';
		    $comma		    			= '';
		    $values		    			= '';
		    $sqlParms					= array();
		    foreach($this->row as $fld => $value)
		    {	    		// loop through all fields in record
				switch($fld)
				{   		// act on specific fields
				    case 'd_surnamesoundex':
				    {       // ask server to calculate
						$fldnames	        	.= $comma . "`$fld`"; 
						$surname        		= $this->row['d_surname'];
						$values		            .= ',LEFT(SOUNDEX(:surname),4)';
						$sqlParms['surname']	= $surname;
						$comma	        		= ", ";
						break;
				    }		// D_SurnameSoundex

				    case 'd_changedby':
				    {
						$fldnames       		.= $comma . "`$fld`"; 
						$values	        		.= $comma . ':changedby';
						$sqlParms['changedby']	= $userid;
						$comma	        		= ", ";
						break;
				    }		// D_ChangedBy

				    case 'd_changedate':
				    {
						$fldnames       		.= $comma . "`$fld`"; 
						$values	        		.= $comma . 'CURDATE()';
						$comma	        		= ", ";
						break;
				    }		// D_ChangeDate

				    case 'd_idir':
				    case 'd_originalitem':
				    {		// integer fields
						$fldnames	        	.= $comma . "`$fld`";
						$shortFld       		= substr($fld, 2);
						$values			        .= $comma . ':' . $shortFld;
						if (is_int($value) || ctype_digit($value))
						    $sqlParms[$shortFld]= $value;
						else
						    $sqlParms[$shortFld]= null;
						$comma			        = ', ';
						break;
				    }		// integer fields

				    default:
				    {
						$fldnames		        .= $comma . "`$fld`";
						$shortFld       		= substr($fld, 2);
						$values			        .= $comma . ':' . $shortFld;
						$sqlParms[$shortFld]	= $value;
						$comma			        = ", ";
						break;
				    }		// most fields
				}	    	// act on specific fields
		    }		    	// loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	    = "INSERT INTO Deaths ($fldnames) VALUES($values)";
		    $insertText	= debugPrepQuery($insert, $sqlParms);

		    // insert the new record into the database
		    $stmt	= $connection->prepare($insert);
		    if ($stmt->execute($sqlParms))
		    {		        // insert successful
				$count			    = $stmt->rowCount();
				if ($xml)
				    print "<$tagname result='$count'>" . xmlentities($insert) . 
						    "</$tagname>\n";
				else
				if ($debug)
				    $warn	        .= "<p>$insert</p>\n<p>count=$count</p>\n";

				$this->changed		= array();	// reset
				$this->needInsert	= false;

				// log the update
				$this->log($insert, $sqlParms);
				return $count;
		    }		        // insert successful
		    else
		    {		        // error inserting record
                error_log("Death.inc: " . __LINE__ . " '$insertText'\n");
				throw new Exception("Death::save: '" .
								substr($insertText,0,40) .  "...', " .
								print_r($stmt->errorInfo(),true));
		    }		        // error inserting record
		}		            // create new record
		else
		{		            // update existing record
		    $update         		= "UPDATE Deaths SET ";
		    $set            		= '';
		    $comma          		= '';
		    $sqlParms       		= array();
		    foreach($this->changed as $fld => $value)
		    {
				switch($fld)
				{		    // act on specific fields
				    case 'd_surname':
				    {		// surname changed
						$set		    .= $comma . '`D_Surname`=:surname';
						$sqlParms['surname']	= $value;
						$set			.= ',`D_SurnameSoundex`='; 
						$set			.= 'LEFT(SOUNDEX(:surname), 4)';
						$comma			= ', ';
						break;
				    }		// D_Surname

				    case 'd_changedby':
				    case 'd_changedate':
				    case 'd_surnamesoundex':
				    {       // calculated
						break;
				    }		// D_ChangedBy, D_ChangeDate

				    case 'd_idir':
				    case 'd_msvol':
				    case 'd_originalitem':
				    {		// integer value fields
						$shortFld   		    = substr($fld, 2);
						$set	    	    	.= $comma . "`$fld`=:$shortFld";
						if (is_int($value) || ctype_digit($value))
						    $sqlParms[$shortFld]= $value;
						else
						    $sqlParms[$shortFld]= null;
						$comma          	    = ', ';
						break;
				    }		// integer value fields

				    default:
				    {		// most fields
						$shortFld       		= substr($fld, 2);
						$set	        		.= $comma . "`$fld`=:$shortFld";
						$sqlParms[$shortFld]	= $value;
						$comma	                = ', ';
						break;
				    }		// most fields
				}		    // act on specific fields
		    }		        // loop through all changed fields in record

		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
            {		// changes made
                // record who changed the record and when
				$set			.= ',D_ChangedBy=:userid';
				$set			.= ',D_ChangeDate=CURDATE()';
				$sqlParms['userid']	= $userid;

				// construct the where clause
				if ($this->former)
				{
				    $sqlParms['domain']	= $this->former['d_regdomain'];
				    $sqlParms['year']	= $this->former['d_regyear'];
				    $sqlParms['number']	= $this->former['d_regnum'];
				}
				else
				{
				    $sqlParms['domain']	= $this->row['d_regdomain'];
				    $sqlParms['year']	= $this->row['d_regyear'];
				    $sqlParms['number']	= $this->row['d_regnum'];
				}
				$where		= 'D_RegDomain=:domain AND ' .
							  'D_RegYear=:year AND ' .
							  'D_RegNum=:number';

				// assemble the UPDATE command
				$update		.= $set . ' WHERE ' . $where;
				$updateText	= debugPrepQuery($update, $sqlParms);

				// perform the update
				$stmt		= $connection->prepare($update);
				if ($stmt->execute($sqlParms))
				{		// update successful
				    $count		= $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$count'>" .
							xmlentities($updateText) . "</$tagname>\n";

				    if ($debug)
						$warn		.= "<p>Death.inc: " . __LINE__ . 
								   " '$updateText'</p>\n";

				    // log the update
				    $this->log($update, $sqlParms);
				    return $count;
				}		// update successful
				else
				{		// error updating record
				    throw new Exception("Death::save:
								'$update', " .
								print_r($stmt->errorInfo(),true));
				}		// error updating record
		    }		    // changes made
		}		        // update existing record
    }		// function Death::save

    /********************************************************************
     *  function Death::delete											*
     *																	*
     *	Delete the database record associated with this instance.		*
     *																	*
     *	Input:															*
     *	    $xml		if true emit XML								*
     *					if a string emit XML using as tag name			*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1				if the method deleted the database record	*
     *	    0				if the record did not need to be deleted	*
     ********************************************************************/
    function delete($xml = false)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		// handle XML output option
		if (is_string($xml))
		    $xmltag	= $xml;
		else
		if ($xml)
		{
		    $xml	= true;
		    $xmltag	= 'cmd';
		}

		// delete the record if it is in the database
		if (!$this->needInsert)
		{		// existing record in database
		    $sqlParms		= array();
		    $sqlParms['domain']	= $this->row['d_regdomain'];
		    $sqlParms['year']	= $this->row['d_regyear'];
		    $sqlParms['number']	= $this->row['d_regnum'];
		    $delete	= "DELETE FROM Deaths WHERE" .
							' D_RegDomain=:domain AND ' .
							' D_RegYear=:year AND D_RegNum=:number';
		    $deleteText	= debugPrepQuery($delete, $sqlParms);
		    $stmt	= $connection->prepare($delete);
		    if ($stmt->execute($sqlParms))
		    {		// delete successful
				$count			= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

				// log the update
				$this->log($delete, $sqlParms);

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// delete successful
		    else
		    {		// error updating record
				error_log("Death.inc: " . __LINE__ . " '$deleteText', " .
						  print_r($stmt->errorInfo(),true) . "\n");
				return false;
		    }		// error updating record
		}		    // existing record in database

		// record never written to database
		return 0;	// nothing to delete
    }		// function Death::delete

}		// class Death 
