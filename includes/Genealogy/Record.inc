<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Record.inc															*
 *																		*
 *  Definition of a base class representing a record in a genealogical	*
 *  database.															*
 *																		*
 *  History:															*
 *		2010/08/14		Save function does insert if record does not yet*
 *						exist on database server.						*
 *		2010/08/14		Add update from POST function					*
 *		2010/08/21		escape XML entities in generated command		*
 *						clear needInsert flag after Insert				*
 *		2010/09/28		ensure zero length strings inserted as strings	*
 *						not NULL										*
 *		2010/10/10		check error for result of SQL command, not		*
 *						connection										*
 *		2010/10/19		connection member made protected				*
 *						add method getTableName							*
 *		2010/10/21		Eliminate 4th parameter of constructor, derive	*
 *						from table attribute.							*
 *		2010/10/23		connection made global							*
 *		2011/01/28		expand functionality of constructor to permit	*
 *						passing numeric key of record in place of an	*
 *						array											*
 *		2012/01/12		permit passing null as the parameter of toXml	*
 *		2012/08/06		add generic setField method						*
 *		2012/08/29		use standard SQL INSERT syntax					*
 *		2012/10/01		in Record::save only update those fields		*
 *						of the database record that have been changed	*
 *		2012/10/13		validate supplied table name parameter in		*
 *						constructor										*
 *						validate presence of primary key in supplied row*
 *						expand functionality of Record::save			*
 *						add method delete for deleting associated		*
 *						database record									*
 *						correct errors in table specific information	*
 *		2012/11/04		in determining whether a field value was		*
 *						changing as a result of a call to				*
 *						Record::set the code now uses !== so that		*
 *						strings that contain only numeric digits are	*
 *						not compared as numbers.						*
 *						in method save if the value is a string and has	*
 *						a leading zero, pass it to SQL as a string, not	*
 *						a number										*
 *		2012/11/12		add new method Record::changed to determine		*
 *						if the instance of Record needs to be saved		*
 *		2013/01/11		do not accidentally convert internal date		*
 *						encoding to integer								*
 *		2013/01/29		table tblDM does not have a numeric key			*
 *						do not query for last inserted record on tables	*
 *						which do not have auto-increment keys			*
 *						do not update database if user is not authorized*
 *		2013/02/07		throw exception if method getId called in an	*
 *						incorrect state									*
 *		2013/02/10		table tblHR does not have a numeric key			*
 *		2013/02/12		add method isExisting							*
 *		2013/02/21		correct syntax error in save					*
 *		2013/02/26		add information to XML in method save			*
 *		2013/03/02		add option to delete to emit XML trace			*
 *		2013/03/05		include prime key value in XML output header tag*
 *		2013/03/06		return 0 from delete if record never inserted	*
 *		2013/03/12		handle array values parameters in postUpdate	*
 *		2013/05/10		add support for Census tables					*
 *		2013/06/10		add support for BMD tables						*
 *		2013/07/13		add support for SubDistricts and Pages			*
 *		2013/08/09		class renamed to Record because it now supports	*
 *						tables that are not part of Legacy				*
 *		2013/08/17		add support for 1921 census						*
 *		2013/11/15		handle lack of database server connection		*
 *						correct date format for added and updated fields*
 *		2014/03/25		subclass Blog requires access to $primeKey		*
 *						add table Users into supported set				*
 *		2014/03/31		permit non-subscriber to write to Blogs			*
 *		2014/04/03		when saving a new record into a table with		*
 *						an auto-increment primary key, log a version	*
 *						of the INSERT command that will maintain the	*
 *						server assigned primary key value				*
 *		2014/04/13		report bad field names in postUpdate			*
 *		2014/05/02		logged insert command truncated if there was a	*
 *						left bracket in any string value				*
 *		2014/08/31		htmlentities misspelled							*
 *		2014/09/27		add method isOwner								*
 *		2014/10/01		add include of RecOwner class					*
 *						allow the creator of any record to update it	*
 *						during the initialization process.  Formerly	*
 *						method isOwner threw an exception if it was		*
 *						called for a new record before it was saved.	*
 *		2014/10/03		add method displayPictures to display instances	*
 *						of class Picture for any supported Record		*
 *		2014/10/22		make parameter of displayPictures optional		*
 *						throw exception for bad table in displayPictures*
 *		2014/11/29		put trace information into $warn				*
 *		2014/12/10		method dump did not properly escape field		*
 *						values for case where issued in XML document	*
 *		2015/01/06		method displayPictures threw exception if		*
 *						called for a record which had not been created	*
 *						in the database yet.							*
 *						Add default picture ident type to common table	*
 *						of database characteristics						*
 *		2015/02/18		method save did not generate debug output		*
 *						for update										*
 *						some error messages were printed rather than	*
 *						deferred until the body of the page				*
 *						add parameter option to create a new record		*
 *						with default field values from the SQL server	*
 *		2015/02/24		add method getSurnameChk which is shared		*
 *						between tblIR, tblNR, and tblNX					*
 *		2015/03/21		support string parameter to save				*
 *		2015/05/27		generate debug output from delete method		*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/10/07		Record::save did not set $row[$prime]			*
 *						use prepared statements for insert and update	*
 *		2015/12/08		move implementation of method displayPictures	*
 *						to a derived class								*
 *		2016/01/21		add Censuses table								*
 *						support non-integral primary keys in save		*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default and change signature	*
 *						to match LegacyIndiv::toXml						*
 *		2016/05/09		permit class to insert a record with a known	*
 *						primary numeric key if the class has identified	*
 *						that the record is improperly missing			*
 *		2016/05/20		assume default for new table with no definition	*
 *						rename method getField to get and method		*
 *						setField to set									*
 *		2017/01/13		add method getRecordJson						*
 *		2017/02/04		add tables `Countries` and `Domains`			*
 *		2017/04/04		do not throw exception for unauthorized save	*
 *		2017/07/20		logSqlUpdate changed							*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/05		surname patterns moved to Surname object		*
 *		2017/09/11		implement interface Iterator					*
 *		2017/10/14		move translation between user-friendly external	*
 *						table names and the internal names used by		*
 *						the SQL server from getRecordCommon.php			*
 *		2017/10/15		add static method getInformation to hide		*
 *						internal tables including $primeKey				*
 *		2017/10/17		improve method postUpdate including support		*
 *						for field name synonyms							*
 *						method getId extended to support primary keys	*
 *						that are not AUTOINCREMENT integers and to		*
 *						report problems by returning null rather than	*
 *						throwing an exception							*
 *		2017/10/22		add function debugPrepQuery						*
 *		2017/11/20		support changing primary key in save			*
 *						use debugPrepQuery for debug output in save		*
 *						use field names for prepared statement parms	*
 *		2017/11/21		add support for subscript access				*
 *						add non-static logging support					*
 *		2017/11/28		improve support for tables without a class		*
 *		2017/12/10		add Nicknames table								*
 *		2017/12/18		methods get and put extended to support			*
 *						accessing the temporary field table				*
 *		2018/01/11		add extras to output of method dump				*
 *		2018/01/14		add default support for tables with multiple	*
 *						primary keys									*
 *		2018/02/13		correct output of toXML where prime is multiple	*
 *						fields											*
 *		2018/03/12		report problem in debugPrepQuery if sqlParms	*
 *						contains an array value							*
 *		2018/04/01		print backtrace failed for recursion on			*
 *						parameters										*
 *		2018/10/14      add method getArray                             *
 *		                iteration goes over both row and extras         *
 *		2018/11/07      add method setGetModeHtml which controls how    *
 *		                values are returned by get                      *     
 *	    2018/11/11      $this->msg and $this->getErrors() added to      *
 *	                    permit constructors to report                   *
 *	                    invalid parameter values, for example bad       *
 *	                    password without throwing an exception.         *
 *	                    Instead if an attempt is made                   *
 *	                    to update or create the record the request      *
 *	                    is ignored with a warning.                      *
 *		2018/10/14      fix iteration                                   *
 *		                delete method getArray                          *
 *		2018/11/21      add table Ocfa                                  *
 *		2018/12/02      remove numeric code to text tables              *
 *		                change exceptions in constructor and save       *
 *		                to add to $this->msg instead                    *
 *		                add member $record->template and setTemplate    *
 *		2018/12/12      add support for multiple field keys to getId    *
 *		2019/04/06      add method __toString                           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Record																*
 *																		*
 *  Definition of a base class implementing common behavior for a		*
 *  record within a genealogy database.									*
 ************************************************************************/

class Record implements Iterator, ArrayAccess
{
    /********************************************************************
     *	$this->row														*
     *																	*
     *	Row returned from database query.								*
     ********************************************************************/
    protected	$row		= null;

    /********************************************************************
     *	$this->changed													*
     *																	*
     *	Row of fields whose values have changed.						*
     *	$this->changed['name'] contains the value of field 'name'		*
     *  but only if it has changed since the record was last loaded from*
     *  the database.													*
     ********************************************************************/
    protected	$changed	= null;

    /********************************************************************
     *	$this->former													*
     *																	*
     *	If the record has been changed the original values of the		*
     *	record are recorded in this array.								*
     ********************************************************************/
    protected	$former		= null;

    /********************************************************************
     *	$this->extras													*
     *																	*
     *	This element permits adding additional temporary information	*
     *	to the record which is not preserved in the database.			*
     *	This information is only accessible through subscripts			*
     ********************************************************************/
    protected	$extras		= array();

    /********************************************************************
     *	$this->table													*
     *																	*
     *	Name of the database table whose records are presented by a		*
     *	class derived from Record.										*
     ********************************************************************/
    protected	$table		= '';

    /********************************************************************
     *	$this->prime													*
     *																	*
     *	The name of the key that uniquely identifies records in this	*
     *	table.  The name of the key is given all in lower case to		*
     *	agree with the default PDO row interface.						*
     ********************************************************************/
    protected	$prime		= 'id';

    /********************************************************************
     *	$this->template													*
     *																	*
     *	If not null this is an instance of Template which is used       *
     *	to provide the text strings necessary to implement              *
     *	internationalization of text outputs from the current Class.    *
     ********************************************************************/
    protected	$template	= null;

    /********************************************************************
     *	$this->needInsert												*
     *																	*
     *	true if record does not yet exist in the						*
     *	database, false if it just needs to be							*
     *	updated to include changes made to the							*
     *	memory copy.													*
     ********************************************************************/
    protected	$needInsert	= true;

