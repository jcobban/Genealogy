<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Marriage.inc														*
 *																		*
 *  Definition of a class representing a marriage registration			*
 *  in a genealogical database.  This class provides access to the		*
 *  information in a row of the table Marriage.							*
 *																		*
 *		CREATE TABLE `Marriage` (										*
 *		    `M_RegDomain` VARCHAR(4) NOT NULL DEFAULT 'CAON',			*
 *		    `M_RegYear` INT(11) NOT NULL,								*
 *		    `M_RegNum` INT(11) NOT NULL,								*
 *		    `M_MsVol` VARCHAR(5) DEFAULT '',							*
 *		    `M_RegCounty` CHAR(3) DEFAULT '',							*
 *		    `M_RegTownship` VARCHAR(64) DEFAULT NULL,					*
 *		    `M_Date` VARCHAR(16) DEFAULT '',							*
 *		    `M_CalcDate` DATE DEFAULT NULL,								*
 *		    `M_Place` VARCHAR(128) DEFAULT NULL,						*
 *		    `M_LicenseType` CHAR(1) DEFAULT 'L',						*
 *		    `M_Remarks` LONGTEXT,										*
 *		    `M_ChangedBy` VARCHAR(64) DEFAULT '',						*
 *		    `M_ChangeDate` DATE DEFAULT NULL,							*
 *		    `M_Image` VARCHAR(256) NOT NULL DEFAULT '',					*
 *		    `M_Registrar` VARCHAR(128) NOT NULL DEFAULT '',				*
 *		    `M_RegDate` VARCHAR(16) NOT NULL DEFAULT '',				*
 *		    `M_OriginalVolume` VARCHAR(16) DEFAULT NULL,				*
 *		    `M_OriginalPage` INT(11) DEFAULT NULL,						*
 *		    `M_OriginalItem` INT(11) DEFAULT NULL,						*
 *		    PRIMARY KEY (`M_RegDomain`,									*
 *		    `M_RegYear`,												*
 *		    `M_RegNum`),												*
 *		    KEY `M_RegCounty` (`M_RegCounty`),							*
 *		    KEY `M_RegTownship` (`M_RegTownship`),						*
 *		    KEY `M_Place` (`M_Place`) )									*
 *		    ENGINE=InnoDB DEFAULT CHARSET=utf8 							*
 *																		*
 *  History:															*
 *		2013/06/10		created											*
 *		2013/08/09		class renamed to Marriage because it has		*
 *						nothing to do with Legacy						*
 *		2014/01/21		complete implementation							*
 *		2014/01/25		missing fields m_calcdate, m_regdate,			*
 *						and m_registrar in default record				*
 *		2014/03/05		dependent MarriageParticipant records not		*
 *						created if invoked with record keys				*
 *						method getGroom returned bride					*
 *		2014/10/10		add country code to default location			*
 *						and support all domains							*
 *						missing include of MarriageParticipant			*
 *		2014/12/26		diagnostic information sent to $warn			*
 *		2015/05/01		default image URL to previous record			*
 *		2015/07/01		unmatched tag in diagnostic output from			*
 *						constructor										*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/02/17		add fields OriginalVolume, OriginalPage, and	*
 *						OriginalItem									*
 *						support searching for record in constructor		*
 *		2017/03/13		permit creating new Marriage given only the		*
 *						pre-1873 volume number, page, and item			*
 *						fill in default year and reel for new pre-1873	*
 *						marriage registrations.							*
 *		2017/07/20		parameter list to logSqlUpdate changed			*
 *		2017/11/07		convert queries to prepared statements			*
 *		2017/12/18		add support for temporary fields				*
 *		2017/12/22		support temporary fields in constructor			*
 *		2018/02/24		numvolume not initialized						*
 *						county, township, etc. not initialized from		*
 *						preceding entry if constructor called with		*
 *						search parms in first parameter					*
 *		2018/05/28		MySQL started objecting to assigning an			*
 *						empty string to an INT, so ensure INT fields	*
 *						set to NULL for empty string					*
 *		2018/12/11      ensure m_calcdate always valid                  *
 *		2019/01/24      add missing method toJson                       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/MarriageParticipant.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/County.inc';

/************************************************************************
 *  Marriage															*
 *																		*
 *  Definition of a class implementing behavior for Marriage			*
 *  registration records within a genealogy database.					*
 ************************************************************************/

class Marriage extends Record
{

    /********************************************************************
     *	Marriage::$initRow												*
     *																	*
     *	static array containing default values for a new record			*
     ********************************************************************/
    protected static	$initRow	= array(
                        'm_regdomain'			=> 'CAON',
                        'm_regyear'				=> 2014,
                        'm_regnum'				=> 0,
                        'm_msvol'				=> '0',
                        'm_regcounty'			=> '',
                        'm_regtownship'			=> '',
                        'm_date'				=> '',
                        'm_calcdate'			=> '0000-00-00',
                        'm_place'				=> '',
                        'm_licensetype'			=> 'L',
                        'm_remarks'				=> '',
                        'm_changedby'			=> '',
                        'm_changedate'			=> '',
                        'm_image'				=> '',
                        'm_registrar'			=> '',
                        'm_regdate'				=> '',
                        'm_originalvolume'		=> '',
                        'm_originalpage'		=> '',
                        'm_originalitem'		=> '');

