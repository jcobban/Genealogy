/************************************************************************
 *  editMarriages.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editMarriages.php.												*
 *																		*
 *  History of editMarriages.js before merger of editMarriage.js:		*
 *		2010/11/08		improve error handling on delete marriage		*
 *		2010/12/04		improve separation of HTML & JS					*
 *		2010/12/26		add callback method marriageUpdated to			*
 *						receive notification from script				*
 *						editMarriage.php								*
 *		2011/02/21		do not fail if calling page has already closed	*
 *		2011/03/03		support keyboard shortcuts:						*
 *						ctrl-S and alt-C for close						*
 *						alt-A for add marriage							*
 *		2011/04/10		reduce size of window for editMarriage.php		*
 *																		*
 *  History of editMarriage.js as a separate file:						*
 *		2010/08/10		Change to use POST for invoking update scripts	*
 *		2010/08/13		Add function to create new spouse.				*
 *		2010/08/13		Add function to create new child.				*
 *		2010/08/13		Add function to detach existing child.			*
 *		2010/08/19		Correct invocation of editIndivid				*
 *		2010/08/21		Add function to reorder children by birth date.	*
 *		2010/08/27		Update page in detail on reorder rather than	*
 *						refresh, which wiped out changes made to other	*
 *						fields											*
 *		2010/08/28		Use getAttribute to get value of attribute of	*
 *						XML element for portability						*
 *		2010/08/29		protect in case opener is terminated before		*
 *						this script is closed.							*
 *		2010/09/04		Use chooseIndivid.php for existing individuals	*
 *		2010/09/20		Do not overlay existing windows for				*
 *						editIndivid.php		to permit creating multiple	*
 *						generations of parents without interruption		*
 *		2010/10/16		implement new format for maintaining citations	*
 *		2010/10/17		citation support moved to citTable.js			*
 *		2010/10/21		parameter removed from onclick methods of		*
 *						buttons											*
 *						use method=post to invoke updateMarriage.php	*
 *		2010/10/29		report explicit error message from				*
 *						detChildXml.php									*
 *						simplify and correct removal of child from		*
 *						web page										*
 *		2010/10/30		correct formatting of children after reordering	*
 *						add support for updating marriage status		*
 *		2010/11/11		set names of row and buttons on added child row	*
 *						so edit and detach child buttons work.			*
 *		2010/11/14		do not pass idir parameter to chooseIndivid		*
 *		2010/12/16		add method to permit editIndivid dialog to		*
 *						update the table of children in this page		*
 *		2010/12/20		add detach spouse button						*
 *		2010/12/26		more object-oriented approach to notifying		*
 *						invoking page of the updated marriage by		*
 *						invoking a call-back method of the invoking		*
 *						document.										*
 *		2011/01/13		check before calling function from invoking page*
 *		2011/02/06		standardize callbacks from editIndivid.php and	*
 *						chooseIndivid.php								*
 *		2011/02/21		fix callback calling sequence					*
 *						addChildToPage updates database to add child	*
 *		2011/02/23		new callbacks setNewHusb and setNewWife with	*
 *						same parameters as changeChild & addChildToPage	*
 *		2011/02/26		initialize father's surname when invoking		*
 *						chooseIndivid.php to select existing child		*
 *		2011/03/03		row id not retrieved							*
 *		2011/03/07		alert on error message from addChildXml			*
 *		2011/03/19		add keyboard shortcuts							*
 *		2011/03/25		set the initial focus on the "Update" button	*
 *						so pressing Enter closes the dialog, and the	*
 *						keyboard shortcuts work							*
 *		2011/04/22		syntax error on IE7								*
 *		2011/05/29		support button for editting pictures			*
 *		2011/06/09		support button for editting events				*
 *																		*
 *  History of merged files:											*
 *		2011/06/11		functionality of editMarriage.js merged			*
 *		2011/06/24		add gender parameter to changeChild callback	*
 *		2011/07/14		enlarge popup windows							*
 *		2011/07/29		explicitly pass updated values of date and		*
 *						location to editEvent.php						*
 *		2011/08/21		always open editIndivid.php in a new window		*
 *		2011/08/22		if the individual has no existing families		*
 *						display the menu to create the first family		*
 *		2011/09/18		add try/catch on set onclick					*
 *		2011/10/01		support database assisted location name			*
 *		2011/11/15		add keyword idmr to initiate edit of			*
 *						pre-selected family.							*
 *						Add buttons for editing Husband and Wife		*
 *						as individuals									*
 *		2011/11/26		Support editing married surnames				*
 *		2012/01/07		explicitly pass parents names to child creation	*
 *		2012/01/11		do not use refresh to update the list of		*
 *						marriages because that causes the displayed		*
 *						marriage to be refreshed as well, removing any	*
 *						changes made since the last write to the		*
 *						database.										*
 *						If the name of the husband is changed, change	*
 *						the now	exposed married surnames of the husband	*
 *						and, if	required by the marriage name rule,		*
 *						set the wife									*
 *		2012/01/13		change class names								*
 *						most functionality moved to commonMarriage.js	*
 *		2012/04/21		align child windows with main window			*
 *		2012/10/13		enable edit buttons for spouses					*
 *		2012/11/18		change edit and delete event buttons so the		*
 *						name of the button contains the event type for	*
 *						internal events and the IDER for Event			*
 *						instances										*
 *		2013/01/17		correct comments								*
 *		2013/03/11		changeLocation renamed to locationChanged		*
 *		2013/04/02		blank out given name on detach spouse			*
 *		2013/05/20		use common function actMouseOverHelp			*
 *						add support for details on not married and		*
 *						no children facts								*
 *		2013/06/11		set explicit gender for new husband				*
 *						request feedback on edit of husband or wife		*
 *		2013/08/30		resize dialog window if necessary				*
 *		2013/10/15		if the user modifies the name of the husband or	*
 *						wife before clicking on the "Edit" button, pass	*
 *						the updated name								*
 *		2013/10/25		use encodeURIComponent on initial names			*
 *		2013/11/26		include supplied name elements when searching	*
 *						for a husband or wife to attach					*
 *		2013/12/12		use birth date of husband or wife to limit		*
 *						potential spouse matches						*
 *		2013/12/31		clean up comment blocks							*
 *		2014/02/27		do not submit form if a modal dialog is shown	*
 *						feedback method for editIndivid to update		*
 *						a child is made a method of the row containing	*
 *						the child, rather than the table of children	*
 *						Consolidate support for feedback from			*
 *						editIndivid.php by using the same style of		*
 *						feedback routine for any individual in the		*
 *						family											*
 *						validate and expand abbreviations in dates		*
 *		2014/03/18		support <fieldset>								*
 *		2014/07/16		better support for checking for open child		*
 *						windows											*
 *		2014/07/19		if not opened as a dialog go back to previous	*
 *						page instead of closing the window				*
 *		2014/10/10		positioning of windows moved to commonMarriage	*
 *						change married surnames if husb surname changes	*
 *		2014/11/15		split element name into text and numeric parts	*
 *						to improve handling of elements in tables		*
 *						<button id='OrderEvents'> was not set up		*
 *		2014/11/16		rename function marrAdd to addFamily			*
 *		2014/11/28		enable onchange for child birth dates			*
 *		2015/02/02		disable and enable edit buttons when editing	*
 *						family member in <iframe>						*
 *		2015/02/10		open all child dialogs in left hand frame		*
 *		2015/02/23		track open windows for spouse or child to		*
 *						prevent updating family while open				*
 *		2015/02/26		do not overwrite child when editing husb or wife*
 *		2015/02/28		set checkfunc for child birth and death			*
 *		2015/06/08		disable edit child buttons on createHusb and	*
 *						createWife										*
 *						invoke create if editHusb/editWife and			*
 *						spouse is not yet defined						*
 *		2015/06/19		enter key on input fields in child description	*
 *						adds another child								*
 *		2015/08/12		add support for tree divisions of database		*
 *		2016/02/06		call pageInit									*
 *		2016/05/31		use common function dateChanged					*
 *		2017/07/22		add month abbreviation table to child birth		*
 *						and death fields								*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/19      call element.click to trigger button click      *
 *		2019/07/20      insert spaces into death date                   *
 *		2019/11/15      pass requested language to child dialogs        *
 *		2020/02/17      hide right column                               *
 *		                missing initGender from editIndivid requests    *
 *		2020/08/26      correct initGender in editHusb                  *
 *		2022/01/19      ensure when displaying edit for wife that       *
 *		                the surname is not empty                        *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/

window.onload	= loadEdit;

/************************************************************************
 *  function loadEdit													*
 *																		*
 *  Initialize elements once the page is loaded into the browser.		*
 *																		*
 *  Input:																*
 *		this			window											*
 ************************************************************************/
