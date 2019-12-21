<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Event.inc															*
 *																		*
 *  Definition of a class representing an event in a genealogical		*
 *  database.  This class												*
 *  provides access to the information in a row of the table tblER.		*
 *  However it is also used to represent events that are recorded in	*
 *  other tables.														*
 *																		*
 *		Table: tblER - Events, Individual and Marriage					*
 *																		*
 *		Columns															*
 *		Name			Type											*
 *		IDER			INT(10) UNSIGNED								*
 *		IDIR			INT(10) UNSIGNED								*
 *				--> tblIR.IDIR, ID to Person							*
 *				--> tblMR.IDMR, ID to Family							*
 *				--> tblCR.IDCR, ID to Child								*
 *		IDType			TINYINT(3)										*
 *				const IDTYPE_INDIV		=  0; Person					*
 *				const IDTYPE_MAR		= 20; Family					*
 *				const IDTYPE_CHILD		= 21; Child						*
 *		IDET			INT(10) UNSIGNED								*
 *				--> tblET.IDET, ID to Event Type						*
 *	            Event::ET_XXXXX											*
 *		Order			SMALLINT(5)										*
 *				used as sort order.										*
 *		EventSD			INT(10) UNSIGNED								*
 *				(see Sort Date)											*
 *		EventD			VARCHAR(100)									*
 *				(see Date)												*
 *		IDLREvent		INT(10) UNSIGNED								*
 *				--> tblLR.IDLR, ID to Location Record					*
 *		Desc			LONGTEXT						-				*
 *				Notes about the event.  See Description.				*
 *		GEDTag			VARCHAR(30)										*
 *				not currently used.                                     *
 *		EventExclude	TINYINT(3)										*
 *				1=Private Event, 0=not									*
 *		IDAR			INT(10) UNSIGNED								*
 *				--> tblAR.IDAR, ID to Event Address Record				*
 *		Description		VARCHAR(255)									*
 *				Information about the event not related to its location	*
 *				For example the occupation in an occupation event.		*
 *		SentenceOverride VARCHAR(255)									*
 *				Not used in this implementation as text presentation	*
 *				is done in template 									*
 *		qsTag			TINYINT(3)										*
 *				temporary use, 0=no, 1=yes								*
 *		RGExclude		TINYINT(3)										*
 *				0=no, 1=yes												*
 *				Not used.												*
 *		Kind			TINYINT(3)										*
 *				0=live		IDLREvent is IDLR value of Location			*
 *				1=temple;   IDLREvent is IDTR value of Temple			*
 *		LDSTempleReady	TINYINT(3)										*
 *				0=no, 1=yes												*
 *		Preferred		TINYINT(3)										*
 *				0=no, 1=yes												*
 *																		*
 *  History:															*
 *		2010/08/09		remove support for searching for events by text	*
 *						value that was erroneously included when the	*
 *						file was created from the Location.inc			*
 *						file.											*
 *		2010/08/21		Split event text into a type and a prefix		*
 *						required to form a valid verb phrase for		*
 *						display to users.								*
 *		2010/10/11		check error on result, not connection			*
 *		2010/10/16		Change name of getDesc and setDesc to getNotes	*
 *						and setNotes.  Ensure response to getNotes and	*
 *						getDescription is always a string.				*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global							*
 *		2010/11/15		Add support for events recorded in tblIR		*
 *		2010/12/12		Replace obsolete LegacyDate::dateToString		*
 *						with toString									*
 *		2011/01/02		Add method getCitType to return associated		*
 *						citation type									*
 *						Modify getType so it returns meaningful strings	*
 *						for LDS events recorded in LegacyIndiv			*
 *						Modify getLocation so it returns instance of 	*
 *						Temple if kind=Temple is specified				*
 *		2011/01/22		throw exceptions on errors						*
 *		2011/01/28		Event::add did not set fetchmode				*
 *						associative										*
 *		2011/02/01		override toXml to expand date & location		*
 *		2011/02/26		default order to current number of events in	*
 *						Event::add so new events added at end with		*
 *						unique value of order							*
 *		2011/08/21		add methods getLocationKind and setLocationKind	*
 *		2011/12/21		alter interpretation of some events				*
 *						add parameter to getType method to suppress		*
 *						prefix											*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/08/06		add ability to construct new empty instance		*
 *		2012/10/06		use LegacyRecord::set to manipulate record		*
 *						fields so save method will correctly construct	*
 *						appropriate INSERT or UPDATE to synchronize the	*
 *						database record.								*
 *						All set methods return former value				*
 *		2012/10/22		remove support for tblET in preparation for I18N*
 *		2012/11/22		remove static function Event::add				*
 *						replaced by addEvent methods in LegacyIndiv and	*
 *						LegacyFamily									*
 *		2012/11/24		permit setting initial values of idtype and idir*
 *						on new Event									*
 *		2012/12/08		erroneous reference to $update in deletion		*
 *		2013/01/11		missing parameter to method setDate				*
 *		2013/05/24		for etype 19 duplicated verb					*
 *		2013/08/09		base class renamed to Record					*
 *		2014/01/31		add LDS Temple Ready indicator, and				*
 *						LDS location kind fields to pseudo event records*
 *						in method toXML interpret IDET					*
 *		2014/02/03		in toXML display boolean flags as Y/N			*
 *						in toXML display IdType as Individual/Family	*
 *		2014/03/14		add static methods getEvents and deleteEvents	*
 *						to encapsulate this functionality				*
 *		2014/03/19		add XML output from static deleteEvents			*
 *		2014/04/30		add method getName								*
 *		2014/05/02		support associative array as more flexible		*
 *						parameter to constructor						*
 *						explicitly support events that are actually		*
 *						recorded in other records						*
 *		2014/05/08		catch exception creating Location in			*
 *						method toXml									*
 *		2014/05/16		always have fields 'kind' and 'ldstempleready'	*
 *						in internal record								*
 *		2014/06/14		variable $idtype not initialized				*
 *		2014/07/06		add record structure description				*
 *						validate all parameters to constructor before	*
 *						throwing exception for problems					*
 *						move textual interpretation of IDET to			*
 *						presentation scripts to support I18N			*
 *		2014/08/31		save changes made to associated record			*
 *						exception in setLocation if supplied string		*
 *						is a new location								*
 *						dates in associatedRecord set incorrectly		*
 *		2014/09/27		class RecOwners renamed to RecOwner				*
 *		2014/10/01		add method addOwner								*
 *		2014/10/12		privatize dates in all individual events		*
 *		2014/10/15		migrate events which used to be recorded in		*
 *						tblIR and tblMR to instances of tblER			*
 *						by permitting the information to be recorded	*
 *						either in its former or its new location		*
 *		2014/10/26		avoid crash if associated record not saved		*
 *						allow construction from a partially built		*
 *						main record										*
 *		2014/11/02		throw exception for multiple preferred events	*
 *		2014/11/03		change initialization of preferred events from	*
 *						individual so instance of LegacyIndiv does not	*
 *						need to contain obsolete fields					*
 *		2014/11/04		add option to setDate to only update this object*
 *		2014/11/07		ensure death and burial sort after all events	*
 *		2014/11/27		for preferred events getCitations returns		*
 *						both new and old style citations				*
 *		2014/11/28		migrating citations only updated when event		*
 *						saved											*
 *		2014/11/30		add method getAssociatedRecord to centralize	*
 *						cases where member associatedRecord is			*
 *						initialized after creation of the Event, and	*
 *						to provide controlled public access				*
 *		2014/12/02		missing parameter to							*
 *						Citation::deleteCitations						*
 *		2014/12/04		add support for sealed to parents event in		*
 *						LegacyChild										*
 *		2014/12/08		diagnostic output goes to $warn to be displayed	*
 *						after the beginning of the page					*
 *		2014/12/19		add privacy limit to method getDate				*
 *		2015/01/02		ensure 'birthsd' in tblIR and tblNX matches		*
 *						preferred birth event so searches based on		*
 *						birth date work									*
 *		2015/01/04		order field not initialized for new indiv		*
 *		2015/01/14		improve diagnostic trace entries				*
 *		2015/01/15		remove extra blank from end of some event texts	*
 *						do not save empty preferred events				*
 *		2015/02/01		throw exception if creating death event with	*
 *						non-empty description							*
 *						correct XML output from delete method			*
 *		2015/02/18		support multiple value fields in getEvents,		*
 *						updateEvents, and deleteEvents					*
 *		2015/03/20		add method addCitations to support merging		*
 *						reload citations in toXML						*
 *		2015/04/26		warning about non-blank description in death	*
 *						event invalid XML								*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/05/20		deleting event passed wrong parameter value		*
 *						to LegacyIndiv::clearEvent for most events		*
 *		2015/06/06		correct `order` value when adding citation		*
 *		2015/06/26		add method compare								*
 *		2015/07/03		add method setAssociatedRecord					*
 *		2015/07/21		do not set idime to zero in citations			*
 *		2015/09/11		do not print out contents of $warn				*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/08		base class changed to RecordPic					*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *						addCitations did not merge citations where		*
 *						one event was an old style event imbedded in	*
 *						a record and the other was a true event			*
 *		2016/10/15		do not save null events							*
 *		2016/12/30		use passed instance of LegacyIndiv in			*
 *						initFromPerson									*
 *		2016/12/31		correct eventsd in death and buried events		*
 *						built from tblIR record							*
 *		2017/01/03		permit setting special values of eventsd		*
 *						without changing displayed date					*
 *		2017/01/26		change default for Preferred to 0 (false)		*
 *		2017/03/17		correct order field when getting citations		*
 *						updateEvents did nothing						*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/03/30		method getWhere built incorrect where clause	*
 *						and SQL parameter array							*
 *		2017/04/23		correct order of christening and burial dates	*
 *						if there are real birth and death events		*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/07		class LegacyChild renamed to class Child		*
 *		2017/09/25		create empty record for bad IDER instead of		*
 *						throwing an exception							*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/30		complete conversion to PDO prepared statements	*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/11/12		replace use of static methods of Citation		*
 *						class with RecordSet							*
 *		2017/12/18		add support for temporary fields				*
 *						correct error in getCitations					*
 *		2018/02/10		add internationalization of dates				*
 *		2018/12/07      correct method setDate for when it is called    *
 *		                with internal date value                        *
 *		                interpret "preferred" as matching instance with *
 *		                lowest order, preparing to remove "preferred"   *
 *		2018/12/21      do not throw exception for bad values in        *
 *		                method set.  Support setting fields to null.    *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/06/01      improve JSON output                             *
 *		2019/07/18      use Person::getPerson                           *
 *		2019/07/21      use Location::getLocation                       *
 *		2019/09/20      correct Event::dump to always return the output *
 *		                constructor ignored eventsd parameter as used   *
 *		                by Person:: getXxxxxEvent                       *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/RecOwner.inc';