    /********************************************************************
     *	Marriage::$translate											*
     *																	*
     *	static array containing field name abbreviation translations	*
     ********************************************************************/
    protected static	$translate	= array(
                        'domain'				=> 'm_regdomain',
                        'regdomain'				=> 'm_regdomain',
                        'year'					=> 'm_regyear',
                        'regyear'				=> 'm_regyear',
                        'num'					=> 'm_regnum',
                        'number'				=> 'm_regnum',
                        'regnum'				=> 'm_regnum',
                        'reel'					=> 'm_msvol',
                        'msvol'					=> 'm_msvol',
                        'county'				=> 'm_regcounty',
                        'regcounty'				=> 'm_regcounty',
                        'township'				=> 'm_regtownship',
                        'regtownship'			=> 'm_regtownship',
                        'date'					=> 'm_date',
                        'calcdate'				=> 'm_calcdate',
                        'place'					=> 'm_place',
                        'licensetype'			=> 'm_licensetype',
                        'remarks'				=> 'm_remarks',
                        'changedby'				=> 'm_changedby',
                        'changedate'			=> 'm_changedate',
                        'image'					=> 'm_image',
                        'registrar'				=> 'm_registrar',
                        'regdate'				=> 'm_regdate',
                        'originalvolume'		=> 'm_originalvolume',
                        'originalpage'			=> 'm_originalpage',
                        'originalitem'			=> 'm_originalitem');

    /********************************************************************
     *	Marriage::$defaultOrder												*
     *																	*
     *	Definition of default order for records retrieved by RecordSet		*
     ********************************************************************/
    protected static	$defaultOrder	= 'M_RegDomain, M_RegYear, M_RegNum';

    /********************************************************************
     *	Marriage::$pre1873Years												*
     *																	*
     *	static array containing default values for registration year		*
     *	by volume number for pre-1873 marriage registrations				*
     ********************************************************************/
    static	$pre1873Years	= array(
                                 0	=> array('year' => 1869, 'reel' => 1),
                                 1	=> array('year' => 1869, 'reel' => 1),
                                 2	=> array('year' => 1869, 'reel' => 1),
                                23	=> array('year' => 1869, 'reel' => 1),
                                 3	=> array('year' => 1869, 'reel' => 1),
                                 4	=> array('year' => 1870, 'reel' => 2),
                                 5	=> array('year' => 1870, 'reel' => 2),
                                 6	=> array('year' => 1870, 'reel' => 2),
                                 7	=> array('year' => 1870, 'reel' => 3),
                                 8	=> array('year' => 1870, 'reel' => 3),
                                 9	=> array('year' => 1870, 'reel' => 3),
                                10	=> array('year' => 1870, 'reel' => 3),
                                11	=> array('year' => 1870, 'reel' => 4),
                                12	=> array('year' => 1871, 'reel' => 4),
                                13	=> array('year' => 1871, 'reel' => 4),
                                14	=> array('year' => 1871, 'reel' => 4),
                                15	=> array('year' => 1871, 'reel' => 5),
                                16	=> array('year' => 1871, 'reel' => 5),
                                17	=> array('year' => 1871, 'reel' => 5),
                                18	=> array('year' => 1871, 'reel' => 5),
                                19	=> array('year' => 1871, 'reel' => 6),
                                20	=> array('year' => 1872, 'reel' => 6),
                                21	=> array('year' => 1872, 'reel' => 6),
                                22	=> array('year' => 1872, 'reel' => 7),
                                //23	=> array('year' => 1872, 'reel' => 7),
                                24	=> array('year' => 1872, 'reel' => 7),
                                25	=> array('year' => 1872, 'reel' => 8),
                                26	=> array('year' => 1872, 'reel' => 8),
                                27	=> array('year' => 1872, 'reel' => 8),
                                28	=> array('year' => 1872, 'reel' => 8),
                                29	=> array('year' => 1872, 'reel' => 9),
                                30	=> array('year' => 1872, 'reel' => 9),
                                31	=> array('year' => 1872, 'reel' => 9));

    /********************************************************************
     *	$participants													*
     *																	*
     *  Array of instances of MarriageParticipant for each				*
     *	participant in the marriage (Bride, Groom, Minister).			*
     ********************************************************************/
    protected	$participants	= array();