function loadEdit()
{
    // the edit button for the preferred marriage
    var	editPref	            = null;
    var	idmrNotSet	            = true;

    if ('idmr' in args)
    {			        // idmr parameter passed
		editPref	    = document.indForm.elements['Edit' + args.idmr];
		if (editPref)
		{
		    idmrNotSet	        = false;
		}
    }			        // idmr parameter passed

    // handle keystrokes that apply to the entire dialog
    document.body.onkeydown	    = emKeyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var ignoredNames    = '';
    var sep             = '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		            // loop through all forms
		var form		        = document.forms[fi];
		form.onsubmit		    = validateForm;
		form.onreset 		    = resetForm;
		if (form.name == 'famForm')
		{	            // individual marriage form
		    // callback from editEvent
		    form.eventFeedback	= eventFeedback;
		}	            // individual marriage form

		// act on elements within the form
		var formElts	        = form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{			    // loop through all elements
		    var element	        = formElts[i];

		    if (element.nodeName.toUpperCase() == 'FIELDSET')
				continue;

		    var	name;
		    if (element.name && element.name.length > 0)
				name	= element.name;
		    else
				name	= element.id;
            if (name.length == 0)
                continue;

		    // default handling of <input> elements
		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handler

		    // do element specific initialization
		    var namePattern	= /^([a-zA-Z$_]+)([0-9]*)$/;
		    var pieces		= namePattern.exec(name);
		    if (pieces === null)
		    {
				alert("editMarriages.js: onLoad: unable to parse name='" +
					name + "' element=" + element.outerHTML);
				pieces	= [name, name, ''];
		    }
		    var	colName		= pieces[1];
		    var rowId		= pieces[2];
		    var idir		= document.getElementById("idir").value - 0;
		    var idirHusb	= document.getElementById("IDIRHusb").value - 0;
		    var idirWife	= document.getElementById("IDIRWife").value - 0;

		    switch(colName)
		    {			// act on specific field name
				case 'Add':
				{		// add a family
				    element.onclick	= addFamily;
				    break;
				}		// Add
    
				case 'Finish':
				{		// close window
				    element.onclick	        = finish;
				    // put the initial keyboard focus
                    // on the "Close" button so
				    // pressing Enter closes the dialog
				    element.focus();
				    break;
				}		// close window
    
				case 'Reorder':
				{		// reorder marriages by date
				    element.onclick		    = marrReorder;
				    break;
				}		// reorder marriages by date

				case 'HusbSurname':
				{		// husband surname field
				    element.checkfunc		= checkName;
				    element.onchange		= changeHusbSurname;
				    break;
				}		// husband surname field

				case 'WifeSurname':
				{		// wife surname field
				    element.checkfunc		= checkName;
				    element.onchange		= change;
				    break;
				}		// wife surname field

				case 'HusbGivenName':
				case 'WifeGivenName':
				{		// given name fields
				    element.checkfunc		= checkName;
				    element.onchange		= change;
				    break;
				}		// given name fields

				case 'MarD':
				{		// marriage date
				    element.abbrTbl		    = MonthAbbrs;
				    element.onchange		= dateChanged;
				    element.checkfunc		= checkDate;
				    break;
				}		// marriage date

				case 'MarLoc':
				{		// marriage location
				    element.abbrTbl		    = evtLocAbbrs;
				    element.onchange		= locationChanged;
				    break;
				}		// marriage location

				case 'editHusb':
				{		// open dialog to edit Husband
				    if (idir == idirHusb)
					    element.disabled	= true;
				    else
					    editChildButtons.push(element);
				    element.onclick		    = editHusb;
				    break;
				}		// open dialog to edit Husband

				case 'chooseHusb':
				{		// open dialog to choose husband
				    if (idir == idirHusb)
					    element.disabled	= true;
				    else
					    editChildButtons.push(element);
				    element.onclick		= chooseHusb;
				    break;
				}		// open dialog to choose husband

				case 'createHusb':
				{		// open dialog to create new husband
				    if (idir == idirHusb)
					    element.disabled	= true;
				    else
					    editChildButtons.push(element);
				    element.onclick		= createHusb;
				    break;
				}		// open dialog to create new husband

				case 'detachHusb':
				{		// detach husband
				    if (idir == idirHusb)
					    element.disabled	= true;
				    else
					    editChildButtons.push(element);
				    element.onclick		= detachHusb;
				    break;
				}		// detach husband

				case 'editWife':
				{		// open dialog to edit Husband
				    if (idir == idirWife)
					    element.disabled	= true;
				    else
					    editChildButtons.push(element);
				    element.onclick		= editWife;
				    break;
				}		// open dialog to edit Husband

				case 'chooseWife':
				{		// open dialog to choose wife
				    if (idir == idirWife)
					element.disabled	= true;
				    else
					editChildButtons.push(element);
				    element.onclick		= chooseWife;
				    break;
				}		// open dialog to choose wife

				case 'createWife':
				{		// open dialog to create new wife
				    if (idir == idirWife)
					element.disabled	= true;
				    else
					editChildButtons.push(element);
				    element.onclick		= createWife;
				    break;
				}		// open dialog to create new wife

				case 'detachWife':
				{		// detach wife
				    if (idir == idirWife)
					element.disabled	= true;
				    else
					editChildButtons.push(element);
				    element.onclick		= detachWife;
				    break;
				}		// detach wife

				case 'noteDetails':
				{		// open dialog to edit marriage notes
				    element.onclick		= noteDetails;
				    break;
				}		// open dialog to edit marriage notes

				case 'noChildrenDetails':
				{
				    element.onclick		= noChildrenDetails;
				    break;
				}

				case 'neverMarriedDetails':
				{
				    element.onclick		= neverMarriedDetails;
				    break;
				}

				case 'eventList':
				{
				    element.onchange	= changeEventList;
				    break;
				}

				case 'OrderEvents':
				{		// order events by date
				    element.onclick		= orderEvents;
				    break;
				}		// order events by date

				case 'addChild':
				{		// choose an existing child
				    element.onclick		= addExistingChild;
				    editChildButtons.push(element);
				    break;
				}		// choose an existing child

				case 'addNewChild':
				{		// create a new child
				    element.onclick		= addNewChild;
				    editChildButtons.push(element);
				    break;
				}		// create a new child

				case 'update':
				{		// apply changes to database
				    element.onclick		= updateMarr;
				    break;
				}		// apply changes to database

				case 'orderChildren':
				{
				    element.onclick		= orderChildren;
				    editChildButtons.push(element);
				    break;
				}

				case 'Pictures':
				{
				    element.onclick		= editPictures;
				    break;
				}

				case 'MarriedNameRule':
				{
				    element.onchange		= changeNameRule;
				    break;
				}

				case 'EditIEvent':
				{
				    // rowId is STYPE
				    element.onclick		= editIEvent;
				    break;
				}

				case 'DelIEvent':
				{
				    // rowId is STYPE
				    element.onclick		= delIEvent;
				    break;
				}

				case 'EditEvent':
				{
				    // rowId is IDER
				    element.onclick		= editEvent;
				    break;
				}

				case 'DelEvent':
				{
				    // rowId is IDER
				    element.onclick		= delEvent;
				    break;
				}

				case 'CGiven':
				{		// given names of a child
				    element.onkeydown		= childKeyDown;
				    break;
				}		// given names of a child

				case 'CSurname':
				{		// surname of a child
				    element.onkeydown		= childKeyDown;
				    break;
				}		// surname of a child

				case 'Cbirth':
				{		// birth date of a child
				    element.abbrTbl		    = MonthAbbrs;
				    element.onkeydown		= childKeyDown;
				    element.onchange		= changeCBirth;
				    element.checkfunc		= checkDate;
				    element.checkfunc();
				    break;
				}		// birth date of a child

				case 'Cdeath':
				{		// death date of a child
				    element.abbrTbl		    = MonthAbbrs;
				    element.onkeydown		= childKeyDown;
				    element.onchange		= changeCDeath;
				    element.checkfunc		= checkDate;
				    element.checkfunc();
				    break;
				}		// death date of a child

				case 'editChild':
				{		// edit details of a child
				    editChildButtons.push(element);
				    element.onclick		= editChild;
				    break;
				}		// edit details of a child

				case 'detChild':
				{		// detach Child
				    element.onclick		= detChild;
				    break;
				}		// detach Child

				case 'Pref':
				{		// set preferred family
				    // rowId is IDMR
				    element.onclick		= clickPref;
				    break;
				}		// set preferred family

				case 'Edit':
				{		// edit family
				    // rowId is IDMR
				    element.onclick		= editFamily;
				    var prefName		= 'Pref' + rowId;
				    var prefbox			= form.elements[prefName];
				    if (editPref === null ||
					(idmrNotSet && prefbox.checked))
					editPref		= element;
				    break;
				}		// edit family

				case 'Delete':
				{		// delete family
				    // rowId is IDMR
				    element.onclick		= marrDel;
				    break;
				}		// Delete family

                default:
                {
                    ignoredNames    += sep + element.name;
                    sep             = ', ';
                }
		    }			// switch on element name
		}			    // loop through all elements in the form
    }				    // loop through all forms

    if (ignoredNames.length > 0)
        console.log("editMarriages.js: ignored=" + ignoredNames);
    // provide methods for other pages to modify information on husband
    // and wife
    var	husbRow			= document.getElementById('Husb');
    husbRow.changePerson	= changeHusb;
    var	wifeRow			= document.getElementById('Wife');
    wifeRow.changePerson	= changeWife;

    // provide methods for other pages to add a child onto this page
    // and to change an existing child
    var	childTable	= document.getElementById('children');
    childTable.addChildToPage	= addChildToPage;

    for(var subElement = childTable.firstChild;
		subElement;
		subElement = subElement.nextSibling)
    {
		if (subElement.nodeName)
		{
		    switch(subElement.nodeName.toLowerCase())
		    {		// act on node name
				case 'tr':
				case 'div':
				{	// logical row
				    subElement.changePerson	= changeChild;
				    break;
				}	// logical row

				case 'tbody':
				{	// using table for layout
				    for(var ir = 0; ir < subElement.rows.length; ir++)
					subElement.rows[ir].changePerson	= changeChild;
				    break;
				}	// using table for layout
		    }		// act on node name
		}		    // element has a nodeName attribute
    }			    // loop through all immediate children

    hideRightColumn();
}		// function loadEdit

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this				form object									*
 ************************************************************************/
