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
 *		`B_RegDomain`			VARCHAR(4) NOT NULL DEFAULT 'CAON',		*
 *		`B_RegYear`			    INT(11) NOT NULL,					    *
 *		`B_RegNum`			    INT(11) NOT NULL,					    *
 *		`B_RegCounty`           CHAR(3) DEFAULT NULL,					*
 *		`B_RegTownship`			VARCHAR(64) DEFAULT NULL,				*
 *		`B_MsVol`			    INT(4) DEFAULT '0',					    *
 *		`B_Surname`			    VARCHAR(32) DEFAULT NULL,				*
 *		`B_SurnameSoundex`      CHAR(4) DEFAULT NULL,					*
 *		`B_GivenNames`			VARCHAR(48) DEFAULT NULL,				*
 *		`B_Sex`                 CHAR(1) DEFAULT NULL,					*
 *		`B_BirthPlace`			VARCHAR(128) DEFAULT NULL,				*
 *		`B_BirthDate`			VARCHAR(16) DEFAULT NULL,				*
 *		`B_CalcBirth`           DATE DEFAULT NULL,					    *
 *		`B_ParentsMarried`      CHAR(1) DEFAULT NULL,					*
 *		`B_FatherName`			VARCHAR(48) DEFAULT NULL,				*
 *		`B_FatherOccupation`	VARCHAR(32) DEFAULT NULL,				*
 *		`B_FatherOccPlace`		VARCHAR(128) DEFAULT NULL,				*
 *		`B_MotherName`			VARCHAR(48) DEFAULT NULL,			    *
 *		`B_MotherOccupation`	VARCHAR(32) DEFAULT NULL,				*
 *		`B_MotherOccPlace`		VARCHAR(128) DEFAULT NULL,				*
 *		`B_FormerHusband`		VARCHAR(48) DEFAULT NULL,			    *
 *		`B_MarriagePlace`		VARCHAR(128) DEFAULT NULL,				*
 *		`B_MarriageDate`		VARCHAR(16) DEFAULT NULL,			    *
 *		`B_Accoucheur`			VARCHAR(48) DEFAULT NULL,			    *
 *		`B_Informant`			VARCHAR(48) DEFAULT NULL,			    *
 *		`B_InformantRes`		VARCHAR(128) DEFAULT NULL,			    *
 *		`B_InformantRel`		VARCHAR(16) DEFAULT NULL,			    *
 *		`B_RegDate`			    VARCHAR(16) DEFAULT NULL,			    *
 *		`B_Remarks`             LONGTEXT,					            *
 *		`B_Image`			    VARCHAR(256) DEFAULT NULL,			    *
 *		`B_IDIR`			    INT(10) UNSIGNED DEFAULT NULL,			*
 *		`B_ChangedBy`			VARCHAR(64) NOT NULL DEFAULT '',		*
 *		`B_ChangeDate`          DATE DEFAULT NULL,					    *
 *		`B_Registrar`			VARCHAR(128) NOT NULL DEFAULT '',		*
 *		`B_OriginalVolume`		VARCHAR(16) DEFAULT NULL,				*
 *		`B_OriginalPage`		INT(11) DEFAULT NULL,					*
 *		`B_OriginalItem`		INT(11) DEFAULT NULL,					*
 *		PRIMARY KEY (`B_RegDomain`,					                    *
 *		             `B_RegYear`,					                    *
 *		             `B_RegNum`),					                    *
 *		KEY `B_RegCounty`   (`B_RegCounty`),					        *
 *		KEY `B_RegTownship` (`B_RegTownship`),					        *
 *		KEY `B_Surname`     (`B_Surname`),					            *
 *		KEY `B_SurnameSoundex` (`B_SurnameSoundex`),					*
 *		KEY `B_GivenNames`  (`B_GivenNames`),					        *
 *		KEY `B_IDIR`        (`B_IDIR`),					                *
 *		KEY `B_ChangeDate`  (`B_ChangeDate`) )					        *
 *		ENGINE=InnoDB DEFAULT CHARSET=utf8 					            *
 *                                                                      *
 *  History:															*
 *		2014/01/14		created											*
 *		2014/01/18		default b_parentsMarried to 'Y'					*
 *		2014/10/10		add country code to default location			*
 *						and support all domains							*
 *		2014/12/06		send trace info to $warn						*
 *		2015/01/23		ensure that B_CalcBirth is updated when			*
 *						B_BirthDate is set even if the value of			*
 *						B_BirthDate is invalid							*
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
				'b_regdomain'			=> 'CAON',
				'b_regyear'				=> 2014,
				'b_regnum'				=> 0,
				'b_regcounty'			=> '',
				'b_regtownship'			=> '',
				'b_msvol'				=> 0,
				'b_surname'				=> '',
				'b_surnamesoundex'		=> '',
				'b_givennames'			=> '',
				'b_sex'					=> '?',
				'b_birthplace'			=> '',
				'b_birthdate'			=> '',
				'b_calcbirth'			=> null,
				'b_parentsmarried'		=> 'Y',
				'b_fathername'			=> '',
				'b_fatheroccupation'	=> '',
				'b_fatheroccplace'		=> '',
				'b_mothername'			=> '',
				'b_motheroccupation'	=> '',
				'b_motheroccplace'		=> '',
				'b_formerhusband'		=> '',
				'b_marriageplace'		=> '',
				'b_marriagedate'		=> '',
				'b_accoucheur'			=> '',
				'b_informant'			=> '',
				'b_informantres'		=> '',
				'b_informantrel'		=> '',
				'b_regdate'				=> '',
				'b_registrar'			=> '',
				'b_remarks'				=> '',
				'b_image'				=> '',
				'b_originalvolume'		=> 0,
				'b_originalpage'		=> 0,
				'b_originalitem'		=> 0,
				'b_idir'				=> 0,
				'b_changedby'			=> '',
				'b_changedate'			=> '');

    /********************************************************************
     *	Birth::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Births',
                'name'      => 'Births',
                'prime'	    => array('b_regdomain','b_regyear','b_regnum'),
	            'srvmg'     => false,
	            'fldcount'	=> 34,
                'order'     => 'B_RegDomain, B_RegYear, B_RegNum',
                'classname' => 'Birth');

    /********************************************************************
     *		Birth::$translate												*
     *																		*
     *		standard alternative field name identifiers						*
     ********************************************************************/
    protected static $translate	= array(
				'regdomain'				=> 'b_regdomain',
				'domain'				=> 'b_regdomain',
				'regyear'				=> 'b_regyear',
				'year'					=> 'b_regyear',
				'regnum'				=> 'b_regnum',
				'num'					=> 'b_regnum',
				'regcounty'				=> 'b_regcounty',
				'county'				=> 'b_regcounty',
				'regtownship'			=> 'b_regtownship',
				'township'				=> 'b_regtownship',
				'msvol'					=> 'b_msvol',
				'surname'				=> 'b_surname',
				'surnamesoundex'		=> 'b_surnamesoundex',
				'givennames'			=> 'b_givennames',
				'sex'					=> 'b_sex',
				'birthplace'			=> 'b_birthplace',
				'place'					=> 'b_birthplace',
				'birthdate'				=> 'b_birthdate',
				'date'					=> 'b_birthdate',
				'calcbirth'				=> 'b_calcbirth',
				'parentsmarried'		=> 'b_parentsmarried',
				'fathername'			=> 'b_fathername',
				'father'				=> 'b_fathername',
				'fatheroccupation'		=> 'b_fatheroccupation',
				'fatheroccplace'		=> 'b_fatheroccplace',
				'mothername'			=> 'b_mothername',
				'mother'				=> 'b_mothername',
				'motheroccupation'		=> 'b_motheroccupation',
				'motheroccplace'		=> 'b_motheroccplace',
				'formerhusband'			=> 'b_formerhusband',
				'marriageplace'			=> 'b_marriageplace',
				'marriagedate'			=> 'b_marriagedate',
				'accoucheur'			=> 'b_accoucheur',
				'informant'				=> 'b_informant',
				'informantname'			=> 'b_informant',
				'informantres'			=> 'b_informantres',
				'informantrel'			=> 'b_informantrel',
				'regdate'				=> 'b_regdate',
				'registrar'				=> 'b_registrar',
				'remarks'				=> 'b_remarks',
				'image'					=> 'b_image',
				'originalvolume'		=> 'b_originalvolume',
				'volume'				=> 'b_originalvolume',
				'originalpage'			=> 'b_originalpage',
				'page'					=> 'b_originalpage',
				'originalitem'			=> 'b_originalitem',
				'item'					=> 'b_originalitem',
				'idir'					=> 'b_idir',
				'changedby'				=> 'b_changedby',
				'changedate'			=> 'b_changedate');

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

		$needInsert	    = false;
		$this->table	= 'Births';
		$cc		        = 'CA';		    // ISO country code
		$pc		        = 'ON';		    // postal service state/province code
		$addrEnd	    = ', ON, CA';	// last part of addresses

		// validate parameters
		if (is_string($domain))
		{		            // initialize new entry
		    $domainObj	    = new Domain(array('domain'	    => $domain,
                                               'language'	=> 'en'));
            $parms          = array('b_domain'              => $domainObj,
                                    'b_regyear'             => $year,
                                    'b_regnum'              => $number);
        }                   // is_string($domain)
        else
        if ($domain instanceof Domain)
        {
            $domainObj      = $domain;
            $domain         = $domainObj->get('domain');
            $parms          = array('b_domain'              => $domainObj,
                                    'b_regyear'             => $year,
                                    'b_regnum'              => $number);
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
	                case 'b_regdomain':
	                {
						if (is_string($value))
						{		            // initialize new entry
                            $domainObj	   
                                = new Domain(array('domain'	    => $domain,
				                                   'language'	=> 'en'));
				        }                   // is_string($domain)
				        else
				        if ($domain instanceof Domain)
				        {
			                $domainObj      = $domain;
			                $domain         = $domainObj->get('domain');
                        }                   // $domain instanceof Domain
                        break;
                    }

				    case 'b_regyear':
                    {
                        $year               = $value;
                        break;
                    }

				    case 'b_regnum':
                    {
                        $number             = $number;
                        break;
                    }
                }	            	// act on specific field
            }                       // loop through parameters

		    if ($domainObj->isExisting())
		    {		        // valid domain
				$cc		    = substr($domain, 0, 2);
				$pc		    = substr($domain, 2);
		    }		        // valid domain
		    else
				$this->msg		.= "Birth::__construct: " .
						"Domain '$domain' must be a supported two character country code followed by a 2 or 3 character character state or province code. ";

		    // determine common ending of default addresses
		    $addrEnd	                    = ", $pc, $cc";

		    if (is_string($year) && ctype_digit($year))
				$year	                    = (int)$year;
		    else
		    if (!is_int($year) || $year < 1800 || $year > 2100)
				$this->msg		.= "Birth::__construct: registration year '$year' must be a numeric year. ";
		    
		    if (is_string($number) && ctype_digit($number))
				$number	                    = (int)$number;
		    else
		    if (!is_int($number) || $number < 1)
				$this->msg		.= "Birth::__construct: registration number '$number' must be a positive integer. ";

		    // search for an existing record matching the key
		    $query	            = "SELECT * FROM Births 
            							WHERE B_RegDomain=:domain AND 
			            					  B_RegYear=:year AND
						            		  B_RegNum=:number";
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
				    $dbrow['b_regdomain']	= $domain;
				    $dbrow['b_regyear']		= $year;
				    $dbrow['b_regnum']		= $number;
				    $dbrow['b_birthdate']	= $year;
				    $dbrow['b_regdate']		= $year;

				    // fill in some fields from the preceding record, 
				    // if it exists
				    $queryPrev	= 'SELECT B_RegCounty, B_RegTownship, B_MsVol,'.
								  'B_Registrar, B_RegDate, B_Image,' .
						  'B_OriginalVolume, B_OriginalPage, B_OriginalItem' .
							       ' FROM Births ' .
						  	       ' WHERE B_RegDomain=:domain AND ' .
									'B_RegYear=:year AND ' .
									'B_RegNum<:number' .
							       ' ORDER BY B_RegNum DESC' .
							       ' LIMIT 1';

				    $stmt	            = $connection->prepare($queryPrev);
			        $queryText	        = debugPrepQuery($queryPrev, $sqlParms);
                    $this->lastSqlCmd   = $queryText;
				    if ($stmt->execute($sqlParms))
				    {		// successful query
						if ($debug)
                            $warn .= '<p>' .
                                     debugPrepQuery($queryPrev, $sqlParms) .
                                     "</p>\n";

						$result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (count($result) > 0)
						{		// a preceding record exists
						    $row			            = $result[0];
						    $row['b_regdomain']		    = $domain;
						    $regCounty			= $row['b_regcounty'];
						    $dbrow['b_regcounty']	    = $regCounty;
						    $regTownship		= $row['b_regtownship'];
						    $dbrow['b_regtownship']	    = $regTownship;
						    $dbrow['b_msvol']		    = $row['b_msvol'];
						    $dbrow['b_registrar']	    = $row['b_registrar'];
						    $dbrow['b_image']		    = $row['b_image'];
						    $volume	            = $row['b_originalvolume'];
						    $dbrow['b_originalvolume']	= $volume;
						    $page	            = $row['b_originalpage'];
						    $dbrow['b_originalpage']	= $page;
						    $item	            = $row['b_originalitem'] + 1;
						    $dbrow['b_originalitem']	= $item + 1;
						    if (strlen($row['b_regdate']) > 0)
							    $dbrow['b_regdate']	= $row['b_regdate'];

						    // initialize places with default value
						    $dftPlace		= $this->getDefaultPlace($row);
						    $dbrow['b_birthplace']	    = $dftPlace;
						    $dbrow['b_fatheroccplace']	= $dftPlace;
						    $dbrow['b_motheroccplace']	= $dftPlace;
						    $dbrow['b_informantres']	= $dftPlace;
						    if ($year < 1908)
							    $dbrow['b_marriageplace']	= 'N/A';
						    else
							    $dbrow['b_marriageplace']	= $dftPlace;
						}	// a preceding record exists

						if ($number > 1000000)
						{
						    $origVol	= floor($number/1000000);
						    $origPage	= floor($number/100) % 1000;
						    $origItem	= $number % 100;
						    $dbrow['b_originalvolume']	= $origVol;
						    $dbrow['b_originalpage']	= $origPage;
						    $dbrow['b_originalitem']	= $origItem;
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
		$domain			= $row['b_regdomain'];
		$cc			= substr($domain, 0, 2);
		$pc			= substr($domain, 2);
		$addrEnd		= ", $pc, $cc";

		$regCounty		= $row['b_regcounty'];
		$regTownship		= $row['b_regtownship'];
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
		    case 'b_surnamesoundex':
		    case 'b_changedby':
		    case 'b_changedate':
		    case 'b_calcbirth':
		    {               // fields whose value is set from another field
				throw new Exception(
						"Birth::set: cannot modify '$field'");
		    }               // fields whose value is set from another field

		    case 'b_birthdate':
		    {
				$date	= new LegacyDate(" $value");
				$y	    = $date->getYear();
				// fixup missing or abbreviated birth years
				if ($y == 0)
				    $y	= $this->row['b_regyear'];
				else
				if ($y < 1000)
				{
				    $regYear	= $this->row['b_regyear'];
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
                
				parent::set('b_calcbirth', "$y-$m-$d");
				return parent::set($fieldLc, $value);
		    }

		    case 'b_parentsmarried':
		    {
				if ($value == '' || $value == 'n' || $value == 'N')
				    return parent::set($fieldLc, 'N');
				else
				    return parent::set($fieldLc, 'Y');
		    }

		    case 'b_regcounty':
		    case 'b_regtownship':
		    case 'b_surname':
		    case 'b_givennames':
		    case 'b_sex':
		    case 'b_birthplace':
		    case 'b_birthdate':
		    case 'b_fathername':
		    case 'b_fatheroccupation':
		    case 'b_fatheroccplace':
		    case 'b_mothername':
		    case 'b_motheroccupation':
		    case 'b_motheroccplace':
		    case 'b_formerhusband':
		    case 'b_marriageplace':
		    case 'b_marriagedate':
		    case 'b_accoucheur':
		    case 'b_informant':
		    case 'b_informantres':
		    case 'b_informantrel':
		    case 'b_regdate':
		    case 'b_registrar':
		    case 'b_remarks':
		    case 'b_image':
		    {
				return parent::set($fieldLc, $value);
		    }

		    case 'b_msvol':
		    case 'b_idir':
		    case 'b_originalvolume':
		    case 'b_originalpage':
		    case 'b_originalitem':
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
     *	function Birthd::save											*
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
            parent::set('b_changedby', $userid);
            parent::set('b_changedate', 
                        $now['year'] . '-' .  $now['mon'] . '-' . $now['mday']);
            parent::save($xml);
        }                   // record needs to be saved
        else
            return 0;
    }       // function Birth::save

}		// class Birth 