    /********************************************************************
     *	function Marriage::__construct									*
     *																	*
     *  Create an instance of a class that describes a					*
     *	marriage registration											*
     *																	*
     *  Input:															*
     *	    $domain		 		4 char administrative domain			*
     *							(2 char country code + state/prov code)	*
     *							or an associative array obtained from	*
     *							an SQL query by a static method			*
     *	    $year				registration year						*
     *	    $number				registration number within year			*
     *																	*
     *  Returns object containing marriage record.						*
     *																	*
     *  Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($domain,
                         $year	= null,
                         $number= null)
    {
        global  $debug;
        global  $warn;
        global  $connection;
        global  $msg;

        $needInsert	        = false;
        $this->table	    = 'Marriage';
        $cc		            = 'CA';		// ISO country code
        $pc		            = 'ON';		// postal service state/province code
        $addrEnd	        = ', ON, CA';	// last part of addresses
        $sqlParms	        = array();

        // validate parameters
        if (is_string($domain) || $domain instanceof Domain)
        {		// initialize new entry
            $parms	            = array('m_regdomain'	=> $domain,
                                        'm_regyear'	    => $year,
                                        'm_regnum'	    => $number);
        }
        else
        if (is_array($domain))
            $parms	            = $domain;
        else
            $this->msg          .= "Invalid parameter " . 
                                    print_r($domain, true) . '. ';

        if (count($parms) >= count(self::$initRow))
        {			// row returned from database query
            $dbrow		        = $parms;
            foreach($dbrow as $field => $value)
            {		// loop through fields
                $fieldLc	    		= strtolower($field);
                if (!array_key_exists($fieldLc, self::$initRow))
                {		// move any extra fields to extras
                    $this->extras[$fieldLc]	= $value;
                    unset($dbrow[$field]);
                }		// move any extra fields to extras
            }		// loop through fields

            $mregdomain	        		= $dbrow['m_regdomain'];
            $mregyear	        		= $dbrow['m_regyear'];
            $mregnum	        		= $dbrow['m_regnum'];

            $needInsert	        		= false;
        }		// row returned from database query
        else
        {		// search parameters
            $sel			    		= '';
            $and			    		= 'WHERE ';
            $sqlParms			        = $parms;
            $year		    	        = 0;
            $number		    	        = 0;
            $domain		    	        = 'CAON';
            $domainObj			        = null;
            $numvolume			        = null;
            foreach($parms as $field => $value)
            {
                $value			= trim($value);
                if (strlen($value) == 0)
                    continue;
                $lcfldname		        = strtolower($field);
                if (array_key_exists($lcfldname, self::$translate))
                    $lcfldname		    = self::$translate[$lcfldname];
                switch($lcfldname)
                {
                    case 'm_originalvolume':
                    {
                        if (ctype_digit($value))
                            $numvolume	= $value;
                        else
                        if ($value == '2/3')
                            $numvolume	= 23;
                        else
                        if ($value >= 'A' and $value <= 'Z')
                        {
                            $numvolume	= ord($value) - ord('A') + 1;
                        }
                        else
                        {
                            error_log("Marriage.inc: " . __LINE__ .
                                        " m_originalvolume='$value'\n");
                        }
                        $number		    += $numvolume * 10000;
                        break;
                    }

                    case 'm_originalpage':
                    {
                        $number		    += $value * 10;
                        break;
                    }

                    case 'm_originalitem':
                    {
                        $number		    += $value;
                        break;
                    }

                    case 'm_regdomain':
                    {
                        if ($value instanceof Domain)
                        {
                            $domainObj	= $value;
                            $domain	    = $domainObj->get('code');
                        }
                        else
                        {
                            $domain	    = $value;
                            $domParms	= array('domain'	=> $domain,
                                        'language'	=> 'en');
                            $domainObj	= new Domain($domParms);
                        }
                        if ($domainObj->isExisting())
                        {		// valid domain
                            $cc		    = substr($domain, 0, 2);
                            $pc		    = substr($domain, 2);
                            $addrEnd	= ", $pc, $cc";
                        }
                        else
                            $warn	    .= "<p>Marriage.inc: " . __LINE__ .
                                           " invalid domain id '$domain'</p>\n";
                        $mregdomain	    = $domain;
                        break;
                    }

                    case 'm_regyear':
                    {
                        $mregyear	    = $value;
                        $year		    = $value;
                        if (is_string($year) && ctype_digit($year))
                            $year	    = (int)$year;
                        else
                        if (!is_int($year) || $year < 1800 || $year > 2100)
                            $warn       .= "<p>Marriage::__construct: " . 
                                            __LINE__ .
                    "registration year '$year' must be a numeric year</p>\n";
                        break;
                    }

                    case 'm_regnum':
                    {
                        $mregnum	    = $value;
                        $number		    = $value;
                        if (is_string($number) && ctype_digit($number))
                            $number	    = (int)$number;
                        else
                        if (!is_int($number) || $number < 1)
                            $warn	    .= "Marriage::__construct: " .
                                           __LINE__ .
        "registration number '$number' must be a positive integer</p>\n";

                        break;
                    }

                }

                $sel	                .= $and . $lcfldname . '=:' . $field;
                $and	                = ' AND ';
            }

            // query the database
            $query	                    = "SELECT * FROM Marriage $sel";
            $stmt	                    = $connection->prepare($query);
            $queryText	                = debugPrepQuery($query, $sqlParms);
            if ($stmt->execute($sqlParms))
            {		// successful query
                $result		            = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($debug)
                {
                    $warn	.= "<p>Marriage.inc: " . __LINE__ .
                               " query='$queryText'</p>\n";
                    $warn	.= "<p>Marriage.inc: returned " . count($result) . 
                                   " records</p>\n";
                }

                if (count($result) > 0)
                {		// existing record
                    $dbrow	            = $result[0];
                    $mregdomain	        = $dbrow['m_regdomain'];
                    $mregyear	        = $dbrow['m_regyear'];
                    $mregnum	        = $dbrow['m_regnum'];
                    $needInsert	        = false;
                }		// existing record
                else
                {		// create a new record
                    if ($year == 0 && $numvolume > 0)
                    {
                        $year	= self::$pre1873Years[$numvolume]['year'];
                        $msvol	= self::$pre1873Years[$numvolume]['reel'];
                    }
                    else
                        $msvol	            = '';
                    // set defaults
                    $dbrow			        = self::$initRow;
                    $dbrow['m_regdomain']	= $domain;
                    $dbrow['m_regyear']		= $year;
                    $dbrow['m_regnum']		= $number;
                    $dbrow['m_date']		= $year;
                    $dbrow['m_regdate']		= $year;
                    $dbrow['m_msvol']		= $msvol;
                    $mregdomain		    	= $domain;
                    $mregyear		    	= $year;
                    $mregnum		    	= $number;

                    // fill in some fields from the preceding record, 
                    // if it exists
                    $query	= "SELECT M_RegCounty,M_RegTownship,M_MsVol," .
                                  "M_OriginalVolume,M_OriginalPage,M_OriginalItem," .
                                  "M_Registrar,M_RegDate,M_Image " .
                                    "FROM Marriage  " .
                                    "WHERE M_RegDomain=:domain AND   " .
                                          "M_RegYear=:year AND  " .
                                          "M_RegNum<:number " .
                                    "ORDER BY M_RegNum DESC " .
                                    "LIMIT 1";

                    $sqlParms	        = array('domain'	=> $domain,
                                                'year'		=> $year,
                                                'number'	=> $number);
                    $stmt	            = $connection->prepare($query);
                    $queryText	        = debugPrepQuery($query, $sqlParms);
                    if ($stmt->execute($sqlParms))
                    {		// successful query
                        if ($debug)
                        {
                            $warn	    .= '<p>Marriage.inc: ' . __LINE__ .
                                           " query='$queryText'</p>\n";
                        }

                        $result		= $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($result))
                        {		// a preceding record exists
                            $row                        = $result;
                            $row['m_regdomain']		    = $domain;
                            $regCounty			        = $row['m_regcounty'];
                            $dbrow['m_regcounty']	    = $regCounty;
                            $regTownship		        = $row['m_regtownship'];
                            $dbrow['m_regtownship']	    = $regTownship;
                            $dbrow['m_msvol']		    = $row['m_msvol'];
                            $dbrow['m_registrar']	    = $row['m_registrar'];
                            $dbrow['m_image']		    = $row['m_image'];
                            $dbrow['m_originalvolume']	= $row['m_originalvolume'];
                            $dbrow['m_originalpage']	= $row['m_originalpage'];
                            $dbrow['m_originalitem']	= $row['m_originalitem'] + 1;
                            if (strlen($row['m_regdate']) > 0)
                                $dbrow['m_regdate']	    = $row['m_regdate'];

                            // initialize places with default value
                            $dftPlace		    = $this->getDefaultPlace($row);
                            $dbrow['m_place']	= $dftPlace;
                        }	// a preceding record exists
                        $needInsert		        = true;
                    }		// successful query
                    else
                    {		// error issuing query
                        if (strlen($queryText) > 40)
                            $queryText	= substr($queryText, 0, 30) . '...' .
                                      substr($queryText, -7);
                        $this->msg  .= "'$queryText' " .
                            print_r($connection->errorInfo(),true) .
                            '. ';
                    }		// error issuing query

                    // apply any other fields
                    foreach($parms as $field => $value)
                    {
                        $lcfldname		    = strtolower($field);
                        if (array_key_exists($lcfldname, self::$translate))
                            $lcfldname		= self::$translate[$lcfldname];
                        $dbrow[$lcfldname]	= $value;
                    }

                    $needInsert		= true;
                }		// create a new record
            }			// successful query
            else
            {
                $this->msg  .=  "Marriage.inc: " . __LINE__ .
                            " query='$queryText' query failed: " .
                            print_r($stmt->errorInfo(),true) . '. ';
            }
        }			// search parameters

        // get participant records
        $sel		= "WHERE M_RegDomain=:domain AND " .
                                "M_RegYear=:year AND " .
                                "M_RegNum=:number";
        $sqlParms	= array("domain"	=> $mregdomain,
                                "year"		=> $mregyear,
                                "number"	=> $mregnum);
        $pquery		= "SELECT * FROM MarriageIndi $sel ORDER BY M_Role";
        $pstmt		= $connection->prepare($pquery);
        $pqueryText	= debugPrepQuery($pquery, $sqlParms);
        if ($pstmt->execute($sqlParms))
        {		// query successful
            $presults	        = $pstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($presults as $prow)
            {		// loop through rows
                $participant	= new MarriageParticipant($prow); 
                $role		    = $prow['m_role'];
                $this->participants[$role]	= $participant;
            }		// loop through rows
        }		// query successful
        else
        {		// error issuing query
             $this->msg     .= " query='$pqueryText', " .
                                print_r($pstmt->errorInfo(),true) . ".\n";
        }		// error issuing query

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Marriage');
        $this->needInsert	= $needInsert;

        // diagnostic output if debug is set
        $this->dump('Marriage Record constructed:');
        $msg                .= $this->msg; 
    }		// function Marriage::__construct

    /********************************************************************
     *	function Marriage::getDefaultPlace								*
     *																	*
     *  Get the default place name from the preceding record in the		*
     *	database														*
     *																	*
     *  Parameters:														*
     *	    $name		field name										*
     *																	*
     *  Returns:														*
     *	    string containing default place name constructed from		*
     *	    the registration town/township information in the record	*
     ********************************************************************/
    private function getDefaultPlace($row)
    {
        $domain			= $row['m_regdomain'];
        $cc			= substr($domain, 0, 2);
        $pc			= substr($domain, 2);
        $addrEnd		= ", $pc, $cc";

        $regCounty		= $row['m_regcounty'];
        $regTownship		= $row['m_regtownship'];
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
    }		// Death::getDefaultPlace

