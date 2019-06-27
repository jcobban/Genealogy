<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Family.inc															*
 *																		*
 *  Definition of a class representing a family (marriage) in a			*
 *  genealogical database.												*
 *  This class provides access to the information in a row of the		*
 *  table tblMR.														*
 *																		*
 *  The following is from the internal documentation of Legacy 7.4		*
 *  Copyright Millenium Corporation										*
 *																		*
 *		Table: tblMR - Marriage List, joins tw`o individual records		*
 *				      husband must be MALE, wife must be FEMALE			*
 *				      RECORD 0 will always exist.  Don't delete it		*
 *				      RECORD 0 field IDTRSeal holds the next IDMR to use*
 *				      when you add a new record and none are available	*
 *				      from tblXM										*
 *																		*
 *					  Note that the functionality of RECORD 0 field		*
 *					  IDTRSeal is replaced by:                          *
 *			'SELECT 1 + COALESCE((SELECT MAX(IDMR) FROM tblMR), 0)'     *
 *																		*
 *		Columns															*
 *		    Name						Type							*
 *		    ID							INTEGER(10) AUTOINCREMENT		*
 *				AutoIncrement non-changing field.  When we added		*
 *				Renumbering of RINs we had to add this field.  Add-on	*
 *				Programs can use this ID to store in their file.		*
 *				When they need to access a family in the Legacy family	*
 *				file they can use this number to read this family. 		*
 *				Then get IDMR below for finding related items within	*
 *				this family file.										*
 *		    IDMR						INTEGER(10)						*
 *				used by: tblIR.IDMRPref, tblIR.IDMRParents,				*
 *				tblCR.IDMR, tblER.IDIR, tblSX.IDIME, tblBR.IDIR,		*
 *				tblHL.IDMR, tblHB.IDMR, tblXM.DeletedID					*
 *		    IDIRHusb					INTEGER(10)						*
 *				--> tblIR.IDIR, ID to Person Record of Husband			*
 *		    HusbOrder					SMALLINT(5)						*
 *				sort order from point of view of husband				*
 *		    HusbPrefMar					TINYINT(1)						*
 *				1=preferred, 0=not										*
 *		    HusbSurname					VARCHAR(255)					*
 *				surname is stored here for speed (don't have to join	*
 *				tables). surname is also stored in [sic] Marriage		*
 *				record and in tblNR.Surname								*
 *				This field is no longer used.                           *
 *		    HusbGivenName				VARCHAR(255)					*
 *				This field is no longer used.                           *
 *		    HusbMarrSurname				VARCHAR(255)					*
 *				This field is no longer used.                           *
 *		    HusbBirthSD					INTEGER(10)						*
 *				(see Sort Date)											*
 *				This field is no longer used.                           *
 *		    IDIRWife					INTEGER(10)						*
 *				--> tblIR.IDIR, ID to Person Record of Wife				*
 *		    WifeOrder					SMALLINT(5)						*
 *				used as sort order.										*
 *		    WifePrefMar					TINYINT(1)						*
 *				1=preferred, 0=not										*
 *		    WifeSurname					VARCHAR(255)					*
 *				surname is stored here for speed (don't have to join	*
 *				tables). surname is also stored in [sic] Marriage		*
 *				record and in tblNR.Surname								*
 *				This field is no longer used.                           *
 *		    WifeGivenName				VARCHAR(255)					*
 *				This field is no longer used.                           *
 *		    WifeMarrSurname				VARCHAR(255)					*
 *				This field is no longer used.                           *
 *		    WifeBirthSD					INTEGER(10)						*
 *				(see Sort Date)											*
 *				This field is no longer used.                           *
 *		    MarriedNameRule				TINYINT(1)						*
 *				0=None - don't generate married names,					*
 *				1=Replace wife's surname with husband's surname			*
 *		    IDMS						INTEGER(10)						*
 *				--> tblMS.IDMS, ID to Marriage Status					*
 *		    MarSD						INTEGER(10)						*
 *				(see Sort Date)											*
 *		    MarD						VARCHAR(255)					*
 *				(see Date)												*
 *		    MarEndSD					INTEGER(10)						*
 *				(see Sort Date)											*
 *		    MarEndD						VARCHAR(255)					*
 *				(see Date)												*
 *		    IDLRMar						INTEGER(10)						*
 *				--> tblLR.IDLR, ID to Location Record					*
 *		    SealSD						INTEGER(10)						*
 *				(see Sort Date)											*
 *		    SealD						VARCHAR(255)					*
 *				(see Date)												*
 *		    IDTRSeal					INTEGER(10)						*
 *				--> tblTR.IDTR, ID to Temple Record						*
 *		    LDSS						TINYINT(1)						*
 *				1=marked for Seal to Spouse TempleReady submission		*
 *				0=not marked											*
 *		    TempleTag					TINYINT(1)						*
 *				temporary use for temple ready, 0=no, 1=yes				*
 *		    Tag1						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag2						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag3						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag4						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag5						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag6						TINYINT(1)						*
 *				0=no, 1=yesca											*
 *		    Tag7						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag8						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    Tag9						TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    TagGroup					TINYINT(1)						*
 *				temporary use, 0=no, 1=yes								*
 *		    SrchTag						TINYINT(1)						*
 *				temporary use, 0=no, 1=yes								*
 *		    qsTag						TINYINT(1)						*
 *				temporary use, 0=no, 1=yes								*
 *		    NoChildren					TINYINT(1)						*
 *				1=this couple had no children, 0=not set				*
 *		    NotMarried					TINYINT(1)						*
 *				1=true they were not married, 0=false					*
 *		    ReminderTag					TINYINT(1)						*
 *				0=no, 1=yes												*
 *		    AlreadyUsed					TINYINT(1)						*
 *				temporary use, 0=no, 1=yes								*
 *		    LTMP1						INTEGER(10)						*
 *				temporary use											*
 *		    LTMP2						INTEGER(10)						*
 *				temporary use											*
 *		    Notes						LONGTEXT						*
 *		    PPCheck						TINYINT(1)						*
 *				0=Potential Problems Exist,								*
 *				1=No Errors, it's been checked already					*
 *				  if no errors were printed then set to 1				*
 *				2=Marked to be excluded from checks						*
 *		    Added						INTEGER(10)						*
 *				date record was added									*
 *				20000216 = YYYYMMDD = 16 Feb 2000 						*
 *		    AddedTime					VARCHAR(5)						*
 *				time record was added.  01:30 = HH:MM					*
 *		    Updated						INTEGER(10)						*
 *				date record was modified/changed						*
 *				20010621 = YYYYMMDD = 21 June 2001 						*
 *		    UpdatedTime					VARCHAR(5)						*
 *				time record was added.  15:30 = HH:MM					*
 *		    IDAR						INTEGER(10)						*
 *				--> tblAR.IDAR, ID to Marriage Event Address Record		*
 *		    HPhrase						VARCHAR(255)					*
 *		    WPhrase						VARCHAR(255)					*
 *		    RPhrase						VARCHAR(255)					*
 *		    RPhrase2					VARCHAR(255)					*
 *		    MPhrase						VARCHAR(255)					*
 *		    SPhrase						VARCHAR(255)					*
 *		    UserRef						VARCHAR(255)					*
 *				User ID/Number, 										*
 *		    HusbWifeOver1				VARCHAR(255)					*
 *		    WifeHusbOver1				VARCHAR(255)					*
 *		    HusbWifeOver2				VARCHAR(255)					*
 *		    WifeHuwbOver2				VARCHAR(255)					*
 *		    SealNote					LONGTEXT						*
 *		    Private						TINYINT(1)						*
 *				0=No, 1=Yes												*
 *																		*
 *  History:															*
 *		2010/08/18		Add IDMR value to SQL log						*
 *		2010/10/11		check error on result not connection			*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/21		remove member idmr								*
 *		2010/10/23		connection made global							*
 *		2010/10/29		Do not try to detach child if not a member of	*
 *						this family										*
 *		2010/10/30		Add support for marriage status field			*
 *		2010/11/05		Handle record not found with dummy record		*
 *						so all methods work.							*
 *		2010/11/15		use LegacyDate::dateToString					*
 *		2010/12/20		throw an Exception to report create problems	*
 *						instead of returning an improper object			*
 *		2010/12/23		move deletion of database record here to new	*
 *						method delete().								*
 *						fix failure to delete tblCR records when tblMR 	*
 *						record deleted it 								*
 *						Also delete associated tblER records and their	*
 *						associated citations.							*
 *						Add methods getNumEvents and getNextEvent		*
 *		2011/01/01		Add set parameter to getNumChildren and			*
 *						getNumEvents									*
 *		2011/01/04		add setName method to adjust name of spouse		*
 *						when corresponding instance of LegacyFamily is	*
 *						updated											*
 *		2011/02/10		delete married name records when deleting		*
 *						family											*
 *		2011/06/12		add method toXml to interpret dates and			*
 *						locations and privatize dates.  Also include	*
 *						children, events, and citations in XML			*
 *						Interpret IDTR value for sealing				*
 *		2012/02/07		handle child record with bad IDIR gracefully in *
 *						getNextChild									*
 *		2012/01/12		allow null as first parameter of toXml			*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/01/24		fixup toXml for records with uninitialized 		*
 *						husbmarrsurname									*
 *		2012/05/29		change detachChild to use IDCR as key rather	*
 *						than IDIR										*
 *		2012/08/06		add option to constructor to defer database		*
 *						update											*
 *						add set method									*
 *						add dump method									*
 *		2012/08/16		ensure toXml identifies instance of LegacyChild	*
 *						associated with each child						*
 *		2012/10/02		use LegacyRecord::set to manipulate record		*
 *						fields so save method will correctly construct	*
 *						appropriate INSERT or UPDATE to synchronize the	*
 *						database record.								*
 *						use save method to perform update in setName	*
 *						method											*
 *		2012/10/14		add implementation specific safe method to		*
 *						manage update date and time stamp, calculate	*
 *						new IDMR value, and protect fields that are		*
 *						fixed after creation							*
 *		2012/11/04		the date fields MarD and SealD are strings but	*
 *						their most common values are all digits.  This	*
 *						misled the LegacyFamily::save method into not	*
 *						putting quotes around the value, and thereby	*
 *						lost significant leading zeroes					*
 *		2012/11/09		error in throwing exception for bad IDIRWife in	*
 *						method setName.									*
 *						add static method updateName to update names in	*
 *						applicable database records to correspond to a	*
 *						name change in an individual record				*
 *		2012/11/12		allow setting married surnames in set			*
 *		2012/11/17		include count of number of events in the		*
 *						<events> tag in XML output						*
 *		2012/11/22		change static LegacyFamily::add to use			*
 *						constructor('new'), set, and save				*
 *						add method addEvent								*
 *		2012/11/24		before saving record to database ensure that	*
 *						references to other table records are valid		*
 *						move functionality for most of method addEvent	*
 *						to Event constructor							*
 *		2012/11/27		static function add replaced by extended		*
 *						constructor call 								*
 *						eliminate use of '&&' in WHERE expressions to	*
 *						avoid need to escape them when generating XML	*
 *		2012/12/06		if idirhusb or idirwife do not point at a valid	*
 *						individual, create individual					*
 *		2013/01/20		change parameter to methods save and delete		*
 *		2013/01/24		do not clear names on new construct				*
 *		2013/02/14		update both husband's and wife's married name	*
 *						indexes											*
 *		2013/02/15		track deleted IDMRs in tblXM					*
 *		2013/03/02		manage children in the class in such a way as	*
 *						to postpone updating the database until the		*
 *						instance of LegacyFamily is saved				*
 *				`		new method LegacyFamily::getChildByIdir,		*
 *						formerly a static method of LegacyChild			*
 *						add fixup for empty husbmarsurname in method	*
 *						setName											*
 *		2013/03/08		return existing child if addChild called for	*
 *						existing child									*
 *		2013/03/23		add method getChildByIdcr						*
 *		2013/04/02		method setName extended to support clearing a	*
 *						spouse											*
 *		2013/04/03		properly escape SQL commands when output to XML	*
 *		2013/05/31		add method getEvents							*
 *						do not fail if asked to delete the same family	*
 *						twice											*
 *		2013/06/01		remove internal use of deprecated interfaces	*
 *		2013/07/01		fix bug in getNumChildren						*
 *		2013/08/09		base class renamed to Record					*
 *		2013/11/12		create method merge to merge 2 families into 1	*
 *		2013/11/15		correct date formats for added and updated		*
 *						log reproducible version of INSERT command		*
 *		2013/11/29		use IDMR as key for updates in stead of ID		*
 *		2014/03/14		calculate added and updated date and time in	*
 *						the script rather than in the SQL server		*
 *						method detachChild returns instance of			*
 *						LegacyChild instead of just IDCR				*
 *						remove deprecated methods: getNumChildren,		*
 *						getNextChild, getNumEvents, getNextEvent		*
 *						functionality of adding child moved to			*
 *						LegacyChild constructor							*
 *						method detachChild returned wrong instance		*
 *						use LegacyChild::getChildren					*
 *		2014/03/19		SQL syntax error if no events to delete			*
 *						no XML output for deletion of events			*
 *						add static method getFamilies					*
 *						add static method deleteFamilies				*
 *						use LegacyAltName::deleteAltNames				*
 *		2014/04/08		class LegacyAltName renamed to LegacyName		*
 *		2014/04/30		add method getName								*
 *		2014/06/18		change non-specific status value returned from	*
 *						method getStatus to empty string for easy id	*
 *		2014/08/07		use Citation::getCitations,						*
 *						Citation::deleteCitations, and					*
 *						Citation::updateCitations						*
 *		2014/09/02		LegacyFamily::save emitted some tags when		*
 *						$xml was false									*
 *		2014/09/26		share selection of records between getFamilies	*
 *						and deleteFamilies								*
 *						If no valid parameters are passed to either		*
 *						getFamilies or deleteFamilies do nothing		*
 *						do not include comparison of IDIR to zero in	*
 *						selection as it matches bad records				*
 *		2014/09/27		class RecOwners renamed to RecOwner				*
 *		2014/10/01		add method addOwner								*
 *		2014/11/13		add method getMarEvent							*
 *		2014/11/16		ensure changed made to MarEvent object are		*
 *						copied to main record on save					*
 *						Children may be added to a family before the	*
 *						family is saved.  Fixup the IDMR values on save	*
 *		2014/11/25		add option to LegacyFamily::getName to not		*
 *						include date of marriage in response			*
 *		2014/11/30		default order for LegacyFamily::getFamilies		*
 *						is an undefined field							*
 *		2014/12/04		add method getSealEvent for LDS Sealing			*
 *		2014/12/08		redirect trace output to $warn					*
 *						save references to husband and wife in members	*
 *						generate parameter dependent debug trace for	*
 *						various constructor parameter options			*
 *		2014/12/25		add parameters limit and offset to getFamilies	*
 *						return count from getFamilies					*
 *						add XML output from getFamilies					*
 *		2015/01/06		add methods getHusband, getWife,				*
 *						getHusbName, getWifeName						*
 *		2015/01/14		add method clearEvent to permit Event			*
 *						to remove an event from this record				*
 *		2015/02/18		add method updateFamilies						*
 *						extend first parameter of getFamilies,			*
 *						deleteFamilies, and updateFamilies to support	*
 *						array values for most fields					*
 *						remove last references to deprecated spouse		*
 *						name fields.  Always get from LegacyIndiv		*
 *						extract common handling of constructor parms	*
 *						'idirhusb' and 'idirwife'						*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/05/01		add support for internationalization to method	*
 *						getName											*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/08		base class changed to RecordPic					*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2016/05/09		correct errors in previous change				*
 *		2016/06/06		correct array to string conversion error		*
 *		2016/10/02		fix toXml output								*
 *		2016/12/08		set husborder or wifeorder when creating new	*
 *						family											*
 *		2017/01/31		add method getRecordJson						*
 *		2017/03/19		use approved interface to new LegacyIndiv		*
 *		2017/07/20		require option to include events in XML			*
 *						and JSON output									*
 *						logSqlUpdate changed to track changes			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/07		class LegacyChild renamed to class Child		*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/04		rename class LegacyFamily to class Family		*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/10/31		use class RecordSet to manage events			*
 *		2017/11/02		move last SQL requests to prepared statements	*
 *		2017/12/18		add support for temporary fields				*
 *		2018/01/20		add 'name' to temporary fields					*
 *		2019/01/06      move to namespace Genealogy                     *
 *      2019/02/15  	$family->husbPriName, $family->husbMarName,     *
 *                      $family->wifePriName, $family->wifeMarName      *
 *                      fields added and corresponding copies of        *
 *                      the names of the partners deprecated            *
 *                      dump did not return contents of dump            *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/05/20      replace exceptions in method set to warnings    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Child.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/RecOwner.inc';

