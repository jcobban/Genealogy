/************************************************************************
 *  editIndivid.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editIndivid.php.												*
 *																		*
 *  History:															*
 *		2010/08/11		Change name of page for updating events within	*
 *						the LegacyIndiv record itself to				*
 *						editEventIndiv.php								*
 *		2010/08/11		Consolidate updating events within the			*
 *						LegacyIndiv record to a function				*
 *						editEventIndiv(type)							*
 *		2010/08/11		Add editNotes() function.						*
 *		2010/08/12		if invoked from another web page, apply			*
 *						changes to that web page as a side effect of	*
 *						submitting the update.							*
 *		2010/08/19		report any errors in the parameters for updating*
 *						fields in the invoking web page.				*
 *		2010/08/27		Implement addition of child to existing family	*
 *		2010/08/31		Do not refresh opener							*
 *						Add debugging for IE7							*
 *		2010/09/05		Pass given name and surname to editEventIndiv	*
 *		2010/09/20		When invoked from another window to create an	*
 *						individual and return information about the		*
 *						individual to the invoker, close the edit window*
 *						and do not display the basic web page for the	*
 *						individual.										*
 *		2010/09/28		Database was not updated if invoked as a dialog	*
 *		2010/10/15		Improve initial size of Edit Events dialog		*
 *						Use common dialog editEvent for events in record*
 *		2010/10/29		Set class of <td> elements in added child		*
 *		2010/11/11		addChildToPage moved to editMarriage.js			*
 *		2010/11/27		explicitly pass given name and surname of		*
 *						individual to dialogs in case they have changed	*
 *						since the last time the record was written to	*
 *						the database.									*
 *						Remove obsolete functions						*
 *		2010/11/28		With addition of ability to edit associated		*
 *						Child record, the presence of the				*
 *						function parentsIdmr							*
 *						parameter is no longer enough to require adding	*
 *						a child onto the invoking editMarriage page		*
 *		2010/12/08		improve separation of HTML and JavaScript		*
 *		2010/12/15		enable submission for buttons that update the	*
 *						function base									*
 *		2010/12/16		correct incorrect addition of row to invoking	*
 *						marriage dialog on update existing child		*
 *						invoke changeChild method of invoking marriage	*
 *						function dialog									*
 *		2010/12/20		If the individual has no family connections		*
 *						provide an option to delete the individual from	*
 *						the database.									*
 *		2010/12/26		Add callback methods marriageUpdated and 		*
 *						setIdmrPref to receive							*
 *						notifications from script editMarriages.php		*
 *						This is provided to permit updating IDMRPref	*
 *						when the first marriage is added or the			*
 *						preferred marriage changed.						*
 *		2011/02/21		change parameter list for addChildToPage		*
 *		2011/02/23		use editEvent.php to edit general notes			*
 *		2011/02/26		clean up parameter handling						*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *						support Ctrl-S and Alt-U shortcuts to perform	*
 *						function update									*
 *						Make Merge a <button> and support Alt-M			*
 *						Support Alt-D to delete							*
 *		2011/03/06		Support Alt shortcuts for edit buttons			*
 *						This required combining the buttons with the	*
 *						preceding label text.  For good appearance		*
 *						the code makes all of the buttons in a column	*
 *						the same width.									*
 *		2011/03/25		enable submit on keystrokes within text fields	*
 *						in the form so that Ctrl-S and Alt-U will submit*
 *						the form even if the onchange method has not	*
 *						been called yet									*
 *		2011/05/25		add support for edit pictures					*
 *		2011/05/12		change parameter list for addChildToPage		*
 *		2011/06/21		add setParentsPref callback						*
 *		2011/06/24		pass gender class in changeChild call			*
 *		2011/07/12		do not try to add child twice to family			*
 *		2011/07/24		fix context specific help						*
 *		2011/07/29		explicitly pass updated values of date and		*
 *						location to editEvent.php						*
 *		2011/08/08		apply abbreviation table to event locations		*
 *		2011/08/24		change alt key for marriages to F				*
 *		2011/10/02		edit events dialog was opened in wrong window	*
 *		2011/10/23		popup help if mouse held over a field or button	*
 *		2011/12/27		do not warn for IE 9							*
 *		2012/01/13		change class names								*
 *						add eventFeedback method to encapsulate updates	*
 *						to this form as a result of user actions in the	*
 *						editEvent form.									*
 *						pass current death cause text to edit Event		*
 *		2012/02/26		use id= rather than name= on elements whose		*
 *						value does not need to be passed to action		*
 *						function script									*
 *						add support for all events moved from			*
 *						editEvents.js									*
 *						Edit Other Events button replaced by Add Event,	*
 *						Order Events by Date buttons and moved to		*
 *						immediately after list of events				*
 *		2012/03/05		use updateIndividXml.php to update the database	*
 *						record using AJAX								*
 *						this fixes problems where adding or modifying an*
 *						Event wiped out changes made to fields			*
 *						in the main Personid record because of the		*
 *						refresh to display the Event					*
 *						use template for text substitution				*
 *						move all constantwindow create parms to front	*
 *		2012/03/26		when refreshing ensure that the idir parameters	*
 *						has been set so the refresh does not create a	*
 *						new instance									*
 *		2012/04/21		align child windows with main window			*
 *		2012/05/30		support unknown sex								*
 *		2012/06/03		only add new child onto family after child has	*
 *						been added as an individual into the database	*
 *		2012/08/01		support user modification of events recorded in	*
 *						Event instances on the main dialog				*
 *		2012/08/12		support editing LDS sealed to parents event		*
 *		2012/08/25		invoke locationChanged for location field in	*
 *						individual events								*
 *		2012/09/20		new parameter idcr when invoked to edit child	*
 *		2012/10/01		update displayed Surname and GivenName if		*
 *						altered by editing name "event"					*
 *		2012/10/07		use "constant" names for case values			*
 *						update database record before adding individual	*
 *						event, or any other action which requires the	*
 *						IDIR value										*
 *		2012/10/25		fix bug in use of constant names for case values*
 *						the switch expression must resolve to an integer*
 *		2012/11/08		expand abbreviations in event location			*
 *		2012/11/09		add customizable events using javascript rather	*
 *						than redisplaying the entire page				*
 *		2012/11/12		Person::save only updates the database			*
 *						record if values have changed, so there is no	*
 *						longer any reason to suppress the submit action	*
 *						abbreviations for words in cause of death added	*
 *		2013/03/06		pass all of the fields from the individual record*
 *						to the addChildToPage method of the invoking page*
 *		2013/03/12		support additional fields						*
 *						display gender field with color highlighting	*
 *		2013/03/16		adjust gender based upon lists of the 40 most	*
 *						common given names for men and women			*
 *		2013/04/17		increase height of editEvent window				*
 *		2013/05/29		use actMouseOverHelp common function			*
 *						disable edit families button if invoked for		*
 *						a child											*
 *		2013/06/03		do not fail validating submit because unable	*
 *						to access invoking page to update it			*
 *		2013/06/06		expand list of female names						*
 *		2013/06/11		disable edit families button if invoked for a	*
 *						function spouse									*
 *		2013/06/23		disable edit families button if invoked to		*
 *						add new child									*
 *		2013/06/28		disable edit parents button if invoked for a	*
 *						function child									*
 *		2013/07/04		update displayed event type text for optional	*
 *						events if etype changes							*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/30		resize dialog window if necessary				*
 *		2013/11/24		popup "loading..." for database update actions	*
 *		2014/02/11		ignore <fieldset> in list of form elements		*
 *		2014/02/17		error in eventFeedback for STYPE_EVENT because	*
 *						no longer use a <table> for layout				*
 *		2014/02/22		do not submit form if a modal dialog is shown	*
 *		2014/06/06		invoke changePerson method of row instead of	*
 *						obsolete changeChild method of children table	*
 *		2014/06/16		the args with which the script is invoked		*
 *						have already been placed in a global object		*
 *		2014/06/16		opener row.changePerson feedback method was		*
 *						called twice for a change to a child			*
 *		2013/09/13		parameter names in array args are lower case	*
 *		2013/09/20		gender string was set to unsupported value		*
 *						'other' instead of 'unknown'					*
 *		2014/10/02		prompt for confirmation before deleting an		*
 *						event.											*
 *						update DOM to remove display of event			*
 *		2014/10/27		birth and death dates no longer guaranteed to	*
 *						be in input fields with names BirthDate and		*
 *						function DeathDate								*
 *		2014/11/02		place information from preferred events into	*
 *						reserved fields on form.  This reverses the		*
 *						change described above for 27 October 2014		*
 *		2014/11/08		interpretation of IDET value in eventFeedback	*
 *						failed because the string value was not			*
 *						interpreted as an integer, causing creation		*
 *						of new row for preferred event					*
 *		2014/11/15		widen window for editMarriages.php				*
 *		2014/12/08		function eventChanged supports any input		*
 *						element whose name contains 'Date', 'Descn',	*
 *						'Pref', 'Location', or 'Order'					*
 *						support for rows with a file '....Temple'		*
 *						All event detail buttons now use the same		*
 *						onclick handler									*
 *						All event delete buttons now use the same		*
 *						onclick handler									*
 *						field names within an event row renamed			*
 *						order events by date now sorts the rows			*
 *						in the DOM on the fly and assigns new order		*
 *						which is applied when the individual is saved	*
 *						support for choosing preferred instance of		*
 *						any event type explicitly through checkbox		*
 *		2015/01/03		eventFeedback added new row when it shouldn't	*
 *						support feedback for LDS Sealed to Parents		*
 *						update order field in events after new event	*
 *		2015/01/04		child feedback returned blank birth and death	*
 *						dates because of change in name of row			*
 *		2015/01/10		after event sort the Detail and Delete buttons	*
 *						were not functional								*
 *						only permit sort events button to used once		*
 *		2015/01/12		add support for Ancestry search in split window	*
 *		2015/01/15		get appropriate text for event types from the	*
 *						web page rather than separate table.  This		*
 *						avoids maintaining two separate tables with		*
 *						the same information, and facilitates I18N		*
 *		2015/01/16		the field validation function was not called	*
 *						for most input fields, so errors not flagged	*
 *		2015/01/18		display all child windows as iframes in right	*
 *						half of window, instead of a separate windows	*
 *						all child windows are now displayed exactly the	*
 *						same way, and the browser does not have to		*
 *						allow popup windows.							*
 *		2015/01/27		open merge dialog in right side					*
 *		2015/02/01		support being opened in an <iframe> by the		*
 *						utility function openFrame						*
 *		2015/02/10		support being invoked in <iframe> missed one	*
 *						function spot									*
 *						document input to eventFeedback method			*
 *						use closeFrame									*
 *		2015/02/23		openFrame returns an instance of Window			*
 *		2015/03/06		use Family::getHusbName and ::getWifeName		*
 *						pass IDCR of child in feedback parms			*
 *		2015/03/14		resize all of the half-window iframes on resize	*
 *						warn the user when requested to update the		*
 *						individual but currently editing an event or	*
 *						the family										*
 *						only permit updating one event at a time		*
 *		2015/03/20		if individual record not created yet in DB		*
 *						defer editEvent in frame						*
 *		2015/03/25		check for adding child only after checking for	*
 *						request to update an existing family member		*
 *						pass debug flag to editMarriages.php			*
 *						pass debug flag to editParents.php				*
 *		2015/04/08		pass debug flag to MergeIndivid.php				*
 *		2015/04/27		implementation of delete event for events that	*
 *						have not yet been moved to Event did not		*
 *						reflect the new implementation.  This resulted	*
 *						in an alert for bad row name and the event was	*
 *						not deleted.									*
 *		2015/05/27		use absolute URLs for AJAX						*
 *						support deleting associated address				*
 *		2015/06/08		reenable the Order Events by Date button		*
 *						when an event is added or modified				*
 *		2015/06/27		only invoke editEvent.php with relevant parms	*
 *		2015/08/12		pass tree name to editMarriages and editParents	*
 *						do not capitalize all parts of surname			*
 *		2015/08/23		if the given name, surname, birth date, or		*
 *						death date are changed on						*
 *						an editIndivid.php page with an empty title		*
 *						then the page title is changed on the fly		*
 *		2015/09/08		setting preferred event cleared all prefs		*
 *						notify update if preferred option changed		*
 *		2015/09/11		ensure changed flag set for eventPref flag		*
 *						turned off										*
 *		2016/01/27		pass numeric sex as well as gender class		*
 *						to changePerson method							*
 *		2016/02/06		use traceAlert									*
 *		2016/02/27		add a space between digits and letters in dates	*
 *		2016/04/12		invoke editEvent.php with debug if requested	*
 *		2016/06/26		eventFeedback did not supply parms.datesd to	*
 *						function createFromTemplate						*
 *		2016/08/13		delete citations when deleting events from		*
 *						from main individual record						*
 *						blank out cause of death when deleting death	*
 *		2016/11/08		support abbreviations in event fields			*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/29		correct popup of "Loading..." indicator when	*
 *						deleting an event that requires database action	*
 *						before updating the display						*
 *						simplify addition of new events: if an event	*
 *						is being added, do not pass a row number to		*
 *						editEvent.php.  When editEvent.php feeds the	*
 *						new event back without a row number it is		*
 *						added at the end of the existing events			*
 *		2017/09/01		correct handling of updated Name event			*
 *						insert new event in order by date				*
 *		2017/09/23		suppress meaningless alerts from eventFeedback	*
 *		2017/12/29		ensure that when clicking "Update Person"		*
 *						the oldest child frame is brought to the front	*
 *						so it can be saved.								*
 *						Track all child frames.  In particular while	*
 *						the "Edit Families" frame was tracked, the		*
 *						"Edit Parents" frame was not.					*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/18      ensure event passed to onclick handlers         *
 *		2019/06/01      use JSON in place of XML for AJAX response      *
 *		                ensure individual created before exiting        *
 *		2019/06/29      first parameter of displayDialog removed        *
 *		2019/08/09      pass language parm to subdialogs                *
 *		2019/10/05      split functionality of eventChanged to          *
 *		                separate out date handling                      *
 *		                defer editName until person saved               *
 *		2019/10/23      deferred editName did not work because it       *
 *		                requires that the IDNX value be part of the     *
 *		                id of the editName button.                      *
 *		2019/11/07      editName was opened in the wrong frame name     *
 *		2019/11/15      remove alert triggered during merge             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  constants identifying the types of events and where the details		*
 *  are located within the record identified by IDIME.					*
 *  This table must match the one in the php file Citation.inc			*
 *																		*
 *  Facts where IDIME points to an Person Record tblIR					*
 ************************************************************************/
var STYPE_UNSPECIFIED			= 0;
var STYPE_NAME					= 1;
var STYPE_BIRTH					= 2;
var STYPE_CHRISTEN				= 3;
var STYPE_DEATH					= 4;
var STYPE_BURIED				= 5;
var STYPE_NOTESGENERAL			= 6;
var STYPE_NOTESRESEARCH			= 7;
var STYPE_NOTESMEDICAL			= 8;
var STYPE_DEATHCAUSE			= 9;
var STYPE_LDSB					= 15;	// Baptism
var STYPE_LDSE					= 16;	// Endowment
var STYPE_LDSC					= 26;	// Confirmation
var STYPE_LDSI					= 27;	// Initiatory

/************************************************************************
 *		IDIME points to Alternate Name Record tblNX						*
 ************************************************************************/
var STYPE_ALTNAME				= 10;

/************************************************************************
 *		IDIME points to Child Record tblCR.IDCR							*
 ************************************************************************/
var STYPE_CHILDSTATUS			= 11;	// Child Status
var STYPE_CPRELDAD				= 12;	// Relationship to Father
var STYPE_CPRELMOM				= 13;	// Relationship to Mother
var STYPE_LDSP					= 17;	// Sealed to Parents

/************************************************************************
 *		IDIME points to Marriage Record tblMR.idmr						*
 ************************************************************************/
var STYPE_LDSS					= 18;	// Sealed to Spouse
var STYPE_NEVERMARRIED			= 19;	// This individual never married 
var STYPE_MAR					= 20;	// Marriage	
var STYPE_MARNOTE				= 21;	// Marriage Note
var STYPE_MARNEVER				= 22;	// Never Married	     
var STYPE_MARNOKIDS				= 23;	// This couple had no children
var STYPE_MAREND				= 24;		// Marriage ended **added**

/************************************************************************
 *	IDIME points to Event Record tblER.ider				                *	
 ************************************************************************/
var STYPE_EVENT					= 30;	// Person Event
var STYPE_MAREVENT				= 31;	// Marriage Event

/************************************************************************
 *  IDIME points to To-Do records tblTD.IDTD							*
 ************************************************************************/
var STYPE_TODO				    = 40;	// To-Do

/************************************************************************
 *		Event::ET_XXXXX													*
 *																		*
 *		The standard values of the event type field						*
 ************************************************************************/