/************************************************************************
 *  Event																*
 *																		*
 *  Definition of a class implementing behavior for event records		*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class Event extends RecordPic
{
    /********************************************************************
     *	Event::IDTYPE_XXX												*
     *																	*
     *	Values of record type field										*
     *  These specify the type of main record with which the event is	*
     *	associated, either an instance of Person or Family,				*
     *	Child.															*
     *	Returned by Event::getIdType									*
     ********************************************************************/
    const IDTYPE_INDIV	 	            =  0;	// Person
    const IDTYPE_MAR		            = 20;	// Family
    const IDTYPE_CHILD		            = 21;	// Child

    /********************************************************************
     *	Event::ET_XXXXX													*
     *																	*
     *	The standard values of the event type field 'idet'				*
     *	In Legacy Family Tree new event types are defined by adding them*
     *	to table tblET.  However that technique does not support I18N.	*
     *	So all new event types must be added here with interpretation in*
     *	Event::eventText and translations in the context specific		*
     *	templates                                                       *
     ********************************************************************/
    const ET_NULL		 	    		=  1;
    const ET_ADOPTION					=  2;
    const ET_BIRTH			    		=  3;
    const ET_BURIAL			    		=  4;
    const ET_CHRISTENING				=  5;
    const ET_DEATH			    		=  6;
    const ET_ANNULMENT					=  7;
    const ET_LDS_BAPTISM				=  8;	// LDS
    const ET_BARMITZVAH					=  9;
    const ET_BASMITZVAH					= 10;
    const ET_BLESSING					= 11;
    const ET_CENSUS			    		= 12;
    const ET_CIRCUMCISION				= 13;
    const ET_CITIZENSHIP				= 14;
    const ET_CONFIRMATION				= 15;
    const ET_LDS_CONFIRMATION			= 16;	// LDS
    const ET_COURT				    	= 17;
    const ET_CREMATION					= 18;
    const ET_DEGREE				    	= 19;
    const ET_DIVORCE					= 20;
    const ET_DIVORCE_FILING				= 21;
    const ET_EDUCATION					= 22;
    const ET_EMIGRATION					= 23;
    const ET_EMPLOYMENT					= 24;
    const ET_ENGAGEMENT					= 25;
    const ET_FIRST_COMMUNION			= 26;
    const ET_GRADUATION					= 27;
    const ET_HOBBIES					= 28;
    const ET_HONOURS					= 29;
    const ET_HOSPITAL					= 30;
    const ET_ILLNESS					= 31;
    const ET_IMMIGRATION				= 32;
    const ET_INTERVIEW					= 33;
    const ET_LAND				    	= 34;
    const ET_MARRIAGE_BANNS				= 35;
    const ET_MARRIAGE_CONTRACT			= 36;
    const ET_MARRIAGE_LICENSE			= 37;
    const ET_MARRIAGE_NOTICE			= 38;
    const ET_MARRIAGE_SETTLEMENT		= 39;
    const ET_MEDICAL					= 40;
    const ET_MEMBERSHIP					= 41;
    const ET_MILITARY_SERVICE			= 42;
    const ET_MISSION					= 43;
    const ET_NAMESAKE					= 44;
    const ET_NATURALIZATION				= 45;
    const ET_OBITUARY					= 46;
    const ET_OCCUPATION					= 47;
    const ET_ORDINANCE					= 48;
    const ET_ORDINATION					= 49;
    const ET_PHYSICAL_DESCRIPTION		= 50;
    const ET_PROBATE					= 51;
    const ET_PROPERTY					= 52;
    const ET_RELIGION					= 53;
    const ET_RESIDENCE					= 54;
    const ET_RETIREMENT					= 55;
    const ET_SCHOOL				    	= 56;
    const ET_SOCIAL_SECURITY_NUMBER		= 57;
    const ET_WILL				    	= 58;
    const ET_MEDICAL_CONDITION			= 59;
    const ET_MILITARY					= 60;
    const ET_PHOTO				    	= 61;
    const ET_SOC_SEC_NUM				= 62;
    const ET_OCCUPATION_1				= 63;
    const ET_NATIONALITY				= 64;
    const ET_FAMILY_GROUP				= 65;
    const ET_ETHNICITY					= 66;
    const ET_FUNERAL					= 67;
    const ET_ELECTION					= 68;
    const ET_MARRIAGE					= 69;
    const ET_MARRIAGE_FACT				= 70;	// installation defined
    const ET_BIRTH_REGISTRATION			= 71;	// added
    const ET_DEATH_REGISTRATION			= 72;	// added
    const ET_MARRIAGE_REGISTRATION		= 73;	// added
    const ET_LDS_ENDOWED				= 74;	// added LDS endowment
    const ET_LDS_INITIATORY				= 75;	// added LDS initiatory
    const ET_LDS_SEALED					= 76;	// added LDS spouse sealing
    const ET_MARRIAGE_END				= 77;	// added
    const ET_LDS_PAR_SEALED				= 78;	// added LDS child sealing

    /********************************************************************
     *  Event::$eventText												*
     *																	*
     *  This table provides a translation from an event type to the text*
     *  to display to the user.  This is ordered alphabetically.		*
     *																	*
     *	This table is superceded by the language specific               *
     *	tables in template "Trantab$lang.html"							*
     *																	*
     *	    $tranTab        = $template->getTranslate();                *
     *	    $eventText      = $tranTab['eventText'];                    *
     *	    $action         = $eventText[$idet];                        *
     *	    $eventStmt      = $tranTab['eventStmt'];                    *
     *	    $sentence       = $eventStmt[idet];                         *
     ********************************************************************/
    static $eventText	= array(
				 2 		=>'adopted',
				63 		=>'also worked as a ',
				17 		=>'appeared in court',
				56 		=>'attended school',
		     15000 		=>'baptized (LDS)',		// obsolete
				 8 		=>'baptized (LDS)',
				 9 		=>'Bar Mitzvah',
				10 		=>'Bat Mitzvah',
				14 		=>'became a citizen',
				41 		=>'belonged to',
				71 		=>'birth was registered',
				11 		=>'blessed (LDS)',
		      2000 		=>'born',			// obsolete
				 3 		=>'born',
		      5000 		=>'buried',			// obsolete
				 4 		=>'buried',
		      3000 		=>'christened',		// obsolete
				 5 		=>'christened',
				13 		=>'circumcized',
				15 		=>'confirmed',
		     26000 		=>'confirmed (LDS)',		// obsolete
				16 		=>'confirmed (LDS)',
				18 		=>'cremated',
				72 		=>'death was registered',
				50 		=>'described as',
		      4000 		=>'died',			// obsolete
				 6 		=>'died',
				20 		=>'divorced',
				22 		=>'education',
				68 		=>'elected as',
				23 		=>'emigrated from',
				24 		=>'employed as',
		     16000 		=>'endowed (LDS)',		// obsolete
				74 		=>'endowed (LDS)',
				25 		=>'engaged',
				36 		=>'entered marriage contract',
				12 		=>'enumerated in a census',
				66 		=>'ethnicity',
				65 		=>'family group',
				21 		=>'filed for divorce',
				26 		=>'first communion',
				67 		=>'funeral',
				27 		=>'graduated from',
				28 		=>'hobby of',
				29 		=>'honored as',
				31 		=>'ill with',
				32 		=>'immigrated',
				30 		=>'in hospital',
				60 		=>'in the military',
		     27000 		=>'initiatory (LDS)',	// obsolete
				75 		=>'initiatory (LDS)',
				33 		=>'interviewed',
				54 		=>'lived',
				35 		=>'marriage banns issued',
				37 		=>'marriage license',
				39 		=>'marriage settlement',
				 7 		=>'marriage was annulled',
				73 		=>'marriage was registered',
				38 		=>'marriage notice',
				69 		=>'married',
		     20000 		=>'married',			// obsolete
				70 		=>'',			// type is in description
				59 		=>'medical condition',
				40 		=>'medical event',
				42 		=>'military',
				43 		=>'mission',
				44 		=>'named for',
				64 		=>'nationality',
				45 		=>'naturalized',
				 1 		=>'null event 1',
				46 		=>'obituary published',
				49 		=>'ordained as',
				48 		=>'ordinance (LDS)',
				34 		=>'owned land',
				52 		=>'owned property',
				61 		=>'photo',
				19 		=>'degree',
				53 		=>'religious affiliation',
				55 		=>'retired',
				78 		=>'sealed to parents',
				76 		=>'sealed to spouse',
		     18000 		=>'sealed to spouse',	// obsolete
				57 		=>'Social Security Number',
				62 		=>'Social Security Number (62)',
				51 		=>'will probated',
				58 		=>'will',
				47 		=>'worked as a',
				 0 		=>'unused 0'
				);

    /********************************************************************
     *	Event::initRow													*
     *																	*
     *	static array containing default values for a new record			*
     ********************************************************************/
    protected static	$initRow	= array(
				'ider'					=> 0,
				'idir'					=> 0,
				'idtype'				=> self::IDTYPE_INDIV,
				'idet'					=> self::ET_NULL,
				'order'					=> 0,
				'eventd'				=> '',
				'eventsd'				=> -99999999,
				'idlrevent'				=> 1,
				'desc'					=> '',
				'gedtag'				=> '',
				'eventexclude'			=> 0,
				'idar'					=> 0,
				'description'			=> '',
				'sentenceoverride'		=> '',
				'qstag'					=> 0,
				'rgexclude'				=> 0,
				'kind'					=> 0,
				'ldstempleready'		=> null,
				'preferred'				=> 0);

    /********************************************************************
     *	Event::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'tblER',
                'name'      => 'Events',
                'prime'	    => 'ider',
	            'srvmg'     => true,
	            'fldcount'	=> 4,
                'order'     => '`Order`',
                'classname' => 'Event');

    /********************************************************************
     *	Event::$translate												*
     *																	*
     *	array containing synonyms for field names						*
     ********************************************************************/
    protected static	$translate	= array(
				'id'		=> 'ider',
				'notes'		=> 'desc',
				'date'		=> 'eventd',
                'location'	=> 'idleevent',
                'private'   => 'eventexclude');

    /********************************************************************
     *	Event::defaultOrder												*
     *																	*
     *	Default order for results returned from RecordSet				*
     ********************************************************************/
    protected static	$defaultOrder	= '`Order`';

    /********************************************************************
     *	$event->associatedRecord										*
     *																	*
     *	A pointer to the associated record if it was passed in the		*
     *	parameters.														*
     ********************************************************************/
     private $associatedRecord	    	= null;
     private $recordSetByConstructor	= false;

    /********************************************************************
     *	$event->citations												*
     *																	*
     *	An associative array of instances of LegacyCitations.			*
     ********************************************************************/
     private $citations			= null;

    /********************************************************************
     *	function Event::createNewRecord									*
     *																	*
     *	Function to create a record when an existing record has not		*
     *  been identified by IDER											*
     *																	*
     *	Input:															*
     *	    $parms				parameters passed to constructor		*
     *	    $idet				IDET value for new record				*
     *	    $idir				id of record associated with event		*
     *	    $idtype				IDTYPE value							*
     *	    $order				event order								*
     *	    $preferred			preferred indicator						*
     *																	*
     ********************************************************************/
     private function createNewRecord($parms, 
								      $idet,
								      $idir,
								      $idtype,
								      $order,
								      $preferred)

    {			// need to create new record
		global	$debug;
		global	$warn;
		global	$connection;

        if ($debug)
            $warn           .= "<p>Event::createNewRecord(\$parms=" . print_r($parms, true) . ",  \$idet=$idet='" . self::$eventText[$idet] . "', \$idir=$idir, \$idtype=$idtype, \$order=$order, \$preferred=$preferred)</p>\n";
		$dbrow		        = self::$initRow;
		$needInsert	        = true;
		$ider		        = 0;

		if ($idet != self::ET_NULL && $preferred)
		{			    // record migrated to tblER
		    if ($idir > 0)
		    {		    // have a completed base record
				// construct the query of the event table
				$query	    = 'SELECT * FROM tblER ' .
				    	     		    'WHERE IDIR=:idir AND ' .
					    			            'IDType=:idtype AND ' .
                                                'IDET=:idet ' .
                                        'ORDER BY `order` LIMIT 1'; 
				$sqlParms	= array('idir'		=> $idir,
								    'idtype'	=> $idtype,
								    'idet'		=> $idet);

				// query the database
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $sqlParms);
				if ($stmt->execute($sqlParms))
				{		// success
				    $row	= $stmt->fetch(PDO::FETCH_ASSOC);
				    if ($debug)
						$warn	.= "<p>Event::CreateNewRecord: " .
							    __LINE__ . " $queryText</p>\n";
				}		// success
				else
				{		// error getting record
				    $this->msg  .= "'$queryText' result=" .
								    print_r($stmt->errorInfo(),true);
				}		// error getting record
		    }		    // have a completed base record
		    else
				$row		= false;

		    // complete initialization of row
		    if ($idir > 0 && is_array($row))
		    {		    // found matching event record
				$dbrow			= $row;
				$ider			= $dbrow['ider'];
				$order			= $dbrow['order'];
				$needInsert		= false;
		    }		    // found matching event record
		    else
		    {		    // set default values of all fields
				$dbrow			= self::$initRow;
		    }		    // set default values of all fields
		}			    // record migrated to tblER
		else
		{			    // new ordinary event
		    $dbrow		= self::$initRow;
		}			    // new ordinary event

		// apply updates from parameters
		foreach($parms as $fldname => $value)
		{		        // loop through parameters
		    $fieldLc	= strtolower($fldname);
		    switch($fieldLc)
		    {		    // act on specific fields
				case 'ider':
				{		// key of record in tblER
				    break;
				}		// key of record in tblER

				case 'idir':
				{
				    $dbrow['idir']		    = $idir;
				    break;
				}	    // key of associated record

				case 'idmr':
				{		// special shortcut
				    $dbrow['idir']		    = $idir;
				    $dbrow['idtype']		= Event::IDTYPE_FAMILY;
				    break;
				}		// special shortcut

				case 'idcr':
				{		// special shortcut
				    $dbrow['idir']		    = $idir;
				    $dbrow['idtype']		= Event::IDTYPE_CHILD;
				    break;
				}		// special shortcut


				case 'idtype':
				{		// type of associated record
				    $dbrow['idtype']		= $idtype;
				    break;
				}		// type of associated record

				case 'idet':
				{		// type of associated record
				    $dbrow['idet']		    = $idet;
				    break;
				}		// type of associated record

				case 'eventd':
                {		// event date
                    if (is_string($value) && strlen($value) > 0)
                        $value      = new LegacyDate($value);
				    if ($value instanceof LegacyDate)
				    {
						$dbrow['eventd']	= $value->getDate();
						$dbrow['eventsd']	= $value->getSortDate();
                    }
                    else
						$dbrow['eventd']	= $value;
				    break;
				}		// event date

				case 'eventsd':
                {		// see 'eventd'
                    if ($dbrow['eventd'] == '')
                    {
                        $dbrow['eventsd']   = $value;
                    }
				    break;
				}		// see 'eventd'-


				case 'order':
				case 'idlrevent':
				case 'eventexclude':
				case 'idar':
				case 'qstag':
				case 'rgexclude':
				case 'kind':
				case 'ldstempleready':
				case 'preferred':
				{		// other numeric field names
				    $dbrow[$fldname]	= intval($value);
				    break;
				}		// other numeric field names

				case 'desc':
				case 'gedtag':
				case 'description':
				case 'sentenceoverride':
				{		// other text field names
				    $dbrow[$fldname]	= $value;
				    break;
				}		// other text field names

		    }		    // act on specific fields
		}		        // loop through parameters

		// determine appropriate event order for new record
		if ($idir > 0 && $ider == 0 && $order == 0)
		{		        // event is to be added to a specific record
		    // determine initial value for 'order'
		    $query	            = 'SELECT COUNT(*) FROM tblER ' .
						            'WHERE IDIR=:idir AND IDType=:idtype';
		    $sqlParms	        = array('idir'		=> $idir,
							            'idtype'	=> $idtype);
		    $stmt	            = $connection->prepare($query);
		    $queryText	        = debugPrepQuery($query, $sqlParms);

		    if ($stmt->execute($sqlParms))
		    {		    // success
				if ($debug)
				    $warn	    .= "<p>Event::createNewRecord: " . __LINE__ . 
								    " '$queryText'</p>\n";
				$row		    = $stmt->fetch(PDO::FETCH_NUM);
				$count		    = $row[0];
				$dbrow['order']	= $count;
		    }		    // success
		    else
		    {		    // error querying existing records
				throw new Exception("Event::_construct: " .
							            "SQL='$queryText' result=" .
							            print_r($stmt->errorInfo(),true));
		    }		    // error querying existing records
		}		        // event is to be added to a specific record
		else
		{		        // use explicit ORDER value if passed
		    $dbrow['order']	= $order;
		}		        // use explicit ORDER value if passed

		parent::__construct($dbrow,
						    'tblER');
		$this->needInsert	= $needInsert;
    }		// private function createNewRecord

    /********************************************************************
     *  function Event::__construct										*
     *																	*
     *  Given the identifier or database result construct an event		*
     *	record.															*
     *																	*
     *  Input:															*
     *	    $parms		a database record presented as an array		    *
     *	             or array('ider' => $ider) to construct from        *
     *	                an existing record                              *
     *	             or array('ider' => 0) to create a new record       *
     *	    $idtype		if $id is 'new' the initial value of 'idtype'	*
     *	                deprecated                                      *
     *	    $idir		if $id is 'new' the initial value of 'idir'		*
     *					or an instance of Person or Family				*
     *					or Child										*
     *	                deprecated                                      *
     *																	*
     *  Returns object containing event record.							*
     ********************************************************************/
    function __construct($parms     = null,
						 $idtype	= 0,        // deprecated
						 $idir		= 0)        // deprecated
    {
		global	$debug;
		global	$warn;
		global	$connection;

		// action depends upon type of first parameter
        // standardize input to an associative array
        if (is_null($parms))
            $parms      = array();
        else
		if (is_int($parms))
		{		// numeric identifier of record
		    $parms		= array('ider'	=> intval($parms));
		}		// numeric identifier of record
		else
		if (is_string($parms) && ctype_digit($parms))
		{		// numeric identifier of record
		    $parms		= array('ider'	=> intval($parms));
		}		// numeric identifier of record
		else
		if ($parms == 'new')
		{		// old style create new event
		    $parms		= array('idtype'	=> $idtype,
								'idir'		=> $idir);
		}		// old style create new event

        if ($debug && is_array($parms))
		{
		    $parmsTxt	        = 'Array';
		    $comma	            = '[';
		    foreach($parms as $key => $parm)
		    {		// loop through parameters
				$parmsTxt	    .= $comma . $key . '=';
				if (is_object($parm))
				    $parmsTxt	.= get_class($parm) . ' object';
				else
				    $parmsTxt	.= gettype($parm) . '=' . $parm;
				$comma		    = ', ';
		    }		// loop through parameters
		    $warn	            .= "<p>Event::__construct(" .
                $parmsTxt . "])</p>\n";
            $backtrace          = debug_backtrace();
            foreach($backtrace as $call)
            {
                if (array_key_exists('class', $call) &&
                    strlen($call['class']) > 0)
                    $classPref      = $call['class'] . '::';
                else
                    $classPref      = '';
                $warn   .= "<p>Event::__construct: " . __LINE__ .
                    " function=$classPref" . $call['function'] .
                    " called from line " . $call['line'] . 
                    " of " . $call['file'] . "</p>\n";
            }
		}

		// act on parameter to select and update record
		if (is_array($parms))
		{			// associative array
		    $ider		        			= 0;
		    $iderSpecified	    			= false;
		    $idet		        			= self::ET_NULL;
		    $idir		        			= 0;	// key of associated record
		    $idtype		        			= self::IDTYPE_INDIV;
		    $order		        			= 0;
		    $preferred		    			= 0;
		    $fieldsValid	    			= true;
		    $needInsert	        			= true;
	        $dbrow                  		= self::$initRow;
            $this->msg		        		= '';
            $and                    		= 'WHERE ';
            $where                  		= '';
            $sqlParms               		= array();

		    // validate parameters
		    foreach($parms as $fldname => $value)
		    {			// loop through parameters
				switch(strtolower($fldname))
				{		// act on identification fields
				    case 'ider':
				    {		// key of record in tblER
						$ider		        = $value;
						$iderSpecified	    = $ider > 0;
						break;
				    }		// key of record in tblER

				    case 'idir':
				    {
						if ($value instanceof Record)
						{	// actual record passed, not just key
						    $this->associatedRecord		    = $value;
						    $this->recordSetByConstructor	= true;
						    if ($value->isExisting())
							    $idir		                = $value->getId();
						    else
							    $idir		                = 0;
						    $parms['idir']	                = $idir;
						}	// actual record passed, not just key
						else
                            $idir		        = $value;
                        if ($idir > 0)
                        {
                            $where              .= $and . "IDIR=:idir";
                            $sqlParms['idir']   = $idir;
                            $and                = ' AND ';
                        }
						break;
				    }	// key of associated record

				    case 'idmr':
				    {		// special shortcut for legibility
						if ($value instanceof Record)
						{	// actual record passed, not just key
						    if (!($value instanceof Family))
							    $this->msg	.= "IDMR object must be Family. ";
						    $this->associatedRecord		    = $value;
						    $this->recordSetByConstructor	= true;
						    $idir		                    = $value->getId();
						    $parms['idir']	                = $idir;
						}	// actual record passed, not just key
						else
						    $idir		                    = $value;
						$idtype			                    = self::IDTYPE_MAR;
                        if ($idir > 0)
                        {
                            $where              .= $and .
                                            "IDIR=:idir AND IdType=:idtype";
                            $sqlParms['idir']   = $idir;
                            $sqlParms['idtype'] = self::IDTYPE_MAR;
                            $and                = ' AND ';
                        }
						break;
				    }		// special shortcut for legibility

				    case 'idcr':
				    {		// special shortcut for legibility
						if ($value instanceof Record)
						{	// actual record passed, not just key
						    if (!($value instanceof Child))
							    $this->msg	.= "IDCR object must be Child. ";
						    $this->associatedRecord		    = $value;
						    $this->recordSetByConstructor	= true;
						    $idir		                    = $value->getId();
						    $parms['idir']	                = $idir;
						}	// actual record passed, not just key
						else
						    $idir		                    = $value;
						$idtype			                = self::IDTYPE_CHILD;
                        if ($idir > 0)
                        {
                            $where              .= $and .
                                            "IDIR=:idir AND IdType=:idtype";
                            $sqlParms['idir']   = $idir;
                            $sqlParms['idtype'] = self::IDTYPE_CHILD;
                            $and                = ' AND ';
                        }
						break;
				    }		// special shortcut for legibility

				    case 'idtype':
				    {		// type of associated record
						if ($value == self::IDTYPE_INDIV ||
						    $value == self::IDTYPE_MAR ||
                            $value == self::IDTYPE_CHILD)
                        {
                            $idtype		        = $value;
                            $where              .= $and . "IdType=:idtype";
                            $sqlParms['idtype'] = self::IDTYPE_CHILD;
                            $and                = ' AND ';
                        }
						else
						    $this->msg	.= "Bad value $value for '$fldname'. ";
						break;
				    }		// type of associated record

				    case 'idet':
				    {		// event type
                        $idet		        = $value;
                        if ($idet != Event::ET_NULL)
                        {
                            $where              .= $and . "IDET=:idet";
                            $sqlParms['idet']   = $idet;
                            $and                = ' AND ';
                        }
						break;
				    }		// event type

				    case 'order':
				    {		// event order
						$order		            = $value;
                        $where                  .= $and . "`Order`=:order";
                        $sqlParms['order']      = $order;
                        $and                    = ' AND ';
						break;
				    }		// event order

				    case 'preferred':
                    {		// other field names
                        if ($value)
                            $preferred	        = 1;    // non-zero or true
                        else
                            $preferred	        = 0;    // zero or false
                        $where                  .= $and . "Preferred=:pref";
                        $sqlParms['pref']       = $preferred;
                        $and                    = ' AND ';
						break;
				    }		// other field names

				    case 'eventd':
				    case 'eventsd':
				    case 'idlrevent':
				    case 'desc':
				    case 'gedtag':
				    case 'eventexclude':
				    case 'idar':
				    case 'description':
				    case 'sentenceoverride':
				    case 'qstag':
				    case 'rgexclude':
				    case 'kind':
				    case 'ldstempleready':
				    {		// non-identification field names
						break;
				    }		// non-identification field names

				    default:
				    {		// unknown field name
						$warn	.= "<p>Event::__construct: " . __LINE__ . 
							" Unexpected fieldname '$fldname' ignored.<p>\n";
						$fieldsValid	= false;
						break;
				    }		// unknown field name
				}		// act on identification fields
		    }			// loop through parameters

		    // the following is special handling for pseudo-events
		    // created by class Person or Family
		    if ($idet >= 1000 || $idtype & 1)
				$ider	= 0;		// belt & suspenders

		    // a complete database row supplied?
		    if (count($parms) >= count(self::$initRow) &&
				$fieldsValid)
		    {			// complete valid row from database
				$dbrow			    = $parms;
				parent::__construct($dbrow,
							        'tblER');
				$needInsert		    = false;
				$this->needInsert	= false;
		    }			// complete row from database
            else
		    {			            // search and initialization list
                if ($ider > 0)
                {                   // search for existing record
					$needInsert	    = false;
					// construct the query of the event table
					$query		    = "SELECT * FROM tblER WHERE IDER=:ider";
					$sqlParms	    = array('ider'	=> $ider);
	
					// query the database
					$stmt		    = $connection->prepare($query);
					$queryText	    = debugPrepQuery($query, $sqlParms);
	
					if ($stmt->execute($sqlParms))
					{		// success
					    if ($debug)
							$warn	.= "<p>Event::__construct: " .__LINE__ .
									    " $queryText</p>\n";
						$row	            = $stmt->fetch(PDO::FETCH_ASSOC);
					    if (is_array($row))
					    {		    // found matching event record
							$dbrow		    = $row;
				            $needInsert		= false;
					    }		    // found matching event record
					    else
	                    {		    // create empty event
                            if ($debug)
                                $warn   .= "Event::__construct: " . __LINE__ .
								    " record not found for ider=$ider" .
								    ", parms=" . print_r($parms, true) . "\n";
							$needInsert		= true;
					    }		    // create empty event
					}			    // success
					else
					{			    // error reading record
					    $this->msg  .= "Event::__construct: " . __LINE__ .
									" '$queryText' result=" .
									print_r($stmt->errorInfo(),true);
					}			    // error reading record
                }                   // search for existing record
                else
                {                   // explicit new record
					$needInsert		        = true;
                }                   // explicit new record

                if ($needInsert && $idir > 0)
                {
				    $this->createNewRecord($parms,
								   		   $idet,
								   		   $idir,
								   		   $idtype,
								   		   $order,
                                           $preferred);
                }
                else
                {                       // invoke constructor of base class
					parent::__construct($dbrow,
								        'tblER');
					foreach($parms as $fldname => $value)
					{		            // loop through parameters
					    $fieldLc	    = strtolower($fldname);
					    // apply any requested changes
					    if ($fieldLc != 'ider')
						    $this->set($fieldLc, $value);
					}		            // loop through parameters
                }           // invoke constructor of base class
				$this->needInsert	= $needInsert;

		    }			                // search and initialization list
		}			                    // associative array
		else
		{		                        // unexpected parameter type
		    $this->msg      .= "Event::__construct: called with " .
                gettype($parms) . ' ' . print_r($parms, true) .
                ". ";
		}		                        // unexpected parameter type

        // fixups
        $eventd             = $this->row['eventd'];
        $eventsd            = $this->row['eventsd'];
        $idet               = $this->row['idet'];
        if ($eventd == '' && $eventsd == -99999999)
        {
            if ($idet == self::ET_BURIAL)
            {
                $this->set('eventsd',       99999999);
            }
            else
            if ($idet == self::ET_DEATH)
            {
                $this->set('eventsd',       99999998);
            }
        }
        else
        if (strlen($eventd) > 1 && substr($eventd, 0, 1) == '1' &&
            $eventsd % 10000 == 615)
        {
            if ($idet == self::ET_BIRTH)
            {
                $eventsd    = $eventsd - 615;
                $this->set('eventsd',       $eventsd);
            }
            else
            if ($idet == self::ET_CHRISTENING)
            {
                $eventsd    = $eventsd + 615;
                $this->set('eventsd',       $eventsd);
            }
        }

		// diagnostic output if debug is set
		$this->dump('Event Record constructed:');

		// validation failed
		if (strlen($this->msg) > 0)
			$msg            = $this->msg;   // display to user
    }		// Event::__construct

    /********************************************************************
     *  function Event::delete											*
     *																	*
     *  Delete the database record associated with this instance.		*
     *																	*
     *  Input:															*
     *	    $xml		if true emit XML output							*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record		*
     *	    0			if the record did not need to be deleted		*
     ********************************************************************/
    function delete($xml = false)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$ider		= $this->row['ider'];
		if ($ider == 0)
		    return;
		$idType		= $this->row['idtype'];
		$count		= false;

		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $msgtag	= "p class='message'";
		    $emsgtag	= "p";
		    $xml	= true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		    $msgtag	= 'msg';
		    $emsgtag	= 'msg';
		}		// true
		else
		    $tagname	= false;

		// to simplify expressions
		if (!$this->needInsert &&
		    ($idType == Event::IDTYPE_INDIV ||
		     $idType == Event::IDTYPE_MAR ||
		     $idType == Event::IDTYPE_CHILD) &&
		    $ider > 0)
		{		// have ider value and record is written to table
		    $delete	                = "DELETE FROM tblER WHERE IDER=:ider";
		    $sqlParms	            = array('ider' => $ider);
		    $stmt	                = $connection->prepare($delete);
            $deleteText	            = debugPrepQuery($delete, $sqlParms);
            $this->lastSqlCmd       = $deleteText;
		    if ($stmt->execute($sqlParms))
		    {		// success
				$count		        = $stmt->rowCount();
				if ($debug)
				    $warn	        .= "<p>Event::delete: " . __LINE__. 
					        		   " '$deleteText', count=$count</p>\n";
				if ($xml)
				    print "<$tagname count='$count'>$deleteText</$tagname>\n";

				// log the update
				$this->log($delete,
						   $sqlParms);

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->row['ider']	= 0;
				$this->change		= null;
				$this->needInsert	= true;
		    }		// success
		    else
		    {		// error updating record
				error_log("Event.inc: " . __LINE__ . " '$delete', " .
							print_r($stmt->errorInfo(),true) . "\n");
				return false;
		    }		// error updating record
		}		// have ider value and record is written to table

		// delete the associated citations if any
		$cittype	= $this->row['idet'];
		$parms		= array('idime'		=> $ider,
							'type'		=> $cittype);
		$citations	= new RecordSet('Citations', $parms);
		$count		= $citations->delete($tagname);

		return $count;	// number of records deleted
    }		// Event::delete

    /********************************************************************
     *  function Event::getName											*
     *																	*
     *	Get a descriptive summary of the object.						*
     *	This function is called getName for symmetry with Person		*
     *	and Name.														*
     ********************************************************************/
    function getName($options = null)
    {
		if ($this->row['idtype'] == self::IDTYPE_INDIV ||
		    $this->row['idtype'] == self::IDTYPE_MAR ||
		    $this->row['idtype'] == self::IDTYPE_CHILD)
		{		// use getName method of associated record
		    $record	= $this->getAssociatedRecord();
		    $name	= $record->getName() . ' ';
		}		// use getName method of associated record
		else
		    $name	= 'Bad IDTYPE=' . $this->row['idtype'] . ': ';
		$name		.= self::$eventText[$this->row['idet']] . ' ' .
						   $this->getDescription() . ' at ' .
						   $this->getLocation()->getName() . ' ' .
						   $this->getDate();

		return $name;
    }		// Event::getName

    /********************************************************************
     *  function Event::getId											*
     *																	*
     *	Get the unique numeric identifier of the event.					*
     *	Synonym of Event::getIder.										*
     ********************************************************************/
    function getId()
    {
		return $this->row['ider'];
    }		// Event::getId

    /********************************************************************
     *  function Event::getIder											*
     *																	*
     *	Get the unique numeric identifier of the event.					*
     *	Synonym of Event::getId.										*
     ********************************************************************/
    function getIder()
    {
		return $this->row['ider'];
    }		// Event::getIder

    /********************************************************************
     *  function Event::getIdir											*
     *																	*
     *	Get the unique numeric identifier of the record the event		*
     *	is associated with.  This is either an instance of Person		*
     *	or Family depending upon the value of getIdType.				*
     *																	*
     *	Deprecated: use $event['idir']									*
     ********************************************************************/
    function getIdir()
    {
		return $this->row['idir'];
    }		// Event::getIdir

    /********************************************************************
     *  function Event::getAssociatedRecord								*
     *																	*
     *	Get the record describing the object this event 				*
     *	is associated with.  This is either an instance of Person		*
     *	or Family depending upon the value of getIdType.				*
     ********************************************************************/
    function getAssociatedRecord()
    {
		global $debug;
		global $warn;

		if (is_null($this->associatedRecord))
		{			// not initialized yet
		    $idime	= $this->row['idir'];
		    if ($idime == 0)
		    {
				$debug	= true;
				$this->dump('Field idir is zero');
				throw new Exception("Event::getAssociatedRecord: " .
							    "field idir is zero");
		    }
		    switch($this->row['idtype'])
		    {
				case self::IDTYPE_INDIV:
				{
				    $this->associatedRecord	= Person::getPerson($idime);
				    break;
				}

				case self::IDTYPE_MAR:
				{
				    $this->associatedRecord	=
							new Family(array('idmr' => $idime));
				    break;
				}

				case self::IDTYPE_CHILD:
				{
				    $this->associatedRecord	=
							new Child($idime);
				    break;
				}

				default:
				{
				    throw new Exception("Event::getAssociatedRecord: " .
							 	"field idtype is " .
								$this->row['idtype']);
				    break;
				}

		    }			// switch on idtype
		}			// not initialized yet

		return $this->associatedRecord;
    }		// Event::getAssociatedRecord

    /********************************************************************
     *  function Event::getPerson										*
     *																	*
     *	Get the instance of Person	this event is associated with.		*
     ********************************************************************/
    function getPerson()
    {
		global $debug;
		global $warn;

		$record		= $this->getAssociatedRecord();
		switch($this->row['idtype'])
		{
		    case self::IDTYPE_INDIV:
		    {
				return $record;
		    }

		    case self::IDTYPE_MAR:
		    {
				$husband	= $record->getHusband();
				if (is_null($husband))
				    return $record->getWife();
				else
				    return $husband;
				break;
		    }

		    case self::IDTYPE_CHILD:
		    {
				return $record->getPerson();
		    }

		    default:
		    {
				throw new Exception("Event::getPerson: " .
						 	    "field idtype is " .
							    $this->row['idtype']);
				break;
		    }

		}			// switch on idtype
    }		// Event::getPerson

    /********************************************************************
     *  function Event::setAssociatedRecord								*
     *																	*
     *	Set the record describing the object this event 				*
     *	is associated with.  This is either an instance of Person		*
     *	or Family depending upon the value of getIdType.				*
     ********************************************************************/
    function setAssociatedRecord($record)
    {
		if ($this->row['idtype'] == self::IDTYPE_INDIV &&
            $record instanceof Person)
        {
            $this->associatedRecord	= $record;
            $this->row['idir']      = $record['idir'];
		}
		else
		if ($this->row['idtype'] == self::IDTYPE_MAR &&
		    $record instanceof Family)
        {
		    $this->associatedRecord	= $record;
            $this->row['idir']      = $record['idmr'];
		}
		else
		if ($this->row['idtype'] == self::IDTYPE_CHILD &&
		    $record instanceof Child)
        {
		    $this->associatedRecord	= $record;
            $this->row['idir']      = $record['idcr'];
		}
		else
		{
		    $parminfo	= gettype($record);
		    if (is_object($record))
				$parminfo	.= " instance of " . get_class($record);
		    throw new Exception("Event.inc:: setAssociatedRecord: " .
							"Invalid parameter " . $parminfo);
		}
    }		// Event::setAssociatedRecord

    /********************************************************************
     *  function Event::getCitId										*
     *																	*
     *	Get the unique numeric identifier of the record containing		*
     *	the event information for citation purposes.					*
     ********************************************************************/
    function getCitId()
    {
		$idet	            = $this->row['idet'];
		if ($idet > 999)
		    return $this->row['idir'];
		else
		    return $this->row['ider'];
    }		// Event::getCitId

    /********************************************************************
     *  function Event::getIdType										*
     *																	*
     *	Get the record type.											*
     *																	*
     *	Returns:														*
     *		IDTYPE_INDIV 	=  0		Individual Event				*
     *		IDTYPE_MAR		= 20		Marriage Event					*
     *		IDTYPE_CHILD	= 21		Child							*
     ********************************************************************/
    function getIdType()
    {
		return $this->row['idtype'];
    }		// Event::getIdType

    /********************************************************************
     *  function Event::getIdet											*
     *																	*
     *	Get the numeric event type.										*
     *	If this is greater than or equal to 1000, then this instance	*
     * 	represents an event recorded in some other table than tblER,	*
     *	and the value is the Citation::STYPE_xxx constant times			*
     *	1000.															*
     ********************************************************************/
    function getIdet()
    {
		return $this->row['idet'];
    }		// Event::getIdet

    /********************************************************************
     *  function Event::getCitType										*
     *																	*
     *	Get the citation type of this event as defined in				*
     *	class Citation. 												*
     ********************************************************************/
    function getCitType()
    {
		$idet	= $this->row['idet'];
		$idtype	= $this->row['idtype'];

		if ($idet > 999)
		    return floor($idet/1000);
		else
		if ($idtype == Event::IDTYPE_INDIV)
		    return Citation::STYPE_EVENT;
		else
		if ($idtype == Event::IDTYPE_MAR)
		    return Citation::STYPE_MAREVENT;
		else
		if ($idtype == Event::IDTYPE_CHILD)
		    return Citation::STYPE_LDSP;
		else
		switch($idet)
		{		// remap from IDET to STYPE
		    case self::ET_BIRTH;		//   3;
				return Citation::STYPE_BIRTH;
		    case self::ET_BURIAL;		//   4;
				return Citation::STYPE_BURIED;
		    case self::ET_CHRISTENING;		//   5;
				return Citation::STYPE_CHRISTEN;
		    case self::ET_DEATH;		//   6;
				return Citation::STYPE_DEATH;
		    case self::ET_LDS_BAPTISM;		//   8;
				return Citation::STYPE_LDSB;
		    case self::ET_LDS_CONFIRMATION;	//  16;
				return Citation::STYPE_LDSC;
		    case self::ET_MARRIAGE;		//  69;
				return Citation::STYPE_MAR;

		    default:
		    {
				throw new Exception("Event.inc:: getCitType: " .
							    "Unsupported IDET=$idet");
		    }
		}		// remap from IDET to STYPE
    }		// Event::getCitType

    /********************************************************************
     *  function Event::setIdet											*
     *																	*
     *	Set the numeric event type.										*
     *																	*
     *	Return:															*
     *	    Former value.												*
     ********************************************************************/
    function setIdet($value)
    {
		return parent::set('idet', $value);
    }		// Event:setIdet

    /********************************************************************
     *  function Event::getNotes										*
     *																	*
     *	Get the extended Notes of the event.							*
     ********************************************************************/
    function getNotes()
    {
		$notes	= $this->row['desc'];
		if ($notes === null)
		    return '';
		else
		    return $notes;
    }		// Event::getNotes

    /********************************************************************
     *  function Event::setNotes										*
     *																	*
     *	Set the extended Notes of the event.							*
     *																	*
     *	Return:															*
     *	    Former value.												*
     ********************************************************************/
    function setNotes($value)
    {
		return parent::set('desc', $value);
    }		// Event::setNotes

    /********************************************************************
     *  function Event::getDesc											*
     *																	*
     *	Get the Notes of the event.										*
     *	This function name is retained for backwards compatibility, but	*
     *	because it is not descriptive of the meaning of the field and	*
     *	confusing beside the similarly named getDescription, it			*
     *	is disparaged.													*
     ********************************************************************/
    function getDesc()
    {
		$notes	= $this->row['desc'];
		if ($notes === null)
		    return '';
		else
		    return $notes;
    }		// Event::getDesc

    /********************************************************************
     *  function Event::getDescription									*
     *																	*
     *	Get the description of the event.								*
     ********************************************************************/
    function getDescription()
    {
		$text	= $this->row['description'];
		if ($text === null)
		    return '';
		else
		    return $text;
    }		// Event::getDescription

    /********************************************************************
     *  function Event::setDescription									*
     *																	*
     *	Set the description of the event.								*
     *																	*
     *	Return:															*
     *	    Former value.												*
     ********************************************************************/
    function setDescription($value)
    {
		return parent::set('description', $value);
    }		// Event::setDescription

    /********************************************************************
     *  function Event::getLocation										*
     *																	*
     *	Get the location of the event.									*
     *																	*
     *	Returns:														*
     *	    instance of Location or Temple								*
     ********************************************************************/
    function getLocation()
    {
		// fixup for bad values of IDLREvent
		$idlr               = $this->row['idlrevent'];
		if ($idlr < 1)
		    $idlr           = 1;

		// return appropriate object
		if ($this->row['kind'] == 1)
		    $location	    = new Temple(array('idtr' => $idlr));
		else
		    $location	    = Location::getLocation($idlr);
		return $location;
    }		// Event::getLocation

    /********************************************************************
     *  function Event::setLocation										*
     *																	*
     *	Set the location of the event.									*
     *	For events that are actually recorded in another record the		*
     *	new value is set both in the instance of Event, and in			*
     *	the other record.												*
     *																	*
     *  Parameters:														*
     *	    value		this can be:									*
     *					1) the unique numeric identifier of a location	*
     *					2) the string text of a location				*
     *					3) an instance of Location						*
     *					4) an instance of Temple						*
     *																	*
     *	Returns:														*
     *	    Former value of numeric identifier IDLR						*
     ********************************************************************/
    function setLocation($value)
    {
		if (is_string($value) && ctype_digit($value))
		    $value	    = (int)$value;

		if (is_null($value) || strlen($value) == 0)
            return parent::set('idlrevent', null);
		else
		if (is_int($value))
		{		// idlr value
		    if ($value < 1)
				$value	= 1;
		    $idlr	    = $value;
		}		// idlr value
		else
		if (is_string($value))
		{		// location text
		    $loc	    = new Location(array('location' => $value));
		    $loc->save(false);		// in case its a new location
		    $idlr	    = $loc->getIdlr();
		}		// location text
		else
		if ($value instanceof Location)
		{		// location object
		    $loc	    = $value;
		    $loc->save(false);		// in case its a new location
		    $idlr	    = $loc->getIdlr();
		}		// location text
		else
		if ($value instanceof Temple)
		{		// temple object
		    $loc	    = $value;
		    $idlr	    = $loc->getIdtr();
		    parent::set('kind', 1);
		}		// location text
		else
		    throw new Exception("Event.inc: " .
				"setLocation: invalid parameter type " . gettype($value));

		return parent::set('idlrevent', $idlr);
    }		// Event::setLocation

    /********************************************************************
     *  function Event::getLocationKind									*
     *																	*
     *	Get the type of the location of the event.						*
     *																	*
     *	Returns:														*
     *	    0				if the location is an instance of Location	*
     *	    1				if the location is an instance of Temple	*
     ********************************************************************/
    function getLocationKind()
    {
		if (array_key_exists('kind', $this->row))
		    return $this->row['kind'];
		else
		    return 0;		// instance of Location
    }		// Event::getLocationKind

    /********************************************************************
     *  function Event::setLocationKind									*
     *																	*
     *	Set the type of the location of the event.						*
     *	This does nothing if the event type does not support Temples.	*
     *																	*
     *  Parameters:														*
     *	    value		this can be:									*
     *			0		if the location is an instance of Location		*
     *			1		if the location is an instance of Temple		*
     *																	*
     *	Returns:														*
     *	    former value of location kind or 0 if the event has no kind	*
     ********************************************************************/
    function setLocationKind($value)
    {
		if (array_key_exists('kind', $this->row))
		    return parent::set('kind', $value);
		return 0;
    }		// Event::setLocationKind

    /********************************************************************
     *  function Event::getDate											*
     *																	*
     *	Get the date of the event in external string format.			*
     *																	*
     *  Parameters:														*
     *	    privlim		the latest year that may be displayed.  Dates	*
     *					after this year are displayed as "Private".		*
     *					Defaults to no privacy limit.					*
     *	    tranTab		translation table for internationalization		*
     *																	*
     *	Returns:														*
     *	    Current date as a human readable string. 					*
     ********************************************************************/
    function getDate($privlim = 9999, $tranTab	= null)
    {
		$date	= new LegacyDate($this->row['eventd']);
		return $date->toString($privlim, false, $tranTab);
    }		// Event::getDate

    /********************************************************************
     *  function Event::setDate											*
     *																	*
     *	Set the date of the event.										*
     *	For events that are actually recorded in another record the		*
     *	new values are set both in the instance of Event, and in		*
     *	the other record.												*
     *																	*
     *	Parameters:														*
     *	    $value		new date as instance of LegacyDate or a string	*
     *	    $local		if true only set fields in this object			*
     *					if false also set fields in associated record	*
     *																	*
     *	Returns:														*
     *	    Former date in encoded form									*
     ********************************************************************/
    function setDate($value,
				     $local = false)
    {
		global	$debug;
		global	$warn;

		if ($value instanceof LegacyDate)
		{			// parameter is an instance of LegacyDate
            if ($debug)
                $warn   .= "<p>Event::setDate(LegacyDate('" .
                            $value->toString() . "'))</p>\n";
		    $date	    = $value;
		}			// parameter is an instance of LegacyDate
		else
		{			// convert the string to instance of LegacyDate
		    $date	    = new LegacyDate($value);
        }			// convert the string to instance of LegacyDate

		// handle events that are actually recorded in another record
		$preferred  	= $this->row['preferred'];
		if ($preferred && !$local)
		{			// preferred instance of event
		    $ider		= $this->row['ider'];
		    $idet		= $this->row['idet'];
		    $idtype		= $this->row['idtype'];
		    if ($idtype == self::IDTYPE_INDIV)
		    {			// store into appropriate fields of Person
				$indiv	= $this->getAssociatedRecord();

				// values of date are updated in Person to support
				// sorting by birth and death date without joining tables
				switch ($idet)
				{		// act on specific event types
				    case self::ET_BIRTH:
				    {		// birth date
						$indiv->set('birthsd', $date->getSortDate());
						break;
				    }		// birth date

				    case self::ET_DEATH:
				    {		// death date
						$indiv->set('deathsd', $date->getSortDate());
						break;
				    }		// death date
				}		// act on specific event types
		    }			// store into appropriate fields of Person
		    else
		    if ($idtype == self::IDTYPE_MAR)
		    {			// store into appropriate fields of Family
				$family	= $this->getAssociatedRecord();

				// values of IDET for simulated events that are actually
				// recorded in Family
				switch ($idet)
				{		// act on specific event types
				    case self::ET_MARRIAGE:
				    {		// marriage date
						$family->set('mard', $date);
						break;
				    }		// marriage date

				    case self::ET_MARRIAGE_END:
				    {		// marriage end date
						$family->set('marendd', $date);
						break;
				    }		// marriage end date

				}		// act on specific event types
		    }			// store into appropriate fields of Family
		}			// preferred instance of event

		parent::set('eventsd', $date->getSortDate());
        return parent::set('eventd', $date->getDate());
    }		// Event::setDate

    /********************************************************************
     *  function Event::compare											*
     *																	*
     *	Compare two events												*
     *																	*
     *	Input:															*
     *	    $other				another instance of Event				*
     *																	*
     *	Returns:														*
     *	    > 0 if $other is greater than $this by date (in order)		*
     *	    == 0 if $other is equal to $this by date					*
     *	    < 0 if $other is less than $this by date (out of order)		*
     ********************************************************************/
    function compare($other)
    {
		if ($other instanceof Event)
		{			// comparing two events
		    if ($this->row['idet'] == Event::ET_BIRTH)
                $diff           = -99999999;
            else
                $diff   = $this->row['eventsd'] - $other->row['eventsd'];
		    return $diff;
		}			// comparing two events
		else
		    throw new Exception("Event: compare: " .
							"parameter is not instance of Event");
    }		// Event::compare

    /********************************************************************
     *  function Event::getSentenceOverride								*
     *																	*
     *	Get the sentence override text of the event.					*
     *																	*
     *	Returns:														*
     *	    Current sentence override text as a string.					*
     ********************************************************************/
    function getSentenceOverride()
    {
		return $this->row['sentenceoverride'];
    }		// Event::getSentenceOverride

    /********************************************************************
     *  function Event::setSentenceOverride								*
     *																	*
     *	Set the sentence override of the event.							*
     *																	*
     *	Returns:														*
     *	    Former sentence override text as a string.					*
     ********************************************************************/
    function setSentenceOverride($value)
    {
		return parent::set('sentenceoverride', $value);
    }		// Event::setSentenceOverride

    /********************************************************************
     *  function Event::__toString										*
     *																	*
     *	Get a textual representation of the event.						*
     *																	*
     *	Returns:														*
     *	    Summary of event as a string.								*
     ********************************************************************/
    function __toString()
    {
		return 	self::$eventText[$this->row['idet']] . ' ' .
				$this->getDate() . ' ' .
				$this->getDesc() . ', ' .
				$this->getDescription() . ', at ' .
				$this->getLocation();
    }		// Event::__toString

    /********************************************************************
     *  function Event::toXml											*
     *																	*
     *  Construct and optionally print an XML representation of the		*
     *	record.															*
     *																	*
     *  Most fields in the record are represented by a child element	*
     *	with the name of the field containing the internal value as text*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two elements.  The internal encoded string value appears in an	*
     *	element with 'c' appended to the field name, while the element	*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *  Each field whose value is an instance of Location appears		*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idlr" prefix removed and a "loc" suffix added.	*
     *	For example the element <idlrbirth> contains the original		*
     *	numeric code while the element <birthloc> contains the external	*
     *	textual value.													*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'event'			*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 3rd parameter						*
     ********************************************************************/
    const	TOXML_INCLUDE_CITATIONS	= 1;

    function toXml($top		= 'event',
				   $print	= true,
				   $options	= 0)
    {
        if (is_array($print))
        {		        // additional parameters to include
            $print		        = true;
            foreach($parms as $field => $value)
            {
                if ($field == 'print')
                    $print	    = $value;
            }
        }

		$includeCitations	= $options & self::TOXML_INCLUDE_CITATIONS;

        $ider		= $this->row['ider'];
        if ($ider == 0)
		    $retval		= "<$top ider='" . self::$eventText[$this['idet']] . "'>\n";
        else
		    $retval		= "<$top ider='$ider'>\n";
		$idtype		= $this->row['idtype'];
		if ($idtype == self::IDTYPE_INDIV)
		{		// individual event
		    $idir	= $this->row['idir'];
		    $isOwner	= RecOwner::chkOwner($idir, 'tblIR');
		}		// individual event
		else
		    $isOwner	= true;

		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'eventd':
				{		// legacy internal date format
				    $date	= new LegacyDate($value);
				    if ($isOwner)
				    {		// can see private data
						$retval	.= "    <{$fld}c>" . htmlentities($value,ENT_XML1) .
							"</{$fld}c>\n";
						$value	= $date->toString();
						$retval	.= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
				    }		// can see private data
				    else
				    {		// cannot see private data
						$value	= $date->toString(1933);
						$retval	.= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
				    }		// can see private data
				    break;
				}		// legacy internal date format

				case 'eventsd':
				{		// legacy internal sort date
				    if ($isOwner)
				    {		// can see private data
						$retval	.= "    <$fld>" . $value . "</$fld>\n";
				    }		// can see private data
				    break;
				}		// legacy internal sort date

				case 'idlrevent':
				{
				    $retval	        .= "    <$fld>" . $value . "</$fld>\n";
				    $loc	        = Location::getLocation($value);
				    $value	        = $loc->getName();
				    $locfld	        = substr($fld,4) . 'loc';
                    $retval	        .= "    <$locfld>" . htmlentities($value,ENT_XML1) . 
                                            "\n    </$locfld>\n";
				    break;
				}

				case 'idet':
				{		// event type code
				    $retval	.= "    <idet>" . $value . "</idet>\n";
				    if (array_key_exists($value, self::$eventText))
				    {
						$retval	.= "    <eventtype>" .
							htmlentities(self::$eventText[$value],ENT_XML1) .
							"</eventtype>\n";
				    }		// can interpret the event type
				    break;
				}		// event type code

				case 'idtype':
				{		// associated record type
				    if ($idtype == self::IDTYPE_INDIV)
						$retval	.= "    <$fld>$value=Person</$fld>\n";
				    else
				    if ($idtype == self::IDTYPE_MAR)
						$retval	.= "    <$fld>$value=Family</$fld>\n";
				    else
				    if ($idtype == self::IDTYPE_CHILD)
						$retval	.= "    <$fld>$value=Child</$fld>\n";
				    else
						$retval	.= "    <$fld>$value</$fld>\n";
				    break;
				}		// event type code

				case 'eventexclude':
				case 'qstag':
				case 'rgexclude':
				{		// yes/no flags
				    if ($value > 0)
						$retval	.= "    <$fld>Y</$fld>\n";
				    else
						$retval	.= "    <$fld>N</$fld>\n";
				    break;
				}		// yes/no flags

				default:
				{		// fields passed back with no interpretation
				    $retval	.= "    <$fld>" . htmlentities($value,ENT_XML1) . "</$fld>\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

		// display citations
		if ($includeCitations)
		{		// include citations in the output
		    $citations		= $this->getCitations();
		    if (count($citations) > 0)
		    {		// found at least one citation record
				$retval	.= "<citations>\n";

				foreach($citations as $idsx => $cit)
				{		// loop through all citation records
				    $retval	.= $cit->toXml('citation', false, 0);
				}		// loop through all citation records

				$retval	.= "</citations>\n";
		    }		// found at least one citation record
		}		// include citations in the output

		$retval	.= "</$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// Event::toXml

    /********************************************************************
     *  function Event::toJson											*
     *																	*
     *  Construct and optionally print an JSON representation of the	*
     *	record.															*
     *																	*
     *  Most fields in the record are represented by a child element	*
     *	with the name of the field containing the internal value as text*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two elements.  The internal encoded string value appears in an	*
     *	element with 'c' appended to the field name, while the element	*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *  Each field whose value is an instance of Location appears		*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idlr" prefix removed and a "loc" suffix added.	*
     *	For example the element <idlrbirth> contains the original		*
     *	numeric code while the element <birthloc> contains the external	*
     *	textual value.													*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *	Returns:														*
     *	    JSON representation of the object							*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 2nd parameter						*
     ********************************************************************/
    const	TOJSON_INCLUDE_CITATIONS	= 1;

    function toJson($print	    = true,
				    $options	= 0)
    {
        if (is_array($print))
        {		        // additional parameters to include
            $print		        = true;
            foreach($parms as $field => $value)
            {
                if ($field == 'print')
                    $print	    = $value;
            }
        }

		$includeCitations	= $options & self::TOJSON_INCLUDE_CITATIONS;

		$ider		        = $this->row['ider'];
		$retval		        = '';
		$comma		        = "{\n";
		$idtype		        = $this->row['idtype'];
		if ($idtype == self::IDTYPE_INDIV)
		{		// individual event
		    $idir	        = $this->row['idir'];
		    $isOwner	    = RecOwner::chkOwner($idir, 'tblIR');
		}		// individual event
		else
		    $isOwner	    = true;

		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'ider':
                {		// fields passed back with no interpretation
                    $ider       = $value;
                    $retval	    .= "$comma\t\"$fld\":\t$value";
				    $comma	    = ",\n";
				    break;
                }		// fields passed back with no interpretation

				case 'idir':
				case 'order':
				case 'idar':
                {		// fields passed back with no interpretation
                    $retval	    .= "$comma\t\"$fld\":\t$value";
				    $comma	    = ",\n";
				    break;
                }		// fields passed back with no interpretation

				case 'eventd':
				{		// legacy internal date format
				    $date	    = new LegacyDate($value);
				    if ($isOwner)
				    {		// can see private data
						$retval	.= $comma . "\t\"{$fld}c\":\t" .
							       json_encode($value);
						$comma	= ",\n";
						$value	= $date->toString();
						$retval	.= $comma . "\t\"$fld\":\t" .
							       json_encode($value);
				    }		// can see private data
				    else
				    {		// cannot see private data
						$value	= $date->toString(1933);
                        $retval	.= "$comma\t\"$fld\":\t" . json_encode($value);
						$comma	= ",\n";
				    }		// can see private data
				    break;
				}		// legacy internal date format

				case 'eventsd':
				{		// legacy internal sort date
				    if ($isOwner)
				    {		// can see private data
						$retval	.= "$comma\t\"$fld\":\t\"$value\"";
						$comma	= ",\n";
				    }		// can see private data
				    break;
				}		// legacy internal sort date

				case 'idlrevent':
                {
                    if (is_null($value))
                        $value          = 1;
				    $retval	            .= "$comma\t\"$fld\":\t$value";
                    $comma	            = ",\n";
                    if ($this->row['kind'])
				        $loc	        = new Temple(array('idtr' => $value));
                    else
				        $loc	        = Location::getLocation($value);
				    $value	            = $loc->getName();
				    $locfld	            = substr($fld,4) . 'loc';
				    $retval	            .= "$comma\t\"$locfld\":\t\"$value\"";
				    break;
				}

				case 'idet':
				{		// event type code
				    $retval	.= "$comma\t\"$fld\":\t$value";
				    $comma	= ",\n";
				    if (array_key_exists($value, self::$eventText))
				    {
						$retval	.= $comma . "\t\"eventtype\":\t" .
							   json_encode(self::$eventText[$value]);
				    }		// can interpret the event type
				    break;
				}		// event type code

				case 'idtype':
				{		// associated record type
				    $retval	.= "$comma\t\"$fld\":\t$value";
				    $comma	= ",\n";
				    $retval	.= $comma . "\t\"idtypemeans\":\t";
				    if ($idtype == self::IDTYPE_INDIV)
						$retval	.= "\"Person\"";
				    else
				    if ($idtype == self::IDTYPE_MAR)
						$retval	.= "\"Family\"";
				    else
				    if ($idtype == self::IDTYPE_CHILD)
                        $retval	.= "\"Child\"";
                    else
                        $retval	.= "\"Undefined $value\"";
				    break;
				}		// event type code

				case 'kind':
				{		// associated record type
				    $retval	        .= "$comma\t\"$fld\":\t$value";
				    $comma	        = ",\n";
				    $retval	        .= $comma . "\t\"kindmeans\":\t";
				    if ($value == 0)
						$retval	    .= "\"Location\"";
				    else
						$retval	    .= "\"LDS Temple\"";
				    break;
                }		// event type code

				case 'eventexclude':
				case 'qstag':
				case 'rgexclude':
				{		// yes/no flags
				    if ($value > 0)
						$retval	.= $comma . "\t\"$fld\":\t" .
							   "\"Y\"";
				    else
						$retval	.= $comma . "\t\"$fld\":\t" .
							   "\"N\"";
				    $comma	= ",\n";
				    break;
				}		// yes/no flags

				default:
				{		// fields passed back with no interpretation
				    $retval	.= $comma . "\t\"$fld\":\t" .
							   json_encode($value);
				    $comma	= ",\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

		// display citations
		if ($includeCitations)
		{		// include citations in the output
		    $citations		= $this->getCitations();
		    if (count($citations) > 0)
		    {		// found at least one citation record
				$retval	.= $comma . "\t\"citations\":\t{";
				$comma	= "\n";
				foreach($citations as $idsx => $cit)
				{		// loop through all citation records
				    $retval	.= $comma . "\t\"" . $cit->getId() . "\":\t";
				    $retval	.= $cit->toJson(false,
								    Citation::TOXML_INCLUDE_SOURCE);
				    $comma	= ",\n";
				}		// loop through all citation records
				$retval	.= "\n}\n";

		    }		// found at least one citation record
		}		// include citations in the output

		// close off the JSON document
		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";

		if ($print)
		    print $retval;
		return $retval;
	}		// function Event::toJson
 
    /********************************************************************
     *  function Event::get												*
     *																	*
     *	Get the value of a field in the object.						    *
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *																	*
     *	Returns:														*
     *	    value of field										        *
     ********************************************************************/
    function get($field)
    {
        global  $warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
        {                       // act on specific fields
			case 'desc':
            {
        		$notes	        = $this->row['desc'];
        		if ($notes === null)
        		    return '';
        		else
        		    return $notes;
            }		// Notes

			case 'description':
            {
        		$text	        = $this->row['description'];
        		if ($text === null)
        		    return '';
        		else
        		    return $text;
            }

			case 'kind':
            {
        		if (array_key_exists('kind', $this->row))
        		    return $this->row['kind'];
        		else
        		    return 0;		// instance of Location
            }		// Kind

		    default:
		    {		// no validation implemented yet
				return parent::get($field);
		    }		// no validation implemented yet
        }               // act on specific fields
	}		// function Event::get

    /********************************************************************
     *  function Event::set												*
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
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    private $setcount   = 20;
    function set($field, $value)
    {
        global  $warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
        if ($this->setcount < 1)
            exit;
        
		switch($fieldLc)
		{               // act on specific field
		    case 'idtype':
		    {		    // type of associated record
				$idtype			= $value;
				if ($idtype == self::IDTYPE_INDIV ||
				    $idtype == self::IDTYPE_MAR ||
				    $idtype == self::IDTYPE_CHILD)
				    return parent::set('idtype', $value);
				else
				$warn	.= "<p>Event::set: ".
						    "Bad value $value for '$field'</p>\n";
				break;
		    }		    // type of associated record

		    case 'eventd':
		    {		    // legacy internal date format
				return $this->setDate($value);
		    }		    // legacy internal date format

		    case 'idlrevent':
		    {		    // value is IDLR
				return $this->setLocation($value);
		    }		    // value is IDLR

		    case 'idar':
		    {		    // value is IDAR
		        if ($value instanceof Address && $value->isExisting())
                    return parent::set('idar', $value->get('idar'));
                else
				if (is_int($value) || ctype_digit($value))
				    return parent::set('idar', $value);
				else
				if (is_null($value) || strlen($value) == 0)
                    return parent::set('idar', null);
                else
				    $warn   .= "<p>Event::set: " .
							"invalid value '$value' for field '$field'</p>\n";
		    }		    // value is IDAR

		    case 'eventsd':
		    {		    // legacy internal date format
				// do not change the value, it is set by set('eventd')
				if ($value < -99990000 || $value > 99990000)
				    return parent::set('eventsd', $value);
				return $this->row['eventsd'];
		    }		    // legacy internal date format

		    default:
		    {		    // no validation implemented yet
				return parent::set($field, $value);
		    }		    // no validation implemented yet
		}		        // switch on field name
    }		// Event::set

    function adjustSortDate($maxSortDate)
    {
        if ($this->row['eventsd'] >= $maxSortDate)
        {
            return parent::set('eventsd', $maxSortDate);
        }
        else
            return $this->row['eventsd'];
    }       // function Event::adjustSortDate

    /********************************************************************
     *  function Event::dump											*
     *																	*
     *  Display a diagnostic dump of the	                            *
     *  contents of the database record in the web page.				*
     *																	*
     *  Input:															*
     *		$label		descriptive identifier for record				*
     *		$force		if true add to $warn even if $debug false		*
     *		            This is deprecated,                             * 
     *		            use $warn   .= $event->dump();                  *
     *																	*
     *	Return:															*
     *	    string containing trace output as HTML						*
     ********************************************************************/
    function dump($label	= null, 
				  $force	= false)
    {
		global	$debug;
		global	$warn;

		// ensure that there is a label to use for the display
		if (!is_string($label) || strlen($label) == 0)
		    $label	    = $this->getName();

	    $output	        = "<p class='label'>\n$label</p>\n";
	    $output	        .= "<table border='1'>\n";
	    foreach($this->row as $key => $value)
	    {
			$output	.= "<tr>\n" .
						"<th class='label'>\n$key\n" .
						"</th>\n" .
						"<td class='dataleft'>\n$value\n";

			if (is_object($value) || strlen($value) > 0)
			switch($key)
			{
			    case 'idir':
			    {
					if ($this->associatedRecord)
					    $output	.= ' = ' .
						get_class($this->associatedRecord);
	//$this->associatedRecord->getName(Person::NAME_INCLUDE_DATES);
					break;
			    }		// dates

			    case 'idtype':
			    {
					switch($value)
					{
					    case self::IDTYPE_INDIV:	// Person
						$output		.= ' = IDIR';
						break;

					    case self::IDTYPE_MAR:	// Family
						$output		.= ' = IDMR';
						break;

					    case self::IDTYPE_CHILD:	// Child
						$output		.= ' = IDCR';
						break;

					}
					break;
			    }

			    case 'eventd':
			    {
					$olddebug	= $debug;
					$debug		= false;
					$date		= new LegacyDate($value);
					$output		.= ' = ' . $date->toString();
					$debug		= $olddebug;
					break;
			    }		// dates

			    case 'idlrevent':
			    {
					$olddebug	= $debug;
					$debug		= false;
					$loc	    = Location::getLocation($value);
					$output	    .= ' = ' . $loc->toString();
					$debug		= $olddebug;
					break;
			    }		// locations

			    case 'idet':
			    {
					$output		.= ' = ' . self::$eventText[$value];
					break;
			    }		// locations

			    default:
			    {
					break;
			    }
			}	// field specific formatting
			$output	.= "</td>\n";
			$output	.= "</tr>\n";
	    }	// loop through keys
        $output	.= "</table>\n";

		if ($debug || $force)
		    $warn       .= $output;

		return $output;
    }		// Event::dump

    /********************************************************************
     *  function Event::save											*
     *																	*
     *  Save changes made to the Event record into the					*
     *  database.														*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    1           if the record was inserted or updated           *
     *	    0           if the record did not need to be updated        *
     *	    false       if the record could not be updated              *
     ********************************************************************/
    function save($xml)
    {
		global	$connection;
		global	$debug;
        global	$warn;
        global  $document_root;

		// the associated record is identified appropriately to make
		// the code easy to understand
		$indiv		= $this->associatedRecord;
		$idir		= intval($this->row['idir']);
		$idtype		= intval($this->row['idtype']);
		$idet		= intval($this->row['idet']);
		$preferred	= $this->row['preferred'];
		if ($idet == Event::ET_BIRTH)
		{
		    $birthsd	= $this->row['eventsd'];
        }
        if ($indiv)
            $indivset   = 'Indiv is set';
        else
            $indivset   = 'Indiv is not set';
        if ($preferred)
            $preftext   = "preferred instance";
        else
            $preftext   = '';

		// do not create a new event record if it has no contents
		if ($this->row['eventd'] == '' &&
		    $this->row['idlrevent'] == 1 &&
		    $this->row['description'] == '')
		    return;

		// migrate events from associated record to Event record
		if ($this->needInsert && $preferred)
		{		// new preferred event
		    if ($debug)
				$warn	.= "<p>Insert preferred event: " .
						   "idir=$idir, idet=$idet</p>\n";

		    // ensure that event information is removed from old location
		    if ($indiv)
		    {		// have old base record
				switch($idet)
				{	// act on specific event types
				    case Event::ET_BIRTH:
				    {
						$birthsd	= $this->row['eventsd'];
						$indiv->clearEvent('birth');
						// restore birthsd which is used for sorting
						$indiv->set('birthsd',
								 $birthsd);
						break;
				    }

				    case Event::ET_BURIAL:
				    {
						$indiv->clearEvent('buried');
						break;
				    }

				    case Event::ET_CHRISTENING:
				    {
						$indiv->clearEvent('chris');
						break;
				    }

				    case Event::ET_DEATH:
				    {
						$indiv->clearEvent('death');
						if ($this->row['description'] != '')
						{
						    $warn	.= "<p>Event::save: " .
							"Description='" .
							htmlentities($this->row['description'],ENT_XML1) .
							"' when saving death event</p>\n";
						    $this->row['description']	= '';
						}
						break;
				    }

				    case Event::ET_LDS_BAPTISM:
				    {
						$indiv->clearEvent('baptism');
						break;
				    }

				    case Event::ET_LDS_CONFIRMATION:
				    {
						$indiv->clearEvent('confirmation');
						break;
				    }

				    case Event::ET_LDS_ENDOWED:
				    {
						$indiv->clearEvent('endow');
						break;
				    }

				    case Event::ET_LDS_INITIATORY:
				    {
						$indiv->clearEvent('initiatory');
						break;
				    }

				    case Event::ET_MARRIAGE:
				    {
						$family		= $indiv;
						$family->clearEvent('mar');
						break;
				    }

				    case Event::ET_LDS_SEALED:
				    {
						$childr		= $indiv;
						$childr->clearEvent('parseal');
						break;
				    }

				    case Event::ET_MARRIAGE_END:
				    {
						$family		= $indiv;
						$family->clearEvent('marend');
						break;
				    }

				    case 0:
				    case Event::ET_NULL:
				    {
						throw new Exception("Event::save: NULL event: " .
							    print_r($this->row, true));
				    }
				}	// act on specific event types
		    }		// have associated record

		    // ensure only one preferred event for type
		    $query	        = 'SELECT COUNT(*) FROM tblER ' .
				        		  "WHERE IDIR=$idir AND IDType=$idtype AND " .
				        			"IDET=$idet AND Preferred=1";
		    $sqlParms	    = array('idir'		=> $idir,
					        		'idtype'	=> $idtype,
					        		'idet'		=> $idet);
		    $stmt	        = $connection->prepare($query);
		    $queryText	    = debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				if ($debug)
				    $warn	.= "<p>Event::save: " . __LINE__ .
								" '$queryText'</p>\n";

				// have a result set, fetch row
				$row 		= $stmt->fetch(PDO::FETCH_NUM);
				$count		= $row[0];
				if ($count > 0)
				{
				    $warn	.= "<p>Event::save: " . __LINE__ .
						" Attempt to insert more than one preferred event for" .
						" IDIR=$idir AND IDType=$idtype AND IDET=$idet</p>\n" ;
				    $this->row['preferred']		= 0;
				}
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("Event::save: '$queryText': " .
							    print_r($stmt->errorInfo(), true));
		    }		// error performing query
		}		// new preferred event

		// for some events information is also stored in the
		// associated record and in tblNX to save a JOIN
		if ($preferred &&
		    $idet == Event::ET_BIRTH)
		{		// update associated record
		    if (!$this->recordSetByConstructor)
		    {
				if ($debug)
				    $warn	.= "<p>\$indiv->save($xml)</p>\n";
				$indiv->save($xml);
		    }

		    // also update name index entries
		    $idir		= $this->row['idir'];
		    if ($debug)
				$warn	.= "<p>Event.inc: " . __LINE__ . " new RecordSet('Names', array('idir'	=> $idir));</p>\n" .
				"<p>\$names->update(array('birthsd'	=> $birthsd))</p>\n";
		    $names	= new RecordSet('Names', array('idir'	=> $idir));
		    $names->update(array('birthsd'	=> $birthsd));
		}		// update associated records

		// invoke parent's implementation
		$oldider	= intval($this->row['ider']);
		if ($this->row['idet'] <= 1)
		    throw new Exception("Event::save: NULL event: " .
							print_r($this->row, true));
		parent::save($xml);
		if ($debug && $oldider == 0)
		    $warn	.= "<p>new event IDER=" . $this->row['ider'] . "</p>\n";

		// also apply any changes made to migrate citations
		$citations		= $this->getCitations();
		$ider			= $this->row['ider'];
		$preferred		= $this->row['preferred'];
		foreach($citations as $idsx => $citation)
		{			// loop through all citations
		    if ($debug)
				$warn	.= "<p>move citation IDSX=$idsx to IDIME=$ider</p>\n";
		    $citation->set('idime', $ider);
		    $citation->save($xml);
		}			// loop through all citations

    }		// Event::save

    /********************************************************************
     *  function Event::getCitations									*
     *																	*
     *  Get a RecordSet of the citations documenting this event.		*
     *																	*
     *  Returns:														*
     *		RecordSet of Citations.  Use:								*
     *			$citations		= $event->getCitations();				*
     *			foreach($citations as $idsx => $citation) ...			*
     ********************************************************************/
    function getCitations()
    {
		global	$debug;
		global	$warn;

		if ($debug)
		    $warn	.= "<p>Event::getCitations()</p>\n";
		// previously constructed
		if ($this->citations)
		{
		    if ($debug)
		    {
		    if (is_array($citations))
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " array count=" . count($citations) . "</p>\n";
		    else
		    if ($this instanceof RecordSet)
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " RecordSet count=" . count($citations) . ", query='" . $citations->getInformation()['query']. "'</p>\n";
		    else
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " " . print_r($citations, true) . " </p>\n";
		    }
		    return $this->citations;
		}

		// construct the query of the citation table
		$ider		= $this->row['ider'];
		$idtype		= $this->row['idtype'];
		if ($idtype == Event::IDTYPE_INDIV)
		    $stype	= Citation::STYPE_EVENT;
		else
		if ($idtype == Event::IDTYPE_MAR)
		    $stype	= Citation::STYPE_MAREVENT;
		else
		if ($idtype == Event::IDTYPE_CHILD)
		    $stype	= Citation::STYPE_LDSP;
		$idet		= $this->row['idet'];
		$idir		= $this->row['idir'];
		$preferred	= $this->row['preferred'];

		// for preferred events the event may be in the process of being
		// migrated to tblER, and so the citations may still refer to the
		// original location
		if ($preferred)
		{			// preferred events
		    switch($idet)
		    {			// act on specific event types
				case Event::ET_BIRTH:
				{
				    $stype	    = Citation::STYPE_BIRTH;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_BURIAL:
				{
				    $stype	    = Citation::STYPE_BURIED;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_CHRISTENING:
				{
				    $stype	    = Citation::STYPE_CHRISTEN;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_DEATH:
				{
				    $stype	    = Citation::STYPE_DEATH;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_LDS_BAPTISM:
				{
				    $stype	    = Citation::STYPE_LDSB;	// Baptism
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_LDS_CONFIRMATION:
				{
				    $stype	    = Citation::STYPE_LDSC;	// Confirmation
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_LDS_ENDOWED:
				{
				    $stype	    = Citation::STYPE_LDSE;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_LDS_INITIATORY:
				{
				    $stype	    = Citation::STYPE_LDSI;
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

				case Event::ET_MARRIAGE:
				{
				    $stype	    = Citation::STYPE_MAR;
				    $newstype	= Citation::STYPE_MAREVENT;
				    break;
				}

				case Event::ET_LDS_SEALED:
				{
				    $stype	    = Citation::STYPE_LDSS;
				    $newstype	= Citation::STYPE_MAREVENT;
				    break;
				}

				case Event::ET_MARRIAGE_END:
				{
				    $stype	    = Citation::STYPE_MAREND;
				    $newstype	= Citation::STYPE_MAREVENT;
				    break;
				}

				default:
				{
				    $newstype	= Citation::STYPE_EVENT;
				    break;
				}

		    }			// act on specific event types

		    // get the old style citations associated with this event
		    $parms		= array('idime'		=> $idir,
								'type'		=> $stype);
		    // set of citations to be moved
		    $mcitations		= new RecordSet('Citations', $parms);

		    // migrate
		    if ($ider > 0)
		    {			// have a real event, move the citations
				foreach($mcitations as $idsx => $citation)
				{			// loop through all citations
				    $citation->set('idime', $ider);
				    $citation->set('type', $newstype);
				}			// loop through all citations
		    }			// have a real event, move the citations

		}			// preferred events

		if ($ider > 0)
		{			// have an actual record in tblER
		    if ($idtype == Event::IDTYPE_INDIV)
				$stype	= Citation::STYPE_EVENT;
		    else
		    if ($idtype == Event::IDTYPE_MAR)
				$stype	= Citation::STYPE_MAREVENT;
		    else
				$stype	= Citation::STYPE_LDSP;

		    // get the citations associated with this event
		    $parms	= array('idime'	=> $ider,
							'type'	=> $stype,
							'order'	=> '`Order`,`EnteredSD`,`IDSX`');

		    $citations	= new RecordSet('Citations', $parms);
		    if ($debug)
		    {
				if (is_array($citations))
				    $warn	.= "<p>Event::getCitations: " . __LINE__ . " array count=" . count($citations) . "</p>\n";
				else
				    $warn	.= "<p>Event::getCitations: " . __LINE__ . " RecordSet count=" . count($citations) . ", query='" . $citations->getInformation()['query']. "'</p>\n";
		    }
		    // add citations from merged event
		    if (isset($mcitations))
				foreach($mcitations as $idsx => $citation)
				    $citations[$idsx]	= $citation;
		}			// have an actual record in tblER
		else
		if (isset($mcitations))
		    $citations		= $mcitations; 
		else
		    $citations		= new RecordSet('Citations', array());

		// reorder citations
		if (canUser('edit'))
		{			// authorized to edit database
		    $nextOrder	= 1;
		    if (isset($citations))
            {			// have citations
				foreach($citations as $idsx => $citation)
				{
				    $citation->set('order', $nextOrder);
				    $citation->save(false);
				    ++$nextOrder;
				}
		    }			// have citations
		}			// authorized to edit database

		if ($debug)
		{
		    if (is_array($citations))
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " return array count=" . count($citations) . "</p>\n";
		    else
		    if ($citations instanceof RecordSet)
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " return RecordSet count=" . count($citations) . ", query='" . $citations->getInformation()['query']. "'</p>\n";
		    else
				$warn	.= "<p>Event::getCitations: " . __LINE__ . " " . print_r($citations, true) . " </p>\n";
		}
		$this->citations	= $citations;
		return $citations;
    }		// Event::getCitations

    /********************************************************************
     *  function Event::addCitations									*
     *																	*
     *  Add a set of citations to this event.							*
     *																	*
     *	Input:															*
     *	    $newCitations	    instance of Citation or					*
     *							array of instances of Citation or		*
     *							RecordSet of Citations					*
     *  Returns:														*
     *	    updated RecordSet of citations. 							*
     ********************************************************************/
    function addCitations($newCitations)
    {
		global	$debug;
		global	$warn;

		// previously constructed
		if (is_null($this->citations))
		    $this->getCitations();
		$order		= count($this->citations);

		if ($newCitations instanceof RecordSet || is_array($newCitations))
		{
		    foreach($newCitations as $idsx => $citation)
		    {		// loop through citations
				$citation->set('idime', $this->getIder());
				$stype		= $citation->get('type');
				switch($stype)
				{
				    case Citation::STYPE_BIRTH:
				    case Citation::STYPE_BURIED:
				    case Citation::STYPE_CHRISTEN:
				    case Citation::STYPE_DEATH:
				    case Citation::STYPE_LDSB:	// Baptism
				    case Citation::STYPE_LDSC:	// Confirmation
				    case Citation::STYPE_LDSE:
				    case Citation::STYPE_LDSI:
						$citation->set('type',
							       Citation::STYPE_EVENT);
						break;

				    case Citation::STYPE_MAR:
				    case Citation::STYPE_LDSS:
				    case Citation::STYPE_MAREND:
						$citation->set('type',
							       Citation::STYPE_MAREVENT);
						break;
				}

				$citation->set('order', $order);
				$this->citations[$idsx]	= $citation;
				$order++;
		    }		// loop through citations
		}
		else
		if ($newCitations instanceof Citation)
		{		// single citation
		    $citation			= $newCitations;
		    $idsx			= $citation->getIdsx();
		    $citation->set('order', $order);
		    $this->citations[$idsx]	= $citation;
		}		// single citation
		else
		    throw new Exception("Event::addCitations: " .
							"Invalid Parameter " .
							print_r($newCitations, true));
    }		// Event::addCitations
}		// class Event
