<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editEvent.php														*
 *																		*
 *  Display a web page for editting one event from the family tree      *
 *  database which is represented by an instance of Event.				*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		type	numeric type value as used by the Citation			    *
 *				record to identify a specific event and which			*
 *				record type it is defined in.  If omitted the default	*
 *				value is 0.												*
 *																		*
 *		    idir parameter must point to Person record					*
 *				STYPE_NAME				= 1								*
 *				STYPE_BIRTH				= 2								*
 *				STYPE_CHRISTEN			= 3								*
 *				STYPE_DEATH				= 4								*
 *				STYPE_BURIED			= 5								*
 *				STYPE_NOTESGENERAL		= 6								*
 *				STYPE_NOTESRESEARCH		= 7								*
 *				STYPE_NOTESMEDICAL		= 8								*
 *				STYPE_DEATHCAUSE		= 9								*
 *				STYPE_LDSB				= 15  LDS Baptism				*
 *				STYPE_LDSE				= 16  LDS Endowment				*
 *				STYPE_LDSC				= 26  LDS Confirmation			*
 *				STYPE_LDSI				= 27  LDS Initiatory			*
 *																		*
 *		    idnx parameter points to Alternate Name Record tblNX		*
 *				STYPE_ALTNAME			= 10							*
 *																		*
 *		    idcr parameter points to Child Record tblCR					*
 *				STYPE_CHILDSTATUS		= 11 Child Status		   		*
 *				STYPE_CPRELDAD			= 12 Relationship to Father  	*
 *				STYPE_CPRELMOM			= 13 Relationship to Mother  	*
 *				STYPE_LDSP				= 17 Sealed to Parents			*
 *																		*
 *		    idmr parameter points to LegacyMarriage Record				*
 *				STYPE_LDSS				= 18 Sealed to Spouse			*
 *				STYPE_NEVERMARRIED		= 19 This individual nvr married*
 *				STYPE_MAR				= 20 Marriage					*
 *				STYPE_MARNOTE			= 21 Marriage Note				*
 *				STYPE_MARNEVER			= 22 Never Married				*
 *				STYPE_MARNOKIDS			= 23 This couple had no children*
 *				STYPE_MAREND			= 24 marriage ended **added**	*
 *																		*
 *		    ider parameter points to Event Record						*
 *				STYPE_EVENT				= 30 Individual Event,			*
 *											idir mandatory				*
 *				STYPE_MAREVENT			= 31 Marriage Event,			*
 *											idmr mandatory				*
 *																		*
 *		    idtd parameter points to To-Do records tblTD.IDTD			*
 *				STYPE_TODO				= 40 To-Do Item		   			*
 *																		*
 *		    a temp source type, also any negative numbers are temporary	*
 *				STYPE_TEMP				= 100 used to swap sources. 	*
 *																		*
 *		idir	unique numeric key of instance of Person				*
 *				required as defined above or 							*
 *		ider	unique numeric key of instance of Event					*
 *				if set to zero with type=STYPE_EVENT or STYPE_MAREVENT	*
 *				causes new Event record to be created.					*
 *		idnx	unique numeric key of instance of Alternate Name		*
 *				Record tblNX											*
 *		idcr	unique numeric key of instance of Child Record tblCR	*
 *		idmr	unique numeric key of instance of LegacyMarriage Record	*
 *		idtd	unique numeric key of instance of To-Do records			*
 *				tblTD.IDTD												*
 * 																		*
 *		givenname optionally explicitly supply given name of individual *
 *				if DB copy may not be current							*
 *		surname	optionally explicitly supply surname of individual		*
 *				if DB copy may not be current							*
 *		date	optionally explicitly supply date of event if DB copy	*
 *				may not be current										*
 *		descn	optionally explicitly supply description of event if	*
 *				DB copy may not be current								*
 *		location optionally explicitly supply location of event if DB	*
 *				copy may not be current 								*
 *		notes	optionally explicitly supply notes for event if DB		*
 *				copy may not be current 								*
 *		rownum	feedback row number for common event					*
 * 																		*
 *  History: 															*
 *		2010/08/08		set $ider for newly created Event				*
 *		2010/08/09		add input field for Order value					*
 *		2010/08/11		use htmlspecialchars to escape text values		*
 *		2010/08/16		change to LegacyCitationList interface			*
 * 		2010/08/21		Change to use new page format					*
 *		2010/08/28		implement delete citation						*
 *		2010/09/05		Permit explictly supplying name of individual	*
 *		2010/10/11		Simplify interface for adding citations			*
 *		2010/10/15		Use cookies to default to last source citation	*
 *						Remove header and trailer sections from dialog.	*
 *						Support all event types, not just Event			*
 *		2010/10/16		Use Event->getNotes()							*
 *		2010/10/17		Import citTable.inc and citTable.js to manage	*
 *						citations										*
 *		2010/10/19		Ensure $notes is not null for NAME event		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/29		move Notes after Location in dialog				*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2010/11/14		include prefix and title in fields for Name		*
 *						event											*
 *		2010/12/04		add link to help page							*
 *		2010/12/12		replace LegacyDate::dateToString with			*
 *						LegacyDate::toString							*
 *		2010/12/20		handle exception thrown by new LegacyIndiv		*
 *						handle exception thrown by new LegacyFamily		*
 *						handle exception thrown by new LegacyLocation	*
 *						improved handling of invalid parameters			*
 *		2011/01/02		add 4 LDS sacraments							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/01/22		clean up code									*
 *		2011/01/30		identify fact type in title of facts from		*
 *						indiv record									*
 *		2011/02/24		identify fact type in context specific help		*
 *						for notes										*
 *		2011/03/03		underline 'U' in "Update Event" button text		*
 *		2011/06/15		pass idmr to updateEvent.php					*
 *						support events in LegacyFamily record			*
 *		2011/07/29		handle new parameters date and location to		*
 *						supply explicit values of date and location		*
 *						of event										*
 *						Use LegacyLocation constructor to resolve		*
 *						short names										*
 *		2011/08/08		trim supplied location name						*
 *		2011/08/21		do not initially display Temple vs. Live kind	*
 *						row for generic event.							*
 *		2011/10/01		provide database lookup assist for setting		*
 *						location names									*
 *						document month name abbreviations in context	*
 *						help											*
 *						change name of class LegacyCitationList			*
 *		2011/11/19		display alternate names in the Name Event and	*
 *						provide a button to selectively delete an		*
 *						alternate name									*
 *		2011/12/23		always display married surnames					*
 *						display all events in dialog and permit adding,	*
 *						modifying, and deleting events.					*
 *						add help panels for all fields					*
 *		2012/01/08		reorder to put the event type before the date	*
 *		2012/01/13		change class names								*
 *						support supplying notes value through parm		*
 *						include <input type=checkbox> in flag events	*
 *						add "No Children" to list of marriage events	*
 *		2012/01/23		display loading indicator while waiting for		*
 *						response to changed in a location field			*
 *		2012/02/25		use tinyMCE for stylized editing of text notes	*
 *		2012/05/06		set explicit class for Order field				*
 *		2012/07/31		make names of individuals identified in the		*
 *						title of the event hyperlinks to the individual	*
 *						record											*
 *						add names of spouses to all marriage events		*
 *						expand date input field to display 24 characters*
 *		2012/08/01		permit invoker to explicitly override			*
 *						description field								*
 *		2012/08/12		support LDS sealed to parents event				*
 *						validate associated record for all events		*
 *						before using it									*
 *						permit setting temple ready indicator			*
 *		2012/10/17		do not attempt to create database objects if	*
 *						the numeric key is invalid						*
 *		2012/10/19		supplied given name and surname was not used	*
 *						by name event									*
 *		2012/10/30		ensure templeReady field default to unused		*
 *		2012/11/05		add support for tinyMCE editing of notes		*
 *		2012/11/22		Event::add removed and replaced by member		*
 *						method addEvent of LegacyIndiv and LegacyFamily	*
 *		2013/03/03		LegacyIndiv::getNextName now returns all		*
 *						alternate names									*
 *		2013/04/02		add support for citations for alternate names	*
 *		2013/04/24		add birth, marriage, and death registrations	*
 *		2013/05/26		use dialog in place of alert for new location	*
 *						name											*
 *		2013/07/04		for individual event recorded in instance of	*
 *						Event do not display event types recorded		*
 *						in other records.  This permits changing the	*
 *						event type without creating a new record		*
 *		2013/08/25		add clear button for note textarea				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/12		replace tables with CSS for layout				*
 *		2014/02/17		define local CSS for this form					*
 *		2014/02/19		add id to <form> 								*
 *		2014/02/24		use dialog to choose from range of locations	*
 *						instead of inserting <select> into the form		*
 *						location support moved to locationCommon.js		*
 *		2014/03/06		label class name changed to column1				*
 *		2014/03/10		ability to edit cause of death added to			*
 *						edit dialogue for normal death event so it		*
 *						can be removed from the edit Individual dialog	*
 *		2014/03/20		replace deprecated LegacyIndiv::getNumNames		*
 *						replace deprecated LegacyIndiv::getNextName		*
 *						wrap alternate name section of Name event in	*
 *						a fieldset for clarity							*
 *						wrap death cause section of Death event in		*
 *						a fieldset for clarity							*
 *						deprecated class LegacyCitationList replaced by	*
 *						calls to Citation::getCitations					*
 *		2014/04/08		LegacyAltName renamed to LegacyName				*
 *						management of citations to alternate names		*
 *						moved to EditName.php script					*
 *		2014/04/13		permit being invoked with just the IDER value	*
 *		2014/04/15		Display default citation while waiting for		*
 *						database server to respond to request for list	*
 *						of sources										*
 *						enable update of citation page number			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/04/30		refine headings for marriage events				*
 *		2014/05/30		use explicit style class actleftcit in			*
 *						template for new source citation to limit		*
 *						the width of the selection list to match the	*
 *						width of the display after the citation added	*
 *		2014/07/06		move textual interpretation of IDET here from	*
 *						Event class to support I18N						*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *						use LegacyTemple::getTemples to get list for	*
 *						<select>										*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/10/03		add support for associating instances of 		*
 *						Picture with an event.							*
 *		2014/10/15		events moved out of tblIR into tblER			*
 *		2014/11/19		provide alternative occupation input row		*
 *		2014/11/20		bad generated name for <input name="IDSR...">	*
 *		2014/11/27		use Event::getCitations							*
 *		2014/11/29		do not crash on new location					*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/04		global $debug not declared in function			*
 *						getDateAndLocation								*
 *		2014/12/12		missing parameter to LegacyTemple::getTemples	*
 *		2014/12/25		redirect debugging output to $warn				*
 *		2014/12/26		add rownum feedback parameter					*
 *		2015/03/07		use LegacyFamily::getHusbName and getWifeName	*
 *						instead of deprecated name fields				*
 *		2015/03/14		include Close button if errors					*
 *		2015/05/15		do not escape HTML tags in textarea, they are	*
 *						used by rich text editor						*
 *		2015/06/14		match field sizes in new citation to existing	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						display notes in a larger area					*
 *		2016/02/05		one trace message was printed instead of saved	*
 *		2016/02/06		use showTrace									*
 *		2017/01/03		undefined $checked								*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/15		class LegacyToDo renamed to class ToDo			*
 *		2017/09/12		use get( and set(								*
 *		2017/09/23		add a "Choose a Temple" option to temple select	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/18		use RecordSet instead of Temple::getTemples		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/02/11		add Close button								*
 *		2018/03/24		add button to control whether textareas are		*
 *						displayed as rich text or raw text				*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/08/01      support tinyMCE 5.0.3                           *
 *		2019/08/06      use editName.php to handle updates of Names     *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

    /********************************************************************
     *  $typeText														*
     *																	*
     *  Events which are implemented by fields inside a record other	*
     *  than an instance of Event (tblER), are distinguished			*
     *	by the citation type, passed as parameter type.  This table		*
     *	is used to modify the title of the dialog based upon the type.	*
     ********************************************************************/
    $typeText	= array(
					 0			=> 'Generic Fact',
					 1			=> 'Name Fact',
					 2			=> 'Birth Event',
					 3			=> 'Christening Event',
					 4			=> 'Death Event',
					 5			=> 'Buried Event',
					 6			=> 'General Notes',
					 7			=> 'Research Notes',
					 8			=> 'Medical Notes',
					 9			=> 'Cause of Death',
			 	    11			=> 'Child Status',	   
			 	    12			=> 'Relationship to Father',  
			 	    13			=> 'Relationship to Mother',  
					15			=> 'LDS Baptism Event',
					16			=> 'LDS Endowment Event',
					17			=> 'LDS Sealed to Parents Event',
					18			=> 'LDS Sealed to Spouse Event',
					19			=> 'Person Never Married Fact',
					20			=> 'Marriage Event',
					21			=> 'Marriage Note',
					22			=> 'Never Married Fact',
					23			=> 'This Couple had no Children Fact',
					24			=> 'Marriage Ended',
					26			=> 'LDS Confirmation Event',
					27			=> 'LDS Initiatory Event'
				);

    /********************************************************************
     *  $eventText														*
     *																	*
     *  This table provides a translation from an event type to the text*
     *  to display to the user.  This is ordered alphabetically for use	*
     *	constructing a select element in a web page.					*
     ********************************************************************/
    static $eventText	= array(
							 2 		=>'was adopted',
							63 		=>'also worked as a ',
							17 		=>'appeared in court',
							56 		=>'attended school',
					     15000 		=>'was baptized (LDS)',
							 8 		=>'was baptized (LDS)',
							 9 		=>'had his Bar Mitzvah',
							10 		=>'had her Bat Mitzvah',
							14 		=>'became a citizen',
							41 		=>'belonged to',
							71 		=>'birth was registered',
							11 		=>'was blessed (LDS)',
					      2000 		=>'was born',
							 3 		=>'was born',
					      5000 		=>'was buried',
							 4 		=>'was buried',
					      3000 		=>'was christened',
							 5 		=>'was christened',
							13 		=>'was circumcized',
							15 		=>'was confirmed',
					     26000 		=>'was confirmed (LDS)',
							16 		=>'was confirmed (LDS)',
							18 		=>'was cremated',
							72 		=>'death was registered',
							50 		=>'was described as ',
					      4000 		=>'died',
							 6 		=>'died',
							20 		=>'was divorced ',
							22 		=>'had education ',
							68 		=>'was elected as',
							23 		=>'emigrated from ',
							24 		=>'was employed as',
					     16000 		=>'was endowed (LDS)',
							74 		=>'was endowed (LDS)',
							25 		=>'got engaged',
							36 		=>'entered marriage contract',
							12 		=>'was enumerated in a census',
							66 		=>'identified with ethnicity',
							65 		=>'belonged to family group',
							21 		=>'filed for a divorce ',
							26 		=>'had first communion',
							67 		=>'had a funeral',
							27 		=>'graduated from',
							28 		=>'had a hobby of',
							29 		=>'was honored as',
							31 		=>'was ill with',
							32 		=>'immigrated',
							30 		=>'in hospital',
							60 		=>'in the military',
					     27000 		=>'initiatory (LDS)',
							75 		=>'initiatory (LDS)',
							33 		=>'was interviewed',
							54 		=>'lived',
							35 		=>'marriage banns issued',
							37 		=>'got a marriage license',
							39 		=>'got a marriage settlement',
							 7 		=>'marriage was annulled',
							73 		=>'marriage was registered',
							38 		=>'was married',
							69 		=>'married',
					     20000 		=>'married',			// obsolete
							77 		=>'marriage ended',
							70 		=>'',	// type is in description
							59 		=>'had a medical condition',
							40 		=>'had a medical event',
							42 		=>'served in the military',
							43 		=>'went on mission',
							44 		=>'was named for',
							64 		=>'had a nationality ',
							45 		=>'was naturalized',
							 1 		=>'null event 1',
							46 		=>'an obituary was published',
							49 		=>'was ordained as',
							48 		=>'ordinance (LDS)',
							34 		=>'owned land',
							52 		=>'owned property',
							61 		=>'had a photo',
							19 		=>'received a degree',
							53 		=>'belonged to the religious affiliation ',
							55 		=>'retired',
							76 		=>'sealed to spouse',
					     18000 		=>'sealed to spouse',	// obsolete
							57 		=>'had Social Security Number',
							62 		=>'had Social Security Number (62)',
							51 		=>'will was probated',
							58 		=>'wrote a will',
							47 		=>'worked as a ',
							 0 		=>'unused 0'
							);

    /********************************************************************
     *	function getEventType											*
     *																	*
     *	Get the type of the event as descriptive text.					*
     ********************************************************************/
    function getEventType($event)
    {
		global $eventText;

		$idet	= $event['idet'];
		if (array_key_exists($idet, $eventText))
		{
		    return $eventText[$idet];
		}
		else
		{
		    return "IDET=$idet";
		}
    }		// getEventType

    /********************************************************************
     *  static $personEvents											*
     *																	*
     *  This table is used to create the selection list for				*
     *	events specific to an individual.  Where the event type			*
     *	is greater than 999 it indicates an event recorded in the		*
     *	Person record itself, and the code is the citation				*
     *	type times 1000.												*
     ********************************************************************/

    static $personEvents	= array(
						    0			=> 'Choose an event type:',
						    1			=> '',
						    2			=> 'Adoption',
						    3			=> 'Birth',
						    4			=> 'Burial',
						    5			=> 'Christening',
						   16			=> 'Confirmation (LDS)',
						    6			=> 'Death',
						    8			=> 'Baptism',
						15000			=> 'Baptism (LDS)',
						    9			=> 'BarMitzvah',
						   10			=> 'BasMitzvah',
						   71			=> 'Birth Registration',
						   11			=> 'Blessing',
						   12			=> 'Census',
						   13			=> 'Circumcision',
						   14			=> 'Citizenship',
						   15			=> 'Confirmation',
						26000			=> 'Confirmation (LDS)',
						   17			=> 'Court',
						   18			=> 'Cremation',
						   72			=> 'Death Registration',
						   19			=> 'Degree',
						   22			=> 'Education',
						   68			=> 'Election',
						   23			=> 'Emigration',
						   24			=> 'Employment',
						16000			=> 'Endowment (LDS)',
						   66			=> 'Ethnicity',
						   65			=> 'Family Group',
						   26			=> 'First Communion',
						   67			=> 'Funeral',
						   27			=> 'Graduation',
						   28			=> 'Hobbies',
						   29			=> 'Honours',
						   30			=> 'Hospital',
						   31			=> 'Illness',
						27000			=> 'Initiatory (LDS)',
						   32			=> 'Immigration',
						   33			=> 'Interview',
						   34			=> 'Land',
						   40			=> 'Medical',
						   59			=> 'Medical Condition',
						   41			=> 'Membership',
						   60			=> 'Military',
						   42			=> 'Military Service',
						   43			=> 'Mission',
						   44			=> 'Namesake',
						   64			=> 'Nationality',
						   45			=> 'Naturalization',
						   46			=> 'Obituary',
						   47			=> 'Occupation',
						   63			=> 'Occupation 1',
						   48			=> 'Ordinance',
						   49			=> 'Ordination',
						   61			=> 'Photo',
						   50			=> 'Physical Description',
						   51			=> 'Probate',
						   52			=> 'Property',
						   53			=> 'Religion',
						   54			=> 'Residence',
						   55			=> 'Retirement',
						   56			=> 'School',
						   57			=> 'Social Security Number',
						   62			=> 'Soc Sec Num',
						   58			=> 'Will'
				        );


    /********************************************************************
     *  $marriageEvents													*
     *																	*
     *  This table is used to create the selection list for				*
     *	events specific to a marriage.  Where the event type			*
     *	is greater than 999 it indicates an event recorded in the		*
     *	Family record itself, and the code is the citation		        *
     *	type times 1000.												*
     ********************************************************************/

    static $marriageEvents	= array(
						    0			=> 'Choose an event type:',
						   69			=> 'Marriage',
						    7			=> 'Annulment',
						   20			=> 'Divorce',
						   25			=> 'Engagement',
						   21			=> 'Filed for Divorce',
						   36			=> 'Marriage Contract',
						24000			=> 'Marriage Ended Old',
						   77			=> 'Marriage Ended',
						   37			=> 'Marriage License',
						   38			=> 'Marriage Notice',
						   72			=> 'Marriage Registered',
						   39			=> 'Marriage Settlement',
						22000			=> 'Never Married',
						23000			=> 'No Children',
						   70			=> 'Other Marriage Fact',
						   76   		=> 'Sealed to Spouse',
						18000			=> 'Sealed to Spouse (LDS) Old'
						);


    /********************************************************************
     *  $idetTitleText													*
     *																	*
     *  This table is used to construct a customized title for events	*
     *	represented by a row in table tblER.							*
     ********************************************************************/
    static $idetTitleText	= array(
				Event::ET_NULL					=> '',
				Event::ET_ADOPTION				=> 'Adoption',
				Event::ET_BIRTH					=> 'Birth',
				Event::ET_BURIAL				=> 'Burial',
				Event::ET_CHRISTENING			=> 'Christening',
				Event::ET_DEATH					=> 'Death',
				Event::ET_ANNULMENT				=> 'Annulment',
				Event::ET_LDS_BAPTISM			=> 'LDS Baptism',
				Event::ET_BARMITZVAH			=> 'Bar Mitzvah',
				Event::ET_BASMITZVAH			=> 'Bas Mitzvah',
				Event::ET_BLESSING				=> 'LDS Blessing',
				Event::ET_CENSUS				=> 'Census Enumeration',
				Event::ET_CIRCUMCISION			=> 'Circumcision',
				Event::ET_CITIZENSHIP			=> 'Citizenship',
				Event::ET_CONFIRMATION			=> 'Confirmation',
				Event::ET_LDS_CONFIRMATION		=> 'LDS Confirmation',
				Event::ET_COURT					=> 'Court',
				Event::ET_CREMATION				=> 'Cremation',
				Event::ET_DEGREE				=> 'Degree',
				Event::ET_DIVORCE				=> 'Divorce',
				Event::ET_DIVORCE_FILING		=> 'Divorce Filing',
				Event::ET_EDUCATION				=> 'Education',
				Event::ET_EMIGRATION			=> 'Emigration',
				Event::ET_EMPLOYMENT			=> 'Employment',
				Event::ET_ENGAGEMENT			=> 'Engagement',
				Event::ET_FIRST_COMMUNION		=> 'First Communion',
				Event::ET_GRADUATION			=> 'Graduation',
				Event::ET_HOBBIES				=> 'Hobbies',
				Event::ET_HONOURS				=> 'Honours',
				Event::ET_HOSPITAL				=> 'Hospital',
				Event::ET_ILLNESS				=> 'Illness',
				Event::ET_IMMIGRATION			=> 'Immigration',
				Event::ET_INTERVIEW				=> 'Interview',
				Event::ET_LAND					=> 'Land',
				Event::ET_MARRIAGE_BANNS		=> 'Marriage Banns',
				Event::ET_MARRIAGE_CONTRACT		=> 'Marriage Contract',
				Event::ET_MARRIAGE_LICENSE		=> 'Marriage License',
				Event::ET_MARRIAGE_NOTICE		=> 'Marriage Notice',
				Event::ET_MARRIAGE_SETTLEMENT	=> 'Marriage Settlement',
				Event::ET_MEDICAL				=> 'Medical',
				Event::ET_MEMBERSHIP			=> 'Membership',
				Event::ET_MILITARY_SERVICE		=> 'Military Service',
				Event::ET_MISSION				=> 'Mission',
				Event::ET_NAMESAKE				=> 'Namesake',
				Event::ET_NATURALIZATION		=> 'Naturalization',
				Event::ET_OBITUARY				=> 'Obituary',
				Event::ET_OCCUPATION			=> 'Occupation',
				Event::ET_ORDINANCE				=> 'Ordinance',
				Event::ET_ORDINATION			=> 'Ordination',
				Event::ET_PHYSICAL_DESCRIPTION	=> 'Physical Description',
				Event::ET_PROBATE				=> 'Probate',
				Event::ET_PROPERTY				=> 'Property',
				Event::ET_RELIGION				=> 'Religion',
				Event::ET_RESIDENCE				=> 'Residence',
				Event::ET_RETIREMENT			=> 'Retirement',
				Event::ET_SCHOOL				=> 'School',
				Event::ET_SOCIAL_SECURITY_NUMBER=> 'Social Security Number',
				Event::ET_WILL					=> 'Will',
				Event::ET_MEDICAL_CONDITION		=> 'Medical Condition',
				Event::ET_MILITARY				=> 'Military',
				Event::ET_PHOTO					=> 'Photo',
				Event::ET_SOC_SEC_NUM			=> 'Social Security Number',
				Event::ET_OCCUPATION_1			=> 'Other Occupation',
				Event::ET_NATIONALITY			=> 'Nationality',
				Event::ET_FAMILY_GROUP			=> 'Family Group',
				Event::ET_ETHNICITY				=> 'Ethnicity',
				Event::ET_FUNERAL				=> 'Funeral',
				Event::ET_ELECTION				=> 'Election',
				Event::ET_MARRIAGE				=> 'Marriage',
				Event::ET_MARRIAGE_FACT			=> 'Marriage Fact',
				Event::ET_BIRTH_REGISTRATION	=> 'Birth Registration',
				Event::ET_DEATH_REGISTRATION	=> 'Death Registration',
				Event::ET_MARRIAGE_REGISTRATION	=> 'Marriage Registration',
				Event::ET_LDS_ENDOWED			=> 'LDS Endowed',
				Event::ET_LDS_INITIATORY		=> 'LDS Initiatory',
				Event::ET_LDS_SEALED			=> 'LDS Sealed',
				Event::ET_MARRIAGE_END			=> 'Marriage End');

/********************************************************************
 *	function getDateAndLocation										*
 *																	*
 *		If the values for date and location have been explicitly	*
 *		provided, use them.  Otherwise obtain the values from the	*
 *		associated database record.									*
 *																	*
 *  Parameters:														*
 *	    $record				data base record as instance of Record	*
 *	    $dateFldName		field name containing date of event		*
 *	    $locFldName			field name containing IDLR of location	*
 *							of event								*
 ********************************************************************/
function getDateAndLocation($record,
						    $dateFldName,
						    $locFldName)
{
	global	$debug;
	global	$warn;
	global	$date;
	global	$location;	// instance of Location
	global	$msg;
	global	$idlr;

	if (is_null($date))
	{		// date value not explicitly supplied
	    $date	= new LegacyDate($record->get($dateFldName));
	    $date	= $date->toString();
	}		// date value not explicitly supplied

	if (is_null($location))
	{		// location value not explicitly supplied
	    $idlr	= $record->get($locFldName);
	    if ($debug)
			$warn	.= "<p>\$idlr set to $idlr from field name '$locFldName'</p>\n";
	    $location	= new Location(array('idlr' 		=> $idlr));
	}		// location value not explicitly supplied
}		// getDateAndLocation

/********************************************************************
 *	function getDateAndLocationLds									*
 *																	*
 *	If the values for date and location have been explicitly		*
 *	provided, use them.  Otherwise obtain the values from the		*
 *	associated database record.										*
 *																	*
 *  Parameters:														*
 *	    $record				data base record as instance of Record	*
 *	    $kind				temple indicator						*
 *	    $dateFldName		field name containing date of event		*
 *	    $locFldName			field name containing IDLR of location	*
 *							of event								*
 ********************************************************************/
function getDateAndLocationLds($record,
						       $kind,
                               $dateFldName,
                               $locFldName)
{
	global $date;
	global $location;
	global $msg;
	global $idtr;

	if (is_null($date))
	{		        // date value not explicitly supplied
	    $date	        = new LegacyDate($record->get($dateFldName));
	    $date	        = $date->toString();
	}		        // date value not explicitly supplied
	$idtr		        = $record->get($locFldName);
	if ($kind == 1)
	    $location	    = new Temple(array('idtr' 		=> $idtr));
	else
	{		        // not in temple
	    if (is_null($location))
	    {	        // do not have explicit location
			$location	= Location::getLocation($idtr);
	    }	// do not have explicit location
	}		// not in temple
}		// function getDateAndLocationLds

/********************************************************************
 *	function getEventInfo											*
 *																	*
 *	Get information from an instance of Event						*
 *																	*
 *  Parameters:														*
 *	    $event				instance of Event						*
 ********************************************************************/
function getEventInfo($event)
{
	global	$etype;
	global	$idet;
	global	$order;
	global	$notes;
	global	$descn;
	global	$kind;
	global	$templeReady;
	global	$preferred;
	$etype		            = getEventType($event);
	if ($idet <= 1)
	    $idet	            = $event['idet'];	// numeric key of tblET
	$order		            = $event['order'];

	if (is_null($notes))
	{
	    $notes	            = $event['desc'];
	    if (is_null($notes))
			$notes	        = '';
	}

	if (is_null($descn))
	    $descn	            = $event['description']; 

	$templeReady	        = $event['ldstempleready'];
	$preferred	            = $event['preferred'];

	$kind		            = $event['kind'];
	if ($kind == 0)
	    getDateAndLocation($event,
					       'eventd',
					       'idlrevent');
	else
	    getDateAndLocationLds($event,
				    		  $kind,
				    		  'eventd',
				    		  'idlrevent');
}	// function getEventInfo

/********************************************************************
 *   OO  PPP  EEEE N  N     CC   OO  DDD  EEEE						*
 *  O  O P  P E    NN N    C  C O  O D  D E							*
 *  O  O PPP  EEE  N NN    C    O  O D  D EEE						*
 *  O  O P    E    N NN    C  C O  O D  D E							*
 *   OO  P    EEEE N  N     CC   OO  DDD  EEEE						*
 ********************************************************************/

// default title
$title				= 'Edit Event Error';
$heading			= 'Edit Event Error';

// safely get parameter values
// defaults
// parameter values from URI
$type				= 0;    // see Citation::STYPE_...
$ider				= null;	// index of Event
$idet				= null;	// index of EventType
$idir				= null;	// index of Person
$idnx				= null;	// index of Name
$idcr				= null;	// index of Child
$idmr				= null;	// index of Family
$idtd				= null;	// index of ToDo
$typetext			= null;	// error text for event type
$idertext			= null;	// error text for key of Event
$idettext			= null;	// error text for key of EventType
$idirtext			= null;	// error text for key of Person
$idnxtext			= null;	// error text for key of Name
$idcrtext			= null;	// error text for key of Child
$idmrtext			= null;	// error text for key of Family
$idtdtext			= null;	// error text for key of ToDo
$date				= null;
$descn				= null;
$location			= null;
$notes				= null;
$notmar				= null;
$nokids				= null;
$cremated			= null;
$deathCause			= null;
$picIdType			= null; // for invoking EditPictures dialog
$given				= '';
$surname			= '';
$rownum				= null;
$lang               = 'en';

// database records
$event				= null;	// instance of Event
$person				= null;	// instance of Person
$family				= null;	// instance of Family
$child				= null;	// instance of Child
$altname			= null;	// instance of Name
$todo				= null;	// instance of ToDo

// other
$readonly			= '';	// attribute value to insert in <input> elements
$submit				= false;

// process input parameters from the search string passed by method=get
if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n";
        $value      = trim($value); 
	    switch(strtolower($key))
	    {
	        case 'type':
	        {		// supplied event type
	            if (ctype_digit($value))
	            {
		            $type	        = intval($value);
		            if ($type == 0)
		                $readonly	= "readonly='readonly'";
		            // textual description of event type
		            if (array_key_exists($type, Citation::$intType))
		                $eventType	= Citation::$intType[$type];
		            else
	                    $eventType	= 'Invalid event type ' . $type;
                }
                else
                    $typetext       = htmlspecialchars($value);
	            break;
	        }		 // supplied event type
	
	        // get the event record identifier if present
	        case 'ider':
	        {
	            if (ctype_digit($value))
		            $ider	        = intval($value);
                else
                    $idertext       = htmlspecialchars($value);
		        break;
	        }		// ider
	
	        // get the event type identifier if present
	        case 'idet':
	        {
	            if (ctype_digit($value))
		            $idet	        = intval($value);
                else
                    $idettext       = htmlspecialchars($value);
	            break;
	        }		// idet
	
	        // get the key of instance of Person
	        case 'idir':
	        {
	            if (ctype_digit($value))
		            $idir	        = intval($value);
                else
                    $idirtext       = htmlspecialchars($value);
	            break;
	        }		// idir
	
	        case 'idnx':
	        {	// get the key of instance of Alternate Name Record tblNX
	            if (ctype_digit($value))
		            $idnx	        = intval($value);
                else
                    $idnxtext       = htmlspecialchars($value);
	            break;
	        }		//idnx
	
	        case 'idcr':
	        {		// key of instance of Child Record tblCR
	            if (ctype_digit($value))
		            $idcr	        = intval($value);
                else
                    $idcrtext       = htmlspecialchars($value);
	            break;
	        }		//idcr
	
	        case 'idmr':
	        {		// key of instance of Marriage Record
	            if (ctype_digit($value))
		            $idmr	        = intval($value);
                else
                    $idmrtext       = htmlspecialchars($value);
	            break;
	        }		//idmr
	
	        case 'idtd':
	        {		// key of instance of To-Do records tblTD.IDTD
	            if (ctype_digit($value))
		            $idtd	        = intval($value);
                else
                    $idtdtext       = htmlspecialchars($value);
	            break;
	        }		// idtd
	
	        // individual's name can be explicitly supplied for events
	        // associated with
	        // a new individual if that information is not available from the
	        // database record because it has not been written yet
	        case 'givenname':
	        {
	            $given		= htmlspecialchars($value);
	            break;
	        }		// given name
	
	        case 'surname':
	        {		// surname	
	            $surname	= htmlspecialchars($value);
	            break;
	        }		// surname
	
	        // the date, location, and notes field values in the DB record may
	        // not be current as a result of user activity
	        case 'date':
	        {		// date of event as an external string
	            $date		= htmlspecialchars($value);
	            break;
	        }		// date
	
	        case 'descn':
	        {		// description of the event
	            $descn		= htmlspecialchars($value);
	            break;
	        }		// descn
	
	        case 'location':
	        {		// location of the event
	            $location	= htmlspecialchars($value);
	            break;
	        }		// location
	
	        case 'idtr':
	        {		// key of temple
	            $idtr		= htmlspecialchars($value);
	            break;
	        }		// key of temple
	
	        case 'rownum':
	        {		// rownum for feedback about the event
	            $rownum		= htmlspecialchars($value);
	            break;
	        }		// rownum for feedback about the event
	
	        case 'notes':
	        {		// notes about the event
	            $notes		= htmlspecialchars($value);
	            break;
	        }		// notes about the event
	
	        case 'submit':
	        {		// control whether uses AJAX or submit
	            if (strtoupper($value) == 'Y')
	                $submit	= true;
	            break;
	        }		// control whether uses AJAX or submit
	
	        case 'debug':
	        {		// debug handled by common code
	            break;
	        }		// debug
	
	
	        case 'lang':
	        {
	            $lang       = FtTemplate::validateLang($value);
	            break;
	        }
	
	        case 'text':
	        case 'editnotes':
	        {
	            break;
	        }           // used by Javascript
	
	        default:
            {		    // other parameters
                $value  = htmlspecialchars($value);
	            $warn	.= "<p>Unexpected parameter $key='$value'</p>\n";
	            break;
	        }		    // other parameters
	    }	            // switch
	}		            // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}			            // invoked by method=get

if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';

// get template
$template               = new FtTemplate("editEvent$action$lang.html",
                                         true);
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

if (is_string($typetext))
	$msg		.= "Invalid value for Type='$typetext'. ";
if (is_string($idertext))
	$msg		.= "Invalid value for IDER='$idertext'. ";
if (is_string($idettext))
	$msg		.= "Invalid value for IDET='$idettext'. ";
if (is_string($idirtext))
	$msg		.= "Invalid value for IDIR='$idirtext'. ";
if (is_string($idnxtext))
	$msg		.= "Invalid value for IDNX='$idnxtext'. ";
if (is_string($idcrtext))
	$msg		.= "Invalid value for IDCR='$idcrtext'. ";
if (is_string($idmrtext))
	$msg		.= "Invalid value for IDMR='$idmrtext'. ";
if (is_string($idtdtext))
    $msg		.= "Invalid value for IDTD='$idtdtext'. ";

// create Event based upon IDER parameter
if (!is_null($ider) && $ider > 0)
{		    // existing event
    $event		    = new Event(array('ider' 		=> $ider));
    if ($event->isExisting() || $ider == 0)
    {
        $idet		= $event['idet'];
        if (!is_null($idir))
        {	// individual event
            $type	= 30;
        }	// individual event
        else
        if (!is_null($idmr))
        {	// marriage event
            $type	= 31;
        }	// married event
        else
        if (!is_null($idtd))
        {	// marriage event
            $type	= 40;
        }	// married event
        else
        if (!is_null($idnx))
        {	// marriage event
            $type	= 10;
        }	// married event
    }
    else
    {		// new Event failed
        $event	= null;
        $msg	.= "No existing event has IDER=$ider. ";
    }		// new Event failed
}		    // existing event
else
{		    // request to create new event
    $event	= null;		// is done later
}		    // request to create new event

// create Name record based upon IDNX keyword
if (!is_null($idnx))
{		    // IDNX was specified in parameters
    $altname		= new Name(array('idnx'			=> $idnx));
    if (!$altname->isExisting())
    {		// no matching Name
        $altname	= null;
        $msg	.= "Invalid name identification idNX=$idnx. ";
    }		// no matching Name 
}		    // IDNX was specified in parameters

// create Child record based upon IDCR keyword
if (!is_null($idcr))
{		    // IDCR was specified in parameters
    $child		    = new Child(array('idcr' 		=> $idcr));
    if ($child->isExisting)
    {       // have matching Child
        $idir		    = $child['idir'];
        $person		    = new Person(array('idir' 		=> $idir));
        $isOwner		= canUser('edit') && $person->isOwner();
        if (!$isOwner)
            $msg	    .= 'You are not authorized to edit " .
                            "the events of this child.  ';
        $idmr		    = $child['idmr'];
        $family		    = new Family(array('idmr' 		=> $idmr));
    }       // have matching Child
    else
    {		// no matching Child
        $child		= null;
        $msg	.= "Invalid child identification idcr=$idcr. " .
                   $e->getMessage();
        $idcr		= null;
    }		// no matching Child
}		    // IDCR was specified in parameters

// get the associated individual record
if (!is_null($idir))
{		    // IDIR was specified in parameters
    $person		        = new Person(array('idir' 		=> $idir));
    if ($person->isExisting())
    {
        $isOwner		= canUser('edit') && 
                          $person->isOwner();
        if (!$isOwner)
            $msg	.= 'You are not authorized to edit the events of this individual.  ';

        // if name of individual not supplied,
        // get it from Person record
        if (strlen($given) == 0)
            $given		= $person->getGivenname();
        if (strlen($surname) == 0)
            $surname	= $person->getSurname();
        $heading	= "Edit Event for " .
            "<a href=\"Person.php?idir=$idir\">$given $surname</a>";
        $title	= "Edit Event for $given $surname";
    }		// try creating instance of Person
    else
    {		// error creating individual
        $person	        = null;
        $idime	        = -1;
        $given	        = '';
        $surname	    = '';
        $msg	        .= "Invalid Value of idir=$idir. ";
        $idir	        = null;
    }		// error creating individual
}		    // IDIR was specified in parameters

// get the To-Do record, under construction
if (!is_null($idtd))
{		    // IDTD was specified in parameters
	$todo	        = new ToDo(array('idtd' 		=> $idtd));
    if ($todo->isExisting())
    {
    }
    else
    {       // no match
	    $todo	= null;
	    $msg	.= "Invalid To Do identification idtd=$idtd. ";
	}		// no match
}		    // IDTD was specified in parameters

// create Family record based upon IDMR keyword
if (!is_null($idmr))
{           // IDMR was specified in parameters
    $family		    = new Family(array('idmr' 		=> $idmr));
    if ($family->isExisting())
    {
        $husbname		= $family->getHusbName();
        $idirhusb		= $family['idirhusb'];
        $wifename		= $family->getWifeName();
        $idirwife		= $family['idirwife'];
        $heading	    = "Edit Event for Family of ";
        if ($idirhusb > 0)
        {		// husband identified
            $heading	.= "<a href=\"Person.php?idir=$idirhusb\">$husbname</a>";
            if ($idirwife > 0)
            {	// both spouses identified
                $heading	.= " and ";
            }	// both spouses identified
        }		// husband identified
	
        if ($idirwife > 0)
        {		// wife identified
            $heading	.= "<a href=\"Person.php?idir=$idirwife\">$wifename</a>";
        }		// wife identified
    }           // existing Family record
    else
    {		    // invalid IDMR value
        $family	= null;
        $msg	.= "Invalid family identification idmr=$idmr. ";
        $idmr	= null;
    }		    // invalid IDMR value
}               // IDMR was specified in parameters

if (strlen($msg) == 0)
{
    // validate the presence of parameters depending upon
    // the value of the type parameter
    // identify the fields in the associated record that are
    // updated for each type of event

    // default that all fields are unsupported
    if ($ider === 0 & $idet > 1)
    {
	    $event		        = new Event(array('ider'			=> 0,
	                                          'idet'			=> $idet,
	                                          'idir'			=> $idir));
	    $event->save(false);
	    $ider				= $event['ider'];
	    $idime				= $ider;	// key for citations
    }

    $etype					= null;
    $order					= null;
    $idlr					= null;
    $kind					= null;
    $prefix					= null;
    $nametitle				= null;
    $templeReady			= null;
    $preferred				= null;

    switch($type)
    {		// take action according to type
	    case Citation::STYPE_UNSPECIFIED:		// 0;
	    {	// type not determined yet
	        // will be either IDCR, IDIR, IDMR, or IDER based event
	        if (is_int($idcr) && $idcr > 0)
	        {		// IDCR based event
                $idime	    = $idcr;
                $text       = $template['headingGenericChild']->innerHTML;
                $heading    = str_replace(array('$idir','$lang','$given','$surname'),
                                          array($idir, $lang, $given, $surname),
                                          $text);
	        }		// IDCR based event
	        else
	        if (is_int($idir) && $idir > 0)
	        {		// IDIR based event
                $idime	    = $idir;
                $text       = $template['headingGenericPerson']->innerHTML;
                $heading    = str_replace(array('$idir','$lang','$given','$surname'),
                                          array($idir, $lang, $given, $surname),
                                          $text);
	        }		// IDIR based event
	        else
	        if (is_int($idmr) && $idmr > 0)
	        {		// IDMR based event
	            $idime	    = $idmr;
                $text       = $template['headingGenericPerson']->innerHTML;
                $heading    = str_replace(array('$idirhusb','$idirwife','$lang','$husbname','$wifename'),
                                          array($idirhusb, $idirwife, $lang, $husbname, $wifename),
                                          $text);
	        }		// IDMR based event
	        else
	        {
	            $msg	    .= $template['missingIDIME']->innerHTML;
	        }
	        $etype	        = '';
	        $idet	        = 0;
	        break;
	    }	// type not determined yet
	
	    //    idir parameter points to Person record
	 	case Citation::STYPE_NAME:		// 1
	    {
	        if (is_null($idir) || $idir == 0)
	        {		        // individual event requires IDIR
	            $msg		.= 'mandatory idir parameter missing. ';
	            $given		= 'Unknown';
	        }		        // individual event requires IDIR
	        else
	        {		        // proceed with edit
	            $name       = new Name(array('idir'     => $idir,
	                                         'order'    => Name::PRIMARY));
	            $idnx       = $name['idnx'];
	            header("Location: /FamilyTree/editName.php?idnx=$idnx");
	            exit;
	
	        }		        // proceed with edit
	        break;
	    }                   // primary name of individual
	
	 	case Citation::STYPE_BIRTH:		    // 2
	 	case Citation::STYPE_CHRISTEN:		// 3
	 	case Citation::STYPE_DEATH:		    // 4
	 	case Citation::STYPE_BURIED:		// 5
	 	case Citation::STYPE_NOTESGENERAL:	// 6
	 	case Citation::STYPE_NOTESRESEARCH:	// 7
	 	case Citation::STYPE_NOTESMEDICAL:	// 8
	 	case Citation::STYPE_DEATHCAUSE:	// 9
	 	case Citation::STYPE_LDSB:		    // 15  LDS Baptism
	 	case Citation::STYPE_LDSE:		    // 16  LDS Endowment
	 	case Citation::STYPE_LDSC:		    // 26  LDS Confirmation
	 	case Citation::STYPE_LDSI:		    // 27  LDS Initiatory
	    {
	        if (is_null($idir))
	        {		// individual event requires IDIR
	            $msg		.= 'mandatory idir parameter missing. ';
	            $given		= 'Unknown';
	        }		// individual event requires IDIR
	        else
	        {		// proceed with edit
	            $idime		= $idir;	// key for citations
	            $heading	= "Edit " . $typeText[$type] .
	    " for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
	            if ($type <= Citation::STYPE_BURIED &&
	 		        $type >= Citation::STYPE_BIRTH)
	                $picIdType	= $type - 1;
	        }		// proceed with edit
	        break;
	    }
	
	    //    idnx parameter points to Alternate Name Record tblNX
	 	case Citation::STYPE_ALTNAME:	// 10
	    {
	        if (is_null($idnx))
	            $msg		.= 'Mandatory idnx parameter missing. ';
	        else
	        {
	            header("Location: /FamilyTree/editName.php?idnx=$idnx");
	            exit;
	        }
	        break;
	    }
	
	    //    idcr parameter points to Child Record tblCR
	 	case Citation::STYPE_CHILDSTATUS:	// 11 Child Status	   
	 	case Citation::STYPE_CPRELDAD:		// 12 Relationship to Father  
	 	case Citation::STYPE_CPRELMOM:		// 13 Relationship to Mother  
	 	case Citation::STYPE_LDSP:		// 17 Sealed to Parents
	    {
	        if (is_null($idcr))
	            $msg		.= 'Mandatory idcr parameter missing. ';
	        else
	            $idime	    = $idcr;	// key for citations
	        $heading	    = "Edit " . $typeText[$type] .
	            " for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
	        break;
	    }
	
	    //    idmr parameter points to LegacyMarriage Record
	 	case Citation::STYPE_LDSS:		// 18 Sealed to Spouse
	 	case Citation::STYPE_NEVERMARRIED:	// 19 individual never married 
	 	case Citation::STYPE_MAR:		// 20 Marriage	
	 	case Citation::STYPE_MARNOTE:		// 21 Marriage Note
	 	case Citation::STYPE_MARNEVER:		// 22 Never Married
	 	case Citation::STYPE_MARNOKIDS:		// 23 No children  
	 	case Citation::STYPE_MAREND:		// 24 marriage end date
	    {		// event defined in marriage record
	        $heading		= "Edit " . $typeText[$type];
	        if (is_null($idmr))
	        {
	            $msg		.= 'Mandatory idmr parameter missing. ';
	        }
	        else
	        {
	            $idime		= $idmr;	// key for citations
	            if ($family)
	            {		// family specified
	                $heading	.= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
	 		    if ($type == Citation::STYPE_MAR)
	                    $picIdType	= Picture::IDTYPEMar;
	            }		// family specified
	        }
	        break;
	    }		// event defined in marriage record
	
	    //    ider parameter points to Event Record
	 	case Citation::STYPE_EVENT:	// 30 Individual Event
	    {
	        if (is_null($event))
	        {
	            $event	= new Event(array('ider' 		=> 0,
	                                      'idir' 		=> $idir));
	        }
	
	        // get the supplied value of the event subtype
	        if ($idet > 1)
	            $event->setIdet($idet);
	
	        $idime	                = $ider;	// key for citations
	        if ($debug)
	            $warn	.= "<p>\$idir set to $idir from event IDER=$ider</p>\n";
	        if (is_null($person))
                $person	        = Person::getPerson($idir);
            if (!is_null($person))
            {
	            if ($ider == 0 && $idet > 1)
	            {		// create new individual event
	                $event	        = $person->addEvent();
	                $ider	        = $event['ider'];
	            }		// create new individual event
	
	            // if name of individual not supplied, get it from Person record
	            if (strlen($given) == 0)
	                $given		= $person->getGivenName();
	            if (strlen($surname) == 0)
	                $surname	= $person->getSurname();
	            $typetext	=  $idetTitleText[$idet];	
	            $heading    = "Edit $typetext Event for <a href=\"Person.php?idir=$idir\">$given $surname</a>";
	            $picIdType	= Picture::IDTYPEEvent;
	        }		// try creating individual
            else
	        {		// error creating individual
	            $person		= null;
	            $idime		= -1;
	            $given		= '';
	            $surname	= '';
	            $heading	= 'Invalid Value of IDIR';
	            $msg		.= $e->getMessage();
	            $msg		.= ', Unable to create individual event because idir parameter missing or invalid. ';
	        }		// error creating individual
	
	        break;
	    }
	
	 	case Citation::STYPE_MAREVENT:	// 31 Marriage Event
	    {
	        if (is_null($idet))
	            $heading	= 'Edit Marriage fact';
	        else
	            $heading	= 'Edit ' . ucfirst(Event::$eventText[$idet]) . ' Event';
	        if ($family)
	        {		// family specified
	            $heading.= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
	        }		// family specified
	
	        if ($ider == 0)
	        {		// create new marriage event
	            if (!is_null($family))
	            {
	                $event	= $family->addEvent();
	                $ider	= $event['ider'];
	
	                // set the supplied value of the event subtype
	                if (!is_null($idet))
	                    $event->setIdet($idet);
	            }
	            else
	            {
	                $msg	.= 'Unable to create family event because idmr parameter missing or invalid. ';
	            }
	        }		// create new event
	        else
	        {		// existing event
	            $idmr		        = $event['idir'];
	            $family		        = new Family(array('idmr' 		=> $idmr));
	            $tidet		        = $event['idet'];
	            if ($tidet == 70)
	                $heading	= 'Edit ' . ucfirst($event['description']) . ' Event';
	            else
	                $heading	= 'Edit ' . ucfirst(Event::$eventText[$tidet]) . ' Event';
	
	            $heading	    .= " for <a href=\"Person.php?idir=$idirhusb\">$husbname</a> and <a href=\"Person.php?idir=$idirwife\" class=\"female\">$wifename</a>";
	        }		// existing event
	
	        $idime	= $ider;	// key for citations
	        $picIdType	= Picture::IDTYPEEvent;
	        break;
	    }
	
	    //    idtd parameter points to To-Do records tblTD.IDTD
	 	case Citation::STYPE_TODO:		// 40 To-Do Item
	    {
	        if (is_null($idtd) || $idtd == 0)
	        {
	            $msg		.= 'Mandatory idtd parameter missing. ';
	            $todo		= null;
	            break;
	        }
	        $idime	        = $idtd;	// key for citations
	        $heading	    = "Edit To Do Fact: IDTD=$idtd";
	        break;
	    }
	
	    default:
	    {
	        $msg	        .= 'Invalid event type ' . $type;
	        $idime	        = -1;
	        $heading	    = 'Invalid Event Type'; 
	    }
    }		// take action according to type

    switch($type)
    {		// act on major event type
	    case Citation::STYPE_UNSPECIFIED:	// 0
	    {	// to be determined
	        break;
	    }	// to be determined
	
	    case Citation::STYPE_NAME:		// 1
	    {
	        if ($person)
	        {
	            if (is_null($notes))
	            {
	                $notes	= $person['namenote'];
	                if (is_null($notes))
	                    $notes	= '';
	            }
	
	            $prefix	= $person['prefix'];
	            if (is_null($prefix))
	                $prefix	= '';
	
	            $nametitle	= $person['title'];
	            if (is_null($nametitle))
	                $nametitle	= '';
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_BIRTH:		// 2
	    {
	        if ($person)
	        {
	            $event		= $person->getBirthEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	            $kind		= null;
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_CHRISTEN:		// 3
	    {
	        if ($person)
	        {
	            $event		= $person->getChristeningEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	            $kind		= null;
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_DEATH:		// 4
	    {
	        if ($person)
	        {
	            $event		= $person->getDeathEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	            $kind		= null;
	
	            $deathCause	= $person['deathcause'];
	            if (is_null($deathCause))
	                $deathCause	= '';
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_BURIED:		// 5
	    {
	        if ($person)
	        {
	            $event		= $person->getBuriedEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	            $kind		= null;
	            if ($descn == '')
	            {
	                $descn	= null;
	                $cremated	= false;
	            }
	            else
	            if ($descn == 'cremated')
	            {
	                $descn	= null;
	                $cremated	= true;
	            }
	            else
	                $cremated	= false;
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_NOTESGENERAL:	// 6
	    {
	        if ($person)
	        {
	            if (is_null($notes))
	            {
	                $notes	= $person['notes'];
	                if (is_null($notes))
	                    $notes	= '';
	            }
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_NOTESRESEARCH:	// 7
	    {
	        if ($person)
	        {
	            $date	= null;
	            $location	= null;
	            if (is_null($notes))
	            {
	                $notes	= $person['references'];
	                if (is_null($notes))
	                    $notes	= '';
	            }
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_NOTESMEDICAL:	// 8
	    {
	        if ($person)
	        {
	            if (is_null($notes))
	            {
	                $notes	= $person['medical'];
	                if (is_null($notes))
	                    $notes	= '';
	            }
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_DEATHCAUSE:	// 9
	    {
	        if ($person)
	        {
	            if (is_null($notes))
	            {
	                $notes	= $person['deathcause'];
	                if (is_null($notes))
	                    $notes	= '';
	            }
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_LDSB:		// 15
	    {
	        if ($person)
	        {
	            $event		= $person->getBaptismEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_LDSE:		// 16
	    {
	        if ($person)
	        {
	            $event		= $person->getEndowEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_LDSC:		// 26
	    {
	        if ($person)
	        {
	            $event		= $person->getConfirmationEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	        }		// individual defined
	        break;
	    }
	
	    case Citation::STYPE_LDSI:		// 27
	    {
	        if ($person)
	        {
	            $event		= $person->getInitiatoryEvent(true);
	            $ider	= $event['ider'];
	            if ($ider > 0)
	            {
	                $idime	= $ider;
	                $type	= Citation::STYPE_EVENT;
	            }
	            getEventInfo($event);
	        }		// individual defined
	        break;
	    }
	
	 	case Citation::STYPE_ALTNAME:		// 10
	    {
	        $notes	= '';
	        break;
	    }
	
	    //    idcr parameter points to Child Record tblCR
	 	case Citation::STYPE_CHILDSTATUS:	// 11 Child Status	   
	    {
	        if ($child)
	        {
	            $notes	= '';
	        }		// child record present
	        break;
	    }
	
	 	case Citation::STYPE_CPRELDAD:	// 12 Relationship to Father  
	    {
	        if ($child)
	        {
	            $notes	= '';
	        }		// child record present
	        break;
	    }
	
	 	case Citation::STYPE_CPRELMOM:	// 13 Relationship to Mother  
	    {
	        if ($child)
	        {
	            $notes	= '';
	        }		// child record present
	        break;
	    }
	
	 	case Citation::STYPE_LDSP:	// 17 Sealed to Parents
	    {
	        if ($child)
	        {
	            getDateAndLocationLds($child,
	                              1,
	                              'parseald',
	                              'idtrparseal');
	            $notes		= $child['parsealnote'];
	            if (is_null($notes))
	                $notes	= '';
	            $templeReady	= $child['ldsp'];
	        }		// child record present
	        break;
	    }
	
	    //    idmr parameter points to LegacyMarriage Record
	 	case Citation::STYPE_LDSS:	// 18 Sealed to Spouse
	    {
	        if ($family)
	        {
	            getDateAndLocationLds($family,
	                              1,
	                              'seald',
	                              'idtrseal');
	            $templeReady	= $family['ldss'];
	        }		// family defined
	        break;
	    }
	
	 	case Citation::STYPE_NEVERMARRIED:// 19 individual never married 
	 	case Citation::STYPE_MARNEVER:	// 22 Never Married
	    {
	        if ($family)
	        {
	        $notmar	= $family['notmarried'];
	        if ($notmar == '')
	            $notmar	= 0;
	        }		// family defined
	        break;
	    }
	
	 	case Citation::STYPE_MAR:		// 20 Marriage	
	    {
	        if ($family)
	        {
	        getDateAndLocation($family,
	                        'mard',
	                        'idlrmar');
	        }		// family defined
	        break;
	    }
	
	 	case Citation::STYPE_MARNOTE:	// 21 Marriage Note
	    {
	        if (is_null($family && $notes))
	        {
	            $notes	= $family['notes'];
	            if (is_null($notes))
	                $notes	= '';
	        }		// family defined
	        break;
	    }
	
	 	case Citation::STYPE_MARNOKIDS:	// 23 couple had no children  
	    {
	        if ($family)
	        {
	        $nokids	= $family['nochildren'];
	        if ($nokids == '')
	            $nokids	= 0;
	        }		// family defined
	        break;
	    }
	
	 	case Citation::STYPE_MAREND:	// 24 marriage ended date
	    {
	        if ($family)
	        {
	        $date	= new LegacyDate($family['marendd']);
	        $date	= $date->toString();
	        }		// family defined
	        break;
	    }
	 	case Citation::STYPE_EVENT:	// 30 Individual Event
	    {
	        if ($event)
	        {
	            getEventInfo($event);
	            $kind		= null;
	
	            if ($idet == Event::ET_DEATH)
	            {
	                $deathCause	= $person['deathcause'];
	                if (is_null($deathCause))
	                    $deathCause	= '';
	            }
	        }		// event defined
	        break;
	    }	// Citation::STYPE_EVENT
	
	 	case Citation::STYPE_MAREVENT:	// 31 Marriage Event
	    {
	        if ($event)
	        {
	            getEventInfo($event);
	            $kind		= null;
	        }		// event defined
	        break;
	    }	// Citation::STYPE_MAREVENT
	
	    //    idtd parameter points to To-Do records tblTD.IDTD
	 	case Citation::STYPE_TODO:	// 40 To-Do Item
	    {
	        $notes	= '';
	        break;
	    }
	
	    default:				// unsupported values
	    {
	        break;
	    }

    }		// act on major event type

    /********************************************************************
     *  If the location is in the form of a string, obtain the			*
     *  associated instance of Location.  This will ensure that			*
     *  short form names are resolved, and the name is displayed with	*
     *  the proper case. Also format the location name so that it can	*
     *  be inserted into the value attribute of the text input field.	*
     ********************************************************************/
    if (!is_null($location))
    {		// location supplied
	    if (is_string($location))
	    {
	        $locName	= $location;
	        $location	= new Location(array('location' 		=> $locName));
	        if (!$location->isExisting())
	            $location->save(false);
	        $idlr	= $location->getIdlr();
	        if ($debug)
	            $warn	.= "<p>\$idlr set to $idlr from location '$locName'</p>\n";
	    }
	    $locName	= str_replace('"','&quot;',$location->getName());
    }		// location supplied
    else	// location not supplied
        $locName	= '';
}
    htmlHeader($heading,
	            array(  '/jscripts/tinymce/js/tinymce/tinymce.js',
	                    '/jscripts/js20/http.js',
	                    '/jscripts/CommonForm.js',
	                    '/jscripts/util.js',
	                    '/jscripts/Cookie.js',
	                    '/jscripts/locationCommon.js',
	                    '/jscripts/templeCommon.js',
	                    'editEvent.js'),
	            true, 'dialog');
?>
  <body>
    <div class="body">
      <script>
        tinyMCEparms.onchange_callback  = 'changeOccupation';
        tinyMCEparms.valid_elements     = 'a[href|class|target], span[*], br'
      </script>
      <template>
        <span class="right">
          <a href="editEventHelpen.html" target="help">? Help</a>
        </span>
        <?php print $heading; ?>
      </template>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {		// errors
?>
    <p class="message"><?php print $msg;?></p>
  <form name="evtForm" id="evtForm" action="donothing" method="post">
      <button type="button" id="close">
    Close
      </button>
  </form>
<?php
    }		// errors
    else
    {		// no errors
        if (false)
        {
?>
<p>
debug:
    $etype = <?php if (is_null($etype)) print 'null'; else  print "'$etype'"; ?>,
    $idet = <?php if (is_null($idet)) print 'null'; else  print $idet; ?>,
    $ider = <?php if (is_null($ider)) print 'null'; else  print $ider; ?>,
    $idir = <?php if (is_null($idir)) print 'null'; else  print $idir; ?>,
    $idmr = <?php if (is_null($idmr)) print 'null'; else  print $idmr; ?>,
    $date = <?php if (is_null($date)) print 'null'; else  print "'$date'"; ?>,
    $order = <?php if(is_null($order)) print 'null'; else print $order;?>,
    $notes = <?php if (is_null($notes)) print 'null'; else print "'$notes'"; ?>,
    $descn = <?php if (is_null($descn)) print 'null'; else print "'$descn'"; ?>,
    $idlr = <?php if (is_null($idlr)) print 'null'; else  print $idlr; ?>,
    $location = <?php if (is_null($location)) print 'null'; else  print "'$location'";?>
</p>
<?php
        }		// debug
?>
  <form name="evtForm" id="evtForm" action="updateEvent.php" method="post">
    <div id="hidden">
<?php
    if (!is_null($idir))
    {
?>
    <input type="hidden" name="idir" id="idir"
            value="<?php print $idir; ?>">
<?php
    }
    if (!is_null($idmr))
    {
?>
    <input type="hidden" name="idmr" id="idmr"
            value="<?php print $idmr; ?>">
<?php
    }
    if (!is_null($ider))
    {
?>
    <input type="hidden" name="ider" id="ider"
            value="<?php print $ider; ?>">
<?php
    }
    if (!is_null($idet))
    {
?>
    <input type="hidden" name="idet" id="idet"
            value="<?php print $idet; ?>">
<?php
    }
    if (!is_null($idcr))
    {
?>
    <input type="hidden" name="idcr" id="idcr"
            value="<?php print $idcr; ?>">
<?php
    }
    if (!is_null($rownum))
    {
?>
    <input type="hidden" name="rownum" id="rownum"
            value="<?php print $rownum; ?>">
<?php
    }
?>
    <input type="hidden" name="type" id="type" 
            value="<?php print $type; ?>">
    </div> <!-- id="hidden" -->
<?php

    // display a selection list of event types if supported by the
    // event
    if (!is_null($idet))
    {		// event type supported
?>
    <div class="row" id="typeRow">
      <label class="column1" for="etype">
    Event Type:
      </label>
    <select name="etype" id="etype" class="white left">
<?php
            if ($idir)
            {	// event applies to an individual
                foreach($personEvents as $et => $mtype)
                {	// loop through individual events
                    if ($type == 30 && $et > 999)
                        continue;
                    if ($et == (int)$idet)
                        $selected = ' selected="selected"';
                    else
                        $selected = "";
?>
            <option value="<?php print $et; ?>" <?php print $selected; ?>>
                <?php print $mtype; ?> 
            </option>
<?php
                }	// loop through individual events
            }	// event applies to an individual
            else
            {	// event applies to a family
                foreach($marriageEvents as $et 	=> $mtype)
                {	// loop through family events
                    if ($type == 31 && $et > 999)
                        continue;
                    if ($et == (int)$idet)
                        $selected = ' selected="selected"';
                    else
                        $selected = "";
?>
            <option value="<?php print $et; ?>" <?php print $selected; ?>>
                <?php print $mtype; ?> 
            </option>
<?php
                }	// loop through family events
            }	// event applies to a family
?>
        </select>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// event type supported

    // display a date field if the event includes a date
    if (!is_null($date))
    {		// date supported
?>
    <div class="row" id="dateRow">
      <label class="column1" for="date">
        Date:
      </label>
    <input type="text" size="24" name="date" id="date" class="white left"
        <?php print $readonly; ?> value="<?php print $date; ?>">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// date supported

    // provide a description input field if supported
    if (!is_null($descn))
    {		// description supported
        $qdescn	= str_replace('"','&quot;',$descn);
        $edescn	= htmlspecialchars($descn);
?>
    <div class="row" id="descRow">
<?php
        switch($idet)
        {		// act on specific IDET values
            case Event::ET_OCCUPATION:
            case Event::ET_OCCUPATION_1:
            {
?>
      <label class="column1" for="occupation">
    Occupation:
      </label>
    <textarea name="occupation" id="occupation"
        maxlength="255" class="white leftnc" cols="64" rows="4"
        <?php print $readonly; ?>><?php print $edescn; ?></textarea>
<?php
                break;
            }	// occupations

            default:
            {	// other event types
?>
      <label class="column1" for="description">
    Description:
      </label>
    <textarea name="description" id="description"
        maxlength="255" class="white leftnc" cols="64" rows="4"
        <?php print $readonly; ?>><?php print $edescn; ?></textarea>
<?php
                break;
            }	// other event types
        }		// act on specific IDET values
?>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// description supported

    // provide a location input field if supported
    if (!is_null($location))
    {		// location supported
?>
    <div class="row" id="locationRow">
<?php
        if ($location instanceof Temple)
        {		// Temple
            $idtr	= $location->getIdtr();
?>
      <label class="column1" for="temple">
    Temple:
      </label>
    <input type="text" name="temple" id="temple"
            class="white leftnc" size="64" maxlength="255"
            <?php print $readonly; ?> value="<?php print $locName; ?>">
<?php
        }		// Temple
        else
        {		// Location
?>
      <label class="column1" for="location">
    Location:
      </label>
    <input type="text" name="location" id="location"
            class="white leftnc" size="64" maxlength="255"
            <?php print $readonly; ?> value="<?php print $locName; ?>">
<?php
        }		// Location
?>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// location supported

    // provide a location kind pair of radio buttons for some LDS
    // events that can occur either at a temple or in the field
    if (!is_null($kind))
    {		// location kind supported
?>
    <div class="row" id="kindRow">
        Temple:
        <input type="radio" name="kind" id="kind" value="1"
                <?php if ($kind == 1) print 'checked="checked"'; ?>>
        Live:
        <input type="radio" name="kind" id="kind" value="0"
                <?php if ($kind != 1) print 'checked="checked"'; ?>>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// location kind supported

    // temple ready
    if (!is_null($templeReady))
    {		// temple ready submission indicator
        if ($templeReady != 0)
            $checked	= 'checked="checked"';
        else
            $checked	= "";
?>
    <div class="row" id="templeReadyRow">
      <label class="column1" for="templeReady">
        Temple Ready:
      </label>
    <input type="checkbox" name="templeReady" id="templeReady"
            <?php print $checked; ?>
        value="1">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// temple ready submission indicator

    if ($type == Citation::STYPE_NAME)
    {		// permit modifying name of individual
?>
    <div class="row" id="SurnameRow">
      <label class="column1" for="surname">
        Surname:
      </label>
    <input type="text" name="surname" id="surname" size="32"
        maxlength="120" class="white left"
        value="<?php print str_replace('"',"&quot;",$surname); ?>">
      <div style="clear: both;"></div>
    </div>
    <div class="row" id="GivenRow">
      <label class="column1" for="givenName">
        Given Name:
      </label>
    <input type="text" name="givenName" id="givenName" size="50"
        maxlength="120" class="white left"
        value="<?php print str_replace('"',"&quot;",$given);?>">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// permit modifying name of individual
    // provide an input text field for name prefix
    if (!is_null($prefix))
    {		// name prefix supported
        $prefix	= str_replace('"','&quot;',$prefix);
?>
    <div class="row" id="namePrefixRow">
      <label class="column1" for="prefix">
    Name Prefix:
      </label>
    <input type="text" name="prefix" id="prefix" size="16" class="white left"
        maxlength="120"
        <?php print $readonly; ?> value="<?php print $prefix; ?>">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// name prefix supported

    // provide an input text field for title
    if (!is_null($nametitle))
    {		// title supported
        $nametitle	= str_replace('"','&quot;',$nametitle);
?>
    <div class="row" id="nameSuffixRow">
      <label class="column1" for="title">
    Name Suffix:
      </label>
    <input type="text" name="title" id="title"
            size="16" class="white left" maxlength="120"
        <?php print $readonly; ?> value="<?php print $nametitle; ?>">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// title supported

    // cremated
    if (!is_null($cremated))
    {		// cremated or buried indicator
?>
    <div class="row" id="crematedRow">
      <label class="column1" for="cremated">
        Cremated:
      </label>
    <select name="cremated" id="cremated" size="1" class="white left">
      <option value="0"
        <?php if ($cremated != 1) print 'selected="selected"'; ?>>
        Buried
      </option>
      <option value="1"
        <?php if ($cremated == 1) print 'selected="selected"'; ?>>
        Cremated
      </option>
    </select>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// cremated or buried indicator

    // provide an input textarea for extended notes if supported
    if (!is_null($notes))
    {		// notes supported
?>
    <div class="row" id="notesRow">
      <label class="column1" for="note">
        Notes:
      </label>
      <textarea name="note" id="note" 
            cols="64" rows="8"><?php
        print $notes; ?></textarea>
      <div style="clear: both;"></div>
    </div>
<?php
    }		// notes supported


    // the Order field is present in the Event record to
    // define a specific order in which these events are to be presented
    // to the user.  For the moment it is made explicitly available to
    // the user, but should be hidden once more intuitive methods of
    // ordering events are supported.
    if (!is_null($order))
    {		// order supported
?>
    <div class="row" id="orderRow">
      <label class="column1" for="order">
        Order:
      </label>
    <input type="text" size="3" name="order" id="order"
                class="white leftnc"
                value="<?php print $order; ?>">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// order supported

    // the Preferred field is present in the Event record to
    // identify the one instance of a particular event type that is
    // to be reported in situations where only one can be reported
    // for example there is only one Birth date and one Death date that
    // is reported in the heading of an individual page, and only one
    // that can be used for searching for individuals by date
    if (!is_null($preferred))
    {		// preferred supported
        if ($preferred)
            $checked	= 'checked="checked"';
        else
            $checked	= "";
?>
    <div class="row" id="preferredRow">
      <label class="column1" for="preferred">
        Preferred:
      </label>
    <input type="checkbox" size="3" name="preferred" id="preferred"
            class="white leftnc" <?php print $checked; ?> value="1">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// preferred supported

    // The not married indicator is present in the Family record
    // The implementation in Legacy according to the documentation is
    // fuzzy, as there are 2 different citation types even though there
    // is only one fact to cite.  The two citation types are described
    // slightly differently.
    // - One specifies that the individual was never married.  But this
    //   should logically be an indicator in Person with no need
    //   for a Family record
    // - The other citation type describes a relationship where it is
    //   known that the couple never married.  This is the only logical
    //   meaning of an indicator in Family

    if (!is_null($notmar))
    {		// not married indicator
        if ($notmar != 0)
            $checked	= 'checked="checked"';
        else
            $checked	= "";
?>
    <div class="row" id="notMarRow">
      <label class="column1" for="notmarried">
        Partners are not married:
      </label>
    <input type="checkbox" name="notmarried" id="notmarried"
            <?php print $checked; ?> value="1">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// not married indicator

    // no children indicator
    if (!is_null($nokids))
    {		// no children indicator
        if ($nokids != 0)
            $checked	= 'checked="checked"';
        else
            $checked	= "";
?>
    <div class="row" id="noKidsRow">
      <label class="column1" for="nochildren">
        Partners had no children:
      </label>
    <input type="checkbox" name="nochildren" id="nochildren"
            <?php print $checked; ?> value="1">
      <div style="clear: both;"></div>
    </div>
<?php
    }		// no children indicator

    // citations for the event
    if (is_null($event))
    {
        $citParms	= array('idime'			=> $idime,
                            'type'			=> $type);
        $citations	= new CitationSet($citParms);
    }
    else
    {
        $citations	= $event->getCitations();
    }
?>
    <table id="citTable">
			<thead>
		  <tr>
			<th class="left">
            <input type="hidden" name="idime" id="idime"
                    value="<?php print $idime; ?>">
            <input type="hidden" name="citType" id="citType"
                    value="<?php print $type; ?>">
            Citations:
			</th>
			<th class="center">
            Source Name
			</th>
			<th class="center">
            Details (Page)
			</th>
		  </tr>
			</thead>
      <tbody>
<?php
    if ($citations)
    foreach($citations as $idsx => $cit)
    {		// loop through all citations to this fact
        $idsr	= $cit->getIdsr();
        $title	= str_replace('"','&quot;',$cit->getSource()->getTitle());
        $detail	= str_replace('"','&quot;',$cit->getDetail());
?>
		  <tr id="sourceRow<?php print $idsx; ?>" >
			<td id="firstButton<?php print $idsx; ?>">
        <button type="button"
                    id="editCitation<?php print $idsx; ?>">
            Edit Citation
        </button>
			</td>
			<td id="sourceCell<?php print $idsx; ?>">
        <input type="text" name="Source<?php print $idsx; ?>"
                    id="Source<?php print $idsx; ?>"
                    class="ina leftnc"
                    value="<?php print $title; ?>"
                    readonly="readonly"
                    size="40">
        <input type="hidden" name="IDSR<?php print $idsx; ?>"
                    id="IDSR<?php print $idsx; ?>"
                    value="<?php print $idsr; ?>">
			</td>
			<td>
        <input type="text" name="Page<?php print $idsx; ?>"
                    id="Page<?php print $idsx; ?>"
                    class="white leftnc"
                    value="<?php print $detail; ?>"
                    size="37">
			</td>
			<td>
        <button type="button"
                    id="delCitation<?php print $idsx; ?>">
            Delete Citation
        </button>
			</td>
		  </tr>
<?php
    }		// loop through citations
?>
      </tbody>
      <tfoot>
		  <tr>
			<td>
              <button type="button" id="AddCitation">
                <u>A</u>dd Citation
              </button>
            </td>   
		  </tr>
      </tfoot>
    </table>
<?php

    // display a list of existing alternate names and the ability
    // to add and delete them for the name event
    if ($type == Citation::STYPE_NAME)
    {		// Name event, provide access to alternates
        $altNames	= $person->getNames();
        showTrace();
        $in		= 0;
?>
    <fieldset class="other" id="altNameSet">
      <legend class="labelSmall">Alternate Names</legend>
<?php
        foreach($altNames as $idnx => $altName)
        {		// loop through defined alternate names
            if ($altName['order'] > 0)
            {
                $in++;
                $idnx	= $altName['idnx'];
?>
    <div class="row" id="altNamesRow<?php print $idnx; ?>">
      <label class="column1">
        Alternate Name:
      </label>
      <div class="column1" id="altNameText<?php print $idnx; ?>">
        <?php print $altName->getName(); ?>
      </div>
      <button type="button" id="editName<?php print $idnx; ?>">
        Details
      </button>
    &nbsp;
      <button type="button" id="delName<?php print $idnx; ?>">
        Delete Name
      </button>
      <div style="clear: both;"></div>
    </div>
<?php
            }	// actual alternate name
        }		// loop through defined alternate names
        showTrace();
?>
    <div class="row" id="addAltSurnameRow">
      <label class="column1" for="newAltSurname">
        New Surname:
      </label>
    <input type="text" name="newAltSurname" id="newAltSurname"
            size="32" maxlength="120" class="white left">
      <div style="clear: both;"></div>
    </div>
    <div class="row" id="addAltGivenRow">
      <label class="column1" for="newAltGivenName">
        New Given Name:
      </label>
    <input type="text" name="newAltGivenName" id="newAltGivenName"
            size="50" maxlength="120" class="white left">
      <div style="clear: both;"></div>
    </div>
    </fieldset>
<?php
    }		// Name event, provide access to alternates

    // provide an input textarea for cause of death
    if (!is_null($deathCause))
    {		// deathCause supported
        $eDeathCause	= str_replace('"','&quot;',$deathCause);
?>
    <fieldset class="other" id="deathCauseSet">
      <legend class="labelSmall">Cause of Death</legend>
    <div class="row" id="deathCauseRow">
      <label class="column1" for="deathCause">
        Cause of Death:
      </label>
      <input type="text" name="deathCause" id="deathCause"
            class="white leftnc" size="64" maxlength="255"
            value="<?php print $eDeathCause; ?>">
      <div style="clear: both;"></div>
    </div>
<?php
        // citations for the cause of death
        $citParms	= array('idime'			=> $idir,
                        'type'			=> Citation::STYPE_DEATHCAUSE);
        $citations	= new CitationSet($citParms);
?>
      <table id="DcCitTable">
			<thead>
		  <tr>
			<th class="left">
                Citations:
			</th>
			<th class="center">
                Source Name
			</th>
			<th class="center">
                Details (Page)
			</th>
		  </tr>
			</thead>
        <tbody>
<?php
        foreach($citations as $idsx => $cit)
        {		// loop through all citations to this fact
            $title	= str_replace('"','&quot;',$cit->getSource()->getTitle());
            $detail	= str_replace('"','&quot;',$cit->getDetail());
?>
		  <tr id="sourceRow<?php print $idsx; ?>" >
			<td>
        <button type="button"
            id="editCitation<?php print $idsx; ?>">
       Edit Citation
        </button>
			</td>
			<td>
        <input type="text" name="Source<?php print $idsx; ?>" 
            id="Source<?php print $idsx; ?>"
            class="ina leftnc"
            value="<?php print $title; ?>"
            readonly="readonly"
            size="40">
			</td>
			<td>
        <input type="text" name="Page<?php print $idsx; ?>"
            id="Page<?php print $idsx; ?>"
            class="white leftnc"
            value="<?php print $detail; ?>"
            size="37">
			</td>
			<td>
        <button type="button" id="delCitation<?php print $idsx; ?>">
            Delete Citation
        </button>
			</td>
			<td>
		  </tr>
<?php
        }		// loop through citations
?>
		  <tr>
			<td>
        <button type="button"
            id="addCitationDeathCause">
          <u>A</u>dd Citation
        </button>
			</td>
		  </tr>
      </tbody>
    </table>
    </fieldset>
<?php
    }		// deathCause supported
?>
    <p id="buttonRow">
<?php
    if ($submit)
    {
?>
      <button type="submit" id="Submit">
<?php
    }
    else
    {               // use AJAX
?>
      <button type="button" id="updEvent">
    <u>U</u>pdate Event
      </button>
    &nbsp;
      <button type="button" id="close">
    Close
      </button>
<?php
    if (!is_null($notes))
    {		// note area present
?>
    &nbsp;
      <button type="button" id="Clear">
    <u>C</u>lear&nbsp;Notes
      </button>
<?php
    }		// note area present

    if (!is_null($picIdType))
    {		// include button for managing pictures
?>
    &nbsp;
      <button type="button" id="Pictures">
    <u>P</u>ictures
      </button>
      <input type="hidden" name="PicIdType" id="PicIdType" 
            value="<?php print $picIdType; ?>">
<?php
        }		// include button for managing pictures
    }           // use AJAX
?>
    </p>
  </form>
<?php
}		        // no errors
showTrace();
?>
 </div> <!-- id="body" -->
<div id="cittemplates" class="hidden">
    <table>
    <!-- The following is the template for what a new citation looks
    *    like before the user enters the citation description
    -->
		  <tr id="sourceRow$rownum" >
			<th>
			</th>
			<td>
              <select name="Source$rownum" id="Source$rownum"
                    class="white leftcit">
                <option value="$idsr" selected="selected">
                    $sourceName
                </option>
              </select>
			</td>
			<td>
            <input type="text" name="Page$rownum" id="Page$rownum"
                    class="white leftnc" value="$detail" size="37">
            <input type="hidden" name="idime$rownum" id="idime$rownum"
                    value="$idime">
            <input type="hidden" name="type$rownum" id="type$rownum" 
                    value="$type">
			</td>
		  </tr>
    <!-- The following is the template for what a citation looks
    *    like after the user enters the citation description
    *    This should match exactly the layout for existing citations
    *    as formatted by PHP above.
    -->
		  <tr id="sourceRow$idsx" >
			<td>
              <button type="button"
                id="editCitation$idsx">
                Edit Citation
              </button>
			</td>
			<td>
              <input type="text" name="Source$idsx" id="Source$idsx"
                    class="ina leftnc"
                    value="$title"
                    readonly="readonly"
                    size="40">
			</td>
			<td>
              <input type="text" name="Page$idsx" id="Page$idsx"
                    class="white leftnc"
                    value="$page"
                    size="37">
			</td>
			<td>
              <button type="button"
                    id="delCitation$idsx">
                    Delete Citation
              </button>
			</td>
          </tr>
        </tbody>
      </table>
    <div>

      <!-- template for confirming the deletion of a citation-->
      <form name="CitDel$template" id="CitDel$template">
        <p class="message">$msg</p>
        <p>
          <button type="button" id="confirmDelete$idsx">
        OK
          </button>
          <input type="hidden" id="rownum$idsx" name="rownum$idsx"
                value="$rownum">
          <input type="hidden" id="formname$idsx" name="formname$idsx"
                value="$formname">
        &nbsp;
          <button type="button" id="cancelDelete$idsx">
        Cancel
          </button>
        </p>
      </form>
    </div> <!-- end of <div id="cittemplates"> -->
    <div class="balloon" id="Helpdate">
      <p>Enter the date on which the event took place or 
	    the fact was observed.  The recommended format for entering dates is day of
	    the month, month name or supported abbreviation thereof, and year.
	    For example "12 July 1879" or "17 Nov 1906".
	    However the month name, day, year format is also supported.  
	    For example "Feb 23 1764".
	    If you enter the date as three decimal numbers separated by slashes, for
	    example "11/4/1887" the first number is normally interpreted as 
	    the day of the month
	    and the second number as the month number,  except:
      <ul>
        <li>If the first number is greater than 31 the value is interpreted as
	        year, month, day.
        <li>if the second number is greater than 12 and therefore cannot be
	        a month number, and the first number is less than or equal to 12		then the first number is interpreted as the month, and the
	        second as the day of the month.  
      </ul>
      <p>For example "11/4/1887" and "1887/4/11" are interpreted as "11 April 1887",
	    while "11/18/1887" is interpreted as "18 November 1887".  Note that this
	    default interpretation is that used in almost every country on earth
        <b>except</b> the United States of America where the standard interpretation
	    of such dates is mm/dd/yyyy.
      </p>
      <p>The day and the day and the month may be omitted if they are not known.
	    For example the following are valid dates: "Oct 1913", "1927".
	    The following abbreviations for month names are recognized in addition
	    to the full month names:
      </p>
      <ul>
        <li>J, Ja, Jan, Jany
        <li>F, Feb, Feby
        <li>M, Mr, Mar
        <li>A, Ap, Apr, Aprl
        <li>Ma, My, May, Y 
        <li>Jn, Jun
        <li>Jl, Jul
        <li>Au, Aug, Augt, G
        <li>S, Sep, Sept
        <li>O, Oct, Octr 
        <li>N, Nov, Novr
        <li>D, Dec, Decr
      </ul>
      <p>The following prefixes may be applied to the date:
	    In, On, Abt, About, Cir, Circa, Bef, Before, Aft, After, WFT Est, Est, From,
      </p>
      <p>A range of dates can be expressed as:
	    Between &lt;begin date&gt; and &lt;end date&gt;,
	    From &lt;begin date&gt; to &lt;end date&gt; or
        &lt;begin date&gt;-&lt;end date&gt;.
      </p>
    </div>
    <div class="balloon" id="Helpnote">
      <p>This is a multiple line text area in which you can record extended
	    comments about the
<?php
        if (array_key_exists($type, $typeText))
            print $typeText[$type]; ?>.
      </p>
    </div>
    <div class="balloon" id="Helpprefix">
      <p>This field is used to supply a name prefix, such as an honorific or
	    indicator of rank or profession.  Typical examples include:
	    Capt., Col., Dr., Elder, Father, Lord, Lt. Col., Major, Miss, Mr., Mrs.,
	    Rev'd, or Sister.
      </p>
    </div>
    <div class="balloon" id="Helptitle">
      <p>This field is used to supply a name suffix.  Typical examples include:
	     J'r, Jr., Jun., S'r, Sen., Sr., III, IV, K.C., M.D.
      </p>
    </div>
    <div class="balloon" id="Helpsurname">
      <p>This field is used to update the primary surname of an individual.
      </p>
    </div>
    <div class="balloon" id="HelpgivenName">
      <p>This field is used to update the primary given name of an individual.
      </p>
    </div>
    <div class="balloon" id="HelpnewAltSurname">
      <p>This field is used to supply a new alternate surname to handle the case
        where the individual was known by more than one surname. 
        For example when a child is adopted, the child generally
        takes the family name of the adopting parents.
	    If you do not fill in an alternate given name in the following row, 
	    then the primary given name is assumed to be used with the new surname.
      </p>
    </div>
    <div class="balloon" id="HelpnewAltGivenName">
     <p>This field is used to supply a new alternate given name to handle 
        the case where the individual was known by different given names. 
        For example when a child is adopted frequently the adopting parents
        choose to use a different
	    given name than the one given by the biological parents.
	    This field works together with the Alternate Surname field.
	    Filling in both fields and applying the update creates an alternate name
	    that has both a different given name and a different surname.  
      </p>
    </div>
    <div class="balloon" id="Helpetype">
     <p>Select the specific type of event.
      </p>
    </div>
    <div class="balloon" id="Helpdescription">
      <p>Text which more precisely explains the nature of the event and which
	    is not part of the location information.  For example for events which
        contain a description this field contains the details 
        of the description.
      </p>
    </div>
    <div class="balloon" id="Helpoccupation">
      <p>Text which describes the occupation of the individual and 
	    is not part of the location information. 
      </p>
    </div>
    <div class="balloon" id="Helpdesc">
      <p>Notes of a more general nature, including your own comments about the
	    event.
      </p>
    </div>
    <div class="balloon" id="Helplocation">
      <p>Enter the location associated with the event or fact.
      </p>
    </div>
    <div class="balloon" id="Helptemple">
      <p>Select the Latter Day Saints temple at which the sacrament took place.
      </p>
    </div>
    <div class="balloon" id="Helpcremated">
      <p>This selection list is used to specify whether the individual was
	    buried or cremated.
      </p>
    </div>
    <div class="balloon" id="Helporder">
      <p>Temporarily the application supports explicitly setting the order
	    index number for this event within the set of existing events for the
	    individual or family.  However once you have added the event it is
	    recommended that you request the "Order Events by Date" function of the
	    Events dialog to calculate the appropriate value of this field.
      </p>
    </div>
    <div class="balloon" id="HelpSource">
      <p>Read-only field displaying one of the master sources
	    cited for this fact or event.
      </p>
    </div>
    <div class="balloon" id="HelpSourceSel">
      <p>This is a selection list used to identify a source which documents
        this event.  If you need to reference a source which has not 
        previously been referenced in the database you can scroll up
        to the first entry in the selection list which says "Add New Source". 
        A short cut for this is pressing
	    the letter "A" while the focus is in this selection list.
      </p>
    </div>
    <div class="balloon" id="HelpPage">
      <p>Field displaying the page number within the master source
	    that documents this fact or event.
      </p>
    </div>
    <div class="balloon" id="HelpeditCitation">
      <p>Clicking on this button pops up a dialog to edit the details of the
	    citation.
      </p>
    </div>
    <div class="balloon" id="HelpdelCitation">
      <p>Clicking on this button deletes this citation.
      </p>
    </div>
    <div class="balloon" id="HelpAddCitation">
      <p>Clicking on this button adds a row to the citation table that permits
	    selecting the master source and specifying the page within that source
	    that documents the current fact or event.
      </p>
    </div>
    <div class="balloon" id="HelpaddCitation">
      <p>Clicking on this button adds a row to the citation table that permits
	    selecting the master source and specifying the page within that source
	    that documents the current fact or event.
      </p>
    </div>
    <div class="balloon" id="HelpdeathCause">
      <p>
	    The cause of death as provided by the coroner or medical attendant, 
	    usually from the death certificate.
      </p>
    </div>
    <div class="balloon" id="HelpaddCitationDeathCause">
      <p>Clicking on this button adds a row to the citation table
	    for the cause of death that permits
	    selecting the master source and specifying the page within that source
	    that documents the cause of death.
      </p>
    </div>
    <div class="balloon" id="HelpeditName">
      <p>Clicking on this button opens a dialog to modify the details of the
	    associated name record.
      </p>
    </div>
    <div class="balloon" id="HelpdelName">
      <p>Clicking on this button deletes the associated name record.
      </p>
    </div>
    <div class="balloon" id="HelpupdEvent">
      <p>Clicking on this button applies all of the changes made to this fact
	    or event and closes the dialog.  Note that pressing the Enter key while
	    editting any text field in this dialog also performs this function.
      </p>
    </div>
    <div class="balloon" id="HelpSubmit">
      <p>Clicking on this button applies all of the changes made to this fact
	    or event and closes the dialog.  Note that pressing the Enter key while
	    editting any text field in this dialog also performs this function.
      </p>
    </div>
    <div class="balloon" id="Helpraw">
      <p>Clicking on this button changes the way that the notes portion of the
        dialog is displayed between the normal rich text editor
        and a raw textarea.
      </p>
    </div>
    <div class="balloon" id="Helpclose">
      <p>Clicking on this button closes the dialog without applying
        any of the updates.
      </p>
    </div>
    <div class="balloon" id="HelpClear">
      <p>Clicking on this button, or alternatively using the keyboard shortcut
        Alt-C, clears all of the text from the notes field associated 
        with this event.
      </p>
    </div>
    <div class="balloon" id="HelpPictures">
      <p>Clicking on this button, or alternatively using the keyboard shortcut
	    Alt-P, pops up a dialog to manage the set of pictures associated with
	    this event.
      </p>
    </div>
    <div id="loading" class="popup">
	    Loading...
    </div>
<?php
include $document_root . '/templates/LocationDialogsen.html';
include $document_root . '/templates/TempleDialogsen.html';
dialogBot();
?>
