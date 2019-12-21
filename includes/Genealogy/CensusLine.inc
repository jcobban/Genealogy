<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CensusLine.inc														*
 *	    																*
 *  Definition of a class representing a single line from a census of	*
 *  Canada,  This class provides access to the information in a row		*
 *  of the tables Censusyyyy.											*
 *		    															*
 *  This record contains a transcription from the population schedule	*
 *  containing information about an individual.							*
 *			    														*
 *  History:															*
 *		2013/06/02		created											*
 *		2013/06/16		move calculation of birth year into class		*
 *		2013/07/01		implement method delete for Census records		*
 *		2013/08/09		base class renamed to Record					*
 *		2013/08/17		add support for 1921 census						*
 *		2014/10/14		add option on constructor to get last line		*
 *						of preceding page								*
 *						do not fail constructor on missing division		*
 *		2014/12/27		redirect diagnostic information to $warn		*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/29		remove method isValidCensus, functionality		*
 *						moved to class Census							*
 *		2017/08/03		use prepared statements to update and insert	*
 *		2017/10/09		use prepared statements to construct and		*
 *						search.											*
 *		2017/11/18		remove static function getCensusLines			*
 *		2017/11/24		add support for Census CAALL					*
 *						get('district') returns int value if value		*
 *						has no fractional part							*
 *		2017/12/18		add support for temporary fields				*
 *		2018/01/14		get('line') always returns at least 2 digits	*
 *		2018/01/17		get returns an empty string for null values		*
 *						permit changing identification fields			*
 *		2018/01/18		for efficiency permit passing instance of		*
 *						class Census to constructor						*
 *						correct calculation of previous page			*
 *						return 'Y', 'N' for flag fields whose value		*
 *						is '1', or '0' from method get					*
 *		2018/01/29		all census records now contain province field	*
 *		2018/02/23		add NumFamilies and NoFamilies to the fields	*
 *						that return empty string for zero value			*
 *		2018/02/24		correct setting of value for empty string		*
 *		2018/03/10		ensure that previous line has non-empty			*
 *						family number									*
 *		2018/06/06		handle subdistrict array value better			*
 *		2018/11/02      set numeric fields to NULL rather than ''       *
 *		                if the value is zero and not to be displayed    *
 *		                Do not include province in search if the        *
 *		                value in the existing record is null.           *
 *		                Update the province from the District object    *
 *		                if the province is null                         *
 *		                Pass the census object to the District          *
 *		                constructor to avoid creating a new copy        *
 *		2018/11/07      support method setGetModeHTML                   *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/01/19      report constructor errors through message       *
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/12/01      do not fail save on empty string for numeric    *
 *		2019/12/19      use named insertion points in method save       *
 *		                truncate values for short string fields and     *
 *		                warn about truncated values in method set       *
 *															    		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';

/************************************************************************
 *  class CensusLine													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of a census of Canada.					*
 ************************************************************************/

class CensusLine extends Record
{
    /********************************************************************
     *  CensusLine::censusId											*
     *																	*
     *  This field holds the unique key of the census for this record.	*
     ********************************************************************/
    private	$censusId		= 'CA1881';

    /********************************************************************
     *  CensusLine::byear												*
     *																	*
     *  This field holds the base year used to calculate the			*
     *	approximate year of birth of an individual from their age.		*
     *	Since the censuses were always taken in the 2nd quarter of the	*
     *	year this is one year lower than the enumeration year, except	*
     *	for pre-Confederation censuses where the individual was asked	*
     *	how old they would be at their next birthday, not how old		*
     *	they were on the day of the enumeration. 						*
     ********************************************************************/
    private	$byear		= 0;

    /********************************************************************
     *  CensusLine::distObj												*
     *																	*
     ********************************************************************/
    private	$distObj	= null;

    /********************************************************************
     *	CensusLine::$drow1851											*
     *																	*
     *	Default initial row for 1851 census.							*
     ********************************************************************/
    private static $drow1851	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'pagedisp'				=> '',
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'occupation'			=> '',
				'bplace'				=> 'Canada West',
				'religion'				=> '',
				'location'				=> '',
				'age'					=> '',
				'byear'					=> null,
				'sex'					=> '?',
				'mstat'					=> '',
				'negro'					=> '',
				'indian'				=> '',
				'member'				=> 'Y',
				'absent'				=> '',
				'deaf'					=> '',
				'blind'					=> '',
				'lunatics'				=> '',
				'school'				=> '',
				'birth'					=> '',
				'deathsex'				=> '',
				'ageatdeath'			=> '',
				'causeofdeath'			=> '',
				'restype'				=> '',
				'stories'				=> '',
				'numfamilies'			=> '',
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1861											*
     *																	*
     *	Default initial row for 1861 census.							*
     ********************************************************************/
    private static $drow1861	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'pagedisp'				=> '',
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'occupation'			=> '',
				'bplace'				=> 'Canada West',
				'maryear'				=> '',
				'religion'				=> '',
				'location'				=> '',
				'age'					=> '',
				'byear'					=> null,
				'sex'					=> '?',
				'mstat'					=> '',
				'coloured'				=> '',
				'member'				=> 'Y',
				'absent'				=> '',
				'deaf'					=> '',
				'blind'					=> '',
				'lunatic'				=> '',
				'school'				=> '',
				'illiterate'			=> '',
				'birth'					=> '',
				'deathsex'				=> '',
				'ageatdeath'			=> '',
				'causeofdeath'			=> '',
				'restype'				=> '',
				'stories'				=> '',
				'numfamilies'			=> '',
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1871											*
     *																	*
     *	Default initial row for 1871 census.							*
     ********************************************************************/
    private static $drow1871	= array(
				'province'		   		=> '',
				'district'		   		=> 0,
				'subdistrict'			=> 0,
				'division'		   		=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'sex'					=> '?',
				'age'					=> '',
				'byear'					=> null,
				'binyear'				=> '',
				'bplace'				=> 'Ontario',
				'religion'				=> '',
				'origin'				=> '',
				'occupation'			=> '',
				'mstat'					=> '',
				'minyear'				=> '',
				'address'				=> '',
				'remarks'				=> '',
				'school'				=> '',
				'cantread'				=> '',
				'cantwrite'				=> '',
				'deaf'					=> '',
				'blind'					=> '',
				'insane'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1881	        								*
     *																	*
     *	Default initial row for 1881 census.							*
     ********************************************************************/
    private static $drow1881	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'sex'					=> '?',
				'age'					=> '',
				'byear'					=> null,
				'binyear'				=> '',
				'bplace'				=> 'Ontario',
				'religion'				=> '',
				'origin'				=> '',
				'occupation'			=> '',
				'mstat'					=> '',
				'remarks'				=> '',
				'school'				=> '',
				'deaf'					=> '',
				'blind'					=> '',
				'insane'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1891											*
     *																	*
     *	Default initial row for 1891 census.							*
     ********************************************************************/
    private static $drow1891	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'sex'					=> '?' ,
				'age'					=> '',
				'byear'					=> null,
				'mstat'					=> '',
				'relation'				=> '',
				'bplace'				=> 'Ontario',
				'french'				=> '',
				'fathersbplace'			=> 'Ontario',
				'mothersbplace'			=> 'Ontario',
				'religion'				=> '',
				'occupation'			=> '',
				'employer'				=> '',
				'employee'				=> '',
				'unemployed'			=> '',
				'numhands'				=> '',
				'canread'				=> '1',
				'canwrite'				=> '1',
				'deaf'					=> '',
				'blind'					=> '',
				'insane'				=> '',
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1901											*
     *																	*
     *	Default initial row for 1901 census.							*
     ********************************************************************/
    private static $drow1901	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'sex'					=> '?' ,
				'race'					=> 'W' ,
				'relation'				=> '',
				'mstat'					=> '',
				'age'					=> '',
				'bdate'					=> '',
				'byeartxt'				=> '',
				'byear'					=> null,
				'bplace'				=> 'Ontario',
				'bplaceru'				=> 'r',
				'immyear'				=> '',
				'natyear'				=> '',
				'origin'				=> '',
				'nationality'			=> 'Canadian',
				'religion'				=> '',
				'occupation'			=> '',
				'address'				=> '',
				'remarks'				=> '',
				'idir'					=> 0,
				'ownmeans'				=> '',
				'employer'				=> '',
				'employee'				=> '',
				'ownacct'				=> '',
				'facthome'				=> ' ',
				'monthsfact'			=> 0,
				'monthshome'			=> 0,
				'monthsother'			=> 0,
				'incomeemp'				=> 0,
				'incomeoth'				=> 0,
				'monthsschool'			=> 0,
				'canread'				=> '',
				'canwrite'				=> '',
				'spkenglish'			=> 1,
				'spkfrench'				=> '',
				'mothertongue'			=> '',
				'infirmities'			=> '');