    /********************************************************************
     *	function Marriage::toXml										*
     *																	*
     *  Emit the contents of the object as XML.							*
     *																	*
     *  Parameters:														*
     *	    $top		tag name as a string for the tag enclosing the	*
     *					output.											*
     *					If null, do not enclose the individual values	*
     *					inside a root node.								*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *  Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/
    function toXml($top,
                   $print	= true,
                   $options	= 0)
    {
        global	$debug;

        // ensure that there is a label to use for the top element
        if (!is_string($top) || strlen($top) == 0)
            $top	= $this->table;

        if (is_null($top))
            $retval	= '';
        else
            $retval	= "    <$top>\n";
        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 2) == 'm_')
                    $key	= substr($key, 2);
            $retval	.= "\t<$key>" . xmlentities($value) . "</$key>\n";
        }

        // display information about participants in the marriage
        foreach ($this->participants as $role => $participant)
        {		// loop through participants
            if ($role == 'B')
                $retval	.= $participant->toXml('bride', false);
            else
            if ($role == 'G')
                $retval	.= $participant->toXml('groom', false);
            else
            if ($role == 'M')
                $retval	.= $participant->toXml('minister', false);
            else
                $retval	.= $participant->toXml($role, false);
        }		// loop through participants

        if ($top !== null)
            $retval	.= "    </$top>\n";
    }		// Marriage::toXml

    /********************************************************************
     *	function Marriage::toJson										*
     *																	*
     *  Emit the contents of the object as JSON.						*
     *																	*
     *  Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *  Returns:														*
     *	    JSON representation of the object							*
     ********************************************************************/
    function toJson($print      = true,
                    $options	= 0)
    {
        global	$debug;


        $retval	    = '';
        $comma	    = "{\n";
        foreach($this->row as $key => $value)
        {
            if (substr($key, 0, 2) == 'm_')
                $key	= substr($key, 2);
            if (is_numeric($value))
		    {
				if ((strlen($value) == 1 || substr($value, 0, 1) != '0'))
				    $retval		.= $comma . "\t\"$key\":\t$value";
				else
				{		// handle values with leading 0
				    $retval		.= $comma . "\t\"$key\":\t\"$value\"";
				}		// handle values with leading 0
		    }
		    else
		    {
				$retval		.= $comma . "\t\"$key\":\t" .
							   json_encode($value);
		    }
		    $comma		= ",\n";
        }

        // display information about participants in the marriage
        foreach ($this->participants as $role => $participant)
        {		// loop through participants
            if ($role == 'B')
				$retval		.= $comma . "\t\"bride\":\n";
            else
            if ($role == 'G')
				$retval		.= $comma . "\t\"groom\":\n";
            else
            if ($role == 'M')
				$retval		.= $comma . "\t\"minister\":\n";
            else
				$retval		.= $comma . "\t\"$role\":\n";
           
            $retval	.= $participant->toJson(false);
        }		// loop through participants

		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";
		if ($print)
		    print $retval;
		return $retval;

    }		// Marriage::toJson

