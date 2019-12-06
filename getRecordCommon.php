<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getRecordCommon.php													*
 *																		*
 *  Get the information on an instance of Record.						*
 *  This is the code shared between getRecordXml.php and				*
 *  getRecordJson.php.													*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		table				    keys: 							        *
 *		'Addresses'				'id'									*
 *		'PictureBases'			'id'									*
 *		'Pictures'				'id'									*
 *		'ChildParentRels'		'id'									*
 *		'Children'				'id'									*
 *		'DontMergeEntries'		'idirleft' and 'idirright'				*
 *		'Events'				'id'									*
 *		'Bookmarks'				'id'									*
 *		'HistoryList'			'id'									*
 *		'Header'				no parameters required					*
 *		'Persons'				'id'									*
 *		'Locations'				'id'									*
 *		'Families'				'id'									*
 *		'Surnames'				'surname'								*
 *		'Names'					'id'									*
 *		'Nicknames'				'id'	    							*
 *		'SurnameList'			'id'									*
 *		'Sources'				'id'									*
 *		'Citations'				'id'									*
 *		'ToDoCategories'		'id'									*
 *		'ToDoEntries'			'id'									*
 *		'ToDoLocalities'		'id'									*
 *		'Temples'				'id'									*
 *		'RemovedPersons'		'id'									*
 *		'RemovedFamilies'		'id'									*
 *		'Users'					'id'									*
 *		'Blogs'					'id'									*
 *		'MethodistBaptisms'		'id'									*
 *		'Births'				'domain', 'year', 'regnum'				*
 *		'Deaths'				'domain', 'year', 'regnum'				*
 *		'Marriages'				'domain', 'year', 'regnum'				*
 *		'CountyMarriages'		'Domain', 'Volume', 'ReportNo',			*
 *								'ItemNo'								*
 *		'CountyMarriageReports'	'Domain', 'Volume', 'ReportNo'			*
 *		'Counties'				'domain', 'county'						*
 *		'Townships'				'domain', 'county', 'code'				*
 *																		*
 *		For some tables if the lowest level identifier is omitted		*
 *		then the output will include all of the records at that level	*
 *																		*
 *		options			integer value to determine which sub-records	*
 *						are displayed for a particular table			*
 *		offset			starting offset in results						*
 *		limit			max number of records to return					*
 *																		*
 *		Values of keys to select specific records:						*
 *																		*
 *		Surname         search by surname for Persons, Names, Surnames	*
 *		                Note that this is a pattern match, specify      *
 *		                ^surname$ for exact match                       *
 *		domain			registration domain: country code + state code	*
 *						default 'CAON'									*
 *		year			registration year								*
 *		number			registration number within the year				*
 *		county			county abbreviation								*
 *		townshipcod		abbreviation									*
 *		townshipnam		full name										*
 *		volume			volume number for county marriage reports		*
 *		reportno		report number within volume of county marriage	*
 *						reports											*
 *		itemno			item within a county marriage report			*
 *		other			field name within database, e.g. surname		*
 *																		*
 *  History:															*
 *		2013/06/10		created											*
 *		2013/08/09		base class LegacyRecord renamed to Record		*
 *		2013/09/13		require signon									*
 *		2013/11/27		handle database server failure gracefully		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2015/07/02		access PHP includes using include_path			*
 *						Moved to top level								*
 *						Support Births, Marriage, Deaths, Counties,		*
 *						Townships, Users, and Blogs.					*
 *		2015/12/30		fix conflict with autoload						*
 *		2016/01/16		allow specifying one fieldname search as		*
 *						already documented								*
 *		2017/01/13		add support for CountyMarriage and				*
 *						CountyMarriageReport							*
 *						add support for list of records					*
 *		2017/01/15		split off from getRecordXml.php					*
 *		2017/02/07		use class Country								*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/05/29		support field name searches on most tables		*
 *						do not permit display of entire table on		*
 *						most tables										*
 *						do not permit display of more than 100 records	*
 *						on any table									*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/15		class LegacyToDo renamed to class ToDo			*
 *		2017/08/17		class LegacyDontMergeEntry renamed to			*
 *						class DontMergeEntry							*
 *		2017/08/18		class LegacyName renamed to class Name			*
 *		2017/09/09		class LegacyLocation renamed to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/09		support search just by province for Censuses	*
 *						make all external table names plural			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *						table name translation moved to class Record	*
 *						use class RecordSet to get records				*
 *						use Record::getInformation to get table info	*
 *		2017/10/16		use class RecordSet to get all sets of Records	*
 *		2018/02/13		for table "Pages" use class PageSet to get		*
 *						all of the page records in an enumeration		*
 *						division										*
 *		2018/12/26      add surname parameter for tblIR, tblNR, tblNX   *
 *		2019/06/23      support sets of Births, Deaths, and Marriages   *
 *		2019/11/20      always create RecordSet if $id is not set       *
 *		                if $parms is empty replace with null to get all *
 *		                pass limit and offset values in parms           *
 *		2019/11/28      table=Censuses&id=censusid may create new entry *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