    /********************************************************************
     *  CensusLine::$drow1906											*
     *																	*
     *	Default initial row for 1906 census.							*
     ********************************************************************/
    private static $drow1906	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'relation'				=> '',
				'sex'					=> '?' ,
				'mstat'					=> '',
				'age'					=> '',
				'byeartxt'				=> '',
				'byear'					=> null,
				'bplace'				=> 'Ontario',
				'immyear'				=> '',
				'postoffice'			=> '',
				'addrsect'				=> '',
				'addrtwp'				=> '',
				'addrrng'				=> '',
				'addrmdn'				=> '',
				'horses'				=> 0,
				'milkcows'				=> 0,
				'cattle'				=> 0,
				'sheep'					=> 0,
				'pigs'					=> 0,
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1911											*
     *																	*
     *	Default initial row for 1911 census.							*
     ********************************************************************/
    private static $drow1911	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'address'				=> '',
				'sex'					=> '?',
				'relation'				=> '',
				'mstat'					=> '',
				'bdate'					=> '',
				'byeartxt'				=> '',
				'age'					=> '',
				'byear'					=> null,
				'bplace'				=> 'Ontario',
				'immyear'				=> '',
				'natyear'				=> '',
				'origin'				=> '',
				'nationality'			=> 'Canadian',
				'religion'				=> '',
				'occupation'			=> '',
				'occother'				=> '',
				'employer'				=> '',
				'employee'				=> '',
				'ownacct'				=> '',
				'empwhere'				=> '',
				'wksemp'				=> '',
				'wksoth'				=> '',
				'hpwemp'				=> '',
				'hpwoth'				=> '',
				'incomeemp'				=> '',
				'incomeoth'				=> '',
				'hourlyrate'			=> 0,
				'lifeinsurance'			=> 0,
				'accinsurance'			=> 0,
				'costinsurance'			=> 0,
				'schoolmons'			=> 0,
				'canread'				=> 'Y',
				'canwrite'				=> 'Y',
				'language'				=> 'English',
				'costeducation'			=> 0,
				'blind'					=> '',
				'deaf'					=> '',
				'insane'				=> '',
				'idiot'					=> '',
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1916											*
     *																	*
     *	Default initial row for 1916 census.							*
     ********************************************************************/
    private static $drow1916	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'military'				=> '',
				'addrtwp'				=> '',
				'addrrng'				=> '',
				'addrmdn'				=> '',
				'addrmuni'				=> '',
				'relation'				=> '',
				'sex'					=> '?' ,
				'mstat'					=> '',
				'age'					=> '',
				'byear'					=> null,
				'bplace'				=> 'Ontario',
				'religion'				=> '',
				'immyear'				=> '',
				'natyear'				=> '',
				'nationality'			=> 'Canadian',
				'origin'				=> '',
				'spkenglish'			=> '1',
				'spkfrench'				=> '',
				'spkother'				=> '',
				'canread'				=> '1',
				'canwrite'				=> '1',
				'occupation'			=> '',
				'emptype'				=> '',
				'empwhere'				=> '',
				'remarks'				=> '',
				'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$drow1921											*
     *																	*
     *	Default initial row for 1921 census.							*
     ********************************************************************/
    private static $drow1921	= array(
				'province'				=> '',
				'district'				=> 0,
				'subdistrict'			=> 0,
				'division'				=> '',
				'page'					=> 0,
				'line'					=> 0,
				'family'				=> 1,
				'surname'				=> '',
				'surnamesoundex'		=> '',
				'givennames'			=> '',
				'township'				=> '',
				'address'				=> '',
				'ownertenant'			=> '',
				'houserent'				=> 0,
				'houseclass'			=> '',
				'housematerial'			=> '',
				'houserooms'			=> 0,
				'relation'				=> '',
				'sex'					=> '?',
				'mstat'					=> '',
				'age'					=> '',
				'byear'					=> null,
				'bplace'				=> 'Ontario',
				'fathersbplace'			=> 'Ontario',
				'mothersbplace'			=> 'Ontario',
				'immyear'				=> '',
				'natyear'				=> '',
				'nationality'			=> 'Canadian',
				'origin'				=> '[origin]',
				'spkenglish'			=> 'Y',
				'spkfrench'				=> '',
				'spkother'				=> '',
				'religion'				=> '[religion]',
				'canread'				=> 'Y',
				'canwrite'				=> 'Y',
				'schoolmons'			=> '',
				'occupation'			=> '',
				'emptype'				=> '',
				'empwhere'				=> '',
				'incomeemp'				=> '',
				'unemployed'			=> '',
				'weeksunemp'			=> '',
				'weeksill'				=> '',
				'remarks'				=> '',
                'idir'					=> 0);

    /********************************************************************
     *  CensusLine::$defaultRows										*
     *																	*
     *  table of initial rows based upon census code					*
     ********************************************************************/
    private static $defaultRows	= array();

    /********************************************************************
     *  function CensusLine::init   	        						*
     *																	*
     *	Initialize CensusLine::$defaultRows                             *
     ********************************************************************/
    static function init()
    {
		self::$defaultRows['CW1851']	= self::$drow1851;
		self::$defaultRows['CE1851']	= self::$drow1851;
		self::$defaultRows['NB1851']	= self::$drow1851;
		self::$defaultRows['NS1851']	= self::$drow1851;
		self::$defaultRows['PI1851']	= self::$drow1851;
		self::$defaultRows['CW1861']	= self::$drow1861;
		self::$defaultRows['CE1861']	= self::$drow1861;
		self::$defaultRows['NB1861']	= self::$drow1861;
		self::$defaultRows['NS1861']	= self::$drow1861;
		self::$defaultRows['PI1861']	= self::$drow1861;
		self::$defaultRows['CA1871']	= self::$drow1871;
		self::$defaultRows['CA1881']	= self::$drow1881;
		self::$defaultRows['CA1891']	= self::$drow1891;
		self::$defaultRows['CA1901']	= self::$drow1901;
		self::$defaultRows['CA1906']	= self::$drow1906;
		self::$defaultRows['CA1911']	= self::$drow1911;
		self::$defaultRows['CA1916']	= self::$drow1916;
		self::$defaultRows['CA1921']	= self::$drow1921;
    }

    /********************************************************************
     *  function CensusLine::getInitRow	        						*
     *																	*
     *	Provide read-only access to the table CensusLine::$defaultRows	*
     *																	*
     *  Returns:														*
     *	    An associative array containing the initial field values	*
     *	    for a row for the specified table name.						*
     ********************************************************************/
    public static function getInitRow($tableName)
    {
		$censusYear	= substr($tableName,6);
		if ($censusYear > 1867)
		    $censusId	= 'CA' . $censusYear;
		else
		    $censusId	= 'CW' . $censusYear;
		return self::$defaultRows[$censusId];
    }		// function getInitRow