/************************************************************************
 *  Family																*
 *																		*
 *  Definition of a class implementing behavior for family records		*
 *  within a genealogy database.										*
 ************************************************************************/

class Family extends RecordPic
{
    /********************************************************************
     *	Family->husband		instance of Person							*
     ********************************************************************/
    private	$husband	    = null;

    /********************************************************************
     *	Family->husbPriName	instance of Name							*
     ********************************************************************/
    private	$husbPriName	= null;

    /********************************************************************
     *	Family->husbMarName	instance of Name							*
     ********************************************************************/
    private	$husbMarName	= null;

    /********************************************************************
     *	Family->wife		instance of Person							*
     ********************************************************************/
    private	$wife		    = null;

    /********************************************************************
     *	Family->wifePriName	instance of Name							*
     ********************************************************************/
    private	$wifePriName	= null;

    /********************************************************************
     *	Family->wifeMarName	instance of Name							*
     ********************************************************************/
    private	$wifeMarName	= null;

    /********************************************************************
     *	Family->children	RecordSet of Child					        *
     ********************************************************************/
    private	$children	    = null;

    /********************************************************************
     *  Family->marEvent	instance of Event describing marriage		*
     ********************************************************************/
    private	$marEvent	    = null;

    /********************************************************************
     *  Family->marEndEvent	instance of Event describing marriage end	*
     ********************************************************************/
    private	$marEndEvent	= null;

    /********************************************************************
     *	Family->sealEvent	instance of Event describing LDS sealing 	*
     ********************************************************************/
    private	$sealEvent	    = null;

    /********************************************************************
     *	Family->events		array of instances of Event					*
     ********************************************************************/
    private	$events		    = null;

    /********************************************************************
     * the standard values of the marriage status field					*
     * these correspond to the values of IDMS for the					*
     * records that Legacy always inserts into table tblMS				*
     * however it is not a complete list of possible values because		*
     * the user can add their own marriage status values.				*
     ********************************************************************/

    const MS_COMMON			    =  1;
    const MS_ANNULLED			=  2;
    const MS_COMMON_LAW			=  3;
    const MS_DIVORCED			=  4;
    const MS_MARRIED			=  5;
    const MS_OTHER			    =  6;
    const MS_SEPARATED			=  7;
    const MS_UNMARRIED			=  8;
    const MS_DIVORCE			=  9;
    const MS_SEPARATION			= 10;
    const MS_PRIVATE			= 11;
    const MS_PARTNERS			= 12;
    const MS_DEATH_1_SPOUSE		= 13;
    const MS_SINGLE			    = 14;
    const MS_FRIENDS			= 15;

    /********************************************************************
     *  Family::$statusText												*
     *																	*
     *  This table provides a translation from an marriage status to	*
     *  the text to display to the user for the pre-defined values.		*
     *	This saves issuing an SQL query to obtain the text for these	*
     *	common values.													*
     ********************************************************************/
    static $statusText	= array(
						    1 	=> '',
						    2 	=> 'Annulled',
						    3 	=> 'Common Law',
						    4 	=> 'Divorced',
						    5 	=> 'Married',
						    6 	=> 'Other',
						    7 	=> 'Separated',
						    8 	=> 'Unmarried',
						    9 	=> 'Divorce',
						   10 	=> 'Separation',
						   11 	=> 'Private',
						   12 	=> 'Partners',
						   13 	=> 'Death of one spouse',
						   14 	=> 'Single',
						   15 	=> 'Friends'
		);

    /********************************************************************
     *  Family::$statusVerb												*
     *																	*
     *  This table provides a translation from an marriage status to	*
     *  the verb to display to the user for the pre-defined values.		*
     *	This saves issuing an SQL query to obtain the text for these	*
     *	common values.													*
     ********************************************************************/
    static $statusVerb	= array(
						    1 	=> 'married',
						    2 	=> 'married',
						    3 	=> 'had a Common Law relationship with',
						    4 	=> 'married',
						    5 	=> 'married',
						    6 	=> 'had some other relationship with',
						    7 	=> 'married',
						    8 	=> 'had a relationship with',
						    9 	=> 'married',
						   10 	=> 'married',
						   11 	=> '[Private]',
						   12 	=> 'was the partner of',
						   13 	=> 'was widowed',
						   14 	=> 'was single',
						   15 	=> 'was a friend [with benefits]'
		);

    /********************************************************************
     *	Family::$initRow                                                *
     *	static array containing default values for a new record			*
     ********************************************************************/
    protected static	$initRow	= array(
								'id'					=> 0,
								'idmr'					=> 0,
								'idirhusb'				=> 0,
								'husborder'				=> 0,
								'husbprefmar'			=> 1,
								'husbsurname'			=> '',
								'husbgivenname'			=> '',
								'husbmarrsurname'		=> '',
								'husbbirthsd'			=> -99999999,
								'idirwife'				=> 0,
								'wifeorder'				=> 0,
								'wifeprefmar'			=> 1,
								'wifesurname'			=> '',
								'wifegivenname'			=> '',
								'wifemarrsurname'		=> '',
								'wifebirthsd'			=> -99999999,
								'marriednamerule'		=> 1,
								'idms'					=> 1,
								'marsd'					=> -99999999,
								'mard'					=> '',
								'marendsd'				=> -99999999,
								'marendd'				=> '',
								'idlrmar'				=> 1,
								'sealsd'				=> -99999999,
								'seald'					=> '',
								'idtrseal'				=> 0,
								'sealnote'				=> '',
								'ldss'					=> 0,
								'templetag'				=> 0,
								'tag1'					=> 0,
								'tag2'					=> 0,
								'tag3'					=> 0,
								'tag4'					=> 0,
								'tag5'					=> 0,
								'tag6'					=> 0,
								'tag7'					=> 0,
								'tag8'					=> 0,
								'tag9'					=> 0,
								'taggroup'				=> 0,
								'srchtag'				=> 0,
								'qstag'					=> 0,
								'remindertag'			=> 0,
								'notmarried'			=> 0,
								'nochildren'			=> 0,
								'alreadyused'			=> 0,
								'ltmp1'					=> 0,
								'ltmp2'					=> 0,
								'notes'					=> '',
								'ppcheck'				=> 0,
								'added'					=> 0,
								'addedtime'				=> '',
								'updated'				=> 0,
								'updatedtime'			=> '',
								'idar'					=> 0,
								'hphrase'				=> '',
								'wphrase'				=> '',
								'rphrase'				=> '',
								'rphrase2'				=> '',
								'userref'				=> '',
								'mphrase'				=> '',
								'sphrase'				=> '',
								'husbwifeover1'			=> '',
								'wifehusbover1'			=> '',
								'husbwifeover2'			=> '',
								'wifehusbover2'			=> '',
								'private'				=> 0);