function validateForm()
{
    // do not submit the update if there are open child edit windows
    // count the number of open child edit windows
    var	numOpenChildWindows	= 0;
    for(var i = 0; i < childWindows.length; i++)
    {		// loop through all edit child windows
		if (!(childWindows[i].closed))
		    numOpenChildWindows++;
    }		// loop through all edit child windows

    // if there are open child edit windows warn the user and skip save
    if (numOpenChildWindows > 0)
    {		// at least one child window still open
		popupAlert("editMarriages.js: validateForm: Warning: " + 
				   numOpenChildWindows + 
				   " subordinate edit windows are still open. ",
				   this);
		return false;
    }		// at least one child window still open
    else
		return true;
}		// function validateForm

/************************************************************************
 *  function resetForm														*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *																		*
 *  Input:																*
 *		this		<form> object										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// function resetForm

/************************************************************************
 *  function finish														*
 *																		*
 *  This method is called when the user requests to close				*
 *  the window.															*
 *																		*
 *  Input:																*
 *		this		<button id='Finish'>    							*
 *		e           click Event                                         *
 ************************************************************************/
function finish(e)
{
    if (window.opener)
    {			// invoked in a separate window
		window.close();
    }			// invoked in a separate window
    else
    if (window.frameElement)
    {			// displayed in a frame
		closeFrame();
    }			// displayed in a frame
    else
    {
		window.history.back();
    }
    return true;
}	// function finish