    /********************************************************************
     *  CensusLine::$translate											*
     *																	*
     *	Field name synonyms												*
     ********************************************************************/
    protected static $translate	= array(
				'prov'			    => 'province',
				'distid'		    => 'district',
				'subdistid'		    => 'subdistrict',
				'div'			    => 'division',
				'givenname'		    => 'givennames',
				'birthplace'		=> 'bplace');

    /********************************************************************
     *  CensusLine::$defaultOrder										*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	=
                            'district,subdistrict,division,page,line';

    /********************************************************************
     *  function CensusLine::__construct								*
     *																	*
     *  Construct an instance of CensusLine based upon the				*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameters				*
     *					identifying the specific row.					*
     *					 'Census'		census identifier or			*
     *									instance of class Census		*
     *					 'District'		district identifier				*
     *					 'SubDistrict'	sub-district identifier			*
     *					 'Division'		division identifier				*
     *					 'Page'			page number						*
     *					 other			override default value			*
     *		$line		line number on page.  If 0 returns the last		*
     *					line on the preceding page, if any				*
     *					can also be set by $parms['line']				*
     *					or a row from the table identified by			*
     *					$parms['Census']								*
     *																	*
     *  Returns:                                                        *
     *      object containing a transcription of a census line.		    *
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms,
						 $line		= null)
    {
		global $debug;
		global $warn;
		global $msg;
		global $connection;

		$dbrow				= null;
		$needInsert			= false;
		$censusId			= null;
		$distID				= null;
		$subDistID			= null;
		$div				= null;
		$page				= null;

		if ($debug)
		{
		    $warn	        .= "<p>CensusLine::__construct(\$parms=";
		    $comma	        = '[';
		    foreach($parms as $key => $value)
		    {
				if ($value instanceof Census)
				    $warn	.= "$comma$key= Census('" .
								   $value->get('censusid') . "')";
				else
				if (is_numeric($value)) 
				    $warn	.= "$comma$key=$value";
				else
				if (is_string($value)) 
				    $warn	.= "$comma$key='$value'";
				else
				    $warn	.= $comma . $key . '=' . print_r($value, true);
				$comma		= ',';
		    }
		    $warn	        .= ']';
		    if (isset($line)) 
				$warn	.= ", \$line=" . print_r($line, true);
		    $warn	.= ")</p>\n";
		}

		foreach($parms as $key => $value)
		{			// loop through all parms
		    switch(strtolower($key))
		    {			// act on identification parms
				case 'census':
				case 'censusid':
				{
				    if (is_string($value))
				    {			// string
						$censusId	    = $value;
						$this->censusId	= $censusId;
						$censusObj	    = new Census(array('censusid' => $censusId));
						if ($censusObj->isExisting())
						{			// valid census identifier
						    $censusYear	= intval(substr($censusId, 2));
						    $table	= 'Census' . $censusYear;
						    $this->table= $table;
						    if ($censusYear < 1867)
								$province	= substr($censusId, 0, 2);
						}			// valid census identifier
						else
						    $this->msg	.= "Invalid Census='$censusId'";
				    }			// string
				    else
				    if ($value instanceof Census)
				    {
						$censusObj	    = $value;
						$censusId	    = $censusObj->get('censusid');
						$this->censusId	= $censusId;
						$censusYear	    = intval(substr($censusId, 2));
						$table		    = 'Census' . $censusYear;
						$this->table	= $table;
						if ($censusYear < 1867)
						    $province	= substr($censusId, 0, 2);
				    }
				    else
						$this->msg	.= "Invalid Census=" . print_r($value, true);
				    break;
				}

				case 'district':
				{
				    $distID		= $value;
				    break;
				}

				case 'subdistrict':
				{
				    if (is_array($value))
				    {
						if (count($value) == 1)
						    $subDistID		= $value[0];
						else
						    throw new Exception('CensusLine.inc: ' .
						    'SubDistrict value ' . print_r($value, true));
				    }
				    else
				        $subDistID		= $value;
				    break;
				}

				case 'division':
				{
				    $div		= $value;
				    break;
				}

				case 'page':
				{
				    $page		= $value;
				    break;
				}

				case 'line':
				{		// line number passed in first parm
				    if (is_null($line))
						$line		= trim($value);
				    break;
				}		// line number passed in first parm

		    }			// act on identification parms
		}			// loop through all parms

        if ($censusId == null)
        {
            showTrace();
            $this->msg            	.= "Missing parameter Census. ";
            print "<p>new Census_Line(" . print_r($parms, true) . ")</p>\n";
            throw new Exception("Missing parameter census");
        }

		if (is_array($line))
		{			// $line is a record
		    $dbrow		        = $line;
		    $needInsert	    	= false;
		}			// $line is a record
		else
		if (is_string($line) || is_int($line))
		{			// $line is a line number
		    $line		            = intval($line);
		    if ($distID == null)
				$this->msg		        .= "Missing parameter District. ";
		    else
		    {			// have district parameter
				if ($distID instanceof District)
				{
				    $this->distObj	= $distId;
				    $distID	    	= $this->distObj->get('id');
				}
				else
				{
				    $distParms		= array('census'    => $censusObj,
										    'id'	    => $distID);
				    $this->distObj	= new District($distParms);
				}
				$province		    = $this->distObj->get('province');
		    }			// have district parameter

		    if ($subDistID == null)
				$this->msg	.= "Missing parameter SubDistrict. ";
		    if ($div == null)
				$div	= '';
		    if ($page == null)
				$this->msg	.= "Missing parameter Page. ";
		    if ($line == null)
				$this->msg	.= "Missing parameter Line. ";
		    else
		    {
				$line		= intval($line);
				// construct the query for an existing record
				$sqlParms	= array('dist'		=> $distID,
									'subdist'	=> $subDistID,
									'div'		=> $div,
									'page'		=> $page);
				if ($line > 0)
				{			// positive line number
				    $sqlParms['line']	= $line;
				    if (intval($censusYear) < 1867)
				    {
						$sqlParms['province']	= $province;
						$query	= "SELECT * FROM $table 
									WHERE Province=:province AND 
									      District=:dist AND 
									      SubDistrict=:subdist AND
									      Division=:div AND
									      Page=:page AND
									      Line=:line";
				    }
				    else
						$query	= "SELECT * FROM $table 
									WHERE District=:dist AND 
									      SubDistrict=:subdist AND
									      Division=:div AND
									      Page=:page AND
									      Line=:line";
				}			// positive line number
				else
				{			// get last line of previous page
				    $sdParms	        = array('sd_census'	=> $censusId,
							        		    'sd_distid'	=> $distID,
							        		    'sd_id'		=> $subDistID,
							        		    'sd_div'	=> $div);
				    $subDistObj		    = new SubDistrict($sdParms);
				    $tpage		        = $page - $subDistObj->get('bypage');
				    $sqlParms['page']	= $tpage;

				    if (intval($censusYear) < 1867)
				    {			// pre-confederation
						$sqlParms['province']	= $province;
						$query	= "SELECT * FROM $table " .
								"WHERE Province=:province AND " .
									     "District=:dist AND " .
									     "SubDistrict=:subdist AND " .
									     "Division=:div AND " .
									     "Page=:page AND " .
									     "Family!='' " .
									     "ORDER BY Line DESC " .
									     "LIMIT 1";
				    }			// pre-confederation
				    else
				    {			// post-confederation
						$query	= "SELECT * FROM $table " .
								"WHERE District=:dist AND " .
									     "SubDistrict=:subdist AND " .
									     "Division=:div AND " .
									     "Page=:page AND " .
									     "Family!='' " .
									     "ORDER BY Line DESC " .
									     "LIMIT 1";
				    }			// post-confederation
				    $line		= 1;
				}			// get last line of previous page

				// query the database
				$stmt		        = $connection->prepare($query);
				$queryText	        = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
				if ($stmt->execute($sqlParms))
				{			// query successful
				    if ($debug)
						$warn	    .= "<p>CensusLine.inc:" . __LINE__ .
									    " '$queryText'</p>\n";

				    $result		    = $stmt->fetch(PDO::FETCH_ASSOC);
				    if (is_array($result))
				    {		// existing record
						$dbrow			= $result;
						$needInsert		= false;
				    }		// existing record
				    else
				    {		// create a new record
						// set defaults
						$dbrow		= self::$defaultRows[$censusId];
						$dbrow['line']	= $line;
						if (array_key_exists('province', $dbrow))
						    $dbrow['province']	= $province;
						$needInsert		= true;
				    }		// create new record
				}		// query successful
				else
				{		// error performing query
				    throw new Exception("CensusLine::__construct:" .
									" '$queryText', " .
									print_r($stmt->errorInfo(),true));
				}		// error performing query
		    }			// numeric identifier of record
		}			// $line is null or a line number
		else
		{			// invalid parameter
		    $this->msg	.= "CensusLine::__construct: called with Line " .
				 gettype($line) . ", value='" . print_r($line) . "'";
		}			// invalid parameter

		// determine year from which birth year is calculated by census
		if ($censusYear == 1851 &&
		    ($province == 'CW' || $province == 'CE'))
		{		// census delayed 1 year by reorg
		    $this->byear	= 1852;
		}		// census delayed 1 year by reorg
		else
		if ($censusYear < 1867)
		{		// age is at next birthday
		    $this->byear	= $censusYear;
		}		// age is at next birthday
		else
		{		// ages is at time of enumeration
		    $this->byear	= $censusYear - 1;
		}		// ages is at time of enumeration

		// invoke constructor of base class
		parent::__construct($dbrow,
						    $table);
		$this->needInsert	= $needInsert;

		// the parameters may include changed values for some fields
		foreach($parms as $field => $value)
		{	// check for other initialization
		    $fieldLc		= strtolower($field);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	= self::$translate[$fieldLc];
		    if ($fieldLc != 'censusid' && $fieldLc != 'line')
 		parent::set($fieldLc, $value);
		}	// check for other initialization

		// diagnostic output if debug is set
        $this->dump('CensusLine Record constructed:');
        $msg    .= $this->msg;
    }		// CensusLine::__construct

    /********************************************************************
     *  function CensusLine:get											*
     *																	*
     *	Get the value of a field in the object.							*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     ********************************************************************/
    function get($field)
    {
        global $warn;
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'district':
		    {			// District number
				$distId		= $this->row['district'];
				if ($distId == floor($distId))
				    $retval		= intval($distId);
				else
				    $retval		= $distId;
                break;
		    }			// District number

		    case 'censusid':
		    {
				$retval		= $this->censusId;
                break;
		    }

		    case 'line':
		    {			// line number in page
				$line	=  $this->row['line'];
				if (strlen($line) < 2)
				    $retval		= '0' . $line;	// pad to 2 digits
				else
				    $retval		= $line;
                break;
		    }			// line number in page

		    case 'numhands':
            {			// interpretation depends upon employer flag
                if (array_key_exists('numhands', $this->row))
                {
				    $numHands		= $this->row['numhands'];
				    if ($numHands == 0 && $this->row['employer'] == '')
				        $retval		= '';
				    else
                        $retval		= $numHands;
                }
                else
                    $retval		= null;
                break;
		    }			// interpretation depends upon employer flag

		    case 'absent':
		    case 'blind':
		    case 'canread':
		    case 'cantread':
		    case 'cantwrite':
		    case 'canwrite':
		    case 'coloured':
		    case 'deaf':
		    case 'employee':
		    case 'employer':
		    case 'french':
		    case 'illiterate':
		    case 'indian':
		    case 'insane':
		    case 'lunatic':
		    case 'lunatics':
		    case 'member':
		    case 'negro':
		    case 'ownacct':
		    case 'ownmeans':
		    case 'spkenglish':
		    case 'spkfrench':
		    case 'unemployed':
		    {			// yes/no flags represented as char(1)
				$value			= $this->row[$fieldLc];
				if ($value == '1')
				    $retval		= 'Y';
				else
				if ($value == '0')
				    $retval		= 'N';
				else
				    $retval		= $value;
                break;
		    }			// yes/no flags represented as char(1)

		    case 'school':
		    case 'birth':	// birth in last 12 months
		    {			// yes/no flags represented as M/F
				$value			= $this->row[$fieldLc];
				if ($value == '1')
				    $retval		= $this->row['sex'];
				else
				if ($value == '0')
				    $retval		= '';
				else
				    $retval		= $value;
                break;
		    }			// yes/no flags represented as M/F

		    case 'monthsfact':
		    case 'monthshome':
		    case 'monthsother':
		    case 'incomeemp':
		    case 'incomeoth':
		    case 'monthsschool':
		    case 'horses':
		    case 'milkcows':
		    case 'cattle':
		    case 'sheep':
		    case 'pigs':
		    case 'wksemp':
		    case 'wksoth':
		    case 'hpwemp':
		    case 'hpwoth':
		    case 'incomeemp':
		    case 'incomeoth':
		    case 'hourlyrate':
		    case 'lifeinsurance':
		    case 'accinsurance':
		    case 'costinsurance':
		    case 'schoolmons':
		    case 'ownertenant':
		    case 'houserent':
		    case 'houserooms':
		    case 'weeksunemp':
		    case 'weeksill':
		    case 'numfamilies':
		    case 'nofamilies':
		    {			// numeric values only displayed if non-zero
				$value		= $this->row[$fieldLc];
				if ($value == 0)
				    $retval		= '';
				else
				    $retval		= $value;
                break;
		    }			// numeric values only displayed if non-zero
		   
		    default:
		    {			// other fields
                if (array_key_exists($fieldLc, $this->row))
                    $retval     = $this->row[$fieldLc];
                else
                if (array_key_exists($fieldLc, $this->extras))
                    $retval     = $this->extras[$fieldLc];
				else
				    $retval		= null;
                break;
		    }			// other fields
        }
        
