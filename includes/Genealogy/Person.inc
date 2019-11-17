<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Person.inc															*
 *																		*
 *  Definition of a class representing an individual in a genealogical	*
 *  database.  This class provides access to the information in a row	*
 *  of the table tblIR.													*
 *																		*
 *		CREATE TABLE `tblIR` (											*
 *		    `ID`				INT(10) NOT NULL AUTO_INCREMENT,		*
 *		    `IDIR`				INT(10) DEFAULT NULL,					*
 *		    `FSID`				VARCHAR(255) DEFAULT NULL,				*
 *		    `Surname`			VARCHAR(120) DEFAULT NULL,			    *
 *		    `SoundsLike`		VARCHAR(4) DEFAULT NULL,				*
 *		    `GivenName`			VARCHAR(120) DEFAULT NULL,			    *
 *		    `Prefix`			VARCHAR(120) DEFAULT NULL,			    *
 *		    `Title`				VARCHAR(120) DEFAULT NULL,				*
 *		    `NameNote`			LONGTEXT,							    *
 *		    `Gender`			TINYINT(3) UNSIGNED DEFAULT NULL,	    *
 *		    `BirthD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `BirthSD`			INT(10) DEFAULT NULL,					*
 *		    `IDLRBirth`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `ChrisD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `ChrisSD`			INT(10) DEFAULT NULL,					*
 *		    `IDLRChris`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `ChrTerm`			VARCHAR(100) DEFAULT NULL,				*
 *		    `DeathD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `DeathSD`			INT(10) DEFAULT NULL,					*
 *		    `IDLRDeath`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `BuriedD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `BuriedSD`			INT(10) DEFAULT NULL,					*
 *		    `IDLRBuried`		INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `Cremated`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `IDARBirth`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `IDARChris`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `IDARDeath`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `IDARBuried`		INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `BirthNote`			LONGTEXT,								*
 *		    `ChrisNote`			LONGTEXT,								*
 *		    `DeathNote`			LONGTEXT,								*
 *		    `BuriedNote`		LONGTEXT,								*
 *		    `Living`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `BaptismD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `BaptismSD`			INT(10) DEFAULT NULL,					*
 *		    `BaptismKind`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `IDTRBaptism`		INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `BaptismNote`		LONGTEXT,								*
 *		    `LDSB`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `ConfirmationD`		VARCHAR(100) DEFAULT NULL,				*
 *		    `ConfirmationSD`	INT(10) DEFAULT NULL,					*
 *		    `ConfirmationKind`	TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `IDTRConfirmation`	INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `ConfirmationNote`	LONGTEXT,								*
 *		    `LDSC`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `InitiatoryD`		VARCHAR(100) DEFAULT NULL,				*
 *		    `InitiatorySD`		INT(10) DEFAULT NULL,					*
 *		    `IDTRInitiatory`	INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `InitiatoryNote`	LONGTEXT,								*
 *		    `LDSI`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `EndowD`			VARCHAR(100) DEFAULT NULL,				*
 *		    `EndowSD`			INT(10) DEFAULT NULL,					*
 *		    `IDTREndow`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `EndowNote`			LONGTEXT,								*
 *		    `LDSE`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `TempleTag`			TINYINT(3) UNSIGNED DEFAULT NULL		*
 *				1=Baptism,												*
 *				2=Endowment',											*
 *		    `IDMRPref`			INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `IDMRParents`		INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `IDAR`				INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `AncInterest`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `DecInterest`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag1`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag2`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag3`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag4`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag5`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag6`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag7`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag8`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Tag9`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `TagGroup`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `TagAnc`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `TagDec`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `SaveTag`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `SrchTag`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `SrchTagIGI`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `SrchTagRG`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `SrchTagFS`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `qsTag`				TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `ReminderTag`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `ReminderTagDeath`	TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `TreeNum`			SMALLINT(5) DEFAULT NULL,				*
 *		    `LTMP1`				INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `LTMP2`				INT(10) UNSIGNED DEFAULT NULL,			*
 *		    `AlreadyUsed`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `UserRef`			VARCHAR(50) DEFAULT NULL,				*
 *		    `AncestralRef`		VARCHAR(20) DEFAULT NULL,				*
 *		    `Notes`				LONGTEXT,								*
 *		    `References`		LONGTEXT,								*
 *		    `Medical`			LONGTEXT,								*
 *		    `DeathCause`		VARCHAR(255) DEFAULT NULL,				*
 *		    `PPCheck`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Imported`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `Added`				INT(10) DEFAULT NULL,					*
 *		    `AddedTime`			VARCHAR(5) DEFAULT NULL,				*
 *		    `UpDATEd`			INT(10) DEFAULT NULL,					*
 *		    `UpDATEdTime`		VARCHAR(5) DEFAULT NULL,				*
 *		    `Relations`			VARCHAR(20) DEFAULT NULL,				*
 *		    `NeverMarried`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `DirectLine`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `STMP1`				VARCHAR(255) DEFAULT NULL,				*
 *		    `ColorTag`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `IntelliShare`		VARCHAR(50) DEFAULT NULL,				*
 *		    `Private`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `PPExclude`			VARCHAR(50) DEFAULT NULL,				*
 *		    `RGExclude`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `DNA`				LONGTEXT,								*
 *		    `FSSync`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `FSDups`			TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `FSOrdinance`		TINYINT(3) UNSIGNED DEFAULT NULL,		*
 *		    `FSLinks`			LONGTEXT,								*
 *		    PRIMARY KEY (`ID`),											*
 *		    UNIQUE KEY `IDIR`	(`IDIR`),								*
 *		    KEY `Surname`		(`Surname`),							*
 *		    KEY `GivenName`		(`GivenName`) )							*
 *		    ENGINE=InnoDB DEFAULT CHARSET=utf8 							*
 *																		*
 *  History:															*
 *		2010/09/05		Move display of individual event to Event		*
 *		2010/10/11		check result not connection for errors			*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global							*
 *		2010/11/15		use LegacyDate::toString						*
 *		2010/11/29		use LegacyChild record							*
 *		2010/12/11		add getChildStatus method						*
 *						add getNextFootnote method						*
 *		2010/12/12		Replace obsolete LegacyDate::dateToString with	*
 *						toString										*
 *		2010/12/20		throw an exception for bad parameters to		*
 *						constructor instead of 'die'ing.				*
 *		2010/12/26		add method setSurname							*
 *						add method getChildRecord						*
 *		2010/12/28		set fields added, addedtime, updated,			*
 *						updatedtime on creation of new instance			*
 *						Honor "kind" flag for IDTRBaptism and			*
 *						IDTRConfirmation								*
 *		2011/01/02		Ensure proper defaults for all fields in new	*
 *						record											*
 *		2011/01/03		Add support for alternate name list				*
 *						o method getNumAltNames							*
 *						o method getNextAltName							*
 *						Ensure surname table tblNR has entry for surname*
 *		2011/01/10		add method setField to filter updates			*
 *		2011/01/11		change signature of Person::delete				*
 *		2011/01/14		add method Person::mergeFrom					*
 *		2011/01/28		add global $connection in mergeFrom				*
 *		2011/02/01		privatize dates in toXml						*
 *		2011/03/06		catch exception from LegacyLocation constructor	*
 *		2011/05/23		display any notes for the name of the individual*
 *		2011/07/01		catch exception on new Temple in toXml			*
 *		2012/01/07		support creating instance with assigned IDIR	*
 *		2012/01/12		permit null first parameter to toXml			*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/03/05		add setGivenName for symmetry					*
 *						in setField handle dates by setting both date	*
 *						and sort date field								*
 *						expand validation of new values in setField		*
 *		2012/03/14		implement link to transcription of specific		*
 *						records in citation								*
 *		2012/04/04		use QueryResponseYyyy.php instead of			*
 *						CensusFormYyyy.php to display census page		*
 *						summary											*
 *		2012/04/12		IDSR not inserted into hyperlink for source name*
 *		2012/05/30		support creation from a record in tblCR			*
 *		2012/08/12		improve documentation of functions using member	*
 *						childr											*
 *						clean up parameter validation in constructor	*
 *						and support creation without database update	*
 *		2012/08/21		in citation table expand recognition of census	*
 *						of Canada citation to permit "district",		*
 *						"subdistrict", and "ward"						*
 *						as alternatives to "dist", "subdist", and "div"	*
 *		2012/08/27		correct setting of ownership to use IDIR rather	*
 *						than ID											*
 *		2012/08/29		use standard SQL INSERT syntax					*
 *						add Person::save method							*
 *		2012/09/01		update name index and family records on name	*
 *						change											*
 *		2012/09/02		constructor no longer inserts database record	*
 *						requires explicit save call						*
 *						use RecOwners::addOwner to manage ownership		*
 *		2012/09/28		do not create database record on 'new'			*
 *		2012/10/01		in Person::save only update those fields		*
 *						of the database record that have been changed	*
 *						Move all functionality for updating the database*
 *						record corresponding to an instance of			*
 *						Person into the Person::save method.			*
 *		2012/10/30		default all IDLRs to 1, not 0					*
 *		2012/11/04		dates may be all numeric, but must be treated as*
 *						strings when writing to the database because 	*
 *						leading zeroes are significant					*
 *		2012/11/09		move updating of names in family record to		*
 *						class LegacyFamily								*
 *		2012/11/22		add method addEvent 							*
 *		2012/11/24		move functionality for most of method addEvent	*
 *						to Event										*
 *		2012/12/23		method save did not update givenname or surname	*
 *		2013/01/11		date fields were set twice during update, once	*
 *						as string with leading zeroes and then as		*
 *						numeric without									*
 *		2013/01/19		method mergeFrom changed to update current		*
 *						record and not return a string					*
 *						change parameter to methods save and delete		*
 *		2013/02/07		permit record with IDIR=0						*
 *						add exception to method getIdir					*
 *		2013/02/15		update tblXI for compatibility with Legacy FT	*
 *		2013/02/26		add information to XML in method save			*
 *		2013/03/03		MySQL SOUNDEX disagrees with PHP soundex		*
 *						function										*
 *						Correct weaknesses in alternate name management	*
 *						by loading alternate names into the object,		*
 *						manipulating them as the object itself is		*
 *						modified and saving the changes only when the	*
 *						object is saved									*
 *		2013/03/13		update primary alternate name record for given	*
 *						name											*
 *		2013/03/25		add methods getFamilies, getParents,			*
 *						getAltNames and getChild.						*
 *						deprecate getNumMarriages, getNextMarriage,		*
 *						getNumParents, getNextParents, getNumAltNames,	*
 *						getNextAltName, getChildRecord, getChildStatus,	*
 *						getCpDad, getCpMom								*
 *		2013/04/03		escape XML characters in SQL update when		*
 *						generating XML output from method save			*
 *		2013/04/21		add method getName								*
 *		2013/05/31		do not fail if method delete is called more		*
 *						than once										*
 *		2013/06/01		remove internal use of deprecated methods		*
 *						rename getMarriages as getFamilies				*
 *						correct function name in error throws in save	*
 *		2013/06/12		display source in new window/tab				*
 *		2013/06/29		add hyperlink for citation to Wesleyan			*
 *						Methodist Baptisms								*
 *		2013/07/07		do not output XML of alternate names during		*
 *						save unless XML output is requested				*
 *		2013/08/02		add option to include families in XML output	*
 *						add option to include parents in XML output		*
 *		2013/08/04		improve wording of exception thrown for bad IDIR*
 *		2013/08/09		base class renamed to Record					*
 *		2013/08/15		do not print debug messages for deletes that	*
 *						did nothing										*
 *		2013/08/18		add hyperlinked footnotes for 1906, 1916, and	*
 *						1921 Censuses									*
 *		2013/11/12		add methods resetFamilies and resetParents		*
 *		2013/11/15		correct date formats for added and updated		*
 *						log reproducible version of INSERT command		*
 *		2013/11/29		use IDIR as key for update in place of ID		*
 *		2014/01/31		remove deprecated Person::getChildRecord,		*
 *						Person::getChildStatus,							*
 *						Person::getCpDad, Person::getCpMom				*
 *						in method toXML display all event inforamtion	*
 *						in <event> subtags, including the 8 events		*
 *						which are implemented in the database as fields	*
 *						in the tblIR record.							*
 *						in method toXML display gender in text			*
 *		2014/02/11		method delete did not log all of the updates	*
 *						made to the database							*
 *		2014/03/04		allow parameters 'male', 'female', and 'unknown'*
 *						to setGender									*
 *		2014/03/14		calculate added and updated date and time in	*
 *						the script rather than in the SQL server		*
 *						remove methods getNumEvents and getNextEvent	*
 *						and replace them with getEvents returning an	*
 *						array of instances of Event						*
 *						use LegacyChild::getChildren					*
 *		2014/03/17		method showCitationTable changed to use CSS		*
 *						rather than table row							*
 *		2014/03/21		use LegacyAltName::getAltNames					*
 *						delete alternate names when deleting record		*
 *						and report number deleted						*
 *						use LegacyAltName::deleteAltNames				*
 *						remove Person::getNumAltNames					*
 *						remove Person::getNextAltName					*
 *		2014/03/26		delete associated blog entries when deleting	*
 *						the individual									*
 *		2014/04/08		LegacyAltName renamed to LegacyName				*
 *						toXML optionally includes instances of			*
 *						LegacyName										*
 *		2014/05/11		add parameter to method addEvent				*
 *						set IDER to zero in pseudo events				*
 *		2014/05/14		add methods to obtain instance of Event			*
 *						for events recorded in the tblIR record			*
 *		2014/05/25		use div.footnote to lay out footnote			*
 *		2014/06/10		change Person::getEvents to return all			*
 *						events											*
 *						set distinct IDET value for cremation			*
 *		2014/08/07		use Citation::deleteCitations instead			*
 *						of SQL to delete citations						*
 *						use Citation::getCitations to obtain			*
 *						list of citations for an event					*
 *		2014/08/24		permit more than one space between 'page' and	*
 *						the page number in a document reference to		*
 *						permit improved sorting of citations by page	*
 *						number											*
 *		2014/08/31		methods to get instances of Event for			*
 *						events which are actually located in this		*
 *						record, did not pass $this pointer to			*
 *						new Event										*
 *		2014/09/04		improve separation of PHP and HTML in			*
 *						static function interpretDetail					*
 *						use case rather than if then else to improve	*
 *						performance and maintainability of static		*
 *						function interpretDetail						*
 *						debug trace message from constructor to use		*
 *						print_r to display parameter					*
 *						minor improvements to parameter checking of		*
 *						static method getWhere							*
 *		2014/09/08		fix crash invoking method_exists for non-object	*
 *						in PHP 5.3 for showCitationTable				*
 *						move HTML interpretation of instance of			*
 *						Citation to Citation::toHtml					*
 *		2014/09/27		class RecOwners renamed to RecOwner				*
 *		2014/10/01		add method addOwner								*
 *		2014/10/03		accept associative array of parameters in		*
 *						constructor										*
 *		2014/10/15		move most of special event handling to			*
 *						class Event so the actual place where			*
 *						the event information is stored can be moved	*
 *						to tblER										*
 *						preferred events are moved to tblER but not		*
 *						until the event is actually updated				*
 *						toXml reported some events twice				*
 *		2014/11/04		change setField for obsolete event field names	*
 *						to update event objects							*
 *		2014/11/17		some individuals have bad values of idmrpref	*
 *						perform a fixup									*
 *		2014/11/18		MDB2 unexpectedly returned row without names	*
 *		2014/11/25		in method getFamilies do not attempt to fix		*
 *						bad preferred family is current user is not		*
 *						authorized to update the record					*
 *		2014/11/30		support expressions combined by the operator OR	*
 *						in static methods getPersons and				*
 *						updatePersons.									*
 *		2014/12/04		permit method getEvents to be called when		*
 *						IDIR value has not yet been initialized			*
 *		2014/12/10		do not attempt to update instances of			*
 *						LegacyFamily during save.  This is now the		*
 *						responsibility of LegacyFamily::__construct		*
 *		2014/12/11		use array constructor for parms to create		*
 *						basic events to make the code easier to read	*
 *						position early childhood events that are in		*
 *						the process of being created after an already	*
 *						created birth event.							*
 *		2014/12/25		missing space in value of getWhere for idir		*
 *						and limit										*
 *						if idir and limit=0 getWhere expression matches	*
 *						no individuals, so returned count value is 0	*
 *		2015/01/01		add options to method getName to include birth	*
 *						and death dates, and/or put surname first		*
 *						add getBirthDate and getDeathDate				*
 *		2015/01/05		toXml displayed two birth events				*
 *		2015/01/06		add method getPreferredParents					*
 *		2015/01/07		ensure that married name records exist when		*
 *						saving record									*
 *		2015/01/08		add parameter to method getNames to limit type	*
 *						of name records to obtain						*
 *						use LegacyName::updateNames to update given		*
 *						name of individual instead of looping through	*
 *						all existing name records for the individual	*
 *		2015/01/14		do not update alternate name records during		*
 *						setField, defer to save							*
 *						add method clearEvent to permit Event			*
 *						to remove an event from this record				*
 *		2015/01/16		getXxxxxEvent methods were creating event		*
 *						records when they should not have				*
 *		2015/02/18		allow list of values in search parameters for	*
 *						getPersons, updatePersons for					*
 *						all text and numeric fields						*
 *						add static method updatePersons					*
 *						in getWhere do not enclose a single comparison	*
 *						in parentheses when combining with OR and		*
 *						do not emit a comparison if sub-array is empty	*
 *						support string tagname parameter for xml flag	*
 *						in function getPersons							*
 *		2015/02/24		support loose match for surname and givenname	*
 *						in functions getPersons and updatePersons		*
 *		2015/03/05		for invalid value of IDMR in an instance of		*
 *						LegacyChild report the problem to admins		*
 *						and do fixup, don't throw exception.			*
 *		2015/03/07		add option to getName to include birth and		*
 *						death locations in result						*
 *						This supports ancestor and descendant reports	*
 *		2015/03/21		eliminate duplicate matching individuals from	*
 *						loose surname matches in getPersons				*
 *						perform surname and givenname matches in		*
 *						getWhere against tblNX, not tblIR				*
 *		2015/03/30		use mdash to separate birth and death dates		*
 *		2015/04/06		provide access to privacy limits				*
 *						do not generate bad SQL for given name check	*
 *						if given name contains bad characters			*
 *		2015/04/08		add ability to search for certain event			*
 *						descriptions, such as occupation, to getPersons	*
 *						ensure that JOIN clauses only appear once		*
 *						include maiden name in response to getName if	*
 *						it is available in the instance, which it will	*
 *						if the instance was created by getPersons with	*
 *						a name search									*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/05/01		remove methods that are used only by the		*
 *						script legacyIndivid.php						*
 *		2015/05/11		correct syntax error in generated SQL from		*
 *						static methods getPersons, updatePersons where	*
 *						given name contains an apostrophe				*
 *		2015/05/15		add simulated fieldname `givenpfx` to			*
 *						search parms for getPersons and updatePersons	*
 *						in getName do not display maiden name if it is	*
 *						the same as the surname							*
 *		2015/05/20		also clear note field in clearEvent				*
 *		2015/06/29		ensure that getXxxxxEvent returns the same		*
 *						instance of Event as getEvents					*
 *		2015/07/03		associated record not set in basic events		*
 *						if they already existed as records in tblER		*
 *		2015/07/05		enhance getPersons so it also extracts the		*
 *						birth and death date from associated events		*
 *						enhance getName so it does not build the		*
 *						entire event structure if the birth and death	*
 *						dates are still in their old location or the	*
 *						instance of Person was built by getPersons		*
 *						It takes over a second to build the event		*
 *						structure, which is overkill if all we want		*
 *						is the birth and death date						*
 *		2015/07/14		remove reference to obsolete field `birth`		*
 *						in method GetName								*
 *						prepare for removal of field `birthsd` by only	*
 *						using it to sort responses from getPersons if	*
 *						there is no instance of birth event in tblER	*
 *						correct error in constructing OR expressions	*
 *						in Person::getWhere								*
 *		2015/07/17		correct error in constructing IDIR exclusion	*
 *						and in unusual given name						*
 *						expressions in Person::getWhere					*
 *		2015/08/04		add check for family tree name					*
 *						add methods setTreeName and getTreeName			*
 *		2015/08/23		expand functionality of setTreeName				*
 *						constructor adds ability to set fields while	*
 *						creating new record								*
 *						always set member altNames to complete array	*
 *						of LegacyName records in method getNames		*
 *		2015/09/07		make getPersons call used by getIndivNamesXml	*
 *						faster by limiting last surname.				*
 *						apply loose to multiple surnames in getWhere	*
 *		2015/09/18		further improve getIndivNamesXml				*
 *		2015/09/26		getIndivNamesXml failed on surname range		*
 *						starting with zero length surname				*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/10/26		getWhere extended to support a range of surnames*
 *		2015/12/08		base class changed to RecordPic					*
 *		2016/03/06		add method getGenderClass						*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2016/06/10		in getWhere search for nicknames in quotes		*
 *						remove duplicate given name search so the		*
 *						nicknames table works							*
 *		2016/08/14		toXml did not handle second parameter key		*
 *						"print" correctly								*
 *		2016/12/08		subquery fails in INSERT statement after		*
 *						MySQL 5.6										*
 *						truncate SOUNDEX to 4 characters				*
 *		2016/12/30		handle array of givennames in getWhere			*
 *		2016/12/31		empty surname was treated as surname not		*
 *						specified in getWhere rather than search for	*
 *						individuals without a surname					*
 *						could not search for a range of surnames with	*
 *						an array of givennames							*
 *						if multiple given names are supplied in this	*
 *						case then for the specific surname only			*
 *						individuals who have ALL of the given name		*
 *						strings present match.  For example "Ma Jane"	*
 *						might match "Mary Jane" or "Emma Jane" or		*
 *						"Margaret Jane" or "Jane Maria" but not just	*
 *						"Mary" or "Jane"								*
 *		2017/01/14		getName sometimes returned null birth date		*
 *		2017/01/17		use method set									*
 *		2017/01/26		improve efficiency of getPersons by surname		*
 *		2017/01/31		add method getRecordJson						*
 *		2017/03/19		some fields missing from initializer array		*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/20		require option to include events in XML			*
 *						and JSON output									*
 *						support incorrect spellings of "Lloyd"			*
 *						parameter list to logSqlUpdate changed			*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/07		class LegacyChild renamed to class Child		*
 *		2017/08/17		class LegacyDontMergeEntry renamed to			*
 *						class DontMergeEntry							*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *						use prepare for new values in updatePersons		*
 *		2017/09/23		reorganize include structure					*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/10		change class LegacyFamily to class Family		*
 *						remove migration support for non-prepared SQL	*
 *						statements from method getWhere					*
 *						use Record::getWhereInt in method getWhere		*
 *		2017/10/13		complete migration to PDO prepared statements	*
 *						rename class LegacyIndiv to class Person		*
 *		2017/10/18		use RecordSet to manage Names					*
 *						use Record::addOwner to manage ownership		*
 *		2017/10/31		use RecordSet to manage events					*
 *		2017/11/05		method delete deleted all citations to events	*
 *						when the Person had no events					*
 *		2017/12/13		static functions getWhere, getPersons, 			*
 *						updatePersons and their support functions		*
 *						replaced by class PersonSet						*
 *		2017/12/18		add support for temporary fields				*
 *		2018/02/10		add support for internationalization to			*
 *						method getName									*
 *		2018/03/21		do not update Name records in setTreeName		*
 *						if the tree name has not changed				*
 *						Surname was reset in Families and old value		*
 *						from Family was copied back to Name record		*
 *		2018/10/26      do not return name of individual if private     *
 *		2018/12/05      functionality of getName moved to class Name    *
 *		2018/12/07      eliminate infinite recursion in getBirthEvent   *
 *		                if debug requested                              *
 *		2019/01/03      name fields in Person obsolete                  *
 *		2019/04/14      do not pass internal date string to LegacyDate  *
 *		                prefixed by space                               *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/05/22      correct bad parms to RecordSet::update          *
 *		2019/06/01      set lastSqlCmd in save and delete               *
 *		                improve JSON outut                              *
 *		2019/07/18      add Person::getPerson                           *
 *		2019/07/21      use Location::getLocation                       *
 *		2019/08/05      method get returns values from row if the       *
 *		                primary Name is not initialized yet             *
 *		2019/09/09      change return value of mergeFrom                *
 *		2019/09/20      correct implementation of Person::dump          *
 *		                correct eventsd in default Death and Buried     *
 *		2019/10/08      remove tracelog entries                         *
 *		2019/10/28      permit spaces in front of save parm             *
 *		                return count from save                          *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordPic.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Child.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/DontMergeEntrySet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Blog.inc';

/************************************************************************
 *  class Person														*
 *																		*
 *  Definition of a class implementing behavior for individual records	*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class Person extends RecordPic
{
    /********************************************************************
     *	Person::pevents													*
     *																	*
     *	When required pevents is initialized as an array of				*
     *	instances of Event that each represent an event that is			*
     *	defined within the Person record itself, so they can be			*
     *	handled like events that are defined in tblER.					*
     ********************************************************************/
    private	$pevents	        = null;

    /********************************************************************
     *	Person::xxxxxEvent												*
     *																	*
     *	There are 8 individual events that were represented by sets of	*
     *	fields within the Person record, but are presented to the		*
     *	use as if they are instances of Event.							*
     ********************************************************************/
    private	$birthEvent		    = null;
    private	$chrisEvent		    = null;
    private	$deathEvent		    = null;
    private	$buriedEvent		= null;
    private	$baptismEvent		= null;
    private	$confirmationEvent	= null;
    private	$initiatoryEvent	= null;
    private	$endowEvent		    = null;

    private	$bprivlim		    = 9999;
    private	$mprivlim		    = 9999;
    private	$dprivlim		    = 9999;

    /********************************************************************
     *	Person::altNames												*
     *																	*
     *	altNames is an array of all of the name records					*
     *	for this individual, including married names, adopted names,	*
     *	The index of the array is the record number IDNX				*
     *																	*
     ********************************************************************/
    private	$altNames	        = null;
    private $priName            = null;

    /********************************************************************
     *	Person::parents													*
     *																	*
     *	parents is an array of all of the Family records 				*
     *	representing families in which this individual is a child		*
     *	child is a parallel array of the corresponding instances of		*
     *	Child															*
     ********************************************************************/
    private	$parents	        = null;
    private	$nextParents	    = 0;
    private	$child		        = null;
    private	$nextChild	        = 0;

    /********************************************************************
     *	Person::families												*
     *																	*
     *	families is an array of all of the Family records 				*
     *	representing families in which this individual is a spouse		*
     ********************************************************************/
    private	$families	        = null;

    /********************************************************************
     *	Person::eventRes												*
     *																	*
     *	Result of query for events for the individual					*
     ********************************************************************/
    private	$eventRes	        = null;

    /********************************************************************
     *	Person::treeName												*
     *																	*
     *	The name of the tree, or subdivision of the database, to		*
     *	which this individual belongs.									*
     ********************************************************************/
    private	$treeName	        = '';

    /********************************************************************
     *	Constant values of gender										*
     ********************************************************************/
    const	MALE			    = 0;
    const	FEMALE			    = 1;
    const	UNKNOWN			    = 2;

    /********************************************************************
     *	Options for method getName										*
     *																	*
     *	These are combined using bitwise OR so each must be a			*
     *	power of 2.														*
     ********************************************************************/
    const	NAME_INCLUDE_DATES	= 1;
    const	NAME_SURNAME_FIRST	= 2;
    const	NAME_INCLUDE_LOCS	= 4;

    /********************************************************************
     *	Person::$initRow												*
     *																	*
     *	array containing default values for a new record				*
     ********************************************************************/
    protected static	$initRow	= array(
                'id'					=> 0,
                'idir'					=> 0,
                'fsid'					=> '',
                'surname'				=> '',          // moved to Name
                'soundslike'			=> '',          // moved to Name
                'givenname'				=> '',          // moved to Name
                'prefix'				=> '',          // moved to Name
                'title'					=> '',          // moved to Name
                'namenote'				=> '',          // moved to Name
                'gender'				=> Person::UNKNOWN,
                'birthsd'				=> -99999999,   // moved to Event
                'birthd'				=> '',          // moved to Event
                'idlrbirth'				=> 1,           // moved to Event
                'chrissd'				=> -99999999,   // moved to Event
                'chrisd'				=> '',          // moved to Event
                'idlrchris'				=> 1,           // moved to Event
                'chrterm'				=> '',          // moved to Event
                'deathsd'				=> -99999999,   // moved to Event
                'deathd'				=> '',          // moved to Event
                'idlrdeath'				=> 1,           // moved to Event
                'buriedsd'				=> -99999999,   // moved to Event
                'buriedd'				=> '',          // moved to Event
                'idlrburied'			=> 1,           // moved to Event
                'cremated'				=> 0,           // moved to Event
                'idarbirth'				=> 1,           // moved to Event
                'idarchris'				=> 1,           // moved to Event
                'idardeath'				=> 1,           // moved to Event
                'idarburied'			=> 1,           // moved to Event
                'birthnote'				=> '',          // moved to Event
                'chrisnote'				=> '',          // moved to Event
                'deathnote'				=> '',          // moved to Event
                'buriednote'			=> '',          // moved to Event
                'baptismnote'			=> '',          // moved to Event
                'endownote'				=> '',          // moved to Event
                'living'				=> 0,
                'baptismsd'				=> -99999999,   // moved to Event
                'baptismd'				=> '',          // moved to Event
                'baptismkind'			=> 0,           // moved to Event
                'idtrbaptism'			=> 1,           // moved to Event
                'ldsb'					=> 0,           // moved to Event
                'endowsd'				=> -99999999,   // moved to Event
                'endowd'				=> '',          // moved to Event
                'idtrendow'				=> 1,           // moved to Event
                'ldse'					=> 0,           // moved to Event
                'confirmationd'			=> '',          // moved to Event
                'confirmationsd'		=> -99999999,   // moved to Event
                'confirmationkind'		=> 0,           // moved to Event
                'idtrconfirmation'		=> 1,           // moved to Event
                'confirmationnote'		=> '',          // moved to Event
                'ldsc'					=> 0,           // moved to Event
                'initiatoryd'			=> '',          // moved to Event
                'initiatorysd'			=> -99999999,   // moved to Event
                'idtrinitiatory'		=> 1,           // moved to Event
                'initiatorynote'		=> '',          // moved to Event
                'ldsi'					=> 0,           // moved to Event
                'templetag'				=> 0,
                'idmrpref'				=> 0,
                'idmrparents'			=> 0,
                'idar'					=> 0,
                'ancinterest'			=> 0,
                'decinterest'			=> 0,
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
                'taganc'				=> 0,
                'tagdec'				=> 0,
                'savetag'				=> 0,
                'srchtag'				=> 0,
                'srchtagigi'			=> 0,
                'srchtagrg'				=> 0,
                'srchtagfs'				=> 0,
                'qstag'					=> 0,
                'remindertag'			=> 0,
                'remindertagdeath'		=> 0,
                'treenum'				=> 0,
                'ltmp1'					=> 0,
                'ltmp2'					=> 0,
                'alreadyused'			=> 0,
                'userref'				=> '',          // moved to Name
                'ancestralref'			=> '',
                'notes'					=> '',
                'references'			=> '',
                'medical'				=> '',
                'deathcause'			=> '',          // moved to Event
                'ppcheck'				=> 0,
                'imported'				=> 0,
                'added'					=> 0,
                'addedtime'				=> '',
                'updated'				=> 0,
                'updatedtime'			=> '',
                'relations'				=> '',
                'nevermarried'			=> 0,
                'directline'			=> 0,
                'stmp1'					=> '',
                'colortag'				=> 0,
                'intellishare'			=> '',
                'private'				=> 0,
                'ppexclude'				=> '',
                'rgexclude'				=> 0,
                'dna'					=> '',
                'fssync'				=> 0,
                'fsdups'				=> 0,
                'fsordinance'			=> 0,
                'fslinks'				=> '');

    /********************************************************************
     *	Person::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     => 'tblIR',
                'name'      => 'Persons',
                'prime'	    => 'idir',
	            'srvmg'     => false,
	            'fldcount'	=> 11,
                'order'     => 'IDIR',
                'classname' => 'Person');

    /********************************************************************
     *	Person::$personIndex									        *
     *																	*
     *	To save time if a specific record is requested more than once   *
     *	during a process, maintain a table of Person records.           *
     ********************************************************************/
    protected static	$personIndex	= array();

    /********************************************************************
     *	static function Person::getPerson								*
     *																	*
     *	Get an existing entry or create an instance of the class Person *
     *	from a row returned from a database query of the table			*
     *  'tblIR' for a value of IDIR.									*
     *																	*
     *	Input:															*
     *	    $idir       integer identifier of a person                  *
     *																	*
     *	Returns:														*
     *		an instance of Person										*
     ********************************************************************/
    static function getPerson($idir)
    {
        if (array_key_exists($idir, self::$personIndex))
	        return self::$personIndex[$idir];
        else
            return new Person(array('idir' => $idir));
    }           // static function Person::getPerson

    /********************************************************************
     *	function Person::__construct									*
     *																	*
     *	Create an instance of the class Person from 					*
     *  a row returned from a database query of the table				*
     *  'tblIR'.														*
     *																	*
     *	Input:															*
     *	    $parms		1) associative array of parameters.				*
     *					   If this array does not contain a non-empty	*
     *					   value of 'idir', 'id', or 'userref' then		*
     *					   create a new record.	 Or ...					*
     *					2) null to create a new empty record, or		*
     *					3) a database record presented as an array, or	*
     *					4) value of a key specified by the second		*
     *					   parameter uniquely identifying the			*
     *					   individual.  This is deprecated in favor of	*
     *					   array($fldname, $value).  Or		...		 	*
     *					5) 'new' to create a new empty record.			*
     *					   This is deprecated in favor of option 1 or 2.*
     *	    $fldName	if $value is a key value the name of the		*
     *					database field to match it against:				*
     *					 'idir'	   key linking individual to other		*
     *							   records. Default if omitted.			*
     *					 'id'	   auto-increment field, internal key	*
     *							   of tblIR								*
     *					 'userref' user assigned identifier				*
     *					This is deprecated because option 4 is			*
     *					deprecated.										*
     *																	*
     *	Example:														*
     *	    $indiv		= new Person(); or								*
     *	    $indiv		= new Person(null); or							*
     *	    $indiv		= new Person(array());							*
     *			Creates a new individual with all default values.		*
     *																	*
     *	    $indiv		= new Person(									*
     *					  array('givenname'		=> 'John',				*
     *							'surname'		=> 'Smith',				*
     *							'gender'		=> Person::MALE,		*
     *							'birthd'		=> '12 Apr 1856',		*
     *							'deathd'		=> '23 Nov 1899'));		*
     *			Creates a new individual with the specified name,		*
     *			gender and birth and death dates.						*
     *																	*
     *	    $indiv		= new Person(array('idir'		=> 1234));		*
     *			Creates an instance from an existing record in the		*
     *			database.  However it is more efficient to use:         *
     *		$indiv      = Person::getPerson($idir);                     *
     ********************************************************************/
    function __construct($parms		= null,
                         $fldName	= 'idir')
    {
        global  $debug;
        global  $warn;
        global  $userid;		// current user
        global  $connection;	// connection to database server
        global  $document_root;

        // rearrange deprecated parameter formats into supported array format
        $fldNameLc		    = strtolower(trim($fldName));
        $usefldname         = true;
        if (is_null($parms))
        {
            $parmText	    = 'null';
            $parms		    = array();
        }
        else
        if (is_bool($parms))
        {
            if ($parms)
                $parmText   = 'true';
            else
                $parmText   = 'false';
            $this->msg      .= "Called with unsupported parameter boolean $parmText. ";
            $parms		    = array();  // create new empty record
        }
        else
        if (is_string($parms))
        {
            $parmText       = "'$parms'";
            $parms		    = array($fldNameLc => $parms);
        }
        else
        if (is_int($parms))
        {
            $parmText       = $parms;
            $parms		    = array($fldNameLc => $parms);
        }
        else
        if ($parms instanceof Record)
        {
            $parmText       = 'class ' . get_class($parms) . 
                                '(' . $parms->getName() . ')';
            $parms		    = array('idir' => $parms);
            $usefldname     = false;
        }
        else
        if (is_array($parms))
        {
            $parmText       = 'array[';
            $comma          = '';
            foreach($parms as $field  => $value)
            {
                if (is_object($value))
                    $parmText	.= "$comma'$field'=> class " .
                                    get_class($value) .
                                    '(' . $value->getName() . ')';
                else
                if (is_string($value))
                    $parmText   .= "$comma'$field'=>'$value'";
                else 
                    $parmText   .= "$comma'$field'=>$value";
                $comma          = ', ';
            }
            $parmText       .= ']';
            $usefldname     = false;
        }
        else
        {
            $parmText	    = print_r($parms, true);
            $this->msg      .= "Called with unsupported parameter " .
                                gettype($parms) . ". ";
            $parms		    = array();  // create new empty record
        }

        if ($usefldname)
            $parmText	    = "$parmText, '$fldName'";

        if ($debug)
            $warn	    .= "<p>Person::__construct($parmText)</p>\n";

        // dbrow represents the contents of the individual fields in
        // the database record
        $dbrow			    = null;
        $idir			    = null;
        $needInsert		    = false;

        // action depends upon size of array
        if (count($parms) >= count(self::$initRow))
        {			        // complete row
            if ($debug)
                $warn	.= "<p>Person::__construct: " . __LINE__ .
                                    " complete row</p>\n";
            $dbrow		= $parms;
        }			        // complete row
        else
        {			        // search parameters
            // if the object is to be initialized from an existing database
            // record then $where is the selection expression
            $where			    = '';
            $sqlParms		    = array();

            // 'idir', 'id', and 'userref' are unique keys of the
            // record.  These are the only fields used in a search.
            // 'id' and 'userref' are only used to search if they are
            // the only parameter passed, otherwise they are used to
            // initialize or update the record

            $dbrow		        = self::$initRow;	// defaults
            $and	            = 'WHERE ';
            foreach($parms as $field => $value)
            {		        // loop through parameters
                $fieldLc	    = strtolower(trim($field));
                switch($fieldLc)
                {	        // act on specific field names
                    case 'idir':
                    {       // primary key of record
                        if ($value instanceof Name)
                        {
                            $this->priName  = $value;
                            $value          = $this->priName['idir'];
                        }
                        else
                        if ($value instanceof Child)
                        {
                            $child			= $value;
                            $idir			= $child->getIdir();
                        }
                        else
                        if (is_string($value))
                        {
                            if (strtolower($value) == 'new')
                                $idir           = 0;
                            else
                            if (ctype_digit(trim($value)))
                                $idir           = intval($value);
                            else
                            {
                                $this->msg  .= "Value of idir '$value' is not numeric. ";
                            }
                        }
                        else
                            $idir           = $value;
                        $where			    .= $and . 'IDIR=:idir';
                        $sqlParms['idir']	= $idir;
                        $and			    = ' AND ';
                        break 2;            // ignore other search parms
                    }       // primary key of record

                    case 'id':
                    case 'userref':
                    {       // other unique keys
                        $where			    .= "$and$field=:$fieldLc";
                        $sqlParms[$fieldLc]	= $value;
                        $and			    = ' AND ';
                        break;
                    }       // other unique keys
                }	        // act on specific field names
            }		        // loop through parameters

            if (strlen($where) > 0)
            {               // search database
                // construct the query of the individual table
                $query		                = "SELECT * FROM tblIR $where";

                // query the database
                $stmt		                = $connection->prepare($query);
                $queryText		            = debugPrepQuery($query, $sqlParms);
                if ($stmt->execute($sqlParms))
                {			// valid query
                    if ($debug)
                        $warn	            .= "<p>Person.inc: " . __LINE__ .
                                                    " '$queryText'</p>\n";

                    $result		            = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (is_array($result))
                    {		// initialize from database record
                        $dbrow		        = $result;
                        $needInsert		    = $dbrow['idir'] == 0;
                    }		// initialize from database record
                    else
                    {		// no match on key
                        if ($idir > 0)
                        {		// return warning record instead of exception
                            $needInsert		    = true;
                            $dbrow			    = self::$initRow;
                            $dbrow['idir']		= $idir;
                            $dbrow['givenname']	= 'Lost Record ' . $idir;
                        }	// return warning record instead of exception
                    }		// no match on key
                }			// valid query
                else
                {			// error performing query
                    $warn	.= "<p>query='$queryText'</p>\n";
                    if (strlen($queryText) > 40)
                        $queryText	= substr($queryText, 0, 30) . '...' .
                                      substr($queryText, -7);
                    $this->msg      .= "Person::__construct: '$queryText', " .
                                        print_r($stmt->errorInfo(),true) . ". ";
                }			// error performing query
            }               // search database
            else
                $needInsert		    = true;
        }			        // search parameters

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'tblIR');
        $this->needInsert	= $needInsert;

        // privatize birth and date information if required
        if ($this->isOwner())
        {		            // do not privatize dates
            $this->bprivlim	= 9999;
            $this->dprivlim	= 9999;
        }		            // do not privatize dates
        else
        {		            // privatize dates
            $currYear		= intval(date('Y'));
            $this->bprivlim	= $currYear - 105;
            $this->dprivlim	= $currYear - 72;
        }		            // privatize dates

        // save a reference to existing record
        if (!$needInsert)
        {		            // existing record
            $idir           = $this['idir'];
            if ($idir > 0)
            {               // have key
                if (!array_key_exists($idir, self::$personIndex))
                {           // not already saved
                    self::$personIndex[$idir]     = $this;
                }           // not already saved
            }               // have key
        }		            // existing record

        // override values in returned record
        if (count($parms) < count(self::$initRow))
        {		            // set of initialization parameters
            foreach($parms as $field => $parms)
            {		        // loop through parameters
                $fieldLc	= strtolower(trim($field));
                switch($fieldLc)
                {		    // act on specific field names
                    case 'idir':
                    case 'id':
                    {	    // used for search
                        break;
                    }	    // used for search

                    case 'treename':
                    {	    // used when creating name records
                        $this->treename	= $parms;
                        break;
                    }	    // used when creating name records

                    case 'ebirthd':
                    case 'edeathd':
                    case 'indexsurname':
                    case 'indexgivenname':
                    case 'indextype':
                    {	    // fields present in response from PersonSet
                        break;
                    }	    // fields present in response from PersonSet

                    default:
                    {	    // initialize fields, including temporaries
                        $this->set($fieldLc, $parms);
                        break;
                    }	    // initialize fields, including temporaries
                }		    // act on specific field names
            }		        // loop through parameters
        }		            // set of initialization parameters

        // diagnostic output if debug is set
        $this->dump('Person Record constructed:');
    }		// Person::__construct

    /********************************************************************
     *	function Person::delete											*
     *																	*
     *  Delete the database records associated with this individual		*
     *																	*
     *  Parameters														*
     *	    $xml	if true generate XML output						    *
     *				if a string use it as tag name in XML output		*
     *																	*
     *	Returns															*
     *	    associative array containing the following members:			*
     *			$counts['citCount']		number of citations deleted		*
     *			$counts['eventCount']	number of events deleted		*
     *			$counts['blogCount']	number of blog entries deleted	*
     *			$counts['indivCount']	number of individuals deleted	*
     *																	*
     *	Throws exception on SQL failure if $xml == false.				*
     ********************************************************************/
    function delete($xml = false)
    {
        global $debug;
        global $warn;
        global $connection;

        $indent             = '';
        $xmlparms           = '';
        if (is_string($xml))
        {		// string tag name
            $matches   		= array();
            if (preg_match('/^(\s*)(\w*)(.*)/', $xml, $matches))
            {
                $indent		= $matches[1];
                $xml   		= $matches[2];
                $xmlparms   = $matches[3];
            }
            $tagname		= $xml;
            $xml	   		= true;
        }		// string tag name
        else
        if ($xml)
        {		// true
            $indent         = '    ';
            $tagname	    = 'cmd';
        }		// true

        $counts	= array();
        $counts['altNameCount']	= 0;
        $counts['citCount']	    = 0;
        $counts['eventCount']	= 0;
        $counts['indivCount']	= 0;

        $idir	= $this->getIdir();

        if (count($this->getParents()) > 0 ||
            count($this->getFamilies()) > 0)
        {		// individual is connected to any others
            // not currently supported:
            // return statistics indicating nothing done
            return $counts;
        }		// individual is connected to any others

        // delete associated name records
        $nameSet	= new RecordSet('Names', array('idir' => $idir));
        $altCount	= $nameSet->delete($xml);
        $counts['altNameCount']	= $altCount;

        // delete citations associated with the main record
        $citCount	= 0;

        // update the database
        // delete citations associated with events in the base record
        $citationSet	= new CitationSet(array('idir'	=> $idir));
        $result		= $citationSet->delete($xml);
        $citCount	+= $result;

        // delete citations associated with event records
        $events		= $this->getEvents();
        if (count($events) > 0)
        {
            $idimeList	= array();
            foreach($events as $ider => $event)
            {
                if (is_numeric($ider))
                    $idimeList[]	= $ider;
            }
            if (count($idimeList) > 0)
            {
                $citationSet	= new RecordSet('Citations',
                                                array('idime'	=> $idimeList,
                                                      'type'	=> 30));
                $result		= $citationSet->delete($xml);
                $citCount	+= $result;
            }
        }
        else
            $citCount	= 0;

        // return statistics
        $counts['citCount']	= $citCount;

        // delete any events associated with this individual
        $eventSet	= new RecordSet('Events',
                                        array('idir'	=> $idir,
                                              'idtype'	=> 0));
        $count		= $eventSet->delete($xml);

        // return statistics
        $counts['eventCount']	= $count;

        // delete any associated blog messages
        $blogSet	= new RecordSet('Blogs',
                                        array('table'		=> '^tblIR$',
                                              'keyvalue'	=> $idir));
        $result		= $blogSet->delete($xml);
        $counts['blogCount']	= $result;

        // finally delete the individual record itself
        $oldJson			= $this->toJson(false);
        $delIndiv			= "DELETE FROM tblIR WHERE IDIR=:idir";
        $sqlParms			= array('idir'		=> $idir);

        // update the database
        $stmt				= $connection->prepare($delIndiv);
        $deleteText			= debugPrepQuery($delIndiv, $sqlParms);
        $this->lastSqlCmd   = $deleteText;
        if ($stmt->execute($sqlParms))
        {			// success
            $result	        = $stmt->rowCount();
            if ($result > 0)
            {			// individual record was deleted
                if ($xml)
                    print "$indent<$tagname count='$result'>$deleteText</$tagname>\n";
                else
                if ($debug)
                    $warn	.= "<p>Person.inc: " . __LINE__ .
                                        " '$deleteText'</p>\n";

                logSqlUpdate($delIndiv,
                             $sqlParms,
                             $idir,
                             $oldJson,
                             '');

                // return statistics
                $counts['indivCount']	= $result;

                // record the deleted IDIR in tblXI
                $addXI		= "INSERT INTO tblXI VALUE(:idir)";
                $sqlParms	= array('idir'		=> $idir);

                // update the database
                $stmt		= $connection->prepare($addXI);
                $addText	= debugPrepQuery($addXI, $sqlParms);
                if ($stmt->execute($sqlParms))
                {		// success
                    if ($xml)
                        print "$indent<$tagname>$addText</$tagname>\n";
                    if ($debug)
                        $warn	.= "<p>Person.inc: " . __LINE__ .
                                        " '$addText'</p>\n";
                    logSqlUpdate($addXI,
                                 $sqlParms,
                                 $idir,
                                 '',
                                 json_encode(array($idir)));
                }		// success
                else
                {		// error performing query
                    $info	= $stmt->errorInfo();
                    $errorInfo	= print_r($info, true);
                    if ($info[0] != '23000')
                    {		// ignore duplicates error
                        if ($xml)
                        {
                            print "$intent<msg>" . xmlentities($errorInfo) .
                                    "</msg>\n";
                        }
                        $warn	.= "<p>Person::delete: " .
                                        "'$addText', " . $errorInfo .
                                    "</p>\n";
                    }		// ignore duplicates
                }		// error performing query
            }			// individual record was deleted
        }			// success
        else
        {			// error performing deletion
            if ($xml)
            {
                print "$indent<$tagname>$deleteText" .
                      "</$tagname>\n";
                print "$indent<msg>" . print_r($stmt->errorInfo(),true) . "</msg>\n";
                return null;
            }
            throw new Exception("Person::delete: " .
                                "'$deleteText', " .
                                print_r($stmt->errorInfo(),true));
        }			// error performing deletion

        return $counts;
    }		// Person::delete

    /********************************************************************
     *	function Person::getIdir										*
     *																	*
     *	Get the value of the unique external numeric key IDIR for		*
     *	this individual.												*
     *																	*
     *	Returns:														*
     *	    Return the key IDIR as an integer							*
     ********************************************************************/
    function getIdir()
    {
        if ($this->needInsert)
            $this->save(false);
        $idir		= $this->row['idir'];
        return $idir;
    }		// Person::getIdir

    /********************************************************************
     *	function Person::getPriName						                *
     *																	*
     *	Get the primary name record for this Person.                    *
     *																	*
     *	Returns:														*
     *	    instance of Name of null                                    *
     ********************************************************************/
    function getPriName()
    {
        if (is_null($this->priName))
        {
            if ($this->row['idir'] > 0)
                $this->priName      = new Name(array('idir' => $this,
     					                             'order'=> Name::PRIMARY));
            else
                $this->priName      = new Name();
        }
        return $this->priName;
    }		// Person::getPriName

    /********************************************************************
     *	function Person::setPriName 									*
     *																	*
     *	Set the reference to the primary Name record for this person.   *
     *																	*
     *	Input:															*
     *	    $newName    instance of class Name                          *
     *																	*
     *	Returns:														*
     *	    previous setting as an instance of Name or null             *
     ********************************************************************/
    function setPriName($newName)
    {
        $oldName                = $this->priName;
        if ($newName instanceof Name)
            $this->priName      = $newName;
        else
            throw new Exception("Person::setPriName: invalid parameter " .
                                print_r($newName, true));
        return $oldName;
    }		// Person::setPriName

    /********************************************************************
     *	function Person::getGender						                *
     *																	*
     *	Get the gender of this individual.								*
     *																	*
     *	Returns:														*
     *	    The gender for this individual as:							*
     *	        Person::MALE		                                    *
     *	        Person::FEMALE				                            *
     *	        Person::UNKNOWN						                    *
     ********************************************************************/
    function getGender()
    {
        return $this->row['gender'];
    }		// Person::getGender

    /********************************************************************
     *	function Person::getGenderClass									*
     *																	*
     *	Get the gender of this individual as a CSS class name			*
     *																	*
     *	Returns:														*
     *	    The gender for this individual as:							*
     *			'male'													*
     *			'female'												*
     *			'unknown'												*
     ********************************************************************/
    private static $genderClassNames	= array('male', 'female', 'unknown');

    function getGenderClass()
    {
        return self::$genderClassNames[$this->row['gender']];
    }		// Person::getGenderClass


    /********************************************************************
     *	function Person::setGender	                                    *
     *																	*
     *	Validate and change the value of gender for this individual.	*
     *																	*
     *  Input:															*
     *	    $value		Person::MALE        or 'M'						*
     *	                Person::FEMALE		or 'F'					    *
     *	                Person::UNKNOWN		or '?'					    *
     *																	*
     *	Returns:														*
     *	    old value of gender for this individual.					*
     *	        Person::MALE		                                    *
     *	        Person::FEMALE				                            *
     *	        Person::UNKNOWN						                    *
     ********************************************************************/
    function setGender($value)
    {
        if (is_string($value) && ctype_digit($value))
            $value	= intval($value);
        if (is_int($value) &&
                $value >= Person::MALE &&
                $value <= Person::UNKNOWN)
        {
            return parent::set('gender', $value);
        }
        else
        if (strtoupper($value) == 'M' || strtoupper($value) == 'MALE')
            return parent::set('gender', Person::MALE);
        else
        if (strtoupper($value) == 'F' || strtoupper($value) == 'FEMALE')
            return parent::set('gender', Person::FEMALE);
        else
        if (strtoupper($value) == '?' || strtoupper($value) == 'UNKNOWN')
            return parent::set('gender', Person::UNKNOWN);
        else
            $warn	.= "<p>Person::setGender: " . __LINE__ .
                        " invalid value '$value' for field 'gender'</p>\n";
    }		//Person::setGender

    /********************************************************************
     *	function Person::getSurname		                                *
     *																	*
     *	Returns:														*
     *		current surname for this individual as a string				*
     ********************************************************************/
    function getSurname()
    {
        if (is_null($this->getPriName()))
            $retval                 = $this->row['surname'];
        else
            $retval	                = $this->priName['surname'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }		// function Person::getSurname

    /********************************************************************
     *	function Person::setSurname										*
     *																	*
     *	Change the value of surname for this individual.				*
     *																	*
     *  Input:															*
     *	    $newname		new surname									*
     *																	*
     *	Returns:														*
     *			former surname for this individual.						*
     ********************************************************************/
    function setSurname($newname)
    {
        if (is_null($this->getPriName()))
            return parent::set('surname', $newname);
	    $oldsurname	            = $this->priName['surname'];
	    if ($newname == $oldsurname)
	        return $oldsurname;
	    $this->priName->setSurname($newname);

        // ensure surname changed in all families
        $families	            = $this->getFamilies();
        foreach($families as $idmr => $family)
            $family->setName($this);

        // although obsolete, continue to update copy of surname
        parent::set('surname', $newname);
        return $oldsurname;
    }		// function Person::setSurname

    /********************************************************************
     *	function Person::getGivenName									*
     *																	*
     *	Get the value of given name for this individual.				*
     *																	*
     *	Return:															*
     *			the value of given name as a string						*
     ********************************************************************/
    function getGivenName()
    { 
        if (is_null($this->getPriName()))
            $retval                 = $this->row['givenname'];
        else
            $retval	                = $this->priName['givenname'];
        if ($retval === null)
            return '';
        else
            return $retval;
    }		// function Person::getGivenName

    /********************************************************************
     *	function Person::setGivenName									*
     *																	*
     *	Change the value of given name for this individual.				*
     *																	*
     *  Input:															*
     *	    $newname	new given names as a string						*
     *																	*
     *	Returns:														*
     *		the old value of given names as a string				    *
     ********************************************************************/
    function setGivenName($newname)
    {
        if (is_null($this->getPriName()))
            return parent::set('givenname', $newname);
        $oldname	                = $this->priName['givenname'];
        if ($newname == $oldname)
            return $oldname;
        $names		                = $this->getNames();
        foreach($names as $idnx => $nameRec)
        {		        // loop through all alternate name records
            if ($nameRec->get('order') <= 0 ||
                $nameRec->get('givenname') == $oldname)
            {		    // primary or married name or matches old given name
                $nameRec->set('givenname', $newname);
            }		    // primary or married name or matches old given name
        }	            // loop through all alternate name records

        // ensure given name changed in all families
        $families	    = $this->getFamilies();
        foreach($families as $idmr => $family)
            $family->setName($this);

        parent::set('givenname', $newname);
        return $oldname;
    }		// function Person::setGivenName

    /********************************************************************
     *	function Person::getName	                                    *
     *																	*
     *	Return the primary name for this individual including all parts *
     *	in a standard form for external display.						*
     *																	*
     *	Input:															*
     *	    $options		combination of option flags					*
     *			NAME_INCLUDE_DATES		include birth and death dates	*
     *			NAME_INCLUDE_LOCS		include birth and death locs	*
     *			NAME_SURNAME_FIRST		put surname first				*
     *																	*
     *	Returns:														*
     *	    string containing the primary name of the individual		*
     ********************************************************************/
    function getName($options	= 0)
    {
        global	$debug;
        global	$warn;

        if (is_int($options))
            $parmText			= $options;
        else
            $parmText			= '$tranTab';

        $idir                       = $this->row['idir'];
        if ($debug)
        {
            $warn			    .= "<p>Person(IDIR=" . $idir .
                                        ")->getName($parmText)</p>\n";
        }

        if (is_null($this->getPriName()))
        {                       // no instances of Name created
	        $evBirth                = null;
	        $evDeath                = null;
			if (is_array($options) || $options instanceof TemplateTag)
			{		// support being passed internationalization table
			    $tranTab			= $options;
			    $options			= self::NAME_INCLUDE_DATES;
			}		// support being passed internationalization table
			else
			    $tranTab			= null;

			$includeDates			= ($options & self::NAME_INCLUDE_DATES) != 0;
			$includeLocs			= ($options & self::NAME_INCLUDE_LOCS) != 0;
			$surnameFirst			= ($options & self::NAME_SURNAME_FIRST) != 0;
	        $idir		    		= $this->row['idir'];
			$prefix		    		= $this->row['prefix'];
			$givenname	    		= $this->row['givenname'];
			$surname	    		= $this->row['surname'];
			$birth		    		= '';
			$death		    		= '';
			$suffix		    		= $this->row['title'];

			$evBirth			    = $this->getBirthEvent();
	        $bprivlim               = $this->getBPrivLim();
	        $dprivlim               = $this->getDPrivLim();

			if ($evBirth)
	        {
				$tbirth		        = $evBirth->getDate($bprivlim,
	                                                    $tranTab);
	            if ($tbirth == 'Private')
	                return 'Private';
            }

	        if (strlen($givenname) == 0 && strlen($surname) == 0)
	            return "New Person";

			$retval		            = '';
			$space		            = '';

			if ($surnameFirst)
			{			// Surname, Given Names
			    $retval		        .= $surname;
			    $retval		        .= ',';
			    $space		        = ' ';

			    if (strlen($prefix) > 0)
			    {
					$retval		    .= $space . $prefix;
					$space		    = ' ';
			    }

			    if (strlen($givenname) > 0)
			    {
					$retval		    .= $space . $givenname;
					$space		    = ' ';
			    }

			    if (strlen($suffix) > 0)
			    {
					$retval		    .= $space . $suffix;
			    }
			}			// Surname, Given Names
			else
	        {			// Given Names Surname
			    if (strlen($prefix) > 0)
			    {
					$retval		    .= $prefix;
					$space		    = ' ';
			    }

			    if (strlen($givenname) > 0)
			    {
					$retval		    .= $space . $givenname;
					$space		    = ' ';
			    }

			    if (strlen($surname) > 0)
			    {
					$retval		    .= $space . $surname;
					$space		    = ' ';
			    }

			    if (strlen($suffix) > 0)
			    {
					$retval		    .= $space . $suffix;
			    }
			}			// Given Names Surname

			// optionally include birth and death dates
			if ($includeDates || $includeLocs)
            {		// add dates
                if ($tranTab)
                    $at         = ' ' . $tranTab['at'] . ' ';
                else
                    $at         = ' at ';
			    if ($evBirth)
			    {
					$birth		= $evBirth->getDate($bprivlim,
	                                                $tranTab);
					if ($includeLocs)
					{	// include location name
					    $loc	= $evBirth->getLocation();
					    if ($loc->getIdlr() > 1)
							$birth	.= $at . $loc->getName();
					}	// include location name
			    }
			    else
					$birth	= '';

			    $evDeath		= $this->getDeathEvent();
			    if ($evDeath)
			    {
					$death		= $evDeath->getDate($dprivlim,
												    $tranTab);
					if ($includeLocs)
					{	// include location name
					    $loc	= $evDeath->getLocation();
					    if ($loc->getIdlr() > 1)
							$death	.= $at . $loc->getName();
					}	// include location name
			    }
			    else
					$death		    = '';

			    $retval	            .= $space . "($birth&#8212;$death)";
            }		// add dates
            return $retval;
        }                       // not initialized yet

        return	$this->priName->getName($options);
    }		// function Person::getName

    /********************************************************************
     *	function Person::getTreeName									*
     *																	*
     *	Get the value of tree name for this individual.					*
     *																	*
     *	Return:															*
     *			the value of tree name as a string						*
     ********************************************************************/
    function getTreeName()
    {
        // ensure synchronized with Names table
        if (is_null($this->getPriName()))
        {
            $this->priName['idir']      = $this['idir'];
            if ($this->priName['birthsd'] == -99999999 &&
                $this['birthsd'] != -99999999)
                $this->priName['birthsd']		= $this['birthsd'];
            if ($this->priName['treename'] == '' &&
                is_string($this['treename']) &&
                $this['treename'] != '')
                $this->priName['treename']		= $this['treename'];
        }
        if ($this->priName->isExisting())
            $this->treeName	        = $this->priName['treename'];
        $this->priName->save(false);    // update primary name record
        return $this->treeName;
    }		// function Person::getTreeName

    /********************************************************************
     *	function Person::setTreeName									*
     *																	*
     *	Change the value of tree name for this individual.				*
     *																	*
     *  Input:															*
     *	    $newname	new tree name as a string						*
     *																	*
     *	Returns:														*
     *		the old value of tree name as a string						*
     ********************************************************************/
    function setTreeName($newname)
    {
        global  $document_root;

        $oldname	        = $this->treeName;
        $this->treeName	    = $newname;
        if ($newname != $oldname)
        {
            $names		    = $this->getNames();
            foreach($names as $idnx => $name)
            {
                $name->set('treename', $newname);
                $name->save(false);
            }
        }
        return $oldname;
    }		// function Person::setTreeName

    /********************************************************************
     *	function Person::get											*
     *																	*
     *	Get the value of a field in the object.							*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to get							*
     *																	*
     *	Returns:														*
     *		the value of the field										*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
        global $debug;
        global $warn;

        $fieldLc	        = strtolower($field);
        switch($fieldLc)
        {
            case 'name':
                return $this->getName();

			case 'surname':
			case 'soundslike':
			case 'givenname':
			case 'prefix':
			case 'title':
            case 'userref':
            {                   // fields moved to Name
                if (is_null($this->getPriName()) ||
                    $this->priName->row[$fieldLc] == '')
                    return $this->row[$fieldLc];
                else
                    return $this->priName->row[$fieldLc];
            }                   // fields moved to Name

            default:
                return parent::get($fieldLc);
        }
    }		// function Person::get

    /********************************************************************
     *	function Person::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.  Also a number of fields have been deprecated *
     *	in this table to avoid maintaining the same information in      *
     *	multiple tables.                                                *
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Returns:														*
     *		the old value of the field									*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
        global $debug;
        global $warn;

        $idir           = $this->row['idir'];
        $fieldLc	    = strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc	= self::$translate[$fieldLc];
        switch($fieldLc)
        {
            case 'idir':
            {			// IDIR
                if (is_string($value))
                {		// support for loading GEDCOM
                    $oldvalue		= $this->row['idir'];
                    $this->row['idir']	= $value;
                    return $oldvalue;
                }		// support for loading GEDCOM
                else
                {		// do not modify value
                    return $this->getIdir();
                }		// do not modify value
            }			// IDIR

            case 'gender':
            {
                return $this->setGender($value);
            }

            case 'surname':
            {
                return $this->setSurname($value);
            }

			case 'soundslike':
			case 'givenname':
			case 'prefix':
			case 'title':
            case 'userref':
            case 'namenote':
            {                   // fields moved to Name
                // $this->priName is set if initialization is complete
                if ($this->priName)
                    $this->priName[$fieldLc]		= $value;
                return parent::set($fieldLc, $value);
            }                   // fields moved to Name

            case 'birthd':
            {		// birth date
                $date		= self::needDateObj($field, $value);
                $sdname		= 'birthsd';
                parent::set($sdname, $date->getSortDate());
                parent::set($field, $date->getDate());
                $event		= $this->getBirthEvent(true);
                return $event->setDate($date, true);
            }		// birth date

            case 'birthsd':
            {
                if ((!$this->needInsert) && $this->priName)
                {
                    $this->priName['birthsd']		= $value;
                    $this->priName->save(false);
                }
                return parent::set($field, $value);
            }

            case 'chrisd':
            {		// christening date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getChristeningEvent(true);
                return $event->setDate($date);
            }		// christening date

            case 'deathd':
            {		// Death date
                $date		= self::needDateObj($field, $value);
                $sdname		= 'deathsd';
                parent::set($sdname, $date->getSortDate());
                parent::set($field, $date->getDate());
                $event		= $this->getDeathEvent(true);
                return $event->setDate($date, true);
            }		// Death date

            case 'buriedd':
            {		// Buried date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getBuriedEvent(true);
                return $event->setDate($date);
            }		// Buried date

            case 'baptismd':
            {		// Baptism date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getBaptismEvent(true);
                return $event->setDate($date);
            }		// Baptism date

            case 'endowd':
            {		// Endow date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getEndowEvent(true);
                return $event->setDate($date);
            }		// Endow date

            case 'confirmationd':
            {		// Initiatory date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getConfirmationEvent(true);
                return $event->setDate($date);
            }		//Initiatory date

            case 'initiatoryd':
            {		// Initiatory date
                $date		= self::needDateObj($field, $value);
                $event		= $this->getInitiatoryEvent(true);
                return $event->setDate($date);
            }		// Initiatory date

            case 'idlrbirth':
            {		// Birth Location
                $tmp		= self::needLocationObj($field, $value);
                $event		= $this->getBirthEvent(true);
                return $event->set('idlrevent', $value);
            }		// Birth Location

            case 'idlrchris':
            {		// Christening Location
                $tmp		= self::needLocationObj($field, $value);
                $event		= $this->getChristeningEvent(true);
                return $event->set('idlrevent', $value);
            }		// Christening Location

            case 'idlrdeath':
            {		// Death Location
                $tmp		= self::needLocationObj($field, $value);
                $event		= $this->getDeathEvent(true);
                return $event->set('idlrevent', $value);
            }		// Death Location

            case 'idlrburied':
            {		// Buried Location
                $tmp		= self::needLocationObj($field, $value);
                $event		= $this->getBuriedEvent(true);
                return $event->set('idlrevent', $value);
            }		// Buried Location

            case 'idtrbaptism':
            {		// Baptism location
                $event		= $this->getBaptismEvent(true);
                return $event->set('idlrevent', $value);
            }		// Baptism location

            case 'idtrendow':
            {		// Endow location
                $event		= $this->getEndowEvent(true);
                return $event->set('idlrevent', $value);
            }		// Endow location

            case 'idtrconfirmation':
            {		// Confirmation location
                $event		= $this->getConfirmationEvent(true);
                return $event->set('idlrevent', $value);
            }		// Confirmation location

            case 'idtrinitiatory';
            {		// Initiatory location
                $event		= $this->getInitiatoryEvent(true);
                return $event->set('idlrevent', $value);
            }		// Initiatory location

            case 'idarbirth':
            {		// Birth IDAR
                $event		= $this->getBirthEvent(true);
                return $event->set('idar', $value);
            }		// Birth IDAR

            case 'idarchris':
            {		// Christening IDAR
                $event		= $this->getChristeningEvent(true);
                return $event->set('idar', $value);
            }		// Christening IDAR

            case 'idardeath':
            {		// Death IDAR
                $event		= $this->getDeathEvent(true);
                return $event->set('idar', $value);
            }		// Death IDAR

            case 'idarburied':
            {		// Buried IDAR
                $event		= $this->getBuriedEvent(true);
                return $event->set('idar', $value);
            }		// Buried IDAR

            case 'cremated':
            case 'living':
            case 'baptismkind':
            case 'ldsb':
            case 'ldse':
            case 'confirmationkind':
            case 'ldsc':
            case 'ldsi':
            case 'templetag':
            case 'fsdups':
            case 'fsordinance':
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
            case 'savetag':
            case 'qstag':
            case 'srchtag':
            case 'srchtagigi':
            case 'srchtagrg':
            case 'srchtagfs':
            case 'rgexclude':
            case 'remindertag':
            case 'remindertagdeath':
            case 'alreadyused':
            case 'imported':
            case 'nevermarried':
            case 'directline':
            {		// value is no (0) or yes (1)
                if (is_string($value))
                {		// string value
                    if (ctype_digit($value))
                        $value	= intval($value);
                    else
                    {		// not numeric
                        if (strtoupper($value) == 'Y')
                            $value	= 1;
                        else
                            $value	= 0;
                    }		// not numeric
                }		// string value
                else
                if (is_null($value))
                    $value		= 0;

                if (is_int($value) && ($value == 0 || $value == 1))
                {
                    return parent::set($field, $value);
                }
                else
                    $warn	.= "<p>Person::set: " . __LINE__ .
                        " invalid value '$value' for field '$field'</p>\n";
                return $this->row[$fieldLc];
            }		// value is no (0) or yes (1)

            case 'fssync':
            {		// FSSync
//		0=Not Synced-2 red arrows
//		1=Synced-2 green arrows
//		2=right green arrow, left red arrow
//		3=right red arrow, left green arrow
                if (is_string($value) && ctype_digit($value))
                    $value	= intval($value);
                if (is_int($value) && $value >= 0 && $value <= 3)
                {
                    return parent::set($field, $value);
                }
                else
                    $warn	.= "<p>Person::set: " . __LINE__ .
                        " invalid value '$value' for field '$field'";
                return $this->row[$fieldLc];
            }		// FSSync

            case 'treename':
            {		// treename
                return $this->setTreeName();
            }		// treename

            case 'fslinks':
            case 'idmrpref':
            case 'idmrparents':
            case 'idar':
            case 'ancinterest':
            case 'decinterest':
            case 'treenum':
            case 'ltmp1':
            case 'ltmp2':
            case 'deathcause':
            case 'ppcheck':
            case 'relations':
            case 'intellishare':
            case 'stmp1':
            case 'colortag':
            case 'private':
            case 'ppexclude':
            case 'taganc':
            case 'tagdec':
            {		// no validation implemented yet
                return parent::set($field, $value);
            }		// no validation implemented yet

            default:
            {		// no validation implemented yet
                return parent::set($field, $value);
            }		// no validation implemented yet
        }		// switch on field name
    }		// Person::set


    /********************************************************************
     *	function Person::needDateObj									*
     *																	*
     *  Make sure that the supplied value is an instance of LegacyDate	*
     *																	*
     *	Input:															*
     *	    $field		field name for error message					*
     *	    $value		either an instance of LegacyDate or a string	*
     *					that can be converted to an instance			*
     *																	*
     *  Returns:														*
     *	    Instance of LegacyDate										*
     ********************************************************************/
    private static function needDateObj($field, $value)
    {
        // validate and interpret new value
        if ($value instanceof LegacyDate)
        {	// parameter is an instance of LegacyDate
            $date	= $value;
        }	// parameter is an instance of LegacyDate
        else
        if (is_string($value) && 
            (substr($value,0,1) == '0' || ctype_digit($value)) && 
            strlen($value) == 18)
        {	// convert internal value to instance of LegacyDate
            $date	= new LegacyDate( $value);
        }	// convert internal value to instance of LegacyDate
        else
        if (is_string($value) || is_int($value))
        {	// convert the value to instance of LegacyDate
            $date	= new LegacyDate(' ' . $value);
        }	// convert the value to instance of LegacyDate
        else
        {	// unexpected type
            throw new Exception("Person::needDateObj: " .
                                "invalid value type " .
                gettype($value) . " for field '$field'");
        }	// unexpected
        return $date;
    }		// Person::needDateObj


    /********************************************************************
     *	function Person::needLocationObj								*
     *																	*
     *  Make sure that the supplied value is an instance of				*
     *	Location														*
     *																	*
     *	Input:															*
     *	    $field		field name for error message					*
     *	    $value		either an instance of Location or 				*
     *					an IDLR value									*
     *																	*
     *  Returns:														*
     *	    Instance of Location										*
     ********************************************************************/
    private static function needLocationObj($field, $value)
    {
        if (is_string($value) && ctype_digit($value))
            $value	= intval($value);
        if (is_int($value))
            $location	= Location::getLocation($value);
        else
            $location	= new Location(array('location' => $value));
        return $location;
    }		// needLocationObj

    /********************************************************************
     *	function Person::getParents									    *
     *																	*
     *  Get a RecordSet of the Families in which this					*
     *	individual is a child.											*
     *																	*
     *  Returns:														*
     *	    RecordSet of instances of Family indexed by the IDCR		*
     *	    of the Child in order starting with the primary parents		*
     *																	*
     *  Side Effects:													*
     *	    private member Person::parents is set to the return			*
     *		value to speed up subsequent calls						    *
     *	    private member Person::child is set to a recordSet			*
     *		of instances of Child indexed by IDCR						*
     *		to hold relationship information						    *
     *	    Both RecordSets have the same index IDCR					*
     ********************************************************************/
    function getParents()
    {
        global $debug;
        global $warn;
        global $connection;
        global $userid;

        if ($this->parents == null)
        {		// need to perform query for parent sets
            // initialize parents to empty RecordSet
            $this->parents	= new RecordSet('Families', array());

            if ($this->needInsert)
                return $this->parents;

            // get instances of Child where current individual is a child
            $parms		= array('idir'	=> $this->row['idir']);
            $this->child	= new RecordSet('Children', $parms);

            foreach($this->child as $idcr => $childr)
            {			// loop through child records
                $idmr		= $childr['idmr'];
                if ($idmr >= 1)
                {
                    $family		= new Family(array('idmr' => $idmr));
                    $this->parents[$idcr]	= $family;
                }
                else
                {		// no reference to family in Child
                    $family	= null;
                }		// no reference to family in Child

                if (is_null($family) || !$family->isExisting())
                {		// invalid IDMR in Child
                    // delete the invalid child record
                    $childr->delete(false);
                    // remove from RecordSet of children
                    unset($this->child[$idcr]);
                    // notify the administrators
                    $idir           = $this->row['idir'];
                    error_log('Person::getParents: ' . __LINE__ .
                                ' Child with ' .
                                "IDIR=$idir, IDCR=$idcr, IDMR=$idmr, " .
                                "fixup: Delete Child with IDCR=$idcr\n");
                }		// invalid IDMR in Child
            }			// loop through child records
        }			// need to perform query for parent sets

        return $this->parents;
    }		// Person::getParents

    /********************************************************************
     *	function Person::getPreferredParents							*
     *																	*
     *  Get the preferred instance of Family in which this				*
     *	individual is a child.											*
     *																	*
     *  Returns:														*
     *	    An instance of Family or null if no parents					*
     *																	*
     ********************************************************************/
    function getPreferredParents()
    {
        global $debug;
        global $warn;
        global $connection;

        $idmrparents		= $this->row['idmrparents'];
        $parents		    = $this->getParents();
        if ($idmrparents > 0)
        {			// preferred parents specified
            foreach($parents as $idmr => $parent)
            {			// loop through sets of parents
                if ($idmr == $idmrparents)
                    return $parent;
            }			// loop through sets of parents
        }			// preferred parents specified

        // execution falls through to here if either idmrparents is zero
        // or does not match any existing set of parents
        if ($parents->count() > 0)
        {			// fixup bad idmrparent value
            $parent		= $parents->rewind();
            // set first set of parents as preferred
            $this->set('idmrparents', $parent['idmr']);
            return $parent;
        }			// fixup bad idmrparent value
        else
        if ($idmrparents > 0)
        {			// no parents, ensure idmrparent is zero
            $this->set('idmrparents', 0);
        }			// no parents, ensure idmrparent is zero

        return null;
    }		// Person::getPreferredParents

    /********************************************************************
     *	function Person::resetParents									*
     *																	*
     *  Reset the internally cached list of parents, forcing			*
     *	Person::getParents to retrieve the list from the database.		*
     *																	*
     ********************************************************************/
    function resetParents()
    {
        $this->parents		= null;
    }		// Person::resetParents

    /********************************************************************
     *	function Person::getChild				                        *
     *																	*
     *  Get an array of instances of Child representing 				*
     *  all of the instances where this individual is a child in a		*
     *	family. This array parallels the one returned by				*
     *	Person::getParents. That is a given index refers to the		    *
     *	same family in both arrays.										*
     *																	*
     *  Returns:														*
     *	    Array of instances of Child									*
     ********************************************************************/
    function getChild()
    {
        if ($this->child === null)
            $this->getParents();	// initializes $this->child
        return $this->child;
    }		// Person::getChild

    /********************************************************************
     *	function Person::getFamilies									*
     *																	*
     *  Get the families in which this individual is a spouse.			*
     *																	*
     *  Returns:														*
     *	    RecordSet of instances of Family							*
     *																	*
     *  Side Effects:													*
     *	    private member Person::families is set						*
     ********************************************************************/
    function getFamilies()
    {
        global $debug;
        global $warn;
        global $connection;

        if ($this->needInsert)
            return new RecordSet('Families', array());	// empty set

        if ($this->families == null)
        {		// need to perform query for families
            $idir		            = $this->row['idir'];

            // construct the query of the marriage table for all marriages
            // in which the current individual is a partner
            if ($this->row['gender'] == Person::FEMALE)
            {			// wife
                $famParms	        = array('IDIRWife'	=> $idir,
                                            'order'		=> 'WifeOrder');
                $this->families	= new RecordSet('Families', $famParms);
            }			// wife
            else
            {			// husband
                $famParms	        = array('IDIRHusb'	=> $idir,
                                            'order'		=> 'HusbOrder');
                $this->families	= new RecordSet('Families', $famParms);
            }			// husband

            // fixup for bad value of idmrpref
            $idmrpref			    = $this->row['idmrpref'];
            if ($idmrpref > 0)
            {			// preferred IDMR specified
                $idmrprefbad		= true;
                foreach($this->families as $idmr => $tfamily)
                {
                    if ($idmr == $idmrpref)
                    {
                        $idmrprefbad	= false;
                        break;
                    }
                }
                if ($idmrprefbad && $this->isOwner())
                {		// only owner can repair
                    $this->set('idmrpref', 0);
                    $this->save(false);
                }		// only owner can repair
            }			// preferred IDMR specified
        }			    // need to perform query for families

        return $this->families;
    }		// function Person::getFamilies

    /********************************************************************
     *	function Person::resetFamilies									*
     *																	*
     *  Reset the internally cached list of families, forcing			*
     *	Person::getFamilies to retrieve the list from the database.		*
     *																	*
     ********************************************************************/
    function resetFamilies()
    {
        $this->families		= null;
    }		// function Person::resetFamilies

    /********************************************************************
     *	function Person::getNumMarriages								*
     *																	*
     *  Get the number of families in which the current individual is a	*
     *  spouse.															*
     *	This method is deprecated.  Use Person::getFamilies()->count().	*
     *																	*
     *	Parameters:														*
     *	    $reset		If true the next call to						*
     *					$this->families->current() returns the			*
     *					first family in the set							*
     *																	*
     *	Returns:														*
     *	    Count of marriages represented as Family records.			*
     ********************************************************************/
    function getNumMarriages($reset = true)
    {
        $this->getFamilies();
        if ($reset)
            $this->families->rewind();
        return $this->families->count();
    }		// Person::getNumMarriages

    /********************************************************************
     *	function Person::getBirthEvent									*
     *																	*
     *  Get the birth event for the individual							*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getBirthEvent($create = false)
    {
        global	$debug;
        global	$warn;
        if ($debug)
        {
            if (is_bool($create))
            {
                if ($create)
                    $createStr	= 'true';
                else
                    $createStr	= 'false';
            }
            else
                $createStr	= $create;
            $warn	        .= "<p>Person(IDIR=" . $this->row['idir'] .
                                ")::getBirthEvent($createStr)</p>\n";
        }

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		    = $this->getEvents();
        }
        $event		        = $this->birthEvent;
        $chrisEvent         = $this->chrisEvent;
        if (is_null($event))
        {		                // not initialized
            $birthd         = $this['birthd'];
            $birthsd        = $this['birthsd'];
            if ($create ||
                $birthd != '' ||
                $this['idlrbirth'] > 1 ||
                $this['idarbirth'] > 1 ||
                $this['birthnote'] != '')
            {		            // create birth event from Person
                if ($birthd == '')
                    $birthsd        = -99999999;
                if ($chrisEvent)
                {
                    $chrissd        = $chrisEvent['eventsd'];
                    if ($birthsd > $chrissd)
                        $birthsd    = $chrissd - 1;
                }
                $trow	    = array('idir'		=> $this,
                                    'idtype'	=> Event::IDTYPE_INDIV,
                                    'idet'		=> Event::ET_BIRTH,
                                    'eventd'	=> $birthd,
                                    'eventsd'	=> $birthsd,
                                    'idlrevent' => $this['idlrbirth'],
                                    'idar'	    => $this['idarbirth'],
                                    'desc'	    => $this['birthnote'],
                                    'preferred' => 1,
                                    'order'	    => -2);
                $event	                    = new Event($trow);
                $this->birthEvent		    = $event;
            }		            // create birth event from Person
            else
                $event			=  null;
        }		                // not initialized
        else
        {                       // have existing event
            $birthsd            = $event['eventsd'];
            if ($this['birthsd'] == -99999999 &&
                $birthsd != -99999999)
            {                   // correct bad sort date in Person
                $this->set('birthsd', $event->get('eventsd'));
                $this->save(false);
            }                   // correct bad sort date in Person
            $chrisEvent         = $this->chrisEvent;
            $chrissd            = $chrisEvent['eventsd'];
            if ($birthsd > $chrissd)
            {
                $event->adjustSortDate($chrissd - 1);
            }
        }                       // have existing event

        if ($event)
            $event->dump("getBirthEvent: " . __LINE__ . " returns:");
        else
        if ($debug)
            $warn   .= "<p>Person::getBirthEvent: " . __LINE__ . " returns null</p>\n";

        return $event;
    }		// Person::getBirthEvent

    /********************************************************************
     *	function Person::getBirthDate									*
     *																	*
     *  Get the privatized birth date for the individual				*
     *																	*
     *	Returns:														*
     *	    A string.													*
     ********************************************************************/
    function getBirthDate()
    {
        $evBirth		= $this->getBirthEvent();
        if ($evBirth)
            return $evBirth->getDate($this->bprivlim);
        else
            return '';
    }		// Person::getBirthDate

    /********************************************************************
     *	function Person::getChristeningEvent							*
     *																	*
     *  Get the christening event for the individual					*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getChristeningEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		                = $this->getEvents();
        }
        $event		                    = $this->chrisEvent;
        if (is_null($event))
        {		// not initialized
            if ($create ||
                $this['chrisd'] != '' ||
                $this['idlrchris'] > 1 ||
                $this['idarchris'] > 1 ||
                $this['chrisnote'] != '')
            {		            // create christening event
                $description	        = $this['chrterm'];
                if (is_null($description))
                    $description        = '';
                $eventd                 = $this->row['chrisd'];
                $eventsd                = $this->row['chrissd'];
                if ($this->birthEvent && $eventd == '')
                {
                    $eventsd	        = $this->birthEvent->get('eventsd') + 1;
                }
                $trow	    = array('idir'		    => $this,
                                    'idtype'	    => Event::IDTYPE_INDIV,
                                    'idet'		    => Event::ET_CHRISTENING,
                                    'eventd'	    => $eventd,
                                    'eventsd'	    => $eventsd,
                                    'idlrevent'	    => $this['idlrchris'],
                                    'idar'		    => $this['idarchris'],
                                    'desc'		    => $this['chrisnote'],
                                    'description'   => $description,
                                    'preferred'     => 1,
                                    'order'		    => -1);
                $event	                = new Event($trow);
                $this->chrisEvent		= $event;
            }		            // create christening event
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// Person::getChristeningEvent

    /********************************************************************
     *	function Person::getDeathEvent									*
     *																	*
     *  Get the death event for the individual							*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getDeathEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		    = $this->getEvents();
        }
        $event		        = $this->deathEvent;
        if (is_null($event))
        {		// not initialized
            $eventd	                    = $this['deathd'];
            if ($eventd == '')
                $eventsd                = 99999998;
            else
                $eventsd	            = $this['deathsd'];
            if ($create ||
                $eventd != '' ||
                $this['idlrdeath'] > 1 ||
                $this['idardeath'] > 1 ||
                $this['deathnote'] != '')
            {		            // create death event from Person
                $trow	= array('idir'		    => $this,
                                'idtype'	    => Event::IDTYPE_INDIV,
                                'idet'		    => Event::ET_DEATH,
                                'eventd'	    => $eventd,
                                'eventsd'	    => $eventsd,
                                'idlrevent'	    => $this['idlrdeath'],
                                'idar'		    => $this['idardeath'],
                                'desc'		    => $this['deathnote'],
                                'description'	=> $this['deathcause'],
                                'preferred'     => 1,
                                'order'		    => (1 << 15) - 3);
                $event	                    = new Event($trow);
                $this->deathEvent		    = $event;
                $this->pevents['death']		= $event;
            }		            // create death event from Person
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// Person::getDeathEvent

    /********************************************************************
     *	function Person::getDeathDate									*
     *																	*
     *  Get the privatized death date for the individual				*
     *																	*
     *	Returns:														*
     *	    A string.													*
     ********************************************************************/
    function getDeathDate()
    {
        $evDeath		= $this->getDeathEvent();
        if ($evDeath)
            return $evDeath->getDate($this->dprivlim);
        else
            return '';
    }		// Person::getDeathDate

    /********************************************************************
     *	function Person::getBuriedEvent									*
     *																	*
     *  Get the buried event for the individual							*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getBuriedEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		= $this->getEvents();
        }
        $event		= $this->buriedEvent;
        if (is_null($event))
        {		// not initialized
            $eventd             = $this['buriedd'];
            if ($eventd == '')
                $eventsd        = 99999999;
            else
                $eventsd	    = $this['buriedsd'];
            if ($create ||
                $eventd != '' ||
                $this['idlrburied'] > 1 ||
                $this['idarburied'] > 1 ||
                $this['buriednote'] != '')
            {		// buried event present
                $deathEv        = $this->getDeathEvent(false);
                if ($deathEv && $eventsd < $deathEv->get('eventsd'))
                    $eventsd    = $deathEv->get('eventsd') + 2;
                if ($this['cremated'] > 0)
                    $description	= 'cremated';
                else
                    $description	= '';
                $trow	= array('idir'		    => $this,
                                'idtype'	    => Event::IDTYPE_INDIV,
                                'idet'		    => Event::ET_BURIAL,
                                'eventd'	    => $eventd,
                                'eventsd'	    => $eventsd,
                                'idlrevent'	    => $this['idlrburied'],
                                'idar'		    => $this['idarburied'],
                                'desc'		    => $this['buriednote'],
                                'description'   => $description,
                                'preferred'     => 1,
                                'order'		    => (1 << 15) - 2);
                $event	                = new Event($trow);
                $this->buriedEvent		= $event;
            }		// buried event present
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// Person::getBuriedEvent

    /********************************************************************
     *	function Person::getBaptismEvent								*
     *																	*
     *  Get the LDS baptism event for the individual					*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getBaptismEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		= $this->getEvents();
        }
        $event		    = $this->baptismEvent;
        if (is_null($event))
        {		// not initialized
            if ($create ||
                $this['baptismd'] != '' ||
                $this['idtrbaptism'] > 1 ||
                $this['baptismnote'] != '')
            {		// LDS baptism event present
                $trow	= array('idir'		    => $this,
                                'idtype'	    => Event::IDTYPE_INDIV,
                                'idet'		    => Event::ET_LDS_BAPTISM,
                                'eventd'	    => $this['baptismd'],
                                'eventsd'	    => $this['baptismsd'],
                                'idlrevent'	    => $this['idtrbaptism'],
                                'kind'		    => $this['baptismkind'],
                                'ldstempleready'=> $this['ldsb'],
                                'desc'		    => $this['baptismnote'],
                                'description'	=> '');
                $event	                = new Event($trow);
                $this->baptismEvent		= $event;
            }		// LDS baptism event present
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// Person::getBaptismEvent

    /********************************************************************
     *	function Person::getConfirmationEvent							*
     *																	*
     *  Get the LDS Confirmation event for the individual				*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getConfirmationEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		= $this->getEvents();
        }
        $event		= $this->confirmationEvent;
        if (is_null($event))
        {		// not initialized
            if ($create ||
                $this['confirmationd'] != '' ||
                $this['idtrconfirmation'] > 1 ||
                $this['confirmationnote'] != '')
            {
                $trow	= array('idir'	        => $this,
                                'idtype'        => Event::IDTYPE_INDIV,
                                'idet'	        => Event::ET_LDS_CONFIRMATION,
                                'eventd'	    => $this['confirmationd'],
                                'eventsd'	    => $this['confirmationsd'],
                                'idlrevent'	    => $this['idtrconfirmation'],
                                'kind'		    => $this['confirmationkind'],
                                'ldstempleready'=> $this['ldsc'],
                                'desc'		    => $this['confirmationnote'],
                                'description'   => '');
                $event	                    = new Event($trow);
                $this->confirmationEvent	= $event;
            }		// LDS confirmation event present
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// getConfirmationEvent

    /********************************************************************
     *	function Person::getInitiatoryEvent				                *
     *																	*
     *  Get the LDS Initiatory event for the individual					*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getInitiatoryEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		= $this->getEvents();
        }
        $event		= $this->initiatoryEvent;
        if (is_null($event))
        {		// not initialized
            if ($create ||
                $this['initiatoryd'] != '' ||
                $this['idtrinitiatory'] > 1 ||
                $this['initiatorynote'] != '')
            {		// LDS initiatory event present
                $trow	= array('idir'	        => $this,
                                'idtype'        => Event::IDTYPE_INDIV,
                                'idet'	        => Event::ET_LDS_INITIATORY,
                                'eventd'	    => $this['initiatoryd'],
                                'eventsd'	    => $this['initiatorysd'],
                                'idlrevent'	    => $this['idtrinitiatory'],
                                'kind'		    => 1,
                                'ldstempleready'=> $this['ldsi'],
                                'desc'		    => $this['initiatorynote'],
                                'description'	=> '');
                $event	                    = new Event($trow);
                $this->initiatoryEvent		= $event;
            }		// LDS initiatory event present
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// getInitiatoryEvent

    /********************************************************************
     *	function Person::getEndowEvent									*
     *																	*
     *  Get the LDS Endow event for the individual						*
     *																	*
     *  Input:															*
     *	    $create		true	return instance of Event even			*
     *							if there is no existing event			*
     *					false	return either Event from tblER			*
     *							or create from tblIR if present			*
     *					0		create from tblIR if present			*
     *																	*
     *	Returns:														*
     *	    An instance of Event or null								*
     ********************************************************************/
    function getEndowEvent($create = false)
    {
        global	$debug;
        global	$warn;

        // ensure that the events table is initialized
        if (is_null($this->pevents))
        {
            $events		= $this->getEvents();
        }
        $event		    = $this->endowEvent;
        if (is_null($event))
        {		// not initialized
            if ($create ||
                $this['endowd'] != '' ||
                $this['idtrendow'] > 1 ||
                $this['endownote'] != '')
            {		// LDS endow event present
                $trow	= array('idir'		    => $this,
                                'idtype'	    => Event::IDTYPE_INDIV,
                                'idet'		    => Event::ET_LDS_ENDOWED,
                                'eventd'	    => $this['endowd'],
                                'eventsd'	    => $this['endowsd'],
                                'idlrevent'	    => $this['idtrendow'],
                                'kind'		    => 1,
                                'ldstempleready'=> $this['ldse'],
                                'desc'		    => $this['endownote'],
                                'description'	=> '');
                $event	                = new Event($trow);
                $this->endowEvent		= $event;
            }		// LDS endow event present
            else
                $event			=  null;
        }		// not initialized
        return $event;
    }		// Person::getEndowEvent

    /********************************************************************
     *	function Person::getEvents										*
     *																	*
     *  Get the events for the individual.  This array					*
     *	includes all events.  This is an associative array in which		*
     *	the key is the IDER value for events represented as records in	*
     *	tblER and a descriptive string for events represented as fields	*
     *	in the record in tblIR.  The internal events associated with	*
     *	childhood, in particular birth and christening, are ordered		*
     *	before the events from tblER.  The internal events associated	*
     *	with old age, in particular death and burial, are ordered after	*
     *	the events from tblER.  For example this means, for example,	*
     *	that a funeral event, which exists only as a record in the      *
     *	Events table is presented before a death event which is         *
     *	constructed from the Persons table.		                        *
     *																	*
     *	Returns:														*
     *	    An associative array of instances of Event indexed			*
     *	    by IDER (or special key for events in tblIR).				*
     *	    Use foreach($indiv->getEvents() as $ider => $event)			*
     ********************************************************************/
    function getEvents()
    {
        global $debug;
        global $warn;
        global $connection;

        $tevents		= null;
        $maxorder       = -1;

        // check for events recorded in individual record instead of
        // tblER
        if ($this->pevents == null)
        {			    // initialize pevents array
            $this->pevents	= array();

            if ($this->isExisting())
            {			// an already existing individual
                // get a list of instances of Event from tblER for this
                // individual.  Each record represents an event or fact
                // in the life of the individual
                $parms	        = array('idir'		=> $this->row['idir'],
                                        'idtype' 	=> Event::IDTYPE_INDIV);
                $tevents	    = new RecordSet('Events', $parms);
                // ensure that methods getXxxxEvent return the same
                // instances as getEvents
                foreach($tevents as $ider => $event)
                {
                    if ($event->get('order') > $maxorder)
                        $maxorder       = $event->get('order');
                    switch($event->getIdet())
                    {
                        case Event::ET_BIRTH:
                        {
                            $event->setAssociatedRecord($this);
                            $this->birthEvent	= $event;
                            break;
                        }

                        case Event::ET_CHRISTENING:
                        {
                            $event->setAssociatedRecord($this);
                            $this->chrisEvent	= $event;
                            break;
                        }

                        case Event::ET_DEATH:
                        {
                            $event->setAssociatedRecord($this);
                            $this->deathEvent	= $event;
                            break;
                        }

                        case Event::ET_BURIAL:
                        {
                            $event->setAssociatedRecord($this);
                            $this->buriedEvent	= $event;
                            break;
                        }

                        case Event::ET_LDS_BAPTISM:
                        {
                            $event->setAssociatedRecord($this);
                            $this->baptismEvent	= $event;
                            break;
                        }

                        case Event::ET_LDS_CONFIRMATION:
                        {
                            $event->setAssociatedRecord($this);
                            $this->confirmationEvent	= $event;
                            break;
                        }

                        case Event::ET_LDS_INITIATORY:
                        {
                            $event->setAssociatedRecord($this);
                            $this->initiatoryEvent	= $event;
                            break;
                        }

                        case Event::ET_LDS_ENDOWED:
                        {
                            $event->setAssociatedRecord($this);
                            $this->endowEvent	= $event;
                            break;
                        }

                    }		// act on event type
                }		// loop through existing event objects
            }			// an already existing individual

            // check to see if the childhood events are
            // still in their old locations in tblIR
            // these events do not have an IDER key
            // put youth related events at front of $this->pevents
            // it would be nice if we could just fix up the database at
            // this point, but this could be invoked for a user who does not
            // have write authority on the database
            if (is_null($this->birthEvent))
            {               // birth
                $tevent                     = $this->getBirthEvent(true);
                if ($tevent)
                    $this->pevents['birth']	= $tevent;
            }               // birth

            if (is_null($this->chrisEvent))
            {               // other Christian christening/baptism
                $tevent		                = $this->getChristeningEvent(true);
                if ($tevent)
                    $this->pevents['christening']	= $tevent;
            }               // other Christian christening/baptism

            if (is_null($this->baptismEvent))
            {               // LDS Baptism 
                $tevent		                = $this->getBaptismEvent(false);
                if ($tevent)
                    $this->pevents['baptism']	= $tevent;
            }               // LDS Baptism 

            $tevent		                = $this->getConfirmationEvent(false);
            if ($tevent && $tevent->get('ider') == 0)
                $this->pevents['confirmation']	= $tevent;

            $tevent		                = $this->getInitiatoryEvent(false);
            if ($tevent && $tevent->get('ider') == 0)
                $this->pevents['initiatory']	= $tevent;

            $tevent		                = $this->getEndowEvent(false);
            if ($tevent && $tevent->get('ider') == 0)
                $this->pevents['endow']		= $tevent;

            // add events from tblER
            if ($tevents)
            {			// an already existing individual
                foreach($tevents as $ider => $event)
                    $this->pevents[$ider]	= $event;
            }			// an already existing individual

            // check to see if the death and/or buried events are
            // still in their old locations in tblIR
            // and append them to the back of the list
            $tevent		= $this->getDeathEvent(true);
            if ($tevent && $tevent->get('ider') == 0)
            {
                $tevent->set('order',       ++$maxorder);
                $this->pevents['death']		= $tevent;
            }

            $tevent		= $this->getBuriedEvent(true);
            if ($tevent && $tevent->get('ider') == 0)
            {
                $tevent->set('order',       ++$maxorder);
                $this->pevents['buried']	= $tevent;
            }

            // ensure that methods getXxxxEvent return the same
            // instances as getEvents
            foreach($this->pevents as $ider => $event)
            {
                switch($event->getIdet())
                {
                    case Event::ET_BIRTH:
                    {
                        if (is_null($this->birthEvent))
                            $this->birthEvent	= $event;
                        break;
                    }

                    case Event::ET_CHRISTENING:
                    {
                        if (is_null($this->chrisEvent))
                            $this->chrisEvent	= $event;
                        break;
                    }

                    case Event::ET_DEATH:
                    {
                        if (is_null($this->deathEvent))
                            $this->deathEvent	= $event;
                        break;
                    }

                    case Event::ET_BURIAL:
                    {
                        if (is_null($this->buriedEvent))
                            $this->buriedEvent	= $event;
                        break;
                    }

                    case Event::ET_LDS_BAPTISM:
                    {
                        if (is_null($this->baptismEvent))
                            $this->baptismEvent	= $event;
                        break;
                    }

                    case Event::ET_LDS_CONFIRMATION:
                    {
                        if (is_null($this->confirmationEvent))
                            $this->confirmationEvent= $event;
                        break;
                    }

                    case Event::ET_LDS_INITIATORY:
                    {
                        if (is_null($this->initiatoryEvent))
                            $this->initiatoryEvent	= $event;
                        break;
                    }

                    case Event::ET_LDS_ENDOWED:
                    {
                        if (is_null($this->endowEvent))
                            $this->endowEvent	= $event;
                        break;
                    }

                }		// act on event type
            }			// loop through existing events
        }			// initialize array
        return $this->pevents;
    }		// Person::getEvents

    /********************************************************************
     *	function Person::getBPrivLim									*
     *																	*
     *  Get the year after which birth associated events are private.	*
     *	For individuals where the current user is not an owner this		*
     *	is 100 years before the present year.							*
     *																	*
     *	Returns:														*
     *	    Year														*
     ********************************************************************/
    function getBPrivLim()
    {
        return $this->bprivlim;
    }		// Person::getBPrivLim

    /********************************************************************
     *	function Person::getDPrivLim									*
     *																	*
     *  Get the year after which death associated events are private.	*
     *	For individuals where the current user is not an owner this		*
     *	is 50 years before the present year.							*
     *																	*
     *	Returns:														*
     *	    Year														*
     ********************************************************************/
    function getDPrivLim()
    {
        return $this->dprivlim;
    }		// Person::getDPrivLim

    /********************************************************************
     *	function Person::addEvent		                                *
     *																	*
     *  Add a new individual event represented by an instance of		*
     *  Event to this individual										*
     *																	*
     *	Input:															*
     *	    $parms	associative array of parameters to initialize		*
     *				the instance of Event								*
     *																	*
     *  Returns:														*
     *			Instance of Event										*
     ********************************************************************/
    function addEvent($parms	= null)
    {
        if (is_null($parms))
        {		// no parameter passed
            $parms	    = array('idtype'	=> Event::IDTYPE_INDIV,
                                'idir'		=> $this);
            // create instance of Event
            $event		= new Event($parms);
        }		// no parameter passed
        else
        if (is_array($parms))
        {		// array
            // ensure key parameters have correct values
            $parms['idtype']	= Event::IDTYPE_INDIV;
            $parms['idir']	= $this;
            // create instance of Event
            $event		= new Event($parms);
        }		// array
        else
        if ($parms instanceof Event)
        {
            $event	= $parms;
        }
        else
        {		// invalid parameter type
            throw new Exception("Person::addEvent: " .
                                "invalid parameter " .
                                print_r($parms, true));
        }		// invalid parameter type


        // if member pevents has been initialized add the new event to it
        if (is_array($this->pevents) && $event->getIder() > 0)
            $this->pevents[$event->getIder()]	= $event;

        return $event;
    }		// Person::addEvent

    /********************************************************************
     *	function Person::getNames										*
     *																	*
     *	Get the alternate name records for the individual.				*
     *																	*
     *	Input:															*
     *	    $order		optional value to select only certain types		*
     *					of name records									*
     *	    getNames(1)													*
     *			returns only the "real" alternate names					*
     *	    getNames(0)													*
     *			returns only the main name of the individual			*
     *	    getNames(-1)												*
     *			returns only the married names of the individual		*
     *	    getNames(array(0,-1))										*
     *			returns the main name of the individual and				*
     *			the married names of the individual						*
     *																	*
     *	Returns:														*
     *	    An instance of RecordSet containing instances of Name		*
     *	    indexed by IDNX.											*
     *	    Use foreach($indiv->getNames() as $idnx => $altname)		*
     ********************************************************************/
    function getNames($order = null)
    {
        global	$debug;
        global  $warn;

        // if individual has not been written to the database
        // there can be no name records
        if ($this->needInsert)
        {
            if ($this->priName)
                return new RecordSet('Names',
                                     array($this->priName));
            else
                return new RecordSet('Names',
                    array());	// empty set
        }

        if (!is_array($this->altNames))
        {		// need to load from database
            $altParms		= array('IDIR'		=> $this->row['idir']);
            $this->altNames	= new RecordSet('Names', $altParms);
        }			// need to load from database

        // interpret parameter to limit return value
        if (is_null($order))
            return $this->altNames;
        else
        {			// return only selected records
            if ($order == 0)
            {			// primary name
                return new RecordSet('Names',
                                     array('idir'	=> $this->row['idir'],
                                           '`order`'	=> 0));
            }			// primary name
            else
            if ($order == -1)
            {			// married names
                return new RecordSet('Names',
                                     array('idir'	=> $this->row['idir'],
                                           '`order`'	=> -1));
            }			// married names
            else
            if ($order >= 1)
            {			// alternate names
                return new RecordSet('Names',
                                     array('idir'	=> $this->row['idir'],
                                           '`order`'	=> '>=0'));
            }			// alternate names
            else
            if (is_array($order) && count($order) >= 2 &&
                $order[0] == 0 && $order[1] == -1)
            {			// primary & married names
                return new RecordSet('Names',
                                     array('idir'	=> $this->row['idir'],
                                           '`order`'	=> '<=0'));
            }			// primary and married names
            else
            if (is_array($order))
            {			// other check
                return new RecordSet('Names',
                                     array('idir'	=> $this->row['idir'],
                                           '`order`'	=> $order));
            }			// other check
        }			// return only selected records

        return new RecordSet('Names',
                             array());	// empty set
    }		// Person::getNames

    /********************************************************************
     *	function Person::toXml											*
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
     *	value appears in an element whose name is the original field name*
     *	with the "idlr" prefix removed and a "loc" suffix added.  For	*
     *	example the element <idlrbirth> contains the original numeric	*
     *	code while the element <birthloc> contains the external			*
     *	textual value.													*
     *																	*
     *  Each field whose value is an instance of Temple appears			*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idtr" prefix removed and a "loc" suffix added.	*
     *	For example the element <idtrbaptism> contains the original		*
     *	numeric code while the element <baptismloc> contains the		*
     *	external textual value.											*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'indiv'			*
     *					if null output not enclosed in a top level node	*
     *	    $parms		if true print the output, 						*
     *					if false just return it							*
     *					if associative array, parameters to include in	*
     *					response, except for 'print' which permits		*
     *					specifying not to print the XML					*
     *	    $options	flags to indicate which information to include	*
     *																	*
     *	Returns:														*
     *	    Always returns the XML document								*
     ********************************************************************/

    /********************************************************************
     *		Constant masks for options in 3rd parameter					*
     ********************************************************************/
    const	TOXML_INCLUDE_FAMILIES	= 1;
    const	TOXML_INCLUDE_PARENTS	= 2;
    const	TOXML_INCLUDE_NAMES	    = 4;
    const	TOXML_INCLUDE_EVENTS	= 8;

    function toXml($top 		= 'indiv',
                   $parms 		= true,
                   $options		= 0)
    {
        global	$userid;
        global	$debug;

        $idir			= $this->row['idir'];
        $isOwner		= $this->isOwner();
        $includeFamilies	= $options & self::TOXML_INCLUDE_FAMILIES;
        $includeParents		= $options & self::TOXML_INCLUDE_PARENTS;
        $includeNames		= $options & self::TOXML_INCLUDE_NAMES;
        $includeEvents		= $options & self::TOXML_INCLUDE_EVENTS;
        $child			= false;

        if (is_null($top))
            $retval	= '';
        else
            $retval	= "<$top idir='$idir' options='$options'>\n";

        if (is_array($parms))
        {		// additional parameters to include
            $print		= true;
            foreach($parms as $field => $value)
            {
                if ($field == 'print')
                    $print	= $value;
                else
                {
                    if ($field == 'idcr')
                        $child	= true;
                    $retval	.= "    <$field>" . xmlentities($value) .
                                        "</$field>\n";
                }
            }
        }		// additional parameters to include
        else
            $print	= $parms;

        // display fields from main record
        foreach($this->row as $field => $value)
        {		// loop through all fields
            switch($field)
            {
                case 'surname':
                case 'soundslike':
                case 'givenname':
                case 'prefix':
                case 'title':
                case 'userref':
                case 'namenote':
                {		// fields moved to class Name
                    break;
                }		// fields moved to class Name

                case 'birthd':
                case 'deathd':
                case 'birthsd':
                case 'deathsd':
                case 'idlrbirth':
                case 'idarbirth':
                case 'birthnote':
                case 'chrisd':
                case 'chrissd':
                case 'idlrchris':
                case 'idarchris':
                case 'chrisnote':
                case 'chrterm':
                case 'idlrdeath':
                case 'idardeath':
                case 'deathnote':
                case 'buriedd':
                case 'buriedsd':
                case 'idlrburied':
                case 'idarburied':
                case 'buriednote':
                case 'cremated':
                case 'baptismd':
                case 'baptismsd':
                case 'idtrbaptism':
                case 'baptismkind':
                case 'baptismnote':
                case 'ldsb':
                case 'endowd':
                case 'endowsd':
                case 'idtrendow':
                case 'endownote':
                case 'ldse':
                case 'confirmationd':
                case 'confirmationsd':
                case 'idtrconfirmation':
                case 'confirmationkind':
                case 'confirmationnote':
                case 'ldsc':
                case 'initiatoryd':
                case 'initiatorysd':
                case 'idtrinitiatory':
                case 'initiatorynote':
                case 'ldsi':
                {		// fields reported in events
                    break;
                }		// fields reported in events

                case 'gender':
                {
                    if ($value == Person::MALE)
                        $retval	.= "    <gender>M</gender>\n";
                    else
                    if ($value == Person::FEMALE)
                        $retval	.= "    <gender>F</gender>\n";
                    else
                        $retval	.= "    <gender>?</gender>\n";
                    break;
                }		// interpret gender

                case 'living':
                case 'templetag':
                {
                    if ($value == 1)
                        $retval	.= "    <$field>Y</$field>\n";
                    else
                    if ($value == 0)
                        $retval	.= "    <$field>N</$field>\n";
                    else
                        $retval	.= "    <$field>$value</$field>\n";
                    break;
                }		// interpret yes/no flags

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
                case 'taganc':
                case 'tagdec':
                case 'savetag':
                case 'srchtag':
                case 'srchtagigi':
                case 'srchtagrg':
                case 'srchtagfs':
                case 'qstag':
                case 'remindertag':
                case 'remindertagdeath':
                case 'treenum':
                case 'alreadyused':
                case 'ppcheck':
                case 'imported':
                case 'nevermarried':
                case 'directline':
                case 'private':
                case 'fssync':
                case 'fsdups':
                case 'fsordinance':
                case 'fslinks':
                {		// flags suppressed for child
                    if (!$child)
                    {
                        if ($value === 1 || $value === '1')
                            $retval	.= "    <$field>Y</$field>\n";
                        else
                        if ($value === 0 || $value === '0')
                            $retval	.= "    <$field>N</$field>\n";
                        else
                        if (is_null($value))
                            $retval	.= "    <$field>null</$field>\n";
                        else
                            $retval	.= "    <$field>$value</$field>\n";
                    }
                    break;
                }		// flags suppressed for child

                case 'idmrparents':
                case 'idmrpref':
                case 'idar':
                case 'ancinterest':
                case 'decinterest':
                case 'ancestralref':
                case 'colortag':
                case 'ltmp1':
                case 'ltmp2':
                case 'ppexclude':
                case 'rgexclude':
                case 'stmp1':
                case 'relations':
                case 'intellishare':
                case 'dna':
                case 'added':
                case 'addedtime':
                case 'updated':
                case 'updatedtime':
                {		// fields suppressed for child
                    if (!$child)
                        $retval	.= "    <$field>" . xmlentities($value) . "</$field>\n";
                    break;
                }		// fields suppressed for child

                default:
                {		// fields passed back with no interpretation
                    $retval	.= "    <$field>" . xmlentities($value) . "</$field>\n";
                    break;
                }		// fields passed back with no interpretation
            }		// switch on field name
        }		// loop through all fields

        // if requested include name information
        if ($includeNames)
            $names	= $this->getNames();
        else
            $names	= $this->getNames(0);
        if (count($names) > 0)
        {		// individual has at least one name record
            if ($includeNames)
                $retval	.= "  <names count='" . count($names) . "'>\n";
            else
                $retval	.= "  <names>\n";
            foreach($names as $idnx => $name)
            {		// loop through names
                $retval	.= $name->toXml('name', false);
            }		// loop through names
            $retval	.= "  </names>\n";
        }		// individual has at least one name record

        // include events as <event> tags
        $this->birthEvent	= null;	// force refresh from DB
        $this->chrisEvent	= null;
        $this->deathEvent	= null;
        $this->buriedEvent	= null;
        $tevents		    = $this->getEvents();
        foreach($tevents as $ider => $tevent)
        {
            if ($includeEvents || 
                ($tevent['idtype'] == 0 && 
                    ($tevent['idet'] == 3 || $tevent['idet'] == 6)))
            {
                $retval	.= $tevent->toXml('event', false);
            }		// include events
        }		// loop through events

        // if requested include parent information
        if ($includeParents)
        {		// include parents information
            $parents	= $this->getParents();
            if ($parents->count() > 0)
            {		// individual is a child in a family
                $retval	.= "  <parents count='" . $parents->count() . "'>\n";
                foreach($parents as $ic => $family)
                {		// loop through parents
                    $retval	.= $family->toXml('family', false, 0);
                }		// loop through parents
                $retval	.= "  </parents>\n";
            }		// individual is a child in a family
        }		// include parents information

        // if requested include family information
        if ($includeFamilies)
        {		// include family information
            $families	= $this->getFamilies();
            if (count($families) > 0)
            {		// individual is a parent in a family
                $retval	.= "  <families count='" . count($families) . "'>\n";
                foreach($families as $idmr => $family)
                {		// loop through families
                    $retval	.= $family->toXml('family', false, 0);
                }		// loop through families
                $retval	.= "  </families>\n";
            }		// individual is a parent in a family
        }		// include family information

        // close off response
        if ($top !== null)
            $retval	.= "</$top>\n";
        if ($print)
            print $retval;
        return $retval;
    }		// Person::toXml

    /********************************************************************
     *	function Person::toJson											*
     *																	*
     *  Emit an JSON representation of the record.						*
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
     *	value appears in an element whose name is the original field name*
     *	with the "idlr" prefix removed and a "loc" suffix added.  For	*
     *	example the element "idlrbirth" contains the original numeric	*
     *	code while the element "birthloc" contains the external			*
     *	textual value.													*
     *																	*
     *  Each field whose value is an instance of Temple appears			*
     *	as two elements.  The internal numeric key value appears in		*
     *	an element with the original field name.  The external textual	*
     *	value appears in an element whose name is the original field	*
     *	name with the "idtr" prefix removed and a "loc" suffix added.	*
     *	For example the element "idtrbaptism" contains the original		*
     *	numeric code while the element "baptismloc" contains the		*
     *	external textual value.											*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, 						*
     *	    $options	flags to indicate which information to include	*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 2nd parameter						*
     ********************************************************************/
    const	TOJSON_INCLUDE_FAMILIES	= 1;
    const	TOJSON_INCLUDE_PARENTS	= 2;
    const	TOJSON_INCLUDE_NAMES	= 4;
    const	TOJSON_INCLUDE_EVENTS	= 8;

    function toJson($parms		    = true,
                    $options		= 0)
    {
        global	$userid;
        global	$debug;

        $idir			        = $this->row['idir'];
        $isOwner		        = $this->isOwner();
        $includeFamilies	    = $options & self::TOJSON_INCLUDE_FAMILIES;
        $includeParents		    = $options & self::TOJSON_INCLUDE_PARENTS;
        $includeNames		    = $options & self::TOJSON_INCLUDE_NAMES;
        $includeEvents		    = $options & self::TOJSON_INCLUDE_EVENTS;
        $child			        = false;

        // display fields from main record
        $retval			        = '';
        $comma			        = "{\n\t";

        if (is_array($parms))
        {		        // additional parameters to include
            $print		        = true;
            foreach($parms as $field => $value)
            {
                if ($field == 'print')
                    $print	    = $value;
                else
                {
                    if ($field == 'idcr')
                        $child	= true;
                    $retval		.= $comma . "\"$field\":\t" .
                                           json_encode($value);
                    $comma		= ",\n\t";
                }
            }
        }		        // additional parameters to include
        else
            $print	= $parms;

        foreach($this->row as $field => $value)
        {		        // loop through all fields
            switch($field)
            {
                case 'surname':
                case 'soundslike':
                case 'givenname':
                case 'prefix':
                case 'title':
                case 'namenote':
                case 'birthd':
                case 'deathd':
                case 'birthsd':
                case 'deathsd':
                case 'idlrbirth':
                case 'idarbirth':
                case 'birthnote':
                case 'chrisd':
                case 'chrissd':
                case 'idlrchris':
                case 'idarchris':
                case 'chrisnote':
                case 'chrterm':
                case 'idlrdeath':
                case 'idardeath':
                case 'deathnote':
                case 'buriedd':
                case 'buriedsd':
                case 'idlrburied':
                case 'idarburied':
                case 'buriednote':
                case 'cremated':
                case 'baptismd':
                case 'baptismsd':
                case 'idtrbaptism':
                case 'baptismkind':
                case 'baptismnote':
                case 'ldsb':
                case 'endowd':
                case 'endowsd':
                case 'idtrendow':
                case 'endownote':
                case 'ldse':
                case 'confirmationd':
                case 'confirmationsd':
                case 'idtrconfirmation':
                case 'confirmationkind':
                case 'confirmationnote':
                case 'ldsc':
                case 'initiatoryd':
                case 'initiatorysd':
                case 'idtrinitiatory':
                case 'initiatorynote':
                case 'ldsi':
                {		// fields reported in events
                    break;
                }		// fields reported in events

                case 'gender':
                {
                    if ($value == Person::MALE)
                        $retval	.= ",\n\t\"gender\":\t\"M\"";
                    else
                    if ($value == Person::FEMALE)
                        $retval	.= ",\n\t\"gender\":\t\"F\"";
                    else
                        $retval	.= ",\n\t\"gender\":\t\"?\"";
                    break;
                }		// interpret gender

                case 'living':
                case 'templetag':
                {
                    if ($value == 1)
                        $retval	.= ",\n\t\"$field\":\t\"Y\"";
                    else
                    if ($value == 0)
                        $retval	.= ",\n\t\"$field\":\t\"N\"";
                    else
                        $retval	.= ",\n\t\"$field\":\t\"$value\"";
                    break;
                }		// interpret yes/no flags

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
                case 'taganc':
                case 'tagdec':
                case 'savetag':
                case 'srchtag':
                case 'srchtagigi':
                case 'srchtagrg':
                case 'srchtagfs':
                case 'qstag':
                case 'remindertag':
                case 'remindertagdeath':
                case 'treenum':
                case 'alreadyused':
                case 'ppcheck':
                case 'imported':
                case 'nevermarried':
                case 'directline':
                case 'private':
                case 'fssync':
                case 'fsdups':
                case 'fsordinance':
                {		// flags suppressed for child
                    if (!$child)
                    {
                        if ($value === 1 || $value === '1')
                            $retval	.= ",\n\t\"$field\":\t\"Y\"";
                        else
                        if ($value === 0 || $value === '0')
                            $retval	.= ",\n\t\"$field\":\t\"N\"";
                        else
                        if (is_null($value))
                            $retval	.= ",\n\t\"$field\":\tnull";
                        else
                            $retval	.= ",\n\t\"$field\":\t$value";
                    }
                    break;
                }		// flags suppressed for child

                case 'fslinks':
                {		// text field suppressed for child
                    if (!$child)
                    {
                        $retval	.= ",\n\t\"$field\":\t\"$value\"";
                    }
                    break;
                }		// text field suppressed for child

                case 'idmrparents':
                case 'idmrpref':
                case 'idar':
                case 'ancinterest':
                case 'decinterest':
                case 'colortag':
                case 'ltmp1':
                case 'ltmp2':
                case 'rgexclude':
                {		// numeric fields suppressed for child
                    if (!$child)
                    {
                        $retval	.= ",\n\t\"$field\":\t$value";
                    }
                    break;
                }		// numeric fields suppressed for child

                case 'userref':
                case 'ancestralref':
                case 'ppexclude':
                case 'stmp1':
                case 'relations':
                case 'intellishare':
                case 'dna':
                case 'added':
                case 'addedtime':
                case 'updated':
                case 'updatedtime':
                {		// text fields suppressed for child
                    if (!$child)
                    {
                        $retval	.= ",\n\t\"$field\":\t" .
                                   json_encode($value);
                    }
                    break;
                }		// text fields suppressed for child

                case 'id':
                {		// first field
                    $retval	    .= "$comma\"$field\":\t$value";
                    $comma		= ",\n\t";
                    break;
                }		// first field

                case 'idir':
                case 'living':
                case 'idmrpref':
                case 'idmrparents':
                case 'idar':
                {		// numeric fields passed back with no interpretation
                    $retval	    .= ",\n\t\"$field\":\t$value";
                    break;
                }		// numeric fields passed back with no interpretation

                default:
                {		// fields passed back with text
                    $retval	    .= ",\n\t\"$field\":\t" .
                                   json_encode($value);
                    break;
                }		// fields passed back with text
            }		    // switch on field name
        }		        // loop through all fields

        // if requested include name information
        if ($includeNames)
            $names	            = $this->getNames();
        else
            $names	            = $this->getNames(0);
        $comma	= ",\n\t";
        if (count($names) > 0)
        {		        // individual has at least one name record
            $retval	            .= "$comma\"names\":\n\t{";
            $sep	            = '';
            foreach($names as $idnx => $name)
            {		    // loop through names
                $retval	        .=  $sep . "\"$idnx\":" . $name->toJson(false);
                $sep	        = ',';
            }		    // loop through names
            $retval	        .= "\t}\n";
            $comma	        = ",\n\t";
        }		        // individual has at least one name record

        // include events as <event> tags
        $this->birthEvent	= null;	// force refresh from DB
        $this->chrisEvent	= null;
        $this->deathEvent	= null;
        $this->buriedEvent	= null;
        $tevents		    = $this->getEvents();
        $retval			    .= $comma . "\"events\":\n\t{";
        $retval			    .= "\"count\":\t" . count($tevents);
        $sep			    = ",\n";
        foreach($tevents as $ider => $tevent)
        {
            if ($includeEvents || 
                ($tevent['idtype'] == 0 && 
                    ($tevent['idet'] == 3 || $tevent['idet'] == 6)))
            {           // display event
                $retval	    .= $sep;
                $retval	    .= "\"$ider\":\t\n\t";
                $retval	    .= $tevent->toJson(false);
                $sep	    = ',';
            }           // display event
        }		        // loop through events
        $retval	.= "\t}\n";
        $comma	= ",\n\t";

        // if requested include parent information
        if ($includeParents)
        {		        // include parents information
            $parents	    = $this->getParents();
            if ($parents->count() > 0)
            {		    // individual is a child in a family
                $retval	    .= $comma . "\"parents\":\n\t{";
                $sep		= '';
                foreach($parents as $ic => $family)
                {		// loop through parents
                    $retval	.= $sep . $family->toJson(false, 0);
                    $sep	= ',';
                }		// loop through parents
                $retval	    .= "\t}\n";
                $comma	    = ",\n\t";
            }		    // individual is a child in a family
        }		        // include parents information

        // if requested include family information
        if ($includeFamilies)
        {		        // include family information
            $families	    = $this->getFamilies();
            if (count($families) > 0)
            {		    // individual is a parent in a family
                $retval	    .= $comma . "\"families\":\n\t{";
                $sep		= '';
                foreach($families as $idmr => $family)
                {		// loop through families
                    $retval	.= $sep . $family->toJson(false, 0);
                    $sep	= ',';
                }		// loop through families
                $retval	    .= "\t}\n";
                $comma	    = ",\n\t";
            }		    // individual is a parent in a family
        }		        // include family information

        // close off response
        $retval		    .= "}\n";

        if ($print)
            print $retval;
        return $retval;
    }		// Person::toJson

    /********************************************************************
     *	function Person::mergeFrom										*
     *																	*
     *  Merge information from another individual into this individual	*
     *	for all fields that do not have an explicit value in the        *
     *	current record.  The internal field values are changed.         *
     *	Invoke the save method to apply the changes to the database.	*
     *																	*
     *	Input:															*
     *	    $indiv2		instance of Person from which to copy			*
     *					values											*
     *																	*
     *	Returns:														*
     *		array containing one entry for each field which has         *
     *		changed.  Each entry is an associative array:               *
     *		array('field' => fieldname, 'value' => new value)           *
     ********************************************************************/
    function mergeFrom($indiv2)
    {
        $retval             = array();

        // check all other fields in the main record
        foreach($this->row as $field => $value)
        {                           // loop through all fields in current
            $value2	        = $indiv2->get($field);
            switch($field)
            {                       // act on specific field
                case 'id':
                case 'idir':
                case 'surname':
                case 'givenname':
                case 'soundslike':
                case 'birthd':
                case 'idlrbirth':
                case 'chrisd':
                case 'idlrchris':
                case 'deathd':
                case 'idlrdeath':
                case 'buriedd':
                case 'idlrburied':
                {	                // already handled
                     break;
                }	                // already handled

                case 'prefix':
                case 'title':
                case 'chrterm':
                case 'fslinks':
                case 'userref':
                case 'ancestralref':
                case 'relations':
                case 'intellishare':
                case 'stmp1':
                case 'ppexclude':
                case 'dna':
                case 'addedtime':
                case 'baptismd':
                case 'endowd':
                case 'confirmationd':
                case 'initiatoryd':
                {	                // text fields preference for first indiv
                    if (strlen($value) == 0 && strlen($value2) > 0)
                    {	            // only replace if no value in first indiv
                        $this->set($field, $value2);
                        $retval     .= array('field'    => $field,
                                             'value'    => $value2);
                    }	            // only replace if no value in first indiv
                    break;
                }	                // text fields preference for first indiv

                case 'baptismsd':
                case 'endowsd':
                case 'confirmationsd':
                case 'initiatorysd':
                case 'birthsd':
                case 'chrissd':
                case 'deathsd':
                case 'buriedsd':
                {	                // sort dates copied with text date
                    break;
                }	                //  sort dates copied with text date

                case 'namenote':
                case 'birthnote':
                case 'chrisnote':
                case 'deathnote':
                case 'buriednote':
                case 'baptismnote':
                case 'endownote':
                case 'confirmationnote':
                case 'initiatorynote':
                case 'notes':
                case 'references':
                case 'medical':
                case 'deathcause':
                {	                // textual notes
                    if (strlen($value) == 0 && strlen($value2) > 0)
                    {	            // replace if no value in first indiv
                        $this->set($field, $value2);
                        $retval[]     = array('field'   => $field,
                                              'value'   => $value2);
                    }	            // replace if no value in first indiv
                    else
                    if (strlen($value) > 0 && strlen($value2) > 0)
                    {	            // concatenate if present in both
                        $this->set($field, $value . "\n" . $value2);
                        $retval[]     = array('field'   => $field,
                                              'value'   => "$value1\\n$value2");
                    }	            // concatenate if present in both
                    break;
                }	                // textual notes

                case 'ancinterest':
                case 'decinterest':
                {	                // researcher interest
                    if ($value2 > $value)
                    {		        // take higher value
                        $this->set($value2);
                        $retval[]     = array('field'   => $field,
                                              'value'   => $value2);
                    }		        // take higher value
                    break;
                }	                // researcher interest

                case 'updated':
                case 'updatedtime':
                {	                // date and time updated set to now
                    break;
                }	                // date and time updated

                default:
                {	                // other fields
                    if (($value == 0) && ($value2 != 0))
                    {	            // replace if no value in first indiv
                        $this->set( $field, $value2);
                        $retval[]     = array('field'   => $field,
                                              'value'   => $value2);
                    }	            // replace if no value in first indiv
                    break;
                }	                // other fields
            }	                    // switch on field name

        }	                        // loop through all fields in individual

        return $retval;
    }		// function Person::mergeFrom

    /********************************************************************
     *	function Person::dump											*
     *																	*
     *  If debug output is enabled, display a diagnostic dump of the	*
     *  contents of a database record in the web page.					*
     *																	*
     *  Input:															*
     *	    $label		descriptive identifier for record				*
     *	    $force		if true output even if $debug false				*
     *	                This is deprecated,                             *
     *	                use $warn   .= $person->dump(...);              *
     *																	*
     *	Return:															*
     *	    string containing trace output as HTML						*
     ********************************************************************/
    function dump($label	= null,
                  $force	= false)
    {
        global $debug;
        global $warn;

        if (false && $debug)
        {                   // for tracking down unexpected dumps
            $backtrace          = debug_backtrace();
            foreach($backtrace as $call)
            {
                if (array_key_exists('class', $call) &&
                    strlen($call['class']) > 0)
                    $classPref      = $call['class'] . '::';
                else
                    $classPref      = '';
                $warn   .= "<p>Person::dump: " . __LINE__ .
                    " function=$classPref" . $call['function'] .
                    " called from line " . $call['line'] . 
                    " of " . $call['file'] . "</p>\n";
            }
        }                  // for tracking down unexpected dumps

        // ensure that there is a label to use for the display
        if (!is_string($label) || strlen($label) == 0)
            $label	= $this->getName(Person::NAME_INCLUDE_DATES);

        $olddebug           = $debug;
        $debug		        = false;	// do not expand lower levels
        $output	            = "<p class='label'>\n  " .
        					    $label .
        					    "\n</p>\n" .
        					    "<table border='1'>\n";
        foreach($this->row as $key => $value)
        {
            $output	.= "    <tr>\n";
            $output	.= "      <th class='label'>\n";
            if ($key == 'id')
                $output	.= "<i>`$key`</i>";
            else
                $output	.= "`$key`";
            $output	.= "      </th>\n";
            $output	.= "      <td class='dataleft'>\n";
            if (is_null($value))
                $output	.= 'null';
            else
                $output	.= htmlspecialchars($value);

            if (strlen($value) > 0)
            switch($key)
            {
                case 'birthd':
                case 'chrisd':
                case 'deathd':
                case 'buriedd':
                case 'baptismd':
                case 'endowd':
                case 'confirmationd':
                case 'initiatoryd':
                {
                    $date	= new LegacyDate($value);
                    $output	.= ' = ' . $date->toString();
                    break;
                }		// dates

                case 'idlrbirth':
                case 'idlrchris':
                case 'idlrdeath':
                case 'idlrburied':
                {
                    if ($value > 0)
                    {
                        $loc    	= Location::getLocation($value);
                        $output	    .= ' = ' . $loc->toString();
                    } 
                    break;
                }		// locations

                case 'idtrbaptism':
                case 'idtrconfirmation':
                {
                    $kind	            = $this[substr($key,4) . 'kind'];
                    if ($value > 0)
                    {
                        if ($kind == 0)
                            $loc	    = Location::getLocation($value);
                        else
                            $loc	= new Temple(array('idtr' => $value));
                        $output	        .= ' = ' . $loc->toString();
                    }
                    break;
                }		// locations

                case 'idtrendow':
                case 'idtrinitiatory':
                {
                    if ($value > 0)
                    {
                        $loc	= new Temple(array('idtr' => $value));
                        $output	.= ' = ' . $loc->toString();
                    }
                    break;
                }		// locations

                default:
                {
                    break;
                }
            }	// field specific formatting
            $output	.= "      </td>\n";
            $output	.= "    </tr>\n";
        }	// loop through keys

        foreach($this->extras as $key => $value)
        {
            $output	        .= "  <tr>\n" .
            				    "    <th class='label'>\n" .
            				    "      '$key'" .
            				    "    </th>\n" .
            				    "    <td class='dataleft'>\n";
            if (is_null($value))
                $output	    .= '      null';
            else
                $output	    .= '      ' . htmlspecialchars($value) . "\n";
            $output	        .= "    </td>\n" .
                                "  </tr>\n";
        }	// loop through keys
        $output	            .= "  </table>\n";

        $debug              = $olddebug;
        if ($debug || $force)
            $warn		    .= $output;
        return $output;
    }		// Person::dump

    /********************************************************************
     *	function Person::save											*
     *																	*
     *  Save changes made to the Person record into the					*
     *  database.														*
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
     *	    Exception if user is not authorized to update the database	*
     ********************************************************************/
    function save($xml = false)
    {
        global	$debug;
        global	$warn;
        global	$userid;
        global	$connection;
        global	$familyTreeCookie;

        $retval                 = 0;
        if ($debug)
        {
            $backtrace          = debug_backtrace();
            foreach($backtrace as $call)
            {
                if (array_key_exists('class', $call) && 
                    strlen($call['class']) > 0)
                    $classPref      = $call['class'].'::';
                else
                    $classPref      = '';
                $warn   .= "<p>Person::save: " . __LINE__ .
                    " function=$classPref" . $call['function'] .
                    " called from line=" . $call['line'] .
                    " of " . $call['file'] . "</p>\n";
            }
            $this->dump("Person::save " . __LINE__);
            if (count($backtrace) > 12)
            {
                showTrace();
                exit;
            }
        }

        // get current date and time for added and updated fields in record
        // in expected internal format (not SQL date/time)
        $now				= getdate();
        $today				= $now['year'] * 10000 + $now['mon']*100 + $now['mday'];
        $hours				= $now['hours'];
        if (strlen($hours) == 1)
            $hours			= '0' . $hours;
        $mins				= $now['minutes'];
        if (strlen($mins) == 1)
            $mins			= '0' . $mins;
        $currtime			= $hours . ':' . $mins;

        // determine top level tag name for XML output
        $indent        		= '';
        if (is_string($xml))
        {			    // string tag name
            $matches   		= array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent		= $matches[1];
                $xml   		= $matches[2];
            }
            $tagname		= $xml;
            $xml	   		= true;
            print "$indent<$tagname>\n";
        }			    // string tag name
        else
        if ($xml)
        {			    // boolean true
            $indent         = "    ";
            $tagname		= 'personsave';
            print "$indent<personsave>\n";
            $backtrace 		= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
            foreach($backtrace as $call)
            {
                if (array_key_exists('class', $call) && 
                    strlen($call['class']) > 0)
                    $classPref      = $call['class'].'::';
                else
                    $classPref      = '';
                print "$indent    <trace>Person.inc: " . __LINE__ .
		                    " function=$classPref" . $call['function'] .
		                    " called from line=" . $call['line'] .
                                " of " . $call['file'] . "\n" .
                      "        </trace>\n";
            }
        }			    // boolean true

        if ($this['idir'] == 0)
            $this->needInsert       	= true;
        if ($this->needInsert)
        {			    // create new record
            $fldnames		            = '';
            $values		                = 'COALESCE(MAX(IDIR), 0) + 1' .
                                            ', :added, :addedtime' .
                                            ', :updated, :updatedtime';
            $sqlParms		            = array();
            $sqlParms['added']		    = $today;
            $sqlParms['addedtime']	    = $currtime;
            $sqlParms['updated']	    = $today;
            $sqlParms['updatedtime']	= $currtime;
            foreach($this->row as $field => $value)
            {
                switch($field)
                {		    // act on field name
                    case 'id':
                    {		// auto-increment key of record
                                // set by SQL server
                        break;
                    }		// auto-increment key of record

                    case 'idir':
                    {		// external key of record
                                // set by SQL code
                        break;
                    }		// external key of record

                    case 'added':
                    case 'addedtime':
                    case 'updated':
                    case 'updatedtime':
                    {		// date and time stamps
                        // set by PHP
                        break;
                    }		// date and time stamps

                    case 'surname':
                    {		// surname also updates soundex
                        $fldnames	.= ", `surname`, `soundslike`";
                        $values		.= ", :surname" .
                                           ", LEFT(SOUNDEX(:surname),4)";
                        $sqlParms['surname']	= $value;
                        break;
                    }		// surname also updates soundex

                    case 'soundslike':
                    {		// set under 'surname'
                        break;
                    }		// set under 'surname'

                    case 'ebirthd':		    // pseudo-field from Event
                    case 'ebirthsd':		// pseudo-field from Event
                    case 'edeathd':		    // pseudo-field from Event
                    case 'edeathsd':		// pseudo-field from Event
                    {		// hide fields
                        break;
                    }		// hide fields

                    default:
                    {		// all other fields
                        $fldnames		        .= ", `$field`";
                        $values			        .= ", :$field";
                        $sqlParms[$field]		= $value;
                        break;
                    }		// all other fields
                }		    // switch on field name
            }			    // loop through all fields in record

            $this->changed		= array();	// reset
            // construct the SQL INSERT command
            $insert		= 'INSERT INTO tblIR ' .
                                '(IDIR, Added, Addedtime, Updated, UpdatedTime'.
                                $fldnames . ') ' .
                                'SELECT ' . $values  . ' FROM tblIR';

            // insert the new record into the database
            $stmt		            = $connection->prepare($insert);
            $insertText		        = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd       = $insertText;
            if ($stmt->execute($sqlParms))
            {			        // success
                $retval		        = $stmt->rowCount();
                if ($xml)
                {
                    $tn             = 'save' . __LINE__;
                    print "$indent    <$tn result='$retval'>" . $insertText .
                        "</$tn>\n";
                }

                if ($debug)
                {
                    $warn	.= "<p>Person.inc: " . __LINE__ .
                                        " '$insertText'" .
                                        ", count=$retval</p>\n";
                }

                $this->changed		= array();	// reset
                $this->needInsert	= false;

                // update primary key value in record, as it was previously 0
                $id			        = $connection->lastInsertId();
                $this->row['id']	= $id;

                // obtain the updated record from the database
                // so the memory copy will include values set by the
                // SQL server or calculated in the INSERT statement
                $query		        = "SELECT * FROM tblIR WHERE ID=:id";
                $refParms	        = array('id'	=> $id);
                $stmt		        = $connection->prepare($query);
                $queryText	        = debugPrepQuery($query, $refParms);
                if ($stmt->execute($refParms))
                {		        // success
                    $this->row	    = $stmt->fetch(PDO::FETCH_ASSOC);
                    $idir	        = $this->row['idir'];
                    if ($xml)
                    {
                        $tn             = 'save' . __LINE__;
                        print "$indent    <$tn idir=\"$idir\">$queryText</$tn>\n";
                    }
                    if ($debug)
                        $warn	.= "<p>Person::save: " . __LINE__ .
                                   " query='$queryText'</p>\n";
                }		        // success
                else
                {		        // error performing query
                    throw new Exception("Person::save: '$queryText', " .
                                        print_r($stmt->errorInfo(),true));
                }		        // error performing query

                // log the update
                logSqlUpdate($insert,
                             $sqlParms,
                             $idir,
                             '',
                             $this->toJson(false));

                // make the current user an owner of the new individual
                $this->addOwner();

                // also create primary name entry
                if ($idir > 0)
                {               // have a valid record
                    if (!is_null($this->getPriName()))
                    {
                        $this->priName['idir']          = $idir;
	                    if ($this->priName['birthsd'] == -99999999 &&
	                        $this['birthsd'] != -99999999)
	                        $this->priName['birthsd']	= $this['birthsd'];
	                    $this->priName['treename']		= $this->treeName;
	                    $this->priName['givenname']		= $this['givenname'];
		                $this->priName['surname']		= $this['surname'];
	                    if ($xml)
	                        $this->priName->save('save' . __LINE__);
	                    else
	                        $this->priName->save(false);
                    }
                }               // have a valid record
            }			        // success
            else
            {			        // error inserting record
                $errorInfo	=  print_r($stmt->errorInfo(),true);
                $warn	.= "<p>Person::save: " . __LINE__ .
                                " insert='$insertText'," .
                                " errorInfo=$errorInfo<p>\n";
                error_log("Person.inc: " . __LINE__ .
                                " insert='$insertText', " .
                                "errorInfo=$errorInfo\n");
                if (strlen($insert) > 43)
                    $insert	= substr($insert, 0, 40) . '...';
                throw new Exception("Person::save:
                                    '$insertText', " .  $errorInfo);
            }			        // error inserting record

        }			            // create new record
        else
        if (count($this->changed) > 0)
        {			            // update existing record
            $retval                 = parent::save($xml);
            if ($xml)
            {
	            $backtrace          = debug_backtrace();
	            foreach($backtrace as $call)
	            {
	                if (array_key_exists('class', $call) && 
	                    strlen($call['class']) > 0)
	                    $classPref  = $call['class'].'::';
	                else
	                    $classPref  = '';
	            }
            }
            $idir	                = $this->row['idir'];

            // update primary alternate name entry
            if ($idir > 0)
            {                   // have a valid record
                if (!is_null($this->getPriName()))
                {
    	            if ($this->priName['birthsd'] == -99999999 &&
    	                $this['birthsd'] != -99999999)
    	                $this->priName['birthsd']	= $this['birthsd'];
                    $this->priName['treename']		= $this->treeName;
                    $this->priName['surname']		= $this['surname'];
                    $this->priName['givenname']		= $this['givenname'];
                    if ($xml)
                        $this->priName->save('save' . __LINE__);
                    else
                        $this->priName->save(false);
                }

	            // ensure that the alternate name entries for all of the
                // families this individual is a spouse in exist
                if ($this['gender'] == Person::FEMALE)
                {                   // wife
		            $families		        = $this->getFamilies();
		            foreach($families as $idmr => $family)
		            {               // loop through families
		                $nameParms	            = array('idir'		=> $this,
	            	                                    'idmr'		=> $family);
		                $altName	            = new Name($nameParms);
		                $altName['treename']	= $this->treeName;
		                if ($altName->get('birthsd') == -99999999 &&
		                    $this['birthsd'] != -99999999)
		                    $altName['birthsd']	= $this['birthsd'];
	                    if ($xml)
	                        $altName->save('save' . __LINE__);
	                    else
		                    $altName->save(false);
		            }		        // loop through families
                }                   // wife

	            // update given name field in main entry and
	            // married name entries
	            $updParms	= array('idir'		=> $idir,
	                                'type'	    => array(0, -1));
	            $newName	= $this->row['givenname'];
	            $setParms	= array('givenname'	=> $newName);
                $nameSet	= new RecordSet('Names', $updParms);
                $setInfo    = $nameSet->getInformation();
	            $result	    = $nameSet->update($setParms,
	                                           $xml);

	            // update birthsd field in all name entries
	            $updParms	= array('idir'		=> $idir);
	            $newBirth	= $this->row['birthsd'];
	            $setParms	= array('birthsd'	=> $newBirth);
	            $nameSet	= new RecordSet('Names', $updParms);
	            $result	    = $nameSet->update($setParms);
            }                   // have a valid record
        }		                // update existing record

        if ($xml)
            print "$indent</$tagname>\n";

        return $retval;
    }		// function Person::save

    /********************************************************************
     *	function Person::clearEvent										*
     *																	*
     *  This function is used by Event to remove information			*
     *	is being moved to Event.										*
     *																	*
     *	Input:															*
     *	    $eventid	string name of event							*
     ********************************************************************/
    function clearEvent($eventid)
    {
        switch(strtolower($eventid))
        {			// validate event id
            case 'birth':
            {			// events using IDLR
                parent::set($eventid . 'd', '');
                // do not clear birthsd
                parent::set($eventid . 'note', '');
                parent::set('idlr' . $eventid, 1);
                break;
            }			// events using IDLR

            case 'buried':
            case 'chris':
            case 'death':
            {			// events using IDLR
                parent::set($eventid . 'd', '');
                parent::set($eventid . 'sd', -99999999);
                parent::set($eventid . 'note', '');
                parent::set('idlr' . $eventid, 1);
                break;
            }			// events using IDLR

            case 'baptism':
            case 'confirmation':
            case 'endow':
            case 'initiatory':
            {			// events using IDTR
                parent::set($eventid . 'd', '');
                parent::set($eventid . 'sd', -99999999);
                parent::set($eventid . 'note', '');
                parent::set('idtr' . $eventid, 1);
                break;
            }			// events using IDTR
        }			// validate event id
    }		// Person::clearEvent

    /********************************************************************
     *	function Person::__toString							            *
     *																	*
     *	Get a textual representation of the individual.					*
     *																	*
     *	Returns:														*
     *	    Summary of individual as a string.							*
     ********************************************************************/
    function __toString()
    {
        return 	'Person.inc: ' . __LINE__ .
                ' idir=' . $this->row['idir'] . ', ' .
                'surname=' . $this->row['surname'] . ', ' .
                'givenname=' . $this->row['givenname'];
    }		// Person::__toString

}		// class Person