var ET_NULL					    =  1;
var ET_ADOPTION					=  2;
var ET_BIRTH					=  3;
var ET_BURIAL					=  4;
var ET_CHRISTENING				=  5;
var ET_DEATH					=  6;
var ET_ANNULMENT				=  7;
var ET_LDS_BAPTISM				=  8;	// LDS
var ET_BARMITZVAH				=  9;
var ET_BASMITZVAH				= 10;
var ET_BLESSING					= 11;
var ET_CENSUS					= 12;
var ET_CIRCUMCISION				= 13;
var ET_CITIZENSHIP				= 14;
var ET_CONFIRMATION				= 15;
var ET_LDS_CONFIRMATION			= 16;	// LDS
var ET_COURT					= 17;
var ET_CREMATION				= 18;
var ET_DEGREE					= 19;
var ET_DIVORCE					= 20;
var ET_DIVORCE_FILING			= 21;
var ET_EDUCATION				= 22;
var ET_EMIGRATION				= 23;
var ET_EMPLOYMENT				= 24;
var ET_ENGAGEMENT				= 25;
var ET_FIRST_COMMUNION			= 26;
var ET_GRADUATION				= 27;
var ET_HOBBIES					= 28;
var ET_HONOURS					= 29;
var ET_HOSPITAL					= 30;
var ET_ILLNESS					= 31;
var ET_IMMIGRATION				= 32;
var ET_INTERVIEW				= 33;
var ET_LAND						= 34;
var ET_MARRIAGE_BANNS			= 35;
var ET_MARRIAGE_CONTRACT		= 36;
var ET_MARRIAGE_LICENSE			= 37;
var ET_MARRIAGE_NOTICE			= 38;
var ET_MARRIAGE_SETTLEMENT		= 39;
var ET_MEDICAL					= 40;
var ET_MEMBERSHIP				= 41;
var ET_MILITARY_SERVICE			= 42;
var ET_MISSION					= 43;
var ET_NAMESAKE					= 44;
var ET_NATURALIZATION			= 45;
var ET_OBITUARY					= 46;
var ET_OCCUPATION				= 47;
var ET_ORDINANCE				= 48;
var ET_ORDINATION				= 49;
var ET_PHYSICAL_DESCRIPTION		= 50;
var ET_PROBATE					= 51;
var ET_PROPERTY					= 52;
var ET_RELIGION					= 53;
var ET_RESIDENCE				= 54;
var ET_RETIREMENT				= 55;
var ET_SCHOOL					= 56;
var ET_SOCIAL_SECURITY_NUMBER	= 57;
var ET_WILL					    = 58;
var ET_MEDICAL_CONDITION		= 59;
var ET_MILITARY					= 60;
var ET_PHOTO					= 61;
var ET_SOC_SEC_NUM				= 62;
var ET_OCCUPATION_1				= 63;
var ET_NATIONALITY				= 64;
var ET_FAMILY_GROUP				= 65;
var ET_ETHNICITY				= 66;
var ET_FUNERAL					= 67;
var ET_ELECTION					= 68;
var ET_MARRIAGE					= 69;
var ET_MARRIAGE_FACT			= 70;	// installation defined
var ET_BIRTH_REGISTRATION		= 71;	// added
var ET_DEATH_REGISTRATION		= 72;	// added
var ET_MARRIAGE_REGISTRATION	= 73;	// added
var ET_LDS_ENDOWED				= 74;	// added
var ET_LDS_INITIATORY			= 75;	// added
var ET_LDS_SEALED				= 76;	// added
var ET_MARRIAGE_END				= 77;	// added

var locTrace			        = "";

/************************************************************************
 *																		*
 * Microsoft Internet Explorer is a piece of $hit prior to IE9			*
 *																		*
 ************************************************************************/
var	ie		                    = false;
var	ieversion               	= null;

// update Title is true if the script is invoked for a brand new individual
var	updateTitle	= false;
// titlePrefix is whatever text is present in the H1 header prior to the
// last three characters.  If updateTitle is true then this is the
// locale specific introductory text to the page title
var	titlePrefix	= "Edit ";

/************************************************************************
 *  parmIdir	        												*
 *																		*
 *  contains the value of idir as requested by the invoking				*
 *  parameters. This is null if the idir parameter is not passed to		*
 *  the PHP script, which means a new individual was created.			*
 *																		*
 ************************************************************************/
var	parmIdir			= null;
var	parentsIdmr			= null;
var	testSubmit			= false;
var	newSearch			= location.search;

/************************************************************************
 *  feedbackRow														    *
 *																		*
 *  DOM element in invoking page to which to feed back results of		*
 *  creation of a new individual.  The method changePerson of this		*
 *  object is called.													*
 ************************************************************************/
var	feedbackRow	= null;

/************************************************************************
 *  idetArray													        *
 *																		*
 *  This array contains the IDET, event type, value for each displayed	*
 *  event, indexed by the row number.  It is used to validate the		*
 *  setting of preferred event of a type.								*
 ************************************************************************/
var	idetArray	= [];

/************************************************************************
 *  windowList													        *
 *																		*
 *  This array contains a list of child windows currently displayed		*
 *  in the right hand side of the main window.							*
 ************************************************************************/
var	windowList	= [];

/************************************************************************
 *  dialogDiv													        *
 *																		*
 *  Global variable to hold a reference to a displayed dialog			*
 ************************************************************************/
var	dialogDiv	= null;

/************************************************************************
 *  datePatt													        *
 *																		*
 *  pattern for common dates											*
 ************************************************************************/
var	datePatt	= /\d{4}/;

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
addEventHandler(window, "load", loadEdit);


/************************************************************************
 *  function loadEdit													*
 *																		*
 *  Initialize elements when the page is loaded.						*
 ************************************************************************/
function loadEdit()
{
    // emit debugging information
    if (false && debug.toLowerCase() == 'y')
        traceAlert("editIndivid.js:loadEdit: " +
		    		location.pathname + location.search);

    // if this is invoked for a completely new individual the
    // heading is meaningless, set up to update it on the fly
    var	titleElement	= document.getElementById('title');
    var	title		    = titleElement.innerHTML.trim();
    // if there is only one space prior to the date range then there is
    // no name information and a date range containing only an m-dash
    // is an empty date range
    updateTitle		= title.substring(title.length - 3) == "(\u2014)" &&
						  (title.match(/ /g)||[]).length <= 1;
    // if updateTitle is true then titlePrefix is whatever text is present
    // preceding the empty date range
    titlePrefix		= title.substring(0, title.length - 3);

    document.body.onresize	= onWindowResize;

    var	idir		            = 0;
	var marriagesButton		    = document.getElementById('Marriages');
	var parentsButton		    = document.getElementById('Parents');

    // loop through arguments passed to script
    for(var key in args)
    {		// loop through all arguments
		var value	= args[key];
		switch(key.toLowerCase())
		{
		    case 'id':
		    case 'idir':
		    {	// request to edit an existing individual
				// note that this requires that elements with names
				// id or idir must precede the element with
				// name parentsIdmr within the invoking request or page
				parmIdir	= parseInt(value);
				break;
		    }	// request to edit an existing individual

		    case 'idcr':
		    {	// request to edit an existing child
                if (marriagesButton)
				    marriagesButton.disabled	= true;
                if (parentsButton)
				    parentsButton.disabled	= true;
				break;
		    }	// request to edit an existing child

		    case 'rowid':
		    {	// request to edit a spouse
                if (marriagesButton)
				    marriagesButton.disabled	= true;
				break;
		    }	// request to edit a spouse

		    case 'parentsidmr':
		    {	// request to add a new child
				parentsIdmr	= parseInt(value);
                if (marriagesButton)
				    marriagesButton.disabled	= true;
                if (parentsButton)
				    parentsButton.disabled	= true;
				break;
		    }	// request to add a new child

		    case 'testsubmit':
		    case 'debug':
		    {		// debug by submitting request rather than using AJAX
				if (value.toUpperCase() == 'Y')
				{
				    testSubmit	= true;
				    alert('editIndivid.js: loadEdit: ' +
						  'testSubmit set to true for ' + key + '=' + value);
				}
				break;
		    }		// debug by submiting
		}		// action depends upon parameter name
    }			// loop through parameters

    document.body.onkeydown		= eiKeyDown;

    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];

		// set action methods for form as a whole
		if (form.name == 'indForm')
		{		// main form
		    if (!testSubmit)
            {
				form.onsubmit	 	= validateForm;
            }
		    else
				alert('editIndivid.js: loadEdit: 630 testSubmit is true');
		    form.onreset 		    = resetForm;
		    form.setIdar		    = setIdar;

		    // callback from editMarriages.php
		    form.marriageUpdated	= marriageUpdated;
		    form.setIdmrPref		= setIdmrPref;
		    form.setParentsPref		= setParentsPref;

		    // callback from editEvent.php
		    form.eventFeedback		= eventFeedback;
		}		// main form

		// activate the dynamic functionality of form elements
		activateElements(form);
    }			// loop through all forms

    // set up refresh action
    if (parmIdir === null)
    {
		parmIdir	= 0;
		newSearch	= "?idir=" + idir +
						      "&" + location.search.substring(1);
    }

    if (window.navigator)
    {		// navigator object defined
		if (/MSIE (\d+\.\d+);/.test(window.navigator.userAgent))
		{ //test for MSIE x.x;
		    ie	= true;
		    ieversion=new Number(RegExp.$1) // capture x.x portion
		    if (ieversion < 9)
				alert ("editIndivid.js: loadEdit: Running under IE version " +
						ieversion);
		}
    }		// navigator object defined
    else
		alert ("editIndivid.js: loadEdit: navigator object not defined");

}		// function loadEdit

/************************************************************************
 *  function onWindowResize												*
 *																		*
 *  This method is called when the browser window size is changed		*
 *  If the window is split between the main display and a second		*
 *  frame, resize all of the half-window iframes.						*
 *																		*
 *  Input:																*
 *		this		Window object										*
 *		ev          resize Event                                        *
 ************************************************************************/
function onWindowResize(ev)
{
    var	body		= document.body;
    var	iframes		= body.getElementsByTagName('iframe');
    for(var fi = 0; fi < iframes.length; fi++)
    {			// loop through all iframes
		var iframe	= iframes[fi];
		if (iframe.src.substring(iframe.src.length - 10) == 'blank.html')
		    continue;
		if (iframe.className == "right")
		    openFrame(iframe.name, null, "right");
		else
		if (iframe.className == "left")
		    openFrame(iframe.name, null, "left");
    }			// loop through all iframes
}		// function onWindowResize

/************************************************************************
 *  function activateElements											*
 *																		*
 *  Activate handling of key strokes in text input fields				*
 *  and handle mouse events for buttons and input fields				*
 *  including support for context specific popup help					*
 ************************************************************************/
function activateElements(form)
{
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var element	= formElts[i];
		if (element.nodeName.toLowerCase() == 'fieldset')
		    continue;
		if ((element.type) && (element.type == 'text'))
		{
		    element.onkeydown	= fldKeyDown;
		    element.oldkeydown	= keyDown;	// context specific help
		}
		else
		    element.onkeydown	= keyDown;	// context specific help
		element.addEventListener('change', change);	// default handler

		// set behavior for individual elements by name
		var	name	= element.name;
		if (name.length == 0)
		    name	= element.id;

		var namePattern	= /^([a-zA-Z$_\[\]]+)([0-9]*)$/;
		var pieces		= namePattern.exec(name);
		if (pieces === null)
		{
		    popupAlert("editIndivid.js: activateElements: " +
						"unable to parse name='" + name + "'",
						element);
		    pieces	= [name, name, ''];
		}
		var colName	= pieces[1];
		var id		= pieces[2];

		switch(colName.toLowerCase())
		{		// switch on name of element
		    case 'idir':
		    {		// unique key of record
				idir		= element.value;
				break;
		    }		// unique key of record

		    case 'detail':
		    {		// buttons to edit event details
				if (id == '6' || id == '7')
				{		// buttons to edit textual notes
				    element.addEventListener('click', editEventIndiv);
				}		// buttons to edit textual notes
				else
				if (id == '8')
				{		// buttons to edit textual notes
				    element.addEventListener('click', editEventIndiv);
				}		// buttons to edit textual notes
				else
				if (id == '17')
				    element.addEventListener('click', editEventChildr)
				else
				    element.addEventListener('click', editEventIndiv);
				break;
		    }		// buttons to edit event details

            case 'editname':
            {
				element.addEventListener('click', editName);
                break;
            }       // edit primary name

		    case 'clear':
		    {		// buttons to clear event details
				if (id == '17')
				    element.addEventListener('click', clearEventChildr);
				else
				    element.addEventListener('click', clearEventIndiv);
				break;
		    }		// buttons to clear event details

		    case 'birthdate':
		    case 'christeningdate':
		    case 'deathdate':
		    case 'burieddate':
		    case 'sealingdate':
		    case 'baptismdate':
		    case 'endowmentdate':
		    case 'confirmationdate':
		    case 'initiatorydate':
		    case 'eventdate':
		    {			// edit major event dates
				element.abbrTbl		= MonthAbbrs;
				element.addEventListener('change', dateChanged);
				element.addEventListener('change', eventChanged);
				element.checkfunc	= checkDate;
				break;
		    }			// edit major event dates

		    case 'birthlocation':
		    case 'christeninglocation':
		    case 'baptismlocation':
		    case 'confirmationlocation':
		    case 'deathlocation':
		    case 'buriedlocation':
		    case 'eventlocation':
		    {			// edit major event locations
				element.abbrTbl		= evtLocAbbrs;
				element.addEventListener('change', locationChanged);
				element.addEventListener('change', eventChanged);
				break;
		    }			// edit major event locations

		    case 'surname':
		    {			// Surname of individual
				element.focus();	// put cursor in surname field
				element.abbrTbl		= surnamePartAbbrs;
				element.addEventListener('change', surnameChanged);
				element.checkfunc	= checkName;
				break;
		    }			// Surname of individual

		    case 'givenname':
		    {			// name fields
				element.checkfunc	= checkName;
				element.addEventListener('change', givenChanged);
				break;
		    }			// name fields

		    case 'deathcause':
		    {			// cause of death
				element.abbrTbl		= CauseAbbrs;
				element.checkfunc	= checkText;
				break;
		    }			// cause of death

		    case 'parents':
		    {			// button to add or edit parents
				element.addEventListener('click', editParents);
				break;
		    }			// button to add or edit parents

		    case 'marriages':
		    {			// button to add or edit families
				element.addEventListener('click', editMarriages);
				break;
		    }			// button to add or edit families

		    case 'addevent':
		    {			// button to add instance of Event
				element.addEventListener('click', eventAdd);
				break;
		    }			// button to add instance of Event

		    case 'order':
		    {			// button to order events by date
				element.addEventListener('click', orderEventsByDate);
				break;
		    }			// button to order events by date

		    case 'showmore':
		    {			// button to display more input fields
				element.addEventListener('click', showMore);
				break;
		    }			// button to display more input fields

		    case 'pictures':
		    {			// button to add or edit pictures
				element.addEventListener('click', editPictures);
				break;
		    }			// button to add or edit pictures

		    case 'address':
		    {			// button to add or edit address
				element.addEventListener('click', editAddress);
				break;
		    }			// button to add or edit address

		    case 'delete':
		    {			// button to delete the individual
				element.addEventListener('click', delIndivid);
				break;
		    }			// button to delete the individual

		    case 'merge':
		    {			// button to merge with another individual
				element.addEventListener('click', mergeIndivid);
				break;
		    }			// button to merge with another individual

		    case 'search':
		    {			// popdown search menu
				element.addEventListener('click', popdownSearch);
				break;
		    }			// popdown search menu

		    case 'censussearch':
		    {			// perform census table search
				element.addEventListener('click', censusSearch);
				break;
		    }			// perform census table search

		    case 'bmdsearch':
		    {			// perform vital statistics search
				element.addEventListener('click', bmdSearch);
				break;
		    }			// perform vital statistics search

		    case 'ancestrysearch':
		    {			// perform Ancestry.com search
				element.addEventListener('click', ancestrySearch);
				break;
		    }			// perform Ancestry.com search

		    case 'gender':
		    {		// Gender of individual
				element.addEventListener('change', genderChanged);
				break;
		    }		// Gender of individual

		    case 'eventdescn':
		    {		// description for generic event
				element.addEventListener('change', eventChanged);
				element.checkfunc	= checkText;
				break;
		    }		// description for generic event

		    case 'eventpref':
		    {		// preferred checkbox
				element.addEventListener('change', eventPrefChanged);
				break;
		    }		// preferred checkbox

		    case 'eventidet':
		    {		// preferred checkbox
				idetArray[id - 0]	= element.value - 0;
				break;
		    }		// preferred checkbox

		    case 'eventdetail':
		    {		// button to popup event detail edit dialog
				element.addEventListener('click', eventDetail);
				break;
		    }		// button to popup event detail edit dialog

		    case 'eventdelete':
		    {		// button to delete an event
				element.addEventListener('click', eventDelete);
				break;
		    }		// button to delete an event

		    case 'grant':
		    {		// button to grant access to this individual
				element.addEventListener('click', grantAccess);
				break;
		    }		// button to grant access to this individual
		}		    // switch on name of element
    }		        // loop through all elements in the form
}		// function activateElements

/************************************************************************
 *  function validateForm												*
 *																		*
 *  This method is called when the user submits the form.				*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.  Update fields in the form	*
 *  of the invoking web page if requested by parameters.				*
 *																		*
 *	Input:																*
 *		this        <form>												*
 ************************************************************************/