    /********************************************************************
     *	function Family::__construct									*
     *																	*
     *  Create a new instance of Family.  If unable to, usually			*
     *	as a result of a bad parameter value, it updates $this->msg     *
     *	report the problem and prevent the record from being saved.		*
     *																	*
     *  Input:															*
     *	    $parms	    o		a database record presented as an		*
     *							associative array, or					*
     *					o		array('idmr' => $idmr) to obtain		*
     *					        the existing record with that key       *
     *					o		array('id' => $id) to obtain		    *
     *					        the existing record with that key       *
     *					        DEPRECATED                              *
     *					o       array('idirhusb'=> $idir) to add        *
     *					        a new family for a husband              *
     *					o       array('idirwife'=> $idir) to add        *
     *					        a new family for a wife                 *
     *					o       array('husbsurname'	=> $surname,        *
     *							'husbmarrsurname' => $surname)          *
     *							to create a new set of parents for      *
     *							a child                                 *
     *					o       note that you cannot obtain an existing *
     *					        record for a husband/wife pair except   *
     *					        through new RecordSet('Families')       *
     *					        array('idirhusb'=> $idir,               *
     *					              'idirwife'=> $idir) adds a new    *
     *					        instance of Family                      *
     ********************************************************************/
    function __construct($parms = null)
    {
		global	$debug;
		global	$warn;
        global	$connection;

        // set defaults
		$dbrow		                        = self::$initRow;
        $needInsert		                    = true;

		// action depends upon type of first parameter
		if (is_string($parms) && ctype_digit($parms))
		    $parms	= (int)$parms;

		if (is_int($parms))
		{		                // deprecated numeric identifier of record
		    error_log("Deprecated new Family($parms)\n");
		    ob_start();
		    debug_print_backtrace();
		    error_log(ob_get_clean() . "\n");
		    $parms		= array('idmr' => $parms);
		}		                // deprecated numeric identifier of record

        // analyse parameters
		if (is_array($parms))
		{			            // associative array
		    if (count($parms) >= count(self::$initRow))
		    {			        // complete row from database
				$dbrow		                = $parms;

				$idmr		                = $dbrow['idmr'];
				if ($debug)
				    $warn	.= "<p>Family: " . __LINE__ .
								   " new Family(complete row IDMR=$idmr)</p>\n";
				// special handling to avoid having to construct instances
				// of Person
				$idirhusb		            = $dbrow['idirhusb'];
				$idirwife		            = $dbrow['idirwife'];
				if ($idirhusb instanceof Person)
				{		        // passed an instance of Person
				    $this->husband	        = $idirhusb;
				    $dbrow['idirhusb']	    = $idirhusb->getIdir();
				}		        // passed an instance of Person
				if ($idirwife instanceof Person)
				{		        // passed an instance of Person
				    $this->wife		        = $idirwife;
				    $dbrow['idirwife']	    = $idirwife->getIdir();
				}		        // passed an instance of Person

				$needInsert		            = $idmr == 0;
		    }			        // complete row
		    else
		    {			        // search parameters
				$parms			            = $parms;
                $idmr			            = 0;
                $where	                    = '';
                $and                        ='WHERE ';
                $sqlParms                   = array();
                $trace                      = '';
                $comma                      = '';

				foreach($parms as $fldname => $value)
				{		        // loop through all parameters	
				    $fieldLc                = strtolower($fldname);
            		if (array_key_exists($fieldLc, self::$translate))
		                $fieldLc	= self::$translate[$fieldLc];
				    switch ($fieldLc)
				    {		    // act on specific fields
						case 'idmr':
						{
                            $idmr		    = $value;
                            if ($idmr > 0)
                            {
                                $where      .= $and . "IDMR=:idmr";
        				        $sqlParms	= array('idmr'	=> $idmr);
                                $and        = ' AND ';
                            }
							$trace		    .= "$comma$fldname=$value";
                            $comma          = ',';
						    break;
						}

						case 'id':
						{
                            $id		        = $value;
                            if ($id > 0)
                            {
                                $where      .= $and . "ID=:id";
        				        $sqlParms	= array('id'	=> $id);
                                $and        = ' AND ';
                            }
							$trace		    .= "$comma$fldname=$value";
                            $comma          = ',';
						    break;
						}

						case 'idirhusb':
						{		// IDIRHusb
							if ($value instanceof Person)
							{
				                $this->husband	= $value;
							    $idirhusb	    = $value->get('idir');
							    $trace	        .= "$comma$fldname=object " .
								        	        "Person(IDIR=$idirhusb)";
							}
                            else
                            {
                                $idirhusb       = $value;
							    $trace		    .= "$comma$fldname=$value";
                            }
                            $dbrow['idirhusb']  = $idirhusb;
                            $comma              = ',';
						    break;
						}		// IDIRHusb

						case 'idirwife':
						{		// IDIRWife
							if ($value instanceof Person)
							{
				                $this->wife	    = $value;
							    $idirwife	    = $value->get('idir');
							    $trace		    .= "$comma$fldname=object " .
								        	        "Person(IDIR=$idirwife)";
                            }
                            else
                            {
                                $idirwife       = $value;
                                $trace          .= "$comma$fldname=$value";
                            }
                            $dbrow['idirwife']  = $idirwife;
                            $comma              = ',';
						    break;
						}		// IDIRWife

						default:
						{
							$trace			    .= "$comma$fldname=$value";
                            $dbrow[$fieldLc]    = $value;
                            $comma              = ',';
						    break;
						}
				    }		    // act on specific fields
                }		        // loop through all parameters

				if ($debug)
				    $warn		        .= "<p>Family: " . __LINE__ .
                                            " new Family(array($trace))</p>\n";
				if (strlen($where) > 0)
				{		        // key supplied
				    // construct the query of the marriage table
				    $query	            = "SELECT * FROM tblMR $where";

				    // query the database
				    $stmt	            = $connection->prepare($query);
				    $queryText	        = debugPrepQuery($query, $sqlParms);

				    if ($stmt->execute($sqlParms))
				    {		    // success
						if ($debug)
						    $warn	    .= "<p>Family: " . __LINE__ .
									       " query='$queryText'</p>\n";
						$row	        = $stmt->fetch(PDO::FETCH_ASSOC);
						if (is_array($row))
						{		// found marriage record
						    $dbrow		= $row;
						    $needInsert	= false;
						}		// found marriage record
						else
						{		// marriage record not found
						}		// marriage record not found
				    }		    // success
				    else
				    {		    // error performing query
						$this->msg  .= "'$queryText', " .
									    print_r($stmt->errorInfo(),true) . ". ";
				    }		    // error performing query
				}		        // key supplied
				else
				{		        // key not specified: new record
				}		        // new record
		    }			        // search parameters
		}			            // array
		else
		{                       // not an array
            $this->msg      = "Family::__create called with " .
                              gettype($parms) . ", value='$parms'. ";
        }	                    // not an array

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblMR');

		$this->needInsert	= $needInsert;

		// initialize the table of children of this family
		if ($needInsert)
		{		// new family has no children
		    $this->children		= new RecordSet('Children', array());
		}		// new family has no children
		else
		    $this->children     = $this->getChildren();

		// diagnostic output if debug is set
        $this->dump('Family Record constructed:');
    }		// Family::__construct

    /********************************************************************
     *	function Family::delete											*
     *																	*
     *	Delete the associated record from the database, along with all	*
     *	records in other tables that reference this record.				*
     *																	*
     *	Parameters:														*
     *	    $xml		true if XML output is to be generated			*
     *					if a string use it as tag name					*
     ********************************************************************/
    function delete($xml = false)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if (is_string($xml))
		{		        // string tag name
		    $tagname	= $xml;
		    $msgtag	    = "p class='message'";
		    $emsgtag	= "p";
		    $xml	    = true;
		}		        // string tag name
		else
		if ($xml)
		{		        // true
		    $tagname	= 'cmd';
		    $msgtag	    = 'msg';
		    $emsgtag	= 'msg';
		}		        // true

		// if the record has not been inserted into the database
		// there is nothing to delete
		if ($this->needInsert)
		    return 0;

		// get identity of record to delete from the database
		$idmr		    = $this->getIdmr();	// primary key of record

		// delete associated events
		$events		    = $this->getEvents();
		if (count($events) > 0)
		{		        // there are events to delete
		    $eventids	= array();
		    foreach ($events as $ie => $event)
		    {		    // create list of all events
				$eventids[]	= $event->get('idir');
		    }		    // create list of all events

		    // delete citations
		    $citparms	= array('idime'	=> $eventids,
								'type'	=> 31);
		    $citations	= new RecordSet('Citations', $citparms);
		    $citations->delete($xml);

		    // delete the associated events if any
		    $events	= new RecordSet('Events',
									array('idir'	=> $idmr,
									      'idtype'	=> 20));
		    $events->delete($xml);
		}		        // there are events to delete

		// delete the married name records associated with this
		// family, if any
		$nameParms	= array('Order'			        => -1, 
							'MarriedNameMarIDID'	=> $idmr);
		$names		= new RecordSet('Names', $nameParms);
		$names->delete($xml);

		// delete the citations associated with this family, if any
		$citations	= new CitationSet(array('idmr' => $idmr));
		$citations->delete($xml);

		// delete the associated child relation entries if any
		// note that this does not delete the associated instances of Person
		// which must be manually deleted if required
		$count		= $this->children->delete();