    /********************************************************************
     *	$this->getModeHtml												*
     *																	*
     *	If true the the method get should return values that can        *
     *	be inserted into an HTML page. For example empty string values  *
     *	and null values result in returning "&nbsp;", and characters    *
     *	which have a special meaning to HTML are escaped.               *
     ********************************************************************/
    protected	$getModeHtml	= false;

    /********************************************************************
     *	$this->msg       												*
     *																	*
     *	Any error messages accumulated by constructor.                  *
     *	See Record::getErrors                                           *
     ********************************************************************/
    protected 	$msg	        = '';

    /********************************************************************
     *	Record::$initRow												*
     *																	*
     *	Array containing default values for a new record.				*
     *	Overridden for derived class.									*
     ********************************************************************/
    protected static	$initRow	= array();

    /********************************************************************
     *	Record::$translate												*
     *																	*
     *	Alternative field name identifiers								*
     ********************************************************************/
    protected static	$translate	= array();

    /********************************************************************
     *	Record::$defaultOrder											*
     *																	*
     *	The default contents of the ORDER BY clause in SELECTs			*
     *	If this is an empty string then the value of the 'prime'		*
     *	attribute of the entry in $primeKey is used.					*
     ********************************************************************/
    protected static	$defaultOrder	= '';

    /********************************************************************
     *	Record::$primeKey												*
     *																	*
     *  This table contains information about the supported tables.		*
     *	Each entry is an array of attributes:							*
     *																	*
     *  'prime'			The primary key name for the table.				*
     *					The key names are in lower case so they will	*
     *					match the field name in the query response.		*
     *	'srvmg'			true if the setting of the primary key is		*
     *					managed by the database server for new records,	*
     *					and false if it is managed by the application.	*
     *	'fldcount'		not currently used								*
     *	'classname'		name of the class that implements the interface	*
     *					to this table									*
     ********************************************************************/
    protected static $primeKey = array(
		'tblAR'		    => array('prime'	=> 'idar',
							 	'srvmg'	    => true,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'Address'),
		'tblBP'		    => array('prime'	=> 'idbp',
							 	'srvmg'	    => true,
							 	'fldcount'	=> 4,
							 	'classname'	=> ''),	// part of Picture
		'tblBR'			=> array('prime'	=> 'idbr',
							 	'srvmg'		=> true,
							 	'fldcount'		=> 4,
							 	'classname'		=> 'Picture'),
		'tblCP'			=> array('prime'	=> 'idcp',
							 	'srvmg'		=> false,
							 	'fldcount'		=> 4,
							 	'classname'		=> ''),	// part of Child
		'tblCR'			=> array('prime'	=> 'idcr',
							 	'srvmg'		=> true,
							 	'fldcount'		=> 4,
							 	'classname'		=> 'Child'),
		'tblDM'			=> array('prime'	=> '',
							 	'srvmg'		=> false,
							 	'fldcount'		=> 4,
							 	'classname'		=> 'DontMergeEntry'),
		'tblER'			=> array('prime'	=> 'ider',
							 	'srvmg'		=> true,
							 	'fldcount'		=> 4,
							 	'classname'		=> 'Event'),
		'tblHB'			=> array('prime'	=> 'idhb',
							 	'srvmg'		=> true,
							 	'fldcount'		=> 3,
							 	'classname'		=> ''),
		'tblHL'			=> array('prime'	=> 'idhl',
							 	'srvmg'		=> true,
							 	'fldcount'		=> 3,
							 	'classname'	=> ''),
		'tblHR'		    => array('prime'	=> '',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 2,
							 	'classname'	=> 'LegacyHeader'),
		'tblIR'		    => array('prime'	=> 'idir',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 11,
							 	'classname'	=> 'Person'),
		'tblLR'		    => array('prime'	=> 'idlr',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 17,
							 	'classname'	=> 'Location'),
		'tblMR'		    => array('prime'	=> 'idmr',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'Family'),
// Note that srvmg is generally true if the primary key is	        *
// an auto_increment field, however the primary key of				*
// tblMS is defined as auto_increment in the original				*
// Legacy database structure, even though it logically				*
// should not be, and the server is not permitted to manage		    *
// this key by Record.												*
		'tblNR'	    	=> array('prime'	=> 'idnr',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'Surname'),
		'tblNX'		    => array('prime'	=> 'idnx',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 17,
							 	'classname'	=> 'Name'),
		'tblRM'		    => array('prime'	=> 'idrm',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 4,
							 	'classname'	=> ''),
		'tblSR'		    => array('prime'	=> 'idsr',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'Source'),
		'tblSX'		    => array('prime'	=> 'idsx',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'Citation'),
		'tblTC'		    => array('prime'	=> 'idtc',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 5,
							 	'classname'	=> ''), // part of ToDo
		'tblTD'		    => array('prime'	=> 'idtd',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 20,
							 	'classname'	=> 'ToDo'),
		'tblTL'		    => array('prime'	=> 'idtl',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 5,
							 	'classname'	=> ''),
		'tblTR'		    => array('prime'	=> 'idtr',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 9,
							 	'classname'	=> 'Temple'),
		'tblXI'		    => array('prime'	=> 'deletedid',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 1,
							 	'classname'	=> ''),
		'tblXM'		    => array('prime'	=> 'deletedid',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 1,
							 	'classname'	=> ''),
		'RecOwners'	    => array('prime'	=> 'ro_index',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'RecOwner'),
		'Users'		    => array('prime'	=> 'id',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 7,
							 	'classname'	=> 'User'),
		'MethodistBaptisms'=> array('prime'	=> 'idmb',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 7,
							 	'classname'	=> 'MethodistBaptism'),
		'Ocfa'          => array('prime'	=> 'RecordIndex',
							 	'srvmg'	    => true,
							 	'fldcount'	=> 8,
							 	'classname'	=> 'Ocfa'),
		'Blogs'		    => array('prime'	=> 'bl_index',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 7,
							 	'classname'	=> 'Blog'),
		'Births'	    => array('prime'	=> array('b_regdomain','b_regyear','b_regnum'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 34,
							 	'classname'	=> 'Birth'),
		'Marriage'	    => array('prime'	=> array('m_regdomain','m_regyear','m_regnum'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 16,
							 	'classname'	=> 'Marriage'),
		'MarriageIndi'	=> array('prime'	=> array('m_regdomain','m_regyear','m_regnum','m_role'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 20,
							 	'classname'	=> ''),
		'CountyMarriageReports'	=> array('prime'=>  array('m_regdomain', 'm_volume', 'm_reportno'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 11,
							 	'classname'	=> 'CountyMarriageReport'),
		'CountyMarriages'=> array('prime'	=> array('m_regdomain', 'm_volume', 'm_reportno', 'm_itemno', 'm_role'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 11,
							 	'classname'	=> 'CountyMarriage'),
		'Deaths'	    => array('prime'	=> array('d_regdomain','d_regyear','d_regnum'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 48,
							 	'classname'	=> 'Death'),
		'Countries'	    => array('prime'	=> 'code',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'Country'),
		'CountryNames'	=> array('prime'	=> '',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'CountryName'),
		'Domains'	    => array('prime'	=> array('domain','language'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 3,
							 	'classname'	=> 'Domain'),
		'Counties'	    => array('prime'	=> array('domain','code'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'County'),
		'Languages'	    => array('prime'	=> 'code639_1',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 4,
							 	'classname'	=> 'Language'),
		'Townships'	    => array('prime'	=> '',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'Township'),
		'Concessions'	=> array('prime'	=> '',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'Concession'),
		'Districts'	    => array('prime'	=> array('d_census',
										 'd_id'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 5,
							 	'classname'	=> 'District'),
		'SubDistricts'	=> array('prime'	=> array('sd_census',
										 'sd_distid',
										 'sd_id',
										 'sd_div',
										 'sd_sched'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 16,
							 	'classname'	=> 'SubDistrict'),
		'Pages'	    	=> array('prime'	=> array('pt_census',
										 'pt_distid',
										 'pt_sdid',
										 'pt_div',
										 'pt_sched'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 10,
							 	'classname'	=> 'Page'),
		'Census1851'	=> array('prime'	=> array('province',
										 'district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 7,
							 	'classname'	=> 'CensusLine'),
		'Census1861'	=> array('prime'	=> array('province',
										 'district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 7,
							 	'classname'	=> 'CensusLine'),
		'Census1871'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1881'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1891'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1901'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1906'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1911'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1916'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Census1921'	=> array('prime'	=> array('district',
										 'subdistrict',
										 'division',
										 'page',
										 'line'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'CensusLine'),
		'Censuses'	    => array('prime'	=> 'censusid',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 6,
							 	'classname'	=> 'Census'),
		'FieldComments'	=> array('prime'	=> 'fc_index',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 12,
							 	'classname'	=> 'FieldComment'),
		'GraveStones'	=> array('prime'	=> 'gs_index',
							 	'srvmg'	=> true,
							 	'fldcount'	=> 13,
							 	'classname'	=> 'GraveStone'),
		'Nicknames'	    => array('prime'	=> 'nickname',
							 	'srvmg'	=> false,
							 	'fldcount'	=> 3,
							 	'classname'	=> 'Nickname'),
		'Videos'	    => array('prime'	=> array('filename','lang'),
							 	'srvmg'	=> false,
							 	'fldcount'	=> 3,
							 	'classname'	=> ''));

    /********************************************************************
     *	Record::$externalTableNames										*
     *																	*
     *  This table contains alternate external names for the SQL tables	*
     *	to be more intuitive and to hide the internal implementation	*
     *	from casual users.												*
     *																	*
     *	Table names should always have a plural form because they		*
     *	contain multiple records, with the exception of the Header		*
     *	table.															*
     ********************************************************************/
    protected static $externalTableNames	= array(
				'Addresses'	    	=> 'tblAR',
				'PictureBases'		=> 'tblBP',
				'Pictures'		    => 'tblBR',
				'ChildParentRels'	=> 'tblCP',
				'Children'		    => 'tblCR',
				'DontMergeEntries'	=> 'tblDM',
				'Events'		    => 'tblER',
				'Bookmarks'		    => 'tblHB',
				'HistoryList'		=> 'tblHL',
				'Header'	    	=> 'tblHR',
				'Persons'	    	=> 'tblIR',
				'Locations'	    	=> 'tblLR',
				'Families'	    	=> 'tblMR',
				'Surnames'	    	=> 'tblNR',
				'Names'		    	=> 'tblNX',
				'Reminders'	    	=> 'tblRM',
				'Sources'	    	=> 'tblSR',
				'Citations'	    	=> 'tblSX',
				'ToDoCategories'	=> 'tblTC',
				'ToDoEntries'		=> 'tblTD',
				'ToDoLocalities'	=> 'tblTL',
				'Temples'	    	=> 'tblTR',
				'RemovedPersons'	=> 'tblXI',
				'RemovedFamilies'	=> 'tblXM',
				'Marriages'		    => 'Marriage');

    /********************************************************************
     *	Record::$internalTableNames										*
     *																	*
     *  This table implements the translation between internal table	*
     *	names used by the implementation to intuitive external			*
     *	table names presented to casual visitors.						*
     ********************************************************************/
    protected static $internalTableNames	= array(
				'tblAR'			=> 'Addresses',
				'tblBP'			=> 'PictureBases',
				'tblBR'			=> 'Pictures',
				'tblCP'			=> 'ChildParentRels',
				'tblCR'			=> 'Children',
				'tblDM'			=> 'DontMergeEntries',
				'tblER'			=> 'Events',
				'tblHB'			=> 'Bookmarks',
				'tblHL'			=> 'HistoryList',
				'tblHR'			=> 'Header',
				'tblIR'			=> 'Persons',
				'tblLR'			=> 'Locations',
				'tblMR'			=> 'Families',
				'tblNR'			=> 'Surnames',
				'tblNX'			=> 'Names',
				'tblRM'			=> 'Reminders',
				'tblSR'			=> 'Sources',
				'tblSX'			=> 'Citations',
				'tblTC'			=> 'ToDoCategories',
				'tblTD'			=> 'ToDoEntries',
				'tblTL'			=> 'ToDoLocalities',
				'tblTR'			=> 'Temples',
				'tblXI'			=> 'RemovedPersons',
				'tblXM'			=> 'RemovedFamilies');

    /********************************************************************
     *	function Record::__construct									*
     *																	*
     *	Create an instance of the class Record from 					*
     *  a row returned from a database query.							*
     *																	*
     *	Input:															*
     *	    $dbrow		row returned from database SELECT or			*
     *					associative array containing key of record		*
     *					for example array('IDIR' => $idir)				*
     *	    $table		Name of database table containing records for	*
     *					this class										*
     *					This is either the actual SQL table name or		*
     *	            	an external synonym				                * 
     ********************************************************************/
    function __construct($dbrow,
						 $table)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		if (is_null($connection))
		{			// no database connection
		    throw new Exception(
						"Record::__construct: no database server connection");
		}			// no database connection

		// support external table name synonym
		if (array_key_exists($table, self::$externalTableNames))
		    $table		    = self::$externalTableNames[$table];

		// validate the $table parameter
		if (strlen($table) == 0 ||
		    !array_key_exists($table, self::$primeKey))
		{		        	// assume defaults
		    self::$primeKey[$table]	= array('prime'		=> '',
        									'srvmg'		=> false,
        									'fldcount'	=> 3);
		}			        // assume defaults

		// initialize object
		$this->table		= $table;
		$this->prime		= self::$primeKey[$table]['prime'];
		$fldcount		    = self::$primeKey[$table]['fldcount'];
		$prime			    = $this->prime;
        $this->changed		= array();
        if (is_array($this->row))
            return;         // already constructed

		// interpret first parameter
		if (is_array($dbrow))
		{			        // associative array
		    if (count($dbrow) < $fldcount)
		    {			    // key of record
				$query		= "SELECT * FROM $table";
				$parms		= array();
				$and		= ' WHERE ';
				foreach($dbrow as $fldname => $value)
				{		// process each parameter
				    $query	.= $and . $fldname . '=?';
				    array_push($parms, $value);
				    $and	= ' AND ';
				}		    // process each parameter
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $parms);
				if ($stmt->execute($parms))
				{		    // successful
				    if ($debug)
						$warn	.= "<p>Record::__construct: " . __LINE__ .
								"'$queryText'</p>\n";
				    $result	= $stmt->fetch(PDO::FETCH_ASSOC);
				    if (is_array($result))
				    {		// have row from DB
						$this->row		        = $result;
						$this->needInsert   	= false;
				    }		// have row from DB
				    else
				    {		// no existing record
						if ($debug)
						    $warn	.= "<p>Record::__construct: " .
								    __LINE__ .
								    " no existing record matches</p>\n";
						$info			        = $this->getInfo();
						$result			        = $info['initrow'];
						foreach($dbrow as $fldname => $value)
						{		// update row from parameters
						    $fieldLc	    	= strtolower($fldname);
						    $result[$fieldLc]	= $value;
						}		// update row from parameters
						$this->row		        = $result;
						$this->needInsert	    = true;
				    }		// no existing record
				}		    // successful
				else
				{		    // error retrieving record row
				    $this->msg  .=
						    "Record::__construct: '$queryText', result=" .
							   print_r($stmt->errorInfo(), true);
				}		// error retrieving record row
		    }			// key of record
		    else
		    {			// full row from database
				if (is_array($prime) || strlen($prime) == 0)
				{		// no numeric key
				    $this->row		            = $dbrow;
				}		// no numeric key
				else
				if (array_key_exists($prime, $dbrow))
				{		// element exists for primary key
				    $this->needInsert	        = $dbrow[$prime] == 0;
				    $this->row		            = $dbrow;
				}		// element exists for primary key
				else
				    $this->msg  .= "Record::__construct: " .
						    "supplied row does not contain field '$prime'";
		    }			// full row from database
		}		// associative array
		else
		    $this->msg  .= "Record::__construct: invalid parameter type " .
						    gettype($dbrow) . ", value=" . $dbrow;
    }		// __construct

    /********************************************************************
     *	function Record::getId											*
     *																	*
     *	Get the unique identifier of the record.						*
     *	This can only be called if the record is in the database.		*
     *																	*
     *	Returns:														*
     *	    unique identifier value of the record or null				*
     ********************************************************************/
    function getId()
    {
		global $warn;

        if (is_null($this->prime) ||
            (is_string($this->prime) && strlen($this->prime) == 0))
		{
		    $warn	.= "<p>Record::getId: table " . $this->table .
						" does not have a unique primary key</p>\n";
		    return null;
        }
        else
        if (is_array($this->prime))
        {
            $retval             = '';
            $comma              = '';
            foreach($this->prime as $keyName)
            {
                $value          = $this->row[$keyName];
                if (is_numeric($value))
                    $retval     .= "$comma$value";
                else
                    $retval     .= "$comma'$value'";
                $comma          = ',';
            }
            return $retval;
        }
		$retval	                = $this->row[$this->prime];
		if ($this->needInsert && $retval == 0)
		{
		    $warn	.= "<p>Record::getId: table " . $this->table .
				" record does not exist in database</p>\n";
		    return null;
		}
		return $retval;
    }		// function Record::getId

    /********************************************************************
     *  function Record::getTableName									*
     *																	*
     *  Returns the name of the database table associated with			*
     *  this instance of Record.  This value can be used				*
     *  to identify the type of record.  For example if the value		*
     *	is "tblIR" then this is an instance of Person.					*
     ********************************************************************/
    function getTableName()
    {
		return $this->table;
    }		// function Record::getTableName

    /********************************************************************
     *	function Record::getName										*
     *																	*
     *	Get a descriptive summary of the object.						*
     *	This function is the default implementation so that getName		*
     *	can be called for any instance of Record.						*
     *																	*
     *	Input:															*
     *	    $options	permit inherited classes to have a parameter	*
     *																	*
     *	Returns:														*
     *	    String containing a description of the record.				*
     ********************************************************************/
    function getName($options = null)
    {
		$name		    = $this->table;
		$information	= $this->getInfo();
		$prime		    = $information['prime'];
		if (is_array($prime))
		{
		    $connector	= ':';
		    foreach($prime as $fieldName)
		    {
				$name	.= ": $fieldName=" . $this->row[$fieldName];
				$connector	= ',';
		    }
		}
		else
		if (strlen($prime) > 0)
		    $name	.= ": $prime=" . $this->row['prime'];
		else
		if (array_key_exists('name', $this->row))
		    $name	.= ", name='" . $this->row['name'] . "'"; 
		else
		{		// include the first field in the record
		    foreach($this->row as $key => $value)
		    {
				$name	.= ", $key='$value'";
				break;
		    }
		}		// include the first field in the record
		return $name;
    }		// function Record::getName

    /********************************************************************
     *	Record::toString												*
     *																	*
     *	Default implementation                                          *
     ********************************************************************/
    function toString()
    {
        return  $this->getName();
    }

    /********************************************************************
     *	Record::__toString												*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return  $this->toString();
    }		// Record::__toString

    /********************************************************************
     *  function Record::isExisting										*
     *																	*
     *  Get an indication of whether or not this record is				*
     *  already in the database.										*
     *																	*
     *  Returns:														*
     *	    true		this record is already in the database			*
     *					calling method save will apply any pending		*
     *					changes to the existing record					*
     *	    false		this record is not in the database				*
     *					calling method save will insert the record		*
     ********************************************************************/
    function isExisting()
    {
		return !$this->needInsert;
    }		// function Record::isExisting

    /********************************************************************
     *  function Record::dump											*
     *																	*
     *  If debug output is enabled, display a diagnostic dump of the	*
     *  contents of a database record in the web page.					*
     *																	*
     *  Input:															*
     *		$label		descriptive identifier for record				*
     *		$force		if true output even if $debug false				*
     *		            This is not needed because you can write:       *
     *		                $warn   = $record->dump();                  *
     *																	*
     *	Return:															*
     *	    string containing trace output as HTML						*
     ********************************************************************/
    function dump($label	= null, 
				  $force	= false)
    {
		global $debug;
		global $warn;

		// ensure that there is a label to use for the display
		if (!is_string($label) || strlen($label) == 0)
		    $label	= $this->table;
		$prime		= $this->prime;

		$output		= '';
		if (is_array($this->row))
		{                       // record is initialized
		    $output	.= "<p class='label'>$label</p>\n";
		    $output	.= "<table border='1'>\n";
		    foreach($this->row as $key => $value)
		    {
				$output	.= "  <tr>\n";
				$output	.= "    <th class='label'>\n";
				if (is_array($prime))
				{
				    if (in_array($key, $prime))
						$output	.= "<i>`$key`</i>";
				    else
						$output	.= "`$key`";
				}
				else
				if ($key == $prime)
				    $output	.= "<i>`$key`</i>";
				else
				    $output	.= "`$key`";
				$output	.= "    </th>\n";
				$output	.= "    <td class='dataleft'>\n";
				if (is_null($value))
				    $output	.= 'null';
				else
				    $output	.= htmlspecialchars($value);
				$output	.= "    </td>\n";
				$output	.= "  </tr>\n";
		    }	// loop through keys

		    foreach($this->extras as $key => $value)
		    {
				$output	.= "  <tr>\n";
				$output	.= "    <th class='label'>\n";
				$output	.= "'$key'";
				$output	.= "    </th>\n";
				$output	.= "    <td class='dataleft'>\n";
				if (is_null($value))
				    $output	.= 'null';
				else
				    $output	.= htmlspecialchars($value);
				$output	.= "    </td>\n";
				$output	.= "  </tr>\n";
		    }	// loop through keys
		    $output	.= "</table>\n";
        }                       // record is initialized
        else
            $output .= "<p class='message'>record not initialized</p>\n";

		if (($debug || $force))
		    $warn		.= $output;
		return $output;
    }		// function Record::dump

    /********************************************************************
     *	function Record::getErrors										*
     *																	*
     *  Get any errors detected by the constructor.  These errors       *
     *  prevent updates being applied to the database but are           *
     *  accumulated as an alternative to throwing an exception.         *
     *																	*
     *	Returns:														*
     *	    string containing error messages                            *
     ********************************************************************/
    function getErrors($xml)
    {
        return $this->$msg;
    }		// function Record::getErrors

    /********************************************************************
     *	function Record::save											*
     *																	*
     *  Save changes made to the Record record into the	database.		*
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
		global	$msg;
		global	$connection;

		// get current date and time for added and updated fields in record
		$now		    = getdate();
		$today		    = $now['year'] * 10000 + $now['mon']*100 + $now['mday'];
		$hours		    = $now['hours'];
		if (strlen($hours) == 1)
		    $hours	    = '0' . $hours;
		$mins	    	= $now['minutes'];
		if (strlen($mins) == 1)
		    $mins	    = '0' . $mins;
		$currtime   	= $hours . ':' . $mins;

		// determine top level tag name for XML output
		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $xml	    = true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		}		// true

		// get information about table
		$table		    = $this->table;
		if ($table != 'Blogs' && $table != 'Users' && !canUser('edit'))
		{
		    if ($debug)
				$warn	.= "<p>Record::save: current user is not authorized to update table '$table'</p>\n";
		    return 0;
		}

		// to simplify expressions
		$prime		        = $this->prime;
		$information	    = self::getInformation($table);
		if ($information)
		    $srvmg	        = $information['srvmg'];
		else
		{
		    print "<p>Record.inc: " . __LINE__ . " table='$table'</p>\n";
		    $srvmg	= false;
		    debug_print_backtrace();
            $this->msg      .= "Record::save: " . __LINE__ . 
                               " no description of table `$table` not defined";
		}

        // report errors detected by constructor and suppress update
        if (strlen($this->msg) > 0)
        {                       // errors detected by constructor 
            $warn   .= "<p>Record::save: " . __LINE__ . " Request to update the " . 
                       get_class($this). " record ignored because of errors detected when the record was created. '" . $this->msg . 
                "' length=" . strlen($this->msg) . "</p>\n";
            return 0;
        }                       // errors detected by constructor 

		$parms		= array();
		if ($this->needInsert)
		{		// create new record
		    if ($debug)
				$warn	.= "<p>Record::save: needInsert=true</p>\n";
		    $fldnames	= '';
		    $values	= '';
		    $comma	= '';
		    if (is_null($this->row))
		    {
				$msg	.= "Record::save: insert with \$this->row null. ";
				return false;
		    }
		    foreach($this->row as $fld => $value)
		    {
				if ($fld ==  'added')
				{	// date added
				    $fldnames	.= "$comma`$fld`";
				    $values	.= $comma . $today;
				    $comma	= ', ';
				}	// date added
				else
				if ($fld ==  'addedtime')
				{	// time added
				    $fldnames	.= "$comma`$fld`";
				    $values	.= "$comma'$currtime'";
				    $comma	= ', ';
				}	// time added
				else
				if ($fld ==  'updated')
				{	// date updated
				    $fldnames	.= "$comma`$fld`";
				    $values	.= $comma . $today;
				    $comma	= ', ';
				}	// date updated
				else
				if ($fld ==  'updatedtime')
				{	// time updated
				    $fldnames	.= "$comma`$fld`";
				    $values	.= "$comma'$currtime'";
				    $comma	= ', ';
				}	// time updated
				else
				if (($fld == 'id' ||
				    (is_string($prime) && $fld == $prime && $srvmg)) &&
				    $value == 0)
				{	// let SQL server manage field value
				}	// let SQL server manage field value
				else
				{	// other fields
				    $fldnames		.= "$comma`$fld`";
				    $values		.= $comma . ':' . $fld;
				    $parms[$fld]	= $value;
				    $comma		= ', ';
				}	// other fields
		    }		// loop through all fields in record

		    $insert	= "INSERT INTO $table ($fldnames) VALUES($values)";
		    $stmt	= $connection->prepare($insert);
		    $insertText	= debugPrepQuery($insert, $parms);
		    if ($stmt->execute($parms))
		    {		// successful
				$count		= $stmt->rowCount();
				if ($xml)
				{		// XML output
				    print "<$tagname count='$count'>" . $insertText .
						    "</$tagname>\n";
				}		// XML output

				if ($debug)
				    $warn	.= "<p>" . $insertText .
							   "</p>\n";
				$this->needInsert	= false;

				// if the record has an auto increment primary key then the
				// value is set by the server
				$primKey	= $count;
				if ($srvmg)
				{		// get value of prime key assigned by server
				    if (is_string($this->prime) && strlen($this->prime) > 0)
				    {		// get value of prime key
						$primKey	= $connection->lastInsertId();
						if ($xml)
						    print "<$tagname result='$primKey'>" .
							    "lastInsertId" .
							  "</$tagname>\n";
						$this->row[$this->prime]	= $primKey;
				    }		// get value of prime key
				}		// get value of prime key assigned by server

				// log the update
				logSqlUpdate($insert,
						     $parms,
						     $primKey,
						     '',
						     $this->toJson(false));
		    }		// successful
		    else
		    {		// error inserting record
				$msg	.= $insertText . ": " .
						   print_r($stmt->errorInfo(), true);
				return false;
		    }		// error inserting record
		}		// create new record
		else
		{		// update existing record
		    if ($debug)
				$warn	.= "<p>Record::save: needInsert=false</p>\n";
		    $set		= '';
		    $comma		= '';
		    $parms		= array();
		    foreach($this->changed as $fld => $value)
		    {		// loop through all modified fields in record
				if ($fld ==  'added')
				{	// date added cannot change
				}	// date added cannot change
				if ($fld ==  'addedtime')
				{	// time added cannot change
				}	// time added cannot change
				if ($fld ==  'updated')
				{	// date updated
				    $set	.= $comma . "`$fld`=DATE_FORMAT(CURDATE(),'%Y%m%d')";
				    $comma		= ', ';
				}	// date updated
				if ($fld ==  'updatedtime')
				{	// time updated
				    $set		.= $comma . "`$fld`=CURTIME()";
				    $comma		= ', ';
				}	// time updated
				else
				{	// other fields
				    $set		.= $comma . "`$fld`=:$fld";
				    $parms[$fld]	= $value;
				    $comma		= ', ';
				}	// other fields
		    }		// loop through all modified fields in record

		    // if there are any changed fields, update the database
		    if (strlen($set) > 0)
		    {		// at least one field modified
				// if any fields modified then former must be set
				// using former to get the key of the record permits
				// changing the value of the primary key as long as it
				// is not changed to an already defined value
				if (is_array($prime))
				{		// primary key is multiple fields
				    $key		= 0;
				    $update		= "UPDATE $table SET $set";
				    $and		= ' WHERE ';
				    foreach($prime as $fldname)
				    {
						$update		.= $and . $fldname . '=:o' . $fldname;
						$parms['o' . $fldname]	= $this->former[$fldname];
						$and		= ' AND ';
				    }
				}		// primary key is multiple fields
				else
				{		// primary key is single field
                    $key		= $this->former[$prime];
                    if ($key == 0)
                        $key    = $this->row[$prime];
				    $update		= "UPDATE $table SET $set" .
								      " WHERE $prime=:prime";
				    $parms['prime']	= $key;
				}		// primary key is single field

				$stmt		= $connection->prepare($update);
				$updateText	= debugPrepQuery($update, $parms);
				if ($stmt->execute($parms))
				{		// successful update
				    $count	= $stmt->rowCount();	// changed rows
				    if ($xml)
				    {
						print "<$tagname result='$count'>" .
							$updateText .
						      "</$tagname>\n";
				    }

				    if ($debug)
						$warn	.= "<p>" . $updateText . "</p>\n";

				    // log the update
				    logSqlUpdate($update,
							 $parms,
							 $key,
							 json_encode($this->former),
							 $this->toJson(false));
				}		// successful update
				else
				{		// error updating record
				    $msg	.= $updateText . ": error=" .
							   print_r($stmt->errorInfo(),true) . ". ";
				    return false;
				}		// error updating record
		    }		// at least one field modified
		    else
				$count	= 0;	// nothing changed
		}		// update existing record

		// all changes have been applied
		$this->changed	= array();	// reset
		$this->former	= null;		// reset

		// successful, return count of number of records updated
		return $count;
    }		// function Record::save

    /********************************************************************
     *	function Record::changed										*
     *																	*
     *  This function is used to determine whether or not the contents	*
     *  of the record have been changed since the last time it was		*
     *  saved to the database.											*
     *																	*
     *	Returns:														*
     *	    false	if the database is up to date						*
     *	    true	if the record needs to be saved to the database		*
     ********************************************************************/
    function changed()
    {
		return($this->needInsert ||
		       (is_array($this->changed) && count($this->changed) > 0));
    }		// function Record::changed

    /********************************************************************
     *	function Record::delete											*
     *																	*
     *  Delete the database record associated with this instance.		*
     *																	*
     *	Input:															*
     *	    $xml		if true emit XML								*
     *					if a string emit XML using as tag name			*
     *																	*
     *	Returns:														*
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
		$table	= $this->table;		// name of table
		$prime	= $this->prime;		// name of primary key
		if (!$this->needInsert)
		{			// existing record
		    if (is_array($prime))
		    {
				$delete		= "DELETE FROM $table";
				$key		= 0;
				$and		= ' WHERE ';
				$sqlParms	= array();
				foreach($prime as $fldname)
				{		// loop through components of key
				    if ($this->former)
						$value	= $this->former[$fldname];
				    else
						$value	= $this->row[$fldname];
				    $delete	.= $and . $fldname . '=:o' . $fldname;
				    $sqlParms['o' . $fldname]	= $value;
				    $and	= ' AND ';
				}		// loop through components of key
		    }
		    else
		    if (array_key_exists($prime, $this->row) &&
				$this->row[$prime] > 0)
		    {
				if ($this->former)
				    $key	= $this->former[$prime];
				else
				    $key	= $this->row[$prime];

				$delete		= "DELETE FROM $table WHERE $prime=:key";
				$sqlParms	= array('key'	=> $key);
		    }			// single key field
		    else
				return 0;	// nothing to delete

		    $stmt	= $connection->prepare($delete);
		    $deleteText	= debugPrepQuery($delete, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {			// successful delete
				$count	= $stmt->rowCount();	// changed rows
				if ($xml)
				    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
				if ($debug)
				    $warn	.= "<p>Record::delete: " . __LINE__ . 
							   " cmd='$deleteText'" .
							   ", count='$count'</p>\n";

				// log the update
				logSqlUpdate($delete,
						     $sqlParms,
						     $key,
						     $this->toJson(false),
						     '');

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				if (is_string($prime))
				    $this->row[$prime]	= 0;
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// successful query
		    else
		    {		// error updating record
				$msg	.= "$delete: " .
						   htmlentities(print_r($connection->errorInfo(),true)) . ". ";
				return false;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }		// function Record::delete

    /********************************************************************
     *	function Record::postCheckCitation								*
     *																	*
     *  Update the citation associated with the post update.			*
     *	If the first parameter starts with "Page" followed by a			*
     *	number the number is treated as the IDSX of a Citation			*
     *																	*
     *  Parameters:														*
     *	    $key		key												*
     *	    $value		srcdetail value for citation					*
     ********************************************************************/
    private function postCheckCitation($key, $value)
    {		// check for citation update
		if (substr($key, 0, 4) == 'Page')
		{
		    $idsx		= substr($key, 4);
		    if (ctype_digit($idsx))
		    {
				$idsx	= intval($idsx);
				try {
				    $citation	= new Citation($idsx);
				    if ($citation->isExisting())
				    {
						$citation->set('srcdetail', $value);
						if ($xml)
						    $citation->save('cmd');
						else
						    $citation->save(false);
				    }
				    else
						$warn	.= "<p>Record::postUpdate: " .
							__LINE__ .
							" Citation record not found for IDSX=$idsx</p>";
				} catch(Exception $e) {
						$warn	.= "<p>Record::postUpdate: " .  __LINE__ .
							   ' ' . $e->getMessage() . "</p>";
				}
		    }
		}
    }		// private function postCheckCitation

    /********************************************************************
     *	function Record::postUpdate										*
     *																	*
     *  Update the contents of the record based upon the contents		*
     *  of the $_POST variable.  Note that this does not update the		*
     *	the database until you call save.								*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format											*
     ********************************************************************/
    function postUpdate($xml)
    {
		global	$debug;
		global	$warn;

		if ($xml)
		    print "    <parms>\n";
		foreach($_POST as $key => $value)
		{
		    // support field name synonyms defined for the class
		    $fieldLc	= self::getRealFieldName($key);
		    if (is_null($fieldLc))
		    {
				$information	= $this->getInfo();
				$message	= "unrecognized field name '$key' for class " .
							  $information['classname'];
				if ($xml)
				    print "\t<$key>$message</$key>\n";
		    }
		    else
		    if ($xml)
				print "\t<$fieldLc>" . htmlspecialchars($value) . "</$fieldLc>\n";

		    if ($fieldLc != $this->prime)
		    {		// not the identifying key of the record
				if (!is_null($fieldLc))
				{		// matches field in record
				    if (is_string($value))
						$this->set($fieldLc, $value);
				    else
				    if (is_array($value))
						$this->set($fieldLc, $value[count($value) - 1]);
				    else
						$this->msg  .= "Record::postUpdate: " . __LINE__ .
								" parameter '$fieldLc' value is " .
								print_r($value, true);
				}		// matches field in record
				else
				    $this->postCheckCitation($key, $value);
		    }		// not the identifying key of the record
		}
		if ($xml)
		    print "    </parms>\n";
    }		// function Record::postUpdate

    /********************************************************************
     *	function Record::toXml											*
     *																	*
     *  Construct and optionally print an XML representation of the		*
     *	record.															*
     *																	*
     *	Parameters:														*
     *	    $top		tag name as a string for the tag enclosing the	*
     *					values.											*
     *					If null, do not enclose the individual values	*
     *					inside a root node.								*
     *	    $print		if true print the output, else just return it	*
     *	    $options	flags to indicate which information to include	*
     *					(available to derived classes)					*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/
    function toXml($top,
				   $print = true,
				   $options = 0)
    {
		global	$debug;

		// ensure that there is a label to use for the top element
		if (!is_string($top) || strlen($top) == 0)
		    $top	= $this->table;

		$prime		= $this->prime;
		if ($top !== null)
		{
		    if (is_string($prime) && strlen($prime) > 0)
		    {
				$keyvalue	= $this->row[$prime];
				$retval		= "    <$top $prime='$keyvalue'>\n";
		    }
		    else
				$retval		= "    <$top>\n";
		}
		else
		    $retval		= '';

		foreach($this->row as $key => $value)
		{
		    if (substr($key, 1, 1) == '_')
				    $key	= substr($key, 2);
		    else
		    if (substr($key, 2, 1) == '_')
				    $key	= substr($key, 3);
		    $retval	.= "\t<$key>" . xmlentities($value) . "</$key>\n";
		}
		if ($top !== null)
		    $retval	.= "    </$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Record::toXml

    /********************************************************************
     *	function Record::toJson											*
     *																	*
     *  Construct and optionally print a JSON representation of the		*
     *	record.															*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	flags to indicate which information to include	*
     *					(available to derived classes)					*
     *																	*
     *	Returns:														*
     *	    JSON representation of the object as a string				*
     ********************************************************************/
    function toJson($print = true,
				    $options = 0)
    {
		global	$debug;

		$retval		= '';
		$comma		= "{\n";
		foreach($this->row as $key => $value)
		{
		    if (substr($key, 1, 1) == '_')
				$key		= substr($key, 2);
		    else
		    if (substr($key, 2, 1) == '_')
				$key		= substr($key, 3);
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
		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Record::toJson

    /********************************************************************
     *	function Record::setTemplate									*
     *																	*
     *  If an instance of Record is constructed from a database row     *
     *  if is not possible to pass a template as a parameter to the     *
     *  constructor. This method explicitly associates an               *
     *  internationalization template with this instance.               *
     *																	*
     *	Parameters:														*
     *	    $newvalue   instance of class Template                      *
     *																	*
     *	Returns:														*
     *	    Previous setting                                            *
     ********************************************************************/
    public function setTemplate($newvalue)
    {
        $oldvalue                   = $this->template;
        if ($newvalue instanceof Template)
            $this->template         = $newvalue;
        return $oldvalue;
    }           // function Record::seTemplate

    /********************************************************************
     *	function Record::setGetModeHTML									*
     *																	*
     *  Set whether method get returns the internal value of a field    *
     *	or a string representation suitable for embedding in an         *
     *	HTML page.                                                      *
     *																	*
     *	Parameters:														*
     *	    $newvalue   boolean true or false                           *
     *																	*
     *	Returns:														*
     *	    Previous setting as a boolean value.                        *
     ********************************************************************/
    public function setGetModeHTML($newvalue)
    {
        $oldvalue       = $this->getModeHtml;
        if ($newvalue)
            $this->getModeHtml      = true;
        else
            $this->getModeHtml      = false;
        return $oldvalue;
    }           // function Record::setGetModeHTML

    /********************************************************************
     *	function Record::get											*
     *																	*
     *  Get the value of a field by name								*
     *	    $record[$name] returns the same value						*
     *																	*
     *	Parameters:														*
     *	    $name		field name.  This is case insensitive and can	*
     *					either an actual field name or a synonym		*
     *					defined by class::$translate					*
     *																	*
     *	Returns:														*
     *	    Current value of the field.									*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    public function get($field)
    {
		global	$debug;
		global	$warn;

		$fieldLc	    = strtolower($field);
		if (array_key_exists($fieldLc, static::$translate))
		    $fieldLc	= static::$translate[$fieldLc];
		if (array_key_exists($fieldLc, $this->row))
		    $retval     = $this->row[$fieldLc];
		else
		if (array_key_exists($fieldLc, $this->extras))
		    $retval     = $this->extras[$fieldLc];
		else
		{
		    $warn	.= "<p>Record::get: " . __LINE__ . 
                        " Field name '$field' has not been defined for class " .
                        get_class($this) . "</p>\n";
            $olddebug       = $debug;
            $this->dump("Record::get: " . __LINE__);
            $debug          = $olddebug;
		    $trace      	= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		    foreach($trace as $l => $caller)
		    {
				$warn	.= "<p style='padding-bottom: 0px; margin-bottom: 0px; margin-top: 0px;'>$l called {$caller['class']}::{$caller['function']}, at {$caller['file']} line {$caller['line']}</p>\n";
		    }
		    $retval     = null;
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
    }		// function Record::get

    /********************************************************************
     *	function Record::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *    $record[$name] = $value; calls $record->set($name, $value)	*
     *																	*
     *  Parameters:														*
     *	    $field		field name.  This is case insensitive and can	*
     *					be either an actual field name or a synonym		*
     *					defined by class::$translate.  It can also		*
     *					be a temporary field name for the life of		*
     *					this object.									*
     *	    $value		value to set the field to						*
     *																	*
     *	Returns:														*
     *	    Previous value of the field.								*
     *																	*
     *	Throws:															*
     *	    if object is not properly initialized						*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		global	$debug;
		global	$warn;
		global	$msg;

		if (is_null($this->row))
		{
		    $msg	.= "Record::set('$field', '$value') called with " .
						   "\$this->row null. ";
		    return null;
		}

		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, static::$translate))
		    $fieldLc		= static::$translate[$fieldLc];
		if (array_key_exists($fieldLc, $this->row))
		{		// valid field name
		    $oldvalue		= $this->row[$fieldLc];
		    if (is_int($oldvalue) &&
				ctype_digit($value) &&
				substr($value, 0, 1) != '0') // dates have leading 0
				$value		= intval($value);
		    if ($value !== $oldvalue)
		    {		// value has changed
				if (is_null($this->former))
				    $this->former		= $this->row;
				$this->row[$fieldLc]		= $value;
				if (!($this->needInsert))
				{
				    $this->changed[$fieldLc]	= $value;
				}
		    }		// value has changed
		}		// valid field name
		else
		{		// insert into extras
		    if (array_key_exists($fieldLc, $this->extras))
				$oldvalue			= $this->extras[$fieldLc];
		    else
		    {
				$oldvalue			= null;
		    }
		    $this->extras[$fieldLc]		= $value;
		}		// insert into extras
		return $oldvalue;
    }		// function Record::set

    // support former name of method
    function setField($field, $value)
    {
		return $this->set($field, $value);
    }

    /********************************************************************
     *  function Record::addOwner										*
     *																	*
     *  Add the current user as an										*
     *  owner of the record and therefore authorized to view the		*
     *	private information in the record and to update the record.		*
     *																	*
     *	Input:															*
     *	    $username	name of user to whom ownership is to be given	*
     *					the default is the current user					*
     *																	*
     *  Returns:														*
     *	    true		ownership is granted							*
     *	    false		the current user is already an owner			*
     ********************************************************************/
    function addOwner($username = null)
    {
		global	$userid;

		if (is_null($username))
		    $username	= $userid;	// current user

		// if the user to be granted access is an administrator there
		// is no need to explicitly authorize the user
		$user	= new User(array('username' => $username));
		if (strtolower($user->get('auth')) == 'yes')
		    return false;

		$recOwner	= new RecOwner(array('UserName'	=> $username,
								     'Table'	=> $this->table,
								     'KeyValue'	=> $this->getId()));

		if ($recOwner->isExisting())
		    return false;
		else
		{
		    $recOwner->save(false);
		    return true;
		}
    }		// function Record::addOwner

    /********************************************************************
     *  function Record::isOwner										*
     *																	*
     *  Get an indication of whether or not the current user is an		*
     *  owner of the record and therefore authorized to view the		*
     *	private information in the record and to update the record.		*
     *																	*
     *  Returns:														*
     *		true		the current user is an owner of the record		*
     *		false		the current user does not own this record		*
     ********************************************************************/
    function isOwner()
    {
		$id		= $this->row[$this->prime];
		if ($id == 0)
		    return true;
		else
		    return RecOwner::chkOwner($id,
							      $this->table);
    }		// function Record::isOwner

    /********************************************************************
     *  function Record::getSurnameChk									*
     *																	*
     *	Deprecated, replaced by RecordSet::getSurnameChk.				*
     *  Get a MySQL expression that performs a loose comparison for a	*
     *  a supplied surname.												*
     *	This can only be called for an object which contains fields		*
     *	named `Surname` and `SoundsLike`, that is for tblIR, tblNR,		*
     *	and tblNX.														*
     *																	*
     *  Input:															*
     *	    $table		table name										*
     *	    $surname	the surname to check for						*
     *																	*
     *  Returns:														*
     *		 a string that can be substituted into a MySQL WHERE clause	*
     ********************************************************************/
    static function getSurnameChk($table,
							      $surname)
    {
		global	$connection;

		$information	= self::getInformation($table);
		if ($information)
		{			// valid table name
		    $initRow	= $information['initrow'];
		    $valid	= array_key_exists('surname',		$initRow) &&
						  array_key_exists('soundslike',	$initRow);
		    $table	= $information['table'];	// actual table name
		}			// valid table name
		else
		    $valid	= false;// invalid table name

		if ($valid)
		{			// valid table
		    $surnameRec	= new Surname(array('surname' => $surname));
		    $pattern	= $surnameRec->get('pattern');
		    if ($pattern != '')
		    {		// use pattern match rather than soundex
				return $table . ".Surname REGEXP '$pattern'";
		    }		// use pattern match rather than soundex
		    else
		    {		// use soundex match
				// remove question marks
				$surname	= str_replace('?', '', $surname);
				$surnameCmp	= $table . ".SoundsLike=LEFT(SOUNDEX(" .
							    $connection->quote($surname) . "),4)";
				if (substr($surname, strlen($surname) - 1, 1) == 's')
				{		// also try soundex without final s
				    $tsurname	= substr($surname, 0, strlen($surname) - 1);
				    $tsurname	= $connection->quote($tsurname);
				}		// also try soundex without final s
				else
				{		// also try soundex with final s
				    $tsurname	= $surname . 's';
				    $tsurname	= $connection->quote($tsurname);
				}		// also try soundex with final s
				$surnameCmp	.= " OR " . $table .
							".SoundsLike=LEFT(SOUNDEX($tsurname),4)";

				if (strlen($surname) > 4)
				{		// add other loose surname comparisons
				    $last1		= substr($surname, strlen($surname) - 1, 1);
				    $first1		= substr($surname, 0, 1);
				    $first2		= substr($surname, 0, 2);
				    $last3		= substr($surname, strlen($surname) - 3, 3);
				    // match a surname that starts with the same 2 characters and
				    // ends with the same character or starts with the same
				    // character and ends with the same 3 characters
				    $surnameCmp	.= " OR (LEFT($table.Surname,2)=" .
							       $connection->quote($first2) .
							       " AND RIGHT($table.Surname,1)=" .
							       $connection->quote($last1) .
							       ") OR (LEFT($table.Surname,1)=" .
							       $connection->quote($first1) .
							       " AND RIGHT($table.Surname,3)=" .
							       $connection->quote($last3) . ")";
				}		// add other loose surname comparisons
				return "($surnameCmp)";
		    }		// use soundex match
		}			// valid table name
		else
		    $warn	.= "<p>record::getSurnameChk: " .
							"Unsupported table '$table'</p>\n";
    }		// getSurnameChk

    /********************************************************************
     *	static function Record::getInformation							*
     *																	*
     *	Staic function to get information about a supported table.		*
     *																	*
     *	Parameters:														*
     *	    The name of the table as a string.  This may be a synonym	*
     *	    as defined in Record::$externalTableNames.					*
     *																	*
     *	Returns:														*
     *	    An associative array of named attributes:					*
     *	'table'			SQL table name									*
     *	'name'			external name of the table shown to users		*
     *  'prime'			The primary key name for the table.				*
     *					The key names are in lower case so they will	*
     *					match the field name in the query response.		*
     *	'srvmg'			true if the setting of the primary key is		*
     *					managed by the database server for new records,	*
     *					and false if it is managed by the application.	*
     *	'fldcount'		not currently used								*
     *	'order'			default order for operations on this table		*
     *	'classname'		name of the class that implements the interface	*
     *					to this table									*
     *	'initrow'		default initial row.  This provides information	*
     *					on the fields in the table						*
     *	'maxsetsize'    the maximum size of a RecordSet for this table  *       
     ********************************************************************/
    public static function getInformation($tableName)
    {
		global	$connection;

		if (array_key_exists($tableName, self::$externalTableNames))
		{
		    $table		            = self::$externalTableNames[$tableName];
		    $externalName	        = $tableName;
		}
		else
		{
		    $table		            = $tableName;
		    if (array_key_exists($table, self::$internalTableNames))
				$externalName	    = self::$internalTableNames[$table];
		    else
				$externalName	    = $table;
		}

		// get information about the selected table
		if (array_key_exists($table, self::$primeKey))
		{
		    $information		    = self::$primeKey[$table];
		    $information['table']	= $table;
		    $information['name']	= $externalName;
		    $information['order']	= $information['prime'];
		    $className			    = $information['classname'];
		    if ($className == '')
		    {
				$className			= 'Record';
				$information['classname']	= 'Record';
		    }
            $nsclass	            = __NAMESPACE__ . "\\" . $className;
            if (!class_exists($nsclass))
            {
                include __NAMESPACE__ . "/" . $className . ".inc";
            }
		    $order			        = $nsclass::$defaultOrder;
		    if (strlen($order) > 0)
                $information['order']	= $order;

            // get contents of default initial row
		    if ($className == 'CensusLine')
				$initrow		    = CensusLine::getInitRow($table);
		    else
				$initrow		    = $nsclass::$initRow;
		    if (count($initrow) == 0)
		    {			// no class specific init row
				// get the very first record in the table
				$query		        = 'SELECT * FROM ' . $table . ' LIMIT 1';
				$stmt		        = $connection->query($query);
				if ($stmt)
				    $row	        = $stmt->fetch(PDO::FETCH_ASSOC);
				if (is_array($row))
				    $initrow	    = $row;
		    }			// no class specific init row
            $information['initrow']	= $initrow;

            $information['maxsetsize']  = 1000;
		    return $information;
		}
		else
		    return null;	// unsupported table
    }		// public static function getInformation

    /********************************************************************
     *	function Record::getInfo										*
     *																	*
     *	Get information about the table associated with the current		*
     *	record.															*
     *	Note that this cannot be called getInformation because the PHP	*
     *	language does not have a mechanism for resolving the conflict	*
     *	because classname::function may either be a reference to a		*
     *	static member or a scope resolution indicator that a parent		*
     *	class's version of a method is to be called.					*
     *																	*
     *	Returns:														*
     *	    An associative array of named attributes:					*
     *	'table'			SQL table name									*
     *	'name'			external name of the table shown to users		*
     *  'prime'			The primary key name for the table.				*
     *					The key names are in lower case so they will	*
     *					match the field name in the query response.		*
     *	'srvmg'			true if the setting of the primary key is		*
     *					managed by the database server for new records,	*
     *					and false if it is managed by the application.	*
     *	'fldcount'		not currently used								*
     *	'order'			default order for operations on this table		*
     *	'classname'		name of the class that implements the interface	*
     *					to this table									*
     *	'initrow'		default initial row.  This provides information	*
     *					on the fields in the table						*
     ********************************************************************/
    public function getInfo()
    {
		return self::getInformation($this->table);
    }       //function Record::getInfo

    /********************************************************************
     *	static function Record::getRealFieldName						*
     *																	*
     *	Perform standard manipulation for this table to obtain the		*
     *	the name of a field from a suggested parameter name.			*
     *	This method uses late static binding so that it will use the	*
     *	internal definition tables for whichever class it is invoked	*
     *	for.  This eliminates the need for access to internal table		*
     *	definition members.												*
     *																	*
     *	Returns:														*
     *	    A field name in lower case or null if name is invalid.		*
     ********************************************************************/
    public static function getRealFieldName($fldname)
    {
		global $debug;
		global $warn;

		$fieldLc    	= str_replace('`','',strtolower($fldname));

		$translated	    = false;
		if (array_key_exists($fieldLc, static::$translate))
		{
		    $fieldLc	= static::$translate[$fieldLc];
		    $translated	= true;
		}
        if ($fieldLc == 'offset' ||
            $fieldLc == 'limit' || 
            $fieldLc == 'order' ||
            $fieldLc == 'template')
		    return $fieldLc;
		else
		if ($translated ||
		    count(static::$initRow) == 0 ||
		    array_key_exists($fieldLc, static::$initRow))
		    return $fieldLc;
		else
		    return null;	// field not defined
    }		// static function getRealFieldName

    /********************************************************************
     *  static function Record::getWhereInt								*
     *																	*
     *	Deprecated, replaced by RecordSet::getWhereInt.					*
     *  Construct the SQL expression to compare a field whose value		*
     *	is a positive integer for a particular parameter.  This is		*
     *	common functionality used by the method getWhere in derived		*
     *	classes.														*
     *																	*
     *	Input:															*
     *	    $fldname	the SQL field name.  Note that this must be		*
     *					a valid field name, not a synonym.				*
     *	    $value		the value, which may be an integer, or			*
     *					a string starting with an operator, or			*
     *					an instance of Record, or						*
     *					an array each member of	which may be an integer	*
     *					or a string starting with an operator			*
     *	    $sqlParms	array used by PDO execute to contain values		*
     *					to substitute into a prepared statement			*
     *																	*
     *	Returns:														*
     *	    String containing an SQL expression							*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied value are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     *	    The array $sqlParms is updated to include new substitution	*
     *	    values corresponding to insertion points in the returned	*
     *	    expression string.											*
     ********************************************************************/
    protected static function getWhereInt($fldname,
						        		  $value,
								          &$sqlParms)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$matches		= array();
		$where			= '';
		if (is_array($value))
		{		// list of values
		    $values		= $value;
		    if (count($values) == 0)
				return '';
		    if (count($values) > 1)
				$where		= '(';
		    $oldwhere		= $where;
		    $or			= '';
		    for($i = 0; $i < count($values); $i++)
		    {
				$value			= trim($values[$i]);
				if (is_int($value))
				{			// integer
				    $where		.= $or . "$fldname=?";
				    $sqlParms[]		= $value;
				    $or			= ' OR ';
				}			// integer
				else
				if (is_string($value))
				{			// elementary value
				    $matches		= array();
				    if (preg_match('/^([<>=!:]*)(-?\d+)$/',
							   $value,
							   $matches))
				    {			// optional operator and integer
						$operator	= $matches[1];
						$value		= $matches[2];

						if ($operator == ':')
						{		// range operator
						    if ($i == 1 && count($values) == 2)
						    {		// only permitted on second element
							// change original comparison for first element
							// and add comparison for second element
							$where		= $oldwhere .
									      "$fldname>=? AND " .
									      "$fldname<=?";
							$sqlParms[]	= $value;
						    }		// only permitted on second element
						    else
						    {		// ignore with warning
							$warn	.= "<p>Invalid $fldname"."[$i] value " .
									"'$value', count(\$values)=" . 
									count($values) . "</p>\n";
						    }		// ignore with warning
						}		// range operator
						else
						{		// comparison operator
						    if ($operator == '')
							$operator	= '=';

						    $where		.= $or . "$fldname$operator?";
						    $sqlParms[]	= $value;
						    if ($operator == '=')
						    {
							$or		= ' OR ';
						    }
						    else
							$or		= ' AND ';
						}		// comparison operator
				    }			// optional operator and integer
				    else
				    {			// invalid value
						$warn		.= "<p>Invalid $fldname"."[$i] value " .
									"'$value'</p>\n";
				    }			// invalid value
				}			// elementary value
				else
				{			// invalid non-elementary value
				    $warn		.= "<p>Invalid $fldname"."[$i] value ". 
								     print_r($value, true) . "</p>\n";
				}			// invalid non-elementary value
		    }				// loop through values
		    if (count($values) > 1)
				$where		.= ')';
		    if ($where == '()')
				$where		= '';	// do not return empty brackets
		}			// list of values
		else
		if ($value instanceof Record)
		{			// record
		    $value		= $value->getId();
		    $where		.= "$fldname=?";
		    $sqlParms[]		= $value;
		}			// record
		else
		if (preg_match('/^([<>=!:]*)(-?\d+)$/',
				       $value,
				       $matches))
		{			// optional operator and integer
		    $operator		= $matches[1];
		    $value		= $matches[2];
		    if ($operator == '')
				$where		.= "$fldname=?";
		    else
				$where		.= "$fldname$operator?";
		    $sqlParms[]		= $value;
		}			// optional operator and integer
		else
		    $warn	.= "<p>Invalid $fldname value " .
							       print_r($value,true) . "</p>\n";
		return $where;
    }		// protected static function getWhereInt,

    /********************************************************************
     *  static function Record::getWherePattern							*
     *																	*
     *	Deprecated, replaced by RecordSet::getWherePattern.				*
     *  Construct the SQL expression to compare a field whose value		*
     *	is a string for a regular expression pattern match.  This is	*
     *	common functionality used by the method getWhere in derived		*
     *	classes.														*
     *	For efficiency and increased portability (since not all SQL		*
     *	servers support regular expressions) the most common regular	*
     *	expression syntaces are simulated using standard functions.		*
     *																	*
     *	Input:															*
     *	    $fldname	the SQL field name.  Note that this must be		*
     *					a valid field name, not a synonym.				*
     *	    $pattern	the value, which is a string containing a		*
     *					regular expression								*
     *	    $sqlParms	array used by PDO execute to contain values		*
     *					to substitute into a prepared statement			*
     *																	*
     *	Returns:														*
     *	    String containing an SQL expression							*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied value are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     *	    The array $sqlParms is updated to include new substitution	*
     *	    values corresponding to insertion points in the returned	*
     *	    expression string.											*
     ********************************************************************/
    protected static function getWherePattern($fldname,
								      $pattern,
								      &$sqlParms)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$matches		= array();
		$where			= '';
		if (substr($pattern, 0 ,1) == '^')
		{		// match at beginning
		    if (substr($pattern, -1) == '$')
		    {		// match whole value
				$vlen		= strlen($pattern) - 2;
				$where		.="$fldname=?";
				$sqlParms[]	= substr($pattern, 1, $vlen);
		    }		// match whole value
		    else
		    {		// match at beginning of string
				$vlen		= strlen($pattern) - 1;
				$where		.="LEFT($fldname, $vlen)=?";
				$sqlParms[]	= substr($pattern, 1);
		    }		// match at beginning of string
		}		// match at beginning
		else
		if (substr($pattern, -1) == '$')
		{		// match at end of string
		    $vlen	 	= strlen($pattern) - 1;
		    $where		.="RIGHT($fldname, $vlen)=?";
		    $sqlParms[]		= substr($pattern, 0, $vlen);
		}		// match at beginning of string
		else
		if (preg_match("/[<>.*+?^$()[\]]/", $pattern) == 1)
		{		// value is a REGEXP pattern
		    $where		.= "$fldname REGEXP ?";
		    $sqlParms[]		= $pattern;
		}		// value is a REGEXP pattern
		else
		{		// value is a string to find
		    $where		.="LOCATE(?, $fldname) > 0";
		    $sqlParms[]		= $pattern;
		}		// value is a string to find
		return $where;
    }		// protected static function getWherePattern

    /********************************************************************
     *  function Record::log											*
     *																	*
     *  Log an SQL command that updates the Legacy Family Tree database	*
     *  so that the change can also be re-applied or backed out.		*
     *																	*
     *  Input:															*
     *	    $command	SQL update command								*
     *	    $parms		parameter array if command issued through		*
     *					prepared statement								*
     ********************************************************************/
    protected function log($command,
						   $parms)
    {
		global $debug;
		global $warn;
		global $connection;
		global $userid;

		// clean up the command by replacing all sequences of spaces
		// with a single space
		$command		= preg_replace("/\s\s*/", " ", $command);

		if (substr($command, strlen($command) - 1) == ';')
		    $command	= substr($command, 0, strlen($command) - 1);

		$id		        = $this->getId();
		
		if ($this->former)
		    $oldJson	= json_encode($this->former);
		else
		    $oldJson	= '';

		if (strtoupper(substr($command, 0, 6)) == 'DELETE')
		    $newJson	= '';
		else
		    $newJson	= json_encode($this->row);

		// prepare the insert
		$logCmd	= 'INSERT INTO SqlLog ' .
						    '(SL_DateTime, SL_Command, SL_Parms, SL_ID, SL_UserName, SL_OldJson, SL_NewJson) ' .
						    'VALUES (NOW(), ?, ?, ?, ?, ?, ?)';
		$parms	= array($command,
						json_encode($parms),
						$id,
						$userid,
						$oldJson,
						$newJson);
		$stmt	= $connection->prepare($logCmd);

		// perform the update
		$stmt->execute($parms);

    }		// function Record::log

    /********************************************************************
     *  static function Record::slog									*
     *																	*
     *  Log an SQL command that updates the Legacy Family Tree database	*
     *  so that the change can also be re-applied or backed out.		*
     *																	*
     *	To Do:	Once all calls to the global function logSqlUpdate		*
     *			are replaced by calls to Record::log then this function	*
     *			can be marked "protected".								*
     *																	*
     *  Input:															*
     *	    $command	SQL update command								*
     *	    $parms		parameter array if command issued through		*
     *					prepared statement								*
     *	    $id			the unique numeric identifier of the record		*
     *					usually from $connection->lastInsertId()		*
     *					if relevant										*
     *	    $oldJson	JSON representation of record before update		*
     *	    $newJson	JSON representation of record after update		*
     ********************************************************************/
    static function slog($command,
						 $parms,
						 $id,
						 $oldJson	= '',
						 $newJson	= '')
    {
		global $debug;
		global $warn;
		global $connection;
		global $userid;

		// clean up the command by replacing all sequences of spaces
		// with a single space
		$command		= preg_replace("/\s\s*/", " ", $command);

		if (substr($command, strlen($command) - 1) == ';')
		    $command	= substr($command, 0, strlen($command) - 1);
		if (substr($command,0,6) == 'INSERT' &&
		    strpos($command, 'SELECT') === false &&
		    substr($command, strlen($command) - 1) != ')')
		{
		    print "<p>command='$command'</p>\n";
		    throw new Exception("logSqlUpdate: invalid parameter '$command'");
		}
		// prepare the insert
		$logCmd	= 'INSERT INTO SqlLog ' .
						    '(SL_DateTime, SL_Command, SL_Parms, SL_ID, SL_UserName, SL_OldJson, SL_NewJson) ' .
						    'VALUES (NOW(), ?, ?, ?, ?, ?, ?)';
		$parms	= array($command,
						json_encode($parms),
						$id,
						$userid,
						$oldJson,
						$newJson);
		$stmt	= $connection->prepare($logCmd);

		// perform the update
		$stmt->execute($parms);

    }		// static function Record::slog

    /********************************************************************
     *	function Record::rewind											*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Reposition to the first element of the "array".					*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    private $iterating      = false;
    public function rewind()
    {
        reset($this->row);
        $this->iterating        = 1;    // iterating through row
    }		// function Record::rewind

    /********************************************************************
     *	function Record::current										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the value of the current element of the "array"			*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function current()
    {
        if ($this->iterating == 1)
        {                       // iterating through row
            $row 		        = current($this->row);
        }                       // iterating through row
        else
        if ($this->iterating == 2)
        {                       // iterating through extras
		    $row 	        	= current($this->extras);
        }                       // iterating through extras
        else
        {                       // iteration not initialized
            reset($this->row);
            $this->iterating    = 1;    // iterating through row
		    $row 	        	= current($this->row);
        }                       // iteration not initialized
		return $row;
    }		// function Record::current

    /********************************************************************
     *	function Record::key											*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the key of the current element of the "array".			*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function key()
    {
        if ($this->iterating == 2)
		    $row 		= key($this->extras);
        else
		    $row 		= key($this->row);
		return $row;
    }		// function Record::key

    /********************************************************************
     *	function Record::next											*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Advance to the next element of the "array".						*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function next()
    {
        if ($this->iterating == 1)
        {                       // iterating through row
            $row 		= next($this->row);
            if ($row === false)
            {
                $this->iterating        = 2;    // iterating through extras
                $row 		            = reset($this->extras);
            }
        }                       // iterating through row
        else
        if ($this->iterating == 2)
        {                       // iterating through extras
		    $row 		= next($this->extras);
        }                       // iterating through extras
        else
        {                       // iteration not initialized
            reset($this->row);
            $this->iterating            = 1;    // iterating through row
		    $row 		                = current($this->row);
        }                       // iteration not initialized
		return $row;
    }		// function Record::next

    /********************************************************************
     *	function Record::valid											*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Returns true if the iterator is valid.							*
     *																	*
     *  Returns:														*
     *	    String														*
     ********************************************************************/
    public function valid()
    {
        if ($this->iterating == 2)
        {                       // iterating through extras
		    $key 		= key($this->extras);
		    return ($key !== NULL && $key !== FALSE);
        }                       // iterating through extras
        else
        {                       // iterating through row
            $key 		= key($this->row);
            if ($key === null)
            {
                if ($this->iterating == 1)
                {                       // iterating through row
                    $this->iterating        = 2;    // iterating through extras
                    $row 		            = reset($this->extras);
                    $key                    = key($this->extras);
                }
            }
		    return ($key !== NULL && $key !== FALSE);
        }                       // iterating through row
    }		// function Record::valid

    /********************************************************************
     *	function Record::offsetSet										*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Updates the set to include a new element.						*
     *	    $record[$field]		= $value;								*
     *	If $field is a valid field name or synonym for a field name		*
     *	then this does $record->set($field, $value).					*
     *	Otherwise it can be used to add an extra pseudo field			*
     *																	*
     *	Parameters:														*
     *	    $field		field name.  This is case insensitive and can	*
     *					be either an actual field name or a synonym		*
     *					defined by class::$translate.  It can also		*
     *					be a temporary field name for the life of		*
     *					this object.									*
     *	    $value		value to set the field to						*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not a string or null.				*
     ********************************************************************/
    public function offsetSet($field, $value)
    {
		global	$debug;
		global	$warn;

		if (is_null($field))
		{		// $recordSet[]		= $value;
		    $this->extras[] = $value;	// add to end
		}		// $recordSet[]		= $value;
		else
		if (is_string($field)) 
		{		// alternative access to fields
		    $this->set($field, $value);
		}		// alternative access to fields
		else
		    $warn	.= "<p>Record::offsetSet: " . __LINE__ .
						   " Unexpected field name " . print_r($field, true) .
						   " ignored</p>\n";
    }		// public function offsetSet

    /********************************************************************
     *	function Record::offsetExists									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Returns true if the field name is in use in either the record	*
     *	or the array of temporary fields.								*
     *	    isset($record[$field]);										*
     *																	*
     *	Parameters:														*
     *	    $field		field name.  This is case insensitive and can	*
     *					be either an actual field name or a synonym		*
     *					defined by class::$translate.  It can also		*
     *					be a temporary field name for the life of		*
     *					this object.									*
     ********************************************************************/
    public function offsetExists($field)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($field, static::$translate))
		    $fieldLc	= static::$translate[$fieldLc];
		return (array_key_exists($fieldLc, $this->row) ||
				array_key_exists($fieldLc, $this->extras));
    }		// public function offsetExists($field)

    /********************************************************************
     *	function Record::offsetUnset									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Deletes a temporary field out of the extras.  Ignored if the	*
     *	offset refers to a field in the record.							*
     *	    unset($record[$field]);										*
     *																	*
     *	Parameters:														*
     *	    $field		field name.  This is case insensitive and must	*
     *					be a temporary field name as you cannot remove	*
     *					a field from the database record.				*
     ********************************************************************/
    public function offsetUnset($field)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, $this->extras))
		    unset($this->extras[$fieldLc]);
    }		// public function offsetUnset($field)

    /********************************************************************
     *	function Record::offsetGet										*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Returns the instance associated with a field name.				*
     *	    $value		= $record[$field];								*
     *	This is the same as calling $this->get($field).					*
     *																	*
     *	Parameters:														*
     *	    $field		field name.  This is case insensitive and can	*
     *					be either an actual field name or a synonym		*
     *					defined by class::$translate.  It can also		*
     *					be a temporary field name for the life of		*
     *					this object.									*
     ********************************************************************/
    public function offsetGet($field)
    {
		return $this->get($field);
    }		// public function offsetGet($field)

}		// class Record

// class RecOwner is derived from class Record
// defining it here ensures that it is available when the methods
// Record::addOwner and Record::isOwner are called

require_once __NAMESPACE__ . '/RecOwner.inc';

/************************************************************************
 *  function::debugPrepQuery											*
 *																		*
 *  For debugging expand a prepared SQL command by inserting the 		*
 *  values of the parameters into the insertion points.					*
 *  A useful side-effect is that this routine provides additional		*
 *  diagnostic information if there is a mismatch between the prepared	*
 *  statement and the parameters than is provided by MySQL.				*
 *																		*
 *  Returns:															*
 *		String containing an SQL statement in a format suitable			*
 *		to be displayed as part of an XML or HTML document as text		*
 ************************************************************************/
function debugPrepQuery($query, $sqlParms)
{
    global $warn;
    // check for substitutions by indexed values
    $queryParts		= explode('?', $query);
    if (count($queryParts) > 1)
    {			// substitutions by indexed values
		$queryText		= $queryParts[0];
		for($i = 1; $i < count($queryParts); $i++)
		{
		    $text		= $queryParts[$i];
            $val		= $sqlParms[$i-1];
            if (is_array($val))
                throw new Exception("debugPrepQuery: sqlParms[" . ($i-1) . "] is " .
                                        print_r($val, true));
		    if (is_null($val) || $val == 'null')
				$queryText	.= 'NULL' . $text;
		    else
		    if (is_int($val) || preg_match('/^[0-9.]+$/', $val) == 1)
				$queryText	.= $val . $text;
		    else
		    if (is_string($val))
				$queryText	.= "'" . str_replace("'", "\\'", $val) .
								"'" . $text;
		    else
				$queryText	.= print_r($val, true) . $text;
		}
		return str_replace('>','&gt;',
				str_replace('<','&lt;',
				 str_replace('&','&amp;',$queryText)));
    }			// substitutions by indexed values

    // check for substitutions by named values
    $queryParts		= explode(':', $query);
    if (count($queryParts) > 1)
    {			// substitutions by named values
		$queryText		= $queryParts[0];
		for($i = 1; $i < count($queryParts); $i++)
		{
		    $matches		= array();
		    $text	    	= $queryParts[$i];
		    if (preg_match('/^(\w+)(.*)$/s', $text, $matches) == 0)
		    {
				ob_start();
				debug_print_backtrace();
				$warn	.= "<p>Record.inc: " . __LINE__ . ' ' .
						   ob_get_clean() . "</p>\n";
				throw new Exception('debugPrepQuery parse failed for \'' .
							$text . "' in '$query'");
		    }
		    $index		= $matches[1];
		    $text		= $matches[2];
		    if (array_key_exists($index, $sqlParms))
				$val		= $sqlParms[$index];
		    else
		    {			// mismatch between parms and prepared statement
				$warn	.= "<p>Record::debugPrepQuery: " .
								"Undefined index '$index'</p>\n";
				$warn	.= "<p>sqlParms=" . print_r($sqlParms, true) . "</p>\n";
				$val		= ':' . $index;
		    }			// mismatch between parms and prepared statement

		    if (is_null($val) || $val === 'null')
				$queryText	.= 'NULL' . $text;
		    else
		    if (is_int($val) || preg_match('/^[0-9.]+$/', $val) == 1)
				$queryText	.= $val . $text;
		    else
		    if (is_array($val))
		    {
				$queryText	.= print_r($val,true) . $text;
				ob_start();
				debug_print_backtrace();
				$warn	.= "<p>" . ob_get_clean() . "</p>\n";
				// query will fail
		    }
		    else
				$queryText	.= "'" . str_replace("'", "\\'", $val) . 
								"'" . $text;
		}
		return str_replace('>','&gt;',
				str_replace('<','&lt;',
				 str_replace('&','&amp;',$queryText)));
    }			// substitutions by named values
    return str_replace('>','&gt;',
				str_replace('<','&lt;',
				 str_replace('&','&amp;',$query)));
}		// function debugPrepQuery

/************************************************************************
 *  logSqlUpdate														*
 *																		*
 *  Log an SQL command that updates the Legacy Family Tree database so	*
 *  that the change can also be re-applied or backed out.				*
 *  This is declared here because all classes derived from Record		*
 *  call it.															*
 *  This function is deprecated, being replaced by the					*
 *  method Record::log													*
 *																		*
 *  Input:																*
 *		$command		SQL update command								*
 *		$parms				parameter array if command issued through	*
 *						prepared statement								*
 *		$id				the unique numeric identifier of the record		*
 *		$oldJson		JSON representation of the record before update	*
 *		$newJson		JSON representation of the record after update	*
 *																		*
 ************************************************************************/
function logSqlUpdate($command,
				      $parms,
				      $id,
				      $oldJson	= '',
				      $newJson	= '')
{
    Record::slog($command,
				 $parms,
				 $id,
				 $oldJson,
				 $newJson);
}		// function logSqlUpdate