function validateForm()
{
    var form                = this;
    console.log("editIndivid.js: validateForm: called");

    // do not submit the form if a modal dialog is being displayed
    if (deferSubmit)
    {
		deferSubmit	        = false;
		return false;   // do not submit
    }

    // handle feedback if the editIndivid.php page was invoked from
    // another window
    var	opener		        = null;
    if (window.frameElement && window.frameElement.opener)
		opener		        = window.frameElement.opener;
    else
		opener		        = window.opener;

    if (opener != null)
    {			        // invoked from another window
		// dstform is the form in the invoking page
		var	dstform		    = null;
		try
		{	            // do not fail script for permission failure 
		    dstform		    = opener.document.forms[0];
		} catch(e) {
		}	            // do not fail script for permission failure 

		if (dstform)
		{		        // opener contains a form
		    // if parmIdir is zero then the script has added a new individual
		    // into the database.  
		    var	adding		= parmIdir == 0;

		    // process the parameters passed to the PHP script
		    for(var key in args)
		    {		    // loop through parameter name value pairs
				var	val	= args[key];
				switch(key.toLowerCase())
				{	    // action depends upon parameter name
				    case 'id':
				    case 'idir':
				    {
						break;
				    }

				    case 'idcr':
				    {	// request to edit a child in a family
						var	childTable	=
							opener.document.getElementById('children');
						if (!childTable)
						    break;
						var genderSel	= form.Gender;
						var index	    = genderSel.selectedIndex;
						var gender;	// gender presentation class
						gender	        = genderSel.options[index].value;
						if (gender == 0)
						    gender	    = 'male';
						else
						if (gender == 1)
						    gender	    = 'female';
						else
						    gender	    = 'unknown';

						var	birthDate	= '';
						var	deathDate	= '';

						var birthRow	= document.getElementById('BirthRow');
						if (birthRow)
						{			// birth row present
						    for(var celt = birthRow.firstChild;
							celt;
							celt = celt.nextSibling)
						    {
							if (celt.tagName && celt.tagName == 'INPUT')
							{		// <input ...
							    if (celt.name == 'BirthDate' ||
								celt.name.substring(0,9) == 'EventDate')
							    {
								birthDate	= celt.value;
								break;
							    }
							}		// <input ...
						    }
						}			// birth row present

						var deathRow	= document.getElementById('DeathRow');
						if (deathRow)
						{			// death row present
						    for(var celt = deathRow.firstChild;
							celt;
							celt = celt.nextSibling)
						    {
							if (celt.tagName && celt.tagName == 'INPUT')
							{		// <input ...
							    if (celt.name == 'DeathDate' ||
								celt.name.substring(0,9) == 'EventDate')
							    {
								deathDate	= celt.value;
								break;
							    }
							}		// <input ...
						    }
						}			// death row present

						var parms	= {
							'idir'		: idir,
							'idcr'		: val,
							'givenname'	: form.GivenName.value,
							'surname'	: form.Surname.value,
							'birthd'	: birthDate,
							'deathd'	: deathDate,
							'gender'	: gender
								};
						break;
				    }	// request to edit a child of a family

				    case 'parentsidmr':
				    {	// request to add a child in a family
						break;
				    }	// request to add a child of a family

				    case 'rowid':
				    {	// update a member of the family
						// invoke the changePerson method of the specific
						// element in the invoking page
						feedbackRow	= opener.document.getElementById(val);
						break;
				    }	// update a spouse in invoking family

				    case 'initsurname':
				    case 'fathsurname':
				    case 'fathgivenName':
				    case 'mothsurname':
				    case 'mothgivenName':
				    case 'enablesubmit':
				    case 'treename':
				    case 'debug':
				    case 'lang':
				    {	// already handled by PHP
						break;
				    }	// already handled by PHP

				    default:
				    {	// unexpected
						if (key.substring(0,4) != 'init')
						    alert("editIndivid.js: 1102: validateForm: " +
							  "unexpected parameter " + key + 
							  "='" + val + "'");
						break;
				    }	// unexpected
				}	    // action depends upon parameter name
		    }		    // loop through parameter name value pairs

		}		        // opener contains a form
		else
		{		        // opener does not contain a form
		    // opener is also set when the page is invoked by a hyper-link
		    // from another page, so this is not an error
		    //alert("validateForm: opener.location='" + opener.location + 
		    //	  "' does not contain a form");
		}		        // opener does not contain a form
    }			        // invoked from another window

    console.log("editIndivid.js: validateForm: call update");
    update();		    // use AJAX to update the database record

    return false;	    // do not submit
}		// function validateForm

/************************************************************************
 *  function refresh													*
 *																		*
 *  This method is called to refresh the edit form but only after		*
 *  pending updates to the main record are applied.						*
 ************************************************************************/
function refresh()
{
    var form	    = document.indForm;

    // parms contain every input element with its value
    var	parms	    = {};
    var	msg	        = "[";
    var comma       = '';
    for (var ei = 0; ei < form.elements.length; ei++)
    {
		var	element	= form.elements[ei]
		if (element.name)
		{
		    var	name	        = element.name;
		    msg		            += comma + name + "='" + element.value;
            comma               = "',";
		    if (name.substring(name.length - 2) == '[]')
		    {		// convention for passing an array
				name	        = name.substring(0,name.length - 2);
				if (element.type != 'checkbox' || element.checked)
				    parms[name]	= element.value;
		    }		// convention for passing an array
		    else
				parms[name]	    = element.value;
		}	// element has a name
    }
    msg		    = msg + "]";
    locTrace    += " editIndivid.js: refresh: " + msg;

    // invoke script to update Event and return XML result
    HTTP.post('/FamilyTree/updatePersonJson.php',
		      parms,
		      gotRefreshed,
		      noUpdated);
}	// function refresh

/************************************************************************
 *  function gotRefreshed												*
 *																		*
 *  The JSON document representing the results of the request to 		*
 *  update the Person has been received.								*
 *																		*
 *	Input:  															*
 *	    jsonObj         Javascript object                               *
 ************************************************************************/
function gotRefreshed(jsonObj)
{
    if (typeof(jsonObj) == "object")
    {
		if (typeof(newSearch) == "string")
		{		// location to go to
		    location.search	= newSearch;
		}		// location to go to
		else
		if (typeof(newSearch) == "object")
		{		// have a pending button click to issue
		    var indiv		        = jsonObj.person;
		    var idir		        = indiv.idir;

		    if ((idir - 0) == 0)
				alert("editIndivid.js: 1164: gotRefreshed: idir=" + idir);
		    var id		            = indiv.id;

		    var form		        = document.indForm;
		    form.idir.value	        = idir; // is now set
		    form.id.value	        = id;	// is now set

            var names               = indiv.names;
            if (names)
            {
                for (var prop in names)
                {
                    var name        = names[prop];
                    var nameButton  = document.getElementById('editName0');
                    if (nameButton)
                        nameButton.id = 'editName' + name.idnx;
                    break;
                }
            }
            else
                alert("editIndivid.js: gotRefreshed: 1229: names is null");
            if (newSearch.type == 'submit')
                form.submit();
            else
		        newSearch.click();	    // simulate press the button
		    newSearch		        = location.search;
		}		// have a pending button click to issue
		else
		{		// unexpected object
		    alert("editIndivid.js: gotRefreshed: 1238: typeof(newSearch)=" + 
						typeof(newSearch));
		}		// unexpected object

    }			// valid response
    else
    {			// error response
		alert("editIndivid.js: gotRefreshed: 1245: " + jsonObj);
    }			// error response

}		// function gotRefreshed

/************************************************************************
 *  function update														*
 *																		*
 *  This method is called to update the main record and segué to the	*
 *  main display form.													*
 *  Called from validateForm.                                           *
 ************************************************************************/
function update()
{
    console.log("editIndivid.js: update: 1259 update called windowList.length=" + windowList.length);
    // check for open frames
    if (windowList.length > 0)
    {			// there are incomplete actions pending
		var	text	= '';
		var	comma	= '';
		var	button	= document.getElementById('Submit');
		for (var iw = 0; iw < windowList.length; iw++)
		{		// loop through open iframes
		    var	iwindow		= windowList[iw];
		    var idocument	= iwindow.document;
		    if (idocument && iwindow.frameElement)
		    {		// window has a document and a frame
				if (idocument.title.length > 0)
				{
				    text	+= comma + idocument.title;
				    comma	= ', ';
				}
				else
				{
				    text	+= comma + idocument.documentURI;
				    comma	= ', ';
				}

				if (iw == 0)
				{		// pop the first open frame to the top
				    var zIndex	= iwindow.frameElement.style.zIndex + 5;
				    iwindow.frameElement.style.zIndex	= zIndex;
				    iwindow.focus();
				}		// pop the first open frame to the top
		    }		// window has a document
		}		// loop through open iframes

		if (text.length > 2)
		{
		    popupAlert("Please complete the following windows: " + text,
				       button);
		    return;
		}
    }			// there are incomplete actions pending

    var form	            = document.indForm;

    // parms contain every input element with its value
    var	parms	            = {};
    var	msg	                = "parms=(";
    for (var ei = 0; ei < form.elements.length; ei++)
    {			// loop through all form elements
		var	element	        = form.elements[ei]
		if (element.name)
		{		// element has a name
		    var	name	    = element.name;
		    msg		        += name + "='" + element.value + "',";
		    if (name.substring(name.length - 2) == '[]')
		    {		// convention for passing an array
				name	    = name.substring(0, name.length - 2);
				if (element.type != 'checkbox' || element.checked)
				    parms[name]	= element.value;
		    }		// convention for passing an array
		    else
				parms[name]	= element.value;
		}		// element has a name
    }			// loop through all form elements
    msg		                = msg.substring(0,msg.length - 2) + "}";
    locTrace                += " editIndivid.js: 1292 update: " + msg;
    console.log("editIndivid.js:update 1326 locTrace=" + locTrace);

    // invoke script to update Event and return JSON result
    HTTP.post('/FamilyTree/updatePersonJson.php',
		      parms,
		      gotUpdated,
		      noUpdated);
}	// function update

/************************************************************************
 *  function gotUpdated													*
 *																		*
 *  The JSON document representing the results of the request to update	*
 *  the record has been received.										*
 *																		*
 *	Input:  															*
 *	    jsonObj         Javascript object                               *
 ************************************************************************/
function gotUpdated(jsonObj)
{
    var	opener		    = null;
    if (window.frameElement && window.frameElement.opener)
		opener		    = window.frameElement.opener;
    else
		opener		    = window.opener;
    if (typeof(jsonObj) == "object")
    {			// valid response
		var	srcform		= document.indForm;
		var	indiv		= jsonObj.person;
		// var	msg	= "";
		// for(key in indiv)
		//     msg		+= key + "='" + indiv[key] + "',";
		// alert("editIndivid.js: gotUpdated: indiv={" + msg + "} ");

		// get the IDIR value from the response
		var	idir		= indiv.idir;
		if (typeof(idir) === "undefined")
		{
		    console.log("editIndivid.js: gotUpdated: 1362 idir=" + idir + 
				  " jsonObj=" + JSON.stringify(jsonObj));
		}
        else
		    console.log("editIndivid.js: gotUpdated: 1366 jsonObj=" + JSON.stringify(jsonObj));
		if ((idir - 0) == 0)
		    console.log("editIndivid.js: gotUpdated: 1368: idir=" + idir + ": " +
						JSON.stringify(jsonObj));
		// update IDIR value in form
		srcform.idir.value	= idir;

		// if there is a child tag present, get the IDCR value 
		var	idcr		    = 0;
		var	childr		    = jsonObj.child;
		if (childr)
		    idcr		    = childr.idcr;

		// if invoker has requested to be notified of key information about
		// the individual to update a specific individual in the invoking page
		if (feedbackRow !== null &&
		    typeof(feedbackRow.changePerson) == 'function')
		{		// updating a family member
		    var	gender		= srcform.Gender.value;
		    if (gender == 0)
				genderClass	= 'male';
		    else
		    if (gender == 1)
				genderClass	= 'female';
		    else
				genderClass	= 'unknown';

		    var	birthDate	        = '';
		    var	deathDate	        = '';

		    var eventBody	        = document.getElementById('EventBody');
		    var	eventRows	        = eventBody.children;
		    for(var ir = 0; ir < eventRows.length; ir++)
		    {			// loop through all events
				var eventRow	    = eventRows[ir];
                var children        = eventRow.getElementsByTagName('input');
				for(var ic = 0;
				    ic < children.length;
				    ic++)
				{
                    celt            = children[ic];
					if (celt.name == 'BirthDate')
					{
					    birthDate	= celt.value;
					    break;
					}
					else
					if (celt.name == 'DeathDate')
					{
					    deathDate	= celt.value;
					    break;
					}
				}
		    }			// death row present

		    // pass the information back to the invoker
		    var	parms		= { "idir"		: idir,
								"givenname"	: srcform.GivenName.value,
								"surname"	: srcform.Surname.value,
								"birthd"	: birthDate,
								"deathd"	: deathDate,
								"gender"	: genderClass,
							    "sex"		: gender};
		    if (parentsIdmr !== null)
				parms.idcr	= idcr;
		
		    feedbackRow.changePerson(parms);
		}		// updating a family member
		else
		if (parmIdir == 0 && parentsIdmr !== null && idcr == 0)
		{		// adding new child
		    // Now that the individual is updated in the database
		    // if adding a child, notify invoker to add the child to the list
		    // of children
		    var	childTable	= opener.document.getElementById('children');
		    var genderSel	= srcform.Gender;
		    var index		= genderSel.selectedIndex;
		    var gender		= genderSel.options[index].value;
		    if (gender == 0)
				indiv.gender	= 'male';
		    else
		    if (gender == 1)
				indiv.gender	= 'female';
		    else
				indiv.gender	= 'unknown';

		    // invoke the add method of the 'children' table
		    // now that the individual has been added into the database
		    if ((idir - 0) == 0)
				console.log("editIndivid.js: gotUpdated: 1425: call addChildToPage: idir=" +
						idir );
            if (childTable)
		        childTable.addChildToPage(indiv,
  				                          false);
		}		// adding new child

		// hide the frame containing this dialog
		if (window.frameElement)
		    closeFrame();
		else
		{
		    location	= "Person.php?idir=" + idir;
		}
    }			// valid response
    else
    {           // invalid response
		if (jsonObj && typeof(jsonObj) == "object")
		    alert("editIndivid.js: gotUpdated: 1473: " + JSON.stringify(jsonObj));
		else
		    alert("editIndivid.js: gotUpdated: 1475: '" + jsonObj + "'");
    }           // invalid response
}		// function gotUpdated

/************************************************************************
 *  function noUpdated													*
 *																		*
 *  The server was unable to find the action script updateIndivid.xml.	*
 *																		*
 *	Input:																*
 *      status          response code from server                       *
 *      statusText      status text                                     *
 ************************************************************************/
function noUpdated(rstatus, statusText)
{
    alert("editIndivid.js: 1460: noUpdated: script 'updatePersonJson.php' not found on server, status=" + rstatus + ", text=" + statusText);
}		// function noUpdated

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function editName   												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about the primary name of the current individual.		*
 *																		*
 *  Input:																*
 *		this	<button id='editName'>									*
 *		ev      click Event                                             *
 ************************************************************************/
function editName(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	    = this.form;
    var	idnx        = this.id.substring(8);
    if (idnx == 0)
    {
		newSearch	= this;		// identify button that was clicked
		refresh();
        return;
    }
    var	given   	= encodeURIComponent(form.GivenName.value);
    var	surname 	= encodeURIComponent(form.Surname.value);
    var	treeName    = encodeURIComponent(form.treeName.value);
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;

	// open edit dialog in right half of window
	var url	= "/FamilyTree/editName.php?idnx=" + idnx +
									  "&given=" + given + 
									  "&surname=" + surname +
									  "&treename=" + treeName +
									  "&lang=" + lang +
									  "&debug=" + debug;
	windowList.push(openFrame("event",
						      url,
						      "right"));
    return true;
}	// function editName

/************************************************************************
 *  function editMarriages												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about the marriages of the current individual.			*
 *																		*
 *  Input:																*
 *		this	<button id='Marriages'>									*
 *		ev      click Event                                             *
 ************************************************************************/
function editMarriages(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	= this.form;
    var	idir	= form.idir.value;
    if (idir > 0)
    {			// idir field present
        var	given   	= encodeURIComponent(form.GivenName.value);
        var	surname 	= encodeURIComponent(form.Surname.value);
        var	treeName    = encodeURIComponent(form.treeName.value);
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;

		// open edit dialog in right half of window
		var url	= "/FamilyTree/editMarriages.php?id=" + idir +
							   "&given=" + given + 
							   "&surname=" + surname +
							   "&treename=" + treeName +
							   "&lang=" + lang +
							   "&debug=" + debug;
		windowList.push(openFrame("marriages",
							  url,
							  "right"));
    }			// idir field present
    else
    {			// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();
    }			// individual record not created in database yet
    return true;
}	// function editMarriages

/************************************************************************
 *  function editParents												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about the parents of the current individual.			*
 *																		*
 *  Input:																*
 *		this	<button id='Parents'>									*
 *		ev      click Event                                             *
 ************************************************************************/