/************************************************************************
 *  function emKeyDown													*
 *																		*
 *  Handle key strokes that apply to the dialog as a whole.  For example*
 *  the key combinations Ctrl-S and Alt-C are interpreted to close the	*
 *  dialog, as shortcut alternatives to using the mouse to click the 	*
 *  Close button.														*
 *																		*
 *  Parameters:															*
 *		this		<body> object										*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function emKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.indForm;

    // take action based upon code
    switch (code)
    {
		case 65:
		{		// letter 'A'
		    if (e.altKey)
		    {		// alt-A
				form.Add.click();
				return false;	// do not perform standard action
		    }		// alt-A
		    break;
		}		// letter 'A'

		case 67:
		{		// letter 'C'
		    if (e.altKey)
		    {		// alt-C
				finish();
		    }		// alt-C
		    break;
		}		// letter 'C'


		case 79:
		{		// letter 'O'
		    if (e.altKey)
		    {		// alt-O
				form.Reorder.click();
				return false;	// do not perform standard action
		    }		// alt-O
		    break;
		}		// letter 'O'

		case 83:
		{		// letter 'S'
		    if (e.ctrlKey)
		    {		// ctrl-S
				finish();
				return false;	// do not perform standard action
		    }		// ctrl-S
		    break;
		}		// letter 'S'
    }	    // switch on key code

    return true;
}		// function emKeyDown

/************************************************************************
 *  function editHusb													*
 *																		*
 *  Prompt the user to edit details of the existing husband				*
 *  in the marriage.													*
 *																		*
 *  Input:																*
 *		this		<button id='editHusb'> element						*
 *		e           click Event                                         *
 ************************************************************************/