    /********************************************************************
     *	function Marriage::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change.  Case is ignored.		*
     *	    $value		value to set the field to						*
     *																	*
     *  Returns:														*
     *			previous value of the field								*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
        $lcfldname	= strtolower($field);
        if (array_key_exists($lcfldname, self::$translate))
            $lcfldname	= self::$translate[$lcfldname];
        switch($lcfldname)
        {		// act on specific field
            case 'm_regdomain':
            case 'm_regyear':
            case 'm_regnum':
            case 'm_changedby':
            case 'm_changedate':
            {
                break;
            }

            case 'm_date':
            {
                if (strlen($value) == 0)
                {
                    $y      = $this->row['m_regyear'];
                    $m      = 6;
                    $d      = 1;
                }
                else
                {
                    $date	= new LegacyDate(" $value");
                    $y	    = $date->getYear();
                    $m	    = $date->getMonth();
                    if ($m < 10)
                        $m	= '0' . $m;
                    $d	= $date->getDay();
                    if ($d < 10)
                        $d	= '0' . $d;
                }
                parent::set('m_calcdate',"$y-$m-$d");
                return parent::set($lcfldname, $value);
            }

            case 'm_regcounty':
            case 'm_regtownship':
            case 'm_msvol':
            case 'm_place':
            case 'm_licensetype':
            case 'm_remarks':
            case 'm_regdate':
            case 'm_registrar':
            case 'm_image':
            case 'm_originalvolume':
            case 'm_originalpage':
            case 'm_originalitem':
            {
                return parent::set($lcfldname, $value);
            }

            default:
            {		// other field names
                return parent::set($fieldLc, $value);
            }		// other fields
        }		// act on specific field
    }		// function Marriage::set

    /********************************************************************
     *	function Marriage::getBride										*
     *																	*
     *  Get the instance of MarriageParticipant for the bride.			*
     *																	*
     *  Returns:														*
     *	    instance of MarriageParticipant								*
     ********************************************************************/
    function getBride()
    {
        if (array_key_exists('B', $this->participants))
            return $this->participants['B'];
        else
        {		// create new bride record
            $domain	= $this->row['m_regdomain'];
            $year	= $this->row['m_regyear'];
            $number	= $this->row['m_regnum'];
            $bride	= new MarriageParticipant($domain,
                                              $year,
                                              $number,
                                              'B');
            $this->participants['B']	= $bride;
            return	$bride;
        }		// create new bride record
    }		// Marriage::getBride