function editParents(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	= this.form;
    var	idir	= form.idir.value;
    if (idir > 0)
    {			// idir field present
        var	given   	= encodeURIComponent(form.GivenName.value);
        var	surname 	= encodeURIComponent(form.Surname.value);
        var	treeName    = encodeURIComponent(form.treeName.value);
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;

		// open edit dialog in right half of window
		var url	= "/FamilyTree/editParents.php?id=" + idir + 
							"&given=" + given + 
							"&surname=" + surname +
							"&treename=" + treeName +
							"&lang=" + lang +
							"&debug=" + debug;
		windowList.push(openFrame("parents",
							  url,
							  "right"));
    }			// idir field present
    else
    {			// individual record not created in database yet
		newSearch	    = this;		// identify button that was clicked
		refresh();
    }			// individual record not created in database yet
    return true;
}	// function editParents

/************************************************************************
 *  function editEventIndiv												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about an event of the current individual				*
 *  that is described by fields within the Person record itself.		*
 *																		*
 *  The type of event is communicated by the id attribute which 		*
 *  is 'Detail' followed by the event type.								*
 *		type		the event type, used to distinguish between the		*
 *					events that are recorded inside the				    *
 *					Person record										*
 *					See the top of this file for definitions		    *
 *																		*
 *  Input:																*
 *		this		instance of <button> that invoked this function		*
 *		ev          click Event                                         *
 ************************************************************************/
function editEventIndiv(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // check for open event frame
    if (windowList.length > 0)
    {			// there are incomplete actions pending
		var	text	= '';
		var	comma	= '';
		var	button	= document.getElementById('Submit');
		for (var iw = 0; iw < windowList.length; iw++)
		{		// loop through open iframes
		    var	iwindow	= windowList[iw];
		    var iframe	= iwindow.frameElement;
		    if (iframe && iframe.name == "event")
		    {
				text	+= comma + iwindow.document.title;
				comma	= ', ';
		    }
		}		// loop through open iframes
		if (text.length > 0)
		{
		    popupAlert("Please complete the following windows: " + text,
				       button);
		    return;
		}
    }			// there are incomplete actions pending

    var	type		= this.id.substring(6) - 0;
    var	form		= this.form;
    var	idir		= form.idir.value;
    var	givenname	= encodeURIComponent(form.GivenName.value);
    var	surname		= encodeURIComponent(form.Surname.value);
    if (idir > 0)
    {			// database record already exists
		var	url	= "/FamilyTree/editEvent.php?idir=" + idir +
							    "&type=" + type +
							    "&givenname=" + givenname +
							    "&surname=" + surname;
		if (debug.toLowerCase() == 'y')
		    url		+= "&debug=y";

		switch(type - 0)
		{		// add parameters for specify event types
		    case STYPE_BIRTH:
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.BirthDate.value) +
				       "&location=" +
				       encodeURIComponent(form.BirthLocation.value);
				break;
		    }		// birth event

		    case STYPE_CHRISTEN:
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.ChristeningDate.value) +
				       "&location=" +
				       encodeURIComponent(form.ChristeningLocation.value);
				break;
		    }		// christening event

		    case STYPE_DEATH:
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.DeathDate.value) +
				       "&location=" +
				       encodeURIComponent(form.DeathLocation.value);
				break;
		    }		// death event

		    case STYPE_BURIED:
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.BuriedDate.value) +
				       "&location=" +
				       encodeURIComponent(form.BuriedLocation.value);
				break;
		    }		// buried event

		    case STYPE_DEATHCAUSE:
		    {
				url	+= "&notes=" + 
				       encodeURIComponent(form.DeathCause.value);
				break;
		    }		// death cause fact

		    case STYPE_LDSB:	// LDS Baptism
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.BaptismDate.value) +
				       "&location=" +
				       encodeURIComponent(form.BaptismLocation.value);
				break;
		    }		// LDS Baptism event

		    case STYPE_LDSE:	// LDS Endowment
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.EndowmentDate.value) +
				       "&location=" +
				       encodeURIComponent(form.EndowmentLocation.value);
				break;
		    }		// LDS endowment event

		    case STYPE_LDSC:	// LDS Confirmation
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.ConfirmationDate.value) +
				       "&location=" +
				       encodeURIComponent(form.ConfirmationLocation.value);
				break;
		    }		// LDS confirmation event

		    case STYPE_LDSI:	// LDS Initiatory
		    {
				url	+= "&date=" + 
				       encodeURIComponent(form.InitiatoryDate.value) +
				       "&location=" +
				       encodeURIComponent(form.InitiatoryLocation.value);
				break;
		    }		// LDS Initiatory event

		}		// add parameters for specify event types

		if (debug != 'n')
		    popupAlert("editIndivid.js: editEventIndiv: " + url,
						this);
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;
        url                 += '&lang=' + lang

		// open edit dialog for event in right half of window
		windowList.push(openFrame("event",
    							  url, 
	    						  "right"));
    }			// database record already exists
    else
    {			// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();
    }			// individual record not created in database yet

    return true;
}	// function editEventIndiv

/************************************************************************
 *  function clearEventIndiv											*
 *																		*
 *  This method is called when the user requests to clear				*
 *  information about an event of the current individual				*
 *  that is described by fields within the Person record itself.		*
 *																		*
 *  The type of event is communicated by the id attribute which 		*
 *  is 'Clear' followed by the event type.								*
 *		type		the event type, used to distinguish between the		*
 *					events that are recorded inside the					*
 *					Person record										*
 *					See the top of the file for definitions				*
 *																		*
 *  Input:																*
 *		this		<button id='Clear...'>								*
 *		ev          click Event                                         *
 ************************************************************************/
function clearEventIndiv(ev)
{	
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	button		    = this;
    var	form		    = this.form;
    var	type		    = button.id.substring(5);
    var promptText      = "Are you sure you want to delete this event?";
    var textElt         = document.getElementById('confirmDeleteEvent');
    if (textElt)
        promptText      = textElt.innerHTML;
    else
        alert("cannot find element 'confirmDeleteEvent'");
    var parms		    = {"type"	: type,
						   "formname"	: form.name, 
						   "template"	: "",
						   "msg"	:
						   promptText};

    // ask user to confirm delete
	displayDialog('ClrInd$template',
			      parms,
			      this,		            // position relative to
			      confirmClearInds);	// 1st button confirms Delete
}		// function clearEventIndiv

/************************************************************************
 *  function confirmClearInd											*
 *																		*
 *  This method is called when the user confirms the request to delete	*
 *  an event which is defined inside the Person record.					*
 *  The contents of the fields describing the event are cleared.		*
 *  The user still needs to update the individual to apply the changes.	*
 *																		*
 *  Input:																*
 *		this		<button id='confirmClear...'>						*
 *		ev          click Event                                         *
 ************************************************************************/
function confirmClearInd(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	type		= this.id.substr(12);
    var	formname	= form.elements['formname' + type].value;
    var	form		= document.forms[formname];

    dialogDiv.style.display	= 'none';
    var	idir		= form.idir.value;
    if (idir)
    {
		// make the visible changes
		switch(type - 0)
		{		// act on specific type
		    case STYPE_BIRTH:
		    {
				form.BirthDate.value		= "";
				form.BirthLocation.value	= "";
				break;
		    }		// case STYPE_BIRTH:

		    case STYPE_CHRISTEN:
		    {
				form.ChristeningDate.value		= "";
				form.ChristeningLocation.value	= "";
				break;
		    }		// case STYPE_CHRISTEN:

		    case STYPE_DEATH:
		    {
				form.DeathDate.value		= "";
				form.DeathLocation.value	= "";
				break;
		    }		// case STYPE_DEATH:

		    case STYPE_BURIED:
		    {
				form.BuriedDate.value		= "";
				form.BuriedLocation.value	= "";
				break;
		    }		// case STYPE_BURIED:

		    case STYPE_DEATHCAUSE:
		    {
				form.DeathCause.value		= "";
				break;
		    }		// case STYPE_DEATHCAUSE:

		    case STYPE_LDSB:
		    {
				form.BaptismDate.value		= "";
				form.BaptismLocation.value	= "";
				if (form.IDTRBaptism)
				    form.IDTRBaptism.value	= 0;
				else
				{
				    // add a field that will cause updatePersonJson.php
				    // to clear the location
				    var	cell		= form.BaptismLocation.parentNode;
				    var	idtrfld		= document.createElement('input');
				    idtrfld.type	= 'hidden';
				    idtrfld.name	= 'IDTRBaptism';
				    idtrfld.value	= 1;
				    cell.appendChild(idtrfld);
				}
				break;
		    }		// case STYPE_LDSB:

		    case STYPE_LDSE:
		    {
				form.EndowmentDate.value	= "";
				form.EndowmentLocation.value	= "";
				if (form.IDTREndowment)
				    form.IDTREndowment.value	= 0;
				else
				{
				    // add a field that will cause updatePersonJson.php
				    // to clear the location
				    var	cell		= form.EndowmentLocation.parentNode;
				    var	idtrfld		= document.createElement('input');
				    idtrfld.type	= 'hidden';
				    idtrfld.name	= 'IDTREndowment';
				    idtrfld.value	= 1;
				    cell.appendChild(idtrfld);
				}
				break;
		    }		// case STYPE_LDSE:

		    case STYPE_LDSC:
		    {
				form.ConfirmationDate.value		= "";
				form.ConfirmationLocation.value		= "";
				if (form.IDTRConfirmation)
				    form.IDTRConfirmation.value		= 0;
				else
				{
				    // add a field that will cause updatePersonJson.php
				    // to clear the location
				    var	cell		= form.ConfirmationLocation.parentNode;
				    var	idtrfld		= document.createElement('input');
				    idtrfld.type	= 'hidden';
				    idtrfld.name	= 'IDTRConfirmation';
				    idtrfld.value	= 1;
				    cell.appendChild(idtrfld);
				}
				break;
		    }		// case STYPE_LDSC:

		    case STYPE_LDSI:
		    {
				form.InitiatoryDate.value		= "";
				form.InitiatoryLocation.value		= "";
				if (form.IDTRInitiatory)
				    form.IDTRInitiatory.value		= 0;
				else
				{
				    // add a field that will cause updatePersonJson.php
				    // to clear the location
				    var	cell		= form.InitiatoryLocation.parentNode;
				    var	idtrfld		= document.createElement('input');
				    idtrfld.type	= 'hidden';
				    idtrfld.name	= 'IDTRInitiatory';
				    idtrfld.value	= 1;
				    cell.appendChild(idtrfld);
				}
				break;
		    }		// case STYPE_LDSI:

		}		// act on specific type

		// update the database
		var	parms	= {"idir"	: idir,
						   "type"	: type};

		// invoke script to update Event and return XML result
		HTTP.post('/FamilyTree/deleteCitationsXml.php',
				  parms,
				  gotClearedEvent,
				  noClearedEvent);

		// show user an operation is in progress
		popupLoading(button);
    }			// have idir value
    else
		popupAlert("editIndivid.js: clearEventIndiv: unable to get value of idir from form",
				   this);
    return true;
}	// function confirmClearInd

/************************************************************************
 *  function editEventChildr											*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about an event of the current individual				*
 *  that is described by fields within the Childr record itself.		*
 *																		*
 *  The type of event is communicated by the id attribute which 		*
 *  is 'Detail' followed by the event type.								*
 *		type		the event type, used to distinguish between the		*
 *					events that are recorded inside the				    *
 *					Childr record								        *
 *				STYPE_LDSP				= 17  LDS Sealed to Parents		*
 *																		*
 *  Input:																*
 *		this	instance of <button> that invoked this function			*
 *		ev      click Event                                             *
 ************************************************************************/
function editEventChildr(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // check for open event frame
    if (windowList.length > 0)
    {			// there are incomplete actions pending
		var	text	= '';
		var	comma	= '';
		var	button	= document.getElementById('Submit');
		for (var iw = 0; iw < windowList.length; iw++)
		{		// loop through open iframes
		    var	iwindow	= windowList[iw];
		    var iframe	= iwindow.frameElement;
		    if (iframe && iframe.name == "event")
		    {
				text	+= comma + iwindow.document.title;
				comma	= ', ';
		    }
		}		// loop through open iframes
		if (text.length > 0)
		{
		    popupAlert("Please complete the following windows: " + text,
				       button);
		    return;
		}
    }			// there are incomplete actions pending

    var	type		= this.id.substring(6) - 0;
    var	form		= this.form;
    var	idcr		= form.idcr.value;
    var	givenname	= encodeURIComponent(form.GivenName.value);
    var	surname		= encodeURIComponent(form.Surname.value);
    if (idcr)
    {
		var	url	= "/FamilyTree/editEvent.php?idcr=" + idcr +
							    "&type=17" +
							    "&givenname=" + givenname +
							    "&surname=" + surname +
							    "&date=" + 
							    encodeURIComponent(form.SealingDate.value) +
							    "&idtr=" + form.SealingIdtr.value;

		if (debug.toLowerCase() == 'y')
		{
		    url		+= "&debug=y";
		    popupAlert("editIndivid.js: editEventChildr: " + url,
						this);
		}
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;
        url                 += '&lang=' + lang

		// open edit dialog for event in right half of window
		windowList.push(openFrame("event",
		    					  url,
			    				  "right"));
    }	// have idir value
    else
		popupAlert("editIndivid.js: editEventChildr: unable to get value of idcr from form",
				   this);
    return true;
}	// function editEventChildr

/************************************************************************
 *  function clearEventChildr											*
 *																		*
 *  This method is called when the user requests to clear				*
 *  information about an event of the current individual				*
 *  that is described by fields within the Childr record itself.		*
 *																		*
 *  The type of event is communicated by the id attribute which 		*
 *  is 'Clear' followed by the event type.								*
 *		type		the event type, used to distinguish between the		*
 *					events that are recorded inside the					*
 *					Child record										*
 *				STYPE_LDSP				= 17  LDS Sealed to Parents		*
 *																		*
 *  Input:																*
 *		this	    instance of <button> that invoked this function		*
 *		ev          click Event                                         *
 ************************************************************************/
function clearEventChildr(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	button		= this;
    var	type		= button.id.substring(5);
    var	form		= this.form;
    var	idcr		= form.idcr.value;
    if (idcr)
    {
		form.SealingDate.value	= "";
		form.SealingTemple.value	= "";
		if (form.IDTRSealing)
		    form.IDTRSealing.value	= 1;
		else
		{
		    // add a field that will cause updatePersonJson.php
		    // to clear the location
		    var	cell	= form.SealingTemple.parentNode;
		    var	idtrfld	= document.createElement('input');
		    idtrfld.type	= 'hidden';
		    idtrfld.name	= 'IDTRSealing';
		    idtrfld.value	= 1;
		    cell.appendChild(idtrfld);
		}

		// delete associated citations
		var	parms	= {"idcr"	: idcr,
						   "type"	: 17};

		// invoke script to update Event and return XML result
		HTTP.post('/FamilyTree/deleteCitationsXml.php',
				  parms,
				  gotClearedEvent,
				  noClearedEvent);

		// show user an operation is in progress
		popupLoading(button);
    }	// have idcr value
    else
		alert("editIndivid.js: 2102: clearEventChildr: unable to get value of idcr from form");
    return true;
}	// function clearEventChildr

/************************************************************************
 *  function gotClearedEvent											*
 *																		*
 *  The XML document representing the results of the request to clear	*
 *  the event has been received.										*
 ************************************************************************/
function gotClearedEvent(xmlDoc)
{
    hideLoading();		// hide the "loading..." popup
    var	topXml	= xmlDoc.documentElement;
    if (topXml && typeof(topXml) == "object" && topXml.nodeName == 'deleted')
    {			// valid response
		// all done
    }			// valid response
    else
    {
		if (topXml && typeof(topXml) == "object")
		    alert("editIndivid.js: 2123: gotClearedEvent: " +
						tagToString(topXml));
		else
		    alert("editIndivid.js: 2126: gotClearedEvent: '" + xmlDoc + "'");
    }
}       // function gotClearedEvent

/************************************************************************
 *  function noClearedEvent												*
 *																		*
 *  The server was unable to find the action script.					*
 ************************************************************************/
function noClearedEvent()
{
    hideLoading();		// hide the "loading..." popup
    alert("editIndivid.js: 2138: noClearedEvent: action script 'ClearIndivEvent.php' not found");
}       // function noClearedEvent

/************************************************************************
 *  function eventDetail												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about an event of the current individual				*
 *  that is described by an instance of Event.							*
 *																		*
 *  The unique numeric key of the instance of Event is					*
 *  contained within the id of the invoking <button>.					*
 *																		*
 *  Input:																*
 *		this		instance of <button> that invoked this function		*
 *		ev          click Event                                         *
 ************************************************************************/