function editHusb(e)
{
    var	button				= this;
    var	form				= button.form;
    var idir				= form.IDIRHusb.value;
    var lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;

    if (idir > 0)
    {		// husband present
		for (var ib = 0; ib < editChildButtons.length; ib++)
		{			// disable all editChild buttons
		    editChildButtons[ib].disabled	= true;
		}			// disable all editChild buttons
		var script	        = "editIndivid.php?idir=" + idir +
                              "&rowid=Husb" +
		                      "&initGivenName=" +
                        encodeURIComponent(form.HusbGivenName.value) + 
		                      "&initSurname=" + 
                        encodeURIComponent(form.HusbSurname.value) +
		                      '&treeName=' + 
                        encodeURIComponent(form.treename.value) +
                              '&initGender=M' +
                              '&lang=' + lang;
		var childWindow	    = openFrame("husbFrame",
						                script,
						                "left");
		childWindows.push(childWindow);
    }		// husband present
    else
		document.getElementById('createHusb').click();
}		// function editHusb

/************************************************************************
 *  function chooseHusb													*
 *																		*
 *  Prompt the user to select an existing individual as husband			*
 *  in the marriage.													*
 *																		*
 *  Parameters:															*
 *		this		<button id='chooseHusb'> element				    *
 *		e           click Event                                         *
 ************************************************************************/