    /********************************************************************
     *	function Marriage::getGroom										*
     *																	*
     *  Get the instance of MarriageParticipant for the groom.			*
     *																	*
     *  Returns:														*
     *	    instance of MarriageParticipant								*
     ********************************************************************/
    function getGroom()
    {
        if (array_key_exists('G', $this->participants))
            return $this->participants['G'];
        else
        {		// create new groom record
            $domain	= $this->row['m_regdomain'];
            $year	= $this->row['m_regyear'];
            $number	= $this->row['m_regnum'];
            $groom	= new MarriageParticipant($domain,
                                          $year,
                                          $number,
                                          'G');
            $this->participants['G']	= $groom;
            return	$groom;
        }		// create new groom record
    }		// Marriage::getGroom

    /********************************************************************
     *	function Marriage::getMinister									*
     *																	*
     *  Get the instance of MarriageParticipant for the minister.		*
     *																	*
     *  Parameters:														*
     *		$create				if true create record if necessary		*
     *																	*
     *  Returns:														*
     *	    instance of MarriageParticipant or null						*
     ********************************************************************/
    function getMinister($create = false)
    {
        if (array_key_exists('M', $this->participants))
            return $this->participants['M'];
        else
        if ($create)
        {		// create new minister record
            $domain	= $this->row['m_regdomain'];
            $year	= $this->row['m_regyear'];
            $number	= $this->row['m_regnum'];
            $minister	= new MarriageParticipant($domain,
                                          $year,
                                          $number,
                                          'M');
            $this->participants['M']	= $minister;
            return	$minister;
        }		// create new minister record
        else
            return null;
    }		// Marriage::getMinister