function eventDetail(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // check for open event frame
    if (windowList.length > 0)
    {			// there are incomplete actions pending
		var	text	= '';
		var	comma	= '';
		var	button	= document.getElementById('Submit');
		for (var iw = 0; iw < windowList.length; iw++)
		{		// loop through open iframes
		    var	iwindow	= windowList[iw];
		    var iframe	= iwindow.frameElement;
		    if (iframe && iframe.name == "event")
		    {
				text	+= comma + iwindow.document.title;
				comma	= ', ';
		    }
		}		// loop through open iframes

		if (text.length > 0)
		{
		    popupAlert("Please complete the following windows: " + text,
				       button);
		    return;
		}
    }			// there are incomplete actions pending

    var	form		= this.form;
    var	idir		= form.idir.value - 0;
    var	sdElt		= null;
    if (idir > 0)
    {			// can edit immediately
		var	rownum		= this.id.substring(11);
		var	ider		= null;
		var	idet		= null;
    
		var	eventRow	= this.parentNode;
		var	rowname		= eventRow.id;
		var	givenname	= encodeURIComponent(form.GivenName.value);
		var	surname		= encodeURIComponent(form.Surname.value);

		// URL to invoke editEvent
		var	url	= "/FamilyTree/editEvent.php?idir=" + idir +
							"&givenname=" + givenname +
							"&surname=" + surname +
							"&rownum=" + rownum;
		if (debug.toLowerCase() == 'y')
		    url		+= "&debug=y";
    
		var	children	= eventRow.getElementsByTagName('input');
		for (var ic = 0; ic < children.length; ic++)
		{			// loop through children
		    var	child		= children[ic];
		    var	name		= child.id;
		    if (name === undefined || name === '')
				name		= child.name;
		    var	namePatt	= /^([a-zA-Z]+)([0-9]*)/;
		    var	result		= namePatt.exec(name);
		    if (result)
				name		= result[1];

		    switch(name.toLowerCase())
		    {		// act on specific fields
				case 'eventdate':
				case 'birthdate':
				case 'christeningdate':
				case 'baptismdate':
				case 'endowmentdate':
				case 'confirmationdate':
				case 'initiatorydate':
				case 'deathdate':
				case 'burieddate':
				{
				    url		+="&date=" + encodeURIComponent(child.value);
				    break;
				}
    
				case 'eventsd':
				{
				    sdElt		= child;
				    break;
				}
    
				case 'eventdescn':
				case 'birthdescn':
				case 'chrisdescn':
				case 'baptismdescn':
				case 'endowmentdescn':
				case 'confirmationdescn':
				case 'initiatorydescn':
				case 'deathdescn':
				case 'burieddescn':
				{
				    url		+= "&descn=" + encodeURIComponent(child.value);
				    break;
				}
    
				case 'eventlocation':
				case 'birthlocation':
				case 'christeninglocation':
				case 'baptismtemple':
				case 'endowmenttemple':
				case 'confirmationtemple':
				case 'initiatorytemple':
				case 'deathlocation':
				case 'buriedlocation':
				{
				    url     += "&location=" + encodeURIComponent(child.value);
				    break;
				}
    
				case 'eventider':
				{
				    ider		= child.value - 0;
				    break;
				}
    
				case 'eventidet':
				{
				    idet		= child.value - 0;
				    break;
				}
		    }		// act on specific fields
		}			// loop through children

		// add explicit IDER value or zero
		url		+= "&ider=" + ider;

		// pass the current values of the form input fields to
		// the server script so it can initialize the edit dialog
		if (ider > 0)
		{			// event in tblER
		    url	+= '&type=30';	// Citation::STYPE_EVENT
		}			// event in tblER
		else
		{			// event in tblIR
		    switch(idet)
		    {
				case 3:	// Event::ET_BIRTH
				{
				    url	+= '&type=2';	// Citation::STYPE_BIRTH
				    break;
				}
    
				case 4:	// Event::ET_BURIAL
				{
				    url	+= '&type=5';	// Citation::STYPE_BURIED
				    break;
				}
    
				case 5:	// Event::ET_CHRISTENING
				{
				    url	+= '&type=3';	// Citation::STYPE_CHRISTEN
				    break;
				}
    
				case 6:	// Event::ET_DEATHEvent::IDTYPE_INDIV
				{
				    url	+= '&type=4';	// Citation::STYPE_DEATH
				    break;
				}
    
				case 8:	// Event::ET_LDS_BAPTISM
				{
				    url	+= '&type=15';	// Citation::STYPE_LDSB
				    break;
				}
    
				case 16:	// Event::ET_LDS_CONFIRMATION
				{
				    url	+= '&type=26';	// Citation::STYPE_LDSC
				    break;
				}
    
				case 74:	// Event::ET_LDS_ENDOWED
				{
				    url	+= '&type=16';	// Citation::STYPE_LDSE
				    break;
				}
    
				case 75:	// Event::ET_LDS_INITIATORY
				{
				    url	+= '&type=27';	// Citation::STYPE_LDSI
				    break;
				}
    
				default:
				{
				    alert("editIndivid.js: 2347: eventDetail: idet=" + idet);
				    break;
				}
		    }
		}			// event in tblIR
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;
        url                 += '&lang=' + lang

		// debugging output if requested
		if (debug != 'n')
		    popupAlert("editIndivid.js: eventDetail: " + url,
				       this);

		// open edit dialog for event in right half of window
		windowList.push(openFrame("event",
		    					  url, 
			    				  "right"));
    }			// can edit immediately
    else
    {			// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();			    // update database first
    }			// individual record not created in database yet
}		// function eventDetail

/************************************************************************
 *  function eventAdd													*
 *																		*
 *  This method is called when the user requests to add					*
 *  an event to an individual.											*
 *																		*
 *  Input:																*
 *		this		instance of <button> that invoked this function		*
 *		ev          click Event                                         *
 ************************************************************************/
function eventAdd(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form		= this.form;
    var	idir		= form.idir.value;
    if (idir > 0)
    {			// database record already exists
		var url		= "/FamilyTree/editEvent.php?ider=0" +
								"&idir=" + idir +
								"&rownum="; 
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;
        url                 += '&lang=' + lang
		if (debug.toLowerCase() == 'y')
		{
		    url		+= "&debug=y";
		    popupAlert("editIndivid.js: eventAdd: " + url,
						this);
		}

		// open edit dialog for event in right half of window
		windowList.push(openFrame("event",
		    					  url,
			    				  "right"));
		return true;
    }			// database record already exists
    else
    {			// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();			    // update database first
    }			// individual record not created in database yet
}	// function eventAdd

/************************************************************************
 *  function eventDelete												*
 *																		*
 *  This method is called when the user requests to delete				*
 *  an event of an individual that is an instance of Event.		        *
 *																		*
 *  Input:																*
 *		this		<button id='EventDelete...'> 						*
 *		ev          click Event                                         *
 ************************************************************************/
function eventDelete(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	button			= this;
    var	form			= button.form;
    var	idir			= form.idir.value;
    var rownum			= button.id.substring(11);
    var	ider			= form.elements['EventIder' + rownum].value;
    var	idet			= form.elements['EventIdet' + rownum].value;
    var	citType			= form.elements['EventCitType' + rownum].value;
    var	row		    	= button.parentNode;
    var promptText      = "Are you sure you want to delete this event?";
    var textElt         = document.getElementById('confirmDeleteEvent');
    if (textElt)
        promptText      = textElt.innerHTML;
    else
        alert("cannot find element 'confirmDeleteEvent'");
    var parms			= {"type"	    : idet,
						   "ider"	    : ider,
						   "formname"	: form.name, 
						   "rownum"	    : rownum, 
						   "rowname"	: row.id,
						   "template"	: "",
						   "msg"	    : promptText};

    // ask user to confirm delete
	displayDialog('ClrInd$template',
			      parms,
			      this,		        // position relative to
			      confirmEventDel);	// 1st button confirms Delete
}		// function eventDelete

/************************************************************************
 *  function confirmEventDel											*
 *																		*
 *  This method is called when the user confirms the request to delete	*
 *  an event which is defined in an instance of Event.					*
 *  A request is sent to the server to delete the instance.				*
 *																		*
 *  Input:																*
 *		this			<button id='confirmClear...'>					*
 *		ev              click Event                                     *
 ************************************************************************/
function confirmEventDel(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // get the parameter values hidden in the dialog
    var	form				= this.form;
    var	type				= this.id.substr(12);
    var	ider				= form.elements['IDER' + type].value;
    var	rowname				= form.elements['RowName' + type].value;
    var	rownum				= form.elements['RowNum' + type].value;
    dialogDiv.style.display	= 'none';

    // action depends upon whether the event is recorded in tblIR or tblER
    if (ider > 0)
    {			// event is in tblER
		// invoke script to delete the record
		var parms		    = { "idime"	: ider,
							    "cittype"	: 30,
							    "rownum"	: rownum,
							    "rowname"	: rowname};
		// alert("confirmEventDel: { idime : " + ider + ", cittype : 30, rownum : " + rownum + ", rowname : " + rowname);
		HTTP.post('/FamilyTree/deleteEventXml.php',
				  parms,
				  gotDeleteEvent,
				  noDeleteEvent);

		// show user a long-running operation is in progress
		var mainForm	= document.indForm;
		var mainButton	= mainForm.elements['EventDelete' + rownum];
		popupLoading(mainButton);
    }			// event is in tblER
    else
    {			// event is in tblIR
		var	row	= document.getElementById(rowname);
		if (row)
		{		// expected standard event row
		    for(var ic = 0; ic < row.childNodes.length; ic++)
		    {		// loop through children
				var child	= row.childNodes[ic];
				if (child.nodeType == 1 && 
				    child.nodeName.toLowerCase() == 'input')
				{	// <input> tag
				    var name	= child.name;
				    if (name.substring(name.length - 4) == 'Date' ||
						name.substring(name.length - 8) == 'Location')
						child.value		= '';
				    else
				    if (name.substring(0, 12) == 'EventChanged')
						child.value		= '1';
				}	// <input> tag
		    }		// loop through children

		    // delete citations for the deleted event
		    var mainForm	= document.getElementById('indForm');
		    var parms		= {'idir'	: mainForm.idir.value,
							   'type'	: type};
		    switch(type)
		    {		// act on IDET value
				case '3':
				{	// birth
				    parms.type	= '2';
				    HTTP.post('/FamilyTree/deleteCitationsXml.php',
						      parms,
						      gotDeleteCitations,
						      noDeleteCitations);
				    break;
				}	// birth

				case '4':
				{	// burial
				    parms.type	= '5';
				    HTTP.post('/FamilyTree/deleteCitationsXml.php',
						      parms,
						      gotDeleteCitations,
						      noDeleteCitations);
				    break;
				}	// burial

				case '5':
				{	// christening
				    parms.type	= '3';
				    HTTP.post('/FamilyTree/deleteCitationsXml.php',
						      parms,
						      gotDeleteCitations,
						      noDeleteCitations);
				    break;
				}	// christening

				case '6':
				{	// death
				    parms.type	= '4';
				    HTTP.post('/FamilyTree/deleteCitationsXml.php',
						      parms,
						      gotDeleteCitations,
						      noDeleteCitations);
				    // also delete citations to cause of death
				    parms.type	= '9';
				    HTTP.post('/FamilyTree/deleteCitationsXml.php',
						      parms,
						      gotDeleteCitations,
						      noDeleteCitations);

				    var cause	= document.createElement("INPUT");
				    cause.setAttribute("type",	"hidden");
				    cause.setAttribute("name",	"DeathCause");
				    cause.setAttribute("id",	"DeathCause");
				    cause.setAttribute("value",	"");
				    mainForm.appendChild(cause);
				    break;
				}	// death

		    }		// act on IDET value
		}		// expected standard event row
		else
		    alert("editIndivid.js: 2580: confirmEventDelete: " +
				  "Unexpected rowname='" + rowname + "'");
    }			// event is in tblIR
}	// function confirmEventDelete

/************************************************************************
 *  function gotDeleteEvent												*
 *																		*
 *  This method is called when the response to the request to delete	*
 *  a Event is received from the server.								*
 *																		*
 *  Parameters:															*
 *		xmlDoc			reply as an XML document						*
 ************************************************************************/
function gotDeleteEvent(xmlDoc)
{
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'deleted')
    {
		var msglist	= root.getElementsByTagName('msg');
		var parmslist	= root.getElementsByTagName('parms');
		if (msglist.length == 0)
		{
		    if (parmslist.length == 0)
		    {			// parms feedback missing
				refresh();	// refresh the page
		    }			// parms feedback missing
		    else
		    {			// update DOM to remove displayed row
				var parms	    = getParmsFromXml(parmslist[0]);
				var parmstr	    = '';
				var comma	    = '{';
				for (key in parms)
				{
				    parmstr	    += comma + key + '=' + parms[key];
				    comma	    = ',';
				}
				//alert("editIndivid.js: gotDeleteEvent: parms=" +
				//		parmstr + "}");
				var rowname	= parms.rowname;
				if (rowname.substring(rowname.length - 3) == 'Row')
				{		// standard event
				    var name	= rowname.substring(0, rowname.length - 3);
				    var dateElt	= document.getElementById(name + "Date");
                    if (dateElt  === null)
                    {
                        alert("editIndivid.js: 2626 could not find element id='" + name + "Date'");
                    }
                    else
                    {
				        dateElt.value	= '';
				        dateElt.onchange();
                    }
				    var locnElt	= document.getElementById(name + "Location");
                    if (locnElt  === null)
                    {
                        alert("editIndivid.js: 2636 could not find element id='" + name + "Location'");
                    }
                    else
                    {
				    locnElt.value	= '';
				    locnElt.onchange();
                    }
				    var rownum	= parms.rownum;
				    var iderElt	= document.getElementById("EventIder" + rownum);
				    iderElt.value	= 0;
				}		// standard event
				else
				{		// row that may be removed from display
				    var row	= document.getElementById(parms.rowname);
				    var table	= row.parentNode;
				    table.removeChild(row);
				}		// row that may be removed from display
		    }			// update DOM to remove displayed row
		}
		else
		{
		    alert("editIndivid.js: 2518: gotDeleteEvent: " +
				  tagToString(msglist.item(0)));
		}
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root && root.childNodes)
		    msg	+= tagToString(root)
		else
		    msg	+= xmlDoc;
		alert (msg);
    }		// error
    hideLoading();		// hide the "loading..." popup
}	// function gotDeleteEvent

/************************************************************************
 *  function noDeleteEvent												*
 *																		*
 *  This method is called if the delete Event server script does		*
 *  not exist on the server.											*
 ************************************************************************/
function noDeleteEvent()
{
    hideLoading();		// hide the "loading..." popup
    alert("editIndivid.js: 2543: noDeleteEvent: " + 
		  "server script deleteEventXml.php not found");
}	// function noDeleteEvent

/************************************************************************
 *  function gotDeleteCitations											*
 *																		*
 *  This method is called when the response to the request to delete	*
 *  LegacyCitations for an event is received from the server.			*
 *																		*
 *  Parameters:															*
 *		xmlDoc			reply as an XML document						*
 ************************************************************************/
function gotDeleteCitations(xmlDoc)
{
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'deleted')
    {
		//alert("gotDeleteCitations: root=" + tagToString(root));
		var msglist	= root.getElementsByTagName('msg');
		var parmslist	= root.getElementsByTagName('parms');
		if (msglist.length > 0)
		{
		    alert("editIndivid.js: 2567: gotDeleteCitations: " +
				  tagToString(msglist.item(0)));
		}
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root && root.childNodes)
		    msg	+= tagToString(root)
		else
		    msg	+= xmlDoc;
		alert (msg);
    }		// error
}	// function gotDeleteCitations

/************************************************************************
 *  function noDeleteCitations											*
 *																		*
 *  This method is called if the delete LegacyCitations server script	*
 *  does not exist on the server.										*
 ************************************************************************/
function noDeleteCitations()
{
    alert("editIndivid.js: 2590: noDeleteCitations: " + 
		  "server script deleteCitationsXml.php not found");
}	// function noDeleteCitations

/************************************************************************
 *  function dateChanged												*
 *																		*
 *  This method is called when the user modifies the value of a date	*
 *  field in an event of an individual.			                        *
 *																		*
 *  Input:																*
 *		this		instance of <input> that invoked this function		*
 *		ev          change Event                                        *
 ************************************************************************/
function dateChanged(ev)
{
    var	value		        = this.value;

    // add a space anytime a digit is followed by a letter or vice-versa
    value		            = value.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    this.value		        = value.replace(/(\d)([a-zA-Z])/g,"$1 $2");

    // expand abbreviations if required
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);
    else
    if (this.value == '[')
		this.value	        = '[Blank]';

    if (this.checkfunc)
		this.checkfunc();

    // if the page title is empty, modify it to include the name fields
    // that have been filled in so far
    if ((this.name == 'BirthDate' || this.name == 'DeathDate') && updateTitle)
    {
        var	form		    = this.form;
		var newName	        = '';
		var givennameElt	= form.GivenName;
		if (givennameElt)
		    newName	        += givennameElt.value;
		var surnameElt	    = form.Surname;
		if (surnameElt)
		    newName	        += ' ' + surnameElt.value;
		newName		        += ' (';
		var birthElt	    = form.BirthDate;
		if (birthElt)
		    newName	        += birthElt.value;
		newName		        += "\u2014";        // m-dash
		var deathElt	    = form.DeathDate;
		if (deathElt)
		    newName	        += deathElt.value;
		newName		        += ')';
		var	titleElement	= document.getElementById('title');
		titleElement.innerHTML	= titlePrefix + newName;
    }               // update title
}	    // function dateChanged

/************************************************************************
 *  function eventChanged												*
 *																		*
 *  This method is called when the user modifies the value of any field	*
 *  in an event of an individual to record that the event has change.   *
 *																		*
 *  Input:																*
 *		this		instance of <input> that invoked this function		*
 *		ev          change Event                                        *
 ************************************************************************/