function chooseHusb(e)
{
    var lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;
    var	form	            = this.form;
    var	url	                = "chooseIndivid.php?id=Husb&name=" + 
				            encodeURIComponent(form.HusbSurname.value + ", " + 
						                       form.HusbGivenName.value);

    var	wifeBirthSD	        = form.WifeBirthSD;
    if (wifeBirthSD)
    {
		var	value	        = wifeBirthSD.value;
		if (value.length > 0 && value != '0' && value != '-99999999')
		{
		    var	birthYear	= Math.floor(value / 10000);
		    url		        += "&birthmin=" + (birthYear - 40) +
					           "&birthmax=" + (birthYear + 40);
		}
    }			// birth SD field present
    url		                += '&treeName=' + 
                                    encodeURIComponent(form.treename.value) +
                               '&lang=' + lang;

    var childWindow	        = openFrame("chooserFrame",
						                url,
						                "left");
}		// function chooseHusb

/************************************************************************
 *  function gotHusb													*
 *																		*
 *  This method is called when the XML file representing				*
 *  information on the selected husband is retrieved.					*
 *																		*
 *  Parameters:															*
 *		xmlDoc			response as an XML document						*
 ************************************************************************/
function gotHusb(xmlDoc)
{
    var	famForm	= document.famForm;

    // get values from the XML file
    var newIdir			= xmlDoc.getElementsByTagName("idir");
    if (newIdir === null)
		alert("editMarriages.js: gotHusb: newIdir is null");
    newIdir			= newIdir[0].textContent;
    famForm.IDIRHusb.value	= newIdir;
    document.getElementById('editHusb').disabled	= false;
    document.getElementById('chooseHusb').disabled	= false;
    document.getElementById('createHusb').disabled	= false;
    var newGiven		= xmlDoc.getElementsByTagName("givenname");
    if (newGiven === null)
		alert("editMarriages.js: gotHusb: newGiven is null");
    newGiven			= newGiven[0].textContent;
    famForm.HusbGivenName.value	= newGiven;
    var newSurname		= xmlDoc.getElementsByTagName("surname");
    if (newSurname === null)
		alert("editMarriages.js: gotHusb: newSurname is null");
    newSurname			= newSurname[0].textContent;
    famForm.HusbSurname.value	= newSurname;
}		// function gotHusb

/************************************************************************
 *  function noHusb														*
 *																		*
 *  This method is called if there is no husband file.					*
 ************************************************************************/
function noHusb()
{
    alert ('editMarriages.js: noHusb: Missing XML file response for selecting existing husband.');
}		// function noHusb

/************************************************************************
 *  function editWife													*
 *																		*
 *  Prompt the user to edit details of an existing wife					*
 *  in the marriage.													*
 *																		*
 *  Input:																*
 *		this		<button id='editWife'> element 						*
 ************************************************************************/