    /********************************************************************
     *	function Marriage::save											*
     *																	*
     *	Save changes made to the Marriage record into the database.		*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format within <cmd> tags.						*
     *					if a string use it as tag name					*
     ********************************************************************/
    function save($xml)
    {
        global	$debug;
        global $warn;
        global	$userid;
        global	$connection;

        if (is_string($xml))
        {			// string tag name
            $tagname	= $xml;
            $xml	= true;
        }			// string tag name
        else
        if ($xml)
        {			// true
            $tagname	= 'cmd';
        }			// true

        if ($this->needInsert)
        {			// create new record
            $fldnames		= '';
            $comma		    = '';
            $values		    = '';
            $sqlParms		= array();
            foreach($this->row as $fld => $value)
            {
                switch($fld)
                {
                    case 'm_changedby':
                    {
                        $fldnames		.= $comma . "`m_changedby`"; 
                        $values			.= $comma . ':userid';
                        $sqlParms['userid']	= $userid;
                        $comma			= ", ";
                        break;
                    }		// M_Userid

                    case 'm_changedate':
                    {
                        $fldnames		.= $comma . "`m_changedate`"; 
                        $values			.= $comma . 'CURDATE()';
                        $comma			= ", ";
                        break;
                    }		// M_ChangeDate

                    case 'm_idir':
                    case 'm_originalpage':
                    case 'm_originalitem':
                    {		// most fields
                        $fldnames		.= $comma . "`$fld`";
                        $shortFld		= substr($fld, 2);
                        $values			.= $comma . ':' . $shortFld;
                        if (is_int($value) || ctype_digit($value))
                            $sqlParms[$shortFld]= $value;
                        else
                            $sqlParms[$shortFld]= null;
                        $comma			= ', ';
                        break;
                    }		// most fields

                    case 'm_calcdate':
                    {
                        if ($value != '0000-00-00')
                        {
	                        $fldnames		    .= $comma . "`$fld`"; 
	                        $values			    .= $comma . ":$fld";
	                        $sqlParms[$fld]		= $value;
	                        $comma			    = ", ";
                        }
                        break;
                    }

                    default:
                    {
                        $fldnames		.= $comma . "`$fld`"; 
                        $values			.= $comma . ":$fld";
                        $sqlParms[$fld]		= $value;
                        $comma			= ", ";
                        break;
                    }		// most fields
                }		// act on specific fields
            }			// loop through all fields in record

            // construct the SQL INSERT command	
            $insert	= "INSERT INTO Marriage ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt	= $connection->prepare($insert);
            $insertText	= debugPrepQuery($insert, $sqlParms);
            if ($stmt->execute($sqlParms))
            {			// successful insert
                $count		= $stmt->rowCount();
                if ($xml)
                    print "<$tagname result='$count'>" . $insertText . 
                            "</$tagname>\n";

                if ($debug)
                {
                    $warn	.= "<p>$insertText</p>\n";
                    $warn	.= "<p>count=$count</p>\n";
                }

                // log the update
                logSqlUpdate($insert,
                             $sqlParms,
                             $count,
                             '',
                             json_encode($this->row));

                // also save associated participant records
                foreach ($this->participants as $role => $participant)
                {			// loop through participants
                    if ($xml)
                    {		// XML output
                        if ($role == 'B')
                            $participant->save('bride');
                        else
                        if ($role == 'G')
                            $participant->save('groom');
                        else
                        if ($role == 'M')
                            $participant->save('minister');
                        else
                            $participant->save($role);
                    }		// XML output
                    else
                        $participant->save(false);
                }			// loop through participants

                // clean up internal state
                $this->changed		= array();	// reset
                $this->needInsert	= false;
            }			// successful insert
            else
            {			// error inserting record
                error_log("Marriage.inc: " . __LINE__ . " $insertText\n");
                if (strlen($insertText) > 40)
                    $insertText	= substr($insertText, 0, 37) . '...'; 
                throw new Exception("Marriage::save: '$insertText', " .
                                    print_r($stmt->errorInfo(),true));
            }			// error inserting record
        }			// create new record
        else
        {			// update existing record
            $update		= "UPDATE Marriage SET ";
            $set		= '';
            $comma		= '';
            $sqlParms		= array();
            foreach($this->changed as $fld => $value)
            {
                switch($fld)
                {		// act on specific fields
                    case 'm_changedby':
                    case 'm_changedate':
                    {		// M_ChangedBy, M_ChangeDate
                        break;
                    }		// M_ChangedBy, M_ChangeDate

                    case 'm_idir':
                    case 'm_originalpage':
                    case 'm_originalitem':
                    {		// most fields
                        $shortFld		= substr($fld, 2);
                        $set			.= $comma . "`$fld`=:$shortFld";
                        if (is_int($value) || ctype_digit($value))
                        {
                            $sqlParms[$shortFld]	= $value;
                        }
                        else
                        {
                            $sqlParms[$shortFld]	= null;
                        }
                        $comma	= ', ';
                        break;
                    }		// most fields

                    default:
                    {		// most fields
                        $set			.= $comma . "`$fld`=:$fld";
                        $sqlParms[$fld]		= $value;
                        $comma			= ', ';
                        break;
                    }		// most fields
                }		// act on specific fields
            }		// loop through all changed fields in record

            // clean up internal state
            $this->changed	= array();	// reset

            if (strlen($set) > 0)
            {		// changes made
                $set	.= ',M_ChangedBy=:userid';
                $set	.= ',M_ChangeDate=CURDATE()';
                $sqlParms['userid']	= $userid;

                // construct the where clause
                if ($this->former)
                {
                    $sqlParms['odomain']	= $this->former['m_regdomain'];
                    $sqlParms['oyear']		= $this->former['m_regyear'];
                    $sqlParms['onumber']	= $this->former['m_regnum'];
                }
                else
                {
                    $sqlParms['odomain']	= $this->row['m_regdomain'];
                    $sqlParms['oyear']		= $this->row['m_regyear'];
                    $sqlParms['onumber']	= $this->row['m_regnum'];
                }

                // assemble the UPDATE command
                $update		.= $set . ' WHERE M_RegDomain=:odomain ' .
                        ' AND M_RegYear=:oyear AND M_RegNum=:onumber';

                // perform the update
                $stmt		= $connection->prepare($update);
                $updateText	= debugPrepQuery($update, $sqlParms);
                if ($stmt->execute($sqlParms))
                {		// successful update
                    $count		= $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>" .
                                $updateText . "</$tagname>\n";

                    if ($debug)
                        $warn	.= "<p>$updateText</p>\n";

                    // log the update
                    logSqlUpdate($update,
                                 $sqlParms,
                                 $count,
                                 json_encode($this->former),
                                 json_encode($this->row));
                }		// successful update
                else
                {		// error updating record
                    error_log("Marriage.inc: " . __LINE__ . " $updateText\n");
                    if (strlen($updateText) > 40)
                        $updateText	= substr($updateText, 0, 37) . '...'; 
                    throw new Exception("Marriage::save:
                                    '$updateText', " .
                                    print_r($stmt->errorInfo(),true));
                }		// error updating record
            }		// changes made

            // also save associated participant records
            foreach ($this->participants as $role => $participant)
            {			// loop through participants
                if ($xml)
                {		// XML output
                    if ($role == 'B')
                        $participant->save('bride');
                    else
                    if ($role == 'G')
                        $participant->save('groom');
                    else
                    if ($role == 'M')
                        $participant->save('minister');
                    else
                        $participant->save($role);
                }		// XML output
                else
                    $participant->save(false);
            }			// loop through participants
        }		// update existing record
    }		// Marriage::save