function eventChanged(ev)
{
    var	row		    = this.parentNode;
    if (row.className != 'row')
        row		    = row.parentNode;
    var inputs      = row.getElementsByTagName('input');
    var	changeElement	= null;
    var elementids      = '';
    for(var i = 0; i < inputs.length; i++)
    {
		var	child	        = inputs[i];
        var id              = child.id;
        elementids          += id + ', ';
		if (id && id.substring(0,12) == 'EventChanged')
		    changeElement	= child;
    }	
    // notify the script updatePersonJson.php that this event has been changed
    if (changeElement)
		changeElement.value	= "1";
    else
		alert("editIndivid.js: 2814: eventChanged: " +
				"cannot find EventChanged element " +
				"for <input name='"+ name + "'> row=" + row.outerHTML + " elementids=" + elementids);
}	// function eventChanged

/************************************************************************
 *  function eventPrefChanged											*
 *																		*
 *  This method is called when the user modifies the setting of a		*
 *  preferred event checkbox.											*
 *																		*
 *  Input:																*
 *		this		instance of <input> that invoked this function		*
 *		ev          change Event                                        *
 ************************************************************************/
function eventPrefChanged(ev)
{
    var	form		= this.form;
    var	name		= this.name;
    var	row		    = this.parentNode;
    var inputs      = this.getElementsByTagName('input');
    for(var ic = 0; ic < inputs.length; ic++)
    {				// loop through all siblings in this row
		var	child	= inputs[ic];
		if (child.id && child.id.substring(0,12) == 'EventChanged')
		{			// EventChanged hidden input field
		    child.value		= "1";
		    break;
		}			// EventChanged hidden input field
    }				// loop through all siblings in this row

    var namePattern	= /^([a-zA-Z$_\[\]]+)([0-9]*)$/;
    var pieces		= namePattern.exec(name);
    if (pieces === null)
    {
		pieces	= [name, name, ''];
    }
    var	colName		= pieces[1];
    var rowNum		= pieces[2] - 0;
    var idet		= idetArray[rowNum];

    // Since this method is only called if the value has changed
    // if the element is now checked then previously it wasn't
    if (this.checked)
    {				// uncheck any other instance that is checked
		for (var i = 0; i < idetArray.length; i++)
		{			// loop through event rows
		    if (i != rowNum && idetArray[i] == idet)
		    {			// another event row of the same type
				var	prefElt	= document.getElementById('EventPref' + i);
				if (prefElt.checked)
				{		// element was previously checked
				    prefElt.checked	= false;
				    var	row		    = prefElt.parentNode;
                    var inputs      = row.getElementsByTagName('input');
				    for(var ic = 0; ic < inputs.length; ic++)
				    {		// loop through all siblings in this row
						var	child	= inputs[ic];
						if (child.id &&
						    child.id.substring(0,12) == 'EventChanged')
						{	// EventChanged hidden input field
						    child.value		= "1";
						    break;
						}	// EventChanged hidden input field
				    }		// loop through all siblings in this row
				}		// element was previously checked
		    }			// another event row of the same type
		}			// loop through event rows
    }				// uncheck any other instance that is checked
    else
    {				// cannot uncheck some events
		if (idet == 3 || idet == 4 || idet == 5 || idet == 6 ||
		    idet == 8 || idet == 16 || idet == 74 || idet == 75 ||
		    idet == 76 || idet == 78)
		{			// events recorded in tblIR
		    if (!this.checked)
		    {			// was not previously checked
				this.checked	= true;
		    }			// was not previously checked
		}			// events recorded in tblIR
    }				// cannot uncheck some events
}		// function eventPrefChanged

/************************************************************************
 *  function grantAccess												*
 *																		*
 *  This method is called when the user requests to grant access		*
 *  to the current individual to a user.								*
 *																		*
 *  Input:																*
 *		this		instance of <button> that invoked this function		*
 *		ev          click Event                                         *
 ************************************************************************/
function grantAccess(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    // open dialog to grant access in right half of window
    var	form	= this.form;
    var	idir	= form.idir.value;
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
	var url			    = "/FamilyTree/grantIndivid.php?idir=" + idir +
                                                        "&lang=" + lang;
    windowList.push(openFrame("event",
						      url,
						      "right"));
}	// function grantAccess

/************************************************************************
 *  function genderChanged												*
 *																		*
 *  This method is called when the user modifies the value of the		*
 *  gender of an individual.											*
 *																		*
 *  Input:																*
 *		this		instance of <input> that invoked this function		*
 *		ev          change Event                                        *
 ************************************************************************/
function genderChanged(ev)
{
    var	form		= this.form;
    var	sex		= this.options[this.selectedIndex].value;
    switch(sex)
    {		// act on new value of sex
		case '0':
		{	// male
		    this.className	= 'male'
		    break;
		}	// male

		case '1':
		{	// female
		    this.className	= 'female'
		    break;
		}	// female

		default:
		{	// unknown
		    this.className	= 'unknown'
		    break;
		}	// unknown

    }		// act on new value of sex
}	// function genderChanged

/************************************************************************
 *  function orderEventsByDate											*
 *																		*
 *  This method is called when the user requests to reorder the			*
 *  Events by date.														*
 *																		*
 *  Input:																*
 *		this		<button id='Order'> element							*
 *		ev          click Event                                         *
 ************************************************************************/
function orderEventsByDate(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	button		= this;
    button.disabled	= true;		// only permit one sort
    var	form		= button.form;
    var	idir		= form.idir.value;

    // construct array of event rows
    var eventBody	= document.getElementById('EventBody');
    var	eventRows	= eventBody.children;
    var	birthSD		= -100000000;
    var sdElement	= null;
    var eventSD		= -99999999;
    var idetElement	= null;
    var idet		= null;

    // some events may have a sorted date of -99999999, patch them to
    // one day after the birth, so they will not be sorted before birth
    for (var ir = 1; ir <= eventRows.length; ir++)
    {
        var orderElt        = document.getElementById('EventOrder' + ir);
        orderElt.value      = ir - 1;
        var changedElt      = document.getElementById('EventChanged' + ir);
        changedElt.value    = 1;
    }
}		// function orderEventsByDate

/************************************************************************
 *  function compareSortDates											*
 *																		*
 *  This function is invoked by the array sort method to compare two	*
 *  sort dates.															*
 *																		*
 *  Parameters:															*
 *		first			sort date of first event						*
 *		second			sort date of first event						*
 *																		*
 *  Returns:															*
 *		<0 if first date is after second date							*
 *		0 if both dates are the same									*
 *		>0 if first date is before second date							*
 ************************************************************************/
function compareSortDates(first, second)
{
    return first.eventSD - second.eventSD;
}		// function compareSortDates

/************************************************************************
 *  function gotOrder													*
 *																		*
 *  This method is called when the XML response to a request to the		*
 *  server to reorder the Events by date is received.					*
 *																		*
 *  Parameters:															*
 *		xmlDoc			reply as an XML document						*
 ************************************************************************/
function gotOrder(xmlDoc)
{
    hideLoading();		// hide the "loading..." popup
    var	evtForm	= document.evtForm;
    var	root	= xmlDoc.documentElement;
    if (root && root.nodeName == 'ordered')
    {
		refresh();		// refresh the page
    }
    else
    {		// error
		var	msg	= "Error: ";
		if (root)
		{
		    for(var i = 0; i < root.childNodes.length; i++)
		    {		// loop through children
				var node	= root.childNodes[i];
				if (node.nodeValue != null)
				    msg		+= node.nodeValue;
		    }		// loop through children
		}		// have XML response
		else
		    msg		+= xmlDoc;
		alert (msg);
    }		// error
}	// function gotOrder

/************************************************************************
 *  function ShowMore													*
 *																		*
 *  This method is called when the user requests to see more input		*
 *  function fields														*
 *																		*
 *  Input:																*
 *		this		<button id='ShowMore'> element						*
 *		ev          click Event                                         *
 ************************************************************************/
function showMore(ev)
{
    if (!ev)
        ev              = window.event;
    ev.stopPropagation();

    var	form		    = this.form;
    var layoutTable	    = document.getElementById('layoutTable');
    var	tableBody	    = layoutTable.tBodies[0];
    var oldRow;		            // existing row to delete
    var	newRow;		            // new row to delete
    var	ie;		                // index through children of a node
    var	cell;		            // cell within a table row
    var	elt		        = null;	// an HTML Element

    var	idir		    = form.idir.value;
    var	parms		    = {'idir'	        : idir,
						   'template'	    : '',
						   'userRef'	    : 
						document.getElementById('HideUserRef').value,
						   'ancestralRef'   :
						document.getElementById('HideAncestralRef').value};
    if (oldRow)
    {			// hide expanded form
		oldRow		    = document.getElementById('NotesRow');
		tableBody.removeChild(oldRow);

		oldRow		    = document.getElementById('PrivateRow');
		tableBody.removeChild(oldRow);

		this.innerHTML	= 'Show More Options';
    }			// hide expanded form
    else
    {			// display expanded form
		// add row with options to edit Medical and Research Notes
		newRow	= createFromTemplate("NotesRow$template",
							     parms,
							     null);
		newRow		= tableBody.appendChild(newRow);
		for(ie = 0; ie < newRow.childNodes.length; ie++)
		{		// loop through cells of row
		    cell		= newRow.childNodes[ie];
		    cell.onmouseover	= eltMouseOver;
		    cell.onmouseout	= eltMouseOut;
		}		// loop through cells of row

		// activate functionality of buttons
		elt		= document.getElementById('Detail7');
		elt.addEventListener('click', editEventIndiv);
		elt		= document.getElementById('Detail8');
		elt.addEventListener('click', editEventIndiv);

		// add row with option to hide information about this individual
		newRow	= createFromTemplate("PrivateRow$template",
							     parms,
							     null);
		newRow		=tableBody.appendChild(newRow);
		for(ie = 0; ie < newRow.childNodes.length; ie++)
		{		// loop through cells of row
		    cell		= newRow.childNodes[ie];
		    cell.onmouseover	= eltMouseOver;
		    cell.onmouseout	= eltMouseOut;
		}		// loop through cells of row

		// initialize checkbox
		elt		= document.getElementById('PrivateCheckBox');
		value		= document.getElementById('Private').value;
		if (value == '1')
		    elt.checked	= true;

		// add row with user and ancestral file reference numbers
		newRow	= createFromTemplate("RefRow$template",
							     parms,
							     null);
		newRow		=tableBody.appendChild(newRow);
		for(ie = 0; ie < newRow.childNodes.length; ie++)
		{		// loop through cells of row
		    cell		= newRow.childNodes[ie];
		    cell.onmouseover	= eltMouseOver;
		    cell.onmouseout	= eltMouseOut;
		}		// loop through cells of row


		this.innerHTML	= 'Hide Extra Options';
    }			// display expanded form

}		// function showMore

/************************************************************************
 *  function editAddress												*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about the Address field of the current individual.		*
 *																		*
 *  Input:																*
 *		this		<button id='Address'> element						*
 *		ev          click Event                                         *
 ************************************************************************/
function editAddress(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	            = this.form;
    if (form.idar && form.idar.value.length > 0)
    {	// idar present
		var	idar	        = form.idar.value;
		var	given	        = encodeURIComponent(form.GivenName.value);
		var	surname	        = encodeURIComponent(form.Surname.value);
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;

		// open edit dialog for event in right half of window
		var	url;
		if (idar > 0)
		    url	  = "/FamilyTree/Address.php?idar=" + idar + 
							    "&kind=0" +
							    "&given=" + given +
							    "&surname=" + surname +
							    "&formname=indForm" +
                                "&lang=" + lang;
		else
		    url	  = "/FamilyTree/Address.php?kind=0" +
							    "&given=" + given +
							    "&surname=" + surname +
							    "&formname=indForm" +
                                "&lang=" + lang;
		windowList.push(openFrame("address",
							  url,
							  "right"));
    }	// idar present
    else
		alert("editIndivid.js: 3074: editAddress: " +
				"unable to get value of idar from form");
    return true;
}	// function editAddress

/************************************************************************
 *  function editPictures												*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures of the current individual that are	*
 *  recorded by instances of Picture.									*
 *																		*
 *  Parameters:															*
 *		this		<button id='Pictures'> element						*
 *		ev          click Event                                         *
 ************************************************************************/
function editPictures(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	= this.form;

    if (form.idir && form.idir.value > 0)
    {	// idir present in form
		var	idir	= form.idir.value;
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;

		// open edit dialog for event in right half of window
		var url		= "/FamilyTree/editPictures.php?idir=" + idir +
					                				"&idtype=Indiv" +
                                                    "&lang=" + lang;
		windowList.push(openFrame("pictures",
							  url,
							  "right"));
    }		// database record already exists
    else
    {	// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();
    }	// individual record not created in database yet
    return true;
}	// function editPictures

/************************************************************************
 *  function setIdar													*
 *																		*
 *  This is a method that may be called from a dialog invoked by		*
 *  this page to set the value of the hidden IDAR element.				*
 *  This method is an attribute of the <form> element.					*
 *																		*
 *  Parameters:															*
 *		this		form containing <input type='hidden' name='IDAR'>   *
 *		newIdar		new value for IDAR								    *
 ************************************************************************/
function setIdar(newIdar)
{
    this.idar.value		= newIdar;
    var	button		= document.getElementById('Address');
    var template	= null;
    if (newIdar == 0)
		template	= document.getElementById('AddressAdd');
    else
		template	= document.getElementById('AddressRepl');
    if (button && template)
    {		// edit address button present
		// replace the text in the button that says "Add Address"
		// with text from a template to make the code language independent
		button.innerHTML	= template.innerHTML;
    }		// edit address button present
}		// function setIdar

/************************************************************************
 *  function delIndivid													*
 *																		*
 *  This is the onclick method of the button with name 'Delete'.		*
 *  This method invokes the delIndivid.php script to delete the current	*
 *  Person.																*
 *																		*
 *  Parameters:															*
 *		this		<button id='Delete'>								*
 *		ev          click Event                                         *
 ************************************************************************/
function delIndivid(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	= this.form;
    if (form.idir && form.idir.value.length > 0)
    {		// idir field present
		var	idir	= form.idir.value;
		if (idir > 0)
		    location	= "deleteIndivid.php?idir=" + idir;
		else
		    location	= 'nominalIndex.php?name=&lang=' + args['lang'];
    }		// idir field present
    else
		alert("editIndivid.js: 3157: delIndivid: " +
				"unable to get value of idir from form");
    return true;	// continue
}		// function delIndivid

/************************************************************************
 *  function mergeIndivid												*
 *																		*
 *  This is the onclick method of the button with name 'Merge'.			*
 *  This method invokes the mergeIndivid.php script to merge the		*
 *  current Person with another											*
 *																		*
 *  Parameters:															*
 *		this		<button id='Merge'>									*
 *		ev          click Event                                         *
 ************************************************************************/
function mergeIndivid(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form	= this.form;
    if (form.idir && form.idir.value > 0)
    {		// idir field present
		var	idir	        = form.idir.value;
		// open merge dialog in right half of window
        var lang            = 'en';
        if ('lang' in args)
            lang            = args.lang;
		var url		        = "/FamilyTree/mergeIndivid.php?idir=" + idir +
                                                            "&lang=" + lang;
		if (debug.toLowerCase() == 'y')
		    url		+= "&Debug=Y";
		var mwindow	= openFrame("mergeFrame",
							    url,
							    "right");
    }		// idir field present
    else
    {	// individual record not created in database yet
		newSearch	= this;		// identify button that was clicked
		refresh();
    }	// individual record not created in database yet
    return true;	// continue
}		// function mergeIndivid

/************************************************************************
 *  function popdownSearch												*
 *																		*
 *  Display popdown menu of search buttons.								*
 *																		*
 *  Input:																*
 *		this		<button id='Search'>			     			    *
 *		ev          click Event                                         *
 ************************************************************************/
function popdownSearch(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form			= this.form;
    var menu			= document.getElementById("SearchDropdownMenu");
    if (menu)
    {			// make the menu visible on top of the button
		menu.style.position	= "fixed";
		menu.style.left		= getOffsetLeft(this) + "px";
		menu.style.top		= getOffsetTop(this) + "px";
		menu.style.display 	= 'block';
		menu.style.visibility	= "visible";
		return menu;
    }			// make the menu visible on top of the button
}		// function popdownSearch

/************************************************************************
 *  function censusSearch												*
 *																		*
 *  Perform a search for a matching individual in census tables.		*
 *																		*
 *  Input:																*
 *		this		<button id='censusSearch'>	    					*
 *		ev          click Event                                         *
 ************************************************************************/
function censusSearch(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form			= this.form;
    var menu			= document.getElementById("SearchDropdownMenu");
    if (menu)
		menu.style.visibility	= "hidden";

    var	yearPatt		= /\d{4}/;
    var	birthDate		= form.BirthDate.value;
    var	birthYear		= '';
    var	rxRes			= yearPatt.exec(birthDate);
    if (rxRes)
		birthYear		= rxRes[0];
    var	givenName		= form.GivenName.value;
    if (givenName.length > 3)
		givenName		= givenName.substring(0,3);
    var	gender			= form.Gender.value;
    var	sex			    = '';
    if (gender == 0)
		sex			    = 'M';
    else
    if (gender == 1)
		sex			    = 'F';
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;

    var searchUrl		= 
		"/database/CensusResponse.php?Query=&Census=CAALL&Count=20"+
		"&GivenNames=" + encodeURIComponent(givenName) +
		"&Surname=" + encodeURIComponent(form.Surname.value) +
		"&SurnameSoundex=yes" +
		"&Sex=" + sex +
		"&BYear=" + birthYear +
		"&Range=5" + 
        "&lang=" + lang;

    // open Ancestry search dialog in right half of window
    var swindow	= openFrame("searchFrame",
						    searchUrl,
						    "right");
}		// function censusSearch