function editWife()
{
    let lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;
    let	button	            = this;
    let	form	            = button.form;
    let idir	            = form.IDIRWife.value;
    if (idir > 0)
    {		// wife present
		for (let ib = 0; ib < editChildButtons.length; ib++)
		{			// disable all editChild buttons
		    editChildButtons[ib].disabled	= true;
		}			// disable all editChild buttons
		let wifeGiven	    = form.WifeGivenName.value; 
		let wifeSurname     = form.WifeSurname.value;
		let treename		= form.treename.value;
        if (wifeSurname == '')
        {
            wifeSurname     = 'Wifeof' +
                               form.HusbGivenName.value +
                               form.HusbSurname.value;
        }
		let script	        = "editIndivid.php?idir=" + idir + 
                              "&rowid=Wife" +
							  "&initGivenName=" +
						                encodeURIComponent(wifeGiven) + 
							  "&initSurname=" +
						                encodeURIComponent(wifeSurname);
							  '&treeName=' +
						                encodeURIComponent(treename) +
                              '&initGender=F' +
                              '&lang=' + lang;
		let childWindow	    = openFrame("wifeFrame",
						                script,
						                "left");
		childWindows.push(childWindow);
    }		// wife present
    else
		document.getElementById('createWife').click();
}		// function editWife

/************************************************************************
 *  function chooseWife													*
 *																		*
 *  Prompt the user to set the wife by selecting from a list of			*
 *  existing women in the database.										*
 *																		*
 *  Parameters:															*
 *		this				<button id='chooseWife'> element			*
 ************************************************************************/
function chooseWife()
{
    var lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;
    var	form    	        = this.form;
    var url	                = "chooseIndivid.php?id=Wife&name=" +
        					encodeURIComponent(form.WifeSurname.value + ", " +
		                					   form.WifeGivenName.value);

    var	husbBirthSD	        = form.HusbBirthSD;
    if (husbBirthSD)
    {
		var	value	        = husbBirthSD.value;
		if (value.length > 0 && value != '0' && value != '-99999999')
		{
		    var	birthYear	= Math.floor(value / 10000);
		    url		        += "&birthmin=" + (birthYear - 40) +
			    	    	   "&birthmax=" + (birthYear + 40);
		}
    }			// birth SD field present
    url		                += '&treeName=' + 
                                encodeURIComponent(form.treename.value) +
                                '&lang=' + lang;

    var childWindow	        = openFrame("chooserFrame",
						                url,
						                "left");
}		// function chooseWife

/************************************************************************
 *  function gotWife													*
 *																		*
 *  This method is called when the XML file representing				*
 *  information on the new wife is retrieved.							*
 *																		*
 *  Parameters:															*
 *		xmlDoc			response as an XML document						*
 ************************************************************************/
function gotWife(xmlDoc)
{
    var	famForm	= document.famForm;

    // get the individual info from the XML file
    var newIdir	= xmlDoc.getElementsByTagName("idir");
    if (newIdir === null)
		alert("editMarriages.js: gotWife: newIdir is null");
    newIdir		= newIdir[0].textContent;
    famForm.IDIRWife.value	= newIdir;
    document.getElementById('editWife').disabled	= false;
    document.getElementById('chooseWife').disabled	= false;
    document.getElementById('createWife').disabled	= false;
    var newGiven	= xmlDoc.getElementsByTagName("givenname");
    if (newGiven === null)
		alert("editMarriages.js: gotWife: newGiven is null");
    newGiven		= newGiven[0].textContent;
    famForm.WifeGivenName.value	= newGiven;
    var newSurname	= xmlDoc.getElementsByTagName("surname");
    if (newIdir === null)
		alert("editMarriages.js: gotWife: newSurname is null");
    newSurname		= newSurname[0].textContent;
    famForm.WifeSurname.value	= newSurname;
}

/************************************************************************
 *  function noWife														*
 *																		*
 *  This method is called if there is no wife response file.			*
 ************************************************************************/
function noWife()
{
    alert ('Missing XML file response for selecting existing wife.');
}		// function noWife

/************************************************************************
 *  function createHusb													*
 *																		*
 *  This method is called when the user requests to add 				*
 *  a new individual to the marriage as husband							*
 *																		*
 *  Input:																*
 *		this		<button id='createHusb'> element 					*
 *		e           click Event                                         *
 ************************************************************************/