		// delete the marriage record
		$oldJson	    = json_encode($this->row);
		$delete		    = "DELETE FROM tblMR WHERE IDMR=:idmr";
		$sqlParms	    = array('idmr' => $idmr);
		$stmt		    = $connection->prepare($delete);
		$deleteText	    = debugPrepQuery($delete, $sqlParms);
		if ($stmt->execute($sqlParms))
		{		        // success
		    $count		= $stmt->rowCount();
		    if ($xml)
				print "    <$tagname count='$count'>\n" .
								"\t$deleteText\n" .
				      "    </$tagname>\n";
		    if ($debug)
				$warn	.= "<p>Family::delete: " . __LINE__ . 
						   " $deleteText</p>";

		    if ($count > 0)
		    {		    // record was deleted
				logSqlUpdate($delete,
						     $sqlParms,
						     $idmr,
						     $oldJson,
						     '');

				// record the deleted IDMR in tblXM
				$addXM		= "INSERT INTO tblXM VALUE(:idmr)";

				// update the database
				$stmt		= $connection->prepare($addXM);
				$addXMText	= debugPrepQuery($addXM, $sqlParms);
				if ($stmt->execute($sqlParms))
				{       // insert released IDMR
				    $countXM	= $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$countXM'>" .
								"$addXMText" .
						      "</$tagname>\n";
				    if ($debug)
						$warn	.= "<p>Family::delete: " . __LINE__ . 
								   " $addXMText</p>";
				    logSqlUpdate($addXM,
								 $sqlParms,
								 $connection->lastInsertId(),
								 '',
								 json_encode(array($idmr)));
				}       // insert released IDMR
				else
				{		// error performing query
				    // ignore duplicate add error
				}		// error performing query
		    }		    // record was deleted
		    return $count;
		}		        // success
		else
		{		        // error performing delete
		    $errorInfo	= print_r($stmt->errorInfo(),true);
		    if ($xml)
		    {		    // XML output
				print "    <$tagname count='0'>\n\t$deleteText\n </$tagname>\n";
				print "    <$msgtag>\n\t$errorInfo\n    </$emsgtag>\n";
		    }		    // XML output
		    else
		    {		    // no XML output
				$warn	.= "<p>Family::delete: '$deleteText' $errorInfo<\p>\n";
		    }		    // no XML output
		    return false;
		}		        // error performing delete
    }		// function Family::delete

    /********************************************************************
     *	function Family::getName										*
     *																	*
     *	Get a descriptive summary of the object.						*
     *	This function is called getName for symmetry with Person		*
     *	and Name.														*
     *																	*
     *	Input:															*
     *		associative array supporting the following options:		    *
     *	    'includeDate'		if false do not include date of marriage*
     *	    'connector'			conjunction between spouse names		*
     *	    'dateprefix'		prefix before marriage date				*
     *	    'locprefix'			prefix before marriage location			*
     *																	*
     *	Returns:														*
     *	    String containing names of spouses and optionally the		*
     *	    date of marriage.											*
     ********************************************************************/
    function getName($options		= null)
    {
		$includeDate 	= true;
		if (!class_exists(__NAMESPACE__ . '\\Language'))
		    include __NAMESPACE__ . "/Language.inc";
		$language	    = new Language();
		$connector	    = $language->get('conjunction');
		$dateprefix	    = $language->get('dateprefix');
		$locprefix	    = '';
		if (is_array($options))
		{
		    foreach($options as $key => $value)
		    {
				switch(strtolower($key))
				{
				    case 'includedate':
				    {
						$includeDate	= $value;
						break;
				    }

				    case 'connector':
				    {
						$connector	    = $value;
						break;
				    }

				    case 'dateprefix':
				    {
						$dateprefix	    = $value;
						break;
				    }

				    case 'locprefix':
				    {
						$locprefix	    = $value;
						break;
				    }
				}		// act on individual options
		    }			// loop through options
		}			    // options supplied

		$name		        = '';
		$mard		        = $this->row['mard'];
        $idlrmar	        = $this->row['idlrmar'];
		if (!is_null($this->getHusbPriName()))
		{		        // husband identified
			$name	        .= $this->husbPriName->getName();
		    if (!is_null($this->getWifePriName()))
		    {	        // both spouses identified
				$name	    .= " $connector " . $this->wifePriName->getName();
		    }	        // both spouses identified
		}		        // husband identified
        else
		if (!is_null($this->getWifePriName()))
        {		        // wife identified
			$name	        .= $this->wifePriName->getName();
		}		        // wife identified

		if ($includeDate && strlen($mard) > 0)
		{		        // include date of marriage
		    $date	        = new LegacyDate($mard);
		    $name	        .= " $dateprefix " . $date->toString();
		    if ($idlrmar > 1)
		    {		    // not the null location
				$marloc		= new Location(array('idlr' => $idlrmar));
				if (strlen($locprefix) > 0)
				    $name	.= " $locprefix " . $marloc->getName();
		    }		    // not the null location
		}		        // include date of marriage
		if (strlen($name) == 0)
		    $name	= 'IDMR=' . $this->row['idmr'];
		return $name;
    }		// function Family::getName

    /********************************************************************
     *  function Family::getHusbPriName									*
     *																	*
     *  Get the instance of Name for the primary name of the husband    *
     *																	*
     *  Returns:														*
     *	    instance of Name                                            *
     *																	*
     *  Side Effects:													*
     *	    member Family::husbPriName is set							*
     ********************************************************************/
    function getHusbPriName()
    {
        if (is_null($this->husbPriName))
        {
            $idirhusb       = $this->row['idirhusb'];
            if ($idirhusb > 0)
                $this->husbPriName  = new Name(array('idir'   => $idirhusb));
        }
        return $this->husbPriName;
    }           // function getHusbPriName

    /********************************************************************
     *  function Family::getHusbMarName									*
     *																	*
     *  Get the instance of Name for the married name of the husband    *
     *																	*
     *  Returns:														*
     *	    instance of Name                                            *
     *																	*
     *  Side Effects:													*
     *	    member Family::husbMarName is set							*
     ********************************************************************/
    function getHusbMarName()
    {
        if (is_null($this->husbMarName))
        {
            $idirhusb       = $this->row['idirhusb'];
            if ($idirhusb > 0)
                $this->husbMarName  = new Name(array('idir'     => $idirhusb,
                                                     'idmr'     => $this));
        }
        return $this->husbMarName;
    }           // function getHusbMarName

    /********************************************************************
     *  function Family::getWifePriName									*
     *																	*
     *  Get the instance of Name for the primary name of the wifeand    *
     *																	*
     *  Returns:														*
     *	    instance of Name                                            *
     *																	*
     *  Side Effects:													*
     *	    member Family::wifePriName is set							*
     ********************************************************************/
    function getWifePriName()
    {
        if (is_null($this->wifePriName))
        {
            $idirwife       = $this->row['idirwife'];
            if ($idirwife > 0)
                $this->wifePriName  = new Name(array('idir'   => $idirwife));
        }
        return $this->wifePriName;
    }           // function getWifePriName

    /********************************************************************
     *  function Family::getWifeMarName									*
     *																	*
     *  Get the instance of Name for the married name of the wifeand    *
     *																	*
     *  Returns:														*
     *	    instance of Name                                            *
     *																	*
     *  Side Effects:													*
     *	    member Family::wifeMarName is set							*
     ********************************************************************/
    function getWifeMarName()
    {
        if (is_null($this->wifeMarName))
        {
            $idirwife       = $this->row['idirwife'];
            if ($idirwife > 0)
                $this->wifeMarName  = new Name(array('idir'     => $idirwife,
                                                     'idmr'     => $this));
        }
        return $this->wifeMarName;
    }           // function getWifeMarName

    /********************************************************************
     *	function Family::getHusbName									*
     *																	*
     *	Get the name of the male spouse.								*
     *																	*
     *	Input:															*
     *	    $options		see Person::getName							*
     *																	*
     *	Returns:														*
     *	    String containing the name of spouse and optionally the		*
     *	    birth and death dates.										*
     ********************************************************************/
    function getHusbName($options = 0)
    {
		if ($this->getHusbPriName())
		    return $this->husbPriName->getName($options);
		else
		    return '';
    }		// Family::getHusbName

    /********************************************************************
     *	function Family::getWifeName									*
     *																	*
     *	Get the name of the female spouse.								*
     *																	*
     *	Input:															*
     *	    $options		see Person::getName							*
     *																	*
     *	Returns:														*
     *	    String containing the name of spouse and optionally the		*
     *	    birth and death dates.										*
     ********************************************************************/
    function getWifeName($options = 0)
    {
		if ($this->getWifePriName())
		    return $this->wifePriName->getName($options);
		else
		    return '';
    }		// function Family::getWifeName

    /********************************************************************
     *	function Family::getHusband										*
     *																	*
     *	Get the record for the male spouse.								*
     *																	*
     *	Returns:														*
     *	    instance of Person or null									*
     ********************************************************************/
    function getHusband()
    {
		if (is_null($this->husband) && $this->row['idirhusb'] > 0)
		    $this->husband	= new Person(
								array('idir' => $this->row['idirhusb']));
		return $this->husband;
    }		// function Family::getHusband

    /********************************************************************
     *	function Family::getWife										*
     *																	*
     *	Get the record for the female spouse.							*
     *																	*
     *	Returns:														*
     *	    instance of Person or null									*
     ********************************************************************/
    function getWife()
    {
		if (is_null($this->wife) && $this->row['idirwife'] > 0)
		    $this->wife		= new Person(
								array('idir' => $this->row['idirwife']));
		return $this->wife;
    }		// function Family::getWife

    /********************************************************************
     *	function Family::getIdmr										*
     *																	*
     *	Get the unique numeric identifier of the family.				*
     ********************************************************************/
    function getIdmr()
    {
		return $this->row['idmr'];
    }		// function Family::getIdmr

    /********************************************************************
     *	function Family::getIdms										*
     *																	*
     *	Get the numeric marriage status.								*
     ********************************************************************/
    function getIdms()
    {
		return $this->row['idms'];
    }		// function Family::getIdms

    /********************************************************************
     *	function Family::setIdms										*
     *																	*
     *	Set the numeric marriage status.								*
     *																	*
     *	Input:															*
     *	    $value		new value of marriage status.					*
     *					usually one of the predefined values:			*
     *			Family::MS_COMMON				=  1;					*
     *			Family::MS_ANNULLED				=  2;					*
     *			Family::MS_COMMON_LAW			=  3;					*
     *			Family::MS_DIVORCED				=  4;					*
     *			Family::MS_MARRIED				=  5;					*
     *			Family::MS_OTHER				=  6;					*
     *			Family::MS_SEPARATED			=  7;					*
     *			Family::MS_UNMARRIED			=  8;					*
     *			Family::MS_DIVORCE				=  9;					*
     *			Family::MS_SEPARATION			= 10;					*
     *			Family::MS_PRIVATE				= 11;					*
     *			Family::MS_PARTNERS				= 12;					*
     *			Family::MS_DEATH_1_SPOUSE		= 13;					*
     *			Family::MS_SINGLE				= 14;					*
     *			Family::MS_FRIENDS				= 15;					*
     *																	*
     *	Returns:														*
     *			previous value of marriage status						*
     ********************************************************************/
    function setIdms($value)
    {
		return parent::set('idms', $value);
    }		// function Family:setIdms

    /********************************************************************
     *	function Family::getStatus										*
     *																	*
     *	Get the text associated with the marriage status.				*
     ********************************************************************/
    function getStatus()
    {
		global	$connection;

		$idms	= $this->row['idms'];
		if (array_key_exists($idms, self::$statusText))
		    return self::$statusText[$idms];
		else
		{
		    $query	= "SELECT MarStatus FROM tblMS WHERE IDMS=:idms";
		    $sqlParms	= array('idms'	=> $idms);
		    $stmt	= $connection->prepare($query);
		    $queryText	= debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {
				$row	= $stmt->fetch(PDO::FETCH_NUM);
				if (is_array($row))
				{
				    return $row[0];
				}
				else
				    return "IDMS=$idms";
		    }
		    else
		    {		// error performing query
				throw new Exception("Family::getStatus: '$queryText', " .
								    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}
    }		// function Family::getStatus

    /********************************************************************
     *	function Family::getStatusVerb									*
     *																	*
     *	Get the verbal phrase associated with the marriage status.		*
     ********************************************************************/
    function getStatusVerb()
    {
		global	$connection;

		$idms	= $this->row['idms'];
		if (array_key_exists($idms, self::$statusVerb))
		    return self::$statusVerb[$idms];
		else
		    return $this->getStatus();
    }		// Family::getStatusVerb

    /********************************************************************
     *  function Family::getChildren									*
     *																	*
     *  Get a RecordSet of the children of this family.					*
     *																	*
     *  Returns:														*
     *      instance of RecordSet containing instances of Child			*
     ********************************************************************/
    function getChildren()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if ($this->children !== null)
		    return	$this->children;	// already done

		// construct a query of the child record table for the current
		// marriage to initialize Family::children
		$parms			    = array('idmr'	=> $this->row['idmr']);
		$this->children		= new RecordSet('Children', $parms);

		return	$this->children;
    }		// function Family::getChildren

    /********************************************************************
     *	function Family::getChildByIdir									*
     *																	*
     *	Get the existing instance of Child corresponding to a			*
     *	particular individual.											*
     *																	*
     *  Parameters:														*
     *	    $idir		unique key of individual (child)				*
     *																	*
     *  Returns:														*
     *	    Instance of Child or null if no matching entry				*
     ********************************************************************/
    function getChildByIdir($idir)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$children	= $this->getChildren();
		foreach($children as $ic => $childr)
		{		// loop through all existing children
		    if ($childr->getIdir() == $idir)
				return $childr;
		}		// loop through all existing children

		return null;
    }		// function Family::getChildByIdir

    /********************************************************************
     *  function Family::addChild										*
     *																	*
     *  Add the specified individual as a child of this family.			*
     *	Note that if the individual is already a child of this family	*
     *	then the existing instance of Child is returned.				*
     *	Note that the returned instance of Child may not yet			*
     *	be in the database, and therefore may not have a value of IDCR.	*
     *																	*
     *  Parameters:														*
     *	    $idir		identifier of new child							*
     *																	*
     *  Returns:														*
     *	    instance of Child											*
     *																	*
     *  Throws exception if unable to complete function					*
     ********************************************************************/
    function addChild($idir)
    {
		global	$connection;
		global	$debug;
		global	$warn;

		$children	= $this->getChildren();
		foreach($children as $ic => $childr)
		{		// loop through all existing children
		    if ($childr->getIdir() == $idir)
				return $childr;
		}		// loop through all existing children
		$idmr		= $this->row['idmr'];
		$childr		= new Child(array('idmr'	=> $idmr,
									  'idir'	=> $idir));
		// note that the new child does not have an IDCR value because
		// it is not backed by a record in the database
		// the IDCR value is assigned when the new record is saved
		// as part of the process of saving all of the changes to the
		// Family.  A unique integer index is assigned in the internal
		// array of the RecordSet but is not reflected in the Child
		// record itself
		$this->children[]	= $childr;
		return $childr;
    }		// function Family::addChild

    /********************************************************************
     *  function Family::detachChild									*
     *																	*
     *  Detach the specified individual as a child of this family.		*
     *																	*
     *  Parameters:														*
     *	    $idcr		identifier of child record in tblCR				*
     *	    $xml		if true emit XML debugging info					*
     *																	*
     *  Returns:														*
     *		The record deleted from the child table						*
     *			$retval->isExisting() returns false						*
     *			null if the $idcr parameter did not match any existing	*
     *			child													*
     ********************************************************************/
    function detachChild($idcr, $xml)
    {
		$children	= $this->getChildren();
		// ToDo:  Now that $this->getChildren() returns a RecordSet
		// and since that RecordSet uses IDCR as the key of each record
		// the required record should be $children[$idcr], which will
		// return null if undefined, unlike the failure when an array.
		foreach($children as $i => $childr)
		{		// loop through loaded list of children
		    if ($childr->get('idcr') == $idcr)
		    {		// remove match
				// remove matching entry from array
				unset($this->children[$i]);

				// delete record from database if necessary
				$childr->delete($xml);

				// resequence order of children to remove gap  
				$j		= 0;
				foreach($this->children as $ochildr)
				{
				    $ochildr->set('order', $j);
				    $j++;
				}
				return $childr;
		    }		// remove match
		}		// loop through loaded list of children

		return null;	// no match to delete
    }		// function Family::detachChild

    /********************************************************************
     *  function Family::getMarEvent									*
     *																	*
     *  Get the primary marriage event for the family.					*
     *																	*
     *  Input:															*
     *	    $create		if true return instance of Event even			*
     *					if there is no existing event					*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getMarEvent($create = false)
    {
		$event		= $this->marEvent;
		if (is_null($event))
		{		// not initialized
		    $trow			        = array();
		    $trow['idir']		    = $this;
		    $trow['idtype']		    = Event::IDTYPE_MAR;
		    $trow['idet']		    = Event::ET_MARRIAGE;
			$trow['eventd']    	    = $this->get('mard');
			$trow['eventsd']   	    = $this->get('marsd');
			$trow['idlrevent'] 	    = $this->get('idlrmar');
			$trow['idar']	        = $this->get('idar');
			$trow['desc']	        = $this->get('notes');
			$trow['description']	= '';
		    $trow['preferred']		= 1;
		    if ($create ||
				$trow['eventsd'] != -99999999 ||
				$trow['idlrevent'] > 1 ||
				$trow['desc'] != '')
		    {		// LDS endow event present
		        $event		        = new Event($trow);
				$this->marEvent		= $event;
		    }		// LDS endow event present
		    else
				$event			=  null;
		}		// not initialized
		return $event;
    }		// function Family::getMarEvent

    /********************************************************************
     *  function Family::getSealEvent									*
     *																	*
     *  Get the LDS sealed event for the family.						*
     *																	*
     *  Input:															*
     *	    $create		if true return instance of Event even			*
     *					if there is no existing event					*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getSealEvent($create = false)
    {
		$event		= $this->sealEvent;
		if (is_null($event))
		{		// not initialized
		    $trow			        = array();
		    $trow['idir']		    = $this;
		    $trow['idtype']		    = Event::IDTYPE_MAR;
		    $trow['idet']		    = Event::ET_LDS_SEALED;
			$trow['eventd']    	    = $this->get('seald');
			$trow['eventsd']   	    = $this->get('sealsd');
			$trow['idlrevent'] 	    = $this->get('idtrseal');
			$trow['kind']	    	= 1;
			$trow['desc']	    	= $this->get('sealnote');
			$trow['description']	= '';
			$trow['ldstempleready'] = $this->get('ldss');
		    $trow['preferred']		= 1;
		    if ($create ||
				$trow['eventsd'] != -99999999 ||
				$trow['idlrevent'] > 1 ||
				$trow['desc'] != '')
		    {		// LDS endow event present
		        $event		        = new Event($trow);
				$this->sealEvent	= $event;
		    }		// LDS endow event present
		    else
				$event			=  null;
		}		// not initialized
		return $event;
    }		// function Family::getSealEvent

    /********************************************************************
     *  function Family::getMarEndEvent									*
     *																	*
     *  Get the marriage end event for the family.						*
     *																	*
     *  Input:															*
     *	    $create		if true return instance of Event even			*
     *					if there is no existing event					*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getMarEndEvent($create = false)
    {
		$event		                = $this->marEndEvent;
		if (is_null($event))
		{		// not initialized
		    $trow			        = array();
		    $trow['idir']		    = $this;
		    $trow['idtype']		    = Event::IDTYPE_MAR;
		    $trow['idet']		    = Event::ET_MARRIAGE_END;
			$trow['eventd']    	    = $this->get('marendd');
			$trow['eventsd']   	    = $this->get('marendsd');
			$trow['idlrevent'] 	    = 0;
			$trow['kind']	    	= 1;
			$trow['desc']	    	= '';
			$trow['description']	= '';
		    $trow['preferred']		= 1;
		    if ($create ||
				$trow['eventsd'] != -99999999 ||
				$trow['idlrevent'] > 1 ||
				$trow['desc'] != '')
		    {		// LDS endow event present
		        $event			    = new Event($trow);
				$this->marEndEvent	= $event;
		    }		// LDS endow event present
		    else
				$event			=  null;
		}		// not initialized
		return $event;
    }		// function Family::getMarEndEvent

    /********************************************************************
     *  function Family::getEvents										*
     *																	*
     *  Get the events associated with this family that are represented	*
     *  as records in tblER.											*
     *																	*
     *  Returns:														*
     *	    instance of RecordSet containing instances of Event			*
     *																	*
     *  Side Effects:													*
     *	    member Family::events is set								*
     ********************************************************************/
    function getEvents()
    {
		if ($this->events == null)
		{		// need to perform query for events
		    $idmr		    = $this->row['idmr'];
		    $parms		    = array('idir'		=> $idmr,
									'idtype'	=> 20);
		    $this->events	= new RecordSet('Events', $parms);

		}		// need to perform query for first event set
		return $this->events;
    }		// function Family::getEvents

    /********************************************************************
     *  function Family::addEvent										*
     *																	*
     *  Add a new individual event represented by an instance of		*
     *  Event to this Family											*
     *																	*
     *  Returns:														*
     *			Instance of Event										*
     ********************************************************************/
    function addEvent($xml=false)
    {
		$parms	= array('idtype'	=> Event::IDTYPE_MAR,
						'idir'		=>  $this);
		$event	= new Event($parms);
		$event->save($xml);	// write to database
		$this->events		= null;	// force reinitialization

		return $event;
    }		// function Family::addEvent

    /********************************************************************
     *  function Family::setName										*
     *																	*
     *  Synchronize data about one of the spouses from the individual	*
     *  record. The name and birth (sort) date are updated to match the	*
     *	instance of Person.												*
     *																	*
     *  Parameters:														*
     *	    $indiv		instance of Person for a spouse, or				*
     *					gender of spouse to clear						*
     *																	*
     ********************************************************************/
    function setName($indiv)
    {
		global	$connection;
		if ($indiv instanceof Person)
		{		// spouse instance
		    $idir	        = $indiv['idir'];
		    $gender	        = $indiv['gender'];
		    $surname	    = $indiv['surname'];
		    $givenName	    = $indiv['givenName'];
		    $birthsd	    = $indiv['birthsd'];
		}		// spouse instance
		else
		if ($indiv instanceof Name)
        {		// Name instance
            $name           = $indiv;
		    $idir	        = $name['idir'];
		    $gender	        = $name['gender'];
		    $surname	    = $name['surname'];
		    $givenName	    = $name['givenName'];
		    $birthsd	    = $name['birthsd'];
		}		// spouse instance
		else
		{		// gender
		    $idir	        = 0;
		    $gender	        = $indiv;
		    $surname	    = '';
		    $givenName	    = '';
		    $birthsd	    = -99999999;
		}		// gender

		if ($gender == Person::FEMALE)
		{		// female
		    if ($idir == 0)
				parent::set('idirwife', 0);
		    else
		    if ($idir != $this->row['idirwife'])
				throw new Exception("Family::setName: " .
								    "IDIR=$idir not equal to IDIRWife=" .
								    $this->row['idirwife']);

		    $oldsurname	= parent::set('wifesurname', $surname);
		    parent::set('wifegivenname', $givenName);
		    parent::set('wifebirthsd', $birthsd);

		    if ($this->row['marriednamerule'] == 1)
		    {		// update married name to match husband's surname
				parent::set('wifemarrsurname', $this->row['husbsurname']);
				// update the associated nominal index record
		    }		// update married name to match husband's surname
		    else
		    if ($this->row['wifemarrsurname'] == $oldsurname)
				parent::set('wifemarrsurname', $surname);

		}		// female
		else
		{		// male
		    if ($idir == 0)
				parent::set('idirhusb', 0);
		    else
		    if ($idir != $this->row['idirhusb'])
				throw new Exception("Family::setName: " .
								    "IDIR=$idir not equal to IDIRHusb=" .
								    $this->row['idirhusb']);

		    $oldsurname		    = parent::set('husbsurname', $surname);
		    $husbmarrsurname	= $this->row['husbmarrsurname'];
		    if ($husbmarrsurname == $oldsurname || $husbmarrsurname=='')
				parent::set('husbmarrsurname', $surname);
		    parent::set('husbgivenname', $givenName);
		    parent::set('husbbirthsd', $birthsd);

		    if ($this->row['marriednamerule'] == 1 &&
				strlen($surname) > 0)
		    {		// update wife's married name to match husband's surname
				parent::set('wifemarrsurname', $surname);
		    }		// update wife's married name to match husband's surname
		}		// male

    }		// function Family::setName

    /********************************************************************
     *  function Family::clearEvent										*
     *																	*
     *  This function is used by Event to remove information			*
     *  about an event which used to be located in this record, but		*
     *	is being moved to Event.										*
     *																	*
     *	Input:															*
     *	   $eventid		string name of event							*
     ********************************************************************/
    function clearEvent($eventid)
    {
		switch(strtolower($eventid))
		{			// validate event id
		    case 'mar':
		    case 'marend':
		    {			// events using IDLR
				parent::set($eventid . 'd', '');
				parent::set($eventid . 'sd', -99999999);
				parent::set('idlr' . $eventid, 1);
				break;
		    }			// events using IDLR
		}			// validate event id
    }		// function Family::clearEvent

    /********************************************************************
     *  function Family::updateName										*
     *																	*
     *  Static member function to update appropriate records in			*
     *	table tblMR as a result of a change to the name of an			*
     *	individual.														*
     *																	*
     *  Parameters:														*
     *	    $indiv		instance of Person for spouse					*
     *	    $xml		true if executed command is to output to XML	*
     ********************************************************************/
    static function updateName($indiv, $xml=false)
    {
		global	$connection;
		global	$debug;
		global	$warn;
		$idir		= $indiv->getIdir();
		$gender		= $indiv->getGender();
		if ($xml)
		{
		    print "<updateName>\n";
		    print "<idir>$idir</idir>\n";
		    print "<gender>$gender</gender>\n";
		    print "</updateName>\n";
		}

		// update any marriage records
		if ($gender == 1 /*Person::FEMALE*/)
		{		// female
		    $getParms	= array('IDIRWife'	=> $idir);
		}		// female
		else
		{		// male
		    $getParms	= array('IDIRHusb'	=> $idir);
		}		// male

		// get the list of marriages for this individual
		$families	= new RecordSet('Families', $getParms);
		foreach($families as $family)
		{		// loop through all matching families
		    $family->setName($indiv);
		    $family->save($xml);
		}		// loop through all matching families
    }		// function Family::updateName

    /********************************************************************
     *  function Family::toXml											*
     *																	*
     *  Emit an XML representation of the record.						*
     *																	*
     *  Most fields in the record are represented by a child element	*
     *	with the name of the field containing the internal value as		*
     *	text.															*
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
     *	For example the element <idlrmar> contains the original			*
     *	numeric code while the element <marloc> contains the external	*
     *	textual value.													*
     *																	*
     *  Each field whose value is an instance of Temple appears			*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idtr" prefix removed and a "loc" suffix added.	*
     *	For example the element <idtrseal> contains the original		*
     *	numeric code while the element <sealloc> contains the external	*
     *	textual value.													*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'family'			*
     *	    $parms		if true print the output, 						*
     *					if false just return it							*
     *	    $options	flags to indicate which information to include	*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 3rd parameter						*
     ********************************************************************/
    const	TOXML_INCLUDE_CHILDREN		= 1;
    const	TOXML_INCLUDE_CITATIONS		= 2;
    const	TOXML_INCLUDE_EVENTS		= 4;

    function toXml($top		= 'family',
				   $print	= true,
				   $options	= self::TOXML_INCLUDE_CHILDREN)
    {
		$idmr			= $this->row['idmr'];
		$includeChildren	= $options & self::TOXML_INCLUDE_CHILDREN;
		$includeCitations	= $options & self::TOXML_INCLUDE_CITATIONS;
		$includeEvents		= $options & self::TOXML_INCLUDE_EVENTS;

		// the current user is an owner of this family record if
		// the current user is an owner of both the husband and the wife
		$idirhusb	= $this->row['idirhusb'];
		$idirwife	= $this->row['idirwife'];
		$isOwner	= ($idirhusb == 0 || 
						   RecOwner::chkOwner($idirhusb, 'tblIR')) &&
						  ($idirwife == 0 || 
						   RecOwner::chkOwner($idirwife, 'tblIR'));
		if (is_null($top))
		    $retval	= '';
		else
		    $retval	= "<$top idmr='$idmr'>\n";

try {
		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'mard':			// displayed in Marriage Event
				case 'marsd':			// displayed in Marriage Event
				case 'idlrmar':			// displayed in Marriage Event
				case 'idar':			// displayed in Marriage Event
				case 'notes':			// displayed in Marriage Event
				case 'marendd':			// displayed in Marriage Ended Event
				case 'marendsd':		// displayed in Marriage Ended Event
				case 'husbsurname':	    // displayed in Name records
				case 'husbgivenname':	// displayed in Name records
				case 'husbmarrsurname':	// displayed in Name records
				case 'husbbirthsd':	    // displayed in Name records
				case 'wifesurname':	    // displayed in Name records
				case 'wifegivenname':	// displayed in Name records
				case 'wifemarrsurname':	// displayed in Name records
				case 'wifebirthsd':	    // displayed in Name records
				case 'wifebirthsd':	    // displayed in Name records
				case 'seald':			// displayed in Sealed Event
				case 'sealsd':			// displayed in Sealed Event
				case 'idtrseal':		// displayed in Sealed Event
				case 'sealnote':		// displayed in Sealed Event
				case 'ldss':			// displayed in Sealed Event
				{       // obsolete fields no longer displayed
				    break;
				}       // obsolete fields no longer displayed

				case 'ltmp1':
				case 'ltmp2':
				case 'srchtag':
				case 'qstag':
				case 'tag1':
				case 'tag2':
				case 'tag3':
				case 'tag4':
				case 'tag5':
				case 'tag6':
				case 'tag7':
				case 'tag8':
                case 'tag9':
			    case 'hphrase':
			    case 'wphrase':
			    case 'rphrase':
			    case 'rphrase2':
			    case 'userref':
			    case 'mphrase':
			    case 'sphrase':
			    case 'husbwifeover1':
			    case 'husbwifeover2':
			    case 'wifehusbover1':
			    case 'wifehusbover2':
				{       // unsupported fields
				    break;
				}       // unsupported fields

				case 'marriednamerule':
				{       // unsupported fields
 				    if ($value == 0)
                        $retval	.= "    <$fld>o=None</$fld>\n";
                    else
 				    if ($value == 1)
                        $retval	.= "    <$fld>" . 
             				"1=Replace wife's surname with husband's surname" .
                            "</$fld>\n";
                    else
                        $retval	.= "    <$fld>" . xmlentities($value) .
                                    "</$fld>\n";
				    break;
				}       // unsupported fields

                case 'idms':
                {       // marriage final status
                    if (array_key_exists($value, self::$statusText))
                        $retval	.= "    <$fld>$value=" . 
                                        self::$statusText[$value] . "</$fld>\n";
                    else
				        $retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    break;
                }       // marriage final status

				default:
				{		// fields passed with no interpretation
				    $retval	.= "    <$fld>" . xmlentities($value) . "</$fld>\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

        // report names of spouses from the associated instances of Name
        if ($this->getHusbPriName())
            $retval     .= $this->husbPriName->toXml('husbpriname', false);
        if ($this->getHusbMarName())
            $retval     .= $this->husbMarName->toXml('husbmarname', false);
        if ($this->getWifePriName())
            $retval     .= $this->wifePriName->toXml('wifepriname', false);
        if ($this->getWifeMarName())
            $retval     .= $this->wifeMarName->toXml('wifemarname', false);

        // handle events that are traditionally in the record itself
        $marEvent       = $this->getMarEvent(false);
        if ($marEvent)
            $retval     .= $marEvent->toXml('marriage',false,$includeCitations);

        $sealEvent       = $this->getSealEvent(false);
        if ($sealEvent)
            $retval     .= $sealEvent->toXml('sealed',false,$includeCitations);

        $marEndEvent    = $this->getMarEndEvent(false);
        if ($marEndEvent)
            $retval     .= $marEndEvent->toXml('marEnded',false,$includeCitations);

        // citations to other internal information
		if ($includeCitations)
		{		// include other citations
		    // report on citations for the this individual never married event 
		    $retval	.=
		    $this->showCitationsXml('nevermarriedcits',
								    Citation::STYPE_NEVERMARRIED,
								    false);
		    // report on citations for the Marriage Note event
		    $retval	.=
		    $this->showCitationsXml('marriagenotecits',
								    Citation::STYPE_MARNOTE,
								    false);
		    // report on citations for the Never Married event	     
		    $retval	.=
		    $this->showCitationsXml('nevermarriedcits',
								    Citation::STYPE_MARNEVER,
								    false);
		    $retval	.=
		    // report on citations for the no children event
		    $this->showCitationsXml('nochildrencits',
								    Citation::STYPE_MARNOKIDS,
								    false);
		}		// include citations

        // report on children
		$children	    = $this->getChildren();
		$numChildren	= count($children);
		if ($includeChildren)
		{		// include children
		    // report on the children
		    $retval	.= "  <children count='$numChildren'>\n";
		    try {
				foreach($children as $index => $childr)
				{
				    $childr->save(true);
				    $child	= $childr->getPerson();
				    $parms	= array('idcr'	=> $childr->getIdcr(),
									'print'	=> false);
				    $retval	.= $child->toXml('child', $parms);
				}		// loop through children
		    } catch(Exception $e) {
				$retval	.= "<msg>" . $e->getMessage() . "</msg>\n";
		    }
		    $retval	.= "  </children>\n";
		}		// include children
		else
		{
		    $retval	.= "  <children count='$numChildren'/>\n";
		}

		// report on the other events
		if ($includeEvents)
		{		// include other events
		    $events		= $this->getEvents();
		    $numEvents	= count($events);
		    $retval	.= "  <events count='$numEvents'>\n";
		    foreach($events as $ie => $event)
		    {
				$retval	.= $event->toXml('event', false);
		    }		// loop through events
		    $retval	.= "  </events>\n";
		}		// include other events
}
catch(Exception $e)
{
		$retval	.= "\t<msg>\n";
		$retval	.= "    " . $e->getMessage() . "\n";
		$retval	.= "\t</msg>\n";
}

		// close off the XML document
		if ($top !== null)
		    $retval	.= "</$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Family::toXml

    /********************************************************************
     *  function Family::toJson											*
     *																	*
     *  Emit an JSON representation of the record.						*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two fields.  The internal encoded string value appears in an	*
     *	field with 'c' appended to the field name, while the field		*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *  Each field whose value is an instance of Location appears		*
     *	as two fields.  The internal numeric key value appears in		*
     *	an field with the original field name.  The external textual	*
     *	value appears in an field whose name is the original field		*
     *	name with the "idlr" prefix removed and a "loc" suffix added.	*
     *	For example the field <idlrmar> contains the original			*
     *	numeric code while the field <marloc> contains the external		*
     *	textual value.													*
     *																	*
     *  Each field whose value is an instance of Temple appears			*
     *	as two fields.  The internal numeric key value appears in		*
     *	an field with the original field name.  The external textual	*
     *	value appears in an field whose name is the original field		*
     *	name with the "idtr" prefix removed and a "loc" suffix added.	*
     *	For example the field <idtrseal> contains the original			*
     *	numeric code while the field <sealloc> contains the external	*
     *	textual value.													*
     *																	*
     *	Parameters:														*
     *	    $parms		if true print the output, 						*
     *					if false just return it							*
     *	    $options	flags to indicate which information to include	*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 2nd parameter						*
     ********************************************************************/
    const	TOJSON_INCLUDE_CHILDREN		= 1;
    const	TOJSON_INCLUDE_CITATIONS	= 2;
    const	TOJSON_INCLUDE_EVENTS		= 4;

    function toJson($print	= true,
				    $options	= self::TOJSON_INCLUDE_CHILDREN)
    {
		$idmr			= $this->row['idmr'];
		$includeChildren	= $options & self::TOJSON_INCLUDE_CHILDREN;
		$includeCitations	= $options & self::TOJSON_INCLUDE_CITATIONS;
		$includeEvents		= $options & self::TOJSON_INCLUDE_EVENTS;

		// the current user is an owner of this family record if
		// the current user is an owner of both the husband and the wife
		$idirhusb	= $this->row['idirhusb'];
		$idirwife	= $this->row['idirwife'];
		$isOwner	= ($idirhusb == 0 || 
						   RecOwner::chkOwner($idirhusb, 'tblIR')) &&
						  ($idirwife == 0 || 
						   RecOwner::chkOwner($idirwife, 'tblIR'));
		$retval		= '';
		$comma		= "{\n";

		try {
		    foreach($this->row as $fld => $value)
		    {		// loop through all fields
				switch($fld)
				{
					case 'mard':			// displayed in Marriage Event
					case 'marsd':			// displayed in Marriage Event
					case 'idlrmar':			// displayed in Marriage Event
					case 'idar':			// displayed in Marriage Event
					case 'notes':			// displayed in Marriage Event
					case 'marendd':			// displayed in Marriage Ended Event
					case 'marendsd':		// displayed in Marriage Ended Event
					case 'husbsurname':	    // displayed in Name records
					case 'husbgivenname':	// displayed in Name records
					case 'husbmarrsurname':	// displayed in Name records
					case 'husbbirthsd':	    // displayed in Name records
					case 'wifesurname':	    // displayed in Name records
					case 'wifegivenname':	// displayed in Name records
					case 'wifemarrsurname':	// displayed in Name records
					case 'wifebirthsd':	    // displayed in Name records
					case 'wifebirthsd':	    // displayed in Name records
					case 'seald':			// displayed in Sealed Event
					case 'sealsd':			// displayed in Sealed Event
					case 'idtrseal':		// displayed in Sealed Event
					case 'sealnote':		// displayed in Sealed Event
					case 'ldss':			// displayed in Sealed Event
					{       // obsolete fields no longer displayed
					    break;
					}       // obsolete fields no longer displayed

					case 'ltmp1':
					case 'ltmp2':
					case 'srchtag':
					case 'qstag':
					case 'tag1':
					case 'tag2':
					case 'tag3':
					case 'tag4':
					case 'tag5':
					case 'tag6':
					case 'tag7':
					case 'tag8':
	                case 'tag9':
				    case 'hphrase':
				    case 'wphrase':
				    case 'rphrase':
				    case 'rphrase2':
				    case 'userref':
				    case 'mphrase':
				    case 'sphrase':
				    case 'husbwifeover1':
				    case 'husbwifeover2':
				    case 'wifehusbover1':
				    case 'wifehusbover2':
					{       // unsupported fields
					    break;
                    }       // unsupported fields

				    default:
				    {		// fields passed back with no interpretation
						$retval	.= $comma . "\t\"$fld\":\t" .
								       json_encode($value);
						$comma		= ",\n";
						break;
				    }		// fields passed back with no interpretation
				}		// switch on field name
		    }		// loop through all fields

	        // report names of spouses from the associated instances of Name
	        if ($this->getHusbPriName())
                $retval     .= ',"husbpriname":' .
                                $this->husbPriName->toJson(false);
	        if ($this->getHusbMarName())
                $retval     .= ',"husbmarname":' . 
                                $this->husbMarName->toJson(false);
	        if ($this->getWifePriName())
                $retval     .= ',"wifepriname":' . 
                                $this->wifePriName->toJson(false);
	        if ($this->getWifeMarName())
                $retval     .= ',"wifemarname":' . 
                                $this->wifeMarName->toJson(false);

	        // handle events that are traditionally in the record itself
	        $marEvent       = $this->getMarEvent(false);
	        if ($marEvent)
	            $retval     .= ',"married":' . $marEvent->toJson(false,$includeCitations);

	        $sealEvent       = $this->getSealEvent(false);
	        if ($sealEvent)
	            $retval     .= ',"sealed":' . $sealEvent->toJson(false,$includeCitations);

	        $marEndEvent    = $this->getMarEndEvent(false);
	        if ($marEndEvent)
	            $retval     .= ',"marEnded":' . $marEndEvent->toJson(false,$includeCitations);

	        // citations to other internal information
		    if ($includeCitations)
		    {		// include citations
				// report on citations for the this individual never married event 
				$retval	.=
						$this->showCitationsJson(Citation::STYPE_NEVERMARRIED,
										 false);
				// report on citations for the Marriage Note event
				$retval	.=
						$this->showCitationsJson(Citation::STYPE_MARNOTE,
										 false);
				// report on citations for the Never Married event	     
				$retval	.=
						$this->showCitationsJson(Citation::STYPE_MARNEVER,
										 false);
				// report on citations for the no children event
				$retval	.=
						$this->showCitationsJson(Citation::STYPE_MARNOKIDS,
										 false);
		    }		// include citations

		    $children		= $this->getChildren();
		    $numChildren	= count($children);
		    if ($includeChildren && $numChildren > 0)
		    {		// include children
				$comma			= ",\n";
				// report on the children
				try {
				    foreach($children as $index => $childr)
				    {
						$childr->save(true);
						$child		= $childr->getPerson();
						$parms		= array('idcr'	=> $childr->getIdcr(),
										'print'	=> false);
						$retval		.= $comma .
									   "\"child$index\":\t" .
									   $child->toJson($parms);
				    }		// loop through children
				} catch(Exception $e) {
				    $retval	.= $comma . "\n\"msg\":\t" .
						       json_encode($e->getMessage());
				}
		    }		// include children

		    // report on the other events
		if ($includeEvents)
		{		// include other events
		    $events		= $this->getEvents();
		    $numEvents	= count($events);
		    foreach($events as $ie => $event)
		    {
				$retval	.= $event->toJson(false);
		    }		// loop through events
		}		// include other events
		}
		catch(Exception $e)
		{
		    $retval	.= $comma . "\t\"msg\":\t" .
						   json_encode($e->getMessage());
		}

		// close off the JSON document
		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";

		if ($print)
		    print $retval;
		return $retval;
    }		// function Family::toJson

    /********************************************************************
     *  function Family::showCitationsXml								*
     *																	*
     *  Given the description of a family event identify the			*
     *  source citations for that event in the form of XML				*
     *																	*
     *	Input:															*
     *	    $top		tag name for top element						*
     *	    $citType	citation type in tblSX							*
     *	    $print		if true print the XML							*
     *																	*
     *	Returns:														*
     *	    The XML as a string											*
     ********************************************************************/
    function showCitationsXml($top,
						      $citType,
						      $print	= true)
    {
		// query the database
		$citparms	= array('idime'	=> $this->row['idmr'],
								'type'	=> $citType);
		$citations	= new RecordSet('Citations', $citparms);
		$retval		= '';
		if ($citations->count() > 0)
		{		// found at least one citation record
		    $retval	.= "<$top>\n";

		    foreach($citations as $idsx => $citation)
		    {		// loop through all citation records
				$retval	.= $citation->toXml('citation', false);
		    }		// loop through all citation records

		    $retval	.= "</$top>\n";
		}		// found at least one citation record

		if ($print)
		    print $retval;
		return $retval;
    }		// function Family::showCitationsXml

    /********************************************************************
     *  function Family::showCitationsJson								*
     *																	*
     *  Given the description of a family event identify the			*
     *  source citations for that event in the form of JSON				*
     *																	*
     *	Input:															*
     *	    $citType	citation type in tblSX							*
     *	    $print		if true print the JSON							*
     *																	*
     *	Returns:														*
     *	    The JSON as a string										*
     ********************************************************************/
    function showCitationsJson($citType,
						       $print	= true)
    {
		// query the database
		$citparms	= array('idime'	=> $this->row['idmr'],
								'type'	=> $citType);
		$citations	= new RecordSet('Citations', $citparms);
		$retval		= '';

		if ($citations->count() > 0)
		{		// found at least one citation record
		    $retval		= ",\n\"citlist$citType\":\n{\n";
		    $comma		= '';
		    foreach($citations as $idsx => $citation)
		    {		// loop through all citation records
				$retval		.= $comma;
				$retval		.= "\"$idsx\":\t" . $citation->toJson(false);
				$comma		= ",\n";
		    }		// loop through all citation records
		    $retval		.= "\n}\n";
		}		// found at least one citation record

		if ($print)
		    print $retval;
		return $retval;
    }		// function Family::showCitationsJson

    /********************************************************************
     *	function Family::get											*
     *																	*
     *	Get the value of a field from the object.						*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to get							*
     *																	*
     *	Returns:														*
     *			previous value of the field								*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
		$fieldLc	    = strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'name':
				return $this->getName(array('includedate' => true));

		    case 'mardate':
		    {
				$date	= new LegacyDate($this->row['mard']);
				return $date->toString();
		    }

		    case 'marloc':
            {
                $idlrmar    = $this->get('idlrmar');
				if ($idlrmar > 1)
				{		// not the null location
				    $marloc	= new Location(array('idlr' => $idlrmar));
				    return $marloc->getName();
				}		// not the null location
				else
				    return '';
		    }

		    default:
		    {		// other fields
				return parent::get($fieldLc);
		    }		// other fields
		}			// act on specific field
    }		// function Family::get

    /********************************************************************
     *	function Family::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied parms is compatible with	*
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
        global  $warn;

		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'mard':
		    case 'marendd':
		    case 'seald':
		    {		// legacy internal date format
				$sdname	= substr($field, 0, strlen($field)-1) . 'sd';
				$oldvalue		= $this->row[$field];
				if ($value instanceof LegacyDate)
				{	// parameter is an instance of LegacyDate
				    $date	= $value;
				}	// parameter is an instance of LegacyDate
				else
				{	// convert the value to instance of LegacyDate
				    $date	= new LegacyDate(' ' . $value);
				}	// convert the value to instance of LegacyDate
				parent::set($field, $date->getDate());
				parent::set($sdname, $date->getSortDate());
				return $oldvalue;
		    }		// legacy internal date format

		    case 'idlrmar':
		    {		// value is IDLR
				if (is_int($value) || ctype_digit($value))
				{
				    $tmp	= new Location(array('idlr' => $value));
				    if ($tmp->isExisting())
						return parent::set($field, $value);
				}
				else
                {
				    $warn   .= "<p>Family::set: " . 
                        "invalid value '$value' for field '$field'</p>\n";
                    return $this->row[$fieldLc];
                }
		    }		// value is IDLR

		    case 'idar':
		    {		// value is IDAR
				if (is_int($value) || ctype_digit($value))
				    return parent::set($field, $value);
				else 
                {
				    $warn   .= "<p>Family::set: " . 
                        "invalid value '$value' for field '$field'</p>\n";
                    return $this->row[$fieldLc];
                }
		    }		// value is IDAR

		    case 'husbprefmar':
		    case 'wifeprefmar':
		    case 'templetag':
		    case 'tag1':
		    case 'tag2':
		    case 'tag3':
		    case 'tag4':
		    case 'tag5':
		    case 'tag6':
		    case 'tag7':
		    case 'tag8':
		    case 'tag9':
		    case 'taggroup':
		    case 'srchtag':
		    case 'qstag':
		    case 'remindertag':
		    case 'notmarried':
		    case 'nochildren':
		    case 'alreadyused':
		    {		// value is no (0) or yes (1)
				if ($value == 0 || $value == 1)
				{
				    return parent::set($field, $value);
				}
                else
                {
				    $warn   .= "<p>Family::set: " . 
                        "invalid value '$value' for field '$field'</p>\n";
                    return $this->row[$fieldLc];
                }
		    }		// value is no (0) or yes (1)

		    case 'husbsurname':
		    case 'husbgivenname':
		    case 'husbbirthsd':
		    case 'wifesurname':
		    case 'wifegivenname':
		    case 'wifebirthsd':
            {		// fields maintained through method setName
                $oldvalue           = $this->row[$fieldLc];
                if ($this->row['idmr'] == 0)
                    $this->row[$fieldLc]    = $value;
                return $oldvalue;
		    }		// fields maintained through method setName

		    default:
		    {		// no validation implemented yet
				return parent::set($field, $value);
		    }		// no validation implemented yet
		}		// switch on field name
    }		// function Family::set

    /********************************************************************
     *  function Family::dump											*
     *																	*
     *  If debug output is enabled, display a diagnostic dump of the	*
     *  contents of a database record in the web page.					*
     *																	*
     *  Input:															*
     *	    $label		descriptive identifier for record				*
     *	    $force		if true output even if $debug false				*
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
		    $label	= $this->getName();

		$output		= '';
        $output	    .= "<p class='label'>$label</p>\n";
	    $output	    .= "<table border='1'>\n";
	    foreach($this->row as $key => $value)
	    {
			$output	.= "<tr>\n";
			$output	.= "<th class='label'>\n$key</th>\n";
			$output	.= "<td class='dataleft'>\n$value";

			if (strlen($value) > 0)
			switch($key)
			{
			    case 'mard':
			    case 'marendd':
			    case 'seald':
			    {
					$olddebug	= $debug;
					$debug		= false;
					$date		= new LegacyDate($value);
					$output		.= ' = ' . $date->toString();
					$debug		= $olddebug;
					break;
			    }		// dates

			    case 'idlrmar':
			    {
					$olddebug	= $debug;
					$debug		= false;
					$loc		= new Location(array('idlr' => $value));
					$output		.= ' = ' . $loc->toString();
					$debug		= $olddebug;
					break;
			    }		// dates

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
		    $warn		.= $output;
		return $output;
    }		// function Family::dump

    /********************************************************************
     *	function Family::save											*
     *																	*
     *  Save changes made to the Family record into the					*
     *  database.														*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format.											*
     *					if a string use it as tag name of top tag		*
     *					of XML output									*
     *																    *
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
		global	$connection;

		// return value
		$count		= false;

		// get current date and time for added and updated fields in record
		$now		= getdate();
		$today		= $now['year'] * 10000 + $now['mon']*100 + $now['mday'];
		$currtime	= $now['hours'] . ':' . $now['minutes'];

		// determine top level tag name for XML output
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

		// validate references to other tables
		// ensure that fields in this record which represent locally
		// cached copies of fields from other records are synchronized
		// before saving.
		$idirhusb	= $this->row['idirhusb'];
		if ($idirhusb > 0)
		{		// husband defined
		    if (is_null($this->husband))
		    {		// husband defined but not allocated
				$this->husband	= new Person(array('idir' => $idirhusb));
		    }		// husband defined but not allocated

		    parent::set('husbgivenname',
						     $this->husband->getGivenName());
		    parent::set('husbsurname',
						     $this->husband->getSurname());
		    parent::set('husbbirthsd',
						     $this->husband->get('birthsd'));
		    $this->husband->save($xml);
		    parent::set('idirhusb', $this->husband->getIdir());
		}		// husband defined

		$idirwife	= $this->row['idirwife'];
		if ($idirwife > 0)
		{		// wife defined
		    // implement wife's married name
		    if ($this->row['marriednamerule'] == 1 &&
				$this->row['husbsurname'] != '')
		    {		// wife takes husband's surname
				parent::set('wifemarrsurname',
								 $this->get('husbsurname'));
		    }		// wife takes husband's surname
		    else
		    {		// wife keeps surname
				parent::set('wifemarrsurname',
								 $this->get('wifesurname'));
		    }		// wife keeps surname

		    try {
				$wife	= new Person(array('idir' => $idirwife));
				parent::set('wifegivenname',	$wife->getGivenName());
				parent::set('wifesurname',	$wife->getSurname());
				parent::set('wifebirthsd',	$wife->get('birthsd'));
		    } catch (Exception $e) {
				$wife	= new Person();	
				$wife->set('givenname',		$this->get('wifegivenname'));
				$wife->set('surname',		$this->get('wifesurname'));
				$birthsd			= $this->row['wifebirthsd'];
				if ($birthsd != -99999999)
				{
				    $by		= floor($birthsd/10000);
				    $birthsd	-= 10000*$by;
				    $bm		= floor($birthsd/100);
				    $bd		= $birthsd % 100;
				    if ($bm > 0)
						$wife->set('birthd',
									$by . '/' . $bm . '/' . $bd);
				    else
						$wife->set('birthd',
									'about ' . $by);
				}
				$wife->save($xml);
				parent::set('idirwife', $wife->getIdir());
		    }

		}		// wife defined

		// validate IDLRMar
		$idlrmar	= $this->row['idlrmar'];
		if ($idlrmar > 1)
		{		// 1 is default empty location
		    $marloc		= new Location(array('idlr' => $idlrmar));
		}		// 1 is default empty location

		// To Do
		$idms		= $this->row['idms'];
		$idar		= $this->row['idar'];
		$idtrseal	= $this->row['idtrseal'];

		if ($this->marEvent)
		{		// marriage event may have been modified
		    parent::set('mard', $this->marEvent->get('eventd'));
		    parent::set('marsd', $this->marEvent->get('eventsd'));
		    parent::set('idlrmar', $this->marEvent->get('idlrevent'));
		}		// marriage event may have been modified

		// insert the record into the database if it does not already
		// exists, otherwise update those fields that need to be updated
		if ($this->needInsert)
		{		// create new record
		    $fldnames	= '';
		    $values	= '';
		    $sqlParms	= array('today'		=> $today,
								    'currtime'	=> $currtime);
		    foreach($this->row as $fld => $value)
		    {
				switch($fld)
				{	            // act on field name
				    case 'id':
				    {	        // auto-increment key of record
						// set by SQL server
						break;
				    }	        // auto-increment key of record

				    case 'idmr':
				    {	        // external key of record
						// set by SQL code
						break;
				    }	        // external key of record 

				    case 'added':
				    case 'addedtime':
				    case 'updated':
				    case 'updatedtime':
				    {	        // date and time stamps
						// set by SQL code
						break;
				    }	        // date and time stamps

				    default:
				    {	        // other fields
						$fldnames		.= ", `$fld`"; 
						$values			.= ", :$fld";
						$sqlParms[$fld]		= $value;
						break;
				    }	        // other fields
				}	            // switch on field name
		    }		            // loop through all fields in record

		    // create the command to insert the new record into the database
		    $insert	= 'INSERT INTO tblMR ' .
    				'(IDMR, Added, Addedtime, Updated, UpdatedTime' .
    				$fldnames . ') ' . 
    				'SELECT 1 + COALESCE((SELECT MAX(IDMR) FROM tblMR), 0),' .
    							':today, :currtime, ' .
    							':today, :currtime ' . $values;

		    // insert the record into the database
		    $stmt		    = $connection->prepare($insert);
		    $insertText		= debugPrepQuery($insert, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		            // success

				$count		= $stmt->rowCount();
				if ($xml)
				   print "<$tagname count='$count'>" .
								$insertText . 
				   	"</$tagname>\n";

				if ($debug)
				{
				   $warn	.= "<p>Family::save: " . __LINE__ .
									" $insertText</p>\n" .
								   "<p>count=$count</p>\n";
				}

				$this->changed		= array();	// reset
				$this->needInsert	= false;	// no longer need insert

				// get the record identifier set by the database server for
				// the new record
				// update primary key value in record, as it was previously 0
				$id			= $connection->lastInsertId();
				$this->row['id']	= $id;

				// obtain the updated record from the database
				// so the memory copy will include values set by the
				// SQL server or calculated in the INSERT statement
				$query		= "SELECT * FROM tblMR WHERE ID=:id";
				$rParms		= array('id'		=> $id);
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $rParms);
				if ($stmt->execute($rParms))
                {		        // success
				    $this->row		= $stmt->fetch(PDO::FETCH_ASSOC);
				    $idmr		= $this->row['idmr'];
				    if ($xml)
				       print "<$tagname idmr=\"$idmr\">$queryText</$tagname>\n";

				    if ($debug)
				       $warn		.= "<p>Family::save: " . __LINE__ .
								       " idmr=$idmr query='$queryText'</p>\n";

				    // log the update
				    $this->log($insert, $sqlParms);

				    // ensure any children added while the instance was
				    // incomplete have the correct IDMR
				    $children		= $this->getChildren();
				    foreach($children as $child)
				    {
				       $child->set('idmr', $idmr);
				       $child->save($xml);
				    }

				    // make the current user an owner of the new family
				    $this->addOwner();
				}		        // success
				else
				{		        // error performing query
				   throw new Exception("Family::save: '$queryText', " .
				   		    print_r($stmt->errorInfo(),true));
				}		        // error performing query
		    }		            // success
		    else
		    {		            // error inserting record
				throw new Exception("Family::save: '$insertText', " .
								    print_r($stmt->errorInfo(),true));
		    }		            // error inserting record
		}		                // create new record
		else
		{		                // update existing record
		    $update		= "UPDATE tblMR SET ";
		    $sqlParms		= array();
		    $set		= '';
		    $comma		= '';
		    $nameChanged	= false;
		    foreach($this->changed as $fld => $value)
		    {
				switch($fld)
				{	            // act on field name
				    case 'id':
				    {	        // auto-increment key of record
						// used in where clause
						break;
				    }	        // auto-increment key of record

				    case 'idmr':
				    case 'added':
				    case 'addedtime':
				    {	        // other fields that cannot be changed
						break;
				    }	        // fields that cannot be changed

				    case 'updated':
				    case 'updatedtime':
				    {	        // update date & time stamp
						break;
				    }	        // date stamp

				    default:
				    {	        // other fields
						$set			.= $comma . "`$fld`=:$fld";
						$sqlParms[$fld]		= $value;
						$comma			= ', ';
						break;
				    }	        // other fields
				}	            // switch on field name
		    }		            // loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		            // changes made to contents of record
				// create command to update existing record
				$update	.= $set . ', Updated=:today, UpdatedTime=:currtime' .
								  ' WHERE IDMR=:idmr';
				$sqlParms['today']	    = $today;
				$sqlParms['currtime']	= $currtime;
				$sqlParms['idmr']	    = $this->row['idmr'];

				// update the record
				$stmt		= $connection->prepare($update);
				$updateText	= debugPrepQuery($update, $sqlParms);
				if ($stmt->execute($sqlParms))
				{		        // success
				    $count		= $stmt->rowCount();
				    // update successful
				    if ($xml)
						print "<$tagname count='$count'>" . $updateText . 
								    "</$tagname>\n";

				    if ($debug)
				    {
						$warn	.= "<p>Family::save: " . __LINE__ .
									" $updateText</p>\n";
						$warn	.= "<p>count=$count</p>\n";
				    }

				    // log the update
				    logSqlUpdate($update,
								 $sqlParms,
								 $this->row['id'],
								 json_encode($this->former),
								 json_encode($this->row));
				}		        // success
				else
				{		        // error updating record
				    throw new Exception("Family::save: '$updateText', " .
									print_r($stmt->errorInfo(),true));
				}		        // error updating record

		    }		            // changes made
		}		                // update existing record

        if ($this->row['idirhusb'] != 0)
        {                       // husband present 
		    // update husbands married name index entry
            if ($this->row['husbsurname'] != $this->row['husbmarrsurname'])
		    {		            // update husband's married name index
				$altNameRec	= new Name(array('idir' => $this->husband,
										     'idmr' => $this));
				$altNameRec->save($xml);
		    }		            // update husband's married name index
        }                       // husband present 

        if ($this->row['idirwife'] != 0)
        {                       // wife present 
		    // update wife's married name index entry
		    if ($this->row['wifesurname'] != $this->row['wifemarrsurname'])
		    {		            // update wife's married name index
				$altNameRec	= new Name(array('idir' => $wife,
										     'idmr' => $this));
				$altNameRec->save($xml);
		    }		            // update wife's married name index
        }                       // wife present 

		// if anything has changed in the children, save it
		if ($this->children)
		{		                // have loaded list of children
		    if ($xml)
				print "<children count='" . count($this->children) . "'>\n";
		    foreach($this->children as $i => $childr)
		    {		            // loop through loaded list of children
				if ($xml)
				{	            // output XML
				    if ($childr->isExisting())
						print "<child idcr='" . $childr->getIdcr() .
								"' idir='" . $childr->getIdir() .
								"' order='" .  $childr->get('order') . "'>\n";
				    else
						print "<child idir='" . $childr->getIdir() .
								"' order='" .  $childr->get('order') . "'>\n";
				}	            // output XML
				$childr->save($xml);	// update database
				if ($xml)
				    print "</child>\n";
		    }		            // loop through loaded list of children
		    if ($xml)
				print "</children>\n";
		}		                // have loaded list of children
		return	$count;
    }		// function Family::save

    /********************************************************************
     *  function Family::merge											*
     *																	*
     *  It has been determined that as a result of merging two			*
     *  individuals there are now two separate family records that have	*
     *  the same husband and wife.  These two families are merged by	*
     *  moving the children, events, and citations from the second 		*
     *  family into the current family.									*
     *																	*
     *  Note that there is nothing wrong intrinsically with having two 	*
     *  family records connecting the same two spouses:  for example	*
     *  Richard Burton and Elizabeth Taylor.  However in this case		*
     *  we are taking two spouses who were previously believed to be	*
     *  distinct individuals, and merging them, so the two marriages	*
     *  are actually one and the same.  This most commonly occurs		*
     *	because one spouse has first been merged with another			*
     *	individual, and then the  other spouse is merged with an		*
     *	existing spouse.												*
     *																	*
     *  Input:															*
     *	   $oldFamily	the source of the merge information				*
     ********************************************************************/
    function merge($oldFamily)
    {
		global	$connection;
		$newidmr	= $this->getIdmr();
		$oldidmr	= $oldFamily->getIdmr();
?>
      <p>Merge family IDMR=<?php print $oldidmr; ?> into 
		family IDMR=<?php print $newidmr; ?> because of now duplicate spouses.
<?php

		// move children of family 2 into family 1
		$children	= new RecordSet('Children', array('idmr' => $oldidmr));
		$count		= $children->update(array('idmr' => $newidmr));
		if ($count > 0)
		{		// at least 1 record updated
?>
      <p>Move <?php print $count; ?> children from family
		IDMR=<?php print $oldidmr;?> to family IDMR=<?php print $newidmr;?>.
<?php
		    // TODO: check for duplicate children, only retain one Child

		}		// at least 1 record updated

		// selectively merge facts

		// merge marriage date and location
		$date	= new LegacyDate($oldFamily->get('mard'));
		$loc	= new Location(array('idlr' => $oldFamily->get('idlrmar')));
		if ($this->get('mard') == '' &&
		    $this->get('idlrmar') <= 1)
		{		// new family does not have a marriage event
		    if ($oldFamily->get('mard') != '' ||
				$oldFamily->get('idlrmar') > 1)
		    {		// fill in from second family
				$this->set('mard', $oldFamily->get('mard'));
				$this->set('idlrmar', $oldFamily->get('idlrmar'));
?>
      <p>Marriage date set to <?php print $date->toString(); ?> and
		 marriage location set to <?php print $loc->toString(); ?>.
<?php
				// update citations associated with old family to
				// reference the new family
				$citparms	= array('idmr'	=> $oldidmr);
				$setparms	= array('idime'	=> $newidmr);
				try {
				    $citations	= new RecordSet('Citations', $citparms);
				    $count	= $citations->update($setparms,
										     false);
				    if ($count > 0)
				    {		// at least 1 record updated
?>
      <p>Moved <?php print $count; ?> source citations for marriage event
				to point to family IDMR=<?php print $newidmr;?>.
<?php
				    }		// at least 1 record updated
				} catch(Exception $e) {
?>
      <p class='message'><?php print $e->getMessage(); ?>
      </p>
<?php
				}
		    }		// fill in from second family
		}		// new family does not have a marriage date
		else
		{		// save second marriage info as alternate marriage event
		    $parms	= array('idtype'	=> Event::IDTYPE_MAR,
								'idir'		=> $newidmr,
								'idet'		=> Event::ET_MARRIAGE,
								'eventd'	=> $date,
								'idlrevent'	=> $oldFamily->get('idlrmar'));
		    $event	= new Event($parms);
		    $event->save('p');	// write to database
		    $ider	= $event->getIder();
?>
      <p>Create alternate marriage event=<?php print $ider;?> to retain
		    marriage <?php print $date->toString(); ?>  at
		    <?php print $loc->toString(); ?> information from old family record
		    IDMR=<?php print $oldidmr; ?>.
<?php
		    // move any citations associated with the old marriage
		    $citparms	= array('idime'	=> $oldidmr,
								'type'	=> 20);
		    $setparms	= array('idime'	=> $ider,
								'type'	=> 31);
		    try {
				$citations	= new RecordSet('Citations',$citparms);
				$count		= $citations->update($setparms,
										     false);
				if ($count > 0)
				{		// at least 1 record updated
?>
      <p>Update <?php print $count; ?> source citations
		    to point to alternate marriage event=<?php print $ider;?>.
<?php
				}		// at least 1 record updated
		    } catch(Exception $e) {
?>
      <p class='message'><?php print $e->getMessage(); ?>
      </p>
<?php
		    }
		}		// save second marriage info

		// move any remaining citations
		$citparms	= array('idmr'	=> $oldidmr);
		$setparms	= array('idime'	=> $newidmr);
		try {
		    $citations	= new CitationSet($citparms);
		    $count	= $citations->update($setparms,
									     false);
		    if ($count > 0)
		    {		// at least 1 record updated
?>
      <p>Move <?php print $count; ?> source citations for marriage facts
		to point to family IDMR=<?php print $newidmr;?>.
<?php
		    }		// at least 1 record updated
		} catch(Exception $e) {
?>
      <p class='message'><?php print $e->getMessage(); ?>
      </p>
<?php
		}		// catch

		// move any remaining marriage facts
		$events	= $oldFamily->getEvents();
		for ($ie = 0; $ie < count($events); $ie++)
		{
		    $event	= $events[$ie];
		    $event->setIdir($newidmr);
		    $event->save('p');
		}		// loop through marriage events/facts

		// apply changes to database
		$this->save('p');
    }		// function Family::merge
}		// class Family