    /********************************************************************
     *	function Marriage::delete										*
     *																	*
     *  Delete the database records associated with this instance.		*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in			*
     *					XML format										*
     *																    *
     *  Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record		*
     *	    0			if the record did not need to be deleted		*
     ********************************************************************/
    function delete($xml = false)
    {
        global	$debug;
        global $warn;
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

        // get the key of the table rows referenced by this record
        $sqlParms			= array();
        if ($this->former)
        {
            $sqlParms['regdomain']	= $this->former['m_regdomain'];
            $sqlParms['regyear']	= $this->former['m_regyear'];
            $sqlParms['regnum']		= $this->former['m_regnum'];
        }
        else
        {
            $sqlParms['regdomain']	= $this->row['m_regdomain'];
            $sqlParms['regyear']	= $this->row['m_regyear'];
            $sqlParms['regnum']		= $this->row['m_regnum'];
        }

        // delete all associated records
        if (!$this->needInsert)
        {		// only delete if already in database
            $delete	= 'DELETE FROM Marriage ' .
                                   'WHERE M_RegDomain=:regdomain AND ' .
                                     'M_RegYear=:regyear AND ' .
                                     'M_RegNum=:regnum';
            $stmt	= $connection->prepare($delete);
            $deleteText	= debugPrepQuery($delete, $sqlParms);
            if ($stmt->execute($sqlParms))
            {		// successful delete
                $count		= $stmt->rowCount();
                if ($debug)
                    $warn	.= "<p>$deleteText</p>\n";

                if ($xml)
                    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

                // log the update
                logSqlUpdate($delete,
                             $sqlParms,
                             $count,		// compound key
                             json_encode($this->row),
                             '');

                // also delete all participants
                $delete		= 'DELETE FROM MarriageIndi ' .
                                      'WHERE M_RegDomain=:regdomain AND ' .
                                        'M_RegYear=:regyear AND ' .
                                        'M_RegNum=:regnum';
                $stmt		= $connection->prepare($delete);
                $deleteText	= debugPrepQuery($delete, $sqlParms);
                if ($stmt->execute($sqlParms))
                {		// successful delete
                    $count		= $stmt->rowCount();
                    if ($xml)
                        print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
                    if ($debug)
                        $warn	.= "<p>$deleteText</p>\n";

                    // log the update
                    logSqlUpdate($delete,
                                 $sqlParms,
                                 $count,		// compound key
                                 '',			// multiple records
                                 '');

                    // update the memory copy of the record to reflect that it
                    // no longer represents a database record
                    $this->needInsert	= true;
                    return $count;
                }		// successful delete
                else
                {		// error updating record
                    error_log("Marriage.inc: " . __LINE__ . " $deleteText\n");
                    error_log(print_r($stmt->errorInfo(),true) . "\n"); 
                    return false;
                }		// error updating record
            }		// successful delete
            else
            {		// error updating record
                error_log("Marriage.inc: " . __LINE__ . " $deleteText\n");
                error_log(print_r($stmt->errorInfo(),true) . "\n"); 
                return false;
            }		// error updating record
        }		// only delete if already in database
        return 0;	// no records deleted
    }		// Marriage::delete

}		// class Marriage 