/************************************************************************
 *  function bmdSearch													*
 *																		*
 *  Perform a search for a matching individual in vital statistics		*
 *  tables.																*
 *																		*
 *  Input:																*
 *		this		<button id='bmdSearch'>								*
 *		ev          click Event                                         *
 ************************************************************************/
function bmdSearch(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form			= this.form;
    var menu			= document.getElementById("SearchDropdownMenu");
    if (menu)
		menu.style.visibility	= "hidden";

    var	yearPatt		= /\d{4}/;
    var	birthDate		= form.BirthDate.value;
    var	birthYear		= '';
    var	rxRes			= yearPatt.exec(birthDate);
    if (rxRes)
		birthYear		= rxRes[0];
    var	deathDate		= form.DeathDate.value;
    var	deathYear		= '';
    var	rxRes			= yearPatt.exec(deathDate);
    if (rxRes)
		deathYear		= rxRes[0];
    var	givenName		= form.GivenName.value;
    if (givenName.length > 3)
		givenName		= givenName.substring(0,3);
    var	gender			= form.Gender.value;
    var	sex			= '';
    if (gender == 0)
		sex			= 'M';
    else
    if (gender == 1)
		sex			= 'F';
    var	birthPlace		= form.BirthLocation.value;
    if (birthPlace.length > 6)
		birthPlace		= birthPlace.substring(0,6);
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;

    var searchUrl		= 
		"/Canada/BirthRegResponse.php?RegDomain=CAON&Offset=0&Limit=20" +
		"&GivenNames=" + encodeURIComponent(givenName) +
		"&Surname=" + encodeURIComponent(form.Surname.value) +
		"&SurnameSoundex=yes" +
		"&Sex=" + sex +
		"&BirthPlace=" + encodeURIComponent(birthPlace) +
		"&BirthDate=" + birthYear +
		"&MotherName=" + encodeURIComponent(form.motherSurname.value) +
        "&lang=" + lang;

    // open Ancestry search dialog in right half of window
    var swindow	= openFrame("searchFrame",
						    searchUrl,
						    "right");
}		// function bmdSearch

/************************************************************************
 *  function ancestrySearch												*
 *																		*
 *  Perform a search for a matching individual in Ancestry.ca.			*
 *																		*
 *  Input:																*
 *		this		<button id='ancestrySearch'>		        		*
 *		ev          click Event                                         *
 ************************************************************************/
function ancestrySearch(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    var	form			= this.form;
    var menu			= document.getElementById("SearchDropdownMenu");
    if (menu)
		menu.style.visibility	= "hidden";

    var	yearPatt		= /\d{4}/;
    var	birthDate		= form.BirthDate.value;
    var	birthYear		= '';
    var	rxRes			= yearPatt.exec(birthDate);
    if (rxRes)
		birthYear		= rxRes[0];
    var	deathDate		= form.DeathDate.value;
    var	deathYear		= '';
    var	rxRes			= yearPatt.exec(deathDate);
    if (rxRes)
		deathYear		= rxRes[0];
    var searchUrl		= 
		"http://search.ancestry.ca/cgi-bin/sse.dll?gl=ROOT_CATEGORY" +
		"&rank=1" +
		"&new=1" +
		"&so=3" +
		"&MSAV=1" +
		"&msT=1" +
		"&gss=ms_f-2_s" +
		"&gsfn=" + encodeURIComponent(form.GivenName.value) +
		"&gsln=" + encodeURIComponent(form.Surname.value) +
		"&msbdy=" + birthYear +
		"&msbpn__ftp=" + encodeURIComponent(form.BirthLocation.value) +
		"&msddy=" + deathYear +
		"&msdpn__ftp=" + encodeURIComponent(form.DeathLocation.value) +
		"&msfng0=" + encodeURIComponent(form.fatherGivenName.value) +
		"&msfns0=" + encodeURIComponent(form.fatherSurname.value) +
		"&msmng0=" + encodeURIComponent(form.motherGivenName.value) +
		"&msmns0=" + encodeURIComponent(form.motherSurname.value) +
		"&cpxt=1" +
		"&catBucket=rstp" +
		"&uidh=l88" +
		"&cp=3"

    // open Ancestry search dialog in right half of window
    var swindow	= openFrame("searchFrame",
						    searchUrl,
						    "right");
}		// function ancestrySearch

/************************************************************************
 *  function marriageUpdated											*
 *																		*
 *  This is a method of the form object that is called by the script 	*
 *  editMarriages.php to report back that a marriage has been updated.	*
 *																		*
 *  Input:																*
 *		this			the form instance								*
 *		idmr			unique numeric identifier of updated marriage	*
 *		newcount		number of marriages after update				*
 ************************************************************************/
function marriageUpdated(idmr, newcount)
{
    // remove the windowList entry for iframe in which the marriage was
    // displayed
    for (var iw = 0; iw < windowList.length; iw++)
    {			// loop through windows
		var iwindow	= windowList[iw];
		var iframe	= iwindow.frameElement;
		if (iframe && iframe.name == "marriages")
		{
		    windowList.splice(iw, 1);
		    break;
		}
    }			// loop through windows

    var	form		= this;
    var button		= document.getElementById('Marriages');
    var	template;
    if (idmr > 0)
    {			// marriage that was updated
		if (form.IDMRPref)
		{	// IDMRPref field present in form
		    if (form.IDMRPref.value == 0)
		    {	// not set yet
				form.IDMRPref.value		= idmr;
		    }	// not set yet
		}	// IDMRPref field present in form
    }			// marriage that was updated
    form.familyCount.value	= newcount;
    if (newcount > 0)
		template	= document.getElementById('EditFamiliesRepl');
    else
		template	= document.getElementById('AddSpouseRepl');

    // update appearance of Marriages button
    if (button && template)
		button.innerHTML	= template.innerHTML;

    // switch between Delete and Merge buttons
    var deleteButton	= document.getElementById('Delete');
    var mergeButton	= document.getElementById('Merge');
    if (newcount == 0 &&
		form.parentCount.value == 0)
    {
		if (deleteButton)
		    deleteButton.style.display	= 'inline';
		if (mergeButton)
		    mergeButton.style.display	= 'none';
    }
    else
    {
		if (deleteButton)
		    deleteButton.style.display	= 'none';
		if (mergeButton)
		    mergeButton.style.display	= 'inline';
    }
}		// function marriageUpdated

/************************************************************************
 *  function setIdmrPref												*
 *																		*
 *  This is a method of the form object that is called by the script	*
 *  editMarriages.php to report back that the preferred marriage has	*
 *  been updated.														*
 *																		*
 *  Input:																*
 *		this		the form instance									*
 *		idmr		unique numeric identifier of preferred marriage		*
 ************************************************************************/
function setIdmrPref(idmr)
{
    var	form		= this;
    var	prefElt		= form.IDMRPref;
    if (!prefElt)
    {	// IDMRPref field not present in form
		// add it
		prefElt		= document.createElement('INPUT');
		inputElt.type	= 'hidden';
		inputElt.name	= 'IDMRPref';
		form.appendChild(inputElt);
    }	// IDMRPref field not present in form
    prefElt.value		= idmr;
}		// function setIdmrPref

/************************************************************************
 *  function setParentsPref												*
 *																		*
 *  This is a method of the form object that is called by the script	*
 *  editMarriages.php to report back that the particular set of parents	*
 *  has been set as the preferred set of parents for this individual.	*
 *																		*
 *  Input:																*
 *		this	the form instance										*
 *		idmr	unique numeric identifier of preferred set of parents	*
 ************************************************************************/
function setParentsPref(idmr)
{
    var	form		= this;
    var	prefElt		= form.ParentsPref;
    if (!prefElt)
    {	// ParentsPref field not present in form
		// add it
		prefElt		= document.createElement('INPUT');
		inputElt.type	= 'hidden';
		inputElt.name	= 'ParentsPref';
		form.appendChild(inputElt);
    }	// ParentsPref field not present in form
    prefElt.value		= idmr;
}		// function setParentsPref

/************************************************************************
 *  function getRowNumOf												*
 *																		*
 *  Determine the displayed event row number containing an element		*
 *																		*
 *  Parameters:															*
 *		anElement		an HtmlElement instance							*
 *																		*
 *  Returns:															*
 *		row number as an integer										*
 ************************************************************************/
function getRowNumOf(anElement)
{
    for (var next = anElement.nextSibling; next; next = next.nextSibling)
    {
		if (next.nodeName.toLowerCase() == 'input')
		{
		    var	namePatt	= /^([a-zA-Z]+)([0-9]+)/;
		    var	result		= namePatt.exec(next.name);
		    if (result)
		    {
				name		= result[1];
				rownum		= result[2];
				return rownum - 0;
		    }
		} 
    }
    return '';
}       // function getRowNumOf

/************************************************************************
 *  function eventFeedback												*
 *																		*
 *  This is a method of the form object that is called by the script	*
 *  editEvent.php to report back changes to an event that should be		*
 *  reflected in this form.												*
 *																		*
 *  Parameters:															*
 *		this	the form instance										*
 *		parms	the values of fields from the editEvent.php dialog		*
 *				as an associative array									*
 *						parms.type				STYPE_xxx				*
 *						parms.preferred			0 or 1 preferred indic	*
 *						parms.ider				key of Event			*
 *						parms.rownum			id='EventRow..' optional*
 *						parms.etype				IDET value				*
 *						parms.surname			new surname value		*
 *						parms.givenName			new given name value	*
 *						parms.date				new event date			*
 *						parms.location			new location string		*
 *						parms.note				new note text			*
 ************************************************************************/