global $record;
global $id;
global $top;
global $msg;
global $warn;
global $connection;

// initialize
$table				= null;		// name of table
$id					= null;		// index into table
$parms				= array();
$domain				= 'CAON';
$volume				= null;
$reportNo			= null;
$itemNo				= null;
$regyear			= 0;
$regnum				= 0;
$county				= null;
$townshipcode		= null;
$townshipname		= null;
$censusYear			= '';		// census year
$cc					= 'CA';		// ISO country code
$countryName		= 'Canada';
$province			= '';		// province for pre-confederation
$censusId			= null;
$distId				= null;
$subdistId			= null;
$division			= null;
$page				= null;
$line				= null;
$options			= 0;		// value passed to method to specify
						// sub-records to include in response
$offset				= 0;		// first record to return from result
$record		        = null;

// check authorization
if (!canUser('edit'))
	$msg	.= 'You must be signed on to run this script. ';

// process the parameters
foreach($_GET as $key 	=> $value)
{			// loop through all parameters
    if (strlen($value) == 0)
        continue;
	switch(strtolower($key))
	{
	    case 'table':
	    {
			$information	= Record::getInformation($value);
			if ($information)
			{
			    $extTableName	= $information['name'];
			    $table		    = $information['table'];
			}
			else
			    $msg	        .= "Invalid parameter value Table='$value'. ";
			break;
	    }		// table name

	    case 'surname':
	    {                   // with tblIR, tblNR, tblNX
			$surname		    = $value;
			$parms[$key]		= $value;
			break;
	    }                   // surname

	    case 'domain':
	    case 'regdomain':
	    {                   // vital statistics
			$domain			    = $value;
			$parms[$key]		= $value;
			break;
	    }                   // vital statistics

	    case 'volume':
	    {
			$volume			    = $value;
			$parms[$key]		= $value;
			break;
	    }

	    case 'reportno':
	    {
			$reportNo		    = $value;
			$parms[$key]		= $value;
			break;
	    }

	    case 'itemno':
	    {
			$itemNo			    = $value;
			$parms[$key]		= $value;
			break;
	    }

	    case 'year':
	    case 'regyear':
	    {
			$rxres	                = preg_match("/^[0-9]{4}$/", $value);
			if ($rxres == 1)
			{
			    $regyear		    = $value;
			    $parms['regyear']	= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// registration year

	    case 'number':
	    case 'regnum':
	    {
			$rxres	                = preg_match("/^[0-9]+$/", $value);
			if ($rxres == 1)
			{
			    $regnum		        = $value;
			    $parms['regnum']	= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// registration number

	    case 'county':
	    case 'regcounty':
	    {
			$rxres	            = preg_match("/^[a-zA-Z]\w+$/", $value);
			if ($rxres == 1)
			{
			    $county		    = $value;
			    $parms[$key]	= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// county code

	    case 'townshipcode':
	    case 'regtownship':
	    {
			$rxres	            = preg_match("/^[a-zA-Z][\w &]+$/", $value);
			if ($rxres == 1)
			{
			    $townshipcode	    = $value;
			    $parms['township']	= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// Township internal name

	    case 'townshipname':
	    {
			$rxres	= preg_match("/^[a-zA-Z][a-zA-Z0-9 ]+$/", $value);
			if ($rxres == 1)
			{
			    $townshipname	= $value;
			    $parms['name']	= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// Township external name

	    case 'census':
	    case 'censusid':
	    {		// census year
require_once __NAMESPACE__ . '/Census.inc';
			if (strlen($value) == 4)
			    $censusId		= 'CA' . $censusYear;
			else
			    $censusId		= $value;

			$censusRec	= new Census(array('censusid'	=> $censusId,
							   'collective'	=> 0));
			if ($censusRec->isExisting())
			{    
			    $parms['censusid']	= $value;
			    $cc			= substr($censusId, 0, 2);
			    $censusYear		= intval(substr($censusId, 2));
			    $countryObj		= new Country(array('code' => $cc));
			    $countryName	= $countryObj->getName();
			}		// includes country code
			else
			{
			    $censusRec	= null;
			    $msg	.= "Census identifier '$value' invalid. ";
			}
			break;
	    }		// census year

	    case 'province':
        {		// province code
            if (strlen($value) < 2)
                break;
			$province		    = $value;
			$parms[$key]		= $value;
			if ($table == 'Censuses')
			    break;
			if (isset($censusRec))
			{
			    $ppos	= strpos($province,
						 $censusRec->get('provinces'));
			    if (strlen($province) != 2 ||
					$ppos < 0 || ($ppos & 1) == 1)
			    {
					$msg	.= "Province '$province' not supported for '$censusId' census. ";
			    }
			}
			else
			    $msg	.= "Province specified without valid Census. ";
			break;
	    }		// province code
	
	    case 'district':
	    {		// district number
			if (preg_match("/^[0-9]+(\.[05]|)$/", $value) == 1)
			{		// matches pattern of a district number
			    if (substr($value,strlen($value)-2) == '.0')
					$distId		= substr($value, 0, strlen($value) - 2);
			    else
					$distId		= $value;
			    $parms[$key]	= $distId;
			}		// matches pattern of a district number
			else
			{
			    $msg		.= "District value $value invalid. ";
			}
			break;
	    }		// district number

	    case 'subdistrict':
	    {		// subdistrict code
			$subdistId		    = $value;
			$parms[$key]		= $value;
			break;
	    }		// subdistrict code

	    case 'division':
	    {		// enumeration division
			$division		    = $value;
			$parms[$key]		= $value;
			break;
	    }		// enumeration division

	    case 'page':
	    {		// page within enumeration division
			$page			    = $value;
			$parms[$key]		= $value;
			break;
	    }		// page within enumeration division

	    case 'line':
	    {		// line within page
			$line			    = $value;
			$parms[$key]		= $value;
			break;
	    }		// line within page

	    case 'iduser':
	    case 'idblog':
	    case 'id':
        {
			if (is_null($table) && strlen($key) > 2)
                $table	= ucfirst(substr($key,2)) . 's';
            if (is_string($table))
            {
                $info           = Record::getInformation($table);
                $prime          = $info['prime'];          
            }
            else
                $prime          = $key;
            if (strpos($value, ','))
                $id             = array($prime => explode(',',$value));
            else
			    $id		        = $value;
			$parms[$prime]	    = $id;
			break;
	    }		// registration number

	    case 'idmb':
	    {
			$id		            = $value;
			if (is_null($table))
			    $table	= 'MethodistBaptisms';
			break;
	    }		// registration number

	    case 'idar':
	    case 'idbp':
	    case 'idbr':
	    case 'idcp':
	    case 'idcr':
	    case 'idcs':
	    case 'ider':
	    case 'idet':
	    case 'idhb':
	    case 'idhl':
	    case 'idir':
	    case 'idlr':
	    case 'idmr':
	    case 'idms':
	    case 'idnx':
	    case 'idrm':
	    case 'idsr':
	    case 'idst':
	    case 'idsx':
	    case 'idtc':
	    case 'idtd':
	    case 'idtl':
	    case 'idtr':
	    case 'idxi':
	    case 'idxm':
	    {		// shortcuts for Legacy tables
			if (is_null($table))
			{
			    $table		    = 'tbl' . strtoupper(substr($key,2));
			}
			$parms[$key]		= $value;
			break;
	    }		// shortcuts for Legacy tables

	    case 'offset':
	    {		// record offset
            $value                      = trim($value);
			if (ctype_digit($value))
			    $parms['offset']	    = intval($value);
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// record offset

	    case 'limit':
        {		// maximum number of records
            $value                  = trim($value);
			if (ctype_digit($value))
			    $parms['limit']	    = intval($value);
			else
			    $msg	        .= "Invalid parameter value $key='$value'. ";
			break;
	    }		// maximum number of records

	    case 'options':
	    {
			if (ctype_digit($value))
			{
			    $options		= $value;
			}
			else
			    $msg	.= "Invalid parameter value $key='$value'. ";
			break;
	    }		// options parameter

	    case 'debug':
	    {
			break;
	    }		// options parameter

	    default:
        {
			$parms[$key]	= $value;
			break;
	    }

	}		// act on specific parameters
}			// loop through all parameters

if (strlen($msg) == 0)
{			// no errors detected
    if (count($parms) == 0)
        $parms          = null;

	//try {		// prevent throw from breaking XML
	switch($table)
	{
	    case 'tblAR':
	    {		// Address record
require_once __NAMESPACE__ . '/Address.inc';
			if (is_array($parms))
			{		// search parameters
			    if (array_key_exists('kind', $parms))
			    {
				if ($parms['kind'] == Address::MAILING &&
				    !canUser('all'))
				    $parms['kind']	= array(1,2);
			    }
			    else
			    if (!canUser('all'))
					$parms['kind']		= array(1,2);
			    $record	= new RecordSet('Addresses',$parms);
			    if ($record->count() == 1)
			    {
					$record		= $record->current();
				if ($record->get('kind') == Address::MAILING &&
				    !canUser('all'))
				    $msg	.=
			"You are not authorized to view mailing address records. ";
			    }
			}		// search parameters
			$top	= 'address';
			break;
	    }		// Address record

	    case 'tblBR':
	    {		// Picture record
require_once __NAMESPACE__ . '/Picture.inc';
			if (isset($id))
            {
                print '"id" : "' . print_r($id, true) . '",';
			    if (is_array($id))
			    {		// search parameters
					$record	    = new RecordSet('Pictures', $id);
			    }		// search parameters
			    else
			    {
					$record	    = new Picture($id);
			    }
			}
			else
			{
                $record	        = new RecordSet('Pictures', $parms);
			}
			$top	= 'picture';
			break;
	    }		// Picture record

	    case 'tblCR':
	    {		// Child record
require_once __NAMESPACE__ . '/Child.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	= new RecordSet('Children', $id);
			    }		// search parameters
			    else
			    {
					$record	= new Child(array('idcr' => $id));
			    }
			}
			else
			{
			    $record	= new RecordSet('Children', $parms);
			}
			$top	= 'child';
			break;
	    }		// Child record

	    case 'tblDM':
	    {
require_once __NAMESPACE__ . '/DontMergeEntry.inc';
			if (isset($idirleft) && isset($idirright))
			{
			    $record	= new DontMergeEntry($idirleft, $idirright);
			}
			else
			{
			    $record	= new RecordSet('DontMergeEntries', $parms);
			}
			$top		= 'dontmerge';
			break;
	    }		// DontMerge

	    case 'tblER':
	    {		// Event record
require_once __NAMESPACE__ . '/Event.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Events', $id);
			    }		// search parameters
			    else
			    {
				    $record	= new Event(array('ider' => $id));
			    }
			}
			else
			{
			    $record	    = new RecordSet('Events', $parms);
			}
			$top	        = 'event';
			break;
	    }		// Event record

	    case 'tblHR':
	    {		// Header record
require_once __NAMESPACE__ . '/LegacyHeader.inc';
			// only one record, key ignored
			$record		= new LegacyHeader();
			$top		= 'header';
			break;
	    }		// Header record

	    case 'tblIR':
	    {		// Person record
require_once __NAMESPACE__ . '/Person.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Persons',$id);
			    }		// search parameters
			    else
			    {
				    $record	= new Person(array('idir' => $id));
			    }
			}
			else
			{
			    $record	    = new RecordSet('Persons',$parms);
			}
			$top	        = 'indiv';
			break;
	    }		// Person record

	    case 'tblLR':
	    {		// Location record
require_once __NAMESPACE__ . '/Location.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Locations',$id);
			    }		// search parameters
			    else
			    {
				    $record	= new Location(array('idlr' => $id));
			    }
			}
			else
			{
			    $record	= new RecordSet('Locations',$parms);
			}
			$top	= 'location';
			break;
	    }		// Location record

	    case 'tblMR':
	    {		// Family record
require_once __NAMESPACE__ . '/Family.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	= new RecordSet('Families',$id);
			    }		// search parameters
			    else
			    {
					$record	= new Family(array('idmr' => $id));
			    }
			}
			else
			{
			    $record	= new RecordSet('Families',$parms);
			}
			$includeParm2		= true;
			$includeParm3		= true;	
			$top			= 'family';
			break;
	    }		// Family record

	    case 'tblNX':
	    {		// Name record
require_once __NAMESPACE__ . '/Name.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Names',$id);
			    }		// search parameters
			    else
			    {
				    $record	= new Name(array('idnx'	=> $id));
			    }
			}
			else
			{
			    $record	    = new RecordSet('Names',$parms);
			}
			$top	= 'name';
			break;
	    }		// Name record

	    case 'tblNR':
	    {		// Surname record
require_once __NAMESPACE__ . '/Surname.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Surnames',$id);
			    }		// search parameters
			    else
			    {
				    $record	= new Surname(array('idnr' => $id));
			    }
			}
			else
			{
			    $record	= new RecordSet('Surnames',$parms);
			}
			$top	= 'surname';
			break;
	    }		// Surname record

	    case 'Nicknames':
	    {		// given name record
            require_once __NAMESPACE__ . '/Nickname.inc';
            if (!array_key_exists('limit', $parms))
                $parms['limit']         = 10000;

			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    $record	= new RecordSet('Nicknames',$parms);
			    }		// search parameters
			    else
			    {
				    $record	= new Nickname(array('nickname' => $id));
                }
                if (strlen($warn) > 0)
                    showTrace();
			}
			else
			{
			    $record	= new RecordSet('Nicknames', $parms);
			}
			$top	= 'nickname';
			break;
	    }		// given name record

	    case 'tblSR':
	    {		// Master Source record
require_once __NAMESPACE__ . '/Source.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	= new RecordSet('Sources',$id);
			    }		// search parameters
			    else
			    {
					$record	= new Source(array('idsr' => $id));
			    }
			}
            else
			{
			    $record	            = new RecordSet('Sources',$parms);
			}
			$top	= 'source';
			break;
	    }		// Master Source record

	    case 'tblSX':
	    {		// Citation record
require_once __NAMESPACE__ . '/Citation.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	= new RecordSet('Citations',$id);
			    }		// search parameters
			    else
			    {
					$record	= new Citation(array('idsx' => $id));
			    }
			}
			else
			{
			    $record	= new RecordSet('Citations',$parms);
			}
			$top	= 'citation';
			break;
	    }		// Citation record

	    case 'tblTD':
	    {		// To Do record
require_once __NAMESPACE__ . '/ToDo.inc';
			if (isset($id))
			{
			    $record	= new ToDo(array('idtd' => $id));
			}
			else
			{
			    $record	= new RecordSet('ToDos',$parms);
			}
			$top	= 'todo';
			break;
	    }		// To Do record

	    case 'tblTR':
	    {		// Temple record
require_once __NAMESPACE__ . '/Temple.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	        = new RecordSet('Temples',$id);
			    }		// search parameters
			    else
			    {
					$record	        = new Temple(array('idtr' => $id));
			    }
			}
			else
			{
			    $record	            = new RecordSet('Temples',$parms);
			}
			$top	                = 'temple';
			break;
	    }		// Temple record

	    case 'tblBP':
	    case 'tblCP':
	    case 'tblHB':
	    case 'tblHL':
	    case 'tblRM':
	    case 'tblTC':
	    case 'tblTL':
	    case 'tblXI':
	    case 'tblXM':
	    {
			$key		            = 'id' . strtolower(substr($table,3));
			if (isset($id))
			{
			    $dbrow	            = array($key => $id);
			    $record	            = new Record($dbrow, $table);
			}
			else
			{
			    $record	            = new RecordSet($table, $parms);
			}
			break;
	    }

	    case 'Births':
	    {
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/BirthSet.inc';
			if (isset($domain) && isset($regyear) && $regnum)
			    $record	= new Birth($domain, $regyear, $regnum);
			else
			    $record	        = new BirthSet($parms);
			$top		        = 'birth';
			break;
	    }		// Births

	    case 'Deaths':
	    {
            require_once __NAMESPACE__ . '/Death.inc';
			if (isset($domain) && isset($regyear) && $regnum)
                $record		    = new Death($domain, $regyear, $regnum);
            else
                $record         = new RecordSet('Deaths', $parms);
			$top		        = 'death';
			break;
	    }		// Deaths

	    case 'Marriage':
	    {
require_once __NAMESPACE__ . '/Marriage.inc';
			if (isset($domain) && isset($regyear) && $regnum)
			    $record		    = new Marriage($domain, $regyear, $regnum);
            else
                $record         = new RecordSet('Marriage', $parms);
			$top		        = 'marriage';
			break;
	    }		// Marriage

	    case 'CountyMarriage':
	    {
require_once __NAMESPACE__ . '/CountyMarriage.inc';
			if (isset($volume))
			{
			    if (isset($reportNo))
			    {		// volume and report number
					if (isset($itemNo))
					{	// individual record
					    $parms	= array('Domain'	=> $domain,
						    		    'Volume'	=> $volume,
				 		        		'ReportNo'	=> $reportNo,
		 			            		'ItemNo'	=> $itemNo);
		 
					    $record	= new CountyMarriage($parms);
					}	// individual record
					else
					{	// only volume and report number
					    $parms	= array('Domain'	=> $domain,
						        	    'Volume'	=> $volume,
				 		        		'ReportNo'	=> $reportNo);
		 
					    $record	= new RecordSet('CountyMarriages',$parms);
					}	// only volume and report number
			    }		    // volume and report number
			    else
			    {		    // only volume number
					$parms	    = array('Domain'	=> $domain,
						                'Volume'	=> $volume);
					$record     = new RecordSet('CountyMarriages',$parms);
			    }			// only volume number
			}
			else
			{			    // only domain
			    $parms	        = array('Domain'	=> $domain);
	 
			    $record	        = new RecordSet('CountyMarriages',$parms);
			}			    // only domain
			$top		        = 'marriage';
			break;
	    }		            // CountyMarriage

	    case 'CountyMarriageReports':
	    {
require_once __NAMESPACE__ . '/CountyMarriageReport.inc';
			if ($domain == 'CAON')
			    $domain	        = 'CACW';
			if (isset($volume))
			{
			    if (isset($reportNo))
			    {
					$parms	    = array('Domain'	=> $domain,
						                'Volume'	=> $volume,
	 				                    'ReportNo'	=> $reportNo);
	 
					$record	    = new CountyMarriageReport($parms);
			    }
			    else
			    {
					$parms	    = array('Domain'	=> $domain,
						                'Volume'	=> $volume);
	 
					$record	    = new RecordSet('CountyMarriageReports',$parms);
			    }	
			}
			else
			{
			    $parms	        = array('Domain'	=> $domain);
	 
			    $record	        = new RecordSet('CountyMarriageReports',$parms);
			}
			$top		        = 'reports';
			break;
	    }		// CountyMarriageReport

	    case 'Countries':
	    {
            $top		        = 'country';
            $record	            = new RecordSet('Countries',$parms);
			break;
	    }		// Countries

	    case 'Domains':
	    {
			$top		= 'domain';
			if (array_key_exists('cc', $parms))
			{
				$parms['domain']	= '^' . $parms['cc'];
			    unset($parms['cc']);
			}
			$record	= new RecordSet('Domains',$parms);
			break;
	    }		// Countries

	    case 'Counties':
	    {
			$top		= 'county';
			if (array_key_exists('county', $parms))
				$record	= new County($parms['domain'],$parms['county']);
			else
				$record	= new RecordSet('Counties',$parms);
			break;
	    }		// Deaths

	    case 'Townships':
	    {
require_once __NAMESPACE__ . '/Township.inc';
			$record		= new RecordSet('Townships',$parms);
			$top		= 'township';
			break;
	    }		// Townships

	    case 'Users':
	    {
require_once __NAMESPACE__ . '/User.inc';
			if (isset($id))
			{
			    $record	= new User($id);
			}
			else
			    $record	= new RecordSet('Users',$parms);
			$top		= 'user';
			if (!canUser('all'))
			    $msg	.= "You are not authorized to view this record. ";
			break;
	    }		// Users

	    case 'Blogs':
	    {
require_once __NAMESPACE__ . '/Blog.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	= new RecordSet('Blogs',$id);
			    }		// search parameters
			    else
			    {
					$record	= new Blog($id);
			    }
			}
			else
			{
			    $record	= new RecordSet('Blogs',$parms);
			}
			$top		= 'blog';
			break;
	    }		// Blogs

	    case 'MethodistBaptisms':
	    {
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
				    if (isset($volume))
				        $id['volume']	= $volume;
					$record	    = new RecordSet('MethodistBaptisms',$id);
			    }		// search parameters
			    else
			    {
					$record	    = new MethodistBaptism(array('idmb' => $id));
			    }
			}
			else
			{
			    $record	        = new RecordSet('MethodistBaptisms',$parms);
			}
			$top		        = 'baptism';
			break;
	    }		// MethodistBaptisms

	    case 'Censuses':
        {
			if (isset($id))
			{
			    if (is_array($id))
			    {		// search parameters
					$record	    = new RecordSet('Censuses',$id);
			    }		// search parameters
			    else
			    {
					$record	    = new Census(array('censusid' => $id));
			    }
			}
			else
			{
			    $record	        = new RecordSet('Censuses',$parms);
			}
			$top		= 'census';
			break;
	    }		// Censuses

	    case 'Districts':
	    {
			$record	    = new RecordSet('Districts',$parms);
			$top		= 'district';
			break;
	    }		// District

	    case 'SubDistricts':
	    {
			$record	    = new RecordSet('SubDistricts',$parms);
			$top		= 'subdistrict';
			break;
	    }		// SubDistrict

	    case 'Pages':
	    {
			$record		= new PageSet($parms);
			$top		= 'page';
			break;
	    }		// Pages

	    case 'Census1851':
	    case 'Census1861':
	    {
require_once __NAMESPACE__ . '/CensusLine.inc';
			$record	    = new RecordSet('CensusLine',$parms);
			$top		= 'line';
			break;
	    }		// Censuses

	    case 'Census1871':
	    case 'Census1881':
	    case 'Census1891':
	    case 'Census1901':
	    case 'Census1906':
	    case 'Census1911':
	    case 'Census1916':
	    case 'Census1921':
	    {
require_once __NAMESPACE__ . '/CensusLine.inc';
            $record	        = new RecordSet($table,$parms);
			$top		    = 'line';
			break;
	    }		// Censuses

	    default:
	    {
			$msg	.= "Table `$table` is not supported by this script. ";
	    }
	}		// switch($table)
//} catch(Exception $e) {
//	    $msg	.= "Unable to create instance of `$table`. " .
//				   $e->getMessage();
//	}
}			// no errors detected previously

// protect against very large response sets
if (strlen($msg) == 0)
{
    if (is_array($record))
    {
	    $count		        = count($record);
	    if ($count == 0)
	        $msg	        = 'No matches.';
	    else
        if ($count == 1)
        {
            $record		    = reset($record);
        }
    }
    else
    if ($record instanceof RecordSet)
    {
	    $count		        = $record->count();
        if ($count == 1)
        {
            $record		    = $record->rewind();
        }
    }
	if ($count == 0)
	    $msg	        = 'No matches.';
	else
	if ($count > 100)
	    $msg	        .= "Too many '$extTableName' records to return: " .
                            number_format($count) . ' matches.';
}