        if ($this->getModeHtml)
        {
            if (is_null($retval) || strlen($retval) == 0)
                $retval     = '&nbsp;';
            else
            if (is_string($retval))
                $retval     = htmlspecialchars($retval);
        }
        return $retval;
    }		// CensusLine::get

    /********************************************************************
     *  function CensusLine:set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		global $debug;
		global $warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'numhands':
		    case 'wksemp':
		    case 'wksoth':
		    case 'hpwemp':
		    case 'hpwoth':
		    case 'incomeemp':
		    case 'incomeoth':
		    case 'horses':
		    case 'milkcows':
		    case 'cattle':
		    case 'sheep':
		    case 'pigs':
		    case 'numfamilies':
		    case 'nofamilies':
		    case 'monthsfact':
		    case 'monthshome':
		    case 'monthsother':
		    case 'incomeemp':
		    case 'incomeoth':
		    case 'monthsschool':
            {		            // numeric fields where blank means zero
                if (is_string($value))
                {
                    $matches        = array();
                    $count          = preg_match('/\d+/', $value, $matches);
                    if ($count == 0)
                        $value      = null;
                    else
                        $value		= $matches[0] - 0;
                }
				return parent::set($field, $value);
		    }		            // numeric fields

		    case 'byeartxt':
		    {	                // birth year as text
                if (strlen($value) > 8)
                {
                    $warn   .= "<p>value '$value' for '$field' is truncated</p>\n";
                    $value  = substr($value, 0, 8);
                }
				if (preg_match("/[0-9]{4}/", $value, $matches))
				{	            // 4 digit birth year supplied
				    parent::set('byear', $matches[0]);
				}	            // 4 digit birth year supplied

				return parent::set('byeartxt', $value);
		    }	                // birth year as text

		    case 'age':
		    {	                // age field
                if (strlen($value) > 8)
                {
                    $warn   .= "<p>value '$value' for '$field' is truncated</p>\n";
                    $value  = substr($value, 0, 8);
                }
				if (!array_key_exists('byeartxt', $this->row) ||
				    !preg_match("/[0-9]{4}/", $this->row['byeartxt'], $matches))
				{	            // birth year not set from 'birthyeartxt'
				    if (preg_match("/^\[?([0-9]+)\]?$/", $value, $matches))
				    {	        // age is a number of years
						// calculate approximate birth year
						parent::set('byear', 
									 $this->byear - $matches[1]);
				    }	        // age is a number of years
				    else
				    if (preg_match("/^m?([0-9]+)m?$/", $value, $matches) &&
						$matches[1] < 5)
				    {	        // less than 5 months old
						// birth in year of enumeration
						parent::set('byear', 
									 $this->byear + 1);
				    }	        // less than 5 months old
				    else
				    {	        // unrecognized or between 5m and a year old
						// birth in year before enumeration
						parent::set('byear', 
									 $this->byear);
				    }	        // unrecognized or between 5m and a year old
				}	            // birth year not set from 'birthyeartxt'

				return parent::set('age', $value);
		    }	                // age field


		    default:
		    {		// no validation implemented yet
				return parent::set($field, $value);
		    }		// no validation implemented yet
		}		// switch on field name
    }		// CensusLine::set

    /********************************************************************
     *  function CensusLine::save										*
     *																	*
     *  Save changes made to the CensusLine record into the				*
     *  database.														*
     *																	*
     *  Parameters:														*
     *		   $xml		if true diagnostic output is emitted in XML		*
     *					format, if a string use it as tag name			*
     ********************************************************************/
    function save($xml = false)
    {
		global	$debug;
		global	$warn;
		global	$userid;
		global	$connection;

		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $xml	= true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		}		// true

		if ($this->needInsert)
		{		// create new record
		    $fldnames		= '';
		    $comma		= '';
		    $values		= '';
		    $sqlParms		= array();
		    $surnameChanged	= false;
		    foreach($this->row as $fld => $value)
		    {
				switch($fld)
				{	// act on field name
				    case 'surname':
				    {	// surname also updates soundex
						$fldnames			.= $comma."`surname`, `surnamesoundex`"; 
						$values				.= "$comma:$fld, LEFT(SOUNDEX(:$fld),4)";
						$sqlParms[$fld]	    = $value;
						$surnameChanged	    = true;
						break;
				    }	// surname also updates soundex

				    case 'surnamesoundex':
				    {	// set under 'surname'
						break;
				    }	// set under 'surname'

		            case 'sex':
		            case 'race':
		            case 'mstat':
		            case 'bplaceru':
		            case 'ownMeans':
		            case 'employer':
		            case 'employee':
		            case 'ownacct':
		            case 'canread':
		            case 'canwrite':
		            case 'spkenglish':
		            case 'spkfrench':
		            {                   // single character fields
		                if (strlen($value) > 1)
		                    $value  = substr($value, 0, 1);
						$fldnames			.= "$comma`$fld`";
		                $values				.= "$comma:$fld"; 
						$sqlParms[$fld]	    = $value;
						break;
		            }                   // single character fields
		
		            case 'facthome':
		            {                   // 2 character fields
		                if (strlen($value) > 2)
		                    $value  = substr($value, 0, 2);
						$fldnames			.= "$comma`$fld`";
		                $values				.= "$comma:$fld"; 
						$sqlParms[$fld]	    = $value;
						break;
		            }                   // 2 character fields
		
				    case 'subdistrict':
				    case 'division':
		            {                   // 4 character fields
		                if (strlen($value) > 4)
		                    $value  = substr($value, 0, 2);
						$fldnames			.= "$comma`$fld`";
		                $values				.= "$comma:$fld"; 
						$sqlParms[$fld]	    = $value;
						break;
		            }                   // 4 character fields
		
		            case 'bdate':
		            case 'immyear':
		            {                   // 8 character fields
		                if (strlen($value) > 8)
		                    $value  = substr($value, 0, 8);
						$fldnames			.= "$comma`$fld`";
		                $values				.= "$comma:$fld"; 
						$sqlParms[$fld]	    = $value;
						break;
		            }                   // 8 character fields

                    case 'origin':
                    case 'spkother':
                    case 'empwhere':
		            case 'nationality':
		            case 'mothertongue':
                    {           // strings limited to 16 characters
						$fldnames	    	.= "$comma`$fld`";
                        $values		    	.= "$comma:$fld"; 
                        if (strlen($value) > 16)
                            $value          = substr($value, 0, 16);
						$sqlParms[$fld]	    = $value;
						break;
                    }           // strings limited to 16 characters

                    case 'surname':
                    case 'relation':
                    case 'bplace':
                    case 'fathersbplace':
                    case 'mothersbplace':
                    {           // strings limited to 32 characters
						$fldnames	    	.= "$comma`$fld`";
                        $values		    	.= "$comma:$fld"; 
                        if (strlen($value) > 32)
                            $value      = substr($value, 0, 32);
						$sqlParms[$fld]	    = $value;
						break;
                    }           // strings limited to 32 characters

                    case 'givennames':
                    case 'township':
                    case 'address':
                    case 'occupation':
                    {           // strings limited to 48 characters
						$fldnames	    	.= "$comma`$fld`";
                        $values		    	.= "$comma:$fld"; 
                        if (strlen($value) > 48)
                            $value      = substr($value, 0, 48);
						$sqlParms[$fld]	    = $value;
						break;
                    }           // strings limited to 48 characters

				    case 'district':
				    case 'page':
				    case 'line':
				    case 'byear':
				    {	// integer fields
						$fldnames			.= "$comma`$fld`";
						$values				.= "$comma:$fld"; 
						if (is_int($value) ||
						    preg_match('/^\s*\d+(\.\d)?/', $value))
						    $sqlParms[$fld]	= $value;
						else
						    $sqlParms[$fld]	= 0;
						break;
                    }	// integer fields

				    case 'numhands':
				    case 'houserent':
				    case 'houserooms':
				    case 'monthsfact':
				    case 'monthshome':
				    case 'monthsother':
				    case 'incomeemp':
				    case 'incomeoth':
				    case 'monthsschool':
				    case 'wksemp':
				    case 'weeksunemp':
				    case 'wksoth':
				    case 'weeksill':
				    case 'hpwemp':
				    case 'hpwoth':
				    case 'hourlyrate':
				    case 'costinsurance':
				    case 'costeducation':
				    case 'lifeinsurance':
				    case 'accinsurance':
				    case 'schoolmons':
				    {	// optional integer fields
						$fldnames			.= "$comma`$fld`";
                        $values				.= "$comma:$fld"; 
						if (is_numeric($value))
						    $sqlParms[$fld]	= $value;
						else
						    $sqlParms[$fld]	= null;
						break;
				    }	// optional integer fields
		

				    default:
				    {	                // other fields
						$fldnames			.= "$comma`$fld`";
						$values				.= "$comma:$fld"; 
						$sqlParms[$fld]	    = $value;
						break;
                    }	                // other fields
				}	                    // switch on field name
				$comma	                    = ", ";
		    }		                    // loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	= "INSERT INTO " . $this->table .
						"($fldnames) VALUES($values)";

		    // insert the new record into the database
		    $stmt	            = $connection->prepare($insert);
		    $insertText	        = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd   = $insertText;
		    if ($stmt->execute($sqlParms))
		    {		// record inserted
				$count			= $stmt->rowCount();
				if ($xml)
				    print "<$tagname result='$count'>" . $insertText . 
						    "</$tagname>\n";

				if ($debug)
				{
				    $warn		.= "<p>$insertText</p>\n";
				    $warn		.= "<p>count=$count</p>\n";
				}

				$this->changed		= array();	// reset
				$this->needInsert	= false;

				// log the update
				$this->log($insert, $sqlParms);
		    }		// record inserted
		    else
		    {		// error inserting record
				showTrace();
				print "<p class='message'>CensusLine::save: " . __LINE__ .
								" '$insertText', " .
								print_r($stmt->errorInfo(),true) .
						"</p>\n";
				if (strlen($insertText) > 60)
				    $insertText	= substr($insertText, 0, 50) . '...' .
								  substr($insertText, -7);
				throw new Exception("CensusLine::save: '" .
								    $insertText . "', " .
								    print_r($stmt->errorInfo(),true));
		    }		// error inserting record
		}		    // create new record
		else
		{		    // update existing record
		    $update		    = "UPDATE " . $this->table . " SET ";
		    $set		    = '';
		    $comma		    = '';
		    $sqlParms		= array();
		    $surnameChanged	= false;
		    foreach($this->changed as $fld => $value)
		    {
				switch($fld)
				{	// act on field name
				    case 'surname':
				    {	// surname also updates soundex 
						$set			    .= $comma .
			"`Surname`=:surname, `SurnameSoundex`=LEFT(SOUNDEX(:surname),4)";
						$sqlParms[$fld]	    = $value;
						$comma		        = ', ';
						$surnameChanged	    = true;
						break;
				    }	// surname also updates soundex

				    case 'surnamesoundex':
				    {	// set under 'surname'
						break;
				    }	// set under 'surname'

				    case 'district':
				    case 'page':
				    case 'line':
				    case 'byear':
				    {	// integer fields
						$set			    .= "$comma`$fld`=:$fld";
						if (is_int($value) ||
						    preg_match('/^\s*\d+(\.\d)?/', $value))
						    $sqlParms[$fld]	= $value;
						else
						    $sqlParms[$fld]	= 0;
						$comma		        = ', ';
						break;
                    }	// integer fields

		            case 'houserent':
		            case 'houserooms':
				    case 'monthsfact':
				    case 'monthshome':
				    case 'monthsother':
				    case 'incomeemp':
				    case 'incomeoth':
				    case 'monthsschool':
				    case 'wksemp':
				    case 'wksoth':
		            case 'weeksunemp':
		            case 'weeksill':
				    case 'hpwemp':
				    case 'hpwoth':
				    case 'hourlyrate':
				    case 'costinsurance':
				    case 'costeducation':
				    case 'lifeinsurance':
				    case 'accinsurance':
				    case 'schoolmons':
				    {	// optional integer fields
						$set			    .= "$comma`$fld`=:$fld";
						if (is_int($value) ||
						    preg_match('/^\s*\d+(\.\d)?/', $value))
						    $sqlParms[$fld]	= $value;
						else
						    $sqlParms[$fld]	= null;
						$comma		        = ', ';
						break;
				    }	// optional integer fields

		            case 'sex':
		            case 'race':
		            case 'mstat':
		            case 'bplaceru':
		            case 'ownMeans':
		            case 'employer':
		            case 'employee':
		            case 'ownacct':
		            case 'canread':
		            case 'canwrite':
		            case 'spkenglish':
		            case 'spkfrench':
		            {                   // single character fields
		                if (strlen($value) > 1)
		                    $value          = substr($value, 0, 1);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
		            }                   // single character fields
		
		            case 'facthome':
		            {                   // 2 character fields
		                if (strlen($value) > 2)
		                    $value          = substr($value, 0, 2);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
		            }                   // 2 character fields
		
				    case 'subdistrict':
				    case 'division':
		            {                   // 4 character fields
		                if (strlen($value) > 4)
		                    $value          = substr($value, 0, 2);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
		            }                   // 4 character fields
		
		            case 'bdate':
		            case 'immyear':
		            {                   // 8 character fields
		                if (strlen($value) > 8)
		                    $value          = substr($value, 0, 8);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
		            }                   // 8 character fields

                    case 'origin':
                    case 'spkother':
                    case 'empwhere':
		            case 'nationality':
		            case 'mothertongue':
                    {           // strings limited to 16 characters
                        if (strlen($value) > 16)
                            $value          = substr($value, 0, 16);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
                    }           // strings limited to 16 characters

                    case 'surname':
                    case 'relation':
                    case 'bplace':
                    case 'fathersbplace':
                    case 'mothersbplace':
                    {           // strings limited to 32 characters
                        if (strlen($value) > 32)
                            $value          = substr($value, 0, 32);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
                    }           // strings limited to 32 characters

                    case 'givennames':
                    case 'township':
                    case 'address':
                    case 'occupation':
                    {           // strings limited to 48 characters
                        if (strlen($value) > 48)
                            $value          = substr($value, 0, 48);
						$sqlParms[$fld]	    = $value;
						$set	    		.= "$comma`$fld`=:$fld";
						$comma	    	    = ', ';
						break;
                    }           // strings limited to 48 characters


				    default:
				    {	        // other fields
						$set	    		.= "$comma`$fld`=:$fld";
						$sqlParms[$fld] 	= $value;
						$comma	    	    = ', ';
				    }	        // other fields
				}	            // switch on field name
		    }		            // loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		            // changes made
				// construct the where clause
                if (array_key_exists('province', $this->row))
                {
                    if ($this->row['province'] !== null)
					{
					    $where	                = ' Province=:oprovince AND ';
					    if ($this->former)
							$sqlParms['oprovince']	= $this->former['province'];
					    else
							$sqlParms['oprovince']	= $this->row['province'];
					}
	                else
	                {           // not initialized
						$set	    	        .= "$comma`Province`=:province";
						$sqlParms['province'] 	= $this->distObj['province'];
	                    $where	                = '';
	                }           // not initialized
                }
                else
                {
                    $where           	= '';
                }
				$where	                .= 'District=:odistrict AND ' . 
		            						'SubDistrict=:osubdistrict AND ' .
				            				'Division=:odivision AND ' . 
					            			'Page=:opage AND ' . 
						            		'Line=:oline';
				if ($this->former)
				{
				    $sqlParms['odistrict']	    = $this->former['district'];
				    $sqlParms['osubdistrict']	= $this->former['subdistrict'];
				    $sqlParms['odivision']	    = $this->former['division'];
				    $sqlParms['opage']	        = $this->former['page'];
				    $sqlParms['oline']	        = $this->former['line'];
				}
				else
				{
				    $sqlParms['odistrict']	    = $this->row['district'];
				    $sqlParms['osubdistrict']	= $this->row['subdistrict'];
				    $sqlParms['odivision']	    = $this->row['division'];
				    $sqlParms['opage']	        = $this->row['page'];
				    $sqlParms['oline']	        = $this->row['line'];
				}

				// assemble the UPDATE command
				$update			                .= $set . ' WHERE ' . $where;

				// perform the update
				$stmt			        = $connection->prepare($update);
				$updateText		        = debugPrepQuery($update, $sqlParms);
                $this->lastSqlCmd       = $updateText;
				if ($stmt->execute($sqlParms))
				{
				    $count		        = $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$count'>" .
								$updateText . "</$tagname>\n";
				    if ($debug)
						$warn		    .= "<p>$updateText</p>\n";

				    // log the update
				    $this->log($update, $sqlParms);
				}
				else
				{		// error updating record
				    print "<p class='message'>CensusLine::save: " . __LINE__ .
								" '$updateText', " .
								print_r($stmt->errorInfo(),true) .
						"</p>\n";
				    if (strlen($updateText) > 60)
						$updateText	= substr($updateText, 0, 50 ) . '...' .
									  substr($updateText, -7);
				    throw new Exception("CensusLine::save: '" .
									$updateText . "', " .
									print_r($stmt->errorInfo(),true));
				}		// error updating record
		    }		// changes made
		}		// update existing record
    }		// CensusLine::save

    /********************************************************************
     *  function CensusLine::delete										*
     *																	*
     *  Delete the database record associated with this instance.		*
     *																	*
     *  Input:															*
     *	    $xml		if true emit XML								*
     *					if a string emit XML using as tag name			*
     *																	*
     *  Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record		*
     *	    0			if the record did not need to be deleted		*
     ********************************************************************/
    function delete($xml=false)
    {
		global	$debug;
		global	$warn;
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

		// to simplify expressions
		$table		= $this->table;		// name of table
		$censusYear	= intval(substr($table, 6));
		if (!$this->needInsert)
		{		// have existing record
		    if ($censusYear < 1867)
		    {			// pre-confederation
				$parms	=  array('province'	=> $this->row['province'],
								'district'	=> $this->row['district'],
								'subdistrict'	=> $this->row['subdistrict'],
								'division'	=> $this->row['division'],
								'page'		=> $this->row['page'],
								'line'		=> $this->row['line']);
				$delete	= "DELETE FROM $table " .
								"WHERE Province=:province" .
									" AND District=:district" .
									" AND SubDistrict=:subdistrict" .
									" AND Division=:division" .
									" AND Page=:page" .
									" AND Line=:line";
		    }			// pre-confederation
		    else
		    {			// post-confederation
				$parms	=  array('district'	=> $this->row['district'],
								'subdistrict'	=> $this->row['subdistrict'],
								'division'	=> $this->row['division'],
								'page'		=> $this->row['page'],
								'line'		=> $this->row['line']);
				$delete	= "DELETE FROM $table " .
								"WHERE District=:district" .
									" AND SubDistrict=:subdistrict" .
									" AND Division=:division" .
									" AND Page=:page" .
									" AND Line=:line";
		    }			// post-confederation

		    $stmt	            = $connection->prepare($delete);
			$deleteText	        = debugPrepQuery($delete, $parms);
            $this->lastSqlCmd   = $deleteText;
		    if ($stmt->execute($parms))
		    {		// delete successful
				$count			= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";

				// log the update
				logSqlUpdate($delete,
						     $parms,
						     0,		// compound key
						     '',
						     $this->toJson(false));

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// delete successful
		    else
		    {		// error updating record
				print "<p class='message'>CensusLine::delete: " . __LINE__ .
								" '$deleteText', " .
								print_r($stmt->errorInfo(),true) .
						"</p>\n";
				return false;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }		// CensusLine::delete

}		// class CensusLine 

/************************************************************************
 *  Initialize CensusLine::$defaultRows									*
 ************************************************************************/
CensusLine::init();

/************************************************************************
 *  class Census1851													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1851 census of Canada.			*
 ************************************************************************/

class Census1851 extends CensusLine
{
    
    /********************************************************************
     *	Census1851::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1851',
                'name'      => 'Census1851',
		        'prime'	    => array('province',
									 'district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1851',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'pagedisp'				=> '',
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'occupation'			=> '',
									 'bplace'				=> 'Canada West',
									 'religion'				=> '',
									 'location'				=> '',
									 'age'					=> '',
									 'byear'				=> null,
									 'sex'					=> '?',
									 'mstat'				=> '',
									 'negro'				=> '',
									 'indian'				=> '',
									 'member'				=> 'Y',
									 'absent'				=> '',
									 'deaf'					=> '',
									 'blind'				=> '',
									 'lunatics'				=> '',
									 'school'				=> '',
									 'birth'				=> '',
									 'deathsex'				=> '',
									 'ageatdeath'			=> '',
									 'causeofdeath'			=> '',
									 'restype'				=> '',
									 'stories'				=> '',
									 'numfamilies'			=> '',
									 'remarks'				=> '',
					                 'idir'					=> 0));

}		// class Census1851 

/************************************************************************
 *  class Census1861													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1861 census of Canada.			*
 ************************************************************************/

class Census1861 extends CensusLine
{
    
    /********************************************************************
     *	Census1861::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1861',
                'name'      => 'Census1861',
		        'prime'	    => array('province',
									 'district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1861',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'pagedisp'				=> '',
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'occupation'			=> '',
									 'bplace'				=> 'Canada West',
									 'maryear'				=> '',
									 'religion'				=> '',
									 'location'				=> '',
									 'age'					=> '',
									 'byear'				=> null,
									 'sex'					=> '?',
									 'mstat'				=> '',
									 'coloured'				=> '',
									 'member'				=> 'Y',
									 'absent'				=> '',
									 'deaf'					=> '',
									 'blind'				=> '',
									 'lunatic'				=> '',
									 'school'				=> '',
									 'illiterate'			=> '',
									 'birth'				=> '',
									 'deathsex'				=> '',
									 'ageatdeath'			=> '',
									 'causeofdeath'			=> '',
									 'restype'				=> '',
									 'stories'				=> '',
									 'numfamilies'			=> '',
									 'remarks'				=> '',
									 'idir'					=> 0));

}		// class Census1861 

/************************************************************************
 *  class Census1871													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1871 census of Canada.			*
 ************************************************************************/

class Census1871 extends CensusLine
{
    
    /********************************************************************
     *	Census1871::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1871',
                'name'      => 'Census1871',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1871',
    	        'initrow'   => array('province'				=> '',
									 'district'		   		=> 0,
									 'subdistrict'			=> 0,
									 'division'		   		=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'sex'					=> '?',
									 'age'					=> '',
									 'byear'				=> null,
									 'binyear'				=> '',
									 'bplace'				=> 'Ontario',
									 'religion'				=> '',
									 'origin'				=> '',
									 'occupation'			=> '',
									 'mstat'				=> '',
									 'minyear'				=> '',
									 'address'				=> '',
									 'remarks'				=> '',
									 'school'				=> '',
									 'cantread'				=> '',
									 'cantwrite'			=> '',
									 'deaf'					=> '',
									 'blind'				=> '',
									 'insane'				=> '',
									 'idir'					=> 0));

}		// class Census1871 

/************************************************************************
 *  class Census1881													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1881 census of Canada.			*
 ************************************************************************/

class Census1881 extends CensusLine
{
    
    /********************************************************************
     *	Census1881::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1881',
                'name'      => 'Census1881',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1881',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'sex'					=> '?',
									 'age'					=> '',
									 'byear'				=> null,
									 'binyear'				=> '',
									 'bplace'				=> 'Ontario',
									 'religion'				=> '',
									 'origin'				=> '',
									 'occupation'			=> '',
									 'mstat'				=> '',
									 'remarks'				=> '',
									 'school'				=> '',
									 'deaf'					=> '',
									 'blind'				=> '',
									 'insane'				=> '',
									 'idir'					=> 0));

}		// class Census1881 

/************************************************************************
 *  class Census1891													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1891 census of Canada.			*
 ************************************************************************/

class Census1891 extends CensusLine
{
    
    /********************************************************************
     *	Census1891::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1891',
                'name'      => 'Census1891',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1891',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'sex'					=> '?' ,
									 'age'					=> '',
									 'byear'				=> null,
									 'mstat'				=> '',
									 'relation'				=> '',
									 'bplace'				=> 'Ontario',
									 'french'				=> '',
									 'fathersbplace'		=> 'Ontario',
									 'mothersbplace'		=> 'Ontario',
									 'religion'				=> '',
									 'occupation'			=> '',
									 'employer'				=> '',
									 'employee'				=> '',
									 'unemployed'			=> '',
									 'numhands'				=> '',
									 'canread'				=> '1',
									 'canwrite'				=> '1',
									 'deaf'					=> '',
									 'blind'				=> '',
									 'insane'				=> '',
									 'remarks'				=> '',
									 'idir'					=> 0));
}		// class Census1891 

/************************************************************************
 *  class Census1901													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1901 census of Canada.			*
 ************************************************************************/

class Census1901 extends CensusLine
{
    
    /********************************************************************
     *	Census1901::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1901',
                'name'      => 'Census1901',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1901',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'sex'					=> '?' ,
									 'race'					=> 'W' ,
									 'relation'				=> '',
									 'mstat'				=> '',
									 'age'					=> '',
									 'bdate'				=> '',
									 'byeartxt'				=> '',
									 'byear'				=> null,
									 'bplace'				=> 'Ontario',
									 'bplaceru'				=> 'r',
									 'immyear'				=> '',
									 'natyear'				=> '',
									 'origin'				=> '',
									 'nationality'			=> 'Canadian',
									 'religion'				=> '',
									 'occupation'			=> '',
									 'address'				=> '',
									 'remarks'				=> '',
									 'idir'					=> 0,
									 'ownmeans'				=> '',
									 'employer'				=> '',
									 'employee'				=> '',
									 'ownacct'				=> '',
									 'facthome'				=> ' ',
									 'monthsfact'			=> 0,
									 'monthshome'			=> 0,
									 'monthsother'			=> 0,
									 'incomeemp'			=> 0,
									 'incomeoth'			=> 0,
									 'monthsschool'			=> 0,
									 'canread'				=> '',
									 'canwrite'				=> '',
									 'spkenglish'			=> 1,
									 'spkfrench'			=> '',
									 'mothertongue'			=> '',
				                     'infirmities'			=> ''));


}		// class Census1901 

/************************************************************************
 *  class Census1906													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1906 census of Canada.			*
 ************************************************************************/

class Census1906 extends CensusLine
{
    
    /********************************************************************
     *	Census1906::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1906',
                'name'      => 'Census1906',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1906',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'relation'				=> '',
									 'sex'					=> '?' ,
									 'mstat'				=> '',
									 'age'					=> '',
									 'byeartxt'				=> '',
									 'byear'				=> null,
									 'bplace'				=> 'Ontario',
									 'immyear'				=> '',
									 'postoffice'			=> '',
									 'addrsect'				=> '',
									 'addrtwp'				=> '',
									 'addrrng'				=> '',
									 'addrmdn'				=> '',
									 'horses'				=> 0,
									 'milkcows'				=> 0,
									 'cattle'				=> 0,
									 'sheep'				=> 0,
									 'pigs'					=> 0,
									 'remarks'				=> '',
									 'idir'					=> 0));

}		// class Census1906

/************************************************************************
 *  class Census1911													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1911 census of Canada.			*
 ************************************************************************/

class Census1911 extends CensusLine
{
    
    /********************************************************************
     *	Census1911::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1911',
                'name'      => 'Census1911',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1911',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'address'				=> '',
									 'sex'					=> '?',
									 'relation'				=> '',
									 'mstat'				=> '',
									 'bdate'				=> '',
									 'byeartxt'				=> '',
									 'age'					=> '',
									 'byear'				=> null,
									 'bplace'				=> 'Ontario',
									 'immyear'				=> '',
									 'natyear'				=> '',
									 'origin'				=> '',
									 'nationality'			=> 'Canadian',
									 'religion'				=> '',
									 'occupation'			=> '',
									 'occother'				=> '',
									 'employer'				=> '',
									 'employee'				=> '',
									 'ownacct'				=> '',
									 'empwhere'				=> '',
									 'wksemp'				=> '',
									 'wksoth'				=> '',
									 'hpwemp'				=> '',
									 'hpwoth'				=> '',
									 'incomeemp'			=> '',
									 'incomeoth'			=> '',
									 'hourlyrate'			=> 0,
									 'lifeinsurance'		=> 0,
									 'accinsurance'			=> 0,
									 'costinsurance'		=> 0,
									 'schoolmons'			=> 0,
									 'canread'				=> 'Y',
									 'canwrite'				=> 'Y',
									 'language'				=> 'English',
									 'costeducation'		=> 0,
									 'blind'				=> '',
									 'deaf'					=> '',
									 'insane'				=> '',
									 'idiot'				=> '',
									 'remarks'				=> '',
									 'idir'					=> 0));

}		// class Census1911 

/************************************************************************
 *  class Census1916													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1916 census of Canada.			*
 ************************************************************************/

class Census1916 extends CensusLine
{
    
    /********************************************************************
     *	Census1916::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1916',
                'name'      => 'Census1916',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1916',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'military'				=> '',
									 'addrtwp'				=> '',
									 'addrrng'				=> '',
									 'addrmdn'				=> '',
									 'addrmuni'				=> '',
									 'relation'				=> '',
									 'sex'					=> '?' ,
									 'mstat'				=> '',
									 'age'					=> '',
									 'byear'				=> null,
									 'bplace'				=> 'Ontario',
									 'religion'				=> '',
									 'immyear'				=> '',
									 'natyear'				=> '',
									 'nationality'			=> 'Canadian',
									 'origin'				=> '',
									 'spkenglish'			=> '1',
									 'spkfrench'			=> '',
									 'spkother'				=> '',
									 'canread'				=> '1',
									 'canwrite'				=> '1',
									 'occupation'			=> '',
									 'emptype'				=> '',
									 'empwhere'				=> '',
									 'remarks'				=> '',
									 'idir'					=> 0));

}		// class Census1916 

/************************************************************************
 *  class Census1921													*
 *							    										*
 *  Definition of a class recording information about an individual		*
 *  from the population schedule of the 1921 census of Canada.			*
 ************************************************************************/

class Census1921 extends CensusLine
{
    
    /********************************************************************
     *	Census1921::$info										        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'Census1921',
                'name'      => 'Census1921',
		        'prime'	    => array('district',
									 'subdistrict',
									 'division',
									 'page',
									 'line'),
	            'srvmg'     => false,
	            'fldcount'	=> 7,
                'order'     => 'district,subdistrict,division,page,line',
                'classname' => 'Census1921',
    	        'initrow'   => array('province'				=> '',
									 'district'				=> 0,
									 'subdistrict'			=> 0,
									 'division'				=> '',
									 'page'					=> 0,
									 'line'					=> 0,
									 'family'				=> 1,
									 'surname'				=> '',
									 'surnamesoundex'		=> '',
									 'givennames'			=> '',
									 'township'				=> '',
									 'address'				=> '',
									 'ownertenant'			=> '',
									 'houserent'			=> 0,
									 'houseclass'			=> '',
									 'housematerial'		=> '',
									 'houserooms'			=> 0,
									 'relation'				=> '',
									 'sex'					=> '?',
									 'mstat'				=> '',
									 'age'					=> '',
									 'byear'				=> null,
									 'bplace'				=> 'Ontario',
									 'fathersbplace'		=> 'Ontario',
									 'mothersbplace'		=> 'Ontario',
									 'immyear'				=> '',
									 'natyear'				=> '',
									 'nationality'			=> 'Canadian',
									 'origin'				=> '[origin]',
									 'spkenglish'			=> 'Y',
									 'spkfrench'			=> '',
									 'spkother'				=> '',
									 'religion'				=> '[religion]',
									 'canread'				=> 'Y',
									 'canwrite'				=> 'Y',
									 'schoolmons'			=> '',
									 'occupation'			=> '',
									 'emptype'				=> '',
									 'empwhere'				=> '',
									 'incomeemp'			=> '',
									 'unemployed'			=> '',
									 'weeksunemp'			=> '',
									 'weeksill'				=> '',
									 'remarks'				=> '',
                					 'idir'					=> 0));

}		// class Census1921 