function eventFeedback(parms)
{
    // diagnostic output
    if (debug.toLowerCase() == 'y')
    {
		var	msg	= "{";
		var	comma	= "";
		for(key in parms)
		{
		    msg		+= comma + key + "='" + parms[key] + "'";
		    comma	= ",";
		}
		alert("editIndivid.js: 3556: eventFeedback: parms=" + msg + "}");
    }

    // remove the windowList entry for iframe in which the event was
    // displayed
    for (var iw = 0; iw < windowList.length; iw++)
    {			// loop through windows
		var iwindow	= windowList[iw];
		var iframe	= iwindow.frameElement;
		if (iframe && iframe.name == "event")
		{
		    windowList.splice(iw, 1);
		    break;
		}
    }			// loop through windows

    // process parameters
    var	form		= this;
    var	type		= 0;
    var	preferred	= 0;
    var	ider		= 0;
    var	idet		= 0;
    var	citType		= 0;
    var date		= '';
    var datesd		= null;
    var	rownum		= '';

    for (var prop in parms)
    {
		switch(prop)
		{
		    case 'type':
		    {
				type			= parseInt(parms.type);
				break;
		    }		// type

		    case 'preferred':
		    {
				preferred		= parseInt(parms.preferred);
				break;
		    }		// preferred

		    case 'ider':
		    {
				ider			= parseInt(parms.ider);
				break;
		    }		// ider

		    case 'etype':
		    {
				idet			= parseInt(parms.etype);
				break;
		    }		// idet

		    case 'citType':
		    {
				citType			= parseInt(parms.citType);
				break;
		    }		// idet

		    case 'date':
		    {
				date			= parms.date;
				break;
		    }		// date

		    case 'datesd':
		    {
				datesd			= parseInt(parms.datesd);
				break;
		    }		// datesd

		    case 'rownum':
		    {
				rownum			= parms.rownum;
				break;
		    }		// rownum

		    case 'surname':
		    {
				form.Surname.value	= parms.surname;
				break;
		    }		// rownum

		    case 'givenName':
		    {
				form.GivenName.value	= parms.givenName;
				break;
		    }		// rownum

		}
    }		// loop through parameters

    // event type is name or notes,recorded in tblIR, not in an event record
    // and so not displayed in one of the event rows in the dialog
    if (type == STYPE_NAME ||		// 1
		type == STYPE_NOTESGENERAL ||	// 6
		type == STYPE_NOTESRESEARCH ||	// 7
		type == STYPE_NOTESMEDICAL)	// 8
    {			// name updated
		return;
    }			// name updated

    if (rownum === '')
    {			// row num not supplied by caller
		switch(type)
		{		// row number on type of event
    
		    case STYPE_BIRTH:
		    {
				if (form.BirthDate)
				{	// form already includes birth date input field
				    rownum	= getRowNumOf(form.BirthDate);
				}	// form already includes birth date input field
				break;
		    }	// birth event
    
		    case STYPE_CHRISTEN:
		    {
				if (form.ChristeningDate)
				{	// form already includes christening date input field
				    rownum	= getRowNumOf(form.ChristeningDate);
				}	// form already includes christening date input field
				break;
		    }	// christening event
    
		    case STYPE_DEATH:
		    {
				if (form.DeathDate)
				{	// form already includes death date input field
				    rownum	= getRowNumOf(form.DeathDate);
				}	// form already includes death date input field
				break;
		    }	// death event
    
		    case STYPE_BURIED:
		    {
				if (form.BuriedDate)
				{	// form already includes buried date input field
				    rownum	= getRowNumOf(form.BuriedDate);
				}	// form already includes buried date input field
				break;
		    }	// buried event
    
		    case STYPE_LDSB:		// LDS Baptism
		    {
				if (form.BaptismDate)
				{	// form already includes LDS baptism date input field
				    rownum	= getRowNumOf(form.BaptismDate);
				}	// form already includes LDS baptism date input field
				break;
		    }	// LDS baptism event
    
		    case STYPE_LDSE:		// LDS Endowment
		    {
				if (form.EndowmentDate)
				{	// form already includes LDS endowment date input field
				    rownum	= getRowNumOf(form.EndowmentDate);
				}	// form already includes LDS endowment date input field
				break;
		    }	// LDS endowment event
    
		    case STYPE_LDSC:		// LDS Confirmation
		    {
				if (form.ConfirmationDate)
				{	// form already includes LDS confirmation date
				    rownum	= getRowNumOf(form.ConfirmationDate);
				}	// form already includes LDS confirmation date
				break;
		    }	// LDS confirmation event
    
		    case STYPE_LDSI:	// LDS Initiatory
		    {
				if (form.InitiatoryDate)
				{	// form already includes LDS initiatory date
				    rownum	= getRowNumOf(form.InitiatoryDate);
				}	// form already includes LDS initiatory date
				break;
		    }	// LDS initiatory event
		}		// row number set on type of event

		if (rownum === '')
		{		// still not set, add new row
		    rownum		= 1;
		    var eventBody	= document.getElementById('EventBody');
		    for(var ic = 0; ic < eventBody.childNodes.length; ic++)
		    {		// loop through "rows" of event "table"
				var rowChild	= eventBody.childNodes[ic];
				if (rowChild.nodeName.toLowerCase() == 'div')
				{		// a "row" of the event "table"
				    rownum++;
				}		// a "row" of the event "table"
		    }		// loop through "rows" of event "table"
		}		// still not set
    }			// row num not supplied by caller
    parms.rownum	= rownum;

    // ensure sort date key is set in event
    if (datesd === null)
    {			// set default date for sort
		var dateObj	= new Date(date);
		datesd		= dateObj.getFullYear() * 10000 +
						  (dateObj.getMonth() + 1) * 100 +
						  dateObj.getDate();
		parms.datesd	= datesd
    }			// set default date for sort

    // get a human interpretation of the event type
    var	typeText	= 'Unknown ' + idet;
    var	eventTextElt	= document.getElementById('EventText' + idet);
    if (eventTextElt)
    {				// have element from web page
		typeText	= eventTextElt.innerHTML.trim() + ':';
		typeText	= typeText.substring(0,1).toUpperCase() +
								  typeText.substring(1);
    }				// have element from web page

    // update existing row or create new row
    // the name of the row cannot always be determined from the row number
    
    var eventButton	= document.getElementById('EventDetail' + rownum);
    var eventRow	= null;
    if (!eventButton)
    {			// add new row
		// find a place to insert the new row after the last event
		// that is not death or buried
		// although the current implementation in editIndivid.php
		// always displays the death fact, it is a customizable
		// option to display it only if it is known
		var table	= document.getElementById("EventBody");
		var nextRow	= null;

		searchForPosition:
		for(var row = table.firstChild; row; row = row.nextSibling)
		{		                // loop through events
		    if (row.nodeName.toLowerCase() == 'div')
		    {		            // an event
				for (var elt = row.firstChild; elt; elt = elt.nextSibling)
				{               // loop through children of div
				    if (elt.nodeName.toLowerCase() == 'input')
				    {
						if (elt.name.substring(0,7) == 'EventSD')
						{
						    if (elt.value > datesd)
						    {
							    nextRow		= row;
							    break searchForPosition;
						    }   // sort date greater than new event
						}       // name="EventSD..."
				    }           // <input> element
				}               // loop through children of div
		    }		            // an event
		}		                // loop through events

		if ('etype' in parms)
		{			// have title for new row
		    // fill in default values for undefined parms
		    if (!('cittype' in parms))
				parms.cittype		= 0;
		    if (!('ider' in parms))
				parms.ider		    = 0;
		    if (!('date' in parms))
				parms.date		    = '';
		    if (!('description' in parms))
				parms.description	= '';
            parms['descn']          = parms['description'];
		    if (!('location' in parms))
				parms['location']	= '';
            parms.locationname      = parms['location'];
		    if (!('idet' in parms))
				parms.idet		    = 0;
            parms.preferredchecked  = '';
            parms.changed           = 0;
            var type                = eventText[parms.idet];
            type.charAt(0).toUpperCase() + type.slice(1)
            parms.type              = type;

		    var	msg	= "{";
		    var	comma	= "";
		    for(key in parms)
		    {
				msg		+= comma + key + "='" + parms[key] + "'";
				comma	= ", ";
		    }
		    // create and insert the new event into the form
		    var newRow	= createFromTemplate("EventRow$rownum",
								             parms,
								             null);
            var elements    = [];
            var inputs      = newRow.getElementsByTagName('input');
            for (ii = 0; ii < inputs.length; ii++)
                elements.push(inputs[ii]);
            var buttons     = newRow.getElementsByTagName('button');
            for (ib = 0; ib < buttons.length; ib++)
                elements.push(buttons[ib]);
            var temp        = {'elements' : elements};  // simulate a form
            activateElements(temp);

		    if (nextRow)
		    {			// insert before next event
				eventRow	= table.insertBefore(newRow, nextRow);
				var order	= (parms.order - 0) + 2;

				// increment order field of following events
				while(nextRow)
				{
				    if (nextRow.nodeName.toLowerCase() == 'div')
				    {		// next row of event section
						var	children	= nextRow.getElementsByTagName('input');
						for (var ic = 0; ic < children.length; ic++)
						{	// loop through children
						    var child	= children[ic];
						    var	name	= child.name;
						    if (name === undefined)
							    name	= child.id;
						    if (name === undefined)
							    name	= '';
						    if (name.substring(0,10) == 'EventOrder')
						    {
							    child.value	= order;
							    order++;
						    }
						    else
						    if (name.substring(0,12) == 'EventChanged')
							    child.value	= 1;
						}	// loop through children
				    }		// next row of event section
				    nextRow	= nextRow.nextSibling;
				}
		    }			// insert before next event
		    else		// add to end of event table
				eventRow	= table.appendChild(newRow);
		}			// have title for new row
    }				// add new row
    else			// update existing row
		eventRow		= eventButton.parentNode;

    // identify fields in the row
    var	detailButton	= null;
    var	deleteButton	= null;
    var	dateElt		    = null;
    var	sdElt		    = null;
    var	descnElt	    = null;
    var	locationElt	    = null;
    var	labelElt	    = null;

    var dateTrace       = '';
    // activate dynamic functionality and update values of
    // fields based upon returned parms
    var	children	= eventRow.getElementsByTagName('input');
    for (var ic = 0; ic < children.length; ic++)
    {				// loop through children
		var	child		= children[ic];
		var	name		= child.id;
		if (name === undefined)
		    name		= child.name;
        var id          = '';
		var	namePatt	= /^([a-zA-Z]+)([0-9]*)/;
		var	result		= namePatt.exec(name);
		if (result)
        {
		    name		= result[1];
            id          = result[2];
        }

        if (dateElt === null)
            dateTrace   += name + ' is null,';
        else
            dateTrace   += name + ' is not null,';
        try {
		switch(name.toLowerCase())
		{			// act on specific fields
		    case 'eventdetail':
		    {
				detailButton		    = child;
				detailButton.addEventListener('click', eventDetail);
				break;
		    }

		    case 'eventdelete':
		    {
				deleteButton		    = child;
				deleteButton.addEventListener('click', eventDelete);
				break;
		    }

		    case 'eventdate':
		    case 'birthdate':
		    case 'christeningdate':
		    case 'baptismdate':
		    case 'endowmentdate':
		    case 'confirmationdate':
		    case 'initiatorydate':
		    case 'deathdate':
		    case 'burieddate':
		    {
				dateElt			    = child;
				dateElt.value		= parms.date;
				dateElt.addEventListener('change', dateChanged);
				dateElt.addEventListener('change', eventChanged);
				dateElt.checkfunc	= checkDate;
				break;
		    }

		    case 'eventsd':
		    {
				sdElt			= child;
                if (dateElt  === null)
                {
                    alert("editIndivid.js: 4138 dateElt is null for " + name + ', trace=' + dateTrace);
                }
                else
                {
				var date		= datePatt.exec(dateElt.value);

				if (date === null)
				{
				    sdElt.value		= -99999999;
				}
				else
				{
				    sdElt.value		= (date[0]-0) * 10000 + 615;
				    sdElt.defaultvalue	= (date[0]-0) * 10000 + 615;
				}
				}
				break;
		    }

		    case 'eventdescn':
		    case 'birthdescn':
		    case 'chrisdescn':
		    case 'baptismdescn':
		    case 'endowmentdescn':
		    case 'confirmationdescn':
		    case 'initiatorydescn':
		    case 'deathdescn':
		    case 'burieddescn':
		    {
				descnElt		= child;
				descnElt.addEventListener('change', eventChanged);
				descnElt.checkfunc	= checkText;
				descnElt.value		= parms.description;
				break;
		    }

		    case 'eventlocation':
		    case 'birthlocation':
		    case 'christeninglocation':
		    case 'baptismtemple':
		    case 'endowmenttemple':
		    case 'confirmationtemple':
		    case 'initiatorytemple':
		    case 'deathlocation':
		    case 'buriedlocation':
		    {
				locationElt		        = child;
 		        locationElt.abbrTbl	    = evtLocAbbrs;
				locationElt.addEventListener('change', locationChanged);
				locationElt.addEventListener('change', eventChanged);
				locationElt.value	    = parms.location;
				break;
		    }

		    case 'eventlabel':
		    {
				labelElt		        = child;
				labelElt.innerHTML	    = typeText;
				break;
		    }

		}			// act on specific fields
        }
        catch (e)
            {
		alert("editIndivi.js: 4195 " + ex + ", trace=" + dateTrace);
        }
    }				// loop through children

    // update other field values in the current dialog based upon values
    // returned from the editEvent.php dialog
    switch(type)
    {		// source fields changed depend on type of event
		case STYPE_UNSPECIFIED:
		{
		    break;
		}

		case STYPE_NAME:
		{	// name event
		    form.Surname.value			= parms.surname;
		    form.GivenName.value		= parms.givenName;
		    break;
		}	// name event

		case STYPE_BIRTH:
		{
		    if (form.BirthDate)
		    {	// form already includes birth date input field
				form.BirthDate.value		= parms.date;
				form.BirthLocation.value	= parms.location;
		    }	// form already includes birth date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;
		    }	// refresh to add the event fields
		    break;
		}	// birth event

		case STYPE_CHRISTEN:
		{
		    if (form.ChristeningDate)
		    {	// form already includes christening date input field
				form.ChristeningDate.value		= parms.date;
				form.ChristeningLocation.value	= parms.location;
		    }	// form already includes christening date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// christening event

		case STYPE_DEATH:
		{
		    if (form.DeathDate)
		    {	// form already includes death date input field
				form.DeathDate.value		= parms.date;
				form.DeathLocation.value	= parms.location;
		    }	// form already includes death date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// death event

		case STYPE_BURIED:
		{
		    if (form.BuriedDate)
		    {	// form already includes buried date input field
				form.BuriedDate.value		= parms.date;
				form.BuriedLocation.value	= parms.location;
		    }	// form already includes buried date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// buried event

		case STYPE_LDSB:		// LDS Baptism
		{
		    if (form.BaptismDate)
		    {	// form already includes LDS baptism date input field
				form.BaptismDate.value		= parms.date;
				form.BaptismLocation.value	= parms.location;
		    }	// form already includes LDS baptism date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// LDS baptism event

		case STYPE_LDSE:		// LDS Endowment
		{
		    if (form.EndowmentDate)
		    {	// form already includes LDS endowment date input field
				form.EndowmentDate.value	= parms.date;
				form.EndowmentLocation.value	= parms.location;
		    }	// form already includes LDS endowment date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// LDS endowment event

		case STYPE_LDSC:		// LDS Confirmation
		{
		    if (form.ConfirmationDate)
		    {	// form already includes LDS confirmation date input field
				form.ConfirmationDate.value	= parms.date;
				form.ConfirmationLocation.value	= parms.location;
		    }	// form already includes LDS confirmation date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;;
		    }	// refresh to add the event fields
		    break;
		}	// LDS confirmation event

		case STYPE_LDSI:	// LDS Initiatory
		{
		    if (form.InitiatoryDate)
		    {	// form already includes LDS initiatory date input field
				form.InitiatoryDate.value	= parms.date;
				form.InitiatoryLocation.value	= parms.location;
		    }	// form already includes LDS initiatory date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;
		    }	// refresh to add the event fields
		    break;
		}	// LDS initiatory event

		case STYPE_LDSP:	// LDS Sealed to Parents
		{
		    if (form.SealingDate)
		    {	// form already includes LDS initiatory date input field
				form.SealingDate.value		= parms.date;
				if (parms.location)
				    form.SealingTemple.value	= parms.location;
		    }	// form already includes LDS initiatory date input field
		    else
		    {	// refresh to add the event fields
				location.search	= newSearch;
		    }	// refresh to add the event fields
		    break;
		}	// LDS initiatory event

		case STYPE_DEATHCAUSE:		// cause of death
		{
		    if (form.DeathCause)
		    {
				form.DeathCause.value	= parms.note;
		    }
		    break;
		}	// death cause fact

		case STYPE_NOTESGENERAL:	// general notes
		case STYPE_NOTESRESEARCH:	// research notes
		case STYPE_NOTESMEDICAL:	// medical notes
		{	// no special handling yet
		    // not displayed in this dialog
		    break;
		}	// no special handling yet

		case STYPE_EVENT:		// individual event
		{	// individual event
		    if (typeText)
				parms.idet	= typeText;

		    if (preferred)
		    {				// preferred event
				switch(parseInt(idet))
				{			// act on event type
				    case ET_BIRTH:
				    {
						if (form.BirthDate)
						{	// form includes birth date input field
						    form.BirthDate.value	= parms.date;
						    form.BirthLocation.value	= parms.location;
						    eventRow			= 0;
						}	// form includes birth date input field
						break;
				    }	// birth event

				    case ET_BURIAL:
				    {
						if (form.BuriedDate)
						{	// form includes buried date input field
						    form.BuriedDate.value	= parms.date;
						    form.BuriedLocation.value	= parms.location;
						    eventRow			= 0;
						}	// form includes buried date input field
						break;
				    }	// buried event

				    case ET_CHRISTENING:
				    {
						if (form.ChristeningDate)
						{	// form includes christening date input field
						    form.ChristeningDate.value	= parms.date;
						    form.ChristeningLocation.value	= parms.location;
						    eventRow			= 0;
						}	// form includes christening date input field
						break;
				    }	// christening event

				    case ET_DEATH:
				    {
						if (form.DeathDate)
						{	// form includes death date input field
						    form.DeathDate.value	= parms.date;
						    form.DeathLocation.value	= parms.location;
						    eventRow			= 0;
						}	// form includes death date input field
						break;
				    }	// death event

				    case ET_LDS_BAPTISM:
				    {
						if (form.BaptismDate)
						{	// form includes LDS Baptism date input field
						    form.BaptismDate.value	= parms.date;
						    form.BaptismLocation.value	= parms.location;
						    eventRow			= 0;
						}	// form includes LDS Baptism date input field
						break;
				    }	// LDS Baptism event

				    case ET_LDS_CONFIRMATION:
				    {
						if (form.ConfirmationDate)
						{	// form includes Confirmation date input field
						    form.ConfirmationDate.value	= parms.date;
						    form.ConfirmationLocation.value= parms.location;
						    eventRow			= 0;
						}	// form includes Confirmation date input field
						break;
				    }	// LDS Confirmation event

				    case ET_LDS_ENDOWED:
				    {
						if (form.EndowmentDate)
						{	// form includes endowment date input field
						    form.EndowmentDate.value	= parms.date;
						    form.EndowmentLocation.value= parms.location;
						    eventRow			= 0;
						}	// form includes endowment date input field
						break;
				    }	// endowment event

				    case ET_LDS_INITIATORY:
				    {
						if (form.InitiatoryDate)
						{	// form includes initiatory date input field
						    form.InitiatoryDate.value	= parms.date;
						    form.InitiatoryLocation.value= parms.location;
						    eventRow			= 0;
						}	// form includes initiatory date input field
						break;
				    }	// initiatory event

				}			// act on event type
		    }				// preferred event
		    break;
		}	// individual event

    }		// source fields to refresh depend on type

    // reenable the Order Events by Date button
    document.getElementById('Order').disabled	= false;
}		// function eventFeedback

/************************************************************************
 *  function fldKeyDown													*
 *																		*
 *  Handle key strokes in text fields that represent values held in the	*
 *  main record, and that therefore require that the form be submitted.	*
 *  The submit button is therefore enabled.								*
 *																		*
 *  Parameters:															*
 *		this	    <input type='text'>									*
 *		ev			keydown Event	                                    *
 ************************************************************************/
function fldKeyDown(ev)
{
    if (!ev)
    {		// browser is not W3C compliant
		ev	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	form	= document.indForm;
    if (ev.ctrlKey || ev.altKey)
		return true;
    var	code	= ev.keyCode;
    if (ev.key)
		code	= ev.key;
    return this.oldkeydown(ev);		// pass to common handling
}		// function fldKeyDown

/************************************************************************
 *  function eiKeyDown													*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.  For		*
 *  example the key combinations Ctrl-S and Alt-U are interpreted to	*
 *  apply the update, as shortcut alternatives to using the mouse to 	*
 *  click the Update Person button.										*
 *																		*
 *  Parameters:															*
 *      this        <input> element                                     *
 *		ev		    a keydown Event		                                *
 ************************************************************************/
function eiKeyDown(ev)
{
    if (!ev)
    {		    // browser is not W3C compliant
		ev	        =  window.event;	// IE
    }		    // browser is not W3C compliant
    var	code	    = ev.keyCode;
    var	form	    = document.indForm;
    var	idir	    = form.idir.value;

    // take action based upon code
    if (ev.ctrlKey)
    {		    // ctrl key shortcuts
		if (code == LTR_S)
		{		// letter 'S'
		    locTrace += " editIndivid.js: eiKeyDown: Ctrl-S";
		    validateForm();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }		    // ctrl key shortcuts

    if (ev.altKey)
    {		    // alt key shortcuts
		switch (code)
		{
		    case LTR_A:
		    {		// letter 'A' edit address
				document.getElementById("Address").click();
				return false;	// suppress default action
		    }		// letter 'A'

		    case LTR_C:
		    {		// letter 'C' census search
				document.getElementById("censusSearch").click();
				return false;	// suppress default action
		    }		// letter 'C'

		    case LTR_D:
		    {		// letter 'D' delete
				document.getElementById("Delete").click();
				return false;	// suppress default action
		    }		// letter 'D'

		    case LTR_E:
		    {		// letter 'E' add event
				document.getElementById("AddEvent").click();
				return false;	// suppress default action
		    }		// letter 'E'

		    case LTR_F:
		    {		// letter 'F' edit families
				var marriagesButton   = document.getElementById("Marriages");
                if (marriagesButton)
				    marriagesButton.click();
				return false;	// suppress default action
		    }		// letter 'F'

		    case LTR_I:
		    {		// letter 'I' edit pictures
				document.getElementById("Pictures").click();
				return false;	// suppress default action
		    }		// letter 'G'

		    case LTR_M:
		    {		// letter 'M' merge button
				document.getElementById("Merge").click();
				return false;	// suppress default action
		    }		// letter 'M'

		    case LTR_N:
		    {		// letter 'N' general notes
				document.getElementById("Detail6").click();
				return false;	// suppress default action
		    }		// letter 'N'

		    case LTR_O:
		    {		// alt-O
				document.getElementById("Order").click();
				return false;	// suppress default action
		    }		// alt-O

		    case LTR_P:
		    {		// letter 'P' edit parents
				var parentsButton   = document.getElementById("Parents");
                if (parentsButton)
				    parentsButton.click();
				return false;	// suppress default action
		    }		// letter 'P'

		    case LTR_R:
		    {		// letter 'R' research notes
				document.getElementById("Detail7").click();
				return false;	// suppress default action
		    }		// letter 'R'

		    case LTR_S:
		    {		// letter 'S' search tables
				document.getElementById("Search").click();
				return false;	// suppress default action
		    }		// letter 'S'

		    case LTR_U:
		    {		// letter 'U' Update
				locTrace += " editIndivid.js: eiKeyDown: Alt-U";
				validateForm();
				return false;	// suppress default action
		    }		// letter 'U'

		    case LTR_V:
		    {		// letter 'V' vital statistics search tables
				document.getElementById("bmdSearch").click();
				return false;	// suppress default action
		    }		// letter 'V'

		    case LTR_Y:
		    {		// letter 'Y' ancestry.ca search
				document.getElementById("ancestrySearch").click();
				return false;	// suppress default action
		    }		// letter 'Y'

		}	    // switch on key code
    }		    // alt key shortcuts

    return true;	// do default action
}		// function eiKeyDown