function createHusb(e)
{
    var lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;
    var	form		        = this.form;
    var script		        = "editIndivid.php?rowid=Husb&initGender=0" +
		                      "&initGivenName=" +
                        encodeURIComponent(form.HusbGivenName.value) + 
		                      "&initSurname=" + 
                        encodeURIComponent(form.HusbSurname.value) + 
    	                      '&treeName=' + 
                        encodeURIComponent(form.treename.value) +
                              '&lang=' + lang;
    var childWindow	        = openFrame("husbFrame",
						                script,
						                "left");
    childWindows.push(childWindow);
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {				// disable all editChild buttons
		editChildButtons[ib].disabled	= true;
    }				// disable all editChild buttons
}	// function createHusb

/************************************************************************
 *  function createWife													*
 *																		*
 *  This method is called when the user requests to add 				*
 *  a new individual to the marriage as wife.							*
 *																		*
 *  Input:																*
 *		this		<button id='createWife'> element					*
 *		e           click Event                                         *
 ************************************************************************/
function createWife(e)
{
    var lang    			= 'en';
    if ('lang' in args)
        lang                = args.lang;
    var	form		        = this.form;
	let wifeGiven	        = form.WifeGivenName.value; 
	let wifeSurname         = form.WifeSurname.value;
	let treename		    = form.treename.value;
    if (wifeSurname == '')
    {
        wifeSurname         = 'Wifeof' +
                                form.HusbGivenName.value +
                                form.HusbSurname.value;
    }
    var script		        = "editIndivid.php?rowid=Wife&initGender=1" +
		                      "&initGivenName=" +
                                        encodeURIComponent(wifeGiven) + 
		                      "&initSurname=" +
                                        encodeURIComponent(wifeSurname) + 
    	                      '&treeName=' +
                                        encodeURIComponent(treename) +
                              '&lang=' + lang;
    var childWindow	        = openFrame("wifeFrame",
						                script,
						                "left");
    childWindows.push(childWindow);
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {				// disable all editChild buttons
		editChildButtons[ib].disabled	= true;
    }				// disable all editChild buttons
}	// function createWife

/************************************************************************
 *  function em1KeyDown													*
 *																		*
 *  Handle key strokes that apply to the dialog as a whole.  For example*
 *  the key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Marriage button.												*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function em1KeyDown(e)
{
    if (!e)
    {		                // browser is not W3C compliant
		e	            =  window.event;	// IE
    }		                // browser is not W3C compliant
    var	code	        = e.key;
    var	form	        = document.famForm;

    if (e.ctrlKey)
    {		                // ctrl
		if (key == 's' || key == 'S')
		{		            // letter 'S'
		    var	button	= form.update;
		    button.click();
		    return false;	// do not perform standard action
		}		            // letter 'S'
    }		                // ctrl

    // handle alt-Key combinations
    if (e.altKey)
    {		                // alt
		var	button	= null;
		// take action based upon code
		switch (code)
		{
		    case 'a':
		    case 'A':
		    {				// letter 'A'
				button	= document.getElementById('addCitation');
				button.click();
				return false;
		    }				// letter 'A'
    
		    case 'e':
		    case 'E':
		    {				// letter 'E'
				button	= document.getElementById('addChild');
				button.click();
				return false;
		    }				// letter 'E'
    
		    case 'h':
		    case 'H':
		    {				// letter 'H'
				button	= document.getElementById('createHusb');
				button.click();
				return false;
		    }				// letter 'H'
    
		    case 'n':
		    case 'N':
		    {				// letter 'N'
				button	= document.getElementById('addNewChild');
				button.click();
				return false;
		    }				// letter 'N'
    
		    case 'o':
		    case 'O':
		    {				// letter 'O'
				button	= document.getElementById('orderChildren');
				button.click();
				return false;
		    }				// letter 'O'

		    case 'p':
		    case 'P':
		    {				// letter 'P'
				button	= document.getElementById('Pictures');
				button.click();
				return false;
		    }				// letter 'P'
    
		    case 'u':
		    case 'U':
		    {				// letter 'U'
				button	= document.getElementById('update');
				button.click();
				return false;
		    }				// letter 'U'
    
            case 'v':
		    case 'V':
		    {		    	// letter 'V'
				button	= document.getElementById('Events');
				button.click();
				return false;
		    }		    	// letter 'V'
    
		    case 'w':
		    case 'W':
		    {		    	// letter 'W'
				button	= document.getElementById('createWife');
				button.click();
				return false;
		    }		    	// letter 'W'

		    default:
		    {
					//alert("alt-" + code);
				return true;
		    } 
		}	            	// switch on key code
    }		            	// alt key held down

    return true;
}		// function em1KeyDown
